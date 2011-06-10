<?php

/**
 * Firewall port forwarding controller.
 *
 * @category   Apps
 * @package    Port_Forwarding
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/port_forwarding/
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
 * Firewall port forwarding controller.
 *
 * @category   Apps
 * @package    Port_Forwarding
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/port_forwarding/
 */

class Port_Forwarding extends ClearOS_Controller
{
    /**
     * Port forwarding overview.
     *
     * @return view
     */

    function index()
    {
        $this->load->library('port_forwarding/Port_Forwarding');
        $this->lang->load('port_forwarding');

        // Load view data
        //---------------

        try {
            $data['ports'] = $this->port_forwarding->get_ports();
            $data['ranges'] = $this->port_forwarding->get_port_ranges();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        // FIXME: $options['type'] = 'report';
        $this->page->view_form('port_forwarding/summary', $data, lang('port_forwarding_port_forwarding'), $options);
    }

    /**
     * Add allow rule.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        $this->load->library('port_forwarding/Port_Forwarding');
        $this->lang->load('port_forwarding');
        $this->lang->load('base');

        // Set validation rules
        //---------------------

        $is_action = FALSE;

        if ($this->input->post('submit_standard')) {
            $this->form_validation->set_policy('service', 'port_forwarding/Port_Forwarding', 'validate_service', TRUE);
            $this->form_validation->set_policy('service_ip', 'port_forwarding/Port_Forwarding', 'validate_address', TRUE);
            $is_action = TRUE;
        } else if ($this->input->post('submit_port')) {
            $this->form_validation->set_policy('port_nickname', 'port_forwarding/Port_Forwarding', 'validate_name', TRUE);
            $this->form_validation->set_policy('port_protocol', 'port_forwarding/Port_Forwarding', 'validate_protocol', TRUE);
            $this->form_validation->set_policy('port_from', 'port_forwarding/Port_Forwarding', 'validate_port', TRUE);
            $this->form_validation->set_policy('port_to', 'port_forwarding/Port_Forwarding', 'validate_port', TRUE);
            $this->form_validation->set_policy('port_ip', 'port_forwarding/Port_Forwarding', 'validate_address', TRUE);
            $is_action = TRUE;
        } else if ($this->input->post('submit_range')) {
            $this->form_validation->set_policy('range_nickname', 'port_forwarding/Port_Forwarding', 'validate_name', TRUE);
            $this->form_validation->set_policy('range_protocol', 'port_forwarding/Port_Forwarding', 'validate_protocol', TRUE);
            $this->form_validation->set_policy('range_start', 'port_forwarding/Port_Forwarding', 'validate_port', TRUE);
            $this->form_validation->set_policy('range_end', 'port_forwarding/Port_Forwarding', 'validate_port', TRUE);
            $this->form_validation->set_policy('range_ip', 'port_forwarding/Port_Forwarding', 'validate_address', TRUE);
            $is_action = TRUE;
        }

        // Handle form submit
        //-------------------

        if ($is_action && $this->form_validation->run()) {
            try {
                if ($this->input->post('submit_standard')) {
                    $this->port_forwarding->add_standard_service(
                        preg_replace('/\//', '_', $this->input->post('service')),
                        $this->input->post('service'),
                        $this->input->post('service_ip')
                    );
                } else if ($this->input->post('submit_port')) {
                    $this->port_forwarding->add_port(
                        $this->input->post('port_nickname'),
                        $this->input->post('port_protocol'),
                        $this->input->post('port_from'),
                        $this->input->post('port_to'),
                        $this->input->post('port_ip')
                    );
                } else if ($this->input->post('submit_range')) {
                    $this->port_forwarding->add_port_range(
                        $this->input->post('range_nickname'),
                        $this->input->post('range_protocol'),
                        $this->input->post('range_start'),
                        $this->input->post('range_end'),
                        $this->input->post('range_ip')
                    );
                }

                $this->port_forwarding->reset(TRUE);

                $this->page->set_status_added();
                redirect('/port_forwarding');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        $data['mode'] = $form_mode;
        $data['protocols'] = $this->port_forwarding->get_basic_protocols();
        $services = $this->port_forwarding->get_standard_service_list();

        // TODO: PPTP and IPsec are not supported - a hack below

        $data['services'] = array();

        foreach ($services as $service)
            if (($service !== 'IPsec') && ($service !== 'PPTP'))
                $data['services'][] = $service;

        // Load the views
        //---------------

        $this->page->view_form('port_forwarding/add', $data, lang('base_add'));
    }

    /**
     * Delete port rule confirmation.
     *
     * @param string  $protocol  protocol
     * @param integer $from_port from port
     * @param integer $to_port   to port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function delete($protocol, $from_port, $to_port, $ip)
    {
        $confirm_uri = '/app/port_forwarding/destroy/' . $protocol . '/' . $from_port . '/' . $to_port . '/' . $ip;
        $cancel_uri = '/app/port_forwarding';
        // FIXME: cleanup look and feel
        $items = array($protocol . ' ' . $from_port . ' > ' . $to_port . ' - ' . $ip);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Delete port range rule confirmation.
     *
     * @param string  $protocol  protocol
     * @param integer $low_port  low port
     * @param integer $high_port high port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function delete_range($protocol, $low_port, $high_port, $ip)
    {
        $confirm_uri = '/app/port_forwarding/destroy_range/' . $protocol . '/' . $low_port . '/' . $high_port . '/' . $ip;
        $cancel_uri = '/app/port_forwarding';
        // FIXME: cleanup look and feel
        $items = array($protocol . ' ' . $low_port . ':' . $high_port . ' - ' . $ip);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys port rule.
     *
     * @param string  $protocol  protocol
     * @param integer $from_port from port
     * @param integer $to_port   to port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function destroy($protocol, $from_port, $to_port, $ip)
    {
        // Load libraries
        //---------------

        $this->load->library('port_forwarding/Port_Forwarding');

        // Handle form submit
        //-------------------

        try {
            $this->port_forwarding->delete_port($protocol, $from_port, $to_port, $ip);
            $this->port_forwarding->reset(TRUE);

            $this->page->set_status_deleted();
            redirect('/port_forwarding');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Destroys port range rule.
     *
     * @param string  $protocol  protocol
     * @param integer $low_port  low port
     * @param integer $high_port high port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function destroy_range($protocol, $low_port, $high_port, $ip)
    {
        // Load libraries
        //---------------

        $this->load->library('port_forwarding/Port_Forwarding');

        // Handle form submit
        //-------------------

        try {
            $this->port_forwarding->delete_port_range($protocol, $low_port, $high_port, $ip);
            $this->port_forwarding->reset(TRUE);

            $this->page->set_status_deleted();
            redirect('/port_forwarding');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disables port rule.
     *
     * @param string  $protocol  protocol
     * @param integer $from_port from port
     * @param integer $to_port   to port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function disable($protocol, $from_port, $to_port, $ip)
    {
        try {
            $this->load->library('port_forwarding/Port_Forwarding');

            $this->port_forwarding->set_port_state(FALSE, $protocol, $from_port, $to_port, $ip);
            $this->port_forwarding->reset(TRUE);

            $this->page->set_status_disabled();
            redirect('/port_forwarding');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Disables range rule.
     *
     * @param string  $protocol  protocol
     * @param integer $low_port  low port
     * @param integer $high_port high port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function disable_range($protocol, $low_port, $high_port, $ip)
    {
        try {
            $this->load->library('port_forwarding/Port_Forwarding');

            $this->port_forwarding->set_port_range_state(FALSE, $protocol, $low_port, $high_port, $ip);
            $this->port_forwarding->reset(TRUE);

            $this->page->set_status_disabled();
            redirect('/port_forwarding');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enables port rule.
     *
     * @param string  $protocol  protocol
     * @param integer $from_port from port
     * @param integer $to_port   to port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function enable($protocol, $from_port, $to_port, $ip)
    {
        try {
            $this->load->library('port_forwarding/Port_Forwarding');

            $this->port_forwarding->set_port_state(TRUE, $protocol, $from_port, $to_port, $ip);
            $this->port_forwarding->reset(TRUE);

            $this->page->set_status_enabled();
            redirect('/port_forwarding');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Enables range rule.
     *
     * @param string  $protocol  protocol
     * @param integer $low_port  low port
     * @param integer $high_port high port
     * @param string  $ip        IP address
     *
     * @return view
     */

    function enable_range($protocol, $low_port, $high_port, $ip)
    {
        try {
            $this->load->library('port_forwarding/Port_Forwarding');

            $this->port_forwarding->set_port_range_state(TRUE, $protocol, $low_port, $high_port, $to);
            $this->port_forwarding->reset(TRUE);

            $this->page->set_status_enabled();
            redirect('/port_forwarding');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
