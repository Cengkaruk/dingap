<?php

/**
 * OpenSSH server class.
 *
 * @category   Apps
 * @package    OpenSSH
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ssh_server/
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

namespace clearos\apps\ssh_server;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('ssh_server');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenSSH server class.
 *
 * @category   Apps
 * @package    OpenSSH
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ssh_server/
 */

class OpenSSH extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/ssh/sshd_config';

    const DEFAULT_PORT = 22;
    const DEFAULT_PASSWORD_AUTHENTICATION = TRUE;
    const DEFAULT_PERMIT_ROOT_LOGIN = 'yes';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();
    protected $permit_root_login_options = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenSSH constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('sshd');

        $this->permit_root_login_options = array(
            'yes' => lang('base_enabled'),
            'no' => lang('base_disabled'),
        );
    }

    /**
     * Returns port.
     *
     * @return integer port number
     * @throws Engine_Exception
     */

    public function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['Port']))
            return $this->config['Port'];
        else
            return self::DEFAULT_PORT;
    }

    /**
     * Returns password authentication policy.
     *
     * @return boolean password authentication policy
     * @throws Engine_Exception
     */

    public function get_password_authentication_policy()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['PasswordAuthentication']))
            return $this->_get_boolean($this->config['PasswordAuthentication']);
        else
            return self::DEFAULT_PASSWORD_AUTHENTICATION;
    }

    /**
     * Returns root login policy.
     *
     * For now, we only allow "yes" and "no" for this option.
     *
     * @return string root login policy
     * @throws Engine_Exception
     */

    public function get_permit_root_login_policy()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['PermitRootLogin'])) {
            if (array_key_exists($this->config['PermitRootLogin'], $this->permit_root_login_options))
                return $this->config['PermitRootLogin'];
            else
                throw new Validation_Exception(lang('base_exception_file_parse_error'));
        } else {
            return self::DEFAULT_PERMIT_ROOT_LOGIN;
        }
    }

    /**
     * Returns root login options.
     *
     * For now, we only allow "yes" and "no" for this option.
     *
     * @return string root login policy
     * @throws Engine_Exception
     */

    public function get_permit_root_login_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->permit_root_login_options;
    }

    /**
     * Sets password authentication policy.
     *
     * @param boolean $policy policy
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_password_authentication_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_password_authentication_policy($policy));

        $policy_value = ($policy) ? 'yes' : 'no';

        $this->_set_parameter('PasswordAuthentication', $policy_value);
    }

    /**
     * Sets permit root login policy.
     *
     * @param boolean $policy policy
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_permit_root_login_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_permit_root_login_policy($policy));

        $this->_set_parameter('PermitRootLogin', $policy);
    }

    /**
     * Sets port.
     *
     * @param integer $port port
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_port($port));

        $this->_set_parameter('Port', $port);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates password authentication policy.
     *
     * @param boolean $policy $policy
     *
     * @return string error message if policy is invalid
     */
    
    public function validate_password_authentication_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($policy))
            return lang('ssh_server_password_authentication_policy_invalid');
    }

    /**
     * Validates permit root login policy.
     *
     * @param boolean $policy $policy
     *
     * @return string error message if policy is invalid
     */
    
    public function validate_permit_root_login_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($policy, $this->permit_root_login_options))
            return lang('ssh_server_permit_root_login_policy_invalid');
    }

    /**
     * Validates port.
     *
     * @param integer $port port
     *
     * @return string error message if port is invalid
     */
    
    public function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_port($port))
            return lang('network_port_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S 
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Converts SSH configuration file booleans.
     *
     * @param string $boolean_text SSH configuration file booleans
     *
     * @return boolean boolean for given text
     */

    protected function _get_boolean($boolean_text)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match('/yes/i', $boolean_text))
            return TRUE;
        else if (preg_match('/no/i', $boolean_text))
            return FALSE;
        else
            throw new Validation_Exception(lang('base_exception_file_parse_error'));
    }

    /**
     * Loads configuration files.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config_file = new Configuration_File(self::FILE_CONFIG, 'split', '/\s+/');
        $this->config = $config_file->load();

        $this->is_loaded = TRUE;
    }

    /**
     * Sets a parameter in the config file.
     *
     * @param string $key   name of the key in the config file
     * @param string $value value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^$key\s+/", "$key $value\n");

        if ($match === 0) {
            $match = $file->replace_lines("/^#\s*$key\s+/", "$key $value\n");
            if ($match === 0)
                $file->add_lines("$key = $value\n");
        }

        $this->is_loaded = FALSE;
    }
}
