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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\accounts\Accounts_Engine as Accounts;

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

    function index($always_show = FALSE)
    {
        // Load dependencies
        //------------------

        $this->load->factory('accounts/Accounts_Factory');
        $this->lang->load('base');

        // Load view data
        //---------------

        try {
            $status = $this->accounts->get_system_status();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if (($status !== Accounts::STATUS_ONLINE) || $always_show)
            $this->page->view_form('accounts/status', array(), lang('base_status'));
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
            $data['status'] = $this->accounts->get_system_status();
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
