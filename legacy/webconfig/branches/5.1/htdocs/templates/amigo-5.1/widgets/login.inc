<?php

$warninghtml = empty($warning) ? "<p>&nbsp; </p>" : "<p class='alert'><b>$warning</b></p>";

$buttons = WebButtonLogin("Login");
if (WEBCONFIG_GUI) $buttons .= " " . WebButton("ExitConsole",
	LOCALE_LANG_EXITCONSOLE, WEBCONFIG_ICON_CANCEL);

WebFormOpen();
echo "
<br>
<table align='center' cellspacing='0' cellpadding='3' width='400' border='0' id='clearos_login_box'>
	<tr>
		<td id='clearos_login_header'>" . LOCALE_LANG_LOGIN . "</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td nowrap align='right'><b>" . LOCALE_LANG_USERNAME . "</b></td>
		<td>$login</td>
	</tr>
	<tr>
		<td nowrap align='right'><b>" . LOCALE_LANG_PASSWORD . "</b></td>
		<td><input type='password' name='reserved_password' value='$password' /></td>
	</tr>
	<tr>
		<td>&nbsp; </td>
		<td>$buttons<br/>$warninghtml<br/></td>
	</tr>
</table>
";
WebFormClose();

?>
