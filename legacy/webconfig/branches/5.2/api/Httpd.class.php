<?php

///////////////////////////////////////////////////////////////////////////////
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
//
// TODO:
// - Handling the default virtual host was an afterthought... it can be cleaned up a bit.
// - Add ServerAdmin to the virtual/default host configs
//
// WARNING:
// - The alphabetical ordering of the files in conf.d is important.
//   *.conf files are read in first, and *.vhost files are next.
//   The default virtual host (i.e. default site) must come before ssl.conf.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Httpd (Apache) class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Folder.class.php');
require_once('Flexshare.class.php');
require_once('Daemon.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Httpd web server class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Httpd extends Daemon
{
	const PATH_CONFD  = '/etc/httpd/conf.d';
	const PATH_DEFAULT = '/var/www/html';
	const PATH_VIRTUAL = '/var/www/virtual';
	const FILE_CONFIG = '/etc/httpd/conf/httpd.conf';
	const FILE_SSL = '/etc/httpd/conf.d/ssl.conf';
	const FILE_DEFAULT = 'default.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Locale constructor.
     */

    function __construct()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__construct('httpd');

        require_once(GlobalGetLanguageTemplate(__FILE__));
    }

	/**
	 * Adds the default host.
	 *
	 * @param  string  $domain  domain name
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function AddDefaultHost($domain)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->AddHost($domain, self::FILE_DEFAULT);
	}

	/**
	 * Adds a virtual host with defaults.
	 *
	 * @param  string  $domain  domain name
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function AddVirtualHost($domain)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->AddHost($domain, "$domain.vhost");
	}

	/**
	 * Generic add virtual host.
	 *
	 * @param  string  $domain  domain name
	 * @param  string  $confd  configuration file
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function AddHost($domain, $confd)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidDomain($domain))
			throw new ValidationException(HTTPD_LANG_ERRMSG_WEB_SITE_INVALID);

		try {
			$config = new File(self::PATH_CONFD . "/$confd");
			if ($config->Exists()) {
				throw new ValidationException(HTTPD_LANG_ERRMSG_WEB_SITE_EXISTS);
			}
		} catch (ValidationException $e) {
			throw new ValidationException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$docroot = self::PATH_VIRTUAL . "/$domain";
		$entry = "<VirtualHost *:80>\n";
		$entry .= "\tServerName $domain\n";
		$entry .= "\tServerAlias *.$domain\n";
		if ($confd ==  self::FILE_DEFAULT) {
			$entry .= "\tDocumentRoot /var/www/html\n";
			$entry .= "\tErrorLog /var/log/httpd/error_log\n";
			$entry .= "\tCustomLog /var/log/httpd/access_log combined\n";
		} else {
			$entry .= "\tDocumentRoot $docroot\n";
			$entry .= "\tErrorLog /var/log/httpd/" . $domain . "_error_log\n";
			$entry .= "\tCustomLog /var/log/httpd/" . $domain . "_access_log combined\n";
		}
		$entry .= "</VirtualHost>\n";

		try {
			$config->Create('root', 'root', '0644');
			$config->AddLines($entry);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$webfolder = new Folder($docroot);
			if (! $webfolder->Exists())
				$webfolder->Create('root', 'root', '0775');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Uncomment NameVirtualHost
		try {
			$httpcfg = new File(self::FILE_CONFIG);
			$match = $httpcfg->ReplaceLines("/^[#\s]*NameVirtualHost.*\*/", "NameVirtualHost *:80\n");
			if (! $match)
				$httpcfg->AddLines("NameVirtualHost *:80\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Make sure our "Include conf.d/*.vhost" is still there
		try {
			$includeline = $httpcfg->LookupLine("/^Include\s+conf.d\/\*\.vhost/");
		} catch (FileNoMatchException $e) {
			$httpcfg->AddLines("Include conf.d/*.vhost\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes a virtual host.
	 *
	 * @param  string  $domain  domain name
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function DeleteVirtualHost($domain)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidDomain($domain))
            throw new ValidationException(HTTPD_LANG_ERRMSG_WEB_SITE_INVALID);

		$flexshare = new Flexshare();
		try {
			$share = $flexshare->GetShare($domain);
			# Check to see if Directory == docroot
			$conf = $this->GetHostInfo($domain . '.vhost');
			if (trim($conf['docroot']) == trim($share['ShareDir'])) {
				# Default flag to *not* delete contents of dir
				$flexshare->DeleteShare($domain, false);
			}
		} catch (FlexshareNotFoundException $e) {
			#Ignore
		} catch (Exception $e) {
           	throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$config = new File(self::PATH_CONFD . "/" . $domain . ".vhost");
			$config->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Gets server name (ServerName).
	 *
	 * @return  string  server name
	 * @throws  EngineException
	 */

	function GetServerName()
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$retval = $file->LookupValue("/^ServerName\s+/i");
		} catch (FileNoMatchException $e) {
			return "";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		return $retval;
	}

	/**
	 * Gets SSL state.
	 *
	 * @return  boolean  true if SSL is enabled
	 * @throws  EngineException
	 */

	function GetSslState()
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_SSL);
			if ($file->Exists())
				return true;
			else
				return false;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Gets a list of configured virtual hosts.
	 *
	 * @return  array  list of virtual hosts
	 * @throws  EngineException
	 */

	function GetVirtualHosts()
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$folder = new Folder(self::PATH_CONFD);
			$files = $folder->GetListing();
			$vhosts = array();
			foreach ($files as $file) {
				if (preg_match("/\.vhost$/", $file))
					array_push($vhosts, preg_replace("/\.vhost$/", "", $file));
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $vhosts;
	}

	/**
	 * Gets default host info and returns it in a hash array.
	 *
	 * @return  array  hash array with default host info
	 * @throws  EngineException
	 */

	function GetDefaultHostInfo()
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$info = $this->GetHostInfo(self::FILE_DEFAULT);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $info;
	}

	/**
	 * Gets virtual host info and returns it in a hash array.
	 *
	 * @param  string  $domain  domain name
	 * @return  array  hash array with virtual host info
	 * @throws  ValidationException, EngineException
	 */

	function GetVirtualHostInfo($domain)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidDomain($domain))
            throw new ValidationException(HTTPD_LANG_ERRMSG_WEB_SITE_INVALID);

		$info = Array();

		try {
			$info = $this->GetHostInfo("$domain.vhost");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		return $info;
	}

	/**
	 * Returns configuration information for a given host.
	 *
	 * @param  string  $confd  the configuration file
	 * @return  array  settings for a given host
	 * @throws  EngineException
	 */

	function GetHostInfo($confd)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$info = array();

		try {
			$file = new File(self::PATH_CONFD . "/$confd");
			$contents = $file->GetContents();
			$count = 0;
			$lines = explode("\n", $contents);
			foreach ($lines as $line) {
				$result = explode(" ", trim($line), 2);
				if ($result[0] == "ServerAlias") {
					$info["aliases"] = $result[1];
					$count++;
				} else if ($result[0] == "DocumentRoot") {
					$info["docroot"] = $result[1];
					$count++;
				} else if ($result[0] == "ServerName") {
					$info["servername"] = $result[1];
					$count++;
				} else if ($result[0] == "ErrorLog") {
					$info["errorlog"] = $result[1];
					$count++;
				} else if ($result[0] == "CustomLog") {
					$info["customlog"] = $result[1];
					$count++;
				}
			}
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		if ($count < 5)
            throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);

		return $info;
	}

	/**
	 * Sets parameters for a virtual host.
	 *
	 * @param  string  $domain  domain name
	 * @param  string  $alias  alias name
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function SetDefaultHost($domain, $alias)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidDomain($domain))
            throw new ValidationException(HTTPD_LANG_ERRMSG_WEB_SITE_INVALID);

		try {
			$file = new File(self::PATH_CONFD . "/" . self::FILE_DEFAULT);
			$file->ReplaceLines("/^\s*ServerName/", "\tServerName $domain\n");
			$file->ReplaceLines("/^\s*ServerAlias/", "\tServerAlias $alias\n");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		if ($this->GetSslState())
			$filename = self::FILE_SSL;
		else
			$filename = self::FILE_SSL . ".off";

		try {
			$file = new File($filename);
			$file->ReplaceLines("/^\s*ServerName/", "ServerName $domain\n");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Sets server name
	 *
	 * @param  string  $servername  server name
	 * @return  array  settings for a given host
	 * @throws  ValidationException, EngineException
	 */

	function SetServerName($servername)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidServerName($servername))
            throw new ValidationException(HTTPD_LANG_SERVERNAME . " - " . LOCALE_LANG_INVALID);

		// Update tag if it exists
		//------------------------

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^\s*ServerName/i", "ServerName $servername\n");
			// If tag does not exist, add it
			//------------------------------
			if (! $match)
				$file->AddLinesAfter("ServerName $servername\n", "/^[^#]/");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Sets SSL state (on or off)
	 *
	 * @param  boolean  $sslstate  SSL state (on or off)
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function SetSslState($sslstate)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// IsValid
		//---------

		if (! $this->IsValidSslState($sslstate))
            throw new ValidationException(HTTPD_LANG_ERRMSG_SSLSTATE_INVALID);

		try {
			$onfile = new File(self::FILE_SSL);
			$offfile = new File(self::FILE_SSL . ".off");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		// Handle "on" condition
		//----------------------

		try {
			if (($sslstate) && (! $onfile->Exists())) {
				if (! $offfile->Exists())
					throw new EngineException(HTTPD_LANG_ERRMSG_SSLCONFIG_MISSING, COMMON_ERROR);
                if (file_exists(COMMON_CORE_DIR.'/api/Horde.class.php')){
                    require_once('Horde.class.php');
                    $horde = new Horde();
                    if ($horde->GetPort() == 443){
                        throw new ValidationException(HORDE_LANG_PORT_INUSE.'groupware');
                    }
                }
				$offfile->MoveTo(self::FILE_SSL);
				return;
			}
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

		// Handle "off" condition
		//-----------------------

		try {
			if ((!$sslstate) && ($onfile->Exists())) {
				$onfile->MoveTo(self::FILE_SSL . ".off");
				return;
			}
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Sets parameters for a virtual host.
	 *
	 * @param  string  $domain  domain name
	 * @param  string  $alias  alias name
	 * @param  string  $docroot  document root
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function SetVirtualHost($domain, $alias, $docroot)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidDomain($domain))
            throw new ValidationException(HTTPD_LANG_ERRMSG_WEB_SITE_INVALID);

		if (! $this->IsValidDocRoot($docroot))
            throw new ValidationException(HTTPD_LANG_ERRMSG_DOCROOT_INVALID);

		// TODO validation

		try {
			$file = new File(self::PATH_CONFD . "/" . $domain . ".vhost");
			$file->ReplaceLines("/^\s*ServerAlias/", "\tServerAlias $alias\n");
			$file->ReplaceLines("/^\s*DocumentRoot/", "\tDocumentRoot $docroot\n");
		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Sets parameters for a domain or virtual host.
	 *
	 * @param  string  $domain the domain name
	 * @param  string  $docroot  document root
	 * @param  string  $group  the group owner
	 * @param  string  $ftp  FTP enabled status
	 * @param  string  $smb  File (SAMBA) enabled status
	 * @return  void
	 * @throws  EngineException
	 */

	function ConfigureUploadMethods($domain, $docroot, $group, $ftp, $smb)
	{
		if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);
	
		if ($ftp && ! file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
			return;

		if ($smb && ! file_exists(COMMON_CORE_DIR . "/api/Samba.class.php"))
			return;

		try {
			$flexshare = new Flexshare();
			try {
				if (!$ftp && !$smb) {
					try {
						$flexshare->GetShare($domain);
						$flexshare->DeleteShare($domain, false);
					} catch (FlexshareNotFoundException $e) {
						// GetShare will trigger this exception on a virgin box
						// TODO: implement Flexshare.Exists($name) instead of this hack
					} catch (Exception $e) {
						throw new EngineException($e->GetMessage(), COMMON_ERROR);
					}
					return;
				}
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
			try {
				$share = $flexshare->GetShare($domain);
			} catch (FlexshareNotFoundException $e) {
				$flexshare->AddShare($domain, HTTPD_LANG_WEB_SITE . " - " . $domain, $group, true);
				$flexshare->SetDirectory($domain, Httpd::PATH_DEFAULT);
				$share = $flexshare->GetShare($domain);
			} catch (Exception $e) {
            	throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}
			# FTP
			# We check setting of some parameters so we can allow user override using Flexshare.
			if (!isset($share['FtpServerUrl']))
				$flexshare->SetFtpServerUrl($domain, $domain);
			$flexshare->SetFtpAllowPassive($domain, 1, Flexshare::FTP_PASV_MIN, Flexshare::FTP_PASV_MAX);
			if (!isset($share['FtpPort']))
				$flexshare->SetFtpOverridePort($domain, 0, Flexshare::DEFAULT_PORT_FTP);
			if (!isset($share['FtpReqSsl']))
				$flexshare->SetFtpReqSsl($domain, 0);
			$flexshare->SetFtpReqAuth($domain, 1);
			$flexshare->SetFtpAllowAnonymous($domain, 0);
			$flexshare->SetFtpUserOwner($domain, null);
			#$flexshare->SetFtpGroupAccess($domain, Array($group));
			if (!isset($share['FtpGroupGreeting']))
				$flexshare->SetFtpGroupGreeting($domain, HTTPD_LANG_WEB_SITE . ' - ' . $domain);
			$flexshare->SetFtpGroupPermission($domain, Flexshare::PERMISSION_READ_WRITE_PLUS);
			$flexshare->SetFtpGroupUmask($domain, Array('owner'=>0, 'group'=>0, 'world'=>2));
			$flexshare->SetFtpEnabled($domain, $ftp);
			# Samba
			$flexshare->SetFileComment($domain, HTTPD_LANG_WEB_SITE . ' - ' . $domain);
			$flexshare->SetFilePublicAccess($domain, 0);
			$flexshare->SetFilePermission($domain, Flexshare::PERMISSION_READ_WRITE);
			$flexshare->SetFileCreateMask($domain, Array('owner'=>6, 'group'=>6, 'world'=>4));
			$flexshare->SetFileEnabled($domain, $smb);

			# Globals
			$flexshare->SetGroup($domain, $group);
			$flexshare->SetDirectory($domain, $docroot);
			$flexshare->ToggleShare($domain, ($ftp|$smb));

		} catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	///////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S								 //
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for checking state of default domain.
	 *
	 * @return boolean true if default domain is set
	 */

	function IsDefaultSet()
	{
		$file = new File(self::PATH_CONFD . "/" . self::FILE_DEFAULT);
		if ($file->Exists()) {
			return true;
		} else {
			# Need file class for lang
			$file = new File();
			$filename = self::PATH_CONFD . "/" . self::FILE_DEFAULT;
			$this->AddValidationError(FILE_LANG_ERRMSG_NOTEXIST . " - " . $filename, __METHOD__, __LINE__);
			return false;
		}
	}

	/**
	 * Validation routine for domain.
	 *
	 * @param string $domain domain
	 * @return boolean true if domain is valid
	 */

	function IsValidDomain($domain)
	{
		// Allow underscores
		if (preg_match("/^([0-9a-zA-Z\.\-_]+)$/", $domain))
			return true;
		$this->AddValidationError(HTTPD_LANG_ERRMSG_WEB_SITE_INVALID, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for servername
	 *
	 * @param string $servername server name
	 * @return boolean true if servername is valid
	 */

	function IsValidServerName($servername)
	{
		if (preg_match("/^[A-Za-z0-9\.\-_]+$/", $servername))
			return true;
		$this->AddValidationError(HTTPD_LANG_ERRMSG_SERVER_NAME_INVALID, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for sslstate
	 *
	 * @param string $sslstate SSL state
	 * @return boole true if sslstate is valid
	 */

	function IsValidSslState($sslstate)
	{
		if (is_bool($sslstate))
			return true;
		$this->AddValidationError(HTTPD_LANG_ERRMSG_SSLSTATE_INVALID, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for docroot.
	 *
	 * @param string $docroot  document root
	 * @return boolean true if document root is valid
	 */

	function IsValidDocRoot($docroot)
	{
		// Allow underscores
		if (!isset($docroot) || !$docroot || $docroot == '') {
			$this->AddValidationError(HTTPD_LANG_ERRMSG_DOCROOT_INVALID, __METHOD__, __LINE__);
			return false;
		}
		$folder = new Folder($docroot);
		if (! $folder->Exists()) {
			$this->AddValidationError(FOLDER_LANG_ERRMSG_NOTEXIST . ' - ' . $docroot, __METHOD__, __LINE__);
			return false;
		}
		return true;
	}

}
// vim: syntax=php ts=4
?>
