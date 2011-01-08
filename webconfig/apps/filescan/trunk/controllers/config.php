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
 * File scanner configuration.
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
 * File scanner configuration.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2011, ClearFoundation
 */

class Config extends ClearOS_Controller
{
	/**
	 * File scanner configuration.
	 */

	function index($view = 'page')
	{
		// Load libraries
		//---------------

		$this->load->library('file_scan/FileScan');
		$this->lang->load('file_scan');

		// Handle form submit
		//-------------------

		if ($this->input->post('submit')) {
			try {
				$requested = $this->input->post('directories');
				$presets = $this->filescan->GetDirectoryPresets();
				$configured = $this->filescan->GetDirectories();
				$schedule_exists = $this->filescan->ScanScheduleExists();

				// Update directories
				//-------------------

				foreach ($presets as $preset => $description) {
					if (array_key_exists($preset, $requested) && (!in_array($preset, $configured)))
						$this->filescan->AddDirectory($preset);
					else if (!array_key_exists($preset, $requested) && (in_array($preset, $configured)))
						$this->filescan->RemoveDirectory($preset);
				}

				// Update shedule
				//---------------

				$hour = $this->input->post('hour');

				if ($schedule_exists && $hour === 'disabled') {
					$this->filescan->RemoveScanSchedule();
				}  else if (!$schedule_exists && $hour !== 'disabled') {
					$this->filescan->SetScanSchedule('0', $hour, '*', '*', '*');
					// FIXME: move this to scan script
					// $this->freshclam->SetBootState(TRUE);
					// $this->freshclam->SetRunningState(TRUE);
				}

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

			$this->load->view('file_scan/config', $data);

		} else if ($view == 'page') {
			$data['form_type'] = 'edit';

			$this->page->set_title(lang('file_scan_antimalware') . ' - ' . lang('base_general_settings'));

			$this->load->view('theme/header');
			$this->load->view('file_scan/config', $data);
			$this->load->view('theme/footer');
		}
	}
}

?>
