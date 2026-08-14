<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export/import and local snapshot support for SMTP settings.
 *
 * The encrypted SMTP password is intentionally excluded from every export
 * and snapshot. It is encrypted with a key derived from this site's
 * wp_salt( 'auth' ), so it is not portable to another install and keeping
 * it out of backup files avoids storing secret material outside the
 * WordPress options table.
 */
class Data_Portability {
	const FORMAT        = 'nalapps-data-backup-v1';
	const MAX_FILE_SIZE = 5242880;
	const MAX_SNAPSHOTS = 5;

	public function __construct() {
		add_action( 'admin_post_nes_export_data', array( $this, 'export_data' ) );
		add_action( 'admin_post_nes_import_data', array( $this, 'import_data' ) );
	}

	public static function snapshot_directory() {
		$uploads = wp_upload_dir();
		$token   = substr( hash_hmac( 'sha256', 'nalapps-easy-smtp|' . home_url(), wp_salt( 'auth' ) ), 0, 32 );
		return trailingslashit( $uploads['basedir'] ) . '.nalapps-backups-' . $token . '/nalapps-easy-smtp/data';
	}

	public static function build_payload() {
		$settings = Smtp::settings();
		unset( $settings['password_enc'], $settings['api_key_enc'] );

		return array(
			'format'           => self::FORMAT,
			'plugin_slug'      => 'nalapps-easy-smtp',
			'plugin_version'   => NES_VERSION,
			'standard_version' => NES_STANDARD_VERSION,
			'created_at'       => gmdate( 'c' ),
			'data'             => array( 'settings' => $settings ),
			'notes'            => 'SMTP 비밀번호와 API 키는 이 사이트에서만 해독 가능하게 암호화되어 있어 백업 파일에 포함되지 않습니다.',
		);
	}

	public static function create_snapshot( $reason = 'manual' ) {
		global $wp_filesystem;

		$dir = self::snapshot_directory();
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'nes_snapshot_dir', '백업 디렉터리를 만들 수 없습니다.' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		self::protect_directory( $dir );
		$filename = sanitize_file_name( gmdate( 'Ymd-His' ) . '-' . sanitize_key( $reason ) . '.json' );
		$written  = false;
		if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
			$written = $wp_filesystem->put_contents( trailingslashit( $dir ) . $filename, wp_json_encode( self::build_payload(), JSON_PRETTY_PRINT ) );
		}
		if ( ! $written ) {
			return new \WP_Error( 'nes_snapshot_write', '데이터 스냅샷을 저장할 수 없습니다.' );
		}
		self::prune_snapshots();
		return $filename;
	}

	public static function list_snapshots() {
		$dir = self::snapshot_directory();
		if ( ! is_dir( $dir ) ) {
			return array();
		}
		$files = glob( trailingslashit( $dir ) . '*.json' );
		if ( ! is_array( $files ) ) {
			return array();
		}
		$names = array_map( 'basename', $files );
		rsort( $names, SORT_STRING );
		return $names;
	}

	public function export_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_export_data' );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="nalapps-easy-smtp-backup-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( self::build_payload(), JSON_PRETTY_PRINT );
		exit;
	}

	public function import_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_import_data' );
		if ( empty( $_FILES['nes_backup']['tmp_name'] ) || ! isset( $_FILES['nes_backup']['size'], $_FILES['nes_backup']['name'] ) ) {
			$this->redirect( 'import_error' );
		}
		$tmp  = (string) $_FILES['nes_backup']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$size = (int) $_FILES['nes_backup']['size'];
		$name = sanitize_file_name( wp_unslash( (string) $_FILES['nes_backup']['name'] ) );
		if ( $size <= 0 || $size > self::MAX_FILE_SIZE || 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) || ! is_uploaded_file( $tmp ) ) {
			$this->redirect( 'import_error' );
		}

		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		$raw     = $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem->get_contents( $tmp ) : false;
		$payload = false !== $raw ? json_decode( $raw, true ) : null;
		if ( ! is_array( $payload ) || self::FORMAT !== ( $payload['format'] ?? '' ) || 'nalapps-easy-smtp' !== ( $payload['plugin_slug'] ?? '' ) || ! isset( $payload['data']['settings'] ) || ! is_array( $payload['data']['settings'] ) ) {
			$this->redirect( 'import_error' );
		}

		$snapshot = self::create_snapshot( 'pre-import' );
		if ( is_wp_error( $snapshot ) ) {
			$this->redirect( 'snapshot_error' );
		}

		$current  = Smtp::settings();
		$imported = $payload['data']['settings'];
		unset( $imported['password_enc'], $imported['api_key_enc'] );
		$imported['password_enc'] = $current['password_enc'];
		$imported['api_key_enc']  = $current['api_key_enc'];
		update_option( Smtp::OPTION_KEY, wp_parse_args( $imported, $current ), false );

		$this->redirect( 'imported' );
	}

	private static function protect_directory( $dir ) {
		global $wp_filesystem;
		if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
			$wp_filesystem->put_contents( trailingslashit( $dir ) . 'index.php', "<?php\n// Silence is golden.\n" );
			$wp_filesystem->put_contents( trailingslashit( $dir ) . '.htaccess', "Deny from all\n" );
		}
	}

	private static function prune_snapshots() {
		foreach ( array_slice( self::list_snapshots(), self::MAX_SNAPSHOTS ) as $name ) {
			wp_delete_file( trailingslashit( self::snapshot_directory() ) . $name );
		}
	}

	private function redirect( $state ) {
		wp_safe_redirect( admin_url( 'admin.php?page=nes-maintenance&state=' . sanitize_key( $state ) ) );
		exit;
	}
}
