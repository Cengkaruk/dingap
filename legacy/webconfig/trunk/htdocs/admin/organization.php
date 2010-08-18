<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
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
require_once("organization.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-setup.png", WEB_LANG_PAGE_INTRO);
WebDialogInfo(WEB_LANG_CERTFICATE_CHANGE_WARNING);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$organization = new Organization();

$hostname = isset($_POST['hostname']) ? $_POST['hostname'] : '';
$name = isset($_POST['name']) ? $_POST['name'] : '';
$unit = isset($_POST['unit']) ? $_POST['unit'] : '';
$street = isset($_POST['street']) ? $_POST['street'] : '';
$city = isset($_POST['city']) ? $_POST['city'] : '';
$region = isset($_POST['region']) ? $_POST['region'] : '';
$country = isset($_POST['country']) ? $_POST['country'] : '';
$postalcode = isset($_POST['postalcode']) ? $_POST['postalcode'] : '';

if (isset($_POST['UpdateOrganizationSettings'])) {
	try {
		$oldhostname = $organization->GetInternetHostname();

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
			WebDialogWarning($errors);
		} else {
			$ssl = new Ssl();
			$certexists = $ssl->ExistsSystemCertificate();

			if (($oldhostname != $hostname) || !$certexists) {
				$domain = $organization->GetDomain();

				if (empty($domain))
					$domain = $hostname;

				// TODO: push down into API
				if ($certexists)
					$ssl->DeleteCertificate('sys-0-cert.pem');

				$ssl->Initialize($hostname, $domain, $name, $unit, $city, $region, $country);

				$syswatch = new Syswatch();
				$syswatch->ReconfigureSystem();
			}

			// Clear form variables
			$hostname = '';
			$domain = '';
			$name = '';
			$unit = '';
			$street = '';
			$city = '';
			$region = '';
			$country = '';
			$postalcode = '';
		}

	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayOrganization($hostname, $name, $unit, $street, $city, $region, $country, $postalcode);
WebFooter();

// vi: syntax=php ts=4
?>
