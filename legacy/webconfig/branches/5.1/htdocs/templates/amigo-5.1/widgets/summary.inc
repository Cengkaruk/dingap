<?php

// FIXME: remove hard coded links

if (isset($_SESSION['system_registered']) && $_SESSION['system_registered'])  {
	$alert = "<span id='clearos-service-title'>&nbsp; </span><span id='clearos-service-state'>&nbsp; </span>";
	$url = "http://www.first.gr/amigocare/" . $_SESSION['system_hostkey'] . "/" . 
			preg_replace("/\//", "_", $_SERVER['PHP_SELF']);
	$support = "
		<td><span class='ui-state-default ui-corner-all ui-icon ui-icon-person'>-</span></td>
		<td><a target='_blank' href='" . $url . "'>AmigoCare Support</a></td>
	";
	$supportwidth = "250";
} else {
	$alert = ($_SERVER['PHP_SELF'] !== "/admin/register.php") ? "<a href='register.php'>Register with AmigoCenter</a>" : "";
	$support = "";
	$supportwidth = "150";
}

// FIXME
$page['user_guide_url'] = "http://www.first.gr/amigo/user_guide/5.1";
$page['section'] = preg_replace("/ClearCenter/i", "AmigoCenter", $page['section']);

echo "
<div class='ui-widget'>
	<div class='ui-state-highlight ui-corner-all' style='margin-top: 12px'>
		<table width='100%' border='0' cellpadding='0' cellspacing='0'>
			<tr>
				<td>
					<div class='clearos-breadcrumb'>
						" . $page['section'] . "<img src='/templates/amigo-5.1/images/breadcrumb-arrow.png' alt='-'> 
						" . $page['subsection'] . "<img src='/templates/amigo-5.1/images/breadcrumb-arrow.png' alt='-'>
						<span class='clearos-breadcrumb-highlight'>" . $page['title'] . "</span>
					</div>
				</td>
				<td nowrap>
					<div id='clearos-service-alert' align='right'>$alert</div>
				</td>
			</tr>
		</table>
		<div class='clearos-summarytable'>
			<table width='100%' border='0' cellpadding='0' cellspacing='10'>
				<tr>
					<td>
						<div class='clearos-summary'> 
							<img src='" . $page['large_icon'] . "' alt='' hspace='8' align='left' />
							" . $page['summary'] . "
						</div>
					</td>
					<td width='$supportwidth' align='right' nowrap>
						<div class='clearos-summaryinforight'>
							<table align='center'>
								<tr>
									<td><span class='ui-state-default ui-corner-all ui-icon ui-icon-info'>-</span></td>
									<td><a target='_blank' href='" . $page['user_guide_url'] . "'>" . LOCALE_LANG_USER_GUIDE . "</a></td>
									<td>&nbsp;</td>
									$support
								</tr>
							</table>
						</div>    
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
";

?>
