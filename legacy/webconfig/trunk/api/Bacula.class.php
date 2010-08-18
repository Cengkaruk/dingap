<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * Backup and restore system system using Bacula.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

/*****************************************************************************/
/* D E P E N D E N C I E S                                                   */
/*****************************************************************************/

require_once("Locale.class.php");
require_once("Daemon.class.php");
require_once("Network.class.php");
require_once("IfaceManager.class.php");
require_once("File.class.php");
require_once("Folder.class.php");
require_once("Software.class.php");
require_once("ShellExec.class.php");
require_once("Hostname.class.php");
require_once("Bnetd.class.php");
require_once("StorageDevice.class.php");
require_once("AutoFs.class.php");
require_once("Mailer.class.php");

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Backup and restore system system using Bacula.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Bacula extends Daemon
{
	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	//////////////////////////////////////////////////////////////////////////////

	const BACULA_ETC = "/etc/bacula/";
	const BACULA_USR = "/usr/bacula/";
	const BACULA_VAR = "/var/bacula/";
	const RESTORE_DEFAULT = "/tmp/bacula-restores";
	const CONSOLE_FILE_CONFIG = "/etc/bacula/bconsole.conf";
	const DIR_FILE_CONFIG = "/etc/bacula/bacula-dir.conf";
	const SD_FILE_CONFIG = "/etc/bacula/bacula-sd.conf";
	const FD_FILE_CONFIG = "/etc/bacula/bacula-fd.conf";
	const CMD_DROP_DATABASE = "/usr/bacula/drop_mysql_database";
	const CMD_CREATE_DATABASE = "/usr/bacula/create_mysql_database";
	const SCRIPT_UPGRADE_DATABASE_1 = "/usr/bacula/update_mysql_tables";
	const SCRIPT_UPGRADE_DATABASE_2 = "/usr/bacula/update_mysql_tables_2";
	const SOCKET_MYSQL = "/opt/bacula/var/lib/mysql/mysql.sock";
	const PATH_MYSQL = "/opt/bacula";
	const CMD_MYSQL = "/opt/bacula/usr/bin/mysql";
	const CMD_MYSQLADMIN = "/opt/bacula/usr/bin/mysqladmin";
	const CMD_BCONSOLE = "/usr/sbin/bconsole";
	const CMD_DIR = "/usr/sbin/bacula-dir";
	const CMD_FD = "/usr/sbin/bacula-fd";
	const CMD_SD = "/usr/sbin/bacula-sd";
	const CMD_SEND_BSR = "/usr/bacula/pcnl_send_bsr";
	const CMD_RESTORE_CATALOG = "/usr/bacula/pcnl_catalog_restore";
	const CMD_CHANGE_DB_PASSWORD = "/usr/bacula/pcnl_db_password";
	const CMD_RESTORE_BY_BSR = "/usr/bacula/pcnl_restore_by_bsr";
	const CMD_LABEL_MEDIA = "/usr/bacula/pcnl_label_media";
	const CMD_GRANT_PRIV = "/usr/bacula/pcnl_grant_privileges";
	const CMD_MOUNT = "/bin/mount";
	const CMD_UMOUNT = "/bin/umount";
	const CMD_EJECT = "/usr/bin/eject";
	const CMD_DVD_INFO = "/usr/bin/dvd+rw-mediainfo";
	const CMD_DVD_HANDLER = "/etc/bacula/dvd-handler";
	const CMD_SMBMOUNT= "/usr/bin/smbmount";
	const CMD_SMBUMOUNT= "/usr/bin/smbumount";
	const CMD_LN = "/bin/ln";
	const JOB_RESTORE_CATALOG = "Restore";
	const FLAG_DELETE = "FLAG_DELETE";
	const FLAG_EMAIL_ON_EDIT = "# DO NOT REMOVE - Email on edit";
	const FLAG_FILESET_DB = "# DO NOT REMOVE - This is a database fileset";
	const FLAG_NO_EDIT = "# DO NOT REMOVE - Webconfig NO_EDIT";
	const FLAG_NO_DELETE = "# DO NOT REMOVE - Webconfig NO_DELETE";
	const RESTRICT_EDIT = 1;
	const RESTRICT_DELETE = 2;
	const RESTRICT_ALL = 3;
	const SCRIPT_BACKUP_PREFIX = "backup_script-";
	const SCRIPT_RESTORE_PREFIX = "restore_script-";
	const FIELDS_REQ_NO_QUOTES = "MaximumVolumeSize\$|FileRetention\$|JobRetention\$|VolumeRetention\$|DIRPort\$|FDPort\$|SDPort\$|Run\$";
	const MEDIA_IOMEGA = "Iomega";
	const MEDIA_DVD = "DVD";
	const MEDIA_FILE = "File";
	const MEDIA_DLT = "DLT";
	const MEDIA_DDS = "DDS";
	const MEDIA_USB = "USB";
	const MEDIA_SMB = "SAMBA";
	const DEFAULT_MOUNT = "/var/bacula/mnt";
	const DEFAULT_SERVER_FILESET = "server.fileset";
	const DIR_TEMP = "/var/bacula/tmp";
	const DVD_MAX_CAPACITY = 4294967000; # 4 GB
	const BASIC_POOL = "Basic";
	const RESTORE_JOB = "Restore";
	const SHARED_DOCS = "SharedDocs";
	const TYPE_OCTET = "application/octet-stream";
	const ENCODING_7BIT = "7bit";

	protected $block = Array();
	protected $loaded = false;
	protected $flatfile = "";
	protected $sql = Array();
	protected $connect2daemon = Array();
	protected $require_restart = Array();
	var $DEFAULT_CLIENT_WORKING_DIR = Array(
		"Windows-98" => "\"C:\\\\Program Files\\\\Bacula\\\\working\"",
		"Windows-2000" => "\"C:\\\\Program Files\\\\Bacula\\\\working\"",
		"Windows-XP" => "\"C:\\\\Program Files\\\\Bacula\\\\working\""
	);
	var $DEFAULT_CLIENT_WORKING_PID = Array(
		"Windows-98" => "\"C:\\\\Program Files\\\\Bacula\\\\working\"",
		"Windows-2000" => "\"C:\\\\Program Files\\\\Bacula\\\\working\"",
		"Windows-XP" => "\"C:\\\\Program Files\\\\Bacula\\\\working\""
	);

	/*************************************************************************/
	/* M E T H O D S                                                         */
	/*************************************************************************/

	/**
	 * The Bacula constructor.
	 *
	 * @return  void
	 */

	function __construct()
	{
	    if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

	    parent::__construct('bacula-mysqld');

	    require_once(GlobalGetLanguageTemplate(__FILE__));

		if(!extension_loaded("bacula")) if(!dl("bacula.so")) exit;
		if(!extension_loaded("mysql")) if(!dl("mysql.so")) exit;
		if(!extension_loaded("imap")) if(!dl("imap.so")) exit;

		$this->sql["host"] = "localhost";
		$this->sql["user"] = "bacula";
		$this->sql["pass"] = $this->GetDirectorDatabasePassword();
		$this->sql["port"] = "3307"; # This is Bacula's own 'private' MySQL server operating on non-default port
		$this->sql["name"] = "bacula";
	}

	/******************************
	*       G E N E R A L         *
	******************************/


	/**
	 * GetDirectorAddress.
	 *
	 * @returns  string  Director's address
	 * @throws  EngineException
	 */

	function GetDirectorAddress()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::CONSOLE_FILE_CONFIG, "Director", "", "Address");
	}

	/**
	 * GetDirectorName.
	 *
	 * @returns  string  Director's name
	 * @throws  EngineException
	 */

	function GetDirectorName()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Director", "", "Name");
	}

	/**
	 * GetDirectorPort.
	 *
	 * @returns  string  Director's port
	 * @throws  EngineException
	 */

	function GetDirectorPort()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Director", "", "DIRport");
	}

	/**
	 * GetDirectorPassword.
	 *
	 * @returns  string  Director's password
	 * @throws  EngineException
	 */

	function GetDirectorPassword()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Director", "", "Password");
	}

	/**
	 * GetDirectorOperatorEmail.
	 *
	 * @returns  string  operator email address.
	 * @throws  EngineException
	 */

	function GetDirectorOperatorEmail()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Doh!  Bacula breaks key/value pair
		$value = $this->GetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "operator");
		$value = preg_replace("/\s.*/", "", $value);
		$value = $this->RemoveComments($value);
		return $value;
	}

	/**
	 * GetDirectorAdminEmail.
	 *
	 * @returns  string  admin email address.
	 * @throws  EngineException
	 */

	function GetDirectorAdminEmail()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Doh!  Bacula breaks key/value pair
		$value = $this->GetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "mail");
		$value = preg_replace("/\s.*/", "", $value);
		$value = $this->RemoveComments($value);
		return $value;
	}

	/**
	 * GetDirectorMailserver.
	 *
	 * @returns  string  Director's mail server address
	 * @throws  EngineException
	 */

	function GetDirectorMailserver()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Doh!  Bacula breaks key/value pair
		$match = array();
		$mail_address = "";
		$value = $this->GetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "mailcommand");
		if (eregi("[[:space:]]+-h[[:space:]]*([^[:space:]]*)", $value, $match))
			$mail_address = trim($match[1]);
		return $mail_address;
	}

	/**
	 * GetDirectorDatabasePassword.
	 *
	 * @returns  string  Director's database password
	 * @throws  EngineException
	 */

	function GetDirectorDatabasePassword()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Catalog", "", "Password");
	}

	/**
	 * Check for webconfig 'email on edit' flag.
	 *
	 * @return  boolean  true if email flag is set
	 * @throws  EngineException
	 */

	function GetDirectorEmailOnEdit()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		foreach($this->block[self::DIR_FILE_CONFIG] as $line) {
			if (eregi(self::FLAG_EMAIL_ON_EDIT, $line))
				return true;
		}
		return false;
	}

	/**
	 * GetStorageName.
	 *
	 * @returns  string  Storage device's name
	 * @throws  EngineException
	 */

	function GetStorageName()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::SD_FILE_CONFIG, "Storage", "", "Name");
	}

	/**
	 * GetStoragePort.
	 *
	 * @returns  string  Storage device's port
	 * @throws  EngineException
	 */

	function GetStoragePort()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::SD_FILE_CONFIG, "Storage", "", "SDPort");
	}

	/**
	 * GetFileName.
	 *
	 * @returns  string  File daemon's name
	 * @throws  EngineException
	 */

	function GetFileName()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::FD_FILE_CONFIG, "FileDaemon", "", "Name");
	}

	/**
	 * GetFilePort.
	 *
	 * @returns  string  File daemon's port
	 * @throws  EngineException
	 */

	function GetFilePort()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::FD_FILE_CONFIG, "FileDaemon", "", "FDport");
	}


	/**
	 * Sets the Director name.
	 *
	 * @param  string  $value  director name
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectorName($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME);

		$oldname = $this->GetDirectorName();
		if ($oldname != $value) {
			$this->SetKey(self::DIR_FILE_CONFIG, "Director", "", "Name", $value);
			$this->SetKey(self::CONSOLE_FILE_CONFIG, "Director", "", "Name", $value);
			$this->SetKey(self::FD_FILE_CONFIG, "Director", $oldname, "Name", $value);
			$this->SetKey(self::FD_FILE_CONFIG, "Messages", "Standard", "director", $value);
			$this->SetKey(self::SD_FILE_CONFIG, "Director", $oldname, "Name", $value);
			$this->SetKey(self::SD_FILE_CONFIG, "Messages", "Standard", "director", $value);
		}
	}

	/**
	 * Sets the Director address.
	 *
	 * @param  string  $value  director address
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetDirectorAddress($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		$network = new Network();
		if (! $network->IsValidIp($value)) {
			$errors = $this->GetValidationErrors();
			if (! $network->IsValidHostname($value)) {
				$errors = $this->GetValidationErrors();
				throw new ValidationException($errors[0]);
			}
		}
		$name = $this->GetDirectorName();
		$this->SetKey(self::CONSOLE_FILE_CONFIG, "Director", $name, "Address", $value);
	}

	/**
	 * Sets the Director port.
	 *
	 * @param  int  $value  director port
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectorPort($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------
		$network = new Network();
		if (! $network->IsValidPort($value)) {
	        self::Log(COMMON_ERROR, "Invalid director's port - " . $value, __METHOD__, __LINE__);
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME);
		}

		$oldport = $this->GetDirectorPort();
		$name = $this->GetDirectorName();
		if ($oldport != $value) {
			$this->SetKey(self::DIR_FILE_CONFIG, "Director", "", "DIRport", $value);
			$this->SetKey(self::CONSOLE_FILE_CONFIG, "Director", $name, "DIRport", $value);
		}
	}

	/**
	 * Sets the Director password.
	 *
	 * @param  string  $value  director password
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectorPassword($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidPassword($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_PASSWORD);

		$oldpassword = $this->GetDirectorPassword();
		if ($oldpassword != $value) {
			$this->SetKey(self::DIR_FILE_CONFIG, "Director", "", "Password", $value);
			$this->SetKey(self::CONSOLE_FILE_CONFIG, "Director", "", "Password", $value);
		}
	}

	/**
	 * Sets the Director administration e-mail.
	 *
	 * @param  string  $value  director admin e-mail
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectorAdminEmail($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidEmail($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_EMAIL);

		$this->SetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "mail", $value);
	}

	/**
	 * Sets the Director operator e-mail.
	 *
	 * @param  string  $value  director operator e-mail
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectorOperatorEmail($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidEmail($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_EMAIL);

		$this->SetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "operator", $value);
	}

	/**
	 * Sets the Director mailserver address for outgoing alert/notification emails.
	 *
	 * @param  string  $value  a valid mailserver address
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectorMailserver($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidMailserver($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_MAIL_SERVER);

		$current_mailserver = $this->GetDirectorMailserver();
		if ($current_mailserver != $value) {
			# Operator
			$raw_data = $this->GetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "operatorcommand");
			$set_value = preg_replace("/" . $current_mailserver . "/", $value, $raw_data);
			$this->SetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "operatorcommand", $set_value);
			# Admin
			$raw_data = $this->GetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "mailcommand");
			$set_value = preg_replace("/" . $current_mailserver . "/", $value, $raw_data);
			$this->SetKey(self::DIR_FILE_CONFIG, "Messages", "Standard", "mailcommand", $set_value);
			# Daemon
			$raw_data = $this->GetKey(self::DIR_FILE_CONFIG, "Messages", "Daemon", "mailcommand");
			$set_value = preg_replace("/" . $current_mailserver . "/", $value, $raw_data);
			$this->SetKey(self::DIR_FILE_CONFIG, "Messages", "Daemon", "mailcommand", $set_value);
		}
	}

	/**
	 * Sets the Director database password.
	 *
	 * @param  string  $value  director database password
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetDirectorDatabasePassword($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($value && !$this->IsValidPassword($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_PASSWORD);

		$shell = new ShellExec();
		try {
			$param = " \"" . $this->sql["host"] . "\" \"" . $this->sql["port"] . "\" \"" . $value . "\" ";
			$retval = $shell->Execute(self::CMD_CHANGE_DB_PASSWORD, $param, true);
			$output = $shell->GetOutput();
	        if ($retval != 0)
				throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException ($e->getMessage(), COMMON_ERROR);
		}
		$this->SetKey(self::DIR_FILE_CONFIG, "Catalog", "MyCatalog", "Password", $value);
		$db = $this->GetDatabaseProperties("Catalog");
		$db["PASS"] = $value;
		$db["bindir"] = self::PATH_MYSQL . "/usr/bin";
		$this->UpdateDbScripts("Catalog", $db);
	}

	/**
	 * Sets the 'email on edit' flag.
	 *
	 * @param  string  $value  true if enabled
	 * @return  void
	 * @throws  EngineException
	 */

	function SetDirectorEmailOnEdit($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$new_block = Array();
		$index = 0;
		$end_of_comments = false;
		foreach($this->block[self::DIR_FILE_CONFIG] as $line) {
			if (eregi(self::FLAG_EMAIL_ON_EDIT, $line))
				continue;
			if (!eregi("^#", $line) && !$end_of_comments && $value) {
				# Found first non-comment
				$new_block[$index] = self::FLAG_EMAIL_ON_EDIT;
				$end_of_comments = true;
				$index++;
			}
			$new_block[$index] = $line;
			$index++;
		}

		$this->block[self::DIR_FILE_CONFIG] = $new_block;
	}

	/**
	 * Sets the File Daemon name.
	 *
	 * @param  string  $value  file daemon name
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFileName($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");
		$this->SetKey(self::FD_FILE_CONFIG, "FileDaemon", "", "Name", $value);
		# TODO - For cosmetic reasons, should change name for client definition in DIR_FILE_CONFIG and also
		# all references in jobs with same old name.  We don't do this currently because it requires the
		# network class "IsThisMe()" method.
	}

	/**
	 * Sets the File Daemon port.
	 *
	 * @param  int  $value  storage daemon  port
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetFilePort($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		$network = new Network();
		if (! $network->IsValidPort($value)) {
			$errors = $this->GetValidationErrors();
	        throw new ValidationException($errors[0]);
		}

		$this->SetKey(self::FD_FILE_CONFIG, "FileDaemon", "", "FDport", $value);
	}

	/**
	 * Sets the Storage Daemon name.
	 *
	 * @param  string  $name  storage daemon name
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetStorageName($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");

		$this->SetKey(self::SD_FILE_CONFIG, "Storage", "", "Name", $value);
	}

	/**
	 * Sets the Storage Daemon port.
	 *
	 * @param  int  $value  storage daemon  port
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetStoragePort($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		$network = new Network();
		if (! $network->IsValidPort($value)) {
			$errors = $this->GetValidationErrors();
	        throw new ValidationException($errors[0]);
		}

		$this->SetKey(self::SD_FILE_CONFIG, "Storage", "", "SDport", $value);
	}

	/******************************
	*          J O B              *
	******************************/

	/**
	 * Get a list of jobs.
	 *
	 * @returns  array  list of jobs
	 * @throws  EngineException
	 */

	function GetJobList()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$job_array = array();

		# Regular expressions
		# -------------------
		$regex_job = "^[[:space:]]*Job*[[:space:]]\\{.*$";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";

		$match = array();
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			if (!is_array($this->block[self::DIR_FILE_CONFIG][$index]))
				continue;
			if (eregi($regex_job, $this->block[self::DIR_FILE_CONFIG][$index][0])) {
				$subindex = 0;
				while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
					$subindex++;
					continue;
				}
				$value = trim($match[2]);
				# Check for comments
				$value = preg_replace("/#.*/", "", $value);
				# Check for quotations
				$value = preg_replace("/\"/", "", $value);
				$job_array[$index] = trim($value);
			}
		}
		return $job_array;
	}

	/**
	 * Get all job attributes (ie. key/value pairs).
	 *
	 * @param  string  $job  a job name
	 * @returns  array  a list of attributes
	 * @throws  EngineException
	 */

	function GetJobAttributes($job)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$job_att = Array();
		$jobdef_att = Array();
		$final = Array();

		# Attributes from job block
		$job_att = $this->RemoveComments($this->GetBlock(self::DIR_FILE_CONFIG, "Job", $job));

		# If no name match, return
		if (sizeof($job_att) == 0)
			return $job_att;

		# Remove block start/stop
		unset($job_att[0]);
		unset($job_att[sizeof($job_att)]);

		$jobdef = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "JobDefs");
		if ($jobdef)
			$jobdef_att = $this->RemoveComments($this->GetBlock(self::DIR_FILE_CONFIG, "JobDefs", $jobdef));

		# Remove block start/stop
		unset($jobdef_att[0]);
		unset($jobdef_att[sizeof($jobdef_att)]);

		# Override JobDef setting
		foreach($job_att as $job_line) {
			$pair = split("=", $job_line);
			$job_key = trim($pair[0]);
			foreach($jobdef_att as $index => $jobdef_line) {
				$pair = split("=", $jobdef_line);
				$jobdef_key = trim($pair[0]);
				if ($job_key == $jobdef_key) {
					unset($jobdef_att[$index]);
					break;
				}
			}
		}

		$final = array_merge($job_att, $jobdef_att);

		return $final;
	}

	/**
	 * Get all job "RunAfter" commands.
	 *
	 * @param  string  $name  the job name
	 * @returns  array  list of commands to run after a job is complete
	 * @throws  EngineException
	 */

	function GetJobRunAfter($job)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$run_after = Array();
		$index = 0;
		$regex_run_after = "^[[:space:]]*(RunAfterJob)[[:space:]]*=[[:space:]]*(.*$)";

		$lines = $this->GetBlock(self::DIR_FILE_CONFIG, "Job", $job);
		if (sizeof($lines) == 0)
			return $run_after;
		foreach ($lines as $line) {
			if (eregi($regex_run_after, preg_replace("/ /", "", $line))) {
				$run_after[$index] = $line;
				$index++;
			}
		}

		return $run_after;
	}

	/**
	 * Gets the job type.
	 *
	 * @param  string  $name  the job name
	 * @returns  string  the job type
	 * @throws  EngineException
	 */

	function GetJobType($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "Type");
	}

	/**
	 * Gets the job schedule.
	 *
	 * @param  string  $name  the job name
	 * @returns  string  the job schedule
	 * @throws  EngineException
	 */

	function GetJobSchedule($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "Schedule");
	}

	/**
	 * Sets the job name.
	 *
	 * @param  string  $name  the current job name
	 * @param  string  $value  the new job name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetJobName($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# No need to continue
		if ($name == $value)
			return;

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");

		# Check for non-uniques
		$check_existing = $this->GetJobAttributes($name);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($value)", COMMON_ERROR);

		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Name", $value);
		$this->SetAllKeys("Job", $name, $value);
	}

	/**
	 * Sets the job type.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job type 
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobType($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Type", $value);
	}

	/**
	 * Sets the job level.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job level
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobLevel($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$value)
			$value = self::FLAG_DELETE;
		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Level", $value);
	}

	/**
	 * Sets the job client.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job client
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobClient($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Client", $value);
	}

	/**
	 * Sets the job file set.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job file set
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobFileset($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "FileSet", $value);
		# Add run before/after jobs for database
		if ($this->IsFilesetDatabase($value)) {
			$db = $this->GetDatabaseProperties($value);
			if ($this->GetJobType($name) == "Backup") {
				$run_before = self::BACULA_USR . self::SCRIPT_BACKUP_PREFIX . $value . ".sh";
				$run_after = "rm -f " . self::BACULA_VAR . $value . ".sql";
				$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "ClientRunBeforeJob", $run_before);
				$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "ClientRunAfterJob", $run_after);
			} else if ($this->GetJobType($name) == "Restore") {
				$run_after = self::BACULA_USR . self::SCRIPT_RESTORE_PREFIX . $value . ".sh";
				$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "ClientRunAfterJob", $run_after);
			}
		}
	}

	/**
	 * Sets the job schedule.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job schedule
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobSchedule($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$value)
			$value = self::FLAG_DELETE;
		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Schedule", $value);
	}

	/**
	 * Sets the job storage device.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job storage device
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobStorageDevice($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Storage", $value);
	}

	/**
	 * Sets the job pool.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job pool
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobPool($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = Array();
		$device_name = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "Storage");
		$device_label_media = $this->GetKey(self::SD_FILE_CONFIG, "Device", $device_name, "LabelMedia");
		if ($device_label_media == "yes") {
			$label_format = $this->GetKey(self::DIR_FILE_CONFIG, "Pool", $value, "LabelFormat");
			if (!$label_format)
				throw new EngineException (BACULA_LANG_ERRMSG_REQUIRES_AUTO_LABEL, COMMON_ERROR);
		}
		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Pool", $value);
	}

	/**
	 * Sets the job priority.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  the job priority
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobPriority($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($value == 0 || !$value)
			$value = self::FLAG_DELETE;
		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "Priority", $value);
	}

	/**
	 * Sets the job WritePartAfterJob directive.
	 *
	 * @param  string  $name  the job name
	 * @param  string  $value  set to 'yes' for DVD
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobWritePartAfterJob($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "WritePartAfterJob", $value);
	}

	/**
	 * Sets the job write BSR.
	 *
	 * @param  string  $name  the job name
	 * @param  boolean  $value  flag to determine whether to create a BSR or not
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobWriteBsr($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$value || $value == "no")
			$value = self::FLAG_DELETE;
		else
			$value = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "WriteBootstrap");
		if (!$value)
			$value = self::BACULA_VAR . $this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "Client") . ".bsr";
		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $name, "WriteBootstrap", $value);
	}

	/**
	 * Sets the job send BSR.
	 *
	 * @param  string  $name  the job name
	 * @param  boolean  $value  flag to determine whether to send a BSR or not
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetJobSendBsr($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$cur_list = Array();
		$regex_run_after = "^[[:space:]]*(RunAfterJob)[[:space:]]*=[[:space:]]*(.*$)";
		if ($value == "yes" && !$this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "WriteBootstrap"))
			# Don't set/send erro message...Set $value to false and continue;
			$value = "no";

		$cur_list = $this->GetJobRunAfter($name);
		$newblock = $this->GetBlock(self::DIR_FILE_CONFIG, "Job", $name);
		$index = 0;
		foreach ($newblock as $line) {
			# Remove old RunAfters
			if (eregi(self::CMD_SEND_BSR, $line))
				$newblock[$index] = self::FLAG_DELETE;
			$index++;
		}
		foreach ($cur_list as $run) {
			if (eregi(self::CMD_SEND_BSR, $run) && (!$value || $value == "no"))
				$newblock[sizeof($newblock)] = self::FLAG_DELETE;
		}

		# Add tag, as required
		if ($value == "yes") {
			$newblock[sizeof($newblock) - 1] = "  RunAfterJob = \" " . self::CMD_SEND_BSR . " " .
				$this->GetKey(self::DIR_FILE_CONFIG, "Job", $name, "WriteBootstrap") . " " .
				"\'" . $name . "\'\"";
			$newblock[] = "}";
		}

		# Set new block
		$this->SetBlock(self::DIR_FILE_CONFIG, "Job", $name, $newblock);
	}

	/**
	 * Add a job.
	 *
	 * @param  string  $name  a job name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function AddJob($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		# Check for non-uniques
		$check_existing = $this->GetJobAttributes($name);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);

		$default_client =  $this->GetClientList();
		$default_fileset =  $this->GetFilesetList();
		$default_schedule =  $this->GetScheduleList();
		$default_storage =  $this->GetSdList();
		$default_pool =  $this->GetPoolList();
		$newjob = Array(
			"Job {",
			"  Name = \"" . trim($name) . "\"",
			"  Type = \"Backup\"",
			"  Level = \"Full\"",
			"  Client = \"" . current($default_client) . "\"",
			"  FileSet = \"" . current($default_fileset) . "\"",
			#"  Schedule = \"" . current($default_schedule) . "\"",
			"  Storage = \"" . current($default_storage) . "\"",
			"  Messages = \"Standard\"",
			"  Pool = \"" . current($default_pool) . "\"",
			"  Priority = \"10\"",
			"}"
		);
		$this->InsertBlock(self::DIR_FILE_CONFIG, $newjob);
	}

	/**
	 * Delete a job.
	 *
	 * @param  int  $index  the job index
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteJob($index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->DeleteBlockByIndex(self::DIR_FILE_CONFIG, $index);
	}

	/**
	 * Delete a job dependent on a resource.
	 *
	 * @param  int  $index  the index of the resource to delete
	 * @param  string  $resource  the resource type
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteDependentJob($index, $resource)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Delete any job dependent on this pool
		$block = Array();
		$job_list = $this->GetJobList();
		$block = $this->block[self::DIR_FILE_CONFIG][$index];
		$name = "";

		# Fetch resource name
		$match = array();
		
		foreach ($block as $block_line) {
			if (eregi("^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)", trim($block_line), $match)) {
				$name = $this->RemoveComments($match[2]);
			} else {
				continue;
			}
		}
		if (!$name)
			throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_DETERMINE_RESOURCE, COMMON_ERROR);

		# Delete jobs that include this resource
		foreach ($job_list as $job) {
			$attributes = $this->GetJobAttributes($job);
			foreach ($attributes as $line) {
				$pair = split("=", $line);
				if (eregi("^[[:space:]]*(JobDefs)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match))
					$jobdefs = $this->RemoveComments($match[2]);
				if (eregi("^[[:space:]]*($resource)[[:space:]]*=[[:space:]]*(.*$)", trim($line), $match)) {
					$resource_name = $this->RemoveComments($match[2]);
					if ($name == $resource_name) {
						$this->DeleteBlock(self::DIR_FILE_CONFIG, "Job", $job);
						$this->DeleteJobDefsEntry($jobdefs, $resource, $name);
					}
				}
			}
		}
	}

	/******************************
	*           P O O L           *
	******************************/

	/**
	 * Get a list of pools.
	 *
	 * @returns  array  list of pools
	 * @throws  EngineException
	 */

	function GetPoolList()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pool_array = Array();

		# Regular expressions
		# -------------------
		$regex_pool = "^[[:space:]]*Pool*[[:space:]]\\{.*$";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$match = array();
		
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			if (!is_array($this->block[self::DIR_FILE_CONFIG][$index]))
				continue;
			if (eregi($regex_pool, $this->block[self::DIR_FILE_CONFIG][$index][0])) {
				$subindex = 0;
				while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
					$subindex++;
					continue;
				}
				$value = trim($match[2]);
				# Check for comments
				$value = preg_replace("/#.*/", "", $value);
				# Check for quotations
				$value = preg_replace("/\"/", "", $value);
				$pool_array[$index] = trim($value);
			}
		}
		return $pool_array;
	}

	/**
	 * Get all pool attributes (ie. key/value pairs).
	 *
	 * @param  string  $pool  the pool name
	 * @returns  array  list of pool attributes
	 * @throws  EngineException
	 */

	function GetPoolAttributes($pool)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Attributes from director file
		$pool_attributes = $this->RemoveComments($this->GetBlock(self::DIR_FILE_CONFIG, "Pool", $pool));

		# Remove block start/stop
		unset($pool_attributes[0]);
		unset($pool_attributes[sizeof($pool_attributes)]);

		return $pool_attributes;
	}

	/**
	 * Sets the pool name.
	 *
	 * @param  string  $name  the current pool name
	 * @param  string  $value  the new pool name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetPoolName($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# No need to continue
		if ($name == $value)
			return;

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");

		# Check for non-uniques
		$check_existing = $this->GetPoolAttributes($value);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($value)", COMMON_ERROR);

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "Name", $value);
		$this->SetAllKeys("Pool", $name, $value);
	}

	/**
	 * Sets the pool type.
	 *
	 * @param  string  $name  the pool name
	 * @param  string  $value  type
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetPoolType($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "PoolType", $value);
	}

	/**
	 * Sets the pool recycle .
	 *
	 * @param  string  $name  the pool name
	 * @param  boolean  $value  flag to recycle pool or not
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetPoolRecycle($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "Recycle", $value);
	}

	/**
	 * Sets the pool auto-prune.
	 *
	 * @param  string  $name  the pool name
	 * @param  boolean  $value  flag to auto prune or not
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetPoolAutoPrune($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "AutoPrune", $value);
	}

	/**
	 * Sets the pool volume retention.
	 *
	 * @param  string  $name  the pool name
	 * @param  int  $value  volume retention period
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetPoolVolumeRetention($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "VolumeRetention", $value);
	}

	/**
	 * Sets the pool maximum volumes parameter.
	 *
	 * @param  string  $name  the pool name
	 * @param  boolean  $value  maximum volumes
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetPoolMaxVolumes($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$value)
			$value = self::FLAG_DELETE;
		else if(! $this->IsValidInteger($value))
			throw new ValidationException(LOCALE_LANG_INVALID . " - $value.");

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "MaximumVolumes", $value);
	}

	/**
	 * Sets the pool maximum volume jobs parameter.
	 *
	 * @param  string  $name  the pool name
	 * @param  boolean  $value  maximum volume jobs
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetPoolMaxVolumeJobs($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$value)
			$value = self::FLAG_DELETE;
		else if(! $this->IsValidInteger($value))
			throw new ValidationException(LOCALE_LANG_INVALID . " - $value.");


		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "MaximumVolumeJobs", $value);
	}

	/**
	 * Sets the pool label format.
	 *
	 * @param  string  $name  the pool name
	 * @param  string  $value  label format
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetPoolLabelFormat($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Pool labels (for auto labelling) must be unique.
		$pool_array = Array();
		$pool_array = $this->GetPoolList();
		if ($value) {
			foreach ($pool_array as $pool) {
				if ($pool == $name)
					continue;
				if (eregi($value, $this->GetKey(self::DIR_FILE_CONFIG, "Pool", $pool, "LabelFormat")))
					throw new EngineException (BACULA_LANG_ERRMSG_NON_UNIQUE_LABEL, COMMON_ERROR);
			}
		}

		$this->SetKey(self::DIR_FILE_CONFIG, "Pool", $name, "LabelFormat", $value);
	}

	/**
	 * Add a pool.
	 *
	 * @param  string  $name  the pool name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function AddPool($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		// Check for non-uniques
		$check_existing = $this->GetPoolAttributes($name);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);

		$newclient = Array(
			"Pool {",
			"  Name = \"" . trim($name) . "\"",
			"  PoolType = \"Backup\"",
			"  Recycle = \"yes\"",
			"  AutoPrune = \"yes\"",
			"  VolumeRetention = 3 months",
			"}"
		);
		$this->InsertBlock(self::DIR_FILE_CONFIG, $newclient);
	}

	/**
	 * Delete a pool.
	 * @param  string  $name  the pool name
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeletePool($index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Delete any dependent jobs
		$this->DeleteDependentJob($index, "Pool");

		# Delete pool
		$this->DeleteBlockByIndex(self::DIR_FILE_CONFIG, $index);
	}

	/******************************
	*         S T O R A G E       *
	******************************/

	/**
	 * Sets a storage device name.
	 *
	 * @param  string  $name  the current storage device name
	 * @param  string  $value  the new storage device name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetSdName($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# No need to continue
		if ($name == $value)
			return;

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");

		# Check for non-uniques
		if (trim($name) != trim($value)) {
			$check_existing = $this->GetSdAttributes($value);
			if ($check_existing)
				throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($value)", COMMON_ERROR);

			$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $name, "Device", $value);
			$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $name, "Name", $value);
			$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "Name", $value);
		}
		$this->SetAllKeys("Device", $name, $value);
	}

	/**
	 * Sets a storage device address.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device address
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetSdAddress($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		# Validate
		$network = new Network();
		if (! $network->IsValidIp($value)) {
			$errors = $this->GetValidationErrors();
			if (! $network->IsValidHostname($value)) {
				$errors = $this->GetValidationErrors();
				throw new ValidationException($errors[0]);
			}
		}
		$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $name, "Address", $value);
	}

	/**
	 * Sets a storage device port.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device port
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetSdPort($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		$network = new Network();
		if (! $network->IsValidPort($value)) {
			$errors = $this->GetValidationErrors();
	        throw new ValidationException($errors[0]);
		}

		$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $name, "SDport", $value);
	}

	/**
	 * Sets a storage device password.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device password
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetSdPassword($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidPassword($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_PASSWORD);

		$oldpassword = $this->GetSdPassword($name);
		$address = $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $name, "Address");
		if ($oldpassword != $value) {
			# Check to see if there are other defined devices at this address.
			# If so, we need to change the password.
			$storage_list = $this->GetSdList();
			foreach ($storage_list as $storage) {
				$storage_address = $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $storage, "Address");
				if ($storage_address == $address)
					$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $storage, "Password", $value);

			}
			$this->SetKey(self::SD_FILE_CONFIG, "Director", $this->GetDirectorName(), "Password", $value);
		}
	}

	/**
	 * Sets a storage device mount point/location.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device mount point
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdMount($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$media = $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "MediaType");
		# Override mount point point for REV and USB MSD's
		if (eregi("^" . Bacula::MEDIA_IOMEGA, $media) || eregi("^" . Bacula::MEDIA_USB, $media)) {
			$value = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $name);
		}
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "ArchiveDevice", $value);
		if (eregi("^" . self::MEDIA_FILE, $media)) {
			$folder = new Folder($value, true);
			if (!$folder->Exists()) {
				$folder->Create("root", "root", "0750");
			}
		}
	}

	/**
	 * Sets a storage device media type.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device media type
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdMediaType($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $name, "MediaType", $value);
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MediaType", $value);

		# To prevent redundancy, we set the "DeviceType" automatically
		if (self::MEDIA_FILE == $value)
			$this->SetSdDeviceType($name, "File");
		else if (self::MEDIA_IOMEGA == $value)
			$this->SetSdDeviceType($name, "File");
		else if (self::MEDIA_USB == $value)
			$this->SetSdDeviceType($name, "File");
		else if (self::MEDIA_DDS == $value || self::MEDIA_DLT == $value)
			$this->SetSdDeviceType($name, "Tape");
		else if (self::MEDIA_DVD == $value)
			$this->SetSdDeviceType($name, "DVD");
	}

	/**
	 * Sets a storage device type.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device media type
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdDeviceType($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "DeviceType", $value);
	}

	/**
	 * Sets a storage device file retention.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device label media parameter
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdLabelMedia($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "LabelMedia", $value);
	}

	/**
	 * Sets a storage device random access.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device random access parameter
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdRandomAccess($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "RandomAccess", $value);
	}

	/**
	 * Sets a storage device auto mount parameter.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device auto mount parameter
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdAutomaticMount($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "AutomaticMount", $value);
	}

	/**
	 * Sets a storage device removable media parameter.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device always open parameter
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdRemovableMedia($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "RemovableMedia", $value);

		if (eregi("yes", $value))
			$this->ConfigureAutoFs($name);
	}

	/**
	 * Sets a storage device always open parameter.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $value  the storage device always open parameter
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdAlwaysOpen($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "AlwaysOpen", $value);
	}

	/**
	 * Sets a storage device maximum volume size parameter.
	 *
	 * @param  string  $name  the storage device name
	 * @param  int  $value  the storage device maximum volume size parameter
	 * @param  string  $unit  the storage device maximum volume size unit
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdMaximumVolumeSize($name, $value, $unit)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($value == 0 || !$value)
			$value = self::FLAG_DELETE;
		else
			$value = $value . $unit;
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MaximumVolumeSize", $value);
	}

	/**
	 * Sets a storage device comment to embed parameters not for bacula.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $key  comment key
	 * @param  string  $value  comment value
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSdComment($name, $key, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, $key, $value, true);
	}

	/**
	 * GetSdName.
	 *
	 * @param  string  $name  the storage device name
	 * @returns  string  the storage device name
	 * @throws  EngineException
	 */

	function GetSdName($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $name, "Name");
	}

	/**
	 * GetSdPassword.
	 *
	 * @param  string  $name  the storage device name
	 * @returns  string  the storage device password
	 * @throws  EngineException
	 */

	function GetSdPassword($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $name, "Password");
	}

	/**
	 * Get a list of active storage devices.
	 *
	 * @returns an array of storage devices
	 * @throws  EngineException
	 */

	function GetSdList()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sd_array = array();

		# Regular expressions
		# -------------------
		$regex_sd = "^[[:space:]]*Storage*[[:space:]]\\{.*$";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$match = array();
		
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			if (!is_array($this->block[self::DIR_FILE_CONFIG][$index]))
				continue;
			if (eregi($regex_sd, $this->block[self::DIR_FILE_CONFIG][$index][0])) {
				$subindex = 0;
				while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
					$subindex++;
					continue;
				}
				$value = trim($match[2]);
				# Check for comments
				$value = preg_replace("/#.*/", "", $value);
				# Check for quotations
				$value = preg_replace("/\"/", "", $value);
				$sd_array[$index] = trim($value);
			}
		}
		return $sd_array;
	}

	/**
	 * Get all storage device attributes (ie. key/value pairs).
	 *
	 * @param  string  $sd  the storage device name
	 * @returns  array  a list of SD attributes
	 * @throws  EngineException
	 */

	function GetSdAttributes($sd)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Attributes from director file
		$dir_attributes = array();
		$dir_attributes = $this->RemoveComments($this->GetBlock(self::DIR_FILE_CONFIG, "Storage", $sd));

		# Remove block start/stop
		unset($dir_attributes[0]);
		unset($dir_attributes[sizeof($dir_attributes)]);

		# Attributes from storage daemon file
		$sd_attributes = array();
		$sd_attributes = $this->RemoveComments($this->GetBlock(self::SD_FILE_CONFIG, "Device", $sd));

		# Remove block start/stop
		unset($sd_attributes[0]);
		unset($sd_attributes[sizeof($sd_attributes)]);

		$final = array_merge($dir_attributes, $sd_attributes);

		return $final;
	}

	/**
	 * GetSdIsRemovable.
	 *
	 * @param  string  $name  the storage device name
	 * @returns  boolean
	 * @throws  EngineException
	 */

	function GetSdIsRemovable($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "RemovableMedia");
		if (eregi("yes", $value))
			return true;
		else
			return false;
	}

	/**
	 * GetSdArchiveDevice.
	 *
	 * @param  string  $name  the storage device name
	 * @returns  string  archive device
	 * @throws  EngineException
	 */

	function GetSdArchiveDevice($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "ArchiveDevice");
		return $value;
	}

	/**
	 * GetSdMountPoint.
	 *
	 * @param  string  $name  the storage device name
	 * @returns  string  the mount point
	 * @throws  EngineException
	 */

	function GetSdMountPoint($name)
	{
	    if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

	    return $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "MountPoint");
	}

	/**
	 * GetSdMediaType.
	 *
	 * @param  string  $name  the storage device name
	 * @returns  string  the media type
	 * @throws  EngineException
	 */

	function GetSdMediaType($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "MediaType");
	}

	/**
	 * Add a storage device.
	 *
	 * @param  string  $name  the storage device name
	 * @param  string  $media_type  the media type (default FILE)
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function AddSd($name, $media_type = self::MEDIA_FILE)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Media Type must be unique
		$media_type .= "-" . rand(1,9999);

		// Validate
		//---------
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		// Check for non-uniques
		$check_existing = $this->GetSdAttributes($name);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);

		# Try not to use 'localhost'
		# Check for 'localhost' address
		$address = 'localhost';
		try {
	        $interfaces = new IfaceManager();
	        $network = new Network();
	        $ethlist = $interfaces->GetInterfaceDetails();
	        foreach ($ethlist as $eth => $info) {
	            if ($network->IsLocalIp($info['address'])) {
					$address = $info['address'];
	                break;
	            }
	        }
		} catch (Exception $e) {
			// self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
	    }

		# Get Storage daemon password from default
		$storage_list = $this->GetSdList();
		foreach ($storage_list as $storage) {
			$storage_address = $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $storage, "Address");
			if ($storage_address == $address || $storage_address == "localhost") {
				$passwd = $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $storage, "Password");
				if ($storage_address == "localhost")
					$this->SetKey(self::DIR_FILE_CONFIG, "Storage", $storage, "Address", $address);
	        }
	    }

		# Write to director daemon configuration
		$newsd = Array(
			"Storage {",
			"  Name = \"" . trim($name) . "\"",
			"  Address = \"" . $address . "\"",
			"  SDport = 9103",
			"  Password = \"" . $passwd . "\"",
			"  Device = \"" . trim($name) . "\"",
			"  MediaType = \"" . $media_type . "\"",
			"}"
		);
		$this->InsertBlock(self::DIR_FILE_CONFIG, $newsd);

		# Write to storage daemon configuration
		unset($newsd);
		$newsd = Array(
			"Device {",
			"  Name = \"" . $name . "\"",
			"  MediaType = \"" . $media_type . "\"",
			"  ArchiveDevice = \"/tmp\"",
			"  LabelMedia = \"yes\"",
			"  RandomAccess = \"yes\"",
			"  AutomaticMount = \"yes\"",
			"  RemovableMedia = \"no\"",
			"  AlwaysOpen = \"yes\"",
			"}"
		);
		$this->InsertBlock(self::SD_FILE_CONFIG, $newsd);
	}

	/**
	 * Delete a storage device.
	 *
	 * @param  int  $index  a storage device index
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteSd($index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Delete any dependent jobs
		$this->DeleteDependentJob($index, "Storage");

		$this->Load(self::DIR_FILE_CONFIG);
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$subindex = 0;
		$match = array();
		
		while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
			if ($subindex > sizeof($this->block[self::DIR_FILE_CONFIG][$index]))
				throw new EngineException (FILE_PARSE_ERROR, COMMON_ERROR);
			$subindex++;
			continue;
		}
		$name = trim($match[2]);
		# Need to delete in the director file
		$this->DeleteBlockByIndex(self::DIR_FILE_CONFIG, $index);
		# And...need to delete in the SD file
		$this->DeleteBlock(self::SD_FILE_CONFIG, "Device", $name);
	}

	/******************************
	*       S C H E D U L E       *
	******************************/

	/**
	 * Sets a schedule name.
	 *
	 * @param  string  $name  the current schedule name
	 * @param  string  $value  the new schedule name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetScheduleName($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# No need to continue
		if ($name == $value)
			return;

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		# Check for non-uniques
		$check_existing = $this->GetScheduleAttributes($value);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($value)", COMMON_ERROR);

		$this->SetKey(self::DIR_FILE_CONFIG, "Schedule", $name, "Name", $value);
		$this->SetAllKeys("Schedule", $name, $value);
	}

	/**
	 * Sets a schedule level.
	 *
	 * @param  string  $name  the schedule name
	 * @param  string  $run_list  the new schedule run list
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetScheduleRunList($name, $run_list)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->DeleteBlock(self::DIR_FILE_CONFIG, "Schedule", $name);

		if (sizeof($run_list) == 0)
			return;

		$insert_list = Array(0 => "Schedule {", 1 => "  Name = \"" . $name . "\"");

		foreach($run_list as $line)
			$insert_list[] = $line;

		$insert_list[sizeof($insert_list)] = "}";

		# Insert new scheduler
		$this->InsertBlock(self::DIR_FILE_CONFIG, $insert_list);
	}

	/**
	 * Sets a schedule pools to use.
	 *
	 * @param  string  $name  the schedule name
	 * @param  string  $pools  the pools to use as default
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetSchedulePools($name, $pools)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->DirectiveExists(self::DIR_FILE_CONFIG, "Pool", $pools['pool']))
			$this->SetKey(self::DIR_FILE_CONFIG, "Schedule", $name, "Pool", $pools['pool']);
		if ($this->DirectiveExists(self::DIR_FILE_CONFIG, "Pool", $pools['full']))
			$this->SetKey(self::DIR_FILE_CONFIG, "Schedule", $name, "FullPool", $pools['full']);
		if ($this->DirectiveExists(self::DIR_FILE_CONFIG, "Pool", $pools['inc']))
			$this->SetKey(self::DIR_FILE_CONFIG, "Schedule", $name, "IncrementalPool", $pools['inc']);
		if ($this->DirectiveExists(self::DIR_FILE_CONFIG, "Pool", $pools['diff']))
			$this->SetKey(self::DIR_FILE_CONFIG, "Schedule", $name, "DifferentialPool", $pools['diff']);
	}

	/**
	 * Get a list of schedules.
	 *
	 * @returns  array  a list of schedules
	 * @throws  EngineException
	 */

	function GetScheduleList()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$schedule_array = array();

		# Regular expressions
		# -------------------
		$regex_schedule = "^[[:space:]]*Schedule*[[:space:]]\\{.*$";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$match = array();
		
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			if (!is_array($this->block[self::DIR_FILE_CONFIG][$index]))
				continue;
			if (eregi($regex_schedule, $this->block[self::DIR_FILE_CONFIG][$index][0])) {
				$subindex = 0;
				while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
					$subindex++;
					continue;
				}
				$value = trim($match[2]);
				# Check for comments
				$value = preg_replace("/#.*/", "", $value);
				# Check for quotations
				$value = preg_replace("/\"/", "", $value);
				$schedule_array[$index] = trim($value);
			}
		}
		return $schedule_array;
	}

	/**
	 * Get all schedule attributes (ie. Name and run commands).
	 *
	 * @param  string  $schedule  a schedule resource
	 * @returns  array  a list of schedule attributes
	 * @throws  EngineException
	 */

	function GetScheduleAttributes($schedule)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$schedule_attributes = array();

		$schedule_attributes = $this->RemoveComments($this->GetBlock(self::DIR_FILE_CONFIG, "Schedule", $schedule));

		# Remove block start/stop
		unset($schedule_attributes[0]);
		unset($schedule_attributes[sizeof($schedule_attributes)]);

		return $schedule_attributes;
	}

	/**
	 * Add a schedule.
	 *
	 * @param  string  $name  a schedule name resource
	 * @returns  void
	 * @throws  EngineException
	 */

	function AddSchedule($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		# Check for non-uniques
		$check_existing = $this->GetScheduleAttributes($name);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);

		$newschedule = Array(
			"Schedule {",
			"  Name = \"" . trim($name) . "\"",
			"  Run = Full monthly at 1:10",
			"}"
		);
		$this->InsertBlock(self::DIR_FILE_CONFIG, $newschedule);
	}

	/**
	 * Delete a schedule.
	 *
	 * @param  int  $index  a schedule index
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteSchedule($index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Delete any dependent jobs
		$this->DeleteDependentJob($index, "Schedule");

		# Delete schedule
		$this->DeleteBlockByIndex(self::DIR_FILE_CONFIG, $index);
	}

	/******************************
	*        F I L E S E T        *
	******************************/

	/**
	 * Get a list of filesets.
	 *
	 * @returns  array  list of filesets
	 * @throws  EngineException
	 */

	function GetFilesetList()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$fileset_array = array();

		# Regular expressions
		# -------------------
		$regex_fileset = "^[[:space:]]*FileSet*[[:space:]]\\{.*$";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";

		$match = array();
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			if (!is_array($this->block[self::DIR_FILE_CONFIG][$index]))
				continue;
			if (eregi($regex_fileset, $this->block[self::DIR_FILE_CONFIG][$index][0])) {
				$subindex = 0;
				while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
					$subindex++;
					if ($subindex > sizeof($this->block[self::DIR_FILE_CONFIG][$index]))
						throw new EngineException (FILE_PARSE_ERROR, COMMON_ERROR);

					continue;
				}
				$value = trim($match[2]);
				# Check for comments
				$value = preg_replace("/#.*/", "", $value);
				# Check for quotations
				$value = preg_replace("/\"/", "", $value);
				$fileset_array[$index] = trim($value);
			}
		}
		return $fileset_array;
	}

	/**
	 * Get all fileset include statements.
	 *
	 * @param  string  $fileset  the fileset name resource
	 * @returns  array  a list of include statements
	 * @throws  EngineException
	 */

	function GetFilesetInclude($fileset)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$include_fileset = Array();
		$include_flag = false;
		$include_index = 0;
		$index = 0;
		$regex_include = "^[[:space:]]*(Include)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_file = "^[[:space:]]*(File)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_block_end = "^[[:space:]]*}[[:space:]]*$";

		$match = array();
		$lines = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $fileset);
		
		foreach ($lines as $line) {
			if (is_array($line)) {
				for ($subindex = 0; $subindex < sizeof($line); $subindex++) {
					if (eregi($regex_include, $line[$subindex]))
						$include_flag = true;
					if (eregi($regex_block_end, $line[$subindex]) && $include_flag) {
						$include_fileset[$include_index][$index] = "";
						$include_flag = false;
						$index = 0;
						$include_index++;
					}
					if ($include_flag) {
						if (eregi($regex_file, $line[$subindex], $match)) {
							$include_fileset[$include_index][$index] = preg_replace("/\"/", "", trim($match[2]));
							$index++;
						}
					}
				}
			}
		}
		return $include_fileset;
	}

	/**
	 * Get all fileset exclude statements.
	 *
	 * @param  string  $fileset  the fileset name resource
	 * @returns array  a list of exclude statements
	 * @throws  EngineException
	 */

	function GetFilesetExclude($fileset)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$exclude_fileset = Array();
		$exclude_flag = false;
		$exclude_index = 0;
		$index = 0;
		$regex_exclude = "^[[:space:]]*(Exclude)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_file = "^[[:space:]]*(File)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_block_end = "^[[:space:]]*}[[:space:]]*$";

		$match = array();
		$lines = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $fileset);
		
		foreach ($lines as $line) {
			if (is_array($line)) {
				for ($subindex = 0; $subindex < sizeof($line); $subindex++) {
					if (eregi($regex_exclude, $line[$subindex]))
						$exclude_flag = true;
					if (eregi($regex_block_end, $line[$subindex])) {
						$exclude_flag = false;
						$index = 0;
						$exclude_index++;
					}
					if ($exclude_flag) {
						if (eregi($regex_file, $line[$subindex], $match)) {
							$exclude_fileset[$exclude_index][$index] = preg_replace("/\"/", "", trim($match[2]));
							$index++;
						}
					}
				}
			}
		}

		return $exclude_fileset;
	}

	/**
	 * Get the fileset options.
	 * @param   fileset                the name of the fileset
	 * @param   inc_index              the fileset index
	 *
	 * @returns an array
	 * @throws  EngineException
	 */

	function GetFilesetOptions($fileset, $inc_index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$include_options = Array();
		$wild = Array();
		$wilddir = Array();
		$wildfile = Array();
		$regex = Array();
		$regexdir = Array();
		$regexfile = Array();
		$option_flag = false;
		$index = -1;
		$regex_include = "^[[:space:]]*(Include)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_options = "^[[:space:]]*(Options)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_compression = "^[[:space:]]*(Compression)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_sig = "^[[:space:]]*(Signature)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_exclude = "^[[:space:]]*(Exclude)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_case = "^[[:space:]]*(IgnoreCase)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_wild = "^[[:space:]]*(Wild)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_wilddir = "^[[:space:]]*(WildDir)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_wildfile = "^[[:space:]]*(WildFile)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_regex= "^[[:space:]]*(Regex)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_regexdir = "^[[:space:]]*(RegexDir)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_regexfile = "^[[:space:]]*(RegexFile)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_block_end = "^[[:space:]]*}[[:space:]]*$";

		$match = array();
		$lines = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $fileset);
		
		foreach ($lines as $line) {
			if (is_array($line)) {
				for ($subindex = 0; $subindex < sizeof($line); $subindex++) {
					if (is_array($line[$subindex])) {
						for ($optionindex = 0; $optionindex < sizeof($line[$subindex]); $optionindex++) {
							if (eregi($regex_options, $line[$subindex][$optionindex]))
								$option_flag = true;
							if (eregi($regex_block_end, $line[$subindex][$optionindex]))
								$option_flag = false;
							if ($option_flag && $index == $inc_index) {
								if (eregi($regex_compression, $line[$subindex][$optionindex], $match)) {
									$include_options["compression"] = true;
								} else if (eregi($regex_sig, $line[$subindex][$optionindex], $match)) {
										$include_options["signature"] = $match[2];
								} else if (eregi($regex_exclude, $line[$subindex][$optionindex], $match)) {
									if (eregi("yes", preg_replace("/^\"|\"$/", "", $match[2])))
										$include_options["exclude"] = true;
									else
										$include_options["exclude"] = false;
								} else if (eregi($regex_case, $line[$subindex][$optionindex], $match)) {
									if (eregi("yes", preg_replace("/^\"|\"$/", "", $match[2])))
										$include_options["case"] = true;
									else
										$include_options["case"] = false;
								} else if (eregi($regex_wild, $line[$subindex][$optionindex], $match)) {
									$wild[] = trim(preg_replace("/^\"|\"$/", "", $match[2]));
								} else if (eregi($regex_wilddir, $line[$subindex][$optionindex], $match)) {
									$wilddir[] = trim(preg_replace("/^\"|\"$/", "", $match[2]));
								} else if (eregi($regex_wildfile, $line[$subindex][$optionindex], $match)) {
									$wildfile[] = trim(preg_replace("/^\"|\"$/", "", $match[2]));
								} else if (eregi($regex_regex, $line[$subindex][$optionindex], $match)) {
									$regex[] = trim(preg_replace("/^\"|\"$/", "", $match[2]));
								} else if (eregi($regex_regexdir, $line[$subindex][$optionindex], $match)) {
									$regexdir[] = trim(preg_replace("/^\"|\"$/", "", $match[2]));
								} else if (eregi($regex_regexfile, $line[$subindex][$optionindex], $match)) {
									$regexfile[] = trim(preg_replace("/^\"|\"$/", "", $match[2]));
								}
							}
						}
					} else {
						# Increase the fileset index
						if (eregi($regex_include, $line[$subindex]))
							$index++;
					}
				}
			}
		}

		# Wild cards
		$wild[] = "";
		$wilddir[] = "";
		$wildfile[] = "";
		$include_options["wild"] = $wild;
		$include_options["wilddir"] = $wilddir;
		$include_options["wildfile"] = $wildfile;

		# REGEX
		$regex[] = "";
		$regexdir[] = "";
		$regexfile[] = "";
		$include_options["regex"] = $regex;
		$include_options["regexdir"] = $regexdir;
		$include_options["regexfile"] = $regexfile;

		return $include_options;
	}

	/**
	 * Add a fileset.
	 * @param  string  $name  the fileset name
	 * @param  boolean $database  a flag indicating the fileset represents a database
	 *
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function AddFileset($name, $database)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		// Check for non-uniques
		$check_existing = $this->GetFilesetInclude($name);
		if ($check_existing) {
			self::Log(COMMON_ERROR, BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", __METHOD__, __LINE__);
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);
		}
		if ($database) {
			$newfileset = Array(
				"FileSet {",
				"  Name = \"" . trim($name) . "\"",
				"  " . self::FLAG_FILESET_DB,
				"  Include {",
				"    Options {",
				"      Signature = MD5",
				"    }",
				"    File = \"" . self::BACULA_VAR . $name . ".sql\"",
				"  }",
				"}"
			);
			$default_properties = Array (
				"TYPE" => "mysql",
				"HOST" => "localhost",
				"NAME" => $name,
				"USER" => "",
				"PASS" => "",
				"PORT" => "3306"
			);
			$this->UpdateDbScripts($name, $default_properties);
		} else {
			$newfileset = Array(
				"FileSet {",
				"  Name = \"" . $name . "\"",
				"  Include {",
				"    Options {",
				"      signature = MD5",
				"    }",
				"  }",
				"  Exclude {",
				"    File = /proc",
				"    File = /tmp",
				"    File = /.autofsck",
				"  }",
				"}"
			);
		}
		$this->InsertBlock(self::DIR_FILE_CONFIG, $newfileset);
	}

	/**
	 * Add a fileset.
	 * @param  string  $name  the fileset name
	 * @param  string  $os  the OS
	 *
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function AddBasicFileset($name, $os)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		# Check for non-uniques
		$check_existing = $this->GetFilesetInclude($name . "-" . $os);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);

		$newfileset = Array();
		try {
			$file = new File(self::BACULA_USR . "/" . $os . ".fileset");
			$newfileset = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new Exception ($e);
		}

		# TODO - this breaks if changes made to /usr/bacula/xxx.fileset
		$newfileset[2] = "  Name = \"" .  $name . "-" . $os . "\"";

		$this->InsertBlock(self::DIR_FILE_CONFIG, $newfileset);
		$this->Commit(true);
	}

	/**
	 * Add an include block to an existing fileset.
	 *
	 * @param  string  $name  name of fileset to add include options
	 * @returns  void
	 * @throws  EngineException
	 */

	function AddFilesetInclude($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$newinclude = Array(
			"  Include {",
			"    Options {",
			"      signature = MD5",
			"    }",
			"  }"
		);
		$newblock = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $name);
		$newblock[sizeof($newblock) - 1] = $newinclude;
		$newblock[] = "}";

		$this->SetBlock(self::DIR_FILE_CONFIG, "FileSet", $name, $newblock);
	}

	/**
	 * Delete a fileset.
	 *
	 * @param  int  $index  index representing fileset
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteFileset($index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Delete any dependent jobs
		$this->DeleteDependentJob($index, "FileSet");

		# Delete fileset
		$this->DeleteBlockByIndex(self::DIR_FILE_CONFIG, $index);
	}

	/**
	 * Sets a fileset list.
	 *
	 * @param  string  $name  the schedule name
	 * @param  string  $include_list  include list
	 * @param  string  $exclude_list  exclude list
	 * @param  string  $options  array contain specific options
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFilesetList($name, $include_list, $exclude_list, $options)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$newblock = Array();
		$include_flag = false;
		$exclude_flag = false;
		$index = 0;
		$include_index = 0;
		$exclude_index = 0;
		$regex_include = "^[[:space:]]*(Include)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_exclude = "^[[:space:]]*(Exclude)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_file = "^[[:space:]]*(File)[[:space:]]*=[[:space:]]*(.*$)";
		$regex_block_end = "^[[:space:]]*(})[[:space:]]*(.*$)";

		$lines = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $name);

		# Add include statement as req'd
		if ($include_list && !$this->GetFilesetInclude($name)) {
			$inc = Array(
				"  Include {",
				Array(
					"    Options {",
					"      Signature = MD5",
					"    }"),
				"  }"
			);
			$lines[sizeof($lines) - 1] = $inc;
			$lines[] = "}";
		}
		# Add exclude statement as req'd
		if ($exclude_list && !$this->GetFilesetExclude($name)) {
			$exc = Array(
				"  Exclude {",
				"  }"
			);
			$lines[sizeof($lines) -1] = $exc;
			$lines[] = "}";
		}

		foreach ($lines as $line) {
			if (is_array($line)) {
				for ($subindex = 0; $subindex < sizeof($line); $subindex++) {
					if (eregi($regex_include, $line[$subindex]) && !$include_list[$include_index]) {
						# Skip block entirely...it was deleted
						$include_index++;
						break;
					}
					if (eregi($regex_exclude, $line[$subindex]) && !$exclude_list[$exclude_index]) {
						# Skip block entirely...it was deleted
						$exclude_index++;
						break;
					}
					if (eregi($regex_include, $line[$subindex]))
						$include_flag = true;
					if (eregi($regex_exclude, $line[$subindex]))
						$exclude_flag = true;
					if ($include_flag && $include_list[$include_index]) {
						if (eregi($regex_file, $line[$subindex]))
							continue;
						if (eregi($regex_block_end, $line[$subindex])) {
							foreach ($include_list[$include_index] as $newline) {
								# Add lines
								$subindex++;
								if (eregi("^[[:space:]]*(File)[[:space:]]*=[[:space:]]*(\"[a-z]:.*$)", $newline))
									$newline = preg_replace("/\\\/", "/", $newline);
								$newblock[$index][$subindex] = $newline;
							}
							$newblock[$index][$subindex + 1] = "  }";
							$include_index++;
							$include_flag = false;
							break;
						} else {
							# Handle options
							if (is_array($line[$subindex])) {
								$newblock[$index][$subindex] = $this->RewriteFilesetOptions(
									$line[$subindex], $options[$include_index]
								);
							} else {
								$newblock[$index][$subindex] = $line[$subindex];
							}
						}
					}
					if ($exclude_flag && $exclude_list[$exclude_index]) {
						if (eregi($regex_file, $line[$subindex]))
							continue;
						if (eregi($regex_block_end, $line[$subindex])) {
							foreach ($exclude_list[$exclude_index] as $newline) {
								# Add lines
								$subindex++;
								$newblock[$index][$subindex] = $newline;
							}
							$newblock[$index][$subindex + 1] = "  }";
							$exclude_index++;
							$exclude_flag = false;
							break;
						} else {
							$newblock[$index][$subindex] = $line[$subindex];
						}
					}
				}
			} else {
				$newblock[$index] = $line;
			}
			$index++;
		}
		$this->SetBlock(self::DIR_FILE_CONFIG, "FileSet", $name, $newblock);
	}

	/**
	 * Rewrite the filset options.
	 * @returns  array  a list containing fileset options
	 */

	function RewriteFilesetOptions($old, $new)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$supported_directives = "compression|signature|ignorecase|exclude|wild|wildfile|wilddir|regex|regexfile|regexdir";
		$regex_directives= "^[[:space:]]*" . $supported_directives . "[[:space:]]*=[[:space:]]*(.*$)";
		$regex_block_end = "^[[:space:]]*(})[[:space:]]*(.*$)";
		$block = Array();
		foreach ($old as $oldline) {
			if (eregi($regex_block_end, $oldline)) {
				if ($new["compression"] == "yes")
					$block[] = "      Compression = \"GZIP\"";
				if ($new["signature"] != "NONE" && $new["signature"])
					$block[] = "      Signature = \"" . $new["signature"] . "\"";
				if ($new["case"])
					$block[] = "      IgnoreCase = \"" . $new["case"] . "\"";
				foreach ($new["wild"] as $line)
					if ($line) {
						$block[] = "      Wild = \"" . preg_replace("/^\"|\"$/", "", $line) . "\"";
				}
				foreach ($new["wilddir"] as $line)
					if ($line) {
						$block[] = "      Wilddir = \"" . preg_replace("/^\"|\"$/", "", $line) . "\"";
				}
				foreach ($new["wildfile"] as $line)
					if ($line) {
						$block[] = "      Wildfile = \"" . preg_replace("/^\"|\"$/", "", $line) . "\"";
				}
				foreach ($new["regex"] as $line)
					if ($line) {
						$block[] = "      Regex = \"" . preg_replace("/^\"|\"$/", "", $line) . "\"";
				}
				foreach ($new["regexdir"] as $line)
					if ($line) {
						$block[] = "      Regexdir = \"" . preg_replace("/^\"|\"$/", "",$line) . "\"";
				}
				foreach ($new["regexfile"] as $line)
					if ($line) {
						$block[] = "      Regexfile = \"" . preg_replace("/^\"|\"$/", "",$line) . "\"";
				}
				if ($new["exclude"])
					$block[] = "      Exclude = " . $new["exclude"];
				foreach ($new as $key => $newline) {
				}
			}
			if (!eregi($supported_directives, $oldline)) {
				$block[] = $oldline;
				continue;
			}
		}
		return $block;
	}

	/**
	 * Sets the fileset name.
	 *
	 * @param  string  $name  the current fileset name
	 * @param  string  $value  the new fileset name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetFilesetName($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# No need to continue
		if ($name == $value)
			return;

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");

		$this->SetKey(self::DIR_FILE_CONFIG, "FileSet", $name, "Name", $value);
		$this->SetAllKeys("FileSet", $name, $value);
	}

	/******************************
	*          C L I E N T        *
	******************************/

	/**
	 * Sets a client name.
	 *
	 * @param  string  $name  the current client name
	 * @param  string  $value  the new client name
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetClientName($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# No need to continue
		if ($name == $value)
			return;

		# Validate
		if(! $this->IsValidName($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($value)");

		$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "Name", $value);
		$this->SetAllKeys("Client", $name, $value);
	}

	/**
	 * Sets a client address.
	 *
	 * @param  string  $name  the client name
	 * @param  string  $value  the client address
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetClientAddress($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		$network = new Network();
		if (! $network->IsValidIp($value)) {
			$errors = $this->GetValidationErrors();
			if (! $network->IsValidHostname($value)) {
				$errors = $this->GetValidationErrors();
				throw new ValidationException($errors[0]);
			}
		}
		$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "Address", $value);
	}

	/**
	 * Sets a client port.
	 *
	 * @param  string  $name  the client name
	 * @param  string  $value  the client port
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetClientPort($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		$network = new Network();
		if (! $network->IsValidPort($value)) {
			$errors = $this->GetValidationErrors();
	        throw new ValidationException($errors[0]);
		}

		$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "FDport", $value);
	}

	/**
	 * Sets a client password.
	 *
	 * @param  string  $name  the client name
	 * @param  string  $value  the client password
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function SetClientPassword($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidPassword($value))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_PASSWORD);

		$oldpassword = $this->GetKey(self::DIR_FILE_CONFIG, "Client", $name, "Password");
		if ($oldpassword != $value) {
			$address = $this->GetKey(self::DIR_FILE_CONFIG, "Client", $name, "Address");
			$network = new Network();
			if ($network->IsLocalIp($address)) {
				$this->SetKey(self::FD_FILE_CONFIG, "Director", $this->GetDirectorName(), "Password", $value);
				$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "Password", $value);
			} else {
				$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "Password", $value);
			}
		}
	}

	/**
	 * Sets a client file retention.
	 *
	 * @param  string  $name  the client name
	 * @param  string  $value  the client file retention
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetClientFileRetention($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "FileRetention", $value);
	}

	/**
	 * Sets a client job retention.
	 *
	 * @param  string  $name  the client name
	 * @param  string  $value  the client job retention
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetClientJobRetention($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "JobRetention", $value);
	}

	/**
	 * Sets a client auto prune.
	 *
	 * @param  string  $name  the client name
	 * @param  string  $value  the client auto prune
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetClientAutoPrune($name, $value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetKey(self::DIR_FILE_CONFIG, "Client", $name, "AutoPrune", $value);
	}

	/**
	 * Get a list of active clients.
	 *
	 * @returns  array  a list of clients
	 * @throws  EngineException
	 */

	function GetClientList()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$client_array = array();

		# Regular expressions
		# -------------------
		$regex_client = "^[[:space:]]*Client*[[:space:]]\\{.*$";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$match = array();
		
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			if (!is_array($this->block[self::DIR_FILE_CONFIG][$index]))
				continue;
			if (eregi($regex_client, $this->block[self::DIR_FILE_CONFIG][$index][0])) {
				$subindex = 0;
				while (!eregi($regex_name, $this->block[self::DIR_FILE_CONFIG][$index][$subindex], $match)) {
					$subindex++;
					continue;
				}
				$value = trim($match[2]);
				# Check for comments
				$value = preg_replace("/#.*/", "", $value);
				# Check for quotations
				$value = preg_replace("/\"/", "", $value);
				$client_array[$index] = trim($value);
			}
		}
		return $client_array;
	}

	/**
	 * Get all client attributes (ie. key/value pairs).
	 * @param  string  $client  a Bacula client
	 *
	 * @returns an array of attributes
	 * @throws  EngineException
	 */

	function GetClientAttributes($client)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$client_attributes = array();

		$client_attributes = $this->RemoveComments($this->GetBlock(self::DIR_FILE_CONFIG, "Client", $client));

		# Remove block start/stop
		unset($client_attributes[0]);
		unset($client_attributes[sizeof($client_attributes)]);

		return $client_attributes;
	}

	/**
	 * Add a client.
	 * @param  string  $name  a client name
	 *
	 * @returns  void
	 * @throws  ValidationException EngineException
	 */

	function AddClient($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Validate
		if(! $this->IsValidName($name))
			throw new ValidationException(BACULA_LANG_ERRMSG_INVALID_NAME . " ($name)");

		# Check for non-uniques
		$check_existing = $this->GetClientAttributes($name);
		if ($check_existing)
			throw new EngineException (BACULA_LANG_ERRMSG_RESOURCE_EXISTS . " ($name)", COMMON_ERROR);

		# Create random password
		$passwd = "";
		$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
		for($i=0; $i < 8; $i++)
			$passwd .= $pattern{rand(0,35)};

		# Try not to use 'localhost'
        # Check for 'localhost' address
        $address = 'localhost';
        try {
            $interfaces = new IfaceManager();
            $network = new Network();
            $ethlist = $interfaces->GetInterfaceDetails();
            foreach ($ethlist as $eth => $info) {
                if ($network->IsLocalIp($info['address'])) {
                    $address = $info['address'];
                    break;
                }
            }
        } catch (Exception $e) {
            // self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
        }

		# Get client password from default
        $client_list = $this->GetClientList();
        foreach ($client_list as $client) {
            $client_address = $this->GetKey(self::DIR_FILE_CONFIG, "Client", $client, "Address");
            if ($client_address == $address || $client_address == "localhost") {
                $passwd = $this->GetKey(self::DIR_FILE_CONFIG, "Client", $client, "Password");
                if ($client_address == "localhost")
                    $this->SetKey(self::DIR_FILE_CONFIG, "Client", $client, "Address", $address);
            }
        }

		$newclient = Array(
			"Client {",
			"  Name = \"" . trim($name) . "\"",
			"  Address = \"" . $address . "\"",
			"  FDport = 9102",
			"  Catalog = \"MyCatalog\"",
			"  Password = \"" . $passwd . "\"",
			"  FileRetention = 30 days",
			"  JobRetention = 6 months",
			"  AutoPrune = \"yes\"",
			"}"
		);
		$this->InsertBlock(self::DIR_FILE_CONFIG, $newclient);
	}

	/**
	 * Delete a client.
	 * @param  int  $index  the client index
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteClient($index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Delete any dependent jobs
		$this->DeleteDependentJob($index, "Client");

		# Delete client
		$this->DeleteBlockByIndex(self::DIR_FILE_CONFIG, $index);
	}


	/******************************
	*       G E N E R I C         *
	******************************/

	/**
	 * Starts a backup.
	 * @param  string  $job  the job name
	 *
	 * @returns int  jobId if started successful, -1 if there were problems
	 * @throws  EngineException
	 */

	function StartBackup($job)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$job_id = -1;
		$restore_job = 1;
		$reply = "";

		# Validate backup job

		if (!$job)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_BACKUP_JOB, COMMON_ERROR);

		$type = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "Type");

		if (!eregi("Backup", $type))
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_BACKUP_JOB . " ($job)", COMMON_ERROR);

		
		$sd_name = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "Storage");
		$media_type = $this->GetSdMediaType($sd_name);
		if (eregi("^" . self::MEDIA_SMB, $media_type)) {
			# SAMBA mount...add before/after scripts if so
			$mount = $this->GetSdArchiveDevice($sd_name);
			$share = $this->DecodeShareInfo($sd_name);
			$this->CheckSmbMount($share, $mount, false);
			$this->SetKey(
				self::DIR_FILE_CONFIG,
				"Job",
				$job,
				"ClientRunAfterJob",
				self::CMD_SMBUMOUNT . " " . $mount
			);
		} else if (eregi("^" . self::MEDIA_DVD, $media_type)) {
			$dev = $this->GetKey(self::SD_FILE_CONFIG, "Device", $sd_name, "ArchiveDevice");
			$mount = $this->GetKey(self::SD_FILE_CONFIG, "Device", $sd_name, "MountPoint");

			# Create the full mount directory hear, because DVD's to not get automounted
			$folder = new Folder($mount, true);
			if (!$folder->Exists())
				$folder->Create("root", "root", "0700");

			# Test DVD
			$shell = new ShellExec();
			
			# Make sure parsing is done in English
			$options['env'] = "LANG=en_US";
			$retval = $shell->Execute(self::CMD_DVD_INFO, $dev, true, $options);
			if ($retval != 0) {
				$this->Disconnect($handle);
				throw new EngineException (BACULA_LANG_ERRMSG_BLANK_DVD_ERROR, COMMON_ERROR);
			}
			$output = $shell->GetOutput();
			$blank = false;
			$rewrite = false;
			$match = array();
			foreach ($output as $line) {
				if (eregi("^[[:space:]]*Mounted Media:.*RW.*", $line)) {
					$rewrite = true;
				} else if (eregi("^[[:space:]]*Disc status: (.*)[[:space:]]*(.*$)", $line, $match)) {
					if (trim($match[1]) == "blank")
						$blank = true;
				}
			}

			if (!$blank && !$rewrite && $backup) {
				throw new EngineException (BACULA_LANG_ERRMSG_DVD_CONTAINS_DATA, COMMON_ERROR);
			} elseif ($rewrite && $backup) {
				# Add disk format
				$this->SetKey(self::DIR_FILE_CONFIG, "Job", $job, "RunBeforeJob", self::CMD_DVD_HANDLER . " $dev prepare");
			} else {
				# Remove disk format
				#$this->SetKey(self::DIR_FILE_CONFIG, "Job", $job, "RunBeforeJob", self::FLAG_DELETE);
				# now that we know this is successful, add RunBefore and RunAfter scripts
				# Do we need this for DVD?
				/*$this->SetKey(
					self::DIR_FILE_CONFIG,
					"Job",
					$job,
					"ClientRunBeforeJob",
					"mount $dev $mount"
				);
				$this->SetKey(
					self::DIR_FILE_CONFIG,
					"Job",
					$job,
					"ClientRunAfterJob",
					"umount $mount"
				);
				*/
			}
		} else if (eregi("^" . self::MEDIA_IOMEGA, $media_type) || eregi("^" . self::MEDIA_USB, $media_type)) {
			# Check for media
			$mount = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $sd_name);
			$file = new File($mount, true);
			if ($file->IsSymLink() != 1)
				throw new EngineException (BACULA_LANG_ERRMSG_DEVICE_NOT_FOUND, COMMON_ERROR);
		}

		$this->Commit(true);

		$handle = $this->Connect();

		if (bacula_command($handle, "run job=\"" . $job . "\" JobId=" . $restore_job . " yes"))	{
			sleep(3);
			$messages = bacula_reply($handle);
			if ($messages) {
				foreach($messages as $line)
					$reply .= $line;
			}
		}
		
		$match = array();
		
		if (eregi("^(.*)(Job started.|Job queued.) JobId\=([[:digit:]]+)(.*$)", $reply, $match)) {
			$job_id = $match[3];
		} else {
			throw new EngineException (BACULA_LANG_ERRMSG_BACKUP_FAILED . " [$reply]", COMMON_ERROR);
		}

		$this->Disconnect($handle);

		return $job_id;
	}

	/**
	 * Starts a restore.
	 * @param  string  $client  a client name
	 * @param  string  $pool  a pool
	 * @param  string  $device  a storage device
	 * @param  string  $fileset  a fileset
	 * @param  string  $where  specifies where to restore the files
	 * @param  string  $replace  a flag (always, ifnewer, ifolder, never)
	 *
	 * @returns  int  jobId if started successful, -1 if there were problems
	 * @throws  EngineException
	 */

	function StartRestore($client, $pool, $device, $fileset, $where, $replace)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$job_id = -1;
		$reply = "";

		# Validate restore

		if (!$client)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_CLIENT, COMMON_ERROR);

		# Check for supported REV drive if defined in job...throw a nicer warning than Bacula
		$media_type = $this->GetSdMediaType($device);
		if (eregi("^" . self::MEDIA_IOMEGA, $media_type)) {
			try {
				$dev = $this->GetDeviceLocation($device);
			} catch (Exception $e) {
				throw new EngineException (BACULA_LANG_ERRMSG_HARDWARE_NOT_FOUND, COMMON_ERROR);
			}
		}
		$client_list = $this->GetClientList();
		$valid_client = false;
		foreach($client_list as $client_name) {
			if ($client_name == $client) {
				$valid_client = true;
				break;
			}
		}
		if (!$valid_client)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_CLIENT . " ($client)", COMMON_ERROR);

		if (!$pool)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_POOL . " ($pool)", COMMON_ERROR);

		$pool_list = $this->GetPoolList();
		$valid_pool = false;
		foreach($pool_list as $pool_name) {
			if ($pool_name == $pool) {
				$valid_pool = true;
				break;
			}
		}
		if (!$valid_pool)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_POOL . " ($pool)", COMMON_ERROR);

		$fileset_list = $this->GetFilesetList();
		$valid_fileset = false;
		foreach($fileset_list as $fileset_name) {
			if ($fileset_name == $fileset) {
				$valid_fileset = true;
				break;
			}
		}
		if (!$valid_fileset)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_FILESET . " ($fileset)", COMMON_ERROR);

		$device_list = $this->GetSdList();
		$valid_device = false;
		foreach($device_list as $device_name) {
			if ($device_name == $device) {
				$valid_device = true;
				break;
			}
		}
		if (!$valid_device)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_DEVICE . " ($device)", COMMON_ERROR);

		# Find the default Restore Job
		$job_list = $this->GetJobList();
		foreach ($job_list as $job) {
			if ($this->GetJobType($job) == "Restore") {
				$job_name = $job;
				break;
			}
		}

		# Set the replace policy flag
		$this->SetKey(self::DIR_FILE_CONFIG, "Job", $job_name, "Replace", $replace);
		$this->Commit(true);

		$handle = $this->Connect();

		if (bacula_command($handle, "restore client=\"" . $client . "\" " .
			"pool=\"" . $pool . "\" storage=\"" . $device . "\" fileset=\"" . $fileset . "\" " .
			"select current all done yes where=" . $where))
		{
			sleep(3);
			$messages = bacula_reply($handle);
			if ($messages) {
				foreach($messages as $line)
					$reply .= $line;
			}
		}
		
		$match = array();
		
		if (eregi("^(.*)(Job started.|Job queued.) JobId\=([[:digit:]]+)(.*$)", $reply, $match)) {
			$job_id = $match[3];
		} else {
			throw new EngineException (BACULA_LANG_ERRMSG_RESTORE_FAILED . " [$reply]", COMMON_ERROR);
		}

		$this->Disconnect($handle);

		return $job_id;
	}

	/**
	 * Start a restore using a BSR file.
	 * @param  string  $job  a job name
	 * @param  string  $client  a client name
	 * @param  string  $device  a storage device
	 * @param  string  $bootstrap  the bootstrap file
	 * @param  string  $where  the restore location
	 * @param  string  $replace  a flag (always, ifnewer, ifolder, never)
	 *
	 * @returns  int  jobId if started successful, -1 if there were problems
	 * @throws  EngineException
	 */

	function StartBsrRestore($job, $client, $device, $bootstrap, $where, $replace)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$job_id = -1;
		# The bash script requires integer for replace
		# 1 => "always"
	    # 2 => "ifnewer"
	    # 3 => "ifolder"
	    # 4 => "never"

		if ($replace == "always")
			$replace = "1";
		else if ($replace == "ifnewer")
			$replace = "2";
		else if ($replace == "ifolder")
			$replace = "3";
		else
			$replace = "4";

		# The Swift emailer class removes the last carriage return which gets Bacula's parser in a knot.
		$file = new File($bootstrap, true);
		if (! $file->Exists())
			throw new FileNotFoundException($bootstrap, COMMON_ERROR);
		$file->AddLines("\r\n");

		$param = "\"$job\" \"$client\" \"$device\" \"$bootstrap\" \"$where\" \"$replace\"";

		$shell = new ShellExec();
		try {
			$retval = $shell->Execute(self::CMD_RESTORE_BY_BSR, $param, true);
			sleep(2);
			$output = $shell->GetOutput();
	        if ($retval != 0)
				throw new EngineException (BACULA_LANG_ERRMSG_RESTORE_FAILED . " [$output]", COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException ($e->getMessage(), COMMON_ERROR);
		}

		$match = array();
		
		foreach ($output as $line) {
			if (eregi("^(.*)(Job started.|Job queued.) JobId\=([[:digit:]]+)(.*$)", $line, $match)) {
				$job_id = $match[3];
				break;
			}
		}
		return $job_id;
	}

	/**
	 * Retrieves messages in the bacula queue.
	 *
	 * @returns messages
	 * @throws  EngineException
	 */

	function GetMessages()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$reply = "";
		$handle = $this->Connect();
		if(bacula_command($handle, "messages"))	{
			$messages = bacula_reply($handle);
			if ($messages) {
				foreach($messages as $line)
					$reply .= $line;
			}
		}
		$this->Disconnect($handle);
		return $reply;
	}

	/**
	 * Retrieves status of events related to Bacula daemons.
	 *
	 * @returns  string  status messages
	 * @throws  EngineException
	 */

	function GetStatus()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$reply = "";
		$handle = $this->Connect();

		if(bacula_command($handle, "status all")) {
			$status = bacula_reply($handle);
			if ($status) {
				foreach($status as $line)
					$reply .= $line;
			}
		}
		$this->Disconnect($handle);
		return $reply;
	}

	/**
	 * Issues a command to the Bacula daemon.
	 *
	 * @param  string  $command  Bacula command
	 * @returns messages
	 * @throws  EngineException
	 */

	function IssueCommand($command)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$bnetd = new Bnetd(1800); // 30 minute timeout
		$bnetd->Connect($this->GetDirectorPassword(), $this->GetDirectorAddress());

		// Terminate
		if ($command == 'shutdown' || $command == 'exit') {
			$bnetd->Shutdown();
			return "Connection closed.";
		}
		
		try {
			// Request
			if ($bnetd->SendCommand(Bnetd::CLIENT_COMMAND, $command) == -1)
				throw new EngineException ("Send command failed...");
			
			// Response
			if ($bnetd->RecvResponse($code, $length, $reply) == -1)
				throw new EngineException ("Response failed...");
		} catch (Exception $e) {
			throw new EngineException ($e->getMessage(), COMMON_ERROR);
		}

		return $reply;
	}

	/**
	 * Make a connection with the Bacula director daemon.
	 *
	 * @returns  object  connection handle
	 * @throws  EngineException
	 */

	function Connect()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Load settings if not already done so.
		$this->Load(self::DIR_FILE_CONFIG);

		$handle = bacula_init();

		// Make connection to Bacula director
		// returns 0 on success, -1 on failure
		$rc = bacula_open($handle, $this->connect2daemon["address"], $this->connect2daemon["port"]);
		if ($rc == -1)
			throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_CONNECT, COMMON_ERROR);

		# Set name (not sure what this name is for, it is used for authentication)
		# bacula_set_name(handle, string name)
		# Defaults to "Director daemon"
		bacula_set_name($handle, $this->connect2daemon["name"]);

		# Set timeouts (optional)
		# bacula_set_timeout(handle, int retry_interval, max_retry_time)
		bacula_set_timeout($handle, 2, 1);

		# Authenticate
		# returns 0 on success, -1 on failure
		#$rc = bacula_authenticate($handle, "bad password");
		$rc = bacula_authenticate($handle, $this->connect2daemon["password"]);
		if ($rc == -1)
			throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_AUTHENTICATE, COMMON_ERROR);
		return $handle;
	}

	/**
	 * Kill a connection with the Bacula director daemon.
	 *
	 * @returns  void
	 */

	function Disconnect($handle)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Close connection
		bacula_close($handle);
	}

	/**
	 * Get last 24 hours activity.
	 *
	 * @returns  array  a result set list
	 * @throws  EngineException
	 */

	function GetLast24HoursActivity()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$result_set = Array();
		$link = mysql_connect($this->sql["host"].":" . self::SOCKET_MYSQL, $this->sql["user"], $this->sql["pass"]);
		
		if (!$link)
			return;

		mysql_select_db($this->sql["name"]);
		$result = mysql_query("
			SELECT Job.Name AS JobName, Job.StartTime,
			Job.EndTime, Job.Level, Pool.Name AS PoolName, Job.JobStatus FROM Job
			LEFT JOIN Pool ON Job.PoolId = Pool.PoolId WHERE EndTime <= NOW()
			AND UNIX_TIMESTAMP(EndTime) > UNIX_TIMESTAMP(NOW())-86400 ORDER BY Job.StartTime DESC
		");
		$index = 0;
		if ($result) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$result_set[$index] = $line;
				$index++;
			}
			mysql_free_result($result);
		}
		mysql_close($link);
		return $result_set;
	}

	/**
	 * Get job stats.
	 * @param  string  $end_date  end date
	 * @param  string  $start_date  start date
	 * @param  string  $name  job name
	 *
	 * @returns  array  a list of job statistics
	 * @throws  EngineException
	 */

	function GetJobStats($end_date, $start_date, $name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$result_set = Array();
		$link = mysql_connect($this->sql["host"].":" . self::SOCKET_MYSQL, $this->sql["user"], $this->sql["pass"]);
		if (!$link)
			throw new EngineException (BACULA_LANG_ERRMSG_DB_CONNECT_FAIL, COMMON_ERROR);

		mysql_select_db($this->sql["name"]);
		$result = mysql_query("
			SELECT *, SEC_TO_TIME(UNIX_TIMESTAMP(Job.EndTime)-UNIX_TIMESTAMP(Job.StartTime)) AS Elapsed
			FROM Job WHERE EndTime < '$end_date' AND EndTime > '$start_date' AND Name='$name'
			ORDER BY EndTime;
		");
		$index = 0;
		if ($result) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$result_set[$index] = $line;
				$index++;
			}
			mysql_free_result($result);
		}
		mysql_close($link);
		return $result_set;
	}

	/**
	 * Get information on the job.
	 *
	 * @returns  array  a result set list
	 * @throws  EngineException
	 */

	function GetJobStatus($job_id)
	{
	    if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

	    $result_set = Array();
	    $link = mysql_connect($this->sql["host"].":" . self::SOCKET_MYSQL, $this->sql["user"], $this->sql["pass"]);

	    if (!$link)
	        return;

	    mysql_select_db($this->sql["name"]);
	    $result = mysql_query("
	        SELECT Name, StartTime, Type, Level, JobFiles, JobBytes, JobStatus FROM Job WHERE Job.JobId = $job_id;
	    ");
	    if ($result) {
			$result_set = mysql_fetch_array($result, MYSQL_ASSOC);
	        mysql_free_result($result);
	    }
	    mysql_close($link);
	    return $result_set;
	}

	/**
	 * Database upgrade check.  Earlier versions of Bacula (pre 1.37) require update to the database.
	 *
	 * @returns  void
	 */

	function UpgradeBaculaDatabase()
	{
	    if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$shell = new ShellExec();
		$attr = 
			"--host={$this->sql['host']} " .
			"--user=\"{$this->sql['user']}\" " .
			"--password=\"{$this->sql['pass']}\" " .
			"{$this->sql['name']} " . 
			"-P{$this->sql['port']} " .
			"-f " .
			" < ";


	    $link = mysql_connect($this->sql["host"].":" . self::SOCKET_MYSQL, $this->sql["user"], $this->sql["pass"]);

	    if (!$link)
	        return;

	    mysql_select_db($this->sql["name"]);
	    $result = mysql_query("SELECT * FROM Version");
		# 1.36 -> 1.38.x Upgrade
	    if ($result) {
	        $version = current(mysql_fetch_row($result));
			if ($version < 9) {
				try {
					$retval = $shell->Execute(self::CMD_MYSQL, $attr . self::SCRIPT_UPGRADE_DATABASE_1, false);
					$output = $shell->GetOutput();
					if ($retval != 0) {
						// TODO: upgrade on already upgraded db generates warnings
					} else {
						Logger::Syslog("bacula", "Pre 1.37 Bacula database upgrade successful.");
					}
				} catch (Exception $e) {
					throw new EngineException ($e->getMessage(), COMMON_ERROR);
				}
			}
	        mysql_free_result($result);
	    }
		# 1.38.x -> 2.0.x Upgrade
	    $result = mysql_query("SELECT * FROM Version");
	    if ($result) {
	        $version = current(mysql_fetch_row($result));
			if ($version < 10) {
				try {
					$retval = $shell->Execute(self::CMD_MYSQL, $attr . self::SCRIPT_UPGRADE_DATABASE_2, false);
					$output = $shell->GetOutput();
					if ($retval != 0) {
						// TODO: upgrade on already upgraded db generates warnings
					} else {
						Logger::Syslog("bacula", "Pre 2.0.x Bacula database upgrade successful.");
					}
				} catch (Exception $e) {
					throw new EngineException ($e->getMessage(), COMMON_ERROR);
				}
			}
	        mysql_free_result($result);
	    }
	    mysql_close($link);
	}

	/**
	 * Create a catalog image from bootstrap (BSR) file.
	 *
	 * @param  string  $bootstrap  the bootstrap file
	 * @returns  path/filename of database image
	 * @throws  EngineException
	 */

	function CreateCatalogFromBootstrap ($bootstrap)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Note...job_id will not be required after BSR is entered.
		$job_id = 0;
		$shell = new ShellExec();
		try {
			$param = self::JOB_RESTORE_CATALOG . " " . $job_id . " " . $bootstrap;
            $retval = $shell->Execute(self::CMD_RESTORE_CATALOG, $param, true);
            if ($retval != 0) {
                $output = $shell->GetOutput();
                throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
            }
		} catch (Exception $e) {
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_CATALOG_FILE, COMMON_ERROR);
		}

		# Hardcode warning - I suppose you could grab this from bacula-dir.conf file.
		return self::RESTORE_DEFAULT . "/var/bacula/Catalog.sql";
	}

	/**
	 * Restore catalog.
	 * @param  string  $filesource  the database image of the catalog
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function RestoreCatalog($filesource)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$file = new File($filesource, true);
		if (!$file->Exists())
			throw new EngineException (FILE_LANG_ERRMSG_NOTEXIST . " ($filesource)", COMMON_ERROR);
		$contents = $file->GetContents();

		# Check that file is a MySQL dump
		if (!eregi("^-- MySQL dump.*$", $contents))
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_CATALOG_FILE . " ($filesource)", COMMON_ERROR);

		$param = " -h" . $this->sql["host"] . " -u" . $this->sql["user"] . " -P" . $this->sql["port"];
		if ($this->sql["pass"])
			$param .= " -p" . $this->sql["pass"];
		$retval = 0;
		$shell = new ShellExec();
		try {
			# TODO - Total hack...but 'webconfig' doesn't have access to default restore directory.
			$file->Chmod("0666");
			if (dirname($filesource) != "/tmp") {
				$file->MoveTo("/tmp/");
				$filesource = "/tmp/" . basename($filesource); 
			}

			$retval = $shell->Execute(self::CMD_MYSQLADMIN, $param . " -f drop " . $this->sql["name"]);
	        if ($retval != 0) {
				$output = $shell->GetOutput();
				throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
	        }
			$retval = $shell->Execute(self::CMD_MYSQLADMIN, $param . " create " . $this->sql["name"]);
	        if ($retval != 0) {
				$output = $shell->GetOutput();
				throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
	        }
			$retval = $shell->Execute(self::CMD_MYSQL, "{$param} {$this->sql["name"]} < {$filesource}");
	        if ($retval != 0) {
				$output = $shell->GetOutput();
				throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
	        }
			$file->delete();
		} catch (Exception $e) {
			$file->delete();
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_CATALOG_FILE, COMMON_ERROR);
		}
	}

	/**
	 * Load a configuration file into array.
	 *
	 * @param  string  $filetoload  configuration file
	 * @returns  void
	 * @throws  EngineException
	 */

	function Load($filetoload)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (isset($this->loaded[$filetoload]) && $this->loaded[$filetoload])
			return;

		# Reset our data structures
		$this->loaded[$filetoload] = false;
		$level = 0;

		$file = new File($filetoload);
		if (!$file->Exists())
			throw new EngineException (FILE_LANG_ERRMSG_NOTEXIST . " ($filetoload)", COMMON_ERROR);

		# Load data structures
		$lines = $file->GetContentsAsArray();

		$block1 = Array();
		$block2 = Array();
		$block3 = Array();
		foreach ($lines as $line) {
			if (eregi("^[[:space:]]*[;#]+.*|^[[:space:]]*$", $line)) {
				# Ignore comments
				if ($level == 0) {
					$this->block[$filetoload][] = $line;
				} elseif ($level ==1) {
					$block1[] = $line;
				} elseif ($level ==2) {
					$block2[] = $line;
				} elseif ($level ==3) {
					$block3[] = $line;
				}
			} else if (eregi("^[[:space:]]*([[:alnum:]]*)[[:space:]]\\{.*$", $line)) {
				if ($level == 0) {
					$block1[] = $line;
				} elseif ($level ==1) {
					$block2[] = $line;
				} elseif ($level ==2) {
					$block3[] = $line;
				}
				$level++;
			} else if (eregi("^[[:space:]]*\\}.*$", $line)) {
				if ($level ==1) {
					$block1[] = $line;
					$this->block[$filetoload][] = $block1;
					unset($block1);
				} elseif ($level ==2) {
					$block2[] = $line;
					$block1[] = $block2;
					unset($block2);
				} elseif ($level ==3) {
					$block3[] = $line;
					$block2[] = $block3;
					unset($block3);
				}
				$level--;
			} else {
				if ($level == 0) {
					$this->block[$filetoload][] = $line;
				} elseif ($level ==1) {
					$block1[] = $line;
				} elseif ($level ==2) {
					$block2[] = $line;
				} elseif ($level ==3) {
					$block3[] = $line;
				}
			}
		}
		$this->loaded[$filetoload] = true;

		# Need old name/pswd in case it is updated.
		if ($filetoload == self::DIR_FILE_CONFIG) {
			$this->connect2daemon["name"] = $this->GetDirectorName();
			$this->connect2daemon["password"] = $this->GetDirectorPassword();
			$this->connect2daemon["address"] = $this->GetDirectorAddress();
			$this->connect2daemon["port"] = $this->GetDirectorPort();
		}
	}

	/**
	 * Generic SetKey function to set a key/value pair in a configuration file.
	 *
	 * @param  string  $file  the configuration file
	 * @param  string  $block  Bacula blocks type (ie. Job, FileSet, Schedule etc.)
	 * @param  string  $name  Bacula block names are not unique and are specified by the Name key
	 * @param  string  $key  the key name
	 * @param  string  $value  the key value
	 * @param  boolean  $comment  this is a comment
	 * @returns  boolean  true if the value for the key was set to something different, false otherwise
	 * @throws  EngineException
	 */

	function SetKey($file, $block, $name, $key, $value, $comment = false)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->Load($file);
		$equal_allowed = true;

		# Doesn't exist
		if ($value == self::FLAG_DELETE && !$this->GetKey($file, $block, $name, $key))
			return;
		# Remove any spaces and/or outer quotes
		$value = preg_replace("/^\"|\"$/", "", trim($value));

		if ($value == self::FLAG_DELETE) {
			$value = $value;
		} else if (eregi(self::FIELDS_REQ_NO_QUOTES, preg_replace("/ /", "", $key))) {
			$value = " " . $value;
		} else {
			$value = " \"" . $value . "\"";
		}

		$match = array();

		for ($index = 0; $index < sizeof($this->block[$file]); $index++) {
			$subblock = $this->block[$file][$index];
			if (!is_array($subblock))
				continue;
			# If first element is not the block type we are looking for, continue
			if (!eregi("^[[:space:]]*$block*[[:space:]]\\{.*$", $subblock[0]))
				continue;
			for ($subindex = 1; $subindex < sizeof($subblock); $subindex++) {
				if ($name) {
					# If non-unique
					$needle = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
					if (eregi($needle, $subblock[$subindex], $match)) {
						# Wrong block
						if ($name != preg_replace("/\"/", "",trim($match[2])))
							break;
					}
				}
				$needleWithComment = "^([[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(.*)\#(.*$)";
				$needleWithEqual = "^([[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(.*)=([^=].+)$";
				if ($comment)
					$needle = "^([[:space:]]*\#[[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(.*)";
				else
					$needle = "^([[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(.*)";
				if (eregi($needle, $subblock[$subindex], $match) && $value == self::FLAG_DELETE) {
					$this->block[$file][$index][$subindex] = self::FLAG_DELETE;
					$this->require_restart[$file] = true;
					return;
				} else if (eregi($needleWithComment, $subblock[$subindex], $match)) {
					$this->block[$file][$index][$subindex] = rtrim($match[1]) . $value . " # " . trim($match[3]);
					$this->require_restart[$file] = true;
					return;
				} else if (eregi($needleWithEqual, $subblock[$subindex], $match) && !eregi($key, "RunBeforeClient")) {
					$this->block[$file][$index][$subindex] = rtrim($match[1]) . $value . " = " . trim($match[3]);
					$this->require_restart[$file] = true;
					return;
				} else if (eregi($needle, $subblock[$subindex], $match)) {
					$this->block[$file][$index][$subindex] = rtrim($match[1]) . $value;
					$this->require_restart[$file] = true;
					return;
				} else if ($subindex == sizeof($subblock) - 1) {
					if ($value == self::FLAG_DELETE) {
						$this->require_restart[$file] = true;
						return;
					}
					# Key did not exists.  Add now.
					if ($comment)
						$this->block[$file][$index][$subindex] = "  #  " . $key . " = " . trim($value);
					else
						$this->block[$file][$index][$subindex] = "  " . $key . " = " . trim($value);
					$this->block[$file][$index][$subindex+1] = "}";
					$this->require_restart[$file] = true;
					return;
				}
			}
		}
	}

	/**
	 * Replaces global instances of key/value pairs in all config files.
	 *
	 * @param  string  $key  the key name
	 * @param  string  $value  the old key value
	 * @param  string  $newvalue  the new key value
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetAllKeys($key, $value, $newvalue)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$needleWithComment = "^([[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(($value)|(\"$value\"))\#(.*$)";
		$needleWithEqual = "^([[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(($value)|(\"$value\"))=(.*$)";
		$needle = "^([[:space:]]*" . $key . "[[:space:]]*=[[:space:]]*)(($value)|(\"$value\")).*$";
		# Remove any quotes
		$newvalue = preg_replace("/\"/", "", trim($newvalue));

		if (eregi(self::FIELDS_REQ_NO_QUOTES, preg_replace("/ /", "", $key)))
			$newvalue = " " . $newvalue;
		else
			$newvalue = " \"" . $newvalue . "\"";

		$config_files = Array(
			self::CONSOLE_FILE_CONFIG,
			self::DIR_FILE_CONFIG,
			self::SD_FILE_CONFIG,
			self::FD_FILE_CONFIG
		);

		$match = array();
		
		foreach ($config_files as $file) {
			$this->Load($file);
			for ($index = 0; $index < sizeof($this->block[$file]); $index++) {
				$subblock = $this->block[$file];
				for ($subindex = 0; $subindex < sizeof($subblock[$index]); $subindex++) {
					if (eregi($needleWithComment, $subblock[$index][$subindex], $match))
						$this->block[$file][$index][$subindex] = rtrim($match[1]) . $newvalue . " # " . $match[3];
					else if (eregi($needleWithEqual, $subblock[$index][$subindex], $match))
						$this->block[$file][$index][$subindex] = rtrim($match[1]) . $newvalue . " = " . $match[3];
					else if (eregi($needle, $subblock[$index][$subindex], $match))
						$this->block[$file][$index][$subindex] = rtrim($match[1]) . $newvalue;
				}
			}
			$this->require_restart[$file] = true;
		}
	}

	/**
	 * Generic GetKey function to set a key/value pair in a configuration file.
	 *
	 * @param  string  $file  the configuration file
	 * @param  string  $block  Bacula blocks type (ie. Job, FileSet, Schedule etc.)
	 * @param  string  $name  Bacula block names are not unique and are specified by the Name key
	 * @param  string  $key  the key name
	 * @returns  void
	 * @throws  EngineException
	 */

	function GetKey($file, $block, $name, $key)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Regular expressions
		# -------------------
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$found_block = false;
		$found_name = false;
		$match = array();
		
		$this->Load($file);

		for ($index = 0; $index < sizeof($this->block[$file]); $index++) {
			$subblock = $this->block[$file];
			if (!is_array($subblock[$index]))
				continue;
			for ($subindex = 0; $subindex < sizeof($subblock[$index]); $subindex++) {
				if (is_array($subblock[$index][$subindex]))
					continue;
				if (eregi("^[[:space:]]*$block*[[:space:]]\\{.*$", $subblock[$index][$subindex]) || $found_block) {
					$found_block = true;
					if ($name) {
						# If non-unique
						if (eregi($regex_name, $subblock[$index][$subindex], $match)) {
							# Wrong block
							if ($name != preg_replace("/\"/", "", trim($match[2]))) {
								$found_block = false;
								break;
							} else {
								$found_name = true;
							}
						}
					} else {
						$found_name = true;
					}
				}
				if ($found_block && $found_name) {
					# Check for JobDefs
					if (eregi("^[[:space:]]*(JobDefs)[[:space:]]*=[[:space:]]*(.*$)",$subblock[$index][$subindex], $match)) {
						$jobdefs = trim($match[2]);
						# Check for comments
						$jobdefs = preg_replace("/#.*/", "", $jobdefs);
						# Check for quotations
						$jobdefs = preg_replace("/\"/", "", $jobdefs);
					}
					if (eregi("^[[:space:]]*($key)[[:space:]]*=[[:space:]]*(.*$)",$subblock[$index][$subindex], $match)) {
						$value = trim($match[2]);
						# Check for comments
						$value = trim(preg_replace("/#.*/", "", $value));
						# Check for outer quotations
						$value = preg_replace("/^\"|\"$/", "", $value);
						return trim($value);
					}
				}
			}
		}

		# If you get here, no key was found.  For Jobs, check the JobDefs section.
		if ($file == self::DIR_FILE_CONFIG && $block == "Job") {
			$found_block = false;
			$found_name = false;
			if ($jobdefs)
				return $this->GetKey($file, "JobDefs", $jobdefs, $key);
		}
	}

	/**
	 * Get block.
	 * @param  string  $file  the configuration file
	 * @param  string  $block  Bacula blocks type (ie. Job, FileSet, Schedule etc.)
	 * @param  string  $name  Bacula block names are not unique and are specified by the Name key
	 *
	 * @returns array
	 * @throws  EngineException
	 */

	function GetBlock($file, $block, $name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$return_block = Array();
		# Regular expressions
		# -------------------
		$regex_block = "^[[:space:]]*($block)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";

		$this->Load($file);

		$match = array();
		
		for ($index = 0; $index < sizeof($this->block[$file]); $index++) {
			if (!is_array($this->block[$file][$index]))
				continue;
			if (eregi($regex_block, $this->block[$file][$index][0])) {
				for ($subindex = 0; $subindex < sizeof($this->block[$file][$index]); $subindex++) {
					if (eregi($regex_name, $this->block[$file][$index][$subindex], $match)) {
						if ($name == preg_replace("/\"/", "", trim($match[2])))
							return $this->block[$file][$index];
					}
				}
			}
		}
		return $return_block;
	}

	/**
	 * Set block.
	 * @param  string  $file  the configuration file
	 * @param  string  $block  Bacula blocks type (ie. Job, FileSet, Schedule etc.)
	 * @param  string  $name  Bacula block names are not unique and are specified by the Name key
	 * @param  string  $replace  the replacement block
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetBlock($file, $block, $name, $replace)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Regular expressions
		# -------------------
		$regex_block = "^[[:space:]]*($block)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$match = array();
		
		$this->Load($file);

		for ($index = 0; $index < sizeof($this->block[$file]); $index++) {
			if (!is_array($this->block[$file][$index]))
				continue;
			if (eregi($regex_block, $this->block[$file][$index][0])) {
				for ($subindex = 0; $subindex < sizeof($this->block[$file][$index]); $subindex++) {
					if (eregi($regex_name, $this->block[$file][$index][$subindex], $match)) {
						if ($name == trim(preg_replace("/\"/", "", $match[2]))) {
							$this->block[$file][$index] = $replace;
							return;
						}
					}
				}
			}
		}
	}

	/**
	 * Add block to director conf.
	 * @param  string  $directive  content
	 *
	 * @returns  void
	 * @throws  EngineException
	 */
	function AddDirectiveToDir($directive)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$this->Load(self::DIR_FILE_CONFIG);
		$this->block[self::DIR_FILE_CONFIG][] = "";
		$this->block[self::DIR_FILE_CONFIG][] = $directive;
	}

	/**
	 * Delete block.
	 * @param  string  $file  the configuration file
	 * @param  string  $block  Bacula blocks type (ie. Job, FileSet, Schedule etc.)
	 * @param  string  $name  Bacula block names are not unique and are specified by the Name key
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteBlock($file, $block, $name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$name = preg_replace("/^\"|\"$/", "", trim($name));
		$found_block = false;
		$found_name = false;

		# Regular expressions
		# -------------------
		$regex_block = "^[[:space:]]*($block)[[:space:]]*{[[:space:]]*(.*$)";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";

		$this->Load($file);

		for ($index = 0; $index < sizeof($this->block[$file]); $index++) {
			$subblock = $this->block[$file];
			if (!is_array($subblock[$index]))
				continue;
			for ($subindex = 0; $subindex < sizeof($subblock[$index]); $subindex++) {
				if (eregi("^[[:space:]]*$block*[[:space:]]\\{.*$", $subblock[$index][$subindex]) || $found_block) {
					$found_block = true;
					# If non-unique
					$match = array();
					if (eregi($regex_name, $subblock[$index][$subindex], $match)) {
						# Wrong block
						if ($name != $this->RemoveComments($match[2])) {
							$found_block = false;
							break;
						} else {
							$found_name = true;
						}
					}

					if ($found_block && $found_name) {
						$this->DeleteBlockByIndex($file, $index);
						return;
					}
				}
			}
		}
	}

	/**
	 * Delete block.
	 * @param  string  $file  the config file
	 * @param  string  $index  the array index
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteBlockByIndex($file, $index)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->Load($file);

		$this->block[$file][$index] = self::FLAG_DELETE;

	}

	/**
	 * Delete job defs entry.
	 * @param  string  $name  the name of the JobDefs resource
	 * @param  string  $resource  the resource type
	 * @param  string  $resource_name  the name of the resource
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function DeleteJobDefsEntry($name, $resource, $resource_name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$found_block = false;
		$found_name = false;

		# Regular expressions
		# -------------------
		$regex_pair = "^[[:space:]]*($resource)[[:space:]]*=[[:space:]]*($resource_name)|(\"$resource_name\")(.*)";
		$regex_name = "^[[:space:]]*(Name)[[:space:]]*=[[:space:]]*(.*$)";
		$match = array();
		
		$this->Load(self::DIR_FILE_CONFIG);

		for ($index = 0; $index < sizeof($this->block[self::DIR_FILE_CONFIG]); $index++) {
			$subblock = $this->block[self::DIR_FILE_CONFIG];
			if (!is_array($subblock[$index]))
				continue;
			for ($subindex = 0; $subindex < sizeof($subblock[$index]); $subindex++) {
				if (eregi("^[[:space:]]*JobDefs*[[:space:]]\\{.*$", $subblock[$index][$subindex]) || $found_block) {
					$found_block = true;
					# If non-unique
					if (eregi($regex_name, $subblock[$index][$subindex], $match)) {
						# Wrong block
						if ($name != $this->RemoveComments($match[2])) {
							$found_block = false;
							break;
						} else {
							$found_name = true;
						}
					}

					if ($found_block && $found_name) {
						if (eregi($regex_pair, $subblock[$index][$subindex], $match)) {
							$this->block[self::DIR_FILE_CONFIG][$index][$subindex] = self::FLAG_DELETE;
							return;
						}
					}
				}
			}
		}
	}

	/**
	 * Inserts a new block into a configuration file.
	 * @param  string  $file  the configuration file
	 * @param  string  $data  the block
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function InsertBlock($file, $data)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->Load($file);

		# Add spacer
		$this->block[$file][] = "";
		# Add block
		$this->block[$file][] = $data;
	}

	/**
	 * Commits any changes configuration files that have been opened.
	 * @param  boolean  $override_restart  Override the daemon restart behavior
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function Commit($override_restart = false)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$this->block)
			return;

		foreach ($this->block as $filename => $content) {
			$this->flatfile = "";
			$this->FlatFile($content);

			$newfile = new File($filename . ".cctmp");
			try {
				# Delete any old temp file lying around
				if ($newfile->Exists())
					$newfile->Delete();
				$newfile->Create("root", "root", "0600");
				# Write out the file
				$newfile->AddLines($this->flatfile);
			} catch (Exception $e) {
				throw new EngineException ($e->getMessage(), COMMON_ERROR);
			}

			// Validate new file
			// -----------------

			$retval = 0;
			$shell = new ShellExec();
			try {
				if ($filename == self::DIR_FILE_CONFIG) {
					$retval = $shell->Execute(self::CMD_DIR, "-t -c " . $filename. ".cctmp", true);
				} else if ($filename == self::CONSOLE_FILE_CONFIG) {
					$retval = $shell->Execute(self::CMD_BCONSOLE, "-t -c " . $filename. ".cctmp", true);
				} else if ($filename == self::FD_FILE_CONFIG) {
					$retval = $shell->Execute(self::CMD_FD, "-t -c " . $filename. ".cctmp", true);
				} else if ($filename == self::SD_FILE_CONFIG) {
					$retval = $shell->Execute(self::CMD_SD, "-t -c " . $filename. ".cctmp", true);
				}
				if ($retval != 0) {
					$output = $shell->GetOutput();
					foreach ($output as $line)
						$msg .= $line;
					// Hopefully, this is never shown.
					throw new EngineException ($msg, COMMON_ERROR);
				}
			} catch (Exception $e) {
				throw new EngineException ($e->getMessage(), COMMON_ERROR);
			}

			if ($retval != 0) {
				$output = $shell->GetOutput();
				throw new EngineException (BACULA_LANG_ERRMSG_PARSE_ERROR . " " . $filename, COMMON_ERROR);
			}
			# Copy the new config over the old config

			$newfile->MoveTo($filename);
		}

		# Reload
		$handle = $this->Connect();
		bacula_command($handle, "reload");
		$this->Disconnect($handle);

		if ($override_restart) {
			unset($this->block);
			clearstatcache();
			$this->loaded = false;
			return;
		}

		# TODO Get session out of here
		if (!empty($this->require_restart)) {
			if (isset($this->require_restart[self::CONSOLE_FILE_CONFIG]))
				$_SESSION['bacula_restart']['bacula-dir'] = "true";
			if (isset($this->require_restart[self::DIR_FILE_CONFIG]))
				$_SESSION['bacula_restart']['bacula-dir'] = "true";
			if (isset($this->require_restart[self::SD_FILE_CONFIG]))
				$_SESSION['bacula_restart']['bacula-sd'] = "true";
			if (isset($this->require_restart[self::FD_FILE_CONFIG]))
				$_SESSION['bacula_restart']['bacula-fd'] = "true";
		}

		# Send admin new config files, if set.
		if ($this->GetDirectorEmailOnEdit())
			$this->SendAdminConfig(1, 1, 1);

		# Force lookup of file next time around.
		unset($this->block);
		clearstatcache();
		$this->loaded = false;
	}

	/**
	 * Sends a copy of the current configuration files to admin email.
	 * @param  string  $dir  flag for director/console config files
	 * @param  string  $fd  flag for file daemon config files
	 * @param  string  $sd  flag for storage daemon config files
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function SendAdminConfig($dir, $fd, $sd)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$mailer = new Mailer();
		$attachments = Array();

		try {
			# bconsole.conf and bacula-dir.conf
			if ($dir) {
				$file = new File(self::CONSOLE_FILE_CONFIG, true);
				$attachments[] = Array(
					'data' => $file->GetContents(),
					'filename' => basename($file->GetFilename()),
					'type' => self::TYPE_OCTET,
					'encoding' => self::ENCODING_7BIT
				);
				$file = new File(self::DIR_FILE_CONFIG, true);
				$attachments[] = Array(
					'data' => $file->GetContents(),
					'filename' => basename($file->GetFilename()),
					'type' => self::TYPE_OCTET,
					'encoding' => self::ENCODING_7BIT
				);
			}
			# bacula-fd.conf
			if ($fd) {
				$file = new File(self::FD_FILE_CONFIG, true);
				$attachments[] = Array(
					'data' => $file->GetContents(),
					'filename' => basename($file->GetFilename()),
					'type' => self::TYPE_OCTET,
					'encoding' => self::ENCODING_7BIT
				);
			}
			# bacula-sd.conf
			if ($sd) {
				$file = new File(self::SD_FILE_CONFIG, true);
				$attachments[] = Array(
					'data' => $file->GetContents(),
					'filename' => basename($file->GetFilename()),
					'type' => self::TYPE_OCTET,
					'encoding' => self::ENCODING_7BIT
				);
			}

			$mailer->AddRecipient($this->GetDirectorAdminEmail());
			$mailer->SetAttachments($attachments);
			$hostname = new Hostname();
			$mailer->SetSubject(BACULA_LANG_SUBJECT_CONFIG . " - " . $hostname->Get());
			$mailer->SetSender("bacula@" . $hostname->Get());
			$mailer->Send();
			
		} catch (Exception $e) {
	        self::Log(COMMON_ERROR, $e->getMessage(), __METHOD__, __LINE__);
	        throw new EngineException ($e->getMessage(), COMMON_ERROR);
	    }
	}

	/**
	 * Sends a copy of the bootstrap file to admin email.
	 * @param  string  $filename  the BSR filename
	 * @param  string  $job  the job name
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function SendAdminBsr($filename, $job)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$mailer = new Mailer();
		$attachments = Array();

		# Make sure filename exists
		$file = new File($filename, true);
		if (! $file->Exists())
			throw new FileNotFoundException($filename, COMMON_ERROR); 
		#$file->AddLine("\n");
		try {
			$attachments[] = Array(
				'data' => $file->GetContents(),
				'filename' => basename($file->GetFilename()),
				'type' => self::TYPE_OCTET,
				'encoding' => self::ENCODING_7BIT
			);
			$hostname = new Hostname();
			$mailer->AddRecipient($this->GetDirectorAdminEmail());
			$mailer->SetAttachments($attachments);
			$hostname = new Hostname();
			$mailer->SetSubject(BACULA_LANG_SUBJECT_BSR . ", " . $hostname->Get() . " - " . $job);
			$mailer->SetBody(BACULA_LANG_BSR_WARNING);
			$mailer->SetSender("bacula@" . $hostname->Get());
			$mailer->Send();
		} catch (Exception $e) {
	        throw new EngineException ($e->getMessage(), COMMON_ERROR);
	    }
	}

	/**
	 * Replaces a configuration file and backs up the old one.
	 * @param  string  $file  a configuration filename
	 *
	 * @returns  void
	 */

	function ReplaceConfig($filename)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$existing_file = new File(self::BACULA_ETC . $filename, true);
		$new_file = new File("/tmp/" . $filename, true);
		try {
			if (!$existing_file->Exists() || !$new_file->Exists()) {
				throw new EngineException (BACULA_LANG_ERRMSG_INVALID_FILE, COMMON_ERROR);
			}

			# Backup the current config
			$new_file->CopyTo(self::BACULA_ETC . $filename . "." . date("F-j\_H-i"));

			# Move new config in place of current one
			$new_file->MoveTo(self::BACULA_ETC . $filename);
	    } catch (Exception $e) {
	        throw new EngineException ($e->getMessage(), COMMON_ERROR);
	    }
	}

	/**
	 * Restart all Bacula daemons (not MySQL).
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function RestartRequiredDaemons($list)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$daemons = array_keys($list);
		foreach ($daemons as $daemon) {
			$daemonclass = new Daemon($daemon);
			$daemonclass->Restart();
		}
		unset($_SESSION['bacula_restart']);
	}

	/**
	 * Restart all Bacula daemons (not MySQL).
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function RestartAllDaemons()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$daemon = new Daemon("bacula-dir");
		$daemon->Restart();
		$daemon = new Daemon("bacula-sd");
		$daemon->Restart();
		$daemon = new Daemon("bacula-fd");
		$daemon->Restart();
		unset($_SESSION['bacula_restart']);
	}

	/**
	 * Start all daemons (including MySQL).
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function StartAllDaemons()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$daemon = new Daemon("bacula-mysqld");
		$daemon->SetRunningState(true);
		$this->UpgradeBaculaDatabase();
		$daemon = new Daemon("bacula-dir");
		$daemon->SetRunningState(true);
		$daemon = new Daemon("bacula-sd");
		$daemon->SetRunningState(true);
		$daemon = new Daemon("bacula-fd");
		$daemon->SetRunningState(true);
	}

	/**
	 * Array to flat file converter.
	 * @param  string  $content  content for file
	 *
	 * @returns  void
	 */

	function FlatFile($content)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		foreach ($content as $line) {
			# We insert flags to delete during the commital, in order to keep the array sizes the same.
			if ($line == self::FLAG_DELETE)
				continue;
			if (is_array($line))
				$this->FlatFile($line);
			else
				$this->flatfile .= $line . "\n";
		}
	}

	/**
	 * Remove any comments from a key/value pair.
	 * @param  string  initial  text to convert
	 *
	 * @returns string or array, depending on what was sent
	 */

	function RemoveComments($initial)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$return_array = Array();
		if (!$initial)
			return $return_array;
		$index = 0;
		if (!is_array($initial)) {
			if (eregi("^[[:space:]]*[;#]+.*|^[[:space:]]*$", $initial))
				return $initial;
			# Check for comments
			$initial = preg_replace("/#.*/", "", $initial);
			# Check for quotations
			$initial = preg_replace("/\"/", "", $initial);
			# Check for semi-colon
			$initial = preg_replace("/\;/", "", $initial);
			return trim($initial);
		} else {
			foreach ($initial as $line) {
				if (eregi("^[[:space:]]*[;#]+.*|^[[:space:]]*$", $line))
					continue;
				# Check for comments
				$line = preg_replace("/#.*/", "", $line);
				# Check for quotations
				$line = preg_replace("/\"/", "", $line);
				# Check for semi-colon
				$line = preg_replace("/\;/", "", $line);
				$return_array[$index] = trim($line);
				$index++;
			}
			return $return_array;
		}
	}

	/**
	 * Returns an array of valid time units for Bacula configuration files.
	 *
	 * @returns  array a list of time units
	 */

	function GetTimeUnits()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"minutes" => LOCALE_LANG_MINUTES,
			"hours" => LOCALE_LANG_HOURS,
			"days" => LOCALE_LANG_DAYS,
			"months" => LOCALE_LANG_MONTHS
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid job types.
	 *
	 * @returns  array  a list of valid job types
	 */

	function GetJobTypes($job)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Prevent multiple "Restore" job types from being created.
		if ($this->GetJobType($job) == "Restore") {
			$pre_defined = Array (
				"Backup" => BACULA_LANG_JOB_BACKUP,
				"Restore" => BACULA_LANG_JOB_RESTORE,
				"Verify" => LOCALE_LANG_VERIFY,
				"Admin" => BACULA_LANG_JOB_ADMIN
			);
		} else {
			$pre_defined = Array (
				"Backup" => BACULA_LANG_JOB_BACKUP,
				"Verify" => LOCALE_LANG_VERIFY,
				"Admin" => BACULA_LANG_JOB_ADMIN
			);
		}
		return $pre_defined;
	}

	/**
	 * Returns an array of valid backup levels.
	 *
	 * @returns  array  a list of backup levels
	 */

	function GetLevelOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"Full" => BACULA_LANG_FULL,
			"Differential" => BACULA_LANG_DIFFERENTIAL,
			"Incremental" => BACULA_LANG_INCREMENTAL
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid schedule qualifiers.
	 *
	 * @returns  array  a list of Bacula defined time variables
	 */

	function GetDateOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"hourly" => LOCALE_LANG_HOURLY,
			"daily" => LOCALE_LANG_DAILY,
			"weekly" => LOCALE_LANG_WEEKLY,
			"monthly" => LOCALE_LANG_MONTHLY,
			"mon" => BACULA_LANG_SCHEDULE_MON,
			"tue" => BACULA_LANG_SCHEDULE_TUE,
			"wed" => BACULA_LANG_SCHEDULE_WED,
			"thu" => BACULA_LANG_SCHEDULE_THU,
			"fri" => BACULA_LANG_SCHEDULE_FRI,
			"sat" => BACULA_LANG_SCHEDULE_SAT,
			"sun" => BACULA_LANG_SCHEDULE_SUN,
			"1st fri" => BACULA_LANG_SCHEDULE_1ST_FRI,
			"2nd-5th fri" => BACULA_LANG_SCHEDULE_NOT_1ST_FRI,
			"1st sat" => BACULA_LANG_SCHEDULE_1ST_SAT,
			"2nd-5th sat" => BACULA_LANG_SCHEDULE_NOT_1ST_SAT,
			"1st sun" => BACULA_LANG_SCHEDULE_1ST_SUN,
			"2nd-5th sun" => BACULA_LANG_SCHEDULE_NOT_1ST_SUN,
			"on 1" => BACULA_LANG_SCHEDULE_FIRST_OF_MONTH,
			"on 2-31" => BACULA_LANG_SCHEDULE_EVERY_DAY_NOT_1ST,
			"Daily mon-fri" => BACULA_LANG_SCHEDULE_DAILY_MON_FRI,
			"Daily mon-sat" => BACULA_LANG_SCHEDULE_DAILY_MON_SAT
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of hourly options using 24hr format.
	 *
	 * @returns  array  a list of hour (time) options
	 */

	function GetHourOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"00" => "00", "01" => "01", "02" => "02", "03" => "03", "04" => "04", "05" => "05",
			"06" => "06", "07" => "07", "08" => "08", "09" => "09", "10" => "10", "11" => "11",
			"12" => "12", "13" => "13", "14" => "14", "15" => "15", "16" => "16", "17" => "17",
			"18" => "18", "19" => "19", "20" => "20", "21" => "21", "22" => "22", "23" => "23"
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of minute options.
	 *
	 * @returns  array  a list of minute (time) options
	 */

	function GetMinuteOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"00" => "00", "01" => "01", "02" => "02", "03" => "03", "04" => "04", "05" => "05",
			"06" => "06", "07" => "07", "08" => "08", "09" => "09", "10" => "10", "11" => "11",
			"12" => "12", "13" => "13", "14" => "14", "15" => "15", "16" => "16", "17" => "17",
			"18" => "18", "19" => "19", "20" => "20", "21" => "21", "22" => "22", "23" => "23",
			"24" => "24", "25" => "25", "26" => "26", "27" => "27", "28" => "28", "29" => "29",
			"30" => "30", "31" => "31", "32" => "32", "33" => "33", "34" => "34", "35" => "35",
			"36" => "36", "37" => "37", "38" => "38", "39" => "39", "40" => "40", "41" => "41",
			"42" => "42", "43" => "43", "44" => "44", "45" => "45", "46" => "46", "47" => "47",
			"48" => "48", "49" => "49", "50" => "50", "51" => "51", "52" => "52", "53" => "53",
			"54" => "54", "55" => "55", "56" => "56", "57" => "57", "58" => "58", "59" => "59"
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid priority values for jobs.
	 *
	 * @returns  array  a list of priority options
	 */

	function GetPriorityOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5",
			"6" => "6", "7" => "7", "8" => "8", "9" => "9", "10" => "10",
			"11" => "11", "12" => "12", "13" => "13", "14" => "14", "15" => "15",
			"16" => "16", "17" => "17", "18" => "18", "19" => "19", "20" => "20"
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid pool types.
	 *
	 * @returns  array  a list of pool type options
	 */

	function GetPoolTypeOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"Backup" => BACULA_LANG_JOB_BACKUP
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid storage unit values.
	 *
	 * @returns  array  a list of prefixes for storage unit display
	 */

	function GetStorageUnits()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"Bytes" => "Bytes", "KB" => "KB", "MB" => "MB", "GB" => "GB"
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of supported database types.
	 *
	 * @returns  array  a list of supported databases that can be backed-up
	 */

	function GetSupportedDatabases()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"mysql" => "MySQL", "pgsql" => "PostgreSQL"
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of signature types.
	 *
	 * @returns  array  a list of file signature options
	 */

	function GetSignatures()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"NONE" => BACULA_LANG_NONE, "MD5" => "MD5", "SHA1" => "SHA1"
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid replace actions.
	 *
	 * @returns  array  a list of replace options
	 */

	function GetReplaceOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"always" => BACULA_LANG_REPLACE_ALWAYS,
			"ifnewer" => BACULA_LANG_REPLACE_IFNEWER,
			"ifolder" => BACULA_LANG_REPLACE_IFOLDER,
			"never" => BACULA_LANG_REPLACE_NEVER
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid device actions.
	 *
	 * @returns  array  a list of device actions
	 */

	function GetDeviceActionOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"mount" => BACULA_LANG_MOUNT,
			"umount" => BACULA_LANG_UMOUNT,
			"umount_eject" => BACULA_LANG_UMOUNT_EJECT,
			"eject" => BACULA_LANG_EJECT,
			"label" => BACULA_LANG_LABEL
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid device media types.
	 *
	 * @returns  array  a list of valid device media types for storage
	 */

	function GetDeviceMediaTypeOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			self::MEDIA_FILE => self::MEDIA_FILE,
			self::MEDIA_IOMEGA => self::MEDIA_IOMEGA,
			self::MEDIA_DVD => self::MEDIA_DVD,
			self::MEDIA_USB => self::MEDIA_USB . " " . BACULA_LANG_MSD,
			self::MEDIA_SMB => self::MEDIA_SMB . " " . BACULA_LANG_MOUNTPOINT
		);
		return $pre_defined;
	}

	/**
	 * Returns an array of valid operating systems for basic fileset creation.
	 *
	 * @returns  array  a list of operating systems
	 */

	function GetBasicFilesetOsOptions()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pre_defined = Array (
			"Windows-98" => "Windows 98",
			"Windows-2000" => "Windows 2000",
			"Windows-XP" => "Windows XP"
		);
		return $pre_defined;
	}

	/******************************
	*     V A L I D A T I O N     *
	******************************/

	/**
	 * Validation routine for a name resource.
	 *
	 * @param  string  $name  name
	 * @return  boolean  true if name is valid
	 */

	function IsValidName($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\ \_]+)$/", $name))
			return true;

		$this->AddValidationError(BACULA_LANG_ERRMSG_INVALID_NAME, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for pool name.
	 *
	 * @param  string  password  password
	 * @return  boolean  true if password is valid
	 */

	function IsValidPassword($password)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([\w\-\%\$\&\!\(\)\_]+)$/", $password))
			return true;

		$this->AddValidationError(BACULA_LANG_ERRMSG_INVALID_PASSWORD, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for email.
	 *
	 * @param  string  $email  e-mail address
	 * @return  boolean  true if e-mail is valid
	 */

	function IsValidEmail($email)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([\-\w\.]+@[\-\w\.]+)$/", $email))
			return true;

		$this->AddValidationError(BACULA_LANG_ERRMSG_INVALID_EMAIL . " ($email)", __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for mailserver.
	 *
	 * @param  $string  mailserver  mailserver address
	 * @return  boolean  true if mailserver is valid
	 */

	function IsValidMailserver($mailserver)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($mailserver)
			return true;

		$this->AddValidationError(BACULA_LANG_ERRMSG_INVALID_MAIL_SERVER . " ($mailserver)", __METHOD__, __LINE__);

		return false;
	}

	/**
	 * Validation routine for an integer value.
	 *
	 * @param  $object  $value  value
	 * @return  boolean  true if value is an integer
	 */

	function IsValidInteger($value)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! preg_match("/^\d+$/", $value))
			return false;
		else
			return true;
	}

	/**
	 * Check for webconfig database flag.
	 *
	 * @param  string  $name  fileset name
	 * @return  boolean  true if fileset is a database
	 * @throws  EngineException 
	 */

	function IsFilesetDatabase($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $name);

		# If no name match, return
		if (sizeof($block) == 0)
			return false;

		foreach($block as $line) {
			if (eregi(self::FLAG_FILESET_DB, $line))
				return true;
		}
		return false;
	}

	/**
	 * Is the job restricted.
	 *
	 * @param  string  $name  job name
	 * @return  integer - see IsRestrictedAccess()
	 * @throws  EngineException 
	 */

	function IsJobRestricted($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "Job", $name);
		return $this->IsRestrictedAccess($block);
	}

	/**
	 * Is the client restricted.
	 *
	 * @param  string  $name  Client name
	 * @return  integer - see IsRestrictedAccess()
	 * @throws  EngineException 
	 */

	function IsClientRestricted($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "Client", $name);
		return $this->IsRestrictedAccess($block);
	}

	/**
	 * Is the fileset restricted.
	 *
	 * @param  string  $name  Fileset name
	 * @return  integer - see IsRestrictedAccess()
	 * @throws  EngineException 
	 */

	function IsFilesetRestricted($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "FileSet", $name);
		return $this->IsRestrictedAccess($block);
	}

	/**
	 * Is the pool restricted.
	 *
	 * @param  string  $name  Pool name
	 * @return  integer - see IsRestrictedAccess()
	 * @throws  EngineException 
	 */

	function IsPoolRestricted($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "Pool", $name);
		return $this->IsRestrictedAccess($block);
	}

	/**
	 * Is the storage device restricted.
	 *
	 * @param  string  $name  Storage device name
	 * @return  integer - see IsRestrictedAccess()
	 * @throws  EngineException 
	 */

	function IsSdRestricted($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "Storage", $name);
		return $this->IsRestrictedAccess($block);
	}

	/**
	 * Is the schedule restricted.
	 *
	 * @param  string  $name  schedule name
	 * @return  integer - see IsRestrictedAccess()
	 * @throws  EngineException 
	 */

	function IsScheduleRestricted($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$block = $this->GetBlock(self::DIR_FILE_CONFIG, "Schedule", $name);
		return $this->IsRestrictedAccess($block);
	}

	/**
	 * Mounts a device at the specified mount point.
	 * @param  string  $name  device name
	 * @param  string  $mountpoint  mountpoint
	 *
	 * @returns  void
	 * @throws  EngineException 
	 */

	function DeviceMount($device, $mountpoint)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$handle = $this->Connect();
		if (bacula_command($handle, "mount \"$device\""))
			$messages = bacula_reply($handle);
		$this->Disconnect($handle);
	}

	/**
	 * Umounts a device.
	 * @param  string  $name  device name
	 *
	 * @returns  void
	 * @throws  EngineException 
	 */

	function DeviceUmount($device)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$handle = $this->Connect();

		if (bacula_command($handle, "umount \"$device\""))
			$messages = bacula_reply($handle);
		$this->Disconnect($handle);
	}

	/**
	 * Labels a device.
	 * @param  string  $name  device name
	 * @param  string  $pool  pool name
	 * @param  string  $label  label
	 *
	 * @returns  void
	 * @throws  EngineException 
	 */

	function DeviceLabel($device, $pool, $label)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$shell = new ShellExec();
		try {
	        $code = $shell->Execute(self::CMD_LABEL_MEDIA, " \"" . $device . "\" \"" . $pool . "\" \"" . $label. "\"", true);
	        if ($code != 0) {
				$output = $shell->GetOutput();
	            throw new EngineException (BACULA_LANG_ERRMSG_LABEL_FAILED, COMMON_ERROR);
	        }
	    } catch (Exception $e) {
	        throw new EngineException ($e->getMessage(), COMMON_ERROR);
	    }

		$output = $shell->GetOutput();
		foreach ($output as $line) {
			if (eregi("3000 OK label", $line)) {
				return;
			} else if (eregi("^3910", $line)) {
	            throw new EngineException (BACULA_LANG_ERRMSG_NOT_MOUNTED, COMMON_ERROR);
			} else if (eregi("already exists", $line)) {
	            throw new EngineException (BACULA_LANG_ERRMSG_VOLUME_EXISTS, COMMON_ERROR);
			}
		}

		throw new EngineException (BACULA_LANG_ERRMSG_LABEL_FAILED, COMMON_ERROR);
	}

	/**
	 * Ejects media from a removable media device.
	 * @param  string  $name  device name
	 *
	 * @returns  void
	 * @throws  EngineException 
	 */

	function DeviceEject($device)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Need media type
		$media_type = $this->GetSdMediaType($device);

		if (eregi("^" . self::MEDIA_FILE, $media_type)) {
			throw new EngineException(BACULA_LANG_ERRMSG_OPERATION_NOT_SUPPORTED, COMMON_ERROR);
		} else {
            $shell = new ShellExec();
            # We don't care if this fails
            try {
				if (eregi("^" . self::MEDIA_DVD, $media_type)) {
					$mount = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $device);
            		$shell->Execute(self::CMD_UMOUNT, $mount, true);
				}
            } catch (Exception $e) {
            	// self::Log(COMMON_ERROR, $e->getMessage(), __METHOD__, __LINE__);
            }

			try {
				$dev = $this->GetKey(self::SD_FILE_CONFIG, "Device", $device, "ArchiveDevice");
				$exitcode = $shell->Execute(self::CMD_EJECT, $dev, true);
				if ($exitcode == 0) {
					return;
				} else {
					$output = $shell->GetOutput();
					throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
				}
			} catch (Exception $e) {
				throw new EngineException ($e->getMessage(), COMMON_ERROR);
			}
		}
	}

	/**
	 * Looks for a no edit/delete flag in block.
	 * @access private
	 *
	 * @returns integer
	 *   0 = No restrictions
	 *   1 = No edits
	 *   2 = No deletes
	 *   3 = No edits or deletes
	 */

	function IsRestrictedAccess($block)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$code = 0;
		# If no name match, return
		if (sizeof($block) == 0)
			return $code;

		foreach($block as $line) {
			if (eregi(self::FLAG_NO_EDIT, $line))
				$code = $code + 1;
			elseif (eregi(self::FLAG_NO_DELETE, $line))
				$code = $code + 2;
		}
		return $code;
	}

	/**
	 * Formats a value into a human readable byte size.
	 * @param  float  $input  the value
	 * @param  int  $dec  number of decimal places
	 *
	 * @returns  string  the byte size suitable for display to end user
	 */

	function GetFormattedBytes($input, $dec)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$prefix_arr = array(" B", "KB", "MB", "GB", "TB");
		$value = round($input, $dec);
		$i=0;
		while ($value>1024) {
			$value /= 1024;
			$i++;
		}
		$display = round($value, $dec) . " " . $prefix_arr[$i];
		return $display;
	}

	/**
	 * Returns the byte unit an multiplier.
	 * @param  float  $data  the sample data
	 *
	 * @returns  string  the value and unit
	 */

	function GetUnitAndMultiplier($key, $data)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Bail on no data.
		if (sizeof($data) == 0)
			return;

		$new_array = array();
		foreach ($data as $point)
			$new_array[] = $point[$key];
		$prefix_arr = array("B", "KB", "MB", "GB", "TB");
		$i=0;
		$multiplier = 1;
		rsort($new_array);
		$highest = $new_array[0];
		while ($highest>1024) {
			$highest /= 1024;
			$multiplier *= 1024;
			$i++;
		}
		$unit_info = array($multiplier, $prefix_arr[$i]);
		return $unit_info;
	}

	/**
	 * Returns the physical device location (ie. /dev/&ls;something&gt;) of an storage device.
	 * @param  string  $name  device name
	 *
	 * @returns  string  the device location
	 * throws  EngineException
	 */

	function GetDeviceLocation($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$name)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_DEVICE, COMMON_ERROR);

		$devices = new StorageDevice();
		foreach ($devices->GetDevices() as $dev => $info) {
			$device = preg_replace("/\s+|\\.|\\,/", "_", $info['vendor'] . $info['model']);
			if (eregi($name, $device))
				return $dev;
		}

		throw new EngineException (BACULA_LANG_ERRMSG_INVALID_DEVICE, COMMON_ERROR);
	}

	/**
	 * Creates a database dump script.
	 * @param  string  $name  database name
	 *
	 * @returns  void
	 * throws  EngineException
	 */

	function GetDatabaseProperties($name)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$db = Array();
		try {
			$file = new File(self::BACULA_USR . self::SCRIPT_BACKUP_PREFIX . $name . ".sh");
			$contents = $file->GetContents();

			$lines = explode("\n", $contents);

			$match = array();
			
			foreach ($lines as $line) {
				if (eregi("^(.*)=(.*)$", $line, $match))
					$db[$match[1]] = trim($match[2]);
			}
		} catch (Exception $e) {
	        throw new EngineException ($e->GetMessage(), COMMON_ERROR);
	    }
		return $db;
	}

	/**
	 * Creates a database dump script.
	 * @param  string  $name  fileset name
	 * @param  string  $db  an array containing information on the database to be backed up
	 *
	 * @returns  void
	 */

	function UpdateDbScripts($name, $db)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Backup script
		# =============
		$filename = self::BACULA_USR . self::SCRIPT_BACKUP_PREFIX . $name . ".sh";
		$newfile = new File($filename . ".cctmp");
		try {
			if ($newfile->Exists())
				$newfile->Delete();
			$newfile->Create("root", "root", "0770");
		} catch (Exception $e) {
	        throw new EngineException ($e->GetMessage(), COMMON_ERROR);
	    }

		try {
			$newfile->AddLines(
				"#!/bin/bash\n" .
				"TYPE=" . $db["TYPE"] . "\n" .
				"HOST=" . $db["HOST"] . "\n" .
				"NAME=" . $db["NAME"] . "\n" .
				"USER=" . $db["USER"] . "\n" .
				"PASS=" . $db["PASS"] . "\n" .
				"PORT=" . $db["PORT"] . "\n"
			);
			if ($db["TYPE"] == "mysql") {
				$mysql_path = "";
				if ($db["NAME"] == "bacula")
					$mysql_path = self::PATH_MYSQL;
				$newfile->AddLines(
					$mysql_path . "/usr/bin/mysqldump -h\$HOST --user=\"\$USER\" " .
								  "--password=\"\$PASS\" -f --opt " .
								  "\$NAME -P\$PORT > " . self::BACULA_VAR . $name . ".sql\n"
				);
			} else if ($db["TYPE"] == "pgsql") {
				$newfile->AddLines(
					"if [ \"\$PASS\" == \"\" ]; then\n" .
					"  /usr/bin/pg_dump -h\$HOST -U\$USER -p\$PORT \$NAME > " .
					self::BACULA_VAR . $name . ".sql\n" .
					"else\n" .
					"  /usr/bin/pg_dump -h\$HOST -U\$USER " .
					"-W\$PASS -P\$PORT \$NAME > " .
					self::BACULA_VAR . $name . ".sql\n" .
					"fi\n"
				);
			}

		} catch (Exception $e) {
	        throw new EngineException ($e->GetMessage(), COMMON_ERROR);
	    }

		# Copy the new script over the old script
		#----------------------------------------
		$newfile->MoveTo($filename);

		# Restore script
		# ==============
		$filename = self::BACULA_USR . self::SCRIPT_RESTORE_PREFIX . $name . ".sh";
		try {
			$newfile = new File($filename . ".cctmp");
			if ($newfile->Exists())
				$newfile->Delete();
			$newfile->Create("root", "root", "0700");
		} catch (Exception $e) {
	        throw new EngineException ($e->GetMessage(), COMMON_ERROR);
	    }

		try {
			$newfile->AddLines(
				"#!/bin/bash\n" .
				"TYPE=" . $db["TYPE"] . "\n" .
				"HOST=" . $db["HOST"] . "\n" .
				"NAME=" . $db["NAME"] . "\n" .
				"USER=" . $db["USER"] . "\n" .
				"PASS=" . $db["PASS"] . "\n" .
				"PORT=" . $db["PORT"] . "\n"
			);
			if ($db["TYPE"] == "mysql") {
				$mysql_path = "";
				if ($db["NAME"] == "bacula")
					$mysql_path = self::PATH_MYSQL;

				$newfile->AddLines(
					"if ( [ \"\$USER\" == \"\" ] && [ \"\$PASS\" == \"\" ] ); then\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT -f drop \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT create \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysql -h\$HOST -P\$PORT \$NAME < $1/" .
					$name . ".sql\n" .
					"elif ( [ \"\$USER\" != \"\" ] && [ \"\$PASS\" == \"\" ] ); then\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT -f drop \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT create \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysql -h\$HOST -P\$PORT \$NAME < $1/" .
					$name . ".sql\n" .
					"  /usr/bin/sudo " . self::CMD_GRANT_PRIV . " \$TYPE \$HOST \$PORT " .
					"\$USER \"\" \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT  reload\n" .
					"else\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT -f drop \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT create \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysql -h\$HOST -P\$PORT \$NAME < $1/" .
					$name . ".sql\n" .
					"  /usr/bin/sudo " . self::CMD_GRANT_PRIV . " \$TYPE \$HOST \$PORT " .
					"\$USER \"\$PASS\" \$NAME\n" .
					"  " . $mysql_path . "/usr/bin/mysqladmin -h\$HOST -P\$PORT  reload\n" .
					"fi\n"
				);
			} else if ($db["TYPE"] == "pgsql") {
				$newfile->AddLines(
					"if ( [ \"\$USER\" == \"\" ] && [ \"\$PASS\" == \"\" ] ); then\n" .
					"  /usr/bin/drop -h\$HOST -p\$PORT \$NAME\n" .
					"  /usr/bin/create -h\$HOST -p\$PORT \$NAME\n" .
					"  /usr/bin/psql -h\$HOST -p\$PORT \$NAME -f $1/" . $name . ".sql\n" .
					"elif ( [ \"\$USER\" != \"\" ] && [ \"\$PASS\" == \"\" ] ); then\n" .
					"  /usr/bin/droph\$HOST -U\$USER -p\$PORT \$NAME\n" .
					"  /usr/bin/create -h\$HOST -U\$USER -p\$PORT \$NAME\n" .
					"  /usr/bin/psql -h\$HOST -U\$USER -p\$PORT \$NAME -f $1/" .
					$name . ".sql\n" .
					"else\n" .
					"  /usr/bin/drop -h\$HOST -U\$USER -W\$PASS -p\$PORT " .
					"\$NAME\n" .
					"  /usr/bin/create -h\$HOST -U\$USER -W\$PASS -p\$PORT " .
					"\$NAME\n" .
					"  /usr/bin/psql -h\$HOST -U\$USER -p\$PORT -W\$PASS " .
					"\$NAME -f $1/" . $name . ".sql\n" .
					"fi\n"
				);
			}
			// Copy the new script over the old script
			//----------------------------------------
			$newfile->MoveTo($filename);
		} catch (Exception $e) {
	        throw new EngineException ($e->GetMessage(), COMMON_ERROR);
	    }
	}

	/**
	 * Return a list of all possible storage devices.
	 *
	 * @returns  array  a list of possible storage devices
	 */
	function GetDevices()
	{
	    if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$storage_device = new StorageDevice();
		return $storage_device->GetDevices();
	}

	/**
	 * Tests whether all conditions (we can think of) for a basic backup job are ready.
	 * @param  string  $client  client name
	 * @param  boolean  $backup  signifies a backup job
	 *
	 * @returns  void
	 * throws EngineException
	 */
	function IsBasicJobReady($job, $backup = true)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$client = false;
			$handle = $this->Connect();
			if (bacula_command($handle,
				"status client=\"" . $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "Client") . "\"")
			) {
				$reply = bacula_reply($handle);
				foreach($reply as $line) {
					if (ereg("Director connected at*.", $line)) {
						$client = true;
						break;
					}
				}
			}
			if (!$client) {
				$this->Disconnect($handle);
				throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_CONNECT_WITH_CLIENT, COMMON_ERROR);
			}

			$storage = false;
			if (bacula_command($handle,
					"status storage=\"" . $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "Storage") . "\"")
			) {
				$reply = bacula_reply($handle);
				foreach($reply as $line) {
					if (ereg(".*Version.*", $line)) {
						$storage = true;
						break;
					}
				}
			}
			if (!$storage) {
				$this->Disconnect($handle);
				throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_CONNECT_WITH_STORAGE, COMMON_ERROR);
			}

			$sd = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "Storage");

			$media_type = $this->GetKey(self::DIR_FILE_CONFIG, "Storage", $sd, "MediaType");
			$media = explode("-", $media_type, 2);

			switch ($media[0]) {
                case self::MEDIA_DVD:

					$dev = $this->GetKey(self::SD_FILE_CONFIG, "Device", $sd, "ArchiveDevice");
					$mount = $this->GetKey(self::SD_FILE_CONFIG, "Device", $sd, "MountPoint");

					# Create the full mount directory hear, because DVD's to not get automounted
					$folder = new Folder($mount, true);
					if (!$folder->Exists())
						$folder->Create("root", "root", "0700");

					# Test DVD
					$shell = new ShellExec();
					
					# Make sure parsing is done in English
					$options['env'] = "LANG=en_US";
					$retval = $shell->Execute(self::CMD_DVD_INFO, $dev, true, $options);
					if ($retval != 0) {
						$this->Disconnect($handle);
						throw new EngineException (BACULA_LANG_ERRMSG_BLANK_DVD_ERROR, COMMON_ERROR);
					}
					$output = $shell->GetOutput();
					$blank = false;
					$rewrite = false;
					$match = array();
					foreach ($output as $line) {
						if (eregi("^[[:space:]]*Mounted Media:.*RW.*", $line)) {
							$rewrite = true;
						} else if (eregi("^[[:space:]]*Disc status: (.*)[[:space:]]*(.*$)", $line, $match)) {
							if (trim($match[1]) == "blank")
								$blank = true;
						}
					}

					if (!$blank && !$rewrite && $backup) {
						throw new EngineException (BACULA_LANG_ERRMSG_DVD_CONTAINS_DATA, COMMON_ERROR);
					} elseif ($rewrite && $backup) {
						# Add disk format
						$this->SetKey(self::DIR_FILE_CONFIG, "Job", $job, "RunBeforeJob", self::CMD_DVD_HANDLER . " $dev prepare");
					} else {
						# Remove disk format
						$this->SetKey(self::DIR_FILE_CONFIG, "Job", $job, "RunBeforeJob", self::FLAG_DELETE);
						# now that we know this is successful, add RunBefore and RunAfter scripts
						/* Do we need this for DVD???
						$this->SetKey(
							self::DIR_FILE_CONFIG,
							"Job",
							$job,
							"ClientRunBeforeJob",
							"mount $dev $mount"
						);
						$this->SetKey(
							self::DIR_FILE_CONFIG,
							"Job",
							$job,
							"ClientRunAfterJob",
							"umount $mount"
						);
						*/
					}

					# Get estimate of job
					if ($backup) {
						$required = 0;
						if (bacula_command($handle, "estimate job=\"$job\"")) {
							$reply = bacula_reply($handle);
							foreach($reply as $line) {
								$regex = "^2000 OK estimate files=([[:digit:]]+)[[:space:]]*bytes=([0-9,]+)(.*$)";
								if (eregi($regex, $line, $match)) {
									$required = ereg_replace(",", "", $match[2]);
									break;
								}
							}
						}
						if ($required == 0 || $required > self::DVD_MAX_CAPACITY) {
							$this->Disconnect($handle);
							throw new EngineException (BACULA_LANG_ERRMSG_ZERO_BACKUP_OR_INSUFFICIENT_SPACE, COMMON_ERROR);
						}

						# There should be no 'append' media waiting for basic...
						$link = mysql_connect(
							$this->sql["host"].":" . self::SOCKET_MYSQL, $this->sql["user"], $this->sql["pass"]
						);

						if (!$link) {
							// self::Log(COMMON_ERROR, BACULA_LANG_ERRMSG_DB_CONNECT_FAIL, __METHOD__, __LINE__);
						}

						mysql_select_db($this->sql["name"]);
						$result = mysql_query(
							"UPDATE Media, Pool SET VolStatus = 'Disabled' WHERE VolStatus='Append' " .
							"AND Media.PoolId = Pool.PoolId and Pool.Name = 'Basic';"
						);
						if (!$result) {
							// self::Log(COMMON_ERROR, $result, BACULA_LANG_ERRMSG_DB_CONNECT_FAIL, __METHOD__, __LINE__);
						}
						mysql_close($link);
					}
                   	break;
                case self::MEDIA_SMB:
					$mount = $this->GetKey(self::SD_FILE_CONFIG, "Device", $sd, "ArchiveDevice");
					# Need to check smbmount
					$shell = new ShellExec();
					# We don't care if this fails
					try {
						$shell->Execute(self::CMD_SMBUMOUNT, $mount, true);
					} catch (Exception $e) {
						// self::Log(COMMON_ERROR, $e->getMessage(), __METHOD__, __LINE__);
					}
					try {
						# We need to get mount parameters 'embedded' in config file
						$sd_name = $this->GetKey(self::DIR_FILE_CONFIG, "Job", $job, "Storage");
						$share = $this->DecodeShareInfo($sd_name);
						$folder = new Folder($mount, true);
						if (!$folder->Exists())
							$folder->Create("root", "root", "0700");
						
						$param = "//" . $share['address'] .
							"/" . $share['sharedir'] . " " . $mount . " -o 'username=" . $share['username'] . 
							",password=" . $share['password'] . "'";
						$retval = $shell->Execute(self::CMD_SMBMOUNT, $param, true);
						if ($retval != 0) {
							$output = $shell->GetOutput();
							foreach($output as $line)
								Logger::Syslog(COMMON_WARNING, $line);
							throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_MOUNT . " ($mount)", COMMON_ERROR);
						}
						$retval = $shell->Execute(self::CMD_SMBUMOUNT, $mount, true);
						# now that we know this is successful, add RunBefore and RunAfter scripts
						$this->SetKey(
							self::DIR_FILE_CONFIG,
							"Job",
							$job,
							"ClientRunBeforeJob",
							self::CMD_SMBMOUNT . " " . $param
						);
						$this->SetKey(
							self::DIR_FILE_CONFIG,
							"Job",
							$job,
							"ClientRunAfterJob",
							self::CMD_SMBUMOUNT . " " . $mount
						);
					} catch (Exception $e) {
						throw new EngineException ($e->getMessage(), COMMON_ERROR);
					}
					break;
                case self::MEDIA_IOMEGA:
					# Check for media
					$mount = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $sd);
					$file = new File($mount, true);
					if ($file->IsSymLink() != 1)
						throw new EngineException (BACULA_LANG_ERRMSG_DEVICE_NOT_FOUND, COMMON_ERROR);
					break;
                case self::MEDIA_USB:
					# Check for media
					$mount = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $sd);
					$file = new File($mount, true);
					if ($file->IsSymLink() != 1)
						throw new EngineException (BACULA_LANG_ERRMSG_DEVICE_NOT_FOUND, COMMON_ERROR);
					break;
			}
		} catch (Exception $e) {
			throw new EngineException ($e->getMessage(), COMMON_ERROR);
		}
		# Commit any changes
		$this->Disconnect($handle);
		$this->Commit(true);
		return;
	}

	/**
	 * Creates a default client configuration file.
	 * @param  string  $client  client name
	 * @param  string  $os  client OS
	 *
	 * @returns  void
	 */
	function CreateClientConfig($client, $os)
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Check for 'localhost' address
		if (eregi("localhost", $this->GetDirectorAddress())) {
			$interfaces = new IfaceManager();
			$network = new Network();
			$ethlist = $interfaces->GetInterfaceDetails();
			foreach ($ethlist as $eth => $info) {
				if ($network->IsLocalIp($info['address'])) {
					$this->SetDirectorAddress($info['address']);
					$this->Commit();
					break;
				}
			}
		}
			
		# WX Console
		$file = new File(COMMON_TEMP_DIR . "/wx-console.conf");
		if ($file->Exists())
			$file->Delete();
		$file->Create("webconfig", "webconfig", "0600");
		$file->AddLines("#\n");
		$file->AddLines("# Bacula User Agent (or Console) Configuration File\n");
		$file->AddLines("#\n\n");
		$file->AddLines("Director {\n");
		$file->AddLines("  Name = " . $this->GetDirectorName() . "\n");
		$file->AddLines("  DIRPort = " . $this->GetDirectorPort() . "\n");
		$file->AddLines("  Address = " . $this->GetDirectorAddress() . "\n");
		$file->AddLines("  Password = \"" . $this->GetDirectorPassword() . "\"\n");
		$file->AddLines("}\n");
		# FD
		$file = new File(COMMON_TEMP_DIR . "/bacula-fd.conf");
		if ($file->Exists())
			$file->Delete();
		$file->Create("webconfig", "webconfig", "0600");
		$file->AddLines("#\n");
		$file->AddLines("# Default Bacula File Daemon Configuration file\n");
		$file->AddLines("#\n");
		$file->AddLines("# List Directors who are permitted to contact this File daemon\n");
		$file->AddLines("#\n");
		$file->AddLines("Director {\n");
		$file->AddLines("  Name = " . $this->GetDirectorName() . "\n");
		$file->AddLines("  Password = \"" . $this->GetKey(self::DIR_FILE_CONFIG, "Client", $client, "Password") . "\"\n");
		$file->AddLines("}\n");
		$file->AddLines("#\n");
		$file->AddLines("# 'Global' File daemon configuration specifications\n");
		$file->AddLines("#\n");
		$file->AddLines("FileDaemon {\n");
  		$file->AddLines("  Name = " . $client . "\n");
		$file->AddLines("  FDPort = 9102\n");
		$file->AddLines("  WorkingDirectory = " . $this->DEFAULT_CLIENT_WORKING_DIR[$os] . "\n");
		$file->AddLines("  Pid Directory = " . $this->DEFAULT_CLIENT_WORKING_PID[$os] . "\n");
		$file->AddLines("}\n\n");
		$file->AddLines("# Send all messages except skipped files back to Director\n");
		$file->AddLines("Messages {\n");
		$file->AddLines("  Name = Standard\n");
		$file->AddLines("  Director = " . $this->GetDirectorName() . " = all, !skipped\n");
		$file->AddLines("}\n\n");
	}

	/**
	 * Searches and automatically cofigures storage devices for 'Basic' operation.
	 *
	 * @returns  void
	 */
	function AutoConfigureDevices()
	{
		if (COMMON_DEBUG_MODE)
	        self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {

			$folder = new Folder(self::DEFAULT_MOUNT, true);
			if (!$folder->Exists())
				$folder->Create("root", "root", "0700");

			$existing =  $this->GetSdList();
			$added = false;
			foreach ($this->GetDevices() as $dev => $info) {

				$name = preg_replace("/\s+|\\.|\\,/", "_", $info['vendor'] . $info['model']);
				if (in_array($name, $existing))
					continue; 
				# DVD RW
				if (eregi("DVD-RW", $info['model'])) {
					$this->ConfigureDvdRw($name, $dev);
					$added = true;
				} else if (eregi("^" . self::MEDIA_IOMEGA, $info['vendor'])) {
					$this->ConfigureRev($name, $dev);
					$added = true;
				}
			}
			if ($added)
				$this->Commit();

		} catch (Exception $e) {
			# Hide message
			self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
		}
	}

	/**
     * Searches configuration file to see if directive exists.
	 * @param  string  $file  the configuration file
	 * @param  string  $block  Bacula blocks type (ie. Job, FileSet, Schedule etc.)
	 * @param  string  $name  Bacula block names are not unique and are specified by the Name key
     *
     * @returns  boolean  true if directive exists
     */
    function DirectiveExists($file, $block, $name)
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
        try {
			$contents = Array();
			$contents = $this->GetBlock($file, $block, $name);
			if (empty($contents))
				return false;
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

    function ResetRestoreToDefault()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$default_client =  $this->GetClientList();
        $default_fileset =  $this->GetFilesetList();
        $default_schedule =  $this->GetScheduleList();
        $default_storage =  $this->GetSdList();
        $default_pool =  $this->GetPoolList();
        $restore = Array(
            "Job {",
            "  " . self::FLAG_NO_EDIT,
            "  " . self::FLAG_NO_DELETE,
            "  Name = \"" . self::RESTORE_JOB . "\"",
            "  Type = \"Restore\"",
            "  Client = \"" . current($default_client) . "\"",
            "  FileSet = \"" . current($default_fileset) . "\"",
            "  Storage = \"" . current($default_storage) . "\"",
            "  Messages = \"Standard\"",
            "  Pool = \"" . current($default_pool) . "\"",
            "  Priority = \"10\"",
            "}"
		);
			
		$this->SetBlock(self::DIR_FILE_CONFIG, "Job", self::RESTORE_JOB, $restore);
		$this->Commit(true);
	}

	/**
     * Configures DVD RW media
     *
     * @access private
     * @return void
     * @throws EngineException
     */

	function ConfigureDvdRw($name, $dev)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->AddSd($name, self::MEDIA_DVD);
		$this->Commit(true);
		$this->SetSdLabelMedia($name, "yes");
		$this->SetSdMount($name, $dev);
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "RequiresMount", "yes");
		$mount = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $name);
		# Create the full mount directory hear, because CD's to not get automounted
		$folder = new Folder($mount, true);
		if (!$folder->Exists())
			$folder->Create("root", "root", "0700");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MountPoint", $mount);
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MountCommand", "/bin/mount -t iso9660 -o ro %a %m");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "UnmountCommand", "/bin/umount %m");
		$folder = new Folder(self::DIR_TEMP, true);
		if (!$folder->Exists())
			$folder->Create("root", "root", "0700");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "SpoolDirectory", self::DIR_TEMP);
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "WritePartCommand", self::CMD_DVD_HANDLER . " %a write %e %v");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "FreeSpaceCommand", self::CMD_DVD_HANDLER . " %a free");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MaximumVolumeSize", "4GB");
	}

	/**
     * Configures Iomega REV drives
     *
     * @access private
     * @return void
     * @throws EngineException
     */

	function ConfigureRev($name, $dev)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->AddSd($name, self::MEDIA_IOMEGA);
		$this->Commit(true);
		$this->SetSdLabelMedia($name, "yes");
		$mount = self::DEFAULT_MOUNT . "/" . str_replace(" ", "", $name);
		$this->SetSdMount($name, $mount);
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "DeviceType", "File");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "RequiresMount", "no");
		$folder = new Folder(self::DEFAULT_MOUNT, true);
		if (!$folder->Exists())
			$folder->Create("root", "root", "0700");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "AlwaysOpen", "yes");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "AutomaticMount", "yes");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "RemovableMedia", "yes");
		$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "RandomAccess", "yes");
		if (eregi("RRD2", $name))
			$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MaximumVolumeSize", "70GB");
		else
			$this->SetKey(self::SD_FILE_CONFIG, "Device", $name, "MaximumVolumeSize", "35GB");
		$this->ConfigureAutoFs($name);
	}

	/**
     * Configures autofs for removable media
     *
     * @access private
     * @return void
     * @throws EngineException
     */

	function ConfigureAutoFs($name)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Get type of media
		$type = $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "MediaType");

		# Don't automount anything other than REV and USB devices
		if (!eregi(self::MEDIA_IOMEGA, $type) && !eregi(self::MEDIA_USB, $type))
			return;

		# Get media type
		$type = $this->GetKey(self::SD_FILE_CONFIG, "Device", $name, "MediaType");

		$folder = new Folder(self::DEFAULT_MOUNT, true);
		if (!$folder->Exists())
			$folder->Create("root", "root", "0700");

		# Remove spaces in name...we'll use it for the key
		$name = str_replace(" ", "", $name);
		$mountpoint = self::DEFAULT_MOUNT . "/" . $name;
		$autofs = new AutoFs();
		$points = $autofs->GetMountPoints();	

		foreach ($this->GetDevices() as $device => $info) {
			$device_name = preg_replace("/\s+|\\.|\\,/", "_", $info['vendor'] . $info['model']);
			if ($device_name == $name) {
				$dev = $device;
				break;
			}
		}

		if (! $dev)
			throw new EngineException (BACULA_LANG_ERRMSG_INVALID_DEVICE . " ($name)", COMMON_ERROR);

		# Create mount if necessary
		try {
			$autofs->AddMountPoint("/var/autofs/bacula", "/etc/auto.bacula", "timeout=5");
		} catch (Exception $e) {
			self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
		}

		# Create mount point
		try {
			$autofs->AddMount("/var/autofs/bacula", $name, ":$dev");
		} catch (Exception $e) {
			self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
		}
	
		# Restart daemon
		$autofs->Restart();
		# Make sure it's set to start on boot
		$autofs->SetBootState(true);

		# Try to remove sym link
		$file = new File($mountpoint, true);
		if ($file->Exists())
			$file->Delete();

		# Create Sym Link
		$shell = new ShellExec();
		try {
			$param = "-s /var/autofs/bacula/$name $mountpoint";
			$retval = $shell->Execute(self::CMD_LN, $param, true);
			if ($retval != 0) {
				$output = $shell->GetOutput();
				throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * CheckSmbMount.
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function CheckSmbMount ($info, $mount, $umountonsuccess = true)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$folder = new Folder($mount, true);
		if (!$folder->Exists())
			$folder->Create("root", "root", "0700");

		$param = "//{$info['address']}/{$info['sharedir']} $mount -o " .
			"'username={$info['username']},password={$info['password']}'";
		$shell = new ShellExec();
		$retval = $shell->Execute(self::CMD_SMBMOUNT, $param, true);
		if ($retval != 0) {
			$output = $shell->GetOutput();
			foreach($output as $line)
				Logger::Syslog(COMMON_WARNING, $line);
			throw new EngineException (BACULA_LANG_ERRMSG_UNABLE_TO_MOUNT . " ($mount)", COMMON_ERROR);
		}
		if ($umountonsuccess)
			$shell->Execute(self::CMD_SMBUMOUNT, $mount, true);
	}

	/**
     * Decodes base 64 encoding of Samba mount info
     *
     * @return array
     */

	function DecodeShareInfo($name)
	{
		$contents = $this->GetBlock(self::SD_FILE_CONFIG, "Device", $name);
		$share = Array("username" => "", "password" => "", "address" => "", "sharedir" => "");
		foreach ($contents as $line) {
			if (eregi("[[:space:]]*\\#[[:space:]]*ShareInfo[[:space:]]*=(.*)", $line, $match)) {
				$data = base64_decode(trim($match[1]));
				$pieces = explode("|", $data); 
				foreach ($pieces as $piece) {
					$keyvalue = split("=", $piece, 2);
					$share[$keyvalue[0]] = $keyvalue[1];
				}
				break;
			}
		}
		return $share;
	}
}

// vim: syntax=php ts=4
?>
