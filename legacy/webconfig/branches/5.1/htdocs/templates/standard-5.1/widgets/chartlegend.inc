<?php

$headerrow = "";

if ($headers) {
	$column_entries = explode("|", $headers);
	foreach ($column_entries as $value)
		$headerrow .= "<td nowrap style='background: #EEEEEE'>" . $value . "</td>\n";
	$headerrow = "<tr>$headerrow</tr>";
}

$legend = "
  <table width='$width' border='0' cellspacing='0' cellpadding='3' align='center' class='chartlegend'>
	<tr>
	  <td class='chartlegendtitle' align='center' colspan='100'><b>$title</b></td>
	</tr>
	$headerrow
	$rows
  </table>
";

?>
