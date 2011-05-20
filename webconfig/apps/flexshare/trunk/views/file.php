<?php

/**
 * Flexshare file view/edit view.
 *
 * @category   Apps
 * @package    Flexshare
 * @subpackage Views
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

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->load->language('flexshare');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $form_path = '/flexshare/file/edit';
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/flexshare/'),
    );
} else {
    $read_only = TRUE;
    $form_path = '/flexshare/file/view';
    $buttons = array(
        anchor_cancel('/app/flexshare/'),
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path . '/' . $share);
echo form_header(lang('flexshare_file'));

///////////////////////////////////////////////////////////////////////////////
// Form fields
///////////////////////////////////////////////////////////////////////////////

echo field_toggle_enable_disable('status', $status, lang('base_status'), $read_only);
echo field_input('path', $path, lang('flexshare_windows_path'), TRUE);
echo field_input('comment', $comment, lang('flexshare_comment'), $read_only);

echo button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
