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

var _ucp = window.IPBoard;

_ucp.prototype.ucp = {
	cropPhoto: { 'cropper': false },
	aboutme_pop: null,
	
	init: function()
	{
		Debug.write("Initializing ips.ucp.js");
		
		document.observe("dom:loaded", function(){
			if ( $('userCPForm') && $('userCPForm').action.match( "area=photo" ) ){
				ipb.ucp.setUpPhotoForm();
			} 
			
			if( $('edit_aboutme') ){
				$('edit_aboutme').observe('click', function(e){
					Event.stop(e);
					if( ipb.ucp.aboutme_pop ){
						ipb.ucp.aboutme_pop.show();
					} else {
						ipb.ucp.aboutme_pop = new ipb.Popup( 'aboutme', {
													type: 'pane',
												 	modal: false,
													initial: $('aboutme_editor').show(),
													hideAtStart: false,
													evalJs: 'force',
													w: '750px',
													h: 600 
												} );
						
						setTimeout( ipb.ucp.initAboutMe, 200 );
						
						/* More jiggery pokery to get around pop-up confusion */
						$('close_aboutme_editor').observe( 'click', ipb.ucp.saveAboutMe );
					}
				});
			}
			
			if( $('auto_track') )
			{
				$('auto_track').observe('click', function(e){

					if( $F('auto_track') )
					{
						if( $F('track_choice') == 'none' )
						{
							var myOpt	= $('track_choice').select('option[value="offline"]');
							
							if( myOpt && myOpt.length > 0 )
							{
								myOpt[0].selected	= true;
							}
						}
					}
				});
			}	
		} );
	},
	
	/**
	 * Init about me
	 */
	initAboutMe: function()
	{
		ipb.textEditor.getEditor().initEditor();
		editorId = ipb.textEditor.getCurrentEditorId();
		
		if ( editorId )
		{
			ipb.textEditor.getEditor( editorId ).initEditor();
			
			$( ipb.textEditor.getCurrentEditorId() ).writeAttribute( 'name', 'Post_x' );
			$('userCPForm').insert( new Element('input', { 'type': 'hidden', 'name': 'Post', 'id': 'Post_About_me' } ) );
		}
	},
	
	/**
	 * Save about me
	 */
	saveAboutMe: function(e)
	{
		Event.stop(e);
		ipb.ucp.aboutme_pop.hide();
		
		/* ensure text area is updated - do this manually 'cos normal triggers are confused by pop-up */
		$('Post_About_me').value = ipb.textEditor.getEditor().getText();
		
		$('userCPForm').submit();
	},
	
	/**
	 * Set up the photo form
	 */
	setUpPhotoForm: function()
	{	
		/* Watch cropper init */
		ipb.delegate.register('.cropperStart', ipb.ucp.photoCropStart );
		
		/* Activate ok/cancel linkies */
		ipb.delegate.register('.cropperAccept', ipb.ucp.photoCropAccept );
		ipb.delegate.register('.cropperCancel', ipb.ucp.photoCropCancel );
		
		/* Watch form */
		ipb.inlineUploader.watch( 'userCPForm', 'upload_photo', ipb.ucp.photoUploaded );
		
		/* Watch for radio buttons */
		new Array( 'upload_photo', 'gravatar' ).each( function(item)
		{
			if ( $(item ) )
			{
				$(item ).observe( 'click', ipb.ucp.rb );
			}
		} );
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
		
		/* Already cropping? */
		if ( ipb.ucp.cropPhoto['cropper'] !== false )
		{
			return false;
		}
		
		var height = parseInt( img.readAttribute('height') );
		var width  = parseInt( img.readAttribute('width') );
		var size   = height;
		
		if ( width < height )
		{
			size = width;
		}
		
		/* Show ok/cancel */
		$('ips_cropperControls').show();
		$('ips_cropperControls').setStyle( 'margin-top: ' + ( height + 5 ) + 'px' );
		
		/* Init cropper */
		ipb.ucp.cropPhoto['cropper'] = new Cropper.Img(  img.id,  { ratioDim: { x: size, y: size }, 
																	minWidth:  size,
																	minHeight: size,
																	maxWidth:  size,
																	maxHeight: size,
																	displayOnInit: true, 
																	onEndCrop: ipb.ucp.photoOnEndCrop  } );
		
		
	},
	
	/**
	 * User lets go of cropper outline
	 * @param coords
	 * @param dimensions
	 */
	photoOnEndCrop: function( coords, dimensions )
	{
		ipb.ucp.cropPhoto['coords'] = coords;
		ipb.ucp.cropPhoto['dims']   = dimensions;
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
		
		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: { x1: ipb.ucp.cropPhoto['coords'].x1,
											  x2: ipb.ucp.cropPhoto['coords'].x2,
											  y1: ipb.ucp.cropPhoto['coords'].y1,
											  y2: ipb.ucp.cropPhoto['coords'].y2 },
								onSuccess: function(t)
								{										    	
									/* No Permission */
									if ( t.responseJSON && t.responseJSON['status'] == 'ok' )
									{
										/* Close down the pop-up */
										ipb.ucp.photoCropCancel();
										
										/* Update mini */
										rand = Math.round( Math.random() * 100000000 );
										$$('.ips_photoPreview._custom' ).first().down('img').writeAttribute( 'src', t.responseJSON['thumb'] + '?t=' + rand );
										
										/* update images on the page */
										$$('.photo').each( function( elem )
										{
											try
											{
												src  = elem.readAttribute('src');
												rand = Math.round( Math.random() * 100000000 );
												
												if ( src == t.responseJSON['thumb'] )
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
	
	/**
	 * Cancel Cropper
	 * 
	 */
	photoCropCancel: function(e, elem)
	{
		$('ips_cropperControls').hide();
		ipb.ucp.cropPhoto['cropper'].remove();
		
		ipb.ucp.cropPhoto['cropper'] = false;
	},
	
	/**
	 * Used as a callback for the inlineuploader
	 * @param json
	 */
	photoUploaded: function( json )
	{
		var fullSize;
		var thumbSize;
		var rand = Math.round( Math.random() * 100000000 );
		
		if ( json['pp_main_photo'] )
		{
			fullSize = ipb.vars['upload_url'] + '/' + json['pp_main_photo'] + '?t=' + rand;
		}
		
		if ( json['pp_thumb_photo'] )
		{
			thumbSize = ipb.vars['upload_url'] + '/' + json['pp_thumb_photo'] + '?t=' + rand;
		}
		
		/* Update data */
		$('ips_currentPhoto').down('img').writeAttribute( 'src', fullSize );
		$('ips_currentPhoto').down('img').writeAttribute( 'width', json['pp_main_width'] );
		$('ips_currentPhoto').down('img').writeAttribute( 'height', json['pp_main_height'] );
		
		/* Update mini */
		$$('.ips_photoPreview._' + json['type'] ).first().down('img').writeAttribute( 'src', thumbSize );
		
		/* update images on the page */
		$$('.photo').each( function( elem )
		{
			try
			{
				src  = elem.readAttribute('src');
				
				if ( src == ipb.vars['upload_url'] + '/' + json['pp_thumb_photo'] )
				{
					elem.src    = thumbSize;
					elem.width  = 50;
					elem.height = 50;
				}
			} catch (err){}
		} );
	}
};

ipb.ucp.init();