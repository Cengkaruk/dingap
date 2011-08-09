<?php

/**
 * 1-to-1 NAT firewall class.
 *
 * @category   Apps
 * @package    One_To_One_NAT
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/nat_firewall/
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

namespace clearos\apps\nat_firewall;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('nat_firewall');

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
 * 1-to-1 NAT firewall class.
 *
 * @category   Apps
 * @package    One_To_One_NAT
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/nat_firewall/
 */

class One_To_One_NAT extends Firewall
{
    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * One_To_One_NAT constructor.
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();
    }

    /**
     * Adds a 1:1 NAT rule.
     *
     * @param string $name      optional rule nickname
     * @param string $wan_ip    WAN IP address
     * @param string $lan_ip    LAN IP address
     * @param string $interface external interface name 
     *
     * @return void
     * @throws Engine_Exception
     */
    
    public function add($name, $wan_ip, $lan_ip, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_interface($interface));
 
        $rule = new Rule();

        $rule->set_name($name);
        $rule->set_address($wan_ip);
        $rule->set_flags(Rule::ONE_TO_ONE | Rule::ENABLED);
        $rule->set_parameter($interface . '_' . $lan_ip);

        $this->add_rule($rule);
    }

    /**
     * Adds a port only 1:1 NAT rule.
     *
     * @param string  $name      optional rule nickname
     * @param string  $wan_ip   WAN IP address
     * @param string  $lan_ip     LAN IP address
     * @param string  $protocol  protocol - TCP or UDP
     * @param integer $port      port number
     * @param string  $interface External interface name (ie: eth0)
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_port($name, $wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($port));
        Validation_Exception::is_valid($this->validate_interface($interface));
 
        $rule = new Rule();

        $rule->set_name($name);
        $rule->set_protocol( $rule->convert_protocol_name($protocol) );
        $rule->set_port($port);
        $rule->set_flags(Rule::ONE_TO_ONE | Rule::ENABLED);
        $rule->set_address($wan_ip);
        $rule->set_parameter($interface . '_' . $lan_ip);

        $this->add_rule($rule);
    }

    /**
     * Adds a port range 1:1 NAT rule.
     *
     * @param string  $name      optional rule nickname
     * @param string  $wan_ip    WAN IP address
     * @param string  $lan_ip    LAN IP address
     * @param string  $protocol  protocol - TCP or UDP
     * @param integer $from_port from port number
     * @param integer $to_port   to port number
     * @param string  $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_port_range($name, $wan_ip, $lan_ip, $protocol, $from_port, $to_port, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port_range($from_port, $to_port));
        Validation_Exception::is_valid($this->validate_interface($interface));
 
        $rule = new Rule();

        $rule->set_name($name);
        $rule->set_protocol( $rule->convert_protocol_name($protocol) );
        $rule->set_port_range($from_port, $to_port);
        $rule->set_flags(Rule::ONE_TO_ONE | Rule::ENABLED);
        $rule->set_address($wan_ip);
        $rule->set_parameter($interface . '_' . $lan_ip);

        $this->add_rule($rule);
    }

    /**
     * Deletes an existing 1:1 NAT rule.
     *
     * @param string $wan_ip    WAN IP address
     * @param string $lan_ip    LAN IP address
     * @param string $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete($wan_ip, $lan_ip, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_interface($interface));

        $rule = new Rule();

        $rule->set_address($wan_ip);

        if (!strlen($interface))
            $rule->set_parameter($lan_ip);
        else
            $rule->set_parameter($interface . '_' . $lan_ip);

        $rule->set_flags(Rule::ONE_TO_ONE);

        $this->delete_rule($rule);
    }

    /**
     * Deletes an existing 1:1 NAT port rule.
     *
     * @param string  $wan_ip    WAN IP address
     * @param string  $lan_ip    LAN IP address
     * @param string  $protocol  protocol - TCP or UDP
     * @param integer $port      port number
     * @param string  $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_port($wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($port));
        Validation_Exception::is_valid($this->validate_interface($interface));

        $rule = new Rule();

        $rule->set_address($wan_ip);

        if (!strlen($interface))
            $rule->set_parameter($lan_ip);
        else
            $rule->set_parameter($interface . '_' . $lan_ip);

        switch ($protocol) {
            case 'TCP':
                $rule->set_protocol(Firewall::PROTOCOL_TCP);
                break;

            case 'UDP':
                $rule->set_protocol(Firewall::PROTOCOL_UDP);
                break;
        }

        $rule->set_port($port);
        $rule->set_flags(Rule::ONE_TO_ONE);

        $this->delete_rule($rule);
    }

    /**
     * Delete an existing 1:1 NAT port range rule.
     *
     * @param string $wan_ip WAN IP address
     * @param string $lan_ip LAN IP address
     * @param string $protocol protocol - TCP or UDP
     * @param int $from_port from port number
     * @param int $to_port to port number
     * @param string $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_port_range($wan_ip, $lan_ip, $protocol, $from_port, $to_port, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port_range($from_port, $to_port));
        Validation_Exception::is_valid($this->validate_interface($interface));

        $rule = new Rule();

        $rule->set_address($wan_ip);

        if (!strlen($interface))
            $rule->set_parameter($lan_ip);
        else
            $rule->set_parameter($interface . '_' . $lan_ip);

        switch ($protocol) {
        case 'TCP':
            $rule->set_protocol(Firewall::PROTOCOL_TCP);
            break;

        case 'UDP':
            $rule->set_protocol(Firewall::PROTOCOL_UDP);
            break;
        }

        $rule->set_port_range($from_port, $to_port);
        $rule->set_flags(Rule::ONE_TO_ONE);

        $this->delete_rule($rule);
    }

    /**
     * Enable/disable an existing 1:1 NAT rule.
     *
     * @param boolean $state     state of rule
     * @param string  $wan_ip    WAN IP address
     * @param string  $lan_ip    LAN IP address
     * @param string  $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_state($state, $wan_ip, $lan_ip, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_interface($interface));

        $rule = new Rule();

        $rule->set_address($wan_ip);
        $rule->set_parameter($interface . '_' . $lan_ip);
        $rule->set_flags(Rule::ONE_TO_ONE);

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
     * Delete an existing 1:1 NAT port rule.
     *
     * @param boolean $state     state of rule
     * @param string  $wan_ip    WAN IP address
     * @param string  $lan_ip    LAN IP address
     * @param string  $protocol  protocol - TCP or UDP
     * @param integer $port      port number
     * @param string  $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_port_state($state, $wan_ip, $lan_ip, $protocol, $port, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port_range($porto_port));
        Validation_Exception::is_valid($this->validate_interface($interface));

        $rule = new Rule();

        $rule->set_address($wan_ip);
        $rule->set_parameter($interface . '_' . $lan_ip);

        switch ($protocol) {
            case 'TCP':
                $rule->set_protocol(Firewall::PROTOCOL_TCP);
                break;

            case 'UDP':
                $rule->set_protocol(Firewall::PROTOCOL_UDP);
                break;
        }

        $rule->set_port($port);
        $rule->set_flags(Rule::ONE_TO_ONE);

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
     * Delete an existing 1:1 NAT port range rule.
     *
     * @param boolean $enabled state of rule
     * @param string $wan_ip WAN IP address
     * @param string $lan_ip LAN IP address
     * @param string $protocol protocol - TCP or UDP
     * @param int $from_port from port number
     * @param int $to_port to port number
     * @param string $interface external interface name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function toggle_enable_port_range($enabled, $wan_ip, $lan_ip, $protocol, $from_port, $to_port, $interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($wan_ip));
        Validation_Exception::is_valid($this->validate_ip($lan_ip));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port_range($from_port, $to_port));
        Validation_Exception::is_valid($this->validate_interface($interface));


        $rule = new Rule();

        try {
            $rule->set_address($wan_ip);
            $rule->set_parameter($interface . '_' . $lan_ip);

            switch ($protocol) {
            case 'TCP':
                $rule->set_protocol(Firewall::PROTOCOL_TCP);
                break;

            case 'UDP':
                $rule->set_protocol(Firewall::PROTOCOL_UDP);
                break;
            }

            $rule->set_port_range($from_port, $to_port);
            $rule->set_flags(Rule::ONE_TO_ONE);

            if (!($rule = $this->find_rule($rule))) return;

            $this->delete_rule($rule);
            ($enabled) ? $rule->enable() : $rule->disable();
            $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), COMMON_WARNING);
        }
    }

    /**
     * Returns list of 1:1 NAT rules.
     *
     * @return array array list of 1:1 NAT rules
     * @throws Engine_Exception
     */

    public function get()
    {
        clearos_profile(__METHOD__, __LINE__);

        $nat_list = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::ONE_TO_ONE))
                continue;

            if ($rule->get_port())
                continue;

            $info = array();
            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();

            if (strpos($rule->get_parameter(), '_') === FALSE) {
                $interface = '';
                $lan_ip = $rule->get_parameter();
            } else {
                list($interface, $lan_ip) = explode('_', $rule->get_parameter());
            }

            $info['host'] = sprintf('%s|%s', $lan_ip, $rule->get_address());
            $info['interface'] = $interface;

            $nat_list[] = $info;
        }

        return $nat_list;
    }

    /**
     * Returns list of 1:1 NAT port rules.
     *
     * @return array array list of 1:1 NAT port rules
     * @throws Engine_Exception
     */

    public function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        $nat_list = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::ONE_TO_ONE))
                continue;

            if (!$rule->get_port())
                continue;

            if (strstr($rule->get_port(), ':'))
                continue;

            $info = array();
            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();
            $info['protocol'] = $rule->get_protocol();
            $info['protocol_name'] = $rule->get_protocol_name();


            if (strpos($rule->get_parameter(), '_') === FALSE) {
                $interface = '';
                $lan_ip = $rule->get_parameter();
            } else {
                list($interface, $lan_ip) = explode('_', $rule->get_parameter());
            }

            $info['host'] = sprintf('%s|%s|%s|%d', $lan_ip, $rule->get_address(), $info['protocol_name'], $rule->get_port());
            $info['interface'] = $interface;

            $nat_list[] = $info;
        }

        return $nat_list;
    }

    /**
     * Returns list of 1:1 NAT port range rules.
     *
     * @return array array list of 1:1 NAT port rules
     * @throws Engine_Exception
     */

    public function get_port_range()
    {
        clearos_profile(__METHOD__, __LINE__);

        $nat_list = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::ONE_TO_ONE))
                continue;

            if (!$rule->get_port())
                continue;

            if (!strstr($rule->get_port(), ':'))
                continue;

            $info = array();
            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();
            $info['protocol'] = $rule->get_protocol();
            $info['protocol_name'] = $rule->get_protocol_name();

            if (strpos($rule->get_parameter(), '_') === FALSE) {
                $interface = '';
                $lan_ip = $rule->get_parameter();
            } else {
                list($interface, $lan_ip) = explode('_', $rule->get_parameter());
            }

            $match = array();
            
            preg_match('/(.*):(.*)/', $rule->get_port(), $match);    
            $info['host'] = sprintf('%s|%s|%s|%d|%d', $lan_ip, $rule->get_address(), $info['protocol_name'], $match[1], $match[2]);
            $info['interface'] = $interface;

            $nat_list[] = $info;
        }

        return $nat_list;
    }
}
