<?php
/**
 * NalApps Easy SMTP uninstall policy.
 *
 * Settings are preserved unless the site owner explicitly enables
 * delete-all on the Backup & Restore screen.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( 'delete_all' !== get_option( 'nes_uninstall_policy', 'preserve' ) ) {
	return;
}

delete_option( 'nalapps_easy_smtp_settings' );
delete_option( 'nes_uninstall_policy' );
delete_option( 'nes_update_last_checked' );
delete_option( 'nes_update_activation_state' );
delete_option( 'nalapps-easy-smtp_license_key' );
delete_option( 'nalapps-easy-smtp_license' );

do_action( 'nes_delete_all_data' );
