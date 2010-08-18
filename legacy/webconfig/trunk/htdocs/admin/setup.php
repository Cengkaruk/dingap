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
//
// TODO: Needless to say, this is not the way to architect a wizard.  It was
// a quick and dirty solution for the 5.x release.  Lots of hard coding and
// duplicate code in here.  When thinking about the webconfig pages rewrite, 
// keep this "wizard" requirement in mind.
//
///////////////////////////////////////////////////////////////////////////////
// FIXME: wizard icons

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallRule.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/FirewallWifi.class.php");
require_once("../../api/Hostname.class.php");
require_once("../../api/HostnameChecker.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Locale.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/NtpTime.class.php");
require_once("../../api/Ntpd.class.php");
require_once("../../api/Organization.class.php");
require_once("../../api/Register.class.php");
require_once("../../api/Resolver.class.php");
require_once("../../api/Routes.class.php");
require_once("../../api/Syswatch.class.php");
require_once("../../api/Ssl.class.php");
// require_once("../../api/PosixUser.class.php");

require_once(GlobalGetLanguageTemplate(__FILE__));

require_once("date.inc.php");
require_once("language.inc.php");
require_once("network.inc.php");
require_once("organization.inc.php");
require_once("register.inc.php");

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

///////////////////////////////////////////////////////////////////////////////
//
// Initialize
//
///////////////////////////////////////////////////////////////////////////////

// We need to initialize these just to get the translations in the menu
$locale = new Locale();
$network = new Network();
$ntptime = new NtpTime();
$ntpd = new Ntpd();
$organization = new Organization();
$register = new Register();
$routes = new Routes();
$firewall = new Firewall();
$hostnameobj = new Hostname();
$resolver = new Resolver();
$interfaces = new IfaceManager();

$steps = array();
$steps['language'] = LOCALE_LANG_LANGUAGE;
// $steps['password'] = LOCALE_LANG_PASSWORD;
$steps['network'] = NETWORK_LANG_NETWORK;
// $steps['register'] = REGISTER_LANG_REGISTER;
$steps['datetime'] = TIME_LANG_TIMEZONE;
// $steps['mode'] = WEB_LANG_MODE;
$steps['domain'] = WEB_LANG_DOMAIN;
$steps['organization'] = ORGANIZATION_LANG_ORGANIZATION;
$steps['complete'] = WEB_LANG_FINISH;

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

// TODO: Manual override
if (isset($_REQUEST['step']))
	$_SESSION['setup_step'] = $_REQUEST['step'];

///////////////////////////////////////////////////////////////////////////////
// Previous step / cancel
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['GoToPreviousStep'])) {
	$_SESSION['setup_step'] = key($_POST['GoToPreviousStep']);
	unset($_POST);

///////////////////////////////////////////////////////////////////////////////
// Language
///////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['action']) && ($_POST['action'] == "UpdateAdminLanguage")) {
	try {
		$locale->SetLocale($_POST['langcode']);
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

	if (! $errormessage) {
		WebSetSession();
		$_SESSION['setup_step'] = key($_POST['GoToNextStep']);
		WebForwardPage("/admin/setup.php?forcereload=yes"); // Reload page for new locale tags
	}

///////////////////////////////////////////////////////////////////////////////
// Password
///////////////////////////////////////////////////////////////////////////////
/*

} else if (isset($_POST['action']) && ($_POST['action'] == "UpdateAdminPassword")) {

	$password = isset($_POST['password']) ? $_POST['password'] : null;
	$verify = isset($_POST['verify']) ? $_POST['verify'] : null;

	try {
		$user = new PosixUser("root");
		$user->SetPassword($password, $verify);
		$password = null;
		$verify = null;
	} catch (ValidationException $e) {
		$errormessage = $user->GetValidationErrors(true);
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

	if (! $errormessage)
		$_SESSION['setup_step'] = key($_POST['GoToNextStep']);
*/

///////////////////////////////////////////////////////////////////////////////
// Network Cards
///////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['DeleteInterface'])) {
	try {
		$interface = new Iface(key($_POST['DeleteInterface']));

		$interface->DeleteConfig();
		$firewall->RemoveInterfaceRole(key($_POST['DeleteInterface']));

		if ($routes->GetGatewayDevice() == key($_POST['DeleteInterface']))
			$routes->DeleteGatewayDevice();

		$syswatch = new Syswatch();
		$syswatch->Reset();

	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

} else if (isset($_POST['SaveNetworkInterface'])) {
	$eth = isset($_POST['eth']) ? $_POST['eth'] : "";
	$role = isset($_POST['role']) ? $_POST['role'] : "";
	$bootproto = isset($_POST['bootproto']) ? $_POST['bootproto'] : "";
	$ip = isset($_POST['ip']) ? $_POST['ip'] : "";
	$netmask = isset($_POST['netmask']) ? $_POST['netmask'] : "";
	$gateway = isset($_POST['gateway']) ? $_POST['gateway'] : "";
	$dhcp_hostname = isset($_POST['dhcp_hostname']) ? $_POST['dhcp_hostname'] : "";
	$peerdns = (isset($_POST['peerdns']) && ($_POST['peerdns'] == "on")) ? true : false; 
	$pppoe_peerdns = (isset($_POST['pppoe_peerdns']) && ($_POST['pppoe_peerdns'] == "on")) ? true : false; 
	$username = isset($_POST['username']) ? $_POST['username'] : "";
	$password = isset($_POST['password']) ? $_POST['password'] : "";
	$mtu = isset($_POST['mtu']) ? $_POST['mtu'] : "";

	// TODO: push this weirdness down into the API
	if ($bootproto == Iface::BOOTPROTO_PPPOE)
		$type = Iface::TYPE_PPPOE;
	else if (! empty($_POST['essid']))
		$type = Iface::TYPE_WIRELESS;
	else
		$type = Iface::TYPE_ETHERNET;

	$interface = new Iface($eth);

	try {
		// Wireless
		//---------

		if ($type == Iface::TYPE_WIRELESS) {

			$essid = isset($_POST['essid']) ? $_POST['essid'] : ""; 
			$mode = isset($_POST['mode']) ? $_POST['mode'] : ""; 
			$key = isset($_POST['key']) ? $_POST['key'] : ""; 
			$rate = isset($_POST['rate']) ? $_POST['rate'] : ""; 

			if ($bootproto == Iface::BOOTPROTO_DHCP) {
				$interface->SaveWirelessConfig(true, "", "", "", $essid, "1", $mode, $key, $rate, $peerdns);
			} else {
				$interface->SaveWirelessConfig(false, $ip, $netmask, $gateway, $essid, "1", $mode, $key, $rate, $peerdns, $mtu);
			}

		// PPPoE
		//------

		} else if ($bootproto == Iface::BOOTPROTO_PPPOE) {
			$firewall->RemoveInterfaceRole($eth);
			$eth = $interface->SavePppoeConfig($eth, $username, $password, $mtu, $pppoe_peerdns);

		// Ethernet
		//---------

		} else if ($bootproto == Iface::BOOTPROTO_DHCP) {
			$interface->SaveEthernetConfig(true, "", "", "", $dhcp_hostname, $peerdns);
		} else if ($bootproto == Iface::BOOTPROTO_STATIC) {
			$gateway_required = ($role == Firewall::CONSTANT_EXTERNAL) ? true : false;
			$interface->SaveEthernetConfig(false, $ip, $netmask, $gateway, "", false, $gateway_required);
		}

		// Reset the routes
		//-----------------

		if ($role == Firewall::CONSTANT_EXTERNAL)
			$routes->SetGatewayDevice($eth);
		else if ($routes->GetGatewayDevice() == $eth)
			$routes->DeleteGatewayDevice();

		// Set firewall roles
		//-------------------

		$firewall->SetInterfaceRole($eth, $role);

		// Enable interface 
		//-----------------

		// Response time can take too long on PPPoE and DHCP connections.

		if (($bootproto == Iface::BOOTPROTO_DHCP) || ($bootproto == Iface::BOOTPROTO_PPPOE))
			$interface->Enable(true);
		else
			$interface->Enable(false);

		// Restart syswatch
		//-----------------

		$syswatch = new Syswatch();
		$syswatch->Reset();

	} catch (ValidationException $e) {
		$errormessage = $interface->GetValidationErrors(true);
		$_POST['DisplayEdit'][$eth] = true;
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
		$_POST['DisplayEdit'][$eth] = true;
	}

} else if (isset($_POST['action']) && ($_POST['action'] == "UpdateNetworkSettings")) {

	try {
		$mode = isset($_POST['mode']) ? $_POST['mode'] : "";

		// Do not let users get locked out with the firewall.
		// If web browser is coming from an untrusted network, warn the user.

		$all_ok = true;

		$resolver->SetNameservers($_POST['ns']);

		if ($mode != Firewall::CONSTANT_TRUSTEDSTANDALONE) {
			$interfaces = new IfaceManager();
			$ethlist = $interfaces->GetInterfaceDetails();

			foreach ($ethlist as $eth => $info) {
				$on_network = (isset($info['address']) && ($info['address'] == $_SERVER['SERVER_ADDR'])) ? true : false;

				if ($on_network && ($info['role'] != Firewall::CONSTANT_LAN)) {
					$incoming = new FirewallIncoming();
					$already_open = $incoming->CheckPort("TCP", "81");
					
					if ($already_open != Firewall::CONSTANT_ENABLED) {
						$errormessage = FIREWALL_LANG_WARNING_LOCKED_OUT;
						$all_ok = false;
					}

					break;
				}
			}
		}

		if ($all_ok) {
			// FIXME: set sane IP ranges for PPTP and OpenVPN configs
			$firewall->SetMode($mode);
			$syswatch = new Syswatch();
			$syswatch->ReconfigureNetworkSettings();
		}
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

	if (! $errormessage)
		$_SESSION['setup_step'] = key($_POST['GoToNextStep']);

///////////////////////////////////////////////////////////////////////////////
// Register
///////////////////////////////////////////////////////////////////////////////

} else if (isset($_REQUEST['Reset'])) {
	unset($_SESSION['system_registered']);
	$register->Reset(true);

} else if (isset($_REQUEST['DisplayRegistrationType'])) {
	// do the registration
} else if (isset($_POST['action']) && ($_POST['action'] == "SkipRegistration")) {
	$_SESSION['setup_step'] = key($_POST['GoToNextStep']);

///////////////////////////////////////////////////////////////////////////////
// Date/time
///////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['action']) && ($_POST['action'] == "UpdateDateTime")) {
	try {
		$ntptime->SetTimeZone($_POST['timezone']);
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

// TODO: check to see if network is up
/*
	try {
		$result = $ntptime->Synchronize();
	} catch (Exception $e) {
		//	Not fatal
	}
*/

	if (! $errormessage)
		$_SESSION['setup_step'] = key($_POST['GoToNextStep']);

///////////////////////////////////////////////////////////////////////////////
// Domain
///////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['action']) && ($_POST['action'] == "UpdateDomain")) {

	$domain = isset($_POST['domain']) ? $_POST['domain'] : "";

	// KLUDGE/TODO: Pre-validate in this case since a single bad hostname 
	// will generate lots of validation errors.  The different types of
	// validation here is an example of how messy things can get.

	try {
		// Need this variable before it is set
		$olddomain = $organization->GetDomain();

		// This will generate a validation error
		$organization->SetDomain($domain);

		$errors = $organization->GetValidationErrors(true);
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

	if (! empty($errors))
		$errormessage = empty($errormessage) ? $errors[0] : $errormessage . "<br>" . $errors[0];

	// We should be validation-error free now.

	if (! $errormessage) {
		try {
			if (file_exists("../../api/DnsMasq.class.php")) {
				require_once("../../api/DnsMasq.class.php");
				$dnsmasq = new DnsMasq();
				$dnsmasq->SetDomainName($domain);
			}

			if (file_exists("../../api/Postfix.class.php")) {
				require_once("../../api/Postfix.class.php");
				$postfix = new Postfix();
				$postfix->SetDomain($domain);
			}

			if (file_exists("../../api/Httpd.class.php")) {
				require_once("../../api/Httpd.class.php");
				$httpd = new Httpd();

				// TODO: this logic should be pushed down into the API
				if ($httpd->IsDefaultSet())
					$httpd->SetDefaultHost($domain, '*.' . $domain);
				else
					$httpd->AddDefaultHost($domain);
			}

			if (file_exists("../../api/Pptpd.class.php")) {
				require_once("../../api/Pptpd.class.php");
				$pptpd = new Pptpd();
				$pptpd->SetDomain($domain);
			}

			if (file_exists("../../api/OpenVpn.class.php")) {
				require_once("../../api/OpenVpn.class.php");
				$openvpn = new OpenVpn();
				$openvpn->SetDomain($domain);
			}
		} catch (Exception $e) {
			// Not fatal
		}

		try {
			if (file_exists("../../api/UserManager.class.php")) {
				require_once("../../api/UserManager.class.php");
				$usermanager = new UserManager();
				$usermanager->RunInitialize();
			}
		} catch (Exception $e) {
			$errormessage = $e->GetMessage();
		}
	}

	if (! $errormessage)
		$_SESSION['setup_step'] = key($_POST['GoToNextStep']);

///////////////////////////////////////////////////////////////////////////////
// Organization
///////////////////////////////////////////////////////////////////////////////

} else if (isset($_POST['action']) && ($_POST['action'] == "UpdateOrganization")) {
	$hostname = isset($_POST['hostname']) ? $_POST['hostname'] : '';
	$name = isset($_POST['name']) ? $_POST['name'] : '';
	$unit = isset($_POST['unit']) ? $_POST['unit'] : '';
	$street = isset($_POST['street']) ? $_POST['street'] : '';
	$city = isset($_POST['city']) ? $_POST['city'] : '';
	$region = isset($_POST['region']) ? $_POST['region'] : '';
	$country = isset($_POST['country']) ? $_POST['country'] : '';
	$postalcode = isset($_POST['postalcode']) ? $_POST['postalcode'] : '';

	try {
        $organization->SetInternetHostname($hostname);
		$organization->SetName($name);
		$organization->SetUnit($unit);
		$organization->SetStreet($street);
		$organization->SetCity($city);
		$organization->SetRegion($region);
		$organization->SetCountry($country);
		$organization->SetPostalCode($postalcode);

		// If validation fails, bail right away
		$errors = $organization->GetValidationErrors(true);

		if (! empty($errors)) {
			$errormessage = $errors;
		} else {
			$domain = $organization->GetDomain();
			$hostname = $organization->GetInternetHostname();
  
			$hostnameobj->Set($hostname);

			// TODO: move this down into Hostname->Set()
			$hostnamechecker = new HostnameChecker();
			$hostnamechecker->AutoFix(true);

			if (file_exists("../../api/Httpd.class.php")) {
				require_once("../../api/Httpd.class.php");
				$httpd = new Httpd();
				$httpd->SetServerName($hostname);
			}

			if (file_exists("../../api/Postfix.class.php")) {
				require_once("../../api/Postfix.class.php");
				$postfix = new Postfix();
				$postfix->SetHostname($hostname);
			}

			$_SESSION['system_hostname'] = $hostname;

			// Initialize the SSL system
			$ssl = new Ssl();

			// TODO: push down into API
			if ($ssl->ExistsSystemCertificate())
				$ssl->DeleteCertificate('sys-0-cert.pem');

			$ssl->Initialize($hostname, $domain, $name, $unit, $city, $region, $country);

			$syswatch = new Syswatch();
			$syswatch->ReconfigureSystem();

			$webconfig = new Webconfig();
			$webconfig->SetSetupState(false);
		}
	} catch (Exception $e) {
		$errormessage = $e->GetMessage();
	}

	if (! $errormessage) {
		$_SESSION['setup_step'] = key($_POST['GoToNextStep']);
	}
}

///////////////////////////////////////////////////////////////////////////////
// Start
///////////////////////////////////////////////////////////////////////////////

if (! isset($_SESSION['setup_step']))
	$_SESSION['setup_step'] = "language";

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

$customheader = "<script type='text/javascript' src='network.js.php'></script>\n";
$customheader .= "<script type='text/javascript' src='organization.js.php'></script>\n";

WebHeader(WEB_LANG_PAGE_TITLE_WIZARD, "wizard", $customheader);

echo "<table cellspacing='0' cellpadding='0' border='0' width='100%'><tr><td width='200' valign='top'>";

WebMenuWizard($steps, $_SESSION['setup_step']);

echo "</td><td width='10'>&nbsp;</td><td valign='top' align='center'>";

if (isset($errormessage))
	WebDialogWarning($errormessage);

if ($_SESSION['setup_step'] == "language") {
	WebDialogInfo(WEB_LANG_HELP_LANGUAGE);
	DisplayLanguage();
	WebWizardNavigation("UpdateAdminLanguage", "" , "network");
/*
} else if ($_SESSION['setup_step'] == "password") {
	WebDialogInfo(WEB_LANG_HELP_PASSWORD);
	DisplayAdminPassword($password, $verify);
	WebWizardNavigation("UpdateAdminPassword", "language" , "network");
*/
} else if ($_SESSION['setup_step'] == "network") {
	WebDialogInfo(WEB_LANG_HELP_NETWORK);
	if (isset($_POST['DisplayEdit'])) {
		DisplayEdit(key($_POST['DisplayEdit']), $role, $bootproto, $ip, $netmask, $gateway, $dhcp_hostname,
		$peerdns, $username, $password, $mtu);
	} else if (isset($_POST['EditPppoe'])) {
		DisplayEditPppoe(key($_POST['EditPppoe']), $role, $pppoe_peerdns, $username, $password, $mtu);
	} else if (isset($_POST['ConfirmDeleteInterface'])) {
		DisplayConfirmDelete("DeleteInterface", key($_POST['ConfirmDeleteInterface']));
	} else {
		// GetInterfaceDetails() takes time, so do it once here.
		try {
			$interfaces = new IfaceManager();
			$ethlist = $interfaces->GetInterfaceDetails();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}

		SanityCheck($ethlist);
		DisplayInterfaces($ethlist);
		WebWizardNavigation("UpdateNetworkSettings", "language" , "datetime");
	}
} else if ($_SESSION['setup_step'] == "register") {
	WebDialogInfo(WEB_LANG_HELP_REGISTER);
	DisplayRegister();
} else if ($_SESSION['setup_step'] == "datetime") {
	WebDialogInfo(WEB_LANG_HELP_TIME_ZONE);
	DisplayTime();
	WebWizardNavigation("UpdateDateTime", "network" , "domain");
} else if ($_SESSION['setup_step'] == "domain") {
	WebDialogInfo(WEB_LANG_HELP_DOMAIN);
	DisplayDomain($domain);
	WebWizardNavigation("UpdateDomain", "datetime" , "organization");
} else if ($_SESSION['setup_step'] == "organization") {
	// TODO: bad hack to wait for LDAP initialization
	WebDialogInfo(WEB_LANG_HELP_ORGANIZATION);
	echo "<div id='user-whirly'><br><br>" . WEBCONFIG_ICON_LOADING . "</div>";
	echo "<div id='user-state'>";
	DisplayOrganization($hostname, $name, $unit, $street, $city, $region, $country, $postalcode);
	WebWizardNavigation("UpdateOrganization", "domain" , "complete");
	echo "</div>";
	echo "<script type='text/javascript'>hide('user-state')</script>";
} else if ($_SESSION['setup_step'] == "complete") {
	sleep(3);
	WebDialogInfo(WEB_LANG_HELP_COMPLETE);
	DisplayComplete();
}


echo "</td></tr></table>";

WebFooter("wizard");

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDomain()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDomain($domain)
{
	global $organization;

	try {
		if (empty($domain)) {
			$domain = $organization->GetDomain();
			if (empty($domain))
				$domain = $organization->SuggestDefaultDomain();
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_DOMAIN_CONFIGURATION);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_DOMAIN . "</td>
			<td><input type='text' name='domain' size='25' value='$domain' /> " . WEB_LANG_DOMAIN_EXAMPLE . "</td>
		</tr>
	";
	WebTableClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRegister()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRegister()
{
	// Set our post variables
	$name = isset($_POST['name']) ? $_POST['name'] : "";
	$type = isset($_POST['type']) ? $_POST['type'] : "";
	$terms = isset($_POST['terms']) ? $_POST['terms'] : "";
	$serial = isset($_POST['serial']) ? $_POST['serial'] : "";
	$service = isset($_POST['service']) ? $_POST['service'] : "";
	$servicelevel = isset($_POST['servicelevel']) ? $_POST['servicelevel'] : "";
	$username = isset($_POST['sdnusername']) ? $_POST['sdnusername'] : "";
	$password = isset($_POST['sdnpassword']) ? $_POST['sdnpassword'] : "";

	// Step 0 -- Already registered
	//-----------------------------

	if (isset($_SESSION['system_registered']) && $_SESSION['system_registered']) {
		DisplaySummary();
		WebWizardNavigation("SkipRegistration", "network" , "datetime");

	// Step 2 -- Registration type
	//----------------------------

	} else if (isset($_POST['DisplayRegistrationType'])) {
		DisplayRegistrationType($username, $password);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);

	// Step 3 -- Licensing
	//--------------------

	} else if (isset($_POST['DisplayLicensing']) && $_POST['type'] == 'DisplayLicensingAdd') {
		DisplayLicensingAdd($username, $password);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);
	} else if (isset($_POST['DisplayLicensing']) && $_POST['type'] == 'DisplayLicensingUpgrade') {
		DisplayLicensingUpgrade($username, $password);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);
	} else if (isset($_POST['DisplayLicensing']) && $_POST['type'] == 'DisplayLicensingReinstall') {
		DisplayLicensingReinstall($username, $password);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);

	// Step 4 -- Service Level
	//----------------------------

	} else if (isset($_POST['DisplayServiceLevel'])) {
		DisplayServiceLevel($username, $password, $name, $serial, $type);

	// Step 5 -- Terms of service
	//----------------------------

	} else if (isset($_POST['DisplayTos'])) {
		DisplayTos($username, $password, $name, $serial, $type, $service);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);

	// Step 6 -- Confirm
	//------------------

	} else if (isset($_POST['DisplayConfirm'])) {
		DisplayConfirm($username, $password, $name, $serial, $type, $service, $servicelevel, $terms);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);

	// Step 7 -- Submit
	//-----------------

	} else if (isset($_POST['SubmitRegistration'])) {
		SubmitRegistration($username, $password, $name, $serial, $type, $service, $terms);
		WebWizardNavigation("SkipRegistration", "network" , "datetime");

	// Step 1 -- Login
	//----------------

	} else {
		DisplayLogin($username);
		WebWizardNavigation("SkipRegistration", "network" , "datetime", WEB_LANG_SKIP_REGISTER);

	}
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayComplete()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayComplete() {

	WebDialogWarning(WEB_LANG_WARNING_CERT);

	if ($_SERVER["REMOTE_ADDR"] == "127.0.0.1") {
		WebDialogInfo("<p>" . WEB_LANG_CONTINUE_ON_CONSOLE . "</p>
			<ul>
				<li><a href='network.php'>" . NETWORK_LANG_NETWORK . "</a></li>
				<li><a href='firewall.php'>" . LOCALE_LANG_FIREWALL . "</a></li>
			</ul>"
		);
	} else {
		WebFormOpen("index.php", "post");
		echo "<div style='text-align:center; padding:30 0 30 0'>" . 
			WebButton("gotoindex", WEB_LANG_CONTINUE_WITH_CONFIG, WEBCONFIG_ICON_CONTINUE) . "
			</div>
		";
		WebFormClose();
	}
}

// vim: syntax=php ts=4
?>
