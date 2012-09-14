/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.replacements.js - Replacements functions	*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

ACPSkinSets = {
	
	/* ------------------------------ */
	/**
	 * Setup
	*/
	init: function()
	{
		document.observe("dom:loaded", function(){
		
			$('ipsNewSkin').on('click', this.showAddDialogue.bindAsEventListener(this) );
			
			ipb.delegate.register(".ipsCancelEditor", ACPSkinSets.showCancelDialogue );
			
		}.bind(this) );
	},
	
	/**
	 * Show cancel dialogue
	 */
	showCancelDialogue: function( e, elem )
	{
		Event.stop(e);
		
		new ipb.Popup( 'cancel_editor', {  type: 'pane',
										   initial: cancelEditorDialogue.evaluate( { 'url': elem.readAttribute('href') } ),
										   stem: false,
										   modal: true,
										   hideAtStart: false,
										   hideClose: false,
										   defer: false,
										   w: 400 } );
	},
	
	/**
	 * Fetch and show the pop-up dialogue
	 */
	showAddDialogue: function(e)
	{
		Event.stop(e);
		
		var url 	=  ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=skinsets&amp;do=showAddDialogue&amp;secure_key=" + ipb.vars['md5_hash'];
		
		// Send
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							onSuccess: function(t)
							{
								if ( t.responseText )
								{
									/* Load */
									new ipb.Popup( 'nooSkin', {  type: 'pane',
																 initial: t.responseText,
																 modal: true,
																 stem: true,
																 hideAtStart: false,
																 hideClose: false,
																 defer: false,
																 w: 400 } );
								}
							}
						});
	},	
};

ACPSkinSets.init();