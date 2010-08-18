<?php

// The WebButton() function passes an image name instead of a class
// Some kludgery to make this CSS friendly

$class = preg_replace("/.*images\//", "pcnbutton_", $image);
$class = preg_replace("/\..*/", "", $class);

$id = empty($options['id']) ? "clearos_button_$button_count" : $options['id'];
$type = empty($options['type']) ? "submit" : $options['type'];
$onclick = (empty($options['onclick'])) ? "" : "onclick='" . preg_replace("/'/", "\"", $options['onclick']) . "'";

$button = "
<input id='$id' name='$name' value=\"$text\" type='$type' $onclick class='ui-state-default ui-corner-all' />
";

?>
