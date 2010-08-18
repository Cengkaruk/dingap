<?php

echo "
<div id='$class$dialogbox_count' class='sb'><div class='dialogpaddingworkaround'>
<table cellspacing='3' cellpadding='0' width='95%' border='0'>
	<tr>
		<td width='40'>$icon</td>
		<td class='dialogalignworkaround'>$blurb</td>
	</tr>
</table></div></div>
<div class='dialogpadding'></div>

<script type='text/javascript'>
	dialogbox.render('$class$dialogbox_count');
</script>

";

?>
