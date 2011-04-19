<?php

/**
 * Theme developer controller.
 *
 * @category   Apps
 * @package    Devel
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/devel/
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
 * Theme developer controller.
 *
 * @category   Apps
 * @package    Devel
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2010-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/devel/
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
        $this->lang->load('devel');
        $this->page->view_form('theme', $data, lang('devel_theme_viewer'));
    }

    /**
     * JSON encoded progress bar information
     *
     * @return string JSON encoded information
     */

    function progress_data()
    {
        $info['progress'] = strftime("%S") * 100 / 60;
        $info['progress_standalone'] = 100 - $info['progress'];

        echo json_encode($info);
    }
}
