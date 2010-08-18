
function NewXMLHttpRequest(url, async)
{
	var request;

	if (window.XMLHttpRequest) {
		request = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
   		// Try ActiveX
		try { 
			request = new ActiveXObject('Msxml2.XMLHTTP');
		} catch (e1) { 
			// first method failed 
			try {
				request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (e2) {
				// both methods failed
				return false;
			} 
		}
 	}

	request.open('POST', url, async);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	return request;
}

// vim: ts=4
