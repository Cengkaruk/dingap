<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2004-2009 Point Clark Networks.
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

require_once("../../api/WebServices.class.php");
require_once("../../api/SoftwareUpdate.class.php");
require_once("../../api/Software.class.php");
require_once("../../api/Register.class.php");
require_once("../../api/Syswatch.class.php");

define('SUBSCRIPTON_CACHE_TIME', 20);

///////////////////////////////////////////////////////////////////////////////
//
// WebServiceStatus()
//
///////////////////////////////////////////////////////////////////////////////

function WebServiceStatus($service, $description)
{
	if (empty($_SESSION['system_registered']))
		return;

	$url = $_SESSION['system_sdn_redirect'] . "/" . $service . "/" . $_SESSION['system_hostkey'];

	echo "
		<script type='text/javascript' language='JavaScript'>
            var servicetitle = document.getElementById('clearos-service-title');
			servicetitle.innerHTML = '<a target=\"_blank\" href=\"$url\">" . $description . " - </a> ';

            var servicestate = document.getElementById('clearos-service-state');
			servicestate.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';

			getServiceStatus();
		</script>
	";
}

///////////////////////////////////////////////////////////////////////////////
//
// WebDialogServiceStatus()
//
///////////////////////////////////////////////////////////////////////////////

function WebDialogServiceStatus($service, $description)
{
	// Show link to registration if not registered
	//--------------------------------------------

	if (empty($_SESSION['system_registered'])) {
		$register = new Register(); // Locale tags
		WebFormOpen();
		WebTableOpen($description, '100%');
		echo "
			<tr>
				<td align='center'>" . REGISTER_LANG_ERRMSG_REGISTER . " &nbsp; " . WebUrlJump("register.php", REGISTER_LANG_REGISTER) . "</td>
			</tr>
		";
		WebTableClose('100%');
		WebFormClose();
		return;
	}

	// Get subscription information
	//-----------------------------

	try {
		// Grab cached data if available
		$webservice = new WebServices($service);
		$subscription = $webservice->GetSubscriptionStatus(false);
	} catch (WebServicesNoCacheException $e) {
		// Otherwise, let the web service handle it
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$doing_update = "";
	$run_update = false;

	// No cached subscription info -- fire up Ajax to get the latest.
	if (! isset($subscription['isenabled'])) {
		$is_enabled = "...";
		$status_message = "...";
		$doing_update = WEBCONFIG_ICON_LOADING;
		$run_update = true;

	// Format the cached subscription info
	} else {
		if ($subscription['isenabled'])
			$is_enabled = "<span class='ok'>" . LOCALE_LANG_ENABLED . "</span>";
		else
			$is_enabled = "<span class='alert'>" . LOCALE_LANG_DISABLED . "</span>";

// FIXME: locale
		if ($subscription['updated'] > 0) {
			$timediff = time() - $subscription['updated'];
			if ($timediff > 86400) {
				$lastupdated = strftime("%B %e %Y - %T %Z", $subscription['updated']);
				$status_message = "Signatures last updated -- " . $lastupdated;
			} else {
				$lastupdated = strftime("%T %Z", $subscription['updated']);
				$status_message = "Signatures last updated -- " . $lastupdated;
			}
		} else {
			$status_message = "...";
		}

		// If the cache is old, automatically fire up Ajax.
		$last_update = (time() - $subscription['cached']);
		if ($last_update > SUBSCRIPTON_CACHE_TIME) {
			$doing_update = WEBCONFIG_ICON_LOADING;
			$run_update = true;
		}
	}
		
	$button_options['onclick'] = 'getServiceStatus()';
	$button_options['type'] = 'button';

// FIXME-- locale tags

WebFormOpen();
echo "
<div class='ui-widget'>
	<div class='ui-state-highlight ui-corner-all' style='margin-top: 12px'>
		<table width='100%' border='0' cellpadding='0' cellspacing='0'>
			<tr>
				<td>
					<div class='clearos-breadcrumb'>
						ClearSDN <img src='/templates/standard-5.1/images/breadcrumb-arrow.png' alt='-'> 
						<span class='clearos-breadcrumb-highlight'>" . $description . "</span>
					</div>
				</td>
				<td width='250'>
					<div class='clearos-global-alert' align='right'>$alert</div>
				</td>
			</tr>
		</table>
		<div class='clearos-summarytable'>
			<table width='100%' border='0' cellpadding='0' cellspacing='10'>
				<tr>
					<td width='60'>
						<div class='clearos-summary'> 
							<img src='/images/clearsdn.png' alt='ClearSDN' hspace='8' align='left' />
						</div>
					</td>
					<td>
						Subscription: <span id='state'>" . $is_enabled . "</span><br>
						Status message: <span id='status_message'>" . $status_message . "</span><span id='error'> </span>
<br>
&nbsp; <span id='servicestatus'>$doing_update</span>
					</td>
					<td width='250' align='right' nowrap>
						<div class='clearos-summaryinforight'>
							<table align='center'>
								<tr>
									<td><span class='ui-state-default ui-corner-all ui-icon ui-icon-info'>-</span></td>
									<td><a target='_blank' href='" . $page['user_guide_url'] . "'>ClearSDN Help</a></td>
									<td>&nbsp;</td>
<td>" . 
				WebButton("GetServiceStatus", "Refresh", WEBCONFIG_ICON_CONTINUE, $button_options) . "
<td>
								</tr>
							</table>
						</div>	
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
";
WebFormClose();

	if ($run_update)
		echo "<script type='text/javascript' language='JavaScript'>getServiceStatus()</script>";

}

// vim: syntax=php
?>
