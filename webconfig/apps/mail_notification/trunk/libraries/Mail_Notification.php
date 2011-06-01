<?php

/**
 * Raid class.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_notification/
 */

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

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\mail_notification;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('mail_notification');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\File as File;
use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\network\Hostname as Hostname;

clearos_load_library('base/File');
clearos_load_library('base/Configuration_File');
clearos_load_library('network/Hostname');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail Notification class.
 *
 * @category   Apps
 * @package    Mail_Notification
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_notification/
 */

class Mail_Notification
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
	protected $config = NULL;
	protected $message = NULL;

	const FILE_CONFIG = '/etc/mailer.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Mail_Notification constructor.
     */

    function __construct()
    {
    }

	/** Send a plain text message.
	 *
	 * @return void
	 *
     * @throws Validation_Exception, Engine_Exception
	 */

	function send()
	{
        clearos_profile(__METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_load_config();

		# Create a recipient list
		$recipient_list = new Swift_RecipientList();

		# Swift mailer logs a warning message if we don't set this
		$ntptime = new Ntp_Time();
		date_default_timezone_set($ntptime->get_time_zone());

		# Validation
		# ----------
		
		if ($this->message['recipient'] == null || empty($this->message['recipient'])) {
			throw new Validation_Exception(lang('mail_notification_recipient_not_set'));
		} else {
			foreach ($this->message['recipient'] as $address) {
				if ($this->validate_email($address['address']))
					throw new Validation_Exception(lang('mail_notification_recipient') . ' - ' . lang('base_invalid') . ' (' . $address['address'] . ')');
			}
		}

		# Sender
		if ($this->get_sender() != null && $this->get_sender() != "") {
			$address = $this->_parse_email_address($this->get_sender());
			$this->message['sender']['address'] = $address['address'];
			$this->message['sender']['name'] = $address['name'];
		} else {
			// Fill in default
			$hostname = new Hostname();
			$this->message['sender']['address'] = "root@" . $hostname->get();
		}

		# ReplyTo
		if (!isset($this->message['replyto']) || $this->message['replyto'] == null || empty($this->message['replyto'])) {
			// Set to Sender
			$this->message['replyto'] = $this->message['sender']['address'];
		}

		try {
			$smtp = new Swift_Connection_SMTP(
				$this->config['host'], intval($this->config['port']), intval($this->config['ssl'])
			);
			if ($this->config['username'] != null && !empty($this->config['username'])) {
				$smtp->setUsername($this->config['username']);
				$smtp->setPassword($this->config['password']);
			}
		} catch (Exception $e) {
			throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
		}

		try {
			$swift = new Swift($smtp);
		} catch (Exception $e) {
			throw new Engine_Exception($e->GetMessage(), COMMON_INFO);
		}

		# Set Subject
		$message = new Swift_Message($this->message['subject']);

		# Set Body
		if (isset($this->message['body']))
			$message->setBody($this->message['body']);

		if (isset($this->message['parts'])) {
			foreach ($this->message['parts'] as $msgpart) {
				if (isset($msgpart['filename'])) {
					if (isset($msgpart['data'])) {
						# Data in variable
						$part = new Swift_Message_Attachment(
							$msgpart['data'], basename($msgpart['filename']), $msgpart['type'],
							$msgpart['encoding'], $msgpart['disposition']
						);
					} else {
						# Data as file
						try {
							$file = new Swift_File($msgpart['filename']);
						} catch (Swift_FileException $e) {
							throw new FileNotFoundException(FILE_LANG_ERRMSG_NOTEXIST . basename($msgpart['filename']));
						}
						$part = new Swift_Message_Attachment(
							$file, basename($msgpart['filename']), $msgpart['type'],
							$msgpart['encoding'], $msgpart['disposition']
						);
					}
				} else if (isset($msgpart['disposition']) && strtolower($msgpart['disposition']) == 'inline') {
					$part = new Swift_Message_Attachment(
						$msgpart['data'], null, $msgpart['type'], $msgpart['encoding'], $msgpart['disposition']
					);
				} else {
					$part = new Swift_Message_Part(
						$msgpart['data'], $msgpart['type'], $msgpart['encoding'], $msgpart['charset']
					);
				}
				if (isset($msgpart['Content-ID']))
					$part->headers->set("Content-ID", $msgpart['Content-ID']);
				$message->attach($part);
			}
		}

		# Override date
		if (isset($this->message['date']))
			$message->SetDate($this->message['date']);

		# Set Custom headers
		# Set a default 'clear-archive-ignore' flag so messages sent from Mailer do not get archived
		if (isset($this->message['headers'])) {
			$ignore_set = false;
			while ($header = current($this->message['headers'])) {
				if (key($header) == 'clear-archive-ignore')
					$ignore_set = true;
				$message->headers->Set(key($header), $header[key($header)]);
				next($this->message['headers']);
			}
			if ($ignore_set)
				$message->headers->Set('clear-archive-ignore', 'true');
		} else {
			$message->headers->Set('clear-archive-ignore', 'true');
		}

		# Set To
		foreach ($this->message['recipient'] as $recipient) {
			$addy = new Swift_Address($recipient['address']);
			if (isset($recipient['name']))
				$addy->setName($recipient['name']);
            $recipient_list->addTo($addy);
		}
		# Set CC 
		if (isset($this->message['cc'])) {
			foreach ($this->message['cc'] as $cc) {
				$addy = new Swift_Address($cc['address']);
				if (isset($cc['name']))
					$addy->setName($cc['name']);
            	$recipient_list->addCc($addy);
			}
		}
		# Set BCC 
		if (isset($this->message['bcc'])) {
			foreach ($this->message['bcc'] as $bcc) {
				$addy = new Swift_Address($bcc['address']);
				if (isset($bcc['name']))
					$addy->setName($bcc['name']);
				$recipient_list->addBCc($addy);
			}
		}
		# Set sender
		$sender = new Swift_Address($this->message['sender']['address']);
		if (isset($this->message['sender']['name']))
			$sender->setName($this->message['sender']['name']);

		# Set reply to
		$message->setReplyTo($this->message['replyto']);

		if ($swift->send($message, $recipient_list, $sender)) {
			$this->_LogSendSuccess();
			$swift->disconnect();
			$this->clear();
		} else {
			$swift->disconnect();
			$this->clear();
			throw new Engine_Exception(MAILER_LANG_ERRMSG_SEND_FAILED, COMMON_WARNING);
		}
	}

	/**
     * Parse an email address.
     *
     * @param mixed $raw  the email address (as a string or array of parts)
     *
     * @access private
     * @return array
     * @throws EngineException
     */

	function _parse_email_address($raw)
	{
        clearos_profile(__METHOD__, __LINE__);

		$address = Array();
		if (! is_array($raw))
			$address[0] = $raw;
		else
			$address = $raw;

		$match = null;

		# Format Some Guy <someguy@domain.com>
        if (preg_match("/^(.*) +<(.*)>$/", $address[0], $match)) {
			$address[0] = $match[2];
			$address[1] = $match[1];
		}

		# Format <someguy@domain.com> Some Guy
        if (preg_match("/^<(.*)> +(.*)$/", $address[0], $match)) {
			$address[0] = $match[2];
			$address[1] = $match[1];
		}

		# Format someguy@domain.com Some Guy
        if (preg_match("/^([a-z0-9\._-\+]+@+[a-z0-9\._-]+\.+[a-z]{2,4}) +(.*)$/", $address[0], $match)) {
			$address[0] = $match[1];
			$address[1] = $match[2];
		}

		# Format Some Guy someguy@domain.com
        if (preg_match("/^(.*) +([a-z0-9\._-\+]+@+[a-z0-9\._-]+\.+[a-z]{2,4})$/", $address[0], $match)) {
			$address[0] = $match[2];
			$address[1] = $match[1];
		}

		# Remove any <>
		$address[0] = ereg_replace("\<|\>", "", $address[0]);
		if (isset($address[1]))
			$address[1] = ereg_replace("\<|\>", "", $address[1]);

		# Check if array is reversed
		if (isset($address[1]) && isset($address[0]) &&
			$this->validate_email($address[1]) == NULL && $this->validate_email($address[0])
		) {
			$temp = $address;
			$address[0] = $temp[1];
			$address[1] = $temp[0];
		}

		$email = Array('address' => $address[0], 'name' => isset($address[1]) ? $address[1] : null);
		return $email;
	}

	/*
     * Returns sender address.
     *
     * @return string sender address
     * @throws Engine_Exception
     */

    function get_sender()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['sender'];
    }

	/*
     * Returns SMTP host.
     *
     * @return string host
     * @throws Engine_Exception
     */

    function get_host()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['host'];
    }

	/*
     * Returns SMTP port.
     *
     * @return string port
     * @throws Engine_Exception
     */

    function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['port'];
    }

	/*
     * Returns SMTP SSL flag.
     *
     * @return boolean ssl 
     * @throws Engine_Exception
     */

    function get_ssl()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['ssl'];
    }

    /*
     * Set the sender notification email.
     *
     * @param string $email a valid email
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        // Validation
        // ----------
        Validation_Exception::is_valid($this->validate_email($email));

        $this->_set_parameter('email', $email);
    }

	/* Set the sender email address field.
	 *
     * @param mixed $sender a string or array (address, name) representing the sender's email address
	 *
	 * @return void
     * @throws Validation_Exception
	 */

	function set_sender($sender)
	{
        clearos_profile(__METHOD__, __LINE__);

		$address = $this->_parse_email_address($sender);

		// Validation
		// ----------
        Validation_Exception::is_valid($this->validate_email($address));
		
		$this->_set_parameter('sender', $sender);
	}

	/* Set the reply to email address field.
	 *
     * @param mixed $replyto a string or array (address, name) representing the replyto email address
	 *
	 * @return void
     * @throws Validation_Exception
	 */

	function set_replyto($replyto)
	{
        clearos_profile(__METHOD__, __LINE__);

		$address = $this->_parse_email_address($replyto);

		// Validation
		// ----------
        Validation_Exception::is_valid($this->validate_email($address));
		
		$this->_set_parameter('replyto', $replyto);
	}

	/* Set the subject field.
	 *
     * @param string $subject the email subject
	 *
	 * @return void
     * @throws Validation_Exception
	 */

	function set_subject($subject)
	{
        clearos_profile(__METHOD__, __LINE__);

		// Validation
		// ----------
        Validation_Exception::is_valid($this->validate_subject($subject));
		
		$this->_set_parameter('subject', $subject);
	}

	/* Set the SMTP host.
	 *
     * @param string $host SMTP host
	 *
	 * @return void
     * @throws Validation_Exception
	 */

	function set_host($host)
	{
        clearos_profile(__METHOD__, __LINE__);

		// Validation
		// ----------
        Validation_Exception::is_valid($this->validate_host($host));
		
		$this->_set_parameter('host', $host);
	}

	/* Set the SMTP port.
	 *
     * @param int $port SMTP port
	 *
	 * @return void
     * @throws Validation_Exception
	 */

	function set_port($port)
	{
        clearos_profile(__METHOD__, __LINE__);

		// Validation
		// ----------
        Validation_Exception::is_valid($this->validate_port($port));
		
		$this->_set_parameter('port', $port);
	}

	/* Set the SMTP use of SSL.
	 *
     * @param boolean  $ssl use SSL flag
	 *
	 * @return void
     * @throws Validation_Exception
	 */

	function set_ssl($ssl)
	{
        clearos_profile(__METHOD__, __LINE__);

		// Validation
		// ----------
        Validation_Exception::is_valid($this->validate_ssl($ssl));
		
		$this->_set_parameter('ssl', $ssl);
	}

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
    * Loads configuration files.
    *
    * @return void
    * @throws Engine_Exception
    */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);

        try {
            $this->config = $configfile->Load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $match = $file->replace_lines("/^$key\s*=\s*/", "$key=$value\n");

            if (!$match)
                $file->add_lines("$key=$value\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for email.
     *
     * @param string $email email
     *
     * @return string void if email is valid, errmsg otherwise
     */

    public function validate_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^[a-z0-9\._-\+]+@+[a-z0-9\._-]+\.+[a-z]{2,5}$/", $email))
            return lang('mail_notification_email_is_invalid');
    }

    /**
     * Validation routine for subject.
     *
     * @param string $subject subject
     *
     * @return mixed void if subject is valid, errmsg otherwise
     */

    public function validate_subject($subject)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/.*\n.*/", $subject))
            return lang('mail_notification_subject_is_invalid');
    }

    /**
     * Validation routine for SMTP port.
     *
     * @param int $port SMTP port
     *
     * @return mixed void if SMTP port is valid, errmsg otherwise
     */

    public function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($port < 1 || $port > 65535)
            return lang('mail_notification_port_is_invalid');
    }

    /**
     * Validation routine for SMTP host.
     *
     * @param string $host SMTP host
     *
     * @return mixed void if SMTP host is valid, errmsg otherwise
     */

    public function validate_host($host)
    {
        clearos_profile(__METHOD__, __LINE__);

        $hostname = new Hostname();

        try {
            Validation_Exception::is_valid($hostname->validate_hostname($host));
        } catch (Validation_Exception $e) {
            return lang('mail_notification_host_is_invalid');
        }
    }

    /**
     * Validation routine for SMTP SSL.
     *
     * @param string $ssl SMTP ssl
     *
     * @return string void if SMTP ssl is valid, errmsg otherwise
     */

    public function validate_ssl($ssl)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_bool($ssl))
            return lang('mail_notification_ssl_is_invalid');
    }
}
