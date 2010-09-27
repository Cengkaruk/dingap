<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * Console server.
 *
 * @package Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("Engine.class.php");
require_once("Locale.class.php");
require_once("Stats.class.php");
require_once('Chap.class.php');
require_once('Firewall.class.php');
//require_once('IfaceManager.class.php');
require_once('Iface.class.php');
require_once("Routes.class.php");
require_once("Syswatch.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Console locale wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Console_GetLocaleStringsResponse
{
	public $tag;
	public $string;
}

/**
 * Console system stats wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Console_GetSystemStatsResponse
{
	public $uptime;
	public $load;
	public $timestamp;
}

/**
 * Console network interface wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Console_GetInterfaceInfoResponse
{
	public $type;
	public $boot_proto;
	public $address;
	public $link;
	public $speed;
}

/**
 * Console network interface configuration wrapper.
 *
 * @package Soap
 * @subpackage Soap
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Console_InterfaceConfig
{
	public $device;
	public $role;
	public $type;
	public $boot_proto;
	public $address;
	public $netmask;
	public $gateway;
	public $mtu;
	public $dhcp_hostname;
	public $peer_dns;
	public $pppoe_username;
	public $pppoe_password;
	public $wifi_mode;
	public $wifi_rate;
	public $wifi_essid;
	public $wifi_secret_key;
}

$_SOAP['CLASS_MAP']['GetLocaleStringsResponse'] = 'Console_GetLocaleStringsResponse';
$_SOAP['CLASS_MAP']['GetSystemStatsResponse'] = 'Console_GetSystemStatsResponse';
$_SOAP['CLASS_MAP']['LoadInterfaceResponse'] = 'Console_InterfaceConfig';
$_SOAP['CLASS_MAP']['SaveInterfaceRequest'] = 'Console_InterfaceConfig';
$_SOAP['CLASS_MAP']['GetLoadAveragesResponse'] = 'Stats_GetLoadAveragesResponse';
$_SOAP['CLASS_MAP']['GetUptimeResponse'] = 'Stats_GetUptimeResponse';
$_SOAP['CLASS_MAP']['GetInterfaceInfoResponse'] = 'Stats_GetInterfaceInfoResponse';

/**
 * Console utility class.
 *
 * The Console class is used by graphical CC Console.
 *
 * @package Soap
 * @author Point Clark Networks
 * @license GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class Console extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	const REGX_TAG = '^define\("(%s)"[[:space:]]*,[[:space:]]"(.*)"\);$';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Console constructor.
	 */

	function __construct() 
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
	}

	/**
	 * Returns array containing the time, load averages, and uptime.
	 *
	 * @return array result
	 */

	final function GetSystemStats()
	{
		$stats = new Stats();

		$result = new Console_GetSystemStatsResponse();

		$result->load = new Stats_GetLoadAveragesResponse();

		$load = $stats->GetLoadAverages();
		$result->load->one = $load["one"];
		$result->load->five = $load["five"];
		$result->load->fifteen = $load["fifteen"];

		$result->uptime = new Stats_GetUptimeResponse();

		$uptime = $stats->GetUptime();
		$result->uptime->uptime = $uptime["uptime"];
		$result->uptime->idle = $uptime["idle"];

		$result->timestamp = time();

		return $result;
	}

	/**
	 * Returns array of locale strings for ccConsole.
	 *
	 * @return array strings
	 */

	final public function GetLocaleStrings()
	{
		$strings = array();

		// An array of locale files and corresponding tags we need.
		// Use the "*" wildcard to import all tags found in a locale file.
		$import = array(
			"console" => array("*"),
			"iface" => array("IFACE_LANG_BIT_RATE", "IFACE_LANG_BOOTPROTO", "IFACE_LANG_DHCP", "IFACE_LANG_ESSID", "IFACE_LANG_ETHERNET", "IFACE_LANG_INTERFACE", "IFACE_LANG_KEY", "IFACE_LANG_LINK", "IFACE_LANG_MODE", "IFACE_LANG_PPPOE", "IFACE_LANG_SPEED", "IFACE_LANG_STATIC", "IFACE_LANG_TYPE", "IFACE_LANG_UNKNOWN", "IFACE_LANG_WIRELESS", "IFACE_UPDATE_TICK", "IFACE_WIZARD_BEGIN", "IFACE_WIZARD_TITLE", "IFACE_LANG_PEERDNS"),
			"locale" => array("LOCALE_LANG_CANCEL", "LOCALE_LANG_CONFIGURE", "LOCALE_LANG_LOADING", "LOCALE_LANG_SAVE", "LOCALE_LANG_CONFIRM", "LOCALE_LANG_CONTINUE", "LOCALE_LANG_DISABLE", "LOCALE_LANG_DISABLED", "LOCALE_LANG_EDIT", "LOCALE_LANG_ENABLE", "LOCALE_LANG_ENABLED", "LOCALE_LANG_HELP", "LOCALE_LANG_PASSWORD", "LOCALE_LANG_PLEASE_WAIT", "LOCALE_LANG_REBOOT", "LOCALE_LANG_RESET", "LOCALE_LANG_SHUTDOWN", "LOCALE_LANG_STATUS", "LOCALE_LANG_UPDATE", "LOCALE_LANG_USERNAME"),
			"network" => array("NETWORK_LANG_GATEWAY", "NETWORK_LANG_HOSTNAME", "NETWORK_LANG_IP", "NETWORK_LANG_NETMASK", "NETWORK_LANG_NETWORK", "WEB_LANG_PAGE_INTRO", "WEB_LANG_PAGE_TITLE", "NETWORK_LANG_MTU"),
			"firewall" => array("FIREWALL_LANG_DMZ", "FIREWALL_LANG_EXTERNAL", "FIREWALL_LANG_LAN", "FIREWALL_LANG_HOT_LAN", "FIREWALL_LANG_MODE", "FIREWALL_LANG_MODE_GATEWAY", "FIREWALL_LANG_MODE_STANDALONE", "FIREWALL_LANG_MODE_TRUSTEDSTANDALONE", "FIREWALL_LANG_ROLE"),
			"hostname" => array("HOSTNAME_CHANGE", "HOSTNAME_LANG_HOSTNAME"),
			"resolver" => array("RESOLVER_LANG_NAMESERVER"),
			"dnsmasq" => array("DNSMASQ_LANG_DOMAIN", "WEB_LANG_PAGE_INTRO", "WEB_LANG_PAGE_TITLE"),
			"webconfig" => array("WEBCONFIG_LANG_LOGIN", "WEBCONFIG_LANG_LOGIN_HELP"),
			"system" => array("SYSTEM_LANG_RESTART", "SYSTEM_LANG_SHUTDOWN")
		);

		$locale = new Locale();
		$code = $locale->GetLanguageCode();
		//$code = "de_DE";
		//$code = "fr_FR";

		$path = pathinfo(__FILE__);
		$corename = explode(".", $path["basename"]);

		foreach($import as $base => $tags) {
			$file = $path["dirname"] . "/lang/$base.$code";

			if(!($fh = @fopen($file, "r"))) continue;
			$this->LoadStrings($fh, $tags, $strings);
			@fclose($fh);

			if(!count($tags)) continue;

			$file = $path["dirname"] . "/../htdocs/admin/lang/$base.$code";

			if(!($fh = @fopen($file, "r"))) continue;
			$this->LoadStrings($fh, $tags, $strings, strtoupper($base) . "_");
			@fclose($fh);
		}

		return $strings;
	}

	final private function LoadStrings($fh, &$tags, &$strings, $prefix = "")
	{
		while(!feof($fh)) {
			$line = chop(fgets($fh, 4096));

			foreach($tags as $key => $tag) {
				$match = ($tag == "*") ? ".*" : $tag;
				$parts = array();
				if(!ereg(sprintf(Console::REGX_TAG, $match), $line, $parts)) continue;

				$element = new Console_GetLocaleStringsResponse();
				$element->tag = $prefix . $parts[1];
				$element->string = strip_tags(html_entity_decode($parts[2], ENT_NOQUOTES, 'UTF-8'));
				$strings[] = $element;

				if($tag != "*") unset($tags[$key]);
			}
		}
	}

	final public function LoadInterface($device)
	{
		$result = new Console_InterfaceConfig();

		$iface = new Iface($device);
		if(!$iface->IsConfigured()) return $result;

		$details = $iface->GetInterfaceInfo();
		$config = $iface->ReadConfig();
		$chap = new Chap();
		$users = $chap->GetUsers();

//		$firewall = new Firewall();

//		$result->role;
//		$result->type;
//		$result->$boot_proto;

		$result->address = $details['address'];
		$result->netmask = $details['netmask'];
		if (isset($config['gateway']))
			$result->gateway = $config['gateway'];
		else
			$result->gateway = "";
		$result->mtu = $details['mtu'];
		if (isset($config['dhcp_hostname']))
			$result->dhcp_hostname = $config['dhcp_hostname'];
		$result->peer_dns = false;
		if (isset($config['peerdns']) && strtolower($config['peerdns']) == 'yes')
				$result->peer_dns = true;
		$result->pppoe_username = "";
		if (isset($config['user'])) {
			$result->pppoe_username = $config['user'];
			if (isset($users[$config['user']]))
				$result->pppoe_password = $users[$config['user']]['password'];
		}
		if (isset($config['mode']))
			$result->wifi_mode = $config['mode'];
		if (isset($config['rate']))
			$result->wifi_rate = $config['rate'];
		if (isset($config['essid']))
			$result->wifi_essid = $config['essid'];
		if (isset($config['key']))
			$result->wifi_secret_key = $config['key'];

		return $result;
	}

	final public function SaveInterface($config)
	{
		$firewall = new Firewall();
		$interface = new Iface($config->device);

		// Wireless
		//---------

		if ($config->type == Iface::TYPE_WIRELESS) {

			if ($config->boot_proto == Iface::BOOTPROTO_DHCP) {
				$interface->SaveWirelessConfig(true, "", "", "", $config->wifi_essid, "1", $config->wifi_mode, $config->wifi_secret_key, $config->wifi_rate, $config->peer_dns);
			} else {
				$interface->SaveWirelessConfig(false, $config->address, $config->netmask, $config->gateway, $config->wifi_essid, "1", $config->wifi_mode, $config->wifi_secret_key, $config->wifi_rate, $config->peer_dns, $config->mtu);
			}

		// PPPoE
		//------

		} else if ($config->type == Iface::TYPE_PPPOE) {
			$firewall->RemoveInterfaceRole($config->device);
			$config->device = $interface->SavePppoeConfig($config->device, $config->pppoe_username, $config->pppoe_password, $config->mtu, $config->peer_dns);

		// Ethernet
		//---------

		} else if ($config->type == Iface::TYPE_ETHERNET) {
			if ($config->boot_proto == Iface::BOOTPROTO_DHCP) {
				$interface->SaveEthernetConfig(true, "", "", "", $config->dhcp_hostname, $config->peer_dns);
			} else {
				$interface->SaveEthernetConfig(false, $config->address, $config->netmask, $config->gateway, "", false);
			}
		}

		// Enable interface and rebuild the network
		//-----------------------------------------

//		try {
			$interface->Enable();
//		} catch (Exception $e) {
//      }

		$routes = new Routes();

		if ($config->role != $firewall->GetInterfaceRole($config->device))
			$firewall->SetInterfaceRole($config->device, $config->role);

		if ($config->role == Firewall::CONSTANT_EXTERNAL)
			$routes->SetGatewayDevice($config->device);
		else if ($routes->GetGatewayDevice() == $config->device)
			$routes->DeleteGatewayDevice();

		$firewall->Restart();

		$syswatch = new Syswatch();
		$syswatch->ReconfigureNetworkSettings();
	}

	final public function AuthCheck()
	{
		return true;
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
