<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");

WebAuthenticate();
header('Content-Type:application/x-javascript');

?>
function ClearSelection(id)
{
	var obj = document.getElementById(id);

	if (!obj) return;

	for (i = 0; i < obj.options.length; i++)
		obj.options[i].selected = false; 
}

function SetChecked(prefix, checked)
{
	var obj;

	for (i = 0; ; i++) {
		obj = document.getElementById(prefix + i);

		if (!obj) break;

		obj.checked = checked;
	}
}

function SelectAllExtensions()
{
	SetChecked('file', true);
}

function SelectNoExtensions()
{
	SetChecked('file', false);
}

function SelectAllLists()
{
	SetChecked('list', true);
}

function SelectNoLists()
{
	SetChecked('list', false);
}

function SelectAllMimes()
{
	SetChecked('mime', true);
}

function SelectNoMimes()
{
	SetChecked('mime', false);
}

function SelectAllPhrases()
{
	SetChecked('phrase', true);
}

function SelectNoPhrases()
{
	SetChecked('phrase', false);
}

function SetTextInput(id, text, focus)
{
	var obj = document.getElementById(id);

	if (!obj) return;

	if (focus) {
		if (obj.value == text) obj.value = '';
	} else {
		if (obj.value == '') obj.value = text;
	}
}

function FindObjectPosition(obj)
{
	var left = 0;
	var top = 0;
	var width = 0;
	var height = 0;

	if (obj.offsetParent) {
		left = obj.offsetLeft;
		top = obj.offsetTop;
		width = obj.offsetWidth;
		height = obj.offsetHeight;

		while (obj = obj.offsetParent) {
			left += obj.offsetLeft;
			top += obj.offsetTop;
		}
	}

	return [left, top, width, height];
}

function ToggleSummary(button, show, hide, id1, id2)
{
	var pos;
	var obj1 = document.getElementById(id1);
	var obj2 = document.getElementById(id2);
	var button_obj = document.getElementById(button);

	if (!obj1 || !obj2) return;

	if (obj1.style.display == 'inline') {
		obj2.style.display = 'inline';
		obj1.style.display = 'none';

		if (button_obj) button_obj.value = show;
	} else {
		pos = FindObjectPosition(obj2);
		obj1.style.left = pos[0];
		obj1.style.top = pos[1];
		obj1.style.width = pos[2];

		obj1.style.display = 'inline';
		obj2.style.display = 'none';

		if (button_obj) button_obj.value = hide;
	}
}

function ToggleSummaryWeak()
{
	ToggleSummary('showsummary', 'Show Summary', 'Hide Summary', 'filtergroupsummary', 'filtergroup');
}

// vim: syntax=javascript ts=4
