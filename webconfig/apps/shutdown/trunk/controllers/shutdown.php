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

        $this->load->library('shutdown/System');
        $this->lang->load('shutdown');

        // Handle form submit
        //-------------------

        $data = array();

        if ($this->input->post('shutdown') || $this->input->post('restart')) {
            try {
                if ($this->input->post('shutdown')) {
                    $this->system->shutdown();
                    $data['action'] = 'shutdown';
                } else {
                    $this->system->restart();
                    $data['action'] = 'restart';
                }

                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load views
        //-----------

        $this->page->view_form('shutdown', $data, lang('shutdown_shutdown_and_restart'));
    }
}
