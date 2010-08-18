<?php

// The WebButton() function passes an image name instead of a class
// Some kludgery to make this CSS friendly

$class = preg_replace("/.*16x16\//", "pcnbutton-", $image);
$class = preg_replace("/\..*/", "", $class);

// Add the button options the YUI way

$id = empty($options['id']) ? "Button$button_count" : $options['id'];
$type = empty($options['type']) ? "submit" : $options['type'];
$yuitype = empty($options['type']) ? "" : "type: '" . $options['type'] . "'";

if (! empty($options['onclick'])) {
	$onclick = "onclick='" . preg_replace("/'/", "\"", $options['onclick']) . "'";
	$yuionclick = empty($options['onclick']) ? "" : ", onclick: { fn: " . $options['onclick'] . " }";
	$yuionclick = preg_replace("/[()]/", " ", $yuionclick);  // Trim the () from the function
} else {
	$onclick = "";
	$yuionclick = "";
}

$button = "
<input id='$id' name='$name' value=\"$text\" type='$type' $onclick />
<script type='text/javascript'>
        var o$id = new YAHOO.widget.Button('$id', { $yuitype $yuionclick });
        o$id.addClass('$class');
</script>
";

?>
