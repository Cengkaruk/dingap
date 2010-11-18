<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2010 ClearFoundation
//
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

/**
 * CHAP/PAP configuration class.
 *
 * @package ClearOS
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2002-2010 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_library('base/File');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * CHAP/PAP configuration class.
 *
 * @package ClearOS
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2002-2010 ClearFoundation
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

	public function __construct()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		parent::__construct();

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

	public function AddUser($username, $password, $server = self::CONSTANT_ANY, $ip = self::CONSTANT_ANY)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

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

	public function DeleteUser($username)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

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

	public function GetUsers() 
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

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

	public function _Load()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

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

	public function _Save()
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

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

	public function _WriteLineEntry($username, $password, $server, $ip)
	{
		ClearOsLogger::Profile(__METHOD__, __LINE__);

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
		ClearOsLogger::Profile(__METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
