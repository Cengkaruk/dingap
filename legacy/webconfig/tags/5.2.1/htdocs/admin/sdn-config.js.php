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

header('Content-Type: application/x-javascript');
// TODO...use a webconfig function here?  Don't want to call this unless we are logged in
if (file_exists("../../api/ClearSdnService.class.php") && (isset($_SESSION['system_login']) || isset($_SESSION['user_login'])))
echo "
  $(document).ready(function() {
    $.ajax({
      type: 'POST',
      url: 'clearsdn-ajax.php',
      data: 'action=getServiceSummary',
      success: function(html) {
  	  $('#clearsdn-splash').remove();
  	  $('#clearsdn-alerts').append(html);
      },
      error: function(xhr, text, err) {
  	  $('#whirly').html(xhr.responseText.toString());
      }
    });
  });
";

// vim: syntax=php ts=4
?>

