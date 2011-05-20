<?php

/**
 * Flexshare summary view.
 *
 * @category   ClearOS
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

$this->lang->load('flexshare');

///////////////////////////////////////////////////////////////////////////////
// View modes
///////////////////////////////////////////////////////////////////////////////

if ($mode === 'view') {
    $read_only = TRUE;
} else {
    $read_only = FALSE;
}

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
	lang('flexshare_name'),
	lang('base_description'),
	lang('flexshare_group'),
	lang('flexshare_access_options')
);

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($flexshares as $share => $info) {

    if ($read_only) {
        $buttons = array(
            anchor_view('/app/flexshare/view/' . $share),
        );
    } else {
        $buttons = array(
            anchor_edit('/app/flexshare/edit/' . $share),
            anchor_delete('/app/flexshare/delete/' . $share)
        );
    }

	$item['title'] = $share;
	$item['action'] = '/app/flexshare/edit/' . $share;
	$item['anchors'] = button_set($buttons);
	$item['details'] = array(
		$share,
		$info['display_name'],
		$share,
	);

	$items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
	lang('flexshare_flexshares'),
	$anchors,
	$headers,
	$items
);
