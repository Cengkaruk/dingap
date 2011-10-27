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

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\File_Types as File_Types;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\content_filter\File_Group as File_Group;
use \clearos\apps\content_filter\File_Group_Manager as File_Group_Manager;
use \clearos\apps\network\Network as Network;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/File_Types');
clearos_load_library('base/Folder');
clearos_load_library('content_filter/File_Group');
clearos_load_library('content_filter/File_Group_Manager');
clearos_load_library('network/Network');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\content_filter\Filter_Group_Not_Found_Exception as Filter_Group_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('content_filter/Filter_Group_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * DansGuardian content filter class.
 *
 * @category   Apps
 * @package    Content_Filter
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
    const FILE_GROUPS = '/etc/dansguardian-av/groups';
    const MAX_FILTER_GROUPS = 9;

    ///////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////

    var $group_keys;
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

        $this->group_keys = array(
            'groupmode', 
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
     * Adds a group to the banned IP list.
     *
     * @param string $groupname name of group
     *
     * @return void
     */

    public function add_banned_ip_group($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_group_by_key('bannediplist', $groupname);
    }

    /**
     * Adds web site or URL to banned list.
     *
     * @param string  $siteurl website or URL
     * @param integer $group   group number
     *
     * @return void
     */

    public function add_banned_site_and_url($siteurl, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_add_items_by_key('bannedsitelist', $sites, $group);

        if (count($urls) > 0)
            $this->_add_items_by_key('bannedurllist', $urls, $group);
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
     * Adds a group to the exception list.
     *
     * @param string $groupname name of group
     *
     * @return void
     */

    public function add_exception_ip_group($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_add_group_by_key('exceptioniplist', $groupname);
    }

    /**
     * Adds web site or URL to exception list.
     *
     * @param string  $siteurl site or URL
     * @param integer $group group ID
     *
     * @return void
     */

    public function add_exception_site_and_url($siteurl, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        // Add to list
        //------------

        if (count($sites) > 0)
            $this->_add_items_by_key('exceptionsitelist', $sites, $group);

        if (count($urls) > 0)
            $this->_add_items_by_key('exceptionurllist', $urls, $group);
    }

    /**
     * Adds a new filter group.
     *
     * @param string $name filter group name
     *
     * @return integer new filter group ID
     */

    public function add_filter_group($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group_name($name));

        if ($this->exists_filter_group($name))
            throw new Engine_Exception(lang('content_filter_group_already_exists'));

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
            throw new Engine_Exception(lang('content_filter_maximum_groups_already_configured'));
        } else if (count($ids)) {
            sort($ids);
            $id = end($ids) + 1;
        }

        // Create new filter group by copying the default
        //-----------------------------------------------

        $file = new File(self::BASE_PATH . '/dansguardianf1.conf', TRUE);

        $file->copy_to(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id));

        $file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), TRUE);

        if ($file->replace_lines('/^groupname.*$/', "groupname = '$name'\n", 1) != 1)
            $file->add_lines_after("groupname = '$name'\n", '/^#groupname.*$/');

        // Default the group mode to "filtered"
        $file->replace_lines('/^groupmode\s*=.*/', "groupmode = 1\n", 1);

        foreach ($this->group_keys as $key) {
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

        // Resequence filter group IDs and set filter group count
        //-------------------------------------------------------

        $this->_sequence_filter_groups();

        return $id;
    }

    /**
     * Adds web site or URL to grey list.
     *
     * @param string  $siteurl site or URL
     * @param integer $group   group number
     *
     * @return void
     */

    public function add_grey_site_and_url($siteurl, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        if (count($sites) > 0)
            $this->_add_items_by_key('greysitelist', $sites, $group);

        if (count($urls) > 0)
            $this->_add_items_by_key('greyurllist', $urls, $group);
    }

    /**
     * Adds group definition.
     *
     * @param string $groupname group name
     *
     * @return void
     */

    public function add_group($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new File_Group($groupname, self::FILE_GROUPS);

        $group->add('');
    }

    /**
     * Adds group member to given group. 
     *
     * This will cascade all the entries to the configuration files that use the group feature.
     *
     * @param string $groupname group name
     * @param string $member    member
     *
     * @return void
     */

    public function add_group_entry($groupname, $member)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network();

        if (!$network->IsValidIp($member))
            return;

        // Add member to banned IP list
        //-----------------------------

        $bannedipfile = $this->_get_filename_by_key('bannediplist');

        $bannedgroup = new File_Group($groupname, $bannedipfile);

        if ($bannedgroup->exists())
            $bannedgroup->AddEntry($member);

        // Add member to exception IP list
        //--------------------------------

        $exceptionipfile = $this->_get_filename_by_key('exceptioniplist');

        $exceptiongroup = new File_Group($groupname, $exceptionipfile);

        if ($exceptiongroup->exists())
            $exceptiongroup->AddEntry($member);

        // Add to group file
        //------------------

        $group = new File_Group($groupname, self::FILE_GROUPS);

        $group->AddEntry($member);
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
     * Deletes group from the banned IP list.
     *
     * @param string $groupname group name
     *
     * @return void
     */

    public function delete_banned_ip_group($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->DeleteGroupByKey('bannediplist', $groupname);
    }

    /**
     * Deletes item from banned sites and URLs.
     *
     * @param string $siteurl site or URL
     * @param integer $group group ID
     *
     * @return void
     */

    public function delete_banned_site_and_url($siteurl, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        // Delete from list
        //-----------------

        if (count($sites) > 0)
            $this->_delete_items_by_key('bannedsitelist', $sites, $group);

        if (count($urls) > 0)
            $this->_delete_items_by_key('bannedurllist', $urls, $group);
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
     * Deletes group from the exception list.
     *
     * @param string $groupname group name
     *
     * @return void
     */

    public function delete_exception_ip_group($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->DeleteGroupByKey('exceptioniplist', $groupname);
    }

    /**
     * Deletes site or URL from the exception list.
     *
     * @param string $siteurl site or URL
     * @param integer $group group ID
     *
     * @return void
     */

    public function delete_exception_site_and_url($siteurl, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        // Delete from list
        //-----------------

        if (count($sites) > 0)
            $this->_delete_items_by_key('exceptionsitelist', $sites, $group);

        if (count($urls) > 0)
            $this->_delete_items_by_key('exceptionurllist', $urls, $group);
    }

    /**
     * Deletes site or URL from the grey list.
     *
     * @param string $siteurl site or URL
     * @param integer $group group ID
     *
     * @return void
     */

    public function delete_grey_site_and_url($siteurl, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sites = array();
        $urls = array();

        $this->_split_sites_and_urls($siteurl, $sites, $urls);

        // Delete from list
        //-----------------

        if (count($sites) > 0)
            $this->_delete_items_by_key('greysitelist', $sites, $group);

        if (count($urls) > 0)
            $this->_delete_items_by_key('greyurllist', $urls, $group);
    }

    /**
     * Deletes group definition.
     *
     * This will also delete all the entries from configuration files that use the group feature.
     *
     * @param string $groupname group name
     *
     * @return void
     */

    public function delete_group($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $bannedipfile = $this->_get_filename_by_key('bannediplist');
        $exceptionipfile = $this->_get_filename_by_key('exceptioniplist');

        // Delete group items from banned IP list
        //---------------------------------------

        $bannedgroup = new File_Group($groupname, $bannedipfile);

        if ($bannedgroup->exists())
            $bannedgroup->Delete();

        // Delete group items from exception IP list
        //------------------------------------------

        $exceptiongroup = new File_Group($groupname, $exceptionipfile);

        if ($exceptiongroup->exists())
            $exceptiongroup->Delete();

        // Delete group
        //-------------

        $group = new File_Group($groupname, self::FILE_GROUPS);

        $group->Delete();
    }

    /**
     * Deletes group member to given group.
     *
     * This will cascade all the entries to the configuration files that use the group feature.
     *
     * @param string $groupname group name
     * @param string $member member
     *
     * @return void
     */

    public function delete_group_entry($groupname, $member)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network();

        if (!$network->IsValidIp($member))
            throw new Engine_Exception(NETWORK_LANG_IP . ' - ' . LOCALE_LANG_INVALID, COMMON_WARNING);

        // Delete member to banned IP list
        //--------------------------------

        $bannedipfile = $this->_get_filename_by_key('bannediplist');

        $bannedgroup = new File_Group($groupname, $bannedipfile);

        if ($bannedgroup->exists())
            $bannedgroup->delete_entry($member);

        // Delete member to exception IP list
        //-----------------------------------

        $exceptionipfile = $this->_get_filename_by_key('exceptioniplist');

        $exceptiongroup = new File_Group($groupname, $exceptionipfile);

        try {
            if ($exceptiongroup->exists())
                $exceptiongroup->delete_entry($member);
        } catch (Exception $e) {
            // XXX: keep going
        }

        // Delete in group file
        //---------------------

        $group = new File_Group($groupname, self::FILE_GROUPS);

        try {
            $group->delete_entry($member);
        } catch (Exception $e) {
            // XXX: keep going
        }
    }

    /**
     * Checks existence of a group.
     *
     * @param string $name group name
     *
     * @return boolean TRUE if group name exists
     * @throws Engine_Exception
     */

    public function exists_filter_group($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $cfg = $this->get_filter_group_configuration(0, $name);
        } catch (Filter_Group_Not_Found_Exception $e) {
            return FALSE;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }

        return TRUE;
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
     * @param integer $group group ID
     *
     * @return array list of banned extensions
     */

    public function get_banned_file_extensions($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('bannedextensionlist', FALSE, $group);
    }

    /**
     * Returns the list of banned URLs and sites.
     *
     * @param integer $group group ID
     *
     * @return array list of banned URLs and sites
     */

    public function get_banned_sites_and_urls($group_id = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $site_list = $this->_get_configuration_data_by_key('bannedsitelist', FALSE, $group_id);
        $url_list = $this->_get_configuration_data_by_key('bannedurllist', FALSE, $group_id);

        return array_merge($url_list, $site_list);
    }

    /**
     * Returns the list of banned IPs.
     *
     * @return array list of banned IPs
     */

    public function get_banned_ips()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('bannediplist', FALSE);
    }

    /**
     * Returns the list of banned IP groups.
     *
     * @return array list of banned IP groups
     */

    public function get_banned_ips_groups()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: this is the same as banned_ips?
        return $this->_get_configuration_data_by_key('bannediplist', TRUE);
    }

    /**
     * Returns the banned MIME list.
     *
     * @param integer $group group ID
     *
     * @return array list of banned MIMEs
     */

    public function get_banned_mime_types($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('bannedmimetypelist', FALSE, $group);
    }

    /**
     * Returns activated blacklists.
     *
     * @param integer $group group ID
     *
     * @return array list of activated blacklists
     */

    public function get_blacklists($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $bannedtypes = array();
        $bannedtypes[] = 'bannedsitelist';
        $bannedtypes[] = 'bannedurllist';

        $active = array();

        foreach ($bannedtypes as $type) {
            $bannedfile = $this->_get_filename_by_key($type, $group);

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
     * Returns the list of IPs in the exception list.
     *
     * @return array list of IPs that bypass the filter
     */

    public function get_exception_ips()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('exceptioniplist', FALSE);
    }

    /**
     * Returns the list of groups the exception list.
     *
     * @return array list of groups that bypass the filter
     */

    public function get_exception_ips_groups()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_data_by_key('exceptioniplist', TRUE);
    }

    /**
     * Returns the sites and URLs in the exception list.
     *
     * @param integer $group group ID
     *
     * @return array list of urls and sites in the exception list
     */

    public function get_exception_sites_and_urls($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sitelist = array();
        $urllist = array();
        $list = array();

        try {
            $sitelist = $this->_get_configuration_data_by_key('exceptionsitelist', FALSE, $group);
        } catch (Exception $e) { }

        try {
            $urllist = $this->_get_configuration_data_by_key('exceptionurllist', FALSE, $group);
        } catch (Exception $e) { }

        $list = array_merge($urllist, $sitelist);

        return $list;
    }

    /**
     * Returns the sites and URLs in the grey list.
     *
     * @param integer $group group ID
     *
     * @return array list of urls and sites in the grey list
     */

    public function get_grey_sites_and_urls($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $sitelist = array();
        $urllist = array();
        $list = array();

        try {
            $sitelist = $this->_get_configuration_data_by_key('greysitelist', FALSE, $group);
        } catch (Exception $e) { }

        try {
            $urllist = $this->_get_configuration_data_by_key('greyurllist', FALSE, $group);
        } catch (Exception $e) { }

        $list = array_merge($urllist, $sitelist);

        return $list;
    }

    /**
     * Returns the filter port (default 8080).
     *
     *
     * @return integer filter port number
     */

    public function get_filter_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        $port = $this->_get_configuration_value('filterport');

        return $port;
    }

    /**
     * Returns available groups.
     *
     * @return array list of available groups
     */

    public function get_groups()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_manager = new File_Group_Manager(self::FILE_GROUPS);

        return $group_manager->get_groups();
    }

    /**
     * Returns available groups in a specific config file.
     *
     * @return array list of groups configured in config file
     */

    public function get_groups_by_key($key)
    {
        clearos_profile(__METHOD__, __LINE__);

        $groupmanager = new File_Group_Manager($key);

        return $groupmanager->get_groups();
    }

    /**
     * Returns group entries.
     *
     * @return array list of entries in a group
     */

    public function get_group_entries($groupname)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = new File_Group($groupname, self::FILE_GROUPS);

        return $group->get_entries();
    }

    /**
     * Returns locale.
     *
     * @return integer the current locale
     */

    public function get_locale($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $locale = $this->_get_configuration_value('language', $group);

        if ($this->locales[$locale])
            return $this->locales[$locale];
        else
            return 'en_US';
    }

    /**
     * Returns naughtyness level.
     *
     * @return integer  the current naughtyness limit
     */

    public function get_naughtyness_limit($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('naughtynesslimit', $group);
    }

    /**
     * Returns the PICS level.
     *
     * @return string current PICS level
     */

    public function get_pics($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $pics = $this->_get_configuration_value('picsfile', $group);
        $pics = preg_replace("/.*pics./i", "", $pics);

        return $pics;
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
     * Returns reverse DNS look-ups.
     *
     * @return string 'on' or 'off'
     */

    public function get_reverse_lookups()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('reverseaddresslookups', -1);
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
     * @param integer $group group ID
     *
     * @return integer current reporting level
     */

    public function get_reporting_level($group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_configuration_value('reportinglevel', $group);
    }

    /**
     * Returns the weight phrase list.
     *
     * @param integer $group group ID
     * @param boolean $details flag get full list including languagues
     *
     * @return array a list of the weight phrases
     */

    public function get_phrase_lists($group = 1, $details = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rawlines = $this->_get_configuration_data_by_key('weightedphraselist', FALSE, $group);
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
     * Sets the access denied page.
     *
     * @return void
     */

    public function set_access_denied_url($url)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_configuration_value('accessdeniedaddress', $url);
    }

    /**
     * Sets the list of autorization plugins.
     *
     * @param array $plugins list of authorization plugins
     *
     * @return void
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
            $ipcheck = $file->LookupLine("/^authplugin.*ip.conf/");
            if (! empty($ipcheck))
                return;
        } catch (File_No_Match_Exception $e) {
        }

        try {
            if (empty($plugins)) {
                $file->PrependLines("/^authplugin\s+/", "#");
            } else {
                $file->replace_lines("/^#\s*authplugin\s+.*proxy-ntlm.conf/", "authplugin = '/etc/dansguardian-av/authplugins/proxy-ntlm.conf'\n");
                $file->replace_lines("/^#\s*authplugin\s+.*proxy-basic.conf/", "authplugin = '/etc/dansguardian-av/authplugins/proxy-basic.conf'\n");
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_ERROR);
        }
    }

    /**
     * Sets the list of banned file extensions.
     *
     * @param array   $extensions list of file extensions
     * @param integer $group_id   group ID
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_banned_file_extensions($extensions, $group_id = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_file_extensions($extensions));
        Validation_Exception::is_valid($this->validate_group_id($group_id));

        $this->_set_configuration_by_key('bannedextensionlist', $extensions, $group_id);
    }

    /**
     * Sets the list of banned MIME types.
     *
     * @param array   $mime_types list of MIME types
     * @param integer $group_id   group ID
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_banned_mime_types($mime_types, $group_id = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mime_types($mime_types));
        Validation_Exception::is_valid($this->validate_group_id($group_id));

        $this->_set_configuration_by_key('bannedmimetypelist', $mime_types, $group_id);
    }

    /**
     * Sets blacklist state.
     *
     * @param array   $list  list of enabled blacklists
     * @param integer $group group ID
     *
     * @return void
     */

    public function set_blacklists($list, $group = 1)
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

        $bannedsitepath = $this->_get_filename_by_key('bannedsitelist', $group);

        $file = new File($bannedsitepath);

        if (!$file->exists())
            $file->create('root', 'root', '0644');

        $file->delete_lines("/^\.Include.*/");
        $file->add_lines($domaindata);

        // Update config file - URLs
        //--------------------------

        $bannedurlpath = $this->_get_filename_by_key('bannedurllist', $group);

        $file = new File($bannedurlpath);

        if (!$file->exists())
            $file->create('root', 'root', '0644');

        $file->delete_lines("/^\.Include.*/");
        $file->add_lines($urldata);
    }

    /**
     * Sets reverse DNS look-ups.
     *
     *
     * @return void
     */

    public function set_reverse_lookups($enable)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_configuration_value('reverseaddresslookups', $enable);
    }

    /**
     * Sets locale.
     *
     *
     * @return void
     */

    public function set_locale($locale)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidLocale($locale))
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_LOCALE_INVALID, COMMON_ERROR);

        $this->set_configuration_value('language', $this->locales[$locale]);
    }

    /**
     * Sets group name.
     *
     *
     * @return void
     */

    public function set_group_name($name, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        try {
            $groups = $this->GetFilterGroups();
            foreach ($groups as $id => $record) {
                if ($id != $group && $name == $record['groupname'])
                    throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_GROUP_EXISTS, COMMON_ERROR);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_GROUP_EXISTS, COMMON_ERROR);
        }

        $file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $group), TRUE);

        try {
            if ($file->replace_lines('/^groupname.*$/', "groupname = '$name'\n", 1) != 1)
                $file->add_lines_after("groupname = '$name'\n", '/^#groupname.*$/');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
    }

    /**
     * Sets naughtyness level.
     *
     *
     * @return void
     */

    public function set_naughtyness_limit($limit, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidNaughtynessLimit($limit))
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_NAUGHTYNESS_INVALID, COMMON_ERROR);

        $this->set_configuration_value('naughtynesslimit', $limit, $group);
    }

    /**
     * Sets filter mode.
     *
     *
     * @return void
     */

    public function set_filter_mode($mode, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidFilterMode($mode))
            throw new Engine_Exception(DANSGUARDIAN_LANG_FILTER_MODE . " - " . LOCALE_LANG_INVALID, COMMON_ERROR);

        $this->set_configuration_value('groupmode', $mode, $group);
    }

    /**
     * Sets bypass link.
     *
     *
     * @return void
     */

    public function set_bypass_link($bypass, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_configuration_value('bypass', $bypass, $group);
    }

    /**
     * Sets content scan.
     *
     *
     * @return void
     */

    public function set_content_scan($scan, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_configuration_value('disablecontentscan', $scan, $group);
    }

    /**
     * Sets deep URL analysis.
     *
     *
     * @return void
     */

    public function set_deep_url_analysis($deepurl, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_configuration_value('deepurlanalysis', $deepurl, $group);
    }

    /**
     * Sets download block.
     *
     *
     * @return void
     */

    public function set_download_block($block, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_configuration_value('blockdownloads', $block, $group);
    }

    /**
     * Sets the PICS level
     *
     * @param   port    the PICS level
     *
     * @return void
     */

    public function set_pics($pics, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidPICS($pics))
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_PICS_INVALID, COMMON_ERROR);

        $this->set_configuration_value('picsfile', self::BASE_PATH . "/pics.$pics", $group);
    }

    /**
     * Sets the reporting level.
     *
     *
     * @return void
     */

    public function set_reporting_level($level, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->IsValidReportingLevel($level))
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_REPORTINGLEVEL_INVALID, COMMON_ERROR);

        $this->set_configuration_value('reportinglevel', $level, $group);
    }

    /**
     * Sets the weight phrase list.
     *
     *
     * @return void
     */

    public function set_phrase_lists($phrase_lists, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_phrase_lists($phrase_lists));
        Validation_Exception::is_valid($this->validate_group_id($group));

        $lines = array();

        foreach ($phrase_lists as $phrase_list) {
            $subfolder = new Folder(self::BASE_PATH. "/lists/phraselists/$phrase_list");
            $listnames = $subfolder->get_listing();

            foreach ($listnames as $listname) {
                if (preg_match('/weighted/', $listname))
                    $lines[] = ".Include<" . self::BASE_PATH. "/lists/phraselists/$phrase_list/$listname>";
            }
        }

        $this->_set_configuration_by_key('weightedphraselist', $lines, $group);
    }

    /**
     * Add a new Filter Group
     *
     * @param string $name New Filter Group Name
     *
     * @return int $id New Filter Group Id
     */

    public function delete_filter_group($id)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {
            $this->GetFilterGroupConfiguration($id);
            $file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), TRUE);
            foreach ($this->group_keys as $key) {
                if (strstr($key, 'list') === FALSE) continue;
                $value = str_replace(array('\'', '"'), '',
                    $file->lookup_value("/^$key\s*=\s*/"));
                $fglist = new File($value);
                if ($fglist->exists()) $fglist->Delete();
            }
            $file->Delete();
            $file = new File(self::FILE_CONFIG, TRUE);
            try {
                $fglist = str_replace(array('\'', '"'), '',
                    $file->lookup_value('/^filtergroupslist\s*=\s*/'));
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
            }
            $file = new File($fglist);
            try {
                $file->delete_lines("/.*=filter$id.*/");
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
            }
            $this->_sequence_filter_groups();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
    }

    /**
     * Return the configuration array for the specified Filter Group if found.
     *
     * @param integer $id   filter group ID
     * @param string  $name option filter group name
     *
     * @return array $cfg Filter Group configuration
     */

    public function get_filter_group_configuration($id, $name = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $cfg = array();

        if ($id <= 0 && $name == NULL) {
            throw new Filter_Group_Not_Found_Exception();
        } else if ($id >= 1) {
            $cfg = $this->_get_configuration_by_filename(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), FALSE);

            if ($cfg == NULL)
                throw new Filter_Group_Not_Found_Exception();
        } else {
            $folder = new Folder(self::BASE_PATH);

            $files = $folder->get_listing();

            $ids = array();

            foreach ($files as $file) {
                if (sscanf($file, 'dansguardianf%d.conf', $id) != 1)
                    continue;
                $ids[] = $id;
            }

            $found = FALSE;

            foreach ($ids as $id) {
                $cfg = $this->_get_configuration_by_filename(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id),
                    FALSE);

                foreach ($cfg as $line) {
                    if (!preg_match("/^groupname\s*=\s*'$name'.*/", $line)) continue;
                    $found = TRUE;
                    break;
                }

                if ($found) break;
            }

            if (!$found)
                throw new Filter_Group_Not_Found_Exception();
        }

        $group = array();

        if ($id == 1)
            $group['groupname'] = lang('content_filter_default');

        foreach ($cfg as $line) {
            list($key, $value) = explode('=', str_replace(array('\'', '"'), '', $line), 2);

            if ($id == 1 && $key == 'groupname')
                continue;

            $group[trim($key)] = trim($value);
        }

        ksort($group);

        return $group;
    }

    /**
     * Return users of a given filter group.
     *
     * @param int $id Filter Group Id
     *
     * @return array $users Filter Group users
     */

    public function get_filter_group_users($id)
    {
        clearos_profile(__METHOD__, __LINE__);
        $users = array();

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            try {
                $fglist = str_replace(array('\'', '"'), '',
                    $file->lookup_value('/^filtergroupslist\s*=\s*/'));
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
            }
            $lines = array();
            $file = new File($fglist);
            try {
                $lines = $file->get_contentsAsArray();
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
            }
            foreach ($lines as $line) {
                if (!preg_match("/^.*=filter$id.*$/", $line)) continue;
                # Ignore commented lines
                if (preg_match("/^[[:space:]]*#.*$/", $line)) continue;
                $users[] = preg_replace("/^(.*)=filter$id.*$/", '\1', $line);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }

        return $users;
    }

    /**
     * Add a user to a given filter group.
     *
     * @param int $id Filter Group Id
     * @param string $user User to add
     *
     * @return void
     */

    public function add_filter_group_user($id, $user)
    {
        clearos_profile(__METHOD__, __LINE__);

        $groups = 0;

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $groups = $file->lookup_value('/^filtergroups\s*=\s*/');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }

        try {
            for ($i = 0; $i < $groups; $i++) {
                $users = $this->GetFilterGroupUsers($i + 1);
                if (!in_array($user, $users)) continue;
                # Already exists in group?  If so, bail.
                if ($id == $i + 1)
                    return;
                $cfg = $this->GetFilterGroupConfiguration($i + 1);
                throw new Engine_Exception(DANSGUARDIAN_LANG_ERR_USER_EXISTS
                    . ' - ' . $user . ' - ' . $cfg['groupname'] . " (#$id)", COMMON_ERROR);
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
        $fglist = NULL;
        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $fglist = str_replace(array('\'', '"'), '',
                $file->lookup_value('/^filtergroupslist\s*=\s*/'));
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
        try {
            $file = new File($fglist);
            $file->add_lines("$user=filter$id\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
    }

    /**
     * Delete a user from a given filter group.
     *
     * @param int $id Filter Group Id
     * @param string $user User to delete
     *
     * @return void
     */

    public function delete_filter_group_user($id, $user)
    {
        clearos_profile(__METHOD__, __LINE__);
        $groups = 0;

        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $groups = $file->lookup_value('/^filtergroups\s*=\s*/');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }

        $fglist = NULL;
        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $fglist = str_replace(array('\'', '"'), '',
                $file->lookup_value('/^filtergroupslist\s*=\s*/'));
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
        try {
            $file = new File($fglist);
            $file->delete_lines("/^$user=filter$id.*$/");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
    }

    /**
     * Deletes all users from a given filter group.
     *
     * @param int $id Filter Group Id
     *
     * @return void
     */

    public function delete_filter_group_users($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        $fglist = NULL;
        try {
            $file = new File(self::FILE_CONFIG, TRUE);
            $fglist = str_replace(array('\'', '"'), '',
                $file->lookup_value('/^filtergroupslist\s*=\s*/'));
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
        try {
            $file = new File($fglist);
            $file->delete_lines("/^.*=filter$id.*$/");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }
    }

    /**
     * Return an array of all filter groups
     *
     *
     * @return array $groups Array of filter groups.
     */

    public function get_filter_groups()
    {
        clearos_profile(__METHOD__, __LINE__);

        $groups = array();
        $folder = new Folder(self::BASE_PATH);

        $files = $folder->get_listing();

        $ids = array();

        foreach ($files as $file) {
            if (sscanf($file, 'dansguardianf%d.conf', $id) != 1)
                continue;
            $ids[] = $id;
        }

        foreach ($ids as $id)
            $groups[$id] = $this->get_filter_group_configuration($id);

        return $groups;
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validates group ID.
     *
     * @param integer $id group ID
     *
     * @return string error message if group ID is invalid
     */

    public function validate_group_id($id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_numeric($id) || ($id < 0) || ($id > self::MAX_FILTER_GROUPS))
            return lang('content_filter_group_id_invalid');
    }

    /**
     * Validates group name.
     *
     * @param string $name group name
     *
     * @return string error message if group name is invalid
     */

    public function validate_group_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^[a-z0-9_\-]+$/', $name))
            return lang('content_filter_group_name_invalid');
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
     * @param integer $limit naughtyness level
     *
     * @return boolean TRUE if naughtyness level is valid
     */

    public function is_valid_naughtyness_limit($limit)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_numeric($limit))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for filter group mode.
     *
     * @param integer $mode filter group mode
     *
     * @return boolean TRUE if filter group mode is valid
     */

    public function is_valid_filter_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_numeric($mode))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for reporting level.
     *
     * @param integer $level reporting level
     *
     * @return boolean TRUE if reporting level is valid
     */

    public function is_valid_reporting_level($level)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_numeric($level) && ($level<=3) && ($level >= -1))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for filter port number.
     *
     * @param integer $port filter port number
     *
     * @return boolean TRUE if filter port number is valid
     */

    public function is_valid_filter_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^\d+$/", $port))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for proxy IP.
     *
     * @param string $ip IP address
     *
     * @return boolean TRUE if IP address is valid
     */

    public function is_valid_proxy_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([0-9\.\-]*)$/", $ip))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for proxy port.
     *
     * @param integer $port proxy port number
     *
     * @return boolean TRUE if proxy port number is valid
     */

    public function is_valid_proxy_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^\d+$/", $port))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for PICS.
     *
     * @param string $pics PICS value
     *
     * @return boolean if PICS value is valid
     */

    public function is_valid_p_i_c_s($pics)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^[". implode("|", $this->GetPossiblePics()) ."]/", $pics))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for web sites.
     *
     * @param string $site web site
     *
     * @return boolean TRUE if web site is valid
     */

    public function is_valid_site($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^[\w\_\.\-]+$|^\*ip$|^\*ips$|^\*\*$|^\*\*s$/", $site))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for URLs.
     *
     * @param string $url URL
     *
     * @return boolean TRUE if URL is valid
     */

    public function is_valid_url($url)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([\w\._\-\/]+)$/", $url))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for file extensions.
     *
     * @param string $extension file extension
     *
     * @return boolean TRUE if file extension is valid
     */

    public function is_valid_extension($extension)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([\w\-\.]+)$/", $extension))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for MIME.
     *
     * @param string $mime MIME type
     *
     * @return boolean TRUE if MIME type is valid
     */

    public function is_valid_m_i_m_e($extension)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (preg_match("/^([\w\/\-]*)$/", $extension))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for IPs.
     *
     * @param string $ip IP address
     *
     * @return boolean TRUE if IP address is valid
     */

    public function is_valid_ip_format($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // This is for a 'DansGuardian' IP (asterisks and other funny chars are allowed)
        if (preg_match("/^([0-9\.\-]*)$/", $ip))
            return TRUE;

        return FALSE;
    }

    /**
     * Validation routine for locale.
     *
     * @param string $locale locale
     *
     * @return boolean TRUE if locale is valid
     */

    public function is_valid_locale($locale)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (in_array($locale, $this->locales))
            return TRUE;
        else
            return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Add group to file designated by the key in the configuration file.
     *
     * @param string $key key in the configuration file
     * @param string $groupname group name
     *
     * @access private
     * @return void
     */

    protected function _add_group_by_key($key, $groupname)
    {
        clearos_profile(__METHOD__, __LINE__);
        // Validate
        //---------

        $cfgfile = $this->_get_filename_by_key($key);

        $group = new File_Group($groupname, $cfgfile);

        $groupexists = $group->exists();

        if ($groupexists)
            throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_GROUP_EXISTS, COMMON_ERROR);

        // Grab information from master group file
        //----------------------------------------

        $groupitems = array();

        $mastergroup = new File_Group($groupname, self::FILE_GROUPS);

        $groupitems = $mastergroup->get_entries();

        $group->Add($groupitems);
    }

    /**
     * Add items to file designated by the key in the configuration file.
     *
     * @param   key           key in the configuration file
     * @param   items         array of items
     *
     * @access private
     * @return void
     */

    protected function _add_items_by_key($key, $items, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);
        $filename = $this->_get_configuration_value($key, $group);

        // Make sure entry does not already exist
        //---------------------------------------

        $contents = array();
        $existlist = array();
        $file = new File($filename);
        try {
            if (!$file->exists())
                $file->create('root', 'root', '0644');
            else
                $contents = $file->get_contents();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_ERROR);
        }

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
     * Delete group from a list.
     *
     * @param   key            key
     * @param   groupname      groupname
     *
     * @access private
     * @return void
     */

    protected function _delete_group_by_key($key, $groupname)
    {
        clearos_profile(__METHOD__, __LINE__);
        $cfgfile = $this->_get_filename_by_key($key);

        $group = new File_Group($groupname, $cfgfile);

        $group->Delete();
    }

    /**
     * Delete items to file designated by the key in the configuration file.
     *
     * @param   key           key in the configuration file
     * @param   items         array of items
     *
     * @access private
     * @return void
     */

    protected function _delete_items_by_key($key, $items, $group)
    {
        clearos_profile(__METHOD__, __LINE__);
        $filename = $this->_get_configuration_value($key, $group);

        $file = new File($filename);

        if (!is_array($items))
            $items = array($items);

        foreach ($items as $item) {
            $item = preg_quote($item, "/");
            $file->delete_lines("/^$item\$/");
        }
    }

    /**
     * Generic parameter fetch.
     *
     * @param string $key configuration file key
     * @param integer $group Filter group ID (default: 0)
     *
     * @access private
     * @return string key value
     */

    protected function _get_configuration_value($key, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        // The configuration in version 2.8 was split.  This was done to
        // group configurations (good).  For now, we simply manage this
        // split here... feel free to redo this when group support is
        // added.

        if (in_array($key, $this->group_keys) && $group > 0)
            $filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $group);
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
     * @access private
     * @param   key    configuration file key
     *
     * @return array lines in target file
     */

    protected function _get_configuration_data_by_key($key, $isgroup, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);
        $filename = $this->_get_configuration_value($key, $group);

        $lines = array();
        $lines = $this->_get_configuration_by_filename($filename, $isgroup, $group);

        return $lines;
    }

    /**
     * Generic fetch of file contents.
     *
     * @param string $filename configuration file
     *
     * @access private
     * @return array lines in target file
     */

    protected function _get_configuration_by_filename($filename, $isgroup, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        $cfgfile = new File($filename);
        $rawdata = $cfgfile->get_contents();

        // Parse all non-commented lines in the file
        //------------------------------------------

        $list = array();
        $rawlines = array();
        $rawlines = explode("\n", $rawdata);

        foreach ($rawlines as $line) {
            // Skip blank lines, comments, other include files

            if (! (preg_match("/^#/", $line) ||
                preg_match("/^.Include/", $line) || preg_match("/^\s*$/", $line))) {
                $list[] = $line;
                // TODO: clean this up (part of a last minute change)
            } else if (preg_match("/^.Include/", $line) &&
                ($filename == self::FILE_PHRASE_LIST ||
                    $filename == sprintf(self::FILE_PHRASE_LIST . '%d', $group))) $list[] = $line;
        }

        //-----------------------------------------------------------
        // Return either group information, or individual information
        //-----------------------------------------------------------

        // Grab the list of groups
        //------------------------

        $groupmanager = new File_Group_Manager($filename);
        $grouplist = array();
        $grouplist = $groupmanager->get_groups();

        // Put all the items in every group into an array
        //-----------------------------------------------

        $groupentries = array();
        foreach ($grouplist as $groupname) {
            try {
                $group = new File_Group($groupname, $filename);
                $newentries = $group->get_entries();
                $groupentries = array_merge($newentries, $groupentries);
            } catch(Exception $e) { }
        }

        // Split list into groups/non-groups
        //----------------------------------

        $items = array();
        $groupitems = array();

        foreach ($list as $value) {
            if (in_array($value, $groupentries))
                $groupitems[] = $value;  // Just used for testing
            else $items[] = $value;
        }

        if ($isgroup)
            return $grouplist;
        else
            return $items;
    }

    /**
     * Returns the target file name for a given dansguardian.conf key.
     *
     * @param string $param Key to search for
     * @param integer $group Filter group ID (default: 0)
     *
     * @return string full path of file
     */

    protected function _get_filename_by_key($param, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (in_array($param, $this->group_keys))
            $filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $group);
        else
            $filename = self::FILE_CONFIG;

        $file = new File($filename);
        $filename = $file->lookup_value("/^".$param ."\s*=\s*'/i");
        $filename = preg_replace("/'/", '', $filename);

        return $filename;
    }

    /**
     * Returns keyed configuration by filename.
     *
     * @param string $filename filename
     *
     * @access private
     * @return array configuration
     */

    protected function _get_keyed_configuration_by_filename($filename)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rawlist = $this->_get_configuration_by_filename($filename, FALSE);

        $list = array();

        foreach ($rawlist as $line) {
            $items = explode("|", $line);
            $key = trim($items[0]);
            $description = isset($items[1]) ? trim($items[1]) : "";
            $list[$key] = $description;
        }

        ksort($list);

        return $list;
    }

    /**
     * (Re)sequence filter group IDs.
     *
     * Called automatically after adding or deleting a filter group.
     *
     * @access private
     * @return void
     */

    protected function _sequence_filter_groups()
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

            $file = new File(self::FILE_CONFIG, TRUE);

            $fglist = str_replace(array('\'', '"'), '', $file->lookup_value('/^filtergroupslist\s*=\s*/'));

            $file = new File($fglist);

            $file->replace_lines_by_pattern(
                sprintf('/^(.*)=filter%d$/', $ids[$i]['id']),
                sprintf('$1=filter%d', $i + 1)
            );

            $file = new File(self::BASE_PATH . '/' . $ids[$i]['file'], TRUE);

            foreach ($this->group_keys as $key) {

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
     * @param string  $key   configuration key
     * @param string  $value value for the configuration key
     * @param integer $group group ID
     *
     * @access private
     * @return void
     */

    protected function _set_configuration_value($key, $value, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (in_array($key, $this->group_keys) && $group > 0)
            $filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $group);
        else
            $filename = self::FILE_CONFIG;

        $file = new File($filename);

        $match = $file->replace_lines("/^#*\s*$key\s=/", "$key = $value\n");

        if (!$match)
            $file->add_lines("$key = $value\n");
    }

    /**
     * Generic set for a configuration file by key in configuration file.
     *
     * @param string  $key   configuration key
     * @param string  $lines array of lines in config file
     * @param integer $group group ID
     *
     * @access private
     * @return void
     */

    protected function _set_configuration_by_key($key, $lines, $group = 1)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($lines))
            $lines = array($lines);

        $filename = $this->_get_configuration_value($key, $group);
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
            if ($this->IsValidSite($value) || $this->IsValidIpFormat($value)) {
                $sites[] = $value;
            } else if ($this->IsValidUrl($value)) {
                $urls[] = $value;
            } else if (preg_match("/^\s$/", $value)) {
                continue; // Ignore blank entries
            } else {
                throw new Engine_Exception(DANSGUARDIAN_LANG_ERRMSG_EXCEPTIONLIST_INVALID, COMMON_ERROR);
            }
        }
    }
}
