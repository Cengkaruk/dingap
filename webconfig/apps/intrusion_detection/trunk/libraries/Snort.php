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
use \clearos\apps\base\Software as Software;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Software');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

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
    const PATH_RULES =  '/etc/snort.d/rules';
    const TYPE_POLICY = 'policy';
    const TYPE_SECURITY = 'security';
    const TYPE_UNSUPPORTED = 'unsupported';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $rule_sets_loaded = FALSE;
    protected $config = array();
    protected $metadata = array();

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

        include clearos_app_base('intrusion_detection') . '/deploy/rule_sets.php';

        $this->metadata = $rule_sets;

        $this->types = array(
            self::TYPE_POLICY => lang('intrusion_detection_rule_set_type_policy'),
            self::TYPE_SECURITY => lang('intrusion_detection_rule_set_type_security'),
            self::TYPE_UNSUPPORTED => lang('intrusion_detection_rule_set_type_unsupported')
        );
    }

    /**
     * Returns list of installed rule set vendors.
     *
     * @return array list of installed rule set vendors
     * @throws Engine_Exception
     */

    public function get_installed_vendors()
    {
        clearos_profile(__METHOD__, __LINE__);

        $rules = $this->get_rule_set_details();

        $installed = array();

        foreach ($rules as $vendor => $rule_sets)
            $installed[] = $vendor;

        return $installed;
    }

    /**
     * Returns detailed list of rule sets.
     *
     * @return array detailed list of rule sets
     * @throws Engine_Exception
     */

    public function get_rule_set_details()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->rule_sets_loaded)
            $this->_load_rule_sets();

        return $this->rule_sets;
    }

    /**
     * Returns list of rule set types.
     *
     * @return array list of rule set types
     * @throws Engine_Exception
     */

    public function get_rule_set_types()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->types;
    }

    /**
     * Returns vendor information.
     *
     * @param string $vendor vendor
     * @param string $rpm    RPM base name
     *
     * @return array vendor information
     * @throws Engine_Exception
     */

    public function get_vendor_information($vendor, $rpm)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_vendor($vendor));

        $detail_list = $this->get_rule_set_details();
        $rule_sets = $detail_list[$vendor];

        $info = array();
        $total_rules = 0;
        $total_rule_sets = 0;

        foreach ($rule_sets as $rule_set => $details) {
            if ($details['type'] === self::TYPE_UNSUPPORTED)
                continue;

            $total_rules += $details['count'];
            $total_rule_sets++;
        }

        $software = new Software($rpm);

        $info['last_update'] = $software->get_build_time();
        $info['total_rules'] = $total_rules;
        $info['total_rule_sets'] = $total_rule_sets;

        return $info;
    }

    /**
     * Set state of rule sets.
     *
     * If the state of a rule set is not given, it is set to disabled.
     *
     * @param string $vendor    vendor
     * @param array  $rule_sets state of rule sets
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_rule_sets($vendor, $rule_sets)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_vendor($vendor));
        Validation_Exception::is_valid($this->validate_rule_sets($vendor, $rule_sets));

        // Grab current rules and set the new state
        //-----------------------------------------

        $detail_list = $this->get_rule_set_details();
        $vendor_list = $detail_list[$vendor];
        $save_list = array();

        foreach ($vendor_list as $rule_set => $details) {
            if (array_key_exists($rule_set, $rule_sets))
                $save_list[$rule_set] = $rule_sets[$rule_set];
            else
                $save_list[$rule_set] = FALSE;
        }

        $this->_save_rule_sets($vendor, $save_list);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation for rule set list.
     *
     * @param string $vendor vendor code
     * @param string $list   rule set list
     *
     * @return string error message if rule set list is invalid
     */

    public function validate_rule_sets($vendor, $list)
    {
        clearos_profile(__METHOD__, __LINE__);

        $detail_list = $this->get_rule_set_details();
        $vendor_list = $detail_list[$vendor];

        foreach ($list as $rule_set => $state) {
            if (! array_key_exists($rule_set, $vendor_list))
                return lang('intrusion_detection_rule_set_does_not_exist');
        }
    }

    /**
     * Validation for vendor code.
     *
     * @param string $vendor vendor code
     *
     * @return string error message if vendor code is invalid
     */

    public function validate_vendor($vendor)
    {
        clearos_profile(__METHOD__, __LINE__);

        $vendors = $this->get_installed_vendors();

        if (! in_array($vendor, $vendors))
            return lang('intrusion_detection_rule_set_vendor_does_not_exist');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads the rule set information.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_rule_sets()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Gather vendor information
        //--------------------------

        $vendor_folder = new Folder(self::PATH_RULES);
        $vendors = $vendor_folder->get_listing();

        // Gather information from scanning the files in rules directory
        //--------------------------------------------------------------

        foreach ($vendors as $vendor) {

            $folder = new Folder(self::PATH_RULES . '/' . $vendor);
            $installed = $folder->get_listing();

            foreach ($installed as $rule_set_file) {
                if (! preg_match('/\.rules$/', $rule_set_file))
                    continue;

                $file = new File(self::PATH_RULES . '/' . $vendor . '/' . $rule_set_file);
                $lines = $file->get_contents_as_array();

                $count = 0;

                foreach ($lines as $line) {
                    if (preg_match('/^alert/', $line))
                        $count++;
                }
                
                $rule_set = preg_replace('/\.rules$/', '', $rule_set_file);

                $info['rule_set'] = $rule_set;
                $info['filename'] = $rule_set_file;
                $info['count'] = $count;
                $info['installed'] = TRUE;
                $info['active'] = FALSE;

                if (empty($this->metadata[$rule_set]['description']))
                    $info['description'] = lang('intrusion_detection_rulelist_unsupported');
                else
                    $info['description'] = $this->metadata[$rule_set]['description'];

                if (empty($this->metadata[$rule_set]['type'])) {
                    $info['type'] = self::TYPE_UNSUPPORTED;
                    $info['type_description'] = lang('instrusion_detection_type_unsupported');
                } else {
                    $info['type'] = $this->metadata[$rule_set]['type'];
                    $info['type_description'] = $this->types[$this->metadata[$rule_set]['type']];
                }

                $this->rule_sets[$vendor][$rule_set] = $info;
            }
        }

        // Gather information from configuration file
        //-------------------------------------------

        $file = new File(self::FILE_CONFIG);
        $lines = $file->get_contents_as_array();

        $matches = array();

        foreach ($lines as $line) {
            if (preg_match('/^\s*include\s+\$RULE_PATH\/(\w+)\/(.*)\.rules/', $line, $matches))
                $this->rule_sets[$matches[1]][$matches[2]]['active'] = TRUE;
        }

        $this->rule_sets_loaded = TRUE;
    }

    /**
     * Saves the current rule set lists.
     *
     * @param string $vendor vendor
     * @param array  $list   list of rule sets
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _save_rule_sets($vendor, $list)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Grab current rules and set the new state
        //-----------------------------------------

        $new_list = $this->get_rule_set_details();

        foreach ($list as $rule_set => $state)
            $new_list[$vendor][$rule_set]['active'] = $state;

        // Add the rule sets to the bottom of the configuration
        //-----------------------------------------------------

        $matches = array();
        $new_lines = array();

        $file = new File(self::FILE_CONFIG);
        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            if (preg_match('/^\s*include\s+\$RULE_PATH\/(.*)/', $line, $matches))
                continue;
            else
                $new_lines[] = $line;
        }

        foreach ($new_list as $vendor => $rules) {
            foreach ($rules as $rule => $details) {
                if ($details['active'])
                    $new_lines[] = 'include $RULE_PATH/' . $vendor . '/' . $rule . '.rules';
            }
        }

        $this->rule_sets_loaded = FALSE;

        $file->dump_contents_from_array($new_lines);
    }
}
