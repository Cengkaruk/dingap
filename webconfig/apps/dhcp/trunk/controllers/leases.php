<?php

/**
 * DHCP leases controller.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
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
 * DHCP leases controller.
 *
 * @category   Apps
 * @package    DHCP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dhcp/
 */

class Leases extends ClearOS_Controller
{
	/**
	 * DHCP server overview.
	 */

	function index($view = 'page')
	{
		// Load libraries
		//---------------

		$this->load->library('dhcp/Dnsmasq');
		$this->lang->load('dhcp');

		// Load view data
		//---------------

		try {
			$data['leases'] = $this->dnsmasq->get_leases();
		} catch (Exception $e) {
			$this->page->view_exception($e);
			return;
		}
 
		// Load views
		//-----------

        $this->page->view_form('dhcp/leases/summary', $data);
	}

	/**
	 * Edit lease view.
	 *
	 * @return string
	 */

	function edit($mac, $ip)
	{
		// Load libraries
		//---------------

		$this->load->library('dhcp/Dnsmasq');
		$this->lang->load('dhcp');

		// Set validation rules
		//---------------------

		$this->form_validation->set_policy('ip', 'dhcp/Dnsmasq', 'validate_ip', TRUE);
		$this->form_validation->set_policy('mac', 'dhcp/Dnsmasq', 'validate_mac', TRUE);
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('update_static') && ($form_ok === TRUE)) {
			try {
                $this->dnsmasq->add_static_lease(
                    $this->input->post('ip'),
                    $this->input->post('mac')
                );

				$this->dnsmasq->reset(TRUE);

				// Return to summary page with status message
                $this->page->set_status_updated();
			} catch (Exception $e) {
				$this->page->view_exception($e);
				return;
			}

        } else if ($this->input->post('submit') && ($form_ok === TRUE)) {
			try {
                $this->dnsmasq->add_static_lease(
                    $this->input->post('ip'),
                    $this->input->post('mac')
                );

				$this->dnsmasq->reset(TRUE);

				// Return to summary page with status message
                $this->page->set_status_updated();
                redirect('/dhcp/leases');
			} catch (Exception $e) {
				$this->page->view_exception($e);
				return;
			}
		}

        // Load view data
        //---------------

        try {
            $data['lease'] = $this->dnsmasq->get_lease($mac, $ip);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('dhcp/leases/edit', $data, lang('base_edit'));
	}

	/**
	 * DHCP server delete subnet view.
	 *
	 * @param string $iface   interface
     * @param string $network network
     *
	 * @return view
	 */

	function delete($iface, $network)
	{
        $confirm_uri = '/app/dhcp/leases/destroy/' . $iface;
        $cancel_uri = '/app/dhcp/leases';
        $items = array($iface . ' - ' . $network);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
	}

	/**
	 * Destroys DHCP server subnet.
	 *
	 * @return view
	 */

	function destroy($iface)
	{
		try {
            $this->load->library('dhcp/Dnsmasq');
    
			$this->dnsmasq->delete_subnet($iface);

			$this->page->set_status_deleted();
		    redirect('/dhcp/leases');
		} catch (Exception $e) {
			$this->page->view_exception($e);
			return;
		}
	}

	/**
	 * Converts a dynamic lease to a static lease.
	 *
	 * @return string
	 */

	function to_static($mac, $ip)
	{
		// Load libraries
		//---------------

		$this->load->library('dhcp/Dnsmasq');
		$this->lang->load('dhcp');

		// Set validation rules
		//---------------------

		$this->form_validation->set_policy('dynamic_ip', 'dhcp/Dnsmasq', 'validate_ip', TRUE);
		$this->form_validation->set_policy('dynamic_mac', 'dhcp/Dnsmasq', 'validate_mac', TRUE);
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('submit') && ($form_ok === TRUE)) {
			try {
                $this->dnsmasq->add_static_lease(
                    $this->input->post('dynamic_ip'),
                    $this->input->post('dynamic_mac')
                );

				$this->dnsmasq->reset(TRUE);

				// Return to summary page with status message
                $this->page->set_status_updated();
			} catch (Exception $e) {
				$this->page->view_exception($e);
				return;
			}
		}

        redirect('/dhcp/leases');
	}
}
