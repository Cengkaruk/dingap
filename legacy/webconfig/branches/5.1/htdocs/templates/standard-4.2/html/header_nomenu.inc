<?php

// Branding logo
$branding = file_exists('../images/branding.png') ? "<img src='/images/branding.png' alt=''>" : "";

///////////////////////////////////////////////////////////////////////////////
// HTML
///////////////////////////////////////////////////////////////////////////////

if (strlen($onload))
	echo "<body onload='$onload'>";
else
	echo "<body>";

echo "
<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' class='page-width-nomenu'>
	<tr>
		<td class='pcn-header-left' width='20'>&nbsp; </td>
		<td class='pcn-header-middle'>&nbsp; </td>
		<td class='pcn-header-right' width='20'>&nbsp; </td>
	</tr>


	<tr>
		<td class='pcn-banner-left' width='20'>&nbsp; </td>
		<td class='pcn-banner-middle'>
			<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0'>
				<tr>
					<td class='pcn-inner-banner-left'>&nbsp; </td>
					<td class='pcn-inner-banner-middle'><img src='/templates/standard-4.2/images/top_c01.jpg' width='251' height='104' alt=''><img src='/templates/standard-4.2/images/top_c02.jpg' width='191' height='104' alt=''></td>
					<td class='pcn-inner-banner-middle' valign='middle' align='center'>$branding</td>
					<td class='pcn-inner-banner-right'>&nbsp; </td>
				</tr>
			</table>
		</td>
		<td class='pcn-banner-right' width='20'>&nbsp; </td>
	</tr>


	<tr>
		<td class='pcn-body-left' width='20'>&nbsp; </td>
		<td class='pcn-body-middle'>
			<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' class='pcn-navbar'>
				<tr>
					<td class='pcn-navbar-left' width='20'>&nbsp; </td>
					<td class='pcn-navbar-buffer' width='380'><div class='pcn-navbar-overlay'><div class='pcn-navbar-text'>&nbsp; </div></div></td>
					<td class='pcn-navbar-buffer'>&nbsp; </td>
					<td class='pcn-navbar-right' width='20'>&nbsp; </td>
				</tr>
			</table>
		</td>
		<td class='pcn-body-right' width='20'>&nbsp; </td>
	</tr>


	<tr>
		<td class='pcn-body-left' width='20'>&nbsp; </td>
		<td class='pcn-body-middle' valign='top'>
			<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0'>
				<tr>
					<td class='content' valign='top' align='center'>
						<br>
";

// vi: syntax=php ts=4
?>
