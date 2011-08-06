<?php

/**
 * OpenLDAP user mapping.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
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

clearos_load_language('shell_extension');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Login_Shell as Login_Shell;

clearos_load_library('base/Login_Shell');

///////////////////////////////////////////////////////////////////////////////
// C O N F I G
///////////////////////////////////////////////////////////////////////////////

$login_shell = new Login_Shell();
$shells = $login_shell->get_list();

$info_map = array(
    'login_shell' => array(
        'type' => 'string',
        'field_type' => 'simple_list',
        'field_options' => $shells, 
        'field_priority' => 'normal',
        'required' => TRUE,
        'validator' => 'validate_login_shell',
        'validator_class' => 'openldap_directory/User_Driver',
        'description' => lang('shell_extension_shell'),
        'object_class' => 'clearAccount',
        'attribute' => 'loginShell'
    ),
);
