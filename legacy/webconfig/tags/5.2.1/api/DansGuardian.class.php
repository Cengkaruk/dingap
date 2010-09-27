<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
// Copyright 2005 Fernand Jonker -- Greylist methods
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * DansGuardian filtering software.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('FileGroup.class.php');
require_once('FileGroupManager.class.php');
require_once('Network.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * DansGuardian filtering software.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class DansGuardian extends Daemon
{
	///////////////////////////////////////////////////////////////////////////
	// C O N S T A N T S
	///////////////////////////////////////////////////////////////////////////

	const PATH_BASE= '/etc/dansguardian';
	const PATH_BLACKLISTS = '/etc/dansguardian/blacklists';
	const PATH_PHRASELISTS = '/etc/dansguardian/phraselists';
	const PATH_LOCALE = '/etc/dansguardian/languages';
	const PATH_LOGS = '/var/log/dansguardian';
	const FILE_CONFIG = '/etc/dansguardian/dansguardian.conf';
	const FILE_CONFIG_GROUP_DEFAULT = '/etc/dansguardian/dansguardianf1.conf';
	const FILE_EXTENSIONS_LIST = '/etc/dansguardian/bannedextensionlist.all';
	const FILE_MIME_LIST = '/etc/dansguardian/bannedmimetypelist.all';
	const FILE_GROUPS = '/etc/dansguardian/groups';

	///////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Dansguardian constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct('dansguardian');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Add IP to exception list.
	 *
	 * @param string ip IP address
	 * @return void
	 */

	public function AddExceptionIp($ip)
	{
		$this->AddItemsByKey("exceptioniplist", $ip);
	}

	/**
	 * Add a group to the exception list.
	 *
	 * @param string groupname name of group
	 * @return void
	 */

	public function AddExceptionIpGroup($groupname)
	{
		$this->AddGroupByKey("exceptioniplist", $groupname);
	}

	/**
	 * Add site or URL to exception list.
	 *
	 * @param   siteurl    site or URL
	 * @returns void
	 */

	public function AddExceptionSiteAndUrl($siteurl)
	{
		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Add to list
		//------------

		if (count($sites) > 0)
			$this->AddItemsByKey("exceptionsitelist", $sites);

		if (count($urls) > 0)
			$this->AddItemsByKey("exceptionurllist", $urls);
	}

	/**
	 * Add site or URL to grey list.
	 *
	 * @param   siteurl    site or URL
	 * @returns void
	 */

	public function AddGreySiteAndUrl($siteurl)
	{
		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Add to list
		//------------

		if (count($sites) > 0)
			$this->AddItemsByKey("greysitelist", $sites);

		if (count($urls) > 0)
			$this->AddItemsByKey("greyurllist", $urls);
	}

	/**
	 * Add IP to banned list.
	 *
	 * @param   ip          IP address
	 * @returns void
	 */

	public function AddBannedIp($ip)
	{
		$this->AddItemsByKey("bannediplist", $ip);
	}


	/**
	 * Add a group to the banned IP list.
	 *
	 * @param   groupname    name of group
	 * @returns void
	 */

	public function AddBannedIpGroup($groupname)
	{
		$this->AddGroupByKey("bannediplist", $groupname);
	}


	/**
	 * Add site or URL to banned list.
	 *
	 * @param   siteurl    site or URL
	 * @returns void
	 */

	public function AddBannedSiteAndUrl($siteurl)
	{
		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Add to list
		//------------

		if (count($sites) > 0)
			$this->AddItemsByKey("bannedsitelist", $sites);

		if (count($urls) > 0)
			$this->AddItemsByKey("bannedurllist", $urls);
	}


	/**
	 * Add group definition.
	 *
	 * @param   groupname     group name
	 * @returns void
	 */

	public function AddGroup($groupname)
	{
		$group = new FileGroup($groupname, self::FILE_GROUPS);
		$group->Add("");
	}


	/**
	 * Add group member to given group. This will cascade all the entries
	 * to the configuration files that use the group feature.
	 *
	 * @param   groupname     group name
	 * @param   member        member
	 * @returns void
	 */

	public function AddGroupEntry($groupname, $member)
	{
		$network = new Network();

		if (!$network->IsValidIp($member))
			return;

		// Add member to banned IP list
		//-----------------------------

		$bannedipfile = $this->GetFilenameByKey("bannediplist");

		$bannedgroup = new FileGroup($groupname, $bannedipfile);

		if ($bannedgroup->Exists())
			$bannedgroup->AddEntry($member);

		// Add member to exception IP list
		//--------------------------------

		$exceptionipfile = $this->GetFilenameByKey("exceptioniplist");

		$exceptiongroup = new FileGroup($groupname, $exceptionipfile);

		if ($exceptiongroup->Exists())
			$exceptiongroup->AddEntry($member);

		// Add to group file
		//------------------

		$group = new FileGroup($groupname, self::FILE_GROUPS);

		$group->AddEntry($member);
	}

	/**
	 * Add a new file extension to the default list.
	 *
	 * @param   extension    file extension
	 * @param   description  a short description of the extension
	 * @returns void
	 */

	public function AddPossibleExtension($extension, $description)
	{
		if (! $this->IsValidExtension($extension) || preg_match("/\|/", $description))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_EXTENSION_INVALID, COMMON_WARNING);

		// We accept extensions with or without the leading dot
		$extension = preg_replace("/^\./", "", $extension);

		$extension = str_pad(".$extension", 6);

		if (strlen($description) == 0)
			$description = $extension;

		try {
			$file = new File(self::FILE_EXTENSIONS_LIST);
			$file->AddLines("$extension|$description\n");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Add a new MIME to the default list.
	 *
	 * @param   mime         MIME
	 * @param   description  a short description of the MIME type
	 * @returns void
	 */

	public function AddPossibleMime($mime, $description)
	{
		if (! $this->IsValidMIME($mime) || preg_match("/\|/", $description))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_MIME_INVALID, COMMON_WARNING);

		if (strlen($description) == 0)
			$description = $mime;

		try {
			$file = new File(self::FILE_MIME_LIST);
			$file->AddLines("$mime|$description\n");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/** 
	 * Removes unavailable blacklists from configuration files.
	 *
	 * @return array list of bad blacklists configurations
	 */

	public function CleanBlacklists()
	{
		$configured = $this->GetBlacklists();
		$blacklists = $this->GetPossibleBlacklists();

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
			}
		}

		$badlist = array_unique($badlist);
		$cleanlist = array_unique($cleanlist);

		$this->SetBlacklists($cleanlist);

		return $badlist;
	}

	/** 
	 * Removes unavailable weighted phrase lists  from configuration files.
	 *
	 * @return array list of bad phrase list configurations
	 */

	public function CleanWeightedPhraselists()
	{
		$configured = $this->GetWeightedPhraseLists();
		$phraselists = $this->GetPossibleWeightedPhrase();

		$available = array();

		foreach ($phraselists as $listid => $listinfo)
			$available[] = $listinfo['name'];

		$badlist = array();
		$cleanlist = array();

		foreach ($configured as $list) {
			if (in_array($list, $available)) {
				$cleanlist[] = $list;
			} else {
				$badlist[] = $list;
			}
		}

		$badlist = array_unique($badlist);
		$cleanlist = array_unique($cleanlist);

		$this->SetWeightedPhraseLists($cleanlist);

		return $badlist;
	}


	/**
	 * Delete an IP from the banned IP list.
	 *
	 * @param   IP        IP address
	 * @returns void
	 */

	public function DeleteBannedIp($ip)
	{
		$this->DeleteItemsByKey("bannediplist", $ip);
	}

	/**
	 * Delete group from the banned IP list.
	 *
	 * @param   groupname     group name
	 * @returns void
	 */

	public function DeleteBannedIpGroup($groupname)
	{
		$this->DeleteGroupByKey("bannediplist", $groupname);
	}


	/**
	 * Delete item from banned sites and URLs.
	 *
	 * @returns void
	 */

	public function DeleteBannedSiteAndUrl($siteurl)
	{
		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Delete from list
		//-----------------

		if (count($sites) > 0)
			$this->DeleteItemsByKey("bannedsitelist", $sites);

		if (count($urls) > 0)
			$this->DeleteItemsByKey("bannedurllist", $urls);
	}

	/**
	 * Delete IP from the exception list.
	 *
	 * @param   ip        IP address
	 * @returns void
	 */

	public function DeleteExceptionIp($ip)
	{
		$this->DeleteItemsByKey("exceptioniplist", $ip);
	}


	/**
	 * Delete group from the exception list.
	 *
	 * @param   groupname     group name
	 * @returns void
	 */

	public function DeleteExceptionIpGroup($groupname)
	{
		$this->DeleteGroupByKey("exceptioniplist", $groupname);
	}


	/**
	 * Delete site or URL from the exception list.
	 *
	 * @param   siteurl   site or URL
	 * @returns void
	 */

	public function DeleteExceptionSiteAndUrl($siteurl)
	{
		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Delete from list
		//-----------------

		if (count($sites) > 0)
			$this->DeleteItemsByKey("exceptionsitelist", $sites);

		if (count($urls) > 0)
			$this->DeleteItemsByKey("exceptionurllist", $urls);
	}


	/**
	 * Delete site or URL from the grey list.
	 *
	 * @param   siteurl   site or URL
	 * @returns void
	 */

	public function DeleteGreySiteAndUrl($siteurl)
	{
		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Delete from list
		//-----------------

		if (count($sites) > 0)
			$this->DeleteItemsByKey("greysitelist", $sites);

		if (count($urls) > 0)
			$this->DeleteItemsByKey("greyurllist", $urls);
	}


	/**
	 * Delete group definition.  This will also delete all the entries
	 * from configuration files that use the group feature.
	 *
	 * @param   groupname     group name
	 * @returns void
	 */

	public function DeleteGroup($groupname)
	{
		$bannedipfile = $this->GetFilenameByKey("bannediplist");

		$exceptionipfile = $this->GetFilenameByKey("exceptioniplist");

		// Delete group items from banned IP list
		//---------------------------------------

		$bannedgroup = new FileGroup($groupname, $bannedipfile);

		if ($bannedgroup->Exists())
			$bannedgroup->Delete();

		// Delete group items from exception IP list
		//------------------------------------------

		$exceptiongroup = new FileGroup($groupname, $exceptionipfile);

		if ($exceptiongroup->Exists())
			$exceptiongroup->Delete();

		// Delete group
		//-------------

		$group = new FileGroup($groupname, self::FILE_GROUPS);

		$group->Delete();
	}

	/**
	 * Delete group member to given group. This will cascade all the entries
	 * to the configuration files that use the group feature.
	 *
	 * @param   groupname     group name
	 * @param   member        member
	 * @returns void
	 */

	public function DeleteGroupEntry($groupname, $member)
	{
		$network = new Network();

		if (!$network->IsValidIp($member))
			throw new EngineException(NETWORK_LANG_IP . " - " . LOCALE_LANG_INVALID, COMMON_WARNING);

		// Delete member to banned IP list
		//--------------------------------

		$bannedipfile = $this->GetFilenameByKey("bannediplist");

		$bannedgroup = new FileGroup($groupname, $bannedipfile);

		if ($bannedgroup->Exists())
			$bannedgroup->DeleteEntry($member);

		// Delete member to exception IP list
		//-----------------------------------

		$exceptionipfile = $this->GetFilenameByKey("exceptioniplist");

		$exceptiongroup = new FileGroup($groupname, $exceptionipfile);

		try {
			if ($exceptiongroup->Exists())
				$exceptiongroup->DeleteEntry($member);
		} catch (Exception $e) {
			// XXX: keep going
		}

		// Delete in group file
		//---------------------

		$group = new FileGroup($groupname, self::FILE_GROUPS);

		try {
			$group->DeleteEntry($member);
		} catch (Exception $e) {
			// XXX: keep going
		}
	}


	/**
	 * Gets the access denied page.
	 *
	 * @returns string
	 * @return  access denited URL
	 */

	public function GetAccessDeniedUrl()
	{
		$url = $this->GetConfigurationValue("accessdeniedaddress");
		return $url;
	}


	/**
	 * Get the banned extension list
	 *
	 * @returns array
	 * @return  list of banned extensions
	 */

	public function GetBannedExtensions()
	{
		$list = array();
		$list = $this->GetConfigurationDataByKey("bannedextensionlist", false);

		return $list;
	}


	/**
	 * Get the list of banned urls and sites.
	 *
	 * @returns array
	 * @return  Banned
	 */

	public function GetBannedSitesAndUrls()
	{
		$sitelist = array();
		$urllist = array();
		$list = array();

		$sitelist = $this->GetConfigurationDataByKey("bannedsitelist", false);

		$urllist = $this->GetConfigurationDataByKey("bannedurllist", false);

		$list = array_merge($urllist, $sitelist);

		return $list;
	}


	/**
	 * Get the list of banned IPs.
	 *
	 * @returns array
	 * @return  list of banned IPs
	 */

	public function GetBannedIps()
	{
		$list = array();
		$list = $this->GetConfigurationDataByKey("bannediplist", false);

		return $list;
	}


	/**
	 * Get the list of banned IP groups.
	 *
	 * @returns array
	 * @return  list of banned IP groups
	 */

	public function GetBannedIpsGroups()
	{
		$list = array();
		$list = $this->GetConfigurationDataByKey("bannediplist", true);

		return $list;
	}


	/**
	 * Get the banned MIME list.
	 *
	 * @returns array
	 * @return  list of banned MIMEs
	 */

	public function GetBannedMimes()
	{
		$list = array();
		$list = $this->GetConfigurationDataByKey("bannedmimetypelist", false);

		return $list;
	}


	/**
	 * Returns activate blacklists.
	 *
	 * @returns array
	 * @return  list of activated blacklists
	 */

	public function GetBlacklists()
	{
		$bannedtypes = array();
		$bannedtypes[] = "bannedsitelist";
		$bannedtypes[] = "bannedurllist";

		$active = array();

		foreach ($bannedtypes as $type) {
			$bannedfile = $this->GetFilenameByKey($type);

			$file = new File($bannedfile);

			if (! $file->Exists())
				continue;

			try {
				$contents = $file->GetContents();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}

			$lines = explode("\n", $contents);

			foreach ($lines as $line) {
				if (preg_match("/^\.Include/", $line)) {
					$listname = preg_replace("/.*blacklists\//", "", $line);
					$listname = preg_replace("/\/.*/", "", $listname);
					$active[] = $listname;
				}
			}
		}

		ksort($active); // remove duplicates

		return $active;
	}


	/**
	 * Get list of available blacklists.
	 *
	 * @returns array
	 * @return  list of available blacklists
	 */

	public function GetPossibleBlacklists()
	{
		$blacklistsinfo = array();
		$blacklistslist = array();
		$folderlist = array();

		$folder = new Folder(self::PATH_BLACKLISTS);

		if ($folder->Exists()) {
			try {
				$folderlist = $folder->GetListing();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
		}

		// Create our list (with descriptions)
		//------------------------------------

		foreach ($folderlist as $foldername) {
			$folder = new Folder(self::PATH_BLACKLISTS . "/$foldername");

			if ($folder->IsDirectory()) {
				$blacklistsinfo["name"] = $foldername;
				$descriptiontag = "DANSGUARDIAN_LANG_BLACKLIST_" . strtoupper(preg_replace("/[\-_]/", "", $foldername));

				if (defined("$descriptiontag"))
					$blacklistsinfo["description"] = constant($descriptiontag);
				else
					$blacklistsinfo["description"] = "...";

				$blacklistslist[] = $blacklistsinfo;
			}
		}

		return $blacklistslist;
	}


	/**
	 * Get the list of IPs in the exception list.
	 *
	 * @returns array
	 * @return  list of IPs that bypass the filter
	 */

	public function GetExceptionIps()
	{
		$list = array();
		$list = $this->GetConfigurationDataByKey("exceptioniplist", false);

		return $list;
	}


	/**
	 * Get the list of groups the exception list.
	 *
	 * @returns array
	 * @return  list of groups that bypass the filter
	 */

	public function GetExceptionIpsGroups()
	{
		$list = array();
		$list = $this->GetConfigurationDataByKey("exceptioniplist", true);

		return $list;
	}


	/**
	 * Get the sites and URLs in the exception list.
	 *
	 * @returns array
	 * @return  list of urls and sites in the exception list
	 */

	public function GetExceptionSitesAndUrls()
	{
		$sitelist = array();
		$urllist = array();
		$list = array();

		$sitelist = $this->GetConfigurationDataByKey("exceptionsitelist", false);

		$urllist = $this->GetConfigurationDataByKey("exceptionurllist", false);

		$list = array_merge($urllist, $sitelist);

		return $list;
	}


	/**
	 * Get the sites and URLs in the grey list.
	 *
	 * @returns array
	 * @return  list of urls and sites in the grey list
	 */

	public function GetGreySitesAndUrls()
	{
		$sitelist = array();
		$urllist = array();
		$list = array();

		$sitelist = $this->GetConfigurationDataByKey("greysitelist", false);

		$urllist = $this->GetConfigurationDataByKey("greyurllist", false);

		$list = array_merge($urllist, $sitelist);

		return $list;
	}


	/**
	 * Returns the target file name for a given dansguardian.conf key.
	 *
	 * @returns string
	 * @return  full path of file
	 */

	public function GetFilenameByKey($param)
	{
		$groupkeys = array('bannedextensionlist', 'bannedsitelist', 'bannedurllist', 'bannedmimetypelist', 'exceptionsitelist',
		                   'exceptionurllist', 'naughtynesslimit', 'picsfile', 'weightedphraselist', 'greysitelist', 'greyurllist');

		if (in_array($param, $groupkeys))
			$filename = self::FILE_CONFIG_GROUP_DEFAULT;
		else
			$filename = self::FILE_CONFIG;

		$file = new File($filename);

		$filename = $file->LookupValue("/^".$param ."\s*=\s*'/i");

		$filename = preg_replace("/'/", "", $filename);

		return $filename;
	}


	/**
	 * Get the filter port (default 8080).
	 *
	 * @returns int
	 * @return  the filter port number
	 */

	public function GetFilterPort()
	{
		$port = $this->GetConfigurationValue("filterport");

		return $port;
	}


	/**
	 * Get available groups.
	 *
	 * @returns array
	 * @return  list of available groups
	 */

	public function GetGroups()
	{
		$groupmanager = new FileGroupManager(self::FILE_GROUPS);
		$groups = array();
		$groups = $groupmanager->GetGroups();

		return $groups;
	}


	/**
	 * Get available groups in a specific config file.
	 *
	 * @returns array
	 * @return  list of groups configured in config file
	 */

	public function GetGroupsByKey($key)
	{
		$groupmanager = new FileGroupManager($key);
		$groups = array();
		$groups = $group->GetGroups();

		return $groups;
	}


	/**
	 * Get group entries.
	 *
	 * @returns array
	 * @return  list of entries in a group
	 */

	public function GetGroupEntries($groupname)
	{
		$group = new FileGroup($groupname, self::FILE_GROUPS);
		$items = array();
		$items = $group->GetEntries();

		return $items;
	}


	/**
	 * Get locale.
	 *
	 * @returns int
	 * @return  the current locale
	 */

	public function GetLocale()
	{
		$locale = $this->GetConfigurationValue("language");

		// TODO: Update functions in app-setup when updating here
		$dglocale = array();
		$dglocale['danish'] = "da_DK";
		$dglocale['german'] = "de_DE";
		$dglocale['mxspanish'] = "es_ES";
		$dglocale['french'] = "fr_FR";
		$dglocale['italian'] = "it_IT";
		$dglocale['dutch'] = "nl_NL";
		$dglocale['polish'] = "pl_PL";
		$dglocale['portuguese'] = "pt_BR";
		$dglocale['swedish'] = "sv_SE";
		$dglocale['turkish'] = "tr_TR";
		$dglocale['chinesebig5'] = "zh_CN";
		$dglocale['ukenglish'] = "en_US";

		if ($dglocale[$locale])
			return $dglocale[$locale];
		else
			return "en_US";
	}


	/**
	 * Get naughtyness level.
	 *
	 * @returns int
	 * @return  the current naughtyness limit
	 */

	public function GetNaughtynessLimit()
	{
		$level = $this->GetConfigurationValue("naughtynesslimit");

		return $level;
	}


	/**
	 * Get the PICS level
	 *
	 * @returns string
	 * @return  the current PICS level
	 */

	public function GetPics()
	{
		$pics = $this->GetConfigurationValue("picsfile");

		$pics = preg_replace("/.*pics./i", "", $pics);

		return $pics;
	}


	/**
	 * Get list of possible file extensions.
	 *
	 * @returns array
	 * @return  list of file extensions
	 */

	public function GetPossibleExtensions()
	{
		$list = array();
		$list = $this->GetKeyedConfigurationByFilename(self::FILE_EXTENSIONS_LIST);

		return $list;
	}


	/**
	 * Get list of possible locales.
	 *
	 * @returns array
	 * @return  list of locales
	 */

	public function GetPossibleLocales()
	{
		$folder = new Folder(self::PATH_LOCALE);
		$rawlist = $folder->GetListing();

		// Dansguardian does not standard locale codes...
		// TODO: put this in a global structure
		$dglocale = array();
		$dglocale['czech'] = "cs_CZ";
		$dglocale['danish'] = "da_DK";
		$dglocale['german'] = "de_DE";
		$dglocale['mxspanish'] = "es_ES";
		$dglocale['spanish'] = "es_ES";
		$dglocale['french'] = "fr_FR";
		$dglocale['italian'] = "it_IT";
		$dglocale['dutch'] = "nl_NL";
		$dglocale['polish'] = "pl_PL";
		$dglocale['portuguese'] = "pt_BR";
		$dglocale['russian-1251'] = "ru_RU";
		$dglocale['swedish'] = "sv_SE";
		$dglocale['turkish'] = "tr_TR";
		$dglocale['chinesebig5'] = "zh_CN";
		$dglocale['ukenglish'] = "en_US";

		$list = array();
		foreach ($rawlist as $item) {
			if (isset($dglocale[$item]))
				$list[] = $dglocale[$item];
		}

		sort($list);

		return $list;
	}


	/**
	 * Get list of possible MIMEs.
	 *
	 * @returns array
	 * @return  Exception
	 */

	public function GetPossibleMimes()
	{
		$list = array();
		$list = $this->GetKeyedConfigurationByFilename(self::FILE_MIME_LIST);

		return $list;
	}


	/**
	 * Get list of possible PICS levels.
	 *
	 * @returns array
	 * @return  list of PICS levels
	 */

	public function GetPossiblePics()
	{
		return array("teen", "youngadult", "tooharsh", "noblocking", "disabled");
	}


	/**
	 * Get list of possible weigthed phrase lists.
	 *
	 * @returns array
	 * @return  list of weighted phrase lists
	 */

	public function GetPossibleWeightedPhrase()
	{
		$phraselistsinfo = array();
		$phraselistslist = array();
		$folderlist = array();

		$folder = new Folder(self::PATH_PHRASELISTS);

		if (! $folder->Exists())
			return $phraselistslist;

		$folderlist = $folder->GetListing();

		// Create our list (with descriptions)
		//------------------------------------

		foreach ($folderlist as $foldername) {
			$phrasefolder = new Folder(self::PATH_PHRASELISTS . "/$foldername");

			$isfolder = $phrasefolder->IsDirectory();

			if ($isfolder) {

				// Not all phraselists directories contain weighted lists
				// (some contain just "banned" lists).
				$filenames = $phrasefolder->GetListing();
				$isweighted = false;
				foreach ($filenames as $phrasefile) {
					if (preg_match("/weighted/", $phrasefile)) {
						$isweighted = true;
						break;
					}
				}

				if ($isweighted) {
					$phraselistsinfo["name"] = $foldername;
					$descriptiontag = "DANSGUARDIAN_LANG_PHRASELIST_" . strtoupper($foldername);

					if (defined("$descriptiontag"))
						$phraselistsinfo["description"] = constant($descriptiontag);
					else
						$phraselistsinfo["description"] = "...";

					$phraselistslist[] = $phraselistsinfo;
				}
			}
		}

		return $phraselistslist;
	}


	/**
	 * Get the proxy IP (default: 127.0.0.1).
	 *
	 * @returns int
	 * @return  the proxy IP
	 */

	public function GetProxyIp()
	{
		$ip = $this->GetConfigurationValue("proxyip");

		return $ip;
	}


	/**
	 * Get the proxy port (default 3128)
	 *
	 * @returns int
	 * @return  the current proxy port
	 */

	public function GetProxyPort()
	{
		$port = $this->GetConfigurationValue("proxyport");

		return $port;
	}


	/**
	 * Get the reporting level.
	 *
	 * @returns int
	 * @return  the current reporting level
	 */

	public function GetReportingLevel()
	{
		$level = $this->GetConfigurationValue("reportinglevel");

		return $level;
	}


	/**
	 * Get the weight phrase list.
	 *
	 * @returns array
	 * @return  A list of the weight phrases
	 */

	public function GetWeightedPhraseLists()
	{
		$rawlines = $this->GetConfigurationDataByKey("weightedphraselist", false);

		foreach ($rawlines as $line) {
			if (preg_match("/^.Include/", $line)) {
				$listitem = preg_replace("/.*\/phraselists\//", "", $line);
				$listitem = preg_replace("/\/weighted[a-z\_]*>\s*/", "", $listitem);
				$list[] = $listitem;
			}
		}

		$list = array_unique($list);

		return $list;
	}


	/**
	 * Sets the access denied page.
	 *
	 * @returns void
	 */

	public function SetAccessDeniedUrl($url)
	{
		// Validate -- TODO
		//---------

		$this->SetConfigurationValue("accessdeniedaddress", $url);
	}


	/**
	 * Set the list of banned extensions.
	 *
	 * @param   banlist   list of banned extensions
	 * @returns void
	 */

	public function SetBannedExtensions($banlist)
	{
		// Validate
		//---------

		if (! is_array($banlist))
			$banlist = array($banlist);

		foreach ($banlist as $value) {
			if ($value && !$this->IsValidExtension($value))
				throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_EXTENSION_INVALID, COMMON_ERROR);
		}

		$this->SetConfigurationByKey("bannedextensionlist", $banlist);
	}


	/**
	 * Set the list of banned MIMEs.
	 *
	 * @param   banlist   list of MIMEs
	 * @returns void
	 */

	public function SetBannedMimes($banlist)
	{
		// Validate
		//---------

		if (! is_array($banlist))
			$banlist = array($banlist);

		foreach ($banlist as $value) {
			if (! $this->IsValidMIME($value))
				throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_MIME_INVALID, COMMON_ERROR);
		}

		$this->SetConfigurationByKey("bannedmimetypelist", $banlist);
	}


	/**
	 * Set blacklist state.
	 *
	 * @param   list   list of enabled blacklists
	 * @returns void
	 */

	public function SetBlacklists($list)
	{
		$domaindata = "";
		$urldata = "";

		foreach ($list as $item) {

			// Domains/sites
			//--------------

			$path = self::PATH_BLACKLISTS . "/" . $item . "/domains";
			$file = new File($path);

			if ($file->Exists())
				$domaindata .= ".Include<$path>\n";

			// URLs
			//-----

			$path = self::PATH_BLACKLISTS . "/" . $item . "/urls";

			$file = new File($path);

			if ($file->Exists())
				$urldata .= ".Include<$path>\n";
		}

		// Update config file - domains/sites
		//-----------------------------------

		$bannedsitepath = $this->GetFilenameByKey("bannedsitelist");

		$file = new File($bannedsitepath);

		if (!$file->Exists()) {
			$file->Create("root", "root", "0644");
		}

		$file->DeleteLines("/^\.Include.*/");

		$file->AddLines($domaindata);

		// Update config file - URLs
		//--------------------------

		$bannedurlpath = $this->GetFilenameByKey("bannedurllist");

		$file = new File($bannedurlpath);

		if (!$file->Exists()) {
			$file->Create("root", "root", "0644");
		}

		$file->DeleteLines("/^\.Include.*/");

		$file->AddLines($urldata);
	}


	/*
	 * Set locale.
	 *
	 * @returns void
	 */

	public function SetLocale($locale)
	{
		// Validate
		//---------

		if (! $this->IsValidLocale($locale))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_LOCALE_INVALID, COMMON_ERROR);

		$dglocale = array();
		$dglocale['da_DK'] = "danish";
		$dglocale['de_DE'] = 'german';
		$dglocale['es_ES'] = 'mxspanish';
		$dglocale['fr_FR'] = 'french';
		$dglocale['it_IT'] = 'italian';
		$dglocale['nl_NL'] = 'dutch';
		$dglocale['pl_PL'] = 'polish';
		$dglocale['pt_BR'] = 'portuguese';
		$dglocale['sv_SE'] = 'swedish';
		$dglocale['tr_TR'] = 'turkish';
		$dglocale['zh_CN'] = 'chinesebig5';
		$dglocale['en_US'] = 'ukenglish';

		$this->SetConfigurationValue("language", $dglocale[$locale]);
	}


	/*
	 * Set naughtyness level.
	 *
	 * @returns void
	 */

	public function SetNaughtynessLimit($limit)
	{
		// Validate
		//---------

		if (! $this->IsValidNaughtynessLimit($limit))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_NAUGHTYNESS_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue("naughtynesslimit", $limit);
	}


	/**
	 * Set the PICS level....
	 *
	 * @param   port    the PICS level
	 * @returns void
	 */

	public function SetPics($pics)
	{
		// Validate
		//---------

		if (! $this->IsValidPICS($pics))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_PICS_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue("picsfile", self::PATH_BASE. "/pics.$pics");
	}


	/**
	 * Set the reporting level.
	 *
	 * @returns void
	 */

	public function SetReportingLevel($level)
	{
		// Validate
		//---------

		if (! $this->IsValidReportingLevel($level))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_REPORTINGLEVEL_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue("reportinglevel", $level);
	}


	/**
	 * Set the weight phrase list.
	 *
	 * @returns void
	 */

	public function SetWeightedPhraseLists($weightphraselist)
	{
		// Validate
		//---------

		if (! $this->IsValidWeightPhraseList($weightphraselist))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_WEIGHT_PHRASE_LIST_INVALID, COMMON_ERROR);

		$lines = array();

		foreach ($weightphraselist as $phraselist) {
			$subfolder = new Folder(self::PATH_BASE. "/phraselists/$phraselist");
			$listnames = $subfolder->GetListing();

			foreach ($listnames as $listname) {
				if (preg_match("/weighted/", $listname))
					$lines[] = ".Include<" . self::PATH_BASE. "/phraselists/$phraselist/$listname>";
			}
		}

		$this->SetConfigurationByKey("weightedphraselist", $lines);
	}


	/*************************************************************************/
	/* G E N E R I C   M E T H O D S                                         */
	/*************************************************************************/

	/**
	 * Add group to file designated by the key in the configuration file.
	 *
	 * @private
	 * @param   key           key in the configuration file
	 * @param   groupname     group name
	 * @returns void
	 */

	private function AddGroupByKey($key, $groupname)
	{
		// Validate
		//---------

		$cfgfile = $this->GetFilenameByKey($key);

		$group = new FileGroup($groupname, $cfgfile);

		$groupexists = $group->Exists();

		if ($groupexists)
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_GROUP_EXISTS, COMMON_ERROR);

		// Grab information from master group file
		//----------------------------------------

		$groupitems = array();

		$mastergroup = new FileGroup($groupname, self::FILE_GROUPS);

		$groupitems = $mastergroup->GetEntries();

		$group->Add($groupitems);
	}


	/**
	 * Add items to file designated by the key in the configuration file.
	 *
	 * @private
	 * @param   key           key in the configuration file
	 * @param   items         array of items
	 * @returns void
	 */

	private function AddItemsByKey($key, $items)
	{
		$filename = $this->GetConfigurationValue($key);

		// Make sure entry does not already exist
		//---------------------------------------

		$existlist = array();
		$file = new File($filename);
		$contents = $file->GetContents();

		$lines = explode("\n", $contents);
		foreach ($lines as $line)
		$existlist[] = $line;

		// Add list of new items to file
		//------------------------------

		if (!is_array($items))
			$items = array($items);

		// TODO - we should probably send an error when a duplicate is found
		foreach ($items as $item) {
			if (!in_array($item, $existlist))
				$filedata .= "$item\n";
		}

		$file->AddLines($filedata);
	}


	/**
	 * Delete group from a list.
	 *
	 * @private
	 * @param   key            key
	 * @param   groupname      groupname
	 * @returns void
	 */

	private function DeleteGroupByKey($key, $groupname)
	{
		$cfgfile = $this->GetFilenameByKey($key);

		$group = new FileGroup($groupname, $cfgfile);

		$group->Delete();
	}


	/**
	 * Delete items to file designated by the key in the configuration file.
	 *
	 * @private
	 * @param   key           key in the configuration file
	 * @param   items         array of items
	 * @returns void
	 */

	private function DeleteItemsByKey($key, $items)
	{
		$filename = $this->GetConfigurationValue($key);

		$file = new File($filename);

		if (!is_array($items))
			$items = array($items);

		foreach ($items as $item) {
			$item = preg_quote($item, "/");
			$file->DeleteLines("/^$item\$/");
		}
	}


	/**
	 * Generic parameter fetch.
	 *
	 * @private
	 * @param   key     configuration file key
	 * @returns string
	 * @return  key value
	 */

	private function GetConfigurationValue($key)
	{
		// The configuration in version 2.8 was split.  This was done to
		// group configurations (good).  For now, we simply manage this
		// split here... feel free to redo this when group support is
		// added.

		$groupkeys = array('bannedextensionlist', 'bannedsitelist', 'bannedurllist', 'bannedmimetypelist', 'exceptionsitelist',
		                   'exceptionurllist', 'naughtynesslimit', 'picsfile', 'weightedphraselist', 'greysitelist', 'greyurllist');

		if (in_array($key, $groupkeys))
			$filename = self::FILE_CONFIG_GROUP_DEFAULT;
		else
			$filename = self::FILE_CONFIG;

		$file = new File($filename);

		try {
			$match = $file->LookupValue("/^$key\s*=\s*/i");
		} catch (FileNoMatchException $e) {
			return;
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$match = preg_replace("/'/", "", $match);

		return $match;
	}


	/**
	 * Generic fetch of file contents specified by a key value in dansguardian.conf.
	 *
	 * @private
	 * @param   key    configuration file key
	 * @returns array
	 * @return  lines in target file
	 */

	private function GetConfigurationDataByKey($key, $isgroup)
	{
		$filename = $this->GetConfigurationValue($key);

		$lines = array();
		$lines = $this->GetConfigurationByFilename($filename, $isgroup);

		return $lines;
	}


	/**
	 * Generic fetch of file contents.
	 *
	 * @private
	 * @param   filename    configuration file
	 * @returns array
	 * @return  lines in target file
	 */

	private function GetConfigurationByFilename($filename, $isgroup)
	{
		$cfgfile = new File($filename);
		$rawdata = $cfgfile->GetContents();

		// Parse all non-commented lines in the file
		//------------------------------------------

		$list = array();
		$rawlines = array();
		$rawlines = explode("\n", $rawdata);
		foreach ($rawlines as $line) {
			// Skip blank lines, comments, other include files

			if (! (preg_match("/^#/", $line) || preg_match("/^.Include/", $line) || preg_match("/^\s*$/", $line))) {
				$list[] = $line;
				// TODO: clean this up (part of a last minute change)
			} else if ( (preg_match("/^.Include/", $line)) && ($filename == "/etc/dansguardian/weightedphraselist")) {
				$list[] = $line;
			}
		}

		//-----------------------------------------------------------
		// Return either group information, or individual information
		//-----------------------------------------------------------

		// Grab the list of groups
		//------------------------

		$groupmanager = new FileGroupManager($filename);
		$grouplist = array();
		$grouplist = $groupmanager->GetGroups();

		// Put all the items in every group into an array
		//-----------------------------------------------

		$groupentries = array();
		foreach ($grouplist as $groupname) {
			try {
				$group = new FileGroup($groupname, $filename);
				$newentries = $group->GetEntries();
				$groupentries = array_merge($newentries, $groupentries);
			} catch(Exception $e) { }

		}

		// Split list into groups/non-groups
		//----------------------------------

		$items = array();
		$groupitems = array();

		foreach ($list as $value) {
			if (in_array($value, $groupentries)) {
				$groupitems[] = $value;  // Just used for testing
			} else {
				$items[] = $value;
			}
		}

		if ($isgroup) {
			//			return $groupitems;
			return $grouplist;
		} else
			return $items;
	}


	/**
	 * Get list of possible file extensions.
	 *
	 * @private
	 * @returns array
	 * @return  list of file extensions
	 */

	private function GetKeyedConfigurationByFilename($filename)
	{
		$rawlist = array();
		$rawlist = $this->GetConfigurationByFilename($filename, false);

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
	 * Generic set for a configuration value.
	 *
	 * @private
	 * @param   key     configuration key
	 * @param   value   value for the configuration key
	 * @returns void
	 */

	private function SetConfigurationValue($key, $value)
	{
		$groupkeys = array('bannedextensionlist', 'bannedsitelist', 'bannedurllist', 'bannedmimetypelist', 'exceptionsitelist',
		                   'exceptionurllist', 'naughtynesslimit', 'picsfile', 'weightedphraselist', 'greysitelist', 'greyurllist');

		if (in_array($key, $groupkeys))
			$filename = self::FILE_CONFIG_GROUP_DEFAULT;
		else
			$filename = self::FILE_CONFIG;

		$file = new File($filename);

		$match = $file->ReplaceLines("/^$key\s=/", "$key = $value\n");

		if (!$match)
			$file->AddLines("$key = $value\n");
	}


	/**
	 * Generic set for a configuration file by key in configuration file.
	 *
	 * @private
	 * @param   key         configuration key
	 * @param   lines       array of lines in config file
	 * @returns void
	 */

	private function SetConfigurationByKey($key, $lines)
	{
		if (! is_array($lines))
			$lines = array($lines);

		$maincfg = new File(self::FILE_CONFIG);
		$filename = $this->GetConfigurationValue($key);
		$filename = preg_replace("/'/", "", $filename);

		$file = new File($filename);
		$file->DeleteLines("/^[^#]+/");
		$file->DeleteLines("/^\s*$/");

		$filedata = "";

		foreach ($lines as $line)
			$filedata .= "$line\n";

		$file->AddLines("$filedata");
	}


	/**
	 * Split an array of URLs and sites into two arrays.
	 * A site includes an entire site (microsoft.com would block all
	 * pages from that domain).  A URL is a specific page
	 * (www.microsoft.com/privacy.html would block just the privacy page).
	 *
	 * @private
	 * @para    sourcelist  list of urls and sites
	 * @param   sites       array of sites
	 * @param   urls        array of urls
	 * @returns void
	 */

	private function SplitSitesAndUrls($sourcelist, &$sites, &$urls)
	{
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
				throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_EXCEPTIONLIST_INVALID, COMMON_ERROR);
			}
		}
	}


	/*************************************************************************/
	/* V A L I D A T I O N   R O U T I N E S                                 */
	/*************************************************************************/

	/**
	 * Validation routine for Naughtyness Limit
	 *
	 * @param   limit		the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidNaughtynessLimit($limit)
	{
		if (is_numeric($limit))
			return true;

		return false;
	}


	/**
	 * Validation routine for Reporting Level
	 *
	 * @param   level		the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidReportingLevel($level)
	{
		if (is_numeric($level) && ($level<=3) && ($level >= -1))
			return true;

		return false;
	}


	/**
	 * Validation routine for Filter Port
	 *
	 * @param   port		the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidFilterPort($port)
	{
		if (preg_match("/^\d+$/", $port))
			return true;

		return false;
	}


	/**
	 * Validation routine for Proxy Ip
	 *
	 * @param   ip		the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidProxyIp($ip)
	{
		if (preg_match("/^([0-9\.\-]*)$/", $ip))
			return true;

		return false;
	}


	/**
	 * Validation routine for Proxy Port
	 *
	 * @param   port		the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidProxyPort($port)
	{
		if (preg_match("/^\d+$/", $port))
			return true;

		return false;
	}


	/**
	 * Validation routine for PICS
	 *
	 * @param   pics		the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidPICS($pics)
	{
		if (preg_match("/^[". implode("|", $this->GetPossiblePics()) ."]/", $pics))
			return true;

		return false;
	}


	/**
	 * Validation routine for weighted phrase lists
	 *
	 * @param   phraselists			an array of phrase lists
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidWeightPhraseList($phraselists)
	{
		if (! is_array($phraselists))
			return false;

		$validlists = $this->GetPossibleWeightedPhrase();

		foreach ($phraselists as $phraselist) {
			$isvalid = false;
			foreach ($validlists as $validlist) {
				if ($phraselist == $validlist['name'])
					$isvalid = true;
			}

			if (! $isvalid)
				return false;
		}

		return true;
	}


	/**
	 * Validation routine for Sites
	 *
	 * @param   list        the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidSite($site)
	{
		if (preg_match("/^[\w\_\.\-]+$|^\*ip$|^\*\*$/", $site))
			return true;

		return false;
	}


	/**
	 * Validation routine for Urls
	 *
	 * @param   list        the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidUrl($url)
	{
		if (preg_match("/^([\w\._\-\/]+)$/", $url))
			return true;

		return false;
	}


	/**
	 * Validation routine for Extensions
	 *
	 * @param   list        the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidExtension($extension)
	{
		if (preg_match("/^([\w\-\.]+)$/", $extension))
			return true;

		return false;
	}


	/**
	 * Validation routine for MIME
	 *
	 * @param   list        the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidMIME($extension)
	{
		if (preg_match("/^([\w\/\-]*)$/", $extension))
			return true;

		return false;
	}


	/**
	 * Validation routine for Ips
	 *
	 * @param   ip        the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	// This is for a 'DansGuardian' IP (asterisks and other funny chars are allowed)
	private function IsValidIpFormat($ip)
	{
		if (preg_match("/^([0-9\.\-]*)$/", $ip))
			return true;

		return false;
	}


	/**
	 * Validation routine for locale
	 *
	 * @param   locale        the value
	 * @returns boolean
	 * @return  true if is valid
	 */

	private function IsValidLocale($locale)
	{
		$dglocales = array('da_DK', 'nl_NL', 'fr_FR', 'de_DE', 'it_IT', 'es_ES', 'pt_BR', 'en_US');

		if (in_array($locale, $dglocales))
			return true;
		else
			return false;
	}

	/**
	 * @ignore
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
