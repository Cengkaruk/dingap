<?php

/**
 * Zarafa class.
 *
 * @category   Apps
 * @package    Zarafa
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 Tim Burgess
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/zarafa/
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

namespace clearos\apps\zarafa;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Shell');

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
 * Zarafa class.
 *
 * @category   Apps
 * @package    Zarafa
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 Tim Burgess
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/zarafa/
 */

class Zarafa extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const LDAP_CONFIG = '/etc/zarafa/ldap.cfg';
    const DAGENT_CONFIG = '/etc/zarafa/dagent.cfg';

    const FILE_ZARAFA_GATEWAY = '/etc/zarafa/gateway.cfg';
    const FILE_ZARAFA_ICAL = '/etc/zarafa/ical.cfg';
    const FILE_ZARAFA_SERVER = '/etc/zarafa/server.cfg';

    const FILE_MYSQL_PASSWORD = '/etc/system/database';

    const POSTFIX_CONFIG = '/etc/postfix/main.cf';
    const POSTFIX_MASTER_CONFIG = '/etc/postfix/master.cf';
    const COMMAND_SERVICE = '/sbin/service';
    const COMMAND_PIDOF = '/sbin/pidof';
    const CONSTANT_SPACE = 1;
    const FILE_TRANSPORT = '/etc/postfix/transport';
    const COMMAND_POSTMAP = '/usr/sbin/postmap';
    const SASL_CONFIG = '/etc/saslauthd.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Zarafa constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('zarafa');
    }

    /**
     * Returns MySQL password.
     *
     * @return string MySQL password
     * @throws Engine_Exception
     */

    public function get_system_mysql_passwd()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_MYSQL_PASSWORD);

        $retval = $file->lookup_value("/^password\s+/i");
        $retval = trim($retval, " = ");

        return $retval;
    }

    /**
     * Returns MySQL configuration.
     *
     * @return array MySQL configuration
     */

    public function get_mysql_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ZARAFA_SERVER);

        $retval = $file->lookup_value("/^mysql_password\s+/i");
        $output['password'] = trim($retval, " = ");

        $retval2 = $file->lookup_value("/^mysql_host\s+/i");
        $output['host'] = trim($retval2, " = ");

        $retval3 = $file->lookup_value("/^mysql_port\s+/i");
        $output['port'] = trim($retval3, " = ");

        return $output;
    }

    /**
     * Returns configured gateway port for given service.
     *
     * @param string $service service name
     *
     * @return integer port number
     * @throws Engine_EXception
     */

    public function get_gateway_port($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ZARAFA_GATEWAY);

        $port = $file->lookup_value("/^${service}_port\s*=\s*/i");
        $port = trim($port);

        return $port;
    }

    /**
     * Returns state of gateway service.
     *
     * @param string $service service name
     *
     * @return boolean state of gateway service.
     * @throws Engine_EXception
     */

    public function get_gateway_service($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ZARAFA_GATEWAY);

        $state = $file->lookup_value("/^${service}_enable\s*=\s*/i");

        if (preg_match('/yes/i', $state))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns configured ICAL port for given service.
     *
     * @param string $service service name
     *
     * @return integer port number
     * @throws Engine_EXception
     */

    public function get_ical_port($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ZARAFA_ICAL);

        $port = $file->lookup_value("/^${service}_port\s*=\s*/i");
        $port = trim($port);
        return $port;
    }

    /**
     * Returns state of iCal service.
     *
     * @param string $service service name
     *
     * @return boolean state of iCal service.
     * @throws Engine_EXception
     */
    public function get_ical_service($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ZARAFA_ICAL);

        $state = $file->lookup_value("/^${service}_enable\s+/i");
        $state = trim($state);

        if (preg_match('/yes/i', $state))
            return TRUE;
        else
            return FALSE;
    }


    ///////////////////////////////////////////////////////////////////////////////////////
    // Reload config - only if we're already running
    // Our own Service reload config as Daemon.class doesn't support easy manipulation of of zarafa-services
    //////////////////////////////////////////////////////////////////////////////////////

    public function reload_config($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        $args = "restart";
        $file = new File("/var/run/" . $service . ".pid");
        try {
            $shell = new Shell();
            $exitcode = $shell->Execute(self::COMMAND_PIDOF, "-x -s $service");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Reload if service is deemed to be running

        if ($file->exists() || $exitcode ==0) {
        try {
            $options['stdin'] = "use_popen";
            $shell = new Shell();
            $shell->Execute(self::COMMAND_SERVICE, "$service $args", TRUE, $options);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        } 
        }
    }



    /**
     * Sets mysql config
     *
     * @param 
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function set_mysql_config($passwd, $host, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ZARAFA_SERVER);

        $match = $file->replace_lines("/^mysql_password\s+/i", "mysql_password = $passwd\n");
        if (! $match)
            $file->add_lines_after("mysql_password = $passwd\n", "/^[^#]/");

        $match2 = $file->replace_lines("/^mysql_host\s+/i", "mysql_host = $host\n");
        if (! $match2)
            $file->add_lines_after("mysql_host = $host\n", "/^[^#]/");

        $match3 = $file->replace_lines("/^mysql_port\s+/i", "mysql_port = $port\n");
        if (! $match3)
            $file->add_lines_after("mysql_port = $port\n", "/^[^#]/");
    }



    public function set_postfix_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Update tag if it exists
        //------------------------
                $file = new File(self::POSTFIX_CONFIG);    
          try {
            $lookup = $file->lookup_value("/^mailbox_transport\s+/i");
            $lookup = trim($lookup, " = ");
            if ($lookup=="zarafa:" || $lookup=="zarafa") {
                $match1 = $file->replace_lines("/^mailbox_transport\s+/i", "mailbox_transport = mailpostfilter\n");
                if (! $match1)
                    $file->add_lines_after("mailbox_transport = mailpostfilter\n", "/^[^#]/");
            }
                } catch (Exception $e) {
                   throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
                }

          try {
            $match2 = $file->replace_lines("/^zarafa_destination_recipient_limit\s+/i", "zarafa_destination_recipient_limit = 1\n");
            if (! $match2)
                $file->add_lines_after("zarafa_destination_recipient_limit = 1\n", "/^[^#]/");
                } catch (Exception $e) {
                   throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
                }

                try {
                      $lookup = $file->lookup_value("/^transport_maps\s+/i");
                      $lookup = trim($lookup, " = ");
                      if ($lookup!="hash:/etc/postfix/transport"){
                              $match3 = $file->replace_lines("/^transport_maps\s+/i", "transport_maps = hash:/etc/postfix/transport\n");
                              if (! $match3)
                                      $file->add_lines_after("transport_maps = hash:/etc/postfix/transport\n", "/^[^#]/");
                      }
                } catch (Exception $e) {
                   throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
                }

                try {
            //required to implement system aliases
                     $match4 = $file->replace_lines("/^virtual_alias_maps\s+/i", "virtual_alias_maps = \$alias_maps, \$virtual_maps, ldap:/etc/postfix/ldap-aliases.cf, ldap:/etc/postfix/ldap-groups.cf\n");
                     if (! $match4)
                            $file->add_lines_after("virtual_alias_maps = \$alias_maps, \$virtual_maps, ldap:/etc/postfix/ldap-aliases.cf, ldap:/etc/postfix/ldap-groups.cf\n", "/^[^#]/");
                } catch (Exception $e) {
                   throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
                }


        try {
        $file = new File(self::POSTFIX_MASTER_CONFIG);
                        $match = $file->replace_lines("/^zarafa\s+/i", "zarafa unix - n n - 10 pipe flags= user=zarafa argv=/usr/bin/zarafa-dagent \${user}\n");
                        if (! $match)
                                $file->add_lines_after("zarafa unix - n n - 10 pipe flags= user=zarafa argv=/usr/bin/zarafa-dagent \${user}\n", "/^[^#]/");

                } catch (Exception $e) {
                   throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
                }

    }


    public function set_dagent_port()
        {
        clearos_profile(__METHOD__, __LINE__);

                // Update tag if it exists as clashes with 2003
                //------------------------

                try {
                     $file = new File(self::DAGENT_CONFIG);
                     $match = $file->replace_lines("/^lmtp_port\s+/i", "lmtp_port = 2004\n");
                     if (! $match)
                                 $file->add_lines_after("lmtp_port = 2004\n", "/^[^#]/");

                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }

    public function set_ical_port($port)
        {
        clearos_profile(__METHOD__, __LINE__);

                if (! $this->IsValidPort($port))
            throw new Validation_Exception(ZARAFA_LANG_ERRMSG_PORT_INVALID);
    
          // Update tag if it exists
                //------------------------

                try {
                        $file = new File(self::FILE_ZARAFA_ICAL);
                        $match = $file->replace_lines("/^ical_port\s+/i", "ical_port = $port\n");

                        // If tag does not exist, add it
                        //------------------------------

                        if (! $match)
                                $file->add_lines_after("ical_port = $port\n", "/^[^#]/");

                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }



    public function set_gateway_port($pop3,$pop3s,$imap,$imaps)
        {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->IsValidPort($pop3))
                throw new Validation_Exception(ZARAFA_LANG_ERRMSG_PORT_INVALID);
        if (! $this->IsValidPort($pop3s))
                throw new Validation_Exception(ZARAFA_LANG_ERRMSG_PORT_INVALID);
        if (! $this->IsValidPort($imap))
                throw new Validation_Exception(ZARAFA_LANG_ERRMSG_PORT_INVALID);
        if (! $this->IsValidPort($imaps))
                throw new Validation_Exception(ZARAFA_LANG_ERRMSG_PORT_INVALID);

                try {
                        $file = new File(self::FILE_ZARAFA_GATEWAY);
               $match1 = $file->replace_lines("/^pop3_port\s+/i", "pop3_port = $pop3\n");
               if (! $match1)
                                $file->add_lines_after("pop3_port = $pop3\n", "/^[^#]/");
               $match2 = $file->replace_lines("/^pop3s_port\s+/i", "pop3s_port = $pop3s\n");
               if (! $match2)
                                $file->add_lines_after("pop3s_port = $pop3s\n", "/^[^#]/");

               $match3 = $file->replace_lines("/^imap_port\s+/i", "imap_port = $imap\n");
               if (! $match3)
                                $file->add_lines_after("imap_port = $imap\n", "/^[^#]/");

               $match4 = $file->replace_lines("/^imaps_port\s+/i", "imaps_port = $imaps\n");
               if (! $match4)
                                $file->add_lines_after("imaps_port = $imaps\n", "/^[^#]/");

                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }


    public function enable_gateway_service($service)
        {
        clearos_profile(__METHOD__, __LINE__);

                try {
                        $file = new File(self::FILE_ZARAFA_GATEWAY);
               $string = $service . "_enable";
               $match = $file->replace_lines("/^$string\s+/i", "$string = yes\n");
               if (! $match)
                                $file->add_lines_after("$string = yes\n", "/^[^#]/");
                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }


    public function disable_gateway_service($service)
        {
        clearos_profile(__METHOD__, __LINE__);

                try {
                        $file = new File(self::FILE_ZARAFA_GATEWAY);
               $string = $service . "_enable";
               $match = $file->replace_lines("/^$string\s+/i", "$string = no\n");
               if (! $match)
                                $file->add_lines_after("$string = no\n", "/^[^#]/");
                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }


    public function enable_ical_service($service)
        {
        clearos_profile(__METHOD__, __LINE__);

                try {
                        $file = new File(self::FILE_ZARAFA_ICAL);
               $string = $service . "_enable";
               $match = $file->replace_lines("/^$string\s+/i", "$string = yes\n");
               if (! $match)
                                $file->add_lines_after("$string = yes\n", "/^[^#]/");
                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }


    public function disable_ical_service($service)
        {
        clearos_profile(__METHOD__, __LINE__);

                try {
                        $file = new File(self::FILE_ZARAFA_ICAL);
               $string = $service . "_enable";
               $match = $file->replace_lines("/^$string\s+/i", "$string = no\n");
               if (! $match)
                                $file->add_lines_after("$string = no\n", "/^[^#]/");
                } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
        }

    /**
     * Returns a list of configured users forwarded to zarafa.
     * This could also be a single domain
     *
     * @return array  list of configured users or domains
     * @throws  Engine_Exception
     */

    public function get_user_forward()
    {
        clearos_profile(__METHOD__, __LINE__);

        $entries = array();

        try {
            $file = new File(self::FILE_TRANSPORT);
            $lines = $file->GetContentsAsArray();
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        foreach ($lines as $line) {
            if ($line && !preg_match("/^[#\s]/", $line) && preg_match("/zarafa/", $line))
                $entries[] = rtrim(preg_replace("/ .*$/", "", $line));
        }

        sort($entries);
        return $entries;
    }

    /**
     * Adds user forward and runs postmap
     *
     * @param string $domain virtual domain
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_user_forward($user)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        $users = $this->GetUserForward();

        if (in_array($user, $users)) {
            $this->AddValidationError(POSTFIX_LANG_ERRMSG_VIRTUAL_DOMAIN_EXISTS, __METHOD__, __LINE__);
            return;
        }


        // Add user forward
        //-------------------

        try {
            $file = new File(self::FILE_TRANSPORT);
            $file->add_lines($user . " zarafa\n");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Run postmap
        //------------

        $shell = new Shell();

        if ($shell->Execute(self::COMMAND_POSTMAP, self::FILE_TRANSPORT, TRUE) != 0)
            throw new Engine_Exception($shell->GetFirstOutputLine(), COMMON_ERROR);
    }

    /**
     * Deletes a forwarder entry.
     *
     * @param string $domain domain name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_forwarder($user)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete user forward
        //-------------------
        $line = "/^$user\s+/i";

        try {
            $file = new File(self::FILE_TRANSPORT);
            $file->DeleteLines($line);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }

        // Run postmap
        //------------

        $shell = new Shell();

        if ($shell->Execute(self::COMMAND_POSTMAP, self::FILE_TRANSPORT, TRUE) != 0)
            throw new Engine_Exception($shell->GetFirstOutputLine(), COMMON_ERROR);

    }

    /*************************************************************************/
    /* V A L I D A T I O N   R O U T I N E S                                 */
    /*************************************************************************/

    /**
     * Validation routine for port
     *
     * @param int $port port
     *
     * @return boolean TRUE if port is valid
     */

    public function is_valid_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^[0-9]+$/", $port))
            return TRUE;
        $this->AddValidationError(ZARAFA_LANG_ERRMSG_PORT_INVALID, __METHOD__, __LINE__);
        return FALSE;
    }
}
