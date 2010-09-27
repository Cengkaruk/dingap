<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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

/**
 * Splash page.
 *
 * If splash logo exists show a splash page, otherwise forward user to a 
 * more useful page.
 *
 * @author Point Clark Networks
 * @license GNU Public License
 * @package Webconfig
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

require_once('../gui/Webconfig.inc.php');
require_once('../api/Os.class.php');

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

if (file_exists(WEBCONFIG_PATH . "/templates/$_SESSION[system_template]/images/logo_splash.png"))
	DisplaySplash();
else 
	WebForwardPage('/admin/index.php');

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Display splash page.
 *
 * @return  string  HTML output to display splash
 */

function DisplaySplash()
{
	$splash = WebSetIcon('logo_splash.png');

	try {
		$os = new Os();
		$version = $os->GetName() . " " . $os->GetVersion();
	} catch (Exception $e) {
		// Not fatal
		$version = "";
	}

	WebHeader($version, false);
	echo "<p align='center'><a href='/admin/index.php'>$splash</a></p>";
	echo "<p align='center'><b>" . WebUrlJump('/admin/index.php', $version) . "</b></p>";
	WebFooter();
}

// vim: syntax=php ts=4
?>
