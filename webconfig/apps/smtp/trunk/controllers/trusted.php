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
	 * Trusted networks overview.
	 */

    function index($mode = 'edit')
	{
		// Handle theme mode redirects
		//----------------------------

        $this->page->theme_redirect('/smtp');

		// Load libraries
		//---------------

		$this->load->library('smtp/Postfix');
		$this->lang->load('smtp');

		// Load view data
		//---------------

		try {
            $data['mode'] = $mode;
			$data['networks'] = $this->postfix->get_trusted_networks();
		} catch (Exception $e) {
			$this->page->view_exception($e);
			return;
		}
 
		// Load views
		//-----------

        $this->page->view_form('smtp/trusted/summary', lang('smtp_trusted_networks'), $data);
	}

	/**
	 * Add trusted network.
	 */

	function add($network)
	{
		$this->_addedit($network, 'add');
	}

	/**
	 * Delete trusted network.
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
	 * Edit trusted network.
     *
     * @param string $network network 
     *
     * @return view
	 */

	function edit($network)
	{
		$this->_addedit($network, 'edit');
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Trusted network common add/edit form handler.
     *
     * @param string $network network 
     *
     * @return view
	 */

	function _addedit($network, $form_mode)
	{
		// Load libraries
		//---------------

		$this->load->library('smtp/Postfix');
		$this->lang->load('smtp');

		// Set validation rules
		//---------------------

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

		// Load the view data 
		//------------------- 

		$data['mode'] = $form_mode;
        $data['network'] = $network;
 
		// Load the views
		//---------------

        $this->page->view_form('smtp/trusted/add_edit', lang('smtp_trusted_networks'), $data);
	}
}
