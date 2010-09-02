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

require_once("../../gui/Webconfig.inc.php");
require_once("../../gui/Charts.inc.php");
require_once("../../api/ClearSdnService.class.php");
require_once("../../api/ClearSdnStore.class.php");
require_once(GlobalGetLanguageTemplate(COMMON_CORE_DIR . '/api/Locale.class.php'));
require_once(GlobalGetLanguageTemplate(COMMON_CORE_DIR . '/api/ClearSdnSoapRequest.class.php'));
require_once(GlobalGetLanguageTemplate(__FILE__));

WebAuthenticate();

$sdn = new ClearSdnService();
$store = new ClearSdnStore();

if (isset($_POST['action']) && $_POST['action'] == 'getBaseSubscription')
	GetBaseSubscription();
else if (isset($_POST['action']) && $_POST['action'] == 'getServiceDetails')
	GetServiceDetails();
else if (isset($_POST['action']) && $_POST['action'] == 'buy')
	GetPurchase();
else if (isset($_POST['action']) && $_POST['action'] == 'uploadCart')
	UploadCart();
else if (isset($_POST['action']) && $_POST['action'] == 'getServiceSummary')
	GetServiceSummary();
else if (isset($_POST['action']) && $_POST['action'] == 'getDynamicDnsSettings')
	GetDynamicDnsSettings();
else
	WebDialogWarning(WEB_LANG_INVALID_ACTION);

function GetBaseSubscription()
{
	global $sdn;
	try {
		$details = $sdn->GetBaseSubscription((isset($_POST['usecache']) ? false : true)); 
	} catch (ClearSdnFailedToConnectException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning(CLEARSDN_SOAP_REQUEST_LANG_SOAP_CONNECTION_FAILED);
		return;
	} catch (ClearSdnAuthenticationException $e) {
		unset($_SESSION['clearsdn_cookie']['authenticated']);
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning($e->GetMessage());
		return;
	} catch (Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning(CLEARSDN_SOAP_REQUEST_LANG_SOAP_REQUEST_FAILED . "  " . $e->GetMessage());
		return;
	}
	echo "<tr><td width='35%' class='mytablesubheader'>" . CLEARSDN_SERVICE_LANG_BASE_DEVICE . "</td>";
	echo "<td width='65%'>";
	echo "
	  <script type='text/javascript' language='JavaScript'>
	  $('#sdn-checkout-content').html('" . addslashes(WEB_LANG_CONFIRM_CHECKOUT) . addslashes("<div style='margin: 10 0 0 0'><table border='0' width='90%' align='center' style='padding:45 0 45 0; background-image: url(/images/icon-clearsdn-logo.png); background-repeat: no-repeat; background-position: 0 0;'><tr><td align='right' width='60%'>" . CLEARSDN_SERVICE_LANG_USERNAME . ":</td><td width='40%'>" . $details['account'] . "</td></tr>" . (!isset($_SESSION['clearsdn_cookie']['authenticated']) ? "<tr><td align='right'>" . CLEARSDN_SERVICE_LANG_PASSWORD . ":</td><td><input type='password' name='password' id='password' value=''></td></tr>" : "") . "</table></div>") . "');
	  </script>
	";
	echo $details['device'] . "</td></tr>";
	echo "<tr><td class='mytablesubheader'>" . LOCALE_LANG_DESCRIPTION . "</td><td>" . $details['license'] . "</td></tr>";
	if (isset($details['serial']))
		echo "<tr><td class='mytablesubheader'>" . CLEARSDN_SERVICE_LANG_BASE_SUBSCRIPTION_SERIAL_NUMBER . "</td><td>" . $details['serial'] . "</td></tr>";
	if (isset($details['expire']))
		echo "<tr><td class='mytablesubheader'>" . CLEARSDN_SERVICE_LANG_BASE_SUBSCRIPTION_EXPIRY . "</td><td>" . date("F j, Y", strtotime($details['expire'])) . "</td></tr>";
	if (isset($details['fullTerm'])) {
		$term = "";
		$start = date('M j, Y', time());
		$end = time();
		if ($details['unit'] == ClearSdnService::TERM_MONTHLY && $details['fullTerm'] == 1) {
			$end = strtotime("+1 month");
			$term = $details['fullTerm'] . " " . WEB_LANG_MONTH . " (" . $start . " - " . date('M j, Y', $end) . ")";
		} else if ($details['unit'] == ClearSdnService::TERM_MONTHLY) {
			$end = strtotime("+" . $details['fullTerm'] . " months");
			$term = $details['fullTerm'] . " " . WEB_LANG_MONTHS  . " (" . $start . " - " . date('M j, Y', $end) . ")";
		} else if ($details['unit'] == ClearSdnService::TERM_ANNUAL && $details['fullTerm'] == 1) {
			$end = strtotime("+1 year");
			$term = $details['fullTerm'] . " " . WEB_LANG_YEAR . " (" . $start . " - " . date('M j, Y', $end) . ")";
		} else if ($details['unit'] == ClearSdnService::TERM_ANNUAL) {
			$end = strtotime("+" . $details['fullTerm'] . " years");
			$term = $details['fullTerm'] . " " . WEB_LANG_YEARS . " (" . $start . " - " . date('M j, Y', $end) . ")";
		} else if ($details['unit'] == ClearSdnService::TERM_1_YEAR_FIXED) {
			$end = strtotime("+" . $details['fullTerm'] . " years");
			$term = $details['fullTerm'] . " x " . WEB_LANG_1_YEAR_TERM . " (" . $start . " - " . date('M j, Y', $end) . ")";
		} else if ($details['unit'] == ClearSdnService::TERM_2_YEAR_FIXED) {
			$end = strtotime("+" . ($details['fullTerm'] * 2) . " years");
			$term = $details['fullTerm'] . " x " . WEB_LANG_2_YEAR_TERM . " (" . $start . " - " . date('M j, Y', $end) . ")";
		} else if ($details['unit'] == ClearSdnService::TERM_3_YEAR_FIXED) {
			$end = strtotime("+" . ($details['fullTerm'] * 3) . " years");
			$term = $details['fullTerm'] . " x " . WEB_LANG_3_YEAR_TERM . " (" . $start . " - " . date('M j, Y', $end) . ")";
		}
		echo "<tr><td class='mytablesubheader'>" . WEB_LANG_FULL_TERM . "</td><td>$term</td></tr>";
		if (isset($details['proRatedDiscount']) && isset($details['expire'])) {
			$end += 24 * 60 * 60;
			echo "<tr><td class='mytablesubheader'>" . WEB_LANG_PRO_RATED_DISCOUNT . "</td><td>" . $details['proRatedDiscount'] . "% (" . WEB_LANG_PRORATED_PERIOD . " " . date('M j, Y', $end) . " - " . date("M j, Y", strtotime($details['expire'])) . ")</td></tr>";
		}
	} else {
		if (isset($details['proRatedDiscount']) && isset($details['expire'])) {
			echo "<tr><td class='mytablesubheader'>" . WEB_LANG_PRO_RATED_DISCOUNT . "</td><td>" . $details['proRatedDiscount'] . "% (" . WEB_LANG_PRORATED_PERIOD . " " . date('M j, Y', time()) . " - " . date("M j, Y", strtotime($details['expire'])) . ")</td></tr>";
		}
	}
}

function GetServiceDetails()
{
	global $sdn;
	try {
		$details = $sdn->GetServiceDetails((isset($_POST['usecache']) ? false : true), $_POST['service']); 
	} catch (ClearSdnFailedToConnectException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'>" . CLEARSDN_SOAP_REQUEST_LANG_SOAP_CONNECTION_FAILED . "</td></tr>";
		return;
	} catch (ClearSdnDeviceNotRegisteredException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'><a href='register.php'>" . $e->GetMessage() . "</a></td></tr>";
		return;
	} catch (Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'>" . CLEARSDN_SOAP_REQUEST_LANG_SOAP_REQUEST_FAILED . "  " . $e->GetMessage() . "</td></tr>";
		return;
	}

	$logs = array();
	if (isset($details['subscription_info']['current_subscription']['logs'])) {
		$logs = $details['subscription_info']['current_subscription']['logs'];
		$counter = 0;
		foreach ($logs as $entry) {
			$rowclass = 'rowenabled' . (($counter % 2) ? 'alt' : '');
			$log .= "<tr class='$rowclass'><td width='2%'>" . $sdn->GetLogIcon($entry[0]) . "</td><td width='80%'>" . $entry[1] . "</td><td width='18%' align='right'>" . date("F j, Y H:i", strtotime($entry[2])) . "</td></tr>";
			$counter++;
		}
		
	}
	echo "<tr>";
	echo "<td class='mytablesubheader' width='40%'>" . ($_POST['service'] == ClearSdnService::SDN_BASE ? WEB_LANG_SUBSCRIPTIONS : WEB_LANG_SERVICE_NAME) . "</td>";
	echo "<td colspan='2'>" . $details['subscription_info']['name'];
	echo "
	  <script type='text/javascript' language='JavaScript'> " .
            (count($logs) > 0 ? "
            $('#clearsdn-nodata').remove();
            $('#clearsdn-logs').append('" . addslashes($log) . "');
            " : "") . " 
	    function confirmPurchase(method) {
	      if (method == 1) {
	        $('#sdn-confirm-purchase-content').html('" . WEB_LANG_PURCHASE_CREDIT_CARD . "<div style=\'margin: 15 0 15 0\'><table border=\'0\' width=\'90%\' height=\'120px\' align=\'center\' style=\'padding:0 0 0 0; background-image: url(/images/icon-clearsdn-logo.png); background-repeat: no-repeat; background-position: 0 0;\'><tr><td align=\'right\' width=\'50%\'>" . CLEARSDN_SERVICE_LANG_USERNAME . ":</td><td width=\'50%\'>" . $details['account'] . "</td></tr>" . (!isset($_SESSION['clearsdn_cookie']['authenticated']) ? "<tr><td align=\'right\'>" . CLEARSDN_SERVICE_LANG_PASSWORD . ":</td><td><input type=\'password\' name=\'password\' id=\'password\' value=\'\'></td></tr>" : "") . "<tr><td align=\'right\'>" . WEB_LANG_CREDIT_CARD . ":</td><td><input type=\'hidden\' name=\'method\' id=\'method\' value=\'' + method + '\'>" . $details['payment']['preauth_card'] . "</td></tr><tr style=\'height:60px\'><td colspan=\'2\'>&nbsp;</td></tr></table></div>');
	      } else {
	        $('#sdn-confirm-purchase-content').html('" . WEB_LANG_PURCHASE_PO . "<div style=\'margin: 15 0 15 0\'><table border=\'0\' width=\'90%\' height=\'120px\' align=\'center\' style=\'padding:0 0 0 0; background-image: url(/images/icon-clearsdn-logo.png); background-repeat: no-repeat; background-position: 0 0;\'><tr><td align=\'right\' width=\'50%\'>" . CLEARSDN_SERVICE_LANG_USERNAME . ":</td><td width=\'50%\'>" . $details['account'] . "</td></tr>" . (!isset($_SESSION['clearsdn_cookie']['authenticated']) ? "<tr><td align=\'right\'>" . CLEARSDN_SERVICE_LANG_PASSWORD . ":</td><td><input type=\'password\' name=\'password\' id=\'password\' value=\'\'></td></tr>" : "") . "<tr><td align=\'right\'>" . WEB_LANG_PURCHASE_ORDER . ":</td><td><input type=\'hidden\' name=\'method\' id=\'method\' value=\'' + method + '\'><input type=\'text\' name=\'po\' id=\'po\' value=\'\'></td></tr><tr style=\'height:60px\'><td colspan=\'2\'>&nbsp;</td></tr></table></div>');
	      }
	      $('#sdn-confirm-purchase').dialog('open');
	    }
	  </script>
	";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader' valign='top'>" . WEB_LANG_DESCRIPTION . "</td>";
	echo "<td colspan='2'>" . $details['subscription_info']['description'] . "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader' valign='top'>" . WEB_LANG_ROI . "</td>";
	echo "<td colspan='2'>" . $details['subscription_info']['roi'] . "</td>";
	echo "</tr>";
	// Is there a current subscription?
	if (isset($details['subscription_info']['current_subscription'])) {
		$current = $details['subscription_info']['current_subscription'];
		echo "<tr>";
		echo "<td class='mytablesubheader'>" . CLEARSDN_SERVICE_LANG_BASE_SUBSCRIPTION . "</td>";
		echo "<td>" . $current['base_name'] . "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='mytablesubheader'>" . CLEARSDN_SERVICE_LANG_BASE_SUBSCRIPTION_SERIAL_NUMBER . "</td>";
		echo "<td>" . $current['serial_number'] . "</td>";
		echo "</tr>";
		if (isset($current['product_name'])) {
			echo "<tr>";
			echo "<td class='mytablesubheader'>" . WEB_LANG_DETAILS . "</td>";
			echo "<td>" . $current['product_name'] . "</td>";
			echo "</tr>";
		}
		echo "<tr>";
		echo "<td class='mytablesubheader'>" . LOCALE_LANG_STATUS . "</td>";
		echo "<td>" . (isset($current['evaluation']) ? WEB_LANG_EVALUATION : WEB_LANG_ACTIVE) . "</td>";
		echo "</tr>";
		if (isset($current['expire'])) {
			echo "<tr>";
			echo "<td class='mytablesubheader'>" . CLEARSDN_SERVICE_LANG_BASE_SUBSCRIPTION_EXPIRY . "</td>";
			echo "<td>" . date("F j, Y", strtotime($current['expire'])) . "</td>";
			echo "</tr>";
		}
		echo "<tr>";
		echo "<td class='mytablesubheader'>&nbsp;</td>";
		echo "<td>";
		echo "<input type='hidden' name='licenseid' value='" . $current['id'] . "'>";
		if (isset($current['evaluation']))
			echo WebButton('converteval', WEB_LANG_BUY_FULL_SUBSCRIPTION, WEBCONFIG_ICON_BUY);
		else
			echo WebButton('renew', LOCALE_LANG_RENEW, WEBCONFIG_ICON_BUY);
		echo "</tr>";
	} else {
		$isfirst = true;
		$options = $details['subscription_info']['subscription_options'];
		ksort($options);
		foreach ($options as $option) {
			echo "<tr>";
			echo "<td class='mytablesubheader'>" . ($isfirst ? WEB_LANG_CURRENT_SUBSCRIPTION : "&nbsp;") . "</td>";
			echo "<td width='3%' valign='top' style='padding: 0 3 3 3;'>";
			echo "<input type='radio' name='pid' value='" . $option['pid'] . "'" . (isset($details['current_subscription']['pid']) ? ($details['current_subscription']['pid'] == $option['pid'] ? " CHECKED" : "") : ($isfirst ? " CHECKED" : "")). ">";
			echo "<input type='hidden' name='description-" . $option['pid'] . "' value='" . $option['product'] . (isset($option['description']) ? ", " . (preg_match("/.*\d+\s*GB.*/", $option['description']) ? $option['description'] : strtolower($option['description'])) : "") . "'>";
			echo "<input type='hidden' name='unitprice-" . $option['pid'] . "' value='" . $option['unit_price'] . "'>";
			echo "<input type='hidden' name='unit-" . $option['pid'] . "' value='" . $option['unit'] . "'>";
			echo "<input type='hidden' name='discount-" . $option['pid'] . "' value='" . $option['discount'] . "'>";
			echo "<input type='hidden' name='currency-" . $option['pid'] . "' value='" . $option['currency'] . "'>";
			// Use cached data on submittal
			if ($isfirst) {
				echo "<input type='hidden' name='usecache' value='1'>";
				// Sneak in removal _blank target
				echo "
				  <script type='text/javascript' language='JavaScript'>
				    $('#clearsdnform').removeAttr('target');
				  </script>
				";
			}
			echo "</td>";
			echo "<td width='58%'>" . $option['product'];
			if ($option['description'] != null)
				echo "<div><b>" . WEB_LANG_DESCRIPTION . ":</b>&nbsp;&nbsp;" . $option['description'] . "</div>";
			if ((float)$option['unit_price'] > 0 && isset($details['payment']))
				echo "<div><b>" . WEB_LANG_SUBSCRIPTION_PRICE . ":</b>&nbsp;&nbsp;" . $details['payment']['currency'] . " " .
					money_format('%i', (float)$option['unit_price']) . " " . $option['unit'] . ($option['discount'] > 0 ? " (" . WEB_LANG_DISCOUNT . " " .
					$option['discount'] . "%)" : "") . "</div>";
			else if ((float)$option['unit_price'] == 0 && isset($details['payment']) && $option['pid'] > 0)
				echo "<div><b>" . WEB_LANG_SUBSCRIPTION_PRICE . ":</b>&nbsp;&nbsp;" . WEB_LANG_FREE . "</div>";
			echo "</td>";
			echo "</tr>";
			$isfirst = false;
		}
		if (isset($details['payment']) && !$current_sub) {
			echo "<tr>";
			echo "<td class='mytablesubheader'>&nbsp;</td>";
			echo "<td colspan='2'>" . WebButtonAddToCart('addtocart');
			if ($details['payment']['preauth'])
				echo WebButton('via-cc', WEB_LANG_CREDIT_CARD . " " . $details['payment']['preauth_card'], WEBCONFIG_ICON_BUY, array('type' => 'button', 'onclick' => 'confirmPurchase(' . ClearSdnStore::CREDIT_CARD . ')'));
			if ($details['payment']['po'])
				echo WebButton('via-po', WEB_LANG_PURCHASE_ORDER, WEBCONFIG_ICON_BUY, array('type' => 'button', 'onclick' => 'confirmPurchase(' . ClearSdnStore::PURCHASE_ORDER . ')'));
			echo "</td>";
			echo "</tr>";
		}
	}
}

function UploadCart()
{
	global $store;
	try {
		$cart = new ClearSdnShoppingCart();
		$url = $store->UploadCart((isset($_POST['password']) ? $_POST['password'] : null));
		$cart->Clear();
		echo $url;
	} catch (ClearSdnFailedToConnectException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning(CLEARSDN_SOAP_REQUEST_LANG_SOAP_CONNECTION_FAILED);
		return;
	} catch (ClearSdnAuthenticationException $e) {
		unset($_SESSION['clearsdn_cookie']['authenticated']);
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning($e->GetMessage());
		return;
	} catch (EngineException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning(CLEARSDN_SOAP_REQUEST_LANG_SOAP_REQUEST_FAILED . "  " . $e->GetMessage());
		return;
	}
}

function GetPurchase()
{
	global $store;
	try {
		// If password not set, authentication has already occurred.
		$store->DoPurchase((isset($_POST['password']) ? $_POST['password'] : null), $_POST['method'], $_POST['pid'], $_POST['po']);
	} catch (ClearSdnFailedToConnectException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning(CLEARSDN_SOAP_REQUEST_LANG_SOAP_CONNECTION_FAILED);
		return;
	} catch (ClearSdnAuthenticationException $e) {
		unset($_SESSION['clearsdn_cookie']['authenticated']);
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning($e->GetMessage());
		return;
	} catch (EngineException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		WebDialogWarning(CLEARSDN_SOAP_REQUEST_LANG_SOAP_REQUEST_FAILED . "  " . $e->GetMessage());
		return;
	}
}
function GetServiceSummary()
{
	try {
		$sdn = new ClearSdnService();
		$services = $sdn->GetAllServiceInfo(); 
		ksort($services);
	} catch (ClearSdnFailedToConnectException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'>" . CLEARSDN_SOAP_REQUEST_LANG_SOAP_CONNECTION_FAILED . "</td></tr>";
		return;
	} catch (ClearSdnDeviceNotRegisteredException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<a href='register.php'>" . $e->GetMessage() . "</a>";
		return;
	} catch (Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'>" . CLEARSDN_SOAP_REQUEST_LANG_SOAP_REQUEST_FAILED . "  " . $e->GetMessage() . "</td></tr>";
		return;
	}

	foreach ($services as $id => $service) {
		echo "<tr>";
		echo "<td width='18' valign='top'>";
		if ($service['status'] == 1) {
			echo "<img src='/templates/base/images/icons/16x16/icon-clearsdn-info.png' style='padding: 0 1 0 1'>";
		} else if ($service['status'] == 2) {
			echo "<img src='/templates/base/images/icons/16x16/icon-clearsdn-warning.png' style='padding: 0 1 0 1'>";
		} else {
			echo "<img src='/templates/base/images/icons/16x16/icon-clearsdn-ok.png' style='padding: 0 1 0 1'>";
		}
		echo "</td>";
		echo "<td>";
		echo $service['brief'];
		echo "&nbsp;&nbsp;<a href='#' id='serviceid-$id-href' style='color:#E08128'>" . WEB_LANG_LEARN_MORE . " <img src='/templates/base/images/icons/16x16/icon-arrowdown.png' alt=''></a>";
		echo "<div id='serviceid-$id' style='padding-top:10px;'>";
		echo "<div style='padding-bottom:5px;'><b>" . WEB_LANG_SUBSCRIPTION_PRICING . ":</b>  " . $service['cost'] . "</div>" ;
		echo "<div>";
		if (isset($service['graph'])) {
			echo "<div style='margin: 5; padding: 0; float: right;'>";
			if ($service['graph']['type'] == 'horizontalbar') 
				echo WebSmallChartHorizontalBar($service['graph']);
			else if ($service['graph']['type'] == 'pie') 
				echo WebSmallChartPie($service['graph']);
			echo "</div>";
		}
		echo "<div><b>" . WEB_LANG_ROI . ":</b>  " . $service['roi'] . "</div>";
		echo "</div>";
		echo "<script type='text/javascript'>
		    $('a#serviceid-$id-href').click(function(e){
			  e.preventDefault();
			  $('#serviceid-$id').slideToggle(200);
		  if ($('#serviceid-$id-href').html().match(/" . WEB_LANG_LEARN_MORE . ".*/gi))
		    $('#serviceid-$id-href').html('" . WEB_LANG_HIDE . " <img src=\'/templates/base/images/icons/16x16/icon-arrowup.png\' alt=\'\'>');
		  else
		    $('#serviceid-$id-href').html('" . WEB_LANG_LEARN_MORE . " <img src=\'/templates/base/images/icons/16x16/icon-arrowdown.png\' alt=\'\'>');
		    });
		    $('#serviceid-$id').hide();
			</script>
		";
		echo "<form action='" . $service['buy_url'] . "' method='post'><div style='text-align: center; padding: 5 0 5 0;'>" . ($service['status'] == 0 ? WebButtonView() : WebButtonSubscribe()) . "</div></form>";
		echo "</td>";
		echo "</tr>";
	}
}
function GetDynamicDnsSettings()
{
	try {
		$sdn = new ClearSdnService();
		$data = $sdn->GetDynamicDnsSettings(); 
		
		$logs = array();
		if (isset($data['logs'])) {
			$logs = $data['logs'];
			$counter = 0;
			foreach ($logs as $entry) {
				$rowclass = 'rowenabled' . (($counter % 2) ? 'alt' : '');
				$log .= "<tr class='$rowclass'><td width='2%'>" . $sdn->GetLogIcon($entry[0]) . "</td><td width='35%'>" . $entry[1] . "</td>" .
					"<td width='30%'>" . $entry[2] . "</td><td width='15%'>" . $entry[3] . "</td>" .
					"<td width='18%' align='right'>" . date("F j, Y H:i", strtotime($entry[4])) . "</td></tr>";
				$counter++;
			}
		}
		echo "<tr>";
        echo "<td class='mytablesubheader' width='40%'>" . WEB_LANG_STATUS . "</td>";
        echo "<td width='60%'>" . WebDropDownEnabledDisabled("enabled", $data['status'], 0, 'toggleControls()', 'enabled') . "</td>";
        echo "</tr>";
		echo "<tr>";
        echo "<td class='mytablesubheader'>" . WEB_LANG_SUBDOMAIN . "</td>";
        echo "<td><input type='text' name='subdomain' id='subdomain' value='" . $data['subdomain'] . "'></td>";
        echo "</tr>";
		echo "<tr>";
        echo "<td class='mytablesubheader'>" . WEB_LANG_DOMAIN . "</td>";
        echo "<td>" . WebDropDownHash("domain", $data['domain'], $data['domainOptions'], 0, null, 'domain') . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='mytablesubheader' id='ipField'>" . WEB_LANG_IP . "</td>";
        echo "<td><input type='text' name='ip' id='ip' value='" . $data['ip'] . "' style='width: 100px' /></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='mytablesubheader'>&nbsp;</td>";
		echo "<td>" . WebButtonUpdate("update");
		echo "
		  <script type='text/javascript' language='JavaScript'>toggleControls();\n" .
            (count($data['logs']) > 0 ? " $('#clearsdn-nodata').remove(); $('#clearsdn-logs').append('" . addslashes($log) . "'); " : "") . " 
          </script>
		";
		echo "</td>";
        echo "</tr>";


	} catch (ClearSdnFailedToConnectException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'>" . CLEARSDN_SOAP_REQUEST_LANG_SOAP_CONNECTION_FAILED . "</td></tr>";
		return;
	} catch (ClearSdnDeviceNotRegisteredException $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<a href='register.php'>" . $e->GetMessage() . "</a>";
		return;
	} catch (Exception $e) {
		header('HTTP/1.1 500 Internal Server Error');
		echo "<tr><td align='center'>" . CLEARSDN_SOAP_REQUEST_LANG_SOAP_REQUEST_FAILED . "  " . $e->GetMessage() . "</td></tr>";
		return;
	}
}


// vim: syntax=php ts=4
?>
