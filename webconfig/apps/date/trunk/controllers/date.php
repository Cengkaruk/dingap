<?php

/**
 * Date controller.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
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
 * Date controller.
 *
 * @category   Apps
 * @package    Date
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
 */

class Date extends ClearOS_Controller
{
    /**
     * Date default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('date/Time');
        $this->lang->load('date');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('timezone', 'date/Time', 'validate_time_zone', TRUE);
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

        try {
            $data['date'] = strftime("%b %e %Y");
            $data['time'] = strftime("%T %Z");
            $data['timezone'] = $this->time->get_time_zone();
            $data['timezones'] = $this->time->get_time_zone_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('date', $data);
    }

    /**
     * Runs a network time synchronization event.
     *
     * @return string offset time
     */

    function sync()
    {
        // Load libraries
        //---------------

        $this->load->library('date/NTP_Time');

        // Run synchronize
        //----------------

        try {
            $diff = $this->ntp_time->synchronize();
        } catch (Exception $e) {
            // FIXME: discuss standard for Ajax errors
            // e.g. $this->page->view_rest_exception($e);
            echo "Ooops: " . $e->getMessage();
            return;
        }

        // Return status message
        //----------------------

        // FIXME: discuss standard for Ajax data - JSON?
        echo "offset: $diff\n"; // FIXME: localize
    }
}
