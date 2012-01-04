<?php

/**
 * Samba initialization check.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
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
 * Samba initialization check.
 *
 * @category   Apps
 * @package    Samba
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/samba/
 */

class Status extends ClearOS_Controller
{
    /**
     * Default controller.
     *
     * @return view
     */

    function index()
    {
        if (! $this->is_initialized())
            $this->widget();
    }

    /**
     * Returns state of account system
     *
     * @return boolean state of accounts driver
     */

    function is_initialized()
    {
        // Load libraries and grab status information
        //-------------------------------------------

        try {
            $this->load->library('samba/OpenLDAP_Driver');

            $initialized = $this->openldap_driver->is_directory_initialized();
        } catch (Accounts_Driver_Not_Set_Exception $e) {
            $this->page->view_exception($e);
            return FALSE;
        }

        return $initalized;
    }

    /**
     * Status widget.
     *
     * @return view accoutns status  view
     */

    function widget($app_redirect)
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');

        // Load views
        //-----------

        if (! preg_match('/^([a-zA-Z0-9_])*/', $app_redirect))
            return;

        $options['javascript'] = array(clearos_app_htdocs('accounts') . '/status.js.php');
        $data['app_redirect'] = $app_redirect;

        $this->page->view_form('samba/initialize', $data, lang('base_initialize'), $options);
    }

    /**
     * Returns accounts status.
     *
     * @return JSON accounts status information
     */

    function get_info()
    {
        // Load view data
        //---------------

        $data['marketplace_installed'] = (clearos_app_installed('marketplace')) ? TRUE : FALSE;
        $data['openldap_directory_installed'] = (clearos_app_installed('openldap_directory')) ? TRUE : FALSE;
        $data['openldap_driver_installed'] = (clearos_library_installed('openldap_directory/OpenLDAP')) ? TRUE : FALSE;
        $data['ad_installed'] = (clearos_app_installed('active_directory')) ? TRUE : FALSE;

        try {
            $this->load->factory('accounts/Accounts_Factory');

            $status = $this->accounts->get_system_status();

            if ($status == Accounts_Engine::STATUS_ONLINE) {
                $data['status_message'] = lang('accounts_account_information_is_online');
                $data['status'] = 'online';
            } else if ($status == Accounts_Engine::STATUS_OFFLINE) {
                $data['status_message'] = lang('accounts_account_information_is_offline');
                $data['status'] = 'offline';
            } else if ($status == Accounts_Engine::STATUS_UNINITIALIZED) {
                $data['status_message'] = lang('accounts_account_system_is_not_initialized');
                $data['status'] = 'uninitialized';
            } else if ($status == Accounts_Engine::STATUS_INITIALIZING) {
                $data['status_message'] = lang('accounts_account_system_is_initializing');
                $data['status'] = 'initializing';
            }

            $data['driver_selected'] = TRUE;
            $data['code'] = 0;
        } catch (Accounts_Driver_Not_Set_Exception $e) {
            $data['driver_selected'] = FALSE;
            $data['status_message'] = '';
            $data['status'] = 'no_driver';
            $data['code'] = 0;
        } catch (Exception $e) {
            $data['code'] = 1;
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
