<?php

/**
 * AJAX controller for Nework Visualiser.
 *
 * @category   Apps
 * @package    Nework_Visualiser
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network_visualiser\Nework_Visualiser as Nework_Visualiser;
use \clearos\apps\base\Engine_Exception as Engine_Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * JSON controller.
 *
 * @category   Apps
 * @package    Nework_Visualiser 
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Ajax extends ClearOS_Controller
{
    /**
     * Ajax default controller
     *
     * @return string
     */

    function index()
    {
        echo "These aren't the droids you're looking for...";
    }

    /**
     * Ajax get app details controller
     *
     * @return JSON
     */

    function get_traffic_data()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');
        try {

            $this->load->library('network_visualiser/Network_Visualiser');

            $data = $this->network_visualiser->get_traffic_data();
            echo json_encode($data);

        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }
}
