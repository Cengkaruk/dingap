<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2006 Point Clark Networks.
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
 * CHAP/PAP configuration class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * CHAP/PAP configuration class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Chap extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $secrets = array();

	const FILE_SECRETS = "/etc/ppp/chap-secrets";
	const FILE_PAP_SECRETS = "/etc/ppp/pap-secrets";
	const LINE_DONE = -3;
	const LINE_DELETE = -2;
	const LINE_ADD = -1;
	const LINE_DEFINED = 0;
	const CONSTANT_ANY = "*";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Chap constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Sets a username/password in the chap/pap secrets file.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @param string $server server
	 * @param string $ip ip
	 * @return void
	 * @throws EngineException
	 */

	function AddUser($username, $password, $server = self::CONSTANT_ANY, $ip = self::CONSTANT_ANY)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_Load();

		if (isset($this->secrets[$username]))
			$this->DeleteUser($username);

		$this->secrets[$username]["password"] = $password;
		$this->secrets[$username]["server"] = $server;
		$this->secrets[$username]["ip"] = $ip;
		$this->secrets[$username]["linestate"] = self::LINE_ADD;

		$this->_Save();
	}

	/**
	 * Deletes a username from the chap/pap secrets file. 
	 * 
	 * @param string $username username
	 * @return void
	 */

	function DeleteUser($username)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_Load();

		if (! isset($this->secrets[$username]))
			return;

		$this->secrets[$username]["linestate"] = self::LINE_DELETE;
		$this->_Save();
	}

	/**
	 * Returns user list.
	 *
	 * @return array information on users
	 * @throws EngineException
	 */

	function GetUsers() 
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_Load();

		return $this->secrets;
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration file.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	function _Load()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Reset our data structures
		//--------------------------

		$this->loaded = false;
		$this->secrets = array();

		// Create chap secrets
		// Load data structures
		//---------------------

		try {
			$file = new File(self::FILE_SECRETS);
			if (!$file->Exists())
				$file->Create("root", "root", "600");
			else
				$file->Chown("root", "root");

			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$linecount = 0;

		foreach ($lines as $line) {
			if (! preg_match("/^#/", $line)) {
				$linedata = preg_split("/[\s]+/", $line, 4);
				$username = preg_replace("/\"/", "", $linedata[0]);
				$this->secrets[$username]["linestate"] = self::LINE_DEFINED;
				$this->secrets[$username]["server"] = preg_replace("/\"/", "", $linedata[1]);
				$this->secrets[$username]["password"] = preg_replace("/\"/", "", $linedata[2]);
				$this->secrets[$username]["ip"] = preg_replace("/\"/", "", $linedata[3]);
			}

			$linecount++;
		}

		$this->loaded = true;
	}

	/**
	 * Saves configuration file.
	 *
	 * @private
	 * @returns void
	 */

	function _Save()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->loaded = false;

		$filedata = "";

		foreach ($this->secrets as $username => $value) {
			if ( isset($this->secrets[$username]["linestate"]) &&
				($this->secrets[$username]["linestate"] == self::LINE_DELETE) ) {
				continue;

			} else {
				$filedata .= $this->_WriteLineEntry(
					$username, 
					$this->secrets[$username]["password"],
					$this->secrets[$username]["server"],
					$this->secrets[$username]["ip"]
				);
			} 
		}

		try {
			$chapfile = new File(self::FILE_SECRETS . ".cctmp");
			if ($chapfile->Exists())
				$chapfile->Delete();

			$papfile = new File(self::FILE_PAP_SECRETS . ".cctmp");
			if ($papfile->Exists())
				$papfile->Delete();

			$chapfile->Create("root", "root", "0600");
			$papfile->Create("root", "root", "0600");

			$chapfile->AddLines($filedata);
			$papfile->AddLines($filedata);

			$chapfile->MoveTo(self::FILE_SECRETS);
			$papfile->MoveTo(self::FILE_PAP_SECRETS);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns the line entry with the proper formatting.
	 *
	 * @access private
	 * @param string $username username
	 * @param string $password password
	 * @param string $server server
	 * @param string $ip ip
	 * @return string line entry
	 */

	function _WriteLineEntry($username, $password, $server, $ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$username = "\"$username\"";
		$password = "\"$password\"";

		if ($server != "*")
			$server = "\"$server\"";

		if ($ip != "*")
			$server = "\"$server\"";

		$line = sprintf("%s %s %s %s\n", $username, $server, $password, $ip);

		return $line;
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
