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

jQuery( function ($) {

    // ======= click in the upload button to check when the logo is inserted ========

    $( "body" ).on( "click", ".image-container #upload_field", function (e) {

            $(".error-image").html('').hide();

            e.preventDefault();

            var _self = $(this);

            var allowed_image_type = new Array('jpeg', 'jpg','png','gif');

            var custom_uploader = wp.media({
                title: 'Custom Image',
                button: {
                    text: 'Upload Image'
                },
                multiple: false
            })
            .on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                if ( allowed_image_type.indexOf( attachment.subtype ) >= 0 ) {
                    var tmpImg = new Image();
                    tmpImg.src = attachment.url;
                    $( tmpImg ).one( 'load',function() {
                        imgWidth  = tmpImg.width;
                        imgHeight = tmpImg.height;

                        if ( imgWidth > 300 || imgHeight > 150 ) {
                            $(".error-image").html(invoice_free_object.logo_message_1 + imgWidth + "x" + imgHeight + " pixels" + invoice_free_object.logo_message_2).show();
                        } else {
                            displayLogo( _self, attachment.url );
                        }
                    });
                } else {
                    $(".error-image").html(invoice_free_object.logo_message_3).show();
                }
            })
            .open();
    });

    $( "body" ).on( "click", ".image-container #delete_field", function () {
        hideLogo($(this));
        return false;
    });

    $( "body" ).on( "click", ".kiwiz-form-submit-button", function () {
        var formId = $(this).attr('data-form-id');
        $('#'+formId).submit();
    });

    function hideLogo( elem ) {
        elem.siblings('img').attr('src', '').hide();
        elem.siblings('.image_url').val('');
        elem.siblings('#upload_field').html(invoice_free_object.value_btn_hide);
        elem.hide();
    }

    function displayLogo( elem, url ) {
        elem.siblings('img').attr('src', url).show();
        elem.siblings('.image_url').val(url);
        elem.siblings('#delete_field').show();
        elem.html(invoice_free_object.value_btn_show);
    }

});