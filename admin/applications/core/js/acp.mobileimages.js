/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.mmobileimages.js 						*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Matt Mecham 							*/
/************************************************/

var _mobileImages = window.IPBACP;

_mobileImages.prototype.mobileImages = {
	popups: [],
	
	/*
	 * Init function
	 */
	init: function()
	{
		Debug.write("Initializing acp.mobileImages.js");
		
		document.observe("dom:loaded", function(){
			acp.mobileImages.initPage();
			
		});
	},
	
	initPage: function()
	{
		$('importXml').observe('click', acp.mobileImages.importPop);
	},
	
	importPop: function( e, elem )
	{
		Event.stop(e);
		
		if ( ! Object.isUndefined(acp.mobileImages.popups['install']) )
		{
			acp.mobileImages.popups['install'].show();
		}
		else
		{
			// Make a new popup
			acp.mobileImages.popups['install'] = new ipb.Popup( 'install', {
																	type: 'modal',
																	initial: ipb.templates['import_xml'].evaluate(),
																	w: '550px',
																	stem: false,
																	hideAtStart: false
																});
		}
	}
};

acp.mobileImages.init();