<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Product-native maintenance screen using the canonical NalApps admin UI. */
class Maintenance {
	const POLICY_OPTION = 'nes_uninstall_policy';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 43 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_nes_save_uninstall_policy', array( $this, 'save_uninstall_policy' ) );
	}

	public function register_page() {
		add_submenu_page( 'nes-easy-smtp-dashboard', '백업 및 복구', '백업 및 복구', 'manage_options', 'nes-maintenance', array( $this, 'render_page' ) );
	}

	public function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'nes-maintenance' !== $page ) {
			return;
		}
		wp_enqueue_style( 'nes-nalapps-admin-ui', NES_URL . 'assets/nalapps-admin-ui.css', array(), NES_VERSION );
		wp_enqueue_style( 'nes-maintenance', NES_URL . 'assets/maintenance.css', array( 'nes-nalapps-admin-ui' ), NES_VERSION );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}

		$delete_all       = 'delete_all' === get_option( self::POLICY_OPTION, 'preserve' );
		$backups          = Rollback_Manager::list_backups();
		$snapshots        = Data_Portability::list_snapshots();
		$release_versions = Rollback_Manager::list_release_versions();
		?>
		<div class="wrap nalapps-shell nes-maintenance-page nalapps-has-global-header">
			<section class="nalapps-panel nalapps-maintenance-row">
				<div class="nalapps-panel-heading">
					<div>
						<h2>설정 백업 및 복원</h2>
						<p>SMTP 연결 설정을 JSON으로 안전하게 내보내거나 다시 가져옵니다. 라이선스 키와 SMTP 비밀번호는 백업 파일에 포함하지 않습니다.</p>
					</div>
				</div>
				<div class="nalapps-inline-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="nes_export_data">
						<?php wp_nonce_field( 'nes_export_data' ); ?>
						<button type="submit" class="button button-primary">설정 백업 파일 다운로드</button>
					</form>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="nes_import_data">
						<?php wp_nonce_field( 'nes_import_data' ); ?>
						<input type="file" name="nes_backup" accept="application/json,.json" required>
						<button type="submit" class="button">백업 파일 복원</button>
					</form>
				</div>
				<p class="nalapps-help">복원 직전 현재 설정은 자동 스냅샷으로 보관합니다. 현재 보관 중인 데이터 스냅샷: <?php echo esc_html( (string) count( $snapshots ) ); ?>개</p>
			</section>

			<section class="nalapps-panel nalapps-maintenance-row">
				<div class="nalapps-panel-heading">
					<div>
						<h2>버전 롤백</h2>
						<p>검증된 GitHub Release 설치 패키지 중 현재 버전보다 이전 버전을 선택해 되돌립니다. 실행 직전 현재 코드와 설정은 자동 백업됩니다.</p>
					</div>
				</div>
				<?php if ( $release_versions ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nalapps-inline-actions">
						<input type="hidden" name="action" value="nes_release_rollback">
						<?php wp_nonce_field( 'nes_release_rollback' ); ?>
						<label for="nes-rollback-version" class="screen-reader-text">되돌릴 버전</label>
						<select id="nes-rollback-version" name="version" required>
							<option value="">버전 선택</option>
							<?php foreach ( array_keys( $release_versions ) as $version ) : ?>
								<option value="<?php echo esc_attr( $version ); ?>">v<?php echo esc_html( $version ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button" onclick="return confirm('선택한 이전 버전으로 롤백하시겠습니까? 현재 코드와 설정은 먼저 자동 백업됩니다.');">선택 버전으로 롤백</button>
					</form>
				<?php else : ?>
					<div class="nalapps-notice">현재 롤백 가능한 검증된 이전 Release Asset을 찾지 못했습니다.</div>
				<?php endif; ?>
			</section>

			<section class="nalapps-panel nalapps-maintenance-row">
				<div class="nalapps-panel-heading">
					<div>
						<h2>로컬 안전 백업</h2>
						<p>업데이트와 롤백 직전에 자동 생성된 코드 백업입니다. 외부 Release와 별개인 비상 복구 수단입니다.</p>
					</div>
				</div>
				<?php if ( ! $backups ) : ?>
					<div class="nalapps-notice">아직 로컬 코드 백업이 없습니다. 다음 업데이트 또는 롤백 직전에 자동 생성됩니다.</div>
				<?php else : ?>
					<div class="nalapps-stack">
						<?php foreach ( $backups as $backup ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nalapps-inline-actions nes-local-backup-row">
								<input type="hidden" name="action" value="nes_rollback">
								<input type="hidden" name="backup" value="<?php echo esc_attr( $backup ); ?>">
								<?php wp_nonce_field( 'nes_rollback' ); ?>
								<code><?php echo esc_html( $backup ); ?></code>
								<button type="submit" class="button" onclick="return confirm('이 로컬 백업으로 복구하시겠습니까? 현재 코드와 설정은 먼저 백업됩니다.');">로컬 백업 복구</button>
							</form>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<section class="nalapps-panel nalapps-danger-zone nalapps-maintenance-row">
				<div class="nalapps-panel-heading">
					<div>
						<h2>플러그인 제거 시 데이터</h2>
						<p>기본값은 설정 보존입니다. 아래 옵션을 켠 상태에서 WordPress에서 플러그인을 삭제할 때만 SMTP 설정과 라이선스 로컬 상태가 영구 삭제됩니다.</p>
					</div>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="nes_save_uninstall_policy">
					<?php wp_nonce_field( 'nes_save_uninstall_policy' ); ?>
					<div class="nalapps-toggle-row">
						<div class="nalapps-toggle-copy">
							<strong>제거 시 모든 데이터 삭제</strong>
							<span>SMTP 설정, 라이선스 로컬 상태를 삭제합니다. 되돌릴 수 없습니다.</span>
						</div>
						<label class="nalapps-switch"><input type="checkbox" name="delete_all" value="1" <?php checked( $delete_all ); ?>><span class="nalapps-switch__track"></span></label>
					</div>
					<div class="nalapps-inline-actions"><button type="submit" class="button">설정 저장</button></div>
				</form>
			</section>
		</div>
		<?php
	}

	public function save_uninstall_policy() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_save_uninstall_policy' );
		$policy = isset( $_POST['delete_all'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['delete_all'] ) ) ? 'delete_all' : 'preserve';
		update_option( self::POLICY_OPTION, $policy, false );
		wp_safe_redirect( admin_url( 'admin.php?page=nes-maintenance&state=policy_saved' ) );
		exit;
	}
}
