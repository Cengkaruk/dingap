<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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
 * RoundCube class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('ConfigurationFile.class.php');
require_once('Syswatch.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * RoundCube class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2009, Point Clark Networks
 */

class RoundCube extends Software
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const CMD_MYSQL = "/usr/share/system-mysql/usr/bin/mysql";
	const FILE_RC_CONFIG = '/usr/share/roundcubemail/config/main.inc.php';
	const FILE_RC_CONFIG_DB = '/usr/share/roundcubemail/config/db.inc.php';
	const FILE_CONFIG_DB = "/etc/system/database";
	const FILE_BOOTSTRAP = "/usr/share/roundcubemail/SQL/mysql.initial.sql"; 
	const FILE_WEBCONFIG_ALIAS = "/usr/webconfig/conf/httpd.d/roundcube.conf"; 
	const FILE_HTTPD_ALIAS = "/etc/httpd/conf.d/roundcube.conf"; 

	protected $config = null;
	protected $is_loaded = false;
	protected $is_restart_req = false;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * RoundCube constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct("roundcube");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Sets product name.
	 *
	 * @param string $name mail domain
	 * @return void
	 * @throws EngineException
	 */

	function SetProductName($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$this->_SetParameter(self::FILE_RC_CONFIG, "\$rcmail_config['product_name']", "'$name';");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Set log logins.
	 *
	 * @param string $log log logins
	 * @return void
	 * @throws EngineException
	 */

	function SetLogLogins($log)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$this->_SetParameter(self::FILE_RC_CONFIG, "\$rcmail_config['log_logins']", ($log ? 'true;' : 'false;'));
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Set web engine.
	 *
	 * @param string $engine web engine
	 * @return void
	 * @throws EngineException
	 */

	function SetWebEngine($engine)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Try not to restart webconfig
		$restart = $restart_webconfig;

		$diron = self::FILE_WEBCONFIG_ALIAS;
		$diroff = self::FILE_HTTPD_ALIAS;

		if ($engine != "81") {
			$diron = self::FILE_HTTPD_ALIAS;
			$diroff = self::FILE_WEBCONFIG_ALIAS;
			if (! file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
				throw new EngineException(ROUNDCUBE_LANG_HTTPD_NOT_INSTALLED, COMMON_WARNING);

			require_once("Httpd.class.php");

		}

		// Try to disable
		$file = new File($diroff, true);
		if ($file->Exists()) {
			$file->MoveTo($diroff . '.disabled');
			if ($diroff == self::FILE_WEBCONFIG_ALIAS)
				$this->is_restart_req = true;
		}

		$file = new File($diron, true);
		if (!$file->Exists()) {
			if ($diron == self::FILE_WEBCONFIG_ALIAS)
				$this->is_restart_req = true;
			$file = new File($diron . '.disabled', true);
			if ($file->Exists()) {
				$file->MoveTo($diron);
			} else {
				$file = new File($diron, true);
				$file->Create("root", "root", "644"); 
				$file->AddLines("# Automatically generated by webconfig\n\n");
				$file->AddLines("Alias /" . $this->GetAlias() . " /usr/share/roundcubemail\n");
			}
		}
		if (! file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php")) {
			$httpd = new Httpd();
			$httpd->Reset();
		}
		if ($this->is_restart_req) {
			$syswatch = new Syswatch();
			$syswatch->ReconfigureSystem();	
		}
	}

	/**
	 * Set alias.
	 *
	 * @param string $alias web alias
	 * @return void
	 * @throws EngineException
	 */

	function SetAlias($alias)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			if ($this->GetWebEngine() == "81")
				$file = new File(self::FILE_WEBCONFIG_ALIAS, true);
			else
				$file = new File(self::FILE_HTTPD_ALIAS, true);

			if (!$file->Exists())
				$file->Create("root", "root", "644"); 

			try {
				$file->LookupLine("/^Alias \\/$alias \\/usr\\/share\\/roundcubemail$/");
				return;
			} catch (FileNoMatchException $e) {
				if ($file->GetFilename() == self::FILE_WEBCONFIG_ALIAS)
					$this->is_restart_req = true;
			}

            $match = $file->ReplaceLines("/^Alias \\/.*\s* \\/usr\\/share\\/roundcubemail$/", "Alias /$alias /usr/share/roundcubemail\n");

            if (!$match) {
				$file->AddLines("# Automatically generated by webconfig\n\n");
				$file->AddLines("Alias /$alias /usr/share/roundcubemail\n");
			}
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }
	}

	/**
	 * Get the product name.
	 *
	 * @return string
	 * @throws EngineException
	 */

	function GetProductName()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["\$rcmail_config['product_name']"];
	}

	/**
	 * Get the web engine.
	 *
	 * @return string
	 * @throws EngineException
	 */

	function GetWebEngine()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Look for conf file
		$file = new File(self::FILE_WEBCONFIG_ALIAS, true);

		if ($file->Exists())
			return "81";

		return "80/443";
	}

	/**
	 * Get the alias name.
	 *
	 * @return string
	 * @throws EngineException
	 */

	function GetAlias()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->GetWebEngine() == "81")
			$file = new File(self::FILE_WEBCONFIG_ALIAS, true);
		else
			$file = new File(self::FILE_HTTPD_ALIAS, true);

		if (!$file->Exists()) {
			$this->SetAlias('webmail');
			return 'webmail';
		}

		$lines = $file->GetContentsAsArray();

		foreach ($lines as $line) {
			if (eregi("^Alias /(.*) /usr/share/roundcubemail", $line, $match))
				return $match[1];
		}

		// Not supposed to happen
		return "webmail";
	}

	/**
	 * Get the log logins settings.
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function GetLogLogins()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$value = $this->config["\$rcmail_config['log_logins']"];

		if ($value == 'true')
			return true;

		return false;
	}

	/**
	 * Returns a list of web server engines.
	 *
	 * @returns array
	 */

	function GetWebEngineOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// TODO: implement on port 83
		//	"81" => ROUNDCUBE_LANG_WEBCONFIG . " - " . ROUNDCUBE_LANG_PORT . " 81 (HTTPS)",
		$options = Array(
			"80/443" => ROUNDCUBE_LANG_HTTP . " - " . ROUNDCUBE_LANG_PORT . " 80 (HTTP) or 443 (HTTPS)"
		);
		return $options;
	}

	/**
	 * Run the bootstrap script.
	 *
	 * @return void
     * @throws EngineException
	 */

	function RunBootstrap()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Initialize flag
		$initialized = false;
		$rootpasswd = '';

		// TODO: push this into system database class
		// If we roundcube database password is in /etc/system/database, bootstrap is already complete
		try {
			$file = new ConfigurationFile(self::FILE_CONFIG_DB, 'explode', '=', 2);
			$config = $file->Load();
			foreach ($config as $key => $value) {
				if ($key == "password")
					$rootpasswd = $value;
				else if ($key == "roundcube.password")
					$initialized = true;
			}
		} catch (FileNotFoundException $e) {
			throw new EngineException($e->GetMessage());
		}

		if ($initialized)
			return;
		
        try {
			// TODO: push this into system database class
            $shell = new ShellExec();

			// Create random password
			$passwd = "";
			$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
			for($i=0; $i < 16; $i++)
				$passwd .= $pattern{rand(0,35)};
		

			// Generate user
			$args = "-uroot -p$rootpasswd -e \"CREATE DATABASE roundcubemail; " .
					"GRANT ALL PRIVILEGES ON roundcubemail.* TO roundcube@localhost IDENTIFIED BY '" . $passwd . "'\"";

            $retval = $shell->Execute(self::CMD_MYSQL, $args, false);
			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}

			// Create tables
			$args = "-uroot -p$rootpasswd roundcubemail < " . self::FILE_BOOTSTRAP;
            $retval = $shell->Execute(self::CMD_MYSQL, $args, false);
			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}
			$file->AddLines("roundcube.password = " . $passwd . "\n");
			$this->_SetParameter(
				self::FILE_RC_CONFIG_DB,
				"\$rcmail_config['db_dsnw']",
				"'mysql://roundcube:$passwd@unix(/var/lib/system-mysql/mysql.sock)/roundcubemail';"
			);

			$passwd = "";
			for($i=0; $i < 24; $i++)
				$passwd .= $pattern{rand(0,35)};
			$this->_SetParameter(self::FILE_RC_CONFIG, "\$rcmail_config['des_key']", "'$passwd';");

			// Set webconfig as default engine
			$this->is_restart_req = true;
			$this->SetWebEngine('81');

        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration files.
	 *
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new File(self::FILE_RC_CONFIG, true);

		try {
			$lines = $configfile->GetContentsAsArray();
			foreach ($lines as $line) {
				if (eregi("^(\\$.*) = (.*)$", $line, $match)) {
					$value = ereg_replace("^'(.*)';$", "\\1", $match[2]);
					$value = ereg_replace("^(.*);$", "\\1", $value);
					$this->config[$match[1]] = $value;
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * Generic set routine.
	 *
	 * @private
	 * @param  string  $filename  config filename
	 * @param  string  $key  key name
	 * @param  string  $value  value for the key
	 * @return  void
	 * @throws EngineException
	 */

	function _SetParameter($filename, $key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$regex = str_replace("[", "\\[", $key);
			$regex = str_replace("]", "\\]", $regex);
			$regex = str_replace("\$", "\\\$", $regex);
            $file = new File($filename, true);
            $match = $file->ReplaceLines("/^$regex\s*=\s*/", "$key = $value\n");
            if (!$match)
                $file->AddLines("$key = $value\n");
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
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
