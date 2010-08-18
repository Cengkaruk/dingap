// Initialize and render the menu bar when it is available in the DOM

YAHOO.util.Event.onContentReady("pcntopnav", function () {

	// Animation object

	var oAnim;

	function onTween(p_sType, p_aArgs, p_oShadow) {

		if (this.cfg.getProperty("iframe")) {
		
			this.syncIframe();
	
		}
	
		if (p_oShadow) {
	
			p_oShadow.style.height = this.element.offsetHeight + "px";
		
		}
	
	}

	function onAnimationComplete(p_sType, p_aArgs, p_oShadow) {

		var oBody = this.body,
			oUL = oBody.getElementsByTagName("ul")[0];

		if (p_oShadow) {
		
			p_oShadow.style.height = this.element.offsetHeight + "px";
		
		}

		YAHOO.util.Dom.setStyle(oUL, "marginTop", "auto");
		YAHOO.util.Dom.setStyle(oBody, "overflow", "visible");
		
		if (YAHOO.env.ua.ie) {
		
			YAHOO.util.Dom.setStyle(oBody, "zoom", "1");
		
		}
		
	}

	// Instantiate and render the menu bar

	var oMenuBar = new YAHOO.widget.MenuBar("pcntopnav", { autosubmenudisplay: true, hidedelay: 750, lazyload: true });

	/*
		 Call the "render" method with no arguments since the markup for 
		 this menu already exists in the DOM.
	*/

	oMenuBar.render();            

});
