<?php

/**
 * Mail_Archive class.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
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

namespace clearos\apps\mail_archive;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('mail_archive');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail_Archive class.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
 */

class Mail_Archive extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    protected $db = NULL;
    protected $config = NULL;
    protected $link = NULL;
    protected $is_loaded = FALSE;
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
    const FILE_AMAVIS_CONFIGLET = '/etc/amavisd/clear-archive.conf';
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
    const ARCHIVE_SIZE10 = 3;
    const ARCHIVE_SIZE100 = 4;
    const ARCHIVE_SIZE1000 = 5;
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
     * Mail_Archive constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!extension_loaded("mysql"))
            dl("mysql.so");

        if (!extension_loaded("imap"))
            dl("imap.so");

        parent::__construct('mail_archive');
    }

    /**
     * Set the archive status.
     *
     * @param boolean $status archive status (enabled/disabled)
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_status($status)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        try {
            $file = new File(self::FILE_AMAVIS_CONFIGLET);
            if ($status) {
                if (!$file->exists) {
                    $file->create("root", "root", "640");
                    $enable_code = array(
                        '$archive_quarantine_to = \'email-archive@localhost\'',
                        '$archive_quarantine_method = \'smtp:[127.0.0.1]:10026\';'
                    );
                    $file->add_lines($enable_code);
                }
            } else {
                if ($file->exists)
                    $file->delete();
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Set the timestamp of the last successful archive.
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_last_successful_archive()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        // Set default timezone
        $time = new Time();
        date_default_timezone_set($time->get_time_zone());
        $this->_set_parameter('last_archive', date("Y-m-d"));
    }

    /**
     * Set the global attachment policy.
     *
     * @param int $attach policy
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_attachment_policy($attach)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if ((int)$attach < 0 || (int)$attach > 5)
            throw new Validation_Exception(lang('mail_archive_attach_policy_invalid'), CLEAROS_ERROR);

        $this->_set_parameter('attachment', $attach);
    }

    /**
     * Set the sender attachment policy by domain.
     *
     * @param array $attach policy
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_sender_attachment_policy($attach)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if ((int)$attach < 0 || (int)$attach > 5)
            throw new Validation_Exception(lang('mail_archive_attach_policy_invalid'), CLEAROS_ERROR);

        foreach ($attach as $domain => $policy) {
            if ((int)$policy < 0 || (int)$policy > 5)
                throw new Validation_Exception(lang('mail_archive_attach_policy_invalid'), CLEAROS_ERROR);
            $this->_set_parameter("attachment-sender[" . $domain . "]", $policy);
        }
    }

    /**
     * Set the auto archive policy.
     *
     * @param int $auto policy
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_auto_archive_policy($auto)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if ((int)$auto < 0 || (int)$auto > 8)
            throw new Validation_Exception(lang('mail_archive_auto_policy_invalid'), CLEAROS_ERROR);

        $this->_set_parameter('auto-archive', $auto);
    }

    /**
     * Set the archive encryption flag.
     *
     * @param boolean $encrypt archive encryption use (enabled/disabled)
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_archive_encryption($encrypt)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($encrypt)
            $encrypt = 1;
        else
            $encrypt = 0;

        $this->_set_parameter('archive-encryption', $encrypt);
    }

    /**
     * Set the archive mailbox password.
     *
     * @param string $password a strong password
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_mailbox_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        $this->_set_parameter('mbox-password', base64_encode($password));
    }

    /**
     * Set the archive encryption password.
     *
     * @param string $password a strong password
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    function set_encryption_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if (!$this->is_valid_password($password)) {
            $errors = $this->get_validation_Errors();
            throw new Validation_Exception($errors[0]);
        }

        $this->_set_parameter('encrypt-password', base64_encode($password));
    }

    /** Archives any messages in the mailbox to the database.
     *
     * @throws Engine_Exception
     * @return void
     */

    function archive_messages()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        // Lower priority
        proc_nice(99);
        $this->_archive_messages();
    }

    /**
     * Returns archive email address.
     *
     * @return String
     * @throws Engine_Exception
     */

    function get_archive_address()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        // Default
        $email = self::SYS_USER . '@' . self::MBOX_HOSTNAME;

        // Determine whether to override
        if (isset($this->config['archive_email_override'])) {
            $mailer = new Mail_Notification();
            if ($mailer->is_valid_email($this->config['archive_email_override']))
                $email = $this->config['archive_email_override'];
        }
        return $email;
    }

    /**
     * Returns condition of performing IMAP check (sanity check).
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function check_imap_status()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['archive_status_override']) && $this->config['archive_status_override'] == 1) 
            return FALSE;

        list($user, $hostname) = explode('@', $this->get_archive_address());

        // Check if local service
        if ($hostname != 'localhost')
            return FALSE;

        return TRUE;
    }

    /**
     * Returns status of archive.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function get_status()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['archive_status_override']) && $this->config['archive_status_override'] == 1) 
            return TRUE;

        try {
            $file = new File(self::FILE_AMAVIS_CONFIGLET);
            return $file->exists();
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns policy.
     *
     * @return string policy
     * @throws Engine_Exception
     */

    function get_policy()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config["policy"];
    }

    /**
     * Returns global attachment policy.
     *
     * @return int attachment
     * @throws Engine_Exception
     */

    function get_attachment_policy()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config["attachment"];
    }

    /**
     * Returns sender attachment policy.
     *
     * @param string $domain the domain
     *
     * @return array attachment
     *
     * @throws Engine_Exception
     */

    function get_sender_attachment_policy($domain)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config["attachment-sender[$domain]"];
    }

    /**
     * Returns auto archive policy.
     *
     * @return int archive
     * @throws Engine_Exception
     */

    function get_auto_archive_policy()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config["auto-archive"];
    }

    /**
     * Returns encryption flag.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    function get_archive_encryption()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config["archive-encryption"];
    }

    /**
     * Returns encryption password.
     *
     * @return string  the encryption password
     * @throws Engine_Exception
     */

    function get_encryption_password()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        return base64_decode($this->config["encrypt-password"]);
    }

    /**
     * Returns database password.
     *
     * @return string  the database password
     * @throws Engine_Exception
     */

    function get_database_password()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (is_NULL($this->db))
            $this->_load_db_config();

        return $this->db["archive.password"];
    }

    /** Returns an array containing parts of an email.
     *
     * @param string $db_name database name to connect to
     * @param int    $id      the message ID
     *
     * @return array an array
     *
     * @throws Engine_Exception
     */

    function get_archived_email($db_name, $id)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        return $this->_sql_get_archived_email($db_name, $id);
    }

    /** Returns an estimate of the current archival.
     *
     * @return array an associative array
     * @throws Engine_Exception
     */

    function get_current_stats()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $stats = $this->_get_stats(self::DB_NAME_CURRENT);

        return $stats;
    }

    /** Returns an estimate of the search archival.
     *
     * @return array an associative array
     * @throws Engine_Exception
     */

    function get_search_stats()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $stats = $this->_get_stats(self::DB_NAME_SEARCH);

        return $stats;
    }

    /** Deletes a symbolic link to a search in the Flexshare directory.
     *
     * @param string $filename filename
     *
     * @return void
     * @throws Engine_Exception, File_Not_Found_Exception
     */

    function delete_archive($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Check if sym link exists
        $file = new File(self::DIR_LINKS . "/$filename", TRUE);
        if ($file->is_sym_link() == 0)
            throw new File_Not_Found_Exception($filename, COMMON_INFO);

        $file->delete();
    }

    /**
     * Returns a list of valid archive schedule options.
     *
     * @return array
     */

    function get_archive_schedule_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $options = Array(
            self::ARCHIVE_NEVER => lang('mail_archive_auto_archive_never'),
            self::ARCHIVE_WEEK => lang('mail_archive_auto_archive_week'),
            self::ARCHIVE_MONTH => lang('mail_archive_auto_archive_month'),
            self::ARCHIVE_SIZE10 => lang('mail_archive_auto_archive_size_10'),
            self::ARCHIVE_SIZE100 => lang('mail_archive_auto_archive_size_100'),
            self::ARCHIVE_SIZE1000 => lang('mail_archive_auto_archive_size_1000')
        );
        return $options;
    }

    /**
     * Returns a list of valid maximum results returned options.
     *
     * @return array
     */

    function get_max_result_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
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
     * @return array
     */

    function get_search_field_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $options = Array(
            self::SEARCH_SUBJECT=>lang('mail_archive_subject'),
            self::SEARCH_FROM=>lang('mail_archive_from'),
            self::SEARCH_BODY=>lang('mail_archive_body'),
            self::SEARCH_DATE=>lang('mail_archive_date'),
            self::SEARCH_TO=>lang('mail_archive_to'),
            self::SEARCH_CC=>lang('mail_archive_cc')
        );
        return $options;
    }

    /**
     * Returns a list of valid search criteria.
     *
     * @return array
     */

    function get_search_criteria_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $options = Array(
            self::CRITERIA_CONTAINS=>lang('mail_archive_contains'),
            self::CRITERIA_IS=>lang('mail_archive_is'),
            self::CRITERIA_BEGINS=>lang('mail_archive_begins_with'),
            self::CRITERIA_ENDS=>lang('mail_archive_ends_with')
        );
        return $options;
    }

    /**
     * Returns a list of valid policy options to deal with attachments.
     *
     * @return array
     */

    function get_archive_attachment_options()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $options = Array(
            self::DISCARD_ATTACH_NEVER=>lang('mail_archive_never'),
            self::DISCARD_ATTACH_ALWAYS=>lang('mail_archive_always'),
            self::DISCARD_ATTACH_1=>lang('mail_archive_discard_1'),
            self::DISCARD_ATTACH_5=>lang('mail_archive_discard_5'),
            self::DISCARD_ATTACH_10=>lang('mail_archive_discard_10'),
            self::DISCARD_ATTACH_25=>lang('mail_archive_discard_25')
        );
        return $options;
    }
            
    /**
     * Search for a message in the database
     *
     * @param string $db_name  database name to connect to
     * @param array  $field    the field to search on
     * @param array  $criteria criteria to search on
     * @param array  $regex    what to search onon
     * @param string $logical  logical expression
     * @param int    $max      maximum number of records to return
     * @param int    $offset   offset to apply on records to return
     *
     * @throws Engine_Exception
     *
     * @return array
     */

    function search($db_name, $field, $criteria, $regex, $logical, $max, $offset)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $search = Array();
        // Filter out arrays with no regex
        for ($index = 0; $index < count($regex); $index++) {
            if ($regex[$index]) {
                $search[] = Array(
                    'field' => $field[$index],
                    'criteria' => $criteria[$index],
                    'regex' => $regex[$index]
                ); 
            }
        }
        if (count($search) == 0)
            throw new Engine_Exception(lang('mail_archive_invalid_query'), CLEAROS_ERROR);
            
        return $this->_sql_search($db_name, $search, $logical, $max, $offset);
    }

    /** Spaws a background process to restore a message.
     *
     * @param string $db_name database name
     * @param array  $ids     array of message IDs
     * @param string $email   email
     *
     * @return void
     * @throws Validation_Exception Engine_Exception
     */

    function spawn_restore_message($db_name, $ids, $email = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {
            $mailer = new Mail_Notification();
            $list = '';
            if (!is_NULL($email)) {
                $addresses = preg_split("/,|;/", $email);
                foreach ($addresses as $recipient) {
                    $parsed_email = $mailer->_parse_email_address($recipient);
                    if (!$mailer->is_valid_email($parsed_email['address']))
                        throw new Validation_Exception(MAILER_LANG_RECIPIENT . " - " . LOCALE_LANG_INVALID);
                    $list .= $parsed_email['name'] . ' <' . $parsed_email['address'] . '>;';
                }
                $list = substr($list, 0, strlen($list) - 1);
            } else {
                $list = NULL;
            }
            $args = $db_name . ' ' . escapeshellarg($ids) . ' ' . escapeshellarg($list);
            $options = array();
            $options['background'] = TRUE;
            $shell = new Shell();
            $retval = $shell->execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_RESEND, $args, TRUE, $options);
            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_ERROR);
            }
        } catch (Validation_Exception $e) {
            throw new Validation_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /** Restores a message - note this function can take a while on large attachments - use spawn_restore_message().
     *
     * @param string $db_name database name
     * @param int    $id      message ID
     * @param string $email   email
     *
     * @return void
     *
     * @throws Engine_Exception, Sql_Exception
     */

    function restore_message($db_name, $id, $email = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $this->_sql_restore_message($db_name, $id, $email);
    }

    /**
     * Returns an array of archive files on the server.
     *
     * @return array a list of archives
     * @throws Engine_Exception
     */

    function get_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $archives = array();

        try {
            $folder = new Folder(self::DIR_LINKS, TRUE);

            if (! $folder->exists())
                throw new Folder_Not_Found_Exception(self::DIR_LINKS, CLEAROS_ERROR);

            $contents = $folder->get_listing();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        if (! $contents)
            return $archives;

        $time = new Time();
        date_default_timezone_set($time->get_time_zone());
        foreach ($contents as $filename) {
            if (! preg_match("/tar.gz$|enc$/", $filename))
                continue;
            $file = new File(self::DIR_LINKS . "/" . $filename, TRUE);

            // OK...it just means the file was deleted between the time of the get_list and now
            if (!$file->exists())
                continue;

            // Hack...check to see of file was just created (i.e. 10 seconds ago or less)
            // If so, ignore it...it is the un-encrypted temporary tar file
            if ($file->last_modified() > (time() -10))
                continue;

            if ($file->is_sym_link() > 0)
                $size = $file->get_size();
            else
                $size = 0;
            $archives[] = Array(
                "filename" => $filename, "modified" => $file->last_modified(),
                "status" => $file->is_sym_link(), "size" => $size) ;
        }

        return array_reverse($archives);
    }

    /**
     * Performs an archive...of the archive.
     *
     * @param string  $filename archive search filename
     * @param boolean $force    force the archival of data (ie. manually initiation)
     * @param boolean $purge    purge current data once archived (used for testing)
     *
     * @return void
     *
     * @throws Engine_Exception, File_Already_Exists_Exception
     */

    public function archive_data($filename, $force = FALSE, $purge = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if service not enabled
        if (!$this->get_status())
            return;

        // load configuration
        if (! $this->is_loaded)
            $this->_load_config();

        // Set default timezone
        $time = new Time();
        date_default_timezone_set($time->get_time_zone());

        $run = FALSE;

        // Get last archive date and skip archiving if run today
        if ($this->config['last_archive'] == date("Y-m-d") && !$force)
            return;

        // Gets status
        $stats = $this->get_current_stats();
        switch ($this->config['auto-archive']) {
            case self::ARCHIVE_NEVER:
                break;
            case self::ARCHIVE_WEEK:
                 // Every Sunday
                if (date("w") == 0)
                    $run = TRUE;
                break;
            case self::ARCHIVE_MONTH:
                // 1st of every month
                if (date("j") == 1)
                    $run = TRUE;
                break;
            case self::ARCHIVE_SIZE10:
                if ($stats["size"] > 10*1024*1024) 
                    $run = TRUE;
                break;
            case self::ARCHIVE_SIZE100:
                if ($stats["size"] > 100*1024*1024) 
                    $run = TRUE;
                break;
            case self::ARCHIVE_SIZE1000:
                if ($stats["size"] > 1*1024*1024*1024) 
                    $run = TRUE;
                break;
        }

        // Bail 
        if (!$run && !$force)
            return;

        $flexshare = new Flexshare();

        $flex_dir = Flexshare::SHARE_PATH . "/" . self::FLEXSHARE_SEARCH . "/";

        // Remove spacing
        $filename = preg_replace("/ /", "_", $filename);

        // Remove encrtiption extension if user added one
        $filename = preg_replace("/.enc$/", "", $filename);

        // Remove file extension if user added one
        $filename = preg_replace("/.tar.gz$/", "", $filename);

        // Add file extension
        $filename = $filename . ".tar.gz";
        
        if (!$this->is_valid_filename($filename))
            throw new Engine_Exception(lang('mail_archive_filename_invalid'), CLEAROS_ERROR);

        // Check if filename is a duplicate
        if ($this->get_archive_encryption())
            $file = new File(self::DIR_LINKS . "/$filename.enc", TRUE);
        else
            $file = new File(self::DIR_LINKS . "/$filename", TRUE);
        if ($file->is_sym_link() != 0)
            throw new File_Already_Exists_Exception($filename, CLEAROS_ERROR);
        
        // Check that we have a flexshare defined
        try {
            $flexshare->get_share(self::FLEXSHARE_SEARCH);
        } catch (Flexshare_Not_Found_Exception $e) {
            $flexshare->add_share(self::FLEXSHARE_SEARCH, lang('mail_archive_flexshare_desc'), self::SYS_USER);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // From archive.inc.php
        if (is_archive_running())
            throw new Engine_Exception(lang('mail_archive_running'), CLEAROS_ERROR);

        try {
            $pw = escapeshellarg($this->get_encryption_password());
            $args = escapeshellarg($filename) . " c";
            $args .= " " . (($pw) ? $pw : "''");
            $args .= ($purge ? ' TRUE' : ' FALSE');
            $options = array();
            $options['background'] = TRUE;
            $shell = new Shell();
            $retval = $shell->execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_ARCHIVE, $args, TRUE, $options);
            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_ERROR);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Restores an archive to the search database.
     *
     * @param string $filename archive search filename
     *
     * @return void
     *
     * @throws Engine_Exception
     *
     */

    public function restore_archive($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_valid_filename($filename))
            throw new Engine_Exception(lang('mail_archive_filename_invalid'), CLEAROS_ERROR);

        // Check if filename link exists
        $file = new File(self::DIR_LINKS . "/$filename", TRUE);
        if ($file->is_sym_link() != 1)
            throw new File_Not_Found_Exception($filename, CLEAROS_ERROR);

        // From archive.inc.php
        if (is_archive_running())
            throw new Engine_Exception(lang('mail_archive_running'), CLEAROS_ERROR);

        try {
            $args = escapeshellarg($filename) . " x";
            if (preg_match("/.enc$/i", $filename))
                $args .= " " . escapeshellarg($this->get_encryption_password());
            $options = array();
            $options['background'] = TRUE;
            $shell = new Shell();
            $retval = $shell->execute(COMMON_CORE_DIR . '/scripts/' . self::FILE_ARCHIVE, $args, TRUE, $options);
            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_ERROR);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Delete the current archive directory and clears database.
     *
     * @throws Engine_Exception
     *
     * @return void
     */

    function reset_current()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $folder = new Folder(self::DIR_CURRENT, TRUE);
            if ($folder->exists())
                $folder->delete(TRUE);

            // Recreate folder to free up inodes that return incorrect stats
            $folder->create("root", "root", "0700");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Reset database
        $this->_sql_clear(self::DB_NAME_CURRENT);
    }

    /**
     * Delete the search archive directory and clear database.
     *
     * @throws Engine_Exception
     *
     * @return void
     */

    function reset_search()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $folder = new Folder(self::DIR_SEARCH, TRUE);
            if ($folder->exists())
                $folder->delete(TRUE);

            // Recreate folder to free up inodes that return incorrect stats
            $folder->create("root", "root", "0700");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Reset database
        $this->_sql_clear(self::DB_NAME_SEARCH);
    }

    /** Run the bootstrap script.
     *
     * @return void
     * @throws Engine_Exception
     */

    function run_bootstrap()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        // If we can connect, bootstrap is already complete
        try {
            $this->_Connect();
            return;
        } catch (Exception $e) {
        }

        try {
            $args = "";
            $shell = new Shell();
            $retval = $shell->execute(self::FILE_BOOTSTRAP, $args, TRUE);
            if ($retval != 0) {
                $errstr = $shell->get_last_output_line();
                throw new Engine_Exception($errstr, CLEAROS_ERROR);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Gets the mailbox password.
     *
     * @return  string
     *
     * @throws  Engine_Exception
     */

    function get_mailbox_password()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        // load configuration
        if (! $this->is_loaded)
            $this->_load_config();

        return base64_decode($this->config["mbox-password"]);
    }

    /**
     * Initialize archive mailbox.
     *
     * @param boolean $reset reset flag
     *
     * @return void
     */

    function initialize_mailbox($reset = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        // Get password for mailserver authentication
        $currentpw = $this->get_mailbox_password();

        // Catch the case where someone drops in an /etc/archive.conf file
        // with an existing password.

        if (!$reset && $currentpw) {
            try {
                $cyrus = new Cyrus();

                if ($cyrus->get_running_state()) {
                    $mbox = @imap_open("{localhost:143/imap/notls}INBOX", self::SYS_USER, $currentpw);
                    $imaperrors = imap_errors();
                    // KLUDGE: non-fatal errors can occur, so we have to look for a specific error message.
                    // Bail -- password works
                    $passed = TRUE;
                    foreach ($imaperrors as $error) {
                        if (preg_match("/Can not authenticate to IMAP server/i", $error))
                            $passed = FALSE;
                    }

                    if ($passed)
                        return;
                } else {
                    // Bail -- password set, but Cyrus is not running
                }
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }
        }

        // Generate random password

        try {
            $password = LDAP_Utilities::generate_password();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Check to see if archive user exists

        $adduser = FALSE;

        try {
            $user = new User(self::SYS_USER);
            $currentinfo = $user->get_info();
        } catch (User_Not_Found_Exception $e) {
            $adduser = TRUE;
        }

        try {
            if ($adduser) {
                $userinfo = array();
                $userinfo['password'] = $password;
                $userinfo['verify'] = $password;
                $userinfo['mailFlag'] = TRUE;
                $userinfo['lastName'] = "Email";
                $userinfo['firstName'] = "Archive";
                $userinfo['uid'] = self::SYS_USER;
                $userinfo['homeDirectory'] = "/dev/NULL";
                $userinfo['loginShell'] = "/sbin/nologin";

                $user->Add($userinfo);
            } else {
                $userinfo = array();
                $userinfo['password'] = $password;
                $userinfo['verify'] = $password;

                if (! $currentinfo['mailFlag'])
                    $userinfo['mailFlag'] = TRUE;

                $user->Update($userinfo);
            }

            $this->set_mailbox_password($password, $password);

        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Formats a value into a human readable byte size.
     *
     * @param stirng $input the value
     * @param int    $dec   number of decimal places
     *
     * @return  string
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

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
    * loads configuration files.
    *
    * @return void
    * @throws Engine_Exception
    */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);
            
        $this->config = $configfile->load();

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
            $regex = str_replace("[", "\\[", $key);
            $regex = str_replace("]", "\\]", $regex);
            $file = new File(self::FILE_CONFIG, TRUE);
            $match = $file->replace_lines("/^$regex\s*=\s*/", "$key = $value\n");
            if (!$match)
                $file->add_lines("$key = $value\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    /**
     * Get stats from the database.
     *
     * @param string $db_name database name
     *
     * @return void
     * @throws Engine_Exception
     *
     */

    function _get_stats($db_name)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $stats = Array("size" => 0);

        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        // Select the db name
        mysql_select_db($db_name);

        // Calculate estimated size
        $bytes = 0;
        try {
            // Add archive table
            $file = new File(self::DIR_MYSQL_ARCHIVE . "/" . $db_name . "/archive.MYD", TRUE);
            if ($file->exists())
                $bytes = $file->get_size();
            // Add attachment table
            $file = new File(self::DIR_MYSQL_ARCHIVE . "/" . $db_name . "/attachment.MYD", TRUE);
            if ($file->exists())
                $bytes += $file->get_size();
            // Add attachments directory
            if ($db_name == self::DB_NAME_CURRENT)
                $folder = new Folder(self::DIR_CURRENT);
            else
                $folder = new Folder(self::DIR_SEARCH);
            if ($folder->exists())
                $bytes += $folder->get_size();
            
            $stats["size"] = $bytes;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        try {
            $db_stats = $this->_sql_get_stats($db_name);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        // Merge two arrays and return
        $stats = array_merge($stats, $db_stats); 
        return $stats;
    }

    /**
     * Destroy class.
     *
     * @return void
     *
     */

    function __destruct()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->link != NULL)
            mysql_close($this->link);

        parent::__destruct();
    }

    /**
     * Archive messages.
     *
     * @return void
     * @throws Engine_Exception
     *
     */

    function _archive_messages()
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        // Bail if lock file exists
        $lock = new File(self::FILE_LOCK, TRUE);
        if ($lock->exists()) {
            $time = new Time();
            date_default_timezone_set($time->get_time_zone());
            if ((time() - $lock->last_modified()) > (self::LOCK_TIMEOUT_HR *24))
                $lock->delete();
            return;
        } else {
            $lock->create("webconfig", "webconfig", "0640");
        }

        $input = '';
        $mailing_list = Array();
        $id = -1;

        // Get password for mailserver authentication
        $passwd = $this->get_mailbox_password();
        
        $cyrus = new Cyrus();

        if ($cyrus->get_service_state(Cyrus::CONSTANT_SERVICE_IMAP)) {
            $option = "/imap/notls";
            $port = 143; 
        } else if ($cyrus->get_service_state(Cyrus::CONSTANT_SERVICE_IMAPS)) {
            $option = "/imap/ssl/novalidate-cert";
            $port = 993; 
        } else if ($cyrus->get_service_state(Cyrus::CONSTANT_SERVICE_POP3)) {
            $option = "/pop3/notls";
            $port = 110; 
        } else if ($cyrus->get_service_state(Cyrus::CONSTANT_SERVICE_POP3S)) {
            $option = "/pop3/ssl/novalidate-cert";
            $port = 995; 
        }
                    
        list($user, $hostname) = explode('@', $this->get_archive_address());

        // Check if local service
        if ($hostname == 'localhost') {
            if (!$cyrus->get_running_state())
                return;
        } else {
            // Default POP3
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
                // Useful in event a problem prevents completion of import
                imap_expunge($mbox);
                $exp_counter = 0;
            } else {
                $exp_counter++;
            }
            // Check for ignore flag
            if (!preg_match("/pcn-archive-ignore/", imap_fetchheader($mbox, $index))) {
                $id = $this->_sql_insert_message($mbox, $index);
                $sender_domain = "";
                $recipient_domain = "";
                // Get domain names to use for attachment handling
                try {
                    $headers = imap_headerinfo($mbox, $index);
                    $sender_domain = $headers->from[0]->host;
                    $recipient_domain = $headers->to[0]->host;
                } catch (Exception $e) {
                    // Ignore
                }
                $this->_sql_insert_attachments($mbox, $index, $id, $sender_domain, $recipient_domain);
            }
            // Delete messages
            imap_delete($mbox, $index);
        }

        imap_close($mbox, CL_EXPUNGE);
        $lock->delete();
    }

    /**
     * Restore a message.
     *
     * @param string $db_name database name
     * @param int    $id      message ID
     * @param string $email   email
     *
     * @return void
     * @throws Engine_Exception
     *
     */

    function _sql_restore_message($db_name, $id, $email)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        // Select the db name
        mysql_select_db($db_name);

        $mailer = new Mail_Notification();

        $sql = sprintf("SELECT *, UNIX_TIMESTAMP(sent) AS unixsent FROM archive WHERE id = '%d'", $id);
        $result = mysql_query($sql);
        $result_set = Array();
        $index = 0;
        if ($result) {
            if (mysql_num_rows($result) == 0)
                throw new Engine_Exception(lang('mail_archive_record_not_found'), CLEAROS_ERROR);
            if (mysql_num_rows($result) > 1)
                throw new Engine_Exception(lang('base_error') . " (" . __LINE__ . ")", CLEAROS_ERROR);
            $result_set = mysql_fetch_array($result, MYSQL_ASSOC);
            mysql_free_result($result);
        } else {
            throw new Engine_Exception(lang('mail_archive_db_error') . " (" . __LINE__ . ")", CLEAROS_ERROR);
        }

        if ($email == NULL)
            $email = $result_set['recipient'];

        // Recipient(s)
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
                    // This screws up eml attachments
                    // No idea why...but doesn't seem to be needed
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
                        $file = new File($dir . "/" . $part['md5'], TRUE);
                        if ($file->exists()) {
                            $file->copy_to(COMMON_TEMP_DIR . "/" . $part['filename']);
                            $file->Chown("webconfig", "webconfig");
                            $file->Chmod("0640");
                        } else {
                            $file->create("webconfig", "webconfig", "0640");
                            $file->add_lines(lang('mail_archive_attachment_does_not_exist') . "\n");
                        }
                        // Set filename with path
                        $part['filename'] = COMMON_TEMP_DIR . "/" . $part['filename'];
                        $filesfordeletion[] = $part['filename'];
                    } else if ($part['encoding'] == "base64") {
                        $part['data'] = base64_decode($part['data']);
                    }

                    $mailer->set_part($part);
                }
                mysql_free_result($result);
            } else {
                throw new Engine_Exception(lang('mail_archive_db_error') . " (" . __LINE__ . ")", CLEAROS_ERROR);
            }
        } catch (Exception $e) {
            clearos_profile(__METHOD__, __LINE__, 'Failed to save attachment ' . clearos_exception_message($e));
        }

        foreach ($addresses as $recipient)
            $mailer->add_recipient($recipient);

        if (isset($result_set['subject']))
            $mailer->set_subject($result_set['subject']);
        if (isset($result_set['body']))
            $mailer->set_body($result_set['body']);
        $mailer->override_date($result_set['unixsent']);
        $mailer->set_reply_to($result_set['sender']);
        $mailer->set_sender($result_set['sender']);
        
        $mailer->Send();

        // Clean up
        if (isset($filesfordeletion)) {
            foreach ($filesfordeletion as $filename) {
                $file = new File($filename, TRUE);
                $file->delete();
            }
        }
    }

    /**
     * Get archived email.
     *
     * @param string $db_name database name
     * @param int    $id      message ID
     *
     * @return array
     * @throws Engine_Exception
     *
     */

    function _sql_get_archived_email($db_name, $id)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        // Select the db name
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
                    throw new Engine_Exception(LOCALE_LANG_ACCESS_DENIED, CLEAROS_ERROR);
                else
                    throw new Engine_Exception(lang('mail_archive_record_not_found'), CLEAROS_ERROR);
            }
            if (mysql_num_rows($result) > 1)
                throw new Engine_Exception(lang('base_error') . " (" . __LINE__ . ")", CLEAROS_ERROR);
            $result_set = mysql_fetch_array($result, MYSQL_ASSOC);
            mysql_free_result($result);
        } else {
            throw new Engine_Exception(lang('mail_archive_db_error') . " (" . __LINE__ . ")", CLEAROS_ERROR);
        }

        // Attachments
        $sql = sprintf("SELECT * FROM attachment WHERE archive_id = '%d' ORDER BY id", $id);
        $result = mysql_query($sql);
        if ($result) {
            while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
                // If TRUE attachment, add
                if (isset($line['filename'])) {
                    $attachments[] = $line;
                } else if (isset($this->config['display-type'])) {
                    // Check config to see what to display
                    $display_regex = "/" . preg_replace("/\//", "\/", $this->config['display-type']) . "/i";
                    if (preg_match($display_regex, $line['type']))
                        $attachments[] = $line;
                } else {
                    // Default to text/plain
                    if (preg_match("/text\/plain/i", $line['type']))
                        $attachments[] = stripslashes($line);
                }
            }
            mysql_free_result($result);
            // Tack on attachments to return array
            $result_set['attachments'] = $attachments;
        }
        return $result_set;
    }

    /**
     * Get archived email.
     *
     * @param string $db_name database name
     * @param string $search  search string
     * @param string $logical logical expression
     * @param int    $max     maximum records to return
     * @param int    $offset  offset
     *
     * @return array
     * @throws Engine_Exception
     *
     */

    function _sql_search($db_name, $search, $logical, $max, $offset)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        if (!isset($offset) || !$offset)
            $offset = 0;
        // Select the db name
        mysql_select_db($db_name);

        $result_set = Array();

        try {
            mysql_query('SET NAMES UTF8', $this->link);
            $sql = "SELECT id, subject, sender, recipient, cc, UNIX_TIMESTAMP(sent) AS sent FROM archive WHERE ";
            $isfirst = TRUE;
            foreach ($search as $conditional) {
                if (! $isfirst)
                    $sql .= " $logical "; 
                // Determine field
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
                // Change asterisk to percent since normal association with wildcard is *
                if ($conditional['regex'] == '*')
                    $conditional['regex'] = '%';

                // Determine criteria
                // Protect against single quote
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
                $isfirst = FALSE;
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
            
            // Check 'attachment' body
            $sql = "SELECT archive.id, archive.subject, archive.sender, archive.recipient, ";
            $sql .= "archive.cc, UNIX_TIMESTAMP(archive.sent) AS sent ";
            $sql .= "FROM archive, attachment WHERE ";
            $sql .= "attachment.archive_id = archive.id AND ";
            $isfirst = TRUE;
            foreach ($search as $conditional) {
                $containsbody = FALSE;
                if (! $isfirst)
                    $sql .= " $logical "; 
                // Determine field
                switch ($conditional['field']) {
                    case self::SEARCH_BODY:
                        $sql.= '(attachment.data ';
                        $containsbody = TRUE;
                        break;
                }

                if (!$containsbody)
                    continue;
                // Determine criteria
                // Protect against single quote
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
                $isfirst = FALSE;
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
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        return $result_set;
    }

    /**
     * Get database statistics.
     *
     * @param string $db_name database name
     *
     * @return array
     * @throws Engine_Exception
     *
     */


    function _sql_get_stats($db_name)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        $db_stats = Array();

        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        // Select the db name
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
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        return $db_stats;
    }

    /**
     * MIME decoder.
     *
     * @param string $string string to decodename
     *
     * @return array
     * @throws Engine_Exception
     *
     */

    function _flat_mime_decode($string)
    {
        $array = imap_mime_header_decode($string);
        $str = "";
        foreach ($array as $key => $part) {
            $str .= $part->text;
        }
        return $str;
    }

    /**
     * Insert message.
     *
     * @param string $mbox  mailbox
     * @param int    $index message index
     *
     * @return int
     * @throws Engine_Exception
     *
     */

    function _sql_insert_message($mbox, $index)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        mysql_select_db(self::DB_NAME_CURRENT);

        if (mysql_errno() != 0)
            throw new Sql_Exception(mysql_error(), CLEAROS_ERROR);

        $headers = imap_headerinfo($mbox, $index);

        $structure = imap_fetchstructure($mbox, $index);

        try {
            // Swift mailer logs a warning message if we don't set this
            $time = new Time();
            date_default_timezone_set($time->get_time_zone());
            // Decode headers
            $fromaddress = $this->_flat_mime_decode($headers->fromaddress);
            $toaddress = $this->_flat_mime_decode($headers->toaddress);
            if (isset($headers->ccaddress))
                $ccaddress = $this->_flat_mime_decode($headers->ccaddress);
            else
                $ccaddress = '';
            $subject = $this->_flat_mime_decode($headers->subject);
            $sql = "INSERT INTO archive (sender, recipient, cc, bcc, subject, size, sent, header, body, created)";
            $sql .= sprintf(
                " VALUES ('%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', now());",
                addslashes($fromaddress),
                addslashes($toaddress),
                addslashes($ccaddress),
                '',
                addslashes($subject),
                $headers->Size, date("Y-m-d H:i:s", $headers->udate),
                addslashes(imap_fetchheader($mbox, $index)),
                ($structure->subtype == 'PLAIN' ? addslashes(imap_body($mbox, $index)) : '')
            );
            $result = mysql_query($sql);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
        if (!$result) {
            throw new Engine_Exception(lang('mail_archive_db_error') . " - " . mysql_error(), CLEAROS_ERROR);
        } else {
            return mysql_insert_id();
        }
    }

    /**
     * Insert message attachments.
     *
     * @param string $mbox      mailbox
     * @param int    $index     message index
     * @param int    $id        message ID
     * @param string $sender    sender
     * @param string $recipient recipient
     *
     * @return int
     * @throws Engine_Exception
     *
     */

    function _sql_insert_attachments($mbox, $index, $id, $sender, $recipient)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        mysql_select_db(self::DB_NAME_CURRENT);

        $mime = new Mime();

        $structure = imap_fetchstructure($mbox, $index);
        $msgparts = $mime->get_parts($structure);
        foreach ($msgparts as $partid => $msgpart) {
            try {
                if (!$msgpart['size'])
                    continue;
                if (isset($msgpart['name'])) {
                    // Make sure config is loaded
                    if (! $this->is_loaded)
                        $this->_load_config();

                    $keep_attach = TRUE;
                    // Determine policy to use
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
                            $keep_attach = FALSE;
                            break;
                        case self::DISCARD_ATTACH_1:
                            if ($msgpart['size'] > 1*1024*1024)
                                $keep_attach = FALSE;
                            break;
                        case self::DISCARD_ATTACH_5:
                            if ($msgpart['size'] > 5*1024*1024)
                                $keep_attach = FALSE;
                            break;
                        case self::DISCARD_ATTACH_10:
                            if ($msgpart['size'] > 10*1024*1024)
                                $keep_attach = FALSE;
                            break;
                        case self::DISCARD_ATTACH_25:
                            if ($msgpart['size'] > 25*1024*1024)
                                $keep_attach = FALSE;
                            break;
                    }

                    // Skip if conditions are met to discard attachment
                    if (! $keep_attach) {
                        clearos_profile(__METHOD__, __LINE__, "Attachment not archived, to=$recipient, send=$sender");
                        continue;
                    }
                    
                    $folder = new Folder(self::DIR_CURRENT, TRUE);
                    if (!$folder->exists())
                        $folder->create("root", "root", "0700");
                    
                    $filename = preg_replace("/([^a-zA-Z0-9.\\-\\_])/", "_", $msgpart['name']);
                    $file = new File($folder->get_foldername() . "/" . $filename, TRUE);

                    if (imap_fetchbody($mbox, $index, $partid) == "")
                        continue;

                    if ($file->exists())
                        $file->delete();
                    $file->create("root", "webconfig", "0640");
                    if ($msgpart['encoding'] == "base64")
                        $file->add_lines(base64_decode(imap_fetchbody($mbox, $index, $partid)));
                    else
                        $file->add_lines(imap_fetchbody($mbox, $index, $partid));
                    $md5 = $file->get_md5();
                    $file->move_to($folder->get_foldername() . "/$md5"); 
                    $sql = "INSERT INTO attachment (archive_id, type, encoding, cid, charset, " .
                           "disposition, filename, md5, size)";
                    $sql .= sprintf(
                        " VALUES ('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')",
                        $id, $msgpart['type'], $msgpart['encoding'],
                        isset($msgpart['Content-ID']) ? $msgpart['Content-ID'] : '',
                        isset($msgpart['charset']) ? $msgpart['charset'] : '',
                        $msgpart['disposition'], $filename, $md5, $msgpart['size']
                    );
                } else {
                    $sql = "INSERT INTO attachment (archive_id, type, encoding, cid, charset, " .
                           "disposition, size, data)";
                    if ($msgpart['encoding'] == "quoted-printable") {
                        $sql .= sprintf(
                            " VALUES ('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')",
                            $id, $msgpart['type'], $msgpart['encoding'],
                            isset($msgpart['Content-ID']) ? $msgpart['Content-ID'] : '',
                            isset($msgpart['charset']) ? $msgpart['charset'] : '',
                            $msgpart['disposition'], $msgpart['size'],
                            addslashes(quoted_printable_decode(imap_fetchbody($mbox, $index, $partid)))
                        );
                    } else {
                        $sql .= sprintf(
                            " VALUES ('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')",
                            $id, $msgpart['type'], $msgpart['encoding'],
                            isset($msgpart['Content-ID']) ? $msgpart['Content-ID'] : '',
                            isset($msgpart['charset']) ? $msgpart['charset'] : '',
                            $msgpart['disposition'], $msgpart['size'],
                            addslashes(imap_fetchbody($mbox, $index, $partid))
                        );
                    }
                }
                $result = mysql_query($sql);

                if (!$result) 
                    throw new Engine_Exception(lang('mail_archive_db_error') . " - " . mysql_error(), CLEAROS_ERROR);
            } catch (Exception $e) {
                clearos_profile(__METHOD__, __LINE__, clearos_exception_method($e));
            }
        }
    }

    /**
     * Clear a database.
     *
     * @param string $db_name database name
     *
     * @return void
     * @throws Engine_Exception
     *
     */

    function _sql_clear($db_name)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->link == NULL)
            $this->_Connect();

        // Select the db name
        mysql_select_db($db_name);

        try {
            $sql = "DELETE FROM archive";
            $result = mysql_query($sql);
            $sql = "DELETE FROM attachment";
            $result = mysql_query($sql);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        if (!$result)
            throw new Engine_Exception(lang('mail_archive_db_error') . " - " . mysql_error(), CLEAROS_ERROR);
    }

    /**
     * Delete the current archive directory.
     *
     * @return void
     * @throws Engine_Exception
     */

    private function _current_dir_clear()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $folder = new Folder(self::DIR_CURRENT);

            if (! $folder->exists())
                throw new Folder_Not_Found_Exception(self::DIR_CURRENT, CLEAROS_ERROR);
            $contents = $folder->get_listing();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        if (! $contents)
            return;

        foreach ($contents as $filename) {
            $file = new File(self::DIR_CURRENT . "/" . $filename, TRUE);
            $file->delete();
        }
    }

    /**
     * Connects to mysql database.
     *
     * @throws Configuration_File_Exception
     * @throws File_Exception
     * @throws Sql_Exception
     * @return void
     */

    private function _connect()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_NULL($this->db))
            $this->_load_db_config();

        $this->link = mysql_connect(
            self::DB_HOST . ":" . self::SOCKET_MYSQL, self::DB_USER, $this->db['archive.password']
        );
        if (!$this->link)
            throw new Sql_Exception(mysql_error(), CLEAROS_ERROR);

        // Check to see if we can connect to the database
        mysql_select_db(self::DB_NAME_CURRENT);
        if (mysql_errno() != 0)
            throw new Sql_Exception(mysql_error(), CLEAROS_ERROR);
    }

    /**
     * Read database parameters from file
     *
     * @throws Engine_Exception
     * @return void
     */

    private function _load_db_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new Configuration_File(self::FILE_CONFIG_DB, 'explode', '=', 2);
            $this->db = $file->load();
        } catch (File_Not_Found_Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for filename.
     *
     * @param string $filename archive filename
     *
     * @return boolean
     */

    function is_valid_filename($filename)
    {
        clearos_profile(__METHOD__, __LINE__);
            
        if (!preg_match("/^([A-Za-z0-9\-\.\_\'\:]+)$/", $filename))
            return lang('mail_archive_filename_invalid');
    }

    /**
     * Validation routine for encryption password.
     *
     * @param boolean $pwd password
     *
     * @return boolean TRUE if password is valid
     */

    function is_valid_password($pwd)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Get length of password
        if (isset($this->config['encrypt-password-length']))
            $min = $this->config['encrypt-password-length'];
        else
            $min = 12;

        if (strlen($pwd) < $min)
            return lang('mail_archive_password_too_short');
        else if (preg_match("/[\|;\*]/", $pwd) || !preg_match("/^[a-zA-Z0-9]/", $pwd))
            return lang('mail_archive_password_invalid');
    }
}
