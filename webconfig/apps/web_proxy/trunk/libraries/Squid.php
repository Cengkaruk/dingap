<?php

/**
 * Squid web proxy class.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
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

namespace clearos\apps\web_proxy;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('web_proxy');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Product as Product;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Network as Network;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Product');
clearos_load_library('base/Shell');
clearos_load_library('network/Network');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Squid web proxy class.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Squid extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/squid/squid.conf';
    const FILE_ADZAPPER = '/usr/sbin/adzapper';
    const FILE_LDAP = '/etc/squid/ldap.conf';
    const PATH_SPOOL = '/var/spool/squid';
    const CONSTANT_NO_OFFSET = -1;
    const CONSTANT_UNLIMITED = 0;
    const DEFAULT_MAX_FILE_DOWNLOAD_SIZE = 0;
    const DEFAULT_MAX_OBJECT_SIZE = 4194304;
    const DEFAULT_REPLY_BODY_MAX_SIZE_VALUE = '0 allow all';
    const DEFAULT_CACHE_SIZE = 104857600;
    const DEFAULT_CACHE_DIR_VALUE = 'ufs /var/spool/squid 100 16 256';
    // A line in the 'acl section' that is guaranteed to be in squid.conf
    const REGEX_ACL_DELIMITER = '/^#\s+webconfig:\s+acl_end/';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Squid constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('squid');
    }

    /**
     * Returns state of Adzapper filter.
     *
     * @return boolean TRUE if Adzapper is enabled.
     * @throws Engine_Exception
     */

    public function get_adzapper_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if ($this->get_redirect_program() == self::FILE_ADZAPPER)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns state of user authentication.
     *
     * @return boolean TRUE if user authentication is enabled.
     * @throws Engine_Exception
     */

    public function get_user_authentication_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['http_access'])) {
            foreach ($this->config['http_access']['line'] as $line) {
                if (preg_match('/^allow\s+webconfig_lan\s+password$/', $line))
                    return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Returns authentication details.
     *
     * @return array authentication details
     * @throws Engine_Exception
     */

    public function get_basic_authentication_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $info = array();

        if (isset($this->config["auth_param"])) {

            foreach ($this->config["auth_param"]["line"] as $line) {
                $items = preg_split("/\s+/", $line, 3);

                if ($items[1] == "program") {
                    if ($items[0] != "basic")
                        throw new Engine_Exception(lang('proxy_custom_configuration_detected'));
                    $info['program'] = $items[2];
                } else if ($items[1] == "children") {
                    $info['children'] = $items[2];
                } else if ($items[1] == "credentialsttl") {
                    $info['credentialsttl'] = $items[2];
                } else if ($items[1] == "realm") {
                    $info['realm'] = $items[2];
                }
            }
        }

        return $info;
    }

    /**
     * Returns the cache size (in bytes).
     *
     * @return integer cache size in bytes
     * @throws Engine_Exception
     */

    public function get_cache_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['cache_dir'])) {
            $items = preg_split('/\s+/', $this->config['cache_dir']['line'][1]);

            if (isset($items[2]))
                return $this->_size_in_bytes($items[2], 'MB');
            else
                return self::DEFAULT_CACHE_SIZE;
        } else {
            return self::DEFAULT_CACHE_SIZE;
        }
    }

    /**
     * Returns the state of content filter.
     *
     * @return boolean state of content filter
     * @throws Engine_Exception
     */

    public function get_content_filter_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        return TRUE;
    }

    /**
     * Returns all defined ACL rules.
    /**
     * Returns the maximum file download size (in bytes).
     *
     * @return integer maximum file download size in bytes
     * @throws Engine_Exception
     */

    public function get_maximum_file_download_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config["reply_body_max_size"])) {
            $items = preg_split("/\s+/", $this->config["reply_body_max_size"]["line"][1]);

            if (isset($items[0]))
                return $items[0];
            else
                return self::DEFAULT_MAX_FILE_DOWNLOAD_SIZE;
        } else {
            return self::DEFAULT_MAX_FILE_DOWNLOAD_SIZE;
        }
    }

    /**
     * Returns the maximum object size (in bytes).
     *
     * @return int maximum object size in bytes
     * @throws Engine_Exception
     */

    public function get_maximum_object_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config["maximum_object_size"])) {
            $items = preg_split("/\s+/", $this->config["maximum_object_size"]["line"][1]);

            if (isset($items[0])) {
                if (isset($items[1]))
                    return $this->_size_in_bytes($items[0], $items[1]);
                else
                    return $items[0];
            } else {
                return self::DEFAULT_MAX_OBJECT_SIZE;
            }
        } else {
            return self::DEFAULT_MAX_OBJECT_SIZE;
        }
    }

    /**
     * Returns redirect_program parameter.
     *
     * @return string redirect program
     * @throws Engine_Exception
     */

    public function get_redirect_program()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config["redirect_program"]))
            return $this->config["redirect_program"]["line"][1];
    }

    /**
     * Returns the state of transparent mode.
     *
     * @return boolean state of transparent mode
     * @throws Engine_Exception
     */

    public function get_transparent_mode_state()
    {
        clearos_profile(__METHOD__, __LINE__);

    }

    /**
     * Returns all defined ACL rules.
     *
     * @return array a list of time-based ACL rules.
     * @throws Engine_Exception
     */

    public function get_acl_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        if (! $this->is_loaded)
            $this->_load_config();

        $file = new File(self::FILE_CONFIG, TRUE);

        foreach ($this->config['http_access']['line'] as $line => $acl) {
            if (!ereg("^(deny|allow) pcngroup-.*$", $acl))
                continue;
            $temp = array();
            $parts = explode(' ', $acl);
            $temp['type'] = $parts[0];
            $temp['name'] = substr($parts[1], 9, strlen($parts[1]));
            $temp['logic'] = !eregi("!", $parts[2]);
            list($dow, $tod) = split(' ', $file->LookupValue("/^acl " . eregi_replace("!", "", $parts[2]) . " time/"));
            $temp['time'] = $parts[2];
            $temp['dow'] = $dow;
            $temp['tod'] = $tod;
            $temp['users'] = '';
            try {
                $temp['users'] = trim($file->LookupValue("/^acl pcngroup-" . $temp['name'] . " proxy_auth/"));
                $temp['ident'] = 'proxy_auth';
            } catch (Exception $e) {
                $temp['users'] = '';
            }
            try {
                $temp['ips'] = trim($file->LookupValue("/^acl pcngroup-" . $temp['name'] . " src/"));
                $temp['ident'] = 'src';
            } catch (Exception $e) {
                $temp['ips'] = '';
            }
            try {
                $temp['macs'] = trim($file->LookupValue("/^acl pcngroup-" . $temp['name'] . " arp/"));
                $temp['ident'] = 'arp';
            } catch (Exception $e) {
                $temp['macs'] = '';
            }
            $list[] = $temp;
        }
        return $list;
    }

    /**
     * Returns all time-based ACL definitions.
     *
     *
     * @return array a list of time-based ACL definitions.
     * @throws Engine_Exception
     */

    public function get_time_definition_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        if (! $this->is_loaded)
            $this->_load_config();

        foreach ($this->config['acl']['line'] as $line => $acl) {
            if (!ereg("^pcntime-.*$", $acl))
                continue;
            $temp = array();
            $parts = explode(' ', $acl);
            $temp['name'] = substr($parts[0], 8, strlen($parts[0]));
            $temp['dow'] = str_split($parts[2]);
            list($start, $end) = explode('-', $parts[3]);
            list($temp['start']['h'], $temp['start']['m']) = explode(':', $start);
            list($temp['end']['h'], $temp['end']['m']) = explode(':', $end);
            $list[] = $temp;
        }
        return $list;
    }

    /**
     * Returns method of identification mapping.
     * $return array a mapping of ID types
     */

    public function get_identification_type_array()
    {
        $type = array(
            'proxy_auth' => SQUID_LANG_USER,
            'src' => SQUID_LANG_IP,
            'arp' => SQUID_LANG_MAC
        );
        return $type;
    }

    /**
     * Returns allow/deny mapping.
     * $return array a mapping of access types
     */

    public function get_access_type_array()
    {
        $type = array(
            'allow' => SQUID_LANG_ALLOW,
            'deny' => SQUID_LANG_DENY
        );
        return $type;
    }

    /**
     * Returns weekday mapping.
     * $return array a mapping of Squid days of weeks and human readable days
     */

    public function get_day_of_week_array()
    {
        $dow = array(
            'S' => LOCALE_LANG_SUNDAY,
            'M' => LOCALE_LANG_MONDAY,
            'T' => LOCALE_LANG_TUESDAY,
            'W' => LOCALE_LANG_WEDNESDAY,
            'H' => LOCALE_LANG_THURSDAY,
            'F' => LOCALE_LANG_FRIDAY,
            'A' => LOCALE_LANG_SATURDAY
        );
        return $dow;
    }

    /**
     * Deletes the proxy cache.
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function reset_cache()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $wasrunning = $this->GetRunningState();
            $this->SetRunningState(FALSE);

            $options['background'] = TRUE;

            $shell = new Shell();
            $retval = $shell->Execute("/bin/mv", "/var/spool/squid /var/spool/squid.delete", TRUE);
            $retval = $shell->Execute("/bin/rm", "-rf /var/spool/squid.delete", TRUE, $options);

            if ($retval != 0)
                throw new Engine_Exception($shell->GetLastOutputLine(), COMMON_WARNING);

            $folder = new Folder(self::PATH_SPOOL);
            $folder->Create("squid", "squid", "0750");

            if ($wasrunning)
                $this->SetRunningState(TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_WARNING);
        }
    }

    /**
     * Sets user authentication state;
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_authentication_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_bool($state))
            throw new Validation_Exception(SQUID_LANG_USER_AUTHENTICATION . " - " . LOCALE_LANG_INVALID);

        $this->UpgradeConfiguration();

        if ($state) {
            $this->_set_parameter("http_access allow webconfig_lan", "password", self::CONSTANT_NO_OFFSET, "");
            $this->_set_parameter("http_access allow localhost", "password", self::CONSTANT_NO_OFFSET, "");
        } else {
            $this->_set_parameter("http_access allow webconfig_lan", "", self::CONSTANT_NO_OFFSET, "");
            $this->_set_parameter("http_access allow localhost", "", self::CONSTANT_NO_OFFSET, "");
        }

        // KLUDGE: DansGuardian does not like having authorization plugins 
        // enabled if Squid is not configured with authentication.  

        if (file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php")) {
            require_once('DansGuardianAv.class.php');
            $plugins = ($state) ? array("dummy_data_for_now") : array();
            $dgav = new DansGuardian();
            $dgav->SetAuthorizationPlugins($plugins);
            $dgav->Reset();
        }
    }

    /**
     * Sets basic authentication default values.
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_basic_authentication_info_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            if (file_exists(COMMON_CORE_DIR . "/api/Product.class.php")) {
                require_once(COMMON_CORE_DIR . "/api/Product.class.php");

                $product = new Product();
                $name = $product->GetName();
                $realm = $name . " - " . SQUID_LANG_WEB_PROXY;
            } else {
                $realm = SQUID_LANG_WEB_PROXY;
            }

            $ldap = new Ldap();
            $binddn = $ldap->GetBindDn();
            $bindpw = $ldap->GetBindPassword();
            $basedn = ClearDirectory::GetUsersOu();

            $file = new File(self::FILE_LDAP);
            if ($file->exists())
                $file->Delete();

            $file->create("root", "squid", "0640");
            $file->add_lines("$bindpw\n");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        $children = '25';

        try {
            $stats = new Stats();
            $meminfo = $stats->GetMemStats();

            $multiplier = floor($meminfo->mem_total / 1000000) + 1;
            $children = $children * $multiplier;
        } catch (Exception $e) {
            // not fatal
        }

        $program = "/usr/lib/squid/squid_ldap_auth -b \"$basedn\" -f \"(&(pcnProxyFlag=TRUE)(uid=%s))\" -h 127.0.0.1 -D \"$binddn\" -W /etc/squid/ldap.conf -s one -v 3 -U pcnProxyPassword -d";
        $this->_set_parameter("auth_param basic children", $children, self::CONSTANT_NO_OFFSET, "");
        $this->_set_parameter("auth_param basic realm", $realm, self::CONSTANT_NO_OFFSET, "");
        $this->_set_parameter("auth_param basic credentialsttl", "2 hours", self::CONSTANT_NO_OFFSET, "");
        $this->_set_parameter("auth_param basic program", $program, self::CONSTANT_NO_OFFSET, "");
        $this->_set_parameter("acl password proxy_auth", "REQUIRED", self::CONSTANT_NO_OFFSET, "");
    }

    /**
     * Sets the cache size.
     *
     * @param integer $size size in bytes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_cache_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_cache_size($size));

        $size = round($size / 1024 / 1024); // MB for cache_dir
        $this->_set_parameter('cache_dir', $size, 3, self::DEFAULT_CACHE_DIR_VALUE);
    }

    /**
     * Sets the maximum download size.
     *
     * @param int $size size in bytes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_maximum_file_download_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_maximum_file_download_size($size));

        $this->_set_parameter('reply_body_max_size', $size, 1, self::DEFAULT_REPLY_BODY_MAX_SIZE_VALUE);
    }

    /**
     * Sets the maximum object size.
     *
     * @param int $size size in bytes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_maximum_object_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_maximum_object_size($size));

        $size = round($size / 1024); // KB to be consistent with squid.conf
        $this->_set_parameter('maximum_object_size', $size . ' KB', self::CONSTANT_NO_OFFSET, '');
    }

    /**
     * Sets Adzapper state.
     *
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_adzapper_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_bool($state))
            throw new Validation_Exception(SQUID_LANG_BANNER_AND_POPUP_FILTER . " - " . LOCALE_LANG_INVALID);

        if ($state)
            $this->_set_parameter("redirect_program", self::FILE_ADZAPPER, self::CONSTANT_NO_OFFSET, "");
        else
            $this->_DeleteParameter("redirect_program");
    }

    /**
     * Adds (or updates) a time-based ACL.
     *
     * @param String $name ACL name
     * @param String $type ACL type (allow or deny)
     * @param String $time a time definition
     * @param Boolean $time_logic TRUE if within time definition, FALSE if NOT within
     * @param String[] $addusers an array containing users to apply ACL
     * @param String[] $addips an array containing IP addresses or network notation to apply ACL
     * @param String[] $addmacs an array containing MAC addresses to apply ACL
     * @param Boolean $update TRUE if we are updating an existing entry
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_acl($name, $type, $time, $time_logic, $addusers, $addips, $addmacs, $update = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
 
        $users = '';
        $ips = '';
        $macs = '';

        if (! $this->IsValidName($name))
            throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_NAME);

        # Check for existing
        if (!$update) {
            $acls = $this->GetAclList();
            foreach ($acls as $acl) {
                if ($name == $acl['name'])
                    throw new Validation_Exception(SQUID_LANG_ERRMSG_DUPLICATE_ACL);
            }
        }

        if ($type != 'allow' && $type != 'deny')
            throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_TYPE);

        $timelist = $this->GetTimeDefinitionList();
        $timevalid = FALSE;

        foreach ($timelist as $timename) {
            if ($time == $timename['name']) {
                $timevalid = TRUE;
                break;
            }
        }
            
        if (!$timevalid)
            throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_TIME_DEFINITION);

        try {
            $usermanager = User_Manager::create();
            $userlist = $usermanager->get_all_user_info();

            # Populate users list on invalid data
            foreach ($addusers as $user) {

                $uservalid = FALSE;

                foreach ($userlist as $userinfo) {
                    if ($userinfo['uid'] != $user)
                        continue;

                    $uservalid = TRUE;

                    if (empty($userinfo['proxyFlag']))
                        throw new Validation_Exception(SQUID_LANG_ERRMSG_USER_NO_ACCESS . ' - ' . $user);
                }

                if (! $uservalid)
                    throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_USER . ' - ' . $user);

                $users .= ' ' . $user;
            }
        } catch (Exception $e) {
            throw new Validation_Exception($e->GetMessage());
        }

        $network = new Network();

        foreach ($addips as $ip) {
            if (empty($ip))
                continue;

            if (eregi("^(.*)-(.*)$", trim($ip), $match)) {
                if (! $network->IsValidIp(trim($match[1])))
                    throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_IP . ' - ' . $match[1]);
                if (! $network->IsValidIp(trim($match[2])))
                    throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_IP . ' - ' . $match[2]);
            } else {
                if (!$network->IsValidIp(trim($ip)))
                    throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_IP . ' - ' . $ip);
            }

            $ips .= ' ' . trim($ip);
        }

        foreach ($addmacs as $mac) {
            if (empty($mac))
                continue;

            if (!$network->IsValidMac(trim($mac)))
                throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_MAC . ' - ' . $mac);

            $macs .= ' ' . trim($mac);
        }

        // Implant into acl section
        //-------------------------

        $file = new File(self::FILE_CONFIG, TRUE);

        try {
            if (! $this->is_loaded)
                $this->_load_config();

            if (strlen($users) > 0) {
                # Usersname based
                $replacement = "acl pcngroup-$name proxy_auth " . trim($users) . "\n";
                $match = $file->ReplaceLines("/acl pcngroup-$name proxy_auth.*$/", $replacement);

                if (! $match)
                    $file->add_lines_after($replacement, Squid::REGEX_ACL_DELIMITER);
                # Force reload
                $this->is_loaded = FALSE;
                $this->config = array();
            } else if (strlen($ips) > 0) {
                # IP based
                $replacement = "acl pcngroup-$name src " . trim($ips) . "\n";
                $match = $file->ReplaceLines("/acl pcngroup-$name src .*$/", $replacement);

                if (! $match)
                    $file->add_lines_after($replacement, Squid::REGEX_ACL_DELIMITER);
            } else if (strlen($macs) > 0) {
                # IP based
                $replacement = "acl pcngroup-$name arp " . trim($macs) . "\n";
                $match = $file->ReplaceLines("/acl pcngroup-$name arp .*$/", $replacement);
                if (! $match)
                    $file->add_lines_after($replacement, Squid::REGEX_ACL_DELIMITER);
            } else {
                throw new Engine_Exception(SQUID_LANG_ERRMSG_EMPTY_ID_ARRAY, COMMON_WARNING);
            }

        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        $this->is_loaded = FALSE;
        $this->config = array();
        $this->_load_config();

        try {
            $replacement = "http_access $type pcngroup-$name " . ($time_logic ? "" : "!") . "pcntime-$time\n";
            $match = $file->ReplaceLines("/http_access (allow|deny) pcngroup-$name .*$/", $replacement);

            if (! $match) {
                # TODO - Arbitrarily add after n-3 occurence of http_access directive - very lame
                $file->add_lines_after(
                    "http_access $type pcngroup-$name " . ($time_logic ? "" : "!") . "pcntime-$time\n", "/http_access " .
                    $this->config['http_access']['line'][$this->config['http_access']['count'] - 3] . "/i"
                );
            }

            # Check for follow_x_forwarded_for directives
            if (strlen($ips) > 0) {
                try {
                    $file->LookupLine("/^follow_x_forwarded_for allow localhost$/");
                } catch (File_No_Match_Exception $e) {
                    $lines = "follow_x_forwarded_for allow localhost\nfollow_x_forwarded_for deny localhost\n";
                    $file->add_linesBefore($lines, "/http_access " . str_replace("/", "\\/", $this->config['http_access']['line'][1]) . "/i");
                } catch (Exception $e) {
                    throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
                }

                # Check for DG config
                try {
                    if (file_exists(COMMON_CORE_DIR . "/api/DansGuardian.class.php")) {
                        $dg = new Daemon("dansguardian");
                        if ($dg->GetRunningState()) {
                            require_once('DansGuardian.class.php');
                            try {
                                $file = new File(DansGuardian::FILE_CONFIG);
                                if (eregi('off', $file->LookupValue("/^forwardedfor\s=/"))) {
                                    if (!$file->ReplaceLines("/^forwardedfor\s=/", "forwardedfor = on\n"))
                                        $file->add_lines("forwardedfor = on\n");
                                    $dg->Restart();
                                }
                            } catch (Exception $e) {
                                # Ignore
                            }
                        }
                    }

                    if (file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php")) {
                        $dgav = new Daemon("dansguardian-av");
                        if ($dgav->GetRunningState()) {
                            require_once('DansGuardianAv.class.php');
                            try {
                                $file = new File(DansGuardian::FILE_CONFIG);
                                if (eregi('off', $file->LookupValue("/^forwardedfor\s=/"))) {
                                    if (!$file->ReplaceLines("/^forwardedfor\s=/", "forwardedfor = on\n"))
                                        $file->add_lines("forwardedfor = on\n");
                                    $dgav->Restart();
                                }
                            } catch (Exception $e) {
                                # Ignore
                            }
                        }
                    }
                } catch (Exception $e) {
                    # Ignore
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        $this->is_loaded = FALSE;
        $this->config = array();
    }

    /**
     * Adds (or updates) a time definition for use with an ACL.
     *
     * @param String $name time name
     * @param String[] $dow an array of days of week
     * @param String[] $start an array containing start hour/min
     * @param String[] $end an array containing end hour/min
     * @param Boolean $update TRUE if we are updating an existing entry
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_definition($name, $dow, $start, $end, $update = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
 
        # Validate
        # --------
        if (! $this->IsValidName($name)) {
            throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_NAME);
        }
        # Check for existing
        if (!$update) {
            $times = $this->GetTimeDefinitionList();
            foreach ($times as $time) {
                if ($name == $time['name'])
                    throw new Validation_Exception(SQUID_LANG_ERRMSG_DUPLICATE_TIME);
            }
        }
        if (!isset($dow) || empty($dow)) {
            throw new Validation_Exception(SQUID_LANG_ERRMSG_INVALID_DOW);
        }

        $formatted_dow = implode('', array_keys($dow));
        if ($start['h'] > $end['h'])
            throw new Validation_Exception(SQUID_LANG_INVALID_TIME_END_LATER_START);
        elseif ($start['h'] == $end['h'] && $start['m'] >= $end['m'])
            throw new Validation_Exception(SQUID_LANG_INVALID_TIME_END_LATER_START);
        else
            $time_range = $start['h'] . ':' . $start['m'] . '-' . $end['h'] . ':' . $end['m']; 
        
        if (! $this->is_loaded)
            $this->_load_config();

        // Implant into acl section
        //-------------------------

        $file = new File(self::FILE_CONFIG, TRUE);
        try {
            $replacement = "acl pcntime-$name time $formatted_dow " . $start['h'] . ":" .
                $start['m'] . "-" . $end['h'] . ":" . $end['m'] . "\n";
            $match = $file->ReplaceLines("/acl pcntime-$name time.*$/", $replacement);
            // TODO: find a better way to put this in the correct spot
            if (! $match) {
                $file->add_lines_after($replacement, Squid::REGEX_ACL_DELIMITER);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
        $this->is_loaded = FALSE;
        $this->config = array();
        
    }

    /**
     * Deletes an ACL.
     *
     * @param String $name acl name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_time_acl($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $type = 'allow';
        foreach ($this->config['http_access']['line'] as $acl) {
            if (ereg("^(deny|allow) pcngroup-$name .*$", $acl, $match)) {
                $type = $match[1];
                break;
            }
        }
        $this->_DeleteParameter("acl pcngroup-$name (proxy_auth|src|arp)");
        $this->_DeleteParameter("http_access $type pcngroup-$name");
    }

    /**
     * Deletes a time definition.
     *
     * @param String $name time name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_time_definition($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        # Delete any ACL's using this time definition
        foreach ($this->config['http_access']['line'] as $acl) {
            if (ereg("^(deny|allow) pcngroup-(.*) (pcntime-$name|!pcntime-$name).*$", $acl, $match)) {
                $type = $match[1];
                $aclname = $match[2];
                $this->_DeleteParameter("http_access $type pcngroup-$aclname");
                # User
                try {
                    $this->_DeleteParameter("acl pcngroup-$aclname proxy_auth");
                } catch (Exception $e) {
                    # Ignore
                }
                # IP
                try {
                    $this->_DeleteParameter("acl pcngroup-$aclname src");
                } catch (Exception $e) {
                    # Ignore
                }
                # MAC
                try {
                    $this->_DeleteParameter("acl pcngroup-$aclname arp");
                } catch (Exception $e) {
                    # Ignore
                }
            }
        }
        # Delete time definition
        $this->_DeleteParameter("acl pcntime-$name time");
    }

    /**
     * Bumps the priority of an ACL.
     *
     * @param String $name time name
     * @param Boolean $priority FALSE for bumping up priority, TRUE for bumping down
     *
     * @return void
     * @throws Engine_Exception
     */

    public function bump_time_acl_priority($name, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $last = '';

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            # Determine number of custom entries
            #foreach ($this->config['http_access']['line'] as $acl) {
            #    if (ereg("^(deny|allow) pcngroup-.*$", $acl))
            #}
            $counter = 1;
            foreach ($this->config['http_access']['line'] as $acl) {
                if (!ereg("^(deny|allow) pcngroup-.*$", $acl)) {
                    $counter++;
                    continue;
                }
                if (ereg("^(deny|allow) pcngroup-$name .*$", $acl)) {
                    #Found ACL
                    $file->DeleteLines("/^http_access $acl$/");
                    if ($priority == -1) {
                        $file->add_linesBefore('http_access ' . $acl . "\n", "/^" . $last . "$/");
                    } else {
                        $file->add_lines_after('http_access ' . $acl . "\n", "/^http_access " . $this->config['http_access']['line'][$counter + 1] . "$/");
                    }
                    $this->is_loaded = FALSE;
                    $this->config = array();
                    break;
                }
                $last = 'http_access ' . $acl;
                $counter++;
            }
        } catch (Exception $e) {
            $this->is_loaded = FALSE;
            $this->config = array();
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Upgrades configuration file
     *
     * @access private
     *
     * @return void
     */

    public function upgrade_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        // Ugh: to maintain a sane file that users can hack, we have to
        // use annoying file markers in the Squid configuration file.

        // Implant: http_access allow webconfig_lan
        //-----------------------------------------

        $httpaccess_implant = TRUE;
        $httpaccess_to_lan_implant = TRUE;

        if (isset($this->config["http_access"])) {
            foreach ($this->config["http_access"]["line"] as $line) {
                if (preg_match("/^allow\s+webconfig_lan\s*/", $line))
                    $httpaccess_implant = FALSE;
                else if (preg_match("/^allow\s+webconfig_to_lan\s*/", $line))
                    $httpaccess_to_lan_implant = FALSE;
            }
        }

        if ($httpaccess_implant) {
            $this->is_loaded = FALSE;
            $this->config = array();

            try {
                $file = new File(self::FILE_CONFIG, TRUE);
                $match = $file->ReplaceLines(
                    "/^http_access\s+deny\s+all$/i", 
                    "http_access allow webconfig_lan\nhttp_access deny all\n"
                );
            } catch (Exception $e) {
                throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
            }
        }

        if ($httpaccess_to_lan_implant) {
            $this->is_loaded = FALSE;
            $this->config = array();

            try {
                $file = new File(self::FILE_CONFIG, TRUE);
                $match = $file->ReplaceLines(
                    "/^http_access\s+deny\s+manager$/i", 
                    "http_access deny manager\nhttp_access allow webconfig_to_lan\n"
                );
            } catch (Exception $e) {
                throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
            }
        }

        // Implant: acl webconfig_lan src x.x.x.x
        //---------------------------------------

        $this->is_loaded = FALSE;
        $this->config = array();

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $file->DeleteLines("/^# webconfig: acl_/");
            $file->DeleteLines("/^acl webconfig_.*lan/");
            $match = $file->ReplaceLines(
                "/^acl\s+localhost\s+src/i", 
                "acl localhost src 127.0.0.0/8\n" .
                "# webconfig: acl_start\n" .
                "acl webconfig_lan src 192.168.0.0/16 10.0.0.0/8\n" .
                "acl webconfig_to_lan dst 192.168.0.0/16 10.0.0.0/8\n" .
                "# webconfig: acl_end\n"
            );
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        // Implant: http_port 192.168.2.2:3128
        //------------------------------------

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $file->LookupLine("/^# webconfig: http_port/");
        } catch (File_No_Match_Exception $e) {
            $file->add_lines("# webconfig: http_port_start\n# webconfig: http_port_end");
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the size in bytes.
     *
     * @param integer $size  size
     * @param string  $units units
     *
     * @access private
     * @return integer size in bytes
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _size_in_bytes($size, $units)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^\d+$/', $size))
            throw new Validation_Exception(lang('web_proxy_size_invalid'));

        if ($units == '') {
            return $size;
        } else if ($units == 'KB') {
            return $size * 1024;
        } else if ($units == 'MB') {
            return $size * 1024*1024;
        } else if ($units == 'GB') {
            return $size * 1024*1024*1024;
        } else {
            throw new Validation_Exception(lang('web_proxy_size_invalid'));
        }
    }

    /**
     * Loads configuration.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);

        $lines = $file->get_contents_as_array();

        $matches = array();

        foreach ($lines as $line) {
            if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line))
                continue;

            $items = preg_split("/\s+/", $line, 2);

            // ACL lists are ordered, so an index is required
            if (isset($this->config[$items[0]]))
                $this->config[$items[0]]['count']++;
            else
                $this->config[$items[0]]['count'] = 1;

            // $count is just to make code more readable
            $count = $this->config[$items[0]]['count'];

            $this->config[$items[0]]['line'][$count] = $items[1];
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Deletes a parameter.
     *
     * @param string $key parameter
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_parameter($key)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $this->is_loaded = FALSE;
        $this->config = array();

        $file = new File(self::FILE_CONFIG, TRUE);
        $match = $file->delete_lines("/^$key\s+/i");
    }

    /**
     * Generic set routine.
     *
     * @param string $key     key name
     * @param string $value   value for the key
     * @param string $offset  value offset
     * @param string $default default value for key if it does not exist
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value, $offset, $default)
    {
        if (! $this->is_loaded)
            $this->_load_config();

        // Do offset magic
        //----------------

        $fullvalue = '';

        if ($offset == self::CONSTANT_NO_OFFSET) {
            $fullvalue = $value;
        } else {
            if (isset($this->config[$key])) {
                $items = preg_split('/\s+/', $this->config[$key]['line'][1]);
                $items[$offset-1] = $value;
                foreach ($items as $item)
                    $fullvalue .= $item . ' ';
            } else {
                $fullvalue = $default;
            }
        }

        $this->is_loaded = FALSE;
        $this->config = array();

        // Update tag if it exists
        //------------------------

        $replacement = trim("$key $fullvalue"); // space cleanup
        $file = new File(self::FILE_CONFIG, TRUE);
        $match = $file->replace_one_line("/^$key\s*/i", "$replacement\n");

        if (!$match) {
            try {
                $file->add_lines_after("$replacement\n", "/^# {0,1}$key /");
            } catch (File_No_Match_Exception $e) {
                $file->add_lines_before("$replacement\n", "/^#/");
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for cache size.
     *
     * @param integer $size cache size
     *
     * @return string error message if cache size is invalid
     */

    public function validate_cache_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ( (!preg_match('/^\d+/', $size)) || ($size < 0) )
            return lang('web_proxy_cache_size_invalid');
    }

    /**
     * Validation routine for maximum file download size.
     *
     * @param integer $size maximum file download size
     *
     * @return string error message if maximum file download size is invalid
     */

    public function validate_maximum_file_download_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ( (!preg_match('/^\d+/', $size)) || ($size < 0) )
            return lang('web_proxy_maximum_file_download_size_invalid');
    }

    /**
     * Validation routine for maximum object size.
     *
     * @param integer $size maximum object size
     *
     * @return string error message if maximum object size is invalid
     */

    public function validate_maximum_object_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ( (!preg_match('/^\d+/', $size)) || ($size < 0) )
            return lang('web_proxy_maximum_object_size_invalid');
    }

    /**
     * Validation routine for a name.
     *
     * @param  string  $name  flexshare name
     *
     * @returns  boolean
     */

    public function is_valid_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([A-Za-z0-9\-\.\_]+)$/", $name))
            return TRUE;

        $this->AddValidationError(SQUID_LANG_ERRMSG_INVALID_NAME, __METHOD__, __LINE__);
        return FALSE;
    }
}
