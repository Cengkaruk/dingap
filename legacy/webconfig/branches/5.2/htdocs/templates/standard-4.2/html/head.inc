<!-- Favicon -->
<link rel="shortcut icon" href="/templates/standard-4.2/images/favicon.ico">

<!-- Menus and Buttons -->
<script type="text/javascript" src="/templates/base/yui/build/menu/menu-min.js"></script>
<script type="text/javascript" src="/templates/base/yui/build/button/button-min.js"></script>

<script type="text/javascript" src="/templates/standard-4.2/html/menu.js"></script>

<link type="text/css" rel="stylesheet" href="/templates/base/yui/build/container/assets/container.css">
<link type="text/css" rel="stylesheet" href="/templates/standard-4.2/html/menu.css">
<link type="text/css" rel="stylesheet" href="/templates/standard-4.2/html/button.css">
<link type="text/css" rel="stylesheet" href="/templates/standard-4.2/html/wizard.css">

<!-- Tabs -->
<script type="text/javascript" src="/templates/base/yui/build/tabview/tabview-min.js" ></script>
<link rel="stylesheet" type="text/css" href="/templates/standard-4.2/html/tabs.css">

<!-- Rounded dialog boxes -->
<script type="text/javascript" src="/templates/standard-4.2/html/shadedborder.js"></script>
<script type="text/javascript">
    var dialogbox = RUZEE.ShadedBorder.create({ corner:8, shadow:14, border:2 });
</script>

<!-- Internet Explorer hacks -->
<?php
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
		echo "<link rel='stylesheet' type='text/css' href='/templates/standard-4.2/html/ie.css'>";
?>



