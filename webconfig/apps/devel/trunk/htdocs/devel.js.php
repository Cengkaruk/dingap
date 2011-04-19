$(document).ready(function() {

    // Dialog Box
    //-----------    

    // Click event
	$('#dialog_box_anchor').click(function() {
		$('#dialog_box_message').dialog('open');
		return false;
	});

    // Dialog box
	$('#dialog_box_message').dialog({
        autoOpen: false,
		buttons: {
			"Ok": function() { 
				$(this).dialog("close"); 
			}, 
			"Cancel": function() { 
				$(this).dialog("close"); 
			} 
		}
	});

    // Progress Bar Demo
    //------------------

    getData();

    function getData() {
        $.ajax({
            url: '/app/devel/progress_data',
            method: 'GET',
            dataType: 'json',
            success : function(json) {
                showData(json);
                window.setTimeout(getData, 1000);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $("#status").html('Ooops: ' + textStatus);
                window.setTimeout(getData, 1000);
            }
        });
    }

    function showData(info) {
        $("#bacon_progress").progressbar({
            value: Math.round(info.progress)
        });

        $("#bacon_progress_standalone").progressbar({
            value: Math.round(info.progress_standalone)
        });
    }

	// Tabs
    //-----

	// $('#tabs').tabs();

});

// vim: ts=4 syntax=javascript
