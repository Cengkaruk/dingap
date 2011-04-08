<?php

/**
 * PPTP VPN server class.
 *
 * @category   Apps
 * @package    PPTPd
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pptpd/
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

namespace clearos\apps\pptpd;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('pptpd');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
// clearos_load_library('network/Iface');
// clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

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
 * PPTP VPN server class.
 *
 * @category   Apps
 * @package    PPTPd
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/pptpd/
 */

class PPTPd extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/pptpd.conf';
    const FILE_OPTIONS = '/etc/ppp/options.pptpd';
    const FILE_STATS = '/proc/net/dev';
    const CONSTANT_PPPNAME = 'pptp-vpn';
    const DEFAULT_KEY_SIZE = 128;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Pptp constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('pptpd');
    }

    /**
     * Returns list of active interfaces.
     *
     * @return array list of active PPTP connections
     * @throws Engine_Exception
     */

    public function get_active_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ethlist = array();
        $ethinfolist = array();

        $ifs = new Iface_Manager();
        $ethlist = $ifs->get_interfaces(FALSE, TRUE);

        foreach ($ethlist as $eth) {
            if (! preg_match('/^pptp[0-9]/', $eth))
                continue;

            $ifdetails = array();

            $if = new Iface($eth);

            // TODO: YAPH - yet another PPPoE hack
            if ($if->is_configured())
                    continue;

            $address = $if->get_live_ip();
            $remote = $if->get_live_ip();

            $ifinfo = array();
            $ifinfo['name'] = $eth;
            $ifinfo['address'] = $address;

            $ethinfolist[] = $ifinfo;
        }

        return $ethinfolist;
    }

    /**
     * Returns the DNS server.
     *
     * @return string DNS server
     * @throws Engine_Exception
     */

    public function get_dns_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_options_parameter('ms-dns');
    }

    /**
     * Returns the domain.
     *
     * @return string domain
     * @throws Engine_Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_options_parameter('domain');
    }

    /**
     * Returns interface statistics.
     *
     * @return array interface statistics
     * @throws Engine_Exception
     */

    public function get_interface_statistics()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: move this to the Iface class
        $stats = array();

        $file = new File(self::FILE_STATS);
        $lines = $file->get_contents_as_array();

        $matches = array();

        foreach ($lines as $line) {
            if (preg_match('/^\s*([^:]*):(.*)/', $line, $matches)) {
                $items = preg_split('/\s+/', $matches[2]);
                $stats[$matches[1]]['received'] = $items[1];
                $stats[$matches[1]]['sent'] = $items[9];
            }
        }

        return $stats;
    }

    /**
     * Returns the local IP settings.
     *
     * @return string local IP
     * @throws Engine_Exception
     */

    public function get_local_ip()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_config_parameter('localip');
    }

    /**
     * Returns remote IP settings.
     *
     * @return string remote IP
     * @throws Engine_Exception
     */

    public function get_remote_ip()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_config_parameter('remoteip');
    }

    /**
     * Returns the  WINS server.
     *
     * @return string WINS server
     * @throws Engine_Exception
     */

    public function get_wins_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_options_parameter('ms-wins');
    }

    /**
     * Sets the DNS server.
     *
     * @param string $server DNS server
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_dns_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_dns_server($server));

        $this->_set_options_parameter('ms-dns', $server);
    }

    /**
     * Sets the domain.
     *
     * @param string $domain domain
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain($domain));

        $this->_set_options_parameter('domain', $domain);
    }

    /**
     * Sets local IP.
     *
     * @param string $ip local IP
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_local_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip_range($ip));

        $this->_set_config_parameter('localip', $ip);
    }

    /**
     * Sets remote IP.
     *
     * @param string $ip remote IP
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_remote_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip_range($ip));

        $this->_set_config_parameter('remoteip', $ip);
    }


    /**
     * Sets the WINS server.
     *
     * @param string $server WINS server
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_wins_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_wins_server($server));

        $this->_set_options_parameter('ms-wins', $server);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for localip/remoteip.
     *
     * @param string $ip PPTP IP address format
     *
     * @return string error message if IP format is invalid
     */

    public function validate_ip_range($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: improve interface
       //    return lang('pptpd_ip_range_is_invalid');
    }

    /**
     * Validation routine for WINS server.
     *
     * @param string $server WINS server
     *
     * @return string error message if WINS server is invalid
     */

    public function validate_wins_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($server !== '') && (! Network_Utils::is_valid_ip($server)))
            return lang('pptpd_wins_server_is_invalid');
    }

    /**
     * Validation routine for DNS server.
     *
     * @param string $server DNS server
     *
     * @return string error message if DNS server is invalid
     */

    public function validate_dns_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($server !== '') && (! Network_Utils::is_valid_ip($server)))
            return lang('pptpd_dns_server_is_invalid');
    }

    /**
     * Validation routine for Internet domain.
     *
     * @param string $domain Internet domain name
     *
     * @return string error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($domain !== '') && (! Network_Utils::is_valid_domain($domain)))
            return lang('pptpd_internet_domain_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns parameter from ppp options file.
     *
     * @param string $parameter parameter in options file
     *
     * @access private
     * @return void
     */

    protected function _get_options_parameter($parameter)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = '';

        try {
            $file = new File(self::FILE_OPTIONS);
            $value = $file->lookup_value("/^$parameter\s+/i");
        } catch (File_No_Match_Exception $e) {
            return;
        } catch (Exception $e) {
            throw new Engine_Exception($e->get_message());
        }

        return $value;
    }

    /**
     * Returns parameter from PPTP configuration file.
     *
     * @param string $parameter parameter in options file
     *
     * @access private
     * @return void
     */

    protected function _get_config_parameter($parameter)
    {
        clearos_profile(__METHOD__, __LINE__);

        $value = '';

        try {
            $file = new File(self::FILE_CONFIG);
            $value = $file->lookup_value("/^$parameter\s+/i");
        } catch (File_No_Match_Exception $e) {
            return;
        } catch (Exception $e) {
            throw new Engine_Exception($e->get_message());
        }

        return $value;
    }

    /**
     * Sets parameter in ppp options file.
     *
     * @param string $parameter parameter in options file
     * @param string $value     value for given parameter
     *
     * @access private
     * @return void
     */

    protected function _set_options_parameter($parameter, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_OPTIONS);

        if (empty($value)) {
            $file->delete_lines("/^$parameter\s*/i");
        } else {
            $match = $file->replace_lines("/^$parameter\s*/i", "$parameter $value\n");

            if (!$match)
                $file->add_lines_after("$parameter $value\n", "/^[^#]/");
        }
    }

    /**
     * Sets parameter in PPTP configuration file.
     *
     * @param string $parameter parameter in options file
     * @param string $value     value for given parameter
     *
     * @access private
     * @return void
     */

    protected function _set_config_parameter($parameter, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^$parameter\s*/i", "$parameter $value\n");

        if (!$match)
            $file->add_lines_after("$parameter $value\n", "/^[^#]/");
    }
}
