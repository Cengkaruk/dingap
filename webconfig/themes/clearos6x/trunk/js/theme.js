$(function(){
    // Buttons and Anchors
    $(".clearos-anchor").button();
    $(".clearos-form-submit").button();
    $(".clearos-button-set").buttonset();

	// Data tables
    $(".theme-summary-table").dataTable({
		"aoColumnDefs": [{ 
			"bSortable": false, "aTargets": [ -1 ] 
		}],
		"bJQueryUI": true,
		"sPaginationType": "full_numbers"
	});

	// Progress bar
    $(".progressbar").progressbar({});

	// Forms / FIXME
    $('fieldset').addClass('ui-widget-content ui-corner-all');
	$('legend').addClass('ui-widget-header ui-corner-all');
	$('label').addClass('ui-widget ui-corner-all');
});

// vim: ts=4 syntax=javascript
