<?php

/**
 * SMTP general settings controller.
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
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
 * SMTP general settings controller.
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
 */

class General extends ClearOS_Controller
{
    /**
     * SMTP default controller
     *
     * @return view
     */

    // FIXME: probably could lose the layout?

    function index($mode = 'edit')
    {
        // Load libraries
        //---------------

        $this->load->library('smtp/Postfix');

        // Set validation rules
        //---------------------
/*
         
        $this->form_validation->set_policy('timezone', 'date/Time', 'validate_time_zone', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->time->set_time_zone($this->input->post('timezone'));
                $this->page->set_status_updated();
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }
*/

        // Load view data
        //---------------

        try {
            $data['mode'] = $mode;
            $data['domain'] = $this->postfix->get_domain();
            $data['hostname'] = $this->postfix->get_hostname();
            $data['relay_hosts'] = $this->postfix->get_relay_hosts();
            $data['catch_all'] = $this->postfix->get_catch_all();
            $data['catch_alls'] = array('alex@example.com', 'bob@example.com', 'tim@example.com'); // FIXME - pull from LDAP
            $data['max_message_size'] = $this->postfix->get_max_message_size();
            $data['smtp_authentication'] = $this->postfix->get_smtp_authentication_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('general/view_edit', $data);
    }
}
