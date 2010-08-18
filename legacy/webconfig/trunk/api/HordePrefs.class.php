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
///////////////////////////////////////////////////////////////////////////////
//
// It is beneficial to keep an LDAP connection open through the life of
// of the object.  This makes the $this->ldaph a bit of a unique
// implementation in the API.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Horde Preferences.
 *
 * @package Api
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once("File.class.php");
require_once("Ldap.class.php");

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Horde Preferences.
 *
 * @package Api
 * @author {@link http://www.whw3.com/ W.H.Welch}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class HordePrefs extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $ldaph = null;
	protected $uid = null;
	protected $dn = null;
	protected $attrs = null;

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Horde Preferences constructor.
	 */

	public function __construct($uid='')
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! empty($uid)){
		    $this->SetUid($uid);
		}
		parent::__construct();

	}
	/**
	 * Set the username
	 *
	 * @param string $uid
	 */
    public function SetUid($uid)
    {
        $this->uid = $uid;
        $this->dn = null;
        $this->_GetUserInfo();

    }

    /**
     * Retrieve preferences for specified Horde appplication
     *
     * @param string $app Application name i.e. horde,imp,ingo,turba
     * @return mixed array|null Array of preference data or null if undefined
     */
    public function Get($app)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->ldaph))
			$this->_GetLdapHandle();

		if (is_null($this->attrs))
            $this->_GetUserInfo();

        $appkey = strtolower($app)."Prefs";
        if (! array_key_exists($appkey,$this->attrs)){
            $prefs = null;
        }else{
            $prefs = array();
            foreach ($this->attrs[$appkey] as $attr){
                $part = explode(':',$attr);
                $prefs[$part[0]] = base64_decode($part[1]);
            }
        }
        return $prefs;
    }

    /**
     * Store preferences for specified Horde appplication
     *
     * @param string $app Application name i.e. horde,imp,ingo,turba
     * @param array $prefs Associative array of preference data
     */
    public function Set($app,$prefs)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (is_null($this->attrs))
            $this->_GetUserInfo();

        if (! is_array($prefs)){
            $this->AddValidationError(LOCALE_LANG_ERRMSG_INVALID_TYPE.': $pref',__METHOD__,__LINE__);
            throw new ValidationException(LOCALE_LANG_ERRMSG_INVALID_TYPE.': $pref');
        }
        $appkey = strtolower($app)."Prefs";
        $attrs = $this->attrs;
        $attrs[$appkey] = array();

        //force a re-read next for time.
        $this->attrs = null;

        foreach ($prefs as $key => $val){
            array_push($attrs[$appkey],$key.':'.base64_encode($val));
        }
        try{
            $this->ldaph->Modify($this->dn,$attrs);
        }catch (Exception $e){
            throw new EngineException($e->getMessage(),COMMON_WARNING);
        }
    }

    /**
     * Remove preferences for specified Horde appplication
     *
     * @param string $app application name i.e. horde,imp,ingo,turba
     */
    public function Clear($app)
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        $this->Set($app,array());
    }
    ///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Creates an LDAP handle.
	 *
	 * @access private
	 * @return void
	 * @throws EngineException
	 */

	private function _GetLdapHandle()
	{
		try {
			$this->ldaph = new Ldap();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

    /**
     * Retrieves user data from LDAP.
     *
     * @access private
     */
    private function _GetUserInfo()
    {
        $this->attrs = null;

		if (is_null($this->ldaph))
			$this->_GetLdapHandle();

		if (is_null($this->uid))
            throw new EngineException(LOCALE_LANG_ERRMSG_NO_MATCH.' $this->uid', COMMON_WARNING);

		$dn = $this->ldaph->GetDnForUid($this->uid);
		$attrs = $this->ldaph->Read($dn);

		if (! $attrs)
			throw new EngineException(USER_LANG_ERRMSG_USER_NOT_FOUND, COMMON_WARNING);

        $this->dn = $dn;
		$this->attrs = array();
        foreach ($attrs as $key=>$attr){
            if (is_numeric($key))
                continue;
            unset($attr['count']);
            $this->attrs[$key] = $attr;
        }
        unset($this->attrs['count']);

    }

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

?>
