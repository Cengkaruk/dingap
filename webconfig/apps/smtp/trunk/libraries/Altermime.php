<?php

/**
 * Altermime class
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
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

namespace clearos\apps\smtp;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('smtp');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\File as File;
use \clearos\apps\base\Software as Software;

clearos_load_library('base/File');
clearos_load_library('base/Software');

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
 * Altermime class
 *
 * @category   Apps
 * @package    SMTP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/smtp/
 */

class Altermime extends Software
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_DISCLAIMER_PLAINTEXT = '/etc/altermime/disclaimer.txt';
    const FILE_DISCLAIMER_STATE = '/etc/altermime/disclaimer.state';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Altermime constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('altermime');
    }

    /**
     * Returns the text of the e-mail disclaimer.
     *
     * @return string disclaimer text
     * @throws Engine_Exception
     */

    public function get_disclaimer_plaintext()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_DISCLAIMER_PLAINTEXT);

        if ($file->exists())
            return htmlentities($file->get_contents(), ENT_COMPAT, 'UTF-8');
        else
            return '';
    }

    /**
     * Returns the state of the e-mail disclaimer service.
     *
     * @return boolean state of the e-mail string disclaimer text
     * @throws Engine_Exception
     */

    public function get_disclaimer_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_DISCLAIMER_STATE);

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Sets the text of the e-mail disclaimer.
     *
     * @param string $text e-mail disclaimer
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_disclaimer_plaintext($text)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_disclaimer_plaintext($text));

        $file = new File(self::FILE_DISCLAIMER_PLAINTEXT);

        if ($file->exists())
            $file->delete();

        $text = trim($text);

        if (! empty($text)) {
            // Remove carriage returns (DOS format)
            $text = preg_replace("/\r/", '', $text);
            $file->create('root', 'root', '0644');
            $file->add_lines("$text\n");
        }
    }

    /**
     * Sets the state of the e-mail disclaimer service.
     *
     * @param boolean $state state of the e-mail disclaimer service
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_disclaimer_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_state($state));

        $file = new File(self::FILE_DISCLAIMER_STATE);
        $exists = $file->exists();

        if ($state && !$exists)
            $file->create("root", "root", "0644");
        else if (!$state && $exists)
            $file->delete();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for mail disclaimer text.
     *
     * @param string $text plaintext mail disclaimer
     *
     * @return boolean error message if disclaimer is invalid
     */

    public function validate_disclaimer_plaintext($text)
    {
        clearos_profile(__METHOD__, __LINE__);

        $plaintext = strip_tags(html_entity_decode($text));

        if ($plaintext != $text)
            return lang('smtp_mail_disclaimer_is_invalid');
    }

    /**
     * Validation routine for state.
     *
     * @param boolean $state state of disclaimer engine
     *
     * @return boolean error message if state is invalid
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // if FIXME
        // return lang('smtp_mail_disclaimer_state_is_invalid');
    }
}
