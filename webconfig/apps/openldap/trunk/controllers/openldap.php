<?php

/**
 * OpenLDAP manager controller.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
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
 * OpenLDAP manager controller.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class OpenLDAP extends ClearOS_Controller
{
    /**
     * OpenLDAP default controller
     *
     * @return view
     */

    function index($reset = FALSE)
    {
        // Load dependencies
        //------------------

        $this->load->factory('ldap/LDAP_Factory');
        $this->lang->load('ldap');
/*

        // Set validation rules
        //---------------------
         
        // FIXME: openldap/LDAP_Driver should not be mentioned
        $this->form_validation->set_policy('mode', 'openldap/LDAP_Driver', 'validate_mode', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->time->set_time_zone($this->input->post('timezone'));
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
            $data['mode'] = $this->ldap_factory->get_mode();
            $data['modes'] = $this->ldap_factory->get_modes();
            $data['domain'] = $this->ldap_factory->get_base_internet_domain();
            $data['master_hostname'] = $this->ldap_factory->get_master_hostname();
            $data['available'] = $this->ldap_factory->is_available();
            $data['initialized'] = ($reset) ? FALSE : $this->ldap_factory->is_initialized();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('openldap', $data, 'Mode'); // FIXME: translate
    }
}
