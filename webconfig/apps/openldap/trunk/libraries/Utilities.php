<?php

/**
 * OpenLDAP utilities class.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
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
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\openldap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

// clearos_load_language('base');
// clearos_load_language('directory_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\OpenLDAP as OpenLDAP;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('openldap/OpenLDAP');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS OpenLDAP utilities class.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class Utilities extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP utilities constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Converts LDAP attributes into a hash array.
     *
     * The attributes array that comes from an ldap_read is not what we want to
     * send back to the API call.  Instead, a basic hash array is created
     * by mapping LDAP attributes like: 
     *
     *    [facsimileTelephoneNumber] => Array
     *        (
     *            [count] => 1
     *            [0] => 1234567
     *        )
     *    [7] => facsimileTelephoneNumber
     *
     * To:
     *
     *   [fax] => 1234567 
     *
     * @param string $attributes LDAP attributes
     * @param string $mapping    attribute to array mapping information
     *
     * @return array attributes in a hash array
     */

    public static function convert_attributes_to_array($attributes, $mapping)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = array();

        foreach ($mapping as $infoname => $detail) {
            if (empty($attributes[$detail['attribute']])) {
                if ($detail['type'] == 'boolean')
                    $info[$infoname] = FALSE;
                else
                    $info[$infoname] = NULL;
            } else {
                if ($infoname != 'password') {
                    if ($detail['type'] == 'boolean') {
                        $info[$infoname] = ($attributes[$detail['attribute']][0] == 'TRUE') ? TRUE : FALSE;
                    } elseif ($detail['type'] == 'stringarray') {
                        array_shift($attributes[$detail['attribute']]);
                        $info[$infoname] = $attributes[$detail['attribute']];
                    } else {
                        $info[$infoname] = $attributes[$detail['attribute']][0];
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Converts hash array into LDAP attributes.
     *
     * Gotcha: in order to delete an attribute on an update, the LDAP object item
     * must be set to an empty array.  See http://ca.php.net/ldap_modify for
     * more information.  However, the empty array on a new user causes
     * an error.  In this case, leaving the LDAP object item undefined
     * is the correct behavior.
     *
     * @param string $array   hash array
     * @param string $mapping attribute to array mapping information
     *
     * @return array LDAP attributes
     */

    public static function convert_array_to_attributes($array, $mapping)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap_object = array();
        $object_classes = array();

        foreach ($array as $info => $value) {
            if (isset($mapping[$info]['attribute'])) {
                $attribute = $mapping[$info]['attribute'];

                // Delete
                if (($value === NULL) || ($value === '')) {
                    // if ($is_modify) FIXME
                        $ldap_object[$attribute] = array();

                // Add/modify
                } else {
                    if ($mapping[$info]['type'] == 'boolean')
                        $ldap_object[$attribute] = ($value) ? 'TRUE' : 'FALSE';
                    else
                        $ldap_object[$attribute] = $array[$info];

                    $object_classes[] = $mapping[$info]['object_class'];
                }
            }
        }

        $ldap_object['objectClass'] = array_unique($object_classes);

        return $ldap_object;
    }

    /**
     * Returns DN for given user ID (username).
     *
     * @param string $uid user ID
     *
     * @return string DN
     * @throws Engine_Exception
     */

// FIXME: remove this function from other classes
    public static function get_dn_for_uid($uid)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldaph = Utilities::get_ldap_handle();

        $ldaph->search('(&(objectclass=clearAccount)(uid=' . $ldaph->escape($uid) . '))');
        $entry = $ldaph->get_first_entry();

        $dn = '';

        if ($entry)
            $dn = $ldaph->get_dn($entry);

        return $dn;
    }

    /**
     * Creates an LDAP connection handle.
     *
     * Many libraries that use OpenLDAP need to:
     *
     * - grab LDAP credentials for connecting to the server
     * - connect to LDAP
     * - perform a bunch of LDAP acctions (search, read, etc)
     *
     * This method provides a common method for doing the firt two steps.
     *
     * @return LDAP handle
     * @throws Engine_Exception
     */

    public static function get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file_config = clearos_app_base('openldap') . '/config/config.php';

        // FIXME: add security context
        $file = new Configuration_File($file_config, 'split', '=', 2);
        $config = $file->load();

        $ldaph = new OpenLDAP($config['base_dn'], $config['bind_dn'], $config['bind_pw']);

        return $ldaph;
    }

    /**
     * Loads group list arrays to help with mapping usernames to DNs.
     *
     * RFC2307bis lists a group of users by DN (which is a CN/common name
     * in our implementation).  Since we prefer seeing a group listed by
     * usernames, this method is used to create two hash arrays to map
     * the usernames and DNs.
     *
     * @access private
     * @return void
     */

// FIXME: remove this function from other classes
    public static function get_usermap_by_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldaph = Utilities::get_ldap_handle();

        $usermap_dn = array();
        $usermap_username = array();

        $directory = new Directory_Driver();

        $result = $ldaph->search(
            "(&(cn=*)(objectclass=posixAccount))",
            $directory->get_users_ou(),
            array('dn', 'uid')
        );

        $entry = $ldaph->get_first_entry($result);

        while ($entry) {
            $attrs = $ldaph->get_attributes($entry);
            $dn = $ldaph->get_dn($entry);
            $uid = $attrs['uid'][0];

            $usermap_dn[$dn] = $uid;
            $usermap_username[$uid] = $dn;

            $entry = $ldaph->next_entry($entry);
        }

        return $usermap_dn;
    }
}
