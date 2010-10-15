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
 * Date controller.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 * @link http://www.clearfoundation.com	
 */

class Date extends MX_Controller 
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

		$this->load->library('date/NtpTime');

		// Handle form submit
		//-------------------

		if ($this->input->post('submit')) {
			try {
				$this->ntptime->SetTimeZone($this->input->post('timezone'));
			} catch (Exception $e) {
				// FIXME: exception handling
				echo "ooops: " . $e->GetMessage();
			}
		}

		// Load view data
		//---------------

		try {
			$data['date'] = strftime("%b %e %Y");
			$data['time'] = strftime("%T %Z");
			$data['timezone'] = $this->ntptime->gettimezone();
			$data['timezones'] = convert_to_hash($this->ntptime->gettimezonelist());
		} catch (Exception $e) {
			// FIXME: exception handling 
			echo "ooops: " . $e->GetMessage();
		}

		// Load views
		//-----------

		$header['title'] = lang('date_time_and_date');

//		$this->load->view('theme/header', $header);
		$this->load->view('date', $data);
//		$this->load->view('theme/footer');
	}

	/**
	 * Runs a network time synchronization event.
	 */

	function sync()
	{
		sleep(1);

		$this->load->library('NtpTime');

		$diff = $this->ntptime->synchronize();

		echo "offset: $diff";
	}
}

// vim: ts=4
?>
