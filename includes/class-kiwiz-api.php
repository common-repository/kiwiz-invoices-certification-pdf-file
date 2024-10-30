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

define('KIWIZ_API_BASE_URL', 'https://api.kiwiz.io');

/**
 * Class Kiwiz_API
 */

class Kiwiz_API {

    static function get_api_url( $action ) {
        $api_url = KIWIZ_API_BASE_URL . $action;

        //add platform params
        $platform_datas = self::get_platform_datas();
        $api_url .= '?platform=' . urlencode($platform_datas['platform']) . '&version=' . urlencode($platform_datas['version']);

        //add test mode param
        $option_setting_name = 'woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings';
        $cert_settings_options = get_option($option_setting_name, array());
        if ( (isset($cert_settings_options['kiwiz_test_mode']) && $cert_settings_options['kiwiz_test_mode'] == 'on') )
            $api_url .= '&test_mode=1';

        return $api_url;
    }

    static function get_token() {
        $token = get_option('kiwiz_api_token', '');

        if ( !empty($token) && self::is_valid_token($token) ) {
            return $token;
        }

        $token = self::get_new_token();
        return $token;
    }

    static function get_new_token() {
        delete_option('kiwiz_api_token');
        $option_setting_name = 'woocommerce_'.KIWIZ_CERT_SETTINGS.'_settings';
        $options    = get_option($option_setting_name, array());
        $login      = isset($options['kiwiz_login'])    ? $options['kiwiz_login'] : '';
        $sid        = isset($options['kiwiz_sid'])       ? $options['kiwiz_sid'] : '';
        $password   = (isset($options['kiwiz_password']) && $options['kiwiz_password'] != '') ? Kiwiz_Encrypt::decrypt($options['kiwiz_password'],hash('sha256',$login.$sid,true)) : '';


        $result = wp_remote_post( self::get_api_url('/token/generate'), array(
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
                'username'          => $login,
                'password'          => $password,
                'subscription_id'   => $sid
            ),
            'cookies' => array()
        ) );

        $decode_res = json_decode($result['body']);
        if ( $result['response']['code'] == 200 ){
            update_option('kiwiz_api_token', $decode_res->token);
            self::save_activation_date();
            return $decode_res->token;
        } else {
            return null;
        }

    }

    static function save_activation_date() {
        if ( get_option('kiwiz_activation_date') === false )
            add_option('kiwiz_activation_date', time());
        else if ( get_option('kiwiz_activation_date') == '' )
            update_option('kiwiz_activation_date', time());
    }

    static function delete_activation_date() {
        delete_option('kiwiz_activation_date');
    }

    static function get_platform_datas() {
        $platform_datas = array();
        //Platform
        $platform_datas['platform'] = 'woocommerce';

        //Wordpress version
        $wordpress_version = get_bloginfo( 'version' );

        //WooCommerce version
        $woocommerce_version = '';
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) )
            $woocommerce_version = $plugin_folder[$plugin_file]['Version'];

        //Kiwiz version
        $plugin_kiwiz_data  = get_file_data(KIWIZ_PLUGIN_FILE, array('Version' => 'Version'), false);
        $kiwiz_version      = $plugin_kiwiz_data['Version'];

        $version_datas = array( 'wordpress:'.$wordpress_version,
            'woocommerce:'.$woocommerce_version,
            'kiwiz:'.$kiwiz_version);

        $platform_datas['version']  = implode("|", $version_datas);

        return $platform_datas;
    }

    static function get_quotas( $token ){

        $result = wp_remote_get( self::get_api_url('/quota/info'), array(
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Authorization' => $token
            ),
            'body' => array(),
            'cookies' => array()
        ) );

        if ( !is_wp_error($result) ){
            return json_decode($result['body']);
        } else {
            return null;
        }

    }

    static function is_valid_token( $token ){
        $result = self::get_quotas($token);

        if ( isset($result->error) && $result->error == 'Token' ) {
            return false;
        } else {
            return true;
        }
    }

    static function save_invoice( $token, $local_file, $order_id ) {
        $invoice_object = Kiwiz_Invoice_Datas::get_order_datas($order_id);

        $post_fields = array (
            'data' => json_encode($invoice_object)
        );
        $boundary = wp_generate_password( 24 );
        $headers  = array(
            'Authorization' => $token,
            'content-type' => 'multipart/form-data; boundary=' . $boundary
        );
        $payload = '';
        // First, add the standard POST fields:
        foreach ( $post_fields as $name => $value ) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $name .
                '"' . "\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }
        // Upload the file
        if ( $local_file ) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . 'document' .
                '"; filename="' . basename( $local_file ) . '"' . "\r\n";
            //        $payload .= 'Content-Type: image/jpeg' . "\r\n";
            $payload .= "\r\n";
            $payload .= file_get_contents( $local_file );
            $payload .= "\r\n";
        }
        $payload .= '--' . $boundary . '--';
        $result = wp_remote_post(
            self::get_api_url('/invoice/save'),
            array(
                'method' => 'POST',
                'timeout' => 1800,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers'    => $headers,
                'body'       => $payload,
                'cookies' => array()
            )
        );

        if ( !is_wp_error($result) ){
            return json_decode($result['body']);
        } else {
            return null;
        }

    }

    static function get_invoice( $token, $block_hash ) {
        $result = wp_remote_get( self::get_api_url('/invoice/get'), array(
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Authorization' => $token
            ),
            'body' => array(
                'block_hash' => $block_hash
            ),
            'cookies' => array()
        ) );

        if ( !is_wp_error($result) ){
            if ( $result['response']['code'] == '200' ) {
                return isset($result['body']) ? $result['body'] : null;
            }
            return null;
        } else {
            return null;
        }

    }

    static function save_refund( $token, $local_file, $refund_id ){
        $memo_object = Kiwiz_Refund_Datas::get_refund_datas($refund_id);

        $post_fields = array (
            'data' => json_encode($memo_object)
        );
        $boundary = wp_generate_password( 24 );
        $headers  = array(
            'Authorization' => $token,
            'content-type' => 'multipart/form-data; boundary=' . $boundary
        );
        $payload = '';
        // First, add the standard POST fields:
        foreach ( $post_fields as $name => $value ) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . $name .
                '"' . "\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }
        // Upload the file
        if ( $local_file ) {
            $payload .= '--' . $boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="' . 'document' .
                '"; filename="' . basename( $local_file ) . '"' . "\r\n";
            //        $payload .= 'Content-Type: image/jpeg' . "\r\n";
            $payload .= "\r\n";
            $payload .= file_get_contents( $local_file );
            $payload .= "\r\n";
        }
        $payload .= '--' . $boundary . '--';
        $result = wp_remote_post(
            self::get_api_url('/creditmemo/save'),
            array(
                'method' => 'POST',
                'timeout' => 1800,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers'    => $headers,
                'body'       => $payload,
                'cookies' => array()
            )
        );
        if ( !is_wp_error($result) ){
            return json_decode($result['body']);
        } else {
            return null;
        }

    }

    static function get_refund( $token, $block_hash ){
        $result = wp_remote_get( self::get_api_url('/creditmemo/get'), array(
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(
                'Authorization' => $token
            ),
            'body' => array(
                'block_hash' => $block_hash
            ),
            'cookies' => array()
        ) );

        if ( !is_wp_error($result) ){
            if ( $result['response']['code'] == '200' ) {
                return isset($result['body']) ? $result['body'] : null;
            }
            return null;
        } else {
            return null;
        }

    }
}