/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.forums.js - Forum javascript 			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

ACPForums = {
	showModForm: 0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.forums.js");
		
		document.observe("dom:loaded", function(){
			if( $('modUserName') )
			{
				ACPForums.autoComplete = new ipb.Autocomplete( $('modUserName'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		});
	},
	
	toggleModOptions: function()
	{
		$$('.moddiv').each( function(div){
			if( ACPForums.showModForm )
			{
				div.hide();
			}
			else
			{
				div.show();
			}
		});
		
		$( 'togglemod' ).innerHTML = ACPForums.showModForm ? ipb.lang['forums_showmod'] : ipb.lang['forums_hidemod'];
		ACPForums.showModForm = ACPForums.showModForm == 1 ? 0 : 1;
		return false;
	},
	
	submitModForm: function()
	{
		var submitValue	= '';
		
		$$('input').each( function(cb){
			if( cb.type == 'checkbox' && cb.checked == true )
			{
				var mainname = cb.id.replace( /^(.+?)_.+?$/  , "$1" );
				var idname   = cb.id.replace( /^(.+?)_(.+?)$/, "$2" );
				
				if ( mainname == 'id' )
				{
					submitValue += ',' + idname;
				}
			}
		});
		
		$('modforumids').value	= submitValue;
		return true;
	},
	
	/**
	 * @todo: not used anymore it seems, we need to remove it at some point
	 */
	convert: function()
	{
		$('convert').value = 1;
		$('adminform').submit();
	}
};

ACPForums.init();