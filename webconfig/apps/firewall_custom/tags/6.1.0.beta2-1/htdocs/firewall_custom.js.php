<?php

/**
 * Javascript helper for Firewall_Custom.
 *
 * @category   Apps
 * @package    Firewall_Custom
 * @subpackage Javascript
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/Company/terms.html ClearSDN license
 * @link       http://www.clearcenter.com/support/documentation/clearos/firweall_custom/
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

clearos_load_language('firewall_custom');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "
$(document).ready(function() {
  $('a.view_rule').click(function (e) {
    e.preventDefault();
    clearos_info(this.id, rules[this.id]);
  });
  $('#entry').attr('style', 'width: 325');
  $('#description').attr('style', 'width: 325');
  $('.left-field-content').css('width', '150');
});

function clearos_info(id, message) {
  $('#theme-page-container').append('<div id=\"dialog-' + id + '\" title=\"" . lang('firewall_custom_full_rule') . "\">' +
      '<p>' +
        '<span class=\"ui-icon ui-icon-info\" style=\"float:left; margin:0 7px 50px 0;\"></span>' + message +
      '</p>' +
    '</div>'
  );
  $('#dialog-' + id).dialog({
    modal: true,
    width: 500,
    buttons: {
      '" . lang('base_close') . "': function() {
        $(this).dialog('close');
      }
    }
  });
}

";

// vim: syntax=php ts=4
