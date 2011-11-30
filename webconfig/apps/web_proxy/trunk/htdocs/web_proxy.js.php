<?php
/**
 * Squid web proxy class.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('web_proxy');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

// Translations
//-------------

lang_resetting = '<?php echo lang("web_proxy_clearing_the_cache"); ?>';
lang_ready = '<?php echo lang("web_proxy_ready"); ?>';

$(document).ready(function() {
    // Cache Reset
    //------------

    $("#result_box").hide();

	$("#reset_cache").click(function(){
		$("#cache_status_text").html('<div class="theme-loading-normal">' + lang_resetting + '</div>');

		$.ajax({
			url: '/web_proxy/cache/reset',
			method: 'GET',
			dataType: 'json',
			success : function(payload) {
		        $("#cache_status_text").html(lang_ready);
            },
			error: function (XMLHttpRequest, textStatus, errorThrown) {
			}
		});
	});

    // Proxy warning
    //--------------

    if ($("#warning_text").html()) {
        if ($("#ip_text").html().length != 0)
            $("#ip_field").show();
        else
            $("#ip_field").hide();

        if ($("#warning_text").html().length != 0) {
            $.ajax({
                url: '/app/web_proxy/warning/get_status',
                method: 'GET',
                dataType: 'json',
                success : function(payload) {
                    var status_class = '';

                    if (payload.status_code == 'online')
                        status_class = 'ok'; 
                    else
                        status_class = 'alert'; 

                    // FIXME: need a class to highlight bad/good state
                    $("#status_text").html('<span class="' + status_class + '">' + payload.status_message + '</span>');
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                }
            });
        }
    }
});

function showData(payload) {
    if (payload.error_message) {
        $("#result").html(payload.error_message);
    } else {
        $("#result").html(payload.diff);
        $("#date").html(payload.date);
        $("#time").html(payload.time);
        $("#result_box").show();
    }
}

// vim: ts=4 syntax=javascript
