<?php

/**
 * Port check firewall class.
 *
 * @category   Apps
 * @package    Incoming_Firewall
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/incoming_firewall/
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

namespace clearos\apps\incoming_firewall;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('incoming_firewall');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\incoming_firewall\Incoming as Incoming;
use \clearos\apps\network\Network as Network;

clearos_load_library('base/Engine');
clearos_load_library('firewall/Firewall');
clearos_load_library('incoming_firewall/Incoming');
clearos_load_library('network/Network');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Port check firewall class.
 *
 * @category   Apps
 * @package    Incoming_Firewall
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2004-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/incoming_firewall/
 */

class Port extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Incoming constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Adds a port/to the incoming allow list.
     *
     * @param string  $name     name
     * @param string  $protocol protocol
     * @param integer $port     port number
     *
     * @return void
     * @throws Engine_Exception, ValidationException
     */

    public function is_firewalled($protocol, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network();

        if (!$network->is_inbound_firewalled())
            return FALSE;

        $incoming = new Incoming();

        $is_firewalled = ($incoming->check_port($protocol, $port) === Firewall::CONSTANT_ENABLED) ? FALSE : TRUE;

        return $is_firewalled;
    }

    /**
     * Adds a port range to the incoming allow list.
     *
     * @param string  $name     name
     * @param string  $protocol protocol
     * @param integer $port     port
     *
     * @return void
     * @throws Engine_Exception, ValidationException
     */

    public function add_allow($name, $protocol, $port)
    {
        clearos_profile(__METHOD__, __LINE__);

        $incoming = new Incoming();

        $state = $incoming->check_port($protocol, $port);

        if ($state === Firewall::CONSTANT_DISABLED)
            $incoming->set_allow_port_state($protocol, $port);
        else if ($state === Firewall::CONSTANT_NOT_CONFIGURED)
            $incoming->add_allow_port($name, $protocol, $port);
    }

    /**
     * Delete a port from the incoming allow list.
     *
     * @param string  $protocol protocol
     * @param integer $port     port number
     *
     * @return void
     * @throws Engine_Exception, ValidationException
     */

    public function delete_allow_port($protocol, $port)
    {
        clearos_profile(__METHOD__, __LINE__);
    }
}
