<?php

/**
 * Philesight class.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/disk_usage/
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

namespace clearos\apps\disk_usage;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('disk_usage');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Philesight class.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/disk_usage/
 */

class Philesight extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const PHILESIGHT_COMMAND = '/usr/sbin/philesightcli';
    const FILE_DATA = '/usr/webconfig/tmp/ps.db';
    const MAX_COORDINATE = 100000;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Philesight constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns a Philesight PNG image.
     *
     * @param string $path path
     *
     * @return image Philesight image
     * @throws Engine_Exception
     */

    public function get_image($path = '/')
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_path($path));

        $command = escapeshellcmd(self::PHILESIGHT_COMMAND . ' --action image --path ' . $path);

        ob_start();
        passthru($command);
        $png = ob_get_clean();

        return $png;
    }

    /**
     * Returns path for given coordinates.
     *
     * @param string  $path   path
     * @param integer $xcoord x-coordinate
     * @param integer $ycoord y-coordinate
     *
     * @return string path
     * @throws Engine_Exception
     */

    public function get_path($path, $xcoord, $ycoord)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_path($path));
        Validation_Exception::is_valid($this->validate_coordinate($xcoord));
        Validation_Exception::is_valid($this->validate_coordinate($ycoord));

        $command = escapeshellcmd(
            self::PHILESIGHT_COMMAND .  ' ' .
            '--action find' . ' ' .
            '--path ' . $path .  ' ' .
            '--xcoord ' . $xcoord . ' ' . 
            '--ycoord ' . $ycoord
        );

        ob_start();
        passthru($command);
        $path = ob_get_clean();

        return $path;
    }

    /**
     * Returns state of Philesight.
     *
     * @return boolean TRUE if Philesight has been initialized
     * @throws Engine_Exception
     */

    public function initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_DATA);

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Validation routine for coordinates.
     *
     * @param integer $coordinate coordinate
     *
     * @return string error message if coordinate is invalid
     */

    public function validate_coordinate($coordinate)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^\d+$/', $coordinate) || ($coordinate > self::MAX_COORDINATE))
            return lang('disk_usage_coordinate_is_invalid');
    }

    /**
     * Validation routine for path.
     *
     * @param string $path path
     *
     * @return string error message if path is invalid
     */

    public function validate_path($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        $path = realpath($path);

        $folder = new Folder($path);

        if (! $folder->exists())
            return lang('disk_usage_path_invalid');
    }
}
