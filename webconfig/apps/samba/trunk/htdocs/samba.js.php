<?php

/**
 * Samba controller.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
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
    $('#profiles_field').hide();
    $('#logon_drive_field').hide();
    $('#logon_script_field').hide();

    changeMode();

    $('#mode').change(function() {
        changeMode();
    });
});

function changeMode() {
    current_mode = $('#mode').val();

    if (current_mode == 'pdc') {
        $('#profiles_field').show();
        $('#logon_drive_field').show();
        $('#logon_script_field').show();
    } else {
        $('#profiles_field').hide();
        $('#logon_drive_field').hide();
        $('#logon_script_field').hide();
    }
}

// vim: syntax=javascript
