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
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly
?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <?php do_action( 'kiwiz_example_head' ); ?>
        <?php wp_print_styles(array('kiwiz-example-dompdf-styles')) ?>
    </head>
    <body>
        <?php do_action( 'kiwiz_example_body' );  ?>
        <div style="text-align:center;color:red;margin:30px auto; border: 1px solid red;padding: 10px; border-radius:5px;width:70%">Specimen</div>
    </body>
</html>
