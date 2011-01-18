<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2008 Point Clark Networks.
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
 * @copyright Copyright 2003-2008, Point Clark Networks
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
// E X C E P T I O N  C L A S S E S
///////////////////////////////////////////////////////////////////////////////

/**
 * Filter group not found exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2008, Point Clark Networks
 */

class FilterGroupNotFoundException extends EngineException
{
	/**
	 * FilterGroupNotFoundException constructor.
	 *
	 * @param string $errmsg error message
	 * @param int $code error code
	 */

	public function __construct($errmsg, $code)
	{
		parent::__construct($errmsg, $code);
	}
}

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
 * @copyright Copyright 2003-2008, Point Clark Networks
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
	const FILE_EXTENSIONS_LIST_ALL = '/etc/dansguardian-av/lists/bannedextensionlist.all';
	const FILE_EXTENSIONS_LIST_USER = '/etc/dansguardian-av/lists/bannedextensionlist.user';
	const FILE_MIME_LIST = '/etc/dansguardian-av/lists/bannedmimetypelist';
	const FILE_MIME_LIST_ALL = '/etc/dansguardian-av/lists/bannedmimetypelist.all';
	const FILE_MIME_LIST_USER = '/etc/dansguardian-av/lists/bannedmimetypelist.user';
	const FILE_PHRASE_LIST = '/etc/dansguardian-av/lists/weightedphraselist';
	const FILE_GROUPS = '/etc/dansguardian-av/groups';
	const MAX_FILTER_GROUPS = 9;

	///////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////

	var $groupkeys;
	var $dglocale;

	///////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////

	/**
	 * Dansguardian constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('dansguardian-av');

		$this->groupkeys = array(
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

		// Dansguardian does not standard locale codes...
		// TODO: Update functions in app-setup when updating here
		$this->dglocale['czech'] = 'cs_CZ';
		$this->dglocale['danish'] = 'da_DK';
		$this->dglocale['german'] = 'de_DE';
		$this->dglocale['mxspanish'] = 'es_ES';
		$this->dglocale['spanish'] = 'es_ES';
		$this->dglocale['french'] = 'fr_FR';
		$this->dglocale['italian'] = 'it_IT';
		$this->dglocale['dutch'] = 'nl_NL';
		$this->dglocale['polish'] = 'pl_PL';
		$this->dglocale['portuguese'] = 'pt_BR';
		$this->dglocale['russian-1251'] = 'ru_RU';
		$this->dglocale['swedish'] = 'sv_SE';
		$this->dglocale['turkish'] = 'tr_TR';
		$this->dglocale['chinesebig5'] = 'zh_CN';
		$this->dglocale['ukenglish'] = 'en_US';

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Adds IP to exception list.
	 *
	 * @param string $ip IP address
	 * @return void
	 */

	public function AddExceptionIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->AddItemsByKey('exceptioniplist', $ip);
	}

	/**
	 * Adds a group to the exception list.
	 *
	 * @param string $groupname name of group
	 * @return void
	 */

	public function AddExceptionIpGroup($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->AddGroupByKey('exceptioniplist', $groupname);
	}

	/**
	 * Adds web site or URL to exception list.
	 *
	 * @param string $siteurl site or URL
	 * @return void
	 */

	public function AddExceptionSiteAndUrl($siteurl, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Add to list
		//------------

		if (count($sites) > 0)
			$this->AddItemsByKey('exceptionsitelist', $sites, $group);

		if (count($urls) > 0)
			$this->AddItemsByKey('exceptionurllist', $urls, $group);
	}

	/**
	 * Adds web site or URL to grey list.
	 *
	 * @param string $siteurl site or URL
	 * @param integer $group group number
	 * @return void
	 */

	public function AddGreySiteAndUrl($siteurl, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		if (count($sites) > 0)
			$this->AddItemsByKey('greysitelist', $sites, $group);

		if (count($urls) > 0)
			$this->AddItemsByKey('greyurllist', $urls, $group);
	}

	/**
	 * Adds IP to banned list.
	 *
	 * @param string $ip IP address
	 * @return void
	 */

	public function AddBannedIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->AddItemsByKey('bannediplist', $ip);
	}

	/**
	 * Adds a group to the banned IP list.
	 *
	 * @param string $groupname name of group
	 * @return void
	 */

	public function AddBannedIpGroup($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->AddGroupByKey('bannediplist', $groupname);
	}


	/**
	 * Adds web site or URL to banned list.
	 *
	 * @param string $siteurl website or URL
	 * @param integer $group group number
	 * @return void
	 */

	public function AddBannedSiteAndUrl($siteurl, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		if (count($sites) > 0)
			$this->AddItemsByKey('bannedsitelist', $sites, $group);

		if (count($urls) > 0)
			$this->AddItemsByKey('bannedurllist', $urls, $group);
	}

	/**
	 * Adds group definition.
	 *
	 * @param string $groupname group name
	 * @return void
	 */

	public function AddGroup($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$group = new FileGroup($groupname, self::FILE_GROUPS);
		$group->Add('');
	}

	/**
	 * Adds group member to given group. 
	 *
	 * This will cascade all the entries to the configuration files that use the group feature.
	 *
	 * @param string $groupname group name
	 * @param string $member member
	 * @return void
	 */

	public function AddGroupEntry($groupname, $member)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if (!$network->IsValidIp($member))
			return;

		// Add member to banned IP list
		//-----------------------------

		$bannedipfile = $this->GetFilenameByKey('bannediplist');

		$bannedgroup = new FileGroup($groupname, $bannedipfile);

		if ($bannedgroup->Exists())
			$bannedgroup->AddEntry($member);

		// Add member to exception IP list
		//--------------------------------

		$exceptionipfile = $this->GetFilenameByKey('exceptioniplist');

		$exceptiongroup = new FileGroup($groupname, $exceptionipfile);

		if ($exceptiongroup->Exists())
			$exceptiongroup->AddEntry($member);

		// Add to group file
		//------------------

		$group = new FileGroup($groupname, self::FILE_GROUPS);

		$group->AddEntry($member);
	}

	/**
	 * Adds a new file extension to the user-defined list.
	 *
	 * @param string $extension file extension
	 * @param string $description a short description of the extension
	 * @return void
	 */

	public function AddUserExtension($extension, $description)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidExtension($extension) || preg_match("/\|/", $description))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_EXTENSION_INVALID, COMMON_WARNING);

		// We accept extensions with or without the leading dot
		$extension = preg_replace("/^\./", '', $extension);

		$extension = str_pad(".$extension", 6);

		if (strlen($description) == 0)
			$description = $extension;

		try {
			$file = new File(self::FILE_EXTENSIONS_LIST_USER);
			if (!$file->Exists()) $file->Create('root', 'root', '0644');
			$file->AddLines("$extension|$description\n");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes file extension from the user-defined list.
	 *
	 * @param string $extension file extension
	 * @return void
	 */

	public function DeleteUserExtension($extension)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$extension = str_replace('.', '\.', $extension);
			$file = new File(self::FILE_EXTENSIONS_LIST_USER);
			$file->DeleteLines("/$extension.*/");
			$file = new File(self::FILE_EXTENSIONS_LIST);
			$file->DeleteLines("/$extension.*/");
			for ($i = 0; $i < self::MAX_FILTER_GROUPS; $i++) {
				$file = new File(sprintf(self::FILE_EXTENSIONS_LIST . '%d', $i + 1));
				if (!$file->Exists()) continue;
				$file->DeleteLines("/$extension.*/");
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Adds a new MIME to the user-defined list.
	 *
	 * @param string $mime MIME type
	 * @param string $description a short description of the MIME type
	 * @return void
	 */

	public function AddUserMimeType($mime, $description)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidMIME($mime) || preg_match("/\|/", $description))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_MIME_INVALID, COMMON_WARNING);

		if (strlen($description) == 0)
			$description = $mime;

		try {
			$file = new File(self::FILE_MIME_LIST_USER);
			if (!$file->Exists()) $file->Create('root', 'root', '0644');
			$file->AddLines("$mime|$description\n");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes a MIME from the user-defined list.
	 *
	 * @param string $mime MIME type
	 * @return void
	 */

	public function DeleteUserMimeType($mime)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		try {
			$mime = str_replace('/', '\/', $mime);
			$file = new File(self::FILE_MIME_LIST_USER);
			$file->DeleteLines("/$mime.*/");
			$file = new File(self::FILE_MIME_LIST);
			$file->DeleteLines("/$mime.*/");
			for ($i = 0; $i < self::MAX_FILTER_GROUPS; $i++) {
				$file = new File(sprintf(self::FILE_MIME_LIST . '%d', $i + 1));
				if (!$file->Exists()) continue;
				$file->DeleteLines("/$mime.*/");
			}
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
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

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
	 * Removes unavailable weighted phrase lists  from configuration files.
	 *
	 * @return array list of bad phrase list configurations
	 */

	public function CleanWeightedPhraselists()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groups = $this->GetFilterGroups();
		$phraselists = $this->GetPossibleWeightedPhrase();
		$baddetails = array();

		foreach ($groups as $groupid => $info) {
			$configured = $this->GetWeightedPhraseLists($groupid, true);
			$available = array();

			foreach ($phraselists as $listid => $listinfo)
				$available[] = $listinfo['name'];

			$cleanlist = array();
			$badlist = array();

			foreach ($configured as $list) {
				$file = new File(self::PATH_PHRASELISTS . "/" . $list);
				if ($file->Exists()) {
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
	 * @return void
	 */

	public function DeleteBannedIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->DeleteItemsByKey('bannediplist', $ip);
	}

	/**
	 * Deletes group from the banned IP list.
	 *
	 * @param string $groupname group name
	 * @return void
	 */

	public function DeleteBannedIpGroup($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->DeleteGroupByKey('bannediplist', $groupname);
	}

	/**
	 * Deletes item from banned sites and URLs.
	 *
	 * @param string $siteurl site or URL
	 * @param integer $group group number
	 * @return void
	 */

	public function DeleteBannedSiteAndUrl($siteurl, $group)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Delete from list
		//-----------------

		if (count($sites) > 0)
			$this->DeleteItemsByKey('bannedsitelist', $sites, $group);

		if (count($urls) > 0)
			$this->DeleteItemsByKey('bannedurllist', $urls, $group);
	}

	/**
	 * Deletes IP from the exception list.
	 *
	 * @param string $ip IP address
	 * @return void
	 */

	public function DeleteExceptionIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->DeleteItemsByKey('exceptioniplist', $ip);
	}

	/**
	 * Deletes group from the exception list.
	 *
	 * @param string $groupname group name
	 * @return void
	 */

	public function DeleteExceptionIpGroup($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->DeleteGroupByKey('exceptioniplist', $groupname);
	}

	/**
	 * Deletes site or URL from the exception list.
	 *
	 * @param string $siteurl site or URL
	 * @param integer $group group number
	 * @return void
	 */

	public function DeleteExceptionSiteAndUrl($siteurl, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Delete from list
		//-----------------

		if (count($sites) > 0)
			$this->DeleteItemsByKey('exceptionsitelist', $sites, $group);

		if (count($urls) > 0)
			$this->DeleteItemsByKey('exceptionurllist', $urls, $group);
	}

	/**
	 * Deletes site or URL from the grey list.
	 *
	 * @param string $siteurl site or URL
	 * @param integer $group group number
	 * @return void
	 */

	public function DeleteGreySiteAndUrl($siteurl, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sites = array();
		$urls = array();

		$this->SplitSitesAndUrls($siteurl, $sites, $urls);

		// Delete from list
		//-----------------

		if (count($sites) > 0)
			$this->DeleteItemsByKey('greysitelist', $sites, $group);

		if (count($urls) > 0)
			$this->DeleteItemsByKey('greyurllist', $urls, $group);
	}

	/**
	 * Deletes group definition.
	 *
	 * This will also delete all the entries from configuration files that use the group feature.
	 *
	 * @param string $groupname group name
	 * @return void
	 */

	public function DeleteGroup($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$bannedipfile = $this->GetFilenameByKey('bannediplist');
		$exceptionipfile = $this->GetFilenameByKey('exceptioniplist');

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
	 * Deletes group member to given group.
	 *
	 * This will cascade all the entries to the configuration files that use the group feature.
	 *
	 * @param string $groupname group name
	 * @param string $member member
	 * @return void
	 */

	public function DeleteGroupEntry($groupname, $member)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if (!$network->IsValidIp($member))
			throw new EngineException(NETWORK_LANG_IP . ' - ' . LOCALE_LANG_INVALID, COMMON_WARNING);

		// Delete member to banned IP list
		//--------------------------------

		$bannedipfile = $this->GetFilenameByKey('bannediplist');

		$bannedgroup = new FileGroup($groupname, $bannedipfile);

		if ($bannedgroup->Exists())
			$bannedgroup->DeleteEntry($member);

		// Delete member to exception IP list
		//-----------------------------------

		$exceptionipfile = $this->GetFilenameByKey('exceptioniplist');

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
	 * Returns the access denied page.
	 *
	 * @return string access denited URL
	 */

	public function GetAccessDeniedUrl()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$url = $this->GetConfigurationValue('accessdeniedaddress');
		return $url;
	}

	/**
	 * Returns the banned extension list.
	 *
	 * @return array list of banned extensions
	 */

	public function GetBannedExtensions($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetConfigurationDataByKey('bannedextensionlist', false, $group);

		return $list;
	}

	/**
	 * Returns the list of banned URLs and sites.
	 *
	 * @return array list of banned URLs and sites
	 */

	public function GetBannedSitesAndUrls($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sitelist = array();
		$urllist = array();
		$list = array();

		try {
			$sitelist = $this->GetConfigurationDataByKey('bannedsitelist', false, $group);
		} catch (Exception $e) { }

		try {
			$urllist = $this->GetConfigurationDataByKey('bannedurllist', false, $group);
		} catch (Exception $e) { }

		$list = array_merge($urllist, $sitelist);

		return $list;
	}

	/**
	 * Returns the list of banned IPs.
	 *
	 * @return array list of banned IPs
	 */

	public function GetBannedIps()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetConfigurationDataByKey('bannediplist', false);

		return $list;
	}

	/**
	 * Returns the list of banned IP groups.
	 *
	 * @return array list of banned IP groups
	 */

	public function GetBannedIpsGroups()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetConfigurationDataByKey('bannediplist', true);

		return $list;
	}

	/**
	 * Returns the banned MIME list.
	 *
	 * @return array list of banned MIMEs
	 */

	public function GetBannedMimeTypes($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetConfigurationDataByKey('bannedmimetypelist', false, $group);

		return $list;
	}

	/**
	 * Returns activate blacklists.
	 *
	 * @return array list of activated blacklists
	 */

	public function GetBlacklists($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$bannedtypes = array();
		$bannedtypes[] = 'bannedsitelist';
		$bannedtypes[] = 'bannedurllist';

		$active = array();

		foreach ($bannedtypes as $type) {
			$bannedfile = $this->GetFilenameByKey($type, $group);

			$file = new File($bannedfile);

			if (! $file->Exists()) continue;

			try {
				$contents = $file->GetContents();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}

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

	public function GetPossibleBlacklists()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

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

	public function GetExceptionIps()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetConfigurationDataByKey('exceptioniplist', false);

		return $list;
	}

	/**
	 * Returns the list of groups the exception list.
	 *
	 * @return array list of groups that bypass the filter
	 */

	public function GetExceptionIpsGroups()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetConfigurationDataByKey('exceptioniplist', true);

		return $list;
	}

	/**
	 * Returns the sites and URLs in the exception list.
	 *
	 * @return array list of urls and sites in the exception list
	 */

	public function GetExceptionSitesAndUrls($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sitelist = array();
		$urllist = array();
		$list = array();

		try {
			$sitelist = $this->GetConfigurationDataByKey('exceptionsitelist', false, $group);
		} catch (Exception $e) { }

		try {
			$urllist = $this->GetConfigurationDataByKey('exceptionurllist', false, $group);
		} catch (Exception $e) { }

		$list = array_merge($urllist, $sitelist);

		return $list;
	}

	/**
	 * Returns the sites and URLs in the grey list.
	 *
	 * @return array list of urls and sites in the grey list
	 */

	public function GetGreySitesAndUrls($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$sitelist = array();
		$urllist = array();
		$list = array();

		try {
			$sitelist = $this->GetConfigurationDataByKey('greysitelist', false, $group);
		} catch (Exception $e) { }

		try {
			$urllist = $this->GetConfigurationDataByKey('greyurllist', false, $group);
		} catch (Exception $e) { }

		$list = array_merge($urllist, $sitelist);

		return $list;
	}

	/**
	 * Returns the target file name for a given dansguardian.conf key.
	 *
	 * @param string $param Key to search for
	 * @param integer $group Filter group ID (default: 0)
	 * @return string full path of file
	 */

	public function GetFilenameByKey($param, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (in_array($param, $this->groupkeys))
			$filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $group);
		else
			$filename = self::FILE_CONFIG;

		$file = new File($filename);
		$filename = $file->LookupValue("/^".$param ."\s*=\s*'/i");
		$filename = preg_replace("/'/", '', $filename);

		return $filename;
	}

	/**
	 * Returns the filter port (default 8080).
	 *
	 * @return integer filter port number
	 */

	public function GetFilterPort()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$port = $this->GetConfigurationValue('filterport');

		return $port;
	}

	/**
	 * Returns available groups.
	 *
	 * @return array list of available groups
	 */

	public function GetGroups()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groupmanager = new FileGroupManager(self::FILE_GROUPS);
		$groups = array();
		$groups = $groupmanager->GetGroups();

		return $groups;
	}

	/**
	 * Returns available groups in a specific config file.
	 *
	 * @return array list of groups configured in config file
	 */

	public function GetGroupsByKey($key)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groupmanager = new FileGroupManager($key);
		$groups = array();
		$groups = $group->GetGroups();

		return $groups;
	}

	/**
	 * Returns group entries.
	 *
	 * @return array list of entries in a group
	 */

	public function GetGroupEntries($groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$group = new FileGroup($groupname, self::FILE_GROUPS);
		$items = array();
		$items = $group->GetEntries();

		return $items;
	}

	/**
	 * Returns locale.
	 *
	 * @return integer the current locale
	 */

	public function GetLocale($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$locale = $this->GetConfigurationValue('language', $group);

		if ($this->dglocale[$locale])
			return $this->dglocale[$locale];
		else
			return 'en_US';
	}

	/**
	 * Returns naughtyness level.
	 *
	 * @return integer  the current naughtyness limit
	 */

	public function GetNaughtynessLimit($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$level = $this->GetConfigurationValue('naughtynesslimit', $group);

		return $level;
	}

	/**
	 * Returns the PICS level.
	 *
	 * @return string current PICS level
	 */

	public function GetPics($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$pics = $this->GetConfigurationValue('picsfile', $group);
		$pics = preg_replace("/.*pics./i", "", $pics);

		return $pics;
	}

	/**
	 * Returns list of file extensions.
	 *
	 * @return array list of file extensions
	 */

	public function GetExtensions()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetKeyedConfigurationByFilename(self::FILE_EXTENSIONS_LIST_ALL);

		return $list;
	}

	/**
	 * Returns list of user-added file extensions.
	 *
	 * @return array list of user-added file extensions
	 */

	public function GetUserExtensions()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetKeyedConfigurationByFilename(self::FILE_EXTENSIONS_LIST_USER);

		return $list;
	}

	/**
	 * Returns list of possible locales.
	 *
	 * @return array list of locales
	 */

	public function GetPossibleLocales()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$folder = new Folder(self::PATH_LOCALE);
		$rawlist = $folder->GetListing();

		$list = array();

		foreach ($rawlist as $item) {
			if (isset($this->dglocale[$item]))
				$list[] = $this->dglocale[$item];
		}

		sort($list);

		return $list;
	}

	/**
	 * Returns list of MIME types.
	 *
	 * @return array list of MIME types
	 */

	public function GetMimeTypes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetKeyedConfigurationByFilename(self::FILE_MIME_LIST_ALL);

		return $list;
	}

	/**
	 * Returns list of user-added MIME types.
	 *
	 * @return array list of user-added MIME types
	 */

	public function GetUserMimeTypes()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$list = array();
		$list = $this->GetKeyedConfigurationByFilename(self::FILE_MIME_LIST_USER);

		return $list;
	}

	/**
	 * Returns list of possible PICS levels.
	 *
	 * @return array list of PICS levels
	 */

	public function GetPossiblePics()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		return array('teen', 'youngadult', 'tooharsh', 'noblocking', 'disabled');
	}


	/**
	 * Returns list of possible weigthed phrase lists.
	 *
	 * @return array list of weighted phrase lists
	 */

	public function GetPossibleWeightedPhrase()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

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
					if (preg_match('/weighted/', $phrasefile)) {
						$isweighted = true;
						break;
					}
				}

				if ($isweighted) {
					$phraselistsinfo['name'] = $foldername;
					$descriptiontag = 'DANSGUARDIAN_LANG_PHRASELIST_' . strtoupper($foldername);

					if (defined("$descriptiontag"))
						$phraselistsinfo['description'] = constant($descriptiontag);
					else
						$phraselistsinfo['description'] = '...';

					$phraselistslist[] = $phraselistsinfo;
				}
			}
		}

		return $phraselistslist;
	}

	/**
	 * Returns the proxy IP (default: 127.0.0.1).
	 *
	 * @return string proxy IP
	 */

	public function GetProxyIp()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$ip = $this->GetConfigurationValue('proxyip');

		return $ip;
	}

	/**
	 * Returns the proxy port (default 3128).
	 *
	 * @return integer the current proxy port
	 */

	public function GetProxyPort()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$port = $this->GetConfigurationValue('proxyport');

		return $port;
	}

	/**
	 * Returns the reporting level.
	 *
	 * @param integer $group group number
	 * @return integer current reporting level
	 */

	public function GetReportingLevel($group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$level = $this->GetConfigurationValue('reportinglevel', $group);

		return $level;
	}

	/**
	 * Returns the weight phrase list.
	 *
	 * @param integer $group group ID
	 * @param boolean $details flag get full list including languagues
	 * @return array a list of the weight phrases
	 */

	public function GetWeightedPhraseLists($group = 1, $details = false)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$rawlines = $this->GetConfigurationDataByKey('weightedphraselist', false, $group);
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

	public function SetAccessDeniedUrl($url)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetConfigurationValue('accessdeniedaddress', $url);
	}

	/**
	 * Sets the list of autorization plugins.
	 *
	 * @param array $plugins list of authorization plugins
	 * @return void
	 */

	public function SetAuthorizationPlugins($plugins)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

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
		} catch (FileNoMatchException $e) {
		}

		try {
			if (empty($plugins)) {
				$file->PrependLines("/^authplugin\s+/", "#");
			} else {
				$file->ReplaceLines("/^#\s*authplugin\s+.*proxy-ntlm.conf/", "authplugin = '/etc/dansguardian-av/authplugins/proxy-ntlm.conf'\n");
				$file->ReplaceLines("/^#\s*authplugin\s+.*proxy-basic.conf/", "authplugin = '/etc/dansguardian-av/authplugins/proxy-basic.conf'\n");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Set the list of banned extensions.
	 *
	 * @param array $banlist list of banned extensions
	 * @param integer $group group number
	 * @return void
	 */

	public function SetBannedExtensions($banlist, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! is_array($banlist))
			$banlist = array($banlist);

		foreach ($banlist as $value) {
			if ($value && !$this->IsValidExtension($value))
				throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_EXTENSION_INVALID, COMMON_ERROR);
		}

		$this->SetConfigurationByKey('bannedextensionlist', $banlist, $group);
	}

	/**
	 * Set the list of banned MIMEs.
	 *
	 * @param array $banlist list of MIMEs
	 * @param integer $group group number
	 * @return void
	 */

	public function SetBannedMimes($banlist, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		// Validate
		//---------

		if (! is_array($banlist))
			$banlist = array($banlist);

		foreach ($banlist as $value) {
			if (! $this->IsValidMIME($value))
				throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_MIME_INVALID, COMMON_ERROR);
		}

		$this->SetConfigurationByKey('bannedmimetypelist', $banlist, $group);
	}

	/**
	 * Sets blacklist state.
	 *
	 * @param array $list list of enabled blacklists
	 * @param integer $group group number
	 * @return void
	 */

	public function SetBlacklists($list, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$domaindata = '';
		$urldata = '';

		foreach ($list as $item) {

			// Domains/sites
			//--------------

			$path = self::PATH_BLACKLISTS . "/$item/domains";
			$file = new File($path);

			if ($file->Exists())
				$domaindata .= ".Include<$path>\n";

			// URLs
			//-----

			$path = self::PATH_BLACKLISTS . "/$item/urls";

			$file = new File($path);

			if ($file->Exists())
				$urldata .= ".Include<$path>\n";
		}

		// Update config file - domains/sites
		//-----------------------------------

		$bannedsitepath = $this->GetFilenameByKey('bannedsitelist', $group);

		$file = new File($bannedsitepath);

		if (!$file->Exists()) {
			$file->Create('root', 'root', '0644');
		}

		$file->DeleteLines("/^\.Include.*/");
		$file->AddLines($domaindata);

		// Update config file - URLs
		//--------------------------

		$bannedurlpath = $this->GetFilenameByKey('bannedurllist', $group);

		$file = new File($bannedurlpath);

		if (!$file->Exists()) {
			$file->Create('root', 'root', '0644');
		}

		$file->DeleteLines("/^\.Include.*/");
		$file->AddLines($urldata);
	}

	/**
	 * Sets reverse DNS look-ups.
	 *
	 * @return void
	 */

	public function SetReverseLookups($enable)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetConfigurationValue('reverseaddresslookups', $enable);
	}

	/**
	 * Returns reverse DNS look-ups.
	 *
	 * @return string 'on' or 'off'
	 */

	public function GetReverseLookups()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		return $this->GetConfigurationValue('reverseaddresslookups', -1);
	}

	/**
	 * Sets locale.
	 *
	 * @return void
	 */

	public function SetLocale($locale)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidLocale($locale))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_LOCALE_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue('language', $this->dglocale[$locale]);
	}

	/**
	 * Sets group name.
	 *
	 * @return void
	 */

	public function SetGroupName($name, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validate
		//---------

		try {
			$groups = $this->GetFilterGroups();
			foreach ($groups as $id => $record) {
				if ($id != $group && $name == $record['groupname'])
					throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_GROUP_EXISTS, COMMON_ERROR);
			}
		} catch (Exception $e) {
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_GROUP_EXISTS, COMMON_ERROR);
		}

		$file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $group), true);

		try {
			if($file->ReplaceLines('/^groupname.*$/', "groupname = '$name'\n", 1) != 1)
				$file->AddLinesAfter("groupname = '$name'\n", '/^#groupname.*$/');
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Sets naughtyness level.
	 *
	 * @return void
	 */

	public function SetNaughtynessLimit($limit, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidNaughtynessLimit($limit))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_NAUGHTYNESS_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue('naughtynesslimit', $limit, $group);
	}

	/**
	 * Sets filter mode.
	 *
	 * @return void
	 */

	public function SetFilterMode($mode, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidFilterMode($mode))
			throw new EngineException(DANSGUARDIAN_LANG_FILTER_MODE . " - " . LOCALE_LANG_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue('groupmode', $mode, $group);
	}

	/**
	 * Sets bypass link.
	 *
	 * @return void
	 */

	public function SetBypassLink($bypass, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetConfigurationValue('bypass', $bypass, $group);
	}

	/**
	 * Sets content scan.
	 *
	 * @return void
	 */

	public function SetContentScan($scan, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetConfigurationValue('disablecontentscan', $scan, $group);
	}

	/**
	 * Sets deep URL analysis.
	 *
	 * @return void
	 */

	public function SetDeepUrlAnalysis($deepurl, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetConfigurationValue('deepurlanalysis', $deepurl, $group);
	}

	/**
	 * Sets download block.
	 *
	 * @return void
	 */

	public function SetDownloadBlock($block, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->SetConfigurationValue('blockdownloads', $block, $group);
	}

	/**
	 * Sets the PICS level
	 *
	 * @param   port    the PICS level
	 * @return void
	 */

	public function SetPics($pics, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidPICS($pics))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_PICS_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue('picsfile', self::BASE_PATH . "/pics.$pics", $group);
	}

	/**
	 * Sets the reporting level.
	 *
	 * @return void
	 */

	public function SetReportingLevel($level, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidReportingLevel($level))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_REPORTINGLEVEL_INVALID, COMMON_ERROR);

		$this->SetConfigurationValue('reportinglevel', $level, $group);
	}

	/**
	 * Sets the weight phrase list.
	 *
	 * @return void
	 */

	public function SetWeightedPhraseLists($weightphraselist, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->IsValidWeightPhraseList($weightphraselist))
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_WEIGHT_PHRASE_LIST_INVALID, COMMON_ERROR);

		$lines = array();

		foreach ($weightphraselist as $phraselist) {
			$subfolder = new Folder(self::BASE_PATH. "/lists/phraselists/$phraselist");
			$listnames = $subfolder->GetListing();

			foreach ($listnames as $listname) {
				if (preg_match('/weighted/', $listname))
					$lines[] = ".Include<" . self::BASE_PATH. "/lists/phraselists/$phraselist/$listname>";
			}
		}

		$this->SetConfigurationByKey('weightedphraselist', $lines, $group);
	}

	/*************************************************************************/
	/* G E N E R I C   M E T H O D S                                         */
	/*************************************************************************/

	/**
	 * Add group to file designated by the key in the configuration file.
	 *
	 * @access private
	 * @param string $key key in the configuration file
	 * @param string $groupname group name
	 * @return void
	 */

	private function AddGroupByKey($key, $groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
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
	 * @access private
	 * @param   key           key in the configuration file
	 * @param   items         array of items
	 * @return void
	 */

	private function AddItemsByKey($key, $items, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$filename = $this->GetConfigurationValue($key, $group);

		// Make sure entry does not already exist
		//---------------------------------------

		$contents = array();
		$existlist = array();
		$file = new File($filename);
		try {
			if (!$file->Exists())
				$file->Create('root', 'root', '0644');
			else
				$contents = $file->GetContents();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
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

		$file->AddLines($filedata);
	}

	/**
	 * Delete group from a list.
	 *
	 * @access private
	 * @param   key            key
	 * @param   groupname      groupname
	 * @return void
	 */

	private function DeleteGroupByKey($key, $groupname)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$cfgfile = $this->GetFilenameByKey($key);

		$group = new FileGroup($groupname, $cfgfile);

		$group->Delete();
	}

	/**
	 * Delete items to file designated by the key in the configuration file.
	 *
	 * @access private
	 * @param   key           key in the configuration file
	 * @param   items         array of items
	 * @return void
	 */

	private function DeleteItemsByKey($key, $items, $group)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$filename = $this->GetConfigurationValue($key, $group);

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
	 * @access private
	 * @param string $key configuration file key
	 * @param integer $group Filter group ID (default: 0)
	 * @return string key value
	 */

	private function GetConfigurationValue($key, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		// The configuration in version 2.8 was split.  This was done to
		// group configurations (good).  For now, we simply manage this
		// split here... feel free to redo this when group support is
		// added.

		if (in_array($key, $this->groupkeys) && $group > 0)
			$filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $group);
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

		$match = preg_replace("/'/", '', $match);

		return $match;
	}

	/**
	 * Generic fetch of file contents specified by a key value in dansguardian.conf.
	 *
	 * @access private
	 * @param   key    configuration file key
	 * @return array lines in target file
	 */

	private function GetConfigurationDataByKey($key, $isgroup, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$filename = $this->GetConfigurationValue($key, $group);

		$lines = array();
		$lines = $this->GetConfigurationByFilename($filename, $isgroup, $group);

		return $lines;
	}

	/**
	 * Generic fetch of file contents.
	 *
	 * @access private
	 * @param   filename    configuration file
	 * @return array lines in target file
	 */

	private function GetConfigurationByFilename($filename, $isgroup, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$cfgfile = new File($filename);
		$rawdata = $cfgfile->GetContents();

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
			if (in_array($value, $groupentries))
				$groupitems[] = $value;  // Just used for testing
			else $items[] = $value;
		}

		if ($isgroup) return $grouplist;
		else return $items;
	}

	/**
	 * Get list of possible file extensions.
	 *
	 * @access private
	 * @return array list of file extensions
	 */

	private function GetKeyedConfigurationByFilename($filename)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

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
	 * @access private
	 * @param string $key Configuration key
	 * @param string $value value for the configuration key
	 * @param integer $group Filter group ID (default: 1)
	 * @return void
	 */

	private function SetConfigurationValue($key, $value, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		if (in_array($key, $this->groupkeys) && $group > 0)
			$filename = sprintf(self::FILE_CONFIG_FILTER_GROUP, $group);
		else
			$filename = self::FILE_CONFIG;

		$file = new File($filename);

		$match = $file->ReplaceLines("/^#*\s*$key\s=/", "$key = $value\n");

		if (!$match)
			$file->AddLines("$key = $value\n");
	}

	/**
	 * Generic set for a configuration file by key in configuration file.
	 *
	 * @access private
	 * @param   key         configuration key
	 * @param   lines       array of lines in config file
	 * @return void
	 */

	private function SetConfigurationByKey($key, $lines, $group = 1)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		if (! is_array($lines)) $lines = array($lines);

		$filename = $this->GetConfigurationValue($key, $group);
		$filename = preg_replace("/'/", '', $filename);

		$file = new File($filename, true);
		if (!$file->Exists())
			$file->Create('root', 'root', '0644');
		else {
			$file->DeleteLines('/^[^#]+/');
			$file->DeleteLines('/^\s*$/');
		}

		$filedata = null;

		foreach ($lines as $line) $filedata .= "$line\n";

		$file->AddLines($filedata);
	}

	/**
	 * Split an array of URLs and sites into two arrays.
	 * A site includes an entire site (microsoft.com would block all
	 * pages from that domain).  A URL is a specific page
	 * (www.microsoft.com/privacy.html would block just the privacy page).
	 *
	 * @access private
	 * @param    sourcelist  list of urls and sites
	 * @param   sites       array of sites
	 * @param   urls        array of urls
	 * @return void
	 */

	private function SplitSitesAndUrls($sourcelist, &$sites, &$urls)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
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

	/**
	 * Add a new Filter Group
	 *
	 * @param string $name New Filter Group Name
	 * @return int $id New Filter Group Id
	 */

	final public function AddFilterGroup($name)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {
			$cfg = $this->GetFilterGroupConfiguration(0, $name);
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_FILTER_GROUP_EXISTS, COMMON_ERROR);
		} catch (FilterGroupNotFoundException $e) {
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$folder = new Folder(self::BASE_PATH);
		$files = array();

		try {
			$files = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		// Look for next available filter group Id
		//----------------------------------------

		$id = 2;
		$ids = array();
		foreach ($files as $file) {
			if (sscanf($file, 'dansguardianf%d', $id) != 1) continue;
			$ids[] = $id;
		}

		if (count($ids) == self::MAX_FILTER_GROUPS)
			throw new EngineException(DANSGUARDIAN_LANG_ERRMSG_MAX_FILTER_GROUPS, COMMON_ERROR);
		else if (count($ids)) {
			sort($ids);
			$id = end($ids) + 1;
		}

		// Create new filter group by copying the default
		//-----------------------------------------------

		$file = new File(self::BASE_PATH . '/dansguardianf1.conf', true);

		try {
			$file->CopyTo(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), true);

		try {
			if($file->ReplaceLines('/^groupname.*$/', "groupname = '$name'\n", 1) != 1)
				$file->AddLinesAfter("groupname = '$name'\n", '/^#groupname.*$/');
			// Default the group mode to "filtered"
			$file->ReplaceLines('/^groupmode\s*=.*/', "groupmode = 1\n", 1);

			foreach ($this->groupkeys as $key) {
				if (strstr($key, 'list') === false) continue;
				$value = str_replace(array('\'', '"'), '',
					$file->LookupValue("/^$key\s*=\s*/"));
				$file->ReplaceOneLineByPattern("/^$key\s*=.*$/",
					sprintf("%s = '%s%d'\n", $key, $value, $id));
				if (!in_array($key,
					array('bannedsitelist', 'bannedurllist', 'weightedphraselist',
					'exceptionfilesitelist'))) {
					$emptylist = new File(sprintf('%s%d', $value, $id));
					if (!$emptylist->Exists())
						$emptylist->Create('root', 'root', '0644');
					continue;
				}
				$default = new File($value);
				if ($default->Exists()) $default->CopyTo(sprintf('%s%d', $value, $id));
				else {
					$emptylist = new File(sprintf('%s%d', $value, $id));
					$emptylist->Create('root', 'root', '0644');
				}
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}


		// Resequence filter group Ids and set filter group count
		//-------------------------------------------------------

		$this->SequenceFilterGroups();

		return $id;
	}

	/**
	 * Add a new Filter Group
	 *
	 * @param string $name New Filter Group Name
	 * @return int $id New Filter Group Id
	 */

	final public function DeleteFilterGroup($id)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		try {
			$this->GetFilterGroupConfiguration($id);
			$file = new File(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), true);
			foreach ($this->groupkeys as $key) {
				if (strstr($key, 'list') === false) continue;
				$value = str_replace(array('\'', '"'), '',
					$file->LookupValue("/^$key\s*=\s*/"));
				$fglist = new File($value);
				if ($fglist->Exists()) $fglist->Delete();
			}
			$file->Delete();
			$file = new File(self::FILE_CONFIG, true);
			try {
				$fglist = str_replace(array('\'', '"'), '',
					$file->LookupValue('/^filtergroupslist\s*=\s*/'));
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
			$file = new File($fglist);
			try {
				$file->DeleteLines("/.*=filter$id.*/");
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
			$this->SequenceFilterGroups();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Return the configuration array for the specified Filter Group if found.
	 *
	 * @param int $id Filter Group Id
	 * @param string $name optional Filter Group Name
	 * @return array $cfg Filter Group configuration
	 */

	final public function GetFilterGroupConfiguration($id, $name = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$cfg = array();

		if ($id <= 0 && $name == null) {
				throw new FilterGroupNotFoundException(DANSGUARDIAN_LANG_ERRMSG_FILTER_GROUP_NOT_FOUND,
					COMMON_ERROR);
		} else if ($id >= 1) {
			$cfg = $this->GetConfigurationByFilename(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id), false);

			if($cfg == null) {
				throw new FilterGroupNotFoundException(DANSGUARDIAN_LANG_ERRMSG_FILTER_GROUP_NOT_FOUND,
					COMMON_ERROR);
			}
		} else {
			$folder = new Folder(self::BASE_PATH);

			try {
				$files = $folder->GetListing();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}

			$ids = array();
			foreach ($files as $file) {
				if (sscanf($file, 'dansguardianf%d.conf', $id) != 1) continue;
				$ids[] = $id;
			}

			$found = false;
			foreach ($ids as $id) {
				$cfg = $this->GetConfigurationByFilename(sprintf(self::FILE_CONFIG_FILTER_GROUP, $id),
					false);

				foreach ($cfg as $line) {
					if (!preg_match("/^groupname\s*=\s*'$name'.*/", $line)) continue;
					$found = true;
					break;
				}

				if($found) break;
			}

			if(!$found) {
				throw new FilterGroupNotFoundException(DANSGUARDIAN_LANG_ERRMSG_FILTER_GROUP_NOT_FOUND,
					COMMON_ERROR);
			}
		}

		$group = array();
		if ($id == 1) $group['groupname'] = DANSGUARDIAN_LANG_DEFAULT_GROUP;
		foreach ($cfg as $line) {
			list($key, $value) = explode('=', str_replace(array('\'', '"'), '', $line), 2);
			if ($id == 1 && $key == 'groupname') continue;
			$group[trim($key)] = trim($value);
		}

		ksort($group);
		return $group;
	}

	/**
	 * Return users of a given filter group.
	 *
	 * @param int $id Filter Group Id
	 * @return array $users Filter Group users
	 */

	final public function GetFilterGroupUsers($id)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$users = array();

		try {
			$file = new File(self::FILE_CONFIG, true);
			try {
				$fglist = str_replace(array('\'', '"'), '',
					$file->LookupValue('/^filtergroupslist\s*=\s*/'));
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
			$lines = array();
			$file = new File($fglist);
			try {
				$lines = $file->GetContentsAsArray();
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
			foreach ($lines as $line) {
				if (!preg_match("/^.*=filter$id.*$/", $line)) continue;
				# Ignore commented lines
				if (preg_match("/^[[:space:]]*#.*$/", $line)) continue;
				$users[] = preg_replace("/^(.*)=filter$id.*$/", '\1', $line);
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		return $users;
	}

	/**
	 * Add a user to a given filter group.
	 *
	 * @param int $id Filter Group Id
	 * @param string $user User to add
	 * @return void
	 */

	final public function AddFilterGroupUser($id, $user)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$groups = 0;

		try {
			$file = new File(self::FILE_CONFIG, true);
			$groups = $file->LookupValue('/^filtergroups\s*=\s*/');
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		try {
			for ($i = 0; $i < $groups; $i++) {
				$users = $this->GetFilterGroupUsers($i + 1);
				if (!in_array($user, $users)) continue;
				# Already exists in group?  If so, bail.
				if ($id == $i + 1)
					return;
				$cfg = $this->GetFilterGroupConfiguration($i + 1);
				throw new EngineException(DANSGUARDIAN_LANG_ERR_USER_EXISTS
					. ' - ' . $user . ' - ' . $cfg['groupname'] . " (#$id)", COMMON_ERROR);
			}
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
		$fglist = null;
		try {
			$file = new File(self::FILE_CONFIG, true);
			$fglist = str_replace(array('\'', '"'), '',
				$file->LookupValue('/^filtergroupslist\s*=\s*/'));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
		try {
			$file = new File($fglist);
			$file->AddLines("$user=filter$id\n");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Delete a user from a given filter group.
	 *
	 * @param int $id Filter Group Id
	 * @param string $user User to delete
	 * @return void
	 */

	final public function DeleteFilterGroupUser($id, $user)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$groups = 0;

		try {
			$file = new File(self::FILE_CONFIG, true);
			$groups = $file->LookupValue('/^filtergroups\s*=\s*/');
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$fglist = null;
		try {
			$file = new File(self::FILE_CONFIG, true);
			$fglist = str_replace(array('\'', '"'), '',
				$file->LookupValue('/^filtergroupslist\s*=\s*/'));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
		try {
			$file = new File($fglist);
			$file->DeleteLines("/^$user=filter$id.*$/");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Deletes all users from a given filter group.
	 *
	 * @param int $id Filter Group Id
	 * @return void
	 */

	final public function DeleteFilterGroupUsers($id)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$fglist = null;
		try {
			$file = new File(self::FILE_CONFIG, true);
			$fglist = str_replace(array('\'', '"'), '',
				$file->LookupValue('/^filtergroupslist\s*=\s*/'));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
		try {
			$file = new File($fglist);
			$file->DeleteLines("/^.*=filter$id.*$/");
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/**
	 * Return an array of all filter groups
	 *
	 * @return array $groups Array of filter groups.
	 */

	final public function GetFilterGroups()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$groups = array();
		$folder = new Folder(self::BASE_PATH);

		try {
			$files = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$ids = array();
		foreach ($files as $file) {
			if (sscanf($file, 'dansguardianf%d.conf', $id) != 1) continue;
			$ids[] = $id;
		}

		foreach ($ids as $id)
			$groups[$id] = $this->GetFilterGroupConfiguration($id);

		return $groups;
	}

	/**
	 * (Re)sequence filter group Ids.
	 * Called automatically after adding or deleting a filter group.
	 *
	 * @return void
	 */

	final private function SequenceFilterGroups()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);
		$folder = new Folder(self::BASE_PATH);

		try {
			$files = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}

		$ids = array();
		foreach ($files as $file) {
			if (sscanf($file, 'dansguardianf%d.conf', $id) != 1) continue;
			$ids[] = array('id' => $id, 'file' => $file);
		}

		if (!count($ids)) return;

		for ($i = 0; $i < count($ids); $i++) {
			if($ids[$i]['id'] == $i + 1) continue;
			$file = new File(self::FILE_CONFIG, true);
			try {
				$fglist = str_replace(array('\'', '"'), '',
					$file->LookupValue('/^filtergroupslist\s*=\s*/'));
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
			$file = new File($fglist);
			try {
				$file->ReplaceLinesByPattern(sprintf('/^(.*)=filter%d$/', $ids[$i]['id']),
					sprintf('$1=filter%d', $i + 1));
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
			$file = new File(self::BASE_PATH . '/' . $ids[$i]['file'], true);
			try {
				foreach ($this->groupkeys as $key) {
					if (strstr($key, 'list') === false) continue;
					$old = str_replace(array('\'', '"'), '', $file->LookupValue("/^$key\s*=\s*/"));
					$new = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
						'', $old) . ($i + 1);
					$file->ReplaceOneLineByPattern("/^$key\s*=.*$/", "$key = '$new'\n");
					$fglist = new File($old);
					if ($fglist->Exists()) $fglist->MoveTo($new);
				}
				$file->MoveTo(self::BASE_PATH . sprintf('/dansguardianf%d.conf', $i + 1));
			} catch (Exception $e) {
				throw new EngineException($e->getMessage(), COMMON_ERROR);
			}
		}

		$file = new File(self::FILE_CONFIG, true);

		try {
			$file->ReplaceLines('/^filtergroups\s*=.*$/',
				sprintf("filtergroups = %d\n", count($ids)));
		} catch (Exception $e) {
			throw new EngineException($e->getMessage(), COMMON_ERROR);
		}
	}

	/*************************************************************************/
	/* V A L I D A T I O N   R O U T I N E S                                 */
	/*************************************************************************/

	/**
	 * Validation routine for naughtyness limit.
	 *
	 * @param integer $limit naughtyness level
	 * @return boolean true if naughtyness level is valid
	 */

	function IsValidNaughtynessLimit($limit)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_numeric($limit))
			return true;

		return false;
	}

	/**
	 * Validation routine for filter group mode.
	 *
	 * @param integer $mode filter group mode
	 * @return boolean true if filter group mode is valid
	 */

	function IsValidFilterMode($mode)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_numeric($mode))
			return true;

		return false;
	}

	/**
	 * Validation routine for reporting level.
	 *
	 * @param integer $level reporting level
	 * @return boolean true if reporting level is valid
	 */

	function IsValidReportingLevel($level)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_numeric($level) && ($level<=3) && ($level >= -1))
			return true;

		return false;
	}

	/**
	 * Validation routine for filter port number.
	 *
	 * @param integer $port filter port number
	 * @return boolean true if filter port number is valid
	 */

	function IsValidFilterPort($port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^\d+$/", $port))
			return true;

		return false;
	}

	/**
	 * Validation routine for proxy IP.
	 *
	 * @param string $ip IP address
	 * @return boolean true if IP address is valid
	 */

	function IsValidProxyIp($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([0-9\.\-]*)$/", $ip))
			return true;

		return false;
	}

	/**
	 * Validation routine for proxy port.
	 *
	 * @param integer $port proxy port number
	 * @return boolean true if proxy port number is valid
	 */

	function IsValidProxyPort($port)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^\d+$/", $port))
			return true;

		return false;
	}

	/**
	 * Validation routine for PICS.
	 *
	 * @param string $pics PICS value
	 * @return boolean if PICS value is valid
	 */

	function IsValidPICS($pics)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^[". implode("|", $this->GetPossiblePics()) ."]/", $pics))
			return true;

		return false;
	}

	/**
	 * Validation routine for weighted phrase lists.
	 *
	 * @param array $phraselists an array of phrase lists
	 * @return boolean true if phrase lists are valid
	 */

	function IsValidWeightPhraseList($phraselists)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

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
	 * Validation routine for web sites.
	 *
	 * @param string $site web site
	 * @return boolean true if web site is valid
	 */

	function IsValidSite($site)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^[\w\_\.\-]+$|^\*ip$|^\*ips$|^\*\*$|^\*\*s$/", $site))
			return true;

		return false;
	}

	/**
	 * Validation routine for URLs.
	 *
	 * @param string $url URL
	 * @return boolean true if URL is valid
	 */

	function IsValidUrl($url)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([\w\._\-\/]+)$/", $url))
			return true;

		return false;
	}

	/**
	 * Validation routine for file extensions.
	 *
	 * @param string $extension file extension
	 * @return boolean true if file extension is valid
	 */

	function IsValidExtension($extension)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([\w\-\.]+)$/", $extension))
			return true;

		return false;
	}

	/**
	 * Validation routine for MIME.
	 *
	 * @param string $mime MIME type
	 * @return boolean true if MIME type is valid
	 */

	function IsValidMIME($extension)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^([\w\/\-]*)$/", $extension))
			return true;

		return false;
	}

	/**
	 * Validation routine for IPs.
	 *
	 * @param string $ip IP address
	 * @return boolean true if IP address is valid
	 */

	function IsValidIpFormat($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// This is for a 'DansGuardian' IP (asterisks and other funny chars are allowed)
		if (preg_match("/^([0-9\.\-]*)$/", $ip))
			return true;

		return false;
	}

	/**
	 * Validation routine for locale.
	 *
	 * @param string $locale locale
	 * @return boolean true if locale is valid
	 */

	function IsValidLocale($locale)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (in_array($locale, $this->dglocales))
			return true;
		else
			return false;
	}

	/**
	 * @access private
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
