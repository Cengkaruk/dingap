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
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Product as Product;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\base\Stats as Stats;
use \clearos\apps\content_filter\DansGuardian as DansGuardian;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Network_Status as Network_Status;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\web_proxy\Squid as Squid;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Product');
clearos_load_library('base/Shell');
clearos_load_library('base/Stats');
clearos_load_library('content_filter/DansGuardian');
clearos_load_library('network/Network');
clearos_load_library('network/Network_Status');
clearos_load_library('network/Network_Utils');
clearos_load_library('web_proxy/Squid');

// Exceptions
//-----------

use \Exception as Exception;
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
    const FILE_ACLS_CONFIG = '/etc/squid/squid_acls.conf';
    const FILE_HTTP_ACCESS_CONFIG = '/etc/squid/squid_http_access.conf';
    const FILE_ADZAPPER = '/usr/sbin/adzapper';
    const PATH_SPOOL = '/var/spool/squid';

    const CONSTANT_NO_OFFSET = -1;
    const CONSTANT_UNLIMITED = 0;

    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_UNKNOWN = 'unknown';
    
    const DEFAULT_MAX_FILE_DOWNLOAD_SIZE = 0;
    const DEFAULT_MAX_OBJECT_SIZE = 4194304;
    const DEFAULT_REPLY_BODY_MAX_SIZE_VALUE = 'none';
    const DEFAULT_CACHE_SIZE = 104857600;
    const DEFAULT_CACHE_DIR_VALUE = 'ufs /var/spool/squid 100 16 256';

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
     * Bumps the priority of an ACL.
     *
     * @param string  $name     time name
     * @param integer $priority use value greater than zero to bump up
     *
     * @return void
     * @throws Engine_Exception
     */

    public function bump_time_acl_priority($name, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);
        $file = new File(self::FILE_HTTP_ACCESS_CONFIG, TRUE);

        $last = '';
        $counter = 1;

        foreach ($config['http_access']['line'] as $acl) {
            if (!preg_match("/^(deny|allow) cleargroup-/", $acl)) {
                $counter++;
                continue;
            }

            if (preg_match("/^(deny|allow) cleargroup-$name\s+/", $acl)) {
                // Found ACL
                $file->delete_lines("/^http_access $acl$/");

                if ($priority > 0)
                    $file->add_lines_before('http_access ' . $acl . "\n", "/^" . $last . "$/");
                else
                    $file->add_lines_after('http_access ' . $acl . "\n", "/^http_access " . $config['http_access']['line'][$counter + 1] . "$/");

                break;
            }

            $last = 'http_access ' . $acl;
            $counter++;
        }
    }

    /**
     * Deletes the proxy cache.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function clear_cache()
    {
        clearos_profile(__METHOD__, __LINE__);

        $was_running = $this->get_running_state();

        if ($was_running)
            $this->set_running_state(FALSE);

        $shell = new Shell();
        $shell->execute('/bin/mv', '/var/spool/squid /var/spool/squid.delete', TRUE);

        $folder = new Folder(self::PATH_SPOOL);
        $folder->create('squid', 'squid', '0750');

        if ($was_running)
            $this->set_running_state(TRUE);

        $shell->execute('/bin/rm', '-rf /var/spool/squid.delete', TRUE);
    }

    /**
     * Deletes an ACL.
     *
     * @param string $name acl name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_time_acl($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);

        $type = 'allow';

        foreach ($config['http_access']['line'] as $acl) {
            if (preg_match("/^(deny|allow) cleargroup-$name .*$/", $acl, $match)) {
                $type = $match[1];
                break;
            }
        }

        $this->_delete_parameter("acl cleargroup-$name (external system_group|src|arp)", self::FILE_ACLS_CONFIG);
        $this->_delete_parameter("http_access $type cleargroup-$name", self::FILE_HTTP_ACCESS_CONFIG);
    }

    /**
     * Deletes a time definition.
     *
     * @param string $name time name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_time_definition($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);

        //  Delete any ACL's using this time definition
        foreach ($config['http_access']['line'] as $acl) {
            if (preg_match("/^(deny|allow) cleargroup-(.*) (cleartime-$name|!cleartime-$name).*$/", $acl, $match)) {
                $type = $match[1];
                $aclname = $match[2];
                $this->_delete_parameter("http_access $type cleargroup-$aclname", self::FILE_HTTP_ACCESS_CONFIG);

                // User
                try {
                    $this->_delete_parameter("acl cleargroup-$aclname external system_group", self::FILE_ACLS_CONFIG);
                } catch (Exception $e) {
                    // Ignore
                }

                // IP
                try {
                    $this->_delete_parameter("acl cleargroup-$aclname src", self::FILE_ACLS_CONFIG);
                } catch (Exception $e) {
                    // Ignore
                }

                // MAC
                try {
                    $this->_delete_parameter("acl cleargroup-$aclname arp", self::FILE_ACLS_CONFIG);
                } catch (Exception $e) {
                    // Ignore
                }
            }
        }

        // Delete time definition
        $this->_delete_parameter("acl cleartime-$name time", self::FILE_ACLS_CONFIG);
    }

    /**
     * Returns allow/deny mapping.
     *
     * @return array a mapping of access types
     */

    public function get_access_types()
    {
        $type = array(
            'allow' => lang('web_proxy_allow'),
            'deny' => lang('web_proxy_deny')
        );

        return $type;
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

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);

        $file = new File(self::FILE_ACLS_CONFIG, TRUE);

        foreach ($config['http_access']['line'] as $line => $acl) {
            if (!preg_match("/^(deny|allow) cleargroup-.*$/", $acl))
                continue;

            $temp = array();
            $parts = explode(' ', $acl);
            $temp['type'] = $parts[0];
            $temp['name'] = substr($parts[1], 11, strlen($parts[1]));
            $temp['logic'] = !preg_match("/^!/", $parts[2]);

            try {
                list($dow, $tod) = preg_split('/ /', $file->lookup_value("/^acl " . preg_replace("/^!/", "", $parts[2]) . " time/"));
            } catch (File_No_Match_Exception $e) {
                continue;
            } 

            $temp['time'] = preg_replace("/.*cleartime-/", "", $parts[2]);
            $temp['dow'] = $dow;
            $temp['tod'] = $tod;
            $temp['groups'] = '';

            try {
                $temp['groups'] = trim($file->lookup_value("/^acl cleargroup-" . $temp['name'] . " external system_group/"));
                $temp['ident'] = 'group';
            } catch (File_No_Match_Exception $e) {
                $temp['groups'] = '';
            }

            try {
                $temp['ips'] = trim($file->lookup_value("/^acl cleargroup-" . $temp['name'] . " src/"));
                $temp['ident'] = 'src';
            } catch (File_No_Match_Exception $e) {
                $temp['ips'] = '';
            }

            try {
                $temp['macs'] = trim($file->lookup_value("/^acl cleargroup-" . $temp['name'] . " arp/"));
                $temp['ident'] = 'arp';
            } catch (File_No_Match_Exception $e) {
                $temp['macs'] = '';
            }

            $list[] = $temp;
        }

        return $list;
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
     * Returns Internet connection status.
     *
     * @return string connection status
     */

    public function get_connection_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $network_status = new Network_Status();
        $status = $network_status->get_connection_status();

        if ($status === Network_Status::STATUS_ONLINE)
            return self::STATUS_ONLINE;
        else if ($status === Network_Status::STATUS_OFFLINE)
            return self::STATUS_OFFLINE;
        else
            return self::STATUS_UNKNOWN;
    }

    /**
     * Returns Internet connection status message.
     *
     * @return string connection status message
     */

    public function get_connection_status_message()
    {
        clearos_profile(__METHOD__, __LINE__);

        $status = $this->get_connection_status();

        if ($status === self::STATUS_ONLINE)
            return lang('web_proxy_online');
        else if ($status === self::STATUS_OFFLINE)
            return lang('web_proxy_offline');
        else
            return lang('web_proxy_unavailable');
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
     * Returns the days of the week options.
     *
     * @return array 
     */

    public function get_days_of_week()
    {
        clearos_profile(__METHOD__, __LINE__);

        $dow = array(
            'M' => lang('base_monday'),
            'T' => lang('base_tuesday'),
            'W' => lang('base_wednesday'),
            'H' => lang('base_thursday'),
            'F' => lang('base_friday'),
            'A' => lang('base_saturday'),
            'S' => lang('base_sunday')
        );

        return $dow;
    }

    /**
     * Returns method of identification mapping.
     *
     * @return array a mapping of ID types
     */

    public function get_identification_types()
    {
        $type = array(
            'group' => lang('web_proxy_group'),
            'src' => lang('web_proxy_ip'),
            'arp' => lang('web_proxy_mac')
        );

        return $type;
    }

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
     * Returns all time-based ACL definitions.
     *
     * @return array a list of time-based ACL definitions.
     * @throws Engine_Exception
     */

    public function get_time_definition_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        $config = $this->_load_configlet(self::FILE_ACLS_CONFIG);

        foreach ($config['acl']['line'] as $line => $acl) {
            if (!preg_match("/^cleartime-.*$/", $acl))
                continue;

            $temp = array();
            $parts = explode(' ', $acl);
            $temp['name'] = substr($parts[0], 10, strlen($parts[0]));
            $temp['dow'] = str_split($parts[2]);
            list($temp['start'], $temp['end']) = explode('-', $parts[3]);

            $list[] = $temp;
        }

        return $list;
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
     * Sets Adzapper state.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_adzapper_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_bool($state))
            throw new Validation_Exception(lang('base_invalid'));

        if ($state)
            $this->_set_parameter("redirect_program", self::FILE_ADZAPPER, self::CONSTANT_NO_OFFSET, "");
        else
            $this->_delete_parameter("redirect_program");
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

        $this->set_basic_authentication_info_default();

        if ($state) {
            $this->_set_parameter('http_access allow webconfig_lan', 'password', self::CONSTANT_NO_OFFSET, '');
            $this->_set_parameter('http_access allow localhost', 'password', self::CONSTANT_NO_OFFSET, '');
        } else {
            $this->_set_parameter('http_access allow webconfig_lan', '', self::CONSTANT_NO_OFFSET, '');
            $this->_set_parameter('http_access allow localhost', '', self::CONSTANT_NO_OFFSET, '');
        }

        // KLUDGE: DansGuardian does not like having authorization plugins 
        // enabled if Squid is not configured with authentication.  

        // FIXME
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
     * @return void
     * @throws Engine_Exception
     */

    public function set_basic_authentication_info_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        $product = new Product();
        $name = $product->get_name();
        $realm = $name . ' - ' . lang('web_proxy_web_proxy');

        $children = '25';

        try {
            $stats = new Stats();
            $memory_stats = $stats->get_memory_stats();

            $multiplier = floor($memory_stats['memory_total'] / 1000000) + 1;
            $children = $children * $multiplier;
        } catch (Exception $e) {
            // not fatal
        }

        $this->_set_parameter('auth_param basic children', $children, self::CONSTANT_NO_OFFSET, '');
        $this->_set_parameter('auth_param basic realm', $realm, self::CONSTANT_NO_OFFSET, '');
        $this->_set_parameter('auth_param basic credentialsttl', '2 hours', self::CONSTANT_NO_OFFSET, '');
        $this->_set_parameter('auth_param basic program', '/usr/lib/squid/pam_auth', self::CONSTANT_NO_OFFSET, '');
        $this->_set_parameter('acl password proxy_auth', 'REQUIRED', self::CONSTANT_NO_OFFSET, '');
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

        if ($size == 'none') {
            $this->_set_parameter('reply_body_max_size', $size, self::CONSTANT_NO_OFFSET, self::DEFAULT_REPLY_BODY_MAX_SIZE_VALUE);
        } else {
            $this->_set_parameter('reply_body_max_size', "$size bytes", self::CONSTANT_NO_OFFSET, self::DEFAULT_REPLY_BODY_MAX_SIZE_VALUE);
        }
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
     * Adds (or updates) a time-based ACL.
     *
     * @param string  $name       ACL name
     * @param string  $type       ACL type (allow or deny)
     * @param string  $time       time definition
     * @param boolean $time_logic TRUE if within time definition, FALSE if NOT within
     * @param array   $addgroup   group to apply ACL
     * @param array   $addips     array containing IP addresses or network notation to apply ACL
     * @param array   $addmacs    array containing MAC addresses to apply ACL
     * @param boolean $update     TRUE if we are updating an existing entry
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_acl($name, $type, $time, $time_logic, $addgroup, $addips, $addmacs, $update = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
 
        Validation_Exception::is_valid($this->validate_name($name));

        $ips = '';
        $macs = '';

        // Check for existing
        if (!$update) {
            $acls = $this->get_acl_list();
            foreach ($acls as $acl) {
                if ($name == $acl['name'])
                    throw new Validation_Exception(lang('web_proxy_duplicate_acl'));
            }
        }

        if ($type != 'allow' && $type != 'deny')
            throw new Validation_Exception(lang('web_proxy_type_invalid'));

        $timelist = $this->get_time_definition_list();
        $timevalid = FALSE;

        foreach ($timelist as $timename) {
            if ($time == $timename['name']) {
                $timevalid = TRUE;
                break;
            }
        }
            
        if (!$timevalid)
            throw new Validation_Exception(lang('web_proxy_time_definition_invalid'));

        $network = new Network();

        foreach ($addips as $ip) {
            if (empty($ip))
                continue;
            $ip = trim($ip);

            if (preg_match("/^(.*)-(.*)$/i", trim($ip), $match)) {
                if (! Network_Utils::is_valid_ip(trim($match[1])))
                    throw new Validation_Exception(lang('nework_ip_invalid'));
                if (! Network_Utils::is_valid_ip(trim($match[2])))
                    throw new Validation_Exception(lang('network_ip_invalid'));
            } else {
                if (! Network_Utils::is_valid_ip(trim($ip)))
                    throw new Validation_Exception(lang('network_ip_invalid'));
            }

            $ips .= ' ' . trim($ip);
        }

        foreach ($addmacs as $mac) {
            if (empty($mac))
                continue;
            $mac = trim($mac);

            if (! Network_Utils::is_valid_mac($mac))
                throw new Validation_Exception(lang('network_mac_invalid'));

            $macs .= ' ' . $mac;
        }

        // Implant into acl section
        //-------------------------

        $file = new File(self::FILE_ACLS_CONFIG, TRUE);

        if (strlen($addgroup) > 0) {
            // Group based
            $replacement = "acl cleargroup-$name external system_group " . $addgroup . "\n";
            $match = $file->replace_lines("/acl cleargroup-$name external system_group.*$/", $replacement);

            if (! $match)
                $file->add_lines($replacement);
        } else if (strlen($ips) > 0) {
            // IP based
            $replacement = "acl cleargroup-$name src " . trim($ips) . "\n";
            $match = $file->replace_lines("/acl cleargroup-$name src .*$/", $replacement);

            if (! $match)
                $file->add_lines($replacement);
        } else if (strlen($macs) > 0) {
            // IP based
            $replacement = "acl cleargroup-$name arp " . trim($macs) . "\n";
            $match = $file->replace_lines("/acl cleargroup-$name arp .*$/", $replacement);

            if (! $match)
                $file->add_lines($replacement);
        } else {
            throw new Engine_Exception(lang('web_proxy_empty_id_array'));
        }

        $file = new File(self::FILE_HTTP_ACCESS_CONFIG);

        try {
            $replacement = "http_access $type cleargroup-$name " . ($time_logic ? "" : "!") . "cleartime-$time\n";
            $match = $file->replace_lines("/http_access (allow|deny) cleargroup-$name .*$/", $replacement);

            if (! $match)
                $file->add_lines("http_access $type cleargroup-$name " . ($time_logic ? "" : "!") . "cleartime-$time\n");

            // Check for follow_x_forwarded_for directives
            if (strlen($ips) > 0) {
                /* FIXME
                try {
                    $file->lookup_line("/^follow_x_forwarded_for allow localhost$/");
                } catch (File_No_Match_Exception $e) {
                    $lines = "follow_x_forwarded_for allow localhost\nfollow_x_forwarded_for deny localhost\n";
                    $file->add_lines_before($lines, "/http_access " . str_replace("/", "\\/", $config['http_access']['line'][1]) . "/i");
                } catch (Exception $e) {
                    throw new Engine_Exception(clearos_exception_message($e));
                }
                */

                // Check for DG config
                // FIXME
                /*try {
                    if (file_exists(COMMON_CORE_DIR . "/api/DansGuardian.class.php")) {
                        $dg = new Daemon("dansguardian");
                        if ($dg->get_running_state()) {
                            require_once('DansGuardian.class.php');
                            try {
                                $file = new File(DansGuardian::FILE_CONFIG);
                                if (eregi('off', $file->lookup_value("/^forwardedfor\s=/"))) {
                                    if (!$file->replace_lines("/^forwardedfor\s=/", "forwardedfor = on\n"))
                                        $file->add_lines("forwardedfor = on\n");
                                    $dg->restart();
                                }
                            } catch (Exception $e) {
                                // Ignore
                            }
                        }
                    }

                    if (file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php")) {
                        $dgav = new Daemon("dansguardian-av");
                        if ($dgav->get_running_state()) {
                            require_once('DansGuardianAv.class.php');
                            try {
                                $file = new File(DansGuardian::FILE_CONFIG);
                                if (eregi('off', $file->lookup_value("/^forwardedfor\s=/"))) {
                                    if (!$file->replace_lines("/^forwardedfor\s=/", "forwardedfor = on\n"))
                                        $file->add_lines("forwardedfor = on\n");
                                    $dgav->restart();
                                }
                            } catch (Exception $e) {
                                // Ignore
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ignore
                }
                */
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Adds (or updates) a time definition for use with an ACL.
     *
     * @param string  $name   time name
     * @param array   $dow    an array of days of week
     * @param string  $start  start hour/min
     * @param string  $end    end hour/min
     * @param boolean $update TRUE if we are updating an existing entry
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_definition($name, $dow, $start, $end, $update = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
 
        // Validate
        // --------
        Validation_Exception::is_valid($this->validate_name($name));
        // Check for existing
        if (!$update) {
            $times = $this->get_time_definition_list();
            foreach ($times as $time) {
                if ($name == $time['name'])
                    throw new Validation_Exception(lang('web_proxy_duplicate_time'));
            }
        }

        Validation_Exception::is_valid($this->validate_day_of_week($dow));

        $formatted_dow = implode('', array_values($dow));

        if (strtotime($start) > strtotime($end))
            throw new Validation_Exception(lang('web_proxy_time_end_later_start_invalid'));
        else
            $time_range = $start . '-' . $end; 
        
        if (! $this->is_loaded)
            $this->_load_config();

        // Implant into acl section
        //-------------------------

        $file = new File(self::FILE_ACLS_CONFIG, TRUE);

        $replacement = "acl cleartime-$name time $formatted_dow " . $time_range . "\n";
        $match = $file->replace_lines("/acl cleartime-$name time.*$/", $replacement);

        if (! $match)
            $file->add_lines($replacement);

        $this->is_loaded = FALSE;
        $this->config = array();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes a parameter.
     *
     * @param string $key parameter
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_parameter($key, $config = self::FILE_CONFIG)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $this->is_loaded = FALSE;
        $this->config = array();

        $file = new File($config, TRUE);

        $match = $file->delete_lines("/^$key\s+/i");
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
     * Loads configlet.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_configlet($configlet)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($configlet, TRUE);

        $lines = $file->get_contents_as_array();

        $matches = array();
        $config = array();

        foreach ($lines as $line) {
            if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line))
                continue;

            $items = preg_split("/\s+/", $line, 2);

            // ACL lists are ordered, so an index is required
            if (isset($config[$items[0]]))
                $config[$items[0]]['count']++;
            else
                $config[$items[0]]['count'] = 1;

            // $count is just to make code more readable
            $count = $config[$items[0]]['count'];

            $config[$items[0]]['line'][$count] = $items[1];
        }

        return $config;
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
        clearos_profile(__METHOD__, __LINE__);

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
     * Validation routine for day of week.
     *
     * @param string $dow name
     *
     * @return boolean
     */

    public function validate_day_of_week($dow)
    {
        clearos_profile(__METHOD__, __LINE__);
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

        if ($size === 'none')
            return;

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
     * @param string $name name
     *
     * @return boolean
     */

    public function validate_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([A-Za-z0-9\-\.\_]+)$/", $name))
            return lang('web_proxy_invalid_name');
    }

    /**
     * Validation routine for time acl definition.
     *
     * @param int $time time index
     *
     * @return boolean
     */

    public function validate_time_acl($time)
    {
        clearos_profile(__METHOD__, __LINE__);
        if ((int)$time < 0)
            return lang('web_proxy_invalid_time_definition');
    }
}
