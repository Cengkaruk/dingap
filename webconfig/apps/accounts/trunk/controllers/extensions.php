<?php

/**
 * Accounts extensions controller.
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
 * Accounts extensions controller.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
 */

class Extensions extends ClearOS_Controller
{
    /**
     * Extensions default controller
     *
     * @return view
     */

    function index()
    {
        // Bail if accounts have not been configured
        //------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy())
            return;

        // Load dependencies
        //------------------

        $this->load->factory('accounts/Accounts_Factory');
        $this->lang->load('accounts');

        // Load view data
        //---------------

        try {
            $data['extensions'] = $this->accounts->get_extensions();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('extensions', $data, lang('accounts_extensions'));
    }

    /** 
     * Extension view
     *
     * @param string $extension extension
     *
     * @return view
     */

    function view($extension)
    {
        // Load dependencies
        //------------------

        $this->lang->load('accounts');
        $this->load->library('base/Software', 'app-' . $extension . '-extension-core');

        // Load view data
        //---------------

        try {
            $data['description'] = $this->software->get_description();
            $data['summary'] = $this->software->get_summary();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('extension', $data, lang('accounts_extension'));
    }
}
