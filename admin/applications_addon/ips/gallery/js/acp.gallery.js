/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.gallery.js - Homepage javascript 		*/
/* (c) IPS, Inc 2010							*/
/* -------------------------------------------- */
/* Author: Matt Mecham							*/
/************************************************/

ACPGallery = {
	section: '',
	autoComplete: null,
	templates: {},
	popups: {},
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.gallery.js");

		document.observe("dom:loaded", function()
		{
			if ( ACPGallery.section == 'albums' )
			{				
				$$('._acp_gallery_addalbum').each( function( elem ) { elem.observe('click', ACPGallery.addAlbumDialogue ); } );
				
				/* Set up delete links and buttons */
				ipb.delegate.register( "._albumDeleteDialogueTrigger", ACPGallery.deleteDialogue );
				
				/* Set up progress bar stuff */
				ipb.delegate.register( 'a[progress~="thumbs"]', ACPGallery.launchThumbRebuild );
				ipb.delegate.register( 'a[progress~="resetpermissions"]', ACPGallery.launchResetPermissions );
				ipb.delegate.register( 'a[progress~="resyncalbums"]', ACPGallery.launchResyncAlbums );
				ipb.delegate.register( 'a[progress~="rebuildnodes"]', ACPGallery.launchRebuildNodes );
				ipb.delegate.register( 'a[progress~="rebuildstats"]', ACPGallery.launchRebuildStats );
				ipb.delegate.register( 'a[progress~="rebuildtreecaches"]', ACPGallery.launchRebuildTreeCaches );
				ipb.delegate.register( '.searchByMember', ACPGallery.searchMemberAlbumsByMemberName );
				ipb.delegate.register( '.searchByParent', ACPGallery.searchMemberAlbumsByParent );
				
				/* Member album search stuff */
				if ( $('searchGo') )
				{
					$('searchGo').on('click', ACPGallery.goMemberAlbumSearch );
				}
				
				/* Global album search stuff */
				if ( $('searchGo_global') )
				{
					$('searchGo_global').on('click', ACPGallery.goGlobalAlbumSearch );
				}
			}	
			
			
			/* Auto complete */
			if ( $('album_owner_autocomplete') )
			{
				// Autocomplete stuff
				var url = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=core&module=ajax&section=findnames&do=get-member-names&secure_key=' + ipb.vars['md5_hash'] + '&name=';
				
				ACPGallery.autoComplete = new ipb.Autocomplete( $('album_owner_autocomplete'), { multibox: false, url: url, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		});
	},
	
	/* Search member's albums by parent ID */
	searchMemberAlbumsByParent: function( e, elem )
	{
		Event.stop(e);
		
		$('tab_GALLERY_1').removeClassName('active');
		$('tab_GALLERY_2').addClassName('active');
		
		new Effect.Fade( $('tab_GALLERY_1_content'), { duration: 0.1, afterFinish: function() { new Effect.Appear( $('tab_GALLERY_2_content'), { duration: 0.2 } ); } } );
		
		$('searchText').value = $(elem).readAttribute('data-album-id');
		$('searchType_parent').selected = true;
		$('searchSort_date').selected = true;
		$('searchDir_desc').selected = true;
		
		ACPGallery.goMemberAlbumSearch( e );
	},

	/* Search member's albums */
	searchMemberAlbumsByMemberName: function( e, elem )
	{
		Event.stop(e);
		
		$('searchText').value = $(elem).readAttribute('data-album-owners-name');
		$('searchType_member').selected = true;
		$('searchSort_date').selected = true;
		$('searchDir_desc').selected = true;
		
		ACPGallery.goMemberAlbumSearch( e );
	},
	
	/* launches the global searchy things */
	goGlobalAlbumSearch: function( e )
	{
		var searchMatch = $F('searchMatch_global');
		var searchSort  = $F('searchSort_global');
		var searchDir   = $F('searchDir_global');
		var searchText  = $F('searchText_global');
		
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=getGlobalAlbums&secure_key=' + ipb.vars['md5_hash'];
		
		/* Send AJAX request */
		new Ajax.Request( url,
							   { method: 'get',
								 parameters: { 'searchMatch': searchMatch,
								 			   'searchSort' : searchSort,
								 			   'searchDir'  : searchDir,
								 			   'searchText' : searchText },
								 onSuccess: function(t)
								 {
									$('galleryGlobalAlbumsHere').update( t.responseText );
									
									ipb.menus.initEvents();
								 }
							 } );
	},
	
	/* launches the searchy things */
	goMemberAlbumSearch: function( e )
	{
		var searchType  = $F('searchType');
		var searchMatch = $F('searchMatch');
		var searchSort  = $F('searchSort');
		var searchDir   = $F('searchDir');
		var searchText  = $F('searchText');
		
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=getMemberAlbums&secure_key=' + ipb.vars['md5_hash'];
		
		/* Send AJAX request */
		new Ajax.Request( url,
							   { method: 'get',
								 parameters: { 'searchType': searchType,
								 			   'searchMatch': searchMatch,
								 			   'searchSort' : searchSort,
								 			   'searchDir'  : searchDir,
								 			   'searchText' : searchText },
								 onSuccess: function(t)
								 {
									$('galleryAlbumsHere').update( t.responseText );
									
									ipb.menus.initEvents();
								 }
							 } );
	},
	
	launchRebuildStats: function( e, elem )
	{
		ipb.menus.closeAll(e);
	
		albumId = elem.readAttribute( 'album-id' );
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=rebuildStats&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
		
		/* Load our wrapper and I don't mean Eminem */
		cb = new IPBProgressBar( { title: 'Rebuilding Album Statistics',
								   total: null,
								   pergo: null,
								   ajaxUrl: url } );
		cb.show();
	},
	
	launchRebuildNodes: function( e, elem )
	{
		ipb.menus.closeAll(e);
		
		albumId = elem.readAttribute( 'album-id' );
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=rebuildNodes&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
		
		/* Load our wrapper and I don't mean Eminem */
		cb = new IPBProgressBar( { title: 'Rebuilding Album Nodes',
								   total: null,
								   pergo: null,
								   ajaxUrl: url } );
		cb.show();
	},
	
	launchResyncAlbums: function( e, elem )
	{
		ipb.menus.closeAll(e);
		
		albumId = elem.readAttribute( 'album-id' );
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=resyncAlbums&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
		
		/* Load our wrapper and I don't mean Eminem */
		cb = new IPBProgressBar( { title: 'Resynchronizing Albums',
								   total: null,
								   pergo: null,
								   ajaxUrl: url } );
		cb.show();
	},
	
	launchThumbRebuild: function( e, elem )
	{
		ipb.menus.closeAll(e);
		
		albumId = elem.readAttribute( 'album-id' );
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=rebuildThumbs&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
				
		/* Load our wrapper and I don't mean Eminem */
		cb = new IPBProgressBar( { title: 'Rebuilding Images',
								   total: null,
								   pergo: null,
								   ajaxUrl: url } );
		cb.show();
	},
	
	launchResetPermissions: function( e, elem )
	{
		ipb.menus.closeAll(e);
		
		albumId = elem.readAttribute( 'album-id' );
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=resetPermissions&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
		
		/* Load our wrapper and I don't mean Eminem */
		cb = new IPBProgressBar( { title: 'Rebuilding Inherited Permissions',
								   total: null,
								   pergo: null,
								   ajaxUrl: url } );
		cb.show();
	},
	
	launchRebuildTreeCaches: function( e, elem )
	{
		ipb.menus.closeAll(e);
		
		albumId = elem.readAttribute( 'album-id' );
		url     = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=rebuildTreeCaches&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
		
		/* Load our wrapper and I don't mean Eminem */
		cb = new IPBProgressBar( { title: 'Rebuilding Tree Caches',
								   total: null,
								   pergo: null,
								   ajaxUrl: url } );
		cb.show();
	},
	
	albumSearchClose: function( e )
	{
		if ( $('album_search_box_ac').visible() )
		{
			new Effect.Fade( $('album_search_box_ac'), { duration: 0.3 } );
		}
	},
	
	albumSearchClick: function( e )
	{
		Event.stop(e);
		elem     = Event.findElement(e);
		
		if ( elem.tagName != 'LI' )
		{
			elem = elem.up('li');
		}
		
		albumId  = elem.id.replace( /album_search_box_ac_item_/, '' );
		
		/* Send AJAX request */
		new Ajax.Request( ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=getAlbumPopup&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId,
							   { method: 'get',
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
									else if ( t.responseJSON['popup'] )
									{
										/* Load inline Album viewer be-doer */
										new ipb.Popup( 'showAlbum_' + albumId, { type: 'pane',
																				 initial: t.responseJSON['popup'],
																				 stem: true,
																				 hideAtStart: false,
																				 hideClose: false,
																				 defer: false,
																				 w: 400 } );
										$('album_search_box').value = '';
										
										/* Set links to new window */
										try
										{
											$$('.galattach').each( function (elem)
											{
												elem.up(elem).writeAttribute('target', '_blank' );
											} );
										}
										catch( err ) { Debug.error( err ); }
									}
								 }
							 } );
	},
	
	/**
	 * Delete dialogue
	 */
	deleteDialogue: function(e, elem)
	{
		Event.stop(e);

		albumId = elem.readAttribute('album-id');
		
		if ( ! Object.isUndefined( ACPGallery.popups['deleteAlbum'] ) )
		{
			ACPGallery.popups['deleteAlbum'].kill();
		}
		
		var _url  = ipb.vars['base_url'] + 'app=gallery&module=ajax&section=albums&do=deleteDialogue&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + albumId;
		Debug.write( _url );
		
		/* easy one this... */
		ACPGallery.popups['deleteAlbum'] =  new ipb.Popup( 'deleteAlbum', { type: 'modal',
																            ajaxURL: _url.replace(/&amp;/g, '&'),
																            stem: false,
																            hideAtStart: false,
																            warning: true,
																            w: '400px'
																			});
		
		
	},
	
	/**
	 * Show a "what kind of album would you like dialogue
	 */
	addAlbumDialogue: function( e )
	{
		Event.stop(e);
		
		if ( ! Object.isUndefined( ACPGallery.popups['addAlbum'] ) )
		{
			ACPGallery.popups['addAlbum'].show();
			return true;
		}
		
		ACPGallery.popups['addAlbum'] = new ipb.Popup( 'addAlbumDialogue', { type: 'pane',
																			 initial: $('acp_gallery_addDialogue').innerHTML,
																			 stem: true,
																			 hideAtStart: false,
																			 hideClose: false,
																			 defer: false,
																			 w: 400 } );
	}
};

ACPGallery.init();