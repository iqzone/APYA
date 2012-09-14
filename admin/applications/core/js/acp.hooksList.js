/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.hooksList.js - Hooks listing				*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Terabyte	 							*/
/************************************************/

var _hooksList = window.IPBACP;

_hooksList.prototype.hooksList = {
	popups: [],
	
	/*
	 * Init function
	 */
	init: function()
	{
		Debug.write("Initializing acp.hooksList.js");
		
		document.observe("dom:loaded", function(){
			acp.hooksList.initPage();
			
		});
	},
	
	initPage: function()
	{
		$('install_new_hook').observe('click', acp.hooksList.addFolder);
	},
	
	addFolder: function( e, elem )
	{
		Event.stop(e);
		
		if ( ! Object.isUndefined(acp.hooksList.popups['install']) )
		{
			acp.hooksList.popups['install'].show();
		}
		else
		{
			// Make a new popup
			acp.hooksList.popups['install'] = new ipb.Popup( 'install', {
																	type: 'modal',
																	initial: ipb.templates['install_new_hook'].evaluate(),
																	w: '550px',
																	stem: false,
																	hideAtStart: false
																});
		}
	}
};

acp.hooksList.init();