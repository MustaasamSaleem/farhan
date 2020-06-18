function swcfpc_lock_screen() {

    if(jQuery(".swcfpc_please_wait").length <= 0) {

        jQuery('input[type=submit]').addClass("swcfpc_display_none");
        jQuery('input[type=button]').addClass("swcfpc_display_none");
        jQuery('a').addClass("swcfpc_display_none");

        jQuery("body").prepend('<div class="swcfpc_please_wait"></div>');

    }

}


function swcfpc_unlock_screen() {

    jQuery('input[type=submit]').removeClass("swcfpc_display_none");
    jQuery('input[type=button]').removeClass("swcfpc_display_none");
    jQuery('a').removeClass("swcfpc_display_none");

    jQuery("body .swcfpc_please_wait").remove();

}


function swcfpc_redirect_to_page( url ) {
    window.location = url;
}


function swcfpc_refresh_page() {
    location.reload();
}


function swcfpc_display_ok_dialog(title, content, width, height, type, subtitle, button_name, callback, callback_first_parameter) {

    width                    = (typeof width === "undefined" || width == null) ? 350 : parseInt(width);
    height                   = (typeof height === "undefined" || height == null) ? 300 : parseInt(height);
    type                     = (typeof type === "undefined") ? null : type;
    subtitle                 = (typeof subtitle === "undefined") ? null : subtitle;
    button_name              = (typeof button_name === "undefined") ? "Close" : button_name;
    callback                 = (typeof callback === "undefined") ? null : callback;
    callback_first_parameter = (typeof callback_first_parameter === "undefined") ? null : callback_first_parameter;

    var html = "<div id='swcfpc_dialog_container'>";

    if(type == "warning")
        html += '<div id="swcfpc_subtitle" class="swcfpc_warning"><span class="icona_dialog fa fa-exclamation-triangle"></span><br/>';
    else if(type == "error")
        html += '<div id="swcfpc_subtitle" class="swcfpc_error"><span class="icona_dialog fa fa-exclamation-circle"></span><br/>';
    else if(type == "info")
        html += '<div id="swcfpc_subtitle" class="swcfpc_info"><span class="icona_dialog fa fa-info-circle"></span><br/>';
    else if(type == "success")
        html += '<div id="swcfpc_subtitle" class="swcfpc_success"><span class="icona_dialog fa fa-check"></span><br/>';


    if(subtitle != null)
        html += subtitle;

    if(type != null)
        html += "</div>";

    html += "<div id='swcfpc_dialog_msg_wrapper'>"+content+"</div>";

    html += "</div>";

    jQuery(html).dialog({
        height: height,
        width: width,
        modal: true,
        title: title,
        buttons: [
            {
                html: button_name,
                click: function() {

                    if( callback != null ) {

                        if( callback_first_parameter != null ) {
                            callback( callback_first_parameter );
                        }
                        else {
                            callback();
                        }

                    }
                    jQuery( this ).dialog('destroy').remove();

                }
            }
        ]
    });


}


function swcfpc_purge_whole_cache() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_purge_whole_cache&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success")
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_purge_single_post_cache( post_id ) {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();
    var dataJson = new Object();

    dataJson["post_id"] = post_id;
    dataJson = encodeURIComponent( JSON.stringify(dataJson) );

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_purge_whole_cache&security="+ajax_nonce+"&data="+dataJson,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success")
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_test_page_cache() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_test_page_cache&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success")
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_enable_page_cache() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_enable_page_cache&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success", null, "Ok", swcfpc_refresh_page)
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_disable_page_cache() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_disable_page_cache&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success", null, "Ok", swcfpc_refresh_page)
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_reset_all() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_reset_all&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success", null, "Ok", swcfpc_refresh_page)
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_clear_logs() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_clear_logs&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success")
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_download_logs() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_download_logs&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'><a href='"+data.logs_url+"' target='_blank'>"+data.success_msg+"</a></div>", null, null, "success")
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


function swcfpc_start_preloader() {

    var ajax_nonce = jQuery("#swcfpc-ajax-nonce").html();

    swcfpc_lock_screen();

    jQuery.ajax({
        type: "POST",
        url: swcfpc_ajax_url,
        data: "action=swcfpc_preloader_start&security="+ajax_nonce,
        dataType: "json",
        success: function (data) {

            swcfpc_unlock_screen();

            if(data.status == "ok") {
                swcfpc_display_ok_dialog("Success", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.success_msg+"</div>", null, null, "success")
            }
            else {
                swcfpc_display_ok_dialog("Error", "<div class='swcfpc_dialog_box_msg_wrapper'>"+data.error+"</div>", null, null, "error")
            }

        },

        failure: function (msg, ajaxOptions, thrownError) {
            alert('Error: ' + msg.status + ' ' + msg.statusText + ' ' + thrownError);
            swcfpc_unlock_screen();
        }

    });

    return false;

}


jQuery(document).on("click", "#swcfpc_clear_logs", function() {
    swcfpc_clear_logs();
    return false;
});


jQuery(document).on("click", "#swcfpc_download_logs", function() {
    swcfpc_download_logs();
    return false;
});


jQuery(document).on("click", "#swcfpc_start_preloader", function() {
    swcfpc_start_preloader();
    return false;
});


jQuery(document).on("click", "#wp-admin-bar-wp-cloudflare-super-page-cache-toolbar-purge-single a", function() {

    var post_id = jQuery(this).attr("href").replace("#", "");

    swcfpc_purge_single_post_cache( post_id );
    return false;

});

jQuery(document).on("click", ".swcfpc_action_row_single_post_cache_purge", function() {

    var post_id = jQuery(this).attr("data-post_id");

    swcfpc_purge_single_post_cache( post_id );
    return false;

});


jQuery(document).on("click", "#wp-admin-bar-wp-cloudflare-super-page-cache-toolbar-purge-all a", function() {
    swcfpc_purge_whole_cache();
    return false;
});


jQuery(document).on("submit", "#swcfpc_form_purge_cache", function() {
    swcfpc_purge_whole_cache();
    return false;
});


jQuery(document).on("submit", "#swcfpc_form_test_cache", function() {
    swcfpc_test_page_cache();
    return false;
});


jQuery(document).on("submit", "#swcfpc_form_enable_cache", function() {
    swcfpc_enable_page_cache();
    return false;
});


jQuery(document).on("submit", "#swcfpc_form_disable_cache", function() {
    swcfpc_disable_page_cache();
    return false;
});


jQuery(document).on("submit", "#swcfpc_form_reset_all", function() {
    swcfpc_reset_all();
    return false;
});


jQuery(document).on("change", "select[name=swcfpc_cf_auth_mode]", function() {

    var method = jQuery(this).val();

    if( method == 0 ) { // API Key
        jQuery(".api_token_method").addClass("swcfpc_hide");
        jQuery(".api_key_method").removeClass("swcfpc_hide");
    }
    else { // API Token
        jQuery(".api_token_method").removeClass("swcfpc_hide");
        jQuery(".api_key_method").addClass("swcfpc_hide");
    }

});