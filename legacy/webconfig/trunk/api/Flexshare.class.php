<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2009 Point Clark Networks.
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
//
// KLUDGE: we lost the ability to have "user groups" in 5.0.  In order to 
// support Flexshares owned by a user, we wedged it into the "ShareGroup"
// parameter.  Ideally, this should be changed to "ShareOwner"
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Flexshare is a flexible collaboration utility.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Engine.class.php");
require_once("ClearDirectory.class.php");
require_once("ConfigurationFile.class.php");
require_once("File.class.php");
require_once("Firewall.class.php");
require_once("Folder.class.php");
require_once("Group.class.php");
require_once("GroupManager.class.php");
require_once("Hostname.class.php");
require_once("Iface.class.php");
require_once("IfaceManager.class.php");
require_once("Ldap.class.php");
require_once("ClearDirectory.class.php");
require_once("Mailer.class.php");
require_once("Mime.class.php");
require_once("NtpTime.class.php");
require_once("Ssl.class.php");
require_once("ShellExec.class.php");
require_once("User.class.php");

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * Flexshare not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class FlexshareNotFoundException extends EngineException
{
	/**
	 * FlexshareNotFoundException constructor.
	 *
	 * @param string $name  name
	 * @param int $code error code
	 */

	public function __construct($name, $code)
	{
		parent::__construct(FLEXSHARE_LANG_ERRMSG_NOTEXIST . " - " . $name, $code);
	}
}

/**
 * Flexshare parameter not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2009, Point Clark Networks
 */

class FlexshareParameterNotFoundException extends EngineException
{
	/**
	 * FlexshareParameterNotFoundException constructor.
	 *
	 * @param string $parameter name of parameter
	 * @param int $code error code
	 */

	public function __construct($parameter)
	{
		parent::__construct(LOCALE_LANG_ERRMSG_REQUIRED_PARAMETER_IS_MISSING . " - " . $parameter, COMMON_INFO);
	}
}

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Flexshare class.
 *
 * Provides interface to add, edit and delete flexshare resources.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2009, Point Clark Networks
 */

class Flexshare extends Software
{
	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const LOG_TAG = 'flexshare';
	const CONSTANT_LOGIN_SHELL = '/sbin/nologin';
	const FILE_CONFIG = '/etc/flexshare.conf';
	const FILE_SMB_VIRTUAL = 'flexshare.conf';
	const FILE_FSTAB_CONFIG = '/etc/fstab';
	const PATH_ROOT = '/var/flexshare';
	const FILE_INITIALIZED = '/etc/system/initialized/flexshare';
	const SHARE_PATH = '/var/flexshare/shares';
	const HTTPD_LOG_PATH = '/var/log/httpd';
	const WEB_VIRTUAL_HOST_PATH = '/etc/httpd/conf.d';
	const FTP_VIRTUAL_HOST_PATH = '/etc/proftpd.d';
	const SMB_VIRTUAL_HOST_PATH = '/etc/samba';
	const CMD_VALIDATE_HTTPD = '/usr/sbin/httpd';
	const CMD_VALIDATE_PROFTPD = '/usr/sbin/proftpd';
	const CMD_VALIDATE_SMBD = '/usr/bin/testparm';
	const CMD_MOUNT = "/bin/mount";
	const CMD_UMOUNT = "/bin/umount";
	const CMD_PHP = "/usr/webconfig/bin/php";
	const CMD_UPDATE_PERMS = "/usr/sbin/updateflexperms";
	const DIR_MAIL_UPLOAD = "email-upload";
	const CONSTANT_USERNAME = 'flexshare';
	const MBOX_HOSTNAME = 'localhost';
	const DEFAULT_PORT_FTP = 2121;
	const DEFAULT_PORT_FTPS = 2123;
	const DEFAULT_SSI_PARAM = 'IncludesNOExec';
	const REGEX_SHARE_DESC = '^[[:space:]]*ShareDescription[[:space:]]*=[[:space:]]*(.*$)';
	const REGEX_SHARE_GROUP = '^[[:space:]]*ShareGroup[[:space:]]*=[[:space:]]*(.*$)';
	const REGEX_SHARE_DIR = '^[[:space:]]*ShareDir[[:space:]]*=[[:space:]]*(.*$)';
	const REGEX_SHARE_CREATED = '^[[:space:]]*ShareCreated[[:space:]]*=[[:space:]]*(.*$)';
	const REGEX_SHARE_ENABLED = '^[[:space:]]*ShareEnabled[[:space:]]*=[[:space:]]*(.*$)';
	const REGEX_OPEN = '^<Share[[:space:]](.*)>$';
	const REGEX_CLOSE = '^</Share>$';
	const ACCESS_LAN = 0;
	const ACCESS_ALL = 1;
	const POLICY_DONOT_WRITE = 0;
	const POLICY_OVERWRITE = 1;
	const POLICY_BACKUP = 2;
	const SAVE_REQ_CONFIRM = 0;
	const SAVE_AUTO = 1;
	const PERMISSION_NONE = 0;
	const PERMISSION_READ = 1;
	const PERMISSION_WRITE = 2;
	const PERMISSION_WRITE_PLUS = 3;
	const PERMISSION_READ_WRITE = 4;
	const PERMISSION_READ_WRITE_PLUS = 5;
	const DIR_INDEX_LIST = 'index.htm index.html index.php index.php3 default.html index.cgi';
	const EMAIL_SAVE_PATH_ROOT = 0;
	const EMAIL_SAVE_PATH_MAIL = 1;
	const EMAIL_SAVE_PATH_PARSE_SUBJECT = 2;
	const CASE_HTTP = 1;
	const CASE_HTTPS = 2;
	const CASE_CUSTOM_HTTP = 3;
	const CASE_CUSTOM_HTTPS = 4;
	const PREFIX = 'flex-';
	const FTP_PASV_MIN = 65000;
	const FTP_PASV_MAX = 65100;
	const WRITE_WARNING = '
#----------------------------------------------------------------
# WARNING: This file is automatically created by webconfig.
#----------------------------------------------------------------
';

	protected $access = array(
	                        self::PERMISSION_NONE => 'PORT QUIT',
	                        self::PERMISSION_READ => 'CWD READ DIRS PORT QUIT',
	                        self::PERMISSION_WRITE => 'CWD WRITE DIRS PORT QUIT',
	                        self::PERMISSION_WRITE_PLUS => 'CWD WRITE DIRS PORT QUIT',
	                        self::PERMISSION_READ_WRITE => 'CWD READ WRITE DIRS PORT QUIT',
	                        self::PERMISSION_READ_WRITE_PLUS => 'CWD READ WRITE DIRS PORT QUIT'
	                    );
	protected $bad_ports = array('81', '82', '83');

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Flexshare constructor.
	 *
	 * @return  void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('app-flexshare');

		if (!extension_loaded("imap"))
			dl("imap.so");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns a list of defined Flexshares.
	 *
	 * @return relational array containing summary of flexshares
	 * @throws EngineException
	 */

	function GetShareSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$share = array('WebEnabled' => 0, 'FtpEnabled' => 0, 'FileEnabled' => 0, 'EmailEnabled' => 0);
		$shares = array();

		try {
			$file = new File(self::FILE_CONFIG);

			if (! $file->Exists())
				return $shares;

			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		$match = array();

		foreach ($lines as $line) {
			if (eregi(self::REGEX_OPEN, $line, $match)) {
				$share['Name'] = $match[1];
			} elseif (eregi(self::REGEX_SHARE_DESC, $line, $match)) {
				$share['Description'] = $match[1];
			} elseif (eregi(self::REGEX_SHARE_GROUP, $line, $match)) {
				$share['Group'] = $match[1];
			} elseif (eregi(self::REGEX_SHARE_CREATED, $line, $match)) {
				$share['Created'] = $match[1];
			} elseif (eregi(self::REGEX_SHARE_ENABLED, $line, $match)) {
				$share['Enabled'] = $match[1];
			} elseif (eregi("^[[:space:]]*ShareDir*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['Dir'] = $match[1];
			} elseif (eregi("^[[:space:]]*ShareInternal*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['Internal'] = $match[1];
			} elseif (eregi("^[[:space:]]*WebEnabled*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['Web'] = $match[1];
			} elseif (eregi("^[[:space:]]*FtpEnabled*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['Ftp'] = $match[1];
			} elseif (eregi("^[[:space:]]*FileEnabled*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['File'] = $match[1];
			} elseif (eregi("^[[:space:]]*EmailEnabled*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['Email'] = $match[1];
			} elseif (eregi("^[[:space:]]*WebModified*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['WebModified'] = $match[1];
			} elseif (eregi("^[[:space:]]*FtpModified*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['FtpModified'] = $match[1];
			} elseif (eregi("^[[:space:]]*FileModified*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['FileModified'] = $match[1];
			} elseif (eregi("^[[:space:]]*EmailModified*[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share['EmailModified'] = $match[1];
			} elseif (eregi(self::REGEX_CLOSE, $line)) {
				$shares[] = $share;
				unset($share);
			}
		}

		return $shares;
	}

	/**
	 * Adds a new Flexshare.
	 *
	 * @param string $name flexshare name
	 * @param string $description brief description of the flexshare
	 * @param string $group group owner of the flexshare
	 * @param boolean $internal flag indicating if the share is designated internal
	 *
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function AddShare($name, $description, $group, $internal = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$name = strtolower($name);

		// Validate
		// --------

		if (! $this->IsValidName($name))
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_NAME);

		if (! $this->IsValidGroup($group))
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_GROUP);

		// Samba limitations
		//------------------

		$groupobj = new Group($name);

		if ($groupobj->Exists())
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_FLEXSHARE_NAME_OVERLAPS_WITH_GROUP);

		$userobj = new User($name);

		if ($userobj->Exists())
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_FLEXSHARE_NAME_OVERLAPS_WITH_USERNAME);

		try {
			$file = new File(self::FILE_CONFIG);

			if (! $file->Exists()) {
				$file->Create("root", "root", 600);
				$file->AddLines("# Flexshare Configuration");
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// Check for non-uniques

		try {
			if (count($file->GetSearchResults("<Share $name>")) > 0)
				throw new EngineException (FLEXSHARE_LANG_ERRMSG_SHARE_EXISTS, COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		try {
			// Create folder (if necessary) and add skeleton
			$folder = new Folder(self::SHARE_PATH . "/$name");

			if (! $folder->Exists()) {
				$groupobj = new Group($group);

				if ($groupobj->Exists())
					$folder->Create(self::CONSTANT_USERNAME, $group, "0775");
				else
					$folder->Create($group, "nobody", "0775");
			}

			$newshare = "<Share $name>\n" .
			            "  ShareDescription=$description\n" .
			            "  ShareGroup=$group\n" .
			            "  ShareCreated=" . time() . "\n" .
			            "  ShareModified=" . time() . "\n" .
			            "  ShareEnabled=0\n" .
			            "  ShareDir=" . self::SHARE_PATH . "/$name\n" .
			            "  ShareInternal=$internal\n" .
			            "</Share>\n"
			            ;
			$file->AddLines($newshare);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes an existing flexshare.
	 *
	 * @param string $name flexshare name
	 * @param boolean $delete_dir boolean flag to delete share directory and any files it contains
	 *
	 * @return void
	 * @throws EngineException
	 */

	function DeleteShare($name, $delete_dir)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

        if (empty($name))                                                                               
			throw new EngineException(FLEXSHARE_LANG_SHARE . " - " . LOCALE_LANG_INVALID, COMMON_ERROR);

		// Set directory back to default
		// This will remove any mount points

		try {
			$defaultdir = self::SHARE_PATH . '/' . $name;
			$this->SetDirectory($name, $defaultdir);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$file = new File(self::FILE_CONFIG);

			if (! $file->Exists())
				throw new Exception(FILE_LANG_ERRMSG_NOTEXIST . " " . self::FILE_CONFIG);

			// Backup in case we need to go back to original
			$file->MoveTo("/tmp/flexshare.conf.orig");
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// Create new file in parallel
		try {
			$newfile = new File(self::FILE_CONFIG . ".cctmp", true);

			if ($newfile->Exists())
				$newfile->Delete();

			$newfile->Create("root", "root", '0600');
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$lines = $file->GetContentsAsArray();
			$found = false;
			$match = array();

			foreach ($lines as $line) {
				if (eregi(self::REGEX_OPEN, $line, $match) && $match[1] == $name) {
					$found = true;
				} elseif (eregi(self::REGEX_CLOSE, $line) && $found) {
					$found = false;
					continue;
				}

				if ($found)
					continue;

				$newfile->AddLines($line);
			}

			$newfile->MoveTo(self::FILE_CONFIG);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$this->GenerateWebFlexshares();
			$this->GenerateFtpFlexshares();
			$this->GenerateFileFlexshares();
			$this->GenerateEmailFlexshares();

			try {
				$file->Delete();
			} catch (Exception $ignore) {
				// Just log
			}
		} catch (Exception $e) {
			// Any exception here, toggle...well, toggle.
			$file->MoveTo(self::FILE_CONFIG);
			// We want to throw SslExecutionException to help users on UI
			if (get_class($e) == SslExecutionException)
				throw new SslExecutionException ($e->GetMessage(), COMMON_ERROR);
			else
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// If you get here, it's OK to delete (as required)
		if ($delete_dir) {
			try {
				$folder = new Folder(self::SHARE_PATH . "/$name");
				if ($folder->Exists())
					$folder->Delete(true);
			} catch (Exception $e) {
				// Just log
			}
		}
	}

	/**
	 * Returns information on a specific flexshare configuration.
	 *
	 * @param string $nameflexshare name
	 * @return array information of flexshare
	 * @throws FlexshareNotFoundException, EngineException
	 */

	function GetShare($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$share = array();

		try {
			$file = new File(self::FILE_CONFIG);

			if (! $file->Exists())
				throw new EngineException (FILE_LANG_ERRMSG_NOTEXIST . " " . self::FILE_CONFIG, COMMON_ERROR);

			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		$found = false;
		$match = array();

		foreach ($lines as $line) {
			if (eregi(self::REGEX_OPEN, $line, $match)) {
				if (trim($match[1]) == trim($name)) {
					$found = true;
					$share['Name'] = $match[1];
				} else {
					continue;
				}
			} elseif ($found && eregi("^[[:space:]]*([[:alpha:]]+)[[:space:]]*=[[:space:]]*(.*$)", $line, $match)) {
				$share[$match[1]] = $match[2];
			} elseif ($found && eregi(self::REGEX_CLOSE, $line)) {
				break;
			}
		}

		if (!$found)
			throw new FlexshareNotFoundException($name, COMMON_INFO);

		return $share;
	}

	/**
	 * Toggles the status of a flexshare.
	 * @param  string  $name  flexshare name
	 * @param  string  $toggle  toggle (enable or disable)
	 * @param  string  $force  force re-creation of config files
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function ToggleShare($name, $toggle, $force = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			if (! $file->Exists())
				throw new EngineException(FILE_LANG_ERRMSG_NOTEXIST . " " . self::FILE_CONFIG, COMMON_ERROR);
			else
				$share = $this->GetShare($name);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		if ($toggle && !$share['WebEnabled'] && !$share['FtpEnabled'] && !$share['FileEnabled'] && !$share['EmailEnabled'])
			throw new EngineException(FLEXSHARE_LANG_ERRMSG_NO_ACCESS);

		// Do we need to generates configs again?
		if ($force || $this->GetParameter($name, 'ShareEnabled') != $toggle) {

			// Set flag
			$this->SetParameter($name, 'ShareEnabled', ($toggle ? 1: 0));

			try {
				$this->GenerateWebFlexshares();
				$this->GenerateFtpFlexshares();
				$this->GenerateFileFlexshares();
				$this->GenerateEmailFlexshares();
			} catch (Exception $e) {
				// Any exception here, toggle...well, toggle.
				if ($toggle)
					$this->SetParameter($name, 'ShareEnabled', 0);
				else
					$this->SetParameter($name, 'ShareEnabled', 1);

				// We want to throw SslExecutionException to help users on UI
				if (get_class($e) == SslExecutionException)
					throw new SslExecutionException ($e->GetMessage(), COMMON_ERROR);
				else
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
			}
		}

		try {
			$this->_UpdateFolderLinks($name, $this->GetParameter($name, 'ShareDir'));
			$this->_UpdateFolderAttributes($share['ShareDir'], $share['ShareGroup']);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns a list of directory options to map to flexshare.
	 * @param  string  $name  the flex share name
	 *
	 * @returns array
	 */

	function GetDirOptions($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array();

		// Custom
		try {
			if ($this->GetParameter(null, 'FlexshareDirCustom')) {
				$list = split("\\|", $this->GetParameter(null, 'FlexshareDirCustom'));
				foreach ($list as $custom) {
					list ($desc, $path) = split(":", $custom);
					$options[$path] = $desc . ' (' . $path . ")\n";
				}
			}
		} catch (Exception $e) {
			// Ignore
		}

		// Default
		$options[self::SHARE_PATH . '/' . $name] = LOCALE_LANG_DEFAULT . ' (' . self::SHARE_PATH . '/' . $name . ")\n";
		return $options;
	}

	/**
	 * Returns a list of valid web access options for a flexshare.
	 *
	 * @returns array
	 */

	function GetWebAccessOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               self::ACCESS_LAN => FLEXSHARE_LANG_ACCESS_LAN,
		               self::ACCESS_ALL => LOCALE_LANG_ALL
		           );

		return $options;
	}

	/**
	 * Returns a list of valid FTP permission options for a flexshare.
	 *
	 * @returns array
	 */

	function GetFtpPermissionOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               self::PERMISSION_READ => FLEXSHARE_LANG_READ,
		               self::PERMISSION_WRITE => FLEXSHARE_LANG_WRITE,
		               self::PERMISSION_WRITE_PLUS => FLEXSHARE_LANG_WRITE_PLUS,
		               self::PERMISSION_READ_WRITE => FLEXSHARE_LANG_READ_WRITE,
		               self::PERMISSION_READ_WRITE_PLUS => FLEXSHARE_LANG_READ_WRITE_PLUS
		           );
		return $options;
	}

	/**
	 * Returns a list of valid FTP umask options for a flexshare.
	 *
	 * @returns array
	 */

	function GetFtpUmaskOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Umask is inverted.
		$options = array(
		               7 => "---",
		               6 => "--x",
		               5 => "-w-",
		               4 => "-wx",
		               3 => "r--",
		               2 => "r-x",
		               1 => "rw-",
		               0 => "rwx",
		           );

		return $options;
	}

	/**
	 * Returns a list of valid file permission options for a flexshare.
	 *
	 * @returns array
	 */

	function GetFilePermissionOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               self::PERMISSION_READ => FLEXSHARE_LANG_READ,
		               self::PERMISSION_READ_WRITE => FLEXSHARE_LANG_READ_WRITE
		           );

		return $options;
	}

	/**
	 * Returns a list of valid file (Samba) create mask options for a flexshare.
	 *
	 * @returns array
	 */

	function GetFileCreateMaskOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               0 => "---",
		               1 => "--x",
		               2 => "-w-",
		               3 => "-wx",
		               4 => "r--",
		               5 => "r-x",
		               6 => "rw-",
		               7 => "rwx",
		           );

		return $options;
	}

	/**
	 * Returns a list of valid email policy options for a flexshare.
	 *
	 * @returns array
	 */

	function GetEmailPolicyOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               self::POLICY_DONOT_WRITE => FLEXSHARE_LANG_DONOT_WRITE,
		               self::POLICY_OVERWRITE => FLEXSHARE_LANG_OVERWRITE,
		               self::POLICY_BACKUP => FLEXSHARE_LANG_BACKUP
		           );

		return $options;
	}

	/**
	 * Returns a list of valid email policy options for a flexshare.
	 *
	 * @returns array
	 */

	function GetEmailSaveOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               self::SAVE_REQ_CONFIRM => FLEXSHARE_LANG_SAVE_REQ_CONFIRM,
		               self::SAVE_AUTO => FLEXSHARE_LANG_SAVE_AUTO
		           );

		return $options;
	}

	/**
	 * Returns a list of valid email directory options for a flexshare.
	 * @param  string  $name  the flex share name
	 *
	 * @returns array
	 */

	function GetEmailDirOptions($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
		               self::EMAIL_SAVE_PATH_ROOT => FLEXSHARE_LANG_ROOT_DIR,
		               self::EMAIL_SAVE_PATH_MAIL => FLEXSHARE_LANG_MAIL_SUB_DIR,
		               self::EMAIL_SAVE_PATH_PARSE_SUBJECT => FLEXSHARE_LANG_PARSE_SUBJECT
		           );
		return $options;
	}

	/**
	 * Returns a list of valid save mask options for a flexshare.
	 *
	 * @returns array
	 */

	function GetEmailSaveMaskOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->GetFileCreateMaskOptions();
	}

	/**
	 * Create the Apache configuration files for the specificed flexshare.
	 *
	 * @returns  void
	 * @throws  SslExecutionException, EngineException
	 */

	function GenerateWebFlexshares()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
			return;

		require_once("Httpd.class.php");

		$httpd = new Httpd();
		$vhosts = array();
		$allow_list = "";

		// Create a unique file identifier
		$backup_key = time();

		// Get file listing in Apache vhost dir
		try {
			$folder = new Folder(self::WEB_VIRTUAL_HOST_PATH);
			$vhosts = $folder->GetListing();
			$index = 0;
			foreach ($vhosts as $vhost) {
				// Flexshares are prefixed with 'flex-'.  Find these files
				if (eregi("flex-443.ssl|^" . self::PREFIX . ".*vhost$|^" . self::PREFIX . ".*conf$", $vhost)) {
					$vhost_file = new File(self::WEB_VIRTUAL_HOST_PATH . "/" . $vhost);
					// Backup existing file
					$vhost_file->MoveTo("/tmp/" . "$vhost.$backup_key.bak");
				} else {
					unset($vhosts[$index]);
				}
				$index++;
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// We'll add this back later if there is an SSL configured
		try {
			$sslfile = new File(Httpd::FILE_SSL);
			if ($sslfile->Exists())
				$sslfile->DeleteLines("/Include conf.d\/" . self::PREFIX . "443.ssl/");
		} catch (FileNotFoundException $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		} catch (Exception $e) {
			// This may not be fatal
		}

		$shares = $this->GetShareSummary();
		// Recreate all virtual configs
		$newlines = array();
		$anon = array();

		for ($index = 0; $index < count($shares); $index++) {
			// Reset our loop variables
			unset($newlines);
			unset($anon);
			$name = $shares[$index]['Name'];
			$share = $this->GetShare($name);

			// If not enabled, continue through loop - we're re-creating lines here
			if (! isset($share["ShareEnabled"]) || ! $share["ShareEnabled"])
				continue;

			if (! isset($share["WebEnabled"]) || ! $share["WebEnabled"])
				continue;

			// Need to know which file we'll be writing to.
			// We determine this by port
			// Ie. /etc/httpd/conf.d/flexshare<port>.<appropriate extension>

			// Port
			if ($share['WebOverridePort']) {
				$port = $share['WebPort'];
				$ext = '.conf';
				if ($share['WebReqSsl'])
					$ssl = '.ssl';
			} else {
				if ($share['WebReqSsl']) {
					$port = 443;
					$ext = '.ssl';
				} else {
					$port = 80;
					$ext = '.conf';
				}
			}

			// Interface
			$lans = array();

			if ($share['WebAccess'] == self::ACCESS_LAN) {
				try {
					$ifacemanager = new IfaceManager();
					$lans = $ifacemanager->GetLanNetworks();
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			}

			$case = $this->DetermineCase($port, $share['WebReqSsl']);

			// Create new file in parallel
			try {
				$filename = self::PREFIX . $port . $ssl . $ext;
				$file = new File(self::WEB_VIRTUAL_HOST_PATH . "/" . $filename);
				if (! $file->Exists())
					$vhosts[] = $filename;
			} catch (Exception $e) {
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
			}

			$newlines = array();

			if (! $file->Exists()) {
				$newlines[] = self::WRITE_WARNING;
				// Only specify Listen directive for custom ports
				if ($case == self::CASE_CUSTOM_HTTP || $case == self::CASE_CUSTOM_HTTPS)
					$newlines[] = "Listen *:$port\n";
				if ($case != self::CASE_HTTP)
					$newlines[] = "NameVirtualHost *:$port\n";
			}

			// cgi-bin Alias must come first.
			if ($share['WebCgi']) {
				$cgifolder = new Folder(self::SHARE_PATH . "/$name/cgi-bin/");
				if (!$cgifolder->Exists())
					$cgifolder->Create(self::CONSTANT_USERNAME, self::CONSTANT_USERNAME, "0777");
				$newlines[] = "ScriptAlias /flexshare/$name/cgi-bin/ " . self::SHARE_PATH . "/$name/cgi-bin/";
			}

			$newlines[] = "Alias /flexshare/$name " . self::SHARE_PATH . "/$name\n";
			$newlines[] = "<VirtualHost *:$port>";
			$newlines[] = "\tServerName " . $name . '.' . trim($share['WebServerName']);
			$newlines[] = "\tDocumentRoot " . self::SHARE_PATH . "/$name";

			if ($share['WebCgi'])
				$newlines[] = "\tScriptAlias /cgi-bin/ " . self::SHARE_PATH . "/$name/cgi-bin/";

			// Logging
			$newlines[] = "\tErrorLog " . self::HTTPD_LOG_PATH . "/" . trim($share['WebServerName']) . "_error_log";
			$newlines[] = "\tCustomLog " . self::HTTPD_LOG_PATH . "/" . trim($share['WebServerName']) . "_access_log common";

			switch ($case) {
			case self::CASE_HTTP:
				break;

			case self::CASE_HTTPS:
				// Enable SSL on server it not already
				$httpd->SetSslState((bool)true);
				// SSL file has to exist now
				// Add include if req'd
				try {
					$sslfile->LookupLine("/Include conf.d\/" . self::PREFIX . "443.ssl/");
				} catch (FileNoMatchException $e) {
					$sslfile->AddLines("Include conf.d/" . self::PREFIX . "443.ssl\n");
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
				break;

			case self::CASE_CUSTOM_HTTPS:
				// Logging
				$newlines[] = "\tErrorLog " . self::HTTPD_LOG_PATH . "/" .
				              trim($share['WebServerName']) . "_error_log";
				$newlines[] = "\tCustomLog " . self::HTTPD_LOG_PATH . "/" .
				              trim($share['WebServerName']) . "_access_log common";
				try {
					$ssl = new Ssl();
					$certs = $ssl->GetCertificates(Ssl::TYPE_CRT);
					$ssl_found = false;
					foreach ($certs as $certfile => $cert) {
						if ($cert['common_name'] == trim($share['WebServerName'])) {
							// Don't use CA
							if ($certfile == Ssl::FILE_CA_CRT)
								continue;
							$ssl_found = true;
							$cert_filename = $certfile;
							break;
						}
					}
					if (! $ssl_found) {
						$ssl->SetRsaKeySize(Ssl::DEFAULT_KEY_SIZE);
						$ssl->SetTerm(Ssl::TERM_1YEAR);
						$ssl->SetPurpose(Ssl::PURPOSE_SERVER_CUSTOM);
						$csr_filename = $ssl->CreateCertificateRequest(trim($share['WebServerName']));
						// Self-sign be default
						$cert_filename = $ssl->SignCertificateRequest($csr_filename);
					}
				} catch (SslExecutionException $e) {
					throw new SslExecutionException(FLEXSHARE_LANG_ERRMSG_SSL_CA_MISSING, COMMON_ERROR);
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}

				$key = ereg_replace("-cert\\.pem", "-key.pem", $cert_filename);

				if (! $httpd->GetSslState())
					$newlines[] = "\n\tLoadModule ssl_module modules/mod_ssl.so\n\n";

				$newlines[] = "\tSSLEngine on\n" .
				              "\tSSLCertificateFile " . Ssl::DIR_SSL . "/$cert_filename\n" .
				              "\tSSLCertificateKeyFile " . Ssl::DIR_SSL . "/private/$key\n" .
				              "\t# No weak export crypto allowed\n" .
				              "\t# SSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP:+eNULL\n" .
				              "\tSSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:!EXP:+eNULL\n" .
				              "\tSetEnvIf User-Agent \".*MSIE.*\" " .
				              "nokeepalive ssl-unclean-shutdown downgrade-1.0 force-response-1.0\n";
				break;
			case self::CASE_CUSTOM_HTTP:
				break;
			}

			$newlines[] = "</VirtualHost>\n";

			if ($share['WebCgi']) {
				$newlines[] = "<Directory " . self::SHARE_PATH . "/$name/cgi-bin>";
				$newlines[] = "\tOptions +ExecCGI";
				if ($share["WebAccess"] == self::ACCESS_LAN) {
					$newlines[] = "\tOrder Deny,Allow";
					$newlines[] = "\tDeny from all";
					if (count($lans) > 0) {
						foreach ($lans as $lan)
							$allow_list .= "$lan ";
						$newlines[] = "\tAllow from " . $allow_list;
					}
				}
				$newlines[] = "</Directory>";
			}

			$newlines[] = "<Directory " . self::SHARE_PATH . "/$name>";
			$options = "";

			if ($share['WebShowIndex'])
				$options .= " +Indexes";
			else
				$options .= " -Indexes";

			if ($share['WebFollowSymLinks'])
				$options .= " +FollowSymLinks";
			else
				$options .= " -FollowSymLinks";

			if ($share['WebAllowSSI'])
				$options .= " +" . self::DEFAULT_SSI_PARAM;
			else
				$options .= " -" . self::DEFAULT_SSI_PARAM;

			if (strlen($options) > 0)
				$newlines[] = "\tOptions" . $options;

			if ($share['WebHtaccessOverride'])
				$newlines[] = "\tAllowOverride All";

			if ($share['WebReqAuth']) {
				$ldap = new Ldap();
				$ldap_conf = "ldap://127.0.0.1:389/" . ClearDirectory::GetUsersOu() . "?uid?one?(pcnWebFlag=TRUE)";
				$newlines[] = "\tAuthType Basic";
				$newlines[] = "\tAuthBasicProvider ldap";
				$newlines[] = "\tAuthzLDAPAuthoritative Off";
				$newlines[] = "\tAuthName \"" . $share['WebRealm'] . "\"";
				$newlines[] = "\tAuthLDAPUrl $ldap_conf";

				// Determine if this is a group or a user
				$group = new Group($share['ShareGroup']);

				if ($group->Exists()) {
					$newlines[] = "\tRequire ldap-group cn=" . $share['ShareGroup'] . "," . ClearDirectory::GetGroupsOu();
				} else {
					// TODO: API should be something like User->GetDn() instead of Ldap->GetDnForUid ?
					$user = new User($share['ShareGroup']);
					if ($user->Exists()) {
						$ldap = new Ldap();
						$dn = $ldap->GetDnForUid($share['ShareGroup']);
						$newlines[] = "\tRequire ldap-dn " . $dn;
					}
				}
			}

			if ($share["WebAccess"] == self::ACCESS_LAN) {
				$newlines[] = "\tOrder deny,allow";
				$newlines[] = "\tDeny from all";

				if (count($lans) > 0) {
					foreach ($lans as $lan)
						$allow_list .= "$lan ";

					$newlines[] = "\tAllow from " . $allow_list;
				}
			} else {
				$newlines[] = "\tOrder deny,allow";
				$newlines[] = "\tAllow from all";
			}

			try {
				// Default to 4
				$php_handler = 'php-script';
				$shell = new ShellExec();
				if ($shell->Execute(Flexshare::CMD_PHP, '-v', false) == 0) {
					$output = $shell->GetOutput();
					if (preg_match("/^PHP (\d+).(\d+).*$/", $output[0], $match)) {
						// PHP5 ?
						if ((int)$match[1] == 5)
							$php_handler = 'php5-script';
					}
				}
			} catch (Exception $e) {
				$php_handler = 'php-script';
			}

			if ($share['WebPhp']) {
				$newlines[] = "\tAddType text/html php";
				$newlines[] = "\tAddHandler $php_handler php";
			} else {
				$newlines[] = "\tRemoveHandler .php";
				$newlines[] = "\tAddType application/x-httpd-php-source .php";
			}

			// TODO: the FollowSymLinks requirement is annoying
			if ($share['WebReqSsl'] && $share['WebFollowSymLinks']) {
				$newlines[] = "\tRewriteEngine On";
				$newlines[] = "\tRewriteCond %{HTTPS} off";
				$newlines[] = "\tRewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI}";
			}

			// DAV (unsupported)
			$davcheck = self::SHARE_PATH . "/$name/.DAV";
			$davfile = new File($davcheck);
			if ($davfile->Exists())
				$newlines[] = "\tDav on";

			$newlines[] = "</Directory>\n\n\n";

			if (! $file->Exists())
				$file->Create('root', 'root', '0640');

			$file->AddLines(implode("\n", $newlines));
		}

		// Validate httpd configuration before restarting server
		$config_ok = true;

		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Flexshare::CMD_VALIDATE_HTTPD, '-t', true);
		} catch (Exception $e) {
			// Backup out of commits
			$config_ok = false;
		}

		if ($exitcode != 0) {
			$config_ok = false;
			$output = $shell->GetOutput();
			Logger::Syslog(self::LOG_TAG, "Invalid httpd configuration!");
			// Oops...we generated an invalid conf file
			foreach($output as $line)
				Logger::Syslog(self::LOG_TAG, $line);
		}

		foreach ($vhosts as $vhost) {
			// Not a flexshare vhost file
			if (!isset($vhost))
				continue;
			$file = new File("/tmp/$vhost.$backup_key.bak");
			if (! $file->Exists()) {
				// Conf was newly created
				$file = new File(self::WEB_VIRTUAL_HOST_PATH . "/$vhost");
				if (! $config_ok)
					$file->Delete();
				continue;
			}

			if ($config_ok) {
				// Delete backups
				$file->Delete();
			} else {
				// Recover backups
				$file->MoveTo(self::WEB_VIRTUAL_HOST_PATH . "/$vhost");
			}
		}
		if (! $config_ok)
			throw new EngineException (FLEXSHARE_LANG_ERRMSG_CONFIG_VALIDATION_FAILED, COMMON_ERROR);

		// Reload web server
		$httpd->Reset();
	}

	/**
	 * Create the ProFtp configuration files for the specificed flexshare.
	 *
	 * @returns  void
	 * @throws  SslExecutionException EngineException
	 */

	function GenerateFtpFlexshares()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
			return;

		require_once("Proftpd.class.php");

		$confs = array();
		$proftpd = new Proftpd();

		// Create a unique file identifier
		$backup_key = time();

		// Get file listing in FTP confs dir
		try {
			$folder = new Folder(self::FTP_VIRTUAL_HOST_PATH);
			$confs = $folder->GetListing();
			$index = 0;
			foreach ($confs as $conf) {
				if (eregi("^" . self::PREFIX . ".*conf$", $conf)) {
					$conf_file = new File(self::FTP_VIRTUAL_HOST_PATH . "/" . $conf);
					// Backup existing file
					$conf_file->MoveTo("/tmp/" . "$conf.$backup_key.bak");
				} else {
					unset($confs[$index]);
				}
				$index++;
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		$shares = $this->GetShareSummary();

		// Recreate all virtual configs
		for ($index = 0; $index < count($shares); $index++) {

			$newlines = array();
			$anon = array();
			$name = $shares[$index]['Name'];
			$share = $this->GetShare($name);
			$append = false;

			// If not enabled, continue through loop - we're re-creating lines here
			if (!isset($share['ShareEnabled']) || !$share['ShareEnabled'])
				continue;
			if (!isset($share['FtpEnabled']) || !$share['FtpEnabled'])
				continue;

			// Add group greeting file
			try {
				// This isn't fatal.  Log and continue on exception
				$file = new File(self::SHARE_PATH . "/$name/.flexshare-group.txt", true);
				if ($file->Exists())
					$file->Delete();

				if ($share['FtpGroupGreeting']) {
					$file->Create("root", "root", 644);
					$file->AddLines($share['FtpGroupGreeting'] . "\n");
				}
			} catch (Exception $e) {
				//
			}

			// Add anonymous greeting file
			try {
				// This isn't fatal.  Log and continue on exception
				$file = new File(self::SHARE_PATH . "/$name/.flexshare-anonymous.txt");
				if ($file->Exists())
					$file->Delete();

				if ($share['FtpAnonymousGreeting']) {
					$file->Create(self::CONSTANT_USERNAME, self::CONSTANT_USERNAME, 644);
					$file->AddLines($share['FtpAnonymousGreeting']);
				}
			} catch (Exception $e) {
				//
			}

			// Need to know which file we'll be writing to.
			// We determine this by port
			// Ie. /etc/proftpd.d/flex-<port>.conf

			// Port
			if ($share['FtpOverridePort']) {
				$port = $share['FtpPort'];
			} else {
				if ($share['FtpReqSsl']) {
					$port = self::DEFAULT_PORT_FTPS;
				} else {
					$port = self::DEFAULT_PORT_FTP;
				}
			}

			// Passive mode flag
			$pasv = '';
			if ($share['FtpAllowPassive'])
				$pasv = ' PASV';

			// Overwrite permission
			if ((int)$share['FtpGroupPermission'] == self::PERMISSION_WRITE_PLUS)
				$group_write = 'on';
			else if ((int)$share['FtpGroupPermission'] == self::PERMISSION_READ_WRITE_PLUS)
				$group_write = 'on';
			else
				$group_write = 'off';

			if ((int)$share['FtpAnonymousPermission'] == self::PERMISSION_WRITE_PLUS)
				$anonymous_write = 'on';
			else if ((int)$share['FtpAnonymousPermission'] == self::PERMISSION_READ_WRITE_PLUS)
				$anonymous_write = 'on';
			else
				$anonymous_write = 'off';

			// Create new file in parallel
			try {
				$filename = self::PREFIX . $port . '.conf';
				// Add to confs array in case of failure
				if (!in_array($filename, $confs))
					$confs[] = $filename;
				$file = new File(self::FTP_VIRTUAL_HOST_PATH . "/" . $filename);
				$tempfile = new File(self::FTP_VIRTUAL_HOST_PATH . "/" . $filename . '.cctmp');
				if ($tempfile->Exists())
					$tempfile->Delete();
				$tempfile->Create("root", "root", '0640');
			} catch (Exception $e) {
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
			}

			if ($file->Exists()) {
				$oldlines = $file->GetContentsAsArray();
				$found_start = false;

				$linestoadd = "";
				foreach ($oldlines as $line) {
					if (ereg("^[[:space:]]*# DNR:Webconfig start - $name$", $line))
						$found_start = true;
					if ($found_start && ereg("^[[:space:]]*# DNR:Webconfig end - $name$", $line)) {
						$found_start = false;
						continue;
					}
					if ($found_start)
						continue;

					// Look for anonymous
					if (eregi("^[[:space:]]*<Anonymous " . self::SHARE_PATH . "/>$", $line))
						$found_anon = true;
					if ($found_anon && eregi("^[[:space:]]*</Anonymous>$", $line)) {
						$found_anon = false;
						continue;
					}

					if ($found_anon)
						$anon[] = $line;
					else
						$linestoadd .= $line . "\n";
					// We need to know if we are working on top of another define or not
					$append = true;
				}
				$tempfile->AddLines($linestoadd);
			}

			try {
				$proftpd_conf = new File(Proftpd::FILE_CONFIG);
				$proftpd_conf->LookupLine("/Include \/etc\/proftpd.d\/\*.conf/");
			} catch (FileNoMatchException $e) {
				// Need this line to include flexshare confs
				$proftpd_conf->AddLines("Include /etc/proftpd.d/*.conf\n");
			} catch (Exception $e) {
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
			}

			if (! $append) {
				$newlines[] = self::WRITE_WARNING;
				// Note: Syswatch/Proftp will automatically handle IP address changes
				$newlines[] = "<VirtualHost 127.0.0.1>";
				$newlines[] = "\tPort $port";
				$newlines[] = "\tDefaultRoot " . self::SHARE_PATH . "/";
				$newlines[] = "\tRequireValidShell off";
				$newlines[] = "\tPassivePorts " . $share["FtpPassivePortMin"]  . " " . $share["FtpPassivePortMax"];
				// $newlines[] = "\tCapabilitiesEngine on";
				// $newlines[] = "\tCapabilitiesSet +CAP_CHOWN";
				$newlines[] = "";
				$newlines[] = "\t<Limit LOGIN CDUP PWD XPWD LIST PROT$pasv>";
				$newlines[] = "\t\tAllowAll";
				$newlines[] = "\t</Limit>";
				$newlines[] = "\t<Limit ALL>";
				$newlines[] = "\t\tDenyAll";
				$newlines[] = "\t</Limit>";
				$newlines[] = "";

				// FTPS (SSL)
				if ($share['FtpReqSsl']) {
					// We need an SSL certificate
					try {
						$ssl = new Ssl();
						$certs = $ssl->GetCertificates(Ssl::TYPE_CRT);
						$ssl_found = false;

						foreach ($certs as $myfile => $cert) {
							if ($cert['common_name'] == trim($share['FtpServerUrl'])) {
								// Don't use CA
								if ($myfile == Ssl::FILE_CA_CRT)
									continue;

								$ssl_found = true;
								$cert_filename = $myfile;
								break;
							}
						}

						if (! $ssl_found) {
							$ssl->SetRsaKeySize(Ssl::DEFAULT_KEY_SIZE);
							$ssl->SetTerm(Ssl::TERM_1YEAR);
							$ssl->SetPurpose(Ssl::PURPOSE_SERVER_CUSTOM);
							$csr_filename = $ssl->CreateCertificateRequest(trim($share['FtpServerUrl']));
							// Self-sign be default
							$cert_filename = $ssl->SignCertificateRequest($csr_filename);
						}
					} catch (SslExecutionException $e) {
						throw new SslExecutionException(FLEXSHARE_LANG_ERRMSG_SSL_CA_MISSING, COMMON_ERROR);
					} catch (Exception $e) {
						throw new EngineException ($e->GetMessage(), COMMON_ERROR);
					}

					$key = ereg_replace("-cert\\.pem", "-key.pem", $cert_filename);
					$newlines[] = "\t<IfModule mod_tls.c>";
					$newlines[] = "\t  TLSEngine on";
					$newlines[] = "\t  TLSLog /var/log/tls.log";
					$newlines[] = "\t  TLSOptions NoCertRequest";
					$newlines[] = "\t  TLSRequired on";
					$newlines[] = "\t  TLSRSACertificateFile " . Ssl::DIR_SSL . "/" . $cert_filename;
					$newlines[] = "\t  TLSRSACertificateKeyFile " . Ssl::DIR_SSL . "/private/" . $key;
					$newlines[] = "\t  TLSCACertificateFile " . Ssl::DIR_SSL . "/" . SsL::FILE_CA_CRT;
					$newlines[] = "\t  TLSVerifyClient off";
					$newlines[] = "\t</IfModule>";
				}
			} else {
				if ($share['FtpAllowPassive']) {
					$tempfile->ReplaceLines(
					    "/\sPassivePorts \d+\s+\d+/",
					    "\tPassivePorts " . $share['FtpPassivePortMin']  . " " . $share['FtpPassivePortMax'] . "\n"
					);
				}
			}

			// Determine if this is a group or a user
			$group = new Group($share['ShareGroup']);

			if ($group->Exists())
				$isgroup = true;
			else
				$isgroup = false;

			// Add flexshare specific directory directives
			$newlines[] = "\t# DNR:Webconfig start - $name";
			$newlines[] = "\t<Directory " . self::SHARE_PATH . "/$name>";
			$newlines[] = "\t\tAllowOverwrite " . $group_write;
			$newlines[] = "\t\tAllowRetrieveRestart on";
			$newlines[] = "\t\tAllowStoreRestart on";
			$newlines[] = "\t\tDisplayChdir .flexshare-group.txt true";
			$newlines[] = "\t\tHideNoAccess on";
			$newlines[] = "\t\tHideFiles (.flexshare)";

			if ($isgroup)
				$newlines[] = "\t\tGroupOwner " . $share["ShareGroup"];

			if (isset($share["FtpReqAuth"]) && $share["FtpReqAuth"]) {
				$newlines[] = "\t\tUmask 0113 0002";

				if (isset($this->access[$share['FtpGroupPermission']]))
					$newlines[] = "\t\t<Limit " . $this->access[$share['FtpGroupPermission']] . "$pasv>";
				else
					$newlines[] = "\t\t<Limit " . $this->access[self::PERMISSION_NONE] . "$pasv>";

				if ($isgroup)
					$newlines[] = "\t\t  AllowGroup " . $share['ShareGroup'];
				else
					$newlines[] = "\t\t  AllowUser " . $share['ShareGroup'];

				$newlines[] = "\t\t  IgnoreHidden on";
				$newlines[] = "\t\t</Limit>";
				$newlines[] = "\t\t<Limit ALL>";
				$newlines[] = "\t\t  DenyAll";
				$newlines[] = "\t\t</Limit>";
			}

			$newlines[] = "\t</Directory>";
			$newlines[] = "\t# DNR:Webconfig end - $name";
			$newlines[] = "";

			if (!$append)
				$anon[] = "\n\t<Anonymous " . self::SHARE_PATH . "/>";

			// Insert Anonymous as required
			if ($share["FtpAllowAnonymous"]) {
				// If new file is being created or anon array = 1 (that is, it contains the <Anonymous...> start tag only
				if (!$append || count($anon) == 1) {
					$anon[] = "\t\tUser\tflexshare";
					$anon[] = "\t\tGroup\tflexshare";
					$anon[] = "\t\tUserAlias\tanonymous flexshare";
				}
				$anon[] = "\t\t# DNR:Webconfig start - $name";
				$anon[] = "\t\t<Directory " . self::SHARE_PATH . "/$name>";
				$anon[] = "\t\tUmask " . $share['FtpAnonymousUmask'];
				$anon[] = "\t\tDisplayChdir .flexshare-anonymous.txt true";
				$anon[] = "\t\tAllowOverwrite " . $anonymous_write;
				$anon[] = "\t\tHideFiles (.flexshare)";
				$anon[] = "\t\t<Limit ALL>\n\t\t  DenyAll\n\t\t</Limit>";

				if (isset($this->access[$share['FtpAnonymousPermission']]))
					$anon[] = "\t\t<Limit " . $this->access[$share['FtpAnonymousPermission']] . "$pasv>";
				else
					$anon[] = "\t\t<Limit " . $this->access[self::PERMISSION_NONE] . "$pasv>";

				$anon[] = "\t\t  AllowAll";
				$anon[] = "\t\t</Limit>";
				$anon[] = "\t\t</Directory>";
				$anon[] = "\t\t# DNR:Webconfig end - $name";
			}

			$anon[] = "\t</Anonymous>";

			if ($append) {
				try {
					$tempfile->DeleteLines("/<\/VirtualHost>/");
					$tempfile->AddLines(implode("\n", $newlines) . "\n" . implode("\n", $anon) . "\n</VirtualHost>\n");
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			} else {
				try {
					$tempfile->AddLines(implode("\n", $newlines) . "\n" . implode("\n", $anon) . "\n</VirtualHost>\n");
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			}

			$tempfile->MoveTo(self::FTP_VIRTUAL_HOST_PATH . "/" . $filename);
		}

		// Validate proftpd configuration before restarting server
		$config_ok = true;

		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Flexshare::CMD_VALIDATE_PROFTPD, '-t', true);
		} catch (Exception $e) {
			$config_ok = false;
		}

		if ($exitcode != 0) {
			$config_ok = false;
			$output = $shell->GetOutput();
			Logger::Syslog(self::LOG_TAG, "Invalid ProFTP configuration!");
			foreach($output as $line)
				Logger::Syslog(self::LOG_TAG, $line);
		}

		foreach ($confs as $conf) {
			// Not a flexshare conf file
			if (!isset($conf))
				continue;

			$file = new File("/tmp/$conf.$backup_key.bak");

			if (! $file->Exists()) {
				// Conf was newly created
				$file = new File(self::FTP_VIRTUAL_HOST_PATH . "/$conf");

				if (! $config_ok)
					$file->Delete();

				continue;
			}

			if ($config_ok) {
				// Delete backups
				$file->Delete();
			} else {
				// Recover backups
				$file->MoveTo(self::FTP_VIRTUAL_HOST_PATH . "/$conf");
			}
		}

		if (! $config_ok)
			throw new EngineException(FLEXSHARE_LANG_ERRMSG_CONFIG_VALIDATION_FAILED, COMMON_ERROR);

		// Reload FTP server
		$proftpd->Reset();
		self::Log(COMMON_DEBUG, 'exiting', __METHOD__, __LINE__);
	}

	/**
	 * Create the Samba configuration files for the specificed flexshare.
	 *
	 * @returns void
	 * @throws EngineException
	 */

	function GenerateFileFlexshares()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! file_exists(COMMON_CORE_DIR . "/api/Samba.class.php"))
			return;

		require_once("Samba.class.php");

		$samba = new Samba();

		// Create a unique file identifier
		$backup_key = time();

		try {
			// Backup original file
			$backup = new File(self::SMB_VIRTUAL_HOST_PATH . "/" . self::FILE_SMB_VIRTUAL);
			if ($backup->Exists())
				$backup->MoveTo("/tmp/$backup_key.bak");

			// Samba is slightly different.  We dump all flexshare-related 'stuff' in one file
			$file = new File(self::SMB_VIRTUAL_HOST_PATH . "/" . self::FILE_SMB_VIRTUAL);
			if ($file->Exists())
				$file->Delete();

			$file->Create("root", "root", '0644');
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$samba_conf = new File(Samba::FILE_CONFIG);

			if (! $samba_conf->Exists())
				throw new Exception(FILE_LANG_ERRMSG_NOTEXIST . " " . Samba::FILE_CONFIG);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// We'll uncomments this directive later, if necessary
		$samba_conf->PrependLines("/^Include = \\/etc\\/samba\\/" . self::FILE_SMB_VIRTUAL . "/", "#");

		$shares = $this->GetShareSummary();
		$linestoadd = "";

		// Recreate samba flexshare.conf

		for ($index = 0; $index < count($shares); $index++) {
			$name = $shares[$index]['Name'];
			$share = $this->GetShare($name);

			// If not enabled, continue through loop - we're re-creating lines here
			if (! isset($share['ShareEnabled']) || ! $share['ShareEnabled'])
				continue;

			if (! isset($share['FileEnabled']) || ! $share['FileEnabled'])
				continue;

			$linestoadd .= "[" . $name . "]\n";
			$linestoadd .= "\tpath = " . $share["ShareDir"] . "\n";
			$linestoadd .= "\tcomment = " . $share["FileComment"] . "\n";

			if ($share["FileBrowseable"])
				$linestoadd .= "\tbrowseable = Yes\n";
			else
				$linestoadd .= "\tbrowseable = No\n";

			if ((int)$share["FilePermission"] == self::PERMISSION_READ_WRITE)
				$linestoadd .= "\tread only = No\n";

			if ($share["FilePublicAccess"]) {
				$linestoadd .= "\tguest ok = Yes\n";
			} else {
				$linestoadd .= "\tguest ok = No\n";
				$linestoadd .= "\tdirectory mask = 775\n";
				$linestoadd .= "\tcreate mask = 664\n";
				// Determine if this is a group or a user
				$group = new Group($share['ShareGroup']);

				if ($group->Exists()) {
					$linestoadd .= "\tvalid users = @\"%D" . '\\' . trim($share["ShareGroup"]) . "\"\n";
				} else {
					$user = new User($share['ShareGroup']);
					if ($user->Exists())
						$linestoadd .= "\tvalid users = \"%D" . '\\' . trim($share["ShareGroup"]) . "\"\n";
				}
			}

			$linestoadd .= "\tveto files = /.flexshare*/\n";

			$vfsobject = "";

			if ($share["FileRecycleBin"]) {
				$vfsobject .= " recycle:recycle";
				$linestoadd .= "\trecycle:repository = .trash/%U\n";
				$linestoadd .= "\trecycle:maxsize = 0\n";
				$linestoadd .= "\trecycle:versions = Yes\n";
				$linestoadd .= "\trecycle:keeptree = Yes\n";
				$linestoadd .= "\trecycle:touch = No\n";
				$linestoadd .= "\trecycle:directory_mode = 0775\n";
			}

			if ($share["FileAuditLog"]) {
				$vfsobject .= " full_audit:audit";
				$linestoadd .= "\taudit:prefix = %u\n";
				$linestoadd .= "\taudit:success = open opendir\n";
				$linestoadd .= "\taudit:failure = all\n";
				$linestoadd .= "\taudit:facility = LOCAL5\n";
				$linestoadd .= "\taudit:priority = NOTICE\n";
			}

			if ($vfsobject)
				$linestoadd .= "\tvfs object =$vfsobject\n";

			$linestoadd .= "\n";
		}

		$file->AddLines($linestoadd);

		// Validate smbd configuration before restarting server
		$config_ok = true;

		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(Flexshare::CMD_VALIDATE_SMBD, '-s', false);
		} catch (Exception $e) {
			// TODO: this requires upgrade cleanup from older Samba versions
			// $config_ok = false;
		}

		if ($exitcode != 0) {
			$config_ok = false;
			$output = $shell->GetOutput();
			Logger::Syslog(self::LOG_TAG, "Invalid Samba configuration!");
			foreach($output as $line)
				Logger::Syslog(self::LOG_TAG, $line);
		}

		if ($config_ok) {
			// Delete backups
			if ($backup->Exists())
				$backup->Delete();
		} else {
			// Recover backups
			if ($backup->Exists()) {
				try {
					$backup->MoveTo(self::SMB_VIRTUAL_HOST_PATH . "/" . self::FILE_SMB_VIRTUAL);
				} catch (Exception $e) {
					// Supresss error here...could be same file
				}

			}
			throw new EngineException (FLEXSHARE_LANG_ERRMSG_CONFIG_VALIDATION_FAILED, COMMON_ERROR);
		}

		// A full restart is required to catch file permission changes
		try {
			$isrunning = $samba->GetRunningState();
			if ($isrunning)
				$samba->Restart();
		} catch (Exception $e) {
			// Not fatal
		}
	}

	/**
	 * Create the Postfix aliases configuration file for the specificed flexshare.
	 *
	 * @returns  void
	 * @throws  EngineException
	 */

	function GenerateEmailFlexshares()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (file_exists(COMMON_CORE_DIR . "/api/Aliases.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Aliases.class.php");
			$aliases = new Aliases();
		} else {
			return;
		}

		try {
			$shares = $this->GetShareSummary();
			for ($index = 0; $index < count($shares); $index++) {
				$name = $shares[$index]['Name'];
				$share = $this->GetShare($name);

				if (! isset($share['ShareEnabled']) || ! isset($share['EmailEnabled']) ||
				        ! $share['ShareEnabled'] || ! $share['EmailEnabled']) {
					try {
						$aliases->DeleteAlias(self::PREFIX . $name);
					} catch (Exception $e) {
						// self::Log(COMMON_WARNING, $e->GetMessage(), __METHOD__, __LINE__);
					}
				} else {
					try {
						$aliases->AddAlias(self::PREFIX . $name, array(self::CONSTANT_USERNAME));
					} catch (DuplicateException $e) {
						// Do nothing
					} catch (Exception $e) {
						throw new EngineException ($e->GetMessage(), COMMON_ERROR);
					}
				}
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Initializes flexshare environment.
	 *
	 * @return void
	 */

	function Initialize()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// TODO: if (master)

		try {
			$file = new File(Flexshare::FILE_INITIALIZED);

			if ($file->Exists())
				return;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		try {
			// Generate random password
			$directory = new ClearDirectory();
			$password = $directory->GeneratePassword();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Check to see if flexshare user exists

		$adduser = false;

		try {
			$user = new User(self::CONSTANT_USERNAME);
			$currentinfo = $user->GetInfo();
		} catch (UserNotFoundException $e) {
			$adduser = true;
		}

		// Add or update user account information

		try {
			if ($adduser) {
				$userinfo = array();
				$userinfo['password'] = $password;
				$userinfo['verify'] = $password;
				$userinfo['mailFlag'] = true; // Mail-to-flexshare
				$userinfo['ftpFlag'] = true;  // Anonymous FTP
				$userinfo['lastName'] = "System";
				$userinfo['firstName'] = "Flexshare";
				$userinfo['uid'] = self::CONSTANT_USERNAME;
				$userinfo['homeDirectory'] = self::PATH_ROOT;
				$userinfo['loginShell'] = self::CONSTANT_LOGIN_SHELL;

				$user->Add($userinfo);
			} else {
				$userinfo = array();
				$userinfo['password'] = $password;
				$userinfo['verify'] = $password;

				if (! $currentinfo['mailFlag'])
					$userinfo['mailFlag'] = true;

				if (! $currentinfo['ftpFlag'])
					$userinfo['ftpFlag'] = true;

				$user->Update($userinfo);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Set the password in flexshare
		// Set the initialized file

		try { 
			$this->SetPassword($password, $password);
			$file->Create("root", "root", "0644");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Convenience function.
	 *
	 * @param  int  $port  int representing port
	 * @param  bool  $ssl_flag  boolean flag
	 * @return  int case type
	 */

	function DetermineCase ($port, $ssl_flag)
	{
		if ($port == 80)
			$case = self::CASE_HTTP;
		elseif ($port == 443)
		$case = self::CASE_HTTPS;
		elseif ($ssl_flag)
		$case = self::CASE_CUSTOM_HTTPS;
		else
			$case = self::CASE_CUSTOM_HTTP;
		return $case;
	}

	/**
	 * Generic set routine.
	 *
	 * @private
	 * @param  string  $name  flexshare name
	 * @param  string  $key  key name
	 * @param  string  $value  value for the key
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetParameter($name, $key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Convert carriage returns
		$value = eregi_replace("\n", "", $value);

		// Update tag if it exists
		try {
			$file = new File(self::FILE_CONFIG);
			if ($name == null) {
				$needle = "/^\s*$key\s*=\s*/i";
				$match = $file->ReplaceLines($needle, "$key=$value\n");
			} else {
				$needle = "/^\s*$key\s*=\s*/i";
				$match = $file->ReplaceLinesBetween($needle, "  $key=$value\n", "/<Share $name>/", "/<\/Share>/");
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// If tag does not exist, add it
		try {
			if (! $match && $name == null)
				$file->AddLinesAfter("$key=$value\n", "/#*./");
			elseif (! $match)
			$file->AddLinesAfter("  $key=$value\n", "/<Share $name>/");
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		// Update last modified
		if ($name != null) {
			if (eregi("^Web", $key))
				$lastmod = "WebModified";
			else if (eregi("^Ftp", $key))
				$lastmod = "FtpModified";
			else if (eregi("^File", $key))
				$lastmod = "FileModified";
			else if (eregi("^Email", $key))
				$lastmod = "EmailModified";
			else
				return;

			try {
				$mod = "  " . $lastmod . "=" . time() . "\n";
				$match = $file->ReplaceLinesBetween("/" . $lastmod . "/", $mod, "/<Share $name>/", "/<\/Share>/");
				if (! $match)
					$file->AddLinesAfter($mod, "/<Share $name>/");
			} catch (Exception $e) {
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
			}
		}
	}

	/**
	 * Sets email-based access.
	 *
	 * @param  string  $password  password
	 * @param  string  $verify  verify
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetPassword($password, $verify)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($password != $verify)
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_PASSWORD_MISMATCH);

		if (! $this->IsValidPassword($password))
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_PASSWORD);

		try {
			$file = new File(self::FILE_CONFIG);

			if (! $file->Exists()) {
				$file->Create("root", "root", '0600');
				$file->AddLines("# Flexshare Configuration\n");
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		$this->SetParameter(null, 'FlexsharePW', $password);
	}

	/**
	 * Sets a flex share's description.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $description  flexshare description
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDescription($name, $description)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		if (! $this->IsValidDescription($description)) {
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_DESCRIPTION);
		}

		$this->SetParameter($name, 'ShareDescription', $description);
	}

	/**
	 * Sets a flexshare's group owner.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $group  flexshare group owner
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetGroup($name, $group)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidGroup($group))
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_GROUP);

		if ($this->GetParameter($name, 'ShareGroup') == $group)
			return;

		$this->SetParameter($name, 'ShareGroup', $group);
		$enabled = 0;
		if ($this->GetParameter($name, 'ShareEnabled'))
			$enabled = (int)$this->GetParameter($name, 'ShareEnabled');
		$this->ToggleShare($name, $enabled, true);
	}

	/**
	 * Sets a flex share's root directory.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $directory  flex share directory
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDirectory($name, $directory)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$directory = trim($directory);
		$defaultdir = self::SHARE_PATH . '/' . $name;

		if (!isset($directory) || !$directory)
			$directory = $defaultdir;

		// Validate
		if (! $this->IsValidDir($directory))
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_DIR);

		$this->_UpdateFolderLinks($name, $directory);

		$this->SetParameter($name, 'ShareDir', $directory);
	}

	////////////////////
	//	 W E B	  //
	////////////////////

	/**
	 * Sets the enabled of web-based access.
	 *
	 * @param  bool  $enabled  web enabled
	 * @param  string  $name  flexshare name
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetWebEnabled($name, $enabled)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebEnabled', ($enabled ? 1: 0));
		$share = $this->GetShare($name);
		// If enabled, check e-mail restricts access
		$prevent = true;
		if ($enabled) {
			if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
				$prevent = false;
			if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
				$prevent = false;
			if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
				$prevent = false;
			if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
				$prevent = false;
			if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
				$prevent = false;
		} else {
			$prevent = false;
		}

		if ($enabled && $prevent) {
			$this->SetParameter($name, 'WebEnabled', 0);
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);
		}

		// Disable entire share if all elements are disabled
		if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled']) {
			$this->SetParameter($name, 'ShareEnabled', 0);
		}

		try {
			$this->GenerateWebFlexshares();
		} catch (Exception $e) {
			// Any exception here, go back to initial state
			if ($enabled)
				$this->SetParameter($name, 'WebEnabled', 0);
			else
				$this->SetParameter($name, 'WebEnabled', 1);

			// We want to throw SslExecutionException to help users on UI
			if (get_class($e) == SslExecutionException)
				throw new SslExecutionException ($e->GetMessage(), COMMON_ERROR);
			else
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the server name of web-based access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $server_name  server name
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetWebServerName($name, $server_name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		// --------
		if (! $this->IsValidWebServerName($server_name)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}
		$this->SetParameter($name, 'WebServerName', $server_name);
	}

	/**
	 * Sets whether to allow an index of files to be displayed in browser.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $show_index  boolean flag to determine to show file index
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebShowIndex($name, $show_index)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebShowIndex', $show_index);
	}

	/**
	 * Sets whether to follow sym links.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $follow_symlinks boolean flag to determine to follow sym links
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebFollowSymLinks($name, $follow_symlinks)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebFollowSymLinks', $follow_symlinks);
	}

	/**
	 * Sets whether to allow server side includes.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $ssi  boolean flag to determine whether to allow SSI's
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebAllowSSI($name, $ssi)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebAllowSSI', $ssi);
	}

	/**
	 * Sets whether to allow override of options if .htaccess file is found.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $htaccess  boolean flag to determine whether to allow htaccess override
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebHtaccessOverride($name, $htaccess)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebHtaccessOverride', $htaccess);
	}

	/**
	 * Sets an override flag to use custom port on the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $override_port  boolean flag
	 * @param  int  $port  int representing port
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetWebOverridePort($name, $override_port, $port)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($override_port && ($port == 80 || $port == 443)) {
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_NON_CUSTOM_PORT);
		}
		$inuse_ports = array();
		$info = $this->GetShare($name);
		$ssl = $info['WebReqSsl'];
		$shares = $this->GetShareSummary();
		foreach ($shares as $share) {
			$info = $this->GetShare($share['Name']);
			if ($name != $share['Name'] && $ssl != $info['WebReqSsl'])
				$inuse_ports[] = $info['WebPort'];
		}
		if ($override_port && (in_array($port, $this->bad_ports) || in_array($port, $inuse_ports))) {
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_PORT_IN_USE);
		}
		$this->SetParameter($name, 'WebOverridePort', $override_port);
		$this->SetParameter($name, 'WebPort', $port);
	}

	/**
	 * Sets the require SSL flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $req_ssl  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebReqSsl($name, $req_ssl)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebReqSsl', $req_ssl);
	}

	/**
	 * Sets the require authentication flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $req_auth  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebReqAuth($name, $req_auth)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// If no auth required, check e-mail restricts access
		$prevent = true;
		if (!$req_auth) {
			$share = $this->GetShare($name);
			if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
				$prevent = false;
			if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
				$prevent = false;
			if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
				$prevent = false;
			if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
				$prevent = false;
			if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
				$prevent = false;
		} else {
			$prevent = false;
		}

		if ($prevent)
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

		$this->SetParameter($name, 'WebReqAuth', $req_auth);
	}

	/**
	 * Sets the realm name of web-based access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $realm  a realm name
	 * @returns  void
	 * @throws  ValidationException, EngineException
	 */

	function SetWebRealm($name, $realm)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		// --------
		if (! $this->IsValidWebRealm($realm)) {
			throw new ValidationException(FLEXSHARE_LANG_ERRMSG_INVALID_WEB_REALM);
		}

		$this->SetParameter($name, 'WebRealm', $realm);
	}

	/**
	 * Sets the access interface for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  int  $access  Intranet, Internet or Any
	 * @returns  void
	 */

	function SetWebAccess($name, $access)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// If web access is ALL, check e-mail restricts access
		$prevent = true;
		if ((int)$access == self::ACCESS_LAN) {
			$share = $this->GetShare($name);
			if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
				$prevent = false;
			if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
				$prevent = false;
			if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
				$prevent = false;
			if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
				$prevent = false;
			if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
				$prevent = false;
		} else {
			$prevent = false;
		}

		if ($prevent)
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

		$this->SetParameter($name, 'WebAccess', $access);
	}

	/**
	 * Sets the groups allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  array  $access  group access array
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebGroupAccess($name, $access)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'WebGroupAccess', implode(' ', $access));
	}

	/**
	 * Sets parameter allowing PHP executeon on the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $web_php  PHP enabled or not
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebPhp($name, $web_php)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// If PHP enabled, check e-mail restricts access
		$prevent = true;
		if ($web_php) {
			$share = $this->GetShare($name);
			if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
				$prevent = false;
			if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
				$prevent = false;
			if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
				$prevent = false;
			if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
				$prevent = false;
			if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
				$prevent = false;

		} else {
			$prevent = false;
		}

		if ($prevent)
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

		$this->SetParameter($name, 'WebPhp', $web_php);
	}

	/**
	 * Sets parameter allowing CGI executeon on the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $web_cgi  CGI enabled or not
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetWebCgi($name, $web_cgi)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// If cgi enabled, check e-mail restricts access
		$prevent = true;
		if ($web_cgi) {
			$share = $this->GetShare($name);
			if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
				$prevent = false;
			if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
				$prevent = false;
			if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
				$prevent = false;
			if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
				$prevent = false;
			if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
				$prevent = false;
		} else {
			$prevent = false;
		}

		if ($prevent)
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

		$this->SetParameter($name, 'WebCgi', $web_cgi);
	}

	////////////////////
	//	 F T P	  //
	////////////////////

	/**
	 * Sets the enabled of ftp-based access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $enabled  ftp enabled
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpEnabled($name, $enabled)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpEnabled', $enabled);
		$share = $this->GetShare($name);
		// Disable entire share if all elements are disabled
		if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled']) {
			$this->SetParameter($name, 'ShareEnabled', 0);
		}
		try {
			$this->GenerateFtpFlexshares();
		} catch (Exception $e) {
			// Any exception here, go back to initial state
			if ($enabled)
				$this->SetParameter($name, 'FtpEnabled', 0);
			else
				$this->SetParameter($name, 'FtpEnabled', 1);
			// We want to throw SslExecutionException to help users on UI
			if (get_class($e) == SslExecutionException)
				throw new SslExecutionException ($e->GetMessage(), COMMON_ERROR);
			else
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets the server URL of FTP based access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $server_url  server URL
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpServerUrl($name, $server_url)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		// --------
		if (! $this->IsValidFtpServerUrl($server_url)) {
			throw new EngineException(FLEXSHARE_LANG_ERRMSG_INVALID_SERVER_URL, COMMON_ERROR);
		}

		$this->SetParameter($name, 'FtpServerUrl', $server_url);
	}

	/**
	 * Sets an override flag to use custom port on the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $override_port  boolean flag
	 * @param  int  $port  port FTP listens on for this flexshare
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpOverridePort($name, $override_port, $port)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($override_port && ($port == self::DEFAULT_PORT_FTP || $port == self::DEFAULT_PORT_FTPS)) {
			throw new EngineException(FLEXSHARE_LANG_ERRMSG_NON_CUSTOM_PORT, COMMON_ERROR);
		}
		if ($override_port && ($port == 21 || $port == 990)) {
			throw new EngineException(FLEXSHARE_LANG_ERRMSG_CANNOT_USE_DEFAULT_PORTS, COMMON_ERROR);
		}
		if ($override_port && $port < 1024) {
			throw new EngineException(FLEXSHARE_LANG_ERRMSG_INVALID_PORT, COMMON_ERROR);
		}
		// Find all ports and see if any conflicts with n-1
		if ($override_port) {
			$shares = $this->GetShareSummary();
			for ($index = 0; $index < count($shares); $index++) {
				$share = $this->GetShare($shares[$index]['Name']);
				if ($share['Name'] != $name) {
					if ((int)$share["FtpPort"] == ($port - 1)) {
						throw new EngineException(FLEXSHARE_LANG_ERRMSG_PORT_CONFLICT, COMMON_ERROR);
					} else if (((int)$share["FtpPort"] -1) == $port) {
						throw new EngineException(FLEXSHARE_LANG_ERRMSG_PORT_CONFLICT, COMMON_ERROR);
					}
				}
			}
		}
		$this->SetParameter($name, 'FtpOverridePort', $override_port);
		$this->SetParameter($name, 'FtpPort', $port);
	}

	/**
	 * Sets the allow passive port (PASV) flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $allow_passive  boolean flag
	 * @param  int  $port_min  minimum port range
	 * @param  int  $port_max  maximum port range
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpAllowPassive($name, $allow_passive, $port_min = self::FTP_PASV_MIN, $port_max = self::FTP_PASV_MAX)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($allow_passive && !$this->IsValidPassivePortRange($port_min, $port_max)) {
			$errors = $this->GetValidationErrors();
			throw new EngineException($errors[0], COMMON_ERROR);
		}

		$this->SetParameter($name, 'FtpAllowPassive', $allow_passive);

		if ($allow_passive) {
			$this->SetParameter($name, 'FtpPassivePortMin', $port_min);
			$this->SetParameter($name, 'FtpPassivePortMax', $port_max);
		}
	}

	/**
	 * Sets the require SSL flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $req_ssl  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpReqSsl($name, $req_ssl)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, "FtpReqSsl", $req_ssl);
	}

	/**
	 * Sets the require authentication flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $req_auth  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpReqAuth($name, $req_auth)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpReqAuth', $req_auth);
	}

	/**
	 * Sets the FTP owner.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $owner  owner
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpUserOwner($name, $owner)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpUserOwner', $owner);
	}

	/**
	 * Sets the greeting message for ftp-based group access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $greeting  greeting displayed on user login
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpGroupGreeting($name, $greeting)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpGroupGreeting', $greeting);
	}

	/**
	 * Sets the groups allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  array  $access  group access array
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpGroupAccess($name, $access)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpGroupAccess', implode(' ', $access));
	}

	/**
	 * Sets the groups ownership of this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $owner  group owner
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpGroupOwner($name, $owner)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpGroupOwner', $owner);
	}

	/**
	 * Sets the group permission allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  int  $permission  read/write permissions extended to useers with group access
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpGroupPermission($name, $permission)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpGroupPermission', $permission);
	}

	/**
	 * Sets the group umask for this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $umask  umask
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpGroupUmask($name, $umask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = "0" . (int)$umask['owner'] . "" . (int)$umask['group'] . "" . (int)$umask['world'];
		$this->SetParameter($name, 'FtpGroupUmask', $value);
	}

	/**
	 * Sets the greeting message for ftp-based access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $anonymous  allow anonymous login
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpAllowAnonymous($name, $anonymous)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpAllowAnonymous', $anonymous);
	}

	/**
	 * Sets the anonymous permission allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  int  $permission  read/write permissions for anonymous users
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpAnonymousPermission($name, $permission)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpAnonymousPermission', $permission);
	}

	/**
	 * Sets the greeting message for ftp-based anonymous access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $greeting  greeting displayed on anonymous login
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpAnonymousGreeting($name, $greeting)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FtpAnonymousGreeting', $greeting);
	}

	/**
	 * Sets the anonymous umask for this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $umask  umask
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFtpAnonymousUmask($name, $umask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = "0" . (int)$umask['owner'] . "" . (int)$umask['group'] . "" . (int)$umask['world'];
		$this->SetParameter($name, 'FtpAnonymousUmask', $value);
	}

	////////////////////////////////
	//	F I L E   (S A M B A)   //
	////////////////////////////////

	/**
	 * Sets the audit log state.
	 *
	 * @param string $name flexshare name
	 * @param bool $state state of audit logging
	 * @return void
	 * @throws EngineException
	 */

	function SetFileAuditLog($name, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileAuditLog', $state);
	}

	/**
	 * Sets the audit log state.
	 *
	 * @param string $name flexshare name
	 * @param bool $state state of audit logging
	 * @return void
	 * @throws EngineException
	 */

	function SetFileBrowseable($name, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileBrowseable', $state);
	}

	/**
	 * Sets the enabled of file-based (SAMBA) access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $enabled  file enabled
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFileEnabled($name, $enabled)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileEnabled', $enabled);
		$share = $this->GetShare($name);

		// Disable entire share if all elements are disabled
		if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled'])
			$this->SetParameter($name, 'ShareEnabled', 0);

		try {
			$this->GenerateFileFlexshares();
		} catch (Exception $e) {
			// Any exception here, go back to initial state
			if ($enabled)
				$this->SetParameter($name, 'FileEnabled', 0);
			else
				$this->SetParameter($name, 'FileEnabled', 1);

			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets file sharing comment for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $comment  a flexshare/fileshare comment
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFileComment($name, $comment)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileComment', $comment);
	}

	/**
	 * Sets file sharing public access flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $public_access  a boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFilePublicAccess($name, $public_access)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FilePublicAccess', $public_access);
	}

	/**
	 * Sets the groups ownership of this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $owner  group owner
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFileGroupOwner($name, $owner)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileGroupOwner', $owner);
	}

	/**
	 * Sets file sharing permissions for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $permission  a valid permission
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFilePermission($name, $permission)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FilePermission', $permission);
	}

	/**
	 * Sets the groups allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  array  $access  group access array
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFileGroupAccess($name, $access)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileGroupAccess', implode(' ', $access));
	}

	/**
	 * Sets the Samba create mask for this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $ mask  mask
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetFileCreateMask($name, $mask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = "0" . (int)$mask['owner'] . "" . (int)$mask['group'] . "" . (int)$mask['world'];
		$this->SetParameter($name, 'FileCreateMask', $value);
	}

	/**
	 * Sets the recycle bin state.
	 *
	 * @param string $name flexshare name
	 * @param bool $state state of recycle bin option
	 * @return void
	 * @throws EngineException
	 */

	function SetFileRecycleBin($name, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'FileRecycleBin', $state);
	}

	////////////////////
	//	E M A I L   //
	////////////////////

	/**
	 * Sets email-based access.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $enabled   email enabled
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailEnabled($name, $enabled)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailEnabled', $enabled);
		$share = $this->GetShare($name);
		// If web access is ALL, check e-mail restricts access
		$prevent = true;
		if ($enabled) {
			if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
				$prevent = false;
			if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
				$prevent = false;
			if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
				$prevent = false;
			if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
				$prevent = false;
			if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
				$prevent = false;
		} else {
			$prevent = false;
		}

		if ($prevent)
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

		// Disable entire share if all elements are disabled
		if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled']) {
			$this->SetParameter($name, 'ShareEnabled', 0);
		}

		try {
			$this->GenerateEmailFlexshares();
		} catch (Exception $e) {
			// Any exception here, go back to initial state
			if ($enabled)
				$this->SetParameter($name, 'EmailEnabled', 0);
			else
				$this->SetParameter($name, 'EmailEnabled', 1);
			// We want to throw SslExecutionException to help users on UI
			if (get_class($e) == SslExecutionException)
				throw new SslExecutionException ($e->GetMessage(), COMMON_ERROR);
			else
				throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

	}

	/**
	 * Sets email access status for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $enabled  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailPolicy($name, $policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailPolicy', $policy);
	}

	/**
	 * Sets the save policy for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  int  $save  save policy
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailSave($name, $save)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailSave', $save);
	}

	/**
	 * Sets the require signature flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $req_signature  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailReqSignature($name, $req_signature)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailReqSignature', $req_signature);
	}

	/**
	 * Sets the groups ownership for this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  array  $owner  group owner
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailGroupOwner($name, $owner)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailGroupOwner', $owner);
	}

	/**
	 * Sets the groups allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  array  $access  group access array
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailGroupAccess($name, $access)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailGroupAccess', implode(' ', $access));
	}

	/**
	 * Sets the groups allowed to access this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  array  $acl  access control list
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailAcl($name, $acl)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailAcl', implode(' ', $acl));
	}

	/**
	 * Sets the restrict access flag for the flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  bool  $restrict_access  boolean flag
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailRestrictAccess($name, $restrict)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// If no restrictions, check that user is not running a wide open web server with PHP/cgi
		$prevent = true;
		if (!$restrict) {
			$share = $this->GetShare($name);
			if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
				$prevent = false;
			if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
				$prevent = false;
			if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
				$prevent = false;
			if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
				$prevent = false;
		} else {
			$prevent = false;
		}

		if ($prevent)
			throw new EngineException(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);
		$this->SetParameter($name, 'EmailRestrictAccess', $restrict);
	}

	/**
	 * Sets email-based default directory.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $dir  root directory path
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailDir($name, $dir)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailDir', $dir);
	}

	/**
	 * Sets email notification service.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $notify  notify email
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailNotify($name, $notify)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetParameter($name, 'EmailNotify', $notify);
	}

	/**
	 * Sets the save mask for this flexshare.
	 *
	 * @param  string  $name  flexshare name
	 * @param  string  $mask  mask
	 * @returns  void
	 * @throws  EngineException
	 */

	function SetEmailSaveMask($name, $mask)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$value = "0" . (int)$mask['owner'] . "" . (int)$mask['group'] . "" . (int)$mask['world'];
		$this->SetParameter($name, 'EmailSaveMask', $value);
	}

	/////////////////////////////////
	//	G E T   M E T H O D S	//
	/////////////////////////////////

	/**
	 * Generic get routine.
	 *
	 * @private
	 * @param  string  $name  flexshare name
	 * @param  string  $key  key name
	 * @returns  string
	 * @throws  EngineException, FlexshareParameterNotFoundException
	 */

	function GetParameter($name, $key)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);

			if ($name == null)
				$retval = $file->LookupValue("/^\s*$key\s*=\s*/i");
			else
				$retval = $file->LookupValueBetween("/^\s*$key\s*=\s*/i", "/<Share $name>/", "/<\/Share>/");
		} catch (FileNotFoundException $e) {
			throw new FlexshareParameterNotFoundException($name);
		} catch (FileNoMatchException $e) {
			throw new FlexshareParameterNotFoundException($name);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_INFO);
		}

		return $retval;
	}

	/**
	 * Gets the global password.
	 *
	 * @returns  string
	 * @throws  EngineException
	 */

	function GetPassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$passwd = $this->GetParameter(null, 'FlexsharePW');

		return $passwd;
	}

	/**
	 * Gets email address for this share for email based access.
	 *
	 * @param  string  $name  flexshare name
	 * @returns  string
	 * @throws  EngineException
	 */

	function GetEmailAddress($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			if (! file_exists(COMMON_CORE_DIR . "/api/Postfix.class.php"))
				return;

			require_once(COMMON_CORE_DIR . "/api/Postfix.class.php");
			$postfix = new Postfix();
			$email = self::PREFIX . $name . "@" . $postfix->GetDomain();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}
		return $email;
	}

	/**
	 * Check mail POP accounts for flexshares.
	 *
	 * @param  bool  $view  a boolean flag indicating access to this method via webconfig (ie. user)
	 * @param  array  $action  array containing message IDs and actions to do on them (delete, save etc.)
	 * @returns  array containing limited information on emails in queue
	 * @throws  EngineException
	 */

	function CheckMessages($view, $action = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! file_exists(COMMON_CORE_DIR . "/api/Postfix.class.php")) {
			// Don't log this...a script using this function is called every 5 minutes
			return;
		}

		if (! file_exists(COMMON_CORE_DIR . "/api/Cyrus.class.php")) {
			// Don't log this...a script using this function is called every 5 minutes
			return;
		}

		$req_check = false;

		$msg = array();
		// Set an empty array if null
		if ($action == null || ! $action)
			$action = array();
		$shares = $this->GetShareSummary();
		// For convenience, we setup array containing required info for "FetchEmail" function
		for ($index = 0; $index < count($shares); $index++) {
			$share = $this->GetShare($shares[$index]['Name']);
			$share['EmailGroupOwner'] = $share['ShareGroup'];
			$share['EmailGroupAccess'] = $share['ShareGroup'];
			if (!isset($share['EmailDir'])) $share['EmailDir'] = 0;
			if (!isset($share['EmailRestrictAccess'])) $share['EmailRestrictAccess'] = 0;
			if (!isset($share['EmailReqSignature'])) $share['EmailReqSignature'] = 0;
			if (!isset($share['EmailAcl'])) $share['EmailAcl'] = 0;
			if (!isset($share['EmailPolicy'])) $share['EmailPolicy'] = 0;
			if (!isset($share['EmailSave'])) $share['EmailSave'] = 0;
			if (!isset($share['EmailSaveMask'])) $share['EmailSaveMask'] = 0;
			if (!isset($share['EmailNotify'])) $share['EmailNotify'] = 0;
			$newarray[$shares[$index]['Name']]['dir'] = $share['EmailDir'];
			$newarray[$shares[$index]['Name']]['restrict_access'] = $share['EmailRestrictAccess'];
			$newarray[$shares[$index]['Name']]['req_signature'] = $share['EmailReqSignature'];
			$newarray[$shares[$index]['Name']]['group_owner'] = $share['EmailGroupOwner'];
			$newarray[$shares[$index]['Name']]['group_access'] = $share['EmailGroupAccess'];
			$newarray[$shares[$index]['Name']]['acl'] = $share['EmailAcl'];
			$newarray[$shares[$index]['Name']]['policy'] = $share['EmailPolicy'];
			$newarray[$shares[$index]['Name']]['save'] = $share['EmailSave'];
			$newarray[$shares[$index]['Name']]['mask'] = $share['EmailSaveMask'];
			$newarray[$shares[$index]['Name']]['notify'] = $share['EmailNotify'];

			// Enabled?
			if (isset($share['ShareEnabled']) && $share['ShareEnabled'] &&
			        isset($share['EmailEnabled']) && $share['EmailEnabled']
			   )
				$req_check = true;
		}

		// May not need to fetch mail
		if (! $req_check)
			return $msg;

		// This may take a while...don't timeout.
		set_time_limit(0);
		$msg = $this->FetchEmail($view, $action, $newarray);
		set_time_limit(30);
		return $msg;
	}

	/**
	 * Check mail POP accounts for flexshares.
	 *
	 * @param  bool  $view  a boolean flag indicating access to this method via webconfig (ie. user)
	 * @param  array  $action  array containing message IDs and actions to do on them (delete, save etc.)
	 * @returns  array containing message information
	 * @throws  EngineException
	 */

	function FetchEmail($view, $action, $shares)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$input = '';
		$mymessage = array();
		$files = array();
		$mailing_list = array();
		require_once(COMMON_CORE_DIR . "/api/Postfix.class.php");
		require_once(COMMON_CORE_DIR . "/api/Cyrus.class.php");

		// Get password for mailserver authentication
		$passwd = $this->GetParameter(null, 'FlexsharePW');

		$cyrus = new Cyrus();
		$mbox = @imap_open("{" . self::MBOX_HOSTNAME . ":143/notls}INBOX", self::CONSTANT_USERNAME, $passwd);
		$ignore = imap_errors();

		if (! $mbox)
			return;

		// Noisy
		// Logger::Syslog(self::LOG_TAG, "Flexshare POP/IMAP login successful..." . imap_num_msg($mbox) . " messages retrieved");
		for ($index = 1; $index <= imap_num_msg($mbox); $index++) {
			$encrypted = false;
			$headers = imap_headerinfo($mbox, $index);
			unset($files);
			$flex_address = null;
			$addresses = array();
			if (is_array($headers->to))
				$addresses = $headers->to;
			if (is_array($headers->cc))
				$addresses = array_merge($addresses, $headers->cc);
			foreach ($addresses as $address) {
				if (preg_match("/^" . self::PREFIX . "(.*)$/", $address->mailbox, $match)) {
					$flex_address = $match[1];
					break;
				}
			}

			// See if we have a matching flexshare defined
			if ($flex_address == null || ! isset($shares[$flex_address])) {
				Logger::Syslog(self::LOG_TAG, "Flexshare does not exist...Subject: " . $headers->subject);
				imap_delete($mbox, $index);
				unset($mymessage[$index]);
				continue;
			}
			$mymessage[$index]['Share'] = $flex_address;
			$mymessage[$index]['From'] = $headers->fromaddress;
			$mymessage[$index]['Reply-To'] = $headers->reply_toaddress;
			$mymessage[$index]['Date'] = $headers->udate;
			$mymessage[$index]['Subject'] = $headers->subject;
			$mymessage[$index]['Size'] = $headers->Size;
			// Set username for file permissions
			$username = $headers->from[0]->mailbox;

			$rawheader = explode("\n", imap_fetchheader($mbox, $index));

			if (is_array($rawheader) && count($rawheader)) {
				$head = array();
				$arg = array();
				foreach($rawheader as $line) {
					eregi("^([^:]*): (.*)", $line, $arg);
					$head[$arg[1]] = $arg[2];
				}
			}
			if (isset($head['Content-Type']) && eregi('x-pkcs7-mime', $head['Content-Type']))
				$encrypted = true;
			// Set encrypted flag for message summary
			$mymessage[$index]['Ssl'] = $encrypted;

			// User deleted...no use continuing
			if (isset($action[$index]) && $action[$index] == 'delete') {
				Logger::Syslog(self::LOG_TAG, "User initiated deletion...Subject: " . $headers->subject);
				imap_delete($mbox, $index);
				unset($mymessage[$index]);
				continue;
			}

			// See if restricted access is enabled
			if ($shares[$flex_address]['restrict_access']) {
				$msg = tempnam("/tmp", self::PREFIX);
				$file = new File($msg);
				if ($file->Exists())
					$file->Delete();
				$file->Create('webconfig', 'webconfig', "0600");
				$file->DumpContentsFromArray($rawheader);
				$file->AddLines(imap_body($mbox, $index));
				$ssl = new Ssl();
				if ($shares[$flex_address]['req_signature']) {
					// Verify signature
					if (! $ssl->VerifySmime($file->GetFilename())) {
						$file->Delete();
						imap_delete($mbox, $index);
						unset($mymessage[$index]);
						continue;
					}
				}
				$file->Delete();
				// Check ACL
				$acl = array();
				$postfix = new Postfix();
				$hostname = $postfix->GetHostname();
				// Get users in groups
				$groups = explode(" ", $shares[$flex_address]['group_access']);
				$additional = explode(" ", $shares[$flex_address]['acl']);
				$acl = array();
				foreach ($groups as $group_name) {
					$group = new Group($group_name);
					$members = $group->GetMembers();
					foreach ($members as $user) {
						$acl[] = "$user@$hostname";
					}
					$acl = array_merge($acl, $additional);
				}
				if (! in_array($headers->from[0]->mailbox . "@" . $headers->from[0]->host, $acl)) {
					Logger::Syslog(self::LOG_TAG, "ACL restricts attachments...Subject: " . $headers->subject);
					imap_delete($mbox, $index);
					unset($mymessage[$index]);
					continue;
				}
			}

			$mask = 664;

			// Determine directory to save files to - Use /flex-upload as default
			$dir = self::SHARE_PATH . "/$flex_address/" . self::DIR_MAIL_UPLOAD . '/';
			$match = array();

			if ((int)$shares[$flex_address]['dir'] == self::EMAIL_SAVE_PATH_ROOT) {
				$dir = self::SHARE_PATH . "/" . $flex_address;
			} else if ((int)$shares[$flex_address]['dir'] == self::EMAIL_SAVE_PATH_PARSE_SUBJECT) {
				$regex = "^Dir[[:space:]]*=[[:space:]]*([A-Za-z0-9\-\_\/\. ]+$)";
				if (eregi($regex, $mymessage[$index]['Subject'], $match)) {
					$dir = self::SHARE_PATH . "/" . $flex_address . "/" . $match[1];
				}
			}

			$folder = new Folder($dir, true);
			// Make sure directory exists
			if (! $folder->Exists())
				$folder->Create(self::CONSTANT_USERNAME, self::CONSTANT_USERNAME, "0775");
			// Last check...make sure we are at least in /var/flexshare/shares/<name>
			if (! eregi(self::SHARE_PATH . "/" . $flex_address, $folder->GetFoldername())) {
				// We're no longer in share directory filesystem...override with mail dir
				$folder = new Folder(self::SHARE_PATH . "/" . $flex_address . "/" . self::DIR_MAIL_UPLOAD . "/", true);
			}


			$structure = imap_fetchstructure($mbox, $index);
			$mime = new Mime();
			$parts = $mime->GetParts($structure);

			if (! $view && ! $shares[$flex_address]['save']) {
				// CRON is calling us
				// Ignore files called by cron and received longer than 5 minutes ago
				// Can't use flags, since POP does not support \\SEEN.
				if (((int)$mymessage[$index]['Date'] + 5*60) > time()) {
					$summary = array(
					               'Share' => $mymessage[$index]['Share'],
					               'Subject' => $mymessage[$index]['Subject'],
					               'From' => $mymessage[$index]['From'],
					               'Reply-To' => $mymessage[$index]['Reply-To'],
					               'Date' => $mymessage[$index]['Date'],
					               'Size' => $this->GetFormattedBytes((int)$mymessage[$index]['Size'], 1)
					           );
					foreach ($parts as $pid => $part) {
						if (isset($part['disposition']) && ereg("^attachment$|^inline$", $part['disposition'])) {
							// Ignore signatures
							if ($part['type'] == "application/x-pkcs7-signature")
								continue;
							$summary['Files'][] = $part['name'] . " (" . $this->GetFormattedBytes((int)$part['size'], 1) . ")";
						}
					}
					$mailing_list[$shares[$flex_address]['notify']][] = $summary;
				}
				continue;
			} else if (isset($action[$index]) && $action[$index] != "save") {
				// User has not click save
				continue;
			}

			// Delete any messages without attachments
			if (count($parts) == 0) {
				$log_from = $mymessage[$index]['Reply-To'];
				unset($mymessage[$index]);
				imap_delete($mbox, $index);
				continue;
			}

			// Check to see if share dir is users' home dir
			if (ereg("^/home/(.*$)", $this->GetParameter($flex_address, 'ShareDir'), $match)) {
				$user = new User($match[1]);
				if ($user->Exists()) {
					$username = $match[1];
				} else {
					$user = new User($username);
					if (!$user->Exists())
						$username = self::CONSTANT_USERNAME;
				}
			} else {
				// Check to see if $username is on the filesystem
				$user = new User($username);
				// If user does not exist, default to 'flexshare.flexshare' for file permission
				if (!$user->Exists())
					$username = self::CONSTANT_USERNAME;
			}

			// Set group owner
			$groupname = $shares[$flex_address]['group_owner'];
			$group = new Group($groupname);

			foreach ($parts as $pid => $part) {
				// Only save if an attachment
				try {
					if (isset($part['disposition']) && ereg("^attachment$|^inline$", $part['disposition'])) {
						// Ignore signatures
						if ($part['type'] == "application/x-pkcs7-signature")
							continue;
						// Check filename exists in attachment
						if (!isset($part['name']) || $part['name'] == '')
							continue;
						$path_and_filename = $dir . "/" . $part['name'];
						if ($shares[$flex_address]['save'] || (isset($action[$index]) && $action[$index] == "save")) {
							$file = new File($path_and_filename, true);
							if (! $file->Exists()) {
								$file->Create($username, $groupname, $mask);
								if ($part['encoding'] == 'base64')
									$file->AddLines(base64_decode(imap_fetchbody($mbox, $index, $pid)));
								else
									$file->AddLines(imap_fetchbody($mbox, $index, $pid));
								$files[] = $part['name'];
							} else {
								$policy = (int)$shares[$flex_address]['policy'];
								switch ($policy) {
								case self::POLICY_DONOT_WRITE:
									break;
								case self::POLICY_OVERWRITE:
									$file->Delete();
									$file->Create($username, $groupname, $mask);
									if ($part['encoding'] == 'base64')
										$file->AddLines(base64_decode(imap_fetchbody($mbox, $index, $pid)));
									else
										$file->AddLines(imap_fetchbody($mbox, $index, $pid));
									$files[] = $part['name'];
									break;
								case self::POLICY_BACKUP:
									$file->MoveTo($path_and_filename.date(".mdy.His",time()).".bak");
									$file = new File($path_and_filename, true);
									$file->Create($username, $groupname, $mask);
									if ($part['encoding'] == 'base64')
										$file->AddLines(base64_decode(imap_fetchbody($mbox, $index, $pid)));
									else
										$file->AddLines(imap_fetchbody($mbox, $index, $pid));
									$files[] = $part['name'];
									break;
								}
							}
							$file->Chmod("0664");
							Logger::Syslog(self::LOG_TAG, "Attachment saved as " . $path_and_filename);
						}
					}
				} catch (Exception $e) {
					Logger::Syslog(self::LOG_TAG, "Error occurred (" . $e->getMessage() . ") Subject: " . $headers->subject);
					continue;
				}
			}
			// Delete messages
			if ($shares[$flex_address]['save'] || (isset($action[$index]) && $action[$index] == "save")) {
				$log_from = $mymessage[$index]['Reply-To'];
				imap_delete($mbox, $index);
				$mymessage[$index]['SavedFiles'] = $files;
			}
		}
		imap_close($mbox, CL_EXPUNGE);
		// Check if notification requires sending
		if (count($mailing_list) > 0) {
			$hostname = new Hostname();
			$mailer = new Mailer();
			foreach ($mailing_list as $address=>$emails) {
				$mailer->Clear();
				$body = FLEXSHARE_LANG_EMAIL_NOTIFICATION_PENDING_APPROVAL . ":\n\n";
				foreach ($emails as $email) {
					$fields = array(
					              "Share" => FLEXSHARE_LANG_SHARE,
					              "Subject" => FLEXSHARE_LANG_SUBJECT,
					              "From" => LOCALE_LANG_FROM,
					              "Date" => LOCALE_LANG_DATE,
					              "Files" => FLEXSHARE_LANG_ATTACHMENTS);
					foreach ($fields as $field => $display) {
						if ($field == "Date") {
							$ntptime = new NtpTime();
							date_default_timezone_set($ntptime->GetTimeZone());

							$body .= str_pad("$display:", 20, " ", STR_PAD_RIGHT) . date("F d, Y H:i", $email[$field])."\n";
						} else if ($field == "Files") {
							$counter = 1;
							if (isset($email['Files']) && is_array($email['Files'])) {
								foreach ($email['Files'] as $filedetails) {
									if ($counter == 1)
										$body .= str_pad("$display:", 20, " ", STR_PAD_RIGHT) . $filedetails . "\n";
									else
										$body .= str_pad(" ", 21, " ", STR_PAD_RIGHT) . $filedetails . "\n";
									$counter++;
								}
							}
						} else {
							$body .= str_pad("$display:", 20, " ", STR_PAD_RIGHT) . $email[$field] . "\n";
						}
						if ($field == "Files")
							$body .= "\n";
					}
				}
				try {
					$mailer->AddRecipient($address);
					$mailer->SetSubject(FLEXSHARE_LANG_EMAIL_NOTIFICATION_SUBJECT . " - " . $hostname->Get());
					$mailer->SetBody($body);
					$mailer->SetSender("flex@" . $hostname->Get());
					$mailer->Send();
				} catch (Exception $e) {
					throw new EngineException ($e->GetMessage(), COMMON_ERROR);
				}
			}
		}

		return $mymessage;
	}

	///////////////////////////////
	//	V A L I D A T I O N	//
	///////////////////////////////

	/**
	 * Validation routine for a name.
	 *
	 * @param  string  $name  flexshare name
	 * @returns  boolean
	 */

	function IsValidName($name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\_]+)$/", $name))
			return true;

		$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_NAME, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for a group.
	 *
	 * @param  string  $group  a system group
	 * @returns  boolean
	 */

	function IsValidGroup($group)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groupobj = new Group($group);

		try {
			$exists = $groupobj->Exists();
			if (! $exists) {
				$user = new User($group);
				$exists = $user->Exists();
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		if ($exists) {
			return true;
		} else {
			$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_GROUP, __METHOD__, __LINE__);
			return false;
		}
	}

	/**
	 * Validation routine for password.
	 *
	 * @param  string  $password  password
	 * @returns  boolean
	 */

	function IsValidPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// TODO: Watch out for auto-generated base64 password
		if (true)
			return true;

		// TODO...probably should use users class.
		if (preg_match("/^([A-Za-z0-9i!-+]+)$/", $password))
			return true;

		$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_PASSWORD, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for description.
	 *
	 * @param  string  $description  flexshare description
	 * @returns  boolean
	 */

	function IsValidDescription($description)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\_\' ]*)$/", $description))
			return true;

		$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_DESCRIPTION, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for directory path.
	 *
	 * @param  string  $dir  directory path for flexshare
	 * @returns  boolean
	 */

	function IsValidDir($dir)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\_\/]+)$/", $dir))
			return true;

		$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_DIR, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for web server name.
	 *
	 * @param  string  $server_name  web server name
	 * @returns  boolean
	 */

	function IsValidWebServerName($server_name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
			return;

		require_once("Httpd.class.php");

		$httpd = new Httpd();
		if ($httpd->IsValidServerName($server_name))
			return true;
		$errors = $httpd->GetValidationErrors();
		$this->AddValidationError($errors[0], __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for web realm.
	 *
	 * @param  string  $realm  web realm
	 * @returns  boolean
	 */

	function IsValidWebRealm($realm)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\_\/\' ]+)$/", $realm))
			return true;

		$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_WEB_REALM, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for FTP passive ports.
	 *
	 * @param  int  $port_min  Port start
	 * @param  int  $port_max  Port end
	 * @returns  boolean
	 */

	function IsValidPassivePortRange($port_min, $port_max)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();
		if (! $network->IsValidPortRange($port_min, $port_max)) {
			$this->AddValidationError(NETWORK_LANG_ERRMSG_PORT_RANGE_INVALID, __METHOD__, __LINE__);
			return false;
		}
		if ($port_min < 1023 || $port_max < 1023) {
			$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_PASSIVE_PORT_BELOW_MIN, __METHOD__, __LINE__);
			return false;
		}
		return true;
	}

	/**
	 * Validation routine for FTP server URL.
	 *
	 * @param  string  $server_url  FTP server URL
	 * @returns  boolean
	 */

	function IsValidFtpServerUrl($server_url)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = New Network();
		if (!$network->IsValidHostname($server_url)) {
			$this->AddValidationError(FLEXSHARE_LANG_ERRMSG_INVALID_SERVER_URL, __METHOD__, __LINE__);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Formats a value into a human readable byte size.
	 * @param  stirng  $input  the value
	 * @param  int  $dec  number of decimal places
	 *
	 * @returns  string
	 */

	function GetFormattedBytes($input, $dec)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$prefix_arr = array(' B', 'KB', 'MB', 'GB', 'TB');
		$value = round($input, $dec);
		$i=0;
		while ($value>1024) {
			$value /= 1024;
			$i++;
		}
		$display = round($value, $dec) . ' ' . $prefix_arr[$i];
		return $display;
	}

	/**
	 * Sanity checks the group ownership.
	 *
	 * Too much command line hacking will leave the group ownership of
	 * files out of whack.  This method fixes this common issue.
	 *
	 * @param string $directory share directory
	 * @param group $group group name
	 * @return void
	 */

	protected function _UpdateFolderAttributes($directory, $group)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!$this->IsValidDir($directory)  || !$this->IsValidGroup($group))
			return;
		
		try {
			$options['background'] = true;

			$shell = new ShellExec();
			$shell->Execute(Flexshare::CMD_UPDATE_PERMS, $directory . ' ' . $group, true, $options);
		} catch (Exception $e) {
			// Not fatal
		}
	}

	/**
	 * @param  String  $name  Flexshare name
	 * @param  String  $directory  Flexshare path
	 *
	 * @returns  void
	 */

	protected function _UpdateFolderLinks($name, $directory)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$shell = new ShellExec();
		$defaultdir = self::SHARE_PATH . '/' . $name;

		// Load fstab config
		try {
			$file = new ConfigurationFile(self::FILE_FSTAB_CONFIG, "split", "\s", 6);
			$config = $file->Load();
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		try {
			// Umount any existing
			if ($this->GetParameter($name, 'ShareDir') != $defaultdir) {
				$param = $defaultdir;
				$options['env'] = "LANG=en_US";
				$retval = $shell->Execute(self::CMD_UMOUNT, $param, true, $options);
				if ($retval != 0) {
					// If it didn't exist, we don't want to throw exception
					if (!ereg('not mounted', $shell->GetLastOutputLine()))
						throw new EngineException (FLEXSHARE_LANG_ERRMSG_DEVICE_BUSY, COMMON_ERROR);
				}
			}
			// Mount new share
			if ($directory != $defaultdir && $this->GetParameter($name, 'ShareEnabled')) {
				$param = "--bind '$directory' '$defaultdir'";
				$retval = $shell->Execute(self::CMD_MOUNT, $param, true);
				if ($retval != 0) {
					$output = $shell->GetOutput();
					throw new EngineException ($shell->GetLastOutputLine(), COMMON_ERROR);
				}
			}
			// Check for entry in fstab
			if (isset($config[$this->GetParameter($name, 'ShareDir')]))
				$file->DeleteLines("/^" . preg_quote($this->GetParameter($name, 'ShareDir'), "/") . ".*$/");
			if ($directory != $defaultdir && $this->GetParameter($name, 'ShareEnabled'))
				$file->AddLines($directory . "\t" . $defaultdir . "\tnone\tdefaults,bind\t0 0\n");
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
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
