<?php

/**
 * Disk usage javascript helper.
 *
 * @category   Apps
 * @package    Disk_Usage
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/disk_usage/
 */

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

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('disk_usage');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    lang_busy = '<?php echo lang("disk_usage_updating_disk_usage_information"); ?>';
    reload = false;

    $("#working").hide();
    $("#usage").hide();

    $("#working").html('<div class="theme-loading-normal">' + lang_busy + '</div>');

    getUsageData();

    function getUsageData() {
        $.ajax({
            url: '/app/disk_usage/get_state',
            method: 'GET',
            dataType: 'json',
            success : function(payload) {
                showUsageData(payload);
                window.setTimeout(getUsageData, 1000);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                window.setTimeout(getUsageData, 1000);
            }

        });
    }

	function showUsageData(payload) {
        if (payload.state) {
            $("#working").hide();
            if (reload)
                window.location = '/app/disk_usage/';
            $("#usage").show();
        } else {
            $("#working").show();
            $("#usage").hide();
            reload = true;
        }
	}
});

// vim: ts=4 syntax=javascript
