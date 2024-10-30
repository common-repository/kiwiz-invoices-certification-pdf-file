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

defined( 'ABSPATH' ) || exit;

/**
 * Class Kiwiz_Init
 */

class Kiwiz_Init {

    private $_slug_main_admin_menu = 'kiwiz-certification';

    function __construct() {
        add_action( 'kiwiz_cron_five_minutes_action', array($this,'cron_task'));
        add_action( 'admin_init',           array($this, 'admin_init'));
        add_action( 'admin_menu',           array($this, 'admin_menu'), 99);
        add_action( 'wp_enqueue_scripts',   array($this, 'load_frontend_script'));
        add_action( 'kiwiz_loaded',         array($this, 'init_options') );
        add_action( 'kiwiz_loaded',         array($this, 'update_increment_id') );
        self::load_languages();
    }

    /**
     * Add Style, Script and Languages
     */
    public function admin_init() {
        wp_enqueue_style( 'kiwiz-certification-style', KIWIZ_PLUGIN_URL . 'admin/assets/css/style.css?v=2.1.2' );
        if ( is_plugin_active('woocommerce/woocommerce.php') )
            wp_register_style( 'jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), WC_VERSION );

        wp_register_script( 'kiwiz-certification-script', KIWIZ_PLUGIN_URL . 'admin/assets/js/kiwiz-admin.js');
        wp_register_script( 'kiwiz-certification-document', KIWIZ_PLUGIN_URL . 'admin/assets/js/kiwiz-document.js' );

        wp_localize_script( 'kiwiz-certification-script', 'invoice_free_object', apply_filters( 'invoice_free_object_localize', array(
            'logo_message_1' => __( "The logo your uploading is ", 'kiwiz-invoices-certification-pdf-file' ),
            'logo_message_2' => __( ". Logo must be no bigger than 300 x 150 pixels", 'kiwiz-invoices-certification-pdf-file' ),
            'logo_message_3' => __( "The image must be in the format: jpg, png, gif", 'kiwiz-invoices-certification-pdf-file' ),
            'value_btn_show' => __( "Edit image", 'kiwiz-invoices-certification-pdf-file' ),
            'value_btn_hide' => __( "Add image", 'kiwiz-invoices-certification-pdf-file' ),
            'error_title'    => __( "Error", 'kiwiz-invoices-certification-pdf-file' ),
        ) ) );
        wp_localize_script( 'kiwiz-certification-document', 'kiwiz_ajax', apply_filters( 'kiwiz_ajax_localize', array('adminAjax' => admin_url( 'admin-ajax.php' ) ) ) );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_style( 'jquery-ui-style' );
        wp_enqueue_script( 'kiwiz-frontend-script' );
        wp_enqueue_script( 'kiwiz-certification-script' );
        wp_enqueue_script( 'kiwiz-certification-document' );
    }

    public function load_frontend_script() {
        wp_register_script( 'kiwiz-frontend-script', KIWIZ_PLUGIN_URL . 'frontend/assets/js/kiwiz-document.js', array( 'jquery' ));
        wp_localize_script( 'kiwiz-frontend-script', 'kiwiz_ajax_front', apply_filters( 'kiwiz_ajax_localize', array('frontAjax' => admin_url( 'admin-ajax.php' ) ) ) );
        wp_enqueue_style( 'kiwiz-frontend-style', KIWIZ_PLUGIN_URL . 'frontend/assets/css/style.css' );
        wp_enqueue_script( 'kiwiz-frontend-script' );
    }

    /**
     * Load language textdomain
     */
    public function load_languages() {
        $domain = 'kiwiz-invoices-certification-pdf-file';

	    $mo_file = KIWIZ_PLUGIN_PATH . 'languages/'. $domain . '-'  . get_locale() . '.mo';
        load_textdomain( $domain, $mo_file );
        load_plugin_textdomain( $domain, false, KIWIZ_PLUGIN_PATH . 'languages/' );

    }

    /**
     * Add main menu
     */
    public function admin_menu(){
        //main menu
        add_submenu_page('woocommerce', __( "KIWIZ Certification", 'kiwiz-invoices-certification-pdf-file' ), __( "KIWIZ Certification", 'kiwiz-invoices-certification-pdf-file' ), 'manage_options', $this->_slug_main_admin_menu, array($this, 'get_page_admin'));
    }


    /**
     * Manage admin menu links
     */
    public function get_page_admin() {
		$page = sanitize_text_field($_GET['page']);
        if ( isset($page) ) {

            switch( $page ) {
                case $this->_slug_main_admin_menu :
                    include KIWIZ_PLUGIN_PATH . 'admin/tpl/kiwiz-certification-page.php';
                    break;
                default:
                    break;
            }

        }
    }

    /**
     * Init options value
     */
    public function init_options() {

        $options = array(   array('id' => 'KIWIZ_DOCUMENT_INVOICE_INCREMENT_ID_OPTION_NAME',  'name' => 'kiwiz_document_invoice_increment_id', 'value' => 100000000 ),
                            array('id' => 'KIWIZ_DOCUMENT_REFUND_INCREMENT_ID_OPTION_NAME', 'name' => 'kiwiz_document_refund_increment_id',  'value' => 100000000 ) );

        foreach ( $options as $option ) {
            if ( ! defined( $option['id'] ) ) { define( $option['id'], $option['name'] ); }

            $option_defined = get_option($option['name'], array());
            if ( !$option_defined ) {
                update_option($option['name'], $option['value']);
            }
        }

    }

    /**
     * Update increment id if necessary
     */
    public function update_increment_id() {

        //Only for version 2.0.6+ -- because setting increment id has change
        $plugin_kiwiz_data  = get_file_data(KIWIZ_PLUGIN_FILE, array('Version' => 'Version'), false);
        $update_increment_id = $option_defined = get_option('kiwiz_update_increment_id', array());
        if ( $plugin_kiwiz_data['Version'] >= "2.0.6" && $update_increment_id != '1' ) {
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            //set increment id
            $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s FOR UPDATE", KIWIZ_DOCUMENT_INVOICE_INCREMENT_ID_OPTION_NAME), ARRAY_A );
            $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s FOR UPDATE", KIWIZ_DOCUMENT_REFUND_INCREMENT_ID_OPTION_NAME), ARRAY_A );

            $last_invoice_increment_id = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_kiwiz_invoice_increment_id' ORDER BY meta_value DESC LIMIT 0,1", ARRAY_A );
            $last_refund_increment_id  = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_kiwiz_refund_increment_id' ORDER BY meta_value DESC LIMIT 0,1", ARRAY_A );

            //update main increment id
            if ( count($last_invoice_increment_id) > 0 && isset($last_invoice_increment_id[0]['meta_value']) ) {
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s", ($last_invoice_increment_id[0]['meta_value'] + 1), KIWIZ_DOCUMENT_INVOICE_INCREMENT_ID_OPTION_NAME));
            }
            if ( count($last_refund_increment_id) > 0 && isset($last_refund_increment_id[0]['meta_value']) ) {
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s", ($last_refund_increment_id[0]['meta_value'] + 1), KIWIZ_DOCUMENT_REFUND_INCREMENT_ID_OPTION_NAME));
            }

            add_option('kiwiz_update_increment_id', '1');

            $wpdb->query('COMMIT');
        }
    }

    public function cron_task() {
        $cronlist = Kiwiz_Document_Certify::get_cron_list();
        if ( !empty($cronlist) ){

            foreach( $cronlist as $documents) {
                foreach ( $documents as $document_type => $objects ) {
                    foreach ( $objects as $object_id ) {
                        switch ($document_type) {
                            case "invoice" :
                                $document = new Kiwiz_Document_Invoice($object_id);
                                break;
                            case "refund":
                                $document = new Kiwiz_Document_Refund($object_id);
                                break;
                            default:
                                break;
                        }
                        if (Kiwiz_Document_Certify::is_document_exist($object_id, $document_type)) {
                            $certify_document = new  Kiwiz_Document_Certify();
                            $certify_document->certify_document($document);
                        }
                    }
                }
            }

        }

    }

}
new Kiwiz_Init;
