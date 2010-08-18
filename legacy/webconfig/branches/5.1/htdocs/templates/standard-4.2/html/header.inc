<?php

///////////////////////////////////////////////////////////////////////////////
// Main
///////////////////////////////////////////////////////////////////////////////

// Figure out what section we're in so we can highlight the tab in the top
// navigation bar.
//-----------------------------------------------------------------------------

$pagedata = WebMenuFetch();

$current_section = "";

foreach ($pagedata as $pageinfo) {
	if ($pageinfo['url'] == $_SERVER['PHP_SELF']) {
		$current_section = $pageinfo['section'];
		break;
	}
}

// Build menu
//-----------

$current_subsection = "";
$current_section = "";

$leftmenuheight = 0;
$leftmenus = array();
$leftmenukey = "";
$navmenu = "";

// TODO: moving target for 5.1 release.  Sorry
/*
foreach ($pagedata as $page) {
	if (($page['type'] == "report") || ($page['type'] == "status"))
		$page['section'] = "Reports";

	$newpagedata[] = $page;
}

sort($newpagedata);
$pagedata = $newpagedata;
*/

foreach ($pagedata as $page) {

	if ($page['section'] != $current_section) {
		$current_section = $page['section'];
		$leftmenucount[$current_section] = 0;

		if (! empty($navmenu))
			$navmenu .= "</ul></div></div></li>";

		$navmenu .= "<li class='yuimenubaritem'><a class='yuimenubaritemlabel' href='#'>" . $page['section'] . "</a><div class='yuimenu'><div class='bd'><ul>";
	}

	if ($page['subsection'] != $current_subsection) {
		$current_subsection = $page['subsection'];
		$menuitem = "</ul>\n<h6 class='menusubtitle'>" . $page['subsection'] . "</h6>\n<ul>\n";
		$leftmenucount[$current_section]++;

		if (isset($leftmenus[$current_section]))
			$leftmenus[$current_section] .= $menuitem;
		else
			$leftmenus[$current_section] = $menuitem;

		$navmenu .= $menuitem;
	}

	if ($_SERVER['PHP_SELF'] == $page['url']) {
		$class = 'yuimenuitemon';
		$leftmenukey = $current_section;
	} else {
		$class = 'yuimenuitem';
	}

	$leftmenucount[$current_section]++;

	$menuitem = "  <li class='$class'><a class='yuimenuitemlabel' href='" . $page['url'] . "'>" . $page['title'] . "</a></li>\n";
	$leftmenus[$current_section] .= $menuitem;
	$navmenu .= $menuitem;
}

$leftmenuheight = $leftmenucount[$leftmenukey] * 21 + 10;

$leftmenu = $leftmenus[$leftmenukey];
$leftmenu = preg_replace("/^<\/ul>/", "", $leftmenu);
if (!empty($leftmenu))
	$leftmenu .= "</ul>";


$navmenu = preg_replace("/<ul><\/ul>/", "", $navmenu);
$navmenu .= "</ul></div></div></li>";

$os = preg_replace("/clearos/i", "", $_SESSION['system_osname']);
$os = $os . " " .$_SESSION['system_osversion'];

if (isset($_SESSION['user_login'])) {
	$login = 
		"<b>" . LOCALE_LANG_USERNAME . 
		"<img src='/templates/standard-4.2/images/icon_arrowright_bottom.png' alt=' - '> " . $_SESSION['user_login'] . "<br>" .
		"<a href='?reserved_logout=y'>" . WEBCONFIG_LANG_LOGOUT . "</a></b>";
} else {
	$login = "";
}

// Branding logo
$branding = file_exists('../images/branding.png') ? "<img src='/images/branding.png' alt=''>" : "";

///////////////////////////////////////////////////////////////////////////////
// HTML
///////////////////////////////////////////////////////////////////////////////

if (strlen($onload))
	echo "<body onload='$onload'>";
else
	echo "<body>";

// Attempted to do this with <div> and CSS... gave up due to browser compatiblity issues

echo "
<table width='100%' border='0' align='center' cellpadding='0' cellspacing='0' class='page-width'>
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
					<td class='pcn-inner-banner-middle'><img src='/templates/standard-4.2/images/top_c01.jpg' width='260' height='104' alt=''><img src='/templates/standard-4.2/images/top_c02.jpg' width='182' height='104' alt=''></td>
					<td class='pcn-inner-banner-middle' valign='middle' align='left'>$branding</td>
					<td class='pcn-inner-banner-middle' valign='middle' width='10'>&nbsp; </td>
					<td class='pcn-inner-banner-middle' valign='top' nowrap align='right'><br>$login</td>
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
					<td class='pcn-navbar-buffer' width='180'><div class='pcn-navbar-overlay'><div class='pcn-navbar-text'>$os</div></div></td>
					<td class='pcn-navbar-middle' width='630'>
						<div id='pcntopnav' class='yuimenubar yuimenubarnav'>
							<div class='bd'>
<div style='float: left;'><img src='/templates/standard-4.2/images/navbar_spcr.jpg' width='12' height='33' alt=''></div>
								<ul class='first-of-type'>
									$navmenu
								</ul>            
							</div>
						</div>
					</td>
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
					<td width='200' align='left' valign='top'>
						<br>
						<table cellspacing='0' cellpadding='0' border='0' width='200'>
							<tr>
								<td class='menu-header-left'>&nbsp; </td>
								<td class='menu-header-middle'>&nbsp;</td>
								<td class='menu-header-right'>&nbsp; </td>
							</tr>
						</table>
						<table cellspacing='0' cellpadding='0' width='200' border='0'>
							<tr>
								<td class='table-left' width='3'>
									&nbsp; 
									<img src='/images/transparent.gif' alt='' width='1' id='pcnmenuheight'>
									<script type='text/javascript'>
										var pcnmenuheight = document.getElementById('pcnmenuheight');
										pcnmenuheight.setAttribute('height', '$leftmenuheight');
									</script>
								</td>
								<td valign='top' width='194' class='menu-body-middle'>
									<div id='ccmenu' class='yuimenu'>
										<div id='menuwidth' class='bd'>
											$leftmenu
										</div>
									</div>
									&nbsp; 
								</td>
								<td class='table-right' width='3'>&nbsp; </td>
							</tr>
						</table>
						<table cellspacing='0' cellpadding='0' border='0' width='100%'>
							<tr>
								<td class='table-footer-left'> </td>
								<td class='table-footer-middle'>&nbsp; </td>
								<td class='table-footer-right'>&nbsp; </td>
							</tr>
						</table>
						<br>
					</td>
					<td width='10'>&nbsp; </td>
					<td class='content' valign='top' align='center'>
						<br>
";

// vi: syntax=php ts=4
?>
