$(function(){
	// Progress bar demo
	$("#progressbartest").progressbar({
		value: 35
	});

	// Dialog box demo
	$('#dialogtest').dialog({
		buttons: {
			"Ok": function() { 
				$(this).dialog("close"); 
			}, 
			"Cancel": function() { 
				$(this).dialog("close"); 
			} 
		}
	});
	
	$('#dialog_test_link').click(function(){
		$('#dialogtest').dialog('open');
		return false;
	});

///////////////////////////////////// TODO

	// Grid
	$("#grid").jqGrid({
		caption: "ClearOS Grid",
		colNames:['Date','Client IP','Total','Average','Notes'],
		colModel:[
			{name:'summarydate', index:'summarydate', width:90, sorttype:"date"},
			{name:'ip', index:'ip', width:100},
			{name:'usage', index:'usage', width:80, align:"right", sorttype:"float"},
			{name:'average', index:'average', width:80, align:"right", sorttype:"float"},
			{name:'note', index:'note', width:200, sortable:false}
		],
		datatype: "local",
		height: 100,
		rowNum: 3,
		rowList:[3,6,9],
		pager: '#gridpagination',
		loadonce: true,
	});
	var mydata = [
			{summarydate:"2010-10-01",ip:"192.168.1.1",note:"This is a note",usage:"300",average:"10"},
			{summarydate:"2010-10-02",ip:"192.168.1.12",note:"This is another note",usage:"300",average:"20"},
			{summarydate:"2010-10-03",ip:"192.168.1.13",note:"This is yet another note",usage:"300",average:"30"},
			{summarydate:"2010-10-04",ip:"192.168.1.1",note:"This is part of a series of notes",usage:"250",average:"10"},
			{summarydate:"2010-10-05",ip:"192.168.1.12",note:"Hey, this a note",usage:"300",average:"10"},
			{summarydate:"2010-10-06",ip:"192.168.1.13",note:"I smell a note!",usage:"400",average:"10"},
			{summarydate:"2010-10-07",ip:"192.168.1.1",note:"Dogs and cats living together, with a note",usage:"200",average:"10"},
			{summarydate:"2010-10-08",ip:"192.168.1.12",note:"This is a high note",usage:"300",average:"20"},
			{summarydate:"2010-10-09",ip:"192.168.1.13",note:"This is a low note",usage:"300",average:"30"},
			];
	for(var i=0;i<=mydata.length;i++)
		jQuery("#grid").jqGrid('addRowData',i+1,mydata[i]);


	// Tabs
	$('#tabs').tabs();

	// Date Picker
	$('#datepicker').datepicker({
		inline: true
	});
	
	// Slider
	$('#slider').slider({
		range: true,
		values: [17, 67]
	});
	
	//hover states on the static widgets
	$('ul#icons li').hover(
		function() { $(this).addClass('ui-state-hover'); }, 
		function() { $(this).removeClass('ui-state-hover'); }
	);
});

// vim: ts=4 syntax=javascript
