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
	 *
	 * @param string $view view type
	 *
	 * @return view
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
				$presets = $this->file_scan->get_directory_resets();
				$configured = $this->file_scan->get_directories();
				$schedule_exists = $this->file_scan->scan_schedule_exists();

				// Redirect to main page
				/*
				$this->page->set_success(lang('base_system_updated'));
				redirect('/file_scan/');
				*/
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

		if ($view == 'form') {
			$data['form_type'] = 'view';

			$this->load->view('file_scan/scan', $data);

		} else if ($view == 'page') {
			$data['form_type'] = 'edit';

			$this->page->set_title(lang('file_scan_antimalware') . ' - ' . lang('base_status'));

			$this->load->view('theme/header');
			$this->load->view('file_scan/scan', $data);
			$this->load->view('theme/footer');
		}
	}

	/**
	 * JSON encoded scan information
	 *
	 * @return string JSON encoded information
	 */

	function info()
	{
		// Load libraries
		//---------------

		$this->load->library('file_scan/File_Scan');

		// Load view data
		//---------------

		try {
			$info = $this->file_scan->get_info();
		} catch (Exception $e) {
			// FIXME: what to return here?
			$info['ajax_error'] = $e->GetMessage();
		}

		echo json_encode($info); 
	}
}

?>
