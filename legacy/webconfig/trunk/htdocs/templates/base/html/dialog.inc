<?php

//----------------------------------------------------------------------------
//
// There are a number of "dialog boxes" used to display things like warning
// messages, information messages, subscription status, page intro, etc.
//
// Variables:
// - class: CSS class used by the dialog box
// - title: the title of the dialog box
// - icon:  an optional 32x32 icon
// - blurb: the contents of the dialog box
//
//----------------------------------------------------------------------------

echo "
<table cellspacing='0' cellpadding='3' border='0' width='100%' class='$class'>
  <tr>
   <th width='99%'align='left'><b>$title </b></th>
  </tr>
  <tr>
   <td colspan='3'>&#160; </td>
  </tr>
</table>
<table cellspacing='0' cellpadding='0' border='0' width='100%' class='$class'>
  <tr>
   <td width='10'>&#160; </td>
   <td width='40' align='center' valign='middle'>$icon</td>
   <td width='10'><img src='/images/transparent.gif' alt='' width='10' height='1' /> </td>
   <td>$blurb</td>
   <td width='10'>&#160; </td>
  </tr>
</table>
<table cellspacing='3' cellpadding='3' border='0' width='100%' class='$class'>
  <tr>
   <td width='99%'>&#160; </td>
  </tr>
</table>
<br />
";

?>
