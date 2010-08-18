<?php

// Map webconfig.inc classes to jqueryui classes
if ($class == "dialogwarning")
	$state = "ui-state-error";
else
	$state = "ui-state-default";

echo "
<div class='ui-widget'>
	<div class='$state ui-corner-all' style='margin-top: 3px; margin-bottom: 3px; padding: 3px;'>
		<table border='0' cellspacing='3' cellpadding='3' width='100%'>
			<tr>
				<td width='35'>$icon<td>
				<td width='99%'>$blurb<td>
			</tr>
		</table>
	</div>
</div>
";

?>
