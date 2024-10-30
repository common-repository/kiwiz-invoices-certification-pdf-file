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

jQuery( document ).ready(function() {
    var document_type = ['invoice', 'refund'];
    for ( var i=0 ; i<document_type.length ; i++) {
        jQuery( '.woocommerce-button.button.'+document_type[i] ).each(function() {
            var link_href       = jQuery( this ).attr('href');
            var request_values  = link_href.substr( (link_href.indexOf('?') + 1), (link_href.length-link_href.indexOf('?')));
            var request_values  = request_values.split("&");
            var result = new Array();
            for ( var j=0 ; j<request_values.length ; j++) {
                var key  =  request_values[j].substr(  0, request_values[j].indexOf('=') );
                var value = request_values[j].substr(  (request_values[j].indexOf('=') + 1) , (request_values[j].length-request_values[j].indexOf('=')) );
                result[key] = value;
            }
            jQuery( this ).attr("href", "#");
            jQuery( this ).attr("onclick", "return manage_kiwiz_document('"+result['kiwiz_action']+"', '"+result['type']+"', '"+result['order_id']+"', '"+result['nonce']+"');");
        });
    }
});

function manage_kiwiz_document( action, type, object_id, nonce ) {
    resetErrorMsg();
    jQuery.ajax( {
        url : kiwiz_ajax_front.frontAjax,
        method : 'POST',
        data : {
            action          : 'kiwiz',
            kiwiz_action    : action,
            type            : type,
            object_id       : object_id,
            nonce           : nonce,
            frontend        : 1,
        },
        success : function( datas ) {
            if ( datas.success ) {
                switch ( datas.data.callback_action ) {
                    case "reload":
                        location.reload();
                        break;
                    case "download":
                        var a = document.createElement("a");
                        a.href = 'data:application/pdf;base64,'+ datas.data.document_content ;
                        a.id = 'kiwiz-document-download-link';
                        a.download = datas.data.document_name;
                        document.body.appendChild(a);
                        a.click();
                        jQuery( "#kiwiz-document-download-link" ).remove();
                        break;
                    default:
                        break;
                }

            } else {
                displayErrorMsg( datas.data )
            }
            return false;
        },
        error : function() {
            displayErrorMsg( 'An error has occurred' );
        }
    });
    return false;

}

function resetErrorMsg() {
    if ( jQuery("#kiwiz-actions-error-msg").length ) {
        jQuery("#kiwiz-actions-error-msg").html('').hide();
    }
}

function displayErrorMsg( msg ) {
    if ( jQuery("#kiwiz-actions-error-msg").length ) {
        jQuery("#kiwiz-actions-error-msg").html(msg).show();
    }
}