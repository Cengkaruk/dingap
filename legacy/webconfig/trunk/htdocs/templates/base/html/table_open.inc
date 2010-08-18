<?php

//----------------------------------------------------------------------------
//
// Variables:
// - width: the width of the table
// - title: the title of the table
//
//----------------------------------------------------------------------------

echo "
<table cellspacing='0' cellpadding='3' border='0' width='$width'>
  <tr>
   <td class='mytabletitle' width='99%' valign='middle'><b>$title</b></td>
   <td class='mytabletitle' width='10'> <img src='/templates/base/images/transparent.png' width='1' height='18' alt='' /></td>
  </tr>
</table>
<table id='$id' cellspacing='0' cellpadding='3' width='$width' border='0' class='mytable'>
";

?>
