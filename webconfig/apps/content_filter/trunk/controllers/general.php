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
                $this->dansguardian->set_blacklists(
                    array_keys($this->input->post('blacklist')),
                    $policy
                );
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

            $data['block_ip_domains'] = $this->dansguardian->get_block_ip_domains($policy);
            $data['blanket_block'] = $this->dansguardian->get_blanket_block($policy);

            $data['group_modes'] = $this->dansguardian->get_possible_filter_modes();
            $data['reporting_levels'] = $this->dansguardian->get_possible_reporting_levels();
            $data['naughtyness_limits'] = $this->dansguardian->get_possible_naughtyness_limits();

            $configuration = $this->dansguardian->get_filter_group_configuration($policy);
            $data['group_mode'] = $configuration['groupmode'];
            $data['disable_content_scan'] = $configuration['disablecontentscan'];
            $data['deep_url_analysis'] = $configuration['deepurlanalysis'];
            $data['block_downloads'] = $configuration['blockdownloads'];
            $data['naughtyness_limit'] = $configuration['naughtynesslimit'];
            $data['reporting_level'] = $configuration['reportinglevel'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('content_filter/policy/general', $data, lang('content_filter_general_settings'));
    }
}
