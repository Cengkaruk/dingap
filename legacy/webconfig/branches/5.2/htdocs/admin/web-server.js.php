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
function enable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = false;
}

function disable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = true;
}

function toggleOwner() {
  if (document.getElementById) {
    if (document.getElementById('ftp').value == 1 || document.getElementById('file').value == 1)
      enable('group_owner');
    else
      disable('group_owner');
  }
}
";

// vim: ts=4
?>
