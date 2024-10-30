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
 * Admin Main page
 */
defined( 'ABSPATH' ) || exit;

$section = ( isset($_GET['section']) ) ? sanitize_text_field($_GET['section']) : 'invoice';
?>

<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=kiwiz-certification&section=invoice' ) ); ?>" class="nav-tab <?php if($section == 'invoice') echo 'nav-tab-active';?>"><?php _e( "Invoices", 'kiwiz-invoices-certification-pdf-file' ); ?></a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=kiwiz-certification&section=refund' ) ); ?>" class="nav-tab <?php if($section == 'refund') echo 'nav-tab-active';?>"><?php _e( "Refunds", 'kiwiz-invoices-certification-pdf-file' ); ?></a>
</nav>

<?php
switch ( $section ) {
    case "refund":
        include KIWIZ_PLUGIN_PATH . 'admin/tpl/refund-list-page.php';
        break;
    case "invoice":
    default:
        include KIWIZ_PLUGIN_PATH . 'admin/tpl/invoice-list-page.php';
        break;
}
?>
