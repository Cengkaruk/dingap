<?php

/**
 * Syswatch class.
 *
 * @category   Apps
 * @package    Syswatch
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/syswatch/
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

namespace clearos\apps\syswatch;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('syswatch');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Daemon');
clearos_load_library('base/Shell');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Syswatch class.
 *
 * System watcher keeps an eye on network up/down events, and various
 * other system health issues.
 *
 * @category   Apps
 * @package    Syswatch
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/syswatch/
 */

class Syswatch extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_KILLALL = '/usr/bin/killall';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Syswatch constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct("syswatch");
    }

    /**
     * Resets daemons after a network change.
     *
     * Daemons will automagically configure themselves depending on the
     * network settings.  For example, if a user swaps the network roles
     * of eth0 and eth1 (LAN/WAN to WAN/LAN), the Samba software will
     * also swap its configuration around.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function reconfigure_network_settings()
    {
        clearos_profile(__METHOD__, __LINE__);
    
        $shell = new Shell();
        $shell->execute(self::COMMAND_KILLALL, "-USR2 syswatch", TRUE);
    }

    /**
     * Reconfigures small bits of the system.
     *
     * There are some chicken and egg moments to re-configuring webconfig.
     * Since webconfig is used to install software, it is not desirable
     * to restart webconfig during any software installation.  This
     * poses a problem to things like Horde web mail (which requires
     * a webconfig restart).
     *
     * The syswatch daemon has a special signal to handle this situation. 
     *
     *
     * @return void
     * @throws Engine_Exception
     */

    public function reconfigure_system()
    {
        clearos_profile(__METHOD__, __LINE__);
    
        $shell = new Shell();
        $shell->execute(self::COMMAND_KILLALL, "-USR1 syswatch", TRUE);
    }

    /**
     * Send signal to syswatch daemon.
     *
     * @param string $signal kill signal
     *
     * @return void
     * @throws Engine_Exception
     */

    public function send_signal($signal)
    {
        clearos_profile(__METHOD__, __LINE__);
    
        $shell = new Shell();
        $shell->execute(self::COMMAND_KILLALL, "-$signal syswatch", TRUE);
    }
}

