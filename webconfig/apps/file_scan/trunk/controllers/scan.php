<?php

/**
 * Antivirus file scan controller.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
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
 * Antivirus file scan controller.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
 */

class Scan extends ClearOS_Controller
{
    /**
     * File scanner execution.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('file_scan/File_Scan');
        $this->lang->load('file_scan');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $requested = $this->input->post('directories');
                $presets = $this->file_scan->get_directory_resets();
                $configured = $this->file_scan->get_directories();
                $schedule_exists = $this->file_scan->scan_schedule_exists();

                // Redirect to main page
                /*
                $this->page->set_success(lang('base_system_updated'));
                redirect('/file_scan/');
                */
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['directories'] = $this->file_scan->get_directories();
            $data['presets'] = $this->file_scan->get_directory_presets();
            $data['schedule_exists'] = $this->file_scan->scan_schedule_exists();

            $schedule = $this->file_scan->get_scan_schedule();
            $data['hour'] = $schedule['hour'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('file_scan/scan', $data, lang('file_scan_antimalware') . ' - ' . lang('base_status'));
    }

    /**
     * JSON encoded scan information
     *
     * @return string JSON encoded information
     */

    function info()
    {
        // Load dependencies
        //------------------

        $this->load->library('file_scan/File_Scan');

        // Run synchronize
        //----------------

        try {
            $data = $this->file_scan->get_info();
            $data['error_code'] = 0;
        } catch (Exception $e) {
            $data['error_code'] = clearos_exception_code($e);
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
