<?php

/**
 * Network status class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
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

namespace clearos\apps\network;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Iface_Manager as Iface_Manager;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Iface_Manager');

// Exceptions
//-----------

use \clearos\apps\network\Network_Status_Unknown_Exception as Network_Status_Unknown_Exception;

clearos_load_library('network/Network_Status_Unknown_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network status class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Network_Status extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_UNKNOWN = 'unknown';

    // TODO: move to syswatch
    const FILE_STATE = '/var/lib/syswatch/state';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ifs_in_use = array();
    protected $ifs_working = array();
    protected $is_state_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Network status constructor.
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns list of working external (WAN) interfaces.
     *
     * Syswatch monitors the connections to the Internet.  A connection
     * is considered online when it can ping the Internet.
     *
     * @return array list of working WAN interfaces
     * @throws Engine_Exception, Network_Status_Unknown_Exception
     */

    public function get_working_external_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_state_loaded)
            $this->_load_status();

        return $this->ifs_working;
    }

    /**
     * Returns list of in use external (WAN) interfaces.
     *
     * Syswatch monitors the connections to the Internet.  A connection
     * is considered in use when it can ping the Internet and is actively
     * used to connect to the Internet.  A WAN interface used for only backup
     * purposes is only included in this list when non-backup WANs are all down.
     *
     * @return array list of in use WAN interfaces
     * @throws Engine_Exception, Network_Status_Unknown_Exception
     */

    public function get_in_use_external_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_state_loaded)
            $this->_load_status();

        return $this->ifs_in_use;
    }

    /**
     * Returns status of connection to Internet.
     *
     * @return integer status of Internet connection
     * @throws Engine_Exception
     */

    public function get_connection_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $ifaces = $this->get_working_external_interfaces();

            if (empty($ifaces))
                return self::STATUS_OFFLINE;
            else
                return self::STATUS_ONLINE;
        } catch (Network_Status_Unknown_Exception $e) {
            return self::STATUS_UNKNOWN;
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Loads state file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception, Network_Status_Unknown_Exception
     */

    protected function _load_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATE);

        if (! $file->exists())
            throw new Network_Status_Unknown_Exception();

        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            $match = array();

            if (preg_match('/^SYSWATCH_WANIF=(.*)/', $line, $match)) {
                $ethraw = $match[1];
                $ethraw = preg_replace('/"/', '', $ethraw);

                if (! empty($ethraw)) {
                    $ethlist = explode(' ', $ethraw);
                    $this->ifs_in_use = explode(' ', $ethraw);
                    $this->is_state_loaded = TRUE;
                }
            }

            if (preg_match('/^SYSWATCH_WANOK=(.*)/', $line, $match)) {
                $ethraw = $match[1];
                $ethraw = preg_replace('/"/', '', $ethraw);

                if (! empty($ethraw)) {
                    $ethlist = explode(' ', $ethraw);
                    $this->ifs_working = explode(' ', $ethraw);
                    $this->is_state_loaded = TRUE;
                }
            }
        }
    }
}
