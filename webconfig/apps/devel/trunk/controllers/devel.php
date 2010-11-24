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
 * Devel controller.
 *
 * @package Frontend
 * @author {@link http://www.clearfoundation.com ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 * @link http://www.clearfoundation.com	
 */

class Devel extends ClearOS_Controller 
{
	/**
	 * Devel default controller
	 *
	 * @return string
	 */

	function index()
	{
		$header['title'] = 'Theme Viewer';

		$this->load->view('theme/header', $header);
		$this->load->view('theme', $data);
		$this->load->view('theme/footer');
	}

	function mobile()
	{
		$_SESSION['system_template'] = 'clearos6xmobile/trunk';

		$header['title'] = 'Theme Viewer';

		$this->load->view('theme/header', $header);
		$this->load->view('theme', $data);
		$this->load->view('theme/footer');
	}

	function normal()
	{
		$_SESSION['system_template'] = 'clearos6x/trunk';

		$header['title'] = 'Theme Viewer';

		$this->load->view('theme/header', $header);
		$this->load->view('theme', $data);
		$this->load->view('theme/footer');
	}
}

// vim: ts=4
?>
