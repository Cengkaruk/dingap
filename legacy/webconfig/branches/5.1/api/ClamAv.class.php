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
 * ClamAV class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('ConfigurationFile.class.php');
require_once('Daemon.class.php');
require_once('Engine.class.php');
require_once('File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClamAV class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2005-2006, Point Clark Networks
 */

class ClamAv extends Daemon
{
	///////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();

	const FILE_CONFIG = '/etc/clamd.conf';
	const DEFAULT_ARCHIVE_MAX_FILES = 1000;
	const DEFAULT_ARCHIVE_MAX_FILE_SIZE = 10;
	const DEFAULT_ARCHIVE_MAX_RECURSION = 8;
	const DEFAULT_PHISHING_SIGNATURES = true;
	const DEFAULT_PHISHING_SCAN_URLS = true;
	const DEFAULT_PHISHING_ALWAYS_BLOCK_SSL_MISMATCH = false;
	const DEFAULT_PHISHING_ALWAYS_CLOAK = false;

	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * ClamAV constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("clamd");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns archive encryption policy.
	 *
	 * @return boolean true if archives should be marked as a virus if encrypted
	 * @throws EngineException
	 */

	function GetArchiveBlockEncrypted()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("ArchiveBlockEncrypted", $this->config)) {
			if (preg_match("/yes/", $this->config['ArchiveBlockEncrypted']))
				return true;
			else
				return false;
		} else {
			return false;
		}
	}

	/**
	 * Returns maximum number of files to be scanned in an archive.
	 *
	 * @return int maximum number of files
	 * @throws EngineException
	 */

	function GetMaxFiles()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("MaxFiles", $this->config))
			return $this->config['MaxFiles'];
		else
			return self::DEFAULT_ARCHIVE_MAX_FILES;
	}

	/**
	 * Returns maximum file size inside archive to be scanned (in megabytes).
	 *
	 * @return int maximum file size
	 * @throws EngineException
	 */

	function GetMaxFileSize()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("MaxFileSize", $this->config))
			return preg_replace("/M\s*$/", "", $this->config['MaxFileSize']);
		else
			return self::DEFAULT_ARCHIVE_MAX_FILE_SIZE;
	}

	/**
	 * Returns maximum recursion in archive.
	 *
	 * For example, if a zip file contains another zip file, files within 
	 * the second zip will also be scanned.  This result from this method
	 * specifies the number of iterations.
	 *
	 * @return int maximum recursion
	 * @throws EngineException
	 */

	function GetMaxRecursion()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("MaxRecursion", $this->config))
			return $this->config['MaxRecursion'];
		else
			return self::DEFAULT_ARCHIVE_MAX_RECURSION;
	}

	/**
	 * Returns phishing signature state.
	 *
	 * @return boolean state of phishing signature engine
	 * @throws EngineException
	 */

	function GetPhishingSignaturesState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("PhishingSignatures", $this->config))
			return $this->_GetBoolean($this->config['PhishingSignatures']);
		else
			return self::DEFAULT_PHISHING_SIGNATURES;
	}
	
	/**
	 * Returns state of URL scanning using heuristics.
	 *
	 * @return boolean state of URL scanning
	 * @throws EngineException
	 */

	function GetPhishingScanUrlsState()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("PhishingScanURLs", $this->config))
			return $this->_GetBoolean($this->config['PhishingScanURLs']);
		else
			return self::DEFAULT_PHISHING_SCAN_URLS;
	}
	
	/**
	 * Returns state of SSL URL mismatch scan.
	 *
	 * @return boolean state of SSL URL mismatch scanning
	 * @throws EngineException
	 */

	function GetPhishingAlwaysBlockSslMismatch()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("PhishingAlwaysBlockSSLMismatch", $this->config))
			return $this->_GetBoolean($this->config['PhishingAlwaysBlockSSLMismatch']);
		else
			return self::DEFAULT_PHISHING_ALWAYS_BLOCK_SSL_MISMATCH;
	}
	
	/**
	 * Returns state of cloak URL blocking.
	 *
	 * @return boolean state of cloak URL blocking
	 * @throws EngineException
	 */

	function GetPhishingAlwaysBlockCloak()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (array_key_exists("PhishingAlwaysBlockCloak", $this->config))
			return $this->_GetBoolean($this->config['PhishingAlwaysBlockCloak']);
		else
			return self::DEFAULT_PHISHING_ALWAYS_CLOAK;
	}
	
	/**
	 * Set archive encryption policy.
	 *
	 * @param boolean $policy archive encryption policy
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetArchiveBlockEncrypted($policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($policy)) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " .
				CLAMAV_LANG_BLOCK_ENCRYPTED_ARCHIVES_POLICY
			);
		}

		$this->_SetBooleanParameter('ArchiveBlockEncrypted', $policy);
	}

	/**
	 * Sets maximum number of files to be scanned in an archive.
	 *
	 * @param int $max maximum number of files to be scanned in an archive
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetMaxFiles($max)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ( (!is_numeric($max)) || ((int)$max < 0) ) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " .
				CLAMAV_LANG_MAXIMUM_FILES
			);
		}

		$this->_SetParameter('MaxFiles', $max);
	}

	/**
	 * Sets maximum file size inside archive to be scanned.
	 *
	 * @param int $max maximum file size inside archive to be scanned
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetMaxFileSize($max)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$checkmax = preg_replace("/M$/", "", $max);

		if ( (!is_numeric($checkmax)) || ((int)$checkmax < 0) ) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " .
				CLAMAV_LANG_MAXIMUM_FILE_SIZE
			);
		}

		$this->_SetParameter('MaxFileSize', $checkmax . "M");
	}

	/**
	 * Sets maximum recursion in archive.
	 *
	 * @param int $max maximum recursion in archive
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetMaxRecursion($max)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ( (!is_numeric($max)) || ((int)$max < 0) ) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " .
				CLAMAV_LANG_MAXIMUM_RECURSION
			);
		}

		$this->_SetParameter('MaxRecursion', $max);
	}

	/**
	 * Sets phishing signature state.
	 *
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPhishingSignaturesState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state)) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . CLAMAV_LANG_PHISHING_SCAN_WITH_SIGNATURES
			);
		}

		$this->_SetBooleanParameter('PhishingSignatures', $state);
	}

	/**
	 * Sets state of URL scanning using heuristics.
	 *
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPhishingScanUrlsState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state)) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . CLAMAV_LANG_PHISHING_SCAN_WITH_HEURISTICS
			);
		}

		$this->_SetBooleanParameter('PhishingScanURLs', $state);
	}

	/**
	 * Sets state of SSL URL mismatch scan.
	 *
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPhishingAlwaysBlockSslMismatch($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state)) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . CLAMAV_LANG_PHISHING_BLOCK_SSL_MISMATCH
			);
		}

		$this->_SetBooleanParameter('PhishingAlwaysBlockSSLMismatch', $state);
	}

	/**
	 * Sets state of cloak URL blocking.
	 *
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetPhishingAlwaysBlockCloak($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state)) {
			throw new ValidationException(
				LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " - " . CLAMAV_LANG_PHISHING_BLOCK_CLOAK
			);
		}

		$this->_SetBooleanParameter('PhishingAlwaysBlockCloak', $state);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Returns a boolean for ClamAV yes/no parameters.
	 *
	 * @access private
	 * @param string value value of a ClamAV boolean (yes, no)
	 * @return boolean boolean value
	 */

	protected function _GetBoolean($value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^yes$/i", $value))
			return true;
		else
			return false;
	}

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

		// Load configuration file
		//------------------------

		try {
			$file = new File(self::FILE_CONFIG);
			$lines = $file->GetContentsAsArray();

			foreach ($lines as $line) {
				if (preg_match("/^\s*#/", $line) || preg_match("/^\s*$/", $line))
					continue;

				$items = preg_split("/\s+/", $line);
				$this->config[$items[0]] = isset($items[1]) ? $items[1] : "";
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * Sets a boolean parameter in the config file.
	 *
	 * @access private
	 * @param string $key name of the key in the config file
	 * @param boolean $policy value for the key
	 * @return void
	 * @throws EngineException
	 */

	protected function _SetBooleanParameter($key, $policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$file->ReplaceLines("/^$key\s+/", "#$key\n");

			$boolvalue = $policy ? "yes" : "no";
			$match = $file->ReplaceLines("/^#\s*$key\s+/", "$key $boolvalue\n");

			if (!$match)
				$file->AddLines("$key $boolvalue\n");

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = false;
	}

	/**
	 * Sets a parameter in the config file.
	 *
	 * @access private
	 * @param string $key name of the key in the config file
	 * @param string $value value for the key
	 * @return void
	 * @throws EngineException
	 */

	protected function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_CONFIG);

		try {
			$match = $file->ReplaceLines("/^$key\s+/", "$key $value\n");

			if (!$match) {
				$match = $file->ReplaceLines("/^#\s*$key\s+/", "$key $value\n");
				if (!$match)
					$file->AddLines("$key = $value\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = false;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
