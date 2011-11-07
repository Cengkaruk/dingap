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
          '<p style=\"text-align: left;\">' +
              '<span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin:0 7px 0px 0;\"></span>' + message +
          '</p>' +
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
                }
            }
        }
    });
    $('.ui-dialog-titlebar-close').hide();
}

function theme_clearos_is_authenticated(action_type)
{

    data_payload = 'ci_csrf_token=' + $.cookie('ci_csrf_token');
    $('#sdn_login_dialog_message_bar').html('');
    // Password being sent - login attempt
    // Email being sent - lost/reset password attempt
    if (action_type == 'login') {
        if ($('#sdn_password').val() == '') {
            $('#sdn_login_dialog_message_bar').html(lang_sdn_password_invalid);
            $('.autofocus').focus();
            return;
        } else {
            data_payload += '&password=' + $('#sdn_password').val();
        }
    } else if (action_type == 'lost_password') {
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

function theme_clearos_on_page_ready()
{
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
    // TODO move to global scope
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
                    theme_clearos_is_authenticated(($('#sdn_lost_password_group').is(':visible') ? 'lost_password' : 'login'));
                }
            },
            'close': {
                text: lang_close,
                click: function() {
                    // Go back to basename
                    $(this).dialog('close');
                    regex = /\/app\/(\w+)\/.*/;
                    my_basename = document.location.pathname.match(regex);
                    if (my_basename == null)
                        return;
                    window.location = 'https://' + document.location.host + '/app/' + my_basename[1];
                }
            }
        }
    });

    $('input#sdn_password').keyup(function(event) {
        if (event.keyCode == 13)
            theme_clearos_is_authenticated('login');
    });

    $('input#sdn_email').keyup(function(event) {
        if (event.keyCode == 13)
            theme_clearos_is_authenticated('lost_password');
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
