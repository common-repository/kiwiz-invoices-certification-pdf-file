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
 * Class Kiwiz_Utils
 */

class Kiwiz_Utils {


    /**
     * Send an notification email to admin store
     * @param $subject
     * @param $message
     */
    static function send_email( $subject, $message ) {
        //get emails
        $option_setting_name = 'woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings';
        $options    = get_option($option_setting_name, array());

        $destinataire_mail = explode(",", $options['kiwiz_emails']);
        $subject = '[' . get_option('blogname') . '] ' . $subject;
        add_filter('wp_mail_content_type', 'getFormat');
        function getFormat(){
            return "text/html";
        }
        $headers_mail = 'From: '. get_option('blogname') .' <'. get_option('admin_email') .'>' . "\r\n";
        wp_mail( $destinataire_mail ,  wp_specialchars_decode( $subject ), nl2br($message) , $headers_mail );

    }

}