<?php

/**
 * SnortSam intrusion prevention class.
 *
 * @category   Apps
 * @package    Intrusion_Prevention
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_prevention/
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

namespace clearos\apps\intrusion_prevention;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('intrusion_prevention');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * SnortSam intrusion prevention class.
 *
 * @category   Apps
 * @package    Intrusion_Prevention
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_prevention/
 */

class SnortSam extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_STATE = '/var/db/snortsam.state';
    const FILE_WHITELIST = '/etc/snortsam.d/webconfig-whitelist.conf';
    const COMMAND_STATE = '/usr/bin/snortsam-state';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * SnortSam constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('snortsam');
    }

    /**
     * Adds IP address to white list.
     *
     * @param string $ip IP address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_whitelist_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));

        $list = array();

        $file = new File(self::FILE_WHITELIST);

        if ($file->exists())
            $list = $this->get_whitelist();
        else
            $file->create('root', 'root', '0644');

        foreach ($list as $entry) {
            if ($ip === $entry)
                return;
        }

        $file->add_lines("dontblock $ip\n");
    }

    /**
     * Delete a blocked host.
     *
     * @param string $crc CRC of blocked host to delete (can also be 'all')
     *
     * @return void
     */

    public function delete_blocked_crc($crc)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_crc($crc));

        $shell = new Shell();
        $shell->execute(self::COMMAND_STATE, "-D $crc", TRUE);
    }

    /**
     * Delete a blocked host.
     *
     * @param string $ip IP address to unblock
     *
     * @return void
     */

    public function delete_blocked_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));

        $block_list = $this->get_block_list();

        foreach ($block_list as $key => $info) {
            if ($info['peer_ip'] == $ip) {
                $this->delete_blocked_crc($info['crc']);
                return;
            }
        }
    }

    /**
     * Deletes IP address from white list.
     *
     * @param string $ip IP address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function delete_whitelist_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));

        $file = new File(self::FILE_WHITELIST);

        $ip = preg_quote($ip, '/');

        $file->delete_lines("/^dontblock\s+$ip$/");
    }

    /**
     * Returns the current block list.
     *
     * @return array information on blocked IPs 
     * @throws Engine_Exception
     */

    public function get_block_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATE);

        if (! $file->exists())
            return array();

        $lines = array();

        $shell = new Shell();
        $shell->execute(self::COMMAND_STATE, ' -q -d :', TRUE);
        $lines = $shell->get_output();

        $blockinfo = array();
        $block_list = array();

        foreach ($lines as $line) {
            if (!strlen($line))
                continue;

            $fields = explode(':', $line);

            // timestamp is first key (for sorting)
            $blockinfo['timestamp'] = $fields[5];
            $blockinfo['sid'] = $fields[0];
            $blockinfo['blocked_ip'] = $fields[1];
            $blockinfo['peer_ip'] = $fields[2];
            $blockinfo['peer_port'] = $fields[3];
            $blockinfo['protocol'] = strtoupper($fields[4]);
            $blockinfo['duration'] = $fields[6];
            $blockinfo['crc'] = $fields[8];
            $block_list[] = $blockinfo;
        }

        rsort($block_list);

        return $block_list;
    }

    /**
     * Returns IP addresses in the white list.
     *
     * @return array list of IP addresses in the white list
     * @throws Engine_Exception
     */

    public function get_whitelist()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        $file = new File(self::FILE_WHITELIST);

        if (! $file->exists())
            return $list;

        $output = $file->get_contents_as_array();

        $matches = array();

        foreach ($output as $line) {
            if (preg_match("/^dontblock\s+(.*)/i", $line, $matches))
                $list[] = $matches[1];
        }

        return $list;
    }

    /**
     * Resets the current block list.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function reset_block_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $shell->execute(self::COMMAND_STATE, "-D all", TRUE);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates IP address.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP address is invalid
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');
    }

    /**
     * Validates CRC.
     *
     * @param string $crc CRC
     *
     * @return string error message if CRC is invalid
     */

    public function validate_crc($crc)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[a-zA-Z0-9]+$/', $crc))
            return lang('intrusion_prevention_crc_invalid');
    }

    /**
     * Validates IP address.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP address is invalid
     */

    public function validate_whitelist_ip($ip, $check_exists = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');

        if ($check_exists && in_array($ip, $this->get_whitelist()))
            return lang('base_entry_already_exists');
    }
}
