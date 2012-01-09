<?php

/**
 * Content filter warning controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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
 * Content filter warning controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class Warning extends ClearOS_Controller
{
    /**
     * Content filter warning overview.
     *
     * @return view
     */

    function index($url, $reason)
    {
        // Load dependencies
        //------------------

        $this->load->library('content_filter/DansGuardian');
        $this->lang->load('content_filter');

        // TODO: validate

        // URL view data
        //--------------

        $data['url'] = base64_decode(strtr($url, '-_.', '+/='));

        // Trim down long URLs
        if (strlen($data['url']) > 60)
            $data['url'] = substr($data['url'], 0, 60) . ' ...';

        // Reason view data
        //-----------------
        // Format is -- <reason description>:<reason summary> (details)

        $reason = base64_decode(strtr($reason, '-_.', '+/='));

        if (preg_match('/:.*\(.*\)/', $reason)) {
            list($reason_header, $details) = split('\(', $reason, 2);
            list($description, $summary) = split(':', $reason_header, 2);

            // Full report details
            $details = trim($details);
            $details = preg_replace("/\(-/", "-(", $details);

            if ($details && !preg_match("/^[\+\-]/", $details))
                $details = "&gt $details";

            $details = preg_replace("/\+-/", "<br />~ ", $details);
            $details = preg_replace("/-/", "<br />- ", $details);
            $details = preg_replace("/\+/", "<br />+ ", $details);
            $details = trim($details);
            $details = preg_replace("/\)$/", "", $details);

            if (is_numeric(trim($summary))) {
                $data['description'] = lang('content_filter_content_has_been_blocked');
                $data['details'] = lang('content_filter_page_score') . ' - ' . $summary . '<br>' . $details;
            } else {
                $data['description'] = "$description -- $summary";
                $data['details'] = $details;
            }
        } else {
            $data['description'] = $reason;
        }

        // Load views
        //-----------

        $page['type'] = MY_Page::TYPE_SPLASH;

        $this->page->view_form('content_filter/warning', $data, lang('content_filter_content_filter'), $page);
    }
}
