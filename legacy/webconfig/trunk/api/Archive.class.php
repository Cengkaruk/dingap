<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
// FIXME - remove SESSION variables

/**
 * Provides interface for discovering mass storage devices.
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
require_once("Cyrus.class.php");
require_once("Mime.class.php");
require_once("Mailer.class.php");
require_once("File.class.php");
require_once("Folder.class.php");
require_once("ShellExec.class.php");
require_once("Flexshare.class.php");
require_once("Postfix.class.php");
require_once("NtpTime.class.php");
require_once("Ldap.class.php");
require_once("ClearDirectory.class.php");
require_once("User.class.php");
require_once(COMMON_CORE_DIR . '/scripts/archive.inc.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Storage Device Utility.
 *
 * Class to assist in the discovery of mass storage devices on the server.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Archive extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $db = null;
	protected $config = null;
	protected $link = null;
	protected $is_loaded = false;
	const FILE_CONFIG = "/etc/archive.conf";
	const SYS_USER = "email-archive";
	const MBOX_HOSTNAME = "localhost";
	const FILE_LOCK = "/var/run/archive_mbox.pid";
	const LOCK_TIMEOUT_HR = 24;
	const DB_HOST = "localhost";
	const DB_PORT = "3308";
	const DB_USER = "archive";
	const DB_NAME_CURRENT = "archive_current";
	const DB_NAME_SEARCH = "archive_search";
	const DIR_MYSQL_ARCHIVE = "/var/lib/system-mysql";
	const SOCKET_MYSQL = "/var/lib/system-mysql/mysql.sock";
	const FILE_CONFIG_DB = "/etc/system/database";
	const FILE_RECIPIENT_BCC = '/etc/postfix/archive_recip_bcc_maps';
	const FILE_SENDER_BCC = '/etc/postfix/archive_send_bcc_maps';
	const FILE_ARCHIVE = 'archive.php';
	const FILE_BOOTSTRAP = '/usr/bin/archive_bootstrap';
	const FILE_RESEND = 'archive_resend.php';
	const ROOT_PATH = "/var/archive";
	const CMD_MYSQL = "/usr/share/system-mysql/usr/bin/mysql";
	const CMD_MYSQL_DUMP = "/usr/share/system-mysql/usr/bin/mysqldump";
	const CMD_TAR = "/bin/tar";
	const CMD_LN = "/bin/ln";
	const DEFAULT_DB = "archive";
	const DIR_CURRENT = "/var/archive/current";
	const DIR_SEARCH = "/var/archive/search";
	const DIR_LINKS = "/var/archive/links";
	const FLEXSHARE_SEARCH = "mail-archives";
	const ARCHIVE_NEVER = 0;
	const ARCHIVE_WEEK = 1;
	const ARCHIVE_MONTH = 2;
	const ARCHIVE_QUARTER = 3;
	const ARCHIVE_YEAR = 4;
	const ARCHIVE_SIZE10 = 5;
	const ARCHIVE_SIZE100 = 6;
	const ARCHIVE_SIZE1000 = 7;
	const ARCHIVE_SIZE10000 = 8;
	const DISCARD_ATTACH_NEVER = 0;
    const DISCARD_ATTACH_ALWAYS = 1;
    const DISCARD_ATTACH_1 = 2;
    const DISCARD_ATTACH_5 = 3;
    const DISCARD_ATTACH_10 = 4;
    const DISCARD_ATTACH_25 = 5;
	const SEARCH_SUBJECT = 0;
	const SEARCH_FROM = 1;
	const SEARCH_BODY = 2;
	const SEARCH_DATE = 3;
	const SEARCH_TO = 4;
	const SEARCH_CC = 5;
	const CRITERIA_CONTAINS = 0;
	const CRITERIA_IS = 1;
	const CRITERIA_BEGINS = 2;
	const CRITERIA_ENDS = 3;
	const LOG_TAG = 'archive';


	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Archive constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		if (!extension_loaded("mysql"))
			dl("mysql.so");

		if (!extension_loaded("imap"))
			dl("imap.so");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Set the archive status.
	 *
	 * @param boolean $status archive status (enabled/disabled)
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetStatus($status)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$postfix = new Postfix();

		try {
			if ($status) {
				if ($this->config['policy'] == 0) {
					try {
						list($user, $hostname) = explode('@', $this->GetArchiveAddress());
						$postfix->SetAlwaysBcc($user . "@" . $hostname);
					} catch (Exception $e) {
						throw new ValidationException (ARCHIVE_LANG_ERRMSG_DOMAIN_INVALID);
					}
					$postfix->DeleteRecipientBccMaps(self::FILE_RECIPIENT_BCC);
					$postfix->DeleteSenderBccMaps(self::FILE_SENDER_BCC);
				} else {
					$postfix->SetAlwaysBcc(null);
					$list = $postfix->GetRecipientBccMaps(self::FILE_RECIPIENT_BCC);
					if (!in_array(self::FILE_RECIPIENT_BCC, $list))
						$postfix->AddRecipientBccMaps(self::FILE_RECIPIENT_BCC);
					unset($list);
					$list = $postfix->GetSenderBccMaps(self::FILE_SENDER_BCC);
					if (!in_array(self::FILE_SENDER_BCC, $list))
						$postfix->AddSenderBccMaps(self::FILE_SENDER_BCC);
				}
			} else {
				$postfix->SetAlwaysBcc(null);
				$postfix->DeleteRecipientBccMaps(self::FILE_RECIPIENT_BCC);
				$postfix->DeleteSenderBccMaps(self::FILE_SENDER_BCC);
			}
		} catch (ValidationException $e) {
			throw new ValidationException($e->GetMessage(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Set the archive policy.
	 *
	 * @param string $policy policy (0 [all] or 1 [filter])
	 * @return void
	 * @throws ValidationException
	 * @throws EngineException
	 */

	function SetPolicy($policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($policy != 0 && $policy != 1)
			throw new ValidationException(ARCHIVE_LANG_ERRMSG_POLICY_INVALID, COMMON_ERROR);

		$this->_SetParameter('policy', $policy);
	}

	/**
	 * Set the timestamp of the last successful archive.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function SetLastSuccessfulArchive()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Set default timezone
		$ntptime = new NtpTime();
		date_default_timezone_set($ntptime->GetTimeZone());
		$this->_SetParameter('last_archive', date("Y-m-d"));
	}

	/**
	 * Set the global attachment policy.
	 *
	 * @param int $attach policy
	 * @return void
	 * @throws ValidationException
	 * @throws EngineException
	 */

	function SetAttachmentPolicy($attach)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ((int)$attach < 0 || (int)$attach > 5)
			throw new ValidationException(ARCHIVE_LANG_ERRMSG_ATTACH_POLICY_INVALID, COMMON_ERROR);

		$this->_SetParameter('attachment', $attach);
	}

	/**
	 * Set the recipient attachment policy by domain.
	 *
	 * @param array $attach policy
	 * @return void
	 * @throws ValidationException
	 * @throws EngineException
	 */

	function SetRecipientAttachmentPolicy($attach)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);


		foreach ($attach as $domain => $policy) {
			if ((int)$policy < 0 || (int)$policy > 5)
				throw new ValidationException(ARCHIVE_LANG_ERRMSG_ATTACH_POLICY_INVALID, COMMON_ERROR);
			$this->_SetParameter("attachment-recipient[" . $domain . "]", $policy);
		}
	}

	/**
	 * Set the sender attachment policy by domain.
	 *
	 * @param array $attach policy
	 * @return void
	 * @throws ValidationException
	 * @throws EngineException
	 */

	function SetSenderAttachmentPolicy($attach)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ((int)$attach < 0 || (int)$attach > 5)
			throw new ValidationException(ARCHIVE_LANG_ERRMSG_ATTACH_POLICY_INVALID, COMMON_ERROR);

		foreach ($attach as $domain => $policy) {
			if ((int)$policy < 0 || (int)$policy > 5)
				throw new ValidationException(ARCHIVE_LANG_ERRMSG_ATTACH_POLICY_INVALID, COMMON_ERROR);
			$this->_SetParameter("attachment-sender[" . $domain . "]", $policy);
		}
	}

	/**
	 * Set the auto archive policy.
	 *
	 * @param int $auto policy
	 * @return void
	 * @throws ValidationException
	 * @throws EngineException
	 */

	function SetAutoArchivePolicy($auto)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ((int)$auto < 0 || (int)$auto > 8)
			throw new ValidationException(ARCHIVE_LANG_ERRMSG_AUTO_POLICY_INVALID, COMMON_ERROR);

		$this->_SetParameter('auto-archive', $auto);
	}

	/**
	 * Set the archive encryption flag.
	 *
	 * @param boolean $encrypt archive encryption use (enabled/disabled)
	 * @return void
	 * @throws EngineException
	 */

	function SetArchiveEncryption($encrypt)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($encrypt)
			$encrypt = 1;
		else
			$encrypt = 0;

		$this->_SetParameter('archive-encryption', $encrypt);
	}

	/**
	 * Set the archive mailbox password.
	 *
	 * @param string $password a strong password
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetMailboxPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$this->_SetParameter('mbox-password', base64_encode($password));
	}

	/**
	 * Set the archive encryption password.
	 *
	 * @param string $password a strong password
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetEncryptionPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (!$this->IsValidPassword($password)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		$this->_SetParameter('encrypt-password', base64_encode($password));
	}

	/** Archives any messages in the mailbox to the database.
	 *
     * @throws EngineException
	 */

	function ArchiveMessages()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Lower priority
		proc_nice(99);
		$this->_ArchiveMessages();
	}

	/**
	 * Returns archive email address.
	 *
	 * @return String
	 * @throws EngineException
	 */

	function GetArchiveAddress()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Default
		$email = self::SYS_USER . '@' . self::MBOX_HOSTNAME;

		# Determine whether to override
		if (isset($this->config['archive_email_override'])) {
			$mailer = new Mailer();
			if ($mailer->IsValidEmail($this->config['archive_email_override']))
				$email = $this->config['archive_email_override'];
		}
		return $email;
	}

	/**
	 * Returns condition of performing IMAP check (sanity check).
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function CheckImapStatus()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['archive_status_override']) && $this->config['archive_status_override'] == 1) 
			return false;

		list($user, $hostname) = explode('@', $this->GetArchiveAddress());

        # Check if local service
        if ($hostname != 'localhost')
			return false;

		return true;
	}

	/**
	 * Returns status of archive.
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function GetStatus()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$postfix = new Postfix();

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (isset($this->config['archive_status_override']) && $this->config['archive_status_override'] == 1) 
			return true;

		if ($this->config['policy'] == 0) {
			try {
				$bcc = $postfix->GetAlwaysBcc();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_ERROR);
			}

			if (empty($bcc))
				return false;
			else
				return true;
		} else {
			try {
				$list = $postfix->GetRecipientBccMaps(self::FILE_RECIPIENT_BCC);
				# just need to look for recipient
				if (in_array(self::FILE_RECIPIENT_BCC, $list))
					return true;
				else
					return false;
			} catch (FileNoMatchException $e) {
				return false;
			}
		}
	}

	/**
	 * Returns policy.
	 *
	 * @return string policy
	 * @throws EngineException
	 */

	function GetPolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["policy"];
	}

	/**
	 * Returns global attachment policy.
	 *
	 * @return int attachment
	 * @throws EngineException
	 */

	function GetAttachmentPolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["attachment"];
	}

	/**
	 * Returns recipient attachment policy.
	 *
	 * @param  string  $domain  the domain
	 * @return array attachment
	 * @throws EngineException
	 */

	function GetRecipientAttachmentPolicy($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["attachment-recipient[$domain]"];
	}

	/**
	 * Returns sender attachment policy.
	 *
	 * @param  string  $domain  the domain
	 * @return array attachment
	 * @throws EngineException
	 */

	function GetSenderAttachmentPolicy($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["attachment-sender[$domain]"];
	}

	/**
	 * Returns auto archive policy.
	 *
	 * @return int archive
	 * @throws EngineException
	 */

	function GetAutoArchivePolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["auto-archive"];
	}

	/**
	 * Returns encryption flag.
	 *
	 * @return boolean
	 * @throws EngineException
	 */

	function GetArchiveEncryption()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config["archive-encryption"];
	}

	/**
	 * Returns encryption password.
	 *
	 * @return string  the encryption password
	 * @throws EngineException
	 */

	function GetEncryptionPassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return base64_decode($this->config["encrypt-password"]);
	}

	/**
	 * Returns database password.
	 *
	 * @return string  the database password
	 * @throws EngineException
	 */

	function GetDatabasePassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (is_null($this->db))
			$this->_LoadDbConfig();

		return $this->db["archive.password"];
	}

	/** Returns an array containing parts of an email.
	 *
	 * @param string $db_name database name to connect to
	 * @param integer $id the message ID
	 * @return array an associative array
     * @throws EngineException
	 */

	function GetArchivedEmail($db_name, $id)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->_SqlGetArchivedEmail($db_name, $id);
	}

	/** Returns an estimate of the current archival.
	 *
	 * @return array an associative array
     * @throws EngineException
	 */

	function GetCurrentStats()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$stats = $this->_GetStats(self::DB_NAME_CURRENT);

		return $stats;
	}

	/** Returns an estimate of the search archival.
	 *
	 * @return array an associative array
     * @throws EngineException
	 */

	function GetSearchStats()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$stats = $this->_GetStats(self::DB_NAME_SEARCH);

		return $stats;
	}

	/** Deletes a symbolic link to a search in the Flexshare directory.
	 *
	 * @return void
     * @throws EngineException
     * @throws FileNotFoundException
	 */

	function DeleteArchive($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		# Check if sym link exists
		$file = new File(self::DIR_LINKS . "/$filename", true);
		if ($file->IsSymLink() == 0)
			throw new FileNotFoundException($filename, COMMON_INFO);

		$file->Delete();
	}

	/**
	 * Returns a list of valid archive schedule options.
	 *
	 * @returns array
	 */

	function GetArchiveScheduleOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = Array(
			self::ARCHIVE_NEVER=>ARCHIVE_LANG_AUTO_ARCHIVE_NEVER,
			self::ARCHIVE_WEEK=>ARCHIVE_LANG_AUTO_ARCHIVE_WEEK,
			self::ARCHIVE_MONTH=>ARCHIVE_LANG_AUTO_ARCHIVE_MONTH,
			self::ARCHIVE_QUARTER=>ARCHIVE_LANG_AUTO_ARCHIVE_QUARTER,
			self::ARCHIVE_YEAR=>ARCHIVE_LANG_AUTO_ARCHIVE_YEAR,
			self::ARCHIVE_SIZE10=>ARCHIVE_LANG_AUTO_ARCHIVE_SIZE_10,
			self::ARCHIVE_SIZE100=>ARCHIVE_LANG_AUTO_ARCHIVE_SIZE_100,
			self::ARCHIVE_SIZE1000=>ARCHIVE_LANG_AUTO_ARCHIVE_SIZE_1000,
			self::ARCHIVE_SIZE10000=>ARCHIVE_LANG_AUTO_ARCHIVE_SIZE_10000
		);
		return $options;
	}

	/**
	 * Returns a list of valid maximum results returned options.
	 *
	 * @returns array
	 */

	function GetMaxResultOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = Array(
			10=>10,
			20=>20,
			30=>30,
			40=>40,
			50=>50,
			100=>100,
			250=>250,
			500=>500
		);
		return $options;
	}

	/**
	 * Returns a list of valid fields to search on.
	 *
	 * @returns array
	 */

	function GetSearchFieldOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = Array(
			self::SEARCH_SUBJECT=>ARCHIVE_LANG_SUBJECT,
			self::SEARCH_FROM=>ARCHIVE_LANG_FROM,
			self::SEARCH_BODY=>ARCHIVE_LANG_BODY,
			self::SEARCH_DATE=>ARCHIVE_LANG_DATE,
			self::SEARCH_TO=>ARCHIVE_LANG_TO,
			self::SEARCH_CC=>ARCHIVE_LANG_CC
		);
		return $options;
	}

	/**
	 * Returns a list of valid search criteria.
	 *
	 * @returns array
	 */

	function GetSearchCriteriaOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = Array(
			self::CRITERIA_CONTAINS=>ARCHIVE_LANG_CONTAINS,
			self::CRITERIA_IS=>ARCHIVE_LANG_IS,
			self::CRITERIA_BEGINS=>ARCHIVE_LANG_BEGINS_WITH,
			self::CRITERIA_ENDS=>ARCHIVE_LANG_ENDS_WITH
		);
		return $options;
	}

	/**
	 * Returns a list of valid policy options to deal with attachments.
	 *
	 * @returns array
	 */

	function GetArchiveAttachmentOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = Array(
			self::DISCARD_ATTACH_NEVER=>ARCHIVE_LANG_NEVER,
			self::DISCARD_ATTACH_ALWAYS=>ARCHIVE_LANG_ALWAYS,
			self::DISCARD_ATTACH_1=>ARCHIVE_LANG_DISCARD_1,
			self::DISCARD_ATTACH_5=>ARCHIVE_LANG_DISCARD_5,
			self::DISCARD_ATTACH_10=>ARCHIVE_LANG_DISCARD_10,
			self::DISCARD_ATTACH_25=>ARCHIVE_LANG_DISCARD_25
		);
		return $options;
	}
			
	/*
	 * @param string $db_name database name to connect to
	 * @param array $field the field to search on
	 * @param array $criteria criteria to search on
	 * @param array $regex what to search onon
	 * @param int $max maximum number of records to return
	 * @param int $offset offset to apply on records to return
	 * @returns array
	 */

	function Search($db_name, $field, $criteria, $regex, $logical, $max, $offset)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$search = Array();
		# Filter out arrays with no regex
		for ($index = 0; $index < count($regex); $index++) {
			if ($regex[$index]) {
				$search[] = Array('field' => $field[$index], 'criteria' => $criteria[$index], 'regex' => $regex[$index]); 
			}
		}
		if (count($search) == 0)
			throw new EngineException(ARCHIVE_LANG_ERRMSG_INVALID_QUERY, COMMON_ERROR);
			
		return $this->_SqlSearch($db_name, $search, $logical, $max, $offset);
	}

	/** Spaws a background process to restore a message.
	 *
	 * @return void
     * @throws ValidationException EngineException
	 */

	function SpawnRestoreMessage($db_name, $ids, $email = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
        try {
			$mailer = new Mailer();
			$list = '';
			if (!is_null($email)) {
				$addresses = preg_split("/,|;/", $email);
				foreach ($addresses as $recipient) {
					$parsed_email = $mailer->_ParseEmailAddress($recipient);
					if (!$mailer->IsValidEmail($parsed_email['address']))
						throw new ValidationException(MAILER_LANG_RECIPIENT . " - " . LOCALE_LANG_INVALID);
					$list .= $parsed_email['name'] . ' <' . $parsed_email['address'] . '>;';
				}
				$list = substr($list, 0, strlen($list) - 1);
			} else {
				$list = null;
			}
			$args = $db_name . ' ' . escapeshellarg($ids) . ' ' . escapeshellarg($list);
            $options = array();
            $options['background'] = true;
            $shell = new ShellExec;
            $retval = $shell->Execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_RESEND, $args, true, $options);
			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}
        } catch (ValidationException $e) {
            throw new ValidationException($e->GetMessage(), COMMON_ERROR);
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/** Restores a message - note this function can take a while on large attachments - use SpawnRestoreMessage().
	 *
	 * @return void
     * @throws EngineException
     * @throws SqlException
	 */

	function RestoreMessage($db_name, $id, $email = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_SqlRestoreMessage($db_name, $id, $email);
	}

	/**
	 * Returns an array of archive files on the server.
	 *
	 * @return array a list of archives
	 * @throws EngineException
	 */

	function GetList()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$archives = array();

		try {
			$folder = new Folder(self::DIR_LINKS, true);

			if (! $folder->Exists())
				throw new FolderNotFoundException(self::DIR_LINKS, COMMON_ERROR);

			$contents = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (! $contents)
			return $archives;

		$ntptime = new NtpTime();
		date_default_timezone_set($ntptime->GetTimeZone());
		foreach ($contents as $filename) {
			if (! preg_match("/tar.gz$|enc$/", $filename))
				continue;
			$file = new File(self::DIR_LINKS . "/" . $filename, true);

			// OK...it just means the file was deleted between the time of the GetList and now
			if (!$file->Exists())
				continue;

			// Hack...check to see of file was just created (i.e. 10 seconds ago or less)
			// If so, ignore it...it is the un-encrypted temporary tar file
			if ($file->LastModified() > (time() -10))
				continue;

			if ($file->IsSymLink() > 0)
				$size = $file->GetSize();
			else
				$size = 0;
			$archives[] = Array(
				"filename" => $filename, "modified" => $file->LastModified(),
				"status" => $file->IsSymLink(), "size" => $size) ;
		}

		return array_reverse($archives);
	}

	/**
	 * Performs an archive...of the archive.
	 * @param  string  $filename archive search filename
	 * @param  boolean  $force  force the archival of data (ie. manually initiation)
	 * @param  boolean  $purge  purge current data once archived (used for testing)
	 *
	 * @throws EngineException, FileAlreadyExistsException
	 */

	public function ArchiveData($filename, $force = false, $purge = true)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Bail if service not enabled
		if (!$this->GetStatus())
			return;

		# Load configuration
		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Set default timezone
		$ntptime = new NtpTime();
		date_default_timezone_set($ntptime->GetTimeZone());

		$run = false;

		 # Get last archive date and skip archiving if run today
        if ($this->config['last_archive'] == date("Y-m-d") && !$force)
            return;

		# Gets status
		$stats = $this->GetCurrentStats();
		switch ($this->config['auto-archive']) {
			case self::ARCHIVE_NEVER:
				break;
			case self::ARCHIVE_WEEK:
 				# Every Sunday
				if (date("w") == 0)
					$run = true;
				break;
			case self::ARCHIVE_MONTH:
				# 1st of every month
				if (date("j") == 1)
					$run = true;
				break;
			case self::ARCHIVE_QUARTER:
 				# 1st of Jan, April, July or Oct (ie. quarterly)
				if ((date("n") == 1 || date("n") == 4 || date("n") == 7 || date("n") == 10) && date("j") == 1)
					$run = true;
				break;
			case self::ARCHIVE_YEAR:
				# Jan 1st
				if (date("n") == 1 && date("j") == 1)
					$run = true;
				break;
			case self::ARCHIVE_SIZE10:
				if ($stats["size"] > 10*1024*1024) 
					$run = true;
				break;
			case self::ARCHIVE_SIZE100:
				if ($stats["size"] > 100*1024*1024) 
					$run = true;
				break;
			case self::ARCHIVE_SIZE1000:
				if ($stats["size"] > 1*1024*1024*1024) 
					$run = true;
				break;
			case self::ARCHIVE_SIZE10000:
				if ($stats["size"] > 10*1024*1024*1024) 
					$run = true;
				break;
		}

		# Bail 
		if (!$run && !$force)
			return;

		$flexshare = new Flexshare();

		$flex_dir = Flexshare::SHARE_PATH . "/" . self::FLEXSHARE_SEARCH . "/";

		# Remove spacing
		$filename = ereg_replace(" ", "_", $filename);

		# Remove encrtiption extension if user added one
		$filename = ereg_replace(".enc$", "", $filename);

		# Remove file extension if user added one
		$filename = ereg_replace(".tar.gz$", "", $filename);

		# Add file extension
		$filename = $filename . ".tar.gz";
		
		if (!$this->IsValidFilename($filename))
			throw new EngineException (ARCHIVE_LANG_ERRMSG_FILENAME_INVALID, COMMON_ERROR);

		# Check if filename is a duplicate
		if ($this->GetArchiveEncryption())
			$file = new File(self::DIR_LINKS . "/$filename.enc", true);
		else
			$file = new File(self::DIR_LINKS . "/$filename", true);
		if ($file->IsSymLink() != 0)
			throw new FileAlreadyExistsException($filename, COMMON_ERROR);
		
		# Check that we have a flexshare defined
		try {
			$flexshare->GetShare(self::FLEXSHARE_SEARCH);
		} catch (FlexshareNotFoundException $e) {
			$flexshare->AddShare(self::FLEXSHARE_SEARCH, ARCHIVE_LANG_FLEXSHARE_DESC, self::SYS_USER);
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_ERROR);
		}

		# From archive.inc.php
		if (IsArchiveRunning())
            throw new EngineException(ARCHIVE_LANG_RUNNING, COMMON_WARNING);

        try {
			$pw = escapeshellarg($this->GetEncryptionPassword());
			$args = escapeshellarg($filename) . " c";
			$args .= " " . (($pw) ? $pw : "''");
			$args .= ($purge ? ' true' : ' false');
            $options = array();
            $options['background'] = true;
            $shell = new ShellExec;
            $retval = $shell->Execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_ARCHIVE, $args, true, $options);
			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Restores an archive to the search database.
	 * @param  string  $filename archive search filename
	 *
	 * @throws EngineException
	 */

	public function RestoreArchive($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!$this->IsValidFilename($filename))
			throw new EngineException (ARCHIVE_LANG_ERRMSG_FILENAME_INVALID, COMMON_ERROR);

		# Check if filename link exists
		$file = new File(self::DIR_LINKS . "/$filename", true);
		if ($file->IsSymLink() != 1)
			throw new FileNotFoundException($filename, COMMON_ERROR);

		# From archive.inc.php
		if (IsArchiveRunning())
            throw new EngineException(ARCHIVE_LANG_RUNNING, COMMON_WARNING);

        try {
			$args = escapeshellarg($filename) . " x";
			if (eregi(".enc$", $filename))
				$args .= " " . escapeshellarg($this->GetEncryptionPassword());
            $options = array();
            $options['background'] = true;
            $shell = new ShellExec;
            $retval = $shell->Execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_ARCHIVE, $args, true, $options);
			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Delete the current archive directory and clears database.
	 *
	 * @throws EngineException
	 */

	function ResetCurrent()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$folder = new Folder(self::DIR_CURRENT, true);
			if ($folder->Exists())
				$folder->Delete(true);

			# Recreate folder to free up inodes that return incorrect stats
			$folder->Create("root", "root", "0700");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Reset database
		$this->_SqlClear(self::DB_NAME_CURRENT);
	}

	/**
	 * Delete the search archive directory and clear database.
	 *
	 * @throws EngineException
	 */

	function ResetSearch()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$folder = new Folder(self::DIR_SEARCH, true);
			if ($folder->Exists())
				$folder->Delete(true);

			# Recreate folder to free up inodes that return incorrect stats
			$folder->Create("root", "root", "0700");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Reset database
		$this->_SqlClear(self::DB_NAME_SEARCH);
	}

	/** Run the bootstrap script.
	 *
	 * @return void
     * @throws EngineException
	 */

	function RunBootstrap()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// If we can connect, bootstrap is already complete
		try {
			$this->_Connect();
			return;
        } catch (Exception $e) { }

        try {
			$args = "";
            $shell = new ShellExec;
            $retval = $shell->Execute(self::FILE_BOOTSTRAP, $args, true);
			if ($retval != 0) {
				$errstr = $shell->GetLastOutputLine();
				throw new EngineException($errstr, COMMON_WARNING);
			}
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }
	}

	/**
	 * Gets the mailbox password.
	 *
	 * @returns  string
	 * @throws  EngineException
	 */

	function GetMailboxPassword()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Load configuration
		if (! $this->is_loaded)
			$this->_LoadConfig();

		return base64_decode($this->config["mbox-password"]);
	}

	/**
	 * Initialize archive mailbox.
	 *
	 * @return void
	 */

	function InitializeMailbox($reset = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		// Get password for mailserver authentication
		$currentpw = $this->GetMailboxPassword();

		// Catch the case where someone drops in an /etc/archive.conf file
		// with an existing password.

		if (!$reset && $currentpw) {
			try {
				$cyrus = new Cyrus();

				if ($cyrus->GetRunningState()) {
					$mbox = @imap_open("{localhost:143/imap/notls}INBOX", self::SYS_USER, $currentpw);
					$imaperrors = imap_errors();
					// KLUDGE: non-fatal errors can occur, so we have to look for a specific error message.
					// Bail -- password works
					$passed = true;
					foreach ($imaperrors as $error) {
						if (preg_match("/Can not authenticate to IMAP server/i", $error))
							$passed = false;
					}

					if ($passed)
						return;
				} else {
					// Bail -- password set, but Cyrus is not running
				}
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}
		}

		// Generate random password

		try {
			$directory = new ClearDirectory();
			$password = $directory->GeneratePassword();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// Check to see if archive user exists

		$adduser = false;

		try {
		    $user = new User(self::SYS_USER);
		    $currentinfo = $user->GetInfo();
		} catch (UserNotFoundException $e) {
		    $adduser = true;
		}

		try {
			if ($adduser) {
				$userinfo = array();
				$userinfo['password'] = $password;
				$userinfo['verify'] = $password;
				$userinfo['mailFlag'] = true;
				$userinfo['lastName'] = "Email";
				$userinfo['firstName'] = "Archive";
				$userinfo['uid'] = self::SYS_USER;
				$userinfo['homeDirectory'] = "/dev/null";
				$userinfo['loginShell'] = "/sbin/nologin";

				$user->Add($userinfo);
			} else {
				$userinfo = array();
				$userinfo['password'] = $password;
				$userinfo['verify'] = $password;

				if (! $currentinfo['mailFlag'])
				    $userinfo['mailFlag'] = true;

				$user->Update($userinfo);
			}

			$this->SetMailboxPassword($password, $password);

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
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

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	function _GetStats($db_name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$stats = Array("size" => 0);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		# Select the db name
		mysql_select_db($db_name);

		# Calculate estimated size
		$bytes = 0;
		try {
			# Add archive table
			$file = new File(self::DIR_MYSQL_ARCHIVE . "/" . $db_name . "/archive.MYD", true);
			if ($file->Exists())
				$bytes = $file->GetSize();
			# Add attachment table
			$file = new File(self::DIR_MYSQL_ARCHIVE . "/" . $db_name . "/attachment.MYD", true);
			if ($file->Exists())
				$bytes += $file->GetSize();
			# Add attachments directory
			if ($db_name == self::DB_NAME_CURRENT)
				$folder = new Folder(self::DIR_CURRENT);
			else
				$folder = new Folder(self::DIR_SEARCH);
			if ($folder->Exists())
				$bytes += $folder->GetSize();
			
			$stats["size"] = $bytes;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		try {
			$db_stats = $this->_SqlGetStats($db_name);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Merge two arrays and return
		$stats = array_merge($stats, $db_stats); 
		return $stats;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($this->link != null)
			mysql_close($this->link);

		parent::__destruct();
	}

	/**
	 * @access private
	 */

	function _ArchiveMessages()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Bail if lock file exists
		$lock = new File(self::FILE_LOCK, true);
		if ($lock->Exists()) {
			$ntptime = new NtpTime();
			date_default_timezone_set($ntptime->GetTimeZone());
			if ((time() - $lock->LastModified()) > (self::LOCK_TIMEOUT_HR *24))
				$lock->Delete();
			return;
		} else {
			$lock->Create("webconfig", "webconfig", "0640");
		}

		$input = '';
		$mailing_list = Array();
		$id = -1;

		# Get password for mailserver authentication
		$passwd = $this->GetMailboxPassword();
		
		$cyrus = new Cyrus();

		if ($cyrus->GetServiceState(Cyrus::CONSTANT_SERVICE_IMAP)) {
			$option = "/imap/notls";
			$port = 143; 
		} else if ($cyrus->GetServiceState(Cyrus::CONSTANT_SERVICE_IMAPS)) {
			$option = "/imap/ssl/novalidate-cert";
			$port = 993; 
		} else if ($cyrus->GetServiceState(Cyrus::CONSTANT_SERVICE_POP3)) {
			$option = "/pop3/notls";
			$port = 110; 
		} else if ($cyrus->GetServiceState(Cyrus::CONSTANT_SERVICE_POP3S)) {
			$option = "/pop3/ssl/novalidate-cert";
			$port = 995; 
		}
					
		list($user, $hostname) = explode('@', $this->GetArchiveAddress());

		# Check if local service
		if ($hostname == 'localhost') {
			if (!$cyrus->GetRunningState())
				return;
		} else {
			# Default POP3
			$port = 110;
			$option = "/pop3/notls";
			if (isset($this->config['archive_port_override']))
				$port = $this->config['archive_port_override'];
			if (isset($this->config['archive_protocol_override']))
				$option = $this->config['archive_protocol_override'];
			if (isset($this->config['archive_password_override']))
				$passwd = $this->config['archive_password_override'];
		}

		$mbox = @imap_open("{" . $hostname . ":$port$option}INBOX", $user, $passwd);
		$ignore = imap_errors();

		if (! $mbox)
			return;

		$exp_counter = 0;
		for ($index = 1; $index <= imap_num_msg($mbox); $index++) {

			if ($exp_counter > 1000) {
				# Useful in event a problem prevents completion of import
				imap_expunge($mbox);
				$exp_counter = 0;
			} else {
				$exp_counter++;
			}
			# Check for ignore flag
			if (!preg_match("/pcn-archive-ignore/", imap_fetchheader($mbox, $index))) {
				$id = $this->_SqlInsertMessage($mbox, $index);
				$sender_domain = "";
				$recipient_domain = "";
				# Get domain names to use for attachment handling
				try {
					$headers = imap_headerinfo($mbox, $index);
					$sender_domain = $headers->from[0]->host;
					$recipient_domain = $headers->to[0]->host;
				} catch (Exception $e) {
					# Ignore
				}
				$this->_SqlInsertAttachments($mbox, $index, $id, $sender_domain, $recipient_domain);
			}
			# Delete messages
			imap_delete($mbox, $index);
		}

		imap_close($mbox, CL_EXPUNGE);
		$lock->Delete();
	}

	/**
	 * @access private
	 */

	function _SqlRestoreMessage($db_name, $id, $email)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		# Select the db name
		mysql_select_db($db_name);

		$mailer = new Mailer();

		$sql = sprintf("SELECT *, UNIX_TIMESTAMP(sent) AS unixsent FROM archive WHERE id = '%d'", $id);
		$result = mysql_query($sql);
		$result_set = Array();
		$index = 0;
        if ($result) {
			if (mysql_num_rows($result) == 0)
				throw new EngineException (ARCHIVE_LANG_ERRMSG_RECORD_NOT_FOUND, COMMON_ERROR);
			if (mysql_num_rows($result) > 1)
				throw new EngineException (LOCALE_LANG_ERRMSG_WEIRD . " (" . __LINE__ . ")", COMMON_ERROR);
            $result_set = mysql_fetch_array($result, MYSQL_ASSOC);
            mysql_free_result($result);
		} else {
			throw new EngineException (ARCHIVE_LANG_ERRMSG_DB_ERROR . " (" . __LINE__ . ")", COMMON_ERROR);
        }

		if ($email == null)
			$email = $result_set['recipient'];

		# Recipient(s)
		$recipient = Array();
		$addresses = preg_split("/,|;/", $email);
		if (empty($addresses))
			$addresses[] = $email;
			
		try {
			$sql = sprintf("SELECT * FROM attachment WHERE archive_id = '%d' ORDER BY id", $id);
			$result = mysql_query($sql);
			if ($result) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$part = $line;
					# This screws up eml attachments
					# No idea why...but doesn't seem to be needed
					if (strtolower($part['type']) == 'multipart/alternative')
						continue;
					if ($part['cid'])
						$part['Content-ID'] = $part['cid'];
					else
						unset($part['cid']);
					if ($part['filename']) {
						$dir = self::DIR_CURRENT; 
						if ($db_name == self::DB_NAME_SEARCH)
							$dir = self::DIR_SEARCH; 
						$file = new File($dir . "/" . $part['md5'], true);
						if ($file->Exists()) {
							$file->CopyTo(COMMON_TEMP_DIR . "/" . $part['filename']);
							$file->Chown("webconfig", "webconfig");
							$file->Chmod("0640");
						} else {
							$file->Create("webconfig", "webconfig", "0640");
							$file->AddLines(ARCHIVE_LANG_ATTACHMENT_DOES_NOT_EXIST . "\n");
						}
						# Set filename with path
						$part['filename'] = COMMON_TEMP_DIR . "/" . $part['filename'];
						$filesfordeletion[] = $part['filename'];
					} else if ($part['encoding'] == "base64") {
						$part['data'] = base64_decode($part['data']);
					}

					$mailer->SetPart($part);
				}
				mysql_free_result($result);
			} else {
				throw new EngineException (ARCHIVE_LANG_ERRMSG_DB_ERROR . " (" . __LINE__ . ")", COMMON_ERROR);
			}
       	} catch (Exception $e) {
			self::Log(COMMON_ERROR, 'Failed to save attachment ' . $e->GetMessage(), __METHOD__, __LINE__);
		}

		foreach ($addresses as $recipient)
			$mailer->AddRecipient($recipient);

		if (isset($result_set['subject']))
			$mailer->SetSubject($result_set['subject']);
		if (isset($result_set['body']))
			$mailer->SetBody($result_set['body']);
		$mailer->OverrideDate($result_set['unixsent']);
		$mailer->SetReplyTo($result_set['sender']);
		$mailer->SetSender($result_set['sender']);
		
		$mailer->Send();

		# Clean up
		if (isset($filesfordeletion)) {
			foreach ($filesfordeletion as $filename) {
				$file = new File($filename, true);
				$file->Delete();
			}
		}
	}

	/**
	 * @access private
	 */

	function _SqlGetArchivedEmail($db_name, $id)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		# Select the db name
		mysql_select_db($db_name);
		mysql_query('SET NAMES UTF8', $this->link);

		$sql = "SELECT id, subject, sender, recipient, cc, header, UNIX_TIMESTAMP(sent) AS sent, body ";
		$sql .= sprintf("FROM archive WHERE id = '%d'", $id);
		if (isset($_SESSION['user_login']) && $_SESSION['user_login'] != 'root') {
			$sql .= " AND (recipient LIKE '" . $_SESSION['user_login'] . "@%' " .
				"OR sender LIKE '" . $_SESSION['user_login'] . "@%' " .
				"OR cc LIKE '" . $_SESSION['user_login'] . "@%' " .
				"OR bcc LIKE '" . $_SESSION['user_login'] . "@%' " .
				"OR recipient LIKE '%<" . $_SESSION['user_login'] . "@%' " .
				"OR sender LIKE '%<" . $_SESSION['user_login'] . "@%' " .
				"OR cc LIKE '%<" . $_SESSION['user_login'] . "@%' " .
				"OR bcc LIKE '%<" . $_SESSION['user_login'] . "@%')";
		};
		$result = mysql_query($sql);
		$result_set = Array();
		$index = 0;
        if ($result) {
			if (mysql_num_rows($result) == 0) {
				if (isset($_SESSION['user_login']) && $_SESSION['user_login'] != 'root')
					throw new EngineException (LOCALE_LANG_ACCESS_DENIED, COMMON_ERROR);
				else
					throw new EngineException (ARCHIVE_LANG_ERRMSG_RECORD_NOT_FOUND, COMMON_ERROR);
			}
			if (mysql_num_rows($result) > 1)
				throw new EngineException (LOCALE_LANG_ERRMSG_WEIRD . " (" . __LINE__ . ")", COMMON_ERROR);
            $result_set = mysql_fetch_array($result, MYSQL_ASSOC);
            mysql_free_result($result);
		} else {
			throw new EngineException (ARCHIVE_LANG_ERRMSG_DB_ERROR . " (" . __LINE__ . ")", COMMON_ERROR);
        }

		# Attachments
		$sql = sprintf("SELECT * FROM attachment WHERE archive_id = '%d' ORDER BY id", $id);
		$result = mysql_query($sql);
		if ($result) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				# If true attachment, add
				if (isset($line['filename'])) {
					$attachments[] = $line;
				# Check config to see what to display
				} else if (isset($this->config['display-type'])) {
					if (eregi(ereg_replace("/", "\/", $this->config['display-type']), $line['type']))
						$attachments[] = $line;
				} else {
					#Default to text/plain
					if (eregi("text\/plain", $line['type']))
						$attachments[] = stripslashes($line);
				}
			}
			mysql_free_result($result);
			# Tack on attachments to return array
			$result_set['attachments'] = $attachments;
		}
		return $result_set;
	}

	/**
	 * @access private
	 */

	function _SqlSearch($db_name, $search, $logical, $max, $offset)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		if (!isset($offset) || !$offset)
			$offset = 0;
		# Select the db name
		mysql_select_db($db_name);

		$result_set = Array();

		try {
			mysql_query('SET NAMES UTF8', $this->link);
			$sql = "SELECT id, subject, sender, recipient, cc, UNIX_TIMESTAMP(sent) AS sent FROM archive WHERE ";
			$isfirst = true;
			foreach ($search as $conditional) {
				if (! $isfirst)
					$sql .= " $logical "; 
				# Determine field
				switch ($conditional['field']) {
				case self::SEARCH_SUBJECT:
					$sql.= '(subject ';
					break;
				case self::SEARCH_FROM:
					$sql.= '(sender ';
					break;
				case self::SEARCH_DATE:
					$sql.= '(sent ';
					break;
				case self::SEARCH_TO:
					$sql.= '(recipient ';
					break;
				case self::SEARCH_CC:
					$sql.= '(cc ';
					break;
				case self::SEARCH_BODY:
					$sql.= '(body ';
					break;
				}
				# Change asterisk to percent since normal association with wildcard is *
				if ($conditional['regex'] == '*')
					$conditional['regex'] = '%';

				# Determine criteria
				# Protect against single quote
				$conditional['regex'] = addslashes($conditional['regex']);
				switch ($conditional['criteria']) {
				case self::CRITERIA_CONTAINS:
					$sql.= "LIKE '%" . $conditional['regex'] . "%') ";
					break;
				case self::CRITERIA_IS:
					$sql.= "= '" . $conditional['regex'] . "') ";
					break;
				case self::CRITERIA_BEGINS:
					$sql.= "LIKE '" . $conditional['regex'] . "%') ";
					break;
				case self::CRITERIA_ENDS:
					$sql.= "LIKE '%" . $conditional['regex'] . "') ";
					break;
				}
				$isfirst = false;
			}
			if (isset($_SESSION['user_login']) && $_SESSION['user_login'] != 'root') {
				$sql .= " AND (recipient LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR sender LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR cc LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR bcc LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR recipient LIKE '%<" . $_SESSION['user_login'] . "@%' " .
					"OR sender LIKE '%<" . $_SESSION['user_login'] . "@%' " .
					"OR cc LIKE '%<" . $_SESSION['user_login'] . "@%' " .
					"OR bcc LIKE '%<" . $_SESSION['user_login'] . "@%')";
			};
			$sql .= ' LIMIT ' . $max . ' OFFSET ' . $offset;  
			$result = mysql_query($sql);
			if ($result) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC))
					$result_set[] = $line;
				mysql_free_result($result);
			}
			
			# Check 'attachment' body
			$sql = "SELECT archive.id, archive.subject, archive.sender, archive.recipient, ";
			$sql .= "archive.cc, UNIX_TIMESTAMP(archive.sent) AS sent ";
			$sql .= "FROM archive, attachment WHERE ";
			$sql .= "attachment.archive_id = archive.id AND ";
			$isfirst = true;
			foreach ($search as $conditional) {
				$containsbody = false;
				if (! $isfirst)
					$sql .= " $logical "; 
				# Determine field
				switch ($conditional['field']) {
				case self::SEARCH_BODY:
					$sql.= '(attachment.data ';
					$containsbody = true;
					break;
				}

				if (!$containsbody)
					continue;
				# Determine criteria
				# Protect against single quote
				$conditional['regex'] = addslashes($conditional['regex']);
				switch ($conditional['criteria']) {
				case self::CRITERIA_CONTAINS:
					$sql.= "LIKE '%" . $conditional['regex'] . "%') ";
					break;
				case self::CRITERIA_IS:
					$sql.= "= '" . $conditional['regex'] . "') ";
					break;
				case self::CRITERIA_BEGINS:
					$sql.= "LIKE '" . $conditional['regex'] . "%') ";
					break;
				case self::CRITERIA_ENDS:
					$sql.= "LIKE '%" . $conditional['regex'] . "') ";
					break;
				}
				$isfirst = false;
			}
			if (isset($_SESSION['user_login']) && $_SESSION['user_login'] != 'root') {
				$sql .= " AND (archive.recipient LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR archive.sender LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR archive.cc LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR archive.bcc LIKE '" . $_SESSION['user_login'] . "@%' " .
					"OR archive.recipient LIKE '%<" . $_SESSION['user_login'] . "@%' " .
					"OR archive.sender LIKE '%<" . $_SESSION['user_login'] . "@%' " .
					"OR archive.cc LIKE '%<" . $_SESSION['user_login'] . "@%' " .
					"OR archive.bcc LIKE '%<" . $_SESSION['user_login'] . "@%')";
			};
			$sql .= ' LIMIT ' . $max . ' OFFSET ' . $offset;  
			$result = mysql_query($sql);
			if ($result) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC))
					$result_set[] = $line;
				mysql_free_result($result);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		return $result_set;
	}

	/**
	 * @access private
	 */

	function _SqlGetStats($db_name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$db_stats = Array();

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		# Select the db name
		mysql_select_db($db_name);

		try {
			$sql = "SELECT count(id) AS messages FROM archive";
			$result = mysql_query($sql);
			if ($result) {
				$db_stats = array_merge($db_stats, mysql_fetch_array($result, MYSQL_ASSOC));
				mysql_free_result($result);
			} else {
				$db_stats['messages'] = 0;
			}
			$sql = "SELECT created AS last FROM archive WHERE id IN (SELECT min(id) FROM archive)";
			$result = mysql_query($sql);
			if ($result) {
				if (mysql_num_rows($result) == 1)
					$db_stats = array_merge($db_stats, mysql_fetch_array($result, MYSQL_ASSOC));
				else
					$db_stats['last'] = date("Y-m-d H:i");
				mysql_free_result($result);
			} else {
				$db_stats['last'] = date("Y-m-d H:i");
			}
			$sql = "SELECT count(id) AS attachments FROM attachment WHERE filename IS NOT NULL";
			$result = mysql_query($sql);
			if ($result) {
				$db_stats = array_merge($db_stats, mysql_fetch_array($result, MYSQL_ASSOC));
				mysql_free_result($result);
			} else {
				$db_stats['attachments'] = 0;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		return $db_stats;
	}

	/**
	 * @access private
	 */

	function _FlatMimeDecode($string) {
		$array = imap_mime_header_decode($string);
		$str = "";
		foreach ($array as $key => $part) {
			$str .= $part->text;
		}
		return $str;
	}

	/**
	 * @access private
	 */

	function _SqlInsertMessage($mbox, $index)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		mysql_select_db(self::DB_NAME_CURRENT);

		if (mysql_errno() != 0)
			throw new SqlException(mysql_error(), COMMON_ERROR);

		$headers = imap_headerinfo($mbox, $index);

		$structure = imap_fetchstructure($mbox, $index);

		try {
			# Swift mailer logs a warning message if we don't set this
			$ntptime = new NtpTime();
			date_default_timezone_set($ntptime->GetTimeZone());
			# Decode headers
			$fromaddress = $this->_FlatMimeDecode($headers->fromaddress);
			$toaddress = $this->_FlatMimeDecode($headers->toaddress);
			if (isset($headers->ccaddress))
				$ccaddress = $this->_FlatMimeDecode($headers->ccaddress);
			else
				$ccaddress = '';
			$subject = $this->_FlatMimeDecode($headers->subject);
			$sql = "INSERT INTO archive (sender, recipient, cc, bcc, subject, size, sent, header, body, created)";
			$sql .=	sprintf(" VALUES ('%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', now());",
					addslashes($fromaddress),
					addslashes($toaddress),
					addslashes($ccaddress),
					'',
					addslashes($subject),
					$headers->Size, date("Y-m-d H:i:s", $headers->udate),
					addslashes(imap_fetchheader($mbox, $index)),
					$structure->subtype == 'PLAIN' ? addslashes(imap_body($mbox,$index)): ''
			);
			$result = mysql_query($sql);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		if (!$result) {
			throw new EngineException (ARCHIVE_LANG_ERRMSG_DB_ERROR . " - " . mysql_error(), COMMON_ERROR);
		} else  {
			return mysql_insert_id();
		}
	}

	/**
	 * @access private
	 */

	function _SqlInsertAttachments($mbox, $index, $id, $sender, $recipient)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		mysql_select_db(self::DB_NAME_CURRENT);

		$mime = new Mime();

		$structure = imap_fetchstructure($mbox, $index);
		$msgparts = $mime->GetParts($structure);
		foreach ($msgparts as $partid => $msgpart) {
			try {
				if (!$msgpart['size'])
					continue;
				if (isset($msgpart['name'])) {
					# Make sure config is loaded
					if (! $this->is_loaded)
						$this->_LoadConfig();

					$keep_attach = true;
					# Determine policy to use
					if ($this->config['policy'] == 'all')
						$policy = $this->config["attachment"];
					else if (isset($this->config["attachment-recipient[$recipient]"]))
						$policy = $this->config["attachment-recipient[$recipient]"];
					else if (isset($this->config["attachment-sender[$sender]"]))
						$policy = $this->config["attachment-sender[$sender]"];
					else
						$policy = self::DISCARD_ATTACH_NEVER; 

					switch ($policy) {
						case self::DISCARD_ATTACH_NEVER:
							break;
						case self::DISCARD_ATTACH_ALWAYS:
							$keep_attach = false;
							break;
						case self::DISCARD_ATTACH_1:
							if ($msgpart['size'] > 1*1024*1024)
								$keep_attach = false;
							break;
						case self::DISCARD_ATTACH_5:
							if ($msgpart['size'] > 5*1024*1024)
								$keep_attach = false;
							break;
						case self::DISCARD_ATTACH_10:
							if ($msgpart['size'] > 10*1024*1024)
								$keep_attach = false;
							break;
						case self::DISCARD_ATTACH_25:
							if ($msgpart['size'] > 25*1024*1024)
								$keep_attach = false;
							break;
					}

					# Skip if conditions are met to discard attachment
					if (! $keep_attach) {
						Logger::Syslog(
							self::LOG_TAG, "Attachment not archived, to=$recipient, send=$sender, size={$msgpart['size']}"
						);
						continue;
					}
					
					$folder = new Folder(self::DIR_CURRENT, true);
					if (!$folder->Exists())
						$folder->Create("root", "root", "0700");
					
					$filename = preg_replace("/([^a-zA-Z0-9.\\-\\_])/", "_", $msgpart['name']);
					$file = new File($folder->GetFoldername() . "/" . $filename, true);

					if (imap_fetchbody($mbox, $index, $partid) == "")
						continue;

					if ($file->Exists())
						$file->Delete();
					$file->Create("root", "webconfig", "0640");
					if ($msgpart['encoding'] == "base64")
						$file->AddLines(base64_decode(imap_fetchbody($mbox, $index, $partid)));
					else
						$file->AddLines(imap_fetchbody($mbox, $index, $partid));
					$md5 = $file->GetMd5();
					$file->MoveTo($folder->GetFoldername() . "/$md5"); 
					$sql = "INSERT INTO attachment (archive_id, type, encoding, cid, charset, " .
						   "disposition, filename, md5, size)";
					$sql .=	sprintf(" VALUES ('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')",
							$id, $msgpart['type'], $msgpart['encoding'],
							isset($msgpart['Content-ID']) ? $msgpart['Content-ID'] : '',
							isset($msgpart['charset']) ? $msgpart['charset'] : '',
							$msgpart['disposition'], $filename, $md5, $msgpart['size']);
				} else {
					$sql = "INSERT INTO attachment (archive_id, type, encoding, cid, charset, " .
						   "disposition, size, data)";
					if ($msgpart['encoding'] == "quoted-printable") {
						$sql .=	sprintf(" VALUES ('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')",
							$id, $msgpart['type'], $msgpart['encoding'],
							isset($msgpart['Content-ID']) ? $msgpart['Content-ID'] : '',
							isset($msgpart['charset']) ? $msgpart['charset'] : '',
							$msgpart['disposition'], $msgpart['size'],
							addslashes(quoted_printable_decode(imap_fetchbody($mbox, $index, $partid))));
					} else {
						$sql .=	sprintf(" VALUES ('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')",
							$id, $msgpart['type'], $msgpart['encoding'],
							isset($msgpart['Content-ID']) ? $msgpart['Content-ID'] : '',
							isset($msgpart['charset']) ? $msgpart['charset'] : '',
							$msgpart['disposition'], $msgpart['size'],
							addslashes(imap_fetchbody($mbox, $index, $partid)));
					}
				}
				$result = mysql_query($sql);

				if (!$result) 
					throw new EngineException(ARCHIVE_LANG_ERRMSG_DB_ERROR . " - " . mysql_error(), COMMON_ERROR);
        	} catch (Exception $e) {
				self::Log(COMMON_ERROR, 'Failed to save attachment ' . $e->GetMessage(), __METHOD__, __LINE__);
			}
		}
	}

	/**
	 * @access private
	 */

	function _SqlClear($db_name)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if ($this->link == null)
			$this->_Connect();

		# Select the db name
		mysql_select_db($db_name);

		try {
			$sql = "DELETE FROM archive";
			$result = mysql_query($sql);
			$sql = "DELETE FROM attachment";
			$result = mysql_query($sql);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (!$result)
			throw new EngineException (ARCHIVE_LANG_ERRMSG_DB_ERROR . " - " . mysql_error(), COMMON_ERROR);
	}

	/**
	 * Delete the current archive directory.
	 *
	 * @access private
	 * @throws EngineException
	 */

	private function _CurrentDirClear()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$folder = new Folder(self::DIR_CURRENT);

			if (! $folder->Exists())
				throw new FolderNotFoundException(self::DIR_CURRENT, COMMON_ERROR);
			$contents = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (! $contents)
			return;

		foreach ($contents as $filename) {
			$file = new File(self::DIR_CURRENT . "/" . $filename, true);
			$file->Delete();
		}
	}

	/**
	 * Connects to mysql database.
	 *
	 * @throws ConfigurationFileException
	 * @throws FileException
	 * @throws SqlException
	 * @see _LoadDbConfig for the inherited exceptions
	 * @return void
	 */

	private function _Connect()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->db))
			$this->_LoadDbConfig();

		$this->link = mysql_connect(self::DB_HOST . ":" . self::SOCKET_MYSQL, self::DB_USER, $this->db['archive.password']);
		if (!$this->link)
			throw new SqlException (mysql_error(), COMMON_ERROR);

		# Check to see if we can connect to the database
		mysql_select_db(self::DB_NAME_CURRENT);
		if (mysql_errno() != 0)
			throw new SqlException(mysql_error(), COMMON_ERROR);
	}

	/**
     * Read database parameters from file
     *
     * @throws EngineException
     * @return void
     */

    private function _LoadDbConfig()
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        try {
            $file = new ConfigurationFile(self::FILE_CONFIG_DB, 'explode', '=', 2);
            $this->db = $file->Load();
        } catch (FileNotFoundException $e) {
            throw new EngineException($e->GetMessage());
        }
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

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * Generic set routine.
	 *
	 * @private
	 * @param  string  $key  key name
	 * @param  string  $value  value for the key
	 * @return  void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {
			$regex = str_replace("[", "\\[", $key);
			$regex = str_replace("]", "\\]", $regex);
            $file = new File(self::FILE_CONFIG, true);
            $match = $file->ReplaceLines("/^$regex\s*=\s*/", "$key = $value\n");
            if (!$match)
                $file->AddLines("$key = $value\n");
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

		$this->is_loaded = false;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Validation routine for filename.
	 *
	 * @param  string  $filename  archive filename
	 * @returns  boolean
	 */

	function IsValidFilename($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/^([A-Za-z0-9\-\.\_\'\:]+)$/", $filename))
			return true;

		$this->AddValidationError(ARCHIVE_LANG_ERRMSG_FILENAME_INVALID, __METHOD__, __LINE__);
		return false;
	}

	/**
	 * Validation routine for encryption password.
	 *
	 * @param  string  $password  encryption password
	 * @returns  boolean
	 */

	function IsValidPassword($pwd)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Get length of password
		if (isset($this->config['encrypt-password-length']))
			$min = $this->config['encrypt-password-length'];
		else
			$min = 12;
		# TODO - use crack lib?
		if (strlen($pwd) >= $min && ereg("[A-Z]", $pwd) && ereg("[a-z]", $pwd) && ereg("[0-9]", $pwd))
			return true;
		$this->AddValidationError(ereg_replace("[[:digit:]]+", "$min", ARCHIVE_LANG_ERRMSG_PASSWORD_INVALID), __METHOD__, __LINE__);
		return false;

	}
}
// vim: syntax=php ts=4
?>
