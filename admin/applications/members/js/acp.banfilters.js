/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.banfilters.js - Banfilters functions		*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Terabyte 							*/
/************************************************/

var _banfilters = window.IPBACP;
_banfilters.prototype.banfilters = {
	popups: null,
	
	init: function()
	{
		Debug.write("Initializing acp.banfilters.js");
		document.observe("dom:loaded", function(){
			/* Setup add filter button */
			if( $('add_banfilter') )
			{
				$('add_banfilter').observe( 'click', acp.banfilters.addNewFilter );
			}
			
			/* Setup delete form */
			if( $('ban-delete') )
			{
				$('ban-delete').observe( 'submit', function( e ){ 
					var checkboxes = $('ban-delete').getElementsByTagName('input');
					
					for( var i = 0; i < checkboxes.length; i++ )
					{
						if( checkboxes[i].checked )
						{
							return true;
						}
					}
					
					alert( ipb.lang['no_ban_to_delete'] );
					
					Event.stop( e );
				});
			}
		});
	},
	
	addNewFilter: function( e )
	{
		Event.stop(e);
		
		if( acp.banfilters.popup )
		{
			acp.banfilters.popup.show();
		}
		else
		{
			acp.banfilters.popup = new ipb.Popup('add_banfilter_popup', { type: 'pane', modal: false, hideAtStart: false, w: '600px', initial: ipb.templates['add_banfilter'] } );
		}
	}
};

acp.banfilters.init();