<?php

/**
 * Accounts ajax helper.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Ajax
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
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

clearos_load_language('accounts');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {
    $("#accounts_status").html('');
    $("#accounts_initialization").hide();

    getAccountsInfo();

    function getAccountsInfo() {
        $.ajax({
            url: '/app/accounts/status/get_info',
            method: 'GET',
            dataType: 'json',
            success : function(payload) {
                window.setTimeout(getAccountsInfo, 2000);
                showAccountsInfo(payload);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                window.setTimeout(getAccountsInfo, 2000);
            }
        });
    }

    function showAccountsInfo(payload) {
        var status_output = '';

        if (payload.status == 'uninitialized') {
            status_output = '<?php echo lang("accounts_account_system_is_not_initialized"); ?>';
            $("#accounts_initialization").show();
        } else if (payload.status == 'offline') {
            status_output = '<?php echo lang("accounts_account_information_is_offline"); ?>';
            $("#accounts_initialization").hide();
        } else {
            status_output = '<?php echo lang("accounts_account_information_is_online"); ?>';
            $("#accounts_initialization").hide();
        }

        $("#accounts_status").html(status_output);
	}
});

// vim: ts=4 syntax=javascript
