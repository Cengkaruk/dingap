<?php

/**
 * Mail Archive controller.
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

use \clearos\apps\mail_archive\Mail_Archive as Mail_Archive_Class;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Mail Archive controller.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
 */

class Mail_Archive extends ClearOS_Controller
{

    /**
     * Mail Archive default controller
     *
     * @return view
     */

    function index()
    {
        // Load views
        //-----------

        $views = array(
            'mail_archive/settings',
            'mail_archive/live'
        );

        $this->page->view_forms($views, lang('mail_archive_app_name'));
    }

}
