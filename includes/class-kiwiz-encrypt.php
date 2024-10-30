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
 * Class Kiwiz Encrype
 */


//Define KIWIZ_CRYPT_METHOD
if ( ! defined( 'KIWIZ_CRYPT_METHOD' ) ) {
    define( 'KIWIZ_CRYPT_METHOD', 'aes-256-cbc' );
}


class Kiwiz_Encrypt {

    public static function encrypt($data, $key)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(KIWIZ_CRYPT_METHOD));
        $encrypted = openssl_encrypt($data, KIWIZ_CRYPT_METHOD, $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decrypt($data, $key)
    {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2); $a =  openssl_decrypt($encrypted_data, KIWIZ_CRYPT_METHOD, $key, 0, $iv);
        return openssl_decrypt($encrypted_data, KIWIZ_CRYPT_METHOD, $key, 0, $iv);
    }
}