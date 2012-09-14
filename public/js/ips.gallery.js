/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.gallery.js - Gallery javascript			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier & Brandon Farber		*/
/************************************************/

/* Hack to get lastDescendant
	Thanks: http://proto-scripty.wikidot.com/prototype:tip-getting-last-descendant-of-an-element
*/
Element.addMethods({
    lastDescendant: function(element) {
        element = $(element).lastChild;
        while (element && element.nodeType != 1) 
            element = element.previousSibling;
        return $(element);
    }
});

/**
* Returns the value of the selected radio button in the radio group, null if
* none are selected, and false if the button group doesn't exist
* @link  http://xavisys.com/using-prototype-javascript-to-get-the-value-of-a-radio-group/
*
* @param {radio Object} or {radio id} el
* OR
* @param {form Object} or {form id} el
* @param {radio group name} radioGroup
*/
function $RF(el, radioGroup) {
    if($(el).type && $(el).type.toLowerCase() == 'radio') {
        var radioGroup = $(el).name;
        var el = $(el).form;
    } else if ($(el).tagName.toLowerCase() != 'form') {
        return false;
    }

    var checked = $(el).getInputs('radio', radioGroup).find(
        function(re) {return re.checked;}
    );
    return (checked) ? $F(checked) : null;
}


var _gallery = window.IPBoard;

_gallery.prototype.gallery = {
	
	totalChecked:	0,
	inSection: '',
	maps: [],
	latLon: null,
	popups: [],
	sAp: null,
	nAp: undefined,
	sApLn: 0,
	uhc: null,
	templates: {},
	contextMenu: false,
	isMedia: 0,
	mediaUpload: null,
	currentModBox: null,
	albumModSelectAll: false,
	albumId: 0,
	albumUrl: '',
	cropPhoto: {},
	imgSize: { width: 0, height: 0 },
	/*timer: [],
	blockSizes: [],*/
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.gallery.js");
		
		document.observe("dom:loaded", function(){
			if ( $('map') )
			{
				ipb.gallery.initMaps();
			}
			
			/* Start cropper */
			if ( $('profileTrigger') )
			{
				ipb.gallery.popups['photoTrigger'] = new ipb.Popup( 'photoTrigger',   { type: 'modal',
																			            initial: $('template_photo').innerHTML,
																			            stem: false,
																			            hideAtStart: true,
																			            warning: false,
																			            w: '700px' } );
				/* Remove template so cropper doesn't get confused */
				$('template_photo').remove();
				
				cropper = new Cropper.Img(  'photo_view_' + ipb.gallery.imageID,  { ratioDim: { x: 100, y: 100 }, 
																					minWidth: 100,
																					minHeight: 100,
																					displayOnInit: true, 
																					autoIncludeCSS: false,
																					onEndCrop: ipb.gallery.photoOnEndCrop  } );

				$('profileTrigger').observe('click', ipb.gallery.photoCropStart );
				
				/* Set up buttocks < that was deliberate */
				$('setAsPhoto_accept').observe('click', ipb.gallery.photoCropAccept );
				$('setAsPhoto_cancel').observe('click', ipb.gallery.photoCropCancel );
			}
			
			/* Delete album */
			ipb.delegate.register('._albumDelete', ipb.gallery.albumDeleteDialogue );
			
			/* Set up new/hidden labels */
			setTimeout( ipb.gallery.setUpLabels, 750 );
			
			/* Sub sub albums baby */
			$$('.sub_album_children').each( function (elem) { ipb.gallery.subAlbumDropDownSetUp(elem) } );
			
			/* Image */
			if ( ipb.gallery.inSection == 'image' )
			{
				if ( ! ipb.gallery.isMedia )
				{
					/* @todo: need to remove that resize event - replaced with max-width: 100% */
					//ipb.gallery.imageViewWindowResize();
					//Event.observe( window, 'resize', ipb.gallery.imageViewWindowResize );
					
					if( $('rotate_left') && $('rotate_right') )
					{
						$('rotate_left').observe('click', ipb.gallery.rotateImage.bindAsEventListener( this, 'left') );
						$('rotate_right').observe('click', ipb.gallery.rotateImage.bindAsEventListener( this, 'right') );
					}
					
					try
					{
						$('theImage').down('img').writeAttribute( 'alt', '' );
						$('theImage').down('img').writeAttribute( 'title', '' );
					}catch(e){}
					
					$('theImage').down('img').observe( 'contextmenu', ipb.gallery.imageContextMenu );
					$('theImage').down('img').observe( 'click', ipb.gallery.click );
				}
			}
			
			/* Setup album moderation */
			if ( ipb.gallery.inSection == 'albumOverview' || ipb.gallery.inSection == 'albumDetailView' )
			{
				ipb.delegate.register('input.albumModBox', ipb.gallery.albumModerate );
			}
			
			/* Are we home sailor? */
			if ( $('home_side_recents') || ipb.gallery.inSection == 'albumOverview' )
			{
				$$('.gallery_tiny_box').each( function(e)
				{
					if ( ! $(e).hasClassName('_image_pop') )
					{
						id = $(e).readAttribute( '-data-id' );
						
						if ( id )
						{
							var a = $(e).down('a');
							a.addClassName( '_hovertrigger' );
							a.writeAttribute("hovercard-ref", 'tinypicpop');
							a.writeAttribute("hovercard-id", id);
						}
					}
				} );
				
				/* Set up cards */
				var ajaxUrl     = ipb.vars['base_url'] + '&app=gallery&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=image&do=imageDetail';
				ipb.hoverCardRegister.initialize( 'tinypicpop', { 'w': '320px', 'delay': 750, 'position': 'auto', 'ajaxUrl': ajaxUrl, 'getId': true, 'setIdParam': 'imageid' } );
			}
		});
	},
	
	/**
	 * Calculate image height / width
	 */
	imageViewWindowResize: function()
	{
		/* Small viewport? */
		var v   = document.viewport.getDimensions();
		var min = 1051;
		
		if ( v.width <= 1050 )
		{
			var change  = ( min / v.width ) * 12;
			var primary = $$('.primary').first();
			var img     = primary.down('.galmedium');
			
			if ( ipb.gallery.imgSize.width == 0 )
			{
				ipb.gallery.imgSize.width  = img.width;
				ipb.gallery.imgSize.height = img.height;
			}
			
			var nW      = ipb.gallery.imgSize.width  - ( ( ipb.gallery.imgSize.width  / 100 ) * change );
			var nH      = ipb.gallery.imgSize.height - ( ( ipb.gallery.imgSize.height / 100 ) * change );
			
			primary.setStyle('width: ' + ( nW + 10 ) + 'px');
			
			img.width   = nW;
			img.height  = nH;
			
			Debug.write( "Resized: " + nW + ", " + nH + " - " + change );
		}
		else if ( ipb.gallery.imgSize.width && v.width > 1050 )
		{
			var primary = $$('.primary').first();
			var img     = primary.down('.galmedium');
			
			var nW      = ipb.gallery.imgSize.width;
			var nH      = ipb.gallery.imgSize.height;
			
			primary.setStyle('width: ' + ( nW + 10 ) + 'px');
			
			img.width   = nW;
			img.height  = nH;
			
			Debug.write( "Resized: " + nW + ", " + nH );
		}
	},
	
	/**
	 * Set up new/hidden labels
	 */
	setUpLabels: function()
	{
		/* New label */
		$$('.hello_i_am_new').each( function (elem) { ipb.gallery.addNewSticker(elem); } );
		
		/* Unapproved label only for overview */
		if ( ipb.gallery.inSection == 'albumOverview' )
		{
			$$('.hello_i_am_hidden').each( function (elem) { ipb.gallery.addHiddenSticker(elem); } );
		}
	},
	
	/**
	 * Launch move dialogue
	 */
	imageMoveDialogue: function(elem, e)
	{
		Event.stop(e);
		
		ipb.menus.closeAll(e);
		
		if ( ! Object.isUndefined( ipb.gallery.popups['move'] ) )
		{
			ipb.gallery.popups['move'].show();
		}
		else
		{
			var _url  = ipb.vars['base_url'] + 'app=gallery&module=ajax&section=image&do=moveDialogue&secure_key=' + ipb.vars['secure_hash'] + '&imageid=' + ipb.gallery.imageID;
			Debug.write( _url );
			
			/* easy one this... */
			ipb.gallery.popups['move'] = new ipb.Popup( 'menuMove', { type: 'pane',
															          ajaxURL: _url,
															          stem: true,
															          hideAtStart: false,
															          w: '400px' });
		}
	},
	
	/**
	 * Set is as a cover image
	 * @param e
	 */
	imageSetAsCover: function(elem, e)
	{
		Event.stop(e);
		
		var _url  = ipb.vars['base_url'] + '&app=gallery&module=ajax&section=image&do=setAsCover&secure_key=' + ipb.vars['secure_hash'] + '&imageId=' + ipb.gallery.imageID;
		Debug.write( _url );
		
		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									/* No Permission */
									if ( t.responseJSON && t.responseJSON['status'] == 'ok' )
									{
										$('menu_set_cover').fade( { duration: 1.0 } );
										setTimeout( ipb.menus.closeAll, 1500 );
									}
								}
							}
						);
		
	},
	
	/* IMAGE VIEW */
	photoCropStart: function(e)
	{
		Event.stop(e);
		ipb.gallery.popups['photoTrigger'].show();
	},
	
	photoCropAccept: function(e)
	{
		/* fetch data */
		var _url  = ipb.vars['base_url'] + '&app=gallery&module=ajax&section=image&do=setAsPhoto&secure_key=' + ipb.vars['secure_hash'] + '&imageId=' + ipb.gallery.imageID;
		Debug.write( _url );
		
		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: { x1: ipb.gallery.cropPhoto['coords'].x1,
											  x2: ipb.gallery.cropPhoto['coords'].x2,
											  y1: ipb.gallery.cropPhoto['coords'].y1,
											  y2: ipb.gallery.cropPhoto['coords'].y2 },
								onSuccess: function(t)
								{
									/* No Permission */
									if ( t.responseJSON && t.responseJSON['status'] == 'ok' )
									{
										/* Close down the pop-up */
										ipb.gallery.photoCropCancel();
										
										/* update images on the page */
										$$('.photo').each( function( elem )
										{
											try
											{
												src  = elem.readAttribute('src');
												rand = Math.round( Math.random() * 100000000 );
												
												if ( src == t.responseJSON['oldPhoto'] )
												{
													elem.src = t.responseJSON['thumb'] + '?t=' + rand;
												}
											} catch (err){}
										} );
									}
								}
							}
						);
	},
	
	photoCropCancel: function(e)
	{
		ipb.gallery.popups['photoTrigger'].hide();
	},
	
	photoOnEndCrop: function( coords, dimensions )
	{
		ipb.gallery.cropPhoto['coords'] = coords;
		ipb.gallery.cropPhoto['dims']   = dimensions;
		
		Debug.dir( coords );
		Debug.dir( dimensions );
	},
	
	/**
	 * Hover card callback
	 */
	albumModerate: function(e, elem)
	{
		// Count checked boxes that are visible
		var count = $$(".albumModBox:checked").findAll( function(el){ return true; } );
		
		if( count.size() ){
			if( !$('album_moderate_box') ){
				$$('body')[0].insert({'bottom': ipb.templates['album_moderation'].evaluate({count: count.size()}) });
				$('albumModAction').on('change', ipb.gallery.albumModerateChangeOption);
				$('submitModAction').on('click', ipb.gallery.albumModerateSubmit);
				$('albumModSelectAll').on('click', ipb.gallery.albumModerateSelectAllImages);
			} else {
				$('images_modcount').update( count.size() );
			}
			
			if( !$('album_moderate_box').visible() ){
				new Effect.Appear( $('album_moderate_box'), { duration: 0.3 } );
			}
		}
		else
		{
			if( $('album_moderate_box') ){
				new Effect.Fade( $('album_moderate_box'), { duration: 0.3 } );
			}
		}
	},
	
	/**
	 * Mouse over
	 */
	subAlbumDropDownSetUp: function(elem)
	{
		id = elem.id.replace( /subAlbumChildren_/, '' );
		
		dims = elem.getDimensions();
		
		Debug.write( dims.width );
		
		wdt = ( ( dims.width > 180 ) ? dims.width : 190 );
		
		div = new Element( 'div', { id: elem.id + '_menucontent' } ).addClassName('albumdd ipbmenu_content').setStyle('width:' + wdt + 'px').writeAttribute('data-loaded', 'false');
		
		$$('body')[0].insert( div );
		
		new ipb.Menu( elem, $( elem.id + "_menucontent" ), { }, { afterOpen: ipb.gallery.subAlbumDropDownCallBack } );
		
	},
	
	/*
	 * Call back for when menu is clicked
	 */
	subAlbumDropDownCallBack: function( obj )
	{
		core    = obj.id.replace( /_menu/, '' );
		ddelem  = $(core + '_menucontent');
		albumId = core.replace( /subAlbumChildren_/, '' );
			
		if ( ddelem.readAttribute( 'data-loaded') == 'false' )
		{
			/* Show spinny */
			ddelem.update("<div style='height: 20px; text-align:center'><img src='" + ipb.vars['loading_img'] + "' alt='' /></div>");
			
			/* fetch data */
			var _url  = ipb.vars['base_url'] + '&app=gallery&module=ajax&section=album&do=getSubAlbumDropDown&secure_key=' + ipb.vars['secure_hash'] + '&albumId=' + albumId;
			Debug.write( _url );
			
			new Ajax.Request( _url,
								{
									method: 'post',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										/* No Permission */
										if ( t.responseText )
										{
											ddelem.update( t.responseText );
											ddelem.writeAttribute('data-loaded', 'true');
											
											ddelem.select('li').each( function (elem)
											{
												Debug.dir( elem );
												a = elem.down('a');
												
												elem.writeAttribute('clink', a.href );
												
												elem.observe( 'click', function(e) { el = Event.findElement(e);  el = el.up('li'); window.location = el.readAttribute('clink'); } );
											} );
										}
									}
								}
							);
		}
	},
	
	/**
	 * Someone changed the drop down box
	 */
	albumModerateChangeOption: function(e)
	{
		elem   = Event.findElement(e);
		option = elem.options[ elem.selectedIndex ].value;
		
		if ( option == 'move' )
		{
			/* Already open? */
			if ( $('albumModBox_moveTo') )
			{
				Effect.BlindDown( $('albumModBox_move'), { 'duration': 0.4 } );
				return;
			}
			
			$('albumModBox_move').update( ipb.templates['album_img_moveto'].evaluate( { 'album_id': ipb.gallery.albumId } ) );
			
			Effect.BlindDown( $('albumModBox_move'), { 'duration': 0.4 } );
		}
		else
		{
			/* Already open? */
			if ( $('albumModBox_move').visible() )
			{
				Effect.BlindUp( $('albumModBox_move'), { 'duration': 0.4 } );
			}
		}
		
	},
	
	/**
	 * Someone pushed the button!
	 */
	albumModerateSubmit: function(e)
	{
		Event.stop(e);
		toAlbumId = 0;
		Debug.write('here');
		if ( $('albumModBox_move').visible() )
		{
			toAlbumId = $F('albumModBox_moveTo');
		}
		Debug.write(toAlbumId);
		/* Fire ajax */
		var act = $( 'albumModAction').value;
		
		var url = ipb.vars['base_url'] + 'app=gallery&module=ajax&section=album&do=moderate&albumId=' + ipb.gallery.albumId + '&modact=' + act + '&selectAll=' + ipb.gallery.albumModSelectAll + '&toAlbumId=' + toAlbumId + '&secure_key=' + ipb.vars['secure_hash'];
		var ids = new Array();

		// Get checked image IDs
		var ids = $$('.albumModBox:checked').collect( function(item){
			return item.id.replace( /modBox_/, '' );
		});
		
		Debug.write( url );
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								encoding: ipb.vars['charset'],
								parameters: {
									md5check: 		ipb.vars['secure_hash'],
									"imageIds[]":	ids
									},
								onSuccess: function(t)
								{
									if ( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
										
										return false;
									}
									else
									{
										/* Untick checkboxes */
										$$('.albumModBox:checked').each( function(i){
											i.checked = false;
										});
										
										if( $('album_moderate_box') ){
											new Effect.Fade( $('album_moderate_box'), { duration: 0.3 } );
										}
										
										if ( act == 'delete' || act == 'move' )
										{
											/* Just go away if still here */
											try
											{
												for( var i = 0 ; i <= ids.length ; i++ )
												{
													var _elem = $('tn_image_view_' + ids[i] ).up('li');
													_elem.identify();
													
													Effect.Fade( $(_elem), { 'duration': 0.8 } );
												}
											} catch(err){}
											
											/* reload page anyway */
											setTimeout( function() { location.href = ipb.gallery.albumUrl; }, 500 );
										}
										else if ( act == 'unapprove' || act == 'approve' )
										{
											for( var i = 0 ; i <= ids.length ; i++ )
											{
												if ( ipb.gallery.inSection == 'albumDetailView' )
												{
													var _elem = $('image_view_' + ids[i] ).up('td');
													
													if ( act == 'approve' )
													{
														_elem.removeClassName('moderated');
													}
													else
													{
														_elem.addClassName('moderated');
													}
												}
												else
												{
													var _elem = $('tn_image_view_' + ids[i] );
													
													if ( act == 'approve' )
													{
														_elem.removeClassName('hello_i_am_hidden');
														$('image_is_hidden_box_' + ids[i]).remove();
													}
													else
													{
														_elem.addClassName('hello_i_am_hidden');
														ipb.gallery.addHiddenSticker(_elem);
													}
												}
											}
										}
									}
								}
							}
						);
	},
	
	/**
	 * Select all album images
	 * @param e
	 */
	albumModerateSelectAllImages: function(e)
	{
		elem = Event.findElement(e);
		
		ipb.gallery.albumModSelectAll = elem.checked;
		
		$$('.albumModBox').each( function(c){
			c.checked = elem.checked;
		});
		
		if( $('album_moderate_box').visible() && !elem.checked ){
			new Effect.Fade( $('album_moderate_box'), { duration: 0.3 } );
		}
	},
	
	/**
	 * Launch album delete dialogue
	 */
	albumDeleteDialogue: function(e, elem)
	{
		Event.stop(e);
		
		albumId = elem.readAttribute('album-id');
		
		ipb.menus.closeAll(e);
		
		if ( ! Object.isUndefined( ipb.gallery.popups['deleteAlbum'] ) )
		{
			ipb.gallery.popups['deleteAlbum'].kill();
		}
		
		var _url  = ipb.vars['base_url'] + 'app=gallery&module=ajax&section=album&do=deleteDialogue&secure_key=' + ipb.vars['secure_hash'] + '&albumId=' + albumId;
		Debug.write( _url );
		
		/* easy one this... */
		ipb.gallery.popups['deleteAlbum'] = new ipb.Popup( 'deleteAlbum', { type: 'modal',
																            ajaxURL: _url,
																            stem: false,
																			w: '400px',
																            hideAtStart: false,
																            warning: true } );
	},
	
	/**
	 * Adds the 'hidden' sticker
	 */
	addHiddenSticker: function( elem, e )
	{
		try
		{
			if ( elem.getStyle('textAlign') == 'center' )
			{
				return;
			}
			
			if ( elem.className.match( /cover_img_/ ) )
			{
				return;
			}
			
			/* is the image padded? */
			dims     = elem.getDimensions();
			width    = dims.width;
			height   = dims.height;
			
			if ( width <= 40 || height <= 40 )
			{
				return;
			}
			
			_div = new Element('div', { id: 'image_is_hidden_box_' + elem.id.replace( /tn_image_view_/, '' ), className: 'image_is_hidden_box' } ).update( ipb.lang['unapproved'] );
			elem.insert( { before: _div } );
			
			if ( elem.hasClassName('galmedium') )
			{
				_div.setStyle( 'margin-left:' + ( 10 ) + 'px !important' );
				width  += 6;
				height += 5;
			}
			
			if ( height )
			{
				_div.setStyle( 'height:16px !important' );
				_div.setStyle( 'margin-top:' + ( height - 19 ) + 'px !important' );
			}
			
			if ( width )
			{
				_div.setStyle( 'width:' + ( width - 10) + 'px !important' );
				
			}
			
			/*elem.up('a').setStyle( { 'textDecoration': 'none' } );*/
		}
		catch(e){}
	},
	
	/**
	 * Adds the 'new' sticker
	 */
	addNewSticker: function( elem, e )
	{
		try
		{
			if ( elem.getStyle('textAlign') == 'center' )
			{
				return;
			}
			
			if ( elem.className.match( /cover_img_/ ) )
			{
				return;
			}
			
			try
			{
				if ( elem.width <= 30 )
				{
					return;
				}
				
			} catch (err) { }
			
			_div = new Element('div', { className: 'image_is_new_box' } ).update( ipb.lang['new_lowercase'] );
			elem.insert( { before: _div } );
			
			/* is the image padded? */
			padLeft  = parseInt( elem.getStyle('paddingLeft') );
			padTop   = parseInt( elem.getStyle('paddingTop') );
			marLeft  = parseInt( elem.getStyle('marginLeft') );
			marTop   = parseInt( elem.getStyle('marginTop') );
			bckTop   = parseInt( elem.getStyle('backgroundPositionX') );
			bckLeft  = parseInt( elem.getStyle('backgroundPositionY') );
			
			if ( padLeft || marLeft || bckLeft )
			{
				_div.setStyle( 'margin-left:' + ( (padLeft + marLeft + bckLeft) - 3 ) + 'px !important' );
			}
			
			if ( padTop || marTop || bckTop )
			{
				_div.setStyle( 'margin-top:' + ( (padTop + marTop + bckTop) - 3 ) + 'px !important' );
			}
			
			elem.up('a').setStyle( { 'textDecoration': 'none' } );
		}
		catch(e){}
	},
	
	/**
	 * Init flash player
	 * 
	 */
	flashPlayerInit: function( file, flowplayerUrl )
	{
		$f("player", flowplayerUrl, { clip: { autoPlay: false, url: file, scaling: 'fit' } } );
	},
	
	/**
	 * Launch delete dialogue
	 */
	imageDeleteDialogue: function(elem, e)
	{
		Event.stop(e);
		
		ipb.menus.closeAll(e);
		
		if ( ! Object.isUndefined( ipb.gallery.popups['delete'] ) )
		{
			ipb.gallery.popups['delete'].show();
		}
		else
		{
			/* easy one this... */
			ipb.gallery.popups['delete'] = new ipb.Popup( 'menuEdit', { type: 'modal',
															            initial: $('template_delete').innerHTML,
															            stem: false,
															            warning: true,
															            hideAtStart: false,
															            w: '300px' } );
		}
	},
	
	/**
	 * Launch the lightbox
	 */
	click: function(e)
	{
		if ( $('ips_lightbox') )
		{
			if ( $('ips_lightbox').readAttribute('available') == 'true' )
			{
				/* Load JS which kick starts the revolution! */
				ipb.gallery_lightbox.launch();
			}
		}
	},
	
	/**
	 * Image Context Menu click
	 */
	imageContextMenu: function(e)
	{		
		if ( ! Event.isLeftClick(e) )
		{
			Event.stop(e);
			
			if ( ipb.gallery.contextMenu !== false )
			{
				ipb.gallery.contextMenu.kill();
			}
			
			ipb.gallery.contextMenu = new ipb.Popup( 'imcontextmenu', {  type: 'balloon',
																	     initial: $('template_sizes').innerHTML,
																	     stem: false,
																	     hideClose: true,
																	     hideAtStart: false,
																	     attach: { target: $('theImage'), position: 'auto' },
																	     w: '350px' });
			
			/* reposition */
			x = Event.pointerX(e);
			y = Event.pointerY(e);
			
			if ( x && y )
			{
				$('imcontextmenu_popup').setStyle( { 'position': 'absolute', 'left': x + 'px', 'top': y + 'px'} );
			}
			
		}
	},
	
	/**
	 * Init map
	 */
	initMaps: function()
	{
		if ( $('map_0') && $('map_1') && ipb.gallery.latLon )
		{
			$('map').appear();
			
			ipb.gallery.maps[0] = $('map_0').src;
			ipb.gallery.maps[1] = $('map_1').src;
			
			$('map_0').observe( 'mouseover', function(e) { $('map_0').src = ipb.gallery.maps[1]; } );
			$('map_0').observe( 'mouseout' , function(e) { $('map_0').src = ipb.gallery.maps[0]; } );
		}
	},
	
	/**
	 * Remove map from image
	 *
	 */
	removeMap: function(elem, e)
	{
		Event.stop(e);
		
		var _url  = ipb.vars['base_url']+'app=gallery&module=ajax&section=image&do=removeMap&secure_key=' + ipb.vars['secure_hash'] + '&imageid=' + ipb.gallery.imageID;
		Debug.write( _url );
		
		new Ajax.Request( 
				_url,
				{
					method: 'get',
					evalJSON: 'force',
					onSuccess: function(t)
					{
						/* No Permission */
						if( Object.isUndefined( t.responseJSON ) )
						{
							alert( ipb.lang['action_failed'] );
							return;
						}
						else if ( t.responseJSON['error'] )
						{
							alert( ipb.lang['no_permission'] );
						}
						else
						{
							if ( t.responseJSON['done'] )
							{
								/* Remove stuff */
								$$('.__mapon').each( function(elem)
								{
									elem.fade();
									
								} );
							
							}
						}
					}
				}
			);
	},
	
	/**
	 * Add map to image
	 *
	 */
	addMap: function(elem, e)
	{
		Event.stop(e);
		
		var _url  = ipb.vars['base_url']+'app=gallery&module=ajax&section=image&do=addMap&secure_key=' + ipb.vars['secure_hash'] + '&imageid=' + ipb.gallery.imageID;
		Debug.write( _url );
		
		new Ajax.Request( 
				_url,
				{
					method: 'get',
					evalJSON: 'force',
					onSuccess: function(t)
					{
						/* No Permission */
						if( Object.isUndefined( t.responseJSON ) )
						{
							alert( ipb.lang['action_failed'] );
							return;
						}
						else if ( t.responseJSON['error'] )
						{
							alert( ipb.lang['no_permission'] );
						}
						else
						{
							if ( t.responseJSON['latLon'] )
							{
								/* Set latlon */
								$$('.__mapoff').invoke('hide');
								ipb.gallery.latLon = t.responseJSON['latLon'];
								ipb.gallery.initMaps();
							}
						}
					}
				}
			);
	},
	
	/**
	 * Sets up the semi-transparent description thingy
	 * @param id
	 * @returns
	 */
	registerDescription: function( id )
	{
		if( !$('image_wdesc_' + id) ){ return; }
		
		var img      = $('image_wdesc_' + id).down('img');
		var ahref    = $('image_wdesc_' + id).down('a');
		var desc     = $('image_wdesc_' + id + '_description' );
		var ofs      = img.cumulativeOffset();
		var dims     = img.getDimensions();
		
		/* Create new div with image as background */
		var div = new Element( 'div', { 'id': '_newDiv_' + id, 'style': 'margin: 0 auto; height: ' + dims.height + 'px; width:' + dims.width + 'px; max-width: 100% !important; position: relative; background-image: url("' + img.src + '");' } );
		ahref.setStyle( { 'text-decoration': 'none' } );
		ahref.writeAttribute( 'title', '' );
		ahref.insert( div );
		
		$('_newDiv_' + id).insert( desc );
		
		var descDims = desc.getDimensions();
		
		/* Take off the padding */
		desc.setStyle( { 'width': descDims.width - 21 + 'px' } );
		
		/* Hide original image */
		img.hide();
		
		desc.hide();
		new Effect.Appear( desc, { duration: 1.5 } );
	},
	
	/**
	 * Generic new album select dialogue
	 *
	 */
	newAlbumDialogue: function(e, elem)
	{
		Event.stop(e);
		parentId = 0;
		
		if ( ! Object.isUndefined( ipb.gallery.nAp ) )
		{
			ipb.gallery.nAp.kill();
		}
		
		try
		{
			parentId = elem.readAttribute( 'data-parentid' );
		}
		catch (er) { }
		
		/* Deletegate */
		ipb.delegate.register('._aSubmit', ipb.gallery.newAlbumSubmit );
		
		var _url  = ipb.vars['base_url']+'app=gallery&module=ajax&section=album&do=newAlbumDialogue&secure_key=' + ipb.vars['secure_hash'] + '&parentId=' + parentId;
		Debug.write( _url );
		
		new Ajax.Request( 
				_url,
				{
					method: 'get',
					onSuccess: function(t)
					{
						/* No Permission */
						if( t.responseText == 'nopermission' )
						{
							alert( ipb.lang['no_permission'] );
						}
						else
						{
							/* Show our popup :D */
							ipb.gallery.nAp = new ipb.Popup( 'newAlbumDialogue', {  type: 'pane',
																	   initial: t.responseText,
																	   stem: true,
																	   hideAtStart: false });
							
							/* Setup onchange event */
							$('album_parent_id').observe('change', ipb.gallery.checkWatermarkOption );
						}
					}
				}
			);
	},
	
	/**
	 * Checks if the parent album allows to apply watermark
	 */
	checkWatermarkOption: function(e)
	{
		Event.stop(e);
		
		elem     = Event.findElement(e);
		parentId = parseInt( elem.value );
		
		if ( !parentId )
		{
			return;
		}
		
		var _url  = ipb.vars['base_url']+'app=gallery&module=ajax&section=album&do=checkWatermarkOption&secure_key=' + ipb.vars['secure_hash'] + '&parentId=' + parentId;
		Debug.write( _url );
		
		new Ajax.Request( 
				_url,
				{
					method: 'get',
					onSuccess: function(t)
					{
						/* No Permission */
						if( Object.isUndefined( t.responseJSON ) )
						{
							alert( ipb.lang['action_failed'] );
							return;
						}
						else if ( t.responseJSON['error'] )
						{
							alert( ipb.lang['no_permission'] );
						}
						else
						{
							/* Show or hide option? */
							if ( t.responseJSON['watermark'] == 'show' )
							{
								$('parentAlbumWatermark').show();
							}
							else
							{
								$('parentAlbumWatermark').hide();
							}
						}
					}
				}
			);
	},
	
	/**
	 * Generic new album select dialogue
	 */
	newAlbumSubmit: function(e, elem)
	{
		Event.stop(e);
		var post = {};
		
		/* Populate */
		post['album_name']        = $F('album_name');
		post['album_description'] = $F('album_description');
		post['album_parent_id']	  = $F('album_parent_id');
		post['album_sort_options__key']	= $F('album_sort_options__key');
		post['album_sort_options__dir']	= $F('album_sort_options__dir');
		post['album_detail_default']    = $F('album_detail_default');
		post['album_watermark']   = $F('album_watermark');
		post['album_is_public']   = $RF('album_is_public');
		
		/* Hide save button temporarily */
		if ( $('fieldset_aSubmit') )
		{
			$('fieldset_aSubmit').hide();
		}
		
		var _url  = ipb.vars['base_url']+'app=gallery&module=ajax&section=album&do=newAlbumSubmit&secure_key=' + ipb.vars['secure_hash'];
		Debug.write( _url );
		
		new Ajax.Request( 
				_url,
				{
					method: 'post',
					parameters: post,
					evalJSON: 'force',
					onSuccess: function(t)
					{
						/* No Permission */
						if( Object.isUndefined( t.responseJSON ) )
						{
							/* show again save button */
							if ( $('fieldset_aSubmit') )
							{
								$('fieldset_aSubmit').show();
							}
							
							alert( ipb.lang['action_failed'] );
						}
						else if ( t.responseJSON['error'] )
						{
							/* show again save button */
							if ( $('fieldset_aSubmit') )
							{
								$('fieldset_aSubmit').show();
							}
							
							alert( t.responseJSON['error'] );
						}
						else
						{
							/* Close the pop-up */
							ipb.gallery.nAp.hide();
							
							if ( t.responseJSON['album'] )
							{
								/* Set id */
								ipb.uploader.setCurrentAlbumId( t.responseJSON['album']['album_id'] );
								ipb.uploader.buildAlbumBox( t.responseJSON['album']['album_id'], albumTemplate, 'albumWrap' );
							}
						}
					}
				}
			);
	},
	
	/**
	 * Call back for album selector for upload forms
	 */
	callBackForUploadFormForAlbumSelector: function( album )
	{
		if ( album.album_id )
		{
			/* Set id */
			ipb.uploader.setCurrentAlbumId( album.album_id );
			ipb.uploader.buildAlbumBox( album.album_id, albumTemplate, 'albumWrap' );
		}
	},
	
	/**
	 * Set up review page
	 */
	setUpReviewPage: function()
	{
		ipb.gallery.inUse = new Array();
		
		/* Set up rotate links */
		ipb.delegate.register('.rotate', ipb.gallery._rotate );
		
		/* Media add thumb */
		ipb.delegate.register( '.media_thumb_pop', ipb.gallery.mediaThumbPop );
		
		$$('.galattach').each( function( id ) {
			id.addClassName('gallery_photo_wrap');
		} );
		
		/* Set up text editors and other stuff */
		$$('._imgIds').each( function( id ) {
			_id = id.className.match( /_x(.+?)(\s|$)/ );
			
			if ( _id[1] )
			{
				Debug.write( "Set up editor for: " + _id[1] );
				
				try
				{
					if ( $('image_thumb_wrap_' + _id[1] ).down('.media_thumb_pop') )
					{
						if ( $('image_thumb_wrap_' + _id[1] ).down('.media_thumb_pop').readAttribute('media-has-thumb') == 'true' )
						{
							$('image_thumb_wrap_' + _id[1] ).down('.media_thumb_pop').value = "Remove Img";
						}
					}
				}
				catch(e){}
			}
		} );
	},
	
	mediaThumbPop: function(e)
	{
		var elem = Event.element(e);
		elem.identify();
		
		var mediaId  = elem.readAttribute('media-id');
		var hasThumb = elem.readAttribute('media-has-thumb');
		
		/* do we have a thumb? then if we clicked we want to remove */
		if ( hasThumb == 'true' )
		{ Debug.write( ipb.vars['base_url']+'app=gallery&module=post&section=image&do=removeUpload&type=mediaThumb&secure_key=' + ipb.vars['secure_hash'] + '&id=' + mediaId );
		
			new Ajax.Request( 
					ipb.vars['base_url']+'app=gallery&module=post&section=image&do=removeUpload&type=mediaThumb&secure_key=' + ipb.vars['secure_hash'] + '&id=' + mediaId,
					{
						method: 'post',
						onSuccess: function(t)
						{
							/* No Permission */
							if( Object.isUndefined( t.responseJSON ) )
							{
								alert( ipb.lang['action_failed'] );
								return;
							}
							else if ( t.responseJSON['error'] )
							{
								alert( t.responseJSON['error'] );
							}
							else
							{
								ipb.gallery._changeMediaThumb( t.responseJSON );
								$('image_thumb_wrap_' + t.responseJSON['id'] ).down('.media_thumb_pop').writeAttribute('media-has-thumb', 'false');
								$('image_thumb_wrap_' + t.responseJSON['id'] ).down('.media_thumb_pop').value = "Add Thumb";
							}
						}
					}
				);	
		}
		else
		{
			if ( ! Object.isUndefined( ipb.gallery.popups['mediaPop-' + mediaId ] ) )
			{
				ipb.gallery.popups['mediaPop-' + mediaId ].kill();
			}
			
			ipb.gallery.popups['mediaPop-' + mediaId ] = new ipb.Popup( 'mediathumb', { type: 'pane',
																						modal: false,
																						w: '420px',
																						h: '300px',
																						initial: $('templates-mediaupload').innerHTML.replace( /\#\{id\}/g, mediaId ),
																						hideAtStart: false,
																						close: 'a[rel="close"]' } );
			
			ipb.gallery.mediaUpload = new ipb.mediaThumbUploader( mediaId, 'mediaUploader' );
		}
	},
	
	mediaThumbClose: function( json )
	{
		if ( ! Object.isUndefined( ipb.gallery.popups['mediaPop-' + json['id'] ] ) )
		{
			ipb.gallery.popups['mediaPop-' + json['id'] ].hide();
		}
		
		if ( json && json['ok'] == 'done' && json['tag'] )
		{
			ipb.gallery._changeMediaThumb( json );
			$('image_thumb_wrap_' + json['id'] ).down('.media_thumb_pop').writeAttribute('media-has-thumb', 'true');
			$('image_thumb_wrap_' + json['id'] ).down('.media_thumb_pop').value = "Remove Img";
		}
	},
	
	_changeMediaThumb: function( json )
	{
		/* this is fugly as fug */
		if ( $('_tmp_xx_x') )
		{
			$('_tmp_xx_x').remove();
		}
		
		/* Cover your eyes now */
		div = new Element( 'div', { id: '_tmp_xx_x', style: 'display:none' } );
		$('postingform').insert( div );
		
		$('_tmp_xx_x').update( json['tag'] ).hide();
		
		$('image_thumb_wrap_' + json['id'] ).down('img').writeAttribute( 'src', $('_tmp_xx_x').down('img').readAttribute('src') );
		/* You can look again now */
	},
	
	/**
	 * Pre rotate from delegate
	 */
	_rotate: function(e)
	{
		var elem = Event.element(e);
		var cn   = elem.className;
		
		if ( ! cn.match( /rotate/ ) )
		{
			cn = elem.up('.rotate').className;
		}
		var _id = cn.match( /_r(.+?)(\s|$)/ );
		
		if ( _id[1] )
		{
			ipb.gallery.imageID = _id[1];
			ipb.gallery.rotateImage(e, 'right' );
		}
	},
	
	rotateImage: function( e, dir )
	{
		//Debug.write( curnotes.size() );
		
		// If we have notes, just refresh
		//if( !Object.isUndefined( curnotes ) && curnotes.size() )
		//{
		//	return;
		//}
		
		Event.stop(e);
		if( ( dir != 'left' && dir != 'right' ) ){ return; }
		
		
		new Ajax.Request( 
							ipb.vars['base_url']+'app=gallery&module=ajax&section=image&do=rotate-' + dir + '&secure_key=' + ipb.vars['secure_hash'] + '&img=' + ipb.gallery.imageID,
							{
								method: 'post',
								onSuccess: function(t)
								{
									/* No Permission */
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
									}
									else if( t.responseText == 'rotate_failed' )
									{
										alert( ipb.lang['gallery_rotate_failed'] );
									}
									else
									{
										var rand = Math.round( Math.random() * 100000000 );
										var img = $('image_view_' + ipb.gallery.imageID) ? $('image_view_' + ipb.gallery.imageID) : $('tn_image_view_' + ipb.gallery.imageID);
										var tmpSrc = img.src;
										var w      = parseInt( $( img ).width );
										var h      = parseInt( $( img ).height );
										
										tmpSrc = tmpSrc.replace(/t=[0-9]+/, '');
										
										$( img ).width  = h;
										$( img ).height = w;
										$( img ).src = tmpSrc + "?t=" + rand;
										
										try
										{
											var div = $(img).up('.image_view_wrap');
											div.setStyle( 'width: ' + h + 'px; height: ' + w + 'px' );
										} catch( e ) { }
									}
								}
							}
						);
		return false;
		
	},
	
	/**
	 * Show the meta information popup
	 */
	showMeta: function(elem, e)
	{
		Event.stop(e);
		
		ipb.menus.closeAll(e);
		
		if( ipb.gallery.popups['meta'] )
		{
			ipb.gallery.popups['meta'].show();
		}
		else
		{
			ipb.gallery.popups['meta'] = new ipb.Popup( 'showmeta', { type: 'pane', modal: false, w: '600px', h: '500', initial: $('metacontent').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		}
		
		return false;
	},
	
	/**
	 * Show the share links popup
	 */
	showShareLinks: function(elem, e)
	{
		Event.stop(e);
		
		ipb.menus.closeAll(e);
		
		if( ipb.gallery.popups['sharelinks'] )
		{
			ipb.gallery.popups['sharelinks'].show();
		}
		else
		{
			ipb.gallery.popups['sharelinks'] = new ipb.Popup( 'showsharelinks', { type: 'pane', modal: false, w: '580px', h: '300px', initial: $('share_links_content').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		}
		
		return false;
	}
};

ipb.gallery.init();

var _mtuploader = window.IPBoard;

_mtuploader.prototype.mediaThumbUploader = Class.create({
	options: [],
	boxes: [],
	json: {},
	
	initialize: function( id )
	{
		this.id = id;
		this.wrapper = 'mt_attachments_' + this.id;
		
		/* Build iframe */
		this.iframe = new Element('iframe', { 	id: 'media_thumb_iframe_' + this.id,
		 										name: 'media_thumb_iframe_' + this.id,
												scrolling: 'no',
												frameBorder: 'no',
												border: '0',
												className: '',
												allowTransparency: true,
												src: ipb.vars['base_url'] + 'app=gallery&module=post&section=image&do=upload&type=mediathumb&id=' + this.id,
												tabindex: '1'
											}).setStyle({
												width: '400px',
												height: '50px',
												overflow: 'hidden',
												backgroundColor: 'transparent'
											});
											
		$( this.wrapper ).insert( this.iframe ).addClassName('traditional');
		
		$('mt_add_files_' + this.id ).observe('click', this.processUpload.bindAsEventListener( this ) );
	},

	
	/**
	* Processes upload
	*/
	processUpload: function( e )
	{
		var iFrameBox  = window.frames[ 'media_thumb_iframe_' + this.id ].document.getElementById('mtiframeUploadBox');
		var iFrameForm = window.frames[ 'media_thumb_iframe_' + this.id ].document.getElementById('mtiframeUploadForm');
		
		iFrameForm.action = ipb.vars['base_url'] + 'app=gallery&module=post&section=image&do=uploadSave&type=mediathumb&id=' + this.id;
		
		$(iFrameForm).submit();
	},
	
	
	_setJSON: function( id, json )
	{
		Debug.dir( json );
		Debug.write( "ipb.uploader.js: Got JSON from the iFrame" );
		
		if ( json['error'] )
		{
			$('mtErrorBox_' + id).update( ipb.lang[ json['error'] ] );
			new Effect.Appear( $('mtErrorBox_' + id) );
		}
		else if ( json['ok'] && json['ok'] == 'done' )
		{
			ipb.gallery.mediaThumbClose( json );
		}
	}
});