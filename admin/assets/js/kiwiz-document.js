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

function manage_kiwiz_document( action, type, object_id, nonce ) {
    resetErrorMsg();
    jQuery.ajax( {
        url : kiwiz_ajax.adminAjax,
        method : 'POST',
        data : {
            action          : 'kiwiz',
            kiwiz_action    : action,
            type            : type,
            object_id       : object_id,
            nonce           : nonce,
        },
        success : function( datas ) {
            if ( datas.success ) {
                switch ( datas.data.callback_action ) {
                    case "reload":
                        location.reload();
                        break;
                    case "download":
                    case "download_and_reload":
                        var a = document.createElement("a");
                        a.href = 'data:application/pdf;base64,'+ datas.data.document_content ;
                        a.id = 'kiwiz-document-download-link';
                        a.download = datas.data.document_name;
                        document.body.appendChild(a);
                        a.click();
                        jQuery( "#kiwiz-document-download-link" ).remove();
                        if ( datas.data.callback_action == "download_and_reload")
                            location.reload();
                        break;
                    default:
                        break;
                }
                hideAllLoader();
            } else {
                hideAllLoader();
                displayErrorMsg( datas.data.error )
            }
            return false;
        },
        error : function() {
            hideAllLoader();
            console.log( 'An error has occurred' );
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

function displayLoader( elem ) {
    elem.find('.loader').show();
}

function hideAllLoader() {
    jQuery('.loader').hide();
}