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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\password_policies\Password_Policies as Policies;

use \Exception as Exception;

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
     * Edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * View view.
     *
     * @return view
     */

    function view()
    {
        $this->_view_edit('view');
    }

    /**
     * Common edit/view view.
     *
     * @param string $form_mode form mode
     *
     * @return view
     */

    function _view_edit($form_mode)
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget('users');
            return;
        }

        // Load libraries
        //---------------

        $this->lang->load('base');
        $this->lang->load('password_policies');
        $this->load->library('password_policies/Password_Policies');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $settings['maximum_age'] = $this->input->post('maximum_age');
                $settings['minimum_age'] = $this->input->post('minimum_age');
                $settings['minimum_length'] = $this->input->post('minimum_length');
                $settings['history_size'] = $this->input->post('history_size');
                $settings['bad_password_lockout'] = $this->input->post('lockout');

                $this->password_policies->set_default_policy($settings);

                $this->page->set_status_updated();
                // redirect...
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $policy = $this->password_policies->get_default_policy();

            $data['form_mode'] = $form_mode;
            $data['maximum_age'] = $policy['maximum_age'];
            $data['minimum_age'] = $policy['minimum_age'];
            $data['minimum_length'] = $policy['minimum_length'];
            $data['history_size'] = $policy['history_size'];
            $data['lockout'] = $policy['bad_password_lockout'];

            $data['maximum_ages'] = array(
                86400 => 1 . " " . lang('base_day'),
                172800 => 2 . " " . lang('base_days'),
                259200 => 3 . " " . lang('base_days'),
                345600 => 4 . " " . lang('base_days'),
                432000 => 5 . " " . lang('base_days'),
                864000 => 10 . " " . lang('base_days'),
                1728000 => 20 . " " . lang('base_days'),
                2592000 => 30 . " " . lang('base_days'),
                5184000 => 60 . " " . lang('base_days'),
                7776000 => 90 . " " . lang('base_days'),
                10368000 => 120 . " " . lang('base_days'),
                12960000 => 150 . " " . lang('base_days'),
                15552000 => 180 . " " . lang('base_days'),
                31536000 => 1 . " " . lang('base_year'),
                63072000 => 2 . " " . lang('base_years'),
                94608000 => 3 . " " . lang('base_years'),
                Policies::CONSTANT_NO_EXPIRE => lang('password_policies_no_expire'),
            );

            $data['minimum_ages'] = array(
                Policies::CONSTANT_MODIFY_ANY_TIME => lang('password_policies_modify_any_time'),
                86400 => 1 . ' ' . lang('base_day'),
                172800 => 2 . ' ' . lang('base_days'),
                259200 => 3 . ' ' . lang('base_days'),
                345600 => 4 . ' ' . lang('base_days'),
                432000 => 5 . ' ' . lang('base_days'),
                864000 => 10 . ' ' . lang('base_days'),
                1728000 => 20 . ' ' . lang('base_days'),
                2592000 => 30 . ' ' . lang('base_days'),
                5184000 => 60 . ' ' . lang('base_days'),
                7776000 => 90 . ' ' . lang('base_days'),
                10368000 => 120 . ' ' . lang('base_days'),
                12960000 => 150 . ' ' . lang('base_days'),
                15552000 => 180 . ' ' . lang('base_days'),
                31536000 => 1 . ' ' . lang('base_year'),
                63072000 => 2 . ' ' . lang('base_years'),
                94608000 => 3 . ' ' . lang('base_years'),
            );

            $data['minimum_lengths'] = array(5, 6, 7, 8, 9, 10, 11, 12, 15, 20, 25, 30, 35, 40, 45, 50);

            $data['history_sizes'] = array(
                Policies::CONSTANT_NO_HISTORY => lang('password_policies_no_history'),
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5,
                10 => 10,
                15 => 15,
                20 => 20,
                25 => 25
            );

        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('password_policies/settings', $data, lang('password_policies_app_name'));
    }
}
