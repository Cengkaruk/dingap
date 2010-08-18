// Main function

function getLogs(){
	newXMLHttpRequest();
  	request.onreadystatechange = processPollRequest;
	request.send(null);
}

function processPollRequest() {
	if (!window.request) 
        setTimeout("getLogs()", 1000);
    else if (request.readyState == 4) {
        if (request.status == 200) {
			updateMsgOnBrowser();
        }
        setTimeout("getLogs()", 1000);
    }
}

function updateMsgOnBrowser(logXml) {
	var message = request.responseXML.getElementsByTagName("message")[0];
	var logtime = request.responseXML.getElementsByTagName("logtime")[0];
	var status = request.responseXML.getElementsByTagName("status")[0];

	if (!message) return;

	var message_value = message.firstChild.nodeValue;
	var logtime_value = logtime.firstChild.nodeValue;
	var status_value = status.firstChild.nodeValue;

	var log_window = document.getElementById("log_window");
	if (log_window)
		log_window.innerHTML = '<font style=\'font-family: courier\'>' + message_value.replace(/\n/g, "<br />") + '</font>';

	var log_time = document.getElementById("log_time");
	if (log_time)
		log_time.innerHTML = logtime_value;

	if (status_value == 'done') {
		var log_status = document.getElementById("log_status");
		if (log_status)
			log_status.innerHTML = '';
	}
}

function newXMLHttpRequest() {
	if (window.XMLHttpRequest) {
		window.request = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
   		// Try ActiveX
		try { 
			window.request = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e1) { 
			// first method failed 
			try {
				window.request = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e2) {
				 // both methods failed 
			} 
		}
 	}

	request.open("POST", "/admin/softwareupdate.php", true);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
} 

YAHOO.util.Event.onContentReady('log_window', getLogs);
