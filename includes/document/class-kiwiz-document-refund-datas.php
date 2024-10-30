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
 * Class Kiwiz_Refund_Datas
 */

class Kiwiz_Refund_Datas {

    /**
     * Return all userfull refund datas to certify document
     * @param $refund_id
     * @return stdClass
     */
    static function get_refund_datas( $refund_id ){

        $refund      = new WC_Order_Refund($refund_id);
        $refund_data = $refund->get_data();

        $order_id = get_post($refund_id)->post_parent;
        $order    = new WC_Order($order_id);

        $document       = new Kiwiz_Document_Refund( $refund_id );
        $refund_object  = new stdClass();

        //number
        $date           = $refund->get_date_created();
        $tva            = abs($refund_data['total_tax']);
        $total_refunded = $refund_data['amount'];
        $total_excl_tax = $total_refunded - $tva;

        $refund_object->increment_id            = $document->get_increment_id();
        $refund_object->date                    = $date->date('Y-m-d H:i:s');
        $refund_object->grand_total_excl_tax    = number_format($total_excl_tax, wc_get_price_decimals(), '.', '');
        $refund_object->grand_total_tax_amount  = Kiwiz_Tax_Datas::get_tax_datas( $order_id,'grand_total_refund', array('refund_id' => $refund_id));
        $refund_object->email                   = $order->get_billing_email();

        return $refund_object;

    }
}