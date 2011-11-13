<?php

/**
 * OpenLDAP directory utilities class.
 *
 * @category   Apps
 * @package    OpenLDAP_Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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

namespace clearos\apps\openldap_directory;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

// clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;

clearos_load_library('base/Engine');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/OpenLDAP');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP directory utilities class.
 *
 * @category   Apps
 * @package    OpenLDAP_Accounts
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Utilities extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP directory utilities constructor.
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

        $ldap = new LDAP_Driver();
        $ldaph = $ldap->get_ldap_handle();

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

    public static function get_usermap_by_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldaph = self::get_ldap_handle();

        $usermap_dn = array();
        $usermap_username = array();

        $result = $ldaph->search(
            "(&(cn=*)(objectclass=posixAccount))",
            OpenLDAP::get_users_container(),
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

    /**
     * Handles running various hooks in an extension
     *
     * @param array $details extension details
     * @return object extension object
     */

    public static function load_user_extension($details)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_load_library($details['app'] . '/OpenLDAP_User_Extension'))
            return;

        $class = '\clearos\apps\\' . $details['app'] . '\OpenLDAP_User_Extension';
        $extension = new $class();

        return $extension;
    }

    /**
     * Loads group extension.
     *
     * @param array $details extension details
     * @return object extension object
     */

    public static function load_group_extension($details)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_load_library($details['app'] . '/OpenLDAP_Group_Extension'))
            return;

        $class = '\clearos\apps\\' . $details['app'] . '\OpenLDAP_Group_Extension';
        $extension = new $class();

        return $extension;
    }

    /**
     * Merges two LDAP object class lists.
     *
     * @param array $array1 LDAP object class list
     * @param array $array2 LDAP object class list
     *
     * @return array object class list
     */

    public static function merge_ldap_object_classes($array1, $array2)
    {
        clearos_profile(__METHOD__, __LINE__);

        $raw_merged = array_merge($array1, $array2);
        $raw_merged = array_unique($raw_merged);

        // PHPism.  Merged arrays have gaps in the keys of the array.
        // The LDAP object barfs on this, so we need to re-key.

        $merged = array();

        foreach ($raw_merged as $class)
            $merged[] = $class;

        return $merged;
    }

    /**
     * Merges two LDAP object arrays.
     *
     * @param array $array1 LDAP object array
     * @param array $array2 LDAP object array
     *
     * @return array LDAP object array
     */

    public static function merge_ldap_objects($array1, $array2)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Handle object class array

        if (isset($array1['objectClass']) && isset($array2['objectClass']))
            $object_classes = Utilities::merge_ldap_object_classes($array1['objectClass'], $array2['objectClass']);
        else if (isset($array1['objectClass']))
            $object_classes = $array1['objectClass'];
        else if (isset($array2['objectClass']))
            $object_classes = $array2['objectClass'];

        $ldap_object = array_merge($array1, $array2);

        if (isset($object_classes))
            $ldap_object['objectClass'] = $object_classes;

        return $ldap_object;
    }
}
