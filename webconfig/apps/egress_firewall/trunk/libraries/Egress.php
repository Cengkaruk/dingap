<?php

/**
 * Egress firewall class.
 *
 * @category   Apps
 * @package    Egress_Firewall
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
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

namespace clearos\apps\egress_firewall;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\firewall\Rule as Rule;
use \clearos\apps\firewall\Firewall as Firewall;

clearos_load_library('firewall/Rule');
clearos_load_library('firewall/Firewall');

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
 * Egress firewall class.
 *
 * @category   Apps
 * @package    Egress_Firewall
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
 */

class Egress extends Firewall
{
    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Egress constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();
    }

    /**
     * Add a common service to the block list.
     *
     * @param string destination destination address
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_block_common_destination($destination)
    {
        clearos_profile(__METHOD__, __LINE__);

        global $domains;

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_destination($destination));

        $mydomains = $domains;

        foreach ($mydomains as $domaininfo) {
            if ($domaininfo[1] == $destination) {
                $rule = new Rule();

                $rule->set_address($domaininfo[0]);
                $rule->set_flags(Rule::OUTGOING_BLOCK | Rule::ENABLED);
                if ($rule->check_validation_errors())
                    $this->errors = $rule->copy_validation_errors(true);
                else
                    $this->add_rule($rule);
            }
        }
    }

    /**
     * Add common destination to block list.
     *
     * @param string name        rule nickname
     * @param string destination destination address
     * @return void
     *
     * @throws Engine_Exception
     */

    public function add_block_destination($name, $destination)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_destination($destination));

        $rule = new Rule();

        try {
            $rule->set_name($name);
            $rule->set_address($destination);
            $rule->set_flags(Rule::OUTGOING_BLOCK | Rule::ENABLED);

            if ($rule->CheckValidationErrors())
                $this->errors = $rule->CopyValidationErrors(true);
            else
                $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Add a port/to the outgoing allow list.
     *
     * @param string name     rule nickname
     * @param string protocol the protocol - UDP/TCP
     * @param int    port     port number
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_block_port($name, $protocol, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($port));

        $rule = new Rule();

        try {
            $rule->set_name($name);
            $rule->set_port($port);
            $rule->set_protocol( $rule->convert_protocol_name($protocol) );
            $rule->set_flags(Rule::OUTGOING_BLOCK | Rule::ENABLED);

            if ($rule->check_validation_errors())
                $this->errors = $rule->copy_validation_errors(true);
            else
                $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Add a port range to the outgoing allow list.
     *
     * @param string name     rule nickname
     * @param string protocol the protocol - UDP/TCP
     * @param int    from     from port number
     * @param int    to       to port number
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_block_port_range($name, $protocol, $from, $to)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_from_port($from));
        Validation_Exception::is_valid($this->validate_to_port($to));

        if ($from >= $to)
            throw new Validation_Exception(lang('egress_firewall_port_from_to_invalid'), CLEAROS_ERROR);

        $rule = new Rule();

        try {
            $rule->set_name($name);
            $rule->set_protocol( $rule->convert_protocol_name($protocol) );
            $rule->set_port_range($from, $to);
            $rule->set_flags(Rule::OUTGOING_BLOCK | Rule::ENABLED);

            if ($rule->check_validation_errors())
                $this->errors = $rule->copy_validation_errors(true);
            else
                $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Add a standard service to the outgoing allow list.
     *
     * @param string service service name eg HTTP, FTP, SMTP
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_block_standard_service($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        global $PORTS;

        if ($service == "PPTP") {
            throw new Engine_Exception("TODO: No support for blocking outgoing PPTP traffic", COMMON_WARNING);
        } else if ($service == "IPsec") {
            throw new Engine_Exception("TODO: No support for blocking outgoing IPsec traffic", COMMON_WARNING);
        } else {
            Validation_Exception::is_valid($this->validate_service($service));

            $rule = new Rule();

            try {
                foreach ($PORTS as $port) {
                    if ($port[3] != $service)
                        continue;

                    $rule->set_port($port[2]);
                    $rule->set_protocol( $rule->convert_protocol_name($port[1]) );
                    $rule->set_name(preg_replace("/\//", "", $service));
                    $rule->set_flags(Rule::OUTGOING_BLOCK | Rule::ENABLED);
                    $this->add_rule($rule);
                }
            } catch (Exception $e) {
                throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
            }
        }
    }

    /**
     * Enable/disable a host, IP or network from the block outgoing hosts list.
     *
     * @param boolean enabled rule enabled?
     * @param string  host    host, IP or network
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function toggle_enable_block_destination($enabled, $host)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        try {
            $rule->set_address($host);
            $rule->set_flags(Rule::OUTGOING_BLOCK);

            if(!($rule = $this->find_rule($rule)))
                return;

            $this->delete_rule($rule);

            ($enabled) ? $rule->enable() : $rule->disable();

            $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Enable/disable a port from the outgoing allow list.
     *
     * @param boolean enabled rule enabled?
     * @param string protocol the protocol - UDP/TCP
     * @param int port port number
     *
     * @return void
     * @throws Engine_Exception
     */

    public function toggle_enable_block_port($enabled, $protocol, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($port));

        if ($from >= $to)
            throw new Validation_Exception(lang('egress_firewall_port_from_to_invalid'), CLEAROS_ERROR);
        try {
            $rule->set_protocol( $rule->convert_protocol_name($protocol) );
            $rule->set_port($port);
            $rule->set_flags(Rule::OUTGOING_BLOCK);

            if(!($rule = $this->find_rule($rule)))
                return;

            $this->delete_rule($rule);

            ($enabled) ? $rule->enable() : $rule->disable();

            $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Enable/disable a port range from the outgoing allow list.
     *
     * @param boolean enabled  rule enabled?
     * @param string  protocol the protocol - UDP/TCP
     * @param int     port     port number
     *
     * @return void
     * @throws Engine_Exception
     */

    public function toggle_enable_block_port_range($enabled, $protocol, $from, $to)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_from_port($from));
        Validation_Exception::is_valid($this->validate_to_port($to));

        $rule = new Rule();

        try {
            $rule->set_protocol( $rule->convert_protocol_name($protocol) );
            $rule->set_port_range($from, $to);
            $rule->set_flags(Rule::OUTGOING_BLOCK);

            if(!($rule = $this->find_rule($rule)))
                return;

            $this->delete_rule($rule);

            ($enabled) ? $rule->enable() : $rule->disable();

            $this->add_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Delete a host, IP or network from the block outgoing hosts list.
     *
     * @param string host host, IP or network
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_block_destination($host)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        try {
            $rule->set_address($host);
            $rule->set_flags(Rule::OUTGOING_BLOCK);
            $this->delete_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Delete a port from the outgoing allow list.
     *
     * @param string protocol the protocol - UDP/TCP
     * @param int    port     port number
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_block_port($protocol, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_port($port));

        $rule = new Rule();

        try {
            $rule->set_protocol( $rule->convert_protocol_name($protocol) );
            $rule->set_port($port);
            $rule->set_flags(Rule::OUTGOING_BLOCK);
            $this->delete_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Delete a port range from the outgoing allow list.
     *
     * @param string protocol the protocol - UDP/TCP
     * @param int    from     from port number
     * @param int    to       to port number
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function delete_block_port_range($protocol, $from, $to)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------
        Validation_Exception::is_valid($this->validate_protocol($protocol));
        Validation_Exception::is_valid($this->validate_from_port($from));
        Validation_Exception::is_valid($this->validate_to_port($to));

        $rule = new Rule();

        try {
            $rule->set_protocol( $rule->convert_protocol_name($protocol) );
            $rule->set_port_range($from, $to);
            $rule->set_flags(Rule::OUTGOING_BLOCK);
            $this->delete_rule($rule);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Return a list of common servers that people block.
     *
     * @return array array list of common services blocked
     */

    public function get_common_block_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        global $domains;

        $byname = array();

        foreach ($domains as $item)
            array_push($byname, $item[1]);

        return $byname;
    }

    /**
     * Returns list of blocked hosts.
     *
     * @return array array list of blocked hosts
     * @throws Engine_Exception
     */

    public function get_block_hosts()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hosts = array();

        try {
            $rules = $this->get_rules();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::OUTGOING_BLOCK))
                continue;

            if ($rule->get_flags() & (Rule::WIFI | Rule::CUSTOM))
                continue;

            if (!strlen($rule->get_address()))
                continue;

            $hostinfo = array();
            $hostinfo['name'] = $rule->get_name();
            $hostinfo['enabled'] = $rule->is_enabled();
            $hostinfo['host'] = $rule->get_address();
            $hostinfo['metainfo'] = $this->lookup_host_metainfo($hostinfo[host]);

            $hosts[] = $hostinfo;
        }

        return $hosts;
    }

    /**
     * Gets allowed outgoing port ranges.  The information is an array
     * with the following hash array entries:
     *
     *  info[name]
     *  info[protocol]
     *  info[from]
     *  info[to]
     *  info[enabled]
     *
     * @return string allowed outgoing port ranges
     * @throws Engine_Exception
     */

    public function get_block_port_ranges()
    {
        clearos_profile(__METHOD__, __LINE__);

        $portlist = array();

        try {
            $rules = $this->get_rules();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        foreach ($rules as $rule) {
            if (!strstr($rule->get_port(), ":"))
                continue;

            if (!($rule->get_flags() & Rule::OUTGOING_BLOCK))
                continue;

            if ($rule->get_flags() & (Rule::WIFI | Rule::CUSTOM))
                continue;

            if ($rule->get_protocol() != Rule::PROTO_TCP && $rule->get_protocol() != Rule::PROTO_UDP)
                continue;

            $info = array();

            switch ($rule->get_protocol()) {

                case Rule::PROTO_TCP:
                    $info['protocol'] = "TCP";
                    break;

                case Rule::PROTO_UDP:
                    $info['protocol'] = "UDP";
                    break;
            }

            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();
            list($info['from'], $info['to']) = split(":", $rule->get_port(), 2);

            $portlist[] = $info;
        }

        return $portlist;
    }

    /**
     * Gets allowed outgoing ports.  The information is an array
     * with the following hash array entries:
     *
     *  info[name]
     *  info[protocol]
     *  info[port]
     *  info[service] (FTP, HTTP, etc.)
     *  info[enabled]
     *
     * @return string allowed outgoing ports
     * @throws Engine_Exception
     */

    public function get_block_ports()
    {
        clearos_profile(__METHOD__, __LINE__);

        $portlist = array();

        try {
            $rules = $this->get_rules();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        foreach ($rules as $rule) {
            if (strstr($rule->get_port(), ":"))
                continue;

            if (!($rule->get_flags() & Rule::OUTGOING_BLOCK))
                continue;

            if ($rule->get_flags() & (Rule::WIFI | Rule::CUSTOM))
                continue;

            if ($rule->get_protocol() != Rule::PROTO_TCP && $rule->get_protocol() != Rule::PROTO_UDP)
                continue;

            $info = array();

            switch ($rule->get_protocol()) {

                case Rule::PROTO_TCP:
                    $info['protocol'] = "TCP";
                    break;

                case Rule::PROTO_UDP:
                    $info['protocol'] = "UDP";
                    break;
            }

            $info['port'] = $rule->get_port();
            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();
            $info['service'] = $this->lookup_service($info['protocol'], $info['port']);
            $portlist[] = $info;
        }

        return $portlist;
    }

    /**
     * Returns state of egress mode.
     *
     * @return boolean true if egress mode is enabled
     * @throws Engine_Exception
     */

    public function get_egress_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->get_state("EGRESS_FILTERING");
    }

    /**
     * Sets state of egress mode.
     *
     * @param boolean $state state of egress mode
     *
     * @returns void
     * @throws Engine_Exception
     */

    public function set_egress_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_state($state, "EGRESS_FILTERING");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for rule name.
     *
     * @param string $name name
     *
     * @return mixed void if name is valid, errmsg otherwise
     */

    function validate_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);
        if ($name == NULL || $name === '')
            return lang('egress_firewall_invalid_name');
    }

    /**
     * Validation routine for destination domain.
     *
     * @param string $destination destination domain
     *
     * @return mixed void if destination is valid, errmsg otherwise
     */

    function validate_destination($destination)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($destination == NULL || $destination === '')
            return lang('egress_firewall_invalid_destination');
    }

    /**
     * Validation routine for protocol.
     *
     * @param string $protocol protocol
     *
     * @return mixed void if protocol is valid, errmsg otherwise
     */

    function validate_protocol($protocol)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($protocol == NULL || $protocol === '')
            return lang('egress_firewall_invalid_protocol');
    }

    /**
     * Validation routine for port.
     *
     * @param string $port port
     *
     * @return mixed void if port is valid, errmsg otherwise
     */

    function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_nan($port) || $port < 0 || $port > 65535)
            return lang('egress_firewall_invalid_port');
    }

    /**
     * Validation routine for from port.
     *
     * @param string $port port
     *
     * @return mixed void if port is valid, errmsg otherwise
     */

    function validate_from_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->validate_port($port);
    }
 
    /**
     * Validation routine for to port.
     *
     * @param string $port port
     *
     * @return mixed void if port is valid, errmsg otherwise
     */

    function validate_to_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->validate_port($port);
    }
}

// vim: syntax=php ts=4
