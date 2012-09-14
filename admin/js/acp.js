
var IPBACP = Class.create({
	
	autocompleteWrap: new Template("<ul id='#{id}' class='ipbmenu_content' style='width: 250px;'></ul>"),
	autocompleteItem: new Template("<li id='#{id}'>#{itemvalue}</li>"),
	autocompleteUrl: '',
	
	
	initialize: function()
	{
		// Tell everyone we are ready
		document.observe("dom:loaded", function(){
			
			Ajax.Responders.register({
			  onLoading: function() {
			    if( !$('ajax_loading') )
				{
					if( !ipb.templates['ajax_loading'] ){ return; }
					$('ipboard_body').insert( ipb.templates['ajax_loading'] );
				}
				
				var effect = new Effect.Appear( $('ajax_loading'), { duration: 0.2 } );
			  },
			  onComplete: function() {
			
				if( !$('ajax_loading') ){ return; }
			    var effect = new Effect.Fade( $('ajax_loading'), { duration: 0.2 } );
			  },
			  onSuccess: function(t){
				if( t.responseText == 'logout' )
				{
					alert( ipb.lang['session_timed_out'] );
					window.location.href = ipb.vars['base_url'];
				}
				
				if( t.responseJSON && t.responseJSON['__session__expired__log__out__'] )
				{
					alert( ipb.lang['session_timed_out'] );
					window.location.href = ipb.vars['base_url'];
				}
			  }
			});
			
			//this.autocompleteUrl = ipb.vars['front_url'] + 'app=core&module=ajax&section=findnames&do=get-member-names&secure_key=' + ipb.vars['md5_hash'] + '&name=';
			this.autocompleteUrl = ipb.vars['base_url'] + 'app=core&module=ajax&section=findnames&do=get-member-names&secure_key=' + ipb.vars['md5_hash'] + '&name=';
			
			/* Check ajax ping dialogues */
			$$('.ajaxWithDialogueTrigger').each( function( elem ) {
				elem.identify();
				elem.observe( 'click', acp.ajaxWithResultClickThrough );
			});
			
			/* License expired notification close */
			if( $('license-close') )
			{
				$('license-close').observe( 'click', function( elem ) {
					var cookieExpire	= new Date();
					cookieExpire.setDate( cookieExpire.getDate()+30 );
					
					ipb.Cookie.set( 'ignore-license-notice', 1, cookieExpire.toUTCString() );
					var effect = new Effect.Fade( $('license_notice_expired'), { duration: 0.3, afterFinish: function(){
						$$('body')[0].removeClassName('expired_license');
					} } );
				} );
			}
			
		}.bind(this));
	},
	
	/**
	 * Sets up handlers for ajaxWithResultAsDialogue with as little intervention as possible
	 */
	ajaxWithResultClickThrough: function( e )
	{
		elem = Event.findElement(e).up('.ajaxWithDialogueTrigger');
		
		if ( $(elem.id) )
		{
			urlBit = $(elem.id).readAttribute( 'ajaxUrl' );
			
			if ( urlBit )
			{
				Event.stop(e);
				ipb.menus.closeAll();
				
				acp.ajaxWithResultAsDialogue( urlBit );
			}
		}
	},
	
	/**
	 * AJAX PHP function is expected to return JSON['error'] (msg) or JSON['ok'] (msg)
	 */
	ajaxWithResultAsDialogue: function( url )
	{
		if ( ! url.match( 'http://') )
		{
			url = ipb.vars['base_url'] + url;
		}
		
		if ( ! url.match( 'md5check' ) )
		{
			url += '&md5check=' + ipb.vars['md5_hash'];
		}
		
		url = url.replace( /&amp;/g, '&' );
		
		Debug.write( url );
			
		/* Send AJAX request */
		new Ajax.Request( url, { method: 'get',
								 evalJSON: 'force',
								 onSuccess: function(t)
								 {
									if ( ! t.responseJSON )
									{
										acp.errorDialogue( 'Oops, something has gone wrong. Sadly I am unable to tell you what. :(' );
									}
									if ( t.responseJSON['error'] )
									{
										acp.errorDialogue( t.responseJSON['error'] );
									}
									else if ( t.responseJSON['ok'] )
									{
										acp.okDialogue( t.responseJSON['ok'] );
									}
								 }
							 } );
	},
	
	/**
	 * Show an error dialogue
	 * @param string
	 * @returns Nothing
	 */
	errorDialogue: function( text )
	{
		content = "<h3>Error</h3><div class='ajax-inline-error' style='height: 40px; text-align:center'><p class='ipsPad'>" + text + "</p></div>";
		
		new ipb.Popup( 'generic__errorDialogue', {  type: 'pane',
													initial: content,
													stem: true,
													hideAtStart: false,
													hideClose: false,
													defer: false,
													warning: true,
													w: 400 } );
	},
	
	/**
	 * Show an OK dialogue
	 * @param string
	 * @returns Nothing
	 */
	okDialogue: function( text )
	{
		content = "<h3>Message</h3><div class='ajax-inline-message' style='height: 40px; text-align:center'><p class='ipsPad'>" + text + "</p></div>";
		
		new ipb.Popup( 'generic__okDialogue', {  type: 'pane',
												 initial: content,
												 stem: true,
												 hideAtStart: false,
												 hideClose: false,
												 defer: false,
												 w: 400 } );
	},
	
	confirmDelete: function( url, msg )
	{
		url = url.replace( /&amp;/g, '&' );
		
		if ( ! msg )
		{
			msg = ipb.lang['ok_to_delete'];
		}
		
		if ( confirm( msg ) )
		{
			window.location.href = url;
		}
		else
		{
			return false;
		}
	},
	
	openWindow: function( url, width, height, name )
	{
		if ( ! name )
		{
			var mydate = new Date();
			name = mydate.getTime();
		}
		
		var Win = window.open( url, name, 'width='+width+',height='+height + ',resizable=1,scrollbars=1,location=no,directories=no,status=no,menubar=no,toolbar=no');
		
		return false;
	},
	
	redirect: function( url, full )
	{
		url = url.replace( /&amp;/g, '&' );
		
		if ( ! full )
		{
			url = ipb.vars['base_url'] + url;
		}
		
		window.location.href = url;
	},
	
	// Todo: language abstraction
	pageJump: function( url_bit, total_posts, per_page )
	{
		pages = 1;
		cur_st = ipb.vars['st'];
		cur_page  = 1;
		
		if ( total_posts % per_page == 0 )
		{
			pages = total_posts / per_page;
		}
		else
		{
			pages = Math.ceil( total_posts / per_page );
		}
		
		msg = ipb.lang['page_multijump'] + pages;
		
		if ( cur_st > 0 )
		{
			cur_page = cur_st / per_page; cur_page = cur_page -1;
		}
		
		show_page = 1;
		
		if ( cur_page < pages )
		{
			show_page = cur_page + 1;
		}
		
		if ( cur_page >= pages )
		{
			show_page = cur_page - 1;
		}
		else
		{
			show_page = cur_page + 1;
		}
		
		userPage = prompt( msg, show_page );
		
		if ( userPage > 0  )
		{
			if ( userPage < 1 )     {    userPage = 1;  }
			if ( userPage > pages ) { userPage = pages; }
			if ( userPage == 1 )    {     start = 0;    }
			else { start = (userPage - 1) * per_page; }

			window.location = url_bit + "&st=" + start;
		}
	},
	
	ajaxRefresh: function( url, text, addtotext )
	{
		new Ajax.Request(
							url + '&__notabsave=1',
							{
								method: 'post',
								onSuccess: function( t )
								{
									var html = t.responseText;
			
									eval( html );
								},
								onFailure: function()
								{
								},
								onException: function()
								{
								}
							}
				);
		
		if ( text )
		{
			// Put it to the top
			if ( addtotext )
			{
				$('refreshbox').innerHTML = text + '<br />' + $('refreshbox').innerHTML;
			}
			else
			{
				$('refreshbox').innerHTML = text;
			}
		}
	},
	
	location_jump: function( url, full )
	{
		url=url.replace( /&amp;/g,'&');
		
		if(full){
			window.location.href=url;
		} else {
			window.location.href=ipb.vars['base_url']+url;
		}
	}
});