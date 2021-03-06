<?php

/**
 * Antiphishing view.
 *
 * @category   ClearOS
 * @package    Antiphishing
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antiphishing/
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

$this->lang->load('antivirus');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_mode === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/antiphishing')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/antiphishing/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('antiphishing');
echo form_header(lang('base_settings'));

echo field_toggle_enable_disable('signatures', $signatures, lang('antiphishing_signature_engine'), $read_only);
echo field_toggle_enable_disable('scan_urls', $scan_urls, lang('antiphishing_heuristics_engine'), $read_only);
echo field_toggle_enable_disable('block_ssl_mismatch', $block_ssl_mismatch, lang('antiphishing_block_ssl_mismatch'), $read_only);
echo field_toggle_enable_disable('block_cloak', $block_cloak, lang('antiphishing_block_cloaked'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
