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
// FIXME: localize "Services and Support" back to WEB_LANG_SERVICE_LEVEL
// FIXME: localize "Online Store" back to "Online Store"

require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
// S T E P  0  -  A L R E A D Y  R E G I S T E R E D
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $register;

	// Send web service request
	//-------------------------

	$hostkey = "";
	$endoflife = "";
	$endoflicense = "";
	$servicelist = array();
	$optional = array();

	try {
		$suva = new Suva();
		$hostkey = $suva->GetHostkey();
	} catch (Exception $e) {
		$hostkey = "...";
	}

	try {
		$os = new Os();
		$osname = $os->GetName();

		$endoflife = $register->GetEndOfLife();
		$endoflicense = $register->GetEndOfLicense();
		//$servicelist = $register->GetServiceList();
		$diagnostics = $register->GetDiagnosticsState();

		// Horizon 2
		try {
			$optional = $register->GetOptionalInfo();
		} catch (Exception $ignore) {
		}
	} catch (WebServicesNotRegisteredException $e) {
		WebFormOpen();
		WebDialogWarning($e->GetMessage() . " - " . WebButtonReset("Reset"));
		WebFormClose();
		return;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		//return;
	}

	$listitems = "";	
	$index = 0;

	/*
	foreach ($servicelist as $service) {
		if ($service['state']) {
			$iconclass = "iconenabled";
			$rowclass = "rowenabled";
		} else {
			$iconclass = "icondisabled";
			$rowclass = "rowdisabled";
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$listitems .= "
			<tr class='$rowclass'>
				<td class='$iconclass'>&nbsp; </td>
				<td nowrap>$service[name]</td>
				<td nowrap>$service[help]</td>
			</tr>
		";
	}
	*/

	// TODO: create some kind of method call instead of the "if" hack below

	// Display HTML
	//-------------

	WebFormOpen();
	WebTableOpen(WEB_LANG_DEVICE_INFO_TITLE, "550");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
	    <td nowrap>" . REGISTER_LANG_REGISTERED . "</td>
	  </tr>
	";
	if (isset($optional['account'])) {
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ACCOUNT . "</td>
			<td nowrap>" . $optional['account'] . "</td>
		  </tr>
		";
	}
	if (isset($optional['var'])) {
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_VAR . "</td>
			<td nowrap>" . $optional['var'] . "</td>
		  </tr>
		";
	}
	if (isset($optional['devicename'])) {
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_DEVICE_NAME . "</td>
			<td nowrap>" . $optional['devicename'] . "</td>
		  </tr>
		";
	}
	if (isset($optional['address'])) {
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ADDRESS . "</td>
			<td nowrap>" . $optional['address'] . "</td>
		  </tr>
		";
	}
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . REGISTER_LANG_HOSTKEY . "</td>
	    <td nowrap>" . $hostkey . "</td>
	  </tr>
	";
	if (isset($optional['license']) && $register->GetSdnOsLicenseRequired()) {
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEBSERVICES_LANG_LICENSE . "</td>
			<td nowrap>" . $optional['license'] . "</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . REGISTER_LANG_PRODUCT_END_OF_LICENSE . "</td>
			<td nowrap>$endoflicense</td>
		  </tr>
		";
	}

	if ($endoflife) {
		echo "
		  <tr>
			<td class='mytablesubheader' nowrap>" . REGISTER_LANG_PRODUCT_END_OF_LIFE . "</td>
			<td nowrap>$endoflife</td>
		  </tr>
		";
	}
	// TODO: translation
/*
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . "Send Diagnostic Data" . "</td>
		<td nowrap>" . WebDropDownEnabledDisabled("diagnostics", $diagnostics) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&nbsp;</td>
		<td nowrap>" . WebButtonUpdate("UpdateDiagnostics") . "</td>
	  </tr>
	";
*/
	
	WebTableClose("550");

	if (!WebIsSetup())
		WebFormClose();

	/*if (!WebIsSetup()) {
		WebTableOpen(WEB_LANG_SERVICE_LIST, "550");
		echo $listitems;
		WebTableClose("550");
	}*/
}


///////////////////////////////////////////////////////////////////////////////
// S T E P  1  -  L O G I N
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayLogin()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLogin($username)
{
	global $register; 

	// Sanity check network
	//-------------------------

	try {
		$syswatch = new Syswatch();
		$ifaces = $syswatch->GetInUseExternalInterfaces();

		if (count($ifaces) == 0) {
			WebDialogWarning("Internet connection is unavailable.  Please check your <a href='network.php'>network settings</a>.");
	
		}
	} catch (Exception $e) {
		//
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_ACCOUNT, "550");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . LOCALE_LANG_USERNAME . "</td>
	    <td><input type='text' style='width:150px' name='sdnusername' value='$username' /></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
	    <td><input type='password' style='width:150px' name='sdnpassword' value='' /></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader'>&#160;</td>
	    <td>" . WebButton("DisplayRegistrationType", WEB_LANG_START_REGISTRATION, WEBCONFIG_ICON_CONTINUE, array()) . "</td>
	  </tr>
	";
	WebTableClose("550");

	if (! WebIsSetup())
		WebFormClose();

	WebDialogInfo(WEB_LANG_NO_ACCOUNT . "<a href='" . $register->GetSdnURL() . "/new_account.jsp' target='_blank'>&#160; <b>" . WEB_LANG_CREATE_ACCOUNT . "</b></a>.");
}


///////////////////////////////////////////////////////////////////////////////
// S T E P  2  -  R E G I S T R A T I O N  T Y P E
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRegistrationType()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRegistrationType($username, $password)
{
	global $register;

	// Send web service request
	//-------------------------

	try {
		$register->Authenticate($username, $password);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayLogin($username);
		return;
	}

	// Display HTML
	//-------------

	WebDialogInfo(WEB_LANG_STEP_INSTALL_TYPE);

	$options = Array (
		"DisplayLicensingAdd" => WEB_LANG_ADD_SYSTEM,
		"DisplayLicensingUpgrade" => WEB_LANG_UPGRADE_SYSTEM,
		"DisplayLicensingReinstall" => WEB_LANG_REINSTALL_SYSTEM
	);
	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />";
	echo "<input type='hidden' name='sdnpassword' value='$password' />";
	WebTableOpen(WEB_LANG_PAGE_TITLE, "100%");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_ACCOUNT . "</td>
	    <td>$username</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
	    <td>" . WebDropDownHash('type', key($options), $options) . "</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader'>&#160; </td>
	    <td>" . WebButtonContinue("DisplayLicensing") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
// S T E P  3  -  L I C E N S I N G
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayLicensingAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLicensingAdd($username, $password)
{
	global $register;

	// Send web service request
	//-------------------------

	$licenselist = array();

	try {
		$licenselist = $register->GetLicenseList($username, $password);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRegistrationType($username, $password);
		return;
	}

	// Drop-down list
	//---------------

	foreach ($licenselist as $details) {
		// Only display unused licenses
		if ($details['status'] == "unassigned") {
			if (!isset($defaultlicense))
				$defaultlicense = trim($details['serial']);
			if (strlen($details['description']) > 50)
				$description = substr($details['description'], 0, 50) . "...";
			else
				$description = $details['description'];

			$serialno = trim($details['serial']);
			$serial_select .= "<option value='$serialno'>$description - $serialno</option>\n";
		}
	}

	if (!$serial_select) {
		WebDialogWarning(WEB_LANG_ADD_LICENSE_WARNING);
		DisplayRegistrationType($username, $password);
		return;
	}

	// Display HTML
	//-------------

	WebDialogInfo(WEB_LANG_ADD_HELP);
	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />";
	echo "<input type='hidden' name='sdnpassword' value='$password' />";
	echo "<input type='hidden' name='type' value='" . Register::TYPE_ADD . "' />";
	if (!$register->GetSdnOsLicenseRequired())
		echo "<input type='hidden' name='serial' value='$defaultlicense' />";
	WebTableOpen(WEB_LANG_ADD_TITLE, "100%");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_ACCOUNT . "</td>
	    <td>$username</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
	    <td>" . WEB_LANG_NEW_INSTALL . "</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_SYSTEM_NAME . "</td>
	    <td><input type='text' name='name' value ='$name' /></td>
	  </tr>
	";
	if ($register->GetSdnOsLicenseRequired()) {
		echo "
		  <tr>
		    <td class='mytablesubheader' nowrap>" . WEB_LANG_SERIAL_NUMBER . "</td>
		    <td><select name='serial'>$serial_select</select></td>
		  </tr>
		";
	}
	echo "
	  <tr>
	    <td class='mytablesubheader'>&#160; </td>
	    <td>" . WebButtonContinue("DisplayServiceLevel") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayLicensingUpgrade()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLicensingUpgrade($username, $password)
{
	global $register;

	// Send web service request
	//-------------------------

	$licenselist = array();

	try {
		$licenselist = $register->GetLicenseList($username, $password);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRegistrationType($username, $password);
		return;
	}

	// Drop-down list
	//---------------

	$serial_select = "";

	foreach ($licenselist as $details) {
		if ($details['status'] == "unassigned") {
			if (!isset($defaultlicense))
				$defaultlicense = trim($details['serial']);
			if (strlen($details['description']) > 50)
				$description = substr($details['description'], 0, 50) . "...";
			else
				$description = $details['description'];

			$serialno = trim($details['serial']);
			$serial_select .= "<option value='$serialno'>$description - $serialno</option>";
		}
	}

	if (! $serial_select) {
		WebDialogWarning(WEB_LANG_UPGRADE_LICENSE_WARNING);
		DisplayRegistrationType($username, $password);
		return;
	}

	// Send web service request
	//-------------------------

	$devicelist = array();

	try {
		$devicelist = $register->GetDeviceList($username, $password);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRegistrationType($username, $password);
	}

	$device_select = "";

	if (count($devicelist)) {
		foreach ($devicelist as $device) {
			$device_select .= "<option value='$device[name]'>$device[name] - $device[osname] $device[osversion]</option>";
		}
	} else {
		WebDialogWarning(WEB_LANG_DEVICES_NOT_FOUND);
		DisplayRegistrationType($username, $password);
		return;
	}

	// Display HTML
	//-------------

	WebDialogInfo(WEB_LANG_UPGRADE_HELP);
	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />";
	echo "<input type='hidden' name='sdnpassword' value='$password' />";
	echo "<input type='hidden' name='type' value='" . Register::TYPE_UPGRADE . "' />";
	if (!$register->GetSdnOsLicenseRequired())
		echo "<input type='hidden' name='serial' value='$defaultlicense' />";
	WebTableOpen(WEB_LANG_UPGRADE_TITLE, "100%");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_ACCOUNT . "</td>
	    <td>$username</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
	    <td>" . WEB_LANG_UPGRADE . "</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_SYSTEM_NAME . "</td>
	    <td><select name='name'>$device_select</select></td>
	  </tr>
	";
	if ($register->GetSdnOsLicenseRequired()) {
		echo "
		  <tr>
		    <td class='mytablesubheader' nowrap>" . WEB_LANG_SERIAL_NUMBER . "</td>
		    <td><select name='serial'>$serial_select</select></td>
		  </tr>
		";
	}
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>&#160;</td>
	    <td>" . WebButtonContinue("DisplayTos") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayLicensingReinstall()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLicensingReinstall($username, $password)
{
	global $register;

	// Send web service request
	//-------------------------

	$licenselist = array();

	try {
		$licenselist = $register->GetLicenseList($username, $password);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRegistrationType($username, $password);
		return;
	}

	// Drop-down list
	//---------------

	$name_select = "";

	foreach ($licenselist as $details) {
		if ($details['status'] == "registered") {
			if (strlen($details['description']) > 50)
				$description = substr($details['description'], 0, 50) . "...";
			else
				$description = $details['description'];

			$name_select .= "<option value='$details[name]'>$details[name] - $description</option>";
		}
	}

	if (!$name_select) {
		WebDialogWarning(WEB_LANG_REINSTALL_LICENSE_WARNING);
		DisplayRegistrationType($username, $password);
		return;
	}


	// Display HTML
	//-------------

	WebDialogInfo(WEB_LANG_REINSTALL_HELP);
	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />";
	echo "<input type='hidden' name='sdnpassword' value='$password' />";
	echo "<input type='hidden' name='type' value='" . Register::TYPE_REINSTALL . "' />";
	echo "<input type='hidden' name='terms' value='notapplicable' />";
	WebTableOpen(WEB_LANG_REINSTALL, "100%");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_ACCOUNT . "</td>
	    <td>$username</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
	    <td>" . WEB_LANG_REINSTALL . "</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_SYSTEM_NAME . "</td>
	    <td><select name='name'>$name_select</select></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>&#160;</td>
	    <td>" . WebButtonContinue("DisplayConfirm") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
// S T E P  4  -  S E R V I C E  L E V E L
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayServiceLevel()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayServiceLevel($username, $password, $name, $serial, $type)
{
	global $register;

	// TODO: create a more formal redirect framework
	$redirect_add = $_SESSION['system_sdn_redirect'] . "/register/addsubscription/" . $_SESSION['system_hostkey'] . "/" . $username;
	$redirect_more = $_SESSION['system_sdn_redirect'] . "/register/moreinfo/" . $_SESSION['system_hostkey'] . "/" . $username;
	$serial = trim($serial);
	$_SESSION['clearsdn_username'] = $username;
	$_SESSION['clearsdn_password'] = $password;

	// Display HTML
	//-------------

	WebDialogInfo(WEB_LANG_ADD_HELP);
	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />";
	echo "<input type='hidden' name='sdnpassword' value='$password' />";
	echo "<input type='hidden' name='type' value='" . Register::TYPE_ADD . "' />";
	echo "<input type='hidden' name='serial' value='$serial' />";
	echo "<input type='hidden' name='name' value='$name' />";
	WebTableOpen(WEB_LANG_ADD_TITLE, "100%");
	echo "
	  <tr>
	    <td class='mytablesubheader' width='50%' nowrap>" . WEB_LANG_ACCOUNT . "</td>
	    <td width='50%'>$username</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
	    <td>" . WEB_LANG_NEW_INSTALL . "</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_SYSTEM_NAME . "</td>
	    <td>$name</td>
	  </tr>
	";
	if ($register->GetSdnOsLicenseRequired()) {
		echo "
		  <tr>
		    <td class='mytablesubheader' nowrap>" . WEB_LANG_SERIAL_NUMBER . "</td>
		    <td>$serial</td>
		  </tr>
		";
	}
	try {
		$licensedetails = "";
		$licenselist = $register->GetServiceLevel($username, $password);
		$options = Array("0" => WEB_LANG_SELECT_SUB);
		if ($register->GetFreeTrialState()) {
			$options["buy"] = WEB_LANG_SERVICE_BUY;
			$options["custom"] = WEB_LANG_CUSTOM_TRIAL;
		}
		foreach ($licenselist as $details) {
			// Only display unused licenses
			if ($details['status'] == "unassigned") {
				$serialno = trim($details['serial']);
				// Hack
				if ($serialno == 'CLEARCENTER-000002') {
					$options[$serialno] = $details['description'];
				} else {
					$options[$serialno] = $details['description'] . " - " . $serialno;
				}
				$licensedetails .= "<div class='myserialno' id='$serialno'>\n";
				foreach ($details['child'] as $children)
					$licensedetails .= $children . "<br/>\n";
				$licensedetails .= "</div>\n";
			}
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . "Services and Support" . "</td>
	    <td>" . WebDropDownHash('service', key($options), $options, $width = 0, 'selectService()', 'sselector') . "</td>
	  </tr>
	  <tr id='c_support' style='display: none;'>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_SUPPORT . "</td>
	    <td>
	      <input type='radio' name='custom_support' value='1' CHECKED>" . LOCALE_LANG_YES . "
	      <input type='radio' name='custom_support' value='0'>" . LOCALE_LANG_NO . "
	    </td>
	  </tr>
	  <tr id='c_perimeter' style='display: none;'>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_PERIMETER_SECURITY . "</td>
	    <td>
	      <input type='radio' name='custom_perimeter' value='1' CHECKED>" . LOCALE_LANG_YES . "
	      <input type='radio' name='custom_perimeter' value='0'>" . LOCALE_LANG_NO . "
	    </td>
	  </tr>
	  <tr id='c_filter' style='display: none;'>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_FILTER . "</td>
	    <td>
	      <input type='radio' name='custom_filter' value='1' CHECKED>" . LOCALE_LANG_YES . "
	      <input type='radio' name='custom_filter' value='0'>" . LOCALE_LANG_NO . "
	    </td>
	  </tr>
	  <tr id='c_file' style='display: none;'>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_FILE_SERVER . "</td>
	    <td>
	      <input type='radio' name='custom_file' value='1' CHECKED>" . LOCALE_LANG_YES . "
	      <input type='radio' name='custom_file' value='0'>" . LOCALE_LANG_NO . "
	    </td>
	  </tr>
	  <tr id='c_email' style='display: none;'>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_MAIL_SERVER . "</td>
	    <td>
	      <input type='radio' name='custom_email' value='1' CHECKED>" . LOCALE_LANG_YES . "
	      <input type='radio' name='custom_email' value='0'>" . LOCALE_LANG_NO . "
	    </td>
	  </tr>
	  <tr id='b_subscription' style='display: none;'>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_PURCHASE . "</td>
	    <td>
	      <a href='" . $register->GetSdnURL() . "/build1.jsp' style='color:#7DAE32;' target='_blank'>" . "Online Store" . "</a>
	    </td>
	  </tr>
	  <tr id='h_subscription' style='display: none;'>
	    <td class='mytablesubheader' valign='top' nowrap>" . WEB_LANG_INCLUDES . "</td>
	    <td>$licensedetails</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader'>&#160; </td>
	    <td>" . WebButtonContinue("DisplayTos") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
// S T E P  5  -  T E R M S   O F   S E R V I C E
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTos()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTos($username, $password, $name, $serial, $type, $service)
{
	global $register;

	$servicelevel = WEB_LANG_NONE;
	// Validate
	//---------

	if (!$name) {
		WebDialogWarning(WEB_LANG_SYSTEM_NAME_MISSING);
		if ($type == Register::TYPE_UPGRADE)
			DisplayLicensingUpgrade($username, $password);
		else if ($type == Register::TYPE_ADD)
			DisplayLicensingAdd($username, $password);
		else if ($type == Register::TYPE_REINSTALL)
			DisplayLicensingReinstall($username, $password);
		return;
	}

	// Send web service request
	//-------------------------

	try {
		$tos = $register->GetTermsOfService($username, $password);
		if (isset($service) && $service !== "0" && $service != "") {
			$details = $register->GetLicenseDetails($username, $password, $service);
			$servicelevel = $details[1];
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRegistrationType($username, $password);
		return;
	}

	if ($type == Register::TYPE_UPGRADE)
		$displaytype = WEB_LANG_UPGRADE;
	else if ($type == Register::TYPE_ADD)
		$displaytype = WEB_LANG_NEW_INSTALL;
	else if ($type == Register::TYPE_REINSTALL)
		$displaytype = WEB_LANG_REINSTALL;
	if ($type == Register::TYPE_UPGRADE || $type == Register::TYPE_REINSTALL )  {
		try {
			$details = $register->GetDeviceDetails($username, $password, $name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			DisplayRegistrationType($username, $password);
			return;
		}
		foreach ($details as $detail) {
			if ($detail['type'] == 'OS') {
				$serial = $detail['serial'];
			} else if ($detail['type'] == 'SERVICE') {
				$service = $detail['serial'];
				$servicelevel = $detail['description'];
			}
		}
	}

	// Display HTML
	//-------------

	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />\n";
	echo "<input type='hidden' name='sdnpassword' value='$password' />\n";
	echo "<input type='hidden' name='type' value='$type' />\n";
	echo "<input type='hidden' name='name' value='$name' />\n";
	echo "<input type='hidden' name='serial' value='$serial' />\n";
	echo "<input type='hidden' name='service' value='$service' />\n";
	echo "<input type='hidden' name='servicelevel' value='$servicelevel' />\n";
	WebTableOpen(WEB_LANG_DEVICE_INFO_TITLE, "550");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_ACCOUNT . "</td>
	    <td>$username</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
	    <td>$displaytype</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_SYSTEM_NAME . "</td>
	    <td>$name</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . "Services and Support" . "</td>
	    <td>" . $servicelevel . "</td>
	  </tr>
	";
	if ($register->GetSdnOsLicenseRequired()) {
		echo "
		  <tr>
		    <td class='mytablesubheader' nowrap>" . WEB_LANG_SERIAL_NUMBER . "</td>
		    <td>$serial</td>
		  </tr>
		";
	}
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . WEB_LANG_TOS . "</td>
	    <td><input type='checkbox' name='terms' />" . WEB_LANG_SERVICE_TOS_AGREE . "</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>&#160;</td>
	    <td>" . WebButtonContinue("DisplayConfirm") . "</td>
	  </tr>
	";
	WebTableClose("550");
	WebFormClose();

	WebTableOpen(WEB_LANG_TOS, "550");
	echo "<tr><td>" . preg_replace("/\n/", "<br />", $tos) . "</td></tr>";
	WebTableClose("550");
}


///////////////////////////////////////////////////////////////////////////////
// S T E P  6  -  C O N F I R M
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirm()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirm($username, $password, $name, $serial, $type, $service, $servicelevel, $terms)
{
	global $register;

	if (!$terms) {
		WebDialogWarning(WEB_LANG_CONFIRM_WARNING);
		DisplayTos($username, $password, $name, $serial, $type, $service);
		return;
	}

	if ($type == Register::TYPE_UPGRADE || $type == Register::TYPE_REINSTALL )  {
		if ($type == Register::TYPE_UPGRADE)
			$typedescription = WEB_LANG_UPGRADE;
		else
			$typedescription = WEB_LANG_REINSTALL;
		try {
			$details = $register->GetDeviceDetails($username, $password, $name);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			DisplayRegistrationType($username, $password);
			return;
		}
		foreach ($details as $detail) {
			if ($detail['type'] == 'OS') {
				$serial = $detail['serial'];
			} else if ($detail['type'] == 'SERVICE') {
				$service = $detail['serial'];
				$servicelevel = $detail['description'];
			}
		}
	} else {
		$typedescription = WEB_LANG_NEW_INSTALL;
	}

	WebFormOpen();
	echo "<input type='hidden' name='sdnusername' value='$username' />\n";
	echo "<input type='hidden' name='sdnpassword' value='$password' />\n";
	echo "<input type='hidden' name='type' value='$type' />\n";
	echo "<input type='hidden' name='name' value='$name' />\n";
	echo "<input type='hidden' name='serial' value='$serial' />\n";
	echo "<input type='hidden' name='service' value='$service' />\n";
	echo "<input type='hidden' name='terms' value='agreed' />\n";
	WebTableOpen(LOCALE_LANG_CONFIRM, "550");
	echo "
		<tr>
			<td class='mytablesubheader' width='40%' nowrap>" . WEB_LANG_ACCOUNT . "</td>
			<td>$username</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_REGISTRATION_TYPE . "</td>
			<td>$typedescription</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_SYSTEM_NAME . "</td>
			<td>$name</td>
		</tr>
	";
	if (! empty($servicelevel)) {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . "Services and Support" . "</td>
				<td>$servicelevel</td>
			</tr>
		";
	}
	if ($register->GetSdnOsLicenseRequired()) {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_SERIAL_NUMBER . "</td>
				<td>$serial</td>
			</tr>
		";
	}

	// TODO: translation
/*
		<tr>
			<td class='mytablesubheader' nowrap>" . "Send Diagnostic Data" . "</td>
			<td nowrap>" . WebDropDownEnabledDisabled("diagnostics", true) . "&nbsp;
			<span class='alert'>Click on the User Guide link for more information!</span>
			</td>
		</tr>
*/
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td>" . WebButton("SubmitRegistration", LOCALE_LANG_CONFIRM, WEBCONFIG_ICON_GO) . "</td>
		</tr>
	";
	WebTableClose("550");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
// S T E P  6  -  P R O C E S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// SubmitRegistration()
//
// Stuff that should be done on a succesful registration...
//
///////////////////////////////////////////////////////////////////////////////

function SubmitRegistration($username, $password, $name, $serial, $type, $service, $terms, $diagnostics)
{
	global $register;

	try {
		$register->SubmitRegistration($username, $password, $name, $serial, $type, $service, $terms);
		$register->SetStatus();
		$_SESSION['system_registered'] = true;

		$register->SetDiagnosticsState($diagnostics);
		$suva = new Suva();
		$suva->AutoConfigure();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRegistrationType($username, $password);
		return;
	}

	// Let syswatch handle new information (Suva Device ID, DynDns, Managed VPN)
	//--------------------------------------------------------------------------

	if (file_exists("../../api/Syswatch.class.php")) {
		require_once("../../api/Syswatch.class.php");
		try {
			$syswatch = new Syswatch();
			$syswatch->Restart();
		} catch (Exception $e) {
			// not important
		}
	}

	DisplaySummary();
}

// vim: syntax=php ts=4
?>
