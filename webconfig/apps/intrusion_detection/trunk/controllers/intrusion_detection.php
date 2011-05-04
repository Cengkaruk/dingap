<?php

/**
 * Intrusion detection controller.
 *
 * @category   Apps
 * @package    Intrusion_Detection
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_detection/
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
 * Intrusion detection controller.
 *
 * @category   Apps
 * @package    Intrusion_Detection
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_detection/
 */

class Intrusion_Detection extends ClearOS_Controller
{
    /**
     * Intrusion detection default controller
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('intrusion_detection');
        $this->load->library('intrusion_detection/Snort');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $this->snort->set_rule_sets($this->input->post('rule_sets'));
                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            // $data['rule_sets'] = $this->snort->get_rule_set_details();
            $rule_sets = $this->snort->get_rule_set_details();
            $data['rule_sets'] = $rule_sets['gpl'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('intrusion_detection', $data, lang('intrusion_detection_intrusion_detection'));
    }
}
