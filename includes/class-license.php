<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Product-native EDD Software Licensing management. */
class License {
	const SDK_KEY_OPTION    = 'nalapps-easy-smtp_license_key';
	const SDK_STATUS_OPTION = 'nalapps-easy-smtp_license';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 35 );
		add_action( 'admin_post_nes_activate_license', array( $this, 'activate_license' ) );
		add_action( 'admin_post_nes_deactivate_license', array( $this, 'deactivate_license' ) );
		add_action( 'admin_post_nes_check_license', array( $this, 'check_license' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
	}

	public function register_page() {
		add_submenu_page( 'nes-easy-smtp-dashboard', '라이선스', '라이선스', 'manage_options', 'nes-license', array( $this, 'render_page' ) );
	}

	public function is_valid() {
		return '' !== $this->key() && in_array( $this->get_status(), array( 'valid', 'active' ), true );
	}

	public function get_status() {
		$data = get_option( self::SDK_STATUS_OPTION );
		if ( is_object( $data ) && isset( $data->license ) ) {
			return sanitize_key( (string) $data->license );
		}
		if ( is_array( $data ) && isset( $data['license'] ) ) {
			return sanitize_key( (string) $data['license'] );
		}
		return 'inactive';
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}

		$status = $this->get_status();
		$key    = $this->key();
		?>
		<div class="wrap nalapps-shell nes-license-page nalapps-has-global-header">
			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>제품 라이선스</h2>
						<p>구매한 시리얼키를 등록하고 이 사이트에서 활성화합니다. SMTP 발송은 라이선스 상태와 관계없이 유지되며 업데이트와 유료 서비스 권한에 사용됩니다.</p>
					</div>
				</div>

				<div class="nalapps-notice <?php echo $this->is_valid() ? 'is-success' : 'is-warning'; ?>">
					<strong>현재 상태:</strong> <?php echo esc_html( $this->status_label( $status ) ); ?>
				</div>

				<div class="nes-license-control-row">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nalapps-form-stack nes-license-primary-form">
						<input type="hidden" name="action" value="nes_activate_license">
						<?php wp_nonce_field( 'nes_activate_license' ); ?>
						<div class="nalapps-field nes-license-key-field">
							<label for="nes-license-key">시리얼키</label>
							<input id="nes-license-key" type="text" name="license_key" autocomplete="off" maxlength="256" value="<?php echo esc_attr( $key ); ?>" placeholder="구매한 시리얼키를 입력하세요">
						</div>
						<button type="submit" class="button button-primary">저장하고 활성화</button>
					</form>

					<?php if ( '' !== $key ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nes-license-action-form">
							<input type="hidden" name="action" value="nes_check_license">
							<?php wp_nonce_field( 'nes_check_license' ); ?>
							<button type="submit" class="button">라이선스 확인</button>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nes-license-action-form">
							<input type="hidden" name="action" value="nes_deactivate_license">
							<?php wp_nonce_field( 'nes_deactivate_license' ); ?>
							<button type="submit" class="button">이 사이트에서 비활성화</button>
						</form>
					<?php endif; ?>
				</div>

				<p class="nalapps-help">시리얼키는 이 사이트의 WordPress 옵션에만 저장되며 시스템 정보·데이터 내보내기에는 포함되지 않습니다.</p>
			</section>
		</div>
		<?php
	}

	public function activate_license() {
		$this->assert_admin();
		check_admin_referer( 'nes_activate_license' );
		$key = isset( $_POST['license_key'] ) ? $this->sanitize_key( wp_unslash( $_POST['license_key'] ) ) : '';
		if ( '' === $key ) {
			$this->redirect( 'empty_key' );
		}
		update_option( self::SDK_KEY_OPTION, $key, false );
		$data = $this->request( 'activate_license', $key );
		$this->store_status( $data );
		$this->redirect( $this->response_is_valid( $data ) ? 'activated' : 'activation_failed' );
	}

	public function check_license() {
		$this->assert_admin();
		check_admin_referer( 'nes_check_license' );
		$key = $this->key();
		if ( '' === $key ) {
			$this->redirect( 'empty_key' );
		}
		$data = $this->request( 'check_license', $key );
		$this->store_status( $data );
		$this->redirect( $this->response_is_valid( $data ) ? 'valid' : 'invalid' );
	}

	public function deactivate_license() {
		$this->assert_admin();
		check_admin_referer( 'nes_deactivate_license' );
		$key = $this->key();
		if ( '' !== $key ) {
			$this->request( 'deactivate_license', $key );
		}
		update_option( self::SDK_STATUS_OPTION, (object) array( 'license' => 'inactive' ), false );
		delete_transient( Update_Manager::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		$this->redirect( 'deactivated' );
	}

	public function render_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) || $this->is_valid() ) {
			return;
		}
		?>
		<div class="notice notice-warning"><p><strong>NalApps Easy SMTP:</strong> 라이선스가 아직 활성화되지 않았습니다. 기존 SMTP 발송은 계속 동작하며, 새 버전 업데이트를 받으려면 <a href="<?php echo esc_url( admin_url( 'admin.php?page=nes-license' ) ); ?>">시리얼키를 등록/활성화</a>해 주세요.</p></div>
		<?php
	}

	private function request( $action, $key ) {
		$response = wp_remote_post(
			NES_STORE_URL,
			array(
				'timeout'   => 15,
				'sslverify' => true,
				'body'      => array(
					'edd_action' => $action,
					'license'    => $key,
					'item_id'    => NES_EDD_ITEM_ID,
					'url'        => home_url(),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'nes_license_http', '라이선스 서버가 정상 응답하지 않았습니다.' );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ) );
		return is_object( $data ) ? $data : new \WP_Error( 'nes_license_json', '라이선스 서버 응답을 해석할 수 없습니다.' );
	}

	private function store_status( $data ) {
		if ( is_wp_error( $data ) ) {
			update_option( self::SDK_STATUS_OPTION, (object) array( 'license' => 'error' ), false );
			return;
		}
		update_option( self::SDK_STATUS_OPTION, $data, false );
		delete_transient( Update_Manager::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
	}

	private function response_is_valid( $data ) {
		return is_object( $data ) && isset( $data->license ) && in_array( sanitize_key( (string) $data->license ), array( 'valid', 'active' ), true );
	}

	private function key() {
		return trim( (string) get_option( self::SDK_KEY_OPTION, '' ) );
	}

	private function sanitize_key( $key ) {
		$key = trim( (string) $key );
		$key = preg_replace( '/[^A-Za-z0-9_-]/', '', $key );
		return substr( (string) $key, 0, 256 );
	}

	private function assert_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}
	}

	private function status_label( $status ) {
		$labels = array(
			'valid'         => '활성화됨',
			'active'        => '활성화됨',
			'inactive'      => '비활성',
			'expired'       => '만료됨',
			'disabled'      => '비활성 처리됨',
			'invalid'       => '유효하지 않음',
			'site_inactive' => '사이트 미활성',
			'error'         => '확인 오류',
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	private function redirect( $state ) {
		wp_safe_redirect( admin_url( 'admin.php?page=nes-license&state=' . sanitize_key( $state ) ) );
		exit;
	}
}
