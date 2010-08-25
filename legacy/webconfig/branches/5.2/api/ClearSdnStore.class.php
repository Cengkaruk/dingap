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
 * ClearSDN billing class.
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
require_once('ClearSdnSoapRequest.class.php');
require_once('ClearSdnShoppingCart.class.php');
require_once('ClearSdnCartItem.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearSDN store class.
 *
 * Provides information on available ClearSDN services provided by ClearCenter
 *
 * @package Api
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnStore extends ClearSdnSoapRequest
{
	protected $cache = array();
	const CREDIT_CARD = 1;
	const PURCHASE_ORDER = 2;
	const JWS_SDN_STORE = "wsSdnStore.jws";
	const PATH_STORE = '/var/lib/suva/store/';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ClearSDN Store constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns an array of billing information for ClearSDN account associated with this system.
	 *
	 * @param boolean $realtime set realtime to true to fetch real-time data
	 * @param string $password account password (required for first time only)
	 * @return array  information
	 * @throws EngineException, ClearSdnAuthenticationException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearClearSdnSoapRequestRemoteException
	 */

	public function GetBillingInfo($realtime = false, $password = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$cachekey = 'sdn.' . __CLASS__ . '-' . __FUNCTION__ . '.cache'; 

			if (!$realtime && $this->_CheckCache($cachekey))
				return $this->cache;

			$client = $this->Request(ClearSdnStore::JWS_SDN_STORE);

			$result = $client->getBillingInfo($this->sysinfo, $password);
	
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
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName))
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			else if (ereg(".*AuthenticationFault", $e->detail->exceptionName))
				throw new ClearSdnAuthenticationException($e->GetMessage());
			else
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns an array of information related around the purchase.
	 *
	 * @param string $password  password of the account in ClearSDN
	 * @param string $method  the purchase method (currently supported credit card = 1, PO = 2)
	 * @param string $pid  the product to purchase
	 * @param string $po   a purchase order (or set to null for credit card purchase)
	 * @return array  information
	 * @throws EngineException, ClearSdnAuthenticationException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearClearSdnSoapRequestRemoteException
	 */

	public function DoPurchase($password, $method, $pid, $po = null)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {

			$client = $this->Request(ClearSdnStore::JWS_SDN_STORE);

			if ($method == 1)
				$result = $client->addServiceByCreditCard($this->sysinfo, $password, $pid);
			else
				$result = $client->addServiceByPurchaseOrder($this->sysinfo, $password, $pid, $po);
	
			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only
	
			return $result;
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName))
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			else if (ereg(".*AuthenticationFault", $e->detail->exceptionName))
				throw new ClearSdnAuthenticationException($e->GetMessage());
			else
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns information to navigate to online checkout.
	 *
	 * @param string $password  password of the account in ClearSDN
	 * @return array  information
	 * @throws EngineException, ClearSdnAuthenticationException, ClearSdnFailedToConnectException, ClearSdnDeviceNotRegisteredException, ClearClearSdnSoapRequestRemoteException
	 */

	public function UploadCart($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {

			$cart = new ClearSdnShoppingCart();
			$items = $cart->GetItems();
			if (empty($items))
				throw new EngineException(CLEARSDN_STORE_LANG_NO_ITEMS_IN_CART, COMMON_WARNING);
			$pidlist = Array();
			foreach ($items as $item)
				$pidlist[] = array('pid' => $item->GetPid(), 'class' => $item->GetClass(), 'group' => $item->GetGroup());

			$client = $this->Request(ClearSdnStore::JWS_SDN_STORE);

			$result = $client->uploadCart($this->sysinfo, $password, $pidlist);
	
			foreach ($client->_cookies as $cookiename => $value)
				$_SESSION['clearsdn_cookie'][$cookiename] = $value[0]; // Take first element only
	
			return $result;
		} catch (SoapFault $e) {
			// TODO - Change on ClearSDN will cause IllegalArgumentException to be thrown...need to reset JSESSIONID
			if (ereg(".*java.lang.IllegalArgumentException.*", $e->faultstring)) {
				unset($_SESSION['clearsdn_cookie']);
				throw new ClearSdnFailedToConnectException($e->GetMessage());
			}
			if (ereg(".*DeviceNotRegisteredFault", $e->detail->exceptionName))
				throw new ClearSdnDeviceNotRegisteredException($e->GetMessage());
			else if (ereg(".*AuthenticationFault", $e->detail->exceptionName))
				throw new ClearSdnAuthenticationException($e->GetMessage());
			else
				throw new ClearSdnSoapRequestRemoteException($e->GetMessage());
		} catch (ClearSdnFailedToConnectException $e) {
			throw new ClearSdnFailedToConnectException($e->GetMessage());
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
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
		$folder = new Folder(self::PATH_STORE);
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

		$folder = new Folder(self::PATH_STORE);
		if (!$folder->Exists())
			return;

		$listing = $folder->GetListing(true);
		foreach ($listing as $element) {
			if (!preg_match("/^sdn-.*\.cache$/", $element['name']))
				continue;
			if ($filename != null && $filename != $element['name'])
				continue;
			$file = new File(self::PATH_STORE . $element['name']);
			$file->Delete();
		}
	}

	/**
	 * @access private
	 */

	protected function _SaveToCache($sig, $result)
	{

		try {
			// We take the md5 hash of the class name and function for unique cache filename.
			$file = new File(self::PATH_STORE . md5($sig), true);

			$folder = new Folder(self::PATH_STORE);
			if (!$folder->Exists())
				$folder->Create('webconfig', 'webconfig', 755);

			if ($file->Exists())
				$file->Delete();

			$file->Create('webconfig', 'webconfig', 600);
			$file->AddLines(serialize($result));
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * @access private
	 */

	protected function _CheckCache($sig)
	{

		try {
			// We take the md5 hash of the class name and function for unique cache filename.
			$file = new File(self::PATH_STORE  . md5($sig), true);
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

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
