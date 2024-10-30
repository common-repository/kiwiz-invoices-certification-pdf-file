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
 * Class Kiwiz
 */

class Kiwiz {

    static $module_woocommerce = array(
        'module_name' =>  'woocommerce',
        'local_path'  =>  'woocommerce/woocommerce.php'
    );

    private $_dir_email_tmp = 'email_tmp';

    /**
     * Kiwiz Constructor.
     */
    public function __construct() {
        $this->_define_constants();
        $this->_includes();
        $this->_create_directories();
        $this->_add_filters();
        $this->_add_actions();
        $this->_languages();

        do_action( 'kiwiz_loaded' );
    }

    /**
     * Define KIWIZ Constants.
     */
    private function _define_constants() {
        $this->_define( 'KIWIZ_CERT_SETTINGS','kiwiz_account' );
        $this->_define( 'KIWIZ_PDF_SETTINGS', 'kiwiz_pdf' );
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function _define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Add hook actions
     */
    private function _add_actions() {
        add_action( 'admin_notices',  array($this, 'admin_notices') );
        add_action( 'wp_ajax_kiwiz',  array($this, 'manage_document') );
        add_action( 'woocommerce_order_partially_refunded'  , array($this, 'after_order_refunded'), 10, 2 );
        add_action( 'woocommerce_order_fully_refunded'      , array($this, 'after_order_refunded'), 10, 2 );
        add_action( 'woocommerce_after_resend_order_email'  , array($this, 'after_resend_order_email'), 10, 1 );
        add_action( 'woocommerce_refund_created'            , array($this, 'after_refund_created'), 10, 1 );
        add_action( 'manage_shop_order_posts_custom_column' , array($this, 'kiwiz_status_order_column_content'), 20, 2 );
        add_action( 'woocommerce_get_sections_integrations' , array($this, 'kiwiz_sections_integrations'));
        add_action( 'admin_notices'                         , array($this, 'kiwiz_bulk_action_admin_notice'));

        $this->_add_hook_order_status_change();
    }

    /**
     * Add hook filters
     */
    private function _add_filters() {
        add_filter( 'woocommerce_email_attachments', array( $this, 'attach_document_to_email' ), 99, 3 );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_kiwiz_status_order_column'), 99);
        add_filter( 'handle_bulk_actions-edit-shop_order',  array( $this,'generate_kiwiz_invoice_handle_bulk_action_edit_shop_order'), 10, 3 );
        add_filter( 'bulk_actions-edit-shop_order',         array( $this,'generate_kiwiz_invoice_bulk_actions_edit_order'), 20, 1 );
    }

    /**
     * Define language folder
     */
    private function _languages() {
        load_plugin_textdomain( 'kiwiz', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Included all the useful classes
     */
    private function _includes() {

        /**
         * Library
         */

        /**
         * Document class
         */
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-certify.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-invoice.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-invoice-datas.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-refund.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-refund-datas.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-image-compress.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-tax-datas.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/document/class-kiwiz-document-example.php';

        /**
         * Backend class
         */
        include_once KIWIZ_PLUGIN_PATH . 'includes/admin/class-kiwiz-admin.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/admin/class-kiwiz-order-access.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/admin/class-kiwiz-invoice-list.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/admin/class-kiwiz-refund-list.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/admin/class-kiwiz-concat-pdf.php';

        /**
         * Frontend class
         */
        include_once KIWIZ_PLUGIN_PATH . 'includes/frontend/class-kiwiz-frontend.php';

        /**
         * Main class
         */
        include_once KIWIZ_PLUGIN_PATH . 'includes/class-kiwiz-init.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/class-kiwiz-api.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/class-kiwiz-utils.php';
        include_once KIWIZ_PLUGIN_PATH . 'includes/class-kiwiz-encrypt.php';



    }

    private function _create_directories() {
        if ( ! file_exists( KIWIZ_DOCUMENT_IMAGES_DIR ) ) {
            wp_mkdir_p( KIWIZ_DOCUMENT_IMAGES_DIR );
        }
    }

    /**
     * Return true if the plugin Kiwiz is activate
     */
    static public function is_kiwiz_plugin_activate() {
        $kiwiz_activation_date  = get_option('kiwiz_activation_date');
        if ( $kiwiz_activation_date !== false && $kiwiz_activation_date < time() )
            return true;
        return false;
    }

    /**
     * Return true if kiwiz account is defined
     */
    public function is_kiwiz_account_activate() {
        self::is_kiwiz_plugin_activate();
    }


    /**
     * Determines if the plugin woocommerce is enabled
     * @return bool
     */
    public static function is_woocommerce_active(){
        if ( is_plugin_active('woocommerce/woocommerce.php') ){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Display admin notice
     */
    public function admin_notices(){
        if ( !$this->is_woocommerce_active() ){
            $class = 'notice notice-error';
            $url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
            $message = sprintf( __( 'KIWIZ WooCommerce Invoice & Refunds Certification : This module must be installed <strong>%s</strong> <a href="%s" class="button-primary">Install WooCommerce</a>', 'kiwiz-invoices-certification-pdf-file' ),'WooCommerce', esc_url($url));
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
        }
    }

    /**
     * Manage document's ajax action
     */
    public function manage_document() {
        $datas = null;
        if ( count($_POST) > 0 )
            $datas = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST ) );
        else if ( count($_GET) > 0 )
            $datas = array_map( 'sanitize_text_field', wp_unslash( (array) $_GET ) );

        if ( isset( $datas['action']) 
			&& $datas['action'] == 'kiwiz'
			&&  $this->_check_user_permission($datas)
			&& $this->is_valide_nonce($datas['nonce'])) {

            $result = array();

             switch ( $datas['type'] ) {
                case 'invoice':
					if (empty($datas['object_id']) || empty($datas['kiwiz_action'])) {
						wp_send_json_error( __( "Error unknow action", 'kiwiz-invoices-certification-pdf-file' ) );
						break;
					}

                    $check_order = Kiwiz_Document_Certify::can_create_document( $datas['object_id'], 'invoice' );
                    if ( $check_order === true ) {
                        $invoice = new Kiwiz_Document_Invoice($datas['object_id']);
                        $result = $invoice->dispatch_action($datas['kiwiz_action']);
                    } else {
                        $result['error'] = __("The invoice can't be created",'kiwiz-invoices-certification-pdf-file').', '.$check_order.'.';
                    }
                    break;
                case 'refund':
					if (empty($datas['object_id']) || empty($datas['kiwiz_action'])) {
						wp_send_json_error( __( "Error unknow action", 'kiwiz-invoices-certification-pdf-file' ) );
						break;
					}
                    $refund = new Kiwiz_Document_Refund($datas['object_id']);
                    $result = $refund->dispatch_action( $datas['kiwiz_action'] );
                    break;
				case 'example':
					$example = new Kiwiz_Document_Example();
					$example->create_document();
					$result = array("document_content" => $example->get_document_content(), "document_name" => 'kiwiz-example', "callback_action" => "download" );
					break;
                default:
                    break;
            }

            if ( isset($result['error']) || $result == null ) {
                 if ( $result == null ) {
                     $result['error'] = __('Generate document failed', 'kiwiz-invoices-certification-pdf-file');
                 }
                 wp_send_json_error( $result );
            }
            else
                wp_send_json_success( $result );
        } else  {
            wp_send_json_error( __( "Error unknow action", 'kiwiz-invoices-certification-pdf-file' ) );
        }
    }

    /**
     * Create refund pdf automatically
     */
    public function after_order_refunded( $order_id, $refund_id) {
        $refund = new Kiwiz_Document_Refund( $refund_id );
        $refund->dispatch_action( 'kiwiz_create_document' );
    }

    /**
     * If document exist, attach to email
     */
    public function attach_document_to_email( $attachments, $status, $object ) {

        if ( ! $object instanceof WC_Order ) {
            return $attachments;
        }

        $document = null;

        switch ( $status ) {
            case "customer_invoice":
                if ( Kiwiz_Document_Certify::is_document_exist($object->get_id(), 'invoice') ) {
                    $document = new Kiwiz_Document_Invoice($object->get_id());
                    if ( ! Kiwiz::is_kiwiz_plugin_activate() && $document->get_document_status() == 'certify' ) { //don't send certify document if no kiwiz account
                        $document = null;
                    }
                }
                break;

            case "customer_partially_refunded_order":
            case "customer_refunded_order":
                $refund = Kiwiz_Document_Refund::get_last_order_refund( $object->get_id() );
                if ( Kiwiz_Document_Certify::is_document_exist($refund->get_id(), 'refund') ) {
                    $document = new Kiwiz_Document_Refund($refund->get_id());
                    if ( ! Kiwiz::is_kiwiz_plugin_activate() && $document->get_document_status() == 'certify' ) {
                        $document = null;
                    }
                }
                break;

            default:
                break;
        }

        if ( $document != null ) {
            $document_content    = base64_decode($document->get_document_content());
            $email_document_path = KIWIZ_DOCUMENT_DIR  . $document->get_document_type() . '/' . $this->_dir_email_tmp . '/'.  $document->get_increment_id() .'.pdf';

            if ( ! file_exists( KIWIZ_DOCUMENT_DIR . $document->get_document_type() . '/' . $this->_dir_email_tmp ) ) {
                wp_mkdir_p( KIWIZ_DOCUMENT_DIR . $document->get_document_type() . '/' . $this->_dir_email_tmp  );
            }

            if ( file_put_contents( $email_document_path , $document_content ) )
                $attachments[] = $email_document_path;
        }

        return $attachments;
    }

    /**
     * Remove temporary invoice file after email send
     * @param $order
     */
    public function after_resend_order_email( $order ) {
        //remove temporary invoice
        $document = new Kiwiz_Document_Invoice($order->get_id());
        $email_document_path = KIWIZ_DOCUMENT_DIR  . $document->get_document_type() . '/' . $this->_dir_email_tmp . '/'.  $document->get_increment_id() .'.pdf';
        unlink($email_document_path);
    }

    /**
     * Remove temporary refund file after email send
     * @param $order
     */
    public function after_refund_created( $refund_id ) {
        //remove temporary refund
        $document = new Kiwiz_Document_Refund($refund_id);
        $email_document_path = KIWIZ_DOCUMENT_DIR  . $document->get_document_type() . '/' . $this->_dir_email_tmp . '/'.  $document->get_increment_id() .'.pdf';
        unlink($email_document_path);
    }

    /**
     * Generate nonce key
     */
    static public function get_wp_nonce() {
        $kiwiz_nonce =  wp_create_nonce( 'kiwiz-nonce' );
        return $kiwiz_nonce;
    }

    /**
     * Test if nonce value is valide
     */
    public function is_valide_nonce( $nonce ) {
        if ( !wp_verify_nonce($nonce, 'kiwiz-nonce' ) )
            return false;
        return true;
    }

    /**
     * Check the permissions of the user
     * @param $datas
     * @return bool
     */
    private function _check_user_permission( $datas ) {

        if ( ! is_user_logged_in() ) {
            return false;
        }

        if( current_user_can('editor') || current_user_can('administrator') || current_user_can('shop_manager')) {
            return true;
        }

        $object_owner = null;
        switch( $datas['type'] ) {
            case 'invoice':
                $object_owner = get_post_meta( $datas['object_id'], '_customer_user', true );
                break;
            case 'refund':
                $parent_id = wp_get_post_parent_id($datas['object_id']);
                $object_owner = get_post_meta( $parent_id, '_customer_user', true );
                break;
            default:
                break;
        }

        if ( isset( $datas['frontend'] ) && get_current_user_id() == $object_owner ) {
            return true;
        }

        return false;
    }

    /**
     * Add "kiwiz status" column in backoffice order grid
     * @param $columns
     * @return array
     */
    public function add_kiwiz_status_order_column($columns ) {
        $new_columns = array();

        foreach( $columns as $key => $column){
            $new_columns[$key] = $column;
            if( $key ==  'order_status' ) { //inserting after "Status" column
                $new_columns['kiwiz_status'] = __( 'Kiwiz Certification','kiwiz-invoices-certification-pdf-file');
            }
        }
        return $new_columns;
    }

    /**
     * Add mass action on orders grid to create invoices
     * @param $redirect_to
     * @param $action
     * @param $post_ids
     * @return string
     */
    public function generate_kiwiz_invoice_handle_bulk_action_edit_shop_order( $redirect_to, $action, $post_ids ) {
        if ( $action !== 'kiwiz_invoice_mass_create_action' )
            return $redirect_to;

        $valid_orders = array();
        $error_orders = array();

        foreach ( $post_ids as $post_id ) {

            $check_order = Kiwiz_Document_Certify::can_create_document( $post_id, 'invoice' );
            if ( $check_order === true ) {
                //$order   = wc_get_order( $post_id );
                $invoice = new Kiwiz_Document_Invoice($post_id);
                if ( $invoice->document_exist() ) {
                    $error_orders[] = $post_id . ' : '. __("The invoice already exist", 'kiwiz-invoices-certification-pdf-file');
                } else {
                    $result = $invoice->dispatch_action( 'kiwiz_create_document' );
                    if ( isset($result['error']) )
                        $error_orders[] = $post_id .' : '. $result['error'];
                    else
                        $valid_orders[] = $post_id;
                }
            } else {
                $error_orders[] = $post_id .' : '. $check_order;
            }

        }

        return $redirect_to = add_query_arg( array(
            'kiwiz_invoice_mass_create_action' => '1',
            'valid_orders' => base64_encode(json_encode($valid_orders)),
            'error_orders' => base64_encode(json_encode($error_orders)),
        ), $redirect_to );
    }

    /**
     * Add kiwiz mass action to admin order list bulk dropdown
     * @param $actions
     */
    public function generate_kiwiz_invoice_bulk_actions_edit_order( $actions ) {
        $actions['kiwiz_invoice_mass_create_action'] = __("Create Invoice PDF", 'kiwiz-invoices-certification-pdf-file');
        return $actions;
    }

    public function kiwiz_bulk_action_admin_notice() {
        if ( empty( $_REQUEST['kiwiz_invoice_mass_create_action'] ) )
            return;

        $valid_orders = isset( $_REQUEST['valid_orders'] ) ? sanitize_text_field(json_decode(base64_decode($_REQUEST['valid_orders']))) : null;
        $error_orders = isset( $_REQUEST['error_orders'] ) ? sanitize_text_field(json_decode(base64_decode($_REQUEST['error_orders']))) : null;

        //display count valid order
        if ( $valid_orders != null && count($valid_orders) > 0 ) {
            printf('<div id="message" class="updated fade"><p>' .
                _n('Kiwiz : %s invoice was created', 
                    'Kiwiz : %s invoices were created.',
                    count($valid_orders),
                    'kiwiz-invoices-certification-pdf-file'
                ) . '</p></div>', count($valid_orders));
        }

        //display orders with errors
        if ( $error_orders != null && count($error_orders) > 0 ) {

            $error_detail = '<ul>';
            foreach ( $error_orders as $error ) {
                $error_detail .= '<li>'. __("Order", 'kiwiz-invoices-certification-pdf-file') .' #'. $error .'</li>';
            }
            $error_detail .= '</ul>';

            printf('<div id="message" class="error fade"><p><strong>' .
                _n("Kiwiz : %s invoice was not created.",
                    "Kiwiz : %s invoices were not created.",
                    count($error_orders),
                    'kiwiz-invoices-certification-pdf-file'
                ) . '</strong></p>%s</div>', count($error_orders), $error_detail);
        }
    }

    /**
     * Display "kiwiz status" content in order grid
     * @param $column_name
     * @param $order_id
     */
    public function kiwiz_status_order_column_content( $column_name, $order_id )
    {
        switch ($column_name) {
            case 'kiwiz_status' :
                $kiwiz_class          = '';
                $kiwiz_label          = '';
                if ( Kiwiz_Document_Certify::is_document_exist($order_id, 'invoice') ) {
                    $invoice              = new Kiwiz_Document_Invoice( $order_id );
                    $invoice_kiwis_status = $invoice->get_document_status();

                    switch ( $invoice_kiwis_status ) {
                        case 'nan':
                            $kiwiz_class = 'dashicons-no status-nan';
                            $kiwiz_label = __("Can not be certified", 'kiwiz-invoices-certification-pdf-file');
                            break;
                        case 'certify':
                            $kiwiz_class = 'dashicons-yes status-certify';
                            $kiwiz_label = __("Certified", 'kiwiz-invoices-certification-pdf-file');
                            break;
                        case 'no certify':
                            $kiwiz_class = 'dashicons-no status-no-certify';
                            $kiwiz_label = __("No certified", 'kiwiz-invoices-certification-pdf-file');
                            break;
                        default:
                            break;
                    }
                } else {
                    $kiwiz_class = 'dashicons-no status-nan';
                    $kiwiz_label = __("No invoice yet", 'kiwiz-invoices-certification-pdf-file');
                }
                echo ('<div class="kiwiz-status-order-grid dashicons-before ' .esc_html($kiwiz_class). '">' .esc_html($kiwiz_label). '</div>');
                break;

            default :
                break;
        }
    }

    /**
     * Add hook on order status change to generate document
     */
    private function _add_hook_order_status_change() {
        $kiwiz_options = get_option ( 'woocommerce_' . KIWIZ_CERT_SETTINGS . '_settings' );
        if ( isset($kiwiz_options['kiwiz_status_order_event_invoice']) && $kiwiz_options['kiwiz_status_order_event_invoice'] != '') {
            foreach ( $kiwiz_options['kiwiz_status_order_event_invoice'] as $status ) {
                $status_array = explode("wc-", $status);
                $action_name= 'woocommerce_order_status_'.$status_array[1];
                add_action( $action_name, array($this, 'generate_invoice_on_status_change'), 11, 1 );
            }
        }
    }

    /**
     * @param $order_id
     * Generate invoice on status change
     */
    public function generate_invoice_on_status_change( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( !$order ) {
            return;
        }

        if ( Kiwiz_Document_Certify::can_create_document( $order_id, 'invoice' ) === true ) {
            $invoice = new Kiwiz_Document_Invoice($order_id);
            if ( !$invoice->document_exist() ) {
                $invoice->dispatch_action( 'kiwiz_create_document' );
            }
        }
    }
}
