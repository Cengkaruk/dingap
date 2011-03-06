<?php

/**
 * SMTP trusted networks controller.
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
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
 * SMTP trusted networks controller.
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
 */

class Trusted extends ClearOS_Controller
{
	/**
	 * trusted networks overview.
	 */

	function index($view = 'page')
	{
		// Handle theme mode redirects
		//----------------------------

		if ($view === 'page') {
			if ($this->session->userdata['theme_mode'] === 'normal')
				redirect('/smtp');
		}

		// Load libraries
		//---------------

		$this->load->library('smtp/Postfix');
		$this->lang->load('smtp');

		// Load view data
		//---------------

		try {
			$data['networks'] = $this->postfix->get_trusted_networks();
		} catch (Exception $e) {
			$this->page->view_exception($e->GetMessage(), $view);
			return;
		}
 
		// Load views
		//-----------

		if ($view == 'form') {

			$this->load->view('smtp/trusted/summary', $data);

		} else if ($view == 'page') {
			
			$this->page->set_title(lang('smtp_trusted_networks'));

			$this->load->view('theme/header');
			$this->load->view('smtp/trusted/summary', $data);
			$this->load->view('theme/footer');
		}
	}

	/**
	 * Add trusted network.
	 */

	function add($iface)
	{
		// Use common add/edit form
		$this->_addedit($iface, 'add');
	}

	/**
	 * Delete trusted network.
	 */

	function delete($iface)
	{
		// Load libraries
		//---------------

		$this->lang->load('smtp');

		// Load views
		//-----------

		$this->page->set_title(lang('smtp_trusted_networks'));

		$data['message'] = sprintf(lang('smtp_confirm_delete_trusted_network'), $network);
		$data['ok_anchor'] = '/app/smtp/trusted/destroy/' . $network;
		$data['cancel_anchor'] = '/app/smtp/trusted';
	
		$this->load->view('theme/header');
		$this->load->view('theme/confirm', $data);
		$this->load->view('theme/footer');
	}

	/**
	 * Destroys trusted network
	 */

	function destroy($iface)
	{
		// Load libraries
		//---------------

		$this->load->library('dns/DnsMasq');

		// Handle form submit
		//-------------------

		try {
			$this->dnsmasq->deletesubnet($iface);
			$this->page->set_success(lang('base_deleted'));
		} catch (Exception $e) {
			$this->page->view_exception($e->GetMessage());
			return;
		}

		// Redirect
		//---------

		redirect('/dhcp/subnets');
	}

	/**
	 * Edit trusted network.
	 */

	function edit($iface = null)
	{
		// Use common add/edit form
		$this->_addedit($iface, 'edit');
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Trusted network common add/edit form handler.
	 */

	function _addedit($iface, $form_type)
	{
		// Load libraries
		//---------------

		$this->load->library('dns/DnsMasq');
		$this->lang->load('dhcp');

		// Set validation rules
		//---------------------

		// TODO: Review the messy dns1/2/3 handling
		$this->load->library('form_validation');
		$this->form_validation->set_policy('gateway', 'dns_DnsMasq_ValidateGateway', TRUE);
		$this->form_validation->set_policy('lease_time', 'dns_DnsMasq_ValidateLeaseTime', TRUE);
		$this->form_validation->set_policy('start', 'dns_DnsMasq_ValidateStartIp', TRUE);
		$this->form_validation->set_policy('end', 'dns_DnsMasq_ValidateEndIp', TRUE);
		$this->form_validation->set_policy('dns1', 'dns_DnsMasq_ValidateDns');
		$this->form_validation->set_policy('dns2', 'dns_DnsMasq_ValidateDns');
		$this->form_validation->set_policy('dns3', 'dns_DnsMasq_ValidateDns');
		$this->form_validation->set_policy('wins', 'dns_DnsMasq_ValidateWins');
		$this->form_validation->set_policy('tftp', 'dns_DnsMasq_ValidateTftp');
		$this->form_validation->set_policy('ntp', 'dns_DnsMasq_ValidateNtp');
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('submit') && ($form_ok === TRUE)) {
			$subnet['network'] = $this->input->post('network');
			$subnet['gateway'] = $this->input->post('gateway');
			$subnet['start'] = $this->input->post('start');
			$subnet['end'] = $this->input->post('end');
			$subnet['wins'] = $this->input->post('wins');
			$subnet['tftp'] = $this->input->post('tftp');
			$subnet['ntp'] = $this->input->post('ntp');
			$subnet['lease_time'] = $this->input->post('lease_time');
			$subnet['dns'] = array(
				$this->input->post('dns1'),
				$this->input->post('dns2'),
				$this->input->post('dns3'),
			);

			try {
				$this->dnsmasq->UpdateSubnet(
					$iface,
					$subnet['gateway'],
					$subnet['start'],
					$subnet['end'],
					$subnet['dns'],
					$subnet['wins'],
					$subnet['lease_time'],
					$subnet['tftp'],
					$subnet['ntp']
				);

				$this->dnsmasq->Reset();

				// Return to summary page with status message
				$this->page->set_success(lang('base_system_updated'));
				redirect('/dhcp/subnets');
			} catch (Exception $e) {
				$this->page->view_exception($e->GetMessage(), $view);
				return;
			}
		}

		// Load the view data 
		//------------------- 

		try {
			if ($form_type === 'add') 
				$subnet = $this->dnsmasq->GetSubnetDefault($iface);
			else
				$subnet = $this->dnsmasq->GetSubnet($iface);
		} catch (Exception $e) {
			$this->page->view_exception($e->GetMessage(), $view);
			return;
		}

		$data['form_type'] = $form_type;

		$data['interface'] = $iface;
		$data['network'] = (isset($subnet['network'])) ? $subnet['network'] : '';
		$data['gateway'] = (isset($subnet['gateway'])) ? $subnet['gateway'] : '';
		$data['start'] = (isset($subnet['start'])) ? $subnet['start'] : '';
		$data['end'] = (isset($subnet['end'])) ? $subnet['end'] : '';
		$data['dns'] = (isset($subnet['dns'])) ? $subnet['dns'] : '';
		$data['wins'] = (isset($subnet['wins'])) ? $subnet['wins'] : '';
		$data['tftp'] = (isset($subnet['tftp'])) ? $subnet['tftp'] : '';
		$data['ntp'] = (isset($subnet['ntp'])) ? $subnet['ntp'] : '';
		$data['lease_time'] = (isset($subnet['lease_time'])) ? $subnet['lease_time'] : '';

		$data['lease_times'] = array();
		$data['lease_times'][12] = 12 . " " . lang('base_hours');
		$data['lease_times'][24] = 24 . " " . lang('base_hours');
		$data['lease_times'][48] = 2 . " " . lang('base_days');
		$data['lease_times'][72] = 3 . " " . lang('base_days');
		$data['lease_times'][96] = 4 . " " . lang('base_days');
		$data['lease_times'][120] = 5 . " " . lang('base_days');
		$data['lease_times'][144] = 6 . " " . lang('base_days');
		$data['lease_times'][168] = 7 . " " . lang('base_days');
		$data['lease_times'][336] = 2 . " " . lang('base_weeks');
		$data['lease_times'][504] = 3 . " " . lang('base_weeks');
		$data['lease_times'][672] = 4 . " " . lang('base_weeks');
		$data['lease_times'][Dnsmasq::CONSTANT_UNLIMITED_LEASE] = lang('base_unlimited');
 
		// Load the views
		//---------------

		$this->page->set_title(lang('dhcp_dhcp') . ' - ' . lang('dhcp_subnets'));

		$this->load->view('theme/header');
		$this->load->view('dhcp/subnets/add_edit', $data);
		$this->load->view('theme/footer');
	}
}

?>
