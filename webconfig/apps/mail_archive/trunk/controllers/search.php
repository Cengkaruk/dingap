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
 * Search controller.
 *
 * @category   Apps
 * @package    Mail_Archive
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/mail_archive/
 */

class Search extends ClearOS_Controller
{

    /**
     * Search Stats default controller
     *
     * @return view
     */

    function index()
    {
        
        // Load dependencies
        //------------------

        $this->load->library('mail_archive/Mail_Archive');
        $this->lang->load('mail_archive');

        if ($this->input->post('search')) {
            // Let's do some searching
/*
            $this->mail_archive->search(
                'current',
                $this->input->post(''),
                $criteria, $regex, $logical, $max, $offset)
*/
        }
        $data['match_options'] = $this->mail_archive->get_match_options();
        for ($index = 1; $index <= 5; $index++) {
            $data['field_options_' . $index] = $this->mail_archive->get_field_options();
            $data['pattern_options_' . $index] = $this->mail_archive->get_pattern_options();
        }

        $this->page->view_form('mail_archive/search', $data, lang('mail_archive_search'));
    }
}
