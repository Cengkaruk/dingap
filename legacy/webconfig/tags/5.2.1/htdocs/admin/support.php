<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 Point Clark Networks.
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
require_once("../../api/Product.class.php");
require_once("../../api/ClearSdnService.class.php");
require_once("../../api/ClearSdnStore.class.php");
require_once("../../api/ClearSdnShoppingCart.class.php");
require_once("../../api/ClearSdnCartItem.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['converteval'])) {
	$product = new Product();
	WebForwardPage($product->GetPortalUrl() . "/license_info.jsp?licenseid=" . $_POST['licenseid']);
} else if (isset($_POST['renew'])) {
	$product = new Product();
	WebForwardPage($product->GetPortalUrl() . "/checkout5.jsp?licenseid=" . $_POST['licenseid']);
}

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $style);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-support.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['addtocart'])) {
	try {
		$item = new ClearSdnCartItem(ClearSdnService::SDN_SUPPORT);
		$item->SetPid($_POST['pid']);
		$item->SetDescription($_POST['description-' . $_POST['pid']]);
		$item->SetUnitPrice($_POST['unitprice-' . $_POST['pid']]);
		$item->SetUnit($_POST['unit-' . $_POST['pid']]);
		$item->SetDiscount($_POST['discount-' . $_POST['pid']]);
		$item->SetCurrency($_POST['currency-' . $_POST['pid']]);
		$item->SetClass(ClearSdnCartItem::CLASS_SERVICE);
		$item->SetGroup("notused");
		$cart = new ClearSdnShoppingCart();
		$cart->AddItem($item);
		WebFormOpen("cart.php");
		WebDialogInfo(CLEARSDN_SHOPPING_CART_LANG_ITEM_ADDED_TO_CART . "&nbsp;&nbsp;" . WebButtonViewCart());
		WebFormClose();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayDetails();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDetails()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDetails()
{
	$sdn = new ClearSdnService();
	$store = new ClearSdnStore();
	echo "<div id='sdn-confirm-purchase' title='" . CLEARSDN_STORE_LANG_PURCHASE_CONFIRMATION . "'>";
	echo "<div id='sdn-confirm-purchase-content'></div>";
	echo "</div>";
	WebFormOpen($_SERVER['PHP_SELF'], "post", "support", "id='clearsdnform' target='_blank'");
	WebTableOpen(CLEARSDN_SERVICE_LANG_OVERVIEW, "100%", "clearsdn-overview");
	echo "
		<tr id='clearsdn-splash'>
		<td align='center'><img src='/images/icon-os-to-sdn.png' alt=''>
		<div id='whirly' style='padding: 10 0 10 0'>" . WEBCONFIG_ICON_LOADING . "</div>
		</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();

	// We need to bring this inside (rather than .js.php helper) because of the POST use
	echo "
        <script language='javascript'>
          $(document).ready(function() {
            $.ajax({
              type: 'POST',
              url: 'clearsdn-ajax.php',
              data: 'action=getServiceDetails&service=" . ClearSdnService::SDN_SUPPORT . (isset($_POST['usecache']) ? "&usecache=1" : "") . "',
              success: function(html) {
                $('#clearsdn-splash').remove();
                $('#clearsdn-overview').append(html);
              },
              error: function(xhr, text, err) {
                $('#whirly').html(xhr.responseText.toString());
                // TODO...should need below hack...edit templates.css someday
                $('.ui-state-error').css('max-width', '700px'); 
              }
            });
          });
        </script>
	";
}

// vim: syntax=php ts=4
?>
