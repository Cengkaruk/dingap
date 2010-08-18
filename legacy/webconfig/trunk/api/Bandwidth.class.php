<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Bandwidth manager
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('ConfigurationFile.class.php');
require_once('Firewall.class.php');
require_once('FirewallRule.class.php');
require_once('IfaceManager.class.php');
require_once('Iface.class.php');

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Bandwidth manager
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class Bandwidth extends Firewall
{
	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/firewall';
	const MAX_IP_RANGE = 255;
	const CONSTANT_SPEED_NOT_SET = 0;

	const MODE_LIMIT = 0;
	const MODE_RESERVE = 1;

	const DIR_ORIGINATING_LAN = 0;
	const DIR_DESTINED_LAN = 1;
	const DIR_ORIGINATING_GW = 2;
	const DIR_DESTINED_GW = 3;

	const TYPE_BASIC = 1;

	protected $is_loaded = false;
	protected $config = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Bandwidth constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Add a new 'basic' Bandwidth Rule.
	 *
	 * @param  string  $name  the bandwidth rule name
	 * @param  int $mode rule mode, limit or reserve
	 * @param  array $service
	 * @param  int $dir rule direction
	 * @param  int  $speed upstream/downstream rate
	 * @param  int  $priority rule priority
	 * @throws  ValidationException, EngineException
	 */

	public function AddBasicBandwidthRule($name, $mode, $service, $dir, $speed, $priority)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($speed == 0) {
			// FIXME: locale string is not accurate
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_MISSING, __METHOD__, __LINE__);
			return;
		} else if (!$this->IsValidSpeed($speed)) {
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID, __METHOD__, __LINE__);
			return;
		}

		try {
			$flags = FirewallRule::BANDWIDTH_RATE | FirewallRule::BANDWIDTH_BASIC | FirewallRule::ENABLED;

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

			$saddr = false;
			$sport = false;
			$internal = false;

			switch ($dir) {
			case self::DIR_ORIGINATING_LAN:
				$flags |= FirewallRule::LOCAL_NETWORK;
				$saddr = false;
				$sport = false;
				$internal = true;
				break;
			case self::DIR_DESTINED_LAN:
				$flags |= FirewallRule::LOCAL_NETWORK;
				$saddr = false;
				$sport = true;
				$internal = true;
				break;
			case self::DIR_ORIGINATING_GW:
				$flags |= FirewallRule::EXTERNAL_ADDR;
				$saddr = false;
				$sport = false;
				break;
			case self::DIR_DESTINED_GW:
				$flags |= FirewallRule::EXTERNAL_ADDR;
				$saddr = false;
				$sport = true;
				break;
			default:
				// TODO: add validation error...
				return;
			}

			// TODO: Basic rules should use 'all' for the external interface name,
			// and the firewall should dynamically duplicate these rules for each
			// external interface.
			$ifm = new IfaceManager();
			$ext_iflist = $ifm->GetExternalInterfaces();
			$ports = explode(':', $service['port']);
			foreach ($ports as $port) {
				foreach ($ext_iflist as $ext_ifn) {
					$rule = new FirewallRule();
					$rule->SetName($name);
					$rule->SetFlags($flags);
					$rule->SetAddress('0.0.0.0');
					$rule->SetPort($port);
					$rule->SetParameter(
						sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
					$ext_ifn, $saddr, $sport, $priority,
					$speed, $ceil, $speed, $ceil));
					if ($rule->CheckValidationErrors() || (! empty($this->errors))) {
						$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
						var_dump($this->errors);
					}
					else $this->AddRule($rule);
				}
			}

		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
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
	 * @throws  ValidationException, EngineException
	 */

	public function AddBandwidthRule($name, $ifn, $src_addr, $src_port, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!strlen($upstream)) $upstream = 0;
		else if (!$this->IsValidSpeed($upstream))
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID . ' - ' . BANDWIDTH_LANG_UPLOADSPEED, __METHOD__, __LINE__);
		if (!strlen($upstream_ceil)) $upstream_ceil = 0;
		else if (!$this->IsValidSpeed($upstream_ceil))
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID . ' - ' . BANDWIDTH_LANG_UPLOADSPEED, __METHOD__, __LINE__);

		if (!strlen($downstream)) $downstream = 0;
		if (!$this->IsValidSpeed($downstream))
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID . ' - ' . BANDWIDTH_LANG_DOWNLOADSPEED, __METHOD__, __LINE__);
		if (!strlen($downstream_ceil)) $downstream_ceil = 0;
		else if (!$this->IsValidSpeed($downstream_ceil))
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID . ' - ' . BANDWIDTH_LANG_DOWNLOADSPEED, __METHOD__, __LINE__);

		if ($upstream == 0 && $downstream == 0)
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_MISSING, __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetFlags(FirewallRule::BANDWIDTH_RATE | FirewallRule::ENABLED);
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
				$this->errors = array_merge($rule->CopyValidationErrors(true), $this->errors);
			else
				$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Toggle the enabled status of an existing "basic" bandwidth rule.
	 *
	 * @param  boolean  $enabled  the status
	 * @param  string  $name bandwidth rule ID
	 * @return  void
	 * @throws  EngineException
	 */

	public function ToggleEnableBasicBandwidthRule($enabled, $name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$rules = $this->GetRules();
			foreach ($rules as $rule) {
				if (!($rule->GetFlags() & FirewallRule::BANDWIDTH_RATE) ||
					!($rule->GetFlags() & FirewallRule::BANDWIDTH_BASIC))
					continue;
				if (strcmp($rule->GetName(), $name)) continue;

				$this->DeleteRule($rule);
				($enabled) ? $rule->Enable() : $rule->Disable();
				$this->AddRule($rule);
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
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
	 * @return  void
	 * @throws  EngineException
	 */

	public function ToggleEnableBandwidthRule($enabled, $ifn, $src_addr, $src_port, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();
			$rule->SetFlags(FirewallRule::BANDWIDTH_RATE);

			if (strlen($ip))
				$rule->SetAddress($ip);

			if (strlen($port)) $rule->SetPort($port);

			$rule->SetParameter(sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
				$ifn, $src_addr, $src_port,
				$priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil));

			if (! ($rule = $this->FindRule($rule)))
				return;

			$this->DeleteRule($rule);

			($enabled) ? $rule->Enable() : $rule->Disable();

			$this->AddRule($rule);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Delete an existing "basic" bandwidth rule.
	 *
	 * @param  string  $name basic bandwidth rule ID
	 * @return  void
	 * @throws  EngineException
	 */

	public function DeleteBasicBandwidthRule($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$rules = $this->GetRules();
			foreach ($rules as $rule) {
				if (!($rule->GetFlags() & FirewallRule::BANDWIDTH_RATE) ||
					!($rule->GetFlags() & FirewallRule::BANDWIDTH_BASIC))
					continue;
				if (strcmp($rule->GetName(), $name)) continue;

				$this->DeleteRule($rule);
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
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
	 * @return  void
	 * @throws  EngineException
	 */

	public function DeleteBandwidthRule($ifn, $src_addr, $src_port, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$rule = new FirewallRule();

			$rule->SetFlags(FirewallRule::BANDWIDTH_RATE);

			if (strlen($ip))
				$rule->SetAddress($ip);

			if (strlen($port)) $rule->SetPort($port);

			$rule->SetParameter(sprintf('%s:%d:%d:%d:%d:%d:%d:%d',
				$ifn, $src_addr, $src_port,
				$priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil));

			$this->DeleteRule($rule);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Disables bandwidth manager.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function Disable()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

        $this->config['BANDWIDTH_QOS'] = false;

        $this->_SaveConfiguration();

		try {
			$firewall = new Firewall();
			$firewall->Restart();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Enables bandwidth manager.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function Enable()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

        $this->config['BANDWIDTH_QOS'] = true;

        $this->_SaveConfiguration();

		try {
			$firewall = new Firewall();
			$firewall->Restart();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Get all bandwidth rules.
	 *
	 * @return  array  a list of all bandwidth rules
	 * @throws  EngineException
	 */

	public function GetBandwidthRules()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$entries = array();

		try {
			$rules = $this->GetRules();

			foreach ($rules as $rule) {
				if (!($rule->GetFlags() & FirewallRule::BANDWIDTH_RATE))
					continue;

				$info = array();
				$info['name'] = $rule->GetName();
				$info['enabled'] = $rule->IsEnabled();
				if ($rule->GetFlags() & FirewallRule::BANDWIDTH_BASIC) {
					$info['type'] = self::TYPE_BASIC;
				} else
					$info['type'] = 0;
				$info['host'] = $rule->GetAddress();
				$info['port'] = $rule->GetPort();
				list(
					$info['wanif'],
					$info['src_addr'],
					$info['src_port'],
					$info['priority'],
					$info['upstream'],
					$info['upstream_ceil'],
					$info['downstream'],
					$info['downstream_ceil']) = split(':', $rule->GetParameter());

				settype($info['src_addr'], 'int');
				settype($info['src_port'], 'int');
				settype($info['priority'], 'int');
				settype($info['upstream'], 'int');
				settype($info['upstream_ceil'], 'int');
				settype($info['downstream'], 'int');
				settype($info['downstream_ceil'], 'int');

				if ($rule->GetFlags() & FirewallRule::BANDWIDTH_BASIC) {
					if ($rule->GetFlags() & FirewallRule::LOCAL_NETWORK &&
						$info['src_addr'] == 0 && $info['src_port'] == 0)
						$info['direction'] = self::DIR_ORIGINATING_LAN;
					else if ($rule->GetFlags() & FirewallRule::LOCAL_NETWORK &&
						$info['src_addr'] == 0 && $info['src_port'] == 1)
						$info['direction'] = self::DIR_DESTINED_LAN;
					else if ($rule->GetFlags() & FirewallRule::EXTERNAL_ADDR &&
						$info['src_addr'] == 0 && $info['src_port'] == 0)
						$info['direction'] = self::DIR_ORIGINATING_GW;
					else if ($rule->GetFlags() & FirewallRule::EXTERNAL_ADDR &&
						$info['src_addr'] == 0 && $info['src_port'] == 1)
						$info['direction'] = self::DIR_DESTINED_GW;
				} else $info['direction'] = -1;

				$entries[] = $info;
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		return $entries;
	}

	/**
	 * Returns network interface details.
	 *
	 * @return array information about network interfaces
	 * @throws EngineException
	 */

	public function GetInterfaces()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		try {
			$ifacemanager = new IfaceManager();
			$ifaces = $ifacemanager->GetExternalInterfaces();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// TODO: setting up/down to zero if undefined ... is this still desirable?

		$ifaceinfo = array();

		foreach ($ifaces as $iface) {
			$ifaceinfo[$iface]['configured'] = true;

			if (array_key_exists($iface, $this->config['BANDWIDTH_UPSTREAM'])) {
				$ifaceinfo[$iface]['upstream'] = $this->config['BANDWIDTH_UPSTREAM'][$iface];
			} else {
				$ifaceinfo[$iface]['upstream'] = 0;
				$ifaceinfo[$iface]['configured'] = false;
			}

			if (array_key_exists($iface, $this->config['BANDWIDTH_DOWNSTREAM'])) {
				$ifaceinfo[$iface]['downstream'] = $this->config['BANDWIDTH_DOWNSTREAM'][$iface];
			} else {
				$ifaceinfo[$iface]['downstream'] = 0;
				$ifaceinfo[$iface]['configured'] = false;
			}
		}

		return $ifaceinfo;
	}

	/**
	 * Returns the state of the bandwidth manager.
	 *
	 * @return boolean true if bandwidth manager is enabled
	 * @throws EngineException
	 */

	public function GetState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		$state = ($this->config['BANDWIDTH_QOS']) ? true : false;

		return $state;
	}

	/**
	 * Returns state of network interface configuration details.
	 *
	 * @return boolean true if all network interfaces have been configured.
	 * @throws EngineException
	 */

	public function IsInitialized()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ifaces = $this->GetInterfaces();

		foreach ($ifaces as $iface => $info) {
			if (!$info['configured'])
				return false;
		}

		return true;
	}

	/**
	 * Updates network interface information for a given interface.
	 *
	 * @param string $iface network interface
	 * @param int $upstream upstream speed in kbit/s
	 * @param int $downstream downstream speed in kbit/s
	 * @return void
	 * @throws EngineException
	 */

	public function UpdateInterface($interface, $upstream, $downstream)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$valid = true;

		$iface = new Iface($interface);
		
		if (! $iface->IsValid()) {
			$this->AddValidationError(IFACE_LANG_INTERFACE . " - " . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
			$valid = false;
		}

		if (!(($upstream > 0) && ($upstream < 1000000000))) {
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID, __METHOD__, __LINE__);
			$valid = false;
		}

		if (!(($downstream > 0) && ($downstream < 1000000000))) {
			$this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_INVALID, __METHOD__, __LINE__);
			$valid = false;
		}

		if (!$valid)
			return;

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		if ((!strlen($upstream) || ($upstream === Bandwidth::CONSTANT_SPEED_NOT_SET)) && isset($this->config['BANDWIDTH_UPSTREAM'][$interface]))
            unset($this->config['BANDWIDTH_UPSTREAM'][$interface]);
        else
            $this->config['BANDWIDTH_UPSTREAM'][$interface] = $upstream;

        if ((!strlen($downstream) || ($downstream === Bandwidth::CONSTANT_SPEED_NOT_SET)) && array_key_exists($interface, $this->config['BANDWIDTH_DOWNSTREAM'][$interface]))
            unset($this->config['BANDWIDTH_DOWNSTREAM'][$interface]);
        else
            $this->config['BANDWIDTH_DOWNSTREAM'][$interface] = $downstream;

        $this->_SaveConfiguration();

		try {
			$firewall = new Firewall();
			$firewall->Restart();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

	public function IsValidSpeed($speed)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!preg_match("/^\d+$/", $speed))
			return false;

		return true;
	}

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads bandwidth configuration.
	 *
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	public function _LoadConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$config = array();
		$config['BANDWIDTH_QOS'] = false;
		$config['BANDWIDTH_UPSTREAM'] = array();
		$config['BANDWIDTH_DOWNSTREAM'] = array();
		$config['BANDWIDTH_UPSTREAM_BURST'] = array();
		$config['BANDWIDTH_UPSTREAM_CBURST'] = array();
		$config['BANDWIDTH_DOWNSTREAM_BURST'] = array();
		$config['BANDWIDTH_DOWNSTREAM_CBURST'] = array();

		$file = new ConfigurationFile(self::FILE_CONFIG);

		if (! $file->Exists())
			throw new EngineException ("Firewall configuration file is missing", COMMON_ERROR);

		try {
			$rawconfig = $file->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach ($rawconfig as $key => $value) {
			$value = trim(str_replace(array('\'', '"'), '', $value));

			if ($key == 'BANDWIDTH_QOS') {
				$config['BANDWIDTH_QOS'] = (preg_match("/on/i", $value)) ? true : false;
			} else if (preg_match("/^(BANDWIDTH_UPSTREAM|BANDWIDTH_DOWNSTREAM)/", $key)) {
				$pairs = explode(' ', $value);

				foreach ($pairs as $pair) {
					list($ifn, $speed) = explode(':', $pair, 2);
					if (! empty($ifn))
						$config[$key][$ifn] = $speed;
				}
			}
		}

		$this->is_loaded = true;
		$this->config = $config;
	}

	/**
	 * Saves bandwidth configuration.
	 *
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	public function _SaveConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->is_loaded = false;

		$file = new File(self::FILE_CONFIG);

		try {
			if (! $file->Exists())
				$file->Create('root', 'root', '644');
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		foreach ($this->config as $key => $value) {
			if ($key == 'BANDWIDTH_QOS') {
				if ($value === true) $value = 'on';
				else $value = 'off';
				try {
					if (!$file->ReplaceLines("/.*$key=/", "$key=\"$value\"\n"))
						$file->AddLines("$key=\"$value\"\n");
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			} else if (!count($this->config[$key])) {
				try {
					$file->ReplaceLines("/.*$key=/", "#$key=\"\"\n");
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			} else {
				$pairs = '';
				foreach ($this->config[$key] as $ifn => $speed)
					$pairs .= "$ifn:$speed ";
				$pairs = trim($pairs);
				try {
					if (!$file->ReplaceLines("/^.*$key=/", "$key=\"$pairs\"\n"))
						$file->AddLines("$key=\"$pairs\"\n");
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			}
		}
	}


    /**
     * @access private
     */

    function __destruct()
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        parent::__destruct();
    }
}

// vim: syntax=php ts=4
?>
