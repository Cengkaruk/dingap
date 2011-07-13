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

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {
    $('.bootproto_form').hide();
    setGateway();

    current_form = '#' + $('#bootproto').val();
    $(current_form).show();

    $('#role').change(function() {
        setGateway();
    });

    $('#bootproto').change(function() {
        $('.bootproto_form').hide();
        new_form = '#' + $(this).attr('value');
        $(new_form).show();
    });
});

function setGateway() {
    role = $('#role').attr('value');
    if (role == 'EXTIF')
        $('#gateway_field').show();
    else
        $('#gateway_field').hide();
}

// vim: ts=4 syntax=javascript
