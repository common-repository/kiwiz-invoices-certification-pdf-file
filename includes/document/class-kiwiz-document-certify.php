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
 * Class Kiwiz_Document_Certify
 */

class Kiwiz_Document_Certify {

    const KIWIZ_CREATE_DOCUMENT_ACTION  = 'kiwiz_create_document';
    const KIWIZ_GET_DOCUMENT_ACTION     = 'kiwiz_get_document';
    const KIWIZ_CERTIFY_DOCUMENT_ACTION = 'kiwiz_certify_document';

    public function __construct() {}

    /**
     * Check if document is certified
     */
    static public function is_certified_document( $order_id, $document_type ) {
        $value = sanitize_text_field(get_post_meta($order_id, '_kiwiz_'.$document_type.'_certify', true));
        if ( $value == 'certify' ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns true if an document had been already generated
     */
    static public function is_document_exist( $order_id, $document_type ) {
        $document_filename = sanitize_text_field(get_post_meta($order_id, '_kiwiz_'.$document_type.'_filename', true));
        if ( $document_filename != '' )
            return true;

        return false;
    }

    /**
     * Return document link, if not false
     */
    static public function get_document_link( $order_id, $type ) {
        $meta_value = null;
        switch ( $type ) {
            case "invoice" :
                $meta_value = '_kiwiz_invoice_filename';
        }

        $document = sanitize_text_field(get_post_meta($order_id, $meta_value, true));
        if ( $document != '' ){
            return KIWIZ_DOCUMENT_URL . $document;
        }

        return false;
    }

    /**
     * Returns details about document
     * @param $order_id
     * @param $type
     * @return array
     */
    static public function get_document_details( $order_id, $type ) {
        $meta_keys = array ('increment_id',
                            'date',
                            'certify',
                            'filename');

       $meta_values = array();
        foreach ( $meta_keys as $mk ) {
           $meta_values[$mk] = sanitize_text_field(get_post_meta($order_id, '_kiwiz_'.$type.'_'.$mk , true));
       }

       return $meta_values;
    }

    /**
     * Returns pdf document settings
     * @param $option_name
     * @return string
     */
    static public function get_document_settings( $option_name ) {
        $option_setting_name = 'woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings';
        $options = get_option ( $option_setting_name );

        if ( !is_null($options) && $options) {
            return sanitize_text_field($options[$option_name]);
        }
        return '';
    }

    /**
     * @param $order_id
     * @return an array with reasons
     */
    public function can_certify_document( $document ){
        $can_certify = array( 'result' => false, 'reason' => '');
        $timestamp_document = 0;

        switch ( $document->_document_type ) {
            case 'invoice':
                $timestamp_document = get_post_meta($document->_order->get_id(), '_kiwiz_invoice_date', true);
                break;
            case 'refund':
                $timestamp_document = get_post_meta($this->_refund->get_id(), '_kiwiz_refund_date', true);
                break;
        }

        if ( $timestamp_document ){
            $kiwiz_activation_date  = get_option('kiwiz_activation_date');
            if ( $timestamp_document >= $kiwiz_activation_date && $kiwiz_activation_date !== false ){
                $can_certify = array( 'result' => true);
            } else {
                $can_certify = array( 'result' => false, 'reason' => __('the document is older than activation Kiwiz account date', 'kiwiz-invoices-certification-pdf-file'));
            }
        } else {
            $can_certify = array( 'result' => true);
        }

        $option_setting_name = 'woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings';
        $cert_settings_options = get_option($option_setting_name, array());
        if (    $cert_settings_options['shop_pdf_name'] == ''
                || $cert_settings_options['shop_pdf_address'] == ''
            || $cert_settings_options['shop_pdf_footer'] == '') {
            $reason_button = ' <a href="%s" class="button-primary" target="_blank">'.__( 'Configure', 'kiwiz-invoices-certification-pdf-file' ).'</a>';
            $reason_button = sprintf( $reason_button, admin_url( 'admin.php?page=wc-settings&tab=integration&section=kiwiz_account' ) );
            $can_certify = array( 'result' => false, 'reason' => __( 'Required PDF settings are not defined', 'kiwiz-invoices-certification-pdf-file' ) . $reason_button );
        }

        return $can_certify;
    }

    /**
     * Certifie document with Kiwiz API
     * @param $document
     */
    public function certify_document( $document ) {

        $token = Kiwiz_API::get_token();
        if ( $token ) {
            $document_path = sanitize_text_field(KIWIZ_DOCUMENT_DIR . $document->_document_type .'/'.  $document->_document_name);
            $kiwiz_result = null;
            $object_id = null;
            switch( $document->_document_type ) {
                case "invoice":
                    $object_id = $document->_order->get_id();
                    $kiwiz_result = Kiwiz_API::save_invoice($token, $document_path, $object_id, sanitize_text_field($document->_document_type) );
                    break;
                case "refund":
                    $object_id = $document->_refund->get_id();
                    $kiwiz_result = Kiwiz_API::save_refund($token, $document_path, $object_id, sanitize_text_field($document->_document_type));
                    break;
                default:
                    break;
            }

            //save hash
            if ( isset($kiwiz_result->file_hash) && isset($kiwiz_result->block_hash) ) {

                $this->save_document_certification_meta($object_id, $kiwiz_result->file_hash, $kiwiz_result->block_hash, sanitize_text_field($document->_document_type));
                //replace with certified document
                if ( $this->save_certified_document($document_path, $document) ) {

                    update_post_meta($object_id, '_kiwiz_'.sanitize_text_field($document->_document_type).'_certify', 'certify');

                    //remove to cron
                    $this->remove_document_to_cron_list($object_id, sanitize_text_field($document->_document_type));

                    //send notification email - success
                    $message =  sprintf( __( 'Certification successfully completed for the document No.%s (order  No.%s)', 'kiwiz-invoices-certification-pdf-file' ), $document->_document_number,  $document->_order->get_id()) . "\n" .
                        sprintf( __( 'file_hash : %s', 'kiwiz-invoices-certification-pdf-file' ), $this->get_document_kiwiz_datas( $object_id, 'file_hash', $document->_document_type )) . "\n" .
                        sprintf( __( 'block_hash : %s', 'kiwiz-invoices-certification-pdf-file' ), $this->get_document_kiwiz_datas( $object_id, 'block_hash', $document->_document_type )) . "\n" .
                        sprintf( __( 'Certification date : %s', 'kiwiz-invoices-certification-pdf-file' ), $this->get_document_kiwiz_datas( $object_id, 'certification_date', $document->_document_type ));

                    Kiwiz_Utils::send_email( __('KIWIZ Certification successfully completed', 'kiwiz-invoices-certification-pdf-file'), $message  );
                } else {
                    update_post_meta($object_id, '_kiwiz_'.sanitize_text_field($document->_document_type).'_certify', 'no certify');
                }

            } else {

                update_post_meta($object_id, '_kiwiz_'.sanitize_text_field($document->_document_type).'_certify', 'no certify');

                //save to cron
                $this->add_document_to_cron_list($object_id, sanitize_text_field($document->_document_type));

                //send notification email - error
                $message =  sprintf( __( 'Kiwiz Certification failed for the document  No.%s (order  No.%s)', 'kiwiz-invoices-certification-pdf-file' ), $document->_document_number,  $document->_order->get_id()) . "\n" .
                            sprintf( __( 'Error: %s - %s', 'kiwiz-invoices-certification-pdf-file' ), array($kiwiz_result->error, $kiwiz_result->message));

                Kiwiz_Utils::send_email( __('Kiwiz Certification failed', 'kiwiz-invoices-certification-pdf-file'), $message  );

            }
        }
    }

    /**
     * Returns certified document
     * @param $order_id
     * @return null
     */
    public function get_certify_document( $document ) {
        $token = Kiwiz_API::get_token();
        if ( $token ) {
            $kiwiz_result = null;
            switch( $document->_document_type ) {
                case "invoice":
                    $document_block_hash = $this->get_document_kiwiz_datas($document->_order->get_id(), 'block_hash', $document->_document_type);
                    if ( $document_block_hash != '' )
                        $kiwiz_result        = Kiwiz_API::get_invoice($token, $document_block_hash );
                    break;
                case "refund":
                    $document_block_hash = $this->get_document_kiwiz_datas($document->_refund->get_id(), 'block_hash', $document->_document_type);
                    if ( $document_block_hash != '' )
                        $kiwiz_result        = Kiwiz_API::get_refund($token,$document_block_hash );
                    break;
                default:
                    break;
            }
            return $kiwiz_result;
        }
        return 'no_certification';
    }

    /**
     * Save certification meta datas
     * @param $order_id
     * @param $file_hash
     * @param $block_hash
     * @param $document_type
     */
    private function save_document_certification_meta( $order_id, $file_hash, $block_hash, $document_type ) {
        update_post_meta($order_id, '_kiwiz_'.$document_type.'_file_hash', $file_hash);
        update_post_meta($order_id, '_kiwiz_'.$document_type.'_block_hash', $block_hash);
        update_post_meta($order_id, '_kiwiz_'.$document_type.'_certification_date', date('Y-m-d H:i:s'));
    }

    /**
     * Cancel certification meta datas
     * @param $order_id
     * @param $file_hash
     * @param $block_hash
     * @param $document_type
     */
    private function remove_document_certification_meta( $order_id, $document_type){
        delete_post_meta($order_id, '_kiwiz_'.$document_type.'_file_hash');
        delete_post_meta($order_id, '_kiwiz_'.$document_type.'_block_hash');
        delete_post_meta($order_id, '_kiwiz_'.$document_type.'_certification_date');
    }

    /**
     * Returns value of kiwie meta data
     * @param $order_id
     * @param $meta_key
     * @param $document_type
     */
    static public function get_document_kiwiz_datas( $order_id, $meta_key, $document_type ) {
        return get_post_meta($order_id, '_kiwiz_'.$document_type.'_'.$meta_key, true);
    }

    /**
     * Save the certified document instead of the generated document
     * @param $order_id
     * @param $document_path
     * @param $document_type
     */
    private function save_certified_document($document_path, $document ) {
        $data = $this->get_certify_document($document);
        if ( $data != null  ) {
            file_put_contents($document_path, $data);
            return true;
        }
        return false;
    }

    /**
     * Add document to cron document list
     * @param $order_id
     * @param $document_type
     */
    private function add_document_to_cron_list( $order_id, $document_type ){
        $crons_list = get_option('_kiwiz_'.$document_type.'_cron_list', array());
        $crons_list[] = $order_id;
        update_option('_kiwiz_'.$document_type.'_cron_list', array_unique(array_filter($crons_list)));
    }

    /**
     * Remove document from cron document list
     * @param $order_id
     * @param $document_type
     */
    private function remove_document_to_cron_list( $order_id, $document_type ) {
        $crons_list = get_option('_kiwiz_'.$document_type.'_cron_list', array());
        foreach ( $crons_list as $k=>$v){
            if ( $v == $order_id){
                unset($crons_list[$k]);
                }
        }
        update_option('_kiwiz_'.$document_type.'_cron_list', array_unique(array_filter($crons_list)));
    }

    /**
     * Return cron list for all document types
     */
    public static function get_cron_list() {
        $crons_list = array();
        $document_types = array( 'invoice', 'refund');
        foreach ( $document_types as  $document_type ) {
            $crons_list[] = array( $document_type => get_option('_kiwiz_'.$document_type.'_cron_list', array()) );
        }
        return $crons_list;
    }

    /**
     * Check if required datas are set
     * @param $object_id
     * @param $document_type
     * @return bool|string|void
     */
    public static function can_create_document( $object_id, $document_type ) {
        switch( $document_type ) {
            case "invoice":
                $order =  wc_get_order( $object_id );
                if ( $order->get_status() == 'auto-draft')
                    return __('the order was not created','kiwiz-invoices-certification-pdf-file');

                if (  count($order->get_items()) == 0 )
                    return __('order is empty','kiwiz-invoices-certification-pdf-file');

                if ( ! self::check_address_datas( $order, 'billing') )
                    return __('billing address is incomplete','kiwiz-invoices-certification-pdf-file');

                if ( ! self::check_address_email_format( $order ) )
                    return __('incorrect email format in the billing address','kiwiz-invoices-certification-pdf-file');

                if ( $order->get_payment_method() == '')
                    return __('payment method is not defined ','kiwiz-invoices-certification-pdf-file');

                return true;

                break;
            default:
                break;
        }
    }

    /**
     * @param $order
     * @param $type
     * @return bool
     */
    public static function check_address_datas($order, $type ) {
        if ( $type == 'billing' ) {
            if ( $order->get_billing_first_name() == ''
                || $order->get_billing_last_name() == ''
                || $order->get_billing_address_1() == ''
                || $order->get_billing_postcode() == ''
                || $order->get_billing_city() == '' ) {

                return false;
            }
            return true;
        }

        if ( $type == 'shipping' ) {
            if ( $order->get_shipping_first_name() == ''
                || $order->get_shipping_last_name() == ''
                || $order->get_shipping_address_1() == ''
                || $order->get_shipping_postcode()  == ''
                || $order->get_shipping_city() == '' ) {

                return false;
            }
            return true;
        }

    }

    /**
     * @param $order
     * Check if email format in the billing address is correct
     */
    public static function check_address_email_format( $order ) {

       if( !preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$^", $order->get_billing_email()) )
           return false;

       return true;
    }
}
