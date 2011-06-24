<?php

/**
 * Javascript helper for Account_Import.
 *
 * @category   Apps
 * @package    Account_Import
 * @subpackage Javascript
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/Company/terms.html ClearSDN license
 * @link       http://www.clearcenter.com/support/documentation/clearos/account_import/
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

clearos_load_language('account_import');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "

function get_progress() {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/account_import/ajax/get_progress',
        data: '',
        success: function(data) {
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (obj['abort'] == null)
                clearos_alert('errmsg', xhr.responseText.toString());
        }
    });
}

";

// vim: syntax=php ts=4
