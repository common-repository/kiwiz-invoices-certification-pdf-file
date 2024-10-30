<?php
/**
 * Kiwiz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at the following URI:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the PHP License and are unable to
 * obtain it through the web, please send a note to contact@kiwiz.io
 * so we can mail you a copy immediately.
 *
 * @author Kiwiz <contact@kiwiz.io>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Kiwiz Uninstall
 *
 * Uninstalling Kiwiz extension options, cron jobs.
 *
 * @package WooCommerce\Uninstaller
 * @version 2.3.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

//delete cron job
wp_clear_scheduled_hook('kiwiz_cron_five_minutes_action');

//delete options
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_activation_date';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_api_token';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_update_increment_id';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = '_kiwiz_invoice_cron_list';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = '_kiwiz_refund_cron_list';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_invoice_list_settings';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_refund_list_settings';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'woocommerce_kiwiz_account_settings';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_document_invoice_increment_id';" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'kiwiz_document_refund_increment_id';" );

//delete meta post datas
$wpdb->query( "DELETE FROM $wpdb->postmeta where meta_key like '_kiwiz_%';" );

//delete kiwiz directories
function removeDirectory($path) {
    $files = glob($path . '/*');
    foreach ($files as $file) {
        is_dir($file) ? removeDirectory($file) : unlink($file);
    }
    rmdir($path);
    return;
}

$wp_upload_dir = wp_upload_dir();
$directories = array (  $wp_upload_dir['basedir'] . '/kiwiz-document/',
                        $wp_upload_dir['basedir'] . '/kiwiz-images/' );

foreach ( $directories as $d ) {
    removeDirectory($d);
}

// Clear any cached data that has been removed.
wp_cache_flush();