<?php

/**
 * Content filter blacklists controller.
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\content_filter\DansGuardian as DansGuardian;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Content filter blacklists controller.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class General extends ClearOS_Controller
{
    /**
     * Content filter blacklist management default controller.
     *
     * @return view
     */

    function index()
    {
        $this->edit(1);
    }

    /**
     * General settings edit.
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
            try {
                $this->dansguardian->set_naughtyness_limit($this->input->post('naughtyness_limit'), $policy);
                $this->dansguardian->set_filter_mode($this->input->post('group_mode'), $policy);
                $this->dansguardian->set_reporting_level($this->input->post('reporting_level'), $policy);
                $this->dansguardian->set_content_scan($this->input->post('content_scan'), $policy);
                $this->dansguardian->set_deep_url_analysis($this->input->post('deep_url_analysis'), $policy);
                $this->dansguardian->set_download_block($this->input->post('block_downloads'), $policy);
                $this->dansguardian->set_blanket_block($this->input->post('blanket_block'), $policy);
                $this->dansguardian->set_block_ip_domains($this->input->post('block_ip_domains'), $policy);
                $this->dansguardian->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/content_filter/policy/configure/' . $policy);
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['policy'] = $policy;

            $data['block_ip_domains'] = $this->dansguardian->get_block_ip_domains($policy);
            $data['blanket_block'] = $this->dansguardian->get_blanket_block($policy);

            $data['group_modes'] = $this->dansguardian->get_possible_filter_modes();
            $data['reporting_levels'] = $this->dansguardian->get_possible_reporting_levels();
            $data['naughtyness_limits'] = $this->dansguardian->get_possible_naughtyness_limits();

            $configuration = $this->dansguardian->get_policy_configuration($policy);
            $data['group_mode'] = $configuration['groupmode'];
            $data['content_scan'] = ($configuration['disablecontentscan']) ? FALSE : TRUE;
            $data['deep_url_analysis'] = $configuration['deepurlanalysis'];
            $data['block_downloads'] = $configuration['blockdownloads'];
            $data['naughtyness_limit'] = $configuration['naughtynesslimit'];
            $data['reporting_level'] = $configuration['reportinglevel'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Remove custom reporting level
        if ($data['reporting_level'] != DansGuardian::REPORTING_LEVEL_CUSTOM)
            unset($data['reporting_levels'][DansGuardian::REPORTING_LEVEL_CUSTOM]);
            
        // Load views
        //-----------

        $this->page->view_form('content_filter/policy/general', $data, lang('content_filter_general_settings'));
    }
}
