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
 * Class Kiwiz_Tax_Datas
 */

class Kiwiz_Tax_Datas {

    /**
     * Return all userfull tax datas to certify document
     * @param $order_id
     * @return stdClass
     */
    static function get_tax_datas( $order_id, $tax_type, $datas = null ) {
        $order      = new WC_Order($order_id);
        $tax_object = new stdClass();

        switch ( $tax_type ) {
            case 'shipping':
                $shipping_taxes = array();
                $tax_labels = self::get_tax_infos( $order, true );
                foreach ( $order->get_items('shipping') as $item_id => $item ) {
                    $taxes = $item->get_taxes();
                    foreach ( $taxes['total'] as $rate_id => $shipping_tax ) {
                        $shipping_tax_object = new stdClass();
                        $shipping_tax_object->tax_name  = (count($tax_labels) > 0) ? $tax_labels[$rate_id] : '';
                        $shipping_tax_object->tax_value = ($shipping_tax>0) ? number_format($shipping_tax,wc_get_price_decimals(), '.', '') : '0.00';
                        $shipping_taxes[] = $shipping_tax_object;
                    }
                }
                $tax_object = $shipping_taxes;
                break;

            case 'item':
                $item_taxes = array();
                if ( $datas !== null && isset($datas['item_id']) ) {
                    $tax_labels  = self::get_tax_infos( $order );
                    $item        = $order->get_item( $datas['item_id'] );
                    $taxes       = $item->get_taxes();

                    if(isset($taxes['subtotal'])){
                        $array_taxes = $taxes['subtotal'];
                    } else {
                        $array_taxes = ( isset($taxes['total']) ) ? $taxes['total'] : array();
                    }

                    foreach ( $array_taxes as $rate_id => $item_tax ) {
                        $item_tax_object = new stdClass();
                        $item_tax_object->tax_name  =(count($tax_labels) > 0) ? $tax_labels[$rate_id] : '';
                        $item_tax_object->tax_value = ($item_tax>0) ? number_format($item_tax,wc_get_price_decimals(), '.', '') : '0.00';
                        $item_taxes[] = $item_tax_object;
                    }
                }
                $tax_object = $item_taxes;
                break;

            case 'grand_total':
                $grand_total_taxes = array();
                foreach ( $order->get_tax_totals() as $code => $tax ) {
                    $grand_total_tax_object = new stdClass();
                    $grand_total_tax_object->tax_name  = $tax->label;
                    $grand_total_tax_object->tax_value = number_format($tax->amount,wc_get_price_decimals(), '.', '');
                    $grand_total_taxes[] = $grand_total_tax_object;
                }
                $tax_object = $grand_total_taxes;
                break;

            case 'grand_total_refund':
                $grand_total_taxes = array();
                if ( $datas !== null && isset($datas['refund_id']) ) {
                    $refund = new WC_Order_Refund($datas['refund_id']);
                    foreach ( $refund->get_tax_totals() as $code => $tax ) {
                        $grand_total_tax_object = new stdClass();
                        $grand_total_tax_object->tax_name  = $tax->label;
                        $grand_total_tax_object->tax_value = number_format(abs(wc_round_tax_total( $tax->amount)),wc_get_price_decimals(), '.', '');
                        $grand_total_taxes[] = $grand_total_tax_object;
                    }
                }
                $tax_object = $grand_total_taxes;
                break;

            default:
                break;
        }

        return $tax_object;
    }

    /**
     * Get all taxes labels associated to order
     * @param $order
     * @param bool $only_shipping
     * @return array
     */
    static function get_tax_infos( $order, $only_shipping = false ) {
        $tax_labels = array();
        foreach ( $order->get_items( 'tax' ) as $tax_item ) {
            if ( ! $only_shipping ) {
                $tax_labels[ $tax_item->get_rate_id() ] = $tax_item->get_label();
            } else {
                if( ! empty($tax_item->get_shipping_tax_total()) ) {
                    $tax_labels[ $tax_item->get_rate_id() ] = $tax_item->get_label();
                }
            }
        }
        return $tax_labels;
    }

}