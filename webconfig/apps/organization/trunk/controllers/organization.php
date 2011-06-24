<?php

/**
 * Organization controller.
 *
 * @category   Apps
 * @package    Organization
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/organization/
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
 * Organization controller.
 *
 * @category   Apps
 * @package    Organization
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/organization/
 */

class Organization extends ClearOS_Controller
{
    /**
     * Organization default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('base/Country');
        $this->load->library('organization/Organization');
        $this->lang->load('organization');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('organization', 'organization/Organization', 'validate_organization', TRUE);
        $this->form_validation->set_policy('unit', 'organization/Organization', 'validate_unit');
        $this->form_validation->set_policy('street', 'organization/Organization', 'validate_street', TRUE);
        $this->form_validation->set_policy('city', 'organization/Organization', 'validate_city', TRUE);
        $this->form_validation->set_policy('region', 'organization/Organization', 'validate_region', TRUE);
        $this->form_validation->set_policy('country', 'organization/Organization', 'validate_country', TRUE);
        $this->form_validation->set_policy('postal_code', 'organization/Organization', 'validate_postal_code', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->organization->set_organization($this->input->post('organization'));
                $this->organization->set_unit($this->input->post('unit'));
                $this->organization->set_street($this->input->post('street'));
                $this->organization->set_city($this->input->post('city'));
                $this->organization->set_region($this->input->post('region'));
                $this->organization->set_country($this->input->post('country'));
                $this->organization->set_postal_code($this->input->post('postal_code'));

                $this->page->set_status_updated();
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['organization'] = $this->organization->get_organization();
            $data['unit'] = $this->organization->get_unit();
            $data['street'] = $this->organization->get_street();
            $data['city'] = $this->organization->get_city();
            $data['region'] = $this->organization->get_region();
            $data['country'] = $this->organization->get_country();
            $data['postal_code'] = $this->organization->get_postal_code();
            $data['countries'] = $this->country->get_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('organization', $data, lang('organization_app_name'));
    }
}
