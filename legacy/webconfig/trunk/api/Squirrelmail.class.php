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

/**
 * Squirrelmail class.
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
require_once('Software.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Squirrelmail class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Squirrelmail extends Software
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();

	const FILE_CONFIG = "/etc/squirrelmail/config.php";
	const TYPE_CYRUS = "cyrus";
	const CONSTANT_DEFAULT_PREFIX = "INBOX/";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Squirrelmail constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("squirrelmail");

		if (!extension_loaded("imap"))
			dl("imap.so");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns default folder prefix.
	 *
	 * @return string default folder prefix
	 */

	function GetDefaultFolderPrefix()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['default_folder_prefix']))
			return $this->config['default_folder_prefix'];
		else
			return "";
	}

	/**
	 * Returns default language.
	 *
	 * @return string default language
	 */

	function GetDefaultLanguage()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['squirrelmail_default_language']))
			return $this->config['squirrelmail_default_language'];
		else
			return "en_US";
	}

	/**
	 * Returns default domain.
	 *
	 * @return string domain
	 */

	function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['domain']))
			return $this->config['domain'];
		else
			return "";
	}

	/**
	 * Returns IMAP server address.
	 *
	 * @return string IMAP server address
	 */

	function GetImapServer()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['imapServerAddress']))
			return $this->config['imapServerAddress'];
		else
			return "";
	}

	/**
	 * Returns IMAP server type.
	 *
	 * @return string IMAP server type
	 */

	function GetImapServerType()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['imap_server_type']))
			return $this->config['imap_server_type'];
		else
			return "";
	}

	/**
	 * Returns organization name.
	 *
	 * @return string organization name
	 */

	function GetOrgName()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['org_name']))
			return $this->config['org_name'];
		else
			return "";
	}

	/**
	 * Returns organization title.
	 *
	 * @return string organization title
	 */

	function GetOrgTitle()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['org_title']))
			return $this->config['org_title'];
		else
			return "";
	}

	/**
	 * Returns SMTP server address.
	 *
	 * @return string SMTP server address
	 */

	function GetSmtpServer()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded) 
			$this->_LoadConfig();

		if (isset($this->config['smtpServerAddress']))
			return $this->config['smtpServerAddress'];
		else
			return "";
	}

	/**
	 * Returns a list of supported languages.
	 *
	 * @return array list of supported languages
	 */

	function GetSupportedLanguages()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$LANGS = array(
			'en_US',
			'bg_BG', 'bn_IN', 'ca_ES', 'cs_CZ', 'cy_GB', 'da_DK', 'de_DE', 'el_GR', 'en_GB', 'es_ES',
			'et_EE', 'eu_ES', 'fa_IR', 'fi_FI', 'fo_FO', 'fr_FR', 'he_IL', 'hr_HR', 'hu_HU', 'id_ID',
			'is_IS', 'it_IT', 'ja_JP', 'ko_KR', 'lt_LT', 'ms_MY', 'nb_NO', 'nl_NL', 'nn_NO', 'pl_PL',
			'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'sk_SK', 'sl_SI', 'sr_YU', 'sv_SE', 'tr_TR', 'zh_CN',
			'zh_TW'
		);

		return $LANGS;
	}

	/**
	 * Sets default language.
	 *
	 * @param string $language default language
	 * @return void
	 */

	function SetDefaultLanguage($language)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidDefaultLanguage($language))
			throw new ValidationException(SQUIRRELMAIL_LANG_ERRMSG_DEFAULTLANGUAGE_INVALID);

		$this->_SetParameter("squirrelmail_default_language", $language);
	}

	/**
	 * Sets domain.
	 *
	 * @param string domain domain
	 * @return void
	 */

	function SetDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidDomain($domain))
			throw new ValidationException(SQUIRRELMAIL_LANG_ERRMSG_DOMAIN_INVALID);

		$this->_SetParameter("domain", $domain);
	}

	/**
	 * Sets IMAP server address.
	 *
	 * @param string $server IMAP server address
	 * @return void
	 */

	function SetImapServer($server)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidServer($server))
			throw new ValidationException(SQUIRRELMAIL_LANG_ERRMSG_IMAPSERVER_INVALID);

		$this->_SetParameter("imapServerAddress", $server);
	}

	/**
	 * Sets organization name.
	 *
	 * @param string $orgname organization name
	 * @return void
	 */

	function SetOrgName($orgname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidOrgName($orgname))
			throw new ValidationException(SQUIRRELMAIL_LANG_ERRMSG_ORGNAME_INVALID);

		$this->_SetParameter("org_name", $orgname);
	}

	/**
	 * Sets organization title.
	 *
	 * @param string $orgtitle organization title
	 * @return void
	 */

	function SetOrgTitle($orgtitle)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidOrgTitle($orgtitle))
			throw new ValidationException(SQUIRRELMAIL_LANG_ERRMSG_ORGTITLE_INVALID);

		$this->_SetParameter("org_title", $orgtitle);
	}

	/**
	 * Sets SMTP server address.
	 *
	 * @param string $server SMTP server address
	 * @return void
	 */

	function SetSmtpServer($server)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidServer($server))
			throw new ValidationException(SQUIRRELMAIL_LANG_ERRMSG_SMTPSERVER_INVALID);

		$this->_SetParameter("smtpServerAddress", $server);
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

	public function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$matches = array();

		foreach ($lines as $line) {
			if (preg_match('/^\$(.*)=(.*);/', $line, $matches)) {
				$key = trim($matches[1]);
				$value = preg_replace("/[\"';]/", "", $matches[2]);
				$this->config[$key] = ltrim($value);
			}
		}

		$this->is_loaded = true;
	}

	/**
	 * Sets a parameter in the configuration file.
	 * 
	 * @access private
	 * @param string $key key in configuration file
	 * @param string $value value for given key
	 * @return void
	 * @throws EngineException
	 */

	public function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->is_loaded = false;

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines('/^\$' . $key . '\s*=/i', '$' . "$key = '$value';\n");
			if (!$match)
				$file->AddLinesAfter('$' . "$key = '$smtpserver';\n", "/^[^#]/");
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

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   R O U T I N E S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for default language.
	 *
	 * @param string $language default language
	 * @return boolean true if defaultlanguage is valid
	 */

	function IsValidDefaultLanguage($language)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([a-zA-Z_]+)$/", $language))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for domain.
	 *
	 * @param string $domain domain
	 * @return boolean true if domain is valid
	 */

	function IsValidDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/[;'\"]/", $domain))
			return false;
		else
			return true;
	}

	/**
	 * Validation routine for IMAP server.
	 *
	 * @param string $imapserver IMAP server address
	 * @return boolean true if imapserver is valid
	 */

	function IsValidServer($imapserver)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/[;'\"]/", $imapserver))
			return false;
		else
			return true;
	}

	/**
	 * Validation routine for organization name.
	 *
	 * @param string $orgname organization name
	 * @return boolean true if orgname is valid
	 */

	function IsValidOrgName($orgname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/[;'\"]/", $orgname))
			return false;
		else
			return true;
	}

	/**
	 * Validation routine for organization title.
	 *
	 * @param string $orgtitle organization title
	 * @return boolean true if orgtitle is valid
	 */

	function IsValidOrgTitle($orgtitle)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/[;'\"]/", $orgtitle))
			return false;
		else
			return true;
	}
}

// vim: syntax=php ts=4
?>
