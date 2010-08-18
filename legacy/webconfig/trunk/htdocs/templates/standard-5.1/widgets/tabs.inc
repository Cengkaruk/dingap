<?php

$tabheader = "";
$tabcontents = "";
$count = 0;
$select = 0;

foreach ($tabinfo as $key => $tabitem) {
    if ($key === $active)
		$select = $count;

	$count++;
    $tabheader .= "<li><a href='#tabs-" . $key . "'>" . $tabitem['title'] . "</a></li>\n";
    $tabcontents .= "<div id='tabs-$key'>" . $tabitem['contents'] . "</div>\n";
}

echo "

<script type='text/javascript'>
	$(function(){
		$('#tabs').tabs({
			selected: $select
		});
	});
</script>

<div id='tabs' class='clearos_tabs'>
	<ul>
		$tabheader
	</ul>
	$tabcontents
</div>
";

// vi: syntax=php ts=4
?>
