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
 * Class Kiwiz_WC_Integration
 */

class Kiwiz_Integration_Account_Settings extends WC_Integration {
    /**
     * Init and hook in the integration.
     */
    public function __construct() {
        $this->id = KIWIZ_CERT_SETTINGS;

        $this->method_title = __( 'KIWIZ', 'kiwiz-invoices-certification-pdf-file' );

        //Description
        $method_description  = '<p class="kiwiz-account-text">' .__('The finance 2016 law requires you to use, since January 2018 accounting software or a secure and certified cash register system to manage your billing.', 'kiwiz-invoices-certification-pdf-file');
        $method_description .= ' ' . __('Kiwiz offers a solution that allows you to maintain your current system and comply with the "VAT Anti-Fraud Act".','kiwiz-invoices-certification-pdf-file');
        $method_description .= ' ' . __('Kiwiz is a certification platform in the Blockchain of accounting documents (invoices and credit notes).','kiwiz-invoices-certification-pdf-file');
        $method_description .= '<p class="kiwiz-account-text">' .__( 'Simply subscribe to a subscription here <a href="https://www.kiwiz.io/prix" target="_blank">https://www.kiwiz.io/prix</a> then retrieve your identifiers indicated in the welcome email and fill out the form below.', 'kiwiz-invoices-certification-pdf-file' ). '</p>';
        $this->method_description = $method_description;

        // Load the settings
        $this->init_form_fields();
        $this->init_field_values();
        $this->init_settings();


        // Actions
        add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

        // Filters
        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

    }


    /**
     * Initialize integration settings form fields.
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'kiwiz_login' => array(
                'title'             => __( 'Login', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'text',
            ),
            'kiwiz_password' => array(
                'title'             => __( 'Password', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'custom_password',
            ),
            'kiwiz_sid' => array(
                'title'             => __( 'Subscription ID', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'text'
            ),
            'kiwiz_test_mode' => array(
                'title'             => __( 'Enable test mode', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'toggle_button'
            ),
            'kiwiz_emails' => array(
                'title'             => __('Notification emails', 'kiwiz-invoices-certification-pdf-file'),
                'type'              => 'textarea',
                'description'       => __('Separated by commas', 'kiwiz-invoices-certification-pdf-file'),
                'css'               => 'width:400px;'
            ),
            'kiwiz_quota' => array(
                'type'              => 'custom_kiwiz_text',
                'kiwiz_type'        => 'quotas'
            ),
            'kiwiz_title1' => array(
                'type'              => 'title',
                'title'             => __( 'Configure PDF Settings Kiwiz', 'kiwiz-invoices-certification-pdf-file' ),
            ),
            'shop_pdf_logo' => array(
                'title'             => __('Shop logo', 'kiwiz-invoices-certification-pdf-file'),
                'type'              => 'custom_image',
                'description'       => __('The image must be in the format: jpg, png, gif', 'kiwiz-invoices-certification-pdf-file') . __('. Logo must be no bigger than 300 x 150 pixels', 'kiwiz-invoices-certification-pdf-file'),
            ),
            'shop_pdf_name' => array(
                'title'             => __( 'Shop Name', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'text',
                'required'          => 'required',
                'default'           => ( get_bloginfo( 'name' ) != '' ) ? get_bloginfo( 'name' ) : '',
            ),
            'shop_pdf_address' => array(
                'title'             => __( 'Shop Address', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'textarea',
                'css'               => 'width:400px;',
                'required'          => 'required',
                'default'           => $this->get_shop_address_default_value()
            ),
            'shop_pdf_header' => array(
                'title'             => __('Document header', 'kiwiz-invoices-certification-pdf-file'),
                'type'              => 'textarea',
                'css'               => 'width:400px;'
            ),
            'shop_pdf_footer' => array(
                'title'             => __('Document footer', 'kiwiz-invoices-certification-pdf-file'),
                'type'              => 'textarea',
                'css'               => 'width:400px;',
                'required'          => 'required'
            ),
            'shop_date_format' => array(
                'title'             => __( 'Date format ', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'select',
                'default'           => 'd/m/Y',
                'options'              => array(
                    'd/m/Y' => 'd/m/Y'.' : '.date('d/m/Y'),
                    'm/d/Y' => 'm/d/Y'.' : '.date('m/d/Y'),
                    'Y-m-d' => 'Y-m-d'.' : '.date('Y-m-d')
                )
            ),
            'kiwiz_preview' => array(
                'type'              => 'custom_kiwiz_preview',
                'kiwiz_type'        => 'preview'
            ),
            'kiwiz_title2' => array(
                'type'              => 'title',
                'title'             => __( 'Configure invoices creation', 'kiwiz-invoices-certification-pdf-file' ),
            ),
            'kiwiz_status_order_event_invoice' => array(
                'title'             => __( 'Create the invoice if the order has the state', 'kiwiz-invoices-certification-pdf-file' ),
                'type'              => 'multiselect',
                'css'               => 'height:inherit;',
                'description'       => __('You can choose multiple states', 'kiwiz-invoices-certification-pdf-file'),
                'options'           => wc_get_order_statuses()
            ),

        );
    }

    public function get_shop_address_default_value() {
        $shop_address = '';
        if ( get_option( 'woocommerce_store_address', '' ) != '' )
            $shop_address .= get_option( 'woocommerce_store_address', '' );
        if ( get_option( 'woocommerce_store_address_2', '' ) != '' )
            $shop_address .= ( ($shop_address != '' ) ? "\n" : '').get_option( 'woocommerce_store_address_2', '' );
        if ( get_option( 'woocommerce_store_postcode', '' ) != '' )
            $shop_address .= "\n".get_option( 'woocommerce_store_postcode', '' );
        if ( get_option( 'woocommerce_store_city', '' ) != '' )
            $shop_address .= ( (get_option( 'woocommerce_store_postcode', '' ) != '') ? ' ' : "\n").get_option( 'woocommerce_store_city', '' );

        return $shop_address;
    }

    public function generate_text_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args( $data, $defaults );

        if ( !isset($data['required']) )
            $data['required'] = false;

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?> <?php if( $data['required'] == true ): ?><span class="required">*</span><?php endif;?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?> <?php if( $data['required'] == true ) echo 'required'; ?> />
                    <?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    public function generate_textarea_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args( $data, $defaults );

        if ( !isset($data['required']) )
            $data['required'] = false;

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?> <?php if( $data['required'] == true ): ?><span class="required">*</span><?php endif;?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <textarea rows="3" cols="20" class="input-text wide-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  // WPCS: XSS ok. ?> <?php if( $data['required'] == true ) echo 'required'; ?>><?php echo esc_textarea( $this->get_option( $key ) ); ?></textarea>
                    <?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Generate toggle button
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_toggle_button_html( $key, $data ) {
        $field    = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class'             => 'button-secondary',
            'css'               => '',
            'custom_attributes' => array(),
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <label class="switch">
                        <span class="span-left-text"><?php echo __( "Yes", 'kiwiz-invoices-certification-pdf-file' ); ?></span>
                        <input type="checkbox" <?php if ( $this->get_option( $key ) == 'on') :?>checked="checked"<?php endif;?> name="<?php echo esc_attr('woocommerce_'.$this->id.'_'.$key) ;?>" id="<?php echo esc_attr('woocommerce_'.$this->id.'_'.$key) ;?>">
                        <span class="slider round"></span>
                        <span class="span-right-text"><?php echo __( "No", 'kiwiz-invoices-certification-pdf-file' ); ?></span>
                    </label>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Add custom text
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_custom_kiwiz_text_html() {
        $token = Kiwiz_API::get_token();
        $quotas = null;
        if ( $token )
            $quotas = Kiwiz_API::get_quotas($token);

        ob_start(); ?>
        <tr valign="top">
            <th scope="row"></th>
            <td scope="row">
        <?php if ( $quotas != null && !(isset($quotas->error)) ) {
            $quotas = Kiwiz_API::get_quotas($token);
            ?>
            <?php if ( (isset($quotas->used)) && (isset($quotas->limit)) ) { ?>
                <div class="kiwiz-account account-valid">
                    <strong><?php echo __('Using your current plan is', 'kiwiz-invoices-certification-pdf-file') ?> : </strong>
                    <div>
                        <span style="color:#006505;font-size: 20px;"><?php echo esc_html($quotas->used);?></span> <span style="color:#ff0000;font-size: 20px;">/ <?php echo esc_html($quotas->limit);?></span>
                    </div>
                </div>
            <?php } ?>
            <?php
        } else {
            Kiwiz_API::delete_activation_date(); ?>
            <div class="kiwiz-account account-no-valid">
                <strong><?php echo __('Your account is not valid', 'kiwiz-invoices-certification-pdf-file') ?></strong>
            </div>
        <?php } ?>
            </td>
        </tr>
        <?php return ob_get_clean();
    }

    /**
     * Add preview button
     */
    public function generate_custom_kiwiz_preview_html() {
        ob_start(); ?>
        <tr valign="top" class="kiwiz-woopdfc-themes">
            <th scope="row"></th>
            <td scope="row">
               <a href="#" class="field-button field-add-button" onclick="return manage_kiwiz_document('kiwiz_get_document', 'example', '', '<?php echo esc_js(Kiwiz::get_wp_nonce()); ?>'); return false;"><?php echo __('Preview', 'kiwiz-invoices-certification-pdf-file') ?></a>
            </td>
        </tr>
        <?php return ob_get_clean();
    }

    /**
     * Generate image field
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_custom_image_html( $key, $data ) {
        if(function_exists( 'wp_enqueue_media' )){
            wp_enqueue_media();
        }else{
            wp_enqueue_style('thickbox');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
        }

        $field    = $this->plugin_id . $this->id . '_' . $key;

        ob_start();
        ?>



        <tr valign="top" class="kiwiz-woopdfc-themes">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <div class="image-container">
                        <div class="error-image" style="display:none"></div>
                        <img <?php if($this->shop_pdf_logo=='') echo 'style="display:none"'?>class="image" src="<?php echo esc_url($this->get_option( $key )) ?>" />
                        <input class="image_url" type="hidden" name="<?php echo esc_attr('woocommerce_'.$this->id.'_'.$key) ;?>" id="<?php echo esc_attr('woocommerce_'.$this->id.'_'.$key) ;?>" value="<?php echo esc_attr($this->shop_pdf_logo); ?>">
                        <a href="#" class="field-button field-add-button" id="upload_field"><?php if($this->shop_pdf_logo==''): echo __( "Add image", 'kiwiz-invoices-certification-pdf-file' ); else: echo __( "Edit image", 'kiwiz-invoices-certification-pdf-file' ); endif;?></a>
                        <a href="#" class="field-button field-remove-button" id="delete_field" <?php if($this->shop_pdf_logo=='') echo 'style="display:none"'?>><?php echo __( "Delete", 'kiwiz-invoices-certification-pdf-file' ) ?></a>
                        <p class="description"><?php echo esc_html($data['description']) ?></p>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate password field
     * @param $key
     * @param $data
     */
    public function generate_custom_password_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );

        if ( !isset($data['required']) )
            $data['required'] = false;

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <input class="input-text regular-input " type="password" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="" placeholder="">
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Initialize fields values.
     *
     * @return void
     */
    public function init_field_values() {

        $this->kiwiz_login      = sanitize_text_field( $this->get_option( 'kiwiz_login' ) );
        $this->kiwiz_password   = sanitize_text_field( $this->get_option( 'kiwiz_password') );
        $this->kiwiz_sid        = sanitize_text_field( $this->get_option( 'kiwiz_sid' ) );
        $this->kiwiz_test_mode  = sanitize_text_field( $this->get_option( 'kiwiz_test_mode' ) );

        $this->shop_pdf_logo    = sanitize_url($this->get_option('shop_pdf_logo'));
        $this->shop_pdf_name    = sanitize_text_field( $this->get_option( 'shop_pdf_name' ) );
        $this->shop_pdf_address = sanitize_text_field( $this->get_option( 'shop_pdf_address' ) );
        $this->shop_pdf_header  = sanitize_text_field( $this->get_option( 'shop_pdf_header' ) );
        $this->shop_pdf_footer  = sanitize_text_field( $this->get_option( 'shop_pdf_footer' ) );
        $this->shop_date_format = sanitize_text_field( $this->get_option( 'shop_date_format' ) );

        $this->kiwiz_status_order_event_invoice = [];
        $temp_order_statuses = $this->get_option( 'kiwiz_status_order_event_invoice', [] );
        $available_statuses = array_keys( wc_get_order_statuses() );
        foreach( $temp_order_statuses as $temp_order_status ) {
            if( in_array( $temp_order_status , $available_statuses) ) array_push( $this->kiwiz_status_order_event_invoice , $temp_order_status );
        }
        
    }


    /**
     * Santize our settings
     * @see process_admin_options()
     */
    public function sanitize_settings( $settings ) {
        return $settings;
    }

    /**
     * Update options
     */
    public function process_admin_options() {
        $this->init_settings();
        $post_data = $this->get_post_data();
        $check_token = false;

        foreach ( $this->get_form_fields() as $key => $field ) {
            if ( 'title' !== $this->get_field_type( $field ) ) {
                try {
                    if ( $key == "kiwiz_password") {
                        //encode password
                        $login    = sanitize_text_field($post_data['woocommerce_' . KIWIZ_CERT_SETTINGS . '_' . 'kiwiz_login']);
                        $sid      = sanitize_text_field($post_data['woocommerce_' . KIWIZ_CERT_SETTINGS . '_' . 'kiwiz_sid']);
                        $password = stripslashes(sanitize_text_field($post_data['woocommerce_' . KIWIZ_CERT_SETTINGS . '_' . 'kiwiz_password']));
                        if ( $password != '' ) {
                            $this->settings[ $key ] = Kiwiz_Encrypt::encrypt($password, hash('sha256', $login.$sid,true));
                            $check_token = true;
                        }
                    } else {

                        switch ($key) {

                            case 'kiwiz_emails':
                                // Check validate email address format and keep only correct format
                                $temp_emails = sanitize_text_field($this->get_field_value( $key, $field, $post_data ));
                                $emails = explode("," , $temp_emails);
                                $correct_emails = [];
                                foreach( $emails as $email ) {
                                    $email = trim($email);
                                    if( is_email( $email ) ) {
                                        array_push($correct_emails, $email);
                                    }
                                }
                                $this->settings[ $key ] = implode(",", $correct_emails);
                                break;
                            
                            case 'shop_pdf_logo':
                                // Check if there is and image url, if it is sanitizing the url
                                $temp_logo_url = $this->get_field_value( $key, $field, $post_data );
                                $this->settings[ $key ] = sanitize_url($temp_logo_url);

                                break;
                            
                            case 'kiwiz_status_order_event_invoice':
                                // Keep only order-status available from Woocommerce
                                $temp_order_statuses = $this->get_field_value( $key, $field, $post_data );
                                $validated_order_statuses = [];
                                if( is_array($temp_order_statuses) ) {
                                    $available_statuses = array_keys( wc_get_order_statuses() );
                                    foreach( $temp_order_statuses as $temp_order_status ) {
                                        if( in_array( $temp_order_status , $available_statuses) ) array_push( $validated_order_statuses , sanitize_text_field($temp_order_status) );
                                    }
                                    $this->settings[ $key ] = $validated_order_statuses;
                                }

                                break;
                                
                            default:
                                // other fields sanitize text field
                                $this->settings[ $key ] = sanitize_text_field($this->get_field_value( $key, $field, $post_data ));
                                break;

                        }

                        
                        
                    }
                } catch ( Exception $e ) {
                    $this->add_error( $e->getMessage() );
                }
            }
        }

        //Check token if ids changed
        $options_to_compare = array('kiwiz_login', 'kiwiz_sid');
        foreach ($options_to_compare as $option) {
            if ($post_data['woocommerce_' . KIWIZ_CERT_SETTINGS . '_' . $option] != $this->$option ) {
                $check_token = true;
            }
        }

        if ($check_token) {
            delete_option('kiwiz_api_token');
        }

        update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
    }


    /**
     * Validate the API key
     * @see validate_settings_fields()
     */
    public function validate_api_key_field( $key ) {
        // get the posted value
        $value = sanitize_text_field( wp_unslash( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );

        // check if the API key is longer than 20 characters. Our imaginary API doesn't create keys that large so something must be wrong. Throw an error which will prevent the user from saving.
        if ( isset( $value ) &&
            20 < strlen( $value ) ) {
            $this->errors[] = $key;
        }
        return $value;
    }

}
