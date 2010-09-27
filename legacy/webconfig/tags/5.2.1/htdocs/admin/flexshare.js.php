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

function togglepassword() {
  if (document.getElementById('encrypt').value == 1)
    document.getElementById('password').disabled = false;
  else
    document.getElementById('password').disabled = true;
}

function togglewebportnum() {
  if (document.getElementById('web_req_ssl').value == 1 && document.getElementById('web_override_port').value == 0)
    document.getElementById('web_port').value = 443;
  else if (document.getElementById('web_req_ssl').value == 0 && document.getElementById('web_override_port').value == 0)
    document.getElementById('web_port').value = 80;
  if (document.getElementById('web_override_port').value == 1)
    enable('web_port');
  else
    disable('web_port');
}

function togglewebreqauth() {
  if (document.getElementById('web_req_auth').value == 1) {
    enable('web_realm');
  } else {
    disable('web_realm');
  }
}

function toggleftpport(ftp, ftps) {
  if (document.getElementById('ftp_override_port').value == 1) {
    enable('ftp_port');
  } else {
    disable('ftp_port');
    if (document.getElementById('ftp_req_ssl').value == 1)
      document.getElementById('ftp_port').value = ftps;
    else if (document.getElementById('ftp_req_ssl').value == 0)
      document.getElementById('ftp_port').value = ftp;
  }
}

function toggleftpportnum(ftp, ftps) {
  if (document.getElementById('ftp_req_ssl').value == 1 && document.getElementById('ftp_override_port').value == 0)
    document.getElementById('ftp_port').value = ftps;
  else if (document.getElementById('ftp_req_ssl').value == 0 && document.getElementById('ftp_override_port').value == 0)
    document.getElementById('ftp_port').value = ftp;
}

function toggleftppassive() {
  if (document.getElementById('ftp_allow_passive').value == 1) {
    enable('ftp_passive_port_min');
    enable('ftp_passive_port_max');
  } else {
    disable('ftp_passive_port_min');
    disable('ftp_passive_port_max');
  }
}

function toggleftpgrouppermission() {
  if (document.getElementById('ftp_req_auth').value == 1 && document.getElementById('ftp_group_permission').value != 1 && document.getElementById('ftp_group_umask_1')) {
    enable('ftp_group_umask_1');
    enable('ftp_group_umask_2');
    enable('ftp_group_umask_3');
  } else if (document.getElementById('ftp_group_umask_1')) {
    disable('ftp_group_umask_1');
    disable('ftp_group_umask_2');
    disable('ftp_group_umask_3');
  }
}

function toggleftpallowanonymous() {
  toggleftpanonymouspermission();
  if (document.getElementById('ftp_allow_anonymous').value == 1) {
    enable('ftp_anonymous_greeting');
    enable('ftp_anonymous_permission');
  } else {
    disable('ftp_anonymous_greeting');
    disable('ftp_anonymous_permission');
  }
}

function toggleftpanonymouspermission() {
  if (document.getElementById('ftp_allow_anonymous').value == 1 && document.getElementById('ftp_anonymous_permission').value != 1 && document.getElementById('ftp_anonymous_umask_1')) {
    enable('ftp_anonymous_umask_1');
    enable('ftp_anonymous_umask_2');
    enable('ftp_anonymous_umask_3');
  } else if (document.getElementById('ftp_anonymous_umask_1')) {
    disable('ftp_anonymous_umask_1');
    disable('ftp_anonymous_umask_2');
    disable('ftp_anonymous_umask_3');
  }
}

function toggleemailsave() {
  if (document.getElementById('email_save').value == 0)
    enable('email_notify');
  else
    disable('email_notify');
}

function toggleemailrestrictaccess(emailcounter) {
  if (document.getElementById('email_restrict_access').value != 0) {
    enable('email_add_acl');
    if (window.oEmailFilter)
      oEmailFilter.set('disabled', false);
    for (i = 1; i <= emailcounter; i++)
      enable('emailacl-' + i);
  } else {
    disable('email_add_acl');
    if (window.oEmailFilter)
      oEmailFilter.set('disabled', true);
    for (i = 1; i <= emailcounter; i++)
      disable('emailacl-' + i);
  }
}

function enable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = false;
}

function disable(id) {
  if (document.getElementById(id))
    document.getElementById(id).disabled = true;
}

";
