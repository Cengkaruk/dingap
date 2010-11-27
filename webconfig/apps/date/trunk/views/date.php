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

$this->load->library('form_validation');
$this->lang->load('date');

echo form_open('date');
echo form_fieldset(lang('date_time_zone'));
echo cos_form_dropdown('timezone', $timezones, $timezone, lang('date_time_zone'));
echo "
	<div>" .
		form_submit_update('submit') . "
	</div>
";
echo form_fieldset_close();
echo form_close();


echo form_fieldset(lang('date_time_and_date'));
echo "
	<div>" .
		form_label(lang('date_date'), 'date') . " " .
		$date . "
	</div>
	<div>" .
		form_label(lang('date_time'), 'time') . " " .
		$time . " <span id='result'></span>
	</div>
	<div>" .
		form_submit_custom('synchronize', 'sync', 'Synchronize Now') . "
	</div>
";
echo form_fieldset_close();

// vim: ts=4
