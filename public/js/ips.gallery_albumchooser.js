/********************************************************/
/* IPB3 Javascript										*/
/* ---------------------------------------------------- */
/* ips.gallery_albumchooser.js - Gallery slideshow code	*/
/* (c) IPS, Inc 2011									*/
/* ---------------------------------------------------- */
/* Author: Matt Mecham      							*/
/********************************************************/

var _ac = window.IPBoard;

_ac.prototype.gallery_albumChooser = {
	
	popups: {},
	params: $H(),
	callBack: '',
	triggerElem: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.gallery_album_chooser.js");	
		
		/* initialise */
		ipb.delegate.register( 'a[data-album-selector]', ipb.gallery_albumChooser.launch );
		ipb.delegate.register( 'input[data-album-select-album-id]', ipb.gallery_albumChooser.select );
		ipb.delegate.register( 'a[data-album-pane-update]', ipb.gallery_albumChooser.updatePane );
		ipb.delegate.register( '#searchGo', ipb.gallery_albumChooser.search );
	},
	
	/**
	 * Manually add a param
	 */
	setParam: function( k, v )
	{
		ipb.gallery_albumChooser.params.set( k, v );
	},
	getParam: function( k )
	{
		return ipb.gallery_albumChooser.params.get( k );
	},
	getAllParamsAsQueryString: function()
	{
		return ipb.gallery_albumChooser.params.toQueryString() + '&';
	},
	getAllParams: function()
	{
		return ipb.gallery_albumChooser.params;
	},
	
	/**
	 * SAVE
	 */
	select: function( e, elem )
	{
		albumId = parseInt( elem.readAttribute('data-album-select-album-id') );
		
		/* Fire it off */
		var _url  = ipb.gallery_albumChooser._ajaxUrl() + '&do=select&secure_key=' + ipb.vars['secure_hash'] + '&album_id=' + albumId;
		
		Debug.write( _url );
		
		/* Send AJAX request */
		new Ajax.Request( _url,
							   { method: 'get',
								 onSuccess: function(t)
								 {
									if ( t.responseJSON['status'] == 'ok' )
									{
										if ( ipb.gallery_albumChooser.callBack )
										{
											Debug.write( 'Firing callback  ' + ipb.gallery_albumChooser.callBack );
											
											/* This is ugly but we pull callback from data-elem so it's not a real reference */
											eval( ipb.gallery_albumChooser.callBack + '(t.responseJSON);' );
										}
										else
										{
											try
											{
												var autoUpdate = ipb.gallery_albumChooser.triggerElem.readAttribute('data-album-selector-auto-update').evalJSON();
												
												if ( ! Object.isUndefined( autoUpdate ) )
												{
													if ( autoUpdate.field )
													{
														$( autoUpdate.field ).value = t.responseJSON['album_id'];
													}
													
													if ( autoUpdate.div )
													{
														$( autoUpdate.div ).update( t.responseJSON['allParents'] );
														
														if ( ! $( autoUpdate.div ).visible() )
														{
															new Effect.Appear( $( autoUpdate.div ) );
														}
													}
												}
											}
											catch(e){ Debug.dir(e);}
										}
										
										ipb.gallery_albumChooser.popups['boSelector'].hide();
									}
								 }
							 } );
		
	},
	
	/**
	 * L(a)unch
	 */
	launch: function( e, elem )
	{
		Event.stop(e);
		
		var _cb = null;
		
		/* reset */
		ipb.gallery_albumChooser.callBack = null;
		
		try {
			_cb = elem.readAttribute('data-album-selector-callback');
		} catch(e) { }
		
		if ( _cb )
		{
			ipb.gallery_albumChooser.callBack = _cb;
		}
		
		if ( ! Object.isUndefined( ipb.gallery_albumChooser.popups['boSelector'] ) )
		{
			ipb.gallery_albumChooser.popups['boSelector'].kill();
		}
		
		var _params = ipb.gallery_albumChooser.getAllParamsAsQueryString();
		
		try {
			_params += elem.readAttribute('data-album-selector').replace( /&amp;/g, '&' );
		} catch(e){}
		
		/* Store this element */
		ipb.gallery_albumChooser.triggerElem = elem;
		
		var _url  = ipb.gallery_albumChooser._ajaxUrl() + '&do=show&secure_key=' + ipb.vars['secure_hash'] + '&' + _params;
		
		/* Lock and load */
		ipb.gallery_albumChooser.popups['boSelector'] = new ipb.Popup( 'boSelector', { type: 'pane',
																	           		   ajaxURL: _url,
																	           		   modal: true,
																	           		   stem: false,
																	           		   hideAtStart: false,
																	           		   w: '800px', h: '500' } );
		
	},

	/**
	 * Search
	 */
	search: function( e, elem )
	{
		var fields  = [ 'searchType', 'searchMatch', 'searchIsGlobal', 'searchSort', 'searchDir', 'searchText' ];
		var filters = $H();
		
		fields.each( function(f)
		{ 
			filters.set( f, $F(f) );
			
			Debug.write( f );
		} );
		
		ipb.gallery_albumChooser.setParam( 'albums', 'search' );

		elem.writeAttribute( 'data-album-pane-update', filters.toQueryString() );
		
		ipb.gallery_albumChooser.updatePane(e, elem );
	},
	
	/**
	 * Update the pane, yo
	 */
	updatePane: function( e, elem )
	{
		Event.stop(e);
		
		var _stored = ipb.gallery_albumChooser.getAllParams();
		var _params = $H();
		
		try {
			_params = $H( elem.readAttribute('data-album-pane-update').replace( /&amp;/g, '&' ).toQueryParams() );
		} catch(e){}
		
		var _moreUrl = _stored.merge( _params ).toQueryString();
		
		/* Fire it off */
		var _url  = ipb.gallery_albumChooser._ajaxUrl() + '&do=albumSelectorPane&secure_key=' + ipb.vars['secure_hash'] + '&' + _moreUrl;
		
		Debug.write( _url );
		
		/* Switch tabs? */
		ipb.gallery_albumChooser._switchTabs( ipb.gallery_albumChooser._extractAlbumParam( _moreUrl ) );

		/* Send AJAX request */
		new Ajax.Request( _url,
							   { method: 'get',
								 onSuccess: function(t)
								 {
									$('albumSelector_content').update( t.responseText );
								 }
							 } );
		
	},
	
	/**
	 * Switch tabs
	 * 
	 */
	_switchTabs: function( albums )
	{
		$('albumSelector_nav').select('li').each( function(li)
		{
			li.removeClassName('active');
			
			thisTabIs = ipb.gallery_albumChooser._extractAlbumParam( li.down('a').readAttribute('data-album-pane-update') );
			
			if ( thisTabIs == albums )
			{
				li.addClassName('active');
			}
		} );
	},
	
	/**
	 * Extract the album= param
	 */
	_extractAlbumParam: function( query )
	{
		var params = query.replace( /&amp;/g, '&').toQueryParams();
		
		if ( params.albums )
		{
			return params.albums;
		}
	},
	
	/**
	 * Return ajax URL
	 */
	_ajaxUrl: function()
	{
		if ( inACP )
		{
			ipb.vars['secure_hash'] = ipb.vars['md5_hash'];
			return ipb.vars['base_url'].replace( /&amp;/g, '&') + '&app=gallery&module=ajax&section=albums';
		}
		else
		{
			return ipb.vars['base_url'] + '&app=gallery&module=ajax&section=albumSelector';
		}
	}

};

ipb.gallery_albumChooser.init();