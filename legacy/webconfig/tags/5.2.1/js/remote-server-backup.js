// GetStatus: Create new XML HTTP request to update remote backup service status
// XXX: NewXMLHttpRequest() included from common.js
function GetStatus()
{
	window.GetStatusRequest = NewXMLHttpRequest('/admin/remote-server-backup.php?request=status', true);
	if (!window.GetStatusRequest) {
		// TODO: Display error message on page, AJAX unavailable...
		return;
	}

	window.GetStatusRequest.onreadystatechange = OnGetStatusResult;
	window.GetStatusRequest.send(null);
}

// OnGetStatusResult: Process result of GetStatus() XML HTTP request
function OnGetStatusResult()
{
	if (!window.GetStatusRequest) return;
	if (window.GetStatusRequest.readyState != 4) return;
	if (window.GetStatusRequest.status == 401) {
		setTimeout('GetStatus()', 50);
		return;
	}
	if (window.GetStatusRequest.status != 200) {
		// TODO: Display error message
		setTimeout('GetStatus()', 1000);
		return;
	}

	var response_xml = window.GetStatusRequest.responseXML;
	if (!response_xml) return;

	var status_node = response_xml.getElementsByTagName('status')[0];
	if (!status_node) return;

	var running_node = status_node.getElementsByTagName('running')[0];
	if (!running_node) return;

	var is_running = running_node.firstChild.nodeValue;

	var running_text_node = status_node.getElementsByTagName('running_text')[0];
	if (!running_text_node) return;

	var running_text = running_text_node.firstChild.nodeValue;

	if (is_running > 0) {
		var code_node = status_node.getElementsByTagName('code')[0];
		if (!code_node) return;

		var status_code = code_node.firstChild.nodeValue;

		var code_text_node = status_node.getElementsByTagName('code_text')[0];
		if (!code_text_node) return;

		var status_text = code_text_node.firstChild.nodeValue;

		var data_node = status_node.getElementsByTagName('data')[0];
		if (!data_node) return;

		var status_data = '';
		if(data_node.firstChild) status_data = data_node.firstChild.nodeValue;

		if (is_running == 3 && document.getElementById('fb_table')) {
			var mounting = document.getElementById('fb_mounting');
			var tbody = document.getElementById('fb_table').getElementsByTagName('TBODY')[1];
			if (!tbody)
				tbody = document.getElementById('fb_table').getElementsByTagName('TBODY')[0];
			if (tbody && status_code == 'FS_MOUNTED' && mounting)
				FileBrowser.changeDir(null);
			else if (tbody && status_code != 'FS_MOUNTED' && !mounting) {
				var tr = document.createElement('TR');

				var td = document.createElement('TD');
				td.id = 'fb_mounting';
				td.setAttribute('colspan', '5');
				td.setAttribute('align', 'center');
				td.setAttribute('style', 'padding: 5px');

				var img = document.createElement('IMG');
				img.setAttribute('src', FileBrowser.loadingImage);

				td.appendChild(img);
				tr.appendChild(td);

				tbody.appendChild(tr);
			}
		}
	} else if (!window.browseLocal) {
		if (document.getElementById('fb_table')) FileBrowser.clearTable();
	}

	var error_node = status_node.getElementsByTagName('error')[0];
	if (!error_node) return;

	var error_code = error_node.firstChild.nodeValue;

	var error_text = '';
	var error_text_node = status_node.getElementsByTagName('error_text')[0];
	if (error_text_node)
		error_text = error_text_node.firstChild.nodeValue;

	var last_backup_node = status_node.getElementsByTagName('last_backup')[0];
	if (!last_backup_node) return;

	var last_backup = '';
	if(last_backup_node.firstChild) last_backup = last_backup_node.firstChild.nodeValue;

	var last_backup_result_node = status_node.getElementsByTagName('last_backup_result')[0];
	if (!last_backup_result_node) return;

	var last_backup_result = '';
	if(last_backup_result_node.firstChild) last_backup_result = last_backup_result_node.firstChild.nodeValue;

	var storage_used_node = status_node.getElementsByTagName('storage_used')[0];
	if (!storage_used_node) return;

	var storage_used = '';
	if(storage_used_node.firstChild) storage_used = storage_used_node.firstChild.nodeValue;

	var storage_remaining_node = status_node.getElementsByTagName('storage_remaining')[0];
	if (!storage_remaining_node) return;

	var storage_remaining = '';
	if(storage_remaining_node.firstChild) storage_remaining = storage_remaining_node.firstChild.nodeValue;

	var html_status = running_text;
	if (is_running && status_text)
		html_status = html_status + '; ' + status_text;
	if (is_running && status_data && status_data != '') {
		switch (status_code) {
		case 'PROVISION_GROW':
		case 'PROVISION_COPY':
			html_status = html_status + '; ' + status_data + '%';
			break;
		default:
			html_status = html_status + '; ' + status_data;
		}
	}

	if (document.getElementById('rbs_status')) {
		var status = document.getElementById('rbs_status');
		if (status) {
			if (status.innerHTML != html_status) status.style.color = "#ff0000";
			else status.style.color = "#000000";
			status.innerHTML = html_status;
		}
	}
	if (document.getElementById('rbs_error') && error_text != '')
		document.getElementById('rbs_error').innerHTML = error_text;
	if (document.getElementById('rbs_last_backup'))
		document.getElementById('rbs_last_backup').innerHTML = last_backup;
	if (document.getElementById('rbs_last_backup_result'))
		document.getElementById('rbs_last_backup_result').innerHTML = last_backup_result;
	if (document.getElementById('rbs_storage_used'))
		document.getElementById('rbs_storage_used').innerHTML = storage_used;
	if (document.getElementById('rbs_storage_remaining'))
		document.getElementById('rbs_storage_remaining').innerHTML = storage_remaining;

	// Update remote backup service status every 1 second
	setTimeout('GetStatus()', 1000);
}

// RefreshBackupHistory: Create new XML HTTP request to the backup history drop-down control
function RefreshBackupHistory()
{
	window.RefreshBackupHistoryRequest = NewXMLHttpRequest('/admin/remote-server-backup.php?request=backup_history', true);
	if (!window.RefreshBackupHistoryRequest) {
		// TODO: Display error message on page, AJAX unavailable...
		return;
	}

	EnableBackupHistory(false);

	window.RefreshBackupHistoryRequest.onreadystatechange = OnRefreshBackupHistoryResult;
	window.RefreshBackupHistoryRequest.send(null);
}

// OnRefreshBackupHistoryResult: Process result of RefreshBackupHistory() XML HTTP request
function OnRefreshBackupHistoryResult()
{
	if (!window.RefreshBackupHistoryRequest) return;
	if (window.RefreshBackupHistoryRequest.readyState != 4) return;
	if (window.RefreshBackupHistoryRequest.status != 200) {
		// TODO: Display error message
		EnableBackupHistory(true);
		return;
	}

	var response_xml = window.RefreshBackupHistoryRequest.responseXML;
	if (!response_xml) {
		EnableBackupHistory(true);
		return;
	}

	var backup_history_node = response_xml.getElementsByTagName('backup_history')[0];
	if (!backup_history_node) {
		EnableBackupHistory(true);
		return;
	}

	var entry_nodes = backup_history_node.getElementsByTagName('entry');
	if (!entry_nodes) {
		EnableBackupHistory(true);
		return;
	}

	var entries = entry_nodes.length;
	if (!entries) {
		EnableBackupHistory(true);
		return;
	}

	var drop_down_control = document.getElementById('rbs_backup_history');
	if (!drop_down_control) {
		EnableBackupHistory(true);
		return;
	}

	while (drop_down_control.options.length)
		drop_down_control.options[0] = null;

	for (var i = 0; i < entries; i++) {
		var mtime_node = entry_nodes[i].getElementsByTagName('mtime')[0];
		if (!mtime_node) continue;
		var text_node = entry_nodes[i].getElementsByTagName('text')[0];
		if (!text_node) continue;
		var option = document.createElement('option');
		if (!option) continue;
		option.text = text_node.firstChild.nodeValue;
		option.value = mtime_node.firstChild.nodeValue;
		drop_down_control.options.add(option);
	}

	EnableBackupHistory(true);
}

// Toggle refresh backup history controls
function EnableBackupHistory(enable)
{
	var drop_down_control = document.getElementById('rbs_backup_history');
	if (drop_down_control) drop_down_control.disabled = !enable;
// XXX: None of these work with YUI buttons :(
//	var refresh_button = document.getElementById('rbs_history_refresh');
//	if (refresh_button) refresh_button.disabled = !enable;
//	if (refresh_button) refresh_button.style.visibility = enable ? 'visible' : 'hidden';
//	if (refresh_button) refresh_button.style.display = enable ? 'block' : 'none';
}

// Toggle automated backup controls
function EnableAutomatedBackup(control)
{
	for (var i = 0; i < 7; i++) {
		var ab_day = document.getElementById('rbs_ab_day' + i);
		if (!ab_day) continue;
		ab_day.disabled = control.value == '1' ? false : true;
	}
	var ab_window = document.getElementById('rbs_ab_window');
	if (ab_window) ab_window.disabled = control.value == '1' ? false : true;
}

// Validate old fs key
function ValidateOldKey(id, security_id, verify_id, result_id)
{
	window.OldSecurityKeyId = id.id;
	window.NewSecurityKeyId = security_id;
	window.VerifySecurityId = verify_id;
	window.ValidateResultId = result_id;

	window.ValidateKeyRequest = NewXMLHttpRequest('/admin/remote-server-backup.php?request=validate_key', true);
	if (!window.ValidateKeyRequest) {
		// TODO: Display error message on page, AJAX unavailable...
		return;
	}

	var post = 'rbs_fskey=' + Url.encode(Base64.encode(id.value));

	window.ValidateKeyRequest.onreadystatechange = OnValidateKeyResult;
	window.ValidateKeyRequest.send(post);
/*
	alert('keypress');
	var result = document.getElementById(result_id);
	if (result) {
		result.innerHTML = id.value;
	}
*/
	return true;
}

// Validate old key result
function OnValidateKeyResult()
{
	if (!window.ValidateKeyRequest) return;
	if (window.ValidateKeyRequest.readyState != 4) return;
	if (window.ValidateKeyRequest.status != 200) {
		// TODO: Display error message
		return;
	}

	var response_xml = window.ValidateKeyRequest.responseXML;
	if (!response_xml) return;

	var validate_node = response_xml.getElementsByTagName('validate')[0];
	if (!validate_node) return;

	var result_node = validate_node.getElementsByTagName('result')[0];
	if (!result_node) return;
	var result_text_node = validate_node.getElementsByTagName('result_text')[0];
	if (!result_text_node) return;

	var result = document.getElementById(window.ValidateResultId);
	if (result) result.innerHTML = result_text_node.firstChild.nodeValue;

	if (result_node.firstChild.nodeValue == '1') {
		var old_security_key = document.getElementById(window.OldSecurityKeyId);
		var new_security_key = document.getElementById(window.NewSecurityKeyId);
		var verify_security_key = document.getElementById(window.VerifySecurityId);
		if (old_security_key) old_security_key.disabled = true;
		if (verify_security_key) verify_security_key.disabled = false;
		if (new_security_key) {
			new_security_key.disabled = false;
			new_security_key.focus();
		}
	}
}

// Call GetStatus() after 'rbs_status_ready' marker has been 'seen'
YAHOO.util.Event.onContentReady('rbs_status_ready', GetStatus);

// vim: ts=4
