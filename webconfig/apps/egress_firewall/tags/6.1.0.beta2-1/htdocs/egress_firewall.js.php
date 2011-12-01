<?php

/**
 * Javascript helper for Egress Firewall.
 *
 * @category   Apps
 * @package    Egress_Firewall
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/egress_firewall/
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

clearos_load_language('egress_firewall');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "

$(document).ready(function() {

    if ($(location).attr('href').match('.*mode$') != null) {
        $('#state').css('width', '240');
    } else if ($(location).attr('href').match('.*\/port\/.*$') != null) {
        $('#port').css('width', '50');
        $('#range_from').css('width', '50');
        $('#range_to').css('width', '50');
    }
    $('tbody', $('#sidebar_summary_table')).append(
      '<tr>' +
      '  <td><b>" . lang('egress_firewall_mode') . "</b></td>' +
      '  <td id=\'clearos_daemon_status\'><div class=\'theme-loading-small\'></div></td>' +
      '</tr>' +
      '<tr>' +
      '  <td><b>" . lang('base_action') . "</b></td>' +
      '  <td>' +
      '<a class=\'theme-button-set-first theme-button-set-last theme-anchor theme-anchor-add theme-anchor-important ui-button ui-widget ui-state-default ui-button-text-only ui-corner-left ui-corner-right\' href=\'/app/egress_firewall/mode\' role=\'button\' aria-disabled=\'false\'><span class=\'ui-button-text\'></span>" . lang('base_configure') . "</a>' + 
      '</td>' +
      '</tr>'
    );

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/egress_firewall/ajax/get_egress_state',
        data: '',
        success: function(json) {
            $('#clearos_daemon_status').html(json.state);
        },
        error: function(xhr, text, err) {
        }
    });
});

";
// vim: syntax=php ts=4
