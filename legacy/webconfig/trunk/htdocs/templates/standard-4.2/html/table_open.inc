<?php

//----------------------------------------------------------------------------
//
// There are two variables included in the table template:
//
// - width: the width of the table
// - title: the title of the table
//
//----------------------------------------------------------------------------

echo "
<table cellspacing='0' cellpadding='0' border='0' width='$width' class='mytablespacingtop'>
	<tr>
		<td class='table-header-left'>&nbsp; </td>
		<td class='table-header-middle'>$title</td>
		<td class='table-header-right'>&nbsp; </td>
	</tr>
</table>
<table cellspacing='0' cellpadding='0' width='$width' border='0' class='tablebody' id='$id'>
	<tr>
		<td class='table-left'>&nbsp; </td>
		<td>
			<table cellspacing='0' cellpadding='$padding' width='100%' border='0' class='mytable' id='$id-inner'>
";

?>
