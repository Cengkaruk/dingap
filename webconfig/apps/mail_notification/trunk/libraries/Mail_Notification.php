<?php

/**
 * Mail notification class.
 *
 * @category   Apps
 * @package    Mail_Notification
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

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Hostname as Hostname;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
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
 * Mail notification class.
 *
 * @category   Apps
 * @package    Mail_Notification
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_notification/
 */

class Mail_Notification extends Engine
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
        set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/clearos/webconfig/var/lib/php');
        include_once 'Swift/lib/Swift.php';
        include_once 'Swift/lib/Swift/File.php';
        include_once 'Swift/lib/Swift/Connection/SMTP.php';
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

        // Create a recipient list
        $recipient_list = new \Swift_RecipientList();

        // Validation
        // ----------
        
        if ($this->message['recipient'] == NULL || empty($this->message['recipient'])) {
            throw new Validation_Exception(lang('mail_notification_recipient_not_set'));
        } else {
            foreach ($this->message['recipient'] as $address) {
                if ($this->validate_email($address['address']))
                    throw new Validation_Exception(lang('mail_notification_recipient') . ' - ' . lang('base_invalid') . ' (' . $address['address'] . ')');
            }
        }

        // Sender
        if ($this->get_sender() != NULL && $this->get_sender() != "") {
            $address = $this->_parse_email_address($this->get_sender());
            $this->message['sender']['address'] = $address['address'];
            $this->message['sender']['name'] = $address['name'];
        } else {
            // Fill in default
            $hostname = new Hostname();
            $this->message['sender']['address'] = "root@" . $hostname->get();
        }

        // ReplyTo
        if (!isset($this->message['replyto']) || $this->message['replyto'] == NULL || empty($this->message['replyto'])) {
            // Set to Sender
            $this->message['replyto'] = $this->message['sender']['address'];
        }

        try {
            $smtp = new \Swift_Connection_SMTP(
                $this->config['host'], intval($this->config['port']), intval($this->config['ssl'])
            );
            if ($this->config['username'] != NULL && !empty($this->config['username'])) {
                $smtp->setUsername($this->config['username']);
                $smtp->setPassword($this->config['password']);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        try {
            $swift = new \Swift($smtp);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_WARNING);
        }

        // Set Subject
        $message = new \Swift_Message($this->message['subject']);

        // Set Body
        if (isset($this->message['body']))
            $message->setBody($this->message['body']);

        if (isset($this->message['parts'])) {
            foreach ($this->message['parts'] as $msgpart) {
                if (isset($msgpart['filename'])) {
                    if (isset($msgpart['data'])) {
                        // Data in variable
                        $part = new \Swift_Message_Attachment(
                            $msgpart['data'], basename($msgpart['filename']), $msgpart['type'],
                            $msgpart['encoding'], $msgpart['disposition']
                        );
                    } else {
                        // Data as file
                        try {
                            $file = new \Swift_File($msgpart['filename']);
                        } catch (\Swift_FileException $e) {
                            throw new Engine_Exception(lang('base_error') . ' - ' . basename($msgpart['filename']));
                        }
                        $part = new \Swift_Message_Attachment(
                            $file, basename($msgpart['filename']), $msgpart['type'],
                            $msgpart['encoding'], $msgpart['disposition']
                        );
                    }
                } else if (isset($msgpart['disposition']) && strtolower($msgpart['disposition']) == 'inline') {
                    $part = new \Swift_Message_Attachment(
                        $msgpart['data'], NULL, $msgpart['type'], $msgpart['encoding'], $msgpart['disposition']
                    );
                } else {
                    $part = new \Swift_Message_Part(
                        $msgpart['data'], $msgpart['type'], $msgpart['encoding'], $msgpart['charset']
                    );
                }
                if (isset($msgpart['Content-ID']))
                    $part->headers->set("Content-ID", $msgpart['Content-ID']);
                $message->attach($part);
            }
        }

        // Override date
        if (isset($this->message['date']))
            $message->SetDate($this->message['date']);

        // Set Custom headers
        // Set a default 'clear-archive-ignore' flag so messages sent from Mailer do not get archived
        if (isset($this->message['headers'])) {
            $ignore_set = FALSE;
            while ($header = current($this->message['headers'])) {
                if (key($header) == 'clear-archive-ignore')
                    $ignore_set = TRUE;
                $message->headers->Set(key($header), $header[key($header)]);
                next($this->message['headers']);
            }
            if ($ignore_set)
                $message->headers->Set('clear-archive-ignore', 'true');
        } else {
            $message->headers->Set('clear-archive-ignore', 'true');
        }

        // Set To
        foreach ($this->message['recipient'] as $recipient) {
            $addy = new \Swift_Address($recipient['address']);
            if (isset($recipient['name']))
                $addy->setName($recipient['name']);
            $recipient_list->addTo($addy);
        }
        // Set CC 
        if (isset($this->message['cc'])) {
            foreach ($this->message['cc'] as $cc) {
                $addy = new \Swift_Address($cc['address']);
                if (isset($cc['name']))
                    $addy->setName($cc['name']);
                $recipient_list->addCc($addy);
            }
        }
        // Set BCC 
        if (isset($this->message['bcc'])) {
            foreach ($this->message['bcc'] as $bcc) {
                $addy = new \Swift_Address($bcc['address']);
                if (isset($bcc['name']))
                    $addy->setName($bcc['name']);
                $recipient_list->addBCc($addy);
            }
        }
        // Set sender
        $sender = new \Swift_Address($this->message['sender']['address']);
        if (isset($this->message['sender']['name']))
            $sender->setName($this->message['sender']['name']);

        // Set reply to
        $message->setReplyTo($this->message['replyto']);

        if ($swift->send($message, $recipient_list, $sender)) {
            $swift->disconnect();
            $this->clear();
        } else {
            $swift->disconnect();
            $this->clear();
            throw new Engine_Exception(lang('mail_notification_send_failed'), CLEAROS_WARNING);
        }
    }

    /* Executes a test to see if mail can be sent through the SMTP server.
     *
     * @param string @email a valid email to send test to
     * @return bool
     * @throws ValidationException, EngineException
     */

    function test_relay($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->add_recipient($email);
        $this->set_subject(lang('mail_notification_test'));
        $this->set_body(lang('mail_notification_test_success'));
        $this->send();
    }

    /**
     * Clears data structures.
     *
     * @return void
     */

    function clear()
    {
        clearos_profile(__METHOD__, __LINE__);

        unset($this->message);
    }

    /**
     * Parse an email address.
     *
     * @param mixed $raw email address (as a string or array of parts)
     *
     * @access private
     * @return array
     * @throws EngineException
     */

    function _parse_email_address($raw)
    {
        clearos_profile(__METHOD__, __LINE__);

        $address = array();

        if (! is_array($raw))
            $address[0] = $raw;
        else
            $address = $raw;

        $match = NULL;

        // Format Some Guy <someguy@domain.com>
        if (preg_match("/^(.*) +<(.*)>$/", $address[0], $match)) {
            $address[0] = $match[2];
            $address[1] = $match[1];
        }

        // Format <someguy@domain.com> Some Guy
        if (preg_match("/^<(.*)> +(.*)$/", $address[0], $match)) {
            $address[0] = $match[2];
            $address[1] = $match[1];
        }

        // Format someguy@domain.com Some Guy
        if (preg_match("/^([a-z0-9\._-\+]+@+[a-z0-9\._-]+\.+[a-z]{2,4}) +(.*)$/iu", $address[0], $match)) {
            $address[0] = $match[1];
            $address[1] = $match[2];
        }

        // Format Some Guy someguy@domain.com
        if (preg_match("/^(.*) +([a-z0-9\._-\+]+@+[a-z0-9\._-]+\.+[a-z]{2,4})$/iu", $address[0], $match)) {
            $address[0] = $match[2];
            $address[1] = $match[1];
        }

        // Remove any <>
        $address[0] = ereg_replace("\<|\>", "", $address[0]);
        if (isset($address[1]))
            $address[1] = ereg_replace("\<|\>", "", $address[1]);

        // Check if array is reversed
        if (isset($address[1]) && isset($address[0]) 
            && $this->validate_email($address[1]) == NULL 
            && $this->validate_email($address[0])
        ) {
            $temp = $address;
            $address[0] = $temp[1];
            $address[1] = $temp[0];
        }

        $email = array('address' => $address[0], 'name' => isset($address[1]) ? $address[1] : NULL);
        return $email;
    }

    /**
     * Adds an email to the send-to (recipient) address field.
     *
     * @param mixed $recipient a string or array (address, name) representing a recipient's email address
     *
     * @return void
     * @throws Validation_Exception
     */

    function add_recipient($recipient)
    {
        clearos_profile(__METHOD__, __LINE__);

        $address = $this->_parse_email_address($recipient);

        // Validation
        // ----------
        Validation_Exception::is_valid($this->validate_email($address['address']));
        
        $this->message['recipient'][] = $address;
    }

    /**
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

    /**
     * Returns the SSL type options for the SMTP server.
     *
     * @return array
     */

    function get_ssl_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            \Swift_Connection_SMTP::ENC_OFF=>lang('mail_notification_none'),
            \Swift_Connection_SMTP::ENC_SSL=>lang('mail_notification_ssl'),
            \Swift_Connection_SMTP::ENC_TLS=>lang('mail_notification_tls')
        );
        return $options;
    }

    /**
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

    /**
     * Returns SMTP port.
     *
     * @return int port
     * @throws Engine_Exception
     */

    function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['port'];
    }

    /**
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

    /**
     * Returns SMTP username.
     *
     * @return string username
     * @throws Engine_Exception
     */

    function get_username()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['username'];
    }

    /**
     * Returns SMTP password.
     *
     * @return string password
     * @throws Engine_Exception
     */

    function get_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['password'];
    }

    /**
     * Sets the sender email address field.
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

        Validation_Exception::is_valid($this->validate_email($address['address']));
        
        $this->_set_parameter('sender', $sender);
    }

    /**
     * Sets the reply to email address field.
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

        Validation_Exception::is_valid($this->validate_email($address['address']));
        
        $this->_set_parameter('replyto', $replyto);
    }

    /**
     * Sets the subject field.
     *
     * @param string $subject the email subject
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_subject($subject)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_subject($subject));
        
        $this->message['subject'] = $subject;
    }

    /**
     * Sets the SMTP host.
     *
     * @param string $host SMTP host
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_host($host)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_host($host));
        
        $this->_set_parameter('host', $host);
    }

    /**
     * Sets the SMTP port.
     *
     * @param int $port SMTP port
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_port($port));
        
        $this->_set_parameter('port', $port);
    }

    /**
     * Sets the SMTP use of SSL.
     *
     * @param boolean $ssl use SSL flag
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_ssl($ssl)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ssl($ssl));
        
        $this->_set_parameter('ssl', $ssl);
    }

    /**
     * Sets the SMTP username.
     *
     * @param string $username SMTP username
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_username($username));
        
        $this->_set_parameter('username', $username);
    }

    /**
     * Sets the SMTP password.
     *
     * @param string $password SMTP password
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_password($password));
        
        $this->_set_parameter('password', $password);
    }

    /**
     * Sets the message body.
     *
     * @param string $body the message body
     *
     * @return void
     * @throws ValidationEException
     */

    function set_body($body)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_body($body));

        $this->message['body'] = $body;
    }

    /**
     * Sets the message HTML body.
     *
     * @param string $html the message HTML body
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_html_body($html)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_html_body($html));

        $html = array($html, "text/html");
        $this->message['parts'][] = $html;
    }

    /**
     * Sets a message part.
     *
     * @param array $part the message part
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_part($part)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->message['parts'][] = $part;
    }

    /**
     * Sets the message attachments to be sent.
     *
     * @param array $attachments associative array containing the message attachments to be included
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_attachments($attachments)
    {
        clearos_profile(__METHOD__, __LINE__);

        foreach ($attachments as $attachment) {
            Validation_Exception::is_valid($this->validate_attachment($attachment));
            if (!isset($attachment['type']))
                $attachment['type'] = 'application/octet-stream';
            if (!isset($attachment['encoding']))
                $attachment['encoding'] = 'base64';
            if (!isset($attachment['disposition']))
                $attachment['disposition'] = 'attachment';

            $this->message['parts'][] = $attachment;
        }
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
     * @return mixed void if email is valid, errmsg otherwise
     */

    public function validate_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i", $email))
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

        if ($hostname->validate_hostname($host))
            return lang('mail_notification_host_is_invalid');
    }

    /**
     * Validation routine for SMTP SSL.
     *
     * @param string $ssl SMTP ssl
     *
     * @return mixed void if SMTP ssl is valid, errmsg otherwise
     */

    public function validate_ssl($ssl)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_bool($ssl))
            return lang('mail_notification_ssl_is_invalid');
    }

    /**
     * Validation routine for SMTP username.
     *
     * @param string $username SMTP username
     *
     * @return mixed void if SMTP username is valid, errmsg otherwise
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[A-Z0-9._%+-@]*$/i", $username))
            return lang('mail_notification_username_is_invalid');
    }

    /**
     * Validation routine for SMTP password.
     *
     * @param string $password SMTP password
     *
     * @return mixed void if SMTP password is valid, errmsg otherwise
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[A-Z0-9._%+-@$\\?\\(\\)]*$/i", $password))
            return lang('mail_notification_password_is_invalid');
    }

    /**
     * Validation routine for message body.
     *
     * @param string $body message body
     *
     * @return mixed void if body is valid, errmsg otherwise
     */

    public function validate_body($body)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for message HTML body.
     *
     * @param string $html message HTML body
     *
     * @return mixed void if HTML body is valid, errmsg otherwise
     */

    public function validate_html_body($html)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
    }

    /**
     * Validation routine for attachments to be sent with e-mail.
     *
     * @param array $attachment - array["/tmp/temp.exe", "temp.exe", "application/octet-stream"]
     *
     * @return mixed void if attachment is valid, errmsg otherwise
     */

    function validate_attachment($attachment)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_array($attachment))
            return lang('mail_notification_attachment_invalid');

        // If data parameter is set, its OK
        if (isset($attachment['data']))
            return;
    
        $file = new File($attachment['filename'], TRUE);

        if (!$file->exists())
            return lang('mail_notification_attachment_file_not_found') . ' - ' . $attachment['filename'];
    }
}
