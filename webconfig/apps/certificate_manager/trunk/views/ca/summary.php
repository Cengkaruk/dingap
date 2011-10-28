<?php

/**
 * Certificate authority view.
 *
 * @category   ClearOS
 * @package    Certificate_Manager
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/certificate_manager/
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

$this->lang->load('certificate_manager');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('certificate_manager_certificate'),
    lang('base_expires'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$item['title'] = lang('certificate_manager_certificate_authority');
$item['action'] = '/app/certificate_manager/ca/edit';
$item['anchors'] = button_set(
    array(anchor_edit('/app/certificate_manager/certificate/edit/ca', 'high'))
);
$item['details'] = array(
    lang('certificate_manager_certificate_authority'),
    $attributes['expireNotAfter'],
);

$items[] = $item;

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('certificate_manager_certificate_authority'),
    array(),
    $headers,
    $items
);
