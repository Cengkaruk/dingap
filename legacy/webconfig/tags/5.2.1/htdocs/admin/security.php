<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
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
require_once("../../api/File.class.php");
require_once("../../api/Hostname.class.php");
require_once("../../api/Organization.class.php");
require_once("../../api/Ssl.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Security Check
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

// Do not bother allowing root to do certificates (harmless, but...)
if (isset($_SESSION['system_login'])) {
	WebHeader(WEB_LANG_PAGE_TITLE);
	WebDialogWarning("Certificates not available for administrator account.");
	WebFooter();
	exit(0);
}

///////////////////////////////////////////////////////////////////////////////
//
// Handle Special Downloads
//
///////////////////////////////////////////////////////////////////////////////

$ssl = new Ssl();

if (isset($_POST['DownloadCertificateAuthority']) || 
	isset($_POST['DownloadCertificate']) || 
	isset($_POST['DownloadKey']) || 
	isset($_POST['DownloadPkcs12'])) {

	// TODO: hard-coded variables should be fixed

	if (isset($_POST['DownloadCertificateAuthority']))
		$filename = "ca-cert.pem";
	else if (isset($_POST['DownloadCertificate']))
		$filename = "client-" . $_SESSION['user_login'] . "-cert.pem";
	else if (isset($_POST['DownloadKey']))
		$filename = "private/client-" . $_SESSION['user_login'] . "-key.pem";
	else if (isset($_POST['DownloadPkcs12']))
		$filename = "client-" . $_SESSION['user_login'] . ".p12";

	try {
		clearstatcache();

		$basename = preg_replace("/.*\//", "", $filename);

		// TODO -- permission handling needs fixing
		// $tempfilename = COMMON_TEMP_DIR . "/" . $_SESSION['user_login'] . ".tempcert";
		$tempfilename = "/var/webconfig/tmp/" . $_SESSION['user_login'] . ".tempcert";

		$tempfile = new File($tempfilename, true);
		if ($tempfile->Exists())
			$tempfile->Delete();

		$certfile = new File(Ssl::DIR_SSL . "/" . $filename, true);
		$certfile->CopyTo($tempfilename);

		$tempfile->Chown("webconfig", "webconfig");

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=" . $basename . ";");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($tempfilename));

		readfile($tempfilename);
		$tempfile->Delete();
		exit(0);
	} catch (Exception $e) {
		WebHeader(WEB_LANG_PAGE_TITLE);
		WebDialogWarning($e->GetMessage());
		WebFooter();
	}

} else if (isset($_POST['DownloadVpnConfig']) && file_exists("../../api/OpenVpn.class.php")) {

	try {
		require_once("../../api/OpenVpn.class.php");

		$openvpn = new OpenVpn();

		$host = $openvpn->GetServerHostname();
		$config = $openvpn->GetClientConfiguration($_POST['vpnconfig'], $_SESSION['user_login']);

		$basename = $host . ".ovpn";
		$filename = "/var/tmp/$basename";
		$file = new File($filename, true);

		if ($file->Exists())
			$file->Delete();

		$file->Create("root", "root", "0644");
		$file->AddLines($config);

		clearstatcache();

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=" . $basename . ";");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($filename));

		readfile($filename);
		exit(0);
	} catch (Exception $e) {
		WebHeader(WEB_LANG_PAGE_TITLE);
		WebDialogWarning($e->GetMessage());
		WebFooter();
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-users.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['CreateCertificate'])) {
	$cert_password = isset($_POST['cert_password']) ? $_POST['cert_password'] : null;
	$cert_verify = isset($_POST['cert_verify']) ? $_POST['cert_verify'] : null;

	try {
		$ssl->CreateDefaultClientCertificate($_SESSION['user_login'], $cert_password, $cert_verify);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['DeleteCertificate'])) {
	try {
		$ssl->DeleteCertificate("client-" . $_SESSION['user_login'] . "-cert.pem");
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['Cancel'])) {
	$userinfo = null;
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

if ($ca_exists)
	DisplayCertificates($_SESSION['user_login']);
else
	WebDialogInfo(WEB_LANG_SECURITY_NOT_CONFIGURED);

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayCertificates()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayCertificates($username)
{
	$ssl = new Ssl();

	try {
		$cert_exists = $ssl->ExistsDefaultClientCertificate($username);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return ;
	}

	if (file_exists("../../api/OpenVpn.class.php")) {
		require_once("../../api/OpenVpn.class.php");

		try {
			$openvpn = new OpenVpn();
		} catch (Exception $e) {
			WebDialogWarning($e->getMessage());
			return ;
		}

		if (preg_match("/Linux/", $_SERVER["HTTP_USER_AGENT"]))
			$vpnconfig = OpenVPN::TYPE_OS_LINUX;
		else if (preg_match("/Mac.*OS/i", $_SERVER["HTTP_USER_AGENT"]))
			$vpnconfig = OpenVPN::TYPE_OS_MACOS;
		else 
			$vpnconfig = OpenVPN::TYPE_OS_WINDOWS;

		$vpnconfig_options = array();
		$vpnconfig_options[OpenVPN::TYPE_OS_WINDOWS] = "Windows";
		$vpnconfig_options[OpenVPN::TYPE_OS_LINUX] = "Linux";
		$vpnconfig_options[OpenVPN::TYPE_OS_MACOS] = "Mac";

		$vpn_html = "
			<tr>
				<td class='mytableheader' colspan='2' nowrap>" . WEB_LANG_VPN . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . SSL_LANG_CERTIFICATE_AUTHORITY . "</td>
				<td nowrap>" .  WebButtonDownload("DownloadCertificateAuthority") . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . SSL_LANG_CERTIFICATE . "</td>
				<td nowrap>" .  WebButtonDownload("DownloadCertificate") . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . SSL_LANG_KEY . "</td>
				<td nowrap>" .  WebButtonDownload("DownloadKey") . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_CONFIGURATION_FILE . "</td>
				<td nowrap>" .
					WebDropDownHash('vpnconfig', $vpnconfig, $vpnconfig_options) . 
					WebButtonDownload("DownloadVpnConfig") . "
				</td>
			</tr>
		";
	} else {
		$vpn_html = "";
	}

	if (file_exists("../../api/Cyrus.class.php")) {
		$mail_html = "
			<tr>
				<td class='mytableheader' colspan='2' nowrap>" . WEB_LANG_MAIL . "</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . SSL_LANG_PKCS12 . "</td>
				<td nowrap>" .  WebButtonDownload("DownloadPkcs12") . "</td>
			</tr>
		";
	} else {
		$mail_html = "";
	}

	if (empty($mail_html) && empty($vpn_html)) {
		WebDialogWarning(WEB_LANG_SECURITY_NOT_CONFIGURED);
		return;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "450");
	if ($cert_exists) {
		echo "
			$mail_html
			$vpn_html
		";
	} else {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
				<td><input type='password' name='cert_password' value='$cert_password' /></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
				<td><input type='password' name='cert_verify' value='$cert_verify' /></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>&nbsp; </td>
				<td nowrap>" . WebButtonCreate("CreateCertificate") . "</td>
			</tr>
		";
	}
	WebTableClose("450");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
