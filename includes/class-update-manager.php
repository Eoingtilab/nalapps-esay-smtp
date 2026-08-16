<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater for a free product: checks the public GitHub Releases API for the
 * latest verified version and installs the matching immutable ZIP asset.
 * No license key is required.
 */
class Update_Manager {
	const CACHE_KEY    = 'nes_github_version_info';
	const CACHE_TTL    = 6 * HOUR_IN_SECONDS;
	const RELEASES_API = 'https://api.github.com/repos/Eoingtilab/nalapps-esay-smtp/releases/latest';

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
		$package        = ! $error ? $this->get_package_url( $info ) : '';
		$download_ready = $available && '' !== $package && current_user_can( 'update_plugins' );
		$last_checked   = get_option( 'nes_update_last_checked', '' );
		?>
		<div class="wrap nalapps-shell nes-update-page nalapps-has-global-header">
			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>제품 업데이트</h2>
						<p>GitHub Release와 설치 버전을 비교합니다. 무료 제품이라 라이선스 확인 없이 바로 설치할 수 있으며, 실제 실행 직전 현재 코드와 데이터는 자동 백업됩니다.</p>
					</div>
				</div>

				<div class="nalapps-grid nalapps-grid-2 nes-version-grid">
					<div class="nes-version-card"><span>현재 버전</span><strong><?php echo esc_html( NES_VERSION ); ?></strong></div>
					<div class="nes-version-card"><span>최신 버전</span><strong><?php echo esc_html( $latest ); ?></strong></div>
				</div>

				<div class="nes-meta-row">
					<span><strong>라이선스</strong> 무료 (자동 활성)</span>
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
				<?php elseif ( $available && '' === $package ) : ?>
					<div class="nalapps-notice is-danger">새 버전은 확인됐지만 설치 패키지 URL을 받지 못했습니다. GitHub Release 자산을 확인하세요.</div>
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
							<button type="submit" class="button button-primary" onclick="return confirm('업데이트 직전 현재 코드와 데이터를 자동 백업한 후 <?php echo esc_js( $latest ); ?> 버전으로 업데이트합니다. 계속하시겠습니까?');">지금 업데이트</button>
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
		$response = wp_remote_get(
			self::RELEASES_API,
			array(
				'timeout'     => 15,
				'sslverify'   => true,
				'redirection' => 3,
				'headers'     => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);
		update_option( 'nes_update_last_checked', current_time( 'mysql' ), false );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 > $code || 300 <= $code ) {
			return new \WP_Error( 'nes_update_http', 'GitHub Release 정보를 가져오지 못했습니다.', array( 'status' => $code ) );
		}
		$release = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
			return new \WP_Error( 'nes_update_json', 'GitHub Release 응답을 해석할 수 없습니다.' );
		}

		$version        = ltrim( sanitize_text_field( (string) $release['tag_name'] ), 'vV' );
		$package        = '';
		$expected_asset = 'nalapps-easy-smtp-' . $version . '.zip';
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'], $asset['browser_download_url'] ) && $expected_asset === $asset['name'] ) {
					$package = esc_url_raw( (string) $asset['browser_download_url'] );
					break;
				}
			}
		}

		$data = array(
			'new_version' => $version,
			'package'     => $package,
			'url'         => ! empty( $release['html_url'] ) ? esc_url_raw( (string) $release['html_url'] ) : NES_STORE_URL,
		);
		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	private function get_package_url( $info ) {
		return is_array( $info ) && ! empty( $info['package'] ) ? esc_url_raw( (string) $info['package'] ) : '';
	}

	private function has_newer_version( $info ) {
		return is_array( $info ) && ! empty( $info['new_version'] ) && version_compare( (string) $info['new_version'], NES_VERSION, '>' );
	}

	private function build_update_object( $info, $plugin ) {
		return (object) array(
			'id'          => 'nalapps-easy-smtp',
			'slug'        => dirname( $plugin ),
			'plugin'      => $plugin,
			'new_version' => sanitize_text_field( (string) $info['new_version'] ),
			'url'         => ! empty( $info['url'] ) ? esc_url_raw( (string) $info['url'] ) : NES_STORE_URL,
			'package'     => $this->get_package_url( $info ),
		);
	}
}
