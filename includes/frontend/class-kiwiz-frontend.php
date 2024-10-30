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
 * Class Kiwiz_Frontend
 */

class Kiwiz_Frontend {

    const KIWIZ_DOCUMENT_TYPE = array( 'invoice', 'refund' );

    function __construct()	{
        add_action('woocommerce_order_details_after_order_table',  array( $this, 'kiwiz_frontend_document_link'), 11, 1);
    }

    /**
     * Display download link on view order page
     */
    public function kiwiz_frontend_document_link( $order ) {
        $document_link = '';
        foreach ( $this::KIWIZ_DOCUMENT_TYPE as $type ) {
            switch ( $type ) {
                case "invoice":
                    $document    = new Kiwiz_Document_Invoice( $order->get_id() );
                    if ( Kiwiz::is_kiwiz_plugin_activate() || $document->get_document_status() == 'nan' || $document->get_document_status() == 'no certify') {
                        if ($document->is_document_exist($order->get_id(), 'invoice')) {
                            $url = admin_url('admin-ajax.php?kiwiz_action=kiwiz_get_document&type=invoice&order_id=' . $order->get_id() . '&nonce=' . Kiwiz::get_wp_nonce());
                            $document_link .= '<h2>' . __('Invoice', 'kiwiz-invoices-certification-pdf-file') . '</h2>';
                            $document_link .= '<table><tr>';
                            $document_link .= '<td>' . sprintf(__('Invoice #%s', 'kiwiz-invoices-certification-pdf-file'), esc_html($document->get_increment_id())) . '</td>';
                            $document_link .= '<td><a href="' . esc_url($url) . '" class="woocommerce-button button invoice">' . __('Download', 'kiwiz-invoices-certification-pdf-file') . '</a></td>';
                            $document_link .= '</tr></table>';
                        }
                    }
                    break;
                case "refund":
                    $refunds    = Kiwiz_Document_Refund::get_order_refund( $order->get_id() );
                    $document_refund = '';
                    if ( $refunds != null ) {
                        foreach ( $refunds as $refund ) {
                            $document = new Kiwiz_Document_Refund( $refund->get_id() );
                            if ( Kiwiz::is_kiwiz_plugin_activate() || $document->get_document_status() == 'nan' || $document->get_document_status() == 'no certify') {
                                if (  $document->is_document_exist($refund->get_id(), 'refund') ) {
                                    $url = admin_url( 'admin-ajax.php?kiwiz_action=kiwiz_get_document&type=refund&order_id=' . $refund->get_id() . '&nonce='.Kiwiz::get_wp_nonce());
                                    $document_refund .= '<tr>';
                                    $document_refund .= '<td>'.sprintf( __( 'Refund #%s', 'kiwiz-invoices-certification-pdf-file' ), esc_html($document->get_increment_id())).'</td>';
                                    $document_refund .= '<td><a href="'.esc_url($url).'" class="woocommerce-button button refund">'.__( 'Download', 'kiwiz-invoices-certification-pdf-file' ).'</a></td>';
                                    $document_refund .= '</tr>';
                                }
                            }
                        }
                    }

                    if ( strlen($document_refund) > 0 ) {
                        $document_link .= '<h2>'.__('Refund','kiwiz-invoices-certification-pdf-file').'</h2>';
                        $document_link .= '<table>';
                        $document_link .= wp_kses_post($document_refund);
                        $document_link .= '</table>';
                    }
                    break;
                default:
                    break;
            }
        }

        if ( $document_link != '' ) {
            echo ('<div id="kiwiz-document-order-view">'.wp_kses_post($document_link).'</div>');
        }
        
    }

}
new Kiwiz_Frontend();
