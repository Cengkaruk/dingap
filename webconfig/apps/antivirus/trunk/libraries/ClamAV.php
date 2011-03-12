<?php

/**
 * ClamAV class.
 *
 * @category   Apps
 * @package    Antivirus
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antivirus/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\antivirus;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('antivirus');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClamAV class.
 *
 * @category   Apps
 * @package    Antivirus
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/antivirus/
 */

class ClamAV extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clamd.conf';
    const DEFAULT_ARCHIVE_MAX_FILES = 1000;
    const DEFAULT_ARCHIVE_MAX_FILE_SIZE = 10;
    const DEFAULT_ARCHIVE_MAX_RECURSION = 8;
    const DEFAULT_BLOCK_ENCRYPTED = FALSE;
    const DEFAULT_PHISHING_SIGNATURES = TRUE;
    const DEFAULT_PHISHING_SCAN_URLS = TRUE;
    const DEFAULT_PHISHING_ALWAYS_BLOCK_SSL_MISMATCH = FALSE;
    const DEFAULT_PHISHING_ALWAYS_CLOAK = FALSE;
    const CONSTANT_MAX_FILES = 100000;
    const CONSTANT_MAX_FILE_SIZE = 200;
    const CONSTANT_MAX_RECURSION = 50;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * ClamAV constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('clamd');
    }

    /**
     * Returns archive encryption policy.
     *
     * @return boolean TRUE if archives should be marked as a virus if encrypted
     * @throws Engine_Exception
     */

    public function get_block_encrypted()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('ArchiveBlockEncrypted', $this->config))
            return $this->_get_boolean($this->config['ArchiveBlockEncrypted']);
        else
            return self::DEFAULT_BLOCK_ENCRYPTED;
    }

    /**
     * Returns maximum number of files to be scanned in an archive.
     *
     * @return integer maximum number of files
     * @throws Engine_Exception
     */

    public function get_max_files()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('MaxFiles', $this->config))
            return $this->config['MaxFiles'];
        else
            return self::DEFAULT_ARCHIVE_MAX_FILES;
    }

    /**
     * Returns maximum file size to be scanned (in megabytes).
     *
     * @return integer maximum file size
     * @throws Engine_Exception
     */

    public function get_max_file_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('MaxFileSize', $this->config))
            return preg_replace('/M\s*$/', '', $this->config['MaxFileSize']);
        else
            return self::DEFAULT_ARCHIVE_MAX_FILE_SIZE;
    }

    /**
     * Returns maximum recursion in archive.
     *
     * For example, if a zip file contains another zip file, files within 
     * the second zip will also be scanned.  This result from this method
     * specifies the number of iterations.
     *
     * @return integer maximum recursion
     * @throws Engine_Exception
     */

    public function get_max_recursion()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('MaxRecursion', $this->config))
            return $this->config['MaxRecursion'];
        else
            return self::DEFAULT_ARCHIVE_MAX_RECURSION;
    }

    /**
     * Returns phishing signature state.
     *
     * @return boolean state of phishing signature engine
     * @throws Engine_Exception
     */

    public function get_phishing_signatures_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('PhishingSignatures', $this->config))
            return $this->_get_boolean($this->config['PhishingSignatures']);
        else
            return self::DEFAULT_PHISHING_SIGNATURES;
    }
    
    /**
     * Returns state of URL scanning using heuristics.
     *
     * @return boolean state of URL scanning
     * @throws Engine_Exception
     */

    public function get_phishing_scan_urls_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('PhishingScanURLs', $this->config))
            return $this->_get_boolean($this->config['PhishingScanURLs']);
        else
            return self::DEFAULT_PHISHING_SCAN_URLS;
    }
    
    /**
     * Returns state of SSL URL mismatch scan.
     *
     * @return boolean state of SSL URL mismatch scanning
     * @throws Engine_Exception
     */

    public function get_phishing_always_block_ssl_mismatch()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('PhishingAlwaysBlockSSLMismatch', $this->config))
            return $this->_get_boolean($this->config['PhishingAlwaysBlockSSLMismatch']);
        else
            return self::DEFAULT_PHISHING_ALWAYS_BLOCK_SSL_MISMATCH;
    }
    
    /**
     * Returns state of cloak URL blocking.
     *
     * @return boolean state of cloak URL blocking
     * @throws Engine_Exception
     */

    public function get_phishing_always_block_cloak()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (array_key_exists('PhishingAlwaysBlockCloak', $this->config))
            return $this->_get_boolean($this->config['PhishingAlwaysBlockCloak']);
        else
            return self::DEFAULT_PHISHING_ALWAYS_CLOAK;
    }
    
    /**
     * Set archive block encryption policy.
     *
     * @param boolean $policy set to TRUE to enable archive encryption blocking
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_block_encrypted($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_block_encrypted($policy));

        $this->_set_boolean_parameter('ArchiveBlockEncrypted', $policy);
    }

    /**
     * Sets maximum number of files to be scanned in an archive.
     *
     * @param int $max maximum number of files to be scanned in an archive
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_max_files($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_files($max));

        $this->_set_parameter('MaxFiles', $max);
    }

    /**
     * Sets maximum file size inside archive to be scanned.
     *
     * @param int $max maximum file size inside archive to be scanned
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_max_file_size($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_file_size($max));

        $this->_set_parameter('MaxFileSize', $max . 'M');
    }

    /**
     * Sets maximum recursion in archive scan.
     *
     * @param int $max maximum recursion in archive scan
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_max_recursion($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_recursion($max));

        $this->_set_parameter('MaxRecursion', $max);
    }

    /**
     * Sets phishing signature state.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_phishing_signatures_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_phishing_signatures_state($state));

        $this->_set_boolean_parameter('PhishingSignatures', $state);
    }

    /**
     * Sets state of URL scanning using heuristics.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_phishing_scan_urls_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_phishing_scan_urls_state($state));

        $this->_set_boolean_parameter('PhishingScanURLs', $state);
    }

    /**
     * Sets state of SSL URL mismatch scan.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_phishing_always_block_ssl_mismatch($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_phishing_always_block_ssl_mismatch($state));

        $this->_set_boolean_parameter('PhishingAlwaysBlockSSLMismatch', $state);
    }

    /**
     * Sets state of cloak URL blocking.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_phishing_always_block_cloak($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_phishing_always_block_cloak($state));

        $this->_set_boolean_parameter('PhishingAlwaysBlockCloak', $state);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation for block encrypted archive flag.
     *
     * @param boolean $flag block encrypted archive flag
     *
     * @return error message if flag is invalid
     */

    public function validate_block_encrypted($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flag = (bool)$flag;

        if (! is_bool($flag))
            return lang('antivirus_block_encrypted_archive_flag_invalid');
    }

    /**
     * Validation for maximum number of files to be scanned in an archive.
     *
     * @param integer $max maximum number of files
     *
     * @return error message if maximum is invalid
     */

    public function validate_max_files($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        $max = (int)$max;

        if ((!is_int($max)) || ($max <= 0) || ($max > self::CONSTANT_MAX_FILES))
            return lang('antivirus_max_files_invalid');
    }

    /**
     * Validation for maximum file size to be scanned.
     *
     * @param integer $max maximum file size
     *
     * @return error message if maximum is invalid
     */

    public function validate_max_file_size($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        $max = (int)$max;

        if ((!is_int($max)) || ($max <= 0) || ($max > self::CONSTANT_MAX_FILE_SIZE))
            return lang('antivirus_max_file_size_invalid');
    }

    /**
     * Validation for maxiumum recursion in archive scan.
     *
     * @param integer $max maximum recursion
     *
     * @return error message if maximum is invalid
     */

    public function validate_max_recursion($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        $max = (int)$max;

        if ((!is_int($max)) || ($max < 0) || ($max > self::CONSTANT_MAX_RECURSION))
            return lang('antivirus_max_recursion_invalid');
    }

    /**
     * Validation for phishing signatures engine.
     *
     * @param boolean $flag state of phishing signatures engine
     *
     * @return error message if flag is invalid
     */

    public function validate_phishing_signatures_state($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flag = (bool)$flag;

        if (! is_bool($flag))
            return lang('antivirus_phishing_signatures_state_invalid');
    }

    /**
     * Validation for scan URLs engine.
     *
     * @param boolean $flag state of scan URLs engine
     *
     * @return error message if flag is invalid
     */

    public function validate_phishing_scan_urls_state($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flag = (bool)$flag;

        if (! is_bool($flag))
            return lang('antivirus_phishing_scan_urls_state');
    }

    /**
     * Validation for SSL URL mismatch scan.
     *
     * @param boolean $flag state of SSL URL mismatch scanning
     *
     * @return error message if flag is invalid
     */

    public function validate_phishing_always_block_ssl_mismatch($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flag = (bool)$flag;

        if (! is_bool($flag))
            return lang('antivirus_phishing_always_block_ssl_mismatch');
    }

    /**
     * Validation for SSL cloaking scan.
     *
     * @param boolean $flag state of SSL cloaking scanning
     *
     * @return error message if flag is invalid
     */

    public function validate_phishing_always_block_cloak($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flag = (bool)$flag;

        if (! is_bool($flag))
            return lang('antivirus_phishing_always_block_cloak');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a boolean for ClamAV yes/no parameters.
     *
     * @param string $value value of a ClamAV boolean (yes, no)
     *
     * @access private
     * @return boolean boolean value
     */

    protected function _get_boolean($value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $retval = (preg_match('/^yes$/i', $value)) ? TRUE : FALSE;

        return $retval;
    }

    /**
     * Loads configuration files.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);
        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line) || preg_match('/^\s*$/', $line))
                continue;

            $items = preg_split('/\s+/', $line);
            $this->config[$items[0]] = isset($items[1]) ? $items[1] : '';
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Sets a boolean parameter in the config file.
     *
     * @param string  $key    name of the key in the config file
     * @param boolean $policy value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_boolean_parameter($key, $policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        $bool_value = $policy ? 'yes' : 'no';

        $file = new File(self::FILE_CONFIG);
        $match = $file->replace_lines("/^\s*$key\s+/", "$key $bool_value\n");

        if (count($match) === 0)
            $file->add_lines("$key $bool_value\n");

        $this->is_loaded = FALSE;
    }

    /**
     * Sets a parameter in the config file.
     *
     * @param string $key   name of the key in the config file
     * @param string $value value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^$key\s+/", "$key $value\n");

        if (!$match) {
            $match = $file->replace_lines("/^#\s*$key\s+/", "$key $value\n");
            if (!$match)
                $file->add_lines("$key = $value\n");
        }

        $this->is_loaded = FALSE;
    }
}
