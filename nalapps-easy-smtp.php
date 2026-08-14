<?php
/**
 * Plugin Name: NalApps Easy SMTP
 * Plugin URI: https://github.com/Eoingtilab/nalapps-esay-smtp
 * Description: WordPress 메일 발송을 위한 안전하고 간단한 SMTP/API 설정, 진단, 시험 발송 도구입니다. 무료로 제공되며 라이선스 등록이 필요하지 않습니다.
 * Version: 1.1.0
 * Author: Eoingti Lab Inc.
 * Author URI: https://eoingti.com/
 * Text Domain: nalapps-easy-smtp
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Update URI: https://github.com/Eoingtilab/nalapps-esay-smtp
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NES_VERSION', '1.1.0' );
define( 'NES_STANDARD_VERSION', '4.6.0' );
define( 'NES_FILE', __FILE__ );
define( 'NES_PATH', plugin_dir_path( __FILE__ ) );
define( 'NES_URL', plugin_dir_url( __FILE__ ) );
define( 'NES_STORE_URL', 'https://github.com/Eoingtilab/nalapps-esay-smtp' );

require_once NES_PATH . 'includes/class-crypto.php';
require_once NES_PATH . 'includes/class-license.php';
require_once NES_PATH . 'includes/class-mail-api.php';
require_once NES_PATH . 'includes/class-data-portability.php';
require_once NES_PATH . 'includes/class-rollback-manager.php';
require_once NES_PATH . 'includes/class-maintenance.php';
require_once NES_PATH . 'includes/class-system-status.php';
require_once NES_PATH . 'includes/class-update-manager.php';
require_once NES_PATH . 'includes/class-nalapps-admin-ui.php';
require_once NES_PATH . 'includes/class-plugin.php';

new NES\License();
new NES\Mail_Api();
new NES\Data_Portability();
new NES\Rollback_Manager();
new NES\Maintenance();
new NES\System_Status();
new NES\Update_Manager();
new NES\NalApps_Admin_UI();
NES\Plugin::instance();
