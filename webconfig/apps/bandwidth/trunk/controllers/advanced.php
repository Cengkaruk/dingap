<?php

/**
 * Bandwidth advanced rules controller.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
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

use \clearos\apps\bandwidth\Bandwidth as Bandwidth;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Bandwidth advanced rules controller.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
 */

class Advanced extends ClearOS_Controller
{
    /**
     * Port forwarding overview.
     *
     * @return view
     */

    function index()
    {
        $this->lang->load('bandwidth');
        $this->load->library('bandwidth/Bandwidth');

        // Load view data
        //---------------

        try {
            $data['rules'] = $this->bandwidth->get_bandwidth_rules(Bandwidth::TYPE_ADVANCED);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('bandwidth/advanced/summary', $data, lang('bandwidth_advanced_rules'));
    }

    /**
     * Add advanced bandwidth rule.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->lang->load('base');
        $this->lang->load('bandwidth');
        $this->load->library('bandwidth/Bandwidth');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('name', 'bandwidth/Bandwidth', 'validate_name', TRUE);
        $this->form_validation->set_policy('direction', 'bandwidth/Bandwidth', 'validate_advanced_direction', TRUE);
        $this->form_validation->set_policy('iface', 'bandwidth/Bandwidth', 'validate_interface', TRUE);

        $this->form_validation->set_policy('ip_type', 'bandwidth/Bandwidth', 'validate_type');
        $this->form_validation->set_policy('ip', 'bandwidth/Bandwidth', 'validate_ip');

        $this->form_validation->set_policy('port_type', 'bandwidth/Bandwidth', 'validate_type');
        $this->form_validation->set_policy('port', 'bandwidth/Bandwidth', 'validate_port');

        $this->form_validation->set_policy('rate', 'bandwidth/Bandwidth', 'validate_rate', TRUE);
        $this->form_validation->set_policy('ceiling', 'bandwidth/Bandwidth', 'validate_rate');
        $this->form_validation->set_policy('priority', 'bandwidth/Bandwidth', 'validate_priority', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            if ($this->input->post('direction') == Bandwidth::DIRECTION_FROM_NETWORK) {
                $download_rate = 0;
                $download_ceiling = 0;
                $upload_rate = $this->input->post('rate');
                $upload_ceiling = $this->input->post('ceiling');
            } else {
                $download_rate = $this->input->post('rate');
                $download_ceiling = $this->input->post('ceiling');
                $upload_rate = 0;
                $upload_ceiling = 0;
            }

            $ip = ($this->input->post('ip')) ? $this->input->post('ip') : '';

            try {
                $this->bandwidth->add_advanced_rule(
                    $this->input->post('name'),
                    $this->input->post('iface'),
                    $this->input->post('ip_type'),
                    $this->input->post('port_type'),
                    $ip,
                    $this->input->post('port'),
                    $this->input->post('priority'),
                    $upload_rate,
                    $upload_ceiling,
                    $download_rate,
                    $download_ceiling
                );

                $this->page->set_status_added();
                redirect('/bandwidth/advanced');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['types'] = $this->bandwidth->get_types();
            $data['directions'] = $this->bandwidth->get_advanced_directions();
            $data['priorities'] = $this->bandwidth->get_priorities();
            $data['interfaces'] = array_keys($this->bandwidth->get_interfaces());
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('bandwidth/advanced/item', $data, lang('base_add'));
    }

    /**
     * Delete bandwidth rule confirmation.
     *
     * @param string $name    rule name
     * @param string $service service
     * @param string $rate    rate
     *
     * @return view
     */

    function delete($name, $service, $rate)
    {
        $this->lang->load('bandwidth');

        $confirm_uri = '/app/bandwidth/advanced/destroy/' . $name;
        $cancel_uri = '/app/bandwidth/advanced';
        $items = array("$service - $rate " . lang('bandwidth_kilobits_s'));

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys bandwidth rule.
     *
     * @param string $name rule name
     *
     * @return view
     */

    function destroy($name)
    {
        // Load libraries
        //---------------

        $this->load->library('bandwidth/Bandwidth');

        // Handle form submit
        //-------------------

        try {
            $this->bandwidth->delete_advanced_rule($name);

            $this->page->set_status_deleted();
            redirect('/bandwidth/advanced');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disables bandwidth rule.
     *
     * @param string $name name
     *
     * @return view
     */

    function disable($name)
    {
        $this->load->library('bandwidth/Bandwidth');

        try {
            $this->bandwidth->set_advanced_rule_state(FALSE, $name);

            $this->page->set_status_disabled();
            redirect('/bandwidth/advanced');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enables bandwidth rule
     *
     * @param string $name name
     *
     * @return view
     */

    function enable($name)
    {
        $this->load->library('bandwidth/Bandwidth');

        try {
            $this->bandwidth->set_advanced_rule_state(TRUE, $name);

            $this->page->set_status_enabled();
            redirect('/bandwidth/advanced');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
