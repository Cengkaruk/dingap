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
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Hosts extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/hosts';

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

    protected $host_data = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Hosts constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Add an entry to the /etc/hosts file.
     *
     * @param string $ip       IP address
     * @param string $hostname canonical hostname
     * @param string $aliases  array of aliases
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function add_entry($ip, $hostname, $aliases = array())
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_hostname($hostname));

        foreach ($aliases as $alias)
            Validation_Exception::is_valid($this->validate_alias($alias));

        if ($this->entry_exists($ip))
            throw new Validation_Exception(lang('network_host_entry_already_exists'));

        // Add
        //----

        $this->_load_entries();

        $file = new File(self::FILE_CONFIG);
        $file->add_lines("$ip $hostname " . implode(' ', $aliases) . "\n");

        // Force a re-read of the data
        $this->is_loaded = FALSE;
    }

    /**
     * Delete an entry from the /etc/hosts file.
     *
     * @param string $ip IP address
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function delete_entry($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_ip($ip));

        // Delete
        //-------

        $file = new File(self::FILE_CONFIG);
        $hosts = $file->delete_lines("/^$ip\s/i");

        $this->is_loaded = FALSE;
    }

    /**
     * Updates hosts entry for given IP address.
     *
     * @param string $ip       IP address
     * @param string $hostname canonical hostname
     * @param array  $aliases  aliases
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function edit_entry($ip, $hostname, $aliases = array())
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_hostname($hostname));

        foreach ($aliases as $alias)
            Validation_Exception::is_valid($this->validate_alias($alias));

        if (! $this->entry_exists($ip))
            throw new Validation_Exception(lang('network_entry_not_found'));

        // Update
        //-------

        $file = new File(self::FILE_CONFIG);
        $file->replace_lines("/^$ip\s+/i", "$ip $hostname " . implode(' ', $aliases) . "\n");

        $this->is_loaded = FALSE;
    }

    /**
     * Returns the hostname and aliases for the given IP address.
     *
     * @param string $ip IP address
     *
     * @return array an array containing the hostname and aliases
     * @throws  Exception, Validation_Exception
     */

    public function get_entry($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_ip($ip));

        // Get Entry
        //----------

        $this->_load_entries();

        foreach ($this->host_data as $real_ip => $entry) {
            if ($entry['ip'] == $ip)
                return $entry;
        }

        throw new Validation_Exception(lang('network_host_entry_not_found'));
    }

    /**
     * Returns information in the /etc/hosts file in an array.
     *
     * The array is indexed on IP, and contains an array of associated hosts.
     *
     * @return array list of host information
     * @throws Exception
     */

    public function get_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_entries();

        return $this->host_data;
    }

    /**
     * Returns the IP address for the given hostname.
     *
     * @param string $hostname hostname
     *
     * @return string IP address if hostname exists, NULL if it does not
     * @throws  Exception, Validation_Exception
     */

    public function get_ip_by_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_hostname($hostname));

        // Get Entry
        //----------

        $this->_load_entries();

        foreach ($this->host_data as $real_ip => $entry) {
            if (strcasecmp($entry['hostname'], $hostname) == 0)
                return $entry['ip'];

            foreach ($entry['aliases'] as $alias) {
                if (strcasecmp($alias, $hostname) == 0)
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
     * @throws Exception, Validation_Exception
     */

    public function entry_exists($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_ip($ip));

        // Get Entry
        //----------

        $this->_load_entries();

        foreach ($this->host_data as $real_ip => $entry) {
            if ($entry['ip'] === $ip)
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

        if (! Network_Utils::is_valid_hostname_alias($alias))
            return lang('network_hostname_alias_is_invalid');
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

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('network_hostname_is_invalid');
    }

    /**
     * Validates IP address entry.
     *
     * @param string  $ip           IP address
     * @param boolean $check_exists set to TRUE to check for pre-existing IP entry
     *
     * @return string error message if IP is invalid
     */

    public function validate_ip($ip, $check_exists = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_is_invalid');

        if ($check_exists) {
            if ($this->entry_exists($ip))
                return lang('network_entry_already_exists');
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads host info from /etc/hosts.
     *
     * @access private
     * @return void
     * @throws Exception
     */

    protected function _load_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_loaded)
            return;

        $this->is_loaded = FALSE;
        $this->host_data = array();

        $file = new File(self::FILE_CONFIG);
        $contents = $file->get_contents_as_array();

        foreach ($contents as $line) {

            $entries = preg_split('/[\s]+/', $line);
            $ip = array_shift($entries);

            $error_message = $this->validate_ip($ip);
            if (! empty($error_message))
                continue;

            // Use inet_pton for proper key sorting
            $addr_key = bin2hex(inet_pton($ip));

            $this->host_data[$addr_key]['ip'] = $ip;
            $this->host_data[$addr_key]['hostname'] = array_shift($entries);
            $this->host_data[$addr_key]['aliases'] = $entries;
        }

        ksort($this->host_data);

        $this->is_loaded = TRUE;
    }
}
