<?php

/**
 * Directory server ajax helper.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Ajax
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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
clearos_load_language('active_directory');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    // Translations
    //-------------

    lang_initializing = '<?php echo lang("base_initializing..."); ?>';
    lang_success = '<?php echo lang("openldap_directory_directory_updated"); ?>';

    // Prep
    //-----

    $("#infoboxes").hide();
    $("#initializing_box").hide();
    $("#directory_information").hide();
    $("#directory_configuration").hide();

    // Run connection attempt
    //-----------------------

    if ($("#validated").val() == 1) {
        $("#infoboxes").show();
        $("#initializing_status").html('<div class="theme-loading-normal">' + lang_initializing + '</div>');
        $("#initializing_box").show();
        $("#directory_information").hide();
        $("#directory_configuration").hide();

        updateDomain();
    } else {
        $("#directory_information").show();
        $("#directory_configuration").show();

        getDirectoryInfo();
    }

});

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Initializes/updates directory.
 */

function updateDomain() {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&domain=' + $("#domain").val(),
        url: '/app/openldap_directory/settings/update',
        success: function(payload) {
            if (payload.code == 0) {
                $("#initializing_box").hide();
                $("#directory_information").show();
                $("#directory_configuration").hide();
            } else {
                $("#initializing_status").html(payload.error_message);
                $("#directory_configuration").show();
            }
        },
        error: function() {
        }
    });
}

/**
 * Gets directory information via Ajax.
 */

function getDirectoryInfo() {
    $.ajax({
        url: '/app/openldap_directory/information/get_info',
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

/**
 * Displays directory information from Ajax request.
 */

function showDirectoryInfo(payload) {
    if (payload.ldap_status == 'online') {
        $("#mode_text").html(payload.mode_text);
        $("#base_dn_text").html(payload.base_dn);
        $("#bind_dn_text").html(payload.bind_dn);
        $("#bind_password_text").html(payload.bind_password);
        $("#users_container_text").html(payload.users_container);
        $("#groups_container_text").html(payload.groups_container);
        $("#computers_container_text").html(payload.computers_container);
        $("#result").html('');
        $("#directory_information").show();
        $("#directory_configuration").show();
    } else {
        $("#directory_information").hide();
        $("#directory_configuration").hide();
    }
}

// vim: ts=4 syntax=javascript
