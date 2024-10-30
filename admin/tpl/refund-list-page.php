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
 * Refund list page
 */
$results  = Kiwiz_Refund_List::process_post();
$settings = array_map('sanitize_text_field', (array) Kiwiz_Refund_List::get_settings());
?>
<div class="kiwiz-listing-factures">
    <div class="wrap">

        <h1><?php echo __('Refund list', 'kiwiz-invoices-certification-pdf-file');?></h1>

        <div id="page-content">

            <?php if( isset($results['message']) && !empty($results['message']) ):?>
                <?php $css = ( isset($results['message-css']) && !empty($results['message-css']) ) ? $results['message-css'] : 'updated  below-h2'?>
                <div id="message" class="<?php echo esc_attr($css); ?>"> 
                    <p><?php echo esc_html($results['message']); ?></p>
                </div>
            <?php endif;?>

            <form method="post" action="">
                <table class="form-table">
                    <tbody>

                    <tr>
                        <th scope="row"><?php echo __('Limit', 'kiwiz-invoices-certification-pdf-file') ?></th>
                        <td>
                            <?php
                            Kiwiz_Admin::render_fields(
                                array(
                                    'type'          => 'select',
                                    'name'          => 'limit',
                                    'required'      => false,
                                    'description'   => '',
                                    'value'         => (isset($settings['limit']) ? $settings['limit'] : 20),
                                    'options'       => array(   10      => '10',
                                                                20      => '20',
                                                                50      => '50',
                                                                100     => '100',
                                                                250     => '250',
                                                                500     => '500',
                                                                1000    => '1000',
                                    )
                                )
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Status', 'kiwiz-invoices-certification-pdf-file') ?></th>
                        <td>
                            <?php
                            Kiwiz_Admin::render_fields(
                                array(
                                    'type'          => 'select',
                                    'name'          => 'status',
                                    'required'      => false,
                                    'description'   => '',
                                    'value'         => (isset($settings['status']) ? $settings['status'] : 'all'),
                                    'options'       => array(   'all'        => __('All', 'kiwiz-invoices-certification-pdf-file'),
                                                                'certify'    => __('Certified', 'kiwiz-invoices-certification-pdf-file'),
                                                                'no certify' => __('No certified', 'kiwiz-invoices-certification-pdf-file'),
                                                                'nan'        => __('Can not be certified', 'kiwiz-invoices-certification-pdf-file'),
                                    )
                                )
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Start Date', 'kiwiz-invoices-certification-pdf-file') ?></th>
                        <td>
                            <?php
                            Kiwiz_Admin::render_fields(
                                array(
                                    'type'          => 'datepicker',
                                    'name'          => 'start_date',
                                    'required'      => false,
                                    'description'   => '',
                                    'value'         => (isset($settings['start_date']) ? $settings['start_date'] : '')
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo __('End Date', 'kiwiz-invoices-certification-pdf-file') ?></th>
                        <td>
                            <?php
                            Kiwiz_Admin::render_fields(
                                array(
                                    'type'          => 'datepicker',
                                    'name'          => 'end_date',
                                    'required'      => false,
                                    'description'   => '',
                                    'value'         => (isset($settings['end_date']) ? $settings['end_date'] : '')
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo __('Order by', 'kiwiz-invoices-certification-pdf-file') ?></th>
                        <td>
                            <?php
                            Kiwiz_Admin::render_fields(
                                array(
                                    'type'          => 'select',
                                    'name'          => 'order',
                                    'required'      => false,
                                    'description'   => '',
                                    'value'         => (isset($settings['order']) ? $settings['order'] : 'p.ID ASC'),
                                    'options'       => array(   'p.ID ASC'              => __('No. ascending order', 'kiwiz-invoices-certification-pdf-file'),
                                                                'p.ID DESC'             => __('No. descending order', 'kiwiz-invoices-certification-pdf-file'),
                                                                'pmnf.meta_value ASC'   => __('No. ascending refund', 'kiwiz-invoices-certification-pdf-file'),
                                                                'pmnf.meta_value DESC'  => __('No. descending refund', 'kiwiz-invoices-certification-pdf-file'),
                                                                'pmdb.meta_value ASC'   => __('Ascending date', 'kiwiz-invoices-certification-pdf-file'),
                                                                'pmdb.meta_value DESC'  => __('Descending date', 'kiwiz-invoices-certification-pdf-file'),
                                    )
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="list_filter_submit" id="submit" class="button button-primary" value="<?php echo __('Save', 'kiwiz-invoices-certification-pdf-file') ?>">
                    <input type="submit" name="list_export_submit" id="submit" class="button button-primary" value="<?php echo __('Export', 'kiwiz-invoices-certification-pdf-file') ?>">
                </p>

                <input type="hidden" name="date_format" value="<?php echo esc_attr(Kiwiz_Document_Certify::get_document_settings('shop_date_format')); ?>" />

                <div id="kiwiz-actions-error-msg"></div>

                <?php
                $listTable = new Kiwiz_Refund_List();
                $listTable->prepare_items();
                $listTable->display();
                $listTable->displayTotal();
                ?>

            </form>

        </div>
    </div>

</div>
<script>
    document.addEventListener("DOMContentLoaded", function(event) {

        function translateDateFormat( format) {
            var defaultFormat = 'dd/mm/yy';
            var convertFormat = {   'm/d/Y' : 'mm/dd/yy',
                'd/m/Y' : 'dd/mm/yy',
                'Y-m-d' : 'yy-mm-dd',
                'j F Y' : defaultFormat
            };
            if ( convertFormat[format] != 'undefined')
                return  convertFormat[format];

            return defaultFormat;
        }

        jQuery('.datepicker').datepicker({
            dateFormat: translateDateFormat('<?php echo esc_js(Kiwiz_Document_Certify::get_document_settings('shop_date_format')); ?>')
        });
    });
</script>
