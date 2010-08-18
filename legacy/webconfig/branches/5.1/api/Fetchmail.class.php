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
 * Fetchmail class.
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
require_once("Daemon.class.php");

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Fetchmail class.
 *
 * Provides interface to add, edit and delete POP/IMAP mail accounts.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Fetchmail extends Daemon
{
	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/fetchmail";
	const CONSTANT_WHITESPACE = "[[:space:],;:=]";
	const CONSTANT_NON_WHITESPACE = "[^[:space:],;:=\"]";
	const CONSTANT_POLL_INTERVAL = "set daemon ";
	const DEFAULT_POLL_INTERVAL = 300;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Fetchmail constructor.
	 *
	 * @return  void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('fetchmail');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds a configuration entry.
	 *
	 * @param string $poll server
	 * @param string $protocol protocol
	 * @param string $username username
	 * @param string $password password
	 * @param string $is a local user
	 * @param boolean $keep keep mail on server flag
	 * @param boolean $active state of account
	 * @return void
	 * @throws EngineException
	 */

	function AddConfigEntry($poll, $protocol, $username, $password, $is, $keep, $active = true)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);

			$entry = $this->_ConvertConfigEntry($poll, $protocol, $username, $password, $is, $keep, $active);
			$contents = $file->GetContentsAsArray();

			if (in_array(trim($entry), $contents))
				throw new EngineException (FETCHMAIL_LANG_ERRMSG_DUPLICATE_ENTRY, COMMON_ERROR);

			array_splice($contents, -1, 0, array($entry));

			$file->DumpContentsFromArray($contents);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes a configuration entry.
	 *
	 * @param int $start starting line
	 * @param int $length number of lines
	 * @return void
	 * @throws EngineException
	 */

	function DeleteConfigEntry($start, $length)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$contents = $file->GetContentsAsArray();
			array_splice($contents, $start, $length);
			$file->DumpContentsFromArray($contents);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns configuration file entries.
	 *
	 * @return array array of config entries
	 * @throws EngineException
	 */

	function GetConfigEntries()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$data = array();

		$W = self::CONSTANT_WHITESPACE;

		try {
			$file = new File(self::FILE_CONFIG);
			$contents = $file->GetContentsAsArray();
			# Merge lines (inelegantly), then send to be parsed
			# If comments divide a multi-line entry, buggage results

			$entry = "";
			$length = 0;
			$start = "";
			
			foreach($contents as $line_num => $line) {
				$length++;

				if (ereg("^$W*#", $line) || ereg("^$W*$", $line) ||
						ereg("set$W+daemon$W+[0-9]+", $line)) {
					$length--;
					continue;
				} else if (ereg("((poll$W)|(skip$W))", $line)) {
					if ($entry == "") {
						$entry = $line;
						$start = $line_num;
						$length = 0;
					} else {
						$fields = $this->_ParseConfigEntry($entry);

						if ($fields["poll"]) {
							$fields["start"] = $start;
							$fields["length"] = $length;
							$data[] = $fields;
						}

						$entry = $line;
						$start = $line_num;
						$length = 0;
					}
				} else {
					$entry .= " " . $line;
				}
			}

			if ($entry != "") {
				$fields = $this->_ParseConfigEntry($entry);
				$fields["start"] = $start;
				$fields["length"] = ++$length;

				if ($fields["poll"])
					$data[] = $fields;
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		return $data;
	}

	/**
	 * Returns the poll interval.
	 *
	 * @returns int poll interval in seconds
	 * @throws EngineException
	 */

	function GetPollInterval()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Defaults
		$reg = array();
		$kill_lines = array();
		$found = false;
		$touched = false;
		$interval = 0;

		try {
			$file = new File(self::FILE_CONFIG);
			$W = self::CONSTANT_WHITESPACE;
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		foreach($contents as $line_num => $line) {
			if (ereg("set$W+daemon$W+([0-9]+)", $line, $reg)) {
				if (!$found) {
					$interval = (int)$reg[1];

					if ($interval > 0) {
						$found = true;
					} else {
						$kill_lines[] = $line_num;
					}
				} else {
					# Duplicate
					$kill_lines[] = $line_num;
				}
			}
		}

		foreach($kill_lines as $line_num) {
			unset($contents[$line_num]);
			$touched = true;
		}

		if (!$found) {
			array_splice($contents, 0, 0, array(self::CONSTANT_POLL_INTERVAL . self::DEFAULT_POLL_INTERVAL));
			$touched = true;
			$interval = self::DEFAULT_POLL_INTERVAL;
		}

		if ($touched)
			$file->DumpContentsFromArray($contents);

		return $interval;
	}

	/**
	 * Replaces a configuration entry.
	 *
	 * @param int start starting line
	 * @param int length number of lines
	 * @param string poll server
	 * @param string $protocol protocol
	 * @param string $ssl use SSL flag
	 * @param string $username username
	 * @param string $password password
	 * @param string $is a local user
	 * @param boolean $keep keep mail on server flag
	 * @param boolean $active state of account
	 * @return void
	 * @throws EngineException
	 */

	function ReplaceConfigEntry($start, $length, $poll, $protocol, $ssl, $username, $password, $is, $keep, $active)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);

			$entry = $this->_ConvertConfigEntry($poll, $protocol, $ssl, $username, $password, $is, $keep, $active);
			$contents = $file->GetContentsAsArray();
			array_splice($contents, $start, $length, array($entry));
			$file->DumpContentsFromArray($contents);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the poll interval.
	 *
	 * @param string $interval poll interval
	 * @return void
	 * @throws EngineException
	 */

	function SetPollInterval($interval)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$W = self::CONSTANT_WHITESPACE;

		# Put in validation routine

		if ($interval <= 0) {
			throw new EngineException (FETCHMAIL_LANG_ERRMSG_BAD_INTERVAL, COMMON_ERROR);
			return;
		}

		try {
			$file = new File(self::FILE_CONFIG);
			$contents = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		# Kill any pre-existing
		$kill_lines = array();

		foreach($contents as $line_num => $line) {
			if (ereg("set$W+daemon$W+([0-9]+)", $line))
				$kill_lines[] = $line_num;
		}

		foreach($kill_lines as $line_num)
		unset($contents[$line_num]);

		# Add new one

		array_splice($contents, 0, 0, array(self::CONSTANT_POLL_INTERVAL . $interval));

		$file->DumpContentsFromArray($contents);
	}

	/**
	 * Toggles status of a configuration entry.
	 *
	 * @param int $start starting line
	 * @return void
	 * @throws EngineException
	 */

	function ToggleConfigEntry($start)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$contents = $file->GetContentsAsArray();
			if (ereg("^poll", $contents[$start])) {
				$contents[$start] = ereg_replace('^poll', 'skip', $contents[$start]);
			} else if (ereg("^skip", $contents[$start])) {
				$contents[$start] = ereg_replace('^skip', 'poll', $contents[$start]);
			} else {
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
			}
			$file->DumpContentsFromArray($contents);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for any string.
	 *
	 * @param string $string string
	 * @return boolean true if arg is valid config string
	 */

	function IsValidString($string)
	{
		$W = self::CONSTANT_WHITESPACE;
		$NW = self::CONSTANT_NON_WHITESPACE;

		# If it's in quotes, it's cool

		if (ereg("^$W*\"[^\"]*\"$W*$", $string))
			return true;

		# Otherwise, no quotes, whitespace, or ",;:="'s

		if (!ereg("$W|\"", $string))
			return true;

		return false;
	}

    ///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

	/**
	 * Converts fields into a formatted config entry.
	 *
	 * @param string $poll server
	 * @param string $protocol protocol
	 * @param string $ssl use SSL flag
	 * @param string $username username
	 * @param string $password password
	 * @param string $is a local user
	 * @param boolean $keep keep mail on server flag
	 * @param boolean $active state of account
	 * @return string a formatted config entry
	 * @throws ValidationException
	 */

	protected function _ConvertConfigEntry($poll, $protocol, $ssl, $username, $password, $is, $keep, $active)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$W = self::CONSTANT_WHITESPACE;

		if (!$this->IsValidString($poll))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_POLL . " - " . FETCHMAIL_LANG_ERRMSG_BAD_CHARS);

		if (!$this->IsValidString($protocol))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_PROTOCOL . " - " . FETCHMAIL_LANG_ERRMSG_BAD_CHARS);

		if (!$this->IsValidString($username))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_USERNAME . " - " . FETCHMAIL_LANG_ERRMSG_BAD_CHARS);

		if (!$this->IsValidString($password))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_PASSWORD . " - " . FETCHMAIL_LANG_ERRMSG_BAD_CHARS);

		if (!$this->IsValidString($is))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_LOCALUSER . " - " . FETCHMAIL_LANG_ERRMSG_BAD_CHARS);

		if (ereg("^$W*$", $poll))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_POLL . " - " . FETCHMAIL_LANG_ERRMSG_EMPTY);

		if (ereg("^$W*$", $username))
			throw new ValidationException(FETCHMAIL_LANG_FIELD_USERNAME . " - " . FETCHMAIL_LANG_ERRMSG_EMPTY);

		$line = ($active) ? "poll $poll " : "skip $poll ";

		if ($protocol)
			$line .= "protocol $protocol ";

		if ($username)
			$line .= "username \"$username\" ";

		if ($ssl)
			$line .= "ssl ";

		if ($password)
			$line .= "password \"$password\" ";

		if ($is)
			$line .= "is \"$is\" here ";

		if ($keep)
			$line .= "keep ";

		return $line;
	}

	/**
	 * Breaks a fetchmail config entry into fields.
	 *
	 * @return array list of config fields
	 * @throws EngineException
	 */

	protected function _ParseConfigEntry($line)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$fields = array();

		$reg = array();

		$W = self::CONSTANT_WHITESPACE;

		$NW = self::CONSTANT_NON_WHITESPACE;

		if (ereg("(poll|skip)$W((\"[^\"]+\")|($NW+))", $line, $reg)) {
			$fields["active"] = ($reg[1] == "poll");
			$fields["poll"] = trim($reg[2], "\"");
		}

		if (ereg("protocol$W((\"[^\"]+\")|($NW+))", $line, $reg))
			$fields["protocol"] = trim($reg[1], "\"");

		if (ereg("no dns", $line, $reg))
			$fields["nodns"] = true;

		if (ereg("$W(ssl password)$W", $line, $reg))
			$fields["ssl"] = true;

		if (ereg("localdomains$W((\"[^\"]+\")|($NW+))", $line, $reg))
			$fields["localdomains"] = trim($reg[1], "\"");

		if (ereg("user(name)?$W((\"[^\"]+\")|($NW+))", $line, $reg))
			$fields["username"] = trim($reg[2], "\"");

		if (ereg("pass(word)?$W((\"[^\"]+\")|($NW+))", $line, $reg))
			$fields["password"] = trim($reg[2], "\"");

		if (ereg("is$W((\"[^\"]+\")|($NW+))$W" . "here", $line, $reg))
			$fields["is"] = trim($reg[1], "\"");

		$fields["keep"] = (ereg("[^n][^o]$W+keep", $line, $reg) == true);

		return $fields;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}

}

// vim: syntax=php ts=4
?>
