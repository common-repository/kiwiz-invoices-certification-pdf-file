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
 * manage access to order when it's already certified
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Kiwiz_Order_Access
 */
class Kiwiz_Order_Access {

    private $_order;

    function __construct(){
        $this->_order = $this->_get_post();
        add_action( 'admin_head', array($this, 'admin_head') );
        add_action( 'add_meta_boxes_shop_order', array( $this, 'add_invoice_buttons' ) );
    }

    private function _get_post() {
        global $pagenow;
        $post_id = isset($_GET['post']) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : null;
        if ( $pagenow == 'post.php' && $post_id > 0 ){
            $_post = get_post($post_id);
            if ( $_post && $_post->post_type == 'shop_order' ){
                return $_post;
            }
        }
        return null;
    }

    public function admin_head() {
        //edit order
        if (  ! is_null($this->_order)  ){
            //desactive actions if invoice is certified
            if ( Kiwiz_Document_Certify::is_document_exist($this->_order->ID, 'invoice') ) {
                ?>
                <style>
                    a.edit_address,
                    a.delete-order-item,
                    a.delete_refund,
                    a.edit-order-item,
                    a.delete-order-tax,
                    button.add-line-item,
                    button.add-coupon,
                    button.bulk-delete-items,
                    #postcustom,
                    .remove-coupon {
                        display: none!important;
                    }
                </style>
                <script type="text/javascript">
                    jQuery(document).ready(function(){
                        jQuery('input[name=order_date]').attr('disabled', 'disabled');
                        jQuery('input[name=order_date_hour]').attr('disabled', 'disabled');
                        jQuery('input[name=order_date_minute]').attr('disabled', 'disabled');

                        var customer_user_selected = jQuery('select[name=customer_user] option:selected');
                        jQuery('select[name=customer_user]').attr('disabled', 'disabled');
                        jQuery('select[name=customer_user]').parent().append( '<input type="hidden" name="customer_user" value="'+customer_user_selected.val()+'" />' );
                        jQuery('select[name=customer_user]').parent().append( '<input type="hidden" name="order_date" value="'+jQuery('input[name=order_date]').val()+'" />' );
                        jQuery('select[name=customer_user]').parent().append( '<input type="hidden" name="order_date_hour" value="'+jQuery('input[name=order_date_hour]').val()+'" />' );
                        jQuery('select[name=customer_user]').parent().append( '<input type="hidden" name="order_date_minute" value="'+jQuery('input[name=order_date_minute]').val()+'" />' );

                    })
                </script>
                <?php
            } ?>
            <style>
                a.delete_refund {
                    display: none!important;
                }
            </style>
            <?php

        }
    }

    public function add_invoice_buttons() {
        add_meta_box(
            'admin-kiwiz-box',
            __( "Manage documents PDF", 'kiwiz-invoices-certification-pdf-file' ),
            array( $this, 'pdf_actions_meta_box' ),
            'shop_order',
            'normal',
            'default'
        );
    }

    /**
     * Create the meta box content on the single order page
     */
    public function pdf_actions_meta_box( $post ) {
        echo '<div id="kiwiz-actions-error-msg"></div>';
		echo '<ul class="kiwiz-actions">';

        $kiwiz_activate = Kiwiz::is_kiwiz_plugin_activate();

        //Invoice
        $document_exist = Kiwiz_Document_Certify::is_document_exist($post->ID, 'invoice');
		echo '<li class="kiwiz-item">';
		echo '<div class="detail-title">' . esc_html(__('Invoice', 'kiwiz-invoices-certification-pdf-file')) . '</div>';
		echo '<ul class="content">';
        if (!$document_exist) {
			echo '<li><a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\'' . esc_js(Kiwiz_Document_Certify::KIWIZ_CREATE_DOCUMENT_ACTION) . '\', \'invoice\', \'' . esc_js($post->ID) . '\', \'' . esc_js(Kiwiz::get_wp_nonce()) . '\')" class="button button-meta-box dashicons-before dashicons-media-spreadsheet" alt="' . __('Create Invoice PDF', 'kiwiz-invoices-certification-pdf-file') . '">' . __('Create Invoice PDF', 'kiwiz-invoices-certification-pdf-file') . '<div class="loader"></div></a></li>';
        } else {
			echo '<li>';
            $document_details = Kiwiz_Document_Certify::get_document_details($post->ID, 'invoice');
			echo wp_kses_post($this->get_document_detail($post->ID, $document_details, 'invoice'));

            if ($document_details['certify'] == 'no certify' && $kiwiz_activate )
				echo '<a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\'' . esc_js(Kiwiz_Document_Certify::KIWIZ_CERTIFY_DOCUMENT_ACTION) . '\', \'invoice\', \'' . esc_js($post->ID) . '\', \'' . esc_js(Kiwiz::get_wp_nonce()) . '\')" class="button button-meta-box dashicons-before dashicons-yes button-certify" alt="' . __('Certify invoice PDF', 'kiwiz-invoices-certification-pdf-file') . '">' . __('Certify invoice PDF', 'kiwiz-invoices-certification-pdf-file') . '<div class="loader"></div></a>';
            if ( $kiwiz_activate || $document_details['certify'] != 'certify' ) {

                if($document_details['certify'] != 'certify'){
                    echo '<a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\'' . esc_js(Kiwiz_Document_Certify::KIWIZ_CERTIFY_DOCUMENT_ACTION) . '\', \'invoice\', \'' . esc_js($post->ID) . '\', \'' . esc_js(Kiwiz::get_wp_nonce()) . '\')" class="button button-meta-box dashicons-before dashicons-undo button-certify" alt="' . __('Relancer la certification', 'kiwiz-invoices-certification-pdf-file') . '">' . __('Relancer la certification', 'kiwiz-invoices-certification-pdf-file') . '<div class="loader"></div></a>';

                }
                echo '<div class="content"><a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\'' . esc_js(Kiwiz_Document_Certify::KIWIZ_GET_DOCUMENT_ACTION) . '\', \'invoice\', \'' . esc_js($post->ID) . '\', \'' . esc_js(Kiwiz::get_wp_nonce()) . '\')" class="button button-meta-box dashicons-before dashicons-media-spreadsheet" alt="' . __('Download invoice PDF', 'kiwiz-invoices-certification-pdf-file') . '">' . __('Download invoice PDF', 'kiwiz-invoices-certification-pdf-file') . '<div class="loader"></div></a></div>';
            }
			echo '</li>';
        }
		echo'</ul>';
		echo '</li>';


        //Refund
        $order_refunds = Kiwiz_Document_Refund::get_order_refund( $post->ID );
        if ( $order_refunds != null ) {
			echo '<li class="kiwiz-item">';
			echo '<div class="detail-title">'.__('Refund', 'kiwiz-invoices-certification-pdf-file').'</div>';
			echo '<ul class="content">';
            foreach ( $order_refunds as $refund ) {
				echo '<li>';
                $document_exist = Kiwiz_Document_Certify::is_document_exist( $refund->get_id(), 'refund' );
				echo '<div class="document-title">'.__('Refund', 'kiwiz-invoices-certification-pdf-file').' #'.esc_html($refund->get_id()).'</div>';
                if (  ! $document_exist ) {
					echo '<a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\''.esc_js(Kiwiz_Document_Certify::KIWIZ_CREATE_DOCUMENT_ACTION).'\', \'refund\', \''.esc_js($refund->get_id()).'\', \''.esc_js(Kiwiz::get_wp_nonce()).'\')" class="button button-meta-box dashicons-before dashicons-media-spreadsheet" alt="' .__('Create Refund PDF', 'kiwiz-invoices-certification-pdf-file'). '">' .__('Create Refund PDF', 'kiwiz-invoices-certification-pdf-file'). '<div class="loader"></div></a>';
                } else {
                    $document_details = Kiwiz_Document_Certify::get_document_details($refund->get_id(), 'refund');
					echo wp_kses_post($this->get_document_detail( $refund->get_id(), $document_details, 'refund' ));
                    if ( $document_details['certify'] == 'no certify' && $kiwiz_activate )
						echo '<a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\'' . esc_js(Kiwiz_Document_Certify::KIWIZ_CERTIFY_DOCUMENT_ACTION) . '\', \'refund\', \'' . esc_js($refund->get_id()) . '\', \'' . esc_js(Kiwiz::get_wp_nonce()) . '\')" class="button button-meta-box dashicons-before dashicons-yes button-certify" alt="' . __('Certify refund PDF', 'kiwiz-invoices-certification-pdf-file') . '">' . __('Certify refund PDF', 'kiwiz-invoices-certification-pdf-file') . '<div class="loader"></div></a><br>';
                    if ( $kiwiz_activate || $document_details['certify'] != 'certify')
						echo '<a href="#" onclick="displayLoader(jQuery(this)); return manage_kiwiz_document(\'' . esc_js(Kiwiz_Document_Certify::KIWIZ_GET_DOCUMENT_ACTION) . '\', \'refund\', \'' . esc_js($refund->get_id()) . '\', \'' . esc_js(Kiwiz::get_wp_nonce()) . '\')" class="button button-meta-box dashicons-before dashicons-media-spreadsheet" alt="' . __('Refund PDF', 'kiwiz-invoices-certification-pdf-file') . '">' . __('Download refund PDF', 'kiwiz-invoices-certification-pdf-file') . '<div class="loader"></div></a>';
                }
				echo '</li>';
            }
			echo '</ul>';
			echo '</li>';
        }

		echo '</ul>';
    }

    private function get_document_detail( $object_id, $document_details, $document_type ) {
        $content = '<div id="document-'.$object_id.'" class="detail-'.$document_type.' detail-main">';
        $content .= '<div class="detail-item">';
        $content .= '<span>' . __('Date : ', 'kiwiz-invoices-certification-pdf-file') . '</span>';
        $content .= '<strong><span>'. esc_html(date(Kiwiz_Document_Certify::get_document_settings('shop_date_format'), $document_details['date'])) .'</span></strong>';
        $content .= '</div>';
        $content .= '<div class="detail-item">';
        $content .= '<span>' . __('Number : ', 'kiwiz-invoices-certification-pdf-file') . '</span>';
        $content .= '<strong><span>'. esc_html($document_details['increment_id']) .'</span></strong>';
        $content .= '</div>';
        $content .= '<div class="detail-item">';
        $content .= '<span>' . __('Certification Kiwiz : ', 'kiwiz-invoices-certification-pdf-file') . '</span>';
        if ( $document_details['certify'] == 'certify' ){
            $content .= '<span class="certification certification-success dashicons-before dashicons-yes">'.__('Certified','kiwiz-invoices-certification-pdf-file').'</span>';
            if ( ! Kiwiz::is_kiwiz_plugin_activate() ) {
                $content .= '<span class="certification certification-infos"> (' . __('Subscribe to <a href="https://www.kiwiz.io/prix" target="_blank">Kiwiz</a> to activate the certification', 'kiwiz-invoices-certification-pdf-file') . ')</span>';
            }
        } elseif ( $document_details['certify'] == 'no certify' ) {
            $content .= '<span class="certification certification-failed dashicons-before dashicons-no">'.__('No certified','kiwiz-invoices-certification-pdf-file').'</span>';
        } else {
            $content .= '<span class="certification certification-nan">'.__('Can not be certified','kiwiz-invoices-certification-pdf-file').'</span>';
            if ( ! Kiwiz::is_kiwiz_plugin_activate() ) {
                $content .= '<span class="certification certification-infos"> (' . __('Subscribe to <a href="https://www.kiwiz.io/prix" target="_blank">Kiwiz</a> to activate the certification', 'kiwiz-invoices-certification-pdf-file') . ')</span>';
            }
        }
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }
}
new KIWIZ_Order_Access();
