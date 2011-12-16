
    
// Credit for this function belongs to http://stackoverflow.com/questions/5047498/how-do-you-animate-the-value-for-a-jquery-ui-progressbar
$(function() {
    $.fn.animate_progressbar = function(value, duration, easing, complete) {
        if (value == null)
            value = 20;
        if (duration == null)
            duration = 1000;
        if (easing == null)
            easing = 'swing';
        if (complete == null)
            complete = function(){};
        var progress = this.find('.ui-progressbar-value');
        progress.stop(true).animate({
            width: value + '%'
        }, duration, easing, function(){
            if(value>=99.5){
                progress.addClass('ui-corner-right');
            } else {
                progress.removeClass('ui-corner-right');
            }
            complete();
        });
    }
});
    
$(function(){
	// Anchors
	$(".theme-anchor").button();

	// Buttons
	$(".theme-form-submit").button();

	// Anchor and button sets
	$(".theme-button-set").buttonset();

	// Progress bar
	/*
	$(".theme-progress-bar").progressbar({ 
		value: 0
	});
*/

    // Charts
    $.jqplot.config.enablePlugins = true;
   
	// Forms / FIXME
	$('fieldset').addClass('ui-widget-content ui-corner-all');
	$('legend').addClass('ui-widget-header ui-corner-all');
	$('label').addClass('ui-widget ui-corner-all');
});

function theme_clearos_dialog_box(id, title, message, options)
{
    $('#theme-page-container').append('<div id=\"' + id + '\" title=\"' + title + '\">' +
          '<div class=\"dialog_alert_icon\"></div>' +
          '<div class=\"dialog_alert_text\">' + message + '</div>' +
      '</div>'
    );
    if (options == undefined)
        options = new Object();
    $('#' + id).dialog({
        modal: true,
        resizable: false,
        draggable: false,
        buttons: {
            'close': {
                text: lang_close,
                click: function() {
                    $(this).dialog('close');
                    if (options.reload_on_close)
                        window.location.reload();
                    else if (options.redirect_on_close)
                        window.location = options.redirect_on_close;
                }
            }
        }
    });
    $('.ui-dialog-titlebar-close').hide();
}

function theme_clearos_is_authenticated()
{

    data_payload = 'ci_csrf_token=' + $.cookie('ci_csrf_token');
    $('#sdn_login_dialog_message_bar').html('');
    // Password being sent - login attempt
    // Email being sent - lost/reset password attempt
    if (auth_options.action_type == 'login') {
        if ($('#sdn_password').val() == '') {
            $('#sdn_login_dialog_message_bar').html(lang_sdn_password_invalid);
            $('.autofocus').focus();
            return;
        } else {
            data_payload += '&password=' + $('#sdn_password').val();
        }
    } else if (auth_options.action_type == 'lost_password') {
        if ($('#sdn_email').val() == '') {
            $('#sdn_login_dialog_message_bar').html(lang_sdn_email_invalid);
            $('.autofocus').focus();
            return;
        } else {
            data_payload += '&email=' + $('#sdn_email').val();
        }
    }

    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: data_payload,
        url: '/app/marketplace/ajax/is_authenticated',
        success: function(data) {
            if (data.code == 0 && data.authorized) {
                // Might have pages where account is displayed (eg. Marketplace)
                $('#display_sdn_username').html(data.sdn_username);
                // Only case where authorized is true.
                $('#sdn_login_dialog').dialog('close');
                // If we're logged in and there is a 'check_sdn_edit' function defined on page, check to see if we need to get settings
                if (window.check_sdn_edit)
                    check_sdn_edit();
                if (auth_options.action_type == 'login' && auth_options.reload_after_auth)
                    window.location.reload();
            } else if (data.code == 0 && !data.authorized) {
                // Auto-populate username
                $('#sdn_username').val(data.sdn_username);
                // Open dialog and change some look and feel
                $('#sdn_login_dialog').dialog('open');
                $('.ui-dialog-titlebar-close').hide();
                $('.autofocus').focus();

                // If email was submitted...reset was a success...
                if (data.email != undefined) {
                    $('#sdn_lost_password_group').hide();
                    $('#sdn_login_dialog_message_bar').css('color', '#686868');
                    $('#sdn_login_dialog_message_bar').html(lang_sdn_password_reset + ': ' + data.email);
                    $('.ui-dialog-buttonpane button:contains(\'' + lang_reset_password_and_send + '\') span').parent().hide();
                    return;
                }
                
            } else if (data.code == 10) {
                // Code 10 is an invalid email
                $('#sdn_login_dialog_message_bar').html(lang_sdn_email_invalid);
            } else if (data.code == 11) {
                // Code 11 is an email mismatch for lost password
                $('#sdn_login_dialog_message_bar').html(lang_sdn_email_mismatch);
            } else if (data.code > 0) {
                $('#sdn_login_dialog_message_bar').html(lang_sdn_password_invalid);
            } else if (data.code < 0) {
                theme_clearos_dialog_box('login_failure', lang_warning, data.errmsg);
                return;
            }
            $('.autofocus').focus();
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                theme_clearos_dialog_box('some-error', lang_warning, xhr.responseText.toString());
            $('#sidebar_setting_status').html('---');
        }
    });
}

function theme_clearos_on_page_ready(my_location)
{
    get_marketplace_data(my_location.basename);

    // Insert login dialog
    $('#theme-page-container').append(
        '<div id=\'sdn_login_dialog\' title=\'' + sdn_org + ' ' + lang_sdn_authentication_required + '\' class=\'theme-hidden\'> \
            <p style=\'text-align: left\'>' + lang_sdn_authentication_required_help + '</p> \
        <div style=\'padding: 0 170 10 0; text-align: right\'><span>' + lang_username + '</span> \
            <input id=\'sdn_username\' type=\'text\' style=\'width: 120px\' readonly=\'readonly\' name=\'sdn_username\' value=\'\' /> \
        </div> \
        <div style=\'padding: 0 170 10 0; text-align: right\' id=\'sdn_password_group\'> \
            <span>' + lang_password + '</span> \
            <input id=\'sdn_password\' type=\'password\' style=\'width: 120px\' name=\'password\' value=\'\' class=\'autofocus\' /> \
            <div style=\'padding: 2 2 0 0;\'> \
                <a href=\'#\' id=\'sdn_forgot_password\' style=\'font-size: 7pt\'>' + lang_forgot_password + '</a> \
            </div> \
        </div> \
        <div style=\'padding: 0 170 10 0; text-align: right\' id=\'sdn_lost_password_group\' class=\'theme-hidden\'> \
            <span>' + lang_sdn_email + '</span> \
            <input id=\'sdn_email\' type=\'text\' style=\'width: 120px\' name=\'sdn_email\' value=\'\' class=\'autofocus\' /> \
        </div> \
        <div style=\'padding: 0 170 10 0; text-align: right\' id=\'sdn_login_dialog_message_bar\'></div> \
        </div>'
    );

    $('#sdn_login_dialog').dialog({
        autoOpen: false,
        bgiframe: true,
        title: false,
        modal: true,
        resizable: false,
        draggable: false,
        closeOnEscape: false,
        height: 250,
        width: 450,
        buttons: {
            'authenticate': {
                text: lang_authenticate,
                click: function() {
                    auth_options.action_type = 'login';

                    if ($('#sdn_lost_password_group').is(':visible'))
                        auth_options.action_type = 'lost_password';
                    theme_clearos_is_authenticated();
                }
            },
            'close': {
                text: lang_close,
                click: function() {
                    // Go back to basename
                    $(this).dialog('close');
                    // If not at default controller, reload page
                    if (!my_location.default_controller)
                        return;
                    window.location = 'https://' + document.location.host + '/app/' + my_location.basename;
                }
            }
        }
    });

    $('input#sdn_password').keyup(function(event) {
        if (event.keyCode == 13) {
            auth_options.action_type = 'login';
            theme_clearos_is_authenticated();
        }
    });

    $('input#sdn_email').keyup(function(event) {
        if (event.keyCode == 13) {
            auth_options.action_type = 'lost_password';
            theme_clearos_is_authenticated();
        }
    });

    $('a#sdn_forgot_password').click(function (e) {
        e.preventDefault();
        $('#sdn_login_dialog_message_bar').html('');
        $('#sdn_password_group').remove();
        $('#sdn_lost_password_group').show();
        $('.autofocus').focus();
        $('.ui-dialog-buttonpane button:contains(\'' + lang_authenticate + '\') span').text(lang_reset_password_and_send);
    });
}

function get_marketplace_data(basename) {
    $.ajax({
        url: '/app/marketplace/ajax/get_app_details/' + basename,
        method: 'GET',
        dataType: 'json',
        success : function(json) {
            if (json.code != undefined && json.code != 0) {
                // Could put real message for codes < 0, but it gets a bit technical
                if (json.code < 0)
                    $('#sidebar_additional_info').html(lang_marketplace_connection_failure);
                else
                    $('#sidebar_additional_info').html(json.errmsg);
                $('#sidebar_additional_info').css('color', 'red');
            } else {
                // This was just a placeholder
                $('#sidebar_additional_info_row').hide();

                // We add rows in the reverse order to keep this section under the Version/Vendor

                // Redemption period
                if (json.license_info != undefined && json.license_info.redemption != undefined && json.license_info.redemption == true) {
                    $('#sidebar_additional_info_row').after(
                        c_row(
                            lang_status,
                            '<span style=\'color: red\'>' + lang_marketplace_redemption + '</span>'
                        )
                    );
                }

                // No Subscription
                if (json.license_info != undefined && json.license_info.no_subscription != undefined && json.license_info.no_subscription == true) {
                    $('#sidebar_additional_info_row').after(
                        c_row(
                            lang_status,
                            '<span style=\'color: red\'>' + lang_marketplace_expired_no_subscription + '</span>'
                        )
                    );
                }

                // Subscription?  A unit of 100 or greater represents a recurring subscription
                if (json.license_info != undefined && json.license_info.unit >= 100) {
                    var bill_cycle = lang_marketplace_billing_cycle_monthly;
                    if (json.license_info.unit == 1000)
                        bill_cycle = lang_marketplace_billing_cycle_yearly;
                    else if (json.license_info.unit == 2000)
                        bill_cycle = lang_marketplace_billing_cycle_2_years;
                    else if (json.license_info.unit == 3000)
                        bill_cycle = lang_marketplace_billing_cycle_3_years;
    
                    $('#sidebar_additional_info_row').after(
                        c_row(
                            lang_marketplace_billing_cycle,
                            bill_cycle
                        )
                    );
                    if (json.license_info.expire != undefined) {
                        $('#sidebar_additional_info_row').after(
                            c_row(
                                lang_marketplace_renewal_date,
                                $.datepicker.formatDate('M d, yy', new Date(json.license_info.expire))
                            )
                        );
                    }
                }

                // Version updates
                if (!json.up2date) {
                    $('#sidebar_additional_info_row').after(
                        c_row(
                            lang_marketplace_upgrade,
                            json.latest_version
                        )
                    );
                }
            }
            if (json.complementary_apps != undefined && json.complementary_apps.length > 0) {
                comp_apps = '<h3>' + lang_marketplace_recommended_apps + '</h3>' +
                    '<div>' + lang_marketplace_sidebar_recommended_apps.replace('APP_NAME', '<b>' + json.name + '</b>') + ':</div>';
		comp_apps += '<table border=\'0\' width=\'100%\'>';
                for (index = 0 ; index < json.complementary_apps.length; index++) {
                    comp_apps += '<tr><td width=\'5\' valign=\'top\'>&#8226;</td><td width=\'60%\'><a href=\'/app/marketplace/view/' +
                        json.complementary_apps[index].basename + '\'>' +
                        json.complementary_apps[index].name + '</a></td><td width=\'35%\' valign=\'top\'>\n';
                    for (var counter = 5 ; counter > json.complementary_apps[index].rating; counter--)
                        comp_apps += '<div class=\'star_off\' />';
                    for (var counter = 0 ; counter < json.complementary_apps[index].rating; counter++)
                        comp_apps += '<div class=\'star_on\' />';
                    comp_apps += '</td></tr>';
                }
                comp_apps += '</table>';
                $('#sidebar-recommended-apps').html(comp_apps);
            }
        },
        error: function (xhr, text_status, error_thrown) {
            // FIXME: Firebug issue?
            //if (xhr['abort'] == undefined)
            //    $('#sidebar_additional_info').html(xhr.responseText.toString());
        }
    });
}

function c_row(field, value) {
    // TODO style should be in CSS
    return '<tr><td><b>' + field + '</b></td><td>' + value + '</td></tr>';
}
