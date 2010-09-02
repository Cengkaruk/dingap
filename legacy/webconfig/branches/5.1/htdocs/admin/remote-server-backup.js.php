<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/ClearSdnService.class.php");
require_once("../../api/ClearSdnStore.class.php");

WebAuthenticate();

header('Content-Type: application/x-javascript');
readfile(sprintf('../../js/%s', str_replace('.php', '', basename(__FILE__))));
readfile('../../js/common.js');
readfile('../js/webtoolkit.url.js');
readfile('../js/webtoolkit.base64.js');
readfile('../../js/filebrowser.js');

$sdn = new ClearSdnService();;
$store = new ClearSdnStore();;
echo "
  $(document).ready(function() {
    $('#sdn-confirm-purchase').dialog({
      autoOpen: false,
      resizable: false,
      modal: true,
      closeOnEscape: true,
      width: 450,
      open: function(event, ui) {
          $('.sdn-errmsg').hide();
          if ($('input[name=pid]:checked').val() != 0) {
            $('#sdn-confirm-purchase').dialog('option', 'buttons', {
              '" . CLEARSDN_STORE_LANG_CONFIRM . "': function() {
                var confirmButton = $('.ui-dialog-buttonpane button:contains(\'" . LOCALE_LANG_CONFIRM . "\')');
                var cancelButton = $('.ui-dialog-buttonpane button:contains(\'" . LOCALE_LANG_CANCEL . "\')');
                var orig = $('#sdn-confirm-purchase-content').html();
                // Hide any existing error messages
                $('.sdn-errmsg').hide();
                confirmButton.attr('disabled', 'disabled');
                confirmButton.addClass('ui-state-disabled');
                cancelButton.attr('disabled', 'disabled');
                cancelButton.addClass('ui-state-disabled');
		// Set password first
		var password = $('#password').val();
		var po = $('#po').val();
		var method = $('#method').val();
		// Then update content
                $('#sdn-confirm-purchase-content').html('" . CLEARSDN_STORE_LANG_CONFIRM_PURCHASE . "<div style=\'padding: 10 0 10 0; text-align: center;\'><img src=\'/images/icon-clearsdn-confirming-purchase.png\' alt=\'\'></div><div style=\'padding: 10 0 10 0; text-align: center;\'><img src=\'/templates/base/images/icons/16x16/icon-loading.gif\'></div>');
                $.ajax({
                  type: 'POST',
                  url: 'clearsdn-ajax.php',
                  data: 'action=buy&method=' + (method === undefined ? '' : method) + (password === undefined ? '' : '&password=' + password) +
                        (po === undefined ? '' : '&po=' + po) + '&pid=' + $('input[name=pid]:checked').val(),
                  success: function(html) {
                    cancelButton.html('" . LOCALE_LANG_CLOSE . "');
                    cancelButton.attr('disabled', false);
                    cancelButton.removeClass('ui-state-disabled');

                    $('#sdn-confirm-purchase-content').html('<div>" . CLEARSDN_STORE_LANG_PURCHASE_COMPLETE . "</div><div style=\'padding: 10 0 10 0; text-align: center;\'><img src=\'/images/icon-clearsdn-purchase-success.png\' alt=\'\'></div>');
                    $('#sdn-confirm-purchase-content').after('<div>' + html + '</div>');
                    location.reload(true);
                  },
                  error: function(xhr, text, err) {
                    $('#sdn-confirm-purchase-content').html(orig);
                    confirmButton.attr('disabled', false);
                    confirmButton.removeClass('ui-state-disabled');
                    cancelButton.attr('disabled', false);
                    cancelButton.removeClass('ui-state-disabled');
                    $('#sdn-confirm-purchase-content').before('<div class=\'sdn-errmsg\' style=\'padding:0 0 20 0;\'>' + xhr.responseText.toString() + '</div>');
                    // TODO...should need below hack...edit templates.css someday
                    $('.ui-state-error').css('max-width', '420px'); 
                  }
                });
              },
              '" . LOCALE_LANG_CANCEL . "': function() {
                $(this).dialog('close');
              }
            });
          } else {
            $('#sdn-confirm-purchase-content').html('<div style=\'text-align:center; padding: 20 20 5 20\'>" . CLEARSDN_SERVICE_LANG_SELECT_SUBSCRIPTION . "</div><div style=\'text-align:center; padding: 5 20 10 20\'><img src=\'/images/icon-clearsdn-no-product.png\' alt=\'\'></div>');
            $('#sdn-confirm-purchase').dialog('option', 'buttons', {
              '" . LOCALE_LANG_CLOSE . "': function() {
                $(this).dialog('close');
              }
            });
          }
      },
      close: function(event, ui) {
      }
    });
  });

";

// vim: syntax=php ts=4
?>
