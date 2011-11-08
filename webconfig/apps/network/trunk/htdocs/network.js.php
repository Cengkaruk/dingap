<?php

/**
 * Network ajax helper.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Ajax
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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

clearos_load_language('base');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    lang_yes = '<?php echo lang("base_yes"); ?>';
    lang_no = '<?php echo lang("base_no"); ?>';
    lang_unknown = '<?php echo lang("base_unknown"); ?>';
    lang_megabits_per_second = '<?php echo lang("base_megabits_per_second"); ?>';

    // Network interface configuration
    //--------------------------------

    if ($('#role').length != 0)  {
        setInterfaceFields();
        setGateway();
        getInterfaceInfo();

        $('#role').change(function() {
            setGateway();
        });

        $('#bootproto').change(function() {
            setInterfaceFields();
            setGateway();
        });

    // Summary page
    //-------------

    } else if ($('#network_summary').length != 0)  {
        getAllInteraceInfo();
    }
});

/**
 * Ajax call to get network information for all interfaces
 */

function getAllInteraceInfo() {

    $.ajax({
        url: '/app/network/get_all_info',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            showAllInterfaceInfo(payload);
            window.setTimeout(getAllInteraceInfo, 1000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getAllInteraceInfo, 1000);
        }
    });
}

/**
 * Ajax call to get network information.
 */

function getInterfaceInfo() {
    var iface = $('#interface').html();

    $.ajax({
        url: '/app/network/get_info/' + iface,
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            showInterfaceInfo(payload);
            window.setTimeout(getInterfaceInfo, 1000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getInterfaceInfo, 1000);
        }
    });
}

/**
 * Updates network information (IP, link) for all interfaces
 */

function showAllInterfaceInfo(payload) {
    for (var iface in payload) {
        var link_text = (payload[iface].link) ? lang_yes : lang_no;

        $('#role_' + iface).html(payload[iface].roletext);
        $('#bootproto_' + iface).html(payload[iface].bootprototext);
        $('#ip_' + iface).html(payload[iface].address);
        $('#link_' + iface).html(link_text);
    }
}

/**
 * Updates network information (IP, link)
 */

function showInterfaceInfo(payload) {
    var link_text = (payload.link) ? lang_yes : lang_no;
    var speed_text = (payload.speed > 0) ? payload.speed + ' ' + lang_megabits_per_second : lang_unknown;

    $('#link').html(link_text);
    $('#speed').html(speed_text);
}

/**
 * Sets visibility of gateway field.
 *
 * The gateway field should be shown on external interfaces with static IPs.
 */

function setGateway() {
    role = $('#role').val();
    type = $('#bootproto').val();

    if (type == 'static') {
        if (role == 'EXTIF')
            $('#gateway_field').show();
        else
            $('#gateway_field').hide();
    }
}

/**
 * Sets visibility of network interface fields.
 */

function setInterfaceFields() {
    // Static
    $('#ipaddr_field').hide();
    $('#netmask_field').hide();
    $('#gateway_field').hide();

    // DHCP
    $('#hostname_field').hide();
    $('#dhcp_dns_field').hide();

    // PPPoE
    $('#username_field').hide();
    $('#password_field').hide();
    $('#mtu_field').hide();
    $('#pppoe_dns_field').hide();

    type = $('#bootproto').val();

    if (type == 'static') {
        $('#ipaddr_field').show();
        $('#netmask_field').show();
        $('#gateway_field').show();
    } else if (type == 'dhcp') {
        $('#hostname_field').show();
        $('#dhcp_dns_field').show();
    } else if (type == 'pppoe') {
        $('#username_field').show();
        $('#password_field').show();
        $('#mtu_field').show();
        $('#pppoe_dns_field').show();
    }
}

// vim: ts=4 syntax=javascript
