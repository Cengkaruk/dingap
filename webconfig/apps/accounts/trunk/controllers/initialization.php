<?php

/**
 * Accounts initialization check.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
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

use \clearos\apps\accounts\Accounts_Driver_Not_Set_Exception as Accounts_Driver_Not_Set_Exception;
use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Accounts initialization check.
 *
 * @category   Apps
 * @package    Accounts
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
 */

class Initialization extends ClearOS_Controller
{
    protected $daemon_name = NULL;
    protected $app_name = NULL;

    /**
     * Constructor.
     */

    function __construct($daemon_name, $app_name)
    {
        $this->daemon_name = $daemon_name;
        $this->app_name = $app_name;
    }

    /**
     * Default controller.
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('base');

        $data['daemon_name'] = $this->daemon_name;
        $data['app_name'] = $this->app_name;

        // Load views
        //-----------

        $options['javascript'] = array(clearos_app_htdocs('accounts') . '/initialization.js.php');

        $this->page->view_form('accounts/initialization', $data, lang('base_server_status'), $options);
    }


    /**
     * Returns accounts status.
     */

    function get_info()
    {
        // Load view data
        //---------------

        $data['marketplace_installed'] = (clearos_marketplace_installed()) ? TRUE : FALSE;
        $data['directory_server_installed'] = (clearos_app_installed('directory_server')) ? TRUE : FALSE;
        $data['openldap_installed'] = (clearos_app_installed('openldap_directory')) ? TRUE : FALSE;
        $data['ad_installed'] = (clearos_app_installed('active_directory')) ? TRUE : FALSE;

// FIXME
$data['directory_server_installed'] = FALSE;

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

    /**
     * Daemon status.
     */

    function status()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        $this->load->library('base/Daemon', $this->daemon_name);

        $status['status'] = $this->daemon->get_status();

        echo json_encode($status);
    }

    /**
     * Daemon start.
     */

    function start()
    {
        $this->load->library('base/Daemon', $this->daemon_name);

        try {
            $this->daemon->set_running_state(TRUE);
            $this->daemon->set_boot_state(TRUE);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Daemon stop.
     */

    function stop()
    {
        $this->load->library('base/Daemon', $this->daemon_name);

        try {
            $this->daemon->set_running_state(FALSE);
            $this->daemon->set_boot_state(FALSE);
        } catch (Exception $e) {
            //
        }
    }
}
