<?php

// vim: syntax=php
///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004 Point Clark Networks.
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

require_once(dirname(__FILE__) . '/../common/Globals.inc.php');
require_once(COMMON_CORE_DIR . '/api/WebServices.class.php');
require_once(COMMON_CORE_DIR . '/api/SoftwareUpdate.class.php');
require_once(COMMON_CORE_DIR . '/api/Software.class.php');

///////////////////////////////////////////////////////////////////////////////
//
// WebCheckSubscription()
//
///////////////////////////////////////////////////////////////////////////////

function WebCheckSubscription($package, $service, $nocache)
{
	$subscription = array();

	// Bail if the subscription status fails
	//--------------------------------------
	
	try {
		$webservice = new WebServices($service);
		$subscription = $webservice->GetSubscriptionStatus($nocache);
	} catch (Exception $e) {
		// TODO: this is a little messy, but it was too close to the 4.0 release date.
		// If a user clicks on "check latest subscription info" and is not 
		// connected to the Internet, we want to return the cached information.

		$warning =  $e->GetMessage();

		if ($nocache == true) {
			try {
				$subscription = $webservice->GetSubscriptionStatus(false);
			} catch (Exception $e) {
				// do nothing
			}
		}
		
		$subscription['error'] = $warning;

		return $subscription;
	}

	// Send helpful message if subscribed to ASP services
	//---------------------------------------------------

	if ($subscription["policy"] == WebServices::CONSTANT_ASP) {
		WebDialogSubscription($subscription);
		WebFooter();
		exit;
	}

	return $subscription;
}


///////////////////////////////////////////////////////////////////////////////
//
// WebDialogSubscription()
//
///////////////////////////////////////////////////////////////////////////////

function WebDialogSubscription($subscription, $width = "100%")
{
	$output = "";

	// Always show service description (if available)
	//-----------------------------------------------

	if (! empty($subscription['message'])) {
		$output .= "
		  <tr>
			<td nowrap class='mytableheader'>" . WEBSERVICES_LANG_LICENSE_DESCRIPTION . " &nbsp; </td>
			<td>" . $subscription['message'] . "</td>
		  </tr>
		";
	}

	// Always show subscription status (if available)
	//-----------------------------------------------

	/*
	TODO: backend needs to be updated for thsi feature
	if (isset($subscription['state'])) {
		if ($subscription['state'])
			$state = "<span class='ok'>" . WEBSERVICES_LANG_SUBSCRIBED . "</span>";
		else
			$state = "<span class='alert'>" . WEBSERVICES_LANG_NO_SUBSCRIPTION . "</span>";
			
		$output .= "
		  <tr>
			<td nowrap class='mytableheader'>" . WEBSERVICES_LANG_SUBSCRIPTION_STATUS . "</td>
			<td>" . $state . "</td>
		  </tr>
		";
	}
	*/

	// Show extra information if subscribed
	//-------------------------------------

	if (isset($subscription['state']) && ($subscription['state'])) {

		if (isset($subscription['isenabled'])) {
			if ($subscription['isenabled'])
				$is_enabled = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
			else
				$is_enabled = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";

			$output .= "
			  <tr>
				<td nowrap class='mytableheader'>" . LOCALE_LANG_STATUS . "</td>
				<td>" . $is_enabled . "</td>
			  </tr>
			";
		}

		// TODO: localize
		// TODO: create a generic way to handle issue noted below
		//
		// The language used ("License Type") does not make sense for the remote
		// backup service, so it is given special handling.

		if (isset($subscription['title']) && preg_match("/remote backup/i", $subscription['title'])) {
			$output .= "
			  <tr>
				<td nowrap class='mytableheader'>Storage Capacity (GB)</td>
				<td>" . $subscription['license'] . "</td>
			  </tr>
			";
		} else if (! empty($subscription['license'])) {
			$license = ($subscription['license'] == WebServices::CONSTANT_UNLIMITED) ? LOCALE_LANG_UNLIMITED : $subscription['license'];

			$output .= "
			  <tr>
				<td nowrap class='mytableheader'>" . WEBSERVICES_LANG_LICENSE_TYPE . "</td>
				<td>" . $license . "</td>
			  </tr>
			";
		}

		/*
		TODO: backend needs update
		if (! empty($subscription['expiry'])) {
			if ($subscription['expiry'] != WebServices::CONSTANT_UNKNOWN) {
				$output .= "
				  <tr>
					<td nowrap class='mytableheader'>" . WEBSERVICES_LANG_EXPIRES . "</td>
					<td>" . strftime("%x", $subscription['expiry']) . "</td>
				  </tr>
				";
			}
		}
		*/
	}

	if (isset($subscription['error'])) {
		$width = "100%"; // override width
		$output .= "
		  <tr>
			<td nowrap class='mytableheader' width='150'>&#160; </td>
			<td>" . $subscription['error'] . "</td>
		  </tr>
		";
	}

    WebFormOpen();
    WebTableOpen(WEBSERVICES_LANG_LICENSE_INFORMATION, $width);
    echo "
	  $output
      <tr>
        <td class='mytableheader'>&nbsp; </td>
        <td>" . WebButton("NoCache", WEBSERVICES_LANG_GET_LATEST, WEBCONFIG_ICON_UPDATE) . "</td>
      </tr>
    ";
    WebTableClose($width);
    WebFormClose($width);
}

