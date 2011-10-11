<?php

/**
 * Flexshare Web controller.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
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

// Classes
//--------

use \clearos\apps\flexshare\Flexshare as Flexshare;

// TODO for Pete:  Why does enabling line below give:
// Fatal error: Call to a member function load() on a non-object i
// Is it needed?
//clearos_load_library('flexshare/Flexshare');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Flexshare Web controller.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
 */

class Web extends ClearOS_Controller
{
    /**
     * Flexshare Web default controller.
     */

    function index($share)
    {
        $this->configure($share);
    }

    /**
     * Flexshare edit view.
     *
     * @param string $share share
     *
     * @return view
     */

    function configure($share)
    {
        // Load libraries
        //---------------

        $this->load->library('web/Httpd');
        $this->load->library('flexshare/Flexshare');
        $this->lang->load('flexshare');

        $this->form_validation->set_policy('web_access', 'flexshare/Flexshare', 'validate_web_access', TRUE);
        if ($this->input->post('req_auth'))
            $this->form_validation->set_policy('realm', 'flexshare/Flexshare', 'validate_web_realm', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->flexshare->set_web_access($share, $this->input->post('web_access'));
                $this->flexshare->set_web_show_index($share, $this->input->post('show_index'));
                $this->flexshare->set_web_follow_sym_links($share, $this->input->post('follow_sym_links'));
                $this->flexshare->set_web_allow_ssi($share, $this->input->post('ssi'));
                $this->flexshare->set_web_htaccess_override($share, $this->input->post('htaccess'));
                $this->flexshare->set_web_req_ssl($share, $this->input->post('req_ssl'));
                $this->flexshare->set_web_override_port(
                    $share,
                    $this->input->post('override_port'),
                    (!$this->input->post('web_port') ? 80 : $this->input->post('web_port'))
                );
                $this->flexshare->set_web_req_auth($share, $this->input->post('req_auth'));
                $this->flexshare->set_web_php($share, $this->input->post('php'));
                $this->flexshare->set_web_cgi($share, $this->input->post('cgi'));
                if ($this->input->post('req_auth'))
                    $this->flexshare->set_web_realm($share, $this->input->post('realm'));
                // Set enabled after all parameters have been set
                $this->flexshare->set_web_enabled($share, $this->input->post('enabled'));
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e));
            }
        }

        // Load view data
        //--------------- 

        try {
            $data['share'] = $this->flexshare->get_share($share);
            $data['accessibility_options'] = $this->flexshare->get_web_access_options();
            $data['server_name'] = $this->httpd->get_server_name();

            $protocol = ($data['share']['WebReqSsl']) ? "https" : "http";

            if ($data['share']['WebOverridePort'])
                $data['server_url'] = array( 
                    $protocol . "://" . $data['server_name'] . ":" . $data['share']['WebPort'] . "/flexshare/$name",
                    $protocol . "://$name." . $data['server_name'] . ":" . $data['share']['WebPort']
                ); 
            else
                $data['server_url'] = array(
                    $protocol . "://" . $data['server_name'] . "/flexshare/$name",
                    $protocol . "://$name." . $data['server_name']
                ); 

            $data['server_url_options'] = array(
                0 => 'bob', 1 => 'joe'
            );
            // Default Port
            if ((int)$data['share']['WebPort'] == 0)
                $data['share']['WebPort'] = Flexshare::DEFAULT_PORT_WEB;


        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('flexshare/web', $data, lang('flexshare_web'));
    }
}
