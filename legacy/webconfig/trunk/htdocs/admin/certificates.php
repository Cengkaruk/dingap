<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2008 Point Clark Networks.
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
require_once("../../api/Country.class.php");
require_once("../../api/Organization.class.php");
require_once("../../api/Ssl.class.php");
require_once("../../api/Syswatch.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

define('WEB_ICON_CA', ReplacePngTags("/images/icon-ssl-ca.png", ""));
define('WEB_ICON_WEB_FTP', ReplacePngTags("/images/icon-ssl-web-ftp.png", ""));
define('WEB_ICON_EMAIL', ReplacePngTags("/images/icon-ssl-email.png", ""));

///////////////////////////////////////////////////////////////////////////////
//
// Security Check
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Special Downloads
//
///////////////////////////////////////////////////////////////////////////////

$ssl = new Ssl();

# This needs to go before server starts sending response
if (isset($_POST['Install']) || isset($_POST['Download'])) {

	if (isset($_POST['Install']))
		$filename = key($_POST['Install']);
	else
		$filename = key($_POST['Download']);
		
	try {
		$cert = $ssl->GetCertificateAttributes($filename);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

		if (isset($_POST['Download'])) {
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=" . $filename . ";");
		} else {
			if (ereg(Ssl::TYPE_P12, $filename))
				header("Content-Type: application/x-pkcs12-signature");
			else if ($cert['ca'])
				header("Content-Type: application/x-x509-ca-cert");
			else
				header("Content-Type: application/x-x509-user-cert");
		}

		header("Content-Transfer-Encoding: binary");

		clearstatcache();
		header("Content-Length: " . filesize(Ssl::DIR_SSL . "/" . $filename));

		readfile(Ssl::DIR_SSL . "/" . $filename);
		exit(0);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-ssl.png", WEB_LANG_PAGE_INTRO, true);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$showdetails = false;

if (isset($_POST['Cancel'])) {
	// Do nothing
} else if (isset($_POST['CreateCertAuth'])) {
	try {
		# Override defaults with user prefs
		$ssl->SetRsaKeySize($_POST['key_size']);
		$ssl->SetCommonName($_POST['common_name']);
		$ssl->SetOrganizationName($_POST['org_name']);
		$ssl->SetOrganizationalUnit($_POST['org_unit']);
		$ssl->SetEmailAddress($_POST['email']);
		$ssl->SetLocality($_POST['city']);
		$ssl->SetStateOrProvince($_POST['region']);
		$ssl->SetCountryCode($_POST['country']);
		# Create CA
		$ssl->CreateCertificateAuthority();
		unset($_POST);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['CreateCert'])) {
	try {
		// Override defaults with user prefs
		$ssl->SetRsaKeySize($_POST['key_size']);
		$ssl->SetOrganizationName($_POST['org_name']);
		$ssl->SetOrganizationalUnit($_POST['org_unit']);
		$ssl->SetEmailAddress($_POST['email']);
		$ssl->SetLocality($_POST['city']);
		$ssl->SetStateOrProvince($_POST['region']);
		$ssl->SetCountryCode($_POST['country']);
		$ssl->SetPurpose($_POST['purpose']);
		$ssl->SetTerm($_POST['term']);

		// Create certificate
		$filename = $ssl->CreateCertificateRequest($_POST['common_name'], $_POST['purpose']);

		// Sign certificate and create PKCS12
		if ($_POST['sign'] == Ssl::SIGN_SELF) {
			$filename = $ssl->SignCertificateRequest($filename);
			if ($_POST['purpose'] == Ssl::PURPOSE_CLIENT_CUSTOM)
				$ssl->ExportPkcs12($filename, $_POST['password'], $_POST['verify']);
		}

		// Restart servers if creating local server certificate
		if ($_POST['purpose'] == Ssl::PURPOSE_SERVER_LOCAL) {
			$syswatch = new Syswatch();
			$syswatch->ReconfigureSystem();
		}

		unset($_POST);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['Import'])) {
	$filename = key($_POST['Import']);
	try {
		if (isset($_POST['Confirm'])) {
			$ssl->ImportSignedCertificate($filename, $_POST['cert']);
		} else {
			DisplayImport($filename);
			$showdetails = true;
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayImport($filename);
		$showdetails = true;
	}
} else if (isset($_POST['CreatePkcs12'])) {
	$filename = key($_POST['CreatePkcs12']);
	try {
		if (isset($_POST['Confirm'])) {
			$ssl->ExportPkcs12($filename, $_POST['password'], $_POST['verify']);
		} else {
			DisplayCreatePkcs12($filename);
			$showdetails = true;
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayCreatePkcs12($filename);
		$showdetails = true;
	}
} else if (isset($_POST['View'])) {
	$filename = key($_POST['View']);
	$showdetails = true;
} else if (isset($_POST['Renew'])) {
	$filename = key($_POST['Renew']);
	try {
		$status = $ssl->IsSignedByLocalCA($filename);
		if (isset($_POST['Confirm'])) {
			$term = isset($_POST['term']) ? $_POST['term'] : "";
			$password = isset($_POST['password']) ? $_POST['password'] : null;
			$verify = isset($_POST['verify']) ? $_POST['verify'] : null;
			$pkcs12 = isset($_POST['pkcs12']) ? $_POST['pkcs12'] : null;

			$ssl->RenewCertificate($filename, $_POST['term'], $_POST['password'], $_POST['verify'], $_POST['pkcs12']);

			if (! $status) {
				$filename = ereg_replace("-cert\\.pem", "-req.pem", $filename);
				$showdetails = true;
			}
		} else {
			DisplayRenew($filename, $status);
			$showdetails = true;
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		DisplayRenew($filename, $status);
		$showdetails = true;
	}
} else if (isset($_POST['Update'])) {
	try {
		if (isset($_POST['Confirm'])) {
			$ssl->SetOrganizationName($_POST['org_name']);
			$ssl->SetOrganizationalUnit($_POST['org_unit']);
			$ssl->SetEmailAddress($_POST['email']);
			$ssl->SetLocality($_POST['city']);
			$ssl->SetStateOrProvince($_POST['region']);
			$ssl->SetCountryCode($_POST['country']);
			$ssl->UpdateCertificate($_POST['common_name'], $_POST['Update']);
		} else {
			$showdetails = true;
			$filename = key($_POST['Update']);
			DisplayUpdate(key($_POST['Update']));
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['Delete'])) {
	try {
		if (isset($_POST['Confirm'])) {
			$ssl->DeleteCertificate($_POST['Delete']);
		} else {
			DisplayDelete(key($_POST['Delete']));
			# If PKCS12 file, don't hide summary
			if (!ereg(Ssl::TYPE_P12, key($_POST['Delete']))) 
				$showdetails = true;
			$filename = key($_POST['Delete']);
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

try {
	$ca_exists = $ssl->ExistsCertificateAuthority();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (!$ca_exists) {
	DisplayCertificateAuthority();
} else if ($showdetails) {
	DisplayDetails($filename);
} else if (isset($_POST['DisplayAddSystemCertificate'])) {
	$_POST['purpose'] = Ssl::PURPOSE_SERVER_LOCAL;
	DisplayAdd();
} else {
	DisplayOverview();
	DisplayUserSummary();
	DisplayCustomSummary();
	DisplayUnsigned();
	DisplayAdd();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S 
/////////////////////////////////////////////////////////////////////////////// 

///////////////////////////////////////////////////////////////////////////////
//
// DisplayCertificateAuthority()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayCertificateAuthority()
{
	global $ssl;

	// Load information from our generic organization information
	// (usually provided during the system setup).

	try {
		$key_options = $ssl->GetRSAKeySizeOptions();

		$country = new Country();
		$countries = $country->GetList();

		$organization = new Organization();
		$common_name_default = $organization->GetDomain();
		$org_name_default = $organization->GetName();
		$org_unit_default = $organization->GetUnit();
		$city_default = $organization->GetCity();
		$region_default = $organization->GetRegion();
		$country_default = $organization->GetCountry();

		if (empty($country_default))
			$country_default = "US";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$common_name = isset($_POST['common_name']) ? $_POST['common_name'] : $common_name_default;
	$org_name = isset($_POST['org_name']) ? $_POST['org_name'] : $org_name_default;
	$org_unit = isset($_POST['org_unit']) ? $_POST['org_unit'] : $org_unit_default;
	$city = isset($_POST['city']) ? $_POST['city'] : $city_default;
	$region = isset($_POST['region']) ? $_POST['region'] : $region_default;
	$country = isset($_POST['country']) ? $_POST['country'] : $country_default;
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$key_size = isset($_POST['key_size']) ? $_POST['key_size'] : Ssl::DEFAULT_KEY_SIZE;

	$country_options = "";

	foreach ($countries as $code => $name) {
		if ($country == $code)
			$selected = "selected";
		elseif ($country == $name)
			$selected = "selected";
		else
			$selected = "";

		$country_options .= "<option value='" . $code . "' $selected>$name - $code</option>\n";
	}

	WebDialogInfo(WEB_LANG_CA_REQUIRED);

	WebFormOpen();
	WebTableOpen(WEB_LANG_CREATE_CA, "80%");
	echo "
		<tr>
			<td width='250' class='mytablesubheader' nowrap>" . WEB_LANG_KEY_SIZE . "</td>
			<td>" . WebDropDownHash("key_size", $key_size, $key_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_COMMON_NAME . "</td>
			<td><input type='text' name='common_name' value='" . $common_name . "' style='width:280px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ORG_NAME . "</td>
			<td><input type='text' name='org_name' value='" . $org_name . "' style='width:280px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ORG_UNIT . "</td>
			<td><input type='text' name='org_unit' value='" . $org_unit . "' style='width:180px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_CITY . "</td>
			<td><input type='text' name='city' value='" . $city . "' style='width:180px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_REGION . "</td>
			<td><input type='text' name='region' value='" . $region . "' style='width:180px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_COUNTRY . "</td>
			<td><select name='country' style='width:280px'>$country_options</select></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL . "</td>
			<td><input type='text' name='email' value='" . $email . "' style='width:280px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td>" . WebButtonCreate("CreateCertAuth") . "</td>
		</tr>
	";
	WebTableClose("80%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd()
{
	global $ssl;

	try {
		// SSL information
		$key_options = $ssl->GetRSAKeySizeOptions();
		$sign_options = $ssl->GetSigningOptions();
		$term_options = $ssl->GetTermOptions();
		$server_cert_exists = $ssl->ExistsSystemCertificate();

		// Country list
		$country = new Country();
		$countries = $country->GetList();

		// Load information from our generic organization information
		// (usually provided during the system setup).
		$organization = new Organization();
		$common_name_default = $organization->GetDomain();
		$org_name_default = $organization->GetName();
		$org_unit_default = $organization->GetUnit();
		$city_default = $organization->GetCity();
		$region_default = $organization->GetRegion();
		$country_default = $organization->GetCountry();

		if (empty($country_default))
			$country_default = "US";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$purpose = isset($_POST['purpose']) ? $_POST['purpose'] : Ssl::PURPOSE_SERVER_CUSTOM;
	$key_size = isset($_POST['key_size']) ? $_POST['key_size'] : Ssl::DEFAULT_KEY_SIZE;
	$common_name = isset($_POST['common_name']) ? $_POST['common_name'] : $common_name_default;
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$term = isset($_POST['term']) ? $_POST['term'] : Ssl::TERM_10YEAR;
	$sign = isset($_POST['sign']) ? $_POST['sign'] : Ssl::SIGN_SELF;
	$org_name = isset($_POST['org_name']) ? $_POST['org_name'] : $org_name_default;
	$org_unit = isset($_POST['org_unit']) ? $_POST['org_unit'] : $org_unit_default;
	$city = isset($_POST['city']) ? $_POST['city'] : $city_default;
	$region = isset($_POST['region']) ? $_POST['region'] : $region_default;
	$country = isset($_POST['country']) ? $_POST['country'] : $country_default;

	// Countries can be either abbreviated or the full name.  

	$country_dropdown = "";

	foreach ($countries as $code => $name) {
		if ($country == $code)
			$selected = "selected";
		elseif ($country == $name)
			$selected = "selected";
		else
			$selected = "";

		$country_dropdown .= "<option value='" . $code . "' $selected>$name - $code</option>\n";
	}

	$purpose_options = array(
		Ssl::PURPOSE_SERVER_CUSTOM => SSL_LANG_PURPOSE_SERVER,
		Ssl::PURPOSE_CLIENT_CUSTOM => SSL_LANG_PURPOSE_EMAIL
	);

	if (! $server_cert_exists)
		$purpose_options[Ssl::PURPOSE_SERVER_LOCAL] = SSL_LANG_PURPOSE_LOCAL;

	WebFormOpen();
	WebTableOpen(WEB_LANG_CREATE_CERTIFICATE, "500");
	echo "
		<tr>
			<td width='250' class='mytablesubheader' nowrap>" . SSL_LANG_PURPOSE . "</td>
			<td>" . WebDropDownHash("purpose", $purpose, $purpose_options, 0, "form.submit()") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_KEY_SIZE . "</td>
			<td>" . WebDropDownHash("key_size", $key_size, $key_options) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_COMMON_NAME . "</td>
			<td><input type='text' name='common_name' value='" . $common_name . "' style='width:280px' /></td>
		</tr>
	";
	if ($purpose == Ssl::PURPOSE_CLIENT_CUSTOM)
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL. "</td>
				<td><input type='text' name='email' value='" . $email . "' style='width:280px' /></td>
			</tr>
		";
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_SIGN . "</td>
			<td>" . WebDropDownHash("sign", $sign, $sign_options, 0, "form.submit()") . "</td>
		</tr>
	";
	if ($sign == Ssl::SIGN_SELF)
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_TERM . "</td>
				<td>" . WebDropDownHash("term", $term, $term_options) . "</td>
			</tr>
	";
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ORG_NAME. "</td>
			<td><input type='text' name='org_name' value='" . $org_name . "' style='width:280px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ORG_UNIT . "</td>
			<td><input type='text' name='org_unit' value='" . $org_unit . "' style='width:180px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_CITY. "</td>
			<td><input type='text' name='city' value='" . $city . "' style='width:180px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_REGION . "</td>
			<td><input type='text' name='region' value='" . $region . "' style='width:180px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_COUNTRY . "</td>
			<td><select name='country' style='width:280px'>$country_dropdown</SElect></td>
		</tr>
	";
	if ($purpose != Ssl::PURPOSE_CLIENT_CUSTOM)
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL. "</td>
				<td><input type='text' name='email' value='" . $email . "' style='width:280px' /></td>
			</tr>
		";
	if ($purpose == Ssl::PURPOSE_CLIENT_CUSTOM)
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_IMPORT_PASSWORD . "</td>
				<td><input type='password' name='password' value='' style='width:180px' /></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_PASSWORD_VERIFY . "</td>
				<td><input type='password' name='verify' style='width:180px' value='' /></td>
			</tr>
		";
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td>" . WebButtonCreate("CreateCert") . "</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayOverview()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayOverview()
{
	global $ssl;

	try {
		$ca_attrs = $ssl->GetCertificateAuthorityAttributes();
		$ca_filename = basename($ca_attrs['filename']);
	} catch (SslCertificateNotFoundException $e) {

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	try {
		$system_attrs = $ssl->GetSystemCertificateAttributes();
		$system_filename = basename($system_attrs['filename']);
		$system_html = WebButtonView("View[$system_filename]");
	} catch (SslCertificateNotFoundException $e) {
		$system_html = WebButtonCreate("DisplayAddSystemCertificate");
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_SERVER_CERTIFICATES, "500");
	echo "
		<tr>
			<td width='250' nowrap class='mytablesubheader'>" . SSL_LANG_CERTIFICATE_AUTHORITY . "</td>
			<td nowrap>" . WebButtonView("View[$ca_filename]") . "</td>
		</tr>
		<tr>
			<td nowrap class='mytablesubheader'>" . SSL_LANG_SYSTEM_CERTIFICATE . "</td>
			<td nowrap>$system_html</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUserSummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUserSummary()
{
	global $ssl;

	try {
		$certs = $ssl->GetCertificates(Ssl::TYPE_CRT);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$row_data = '';

	foreach ($certs as $filename => $cert) {
		// TODO: this should be an attribute instead of the preg_match below
		if (!preg_match("/^client/", $filename))
			continue;

		$row_data .= "
			<tr>
				<td nowrap>" . $cert['common_name'] . "</td>
				<td nowrap>" . date('F d Y', strtotime($cert['expireNotAfter'])) . "</td>
				<td nowrap>" . $cert['key_size'] . "b</td>
				<td align='center' nowrap>" . WebButtonView("View[$filename]") . "</td>
			</tr>
		";
	}
	
	if (! $row_data)
		$row_data = "<tr><td colspan='4' align='center'>" . WEB_LANG_NO_CERTS . "</td></tr>"; 

	WebFormOpen();
	WebTableOpen(SSL_LANG_USER_CERTIFICATES, "500");
	WebTableHeader(
		LOCALE_LANG_USERNAME . "|" . 
		WEB_LANG_EXPIRE . "|" .
		WEB_LANG_KEY_SIZE . "|&nbsp;"
	);
	echo $row_data;
	WebTableClose("500");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUnsigned()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUnsigned()
{
	global $ssl;

	try {
		$certs = $ssl->GetCertificates(Ssl::TYPE_REQ);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$row_data = '';

	foreach ($certs as $filename => $cert) {
		$row_data .= "
			<tr>
				<td nowrap>" . $cert['common_name'] . "</td>
				<td nowrap>" . $cert['key_size'] . "b</td>
				<td nowrap>" .
					WebButtonView("View[$filename]") .
					WebButtonDelete("Delete[$filename]") . "
				</td>
			</tr>";
	}

	if (! $row_data)
		return;

	WebFormOpen();
	WebTableOpen(WEB_LANG_UNSIGNED_CERTIFICATES, "500");
	WebTableHeader(WEB_LANG_COMMON_NAME . "|" . WEB_LANG_KEY_SIZE . "|");
	echo $row_data;
	WebTableClose("500");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDetails()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDetails($filename)
{
	$ssl = new Ssl();

	try {
		$cert = $ssl->GetCertificateAttributes($filename);
		$lines = $ssl->GetCertificatePem($filename);

		$pem = "";
		foreach ($lines as $line)
			$pem .= $line . "<br />";

		unset($lines);
		$lines = $ssl->GetCertificateText($filename);

		$text = "";
		foreach ($lines as $line)
			$text .= $line . "<br />";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_CERTIFICATE_DETAILS, "100%");
	echo "
		<tr class='mytableheader'>
			<td colspan='2'>" . WEB_LANG_MISCELLANEOUS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' width='100' nowrap>" . WEB_LANG_FILE . "</td>
			<td>" . Ssl::DIR_SSL . "/$filename</td>
		</tr>
	";

	if (!ereg(Ssl::TYPE_REQ, $filename)) {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_ISSUED . "</td>
				<td>" . date('F d Y', strtotime($cert['expireNotBefore'])) . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_EXPIRE . "</td>
				<td>" . date('F d Y', strtotime($cert['expireNotAfter'])) . "</td>
			</tr>
		";
	}

	echo "<tr><td class='mytablesubheader' valign='top' nowrap>" . LOCALE_LANG_ACTION . "</td>";
	echo "<td>";

	if (!ereg(Ssl::TYPE_REQ, $filename))
		echo WebButton("Install[$filename]", LOCALE_LANG_INSTALL, WEBCONFIG_ICON_UPDATE) . "<br />"; 

	echo WebButton("Download[$filename]", LOCALE_LANG_DOWNLOAD, WEBCONFIG_ICON_UPDATE);

	if (ereg(Ssl::TYPE_REQ, $filename))
		echo "<br />" . WebButton("Import[$filename]", WEB_LANG_IMPORT, WEBCONFIG_ICON_ADD);

	if (ereg(Ssl::TYPE_CRT, $filename) && $cert['smime'] && !$ssl->IsPkcs12Exist($filename))
		echo "<br />" . WebButton("CreatePkcs12[$filename]", WEB_LANG_CREATE_PKCS12, WEBCONFIG_ICON_ADD);

	if (ereg(Ssl::TYPE_CRT, $filename) && !ereg(Ssl::FILE_CA_CRT, $filename))
		echo "<br />" . WebButtonRenew("Renew[$filename]");

	echo "<br />" . WebButtonDelete("Delete[$filename]");
	echo "<br />" . WebButtonUpdate("Update[$filename]");
	echo "<br />" . WebButton("Cancel", WEB_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK);
	echo "</td></tr>";

	echo "
		<tr class='mytableheader'><td colspan='2'>" . WEB_LANG_CERT_PEM . "</td></tr>
		<tr>
			<td colspan='2' style='font-family: monospace; font-size: 10px;'>$pem</td>
		</tr>
		<tr class='mytableheader'>
			<td colspan='2'>" . WEB_LANG_CERT_TEXT . "</td>
		</tr>
		<tr>
			<td colspan='2' style='font-family: monospace; font-size: 10px;'>$text</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDelete()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDelete($filename)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "80%");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='Delete' value='$filename'>
		  <p>" . WEBCONFIG_ICON_WARNING . " " . LOCALE_LANG_CONFIRM_DELETE . " <b><i>" . $filename . "</i></b>?</p>" .
		  WebButtonDelete("Confirm") . "&#160;" . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("80%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUpdate()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUpdate($filename)
{
	$ssl = new Ssl();
	$country = new Country();

	try {
		$cert = $ssl->GetCertificateAttributes($filename);
		$countries = $country->GetList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$common_name = isset($_POST['common_name']) ? $_POST['common_name'] : $cert['common_name'];
	$email = isset($_POST['email']) ? $_POST['email'] : $cert['email'];
	$org_name = isset($_POST['org_name']) ? $_POST['org_name'] : $cert['org_name'];
	$org_unit = isset($_POST['org_unit']) ? $_POST['org_unit'] : $cert['org_unit'];
	$city = isset($_POST['city']) ? $_POST['city'] : $cert['city'];
	$region = isset($_POST['region']) ? $_POST['region'] : $cert['region'];
	$country = isset($_POST['country']) ? $_POST['country'] : $cert['country'];

	$country_options = "";

	foreach ($countries as $code => $name) {
		if ($country == $code)
			$selected = "selected";
		elseif ($country == $name)
			$selected = "selected";
		else
			$selected = "";

		$country_options .= "<option value='" . $code . "' $selected>$name - $code</option>\n";
	}

	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "80%");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_COMMON_NAME . "</td>
		<td><input type='text' name='common_name' value='$common_name' style='width:280px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_ORG_NAME . "</td>
		<td><input type='text' name='org_name' value='$org_name' style='width:280px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_ORG_UNIT . "</td>
		<td><input type='text' name='org_unit' value='$org_unit' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_CITY . "</td>
		<td><input type='text' name='city' value='$city' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_REGION . "</td>
		<td><input type='text' name='region' value='$region' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_COUNTRY . "</td>
		<td><select name='country' style='width:280px'>$country_options</select></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL . "</td>
		<td><input type='text' name='email' value='$email' style='width:280px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160;<input type='hidden' name='Update' value='$filename'></td>
		<td>" . WebButtonUpdate("Confirm") . "</td>
	  </tr>

	";
	WebTableClose("80%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayImport()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayImport($filename)
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_IMPORT_FOR . " " . $filename, "100%");
	echo "
	  <tr>
		<td nowrap>" . WEB_LANG_IMPORT_SIGNED_CERT . "</td>
	  </tr>
	  <tr>
		<td align='center'>
		  <input type='hidden' name='Confirm' value='Confirm'>
		  <textarea name='cert' cols='60' rows='17' style='font-family: monospace; font-size: 10px;'>" . trim($_POST['cert']) . "</textarea>
		  <p>" .
			WebButtonSave("Import[$filename]") . "&#160;&#160;" .
			WebButtonCancel("Cancel") . "
		  </p>
		</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRenew()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRenew($filename, $local)
{
	$ssl = new Ssl();

	try {
		$cert = $ssl->GetCertificateAttributes($filename);
		$options = $ssl->GetTermOptions();
		
		# If nothing has been posted yet, check to see if there is a PKCS12 file for default
		if (!isset($_POST['term'])) {
			$file = new File(Ssl::DIR_SSL . "/" . ereg_replace("-cert\\.pem", ".p12", $filename));
			if ($file->Exists())
				$_POST['pkcs12'] = 1;
		}

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$pkcs12_checkbox = isset($_POST['pkcs12']) ? "checked" : "";
	$term = isset($_POST['term']) ? $_POST['term'] : Ssl::TERM_1YEAR;

	$term_options = '';

	foreach($options as $opt=>$display_opt) {
		if ($term == $opt)
			$term_options .= "<option value='$opt' SELECTED>$display_opt</option>";
		else
			$term_options .= "<option value='$opt'>$display_opt</option>";
	}

	if (! $local)
		WebDialogInfo(WEB_LANG_REQUIRES_CA);

	WebFormOpen();
	if (! $local)
		WebTableOpen(LOCALE_LANG_RENEW . "/" . WEB_LANG_CREATE_CSR, "80%");
	else
		WebTableOpen(LOCALE_LANG_RENEW, "80%");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_COMMON_NAME . "</td>
		<td>" . $cert['common_name'] . "<input type='hidden' name='Renew[$filename]' value='$filename'></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL . "</td>
		<td>" . $cert['email'] . "</td>
	  </tr>
	";
	if ($local)
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_TERM . "</td>
		<td><select name='term'>$term_options</select></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_CREATE_PKCS12 . "</td>
		<td><input type='checkbox' name='pkcs12' onChange='form.submit()' $pkcs12_checkbox />
	  </tr>
	";
	if ($local && isset($_POST['pkcs12']))
		echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
		<td><input type='password' name='password' value='' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_PASSWORD_VERIFY . "</td>
		<td><input type='password' name='verify' style='width:180px' value='' /></td>
	  </tr>
	";
	echo "
	  <tr>
		<td class='mytablesubheader'>&nbsp; </td>
		<td>" . 
			WebButtonConfirm("Confirm[$filename]") . " " .
			WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("80%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayCreatePkcs12()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayCreatePkcs12($filename)
{
	try {
		$ssl = new Ssl();
		$cert = $ssl->GetCertificateAttributes($filename);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}
	WebFormOpen();
	WebTableOpen(WEB_LANG_CREATE_PKCS12, "80%");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL . "</td>
		<td>" . $cert['email'] . "<input type='hidden' name='Confirm' value='Confirm'></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_IMPORT_PASSWORD . "</td>
		<td><input type='password' name='password' value='' style='width:180px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_PASSWORD_VERIFY . "</td>
		<td><input type='password' name='verify' style='width:180px' value='' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap></td>
		<td>" . 
			WebButtonCreate("CreatePkcs12[$filename]") . "&#160;&#160;" .
			WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("80%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayCustomSummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayCustomSummary()
{
	global $ssl;

	try {
		$certs = $ssl->GetCertificates(Ssl::TYPE_CRT);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	# Signed Certificates
	# -------------------

	$row_data = '';

	foreach ($certs as $filename => $cert) {
		$icon = "";

		if ($cert['ca']) {
			continue;
		// TODO: this should be an attribute instead of the preg_match below
		} else if (preg_match("/^client/", $filename)) {
			continue;
		} else if (preg_match("/^sys/", $filename)) {
			continue;
		} else {
			if ($cert['smime'])
				$icon .= WEB_ICON_EMAIL;
			if ($cert['server']) {
				if ($icon)
					$icon .= "&#160;";
				$icon .= WEB_ICON_WEB_FTP;
			}
		}

		$row_data .= "
			<tr>
				<td width='30' align='center' nowrap>$icon</td>
				<td nowrap>" . $cert['common_name'] . "</td>
				<td nowrap>" . date('F d Y', strtotime($cert['expireNotAfter'])) . "</td>
				<td nowrap>" . $cert['key_size'] . "b</td>
				<td align='center' nowrap>" . WebButtonView("View[$filename]") . "</td>
			</tr>
		";
	}
	
	if (! $row_data)
		$row_data = "<tr><td colspan='5' align='center'>" . WEB_LANG_NO_CERTS . "</td></tr>"; 

	$legend = 
		WEB_ICON_WEB_FTP . " " . WEB_LANG_SERVER . " &#160; &#160; " .
		WEB_ICON_EMAIL . " " . WEB_LANG_EMAIL;

	WebFormOpen();
	WebTableOpen(WEB_LANG_SIGNED_CERTIFICATES, "500");
	WebTableHeader(
		"|" .
		WEB_LANG_COMMON_NAME . "|" . 
		WEB_LANG_EXPIRE . "|" .
		WEB_LANG_KEY_SIZE . "|"
	);
	echo "
		$row_data
		<tr>
			<td colspan='6' class='mytablelegend'>$legend</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();
}

// vi: syntax=php ts=4
?>
