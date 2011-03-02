<?php

/**
 * Postgrey class.
 *
 * @category   Apps
 * @package    Greylisting
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2007-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/greylisting/
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

namespace clearos\apps\greylisting;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('greylisting');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\smtp\Postfix as Postfix;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('smtp/Postfix');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Postgrey class.
 *
 * @category   Apps
 * @package    Greylisting
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2007-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/greylisting/
 */

class Postgrey extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/sysconfig/postgrey';
    const DEFAULT_DELAY = 300;
    const DEFAULT_RETENTION_TIME = 35;
    const DEFAULT_CONFIG = 'OPTIONS="--delay=$DELAY --max-age=$MAXAGE"';
    const MAX_DELAY = 100000;
    const MAX_RETENTION_TIME = 3650;
    const CONSTANT_POSTFIX_POLICY_SERVICE = 'unix:/var/spool/postfix/postgrey/socket';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Postgrey constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('postgrey');
    }

    /**
     * Returns greylist delay in seconds.
     *
     * @return integer greylist delay in seconds
     * @throws Engine_Exception
     */

    public function get_delay()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['DELAY']))
            return $this->config['DELAY'];
        else
            return self::DEFAULT_DELAY;
    }

    /**
     * Returns retention time (in days) for entries in database.
     *
     * @return integer retention time for entries in database
     * @throws Engine_Exception
     */

    public function get_retention_time()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['MAXAGE']))
            return $this->config['MAXAGE'];
        else
            return self::DEFAULT_RETENTION_TIME;
    }

    /**
     * Returns state of filter.
     *
     * @return boolean TRUE if filter is enabled
     * @throws Engine_Exception
     */

    public function get_mail_configuration_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        $postfix = new Postfix();
        $is_in_postfix = $postfix->get_policy_service(self::CONSTANT_POSTFIX_POLICY_SERVICE);
        
        if ($is_in_postfix)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Sets greylist delay in seconds.
     *
     * @param integer $seconds greylist delay in seconds
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_delay($seconds)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_delay($seconds));

        $this->_set_parameter('DELAY', $seconds);
    }

    /**
     * Sets retention time (in days) for entries in database.
     *
     * @param integer $days retention time (in days) for entries in database.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_retention_time($days)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_retention_time($days));

        $this->_set_parameter('MAXAGE', $days);
    }

    /**
     * Sets the state of the greylist service.
     *
     * @param boolean $state state of greylist service
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */
    
    public function set_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_state($state));

        if ($state) {
            $this->set_running_state(TRUE);
            $this->set_boot_state(TRUE);
        } else {
            $this->set_running_state(FALSE);
            $this->set_boot_state(FALSE);
        }

        $postfix = new Postfix();
        $postfix->set_policy_service(self::CONSTANT_POSTFIX_POLICY_SERVICE, $state);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for delay.
     *
     * @param integer $delay delay in seconds
     *
     * @return boolean error message if delay is invalid
     */

    public function validate_delay($delay)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^\d+$/', $delay) || ($delay > self::MAX_DELAY))
            return lang('greylisting_delay_is_invalid');
    }

    /**
     * Validation routine for retention time.
     *
     * @param integer $days retention time in days
     *
     * @return boolean error message if retention time is invalid
     */

    public function validate_retention_time($days)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^\d+$/', $days) || ($days > self::MAX_RETENTION_TIME))
            return lang('greylisting_maximum_retention_time_is_invalid');
    }

    /**
     * Validation routine for state.
     *
     * @param boolean $state state
     *
     * @return boolean error message if state is invalid
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: discuss
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Sets a parameter.
     *
     * @param string $key   key
     * @param string $value value for key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);
            $match = $file->replace_lines("/^$key=.*/", "$key=\"$value\"\n");
            if (!$match)
                $file->add_lines_before("$key=\"$value\"\n", '/^OPTIONS=/');
        } catch (File_Not_Found_Exception $e) {
            $file->create('root', 'root', '0644');
            $file->add_lines(self::DEFAULT_CONFIG . "\n");
            $file->add_lines_before("$key=\"$value\"\n", '/^OPTIONS=/');
        } catch (File_No_Match_Exception $e) {
            $file->add_lines(self::DEFAULT_CONFIG . "\n");
            $file->add_lines_before("$key=\"$value\"\n", '/^OPTIONS=/');
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }

    /**
     * Loads configuration file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new Configuration_File(self::FILE_CONFIG);
            $config = $file->Load();
        } catch (File_Not_Found_Exception $e) {
            // Empty configuration
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }

        if (!empty($config)) {
            foreach ($config as $key => $value)
                $this->config[$key] = preg_replace('/"/', '', $value);
        }

        $this->is_loaded = TRUE;
    }
}
