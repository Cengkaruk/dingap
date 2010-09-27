<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
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
require_once("../../api/Os.class.php");
require_once("../../api/Register.class.php");
require_once("../../api/Resolver.class.php");
require_once("../../api/Suva.class.php");
require_once("../../api/Syswatch.class.php");
require_once("register.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-register.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

// Set our post variables
$name = isset($_POST['name']) ? $_POST['name'] : "";
$type = isset($_POST['type']) ? $_POST['type'] : "";
$terms = isset($_POST['terms']) ? $_POST['terms'] : "";
$serial = isset($_POST['serial']) ? $_POST['serial'] : "";
$service = isset($_POST['service']) ? $_POST['service'] : "";
$servicelevel = isset($_POST['servicelevel']) ? $_POST['servicelevel'] : "";
$username = isset($_POST['sdnusername']) ? $_POST['sdnusername'] : "";
$password = isset($_POST['sdnpassword']) ? $_POST['sdnpassword'] : "";
$diagnostics = isset($_POST['diagnostics']) ? (bool)$_POST['diagnostics'] : false;

// Disable
$diagnostics = false;

$register = new Register();

// Set our registration and wizard status if we get the ok from the
// registration server.

if (isset($_REQUEST['Unregister'])) {
	unset($_SESSION['system_registered']);
	$register->Reset(false);
} else if (isset($_REQUEST['Reset'])) {
	unset($_SESSION['system_registered']);
	$register->Reset(true);
} else if (isset($_REQUEST['UpdateDiagnostics'])) {
	$register->SetDiagnosticsState($diagnostics);
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

// Step 0 -- Already registered
//-----------------------------

if (isset($_SESSION['system_registered']) && $_SESSION['system_registered']) {
	DisplaySummary();

// Step 2 -- Registration type
//----------------------------

} else if (isset($_POST['DisplayRegistrationType'])) {
	DisplayRegistrationType($username, $password);

// Step 3 -- Licensing
//--------------------

} else if (isset($_POST['DisplayLicensing']) && $_POST['type'] == 'DisplayLicensingAdd') {
	DisplayLicensingAdd($username, $password);
} else if (isset($_POST['DisplayLicensing']) && $_POST['type'] == 'DisplayLicensingUpgrade') {
	DisplayLicensingUpgrade($username, $password);
} else if (isset($_POST['DisplayLicensing']) && $_POST['type'] == 'DisplayLicensingReinstall') {
	DisplayLicensingReinstall($username, $password);

// Step 4 -- Service Level
//----------------------------

} else if (isset($_POST['DisplayServiceLevel'])) {
	DisplayServiceLevel($username, $password, $name, $serial, $type);

// Step 5 -- Terms of service
//----------------------------

} else if (isset($_POST['DisplayTos'])) {
	if (isset($_POST['service']) && ($_POST['service'] == '0' || $_POST['service'] == 'buy')) {
		WebDialogWarning(WEB_LANG_INVALID_SERVICE);
		DisplayServiceLevel($username, $password, $name, $serial, $type);
		return;
	} else if (isset($_POST['service']) && $_POST['service'] == 'custom') {
		// Override service
		$service = 'EVAL-000';
		$service .= $_POST['custom_support'];
		$service .= $_POST['custom_perimeter'];
		$service .= $_POST['custom_filter'];
		$service .= $_POST['custom_file'];
		$service .= $_POST['custom_email'];
	}
	DisplayTos($username, $password, $name, $serial, $type, $service);

// Step 6 -- Confirm
//------------------

} else if (isset($_POST['DisplayConfirm'])) {
	DisplayConfirm($username, $password, $name, $serial, $type, $service, $servicelevel, $terms);

// Step 7 -- Submit
//-----------------

} else if (isset($_POST['SubmitRegistration'])) {
	SubmitRegistration($username, $password, $name, $serial, $type, $service, $terms, $diagnostics);

// Step 1 -- Login
//----------------

} else {
	DisplayLogin($username);
}

WebFooter();

// vim: syntax=php ts=4
?>
