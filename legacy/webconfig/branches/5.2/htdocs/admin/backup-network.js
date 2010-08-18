<!--
function GetXmlHttp()
{
	if (window.XMLHttpRequest)
		window.xmlHttp = new XMLHttpRequest();
	else if (window.ActiveXObject) {
		// Try ActiveX
		try { 
			window.xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e1) { 
			// first method failed 
			try {
				window.xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e2) {
				alert('No AJAX support detected.  Upgrade your web browser.');
			} 
		}
	}
}

function SubmitCommand()
{
	var url = 'backup-network.php?Action=virtual';
	var command = document.getElementById("command");

	if (!command) return;
	
	if (command.value == 'clear') {
		ConsoleClear();
		command.value = '';
		return;
	}

	ConsoleAppend(command.value + '\n');
	var post = "command=" + encodeURIComponent(command.value);

	GetXmlHttp();

	xmlHttp.onreadystatechange = LoadData;
	xmlHttp.open('POST', url, true);
	xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlHttp.setRequestHeader("Content-length", post.length);
	xmlHttp.setRequestHeader("Connection", "close");
	xmlHttp.send(post);

	command.disabled = true;

	var command_submit = document.getElementById("command_submit");

	if (command_submit) {
		command_submit.disabled = true;
		command_submit.value = 'Sending...';
	}
}

function LoadData()
{
	if (xmlHttp.readyState == 4 || xmlHttp.readyState == 'complete') {
		ConsoleAppend(xmlHttp.responseText);

		var element = document.getElementById("command");

		if (element) {
			element.disabled = false;
			element.focus();
			element.value = '';
		}

		element = document.getElementById("command_submit");

		if (element) {
			element.disabled = false;
			element.value = 'Send';
		}
	}
}

function ConsoleAppend(text)
{
	var element = document.getElementById("console_output");

	if (element) {
		element.value += text;
		element.scrollTop = element.scrollHeight;
	}
}

function ConsoleClear()
{
	var element = document.getElementById("console_output");

	if (element) {
		element.value = '';
		element.scrollTop = element.scrollHeight;
	}
}

function CommandKeypress(evt)
{
	var code = null;

	if (evt.which)
		code = evt.which;
	else if (evt.keyCode)
		code = evt.keyCode;

	if (code == 13) {
		SubmitCommand();
		return false;
	}

	return true;
}

// vi: ts=4
// -->
