<?php

/**
 * Web access control time definition summary controller.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_access_control/
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

use \clearos\apps\web_proxy\Squid as Squid;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Time Summary controller.
 *
 * @category   Apps
 * @package    Web_Access_Control
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_access_control/
 */

class Time_Summary extends ClearOS_Controller
{

    /**
     * Web Access Control default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');

        // Load dependencies
        //------------------

        $this->lang->load('web_access_control');
        $this->lang->load('web_proxy');

        $data['time_definitions'] = $this->squid->get_time_definition_list();
        $data['day_of_week_options'] = $this->squid->get_day_of_week_options();

        $this->page->view_form('time_summary', $data, lang('web_access_control_web_access_control'));
    }

    /**
     * Delete a time definition.
     *
     * @param string $name    name of time rule
     * @param string $confirm confirm intentions to delete
     *
     * @return view
     */

    function delete($name, $confirm = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');

        // Load dependencies
        //------------------

        $this->lang->load('web_access_control');
        $this->lang->load('web_proxy');
        $confirm_uri = '/app/web_access_control/time_summary/delete/' . $name . "/1";
        $cancel_uri = '/app/web_access_control';

        if ($confirm != NULL) {
            $this->squid->delete_time_definition($name);
            $this->squid->reset(TRUE);

            redirect('/web_access_control');
        }

        $items = array($name . '  (' . lang('web_access_control_time_delete_warning') . ')');

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

}
