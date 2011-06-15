<?php

/**
 * Account import/export controller.
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

use \clearos\apps\account_import\Account_Import as Import;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Account Import/Export controller.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

class Account_Import extends ClearOS_Controller
{

    /**
     * Account_Import default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->helper('number');
        $this->load->library('account_import/Account_Import');
        $this->lang->load('account_import');

        $data['import_ready'] = $this->account_import->is_csv_file_uploaded();

        if ($data['import_ready']) {
            $data['filename'] = IMPORT::FILE_CSV;
            $data['size'] = byte_format($this->account_import->get_csv_size(), 1);
            //$data['number_of_records'] = $this->account_import->get_number_of_records();
        }

        // Load views
        //-----------

        $this->page->view_form('overview', $data, lang('account_import_account_import'));
    }

    /**
     * Account_Import download template controller
     *
     * @return view
     */

    function template()
    {
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=import2.csv');
        //header('Content-Disposition: inline; filename=import.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile('/var/clearos/framework/cache/import2.csv');

    }

}
