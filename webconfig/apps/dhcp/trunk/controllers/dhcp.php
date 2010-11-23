<?php

//////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * DHCP server configuration.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 */

class Dhcp extends ClearOS_Controller
{
	/**
	 * DHCP server overview.
	 *
	 * @return string
	 */

	function index()
	{
		// Load libraries
		//---------------

		$this->load->library('dns/DnsMasq');

		// Set validation rules
		//---------------------

		$this->load->library('form_validation');
		$this->form_validation->set_rules('domain', lang('dhcp_domain'), 'required|api_dns_DnsMasq_ValidateDomain');
		$this->form_validation->set_rules('authoritative', lang('dhcp_authoritative'), '');
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('submit') && ($form_ok)) {
			try {
				$this->dnsmasq->SetDomainName($this->input->post('domain'));
				$this->dnsmasq->SetAuthoritativeState((bool)$this->input->post('authoritative'));
				$this->dnsmasq->Reset();
			} catch (Exception $e) {
				// FIXME: review exception handling
				$header['fatal_error'] = $e->GetMessage();
			}
		}

		// Load view data
		//---------------

		$data['authoritative'] = $this->dnsmasq->GetAuthoritativeState();
		$data['domain'] = $this->dnsmasq->GetDomainName();
		$data['subnets'] = $this->dnsmasq->GetSubnets();
		$data['ethlist'] = $this->dnsmasq->GetDhcpInterfaces();
 
		// Load views
		//-----------

		$header['title'] = "DHCP"; // localize

		$this->load->view('theme/header', $header);
		$this->load->view('dhcp/overview', $data);
		$this->load->view('dhcp/subnets', $data);
		$this->load->view('theme/footer');
	}

	/**
	 * DHCP server add subnet.
	 *
	 * @return string
	 */

	function add($iface)
	{
		// Use common add/edit form
		$this->_addedit($iface, 'add');
	}

	/**
	 * DHCP server delete subnet.
	 *
	 * @return string
	 */

	function delete($iface = null)
	{
		// Load libraries
		//---------------

		$this->load->library('dns/DnsMasq');

		// Handle form submit
		//-------------------

		$this->dnsmasq->deletesubnet($iface);

// FIXME
$this->load->helper('url');
redirect('/dhcp');
		// Load the view data 
		//------------------- 

		$data['iface'] = $iface;

		// Load the views
		//---------------

		$this->load->view('theme/header', $header);
		$this->load->view('dhcp/overview', $data);
		$this->load->view('dhcp/subnets', $data);
		$this->load->view('theme/footer');
	}

	/**
	 * DHCP server edit subnet.
	 *
	 * @return string
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
	 * DHCP server common add/edit form handler.
	 *
	 * @return string
	 */

	function _addedit($iface, $formtype)
	{
		// Load libraries
		//---------------

		$this->load->library('dns/DnsMasq');
		$this->lang->load('dhcp');

		// Set validation rules
		//---------------------

		$this->load->library('form_validation');
		$this->load->helper('url');

		// TODO: Review the messy dns1/2/3 handling
		$this->form_validation->set_rules('gateway', lang('dhcp_gateway'), 'required|api_dns_DnsMasq_ValidateGateway');
		$this->form_validation->set_rules('leasetime', lang('dhcp_lease_time'), 'required');
		$this->form_validation->set_rules('start', lang('dhcp_ip_range_start'), 'required|api_dns_DnsMasq_ValidateStartIp');
		$this->form_validation->set_rules('end', lang('dhcp_ip_range_end'), 'required|api_dns_DnsMasq_ValidateEndIp');
		$this->form_validation->set_rules('dns1', lang('dhcp_dns'), 'api_dns_DnsMasq_ValidateDns');
		$this->form_validation->set_rules('dns2', lang('dhcp_dns'), 'api_dns_DnsMasq_ValidateDns');
		$this->form_validation->set_rules('dns3', lang('dhcp_dns'), 'api_dns_DnsMasq_ValidateDns');
		$this->form_validation->set_rules('wins', lang('dhcp_wins'), 'api_dns_DnsMasq_ValidateWins');
		$this->form_validation->set_rules('tftp', lang('dhcp_tftp'), 'api_dns_DnsMasq_ValidateTftp');
		$this->form_validation->set_rules('ntp', lang('dhcp_ntp'), 'api_dns_DnsMasq_ValidateNtp');
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		// FIXME: should bomb out if already exists (someone hacking URL)
		if ($this->input->post('submit') && ($form_ok === TRUE)) {
			$subnet['network'] = $this->input->post('network');
			$subnet['gateway'] = $this->input->post('gateway');
			$subnet['start'] = $this->input->post('start');
			$subnet['end'] = $this->input->post('end');
			$subnet['wins'] = $this->input->post('wins');
			$subnet['tftp'] = $this->input->post('tftp');
			$subnet['ntp'] = $this->input->post('ntp');
			$subnet['leasetime'] = $this->input->post('leasetime');
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
					$subnet['leasetime'],
					$subnet['tftp'],
					$subnet['ntp']
				);

				$this->dnsmasq->Reset();
				redirect('/dhcp');
			} catch (Exception $e) {
				$header['fatal_error'] = $e->GetMessage();
			}
		}

		// Load the view data 
		//------------------- 

		try {
			if ($formtype === 'add') 
				$subnet = $this->dnsmasq->GetSubnetDefault($iface);
			else
				$subnet = $this->dnsmasq->GetSubnet($iface);
		} catch (Exception $e) {
			// FIXME: exception handling
			// FIXME: multiple fatal exceptions?
			echo "dude " . $e->GetMessage();
		}

		$data['formtype'] = $formtype;

		$data['interface'] = $iface;
		$data['network'] = (isset($subnet['network'])) ? $subnet['network'] : '';
		$data['gateway'] = (isset($subnet['gateway'])) ? $subnet['gateway'] : '';
		$data['start'] = (isset($subnet['start'])) ? $subnet['start'] : '';
		$data['end'] = (isset($subnet['end'])) ? $subnet['end'] : '';
		$data['dns'] = (isset($subnet['dns'])) ? $subnet['dns'] : '';
		$data['wins'] = (isset($subnet['wins'])) ? $subnet['wins'] : '';
		$data['tftp'] = (isset($subnet['tftp'])) ? $subnet['tftp'] : '';
		$data['ntp'] = (isset($subnet['ntp'])) ? $subnet['ntp'] : '';
		$data['leasetime'] = (isset($subnet['leasetime'])) ? $subnet['leasetime'] : '';

		$data['leasetimes'] = array();
		$data['leasetimes'][12] = 12 . " " . lang('base_hours');
		$data['leasetimes'][24] = 24 . " " . lang('base_hours');
		$data['leasetimes'][48] = 2 . " " . lang('base_days');
		$data['leasetimes'][72] = 3 . " " . lang('base_days');
		$data['leasetimes'][96] = 4 . " " . lang('base_days');
		$data['leasetimes'][120] = 5 . " " . lang('base_days');
		$data['leasetimes'][144] = 6 . " " . lang('base_days');
		$data['leasetimes'][168] = 7 . " " . lang('base_days');
		$data['leasetimes'][336] = 2 . " " . lang('base_weeks');
		$data['leasetimes'][504] = 3 . " " . lang('base_weeks');
		$data['leasetimes'][672] = 4 . " " . lang('base_weeks');
		$data['leasetimes'][Dnsmasq::CONSTANT_UNLIMITED_LEASE] = lang('base_unlimited');
 
		// Load the views
		//---------------

		$header['title'] = "DHCP"; // localize
		$intro['title'] = "DHCP"; // localize
		$intro['intro'] = "Some intro..."; // localize

		$this->load->view('theme/header', $header);
		$this->load->view('dhcp/add_edit', $data);
		$this->load->view('theme/footer');
	}
}

?>
