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
 * Class Kiwiz_Invoice_Datas
 */

class Kiwiz_Invoice_Datas {

    /**
     * Return all userfull invoice datas to certify document
     * @param $order_id
     * @return stdClass
     */
    static function get_order_datas( $order_id ) {
        $order          = new WC_Order($order_id);
        $kiwiz_invoice  = new Kiwiz_Document_Invoice($order_id);
        $invoice_object = new stdClass();

        //Invoice details
        $invoice_object->increment_id   = $kiwiz_invoice->get_increment_id();
        $date                           = $order->get_date_created();
        $invoice_object->date           = $date->date('Y-m-d H:i:s');
        $invoice_object->email          = $order->get_billing_email();
        $invoice_object->payment_method = $order->get_payment_method();

        //Billing address
        $billing_adresse = new stdClass();
        $billing_adresse->firstname         = $order->get_billing_first_name();
        $billing_adresse->lastname          = $order->get_billing_last_name();
        $billing_adresse->company           = $order->get_billing_company();
        $billing_adresse->street            = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
        $billing_adresse->postcode          = $order->get_billing_postcode();
        $billing_adresse->city              = $order->get_billing_city();
        if (  $order->get_billing_country() != '' )
            $billing_adresse->country_code      = $order->get_billing_country();
        $invoice_object->billing_address    = $billing_adresse;

        //Shipping address
        $send_shipping_adresse_data = true;
        if ( $order->get_shipping_last_name() == '' || $order->get_shipping_address_1() == '' || $order->get_shipping_postcode() == '' || $order->get_shipping_city() == '' ) {
            $send_shipping_adresse_data = false;
        }
        if ( $send_shipping_adresse_data ) {
            $shipping_adresse = new stdClass();
            $shipping_adresse->firstname        = $order->get_shipping_first_name();
            $shipping_adresse->lastname         = $order->get_shipping_last_name();
            $shipping_adresse->company          = $order->get_shipping_company();
            $shipping_adresse->street           = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
            $shipping_adresse->postcode         = $order->get_shipping_postcode();
            $shipping_adresse->city             = $order->get_shipping_city();
            if ( $order->get_shipping_country() != '')
                $shipping_adresse->country_code     = $order->get_shipping_country();
            $invoice_object->shipping_address   = $shipping_adresse;
        }


        //Shipping details
        $invoice_object->shipping_method          = $order->get_shipping_method();
        $invoice_object->shipping_amount_excl_tax = number_format($order->get_shipping_total(),wc_get_price_decimals(), '.', '');
        $invoice_object->shipping_tax_amount      = Kiwiz_Tax_Datas::get_tax_datas( $order_id,'shipping');

        //Order items
        $items = $order->get_items();
        $array_items = array();
        $grand_total_excl_tax = $order->get_shipping_total();
        foreach ( $items as $product_item ){

            $wc_product = $product_item->get_product(); // the WC_Product object
            $item_data  = $product_item->get_data();

            $item       = new stdClass();
            $sku        = $wc_product->get_sku();
            $item->sku  = (isset($sku) && !empty($sku)) ? $sku : $wc_product->get_id(); //if sku is not defined, put the product id

            $item->ean13        = '';
            $item->product_name = $item_data['name'];
            $item->manufacturer = '';
            $item->qty          = $item_data['quantity'];

            $item->row_total_excl_tax   = number_format($item_data['total'],wc_get_price_decimals(), '.', '');
            $item->row_total_tax_amount = Kiwiz_Tax_Datas::get_tax_datas( $order_id,'item', array('item_id' => $product_item->get_id()));

            $array_items[] = $item;

            $grand_total_excl_tax += $item->row_total_excl_tax;
        }

        //Order fees
        $array_fees = array();
        foreach($order->get_items('fee') as $item_id => $item_fee ) {
            $fee       = new stdClass();
            $fee->sku  = 'fee';

            $fee->ean13        = '';
            $fee->product_name = 'fee';
            $fee->manufacturer = '';
            $fee->qty          = 1;

            $fee->row_total_excl_tax   = number_format($item_fee->get_total(),wc_get_price_decimals(), '.', '');
            $fee->row_total_tax_amount = Kiwiz_Tax_Datas::get_tax_datas( $order_id,'item', array('item_id' => $item_id));

            $array_fees[] = $fee;

            $grand_total_excl_tax += $fee->row_total_excl_tax;
        }

        //merge items values
        $invoice_object->items = array_merge($array_items, $array_fees);

        $invoice_object->grand_total_excl_tax   = number_format($grand_total_excl_tax,wc_get_price_decimals(), '.', '');
        $invoice_object->grand_total_tax_amount = Kiwiz_Tax_Datas::get_tax_datas( $order_id,'grand_total');

        return $invoice_object;
    }

}