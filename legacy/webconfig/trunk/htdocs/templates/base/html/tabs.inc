<?php

$tabheader = "";
$tabcontents = "";

foreach ($tabinfo as $key => $tabitem) {
	$selected = ($key === $active) ? "class='selected'" : "";
	$tabheader .= "<li $selected><a href='#pcn" . $key . "'><em>" . $tabitem['title'] . "</em></a></li>\n";

	$tabcontents .= "<div id='pcn$key'>" . $tabitem['contents'] . "</div>\n";
}


echo "
<br>
<div id='pcntab' class='yui-navset'>
  <table cellspacing='0' cellpadding='0' border='0' width='100%' class='mytabletitle'>
    <tr>
      <td width='15' valign='top' class='mytable-tl'></td>
      <td class='mytable-tm'>$tabtitle</td>
      <td width='15' valign='top' class='mytable-tr'><img src='/images/transparent.gif' alt='' width='15' height='35' /></td>
    </tr>
  </table>
  <table id='tid-3f6abce2' cellspacing='0' cellpadding='0' width='100%' border='0' class='mymenuouter'>
    <tr>
      <td>
        <table cellspacing='0' cellpadding='3' width='100%' border='0' class='mymenuinner'>
          <tr>
            <td class='table-left'>&nbsp; </td>
            <td class='table-header-middle'>
              <ul class='yui-nav'>
                $tabheader
              </ul>
            </td>
            <td class='table-right'>&nbsp; </td>
          </tr>
          <tr>
            <td class='table-left'>&nbsp; </td>
            <td>
              <div class='yui-content'>
                $tabcontents
              </div>
            </td>
            <td class='table-right'>&nbsp; </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <table cellspacing='0' cellpadding='0' border='0' width='100%' class='mytable'>
    <tr>
     <td width='20' valign='top' class='mytable-bl'><img src='/images/transparent.gif' alt='' width='20' height='20' /></td>
     <td class='mytable-bm'><img src='/images/transparent.gif' alt='' width='20' height='20' /></td>
     <td width='20' valign='top' class='mytable-br'><img src='/images/transparent.gif' alt='' width='20' height='20' /></td>
    </tr>
  </table>
</div>
";
// vi: syntax=php ts=4
?>
