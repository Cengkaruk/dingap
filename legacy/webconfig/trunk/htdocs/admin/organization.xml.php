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
require_once("../../api/Hostname.class.php");

WebAuthenticate();

try {
	if (file_exists("../../api/DynamicDns.class.php")) {
		require_once("../../api/DynamicDns.class.php");
		$dyndns = new DynamicDns();
		$dyndnsinfo = $dyndns->GetInfo();
		$realhostname = $dyndnsinfo['domain'];
	}
} catch (Exception $e) {
	// Not fatal
}

if (empty($realhostname)) {
	$hostname = new Hostname();
	$realhostname = $hostname->Get();
}

header('Content-Type: application/xml');
echo "<organization>
	<hostname>$realhostname</hostname>
</organization>
";

?>
