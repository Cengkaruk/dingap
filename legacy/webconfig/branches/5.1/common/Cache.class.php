<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
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

/**
 * Cache class.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C E S
///////////////////////////////////////////////////////////////////////////////

require_once(COMMON_CORE_DIR . '/api/File.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * A data-caching class.
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 *
 * Based on {@link http://www.phpguru.org/static/Caching.html Caching with PHP5}
 *  Copyright 2005 Richard Heyes
 */

class Cache
{
	/**
	* Whether caching is enabled
	* @var bool
	*/
	public $enabled = true;

	/**
	* Place to store the cache files
	* @var string
	*/
	protected $store = '/dev/shm/';

	/**
	* Prefix to use on cache files
	* @var string
	*/
	protected $prefix = 'cache_';

	/**
	* Stores data
	*
	* @param string $group Group to store data under
	* @param string $id    Unique ID of this data
	* @param int    $ttl   How long to cache for (in seconds)
	*/
	protected function Write($group, $id, $ttl, $data)
	{
		$filename = $this->GetFilename($group, $id);

		if ($fp = gzopen($filename, 'wb5')) {

			if (flock($fp, LOCK_EX)) {
				gzwrite($fp, $data);
			}

			gzclose($fp);

			// Set filemtime
			touch($filename, time() + $ttl);
		} else {
			throw new FileIoException('cache write',COMMON_NOTICE);
		}
	}

	/**
	* Reads data
	*
	* @param string $group Group to store data under
	* @param string $id    Unique ID of this data
	*/
	protected function Read($group, $id)
	{
		$filename = $this->GetFilename($group, $id);

		if ($fp = gzopen($filename,'rb5')) {
			$contents = gzread($fp, filesize($filename));
			gzclose($fp);
		} else {
			throw new FileIoException('cache read',COMMON_NOTICE);
		}

		return $contents;
	}

	/**
	* Determines if an entry is cached
	*
	* @param string $group Group to store data under
	* @param string $id    Unique ID of this data
	*/
	protected function IsCached($group, $id)
	{
		$filename = $this->GetFilename($group, $id);

		if ($this->enabled && file_exists($filename) && filemtime($filename) > time()) {
			return true;
		}

		if (file_exists($filename)){
		  unlink($filename);
		}
		return false;
	}

	/**
	* Builds a filename/path from group, id and
	* store.
	*
	* @param string $group Group to store data under
	* @param string $id    Unique ID of this data
	*/
	protected function GetFilename($group, $id)
	{
		$id = md5($id);

		return $this->store . $this->prefix . "{$group}_{$id}";
	}

	/**
	* Sets the filename prefix to use
	*
	* @param string $prefix Filename Prefix to use
	*/
	public function SetPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	/**
	* Sets the store for cache files. Defaults to
	* /dev/shm. Must have trailing slash.
	*
	* @param string $store The dir to store the cache data in
	*/
	public function SetStore($store)
	{
		$this->store = $store;
	}
}

/**
 * Output Cache extension of base caching class
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class OutputCache extends Cache
{
	/**
	* Group of currently being recorded data
	* @var string
	*/
	private $group;

	/**
	* ID of currently being recorded data
	* @var string
	*/
	private $id;

	/**
	* Ttl of currently being recorded data
	* @var int
	*/
	private $ttl;

	/**
	* Starts caching off. Returns true if cached, and dumps
	* the output. False if not cached and start output buffering.
	*
	* @param  string $group Group to store data under
	* @param  string $id    Unique ID of this data
	* @param  int    $ttl   How long to cache for (in seconds)
	* @return bool          True if cached, false if not
	*/
	public function Start($group, $id, $ttl)
	{
		if ($this->IsCached($group, $id)) {
			echo $this->Read($group, $id);
			return true;

		} else {

			ob_start();

			$this->group = $group;
			$this->id    = $id;
			$this->ttl   = $ttl;

			return false;
		}
	}

	/**
	* Ends caching. Writes data to disk.
	*/
	public function End()
	{
		$data = ob_get_contents();
		ob_end_flush();

		$this->Write($this->group, $this->id, $this->ttl, $data);
	}
}

/**
 * Data cache extension of base caching class
 *
 * @package Common
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class DataCache extends Cache
{
	/**
	* Retrieves data from the cache
	*
	* @param  string $group Group this data belongs to
	* @param  string $id    Unique ID of the data
	* @return mixed         Either the resulting data, or null
	*/
	public function Get($group, $id)
	{
		if ($this->IsCached($group, $id)) {
			return unserialize($this->Read($group, $id));
		}

		return null;
	}

	/**
	* Stores data in the cache
	*
	* @param string $group Group this data belongs to
	* @param string $id    Unique ID of the data
	* @param int    $ttl   How long to cache for (in seconds)
	* @param mixed  $data  The data to store
	*/
	public function Put($group, $id, $ttl, $data)
	{
		$this->Write($group, $id, $ttl, serialize($data));
	}

	/**
	* Do "garbage-collection" on the cache
	* remove expired objects
	*/
	function Clean()
	{
		$dir = $this->store;
		$dh = opendir($dir);

		while ($filename = readdir($dh)) {
			if ($filename == '.' OR $filename == '..' OR (strpos($filename,$this->prefix) === false)) {
				continue;
			}

			if (filemtime($dir . DIRECTORY_SEPARATOR . $filename) < time()) {
				unlink($dir . DIRECTORY_SEPARATOR . $filename);
			}
		}
	}
}

// vim: syntax=php ts=4
?>
