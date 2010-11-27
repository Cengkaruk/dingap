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
 * DHCP server configuration.
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
	 */

	function index()
	{
		if ($this->session->userdata['theme_mode'] === CLEAROS_MOBILE)
			$this->mobile_index();
		else
			$this->desktop_index();
	}

	/**
	 * DHCP server summary view for desktops.
	 */

	function desktop_index()
	{
		// Load libraries
		//---------------

		$this->lang->load('dhcp');
		$this->load->module('dhcp/general');
		$this->load->module('dhcp/subnets');
		$this->load->module('dhcp/leases');

		// Load views
		//-----------

		$header['title'] = lang('dhcp_dhcp');

		$this->load->view('theme/header', $header);
		$this->general->index('form');
		$this->subnets->index('form');
		$this->leases->index('form');
		$this->load->view('theme/footer');
	}

	/**
	 * DHCP server summary view for mobile/control panel.
	 */

	function mobile_index()
	{
		// Load libraries
		//---------------

		$this->lang->load('base');
		$this->lang->load('dhcp');

		// Load views
		//-----------

// FIXME: add icons and help blurb for control panel view
		$summary['links']['/app/dhcp/general'] = lang('base_general_settings');
		$summary['links']['/app/dhcp/subnets'] = lang('dhcp_subnets');
		$summary['links']['/app/dhcp/leases'] = lang('dhcp_leases');

		$header['title'] = lang('dhcp_dhcp');

		$this->load->view('theme/header', $header);
		$this->load->view('theme/summary', $summary);
		$this->load->view('theme/footer');
	}
}

?>
