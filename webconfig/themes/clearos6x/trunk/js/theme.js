$(function(){
	// Anchors
	$(".theme-anchor").button();

	// Buttons
	$(".theme-form-submit").button();

	// Anchor and button sets
	$(".theme-button-set").buttonset();

	// Progress bar
	/*
	$(".theme-progress-bar").progressbar({ 
		value: 0
	});
*/

	// List tables
	$(".theme-list-table").dataTable({
		"aoColumnDefs": [{ 
			"bSortable": false, "aTargets": [ -1 ] 
		}],
		"bJQueryUI": true,
		"bPaginate": false,
		"bFilter": false,
		"sPaginationType": "full_numbers"
	});

	// Summary tables
	$(".theme-summary-table-small").dataTable({
		"aoColumnDefs": [{ 
			"bSortable": false, "aTargets": [ -1 ] 
		}],
		"bJQueryUI": true,
		"sPaginationType": "full_numbers",
        "bInfo": false,
        "bPaginate": false,
        "bFilter": false,
	});


	$(".theme-summary-table-large").dataTable({
		"aoColumnDefs": [{ 
			"bSortable": false, "aTargets": [ -1 ] 
		}],
		"bJQueryUI": true,
		"sPaginationType": "full_numbers",
        "bInfo": true,
        "bPaginate": true,
        "bFilter": true,
	});

    // Charts
    $.jqplot.config.enablePlugins = true;
   
	// Forms / FIXME
	$('fieldset').addClass('ui-widget-content ui-corner-all');
	$('legend').addClass('ui-widget-header ui-corner-all');
	$('label').addClass('ui-widget ui-corner-all');
});
