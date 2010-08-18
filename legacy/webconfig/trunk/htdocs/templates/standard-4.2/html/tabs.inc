<?php

$tabheader = "";
$tabcontents = "";

foreach ($tabinfo as $key => $tabitem) {
	$selected = ($key === $active) ? "class='selected'" : "";
	$tabheader .= "<li $selected><a href='#pcn" . $key . "'><em>" . $tabitem['title'] . "</em></a></li>\n";

	$tabcontents .= "<div id='pcn$key'>" . $tabitem['contents'] . "</div>\n";
}

echo "
<div id='pcntab' class='yui-navset'>
  <table cellspacing='0' cellpadding='0' border='0' width='100%' class='mytablespacingtop'>
    <tr>
      <td class='table-header-left'>&nbsp; </td>
      <td class='table-header-middle'>$tabtitle</td>
      <td class='table-header-right'>&nbsp; </td>
    </tr>
  </table>
  <table cellspacing='0' cellpadding='0' width='100%' border='0' class='tablebody'>
    <tr>
      <td class='table-left'>&nbsp; </td>
      <td class='pcn-tabs-header'><ul class='yui-nav'>$tabheader</ul></td>
      <td class='table-right'>&nbsp; </td>
    </tr>
    <tr>
      <td class='table-left'>&nbsp; </td>
      <td class='pcn-tabs-content'>
        <div style='background: white'>
          <div class='yui-content'>
            $tabcontents
          </div>
        </div>
      </td>
    <td class='table-right'>&nbsp; </td>
  </tr>
  </table>
  <table cellspacing='0' cellpadding='0' border='0' width='100%' class='mytablespacingbottom'>
    <tr>
      <td class='table-footer-left'>&nbsp; </td>
      <td class='table-footer-middle'>&nbsp; </td>
      <td class='table-footer-right'>&nbsp; </td>
    </tr>
  </table>
</div>
";
// vi: syntax=php ts=4
?>
