<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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

function selectService() {
  var fields =['c_support','c_perimeter','c_filter','c_file','c_email'];
  if (document.getElementById('sselector').value == 'custom') {
    for (var i = 0; i < fields.length; i++) {
      show(fields[i]);
    }
  } else {
    for (var i = 0; i < fields.length; i++) {
      hide(fields[i]);
    }
  }
  if (document.getElementById('sselector').value == 'buy')
    show('b_subscription');
  else
    hide('b_subscription');
  if (document.getElementById('sselector').value.length >= 14) {
    show('h_subscription');
    var allDivs = document.getElementsByTagName('div');
    for(i=0; i<allDivs.length; i++) {
      if(allDivs[i].className == 'myserialno') allDivs[i].style.display = 'none';
    }
    show(document.getElementById('sselector').value);
  } else {
    hide('h_subscription');
  }
}

function hide(id) {
  if (document.getElementById(id))
    document.getElementById(id).style.display = 'none';
}

function show(id) {
  if (document.getElementById(id)) {
    if (document.all)
      document.getElementById(id).style.display = 'inline';
    else
      document.getElementById(id).style.display = 'table-row';
  }
}
";

?>
