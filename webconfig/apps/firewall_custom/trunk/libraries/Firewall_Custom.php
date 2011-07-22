<?php

/**
 * Custom Firewall class.
 *
 * @category   Apps
 * @package    Firewall_Custom
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
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

namespace clearos\apps\firewall_custom;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('firewall_custom');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Engine');
clearos_load_library('base/File');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Custom firewall class.
 *
 * @category   Apps
 * @package    Firewall_Custom
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_custom/
 */

class Firewall_Custom extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    protected $configuration = NULL;
    protected $is_loaded = FALSE;

    const FILE_CONFIG = '/etc/clearos/firewall.d/custom';
    const FILE_FIREWALL_STATE = '/var/clearos/firewall/invalid.state';
    const MOVE_UP = -1;
    const MOVE_DOWN = 1;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Firewall_Custom constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Get array of custom firewall rules.
     *
     * @return array of rules
     * @throws Engine_Exception
     */

    public function get_rules()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $rules = array();

        $index = -1;

        foreach ($this->configuration as $entry) {
            $index++;
            $rule = array ('line' => $index, 'enabled' => 0, 'description' => '');
            if (preg_match('/^\s*$/', $entry, $match)) {
                // Blank line
                continue;
            } else if (preg_match('/^\s*#\s*iptables\s+([^#]*)#(.*)/', $entry, $match)) {
                $rule['entry'] = 'iptables ' . $match[1];
                $rule['description'] = $match[2];
            } else if (preg_match('/^\s*#\s*iptables\s+(.*)/', $entry, $match)) {
                $rule['entry'] = 'iptables ' . $match[1];
            } else if (preg_match('/^\s*#(.*)/', $entry, $match)) {
                // Comment only
                continue;
            } else if (preg_match('/^\s*iptables\s+([^#]*)#(.*)/', $entry, $match)) {
                $rule['entry'] = 'iptables ' . $match[1];
                $rule['enabled'] = 1;
                $rule['description'] = $match[2];
            } else {
                $rule['entry'] = $entry;
                $rule['enabled'] = 1;
            }
            $rules[$index] = $rule;
        }

        return $rules;
    }

    /**
     * Toggle rule status (enable/disable)
     *
     * @param String  $line   line work with
     * @param boolean $status enable/disable
     *
     * @return void 
     * @throws Engine_Exception
     */

    public function toggle_rule($line, $status)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        if ($status) {
            if (preg_match('/^\s*#\s*iptables\s+(.*)/', $this->configuration[$line], $match))
                $this->configuration[$line] = 'iptables ' . $match[1];
            else
                throw new Engine_Exception(lang('firewall_custom_something_wrong'), COMMON_WARNING);
            
        } else {
            $this->configuration[$line] = '# ' . $this->configuration[$line]; 
        }

        $this->_save_configuration();
    }

    /**
     * Get rule
     *
     * @param String $line the line
     *
     * @return String
     * @throws Engine_Exception
     */

    public function get_rule($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rules = $this->get_rules();

        return $rules[$line];
    }

    /**
     * Add new rule
     *
     * @param String  $entry       line entry
     * @param String  $description rule description
     * @param Boolean $enabled     enabled/disabled
     * @param int     $priority    rule priority
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_rule($entry, $description, $enabled, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        if (!isset($entry) || $entry == '')
            throw new Engine_Exception(lang('firewall_custom_no_rule'), CLEAROS_WARNING);

        if (!preg_match("/^iptables.*$/", $entry))
            throw new Engine_Exception(lang('firewall_custom_invalid_rule'), CLEAROS_WARNING);

        if ($priority > 0)
            array_unshift(
                $this->configuration,
                ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : "")
            );
        else
            array_push(
                $this->configuration,
                ($enabled ? "" : "# ") . $entry . (isset($description) ? " # " . $description : "")
            );

        // Rule has been added, but it might be in front of top-header comments
        if ($priority > 0) {
            $linenumber = 0;

            foreach ($this->configuration as $entry) {
                // Line 0 is our new addition
                if ($linenumber == 0) {
                    $swap = $entry;
                } else if (preg_match('/^\s*$/', $entry)) {
                    // Blank line
                    $this->configuration[$linenumber - 1] = $this->configuration[$linenumber];
                    $this->configuration[$linenumber] = $swap;
                } else if (preg_match('/^\s*iptables.*/', $entry)) {
                    // Not a comment...break;
                    break;
                } else if (!preg_match('/^\s*#\s*iptables.*/', $entry)) {
                    // Comment
                    $this->configuration[$linenumber - 1] = $this->configuration[$linenumber];
                        $this->configuration[$linenumber] = $swap;
                }

                $linenumber++;
            }
        }

        $this->_save_configuration();
    }

    /**
     * Update/Edit rule
     *
     * @param String  $line        line
     * @param String  $entry       new line
     * @param String  $description rule description
     * @param Boolean $enabled     enabled/disabled
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update_rule($line, $entry, $description, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $replace = $enabled ? "" : "# " . $entry . isset($description) ? " # " . $description : "";
        $this->configuration[$line] = $replace;
        $this->_save_configuration();
    }

    /**
     * Delete rule
     *
     * @param String $line line to delete
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_rule($line)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        unset($this->configuration[$line]);

        $this->_save_configuration();
    }

    /**
     * Move rule up in table
     *
     * @param String $line      line to delete
     * @param int    $direction direction to move up (+1) or down (-1)
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_rule_priority($line, $direction)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $swap = $this->configuration[$line + $direction];
        $counter = 1;

        while (!preg_match("/\s*iptables.*/", $swap) && !preg_match("/\s*#\s*iptables.*/", $swap)) {
            $counter++;
            $swap = $this->configuration[$line + $counter * $direction];
        }

        $this->configuration[$line + $counter * $direction] = $this->configuration[$line];
        $this->configuration[$line] = $swap;
        $this->_save_configuration();
    }

    /**
     * Determine if firewall restart is required
     *
     * @return boolean
     * @throws Engine_Exception
     */

    public function is_firewall_restart_required()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = new File(self::FILE_CONFIG);
        $state = new File(self::FILE_FIREWALL_STATE); 

        if ($config->last_modified() > $state->last_modified())
            return TRUE;
        else
            return FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Load configuration file
     *
     * @return void;
     */

    function _load_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->configuration = array();

        try {
            $file = new File(self::FILE_CONFIG);
            $this->configuration = $file->get_contents_as_array();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Save configuration file
     *
     * @return void;
     */

    function _save_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete any old temp file lying around
        //--------------------------------------

        $newfile = new File(self::FILE_CONFIG . '.cctmp');

        if ($newfile->exists())
            $newfile->delete();

        // Create temp file
        //-----------------

        $newfile->create('root', 'root', '0755');

        // Write out the file
        //-------------------

        $newfile->add_lines(implode("\n", $this->configuration) . "\n");

        // Copy the new config over the old config
        //----------------------------------------

        $newfile->move_to(self::FILE_CONFIG);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation iptables rule.
     *
     * @param string $iptables iptables
     *
     * @return mixed void if iptables is valid, errmsg otherwise
     */

    public function validate_iptables($iptables)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^iptables.*/", $iptables))
            return lang('firewall_custom_must_start_iptables');
    }

    /**
     * Validation routine for description.
     *
     * @param int $description description
     *
     * @return mixed void if description is valid, errmsg otherwise
     */

    public function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/.*/", $iptables))
            return lang('firewall_custom_description_is_invalid');
    }
}
