<?php

/**
 * Antiphishing controller.
 *
 * @category   Apps
 * @package    Antiphishing
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antiphishing/
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
 * Antiphishing controller.
 *
 * @category   Apps
 * @package    Antiphishing
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antiphishing/
 */

class Antiphishing extends ClearOS_Controller
{
    /**
     * Antiphishing default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('antivirus/ClamAV');
        $this->lang->load('antiphishing');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('scan_urls', 'antivirus/ClamAV', 'validate_phishing_scan_urls_state', TRUE);
        $this->form_validation->set_policy('signatures', 'antivirus/ClamAV', 'validate_phishing_signatures_state', TRUE);
        $this->form_validation->set_policy('block_cloak', 'antivirus/ClamAV', 'validate_phishing_always_block_cloak', TRUE);
        $this->form_validation->set_policy('block_ssl_mismatch', 'antivirus/ClamAV', 'validate_phishing_always_block_ssl_mismatch', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->clamav->set_phishing_scan_urls_state($this->input->post('scan_urls'));
                $this->clamav->set_phishing_signatures_state($this->input->post('signatures'));
                $this->clamav->set_phishing_always_block_cloak($this->input->post('block_cloak'));
                $this->clamav->set_phishing_always_block_ssl_mismatch($this->input->post('block_ssl_mismatch'));

                $this->clamav->reset();

                $this->page->set_success();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['scan_urls'] = $this->clamav->get_phishing_scan_urls_state();
            $data['signatures'] = $this->clamav->get_phishing_signatures_state();
            $data['block_cloak'] = $this->clamav->get_phishing_always_block_cloak();
            $data['block_ssl_mismatch'] = $this->clamav->get_phishing_always_block_ssl_mismatch();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view('antiphishing', $data, lang('antiphishing_antiphishing'));
    }
}
