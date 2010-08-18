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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/Layer7Filter.class.php');

WebAuthenticate();

$l7 = new Layer7Filter();

try {
	$l7->GetProtocols($groups, $patterns);
	$l7->GetStatus($patterns);
} catch (Exception $e) {
	// Not fatal
}

header('Content-Type: application/xml');
echo "<?xml version='1.0'?>\n";
echo "<status>\n";

foreach ($patterns as $pattern) {
	if (!$pattern['enabled'])
		continue;

	echo "\t<protocol>\n";
	printf("\t\t<mark>%d</mark>\n", $pattern['mark']);
	printf("\t\t<packets>%d</packets>\n", $pattern['packets']);
	printf("\t\t<bytes>%d</bytes>\n", $pattern['bytes']);
	echo "\t</protocol>\n";
}

echo "</status>\n";

// vim: ts=4
?>
