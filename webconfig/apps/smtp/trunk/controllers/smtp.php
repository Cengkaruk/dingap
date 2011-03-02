<?php

/**
 * SMTP controller.
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
 * SMTP controller.
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
 */

class SMTP extends ClearOS_Controller
{
	/**
	 * SMTP server overview.
	 */

	function index()
	{
		if ($this->session->userdata['theme_mode'] === CLEAROS_MOBILE)
			$this->mobile_index();
		else
			$this->desktop_index();
	}

	/**
	 * SMTP server summary view for desktops.
	 */

	function desktop_index()
	{
		// Load libraries
		//---------------

		$this->lang->load('smtp');
		$this->load->module('smtp/general');
//		$this->load->module('smtp/subnets');

		// Load views
		//-----------

		$this->page->set_title(lang('smtp_smtp_server'));

		$this->load->view('theme/header');
		$this->general->index('form');
//		$this->subnets->index('form');
		$this->load->view('theme/footer');
	}

	/**
	 * SMTP server summary view for mobile/control panel.
	 */

	function mobile_index()
	{
		// Load libraries
		//---------------

		$this->lang->load('base');
		$this->lang->load('smtp');

		// Load views
		//-----------

// FIXME: add icons and help blurb for control panel view
		$summary['links']['/app/smtp/general'] = lang('base_general_settings');
		$summary['links']['/app/smtp/networks'] = lang('smtp_trusted_networks');
		$summary['links']['/app/smtp/domains'] = lang('smtp_destination_domains');
		$summary['links']['/app/smtp/forwarders'] = lang('smtp_forward_domains');

		$this->page->set_title(lang('smtp_smtp_server'));

		$this->load->view('theme/header');
		$this->load->view('theme/summary', $summary);
		$this->load->view('theme/footer');
	}
}

?>
