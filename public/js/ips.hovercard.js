/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.hoverCard.js - Delayed pop-up menu		*/
/* (c) IPS, Inc 2010							*/
/* -------------------------------------------- */
/* Author: Matt Mecham              			*/
/************************************************/

/* ===================================================================================================== */
/* IPB3 Hover card manager */

IPBoard.prototype.hoverCardRegister = {
	
	mainStore: $H(),
	
	initialize: function( key, options )
	{
		var store = $H();
		
		/* Insert into main store */
		if ( ! ipb.hoverCardRegister.mainStore.get( key ) )
		{
			ipb.hoverCardRegister.mainStore.set( key, options );
		}
		
		$$('._hovertrigger').each( function( elem )
		{
			try
			{
				_key = $(elem).readAttribute( "hovercard-ref" );
				
				if ( key == _key )
				{
					$(elem).addClassName( '___hover___' + key );
					store.set( 'key', key );
					
					/* remove trigger class to prevent re-iteration later */
					$(elem).removeClassName('_hovertrigger');
					$(elem).addClassName('_hoversetup');
				}
			}
			catch( err )
			{
				Debug.error( err );
			}
		} );
		
		/* Set up cards */
		store.each( function( elem )
		{
			new ipb.hoverCard( '___hover___' + elem.value, options );
		} );
	},
	
	/* Post ajax update 'cos new content added via ajax won't be set up */
	postAjaxInit: function()
	{
		ipb.hoverCardRegister.mainStore.each( function( elem )
		{
			ipb.hoverCardRegister.initialize( elem.key, elem.value );
		} );
	}
};

IPBoard.prototype.hoverCard = Class.create( {
	
	/**
	 * options.ajaxUrl: Pass a complete ajax URL in.
	 * options.ajaxCacheExpire:	Number of seconds to store the ajax cache. 0 to not cache
	 * options.type			  : Balloon* or pane
	 * options.position		  : Auto, topLeft, bottomLeft*, topRight, bottomRight
	 */
	/*------------------------------*/
	/* Constructor 					*/
	initialize: function( className, options )
	{
		this.id          = className;
		this.timer	     = {},
		this.card	     = false,
		this.ajaxCache   = {},
		this.popupActive = {},
		this.openId      = false;
		this.curEvent    = false;
		
		this.options = Object.extend( {
										type: 			 'balloon',
										position: 		 'bottomLeft',
										w:    			 '500px',
										openOnClick:	 false,
										openOnHover:	 true,
										ajaxUrl:		 false,
										delay:			 800,
										ajaxCacheExpire: 0,
										black:			 false,
										getId:			 false,
										setIdParam:		 'id',
										callback: false }, arguments[1] );
		this.init();
	},
	
	init: function()
	{
		this.debugWrite( "hoverCard.init()" );
		
		/* Scoping issues #ftl */
		var _hc = this;
		
		document.observe( 'mousemove', _hc.mMove.bindAsEventListener( _hc ) );
		
		/* Set up elements */
		$$('.' + this.id ).each( function( elem )
		{
			elem.identify();
			
			try
			{
				Event.stopObserving( $( elem.id ), 'mouseout' );
				Event.stopObserving( $( elem.id ), 'mouseover' );
				
				/* title attributes murder the pop-up effect */
				$( elem.id ).writeAttribute( 'title', '' );
				
				if ( $( elem.id ).down('a') )
				{
					$( elem.id ).down('a').writeAttribute( 'title', '' );
				}
				
				if( $( elem.id ).down('img') )
				{
					$( elem.id ).down('img').writeAttribute( 'title', '' );
					$( elem.id ).down('img').writeAttribute( 'alt', '' );
				}
			}
			catch (aBall){}
			
			$( elem.id ).observe( 'contextmenu' , _hc.mContext.bindAsEventListener( _hc, elem.id ) );
			$( elem.id ).observe( 'click'       , _hc.mClick.bindAsEventListener( _hc, elem.id ) );
			$( elem.id ).observe( 'mouseover'   , _hc.mOver.bindAsEventListener( _hc, elem.id ) );
			$( elem.id ).observe( 'mouseout'    , _hc.mOut.bindAsEventListener( _hc, elem.id ) );
		} );
	},
	
	/**
	 * Mouse Move
	 */
	mMove: function(e)
	{
		/* IE loses reference to event, so we need to copy it instead of use original reference */
		var _newEvent	= {};
		for( var i in e ) { _newEvent[i] = e[i]; }
		
		this.curEvent = _newEvent;
	},
	
	/**
	 * ITEM CLICK
	 */
	mClick: function(e, id)
	{
		if ( ! this.options.openOnClick )
		{
			this.close(id);
		}
		else
		{
			
			if ( $(id).tagName.toLowerCase() == 'input' && $(id).type.toLowerCase() == 'checkbox' )
			{
				/* If this is a checkbox, and we're choosing to show on click then we probably only
				 * want to show the card if we're clicking 'true'
				 */
				
				if ( $(id).checked !== true )
				{
					return true;
				}
			}
			
			this.show(id);
		}
	},
	
	/**
	 * ITEM Context menu
	 */
	mContext: function(e, id)
	{
		this.close(id);
	},
	
	/**
	 * ITEM MOUSEOVER
	 */
	mOver: function(e, id)
	{
		Event.stop(e);
				
		if ( this.overPopUp(id) === true )
		{
			return false;
		}
		
		if ( this.options.openOnHover !== true )
		{
			return false;
		}
		
		this.debugWrite( "mover - setting time OVER " + id );
		
		if ( ! Object.isUndefined( this.timer[id + '_out'] ) )
		{
			clearTimeout( this.timer[id + '_out'] );
		}
		
		/* Set timer */
		this.timer[id + '_over'] = setTimeout( this.show.bind(this, id), this.options.delay );															 
	},
	
	/*
	 * ITEM MOUSEOUT
	 */
	mOut: function(e, id)
	{
		Event.stop(e);
		
		if ( this.overPopUp(id) === true )
		{
			return false;
		}
		
		/* Pop up class dominates the mouseover class */
		Event.stopObserving( $( id ), 'mouseover' );
		$( id ).observe( 'mouseover' , this.mOver.bindAsEventListener( this, id ) );
		
		/* Clear any time out */
		if ( ! Object.isUndefined( this.timer[id + '_over'] ) )
		{
			clearTimeout( this.timer[id + '_over'] );
		}
		
		/* Set timer */
		this.debugWrite( "Mout - setting time OUT " + id );
		this.timer[id + '_out'] = setTimeout( this.close.bind(this, id), 800 );
	},
	
	/*
	 * SHOW POP UP
	 */
	show: function(id)
	{
		var popup = 'pu__' + this.id + '_popup';
		
		/* Clear any time out */
		if ( ! Object.isUndefined( this.timer[id + '_out'] ) )
		{
			clearTimeout( this.timer[id + '_out'] );
		}
		
		if ( ! Object.isUndefined( this.card ) && this.card !== false )
		{
			this.card.kill();
			this.card = false;
		}
		
		/* If we have an old popup from a previous class method, kill it 'cos this.card won't be populated */
		if ( $(popup) )
		{
			$(popup).remove();
		}
		
		/* Set this as open */
		this.openId = id;
		
		var content = false;
		
		if ( this.options.ajaxUrl )
		{
			content = "<div class='general_box pad' style='height: 130px; padding-top: 130px; text-align:center'><img src='" + ipb.vars['loading_img'] + "' alt='' /></div>";
		}
		else
		{
			/* Run call back */
			if ( Object.isFunction( this.options.callback ) )
			{
				content = this.options.callback( this, id );
				
				/* Did we decide to skip output? */
				if ( content === false )
				{
					return false;
				}
			}
			else
			{
				Debug.error( "No AJAX or Callback specified. Whaddayagonnado?!" );
			}
		}
		
		this.card = new ipb.Popup( 'pu__' + this.id, {  type: 'balloon',
														initial: content,
														stem: true,
														hideAtStart: false,
														hideClose: true,
														defer: false,
														black: this.options.black,
														attach: { target: $( id ), position: this.options.position },
														w: this.options.w } );
		
		/* Pop up class dominates the mouseover class */
		Event.stopObserving( $( id ), 'mouseout' );
		Event.stopObserving( $( id ), 'contextmenu' );
		Event.stopObserving( $( id ), 'click' );
		
		$( id ).observe( 'mouseout'    , this.mOut.bindAsEventListener( this, id ) );
		$( id ).observe( 'contextmenu' , this.mContext.bindAsEventListener( this, id ) );
		$( id ).observe( 'click'       , this.mClick.bindAsEventListener( this, id ) );
		
		if ( this.options.ajaxUrl )
		{
			this.ajax( id );
		}
	},
	
	/**
	 * CLOSE POP-UP
	 */
	close: function(id)
	{
		/* Don't close if we brushed past another trigger and are now over a pop-up */
		if ( this.overPopUp(id) === true )
		{
			return false;
		}
		
		this.debugWrite( "Close: " + id );
		
		/* Clear all time outs */
		if ( ! Object.isUndefined( this.timer[id + '_out'] ) )
		{
			this.debugWrite( "-- Clearing: " + id + '_out' );
			clearTimeout( this.timer[id + '_out'] );
		}
		
		if ( ! Object.isUndefined( this.timer[id + '_over'] ) )
		{
			this.debugWrite( "-- Clearing: " + id + '_over' );
			clearTimeout( this.timer[id + '_over'] );
		}
		
		if ( ! Object.isUndefined( this.card ) && this.card !== false && id == this.openId )
		{
			/* Hide makes card fade nicely, we kill it in open */
			this.card.hide();
			this.card = false;
			
			/* Set this as closed */
			this.openId = false;
		}
	},
	
	/**
	 * Load ajax URL
	 */
	ajax: function( id )
	{
		var now   = this.unixtime();
		var url   = false;
		var bDims = {};
		var aDims = {};
		
		/* Before we load ajax, grab the top and height of item */
		var popup = 'pu__' + this.id + '_popup';
		//this.debugWrite( $(popup).innerHTML);
		bDims['height'] = $( popup ).getHeight();
		bDims['top']    = parseInt( $( popup ).style.top );
		
		if ( ! Object.isUndefined( this.ajaxCache[ id ] ) )
		{
			if ( this.options.AjaxCacheExpire )
			{
				if ( now - parseInt( this.options.AjaxCacheExpire ) < this.ajaxCache[ id ]['time'] )
				{
					this.debugWrite( "Fetching from cache " + id );
					
					/* Just update the card with the contents previously fetched */
					this.card.update( this.ajaxCache[ id ]['content'] );
					this.card.ready = true;
										
					/* Now Check after and reposition if need be */
					this._rePos( bDims, popup, this.id );
					
					return;
				}
			}
			else
			{
				this.debugWrite( "Fetching from cache " + id );
					
				/* Just update the card with the contents previously fetched */
				this.card.update( this.ajaxCache[ id ]['content'] );
				this.card.ready = true;
				
				/* Now Check after and reposition if need be */
				this._rePos( bDims, popup, this.id );				
				
				return;
			}
		}
		
		/* Fetching ID? */
		if ( this.options.getId )
		{
			var _id = $(id).readAttribute('hovercard-id');
			url = this.options.ajaxUrl + '&' + this.options.setIdParam + '=' + _id;
		}
		
		this.debugWrite( "Ajax load " + id + " " + url );
		
		new Ajax.Request( url,
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( t.responseText != 'error' )
								{
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
										return;
									}
									
									/* Check for log out */
									if ( t.responseText.match( "__session__expired__log__out__" ) )
									{
										this.update('');
										alert( "Your session has expired, please refresh the page and log back in" );
										return false;
									}
									
									this.debugWrite( "AJAX done!" );
									this.card.update( t.responseText );
									this.card.ready = true;
									
									/* Now Check after and reposition if need be */
									this._rePos( bDims, popup, this.id );						
									
									this.ajaxCache[ id ] = { 'content': t.responseText, 'time': now };
								}
								else
								{
									this.debugWrite( t.responseText );
									return;
								}
							}.bind(this)
						});
	},
	
	_rePos: function( bDims, popup, id )
	{
		//Debug.dir( bDims);
		//Debug.write('here'); //Debug.dir( aDims);
		//this.debugWrite(id)/;
		/* Now Check after and reposition if need be */
		aDims = {};
		aDims['height'] = $( popup ).getHeight();
		aDims['top']    = parseInt( $( popup ).getStyle( 'top' ) );
		
		if ( $( 'pu__' + id + '_stem').className.match( /top/ ) && ( aDims['height'] != bDims['height'] ) )
		{
			var _nt = bDims['top'] - ( aDims['height'] - bDims['height'] ) - 10;
			
			$( popup ).setStyle( { 'top': _nt + 'px' } );										
		}		
	},
	
	/**
	 * Fetch unixtime
	 *
	 */
	unixtime: function()
	{
		var _time = new Date();
		return Date.parse( _time ) / 1000;
	},
	
	/**
	 * Hovering over a pop-up?
	 */
	overPopUp: function( id )
	{
		var myevent = this.curEvent;
		
		if( !id ){ return; }
		
		/* Moz chokes without try. */
		try
		{
			if ( $(Event.findElement(myevent)) && $(Event.findElement(myevent)).descendantOf( $('pu__' + this.id + '_popup') ) )
			{
				this.debugWrite( "*** Over Pop Up ***" );
				
				/* Test close again */
				if ( this.openId !== false )
				{
					this.timer[id + '_out'] = setTimeout( this.close.bind(this, id), 800 );
				}
				
				return true;
			}
		}
		catch(err){}
		
		return false;
	},
	
	/**
	 * Debug write. Allows me to turn this off quickly
	 */
	debugWrite: function( text )
	{
		Debug.write( text );
	}
} );
