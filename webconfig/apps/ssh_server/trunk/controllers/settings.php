<?php

/**
 * OpenSSH server settings controller.
 *
 * @category   Apps
 * @package    OpenSSH
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ssh_server/
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

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenSSH server settings controller.
 *
 * @category   Apps
 * @package    OpenSSH
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ssh_server/
 */

class Settings extends ClearOS_Controller
{
    /**
     * NTP settings controller.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');
        $this->load->library('ssh_server/OpenSSH');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('port', 'ssh_server/OpenSSH', 'validate_port', TRUE);
        $this->form_validation->set_policy('permit_root_login', 'ssh_server/OpenSSH', 'validate_permit_root_login_policy', TRUE);
        $this->form_validation->set_policy('password_authentication', 'ssh_server/OpenSSH', 'validate_password_authentication_policy', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            try {
                $this->openssh->set_port($this->input->post('port'));
                $this->openssh->set_permit_root_login_policy($this->input->post('permit_root_login'));
                $this->openssh->set_password_authentication_policy($this->input->post('password_authentication'));

                $this->openssh->reset(TRUE);
                $this->page->set_status_updated();
                redirect('/ssh_server');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['port'] = $this->openssh->get_port();
            $data['password_authentication'] = $this->openssh->get_password_authentication_policy();
            $data['permit_root_login'] = $this->openssh->get_permit_root_login_policy();
            $data['permit_root_logins'] = $this->openssh->get_permit_root_login_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('ssh_server/settings', $data, lang('base_settings'));
    }
}
