function gDisableButtons() {
	var allbuttons = document.getElementsByTagName('button');
	for (var i = 0; i < allbuttons.length; i++) {
		allbuttons[i].disabled = true;
	}
}

function gEnableButtons() {
	var allbuttons = document.getElementsByTagName('button');
	for (var i = 0; i < allbuttons.length; i++) {
		allbuttons[i].disabled = false;
	}
}
