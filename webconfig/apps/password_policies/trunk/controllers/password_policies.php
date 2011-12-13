<?php

/**
 * Password policies controller.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
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
 * Password policies controller.
 *
 * @category   Apps
 * @package    Password_Policies
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/password_policies/
 */

class Password_Policies extends ClearOS_Controller
{
    /**
     * Password policies default controller.
     *
     * @return view
     */

    function index()
    {
        $this->view();
    }

    /**
     * Antiphishing edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * Antiphishing view view.
     *
     * @return view
     */

    function view()
    {
        $this->_view_edit('view');
    }

    /**
     * Antiphishing default controller
     *
     * @param string $form_mode form mode
     *
     * @return view
     */

    function _view_edit($form_mode)
    {
        // Load libraries
        //---------------

        $this->lang->load('password_policies');
        $this->load->library('password_policies/Password_Policies');

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

                $this->clamav->reset(TRUE);

                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_mode'] = $form_mode;
            $policy = $this->password_policies->get_default_policy();
/*
echo field_simple_dropdown('minimum_length', $minimum_lengths, $minimum_length, lang('password_policies_minimum_password_length'), $read_only);
echo field_dropdown('minimum_age', $minimum_ages, $minimum_age, lang('password_policies_minimum_password_age'), $read_only);
echo field_dropdown('maximum_age', $maximum_ages, $maximum_age, lang('password_policies_maximum_password_age'), $read_only);
echo field_dropdown('history_size', $history_sizes, $history_size, lang('password_policies_history_size'), $read_only);
echo field_toggle_enable_disable('lockout', $lockout, lang('password_policies_account_lockout'), $read_only);
*/
print_r($policy);


        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('password_policies/settings', $data, lang('antiphishing_app_name'));
    }
}
