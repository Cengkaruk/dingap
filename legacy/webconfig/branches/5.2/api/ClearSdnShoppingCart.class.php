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
 * ClearSDN Shopping Cart class.
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
require_once('Folder.class.php');
require_once('ClearSdnCartItem.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearSDN shopping cart class.
 *
 * Provides information on available ClearSDN services provided by ClearCenter
 *
 * @package Api
 * @author {@link http://www.clearcenter.com/ ClearCenter}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearCenter
 */

class ClearSdnShoppingCart extends Engine
{
	protected $is_loaded = false;
	protected $contents = array();
	const FOLDER_STORE = "/var/lib/suva/store";
	const FILE_CART = "/var/lib/suva/store/cart.txt";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ClearSDN shopping cart constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns an array of items in the shopping cart.
	 *
	 * @param boolean $saved  flag to retrieve current or saved items (default is all items in the current cart)
	 * @return array  an array of ClearSdnCartItem objects
	 * @throws EngineException
	 */

	public function GetItems($saved = false)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadFromFile();

		return $this->contents;
	}

	/**
	 * Adds an item to the shopping cart
	 *
	 * @param obj $item  a ClearSdnCartItem
	 * @throws EngineException
	 */

	public function AddItem($item)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadFromFile();

		$counter = 0;
		$found = false;
		foreach ($this->contents as $cartitem) {
			if ($cartitem->GetId() == $item->GetId()) {
				$this->contents[$counter] = $item;
				$found = true;
				break;
			}
			$counter++;
		}

		if (!$found)
			$this->contents[] = $item;

		$this->_SaveToFile();
	}

	/**
	 * Deletes an item from the shopping cart
	 *
	 * @param string $id  a key representing a ClearSdnCartItem in the cart
	 * @throws EngineException
	 */

	public function DeleteItem($key)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadFromFile();

		$counter = 0;
		$found = false;
		foreach ($this->contents as $cartitem) {
			if ($cartitem->GetId() == $key) {
				unset($this->contents[$counter]);
				$found = true;
				break;
			}
			$counter++;
		}

		if (!$found)
			throw new EngineException(CLEARSDN_SHOPPING_CART_LANG_ITEM_NOT_FOUND);

		$this->_SaveToFile();
	}

	/**
	 * Clears the shopping cart
	 *
	 * @throws EngineException
	 */

	public function Clear()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CART, true);

			if ($file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new GeneralException ($e->getMessage());
		}
	}

	/**
	 * @access private
	 */

	protected function _SaveToFile()
	{
		try {
			$folder = new Folder(self::FOLDER_STORE, true);
			if (!$folder->Exists())
				$folder->Create('webconfig', 'webconfig', 755);
			
			$file = new File(self::FILE_CART, true);

			if ($file->Exists())
				$file->Delete();

			$file->Create('webconfig', 'webconfig', 600);
			foreach ($this->contents as $lineitem) {
				$file->AddLines(serialize($lineitem));
			}
		} catch (Exception $e) {
			throw new EngineException ($e->GetMessage());
		}
	}

	/**
	 * @access private
	 */

	protected function _LoadFromFile()
	{
		try {
			$file = new File(self::FILE_CART, true);

			if (!$file->Exists()) {
				$this->is_loaded = true;
				return;
			}

			$contents = $file->GetContentsAsArray();
			foreach ($contents as $lineitem) {
				$this->contents[] = unserialize($lineitem);
			}

			$this->is_loaded = true;
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
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
