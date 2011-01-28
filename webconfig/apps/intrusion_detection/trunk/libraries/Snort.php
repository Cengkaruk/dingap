<?php

/**
 * Snort intrusion detection class.
 *
 * @category   Apps
 * @package    Intrusion_Detection
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_detection/
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

namespace clearos\apps\intrusion_detection;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('intrusion_detection');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Snort intrusion detection class.
 *
 * @category   Apps
 * @package    Intrusion_Detection
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/intrusion_detection/
 */

class Snort extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/snort.conf';
    const PATH_RULES =  '/etc/snort';
    const TYPE_SECURITY = 'security';
    const TYPE_POLICY = 'policy';
    const TYPE_CRUFT = 'cruft';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();
    protected $rule_sets = array();
    protected $types = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Snort constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('snort');

        require_once('Snort.inc.php');

        $this->rule_sets = $rule_sets;

        $this->types = array(
            self::TYPE_SECURITY,
            self::TYPE_POLICY,
            self::TYPE_CRUFT
        );
    }

    /**
     * Returns list of active rule sets.
     *
     * @return array list of active rule sets
     * @throws Engine_Exception
     */

    public function get_active_rule_sets()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        return $this->config['active_rule_sets'];
    }

    /**
     * Returns list of installed rule sets.
     *
     * @return array list of installed rule sets
     * @throws Engine_Exception
     */

    public function get_rule_sets()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        foreach ($this->rule_sets as $rule_set => $details) {
            $list[] = $rule_set;
        }

        return $list;
    }

    /**
     * Returns detail information on all supported rule sets.
     *
     * @return array information on all supported rule sets
     * @throws Engine_Exception
     */

    public function get_rule_set_details()
    {
        clearos_profile(__METHOD__, __LINE__);

        $info = array();
        $list = array();

        foreach ($this->rule_sets as $rule_set => $detail) {

            $info['rule_set'] = $rule_set;
            $info['description'] = $detail['description'];
            $info['type'] = $detail['type'];

            try {
                $file = new File(self::PATH_RULES . '/' . $rule_set);
                $lines = $file->get_contents_as_array();
            } catch (File_Not_Found_Exception $e) {
                $info['installed'] = FALSE;
                $info['count'] = 0;
                $list[$rule_set] = $info;
                continue;
            }

            $count = 0;

            foreach ($lines as $line) {
                if (preg_match('/^alert/', $line))
                    $count++;
            }

            $info['count'] = $count;
            $info['installed'] = TRUE;

            $list[$rule_set] = $info;
        }

        return $list;
    }

    /**
     * Returns list of rule set types.
     *
     * @returns array list of rule set types
     * @throws Engine_Exception
     */

    public function get_rule_set_types()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->types;
    }

    /**
     * Sets the list of active rule sets.
     *
     * @param array $list list of rule sets
     *
     * @returns void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_active_rule_sets($list)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_rule_set_list($list));

        $this->config['active_rule_sets'] = $list;

        $this->_save_config();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    public function validate_rule_set_list($list)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule_sets = $this->get_rule_sets();

        foreach ($list as $rule_set) {
            if (! in_array($rule_set, $rule_sets))
                return lang('intrusion_detection_validate_rule_set_does_not_exist');
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads the snort.conf configuration file
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $lines = array();

        $file = new File(self::FILE_CONFIG);
        $lines = $file->get_contents_as_array();

        $matches = array();

        foreach ($lines as $line) {
            if (preg_match('/^\s*include\s+\$RULE_PATH\/(.*)/', $line, $matches))
                $this->config['active_rule_sets'][] = $matches[1];
        }
    }

    /**
     * Saves the current configuration.
     *
     * @access private
     *
     * @returns void
     * @throws Engine_Exception
     */

    private function _save_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->is_loaded = FALSE;

        $file = new File(self::FILE_CONFIG);
        $lines = $file->get_contents_as_array();

        // Try to add the rules in the same spot in the config file.

        $new_lines = array();
        $rules_added = FALSE;
        $matches = array();
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*include\s+\$RULE_PATH\/(.*)/', $line, $matches)) {
                if (!$rules_added) {
                    $rules_added = TRUE;
                    foreach ($this->config['active_rule_sets'] as $rule)
                        $new_lines[] = 'include $RULE_PATH/' . $rule;
                }

                continue;
            } else {
                $new_lines[] = $line;
            }
        }


        if (!$rules_added) {
            foreach ($this->config['active_rule_sets'] as $rule)
                $new_lines[]    = 'include $RULE_PATH/' . $rule;
        }

        $file->dump_contents_from_array($new_lines);
    }
}
