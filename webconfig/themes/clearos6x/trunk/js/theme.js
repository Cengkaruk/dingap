$(function(){
    // Buttons and Anchors
    $(".clearos-anchor").button();
    $(".clearos-form-submit").button();
    $(".clearos-button-set").buttonset();

	// Progress bar
    $(".progressbar").progressbar({});

	// Dialog box
    $('.dialogbox').dialog({
        autoOpen: false,
        width: 600
    });

	// Forms / FIXME
    $('fieldset').addClass('ui-widget-content ui-corner-all');
	$('legend').addClass('ui-widget-header ui-corner-all');
	$('label').addClass('ui-widget ui-corner-all');
});

// vim: ts=4 syntax=javascript
