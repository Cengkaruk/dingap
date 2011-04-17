<?php

/**
 * Antivirus file scan configuration controller.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
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
 * Antivirus file scan configuration controller.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
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

		$this->load->library('file_scan/File_Scan');
		$this->lang->load('file_scan');

		// Handle form submit
		//-------------------

		if ($this->input->post('submit')) {
			try {
				$requested = $this->input->post('directories');
				$presets = $this->file_scan->get_directory_Presets();
				$configured = $this->file_scan->get_directories();
				$schedule_exists = $this->file_scan->scan_schedule_exists();

				// Update directories
				//-------------------

				foreach ($presets as $preset => $description) {
					if (array_key_exists($preset, $requested) && (!in_array($preset, $configured)))
						$this->file_scan->add_directory($preset);
					else if (!array_key_exists($preset, $requested) && (in_array($preset, $configured)))
						$this->file_scan->Remove_directory($preset);
				}

				// Update shedule
				//---------------

				$hour = $this->input->post('hour');

				if ($schedule_exists && $hour === 'disabled') {
					$this->file_scan->remove_scan_schedule();
				}  else if (!$schedule_exists && $hour !== 'disabled') {
					$this->file_scan->set_scan_schedule('0', $hour, '*', '*', '*');
					// FIXME: move this to scan script
					// $this->freshclam->SetBootState(TRUE);
					// $this->freshclam->SetRunningState(TRUE);
				}

				// Redirect to main page
//				 $this->page->set_success(lang('base_system_updated'));
//				redirect('/file_scan/');
			} catch (Exception $e) {
				$this->page->view_exception($e->GetMessage(), $view);
				return;
			}
		}

		// Load view data
		//---------------

		try {
			$data['directories'] = $this->file_scan->get_directories();
			$data['presets'] = $this->file_scan->get_directory_presets();
			$data['schedule_exists'] = $this->file_scan->scan_schedule_exists();

			$schedule = $this->file_scan->get_scan_schedule();
			$data['hour'] = $schedule['hour'];
		} catch (Exception $e) {
			$this->page->view_exception($e->GetMessage(), $view);
			return;
		}
 
		// Load views
		//-----------

        $this->page->view_form('file_scan/config', $data, lang('file_scan_antimalware') . ' - ' . lang('base_general_settings'));
	}
}
