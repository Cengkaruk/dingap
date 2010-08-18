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

require_once("../../api/Country.class.php");
require_once("../../api/Organization.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// DisplayOrganizationWarning()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayOrganizationWarning()
{
	WebDialogInfo(WEB_LANG_CERTFICATE_CHANGE_WARNING);
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayOrganization()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayOrganization($hostname, $name, $unit, $street, $city, $region, $country, $postalcode)
{
	global $organization;

	try {
		$countryobj = new Country();
		$countries = $countryobj->GetList();
		
		$name = empty($name) ? $organization->GetName() : $name;
		$unit = empty($unit) ? $organization->GetUnit() : $unit;
		$street = empty($street) ? $organization->GetStreet() : $street;
		$city = empty($city) ? $organization->GetCity() : $city;
		$region = empty($region) ? $organization->GetRegion() : $region;
		$country = empty($country) ? $organization->GetCountry() : $country;
		$postalcode = empty($postalcode) ? $organization->GetPostalCode() : $postalcode;
        $hostname = empty($hostname) ? $organization->GetInternetHostname() : $hostname;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Suggest a hostname if one is not set

	if (empty($hostname))
		$hostname_html = "<span id='hostname'> &nbsp " . WEBCONFIG_ICON_LOADING . " &nbsp</span>";
	else
		$hostname_html = "<input type='text' name='hostname' style='width: 200px' value='$hostname'>";

	// A bit unusual: countries can use the two-letter code or full name.

	if (empty($country))
		$country = "US";

	 $country_options = "";

	 foreach ($countries as $code => $countryname) {
		  if ($country == $code)
				$selected = "selected";
		  elseif ($country == $countryname)
				$selected = "selected";
		  else
				$selected = "";

		  $country_options .= "<option value='" . $code . "' $selected>$countryname - $code</option>\n";
	 }

	WebFormOpen();
	WebTableOpen(ORGANIZATION_LANG_ORGANIZATION);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='150'>" . ORGANIZATION_LANG_INTERNET_HOSTNAME . "</td>
			<td>$hostname_html " . WEB_LANG_HOSTNAME_EXAMPLE . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap width='150'>" . ORGANIZATION_LANG_ORGANIZATION . "</td>
			<td><input type='text' name='name' style='width: 200' value='$name'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ORGANIZATION_UNIT . "</td>
			<td><input type='text' name='unit' style='width: 200' value='$unit'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_STREET . "</td>
			<td><input type='text' name='street' style='width: 200' value='$street'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_CITY . "</td>
			<td><input type='text' name='city' style='width: 200' value='$city'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_REGION . "</td>
			<td><input type='text' name='region' style='width: 150px' value='$region'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_COUNTRY . "</td>
			<td><select name='country'>$country_options</select></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_POSTAL_CODE . "</td>
			<td><input type='text' name='postalcode' style='width: 150px' value='$postalcode'></td>
		</tr>
	";

	if (! WebIsSetup()) {
		echo "
			<tr>
				<td class='mytablesubheader'>&#160; </td>
				<td>" . WebButtonUpdate("UpdateOrganizationSettings") . "</td>
			</tr>
		";
	}

	WebTableClose();

	if (! WebIsSetup())
		WebFormClose();
}

// vi: syntax=php ts=4
?>
