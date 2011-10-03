<?php

/**
 * Httpd class.
 *
 * @category   Apps
 * @package    Httpd
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/httpd/
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

namespace clearos\apps\httpd;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('httpd');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Httpd class.
 *
 * @category   Apps
 * @package    Httpd
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/httpd/
 */

class Httpd extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

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
     * Httpd constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Adds the default host.
     *
     * @param string $domain domain name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function add_default_host($domain)
    {
        clearos_profile(__METHOD__, __LINE__);
        $this->add_host($domain, self::FILE_DEFAULT);
    }

    /**
     * Adds a virtual host with defaults.
     *
     * @param string $domain domain name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function add_virtual_host($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->add_host($domain, "$domain.vhost");
    }

    /**
     * Generic add virtual host.
     *
     * @param string $domain domain name
     * @param string $confd  configuration file
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function add_host($domain, $confd)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        if (! $this->is_valid_domain($domain))
            throw new Validation_Exception(lang('web_website_invalid'));

        try {
            $config = new File(self::PATH_CONFD . "/$confd");
            if ($config->exists()) {
                throw new Validation_Exception(lang('web_website_exists'));
            }
        } catch (Validation_Exception $e) {
            throw new Validation_Exception(clearos_exception_message($e));
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $docroot = self::PATH_VIRTUAL . "/$domain";
        $entry = "<VirtualHost *:80>\n";
        $entry .= "\tServerName $domain\n";
        $entry .= "\tServerAlias *.$domain\n";
        if ($confd == self::FILE_DEFAULT) {
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
            $config->create('root', 'root', '0644');
            $config->add_lines($entry);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $webfolder = new Folder($docroot);
            if (! $webfolder->exists())
                $webfolder->create('root', 'root', '0775');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Uncomment NameVirtualHost
        try {
            $httpcfg = new File(self::FILE_CONFIG);
            $match = $httpcfg->replace_lines("/^[#\s]*NameVirtualHost.*\*/", "NameVirtualHost *:80\n");
            if (! $match)
                $httpcfg->add_lines("NameVirtualHost *:80\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Make sure our "Include conf.d/*.vhost" is still there
        try {
            $includeline = $httpcfg->LookupLine("/^Include\s+conf.d\/\*\.vhost/");
        } catch (File_No_Match_Exception $e) {
            $httpcfg->add_lines("Include conf.d/*.vhost\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Deletes a virtual host.
     *
     * @param string $domain domain name
     *
     * @return void
     *
     * @throws Validation_Exception, Engine_Exception
     */

    function delete_virtual_host($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_domain($domain));

        $flexshare = new Flexshare();
        try {
            $share = $flexshare->get_share($domain);
            // Check to see if Directory == docroot
            $conf = $this->get_host_info($domain . '.vhost');
            if (trim($conf['docroot']) == trim($share['ShareDir'])) {
                // Default flag to *not* delete contents of dir
                $flexshare->delete_share($domain, FALSE);
            }
        } catch (Flexshare_Not_Found_Exception $e) {
            //Ignore
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $config = new File(self::PATH_CONFD . "/" . $domain . ".vhost");
            $config->delete();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Gets server name (ServerName).
     *
     * @return string server name
     *
     * @throws Engine_Exception
     */

    function get_server_name()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);
            $retval = $file->lookup_value("/^ServerName\s+/i");
        } catch (File_No_Match_Exception $e) {
            return "";
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        return $retval;
    }

    /**
     * Gets SSL state.
     *
     * @return boolean TRUE if SSL is enabled
     *
     * @throws Engine_Exception
     */

    function get_ssl_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_SSL);
            if ($file->exists())
                return TRUE;
            else
                return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Gets a list of configured virtual hosts.
     *
     * @return array list of virtual hosts
     *
     * @throws Engine_Exception
     */

    function get_virtual_hosts()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $folder = new Folder(self::PATH_CONFD);
            $files = $folder->get_listing();
            $vhosts = array();
            foreach ($files as $file) {
                if (preg_match("/\.vhost$/", $file))
                    array_push($vhosts, preg_replace("/\.vhost$/", "", $file));
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        return $vhosts;
    }

    /**
     * Gets default host info and returns it in a hash array.
     *
     * @return array hash array with default host info
     * @throws Engine_Exception
     */

    function get_default_host_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $info = $this->get_host_info(self::FILE_DEFAULT);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        return $info;
    }

    /**
     * Gets virtual host info and returns it in a hash array.
     *
     * @param string $domain domain name
     *
     * @return array hash array with virtual host info
     * @throws Validation_Exception, Engine_Exception
     */

    function get_virtual_host_info($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_domain($domain));

        $info = Array();

        try {
            $info = $this->get_host_info("$domain.vhost");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        return $info;
    }

    /**
     * Returns configuration information for a given host.
     *
     * @param string $confd the configuration file
     *
     * @return array settings for a given host
     * @throws Engine_Exception
     */

    function get_host_info($confd)
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = array();

        try {
            $file = new File(self::PATH_CONFD . "/$confd");
            $contents = $file->get_contents();
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
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        if ($count < 5)
            throw new Engine_Exception(lang('base_something_unexpected_happened'), COMMON_ERROR);

        return $info;
    }

    /**
     * Sets parameters for a virtual host.
     *
     * @param string $domain domain name
     * @param string $alias  alias name
     *
     * @return void
     * @throws ValidationException, Engine_Exception
     */

    function set_default_host($domain, $alias)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_domain($domain));

        try {
            $file = new File(self::PATH_CONFD . "/" . self::FILE_DEFAULT);
            $file->replace_lines("/^\s*ServerName/", "\tServerName $domain\n");
            $file->replace_lines("/^\s*ServerAlias/", "\tServerAlias $alias\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        if ($this->get_ssl_state())
            $filename = self::FILE_SSL;
        else
            $filename = self::FILE_SSL . ".off";

        try {
            $file = new File($filename);
            $file->replace_lines("/^\s*ServerName/", "ServerName $domain\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets server name
     *
     * @param string $servername server name
     *
     * @return array settings for a given host
     * @throws ValidationException, Engine_Exception
     */

    function set_server_name($servername)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_servername($servername));

        // Update tag if it exists
        //------------------------

        try {
            $file = new File(self::FILE_CONFIG);
            $match = $file->replace_lines("/^\s*ServerName/i", "ServerName $servername\n");
            // If tag does not exist, add it
            //------------------------------
            if (! $match)
                $file->add_lines_after("ServerName $servername\n", "/^[^#]/");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets SSL state (on or off)
     *
     * @param boolean $sslstate SSL state (on or off)
     *
     * @return void
     * @throws ValidationException, Engine_Exception
     */

    function set_ssl_state($sslstate)
    {
        clearos_profile(__METHOD__, __LINE__);

        // IsValid
        //---------

        Validation_Exception::is_valid($this->validate_ssl_state($sslstate));

        try {
            $onfile = new File(self::FILE_SSL);
            $offfile = new File(self::FILE_SSL . ".off");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Handle "on" condition
        //----------------------

        try {
            if (($sslstate) && (! $onfile->exists())) {
                if (! $offfile->exists())
                    throw new Engine_Exception(lang('web_sslconfig_missing'), COMMON_ERROR);
                if (file_exists(COMMON_CORE_DIR.'/api/Horde.class.php')) {
                    include_once 'Horde.class.php';
                    $horde = new Horde();
                    if ($horde->GetPort() == 443) {
                        throw new ValidationException(lang('horde_port_inuse'));
                    }
                }
                $offfile->MoveTo(self::FILE_SSL);
                return;
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Handle "off" condition
        //-----------------------

        try {
            if ((!$sslstate) && ($onfile->exists())) {
                $onfile->move_to(self::FILE_SSL . ".off");
                return;
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets parameters for a virtual host.
     *
     * @param string $domain  domain name
     * @param string $alias   alias name
     * @param string $docroot document root
     *
     * @return void
     * @throws ValidationException, Engine_Exception
     */

    function set_virtual_host($domain, $alias, $docroot)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_domain($domain));

        Validation_Exception::is_valid($this->validate_doc_root($docroot));

        // TODO validation

        try {
            $file = new File(self::PATH_CONFD . "/" . $domain . ".vhost");
            $file->replace_lines("/^\s*ServerAlias/", "\tServerAlias $alias\n");
            $file->replace_lines("/^\s*DocumentRoot/", "\tDocumentRoot $docroot\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets parameters for a domain or virtual host.
     *
     * @param string $domain  the domain name
     * @param string $docroot document root
     * @param string $group   the group owner
     * @param string $ftp     FTP enabled status
     * @param string $smb     File (SAMBA) enabled status
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function configure_upload_methods($domain, $docroot, $group, $ftp, $smb)
    {
        clearos_profile(__METHOD__, __LINE__);
    
        if ($ftp && ! file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
            return;

        if ($smb && ! file_exists(COMMON_CORE_DIR . "/api/Samba.class.php"))
            return;

        try {
            $flexshare = new Flexshare();
            try {
                if (!$ftp && !$smb) {
                    try {
                        $flexshare->get_share($domain);
                        $flexshare->delete_share($domain, FALSE);
                    } catch (Flexshare_Not_Found_Exception $e) {
                        // GetShare will trigger this exception on a virgin box
                        // TODO: implement Flexshare.exists($name) instead of this hack
                    } catch (Exception $e) {
                        throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                    }
                    return;
                }
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }
            try {
                $share = $flexshare->get_share($domain);
            } catch (Flexshare_Not_Found_Exception $e) {
                $flexshare->add_share($domain, lang('web_site') . " - " . $domain, $group, TRUE);
                $flexshare->set_directory($domain, Httpd::PATH_DEFAULT);
                $share = $flexshare->get_share($domain);
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }
            // FTP
            // We check setting of some parameters so we can allow user override using Flexshare.
            if (!isset($share['FtpServerUrl']))
                $flexshare->set_ftp_server_url($domain, $domain);
            $flexshare->set_ftp_allow_passive($domain, 1, Flexshare::FTP_PASV_MIN, Flexshare::FTP_PASV_MAX);
            if (!isset($share['FtpPort']))
                $flexshare->set_ftp_override_port($domain, 0, Flexshare::DEFAULT_PORT_FTP);
            if (!isset($share['FtpReqSsl']))
                $flexshare->set_ftp_req_ssl($domain, 0);
            $flexshare->set_ftp_req_auth($domain, 1);
            $flexshare->set_ftp_allow_anonymous($domain, 0);
            $flexshare->set_ftp_user_owner($domain, NULL);
            //$flexshare->set_ftp_group_access($domain, Array($group));
            if (!isset($share['FtpGroupGreeting']))
                $flexshare->set_ftp_group_greeting($domain, lang('web_site') . ' - ' . $domain);
            $flexshare->set_ftp_group_permission($domain, Flexshare::PERMISSION_READ_WRITE_PLUS);
            $flexshare->set_ftp_group_umask($domain, Array('owner'=>0, 'group'=>0, 'world'=>2));
            $flexshare->set_ftp_enabled($domain, $ftp);
            // Samba
            $flexshare->set_file_comment($domain, lang('web_site') . ' - ' . $domain);
            $flexshare->set_file_public_access($domain, 0);
            $flexshare->set_file_permission($domain, Flexshare::PERMISSION_READ_WRITE);
            $flexshare->set_file_create_mask($domain, Array('owner'=>6, 'group'=>6, 'world'=>4));
            $flexshare->set_file_enabled($domain, $smb);
            $flexshare->set_file_browseable($domain, 0);

            // Globals
            $flexshare->set_group($domain, $group);
            $flexshare->set_directory($domain, $docroot);
            $flexshare->toggle_share($domain, ($ftp|$smb));

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S                                 //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for checking state of default domain.
     *
     * @return mixed void if state is valid, errmsg otherwise
     */

    function is_default_set()
    {
        clearos_profile(__METHOD__, __LINE__);
        $file = new File(self::PATH_CONFD . "/" . self::FILE_DEFAULT);
        if (!$file->exists()) {
            // Need file class for lang
            $file = new File();
            $filename = self::PATH_CONFD . "/" . self::FILE_DEFAULT;
            return lang('base_exception_file_not_found') . ' (' . $filename . ')', __METHOD__, __LINE__);
        }
    }

    /**
     * Validation routine for domain.
     *
     * @param string $domain domain
     *
     * @return mixed void if domain is valid, errmsg otherwise
     */

    function is_valid_domain($domain)
    {
        // Allow underscores
        if (!preg_match("/^([0-9a-zA-Z\.\-_]+)$/", $domain))
            return lang('web_site_invalid'), __METHOD__, __LINE__);
    }

    /**
     * Validation routine for servername
     *
     * @param string $servername server name
     *
     * @return boolean TRUE if servername is valid
     */

    function is_valid_server_name($servername)
    {
        if (!preg_match("/^[A-Za-z0-9\.\-_]+$/", $servername))
            return lang('web_server_name_invalid'), __METHOD__, __LINE__);
    }

    /**
     * Validation routine for sslstate
     *
     * @param string $sslstate SSL state
     *
     * @return boole TRUE if sslstate is valid
     */

    function is_valid_ssl_state($sslstate)
    {
        if (!is_bool($sslstate))
            return lang('web_sslstate_invalid'), __METHOD__, __LINE__);
    }

    /**
     * Validation routine for docroot.
     *
     * @param string $docroot document root
     *
     * @return boolean TRUE if document root is valid
     */

    function is_valid_doc_root($docroot)
    {
        // Allow underscores
        if (!isset($docroot) || !$docroot || $docroot == '')
            return lang('web_docroot_invalid'), __METHOD__, __LINE__);
        $folder = new Folder($docroot);
        if (! $folder->exists())
            return lang('base_exception_folder_not_found') . ' (' . $docroot . ')', __METHOD__, __LINE__);
    }

}
// vim: syntax=php ts=4
