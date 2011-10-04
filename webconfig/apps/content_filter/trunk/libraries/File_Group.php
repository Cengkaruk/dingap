<?php

/**
 * DansGuardian file group handler class.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
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

namespace clearos\apps\content_filter;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('content_filter');

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
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * DansGuardian file group handler class.
 *
 * @category   Apps
 * @package    Content_Filter
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2005-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/content_filter/
 */

class File_Group extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $group = NULL;
    protected $filename = NULL;
    protected $file_owner = NULL;
    protected $file_group = NULL;
    protected $file_mode = NULL;
    protected $demark = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Group manager constructor.
     *
     * @param string $group      group name
     * @param string $filename   filename for storing group information
     * @param string $file_owner file owner
     * @param string $file_group file group
     * @param string $file_mode  file mode
     * @param string $demark     demarker
     */

    public function __construct($group, $filename, $file_owner = "root", $file_group = "root", $file_mode = "0644", $demark = "#")
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->group = $group;
        $this->filename = $filename;
        $this->file_owner = $file_owner;
        $this->file_group = $file_group;
        $this->file_mode = $file_mode;
        $this->demark = $demark;
    }

    /**
     * Adds a group.
     *
     * @param array $entries list of entries
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add($entries)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->filename);

        if (!$file->exists())
            $file->create($this->file_owner, $this->file_group, $this->file_mode);

        if ($this->exists())
            throw new Engine_Exception(lang('content_filter_group_configuration_already_exists'));

        $filedata = sprintf("%s Webconfig Group Header: %s\n", $this->demark, $this->group);

        if ($entries) {
            foreach ($entries as $entry)
            $filedata .= "$entry\n";
        }

        $filedata .= sprintf("%s Webconfig Group Footer: %s\n", $this->demark, $this->group);

        $file->add_lines($filedata);
    }

    /**
     * Deletes a group.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->filename);

        if (!$this->exists())
            throw new Engine_Exception(lang('content_filter_group_configuration_does_not_exist'));

        $header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
        $footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

        $match = $file->replace_lines_between("/.*/", "", $header, $footer);

        if (!$match)
            throw new Engine_Exception(lang('content_filter_group_configuration_failed'));

        $file->delete_lines($header);
        $file->delete_lines($footer);
    }

    /**
     * Checks for existence of group.
     *
     * @return boolean TRUE if group exists
     * @throws Engine_Exception
     */

    public function exists()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($this->filename);

        if (!$file->exists())
            return FALSE;

        $search = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);

        try {
            $match = $file->lookup_line($search);
        } catch (File_No_Match_Exception $e) {
            return FALSE;
        }

        if ($match)
            return TRUE;

        return FALSE;
    }

    /**
     * Checks to see if entry exists in group.
     *
     * @param string $entry entry
     *
     * @return boolean TRUE if entry exists
     * @throws Engine_Exception
     */

    public function entry_exists($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->exists())
            throw new Engine_Exception(lang('content_filter_group_configuration_does_not_exist'));

        $file = new File($this->filename);

        $header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
        $footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

        try {
            $match = $file->lookup_value_between("/^$entry\$/", $header, $footer);
        } catch (File_No_Match_Exception $e) {
            return FALSE;
        }

        if ($match)
            return TRUE;

        return FALSE;
    }

    /**
     * Returns list of group entries.
     *
     * @return array list of entries
     * @throws Engine_Exception
     */

    public function get_entries()
    {
        clearos_profile(__METHOD__, __LINE__);

        $entries = array();

        if (!$this->exists())
            throw new Engine_Exception(lang('content_filter_group_configuration_does_not_exist'));

        $file = new File($this->filename);

        $contents = $file->get_contents();

        $lines = explode("\n", $contents);

        $header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
        $footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

        foreach ($lines as $line) {
            if (preg_match($header, $line))
                break;

            array_shift($lines);
        }

        array_shift($lines);

        foreach ($lines as $line) {
            if (preg_match($footer, $line))
                break;

            $entries[] = $line;
        }

        return $entries;
    }

    /**
     * Adds a group member.
     *
     * @param string $entry entry
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add_entry($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->Exists())
            throw new Engine_Exception(FILEGROUP_LANG_ERRMSG_NOT_EXISTS, COMMON_ERROR);

        if ($this->entry_exists($entry))
            throw new Engine_Exception(FILEGROUP_LANG_ERRMSG_ENTRY_EXISTS, COMMON_ERROR);

        $footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

        $file = new File($this->filename);

        $file->PrependLines($footer, $entry . "\n");
    }


    /**
     * Deletes a group member.
     *
     * @param string $entry entry
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_entry($entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->exists())
            throw new Engine_Exception(lang('content_filter_group_configuration_does_not_exist'));

        if (!$this->entry_exists($entry))
            throw new Engine_Exception(lang('content_filter_group_configuration_entry_does_not_exist'));

        $file = new File($this->filename);

        $header = sprintf("/^%s Webconfig Group Header: %s\$/", $this->demark, $this->group);
        $footer = sprintf("/^%s Webconfig Group Footer: %s\$/", $this->demark, $this->group);

        $match = $file->replace_lines_between("/^$entry\$/", "", $header, $footer);
    }
}
