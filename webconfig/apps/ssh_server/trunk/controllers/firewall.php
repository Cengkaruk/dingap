<?php

/**
 * OpenSSH server firewall controller.
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
 * OpenSSH server firewall controller.
 *
 * @category   Apps
 * @package    OpenSSH
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/ssh_server/
 */

class Firewall extends ClearOS_Controller
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
        $this->load->library('network/Network');
        $this->load->library('ssh_server/OpenSSH');
//  FIXME: make this options
        $this->load->library('incoming_firewall/Port');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('port', 'ssh_server/OpenSSH', 'validate_port', TRUE);
        $this->form_validation->set_policy('permit_root_login', 'ssh_server/OpenSSH', 'validate_permit_root_login_policy', TRUE);
        $this->form_validation->set_policy('password_authentication', 'ssh_server/OpenSSH', 'validate_password_authentication_policy', TRUE);

        $form_ok = $this->form_validation->run();

        // Load view data
        //---------------

        try {
            $data['port']= $this->openssh->get_port();
            $data['is_firewalled'] = $this->port->is_firewalled('TCP', $data['port']);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('ssh_server/firewall', $data, lang('ssh_server_firewall'));
    }
}
