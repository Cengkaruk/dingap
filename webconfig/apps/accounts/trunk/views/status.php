<?php

/**
 * Accounts initialization view.
 *
 * @category   ClearOS
 * @package    Accounts
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/accounts/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////
// FIXME: translate

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('accounts');

///////////////////////////////////////////////////////////////////////////////
// Accounts Setup
///////////////////////////////////////////////////////////////////////////////

$ad_logo = clearos_app_htdocs('accounts') . '/ad_logo.png';
$openldap_logo = clearos_app_htdocs('accounts') . '/openldap_logo.gif';

$ad_installed_action = anchor_custom('/app/active_directory', lang('accounts_configure_active_directory_connector'));
$ad_marketplace_action = anchor_custom('/app/marketplace/view/active_directory', lang('accounts_install_active_directory_connector'));
$openldap_installed_action = anchor_javascript('initialize_openldap', lang('accounts_initialize_builtin_directory'));
$openldap_marketplace_action = anchor_custom('/app/marketplace/view/openldap_directory', lang('accounts_install_builtin_directory'));
$directory_installed_action = anchor_custom('/app/directory_server', lang('accounts_configure_builtin_directory'));

echo "<input id='accounts_status_lock' value='off' type='hidden'>\n";

echo "<div id='accounts_configuration_widget'>";

echo form_open('accounts/info');
echo form_header(lang('accounts_account_manager_configuration'));
echo "
<tr>
    <td align='center' width='250'><img src='$ad_logo' alt='Active Directory Connector'><br><br></td>
    <td>
        <p>With the Active Directory Connector, you can use users and groups defined in
        your Microsoft AD system.</p>
        <div id='ad_installed'>$ad_installed_action</div>
        <div id='ad_marketplace'>$ad_marketplace_action</div>
    </td>
</tr>
<tr>
    <td align='center'><img src='$openldap_logo' alt='OpenLDAP'><br><br></td>
    <td>
        <p>The native Directory Server provides the most flexibility
        when it comes to supporting third party apps.</p>
        <div id='directory_server_installed'>$directory_installed_action</div>
        <div id='openldap_installed'>$openldap_installed_action</div>
        <div id='openldap_marketplace'>$openldap_marketplace_action</div>
    </td>
</tr>
";
echo form_footer();
echo form_close();

echo "</div>";


///////////////////////////////////////////////////////////////////////////////
// Accounts Status
///////////////////////////////////////////////////////////////////////////////

echo "<div id='accounts_status_widget'>";
echo infobox_highlight(lang('accounts_account_manager_status'), '<div id="accounts_status"></div>');
echo "</div>";
