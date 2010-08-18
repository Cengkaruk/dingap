#!/usr/webconfig/bin/php
<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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

error_reporting(0);
set_time_limit(0);

require_once("/var/webconfig/common/Logger.class.php");
include_once("/var/webconfig/api/Archive.class.php");
include_once("/var/webconfig/api/NtpTime.class.php");

$archive = new Archive();

try {
	$ntptime = new NtpTime();
	date_default_timezone_set($ntptime->GetTimeZone());
	$filename = date("Y-m-d_H.i");	
	$archive->ArchiveData($filename);
} catch (Exception $e) {
    Logger::Syslog("archive", $e->GetMessage());
}
// vim: syntax=php ts=4
?>
