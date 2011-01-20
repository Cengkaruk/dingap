<?php

/**
 * Hosts file manager class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
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

namespace clearos\apps\network;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hosts file manager class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
 */

class Hosts extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * @var bool is_loaded
     */

    protected $is_loaded = FALSE;

    /**
     * @var array hosts_array
     */

    protected $hostdata = array();

    const FILE_CONFIG = '/etc/hosts';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Add an entry to the /etc/hosts file.
     *
     * @param string $ip       IP address
     * @param string $hostname canonical hostname
     * @param string $aliases  array of aliases
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_entry($ip, $hostname, $aliases = array())
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($error = $this->validate_ip($ip))
            throw new Validation_Exception($error);

        if ($error = $this->validate_hostname($hostname))
            throw new Validation_Exception($error);

        foreach ($aliases as $alias) {
            if ($error = $this->validate_alias($alias))
                throw new Validation_Exception($error);
        }

        if ($this->entry_exists($ip))
            throw new Validation_Exception('Entry already exists for this IP'); // FIXME: translate

        // Add
        //----

        $this->_load_entries();

        try {
            $file = new File(self::FILE_CONFIG);
            $file->add_lines("$ip $hostname " . implode(' ', $aliases) . "\n");
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), COMMON_ERROR);
        }

        // Force a re-read of the data
        $this->is_loaded = FALSE;
    }

    /**
     * Delete an entry from the /etc/hosts file.
     *
     * @param string $ip IP address
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_entry($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($error = $this->validate_ip($ip))
            throw new Validation_Exception($error);

        // Delete
        //-------

        try {
            $file = new File(self::FILE_CONFIG);
            $hosts = $file->delete_lines('/^' . $ip . '\s/i');
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), COMMON_ERROR);
        }

        // Force a reload
        $this->is_loaded = FALSE;
    }

    /**
     * Updates hosts entry for given IP address
     *
     * @param string $ip       IP address
     * @param string $hostname caononical hostname
     * @param array  $aliases  aliases
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function edit_entry($ip, $hostname, $aliases = array())
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($error = $this->validate_ip($ip))
            throw new Validation_Exception($error);

        if ($error = $this->validate_hostname($hostname))
            throw new Validation_Exception($error);

        foreach ($aliases as $alias) {
            if ($error = $this->validate_alias($alias))
                throw new Validation_Exception($error);
        }

        if (! $this->entry_exists($ip))
            throw new Validation_Exception('No entry exists for this IP'); // FIXME: translate

        // Update
        //-------

        try {
            $file = new File(self::FILE_CONFIG);
            $file->replace_lines("/^$ip\s+/i", "$ip $hostname " . implode(' ', $aliases) . "\n");
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), COMMON_ERROR);
        }

        // Force a reload
        $this->is_loaded = FALSE;
    }

    /**
     * Returns the hostname and aliases for the given IP address.
     *
     * @param string $ip IP address
     *
     * @return array an array containing the hostname and aliases
     * @throws Engine_Exception
     */

    public function get_entry($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($error = $this->validate_ip($ip))
            throw new Validation_Exception($error);

        // Get Entry
        //----------

        $this->_load_entries();

        foreach ($this->hostdata as $real_ip => $entry) {
            if ($entry['ip'] == $ip)
                return $entry;
        }

        throw new Validation_Exception("No entry exists for this IP");  // FIXME: translate
    }

    /**
     * Returns information in the /etc/hosts file in an array.
     *
     * The array is indexed on IP, and contains an array of associated hosts.
     *
     * @return array list of host information
     *
     * @throws Engine_Exception
     */

    public function get_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_entries();

        return $this->hostdata;
    }

    /**
     * Returns the IP address for the given hostname.
     *
     * @param string $hostname hostname
     *
     * @return string IP address if hostname exists, NULL if it does not
     * @throws Engine_Exception
     */

    public function get_ip_by_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($error = $this->validate_hostname($hostname))
            throw new Validation_Exception($error);

        // Get Entry
        //----------

        $this->_load_entries();

        foreach ($this->hostdata as $real_ip => $entry) {
            if ($entry['hostname'] === $hostname)
                return $entry['ip'];

            foreach ($entry['aliases'] as $alias) {
                if ($alias === $hostname)
                    return $entry['ip'];
            }
        }

        return NULL;
    }

    /**
     * Checks to see if entry exists.
     *
     * @param string $ip IP address
     *
     * @return boolean true if entry exists
     * @throws Engine_Exception
     */

    public function entry_exists($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($error = $this->validate_ip($ip))
            throw new Validation_Exception($error);

        // Get Entry
        //----------

        $this->_load_entries();

        foreach ($this->hostdata as $real_ip => $entry) {
            if ($entry['ip'] == $ip)
                return TRUE;
        }

        return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates a hostname alias.
     *
     * @param string $alias alias
     *
     * @return string error message if alias is invalid
     */

    public function validate_alias($alias)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network_Utils();

        return $network->validate_hostname_alias($alias);
    }

    /**
     * Validates a hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network_Utils();

        return $network->validate_hostname($hostname);
    }

    /**
     * Validates IP address entry.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP is invalid
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network_Utils();

        return $network->validate_ip($ip);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads host info from /etc/hosts.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_loaded)
            return;

        try {
            $file = new File(self::FILE_CONFIG);
            $contents = $file->get_contents_as_array();
        } catch (Engine_Exception $e) {
            throw new Engine_Exception($e->get_message(), COMMON_ERROR);
        }

        $hostdata = array();

        foreach ($contents as $line) {

            $entries = preg_split('/[\s]+/', $line);
            $ip = array_shift($entries);

            // TODO: IPv6 won't work with ip2long

            if ($this->validate_ip($ip))
                continue;

            // Use long IP for proper sorting
            $ip_real = ip2long($ip);

            $this->hostdata[$ip_real]['ip'] = $ip;
            $this->hostdata[$ip_real]['hostname'] = array_shift($entries);
            $this->hostdata[$ip_real]['aliases'] = $entries;
        }

        ksort($this->hostdata);

        $this->is_loaded = TRUE;
    }
}
