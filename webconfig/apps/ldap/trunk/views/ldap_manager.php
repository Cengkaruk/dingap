<?php

/**
 * OpenLDAP view.
 *
 * @category   ClearOS
 * @package    OpenLDAP
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/

///////////////////////////////////////////////////////////////////////////////
// Common mode field
///////////////////////////////////////////////////////////////////////////////

$mode = 'master';
$read_only = FALSE;

/*
if (empty($mode)) {
    $read_only = FALSE;
} else {
    $read_only = TRUE;
}
*/

$ids['input'] = 'mode';

echo form_open('directory_manager');
echo form_header(lang('directory_manager_mode'));

echo form_fieldset(lang('base_general_settings'));
echo field_dropdown('mode', $modes, $mode, lang('directory_manager_mode'), $read_only, $ids);
echo form_fieldset_close();

///////////////////////////////////////////////////////////////////////////////
// OpenLDAP Master
///////////////////////////////////////////////////////////////////////////////

echo "<div id='master' class='mode_form'>";

echo form_fieldset(lang('directory_manager_master'));
echo field_input('domain', $domain, lang('directory_manager_base_domain'));
echo field_dropdown('publish_policy', $publish_policies, $publish_policy, lang('directory_manager_publish_policy'));
echo form_fieldset_close();

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// OpenLDAP Slave
///////////////////////////////////////////////////////////////////////////////

echo "<div id='slave' class='mode_form'>";

echo form_fieldset(lang('directory_manager_slave'));
echo field_input('master_hostname', $master_hostname, lang('directory_manager_master_directory_hostname'));
echo field_input('master_password', $master_password, lang('directory_manager_master_directory_password'));
echo field_dropdown('publish_policy', $publish_policies, $publish_policy, lang('directory_manager_publish_policy'));
echo form_fieldset_close();

echo "</div>";

///////////////////////////////////////////////////////////////////////////////
// Common submit button
///////////////////////////////////////////////////////////////////////////////

echo button_set(
    array( form_submit_update('submit', 'high') )
);

///////////////////////////////////////////////////////////////////////////////
// Close form
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
