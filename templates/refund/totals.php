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
?>
<table class="document-totals">
    <tbody>
        <tr>
            <td class="first"></td>
            <td class="second">
                <table class="totals">
                    <?php do_action( 'kiwiz_document_refund_order_totals' ); ?>
                </table>
            </td>
        </tr>
    </tbody>
</table>