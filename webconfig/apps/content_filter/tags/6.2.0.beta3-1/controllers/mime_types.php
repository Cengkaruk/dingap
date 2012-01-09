<?php

/**
 * Content filter mime types controller.
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
 * Content filter mime types controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class MIME_Types extends ClearOS_Controller
{
    /**
     * Content filter mime types management default controller.
     *
     * @return view
     */

    function index()
    {
        $this->edit(1);
    }

    /**
     * Blacklist edit.
     *
     * @param integer $policy policy ID
     *
     * @return view
     */

    function edit($policy = 1)
    {
        // Load libraries
        //---------------

        $this->lang->load('content_filter');
        $this->load->library('content_filter/DansGuardian');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            // Note: . is not allowed in input keys in CodeIgniter
            $mime_types = array();
            $raw_mime_types = array_keys($this->input->post('mime_types'));

            foreach ($raw_mime_types as $mime_type)
                $mime_types[] = strtr($mime_type, '_', '.');

            try {
                $this->dansguardian->set_banned_mime_types($mime_types, $policy);
                $this->dansguardian->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/content_filter/policy/edit/' . $policy);
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['policy'] = $policy;
            $data['all_mime_types'] = $this->dansguardian->get_possible_mime_types();
            $data['banned_mime_types'] = $this->dansguardian->get_banned_mime_types($policy);
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('content_filter/policy/mime_types', $data, lang('content_filter_mime_types'));
    }
}
