<?php

/**
 * Intrusion prevention blocked list controller.
 *
 * @category   Apps
 * @package    Intrusion_Prevention
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_prevention/
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
 * Intrusion prevention blocked list controller.
 *
 * @category   Apps
 * @package    Intrusion_Prevention
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_prevention/
 */

class Blocked_List extends ClearOS_Controller
{
    /**
     * Blocked list default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('intrusion_prevention/SnortSam');
        $this->lang->load('intrusion_prevention');

        // Load view data
        //---------------

        try {
            $data['blocked'] =  $this->snortsam->get_block_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('blocked_list', $data, lang('intrusion_prevention_blocked_list'));
    }

    /**
     * Delete entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function delete($ip = NULL)
    {
        $confirm_uri = '/app/intrusion_prevention/blocked_list/destroy/' . $ip;
        $cancel_uri = '/app/intrusion_prevention/blocked_list';
        $items = array($ip);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys entry.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function destroy($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('intrusion_prevention/SnortSam');
        $this->lang->load('intrusion_prevention');

        // Handle delete
        //--------------

        try {
            $this->snortsam->delete_blocked_ip($ip);
            $this->snortsam->reset($ip);

            $this->page->set_status_deleted();
            redirect('/intrusion_prevention');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Adds IP to white list.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function exempt($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('intrusion_prevention/SnortSam');
        $this->lang->load('intrusion_prevention');

        // Handle delete
        //--------------

        try {
            $this->snortsam->delete_blocked_ip($ip);
        } catch (Exception $e) {
            // Quirky
        }

        try {
            $this->snortsam->add_whitelist_ip($ip);
            $this->snortsam->reset($ip);

            $this->page->set_status_updated();
            redirect('/intrusion_prevention');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
