<?php

/**
 * System applications cloud controller.
 *
 * @category   Apps
 * @package    System_Services
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/system_services/
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
 * System applications cloud controller.
 *
 * @category   Apps
 * @package    System_Services
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/system_services/
 */

class Cloud extends ClearOS_Controller
{
    /**
     * Cloud default controller
     *
     * @return view
     */

    function index($mode = 'edit')
    {
        // Load libraries
        //---------------

        $this->lang->load('system_services');
/*
        $this->load->library('smtp/Postfix');

        // Set validation rules
        //---------------------
        $this->form_validation->set_policy('timezone', 'date/Time', 'validate_time_zone', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->page->set_status_updated();
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['mode'] = $mode;
            $data['smtp_authentication'] = $this->postfix->get_smtp_authentication_state();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
*/

        // Load views
        //-----------

        $this->page->view_form('system_services/cloud', $data, lang('system_services_cloud_servies'));
    }
}
