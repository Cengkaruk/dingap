<?php

/**
 * Intrusion protection report controller.
 *
 * @category   Apps
 * @package    Intrusion_Protection_Report
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
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
 * Intrusion protection report controller.
 *
 * @category   Apps
 * @package    Intrusion_Protection_Report
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/date/
 */

class Intrusion_Protection_Report extends ClearOS_Controller
{
    /**
     * Date default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('intrusion_protection_report');

        // Load views
        //-----------

        $page['type'] = MY_Page::TYPE_REPORT;

        $this->page->view_form('intrusion_protection_report', $data, lang('intrusion_protection_report_intrusion_protection_report'), $page);
    }
}
