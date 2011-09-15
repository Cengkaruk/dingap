<?php

/**
 * Network visualiser controller.
 *
 * @category   Apps
 * @package    Network_Visualiser
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
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
 * Network visualiser controller.
 *
 * @category   Apps
 * @package    Network_Visualiser
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Network_Visualiser extends ClearOS_Controller
{
    /**
     * Mail Notification default controller
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * Mail Notification edit controller
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    function _view_edit($mode = null)
    {
        // Load dependencies
        //------------------

        $this->load->library('network_visualiser/Network_Visualiser');
        $this->lang->load('network_visualiser');

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('sender', 'network_visualiser/Network_Visualiser', 'validate_email', TRUE);
        $this->form_validation->set_policy('host', 'network_visualiser/Network_Visualiser', 'validate_host', TRUE);
        $this->form_validation->set_policy('username', 'network_visualiser/Network_Visualiser', 'validate_username', FALSE);
        $this->form_validation->set_policy('password', 'network_visualiser/Network_Visualiser', 'validate_password', FALSE);
        $this->form_validation->set_policy('port', 'network_visualiser/Network_Visualiser', 'validate_port', TRUE);
        $this->form_validation->set_policy('ssl', 'network_visualiser/Network_Visualiser', 'validate_ssl', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->network_visualiser->set_host($this->input->post('host'));
                $this->network_visualiser->set_port($this->input->post('port'));
                $this->network_visualiser->set_ssl($this->input->post('ssl'));
                $this->network_visualiser->set_username($this->input->post('username'));
                $this->network_visualiser->set_password($this->input->post('password'));
                $this->network_visualiser->set_sender($this->input->post('sender'));
                $this->page->set_status_updated();
                redirect('/network_visualiser');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['host'] = $this->network_visualiser->get_host();
            $data['port'] = $this->network_visualiser->get_port();
            $data['ssl'] = $this->network_visualiser->get_ssl();
            $data['username'] = $this->network_visualiser->get_username();
            $data['password'] = $this->network_visualiser->get_password();
            $data['sender'] = $this->network_visualiser->get_sender();
            $data['ssl_options'] = $this->network_visualiser->get_ssl_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('network_visualiser/settings', $data, lang('network_visualiser_app_name'));
    }

    /**
     * Mail Notification test controller
     *
     * @return view
     */

    function test()
    {
        // Load dependencies
        //------------------

        $this->load->library('network_visualiser/Network_Visualiser');
        $this->lang->load('network_visualiser');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('email', 'network_visualiser/Network_Visualiser', 'validate_email', TRUE);
        $form_ok = $this->form_validation->run();

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->network_visualiser->test_relay($this->input->post('email'));
                $this->page->set_message(lang('network_visualiser_test_success'), 'info');
                redirect('/network_visualiser');
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e));
            }
        }

        $this->page->view_form('network_visualiser/test', $data, lang('network_visualiser_test'));
    }
}
