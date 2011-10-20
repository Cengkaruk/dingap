<?php

/**
 * Bandwidth class.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
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

namespace clearos\apps\bandwidth;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('bandwidth');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\bandwidth\Bandwidth as Bandwidth;
use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;
use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\firewall\Rule as Rule;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;

clearos_load_library('bandwidth/Bandwidth');
clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
clearos_load_library('firewall/Firewall');
clearos_load_library('firewall/Rule');
clearos_load_library('network/Iface');
clearos_load_library('network/Iface_Manager');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Bandwidth class.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
 */

class Bandwidth extends Firewall
{
	//////////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/clearos/bandwidth.conf';
	const MAX_IP_RANGE = 255;
	const CONSTANT_SPEED_NOT_SET = 0;

	const MODE_LIMIT = 0;
	const MODE_RESERVE = 1;

	const DIR_ORIGINATING_LAN = 0;
	const DIR_DESTINED_LAN = 1;
	const DIR_ORIGINATING_GW = 2;
	const DIR_DESTINED_GW = 3;

	const TYPE_BASIC = 1;
    const MAX_SPEED = 10000000;

	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();

	///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Bandwidth constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();
    }

    /**
     * Add a new 'basic' Bandwidth Rule.
     *
     * @param string  $name     bandwidth rule name
     * @param integer $mode     rule mode, limit or reserve
     * @param array   $service  service
     * @param integer $dir      rule direction
     * @param integer $speed    upstream/downstream rate
     * @param integer $priority rule priority
     * 
     * @param return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_basic_bandwidth_rule($name, $mode, $service, $dir, $speed, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_speed($speed));

        try {
            $flags = Rule::BANDWIDTH_RATE | Rule::BANDWIDTH_BASIC | Rule::ENABLED;

            switch ($mode) {
            case self::MODE_LIMIT:
                $ceil = $speed;
                break;
            case self::MODE_RESERVE:
                $ceil = 0;
                break;
            default:
                // TODO: add validation error...
                return;
            }

            $saddr = FALSE;
            $sport = FALSE;
            $internal = FALSE;

            switch ($dir) {
            case self::DIR_ORIGINATING_LAN:
                $flags |= Rule::LOCAL_NETWORK;
                $saddr = FALSE;
                $sport = FALSE;
                $internal = TRUE;
                break;
            case self::DIR_DESTINED_LAN:
                $flags |= Rule::LOCAL_NETWORK;
                $saddr = FALSE;
                $sport = TRUE;
                $internal = TRUE;
                break;
            case self::DIR_ORIGINATING_GW:
                $flags |= Rule::EXTERNAL_ADDR;
                $saddr = FALSE;
                $sport = FALSE;
                break;
            case self::DIR_DESTINED_GW:
                $flags |= Rule::EXTERNAL_ADDR;
                $saddr = FALSE;
                $sport = TRUE;
                break;
            default:
                // TODO: add validation error...
                return;
            }

            // TODO: Basic rules should use 'all' for the external interface name,
            // and the firewall should dynamically duplicate these rules for each
            // external interface.
            $ifm = new Iface_Manager();
            $ext_iflist = $ifm->GetExternalInterfaces();
            $ports = explode(':', $service['port']);
            foreach ($ports as $port) {
                foreach ($ext_iflist as $ext_ifn) {
                    $rule = new Rule();
                    $rule->SetName($name);
                    $rule->SetFlags($flags);
                    $rule->SetAddress('0.0.0.0');
                    $rule->SetPort($port);
                    $rule->SetParameter(
                        sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
                    $ext_ifn, $saddr, $sport, $priority,
                    $speed, $ceil, $speed, $ceil));
                    if ($rule->CheckValidationErrors() || (! empty($this->errors))) {
                        $this->errors = array_merge($rule->CopyValidationErrors(TRUE), $this->errors);
                        var_dump($this->errors);
                    }
                    else $this->AddRule($rule);
                }
            }

        } catch (Exception $e) {
            throw new Engine_Exception(lang('bandwidth_FIXME'));
        }
    }

    /**
     * Add a new Bandwidth Rule.
     *
     * @param  string  $name  the bandwidth rule name
     * @param  string  $ifn  the external interface
     * @param  string  $src_addr  addr type: 0 destination, 1 source
     * @param  string  $src_port  src type: 0 destination, 1 source
     * @param  string  $ip  the IP address
     * @param  int  $port  the port
     * @param  int  $priority  priority
     * @param  int  $upstream  upstream rate
     * @param  int  $upstream_ceil  upstream ceiling
     * @param  int  $downstream  downstream rate
     * @param  int  $downstream_ceil  downstream ceiling
     * @throws  Validation_Exception, Engine_Exception
     */

    public function add_bandwidth_rule($name, $ifn, $src_addr, $src_port, $ip, $port, $priority, $upstream = 0, $upstream_ceil = 0, $downstream = 0, $downstream_ceil = 0)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_speed($upstream));
        Validation_Exception::is_valid($this->validate_speed($upstream_ceil));
        Validation_Exception::is_valid($this->validate_speed($downstream));
        Validation_Exception::is_valid($this->validate_speed($downstream_ceil));

        if ($upstream == 0 && $downstream == 0)
            $this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_MISSING, __METHOD__, __LINE__);

        try {
            $rule = new Rule();
            $rule->SetFlags(Rule::BANDWIDTH_RATE | Rule::ENABLED);
            $rule->SetName($name);

            if (strlen($ip)) {
                $rule->SetAddress($ip);

                if (preg_match('/:/', $ip)) {
                    list($lo, $hi) = explode(':', $ip);
                    if (ip2long($hi) - ip2long($lo) > self::MAX_IP_RANGE) {
                        $this->AddValidationError(BANDWIDTH_LANG_ERRMSG_IPRANGE_TOO_LARGE, __METHOD__, __LINE__);
                    }
                }
            }

            if (strlen($port)) $rule->SetPort($port);

            $rule->SetParameter(sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
                $ifn, $src_addr, $src_port,
                $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil));

            if ($rule->CheckValidationErrors() || (! empty($this->errors)))
                $this->errors = array_merge($rule->CopyValidationErrors(TRUE), $this->errors);
            else
                $this->AddRule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(lang('bandwidth_FIXME'));
        }
    }

    /**
     * Toggle the enabled status of an existing "basic" bandwidth rule.
     *
     * @param  boolean  $enabled  the status
     * @param  string  $name bandwidth rule ID
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function toggle_enable_basic_bandwidth_rule($enabled, $name)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $rules = $this->get_rules();
            foreach ($rules as $rule) {
                if (!($rule->get_flags() & Rule::BANDWIDTH_RATE) ||
                    !($rule->get_flags() & Rule::BANDWIDTH_BASIC))
                    continue;
                if (strcmp($rule->get_name(), $name)) continue;

                $this->DeleteRule($rule);
                ($enabled) ? $rule->enable() : $rule->Disable();
                $this->AddRule($rule);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(lang('bandwidth_FIXME'));
        }
    }

    /**
     * Toggle the enabled status of an existing bandwidth rule.
     *
     * @param  boolean  $enabled  the status
     * @param  string  $ifn  external interface
     * @param  string  $src_addr  addr type: 0 destination, 1 source
     * @param  string  $src_port  port type: 0 destination, 1 source
     * @param  string  $ip  the IP address
     * @param  string  $port  the port
     * @param  int  $priority  priority
     * @param  int  $upstream  upstream rate
     * @param  int  $upstream_ceil  upstream ceiling
     * @param  int  $downstream  downstream rate
     * @param  int  $downstream_ceil  downstream rate
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function toggle_enable_bandwidth_rule($enabled, $ifn, $src_addr, $src_port, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $rule = new Rule();
            $rule->SetFlags(Rule::BANDWIDTH_RATE);

            if (strlen($ip))
                $rule->SetAddress($ip);

            if (strlen($port)) $rule->SetPort($port);

            $rule->SetParameter(sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
                $ifn, $src_addr, $src_port,
                $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil));

            if (! ($rule = $this->FindRule($rule)))
                return;

            $this->DeleteRule($rule);

            ($enabled) ? $rule->enable() : $rule->Disable();

            $this->AddRule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(lang('bandwidth_FIXME'));
        }
    }

    /**
     * Delete an existing "basic" bandwidth rule.
     *
     * @param  string  $name basic bandwidth rule ID
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function delete_basic_bandwidth_rule($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $rules = $this->get_rules();
            foreach ($rules as $rule) {
                if (!($rule->get_flags() & Rule::BANDWIDTH_RATE) ||
                    !($rule->get_flags() & Rule::BANDWIDTH_BASIC))
                    continue;
                if (strcmp($rule->get_name(), $name)) continue;

                $this->DeleteRule($rule);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(lang('bandwidth_FIXME'));
        }
    }

    /**
     * Delete an existing bandwidth rule.
     *
     * @param  string  $ifn  external interface
     * @param  string  $src_addr  addr type: 0 destination, 1 source
     * @param  string  $src_port  port type: 0 destination, 1 source
     * @param  string  $ip  the IP address
     * @param  string  $port  the port
     * @param  int  $priority  priority
     * @param  int  $upstream  upstream rate
     * @param  int  $upstream_ceil  upstream ceiling
     * @param  int  $downstream  downstream rate
     * @param  int  $downstream_ceil  downstream rate
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function delete_bandwidth_rule($ifn, $src_addr, $src_port, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $rule = new Rule();

            $rule->SetFlags(Rule::BANDWIDTH_RATE);

            if (strlen($ip))
                $rule->SetAddress($ip);

            if (strlen($port)) $rule->SetPort($port);

            $rule->SetParameter(sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
                $ifn, $src_addr, $src_port,
                $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil));

            $this->DeleteRule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(lang('bandwidth_FIXME'));
        }
    }

    /**
     * Get all bandwidth rules.
     *
     * @return array list of all bandwidth rules
     * @throws Engine_Exception
     */

    public function get_bandwidth_rules()
    {
        clearos_profile(__METHOD__, __LINE__);

        $entries = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::BANDWIDTH_RATE))
                continue;

            $info = array();
            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();
            $info['type'] = ($rule->get_flags() & Rule::BANDWIDTH_BASIC) ? self::TYPE_BASIC : 0;
            $info['host'] = $rule->get_address();
            $info['port'] = $rule->get_port();
            list(
                $info['wanif'],
                $info['src_addr'],
                $info['src_port'],
                $info['priority'],
                $info['upstream'],
                $info['upstream_ceil'],
                $info['downstream'],
                $info['downstream_ceil']) = split(':', $rule->get_parameter());

            settype($info['src_addr'], 'int');
            settype($info['src_port'], 'int');
            settype($info['priority'], 'int');
            settype($info['upstream'], 'int');
            settype($info['upstream_ceil'], 'int');
            settype($info['downstream'], 'int');
            settype($info['downstream_ceil'], 'int');

            if ($rule->get_flags() & Rule::BANDWIDTH_BASIC) {
                if ($rule->get_flags() & Rule::LOCAL_NETWORK &&
                    $info['src_addr'] == 0 && $info['src_port'] == 0)
                    $info['direction'] = self::DIR_ORIGINATING_LAN;
                else if ($rule->get_flags() & Rule::LOCAL_NETWORK &&
                    $info['src_addr'] == 0 && $info['src_port'] == 1)
                    $info['direction'] = self::DIR_DESTINED_LAN;
                else if ($rule->get_flags() & Rule::EXTERNAL_ADDR &&
                    $info['src_addr'] == 0 && $info['src_port'] == 0)
                    $info['direction'] = self::DIR_ORIGINATING_GW;
                else if ($rule->get_flags() & Rule::EXTERNAL_ADDR &&
                    $info['src_addr'] == 0 && $info['src_port'] == 1)
                    $info['direction'] = self::DIR_DESTINED_GW;
            } else $info['direction'] = -1;

            $entries[] = $info;
        }

        return $entries;
    }

    /**
     * Returns network interface details.
     *
     * @return array information about network interfaces
     * @throws Engine_Exception
     */

    public function get_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $ifacemanager = new Iface_Manager();
        $ifaces = $ifacemanager->get_external_interfaces();

        // TODO: setting up/down to zero if undefined ... is this still desirable?

        $ifaceinfo = array();

        foreach ($ifaces as $iface) {
            $ifaceinfo[$iface]['configured'] = TRUE;

            if (array_key_exists($iface, $this->config['BANDWIDTH_UPSTREAM'])) {
                $ifaceinfo[$iface]['upstream'] = $this->config['BANDWIDTH_UPSTREAM'][$iface];
            } else {
                $ifaceinfo[$iface]['upstream'] = 0;
                $ifaceinfo[$iface]['configured'] = FALSE;
            }

            if (array_key_exists($iface, $this->config['BANDWIDTH_DOWNSTREAM'])) {
                $ifaceinfo[$iface]['downstream'] = $this->config['BANDWIDTH_DOWNSTREAM'][$iface];
            } else {
                $ifaceinfo[$iface]['downstream'] = 0;
                $ifaceinfo[$iface]['configured'] = FALSE;
            }
        }

        return $ifaceinfo;
    }

    /**
     * Returns the state of the bandwidth manager.
     *
     * @return boolean TRUE if bandwidth manager is enabled
     * @throws Engine_Exception
     */

    public function get_engine_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $state = ($this->config['BANDWIDTH_QOS']) ? TRUE : FALSE;

        return $state;
    }

    /**
     * Returns state of network interface configuration details.
     *
     * @return boolean TRUE if all network interfaces have been configured.
     * @throws Engine_Exception
     */

    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifaces = $this->get_interfaces();

        foreach ($ifaces as $iface => $info) {
            if (!$info['configured'])
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Sets the state of the bandwidth manager.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_engine_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $this->config['BANDWIDTH_QOS'] = $state;

        $this->_save_configuration();
    }

    /**
     * Updates network interface information for a given interface.
     *
     * @param string  $iface      network interface
     * @param integer $upstream   upstream speed in kbit/s
     * @param integer $downstream downstream speed in kbit/s
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function update_interface($iface, $upstream, $downstream)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($iface));
        Validation_Exception::is_valid($this->validate_speed($upstream));
        Validation_Exception::is_valid($this->validate_speed($downstream));

        if (! $this->is_loaded)
            $this->_load_configuration();

        if ((!strlen($upstream) || ($upstream === Bandwidth::CONSTANT_SPEED_NOT_SET))
            && isset($this->config['BANDWIDTH_UPSTREAM'][$iface])
        )
            unset($this->config['BANDWIDTH_UPSTREAM'][$iface]);
        else
            $this->config['BANDWIDTH_UPSTREAM'][$iface] = $upstream;

        if ((!strlen($downstream) || ($downstream === Bandwidth::CONSTANT_SPEED_NOT_SET)) 
            && array_key_exists($iface, $this->config['BANDWIDTH_DOWNSTREAM'][$iface])
        )
            unset($this->config['BANDWIDTH_DOWNSTREAM'][$iface]);
        else
            $this->config['BANDWIDTH_DOWNSTREAM'][$iface] = $downstream;

        $this->_save_configuration();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates network interface.
     *
     * @param string $iface interface
     *
     * @return void
     */

    public function validate_interface($iface)
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();

        $ifaces = $iface_manager->get_interfaces();

        if (!in_array($iface, $ifaces))
            return lang('bandwidth_network_interface_is_invalid');
    }

    /**
     * Validates speed.
     *
     * @param integer $speed speed in kbit/s
     *
     * @return void
     */

    public function validate_speed($speed)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ((!preg_match("/^\d+$/", $speed) || ($speed > self::MAX_SPEED)))
            return lang('bandwidth_speed_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads bandwidth configuration.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    protected function _load_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = array();
        $config['BANDWIDTH_QOS'] = FALSE;
        $config['BANDWIDTH_UPSTREAM'] = array();
        $config['BANDWIDTH_DOWNSTREAM'] = array();
        $config['BANDWIDTH_UPSTREAM_BURST'] = array();
        $config['BANDWIDTH_UPSTREAM_CBURST'] = array();
        $config['BANDWIDTH_DOWNSTREAM_BURST'] = array();
        $config['BANDWIDTH_DOWNSTREAM_CBURST'] = array();

        $file = new Configuration_File(self::FILE_CONFIG);

        if (! $file->exists())
            throw new Engine_Exception(lang('bandwidth_configuration_file_missing'));

        $rawconfig = $file->load();

        foreach ($rawconfig as $key => $value) {
            $value = trim(str_replace(array('\'', '"'), '', $value));

            if ($key == 'BANDWIDTH_QOS') {
                $config['BANDWIDTH_QOS'] = (preg_match("/on/i", $value)) ? TRUE : FALSE;
            } else if (preg_match("/^(BANDWIDTH_UPSTREAM|BANDWIDTH_DOWNSTREAM)/", $key)) {
                $pairs = explode(' ', $value);

                foreach ($pairs as $pair) {
                    list($ifn, $speed) = explode(':', $pair, 2);
                    if (! empty($ifn))
                        $config[$key][$ifn] = $speed;
                }
            }
        }

        $this->is_loaded = TRUE;
        $this->config = $config;
    }

    /**
     * Saves bandwidth configuration.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    protected function _save_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->is_loaded = FALSE;

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            $file->create('root', 'root', '644');

        foreach ($this->config as $key => $value) {
            if ($key == 'BANDWIDTH_QOS') {
                if ($value === TRUE)
                    $value = 'on';
                else
                    $value = 'off';

                if (!$file->replace_lines("/.*$key=/", "$key=\"$value\"\n"))
                    $file->add_lines("$key=\"$value\"\n");
            } else if (!count($this->config[$key])) {
                $file->replace_lines("/.*$key=/", "#$key=\"\"\n");
            } else {
                $pairs = '';

                foreach ($this->config[$key] as $ifn => $speed)
                    $pairs .= "$ifn:$speed ";

                $pairs = trim($pairs);

                if (!$file->replace_lines("/^.*$key=/", "$key=\"$pairs\"\n"))
                    $file->add_lines("$key=\"$pairs\"\n");
            }
        }
    }
}
