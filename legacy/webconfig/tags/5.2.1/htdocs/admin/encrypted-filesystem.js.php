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
var mount_point_changed = false;

function ShowDialog(id, volume_name)
{
	var dialog = document.getElementById(id);

	if (!dialog) return;
	dialog.style.display = 'inline';

	if (id == 'password_dialog') {
		var mount_password = document.getElementById('mount_password');
		if (mount_password) mount_password.focus();
		var vn = document.getElementById('passwd_vol_name');
		if (!vn) return;
		vn.value = volume_name;
	}
	else if (id == 'delete_volume_dialog') {
		var span_volume_name = document.getElementById('span_volume_name');
		if(span_volume_name) span_volume_name.firstChild.nodeValue = volume_name;
		var vn = document.getElementById('delete_vol_name');
		if (!vn) return;
		vn.value = volume_name;
		var confirm_delete = document.getElementById('confirm_delete');
		if (confirm_delete) confirm_delete.checked = false;
	}
}

function HideDialog(id)
{
	var dialog = document.getElementById(id);

	if (!dialog) return;

	dialog.style.display = 'none';

	if (id == 'password_dialog') {
		var mount_password = document.getElementById('mount_password');
		if (mount_password) mount_password.value = '';
	}
	else if (id == 'delete_volume_dialog') {
		var confirm_delete = document.getElementById('confirm_delete');
		if (confirm_delete) confirm_delete.checked = false;
	}
}

function ConfirmDelete(form, lang_click_confirm)
{
	var confirm_delete = document.getElementById('confirm_delete');

	if (!confirm_delete) return;

	if (!confirm_delete.checked) {
		alert(lang_click_confirm);
		return;
	}

	form.submit();
}

function StringRight(str, n)
{
	if (n <= 0) return '';
	else if (n > String(str).length) return str;
	else {
		var len = String(str).length;
		return String(str).substring(len, len - n);
	}
}

function StringLTrim(str)
{
	var whitespace = new String(" \t\n\r");
	var s = new String(str);

	if (whitespace.indexOf(s.charAt(0)) != -1) {
		var j = 0, i = s.length;

		while (j < i && whitespace.indexOf(s.charAt(j)) != -1) j++;
		s = s.substring(j, i);
	}

	return s;
}

function StringRTrim(str)
{
	var whitespace = new String(" \t\n\r");
	var s = new String(str);

	if (whitespace.indexOf(s.charAt(s.length - 1)) != -1) {
		var i = s.length - 1;

		while (i >= 0 && whitespace.indexOf(s.charAt(i)) != -1) i--;

		s = s.substring(0, i + 1);
	}

	return s;
}

function StringTrimSlash(str)
{
	var slash = new String("/");
	var s = new String(str);

	if (slash.indexOf(s.charAt(s.length - 1)) != -1) {
		var i = s.length - 1;

		while (i >= 0 && slash.indexOf(s.charAt(i)) != -1) i--;

		s = s.substring(0, i + 1);
	}

	if (slash.indexOf(s.charAt(0)) != -1) {
		var j = 0, i = s.length;

		while (j < i && slash.indexOf(s.charAt(j)) != -1) j++;
		s = s.substring(j, i);
	}

	return s;
}

function StringTrim(str)
{
	return StringRTrim(StringLTrim(str));
}

function StringStrip(str)
{
	var i = 0;
	var badchars = new String("/;&*()`'\"|\\!$ ");
	var input = new String(StringTrim(str));
	var output = new String();

	for ( ; i < input.length; i++) {
		if (badchars.indexOf(input.charAt(i)) != -1) continue;
		output = output + input.charAt(i);
	}

	return output;
}

function IsBadNameChar(str)
{
	var badchars = new String("/;&*()`'\"|\\!$ ");

	if (badchars.indexOf(str.charAt(0)) != -1) return true;

	return false;
}

function OnNameChange(e)
{
	var keynum;
	var keychar;
	var numcheck;

	if(window.event) // IE
		keynum = e.keyCode;
	else if(e.which) // Netscape/Firefox/Opera
		keynum = e.which;

	keychar = String.fromCharCode(keynum);

	return !IsBadNameChar(keychar);
}

function UpdateMountPoint(default_mount_point) {
	var vol_name = document.getElementById('vol_name');
	var vol_mount_point = document.getElementById('vol_mount_point');

	if (!vol_name || !vol_mount_point) return;
	if (!vol_mount_point.length) vol_mount_point.value = default_mount_point + '/' + vol_name.value;
}

function IsBadMountPointChar(str)
{
	var badchars = new String(";&*()`'\"|\\!$ ");

	if (badchars.indexOf(str.charAt(0)) != -1) return true;

	return false;
}

function OnMountPointChange(e, default_mount_point)
{
	var keynum;
	var keychar;
	var numcheck;

	if(window.event) // IE
		keynum = e.keyCode;
	else if(e.which) // Netscape/Firefox/Opera
		keynum = e.which;

	keychar = String.fromCharCode(keynum);

	return !IsBadMountPointChar(keychar);
}

function FixMountPoint(default_mount_point)
{
	var vol_name = document.getElementById('vol_name');
	var vol_mount_point = document.getElementById('vol_mount_point');

	if (!vol_mount_point.value.length && vol_name.value.length) {
		vol_mount_point.value = default_mount_point + '/' + vol_name.value;
	}

	if (vol_name.value.length)
		vol_mount_point.value = '/' + StringTrimSlash(vol_mount_point.value);
}

function DisableFormElements(form, disable)
{
	var nodes = form.elements;
	for (var i = 0; i < nodes.length; i++) {
		nodes[i].disabled = disable;
	}
}

function VerifyForm(form, lang_name_empty, lang_mount_point_empty, lang_size_is_zero, lang_password_mismatch, lang_password_empty)
{
	var vol_name = document.getElementById('vol_name');
	var vol_mount_point = document.getElementById('vol_mount_point');
	var vol_size = document.getElementById('vol_size');
	var vol_passwd = document.getElementById('vol_passwd');
	var vol_verify_passwd = document.getElementById('vol_verify_passwd');

//	DisableFormElements(form, true);

	if (!vol_name || !vol_mount_point || !vol_size || !vol_passwd || !vol_verify_passwd) return;

	if (!vol_name.value.length) {
		vol_name.focus();
		alert(lang_name_empty);
		return;
	}

	if (!vol_mount_point.value.length) {
		vol_mount_point.focus();
		alert(lang_mount_point_empty);
		return;
	}

	if (!vol_size.value.length || parseInt(vol_size.value) == 0) {
		vol_size.value = '';
		vol_size.focus();
		alert(lang_size_is_zero);
		return;
	}

	if (!vol_passwd.value.length || !vol_verify_passwd.value.length) {
		if (!vol_passwd.value.length) vol_passwd.focus();
		else if (!vol_verify_passwd.value.length) vol_verify_passwd.focus();
		alert(lang_password_empty);
		return;
	}

	if (vol_passwd.value != vol_verify_passwd.value) {
		vol_passwd.value = '';
		vol_verify_passwd.value = '';
		vol_passwd.focus();
		alert(lang_password_mismatch);
		return;
	}

	form.submit();
}

function OnSizeChange(e)
{
	var keynum;
	var keychar;
	var numcheck;

	if(window.event) // IE
		keynum = e.keyCode;
	else if(e.which) // Netscape/Firefox/Opera
		keynum = e.which;
	else return true;

	keychar = String.fromCharCode(keynum);

	numcheck = /\d/;
	return numcheck.test(keychar)
}

function togglevolsize() {
  if (document.getElementById('mounted-' + document.getElementById('vol_device').value).value != 0)
	enable('vol_size');
  else
	disable('vol_size');
}

function enable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = false;
}

function disable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = true;
}

// vim: syntax=javascript ts=4
