<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/Squid.class.php');
require_once('../../api/UserManager.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-proxy-policy.png', WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$squid = new Squid();
$activetab = 'tab_list_acl';
# Jump to configure time
if (isset($_REQUEST['addtime']))
	$activetab = 'tab_add_time';

try {
	# Bump priority
	if (isset($_REQUEST['priority'])) {
		$squid->BumpTimeAclPriority($_REQUEST['aclname'], $_REQUEST['priority']);
		$squid->Reset();
	}
	# Add new ACL
	if (isset($_POST['AddAcl'])) {
		try {
			$squid->SetTimeAcl(
				$_POST['aclname'], $_POST['type'], $_POST['time'], $_POST['logic'],
				$_POST['addusers'], explode("\n", $_POST['addips']), explode("\n", $_POST['addmacs'])
			);
			# Reset form fields
			unset($_POST);
			$activetab = 'tab_list_acl';
			$squid->Reset();
		} catch (Exception $e) {
			$activetab = 'tab_add_acl';
			WebDialogWarning($e->GetMessage());
		}
	}

	# Edit ACL
	if (isset($_POST['EditAcl'])) {
		try {
			# Check one of the form varibles to see if we are updating
			if ($_POST['type']) {
				$squid->SetTimeAcl(
					$_POST['aclname'], $_POST['type'], $_POST['time'], $_POST['logic'],
					$_POST['addusers'], explode("\n", $_POST['addips']), explode("\n", $_POST['addmacs']), true
				);
				unset($_POST);
				$activetab = 'tab_list_acl';
				$squid->Reset();
			} else {
				$activetab = 'tab_add_acl';
			}
		} catch (Exception $e) {
			$activetab = 'tab_add_acl';
			WebDialogWarning($e->GetMessage());
		}
	}
	# Delete ACL
	if (isset($_POST['DeleteAcl'])) {
		try {
			if (isset($_POST['Cancel'])) {
				# Do nothing
			} else if ($_POST['Confirm']) {
				try {
					$squid->DeleteTimeAcl($_POST['DeleteAcl']);
					$squid->Reset();
				} catch (Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			} else {
				DisplayDeleteAcl(key($_POST['DeleteAcl']));
			}
			$activetab = 'tab_list_acl';
		} catch (Exception $e) {
			$activetab = 'tab_list_acl';
			WebDialogWarning($e->GetMessage());
		}
	}

	# Add new time period
	if (isset($_POST['AddTime'])) {
		try {
			$squid->SetTimeDefinition($_POST['newname'], $_POST['newdow'], $_POST['newstart'], $_POST['newend']);
			$activetab = 'tab_add_time';
			unset($_POST);
			$squid->Reset();
		} catch (Exception $e) {
			$activetab = 'tab_add_time';
			WebDialogWarning($e->GetMessage());
		}
	}
	# Update time period
	if (isset($_POST['UpdateTime'])) {
		try {
			$name = key($_POST['UpdateTime']);
			$squid->SetTimeDefinition($name, $_POST['dow'][$name], $_POST['start'][$name], $_POST['end'][$name], true);
			$activetab = 'tab_add_time';
			unset($_POST);
			$squid->Reset();
		} catch (Exception $e) {
			$activetab = 'tab_add_time';
			WebDialogWarning($e->GetMessage());
		}
	}
	# Delete time period
	if (isset($_POST['DeleteTime'])) {
		try {
			if (isset($_POST['Cancel'])) {
				# Do nothing
			} else if ($_POST['Confirm']) {
				try {
					$squid->DeleteTimeDefinition($_POST['DeleteTime']);
					$squid->Reset();
				} catch (Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			} else {
				DisplayDeleteTime(key($_POST['DeleteTime']));
			}
			$activetab = 'tab_add_time';
		} catch (Exception $e) {
			$activetab = 'tab_add_time';
			WebDialogWarning($e->GetMessage());
		}
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayConfig($activetab);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig($activetab)
{
	$tabinfo['tab_list_acl']['title'] = WEB_LANG_LIST_ACL;
	$tabinfo['tab_list_acl']['contents'] = GetDisplayAclList();
	$tabinfo['tab_add_acl']['title'] = WEB_LANG_ADD_EDIT_ACL;
	$tabinfo['tab_add_acl']['contents'] = GetDisplayAddEditAcl();
	$tabinfo['tab_add_time']['title'] = WEB_LANG_ADD_EDIT_TIME;
	$tabinfo['tab_add_time']['contents'] = GetDisplayAddEditTime();
	WebTab(WEB_LANG_PAGE_TITLE, $tabinfo, $activetab);
}

///////////////////////////////////////////////////////////////////////////////
//
// GetDisplayAclList()
//
///////////////////////////////////////////////////////////////////////////////

function GetDisplayAclList()
{
	global $squid;
	$list = '';
	$index = 0;
	$acls = $squid->GetAclList();
	$type_options = $squid->GetAccessTypeArray();
	foreach ($acls as $acl) {
		# Reset loop variables
		$name = $acl['name'];
		$user_acl = false;
		$acllist = '';
		if (strlen($acl['users']) > 0) {
			$acllist = ereg_replace(" ", "<br>", $acl['users']);
			$ident_tag = SQUID_LANG_USER;
		} else if (strlen($acl['ips']) > 0) {
			$acllist = ereg_replace(" ", "<br>", $acl['ips']);
			$ident_tag = SQUID_LANG_IP;
		} else if (strlen($acl['macs']) > 0) {
			$acllist = ereg_replace(" ", "<br>", $acl['macs']);
			$ident_tag = SQUID_LANG_MAC;
		}
		$rowclass = 'rowenabled' . (($index % 2) ? 'alt' : '');
		if (count($acls) > 1 && $index != 0)
			$priority = "<a href='proxy-policy.php?aclname=$name&amp;priority=-1'>" . WEBCONFIG_ICON_UP . "</a>";
		if (count($acls) > 1 && $index < (count($acls) - 1))
			$priority .= "<a href='proxy-policy.php?aclname=$name&amp;priority=1'>" . WEBCONFIG_ICON_DOWN . "</a>";
		$list .= "
		  <tr class='$rowclass'>
            <td nowrap>" . WEBCONFIG_ICON_ENABLED . "</td>
            <td nowrap>$name</td>
			<td nowrap id='position-$index'>" . $type_options[$acl['type']] . "</td>
			<td nowrap>" .
			  ($acl['logic'] ? WEB_LANG_WITHIN : WEB_LANG_OUTSIDE) . " " . str_replace('pcntime-', '', str_replace('!', '', $acl['time'])) . "
			</td>
            <td nowrap align='center'>$priority</td>
			<td nowrap>" . WebButtonEdit("EditAcl[$name]") . WebButtonDelete("DeleteAcl[$name]") . "
          </tr>\n
		";
		$index++;
	}

	if ($list == '')
		$list = "<tr><td colspan='7' align='center'>" . WEB_LANG_NO_RECORDS_FOUND . "</td></tr>";
	$contents = "
	<form action='proxy-policy.php' method='post' enctype='multipart/form-data'>
	  <div id='container' style='width:100%;'>
	    <table cellspacing='0' cellpadding='5'  border='0' class='tablebody'  style='width:100%;'>
          <tr>
	  	    <td class='mytableheader'>&#160;</td>
            <td class='mytableheader' nowrap>" . WEB_LANG_NAME . "</td>
	  	    <td class='mytableheader' nowrap>" . WEB_LANG_RULE_TYPE . "</td>
            <td class='mytableheader' nowrap>" . WEB_LANG_TIME_RULE . "</td>
            <td class='mytableheader' align='center' nowrap>" . WEB_LANG_PRIORITY . "</td>
		    <td class='mytableheader'>&#160;</td>
          </tr>
          $list
        </table>
	  </div>
    </form>
	
	";

	return $contents;

}

///////////////////////////////////////////////////////////////////////////////
//
// GetDisplayAddEditAcl()
//
///////////////////////////////////////////////////////////////////////////////

function GetDisplayAddEditAcl()
{
	global $squid;

	$usermanager = new UserManager();
	$time_options = '';
	$user_options = '';
	$contents = '';

	try {
		$userlist = $usermanager->GetAllUsers(UserManager::TYPE_PROXY);
   		$servicelist = $usermanager->GetInstalledServices();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}
	try {
		if (key($_POST['EditAcl'])) {
			$found = false;
			$form_name = key($_POST['EditAcl']);
			$acls = $squid->GetAclList();
			foreach ($acls as $acl) {
				if (key($_POST['EditAcl']) != $acl['name'])
					continue;
				$found = true;
				$form_users = explode(" ", $acl['users']);
				$form_ips = $acl['ips'];
				$form_macs = $acl['macs'];
				$form_type = $acl['type'];
				$form_logic = $acl['logic'];
				$form_time = $acl['time'];
				$form_ident = $acl['ident'];
				break;
			}
			$disablename = 'readonly';
			if (!$found)
				throw new EngineException(LOCALE_LANG_ERRMSG_WEIRD, COMMON_WARNING);
		}
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	# Override preset values
	if (isset($_POST['aclname']))
		$form_name = $_POST['aclname'];
	if (isset($_POST['addusers']))
		$form_users = $_POST['addusers'];
	if (isset($_POST['addips']))
		$form_ips = $_POST['addips'];
	if (isset($_POST['addmacs']))
		$form_macs = $_POST['addmacs'];
	if (isset($_POST['type']))
		$form_type = $_POST['type'];
	else if (! isset($form_type))
		$form_type = 'allow';
	if (isset($_POST['logic']))
		$form_logic = $_POST['logic'];
	if (isset($_POST['time']))
		$form_time = $_POST['time'];
	if (isset($_POST['ident']))
		$form_ident = $_POST['ident'];
	else if (! isset($form_ident))
		$form_ident = 'proxy_auth';

	# Populate users list on invalid data
	foreach ($userlist as $user) {
		$selected = (in_array($user, $form_users)) ? "selected" : "";
		$user_options .= "<option value='$user' $selected>$user</option>\n";	
	}

	if (isset($form_ips))
		$ips = ereg_replace(' ', "\n", $form_ips);
	if (isset($form_macs))
		$macs = ereg_replace(' ', "\n", $form_macs);

	$acls = $squid->GetTimeDefinitionList();
	foreach ($acls as $acl) {
		if ($form_time == 'pcntime-' . $acl['name'])
			$time_options .= "<option value='" . $acl['name'] . "' SELECTED>" . $acl['name'] . "</option>\n";
		else
			$time_options .= "<option value='" . $acl['name'] . "'>" . $acl['name'] . "</option>\n";
	}
	$ident_types = $squid->GetIdentificationTypeArray();
	foreach ($ident_types as $key => $value) {
		if ($key == $form_ident)
			$ident_options .= "<option value='$key' SELECTED>$value</option>\n";
		else
			$ident_options .= "<option value='$key'>$value</option>\n";
	}
	
	if ($time_options == '')
		$link = ' - ' . WebUrlJump('proxy-policy.php?addtime=1', LOCALE_LANG_CONFIGURE);
	else
		$link = '';
	# If user authentication is enabled, show user table
	if ($squid->GetAuthenticationState())
		$userauth = "<select style='width: 200px;' multiple size='6' name='addusers[]'>$user_options</select>";
	else
		$userauth = WEB_LANG_AUTH_DISABLED . ' - ' . WebUrlJump('proxy.php', LOCALE_LANG_CONFIGURE);
	$contents .= "
	<form action='proxy-policy.php' method='post' enctype='multipart/form-data'>
	  <table cellspacing='0' cellpadding='5' border='0' class='tablebody'>
		<tr>
		  <td class='mytablesubheader' width='200' nowrap>" . WEB_LANG_NAME . "</td>
		  <td><input type='text' name='aclname' value='$form_name' style='width:150px' $disablename /></td>
		</tr>
        <tr>
          <td class='mytablesubheader'>" . WEB_LANG_RULE_TYPE . "</td>
          <td nowrap>" .
            WebDropDownHash('type', $form_type, $squid->GetAccessTypeArray()) . "
          </td>
        </tr>
        <tr>
          <td class='mytablesubheader'>" . WEB_LANG_TIME_RULE . "</td>
          <td nowrap><select name='time'>$time_options</select>$link</td>
        </tr>
        <tr>
          <td class='mytablesubheader'>" . WEB_LANG_TIME_RESTRICTION . "</td>
          <td nowrap>" .
            WebDropDownHash('logic', $form_logic, Array(0 => WEB_LANG_OUTSIDE_RANGE, 1 => WEB_LANG_WITHIN_RANGE)) . "
          </td>
        </tr>
        <tr>
          <td class='mytablesubheader'>" . WEB_LANG_ID_METHOD . "</td>
          <td nowrap><select name='ident' id='ident' onchange='toggleIdOption()'>$ident_options</select></td>
        </tr>
        <tr id='byuser'>
          <td class='mytablesubheader' valign='top'>" . WEB_LANG_APPLY_USERS . "</td>
          <td nowrap>$userauth</td>
        </tr>
        <tr id='byip'>
          <td class='mytablesubheader' valign='top'>" . WEB_LANG_APPLY_IPS . "</td>
          <td nowrap valign='top'>
		    <textarea style='width: 180px;' rows='6' name='addips' />$ips</textarea>
			<br /><font class='small'>" . WEB_LANG_NOTE_ONEPERLINE_IP . "</font>
		  </td>
        </tr>
        <tr id='bymac'>
          <td class='mytablesubheader' valign='top'>" . WEB_LANG_APPLY_MACS . "</td>
          <td nowrap valign='top'>
		    <textarea style='width: 180px;' rows='6' name='addmacs' />$macs</textarea>
			<br /><font class='small'>" . WEB_LANG_NOTE_ONEPERLINE_MAC . "</font>
		  </td>
        </tr>
        <tr>
          <td class='mytablesubheader' nowrap>&#160;</td>
          <td valign='top'>". (key($_POST['EditAcl']) ? WebButtonUpdate("EditAcl[$form_name]") : WebButtonAdd('AddAcl', Array('id' => 'AddAcl'))) . "</td>
        </tr>
	  </table>
	</form>
	";
	if ($time_options == '')
		$contents .= "<script language=\"JavaScript\">\nif (window.oAddAcl)\n\toAddAcl.set('disabled', true);\n</script>";
	if ($form_ident != 'proxy_auth')
		$contents .= "<script language=\"JavaScript\">\nhide('byuser')\n</script>";
	if ($form_ident != 'src')
		$contents .= "<script language=\"JavaScript\">\nhide('byip')\n</script>";
	if ($form_ident != 'arp')
		$contents .= "<script language=\"JavaScript\">\nhide('bymac')\n</script>";
		
	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// GetDisplayAddEditTime()
//
///////////////////////////////////////////////////////////////////////////////

function GetDisplayAddEditTime()
{
	global $squid;

	try {
		$acls = $squid->GetTimeDefinitionList();
		$dow_list = $squid->GetDayOfWeekArray();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$data = '';
	$list = '';
	$index = 0;
	$contents = '';
	$dow_header = '';

	foreach ($dow_list as $key => $day)
		$dow_header .= "<td align='center' nowrap>" . substr($day, 0, 1) . "</td>\n";

	foreach ($acls as $acl) {
		# Reset loop variables
		$dow = '';
		$name = $acl['name'];

		foreach ($dow_list as $key => $day) {
			$dow .= "<td align='center' width='10' nowrap>";
				if (in_array($key, $acl['dow']))
					$dow .= "<input type='checkbox' name='dow[$name][$key]' value='$key' CHECKED />\n";
				else
					$dow .= "<input type='checkbox' name='dow[$name][$key]' value='$key' />\n";
			$dow .= "</td>\n";
		}

		$rowclass = 'rowenabled' . (($index % 2) ? 'alt' : '');
		$index++;

		$list .= "
		  <tr class='$rowclass'>
            <td nowrap>" . $acl['name'] . "</td>
			$dow
            <td nowrap>
              <select name='start[$name][h]' style='width:42px'>" . WebNumberDropDown(23, $acl['start']['h'], true) . "</select>&#160;:&#160;" .
              WebDropDownArray("start[$name][m]", $acl['start']['m'], Array('00', '15', '30', '45'), 42) . "&#160;-&#160;
              <select name='end[$name][h]' style='width:42px'>" . WebNumberDropDown(24, $acl['end']['h'], true) . "</select>&#160;:&#160;" .
              WebDropDownArray("end[$name][m]", $acl['end']['m'], Array('00', '15', '30', '45'), 42) . "
            </td>
			<td nowrap>" . WebButtonUpdate("UpdateTime[$name]") . WebButtonDelete("DeleteTime[$name]") . "
          </tr>
		";
	}

	$contents = "
	<form action='proxy-policy.php' method='post' enctype='multipart/form-data'>
	  <table cellspacing='0' cellpadding='3' border='0' class='tablebody' style='width:100%;'>
        <tr class='mytableheader' style='width:560px;'>
          <td nowrap>" . WEB_LANG_NAME . "</td>
		  $dow_header
          <td nowrap>" . WEB_LANG_TIME_OF_DAY . "</td>
		  <td>&#160;</td>
        </tr>
        $list
        <tr style='width:100%;'>
		  <td>
            <input type='text' name='newname' value='" . $_POST['name'] . "' style='width:150px' />
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[S]' value='S' " . (isset($_POST['newdow']['S']) ? 'CHECKED' : '') . " />
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[M]' value='M' " . (isset($_POST['newdow']['M']) ? 'CHECKED' : '') . "/>
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[T]' value='T' " . (isset($_POST['newdow']['T']) ? 'CHECKED' : '') . "/>
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[W]' value='W' " . (isset($_POST['newdow']['W']) ? 'CHECKED' : '') . "/>
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[H]' value='H' " . (isset($_POST['newdow']['H']) ? 'CHECKED' : '') . "/>
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[F]' value='F' " . (isset($_POST['newdow']['F']) ? 'CHECKED' : '') . "/>
          </td>
          <td align='center'>
            <input type='checkbox' name='newdow[A]' value='A' " . (isset($_POST['newdow']['A']) ? 'CHECKED' : '') . "/>
          </td>
		  <td>
            <select name='newstart[h]' style='width:42px'>" .
			WebNumberDropDown(23, (isset($_POST['newstart']['h']) ? $_POST['newstart']['h'] : 0), true) . "
			</select>&#160;:
            " . WebDropDownArray('newstart[m]', (isset($_POST['newstart']['m']) ? $_POST['newstart']['m'] : 0),
			Array('00', '15', '30', '45'), 42) . "&#160;-&#160;
            <select name='newend[h]' style='width: 42px'>" .
			WebNumberDropDown(24, (isset($_POST['newend']['h']) ? $_POST['newend']['h'] : 0), true) . "
			</select>&#160;:
            " . WebDropDownArray('newend[m]', (isset($_POST['newend']['m']) ? $_POST['newend']['m'] : 0),
			Array('00', '15', '30', '45'), 42) . "
          </td>
          <td valign='top'>". WebButtonAdd('AddTime') . "</td>
        </tr>
      </table>
    </form>
	";

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteAcl()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteAcl($name)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='DeleteAcl' value='$name'>
		  <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE_DELETE . "<b> <i>" . $name . "</i></b>?</p>" .
		  WebButtonDelete("Confirm") . " " . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteTime()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteTime($name)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='DeleteTime' value='$name'>
		  <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE_DELETE . "<b> <i>" . $name . "</i></b>?</p>" .
		  WebButtonDelete("Confirm") . " " . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}

// vi: syntax=php ts=4
?>
