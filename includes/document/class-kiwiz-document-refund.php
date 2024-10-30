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
 * Class Kiwiz_Document_Refund
 */

class Kiwiz_Document_Refund extends Kiwiz_Document {

    CONST INCREMENT_ID_META_KEY = '_kiwiz_refund_increment_id';
    CONST FILENAME_META_KEY     = '_kiwiz_refund_filename';
    CONST DATE_META_KEY         = '_kiwiz_refund_date';
    CONST CERTIFY_META_KEY      = '_kiwiz_refund_certify';
    protected $_document_number;
    protected $_document_name;
    protected $_document_date;
    protected $_document_type;
    protected $_order;
    protected $_refund;
    private $_template_dir;
    protected $error = null;

    public function __construct($refund_id)
    {
        $this->_order  = $this->set_order($refund_id);
        $this->_refund = new WC_Order_Refund($refund_id);
        $this->_document_type   = 'refund';
        $this->_template_dir    = $this->set_template_dir();
        $this->_document_number = $this->set_increment_id();
        $this->_document_name   = $this->set_document_name();
        $this->_document_date   = $this->set_document_date();
    }

    private function set_order( $refund_id ) {
        $order_id = get_post($refund_id)->post_parent;
        return wc_get_order( $order_id );
    }

    public function get_document_type() {
        return $this->_document_type;
    }

    private function set_template_dir() {
        return 'templates/refund';
    }

    public function get_template_dir() {
        return $this->_template_dir;
    }

    private function set_increment_id() {
        $increment_id = get_post_meta( $this->_refund->get_id(), $this::INCREMENT_ID_META_KEY, true );
        if ( $increment_id == '' )
            return null;
        return $increment_id;
    }

    public function get_increment_id() {
        return $this->_document_number;
    }

    private function set_document_date() {
        $document_date = get_post_meta( $this->_refund->get_id(), $this::DATE_META_KEY, true );
        if ( $document_date == '' )
            return time();
        else
            return $document_date;
    }

    public function get_document_date() {
        return $this->_document_date;
    }

    private function set_document_name() {
        $file_name = get_post_meta( $this->_refund->get_id(), $this::FILENAME_META_KEY, true );
        if ( $file_name == '' )
            return md5('refund_' . $this->_document_number. time() ).'.pdf';
        else
            return $file_name;
    }

    public function get_document_name() {
        return $this->_document_name;
    }

    public function get_document_status() {
        $document_status = get_post_meta( $this->_refund->get_id(), $this::CERTIFY_META_KEY, true );
        if ( $document_status != '' )
            return $document_status;
        else
            return 'nan';
    }

    /**
     * Set wp error message
     * @param $error
     */
    private function set_error( $error ) {
        $this->error = $error;
    }

    /**
     * Add body action
     */
    public function get_content_document() {
        add_action('kiwiz_refund_body', array( $this, 'add_content' ) );
    }

    /**
     * Add document content
     */
    public function add_content() {
        add_action('kiwiz_document_refund_shop_logo',          array( $this, 'add_shop_logo' ) );
        add_action('kiwiz_document_refund_shop_info',          array( $this, 'add_shop_info' ) );
        add_action('kiwiz_document_refund_shop_header',        array( $this, 'add_shop_header' ) );
        add_action('kiwiz_document_refund_header',             array( $this, 'add_header' ) );
        add_action('kiwiz_document_invoice_details',           array( $this, 'add_details' ) );
        add_action('kiwiz_document_refund_address',            array( $this, 'add_address' ) );
        add_action('kiwiz_document_refund_shipping_address',   array( $this, 'add_shipping_address' ) );
        add_action('kiwiz_document_refund_items',              array( $this, 'add_items' ) );

        wc_get_template( 'body.php', null, KIWIZ_PLUGIN_PATH.$this->_template_dir.'/', KIWIZ_PLUGIN_PATH.$this->_template_dir.'/' );
    }

    /**
     * Returns html code about the logo
     */
    public function add_shop_logo() {
        $shop_logo = $this->get_document_option('shop_pdf_logo');
        $logo = '<div class="shop-logo">';
        if ( isset( $shop_logo ) && $shop_logo != '' ) {
            $logo_img = $this->compress_image( $shop_logo, 'logo-document-kiwiz' );
            if ( $logo_img != null )
                $logo .= '<img src="' . esc_url($logo_img) . '">';
        }
        $logo .= '</div>';
        echo wp_kses_post($logo);
    }

    /**
     * Returns html code about the shop informations
     */
    public function add_shop_info() {
        $shop_pdf_name    = $this->get_document_option( ('shop_pdf_name') );
        $shop_pdf_address = $this->get_document_option( ('shop_pdf_address') );

        if ( ! isset( $shop_pdf_name ) && ! isset( $shop_pdf_address ) ) {
            return;
        }
        if ( isset( $shop_pdf_name ) ) {
            echo '<div class="shop-name">' . esc_html($shop_pdf_name) . '</div>';
        }
        if ( isset ( $shop_pdf_address ) ) {
            echo '<div class="shop-address" > ' . nl2br(esc_html($shop_pdf_address)) . '</div> ';
        }
    }

    /**
     * Returns html code about custom shop header
     */
    public function add_shop_header() {
        $shop_pdf_header = $this->get_document_option( ('shop_pdf_header') );
        if ( ! isset( $shop_pdf_header ) || $shop_pdf_header == '' ) {
            return;
        }
        echo ' <tr><td class="document-header-data">' . esc_html($shop_pdf_header) . '</td></tr>';
    }

    /**
     * Returns custom shop footer value
     */
    public function get_shop_footer() {
        return $this->get_document_option( ('shop_pdf_footer') );
    }

    /**
     * Returns html code about refund and the order informations
     */
    public function add_header() {
        echo '<table>
            <tr class="document-number">
                <td>' . __( "Refund n°", 'kiwiz-invoices-certification-pdf-file' ) .' : '. esc_html($this->_document_number) .'</td>
                <td>' .__( "Order n°", 'kiwiz-invoices-certification-pdf-file' ) .' : '. esc_html($this->_order->get_id()) .'</td>
               
            </tr>
            <tr class="document-date">
                <td>' . __( "Refund date", 'kiwiz-invoices-certification-pdf-file' ) .' : '. esc_html(date( parent::get_document_settings('shop_date_format'), $this->_document_date)) .'</td>
                <td>' .__( "Order date", 'kiwiz-invoices-certification-pdf-file' ) .' : '. esc_html(wc_format_datetime( $this->_order->get_date_created(), parent::get_document_settings('shop_date_format') )) .'</td>
            </tr>
        </table>';
    }

    /**
     * Returns html code about invoice shipping and payment method
     */
    public function add_details() {
        echo '<table>
            <tr>
                <td>' .__( "Payment method", 'kiwiz-invoices-certification-pdf-file' ) .' : '. esc_html($this->_order->get_payment_method_title()) .'</td>
            </tr>';

        $shipping_items = $this->_order->get_items('shipping');
        if ( count($shipping_items) > 0 ) {
            $shipping_methods = WC()->shipping->get_shipping_methods();
            foreach( $shipping_items as $item_id => $item ) {
                foreach ( $shipping_methods as $method ) {
                    if (  $method->id == $item->get_method_id() ) {
                        $method_title = ( $item->get_method_title() != '') ? $item->get_method_title() : $method->method_title;
                        echo '<tr>
                            <td>' . __("Shipping method", 'kiwiz-invoices-certification-pdf-file') . ' : ' . esc_html($method_title) . '</td>
                        </tr>';
                    }
                }
            }
        }

        echo '</table>';
    }

    /**
     * Returns html code about invoice address
     */
    public function add_address() {
        echo '<div class="refund-address" > ';
        if ( $this->_order->get_formatted_billing_address() ) {
            echo '<div class="title"> ' . __( "Invoice address", 'kiwiz-invoices-certification-pdf-file' ) . '</div >' .
                wp_kses( $this->_order->get_formatted_billing_address (), array( "br" => array() ) );
        }
        echo '</div> ';
    }

    /**
     * Returns html code about shipping address
     */
    public function add_shipping_address() {
        echo '<div class="shipping-address" > ';
        if ( $this->_order->get_formatted_shipping_address() ) {
            echo '<div class="title"> ' . __( "Shipping address", 'kiwiz-invoices-certification-pdf-file' ) . '</div >' .
                wp_kses( $this->_order->get_formatted_shipping_address(), array( "br" => array() ) );
        }
        echo '</div> ';
    }

    /**
     * Add actions about order items and totals
     */
    public function add_items() {
        add_action('kiwiz_document_refund_order_items', array( $this, 'add_refund_items' ));
        wc_get_template( 'items.php', null, KIWIZ_PLUGIN_PATH.$this->_template_dir.'/', KIWIZ_PLUGIN_PATH.$this->_template_dir.'/' );

        add_action('kiwiz_document_refund_order_totals', array( $this, 'add_refund_totals' ));
        wc_get_template( 'totals.php', null, KIWIZ_PLUGIN_PATH.$this->_template_dir.'/', KIWIZ_PLUGIN_PATH.$this->_template_dir.'/' );
    }

    /**
     * Returns the html code of the header of the table containing the items of the order
     */
    public function get_items_table_head() {
        return '<tr class="thead">
            <td>'.  __( 'Products',   'kiwiz-invoices-certification-pdf-file' ) .'</td>
            <td>'.  __( 'Sku',        'kiwiz-invoices-certification-pdf-file' ) .'</td>
            <td class="ta-right">'.  __( 'Price',      'kiwiz-invoices-certification-pdf-file' ) .'</td>
            <td class="ta-right">'.  __( 'Qty',        'kiwiz-invoices-certification-pdf-file' ) .'</td>
            <td class="ta-right">'.  __( 'Tax',       'kiwiz-invoices-certification-pdf-file' ) .'</td>
            <td class="ta-right">'.  __( 'Subtotal',   'kiwiz-invoices-certification-pdf-file' ) .'</td>
        </tr>';
    }

    /**
     * Returns html code about order items
     */
    public function add_refund_items() {
        $items = $this->_refund->get_items();
        $table_break = 14;
        $row_count = 1;
        $close_table = false;

        if ( count($items) > 0 ) {
            foreach ($items as $item) {
                if ($row_count == 1) {
                    echo '<table class="document-item">';
                    echo wp_kses_post($this->get_items_table_head());
                    $close_table = true;
                }

                $product = wc_get_product($item->get_product_id());
                $quantity = $item->get_quantity() ? $item->get_quantity() : 1;

                echo '<tr class="tbody">
                    <td class="product_name">' . esc_html($this->format_item_value( $item->get_name() )). '</td>
                    <td>' . esc_html($this->format_item_value( $product->get_sku() )) . '</td>
                    <td class="ta-right">' . wp_kses_post($this->format_item_value( wc_price( abs(wc_round_tax_total(abs($item->get_subtotal()) / abs($quantity))) ) )) . '</td>
                    <td class="ta-right">' . esc_html($this->format_item_value( abs($quantity) )) . '</td>                
                    <td class="ta-right">' . wp_kses_post($this->format_item_value(wc_price(abs($item->get_subtotal_tax())))) . '</td>
                    <td class="ta-right">' . wp_kses_post($this->format_item_value(wc_price(abs(wc_round_tax_total($item->get_subtotal() + $item->get_subtotal_tax()))))) . '</td>
                </tr>';

                if ( $row_count < $table_break ) {
                    $product_nb_lines = round(strlen($item->get_name()) / 30 );
                    if ( $product_nb_lines <= 1 )
                        $row_count ++;
                    else
                        $row_count += $product_nb_lines;
                }
                else {
                    $row_count = 1;
                    $close_table = false;
                    echo '</table>
                    <div class="page_break"></div>';
                }
            }

            //Add fees
            foreach( $this->_refund->get_items('fee') as $item_id => $item_fee ) {
                if ($row_count == 1) {
                    echo '<table class="document-item">';
                    echo wp_kses_post($this->get_items_table_head());
                    $close_table = true;
                }

                echo '<tr class="tbody">
                    <td class="product_name">' . __( "Fee", 'kiwiz-invoices-certification-pdf-file' ) . '</td>
                    <td>&nbsp;</td>
                    <td class="ta-right">' . wp_kses_post($this->format_item_value( wc_price( abs($item_fee->get_amount()) ) )) . '</td>
                    <td class="ta-right">&nbsp;</td>                
                    <td class="ta-right">' .wp_kses_post($this->format_item_value( wc_price( abs(wc_round_tax_total($item_fee->get_total_tax())) ) )).'</td>
                    <td class="ta-right">' . wp_kses_post($this->format_item_value( wc_price( abs($item_fee->get_total() + $item_fee->get_total_tax()) ) )) . '</td>
                </tr>';

                if ( $row_count < $table_break )
                        $row_count ++;
                else {
                    $row_count = 1;
                    $close_table = false;
                    echo '</table>
                    <div class="page_break"></div>';
                }
            }

            if ( $close_table )
                echo '</table>';
        } else {
            echo '<table class="document-item">';
            echo wp_kses_post($this->get_items_table_head());
            $refund_name =  __('Refund', 'kiwiz-invoices-certification-pdf-file');//( $this->_refund->get_reason() != '' ) ? $this->_refund->get_reason() : __('Refund', 'kiwiz-invoices-certification-pdf-file');
            echo '<tr class="tbody">
                    <td class="product_name">' . wp_kses_post($this->format_item_value( $refund_name )) . '</td>
                    <td>&nbsp;</td>
                    <td class="ta-right">' . wp_kses_post($this->format_item_value( wc_price( abs(wc_round_tax_total($this->_refund->get_total() - $this->_refund->get_total_tax())) ) )) . '</td>
                    <td class="ta-right">&nbsp;</td>                
                    <td class="ta-right">&nbsp;</td>
                    <td class="ta-right">' . wp_kses_post($this->format_item_value( wc_price( abs($this->_refund->get_total()) ) )) . '</td>
                </tr>';
            echo '</table>';
        }
    }

    /**
     * Format item value
     */
    public function format_item_value( $item_value ) {
        return ($item_value != '') ? $item_value : '&nbsp;';
    }

    /**
     * Returns html code about order totals
     */
    public function add_refund_totals() {

        if ( get_option ( "woocommerce_calc_taxes") == "yes"  ) {
            $tax_block = '';
            $total_tax = 0;

            $refund_totals = $this->_refund->get_tax_totals();
            if ( count($refund_totals) > 0 ) {
                foreach ( $refund_totals as $code => $tax ) {
                    $tax_block .= '<tr class="tax-rates">
                            <td class="column-product">' . esc_html($tax->label) . ':</td>
                            <td class="column-total">' . wc_price( abs(wc_round_tax_total( $tax->amount)) ) . '</td>
                        </tr>';
                    $total_tax += $tax->amount;
                }
            }
            $refund_subtotal = abs(wc_round_tax_total($this->_refund->get_total() - $this->_refund->get_total_tax()));
            $refund_subtotal_inc_tax = abs($this->_refund->get_total());
        }

        echo '<tr class="subtotal total-subtitle">
                <td class="column-product">' . __( "Subtotal Excl. Tax", 'kiwiz-invoices-certification-pdf-file' ) . ':</td>
                <td class="column-total">' . wc_price( $refund_subtotal ) . '</td>
            </tr>';

        if ( $tax_block != '' ) {
            echo '<tr class="tax-rates total-subtitle">
                    <td class="column-product">' . __( "Tax Total", 'kiwiz-invoices-certification-pdf-file' ) . ':</td>
                    <td class="column-total">' . wc_price(abs($total_tax)) . '</td>
                </tr>';
            echo wp_kses_post($tax_block);
        }

        if ( abs($this->_refund->get_shipping_total()) > 0 ) {
            echo '<tr class="shipping">
                    <td class="column-product">' . __( "Shipping", 'kiwiz-invoices-certification-pdf-file' ) .':</td>
                    <td class="column-total">' . wc_price( abs($this->_refund->get_shipping_total() + $this->_refund->get_shipping_tax()) ) . '</td>
                </tr>';
        }

        echo '<tr class="total">
                <td class="column-product">' . __( "Total", 'kiwiz-invoices-certification-pdf-file' ) .':</td>
                <td class="column-total">' . wc_price( abs($this->_refund->get_total()) ) . '</td>
            </tr>';
    }

    /**
     * Add header action
     */
    public function get_header_document() {
        add_action('kiwiz_refund_head', array( $this, 'add_style' ) );
    }
    /**
     * Add document style
     */
    public function add_style() {
        //$style_file = KIWIZ_PLUGIN_PATH. $this->_template_dir . '/'. 'style.css';
        $style_file = KIWIZ_PLUGIN_URL. $this->_template_dir . '/'. 'style.css';
//        echo '<link rel="stylesheet" type="text/css" href="' . esc_url($style_file) . '">';
		wp_enqueue_style('kiwiz-refund-dompdf-styles', $style_file);    }

    /**
     * Prepare datas for the creation of the pdf
     */
    protected function create_document() {

        global $wpdb;
        $wpdb->query('START TRANSACTION');

        //set increment id
        $result                 = $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s FOR UPDATE", KIWIZ_DOCUMENT_REFUND_INCREMENT_ID_OPTION_NAME), ARRAY_A );
        $this->_document_number = $result[0]['option_value'];
        $this->get_header_document();
        $this->get_content_document();

        ob_start();
        wc_get_template( 'main.php', null, KIWIZ_PLUGIN_PATH.$this->_template_dir.'/', KIWIZ_PLUGIN_PATH.$this->_template_dir.'/' );
        $document = ob_get_contents();
        ob_end_clean();

        $this->generate_pdf_file($document, 'refund', $this->_document_name);
        $this->flush_template();

        //save document datas
        $this->save_refund_datas();

        //update main increment id
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s", ($this->_document_number + 1), KIWIZ_DOCUMENT_REFUND_INCREMENT_ID_OPTION_NAME));

        $wpdb->query('COMMIT');
    }

    /**
     * Save post datas about pdf document
     */
    protected function save_refund_datas() {
        add_post_meta($this->_refund->get_id(), $this::INCREMENT_ID_META_KEY, $this->_document_number, true);
        add_post_meta($this->_refund->get_id(), $this::FILENAME_META_KEY, $this->_document_name, true);
        add_post_meta($this->_refund->get_id(), $this::DATE_META_KEY, $this->_document_date, true);
        add_post_meta($this->_refund->get_id(), $this::CERTIFY_META_KEY, 'nan', true);
    }

    /**
     * Reset all post metas datas about the document
     */
    public function reset_document() {
        parent::remove_document( $this->_refund->get_id(), 'refund', $this->_document_name, 'refund' );
        delete_post_meta($this->_refund->get_id(), $this::INCREMENT_ID_META_KEY);
        delete_post_meta($this->_refund->get_id(), $this::FILENAME_META_KEY);
        delete_post_meta($this->_refund->get_id(), $this::DATE_META_KEY);
        delete_post_meta($this->_refund->get_id(), $this::CERTIFY_META_KEY);
    }

    /**
     * Clear all filters
     */
    public function flush_template() {
        remove_all_filters('kiwiz_document_refund_shop_logo');
        remove_all_filters('kiwiz_document_refund_shop_info');
        remove_all_filters('kiwiz_document_refund_shop_header');
        remove_all_filters('kiwiz_document_invoice_details');
        remove_all_filters('kiwiz_document_refund_header');
        remove_all_filters('kiwiz_document_refund_address');
        remove_all_filters('kiwiz_document_refund_shipping_address');
        remove_all_filters('kiwiz_document_refund_items');
        remove_all_filters('kiwiz_refund_body');
        remove_all_filters('kiwiz_document_refund_order_items');
        remove_all_filters('kiwiz_document_refund_order_totals');
        remove_all_filters('kiwiz_refund_head');
    }

    /**
     * Call parent actions dispatcher
     * @param $action
     * @return array
     */
    public function dispatch_action( $action ) {
        return parent::dispatch_action($action);
    }


    /**
     * Returns all the refunds associated with an order
     * @param $order_id
     * @return array|null
     */
    static public function get_order_refund( $order_id ) {
        $order   = wc_get_order( $order_id );
        $refunds = $order->get_refunds();
        if ( count($refunds) > 0 )
            return $refunds;
        return null;
    }

    /**
     * Returns the last order's refund
     * @param $order_id
     * @return array|null
     */
    static public function get_last_order_refund( $order_id ) {
        $order       = wc_get_order( $order_id );
        $refunds     = $order->get_refunds();
        $last_refund = null;
        if ( count($refunds) > 0 ) {
            $max_id = 0;
            foreach ( $refunds as $refund ) {
                if ( $refund->get_id() > $max_id ) {
                    $last_refund = $refund;
                    $max_id      = $refund->get_id();
                }

            }
        }
        return $last_refund;
    }

    /**
     * Return document content
     */
    public function get_document_content( $get_certify = true ) {
        return parent::get_document_content( $get_certify );
    }
}
