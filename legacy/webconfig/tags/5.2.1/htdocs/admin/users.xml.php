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
require_once("../../api/User.class.php");

WebAuthenticate();

// Delete alias
//-------------

if (isset($_REQUEST['DeleteAlias'])) {
	try {
		if (isset($_REQUEST['alias']) && isset($_REQUEST['username'])) {
			$user = new User($_REQUEST['username']);
			$user->DeleteAlias($_REQUEST['alias']);
		}
	} catch (Exception $e) { 
		//
	}

// Add alias
//----------

} else if (isset($_REQUEST['AddAlias'])) {
	try {
		if (isset($_REQUEST['alias']) && isset($_REQUEST['username'])) {
			$user = new User($_REQUEST['username']);
			$user->AddAlias($_REQUEST['alias']);
		}
	} catch (Exception $e) { 
		echo $e->GetMessage();
	}

// Show aliases
//-------------

} else if (isset($_REQUEST['GetAliases'])) {
	$aliases = "";
	$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : "";

	if ($username) {
		sleep(2);
		try {
			$user = new User($username);
			$info = $user->GetInfo();
			$aliases = empty($info['aliases']) ? "" : implode(",", $info['aliases']);
			$aliases = ltrim($aliases, ',');
		} catch (Exception $e) {
			$result = "...";
		}
	}

	header('Content-Type: application/xml');
echo "<user>
	<username>$username</username>
	<aliases>$aliases</aliases>
</user>
";
}

?>
