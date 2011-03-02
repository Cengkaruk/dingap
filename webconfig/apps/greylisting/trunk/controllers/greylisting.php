<?php

/**
 * Greylisting controller.
 *
 * @category   Apps
 * @package    Greylisting
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/greylisting/
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
 * Greylisting controller.
 *
 * @category   Apps
 * @package    Greylisting
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/greylisting/
 */

class Greylisting extends ClearOS_Controller
{
    /**
     * Greylisting default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('greylisting/Postgrey');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('delay', 'greylisting/Postgrey', 'validate_delay');
        $this->form_validation->set_policy('retention', 'greylisting/Postgrey', 'validate_retention_time');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->postgrey->set_delay($this->input->post('delay'));
                $this->postgrey->set_retention_time($this->input->post('retention_time'));
                $this->postgrey->reset();

                $this->page->set_success(lang('base_system_updated'));
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['delay'] = $this->postgrey->get_delay();
            $data['retention_time'] = $this->postgrey->get_retention_time();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->set_title(lang('greylisting_greylisting'));

        $this->load->view('theme/header');
        $this->load->view('greylisting', $data);
        $this->load->view('theme/footer');
    }
}
