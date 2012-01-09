<?php

/**
 * DansGuardian content filter class.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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

namespace clearos\apps\content_filter;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('content_filter');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\groups\Group_Manager_Factory as Group_Manager;

clearos_load_library('groups/Group_Manager_Factory');

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\File_Types as File_Types;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/File_Types');
clearos_load_library('base/Folder');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('network/Network_Utils');

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
 * DansGuardian content filter class.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class DansGuardian extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    const BASE_PATH= '/etc/dansguardian-av';
    const PATH_BLACKLISTS = '/etc/dansguardian-av/lists/blacklists';
    const PATH_PHRASELISTS = '/etc/dansguardian-av/lists/phraselists';
    const PATH_LOCALE = '/etc/dansguardian-av/languages';
    const PATH_LOGS = '/var/log/dansguardian';
    const FILE_CONFIG = '/etc/dansguardian-av/dansguardian.conf';
    const FILE_CONFIG_FILTER_GROUP = '/etc/dansguardian-av/dansguardianf%d.conf';
    const FILE_EXTENSIONS_LIST = '/etc/dansguardian-av/lists/bannedextensionlist';
    const FILE_MIME_LIST = '/etc/dansguardian-av/lists/bannedmimetypelist';
    const FILE_PHRASE_LIST = '/etc/dansguardian-av/lists/weightedphraselist';
    const FILE_SYSTEM_GROUPS = '/etc/dansguardian-av/lists/filtergroupslist';

    const REPORTING_LEVEL_STEALTH = -1;
    const REPORTING_LEVEL_SHORT = 1;
    const REPORTING_LEVEL_FULL = 2;
    const REPORTING_LEVEL_CUSTOM = 3;

    const MAX_FILTER_GROUPS = 9;
    const MAX_NAUGHTYNESS_LIMIT = 99999;

    ///////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////

    var $policy_keys;
    var $locales;

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Dansguardian constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('dansguardian-av');

        $this->policy_keys = array(
            'groupmode', 
            'groupname', 
            'bypass',
            'disablecontentscan',
            'bannedextensionlist',
            'bannedsitelist',
            'bannedurllist',
            'bannedmimetypelist',
            'exceptionsitelist',
            'exceptionurllist',
            'naughtynesslimit',
            'picsfile',
            'weightedphraselist',
            'greysitelist',
            'greyurllist',
            'deepurlanalysis',
            'reportinglevel',
            'blockdownloads',
            'exceptionextensionlist',
            'exceptionmimetypelist'
        );

        // Dansguardian does use locale codes...
        // TODO: Keep these up-to-date with app-language
        $this->locales['czech'] = 'cs_CZ';
        $this->locales['danish'] = 'da_DK';
        $this->locales['german'] = 'de_DE';
        $this->locales['mxspanish'] = 'es_ES';
        $this->locales['spanish'] = 'es_ES';
        $this->locales['french'] = 'fr_FR';
        $this->locales['italian'] = 'it_IT';
        $this->locales['dutch'] = 'nl_NL';
        $this->locales['polish'] = 'pl_PL';
        $this->locales['portuguese'] = 'pt_BR';
        $this->locales['russian-1251'] = 'ru_RU';
        $this->locales['swedish'] = 'sv_SE';
        $this->locales['turkish'] = 'tr_TR';
        $this->locales['chinesebig5'] = 'zh_CN';
        $this->locales['ukenglish'] = 'en_US';
    }

    /**
     * Adds IP to banned list.
     *
     * @param string $ip IP address
     *
     * @return void
     */

    public function add_banned_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_items_by_key('bannediplist', $ip);
    }

    /**
     * Adds web site or URL to banned list.
     *
     * @param string  $siteurl website or URL
     * @param integer $policy  policy ID
     *
     * @return void
     */

    public function add_banned_site_and_url($siteurl, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_add_items_by_key('bannedsitelist', $sites, $policy);

        if (count($urls) > 0)
            $this->_add_items_by_key('bannedurllist', $urls, $policy);
    }

    /**
     * Adds IP to exception list.
     *
     * @param string $ip IP address
     *
     * @return void
     */

    public function add_exception_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_items_by_key('exceptioniplist', $ip);
    }

    /**
     * Adds web site or URL to exception list.
     *
     * @param string  $siteurl site or URL
     * @param integer $policy  policy ID
     *
     * @return void
     */

    public function add_exception_site_and_url($siteurl, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_add_items_by_key('exceptionsitelist', $sites, $policy);

        if (count($urls) > 0)
            $this->_add_items_by_key('exceptionurllist', $urls, $policy);
    }

    /**
     * Adds web site or URL to gray list.
     *
     * @param string  $siteurl site or URL
     * @param integer $policy  policy ID
     *
     * @return void
     */

    public function add_gray_site_and_url($siteurl, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_add_items_by_key('greysitelist', $sites, $policy);

        if (count($urls) > 0)
            $this->_add_items_by_key('greyurllist', $urls, $policy);
    }

    /**
     * Adds a new filter policy.
     *
     * @param string $name  policy name
     * @param string $group system group
     *
     * @return integer new filter policy ID
     */

    public function add_policy($name, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_policy_name($name));
        Validation_Exception::is_valid($this->validate_group($group));

        if ($this->exists_policy_name($name))
            throw new Engine_Exception(lang('content_filter_policy_already_exists'));

        $folder = new Folder(self::BASE_PATH);
        $files = $folder->get_listing();

        // Look for next available filter group ID
        //----------------------------------------

        $id = 2;
        $ids = array();

        foreach ($files as $file) {
            if (sscanf($file, 'dansguardianf%d', $id) != 1)
                continue;
            $ids[] = $id;
        }

        if (count($ids) == self::MAX_FILTER_GROUPS) {
            throw new Engine_Exception(lang('content_filter_maximum_policies_already_configured'));
        } else if (count($ids)) {
            sort($ids);
            $id = end($ids) + 1;
        }

        // Create new filter policy by copying the default
        //-----------------------------------------------

        $file = new File(self::BASE_PATH . '/dansguardianf1.conf', TRUE);

        $file->copy_to(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id));

        $file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), TRUE);

        if ($file->replace_lines('/^groupname.*$/', "groupname = '$name'\n", 1) != 1)
            $file->add_lines_after("groupname = '$name'\n", '/^#groupname.*$/');

        // Default the group mode to "filtered"
        $file->replace_lines('/^groupmode\s*=.*/', "groupmode = 1\n", 1);

        foreach ($this->policy_keys as $key) {
            if (strstr($key, 'list') === FALSE)
                continue; 

            $value = str_replace(array('\'', '"'), '', $file->lookup_value("/^$key\s*=\s*/"));
            $file->replace_one_line_by_pattern("/^$key\s*=.*$/", sprintf("%s = '%s%d'\n", $key, $value, $id));

            if (!in_array($key, array('bannedsitelist', 'bannedurllist', 'weightedphraselist', 'exceptionfilesitelist'))) {
                $emptylist = new File(sprintf('%s%d', $value, $id));

                if (!$emptylist->exists())
                    $emptylist->create('root', 'root', '0644');

                continue;
            }

            $default = new File($value);

            if ($default->exists()) {
                $default->copy_to(sprintf('%s%d', $value, $id));
            } else {
                $emptylist = new File(sprintf('%s%d', $value, $id));
                $emptylist->create('root', 'root', '0644');
            }
        }

        // Add system group to filtergroupslist
        //-------------------------------------

        $file = new File(self::FILE_SYSTEM_GROUPS);
        $file->add_lines("$group\n");

        return $id;
    }


    /** 
     * Removes unavailable blacklists from configuration files.
     *
     * @return array list of bad blacklists configurations
     */

    public function clean_blacklists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $groups = $this->GetFilterGroups();
        $blacklists = $this->GetPossibleBlacklists();
        $baddetails = array();

        foreach ($groups as $groupid => $info) {
            $configured = $this->GetBlacklists($groupid);

            $available = array();

            foreach ($blacklists as $listid => $listinfo)
                $available[] = $listinfo['name'];

            $badlist = array();
            $cleanlist = array();

            foreach ($configured as $list) {
                if (in_array($list, $available)) {
                    $cleanlist[] = $list;
                } else {
                    $badlist[] = $list;
                    $baddetails[] = "Group $groupid: $list";
                }
            }

            $badlist = array_unique($badlist);
            $cleanlist = array_unique($cleanlist);

            if (count($badlist) > 0)
                $this->SetBlacklists($cleanlist, $groupid);
        }

        return $baddetails;
    }

    /** 
     * Removes unavailable weighted phrase lists from configuration files.
     *
     * @return array list of bad phrase list configurations
     */

    public function clean_phrase_lists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $groups = $this->GetFilterGroups();
        $phrase_lists = $this->GetPossibleWeightedPhrase();
        $baddetails = array();

        foreach ($groups as $groupid => $info) {
            $configured = $this->GetWeightedPhraseLists($groupid, TRUE);
            $available = array();

            foreach ($phrase_lists as $listid => $listinfo)
                $available[] = $listinfo['name'];

            $cleanlist = array();
            $badlist = array();

            foreach ($configured as $list) {
                $file = new File(self::PATH_PHRASELISTS . "/" . $list);
                if ($file->exists()) {
                    $cleanlist[] = preg_replace("/\/.*/", "", $list);
                } else {
                    $badlist[] = $list;
                    $baddetails[] = "Group $groupid: $list";
                }
            }

            $badlist = array_unique($badlist);
            $cleanlist = array_unique($cleanlist);

            if (count($badlist) > 0)
                $this->SetWeightedPhraseLists($cleanlist, $groupid);
        }

        return $baddetails;
    }

    /**
     * Deletes an IP from the banned IP list.
     *
     * @param string $ip IP address
     *
     * @return void
     */

    public function delete_banned_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_delete_items_by_key('bannediplist', $ip);
    }

    /**
     * Deletes item from banned sites and URLs.
     *
     * @param string  $siteurl site or URL
     * @param integer $policy  policy ID
     *
     * @return void
     */

    public function delete_banned_site_and_url($siteurl, $policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_delete_items_by_key('bannedsitelist', $sites, $policy);

        if (count($urls) > 0)
            $this->_delete_items_by_key('bannedurllist', $urls, $policy);
    }

    /**
     * Deletes IP from the exception list.
     *
     * @param string $ip IP address
     *
     * @return void
     */

    public function delete_exception_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_delete_items_by_key('exceptioniplist', $ip);
    }

    /**
     * Deletes site or URL from the exception list.
     *
     * @param string  $siteurl site or URL
     * @param integer $policy  policy ID
     *
     * @return void
     */

    public function delete_exception_site_and_url($siteurl, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_delete_items_by_key('exceptionsitelist', $sites, $policy);

        if (count($urls) > 0)
            $this->_delete_items_by_key('exceptionurllist', $urls, $policy);
    }

    /**
     * Deletes site or URL from the gray list.
     *
     * @param string  $siteurl site or URL
     * @param integer $policy  policy ID
     *
     * @return void
     */

    public function delete_gray_site_and_url($siteurl, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_delete_items_by_key('greysitelist', $sites, $policy);

        if (count($urls) > 0)
            $this->_delete_items_by_key('greyurllist', $urls, $policy);
    }

    /**
     * Deletes a policy.
     *
     * @param integer $policy policy ID
     *
     * @return void
     */

    public function delete_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete policy configuration files
        //----------------------------------

        $policy_config = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $policy), TRUE);

        foreach ($this->policy_keys as $key) {
            if (strstr($key, 'list') === FALSE)
                continue;

            $value = str_replace(array('\'', '"'), '', $policy_config->lookup_value("/^$key\s*=\s*/"));
            $fglist = new File($value);

            if ($fglist->exists())
                $fglist->delete();
        }

        $policy_config->delete();

        // Delete system group entry
        //-------------------------

        $groups = new File(self::FILE_SYSTEM_GROUPS);
        $lines = $groups->get_contents_as_array();

        $new_list = '';
        $count = 0;

        foreach ($lines as $line) {
            if (preg_match('/^#/', $line))
                continue;

            $count++;

            if ($count != $policy)
                $new_list .= "$line\n";
        }

        $groups->delete();
        $groups->create('root', 'root', '0644');
        $groups->add_lines($new_list);

        // Fix gaps in the sequence IDs
        //-----------------------------

        $this->_sequence_policies();
    }

    /**
     * Checks existence of a policy name.
     *
     * @param string $name policy name
     *
     * @return boolean TRUE if policy name exists
     * @throws Engine_Exception
     */

    public function exists_policy_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $policies = $this->get_policies();

        foreach ($policies as $id => $details) {
            if ($details['groupname'] === $name)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Returns the access denied page.
     *
     * @return string access denited URL
     */

    public function get_access_denied_url()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('accessdeniedaddress');
    }

    /**
     * Returns the banned extension list.
     *
     * @param integer $policy policy ID
     *
     * @return array list of banned extensions
     */

    public function get_banned_file_extensions($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('bannedextensionlist', $policy);
    }

    /**
     * Returns the list of banned URLs and sites.
     *
     * @param integer $policy policy ID
     *
     * @return array list of banned URLs and sites
     */

    public function get_banned_sites_and_urls($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $raw_list = $this->_get_banned_sites_and_urls($policy);

        $ban_list = array();

        // Skip blanket block, IP block
        foreach ($raw_list as $site) {
            if (($site != '**') && ($site != '*ip'))
                $ban_list[] = $site;
        }

        return $ban_list;
    }

    /**
     * Returns the list of banned IPs.
     *
     * @return array list of banned IPs
     */

    public function get_banned_ips()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('bannediplist');
    }

    /**
     * Returns the banned MIME list.
     *
     * @param integer $policy policy ID
     *
     * @return array list of banned MIMEs
     */

    public function get_banned_mime_types($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('bannedmimetypelist', $policy);
    }

    /**
     * Returns activated blacklists.
     *
     * @param integer $policy policy ID
     *
     * @return array list of activated blacklists
     */

    public function get_blacklists($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $bannedtypes = array();
        $bannedtypes[] = 'bannedsitelist';
        $bannedtypes[] = 'bannedurllist';

        $active = array();

        foreach ($bannedtypes as $type) {
            $bannedfile = $this->_get_filename_by_key($type, $policy);

            $file = new File($bannedfile);

            if (! $file->exists())
                continue;

            $contents = $file->get_contents();

            $lines = explode("\n", $contents);

            foreach ($lines as $line) {
                if (preg_match("/^\.Include/", $line)) {
                    $listname = preg_replace("/.*blacklists\//", '', $line);
                    $listname = preg_replace("/\/.*/", '', $listname);
                    $active[] = $listname;
                }
            }
        }

        ksort($active); // remove duplicates

        return $active;
    }

    /**
     * Returns blanket block policy.
     *
     * @param integer $policy policy ID
     *
     * @return boolean TRUE if blanket block policy is enabled.
     */

    public function get_blanket_block($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ban_list = $this->_get_banned_sites_and_urls($policy);

        if (in_array('**', $ban_list))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns block IP domains policy.
     *
     * @param integer $policy policy ID
     *
     * @return boolean TRUE if block IP domains policy is enabled.
     */

    public function get_block_ip_domains($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ban_list = $this->_get_banned_sites_and_urls($policy);

        if (in_array('*ip', $ban_list))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns the list of IPs in the exception list.
     *
     * @return array list of IPs that bypass the filter
     */

    public function get_exception_ips()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('exceptioniplist');
    }

    /**
     * Returns the sites and URLs in the exception list.
     *
     * @param integer $policy policy ID
     *
     * @return array list of urls and sites in the exception list
     */

    public function get_exception_sites_and_urls($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sitelist = array();
        $urllist = array();
        $list = array();

        $sitelist = $this->_get_configuration_data_by_key('exceptionsitelist', $policy);
        $urllist = $this->_get_configuration_data_by_key('exceptionurllist', $policy);

        $list = array_merge($urllist, $sitelist);

        return $list;
    }

    /**
     * Returns the sites and URLs in the gray list.
     *
     * @param integer $policy policy ID
     *
     * @return array list of urls and sites in the gray list
     */

    public function get_gray_sites_and_urls($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sitelist = array();
        $urllist = array();
        $list = array();

        $sitelist = $this->_get_configuration_data_by_key('greysitelist', $policy);
        $urllist = $this->_get_configuration_data_by_key('greyurllist', $policy);

        $list = array_merge($urllist, $sitelist);

        return $list;
    }

    /**
     * Returns the filter port (default 8080).
     *
     * @return integer filter port number
     */

    public function get_filter_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('filterport');
    }

    /**
     * Returns locale.
     *
     * @param integer $policy policy ID
     *
     * @return integer the current locale
     */

    public function get_locale($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $locale = $this->_get_configuration_value('language', $policy);

        if ($this->locales[$locale])
            return $this->locales[$locale];
        else
            return 'en_US';
    }

    /**
     * Returns naughtyness level.
     *
     * @param integer $policy policy ID
     *
     * @return integer current naughtyness limit
     */

    public function get_naughtyness_limit($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('naughtynesslimit', $policy);
    }

    /**
     * Returns the weight phrase list.
     *
     * @param integer $policy  policy ID
     * @param boolean $details flag get full list including languagues
     *
     * @return array a list of the weight phrases
     */

    public function get_phrase_lists($policy = 1, $details = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rawlines = $this->_get_configuration_data_by_key('weightedphraselist', $policy);
        $list = array();

        foreach ($rawlines as $line) {
            if (preg_match('/^.Include/', $line)) {
                $listitem = preg_replace("/.*\/phraselists\//", '', $line);

                if ($details)
                    $listitem = preg_replace("/>\s*/", '', $listitem);
                else
                    $listitem = preg_replace("/\/weighted[a-z\_]*>\s*$/", '', $listitem);

                $list[] = $listitem;
            }
        }

        $list = array_unique($list);

        return $list;
    }

    /**
     * Return an array of all policies.
     *
     * @return array policy information.
     * @throws Engine_Exception
     */

    public function get_policies()
    {
        clearos_profile(__METHOD__, __LINE__);

        $policies = array();
        $folder = new Folder(self::BASE_PATH);

        $files = $folder->get_listing();

        $ids = array();

        foreach ($files as $file) {
            if (sscanf($file, 'dansguardianf%d.conf', $id) != 1)
                continue;
            $ids[] = $id;
        }

        $system_groups = $this->get_system_groups();

        foreach ($ids as $id) {
            $policies[$id] = $this->get_policy_configuration($id);
            $policies[$id]['systemgroup'] = $system_groups[$id];
        }

        return $policies;
    }

    /**
     * Return the configuration array for the specified policy.
     *
     * @param integer $policy policy ID
     *
     * @return array policy configuration
     */

    public function get_policy_configuration($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_policy_id($policy));

        if ($policy >= 1) {
            $cfg = $this->_get_configuration_by_filename(sprintf(self::FILE_CONFIG_FILTER_GROUP, $policy));

            if ($cfg == NULL)
                throw new Validation_Exception('content_filter_policy_not_found');
        }

        $policy = array();

        if ($policy == 1)
            $policy['groupname'] = lang('content_filter_default');

        foreach ($cfg as $line) {
            list($key, $value) = explode('=', str_replace(array('\'', '"'), '', $line), 2);

            if ($policy == 1 && $key == 'groupname')
                continue;

            $value = trim($value);

            if (preg_match('/off/i', $value))
                $value = FALSE;
            else if (preg_match('/on/i', $value))
                $value = TRUE;

            $policy[trim($key)] = trim($value);
        }

        ksort($policy);

        return $policy;
    }

    /**
     * Returns the policy system group.
     *
     * @param integer $policy policy ID
     *
     * @return string system group
     */

    public function get_policy_system_group($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $file = new File(self::FILE_SYSTEM_GROUPS);

        $lines = $file->get_contents_as_array();
        $count = 1;

        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line))
                continue;

            if ($count == $policy)
                return $line;

            $count++;
        }
    }

    /**
     * Returns the policy name.
     *
     * @param integer $policy policy ID
     *
     * @return string policy name
     */

    public function get_policy_name($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $configuration = $this->get_policy_configuration($policy);

        return $configuration['groupname'];
    }

    /**
     * Returns the PICS level.
     *
     * @param integer $policy policy ID
     *
     * @return string current PICS level
     */

    public function get_pics($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $pics = $this->_get_configuration_value('picsfile', $policy);
        $pics = preg_replace("/.*pics./i", "", $pics);

        return $pics;
    }

    /**
     * Returns list of available blacklists.
     *
     * @return array list of available blacklists
     */

    public function get_possible_blacklists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $blacklistsinfo = array();
        $blacklistslist = array();
        $folderlist = array();

        $folder = new Folder(self::PATH_BLACKLISTS);

        if ($folder->exists())
            $folderlist = $folder->get_listing();

        // Create our list (with descriptions)
        //------------------------------------

        foreach ($folderlist as $foldername) {
            $folder = new Folder(self::PATH_BLACKLISTS . "/$foldername");

            if ($folder->is_directory()) {
                $blacklistsinfo['name'] = $foldername;
                $descriptiontag = 'DANSGUARDIAN_LANG_BLACKLIST_' . strtoupper(preg_replace("/[\-_]/", '', $foldername));

                if (defined("$descriptiontag"))
                    $blacklistsinfo['description'] = constant($descriptiontag);
                else
                    $blacklistsinfo['description'] = '...';

                $blacklistslist[] = $blacklistsinfo;
            }
        }

        return $blacklistslist;
    }

    /**
     * Returns list of file extensions.
     *
     * @return array list of file extensions
     */

    public function get_possible_file_extensions()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file_types = new File_Types();
        $system_extensions = $file_types->get_file_extensions();

        // The array format is slightly different, so convert it to
        // what is typically used in this class.
    
        $extensions = array();

        foreach ($system_extensions as $extension => $details) {
            $info['name'] = $extension;
            $info['category'] = $details['category'];
            $info['category_text'] = $details['category_text'];
            $info['description'] = $details['description'];

            $extensions[] = $info;
        }

        return $extensions;
    }

    /**
     * Returns possible filter modes.
     *
     * @return array list of filter modes
     */

    public function get_possible_filter_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array(
            '0' => lang('content_filter_ban_all'),
            '1' => lang('content_filter_normal_filtering'),
            '2' => lang('content_filter_no_filtering'),
        );
    }

    /**
     * Returns possible naughtyness limits.
     *
     * @return array list of naughtyness limits
     */

    public function get_possible_naughtyness_limits()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array(
            '50' => lang('content_filter_very_aggressive'),
            '100' => lang('content_filter_aggressive'),
            '150' => lang('content_filter_normal'),
            '200' => lang('content_filter_lax'),
            '400' => lang('content_filter_very_lax'),
            '99999' => lang('base_disabled'),
        );
    }

    /**
     * Returns list of possible locales.
     *
     * @return array list of locales
     */

    public function get_possible_locales()
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder(self::PATH_LOCALE);
        $rawlist = $folder->get_listing();

        $list = array();

        foreach ($rawlist as $item) {
            if (isset($this->locales[$item]))
                $list[] = $this->locales[$item];
        }

        sort($list);

        return $list;
    }

    /**
     * Returns list of MIME types.
     *
     * @return array list of MIME types
     */

    public function get_possible_mime_types()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file_types = new File_Types();
        $system_mime_types = $file_types->get_mime_types();

        // The array format is slightly different, so convert it to
        // what is typically used in this class.
    
        $mime_types = array();

        foreach ($system_mime_types as $mime_type => $details) {
            $info['name'] = $mime_type;
            $info['description'] = $details['description'];

            $mime_types[] = $info;
        }

        return $mime_types;
    }

    /**
     * Returns list of possible PICS levels.
     *
     * @return array list of PICS levels
     */

    public function get_possible_pics()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array('teen', 'youngadult', 'tooharsh', 'noblocking', 'disabled');
    }

    /**
     * Returns list of possible weighted phrase lists.
     *
     * @return array list of weighted phrase lists
     */

    public function get_possible_phrase_lists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $phrase_lists_info = array();
        $phrase_lists_list = array();
        $folderlist = array();

        $folder = new Folder(self::PATH_PHRASELISTS);

        if (! $folder->exists())
            return $phrase_lists_list;

        $folderlist = $folder->get_listing();

        // Create our list (with descriptions)
        //------------------------------------

        foreach ($folderlist as $foldername) {
            $phrasefolder = new Folder(self::PATH_PHRASELISTS . "/$foldername");

            $isfolder = $phrasefolder->is_directory();

            if ($isfolder) {

                // Not all phrase lists directories contain weighted lists
                // (some contain just "banned" lists).
                $filenames = $phrasefolder->get_listing();
                $isweighted = FALSE;
                foreach ($filenames as $phrasefile) {
                    if (preg_match('/weighted/', $phrasefile)) {
                        $isweighted = TRUE;
                        break;
                    }
                }

                if ($isweighted) {
                    $phrase_lists_info['name'] = $foldername;
                    $descriptiontag = 'DANSGUARDIAN_LANG_PHRASELIST_' . strtoupper($foldername);

                    if (defined("$descriptiontag"))
                        $phrase_lists_info['description'] = constant($descriptiontag);
                    else
                        $phrase_lists_info['description'] = '...';

                    $phrase_lists_list[] = $phrase_lists_info;
                }
            }
        }

        return $phrase_lists_list;
    }

    /**
     * Returns possible reporting levels.
     *
     * @return array list of reporting levels
     */

    public function get_possible_reporting_levels()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array(
            self::REPORTING_LEVEL_STEALTH => lang('content_filter_stealth_mode'),
            self::REPORTING_LEVEL_SHORT => lang('content_filter_short_report'),
            self::REPORTING_LEVEL_FULL => lang('content_filter_full_report'),
            self::REPORTING_LEVEL_CUSTOM => lang('content_filter_custom_report'),
        );
    }

    /**
     * Returns possible systems groups.
     *
     * @param string $add_group add group to possible list
     *
     * @return array list of possible system groups
     */

    public function get_possible_system_groups($add_group = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_manager = Group_Manager::create();

        $possible_groups = array();
        $configured_groups = array();
        $all_groups = $group_manager->get_list();
        $policies = $this->get_policies();

        foreach ($policies as $policy)
            $configured_groups[] = $policy['systemgroup'];

        foreach ($all_groups as $group) {
            if (! in_array($group, $configured_groups))
                $possible_groups[] = $group;
        }

        if (($add_group) && (!in_array($add_group, $possible_groups)))
            array_unshift($possible_groups, $add_group);

        return $possible_groups;
    }

    /**
     * Returns the proxy IP (default: 127.0.0.1).
     *
     * @return string proxy IP
     */

    public function get_proxy_ip()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('proxyip');
    }

    /**
     * Returns the proxy port (default 3128).
     *
     * @return integer the current proxy port
     */

    public function get_proxy_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('proxyport');
    }

    /**
     * Returns the reporting level.
     *
     * @param integer $policy policy ID
     *
     * @return integer current reporting level
     */

    public function get_reporting_level($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('reportinglevel', $policy);
    }

    /**
     * Returns reverse DNS look-ups.
     *
     * @return string 'on' or 'off'
     */

    public function get_reverse_lookups()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('reverseaddresslookups');
    }

    /**
     * Return list of system groups.
     *
     * @return array $groups list of system groups.
     */

    public function get_system_groups()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_SYSTEM_GROUPS);

        $lines = $file->get_contents_as_array();
        $system_groups = array();

        // Might as well keep the array indexes the same as DansGuardian internals
        $system_groups[] = 'not used';

        foreach ($lines as $line) {
            if (! preg_match('/^#/', $line))
                $system_groups[] = $line;
        }

        return $system_groups;
    }

    /**
     * Sets the access denied page.
     *
     * @param string $url access denied URL
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_access_denied_url($url)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_configuration_value('accessdeniedaddress', $url);
    }

    /**
     * Sets the list of autorization plugins.
     *
     * @param array $plugins list of authorization plugins
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_authorization_plugins($plugins)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: hardcoded to keep it safe and simple for 4.2 errata
        // The methodology below is not very robust for config file hacking.
        // Hopefully this can be deleted an authplugins can be left alone.
        // Right now, the plugins must be removed when user authorization
        // is disabled in Squid.
        //
        // Note: if we detect someone has configured IP-based authorization
        // then just leave everything alone.

        $file = new File(self::FILE_CONFIG);

        try {
            $ipcheck = $file->lookup_line("/^authplugin.*ip.conf/");
            if (! empty($ipcheck))
                return;
        } catch (File_No_Match_Exception $e) {
        }

        if (empty($plugins)) {
            $file->PrependLines("/^authplugin\s+/", "#");
        } else {
            $file->replace_lines("/^#\s*authplugin\s+.*proxy-ntlm.conf/", "authplugin = '/etc/dansguardian-av/authplugins/proxy-ntlm.conf'\n");
            $file->replace_lines("/^#\s*authplugin\s+.*proxy-basic.conf/", "authplugin = '/etc/dansguardian-av/authplugins/proxy-basic.conf'\n");
        }
    }

    /**
     * Sets the list of banned file extensions.
     *
     * @param array   $extensions list of file extensions
     * @param integer $policy     policy_id
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_banned_file_extensions($extensions, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_file_extensions($extensions));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $this->_set_configuration_by_key('bannedextensionlist', $extensions, $policy);
    }

    /**
     * Sets the list of banned MIME types.
     *
     * @param array   $mime_types list of MIME types
     * @param integer $policy     policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_banned_mime_types($mime_types, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mime_types($mime_types));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $this->_set_configuration_by_key('bannedmimetypelist', $mime_types, $policy);
    }

    /**
     * Sets blacklist state.
     *
     * @param array   $list   list of enabled blacklists
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_blacklists($list, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $domaindata = '';
        $urldata = '';

        foreach ($list as $item) {

            // Domains/sites
            //--------------

            $path = self::PATH_BLACKLISTS . "/$item/domains";
            $file = new File($path);

            if ($file->exists())
                $domaindata .= ".Include<$path>\n";

            // URLs
            //-----

            $path = self::PATH_BLACKLISTS . "/$item/urls";

            $file = new File($path);

            if ($file->exists())
                $urldata .= ".Include<$path>\n";
        }

        // Update config file - domains/sites
        //-----------------------------------

        $bannedsitepath = $this->_get_filename_by_key('bannedsitelist', $policy);

        $file = new File($bannedsitepath);

        if (!$file->exists())
            $file->create('root', 'root', '0644');

        $file->delete_lines("/^\.Include.*/");
        $file->add_lines($domaindata);

        // Update config file - URLs
        //--------------------------

        $bannedurlpath = $this->_get_filename_by_key('bannedurllist', $policy);

        $file = new File($bannedurlpath);

        if (!$file->exists())
            $file->create('root', 'root', '0644');

        $file->delete_lines("/^\.Include.*/");
        $file->add_lines($urldata);
    }

    /**
     * Sets blanket block.
     *
     * @param boolean $state  state of blanket block
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_blanket_block($state, $policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($state)
            $this->add_banned_site_and_url('**', $policy);
        else
            $this->delete_banned_site_and_url('**', $policy);
    }

    /**
     * Sets block IP domains.
     *
     * @param boolean $state  state of block IP domains
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_block_ip_domains($state, $policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($state)
            $this->add_banned_site_and_url('*ip', $policy);
        else
            $this->delete_banned_site_and_url('*ip', $policy);
    }

    /**
     * Sets reverse DNS look-ups.
     *
     * @param boolean $state reverse lookups state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_reverse_lookups($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_configuration_value('reverseaddresslookups', $state);
    }

    /**
     * Sets locale.
     *
     * @param string $locale locale
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_locale($locale)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidLocale($locale))
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_LOCALE_INVALID, COMMON_ERROR);

        $this->_set_configuration_value('language', $this->locales[$locale]);
    }

    /**
     * Sets the policy information for given ID.
     *
     * @param integer $policy policy ID
     * @param string  $name   policy name
     * @param string  $group  system group
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_policy($policy, $name, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $this->_set_configuration_value('groupname', $name, $policy);
        
// pete
        $file = new File(self::FILE_SYSTEM_GROUPS);

        $lines = $file->get_contents_as_array();
        $count = 1;

        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line))
                continue;

            if ($count == $policy) {
                $old_group = $line;
                break;
            }

            $count++;
        }

        if ($group !== $old_group)
            $file->replace_lines("/^$old_group$/", "$group\n");
    }

    /**
     * Sets naughtyness limit.
     *
     * @param integer $limit  naughtyness limit.
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_naughtyness_limit($limit, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_naughtyness_limit($limit));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $this->_set_configuration_value('naughtynesslimit', $limit, $policy);
    }

    /**
     * Sets filter mode.
     *
     * @param string  $mode   mode
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_filter_mode($mode, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_filter_mode($mode));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $this->_set_configuration_value('groupmode', $mode, $policy);
    }

    /**
     * Sets bypass link.
     *
     * @param string  $bypass bypass URL
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_bypass_link($bypass, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_configuration_value('bypass', $bypass, $policy);
    }

    /**
     * Sets content scan.
     *
     * @param boolean $state  content scan flag
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_content_scan($state, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_content_scan($state));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        // Parameter name is negative
        $state = ($state) ? 'off' : 'on';

        $this->_set_configuration_value('disablecontentscan', $state, $policy);
    }

    /**
     * Sets deep URL analysis.
     *
     * @param boolean $state  deep URL scan flag
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_deep_url_analysis($state, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_deep_url_analysis($state));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $state = ($state) ? 'on' : 'off';

        $this->_set_configuration_value('deepurlanalysis', $state, $policy);
    }

    /**
     * Sets download block.
     *
     * @param boolean $state  download block flag
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_download_block($state, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_download_block($state));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $state = ($state) ? 'on' : 'off';

        $this->_set_configuration_value('blockdownloads', $state, $policy);
    }

    /**
     * Sets the PICS level
     *
     * @param string  $pics   PICS level
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_pics($pics, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_set_configuration_value('picsfile', self::BASE_PATH . "/pics.$pics", $policy);
    }

    /**
     * Sets the reporting level.
     *
     * @param string  $level  reporting level
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_reporting_level($level, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_reporting_level($level));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $this->_set_configuration_value('reportinglevel', $level, $policy);
    }

    /**
     * Sets the weight phrase list.
     *
     * @param string  $lists  phrase lists
     * @param integer $policy policy ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_phrase_lists($lists, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_phrase_lists($lists));
        Validation_Exception::is_valid($this->validate_policy_id($policy));

        $lines = array();

        foreach ($lists as $phrase_list) {
            $subfolder = new Folder(self::BASE_PATH. "/lists/phraselists/$phrase_list");
            $listnames = $subfolder->get_listing();

            foreach ($listnames as $listname) {
                if (preg_match('/weighted/', $listname))
                    $lines[] = ".Include<" . self::BASE_PATH. "/lists/phraselists/$phrase_list/$listname>";
            }
        }

        $this->_set_configuration_by_key('weightedphraselist', $lines, $policy);
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for content scan.
     *
     * @param boolean $state content scan
     *
     * @return string error message if flag is invalid
     */

    public function validate_content_scan($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('content_filter_content_scan_invalid');
    }

    /**
     * Validation routine for deep URL analysis.
     *
     * @param boolean $state deep URL analysis flag
     *
     * @return string error message if flag is invalid
     */

    public function validate_deep_url_analysis($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('content_filter_deep_url_analysis_invalid');
    }

    /**
     * Validation routine for download block flag.
     *
     * @param boolean $state download block flag
     *
     * @return string error message if flag is invalid
     */

    public function validate_download_block($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('content_filter_download_block_invalid');
    }

    /**
     * Validates filter mode.
     *
     * @param integer $mode filter mode
     *
     * @return string error message if filter mode is invalid
     */

    public function validate_filter_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        $modes = $this->get_possible_filter_modes();

        if (! array_key_exists($mode, $modes))
            return lang('content_filter_filter_mode_invalid');
    }

    /**
     * Validation routine for file extensions list.
     *
     * @param array $extensions an array of file extensions
     *
     * @return string error message if extensions list is invalid
     */

    public function validate_file_extensions($extensions)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($extensions))
            return lang('content_filter_file_extensions_invalid');

        $valid_extensions = $this->get_possible_file_extensions();

        foreach ($extensions as $extension) {
            $is_valid = FALSE;

            foreach ($valid_extensions as $valid_extension) {
                if ($extension == $valid_extension['name'])
                    $is_valid = TRUE;
            }

            if (! $is_valid)
                return lang('content_filter_file_extension_invalid');
        }
    }

    /**
     * Validates system group.
     *
     * @param string $group system group name
     *
     * @return string error message if system group name is invalid
     */

    public function validate_group($group)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_manager = Group_Manager_Factory::create();
        $all_groups = $group_manager->get_list();

        if (! in_array($group, $all_groups))
            return lang('content_filter_group_invalid');
    }

    /**
     * Validates MIME type list.
     *
     * @param array $mime_types array of MIME types
     *
     * @return string error message if MIME types array is invalid
     */

    public function validate_mime_types($mime_types)
    {
        clearos_profile(__METHOD__, __LINE__);

        $valid_types = $this->get_possible_mime_types();

        foreach ($mime_types as $mime_type) {
            $is_valid = FALSE;

            foreach ($valid_types as $valid_type) {
                if ($mime_type == $valid_type['name'])
                    $is_valid = TRUE;
            }

            if (! $is_valid)
                return lang('content_filter_mime_type_invalid');
        }
    }

    /**
     * Validation routine for naughtyness limit.
     *
     * @param integer $limit naughtyness limit
     *
     * @return string error message if limit is invalid
     */

    public function validate_naughtyness_limit($limit)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!(preg_match('/^[0-9]+$/', $limit) && ($limit > 0) && ($limit <= self::MAX_NAUGHTYNESS_LIMIT)))
            return lang('content_filter_dynamic_scan_sensitivity_invalid');
    }

    /**
     * Validation routine for phrase lists.
     *
     * @param array $phrase_lists an array of phrase lists
     *
     * @return string error message if phrase list is invalid
     */

    public function validate_phrase_lists($phrase_lists)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($phrase_lists))
            return lang('content_filter_phrase_list_invalid');

        $valid_lists = $this->get_possible_phrase_lists();

        foreach ($phrase_lists as $phrase_list) {
            $is_valid = FALSE;

            foreach ($valid_lists as $valid_list) {
                if ($phrase_list == $valid_list['name'])
                    $is_valid = TRUE;
            }

            if (! $is_valid)
                return lang('content_filter_phrase_list_invalid');
        }
    }

    /**
     * Validates policy ID.
     *
     * @param integer $id policy ID
     *
     * @return string error message if policy ID is invalid
     */

    public function validate_policy_id($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_numeric($id) || ($id < 0) || ($id > self::MAX_FILTER_GROUPS))
            return lang('content_filter_policy_id_invalid');
    }

    /**
     * Validates policy name.
     *
     * @param string $name policy name
     *
     * @return string error message if policy name is invalid
     */

    public function validate_policy_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^[a-z0-9_\-]+$/', $name))
            return lang('content_filter_policy_name_invalid');
    }

    /**
     * Validation routine for reporting level.
     *
     * @param integer $level reporting level
     *
     * @return string error message if reporting level is invalid
     */

    public function validate_reporting_level($level)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!(is_numeric($level) && ($level <= 3) && ($level >= -1)))
            return lang('content_filter_reporting_level_invalid');
    }

    /**
     * Validation routine for filter port number.
     *
     * @param integer $port filter port number
     *
     * @return string error message if filter port is invalid
     */

    public function validate_filter_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^\d+$/", $port))
            return lang('content_filter_filter_port_invalid');
    }

    /**
     * Validation routine for proxy IP.
     *
     * @param string $ip IP address
     *
     * @return string error message if proxy IP is invalid
     */

    public function validate_proxy_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([0-9\.\-]*)$/", $ip))
            return lang('content_filter_proxy_ip_invalid');
    }

    /**
     * Validation routine for proxy port.
     *
     * @param integer $port proxy port number
     *
     * @return string error message if proxy port is invalid
     */

    public function validate_proxy_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_port($port))
            return lang('content_filter_proxy_port_invalid');
    }

    /**
     * Validation routine for PICS.
     *
     * @param string $pics PICS value
     *
     * @return string error message if PICS value is invalid
     */

    public function validate_pics($pics)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[". implode("|", $this->get_possible_pics()) ."]/", $pics))
            return lang('content_filter_pics_invalid');
    }

    /**
     * Validation routine for web sites.
     *
     * @param string $site web site
     *
     * @return error message if site is invalid
     */

    public function validate_site($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! (preg_match("/^[\w\_\.\-]+\.[\w\_\.\-]+$|^\*ip$|^\*ips$|^\*\*$|^\*\*s$/", $site)))
            return lang('content_filter_site_invalid');
    }

    /**
     * Validation routine for URLs.
     *
     * @param string $url URL
     *
     * @return error message if URL is invalid
     */

    public function validate_url($url)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! (preg_match("/^([\w\._\-\/]+)$/", $url)))
            return lang('content_filter_url_invalid');
    }

    /**
     * Validation routine for IPs.
     *
     * DansGuardian also accepts asterisks and *ip
     *
     * @param string $ip IP address
     *
     * @return error message if IP is invalid.
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: tighten this up
        if (!(preg_match("/^([0-9\.\-]*)$/", $ip)))
            return lang('content_filter_ip_invalid');
    }

    /**
     * Validation routine for locale.
     *
     * @param string $locale locale
     *
     * @return string error message if locale is invalid
     */

    public function validate_locale($locale)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!in_array($locale, $this->locales))
            return lang('content_filter_locale_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Adds items to file designated by the key in the configuration file.
     *
     * @param string  $key    key in the configuration file
     * @param array   $items  array of items
     * @param integer $policy policy ID
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _add_items_by_key($key, $items, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = $this->_get_configuration_value($key, $policy);

        // Make sure entry does not already exist
        //---------------------------------------

        $contents = array();
        $existlist = array();
        $file = new File($filename);

        if (!$file->exists())
            $file->create('root', 'root', '0644');
        else
            $contents = $file->get_contents();

        $lines = explode("\n", $contents);

        foreach ($lines as $line)
            $existlist[] = $line;

        // Add list of new items to file
        //------------------------------

        if (!is_array($items))
            $items = array($items);

        // TODO - we should probably send an error when a duplicate is found
        $filedata = "";

        foreach ($items as $item) {
            if (!in_array($item, $existlist))
                $filedata .= "$item\n";
        }

        $file->add_lines($filedata);
    }

    /**
     * Deletes items in file designated by the key in the configuration file.
     *
     * @param string  $key    key in the configuration file
     * @param array   $items  array of items
     * @param integer $policy policy ID
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_items_by_key($key, $items, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = $this->_get_configuration_value($key, $policy);

        $file = new File($filename);

        if (!is_array($items))
            $items = array($items);

        foreach ($items as $item) {
            $item = preg_quote($item, "/");
            $file->delete_lines("/^$item\$/");
        }
    }

    /**
     * Returns the list of banned URLs and sites.
     *
     * @param integer $policy policy ID
     *
     * @return array list of banned URLs and sites
     * @throws Engine_Exception
     */

    protected function _get_banned_sites_and_urls($policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $site_list = $this->_get_configuration_data_by_key('bannedsitelist', $policy);
        $url_list = $this->_get_configuration_data_by_key('bannedurllist', $policy);

        return array_merge($url_list, $site_list);
    }

    /**
     * Generic parameter fetch.
     *
     * @param string  $key    configuration file key
     * @param integer $policy policy ID
     *
     * @access private
     * @return string key value
     * @throws Engine_Exception
     */

    protected function _get_configuration_value($key, $policy = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (in_array($key, $this->policy_keys) && ($policy != NULL) && $policy > 0)
            $filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $policy);
        else
            $filename = self::FILE_CONFIG;

        $file = new File($filename);

        try {
            $match = $file->lookup_value("/^$key\s*=\s*/i");
        } catch (File_No_Match_Exception $e) {
            return;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        $match = preg_replace("/'/", '', $match);

        return $match;
    }

    /**
     * Generic fetch of file contents specified by a key value in dansguardian.conf.
     *
     * @param string  $key    configuration file key
     * @param integer $policy policy ID
     *
     * @access private
     * @return array lines in target file
     * @throws Engine_Exception
     */

    protected function _get_configuration_data_by_key($key, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = $this->_get_configuration_value($key, $policy);

        $lines = $this->_get_configuration_by_filename($filename, $policy);

        return $lines;
    }

    /**
     * Generic fetch of file contents.
     *
     * @param string  $filename configuration file
     * @param integer $policy   policy ID
     *
     * @access private
     * @return array lines in target file
     */

    protected function _get_configuration_by_filename($filename, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config_file = new File($filename);
        $lines = $config_file->get_contents_as_array();

        $list = array();

        foreach ($lines as $line) {
            // Skip blank lines, comments, other include files
            if (! (preg_match("/^#/", $line) || preg_match("/^.Include/", $line) || preg_match("/^\s*$/", $line))) {
                $list[] = $line;
                // TODO: clean this up (part of a last minute change)
            } else if (preg_match("/^.Include/", $line) 
                && ($filename == self::FILE_PHRASE_LIST || $filename == sprintf(self::FILE_PHRASE_LIST . '%d', $policy))
            ) {
                $list[] = $line;
            }
        }

        return $list;
    }

    /**
     * Returns the target file name for a given dansguardian.conf key.
     *
     * @param string  $param  key to search for
     * @param integer $policy policy ID
     *
     * @return string full path of file
     */

    protected function _get_filename_by_key($param, $policy = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (in_array($param, $this->policy_keys) && ($policy != NULL))
            $filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $policy);
        else
            $filename = self::FILE_CONFIG;

        $file = new File($filename);
        $filename = $file->lookup_value("/^".$param ."\s*=\s*'/i");
        $filename = preg_replace("/'/", '', $filename);

        return $filename;
    }

    /**
     * (Re)sequence filter group IDs.
     *
     * Called after deleting a filter group.
     *
     * @access private
     * @return void
     */

    protected function _sequence_policies()
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder(self::BASE_PATH);

        $files = $folder->get_listing();

        $ids = array();

        foreach ($files as $file) {
            if (sscanf($file, 'dansguardianf%d.conf', $id) != 1)
                continue;
            $ids[] = array('id' => $id, 'file' => $file);
        }

        if (!count($ids))
            return;

        for ($i = 0; $i < count($ids); $i++) {
            if ($ids[$i]['id'] == $i + 1)
                continue;

            $file = new File(self::BASE_PATH . '/' . $ids[$i]['file'], TRUE);

            foreach ($this->policy_keys as $key) {

                if (strstr($key, 'list') === FALSE)
                    continue;

                $old = str_replace(array('\'', '"'), '', $file->lookup_value("/^$key\s*=\s*/"));
                $new = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), '', $old) . ($i + 1);
                $file->replace_one_line_by_pattern("/^$key\s*=.*$/", "$key = '$new'\n");

                $fglist = new File($old);

                if ($fglist->exists())
                    $fglist->move_to($new);
            }

            $file->move_to(self::BASE_PATH . sprintf('/dansguardianf%d.conf', $i + 1));
        }

        $file = new File(self::FILE_CONFIG, TRUE);

        $file->replace_lines('/^filtergroups\s*=.*$/', sprintf("filtergroups = %d\n", count($ids)));
    }

    /**
     * Generic set for a configuration value.
     *
     * @param string  $key    configuration key
     * @param string  $value  value for the configuration key
     * @param integer $policy policy ID
     *
     * @access private
     * @return void
     */

    protected function _set_configuration_value($key, $value, $policy = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (in_array($key, $this->policy_keys) && (!is_null($policy)) && ($policy > 0))
            $filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $policy);
        else
            $filename = self::FILE_CONFIG;

        $file = new File($filename);

        $match = $file->replace_lines("/^#*\s*$key\s=/", "$key = '$value'\n");

        if (!$match)
            $file->add_lines("$key = $value\n");
    }

    /**
     * Generic set for a configuration file by key in configuration file.
     *
     * @param string  $key    configuration key
     * @param string  $lines  array of lines in config file
     * @param integer $policy policy ID
     *
     * @access private
     * @return void
     */

    protected function _set_configuration_by_key($key, $lines, $policy = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($lines))
            $lines = array($lines);

        $filename = $this->_get_configuration_value($key, $policy);
        $filename = preg_replace("/'/", '', $filename);

        $file = new File($filename, TRUE);

        if (!$file->exists())
            $file->create('root', 'root', '0644');
        else {
            $file->delete_lines('/^[^#]+/');
            $file->delete_lines('/^\s*$/');
        }

        $filedata = NULL;

        foreach ($lines as $line)
            $filedata .= "$line\n";

        $file->add_lines($filedata);
    }

    /**
     * Split an array of URLs and sites into two arrays.
     *
     * A site includes an entire site (microsoft.com would block all
     * pages from that domain).  A URL is a specific page
     * (www.microsoft.com/privacy.html would block just the privacy page).
     *
     * @param array $sourcelist list of urls and sites
     * @param array &$sites     array of sites
     * @param array &$urls      array of urls
     *
     * @access private
     * @return void
     */

    protected function _split_sites_and_urls($sourcelist, &$sites, &$urls)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($sourcelist))
            $sourcelist = array($sourcelist);

        $sites = array();

        $urls = array();

        foreach ($sourcelist as $value) {
            if ($this->validate_site($value) || $this->validate_ip($value)) {
                $sites[] = $value;
            } else if ($this->validate_url($value)) {
                $urls[] = $value;
            } else if (preg_match("/^\s$/", $value)) {
                continue; // Ignore blank entries
            } else {
                throw new Validation_Exception(lang('content_filter_site_invalid'));
            }
        }
    }
}
