/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.facebook.js - Facebook SDK functionality	*/
/* (c) IPS, Inc 2012							*/
/* -------------------------------------------- */
/* Author: Matt Mecham						*/
/************************************************/

var _facebook = window.IPBoard;

_facebook.prototype.facebook = {
	
	appId: '',
	extendedPerms: 'email,read_stream,publish_stream',
	
	/* ------------------------------ */
	/**
	 * Constructor
	*/
	init: function()
	{
		Debug.write("Initializing ips.facebook.js");
		
		document.observe("dom:loaded", function()
		{
			if ( ! $('fb-root') )
			{
				$('ipboard_body').insert( new Element( 'div', { 'id': 'fb-root' } ) );
			}
		} );
	},
	
	/* ! load */
	/**
	 * Manually run to pass through appID, sets up JS
	 */
	load: function( appId )
	{
		ipb.facebook.appId = appId;
		
		/* Load Facebook JS */
		ipb.facebook.asyncInit();
	},
	
	/* ! asyncInit */
	/**
	 * Loads up JS and inits Facebook JS
	 */
	asyncInit: function()
	{
		ipb.facebook.loadFacebookJs();
		
		window.fbAsyncInit = function()
		{
		    FB.init(
		    {
		      appId      : ipb.facebook.appId,
		      channelUrl : ipb.vars['board_url'] + 'interface/facebook/channel.php', // Channel File
		      status     : true, // check login status
		      cookie     : true, // enable cookies to allow the server to access the session
		      xfbml      : true  // parse XFBML
		    } );
		 };
	},
	
	/* ! connectNow */
	/* Connects logged in user account to Facebook if not logged in
	 */
	connectNow: function()
	{
		/* Already connected? */
		if ( ipb.vars['fb_uid'] == 0 )
		{
			FB.login( function( response )
			{
   				if ( response.authResponse )
   				{
     				var access_token =  FB.getAuthResponse()['accessToken'];
    				
    				Debug.write('Access Token = '+ access_token);
     				
     				/* Check we're all here and accounted for */
     				FB.api('/me', function( response )
     				{
     					Debug.dir( response );
     					var url  = ipb.vars['base_url'] + 'app=core&module=ajax&section=facebook&do=storeFacebookAuthDetails&secure_key=' + ipb.vars['secure_hash'];
						
						new Ajax.Request( url,
										{
											method: 'post',
											parameters: { 'accessToken': access_token, 'userId': response['id'] },
											hideLoader: true,
											onSuccess: function(t)
											{
												/* Nothing needed here, just return correct ID */
												ipb.vars['fb_uid'] = response['id'];
												
												return ipb.vars['fb_uid'];
											}
										});

    				} );
   				}
   				else
   				{
     				Debug.log('User Canceled');
   				}
   			
 			}, {scope: ipb.facebook.extendedPerms } );
		}
		else
		{
			return ipb.vars['fb_uid'];
		}
	},
	
	/* ! loadFacebookJs */
	loadFacebookJs: function()
	{
		var js, id = 'facebook-jssdk';
		var d      = document;
		
		if ( $( id ) )
		{
			return;
		}
 		
 		js = d.createElement('script');
 		
 		js.id = id;

		js.async = true;
		
		js.src = "//connect.facebook.net/en_US/all.js";
		
 		d.getElementsByTagName('head')[0].appendChild(js);
		
	}
	
};

ipb.facebook.init();