<?php

/**
 * Intrusion prevention white list controller.
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
 * Intrusion prevention white list controller.
 *
 * @category   Apps
 * @package    Intrusion_Prevention
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_prevention/
 */

class White_List extends ClearOS_Controller
{
    /**
     * White list default controller
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
            $data['white_list'] =  $this->snortsam->get_whitelist();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('white_list', $data, lang('intrusion_prevention_white_list'));
    }

    /**
     * Add DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function add($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('intrusion_prevention/SnortSam');
        $this->lang->load('intrusion_prevention');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('ip', 'intrusion_prevention/SnortSam', 'validate_whitelist_ip', TRUE, TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            try {
                $ip = $this->input->post('ip');

                $this->snortsam->add_whitelist_ip($ip);
                $this->snortsam->reset(TRUE);

                // Return to summary page with status message
                $this->page->set_status_added();
                redirect('/intrusion_prevention');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the views
        //---------------

        $this->page->view_form('add_edit', array(), lang('intrusion_prevention_white_list'));
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
        $confirm_uri = '/app/intrusion_prevention/white_list/destroy/' . $ip;
        $cancel_uri = '/app/intrusion_prevention/white_list';
        $items = array($ip);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys DNS entry view.
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
            $this->snortsam->delete_whitelist_ip($ip);

            $this->page->set_status_deleted();
            redirect('/intrusion_prevention');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
