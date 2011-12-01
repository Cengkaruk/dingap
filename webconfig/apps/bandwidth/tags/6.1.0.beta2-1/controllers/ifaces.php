<?php

/**
 * Bandwidth interfaces controller.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
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
 * Bandwidth interfaces controller.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
 */

class Ifaces extends ClearOS_Controller
{
    /**
     * Bandwidth interfaces overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('bandwidth');
        $this->load->library('bandwidth/Bandwidth');

        // Load view data
        //---------------

        try {
            $data['ifaces'] = $this->bandwidth->get_interfaces();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('bandwidth/ifaces/summary', $data, lang('bandwidth_network_interfaces'));
    }

    /**
     * Bandwidth interface edit.
     *
     * @param string $iface network interface
     *
     * @return view
     */

    function edit($iface)
    {
        // Load libraries
        //---------------

        $this->lang->load('bandwidth');
        $this->load->library('bandwidth/Bandwidth');
        $this->load->library('network/Iface', $iface);

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('upstream', 'bandwidth/Bandwidth', 'validate_rate', TRUE);
        $this->form_validation->set_policy('downstream', 'bandwidth/Bandwidth', 'validate_rate', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            try {
                $this->bandwidth->update_interface(
                    $iface,
                    $this->input->post('upstream'),
                    $this->input->post('downstream')
                );

                // Return to summary page with status message
                $this->page->set_status_updated();
                redirect('/bandwidth/ifaces');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $info = $this->bandwidth->get_interface_settings($iface);
            $ip = $this->iface->get_live_ip();

            $data['iface'] = $iface;
            $data['ip'] = $ip;
            $data['configured'] = $info['configured'];
            $data['upstream'] = $info['upstream'];
            $data['downstream'] = $info['downstream'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('bandwidth/ifaces/item', $data, lang('bandwidth_network_interface'));
    }
}
