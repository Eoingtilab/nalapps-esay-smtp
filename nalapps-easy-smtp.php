<?php
/**
 * Plugin Name: NalApps Easy SMTP
 * Plugin URI: https://eoingti.com/
 * Description: WordPress 메일 발송을 위한 안전하고 간단한 SMTP 설정, 진단, 시험 발송 도구입니다.
 * Version: 1.0.0
 * Author: Eoingti Lab Inc.
 * Author URI: https://eoingti.com/
 * Text Domain: nalapps-easy-smtp
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Update URI: https://app.nal.la/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NES_VERSION', '1.0.0' );
define( 'NES_STANDARD_VERSION', '4.6.0' );
define( 'NES_FILE', __FILE__ );
define( 'NES_PATH', plugin_dir_path( __FILE__ ) );
define( 'NES_URL', plugin_dir_url( __FILE__ ) );
define( 'NES_STORE_URL', 'https://app.nal.la' );
define( 'NES_EDD_ITEM_ID', 533 );

add_action(
	'edd_sl_sdk_registry',
	function ( $registry ) {
		$registry->register(
			array(
				'id'      => 'nalapps-easy-smtp',
				'url'     => NES_STORE_URL,
				'item_id' => NES_EDD_ITEM_ID,
				'version' => NES_VERSION,
				'file'    => NES_FILE,
			)
		);
	}
);

$nes_edd_sdk = NES_PATH . 'vendor/easy-digital-downloads/edd-sl-sdk/edd-sl-sdk.php';
if ( file_exists( $nes_edd_sdk ) ) {
	require_once $nes_edd_sdk;
}

require_once NES_PATH . 'includes/class-crypto.php';
require_once NES_PATH . 'includes/class-license.php';
require_once NES_PATH . 'includes/class-data-portability.php';
require_once NES_PATH . 'includes/class-rollback-manager.php';
require_once NES_PATH . 'includes/class-maintenance.php';
require_once NES_PATH . 'includes/class-system-status.php';
require_once NES_PATH . 'includes/class-update-manager.php';
require_once NES_PATH . 'includes/class-nalapps-admin-ui.php';
require_once NES_PATH . 'includes/class-plugin.php';

new NES\License();
new NES\Data_Portability();
new NES\Rollback_Manager();
new NES\Maintenance();
new NES\System_Status();
new NES\Update_Manager();
new NES\NalApps_Admin_UI();
NES\Plugin::instance();
