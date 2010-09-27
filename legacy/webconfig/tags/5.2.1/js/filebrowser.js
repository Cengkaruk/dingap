// Javascript File Browser

var FileBrowser = {
	// Public properties
	postUrl : null,
	loadingImage : null,
	config : null,

	// Private properties
	selectDirRequest : null,
	changeDirRequest : null,

	// Select a directory
	selectDir : function(hash) {
		if (FileBrowser.config == null || hash == null) return;

		FileBrowser.selectDirRequest = NewXMLHttpRequest(FileBrowser.postUrl, true);
		if (!FileBrowser.selectDirRequest) {
			// TODO: Display error message on page, AJAX unavailable...
			return;
		}

		var post = 'fb_config=' + Url.encode(Base64.encode(FileBrowser.config)) +
			'&fb_action=select' + '&fb_hash=' + hash;

		var checkbox = null;
		if (hash.substr(0, 3) == 'cb_')
			checkbox = document.getElementById(hash);
		else
			checkbox = document.getElementById('cb_' + hash);

		if (checkbox && checkbox.checked) post = post + '&fb_select=true';

		FileBrowser.selectDirRequest.onreadystatechange = FileBrowser.selectDirResult;
		FileBrowser.selectDirRequest.send(post);
	},

	// Change working directory
	changeDir : function(hash) {
		if (FileBrowser.config == null) return;
		if (FileBrowser.postUrl == null) {
			alert('FileBrowser.postUrl is not set!');
			return;
		}

		FileBrowser.changeDirRequest = NewXMLHttpRequest(FileBrowser.postUrl, true);
		if (!FileBrowser.changeDirRequest) {
			// TODO: Display error message on page, AJAX unavailable...
			return;
		}

		// Clear file/folder table
		var tbody = FileBrowser.clearTable();
		if (!tbody) return;

		var tr = document.createElement('TR');

		var td = document.createElement('TD');
		td.setAttribute('colspan', '5');
		td.setAttribute('align', 'center');
		td.setAttribute('style', 'padding: 5px');

		var img = document.createElement('IMG');
		img.setAttribute('src', FileBrowser.loadingImage);

		td.appendChild(img);
		tr.appendChild(td);

		tbody.appendChild(tr);

		var post = 'fb_config=' + Url.encode(Base64.encode(FileBrowser.config)) +
			'&fb_action=change' + '&fb_hash=' + hash;

		FileBrowser.changeDirRequest.onreadystatechange = FileBrowser.changeDirResult;
		FileBrowser.changeDirRequest.send(post);
	},

	selectDirResult : function() {
		if (FileBrowser.selectDirRequest.readyState != 4) return;
		if (FileBrowser.selectDirRequest.status != 200) return;

		// TODO: Select None/All
	},

	changeDirResult : function() {
		if (FileBrowser.changeDirRequest.readyState != 4) return;
		if (FileBrowser.changeDirRequest.status != 200) return;
		if (FileBrowser.changeDirRequest.responseXML == null) return;

		var response = FileBrowser.changeDirRequest.responseXML;
		if (!response) return;

		// Clear file/folder table
		var tbody = FileBrowser.clearTable();
		if (!tbody) return;

		// Check for error string
		var error = response.getElementsByTagName('error')[0];
		if (error) {
			error_text = error.firstChild.nodeValue;
			
			var tr = document.createElement('TR');

			var td = document.createElement('TD');
			td.setAttribute('colspan', '5');
			td.appendChild(document.createTextNode(error_text));
			tr.appendChild(td);

			tbody.appendChild(tr);

			return;
		}

		// Configuration file
		var entries = response.getElementsByTagName('entries')[0];
		if (!entries) return;
		var config = entries.getElementsByTagName('config')[0].firstChild.nodeValue;
		if (!config) return;

		// Set path
		var path = entries.getElementsByTagName('path')[0].firstChild.nodeValue;
		var path_object = document.getElementById('fb_path');
		if (path && path_object)
			path_object.innerHTML = Base64.decode(Url.decode(path));

		var tr = document.createElement('TR');
		tr.setAttribute('class', 'rowenabled');
		tr.setAttribute('onclick', "FileBrowser.changeDir('parent')");
		tr.setAttribute('onmouseover', "FileBrowser.mouseOver('parent')");
		tr.setAttribute('onmouseout', "FileBrowser.mouseOut('parent', 'rowenabled')");
		tr.setAttribute('style', 'height: 20px; cursor: pointer;');
		tr.id = 'parent';

		// Icon
		var td = document.createElement('TD');
		td.setAttribute('width', '100%');
		td.setAttribute('width', '24px');
		td.setAttribute('class', 'pcn_icon_parent');
		td.appendChild(document.createTextNode(''));
		tr.appendChild(td);

		// File/folder name
		td = document.createElement('TD');
		td.appendChild(document.createTextNode('..'));
		tr.appendChild(td);

		// Size
		td = document.createElement('TD');
		td.appendChild(document.createTextNode(''));
		tr.appendChild(td);

		// Changed (modified)
		td = document.createElement('TD');
		td.appendChild(document.createTextNode(''));
		tr.appendChild(td);

		// Options
		td = document.createElement('TD');
		td.appendChild(document.createTextNode(''));
		tr.appendChild(td);

		tbody.appendChild(tr);

		// Get folder entries
		if (!entries.getElementsByTagName('entry').length) return;

		for (var x = 0; x < entries.getElementsByTagName('entry').length; x++) {
			var entry = entries.getElementsByTagName('entry')[x];

			var hash = entry.getElementsByTagName('hash')[0].firstChild.nodeValue;
			var filename = Url.decode(entry.getElementsByTagName('filename')[0].firstChild.nodeValue);
			var size = entry.getElementsByTagName('size')[0].firstChild.nodeValue;
			var properties = entry.getElementsByTagName('properties')[0].firstChild.nodeValue;
			var modified_text = entry.getElementsByTagName('modified_text')[0].firstChild.nodeValue;
			var selected = entry.getElementsByTagName('selected')[0].firstChild.nodeValue;

			var style = 'rowenabledalt';
			if (x % 2) style = 'rowenabled';

			tr = document.createElement('TR');
			tr.id = hash;
			tr.setAttribute('class', style);
			if (properties.substr(0, 1) == 'd') {
				tr.setAttribute('style', 'cursor: pointer;');
				tr.setAttribute('onmouseover', "FileBrowser.mouseOver('" + hash + "')");
				tr.setAttribute('onmouseout', "FileBrowser.mouseOut('" + hash + "', '" + style + "')");
			}

			// Icon
			td = document.createElement('TD');
			td.setAttribute('width', '16px');
			td.setAttribute('class', properties.substr(0, 1) == 'd' ? 'pcn_icon_folder' : 'pcn_icon_file');
			if (properties.substr(0, 1) == 'd')
				td.setAttribute('onclick', "FileBrowser.changeDir('" + hash + "')");
			td.appendChild(document.createTextNode(''));
			tr.appendChild(td);

			// File/folder name
			td = document.createElement('TD');
			if (properties.substr(0, 1) == 'd')
				td.setAttribute('onclick', "FileBrowser.changeDir('" + hash + "')");
			td.appendChild(document.createTextNode(filename));
			tr.appendChild(td);

			// Size
			td = document.createElement('TD');
			td.appendChild(document.createTextNode(properties.substr(0, 1) == 'd' ? '' : size));
			td.setAttribute('align', 'right');
			td.setAttribute('style', 'padding-right: 20px');
			if (properties.substr(0, 1) == 'd')
				td.setAttribute('onclick', "FileBrowser.changeDir('" + hash + "')");
			tr.appendChild(td);

			// Changed (modified)
			td = document.createElement('TD');
			// Change commenting on next 2 lines to prevent display of date for folders
			td.appendChild(document.createTextNode(modified_text));
			//td.appendChild(document.createTextNode(properties.substr(0, 1) == 'd' ? '' : modified_text));
			if (properties.substr(0, 1) == 'd')
				td.setAttribute('onclick', "FileBrowser.changeDir('" + hash + "')");
			tr.appendChild(td);

			// Options
			td = document.createElement('TD');
			var checkbox = document.createElement('INPUT'); 
			checkbox.id = 'cb_' + hash;
			checkbox.setAttribute('type', 'checkbox');
			checkbox.setAttribute('name', checkbox.id);
			checkbox.setAttribute('onclick', "FileBrowser.selectDir('" + checkbox.id + "')");
			if (selected == 1) checkbox.setAttribute('checked', true);
			td.appendChild(checkbox);
			td.setAttribute('align', 'center');
			tr.appendChild(td);

			tbody.appendChild(tr);
		}
	},

	// Handle mouseOver event
	mouseOver : function(hash) {
		document.getElementById(hash).className = 'rowhoverhighlight';
	},

	// Handle mouseOut event
	mouseOut : function(hash, style) {
		document.getElementById(hash).className = style;
	},

	// Handle selectAll event
	selectAll : function() {
		FileBrowser.selectEvent(true);
	},

	// Handle selectNone event
	selectNone : function() {
		FileBrowser.selectEvent(false);
	},

	// Process selectAll/None event
	selectEvent : function(select) {
		var table = document.getElementById('fb_table');
		if (!table) return null;

		var tbody = table.getElementsByTagName('TBODY')[1];
		if (!tbody) tbody = table.getElementsByTagName('TBODY')[0];
		if (!tbody) return null;

		for(var i = 0; i < tbody.rows.length; i++) {
			var hash = tbody.rows[i].id;
			if (!hash.length || hash == 'parent') continue;
			var checkbox = document.getElementById('cb_' + hash);
			if (checkbox) checkbox.checked = select;
			FileBrowser.selectDir(hash);
		}
	},

	// Delete any existing files and folders	
	clearTable : function() {
		var table = document.getElementById('fb_table');
		if (!table) return null;

		var tbody = table.getElementsByTagName('TBODY')[1];
		if (!tbody) tbody = table.getElementsByTagName('TBODY')[0];
		if (!tbody) return null;

		for(var i = tbody.rows.length - 1; i > 0; i--) tbody.deleteRow(i); 

		return tbody;
	}
}

// vi: ts=4
