// Main function

function getStatus(){
	newXMLHttpRequest();
  	request.onreadystatechange = processPollRequest;
	request.send(null);
}

function processPollRequest() {
	if (!window.request) 
        setTimeout("getStatus()", 2000);
    else if (request.readyState == 4) {
        if (request.status == 200) {
			updateMsgOnBrowser();
        }
        setTimeout("getStatus()", 2000);
    }
}

function updateMsgOnBrowser() {
	if (request.responseXML == null)
		return;
	var message = request.responseXML.getElementsByTagName("raidstatus")[0];

	if (!message) return;

	var message_value = message.firstChild.nodeValue;
	//var timestamp_value = timestamp.firstChild.nodeValue;

	for (var x=0; x < message.getElementsByTagName("devicearray").length; x++) {
		var device = message.getElementsByTagName("devicearray")[x].getElementsByTagName("name")[0].firstChild.nodeValue;
		var name = device.replace(/\/dev\//g, "");
		var status = message.getElementsByTagName("devicearray")[x].getElementsByTagName("code")[0].firstChild.nodeValue;
		var msg = message.getElementsByTagName("devicearray")[x].getElementsByTagName("msg")[0].firstChild.nodeValue;
		var id = document.getElementById(device.replace(/\/dev\//g, ""));
		if (document.getElementById('status_' + name))
			document.getElementById('status_' + name).innerHTML = msg;
		if (document.getElementById('icon_' + name)) {
			// 0 = Clean
			if (status != 0) {
				document.getElementById('icon_' + name).className = 'icondisabled';
				if (status == 2 || status == 3) {
					if (document.getElementById('action_' + name))
						document.getElementById('action_' + name).style.display = 'none';
				}
			} else {
				document.getElementById('icon_' + name).className = 'iconenabled';
				if (document.getElementById('action_' + name))
					document.getElementById('action_' + name).style.display = 'none';

			}
		}
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

	request.open("POST", "/admin/raidstatus.php", true);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
} 
// vim: ts=4
