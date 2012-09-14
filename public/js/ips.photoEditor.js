/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.photoEditor.js				*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Matt Mecham						*/
/************************************************/

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

var _photoEditor = window.IPBoard;

_photoEditor.prototype.photoEditor = {
	cropPhoto: { 'cropper': false },
	scaled: {},
	
	init: function()
	{
		Debug.write("Initializing ips.photoEditor.js");
		
		/* inline loader loaded? */
		if ( Object.isUndefined( ipb.inlineUploader ) )
		{
			Debug.write('not loaded');
			setTimeout( ipb.photoEditor.init, 1000 );
			return;
		}
		
		/* Watch cropper init */
		ipb.delegate.register('.cropperStart', ipb.photoEditor.photoCropStart );
		
		/* Activate ok/cancel linkies */
		ipb.delegate.register('.cropperAccept', ipb.photoEditor.photoCropAccept );
		ipb.delegate.register('.cropperCancel', ipb.photoEditor.photoCropCancel );
		ipb.delegate.register('.ips_photoSubmit', ipb.photoEditor.photoDone );
		ipb.delegate.register('.ips_photoRemove', ipb.photoEditor.photoRemove );
				
		/* Watch form */
		if ( $('upload_photo') )
		{
			ipb.inlineUploader.watch( 'photoEditorForm', 'upload_photo', ipb.photoEditor.photoUploaded );
		}
		
		/* Watch for radio buttons */
		new Array( 'upload_photo', 'gravatar', 'facebook', 'twitter' ).each( function(item)
		{
			if ( $(item ) )
			{
				$(item ).observe( 'click', ipb.photoEditor.rb );
			}
		} );
		
		if ( $('url_photo') )
		{
			$('url_photo').defaultize( ipb.lang['photo_editor_enterurl'] );
			$('url_import').on( 'click', ipb.photoEditor.importUrl.bindAsEventListener(this) );
		}
	},
	
	/**
	 * Import photie!
	 */
	importUrl: function( e )
	{
		/* fetch data */
		var _url  = ipb.vars['base_url'] + '&app=members&module=ajax&section=photo&do=importUrl&secure_key=' + ipb.vars['secure_hash'];
		Debug.write( _url );
		
		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: { 'url': $('url_photo').value },
								onSuccess: function(t)
								{										    	
									/* Done! */
									$('url_photo').value = '';
									$('url_photo').defaultize( ipb.lang['photo_editor_enterurl'] );
									
									$('ips_ptype_custom').checked = true;
									
									ipb.photoEditor.photoUploaded( t.responseJSON, e );									
								}
							}						
						);	
	},
	
	/**
	 * Close pop up
	 */
	photoDone: function( e )
	{
		Event.stop(e);
		
		/* Still cropping? */
		if ( ipb.photoEditor.cropPhoto['cropper'] !== false )
		{
			alert( ipb.lang['photo_editor_cropping_still'] );
			return false;
		}
		
		/* If we're not using custom, lets do some data mining */
		if ( $('ips_ptype_custom').checked !== true )
		{
			var params = {};
			
			new Array( 'upload_photo', 'gravatar', 'facebook', 'twitter' ).each( function(item)
			{
				if ( $( 'ips_ptype_' + item ) && $( 'ips_ptype_' + item ).checked )
				{
					params['photoType'] = item;
				}
			} );
			
			/* Finish up */
			params['gravatar'] = $F('gravatar');
			
			/* fetch data */
			var _url  = ipb.vars['base_url'] + '&app=members&module=ajax&section=photo&do=save&secure_key=' + ipb.vars['secure_hash'];
			Debug.write( _url );
			
			new Ajax.Request( _url,
								{
									method: 'post',
									evalJSON: 'force',
									parameters: params,
									onSuccess: function(t)
									{										    	
										/* No Permission */
										if ( t.responseJSON && t.responseJSON['status'] == 'ok' )
										{
											ipb.photoEditor.updatePagePhotos( ipb.vars['member_id'], t.responseJSON['oldThumb'], t.responseJSON['thumb'], t.responseJSON['pp_main_photo'], [ t.responseJSON['pp_main_width'], t.responseJSON['pp_main_height'] ] );
										}
										
										if ( ! Object.isUndefined( ipb.global.popups['photoEditor'] ) )
										{
											ipb.global.popups['photoEditor'].hide();
										}
									}
								}						
							);	
			
		}
		else
		{
			if ( ! Object.isUndefined( ipb.global.popups['photoEditor'] ) )
			{
				ipb.global.popups['photoEditor'].hide();
			}
		}
	},
	
	/**
	 * Remove the photo
	 * @param e
	 */
	photoRemove: function(e)
	{
		Event.stop(e);
		
		/* fetch data */
		var _url  = ipb.vars['base_url'] + '&app=members&module=ajax&section=photo&do=remove&secure_key=' + ipb.vars['secure_hash'];		
		Debug.write( _url );
		
		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function(t)
								{										    	
									/* No Permission */
									if ( t.responseJSON && t.responseJSON['status'] == 'deleted' )
									{
										ipb.photoEditor.updatePagePhotos( ipb.vars['member_id'], t.responseJSON['oldThumb'], t.responseJSON['thumb'], t.responseJSON['pp_main_photo'] );
										
										if ( ! Object.isUndefined( ipb.global.popups['photoEditor'] ) )
										{
											ipb.global.popups['photoEditor'].hide();
										}
									}
								}
							}						
						);	

	},
	
	/**
	 * Radio button changed
	 * 
	 */
	rb: function(e)
	{
		elem = Event.findElement(e);
		
		switch ( elem.id )
		{
			case 'upload_photo':
				$('ips_ptype_custom').checked = true;
			break;
			case 'gravatar':
				$('ips_ptype_gravatar').checked = true;
			break;
			case 'facebook':
				$('ips_ptype_facebook').checked = true;
			break;
			case 'twitter':
				$('ips_ptype_twitter').checked = true;
			break;
		}
	},
	
	/**
	 * Init Cropper
	 * 
	 */
	photoCropStart: function(e, elem)
	{
		var img    = $('ips_currentPhoto').down('img');
		img.identify();
		var maxSize = 200;
		
		/* Already cropping? */
		if ( ipb.photoEditor.cropPhoto['cropper'] !== false )
		{
			return false;
		}
		
		var height = parseInt( img.readAttribute('height') );
		var width  = parseInt( img.readAttribute('width') );
		var size   = height;
		
		/* Scale it down? */
		if ( width > maxSize )
		{
			ipb.photoEditor.scaled = { 'origWidth': width, 'origHeight': height };
			
			height = Math.ceil( ( height * ( ( maxSize * 100 ) / width ) ) / 100 );
			width  = maxSize;
			
			img.width  = width;
			img.height = height;
		}
		else
		{
			ipb.photoEditor.scaled = { 'origWidth': width, 'origHeight': height };
			
			width  = Math.ceil( ( width * ( ( maxSize * 100 ) / height ) ) / 100 );
			height = maxSize;
			
			img.width  = width;
			img.height = height;
		}
		
		size = ( width < height ) ? width : height;
		
		/* Show ok/cancel */
		$('ips_cropperStart').hide();
		$('ips_cropperControls').show();
		
		/* Init cropper */
		ipb.photoEditor.cropPhoto['cropper'] = new Cropper.Img(  img.id,  { ratioDim: { x: size, y: size }, 
																			minWidth:  size,
																			minHeight: size,
																			maxWidth:  size,
																			maxHeight: size,
																			displayOnInit: true, 
																			onEndCrop: ipb.photoEditor.photoOnEndCrop  } );
		
		
	},
	
	/**
	 * User lets go of cropper outline
	 * @param coords
	 * @param dimensions
	 */
	photoOnEndCrop: function( coords, dimensions )
	{
		ipb.photoEditor.cropPhoto['coords'] = coords;
		ipb.photoEditor.cropPhoto['dims']   = dimensions;
	},
	
	/**
	 * Accept the cropping
	 * @param e
	 */
	photoCropAccept: function(e)
	{
		/* fetch data */
		var _url  = ipb.vars['base_url'] + '&app=members&module=ajax&section=photo&do=cropPhoto&secure_key=' + ipb.vars['secure_hash'];
		Debug.write( _url );
		
		/* is it scaled? */
		var img = $('ips_currentPhoto').down('img');
		
		var w = img.width;
		var h = img.height;
		var xFactor, yFactor = 1;
		
		if ( w < ipb.photoEditor.scaled['origWidth'] )
		{
			xFactor = ipb.photoEditor.scaled['origWidth'] / w;
		}
		
		if (  h < ipb.photoEditor.scaled['origHeight'] )
		{
			yFactor =ipb.photoEditor.scaled['origHeight'] / h;
		}

		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: { x1: parseInt( ipb.photoEditor.cropPhoto['coords'].x1 ) * xFactor,
											  x2: parseInt( ipb.photoEditor.cropPhoto['coords'].x2 ) * xFactor,
											  y1: parseInt( ipb.photoEditor.cropPhoto['coords'].y1 ) * yFactor,
											  y2: parseInt( ipb.photoEditor.cropPhoto['coords'].y2 ) * yFactor },
								onSuccess: function(t)
								{										    	
									/* No Permission */
									if ( t.responseJSON && t.responseJSON['status'] == 'ok' )
									{
										/* Close down the pop-up */
										ipb.photoEditor.photoCropCancel();
										
										ipb.photoEditor.updatePagePhotos( ipb.vars['member_id'], t.responseJSON['thumb'], t.responseJSON['thumb'], t.responseJSON['pp_main_photo'], [ t.responseJSON['pp_main_width'], t.responseJSON['pp_main_height'] ] );
									}
								}
							}						
						);	
	},
	
	/**
	 * Update photos on the page
	 * @param oldThumb
	 * @param newThumb
	 * @param newFull
	 */
	updatePagePhotos: function( memberId, oldThumb, newThumb, newFull, newFullSize )
	{
		/* update images on the page */
		$$('.ipsUserPhoto').each( function( elem )
		{
			try
			{
				if ( elem.readAttribute('id') != 'profile_photo' && elem.up('div').readAttribute('id') != 'ips_currentPhoto' && !elem.up('a').hasClassName('ipsUserPhotoLink') )
				{
					src  = elem.readAttribute('src');
					rand = Math.round( Math.random() * 100000000 );
					
					if ( src == oldThumb )
					{ 
						elem.src    = newThumb + '?t=' + rand;
						elem.width  = 50;
						elem.height = 50;
					}
				}
			} catch (err){}
		} );
		
		/* update images on the page based on URL */
		$$('.ipsUserPhotoLink').each( function( elem )
		{
			try
			{
				url  = elem.readAttribute('href');
				
				if ( url.match( new RegExp( "user/" + memberId + "-" ) ) )
				{
					img = elem.down('img');
					src  = img.readAttribute('src');
					rand = Math.round( Math.random() * 100000000 );
					
					if ( src.match( /-thumb/ ) )
					{ 
						img.src    = newThumb + '?t=' + rand;
						img.width  = 50;
						img.height = 50;
					}
					else
					{
						img.src    = newFull + '?t=' + rand;
						img.width  = 50;
						img.height = 50;
					}
				}
			} catch (err){}
		} );
		
		/* Update mini */
		rand = Math.round( Math.random() * 100000000 );
		
		if ( ! newThumb.match( /gravatar\.com/ ) )
		{
			$$('.ips_photoPreview._custom' ).first().down('img').writeAttribute( 'src', newThumb + '?t=' + rand );
			
			if ( ! Object.isUndefined( newFullSize ) && newFullSize < 100 )
			{
				$$('.ips_photoPreview._custom' ).first().down('img').writeAttribute( 'width', newFullSize[0] );
				$$('.ips_photoPreview._custom' ).first().down('img').writeAttribute( 'height', newFullSize[1] );
			}
			else
			{
				$$('.ips_photoPreview._custom' ).first().down('img').writeAttribute( 'width', 100 );
				$$('.ips_photoPreview._custom' ).first().down('img').writeAttribute( 'height', 100 );
			}
		}
				Debug.write( newFull);
		/* Update profile pic */
		if ( $('ips_currentPhoto') )
		{
			$('ips_currentPhoto').down('img').writeAttribute( 'src', newFull + '?t=' + rand );
			
			if ( ! Object.isUndefined( newFullSize ) )
			{
				$('ips_currentPhoto').down('img').writeAttribute( 'width', newFullSize[0] );
				$('ips_currentPhoto').down('img').writeAttribute( 'height', newFullSize[1] );
			}
			else
			{
				$('ips_currentPhoto').down('img').writeAttribute( 'width', 125 );
				$('ips_currentPhoto').down('img').writeAttribute( 'height', 125 );
			}
		}
		
		if ( $('profile_photo') )
		{
			$('profile_photo').writeAttribute( 'src', newFull + '?t=' + rand );

			if ( ! Object.isUndefined( newFullSize ) )
			{
				$('ips_currentPhoto').down('img').writeAttribute( 'width', newFullSize[0] );
				$('ips_currentPhoto').down('img').writeAttribute( 'height', newFullSize[1] );
			}
			else
			{
				$('ips_currentPhoto').down('img').writeAttribute( 'width', 125 );
				$('ips_currentPhoto').down('img').writeAttribute( 'height', 125 );
			}
		}
	},
	
	/**
	 * Cancel Cropper
	 * 
	 */
	photoCropCancel: function(e, elem)
	{
		$('ips_cropperControls').hide();
		$('ips_cropperStart').show();
		ipb.photoEditor.cropPhoto['cropper'].remove();
		
		ipb.photoEditor.cropPhoto['cropper'] = false;
	},
	
	/**
	 * Used as a callback for the inlineuploader
	 * @param json
	 */
	photoUploaded: function( json, e )
	{
		if ( Object.isUndefined( json['error'] ) )
		{
			var fullSize;
			var thumbSize;
			var rand = Math.round( Math.random() * 100000000 );
			
			if ( json['pp_main_photo'] )
			{
				if ( json['pp_main_photo'].match( /^http/ ) )
				{
					fullSize = json['pp_main_photo'] + '?t=' + rand;
				}
				else
				{
					fullSize = ipb.vars['upload_url'] + '/' + json['pp_main_photo'] + '?t=' + rand;
				}
				
			}
			
			if ( json['pp_thumb_photo'] )
			{
				if ( json['pp_thumb_photo'].match( /^http/ ) )
				{
					thumbSize = json['pp_thumb_photo'] + '?t=' + rand;
				}
				else
				{
					thumbSize = ipb.vars['upload_url'] + '/' + json['pp_thumb_photo'] + '?t=' + rand;
				}
			}
			
			$('ips_type_custom_error').hide();
			
			ipb.photoEditor.updatePagePhotos( ipb.vars['member_id'], json['oldThumb'], thumbSize, fullSize, [ json['pp_main_width'], json['pp_main_height'] ] );
		}
		else
		{
			$('ips_type_custom_error').show();
			$('ips_type_custom_error').update( json['error'] );
		}
	}
};

ipb.photoEditor.init();