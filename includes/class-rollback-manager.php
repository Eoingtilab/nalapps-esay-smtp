<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Creates code/data backups before updates and supports controlled rollback. */
class Rollback_Manager {
	const MAX_BACKUPS             = 3;
	const RELEASES_API            = 'https://api.github.com/repos/Eoingtilab/nalapps-esay-smtp/releases?per_page=30';
	const ACTIVATION_STATE_OPTION = 'nes_update_activation_state';

	public function __construct() {
		add_filter( 'upgrader_pre_install', array( $this, 'backup_before_update' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'restore_activation_after_update' ), 20, 2 );
		add_action( 'admin_post_nes_rollback', array( $this, 'rollback' ) );
		add_action( 'admin_post_nes_release_rollback', array( $this, 'rollback_release' ) );
	}

	public static function backup_directory() {
		$uploads = wp_upload_dir();
		$token   = substr( hash_hmac( 'sha256', 'nalapps-easy-smtp|' . home_url(), wp_salt( 'auth' ) ), 0, 32 );
		return trailingslashit( $uploads['basedir'] ) . '.nalapps-backups-' . $token . '/nalapps-easy-smtp/code';
	}

	public function backup_before_update( $response, $hook_extra ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$plugin = isset( $hook_extra['plugin'] ) ? (string) $hook_extra['plugin'] : '';
		if ( plugin_basename( NES_FILE ) !== $plugin ) {
			return $response;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		update_option(
			self::ACTIVATION_STATE_OPTION,
			array(
				'plugin'  => $plugin,
				'active'  => is_plugin_active( $plugin ),
				'network' => is_multisite() && is_plugin_active_for_network( $plugin ),
			),
			false
		);

		$backup = self::create_code_backup( 'pre-update' );
		if ( is_wp_error( $backup ) ) {
			return $backup;
		}
		$snapshot = Data_Portability::create_snapshot( 'pre-update' );
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}
		return $response;
	}

	public function restore_activation_after_update( $upgrader, $hook_extra ) {
		unset( $upgrader );
		if ( empty( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] || empty( $hook_extra['action'] ) || 'update' !== $hook_extra['action'] ) {
			return;
		}

		$plugin  = plugin_basename( NES_FILE );
		$plugins = array();
		if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
			$plugins = array_map( 'strval', $hook_extra['plugins'] );
		} elseif ( ! empty( $hook_extra['plugin'] ) ) {
			$plugins = array( (string) $hook_extra['plugin'] );
		}
		if ( ! in_array( $plugin, $plugins, true ) ) {
			return;
		}

		$state = get_option( self::ACTIVATION_STATE_OPTION, array() );
		delete_option( self::ACTIVATION_STATE_OPTION );
		if ( ! is_array( $state ) || empty( $state['active'] ) || empty( $state['plugin'] ) || $plugin !== $state['plugin'] ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( ! is_plugin_active( $plugin ) ) {
			activate_plugin( $plugin, '', ! empty( $state['network'] ), true );
		}
	}

	public static function create_code_backup( $reason = 'manual' ) {
		global $wp_filesystem;

		$dir = self::backup_directory();
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'nes_rollback_dir', '롤백 백업 디렉터리를 만들 수 없습니다.' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		WP_Filesystem();
		$filename = sanitize_file_name( gmdate( 'Ymd-His' ) . '-' . NES_VERSION . '-' . sanitize_key( $reason ) . '.zip' );
		$path     = trailingslashit( $dir ) . $filename;
		$archive  = new \PclZip( $path );
		$source   = untrailingslashit( NES_PATH );
		$created  = $archive->create( $source, PCLZIP_OPT_REMOVE_PATH, dirname( $source ) );
		if ( 0 === $created ) {
			return new \WP_Error( 'nes_rollback_zip', '롤백 패키지를 만들 수 없습니다.' );
		}
		self::protect_directory();
		self::prune_backups();
		return $filename;
	}

	public static function list_backups() {
		$dir = self::backup_directory();
		if ( ! is_dir( $dir ) ) {
			return array();
		}
		$files = glob( trailingslashit( $dir ) . '*.zip' );
		if ( ! is_array( $files ) ) {
			return array();
		}
		$names = array_map( 'basename', $files );
		rsort( $names, SORT_STRING );
		return $names;
	}

	public static function list_release_versions() {
		$cached = get_transient( 'nes_release_rollback_versions' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$response = wp_remote_get(
			self::RELEASES_API,
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}
		$releases = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $releases ) ) {
			return array();
		}
		$versions = array();
		foreach ( $releases as $release ) {
			if ( ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) || empty( $release['tag_name'] ) || empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
				continue;
			}
			$version = ltrim( sanitize_text_field( (string) $release['tag_name'] ), 'vV' );
			if ( ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) || ! version_compare( $version, NES_VERSION, '<' ) ) {
				continue;
			}
			$expected = 'nalapps-easy-smtp-' . $version . '.zip';
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'], $asset['browser_download_url'] ) && $expected === $asset['name'] ) {
					$versions[ $version ] = esc_url_raw( (string) $asset['browser_download_url'] );
					break;
				}
			}
		}
		uksort(
			$versions,
			static function ( $a, $b ) {
				return version_compare( $b, $a );
			}
		);
		set_transient( 'nes_release_rollback_versions', $versions, HOUR_IN_SECONDS );
		return $versions;
	}

	public function rollback() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_rollback' );
		$requested = isset( $_POST['backup'] ) ? sanitize_file_name( wp_unslash( $_POST['backup'] ) ) : '';
		if ( ! in_array( $requested, self::list_backups(), true ) ) {
			$this->redirect( 'rollback_error' );
		}
		$this->perform_rollback( trailingslashit( self::backup_directory() ) . $requested );
	}

	public function rollback_release() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( '권한이 없습니다.' );
		}
		check_admin_referer( 'nes_release_rollback' );
		$requested = isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : '';
		$versions  = self::list_release_versions();
		if ( ! isset( $versions[ $requested ] ) || ! version_compare( $requested, NES_VERSION, '<' ) ) {
			$this->redirect( 'rollback_error' );
		}
		$this->perform_rollback( $versions[ $requested ] );
	}

	private function perform_rollback( $package ) {
		$current  = self::create_code_backup( 'pre-rollback' );
		$snapshot = Data_Portability::create_snapshot( 'pre-rollback' );
		if ( is_wp_error( $current ) || is_wp_error( $snapshot ) ) {
			$this->redirect( 'rollback_error' );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugin     = plugin_basename( NES_FILE );
		$was_active = is_plugin_active( $plugin );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $package, array( 'overwrite_package' => true ) );
		delete_site_transient( 'update_plugins' );
		delete_transient( 'nes_release_rollback_versions' );
		if ( is_wp_error( $result ) || false === $result ) {
			$this->redirect( 'rollback_error' );
		}
		if ( $was_active && ! is_plugin_active( $plugin ) ) {
			activate_plugin( $plugin, '', false, true );
		}
		$this->redirect( 'rolled_back' );
	}

	private static function protect_directory() {
		global $wp_filesystem;
		$dir = self::backup_directory();
		if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
			$wp_filesystem->put_contents( trailingslashit( $dir ) . 'index.php', "<?php\n// Silence is golden.\n" );
			$wp_filesystem->put_contents( trailingslashit( $dir ) . '.htaccess', "Deny from all\n" );
		}
	}

	private static function prune_backups() {
		foreach ( array_slice( self::list_backups(), self::MAX_BACKUPS ) as $name ) {
			wp_delete_file( trailingslashit( self::backup_directory() ) . $name );
		}
	}

	private function redirect( $state ) {
		wp_safe_redirect( admin_url( 'admin.php?page=nes-maintenance&state=' . sanitize_key( $state ) ) );
		exit;
	}
}
