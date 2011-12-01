<?php

/**
 * Egress firewall controller.
 *
 * @category   Apps
 * @package    Egress_Firewall
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Egress firewall controller.
 *
 * @category   Apps
 * @package    Egress_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
 */

class Egress_Firewall extends ClearOS_Controller
{
    /**
     * Firewall (egress) overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('egress_firewall');

        // Load views
        //-----------

        $views = array('egress_firewall/port', 'egress_firewall/domain');

        $this->page->view_forms($views, lang('egress_firewall_app_name'));
    }

    /**
     * Egress mode.
     *
     * @return view
     */

    function mode()
    {
        // Load dependencies
        //------------------

        $this->load->library('egress_firewall/Egress');
        $this->lang->load('egress_firewall');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $this->egress->set_egress_state($this->input->post('state'));
                $this->page->set_status_updated();
                redirect('/egress_firewall');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['state_options'] = $this->egress->get_egress_state_options();
            $data['state'] = $this->egress->get_egress_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('egress_firewall/mode', $data, lang('egress_firewall_mode'));
    }
}
