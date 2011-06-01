<?php

/**
 * Javascript helper for Raid.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/raid/
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

clearos_load_language('raid');
clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "

function togglenotify() {
  if (document.getElementById('monitor').value == 1) {
    document.getElementById('notify').disabled = false;
  } else {
    document.getElementById('notify').value = 0;
    document.getElementById('notify').disabled = true;
  }
  toggleemail();
}

function toggleemail() {
  if (document.getElementById('notify').value == 1)
    document.getElementById('email').disabled = false;
  else
    document.getElementById('email').disabled = true;
}

function enable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = false;
}

function disable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = true;
}

function toggleview() {
  if (document.getElementById('action').value == 1) {
    document.getElementById('copyto').style.display = 'none';
    document.getElementById('copyfrom').style.display = 'none';
  } else {
    if (document.all) {
      document.getElementById('copyto').style.display = 'inline';
      document.getElementById('copyfrom').style.display = 'inline';
    } else {
      document.getElementById('copyto').style.display = 'table-row';
      document.getElementById('copyfrom').style.display = 'table-row';
    }
  }
}

";

// vim: syntax=php ts=4
