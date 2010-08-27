<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearCenter
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
//
// Eventually, this will get merged with the WebServices class. For now, this
// class will handle its own request to the Service Delivery Network.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearSDN Service class.
 *
 * @package Api
 * @subpackage WebServices
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('Folder.class.php');
require_once('ClearSdnSoapRequest.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearSDN service class.
 *
 * Provides information on available ClearSDN services provided by ClearCenter
 *
 * @package Api
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnService extends ClearSdnSoapRequest
{
	protected $servicelist = array();
	protected $cache = array();
	protected $log_ok = Array('min' => 100000,'max' => 299999);
	protected $log_warning = Array('min' => 300000,'max' => 399999);
	protected $log_fatal = Array('min' => 400000,'max' => 599999);
	// Do not change the constants below...they map with ClearSDN portal
	const JWS_SDN_SERVICE = "wsSdnService.jws";
	const SDN_BASE = "base";
	const SDN_SUPPORT = "support";
	const SDN_INTRUSION = "intrusion_detect";
	const SDN_BACKUP = "remote_backup";
	const SDN_MONITOR = "remote_monitor";
	const SDN_FILTER = "content_filter";
	const SDN_BANDWIDTH = "remote_bandwidth";
	const SDN_AV = "antimalware";
	const SDN_AS = "antispam";
	const SDN_AUDIT = "remote_audit";
	const SDN_DYNDNS = "dynamic_dns";
	const SDN_IPSEC_VPN = "ipsec_vpn";
	const TERM_MONTHLY = 1;
	const TERM_ANNUAL = 2;
	const TERM_1_YEAR_FIXED = 1000;
	const TERM_2_YEAR_FIXED = 2000;
	const TERM_3_YEAR_FIXED = 3000;
	const FILE_SPAM_STATS = "/var/webconfig/tmp/sdn-mailsummary.out";
	const CMD_DF = "/bin/df";
	const PATH_SERVICE = '/var/lib/suva/services/';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ClearSDN Service constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns statistics on cache files for this web service.
	 *
	 * @return array  information
	 */

	public function GetCacheStats()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$stats = Array();
		$folder = new Folder(self::PATH_SERVICE);
		if (!$folder->Exists())
			$folder->Create("root", "root", "755");
		$listing = $folder->GetListing(true);
		foreach ($listing as $element) {
			if (preg_match("/^sdn-.*\.cache$/", $element['name']))
				$stats[] = $element;
		}
		
		return $stats;
	}

	/**
	 * Deletes a cache file.
	 *
	 * @param string  $filename  a filename or set to null for all cache files
	 * @return array  information
	 */

	public function DeleteCache($filename = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$folder = new Folder(self::PATH_SERVICE);
		if (!$folder->Exists())
			return;

		$listing = $folder->GetListing(true);
		foreach ($listing as $element) {
			if (!preg_match("/^sdn-.*\.cache$/", $element['name']))
				continue;
			if ($filename != null && $filename != $element['name'])
				continue;
			$file = new File(self::PATH_SERVICE . $element['name']);
			$file->Delete();
		}
	}

	/**
	 * Returns an array of ClearSDN subscription information.
	 *
	 * @param boolean $realtime set realtime to true to fetch real-time data
	 * @return array  information
	 * @throws EngineException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearSdnSoapRequestRemoteException
	 */

	public function GetAllServiceInfo($realtime = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cachekey = __CLASS__ . '-' . __FUNCTION__; 

			if (!$realtime && $this->_CheckCache($cachekey))
				return $this->cache;
	
			$client = $this->Request(ClearSdnService::JWS_SDN_SERVICE);
			$this->_GetSupportStatus();
			$this->_GetIdsStatus();
			$this->_GetRbsStatus();
			$this->_GetContentFilterStatus();
			$this->_GetAntimalwareStatus();
			$this->_GetAntispamStatus();
			$this->_GetSecurityAuditStatus();
			$this->_GetRemoteMonitorStatus();
			$this->_GetRemoteBandwidthMonitorStatus();

			$result = $client->getServiceInfo($this->sysinfo, $this->servicelist);

			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only

			usort($result, Array("ClearSdnService", "_GetCompare"));

			$this->_SaveToCache($cachekey, $result);
		
			return $result;
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName)) {
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			} else {
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
			}
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage());
		}
	}

	/**
	 * Returns an array of information on the base subscription.
	 *
	 * @param boolean $realtime set realtime to true to fetch real-time data
	 * @return array  information
	 * @throws EngineException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearSdnSoapRequestRemoteException
	 */

	public function GetBaseSubscription($realtime = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cachekey = __CLASS__ . '-' . __FUNCTION__; 

			if (!$realtime && $this->_CheckCache($cachekey))
				return $this->cache;

			$client = $this->Request(ClearSdnService::JWS_SDN_SERVICE);

			$result = $client->getBaseSubscription($this->sysinfo);

			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only

			$this->_SaveToCache($cachekey, $result);
		
			return $result;
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName)) {
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			} else {
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
			}
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage());
		}
	}

	/**
	 * Returns an array of ClearSDN subscription information.
	 *
	 * @param boolean $realtime set realtime to true to fetch real-time data
	 * @param string $service the service to get information on
	 * @return array  information
	 * @throws EngineException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearSdnSoapRequestRemoteException
	 */

	public function GetServiceDetails($realtime = false, $service)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cachekey = __CLASS__ . '-' . __FUNCTION__ . '-' . $service; 

			if (!$realtime && $this->_CheckCache($cachekey))
				return $this->cache;

			// Make sure we don't have any prior service data
			unset($servicelist);

			$client = $this->Request(ClearSdnService::JWS_SDN_SERVICE);

			if ($service == self::SDN_BASE)
				$this->_GetBaseStatus();
			if ($service == self::SDN_SUPPORT)
				$this->_GetSupportStatus();
			if ($service == self::SDN_INTRUSION)
				$this->_GetIdsStatus();
			if ($service == self::SDN_BACKUP)
				$this->_GetRbsStatus();
			if ($service == self::SDN_FILTER)
				$this->_GetContentFilterStatus();
			if ($service == self::SDN_AV)
				$this->_GetAntimalwareStatus();
			if ($service == self::SDN_AS)
				$this->_GetAntispamStatus();
			if ($service == self::SDN_AUDIT)
				$this->_GetSecurityAuditStatus();
			if ($service == self::SDN_MONITOR)
				$this->_GetRemoteMonitorStatus();
			if ($service == self::SDN_BANDWIDTH)
				$this->_GetRemoteBandwidthMonitorStatus();

			$result = $client->getServiceDetails($this->sysinfo, $this->servicelist);

			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only

			$this->_SaveToCache($cachekey, $result);
		
			return $result;
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName)) {
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			} else {
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
			}
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage());
		}
	}

	/**
	 * Returns an array of ClearSDN dynamic DNS information.
	 *
	 * @return array  information
	 * @throws EngineException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearSdnSoapRequestRemoteException
	 */

	public function GetDynamicDnsSettings()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cachekey = __CLASS__ . '-' . __FUNCTION__; 

			$client = $this->Request(ClearSdnService::JWS_SDN_SERVICE);

			$result = $client->getDynamicDnsSettings($this->sysinfo);

			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only

			return $result;
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName)) {
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			} else {
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
			}
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage());
		}
	}

	/**
	 * Update dynamic DNS settings to the ClearSDN.
	 *
	 * @throws EngineException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearSdnSoapRequestRemoteException
	 */

	public function SetDynamicDnsSettings($enabled, $subdomain, $domain, $ip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cachekey = __CLASS__ . '-' . __FUNCTION__; 

			$client = $this->Request(ClearSdnService::JWS_SDN_SERVICE);

			$result = $client->setDynamicDnsSettings($this->sysinfo, $enabled, $subdomain, $domain, $ip);

			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName)) {
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			} else {
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
			}
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage());
		}
	}

	/**
	 * Returns an appropriate icon corresponding with a ClearSDN log code.
	 *
	 * @param int $code  the log code
	 * @return String  the approriate icon
	 */

	public function GetLogIcon($code)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			// TODO Pete's not going to want this here...
			if ($this->log_ok['min'] < $code && $code < $this->log_ok['max'])
				return "<img src='/templates/base/images/icons/16x16/icon-clearsdn-ok.png' style='padding: 0 1 0 1'>";
			else if ($this->log_warning['min'] < $code && $code < $this->log_warning['max'])
				return "<img src='/templates/base/images/icons/16x16/icon-clearsdn-info.png' style='padding: 0 1 0 1'>";
			else
				return "<img src='/templates/base/images/icons/16x16/icon-clearsdn-warning.png' style='padding: 0 1 0 1'>";

		} catch (Exception $e) {
			return "<img src='/templates/base/images/icons/16x16/icon-clearsdn-warning.png' style='padding: 0 1 0 1'>";
		}
	}

	/**
	 * @access private
	 */

	protected function _GetBaseStatus()
	{
		$this->servicelist[ClearSdnService::SDN_BASE] = array('state' => 1);
	}

	/**
	 * @access private
	 */

	protected function _GetSupportStatus()
	{
		$users = 0;
		$services = 0;
		if (file_exists(COMMON_CORE_DIR . "/api/UserManager.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/UserManager.class.php");
			try {
				$usermanager = new UserManager();
				$users = count($usermanager->GetAllUsers());
			} catch (Exception $ignore) {
			}
		}

		// Count services that could be improved with ClearCenter's ClearSDN services
		if (file_exists(COMMON_CORE_DIR . "/api/ClamAv.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/IpSec.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Snort.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Postfix.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Amavis.class.php"))
			$services++;

		$this->servicelist[ClearSdnService::SDN_SUPPORT] = array('users'=> $users, 'services'=> $services);
	}

	/**
	 * @access private
	 */

	protected function _GetIdsStatus()
	{
		if (!file_exists(COMMON_CORE_DIR . "/api/Snort.class.php"))
			return;
		require_once(COMMON_CORE_DIR . "/api/Snort.class.php");
		$service = new Snort();
		$this->servicelist[ClearSdnService::SDN_INTRUSION] = array('state'=> ($service->GetRunningState() ? 1 : 0));
	}

	/**
	 * @access private
	 */

	protected function _GetContentFilterStatus()
	{
		if (!file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php"))
			return;
		require_once(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php");
		$service = new DansGuardian();
		$this->servicelist[ClearSdnService::SDN_FILTER] = array('state'=> ($service->GetRunningState() ? 1 : 0));
	}

	/**
	 * @access private
	 */

	protected function _GetAntispamStatus()
	{
		if (!file_exists(COMMON_CORE_DIR . "/api/Postfix.class.php"))
			return;
		require_once(COMMON_CORE_DIR . "/api/Postfix.class.php");
		$service = new Postfix();
		$this->servicelist[ClearSdnService::SDN_AS] = array('state'=> ($service->GetRunningState() ? 1 : 0));
		try {
			$file = new File(ClearSdnService::FILE_SPAM_STATS);
			if ($file->Exists()) {
				$contents = $file->GetContents();
				$values = $rawdata = split("\|", $contents);
				$this->servicelist[ClearSdnService::SDN_AS]['ham'] = (int)$values[0];
				$this->servicelist[ClearSdnService::SDN_AS]['spam'] = (int)$values[1];
				$this->servicelist[ClearSdnService::SDN_AS]['sdnspam'] = (int)$values[2];
			}
		} catch (Exception $e) {
			// Fail quietly
		}
	}

	/**
	 * @access private
	 */

	protected function _GetAntimalwareStatus()
	{
		$avdependents = 0;
		// Content filter = 1
		// Mail antivirus = 2
		// Samba file server = 4
		if (file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php");
			$service = new DansGuardian();
			if ($service->GetRunningState())
				$avdependents += 1;
		}
		if (file_exists(COMMON_CORE_DIR . "/api/Postfix.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Postfix.class.php");
			$service = new Postfix();
			if ($service->GetRunningState())
				$avdependents += 2;
		}
		if (file_exists(COMMON_CORE_DIR . "/api/Samba.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Samba.class.php");
			$service = new Samba();
			if ($service->GetRunningState())
				$avdependents += 4;
		}
		$this->servicelist[ClearSdnService::SDN_AV] = array('state' => $avdependents);
	}

	/**
	 * @access private
	 */

	protected function _GetRbsStatus()
	{
		$total = $this->_GetDiskUsage();

		if (!file_exists(COMMON_CORE_DIR . "/api/RemoteBackup.class.php"))
			return;
		require_once(COMMON_CORE_DIR . "/api/RemoteBackup.class.php");
		$rbs = new RemoteBackup();
		$state = 0;
		if ($rbs->IsBackupScheduleEnabled() != null)
			$state = 1;
		$this->servicelist[ClearSdnService::SDN_BACKUP] = array('state' => $state, 'size' => $total);
	}

	/**
	 * @access private
	 */

	protected function _GetRemoteMonitorStatus()
	{
		$services = 0;
		// Count services that could be monitored with ClearCenter's ClearSDN services
		if (file_exists(COMMON_CORE_DIR . "/api/Httpd.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/IpSec.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Snort.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Proftpd.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Postfix.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/DansGuardianAv.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Amavis.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Pptpd.class.php"))
			$services++;
		if (file_exists(COMMON_CORE_DIR . "/api/Mysql.class.php"))
			$services++;

		$this->servicelist[ClearSdnService::SDN_MONITOR] = array('services' => $services);
	}

	/**
	 * @access private
	 */

	protected function _GetRemoteBandwidthMonitorStatus()
	{
		$this->servicelist[ClearSdnService::SDN_BANDWIDTH] = array('state' => 1);
	}

	/**
	 * @access private
	 */

	protected function _GetSecurityAuditStatus()
	{
		$total = $this->_GetDiskUsage();

		$this->servicelist[ClearSdnService::SDN_AUDIT] = array('size' => $total);
	}

	/**
	 * Returns total usage of all mounted disks.
	 *
	 * @returns    total useage in MB
	 */

	protected function _GetDiskUsage()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$total = (float)-1;

		$shell = new ShellExec();
		$retval = $shell->Execute(self::CMD_DF, $args);

		if ($retval != 0) {
			// Not to concerned about errors...just bail
			return $total;
		} else {
			$lines = $shell->GetOutput();
			foreach ($lines as $line) {
				if (preg_match("/^(.*)\s+([\d]+)\s+([\d]+)\s+([\d]+)\s+.*$/", $line)) {
					$parts = preg_split("/\s+/", $line);
					$total += ((int)$parts[2])/1024;  // Return MB
				}
			}
		}

		return (float)$total;
	}

	/**
	 * @access private
	 */

	protected function _SaveToCache($sig, $result)
	{

		try {
			$folder = new Folder(self::PATH_SERVICE);
			if (!$folder->Exists())
				$folder->Create('webconfig', 'webconfig', 755);

			// We take the md5 hash of the class name and function for unique cache filename.
			$file = new File(self::PATH_SERVICE . "sdn-" . md5($sig) . ".cache", true);

			if ($file->Exists())
				$file->Delete();

			$file->Create('webconfig', 'webconfig', 600);
			$file->AddLines(serialize($result));
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage());
		}
	}

	/**
	 * @access private
	 */

	protected function _CheckCache($sig)
	{

		try {
			// We take the md5 hash of the class name and function for unique cache filename.
			$file = new File(self::PATH_SERVICE . "sdn-" . md5($sig) . ".cache", true);
			$fileexists = false;

			try {
				$fileexists = $file->Exists();
			} catch (Exception $e) {
				return false;
			}

			if (!$fileexists)
				return false;

			// See if file is older than 24 hours
			$lastmod = $file->LastModified();
			$old = strtotime('24 hours ago');
			if ($lastmod < $old)
				return false;

			$contents = $file->GetContents();
			$this->cache = unserialize($contents);
			return true;

		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @access private
	 */

	private static function _GetCompare($ent1, $ent2)
	{
		if ($ent1['priority'] > $ent2['priority'])
			return 1;
		else if ($ent1['priority'] < $ent2['priority'])
			return -1;
		else
			return 0;
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
