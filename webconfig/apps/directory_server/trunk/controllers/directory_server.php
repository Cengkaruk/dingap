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

use \clearos\apps\ldap\LDAP_Engine as LDAP_Engine;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Directory_server controller.
 *
 * We are actually initializing two layers here:
 * - The base LDAP layer using the (basically, an empty LDAP directory)
 * - The base accounts layer (all things related to user accounts)
 *
 * @category   Apps
 * @package    Directory_Server
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_server/
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

        $this->lang->load('directory_server');
        $this->load->factory('ldap/LDAP_Factory');
        $this->load->library('openldap_directory/Accounts_Driver');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('domain', 'openldap/LDAP_Driver', 'validate_domain', TRUE);
        $this->form_validation->set_policy('policy', 'openldap/LDAP_Driver', 'validate_security_policy', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                // Don't set the domain in slave mode
/*
                $mode = $this->ldap->get_mode();

                if ($mode !== LDAP_Engine::MODE_SLAVE)
                    $this->ldap->set_domain($this->input->post('domain'), TRUE);
*/

                $this->ldap->set_security_policy($this->input->post('policy'));

                $this->ldap->reset(TRUE);

                $this->page->set_status_updated();
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['policies'] = $this->ldap->get_security_policies();
            $data['policy'] = $this->ldap->get_security_policy();
            $data['domain'] = $this->ldap->get_base_internet_domain();
            $data['mode'] = $this->ldap->get_mode();
            $data['status'] = $this->accounts_driver->get_driver_status();
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('directory_server', $data, lang('directory_server_app_name'));
    }

    /**
     * Updates domains
     */

    function update_domain($domain)
    {
        // Load dependencies
        //------------------

        $this->load->factory('ldap/LDAP_Factory');

        // Handle form submit
        //-------------------

        try {
            $this->ldap->set_domain($domain, TRUE);
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Returns directory information. 
     */

    function get_info()
    {
        // Load dependencies
        //------------------

        $this->load->library('openldap_directory/Accounts_Driver');
        $this->load->library('openldap_directory/OpenLDAP');
        $this->load->library('openldap/LDAP_Driver');

        // Load view data
        //---------------

        try {
            // Low level LDAP information
            $data['mode_text'] = $this->ldap_driver->get_mode_text();
            $data['base_dn'] = $this->ldap_driver->get_base_dn();
            $data['bind_dn'] = $this->ldap_driver->get_bind_dn();
            $data['bind_password'] = $this->ldap_driver->get_bind_password();

            // Account driver information
            $data['accounts_status'] = $this->accounts_driver->get_driver_status();

            // Account information
            $data['computers_container'] = $this->openldap->get_computers_container();
            $data['groups_container'] = $this->openldap->get_groups_container();
            $data['users_container'] = $this->openldap->get_users_container();
        } catch (Engine_Exception $e) {
            $data['code'] = 1;
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
