var _signin = window.IPBoard;

_signin.prototype.signin = {
	init: function()
	{
		Debug.write("Initializing ips.signin.js");
		
		document.observe("dom:loaded", function(){
			if( $('live_signin') ){
				$('live_signin').hide();
				$('live_open').observe('click', ipb.signin.toggleLive);
				$('live_close').observe('click', ipb.signin.toggleLive);
			}
			
			if( $('login') ){
				$('login').observe('submit', ipb.signin.validateLogin );
			}
			
			if( $('ips_username') ){
				$('ips_username').focus();
			}
		});
	},

	/* ------------------------------ */
	/**
	 * Toggles the Windows Live login field
	 * 
	 * @param	{event}		e	The event
	*/
	toggleLive: function(e)
	{
		if( $('live_signin').visible() )
		{
			$('live_signin').hide();
			$('regular_signin').show();
		}
		else
		{
			$('live_signin').show();
			$('regular_signin').hide();
		}
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Form validation for login
	 * 
	 * @param	{event}		e	The event
	 * @return	@e void
	*/
	validateLogin: function(e)
	{
		if( !ipb.signin.isFilled( $('ips_username') ) )
		{
			alert( ipb.lang['signin_nosigninname'] );
			Event.stop(e);
			return;
		}
		if( !ipb.signin.isFilled( $('ips_password') ) )
		{
			alert( ipb.lang['signin_nopassword'] );
			Event.stop(e);
			return;
		}		
	},
	
	/* ------------------------------ */
	/**
	 * Validate that content is filled
	 * 
	 * @param	{event}		e	The event
	 * @return	@e void
	 * @SKINNOTE 	Stop using this duplicated code and use ipb.js validate object
	*/
	isFilled: function( obj )
	{
		if( !obj.value )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
};

ipb.signin.init();