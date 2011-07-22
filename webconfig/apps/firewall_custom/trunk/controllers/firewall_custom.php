<?php

/**
 * Firewall Custom controller.
 *
 * @category   Apps
 * @package    Firewall_Custom
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
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

use \clearos\apps\firewall_custom\Firewall_Custom as Firewall_Custom_Class;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall Custom controller.
 *
 * @category   Apps
 * @package    Firewall_Custom
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

class Firewall_Custom extends ClearOS_Controller
{

    /**
     * Firewall Custom default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');

        // Load views
        //-----------

        $views = array('firewall_custom/summary');

        $data['rules'] = $this->firewall_custom->get_rules();

        $this->page->view_form('summary', $data, lang('firewall_custom_overview'));
    }

    /**
     * Add rule.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $is_action = FALSE;

        $this->form_validation->set_policy('iptables', 'firewall_custom/Firewall_Custom', 'validate_iptables', TRUE);
        $this->form_validation->set_policy('description', 'firewall_custom/Firewall_Custom', 'validate_description', TRUE);

        // Handle form submit
        //-------------------

        if ($this->form_validation->run()) {
            try {
                $this->firewall_custom->add_rule(
                    $this->input->post('iptables'),
                    $this->input->post('description'),
                    $this->input->post('enabled'),
                    $this->input->post('priority')
                );

                $this->page->set_status_added();
                redirect('/firewall_custom');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the views
        //---------------

        $this->page->view_form('firewall_custom/add', $data, lang('base_add'));
    }

    /**
     * Delete custom rule.
     *
     * @param integer $line line number
     *
     * @return view
     */

    function delete($line)
    {
        $confirm_uri = '/app/firewall_custom/destroy/' . $line;
        $cancel_uri = '/app/firewall_custom';

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');

        $rule = $this->firewall_custom->get_rule($line);
        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, array($rule['description']));
    }

    /**
     * Destroys rule.
     *
     * @param string $line line
     *
     * @return view
     */

    function destroy($line)
    {
        // Load libraries
        //---------------

        $this->load->library('firewall_custom/Firewall_Custom');
        $this->lang->load('firewall_custom');

        // Handle form submit
        //-------------------

        try {
            $this->firewall_custom->delete_rule($line);

            $this->page->set_status_deleted();
            redirect('/firewall_custom');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

}
