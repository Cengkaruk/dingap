<?php

/**
 * Kolab OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Kolab_Directory_Extension
 * @subpackage Config
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/kolab_directory_extension/
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

clearos_load_language('kolab');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\kolab\Kolab as Kolab;

clearos_load_library('kolab/Kolab');

///////////////////////////////////////////////////////////////////////////////
// C O N F I G
///////////////////////////////////////////////////////////////////////////////

$info_map = array(

    'alias' => array(
        'type' => 'string',
        'field_type' => 'list',
        'field_options' => array('W:', 'X:', 'Y:'),
        'required' => FALSE,
        'validator' => 'validate_alias',
        'validator_class' => 'kolab_directory_extension/OpenLDAP_User_Extension',
        'description' => lang('kolab_mail_aliases'),
        'object_class' => 'kolabInetOrgPerson',
        'attribute' => 'alias'
    ),

    'delete_mailbox' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_mailbox',
        'validator_class' => 'kolab_directory_extension/OpenLDAP_User_Extension',
        'description' => lang('kolab_delete_mailbox_state'),
        'object_class' => 'kolabInetOrgPerson',
        'attribute' => 'kolabDeleteflag'
    ),

    'home_server' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_home_server',
        'validator_class' => 'kolab_directory_extension/OpenLDAP_User_Extension',
        'description' => lang('kolab_mail_server'),
        'object_class' => 'kolabInetOrgPerson',
        'attribute' => 'kolabHomeServer'
    ),

    'invitation_policy' => array(
        'type' => 'string',
        'field_type' => 'list',
        'field_options' => Kolab::get_invitation_policies(),
        'required' => FALSE,
        'validator' => 'validate_invitation_policy',
        'validator_class' => 'kolab_directory_extension/OpenLDAP_User_Extension',
        'description' => lang('kolab_invitation_policy'),
        'object_class' => 'kolabInetOrgPerson',
        'attribute' => 'kolabInvitationPolicy'
    ),

    'mail_quota' => array(
        'type' => 'string',
        'field_type' => 'text',
        'required' => FALSE,
        'validator' => 'validate_mail_quota',
        'validator_class' => 'kolab_directory_extension/OpenLDAP_User_Extension',
        'description' => lang('kolab_mail_quota'),
        'object_class' => 'kolabInetOrgPerson',
        'attribute' => 'cyrus-userquota'
    ),
);
