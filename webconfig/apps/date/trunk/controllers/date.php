<?php

//////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
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

/**
 * Date controller.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU General Public License version 3 or later
 * @copyright Copyright 2010, ClearFoundation
 * @link http://www.clearfoundation.com	
 */

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Date controller.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU General Public License version 3 or later
 * @copyright Copyright 2010, ClearFoundation
 * @link http://www.clearfoundation.com	
 */

class Date extends ClearOS_Controller 
{
	/**
	 * Date default controller
	 *
	 * @return string
	 */

	function index()
	{
		// Load libraries
		//---------------

		$this->load->library('date/Time');

		$header['exceptions'] = array();
		$header['warnings'] = array();

		// Set validation rules
		//---------------------
		 
		$this->load->library('form_validation');
		$this->form_validation->set_rules('timezone', lang('date_time_zone'), 'api_date_Time_ValidateTimeZone');
		$form_ok = $this->form_validation->run();

		// Handle form submit
		//-------------------

		if ($this->input->post('submit')) {
			try {
				$this->time->SetTimeZone($this->input->post('timezone'));
			} catch (Exception $e) {
				$header['exceptions'][] = $e->GetMessage();
			}
		}

		// Load view data
		//---------------

		try {
			$data['timezone'] = $this->time->gettimezone();
		} catch (TimezoneNotSetException $e) {
			// Not fatal
			$data['timezone'] = '';
			$header['warnings'][] = $e->GetMessage();
		} catch (Exception $e) {
			$header['exceptions'][] = $e->GetMessage();
		}

		try {
			$data['date'] = strftime("%b %e %Y");
			$data['time'] = strftime("%T %Z");
			$data['timezones'] = convert_to_hash($this->time->gettimezonelist());
		} catch (Exception $e) {
			$header['exceptions'][] = $e->GetMessage();
		}

		// Load views
		//-----------

		$header['title'] = lang('date_date');

		$this->load->view('theme/header', $header);
		$this->load->view('date', $data);
		$this->load->view('theme/footer');
	}

	/**
	 * Runs a network time synchronization event.
	 */

	function sync()
	{
		sleep(1); // FIXME -- just for viewing the whirlygig.

		// Load libraries
		//---------------

		$this->load->library('NtpTime');

		// Run synchronize
		//----------------

		try {
			$diff = $this->ntptime->synchronize();
		} catch (Exception $e) {
			// FIXME: should have a standard here?
			echo "Ooops: " . $e->GetMessage();
			return;
		}

		// Return status message
		//----------------------

		// FIXME: use a view?  Some other standard function call?
		echo "offset: $diff\n"; // FIXME: localize
	}
}

// vim: ts=4
?>
