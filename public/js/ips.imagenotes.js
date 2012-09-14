/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.slideshow.js - Gallery image notes code	*/
/* (c) IPS, Inc 2009							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _notes = window.IPBoard;

_notes.prototype.imagenotes = Class.create({
	
	/* Image ID */
	id: 0,
	/* Notes already made */
	notes: null,
	/* Wrapper */
	imageWrap: null,
	/* Mouseover handlers */
	mouseOvers: {},
	/* Mouseover for image */
	mainMouseover: null,
	/* Are we dragging */
	dragging: false,
	/* Default sizing */
	defaultSize: { 'width': 50, 'height': 50 },
	/* Incrementor */
	i: 0,
	/* Resizer objects */
	resizers: {},
	/* Currently editing...*/
	currentEdit: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	initialize: function( image, notes, options )
	{
		Debug.write("Initializing ips.imagenotes.js");
		if( !$( image ) ){ return; }
		
		this.id = $( image ).identify();
		this.notes = notes;
		this.options = Object.extend({
			editable: false,
			add_note: null
		}, options || {} );
		
		try
		{
			setTimeout( this.initImage().bind(this), 1000 );
		}
		catch(err) { }
	},
	
	/*------------------------------*/
	/* Initialize image				*/
	initImage: function()
	{
		if( !$( this.id ).complete )
		{
			this.initImage.bind( this ).delay( .2 );
			return;
		}
		Debug.write( "Initiated" );
		
		var imageDims = $( this.id ).getDimensions();
		
		// Wrap the image
		this.imageWrap = new Element( 'div', { id: this.id + '_wrap' } );
		$( this.id ).wrap( this.imageWrap );
		this.imageWrap.setStyle( { width: imageDims['width'] + 'px', height: imageDims['height'] + 'px' } ).addClassName('image_view_wrap');
		this.imageWrap.setStyle( { background: "url(data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==)", position: 'relative' } );
		
		// Build current notes
		this.buildNotes();
		
		// Set events on image
		$( this.imageWrap ).observe( 'mouseover', this.imageHover.bindAsEventListener( this ) );
		$( this.imageWrap ).observe( 'mouseout', this.imageHoverOff.bindAsEventListener( this ) );
		
		// Add droppables if we can edit
		if( this.options.editable ){
			Droppables.add( this.id + '_wrap', { accept: 'note_wrap', containment: $( this.imageWrap ), onDrop: function(){ return false; }.bind(this) } );
		}
		
		// Add note button
		if( this.options.add_note ){
			$( this.options.add_note ).observe('click', this.addNote.bindAsEventListener( this ));
		}
		
		if ( $('notes_trigger') )
		{
			$('notes_trigger').setStyle( { cursor: 'pointer' } );
			$('notes_trigger').observe( 'mouseover', this.imageHover.bindAsEventListener( this ) );
			$('notes_trigger').observe( 'mouseout' , this.imageHoverOff.bindAsEventListener( this ) );
		}
		
		// Set up body events
		Event.observe( document.body, 'click', this.bodyClick.bindAsEventListener( this ) );
		
	},
	
	/*------------------------------*/
	addNote: function( e )
	{
		Event.stop(e);
		
		if( this.currentEdit ){
			return;
		}
		
		ipb.menus.closeAll(e);
		
		this.i++;
		
		/* Cancel lightbox */
		this._cancelLightbox();
		
		var wrapDims = $( this.imageWrap ).getDimensions();
		
		this.buildNote( this.id + '_new_' + this.i, { 	content: '',
		 												width: this.defaultSize['width'], height: this.defaultSize['height'],
														top: Math.ceil( wrapDims['height'] / 2 ), left: Math.ceil( wrapDims['width'] / 2 )
													});
													
		// Show notes even if not mousing over
		this.imageHover( e, function(){ 
			this.noteSelect( e, this.id + '_new_' + this.i );
		}.bind(this) );
				
	},
	
	/*------------------------------*/
	/* image mouseover				*/
	imageHover: function( e, afterFinish )
	{
		clearTimeout( this.mainMouseover );
		
		this.mainMouseover = setTimeout( function(){
			$( this.imageWrap ).select('.note_wrap').each( function(item){
				new Effect.Appear( $( item ), { duration: 0.2, afterFinish: afterFinish || null } );
			});
		}.bind(this), 300 );		
	},
	
	/*------------------------------*/
	/* image mouseoff				*/
	imageHoverOff: function( e )
	{
		clearTimeout( this.mainMouseover );
		
		this.mainMouseover = setTimeout( function(){
			$( this.imageWrap ).select('.note_wrap').each( function(item){
				if( $( item ).readAttribute('editing') != 'yes' ){
					new Effect.Fade( $( item ), { duration: 0.2 } );
				}
			});
		}.bind( this ), 1000 );
	},
	
	/*------------------------------*/
	/* Build existing notes			*/
	buildNotes: function()
	{
		
		this.notes.each( function( item ){
			this.buildNote( this.id + '_' + item.key, item.value );						
		}.bind(this) );
	},
	
	/*------------------------------*/
	/* Builds a note				*/
	buildNote: function( id, info )
	{
		/* Info should contain:
		 * { content, left, top, width, height }
		 */
		
		var note = noteTemplate.evaluate( { id: id, text: info['content'] } );
		$( this.id + '_wrap' ).insert( note );
		
		/* IE won't recognize mouseover unless there's some kind of fill inside,
		 * so we'll set a very transparent background on it */
		if( Prototype.Browser.IE ){
			$( 'note_fill_' + id ).setStyle('background: #fff; opacity: 0.01');
		} 
		
		// Set the size
		$('note_' + id ).setStyle( { left: info['left'] + 'px', top: info['top'] + 'px' } ).hide();
		$('note_box_' + id ).setStyle( { width: info['width'] + 'px', height: info['height'] + 'px' } );
		
		// Set event
		$( 'note_box_' + id ).observe('mouseover', this.noteHover.bindAsEventListener( this, id ));
		$( 'note_box_' + id ).observe('mouseout', this.noteHoverOff.bindAsEventListener( this, id ));
		
		if( this.options.editable ){
			$( 'note_' + id ).addClassName('editable');
			$( 'note_box_' + id ).observe('click', this.noteSelect.bindAsEventListener( this, id ) );
			
			// Add draggable
			new Draggable( $('note_' + id), { 
				revert: 'failure',
				onDrag: function(){
					$('note_' + id ).addClassName('selected');
					$('note_text_' + id ).hide();
					this.dragging = true;
				}.bind(this),
				onEnd: function(){
					this.updateCoords( id );					
				}.bind(this)
			});
			
			this.resizers[ id ] = new ipb.resizer( 
				$('note_box_' + id ),
				$('note_handle_' + id ),
			 	{
					onStart: function(drag){
						$('note_' + id ).addClassName('selected');
						$('note_text_' + id ).hide();
						this.dragging = true;
					}.bind(this),
					onFinish: function(drag){
						this.updateCoords( id );	
					}.bind(this)							
				 }
			);
		}	
		
		Debug.write( id );	
	},
	
	updateCoords: function( id )
	{
		var realID = id.replace( this.id + '_', '');
		
		if( !this.notes.get( realID ) ){ return; } // This is a new, unsaved note
		
		var containerPos = $('note_' + id).positionedOffset();
		var boxSize = $('note_box_' + id).getDimensions();
		var noteId = this.notes.get( realID )['noteId'];
		var content = this.notes.get( realID )['content'];

		new Ajax.Request( ipb.vars['base_url'] + "app=gallery&module=ajax&section=image&do=edit-note&secure_key="+ipb.vars['secure_hash']+'&img='+ipb.gallery.imageID,
						{
							method: 'post',
							parameters: {
								'top': containerPos['top'],
								'left': containerPos['left'],
								'width': boxSize['width'],
								'height': boxSize['height'],
								'note': content,
								'noteId': noteId
							},
							onSuccess: function(t)
							{
								Debug.write( t.responseText );
								
								switch( t.responseText )
								{
									case 'ok':
										this.notes.get( realID )['top'] = containerPos['top'];
										this.notes.get( realID )['left'] = containerPos['left'];
										this.notes.get( realID )['width'] = boxSize['width'];
										this.notes.get( realID )['height'] = boxSize['height'];
										
										Debug.write("Width: " + boxSize['width']);
										Debug.write("height: " + boxSize['height']);
									break;
									case 'nopermission':
										alert( ipb.lang['note_no_permission_e'] );
									break;
									case 'missing_data':
										alert( ipb.lang['required_data_missing'] );
									break;
								}
							}.bind( this )
						});
		
		setTimeout( function(){
			if( $('note_' + id).readAttribute('editing') != 'yes' ){
				$('note_' + id ).removeClassName('selected');
			}
			this.dragging = false;
		}.bind(this), 200);
		
	},
	
	/*------------------------------*/
	/* Note select					*/
	noteSelect: function( e, id )
	{
		Event.stop( e );
		
		if( this.dragging ){ return; }	
		
		/* Cancel it */
		this._cancelLightbox();
		
		$( 'note_' + id ).addClassName('selected');
		
		if( !this.currentEdit )
		{
			this.currentEdit = id;
			
			$( 'note_' + id ).writeAttribute('editing', 'yes');
			$( 'note_text_' + id).hide();

			var realID = id.replace( this.id + '_', '');
		
			if( realID.startsWith('new_') && Object.isUndefined( this.notes.get( realID ) ) ){
				var content = '';
			} else {
				var content = this.notes.get( realID )['content'];
			}
		
			var noteForm = editTemplate.evaluate({ id: id, content: content });
			$( 'note_' + id ).insert( noteForm );		
			$('note_content_' + id).activate();
			
			if( realID.startsWith('new_') && Object.isUndefined( this.notes.get( realID ) ) ){
				$('note_cancel_' + id).hide();
			} else {
				$('note_cancel_' + id).show();
			}
			
			// Events
			$( 'note_save_' + id ).observe( 'click', this.formSave.bindAsEventListener( this, id ) );
			$( 'note_cancel_' + id ).observe( 'click', this.formCancel.bindAsEventListener( this, id ) );
			$( 'note_delete_' + id ).observe( 'click', this.formDelete.bindAsEventListener( this, id ) );
		}
	},
	
	/*------------------------------*/
	/* Save note					*/
	formSave: function( e, id )
	{
		if( e ){ Event.stop(e); }
		
		if( $F('note_content_' + id).blank() ){
			alert( ipb.lang['note_save_empty'] );
			return;
		}
		
		var realID = id.replace( this.id + '_', '');
		var content = $F( 'note_content_' + id );
		var newNote = false;
		var containerPos = {};
		var boxSize = {};
		var type = '';
		var noteId = null;
		var done = false;
		
		// Is this a new note?
		if( realID.startsWith('new_') && Object.isUndefined( this.notes.get( realID ) ) ){
			containerPos = $('note_' + id).positionedOffset();
			boxSize = $('note_box_' + id).getDimensions();
			newNote = true;
			type = 'add';	
		} else {
			containerPos = { 'left': this.notes.get( realID )['left'], 'top': this.notes.get( realID )['top'] };
			boxSize = { 'width': this.notes.get( realID )['width'], 'height': this.notes.get( realID )['height'] };	
			type = 'edit';
			noteId = this.notes.get( realID )['noteId'];
		}
		
		new Ajax.Request( ipb.vars['base_url'] + "app=gallery&module=ajax&section=image&do=" + type + "-note&secure_key="+ipb.vars['secure_hash']+'&img='+ipb.gallery.imageID,
						{
							method: 'post',
							parameters: {
								'top': containerPos['top'],
								'left': containerPos['left'],
								'width': boxSize['width'],
								'height': boxSize['height'],
								'note': content.encodeParam(),
								'noteId': noteId
							},
							onSuccess: function(t)
							{
								Debug.write( t.responseText );
								
								/*** ADDING A NOTE ***/
								if( type == 'add' )
								{
									if( t.responseText.startsWith('ok') )
									{
										noteId = t.responseText.replace('ok|', '');
										this.notes.set( realID, {
											'width': boxSize['width'],
											'height': boxSize['height'],
											'top': containerPos['top'],
											'left': containerPos['left'],
											'content': content,
											'noteId': noteId
										} );
										done = true;
									}
									else
									{
										switch( t.responseText )
										{			
											case 'missing_data':
												alert( ipb.lang['required_data_missing'] );
											break;
											case 'nopermission':
												alert( ipb.lang['notes_no_permission_a'] );
											break;
											case 'max_notes_reached':
												alert( ipb.lang['max_notes_reached'] );
											break;
										}
									}
								}
								/*** EDITING A NOTE ***/
								else
								{
									switch( t.responseText )
									{
										case 'ok':
											this.notes.get( realID )['content'] = content;
											done = true;											
										break;
										case 'missing_data':
											alert( ipb.lang['required_data_missing'] );
										break;
										case 'nopermission':
											alert( ipb.lang['notes_no_permission_e'] );
										break;
									}
								}
								
								if( done ){
									$('note_text_' + id).update( content );

									this.finishEditing( id );

									$('note_text_' + id).show();
									this.noteHoverOff( null, id );
								}					
							}.bind(this)
						});						
		
	},
	
	/*------------------------------*/
	/* Cancel note					*/
	formCancel: function( e, id )
	{
		if( e ){ Event.stop(e); }
		this.finishEditing( id );		
	},
	
	/*------------------------------*/
	/* Delete note					*/
	formDelete: function( e, id )
	{
		if( e ){ Event.stop(e); }
		
		if( confirm( ipb.lang['note_confirm_delete'] ) )
		{
			var realID = id.replace( this.id + '_', '');
			
			if( this.notes.get( realID ) )
			{
				new Ajax.Request( ipb.vars['base_url'] + "app=gallery&module=ajax&section=image&do=remove-note&secure_key="+ipb.vars['secure_hash']+'&img='+ipb.gallery.imageID,
								{
									method: 'post',
									parameters: {
										'noteId': this.notes.get( realID )['noteId']
									},
									onSuccess: function(t)
									{
										switch( t.responseText )
										{
											case 'ok':												
												new Effect.Fade( $('note_' + id), { duration: 0.2, afterFinish: function(){
														this.finishEditing( id );
														$('note_' + id).remove();
														this.notes.unset( realID );
													}.bind( this )
												});												
											break;
											case 'nopermission':
												alert( ipb.lang['note_no_permission_d'] );
											break;
											case 'missing_data':
												alert( ipb.lang['required_data_missing'] );
											break;
										}
									}.bind( this )
								});
			}
			else
			{
				new Effect.Fade( $('note_' + id), { duration: 0.2, afterFinish: function(){
						this.finishEditing( id );
						$('note_' + id).remove();
					}.bind( this )
				});
			}				
		}
	},
	
	/*------------------------------*/
	/* Completed editing			*/
	finishEditing: function( id )
	{
		// Remove form
		$( 'note_form_' + id ).remove();
		$( 'note_' + id ).writeAttribute('editing', 'no').removeClassName('selected');
		this.currentEdit = null;
		
		/* Restore lightbox */
		this._restoreLightbox();
	},
	
	/*------------------------------*/
	/* Note mouseover				*/
	noteHover: function(e, id)
	{				
		if( !this.dragging && $('note_' + id).readAttribute('editing') != 'yes' )
		{
			clearTimeout( this.mouseOvers[ id ] );
		
			this.mouseOvers[ id ] = setTimeout( function(){
				new Effect.Appear( $( 'note_text_' + id ), { duration: 0.2 } );
			}.bind( this ), 300 );
		}
	},
	
	/*------------------------------*/
	/* Note mouseoff				*/
	noteHoverOff: function(e, id)
	{		
		clearTimeout( this.mouseOvers[ id ] );
		
		if( $('note_text_' + id ).visible() )
		{
			this.mouseOvers[ id ] = setTimeout( function(){
				new Effect.Fade( $( 'note_text_' + id ), { duration: 0.2 } );
			}.bind( this ), 800 );
		}
	},
	
	/*------------------------------*/
	/* Handles document click		*/
	bodyClick: function( e )
	{
		// Get all notes inside the wrapper
		$( this.imageWrap ).select('.note_wrap').each( function( elem ){
			if( !Event.element(e).descendantOf( elem ) ){
				if( $( elem ).readAttribute('editing') != 'yes' ){
					$( elem ).removeClassName('selected');
				}
			}
		});		
	}
	,
	
	/**
	 * Cancel lightbox
	 */
	_cancelLightbox: function()
	{
		if ( $('ips_lightbox') )
		{
			$('ips_lightbox').writeAttribute('available', 'false');
		}
	},
	
	/**
	 * Restore lightbox
	 */
	_restoreLightbox: function()
	{
		if ( $('ips_lightbox') )
		{
			$('ips_lightbox').writeAttribute('available', 'true');
		}
	}
	
});

/************************************************/
/* Element resizing class						*/
/************************************************/
var _resize = window.IPBoard;
_resize.prototype.resizer = Class.create({
	
	container: null,
	handler: null,
	movePosition: {'x': 0, 'y': 0},
	func: null,
	
	/*--------------------------*/
	/* Constructor 				*/
	initialize: function( container, handle, options )
	{
		if( !$( container ) || !$( handle ) ){
			return false;
		}
		
		this.container = $( container );
		this.handle = $( handle );
		this.options = Object.extend( {
			onStart: null,
			onFinish: null
		}, options || {});
		
		new Draggable( $( this.handle ), { 
			onStart: this.setUpResize.bindAsEventListener( this ),
			change: this.mouseMoved.bindAsEventListener( this ),
			onEnd: this.endResize.bindAsEventListener( this )
		});
		
	},
	
	/*---------------------------*/
	/* onStart event, gets sizes */			
	setUpResize: function( draggable, e )
	{
		this.containerPos = $( this.container ).positionedOffset();	
		this.handleSize = $( this.handle ).getDimensions();

		$( this.container ).makePositioned();
		$( this.handle ).makePositioned();
		
		// Set cursor
		document.body.addClassName('resizing');
		
		if( Object.isFunction( this.options.onStart ) ){
			this.options.onStart( draggable );
		}
	},
	
	/*--------------------------*/
	/* Finish resizing			*/
	endResize: function( draggable, e )
	{
		document.body.removeClassName('resizing');
		
		if( Object.isFunction( this.options.onFinish ) ){
			this.options.onFinish( draggable );
		}
	},
	
	/*--------------------------*/
	/* 'change' event			*/
	mouseMoved: function( draggable, e )
	{
		// Get position
		var pos = $( this.handle ).positionedOffset();
	
		var newWidth = ( pos['left'] - this.containerPos['left'] ) + this.handleSize['width'];
		var newHeight = ( pos['top'] - this.containerPos['top'] ) + this.handleSize['height'];
		
		if( newWidth < 25 ){
			newWidth = 25;
			$( this.handle ).setStyle( { left: ( ( this.containerPos['left'] + 25 ) - this.handleSize['width'] ) + 'px' } );
		}
		
		if( newHeight < 25 ){
			newHeight = 25;
			$( this.handle ).setStyle( { top: ( ( this.containerPos['top'] + 25 ) - this.handleSize['height'] ) + 'px' } );
		}
				
		$( this.container ).setStyle( { width: newWidth + 'px', height: newHeight + 'px' } );		
	}
	
});