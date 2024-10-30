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
 * Class Kiwiz_Admin
 */

class Kiwiz_Admin {
    static $_required = array();
    static $_error = array();

    public static function get_settings( $option_name ){
        $options = get_option($option_name, array());
        return $options;
    }

    public static function render_fields($args){
        $default = array(
            'type' => 'text',
            'name' => 'name',
            'required' => false,
            'description' => '',
            'value' => '',
            'options' => array()
        );
        $args = wp_parse_args($args, $default);
        extract($args);
        switch ($type){
            case 'text':?>
                <input class="regular-text <?php if(in_array($name,self::$_error)):?>error<?php endif;?>" type="text" value="<?php echo esc_attr($value);?>" name="<?php echo esc_attr($name);?>">
                <?php if(!empty($description)):?><br><em><?php echo esc_html($description);?></em><?php endif;?>
                <?php break;

            case 'textarea':?>
                <textarea style="height: 150px;" class="regular-text <?php if(in_array($name,self::$_error)):?>error<?php endif;?>" name="<?php echo esc_attr($name);?>"><?php echo esc_html($value);?></textarea>
                <?php if(!empty($description)):?><br><em><?php echo esc_html($description);?></em><?php endif;?>
                <?php break;

            case 'checkbox':?>
                <input class="<?php if(in_array($name,self::$_error)):?>error<?php endif;?>" type="checkbox" value="1" <?php if ( $value == 1) :?>checked="checked"<?php endif;?> name="<?php echo esc_attr($name);?>" id="<?php echo esc_attr($name);?>">
                <?php if(!empty($description)):?><br><em><?php echo esc_html($description);?></em><?php endif;?>
                <?php break;

            case 'image':?>
                <div class="image-container">
                    <img <?php if($value=='') echo 'style="display:none"'?>class="image" src="<?php echo esc_url($value) ?>" />
                    <input class="image_url" type="hidden" name="<?php echo esc_attr($name);?>" id="<?php echo esc_attr($name);?>" value="<?php echo esc_attr($value); ?>">
                    <a href="#" class="field-button field-add-button" id="upload_field"><?php if($value==''): echo __( "Add image", 'kiwiz-invoices-certification-pdf-file' ); else: echo __( "Edit image", 'kiwiz-invoices-certification-pdf-file' ); endif;?></a>
                    <a href="#" class="field-button field-remove-button" id="delete_field" <?php if($value=='') echo 'style="display:none"'?>><?php echo __( "Delete", 'kiwiz-invoices-certification-pdf-file' ) ?></a>
                    <div class="error error-image" style="display:none"></div>
                </div>
                <?php break;

            case 'select':?>
                <select class="regular-text" name="<?php echo esc_attr($name);?>">
                    <?php foreach ( $options as $v => $lab): ?>
                        <option <?php if( $value == $v ):?>selected="selected"<?php endif;?> value="<?php echo esc_attr($v);?>"><?php echo esc_html($lab);?></option>
                    <?php endforeach;?>
                </select>
                <?php if(!empty($description)):?><br><em><?php echo esc_html($description);?></em><?php endif;?>
                <?php break;

            case 'datepicker': ?>
                <input type="text" class="datepicker" value="<?php echo esc_attr($value);?>" name="<?php echo esc_attr($name);?>" />
                <?php break;

            case 'toggle_button': ?>
                <label class="switch">
                    <span class="span-left-text"><?php echo __( "Yes", 'kiwiz-invoices-certification-pdf-file' ); ?></span>
                    <input type="checkbox" <?php if ( $value == 'on') :?>checked="checked"<?php endif;?> name="<?php echo esc_attr($name);?>">
                    <span class="slider round"></span>
                    <span class="span-right-text"><?php echo __( "No", 'kiwiz-invoices-certification-pdf-file' ); ?></span>
                </label>
                <?php break;
            default:break;
        }

        if($required){
            array_push(self::$_required,$name);
        }
    }

    public static function hidden_fields(){
        if ( !empty(self::$_required) ){
            $hiddenfields = implode(',',self::$_required);
            ?>
            <input type="hidden" value="<?php echo esc_attr($hiddenfields); ?>" name="required_fields">
        <?php }
    }

    //check required fields
    public static function check_required_fields($postdata){
        $msg = '';
        if(isset($postdata['required_fields'])){
            $required_fields = explode(',',$postdata['required_fields']);
            foreach ($required_fields as $field) {
                if((!isset($postdata[$field]) || empty($postdata[$field])) && (!isset($_FILES[$field]) || empty($_FILES[$field]['name']))){
                    $msg.= 'Le champs ' . esc_html($field) . ' est requis<br>';
                    array_push(self::$_error, $field);
                }
            }
        }
        return $msg;
    }

    //process submission
    public static function process_post( ){

        if( isset($_POST) &&  count($_POST) > 0) {

            $post_datas     = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST ) );
            $option_name    = $post_datas['option_name'];
            $check_token    = false;

            //check token if ids changed
            if ( $option_name == KIWIZ_CERT_SETTINGS ) {
                $cert_settings_options  = get_option(KIWIZ_CERT_SETTINGS, array());
                $data_to_compare        = array('login', 'password', 'sid');
                if ( count($cert_settings_options) > 0 ) {
                    foreach ( $data_to_compare as $data ) {
                        if ( $post_datas[$data] != $cert_settings_options[$data] )
                            $check_token = true;
                    }
                } else {
                    $check_token = true;
                }

            }

            //Validate required fields
            $errors = self::check_required_fields($post_datas);
            if( !empty($errors) ) return array (
                'error'     => $errors,
                'message'   => ''
            );

            unset($post_datas[$option_name]);
            unset($post_datas['required_fields']);

            //save tab option
            update_option($option_name, $post_datas);

            if ( $check_token ) {
                Kiwiz_API::get_new_token();
            }

            //remove listing options
            if ( $option_name == KIWIZ_PDF_SETTINGS ) {
                delete_option('kiwiz_invoice_list_settings', array());
                delete_option('kiwiz_refund_list_settings', array());
            }
        }
    }
}
