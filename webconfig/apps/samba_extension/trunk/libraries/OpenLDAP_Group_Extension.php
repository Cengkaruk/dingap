<?php

/**
 * Samba OpenLDAP group extension.
 *
 * @category   Apps
 * @package    Samba_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba_extension/
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

namespace clearos\apps\samba_extension;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('samba');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\samba\OpenLDAP_Driver as OpenLDAP_Driver;
use \clearos\apps\samba\Samba as Samba;

clearos_load_library('base/Engine');
clearos_load_library('samba/OpenLDAP_Driver');
clearos_load_library('samba/Samba');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Samba OpenLDAP group extension.
 *
 * @category   Apps
 * @package    Samba_Extension
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba_extension/
 */

class OpenLDAP_Group_Extension extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Samba OpenLDAP_Extension constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /** 
     * Add LDAP attributes hook.
     *
     * @param array $user_info user information in hash array
     * @param array $ldap_object LDAP object
     *
     * @return array LDAP attributes
     * @throws Engine_Exception
     */

    public function add_attributes_hook($group_info, $ldap_object)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if Samba has not been initialized
        //---------------------------------------

        $samba_driver = new OpenLDAP_Driver();

        if (!$samba_driver->is_directory_initialized())
            return array();

        // Process attributes
        //-------------------

        $samba = new Samba();

        $sid = $samba->get_domain_sid();
        $domain = $samba->get_workgroup();

        // Add built-in attributes
        //------------------------

        $attributes['sambaSID'] = $sid . '-' . $group_info['gid_number'];
        $attributes['sambaGroupType'] = 2;
        $attributes['displayName'] = $group_info['group_name'];
        $attributes['objectClass'][] = 'sambaGroupMapping';

        return $attributes;
    }

    /**
     * Returns group info hash array.
     *
     * @param array $attributes LDAP attributes
     *
     * @return array group info array
     * @throws Engine_Exception
     */

    public function get_info_hook($attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if Samba has not been initialized
        //---------------------------------------

        $samba_driver = new OpenLDAP_Driver();

        if (!$samba_driver->is_directory_initialized())
            return;

        // Return info array
        //------------------

        if (isset($attributes['sambaSID']))
            $info['sid'] = $attributes['sambaSID'][0];

        return $info;
    }
}
