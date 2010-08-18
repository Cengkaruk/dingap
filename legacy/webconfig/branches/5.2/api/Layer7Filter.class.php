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

/**
 * Protocol filter (l7-filter) class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Software.class.php');
require_once('Daemon.class.php');
require_once('Firewall.class.php');
require_once('ShellExec.class.php');
require_once('NtpTime.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Protocol filter (l7-filter) class.
 *
 * @package Api
 * @subpackage Network
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2009, Point Clark Networks
 */

class Layer7Filter extends Daemon
{
	//////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = '/etc/l7-filter/l7-filter.conf';
	const DIR_PROTOCOLS = '/etc/l7-filter/protocols';
	const FILE_CACHE = '/var/webconfig/tmp/l7-protocols.cache';
	const CMD_IPTABLES = '/sbin/iptables';

	// Old IPP2P firewall rule types
	const IPP2P			= 0x00001000;	// P2P rule
	const IPP2P_EDK		= 0x00002000;	// P2P: eDonkey, eMule, Kademlia
	const IPP2P_KAZAA	= 0x00004000;	// P2P: KaZaA, FastTrack
	const IPP2P_GNU		= 0x00008000;	// P2P: Gnutella
	const IPP2P_DC		= 0x00010000;	// P2P: Direct Connect
	const IPP2P_BIT		= 0x00020000;	// P2P: BitTorrent, extended BT
	const IPP2P_APPLE	= 0x00040000;	// P2P: AppleJuice
	const IPP2P_WINMX	= 0x00080000;	// P2P: WinMX
	const IPP2P_SOUL	= 0x00100000;	// P2P: SoulSeek
	const IPP2P_ARES	= 0x00200000;	// P2P: Ares, AresLite

	private $group_filter = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Layer7Filter constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct('l7-filter');

		require_once(GlobalGetLanguageTemplate(__FILE__));

		// Group patterns here will be removed from the groups array
		$this->group_filter[] = '/^ietf/i';
		$this->group_filter[] = '/^proprietary/i';
		$this->group_filter[] = '/^x_consortium/i';
		$this->group_filter[] = '/^open_source/i';
		$this->group_filter[] = '/^obsolete/i';
		$this->group_filter[] = '/^itu/i';
		$this->group_filter[] = '/^none$/i';
		$this->group_filter[] = '/^unknown$/i';
		$this->group_filter[] = '/^unset$/i';
	}

	/**
	 * Translate l7-filter groups to localized strings.
	 */

	final private function LocalizeGroups(&$groups)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$groups_locale = array('all' => LAYER7FILTER_LANG_GROUP_ALL);
		foreach ($groups as $group) {
			$valid = true;
			foreach ($this->group_filter as $filter) {
				if (!preg_match($filter, $group)) continue;
				$valid = false;
				break;
			}
			if (!$valid) continue;
			$tag = 'LAYER7FILTER_LANG_GROUP_' . strtoupper($group);
			if (!defined($tag))
				$string = ucwords(str_replace('_', ' ', $group));
			else eval("\$string = $tag;");
			$groups_locale[$group] = $string;
		}

		$groups = $groups_locale;
		asort($groups, SORT_LOCALE_STRING);
	}

	/**
	 * Load l7-filter configuration, enable/disable patterns.
	 */

	final private function LoadConfiguration(&$patterns)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$config = array();
		$f = new File(self::FILE_CONFIG);
		$contents = $f->GetContentsAsArray();
		foreach ($contents as $line) {
			$buffer = chop($line);
			if (!preg_match('/^[[:alnum:]]/', $buffer)) continue;
			$config[] = explode(' ',
				preg_replace('/[[:space:]]+/', ' ', $buffer));
		}

		foreach ($patterns as $key => $pattern) {
			$patterns[$key]['enabled'] = false;
			foreach ($config as $entry) {
				if (strcmp($pattern['name'], $entry[0])) continue;
				$patterns[$key]['enabled'] = true;
				$patterns[$key]['mark'] = $entry[1];
				break;
			}
		}
	}

	/**
	 * Return associative array of translated l7-filter groups.
	 * Return associative array of l7-filter protocol patterns.
	 * Attempts to load this data from a cache if present and newer
	 * than the last installed/updated l7-protocols RPM.  Generating
	 * this pattern meta data is a slow process so the results
	 * are cached to dramatically improve webconfig page loads.
	 */

	final public function GetProtocols(&$groups, &$patterns)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$rpm = new Software('l7-protocols');
		$f = new File(self::FILE_CACHE);
		if ($f->Exists() && $f->LastModified() > $rpm->GetInstallTime()) {
			$contents = $f->GetContents();
			if (($data = unserialize($contents)) !== false) {
				$groups = $data['groups'];
				$this->LocalizeGroups($groups);
				$patterns = $data['patterns'];
				$this->LoadConfiguration($patterns);
				return;
			}
		}

		$d = new Folder(self::DIR_PROTOCOLS);
		$files = $d->GetListing();

		$dirs = array();
		foreach ($files as $f) {
			$d = new Folder(sprintf('%s/%s', self::DIR_PROTOCOLS, $f));
			if ($d->IsDirectory()) $dirs[] = $f;
		}

		$patterns = array();
		foreach ($dirs as $dir) {
			$d = new Folder(self::DIR_PROTOCOLS . "/$dir");
			$files = $d->GetListing();

			foreach ($files as $f) {
				if (preg_match('/^.*\.pat$/', $f)) {
					$pattern = array();
					$pattern['dir'] = $dir;
					$pattern['file'] = $f;
					$pattern['path'] = self::DIR_PROTOCOLS . "/$dir/$f";
					$patterns[] = $pattern;
				}
			}
		}

		$groups = array();
		foreach ($patterns as $key => $pattern) {
			$f = new File($pattern['path'], 'r');
			$contents = $f->GetContentsAsArray();

			$patterns[$key]['desc'] = preg_replace('/^#[[:space:]]*/', '',
				chop($contents[0]));

			$patterns[$key]['attr'] = preg_replace('/^#[[:space:]]*pattern attributes:[[:space:]]*/i', '',
				chop($contents[1]));

			$patterns[$key]['groups'] = preg_replace('/^#[[:space:]]*protocol groups:[[:space:]]*/i', '',
				chop($contents[2]));

			if (!strlen($patterns[$key]['groups'])) $patterns[$key]['groups'] = 'none';
			$groups = array_unique(array_merge($groups, explode(' ', $patterns[$key]['groups'])));
			$patterns[$key]['groups'] = explode(' ', $patterns[$key]['groups']);

			$patterns[$key]['wiki'] = preg_replace('/^#[[:space:]]*wiki:[[:space:]]*/i', '',
				chop($contents[3]));
			if (!preg_match('/^http/i', $patterns[$key]['wiki']))
				$patterns[$key]['wiki'] = null;

			$patterns[$key]['name'] = null;

			$lines = count($contents);
			for ($i = 4 ; $i < $lines; $i++) {
				$buffer = chop($contents[$i]);
				if (!preg_match('/^[[:alnum:]]/', $buffer)) continue;
				$patterns[$key]['name'] = $buffer;
				break;
			}

			if ($patterns[$key]['name'] == null) {
				unset($patterns[$key]);
				continue;
			}

			ksort($patterns[$key]);
		}

		ksort($patterns);

		$f = new File(self::FILE_CACHE);
		if ($f->Exists()) $f->Delete();
		$f->Create('webconfig', 'webconfig', 0644);
		$cache = array('groups' => $groups, 'patterns' => $patterns);
		$f->AddLines(serialize($cache));

		$this->LocalizeGroups($groups);
		$this->LoadConfiguration($patterns);
	}

	/**
	 * Get blocked packet/bytes iptables status.
	 */
	final public function GetStatus(&$patterns)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$enabled = 0;
		foreach ($patterns as $pattern) {
			if ($pattern['enabled']) $enabled++;
		}
		if ($enabled == 0) return;

		$shell = new ShellExec();
		$exitcode = $shell->Execute(self::CMD_IPTABLES,
			'-t mangle -L l7-filter-drop -v -n', true);

		if ($exitcode != 0) {
			// The command will fail in standalone mode.  Could certainly handle this better.
			return;
		}

		$contents = $shell->GetOutput();

		foreach ($contents as $mark) {
			// 0	 0 DROP	   all  --  *	  *	   0.0.0.0/0			0.0.0.0/0		   MARK match 0x1c
			if (!preg_match('/^[[:space:]]*([[:digit:]KMG]+)[[:space:]]+([[:digit:]KMG]+)[[:space:]]+DROP.*match[[:space:]]+0x([[:xdigit:]]+)$/', chop($mark), $matches)) continue;
			foreach ($patterns as $key => $pattern) {
				if ($pattern['mark'] != hexdec($matches[3])) continue;
				$patterns[$key]['packets'] = $matches[1];
				$patterns[$key]['bytes'] = $matches[2];
				break;
			}
		}
	}

	/**
	 * Enable (block) an l7-filter protocol pattern.
	 */

	final public function EnableProtocol(&$patterns, $protocol)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!is_array($patterns))
			throw new EngineException(LAYER7FILTER_LANG_ERR_INVALID_TYPE, COMMON_ERROR);

		$valid = false;
		foreach ($patterns as $key => $pattern) {
			if (strcasecmp($pattern['name'], $protocol)) continue;
			$valid = true;
			$patterns[$key]['enabled'] = true;
			break;
		}

		if (!$valid)
			throw new EngineException(LAYER7FILTER_LANG_ERR_INVALID_PROTOCOL, COMMON_ERROR);
	}

	/**
	 * Disable (unblock) an l7-filter protocol pattern.
	 */

	final public function DisableProtocol(&$patterns, $protocol)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!is_array($patterns))
			throw new EngineException(LAYER7FILTER_LANG_ERR_INVALID_TYPE, COMMON_ERROR);

		$valid = false;
		foreach ($patterns as $key => $pattern) {
			if (strcasecmp($pattern['name'], $protocol)) continue;
			$valid = true;
			$patterns[$key]['enabled'] = false;
			break;
		}

		if (!$valid)
			throw new EngineException(LAYER7FILTER_LANG_ERR_INVALID_PROTOCOL, COMMON_ERROR);
	}

	/**
	 * Toggle (block/unblock) an l7-filter protocol pattern.
	 */

	final public function ToggleProtocol(&$patterns, $protocol)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!is_array($patterns))
			throw new EngineException(LAYER7FILTER_LANG_ERR_INVALID_TYPE, COMMON_ERROR);

		$valid = false;
		foreach ($patterns as $key => $pattern) {
			if (strcasecmp($pattern['name'], $protocol)) continue;
			$valid = true;
			$patterns[$key]['enabled'] = ($patterns[$key]['enabled']) ? false : true;
			break;
		}

		if (!$valid)
			throw new EngineException(LAYER7FILTER_LANG_ERR_INVALID_PROTOCOL, COMMON_ERROR);
	}

	/**
	 * Write l7-filter configuration, restart the l7-filter-userspace
	 * daemon and firewall.
	 */

	final public function CommitChanges($patterns)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$protocols = array();
		foreach ($patterns as $pattern) {
			if ($pattern['enabled'] !== true) continue;
			$protocols[] = $pattern['name'];
		}

		// PHP will throw a notice if time zone is not explicity set.
		$ntptime = new NtpTime();
		date_default_timezone_set($ntptime->GetTimeZone());

		$mark = 3;
		$contents = array();
		$contents[] = '# This configuration file was generated by webconfig';
		$contents[] = '# ' . strftime('%c');
		sort($protocols, SORT_STRING);
		foreach ($protocols as $name)
			$contents[] = sprintf('%-40s %-3d', $name, $mark++);

		$f = new File(self::FILE_CONFIG, true);
		$f->DumpContentsFromArray($contents);

		if ($this->GetBootState()) $this->Restart();
		$fw = new Firewall();
		$fw->Restart();
	}

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vi: ts=4
?>
