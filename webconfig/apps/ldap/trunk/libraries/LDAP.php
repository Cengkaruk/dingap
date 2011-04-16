<?php

/**
 * Generic LDAP class.
 *
 * @category   Apps
 * @package    LDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ldap/
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

namespace clearos\apps\ldap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('ldap');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;

clearos_load_library('base/Daemon');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\ldap\LDAP_Unavailable_Exception as LDAP_Unavailable_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('ldap/LDAP_Unavailable_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Generic LDAP class.
 *
 * This is a low-level PHP class for performing LDAP operations.
 *
 * @category   Apps
 * @package    LDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ldap/
 */

class LDAP extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $bound = FALSE;
    protected $connection = NULL;
    protected $search_result = FALSE;
    protected $config = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * LDAP constructor.
     *
     * @param string $base_dn   Base DN
     * @param string $bind_dn   Bind DN
     * @param string $bind_pw   Bind password
     * @param string $bind_host Bind host
     */

    public function __construct($base_dn, $bind_dn, $bind_pw, $bind_host = 'localhost')
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->config['base_dn'] = $base_dn;
        $this->config['bind_dn'] = $bind_dn;
        $this->config['bind_pw'] = $bind_pw;
        $this->config['bind_host'] = $bind_host;

        parent::__construct('slapd');
    }

    /**
     * Performs LDAP add.
     *
     * @param string $dn         distinguished name
     * @param array  $attributes attributes
     *
     * @return void
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function add($dn, $attributes)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        if (! ldap_add($this->connection, $dn, $attributes))
            throw new Engine_Exception(ldap_error($this->connection));
    }

    /**
     * Closes LDAP connection.
     *
     * @return void
     */

    public function close()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->search_result) {
            ldap_free_result($this->search_result);
            $this->search_result = NULL;
        }

        if (! is_null($this->connection))
            ldap_close($this->connection);

        $this->connection = NULL;
        $this->bound = FALSE;
    }

    /**
     * Deletes an an LDAP object
     *
     * @param string $dn distinguished name
     *
     * @return void
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function delete($dn)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        if (! ldap_delete($this->connection, $dn))
            throw new Engine_Exception(lang('ldap_ldap_operation_failed'));
    }

    /**
     * Checks the existence of given DN.
     *
     * @param string $dn distinguised name
     *
     * @return TRUE if DN exists
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function exists($dn)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $result = @ldap_read($this->connection, $dn, '(objectclass=*)');

        if (empty($result))
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Returns LDAP error.
     *
     * @return string LDAP error
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function error()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        return ldap_error($this->connection);
    }

    /**
     * Returns attributes from a search result.
     *
     * @param string $entry LDAP entry
     *
     * @return array attributes in array format
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function get_attributes($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $attributes = ldap_get_attributes($this->connection, $entry);

        if (! $attributes)
            throw new Engine_Exception(lang('ldap_ldap_operation_failed'));

        return $attributes;
    }

    /** 
     * Returns configured base DN.
     *
     * @return string base DN
     * @throws Engine_Exception
     */

    public function get_base_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->config['base_dn'];
    }

    /** 
     * Returns configured bind DN.
     *
     * @return string bind DN
     * @throws Engine_Exception
     */

    public function get_bind_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->config['bind_dn'];
    }

    /** 
     * Returns configured bind password.
     *
     * @return string bind password
     * @throws Engine_Exception
     */

    public function get_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->config['bind_pw'];
    }

    /**
     * Returns DN of a result entry.
     *
     * @param string $entry LDAP entry
     *
     * @return string DN
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function get_dn($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $dn = ldap_get_dn($this->connection, $entry);

        if (! $dn)
            throw new Engine_Exception(ldap_error($this->connection));

        return $dn;
    }

    /**
     * Returns LDAP entries.
     *
     * @return array complete result information in a multi-dimensional array
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function get_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $entries = ldap_get_entries($this->connection, $this->search_result);

        if (! $entries)
            throw new Engine_Exception(ldap_error($this->connection));

        return $entries;
    }

    /**
     * Returns first LDAP entry.
     *
     * @return resource result entry identifier for the first entry.
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function get_first_entry()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $entry = ldap_first_entry($this->connection, $this->search_result);

        return $entry;
    }

    /** 
     * Returns configured LDAP URI.
     *
     * @return string LDAP URI
     * @throws Engine_Exception
     */

    public function get_ldap_uri()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->config['ldap_uri'];
    }

    /** 
     * Checks LDAP availability.
     *
     * @return boolean TRUE if LDAP connection was successful
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function is_available()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->bound)
            return TRUE;

        try {
            $this->_bind();
        } catch (LDAP_Unavailable_Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Modifies LDAP entry.
     *
     * @param string $dn    distinguished name
     * @param string $entry entry
     *
     * @return void
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function modify($dn, $entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $ok = ldap_modify($this->connection, $dn, $entry);

        if (!$ok)
            throw new Engine_Exception(ldap_error($this->connection));
    }

    /**
     * Modifies LDAP list of entries.
     *
     * @param string $dn      distinguished name
     * @param string $entries entries
     *
     * @return void
     * @throws Engine_Exception
     */

    public function modify_members($dn, $entries)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $ok = ldap_modify($this->connection, $dn, $entry);

        if (!$ok)
            throw new Engine_Exception(ldap_error($this->connection));
    }

    /**
     * Returns next result entry.
     *
     * @param string $entry LDAP entry
     *
     * @return resource next result entry
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function next_entry($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        return ldap_next_entry($this->connection, $entry);
    }

    /**
     * Performs LDAP read operation.
     *
     * @param string $dn distinguished name
     *
     * @return array complete entry information
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function read($dn)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $result = @ldap_read($this->connection, $dn, '(objectclass=*)');

        if (!$result)
            throw new Engine_Exception(ldap_error($this->connection));

        $entry = ldap_first_entry($this->connection, $result);

        if (!$entry) {
            ldap_free_result($result);
            return;
        }

        $ldap_object = ldap_get_attributes($this->connection, $entry);

        if (! $ldap_object)
            throw new Engine_Exception(ldap_error($this->connection));

        ldap_free_result($result);

        return $ldap_object;
    }

    /**
     * Performs LDAP rename.
     *
     * @param string $dn         distinguished name
     * @param string $rdn        new relative DN
     * @param string $new_parent new parent
     *
     * @return void
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function rename($dn, $rdn, $new_parent = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        if (empty($new_parent))
            $new_parent = $this->config['base_dn'];

        if (! ldap_rename($this->connection, $dn, $rdn, $new_parent, TRUE))
            throw new Engine_Exception(ldap_error($this->connection));
    }

    /**
     * Performs LDAP search.
     *
     * @param string $filter     filter
     * @param string $base_dn    base DN for performing filter
     * @param array  $attributes attributes
     *
     * @return handle search result identifier
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function search($filter, $base_dn = NULL, $attributes = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        $this->_free_search_result();

        if (is_null($base_dn))
            $base_dn = $this->config['base_dn'];

        if ($attributes == NULL)
            $this->search_result = ldap_search($this->connection, $base_dn, $filter);
        else
            $this->search_result = ldap_search($this->connection, $base_dn, $filter, $attributes);

        if (! $this->search_result)
            throw new Engine_Exception(ldap_error($this->connection));

        return $this->search_result;
    }

    /**
     * Sorts LDAP result.
     *
     * @param handle $result      search handle
     * @param string $sort_filter attribute used for sorting
     *
     * @return void
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    public function sort(&$result, $sort_filter)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->bound)
            $this->_bind();

        // FIXME: remove pass by reference
        ldap_sort($this->connection, $result, $sort_filter);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // S T A T I C  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * LDAP DN escaping as described in RFC 2253.
     *
     * @param string $string string
     *
     * @return string escaped string
     */

    static function dn_escape($string)
    {
        clearos_profile(__METHOD__, __LINE__);

        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(',', '\\,', $string);
        $string = str_replace('+', '\\,', $string);
        $string = str_replace('<', '\\<', $string);
        $string = str_replace('>', '\\>', $string);
        $string = str_replace(';', '\\;', $string);

        if ($string[0] == '#')
            $string = '\\' . $string;

        return $string;
    }

    /**
     * LDAP sring escaping as described in RFC-2254.
     *
     * If a value should contain any of the following characters
     * - * 0x2a
     * - ( 0x28
     * - ) 0x29
     * - \ 0x5c
     * - NUL 0x00
     *
     * the character must be encoded as the backslash '\' character (ASCII
     * 0x5c) followed by the two hexadecimal digits representing the ASCII
     * value of the encoded character. The case of the two hexadecimal
     * digits is not significant.
     *
     * @param string $string string
     *
     * @return string escaped string
     */

    static function escape($string)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: make this an internal method call (i.e. remove from external calls)
        $string = str_replace('\\', '\\5c', $string);
        $string = str_replace('*', '\\2a', $string);
        $string = str_replace('(', '\\28', $string);
        $string = str_replace(')', '\\29', $string);
        $string = str_replace('\0', '\\00', $string);

        return $string;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads default settings, connects and binds to LDAP server.
     *
     * @return void
     * @throws Engine_Exception, LDAP_Unavailable_Exception
     */

    protected function _bind()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->connection = ldap_connect($this->config['bind_host']);

        if (! ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3))
            throw new Engine_Exception(lang('ldap_ldap_operation_failed'));

        if (! @ldap_bind($this->connection, $this->config['bind_dn'], $this->config['bind_pw'])) {
            if (ldap_errno($this->connection) === -1)
                throw new LDAP_Unavailable_Exception();
            else
                throw new Engine_Exception($this->error());
        }

        $this->bound = TRUE;
    }

    /**
     * Clears search results.
     *
     * @access private
     *
     * @return void
     */

    protected function _free_search_result()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->search_result) {
            ldap_free_result($this->search_result);
            $this->search_result = FALSE;
        }
    }
}
