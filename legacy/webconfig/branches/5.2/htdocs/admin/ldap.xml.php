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

require_once("../../api/ClearDirectory.class.php");
require_once("../../api/Ldap.class.php");

$state = "no";
$basedn = "...";
$binddn = "...";
$bindpassword = "...";

try {
	$directory = new ClearDirectory();

	$initialized = $directory->IsInitialized();

	if ($initialized) {
		$ldap = new Ldap();
		$state = ($ldap->GetRunningState()) ? "yes" : "no";

		if ($state) {
            $basedn = $ldap->GetBaseDn();
            $binddn = $ldap->GetBindDn();
            $bindpassword = $ldap->GetBindPassword();
		}
	}
} catch (Exception $e) {
	$state = "no";
}

sleep(4);

header('Content-Type: application/xml');
echo "<setup>
<directorystate>$state</directorystate>
<basedn>$basedn</basedn>
<binddn>$binddn</binddn>
<bindpassword>$bindpassword</bindpassword>
</setup>
";

?>
