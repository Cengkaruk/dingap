<?php

/**
 * Account egress_firewall ajax controller.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
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

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Account Egress_Firewall ajax controller.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
 */

class Ajax extends ClearOS_Controller
{

    /**
     * Egress_Firewall AJAX default controller
     *
     * @return view
     */

    function index()
    {
        echo "These aren't the droids you're looking for...";
    }

    /**
     * Get egress mode/state (allow or block all).
     *
     * @return JSON
     */

    function get_egress_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');

        // Load dependencies
        //------------------

        $this->load->library('egress_firewall/Egress');
        $this->lang->load('egress_firewall');

        try {
            $state = $this->egress->get_egress_state();
            echo json_encode(Array('state' => ($state ? lang('egress_firewall_block_all') : lang('egress_firewall_allow_all'))));
        } catch (Exception $e) {
            echo json_encode(Array('state' => lang('base_unknown')));
        }
    }
}
