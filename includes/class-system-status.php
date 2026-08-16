<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Read-only redacted diagnostics. */
class System_Status {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 45 );
		add_filter( 'plugin_action_links_' . plugin_basename( NES_FILE ), array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
		add_filter( 'debug_information', array( $this, 'site_health_info' ) );
	}

	public function register_page() {
		add_submenu_page( 'nes-easy-smtp-dashboard', '시스템 정보', '시스템 정보', 'manage_options', 'nes-system-status', array( $this, 'render_page' ) );
	}

	public function action_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=nes-update' ) ) . '">업데이트</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=nes-maintenance' ) ) . '">백업/복구</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=nes-system-status' ) ) . '">시스템 정보</a>';
		return $links;
	}

	public function row_meta( $links, $file ) {
		if ( plugin_basename( NES_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( 'https://eoingti.com/' ) . '" target="_blank" rel="noopener noreferrer">개발자 사이트</a>';
		$links[] = '<a href="' . esc_url( 'https://github.com/Eoingtilab/nalapps-esay-smtp' ) . '" target="_blank" rel="noopener noreferrer">GitHub</a>';
		return $links;
	}

	public static function values() {
		$settings = Smtp::settings();
		$provider = array_key_exists( $settings['provider'], Smtp::PROVIDERS ) ? Smtp::PROVIDERS[ $settings['provider'] ]['label'] : $settings['provider'];
		return array(
			'플러그인 버전'          => NES_VERSION,
			'NalApps Standard' => NES_STANDARD_VERSION,
			'WordPress'        => get_bloginfo( 'version' ),
			'PHP'              => PHP_VERSION,
			'Locale'           => get_locale(),
			'HTTPS'            => is_ssl() ? '예' : '아니오',
			'Multisite'        => is_multisite() ? '예' : '아니오',
			'메일 발송 사용'         => ! empty( $settings['enabled'] ) ? '켬' : '끔',
			'연결 방식'            => 'api' === $settings['connection_mode'] ? 'API 키' : 'SMTP',
			'선택된 서비스'          => $provider,
			'SMTP 암호화'         => strtoupper( (string) $settings['encryption'] ),
			'라이선스 상태'          => '무료 (활성)',
			'마지막 업데이트 확인'      => (string) get_option( 'nes_update_last_checked', '확인 기록 없음' ),
			'롤백 코드 백업'         => (string) count( Rollback_Manager::list_backups() ),
			'설정 스냅샷'           => (string) count( Data_Portability::list_snapshots() ),
			'제거 시 데이터 삭제'      => 'delete_all' === get_option( Maintenance::POLICY_OPTION, 'preserve' ) ? 'ON' : 'OFF',
			'업데이트 소스'          => NES_STORE_URL,
			'Telemetry'        => 'OFF',
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '권한이 없습니다.', 'nalapps-easy-smtp' ) );
		}
		?>
		<div class="wrap nalapps-shell nes-system-status-page nalapps-has-global-header">
			<section class="nalapps-panel">
				<div class="nalapps-panel-heading">
					<div>
						<h2>시스템 정보</h2>
						<p>고객지원과 업데이트 진단을 위한 읽기 전용 정보입니다. 라이선스 키, 비밀번호, API 키 등 비밀정보는 표시하지 않습니다.</p>
					</div>
				</div>
				<table class="widefat striped" role="table"><tbody>
				<?php foreach ( self::values() as $label => $value ) : ?>
					<tr><th scope="row"><?php echo esc_html( $label ); ?></th><td><?php echo esc_html( $value ); ?></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			</section>
		</div>
		<?php
	}

	public function site_health_info( $debug_info ) {
		$fields = array();
		foreach ( self::values() as $label => $value ) {
			$fields[ sanitize_key( $label ) ] = array(
				'label' => $label,
				'value' => (string) $value,
			);
		}
		$debug_info['nalapps_easy_smtp'] = array(
			'label'  => 'NalApps Easy SMTP',
			'fields' => $fields,
		);
		return $debug_info;
	}
}
