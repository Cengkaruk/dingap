<?php

/**
 * Firewall port forwarding class.
 *
 * @category   Apps
 * @package    Port_Forwarding
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/port_forwarding/
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

namespace clearos\apps\port_forwarding;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('port_forwarding');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\firewall\Rule as Rule;

clearos_load_library('firewall/Firewall');
clearos_load_library('firewall/Rule');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall port forwarding class.
 *
 * @category   Apps
 * @package    Port_Forwarding
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/port_forwarding/
 */

class Port_Forwarding extends Firewall
{
    /**
     * Port_Forwarding constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Adds a port/to the forward allow list.
     *
     * @param string  $name      name
     * @param string  $protocol  protocol
     * @param integer $from_port from port number
     * @param integer $to_port   to port number
     * @param string  $to_ip     to address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_port($name, $protocol, $from_port, $to_port, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($from_port));
        Validation_Exception::is_valid($this->validate_port($to_port));
        Validation_Exception::is_valid($this->validate_address($to_ip));

        $rule = new Rule();

        if (strlen($name))
            $rule->set_name($name);

        $rule->set_parameter($from_port);
        $rule->set_port($to_port);
        $rule->set_protocol($rule->convert_protocol_name($protocol));
        $rule->set_address($to_ip);
        $rule->set_flags(Rule::FORWARD | Rule::ENABLED);

        $this->add_rule($rule);
    }

    /**
     * Adds a port range to the forward allow list.
     *
     * @param string  $name      name
     * @param string  $protocol  protocol
     * @param integer $low_port  low port number
     * @param integer $high_port high port number
     * @param string  $to_ip     to address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_port_range($name, $protocol, $low_port, $high_port, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($low_port));
        Validation_Exception::is_valid($this->validate_port($high_port));
        Validation_Exception::is_valid($this->validate_address($to_ip));

        $rule = new Rule();

        if (strlen($name))
            $rule->set_name($name);

        $rule->set_parameter("$low_port:$high_port");
        $rule->set_protocol($rule->convert_protocol_name($protocol));
        $rule->set_address($to_ip);
        $rule->set_flags(Rule::FORWARD | Rule::ENABLED);

        $this->add_rule($rule);
    }

    /**
     * Adds a standard service to the forward allow list.
     *
     * @param string $name    name
     * @param string $service service
     * @param string $to_ip   to address
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_standard_service($name, $service, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_service($service));
        Validation_Exception::is_valid($this->validate_address($to_ip));

        if ($service == 'IPsec') {
            throw new Engine_Exception(lang('firewall_feature_is_not_supported'));
        } else if ($service == 'PPTP') {
            throw new Engine_Exception(lang('firewall_feature_is_not_supported'));
        }

        $ports = $this->get_ports_list();

        foreach ($ports as $port_info) {
            if ($port_info[3] == $service) {
                if (preg_match("/:/", $port_info[2])) {
                    $ports = explode(":", $port_info[2]);
                    $this->add_port_range($name, $port_info[1], $ports[0], $ports[1], $to_ip);
                } else {
                    $this->add_port($name, $port_info[1], $port_info[2], $port_info[2], $to_ip);
                }
            }
        }
    }

    /**
     * Delete a port from the forward allow list.
     *
     * @param string  $protocol  protocol
     * @param integer $from_port from port
     * @param integer $to_port   to port
     * @param string  $to_ip     to address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_port($protocol, $from_port, $to_port, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        $rule->set_protocol($rule->convert_protocol_name($protocol));
        $rule->set_address($to_ip);
        $rule->set_port($to_port);
        $rule->set_parameter($from_port);
        $rule->set_flags(Rule::FORWARD);

        $this->delete_rule($rule);
    }

    /**
     * Delete a port range from the forward range allow list.
     *
     * @param string  $protocol  protocol
     * @param integer $low_port  low port number
     * @param integer $high_port high port number
     * @param string  $to_ip     to address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_port_range($protocol, $low_port, $high_port, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        $rule->set_protocol($rule->convert_protocol_name($protocol));
        $rule->set_address($to_ip);
        $rule->set_parameter("$low_port:$high_port");
        $rule->set_flags(Rule::FORWARD);

        $this->delete_rule($rule);
    }

    /**
     * Returns allowed forward ports.
     *
     * The information is an array with the following hash array entries:
     *
     *  info[enabled]
     *  info[protocol]
     *  info[name]
     *  info[from_port]
     *  info[to_ip]
     *  info[to_port]
     *  info[service] (FTP, HTTP, etc.)
     *
     * @return array array containing allowed forward ports
     * @throws Engine_Exception
     */

    public function get_ports()
    {
        clearos_profile(__METHOD__, __LINE__);

        $portlist = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (strstr($rule->get_parameter(), ":"))
                continue;

            if (!($rule->get_flags() & Rule::FORWARD))
                continue;

            if ($rule->get_flags() & (Rule::WIFI | Rule::CUSTOM))
                continue;

            $portinfo = array();

            switch ($rule->get_protocol()) {
                case Rule::PROTO_TCP:
                    $portinfo['protocol'] = "TCP";
                    break;

                case Rule::PROTO_UDP:
                    $portinfo['protocol'] = "UDP";
                    break;
            }

            $portinfo['name'] = $rule->get_name();
            $portinfo['enabled'] = $rule->is_enabled();
            $portinfo['to_ip'] = $rule->get_address();
            $portinfo['to_port'] = $rule->get_port();
            $portinfo['from_port'] = $rule->get_parameter();
            $portinfo['service'] = $this->lookup_service($portinfo['protocol'], $portinfo['to_port']);
            $portlist[] = $portinfo;
        }

        return $portlist;
    }

    /**
     * Returns forward port ranges.
     *
     * The information is an array with the following hash array entries:
     *
     *  info[enabled]
     *  info[protocol]
     *  info[name]
     *  info[to_ip]
     *  info[low_port]
     *  info[high_port]
     *
     * @return array array containing allowed forward ports
     */

    public function get_port_ranges()
    {
        clearos_profile(__METHOD__, __LINE__);

        $portlist = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!strstr($rule->get_parameter(), ":"))
                continue;

            if (!($rule->get_flags() & Rule::FORWARD))
                continue;

            if ($rule->get_flags() & (Rule::WIFI | Rule::CUSTOM))
                continue;

            $portinfo = array();

            switch ($rule->get_protocol()) {

                case Rule::PROTO_TCP:
                    $portinfo['protocol'] = "TCP";
                    break;

                case Rule::PROTO_UDP:
                    $portinfo['protocol'] = "UDP";
                    break;
            }

            $portinfo['name'] = $rule->get_name();
            $portinfo['enabled'] = $rule->is_enabled();
            $portinfo['to_ip'] = $rule->get_address();
            $portinfo['service'] = "";
            list($portinfo['low_port'], $portinfo['high_port']) = preg_split('/:/', $rule->get_parameter());

            $portlist[] = $portinfo;
        }

        return $portlist;
    }

    /**
     * Returns IP of PPTP server behind the firewall.
     *
     * @return array array containing the IP of a PPTP server
     * @throws Engine_Exception
     */

    public function get_pptp_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        $rules = $this->get_rules();

        $info = array();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::PPTP_FORWARD))
                continue;

            $info['host'] = $rule->get_address();
            $info['enabled'] = $rule->is_enabled();
            break; // Can only have one of these...
        }

        return $info;
    }

    /**
     * Enable/disable a port from the forward allow list.
     *
     * @param string  $state     state
     * @param string  $protocol  protocol
     * @param integer $from_port from port number
     * @param integer $to_port   to port number
     * @param string  $to_ip     to address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_port_state($state, $protocol, $from_port, $to_port, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($from_port));
        Validation_Exception::is_valid($this->validate_port($to_port));
        Validation_Exception::is_valid($this->validate_address($to_ip));

        $rule = new Rule();

        $rule->set_protocol($rule->convert_protocol_name($protocol));
        $rule->set_address($to_ip);
        $rule->set_port($to_port);
        $rule->set_parameter($from_port);
        $rule->set_flags(Rule::FORWARD);

        if (!($rule = $this->find_rule($rule)))
            return;

        $this->delete_rule($rule);

        if ($state)
            $rule->enable();
        else
            $rule->disable();

        $this->add_rule($rule);
    }

    /**
     * Enable/disable a port range from the forward range allow list.
     *
     * @param string  $state     state
     * @param string  $protocol  protocol
     * @param integer $low_port  low port number
     * @param integer $high_port high port number
     * @param string  $to_ip     to address
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_port_range_state($state, $protocol, $low_port, $high_port, $to_ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($low_port));
        Validation_Exception::is_valid($this->validate_port($high_port));
        Validation_Exception::is_valid($this->validate_address($to_ip));

        $rule = new Rule();

        $rule->set_protocol($rule->convert_protocol_name($protocol));
        $rule->set_address($to_ip);
        $rule->set_parameter("$low_port:$high_port");
        $rule->set_flags(Rule::FORWARD);

        if (!($rule = $this->find_rule($rule)))
            return;

        $this->delete_rule($rule);

        if ($state)
            $rule->enable();
        else
            $rule->disable();

        $this->add_rule($rule);
    }

    /**
     * Sets PPTP forwarding to the given IP address.
     *
     * @param string $ip IP of PPTP server
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_pptp_server($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        $rule->set_port(1723);
        $rule->set_protocol(Rule::PROTO_GRE);
        $rule->set_flags(Rule::PPTP_FORWARD | Rule::ENABLED);

        $hostinfo = $this->get_pptp_server();

        $oldip = $hostinfo['host'];

        if (strlen($oldip)) {
            $rule->set_address($oldip);
            $this->delete_rule($rule);
        }

        if (strlen($ip)) {
            $rule->set_address($ip);
            $this->add_rule($rule);
        }
    }

    /**
     * Sets PPTP forwarding state.
     *
     * @param boolean $state state
     * @param string  $ip    IP of PPTP server
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_pptp_server_state($state, $ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_address($ip));

        $rule = new Rule();

        $rule->set_port(1723);
        $rule->set_protocol(Rule::PROTO_GRE);
        $rule->set_flags(Rule::PPTP_FORWARD);
        $rule->set_address($ip);

        if (!($rule = $this->find_rule($rule)))
            return;

        $this->delete_rule($rule);

        if ($state)
            $rule->enable();
        else
            $rule->disable();

        $this->add_rule($rule);
    }
}
