/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.homepage.js - Homepage javascript 		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

ACPHomepage = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.homepage.js");
	},
	
	editMember: function()
	{
		if ( $('members_display_name').value == "" )
		{
			alert("You must enter a username!");
			return false;
		}
		
		return true;
	}
};

ACPHomepage.init();