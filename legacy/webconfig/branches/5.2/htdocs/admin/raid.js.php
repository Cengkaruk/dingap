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

echo "

function togglenotify() {
  if (document.getElementById('monitor').value == 1) {
    document.getElementById('notify').disabled = false;
  } else {
    document.getElementById('notify').value = 0;
    document.getElementById('notify').disabled = true;
  }
  toggleemail();
}

function toggleemail() {
  if (document.getElementById('notify').value == 1)
    document.getElementById('email').disabled = false;
  else
    document.getElementById('email').disabled = true;
}

function enable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = false;
}

function disable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = true;
}

function toggleview() {
  if (document.getElementById('action').value == 1) {
    document.getElementById('copyto').style.display = 'none';
    document.getElementById('copyfrom').style.display = 'none';
  } else {
    if (document.all) {
      document.getElementById('copyto').style.display = 'inline';
      document.getElementById('copyfrom').style.display = 'inline';
    } else {
      document.getElementById('copyto').style.display = 'table-row';
      document.getElementById('copyfrom').style.display = 'table-row';
    }
  }
}


";

// vi: ts=4
?>
