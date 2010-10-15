<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////
// TODO: handle read-only entries
// TODO: move time server to a separate page

$this->lang->load('date');

echo form_open('date');
echo form_fieldset(lang('date_time_and_date'));
echo "
	<div>" .
		form_label(lang('date_date'), 'date') . " " .
		$date . " 
	</div>
	<div>" .
		form_label(lang('date_time'), 'time') . " " .
		$time . " 
	</div>
	<div>" .
		form_label(lang('date_time_zone'), 'time_zone') . " " .
		form_dropdown('timezone', $timezones, $timezone) . "
	</div>
	<div>" .
		form_submit('submit', LOCALE_LANG_UPDATE) . "
		<div id='sync'>" . "Synchronize Now" . "</div><span id='result'></span>
	</div>
";
echo form_fieldset_close();
echo form_close();

// vim: ts=4
