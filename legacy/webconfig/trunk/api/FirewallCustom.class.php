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
 * FirewallCustom class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("File.class.php");
require_once("ShellExec.class.php");

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * FirewallCustom class.
 *
 * Provides interface to add, edit and delete custom firewall rules.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class FirewallCustom extends Daemon
{
	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $configuration = null;
	protected $is_loaded = false;

	const FILE_CONFIG = "/etc/rc.d/rc.firewall.custom";
	const FILE_FIREWALL_STATE = "/var/lib/firewall/invalid.state";
	const MOVE_UP = -1;
	const MOVE_DOWN = 1;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * FirewallCustom constructor.
	 *
	 * @return  void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('firewallcustom');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Get array of custom firewall rules.
	 *
	 * @return array of rules
	 * @throws EngineException
	 */

	public function GetRules()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		$rules = array();

		$index = -1;

		foreach ($this->configuration as $entry) {
			$index++;
			$rule = Array ('line' => $index, 'enabled' => 0, 'description' => '');
			if (preg_match('/^\s*$/', $entry, $match)) {
				// Blank line
				continue;
			} else if (preg_match('/^\s*#\s*iptables\s+([^#]*)#(.*)/', $entry, $match)) {
				$rule['entry'] = 'iptables ' . $match[1];
				$rule['description'] = $match[2];
			} else if (preg_match('/^\s*#\s*iptables\s+(.*)/', $entry, $match)) {
				$rule['entry'] = 'iptables ' . $match[1];
			} else if (preg_match('/^\s*#(.*)/', $entry, $match)) {
				// Comment only
				continue;
			} else if (preg_match('/^\s*iptables\s+([^#]*)#(.*)/', $entry, $match)) {
				$rule['entry'] = 'iptables ' . $match[1];
				$rule['enabled'] = 1;
				$rule['description'] = $match[2];
			} else {
				$rule['entry'] = $entry;
				$rule['enabled'] = 1;
			}
			$rules[$index] = $rule;
		}

		return $rules;
	}

	/**
	 * Toggle rule status (enable/disable)
	 *
	 * @throws EngineException
	 */

	public function ToggleRule($line, $status)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		try {
			if ($status) {
				if (preg_match('/^\s*#\s*iptables\s+(.*)/', $this->configuration[$line], $match))
					$this->configuration[$line] = 'iptables ' . $match[1];
				else
					throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
				
			} else {
				$this->configuration[$line] = '# ' . $this->configuration[$line]; 
			}
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Get rule
	 *
	 * @throws EngineException
	 */

	public function GetRule($line)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$rules = $this->GetRules();
			return $rules[$line];
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Add new rule
	 *
	 * @throws EngineException
	 */

	public function AddRule($entry, $description, $enabled, $priority)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		try {
			if (!isset($entry) || $entry == '')
				throw new EngineException(FIREWALL_CUSTOM_LANG_ERRMSG_NO_RULE, COMMON_WARNING);
			if (!preg_match("/^iptables.*$/", $entry))
				throw new EngineException(FIREWALL_CUSTOM_LANG_ERRMSG_INVALID_RULE, COMMON_WARNING);

			if ($priority > 0)
				array_unshift($this->configuration, ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : ""));
			else
				array_push($this->configuration, ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : ""));

			# Rule has been added, but it might be in front of top-header comments
			if ($priority > 0) {
				$linenumber = 0;
				foreach ($this->configuration as $entry) {
					# Line 0 is our new addition
					if ($linenumber == 0) {
						$swap = $entry;
					} else if (preg_match('/^\s*$/', $entry)) {
						# Blank line
						$this->configuration[$linenumber - 1] = $this->configuration[$linenumber];
						$this->configuration[$linenumber] = $swap;
					} else if (preg_match('/^\s*iptables.*/', $entry)) {
						# Not a comment...break;
						break;
					} else if (!preg_match('/^\s*#\s*iptables.*/', $entry)) {
						# Comment
						$this->configuration[$linenumber - 1] = $this->configuration[$linenumber];
						$this->configuration[$linenumber] = $swap;
					}
					$linenumber++;
				}
			}
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Update/Edit rule
	 *
	 * @throws EngineException
	 */

	public function UpdateRule($line, $entry, $description, $enabled)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		try {
			$this->configuration[$line] = ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : "");
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Delete rule
	 *
	 * @throws EngineException
	 */

	public function DeleteRule($line)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		try {
			unset($this->configuration[$line]);
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Move rule up in table
	 *
	 * @throws EngineException
	 */

	public function SetRulePriority($line, $direction)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfiguration();

		try {
			$swap = $this->configuration[$line + $direction];
			$counter = 1;
			while (!preg_match("/\s*iptables.*/", $swap) && !preg_match("/\s*#\s*iptables.*/", $swap)) {
				$counter++;
				$swap = $this->configuration[$line + $counter * $direction];
			}
			$this->configuration[$line + $counter * $direction] = $this->configuration[$line];
			$this->configuration[$line] = $swap;
			$this->_SaveConfiguration();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Determine if firewall restart is required
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	public function IsFirewallRestartRequired()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$config = new File(self::FILE_CONFIG);
			$state = new File(self::FILE_FIREWALL_STATE); 

			if ($config->LastModified() > $state->LastModified())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_WARNING);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
	 * @load configuration file
	 */

	function _LoadConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);
		$this->configuration = $file->GetContentsAsArray();
		$this->is_loaded = true;
	}

	/**
	 * @save configuration file
	 */

	function _SaveConfiguration()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
		// Delete any old temp file lying around
		//--------------------------------------

		try {
			$newfile = new File(self::FILE_CONFIG . '.cctmp');
			if ($newfile->Exists())
				$newfile->Delete();

			// Create temp file
			//-----------------
			$newfile->Create('root', 'root', '0755');

			// Write out the file
			//-------------------

			$newfile->AddLines(implode("\n", $this->configuration) . "\n");

			// Copy the new config over the old config
			//----------------------------------------

			$newfile->MoveTo(self::FILE_CONFIG);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}
}

// vim: syntax=php ts=4
?>
