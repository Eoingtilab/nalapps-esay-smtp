<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product-native mail settings: provider quick-setup, SMTP or API key
 * connection, test delivery and PHPMailer wiring.
 *
 * The option key, encrypted-password field and phpmailer_init contract are
 * preserved unchanged from the original commerce add-on release. The
 * encrypted API key field follows the same site-scoped encryption contract.
 */
class Smtp {
	const OPTION_KEY = 'nalapps_easy_smtp_settings';

	const PROVIDERS = array(
		'custom'   => array(
			'label' => '직접 입력',
		),
		'brevo'    => array(
			'label'    => 'Brevo',
			'signup'   => 'https://app.brevo.com/account/register',
			'host'     => 'smtp-relay.brevo.com',
			'port'     => 587,
			'username' => '',
		),
		'mailgun'  => array(
			'label'    => 'Mailgun',
			'signup'   => 'https://signup.mailgun.com/new/signup',
			'host'     => 'smtp.mailgun.org',
			'port'     => 587,
			'username' => '',
		),
		'sendgrid' => array(
			'label'    => 'SendGrid',
			'signup'   => 'https://signup.sendgrid.com/',
			'host'     => 'smtp.sendgrid.net',
			'port'     => 587,
			'username' => 'apikey',
		),
	);

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_nes_save_smtp', array( $this, 'save' ) );
		add_action( 'admin_post_nes_send_test', array( $this, 'send_test' ) );
		add_action( 'phpmailer_init', array( $this, 'configure_mailer' ) );
	}

	public function register_menu(): void {
		add_menu_page( '간편 SMTP', '간편 SMTP', 'manage_options', 'nes-easy-smtp-dashboard', array( $this, 'render' ), 'dashicons-email-alt', 80 );
		add_submenu_page( 'nes-easy-smtp-dashboard', '메일 설정', '메일 설정', 'manage_options', 'nes-easy-smtp-dashboard', array( $this, 'render' ) );
	}

	public function enqueue_assets(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'nes-easy-smtp-dashboard' !== $page ) {
			return;
		}
		wp_enqueue_style( 'nes-nalapps-admin-ui', NES_URL . 'assets/nalapps-admin-ui.css', array(), NES_VERSION );
		wp_enqueue_style( 'nes-maintenance', NES_URL . 'assets/maintenance.css', array( 'nes-nalapps-admin-ui' ), NES_VERSION );
		wp_enqueue_script( 'nes-smtp-settings', NES_URL . 'assets/smtp-settings.js', array(), NES_VERSION, true );
	}

	public function render(): void {
		$this->guard_page();
		$settings = self::settings();
		?>
		<div class="wrap nalapps-shell nes-admin-screen nalapps-has-global-header">
			<?php $this->notice(); ?>
			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>메일 발송 설정</h2>
						<p>메일 서비스를 선택하면 접속 정보가 자동으로 채워집니다. SMTP 비밀번호와 API 키는 이 사이트에서만 해독 가능하게 암호화되어 저장됩니다.</p>
					</div>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nalapps-form-stack" id="nes-smtp-form">
					<input type="hidden" name="action" value="nes_save_smtp">
					<?php wp_nonce_field( 'nes_save_smtp' ); ?>

					<div class="nalapps-toggle-row">
						<div class="nalapps-toggle-copy">
							<strong>메일 발송 사용</strong>
							<span>WordPress 메일(wp_mail)을 아래 설정으로 발송합니다.</span>
						</div>
						<label class="nalapps-switch"><input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'] ); ?>><span class="nalapps-switch__track"></span></label>
					</div>

					<div class="nes-provider-step">
						<span class="nes-step-label">1. 메일 서비스 선택</span>
						<div class="nes-provider-grid" role="group" aria-label="메일 서비스 선택">
							<button type="button" class="nes-provider-card" data-provider="brevo">
								<span class="nes-provider-index">1</span>
								<span class="nes-provider-name">Brevo</span>
							</button>
							<button type="button" class="nes-provider-card" data-provider="mailgun">
								<span class="nes-provider-index">2</span>
								<span class="nes-provider-name">Mailgun</span>
							</button>
							<button type="button" class="nes-provider-card" data-provider="sendgrid">
								<span class="nes-provider-index">3</span>
								<span class="nes-provider-name">SendGrid</span>
							</button>
							<button type="button" class="nes-provider-card" data-provider="custom">
								<span class="nes-provider-index">4</span>
								<span class="nes-provider-name">직접 입력</span>
							</button>
						</div>
						<div class="nalapps-inline-actions nes-provider-select-row">
							<label for="nes-provider-select" class="screen-reader-text">메일 서비스 선택(드롭다운)</label>
							<select id="nes-provider-select" name="provider">
								<?php foreach ( self::PROVIDERS as $key => $meta ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['provider'], $key ); ?>><?php echo esc_html( $meta['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<a id="nes-provider-signup" class="button" href="#" target="_blank" rel="noopener noreferrer" hidden>가입하러 가기 ↗</a>
						</div>
					</div>

					<div class="nes-provider-step">
						<span class="nes-step-label">2. 연결 방식 선택</span>
						<div class="nalapps-radio-pills" role="radiogroup" aria-label="연결 방식">
							<label><input type="radio" name="connection_mode" value="smtp" <?php checked( $settings['connection_mode'], 'smtp' ); ?>> SMTP 계정 (호스트·사용자명·비밀번호)</label>
							<label><input type="radio" name="connection_mode" value="api" <?php checked( $settings['connection_mode'], 'api' ); ?>> API 키 (아이디·비밀번호 없이 키 하나로 연결)</label>
						</div>
					</div>

					<div class="nes-provider-step">
						<span class="nes-step-label">3. 접속 정보 입력</span>

						<table class="form-table nalapps-form-table nes-mode-smtp" role="presentation">
							<tr>
								<th><label for="nes-smtp-host">SMTP 서버</label></th>
								<td><input id="nes-smtp-host" class="regular-text" type="text" name="host" value="<?php echo esc_attr( $settings['host'] ); ?>" placeholder="smtp.example.com"></td>
							</tr>
							<tr>
								<th><label for="nes-smtp-port">포트</label></th>
								<td><input id="nes-smtp-port" class="small-text" type="number" min="1" max="65535" name="port" value="<?php echo esc_attr( (string) $settings['port'] ); ?>"></td>
							</tr>
							<tr>
								<th><label for="nes-smtp-encryption">암호화</label></th>
								<td>
									<select id="nes-smtp-encryption" name="encryption">
										<option value="none" <?php selected( $settings['encryption'], 'none' ); ?>>없음</option>
										<option value="tls" <?php selected( $settings['encryption'], 'tls' ); ?>>TLS</option>
										<option value="ssl" <?php selected( $settings['encryption'], 'ssl' ); ?>>SSL</option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="nes-smtp-username">사용자명</label></th>
								<td><input id="nes-smtp-username" class="regular-text" type="text" name="username" autocomplete="off" value="<?php echo esc_attr( $settings['username'] ); ?>" placeholder="Mailgun은 postmaster@발신도메인"></td>
							</tr>
							<tr>
								<th><label for="nes-smtp-password">비밀번호 / SMTP 키</label></th>
								<td>
									<input id="nes-smtp-password" class="regular-text" type="password" name="password" autocomplete="new-password" value="" placeholder="변경할 때만 입력">
									<p class="description">저장된 값은 화면에 다시 표시하지 않습니다.</p>
								</td>
							</tr>
						</table>

						<table class="form-table nalapps-form-table nes-mode-api" role="presentation">
							<tr>
								<th><label for="nes-api-key">API 키</label></th>
								<td>
									<input id="nes-api-key" class="regular-text" type="password" name="api_key" autocomplete="off" value="" placeholder="변경할 때만 입력">
									<p class="description">저장된 키는 화면에 다시 표시하지 않습니다.</p>
								</td>
							</tr>
							<tr class="nes-field-mailgun-domain">
								<th><label for="nes-mailgun-domain">Mailgun 발신 도메인</label></th>
								<td><input id="nes-mailgun-domain" class="regular-text" type="text" name="mailgun_domain" value="<?php echo esc_attr( $settings['mailgun_domain'] ); ?>" placeholder="mg.example.com"></td>
							</tr>
							<tr class="nes-field-mailgun-domain">
								<th><label for="nes-mailgun-region">Mailgun 지역</label></th>
								<td>
									<select id="nes-mailgun-region" name="mailgun_region">
										<option value="us" <?php selected( $settings['mailgun_region'], 'us' ); ?>>US</option>
										<option value="eu" <?php selected( $settings['mailgun_region'], 'eu' ); ?>>EU</option>
									</select>
								</td>
							</tr>
						</table>
					</div>

					<table class="form-table nalapps-form-table" role="presentation">
						<tr>
							<th><label for="nes-smtp-from-email">발신 이메일</label></th>
							<td><input id="nes-smtp-from-email" class="regular-text" type="email" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>"></td>
						</tr>
						<tr>
							<th><label for="nes-smtp-from-name">발신자 이름</label></th>
							<td><input id="nes-smtp-from-name" class="regular-text" type="text" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>"></td>
						</tr>
					</table>

					<div class="nalapps-inline-actions">
						<?php submit_button( '메일 설정 저장', 'primary', 'submit', false ); ?>
					</div>
				</form>
			</section>

			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>테스트 이메일</h2>
						<p>설정을 저장한 뒤 지정한 주소로 실제 WordPress 테스트 메일을 발송합니다.</p>
					</div>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nalapps-inline-actions">
					<input type="hidden" name="action" value="nes_send_test">
					<?php wp_nonce_field( 'nes_send_test' ); ?>
					<input class="regular-text" type="email" name="test_email" required value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<?php submit_button( '테스트 메일 발송', 'secondary', 'submit', false ); ?>
				</form>
			</section>
		</div>
		<?php
	}

	public function save(): void {
		$this->guard_action( 'nes_save_smtp' );
		$current    = self::settings();
		$password   = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$api_key    = isset( $_POST['api_key'] ) ? (string) wp_unslash( $_POST['api_key'] ) : '';
		$encryption = isset( $_POST['encryption'] ) ? sanitize_key( wp_unslash( $_POST['encryption'] ) ) : 'tls';
		$mode       = isset( $_POST['connection_mode'] ) ? sanitize_key( wp_unslash( $_POST['connection_mode'] ) ) : 'smtp';
		$provider   = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'custom';
		$region     = isset( $_POST['mailgun_region'] ) ? sanitize_key( wp_unslash( $_POST['mailgun_region'] ) ) : 'us';

		$data = array(
			'enabled'         => isset( $_POST['enabled'] ),
			'provider'        => array_key_exists( $provider, self::PROVIDERS ) ? $provider : 'custom',
			'connection_mode' => in_array( $mode, array( 'smtp', 'api' ), true ) ? $mode : 'smtp',
			'host'            => isset( $_POST['host'] ) ? sanitize_text_field( wp_unslash( $_POST['host'] ) ) : '',
			'port'            => isset( $_POST['port'] ) ? min( 65535, max( 1, absint( $_POST['port'] ) ) ) : 587,
			'encryption'      => in_array( $encryption, array( 'none', 'tls', 'ssl' ), true ) ? $encryption : 'tls',
			'username'        => isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '',
			'password_enc'    => '' !== $password ? Crypto::encrypt( $password ) : $current['password_enc'],
			'api_key_enc'     => '' !== $api_key ? Crypto::encrypt( $api_key ) : $current['api_key_enc'],
			'mailgun_domain'  => isset( $_POST['mailgun_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['mailgun_domain'] ) ) : '',
			'mailgun_region'  => in_array( $region, array( 'us', 'eu' ), true ) ? $region : 'us',
			'from_email'      => isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : '',
			'from_name'       => isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : '',
		);

		if ( $data['enabled'] && '' === $data['from_email'] ) {
			$this->redirect( '발신 이메일을 입력해 주세요.' );
		}
		if ( $data['enabled'] && 'smtp' === $data['connection_mode'] && '' === $data['host'] ) {
			$this->redirect( 'SMTP 서버 주소를 입력해 주세요.' );
		}
		if ( $data['enabled'] && 'api' === $data['connection_mode'] && '' === $data['api_key_enc'] ) {
			$this->redirect( 'API 키를 입력해 주세요.' );
		}
		if ( $data['enabled'] && 'api' === $data['connection_mode'] && 'mailgun' === $data['provider'] && '' === $data['mailgun_domain'] ) {
			$this->redirect( 'Mailgun 발신 도메인을 입력해 주세요.' );
		}
		if ( '' !== $password && '' === $data['password_enc'] ) {
			$this->redirect( '서버에서 비밀번호를 안전하게 암호화할 수 없습니다. OpenSSL 상태를 확인해 주세요.' );
		}
		if ( '' !== $api_key && '' === $data['api_key_enc'] ) {
			$this->redirect( '서버에서 API 키를 안전하게 암호화할 수 없습니다. OpenSSL 상태를 확인해 주세요.' );
		}

		update_option( self::OPTION_KEY, $data, false );
		$this->redirect( '메일 설정을 저장했습니다.' );
	}

	public function send_test(): void {
		$this->guard_action( 'nes_send_test' );
		$to = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
		if ( ! is_email( $to ) ) {
			$this->redirect( '올바른 테스트 이메일 주소를 입력해 주세요.' );
		}
		$sent = wp_mail( $to, '[NalApps] 메일 발송 테스트', '날라앱스 간편 SMTP 테스트 메일입니다.' );
		$this->redirect( $sent ? '테스트 메일을 발송했습니다.' : '테스트 메일 발송에 실패했습니다. 설정을 확인해 주세요.' );
	}

	public function configure_mailer( $phpmailer ): void {
		$settings = self::settings();
		if ( ! $settings['enabled'] || 'smtp' !== $settings['connection_mode'] ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['host'];
		$phpmailer->Port       = $settings['port'];
		$phpmailer->SMTPAuth   = '' !== $settings['username'];
		$phpmailer->Username   = $settings['username'];
		$phpmailer->Password   = Crypto::decrypt( $settings['password_enc'] );
		$phpmailer->SMTPSecure = 'none' === $settings['encryption'] ? '' : $settings['encryption'];
		$phpmailer->From       = $settings['from_email'];
		$phpmailer->FromName   = $settings['from_name'];
		$phpmailer->Timeout    = 15;
	}

	public static function settings(): array {
		$defaults = array(
			'enabled'         => false,
			'provider'        => 'custom',
			'connection_mode' => 'smtp',
			'host'            => '',
			'port'            => 587,
			'encryption'      => 'tls',
			'username'        => '',
			'password_enc'    => '',
			'api_key_enc'     => '',
			'mailgun_domain'  => '',
			'mailgun_region'  => 'us',
			'from_email'      => get_option( 'admin_email', '' ),
			'from_name'       => get_bloginfo( 'name' ),
		);
		$value = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $value ) ? $value : array(), $defaults );
	}

	private function guard_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '이 화면에 접근할 권한이 없습니다.', 'nalapps-easy-smtp' ) );
		}
	}

	private function guard_action( string $action ): void {
		$this->guard_page();
		check_admin_referer( $action );
	}

	private function redirect( string $message ): void {
		$args = array(
			'page'       => 'nes-easy-smtp-dashboard',
			'nes_notice' => $message,
		);
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	private function notice(): void {
		if ( ! isset( $_GET['nes_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$message = sanitize_text_field( wp_unslash( $_GET['nes_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}
