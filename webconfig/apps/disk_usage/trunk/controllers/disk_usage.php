<?php

/**
 * Disk Usage controller.
 *
 * @category   Apps
 * @package    Disk_Usage
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/disk_usage/
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

use \clearos\apps\disk_usage\Disk_Usage as Disk_Usage_Class;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Disk Usage controller.
 *
 * @category   Apps
 * @package    Disk_Usage
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/disk_usage/
 */

class Disk_Usage extends ClearOS_Controller
{
    /**
     * Disk Usage default controller
     *
     * @return view
     */

    function index($encoded_path = NULL, $xcoord = 0, $ycoord = 0)
    {
        // Load dependencies
        //------------------

        $this->lang->load('disk_usage');
        $this->load->library('disk_usage/Philesight');

        // Set default to / if path is not specified, decode
        //--------------------------------------------------

        if (is_null($encoded_path))
            $encoded_path = strtr(base64_encode('/'),  '+/=', '-_.');

        $real_path = base64_decode(strtr($encoded_path, '-_.', '+/='));

        // Validation
        //-----------
        // This is to catch security shenanigans, end users won't see this.

        try {
            if ($this->philesight->validate_path($real_path))
                throw new \Exception(lang('disk_usage_path_invalid'));

            if ($this->philesight->validate_coordinate($xcoord))
                throw new \Exception(lang('disk_usage_coordinate_invalid'));

            if ($this->philesight->validate_coordinate($ycoord))
                throw new \Exception(lang('disk_usage_coordinate_invalid'));
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load view data
        //---------------

        try {
            $data['initialized'] = $this->philesight->is_initialized();
            $data['real_path'] = $real_path;
            $data['encoded_path'] = $encoded_path;
            $data['xcoord'] = $xcoord;
            $data['ycoord'] = $ycoord;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Update database if not initialized
        //-----------------------------------

        if (! $data['initialized'])
            $this->philesight->update_database();

        // Load view
        //----------

        $this->page->view_form('disk_usage', $data, lang('disk_usage_app_name'));
    }

    /**
     * Returns image for given path.
     *
     * @param string $encoded_path encoded path
     *
     * @return PNG image
     */

    function get_image($encoded_path)
    {
        $this->load->library('disk_usage/Philesight');

        $real_path = base64_decode(strtr($encoded_path, '-_.', '+/='));

        // Validation
        //-----------

        if ($this->philesight->validate_path($real_path))
            return;

        header("Content-type: image/png");
        echo $this->philesight->get_image($real_path);
    }

    /**
     * Gets state of database.
     *
     * @return state
     */

    function get_state()
    {
        // Load dependencies
        //------------------

        $this->load->library('disk_usage/Philesight');

        // Run synchronize
        //----------------

        try {
            $data['error_code'] = 0;
            $data['state'] = $this->philesight->is_initialized();
        } catch (Exception $e) {
            $data['error_code'] = clearos_exception_code($e);
            $data['error_message'] = clearos_exception_message($e);
        }

        // Return status message
        //----------------------

        $this->output->set_header("Content-Type: application/json");
        $this->output->set_output(json_encode($data));
    }
}
