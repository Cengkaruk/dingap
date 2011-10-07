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

        $this->load->library('flexshare/Flexshare');
        $this->lang->load('flexshare');

        $this->form_validation->set_policy('enabled', 'flexshare/Flexshare', 'validate_web_enabled', TRUE);
        $this->form_validation->set_policy('server_url', 'flexshare/Flexshare', 'validate_web_server_url', TRUE);
        $this->form_validation->set_policy('req_ssl', 'flexshare/Flexshare', 'validate_web_req_ssl', TRUE);
        //$this->form_validation->set_policy('override_port', 'flexshare/Flexshare', 'validate_web_override_port', TRUE);
        //$this->form_validation->set_policy('allow_passive', 'flexshare/Flexshare', 'validate_web_allow_passive', TRUE);
        $this->form_validation->set_policy('group_permission', 'flexshare/Flexshare', 'validate_web_group_permission', TRUE);
        $this->form_validation->set_policy('group_greeting', 'flexshare/Flexshare', 'validate_web_group_greeting', FALSE);
        $this->form_validation->set_policy('allow_anonymous', 'flexshare/Flexshare', 'validate_web_allow_anonymous', TRUE);
        if ($this->input->post('allow_anonymous'))
            $this->form_validation->set_policy('anonymous_permission', 'flexshare/Flexshare', 'validate_web_anonymous_permission', TRUE);
        $this->form_validation->set_policy('anonymous_greeting', 'flexshare/Flexshare', 'validate_web_anonymous_greeting', FALSE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

#echo field_toggle_enable_disable('req_ssl', $share['WebReqSsl'], lang('flexshare_web_require_ssl'), $read_only);
#echo field_toggle_enable_disable('override_port', $share['WebOverridePort'], lang('flexshare_web_override_port'), $read_only);
#echo field_input('port', $share['WebPort'], lang('flexshare_web_port'), $read_only);
#echo field_toggle_enable_disable('allow_passive', $share['WebAllowPassive'], lang('flexshare_web_allow_passive'), $read_only);
#echo field_input('passive_min_port', $share['WebPassivePortMin'], lang('flexshare_web_min_port'), $read_only);
#echo field_input('passive_max_port', $share['WebPassivePortMax'], lang('flexshare_web_max_port'), $read_only);
#echo field_dropdown('group_permission', $group_permission_options, $share['WebGroupPermission'], lang('flexshare_web_group_permissions'), $read_only);
#echo field_textarea('group_greeting', $share['WebGroupGreeting'], lang('flexshare_web_group_greeting'), $read_only);
#echo field_toggle_enable_disable('allow_anonymous', $share['WebAllowAnonymous'], lang('flexshare_web_allow_anonymous'), $read_only);
#echo field_dropdown('anonymous_permission', $anonymous_permission_options, $share['WebAnonymousPermission'], lang('flexshare_web_anonymous_permissions'), $read_only);
#echo field_textarea('anonymous_greeting', $share['WebAnonymousGreeting'], lang('flexshare_web_anonymous_greeting'), $read_only);

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->flexshare->set_web_server_url($share, $this->input->post('server_url'));
                $this->flexshare->set_web_req_ssl($share, $this->input->post('req_ssl'));
                $this->flexshare->set_web_override_port(
                    $share,
                    $this->input->post('override_port'),
                    (!$this->input->post('port') ? 2121 : $this->input->post('port'))
                );
                $this->flexshare->set_web_allow_passive(
                    $share,
                    $this->input->post('allow_passive'),
                    $this->input->post('passive_min_port'),
                    $this->input->post('passive_max_port')
                );
                $this->flexshare->set_web_group_permission($share, $this->input->post('group_permission'));
                $this->flexshare->set_web_group_greeting($share, $this->input->post('group_greeting'));
                $this->flexshare->set_web_allow_anonymous($share, $this->input->post('allow_anonymous'));
                $this->flexshare->set_web_anonymous_permission($share, $this->input->post('anonymous_permission'));
                $this->flexshare->set_web_anonymous_greeting($share, $this->input->post('anonymous_greeting'));
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

            // Default Port
            if ((int)$data['share']['WebPort'] == 0)
                $data['share']['WebPort'] = Flexshare::DEFAULT_PORT_Web;

            // Passive port range
            if ((int)$data['share']['WebPassivePortMin'] == 0)
                $data['share']['WebPassivePortMin'] = Flexshare::Web_PASV_MIN;

            if ((int)$data['share']['WebPassivePortMax'] == 0)
                $data['share']['WebPassivePortMax'] = Flexshare::Web_PASV_MAX;

            $data['group_permission_options'] = $this->flexshare->get_web_permission_options();
            $data['anonymous_permission_options'] = $this->flexshare->get_web_permission_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('flexshare/web', $data, lang('flexshare_web'));
    }
}
