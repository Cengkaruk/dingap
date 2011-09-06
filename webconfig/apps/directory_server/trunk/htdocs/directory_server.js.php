<?php

/**
 * Directory server ajax helper.
 *
 * @category   Apps
 * @package    Directory_Server
 * @subpackage Ajax
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_server/
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
    $("#result").html('<div class="theme-loading"></div>');
    $("#directory_information").hide();

    getDirectoryInfo();

    // Update via Ajax
    //----------------

    $("#update_directory").click(function(){
        $("#update_directory").trigger('submit');
        $("#directory_information").hide();

        $.ajax({
// FIXME
            url: '/app/directory_server/update_domain/test8.lan',
            method: 'GET',
            dataType: 'json',
            success : function(payload) {
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
            }
        });
    });

    // Directory information via Ajax
    //-------------------------------

    function getDirectoryInfo() {
        $.ajax({
            url: '/app/directory_server/get_info',
            method: 'GET',
            dataType: 'json',
            success : function(payload) {
                window.setTimeout(getDirectoryInfo, 2000);
                showDirectoryInfo(payload);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                window.setTimeout(getDirectoryInfo, 2000);
            }
        });
    }

    function showDirectoryInfo(payload) {
        if (payload.accounts_status == 'ok') {
            $("#directory_information").show();
        } else {
            $("#directory_information").hide();
        }

        $("#mode_text").html(payload.mode_text);
        $("#base_dn").html(payload.base_dn);
        $("#bind_dn").html(payload.bind_dn);
        $("#bind_password").html(payload.bind_password);
        $("#users_container").html(payload.users_container);
        $("#groups_container").html(payload.groups_container);
        $("#computers_container").html(payload.computers_container);
        $("#result").html('');
	}
});

// vim: ts=4 syntax=javascript
