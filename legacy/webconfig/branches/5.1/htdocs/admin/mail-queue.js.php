<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
// Created by: Michel Scherhage [techlab@dhd4all.com]
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");

WebAuthenticate();

header('Content-Type:application/x-javascript');
?>

function SetChecked(prefix, checked)
{
	var allinputs = document.getElementsByTagName("input");

	for (var i = 0; i < allinputs.length; i++) {
		if (allinputs[i].id.substring(0,2) == 'id') {
			allinputs[i].checked = checked;
		}
	}
}

function SelectAll()
{
    SetChecked('id', true);
}

function SelectNone()
{
    SetChecked('id', false);
}

// vim: syntax=javascript ts=4
