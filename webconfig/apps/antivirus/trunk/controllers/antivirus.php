<?php

/**
 * Antivirus controller.
 *
 * @category   Apps
 * @package    Antimalware
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antimalware/
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
 * Antivirus controller.
 *
 * @category   Apps
 * @package    Antimalware
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antimalware/
 */

class Antivirus extends ClearOS_Controller
{
    /**
     * Antivirus default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('antimalware/ClamAV');
        $this->load->library('antimalware/Freshclam');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('checks', 'antimalware/Freshclam', 'validate_checks_per_day', TRUE);
        $this->form_validation->set_policy('max_files', 'antimalware/ClamAV', 'validate_max_files', TRUE);
        $this->form_validation->set_policy('max_file_size', 'antimalware/ClamAV', 'validate_max_file_size', TRUE);
        $this->form_validation->set_policy('max_recursion', 'antimalware/ClamAV', 'validate_max_recursion', TRUE);
        $this->form_validation->set_policy('block_encrypted', 'antimalware/ClamAV', 'validate_block_encrypted', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->freshclam->set_checks_per_day($this->input->post('checks'));
                $this->clamav->set_max_files($this->input->post('max_files'));
                $this->clamav->set_max_file_size($this->input->post('max_file_size'));
                $this->clamav->set_max_recursion($this->input->post('max_recursion'));
                $this->clamav->set_block_encrypted($this->input->post('block_encrypted'));

                $this->freshclam->reset();
                $this->clamav->reset();

                $this->page->set_success(lang('base_system_updated'));
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['checks'] = $this->freshclam->get_checks_per_day();
            $data['max_files'] = $this->clamav->get_max_files();
            $data['max_file_size'] = $this->clamav->get_max_file_size();
            $data['max_recursion'] = $this->clamav->get_max_recursion();
            $data['block_encrypted'] = $this->clamav->get_block_encrypted();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->set_title(lang('antimalware_antivirus'));

        $this->load->view('theme/header');
        $this->load->view('antivirus', $data);
        $this->load->view('theme/footer');
    }
}
