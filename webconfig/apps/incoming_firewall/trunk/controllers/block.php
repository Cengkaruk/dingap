<?php

/**
 * Firewall incoming block controller.
 *
 * @category   Apps
 * @package    Incoming_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/incoming_firewall/
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
 * Firewall incoming block controller.
 *
 * @category   Apps
 * @package    Incoming_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/incoming_firewall/
 */

class Block extends ClearOS_Controller
{
    /**
     * Incoming block overview.
     *
     * @return view
     */

    function index()
    {
        $this->load->library('incoming_firewall/Incoming');
        $this->lang->load('incoming_firewall');

        // Load view data
        //---------------

        try {
            $data['hosts'] = $this->incoming->get_block_hosts();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('incoming_firewall/block/summary', $data, lang('incoming_firewall_blocked_incoming_connections'));
    }

    /**
     * Add block host rule.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->load->library('incoming_firewall/Incoming');
        $this->lang->load('incoming_firewall');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('nickname', 'incoming_firewall/Incoming', 'validate_name', TRUE);
        $this->form_validation->set_policy('host', 'incoming_firewall/Incoming', 'validate_address', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->incoming->add_block_host($this->input->post('nickname'), $this->input->post('host'));
                $this->incoming->reset(TRUE);

                $this->page->set_status_added();
                redirect('/incoming_firewall/block');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        $data['mode'] = $form_mode;
 
        // Load the views
        //---------------

        $this->page->view_form('incoming_firewall/block/add', $data, lang('base_add'));
    }

    /**
     * Delete blocked host.
     *
     * @param string $host host
     *
     * @return view
     */

    function delete($host)
    {
        $confirm_uri = '/app/incoming_firewall/block/destroy/' . $host;
        $cancel_uri = '/app/incoming_firewall/block';
        $items = array($host);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys blocked host rule.
     *
     * @param string $host host
     *
     * @return view
     */

    function destroy($host)
    {
        try {
            $this->load->library('incoming_firewall/Incoming');

            $this->incoming->delete_block_host($host);
            $this->incoming->reset(TRUE);

            $this->page->set_status_deleted();
            redirect('/incoming_firewall/block');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disables blocked host rule.
     *
     * @param string $host host
     *
     * @return view
     */

    function disable($host)
    {
        try {
            $this->load->library('incoming_firewall/Incoming');

            $this->incoming->set_block_host_state(FALSE, $host);
            $this->incoming->reset(TRUE);

            $this->page->set_status_disabled();
            redirect('/incoming_firewall/block');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enables block host rule.
     *
     * @param string $host host
     *
     * @return view
     */

    function enable($host)
    {
        try {
            $this->load->library('incoming_firewall/Incoming');

            $this->incoming->set_block_host_state(TRUE, $host);
            $this->incoming->reset(TRUE);

            $this->page->set_status_enabled();
            redirect('/incoming_firewall/block');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
