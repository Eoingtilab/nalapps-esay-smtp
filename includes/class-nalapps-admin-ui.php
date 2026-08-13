<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical NalApps Admin UI adapter.
 *
 * This class owns the single product navigation/header shell. Product screens
 * render content only; they must not render a second page title/header.
 */
class NalApps_Admin_UI {
	private $header_rendered = false;

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'in_admin_header', array( $this, 'render_admin_header' ), 20 );
	}

	public function enqueue_assets() {
		if ( ! $this->is_target_screen() ) {
			return;
		}

		wp_enqueue_style( 'nes-nalapps-admin-ui', NES_URL . 'assets/nalapps-admin-ui.css', array(), NES_VERSION );
		wp_enqueue_style( 'nes-nalapps-admin-typography', NES_URL . 'assets/nalapps-admin-typography.css', array( 'nes-nalapps-admin-ui' ), NES_VERSION );

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $page, array( 'nes-license', 'nes-update', 'nes-maintenance' ), true ) ) {
			wp_enqueue_style( 'nes-maintenance', NES_URL . 'assets/maintenance.css', array( 'nes-nalapps-admin-ui' ), NES_VERSION );
		}
	}

	public function admin_body_class( $classes ) {
		if ( $this->is_target_screen() ) {
			$classes .= ' nalapps-admin-screen nes-admin-screen';
		}
		return $classes;
	}

	public function render_admin_header() {
		if ( $this->header_rendered || ! $this->is_target_screen() ) {
			return;
		}

		$this->header_rendered = true;
		$page                  = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active                = 'dashboard';
		$title                 = 'SMTP 설정';
		$description           = 'SMTP 서버 연결 정보를 등록하고 테스트 메일을 보내 발송 상태를 확인합니다.';
		$action_url            = admin_url( 'admin.php?page=nes-update' );
		$action_text           = '업데이트';

		if ( 'nes-license' === $page ) {
			$active      = 'license';
			$title       = '라이선스';
			$description = '구매한 시리얼키를 등록·활성화하고 현재 라이선스 상태를 확인합니다.';
			$action_url  = admin_url( 'admin.php?page=nes-update' );
			$action_text = '업데이트';
		} elseif ( 'nes-update' === $page ) {
			$active      = 'update';
			$title       = '업데이트';
			$description = '최신 버전을 확인하고 검증된 배포 패키지로 업데이트하거나 이전 안정 버전으로 롤백합니다.';
			$action_url  = admin_url( 'admin.php?page=nes-maintenance' );
			$action_text = '백업 및 복구';
		} elseif ( 'nes-maintenance' === $page ) {
			$active      = 'maintenance';
			$title       = '백업 및 복구';
			$description = '설정 내보내기·가져오기, 업데이트 전 백업, 버전 롤백과 제거 정책을 관리합니다.';
			$action_url  = admin_url( 'admin.php?page=nes-update' );
			$action_text = '업데이트';
		} elseif ( 'nes-system-status' === $page ) {
			$active      = 'status';
			$title       = '시스템 정보';
			$description = '버전, 라이선스, 백업 및 WordPress 환경을 비밀정보 노출 없이 확인합니다.';
			$action_url  = admin_url( 'admin.php?page=nes-update' );
			$action_text = '업데이트 확인';
		}
		?>
		<div class="nalapps-shell nes-admin-shell">
			<div class="nalapps-global-nav">
				<div class="nalapps-brand">
					<span class="nalapps-brand__mark"><span class="dashicons dashicons-email-alt"></span></span>
					<span class="nalapps-brand__text"><strong>NalApps Easy SMTP</strong><small>NalApps WordPress Plugin Standard v<?php echo esc_html( NES_STANDARD_VERSION ); ?></small></span>
				</div>
				<nav class="nalapps-nav" aria-label="간편 SMTP 관리 메뉴">
					<a class="<?php echo 'dashboard' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=nes-easy-smtp-dashboard' ) ); ?>">SMTP 설정</a>
					<a class="<?php echo 'license' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=nes-license' ) ); ?>">라이선스</a>
					<a class="<?php echo 'update' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=nes-update' ) ); ?>">업데이트</a>
					<a class="<?php echo 'maintenance' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=nes-maintenance' ) ); ?>">백업 및 복구</a>
					<a class="<?php echo 'status' === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=nes-system-status' ) ); ?>">시스템 정보</a>
				</nav>
			</div>
			<div class="nalapps-page-header">
				<div class="nalapps-page-header__copy">
					<span class="nalapps-page-kicker">EOINGTI LAB · NALAPPS</span>
					<h1><?php echo esc_html( $title ); ?></h1>
					<p><?php echo esc_html( $description ); ?></p>
				</div>
				<div class="nalapps-page-actions"><a class="button" href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_html( $action_text ); ?></a></div>
			</div>
		</div>
		<?php
	}

	private function is_target_screen() {
		$screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$page_ids = array( 'nes-easy-smtp-dashboard', 'nes-license', 'nes-update', 'nes-maintenance', 'nes-system-status' );

		if ( $screen && isset( $screen->id ) ) {
			foreach ( $page_ids as $page_id ) {
				if ( false !== strpos( $screen->id, $page_id ) ) {
					return true;
				}
			}
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $page, $page_ids, true );
	}
}
