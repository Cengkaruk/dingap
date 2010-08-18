<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 ClearCenter
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
require_once("../../gui/WebServices.inc.php");

WebAuthenticate();

if (! isset($_REQUEST['service']))
	exit();

try {
	$webservice = new WebServices($_REQUEST['service']);
	$subscription = $webservice->GetSubscriptionStatus(true);
} catch (Exception $e) {
	// If a user clicks on "check latest subscription info" and is not
	// connected to the Internet, we want to return the cached information.

	$warning =  $e->GetMessage();

	try {
		$subscription = $webservice->GetSubscriptionStatus(false);
	} catch (Exception $e) {
		// do nothing
	}

	$subscription['error'] = $warning;
}

header('Content-Type: application/xml');

echo "<subscription>";
foreach ($subscription as $key => $value) {
	if (empty($value))
		$value = 0;

	echo "    <$key>$value</$key>\n";
}
echo "</subscription>";

?>
