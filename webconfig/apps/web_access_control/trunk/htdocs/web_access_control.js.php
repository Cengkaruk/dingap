<?php

/**
 * Javascript helper for Web Access Control.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/web_access_control/
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

clearos_load_language('web_access_control');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "


$(document).ready(function() {
  // Initialize some inputboxes
  change_id_selector();
  $('#ident').change(function(e) {
    change_id_selector();
  });
  $('#time').change(function(e) {
    if ($('#time').val() == -1)
      window.location = '/app/web_access_control/add_edit_time';
  });
});


function change_id_selector() {
  // Hide all rows
  $('#byuser_field').hide();
  $('#byip_field').hide();
  $('#bymac_field').hide();
  if ($('#ident').val() == 'proxy_auth') {
    $('#byuser_field').show();
  } else if ($('#ident').val() == 'src') {
    $('#byip_field').show();
  } else if ($('#ident').val() == 'arp') {
    $('#bymac_field').show();
  }
}
";

// vim: syntax=php ts=4
