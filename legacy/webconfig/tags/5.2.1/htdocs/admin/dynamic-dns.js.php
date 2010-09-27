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

require_once("../../gui/Webconfig.inc.php");
require_once(GlobalGetLanguageTemplate('clearsdn-ajax.php'));
WebAuthenticate();
header('Content-Type: application/x-javascript');
echo "
  $(document).ready(function() {
    $.ajax({
      type: 'POST',
      url: 'clearsdn-ajax.php',
      data: 'action=getDynamicDnsSettings',
      success: function(html) {
        $('#clearsdn-splash').remove();
        $('#clearsdn-overview').append(html);
      },
      error: function(xhr, text, err) {
        $('#whirly').html(xhr.responseText.toString());
        // TODO...should need below hack...edit templates.css someday
        $('.ui-state-error').css('max-width', '700px'); 
      }
    });
  });
  function toggleControls() {
    if (document.getElementById('enabled').value == 1) {
      document.getElementById('subdomain').disabled = false;
      document.getElementById('domain').disabled = false;
      document.getElementById('ipField').innerHTML = '" . WEB_LANG_IP . "';
      document.getElementById('ip').style.width = '100px';
    } else {
      document.getElementById('subdomain').disabled = true;
      document.getElementById('domain').disabled = true;
      document.getElementById('ipField').innerHTML = '" . WEB_LANG_ADDRESS . "';
      document.getElementById('ip').style.width = '220px';
    }
  }

";

// vim: syntax=php ts=4
?>

