<?php

/**
 * OpenVPN server.
 *
 * @category   Apps
 * @package    OpenVPN
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2008-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openvpn/
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

namespace clearos\apps\openvpn;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('openvpn');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('firewall/Firewall');
clearos_load_library('network/Hostname');
clearos_load_library('network/Network');

/**
 * OpenVPN server.
 *
 * @category   Apps
 * @package    OpenVPN
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2008-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openvpn/
 */

class OpenVPN extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CLIENTS_CONFIG = '/etc/openvpn/clients.conf';
    const DEFAULT_PORT = 1194;
    const DEFAULT_PROTOCOL = 'udp';
    const CONSTANT_PROTOCOL_UDP = 'udp';
    const CONSTANT_PROTOCOL_TCP = 'tcp';
    const TYPE_OS_WINDOWS = 'Windows';
    const TYPE_OS_LINUX = 'Linux';
    const TYPE_OS_MACOS = 'MacOS';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenVPN constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('openvpn');
    }

    /**
     * Returns configuration file for requested client type.
     *
     * @param string $type    client type (eg Windows)
     * @param string $file_id unique identifier used in hostname (eg username)
     *
     * @return void
     * @throws Engine_Exception
     */

    public function get_client_configuration($type, $file_id)
    {
        clearos_profile(__METHOD__, __LINE__);

        $host = $this->get_server_hostname();
        $port = $this->get_client_port();
        $protocol = $this->get_client_protocol();

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
cert client-" . $file_id . "-cert.pem
key client-" . $file_id . "-key.pem
ns-cert-type server
comp-lzo
verb 3
auth-user-pass
";
        } else {
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
cert client-" . $file_id . "-cert.pem
key client-" . $file_id . "-key.pem
ns-cert-type server
comp-lzo
verb 3
auth-user-pass
";

        return $config;
    }

    /**
     * Returns port number for desktop client server.
     *
     * @return integer port number
     * @throws Engine_Exception
     */

    public function get_client_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (empty($this->config['port']))
            return OpenVpn::DEFAULT_PORT;
        else
            return $this->config['port'];
    }

    /**
     * Returns protocol for desktop client server.
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function get_client_protocol()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (empty($this->config['proto']))
            return OpenVpn::DEFAULT_PROTOCOL;
        else
            return $this->config['proto'];
    }

    /**
     * Returns DNS server pushed out to clients.
     *
     *
     * @return string DNS server IP address
     * @throws Engine_Exception
     */

    public function get_dns_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (empty($this->config['push']['dhcp-option']['DNS']))
            return "";
        else
            return $this->config['push']['dhcp-option']['DNS'];
    }

    /**
     * Returns domain name pushed out to clients.
     *
     *
     * @return string domain name
     * @throws Engine_Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (empty($this->config['push']['dhcp-option']['DOMAIN']))
            return "";
        else
            return $this->config['push']['dhcp-option']['DOMAIN'];
    }

    /**
     * Returns the hostname to use to connect to this server.
     *
     *
     * @return string OpenVPN server hostname
     * @throws Engine_Exception
     */

    public function get_server_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

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
     *
     * @return string WINS server IP address
     * @throws Engine_Exception
     */

    public function get_wins_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (empty($this->config['push']['dhcp-option']['WINS']))
            return "";
        else
            return $this->config['push']['dhcp-option']['WINS'];
    }

    /**
     * Sets DNS server pushed out to clients.
     *
     * @param string $ip DNS server IP
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_dns_server($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

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
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

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
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_wins_server($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

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
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new File(self::FILE_CLIENTS_CONFIG);

        if (! in_array($time_zone, $list))
            return lang('date_time_zone_is_invalid');
    }
}
