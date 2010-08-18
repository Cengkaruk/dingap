<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 ClearCenter
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
require_once("../../api/Register.class.php");

WebAuthenticate();

$username = $_SESSION['clearsdn_username'];
$password = $_SESSION['clearsdn_password'];

$licenselist = array();

try {
	$register = new Register();
	$licenselist = $register->GetServiceLevel($username, $password);
} catch (Exception $e) {
	$result = "";
}

foreach ($licenselist as $details) {
	// Only display unused licenses
	if ($details['status'] == "unassigned") {
		$serialno = trim($details['serial']);
		$licenses .= 
			"<license>\n" .
				"<serial>" . $serialno . "</serial>\n" .
				"<description>" . $details['description'] . "</description>\n" .
			"</license>\n";
	}
}

sleep(5);

header('Content-Type: application/xml');

echo "<licenses>
	$licenses
</licenses>
";

?>
