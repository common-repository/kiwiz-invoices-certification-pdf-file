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
 * Admin Invoice List
 */
defined( 'ABSPATH' ) || exit;

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Kiwiz_Invoice_List
 */

class Kiwiz_Invoice_List extends WP_List_Table {

    private $_is_kiwiz_activate;

    function __construct(){
        $this->_is_kiwiz_activate = Kiwiz::is_kiwiz_plugin_activate();
        parent::__construct( array(
            'singular'  => __('Invoices list', 'kiwiz-invoices-certification-pdf-file'),
            'plural'    => __('Invoices list', 'kiwiz-invoices-certification-pdf-file'),
            'ajax'      =>  false
        ) );
    }

    /**
     * get filter settings
     *
     * @return $options
     */
    public static function get_settings(){
        $options = get_option('kiwiz_invoice_list_settings', array());
        return $options;
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $settings   = array_map('sanitize_text_field', (array) self::get_settings());

        $columns    = $this->get_columns();
        $hidden     = $this->get_hidden_columns();
        $sortable   = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $per_page = isset($settings["limit"]) ? $settings["limit"] : 20;
        if(isset($_GET['limit'])){
            $per_page = wp_unslash($_GET['limit']);
        }
        $status         = isset($settings['status']) ? $settings['status'] : 'all';
        $start_date     = isset($settings['start_date']) ? $settings['start_date'] : '';
        $end_date       = isset($settings['end_date']) ? $settings['end_date'] : '';
        $date_format    = isset($settings['date_format']) ? $settings['date_format'] : '';
        $order          = isset($settings['order']) ? $settings['order'] : 'p.ID ASC';
        $current_page   = $this->get_pagenum();

        $resultats = $this->get_items($current_page, $per_page, array(
            'status'        => $status,
            'start_date'    => $start_date,
            'end_date'      => $end_date,
            'date_format'   => $date_format,
            'order'         => $order,
        ) );

        if ( is_string($resultats) ){
            $this->set_pagination_args( array(
                'total_items' => 0,
                'total_pages' => 0,
                'per_page'    => 0
            ));

            $this->items = null;
        } else {
            $this->set_pagination_args( array(
                'total_items' => $resultats['total'],
                'total_pages' => $resultats['pages'],
                'per_page'    => $per_page
            ));
            $this->items = $resultats['items'];
        }
    }

    function get_items($page, $limit, $args = array(), $found_rows = true){
        global $wpdb;

        //prepare sql request
        $sql_args = array();

        $join = '';
        $where = " WHERE p.post_type = 'shop_order' ";

        if ( isset($args['start_date']) && !empty($args['start_date']) ){
            if ( $args['start_date'] != '' ) {
                $dateTime = DateTime::createFromFormat($args['date_format'], $args['start_date']);
                $dateTime->setTime(0,0,0);
                $ts = $dateTime->format('U');
                $where .= " AND pmdb.meta_value >= %s ";
                $sql_args[] = esc_sql($ts);
            }
        }

        if ( isset($args['end_date']) && !empty($args['end_date']) ){
            if ( $args['end_date'] != '' ){
                $dateTime = DateTime::createFromFormat($args['date_format'], $args['end_date']);
                $dateTime->setTime(23,59,59);
                $ts = $dateTime->format('U');
                $where .= " AND pmdf.meta_value <= %s ";
                $sql_args[] = esc_sql($ts);
            }
        }

        if ( isset($args['status']) && !empty($args['status']) ){
            if ( $args['status'] != 'all' ){
                if ( $args['status'] == 'nan' ){
                    $where .= " AND ( pmstat.meta_value = 'nan' OR pmstat.meta_value IS NULL ) ";
                } else {
                    $where .= " AND pmstat.meta_value = %s ";
                    $sql_args[] = esc_sql($args['status']);
                }
            }
        }

        $groupby = " GROUP BY p.ID";

        $orderby = " ORDER BY ".$args['order'];

        $field = 'p.ID';
        if ( $found_rows ){
            $field = 'SQL_CALC_FOUND_ROWS *';
        }

        $field .= " ,pmnf.meta_value as document_num, pmdb.meta_value as document_date, pmdc.meta_value as document_status ";

        $sql = "SELECT
                  {$field}
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
                INNER JOIN {$wpdb->postmeta} AS pmnf ON p.ID = pmnf.post_id AND pmnf.meta_key = '_kiwiz_invoice_increment_id'
                INNER JOIN {$wpdb->postmeta} AS pmdb ON p.ID = pmdb.post_id AND pmdb.meta_key = '_kiwiz_invoice_date'
                INNER JOIN {$wpdb->postmeta} AS pmdf ON p.ID = pmdf.post_id AND pmdf.meta_key = '_kiwiz_invoice_date'
                INNER JOIN {$wpdb->postmeta} AS pmdc ON p.ID = pmdc.post_id AND pmdc.meta_key = '_kiwiz_invoice_certify'
                LEFT JOIN {$wpdb->postmeta} AS pmstat ON p.ID = pmstat.post_id AND pmstat.meta_key = '_kiwiz_invoice_certify'
                {$join}
                {$where}
                {$groupby}
                {$orderby}";

        if ( $limit != -1 ){
            $offset = ($page -1) * $limit;
            $sql .= " LIMIT %d, %d";
            $sql_args[] = $offset;
            $sql_args[] = $limit;
        }

        if ( count($sql_args) > 0 )
            $items = $wpdb->get_results( $wpdb->prepare($sql, $sql_args),'ARRAY_A' );
        else
            $items = $wpdb->get_results( $sql,'ARRAY_A'  );

        $total = $wpdb->get_var('SELECT FOUND_ROWS();');

        return array(
            'total' => $total,
            'pages' => ceil($total/$limit),
            'items' => $items,
        );
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'document_num'      => __('Invoices N°', 'kiwiz-invoices-certification-pdf-file'),
            'ID'                => __('Order Id', 'kiwiz-invoices-certification-pdf-file'),
            'document_date'     => __('Invoices date', 'kiwiz-invoices-certification-pdf-file'),
            'document_status'   => __('Kiwiz status', 'kiwiz-invoices-certification-pdf-file'),
            'action'            => __('Actions', 'kiwiz-invoices-certification-pdf-file'),
        );

        return $columns;
    }
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array();
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'ID':
                return '<a href="' .get_edit_post_link( $item[ $column_name ]). '" target="_blank">' .esc_html($item[ $column_name ]). '</a>';
                break;
            case 'document_date':
                return date(Kiwiz_Document_Certify::get_document_settings('shop_date_format'), $item[ $column_name ]);
                break;
            case "document_status":
                if ( $item[ $column_name ] == 'certify')
                    return '<a class="kiwiz-list-grid dashicons-before dashicons-yes status-certify" onclick="return false;" href="#" title="File Hash : ' . esc_attr(Kiwiz_Document_Certify::get_document_kiwiz_datas( $item['ID'], 'file_hash', 'invoice' )) . "\n" . 'Block Hash : ' . esc_attr(Kiwiz_Document_Certify::get_document_kiwiz_datas( $item['ID'], 'block_hash', 'invoice' )) . '"><strong>'.__('Certified','kiwiz-invoices-certification-pdf-file').'</strong></a>';
                if ( $item[ $column_name ] == 'no certify')
                    return '<div class="kiwiz-list-grid dashicons-before dashicons-no status-no-certify"><strong>'.__('No certified', 'kiwiz-invoices-certification-pdf-file').'</strong></div>';
                else
                    return '<div class="kiwiz-list-grid dashicons-before dashicons-no status-nan">'.__("Can not be certified", 'kiwiz-invoices-certification-pdf-file').'</div>';
                break;
            case 'action':
                ob_start();
                ?>
                <div id="action-box">
                    <div class="document-information" style="clear: both;">
                        <?php
                        if ( $item[ 'document_status' ] != 'nan' ) { ?>
                            <?php if ( $this->_is_kiwiz_activate && $item[ 'document_status' ] == 'certify' ) { ?>
                                <a class="button tips display_document"
                                   onclick="return manage_kiwiz_document('<?php echo esc_js(Kiwiz_Document_Certify::KIWIZ_GET_DOCUMENT_ACTION) ?>', 'invoice', '<?php echo esc_js($item['ID']) ?>', '<?php echo esc_js(Kiwiz::get_wp_nonce()) ?>')"><?php echo __("Display", 'kiwiz-invoices-certification-pdf-file'); ?></a>
                            <?php } else if ( $item[ 'document_status' ] == 'no certify' ) { ?>
                                <a class="button tips display_document"
                                   onclick="return manage_kiwiz_document('<?php echo esc_js(Kiwiz_Document_Certify::KIWIZ_GET_DOCUMENT_ACTION) ?>', 'invoice', '<?php echo esc_js($item['ID']) ?>', '<?php echo esc_js(Kiwiz::get_wp_nonce()) ?>')"><?php echo __("Display", 'kiwiz-invoices-certification-pdf-file'); ?></a>
                                <?php if (!Kiwiz_Document_Certify::is_certified_document($item['ID'], 'invoice') && $this->_is_kiwiz_activate) { ?>
                                    <a class="button tips create_certify_document"
                                       onclick="return manage_kiwiz_document('<?php echo esc_js(Kiwiz_Document_Certify::KIWIZ_CERTIFY_DOCUMENT_ACTION) ?>', 'invoice', '<?php echo esc_js($item['ID']) ?>', '<?php echo esc_js(Kiwiz::get_wp_nonce()) ?>')"><?php echo __("Send to Kiwiz", 'kiwiz-invoices-certification-pdf-file'); ?></a>
                                    <?php
                                }
                            }
                        } else { ?>
                          <a class="button tips display_document" onclick="return manage_kiwiz_document('<?php echo esc_js(Kiwiz_Document_Certify::KIWIZ_GET_DOCUMENT_ACTION) ?>', 'invoice', '<?php echo esc_js($item['ID']) ?>', '<?php echo esc_js(Kiwiz::get_wp_nonce()) ?>')"><?php echo __( "Display", 'kiwiz-invoices-certification-pdf-file' ); ?></a>
                        <?php }
                        ?>
                    </div>
                </div>

                <?php
                return ob_get_clean();
                break;
            default:
                return esc_html($item[ $column_name ]);
                break;
        }

        return 'no data';
    }
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'id';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }

    /**
     * It takes a date string and a format string, and returns true if the date string matches the
     * format string
     *
     * @param date The date you want to validate.
     * @param format The format of the date you're trying to validate.
     */
    private static function validate_date($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }


    /**
     * It takes an array of data, and returns an array of data.
     *
     * @param data The data to be validated.
     *
     * @return an array of validated data.
     */
    private static function validate_user_filter_data($data)
    {

        $validated_data = [];
        $date_format = $data['date_format'];
        $allowOrderBy = ['p.ID ASC', 'p.ID DESC', 'pmnf.meta_value ASC', 'pmnf.meta_value DESC', 'pmdb.meta_value ASC', 'pmdb.meta_value DESC'];

        foreach ($data as $key => $value) {
            $value = trim($value);

            switch ($key) {

                case 'status':
                    if( in_array($value , ['all' , 'certify' , 'no certify' , 'nan']) )
                    $validated_data[$key] = $value;
                    break;

                case in_array($key, ['limit', 'paged']):
                    if (is_numeric($value)) $validated_data[$key] = $value;
                    break;

                case in_array($key, ['start_date', 'end_date']):
                    if (self::validate_date($value, $date_format))  $validated_data[$key] = $value;
                    break;

                case '_wp_http_referer':
                    $validated_data[$key] = sanitize_url($value);
                    break;

                case 'order':
					$validated_data[$key] = 'p.ID ASC';
                    if (in_array($value, $allowOrderBy)) {
                        $validated_data[$key] = $value;
					}
                    break;

                default:
                    $validated_data[$key] = sanitize_text_field($value);
                    break;
            }
        }

        return $validated_data;
    }

	/**
	 * It takes full file path and out put it.
	 *
	 * @param $file_path
	 * @return void
	 */
	private static function read_export_pdf_file($file_path) {
		header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
		header('Pragma: public');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		// force download dialog
		if (strpos(php_sapi_name(), 'cgi') === false) {
			header('Content-Type: application/force-download',true,200);
			header('Content-Type: application/octet-stream', false, 200);
			header('Content-Type: application/download', false, 200);
			header('Content-Type: application/pdf', false, 200);
		} else {
			header('Content-Type: application/pdf', true, 200);
		}
		// use the Content-Disposition header to supply a recommended filename
		header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
		header('Content-Transfer-Encoding: binary');

		while (ob_get_level()) {
			ob_end_clean();
			ob_clean();
			flush();
		}

		readfile($file_path);
	}

    public static function process_post(){
        if(isset($_POST) && isset($_POST['list_filter_submit'])) {
            $postdata = self::validate_user_filter_data( wp_unslash( (array) $_POST ) );
            update_option('kiwiz_invoice_list_settings', $postdata);
            return array( 'message' => __('Configuration saved', 'kiwiz-invoices-certification-pdf-file') );
        } elseif ( isset($_POST) && isset($_POST['list_export_submit']) ) {
            $kiwiz_list  = new Kiwiz_Invoice_List();
            $settings    = self::get_settings();
            $status      = isset($settings['status']) ? sanitize_text_field($settings['status']) : 'all';
            $start_date  = isset($settings['start_date']) ? sanitize_text_field($settings['start_date']) : '';
            $end_date    = isset($settings['end_date']) ? sanitize_text_field($settings['end_date']) : '';
            $date_format = isset($settings['date_format']) ? sanitize_text_field($settings['date_format']) : '';
            $order       = isset($settings['order']) ? sanitize_text_field($settings['order']) : 'p.ID ASC';
            $page        = 1;
            $limit       = -1;

            $results = $kiwiz_list->get_items(
                $page,
                $limit,
                array(
                    'status'      => $status,
                    'start_date'  => $start_date,
                    'end_date'    => $end_date,
                    'date_format' => $date_format,
                    'order'       => $order,
                ),
                false
            );

            if ( isset($results['items']) && !empty($results['items']) ) {
                $pdfs = array();
                foreach ( $results['items'] as $item ){

                    $document = new Kiwiz_Document_Invoice($item['ID']);
                    $full_path = null;

                    switch ( $document->get_document_status() ) {
                        case 'nan':
                        case 'no certify':
                            $full_path = KIWIZ_DOCUMENT_DIR . $document->get_document_type() .'/'.  $document->get_document_name();
                            break;
                        case 'certify':
                            //recall api to get invoice
                            $certify_document = new Kiwiz_Document_Certify();
                            if ( $certify_document->is_certified_document($item['ID'], 'invoice') && $kiwiz_list->_is_kiwiz_activate ) {
                                $certify_document->get_certify_document($document);
                                $full_path = KIWIZ_DOCUMENT_DIR . $document->get_document_type() .'/'.  $document->get_document_name();
                            }
                            break;
                    }

                    if ( $full_path != null && file_exists($full_path) )
                        $pdfs[] = $full_path;

                }

                if ( count($pdfs) > 0 ) {
                    $pdf_export = new Kiwiz_Concat_Pdf();
                    $pdf_export->setFiles($pdfs);
                    $pdf_export->concat();
                    $file_export_name = KIWIZ_DOCUMENT_DIR . 'invoice/documents_invoice_' . date("Ymd_His") . ".pdf";
                    $pdf_export->Output($file_export_name, "F");

					self::read_export_pdf_file($file_export_name);
                    die;
                } else {
                    return array( 'message' => __('No documents found', 'kiwiz-invoices-certification-pdf-file'), 'message-css' => 'notice below-h2 notice-warning ' );
                }
            }

        }
        return;
    }

}
