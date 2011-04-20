<?php

/**
 * FreeRADIUS class.
 *
 * @category   Apps
 * @package    FreeRADIUS
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/radius/
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

namespace clearos\apps\radius;

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
clearos_load_language('radius');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
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
 * FreeRADIUS class.
 *
 * @category   Apps
 * @package    FreeRADIUS
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/radius/
 */

class FreeRADIUS extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $clients = array();

    const FILE_CLIENTS = '/etc/raddb/clearos-clients.conf';
    const FILE_USERS = '/etc/raddb/clearos-users';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * FreeRADIUS constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('radiusd');
    }

    /**
     * Adds a client.
     *
     * @param string $ip       client IP
     * @param string $password client password
     * @param string $nickname client nickname
     *
     * @return array clients information
     * @throws Engine_Exception
     */

    public function add_client($ip, $password, $nickname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_nickname($nickname));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->clients[$ip]['password'] = $password;
        $this->clients[$ip]['nickname'] = $nickname;

        $this->_save_config();
    }

    /**
     * Deletes a client.
     *
     * @param string $ip client IP
     *
     * @return array clients information
     * @throws Engine_Exception
     */

    public function delete_client($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));

        if (! $this->is_loaded)
            $this->_load_config();

        if (! isset($this->clients[$ip]))
            throw new Engine_Exception(FREERADIUS_LANG_CLIENT . ' - ' . LOCALE_LANG_INVALID, COMMON_WARNING);

        unset($this->clients[$ip]);

        $this->_save_config();
    }

    /**
     * Returns client information for a given nickname.
     *
     * @param string $ip client IP
     *
     * @return array clients information
     * @throws Engine_Exception
     */

    public function get_client_info($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->clients[$ip];
    }

    /**
     * Returns clients information.
     *
     * @return array clients information
     * @throws Engine_Exception
     */

    public function get_clients()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->clients;
    }

    /**
     * Returns user defined RADIUS group.
     *
     * @return string user defined RADIUS group
     * @throws Engine_Exception
     */

    public function get_group()
    {
        clearos_profile(__METHOD__, __LINE__);

        $lines = array();

        $usersfile = new File(self::FILE_USERS, TRUE);
        $lines = $usersfile->get_contents_as_array();

        $group = '';

        foreach ($lines as $line) {
            $matches = array();
            if (preg_match('/^DEFAULT LDAP-Group .= "([^\"]*)",/', $line, $matches)) {
                $group = $matches[1];
                break;
            }
        }

        return $group;
    }

    /**
     * Updates client information.
     *
     * @param string $ip       client IP
     * @param string $password client password
     * @param string $nickname client nickname
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_client($ip, $password, $nickname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_nickname($nickname));

        if (! $this->is_loaded)
            $this->_load_config();

        $this->clients[$ip]['nickname'] = $nickname;
        $this->clients[$ip]['password'] = $password;

        $this->_save_config();
    }

    /**
     * Updates user defined RADIUS group.
     *
     * @param string $group user defined RADIUS group
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_group($group)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_USERS, TRUE);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'radiusd', '0640');
        $file->add_lines("DEFAULT LDAP-Group != \"$group\", Auth-Type := Reject\n");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates client.
     *
     * @param string $ip client IP
     *
     * @return string error message if client is invalid
     * @throws Engine_Exception
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_is_invalid');
    }

    /**
     * Validates nickname.
     *
     * @param string $nickname client nickname
     *
     * @return string error message if nickname is invalid
     * @throws Engine_Exception
     */

    public function validate_nickname($nickname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nickname))
            return lang('radius_nickname_is_invalid');
    }

    /**
     * Validates password.
     *
     * @param string $password client password
     *
     * @return string error message if password is invalid
     * @throws Engine_Exception
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
        //    return lang('base_password_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    private function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $lines = array();

        $clientsfile = new File(self::FILE_CLIENTS, TRUE);
        $lines = $clientsfile->get_contents_as_array();

        $client = '';
        $this->clients = array();

        foreach ($lines as $line) {
            $matches = array();

            if (preg_match('/^\s*client\s*([^\s]+)\s*{/', $line, $matches))
                $client = $matches[1];
            else if (preg_match('/^\s*secret\s*=\s*([^\s]+)/', $line, $matches))
                $this->clients[$client]['password'] =  $matches[1];
            else if (preg_match('/^\s*shortname\s*=\s*([^\s]+)/', $line, $matches))
                $this->clients[$client]['nickname'] =  $matches[1];
        }

        ksort($this->clients);

        $this->is_loaded = TRUE;
    }

    /**
     * Saves configuration file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    private function _save_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $contents = '';

        foreach ($this->clients as $client => $details) {
            $contents .= "client $client {\n";
            $contents .= "\tsecret = " . $details['password'] . "\n";
            $contents .= "\tshortname = " . $details['nickname'] . "\n";
            $contents .= "}\n";
        }

        $clientsfile = new File(self::FILE_CLIENTS, TRUE);

        if ($clientsfile->exists())
            $clientsfile->delete();

        $clientsfile->create('root', 'radiusd', '0640');
        $clientsfile->add_lines($contents);

        $this->is_loaded = FALSE;
    }
}
