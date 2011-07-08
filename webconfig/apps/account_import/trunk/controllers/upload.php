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

use \clearos\apps\account_import\Account_Import as Account_Import;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * File upload controller.
 *
 * @category   Apps
 * @package    Account Import
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/account_import/
 */

class Upload extends ClearOS_Controller
{

    function __construct()
    {
        parent::__construct();
        //$this->load->helper(array('form', 'url'));
    }

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->helper('number');
        $this->load->library('account_import/Account_Import');
        $this->lang->load('account_import');

        // Handle form submit
        //-------------------

        if ($this->input->post('reset')) {
            try {
                $this->account_import->delete_csv_file();
                redirect('/account_import');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        } if ($this->input->post('start')) {
            try {
                $this->account_import->import();
                redirect('/account_import/progress');
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e));
                redirect('/account_import/progress');
            }
        }
        $config['upload_path'] = CLEAROS_TEMP_DIR;
        $config['allowed_types'] = 'csv';
        $config['overwrite'] = TRUE;
        $config['file_name'] = Account_Import::FILE_CSV;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('csv_file')) {
            $this->page->set_message($this->upload->display_errors());
        } else {
            $upload = $this->upload->data();
            $this->account_import->set_csv_file($upload['file_name']);
            $data['filename'] = $upload['file_name'];
            $data['import_ready'] = TRUE;
            $data['size'] = byte_format($this->account_import->get_csv_size(), 1);
            $data['number_of_records'] = $this->account_import->get_number_of_records();
        }
        $this->page->view_form('overview', $data, lang('account_import_account_import'));
    }
}
