/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.rss.js - RSS Form javascript 			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

ACPRss = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.rss.js");
		
		document.observe("dom:loaded", function(){
			if( $('rss_import_mid') )
			{
				ACPRss.autoComplete = new ipb.Autocomplete( $('rss_import_mid'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		});
	},
	
	showAuthBoxes: function()
	{
		if( $('rss_import_auth_userinfo_1').visible() )
		{
			$('rss_import_auth_userinfo_1').hide();
			$('rss_import_auth_userinfo_2').hide();
		}
		else
		{
			$('rss_import_auth_userinfo_1').show();
			$('rss_import_auth_userinfo_2').show();
		}
	},
	
	validate: function()
	{
		formobj = $('rssimport_validate');
		formobj.value = "1";
		$('rssimport_form').submit();
	}
	
	
};

ACPRss.init();