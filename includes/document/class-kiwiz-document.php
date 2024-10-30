<?php

use Dompdf\Dompdf;

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
 * Class Kiwiz_Document
 */

class Kiwiz_Document extends Kiwiz_Document_Certify {

    public function __construct() {}

    /**
     * Generate pdf document from html dom
     * @param $html
     * @return Convert html datas to pdf file
     */
    protected function generate_pdf_file( $html, $path, $document_name ) {
        require_once( KIWIZ_PLUGIN_PATH . "lib/dompdf/autoload.inc.php" );
        define("DOMPDF_ENABLE_REMOTE", true);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);

        $options = $dompdf->getOptions();
        $options->setDefaultFont('helvetica');
        $options->setIsRemoteEnabled(true);
        $options->setIsHtml5ParserEnabled(true);
        $options->setDefaultPaperSize('A4');
        $dompdf->setOptions($options);

        $dompdf->render();


        $canvas      = $dompdf->getCanvas();
        $font        = 'Arial';
        $this->create_custom_document_footer( $canvas, $font );
        $footer_pagination = __( 'Page: {PAGE_NUM} of {PAGE_COUNT}',   'kiwiz-invoices-certification-pdf-file' );
        $canvas->page_text(16, 800, $footer_pagination, $font, 7, array(0,0,0));

        // The next call will store the entire PDF as a string in $pdf
        $document = $dompdf->output();

        $this->save_document($document, $path, $document_name);
    }

    /**
     * Create document footer
     */
    protected function create_custom_document_footer( $canvas, $font ) {
        $footer_custom_text = $this->get_shop_footer();
        $text_result = array();
        $max_width = 165;

        while( strlen($footer_custom_text) > $max_width ) {
            $text_result[] = substr($footer_custom_text,0,$max_width);
            $footer_custom_text = substr($footer_custom_text,$max_width,$max_width);
        }
        if ( $footer_custom_text != '' )
            $text_result[] = $footer_custom_text;

        $y = 770 -  ( (count($text_result)-1)*10 );
        foreach( $text_result as $line ) {
            $canvas->page_text(16, $y, $line, $font, 7, array(0,0,0));
            $y += 10;
        }
    }

    /**
     * Compress image
     * @param $source_url
     * @param $destination_url
     * @param $quality
     * @return mixed
     */
    protected function compress_image( $source_url, $image_name ) {

        $compress_image = new  Kiwiz_Image_Compress($source_url, $image_name, 90, 9, KIWIZ_DOCUMENT_IMAGES_DIR);
        $new_image = $compress_image->compress_image();
        if ( $new_image !== false ) {
//            $path           = parse_url(KIWIZ_DOCUMENT_IMAGES_URL . $new_image, PHP_URL_PATH);
//            if ( $path != '' ) {
//                //$compress_image = $_SERVER["DOCUMENT_ROOT"].$path;
//                $compress_image = get_site_url().$path;
//                return $compress_image;
//            }
			return KIWIZ_DOCUMENT_IMAGES_URL . $new_image;
        }
        return null;
    }

        /**
     * Save the document in document in serveur directory defined
     * @param $document_content
     * @param $document_path
     * @param $document_name
     */
    protected function save_document( $document_content, $document_path, $document_name ) {

        if ( ! file_exists( KIWIZ_DOCUMENT_DIR. $document_path ) ) {
            wp_mkdir_p( KIWIZ_DOCUMENT_DIR. $document_path  );
        }
        if ( ! file_put_contents( KIWIZ_DOCUMENT_DIR. $document_path .'/' . $document_name , $document_content ) )
            $this->set_error( new WP_Error( 'kiwiz_error_create_document', __('Create document fail', 'kiwiz-invoices-certification-pdf-file') ) );
    }

    /**
     * Delete document
     * @param $order_id
     * @param $document_path
     * @param $document_name
     * @param $document_type
     */
    protected function remove_document( $order_id, $document_path, $document_name, $document_type ) {
        if ( get_post_meta($order_id, '_kiwiz_'.$document_type.'_certify', 'certify') != 'certify' )
            unlink(KIWIZ_DOCUMENT_DIR. $document_path .'/' . $document_name );
    }

    /**
     * Returns pdf document settings
     * @param $option_name
     * @return option value if it exists or null
     */
    protected function get_document_option( $option_name ) {
        $options = get_option ( 'woocommerce_' . KIWIZ_CERT_SETTINGS . '_settings' );
        if ( !is_null($options) ) {
            return $options[$option_name];
        }
        return null;
    }

    /**
     * Returns document content encode in base64
     * @return null|string
     */
    protected function get_document_content( $get_certify = true ) {

        if ( $get_certify ) {
            $content = parent::get_certify_document( $this );
            if ( $content == null && $this->get_document_status() == "certify" )
                return null;
        }
        else
            $content = null;

        if ( $content == 'no_certification' || $content == null ) {
            $content = file_get_contents( KIWIZ_DOCUMENT_DIR. $this->_document_type . '/' . $this->_document_name );
            if ( $content === false )
                return null;
        }
        return base64_encode($content);


    }

    /**
     * Dispatach ajax action
     * @param $action
     * @return array
     */
    protected function dispatch_action(  $action ) {
        switch ( $action ) {
            case "kiwiz_create_document":
                $can_create_document = $this->check_required_pdf_datas();
                if ( ! $this->document_exist() && $can_create_document['result'] ) {
                    $this->create_document();
                } else {
                    $reason = isset($can_create_document['reason']) ? $can_create_document['reason'] : null;
                    return array("error" => $reason );
                }
                $can_certifiy_document = parent::can_certify_document($this);
                if ( $can_certifiy_document && $can_certifiy_document['result'] )
                    parent::certify_document( $this );
                else {
                    if ( Kiwiz_API::get_token() ) //if token exist, flag document as no certify
                        update_post_meta($this->_order->get_id(), '_kiwiz_'.$this->_document_type.'_certify', 'no certify');
                }
                $document_content = $this->get_document_content( false );
                if ( $document_content != null ) {
                    $this->send_email_to_customer( $this->_document_type );
                    return array("document_content" => $document_content, "document_name" => $this->_document_number, "callback_action" => "download_and_reload" );
                }
                else
                    return array("error" => __('The document does not exist', 'kiwiz-invoices-certification-pdf-file') );
                break;
            case "kiwiz_certify_document":
                $can_certifiy_document = parent::can_certify_document($this);
                if ( $this->document_exist() && $can_certifiy_document['result'] ) {
                    parent::certify_document( $this );
                    return array("callback_action" => "reload" );
                }

                $error_message = __('Certification document failed', 'kiwiz-invoices-certification-pdf-file');
                if ( ! $can_certifiy_document['result'] ) {
                    $error_message .= ' : '.$can_certifiy_document['reason'];
                }
                return array("error" => $error_message );
                break;
            case "kiwiz_get_document":
                $document_content = $this->get_document_content();
                if ( $document_content != null )
                    return array("document_content" => $document_content, "document_name" => $this->_document_number, "callback_action" => "download" );
                else
                    return array("error" => __('The document does not exist', 'kiwiz-invoices-certification-pdf-file') );
                break;
            default:
                break;
        }
    }

    /**
     * Returns true if the meta data "filename" exists and pdf document too
     * @return bool
     */
    public function document_exist() {
        $object_id = null;
        switch ( $this->_document_type ) {
            case "invoice":
                $object_id = $this->_order->get_id();
                break;
            case "refund":
                $object_id = $this->_refund->get_id();
                break;
        }
        $file_name = get_post_meta($object_id, $this::FILENAME_META_KEY , true);
        if ( $file_name != '' && file_exists( KIWIZ_DOCUMENT_DIR . $this->_document_type . '/' . $file_name ) )
            return true;

        return false;
    }

    /**
     * Send an email with document
     */
    protected function send_email_to_customer( $document_type ) {
        switch ( $document_type ) {
            case "invoice":
                do_action( 'woocommerce_before_resend_order_emails', $this->_order, 'customer_invoice' );

                // Send the customer invoice email.
                WC()->payment_gateways();
                WC()->shipping();
                WC()->mailer()->customer_invoice( $this->_order );

                // Note the event.
                $this->_order->add_order_note( __( 'Order details manually sent to customer.', 'woocommerce' ), false, true );

                do_action( 'woocommerce_after_resend_order_email', $this->_order, 'customer_invoice' );

                break;
            default:
                break;
        }
    }

    /**
     * Check if all required settings are defined
     * @return bool|string|void
     */
    public function check_required_pdf_datas() {
        $option_setting_name = 'woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings';
        $cert_settings_options = get_option($option_setting_name, array());
        if (    $cert_settings_options['shop_pdf_name'] == ''
            || $cert_settings_options['shop_pdf_address'] == ''
            || $cert_settings_options['shop_pdf_footer'] == '') {
            $reason_button = ' <a href="%s" class="button-primary" target="_blank">'.__( 'Configure', 'kiwiz-invoices-certification-pdf-file' ).'</a>';
            $reason_button = sprintf( $reason_button, admin_url( 'admin.php?page=wc-settings&tab=integration&section=kiwiz_account' ) );
            return array( 'result' => false, 'reason' => __( 'Required PDF settings are not defined', 'kiwiz-invoices-certification-pdf-file' ) . $reason_button );
        }
        return array( 'result' => true);
    }

}
