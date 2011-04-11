<?php

/**
 * Postfix class
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
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

namespace clearos\apps\smtp;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('smtp');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Postfix class
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
 */

class Postfix extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/postfix/main.cf';
    const FILE_CONFIG_MASTER = '/etc/postfix/master.cf';
    const FILE_TRANSPORT = '/etc/postfix/transport';
    const FILE_SENDER_BCC = '/etc/postfix/sender_bcc_maps';
    const FILE_RECIPIENT_BCC = '/etc/postfix/recipient_bcc_maps';
    const COMMAND_POSTCONF = '/usr/sbin/postconf';
    const COMMAND_POSTFIX = '/usr/sbin/postfix';
    const COMMAND_POSTMAP = '/usr/sbin/postmap';
    const COMMAND_POSTSUPER = '/usr/sbin/postsuper';
    const COMMAND_MAILQ_FORMATTED = '/usr/sbin/mailqfmt.pl';
    const LOCALHOST = '127.0.0.0/8';
    const CONSTANT_SPACE = 1;
    const CONSTANT_MODE_SERVER = 'server';
    const CONSTANT_MODE_GATEWAY = 'gateway';
    const DEFAULT_MAX_MAILBOX_SIZE = 51200000;
    const DEFAULT_MAX_MESSAGE_SIZE = 10240000;
    const DEFAULT_SMTP_AUTHENTICATION = TRUE;
    const DEFAULT_LOCAL_RECIPIENT_MAPS = 'proxy:unix:passwd.byname $alias_maps';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Postfix constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('postfix');
    }

    ///////////////////////////////////////////////////////////////////////////
    // M A I N  M E T H O D S                                                //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Adds a destination domain.
     *
     * @param string $domain destination domain
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_destination($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_destination_domain($domain));

        $this->_add_list_item('mydestination', ',', $domain);
    }

    /**
     * Adds a mail forwarder.
     *
     * @param string  $domain domain name
     * @param string  $server target mail server
     * @param integer $port   target mail port
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_forwarder($domain, $server, $port = 25)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_forwarder_domain($domain));
        Validation_Exception::is_valid($this->validate_server($server));
        Validation_Exception::is_valid($this->validate_port($port));

        $info['domain'] = $domain;
        $info['server'] = $server;
        $info['port'] = $port;

        $forwarders[] = $info;

        $this->_set_forwarders($forwarders);
        $this->_add_list_item('relay_domains', ',', $domain);
    }

    /**
     * Adds a trusted network.
     *
     * @param string $network trusted network for relaying
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_trusted_network($network)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: network with a prefix is not working 
        Validation_Exception::is_valid($this->validate_trusted_network($network));

        $this->_add_list_item('mynetworks', ',', $network);
    }

    /**
     * Add a file to use to match for recipient BCC.
     *
     * @param array $filename filename to be used to match recipient BCC address
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_recipient_bcc_maps($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: validate
        $file = new File($filename, TRUE);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $this->_add_list_item('recipient_bcc_maps', ',', 'hash:' . $filename);
    }
    /**
     * Add a file to use to match for sender BCC.
     *
     * @param  array  $filename  the filename to be used to match sender BCC address
     *
     * @return  void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function add_sender_bcc_maps($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: validate
        $file = new File($filename, TRUE);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $this->_add_list_item('sender_bcc_maps', ',', 'hash:' . $filename);
    }

    /**
     * Deletes a destination domain.
     *
     * @param string $domain destination domain
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_destination($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_destination_domain($domain));

        $this->_delete_list_item('mydestination', ',', $destination);
    }

    /**
     * Deletes a forwarder entry.
     *
     * @param string $domain domain name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_forwarder($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        $forwarders = array();

        $forwarders = $this->get_forwarders();

        // Check for existing record
        if (count($forwarders) == 0)
            return;

        $newforwarders = array();

        foreach ($forwarders as $forwardinfo) {
            if ($forwardinfo['domain'] == $domain)
                continue;

            $newforwarders[] = $forwardinfo;
        }

        $this->_set_forwarders($newforwarders);

        $this->_delete_list_item('relay_domains', ',', $domain);
    }

    /**
     * Deletes queued messages for given list of IDs.
     *
     * @param array $messageids message IDs
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_queued_messages($messageids)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();

        foreach ($messageids as $id) {
            if (preg_match('/^[A-Z0-9]+$/', $id))
                $shell->Execute(self::COMMAND_POSTSUPER, "-d $id", TRUE);
            else
                throw new Validation_Exception(POSTFIX_LANG_MAIL_ID . " - " . LOCALE_LANG_INVALID, COMMON_ERROR);
        }
    }

    /**
     * Deletes a trusted network.
     *
     * @param string $trustednetwork trusted network for relaying
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_trusted_network($trustednetwork)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_trusted_network($network));

        // Prevent deletion of localhost
        //------------------------------

        // if ($trustednetwork == self::LOCALHOST)
        //   throw new Validation_Exception(POSTFIX_LANG_ERRMSG_LOCALHOST);

        $this->_delete_list_item('mynetworks', ',', $trustednetwork);
    }

    /**
     * Deletes a recipient BCC mapping.
     *
     * @param  string  $filename  recipient bcc map file
     *
     * @return  void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function delete_recipient_bcc_maps($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete recipient bcc
        //---------------------

        $this->_delete_list_item('recipient_bcc_maps', ',', "hash:$filename");
    }


    /**
     * Deletes a sender BCC mapping.
     *
     * @param  string  $filename  sender bcc map file
     *
     * @return  void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function delete_sender_bcc_maps($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete sender bcc
        //------------------

        $this->_delete_list_item('sender_bcc_maps', ',', "hash:$filename");
    }


    /**
     * Flushes the mail queue.
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function flush_queue()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['background'] = TRUE;

        $shell = new Shell();
        $shell->Execute(self::COMMAND_POSTSUPER, "-r ALL", TRUE);
        $shell->Execute(self::COMMAND_POSTFIX, "flush", TRUE, $options);
    }

    /**
     * Returns the always_bcc email.
     *
     * @return string bcc email
     * @throws Engine_Exception
     */

    public function get_always_bcc()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['always_bcc'];
    }

    /**
     * Returns catch all user (luser_relay).
     *
     * @return string catch all user
     * @throws Engine_Exception
     */

    public function get_catch_all()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['luser_relay'];
    }

    /**
     * Returns destinations.
     *
     * With the scrub flag set, localhost and any domain that uses a variable
     * (e.g. $myhostname, localhost.$mydomain) will be ignored.
     *
     * @param boolean $scrub scrub defaults
     *
     * @return array array of destinations
     * @throws Engine_Exception
     */

    public function get_destinations($scrub = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $hosts = array();
        $hosts = $this->_get_list_items('mydestination', ',');

        if ($scrub) {
            $scrubbed_hosts = array();
            foreach ($hosts as $host) {
                if (!(preg_match('/\$/', $host) || ($host === 'localhost')))
                    $scrubbed_hosts[] = $host;
            }

            $hosts = $scrubbed_hosts;
        }

        return $hosts;
    }

    /**
     * Returns domain.
     *
     * @return string domain
     * @throws Engine_Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['mydomain'];
    }

    /**
     * Returns hostname.
     *
     * @return string hostname
     * @throws Engine_Exception
     */

    public function get_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['myhostname'];
    }

    /**
     * Returns list of mail forwarder.
     *
     * @return array  hashed list of forwarded domain
     * @throws  Engine_Exception
     */

    public function get_forwarders()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_TRANSPORT);
            $lines = $file->get_contents_as_array();
        } catch (File_Not_Found_Exception $e) {
            return array();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $forward_list = array();
        $forward_info = array();

        foreach ($lines as $line) {
            if (preg_match("/^[0-9a-zA-Z_\-\.]+\s+smtp:/i", $line)) {
                $forward_info["domain"] = preg_replace("/\s+smtp:.*/", "", $line);
                $target = preg_replace("/.*\s+smtp:/", "", $line);

                $forward_info["server"] = preg_replace("/:.*/", "", $target);
                $forward_info["port"] = preg_replace("/.*:/", "", $target);
                $forward_list[] = $forward_info;
            }
        }

        return $forward_list;
    }

    /**
     * Returns local domains.
     *
     * Local domains include:
     * - primary domain (mydomain)
     * - destination domains (mydestination)
     * - forwarder domains (/etc/postfix/transport)
     *
     * @return array list of local domains
     * @throws Engine_Exception
     */

    public function get_local_domains()
    {
        clearos_profile(__METHOD__, __LINE__);

        $domains = array();

        // Primary domain
        $domains[] = $this->get_domain();

        // Destination domains
        $domains = array_merge($this->get_destinations(TRUE), $domains);

        // Forwarder domains
        $forwarders = $this->get_forwarders();

        foreach ($forwarders as $id => $details)
            $domains[] = $details['domain'];

        return $domains;
    }

    /**
     * Returns local recipient maps.
     *
     * @return string local recipient maps
     * @throws Engine_Exception
     */

    public function get_local_recipient_maps()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['local_recipient_maps'];
    }

    /** 
     * Returns the state of a policy service eg a greylist.
     *
     * @param string $service policy service name
     *
     * @return boolean state of policy service
     * @throws Engine_Exception
     */

    public function get_policy_service($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_mail_restriction(
            "smtpd_recipient_restrictions", 
            "check_policy_service",
            $service
        );
    }

    /**
     * Returns mail queue information.
     *
     * @return array mail queue information
     * @throws Engine_Exception
     */

    public function get_mail_queue()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $shell->execute(self::COMMAND_MAILQ_FORMATTED, '', TRUE);
        $output = $shell->get_output();

        return $output;
    }

    /**
     * Returns mailbox size.
     *
     * @return integer mailbox size in bytes
     * @throws Engine_Exception
     */

    public function get_max_mailbox_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['mailbox_size_limit'];
    }

    /**
     * Returns message size.
     *
     * @return integer message size in bytes
     * @throws Engine_Exception
     */

    public function get_max_message_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['message_size_limit'];
    }

    /**
     * Returns the recipient_bcc_maps list.
     *
     * @return array a file list as an array
     * @throws Engine_Exception
     */

    public function get_recipient_bcc_maps()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();
        $match = array();

        $bcc = $this->_get_list_items('recipient_bcc_maps', ',');
        
        foreach ($bcc as $entry) {
            if (preg_match('/^hash:(.*)/', $entry, $match))
                $list[] = trim($match[1]);
        }

        return $list;
    }

    /**
     * Returns the recipient_bcc_maps.
     *
     * @param  string $filename the filename of the map file
     *
     * @return array contents of file as an array
     * @throws Engine_Exception
     */

    public function get_recipient_bcc_map_contents($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $contents = Array();
        
        $file = new File($filename);

        if ($file->exists())
            $contents = $file->get_contents_as_array();

        return $contents;
    }

    /**
     * Returns relay hosts.
     *
     * @return array  array of relay hosts
     * @throws  Engine_Exception
     */

    public function get_relay_hosts()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hosts = array();

        $rawhosts = array();
        $rawhosts = $this->_get_list_items('relayhost', self::CONSTANT_SPACE);

        if ($rawhosts) {
            foreach ($rawhosts as $host) {
                $host = preg_replace("/[\[\]]/", "", $host);
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    /**
     * Returns the sender_bcc_maps list.
     *
     * @return array a file list as an array
     * @throws Engine_Exception
     */

    public function get_sender_bcc_maps()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = Array();

        $bcc = $this->_get_list_items('sender_bcc_maps', ',');
        $scrubbed_bcc = array();
        $match = array();
        
        foreach ($bcc as $entry) {
            if (preg_match('/^hash:(.*)/', $entry, $match))
                $list[] = trim($match[1]);
        }
        
        return $list;
    }

    /**
     * Returns the sender_bcc_maps.
     *
     * @return string the file that maps senders to BCC
     * @throws Engine_Exception
     */

    public function get_sender_bcc_maps_contents($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $contents = Array();
        
        $file = new File($filename);

        if ($file->exists())
            $contents = $file->get_contents_as_array();

        return $contents;
    }

    /**
     * Get SMTP authentication state.
     *
     *
     * @return boolean  TRUE if SMTP authentication is enabled
     * @throws  Engine_Exception
     */

    public function get_smtp_authentication_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $state = $this->config['smtpd_sasl_auth_enable'];

        if (preg_match("/yes/i", $state))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns SMTP port.
     *
     * @return integer SMTP port
     * @throws Engine_Exception
     */

    public function get_smtp_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        $port = 0;

        $file = new File(self::FILE_CONFIG_MASTER);
        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            if ($line && (preg_match("/^smtp/", $line) || preg_match("/^2525/", $line))) {
                $port = (int) rtrim(preg_replace("/^([\w]+).*$/", "\$1", $line));
                break;
            }
        }

        if ($port === 'smtp')
            $port = 25;

        return $port;
    }

    /**
     * Returns trusted networks for relaying.
     *
     * @return array  array of trusted networks for relaying
     * @throws  Engine_Exception
     */

    public function get_trusted_networks()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();
        $list = $this->_get_list_items('mynetworks', ',');

        return $list;
    }

    /**
     * Set the always bcc parameter.
     *
     * @param string $email e-mail address
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_always_bcc($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($email !== '')
            Validation_Exception::is_valid($this->validate_email($email));

        $this->_set_parameter('always_bcc', $email);
    }

    /**
     * Sets catch all user (luser_relay).
     *
     * If username is an empty string, messages will be set to bounce.
     *
     * @param string $username catch all username
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_catch_all($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($username !== '')
            Validation_Exception::is_valid($this->validate_username($username));

        $this->_set_parameter('luser_relay', $username);
    }

    /**
     * Sets domain.
     *
     * @param string $domain domain
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain($domain));

        $this->_set_parameter('mydomain', $domain);    
    }

    /**
     * Sets hostname.
     *
     * @param string $hostname hostname
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($hostname));

        $this->_set_parameter('myhostname', $hostname);
    }

    /**
     * Sets local recipient maps.
     *
     * This parameter can only be set to the default (DEFAULT_LOCAL_RECIPIENT_MAPS)
     * or nothing at all.
     *
     * @param string $maps local recipient maps
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_local_recipient_maps($maps)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: Some users have added their own local_recipient_maps configuration
        // Specifically, boxes used as a antivirus/antispam gateways have a list
        // of valid user e-mail addresses configured on the gateway box.

        $current = $this->get_local_recipient_maps();

        if (! (empty($current) || $current === self::DEFAULT_LOCAL_RECIPIENT_MAPS))
            return;

        if (! (empty($maps) || $maps === self::DEFAULT_LOCAL_RECIPIENT_MAPS) )
            throw new Validation_Exception(lang('smtp_recipient_mapping_is_invalid'));

        $this->_set_parameter('local_recipient_maps', $maps);    
    }

    /**
     * Sets the maximum mailbox size (in bytes).
     *
     * @param integer $size mailbox size in bytes
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_max_mailbox_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match("/^(\d)+$/", $size))
            throw new Validation_Exception(POSTFIX_LANG_MAILBOX_SIZE . " - " . LOCALE_LANG_INVALID);

        $this->_set_parameter('mailbox_size_limit', $size);
    }

    /**
     * Set the maximum message size (in bytes).
     * Note: you should set the antivirus and/or antispam message
     * size limits if required.
     *
     * @param int  $size  message size in bytes
     *
     * @return void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function set_max_message_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! preg_match("/^(\d)+$/", $size))
            throw new Validation_Exception(POSTFIX_LANG_MESSAGE_SIZE . " - " . LOCALE_LANG_INVALID);

        // Make sure mailbox_size_limit is bigger than message_size_limit
        //---------------------------------------------------------------

        $mailbox_size_limit = $this->GetMaxMailboxSize();

        if ($mailbox_size_limit < $size)
            $this->_set_parameter('mailbox_size_limit', $size);

        // Set parameter
        //--------------

        $this->_set_parameter('message_size_limit', $size);
    }

    /**
     * Set the recipient_bcc_maps parameter.
     *
     * @param  string $filename the filename of the map file
     * @param  array $map  an array containing the mapping for mail accounts and the bcc recipient
     *
     * @return  void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function set_recipient_bcc_maps($filename, $map, $replace = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // On purpose, we don't add entry to the the recipient_bcc_maps directive...do this with 'AddRecipientBcc' method
        $contents = Array();
        $newcontents = Array();
        $data = "";

        // Make sure file exists
        try {
            $file = new File($filename, TRUE);
            if ($file->exists())
                $contents = $file->get_contents_as_array();
            if ($file->exists())
                $file->Delete();
            $file->create('root', 'root', '0644');
            if ($replace)
                $newcontents = array_unique($map);
            else
                $newcontents = array_unique(array_merge($contents, $map));

            foreach ($newcontents as $line)
                $data .= "$line\n";

            $file->add_lines($data);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Run postmap
        $shell = new Shell();

        if ($shell->Execute(self::COMMAND_POSTMAP, $filename, TRUE) != 0)
            throw new Engine_Exception($shell->GetFirstOutputLine(), COMMON_ERROR);
    }

    /**
     * Sets relay host.
     *
     * @param string  $host relay host
     * @param integer $port port
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_relay_host($host, $port = 25)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_relay_host($host));
        Validation_Exception::is_valid($this->validate_port($port));

        $host_entry = ($host === '') ? '' : "[$host]:$port";

        $this->_set_parameter('relayhost', $host_entry);
    }

    /**
     * Set the sender_bcc_maps parameter.
     *
     * @param  string $filename the filename of the map file
     * @param  array $map  an array containing the mapping for mail accounts and the bcc sender
     *
     * @return  void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function set_sender_bcc_maps($filename, $map, $replace = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // On purpose, we don't add entry to the the sender_bcc_maps directive...do this with 'AddSenderBcc' method
        $contents = Array();
        $newcontents = Array();
        $data = "";

        // Make sure file exists
        try {
            $file = new File($filename, TRUE);
            if ($file->exists())
                $contents = $file->get_contents_as_array();
            if ($file->exists())
                $file->Delete();
            $file->create('root', 'root', '0644');
            if ($replace)
                $newcontents = array_unique($map);
            else
                $newcontents = array_unique(array_merge($contents, $map));

            foreach ($newcontents as $line)
                $data .= "$line\n";

            $file->add_lines($data);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Run postmap
        $shell = new Shell();

        if ($shell->Execute(self::COMMAND_POSTMAP, $filename, TRUE) != 0)
            throw new Engine_Exception($shell->GetFirstOutputLine(), COMMON_ERROR);
    }

    /** 
     * Sets a policy service eg a greylist.
     *
     * @param string $service policy service name
     * @param boolean $state state of policy service
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_policy_service($service, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

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
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_smtp_authentication_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if ($state)
            $state = "yes";
        else
            $state = "no";

        // Set parameter
        //--------------

        $this->_set_parameter('smtpd_sasl_auth_enable', $state);
    }

    /**
     * Sets SMTP port to bind to/listen on.
     *
     * @param int  $port  port to bind to
     *
     * @return void
     * @throws  Validation_Exception, Engine_Exception
     */

    public function set_smtp_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($port != "25" && $port != "2525" && $port != "smtp")
            throw new Validation_Exception(POSTFIX_LANG_ERRMSG_PORT_INVALID);

        try {
            $file = new File(self::FILE_CONFIG_MASTER);

            if ($port == "25")
                $port = "smtp";

            $match = $file->ReplaceOneLineByPattern("/^smtp /", "$port \$1");

            if (! $match)
                $match = $file->ReplaceOneLineByPattern("/^2525 (.*)/i", "$port \$1");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for destination domain name.
     *
     * @param string  $domain       domain name.
     * @param boolean $check_exists 
     *
     * @return boolean error message if domain is invalid
     */

    public function validate_destination_domain($domain, $check_exists = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('smtp_domain_is_invalid');

        if ($check_exists) {
            $destinations = $this->get_destinations();

            if (in_array($domain, $destinations))
                return lang('smtp_domain_already_exists');
        }
    }

    /**
     * Validation routine for domain name.
     *
     * @param string $domain domain name.
     *
     * @return boolean error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('smtp_domain_is_invalid');
    }

    /**
     * Validation routine for an email address
     *
     * @param string $email an email address
     *
     * @return string error message if email address is invalid
     */

    public function validate_email($email)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^.*@localhost$|^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$/', $email))
            return lang('smtp_email_address_is_invalid');
    }

    /**
     * Validation routine for forwarder domain name.
     *
     * @param string $domain domain name.
     *
     * @return boolean error message if forwarder domain is invalid
     */

    public function validate_forwarder_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('smtp_domain_is_invalid');

        $forwarders = $this->get_forwarders();

        foreach ($forwarders as $forward_info) {
            if ($forward_info['domain'] === $domain)
                return lang('smtp_domain_already_exists');
        }
    }

    /**
     * Validation routine for server target.
     *
     * @param string $server server
     *
     * @return string error message if server is invalid
     */

    public function validate_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ((! Network_Utils::is_valid_ip($server)) && (! Network_Utils::is_valid_domain($server)))
            return lang('smtp_server_is_invalid');
    }

    /**
     * Validation routine for port.
     *
     * @param string $port port number
     *
     * @return boolean error message if trusted port is invalid
     */

    public function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ((! Network_Utils::is_valid_port($port)))
            return lang('smtp_port_is_invalid');
    }

    /**
     * Validation routine for relay host.
     *
     * @param string $host host
     *
     * @return string error message if host is invalid
     */

    public function validate_relay_host($host)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($host === '')
            return;

        if ((! Network_Utils::is_valid_ip($host)) && (! Network_Utils::is_valid_domain($host)))
            return lang('smtp_host_is_invalid');
    }

    /**
     * Validation routine for trusted networks.
     *
     * Both IP addresses and networks are permitted.
     *
     * @param string $network trusted network
     *
     * @return boolean error message if trusted network is invalid
     */

    public function validate_trusted_network($network)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ((! Network_Utils::is_valid_network($network)) && (! Network_Utils::is_valid_ip($network)))
            return lang('smtp_trusted_network_is_invalid');
    }

    /**
     * Validation routine for user.
     *
     * @param string $user username or email address
     *
     * @return string error message if user is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
        if (preg_match("/;/", $username))
            return lang('smtp_username_is_invalid');
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
     *
     * @return boolean TRUE if item already exists
     * @throws Validation_Exception, Engine_Exception
     */

    protected function _add_list_item($key, $delimiter, $newitem)
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();
        $list = $this->_get_list_items($key, $delimiter);

        $thelist = "";

        foreach ($list as $item) {
            if ($item === $newitem)
                return TRUE;

            if ($delimiter == self::CONSTANT_SPACE)
                $thelist .= $item . ' ';
            else
                $thelist .= $item . $delimiter . ' ';
        }

        $thelist .= $newitem;

        $shell = new Shell();
        $shell->execute(self::COMMAND_POSTCONF, "-e '$key=$thelist'", TRUE);

        $this->is_loaded = FALSE;
    }

    /**
     * Deletes an item from a given list.
     *
     * @access private
     * @param string $key parameter name in the configuration file
     * @param string $delimiter delimiter used for the given key
     * @param string $olditem item to delete from list
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_list_item($key, $delimiter, $olditem)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Grab the current list (if any)
        //-------------------------------

        $list = array();
        $list = $this->_get_list_items($key, $delimiter);

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

        $shell = new Shell();
        $shell->execute(self::COMMAND_POSTCONF, "-e '$key=$thelist'", TRUE);

        $this->is_loaded = FALSE;
    }

    /**
     * Returns a list of items for a given key.
     *
     * @param string $key       parameter name in the configuration file
     * @param string $delimiter delimiter used for the given key
     *
     * @access private
     * @return array list of items for given key
     * @throws Engine_Exception
     */

    protected function _get_list_items($key, $delimiter)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $rawlist = $this->config[$key];

        if (empty($rawlist))
            return array();

        $list = array();

        if ($delimiter === self::CONSTANT_SPACE)
            $list = preg_split('/ /', $rawlist);
        else
            $list = preg_split("/$delimiter/", $rawlist);

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
     * @param string $value value for given type
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_mail_restriction($parameter, $type, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $right_side = empty($value) ? $type : "$type $value";
        $right_side = preg_replace("/\//", '\/', $right_side);

        if (preg_match("/$right_side/", $this->config[$parameter]))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Loads configuration files.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $shell->execute(self::COMMAND_POSTCONF, '');
        $rawoutput = $shell->get_output();

        foreach ($rawoutput as $line) {
            $items = preg_split('/\s+/', $line, 3);
            $this->config[$items[0]] = isset($items[2]) ? $items[2] : '';
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Sets mail forwarder list.
     *
     * @param array $forwarders hash array of forwarders
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_forwarders($forwarders)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Set parameter in main.cf
        $this->_set_parameter('transport_maps', "hash:" . self::FILE_TRANSPORT);

        // Managed transport file
        $file = new File(self::FILE_TRANSPORT);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $transport_data = '';

        foreach ($forwarders as $forwarder)
            $transport_data .= "$forwarder[domain] smtp:$forwarder[server]:$forwarder[port]\n";

        $file->add_lines($transport_data);

        // Run postmap
        $shell = new Shell();
        $shell->execute(self::COMMAND_POSTMAP, self::FILE_TRANSPORT, TRUE);
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
     * @param string $value value for given type
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_mail_restriction($parameter, $type, $value, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $right_side = empty($value) ? $type : "$type $value";

        if ($state)    
            $this->_add_list_item($parameter, ",", $right_side);
        else
            $this->_delete_list_item($parameter, ",", $right_side);
    }

    /**
     * Sets a value for a parameter.
     *
     * @access private
     * @param string $key key name
     * @param string $value value for the key
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $shell->Execute(self::COMMAND_POSTCONF, "-e '$key=$value'", TRUE);

        $this->is_loaded = FALSE;
    }
}
