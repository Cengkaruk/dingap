<?php

/**
 * Directory server information controller.
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
 * Directory_server information controller.
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

class Information extends ClearOS_Controller
{
    /**
     * Directory server information controller
     *
     * @return view
     */

    function index()
    {
        // Load views
        //-----------

        $this->page->view_form('information', $data, lang('base_information'));
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
            $data['ldap_status'] = $this->ldap_driver->get_system_status();

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
