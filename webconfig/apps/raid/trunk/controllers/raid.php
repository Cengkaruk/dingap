<?php

/**
 * Raid controller.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
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
 * Raid controller.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

class Raid extends ClearOS_Controller
{

    /**
     * Raid default controller
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * Raid edit controller
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

        $this->load->library('raid/Raid');
        $this->lang->load('raid');

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('server_name', 'raid/Raid', 'validate_server_name', TRUE);
        $this->form_validation->set_policy('max_instances', 'raid/Raid', 'validate_max_instances', TRUE);
        $this->form_validation->set_policy('port', 'raid/Raid', 'validate_port', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
   //             $this->raid->set_server_name($this->input->post('server_name'));
    //            $this->raid->set_max_instances($this->input->post('max_instances'));
     //           $this->raid->set_port($this->input->post('port'));
      //          $this->page->set_status_updated();
                redirect('/raid');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['type'] = $this->raid->get_type_details();
            $data['monitor'] = $this->raid->get_monitor_status();
            $data['notify'] = $this->raid->get_notify();
            $data['email'] = $this->raid->get_email();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('overview', $data, lang('raid_overview'));
    }

}
