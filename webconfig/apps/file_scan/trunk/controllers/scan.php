<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2011 ClearFoundation
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
 * File scanner execution.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2011, ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// C O N T R O L L E R
///////////////////////////////////////////////////////////////////////////////

/**
 * File scanner execution.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2011, ClearFoundation
 */

class Scan extends ClearOS_Controller
{
	/**
	 * File scanner execution.
	 */

	function index($view = 'page')
	{
		// Load libraries
		//---------------

		$this->load->library('filescan/FileScan');
		$this->lang->load('filescan');

		// Handle form submit
		//-------------------

		if ($this->input->post('submit')) {
			try {
/*
				$presets = $this->filescan->GetDirectoryPresets();
				$configured = $this->filescan->GetDirectories();
				$requested = $this->input->post('directories');
				$schedule_exists = $this->filescan->ScanScheduleExists();
*/

				// Redirect to main page
//				 $this->page->set_success(lang('base_system_updated'));
//				redirect('/filescan/');
			} catch (Exception $e) {
				$this->page->view_exception($e->GetMessage(), $view);
				return;
			}
		}

		// Load view data
		//---------------

		try {
			$data['directories'] = $this->filescan->GetDirectories();
			$data['presets'] = $this->filescan->GetDirectoryPresets();
			$data['schedule_exists'] = $this->filescan->ScanScheduleExists();

			$schedule = $this->filescan->GetScanSchedule();
			$data['hour'] = $schedule['hour'];
		} catch (Exception $e) {
			$this->page->view_exception($e->GetMessage(), $view);
			return;
		}
 
		// Load views
		//-----------

		if ($view == 'form') {
			$data['form_type'] = 'view';

			$this->load->view('filescan/scan', $data);

		} else if ($view == 'page') {
			$data['form_type'] = 'edit';

			$this->page->set_title(lang('filescan_antimalware') . ' - ' . lang('base_status'));

			$this->load->view('theme/header');
			$this->load->view('filescan/scan', $data);
			$this->load->view('theme/footer');
		}
	}

	// FIXME: standard naming convention?
	function info()
	{
		// Load libraries
		//---------------

		$this->load->library('filescan/FileScan');

		// Load view data
		//---------------

		try {
			$info = $this->filescan->GetInfo();
		} catch (Exception $e) {
			// FIXME: what to return here?
			$info['ajax_error'] = $e->GetMessage();
		}

		echo json_encode($info); 
	}
}

?>
