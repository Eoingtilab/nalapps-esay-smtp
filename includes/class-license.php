<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NalApps Easy SMTP is a free product: no serial key or activation is
 * required. This class only renders the standard License screen so the
 * navigation stays consistent with other NalApps products, and reports a
 * permanently active/free status to anything that checks it.
 */
class License {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 35 );
	}

	public function register_page() {
		add_submenu_page( 'nes-easy-smtp-dashboard', '라이선스', '라이선스', 'manage_options', 'nes-license', array( $this, 'render_page' ) );
	}

	public function is_valid() {
		return true;
	}

	public function get_status() {
		return 'free';
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		?>
		<div class="wrap nalapps-shell nes-license-page nalapps-has-global-header">
			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>제품 라이선스</h2>
						<p>NalApps Easy SMTP는 무료 플러그인입니다. 시리얼키 등록이나 사이트 활성화 절차 없이 모든 기능과 업데이트를 바로 사용할 수 있습니다.</p>
					</div>
				</div>

				<div class="nalapps-notice is-success">
					<strong>현재 상태:</strong> 활성 (무료)
				</div>

				<p class="nalapps-help">업데이트는 GitHub Release를 기준으로 자동 확인되며, 라이선스 키가 필요하지 않습니다.</p>
			</section>
		</div>
		<?php
	}
}
