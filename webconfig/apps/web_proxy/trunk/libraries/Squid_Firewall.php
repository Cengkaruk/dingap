<?php

/**
 * Squid web proxy firewall class.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
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

namespace clearos\apps\web_proxy;

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

use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\firewall\Rule as Rule;

clearos_load_library('firewall/Firewall');
clearos_load_library('firewall/Rule');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Squid web proxy firewall class.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Squid_Firewall extends Firewall
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Squid firewall constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();
    }

    /**
     * Returns proxy bypass rules.
     *
     * The returned array contains the following hash entries:
     * - info[name]
     * - info[address]
     * - info[enabled]
     *
     * @return array array containing proxy bypass rules
     * @throws Engine_Exception
     */

    public function get_proxy_bypass_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & (Rule::PROXY_BYPASS)))
                continue;

            $info = array();
            $info['name'] = $rule->get_name();
            $info['address'] = $rule->get_address();
            $info['enabled'] = $rule->is_enabledd();
            $list[] = $info;
        }

        return $list;
    }

    /**
     * Returns the port of the proxy content filter.
     *
     * @return int port address of the parent filter
     * @throws Engine_Exception
     */

    public function get_proxy_filter_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->get_value('SQUID_FILTER_PORT');
    }

    /**
     * Returns state of proxy transparent mode.
     *
     * @return boolean TRUE if in trasparent mode enabled
     * @throws Engine_Exception
     */

    public function get_proxy_transparent_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->get_state('SQUID_TRANSPARENT');
    }

    /**
     * Adds a proxy bypass rule to the firewall.
     *
     * @param string $name    rule nickname
     * @param string $address host/IP address to enable proxy bypass
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function add_proxy_bypass($name, $address)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_address($address));

        $rule = new Rule();

        $rule->set_name($name);
        $rule->set_address($address);
        $rule->set_flags(Rule::PROXY_BYPASS | Rule::ENABLED);

        $this->add_rule($rule);
    }

    /**
     * Remove a proxy bypass rule from the firewall.
     *
     * @param string $address host/IP address to enable proxy bypass
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function delete_proxy_bypass($address)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_address($address));

        $rule = new Rule();

        $rule->set_flags(Rule::PROXY_BYPASS);
        $rule->set_address($address);
        $this->delete_rule($rule);
    }

    /**
     * Define a port for a parent content filter used by proxy.
     *
     * @param integer $port Port address of the Content Filter
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_proxy_filter_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_value($port, 'SQUID_FILTER_PORT');
    }

    /**
     * Set state of proxy transparent mode.
     *
     * @param string $state state of transparent mode
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_proxy_transparent_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_state($state, 'SQUID_TRANSPARENT');
    }

    /**
     * Sets state of proxy bypass rule.
     *
     * @param boolean $state   state of the rule
     * @param string  $address host/IP address to remove
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_proxy_bypass_state($state, $address)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_address($address));

        $rule = new Rule();

        $rule->set_address($address);
        $rule->set_flags(Rule::PROXY_BYPASS);

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
