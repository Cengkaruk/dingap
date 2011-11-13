<?php

/**
 * Directory server security controller.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
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
 * Directory_server security controller.
 *
 * We are actually initializing two layers here:
 * - The base LDAP layer using the (basically, an empty LDAP directory)
 * - The base accounts layer (all things related to user accounts)
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Security extends ClearOS_Controller
{
    /**
     * Directory server security controller
     *
     * @return view
     */

    function index()
    {
        $this->_item('view');
    }

    function edit()
    {
        $this->_item('edit');
    }

    function _item($form_type)
    {
        // Load dependencies
        //------------------

        $this->lang->load('openldap_directory');
        $this->load->library('openldap/LDAP_Driver');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('policy', 'openldap/LDAP_Driver', 'validate_security_policy', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->ldap_driver->set_security_policy($this->input->post('policy'));
                $this->ldap_driver->reset(TRUE);

                $this->page->set_status_updated();
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['policies'] = $this->ldap_driver->get_security_policies();
            $data['policy'] = $this->ldap_driver->get_security_policy();
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('security', $data, lang('openldap_directory_app_name'));
    }
}
