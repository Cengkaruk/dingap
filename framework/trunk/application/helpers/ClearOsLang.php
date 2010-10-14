<?php
/**
 * CodeIgniter
 * Modifed by ClearFoundation to give API similar functionality to CI_Lang
 *
 * @package		ClearOS
 * @author		ExpressionEngine Dev Team / ClearFoundation
 * @copyright	Copyright (c) 2008 - 2010, EllisLab, Inc., ClearFoundation 2010
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 */

// ------------------------------------------------------------------------

/**
 * ClearOS Language Class
 *
 * @package		ClearOS
 * @subpackage	Libraries
 * @author		ExpressionEngine Dev Team / ClearFoundation
 */
class ClearOsLang {

	var $use_ci = TRUE;
	var $language = array();
	var $is_loaded = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function ClearOsLang()
	{}

	// --------------------------------------------------------------------

	/**
	 * Load a language file
	 *
	 * @accesspublic
	 * @param string $langfile the language file
	 * @return true if load was successful
	 */
	function load($langfile = '')
	{
		$langfile = $langfile . "_lang.php";

		if (in_array($langfile, $this->is_loaded, TRUE))
			return;

		// FIXME - pull in language
		// $deft_lang = ( ! isset($config['language'])) ? 'english' : $config['language'];
		// $idiom = ($deft_lang == '') ? 'english' : $deft_lang;

		// Load the language file
//		if (file_exists(APPPATH.'language/'.$idiom.'/'.$langfile)) {
	//		include(APPPATH.'language/'.$idiom.'/'.$langfile);
			include("/home/peter/devel/clearos/apps/date/trunk/language/english/$langfile");
//		} else {
			// FIXME
//		}

		$this->is_loaded[] = $langfile;
		$this->language = array_merge($this->language, $lang);

		unset($lang);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a single line of text from the language array
	 *
	 * @access	public
	 * @param	string	$line 	the language line
	 * @return	string
	 */
	function line($line = '')
	{
		$line = ($line == '' OR ! isset($this->language[$line])) ? FALSE : $this->language[$line];
		return $line;
	}
}
