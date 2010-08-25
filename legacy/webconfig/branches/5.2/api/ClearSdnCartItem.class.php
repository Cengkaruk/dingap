<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearCenter
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
// Eventually, this will get merged with the WebServices class. For now, this
// class will handle its own request to the Service Delivery Network.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearSDN Cart Item class.
 *
 * @package Api
 * @subpackage WebServices
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('File.class.php');
require_once('ClearSdnShoppingCart.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearSDN shopping cart item class.
 *
 * Provides information on available ClearSDN services provided by ClearCenter
 *
 * @package Api
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnCartItem extends Engine
{

	protected $id;
	protected $item = Array();
	const CLASS_SERVICE = "sdn-service";
	const CLASS_DNS = "sdn-dns";
	const CLASS_MODULE = "sdn-module";
	const CLASS_SSL = "sdn-ssl";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ClearSDN shopping cart constructor.
	 */

	function __construct($id)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->id = $id;

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Sets the cart item's product ID.
	 *
	 * @param int $pid  a product ID from ClearSDN
	 * @throws ValidationException
	 */

	public function SetPid($pid)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($pid <= 0)
			throw new ValidationException(CLEARSDN_CART_ITEM_LOCALE_INVALID_PID);

		$this->item[$this->id]['pid'] = $pid;
	}

	/**
	 * Sets the cart item's product description.
	 *
	 * @param string $description  a product description from ClearSDN
	 * @throws ValidationException
	 */

	public function SetDescription($description)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['description'] = $description;
	}

	/**
	 * Sets the cart item's unit.
	 *
	 * @param string $unit the product unit
	 * @throws ValidationException
	 */

	public function SetUnit($unit)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['unit'] = $unit;
	}

	/**
	 * Sets the cart item's product discount.
	 *
	 * @param float $discount the product discount
	 * @throws ValidationException
	 */

	public function SetDiscount($discount)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['discount'] = $discount;
	}

	/**
	 * Sets the cart item's product unit price.
	 *
	 * @param float $unit_price the product unit price
	 * @throws ValidationException
	 */

	public function SetUnitPrice($unit_price)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['unit_price'] = $unit_price;
	}

	/**
	 * Sets the cart item's product currency.
	 *
	 * @param String currency the unit price currency
	 * @throws ValidationException
	 */

	public function SetCurrency($currency)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['currency'] = $currency;
	}

	/**
	 * Sets the cart class.
	 *
	 * @param String the class of the product
	 * @throws ValidationException
	 */

	public function SetClass($class)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['class'] = $class;
	}

	/**
	 * Sets the cart group ID.
	 *
	 * @param String the group ID
	 * @throws ValidationException
	 */

	public function SetGroup($group)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->item[$this->id]['group'] = $group;
	}

	/**
	 * Get a shopping cart item
	 *
	 * @throws EngineException
	 * @return array  cart information
	 */

	public function Get()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item;
	}

	/**
	 * Gets the cart item's ID.
	 *
	 * @throws ValidationException
	 */

	public function GetId()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->id;
	}

	/**
	 * Gets the cart item's product ID.
	 *
	 */

	public function GetPid()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['pid'];
	}

	/**
	 * Gets the cart item's product description.
	 *
	 */

	public function GetDescription()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['description'];
	}

	/**
	 * Gets the cart item's product unit price.
	 *
	 */

	public function GetUnit()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['unit'];
	}

	/**
	 * Gets the cart item's product unit price.
	 *
	 */

	public function GetUnitPrice()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['unit_price'];
	}

	/**
	 * Gets the cart item's product discount.
	 *
	 */

	public function GetDiscount()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['discount'];
	}

	/**
	 * Gets the cart item's currency.
	 *
	 */

	public function GetCurrency()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['currency'];
	}

	/**
	 * Gets the cart item's class.
	 *
	 */

	public function GetClass()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['class'];
	}

	/**
	 * Gets the cart item's group ID.
	 *
	 */

	public function GetGroup()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->item[$this->id]['group'];
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
