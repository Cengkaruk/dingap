<?php

/**
 * OpenLDAP group mapping.
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

clearos_load_language('groups');

///////////////////////////////////////////////////////////////////////////////
// C O N F I G
///////////////////////////////////////////////////////////////////////////////

$info_map = array(
    'group_name' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => TRUE,
        'validator' => 'validate_group_name',
        'validator_class' => 'openldap/Group_Driver',
        'description' => lang('groups_group'),
        'object_class' => 'posixGroup',
        'attribute' => 'cn'
    ),

    'description' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => TRUE,
        'validator' => 'validate_description',
        'validator_class' => 'openldap/Group_Driver',
        'description' => lang('groups_description'),
        'object_class' => 'posixGroup',
        'attribute' => 'description'
    ),

    'gid_number' => array(
        'type' => 'integer',
        'field_type' => 'text',
        'required' => TRUE,
        'validator' => 'validate_gid_number',
        'validator_class' => 'openldap/Group_Driver',
        'description' => lang('groups_group_id'),
        'object_class' => 'posixGroup',
        'attribute' => 'gidNumber'
    ),
);
