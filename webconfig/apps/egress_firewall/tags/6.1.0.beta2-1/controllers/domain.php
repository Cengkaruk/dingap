<?php

/**
 * Firewall egress domain controller.
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
 * Firewall egress domain controller.
 *
 * @category   Apps
 * @package    Egress_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/egress_firewall/
 */

class Domain extends ClearOS_Controller
{
    /**
     * Egress domain overview.
     *
     * @return view
     */

    function index()
    {
        $this->load->library('egress_firewall/Egress');
        $this->lang->load('egress_firewall');

        // Load view data
        //---------------

        try {
            $data['hosts'] = $this->egress->get_exception_hosts();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('egress_firewall/domain/summary', $data, lang('egress_firewall_app_name'));
    }

    /**
     * Add domain rule.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->load->library('egress_firewall/Egress');
        $this->lang->load('egress_firewall');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('nickname', 'egress_firewall/Egress', 'validate_name', TRUE);
        $this->form_validation->set_policy('host', 'egress_firewall/Egress', 'validate_address', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->egress->add_exception_destination($this->input->post('nickname'), $this->input->post('host'));

                $this->page->set_status_added();
                redirect('/egress_firewall');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the views
        //---------------

        $this->page->view_form('egress_firewall/domain/add', $data, lang('base_add'));
    }

    /**
     * Delete blocked host.
     *
     * @param string $host host
     *
     * @return view
     */

    function delete($host)
    {
        $confirm_uri = '/app/egress_firewall/domain/destroy/' . $host;
        $cancel_uri = '/app/egress_firewall';
        $items = array($host);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys blocked host rule.
     *
     * @param string $host host
     *
     * @return view
     */

    function destroy($host)
    {
        try {
            $this->load->library('egress_firewall/Egress');

            $this->egress->delete_exception_destination($host);

            $this->page->set_status_deleted();
            redirect('/egress_firewall');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disables blocked host rule.
     *
     * @param string $host host
     *
     * @return view
     */

    function disable($host)
    {
        try {
            $this->load->library('egress_firewall/Egress');

            $this->egress->toggle_enable_exception_destination(FALSE, $host);

            $this->page->set_status_disabled();
            redirect('/egress_firewall');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enables block host rule.
     *
     * @param string $host host
     *
     * @return view
     */

    function enable($host)
    {
        try {
            $this->load->library('egress_firewall/Egress');

            $this->egress->toggle_enable_exception_destination(TRUE, $host);

            $this->page->set_status_enabled();
            redirect('/egress_firewall');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
