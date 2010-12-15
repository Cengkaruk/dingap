<?php

///////////////////////////////////////////////////////////////////////////////
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
 * Local DNS management.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// C O N T R O L L E R
///////////////////////////////////////////////////////////////////////////////

/**
 * Local DNS management.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 */

class Dns extends ClearOS_Controller
{
	/**
	 * Local DNS summary view.
	 */

	function index()
	{
		// Load libraries
		//---------------

		$this->load->library('network/Hosts');
		$this->lang->load('dns');

		// Load view data
		//---------------

		try {
			$data['hosts'] = $this->hosts->GetEntries();
		} catch (Exception $e) {
			$this->page->exception($e->GetMessage());
			return;
		}
 
		// Load views
		//-----------
			
		$page['title'] = lang('dns_local_dns_server');

		$this->load->view('theme/header', $page);
		$this->load->view('dns/summary', $data);
		$this->load->view('theme/footer', $page);
	}

	/**
	 * Add local DNS entry view.
	 *
	 * @param string $ip IP address
	 * @return view
	 */

	function add($ip)
	{
		// Use common add/edit form
		$this->_addedit($ip, 'add');
	}

	/**
	 * Delete local DNS entry view.
	 *
	 * @param string $ip IP address
	 * @return view
	 */

	function delete($ip)
	{
		// Load libraries
		//---------------

		$this->lang->load('dns');

		// Load views
		//-----------

		$page['title'] = lang('dns_dns_entry');

		$data['message'] = sprintf(lang('dns_confirm_delete'), $ip);
		$data['ok_anchor'] = '/app/dns/destroy/' . $ip;
		$data['cancel_anchor'] = '/app/dns';
	
		$this->load->view('theme/header', $page);
		$this->load->view('theme/confirm', $data);
		$this->load->view('theme/footer', $page);
	}

	/**
	 * Edit DNS entry view.
	 *
	 * @param string $ip IP address
	 * @return view
	 */

	function edit($ip)
	{
		// Use common add/edit form
		$this->_addedit($ip, 'edit');
	}

	/**
	 * Destroys local DNS entry view.
	 *
	 * @param string $ip IP address
	 * @param boolean $confirm confirmation
	 * @return view
	 */

	function destroy($ip)
	{
		// Load libraries
		//---------------

		$this->load->library('network/Hosts');
		$this->load->library('dns/DnsMasq');

		// Handle form submit
		//-------------------

		try {
			$this->hosts->deleteentry($ip);
			$this->dnsmasq->reset();
		} catch (Exception $e) {
			$this->page->exception($e->GetMessage());
			return;
		}

		// Redirect
		//---------

		$this->page->success(lang('base_deleted'));
		redirect('/dns');
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * DNS entry rommon add/edit form handler.
	 *
	 * @param string $ip IP address
	 * @param string $form_type form type
	 * @return view
	 */

	function _addedit($ip, $form_type)
	{
		// Load libraries
		//---------------

		$this->load->library('network/Hosts');
		$this->load->library('dns/DnsMasq');
		$this->lang->load('dns');
		$this->lang->load('network');

		// Set validation rules
		//---------------------

		// TODO: Review the messy alias1/2/3 handling
		$this->load->library('form_validation');
		$this->form_validation->set_rules('ip', lang('network_ip'), 'required|api_network_Hosts_ValidateIp');
		$this->form_validation->set_rules('hostname', lang('network_hostname'), 'valid_email|required|api_network_Hosts_ValidateHostname');
		$this->form_validation->set_rules('alias1', lang('dns_alias'), 'api_network_Hosts_ValidateAlias');
		$this->form_validation->set_rules('alias2', lang('dns_alias'), 'api_network_Hosts_ValidateAlias');
		$this->form_validation->set_rules('alias3', lang('dns_alias'), 'api_network_Hosts_ValidateAlias');
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('submit') && ($form_ok === TRUE)) {

			$ip = $this->input->post('ip');
			$hostname = $this->input->post('hostname');
			$aliases = array();

			if ($this->input->post('alias1'))
				$aliases[] = $this->input->post('alias1');

			if ($this->input->post('alias2'))
				$aliases[] = $this->input->post('alias2');

			if ($this->input->post('alias3'))
				$aliases[] = $this->input->post('alias3');

			try {
				if ($form_type === 'edit') 
					$this->hosts->EditEntry($ip, $hostname, $aliases);
				else
					$this->hosts->AddEntry($ip, $hostname, $aliases);

				$this->dnsmasq->Reset();

				// Return to summary page with status message
				$this->page->success(lang('base_system_updated'));
				redirect('/dns');
			} catch (Exception $e) {
				$this->page->exception($e->GetMessage(), $view);
				return;
			}
		}

		// Load the view data 
		//------------------- 

		try {
			if ($form_type === 'edit') 
				$entry = $this->hosts->GetEntry($ip);
		} catch (Exception $e) {
			$this->page->exception($e->GetMessage(), $view);
			return;
		}

		$data['form_type'] = $form_type;

		$data['ip'] = $ip;
		$data['hostname'] = isset($entry['hostname']) ? $entry['hostname'] : '';
		$data['aliases'] = isset($entry['aliases']) ? $entry['aliases'] : '';

		// Load the views
		//---------------

		$page['title'] = lang('dns_dns_entry');

		$this->load->view('theme/header', $page);
		$this->load->view('dns/add_edit', $data);
		$this->load->view('theme/footer', $page);
	}
}

?>
