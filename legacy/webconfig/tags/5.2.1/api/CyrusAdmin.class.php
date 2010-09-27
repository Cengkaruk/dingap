<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
//  Portions based on sieve-php by Dan Ellis. (http://sieve-php.sourceforge.net/)
//  Modified for webconfig and updated for PHP5 by W.H.Welch
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Cyrus Administrative Interface.
 *
 * @package Api
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("ConfigurationFile.class.php");
require_once('Net/IMAP.php');
require_once('Net/IMAPProtocol.php');
require_once('Net/Socket.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Cyrus Administrative Interface.
 *
 * A limited API to the Cyrus Imap server.
 *
 * @package Api
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class CyrusAdmin extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // F I E L D S
    ///////////////////////////////////////////////////////////////////////////////
    protected $host = '127.0.0.1';
    protected $port = '143';
    protected $is_loaded = false;
    protected $config = null;
    protected $Imap = null;

    const FILE_KOLAB_CONFIG = '/etc/kolab/kolab.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Cyrus Admin constructor.
     *
     * @param string $host string hostname to use. Defaults to 127.0.0.1
     * @param integer $port string Numeric port to user. Defaults to 2000.
     *
     */
    public function __construct($host='127.0.0.1', $port='143')
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        $this->_host = $host;
        $this->_port = $port;
        $this->Imap = new Net_IMAP($this->host,$this->port);

        parent::__construct();
    }

    /**
     * Pear Error Handling Callback function.
     * Throws an Engine Exception in response to a PEAR_Error
     *
     * @access private
     * @param PEAR_Error $error
     * @throws EngineException
     */
    function _ThrowEngineException(PEAR_Error $error)
    {
        throw new EngineException($error->getMessage(),COMMON_WARNING);
    }

    /**
     * Connects and logs into the server.
     *
     * @access public
     */
    function Login()
    {
        $ret = $this->Imap->login('manager',$this->_GetBindPassword(),true,false);

        if ($ret instanceof PEAR_Error)
            $this->_ThrowEngineException($ret);

        return true;

    }
    /**
     * Returns a list of folders for a particular user.
     *
     * @param string $prepend  Optional string to prepend
     *
     * @return array  Array of folders matched
     * @access public
     */
    function GetFolderList($folderMask = null )
    {
        if( $folderMask === null ){
            $folderMask = 'user' . $this->Imap->getHierarchyDelimiter( ) . '*' ;
        }
        //echo "FOLDERLIST: $folderMask\n";
        $ret = $this->Imap->getMailboxes('', $folderMask , false );

        if ($ret instanceof PEAR_Error)
            $this->_ThrowEngineException($ret);

        return $ret;
    }


    /**
     * Returns a list of users.
     *
     * @return array  Array of users found
     * @access public
     */
    function GetUserList()
    {

        $hierarchyDelimiter= $this->Imap->getHierarchyDelimiter();
        $user_base='user' . $hierarchyDelimiter . '%' ;
        $user_list = $this->GetFolderList($user_base);
        $users = array();
        foreach ($user_list as $user) {
            $user_arr=explode($hierarchyDelimiter, $user);
            $users[]=$user_arr[1];
        }
        return $users;
    }

    /**
    * check if the mailbox name exists
    *
    * @param string $user the user to check
    * @param string $foldername the foldername to check
    *
    * @return boolean true on Success/false on Failure
    */

    function FolderExist($user,$foldername)
    {
        $hierarchyDelimiter= $this->Imap->getHierarchyDelimiter();
        $mailbox = 'user' . $hierarchyDelimiter . $user .$hierarchyDelimiter. $foldername ;
        // true means do an exact match
        $ret = $this->Imap->getMailboxes($mailbox,true);

        if ($ret instanceof PEAR_Error)
            $this->_ThrowEngineException($ret);

        if( count( $ret ) > 0 ){
            return true;
        }
        return false;
    }


    /**
     * Creates a mailbox.
     *
     * @param string $user Name of the user to modify
     * @param string $foldername Name of folder to create
     *
     * @return boolen  True on success
     */
    function CreateFolder($user,$foldername)
    {
        $hierarchyDelimiter= $this->Imap->getHierarchyDelimiter();
        $mailbox = 'user' . $hierarchyDelimiter . $user .$hierarchyDelimiter. $foldername ;

        $ret = $this->Imap->createMailbox($mailbox);

        if ($ret instanceof PEAR_Error)
            $this->_ThrowEngineException($ret);

        return true;
    }
    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration file.
     *
     * @access private
     * @return void
     * @throws EngineException
     */

    private function _LoadConfig()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        $configfile = new ConfigurationFile(self::FILE_KOLAB_CONFIG, 'split', ':', 2);

        try {
            $this->config = $configfile->Load();
        } catch (Exception $e) {
            throw new EngineException($e->getMessage(),COMMON_ERROR);
        }

        $this->is_loaded = true;
    }

    /**
     * Returns configured bind password.
     *
     * @access private
     * @return string bind password
     * @throws EngineException
     */

    private function _GetBindPassword()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['bind_pw'];
    }

    function __destruct()
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if ($this->Imap instanceof Net_IMAP)
            $this->Imap->disconnect();

        parent::__destruct();
    }
}
// vi: syntax=php ts=4
?>
