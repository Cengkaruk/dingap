<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
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
 * OpenVPN server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('File.class.php');
require_once('Firewall.class.php');
require_once('Hostname.class.php');
require_once('Network.class.php');

/**
 * OpenVPN server.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2008, Point Clark Networks
 */

class OpenVpn extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $is_loaded = false;
	protected $config = array();

	const FILE_CLIENTS_CONFIG = '/etc/openvpn/clients.conf';
	const DEFAULT_PORT = 1194;
	const DEFAULT_PROTOCOL = "udp";
	const CONSTANT_PROTOCOL_UDP = "udp";
	const CONSTANT_PROTOCOL_TCP = "tcp";
	const TYPE_OS_WINDOWS = "Windows";
	const TYPE_OS_LINUX = "Linux";
	const TYPE_OS_MACOS = "MacOS";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * OpenVPN constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('openvpn');

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns configuration file for requested client type.
	 *
	 * @param string $type client type (eg Windows)
	 * @param string $fileid unique identifier used in hostname (eg username)
	 * @return void
	 * @throws EngineException
	 */

	public function GetClientConfiguration($type, $fileid)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$host = $this->GetServerHostname();
		$port = $this->GetClientPort();
		$protocol = $this->GetClientProtocol();

		if ($type == self::TYPE_OS_WINDOWS) {
			$config = "client
remote $host $port
dev tun
proto $protocol
resolv-retry infinite
nobind
persist-key
persist-tun
ca ca-cert.pem
cert client-" . $fileid . "-cert.pem
key client-" . $fileid . "-key.pem
ns-cert-type server
comp-lzo
verb 3
auth-user-pass
";
		} else if (($type == self::TYPE_OS_LINUX) || ($type == self::TYPE_OS_MACOS)) {
			$config = "client
remote $host $port
dev tun
proto $protocol
resolv-retry infinite
nobind
user nobody
group nobody
persist-key
persist-tun
ca ca-cert.pem
cert client-" . $fileid . "-cert.pem
key client-" . $fileid . "-key.pem
ns-cert-type server
comp-lzo
verb 3
auth-user-pass
";
		} else {
			throw new EngineException(OPENVPN_LANG_CONFIGURATION_FILE . " - " . LOCALE_LANG_INVALID, COMMON_WARNING);
		}

		return $config;
	}

	/**
	 * Returns port number for desktop client server.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function GetClientPort()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (empty($this->config['port']))
			return OpenVpn::DEFAULT_PORT;
		else
			return $this->config['port'];
	}

	/**
	 * Returns protocol for desktop client server.
	 *
	 * @return void
	 * @throws EngineException
	 */

	public function GetClientProtocol()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (empty($this->config['proto']))
			return OpenVpn::DEFAULT_PROTOCOL;
		else
			return $this->config['proto'];
	}

	/**
	 * Returns DNS server pushed out to clients.
	 *
	 * @return string DNS server IP address
	 * @throws EngineException
	 */

	public function GetDnsServer()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (empty($this->config['push']['dhcp-option']['DNS']))
			return "";
		else
			return $this->config['push']['dhcp-option']['DNS'];
	}

	/**
	 * Returns domain name pushed out to clients.
	 *
	 * @return string domain name
	 * @throws EngineException
	 */

	public function GetDomain()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (empty($this->config['push']['dhcp-option']['DOMAIN']))
			return "";
		else
			return $this->config['push']['dhcp-option']['DOMAIN'];
	}

	/**
	 * Returns the hostname to use to connect to this server.
	 *
	 * @return string OpenVPN server hostname
	 * @throws EngineException
	 */

	public function GetServerHostname()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Use the defined Internet hostname (if configured)
		//--------------------------------------------------

		if (file_exists(COMMON_CORE_DIR . "/api/Organization.class.php")) {
			require_once(COMMON_CORE_DIR . "/api/Organization.class.php");

			$organization = new Organization();
			$myhost = $organization->GetInternetHostname();
		}

		if (empty($myhost)) {
			$hostname = new Hostname();
			$myhost = $hostname->Get();
        }

		return $myhost;
	}

	/**
	 * Returns WINS server pushed out to clients.
	 *
	 * @return string WINS server IP address
	 * @throws EngineException
	 */

	public function GetWinsServer()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		if (empty($this->config['push']['dhcp-option']['WINS']))
			return "";
		else
			return $this->config['push']['dhcp-option']['WINS'];
	}

	/**
	 * Sets DNS server pushed out to clients.
	 *
	 * @param string $ip DNS server IP
	 * @return void
	 * @throws EngineException
	 */

	public function SetDnsServer($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidIp($ip)) {
            $this->AddValidationError(NETWORK_LANG_DNS_SERVER . " - " . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            return;
        }

		$this->_SetDhcpParameter('DNS', $ip);
	}

	/**
	 * Sets domain name pushed out to clients.
	 *
	 * @param string $domain domain name
	 * @return void
	 * @throws EngineException
	 */

	public function SetDomain($domain)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidDomain($domain)) {
            $this->AddValidationError(NETWORK_LANG_DOMAIN . " - " . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            return;
        }

		$this->_SetDhcpParameter('DOMAIN', $domain);
	}

	/**
	 * Sets WINS server pushed out to clients.
	 *
	 * @param string $ip WINS server IP
	 * @return void
	 * @throws EngineException
	 */

	public function SetWinsServer($ip)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$network = new Network();

		if (! $network->IsValidIp($ip)) {
            $this->AddValidationError(NETWORK_LANG_WINS_SERVER . " - " . LOCALE_LANG_INVALID, __METHOD__, __LINE__);
            return;
        }

		$this->_SetDhcpParameter('WINS', $ip);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S 
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration files.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new File(self::FILE_CLIENTS_CONFIG);

		try {
			$lines = $configfile->GetContentsAsArray();
			$matches = array();

			foreach ($lines as $line) {
				if (preg_match('/^push\s+"route\s+([^\s+]*)\s+([^"]*)"\s*$/', $line, $matches)) {
					$this->config['push']['route'][$matches[1] . "/" . $matches[2]] = true;
				} else if (preg_match('/^push\s+"dhcp-option\s+([^\s+]*)\s+([^"]*)"\s*$/', $line, $matches)) {
					$this->config['push']['dhcp-option'][$matches[1]] = $matches[2];
				} else if (preg_match('/^push\s+"redirect-gateway"\s*$/', $line, $matches)) {
					$this->config['push']['redirect-gateway'] = true;
				} else if (preg_match('/^push\s+"(.*)"\s*$/', $line, $matches)) {
					// Ignore other push parameters for now
				} else if (preg_match('/^([a-zA-Z][^\s]*)\s+(.*)$/', $line, $matches)) {
					$this->config[$matches[1]] = $matches[2];
				}
			}

			$this->is_loaded = true;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets a parameter in the config file.
	 *
	 * @access private
	 * @param string $key name of the key in the config file
	 * @param string $value value for the key
	 * @return void
	 * @throws EngineException
	 */

	function _SetDhcpParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->is_loaded = false;

		try {
			$file = new File(self::FILE_CLIENTS_CONFIG);
			$match = $file->ReplaceLines("/^push\s+\"dhcp-option\s+$key\s+/", "push \"dhcp-option $key $value\"\n");
			if (!$match)
				$file->AddLines("push \"dhcp-option $key $value\"\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
