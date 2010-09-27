<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../api/ClearSdnService.class.php");
require_once("../../gui/Webconfig.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));
WebAuthenticate();
header('Content-Type: application/x-javascript');
echo "
  $(document).ready(function() {
    $('#sdn-checkout').dialog({
      autoOpen: false,
      resizable: false,
      modal: true,
      closeOnEscape: true,
      width: 450,
      open: function(event, ui) {
          $('.sdn-errmsg').hide();
          $('#sdn-checkout').dialog('option', 'buttons', {
            '" . LOCALE_LANG_CONFIRM . "': function() {
              var orig = $('#sdn-checkout-content').html();
              var confirmButton = $('.ui-dialog-buttonpane button:contains(\'" . LOCALE_LANG_CONFIRM . "\')');
              var cancelButton = $('.ui-dialog-buttonpane button:contains(\'" . LOCALE_LANG_CANCEL . "\')');
              // Set password first
              var password = $('#password').val();
              // Now update content
              $('#sdn-checkout-content').html('" . addslashes(WEB_CART_LANG_ITEMS_UPLOADING) . addslashes("<div style='padding: 10 0 10 0; text-align: center;'><img src='/images/icon-clearsdn-moving-cart-items.png'></div><div style='padding: 10 0 10 0; text-align: center;'><img src='/templates/base/images/icons/16x16/icon-loading.gif'></div>") . "');
              // Hide any existing error messages
              $('.sdn-errmsg').hide();
              confirmButton.attr('disabled', 'disabled');
              confirmButton.addClass('ui-state-disabled');
              cancelButton.attr('disabled', 'disabled');
              cancelButton.addClass('ui-state-disabled');
              $.ajax({
                type: 'POST',
                url: 'clearsdn-ajax.php',
                data: 'action=uploadCart' + (password === undefined ? '' : '&password=' + password),
                success: function(html) {
                  cancelButton.html('" . LOCALE_LANG_CLOSE . "');
                  cancelButton.attr('disabled', false);
                  cancelButton.removeClass('ui-state-disabled');

                  //$('#sdn-checkout-content').html('<div>" . addslashes(WEB_LANG_CONTENTS_UPLOADED) . "</div>" . addslashes("<div style='padding: 10 0 10 0; text-align: center;'><img src='/images/icon-clearsdn-transfer-success.png' alt=''></div>") . "');
                  location.replace(html);
                  // Pop-Ups pain in most browsers and loses continuity
                  //window.open(html, '_blank');
                  //$(this).dialog('close');
                },
                error: function(xhr, text, err) {
                  $('#sdn-checkout-content').html(orig);
                  confirmButton.attr('disabled', false);
                  confirmButton.removeClass('ui-state-disabled');
                  cancelButton.attr('disabled', false);
                  cancelButton.removeClass('ui-state-disabled');
                  $('#sdn-checkout-content').before('<div class=\'sdn-errmsg\' style=\'padding:0 0 20 0;\'>' + xhr.responseText.toString() + '</div>" . addslashes("<div style='padding: 10 0 10 0; text-align: center;'><img src='/images/icon-clearsdn-transfer-fail.png' alt=''></div>") . "');
                  // TODO...should need below hack...edit templates.css someday
                  $('.ui-state-error').css('max-width', '420px'); 
                }
              });
            },
            '" . LOCALE_LANG_CANCEL . "': function() {
              $(this).dialog('close');
            }
          });
      },
      close: function(event, ui) {
        $('#working-whirly').remove();
      }
    });
  });

  function checkoutNow() {
    $('#sdn-checkout').dialog('open');
  }

";

// vim: syntax=php ts=4
?>

