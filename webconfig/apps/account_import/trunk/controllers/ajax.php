<?php

/**
 * Account import/export ajax controller.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
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

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Account Import/Export ajax controller.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

class Ajax extends ClearOS_Controller
{

    /**
     * Account_Import AJAX default controller
     *
     * @return view
     */

    function index()
    {
        echo "These aren't the droids you're looking for...";
    }

    /**
     * Get progress from import.
     *
     * @return JSON
     */

    function get_progress()
    {
        clearos_profile(__METHOD__, __LINE__);

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
//        header('Content-type: application/json');

        // Load dependencies
        //------------------

        $this->load->library('account_import/Account_Import');
        $this->lang->load('account_import');

        try {
            $status = $this->account_import->get_progress();
            $summary = array();
            $progress = 0;
            foreach ($status as $line) {
                $json = json_decode($line);
                $summary[] = $json->msg;
                $code = $json->code;
                $progress = $json->progress;
            }
            echo json_encode(Array('code' => $code, 'summary' => $summary, 'progress' => $progress));
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }
}
