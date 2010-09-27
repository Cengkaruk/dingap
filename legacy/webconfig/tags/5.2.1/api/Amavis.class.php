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

/**
 * Amavis class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2009, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Daemon.class.php');
require_once('Engine.class.php');
require_once('File.class.php');
require_once('FileTypes.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Amavis class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006-2009, Point Clark Networks
 */

class Amavis extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $params = array();
	protected $banned_extensions = array();
	protected $double_extensions = array();
	protected $mime_types = array();
	protected $is_loaded = false;

	const FILE_CONFIG = '/etc/amavisd/api.conf';
	const FILE_IMAGES_CONFIG = '/etc/mail/spamassassin/FuzzyOcr.cf';
	const DEFAULT_FINAL_SPAM_DESTINY = 'D_BOUNCE';
	const DEFAULT_FINAL_VIRUS_DESTINY = 'D_DISCARD';
	const DEFAULT_KILL_LEVEL = 25;
	const DEFAULT_MAX_CHILDREN = 2;
	const DEFAULT_QUARANTINE_LEVEL = 'undef';
	const DEFAULT_SUBJECT_TAG_LEVEL = 2;
	const TYPE_PASS = 'D_PASS';
	const TYPE_BOUNCE = 'D_BOUNCE';
	const TYPE_DISCARD = 'D_DISCARD';
	const POLICY_PASS = 'pass';
	const POLICY_BOUNCE = 'bounce';
	const POLICY_DISCARD = 'discard';
	const POLICY_QUARANTINE = 'quarantine';
	const QUARANTINE_METHOD_SQL = 'sql:';
	const CONSTANT_UNDEF = 'undef';
	const CONSTANT_REMOVE_PARAMETER = 'pcn_remove';
	const CONSTANT_MIME_TYPES = 'MIME types';
	const CONSTANT_BANNED_EXTENSIONS = 'Banned extensions';
	const CONSTANT_DOUBLE_EXTENSIONS = 'Double extensions';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Amavis constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("amavisd");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Returns the state of the antispam engine.
	 *
	 * @return boolean state
	 * @throws EngineException
	 */

	function GetAntispamState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		if (isset($this->params['bypass_spam_checks_maps'])) {
			if ($this->params['bypass_spam_checks_maps'] == "(1)")
				return false;
			else
				throw new EngineException(LOCALE_LANG_ERRMSG_CONFIGURATION_FILE_HAS_BEEN_CUSTOMIZED, COMMON_NOTICE);
		} else {
			return true;
		}
	}

	/**
	 * Returns antispam discard and quarantine settings.
	 *
	 * The discard/quarantine logic in the Amavis configuration file is a bit
	 * non-intuitive.  For example, the sa_kill_level_deflt can be either the
	 * spam level used to discard a message, or, the spam level used to quarantine
	 * the message; it depends on how other parameters are set!  To hide these 
	 * details in the API, coarse methods are created:
	 *
	 * Get/SetAntispamDiscardAndQuarantine($discard, $discard_threshold, $quarantine, $quarantine_threshold)
	 *
	 * The logic behind these methods is shown in the following table:
	 *
	 *                            | final_x_destiny | sa_kill_level_deflt | x_quarantine_method | sa_quarantine_cutoff_level
	 *                            +-----------------+---------------------+---------------------+---------------------------
	 * Discard + Quarantine       |    D_DISCARD    | used for quarantine |        sql:         |     used for discard
	 * Discard + No Quarantine    |    D_DISCARD    |   used for discard  |      <blank>        |         undef (n/a)
	 * No Discard + Quarantine    |    D_DISCARD    | used for quarantine |        sql:         |         undef
	 * No Discard + No Quarantine |      D_PASS     |         n/a         |      <blank>        |         undef (n/a)
	 *
	 * @return string block type
	 * @throws EngineException
	 */

	function GetAntispamDiscardAndQuarantine()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		// Get the raw configuration data
		//-------------------------------

		if (isset($this->params['final_spam_destiny']))
			$final_spam_destiny = $this->params['final_spam_destiny'];
		else
			$final_spam_destiny = Amavis::DEFAULT_FINAL_SPAM_DESTINY;

		if (isset($this->params['sa_kill_level_deflt']))
			$sa_kill_level = $this->params['sa_kill_level_deflt'];
		else
			$sa_kill_level = Amavis::DEFAULT_KILL_LEVEL;

		if (isset($this->params['spam_quarantine_method']))
			$spam_quarantine_method = $this->params['spam_quarantine_method'];
		else
			$spam_quarantine_method = '';

		if (isset($this->params['sa_quarantine_cutoff_level'])) 
			$sa_quarantine_level = $this->params['sa_quarantine_cutoff_level'];
		else
			$sa_quarantine_level = Amavis::DEFAULT_QUARANTINE_LEVEL;

		// Apply the logic described above
		//--------------------------------

		$info = array();

		if (($final_spam_destiny == Amavis::TYPE_DISCARD) && 
			($spam_quarantine_method != '') &&
			($sa_quarantine_level != Amavis::CONSTANT_UNDEF)) {
			$info['discard'] = true;
			$info['quarantine'] = true;
			$info['discard_level'] = $sa_quarantine_level;
			$info['quarantine_level'] = $sa_kill_level;
		} else if (($final_spam_destiny == Amavis::TYPE_DISCARD) && ($spam_quarantine_method == '')) {
			$info['discard'] = true;
			$info['quarantine'] = false;
			$info['discard_level'] = $sa_kill_level;
			$info['quarantine_level'] = $sa_kill_level - 5;
		} else if (($final_spam_destiny == Amavis::TYPE_DISCARD) && 
			($spam_quarantine_method != '') &&
			($sa_quarantine_level == Amavis::CONSTANT_UNDEF)) {
			$info['discard'] = false;
			$info['quarantine'] = true;
			$info['discard_level'] = $sa_kill_level + 5;
			$info['quarantine_level'] = $sa_kill_level;
		} else if (($final_spam_destiny == Amavis::TYPE_PASS)) {
			$info['discard'] = false;
			$info['quarantine'] = false;
			$info['discard_level'] = 20;
			$info['quarantine_level'] = 15;
		}

		return $info;
	}

	/**
	 * Returns antivirus policy.
	 *
	 * Return values:
	 * - Amavis::POLICY_PASS
	 * - Amavis::POLICY_DISCARD
	 * - Amavis::POLICY_QUARANTINE
	 *
	 * @return string antivirus policy
	 * @throws EngineException
	 */

	function GetAntivirusPolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		$final_virus_destiny = $this->params['final_virus_destiny'];
		$virus_quarantine_method = $this->params['virus_quarantine_method'];

		if ($final_virus_destiny == Amavis::TYPE_PASS) {
			$policy = Amavis::POLICY_PASS;
		} else if (($final_virus_destiny == Amavis::TYPE_DISCARD) && ($virus_quarantine_method == '')) {
			$policy = Amavis::POLICY_DISCARD;
		} else if (($final_virus_destiny == Amavis::TYPE_DISCARD) && ($virus_quarantine_method != '')) {
			$policy = Amavis::POLICY_QUARANTINE;
		}

		return $policy;
	}

	/**
	 * Returns the state of the antivirus engine.
	 *
	 * @return boolean state
	 * @throws EngineException
	 */

	function GetAntivirusState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		if (isset($this->params['bypass_virus_checks_maps'])) {
			if ($this->params['bypass_virus_checks_maps'] == "(1)")
				return false;
			else
				throw new EngineException(LOCALE_LANG_ERRMSG_CONFIGURATION_FILE_HAS_BEEN_CUSTOMIZED, COMMON_NOTICE);
		} else {
			return true;
		}
	}

	/**
	 * Returns bad header policy.
	 *
	 * @return string bad header policy type
	 * @throws EngineException
	 */

	function GetBadHeaderPolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		$final_bad_header_destiny = $this->params['final_bad_header_destiny'];
		$bad_header_quarantine_method = $this->params['bad_header_quarantine_method'];

		if ($final_bad_header_destiny == Amavis::TYPE_PASS) {
			$policy = Amavis::POLICY_PASS;
		} else if ($final_bad_header_destiny == Amavis::TYPE_BOUNCE) {
			$policy = Amavis::POLICY_BOUNCE;
		} else if (($final_bad_header_destiny == Amavis::TYPE_DISCARD) && ($bad_header_quarantine_method == '')) {
			$policy = Amavis::POLICY_DISCARD;
		} else if (($final_bad_header_destiny == Amavis::TYPE_DISCARD) && ($bad_header_quarantine_method != '')) {
			$policy = Amavis::POLICY_QUARANTINE;
		}

		return $policy;
	}

	/**
	 * Returns banned files policy.
	 *
	 * @return string banned files policy
	 * @throws EngineException
	 */

	function GetBannedPolicy()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		$final_banned_destiny = $this->params['final_banned_destiny'];
		$banned_files_quarantine_method = $this->params['banned_files_quarantine_method'];

		if ($final_banned_destiny == Amavis::TYPE_PASS) {
			$policy = Amavis::POLICY_PASS;
		} else if ($final_banned_destiny == Amavis::TYPE_BOUNCE) {
			$policy = Amavis::POLICY_BOUNCE;
		} else if (($final_banned_destiny == Amavis::TYPE_DISCARD) && ($banned_files_quarantine_method == '')) {
			$policy = Amavis::POLICY_DISCARD;
		} else if (($final_banned_destiny == Amavis::TYPE_DISCARD) && ($banned_files_quarantine_method != '')) {
			$policy = Amavis::POLICY_QUARANTINE;
		}

		return $policy;
	}

	/**
	 * Returns list of available extensions.
	 *
	 * @return array list of available extensions
	 * @throws EngineException
	 */

	function GetBannedExtensionList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = array();
		$filetypes = new FileTypes();

		try {
			$list = $filetypes->GetFileExtensions();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return $list;
	}

	/**
	 * Returns list of banned extensions.
	 *
	 * @return array list of banned extensions
	 * @throws EngineException
	 */

	function GetBannedExtensions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		return $this->banned_extensions;
	}

	/**
	 * Returns state of image processing.
	 *
	 * @return boolean true if image processing is enabled
	 * @throws EngineException
	 */

	function GetImageProcessingState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_IMAGES_CONFIG);

		try {
			$lines = $file->GetContentsAsArray();
			foreach ($lines as $line) {
				if (preg_match("/^\s*loadplugin\s+.*FuzzyOcr\s*/", $line))
					return true;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return false;
	}


	/**
	 * Returns the maximum number of children.
	 *
	 * @return integer maximum number of children.
	 * @throws EngineException
	 */

	function GetMaxChildren()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		if (isset($this->params['max_servers']))
			return $this->params['max_servers'];
		else
			return self::DEFAULT_MAX_CHILDREN;
	}

	/**
	 * Returns the subject tag for spam.
	 *
	 * @return string subject tag
	 * @throws EngineException
	 */

	function GetSubjectTag()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		if (isset($this->params['sa_spam_subject_tag']))
			return $this->params['sa_spam_subject_tag'];
		else
			return "";
	}

	/**
	 * Returns required score to use subject tag.
	 *
	 * @return float required hits
	 * @throws EngineException
	 */

	function GetSubjectTagLevel()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		if (isset($this->params['sa_tag2_level_deflt']))
			return $this->params['sa_tag2_level_deflt'];
		else
			return self::DEFAULT_SUBJECT_TAG_LEVEL;
	}

	/**
	 * Returns state of subject tag re-writing.
	 *
	 * @return boolean true if subject tag rewriting is on
	 * @throws EngineException
	 */

	function GetSubjectTagState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->LoadConfig();

		if (isset($this->params['sa_spam_modifies_subj']) && ($this->params['sa_spam_modifies_subj'] == 1))
			return true;
		else
			return false;
	}

	/**
	 * Returns antispam discard and quarantine settings.
	 *
	 * @param boolean $discard state of discard engine
	 * @param int $discard_threshold discard level
	 * @param boolean $quarantine state of quarantine engine
	 * @param int $quarantine_threshold quarantine level
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetAntispamDiscardAndQuarantine($discard, $discard_level, $quarantine, $quarantine_level)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($discard && ($discard_level > 0) && $quarantine && ($quarantine_level > 0)) {
			$this->SetParameterValue('$final_spam_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$sa_kill_level_deflt', $quarantine_level);
			$this->SetParameterValue('$spam_quarantine_method', "'" . Amavis::QUARANTINE_METHOD_SQL . "'");
			$this->SetParameterValue('$sa_quarantine_cutoff_level', $discard_level);
		} else if ($discard && ($discard_level > 0) && !$quarantine) {
			$this->SetParameterValue('$final_spam_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$sa_kill_level_deflt', $discard_level);
			$this->SetParameterValue('$spam_quarantine_method', '');
			$this->SetParameterValue('$sa_quarantine_cutoff_level', Amavis::CONSTANT_UNDEF);
		} else if (!$discard && $quarantine && ($quarantine_level > 0)) {
			$this->SetParameterValue('$final_spam_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$sa_kill_level_deflt', $quarantine_level);
			$this->SetParameterValue('$spam_quarantine_method', "'" . Amavis::QUARANTINE_METHOD_SQL . "'");
			$this->SetParameterValue('$sa_quarantine_cutoff_level', Amavis::CONSTANT_UNDEF);
		} else if (!$discard && !$quarantine) {
			$this->SetParameterValue('$final_spam_destiny', Amavis::TYPE_PASS);
			$this->SetParameterValue('$spam_quarantine_method', '');
			$this->SetParameterValue('$sa_quarantine_cutoff_level', Amavis::CONSTANT_UNDEF);
		}
	}

	/**
	 * Sets the state of the antispam engine.
	 *
	 * @param boolean $state state of the antispam engine
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetAntispamState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID);

		if ($state)
			$this->SetParameterValue('@bypass_spam_checks_maps', self::CONSTANT_REMOVE_PARAMETER);
		else
			$this->SetParameterValue('@bypass_spam_checks_maps', "(1)");
	}

	/**
	 * Sets the antivirus policy.
	 *
	 * @param string $policy antivirus policy
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetAntivirusPolicy($policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($policy == Amavis::POLICY_PASS) {
			$this->SetParameterValue('$final_virus_destiny', Amavis::TYPE_PASS);
			$this->SetParameterValue('$virus_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_DISCARD) {
			$this->SetParameterValue('$final_virus_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$virus_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_QUARANTINE) {
			$this->SetParameterValue('$final_virus_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$virus_quarantine_method', "'" . Amavis::QUARANTINE_METHOD_SQL . "'");
		} else {
			throw new ValidationException(AMAVIS_LANG_VIRUS_DETECTED_POLICY . " - " . LOCALE_LANG_INVALID);
		}
	}

	/**
	 * Sets the state of the antivirus engine.
	 *
	 * @param boolean $state state of the antivirus engine
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetAntivirusState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($state))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID);

		if ($state)
			$this->SetParameterValue('@bypass_virus_checks_maps', self::CONSTANT_REMOVE_PARAMETER);
		else
			$this->SetParameterValue('@bypass_virus_checks_maps', "(1)");
	}

	/**
	 * Sets the bad header policy.
	 *
	 * @param string $policy bad header policy
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetBadHeaderPolicy($policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($policy == Amavis::POLICY_PASS) {
			$this->SetParameterValue('$final_bad_header_destiny', Amavis::TYPE_PASS);
			$this->SetParameterValue('$bad_header_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_DISCARD) {
			$this->SetParameterValue('$final_bad_header_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$bad_header_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_QUARANTINE) {
			$this->SetParameterValue('$final_bad_header_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$bad_header_quarantine_method', "'" . Amavis::QUARANTINE_METHOD_SQL . "'");
		} else {
			throw new ValidationException(AMAVIS_LANG_BAD_HEADER_POLICY . " - " . LOCALE_LANG_INVALID);
		}
	}

	/**
	 * Sets the banned files policy.
	 *
	 * @param string $policy banned files policy
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetBannedPolicy($policy)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if ($policy == Amavis::POLICY_PASS) {
			$this->SetParameterValue('$final_banned_destiny', Amavis::TYPE_PASS);
			$this->SetParameterValue('$banned_files_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_BOUNCE) {
			$this->SetParameterValue('$final_banned_destiny', Amavis::TYPE_BOUNCE);
			$this->SetParameterValue('$banned_files_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_DISCARD) {
			$this->SetParameterValue('$final_banned_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$banned_files_quarantine_method', '');
		} else if ($policy == Amavis::POLICY_QUARANTINE) {
			$this->SetParameterValue('$final_banned_destiny', Amavis::TYPE_DISCARD);
			$this->SetParameterValue('$banned_files_quarantine_method', "'" . Amavis::QUARANTINE_METHOD_SQL . "'");
		} else {
			throw new ValidationException(AMAVIS_LANG_BANNED_FILE_EXTENSION_POLICY . " - " . LOCALE_LANG_INVALID);
		}
	}

	/**
	 * Sets list of banned extensions.
	 *
	 * @param array $extensions list of banned extensions
	 * @throws EngineException, ValidationException
	 */

	function SetBannedExtensions($extensions)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$badlist = array();
		$allowedextensions = array_keys($this->GetBannedExtensionList());

		foreach ($extensions as $extension) {
			if (!in_array($extension, $allowedextensions)) {
				$badlist[] = $extension;
			}
		}

		if (count($badlist) > 0) {
			$badout = implode(' ', $badlist);
			throw new ValidationException(FILETYPES_LANG_FILE_EXTENSION . " ($badout) - " . LOCALE_LANG_INVALID);
		}

		$this->is_loaded = false;

		$amavislist = implode('|', $extensions);

		try {
			$newlines = array();

			$file = new File(self::FILE_CONFIG);
			$lines = $file->GetContentsAsArray();

			$skip = false;
			
			foreach ($lines as $line) {
				if ($skip) {
					$skip = false;
				} else {
					if (preg_match("/^\s*# " . self::CONSTANT_BANNED_EXTENSIONS . "/", $line)) {
						$newlines[] = '  # ' . self::CONSTANT_BANNED_EXTENSIONS;
						$newlines[] = '  qr\'\.(' . $amavislist . ')$\'i,';
						$skip = true;
					} else {
						$newlines[] = $line;
					}
				}
			}

			$file->DumpContentsFromArray($newlines);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets state of image processing.
	 *
	 * @param boolean $state state of image processing
	 * @throws EngineException
	 */

	function SetImageProcessingState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$enabled = $this->GetImageProcessingState();

		try {
			if (!$enabled && $state) {
				$file = new File(self::FILE_IMAGES_CONFIG . ".disabled");
				if (! $file->Exists())
					throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_ERROR);
				$file->MoveTo(self::FILE_IMAGES_CONFIG);
			} else if ($enabled && !$state) {
				$file = new File(self::FILE_IMAGES_CONFIG);
				$file->MoveTo(self::FILE_IMAGES_CONFIG . ".disabled");

				$emptyfile = new File(self::FILE_IMAGES_CONFIG);
				$emptyfile->Create("root", "root", "0644");
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the maximum number of children.
	 *
	 * @param integer $children maximum number of children to spawn.
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetMaxChildren($children)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidMaxChildren($children))
			throw new ValidationException(AMAVIS_LANG_MAIL_VOLUME . " - " . LOCALE_LANG_INVALID);

		$this->SetParameterValue('$max_servers', $children);
	}

	/**
	 * Sets the subject tag.
	 *
	 * @param string $tag subject tag
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetSubjectTag($tag)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidSubjectTag($tag))
			throw new ValidationException(AMAVIS_LANG_SUBJECT_TAG . " - " . LOCALE_LANG_INVALID);

		$this->SetParameterValue('$sa_spam_subject_tag', "\"$tag\"");
	}

	/**
	 * Sets required hits before marking mail as spam.
	 *
	 * @param float $requiredhit required hits
	 * @return void
	 * @throws EngineException, ValidationException
	 */

	function SetSubjectTagLevel($level)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsValidSubjectTagLevel($level))
			throw new ValidationException(AMAVIS_LANG_SUBJECT_TAG_LEVEL . " - " . LOCALE_LANG_INVALID);

		$this->SetParameterValue('$sa_tag2_level_deflt', $level);
	}

	/**
	 * Sets if the subject should be rewritten
	 *
	 * @param boolean $rewrite true if the subject should be rewritten
	 * @return void
	 */

	function SetSubjectTagState($rewrite)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! is_bool($rewrite))
			throw new ValidationException(AMAVIS_LANG_SUBJECT_TAG_STATE . " - " . LOCALE_LANG_INVALID);

		if ($rewrite)
			$value = 1;
		else
			$value = 0;

		$this->SetParameterValue('$sa_spam_modifies_subj', $value);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration values.
	 *
	 * @access private
	 * @throws EngineException
	 */

	function LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$lines = $file->GetContentsAsArray();
			$is_banned_filename = false;
			$current = "";

			foreach ($lines as $line) {
				$matches = array();

				if (preg_match('/^\s*\$(.*)\s*=\s*(.*);/', $line, $matches)) {
					$value = preg_replace("/['\"]/", "", $matches[2]);
					$this->params[trim($matches[1])] = $value;
				} else if (preg_match('/^\s*@(.*)\s*=\s*(.*);/', $line, $matches)) {
					$value = preg_replace("/['\"]/", "", $matches[2]);
					$this->params[trim($matches[1])] = $value;
				// The rest of the logic is for parsing banned_filename_re
				} else if (preg_match("/banned_filename_re\s*=/", $line)) {
					$is_banned_filename = true;
				} else if (preg_match("/;\s*$/", $line)) {
					$is_banned_filename = false;
				} else if ($is_banned_filename && preg_match("/#\s*(.*)/", $line, $matches)) {
					$current = $matches[1];
				} else if (($current == self::CONSTANT_BANNED_EXTENSIONS) && (preg_match('/^\s*qr\'\\\\.\((.*)\)/', $line, $matches))) {
					$this->banned_extensions = explode("|", $matches[1]);
					$current = "";
				} else if (($current == self::CONSTANT_DOUBLE_EXTENSIONS) && (preg_match('/^\s*qr([^\(]*)*\((.*)\)/', $line, $matches))) {
					$this->double_extensions = explode("|", $matches[1]);
					$current = "";
				} else if (($current == self::CONSTANT_MIME_TYPES) && (preg_match('/^\s*qr\'\^(.*)\$/', $line, $matches))) {
					$this->mime_types[] = $matches[1];
				}
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * Sets the subject tag
	 *
	 * @access private
	 * @param string $parameter key in configuration file
	 * @param string $value value for given parameter
	 * @return void
	 * @throws EngineException
	 */

	function SetParameterValue($parameter, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->is_loaded = false;

		try {
			$file = new File(self::FILE_CONFIG);

			$parameter_preg = preg_quote($parameter);

			if ($value === self::CONSTANT_REMOVE_PARAMETER) {
				$file->ReplaceLines("/^\s*$parameter_preg\s*=/i", "");
			} else {
				if (empty($value))
					$value = "''";

				$match = $file->ReplaceLines("/^\s*$parameter_preg\s*=/i", "$parameter = $value;\n");

				if (!$match)
					$file->AddLinesBefore("$parameter = $value;\n", "/^1;$/");
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}


	/*************************************************************************/
	/* V A L I D A T I O N   R O U T I N E S								 */
	/*************************************************************************/

	/**
	 * Validation routine for block level
	 *
	 * @return boolean true if valid
	 */
	
	function IsValidBlockLevel($level)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^[0-9]+$/", $level))
			return true;

		if (preg_match("/^[0-9]+\.[0-9]{1,2}$/", $level))
			return true;

		return false;
	}

	/**
	 * Validation routine for block type
	 *
	 * @return boolean true if valid
	 */
	
	function IsValidMaxChildren($children)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^\d+$/", $children) && ($children > 0) && ($children <= 100))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for subject tag
	 *
	 * @return boolean true if valid
	 */

	function IsValidSubjectTag($tag)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^[ \w-\.\[\]]+$/", $tag))
			return true;
		else
			return false;
	}

	/**
	 * Validation routine for subject tag level
	 *
	 * @return boolean true if valid
	 */
	
	function IsValidSubjectTagLevel($level)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (preg_match("/^[0-9]+$/", $level))
			return true;

		if (preg_match("/^[0-9]+\.[0-9]{1,2}$/", $level))
			return true;

		return false;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>
