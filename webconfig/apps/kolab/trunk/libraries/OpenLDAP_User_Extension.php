<?php

/**
 * Kolab OpenLDAP user extension.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/kolab/
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
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\kolab;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\Utilities as Utilities;

clearos_load_library('base/Engine');
clearos_load_library('openldap/Utilities');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Kolab OpenLDAP user extension.
 *
 * @category   Apps
 * @package    Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/kolab/
 */

class OpenLDAP_User_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_KOLABCONF = '/usr/sbin/kolabconf';
    const FILE_KOLAB_CONFIG = '/etc/kolab/kolab.conf';
    const DEFAULT_INVITATION_POLICY = 'ACT_MANUAL';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $info_map = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Kolab constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->info_map = array(
            'alias' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'objectclass' => 'kolabInetOrgPerson',
                'attribute' => 'alias'
            ),
            'delete_mailbox' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'objectclass' => 'kolabInetOrgPerson',
                'attribute' => 'kolabDeleteflag'
            ),
            'home_server' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'objectclass' => 'kolabInetOrgPerson',
                'attribute' => 'kolabHomeServer'
            ),
            'invitation_policy' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_FIXME',
                'objectclass' => 'kolabInetOrgPerson',
                'attribute' => 'kolabInvitationPolicy'
            ),
            'mail_quota' => array(
                'type' => 'string',
                'required' => FALSE,
                'validator' => 'validate_mail_quota',
                'objectclass' => 'kolabInetOrgPerson',
                'attribute' => 'cyrus-userquota'
            ),
        );
    }

    /** 
     * Adds LDAP attributes for given user info hash array.
     *
     * @param array $user_info user information in hash array
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($user_info)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set defaults
        //-------------

        if (! isset($user_info['home_server']))
            $user_info['kolab']['home_server'] = 'notused.lan';

        if (! isset($user_info['invitation_policy']))
            $user_info['kolab']['invitation_policy'] = self::DEFAULT_INVITATION_POLICY;

        // Convert to LDAP attributes
        //---------------------------

        // FIXME: objectclass
        $attributes = Utilities::convert_array_to_attributes($user_info['kolab'], $this->info_map);

        return $attributes;
    }

    /**
     * Runs after adding a user.
     *
     * @return void
     */

    public function add_post_processing()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /** 
     * Returns user info for passwd in raw LDAP attributes.
     *
     * @param array $attributes LDAP attributes
     *
     * @return string default home server
     * @throws Engine_Exception
     */

    public function get_info_hook($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = Utilities::convert_attributes_to_array($attributes, $this->info_map);

        return $info;
    }
}
