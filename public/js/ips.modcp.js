/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.modcp.js - Modcp code					*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

var _modcp = window.IPBoard;

_modcp.prototype.modcp = {
	
	initialtext: '',
	
	init: function()
	{
		Debug.write("Initializing ips.modcp.js");
		
		document.observe("dom:loaded", function(){
			if( $('memberlookup') )
			{
				$('memberlookup').observe( 'focus', function(e) {
					if( $('memberlookup').value == ipb.modcp.initialtext )
					{
						$('memberlookup').value	= '';
					}
				});
				
				var url	= ipb.vars['base_url'] + 'secure_key=' + ipb.vars['secure_hash'] + '&app=core&module=ajax&section=modcp&do=getmembers&name=';
				var ac	= new ipb.Autocomplete( $('memberlookup'), { multibox: false, url: url, goToUrl: true, templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_item'] } } );
			}
		});
	}
};

ipb.modcp.init();