<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2008 Point Clark Networks.
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
 * SMTP (Postfix) class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Daemon.class.php');
require_once('Network.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Postfix mail server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2007, Point Clark Networks
 */

class Postfix extends Daemon
{
	const FILE_CONFIG = '/etc/postfix/main.cf';
	const FILE_CONFIG_MASTER = '/etc/postfix/master.cf';
	const FILE_VIRTUAL = '/etc/postfix/virtual';
	const FILE_POLICY = '/usr/share/system/settings/postfix';
	const FILE_TRANSPORT = '/etc/postfix/transport';
	const FILE_SENDER_BCC = '/etc/postfix/sender_bcc_maps';
	const FILE_RECIPIENT_BCC = '/etc/postfix/recipient_bcc_maps';
    const CMD_POSTCONF = '/usr/sbin/postconf';
	const CMD_POSTFIX = '/usr/sbin/postfix';
	const CMD_POSTMAP = '/usr/sbin/postmap';
	const CMD_POSTSUPER = '/usr/sbin/postsuper';
	const CMD_MAILQ_FORMATTED = '/usr/sbin/mailqfmt.pl';
	const LOCALHOST = '127.0.0.0/8';
	const CONSTANT_SPACE = 1;
	const CONSTANT_MODE_SERVER = "server";
	const CONSTANT_MODE_GATEWAY = "gateway";
	const CONSTANT_BOUNCE = 'bounce';

    protected $is_loaded = false;
    protected $config = array();

	// Defaults for Postfix
	const DEFAULT_MAX_MAILBOX_SIZE = 51200000;
	const DEFAULT_MAX_MESSAGE_SIZE = 10240000;
	const DEFAULT_SMTP_AUTHENTICATION = false;
	const DEFAULT_LOCAL_RECIPIENT_MAPS = 'proxy:unix:passwd.byname $alias_maps';

    // Blacklists
    const CONSTANT_SA_BLACKLIST = "sa";
    const CONSTANT_MALWARE_BLACKLIST = "malware";
    const CONSTANT_CUSTOM_BLACKLIST = "custom";
    const FILE_SA_BLACKLIST = '/etc/postfix/filters/sa-blacklist';
    const FILE_MALWARE_BLACKLIST = '/etc/postfix/filters/malware';
    const FILE_CUSTOM_BLACKLIST = '/etc/postfix/filters/custom-blacklist';

    protected $blacklist_params = array();
    protected $blacklist_files = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Postfix constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('postfix');

        $this->blacklist_params = array('smtpd_client_restrictions', 'smtpd_sender_restrictions');
        $this->blacklist_files[self::CONSTANT_SA_BLACKLIST] = self::FILE_SA_BLACKLIST;
        $this->blacklist_files[self::CONSTANT_MALWARE_BLACKLIST] = self::FILE_MALWARE_BLACKLIST;
        $this->blacklist_files[self::CONSTANT_CUSTOM_BLACKLIST] = self::FILE_CUSTOM_BLACKLIST;

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	///////////////////////////////////////////////////////////////////////////
	// M A I N  M E T H O D S												//
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Adds a destination domain.
	 *
	 * @param string $destination destination domain
	 * @return void
	 * @throws EngineException
	 */

	function AddDestination($destination)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);


		// Validate
		//---------

		$network = new Network();

		if (! $network->IsValidDomain($destination)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_DESTINATION_INVALID, __METHOD__, __LINE__);
			return;
		}

		$virtuals = $this->GetVirtualDomains();

		if (in_array($destination, $virtuals)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_EXISTS, __METHOD__, __LINE__);
			return;
		}

		$destinations = $this->GetDestinations();

		if (in_array($destination, $destinations)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_DESTINATION_EXISTS, __METHOD__, __LINE__);
			return;
		}

		// Add relay host
		//---------------

		$this->_AddListItem("mydestination", ",", $destination);
	}

	/**
	 * Adds a mail forwarder.
	 *
	 * @param string $domain domain name
	 * @param string $server target mail server
	 * @param int $port target mail port
	 * @return void
	 * @throws EngineException
	 */

	function AddForwarder($domain, $server, $port)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		$is_valid = true;

		$network = new Network();

		if (! $network->IsValidDomain($domain)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_FORWARD_DOMAIN_INVALID, __METHOD__, __LINE__);
			$is_valid = false;
		}

		if (! ($network->IsValidDomain($server) || $network->IsValidIp($server))) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_FORWARDER_INVALID, __METHOD__, __LINE__);
			$is_valid = false;
		}

		if (! $network->IsValidPort($port)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_PORT_INVALID, __METHOD__, __LINE__);
			$is_valid = false;
		}

		// Check for duplicates
		$forwarders = array();

		try {
			$forwarders = $this->GetForwarders();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		foreach ($forwarders as $forwardinfo) {
			if ($forwardinfo['domain'] == $domain) {
				$this->AddValidationError(POSTFIX_LANG_ERRMSG_FORWARDER_EXISTS, __METHOD__, __LINE__);
				$is_valid = false;
			}
		}

		if (!$is_valid)
			return;

		// Add to list of forwarders
		//--------------------------

		$forwardinfo['domain'] = $domain;
		$forwardinfo['server'] = $server;
		$forwardinfo['port'] = $port;

		$forwarders[] = $forwardinfo;

		$this->SetForwarders($forwarders);
		$this->_AddListItem('relay_domains', ',', $domain);
	}

	/**
	 * Adds a relay host.
	 *
	 * @param string $relayhost relay host
	 * @return void
	 * @throws EngineException
	 */

	function AddRelayHost($relayhost)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidHostWithPort($relayhost)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_RELAYHOST_INVALID, __METHOD__, __LINE__);
			return;
		}

		// Add relay host
		//---------------

		$this->_AddListItem('relayhost', self::CONSTANT_SPACE, "[$relayhost]");
	}

	/**
	 * Adds a trusted network.
	 *
	 * @param string $trustednetwork trusted network for relaying
	 * @return void
	 * @throws EngineException
	 */

	function AddTrustedNetwork($trustednetwork)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidTrustedNetwork($trustednetwork)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_NETWORK_INVALID, __METHOD__, __LINE__);
			return;
		}

		// Add trusted network
		//--------------------

		$this->_AddListItem('mynetworks', ',', $trustednetwork);
	}

	/**
	 * Adds virtual domain.
	 *
	 * @param string $domain virtual domain
	 * @return void
	 * @throws EngineException
	 */

	function AddVirtualDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		$network = new Network();

		if (! $network->IsValidDomain($domain)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_INVALID, __METHOD__, __LINE__);
			return;
		}

		$virtuals = $this->GetVirtualDomains();

		if (in_array($domain, $virtuals)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_EXISTS, __METHOD__, __LINE__);
			return;
		}

		$destinations = $this->GetDestinations();

		if (in_array($domain, $destinations)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_DESTINATION_EXISTS, __METHOD__, __LINE__);
			return;
		}

		// Add virtual domain
		//-------------------

		try {
			$file = new File(self::FILE_VIRTUAL);
			$file->AddLines($domain . " virtualdomain\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->SetVirtualMaps("hash:/etc/postfix/virtual");

		// Run postmap
		//------------

		$shell = new ShellExec();

		if ($shell->Execute(self::CMD_POSTMAP, self::FILE_VIRTUAL, true) != 0)
			throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
	}

	/**
	 * Add a file to use to match for recipient BCC.
	 *
	 * @param  array  $filename  the filename to be used to match recipient BCC address
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function AddRecipientBccMaps($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Make sure file exists
		try {
			$file = new File($filename, true);
			if (! $file->Exists())
				$file->Create('root', 'root', '0644');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->_AddListItem("recipient_bcc_maps", ",", "hash:$filename");
	}

	/**
	 * Add a file to use to match for sender BCC.
	 *
	 * @param  array  $filename  the filename to be used to match sender BCC address
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function AddSenderBccMaps($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# Make sure file exists
		try {
			$file = new File($filename, true);
			if (! $file->Exists())
				$file->Create('root', 'root', '0644');
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$this->_AddListItem("sender_bcc_maps", ",", "hash:$filename");
	}

	/**
	 * Deletes a destination domain.
	 *
	 * @param string $destination destination domain
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteDestination($destination)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		// Postfix uses some variables (like $mydomain)... so, we
		// allow the $ sign in domains for validation.

		$destination_validation = preg_replace("/\\$/", "", $destination);

		$network = new Network();

		if (! $network->IsValidDomain($destination_validation))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_DESTINATION_INVALID);

		// Delete relay host
		//------------------

		$this->_DeleteListItem('mydestination', ',', $destination);
	}

	/**
	 * Deletes a forwarder entry.
	 *
	 * @param string $domain domain name
	 * @return void
	 * @throws EngineException
	 */

	function DeleteForwarder($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$forwarders = array();

		$forwarders = $this->GetForwarders();

		// Check for existing record
		if (count($forwarders) == 0)
			return;

		$newforwarders = array();

		foreach ($forwarders as $forwardinfo) {
			if ($forwardinfo['domain'] == $domain)
				continue;

			$newforwarders[] = $forwardinfo;
		}

		$this->SetForwarders($newforwarders);

		$this->_DeleteListItem('relay_domains', ',', $domain);
	}

	/**
	 * Deletes queued messages for given list of IDs.
	 *
	 * @param array $messageids message IDs
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteQueuedMessages($messageids)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();

			foreach ($messageids as $id) {
				if (preg_match("/^[A-Z0-9]+$/", $id))
					$shell->Execute(self::CMD_POSTSUPER, "-d $id", true);
				else
					throw new ValidationException(POSTFIX_LANG_MAIL_ID . " - " . LOCALE_LANG_INVALID, COMMON_ERROR);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes a relay host.
	 *
	 * @param string $relayhost relay host
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteRelayHost($relayhost)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidHostWithPort($relayhost))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_RELAYHOST_INVALID);

		// Delete relay host
		//------------------

		$this->_DeleteListItem('relayhost', self::CONSTANT_SPACE, $relayhost);

		// Try delete with [] around host name
		$this->_DeleteListItem('relayhost', self::CONSTANT_SPACE, "[$relayhost]");
	}

	/**
	 * Deletes a trusted network.
	 *
	 * @param string $trustednetwork trusted network for relaying
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteTrustedNetwork($trustednetwork)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Prevent deletion of localhost
		//------------------------------

		if ($trustednetwork == self::LOCALHOST)
			throw new ValidationException(POSTFIX_LANG_ERRMSG_LOCALHOST);

		// Delete trusted network
		//-----------------------

		$this->_DeleteListItem('mynetworks', ',', $trustednetwork);
	}

	/**
	 * Deletes a virtual domain.
	 *
	 * @param string $virtualdomain virtual domain
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function DeleteVirtualDomain($virtualdomain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidVirtualDomain($virtualdomain))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_INVALID);

		try {
			$file = new File(self::FILE_VIRTUAL);
			$file->DeleteLines("/@" . $virtualdomain . "\s+/i");
			$file->DeleteLines("/^" . $virtualdomain . "\s+/i");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Run postmap
		$shell = new ShellExec();

		if ($shell->Execute(self::CMD_POSTMAP, self::FILE_VIRTUAL, true) != 0)
			throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
	}

	/**
	 * Deletes a recipient BCC mapping.
	 *
	 * @param  string  $filename  recipient bcc map file
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function DeleteRecipientBccMaps($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Delete recipient bcc
		//---------------------

		$this->_DeleteListItem('recipient_bcc_maps', ',', "hash:$filename");
	}


	/**
	 * Deletes a sender BCC mapping.
	 *
	 * @param  string  $filename  sender bcc map file
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function DeleteSenderBccMaps($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Delete sender bcc
		//------------------

		$this->_DeleteListItem('sender_bcc_maps', ',', "hash:$filename");
	}


	/**
	 * Flushes the mail queue.
	 *
	 * @return void
	 * @throws EngineException
	 */

	function FlushQueue()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$options['background'] = true;

			$shell = new ShellExec();
			$shell->Execute(self::CMD_POSTSUPER, "-r ALL", true);
			$shell->Execute(self::CMD_POSTFIX, "flush", true, $options);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Returns the always_bcc email.
	 *
	 * @return string the email to copy messages to
	 * @throws FileNoMatchException, EngineException
	 */

	function GetAlwaysBcc()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$retval = $file->LookupValue("/^always_bcc\s*=\s*/i");
		} catch (FileNoMatchException $e) {
			$retval = "";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $retval;
	}


	/**
	 * Returns catchall user (luser_relay).
	 *
	 * @return string catchall user
	 * @throws EngineException
	 */

	function GetCatchall()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['luser_relay'];
	}

	/**
	 * Returns catchall user for a virtual domain.
	 *
	 * @param string $virtualdomain virtual domain
	 * @return string catchall user
	 * @throws EngineException
	 */

	function GetCatchallVirtual($virtualdomain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidVirtualDomain($virtualdomain))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_INVALID);

		try {
			$file = new File(self::FILE_VIRTUAL);
			$retval = $file->LookupValue("/^@" . $virtualdomain . "\s+/i");
		} catch (FileNoMatchException $e) {
			return self::CONSTANT_BOUNCE;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $retval;
	}

	/**
	 * Returns the state of the list.
	 * 
	 * @param string $list list name
	 * @return boolean state of the list
	 * @throws EngineException
	 */

	function GetBlacklistState($list)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (!empty($this->config['smtpd_sender_restrictions']) &&
			preg_match("/$list/", $this->config['smtpd_sender_restrictions']))
			return true;
		else
			return false;
	}

	/**
	 * Returns destinations.
	 *
	 * With the scrub flag set, localhost and any domain that uses a variable
	 * (e.g. $myhostname, localhost.$mydomain) will be ignored.
	 *
	 * @param boolean $scrub scrub defaults
	 * @return array  array of destinations
	 * @throws  EngineException
	 */

	function GetDestinations($scrub = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$hosts = array();
		$hosts = $this->_GetListItems('mydestination', ',');

		if ($scrub) {
			$scrubbed_hosts = array();
			foreach ($hosts as $host) {
				if (!(preg_match('/\$/', $host) || ($host == "localhost")))
					$scrubbed_hosts[] = $host;
			}

			$hosts = $scrubbed_hosts;
		}

		return $hosts;
	}

	/**
	 * Returns domain.
	 *
	 * @return string  domain
	 * @throws  EngineException
	 */

	function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['mydomain'];
	}

	/**
	 * Returns hostname.
	 *
	 * @return string  hostname
	 * @throws  EngineException
	 */

	function GetHostname()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['myhostname'];
	}

	/**
	 * Returns list of mail forwarder.
	 *
	 * @return array  hashed list of forwarded domain
	 * @throws  EngineException
	 */

	function GetForwarders()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_TRANSPORT);
			$lines = $file->GetContentsAsArray();
		} catch (FileNotFoundException $e) {
			return array();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		$forwardlist = array();
		$forwardinfo = array();

		foreach ($lines as $line) {
			if (preg_match("/^[0-9a-zA-Z_\-\.]+\s+smtp:/i", $line)) {
				$forwardinfo["domain"] = preg_replace("/\s+smtp:.*/", "", $line);
				$target = preg_replace("/.*\s+smtp:/", "", $line);
				$forwardinfo["server"] = preg_replace("/:.*/", "", $target);
				$forwardinfo["port"] = preg_replace("/.*:/", "", $target);
				$forwardlist[] = $forwardinfo;
			}
		}

		return $forwardlist;
	}

	/**
	 * Returns local domains.
	 *
	 * Local domains include:
	 * - primary domain (mydomain)
	 * - destination domains (mydestination)
	 * - forwarder domains (/etc/postfix/transport)
	 * - virtual domains (/etc/postfix/virtual)
	 *
	 * @return array list of local domains
	 * @throws EngineException
	 */

	function GetLocalDomains()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$domains = array();

		try {
			// Primary domain
			$domains[] = $this->GetDomain();

			// Destination domains
			$domains = array_merge($this->GetDestinations(true), $domains);

			// Virtual domains
			$domains = array_merge($this->GetVirtualDomains(), $domains);

			// Forwarder domains
			$forwarders = $this->GetForwarders();

			foreach ($forwarders as $id => $details)
				$domains[] = $details['domain'];


		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $domains;
	}

	/**
	 * Returns local recipient maps.
	 *
	 * @return string local recipient maps
	 * @throws EngineException
	 */

	function GetLocalRecipientMaps()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['local_recipient_maps'];
	}

	/**
	 * Returns mail queue information.
	 *
	 * @return array mail queue information
	 * @throws  EngineException
	 */

	function GetMailQueue()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			if ($shell->Execute(self::CMD_MAILQ_FORMATTED, "", true) != 0)
				throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);

			$output = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $output;
	}

	/**
	 * Returns mailbox size.
	 *
	 * @return int  mailbox size in bytes
	 * @throws  EngineException
	 */

	function GetMaxMailboxSize()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['mailbox_size_limit'];
	}

	/**
	 * Returns message size.
	 *
	 * @return int mailbox size in bytes
	 * @throws EngineException
	 */

	function GetMaxMessageSize()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['message_size_limit'];
	}

	/**
	 * Gets the recipient_bcc_maps list.
	 *
	 * @return array a file list as an array
	 * @throws EngineException
	 */

	function GetRecipientBccMaps()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = Array();

		try {
			$bcc = $this->_GetListItems('recipient_bcc_maps', ',');
			
			$match = array();
			
			foreach ($bcc as $entry) {
				if (preg_match('/^hash:(.*)/', $entry, $match))
					$list[] = trim($match[1]);
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		
		return $list;
	}

	/**
	 * Gets the sender_bcc_maps list.
	 *
	 * @return array a file list as an array
	 * @throws EngineException
	 */

	function GetSenderBccMaps()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = Array();

		try {
			$bcc = $this->_GetListItems('sender_bcc_maps', ',');
			$scrubbed_bcc = array();
			$match = array();
			
			foreach ($bcc as $entry) {
				if (preg_match('/^hash:(.*)/', $entry, $match))
					$list[] = trim($match[1]);
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
		
		return $list;
	}

	/**
	 * Gets the recipient_bcc_maps.
	 *
	 * @param  string $filename the filename of the map file
	 * @return array contents of file as an array
	 * @throws EngineException
	 */

	function GetRecipientBccMapContents($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$contents = Array();
		
		try {
            $file = new File($filename);
			if ($file->Exists())
				$contents = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

        return $contents;
	}

	/**
	 * Gets the sender_bcc_maps.
	 *
	 * @return string the file that maps senders to BCC
	 * @throws FileNoMatchException, EngineException
	 */

	function GetSenderBccMapsContents($filename)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$contents = Array();
		
		try {
            $file = new File($filename);
			if ($file->Exists())
				$contents = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_ERROR);
        }

        return $contents;
	}

	/**
	 * Returns the mode for Postfix (gateway or server).
	 * self::CONSTANT_MODE_SERVER
	 * GATEWAY_CONSTANT_MODE_GATEWAY
	 *
	 * @return string  the mode
	 * @throws  EngineException
	 */

	function GetMode()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$mode = "";

		try {
			$file = new File(self::FILE_POLICY);
			$mode = $file->LookupValue("/^mode\s*=\s*/i");
		} catch (FileNotFoundException $e) {
			return self::CONSTANT_MODE_SERVER;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if (!$mode || ($mode == self::CONSTANT_MODE_SERVER)) {
			return self::CONSTANT_MODE_SERVER;
		} else if ($mode == self::CONSTANT_MODE_GATEWAY) {
			return self::CONSTANT_MODE_GATEWAY;
		} else {
			throw new EngineException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID, COMMON_ERROR);
		}
	}

	/** 
	 * Returns the state of a policy service eg a greylist.
	 *
	 * @param string $service policy service name
	 * @return boolean state of policy service
	 * @throws EngineException
	 */

	function GetPolicyService($service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return $this->_GetMailRestriction(
			"smtpd_recipient_restrictions", 
			"check_policy_service",
			$service
		);
	}

	/**
	 * Returns relay hosts.
	 *
	 * @return array  array of relay hosts
	 * @throws  EngineException
	 */

	function GetRelayHosts()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$hosts = array();

		$rawhosts = array();
		$rawhosts = $this->_GetListItems('relayhost', self::CONSTANT_SPACE);

		if ($rawhosts) {
			foreach ($rawhosts as $host) {
				$host = preg_replace("/[\[\]]/", "", $host);
				$hosts[] = $host;
			}
		}

		return $hosts;
	}

	/**
	 * Get SMTP authentication state.
	 *
	 * @return boolean  true if SMTP authentication is enabled
	 * @throws  EngineException
	 */

	function GetSmtpAuthenticationState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$state = $this->config['smtpd_sasl_auth_enable'];

		if (preg_match("/yes/i", $state))
			return true;
		else
			return false;
	}

	/**
	 * Returns specified user for a virtual domain.
	 *
	 * @param string $user username
	 * @param string $virtualdomain virutal domain
	 * @return string  user
	 * @throws  EngineException
	 */

	function GetUserVirtual($user, $virtualdomain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidVirtualDomain($virtualdomain))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_INVALID);

		try {
			$file = new File(self::FILE_VIRTUAL);
			$retval = $file->LookupValue("/^" . $user . "@" . $virtualdomain . "\s+/i");
		} catch (FileNoMatchException $e) {
			return "";
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		return $retval;
	}

	/**
	 * Returns trusted networks for relaying.
	 *
	 * @return array  array of trusted networks for relaying
	 * @throws  EngineException
	 */

	function GetTrustedNetworks()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->_GetListItems('mynetworks', ',');

		return $list;
	}

	/**
	 * Returns a list of configured virtual domains.
	 *
	 * @return array  list of configured virtual domains
	 * @throws  EngineException
	 */

	function GetVirtualDomains()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$domains = array();

		try {
			$file = new File(self::FILE_VIRTUAL);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		foreach ($lines as $line) {
			if ($line && !preg_match("/^[#\s]/", $line) && !preg_match("/@/", $line))
				$domains[] = rtrim(preg_replace("/ .*$/", "", $line));
		}

		return $domains;
	}

	/**
	 * Returns virtual maps settings.
	 *
	 * @return string virtual maps settings
	 * @throws EngineException
	 */

	function GetVirtualMaps()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['virtual_maps'];
	}

	/**
	 * Returns users who have access to virtual domain.
	 *
	 * @param string  $virtual  Virtual host definition 
	 * @return array  list of users who have access to virtual domain
	 * @throws  EngineException
	 */

	function GetVirtualUserList($virtual)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$virtualusers = array();

		try {
			$file = new File(self::FILE_VIRTUAL);
			$lines = $file->GetContentsAsArray();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		foreach ($lines as $line) {
			if (!preg_match("/^#/", $line) && !preg_match("/^$virtual/", $line) && preg_match("/$virtual/", $line)) {
				$user = explode(' ', $line);
				$virtualusers[] = rtrim($user[1]);
			}
		}

		return $virtualusers;
	}

	/**
	 * Sets the state of the list.
	 * 
	 * @param string $listname list name
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException
	 */

	function SetBlacklistState($listname, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		foreach ($this->blacklist_params as $parameter) {
			$filename = $this->blacklist_files[$listname];
			try {
				if ($state)	
					$this->_AddListItem($parameter, ",", "check_sender_access hash:$filename");
				else
					$this->_DeleteListItem($parameter, ",", "check_sender_access hash:$filename");
			} catch (DuplicateException $e) {
				// Not fatal
			}
		}

		$this->is_loaded = false;
	}

	/**
	 * Sets catchall user (luser_relay).
	 *
	 * @param string  $user  catch all user
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetCatchall($user)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidUser($user))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_USER_INVALID);

		// Set parameter
		//--------------

		if ($user == self::CONSTANT_BOUNCE)
			$user = "";

		$this->_SetParameter('luser_relay', $user);
	}

	/**
	 * Set the maximum mailbox size (in bytes).
	 *
	 * @param int  $size  mailbox size in bytes
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetMaxMailboxSize($size)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! preg_match("/^(\d)+$/", $size))
			throw new ValidationException(POSTFIX_LANG_MAILBOX_SIZE . " - " . LOCALE_LANG_INVALID);

		// Set parameter
		//--------------

		$this->_SetParameter('mailbox_size_limit', $size);
	}

	/**
	 * Set the maximum message size (in bytes).
	 * Note: you should set the antivirus and/or antispam message
	 * size limits if required.
	 *
	 * @param int  $size  message size in bytes
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetMaxMessageSize($size)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! preg_match("/^(\d)+$/", $size))
			throw new ValidationException(POSTFIX_LANG_MESSAGE_SIZE . " - " . LOCALE_LANG_INVALID);

		// Make sure mailbox_size_limit is bigger than message_size_limit
		//---------------------------------------------------------------

		$mailbox_size_limit = $this->GetMaxMailboxSize();

		if ($mailbox_size_limit < $size)
			$this->_SetParameter('mailbox_size_limit', $size);

		// Set parameter
		//--------------

		$this->_SetParameter('message_size_limit', $size);
	}

	/**
	 * Set the always_bcc parameter.
	 *
	 * @param  string  $email  set the always_bcc parameter
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function SetAlwaysBcc($email)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($email == null) {
			$file = new File(self::FILE_CONFIG);
			$file->DeleteLines("/^always_bcc\s+/i");
			return;
		}
			
		// Validate
		//---------

		if (! $this->IsValidEmail($email))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_EMAIL_INVALID);

		// Set parameter
		//--------------

		$this->_SetParameter('always_bcc', $email);
	}

	/**
	 * Set the recipient_bcc_maps parameter.
	 *
	 * @param  string $filename the filename of the map file
	 * @param  array $map  an array containing the mapping for mail accounts and the bcc recipient
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function SetRecipientBccMaps($filename, $map, $replace = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# On purpose, we don't add entry to the the recipient_bcc_maps directive...do this with 'AddRecipientBcc' function
		$contents = Array();
		$newcontents = Array();
		$data = "";
		# Make sure file exists
		try {
			$file = new File($filename, true);
			if ($file->Exists())
				$contents = $file->GetContentsAsArray();
			if ($file->Exists())
				$file->Delete();
			$file->Create('root', 'root', '0644');
			if ($replace)
				$newcontents = array_unique($map);
			else
				$newcontents = array_unique(array_merge($contents, $map));

			foreach ($newcontents as $line)
				$data .= "$line\n";

			$file->AddLines($data);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Run postmap
		$shell = new ShellExec();

		if ($shell->Execute(self::CMD_POSTMAP, $filename, true) != 0)
			throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
	}

	/**
	 * Set the sender_bcc_maps parameter.
	 *
	 * @param  string $filename the filename of the map file
	 * @param  array $map  an array containing the mapping for mail accounts and the bcc sender
	 * @return  void
	 * @throws  ValidationException, EngineException
	 */

	function SetSenderBccMaps($filename, $map, $replace = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		# On purpose, we don't add entry to the the sender_bcc_maps directive...do this with 'AddSenderBcc' function
		$contents = Array();
		$newcontents = Array();
		$data = "";
		# Make sure file exists
		try {
			$file = new File($filename, true);
			if ($file->Exists())
				$contents = $file->GetContentsAsArray();
			if ($file->Exists())
				$file->Delete();
			$file->Create('root', 'root', '0644');
			if ($replace)
				$newcontents = array_unique($map);
			else
				$newcontents = array_unique(array_merge($contents, $map));

			foreach ($newcontents as $line)
				$data .= "$line\n";

			$file->AddLines($data);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		# Run postmap
		$shell = new ShellExec();

		if ($shell->Execute(self::CMD_POSTMAP, $filename, true) != 0)
			throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
	}

	/**
	 * Sets (or changes) catchall user for a virtual domain.
	 *
	 * @param string  $virtualdomain  the virtual domain name
	 * @param string  $catchall  the catchall user
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetCatchallVirtual($virtualdomain, $catchall)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidVirtualDomain($virtualdomain))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_INVALID);

		if (! $this->IsValidUser($catchall))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_USER_INVALID);

		try {
			$file = new File(self::FILE_VIRTUAL);
			$file->DeleteLines("/^@" . $virtualdomain . "\s+/i");
			if (!empty($catchall) && $catchall != self::CONSTANT_BOUNCE)
				$file->AddLinesAfter("@$virtualdomain $catchall\n", "/$virtualdomain virtualdomain/i");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Run postmap
		$shell = new ShellExec();

		if ($shell->Execute(self::CMD_POSTMAP, self::FILE_VIRTUAL, true) != 0)
			throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
	}

	/** 
	 * Sets a policy service eg a greylist.
	 *
	 * @param string $service policy service name
	 * @param boolean $state state of policy service
	 * @return void
	 * @throws EngineException
	 */

	function SetPolicyService($service, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_SetMailRestriction(
			"smtpd_recipient_restrictions", 
			"check_policy_service",
			$service,
			$state
		);
	}

	/**
	 * Set the SMTP authentication state.
	 *
	 * @param boolean $state state of SMTP authentication
	 * @return void
	 * @throws EngineException
	 */

	function SetSmtpAuthenticationState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if ($state)
			$state = "yes";
		else
			$state = "no";

		// Set parameter
		//--------------

		$this->_SetParameter('smtpd_sasl_auth_enable', $state);
	}

	/**
	 * Sets user access to a virtual domain.
	 *
	 * @param string $virtualdomain virtual domain to add
	 * @param array $usernames array of users who have access to virtual domain
	 * @return void
	 * @throws EngineException
	 */

	function SetUserAccessVirtual($virtualdomain, $usernames)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		if (! $this->IsValidVirtualDomain($virtualdomain))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_INVALID);

		for ($i=0;$i < count($usernames);$i++) {
			if (! $this->IsValidUser($usernames[$i])) {
				throw new ValidationException(POSTFIX_LANG_ERRMSG_USER_INVALID);
			}
		}

		try {
			$file = new File(self::FILE_VIRTUAL);

			// Kills all existing users
			// Not pretty (due to bug #365 workaround)

			$lines = $file->GetContentsAsArray();

			foreach ($lines as $line) {
				// Parse: bob@domain.com bob
				$elements = explode(' ', $line);
				$subelements = explode("@", $elements[0]);

				$mailname = $subelements[0];
				$domain = isset($subelements[1]) ? $subelements[1] : '';
				$username = $elements[1];

				if (preg_match("/^$virtualdomain$/i", $domain) && ($mailname == $username))
					$file->DeleteLines("/^$mailname@$virtualdomain\s+$username$/i");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		// Add list of valid users
		for ($i=0;$i < count($usernames);$i++)
			$file->AddLinesAfter("$usernames[$i]@$virtualdomain $usernames[$i]\n", "/$virtualdomain virtualdomain/i");

		// Run postmap

		$shell = new ShellExec();

		if ($shell->Execute(self::CMD_POSTMAP, self::FILE_VIRTUAL, true) != 0)
			throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
	}

	/**
	 * Sets domain.
	 *
	 * @param string  $domain  domain
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		$network = new Network();

		if (! $network->IsValidDomain($domain))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_DOMAIN_INVALID);

		$this->_SetParameter('mydomain', $domain);	
	}

	/**
	 * Sets mail forwarder list.
	 *
	 * @access private
	 * @param array  $forwarders  hash array of forwardrs
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetForwarders($forwarders)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->_SetParameter('transport_maps', "hash:" . self::FILE_TRANSPORT);

		// Write transport file
		//---------------------

		try {
			$file = new File(self::FILE_TRANSPORT);

			if ($file->Exists())
				$file->Delete();

			$file->Create('root', 'root', '0644');

			$transportdata = "";

			foreach ($forwarders as $forwarder)
			$transportdata .= "$forwarder[domain] smtp:$forwarder[server]:$forwarder[port]\n";

			$file->AddLines($transportdata);

			// Run postmap
			$shell = new ShellExec();

			if ($shell->Execute(self::CMD_POSTMAP, self::FILE_TRANSPORT, true) != 0)
				throw new EngineException($shell->GetFirstOutputLine(), COMMON_ERROR);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets hostname.
	 *
	 * @param string  $hostname  hostname
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetHostname($hostname)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidDomain($hostname))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_HOSTNAME_INVALID);

		$this->_SetParameter('myhostname', $hostname);
	}

	/**
	 * Sets local recipient maps.
	 *
	 * This parameter can only be set to the default (DEFAULT_LOCAL_RECIPIENT_MAPS)
	 * or nothing at all.
	 *
	 * @param string $maps local recipient maps
	 * @return void
	 * @throws ValidationException, EngineException
	 */

	function SetLocalRecipientMaps($maps)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// TODO: Some users have added their own local_recipient_maps configuration
		// Specifically, boxes used as a antivirus/antispam gateway have a list
		// of valid user e-mail addresses configured on the gateway box.

		$current = $this->GetLocalRecipientMaps();
		if (! (empty($current) || $current == self::DEFAULT_LOCAL_RECIPIENT_MAPS) ) {
			$this->AddValidationError(LOCALE_LANG_ERRMSG_CONFIGURATION_FILE_HAS_BEEN_CUSTOMIZED . " - local_recipient_maps", __METHOD__, __LINE__);
			return;
		}

		if (! (empty($maps) || $maps == self::DEFAULT_LOCAL_RECIPIENT_MAPS) )
			throw new ValidationException('local_recipient_maps' . " - " . LOCALE_LANG_INVALID);

		$this->_SetParameter('local_recipient_maps', $maps);	
	}

	/**
	 * Sets virtual maps settings.
	 *
	 * @param string  $virtualmaps  virtual maps settings
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetVirtualMaps($virtualmaps)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidVirtualMaps($virtualmaps))
			throw new ValidationException(POSTFIX_LANG_ERRMSG_VIRTUALMAP_INVALID);

		$this->_SetParameter('virtual_maps', $virtualmaps);
	}

	/**
	 * Sets SMTP port to bind to/listen on.
	 *
	 * @param int  $port  port to bind to
	 * @return void
	 * @throws  ValidationException, EngineException
	 */

	function SetSmtpPort($port)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if ($port != "25" && $port != "2525" && $port != "smtp")
			throw new ValidationException(POSTFIX_LANG_ERRMSG_PORT_INVALID);

		try {
			$file = new File(self::FILE_CONFIG_MASTER);

			if ($port == "25")
				$port = "smtp";

			$match = $file->ReplaceOneLineByPattern("/^smtp /", "$port \$1");

			if (! $match)
				$match = $file->ReplaceOneLineByPattern("/^2525 (.*)/i", "$port \$1");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Get SMTP port
	 *
	 * @return int  SMTP port
	 * @throws  EngineException
	 */

	function GetSmtpPort()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$port = "0";
			$file = new File(self::FILE_CONFIG_MASTER);

			$lines = $file->GetContentsAsArray();

			foreach ($lines as $line) {
				if ($line && (preg_match("/^smtp/", $line) || preg_match("/^2525/", $line))) {
					$port = rtrim(preg_replace("/^([\w]+).*$/", "\$1", $line));
					break;
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

		if ($port == "smtp")
			$port = "25";

		return $port;
	}

	/*************************************************************************/
	/* V A L I D A T I O N   R O U T I N E S								 */
	/*************************************************************************/

	/**
	 * Validation routine for hostnames with optional port.
	 *
	 * The hostname and port must be separated by a colon.
	 *
	 * @param string $host  host with optional port
	 * @return boolean  true if valid
	 */

	function IsValidHostWithPort($host)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/:/", $host)) {
			$port = preg_replace("/.*:/", "", $host);
			$host = preg_replace("/:.*/", "", $host);

			if (! preg_match("/^\d+$/", $port))
				return false;

			if (($port > 65535) || ($port <= 0))
				return false;
		}

		$network = new Network();

		if ((! $network->IsValidIp($host)) && (! $network->IsValidDomain($host)))
			return false;
		else
			return true;
	}

	/**
	 * Validation routine for trusted networks.
	 *
	 * @param string  $trustednetwork  trusted network
	 * @return boolean  true if trusted network is valid
	 */

	function IsValidTrustedNetwork($trustednetwork)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if ($network->IsValidNetwork($trustednetwork) || $network->IsValidIp($trustednetwork))
			return true;
		else 
			return false;
	}

	/**
	 * Validation routine for virtual domain
	 *
	 * @param string $virtualdomain virtual domain
	 * @return boolean true if virtualdomain is valid
	 */

	function IsValidVirtualDomain($virtualdomain)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (empty($virtualdomain)) {
			return false;
		} else if (preg_match("/^([\w\.\/-]*)$/", $virtualdomain)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validation routine for user
	 *
	 * @param string $user username or email address
	 * @return boolean true if user is valid
	 */

	function IsValidUser($user)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/;/", $user))
			return false;
		else
			return true;
	}

	/**
	 * Validation routine for virtualmaps
	 *
	 * @param string $virtualmaps virtual maps settings
	 * @return boolean true if virtualmaps is valid
	 */

	function IsValidVirtualMaps($virtualmaps)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (preg_match("/;/", $virtualmaps))
			return false;
		else
			return true;
	}

	/**
	 * Validation routine for an email address
	 *
	 * @param string $email an email address
	 * @return boolean true if virtualmaps is valid
	 */
	function IsValidEmail($email)
    {
        if(!eregi("^.*@localhost$|^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$", $email)) {
			$this->AddValidationError(POSTFIX_LANG_ERRMSG_EMAIL_INVALID, __METHOD__, __LINE__);
            return false;
        }

        return true;
    }

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Adds an item to a list.
	 *
	 * @access private
	 * @param string $key parameter name in the configuration file
	 * @param string $delimiter delimiter used for the given key
	 * @param string $newitem item to add to list
	 * @return void
	 * @throws DuplicateException, EngineException
	 */

	function _AddListItem($key, $delimiter, $newitem)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Grab the current list (if any)
		//-------------------------------

		$list = array();
		$list = $this->_GetListItems($key, $delimiter);

		$thelist = "";

		foreach ($list as $item) {
			if ($item === $newitem)
				throw new DuplicateException(POSTFIX_LANG_ERRMSG_RECORD_EXISTS, COMMON_ERROR);

			if ($delimiter == self::CONSTANT_SPACE)
				$thelist .= $item . ' ';
			else
				$thelist .= $item . $delimiter . ' ';
		}

		$thelist .= $newitem;

		// Update tag if it exists
		//------------------------

		try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_POSTCONF, "-e '$key=$thelist'", true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

        $this->is_loaded = false;
	}

	/**
	 * Deletes an item from a given list.
	 *
	 * @access private
	 * @param string $key parameter name in the configuration file
	 * @param string $delimiter delimiter used for the given key
	 * @param string $olditem item to delete from list
	 * @return void
	 * @throws EngineException
	 */

	function _DeleteListItem($key, $delimiter, $olditem)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Grab the current list (if any)
		//-------------------------------

		$list = array();
		$list = $this->_GetListItems($key, $delimiter);

		$thelist = "";

		foreach ($list as $item) {
			if ($item == $olditem)
				continue;

			if ($delimiter == self::CONSTANT_SPACE)
				$thelist .= $item . " ";
			else
				$thelist .= $item . $delimiter . " ";
		}

		// Get rid of the last delimter added above
		$thelist = preg_replace("/$delimiter\s+$/", "", $thelist);

		try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_POSTCONF, "-e '$key=$thelist'", true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

        $this->is_loaded = false;
	}

	/**
	 * Returns a list of items for a given key.
	 *
	 * @access private
	 * @param string $key parameter name in the configuration file
	 * @param string $delimiter delimiter used for the given key
	 * @return array list of items for given key
	 * @throws EngineException
	 */

	function _GetListItems($key, $delimiter)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$rawlist = $this->config[$key];

		if (empty($rawlist))
			return array();

		$list = array();

		if ($delimiter == self::CONSTANT_SPACE)
			$list = split(' ', $rawlist);
		else
			$list = split($delimiter, $rawlist);

		$trimlist = array();
		
		foreach ($list as $item)
			$trimlist[] = trim($item);

		return $trimlist;
	}

	/**
	 * Returns the state of a sender/recipient restriction.
	 * 
	 * @param string $parameter configuration parameter (eg smtpd_recipient_restrictions)
	 * @param string $type type of restriction (eg check_sender_access)
	 * @param string $value value for given type (eg /etc/postfix/filters/sa-blacklist)
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException
	 */

	function _GetMailRestriction($parameter, $type, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$rightside = empty($value) ? $type : "$type $value";
		$rightside = preg_replace("/\//", '\/', $rightside);

		if (preg_match("/$rightside/", $this->config[$parameter]))
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

        try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_POSTCONF, '');
			$rawoutput = $shell->GetOutput();
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

		foreach ($rawoutput as $line) {
			$items = preg_split('/\s+/', $line, 3);
			$this->config[$items[0]] = isset($items[2]) ? $items[2] : '';
		}

        $this->is_loaded = true;
    }

	/**
	 * Sets the state of a sender/recipient restriction.
	 * 
	 * This method provides a generic way of adding policy
	 * - smtpd_recipient_restrictions = check_policy_service unix:/var/spool/postgrey...
	 * - smtpd_sender_restrictions = check_sender_access hash:/etc/postfix/filters/sa-blacklist
	 *
	 * @param string $parameter configuration parameter (eg smtpd_recipient_restrictions)
	 * @param string $type type of restriction (eg check_sender_access)
	 * @param string $value value for given type (eg /etc/postfix/filters/sa-blacklist)
	 * @param boolean $state state
	 * @return void
	 * @throws EngineException
	 */

	function _SetMailRestriction($parameter, $type, $value, $state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		$rightside = empty($value) ? $type : "$type $value";

		try {
			if ($state)	
				$this->_AddListItem($parameter, ",", $rightside);
			else
				$this->_DeleteListItem($parameter, ",", $rightside);
		} catch (DuplicateException $e) {
			// Not important
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Sets a value for a parameter.
	 *
	 * @access private
	 * @param string $key key name
	 * @param string $value value for the key
	 * @return void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$shell = new ShellExec();
			$shell->Execute(self::CMD_POSTCONF, "-e '$key=$value'", true);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}

        $this->is_loaded = false;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
