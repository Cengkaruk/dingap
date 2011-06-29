<?php

/**
 * Shutdown and restart controller.
 *
 * @category   Apps
 * @package    Shutdown
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/shutdown/
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
 * Shutdown and restart controller.
 *
 * @category   Apps
 * @package    Shutdown
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/shutdown/
 */

class Shutdown extends ClearOS_Controller
{
    /**
     * Shutdown and restart default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('shutdown');

        // Load views
        //-----------

        $this->page->view_form('shutdown', array(), lang('shutdown_app_name'));
    }

    /**
     * Shutdown view confirm view.
     *
     * @return view
     */

    function confirm_shutdown($confirm = '')
    {
        // Load dependencies
        //------------------

        $this->load->library('shutdown/System');
        $this->lang->load('shutdown');

        // Handle action
        //--------------

        if (empty($confirm)) {
            $confirm_uri = '/app/shutdown/confirm_shutdown/confirmed';
            $cancel_uri = '/app/shutdown';
            $items = array();

            $this->page->view_confirm(lang('shutdown_confirm_shutdown'), $confirm_uri, $cancel_uri, $items);
        } else {
            $this->system->shutdown(); 
            $data['action'] = 'shutdown';

            $this->page->view_form('shutdown', $data, lang('shutdown_app_name'));
        }
    }

    /**
     * Restart view confirm view.
     *
     * @return view
     */

    function confirm_restart($confirm = '')
    {
        // Load dependencies
        //------------------

        $this->load->library('shutdown/System');
        $this->lang->load('shutdown');

        // Handle action
        //--------------

        if (empty($confirm)) {
            $confirm_uri = '/app/shutdown/confirm_restart/confirmed';
            $cancel_uri = '/app/shutdown';
            $items = array();

            $this->page->view_confirm(lang('shutdown_confirm_restart'), $confirm_uri, $cancel_uri, $items);
        } else {
            $this->system->restart(); 
            $data['action'] = 'restart';

            $this->page->view_form('shutdown', $data, lang('shutdown_app_name'));
        }
    }
}
