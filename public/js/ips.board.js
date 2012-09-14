/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _idx = window.IPBoard;

_idx.prototype.board = {
	_statusClick: 0,
	_statusDefaultValue: '',
	count_sbars: 0,
	count_hidden: 0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.board.js");
		
		document.observe("dom:loaded", function(){
			ipb.board.setUpForumTables();
			ipb.board.initSidebar();
			
			ipb.board._statusDefaultValue = ( $('hookStatusTextField') ) ? $F('hookStatusTextField') : '';
			if( $('updateStatusForm') ){
				$('updateStatusForm').observe( 'submit', ipb.board.statusHookSubmit );
			}
						
		});
	},
	
	/* ------------------------------ */
	/**
	 * Hook: Status update
	*/
	statusHookClick: function()
	{
		if ( ! ipb.board._statusClick )
		{
			if( $('hookStatusTextField') ){
				$('hookStatusTextField').value = '';
			}
			
			ipb.board._statusClick = 1;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Hook: Status update
	*/
	statusHookSubmit: function(e)
	{
		Event.stop(e);
		
		if ( ipb.board._statusClick && $F('hookStatusTextField') && $F('hookStatusTextField') != ipb.board._statusDefaultValue )
		{ 
			$('updateStatusForm').submit();
		}
		else
		{
			return false;
		}
	},

	/* ------------------------------ */
	/**
	 * Inits the forum tables ready for collapsing
	*/
	setUpForumTables: function()
	{
		ipb.delegate.register(".category_block .toggle", ipb.board.toggleCat);
		var cookie			= ipb.Cookie.get('toggleCats');
		
		if( cookie )
		{
			var cookies		= cookie.split( ',' );
			var newCookies	= new Array();
			var resetCookie	= false;
						
			for( var abcdefg=0; abcdefg < cookies.length; abcdefg++ )
			{
				if( cookies[ abcdefg ] )
				{
					if( $( 'category_' + cookies[ abcdefg ] ) )
					{
						var wrapper	= $( 'category_' + cookies[ abcdefg ] ).down('.table_wrap');
						
						wrapper.hide();
						$( 'category_' + cookies[ abcdefg ] ).addClassName('collapsed');
						
						newCookies.push( cookies[ abcdefg ] );
					}
					else
					{
						resetCookie	= true;
					}
				}
			}
			
			if( resetCookie )
			{
				ipb.Cookie.set( 'toggleCats', newCookies.join( ',' ) );
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Show/hide a category
	 * 
	 * @var		{event}		e	The event
	*/
	toggleCat: function(e, elem)
	{
		if( ipb.board.animating ){ return false; }
		
		var remove = $A();
		var catname = $( elem ).up('.category_block');
		var wrapper = $( catname ).down('.table_wrap');
		$( wrapper ).identify(); // IE8 fix
		var catid = catname.id.replace('category_', '');
		
		ipb.board.animating = true;

		// Get cookie
		var cookie = ipb.Cookie.get('toggleCats');
		
		if( cookie == null ){
			cookie = $A();
		} else {
			_cookie = cookie.split(',');
			cookie  = $A();
			
			_cookie.each( function( item )
			{
				if ( item )
				{
					cookie.push( item );
				}
			} );
		}
		
		Effect.toggle( wrapper, 'blind', {duration: 0.4, afterFinish: function(){ ipb.board.animating = false; } } );
		Effect.toggle( wrapper, 'appear', {duration: 0.3});
		
		if( catname.hasClassName('collapsed') )
		{
			catname.removeClassName('collapsed');
			remove.push( catid );
		}
		else
		{
			new Effect.Morph( $(catname), {style: 'collapsed', duration: 0.4, afterFinish: function(){
				$( catname ).addClassName('collapsed');
				ipb.board.animating = false;
			} });
			
			cookie.push( catid );
		}

		/* without not so hot in Chrome */
		_cookie = cookie;
		cookie  = $A();
		
		_cookie.each( function( item )
		{
			ok = true;
			
			remove.each( function( ritem )
			{
				if ( ritem == item )
				{
					ok = false;
				}
			} )
			
			if ( ok === true )
			{
				cookie.push( item );
			}
		} );
			
		cookie = "," + cookie.uniq().join(',') + ",";
		
		ipb.Cookie.set('toggleCats', cookie, 1);
		
		Event.stop( e );
	},
	
	/* ------------------------------ */
	/**
	 * Sets up the sidebar
	*/
	initSidebar: function()
	{
		if( $('toggle_sidebar') )
		{
			$('toggle_sidebar').on('click', ipb.board.toggleSidebar);
			
			if( $('index_stats').visible() ){
				$('toggle_sidebar').update( $('toggle_sidebar').readAttribute("data-open") );
			} else {
				$('toggle_sidebar').update( $('toggle_sidebar').readAttribute("data-closed") );
			}
		}
		
		ipb.board.setUpSideBarBlocks();
	},
	
	toggleSidebar: function(e){
		if( $('index_stats').visible() )
		{
			ipb.Cookie.set('hide_sidebar', 1, 1);
			
			new Effect.Fade( $('index_stats'), { duration: 0.4, afterFinish: function(){
				new Effect.Morph( $('board_index'), { duration: 0.3, style:'no_sidebar' });
			} } );
			
			$('toggle_sidebar').update("&laquo;");
		}
		else
		{
			ipb.Cookie.set('hide_sidebar', 0, 1);
			
			new Effect.Morph( $('board_index'), { duration: 0.3, style:'force_sidebar', afterFinish: function(){
				new Effect.Appear( $('index_stats'), { duration: 0.4, afterFinish: function(){
					$('board_index').removeClassName('no_sidebar').removeClassName('force_sidebar');
				}});
			} } );
			
			$('toggle_sidebar').update("&times;");
		}
		
		Event.stop(e);
	},
	
	/**
	 * Add in collapsable icons
	 */
	setUpSideBarBlocks: function()
	{
		if ( $('index_stats') && $('index_stats').visible() )
		{
			$$('#index_stats h3').each( function(h3)
			{
				$( h3 ).identify();
				
				ipb.board.count_sbars++;
				
				/* Set title - fugly 
				   Here we run through escape first to change foreign chars to %xx with xx being latin, and then we remove % after */
				title = escape( $( h3 ).innerHTML.replace( /<(\/)?([^>]+?)>/g, '' ) ).replace( /%/g, '' );
				title = title.replace( / /g, '' ).replace( /[^a-zA-Z0-9]/, '' ).toLowerCase();

				$( h3 ).up('div').addClassName( '__xX' + title );
				
				/* insert the image */
				$( h3 ).update( '<a href="#" class="ipsSidebar_trigger ipsType_smaller right desc mod_links">' + ipb.lang['hide'] + '</a>' + h3.innerHTML );
			});
			
			cookie = ipb.Cookie.get('toggleSBlocks');
		
			if( cookie )
			{
				var cookies = cookie.split( ',' );
				
				for( var abcdefg=0; abcdefg < cookies.length; abcdefg++ )
				{
					if( cookies[ abcdefg ] )
					{
						var top     = $('index_stats').down('.__xX' + cookies[ abcdefg ] );
						
						if ( top )
						{
							var wrapper	= top.down('._sbcollapsable');
							
							if ( ! wrapper )
							{
								wrapper = top.down('ul');
							}
							
							if ( ! wrapper )
							{
								wrapper = top.down('ol');
							}
							
							if ( ! wrapper )
							{
								wrapper = top.down('div');
							}
							
							if ( ! wrapper )
							{
								wrapper = top.down('table');
							}

							if ( wrapper )
							{
								if ( top.hasClassName('alt') )
								{
									top._isAlt = true;
									top.removeClassName('alt');
								}
								
								ipb.board.count_hidden++;
								top.down('.ipsSidebar_trigger').update( ipb.lang['unhide'] );
								wrapper.hide();
							}
						}
					}
				}
			}
		}
		
		ipb.delegate.register(".ipsSidebar_trigger", ipb.board.toggleSideBarBlock);
	},
	
	/**
	 * Toggle the block
	 */
	toggleSideBarBlock: function( e, elem )
	{
		Event.stop(e);
		elem.identify();
		
		var remove = $A();
		cookie = ipb.Cookie.get('toggleSBlocks');
		
		if( cookie == null ){
			cookie = $A();
		} else {
			_cookie = cookie.split(',');
			cookie  = $A();
			
			_cookie.each( function( item )
			{
				if ( item )
				{
					cookie.push( item );
				}
			} );
		}
		
		/* Test for known class name */
		var top   = elem.up('div');
		
		moo   = top.className.match('__xX([0-9A-Za-z]+)');
		topId = moo[1]; 
		
		block = top.down('._sbcollapsable');
		
		if ( ! $( block ) )
		{
			block = elem.up('div').down('ul');
		}
		
		if ( ! $( block ) )
		{
			block = elem.up('div').down('ol');
		}
		
		if ( ! $( block ) )
		{
			block = elem.up('div').down('div');
		}
		
		if ( ! $( block ) )
		{
			block = elem.up('div').down('table');
		}
		
		if ( $( block ) )
		{
			$( block ).identify();
			
			ipb.board.animating = true;
			
			if ( $( block ).visible() )
			{
				if ( $( top ).hasClassName('alt') )
				{
					$( top )._isAlt = true;
					$( top ).removeClassName('alt');
				}
				
				top.down('.ipsSidebar_trigger').update( ipb.lang['unhide'] );
				ipb.board.count_hidden--;
				Debug.write( "Adding " + topId );
				cookie.push( topId );
			}
			else
			{
				if ( $( top )._isAlt )
				{
					$( top ).addClassName('alt');
				}
				
				top.down('.ipsSidebar_trigger').update( ipb.lang['hide'] );
				
				ipb.board.count_hidden--;
				
				Debug.write( "Removing " + topId );
				remove.push( topId );
			}
		
			Effect.toggle( block, 'blind', {duration: 0.4, afterFinish: function(){ ipb.board.animating = false; } } );
		}
		
		/* without not so hot in Chrome */
		_cookie = cookie;
		cookie  = $A();
		
		_cookie.each( function( item )
		{
			ok = true;
			
			remove.each( function( ritem )
			{
				if ( ritem == item )
				{
					ok = false;
				}
			} )
			
			if ( ok === true )
			{
				cookie.push( item );
			}
		} );
			
		cookie = "," + cookie.uniq().join(',') + ",";
		
		ipb.Cookie.set('toggleSBlocks', cookie, 1);
	}
};

ipb.board.init();