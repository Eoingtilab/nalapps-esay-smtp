<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Hybrid updater for NalApps Easy SMTP. */
class Update_Manager {
	const CACHE_KEY = 'nes_edd_version_info';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	public function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_wordpress_update' ) );
		add_action( 'admin_menu', array( $this, 'register_update_page' ), 40 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_nes_check_updates', array( $this, 'handle_manual_check' ) );
		add_action( 'admin_post_nes_install_update', array( $this, 'handle_install_update' ) );
		add_action( 'admin_notices', array( $this, 'render_update_notice' ) );
	}

	public function register_update_page() {
		add_submenu_page( 'nes-easy-smtp-dashboard', '업데이트', '업데이트', 'manage_options', 'nes-update', array( $this, 'render_update_page' ) );
	}

	public function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'nes-update' !== $page ) {
			return;
		}
		wp_enqueue_style( 'nes-nalapps-admin-ui', NES_URL . 'assets/nalapps-admin-ui.css', array(), NES_VERSION );
		wp_enqueue_style( 'nes-maintenance', NES_URL . 'assets/maintenance.css', array( 'nes-nalapps-admin-ui' ), NES_VERSION );
	}

	public function inject_wordpress_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$info = $this->get_remote_version( false );
		if ( is_wp_error( $info ) || ! $this->has_newer_version( $info ) ) {
			return $transient;
		}
		$plugin                         = plugin_basename( NES_FILE );
		$transient->response[ $plugin ] = $this->build_update_object( $info, $plugin );
		return $transient;
	}

	public function render_update_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}

		$info           = $this->get_remote_version( false );
		$error          = is_wp_error( $info ) ? $info->get_error_message() : '';
		$latest         = ( ! $error && ! empty( $info['new_version'] ) ) ? (string) $info['new_version'] : '확인할 수 없음';
		$available      = ! $error && $this->has_newer_version( $info );
		$license_status = $this->get_license_status();
		$license_valid  = in_array( $license_status, array( 'valid', 'active' ), true );
		$package        = ! $error ? $this->get_package_url( $info ) : '';
		$download_ready = $available && $license_valid && '' !== $package && current_user_can( 'update_plugins' );
		$last_checked   = get_option( 'nes_update_last_checked', '' );
		?>
		<div class="wrap nalapps-shell nes-update-page nalapps-has-global-header">
			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>제품 업데이트</h2>
						<p>EDD 업데이트 서버와 설치 버전을 비교하고 검증된 패키지로 업데이트합니다. 실제 실행 직전 현재 코드와 데이터는 자동 백업됩니다.</p>
					</div>
				</div>

				<div class="nalapps-grid nalapps-grid-2 nes-version-grid">
					<div class="nes-version-card"><span>현재 버전</span><strong><?php echo esc_html( NES_VERSION ); ?></strong></div>
					<div class="nes-version-card"><span>최신 버전</span><strong><?php echo esc_html( $latest ); ?></strong></div>
				</div>

				<div class="nes-meta-row">
					<span><strong>라이선스</strong> <?php echo esc_html( $license_valid ? '활성화됨' : $license_status ); ?></span>
					<?php if ( $last_checked ) : ?>
						<span><strong>마지막 확인</strong> <?php echo esc_html( $last_checked ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $error ) : ?>
					<div class="nalapps-notice is-danger"><?php echo esc_html( $error ); ?></div>
				<?php elseif ( $available && ! current_user_can( 'update_plugins' ) ) : ?>
					<div class="nalapps-notice is-warning">새 버전 <?php echo esc_html( $latest ); ?>이 있지만 현재 계정에는 플러그인 업데이트 실행 권한이 없습니다.</div>
				<?php elseif ( $download_ready ) : ?>
					<div class="nalapps-notice"><strong><?php echo esc_html( $latest ); ?></strong> 업데이트가 준비되었습니다.</div>
				<?php elseif ( $available && ! $license_valid ) : ?>
					<div class="nalapps-notice is-warning">새 버전 <?php echo esc_html( $latest ); ?>이 있지만 업데이트 설치를 위해 라이선스 활성화가 필요합니다.</div>
				<?php elseif ( $available && '' === $package ) : ?>
					<div class="nalapps-notice is-danger">새 버전은 확인됐지만 설치 패키지 URL을 받지 못했습니다. EDD Update File 연결을 확인하세요.</div>
				<?php else : ?>
					<div class="nalapps-notice is-success">현재 최신 버전을 사용 중입니다.</div>
				<?php endif; ?>

				<div class="nalapps-inline-actions nes-update-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="nes_check_updates">
						<?php wp_nonce_field( 'nes_check_updates' ); ?>
						<button type="submit" class="button">업데이트 확인</button>
					</form>
					<?php if ( $download_ready ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="nes_install_update">
							<?php wp_nonce_field( 'nes_install_update' ); ?>
							<button type="submit" class="button button-primary" onclick="return confirm('업데이트 직전 현재 코드와 데이터를 자동 백업한 후 <?php echo esc_js( $latest ); ?> 버전으로 업데이트합니다. 계속하시겠습니까?');"><?php echo esc_html( $latest ); ?>로 지금 업데이트</button>
						</form>
					<?php elseif ( ! $available && ! $error ) : ?>
						<button type="button" class="button" disabled>최신 버전 사용 중</button>
					<?php endif; ?>
				</div>
				<p class="nalapps-help">이전 버전으로 되돌리거나 로컬 안전 백업을 복원하려면 <a href="<?php echo esc_url( admin_url( 'admin.php?page=nes-maintenance' ) ); ?>">백업 및 복구</a>를 사용하세요.</p>
			</section>
		</div>
		<?php
	}

	public function handle_manual_check() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_check_updates' );
		delete_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		$this->get_remote_version( true );
		wp_update_plugins();
		wp_safe_redirect( admin_url( 'admin.php?page=nes-update&checked=1' ) );
		exit;
	}

	public function handle_install_update() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_install_update' );
		if ( ! in_array( $this->get_license_status(), array( 'valid', 'active' ), true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=nes-update&license_required=1' ) );
			exit;
		}
		$info    = $this->get_remote_version( true );
		$package = ! is_wp_error( $info ) ? $this->get_package_url( $info ) : '';
		if ( is_wp_error( $info ) || ! $this->has_newer_version( $info ) || '' === $package ) {
			wp_safe_redirect( admin_url( 'admin.php?page=nes-update&update_error=1' ) );
			exit;
		}
		$plugin  = plugin_basename( NES_FILE );
		$updates = get_site_transient( 'update_plugins' );
		if ( ! is_object( $updates ) ) {
			$updates = new \stdClass();
		}
		if ( ! isset( $updates->response ) || ! is_array( $updates->response ) ) {
			$updates->response = array();
		}
		$updates->response[ $plugin ] = $this->build_update_object( $info, $plugin );
		set_site_transient( 'update_plugins', $updates );
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $plugin );
		delete_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		$state = is_wp_error( $result ) || false === $result ? 'update_error=1' : 'updated=1';
		wp_safe_redirect( admin_url( 'admin.php?page=nes-update&' . $state ) );
		exit;
	}

	public function render_update_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'nes-update' === $page ) {
			return;
		}
		$info = $this->get_remote_version( false );
		if ( is_wp_error( $info ) || ! $this->has_newer_version( $info ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible"><p><strong>NalApps Easy SMTP:</strong> 새 버전 <?php echo esc_html( (string) $info['new_version'] ); ?>을 사용할 수 있습니다. <a href="<?php echo esc_url( admin_url( 'admin.php?page=nes-update' ) ); ?>">업데이트 화면에서 설치</a></p></div>
		<?php
	}

	private function get_remote_version( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		$params  = array(
			'edd_action'  => 'get_version',
			'item_id'     => NES_EDD_ITEM_ID,
			'url'         => home_url(),
			'php_version' => PHP_VERSION,
			'wp_version'  => get_bloginfo( 'version' ),
		);
		$license = trim( (string) get_option( License::SDK_KEY_OPTION, '' ) );
		if ( '' !== $license ) {
			$params['license'] = $license;
		}
		$response = wp_remote_get(
			add_query_arg( $params, NES_STORE_URL ),
			array(
				'timeout'     => 15,
				'sslverify'   => true,
				'redirection' => 3,
			)
		);
		update_option( 'nes_update_last_checked', current_time( 'mysql' ), false );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 > $code || 300 <= $code ) {
			return new \WP_Error( 'nes_update_http', '업데이트 서버가 정상 응답하지 않았습니다.', array( 'status' => $code ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'nes_update_json', '업데이트 서버 응답을 해석할 수 없습니다.' );
		}
		if ( ! empty( $data['error'] ) ) {
			$message = ! empty( $data['msg'] ) ? (string) $data['msg'] : '업데이트 정보를 가져오지 못했습니다.';
			return new \WP_Error( 'nes_update_api', $message );
		}
		$data = $this->normalize_remote_info( $data );
		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	private function normalize_remote_info( $info ) {
		if ( ! is_array( $info ) ) {
			return array();
		}
		$package = '';
		if ( ! empty( $info['package'] ) ) {
			$package = esc_url_raw( (string) $info['package'] );
		} elseif ( ! empty( $info['download_link'] ) ) {
			$package = esc_url_raw( (string) $info['download_link'] );
		}
		$info['package'] = $package;
		if ( empty( $info['download_link'] ) && '' !== $package ) {
			$info['download_link'] = $package;
		}
		return $info;
	}

	private function get_package_url( $info ) {
		if ( ! is_array( $info ) ) {
			return '';
		}
		if ( ! empty( $info['package'] ) ) {
			return esc_url_raw( (string) $info['package'] );
		}
		if ( ! empty( $info['download_link'] ) ) {
			return esc_url_raw( (string) $info['download_link'] );
		}
		return '';
	}

	private function has_newer_version( $info ) {
		return is_array( $info ) && ! empty( $info['new_version'] ) && false !== $info['new_version'] && version_compare( (string) $info['new_version'], NES_VERSION, '>' );
	}

	private function build_update_object( $info, $plugin ) {
		$update = array(
			'id'          => 'nalapps-easy-smtp',
			'slug'        => dirname( $plugin ),
			'plugin'      => $plugin,
			'new_version' => sanitize_text_field( (string) $info['new_version'] ),
			'url'         => ! empty( $info['url'] ) ? esc_url_raw( (string) $info['url'] ) : NES_STORE_URL,
			'package'     => $this->get_package_url( $info ),
		);
		foreach ( array( 'tested', 'requires', 'requires_php' ) as $field ) {
			if ( isset( $info[ $field ] ) ) {
				$update[ $field ] = sanitize_text_field( (string) $info[ $field ] );
			}
		}
		if ( isset( $info['icons'] ) && is_array( $info['icons'] ) ) {
			$update['icons'] = $info['icons'];
		}
		if ( isset( $info['banners'] ) && is_array( $info['banners'] ) ) {
			$update['banners'] = $info['banners'];
		}
		return (object) $update;
	}

	private function get_license_status() {
		$data = get_option( License::SDK_STATUS_OPTION );
		if ( is_object( $data ) && isset( $data->license ) ) {
			return sanitize_key( (string) $data->license );
		}
		if ( is_array( $data ) && isset( $data['license'] ) ) {
			return sanitize_key( (string) $data['license'] );
		}
		return 'inactive';
	}
}
