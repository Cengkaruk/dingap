<?php

// FIXME: locale
// FIXME: icons

if (! empty($page['report_url']))
	$report_url = "<img src='/images/icons/16x16/icon-antivirus.png' alt=''> <a href='" . $page['report_url'] . "'>" . "Report" . "</a>";

if (! empty($page['status_url']))
	$status_url = "<img src='/images/icons/16x16/icon-antivirus.png' alt=''>  <a href='" . $page['status_url'] . "'>" . "Status" . "</a>";

if (! empty($page['sdn_url']))
	$sdn_url = "<img src='/images/icons/16x16/icon-antivirus.png' alt=''> <a href='" . $page['sdn_url'] . "'>" . "ClearSDN" . "</a>";

echo "
<div id='dialogintro1' class='sb'><div class='dialogpaddingworkaround'>
<table cellspacing='3' cellpadding='0' width='100%' border='0'>
	<tr>
		<td width='40'><img src='" . $page['large_icon'] . "' alt=''></td>
		<td>" . $page['summary'] . "</td>
		<td width='5'>&nbsp; </td>
		<td width='120'>
			<img src='/images/icons/16x16/icon-antivirus.png' alt=''> <a href='" . $page['user_guide_url'] . "'>" . "User Guide" . "</a><br>
			<img src='/images/icons/16x16/icon-bandwidth.png' alt=''> <a href='" . $page['support_url'] . "'>" . "Support" . "</a><br>
		</td>
		<td width='120'>
			$status_url<br>
			$report_url
		</td>
		<td width='120' class='dialogalignworkaround'>
		 	<img src='/images/icons/16x16/icon-multiwan.png' alt=''>  <a href='" . $page['forums_url'] . "'>" . "Forums" . "</a><br>
			$sdn_url
		</td>
	</tr>
</table></div></div>
<div class='dialogpadding'></div>

<script type='text/javascript'>
	dialogbox.render('dialogintro1');
</script>

";

?>
