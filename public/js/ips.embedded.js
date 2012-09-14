/**
 * Embed IP.Board via iFrame
 * Using old school JS, none of your fancy stuffs!
 *
 * (c) 2011 Invision Power Services, Inc.
 * By Matt Mecham
 */

var _idx = window.IPBoard;

_idx.prototype.embedded = {
	curHeight: null,
	remoteMsg: null,
	inFrame:   ( top !== self ) ? true : false,
	ajaxIsLoading: null,
	
	init: function()
	{
		if ( this.inFrame )
		{
			/* We need to do two things: set up a setLocation and setMessage functions
			 * if we have everything > IE7 this is easy, see...
			 * So lets pretent IE7 does NOT exist.
			 */
			if ( 'postMessage' in parent )
			{
				this.setMessage = function( message, target )
				{
					Debug.write("SENDING msg: " + message + " to " + target );
					return parent.postMessage( message, target );
				};
				
				this.setLocation = function( newLocation )
				{
					Debug.write( "Setting location to " + newLocation );
					parent.window.frames[0].location.replace( newLocation );
				};
				
				this.setHeight = function()
				{
			    	var newHeight = document.body.offsetHeight;
			    	
			    	if ( newHeight != this.curHeight )
			    	{
			            this.curHeight = newHeight;
			            
			            this.setMessage('height:' + this.curHeight, '*');
			    	}
				};
				
				this.checkAjaxStatus = function()
				{
					if ( $('ajax_loading') )
					{
						if ( $('ajax_loading').visible() )
						{
							this.ajaxIsLoading = true;
							this.setMessage( 'ajaxLoading', '*' );
						}
						else if ( this.ajaxIsLoading && ! $('ajax_loading').visible() )
						{
							this.ajaxIsLoading = false;
							this.setMessage( 'ajaxDone', '*' );
						}
					}
				}
				
				this.aClicked = function( e, elem )
				{
					href   = elem.readAttribute('href');
					rel    = '';
					target = '';
					
					try
					{
						rel    = elem.readAttribute('rel');
						target = elem.readAttribute('target');
					} catch(e){}
					
					if ( ( href.substr(0, 4) == 'http' ) && rel != 'external' && target != '_blank' )
					{
						
						/* One last check */
						if ( ipb.vars['board_url'] == href.substr( 0, ipb.vars['board_url'].length ) )
						{
							Event.stop(e);
							var path      = href.substr( ipb.vars['board_url'].length );
							var hashIndex = path.indexOf('#');
				            var hash      = '';
				            
				            if ( hashIndex > -1 )
				            {
				               hash = path.substr( hashIndex );
				               path = path.substr( 0, hashIndex );
				            }
				            
				            ipb.embedded.setMessage('location:' + path, '*');
				            ipb.embedded.setLocation( ipb.vars['board_url'] + path + hash );
						}
					}
					
					return true;
				};
				
				/* Intercept a links */
				ipb.delegate.register( "a", this.aClicked );
				
				/* Routintely check height */
				setInterval( this.setHeight.bindAsEventListener(this), 300 );
				
				/* And ajax status */
				setInterval( this.checkAjaxStatus.bindAsEventListener(this), 300 );
				
				Event.observe( document, 'unload', function() { this.setMessage('unload', '*').bindAsEventListener(this); } );
			}
		}
	}
};

ipb.embedded.init();
