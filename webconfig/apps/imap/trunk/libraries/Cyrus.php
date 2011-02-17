<?php

/**
 * Cyrus mail server class.
 *
 * @category   Apps
 * @package    IMAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/imap/
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

namespace clearos\apps\imap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('imap');

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

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cyrus mail server class.
 *
 * In order to handle the LDAP/Cyrus account synchronization, access to IMAP 
 * on 127.0.0.1 must be running at all times.
 *
 * @category   Apps
 * @package    IMAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/imap/
 */

class Cyrus extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG_CYRUS = '/etc/cyrus.conf';
    const SERVICE_IMAP = 'imap';
    const SERVICE_IMAPS = 'imaps';
    const SERVICE_POP3 = 'pop3';
    const SERVICE_POP3S = 'pop3s';
    const STATE_NEW = 1;
    const STATE_ENABLED = 2;
    const STATE_DISABLED = 3;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();
    protected $services = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Cyrus constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('cyrus-imapd');

        $this->services = array(
                self::SERVICE_IMAP,
                self::SERVICE_IMAPS,
                self::SERVICE_POP3,
                self::SERVICE_POP3S
        );
    }

    /**
     * Enables idled service.
     *
     * @param boolean $state service state
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_idled_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_state($state));

        if (!$this->is_loaded)
            $this->_load_config();

        if ($state) {
            $this->config['start']['idled']['state'] = self::STATE_NEW;
            $this->config['start']['idled']['cmd'] = 'idled';
        } else {
            $this->config['start']['idled']['state'] = self::STATE_DISABLED;
        }

        $this->_save_config();
    }

    /**
     * Enables service.
     *
     * @param string  $service service name
     * @param boolean $state service state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_service_state($service, $state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_service($service));
        Validation_Exception::is_valid($this->validate_state($state));

        if (!$this->is_loaded)
            $this->_load_config();

        if ($state) {
            if ($service === self::SERVICE_IMAPS) {
                $this->config['services'][self::SERVICE_IMAPS]['state'] = self::STATE_NEW;
                $this->config['services'][self::SERVICE_IMAPS]['cmd'] = 'imapd -s';
                $this->config['services'][self::SERVICE_IMAPS]['listen'] = 993;
                $this->config['services'][self::SERVICE_IMAPS]['prefork'] = 3;
            } else if ($service === self::SERVICE_IMAP) {
                $this->config['services'][self::SERVICE_IMAP]['state'] = self::STATE_NEW;
                $this->config['services'][self::SERVICE_IMAP]['cmd'] = 'imapd';
                $this->config['services'][self::SERVICE_IMAP]['listen'] = 143;
                $this->config['services'][self::SERVICE_IMAP]['prefork'] = 3;
            } else if ($service === self::SERVICE_POP3S) {
                $this->config['services'][self::SERVICE_POP3S]['state'] = self::STATE_NEW;
                $this->config['services'][self::SERVICE_POP3S]['cmd'] = 'pop3d -s';
                $this->config['services'][self::SERVICE_POP3S]['listen'] = 995;
                $this->config['services'][self::SERVICE_POP3S]['prefork'] = 0;
            } else if ($service === self::SERVICE_POP3) {
                $this->config['services'][self::SERVICE_POP3]['state'] = self::STATE_NEW;
                $this->config['services'][self::SERVICE_POP3]['cmd'] = 'pop3d';
                $this->config['services'][self::SERVICE_POP3]['listen'] = 110;
                $this->config['services'][self::SERVICE_POP3]['prefork'] = 0;
            }
        } else {
            if ($service === self::SERVICE_IMAP) {
                $this->config['services'][$service]['state'] = self::STATE_NEW;
                $this->config['services'][$service]['listen'] = '127.0.0.1:143';
                $this->config['services'][self::SERVICE_IMAP]['cmd'] = 'imapd';
                $this->config['services'][self::SERVICE_IMAP]['prefork'] = 3;
            } else {
                $this->config['services'][$service]['state'] = self::STATE_DISABLED;
            }
        }

        $this->_save_config();
    }

    /**
     * Returns the state of idled (push mail).
     *
     * @return boolean TRUE if service is enabled
     * @throws Engine_Exception
     */

    public function get_idled_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['start']['idled']['state']) 
            && ($this->config['start']['idled']['state'] === self::STATE_ENABLED)
        ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Returns list of available services
     *
     * @return array list of services
     * @throws Engine_Exception, Validation_Exception
     */

    public function get_service_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->services;
    }

    /**
     * Returns the state of the service.
     *
     * @param string $service service name
     *
     * @return boolean TRUE if service is enabled
     * @throws Engine_Exception, Validation_Exception
     */

    public function get_service_state($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_service($service));

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config['services'][$service]['state']) 
            && ($this->config['services'][$service]['state'] === self::STATE_ENABLED)
        ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates the service name.
     *
     * @param string $service service name
     *
     * @return string error message if service is invalid
     */

    public function validate_service($service)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! in_array($service, $this->services))
            return lang('imap_mail_service_invalid');
    }

    /**
     * Validates the state.
     *
     * @param string $state state
     *
     * @return string error message if state is invalid
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $section = '';
        $matches = array();
        $this->config['services'] = array();

        $file = new File(self::FILE_CONFIG_CYRUS);
        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            if (preg_match('/^\s*START/', $line))
                $section = 'start';

            if (preg_match('/^\s*SERVICES/', $line))
                $section = 'services';

            if (preg_match('/^\s*EVENTS/', $line))
                $section = 'events';

            if (preg_match('/^\s*}/', $line))
                $section = '';

            if (preg_match('/^\s*[a-z][A-Z]*/', $line)) {

                // process
                if (preg_match('/^\s*([^\s]*)/', $line, $matches))
                    $process = $matches[1];
                else
                    $process = 'unknown';

                $this->config[$section][$process]['state'] = self::STATE_ENABLED;

                // cmd
                if (preg_match('/cmd="([^\"]*)"/', $line, $matches))
                    $this->config[$section][$process]['cmd'] = $matches[1];

                // prefork
                if (preg_match('/prefork=([^\s]*)/', $line, $matches))
                    $this->config[$section][$process]['prefork'] = preg_replace("/[\"\']/", '', $matches[1]);

                // listen
                if (preg_match('/listen=([^\s]*)/', $line, $matches))
                    $this->config[$section][$process]['listen'] = preg_replace("/[\"\']/", '', $matches[1]);

                // See note about LDAP/Cyrus above.  If IMAP is only listening on localhost
                // then we consider IMAP to be disabled.
                if (($process === self::SERVICE_IMAP) 
                    && (preg_match('/127.0.0.1/', $this->config[$section][$process]['listen']))
                )
                    $this->config[$section][$process]['state'] = self::STATE_DISABLED;
            }
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Saves configuration.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG_CYRUS);
        $lines = $file->get_contents_as_array();

        $section = '';
        $config = array();
        $matches = array();
// FIXME: imap on 127.0.0.1 is not working

        foreach ($lines as $line) {

            // Find new sections
            //------------------

            if (preg_match('/^\s*START/', $line)) {
                $section = 'start';
            } else if (preg_match('/^\s*SERVICES/', $line)) {
                $section = 'services';
            } else if (preg_match('/^\s*EVENTS/', $line)) {
                $section = 'events';
            }

            // Add new lines when we have reached the end of a section
            //--------------------------------------------------------
    
            if (preg_match('/^\s*}/', $line) && $section) {
                foreach ($this->config[$section] as $process => $details) {
                    if ($details['state'] === self::STATE_NEW) {

                        $new_lines = "  $process";

                        if (isset($details['cmd']))
                            $new_lines .= ' cmd="' . $details['cmd'] . '"';

                        if (isset($details['listen']))
                            $new_lines .= ' listen="' . $details['listen'] . '"';

                        if (isset($details['prefork']))
                            $new_lines .= ' prefork=' . $details['prefork'];

                        $config[] = rtrim($new_lines);
                    }
                }

                $section = "";
            }

            // Parse existing item entries (imaps cmd=...)
            //--------------------------------------------

            if (preg_match('/^\s*[a-z][A-Z]*/', $line)) {
                if (preg_match('/^\s*([^\s]*)/', $line, $matches))
                    $process = $matches[1];
                else
                    $process = "unknown";

                // STATE_DISABLED: delete the line
                // STATE_ENABLED: keep existing line
                // STATE_NEW: rewrite line (not fully implemented)

                if (isset($this->config[$section][$process]['state'])) {
                    if ($this->config[$section][$process]['state'] === self::STATE_DISABLED)
                        continue;
                    else if ($this->config[$section][$process]['state'] === self::STATE_NEW)
                        continue;
                }
            }

            $config[] = $line;
        }

        $file->dump_contents_from_array($config);

        $this->is_loaded = FALSE;
    }
}
