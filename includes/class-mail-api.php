<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends WordPress mail through a provider's HTTP API instead of SMTP when
 * the site owner selects API-key connection mode. Bypasses PHPMailer
 * entirely via the pre_wp_mail filter, so no SMTP account is required.
 */
class Mail_Api {
	public function __construct() {
		add_filter( 'pre_wp_mail', array( $this, 'maybe_send' ), 10, 2 );
	}

	public function maybe_send( $short_circuit, $atts ) {
		$settings = Smtp::settings();
		if ( ! $settings['enabled'] || 'api' !== $settings['connection_mode'] ) {
			return $short_circuit;
		}

		$key = Crypto::decrypt( $settings['api_key_enc'] );
		if ( '' === $key ) {
			return new \WP_Error( 'nes_api_key_missing', 'API 키가 설정되어 있지 않습니다.' );
		}

		$to      = $this->normalize_recipients( $atts['to'] ?? '' );
		$subject = (string) ( $atts['subject'] ?? '' );
		$message = (string) ( $atts['message'] ?? '' );
		$is_html = $this->is_html( $atts['headers'] ?? '' );

		if ( empty( $to ) ) {
			return new \WP_Error( 'nes_api_no_recipient', '수신자 주소가 없습니다.' );
		}

		switch ( $settings['provider'] ) {
			case 'brevo':
				return $this->send_brevo( $key, $settings, $to, $subject, $message, $is_html );
			case 'sendgrid':
				return $this->send_sendgrid( $key, $settings, $to, $subject, $message, $is_html );
			case 'mailgun':
				return $this->send_mailgun( $key, $settings, $to, $subject, $message, $is_html );
			default:
				return new \WP_Error( 'nes_api_provider_unsupported', 'API 키 발송을 지원하지 않는 서비스입니다.' );
		}
	}

	private function send_brevo( $key, $settings, $to, $subject, $message, $is_html ) {
		$body = array(
			'sender'  => array(
				'email' => $settings['from_email'],
				'name'  => $settings['from_name'],
			),
			'to'      => array_map(
				static function ( $email ) {
					return array( 'email' => $email );
				},
				$to
			),
			'subject' => $subject,
		);
		$body[ $is_html ? 'htmlContent' : 'textContent' ] = $message;

		return $this->post(
			'https://api.brevo.com/v3/smtp/email',
			array(
				'api-key'      => $key,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			wp_json_encode( $body )
		);
	}

	private function send_sendgrid( $key, $settings, $to, $subject, $message, $is_html ) {
		$body = array(
			'personalizations' => array(
				array(
					'to' => array_map(
						static function ( $email ) {
							return array( 'email' => $email );
						},
						$to
					),
				),
			),
			'from'              => array(
				'email' => $settings['from_email'],
				'name'  => $settings['from_name'],
			),
			'subject'           => $subject,
			'content'           => array(
				array(
					'type'  => $is_html ? 'text/html' : 'text/plain',
					'value' => $message,
				),
			),
		);

		return $this->post(
			'https://api.sendgrid.com/v3/mail/send',
			array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			wp_json_encode( $body )
		);
	}

	private function send_mailgun( $key, $settings, $to, $subject, $message, $is_html ) {
		$domain = trim( (string) $settings['mailgun_domain'] );
		if ( '' === $domain ) {
			return new \WP_Error( 'nes_mailgun_domain_missing', 'Mailgun 발신 도메인이 설정되어 있지 않습니다.' );
		}
		$region_host = 'eu' === $settings['mailgun_region'] ? 'api.eu.mailgun.net' : 'api.mailgun.net';
		$from        = '' !== $settings['from_name'] ? $settings['from_name'] . ' <' . $settings['from_email'] . '>' : $settings['from_email'];
		$body        = array(
			'from'    => $from,
			'to'      => implode( ',', $to ),
			'subject' => $subject,
		);
		$body[ $is_html ? 'html' : 'text' ] = $message;

		return $this->post(
			'https://' . $region_host . '/v3/' . rawurlencode( $domain ) . '/messages',
			array( 'Authorization' => 'Basic ' . base64_encode( 'api:' . $key ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$body,
			false
		);
	}

	private function post( $url, $headers, $body, $is_json = true ) {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => $headers,
				'body'    => $is_json ? $body : $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$detail = $this->extract_error_detail( wp_remote_retrieve_body( $response ) );
			$message = 'API 발송 서버가 오류를 반환했습니다. (HTTP ' . (int) $code . ')';
			if ( '' !== $detail ) {
				$message .= ' - ' . $detail;
			}
			return new \WP_Error( 'nes_api_send_failed', $message );
		}
		return true;
	}

	private function extract_error_detail( $raw_body ) {
		$data = json_decode( (string) $raw_body, true );
		if ( ! is_array( $data ) ) {
			return mb_substr( wp_strip_all_tags( (string) $raw_body ), 0, 200 );
		}
		foreach ( array( 'message', 'Message', 'error', 'error_description' ) as $key ) {
			if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
				return mb_substr( $data[ $key ], 0, 200 );
			}
		}
		if ( ! empty( $data['errors'][0]['message'] ) ) {
			return mb_substr( (string) $data['errors'][0]['message'], 0, 200 );
		}
		return '';
	}

	private function normalize_recipients( $to ) {
		$list = is_array( $to ) ? $to : explode( ',', (string) $to );
		$list = array_filter( array_map( 'trim', $list ) );
		return array_values(
			array_filter(
				$list,
				static function ( $email ) {
					return is_email( $email );
				}
			)
		);
	}

	private function is_html( $headers ) {
		$headers = is_array( $headers ) ? implode( "\n", $headers ) : (string) $headers;
		return false !== stripos( $headers, 'text/html' );
	}
}
