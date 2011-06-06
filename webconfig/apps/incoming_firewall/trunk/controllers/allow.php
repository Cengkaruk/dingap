<?php

/**
 * Firewall incoming controller.
 *
 * @category   Apps
 * @package    System_Firewall
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
 * Firewall incoming controller.
 *
 * @category   Apps
 * @package    System_Firewall
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/incoming_firewall/
 */

class Allow extends ClearOS_Controller
{
	/**
	 * Incoming overview.
	 */

    function index()
	{
		$this->load->library('incoming_firewall/Incoming');
		$this->lang->load('incoming_firewall');

		// Load view data
		//---------------

		try {
			$data['ports'] = $this->incoming->get_allow_ports();
			$data['ranges'] = $this->incoming->get_allow_port_ranges();
			$data['ipsec'] = $this->incoming->get_ipsec_server_state();
			$data['pptp'] = $this->incoming->get_pptp_server_state();
		} catch (Exception $e) {
			$this->page->view_exception($e);
			return;
		}
 
		// Load views
		//-----------

        $this->page->view_form('incoming_firewall/allow/summary', $data, lang('incoming_firewall_allow_incoming'));
	}

	/**
	 * Add allow rule.
	 */

	function add()
	{
		$this->_add_edit('add');
	}

	/**
	 * Delete rule.
     *
     * @param string $network network 
     *
     * @return view
	 */

	function delete($network)
	{
		$this->lang->load('smtp');

		$data['items'] = $network;
		$data['ok_anchor'] = '/app/smtp/trusted/destroy/' . $network;
		$data['cancel_anchor'] = '/app/smtp/trusted';

        $this->page->view_form('theme/confirm_delete', lang('smtp_trusted_networks'), $data);
	}

	/**
	 * Delete IPsec rule.
     *
     * @return view
	 */

	function delete_ipsec()
	{
        $confirm_uri = '/app/incoming_firewall/allow/destroy_ipsec';
        $cancel_uri = '/app/incoming_firewall/allow';
        $items = array('IPsec');

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

	/**
	 * Destroys trusted network.
     *
     * @param string $network network 
     *
     * @return view
	 */

	function destroy($network)
	{
		// Load libraries
		//---------------

		$this->load->library('smtp/Postfix');

		// Handle form submit
		//-------------------

		try {
			$this->postfix->delete_trusted_network($network);
            $this->postfix->reset();

			$this->page->set_status_deleted();
            redirect('/smtp/trusted');
		} catch (Exception $e) {
			$this->page->view_exception($e);
			return;
		}
	}

	/**
	 * Destroys IPsec rule.
     *
     * @return view
	 */

	function destroy_ipsec()
	{
		// Load libraries
		//---------------

		$this->load->library('incoming_firewall/Incoming');

		// Handle form submit
		//-------------------

		try {
			$this->incoming->set_ipsec_server_state(FALSE);
            $this->incoming->reset();

			$this->page->set_status_deleted();
            redirect('/incoming_firewall/allow');
		} catch (Exception $e) {
			$this->page->view_exception($e);
			return;
		}
    }

	/**
	 * Edit trusted network.
     *
     * @param string $network network 
     *
     * @return view
	 */

	function edit($network)
	{
		$this->_add_edit($network, 'edit');
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Trusted network common add/edit form handler.
     *
     * @param string $form_mode form mode
     *
     * @return view
	 */

	function _add_edit($form_mode)
	{
		// Load libraries
		//---------------

		$this->load->library('incoming_firewall/Incoming');
		$this->lang->load('incoming_firewall');
		$this->lang->load('base');

		// Set validation rules
		//---------------------
/*

        $this->form_validation->set_policy('network', 'smtp/Postfix', 'validate_trusted_network', TRUE);
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('submit') && ($form_ok === TRUE)) {
			try {
				$this->postfix->add_trusted_network($this->input->post('network'));
				$this->postfix->reset();

				$this->page->set_status_added();
				redirect('/smtp/trusted');
			} catch (Exception $e) {
				$this->page->view_exception($e);
				return;
			}
		}
*/

		// Load the view data 
		//------------------- 

		$data['mode'] = $form_mode;
        $data['services'] = $this->incoming->get_standard_service_list();
        $data['protocols'] = $this->incoming->get_basic_protocols();
 
		// Load the views
		//---------------

        $this->page->view_form('incoming_firewall/allow/add_edit', $data, lang('base_edit'));
	}
}
