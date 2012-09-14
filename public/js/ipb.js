//************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ipb.js - Global code							*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

/* ===================================================================================================== */
/* IPB3 JS Debugging */

var Debug = {
	write: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.log( text );
		}
		/*else if( jsDebug )
		{
			if( !$('_inline_debugging') ){
				var _inline_debug =  new Element('div', { id: '_inline_debugging' }).setStyle('background: rgba(0,0,0,0.7); color: #fff; padding: 10px; width: 97%; height: 150px; position: absolute; bottom: 0; overflow: auto; z-index: 50000').show();
				
				if( !Object.isUndefined( $$('body')[0] ) ){
					$$('body')[0].insert( _inline_debug );
				}
			}
			
			try {
				$('_inline_debugging').innerHTML += "<br />" + text;
			} catch(err){}
		}*/
	},
	dir: function( values ){
		if( jsDebug && !Object.isUndefined(window.console) && ! Prototype.Browser.IE && ! Prototype.Browser.Opera ){
			console.dir( values );
		}
	},
	error: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.error( text );
		}
	},
	warn: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.warn( text );
		}
	},
	info: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.info( text );
		}
	}
};

/* Set up version specifics */
Prototype.Browser.IE6 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 6;
Prototype.Browser.IE7 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 7;
Prototype.Browser.IE8 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 8;
Prototype.Browser.IE9 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 9;

/* Add in stuff prototype does not include */
Prototype.Browser.Chrome = Prototype.Browser.WebKit && ( navigator.userAgent.indexOf('Chrome/') > -1 );

/* ===================================================================================================== */
/* OVERWRITE getOffsetParent TO FIX TD ISSUE */

function isBody(element) {
  return element.nodeName.toUpperCase() === 'BODY';
}

function isHtml(element) {
  return element.nodeName.toUpperCase() === 'HTML';
}

function isDocument(element) {
  return element.nodeType === Node.DOCUMENT_NODE;
}

function isDetached(element) {
  return element !== document.body &&
   !Element.descendantOf(element, document.body);
}

Element.Methods.getOffsetParent = function(element) {
	element = $(element);
	
	if (isDocument(element) || isDetached(element) || isBody(element) || isHtml(element))
	return $(document.body);
	
	if( Prototype.Browser.IE ){
		if (element.offsetParent && element.offsetParent != document.body && Element.getStyle( element.offsetParent, 'position' ) != 'static') return $(element.offsetParent);
		if (element == document.body) return $(element);
	} else {	
		var isInline = (Element.getStyle(element, 'display') === 'inline');
		if (!isInline && element.offsetParent && Element.getStyle(element.offsetParent,'position') != 'static') return $(element.offsetParent);
	}

	while ((element = element.parentNode) && element !== document.body) {
		if (Element.getStyle(element, 'position') !== 'static') {
			return isHtml(element) ? $(document.body) : $(element);
		}
	}

	return $(document.body);
}

/*Event.observe( window, 'load', function(e){
	Element.Methods.getOffsetParent = function( element ){
		//alert( "Using overloaded getOffsetParent" );
		if (element.offsetParent && element.offsetParent != document.body) return $(element.offsetParent);
		if (element == document.body) return $(element);

		while ((element = element.parentNode) && element != document.body)
		  if (Element.getStyle(element, 'position') != 'static')
		    return $(element);

		return $(document.body);
	};
});

function _getOffsetParent( element )
{
	//alert( "Using overloaded getOffsetParent" );
	if (element.offsetParent && element.offsetParent != document.body) return $(element.offsetParent);
	if (element == document.body) return $(element);

	while ((element = element.parentNode) && element != document.body )
	  if (Element.getStyle(element, 'position') != 'static')
	    return $(element);

	return $(document.body);
}*/

/* ===================================================================================================== */
/* MAIN ROUTINE */

window.IPBoard = Class.create({
	namePops: [],
	topicPops: [],
	vars: [],
	lang: [],
	templates: [],
	editors: $A(),
	initDone: false,
	
	initialize: function()
	{
		Debug.write("IPB js is loading...");
		
		document.observe("dom:loaded", function(){
			
			this.Cookie.init();
			// Show a little loading graphic
			Ajax.Responders.register({
			  onLoading: function( handler ) {
				if( !Object.isUndefined( handler['options']['hideLoader'] ) && handler['options']['hideLoader'] != false ){
					return;
				}
				
			    if( !$('ajax_loading') ){
					if( !ipb.templates['ajax_loading'] ){ return; }
					$('ipboard_body').insert( ipb.templates['ajax_loading'] );
				}
				
				var effect = new Effect.Appear( $('ajax_loading'), { duration: 0.2 } );
			  },
			  onComplete: function() {
			
				if( !$('ajax_loading') || !$('ajax_loading').visible() ){ return; }
			    var effect = new Effect.Fade( $('ajax_loading'), { duration: 0.2 } );
			    
			    if ( ! Object.isUndefined( ipb.hoverCard ) ){
			    	ipb.hoverCardRegister.postAjaxInit();
				}
			    
			    /* General links to functions */
				$$("[data-clicklaunch]").invoke('clickLaunch');
			  },
			  onSuccess: function() {
			    if ( ! Object.isUndefined( ipb.hoverCard ) ){
			    	ipb.hoverCardRegister.postAjaxInit();
				}
			  },
			  onFailure: function( t )
			  {
				 if( !$('ajax_loading') || !$('ajax_loading').visible() ){ return; }
				 var effect = new Effect.Fade( $('ajax_loading'), { duration: 0.2 } );
				  
				 if ( ! Object.isUndefined( ipb.global ) )
				 {
					 ipb.global.showInlineNotification( ipb.lang['ajax_failure'] );
				 }
				  
			  },
			  onException: function( t, exception )
			  {
				  if( !$('ajax_loading') || !$('ajax_loading').visible() ){ return; }
				  var effect = new Effect.Fade( $('ajax_loading'), { duration: 0.2 } );
				  
				  Debug.error( exception );
				  
				  if ( ! Object.isUndefined( ipb.global ) )
				  {
					  //ipb.global.showInlineNotification( ipb.lang['ajax_failure'] );
				  }
			  }
			});
			
			// Initialize our delegation manager
			ipb.delegate.initialize();			
			ipb.initDone = true;
			
		}.bind(this));
	},
	
	positionCenter: function( elem, dir )
	{
		if( !$(elem) ){ return; }
		elem_s = $(elem).getDimensions();
		window_s = document.viewport.getDimensions();
		window_offsets = document.viewport.getScrollOffsets();

		center = { 	left: ((window_s['width'] - elem_s['width']) / 2),
					 top: ((window_s['height'] - elem_s['height']) / 2)
				};
		
		if( typeof(dir) == 'undefined' || ( dir != 'h' && dir != 'v' ) )
		{
			$(elem).setStyle('top: ' + center['top'] + 'px; left: ' + center['left'] + 'px');
		}
		else if( dir == 'h' )
		{
			$(elem).setStyle('left: ' + center['left'] + 'px');
		}
		else if( dir == 'v' )
		{
			$(elem).setStyle('top: ' + center['top'] + 'px');
		}
		
		$(elem).setStyle('position: fixed');
	},
	showModal: function()
	{
		if( !$('ipb_modal') )
		{
			this.createModal();
		}
		this.modal.show();
	},
	hideModal: function()
	{
		if( !$('ipb_modal') ){ return; }
		this.modal.hide();		
	},
	createModal: function()
	{
		this.modal = new Element('div', { id: 'ipb_modal' } ).hide().addClassName('modal');
		this.modal.setStyle("width: 100%; height: 100%; position: fixed; top: 0px; left: 0px; overflow: hidden; z-index: 1000; opacity: 0.2");
		$('ipboard_body').insert({bottom: this.modal});
	},
	editorInsert: function( content, editorid )
	{
		// If no editor id supplied, lets use the first one
		if( !editorid )	{
			var editor = ipb.textEditor.getEditor();
		} else {
			var editor = ipb.textEditor.getEditor(editorid);
		}
		
		if( Object.isUndefined( editor ) )
		{
			/* Get current */
			var editor = ipb.textEditor.getEditor();
		}
		
		editor.insert( content );
	}
});

/* ===================================================================================================== */
/* IPB3 Delegation manager */
/* Simple class that allows us to specify css selectors and an associated function to run */
/* when an appropriate element is clicked */

IPBoard.prototype.delegate = {
	store: $A(),
	
	initialize: function()
	{
		document.observe('click', function(e){

			if( Event.isLeftClick(e) || Prototype.Browser.IE || ipb.vars['is_touch'] ) // IE doesnt provide isLeftClick info for click event, touch devices either
			{
				var elem = null;
				var handler = null;
			
				var target = ipb.delegate.store.find( function(item){
					elem = e.findElement( item['selector'] );
					if( elem ){
						handler = item;
						return true;
					} else {
						return false;
					}
				});
			
				if( !Object.isUndefined( target ) )
				{				
					if( handler )
					{
						Debug.write("Firing callback for selector " + handler['selector'] );
						handler['callback']( e, elem, handler['params'] );
					}
				}
			}
        });
	},
	
	register: function( selector, callback, params )
	{
		ipb.delegate.store.push( { selector: selector, callback: callback, params: params } );
	}
};

/* ===================================================================================================== */
/* IPB3 Cookies */

/* Meow */
IPBoard.prototype.Cookie = {
	store: [],
	initDone: false,
	
	set: function( name, value, sticky )
	{
		var expires = '';
		var path = '/';
		var domain = '';
		
		if( !name )
		{
			return;
		}
		
		if( sticky )
		{	
			if( sticky == 1 )
			{
				expires = "; expires=Wed, 1 Jan 2020 00:00:00 GMT";
			}
			else if( sticky == -1 ) // Delete
			{
				expires = "; expires=Thu, 01-Jan-1970 00:00:01 GMT";
			}
			else if( sticky.length > 10 )
			{
				expires = "; expires=" + sticky;
			}
		}
		if( ipb.vars['cookie_domain'] )
		{
			domain = "; domain=" + ipb.vars['cookie_domain'];
		}
		if( ipb.vars['cookie_path'] )
		{
			path = ipb.vars['cookie_path'];
		}
		
		document.cookie = ipb.vars['cookie_id'] + name + "=" + escape( value ) + "; path=" + path + expires + domain + ';';
		
		ipb.Cookie.store[ name ] = value;
		
		Debug.write( "Set cookie: " + ipb.vars['cookie_id'] + name + "=" + value + "; path=" + path + expires + domain + ';' );
	},
	get: function( name )
	{
		/* Init done yet? */
		if ( ipb.Cookie.initDone !== true )
		{
			ipb.Cookie.init();
		}
		
		if( ipb.Cookie.store[ name ] )
		{
			return ipb.Cookie.store[ name ];
		}
		
		return '';
	},
	doDelete: function( name )
	{
		Debug.write("Deleting cookie " + name);
		ipb.Cookie.set( name, '', -1 );
	},
	init: function()
	{
		// Already init?
		if ( ipb.Cookie.initDone )
		{
			return true;
		}
		
		// Init cookies by pulling in document.cookie
		skip = ['session_id', 'ipb_admin_session_id', 'member_id', 'pass_hash'];
		cookies = $H( document.cookie.replace(" ", '').toQueryParams(";") );
	
		if( cookies )
		{
			cookies.each( function(cookie){
				cookie[0] = cookie[0].strip();
				
				if( ipb.vars['cookie_id'] != '' )
				{
					if( !cookie[0].startsWith( ipb.vars['cookie_id'] ) )
					{
						return;
					}
					else
					{
						cookie[0] = cookie[0].replace( ipb.vars['cookie_id'], '' );
					}
				}
				
				if( skip[ cookie[0] ] )
				{
					return;
				}
				else
				{
					ipb.Cookie.store[ cookie[0] ] = unescape( cookie[1] || '' );
					Debug.write( "Loaded cookie: " + cookie[0] + " = " + cookie[1] );
				}				
			});
		}
		
		ipb.Cookie.initDone = true;	
	}
};

/* ===================================================================================================== */
/* Form validation */

IPBoard.prototype.validate = {
	// Checks theres actually a value
	isFilled: function( elem )
	{
		if( !$( elem ) ){ return null; }
		return !$F(elem).blank();
	},
	isNumeric: function( elem )
	{
		if( !$( elem ) ){ return null; }
		return $F(elem).match( /^[\d]+?$/ );
	},
	isMatching: function( elem1, elem2 )
	{
		if( !$( elem1 ) || !$( elem2 ) ){ return null; }
		return $F(elem1) == $F(elem2);
	},
	email: function( elem )
	{
		if( !$( elem ) ){ return null; }
		if( $F( elem ).match( /^.+@.+\..{2,4}$/ ) ){
			return true;
		} else {
			return false;
		}
	}
};

/* ===================================================================================================== */
/* AUTOCOMPLETE */

IPBoard.prototype.Autocomplete = Class.create( {
	
	initialize: function(id, options)
	{
		this.id = $( id ).id;
		this.timer = null;
		this.last_string = '';
		this.internal_cache = $H();
		this.pointer = 0;
		this.items = $A();
		this.observing = true;
		this.objHasFocus = null;
		this.options = Object.extend({
			min_chars: 3,
			multibox: false,
			global_cache: false,
			goToUrl: false,
			classname: 'ipb_autocomplete',
			templates: 	{ 
							wrap: new Template("<ul id='#{id}'></ul>"),
							item: new Template("<li id='#{id}' data-url='#{url}'>#{itemvalue}</li>")
						}
		}, arguments[1] || {});
		
		//-----------------------------------------
		
		if( !$( this.id ) ){
			Debug.error("Invalid textbox ID");
			return false;
		}
		
		this.obj = $( this.id );
		
		if( !this.options.url )
		{
			Debug.error("No URL specified for autocomplete");
			return false;
		}
		
		$( this.obj ).writeAttribute('autocomplete', 'off');
		
		this.buildList();
		
		// Observe keypress
		$( this.obj ).observe('focus', this.timerEventFocus.bindAsEventListener( this ) );
		$( this.obj ).observe('blur', this.timerEventBlur.bindAsEventListener( this ) );
		$( this.obj ).observe('keypress', this.eventKeypress.bindAsEventListener( this ) );
	},
	
	eventKeypress: function(e)
	{	
		if( ![ Event.KEY_TAB, Event.KEY_UP, Event.KEY_DOWN, Event.KEY_LEFT, Event.KEY_RIGHT, Event.KEY_RETURN ].include( e.keyCode ) ){
			return; // Not interested in anything else
		}
		
		// & and up key are both keycode 38. So if we're holding shift, ignore this
		console.log( e.shiftKey );
		if ( e.shiftKey === true ) {
			return;
		}
		
		if( $( this.list ).visible() )
		{
			switch( e.keyCode )
			{
				case Event.KEY_TAB:
				case Event.KEY_RETURN:
					this.selectCurrentItem(e);
				break;
				case Event.KEY_UP:
				case Event.KEY_LEFT:
					this.selectPreviousItem(e);
				break;
				case Event.KEY_DOWN:
				case Event.KEY_RIGHT:
					this.selectNextItem(e);
				break;
			}
			
			Event.stop(e);
		}
	},
	
	// MOUSE & KEYBOARD EVENT
	selectCurrentItem: function(e)
	{
		var current = $( this.list ).down('.active');
		this.unselectAll();
		
		if( !Object.isUndefined( current ) )
		{
			var itemid = $( current ).id.replace( this.id + '_ac_item_', '');
			if( !itemid ){ return; }
			
			// Go to URL?
			if( this.options.goToUrl && $( current ).readAttribute('data-url') )
			{
				window.location = $( current ).readAttribute('data-url');
				return false;
			}

			// Get value
			var value = this.items[ itemid ]
				.replace('&amp;', '&')
				.replace( /&#39;/g, "'" )
				.replace( /&gt;/g, '>' )
				.replace( /&lt;/g, '<' )
				.replace( /&#33;/g, '!' );

			if( this.options.multibox )
			{
				// some logic to get current name
				if( $F( this.obj ).indexOf(',') !== -1 )
				{
					var pieces = $F( this.obj ).split(',');
					pieces[ pieces.length - 1 ] = '';

					$( this.obj ).value = pieces.join(',') + ' ';
				}
				else
				{
					$( this.obj ).value = '';
					$( this.obj ).focus();
				}
				
				$( this.obj ).value = $F( this.obj ) + value + ', ';
			}
			else
			{
				$( this.obj ).value = value;
				
				var effect = new Effect.Fade( $(this.list), { duration: 0.3 } );
				//this.observing = false;
			}	
		}
		
		$( this.obj ).focus();
		
		/* Stop cursor jumping back when adding input */
		if ( Prototype.Browser.IE )
		{
			if ( $( this.obj ).createTextRange )
			{
				var r = $( this.obj ).createTextRange();
				r.moveStart("character", $( this.obj ).value.length);
				r.select();
			}
        }
	},
	
	// MOUSE EVENT
	selectThisItem: function(e)
	{
		this.unselectAll();
		
		var items = $( this.list ).immediateDescendants();
		var elem = Event.element(e);
		
		// Find the element
		while( !items.include( elem ) )
		{
			elem = elem.up();
		}
		
		$( elem ).addClassName('active');
	},
	
	// KEYBOARD EVENT
	selectPreviousItem: function(e)
	{
		var current = $( this.list ).down('.active');
		this.unselectAll();
		
		if( Object.isUndefined( current ) )
		{
			this.selectFirstItem();
		}
		else
		{
			var prev = $( current ).previous();
			
			if( prev ){
				$( prev ).addClassName('active');
			}
			else
			{
				this.selectLastItem();
			}
		}
	},
	
	// KEYBOARD EVENT
	selectNextItem: function(e)
	{
		// Get the current item
		var current = $( this.list ).down('.active');
		this.unselectAll();
		
		if( Object.isUndefined( current ) ){
			this.selectFirstItem();
		}
		else
		{
			var next = $( current ).next();
			
			if( next ){
				$( next ).addClassName('active');
			}
			else
			{
				this.selectFirstItem();
			}
		}
	},
	
	// INTERNAL CALL
	selectFirstItem: function()
	{
		if( !$( this.list ).visible() ){ return; }
		this.unselectAll();
		
		$( this.list ).firstDescendant().addClassName('active');
	},
	
	// INTERNAL CALL
	selectLastItem: function()
	{
		if( !$( this.list ).visible() ){ return; }
		this.unselectAll();
		
		var d = $( this.list ).immediateDescendants();
		var l = d[ d.length -1 ];
		
		if( l )
		{
			$( l ).addClassName('active');
		}
	},
	
	unselectAll: function()
	{
		$( this.list ).childElements().invoke('removeClassName', 'active');
	},
	
	// Ze goggles are blurry!
	timerEventBlur: function(e)
	{ 
		window.clearTimeout( this.timer );
		this.eventBlur.bind(this).delay( 0.6, e );
	},
	
	// Phew, ze goggles are focussed again
	timerEventFocus: function(e)
	{
		this.timer = this.eventFocus.bind(this).delay(0.4, e);
	},
	
	eventBlur: function(e)
	{
		this.objHasFocus = false;
		
		if( $( this.list ).visible() )
		{
			var effect = new Effect.Fade( $(this.list), { duration: 0.3 } );
		}
	},
	
	eventFocus: function(e)
	{
		if( !this.observing ){ Debug.write("Not observing keypress"); return; }
		this.objHasFocus = true;
		
		// Keep loop going
		this.timer = this.eventFocus.bind(this).delay(0.6, e);
		
		var curValue = this.getCurrentName();
		if( curValue == this.last_string ){ return; }
		
		if( curValue.length < this.options.min_chars ){
			// Hide list if necessary
			if( $( this.list ).visible() )
			{
				var effect = new Effect.Fade( $( this.list ), { duration: 0.3, afterFinish: function(){ $( this.list ).update(); }.bind(this) } );
			}
			
			return;
		}
		
		this.last_string = curValue;
		
		// Cached?
		json = this.cacheRead( curValue );
		
		if( json == false ){
			// No results yet, get them
			var request = new Ajax.Request( this.options.url + escape( curValue ),
								{
									method: 'get',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										if( Object.isUndefined( t.responseJSON ) )
										{
											// Well, this is bad.
											Debug.error("Invalid response returned from the server");
											return;
										}
										
										if( t.responseJSON['error'] )
										{
											switch( t.responseJSON['error'] )
											{
												case 'requestTooShort':
													Debug.warn("Server said request was too short, skipping...");
												break;
												default:
													Debug.error("Server returned an error: " + t.responseJSON['error']);
												break;
											}
											
											return false;
										}
										
										if( t.responseText != "[]" )
										{
										
											// Seems to be OK!
											this.cacheWrite( curValue, t.responseJSON );
											this.updateAndShow( t.responseJSON );
										}
									}.bind( this )
								}
							);
		}
		else
		{
			this.updateAndShow( json );
		}
		
		//Debug.write( curValue );
	},
	
	updateAndShow: function( json )
	{
		if( !json ){ return; }
		
		this.updateList( json );

		if( !$( this.list ).visible() && this.objHasFocus )
		{
			Debug.write("Showing");
			var effect = new Effect.Appear( $( this.list ), { duration: 0.3, afterFinish: function(){ this.selectFirstItem(); }.bind(this) } );
		}
	},
	
	cacheRead: function( value )
	{
		if( this.options.global_cache != false )
		{
			if( !Object.isUndefined( this.options.global_cache.get( value ) ) ){
				Debug.write("Read from global cache");
				return this.options.global_cache.get( value );
			}
		}
		else
		{
			if( !Object.isUndefined( this.internal_cache.get( value ) ) ){
				Debug.write("Read from internal cache");
				return this.internal_cache.get( value );
			}
		}
		
		return false;
	},
	
	cacheWrite: function( key, value )
	{
		if( this.options.global_cache !== false ){
			this.options.global_cache.set( key, value );
		} else {
			this.internal_cache.set( key, value );
		}
		
		return true;
	},
	
	getCurrentName: function()
	{
		if( this.options.multibox )
		{
			// some logic to get current name
			if( $F( this.obj ).indexOf(',') === -1 ){
				return $F( this.obj ).strip();
			}
			else
			{
				var pieces = $F( this.obj ).split(',');
				var lastPiece = pieces[ pieces.length - 1 ];
				
				return lastPiece.strip();
			}
		}
		else
		{
			return $F( this.obj ).strip();
		}
	},
	
	buildList: function()
	{
		if( $( this.id + '_ac' ) )
		{
			return;
		}
		
		var finalPos = {};
		
		// Position menu to keep it on screen
		var sourcePos = $( this.id ).viewportOffset();
		var sourceDim = $( this.id ).getDimensions();
		var delta = [0,0];
		var parent = null;
		var screenScroll = document.viewport.getScrollOffsets();

		var ul = this.options.templates.wrap.evaluate({ id: this.id + '_ac' });
		
		/* In a modal pop up? */
		var test = $( this.id ).up('.popupWrapper');
		
		if ( ! Object.isUndefined( test ) && test.getStyle('position') == 'fixed' )
		{
			$(this.id).up().insert( {bottom: ul} );
			
			parent = $( this.id ).getOffsetParent();
			delta = [ parseInt( parent.getStyle('left') / 2 ), parseInt( parent.getStyle('top') / 2 ) ];
			
			finalPos['left'] = delta[0];
			finalPos['top'] = delta[1] + screenScroll.top;
					
			/* Make it appear over */
			$( this.id + '_ac' ).setStyle( { 'zIndex': 10002 } );
		}
		else
		{
			$$('body')[0].insert( {bottom: ul} );
			
			if ( Element.getStyle( $( this.id ), 'position') == 'absolute')
			{
				parent = $( this.id ).getOffsetParent();
				delta = [ parseInt( parent.getStyle('left') ), parseInt( parent.getStyle('top') ) ];
		    }
	
			finalPos['left'] = sourcePos[0] - delta[0];
			finalPos['top'] = sourcePos[1] - delta[1] + screenScroll.top;
		}
		
		// Now try and keep it on screen
		finalPos['top'] = finalPos['top'] + sourceDim.height;
		
		$( this.id + '_ac' ).setStyle('position: absolute; top: ' + finalPos['top'] + 'px; left: ' + finalPos['left'] + 'px;').hide();
		
		
		this.list = $( this.id + '_ac' );
	},
	
	updateList: function( json )
	{	
		if( !json || !$( this.list ) ){ return; }
	
		var newitems ='';
		this.items = $A();
		
		json = $H( json );
		
		json.each( function( item )
			{		
				var li = this.options.templates.item.evaluate({ id: this.id + '_ac_item_' + item.key,
				 												itemid: item.key,
				 												itemvalue: item.value['showas'] || item.value['name'],
				 												img: item.value['img'] || '',
																img_w: item.value['img_w'] || '',
																img_h: item.value['img_h'] || '',
																url: item.value['url'] || ''
															});
				this.items[ item.key ] = item.value['name'];
	
				newitems = newitems + li;
			}.bind(this)
		);
		
		$( this.list ).update( newitems );
		$( this.list ).immediateDescendants().each( function(elem){
			$( elem ).observe('mouseover', this.selectThisItem.bindAsEventListener(this));
			$( elem ).observe('click', this.selectCurrentItem.bindAsEventListener(this));
			$( elem ).setStyle('cursor: pointer');
		}.bind(this));
		
		if( $( this.list ).visible() )
		{
			this.selectFirstItem();
		}
	}
});

/* ===================================================================================================== */
/* Extended objects */

// Extend RegExp with escape
Object.extend( RegExp, { 
	escape: function(text)
	{
		if (!arguments.callee.sRE)
		{
		   	var specials = [ '/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\', '$' ];
		   	//arguments.callee.sRE = new RegExp( '(\\' + specials.join('|\\') + ')' ); // IMPORTANT: dont use g flag
		   	arguments.callee.sRE = new RegExp( '(\\' + specials.join('|\\') + ')', 'g' );
		}
		return text.replace(arguments.callee.sRE, '\\$1');
	}
});

// Extend String with URL UTF-8 escape
String.prototype.encodeUrl = function()
{
		text = this;
		var regcheck = text.match(/[\x90-\xFF]/g);
		
		if ( regcheck )
		{
			for (var i = 0; i < regcheck.length; i++)
			{
				//text = text.replace(regcheck[i], '%u00' + (regcheck[i].charCodeAt(0) & 0xFF).toString(16).toUpperCase());
			}
		}
	
		return escape(text).replace(/\+/g, "%2B").replace(/%20/g, '+').replace(/\*/g, '%2A').replace(/\//g, '%2F').replace(/@/g, '%40');
};

// Extend String with URL UTF-8 escape - duplicated so it can be changed from above
String.prototype.encodeParam = function()
{
		text = this;
		var regcheck = text.match(/[\x90-\xFF]/g);

		if ( regcheck )
		{
			for (var i = 0; i < regcheck.length; i++)
			{
				//text = text.replace(regcheck[i], '%u00' + (regcheck[i].charCodeAt(0) & 0xFF).toString(16).toUpperCase());
			}
		}
		
		/* Return just text as it is then encoded by prototype lib */
		return escape(text).replace(/\+/g, "%2B");
};


// Extend Date object with a function to check for DST
Date.prototype.getDST = function()
{
	var beginning	= new Date( "January 1, 2008" );
	var middle		= new Date( "July 1, 2008" );
	var difference	= middle.getTimezoneOffset() - beginning.getTimezoneOffset();
	var offset		= this.getTimezoneOffset() - beginning.getTimezoneOffset();
	
	if( difference != 0 )
	{
		/* Hemisphere check */
		if ( difference < 0 )
		{
			/* Northern */
			return (difference == offset) ? 1 : 0;
		}
		else
		{
			/* Southern */
			return (difference != offset) ? 1 : 0;
		}
	}
	else
	{
		return 0;
	}
};

/* ==================================================================================================== */
/* IPB3 JS Loader */

var Loader = {
	require: function( name )
	{
		document.write("<script type='text/javascript' src='" + name + ".js'></script>");
	},
	boot: function()
	{
		$A( document.getElementsByTagName("script") ).findAll(
			function(s)
			{
  				return (s.src && s.src.match(/ipb\.js(\?.*)?$/));
			}
		).each( 
			function(s) {
  				var path = s.src.replace(/ipb\.js(\?.*)?$/,'');
  				var includes = s.src.match(/\?.*load=([a-zA-Z0-9_,]*)/);
				if( ! Object.isUndefined(includes) && includes != null && includes[1] )
				{
					includes[1].split(',').each(
						function(include)
						{
							if( include )
							{
								Loader.require( path + "ips." + include );
							}
						}
					);
				}
			}
		);
	}
};
var callback = { 
	afterOpen: function( popup ){
		try {
			$( 'pj_' + $(elem).identify() + '_input').activate();
		}
		catch(err){ }
	}
};

/* ==================================================================================================== */
/* ELEMENT EXTENSIONS (tooltips, authorpane etc.) */

Element.addMethods( {
	
	defaultize: function( element, lang )
	{
		if( ipb.global._supportsPlaceholder == null ){
			ipb.global._supportsPlaceholder = (function(){
				var i = document.createElement('input');
				return 'placeholder' in i;
			})();
		}
	
		if( ipb.global._supportsPlaceholder ){
			if( $F( element ) == lang || $F( element ).empty() ){
				$(element).removeClassName('inactive').writeAttribute('placeholder', lang).value = '';
			}
		} else {
			if( $F( element ) == lang || $F( element ).empty() ){
				$(element).addClassName('inactive').value = lang;
			}
		
			$(element).observe('focus', function(e){
				if( $(element).hasClassName('inactive') && ( $F(element) == '' || $F(element) == lang ) ){
					$(element).removeClassName('inactive').value = '';
				} else {
					$(element).removeClassName('inactive');
				}
			}).
			observe('blur', function(e){
				if( $F(element).empty() ){
					$(element).addClassName('inactive').value = lang;
				}
			});
			
			// Try and find a form around the element
			var form = $( element ).up('form');
			if( !Object.isUndefined( form ) ){
				$( form ).observe('submit', function(e){
					if( $(element).hasClassName('inactive') ){
						$(element).value = '';
					}
				});
			}
		}	
	},
	
	clickLaunch: function( element )
	{
		var _callback = $( element ).readAttribute("data-clicklaunch");
		var _scope    = 'global';
		
		try {
			var _try = $( element ).readAttribute("data-scope");
			_scope = ( _try ) ? _try.replace("ipb.", '') : _scope;
		} catch(e) { };
		
		if( $(element).retrieve('clickevent') ){
			try {
		 		$(element).retrieve('clickevent').stop();
			} catch(err){ };
		}
		
		var click = $(element).on( 'click', function(e) {
												Event.stop(e);
												ipb[_scope][_callback]( element, e);
											} );
		$(element).store('clickevent', click);
	},
	
	confirmAction: function( element )
	{
		var _text     = $( element ).readAttribute("data-confirmaction");
		var _ok       = '';
		
		if ( element.tagName == 'FORM' )
		{
			_ok = "$('" + element.id +"').submit()";
		}
		else
		{
			_ok =  'window.location=\'' + element.readAttribute( 'href' ) + '\'';
		}
	
		if ( ! _text || _text == 'true' )
		{
			_text = ipb.lang['gbl_confirm_desc'];
		}
		
		var _options = { type: 'pane',
						 modal: true,
						 /* Inline JS makes it easier even if it is uglier */
					 	 initial: '<div><h3>' + ipb.lang['gbl_confirm_text'] + '</h3><div class="ipsPad ipsForm_center"><p>' + _text + '</p><br /><span onclick="ipb.global.popups[\'conact\'].hide()" class="clickable ipsButton_secondary important">' + ipb.lang['gbl_confirm_cancel'] + '</span> &nbsp; <span onclick="' + _ok + '" class="clickable ipsButton_secondary">' + ipb.lang['gbl_confirm_ok'] + '</span></div>',
					 	 hideAtStart: false,
						 w: '300px',
					 	 h: 150 };
		
		if ( element.tagName == 'FORM' )
		{
			/* Fire immediately */
			if ( ! Object.isUndefined( ipb.global.popups['conact'] ) )
			{
				ipb.global.popups['conact'].kill();
			}
			
			ipb.global.popups['conact'] = new ipb.Popup( 'confirm', _options  );
		}
		else 
		{
			$(element).on( 'click', function(e)
			{
				Event.stop(e);
				
				if ( ! Object.isUndefined( ipb.global.popups['conact'] ) )
				{
					ipb.global.popups['conact'].kill();
				}
				
				ipb.global.popups['conact'] = new ipb.Popup( 'confirm', _options );
			
			} );
		}
	},
	
	tooltip: function( element, options ){
		
		options = Object.extend( {
			template: new Template("<div class='ipsTooltip' id='#{id}' style='display: none'><div class='ipsTooltip_inner'>#{content}</div></div>"),
			position: 'auto',
			content: $( element ).readAttribute("data-tooltip"),
			animate: true,
			overrideBrowser: true,
			delay: 0.4
		}, options);
		
		var show = function(e){
			if( options.delay && !options._still_going ){
				return; // Action has been cancelled
			}
			
			// Don't show if empty. Bug #36247 Tooltips
			if( ! options.content ){
				return;
			}		
			
			var id = $(element).identify();

			if( !$( id + '_tooltip' ) ){
				$( document.body ).insert({ 'bottom': options.template.evaluate({ 'id': id + '_tooltip', 'content': options.content }) } );
			}

			if( options.overrideBrowser && $(element).hasAttribute('title') ){
				$(element).writeAttribute("data-title", $(element).readAttribute('title')).writeAttribute("title", false);
			}

			var tooltip = $(id + '_tooltip').setStyle({position: 'absolute'});
			
			// Add word wrap for calculations
			//$(tooltip).setStyle('white-space: nowrap');
			
			var layout = $(element).getLayout();
			var position = $(element).cumulativeOffset();
			var dims = $( id + '_tooltip' ).getDimensions();
			var docDim = $( document.body ).getLayout();
			
			// Detect best position for tooltip
			if( options.position == 'auto' ){
				if( position.left + (layout.get('padding-box-width')/2) - (dims.width/2) < 0 ){
					options.position = 'right';
				} else if( position.left + (dims.width/2) > docDim.get('width') ){
					options.position = 'left';
				} else {
					options.position = 'top';
				}				
			}
			
			Debug.write( dims );
			
			// And now position
			switch( options.position ){
				case 'top':
					$(tooltip).setStyle( { top: (position.top - dims.height - 1) + 'px', left: (position.left + (layout.get('padding-box-width')/2) - (dims.width/2)) + 'px' } ).addClassName('top');
				break;
				case 'bottom':
					$(tooltip).setStyle( { top: (position.top + layout.get('padding-box-height') + 1) + 'px', left: (position.left + (layout.get('padding-box-width')/2) - (dims.width/2)) + 'px' } ).addClassName('bottom');
				break;
				case 'left':
					$(tooltip).setStyle( { top: (position.top - (layout.get('padding-box-height') / 2)) + 'px', left: (position.left - dims.width -  3) + 'px' }).addClassName('left');
				break;
				case 'right':
					$(tooltip).setStyle( { top: (position.top - (layout.get('padding-box-height') / 2)) + 'px', left: (position.left + layout.get('padding-box-width') - 3) + 'px' }).addClassName('right');
				break;
			}
			
			// Remove word wrap
			//$(tooltip).setStyle('white-space: normal');

			if( options.animate ){
				new Effect.Appear( $(tooltip), { duration: 0.3, queue: 'end' } );
			} else {
				$(tooltip).show();
			}
		},
		hide = function(e){
			var id = $(element).identify();
			if( !$(id + '_tooltip') ){ return; }
			
			if( options.animate ){
				new Effect.Fade( $(id + '_tooltip'), { duration: 0.2, queue: 'end' });
			} else {
				$(id + '_tooltip').hide();
			}
		};
		
		$( element ).observe("mouseenter", function(e){
			if( options.delay ){
				options._still_going = true;
				show.delay( options.delay, e );
			} else {
				show(e);
			}
		}).
		observe("click", function(e){
			options._still_going = false;
			hide();
		}).
		observe("mouseleave", function(e)
		{
			options._still_going = false;
			hide();				
		});	
	}
});

/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.global.js - Global functionality			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _global = window.IPBoard;

_global.prototype.global = {
	searchTimer: [],
	searchLastQuery: '',
	rssItems: [],
	reputation: {},
	popups: {},
	ac_cache: $H(),
	pageJumps: $H(),
	pageJumpMenus: $H(),
	boardMarkers: $H(),
	searchResults: $H(),
	tidPopOpen: 0,
	activeTab: 'forums',
	userCards: null,
	inlineNotification: { timers: [] },
	_supportsPlaceholder: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.global.js");
		
		document.observe("dom:loaded", function(){
			ipb.global.initEvents();
		});
	},
	initEvents: function()
	{
		// Delegate our user popup links/warn logs
		ipb.delegate.register(".warn_link", ipb.global.displayWarnLogs);
		ipb.delegate.register(".mini_friend_toggle", ipb.global.toggleFriend);
		ipb.delegate.register(".__topic_preview", ipb.global.topicPreview);
		ipb.delegate.register('.bbc_spoiler_show', ipb.global.toggleSpoiler);
		ipb.delegate.register('a[rel~="external"]', ipb.global.openNewWindow );
		ipb.delegate.register('._repLikeMore', ipb.global.repLikeMore);
		ipb.delegate.register('a[rel~="quickNavigation"]', ipb.global.openQuickNavigation );
		
		if( $('sign_in') && !$('sign_in').hasClassName('no_ajax') ){
			$('sign_in').on('click', ipb.global.inlineSignin);
		}
		
		if( $('rss_feed') ){
			ipb.global.buildRSSmenu();
		}
		
		if ( ! Object.isUndefined( ipb.vars['notificationData'] ) )
		{
			new ipb.Popup( 'navigation_popup', { type: 'modal',
											 	 initial: ipb.templates['notificationTemplate'].evaluate( ipb.vars['notificationData'] ),
											 	 hideAtStart: false,
												 w: '600px',
											 	 h: 250} );
		}
		
		/* This is needed for RTL - the generic anchoring isn't working on RTL for some reason */
		if( $('backtotop') ){
			$('backtotop').observe( "click", function(e){ Event.stop(e); window.scroll( 0, 0 ); } );
		}
		
		ipb.global.buildPageJumps();		
		ipb.global.initUserCards();
		
		/* Got an inline notification? */
		if ( ! Object.isUndefined( ipb.templates['inlineMsg'] ) && ipb.templates['inlineMsg'] != '' ){
			ipb.global.showInlineNotification( ipb.templates['inlineMsg'] );
		}
		
		// Contextual search
		if( $('search-box') ){
			ipb.global.contextualSearch();
		}
		
		// Global menus
		if( $('user_link') ){
			new ipb.Menu( $('user_link'), $('user_link_menucontent') );
		}
		
		if( $('new_skin') ){
			new ipb.Menu( $('new_skin'), $('new_skin_menucontent') );
		}

		if( $('new_language') ){
			new ipb.Menu( $('new_language'), $('new_language_menucontent') );
		}

		if( $('mark_all_read') ){
			new ipb.Menu( $('mark_all_read'), $('mark_all_read_menucontent') );
		}
		
		// Tooltips
		$$("[data-tooltip]").invoke('tooltip');
		
		// General click handlers
		$$("[data-clicklaunch]").invoke('clickLaunch');
		
		// Confirm action used in delete, etc
		$$("[data-confirmaction]").invoke('confirmAction');
		 
		// Status updates
		if( $('statusUpdateGlobal') ){
			$('statusUpdateGlobal').defaultize( ipb.lang['global_status_update'] );
			
			$('statusSubmitGlobal').observe( 'click', ipb.global.statusUpdated );
		}
		
		/* Attachments wrapped in URL @link http://community.invisionpower.com/tracker/issue-37113-linking-image-attachments-does-not-work/ */
		$$('a.resized_img').each( function( elem )
		{
			if ( $(elem).previous('a.bbc_url') )
			{
				var test = $(elem).previous('a.bbc_url');
				
				if ( ! test.innerHTML.length )
				{
					$(elem).writeAttribute( 'href', test.href );
					$(elem).writeAttribute( 'rel',  test.rel );
					
					/* Remove empty href */
					test.remove();
				}
			}
		} );
		
		/* Tag show more */
		if ( ! Object.isUndefined( ipb.hoverCard ) && ipb.vars['is_touch'] === false )
		{
			var ajaxUrl = ipb.vars['base_url'] + "app=core&module=ajax&section=tags&do=getTagsAsPopUp&md5check="+ipb.vars['secure_hash'];
			
			ipb.hoverCardRegister.initialize( 'tagsPopUp', { 'w': '500px', 'delay': 750, 'position': 'auto', 'ajaxUrl': ajaxUrl, 'getId': true, 'setIdParam': 'key' } );
		}
	},
	
	/* Lightbox has been disabled */
	lightBoxIsOff: function()
	{
		$$('span[rel*="lightbox"]').each( function( elem )
		{
			if ( ! $(elem).down('a') )
			{
				$(elem).down('img').on( 'click', function(e, el) { window.open(el.src) } );
			}
		} );
	},
	
	/* Stores default values of status share checkboxes */
	saveSocialShareDefaults: function( elem, e )
	{
		var services = {};
		
		/* Gather elements */
		$$('._share_x_').each( function(elem){
			services[ elem.id.replace(/share_x_/, '' ) ] = ( elem.checked ) ? 1 : 0;
		} );
		
		new Ajax.Request( ipb.vars['base_url'] + "app=core&section=sharelinks&module=ajax&do=savePostPrefs&md5check=" + ipb.vars['secure_hash'],
				{
					method: 'post',
					evalJSON: 'force',
					parameters: services,
					onSuccess: function(t)
					{
						if ( Object.isUndefined( t.responseJSON ) )
						{
							alert( ipb.lang['action_failed'] );
							return;
						}
						
						if ( ! Object.isUndefined( t.responseJSON['error'] ) )
						{
							alert( t.responseJSON['error'] );
						}
						else
						{
							/* Nothing to do */
						}
					}
				});
	},
	
	/*!! statusUpdated */
	/* Updates a status where ever you are on the page. Differs from the ipb.status version slightly */
	statusUpdated: function(e)
	{
		Event.stop(e);
		
		if ( $('statusUpdateGlobal' ).value.length < 2 || $('statusUpdateGlobal').value == ipb.lang['prof_update_default'] )
		{
			return false;
		}
		
		// Bug #34650: Commented this out so that board index updates
		// use the ajax function defined below instead of the main status function
		/* Main status library loaded? */
		/*if ( ! Object.isUndefined( ipb.status ) )
		{
			return ipb.status.updateSubmit( e, 'statusUpdateGlobal' );
		}*/
		
		var su_Twitter  = $('su_TwitterGlobal') && $('su_TwitterGlobal').checked ? 1  : 0;
		var su_Facebook = $('su_FacebookGlobal') && $('su_FacebookGlobal').checked ? 1 : 0;
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=new&md5check=" + ipb.vars['secure_hash'] + "&skin_group=boards&return=json&smallSpace=1",
						{
							method: 'post',
							evalJSON: 'force',
							parameters: {
								content: $('statusUpdateGlobal' ).value.encodeParam(),
								su_Twitter: su_Twitter,
								su_Facebook: su_Facebook
							},
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								
								if ( t.responseJSON['error'] )
								{
									alert( t.responseJSON['error'] );
								}
								else
								{
									try {
										if ( $('status_wrapper') )
										{
											var memberId = 0;
											
											try
											{
												memberId = $('status_wrapper').readAttribute('data-member');
											}
											catch(err){ }
											
											if ( ! memberId || ( memberId == ipb.vars['member_id'] ) )
											{
												$('status_wrapper').innerHTML = t.responseJSON['html'] + $('status_wrapper').innerHTML;
												
												/* Showing latest only? */
												if ( ipb.status.myLatest )
												{
													if ( $('statusWrap-' + ipb.status.myLatest ) )
													{
														$('statusWrap-' + ipb.status.myLatest ).hide();
													}
												}
											}
										}
										
										ipb.menus.closeAll(e,true);
										ipb.global.showInlineNotification( ipb.lang['status_updated'] );
										
									}
									catch(err)
									{
										Debug.error( 'Logging error: ' + err );
									}
								}
							}
						});
	},
	
	/**
	 * Changes the user's skin
	 */
	changeSkin: function(element, e)
	{
		Debug.dir( element );
		var skinId = $(element).readAttribute( 'data-skinid');
		
		var url = ipb.vars['base_url'] + 'app=core&module=ajax&section=skin&do=change&skinId=' + skinId + '&secure_key=' + ipb.vars['secure_hash'];
		Debug.write( url );
		new Ajax.Request(	url,
							{
								method: 'get',
								onSuccess: function(t)
								{
									/*
									 * Get an error?
									 */
									if( t.responseJSON['status'] == 'ok' )
									{
										window.location = window.location;
										window.location.reload(true);
									}
									else
									{
										ipb.global.errorDialogue( ipb.lang['ajax_failure'] );
									}
								}
							}
						);
		
		
		Event.stop(e);
		return false;
	},
	
	/**
	 * Displays the inbox drop down in header
	 */
	getInboxList: function(element, e)
	{
		/*
		 * Only run AJAX call once.  Cache and use the cache for subsequent requests.
		 */
		if ( Object.isUndefined( ipb.global.popups['inbox'] ) )
		{
			ipb.global.popups['inbox'] = true;
			ipb.menus.closeAll(e);
			
			$(element).identify();
			
			/* Create pop-up wrapper */
			$(element).addClassName('ipbmenu');
			
			$('ipboard_body').insert( ipb.templates['header_menu'].evaluate( { id: 'user_inbox_link_menucontent' } ) );
			
			$('user_inbox_link_menucontent').setStyle('width: 216px').update( "<div class='ipsPad ipsForm_center'><img src='" + ipb.vars['loading_img'] + "' /></div>" );
			
			var _newMenu = new ipb.Menu( $(element), $( "user_inbox_link_menucontent" ) );
			_newMenu.doOpen();			
			
			var url = ipb.vars['base_url'] + 'app=members&module=ajax&section=messenger&do=getInboxDropDown';
			Debug.write( url );
			new Ajax.Request(	url,
								{
									method: 'post',
									evalJSON: 'force',
									hideLoader: true,
									parameters: {
										secure_key: 	ipb.vars['secure_hash']
									},
									onSuccess: function(t)
									{
										/*
										 * Get an error?
										 */
										if( t.responseJSON['error'] )
										{
											if ( t.responseJSON['__board_offline__'] )
											{
												ipb.global.errorDialogue( ipb.lang['board_offline'] );
												ipb.menus.closeAll(e);
											}
										}
										else
										{										
											$('user_inbox_link_menucontent').update( t.responseJSON['html'] );
											
											/* Clear counter */
											try
											{
												$(element).down('.ipsHasNotifications').fade( { afterFinish: function() { $(element).down('.ipsHasNotifications').show().addClassName('ipsHasNotifications_blank'); } } );
											} catch( acold ) { }
										}
									}
								}
							);
		}
		
		Event.stop(e);
		return false;
	},
	
	/**
	 * Displays the notifications drop down in header
	 */
	getNotificationsList: function(element, e)
	{
		Event.stop(e);
		
		/*
		 * Only run AJAX call once.  Cache and use the cache for subsequent requests.
		 */
		if ( Object.isUndefined( ipb.global.popups['notification'] ) )
		{
			ipb.global.popups['notification'] = true;
			
			ipb.menus.closeAll(e);
			
			$(element).identify();
			
			/* Create pop-up wrapper */
			$(element).addClassName('ipbmenu');
			
			$('ipboard_body').insert( ipb.templates['header_menu'].evaluate( { id: 'user_notifications_link_menucontent' } ) );
			
			$('user_notifications_link_menucontent').setStyle('width: 216px').update( "<div class='ipsPad ipsForm_center'><img src='" + ipb.vars['loading_img'] + "' /></div>" );
			
			var _newMenu = new ipb.Menu( $(element), $( "user_notifications_link_menucontent" ) );
			_newMenu.doOpen();
			
			var url = ipb.vars['base_url'] + 'app=core&module=ajax&section=notifications&do=getlatest';
			Debug.write( url );
			
			new Ajax.Request(	url,
								{
									method: 'post',
									evalJSON: 'force',
									hideLoader: true,
									parameters: {
										secure_key: 	ipb.vars['secure_hash']
									},
									onSuccess: function(t)
									{
										/*
										 * Get an error?
										 */
										if( t.responseJSON['error'] )
										{
											if ( t.responseJSON['__board_offline__'] )
											{
												ipb.global.errorDialogue( ipb.lang['board_offline'] );
												ipb.menus.closeAll(e);
											}
										}
										else
										{										
											$('user_notifications_link_menucontent').update( t.responseJSON['html'] );
											
											/* Clear counter */
											try
											{
												$(element).down('.ipsHasNotifications').fade( { afterFinish: function() { $(element).down('.ipsHasNotifications').show().addClassName('ipsHasNotifications_blank'); } } );
											} catch( acold ) { }
										}
									}
								}
							);
		}
		
		
		return false;
	},

	
	openQuickNavigation: function( e )
	{
		Event.stop(e);
		
		if( ipb.global.popups['quickNav'] ){
			ipb.global.popups['quickNav'].show();
		} else {
			var url = ipb.vars['base_url'] + "app=core&module=ajax&section=navigation&secure_key=" + ipb.vars['secure_hash'] + "&inapp=" + ipb.vars['active_app'];
			ipb.global.popups['quickNav'] = new ipb.Popup( 'navigation_popup', { type: 'modal',
												 ajaxURL: url,
												 hideAtStart: false,
												 w: '600px',
												 h: 460 } );

		
			/* delegate */
			ipb.delegate.register('a[rel~="ipsQuickNav"]', ipb.global.quickNavTabClick );
		}
		
		return false;
	},
	
	launchPhotoEditor: function( elem, e )
	{
		Event.stop(e);
		
		if ( ! Object.isUndefined( ipb.global.popups['photoEditor'] ) )
		{
			ipb.global.popups['photoEditor'].kill();
		}
		
		var url = ipb.vars['base_url'] + "&app=members&module=ajax&section=photo&do=show&secure_key=" + ipb.vars['secure_hash'];		
		ipb.global.popups['photoEditor'] = new ipb.Popup( 'photo_popup', {  type: 'pane',
																				 modal: true,
																				 ajaxURL: url,
																				 hideAtStart: false,
																				 evalJs: 'force',
																				 w: '750px',
																				 h: 500
																				  } );
		return false;
	},
	
	quickNavTabClick: function( e, elem )
	{
		Event.stop(e);
		app = elem.readAttribute( 'data-app' );
		
		var url = ipb.vars['base_url'] + "app=core&module=ajax&section=navigation&secure_key=" + ipb.vars['secure_hash'] + "&do=panel&inapp=" + app;
		
		new Ajax.Request(	url.replace(/&amp;/g, '&'),
				{
					method: 'get',
					evalJSON: 'force',
					hideLoader: true,
					onSuccess: function(t)
					{
						$('ipsNav_content').update( t.responseText );
						
						$$('a[rel~="ipsQuickNav"]').each( function(link)
						{
							link.up('li').removeClassName('active');
							var _app = link.readAttribute( 'data-app' );
							
							if ( _app == app )
							{
								link.up('li').addClassName('active');
							}
						} );
					}
				});
		
		return false;
	},
	
	
	ajaxPagination: function( element, url )
	{
		new Ajax.Request(	url.replace(/&amp;/g, '&'),
							{
								method: 'get',
								evalJSON: 'force',
								hideLoader: true,
								onSuccess: function(t)
								{
									$(element).update( t.responseText );
								}
							});

		return false;
	},
	
	inlineSignin: function( e )
	{
		if( ipb.vars['is_touch'] ){ // Just go to normal form for touch devices
			return;
		}
		
		/* If we don't have the template bit.. */
		if ( ! $('inline_login_form') )
		{
			return;
		}
		
		Event.stop(e);

		if( ipb.global.loginRedirect )
		{
			window.location = ipb.global.loginRedirect;
			return;
		}
		
		new ipb.Popup( 'sign_in_popup', {	type: 'pane',
											initial: $('inline_login_form').show(),
											hideAtStart: false,
											hideClose: false,
											defer: false,
											modal: true,
											w: '600px' },
										{
											afterShow: function(pop){
												try {
													$('ips_username').focus();
												} catch(err){}
											}
										});
	},
	
	forumMarkRead: function(elem, e)
	{
		Event.stop(e);
		
		var id = $(elem).readAttribute("data-fid");
		
		if( !id ){ return; }
		
		var url    = ipb.vars['base_url'] + '&app=forums&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=forums&do=markRead&fid=' + id;
	
		// Send AJAX request
		new Ajax.Request( 	url,
			 				{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( t.responseText == 'no_forum' || t.responseText == 'no_permission' ){
										alert( ipb.lang['mark_read_forum'] );
										return;
									}
									
									/* Remove elements */
									$$('.__topic').each( function( topic )
									{
										if ( $(topic).hasClassName('unread') )
										{
											var tid = $(topic).readAttribute("data-tid");
											
											if ( tid )
											{
												ipb.global.topicRemoveUnreadElements( tid );
											}
										}
									} );
								}
							});
		
	},
	
	topicMarkRead: function(elem, e)
	{
		Event.stop(e);
		
		var id = $(elem).readAttribute("data-tid");
		
		if( !id ){ return; }
		
		var row    = $('trow_'+id);
		var url    = ipb.vars['base_url'] + '&app=forums&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=topics&do=markRead&tid=' + id;
	
		// Send AJAX request
		new Ajax.Request( 	url,
			 				{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( t.responseText == 'no_topic' || t.responseText == 'no_permission' ){
										alert( ipb.lang['mark_read_topic'] );
										return;
									}
									
									/* Remove mark as read link */
									$(elem).remove();
									
									/* Close preview */
									ipb.global.topicPreview(e, row.down('.__topic_preview') );
									
									/* Remove elements */
									ipb.global.topicRemoveUnreadElements( id );
								}
							});
		
	},
	
	topicRemoveUnreadElements: function( tid )
	{
		$('trow_' + tid).removeClassName('unread').down('.col_f_icon').select('a img').invoke('remove');
	},
	
	topicPreview: function(e, elem)
	{				
		Event.stop(e);
		
		var toggle = $(elem).down(".expander");
		var row = $(elem).up(".__topic");
		var id = $(row).readAttribute("data-tid");
		
		if( !id ){ return; }
		
		// Stop multiple loads
		if( $(row).readAttribute('loadingPreview') == 'yes' ){
			return; // Just be patient!
		}		
		$( row ).writeAttribute('loadingPreview', 'yes');
		
		if( $("topic_preview_" + id) )
		{
			if( $("topic_preview_wrap_" + id).visible() )
			{ 
				new Effect.BlindUp( $("topic_preview_wrap_" + id), { duration: 0.3, afterFinish: function(){ $('topic_preview_' + id).hide(); } } );
				row.removeClassName('highlighted');
				$( toggle ).addClassName('closed').removeClassName('loading').removeClassName('open').writeAttribute('title', ipb.lang['open_tpreview']);
			}
			else
			{
				$('topic_preview_' + id).show();
				new Effect.BlindDown( $("topic_preview_wrap_" + id), { duration: 0.3 } );
				row.addClassName('highlighted');
				$( toggle ).addClassName('open').removeClassName('loading').removeClassName('closed').writeAttribute('title', ipb.lang['close_tpreview']);
			}
			
			$(row).writeAttribute('loadingPreview', 'no');
		}
		else
		{
			var url    = ipb.vars['base_url'] + '&app=forums&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=topics&do=preview&tid=' + id;
			if ( ipb.global.searchResults[ id ] ){
				url += '&pid=' + ipb.global.searchResults[ id ]['pid'] + '&searchTerm=' + ipb.global.searchResults[ id ]['searchterm'];
			}
			
			$( toggle ).addClassName('loading').removeClassName('closed').removeClassName('open');
			
			// Send AJAX request
			new Ajax.Request( 	url,
				 				{
									method: 'get',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										if( t.responseText == 'no_topic' || t.responseText == 'no_permission' ){
											alert( ipb.lang['no_permission_preview'] );
											$( toggle ).addClassName('open').removeClassName('loading').removeClassName('closed').writeAttribute('title', ipb.lang['close_tpreview']);

											$(row).writeAttribute('loadingPreview', 'no');
											return;
										}
										
										if( row.tagName == "TR" )
										{
											var count = row.childElements().size();
											var newrow = new Element('tr', { 'class': 'preview', 'id': 'topic_preview_' + id });
											var newcell = new Element('td', { 'colspan': count } );
											var wrap = new Element('div', { 'id': 'topic_preview_wrap_' + id }).hide().update( new Element('div' ) );
											
											// Put all the bits inside each other
											row.insert( { after: newrow.insert( newcell.insert( wrap ) ) } );
										}
										else
										{
											var wrap = new Element('div', { 'id': 'topic_preview_wrap_' + id }).hide().update( new Element('div') );
											row.insert( { after: wrap } );
										}										
										
										// Insert content
										wrap.update( t.responseText ).relativize();
										// display it
										new Effect.BlindDown( wrap, { duration: 0.3 } );
										// Update row & elem
										row.addClassName('highlighted');
										$( toggle ).addClassName('open').removeClassName('loading').removeClassName('closed').writeAttribute('title', ipb.lang['close_tpreview']);
										
										$(row).writeAttribute('loadingPreview', 'no');
									}
								});
		}
	},
	
	// Set up the main menu
	activateMainMenu: function()
	{
		if( $("nav_other_apps") && $("community_app_menu") ){
			/* Image is 0 width inside hidden div, so we have to compensate manually...hardcoded for our useropts image 17px + 3px margin */
			/* Note that we are grabbing width of more menu tab, instead of 'margin-box-width' which accounts for margin.  Cannot grab this
				value while the tab is hidden and we don't want to loop a second time unnecessarily to readjust after showing the menu */
			var start = totalW = $("nav_other_apps").getWidth() + 20; 
			var menuWidth = $("community_app_menu").getWidth();

			/* Add up the widths of any menu items we won't be moving to the more menu */
			$("community_app_menu").select("li.skip_moremenu").each( function(elem){
				totalW += $(elem).measure('margin-box-width');
			});

			$("community_app_menu").select("li:not(#nav_other_apps,.submenu_li)").each( function(elem){
				/* These tabs should not be moved to the more menu */
				if( $(elem).hasClassName('skip_moremenu') )
				{
					return;
				}

				totalW += $(elem).measure('margin-box-width');

				if( totalW >= menuWidth )
				{
					if( !$("more_apps_menucontent") ){
						$$("body")[0].insert("<div id='more_apps_menucontent' class='submenu_container clearfix boxShadow'><div class='left'><ul class='submenu_links' id='more_apps_menucontentul'></ul></div></div>");
					}

					$(elem).addClassName('submenu_li').removeClassName('left');
					
					$("more_apps_menucontentul").insert( elem ); // Move item to menu
				}
			});
			
			// Do we have an app menu?
			if( $("more_apps_menucontent" ) )
			{
				$("nav_other_apps").show();
				new ipb.Menu( $('more_apps'), $('more_apps_menucontent') );
			}
			
			Debug.write( menuWidth );
		}
	},
	
	/**
	 * Init user cards
	 */
	initUserCards: function()
	{
		/* User cards */
		if ( ! Object.isUndefined( ipb.hoverCard ) && ipb.vars['is_touch'] === false && ipb.vars['member_group']['g_mem_info'] == 1 )
		{
			var ajaxUrl = ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=card';
			if ( ipb.topic !== undefined && ipb.topic.forum_id !== undefined )
			{
				ajaxUrl += "&f=" + ipb.topic.forum_id;
			}
			
			ipb.hoverCardRegister.initialize( 'member', { 'w': '278px', 'delay': 750, 'position': 'auto', 'ajaxUrl': ajaxUrl, 'getId': true, 'setIdParam': 'mid' } );
		}
	},
	
	/**
	 * Display an inline notification
	 * 
	 */
	showInlineNotification: function( content, options )
	{
		/* Fix options */
		options					  = ( Object.isUndefined( options ) ) ? {} : options;
		options.showClose 		  = ( Object.isUndefined( options.manualClose ) ) ? false : options.showClose;
		options.neverClose 		  = ( Object.isUndefined( options.neverClose ) )  ? false : options.neverClose;
		options.displayForSeconds = ( Object.isUndefined( options.displayForSeconds ) ) ? 5 : options.displayForSeconds;
		
		/* Already one open? */
		if ( $('ipsGlobalNotification' ) )
		{
			var span = $('ipsGlobalNotification').down('span');
			
			/* fade out current content and fade in new */
			new Effect.Fade( span, { duration: 0.8, afterFinish:
				function() {
					span.replace( new Element( 'span' ).update( content ) );
					new Effect.Appear( $('ipsGlobalNotification').down('span'), { duration: 0.8 } );
				}
			} );
		}
		else
		{
			if( $('ipbwrapper') )
			{
				/* Front end */
				$('ipbwrapper').insert( new Element('div', { id: 'ipsGlobalNotification' } ).update( ipb.templates['global_notify'].evaluate( { 'message': content } ) ) );
			}
			else
			{
				/* ACP */
				$('ipboard_body').insert( new Element('div', { id: 'ipsGlobalNotification' } ).update( ipb.templates['global_notify'].evaluate( { 'message': content, 'close': ipb.templates['global_notify_close'] } ) ) );
			}
			
			/* Add in content */			
			new Effect.Appear( 'ipsGlobalNotification', { duration: 1.5 } );
			
			if ( options.showClose )
			{
				$('ipsGlobalNotification').insert( new Element( 'div', { id: 'ipsGlobalNotification_close' } ) );
				$('ipsGlobalNotification_close').observe('click', ipb.global.closeInlineNotification );
			}
			else if( $('ipsGlobalNotification_close') )
			{
				$('ipsGlobalNotification_close').observe('click', ipb.global.closeInlineNotification );
			}
		}
		
		/* Listen on any a hrefs */
		$('ipsGlobalNotification').on('click', 'span a', ipb.global.closeInlineNotification);
		
		/* Close */
		if ( options.neverClose !== true )
		{
			try	{
				clearTimeout( ipb.global.inlineNotification['timers']['close'] );
			}
			catch(e) {}
			
			ipb.global.inlineNotification['timers']['close'] = setTimeout( ipb.global.closeInlineNotification, options.displayForSeconds * 1000 );
		}
	},
	
	/**
	 * Closes a notification
	 */
	closeInlineNotification: function()
	{
		if ( $('ipsGlobalNotification_close') ){
			$('ipsGlobalNotification_close').stopObserving('click');
		}
		
		try {
			clearTimeout( ipb.global.inlineNotification['timers']['close'] );
		}
		catch(e) {}
		
		new Effect.Fade( 'ipsGlobalNotification', { duration: 1.0 } );
		setTimeout( function() { $('ipsGlobalNotification').remove(); }, 2000 );
	},
	
	/**
	 * Show an error dialogue
	 * @param string
	 * @returns Nothing
	 */
	errorDialogue: function( text )
	{
		errContent = "<h3>" + ipb.lang['error_occured'] + "</h3><div class='row2 ipsPad ipsForm_center'><p>" + text + "</p></div>";
		
		new ipb.Popup( 'generic__errorDialogue', {  type: 'pane',
													initial: errContent,
													stem: true,
													hideAtStart: false,
													hideClose: false,
													defer: false,
													warning: false,
													w: 400 } );
	},
	
	/**
	 * Show an OK dialogue
	 * @param string
	 * @returns Nothing
	 */
	okDialogue: function( text )
	{
		okContent = "<h3>" + ipb.lang['success'] + "</h3><div class='row2 ipsPad ipsForm_center'><p>" + text + "</p></div>";
		
		new ipb.Popup( 'generic__okDialogue', {  type: 'pane',
												 initial: okContent,
												 stem: true,
												 hideAtStart: false,
												 hideClose: false,
												 defer: false,
												 w: 400 } );
	},
	
	contextualSearch: function()
	{
		if( !$('search_options') && !$('search_options_menucontent') ){ return; }
		
		if ( ! $('main_search') )
		{
			return;
		}
		
		$('main_search').defaultize( ipb.lang['search_default_value'] );
		
		// This removes the text for IE7
		$('search').select('.submit_input').find( function(elem){ $(elem).value = ''; } );
		
		var update = function( noSelect )
		{
			var checked = $('search_options_menucontent').select('input').find( function(elem){ return $(elem).checked; } );
			if( Object.isUndefined( checked ) ){ 
				checked = $('search_options_menucontent').select('input:first')[0];
				if( !checked ){ return; }
				checked.checked = true;
			}
			$('search_options').show().update( $( checked ).up('label').readAttribute('title') || '' );
			
			// Put cursor in search box
			if( noSelect != true ){
				$('main_search').focus();
			}
			
			return true;
		};
		update(true);
		
		$('search_options_menucontent').select('input').invoke('observe', 'click', update);
	},

	fetchTid: function( e )
	{
		var elem = Event.element(e);
		elem.identify();
		
		if( !elem.hasClassName('__topic') )
		{
			elem = elem.up('.__topic');
		}

		var id   = elem.id;
		
		if ( !id || ! $(id) )
		{
			return 0;
		}
		
		var m    = $(id).className.match('__tid([0-9]+)');
		var tid  = m[1];
		
		return tid;
	},

	displayWarnLogs: function( e, elem )
	{		
		mid = elem.id.match('warn_link_([0-9a-z]+)_([0-9]+)')[2];
		if( Object.isUndefined(mid) ){ return; }
		
		if( parseInt(mid) == 0 ){
			return false;
		}
		
		Event.stop(e);
		
		var _url 		= ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=warn&do=view&mid=' + mid;
		warnLogs = new ipb.Popup( 'warnLogs', {type: 'pane', modal: false, w: '500px', h: 500, ajaxURL: _url, hideAtStart: false, close: '.cancel' } );
		
	},
	
	/* ------------------------------ */
	/**
	 * Toggle mini friend button
	 * 
	 * @param	{event}		e		The event
	 * @param	{int}		id		Member id
	*/
	toggleFriend: function(e, elem)
	{
		Event.stop(e);
		
		// Get ID of friend
		var id = $( elem ).id.match('friend_(.*)_([0-9]+)');
		if( Object.isUndefined( id[2] ) ){ return; }
		
		var isFriend = ( $(elem).hasClassName('is_friend') ) ? 1 : 0;
		var urlBit = ( isFriend ) ? 'remove' : 'add';
		
		var url = ipb.vars['base_url'] + "app=members&section=friends&module=ajax&do=" + urlBit + "&member_id=" + id[2] + "&md5check=" + ipb.vars['secure_hash'];
		
		// Send
		new Ajax.Request( 	url,
			 				{
								method: 'get',
								onSuccess: function(t)
								{
									switch( t.responseText )
									{
										case 'pp_friend_timeflood':
											alert( ipb.lang['cannot_readd_friend'] );
											Event.stop(e);
											break;
										case "pp_friend_already":
											alert( ipb.lang['friend_already'] );
											Event.stop(e);
											break;
										case "error":
											return true;
											break;
										default:
											
											var newIcon = ( isFriend ) ? ipb.templates['m_add_friend'].evaluate({ id: id[2]}) : ipb.templates['m_rem_friend'].evaluate({ id: id[2] });
											 
											// Find all friend links for this user
											var friends = $$('.mini_friend_toggle').each( function( fr ){
												if( $(fr).id.endsWith('_' + id[2] ) )
												{
													if ( isFriend ) {
														$(fr).removeClassName('is_friend').addClassName('is_not_friend').update( newIcon );
													} else {
														$(fr).removeClassName('is_not_friend').addClassName('is_friend').update( newIcon );
													}
												}											
											});
											
											new Effect.Highlight( $( elem ), { startcolor: ipb.vars['highlight_color'] } );
											
											// Fire an event so we can update if necessary
											document.fire('ipb:friendRemoved', { friendID: id[2] } );
											Event.stop(e);
										break;
									}
								}
							}
						);
	},
	
	/**
	* MATT
	* Toggle spammer
	*/
	toggleFlagSpammer: function( memberId, flagStatus )
	{
		if ( flagStatus == true )
		{
			if( confirm( ipb.lang['set_as_spammer'] ) )
			{
				var tid	= 0;
				var fid	= 0;
				var sid	= 0;
				
				if( typeof(ipb.topic) != 'undefined' )
				{
					tid = ipb.topic.topic_id;
					fid = ipb.topic.forum_id;
					sid = ipb.topic.start_id;
				}

				window.location = ipb.vars['base_url'] + 'app=core&module=modcp&do=setAsSpammer&member_id=' + memberId + '&t=' + tid + '&f=' + fid + '&st=' + sid + '&auth_key=' + ipb.vars['secure_hash'];
				return false;
			}
			else
			{
				return false;
			}
		}
		else
		{
			alert( ipb.lang['is_spammer'] );
			return false;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Toggle spoiler
	 * 
	 * @param	{event}		e		The event
	*/
	toggleSpoiler: function(e, button)
	{
		Event.stop(e);
		
		var returnvalue = $(button).up('.bbc_spoiler').down('.bbc_spoiler_wrapper').down('.bbc_spoiler_content').toggle();
		
		if( returnvalue.visible() ){
			$(button).value = ipb.lang['spoiler_hide'];
		} else {
			$(button).value = ipb.lang['spoiler_show'];
		}
	},

	/* ------------------------------ */
	/**
	 * Builds the popup menu for RSS feeds
	*/
	buildRSSmenu: function()
	{
		// Get all link tags
		$$('link').each( function(link)
		{
			if( link.readAttribute('type') == "application/rss+xml" )
			{
				ipb.global.rssItems.push( ipb.templates['rss_item'].evaluate( { url: link.readAttribute('href'), title: link.readAttribute('title') } ) );
			}
		});
		
		if( ipb.global.rssItems.length > 0 )
		{
			rssmenu = ipb.templates['rss_shell'].evaluate( { items: ipb.global.rssItems.join("\n") } );
			$( 'rss_feed' ).insert( { after: rssmenu } );
			new ipb.Menu( $( 'rss_feed' ), $( 'rss_menu' ) );
		}
		else
		{
			$('rss_feed').hide();
		}
	},
	
	/* ------------------------------ */
	/**
	 * Reputation Popup Balloon
	 */
	repPopUp: function( e, repId, repApp, repType )
	{
		if( ipb.global.popups['rep_' + repId] ){
			ipb.global.popups['rep_' + repId].kill();
		}
		
		var _url = ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=reputation&do=view&repApp=' + repApp + '&repType=' + repType + '&repId=' + repId;
		ipb.global.popups['rep_' + repId] = new ipb.Popup('rep_' + repId, {
																type: 'balloon',
																stem: true,
																attach: { target: e, position: 'auto' },
																hideAtStart: false,
																ajaxURL: _url,
																w: '300px',
																h: 400
															});
	},
	
	/* ------------------------------ */
	/**
	 * Hides the PM notification box
	 * 
	 * @param	{event}		e		The event
	*/
	closePMpopup: function(e)
	{
		if( $('pm_notification') )
		{
			new Effect.Parallel([
				new Effect.Fade( $('pm_notification') ),
				new Effect.BlindUp( $('pm_notification') )
			], { duration: 0.5 } );
		}
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Mark a PM read from popup
	 * 
	 * @param	{event}		e		The event
	*/
	markReadPMpopup: function(e)
	{
		if( $('pm_notification') )
		{
			var elem 	= Event.findElement(e, 'a');
			var href	= elem.href.replace( /&amp;/g, '&' ) + '&ajax=1';
			
			//Debug.write( 'Mark as read: ' + href );
			
			new Ajax.Request( href + "&md5check=" + ipb.vars['secure_hash'],
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t){}
							});
			
			new Effect.Parallel([
				new Effect.Fade( $('pm_notification') ),
				new Effect.BlindUp( $('pm_notification') )
			], { duration: 0.5 } );
		}
		
		Event.stop(e);
		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Initializes GD image
	 *
	*/
	initGD: function()
	{
		$('gd-antispam').observe('click', ipb.global.generateNewImage);
		
		if( $('gd-image-link') )
		{
			$('gd-image-link').observe('click', ipb.global.generateNewImage );
		}
	},

	/* ------------------------------ */
	/**
	 * Simulate clicking the image
	 *
	 * @param	{element}	elem	The GD image element
	*/
	generateImageExternally: function( elem )
	{
		if( !$(elem) ){ return; }
		
		$(elem).observe('click', ipb.global.generateNewImage);
	},	
	
	/* ------------------------------ */
	/**
	 * Click event for generating new GD image
	 * 
	 * @param	{event}		e	The event
	*/
	generateNewImage: function(e)
	{
		img	= $('gd-antispam');
		
		Event.stop(e);

		oldSrc = img.src.toQueryParams();
		oldSrc = $H( oldSrc ).toObject();
		
		if( !oldSrc['captcha_unique_id'] ){	Debug.error("No captcha ID found"); }
		
		// Get new image
		new Ajax.Request( 
			ipb.vars['base_url'] + "app=core&module=global&section=captcha&do=refresh&captcha_unique_id=" + oldSrc['captcha_unique_id'] + '&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				onSuccess: function(t)
				{
					//Change src
					oldSrc['captcha_unique_id'] = t.responseText;
					img.writeAttribute( { src: ipb.vars['base_url'] + $H( oldSrc ).toQueryString() } );
					$('regid').value = t.responseText;
				}
			}
		);
	},
	
	/* ------------------------------ */
	/**
	 * Registers a reputation toggle on the page
	 * 
	 * @param	{int}		id		The element that wraps rep
	 * @param	{string}	url		The URL to ping
	 * @param	{int}		rating	The current rep rating
	*/
	registerReputation: function( id, url, rating )
	{
		if( !$( id ) ){ return; }
				
		// Find rep up
		var rep_up         = $( id ).down('.rep_up');
		var rep_down       = $( id ).down('.rep_down');
		
		var domLikeStripId = ( $(url.domLikeStripId) ) ? $(url.domLikeStripId) : false;
	
		var sendUrl = ipb.vars['base_url'] + '&app=core&module=ajax&section=reputation&do=add_rating&app_rate=' + url.app + '&type=' + url.type + '&type_id=' + url.typeid + '&secure_key=' + ipb.vars['secure_hash'];		
		
		if( $( rep_up ) ){
			$( rep_up ).observe( 'click', ipb.global.repRate.bindAsEventListener(this, 1, id) );
		}
		
		if( $( rep_down ) ){
			$( rep_down ).observe( 'click', ipb.global.repRate.bindAsEventListener(this, -1, id) );
		}
		
		ipb.global.reputation[ id ] = { obj: $( id ), domLikeStripId: domLikeStripId, url: url, sendUrl: sendUrl, currentRating: rating || 0 };
		Debug.write( "Registered reputation" );
	},
	
	/* ------------------------------ */
	/**
	 * Does a reputation rating action
	 * 
	 * @param	{event}		e		The event
	*/
	repRate: function( e )
	{
		Event.stop(e);
		var type = $A(arguments)[1];
		var id = $A(arguments)[2];
		var value = ( type == 1 ) ? 1 : -1;
		
		if( !ipb.global.reputation[ id ] ){
			return;
		} else {
			var rep = ipb.global.reputation[ id ];
		}

		Debug.write( rep.sendUrl + '&rating=' + value );
		
		// Send ping
		new Ajax.Request( rep.sendUrl + '&rating=' + value,
						{
							method: 'get',
							onSuccess: function( t )
							{
								if( t.responseJSON['status'] == 'ok' )
								{
									try {									
										// It worked! Hide the rep buttons
										rep.obj.down('.rep_up').up('li').hide();
										rep.obj.down('.rep_down').up('li').hide();
										
										/* Can we see some, though? */
										if ( t.responseJSON['canRepUp'] === true )
										{
											rep.obj.down('.rep_up').up('li').show();
										}
										
										if ( t.responseJSON['canRepDown']  === true )
										{
											rep.obj.down('.rep_down').up('li').show();
										}										
									} catch(err) { Debug.error( err ); }
									
									// Update the figure
									var rep_display = rep.obj.down('.rep_show');
									if( rep_display )
									{										
										['positive', 'negative', 'zero'].each(function(c){ rep_display.removeClassName(c); });
										
										var newValue = parseInt( t.responseJSON['rating'] );
										
										if( newValue > 0 )
										{
											rep_display.addClassName('positive');
										}
										else if( newValue < 0 )
										{
											rep_display.addClassName('negative');
										}
										else
										{
											rep_display.addClassName('zero');
										}
										
										rep_display.update( newValue );
									}
									
									/* Got a like strip */
									if ( $(rep.domLikeStripId.id) )
									{
										if ( t.responseJSON['likeData'].formatted !== false )
										{
											$(rep.domLikeStripId.id).update( t.responseJSON['likeData'].formatted ).show();
										}
										else
										{
											$(rep.domLikeStripId.id).update( '' ).hide();
										}
									}
								}
								else
								{
									if( t.responseJSON['error'] == 'nopermission' || t.responseJSON['error'] == 'no_permission' )
									{
										ipb.global.errorDialogue( ipb.lang['no_permission'] );
									}
									else
									{
										ipb.global.errorDialogue( t.responseJSON['error'] );
									}
								}
							}
						});
	
	 },
	
	 /**
	 * Fetch 'more'pop-up
	 */
	repLikeMore: function(e, elem)
	{
		Event.stop(e);
		
		try
		{
			var id   = elem.readAttribute('data-id');
			var app  = elem.readAttribute('data-app');
			var type = elem.readAttribute('data-type');
		}
		catch( e )
		{
			Debug.error(e);
		}
		
		if ( ! Object.isUndefined( ipb.global.popups['likeMore'] ) )
		{
			ipb.global.popups['likeMore'].kill();
		}
		
		var popid = 'setfave_' + id;
		var _url  = ipb.vars['base_url'] + '&app=core&module=ajax&section=reputation&do=more&secure_key=' + ipb.vars['secure_hash'] + '&f_app=' + app + '&f_type=' + type + '&f_id=' + id;
		Debug.write( _url );
		
		/* easy one this... */
		ipb.global.popups['likeMore'] = new ipb.Popup( popid, { type: 'pane',
																ajaxURL: _url,
																stem: false,
																hideAtStart: false,
																h: 500,
																w: '450px' });		
	},

	/* ------------------------------ */
	/**
	 * Utility function for converting bytes
	 * 
	 * @param	{int}		size	The value in bytes to convert
	 * @return	{string}			The converted string, with unit
	*/
	convertSize: function(size)
	{
		var kb = 1024;
		var mb = 1024 * 1024;
		var gb = 1024 * 1024 * 1024;
		
		if( size < kb ){ return size + " B"; }
		if( size < mb ){ return ( size / kb ).toFixed( 2 ) + " KB"; }
		if( size < gb ){ return ( size / mb ).toFixed( 2 ) + " MB"; }
		
		return ( size / gb ).toFixed( 2 ) + " GB";
	},

	/* ------------------------------ */
	/**
	 * Registers a page jump toggle
	 * 
	 * @param	{int}	source		ID of this jump
	 * @param	{hash}	options		Options for this jump
	*/
	registerPageJump: function( source, options )
	{
		if( !source || !options ){
			return;
		}
		
		ipb.global.pageJumps[ source ] = options;	
	},
	
	/* ------------------------------ */
	/**
	 * Builds a page jump control
	*/
	buildPageJumps: function()
	{
		$$('.pagejump').each( function(elem){
			// Find the pj ID
			var classes = $( elem ).className.match(/pj([0-9]+)/);
			
			if( Object.isUndefined( classes ) || !classes || !classes[1] ){
				return;
			}
			
			$( elem ).identify();
			
			// Doth a popup exist?
			//Debug.write( "This wrapper has been created! " + classes[1]  );
			var temp = ipb.templates['page_jump'].evaluate( { id: 'pj_' + $(elem).identify() } );
			$$('body')[0].insert( temp );
			
			$('pj_' + $(elem).identify() + '_submit').observe('click', ipb.global.pageJump.bindAsEventListener( this, $(elem).identify() ) );
			
			// So it submits on enter
			$('pj_' + $(elem).identify() + '_input').observe('keypress', function(e){
				if( e.which == Event.KEY_RETURN )
				{
					ipb.global.pageJump( e, $(elem).identify() );
				}
			});
			
			var wrap = $( 'pj_' + $(elem).identify() + '_wrap' ).addClassName('pj' + classes[1]).writeAttribute('jumpid', classes[1] );
			
			var callback = { 
				afterOpen: function( popup ){
					try {
						$( 'pj_' + $(elem).identify() + '_input').activate();
					}
					catch(err){ }
				}
		 	};
			
			ipb.global.pageJumpMenus[ classes[1] ] = new ipb.Menu( $( elem ), $( wrap ), { stopClose: true }, callback );
		});
	},
	
	/* ------------------------------ */
	/**
	 * Executes a page jump
	 * 
	 * @param	{event}		e		The event
	 * @param	{element}	elem	The page jump element
	*/
	pageJump: function( e, elem )
	{
		if( !$( elem ) || !$( 'pj_' + $(elem).id + '_input' ) ){ return; }
		
		var value = $F( 'pj_' + $(elem).id + '_input' );
		var jumpid = $( 'pj_' + $(elem).id + '_wrap' ).readAttribute( 'jumpid' );
		
		if( value.blank() ){
			try {
				ipb.global.pageJumpMenus[ source ].doClose();
			} catch(err) { }
		}
		else
		{
			value = parseInt( value );
		}
		
		// Work out page number 
		var options = ipb.global.pageJumps[ jumpid ];
		if( !options ){ Debug.dir( ipb.global.pageJumps ); Debug.write( jumpid ); return; }
		
		var pageNum = ( ( value - 1 ) * options.perPage );
		Debug.write( pageNum );
		
		if( pageNum < 1 ){
			pageNum = 0;
		}
		/*else if( pageNum > options.totalPages ){
			pageNum = options.totalPages;
		}*/
		
		if( ipb.vars['seo_enabled'] ){
			// Bug #33897
			// Pagination broken in search results due to use of query string, so here we'll check
			// whether QS's are used with regex, and use old style pagination if that's the case.
			if( options.url.charAt( options.url.length - 1 ) == '=' || options.url.match(/(&|&amp;)[\d\w]+=/gi) ){
				// Bug #31029
				// SEO URLs ending with = don't work. Use non-seo urls in this case.
				var url = options.url + '&amp;' + options.stKey + '=' + pageNum;
			} else {
				if( document.location.toString().match( ipb.vars['seo_params']['start'] ) && document.location.toString().match( ipb.vars['seo_params']['end'] ) ){
					if ( options.url.match( ipb.vars['seo_params']['varBlock'] ) )
					{
						var url = options.url + ipb.vars['seo_params']['varSep'] + options.stKey + ipb.vars['seo_params']['varSep'] + pageNum;
					}
					else
					{
						var url = options.url + ipb.vars['seo_params']['varBlock'] + options.stKey + ipb.vars['seo_params']['varSep'] + pageNum;
					}
				} else {
					var url = options.url + ipb.vars['seo_params']['varBlock'] + options.stKey + ipb.vars['seo_params']['varSep'] + pageNum;
				}
			}
		} else {
			var url = options.url + '&amp;' + options.stKey + '=' + pageNum;
		}

		if( options.anchor )
		{
			url = url + options.anchor;
		}
	
		url = url.replace(/&amp;/g, '&');
		// Without a negative lookbehind, http:// gets replaced with http:/ when we replace // with /
		// @see http://blog.stevenlevithan.com/archives/mimic-lookbehind-javascript
		url = url.replace(/(http:|https:)?\/\//g, function($0, $1) { return $1 ? $0 : '/'; } );
		
		document.location = url;
		
		return;
	},
	
	/* ------------------------------ */
	/**
	 * Open the link in a new window
	 * 
	 * @param	{event}		e		The event
	 * @param	{boolean}	force	Force new window regardless of host?
	*/
	openNewWindow: function(e, link, force)
	{		
		var ourHost	= document.location.host;
		var newHost = link.host;
		
		if( Prototype.Browser.IE )
		{
			newHost	= newHost.replace( /^(.+?):(\d+)$/, '$1' );
		}

		/**
		 * Open a new window, if link is to a different host
		 */
		if( ourHost != newHost || force )
		{
			window.open(link.href);
			Event.stop(e);
			return false;
		}
		else
		{
			return true;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Registers an ajax marker
	 * 
	 * @param	{string}	id		ID of the wrapper element
	 * @param	{string}	key		Key of the current marker status (e.g. f_unread)
	 * @param	{string}	url		URL to ping
	*/
	registerMarker: function( id, key, url )
	{
		if( !$(id) || key.blank() || url.blank() ){ return; }
		Debug.write( "Marker INIT: " + id );
		$( id ).observe('click', ipb.global.sendMarker.bindAsEventListener( this, id, key, url ) );
	},
	
	/* ------------------------------ */
	/**
	 * Sends a marker read request
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		ID of containing element
	 * @param	{string}	key		Key of current marker
	 * @param	{string}	url		URL to ping
	*/
	sendMarker: function( e, id, key, url )
	{
		Event.stop(e);
		
		new Ajax.Request( url + "&secure_key=" + ipb.vars['secure_hash'], 
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										Debug.error("Invalid server response");
										return false;
									}
									
									if( t.responseJSON['error'] )
									{
										Debug.error( t.responseJSON['error'] );
										return false;
									}
									
									if ( $(id + '_tooltip') ){
										$(id + '_tooltip').hide();
									}
									
									$( id ).up('tr').removeClassName('unread');
									
									// Update icon
									$( id ).replace( unreadIcon );
									
									// Update subforum icons
									var _intId	= id.replace( /forum_img_/, '' );
									
									if( $("subforums_" + _intId ) )
									{
										$$("#subforums_" + _intId + " li" ).each( function(elem) {
											$(elem).removeClassName('unread');
										});
									}
								}
							});
	},
	
	registerCheckAll: function( id, classname )
	{
		if( !$( id ) ){ return; }
		
		$( id ).observe('click', ipb.global.checkAll.bindAsEventListener( this, classname ) );
		
		$$('.' + classname ).each( function(elem){
			$( elem ).observe('click', ipb.global.checkOne.bindAsEventListener( this, id ) );
		});
	},
	
	checkAll: function( e, classname )
	{
		Debug.write('checkAll');
		var elem = Event.element(e);
		
		// Get all checkboxes
		var checkboxes = $$('.' + classname);
		
		if( elem.checked ){
			checkboxes.each( function(check){
				check.checked = true;
			});
		} else {
			checkboxes.each( function(check){
				check.checked = false;
			});
		}			
	},
	
	checkOne: function(e, id)
	{
		var elem = Event.element(e);
		
		if( $( id ).checked && elem.checked == false )
		{
			$( id ).checked = false;
		}		
	},
	
	updateReportStatus: function(e, reportID, noauto, noimg )
	{
		Event.stop(e);
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=reports&amp;do=change_status&secure_key=" + ipb.vars['secure_hash'] + "&amp;status=3&amp;id=" + parseInt( reportID ) + "&amp;noimg=" + parseInt( noimg ) + "&amp;noauto=" + parseInt( noauto );
		
		// Do request, see what we get
		new Ajax.Request( url.replace(/&amp;/g, '&'),
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function( t )
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									
									try {
										$('rstat-' + reportID).update( t.responseJSON['img'] );
										ipb.menus.closeAll( e );
									} catch(err) {
										Debug.error( err );
									}
								}
							});
	},
	
	getTotalOffset: function(elem, top, left)
	{
		if( $( elem ).getOffsetParent() != document.body )
		{
			Debug.write( "Checking " + $(elem).id );
			var extra = $(elem).positionedOffset();
			top += extra['top'];
			left += extra['left'];
			
			return ipb.global.getTotalOffset( $( elem ).getOffsetParent(), top, left );
		}
		else
		{
			Debug.write("OK Finished!");
			return { top: top, left: left };
		}
	},
	
	// Checks a server response from an ajax request for 'nopermission'
	checkPermission: function( text )
	{
		if( text == "nopermission" || text == 'no_permission' )
		{
			alert( ipb.lang['no_permission'] );
			return false;
		}
		
		return true;
	},
	
	/**
	 * Check for entry keypress
	 */
	checkForEnter: function(e, callback)
	{
		if( ![ Event.KEY_RETURN ].include( e.keyCode ) ){
			return; // Not interested in anything else
		}
		
		if ( callback )
		{
			switch( e.keyCode )
			{
				case Event.KEY_RETURN:
					callback(e);
				break;
			}
			
			Event.stop(e);
		}	
	},

	/**
	 * Check for DST
	 */
	checkDST: function()
	{
		var memberHasDst	= ipb.vars['dst_in_use'];
		var dstInEffect		= new Date().getDST();

		if( memberHasDst - dstInEffect != 0 )
		{
			var url = ipb.vars['base_url'] + 'app=members&module=ajax&section=dst&md5check='+ipb.vars['secure_hash'];
			
			new Ajax.Request(	url,
								{
									method: 'get',
									onSuccess: function(t)
									{
										// We don't need to do anything about this..
										return true;
									}
								}
							);
		}
	}
};

/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.menu.js - Me n you class	<3				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

/* ipb.menus is a menu manager; ipb.Menu is a menu object */

var _menu = window.IPBoard;
_menu.prototype.menus = {
	registered: $H(),
	closeCallBack: false,
	
	init: function()
	{
		Debug.write("Initializing ips.menu.js");
		document.observe("dom:loaded", function(){
			ipb.menus.initEvents();
		});
	},
	
	initEvents: function()
	{  
		// Set document event
		Event.observe( document, 'click', ipb.menus.docCloseAll );
		
		// Auto-find menus
		$$('.ipbmenu').each( function(menu){
			id = menu.identify();
			if( $( id + "_menucontent" ) )
			{ 
				new ipb.Menu( menu, $( id + "_menucontent" ) );
			}
		});
	},
	
	register: function( source, obj )
	{
		ipb.menus.registered.set( source, obj );
	},
	
	registerCloseCallBack: function( callBack )
	{
		ipb.menus.closeCallBack = callBack;
	},
	
	docCloseAll: function( e )
	{
		ipb.menus.closeAll( e );
	},
	
	/*!! Close all menus (forceClose ignores clicked in menu area check) */
	closeAll: function( except, forceClose )
	{
		ipb.menus.registered.each( function(menu, force){
		
			if( typeof( except ) == 'undefined' || ( except && menu.key != except ) )
			{
				try{
					if( forceClose || ( !(except.target && $(except.target).descendantOf( menu.value.target )) && except != menu.key ) ){
						menu.value.doClose();
					}
				} catch(err) {
					// Assume this menu gone byebye
				}
			}
		});
		
		/* Could make this an array and chain events */
		if ( Object.isFunction( ipb.menus.closeCallBack ) )
		{
			ipb.menus.closeCallBack();
		}
	}	
};

_menu.prototype.Menu = Class.create({
	initialize: function( source, target, options, callbacks ){
		if( !$( source ) || !$( target ) ){ return; }
		if( !$( source ).id ){
			$( source ).identify();
		}
		this.id = $( source ).id + '_menu';
		this.source = $( source );
		this.target = $( target );
		this.callbacks = callbacks || {};
		
		this.options = Object.extend( {
			eventType: 'click',
			closeOnMouseout: false,
			stopClose: true,
			offsetX: -63,
			offsetY: -20
		}, arguments[2] || {});
		
		// Set up events
		$( source ).observe( 'click', this.eventClick.bindAsEventListener( this ) );
		$( source ).observe( 'mouseover', this.eventOver.bindAsEventListener( this ) );
		$( target ).observe( 'click', this.targetClick.bindAsEventListener( this ) );

		/* Close on mouse out too? */
		if( this.options['closeOnMouseout'] !== false )
		{
			$( this.options['closeOnMouseout'] ).observe( 'mouseleave', this.mouseOutClose.bindAsEventListener( this ) );
		}
		
		/* Have an alt? */
		if ( $( $( source ).id + '_alt' ) )
		{
			$( $( source ).id + '_alt' ).observe( 'click', this.eventClick.bindAsEventListener( this, $( $( source ).id + '_alt' ) ) );
			$( $( source ).id + '_alt' ).observe( 'mouseover', this.eventOver.bindAsEventListener( this ) );
		}
		
		// Set up target
		$( this.target ).setStyle( 'position: absolute;' ).hide().setStyle( { zIndex: 9999 } );
		$( this.target ).descendants().each( function( elem ){
			$( elem ).setStyle( { zIndex: 10000 } );
		});
		
		ipb.menus.register( $( source ).id, this ); 
		
		if( Object.isFunction( this.callbacks['afterInit'] ) )
		{
			this.callbacks['afterInit']( this );
		}
	},
	
	doOpen: function(elem)
	{
		Debug.write("Menu open");
		var pos = {};
		
		var _source = ( this.options.positionSource ) ? this.options.positionSource : this.source;
	
		if ( ! Object.isUndefined( elem ) )
		{
			var _source = elem;
		}
		
		// This is the positioned offset of the source element
		var sourcePos		= $( _source ).positionedOffset();
		
		// Cumulative offset (actual position on the page, e.g. if you scrolled down it could be higher than max resolution height)
		var _sourcePos		= $( _source ).cumulativeOffset();
		
		// Cumulative offset of your scrolling (how much you have scrolled)
		var _offset			= $( _source ).cumulativeScrollOffset();
		
		// Real source position: Actual position on page, minus scroll offset (provides position on page within viewport)
		//Todo hardcode
		var realSourcePos	= { top: 49, left: _sourcePos.left - _offset.left };
		
		// Dimensions of source object
		var sourceDim		= $( _source ).getDimensions();
		
		// Viewport dimensions (e.g. 1280x1024)
		var screenDim		= document.viewport.getDimensions();
		
		// Target dimensions
		var menuDim			= { width: $( this.target ).measure('border-box-width'), height: $(this.target).measure('border-box-height') };
		
		var isFixed = $( _source ).ancestors().find( function(el){ return el.getStyle('position') == 'fixed'; } );
		
		/* RTL bug fixes */
		if( isRTL )
		{
			if( sourcePos.top < 0 )
			{
				sourcePos.top	= realSourcePos.top;
			}
			
			// Really really hacky RTL bug fix... :(
			if( $(_source).id == 'user_link' )
			{
				//alert(sourcePos.left);
				sourcePos.left = sourcePos.left - ( parseInt($(_source).getStyle('padding-left').replace( /px/, '' )) + parseInt($(_source).getStyle('margin-left').replace( /px/, '' )) );
				//alert(sourcePos.left);
			}
		}
		
		// Some logging	
		Debug.write( "realSourcePos: " + realSourcePos.top + " x " + realSourcePos.left );
		Debug.write( "sourcePos: " + sourcePos.top + " x " + sourcePos.left );
		Debug.write( "scrollOffset: " + _offset.top + " x " + _offset.left );
		Debug.write( "_sourcePos: " + _sourcePos.top + " x " + _sourcePos.left );
		Debug.write( "sourceDim: " + sourceDim.width + " x " + sourceDim.height);
		Debug.write( "menuDim: " + menuDim.height );
		Debug.write( "screenDim: " + screenDim.height );
		Debug.write( "manual ofset: " + this.options.offsetX + " x " + this.options.offsetY );

		// Ok, if it's a relative parent, do one thing, else be normal
		// Getting fed up of this feature and IE bugs
		_a = _source.getOffsetParent();
		_b = this.target.getOffsetParent();
		
		Debug.write("_a is " + _a );
		Debug.write("_b is " + _b );
		
		if( isFixed )
		{
			$( this.target ).setStyle('position: fixed');
			
			if( ( _sourcePos.left + menuDim.width ) > screenDim.width ){
				diff = menuDim.width - sourceDim.width;
				pos.left = _sourcePos.left - diff + this.options.offsetX;
			} else {
				pos.left = (_sourcePos.left) + this.options.offsetX;
			}
			
			if( ( _sourcePos.top + menuDim.height ) > screenDim.height ){
				pos.top = _sourcePos.top - menuDim.height + this.options.offsetY;
			} else {
				pos.top = _sourcePos.top + sourceDim.height + this.options.offsetY;
			}
			
			
			$( this.target ).setStyle( 'top: ' + (pos.top-1) + 'px; left: ' + pos.left + 'px;' );
		}
		else
		{
			if( _a != _b )
			{
				// Left
				if( ( realSourcePos.left + menuDim.width ) > screenDim.width ){
					diff = menuDim.width - sourceDim.width;
					pos.left = _sourcePos.left - diff + this.options.offsetX;
				} else {
					if( Prototype.Browser.IE7 ){
						pos.left = (_sourcePos.left) + this.options.offsetX;
					} else {
						pos.left = (_sourcePos.left) + this.options.offsetX;
					}
				}
			
				// Top
				/* If there's no space to open downwards, open upwards *unless*
				/* it would go off the top of the screen (i.e. < 0px ) Bug #18270 */
				if( 
					( ( ( realSourcePos.top + sourceDim.height ) + menuDim.height ) > screenDim.height ) &&
					( _sourcePos.top - menuDim.height + this.options.offsetY ) > 0 )
				{
					pos.top = _sourcePos.top - menuDim.height + this.options.offsetY;
				} else {
					pos.top = _sourcePos.top + sourceDim.height + this.options.offsetY;
				}
			}
			else
			{
				Debug.write("MENU: source offset EQUALS target offset");
			
				// Left
				if( ( realSourcePos.left + menuDim.width ) > screenDim.width ){
					diff = menuDim.width - sourceDim.width;
					pos.left = sourcePos.left - diff + this.options.offsetX;
				} else {
					pos.left = sourcePos.left + this.options.offsetX;
				}
			
				// Top
				/* If there's no space to open downwards, open upwards *unless*
				/* it would go off the top of the screen (i.e. < 0px ) Bug #18270 */
				if( 
					( ( ( realSourcePos.top + sourceDim.height ) + menuDim.height ) > screenDim.height ) &&
					( _sourcePos.top - menuDim.height + this.options.offsetY ) > 0 )
				{
					pos.top = sourcePos.top - menuDim.height + this.options.offsetY;
				} else {
					pos.top = sourcePos.top + sourceDim.height + this.options.offsetY;
				}
			}
			
			$( this.target ).setStyle( 'top: ' + (pos.top-1) + 'px; left: ' + pos.left + 'px;' );
		}
		
		$( this.source ).addClassName('menu_active'); // Set active class on the source
		
		// Now set pos
		Debug.write("Menu position: " + pos.top + " x " + pos.left );
		
		
		// If we have any fixed ancestors, then fix the menu too
		/*if( isFixed ){
			$( this.target ).setStyle('position: fixed');
		}*/
		
		// And show
		new Effect.Appear( $( this.target ), { duration: 0.2, afterFinish: function(e){
				if( Object.isFunction( this.callbacks['afterOpen'] ) )
				{
					this.callbacks['afterOpen']( this );
				}
		}.bind(this) } );
		
		// Set key event so we can close on ESC
		Event.observe( document, 'keypress', this.checkKeyPress.bindAsEventListener( this ) );		
	},
	
	checkKeyPress: function( e )
	{
		//Debug.write( e );
		
		if( e.keyCode == Event.KEY_ESC )
		{
			this.doClose();
		}		
	},

	mouseOutClose: function()
	{
		this.doClose();
	},

	doClose: function()
	{
		new Effect.Fade( $( this.target ), { duration: 0.3, afterFinish: function(e){
				if( Object.isFunction( this.callbacks['afterClose'] ) )
				{
					this.callbacks['afterClose']( this );
				}
		 }.bind( this ) } );
		
		//Debug.write( "Closing " + $( this.source ).id );
		this.source.removeClassName('menu_active');
	},
	
	targetClick: function(e)
	{
		if( !this.options.stopClose ){
			this.doClose();
		}
		
		/*try
		{
			var elem = Event.findElement(e);
			
			if ( elem.hasClassName('_noCloseMenuUponClick') )
			{
				Event.stop(e);
			}
		}
		catch(e) { }
		
		if( ( this.options.stopClose && !$(elem).match("input") || $(elem).match("input") ) ){
			//if( $(e.target).match("[type=checkbox]")){
			//	Debug.write( $(e.target).checked );
			//	$(e.target).checked = true;
			//}
			Event.stop(e);
		}*/
	},
	
	eventClick: function(e, elem)
	{
		if( this.options['eventType'] == 'click' )
		{
			Event.stop(e);
			
			if( $( this.target ).visible() ){
				
				if( Object.isFunction( this.callbacks['beforeClose'] ) )
				{
					this.callbacks['beforeClose']( this );
				}
				
				this.doClose();
			} else {
				ipb.menus.closeAll( $(this.source).id );
				
				if( Object.isFunction( this.callbacks['beforeOpen'] ) )
				{
					this.callbacks['beforeOpen']( this );
				}
				
				this.doOpen(elem);
			}
		}
	},
	
	eventOver: function()
	{
		if( this.options['eventType'] == 'mouseover' )
		{
			if( !$( this.target ).visible() ){
				
				ipb.menus.closeAll( $(this.source).id );
				
				if( Object.isFunction( this.callbacks['beforeOpen'] ) )
				{
					this.callbacks['beforeOpen']( this );
				}
				
				this.doOpen();
			}
		}
	}
});


/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.popup.js - Popup creator					*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

/**
 * Full list of options:
 * 
 * type: 			balloon, pane
 * modal: 			true/false
 * w: 				width
 * h: 				height
 * classname: 		classname to be applied to wrapper
 * initial: 		initial content
 * ajaxURL: 		If supplied, will ping URL for content and update popup
 * close: 			element that will close popup (wont work with balloon)
 * attach: 			{ target, event, mouse, offset }
 * hideAtStart: 	Hide after creation (allows showing at a later time)
 * stem: 			true/false
 * delay: 			{ show, hide }
 */
_popup = window.IPBoard;
_popup.prototype.Popup = Class.create({
		
	initialize: function( id, options, callbacks )
	{
		/* Set up properties */
		this.id				= '';
		this.wrapper		= null;
		this.inner			= null;
		this.stem			= null;
		this.options		= {};
		this.timer			= [];
		this.ready			= false;
		this.visible		= false;
		this._startup		= null;
		this.hideAfterSetup	= false;
		this.eventPairs		= {	'mouseover': 	'mouseout',
								'mousedown': 	'mouseup'
							  };
		this._tmpEvent 		= null;
		
		/* Now run */
		this.id = id;
		this.options = Object.extend({
			type: 				'pane',
			w: 					'500px',
			modal: 				false,
			modalOpacity: 		0.4,
			hideAtStart: 		true,
			delay: 				{ show: 0, hide: 0 },
			defer: 				false,
			hideClose: 			false,
			black:				false,
			warning:			false,
			evalJs:				true,
			closeContents: 		ipb.templates['close_popup']		
		}, arguments[1] || {});
		
		this.callbacks = callbacks || {};
		
		// Are we deferring the load?
		if( this.options.defer && $( this.options.attach.target ) )
		{
			this._defer = this.init.bindAsEventListener( this );
			$( this.options.attach.target ).observe( this.options.attach.event, this._defer );
			
			if( this.eventPairs[ this.options.attach.event ] )
			{
				this._startup = function(e){ this.hideAfterSetup = true; this.hide(); }.bindAsEventListener( this );
				$( this.options.attach.target ).observe( this.eventPairs[ this.options.attach.event ], this._startup  );
			}
		}
		else
		{
			this.init();
		}
	},
	
	init: function()
	{
		try {
			Event.stopObserving( $( this.options.attach.target ), this.options.attach.event, this._defer );
			
			if ( $(this.options.attach.target) )
			{
				var toff = $( this.options.attach.target ).positionedOffset();
				var menu = $(this.options.attach.target).up('.ipbmenu_content');
				
				if ( toff.top == 0 && toff.left == 0 || $(menu) )
				{
					/* make it a centered box */
					this.options.type = 'modal';
					this.options.attach = {};
				}
			}
			
		} catch(err) { }
		
		if(this.id == 'pu_____hover___member')
		{
			setClassName = 'popup-hover';
		}
		else{
			setClassName = 'popupWrapper';
		}
		this.wrapper = new Element('div', { 'id': this.id + '_popup' } ).setStyle('z-index: 10001').hide().addClassName(setClassName);
		
		this.inner = new Element('div', { 'id': this.id + '_inner' } ).addClassName('popupInner');
		
		if ( this.options.black )
		{
			this.inner.addClassName('black_mode');
		}
		
		if ( this.options.warning )
		{
			this.inner.addClassName('warning_mode');
		}
		
		if( this.options.w ){ this.inner.setStyle( 'width: ' + this.options.w ); }
		
		this.wrapper.insert( this.inner );
		
		if( this.options.hideClose != true )
		{
			this.closeLink = new Element('div', { 'id': this.id + '_close' } ).addClassName('popupClose').addClassName('clickable');
			this.closeLink.update( this.options.closeContents );
			this.closeLink.observe('click', this.hide.bindAsEventListener( this ) );
			this.wrapper.insert( this.closeLink );
			
			if ( this.options.black || this.options.warning )
			{
				this.closeLink.addClassName('light_close_button');
			}
		}
		
		$$('body')[0].insert( this.wrapper );
	
		if( this.options.classname ){ this.wrapper.addClassName( this.options.classname ); }
		
		if( this.options.initial ){
			this.update( this.options.initial );
		}
		
		// Callback
		if( Object.isFunction( this.callbacks['beforeAjax'] ) )
		{
			this.callbacks['beforeAjax']( this );
		}
		
		// If we are updating with ajax, handle the show there
		if( this.options.ajaxURL ){
			this.updateAjax();
			setTimeout( this.continueInit.bind(this), 80 );
		} else {
			this.ready = true;
			this.continueInit();
		}
	},
	
	continueInit: function()
	{
		if( !this.ready )
		{
			setTimeout( this.continueInit.bind(this), 80 );
			return;
		}
		
		/* Ensure pop-up isn't larger than viewport */
		if( this.inner.select(".fixed_inner").size() )
		{
			Debug.write("Found fixed_inner");
			this.inner.select(".fixed_inner")[0].setStyle( 'height: ' + this.options.h + 'px; max-height: ' + this.options.h + 'px; overflow: auto' );
		}
		else
		{
			var _vph = document.viewport.getDimensions().height - 25;
			this.options.h = ( this.options.h && _vph > this.options.h ) ? this.options.h : _vph;
			this.inner.setStyle( 'max-height: ' + this.options.h + 'px' );
		}
		
		//Debug.write("Continuing...");
		// What are we making?
		if( this.options.type == 'balloon' ){
			this.setUpBalloon();
		} else {
			this.setUpPane();
		}
		
		// Set up close event
		try {
			if( this.options.close ){
				closeElem = $( this.wrapper ).select( this.options.close )[0];
				
				if( Object.isElement( closeElem ) )
				{
					$( closeElem ).observe( 'click', this.hide.bindAsEventListener( this ) );
				}
			}
		} catch( err ) {
			Debug.write( err );
		}
		
		// Callback
		if( Object.isFunction( this.callbacks['afterInit'] ) )
		{
			this.callbacks['afterInit']( this );
		}
		
		if( !this.options.hideAtStart && !this.hideAfterSetup )
		{
			this.show();
		}
		if( this.hideAfterSetup && this._startup )
		{	
			Event.stopObserving( $( this.options.attach.target ), this.eventPairs[ this.options.attach.event ], this._startup );
		}
	},
	
	updateAjax: function()
	{
		Debug.write( this.options.ajaxURL );
		new Ajax.Request( this.options.ajaxURL,
						{
							method: 'get',
							evalJS: this.options.evalJs,
							onSuccess: function(t)
							{
								if ( t.responseText != 'error' )
								{ 
									try
									{
										if ( ! Object.isUndefined( t.responseJSON ) && ! Object.isUndefined( t.responseJSON['error'] ) )
										{
											if ( t.responseJSON['__board_offline__'] )
											{
												ipb.global.errorDialogue( ipb.lang['board_offline'] );
												ipb.menus.closeAll(e);
											}
											else
											{
												ipb.global.errorDialogue( t.responseJSON['error'] );
											}
											
											return false;
										}
									} catch(e){}
									
									if ( t.responseText == 'nopermission' )
									{
										ipb.global.errorDialogue( ipb.lang['no_permission'] );
										return;
									}
									
									/* Check for log out */
									if ( t.responseText.match( "__session__expired__log__out__" ) )
									{
										this.update('');
										alert( ipb.lang['session_timed_out'] );
										return false;
									}
									
									Debug.write( "AJAX done!" );
									this.update( t.responseText );
									this.ready = true;
									
									// Callback
									if( Object.isFunction( this.callbacks['afterAjax'] ) )
									{
										this.callbacks['afterAjax']( this, t.responseText );
									}
								}
								else
								{
									Debug.write( t.responseText );
									return;
								}
							}.bind(this)
						});
	},
	
	show: function(e)
	{ 
		if( e ){ Event.stop(e); }
		
		if( this.timer['show'] ){
			clearTimeout( this.timer['show'] );
		}
		
		if( this.options.delay.show != 0 ){
		
			this.timer['show'] = setTimeout( this._show.bind( this ), this.options.delay.show );
		} else {
			this._show(); // Just show it
		}
	},
	
	hide: function(e)
	{
		if( e ){ Event.stop(e); }
		if( this.document_event ){
			Event.stopObserving( document, 'click', this.document_event );
		}
		
		if( this.timer['hide'] ){
			clearTimeout( this.timer['hide'] );
		}
				
		if( this.options.delay.hide != 0 ){
			this.timer['hide'] = setTimeout( this._hide.bind( this ), this.options.delay.hide );
		} else {
			this._hide(); // Just hide it
		}
	},
	
	/* remove wrapper and kill timeouts */
	kill: function()
	{
		if( this.timer['hide'] ){
			clearTimeout( this.timer['hide'] );
		}
		
		if( this.timer['show'] ){
			clearTimeout( this.timer['show'] );
		}
		
		if( $( this.wrapper ) )
		{
			$( this.wrapper ).remove();
		}
	},
	
	_show: function()
	{
		this.visible = true;
		
		/* Experimental */
		try
		{
			if ( this.options.warning )
			{
				_wrap = this.inner.down('h3').next('div');
				
				if ( _wrap )
				{
					if ( ! _wrap.className.match( /moderated/ ) )
					{
						_wrap.addClassName('moderated');
					}
				}
			}
		} catch(e){}
		
		if( this.options.modal == false ){
			new Effect.Appear( $( this.wrapper ), { duration: 0.3, afterFinish: function(){
				if( Object.isFunction( this.callbacks['afterShow'] ) )
				{
					this.callbacks['afterShow']( this );
				}
			}.bind(this) } );
			this.document_event = this.handleDocumentClick.bindAsEventListener(this);
			this.setDocumentEvent();
		} else {
			new Effect.Appear( $('document_modal'), { duration: 0.3, to: this.options.modalOpacity, afterFinish: function(){
				new Effect.Appear( $( this.wrapper ), { duration: 0.4, afterFinish: function(){
					if( Object.isFunction( this.callbacks['afterShow'] ) )
					{
						this.callbacks['afterShow']( this );
					}
			 	}.bind(this) } );
			}.bind(this) });
		}
	},
	
	_hide: function()
	{
		this.visible = false;
		
		if( this._tmpEvent != null )
		{
			Event.stopObserving( $( this.wrapper ), 'mouseout', this._tmpEvent );
			this._tmpEvent = null;
		}
						
		if( this.options.modal == false ){
			new Effect.Fade( $( this.wrapper ), { duration: 0.3, afterFinish: function(){
				if( Object.isFunction( this.callbacks['afterHide'] ) )
				{
					this.callbacks['afterHide']( this );
				}
			}.bind(this) } );
		} else {
			new Effect.Fade( $( this.wrapper ), { duration: 0.3, afterFinish: function(){
				new Effect.Fade( $('document_modal'), { duration: 0.2, afterFinish: function(){
					if( Object.isFunction( this.callbacks['afterHide'] ) )
					{
						this.callbacks['afterHide']( this );
					}
				}.bind(this) } );
			}.bind(this) });
		}
	},
	
	setDocumentEvent: function()
	{		
		if( !ipb.vars['is_touch'] ){
			Event.observe( document, 'click', this.document_event );
			return;
		}
		
		// Touch event
		Event.observe( document, 'touchstart', this.document_event );
	},
	
	handleDocumentClick: function(e)
	{
		Debug.write( 'document click: ' + Event.element(e).id);
		
		if( !Event.element(e).descendantOf( this.wrapper ) && ( this.options.attach && ( Event.element(e).id != this.options.attach.target.id ) ) )
		{
			this.hide(e);
		}
	},
	
	update: function( content, evalScript )
	{
		if( Object.isElement( content ) ){
			this.inner.insert( { bottom: content } );
		} else {
			this.inner.update( content );
		}
		
		// Should this popup eval scripts? Default is YES
		if( Object.isUndefined( evalScript ) || evalScript != false ){
			this.inner.innerHTML.evalScripts();
		}
	},
	
	setUpBalloon: function()
	{
		// Are we attaching?
		if( this.options.attach )
		{
			var attach = this.options.attach;
			
			if( attach.target && $( attach.target ) )
			{				
				if( this.options.stem == true )
				{
					this.createStem();
				}
				
				// Get position
				if( !attach.position ){ attach.position = 'auto'; }
				
				if( isRTL )
				{
					if( Object.isUndefined( attach.offset ) ){ attach.offset = { top: 0, right: 0 }; }
					if( Object.isUndefined( attach.offset.top ) ){ attach.offset.top = 0; }
					if( Object.isUndefined( attach.offset.left ) ){ attach.offset.right = 0; }else{ attach.offset.right = attach.offset.left; }
				}
				else
				{
					if( Object.isUndefined( attach.offset ) ){ attach.offset = { top: 0, left: 0 }; }
					if( Object.isUndefined( attach.offset.top ) ){ attach.offset.top = 0; }
					if( Object.isUndefined( attach.offset.left ) ){ attach.offset.left = 0; }
				}
				
				if( attach.position == 'auto' )
				{
					Debug.write("Popup: auto-positioning");
					var screendims 		= document.viewport.getDimensions();
					var screenscroll 	= document.viewport.getScrollOffsets();
					var toff			= $( attach.target ).viewportOffset();
					var wrapSize 		= $( this.wrapper ).getDimensions();
					var delta 			= [0,0];
					
					if (Element.getStyle( $( attach.target ), 'position') == 'absolute')
					{
						var parent = attach.target.getOffsetParent();
						delta = parent.viewportOffset();
				    }
				
				    if( isRTL )
				    {
				    	toff['right'] = screendims.width - ( toff[0] - delta[0] );
				    }
				    else
				    {
				    	toff['left'] = toff[0] - delta[0];
				    }
					toff['top'] = toff[1] - delta[1] + screenscroll.top;

					//Debug.write( toff['left'] + "    " + toff['top'] );
					// Need to figure out if it will be off-screen
					var start 	= 'top';
					
					if( isRTL ){
						var end 	= 'right';
					} else {
						var end 	= 'left';
					}

					//Debug.write( "Target offset top: " + toff.top + ", wrapSize Height: " + wrapSize.height + ", screenscroll top: " + screenscroll.top);
					if( ( toff.top - wrapSize.height - attach.offset.top ) < ( 0 + screenscroll.top ) ){
						var start = 'bottom';
					}
					
					if( isRTL )
					{
						if( ( toff.right + wrapSize.width - attach.offset.right ) < ( screendims.width - screenscroll.left ) ){
							var end = 'left';
						}
					}
					else
					{
						if( ( toff.left + wrapSize.width - attach.offset.left ) > ( screendims.width - screenscroll.left ) ){
							var end = 'right';
						}
					}

					finalPos = this.position( start + end, { target: $( attach.target ), content: $( this.wrapper ), offset: attach.offset } );
					
					if( this.options.stem == true )
					{
						finalPos = this.positionStem( start + end, finalPos );
					}
				}
				else
				{
					Debug.write("Popup: manual positioning");
					
					finalPos = this.position( attach.position, { target: $( attach.target ), content: $( this.wrapper ), offset: attach.offset } );
					
					if( this.options.stem == true )
					{
						finalPos = this.positionStem( attach.position, finalPos );
					}
				}
				
				// Add mouse events
				if( !Object.isUndefined( attach.event ) ){
					$( attach.target ).observe( attach.event, this.show.bindAsEventListener( this ) );
					
					if( attach.event != 'click' && !Object.isUndefined( this.eventPairs[ attach.event ] ) ){
						$( attach.target ).observe( this.eventPairs[ attach.event ], this.hide.bindAsEventListener( this ) );
					}
						
					$( this.wrapper ).observe( 'mouseover', this.wrapperEvent.bindAsEventListener( this ) );					
				}				
			}
		}
		
		if( isRTL )
		{
			Debug.write("Popup: Right: " + finalPos.right + "; Top: " + finalPos.top);
			$( this.wrapper ).setStyle( 'top: ' + finalPos.top + 'px; right: ' + finalPos.right + 'px; position: absolute;' );
		}
		else
		{
			Debug.write("Popup: Left: " + finalPos.left + "; Top: " + finalPos.top);
			$( this.wrapper ).setStyle( 'top: ' + finalPos.top + 'px; left: ' + finalPos.left + 'px; position: absolute;' );
		}
	},
	
	wrapperEvent: function(e)
	{
		if( this.timer['hide'] )
		{
			// Cancel event now
			clearTimeout( this.timer['hide'] );
			this.timer['hide'] = null;
			
			if( this.options.attach.event && this.options.attach.event == 'mouseover' )
			{
				// Set new event to account for mouseout of the popup,
				// but only if we don't already have one - otherwise we get
				// expontentially more event calls. Bad.
				if( this._tmpEvent == null ){
					this._tmpEvent = this.hide.bindAsEventListener( this );
					$( this.wrapper ).observe('mouseout', this._tmpEvent );
				}
			}
		}
	},
	
	positionStem: function( pos, finalPos )
	{
		var stemSize = { height: 16, width: 31 };
		var wrapStyle = {};
		var stemStyle = {};
		
		switch( pos.toLowerCase() )
		{
			case 'topleft':
				wrapStyle = { marginBottom: stemSize.height + 'px' };
				
				if( isRTL )
				{
					stemStyle = { bottom: -(stemSize.height) + 'px', right: '5px' };
					finalPos.right = finalPos.right - 15;
				}
				else
				{
					stemStyle = { bottom: -(stemSize.height) + 'px', left: '5px' };
					finalPos.left = finalPos.left - 15;
				}
				break;
			case 'topright':
				wrapStyle = { marginBottom: stemSize.height + 'px' };

				if( isRTL )
				{
					stemStyle = { bottom: -(stemSize.height) + 'px', left: '5px' };
					finalPos.right = finalPos.right + 15;
				}
				else
				{
					stemStyle = { bottom: -(stemSize.height) + 'px', right: '5px' };
					finalPos.left = finalPos.left + 15;
				}
				break;
			case 'bottomleft':
				wrapStyle = { marginTop: stemSize.height + 'px' };

				if( isRTL )
				{
					stemStyle = { top: -(stemSize.height) + 'px', right: '5px' };
					finalPos.right = finalPos.right - 15;
				}
				else
				{
					stemStyle = { top: -(stemSize.height) + 'px', left: '5px' };
					finalPos.left = finalPos.left - 15;
				}
				break;
			case 'bottomright':
				wrapStyle = { marginTop: stemSize.height + 'px' };

				if( isRTL )
				{
					stemStyle = { top: -(stemSize.height) + 'px', left: '5px' };
					finalPos.right = finalPos.right + 15;
				}
				else
				{
					stemStyle = { top: -(stemSize.height) + 'px', right: '5px' };
					finalPos.left = finalPos.left + 15;
				}
				break;
		}

		$( this.wrapper ).setStyle( wrapStyle );
		$( this.stem ).setStyle( stemStyle ).setStyle('z-index: 6000').addClassName( pos.toLowerCase() );
		
		return finalPos;
	},
	
	position: function( pos, v )
	{
		finalPos = {};
		
		v.target.identify();
		
		var toff			= $( v.target.id ).viewportOffset();
		var tsize	 		= $( v.target.id ).getDimensions();
		var wrapSize 		= $( v.content ).getDimensions();
		var screenscroll 	= document.viewport.getScrollOffsets();
		var offset 			= v.offset;
		var delta			= [0,0];
		
		if (Element.getStyle( $( v.target.id ), 'position') == 'absolute')
		{
			var parent = $( v.target.id ).getOffsetParent();
			delta    = parent.viewportOffset();
			
			/* That above doesn't help */
			delta = [0,0];
		
	    }
		
	    if( isRTL )
	    {
			toff['right'] = document.viewport.getDimensions().width - ( toff[0] - delta[0] );
		}
		else
		{
			toff['left'] = toff[0] - delta[0];
		}
	    
		toff['top'] = toff['top'] - delta[1] + screenscroll.top;
				
		switch( pos.toLowerCase() )
		{
			case 'topleft':
				finalPos.top = ( toff.top - wrapSize.height - ( tsize.height / 2 ) ) - offset.top;
				
				if( isRTL )
				{
					finalPos.right = toff.right + offset.right;
				}
				else
				{
					finalPos.left = toff.left + offset.left;
				}
				break;
			case 'topright':			
			 	finalPos.top = ( toff.top - wrapSize.height - ( tsize.height / 2 ) ) - offset.top;
			 	
			 	if( isRTL )
			 	{
			 		finalPos.right = ( toff.right - ( wrapSize.width - tsize.width ) ) - offset.right;
			 	}
			 	else
			 	{
					finalPos.left = ( toff.left - ( wrapSize.width - tsize.width ) ) - offset.left;
				}
				break;
			case 'bottomleft':
				finalPos.top = ( toff.top + tsize.height ) + offset.top;
				
				if( isRTL )
				{
					finalPos.right = toff.right + offset.right;
				}
				else
				{
					finalPos.left = toff.left + offset.left;
				}
				break;
			case 'bottomright':
				finalPos.top = ( toff.top + tsize.height ) + offset.top;
				
				if( isRTL )
				{
					finalPos.right = ( toff.right - ( wrapSize.width - tsize.width ) ) - offset.right;
				}
				else
				{
					finalPos.left = ( toff.left - ( wrapSize.width - tsize.width ) ) - offset.left;
				}
				break;
		}
	
		return finalPos;
	},
	
	createStem: function()
	{
		this.stem = new Element('div', { id: this.id + '_stem' } ).update('&nbsp;').addClassName('stem');
		this.wrapper.insert( { top: this.stem } );
	},
	
	setUpPane: function()
	{
		// Does the document have a modal blackout?
		if( !$('document_modal') ){
			this.createDocumentModal();
		}
		
		this.positionPane();	
	},
	
	positionPane: function()
	{
		// Position it in the middle
		var elem_s = $( this.wrapper ).getDimensions();
		var window_s = document.viewport.getDimensions();
		var window_offsets = document.viewport.getScrollOffsets();

		if( ipb.vars['is_touch'] ){
			// #35826: On some skins, webkit will handle the document viewport sizes incorrectly.
			// To try and protect against this, we'll use the actual screen size for these
			// calculations.
			window_s = { width: window.innerWidth, height: window.innerHeight };
		}
		
		if( isRTL )
		{
			var center = { 	right: ((window_s['width'] - elem_s['width']) / 2),
						 	top: (((window_s['height'] - elem_s['height']) / 2)/2)
						};
						
			if( center.top < 10 ){ center.top = 10; }
						
			$( this.wrapper ).setStyle('top: ' + center['top'] + 'px; right: ' + center['right'] + 'px; position: fixed;');

		}
		else
		{
			var center = { 	left: ((window_s['width'] - elem_s['width']) / 2),
						 	top: (((window_s['height'] - elem_s['height']) / 2)/2)
						};

			if( center.top < 10 ){ center.top = 10; }
						
			$( this.wrapper ).setStyle('top: ' + center['top'] + 'px; left: ' + center['left'] + 'px; position: fixed;');
		}
	},
			
	createDocumentModal: function()
	{
		var pageLayout = $( document.body ).getLayout();
		var pageSize = { width: pageLayout.get('width'), height: pageLayout.get('margin-box-height') };
		var viewSize = document.viewport.getDimensions();
		
		var dims = [];
		
		Debug.dir( pageSize );
		Debug.dir( viewSize );
		
		if( viewSize['height'] < pageSize['height'] ){
			dims['height'] = pageSize['height'];
		} else {
			dims['height'] = viewSize['height'];
		}
		
		if( viewSize['width'] < pageSize['width'] ){
			dims['width'] = pageSize['width'];
		} else {
			dims['width'] = viewSize['width'];
		}
		
		var modal = new Element( 'div', { 'id': 'document_modal' } ).addClassName('modal').hide();
		
		if( isRTL ){
			modal.setStyle('width: ' + dims['width'] + 'px; height: ' + dims['height'] + 'px; position: fixed; top: 0px; right: 0px; z-index: 10000;');
		} else {
			modal.setStyle('width: ' + dims['width'] + 'px; height: ' + dims['height'] + 'px; position: fixed; top: 0px; left: 0px; z-index: 10000;');
		}
		
		$$('body')[0].insert( modal );
	},
	
	getObj: function()
	{
		return $( this.wrapper );
	}
});

/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.ticker.js - Popup creator				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/
_ticker = window.IPBoard;
_ticker.prototype.Ticker = Class.create({		
	initialize: function( root, options, callbacks )
	{
		if( !$(root) ){
			return;
		}
		this.root = $(root);
		this.options = Object.extend({
			duration: 4,
			select: "li"
		}, options || {});
		
		this.items = $( root ).select( this.options.select );
		if( !this.items.length ){ return; }
		
		// Hide all items except first
		this.items.invoke('hide').first().show();
		
		// Start timer
		this.timer = this.nextItem.bind( this ).delay( this.options.duration );
		
		// Pause event
		$( this.root ).observe('mouseenter', this.pauseTicker.bindAsEventListener( this ) );
		$( this.root ).observe('mouseleave', this.unpauseTicker.bindAsEventListener( this ) );
	},
	pauseTicker: function(e)
	{
		clearTimeout( this.timer );
	},
	unpauseTicker: function(e)
	{
		this.timer = this.nextItem.bind(this).delay( this.options.duration );
	},
	nextItem: function()
	{
		// Find current item
		var cur = this.items.find( function(elem){
			return elem.visible();
		});

		var next = $( cur ).next( this.options.select );

		if( Object.isUndefined( next ) ){
			next = this.items.first();
		}

		// Fade current
		new Effect.Fade( $( cur ), { duration: 0.4, queue: 'end', afterFinish: function(){
			new Effect.Appear( $( next ), { duration: 0.8, queue: 'end' } );
		} } );

		// Reset timer
		this.timer = this.nextItem.bind( this ).delay( this.options.duration );
	}
});

function warningPopup( elem, id )
{
	var _url = ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=warnings&id=' + id;
	new ipb.Popup( 'warning' + id, {
		type: 'balloon',
		stem: true,
		attach: { target: elem, position: 'auto' },
		hideAtStart: false,
		ajaxURL: _url,
		w: '600px',
		h: 800
	});
}

ipb = new IPBoard;
ipb.global.init();
ipb.menus.init();