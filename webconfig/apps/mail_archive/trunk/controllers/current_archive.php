<?php

/**
 * Mail Archive current controller.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Current Archive controller.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
 */

class Current_Archive extends ClearOS_Controller
{

    /**
     * Mail Archive default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('mail_archive/Mail_Archive');
        $this->lang->load('mail_archive');

        try {
            $data['stats' ] = $this->mail_archive->get_current_stats();
            print_r($data);
        } catch (Exception $e) {
            $this->page->set_message(clearos_exception_message($e));
        }
        $this->page->view_form('mail_archive/stats', $data, lang('mail_archive_current_stats'));
    }

}
