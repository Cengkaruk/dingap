<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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
require_once("../../api/SoftwareUpdate.class.php");

WebAuthenticate();

try {
	$update = new SoftwareUpdate();
	$logentries = $update->GetLog();
} catch (Exception $e) {
	$logentries = array();
}

$logentries = array_reverse($logentries);
$contents = '';
$count = 0;
$status = "installing";

foreach ($logentries as $entry) {
	if ($count < 30) {
		$contents = htmlentities($entry) . "\n" . $contents;
		$count++;
	}

	// TODO: dirty -- fix this
	if (preg_match("/Nothing to do/", $entry) || preg_match("/Complete!/", $entry))
		$status = "done";
}

header('Content-Type: application/xml');

$thedate = strftime("%b %e %Y");
$thetime = strftime("%T %Z");

echo "<?xml version='1.0'?>
<softwareupdate>
	<logtime>$thedate $thetime</logtime>
	<message>$contents</message>
	<status>$status</status>
</softwareupdate>
";

?>
