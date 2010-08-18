<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2006 Point Clark Networks.
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
 * Snort intrusion detection class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');
require_once('Folder.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Snort intrusion detection class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Snort extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $active_rules;
	protected $rule_details; // Defined in Snort.inc.php

	const FILE_CONFIG = "/etc/snort.conf";
	const PATH_RULES =  "/etc/snort"; // Should really check snort.conf
	const TYPE_SECURITY = "security";
	const TYPE_POLICY = "policy";
	const TYPE_IGNORE = "ignore";

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Snort constructor.
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__construct("snort");

		require_once(GlobalGetLanguageTemplate(__FILE__));
		require_once('Snort.inc.php');
    }

	/**
	 * Returns list of active snort rules files.
	 *
	 * @return array list of active rules files
	 * @throws EngineException
	 */

	function GetActiveRules()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->active_rules;
	}

	/**
	 * Returns information on installed rules files.
	 *
	 * @return array rules files information
	 * @throws EngineException
	 */

	function GetAvailableRules()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rulefiles = array();

		try {
			$folder = new Folder(self::PATH_RULES);
			$rulefiles = $folder->GetListing();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

		$rulelist = array();
		$ruleinfo = array();

		foreach ($rulefiles as $file) {
			if (!preg_match("/\.rules$/", $file))
				continue;

			try {
				$rulefile = new File(self::PATH_RULES . "/" . $file);
				$ruledata = $rulefile->GetContents();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$lines = explode("\n", $ruledata);
			$count = 0;
			foreach ($lines as $line) {
				if (preg_match("/^alert/", $line))
					$count++;

				if (preg_match("/^# .Id:/", $line)) {
					$lineitem = explode(" ", $line);
					$time = $lineitem[5];
					$dateitem = explode("/", $lineitem[4]);
					$year = $dateitem[0];
					$month = $dateitem[1];
					$day = $dateitem[2];
					$timestamp = strtotime($year . $month . $day);
				}
			}
			$ruleinfo["filename"] = $file;
			$ruleinfo["count"] = $count;
			$ruleinfo["timestamp"] = $timestamp;
			$rulelist[$file] = $ruleinfo;
		}

		return $rulelist;
	}

	/**
	 * Returns information about rule sets.
	 *
	 * @returns array information about rule sets
	 * @throws EngineException
	 */

	function GetRuleDetails()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->rule_details;
	}

	/**
	 * Returns list of rule types.
	 *
	 * @returns array list of rule types
	 * @throws EngineException
	 */

	function GetRuleTypes()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return array(
			self::TYPE_SECURITY,
			self::TYPE_POLICY,
			self::TYPE_IGNORE
		);
	}

	/**
	 * Sets the list of active snort rules files.
	 *
	 * @param array $rules rules files
	 * @returns void
	 * @throws EngineException, ValidationException
	 */

	function SetActiveRules($rules)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// TODO: validate
		$this->active_rules = $rules;

		$this->_SaveConfig();
	}

	/**
	 * Reads the /etc/snort.conf file.
	 *
     * @access private
	 * @return void
	 * @throws EngineException
	 */

	private function _LoadConfig()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$lines = array();

		try {
			$file = new File(self::FILE_CONFIG);
			$lines = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

		$matches = array();

		foreach ($lines as $line) {
			if (preg_match('/^\s*include\s+\$RULE_PATH\/(.*)/', $line, $matches))
				$this->active_rules[] = $matches[1];
		}
	}

	/**
	 * Saves the current configuration.
	 *
     * @access private
	 * @returns void
	 * @throws EngineException
	 */

	private function _SaveConfig()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->is_loaded = false;

		$file = new File(self::FILE_CONFIG);

		try {
			$lines = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Try to add the rules in the same spot in the config file.

		$newlines = array();
		$rules_added = false;
		$matches = array();
		
		foreach ($lines as $line) {
			if (preg_match('/^\s*include\s+\$RULE_PATH\/(.*)/', $line, $matches)) {
				if (!$rules_added) {
					$rules_added = true;
					foreach ($this->active_rules as $rule)
						$newlines[] = 'include $RULE_PATH/' . $rule;
				}

				continue;
			} else {
				$newlines[] = $line;
			}
		}


		if (!$rules_added) {
			foreach ($this->active_rules as $rule)
				$newlines[]	= 'include $RULE_PATH/' . $rule;
		}

		try {
			$file->DumpContentsFromArray($newlines);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
	}

    /**
     * @access private
     */

    public function __destruct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__destruct();
    }
}

// vim: syntax=php ts=4
?>
