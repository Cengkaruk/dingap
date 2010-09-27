// IE weirdness ... the dialog boxes seem to render properly 
// when the dialogbox.render call is done here.  

var alldivs = document.getElementsByTagName("div");

for (var i = 0; i < alldivs.length; i++) {
	if (alldivs[i].id.substring(0,6) == 'dialog') {
		var checkdiv = document.getElementById(alldivs[i].id);
		if (checkdiv.className == 'sb')
			dialogbox.render(alldivs[i]);
	}
}
