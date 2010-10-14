<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS framework configuration
 *
 * @package Framework
 * @author {@link http://www.foundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2010, ClearFoundation
 */

class ClearOsConfig {
	// Paths
	public static $apps_path = '/usr/clearos/apps';
	public static $framework_path = '/usr/clearos/framework';
	public static $htdocs_path = '/usr/clearos/framework/htdocs';
	public static $themes_path = '/usr/clearos/themes';

	// Debug mode
	public static $debug_mode = FALSE;
	public static $debug_log_path = '/var/log/webconfig/';

	// Development versioning
	public static $clearos_devel_versions = array();
}

// vim: syntax=php ts=4
