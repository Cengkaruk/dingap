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
require_once("../../api/ClearSdnShoppingCart.class.php");
require_once("../../api/ClearSdnCartItem.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-cart.png', WEB_LANG_PAGE_INTRO, true);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$cart = new ClearSdnShoppingCart();
try {
	if (isset($_POST['DeleteItem']))
		DeleteItem();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplaySummary();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S 
/////////////////////////////////////////////////////////////////////////////// 

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $cart;

	try {
		$items = $cart->GetItems();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if (!empty($items)) {
		echo "<div id='sdn-checkout' title='" . WEB_LANG_CHECKOUT . "'>";
		echo "<div id='sdn-checkout-content'></div>";
		echo "</div>";
	}
	WebTableOpen(WEB_LANG_BASE_SUBSCRIPTION, "100%", "clearsdn-overview");
	echo "
		<tr id='clearsdn-splash'>
		<td align='center'><img src='/images/icon-os-to-sdn.png' alt=''>
		<div id='whirly' style='padding: 10 0 10 0'>" . WEBCONFIG_ICON_LOADING . "</div>
		</td>
		</tr>
	";
	WebTableClose("100%");

	WebFormOpen();
	WebTableOpen(WEB_LANG_CART_CONTENTS, "100%");
	echo "<tr class='mytableheader'>";
	echo "<td>" . WEB_LANG_DESCRIPTION . "</td>";
	echo "<td align='right'>" . WEB_LANG_UNIT_PRICE . "</td>";
	echo "<td style='padding-left: 20px'>" . WEB_LANG_UNIT . "</td>";
	echo "<td align='right' width='15%'>" . WEB_LANG_DISCOUNT . "</td>";
	echo "<td width='15%'>&nbsp;</td>";
	echo "</tr>";
	$rowcount = 0;
	foreach ($items as $item) {
		$rowclass = "rowenabled" . ($rowcount % 2 ? 'alt' : '');

		echo "
			<tr class='$rowclass'>
				<td>" . $item->GetDescription() . "</td>
				<td nowrap align='right'>" . $item->GetCurrency() . ' ' . money_format('%i', $item->GetUnitPrice()) . "</td>
				<td style='padding-left: 20px'>" . $item->GetUnit() . "</td>
				<td align='right'>" . $item->GetDiscount() . "%</td>
				<td nowrap align='right'>" . 
					WebButtonDelete("DeleteItem[" . $item->GetId() . "]") . "
				</td>
			</tr>
		";
		$rowcount++;
	}

	if (empty($items))
		echo "<tr><td colspan='5' align='center'>" . WEB_LANG_NO_ITEMS_IN_CART . "</td></tr>";
	WebTableClose("100%");
	if (!empty($items)) {
		echo "<div style='padding: 20 0 20 0; text-align: center;'>";
		echo WebButton('checkout', WEB_LANG_CHECKOUT, WEBCONFIG_ICON_CHECKOUT, array('type' => 'button', 'onclick' => 'checkoutNow()'));
		echo "</div>";
	}
	WebFormClose();

	// We need to bring this inside (rather than .js.php helper) becuase of the POST use
	echo "
          <script language='javascript'>
	  $(document).ready(function() {
	    $.ajax({
	      type: 'POST',
	      url: 'clearsdn-ajax.php',
	      data: 'action=getBaseSubscription" . (isset($_POST['DeleteItem']) ? "&usecache=1" : "") . "',
	      success: function(html) {
		$('#clearsdn-splash').remove();
		$('#clearsdn-overview').append(html);
	      },
	      error: function(xhr, text, err) {
		$('#whirly').html(xhr.responseText.toString());
	      }
	    });
	  });
          </script>
	";
}

///////////////////////////////////////////////////////////////////////////////
//
// DeleteItem()
//
///////////////////////////////////////////////////////////////////////////////

function DeleteItem()
{
	global $cart;
	try {
		$cart->DeleteItem(key($_POST['DeleteItem']));
	} catch (Exception $e) {
		throw new EngineException($e->GetMessage());
	}
}

// vi: syntax=php ts=4
?>
