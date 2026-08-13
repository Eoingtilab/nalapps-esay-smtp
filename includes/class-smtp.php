<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product-native SMTP settings, test delivery and PHPMailer wiring.
 *
 * The option key, encrypted-password field and phpmailer_init contract are
 * preserved unchanged from the original commerce add-on release.
 */
class Smtp {
	const OPTION_KEY = 'nalapps_easy_smtp_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_nes_save_smtp', array( $this, 'save' ) );
		add_action( 'admin_post_nes_send_test', array( $this, 'send_test' ) );
		add_action( 'phpmailer_init', array( $this, 'configure_mailer' ) );
	}

	public function register_menu(): void {
		add_menu_page( '간편 SMTP', '간편 SMTP', 'manage_options', 'nes-easy-smtp-dashboard', array( $this, 'render' ), 'dashicons-email-alt', 80 );
		add_submenu_page( 'nes-easy-smtp-dashboard', 'SMTP 설정', 'SMTP 설정', 'manage_options', 'nes-easy-smtp-dashboard', array( $this, 'render' ) );
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
						<h2>SMTP 연결 설정</h2>
						<p>SMTP 인증정보는 이 사이트에서만 해독 가능하게 암호화되어 저장됩니다.</p>
					</div>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nalapps-form-stack">
					<input type="hidden" name="action" value="nes_save_smtp">
					<?php wp_nonce_field( 'nes_save_smtp' ); ?>
					<table class="form-table nalapps-form-table" role="presentation">
						<tr>
							<th><label for="nes-smtp-enabled">SMTP 사용</label></th>
							<td><label><input id="nes-smtp-enabled" type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'] ); ?>> WordPress 메일을 SMTP로 발송합니다.</label></td>
						</tr>
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
							<td><input id="nes-smtp-username" class="regular-text" type="text" name="username" autocomplete="off" value="<?php echo esc_attr( $settings['username'] ); ?>"></td>
						</tr>
						<tr>
							<th><label for="nes-smtp-password">비밀번호</label></th>
							<td>
								<input id="nes-smtp-password" class="regular-text" type="password" name="password" autocomplete="new-password" value="" placeholder="변경할 때만 입력">
								<p class="description">저장된 비밀번호는 화면에 다시 표시하지 않습니다.</p>
							</td>
						</tr>
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
						<?php submit_button( 'SMTP 설정 저장', 'primary', 'submit', false ); ?>
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
		$encryption = isset( $_POST['encryption'] ) ? sanitize_key( wp_unslash( $_POST['encryption'] ) ) : 'tls';
		$data       = array(
			'enabled'      => isset( $_POST['enabled'] ),
			'host'         => isset( $_POST['host'] ) ? sanitize_text_field( wp_unslash( $_POST['host'] ) ) : '',
			'port'         => isset( $_POST['port'] ) ? min( 65535, max( 1, absint( $_POST['port'] ) ) ) : 587,
			'encryption'   => in_array( $encryption, array( 'none', 'tls', 'ssl' ), true ) ? $encryption : 'tls',
			'username'     => isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '',
			'password_enc' => '' !== $password ? Crypto::encrypt( $password ) : $current['password_enc'],
			'from_email'   => isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : '',
			'from_name'    => isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : '',
		);

		if ( $data['enabled'] && ( '' === $data['host'] || '' === $data['from_email'] ) ) {
			$this->redirect( 'SMTP 서버와 발신 이메일을 입력해 주세요.' );
		}
		if ( '' !== $password && '' === $data['password_enc'] ) {
			$this->redirect( '서버에서 비밀번호를 안전하게 암호화할 수 없습니다. OpenSSL 상태를 확인해 주세요.' );
		}

		update_option( self::OPTION_KEY, $data, false );
		$this->redirect( 'SMTP 설정을 저장했습니다.' );
	}

	public function send_test(): void {
		$this->guard_action( 'nes_send_test' );
		$to = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
		if ( ! is_email( $to ) ) {
			$this->redirect( '올바른 테스트 이메일 주소를 입력해 주세요.' );
		}
		$sent = wp_mail( $to, '[NalApps] SMTP 테스트', '날라앱스 간편 SMTP 테스트 메일입니다.' );
		$this->redirect( $sent ? '테스트 메일을 발송했습니다.' : '테스트 메일 발송에 실패했습니다. SMTP 서버와 인증정보를 확인해 주세요.' );
	}

	public function configure_mailer( $phpmailer ): void {
		$settings = self::settings();
		if ( ! $settings['enabled'] ) {
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
			'enabled'      => false,
			'host'         => '',
			'port'         => 587,
			'encryption'   => 'tls',
			'username'     => '',
			'password_enc' => '',
			'from_email'   => get_option( 'admin_email', '' ),
			'from_name'    => get_bloginfo( 'name' ),
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
