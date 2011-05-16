<?php

/**
 * Accounts info controller.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
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
 * Accounts info controller.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
 */

class Status extends ClearOS_Controller
{
    /**
     * Extensions default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->factory('accounts/Accounts_Factory');
        $this->lang->load('base');

        // Load view data
        //---------------

        try {
            $is_initialized = $this->accounts->is_initialized();
            $is_available = $this->accounts->is_available();
        } catch (Exception $e) {
            $data['code'] = 1;
            $data['error_message'] = clearos_exception_message($e);
        }

        // Load views
        //-----------

        if (!$is_initialized || !$is_available)
            $this->page->view_form('accounts/status', $data, lang('base_status'));
    }

    /**
     * Returns accounts information. 
     */

    function get_info()
    {
        // Load dependencies
        //------------------

        $this->load->factory('accounts/Accounts_Factory');

        // Load view data
        //---------------

        try {
            $data['is_initialized'] = $this->accounts->is_initialized();
            $data['is_available'] = $this->accounts->is_available();
//            $data['is_initialized'] = FALSE;
        } catch (Exception $e) {
            $data['code'] = 1;
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
