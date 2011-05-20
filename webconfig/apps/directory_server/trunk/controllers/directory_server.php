<?php

/**
 * Directory server controller.
 *
 * @category   Apps
 * @package    Directory_Server
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_server/
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
 * Date controller.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
 */

class Directory_Server extends ClearOS_Controller
{
    /**
     * Directory server default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('openldap/LDAP_Driver');

/*
        $this->load->factory('ldap/LDAP_Factory');
        $this->load->library('openldap_directory/OpenLDAP');
        // $this->lang->load('ldap');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('mode', 'openldap/LDAP_Driver', 'validate_mode', TRUE);
        $form_ok = $this->form_validation->run();

        if ($form_ok) {
            if ($this->input->post('mode') === LDAP::MODE_MASTER) {
                $this->form_validation->set_policy('master_domain', 'openldap/LDAP_Driver', 'validate_domain', TRUE);
            } else if ($this->input->post('mode') === LDAP::MODE_STANDALONE) {
                $this->form_validation->set_policy('standalone_domain', 'openldap/LDAP_Driver', 'validate_domain', TRUE);
            } else if ($this->input->post('mode') === LDAP::MODE_SLAVE) {
            }
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
        //        $this->openldap->run_initialize(

                $this->openldap->initialize_master($this->input->post('master_domain'), 'subway', TRUE);
                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------
*/

        try {
            $data['domain'] = $this->ldap_driver->get_base_internet_domain();
            $data['available'] = $this->ldap_driver->is_available();
            $data['initialized'] = ($reset) ? FALSE : $this->ldap_driver->is_initialized();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('directory_server', $data, lang('directory_server_directory_server'));
    }

    /**
     * Returns directory information. 
     */

    function get_info()
    {
        // Load dependencies
        //------------------

        $this->load->library('openldap_directory/OpenLDAP');

        // Load view data
        //---------------

        try {
            $data['base_dn'] = $this->openldap->get_base_dn();
// FIXME
//            $data['bind_dn'] = $this->ldap_driver->get_bind_dn();
//            $data['bind_password'] = $this->ldap_driver->get_bind_password();
        } catch (Exception $e) {
            $data['code'] = 1;
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
