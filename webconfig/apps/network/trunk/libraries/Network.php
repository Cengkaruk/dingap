<?php

/**
 * Network class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
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

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network class.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Network extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/network';
    const MODE_AUTO = 'auto';
    const MODE_GATEWAY = 'gateway';
    const MODE_TRUSTEDGATEWAY = 'trustedgateway';
    const MODE_STANDALONE = 'standalone';
    const MODE_TRUSTED_STANDALONE = 'trustedstandalone';
    const MODE_DMZ = 'dmz';
    const MODE_BRIDGE = 'bridge';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Network constructor.
     *
     * @return void
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns network mode.
     *
     * @return string mode
     * @throws Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

		try {
			$config = new File(self::FILE_CONFIG);
			$retval = $config->lookup_value('/^MODE=/');
		} catch (File_Not_Found_Exception $e) {
            return self::MODE_TRUSTEDSTANDALONE;
		} catch (Exception $e) {
			throw new Engine_Exception($e->get_message(), COMMON_WARNING);
		}

		$retval = preg_replace('/"/', '', $retval);
		$retval = preg_replace('/\s.*/', '', $retval);

        try {
            Validation_Exception::is_valid($this->validate_mode($mode));
        } catch (Exception $e) {
		    $retval = self::MODE_TRUSTEDSTANDALONE;
        }

        return $retval;
    }

	/**
	 * Set network mode.
	 *
	 * @param string mode Network mode
	 * @return void
	 * @throws Exception, Validation_Exception
	 */

	public function set_mode($mode)
	{
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mode($mode));

		try {
		    $config = new File(self::FILE_CONFIG);
			$match = $config->replace_lines("/^MODE=/", "MODE=\"$mode\"\n");
			if (! $match)
				$config->add_lines_after("MODE=\"$mode\"\n", '/^[^#]/');
		} catch (Exception $e) {
			throw new Engine_Exception($e->get_message(), COMMON_WARNING);
		}
	}

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates a network mode.
     *
     * @param string $mode Network mode
     *
     * @return string error message if mode is invalid
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

		switch ($mode)
		{
		case self::MODE_AUTO:
		case self::MODE_GATEWAY:
		case self::MODE_TRUSTEDGATEWAY:
		case self::MODE_STANDALONE:
		case self::MODE_TRUSTEDSTANDALONE:
		case self::MODE_DMZ:
		case self::MODE_BRIDGE:
            return '';
		}

        return lang('network_mode_is_invalid');
    }
}
