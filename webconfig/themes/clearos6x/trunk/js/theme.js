$(function(){
	// Anchors
	$(".theme-anchor").button();

	// Buttons
	$(".theme-form-submit").button();

	// Anchor and button sets
	$(".theme-button-set").buttonset();

	// Progress bar
	$(".theme-progress-bar").progressbar({ 
		value: 0
	});

	// Summary tables
	$(".theme-summary-table").dataTable({
		"aoColumnDefs": [{ 
			"bSortable": false, "aTargets": [ -1 ] 
		}],
		"bJQueryUI": true,
		"sPaginationType": "full_numbers"
	});

	// Forms / FIXME
	$('fieldset').addClass('ui-widget-content ui-corner-all');
	$('legend').addClass('ui-widget-header ui-corner-all');
	$('label').addClass('ui-widget ui-corner-all');
});

// vim: ts=4 syntax=javascript
