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
 * Plugin Name: KIWIZ Invoices Certification & PDF file
 * Plugin URI: https://woocommerce.com/products/pdf-certification-documentation-kiwiz/
 * Description: Système de certification en temps réel dans la Blockchain pour se conformer à la loi anti-fraude TVA 2018.
 * Version: 2.1.9
 * Author: KIWIZ
 * Author URI: https://www.kiwiz.io/
 * Text Domain: kiwiz-invoices-certification-pdf-file
 * Domain Path: /languages/
 * Woo: 3895000:5d6f5252c4f6b9d36bfa371f3c85029d
 * WC requires at least: 3.0.0
 * WC tested up to: 8.8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define KIWIZ_PLUGIN_FILE
if ( ! defined( 'KIWIZ_PLUGIN_FILE' ) ) {
    define( 'KIWIZ_PLUGIN_FILE', __FILE__ );
}

// Define KIWIZ_PLUGIN_PATH
if ( ! defined( 'KIWIZ_PLUGIN_PATH' ) ) {
    define( 'KIWIZ_PLUGIN_PATH', dirname( KIWIZ_PLUGIN_FILE ) . '/' );
}

// Define KIWIZ_PLUGIN_URL
if ( ! defined( 'KIWIZ_PLUGIN_URL' ) ) {
    define( 'KIWIZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Define KIWIZ_DOCUMENT_DIR
$wp_upload_dir = wp_upload_dir();
if ( ! defined( 'KIWIZ_DOCUMENT_DIR' ) ) {
    define( 'KIWIZ_DOCUMENT_DIR', $wp_upload_dir['basedir'] . '/kiwiz-document/' );
}

// Define KIWIZ_DOCUMENT_IMAGES_DIR
if ( ! defined( 'KIWIZ_DOCUMENT_IMAGES_DIR' ) ) {
    define( 'KIWIZ_DOCUMENT_IMAGES_DIR', $wp_upload_dir['basedir'] . '/kiwiz-images/' );
}

//Define KIWIZ_DOCUMENT_URL
if ( ! defined( 'KIWIZ_DOCUMENT_URL' ) ) {
    define( 'KIWIZ_DOCUMENT_URL', $wp_upload_dir['baseurl'] . '/kiwiz-document/' );
}

//Define KIWIZ_DOCUMENT_IMAGES_URL
if ( ! defined( 'KIWIZ_DOCUMENT_IMAGES_URL' ) ) {
    define( 'KIWIZ_DOCUMENT_IMAGES_URL', $wp_upload_dir['baseurl'] . '/kiwiz-images/' );
}

require_once( KIWIZ_PLUGIN_PATH . '/includes/class-kiwiz.php' );



class KIWIZ_Wocommerce_PDF_certificate {

    private $_kiwiz;

    function __construct() {

        $this->_kiwiz = new Kiwiz();

        register_activation_hook( __FILE__, array($this, 'activate') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );

        add_filter('cron_schedules', array($this,'scheduled_interval'));

        add_action( 'admin_notices', array( $this, 'kiwiz_admin_notices' ) ) ;

        add_action( 'plugins_loaded', array( $this, 'init' ) );

    }

    public function activate() {

        set_transient( 'kiwiz-notice-install', true, 10 );

        if ( !wp_next_scheduled('kiwiz_cron_five_minutes_action') ) {
            wp_schedule_event(time(), 'every_five_minutes', 'kiwiz_cron_five_minutes_action');
        }

        //if kiwiz ids exist, try to generate token and set activation date
        $certification_settings = get_option('woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings', array());
        if ( $certification_settings != '' ) {
            Kiwiz_API::get_new_token();
        }

    }
    public function deactivate() {
        update_option('kiwiz_activation_date', '');
        update_option('kiwiz_api_token', '');
        update_option('_kiwiz_invoice_cron_list', array());
        update_option('_kiwiz_refund_cron_list', array());
        wp_clear_scheduled_hook('kiwiz_cron_five_minutes_action');
    }

    public function scheduled_interval($schedules) {
        $schedules['every_five_minutes'] = array(
            'interval' => 300,
            'display'  => __( 'Every 5 Minutes', 'kiwiz-invoices-certification-pdf-file' )
        );
        return $schedules;
    }

    public function kiwiz_admin_notices () {

       if ( get_transient( 'kiwiz-notice-install' ) ) {
           if ( Kiwiz::is_woocommerce_active() ) {
               $html = '<div class="kiwiz-notice updated notice is-dismissible"><p>';
               $html .= __( 'The extension <strong>KIWIZ Invoices Certification & PDF System</strong> is activated.', 'kiwiz-invoices-certification-pdf-file' );
               $html .= '</p>';
               $html .= '<p class="submit">';

               $account_button = '<a href="%s" class="button-primary">'.__( 'Configure KIWIZ Account', 'kiwiz-invoices-certification-pdf-file' ).'</a>';
               $account_button = sprintf( $account_button, admin_url( 'admin.php?page=wc-settings&tab=integration&section=kiwiz_account' ) );

               $html .= $account_button;

               $html .= '</p></div>';
               echo wp_kses_post($html);
           }
           delete_transient( 'kiwiz-notice-install' );
       }
    }

    /**
     * Initialize the plugin.
     */
    public function init() {

        if ( class_exists( 'WC_Integration' ) ) {

            //Account settings
            include_once KIWIZ_PLUGIN_PATH . 'includes/admin/integration/class.kiwiz-integration-account-settings.php';
            add_filter( 'woocommerce_integrations', array(  $this, 'add_kiwiz_integration' ) );
        }
    }

    /**
     * Add a new integration to WooCommerce.
     */
    public function add_kiwiz_integration( $integrations ) {
        $integrations[] = 'Kiwiz_Integration_Account_Settings';
        return $integrations;
    }

}

new KIWIZ_Wocommerce_PDF_certificate();
