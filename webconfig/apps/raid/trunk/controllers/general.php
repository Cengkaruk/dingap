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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\raid\Raid as Raid_Class;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Raid general settings controller.
 *
 * @category   Apps
 * @package    Raid
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/raid/
 */

class General extends ClearOS_Controller
{

    /**
     * Raid default controller
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit();
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

    function _view_edit($mode = 'view')
    {
        // Load dependencies
        //------------------

        $this->load->library('raid/Raid');
        $this->lang->load('raid');

        $data['mode'] = $mode;

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('monitor', 'raid/Raid', 'validate_monitor', TRUE);
        $this->form_validation->set_policy('notify', 'raid/Raid', 'validate_notify', TRUE);
        $this->form_validation->set_policy('email', 'raid/Raid', 'validate_email', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->raid->set_monitor($this->input->post('monitor'));
                $this->raid->set_notify($this->input->post('notify'));
                $this->raid->set_email($this->input->post('email'));
                $this->page->set_status_updated();
                redirect('/raid');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $type = $this->raid->get_type_details();
            $data['type'] = $type;
            $data['monitor'] = $this->raid->get_monitor();
            $data['notify'] = $this->raid->get_notify();
            $data['email'] = $this->raid->get_email();
            $data['is_supported'] = TRUE;
            if ($type['id'] != Raid_Class::TYPE_UNKNOWN)
                $data['is_supported'] = TRUE;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('general', $data, lang('raid_general_settings'));
    }

}
