<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2011 ClearFoundation
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

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {
    $('.mode_form').hide();

    current_form = '#' + $('#mode').val();
    $(current_form).show();

    $('#mode').change(function() {
        $('.mode_form').hide();
        new_form = '#' + $(this).attr('value');
        $(new_form).show();
    });
});

/*
function toggleNetworkType() {
    if (document.getElementById) {
        if (document.getElementById('networktype').value == 'static') {
            hide('pppoe');  
            hide('dhcp');  
            show('static');
        } else if (document.getElementById('networktype').value == 'pppoe') {
            show('pppoe');  
            hide('dhcp');  
            hide('static');
        } else if (document.getElementById('networktype').value == 'dhcp') {
            hide('pppoe');  
            show('dhcp');  
            hide('static');
        }
    }
}
*/

// vim: syntax=javascript
