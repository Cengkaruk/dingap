<?php

/**
 * Flexshare class.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
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

namespace clearos\apps\flexshare;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('flexshare');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\groups\Group_Factory as Group_Factory;
use \clearos\apps\users\User_Factory as User_Factory;

clearos_load_library('groups/Group_Factory');
clearos_load_library('users/User_Factory');

// Classes
//--------

//use \clearos\apps\\Aliases as Aliases;
//use \clearos\apps\\ClearDirectory as ClearDirectory;
//use \clearos\apps\\Ssl as Ssl;
use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Mime as Mime;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\date\Time as Time;
use \clearos\apps\ftp\ProFTPd as ProFTPd;
use \clearos\apps\imap\Cyrus as Cyrus;
use \clearos\apps\ldap\LDAP_Utilities as LDAP_Utilities;
use \clearos\apps\mail_notification\Mail_Notification as Mail_Notification;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\openldap_directory\Group_Driver as Group_Driver;
use \clearos\apps\openldap_directory\User_Driver as User_Driver;
use \clearos\apps\samba\Samba as Samba;
use \clearos\apps\samba\Smbd as Smbd;
use \clearos\apps\smtp\Postfix as Postfix;
use \clearos\apps\web\Httpd as Httpd;

//clearos_load_library('/Aliases');
//clearos_load_library('/ClearDirectory');
//clearos_load_library('/Ssl');
clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Mime');
clearos_load_library('base/Shell');
clearos_load_library('date/Time');
clearos_load_library('ftp/ProFTPd');
clearos_load_library('imap/Cyrus');
clearos_load_library('ldap/LDAP_Utilities');
clearos_load_library('mail_notification/Mail_Notification');
clearos_load_library('network/Hostname');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network');
clearos_load_library('network/Network_Utils');
clearos_load_library('openldap_directory/Group_Driver');
clearos_load_library('openldap_directory/User_Driver');
clearos_load_library('samba/Samba');
clearos_load_library('samba/Smbd');
clearos_load_library('smtp/Postfix');
clearos_load_library('web/Httpd');

// Exceptions
//-----------

//use \clearos\apps\\Duplicate_Exception as Duplicate_Exception;
//use \clearos\apps\\Ssl_Excecution_Exception as Ssl_Excecution_Exception;
use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\flexshare\Flexshare_Not_Found_Exception as Flexshare_Not_Found_Exception;
use \clearos\apps\flexshare\Flexshare_Parameter_Not_Found_Exception as Flexshare_Parameter_Not_Found_Exception;
use \clearos\apps\users\User_Not_Found_Exception as User_Not_Found_Exception;

//clearos_load_library('/Duplicate_Exception');
//clearos_load_library('/Ssl_Excecution_Exception');
clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('flexshare/Flexshare_Not_Found_Exception');
clearos_load_library('flexshare/Flexshare_Parameter_Not_Found_Exception');
clearos_load_library('users/User_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Flexshare class.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
 */

class Flexshare extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const LOG_TAG = 'flexshare';
    const CONSTANT_LOGIN_SHELL = '/sbin/nologin';
    const FILE_CONFIG = '/etc/clearos/flexshare.conf';
    const FILE_SMB_VIRTUAL = 'flexshare.conf';
    const FILE_FSTAB_CONFIG = '/etc/fstab';
    const PATH_ROOT = '/var/flexshare';
    const FILE_INITIALIZED = '/var/clearos/flexshare/initialized';
    const SHARE_PATH = '/var/flexshare/shares';
    const HTTPD_LOG_PATH = '/var/log/httpd';
    const WEB_VIRTUAL_HOST_PATH = '/etc/httpd/conf.d';
    const FTP_VIRTUAL_HOST_PATH = '/etc/proftpd.d';
    const SMB_VIRTUAL_HOST_PATH = '/etc/samba';
    const CMD_VALIDATE_HTTPD = '/usr/sbin/httpd';
    const CMD_VALIDATE_PROFTPD = '/usr/sbin/proftpd';
    const CMD_VALIDATE_SMBD = '/usr/bin/testparm';
    const CMD_MOUNT = "/bin/mount";
    const CMD_UMOUNT = "/bin/umount";
    const CMD_PHP = "/usr/clearos/webconfig/usr/bin/php";
    const CMD_UPDATE_PERMS = "/usr/sbin/updateflexperms";
    const DIR_MAIL_UPLOAD = "email-upload";
    const CONSTANT_USERNAME = 'flexshare';
    const MBOX_HOSTNAME = 'localhost';
    const DEFAULT_PORT_WEB = 80;
    const DEFAULT_PORT_FTP = 2121;
    const DEFAULT_PORT_FTPS = 2123;
    const DEFAULT_SSI_PARAM = 'IncludesNOExec';
    const REGEX_SHARE_DESC = '/^\s*ShareDescription\s*=\s*(.*$)/i';
    const REGEX_SHARE_GROUP = '/^\s*ShareGroup\s*=\s*(.*$)/i';
    const REGEX_SHARE_DIR = '/^\s*ShareDir\s*=\s*(.*$)/i';
    const REGEX_SHARE_CREATED = '/^\s*ShareCreated\s*=\s*(.*$)/i';
    const REGEX_SHARE_ENABLED = '/^\s*ShareEnabled\s*=\s*(.*$)/i';
    const REGEX_OPEN = '/^<Share\s(.*)>$/i';
    const REGEX_CLOSE = '/^<\/Share>$/i';
    const ACCESS_LAN = 0;
    const ACCESS_ALL = 1;
    const POLICY_DONOT_WRITE = 0;
    const POLICY_OVERWRITE = 1;
    const POLICY_BACKUP = 2;
    const SAVE_REQ_CONFIRM = 0;
    const SAVE_AUTO = 1;
    const PERMISSION_NONE = 0;
    const PERMISSION_READ = 1;
    const PERMISSION_WRITE = 2;
    const PERMISSION_WRITE_PLUS = 3;
    const PERMISSION_READ_WRITE = 4;
    const PERMISSION_READ_WRITE_PLUS = 5;
    const DIR_INDEX_LIST = 'index.htm index.html index.php index.php3 default.html index.cgi';
    const EMAIL_SAVE_PATH_ROOT = 0;
    const EMAIL_SAVE_PATH_MAIL = 1;
    const EMAIL_SAVE_PATH_PARSE_SUBJECT = 2;
    const CASE_HTTP = 1;
    const CASE_HTTPS = 2;
    const CASE_CUSTOM_HTTP = 3;
    const CASE_CUSTOM_HTTPS = 4;
    const PREFIX = 'flex-';
    const FTP_PASV_MIN = 65000;
    const FTP_PASV_MAX = 65100;
    const WRITE_WARNING = '
#----------------------------------------------------------------
# WARNING: This file is automatically created by webconfig.
#----------------------------------------------------------------
';

    protected $access = array(
                            self::PERMISSION_NONE => 'PORT QUIT',
                            self::PERMISSION_READ => 'CWD READ DIRS PORT QUIT',
                            self::PERMISSION_WRITE => 'CWD WRITE DIRS PORT QUIT',
                            self::PERMISSION_WRITE_PLUS => 'CWD WRITE DIRS PORT QUIT',
                            self::PERMISSION_READ_WRITE => 'CWD READ WRITE DIRS PORT QUIT',
                            self::PERMISSION_READ_WRITE_PLUS => 'CWD READ WRITE DIRS PORT QUIT'
                        );
    protected $bad_ports = array('81', '82', '83');

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Flexshare constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        //if (!extension_loaded("mysql"))
         //   dl("mysql.so");

        //if (!extension_loaded("imap"))
         //   dl("imap.so");
    }

    /**
     * Returns a list of defined Flexshares.
     *
     * @return array summary of flexshares
     * @throws Engine_Exception
     */

    function get_share_summary()
    {
        clearos_profile(__METHOD__, __LINE__);

        $share = array('WebEnabled' => 0, 'FtpEnabled' => 0, 'FileEnabled' => 0, 'EmailEnabled' => 0);
        $shares = array();

        try {
            $file = new File(self::FILE_CONFIG);

            if (! $file->exists())
                return $shares;

            $lines = $file->get_contents_as_array();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $match = array();

        foreach ($lines as $line) {
            if (preg_match(self::REGEX_OPEN, $line, $match)) {
                $share['Name'] = $match[1];
            } elseif (preg_match(self::REGEX_SHARE_DESC, $line, $match)) {
                $share['Description'] = $match[1];
            } elseif (preg_match(self::REGEX_SHARE_GROUP, $line, $match)) {
                $share['Group'] = $match[1];
            } elseif (preg_match(self::REGEX_SHARE_CREATED, $line, $match)) {
                $share['Created'] = $match[1];
            } elseif (preg_match(self::REGEX_SHARE_ENABLED, $line, $match)) {
                $share['Enabled'] = $match[1];
            } elseif (preg_match("/^\s*ShareDir*\s*=\s*(.*$)/i", $line, $match)) {
                $share['Dir'] = $match[1];
            } elseif (preg_match("/^\s*ShareInternal*\s*=\s*(.*$)/i", $line, $match)) {
                $share['Internal'] = $match[1];
            } elseif (preg_match("/^\s*WebEnabled*\s*=\s*(.*$)/i", $line, $match)) {
                $share['WebEnabled'] = $match[1];
            } elseif (preg_match("/^\s*FtpEnabled*\s*=\s*(.*$)/i", $line, $match)) {
                $share['FtpEnabled'] = $match[1];
            } elseif (preg_match("/^\s*FileEnabled*\s*=\s*(.*$)/i", $line, $match)) {
                $share['FileEnabled'] = $match[1];
            } elseif (preg_match("/^\s*EmailEnabled*\s*=\s*(.*$)/i", $line, $match)) {
                $share['EmailEnabled'] = $match[1];
            } elseif (preg_match("/^\s*WebModified*\s*=\s*(.*$)/i", $line, $match)) {
                $share['WebModified'] = $match[1];
            } elseif (preg_match("/^\s*FtpModified*\s*=\s*(.*$)/i", $line, $match)) {
                $share['FtpModified'] = $match[1];
            } elseif (preg_match("/^\s*FileModified*\s*=\s*(.*$)/i", $line, $match)) {
                $share['FileModified'] = $match[1];
            } elseif (preg_match("/^\s*EmailModified*\s*=\s*(.*$)/i", $line, $match)) {
                $share['EmailModified'] = $match[1];
            } elseif (preg_match(self::REGEX_CLOSE, $line)) {
                $shares[] = $share;
                unset($share);
            }
        }

        return $shares;
    }

    /**
     * Adds a new Flexshare.
     *
     * @param string  $name        flexshare name
     * @param string  $description brief description of the flexshare
     * @param string  $group       group owner of the flexshare
     * @param string  $directory   directory
     * @param boolean $internal    flag indicating if the share is designated internal
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function add_share($name, $description, $group, $directory, $internal = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $name = strtolower($name);

        // if directory = root share path...tack on name
        if ($directory == self::SHARE_PATH)
            $directory .= '/' . $name;

        // Validate
        // --------

        Validation_Exception::is_valid($this->validate_name($name));

        Validation_Exception::is_valid($this->validate_description($description));

        Validation_Exception::is_valid($this->validate_group($group));

        Validation_Exception::is_valid($this->validate_directory($directory));

        // Samba limitations
        //------------------

        $groupobj = new Group_Driver($name);

        if ($groupobj->exists())
            throw new Validation_Exception(lang('flexshare_name_overlaps_with_group'));

        $userobj = new User_Driver($name);

        if ($userobj->exists())
            throw new Validation_Exception(lang('flexshare_name_overlaps_with_username'));

        try {
            $file = new File(self::FILE_CONFIG);

            if (! $file->exists()) {
                $file->create("root", "root", 600);
                $file->add_lines("# Flexshare Configuration");
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Check for non-uniques

        try {
            if (count($file->get_search_results("<Share $name>")) > 0)
                throw new Engine_Exception(FLEXSHARE_LANG_ERRMSG_SHARE_EXISTS, CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            // Create folder (if necessary) and add skeleton
            $folder = new Folder(self::SHARE_PATH . "/$name");

            if (! $folder->exists()) {
                $groupobj = Group_Factory::create($group);

                if ($groupobj->exists())
                    $folder->create(self::CONSTANT_USERNAME, $group, "0775");
                else
                    $folder->create($group, "nobody", "0775");
            }

            $newshare = "<Share $name>\n" .
                        "  ShareDescription=$description\n" .
                        "  ShareGroup=$group\n" .
                        "  ShareCreated=" . time() . "\n" .
                        "  ShareModified=" . time() . "\n" .
                        "  ShareEnabled=0\n" .
                        "  ShareDir=" . self::SHARE_PATH . "/$name\n" .
                        "  ShareInternal=$internal\n" .
                        "</Share>\n"
                        ;
            $file->add_lines($newshare);

            // Now set directory
            $this->set_directory($name, $directory);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Deletes an existing flexshare.
     *
     * @param string  $name       flexshare name
     * @param boolean $delete_dir boolean flag to delete share directory and any files it contains
     *
     * @return void
     * @throws Engine_Exception
     */

    function delete_share($name, $delete_dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($name))                                                                               
            throw new Engine_Exception(lang('flexshare_share') . " - " . lang('base_invalid'), CLEAROS_ERROR);

        // Set directory back to default
        // This will remove any mount points

        try {
            $defaultdir = self::SHARE_PATH . '/' . $name;
            $this->set_directory($name, $defaultdir);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $file = new File(self::FILE_CONFIG);

            if (! $file->exists())
                throw new File_Not_Found_Exception(self::FILE_CONFIG);

            // Backup in case we need to go back to original
            $file->move_to(CLEAROS_TEMP_DIR . "/flexshare.conf.orig");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Create new file in parallel
        try {
            $newfile = new File(self::FILE_CONFIG . ".cctmp", TRUE);

            if ($newfile->exists())
                $newfile->delete();

            $newfile->create("root", "root", '0600');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $lines = $file->get_contents_as_array();
            $found = FALSE;
            $match = array();

            foreach ($lines as $line) {
                if (preg_match(self::REGEX_OPEN, $line, $match) && $match[1] == $name) {
                    $found = TRUE;
                } elseif (preg_match(self::REGEX_CLOSE, $line) && $found) {
                    $found = FALSE;
                    continue;
                }

                if ($found)
                    continue;

                $newfile->add_lines($line);
            }

            $newfile->move_to(self::FILE_CONFIG);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $this->generate_web_flexshares();
            $this->generate_ftp_flexshares();
            $this->generate_file_flexshares();
            $this->generate_email_flexshares();

            try {
                $file->delete();
            } catch (Exception $ignore) {
                // Just log
            }
        } catch (Exception $e) {
            // Any exception here, toggle...well, toggle.
            $file->move_to(self::FILE_CONFIG);
            // We want to throw Ssl_Excecution_Exception to help users on UI
            if (get_class($e) == Ssl_Excecution_Exception)
                throw new Ssl_Excecution_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            else
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // If you get here, it's OK to delete (as required)
        if ($delete_dir) {
            try {
                $folder = new Folder(self::SHARE_PATH . "/$name");
                if ($folder->exists())
                    $folder->delete(TRUE);
            } catch (Exception $e) {
                // Just log
            }
        }
    }

    /**
     * Returns information on a specific flexshare configuration.
     *
     * @param string $name flexshare name
     *
     * @return array information of flexshare
     * @throws Flexshare_Not_Found_Exception, Engine_Exception
     */

    function get_share($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $share = array();

        try {
            $file = new File(self::FILE_CONFIG);

            if (! $file->exists())
                throw new File_Not_Found_Exception(self::FILE_CONFIG, CLEAROS_ERROR);

            $lines = $file->get_contents_as_array();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $found = FALSE;
        $match = array();

        $regex = "/^\s*([[:alpha:]]+)\s*=\s*(.*$)/i";
        foreach ($lines as $line) {
            if (preg_match(self::REGEX_OPEN, $line, $match)) {
                if (trim($match[1]) == trim($name)) {
                    $found = TRUE;
                    $share['Name'] = $match[1];
                } else {
                    continue;
                }
            } elseif ($found && preg_match($regex, $line, $match)) {
                $share[$match[1]] = $match[2];
            } elseif ($found && preg_match(self::REGEX_CLOSE, $line)) {
                break;
            }
        }

        if (!$found)
            throw new Flexshare_Not_Found_Exception($name, CLEAROS_INFO);

        return $share;
    }

    /**
     * Toggles the status of a flexshare.
     *
     * @param string $name   flexshare name
     * @param string $toggle toggle (enable or disable)
     * @param string $force  force re-creation of config files
     *
     * @return void
     * @throws Engine_Exception
     */

    function toggle_share($name, $toggle, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);
            if (! $file->exists())
                throw new Engine_Exception(FILE_LANG_ERRMSG_NOTEXIST . " " . self::FILE_CONFIG, CLEAROS_ERROR);
            else
                $share = $this->get_share($name);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        if ($toggle) {
            if (!$share['WebEnabled'] && !$share['FtpEnabled'] && !$share['FileEnabled'] && !$share['EmailEnabled'])
                throw new Engine_Exception(lang('flexshare_no_access'));
        }

        // Do we need to generates configs again?
        if ($force || $this->get_parameter($name, 'ShareEnabled') != $toggle) {

            // Set flag
            $this->set_parameter($name, 'ShareEnabled', ($toggle ? 1: 0));

            try {
                $this->generate_web_flexshares();
                $this->generate_ftp_flexshares();
                $this->generate_file_flexshares();
                $this->generate_email_flexshares();
            } catch (Exception $e) {
                // Any exception here, toggle...well, toggle.
                if ($toggle)
                    $this->set_parameter($name, 'ShareEnabled', 0);
                else
                    $this->set_parameter($name, 'ShareEnabled', 1);

                // We want to throw Ssl_Excecution_Exception to help users on UI
                if (get_class($e) == Ssl_Excecution_Exception)
                    throw new Ssl_Excecution_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                else
                    throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }
        }

        try {
            $this->_Update_folder_links($name, $this->get_parameter($name, 'ShareDir'));
            $this->_Update_folder_attributes($share['ShareDir'], $share['ShareGroup']);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Returns a list of directory options to map to flexshare.
     *
     * @param string $name the flex share name
     *
     * @return array
     */

    function get_dir_options($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array();

        // Custom
        try {
            $list = preg_split("/\\|/", $this->get_parameter(NULL, 'FlexshareDirCustom'));
            foreach ($list as $custom) {
                list ($desc, $path) = preg_split("/:/", $custom);
                $options[$path] = $desc . ' (' . $path . ")\n";
            }
        } catch (Flexshare_Parameter_Not_Found_Exception $e) {
            // Ignore
        } catch (Engine_Exception $e) {
            // Ignore
        }

        // If $name is NULL, fancy up the path displayed
        if ($name == NULL)
            $name = preg_replace('/ /', '_', strtoupper(lang('flexshare_share_name'))); 

        // Default
        $options[self::SHARE_PATH . ($name == NULL ? '' : '/' . $name)] = lang('base_default') . ' (' . self::SHARE_PATH . '/' . $name . ")\n";
        return $options;
    }

    /**
     * Returns a list of valid web access options for a flexshare.
     *
     * @return array
     */

    function get_web_access_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
           self::ACCESS_LAN => lang('flexshare_access_lan'),
           self::ACCESS_ALL => lang('base_all')
        );

        return $options;
    }

    /**
     * Returns a list of valid FTP permission options for a flexshare.
     *
     * @return array
     */

    function get_ftp_permission_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
           self::PERMISSION_READ => lang('flexshare_read'),
           self::PERMISSION_READ_WRITE => lang('flexshare_read_write'),
           self::PERMISSION_READ_WRITE_PLUS => lang('flexshare_read_write_plus')
        );
        return $options;
    }

    /**
     * Returns a list of valid FTP umask options for a flexshare.
     *
     * @return array
     */

    function get_ftp_umask_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Umask is inverted.
        $options = array(
            7 => "---",
            6 => "--x",
            5 => "-w-",
            4 => "-wx",
            3 => "r--",
            2 => "r-x",
            1 => "rw-",
            0 => "rwx"
        );

        return $options;
    }

    /**
     * Returns a list of valid file permission options for a flexshare.
     *
     * @return array
     */

    function get_file_permission_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            self::PERMISSION_READ => lang('flexshare_read'),
            self::PERMISSION_READ_WRITE => lang('flexshare_read_write')
        );

        return $options;
    }

    /**
     * Returns a list of valid file (Samba) create mask options for a flexshare.
     *
     * @return array
     */

    function get_file_create_mask_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            0 => "---",
            1 => "--x",
            2 => "-w-",
            3 => "-wx",
            4 => "r--",
            5 => "r-x",
            6 => "rw-",
            7 => "rwx",
        );

        return $options;
    }

    /**
     * Returns a list of valid email policy options for a flexshare.
     *
     * @return array
     */

    function get_email_policy_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            self::POLICY_DONOT_WRITE => lang('flexshare_do_not_write'),
            self::POLICY_OVERWRITE => lang('flexshare_overwrite'),
            self::POLICY_BACKUP => lang('flexshare_backup')
        );

        return $options;
    }

    /**
     * Returns a list of valid email policy options for a flexshare.
     *
     * @return array
     */

    function get_email_save_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
           self::SAVE_REQ_CONFIRM => lang('flexshare_save_req_confirm'),
           self::SAVE_AUTO => lang('flexshare_save_auto')
        );

        return $options;
    }

    /**
     * Returns a list of valid email directory options for a flexshare.
     *
     * @param string $name the flex share name
     *
     * @return array
     */

    function get_email_dir_options($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
           self::EMAIL_SAVE_PATH_ROOT => lang('flexshare_root_dir'),
           self::EMAIL_SAVE_PATH_MAIL => lang('flexshare_mail_sub_dir'),
           self::EMAIL_SAVE_PATH_PARSE_SUBJECT => lang('flexshare_parse_subject')
        );
        return $options;
    }

    /**
     * Returns a list of valid save mask options for a flexshare.
     *
     * @return array
     */

    function get_email_save_mask_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->get_file_create_mask_options();
    }

    /**
     * Create the Apache configuration files for the specificed flexshare.
     *
     * @return void
     * @throws Ssl_Excecution_Exception, Engine_Exception
     */

    function generate_web_flexshares()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_library_installed('web/Httpd'))
            return;

        $httpd = new Httpd();
        $vhosts = array();
        $allow_list = "";

        // Create a unique file identifier
        $backup_key = time();

        // Get file listing in Apache vhost dir
        try {
            $folder = new Folder(self::WEB_VIRTUAL_HOST_PATH);
            $vhosts = $folder->get_listing();
            $index = 0;
            foreach ($vhosts as $vhost) {
                // Flexshares are prefixed with 'flex-'.  Find these files
                if (preg_match("/flex-443.ssl|^" . self::PREFIX . ".*vhost$|^" . self::PREFIX . ".*conf$/i", $vhost)) {
                    $vhost_file = new File(self::WEB_VIRTUAL_HOST_PATH . "/" . $vhost);
                    // Backup existing file
                    $vhost_file->move_to(CLEAROS_TEMP_DIR . "/" . "$vhost.$backup_key.bak");
                } else {
                    unset($vhosts[$index]);
                }
                $index++;
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // We'll add this back later if there is an SSL configured
        try {
            $sslfile = new File(Httpd::FILE_SSL);
            if ($sslfile->exists())
                $sslfile->delete_lines("/Include conf.d\/" . self::PREFIX . "443.ssl/i");
        } catch (File_Not_Found_Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            // This may not be fatal
        }

        $shares = $this->get_share_summary();
        // Recreate all virtual configs
        $newlines = array();
        $anon = array();

        for ($index = 0; $index < count($shares); $index++) {
            // Reset our loop variables
            unset($newlines);
            unset($anon);
            $name = $shares[$index]['Name'];
            $share = $this->get_share($name);

            // If not enabled, continue through loop - we're re-creating lines here
            if (! isset($share["ShareEnabled"]) || ! $share["ShareEnabled"])
                continue;

            if (! isset($share["WebEnabled"]) || ! $share["WebEnabled"])
                continue;

            // Need to know which file we'll be writing to.
            // We determine this by port
            // Ie. /etc/httpd/conf.d/flexshare<port>.<appropriate extension>

            // Port
            if ($share['WebOverridePort']) {
                $port = $share['WebPort'];
                $ext = '.conf';
                if ($share['WebReqSsl'])
                    $ssl = '.ssl';
            } else {
                if ($share['WebReqSsl']) {
                    $port = 443;
                    $ext = '.ssl';
                } else {
                    $port = 80;
                    $ext = '.conf';
                }
            }

            // Interface
            $lans = array();

            if ($share['WebAccess'] == self::ACCESS_LAN) {
                try {
                    $ifacemanager = new Iface_Manager();
                    $lans = $ifacemanager->get_lan_networks();
                } catch (Exception $e) {
                    throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                }
            }

            $case = $this->determine_case($port, $share['WebReqSsl']);

            // Create new file in parallel
            try {
                $filename = self::PREFIX . $port . $ssl . $ext;
                $file = new File(self::WEB_VIRTUAL_HOST_PATH . "/" . $filename);
                if (! $file->exists())
                    $vhosts[] = $filename;
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }

            $newlines = array();

            if (! $file->exists()) {
                $newlines[] = self::WRITE_WARNING;
                // Only specify Listen directive for custom ports
                if ($case == self::CASE_CUSTOM_HTTP || $case == self::CASE_CUSTOM_HTTPS)
                    $newlines[] = "Listen *:$port\n";
                if ($case != self::CASE_HTTP)
                    $newlines[] = "NameVirtualHost *:$port\n";
            }

            // cgi-bin Alias must come first.
            if ($share['WebCgi']) {
                $cgifolder = new Folder(self::SHARE_PATH . "/$name/cgi-bin/");
                if (!$cgifolder->exists())
                    $cgifolder->create(self::CONSTANT_USERNAME, self::CONSTANT_USERNAME, "0777");
                $newlines[] = "ScriptAlias /flexshare/$name/cgi-bin/ " . self::SHARE_PATH . "/$name/cgi-bin/";
            }

            $newlines[] = "Alias /flexshare/$name " . self::SHARE_PATH . "/$name\n";
            $newlines[] = "<VirtualHost *:$port>";
            $newlines[] = "\tServerName " . $name . '.' . trim($share['WebServerName']);
            $newlines[] = "\tDocumentRoot " . self::SHARE_PATH . "/$name";

            if ($share['WebCgi'])
                $newlines[] = "\tScriptAlias /cgi-bin/ " . self::SHARE_PATH . "/$name/cgi-bin/";

            // Logging
            $newlines[] = "\tErrorLog " . self::HTTPD_LOG_PATH . "/" .
                trim($share['WebServerName']) . "_error_log";
            $newlines[] = "\tCustomLog " . self::HTTPD_LOG_PATH . "/" .
                trim($share['WebServerName']) . "_access_log common";

            switch ($case) {
                case self::CASE_HTTP:
                    break;

                case self::CASE_HTTPS:
                    // Enable SSL on server it not already
                    $httpd->set_ssl_state((bool)TRUE);
                    // SSL file has to exist now
                    // Add include if req'd
                    try {
                        $sslfile->lookup_line("/Include conf.d\/" . self::PREFIX . "443.ssl/i");
                    } catch (File_No_Match_Exception $e) {
                        $sslfile->add_lines("Include conf.d/" . self::PREFIX . "443.ssl\n");
                    } catch (Exception $e) {
                        throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                    }
                    break;

                case self::CASE_CUSTOM_HTTPS:
                    // Logging
                    $newlines[] = "\tErrorLog " . self::HTTPD_LOG_PATH . "/" .
                                  trim($share['WebServerName']) . "_error_log";
                    $newlines[] = "\tCustomLog " . self::HTTPD_LOG_PATH . "/" .
                                  trim($share['WebServerName']) . "_access_log common";
                    try {
                        $ssl = new Ssl();
                        $certs = $ssl->get_certificates(Ssl::TYPE_CRT);
                        $ssl_found = FALSE;
                        foreach ($certs as $certfile => $cert) {
                            if ($cert['common_name'] == trim($share['WebServerName'])) {
                                // Don't use CA
                                if ($certfile == Ssl::FILE_CA_CRT)
                                    continue;
                                $ssl_found = TRUE;
                                $cert_filename = $certfile;
                                break;
                            }
                        }
                        if (! $ssl_found) {
                            $ssl->set_rsa_key_size(Ssl::DEFAULT_KEY_SIZE);
                            $ssl->set_term(Ssl::TERM_1YEAR);
                            $ssl->set_purpose(Ssl::PURPOSE_SERVER_CUSTOM);
                            $csr_filename = $ssl->create_certificate_request(trim($share['WebServerName']));
                            // Self-sign be default
                            $cert_filename = $ssl->sign_certificate_request($csr_filename);
                        }
                    } catch (Ssl_Excecution_Exception $e) {
                        throw new Ssl_Excecution_Exception(FLEXSHARE_LANG_ERRMSG_SSL_CA_MISSING, CLEAROS_ERROR);
                    } catch (Exception $e) {
                        throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                    }

                    $key = preg_replace("/-cert\\.pem/", "-key.pem", $cert_filename);

                    if (! $httpd->get_ssl_state())
                        $newlines[] = "\n\tLoadModule ssl_module modules/mod_ssl.so\n\n";

                    $newlines[] = "\tSSLEngine on\n" .
                        "\tSSLCertificateFile " . Ssl::DIR_SSL . "/$cert_filename\n" .
                        "\tSSLCertificateKeyFile " . Ssl::DIR_SSL . "/private/$key\n" .
                        "\t# No weak export crypto allowed\n" .
                        "\t# SSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP:+eNULL\n" .
                        "\tSSLCipherSuite ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:!EXP:+eNULL\n" .
                        "\tSetEnvIf User-Agent \".*MSIE.*\" " .
                        "nokeepalive ssl-unclean-shutdown downgrade-1.0 force-response-1.0\n";
                    break;
                case self::CASE_CUSTOM_HTTP:
                    break;
            }

            $newlines[] = "</VirtualHost>\n";

            if ($share['WebCgi']) {
                $newlines[] = "<Directory " . self::SHARE_PATH . "/$name/cgi-bin>";
                $newlines[] = "\tOptions +ExecCGI";
                if ($share["WebAccess"] == self::ACCESS_LAN) {
                    $newlines[] = "\tOrder Deny,Allow";
                    $newlines[] = "\tDeny from all";
                    if (count($lans) > 0) {
                        foreach ($lans as $lan)
                            $allow_list .= "$lan ";
                        $newlines[] = "\tAllow from " . $allow_list;
                    }
                }
                $newlines[] = "</Directory>";
            }

            $newlines[] = "<Directory " . self::SHARE_PATH . "/$name>";
                $options = "";

            if ($share['WebShowIndex'])
                $options .= " +Indexes";
            else
                $options .= " -Indexes";

            if ($share['WebFollowSymLinks'])
                $options .= " +FollowSymLinks";
            else
                $options .= " -FollowSymLinks";

            if ($share['WebAllowSSI'])
                $options .= " +" . self::DEFAULT_SSI_PARAM;
            else
                $options .= " -" . self::DEFAULT_SSI_PARAM;

            if (strlen($options) > 0)
                $newlines[] = "\tOptions" . $options;

            if ($share['WebHtaccessOverride'])
                $newlines[] = "\tAllowOverride All";

            if ($share['WebReqAuth']) {
                $ldap_conf = "ldap://127.0.0.1:389/" . ClearDirectory::GetUsersOu() . "?uid?one?(pcnWebFlag=TRUE)";
                $newlines[] = "\tAuthType Basic";
                $newlines[] = "\tAuthBasicProvider ldap";
                $newlines[] = "\tAuthzLDAPAuthoritative Off";
                $newlines[] = "\tAuthName \"" . $share['WebRealm'] . "\"";
                $newlines[] = "\tAuthLDAPUrl $ldap_conf";

                // Determine if this is a group or a user
                $group = Group_Factory::create($share['ShareGroup']);

                if ($group->exists()) {
                    $newlines[] = "\tRequire ldap-group cn=" .
                        $share['ShareGroup'] . "," . ClearDirectory::GetGroupsOu();
                } else {
                    $user = User_Factory::create($share['ShareGroup']);
                    if ($user->exists()) {
                        // TODO: API should be something like User->GetDn() instead of Ldap->GetDnForUid ?
                        $dn = $user->get_dn_for_uid($share['ShareGroup']);
                        $newlines[] = "\tRequire ldap-dn " . $dn;
                    }
                }
            }

            if ($share["WebAccess"] == self::ACCESS_LAN) {
                $newlines[] = "\tOrder deny,allow";
                $newlines[] = "\tDeny from all";

                if (count($lans) > 0) {
                    foreach ($lans as $lan)
                        $allow_list .= "$lan ";

                    $newlines[] = "\tAllow from " . $allow_list;
                }
            } else {
                $newlines[] = "\tOrder deny,allow";
                $newlines[] = "\tAllow from all";
            }

            try {
                // Default to 4
                $php_handler = 'php-script';
                $shell = new Shell();
                if ($shell->execute(self::CMD_PHP, '-v', FALSE) == 0) {
                    $output = $shell->get_output();
                    if (preg_match("/^PHP (\d+).(\d+).*$/", $output[0], $match)) {
                        // PHP5 ?
                        if ((int)$match[1] == 5)
                            $php_handler = 'php5-script';
                    }
                }
            } catch (Exception $e) {
                $php_handler = 'php-script';
            }

            if ($share['WebPhp']) {
                $newlines[] = "\tAddType text/html php";
                $newlines[] = "\tAddHandler $php_handler php";
            } else {
                $newlines[] = "\tRemoveHandler .php";
                $newlines[] = "\tAddType application/x-httpd-php-source .php";
            }

            // TODO: the FollowSymLinks requirement is annoying
            if ($share['WebReqSsl'] && $share['WebFollowSymLinks']) {
                $newlines[] = "\tRewriteEngine On";
                $newlines[] = "\tRewriteCond %{HTTPS} off";
                $newlines[] = "\tRewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI}";
            }

            // DAV (unsupported)
            $davcheck = self::SHARE_PATH . "/$name/.DAV";
            $davfile = new File($davcheck);
            if ($davfile->exists())
                $newlines[] = "\tDav on";

            $newlines[] = "</Directory>\n\n\n";

            if (! $file->exists())
                $file->create('root', 'root', '0640');

            $file->add_lines(implode("\n", $newlines));
        }

        // Validate httpd configuration before restarting server
        $config_ok = TRUE;

        try {
            $shell = new Shell();
            $exitcode = $shell->execute(self::CMD_VALIDATE_HTTPD, '-t', TRUE);
        } catch (Exception $e) {
            // Backup out of commits
            $config_ok = FALSE;
        }

        if ($exitcode != 0) {
            $config_ok = FALSE;
            $output = $shell->get_output();
            log_message(self::LOG_TAG, "Invalid httpd configuration!");
            // Oops...we generated an invalid conf file
            foreach ($output as $line)
                log_message(self::LOG_TAG, $line);
        }

        foreach ($vhosts as $vhost) {
            // Not a flexshare vhost file
            if (!isset($vhost))
                continue;
            $file = new File(CLEAROS_TEMP_DIR . "/$vhost.$backup_key.bak");
            if (! $file->exists()) {
                // Conf was newly created
                $file = new File(self::WEB_VIRTUAL_HOST_PATH . "/$vhost");
                if (! $config_ok)
                    $file->delete();
                continue;
            }

            if ($config_ok) {
                // Delete backups
                $file->delete();
            } else {
                // Recover backups
                $file->move_to(self::WEB_VIRTUAL_HOST_PATH . "/$vhost");
            }
        }
        if (! $config_ok)
            throw new Engine_Exception(lang('flexshare_config_validation_failed'), CLEAROS_ERROR);

        // Reload web server
        $httpd->reset();
    }

    /**
     * Create the ProFtp configuration files for the specificed flexshare.
     *
     * @return void
     * @throws Ssl_Excecution_Exception Engine_Exception
     */

    function generate_ftp_flexshares()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_library_installed('ftp/ProFTPd'))
            return;

        $confs = array();
        $proftpd = new ProFTPd();

        // Create a unique file identifier
        $backup_key = time();

        // Get file listing in FTP confs dir
        try {
            $folder = new Folder(self::FTP_VIRTUAL_HOST_PATH);
            $confs = $folder->get_listing();
            $index = 0;
            foreach ($confs as $conf) {
                if (preg_match("/^" . self::PREFIX . ".*conf$/i", $conf)) {
                    $conf_file = new File(self::FTP_VIRTUAL_HOST_PATH . "/" . $conf);
                    // Backup existing file
                    $conf_file->move_to(CLEAROS_TEMP_DIR . "/$conf.$backup_key.bak");
                } else {
                    unset($confs[$index]);
                }
                $index++;
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $shares = $this->get_share_summary();

        // Recreate all virtual configs
        for ($index = 0; $index < count($shares); $index++) {

            $newlines = array();
            $anon = array();
            $name = $shares[$index]['Name'];
            $share = $this->get_share($name);
            $append = FALSE;

            // If not enabled, continue through loop - we're re-creating lines here
            if (!isset($share['ShareEnabled']) || !$share['ShareEnabled'])
                continue;
            if (!isset($share['FtpEnabled']) || !$share['FtpEnabled'])
                continue;

            // Add group greeting file
            try {
                // This isn't fatal.  Log and continue on exception
                $file = new File(self::SHARE_PATH . "/$name/.flexshare-group.txt", TRUE);
                if ($file->exists())
                    $file->delete();

                if ($share['FtpGroupGreeting']) {
                    $file->create("root", "root", 644);
                    $file->add_lines($share['FtpGroupGreeting'] . "\n");
                }
            } catch (Exception $e) {
                //
            }

            // Add anonymous greeting file
            try {
                // This isn't fatal.  Log and continue on exception
                $file = new File(self::SHARE_PATH . "/$name/.flexshare-anonymous.txt");
                if ($file->exists())
                    $file->delete();

                if ($share['FtpAnonymousGreeting']) {
                    $file->create(self::CONSTANT_USERNAME, self::CONSTANT_USERNAME, 644);
                    $file->add_lines($share['FtpAnonymousGreeting']);
                }
            } catch (Exception $e) {
                //
            }

            // Need to know which file we'll be writing to.
            // We determine this by port
            // Ie. /etc/proftpd.d/flex-<port>.conf

            // Port
            if ($share['FtpOverridePort']) {
                $port = $share['FtpPort'];
            } else {
                if ($share['FtpReqSsl']) {
                    $port = self::DEFAULT_PORT_FTPS;
                } else {
                    $port = self::DEFAULT_PORT_FTP;
                }
            }

            // Passive mode flag
            $pasv = '';
            if ($share['FtpAllowPassive'])
                $pasv = ' PASV';

            // Overwrite permission
            if ((int)$share['FtpGroupPermission'] == self::PERMISSION_WRITE_PLUS)
                $group_write = 'on';
            else if ((int)$share['FtpGroupPermission'] == self::PERMISSION_READ_WRITE_PLUS)
                $group_write = 'on';
            else
                $group_write = 'off';

            if ((int)$share['FtpAnonymousPermission'] == self::PERMISSION_WRITE_PLUS)
                $anonymous_write = 'on';
            else if ((int)$share['FtpAnonymousPermission'] == self::PERMISSION_READ_WRITE_PLUS)
                $anonymous_write = 'on';
            else
                $anonymous_write = 'off';

            // Create new file in parallel
            try {
                $filename = self::PREFIX . $port . '.conf';
                // Add to confs array in case of failure
                if (!in_array($filename, $confs))
                    $confs[] = $filename;
                $file = new File(self::FTP_VIRTUAL_HOST_PATH . "/" . $filename);
                $tempfile = new File(self::FTP_VIRTUAL_HOST_PATH . "/" . $filename . '.cctmp');
                if ($tempfile->exists())
                    $tempfile->delete();
                $tempfile->create("root", "root", '0640');
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }

            if ($file->exists()) {
                $oldlines = $file->get_contents_as_array();
                $found_start = FALSE;

                $linestoadd = "";
                foreach ($oldlines as $line) {
                    if (preg_match("/^\s*# DNR:Webconfig start - $name$/", $line))
                        $found_start = TRUE;
                    if ($found_start && preg_match("/^\s*# DNR:Webconfig end - $name$/", $line)) {
                        $found_start = FALSE;
                        continue;
                    }
                    if ($found_start)
                        continue;

                    // Look for anonymous
                    if (preg_match("/^\s*<Anonymous " . self::SHARE_PATH . "/>$/", $line))
                        $found_anon = TRUE;
                    if ($found_anon && preg_match("/^\s*</Anonymous>$/", $line)) {
                        $found_anon = FALSE;
                        continue;
                    }

                    if ($found_anon)
                        $anon[] = $line;
                    else
                        $linestoadd .= $line . "\n";
                    // We need to know if we are working on top of another define or not
                    $append = TRUE;
                }
                $tempfile->add_lines($linestoadd);
            }

            try {
                $proftpd_conf = new File(ProFTPd::FILE_CONFIG);
                $proftpd_conf->lookup_line("/Include \/etc\/proftpd.d\/\*.conf/i");
            } catch (File_No_Match_Exception $e) {
                // Need this line to include flexshare confs
                $proftpd_conf->add_lines("Include /etc/proftpd.d/*.conf\n");
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }

            if (! $append) {
                $newlines[] = self::WRITE_WARNING;
                // Note: Syswatch/Proftp will automatically handle IP address changes
                $newlines[] = "<VirtualHost 127.0.0.1>";
                $newlines[] = "\tPort $port";
                $newlines[] = "\tDefaultRoot " . self::SHARE_PATH . "/";
                $newlines[] = "\tRequireValidShell off";
                $newlines[] = "\tPassivePorts " . $share["FtpPassivePortMin"]  . " " . $share["FtpPassivePortMax"];
                // $newlines[] = "\tCapabilitiesEngine on";
                // $newlines[] = "\tCapabilitiesSet +CAP_CHOWN";
                $newlines[] = "";
                $newlines[] = "\t<Limit LOGIN CDUP PWD XPWD LIST PROT$pasv>";
                $newlines[] = "\t\tAllowAll";
                $newlines[] = "\t</Limit>";
                $newlines[] = "\t<Limit ALL>";
                $newlines[] = "\t\tDenyAll";
                $newlines[] = "\t</Limit>";
                $newlines[] = "";
                // FTPS (SSL)
                if ($share['FtpReqSsl']) {
                    // We need an SSL certificate
                    /* TODO Need SSL class
                    try {
                        $ssl = new Ssl();
                        $certs = $ssl->get_certificates(Ssl::TYPE_CRT);
                        $ssl_found = FALSE;

                        foreach ($certs as $myfile => $cert) {
                            if ($cert['common_name'] == trim($share['FtpServerUrl'])) {
                                // Don't use CA
                                if ($myfile == Ssl::FILE_CA_CRT)
                                    continue;

                                $ssl_found = TRUE;
                                $cert_filename = $myfile;
                                break;
                            }
                        }

                        if (! $ssl_found) {
                            $ssl->set_rsa_key_size(Ssl::DEFAULT_KEY_SIZE);
                            $ssl->set_term(Ssl::TERM_1YEAR);
                            $ssl->set_purpose(Ssl::PURPOSE_SERVER_CUSTOM);
                            $csr_filename = $ssl->create_certificate_request(trim($share['FtpServerUrl']));
                            // Self-sign be default
                            $cert_filename = $ssl->sign_certificate_request($csr_filename);
                        }
                    } catch (Ssl_Excecution_Exception $e) {
                        throw new Ssl_Excecution_Exception(FLEXSHARE_LANG_ERRMSG_SSL_CA_MISSING, CLEAROS_ERROR);
                    } catch (Exception $e) {
                        throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                    }

                    $key = preg_replace("/-cert\\.pem/", "-key.pem", $cert_filename);
                    $newlines[] = "\t<IfModule mod_tls.c>";
                    $newlines[] = "\t  TLSEngine on";
                    $newlines[] = "\t  TLSLog /var/log/tls.log";
                    $newlines[] = "\t  TLSOptions NoCertRequest";
                    $newlines[] = "\t  TLSRequired on";
                    $newlines[] = "\t  TLSRSACertificateFile " . Ssl::DIR_SSL . "/" . $cert_filename;
                    $newlines[] = "\t  TLSRSACertificateKeyFile " . Ssl::DIR_SSL . "/private/" . $key;
                    $newlines[] = "\t  TLSCACertificateFile " . Ssl::DIR_SSL . "/" . SsL::FILE_CA_CRT;
                    $newlines[] = "\t  TLSVerifyClient off";
                    $newlines[] = "\t</IfModule>";
                    */
                }
            } else {
                if ($share['FtpAllowPassive']) {
                    $tempfile->replace_lines(
                        "/\sPassivePorts \d+\s+\d+/",
                        "\tPassivePorts " . $share['FtpPassivePortMin']  . " " . $share['FtpPassivePortMax'] . "\n"
                    );
                }
            }

            // Determine if this is a group or a user
            $group = new Group_Driver($share['ShareGroup']);

            if ($group->exists())
                $isgroup = TRUE;
            else
                $isgroup = FALSE;

            // Add flexshare specific directory directives
            $newlines[] = "\t# DNR:Webconfig start - $name";
            $newlines[] = "\t<Directory " . self::SHARE_PATH . "/$name>";
            $newlines[] = "\t\tAllowOverwrite " . $group_write;
            $newlines[] = "\t\tAllowRetrieveRestart on";
            $newlines[] = "\t\tAllowStoreRestart on";
            $newlines[] = "\t\tDisplayChdir .flexshare-group.txt TRUE";
            $newlines[] = "\t\tHideNoAccess on";
            $newlines[] = "\t\tHideFiles (.flexshare)";

            if ($isgroup)
                $newlines[] = "\t\tGroupOwner " . $share["ShareGroup"];

            if (isset($share["FtpReqAuth"]) && $share["FtpReqAuth"]) {
                $newlines[] = "\t\tUmask 0113 0002";

                if (isset($this->access[$share['FtpGroupPermission']]))
                    $newlines[] = "\t\t<Limit " . $this->access[$share['FtpGroupPermission']] . "$pasv>";
                else
                    $newlines[] = "\t\t<Limit " . $this->access[self::PERMISSION_NONE] . "$pasv>";

                if ($isgroup)
                    $newlines[] = "\t\t  AllowGroup " . $share['ShareGroup'];
                else
                    $newlines[] = "\t\t  AllowUser " . $share['ShareGroup'];

                $newlines[] = "\t\t  IgnoreHidden on";
                $newlines[] = "\t\t</Limit>";
                $newlines[] = "\t\t<Limit ALL>";
                $newlines[] = "\t\t  DenyAll";
                $newlines[] = "\t\t</Limit>";
            }

            $newlines[] = "\t</Directory>";
            $newlines[] = "\t# DNR:Webconfig end - $name";
            $newlines[] = "";

            if (!$append)
                $anon[] = "\n\t<Anonymous " . self::SHARE_PATH . "/>";

            // Insert Anonymous as required
            if ($share["FtpAllowAnonymous"]) {
                // If new file is being created or anon array = 1 (that is, it contains the <Anonymous> start tag only
                if (!$append || count($anon) == 1) {
                    $anon[] = "\t\tUser\tflexshare";
                    $anon[] = "\t\tGroup\tflexshare";
                    $anon[] = "\t\tUserAlias\tanonymous flexshare";
                }
                $anon[] = "\t\t# DNR:Webconfig start - $name";
                $anon[] = "\t\t<Directory " . self::SHARE_PATH . "/$name>";
                $anon[] = "\t\tUmask " . $share['FtpAnonymousUmask'];
                $anon[] = "\t\tDisplayChdir .flexshare-anonymous.txt TRUE";
                $anon[] = "\t\tAllowOverwrite " . $anonymous_write;
                $anon[] = "\t\tHideFiles (.flexshare)";
                $anon[] = "\t\t<Limit ALL>\n\t\t  DenyAll\n\t\t</Limit>";

                if (isset($this->access[$share['FtpAnonymousPermission']]))
                    $anon[] = "\t\t<Limit " . $this->access[$share['FtpAnonymousPermission']] . "$pasv>";
                else
                    $anon[] = "\t\t<Limit " . $this->access[self::PERMISSION_NONE] . "$pasv>";

                $anon[] = "\t\t  AllowAll";
                $anon[] = "\t\t</Limit>";
                $anon[] = "\t\t</Directory>";
                $anon[] = "\t\t# DNR:Webconfig end - $name";
            }

            $anon[] = "\t</Anonymous>";

            if ($append) {
                try {
                    $tempfile->delete_lines("/<\/VirtualHost>/");
                    $tempfile->add_lines(implode("\n", $newlines) . "\n" . implode("\n", $anon) . "\n</VirtualHost>\n");
                } catch (Exception $e) {
                    throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                }
            } else {
                try {
                    $tempfile->add_lines(implode("\n", $newlines) . "\n" . implode("\n", $anon) . "\n</VirtualHost>\n");
                } catch (Exception $e) {
                    throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                }
            }

            $tempfile->move_to(self::FTP_VIRTUAL_HOST_PATH . "/" . $filename);
        }

        // Validate proftpd configuration before restarting server
        $config_ok = TRUE;

        try {
            $shell = new Shell();
            $exitcode = $shell->execute(self::CMD_VALIDATE_PROFTPD, '-t', TRUE);
        } catch (Exception $e) {
            $config_ok = FALSE;
        }

        if ($exitcode != 0) {
            $config_ok = FALSE;
            $output = $shell->get_output();
            log_message(self::LOG_TAG, "Invalid ProFTP configuration!");
            foreach ($output as $line)
                log_message(self::LOG_TAG, $line);
        }

        foreach ($confs as $conf) {
            // Not a flexshare conf file
            if (!isset($conf))
                continue;

            $file = new File(CLEAROS_TEMP_DIR . "/$conf.$backup_key.bak");

            if (! $file->exists()) {
                // Conf was newly created
                $file = new File(self::FTP_VIRTUAL_HOST_PATH . "/$conf");

                if (! $config_ok)
                    $file->delete();

                continue;
            }

            if ($config_ok) {
                // Delete backups
                $file->delete();
            } else {
                // Recover backups
                $file->move_to(self::FTP_VIRTUAL_HOST_PATH . "/$conf");
            }
        }

        if (! $config_ok)
            throw new Engine_Exception(lang('flexshare_config_validation_failed'), CLEAROS_ERROR);

        // Reload FTP server
        $proftpd->reset();
    }

    /**
     * Create the Samba configuration files for the specificed flexshare.
     *
     * @return void
     * @throws Engine_Exception
     */

    function generate_file_flexshares()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_library_installed('samba/Smbd'))
            return;

        // Create a unique file identifier
        $backup_key = time();

        try {
            // Backup original file
            $backup = new File(self::SMB_VIRTUAL_HOST_PATH . "/" . self::FILE_SMB_VIRTUAL);
            if ($backup->exists())
                $backup->move_to(CLEAROS_TEMP_DIR . "/$backup_key.bak");

            // Samba is slightly different.  We dump all flexshare-related 'stuff' in one file
            $file = new File(self::SMB_VIRTUAL_HOST_PATH . "/" . self::FILE_SMB_VIRTUAL);
            if ($file->exists())
                $file->delete();

            $file->create("root", "root", '0644');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $samba_conf = new File(Samba::FILE_CONFIG);

            if (! $samba_conf->exists())
                throw new Engine_Exception(lang('base_exception_file_not_found') . ' (' . Samba::FILE_CONFIG . ')');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $shares = $this->get_share_summary();
        $linestoadd = "";

        // Recreate samba flexshare.conf

        for ($index = 0; $index < count($shares); $index++) {
            $name = $shares[$index]['Name'];
            $share = $this->get_share($name);

            // If not enabled, continue through loop - we're re-creating lines here
            if (! isset($share['ShareEnabled']) || ! $share['ShareEnabled'])
                continue;

            if (! isset($share['FileEnabled']) || ! $share['FileEnabled'])
                continue;

            $linestoadd .= "[" . $name . "]\n";
            $linestoadd .= "\tpath = " . $share["ShareDir"] . "\n";
            $linestoadd .= "\tcomment = " . $share["FileComment"] . "\n";

            if ($share["FileBrowseable"])
                $linestoadd .= "\tbrowseable = Yes\n";
            else
                $linestoadd .= "\tbrowseable = No\n";

            if ((int)$share["FilePermission"] == self::PERMISSION_READ_WRITE)
                $linestoadd .= "\tread only = No\n";

            if ($share["FilePublicAccess"]) {
                $linestoadd .= "\tguest ok = Yes\n";
            } else {
                $linestoadd .= "\tguest ok = No\n";
                $linestoadd .= "\tdirectory mask = 775\n";
                $linestoadd .= "\tcreate mask = 664\n";
                // Determine if this is a group or a user
                $group = Group_Factory::create($share['ShareGroup']);

                if ($group->exists()) {
                    $linestoadd .= "\tvalid users = @\"%D" . '\\' . trim($share["ShareGroup"]) . "\"\n";
                } else {
                    $user = User_Factory::create($share['ShareGroup']);
                    if ($user->exists())
                        $linestoadd .= "\tvalid users = \"%D" . '\\' . trim($share["ShareGroup"]) . "\"\n";
                }
            }

            $linestoadd .= "\tveto files = /.flexshare*/\n";

            $vfsobject = "";

            if ($share["FileRecycleBin"]) {
                $vfsobject .= " recycle:recycle";
                $linestoadd .= "\trecycle:repository = .trash/%U\n";
                $linestoadd .= "\trecycle:maxsize = 0\n";
                $linestoadd .= "\trecycle:versions = Yes\n";
                $linestoadd .= "\trecycle:keeptree = Yes\n";
                $linestoadd .= "\trecycle:touch = No\n";
                $linestoadd .= "\trecycle:directory_mode = 0775\n";
            }

            if ($share["FileAuditLog"]) {
                $vfsobject .= " full_audit:audit";
                $linestoadd .= "\taudit:prefix = %u\n";
                $linestoadd .= "\taudit:success = open opendir\n";
                $linestoadd .= "\taudit:failure = all\n";
                $linestoadd .= "\taudit:facility = LOCAL5\n";
                $linestoadd .= "\taudit:priority = NOTICE\n";
            }

            if ($vfsobject)
                $linestoadd .= "\tvfs object =$vfsobject\n";

            $linestoadd .= "\n";
        }

        $file->add_lines($linestoadd);

        // Validate smbd configuration before restarting server
        $config_ok = TRUE;

        try {
            $shell = new Shell();
            $exitcode = $shell->execute(self::CMD_VALIDATE_SMBD, '-s', FALSE);
        } catch (Validation_Exception $e) {
            $config_ok = FALSE;
            log_message(self::LOG_TAG, "Invalid Samba config: " . clearos_exception_message($e));
        }

        if ($config_ok) {
            // Delete backups
            if ($backup->exists())
                $backup->delete();
        } else {
            // Recover backups
            if ($backup->exists()) {
                try {
                    $backup->move_to(self::SMB_VIRTUAL_HOST_PATH . "/" . self::FILE_SMB_VIRTUAL);
                } catch (Exception $e) {
                    // Supresss error here...could be same file
                }

            }
            throw new Engine_Exception(lang('flexshare_config_validation_failed'), CLEAROS_ERROR);
        }

        // A full restart is required to catch file permission changes
        try {
            $smbd = new Smbd();
            $isrunning = $smbd->get_running_state();
            if ($isrunning)
                $smbd->restart();
        } catch (Exception $e) {
            // Not fatal
        }
    }

    /**
     * Create the Postfix aliases configuration file for the specificed flexshare.
     *
     * @return void
     * @throws Engine_Exception
     */

    function generate_email_flexshares()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_library_installed('aliases/Aliases'))
            return;

        $aliases = new Aliases();

        try {
            $shares = $this->get_share_summary();
            for ($index = 0; $index < count($shares); $index++) {
                $name = $shares[$index]['Name'];
                $share = $this->get_share($name);

                if (!isset($share['ShareEnabled'])
                    || !isset($share['EmailEnabled'])
                    || !$share['ShareEnabled']
                    || !$share['EmailEnabled']
                ) {
                    try {
                        $aliases->delete_alias(self::PREFIX . $name);
                    } catch (Exception $e) {
                    }
                } else {
                    try {
                        $aliases->add_alias(self::PREFIX . $name, array(self::CONSTANT_USERNAME));
                    } catch (Duplicate_Exception $e) {
                        // Do nothing
                    } catch (Exception $e) {
                        throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Initializes flexshare environment.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception, User_Not_Found_Exception
     */

    function initialize()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: if (master)

        try {
            $file = new File(self::FILE_INITIALIZED);

            if ($file->exists())
                return;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            // Generate random password
            $password = LDAP_Utilities::generate_password();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Check to see if flexshare user exists

        $adduser = FALSE;

        try {
            $user = User_Factory::create(self::CONSTANT_USERNAME);
            $currentinfo = $user->get_info();
        } catch (User_Not_Found_Exception $e) {
            $adduser = TRUE;
        }

        // Add or update user account information

        try {
            if ($adduser) {
                $userinfo = array();
                $userinfo['password'] = $password;
                $userinfo['verify'] = $password;
                $userinfo['mailFlag'] = TRUE; // Mail-to-flexshare
                $userinfo['ftpFlag'] = TRUE;  // Anonymous FTP
                $userinfo['lastName'] = "System";
                $userinfo['firstName'] = "Flexshare";
                $userinfo['uid'] = self::CONSTANT_USERNAME;
                $userinfo['homeDirectory'] = self::PATH_ROOT;
                $userinfo['loginShell'] = self::CONSTANT_LOGIN_SHELL;

                $user->Add($userinfo);
            } else {
                $userinfo = array();
                $userinfo['password'] = $password;
                $userinfo['verify'] = $password;

                if (! $currentinfo['mailFlag'])
                    $userinfo['mailFlag'] = TRUE;

                if (! $currentinfo['ftpFlag'])
                    $userinfo['ftpFlag'] = TRUE;

                $user->update($userinfo);
            }
        } catch (Exception $e) {
            throw new Engine_Exception("TODO - LDAP Initialization", CLEAROS_ERROR);
            //throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Set the password in flexshare
        // Set the initialized file

        try { 
            $this->set_password($password, $password);
            $file->create("root", "root", "0644");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Convenience function.
     *
     * @param int  $port     port
     * @param bool $ssl_flag flag
     *
     * @return  int case type
     */

    function determine_case ($port, $ssl_flag)
    {
        if ($port == 80)
            $case = self::CASE_HTTP;
        elseif ($port == 443)
            $case = self::CASE_HTTPS;
        elseif ($ssl_flag)
            $case = self::CASE_CUSTOM_HTTPS;
        else
            $case = self::CASE_CUSTOM_HTTP;
        return $case;
    }

    /**
     * Generic set routine.
     *
     * @param string $name  flexshare name
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_parameter($name, $key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Convert carriage returns
        $value = preg_replace("/\n/", "", $value);

        // Update tag if it exists
        try {
            $file = new File(self::FILE_CONFIG);
            if ($name == NULL) {
                $needle = "/^\s*$key\s*=\s*.+/i";
                $match = $file->replace_lines($needle, "$key=$value\n");
            } else {
                $needle = "/^\s*$key\s*=\s*.+/i";
                $match = $file->replace_lines_between($needle, "  $key=$value\n", "/<Share $name>/", "/<\/Share>/");
            }
        } catch (File_No_Match_Exception $e) {
            // Do nothing
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // If tag does not exist, add it
        try {
            if (! $match && $name == NULL)
                $file->add_lines_after("$key=$value\n", "/#*./");
            elseif (! $match)
                $file->add_lines_after("  $key=$value\n", "/<Share $name>/");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Update last modified
        if ($name != NULL) {
            if (preg_match("/^Web/", $key))
                $lastmod = "WebModified";
            else if (preg_match("/^Ftp/", $key))
                $lastmod = "FtpModified";
            else if (preg_match("/^File/", $key))
                $lastmod = "FileModified";
            else if (preg_match("/^Email/", $key))
                $lastmod = "EmailModified";
            else
                return;

            try {
                $mod = "  " . $lastmod . "=" . time() . "\n";
                $file->replace_lines_between("/" . $lastmod . "/", $mod, "/<Share $name>/", "/<\/Share>/");
            } catch (File_No_Match_Exception $e) {
                $file->add_lines_after($mod, "/<Share $name>/");
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }
        }
    }

    /**
     * Sets email-based access.
     *
     * @param string $password password
     * @param string $verify   verify
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_password($password, $verify)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($password != $verify)
            throw new Validation_Exception(FLEXSHARE_LANG_ERRMSG_PASSWORD_MISMATCH);

        Validation_Exception::is_valid($this->validate_password($password));

        try {
            $file = new File(self::FILE_CONFIG);

            if (! $file->exists()) {
                $file->create("root", "root", '0600');
                $file->add_lines("# Flexshare Configuration\n");
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->set_parameter(NULL, 'FlexsharePW', $password);
    }

    /**
     * Sets a flex share's description.
     *
     * @param string $name        flexshare name
     * @param string $description flexshare description
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_description($name, $description)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        Validation_Exception::is_valid($this->validate_description($description));

        $this->set_parameter($name, 'ShareDescription', $description);
    }

    /**
     * Sets a flexshare's group owner.
     *
     * @param string $name  flexshare name
     * @param string $group flexshare group owner
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_group($name, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group($group));

        if ($this->get_parameter($name, 'ShareGroup') == $group)
            return;

        $this->set_parameter($name, 'ShareGroup', $group);
        $enabled = 0;
        if ($this->get_parameter($name, 'ShareEnabled'))
            $enabled = (int)$this->get_parameter($name, 'ShareEnabled');
        $this->toggle_share($name, $enabled, TRUE);
    }

    /**
     * Sets a flex share's root directory.
     *
     * @param string $name      flexshare name
     * @param string $directory flex share directory
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_directory($name, $directory)
    {
        clearos_profile(__METHOD__, __LINE__);

        $directory = trim($directory);
        $defaultdir = self::SHARE_PATH . '/' . $name;

        if (!isset($directory) || !$directory)
            $directory = $defaultdir;

        // Validate
        Validation_Exception::is_valid($this->validate_directory($directory));

        $this->_update_folder_links($name, $directory);

        $this->set_parameter($name, 'ShareDir', $directory);
    }

    ////////////////////
    //     W E B      //
    ////////////////////

    /**
     * Sets the enabled of web-based access.
     *
     * @param string $name    flexshare name
     * @param bool   $enabled web enabled
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_web_enabled($name, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebEnabled', ($enabled ? 1: 0));
        $share = $this->get_share($name);
        // If enabled, check e-mail restricts access
        $prevent = TRUE;
        if ($enabled) {
            if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
                $prevent = FALSE;
            if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
                $prevent = FALSE;
            if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
                $prevent = FALSE;
            if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
                $prevent = FALSE;
            if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
                $prevent = FALSE;
        } else {
            $prevent = FALSE;
        }

        if ($enabled && $prevent) {
            $this->set_parameter($name, 'WebEnabled', 0);
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);
        }

        // Disable entire share if all elements are disabled
        if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled']) {
            $this->set_parameter($name, 'ShareEnabled', 0);
        }

        try {
            $this->generate_web_flexshares();
        } catch (Exception $e) {
            // Any exception here, go back to initial state
            if ($enabled)
                $this->set_parameter($name, 'WebEnabled', 0);
            else
                $this->set_parameter($name, 'WebEnabled', 1);

            // We want to throw Ssl_Excecution_Exception to help users on UI
            if (get_class($e) == Ssl_Excecution_Exception)
                throw new Ssl_Excecution_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            else
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets the server name of web-based access.
     *
     * @param string $name        flexshare name
     * @param string $server_name server name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_web_server_name($name, $server_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        // --------
        Validation_Exception::is_valid($this->validate_web_server_name($server_name));

        $this->set_parameter($name, 'WebServerName', $server_name);
    }

    /**
     * Sets whether to allow an index of files to be displayed in browser.
     *
     * @param string $name       flexshare name
     * @param bool   $show_index boolean flag to determine to show file index
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_show_index($name, $show_index)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebShowIndex', $show_index);
    }

    /**
     * Sets whether to follow sym links.
     *
     * @param string $name            flexshare name
     * @param bool   $follow_symlinks boolean flag to determine to follow sym links
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_follow_sym_links($name, $follow_symlinks)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebFollowSymLinks', $follow_symlinks);
    }

    /**
     * Sets whether to allow server side includes.
     *
     * @param string $name flexshare name
     * @param bool   $ssi  boolean flag to determine whether to allow SSI's
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_allow_ssi($name, $ssi)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebAllowSSI', $ssi);
    }

    /**
     * Sets whether to allow override of options if .htaccess file is found.
     *
     * @param string $name     flexshare name
     * @param bool   $htaccess boolean flag to determine whether to allow htaccess override
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_htaccess_override($name, $htaccess)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebHtaccessOverride', $htaccess);
    }

    /**
     * Sets an override flag to use custom port on the flexshare.
     *
     * @param string $name          flexshare name
     * @param bool   $override_port boolean flag
     * @param int    $port          port
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_web_override_port($name, $override_port, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($override_port && ($port == 80 || $port == 443))
            throw new Engine_Exception(lang('flexshare_non_custom_port'), CLEAROS_ERROR);
        $inuse_ports = array();
        $info = $this->get_share($name);
        $ssl = $info['WebReqSsl'];
        $shares = $this->get_share_summary();
        foreach ($shares as $share) {
            $info = $this->get_share($share['Name']);
            if ($name != $share['Name'] && $ssl != $info['WebReqSsl'])
                $inuse_ports[] = $info['WebPort'];
        }
        if ($override_port && (in_array($port, $this->bad_ports) || in_array($port, $inuse_ports))) {
            throw new Validation_Exception(FLEXSHARE_LANG_ERRMSG_PORT_IN_USE);
        }
        $this->set_parameter($name, 'WebOverridePort', $override_port);
        $this->set_parameter($name, 'WebPort', $port);
    }

    /**
     * Sets the require SSL flag for the flexshare.
     *
     * @param string $name    flexshare name
     * @param bool   $req_ssl boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_req_ssl($name, $req_ssl)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebReqSsl', $req_ssl);
    }

    /**
     * Sets the require authentication flag for the flexshare.
     *
     * @param string $name     flexshare name
     * @param bool   $req_auth boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_req_auth($name, $req_auth)
    {
        clearos_profile(__METHOD__, __LINE__);

        // If no auth required, check e-mail restricts access
        $prevent = TRUE;
        if (!$req_auth) {
            $share = $this->get_share($name);
            if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
                $prevent = FALSE;
            if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
                $prevent = FALSE;
            if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
                $prevent = FALSE;
            if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
                $prevent = FALSE;
            if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
                $prevent = FALSE;
        } else {
            $prevent = FALSE;
        }

        if ($prevent)
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

        $this->set_parameter($name, 'WebReqAuth', $req_auth);
    }

    /**
     * Sets the realm name of web-based access.
     *
     * @param string $name  flexshare name
     * @param bool   $realm a realm name
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_web_realm($name, $realm)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        // --------
        Validation_Exception::is_valid($this->validate_web_realm($realm));

        $this->set_parameter($name, 'WebRealm', $realm);
    }

    /**
     * Sets the access interface for the flexshare.
     *
     * @param string $name   flexshare name
     * @param int    $access Intranet, Internet or Any
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_access($name, $access)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        // --------
        Validation_Exception::is_valid($this->validate_web_access($access));

        // If web access is ALL, check e-mail restricts access
        $prevent = TRUE;
        if ((int)$access == self::ACCESS_LAN) {
            $share = $this->get_share($name);
            if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
                $prevent = FALSE;
            if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
                $prevent = FALSE;
            if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
                $prevent = FALSE;
            if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
                $prevent = FALSE;
            if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
                $prevent = FALSE;
        } else {
            $prevent = FALSE;
        }

        if ($prevent)
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

        $this->set_parameter($name, 'WebAccess', $access);
    }

    /**
     * Sets the groups allowed to access this flexshare.
     *
     * @param string $name   flexshare name
     * @param array  $access group access array
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_group_access($name, $access)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'WebGroupAccess', implode(' ', $access));
    }

    /**
     * Sets parameter allowing PHP executeon on the flexshare.
     *
     * @param string $name    flexshare name
     * @param bool   $web_php PHP enabled or not
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_php($name, $web_php)
    {
        clearos_profile(__METHOD__, __LINE__);

        // If PHP enabled, check e-mail restricts access
        $prevent = TRUE;
        if ($web_php) {
            $share = $this->get_share($name);
            if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
                $prevent = FALSE;
            if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
                $prevent = FALSE;
            if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
                $prevent = FALSE;
            if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
                $prevent = FALSE;
            if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
                $prevent = FALSE;

        } else {
            $prevent = FALSE;
        }

        if ($prevent)
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

        $this->set_parameter($name, 'WebPhp', $web_php);
    }

    /**
     * Sets parameter allowing CGI executeon on the flexshare.
     *
     * @param string $name    flexshare name
     * @param bool   $web_cgi CGI enabled or not
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_web_cgi($name, $web_cgi)
    {
        clearos_profile(__METHOD__, __LINE__);

        // If cgi enabled, check e-mail restricts access
        $prevent = TRUE;
        if ($web_cgi) {
            $share = $this->get_share($name);
            if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
                $prevent = FALSE;
            if (!isset($share['EmailEnabled']) || !$share['EmailEnabled'])
                $prevent = FALSE;
            if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
                $prevent = FALSE;
            if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
                $prevent = FALSE;
            if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
                $prevent = FALSE;
        } else {
            $prevent = FALSE;
        }

        if ($prevent)
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

        $this->set_parameter($name, 'WebCgi', $web_cgi);
    }

    ////////////////////
    //     F T P      //
    ////////////////////

    /**
     * Sets the enabled of ftp-based access.
     *
     * @param string $name    flexshare name
     * @param bool   $enabled ftp enabled
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_enabled($name, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpEnabled', $enabled);
        $share = $this->get_share($name);
        // Disable entire share if all elements are disabled
        if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled']) {
            $this->set_parameter($name, 'ShareEnabled', 0);
        }
        try {
            $this->generate_ftp_flexshares();
        } catch (Exception $e) {
            // Any exception here, go back to initial state
            if ($enabled)
                $this->set_parameter($name, 'FtpEnabled', 0);
            else
                $this->set_parameter($name, 'FtpEnabled', 1);
            // We want to throw Ssl_Excecution_Exception to help users on UI
            if (get_class($e) == Ssl_Excecution_Exception)
                throw new Ssl_Excecution_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            else
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets the server URL of FTP based access.
     *
     * @param string $name       flexshare name
     * @param string $server_url server URL
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_server_url($name, $server_url)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        // --------

        Validation_Exception::is_valid($this->validate_ftp_server_url($server_url));

        $this->set_parameter($name, 'FtpServerUrl', $server_url);
    }

    /**
     * Sets an override flag to use custom port on the flexshare.
     *
     * @param string $name          flexshare name
     * @param bool   $override_port boolean flag
     * @param int    $port          port FTP listens on for this flexshare
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_override_port($name, $override_port, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($override_port && ($port == self::DEFAULT_PORT_FTP || $port == self::DEFAULT_PORT_FTPS))
            throw new Engine_Exception(lang('flexshare_non_custom_port'), CLEAROS_ERROR);

        if ($override_port && ($port == 21 || $port == 990))
            throw new Engine_Exception(lang('flexshare_ftp_cannot_use_default_ports'), CLEAROS_ERROR);

        if ($override_port && $port < 1024)
            throw new Engine_Exception(lang('flexshare_invalid_port'), CLEAROS_ERROR);
        // Find all ports and see if any conflicts with n-1
        if ($override_port) {
            $shares = $this->get_share_summary();
            for ($index = 0; $index < count($shares); $index++) {
                $share = $this->get_share($shares[$index]['Name']);
                if ($share['Name'] != $name) {
                    if ((int)$share["FtpPort"] == ($port - 1)) {
                        throw new Engine_Exception(lang('flexshare_ftp_port_conflict'), CLEAROS_ERROR);
                    } else if (((int)$share["FtpPort"] -1) == $port) {
                        throw new Engine_Exception(lang('flexshare_ftp_port_conflict'), CLEAROS_ERROR);
                    }
                }
            }
        }
        $this->set_parameter($name, 'FtpOverridePort', $override_port);
        $this->set_parameter($name, 'FtpPort', $port);
    }

    /**
     * Sets the allow passive port (PASV) flag for the flexshare.
     *
     * @param string $name          flexshare name
     * @param bool   $allow_passive boolean flag
     * @param int    $port_min      minimum port range
     * @param int    $port_max      maximum port range
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_allow_passive($name, $allow_passive, $port_min = self::FTP_PASV_MIN, $port_max = self::FTP_PASV_MAX)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        // --------

        if ($allow_passive)
            Validation_Exception::is_valid($this->validate_passive_port_range($port_min, $port_max));


        $this->set_parameter($name, 'FtpAllowPassive', $allow_passive);

        if ($allow_passive) {
            $this->set_parameter($name, 'FtpPassivePortMin', $port_min);
            $this->set_parameter($name, 'FtpPassivePortMax', $port_max);
        }
    }

    /**
     * Sets the require SSL flag for the flexshare.
     *
     * @param string $name    flexshare name
     * @param bool   $req_ssl boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_req_ssl($name, $req_ssl)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, "FtpReqSsl", $req_ssl);
    }

    /**
     * Sets the require authentication flag for the flexshare.
     *
     * @param string $name     flexshare name
     * @param bool   $req_auth boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_req_auth($name, $req_auth)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpReqAuth', $req_auth);
    }

    /**
     * Sets the FTP owner.
     *
     * @param string $name  flexshare name
     * @param string $owner owner
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_user_owner($name, $owner)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpUserOwner', $owner);
    }

    /**
     * Sets the greeting message for ftp-based group access.
     *
     * @param string $name     flexshare name
     * @param string $greeting greeting displayed on user login
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_group_greeting($name, $greeting)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpGroupGreeting', $greeting);
    }

    /**
     * Sets the groups allowed to access this flexshare.
     *
     * @param string $name   flexshare name
     * @param array  $access group access array
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_group_access($name, $access)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpGroupAccess', implode(' ', $access));
    }

    /**
     * Sets the groups ownership of this flexshare.
     *
     * @param string $name  flexshare name
     * @param string $owner group owner
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_group_owner($name, $owner)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpGroupOwner', $owner);
    }

    /**
     * Sets the group permission allowed to access this flexshare.
     *
     * @param string $name       flexshare name
     * @param int    $permission read/write permissions extended to useers with group access
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_group_permission($name, $permission)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        // --------

        Validation_Exception::is_valid($this->validate_ftp_group_permission($permission));

        $this->set_parameter($name, 'FtpGroupPermission', $permission);
    }

    /**
     * Sets the group umask for this flexshare.
     *
     * @param string $name  flexshare name
     * @param string $umask umask
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_group_umask($name, $umask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = "0" . (int)$umask['owner'] . "" . (int)$umask['group'] . "" . (int)$umask['world'];
        $this->set_parameter($name, 'FtpGroupUmask', $value);
    }

    /**
     * Sets the greeting message for ftp-based access.
     *
     * @param string $name      flexshare name
     * @param bool   $anonymous allow anonymous login
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_allow_anonymous($name, $anonymous)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpAllowAnonymous', $anonymous);
    }

    /**
     * Sets the anonymous permission allowed to access this flexshare.
     *
     * @param string $name       flexshare name
     * @param int    $permission read/write permissions for anonymous users
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_anonymous_permission($name, $permission)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpAnonymousPermission', $permission);
    }

    /**
     * Sets the greeting message for ftp-based anonymous access.
     *
     * @param string $name     flexshare name
     * @param string $greeting greeting displayed on anonymous login
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_anonymous_greeting($name, $greeting)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FtpAnonymousGreeting', $greeting);
    }

    /**
     * Sets the anonymous umask for this flexshare.
     *
     * @param string $name  flexshare name
     * @param string $umask umask
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_ftp_anonymous_umask($name, $umask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = "0" . (int)$umask['owner'] . "" . (int)$umask['group'] . "" . (int)$umask['world'];
        $this->set_parameter($name, 'FtpAnonymousUmask', $value);
    }

    ////////////////////////////////
    //    F I L E   (S A M B A)   //
    ////////////////////////////////

    /**
     * Sets the audit log state.
     *
     * @param string $name  flexshare name
     * @param bool   $state state of audit logging
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_audit_log($name, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileAuditLog', $state);
    }

    /**
     * Sets the audit log state.
     *
     * @param string $name  flexshare name
     * @param bool   $state state of audit logging
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_browseable($name, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileBrowseable', $state);
    }

    /**
     * Sets the enabled of file-based (SAMBA) access.
     *
     * @param string $name    flexshare name
     * @param bool   $enabled file enabled
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_enabled($name, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileEnabled', $enabled);
        $share = $this->get_share($name);

        // Disable entire share if all elements are disabled
        if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled'])
            $this->set_parameter($name, 'ShareEnabled', 0);

        try {
            $this->generate_file_flexshares();
        } catch (Exception $e) {
            // Any exception here, go back to initial state
            if ($enabled)
                $this->set_parameter($name, 'FileEnabled', 0);
            else
                $this->set_parameter($name, 'FileEnabled', 1);

            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Sets file sharing comment for the flexshare.
     *
     * @param string $name    flexshare name
     * @param string $comment a flexshare/fileshare comment
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_comment($name, $comment)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileComment', $comment);
    }

    /**
     * Sets file sharing public access flag for the flexshare.
     *
     * @param string $name          flexshare name
     * @param bool   $public_access a boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_public_access($name, $public_access)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FilePublicAccess', $public_access);
    }

    /**
     * Sets the groups ownership of this flexshare.
     *
     * @param string $name  flexshare name
     * @param string $owner group owner
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_group_owner($name, $owner)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileGroupOwner', $owner);
    }

    /**
     * Sets file sharing permissions for the flexshare.
     *
     * @param string $name       flexshare name
     * @param string $permission a valid permission
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_permission($name, $permission)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FilePermission', $permission);
    }

    /**
     * Sets the groups allowed to access this flexshare.
     *
     * @param string $name   flexshare name
     * @param array  $access group access array
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_group_access($name, $access)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileGroupAccess', implode(' ', $access));
    }

    /**
     * Sets the Samba create mask for this flexshare.
     *
     * @param string $name flexshare name
     * @param string $mask mask
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_create_mask($name, $mask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = "0" . (int)$mask['owner'] . "" . (int)$mask['group'] . "" . (int)$mask['world'];
        $this->set_parameter($name, 'FileCreateMask', $value);
    }

    /**
     * Sets the recycle bin state.
     *
     * @param string $name  flexshare name
     * @param bool   $state state of recycle bin option
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_file_recycle_bin($name, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'FileRecycleBin', $state);
    }

    ////////////////////
    //    E M A I L   //
    ////////////////////

    /**
     * Sets email-based access.
     *
     * @param string $name    flexshare name
     * @param bool   $enabled email enabled
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_enabled($name, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailEnabled', $enabled);
        $share = $this->get_share($name);
        // If web access is ALL, check e-mail restricts access
        $prevent = TRUE;
        if ($enabled) {
            if (isset($share['EmailRestrictAccess']) && $share['EmailRestrictAccess'])
                $prevent = FALSE;
            if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
                $prevent = FALSE;
            if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
                $prevent = FALSE;
            if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
                $prevent = FALSE;
            if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
                $prevent = FALSE;
        } else {
            $prevent = FALSE;
        }

        if ($prevent)
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);

        // Disable entire share if all elements are disabled
        if (! $share['WebEnabled'] && ! $share['FtpEnabled'] && ! $share['FileEnabled'] && ! $share['EmailEnabled']) {
            $this->set_parameter($name, 'ShareEnabled', 0);
        }

        try {
            $this->generate_email_flexshares();
        } catch (Exception $e) {
            // Any exception here, go back to initial state
            if ($enabled)
                $this->set_parameter($name, 'EmailEnabled', 0);
            else
                $this->set_parameter($name, 'EmailEnabled', 1);
            // We want to throw Ssl_Excecution_Exception to help users on UI
            if (get_class($e) == Ssl_Excecution_Exception)
                throw new Ssl_Excecution_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            else
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

    }

    /**
     * Sets email access status for the flexshare.
     *
     * @param string $name   flexshare name
     * @param bool   $policy policy flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_policy($name, $policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailPolicy', $policy);
    }

    /**
     * Sets the save policy for the flexshare.
     *
     * @param string $name flexshare name
     * @param int    $save save policy
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_save($name, $save)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailSave', $save);
    }

    /**
     * Sets the require signature flag for the flexshare.
     *
     * @param string $name          flexshare name
     * @param bool   $req_signature boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_req_signature($name, $req_signature)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailReqSignature', $req_signature);
    }

    /**
     * Sets the groups ownership for this flexshare.
     *
     * @param string $name  flexshare name
     * @param array  $owner group owner
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_group_owner($name, $owner)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailGroupOwner', $owner);
    }

    /**
     * Sets the groups allowed to access this flexshare.
     *
     * @param string $name   flexshare name
     * @param array  $access group access array
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_group_access($name, $access)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailGroupAccess', implode(' ', $access));
    }

    /**
     * Sets the groups allowed to access this flexshare.
     *
     * @param string $name flexshare name
     * @param array  $acl  access control list
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_acl($name, $acl)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation

        foreach ($acl as $email)
            Validation_Exception::is_valid($this->validate_email_address($email));

        $this->set_parameter($name, 'EmailAcl', implode(' ', $acl));
    }

    /**
     * Sets the restrict access flag for the flexshare.
     *
     * @param string $name     flexshare name
     * @param bool   $restrict boolean flag
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_restrict_access($name, $restrict)
    {
        clearos_profile(__METHOD__, __LINE__);

        // If no restrictions, check that user is not running a wide open web server with PHP/cgi
        $prevent = TRUE;
        if (!$restrict) {
            $share = $this->get_share($name);
            if (!isset($share['WebEnabled']) || !$share['WebEnabled'])
                $prevent = FALSE;
            if ((!isset($share['WebPhp']) || !$share['WebPhp']) && (!isset($share['WebCgi']) || !$share['WebCgi']))
                $prevent = FALSE;
            if (isset($share['WebReqAuth']) && $share['WebReqAuth'])
                $prevent = FALSE;
            if (isset($share['WebAccess']) && (int)$share['WebAccess'] == self::ACCESS_LAN)
                $prevent = FALSE;
        } else {
            $prevent = FALSE;
        }

        if ($prevent)
            throw new Engine_Exception(FLEXSHARE_LANG_WARNING_CONFIG, COMMON_WARNING);
        $this->set_parameter($name, 'EmailRestrictAccess', $restrict);
    }

    /**
     * Sets email-based default directory.
     *
     * @param string $name flexshare name
     * @param string $dir  root directory path
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_dir($name, $dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailDir', $dir);
    }

    /**
     * Sets email notification service.
     *
     * @param string $name   flexshare name
     * @param string $notify notify email
     *
     * @return  void
     * @throws Engine_Exception
     */

    function set_email_notify($name, $notify)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_parameter($name, 'EmailNotify', $notify);
    }

    /**
     * Sets the save mask for this flexshare.
     *
     * @param string $name flexshare name
     * @param string $mask mask
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_email_save_mask($name, $mask)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = "0" . (int)$mask['owner'] . "" . (int)$mask['group'] . "" . (int)$mask['world'];
        $this->set_parameter($name, 'EmailSaveMask', $value);
    }

    /////////////////////////////////
    //    G E T   M E T H O D S    //
    /////////////////////////////////

    /**
     * Generic get routine.
     *
     * @param string $name flexshare name
     * @param string $key  key name
     *
     * @return string
     *
     * @throws Engine_Exception, File_Not_Found_Exception, Flexshare_Parameter_Not_Found_Exception
     */

    function get_parameter($name, $key)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);

            if ($name == NULL)
                $retval = $file->lookup_value("/^\s*$key\s*=\s*/i");
            else
                $retval = $file->lookup_value_between("/^\s*$key\s*=\s*/i", "/<Share $name>/", "/<\/Share>/");
        } catch (File_Not_Found_Exception $e) {
            throw new File_Not_Found_Exception($file->get_filename(), CLEAROS_ERROR);
        } catch (File_No_Match_Exception $e) {
            throw new Flexshare_Parameter_Not_Found_Exception($key, CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        return $retval;
    }

    /**
     * Gets the global password.
     *
     * @return string
     *
     * @throws Engine_Exception
     */

    function get_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $passwd = $this->get_parameter(NULL, 'FlexsharePW');

        return $passwd;
    }

    /**
     * Gets email address for this share for email based access.
     *
     * @param string $name flexshare name
     *
     * @return string
     *
     * @throws Engine_Exception
     */

    function get_email_address($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if (!is_library_installed('smtp/Postfix'))
                return;

            $postfix = new Postfix();
            $email = self::PREFIX . $name . "@" . $postfix->get_domain();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        return $email;
    }

    /**
     * Check mail POP accounts for flexshares.
     *
     * @param boolean $view   a boolean flag indicating access to this method via webconfig (ie. user)
     * @param array   $action array containing message IDs and actions to do on them (delete, save etc.)
     *
     * @return array containing limited information on emails in queue
     *
     * @throws Engine_Exception
     */

    function check_messages($view = FALSE, $action = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_library_installed('smtp/Postfix'))
            return;
        if (!is_library_installed('imap/Cyrus'))
            return;

        $req_check = FALSE;

        $msg = array();
        // Set an empty array if NULL
        if ($action == NULL || ! $action)
            $action = array();
        $shares = $this->get_share_summary();
        // For convenience, we setup array containing required info for "FetchEmail" function
        for ($index = 0; $index < count($shares); $index++) {
            $share = $this->get_share($shares[$index]['Name']);
            $share['EmailGroupOwner'] = $share['ShareGroup'];
            $share['EmailGroupAccess'] = $share['ShareGroup'];
            if (!isset($share['EmailDir'])) $share['EmailDir'] = 0;
            if (!isset($share['EmailRestrictAccess'])) $share['EmailRestrictAccess'] = 0;
            if (!isset($share['EmailReqSignature'])) $share['EmailReqSignature'] = 0;
            if (!isset($share['EmailAcl'])) $share['EmailAcl'] = 0;
            if (!isset($share['EmailPolicy'])) $share['EmailPolicy'] = 0;
            if (!isset($share['EmailSave'])) $share['EmailSave'] = 0;
            if (!isset($share['EmailSaveMask'])) $share['EmailSaveMask'] = 0;
            if (!isset($share['EmailNotify'])) $share['EmailNotify'] = 0;
            $newarray[$shares[$index]['Name']]['dir'] = $share['EmailDir'];
            $newarray[$shares[$index]['Name']]['restrict_access'] = $share['EmailRestrictAccess'];
            $newarray[$shares[$index]['Name']]['req_signature'] = $share['EmailReqSignature'];
            $newarray[$shares[$index]['Name']]['group_owner'] = $share['EmailGroupOwner'];
            $newarray[$shares[$index]['Name']]['group_access'] = $share['EmailGroupAccess'];
            $newarray[$shares[$index]['Name']]['acl'] = $share['EmailAcl'];
            $newarray[$shares[$index]['Name']]['policy'] = $share['EmailPolicy'];
            $newarray[$shares[$index]['Name']]['save'] = $share['EmailSave'];
            $newarray[$shares[$index]['Name']]['mask'] = $share['EmailSaveMask'];
            $newarray[$shares[$index]['Name']]['notify'] = $share['EmailNotify'];

            // Enabled?
            if (isset($share['ShareEnabled'])
                && $share['ShareEnabled']
                && isset($share['EmailEnabled'])
                && $share['EmailEnabled']
            )
                $req_check = TRUE;
        }
echo 'fuck' . "\n";

echo 'fuck';
        // May not need to fetch mail
        if (! $req_check)
            return $msg;

        // This may take a while...don't timeout.
        set_time_limit(0);
        $msg = $this->fetch_email($view, $action, $newarray);
        set_time_limit(30);
        return $msg;
    }

    /**
     * Check mail POP accounts for flexshares.
     *
     * @param bool  $view   a boolean flag indicating access to this method via webconfig (ie. user)
     * @param array $action array containing message IDs and actions to do on them (delete, save etc.)
     * @param array $shares flexshares
     *
     * @return array containing message information
     *
     * @throw Engine_Exception
     */

    function fetch_email($view, $action, $shares)
    {
        clearos_profile(__METHOD__, __LINE__);

        $input = '';
        $mymessage = array();
        $files = array();
        $mailing_list = array();
        if (!is_library_installed('smtp/Postfix'))
            return;
        if (!is_library_installed('imap/Cyrus'))
            return;

echo 'shit';
        // Get password for mailserver authentication
        $passwd = $this->get_parameter(NULL, 'FlexsharePW');

        $cyrus = new Cyrus();
        $mbox = @imap_open("{" . self::MBOX_HOSTNAME . ":143/notls}INBOX", self::CONSTANT_USERNAME, $passwd);
        $ignore = imap_errors();

        if (! $mbox)
            return;

        for ($index = 1; $index <= imap_num_msg($mbox); $index++) {
            $encrypted = FALSE;
            $headers = imap_headerinfo($mbox, $index);
            unset($files);
            $flex_address = NULL;
            $addresses = array();
            if (is_array($headers->to))
                $addresses = $headers->to;
            if (is_array($headers->cc))
                $addresses = array_merge($addresses, $headers->cc);
            foreach ($addresses as $address) {
                if (preg_match("/^" . self::PREFIX . "(.*)$/", $address->mailbox, $match)) {
                    $flex_address = $match[1];
                    break;
                }
            }

            // See if we have a matching flexshare defined
            if ($flex_address == NULL || ! isset($shares[$flex_address])) {
                log_message(self::LOG_TAG, "Flexshare does not exist...Subject: " . $headers->subject);
                imap_delete($mbox, $index);
                unset($mymessage[$index]);
                continue;
            }
            $mymessage[$index]['Share'] = $flex_address;
            $mymessage[$index]['From'] = $headers->fromaddress;
            $mymessage[$index]['Reply-To'] = $headers->reply_toaddress;
            $mymessage[$index]['Date'] = $headers->udate;
            $mymessage[$index]['Subject'] = $headers->subject;
            $mymessage[$index]['Size'] = $headers->Size;
            // Set username for file permissions
            $username = $headers->from[0]->mailbox;

            $rawheader = explode("\n", imap_fetchheader($mbox, $index));

            if (is_array($rawheader) && count($rawheader)) {
                $head = array();
                $arg = array();
                foreach ($rawheader as $line) {
                    preg_match("/^([^:]*): (.*)/", $line, $arg);
                    $head[$arg[1]] = $arg[2];
                }
            }
            if (isset($head['Content-Type']) && preg_match('/x-pkcs7-mime/', $head['Content-Type']))
                $encrypted = TRUE;
            // Set encrypted flag for message summary
            $mymessage[$index]['Ssl'] = $encrypted;

            // User deleted...no use continuing
            if (isset($action[$index]) && $action[$index] == 'delete') {
                log_message(self::LOG_TAG, "User initiated deletion...Subject: " . $headers->subject);
                imap_delete($mbox, $index);
                unset($mymessage[$index]);
                continue;
            }

            // See if restricted access is enabled
            if ($shares[$flex_address]['restrict_access']) {
                $msg = tempnam("/tmp", self::PREFIX);
                $file = new File($msg);
                if ($file->exists())
                    $file->delete();
                $file->create('webconfig', 'webconfig', "0600");
                $file->dump_contents_from_array($rawheader);
                $file->add_lines(imap_body($mbox, $index));
                $ssl = new Ssl();
                if ($shares[$flex_address]['req_signature']) {
                    // Verify signature
                    if (! $ssl->verify_smime($file->get_filename())) {
                        $file->delete();
                        imap_delete($mbox, $index);
                        unset($mymessage[$index]);
                        continue;
                    }
                }
                $file->delete();
                // Check ACL
                $acl = array();
                $postfix = new Postfix();
                $hostname = $postfix->get_hostname();
                // Get users in groups
                $groups = explode(" ", $shares[$flex_address]['group_access']);
                $additional = explode(" ", $shares[$flex_address]['acl']);
                $acl = array();
                foreach ($groups as $group_name) {
                    $group = new Group($group_name);
                    $members = $group->get_members();
                    foreach ($members as $user) {
                        $acl[] = "$user@$hostname";
                    }
                    $acl = array_merge($acl, $additional);
                }
                if (! in_array($headers->from[0]->mailbox . "@" . $headers->from[0]->host, $acl)) {
                    log_message(self::LOG_TAG, "ACL restricts attachments...Subject: " . $headers->subject);
                    imap_delete($mbox, $index);
                    unset($mymessage[$index]);
                    continue;
                }
            }

            $mask = 664;

            // Determine directory to save files to - Use /flex-upload as default
            $dir = self::SHARE_PATH . "/$flex_address/" . self::DIR_MAIL_UPLOAD . '/';
            $match = array();

            if ((int)$shares[$flex_address]['dir'] == self::EMAIL_SAVE_PATH_ROOT) {
                $dir = self::SHARE_PATH . "/" . $flex_address;
            } else if ((int)$shares[$flex_address]['dir'] == self::EMAIL_SAVE_PATH_PARSE_SUBJECT) {
                $regex = "/^Dir\s*=\s*([A-Za-z0-9\-\_\/\. ]+$)/i";
                if (preg_match($regex, $mymessage[$index]['Subject'], $match)) {
                    $dir = self::SHARE_PATH . "/" . $flex_address . "/" . $match[1];
                }
            }

            $folder = new Folder($dir, TRUE);
            // Make sure directory exists
            if (! $folder->exists())
                $folder->create(self::CONSTANT_USERNAME, self::CONSTANT_USERNAME, "0775");
            // Last check...make sure we are at least in /var/flexshare/shares/<name>
            $regex = "/" . preg_replace("/\//", "\\/", self::SHARE_PATH . "/" . $flex_address) . "/";
            if (! preg_match($regex, $folder->get_foldername())) {
                // We're no longer in share directory filesystem...override with mail dir
                $folder = new Folder(self::SHARE_PATH . "/" . $flex_address . "/" . self::DIR_MAIL_UPLOAD . "/", TRUE);
            }


            $structure = imap_fetchstructure($mbox, $index);
            $mime = new Mime();
            $parts = $mime->get_parts($structure);

            if (! $view && ! $shares[$flex_address]['save']) {
                // CRON is calling us
                // Ignore files called by cron and received longer than 5 minutes ago
                // Can't use flags, since POP does not support \\SEEN.
                if (((int)$mymessage[$index]['Date'] + 5*60) > time()) {
                    $summary = array(
                       'Share' => $mymessage[$index]['Share'],
                       'Subject' => $mymessage[$index]['Subject'],
                       'From' => $mymessage[$index]['From'],
                       'Reply-To' => $mymessage[$index]['Reply-To'],
                       'Date' => $mymessage[$index]['Date'],
                       'Size' => $this->get_formatted_bytes((int)$mymessage[$index]['Size'], 1)
                    );
                    foreach ($parts as $pid => $part) {
                        $regex = "/^attachment$|^inline$/";
                        if (isset($part['disposition']) && preg_match($regex, $part['disposition'])) {
                            // Ignore signatures
                            if ($part['type'] == "application/x-pkcs7-signature")
                                continue;
                            $summary['Files'][] = $part['name'] .
                                " (" . $this->get_formatted_bytes((int)$part['size'], 1) . ")";
                        }
                    }
                    $mailing_list[$shares[$flex_address]['notify']][] = $summary;
                }
                continue;
            } else if (isset($action[$index]) && $action[$index] != "save") {
                // User has not click save
                continue;
            }

            // Delete any messages without attachments
            if (count($parts) == 0) {
                $log_from = $mymessage[$index]['Reply-To'];
                unset($mymessage[$index]);
                imap_delete($mbox, $index);
                continue;
            }

            // Check to see if share dir is users' home dir
            if (preg_match("/^\\/home\\/\\(\\.\\*\\$\\)/", $this->get_parameter($flex_address, 'ShareDir'), $match)) {
                $user = User_Factory::create($match[1]);
                if ($user->exists()) {
                    $username = $match[1];
                } else {
                    $user = User_Factory::create($username);
                    if (!$user->exists())
                        $username = self::CONSTANT_USERNAME;
                }
            } else {
                // Check to see if $username is on the filesystem
                $user = User_Factory::create($username);
                // If user does not exist, default to 'flexshare.flexshare' for file permission
                if (!$user->exists())
                    $username = self::CONSTANT_USERNAME;
            }

            // Set group owner
            $groupname = $shares[$flex_address]['group_owner'];
            $group = new Group($groupname);

            foreach ($parts as $pid => $part) {
                // Only save if an attachment
                try {
                    if (isset($part['disposition']) && preg_match("/^attachment$|^inline$/", $part['disposition'])) {
                        // Ignore signatures
                        if ($part['type'] == "application/x-pkcs7-signature")
                            continue;
                        // Check filename exists in attachment
                        if (!isset($part['name']) || $part['name'] == '')
                            continue;
                        $path_and_filename = $dir . "/" . $part['name'];
                        if ($shares[$flex_address]['save'] || (isset($action[$index]) && $action[$index] == "save")) {
                            $file = new File($path_and_filename, TRUE);
                            if (! $file->exists()) {
                                $file->create($username, $groupname, $mask);
                                if ($part['encoding'] == 'base64')
                                    $file->add_lines(base64_decode(imap_fetchbody($mbox, $index, $pid)));
                                else
                                    $file->add_lines(imap_fetchbody($mbox, $index, $pid));
                                $files[] = $part['name'];
                            } else {
                                $policy = (int)$shares[$flex_address]['policy'];
                                switch ($policy) {
                                    case self::POLICY_DONOT_WRITE:
                                        break;
                                    case self::POLICY_OVERWRITE:
                                        $file->delete();
                                        $file->create($username, $groupname, $mask);
                                        if ($part['encoding'] == 'base64')
                                            $file->add_lines(base64_decode(imap_fetchbody($mbox, $index, $pid)));
                                        else
                                            $file->add_lines(imap_fetchbody($mbox, $index, $pid));
                                        $files[] = $part['name'];
                                        break;
                                    case self::POLICY_BACKUP:
                                        $file->move_to($path_and_filename . date(".mdy.His", time()) . ".bak");
                                        $file = new File($path_and_filename, TRUE);
                                        $file->create($username, $groupname, $mask);
                                        if ($part['encoding'] == 'base64')
                                            $file->add_lines(base64_decode(imap_fetchbody($mbox, $index, $pid)));
                                        else
                                            $file->add_lines(imap_fetchbody($mbox, $index, $pid));
                                        $files[] = $part['name'];
                                        break;
                                }
                            }
                            $file->Chmod("0664");
                            log_message(self::LOG_TAG, "Attachment saved as " . $path_and_filename);
                        }
                    }
                } catch (Exception $e) {
                    // TODO log this:
                    //"Error - " . $e->getMessage() . " Subject: " . $headers->subject;
                    continue;
                }
            }
            // Delete messages
            if ($shares[$flex_address]['save'] || (isset($action[$index]) && $action[$index] == "save")) {
                $log_from = $mymessage[$index]['Reply-To'];
                imap_delete($mbox, $index);
                $mymessage[$index]['SavedFiles'] = $files;
            }
        }
        imap_close($mbox, CL_EXPUNGE);
        // Check if notification requires sending
        if (count($mailing_list) > 0) {
            $hostname = new Hostname();
            $mail_notification = new Mail_Notification();
            foreach ($mailing_list as $address=>$emails) {
                $mail_notification->clear();
                $body = FLEXSHARE_LANG_EMAIL_NOTIFICATION_PENDING_APPROVAL . ":\n\n";
                foreach ($emails as $email) {
                    $fields = array(
                                  "Share" => FLEXSHARE_LANG_SHARE,
                                  "Subject" => FLEXSHARE_LANG_SUBJECT,
                                  "From" => LOCALE_LANG_FROM,
                                  "Date" => LOCALE_LANG_DATE,
                                  "Files" => FLEXSHARE_LANG_ATTACHMENTS);
                    foreach ($fields as $field => $display) {
                        if ($field == "Date") {
                            $time = new Time();
                            date_default_timezone_set($time->get_time_zone());

                            $body .= str_pad("$display:", 20, " ", STR_PAD_RIGHT) .
                                        date("F d, Y H:i", $email[$field])."\n";
                        } else if ($field == "Files") {
                            $counter = 1;
                            if (isset($email['Files']) && is_array($email['Files'])) {
                                foreach ($email['Files'] as $filedetails) {
                                    if ($counter == 1)
                                        $body .= str_pad("$display:", 20, " ", STR_PAD_RIGHT) . $filedetails . "\n";
                                    else
                                        $body .= str_pad(" ", 21, " ", STR_PAD_RIGHT) . $filedetails . "\n";
                                    $counter++;
                                }
                            }
                        } else {
                            $body .= str_pad("$display:", 20, " ", STR_PAD_RIGHT) . $email[$field] . "\n";
                        }
                        if ($field == "Files")
                            $body .= "\n";
                    }
                }
                try {
                    $mail_notification->add_recipient($address);
                    $mail_notification->set_subject(FLEXSHARE_LANG_EMAIL_NOTIFICATION_SUBJECT . " - " . $hostname->Get());
                    $mail_notification->set_body($body);
                    $mail_notification->set_sender("flex@" . $hostname->Get());
                    $mail_notification->send();
                } catch (Exception $e) {
                    throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
                }
            }
        }

        return $mymessage;
    }

    /**
     * Formats a value into a human readable byte size.
     *
     * @param string $input the value
     * @param int    $dec   number of decimal places
     *
     * @return string
     */

    function get_formatted_bytes($input, $dec)
    {
        clearos_profile(__METHOD__, __LINE__);

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

    /**
     * Sanity checks the group ownership.
     *
     * Too much command line hacking will leave the group ownership of
     * files out of whack.  This method fixes this common issue.
     *
     * @param string $directory share directory
     * @param string $group     group name
     *
     * @return void
     */

    protected function _update_folder_attributes($directory, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->validate_directory($directory)  || !$this->validate_group($group))
            return;
        
        try {
            $options['background'] = TRUE;

            $shell = new Shell();
            $shell->execute(self::CMD_UPDATE_PERMS, $directory . ' ' . $group, TRUE, $options);
        } catch (Exception $e) {
            // Not fatal
        }
    }

    /**
     * Update folder links
     *
     * @param String $name      Flexshare name
     * @param String $directory Flexshare path
     *
     * @return void
     */

    protected function _update_folder_links($name, $directory)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $defaultdir = self::SHARE_PATH . '/' . $name;

        // Load fstab config
        try {
            $file = new Configuration_File(self::FILE_FSTAB_CONFIG, "split", "\s", 6);
            $config = $file->load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            // Umount any existing
            if ($this->get_parameter($name, 'ShareDir') != $defaultdir) {
                $param = $defaultdir;
                $options['env'] = "LANG=en_US";
                try {
                    $retval = $shell->execute(self::CMD_UMOUNT, $param, TRUE, $options);
                } catch (Validation_Exception $e) {
                    if (!preg_match('/.*not mounted.*/', $e->get_message()))
                        throw new Engine_Exception(lang('flexshare_device_busy'), CLEAROS_ERROR);
                }
            }
            // Mount new share
            if ($directory != $defaultdir && $this->get_parameter($name, 'ShareEnabled')) {
                $param = "--bind '$directory' '$defaultdir'";
                $retval = $shell->execute(self::CMD_MOUNT, $param, TRUE);
                if ($retval != 0) {
                    $output = $shell->get_output();
                    throw new Engine_Exception($shell->get_last_output_line(), CLEAROS_ERROR);
                }
            }
            // Check for entry in fstab
            if (isset($config[$this->get_parameter($name, 'ShareDir')]))
                $file->delete_lines("/^" . preg_quote($this->get_parameter($name, 'ShareDir'), "/") . ".*$/");
            if ($directory != $defaultdir && $this->get_parameter($name, 'ShareEnabled'))
                $file->add_lines($directory . "\t" . $defaultdir . "\tnone\tdefaults,bind\t0 0\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /*
    function __destruct()
    {
        // TODO
        clearos_profile(__METHOD__, __LINE__);

        if ($this->link != NULL)
            mysql_close($this->link);
    }
    */

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for flexshare name.
     *
     * @param string $name flexshare name
     *
     * @return mixed void if name is valid, errmsg otherwise
     */

    function validate_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (!preg_match("/^([A-Za-z0-9\-\.\_]+)$/", $name))
            return lang('flexshare_invalid_name');
    }

    /**
     * Validation routine for a group.
     *
     * @param string $group a system group
     *
     * @return mixed void if group is valid, errmsg otherwise
     */

    function validate_group($group)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = Group_Factory::create($group);

        try {
            if (! $group->exists()) {
                $user = User_Factory::create($group);
                if (! $user->exists())
                    return lang('flexshare_invalid_group');
            }
        } catch (Exception $e) {
            return clearos_exception_message($e);
        }
    }

    /**
     * Validation routine for password.
     *
     * @param string $password password
     *
     * @return mixed void if group is valid, errmsg otherwise
     */

    function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: Watch out for auto-generated base64 password
    }

    /**
     * Validation routine for description.
     *
     * @param string $description flexshare description
     *
     * @return mixed void if description is valid, errmsg otherwise
     */

    function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([A-Za-z0-9\-\.\_\' ]*)$/", $description))
            return lang('flexshare_invalid_description');
    }

    /**
     * Validation routine for directory path.
     *
     * @param string $dir directory path for flexshare
     *
     * @return mixed void if directory is valid, errmsg otherwise
     */

    function validate_directory($dir)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([A-Za-z0-9\-\.\_\/]+)$/", $dir))
            return lang('flexshare_invalid_dir');
    }

    /**
     * Validation routine for web server name.
     *
     * @param string $server_name web server name
     *
     * @return mixed void if web server name is valid, errmsg otherwise
     */

    function validate_web_server_name($server_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_library_installed('web/Httpd'))
            return;

        $httpd = new Httpd();
        return $httpd->validate_server_name($server_name);
    }

    /**
     * Validation routine for web realm.
     *
     * @param string $realm web realm
     *
     * @return mixed void if web realm is valid, errmsg otherwise
     */

    function validate_web_realm($realm)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([A-Za-z0-9\-\.\_\/\' ]+)$/", $realm))
            return lang('flexshare_invalid_web_realm');
    }

    /**
     * Validation routine for flexshare FTP.
     *
     * @param boolean $status FTP flexshare status
     *
     * @return mixed void if status is valid, errmsg otherwise
     */

    function validate_ftp_enabled($status)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for FTP passive ports.
     *
     * @param int $port_min Port start
     * @param int $port_max Port end
     *
     * @return mixed void if ports are valid, errmsg otherwise
     */

    function validate_passive_port_range($port_min, $port_max)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_port_range($port_min, $port_max))
            return lang('network_lang_port_range_invalid');

        if ($port_min < 1023 || $port_max < 1023)
            return lang('flexshare_passive_port_below_min');
    }

    /**
     * Validation routine for FTP server URL.
     *
     * @param string $server_url FTP server URL
     *
     * @return mixed void if FTP server URL is valid, errmsg otherwise
     */

    function validate_ftp_server_url($server_url)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($server_url))
            return lang('flexshare_invalid_server_url');
    }

    /**
     * Validation routine for flexshare require SSL on FTP.
     *
     * @param boolean $req FTP flexshare require SSL status
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_ftp_req_ssl($req)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for flexshare group permission on FTP.
     *
     * @param boolean $permission FTP flexshare group permission
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_ftp_group_permission($permission)
    {
        clearos_profile(__METHOD__, __LINE__);
        $options = $this->get_ftp_permission_options();
        if (!array_key_exists($permission, $options))
            return lang('flexshare_invalid_permission');
    }

    /**
     * Validation routine for flexshare group greeting on FTP.
     *
     * @param boolean $greeting FTP flexshare group greeting
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_ftp_group_greeting($greeting)
    {
        clearos_profile(__METHOD__, __LINE__);
        // Invalid characters in greeting?
        //if (preg_match("//" $greeting))
        //    return lang('flexshare_invalid_greeting');
    }

    /**
     * Validation routine for flexshare allow anonymous on FTP.
     *
     * @param boolean $allow_anonymous FTP flexshare allow anonymous
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_ftp_allow_anonymous($allow_anonymous)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for flexshare anonymous permission on FTP.
     *
     * @param boolean $permission FTP flexshare anonymous permission
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_ftp_anonymous_permission($permission)
    {
        clearos_profile(__METHOD__, __LINE__);
        $options = $this->get_ftp_permission_options();
        if (!array_key_exists($permission, $options))
            return lang('flexshare_invalid_permission');
    }

    /**
     * Validation routine for flexshare anonymous greeting on FTP.
     *
     * @param boolean $greeting FTP flexshare anonymous greeting
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_ftp_anonymous_greeting($greeting)
    {
        clearos_profile(__METHOD__, __LINE__);
        // Invalid characters in greeting?
        //if (preg_match("//" $greeting))
        //    return lang('flexshare_invalid_greeting');
    }

    /**
     * Validation routine for flexshare web access on Web.
     *
     * @param boolean $accessibility Web access
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_web_access($accessibility)
    {
        clearos_profile(__METHOD__, __LINE__);
        $options = $this->get_web_access_options();
        if (!array_key_exists($accessibility, $options))
            return lang('flexshare_invalid_accessibility');
    }

    /**
     * Validation routine for flexshare file comment.
     *
     * @param string $comment file comment
     *
     * @return mixed void if invalid, errmsg otherwise
     */

    function validate_file_comment($comment)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (FALSE)
            return lang('flexshare_invalid_file_comment');
    }

    /**
     * Validation routine for flexshare email.
     *
     * @param boolean $status Email dropbox flexshare status
     *
     * @return mixed void if status is valid, errmsg otherwise
     */

    function validate_email_enabled($status)
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Validation routine for flexshare email address.
     *
     * @param string $address Email address
     *
     * @return mixed void if address is valid, errmsg otherwise
     */

    function validate_email_address($address)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (! preg_match("/^([a-z0-9_\-\.\$]+)@/", $address))
            return lang('flexshare_email_acl_address_is_invalid');
    }

    /**
     * Validation routine for flexshare notify email address.
     *
     * @param string $address Email address
     *
     * @return mixed void if address is valid, errmsg otherwise
     */

    function validate_email_notify($address)
    {
        clearos_profile(__METHOD__, __LINE__);
        return $this->validate_email_address($address);
    }
}
