/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ipb.gallery_uploader.js - Gallery uploader	*/
/* (c) IPS, Inc 2010							*/
/* -------------------------------------------- */
/* Author: Matt Mecham							*/
/************************************************/
/* -TRUE- MULTIPLE ATTACHMENTS!!!				*/
/* -------------------------------------------- */

var _uploader = window.IPBoard;

_uploader.prototype.uploader = {
	uploaders: [],
	template: '',
	thumbSize: 50,
	_currentAlbumId: 0,
	_to: '',
	popup: {},
	
	init: function()
	{
		Debug.write("Initializing ipb.gallery_uploader.js");
		
		document.observe("dom:loaded", function(){
			/* Observe select album button */
			ipb.delegate.register('._albumSelector', ipb.uploader._albumSelectorLaunch );
			
			/* Observe select album button */
			ipb.delegate.register('._albumNew'     , ipb.uploader._albumNewLaunch );
			
			/* File types */
			ipb.delegate.register("#showFileTypes", ipb.uploader.showFileTypes);
		});
	},
	
	switchUploadType: function( mode )
	{
		var url  = ipb.vars['base_url'] + "app=core&module=ajax&section=attach&do=setPref&pref=" + mode + '&secure_key=' + ipb.vars['secure_hash'];
		var wLoc = window.location.toString();

		new Ajax.Request( url,
						{
							method: 'post',
							evalJSON: 'force',
							hideLoader: true,
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }
								
								if ( t.responseJSON['status'] == 'ok' )
								{
									window.location = wLoc.replace("#", "").replace( new RegExp( /&uploadPref=\S+?(&|$)/ ), '' ) + '&uploadPref=' + mode;
								}
								else
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
							}
						});
	},
	
	showFileTypes: function(e)
	{
		Event.stop(e);
		var elem = Event.findElement(e, 'a');
		
		if( ipb.uploader.popup['fileTypes'] )
		{
			ipb.uploader.popup['fileTypes'].show();
		}
		else
		{
			/* easy one this... */
			ipb.uploader.popup['fileTypes'] = new ipb.Popup( 'fileTypes', {
															type: 'balloon',
															initial: $('showFileTypesContent').innerHTML,
															hideAtStart: false,
															stem: true,
															hideClose: true,
															attach: { target: elem, position: 'auto' },
															w: '400px'
														} );
		}
	},
	
	/**
	 * Dynamically build an album box
	 *
	 */
	buildAlbumBox: function( album_id, template, wrapper )
	{
		var _t = template;
		
		if ( album_id )
		{
			ipb.uploader.setCurrentAlbumId( album_id );
		}
		
		/* Move the upload box out of the way. Flash being touchy */
		$('uploadFieldWrap').setStyle( { 'position': 'absolute', 'left': '-1000px' } ).show();
		
		/* Got an album id? Show the upload box */
		if ( parseInt( album_id ) < 1 )
		{
			$('albumWrapNone').show();
		}
		
		Debug.write( "Fetching album details for " + album_id );
			
		/* Fetch JASON */
		new Ajax.Request( ipb.vars['base_url'] + 'app=gallery&module=ajax&section=album&do=fetchAlbumJson&album_id=' + album_id + '&secure_key=' + ipb.vars['secure_hash'],
		{
			method: 'post',
			evalJSON: 'force',
			onSuccess: function(t)
			{
				if( Object.isUndefined( t.responseJSON ) )
				{
					alert( ipb.lang['action_failed'] );
					return;
				}
				
				if ( t.responseJSON['album_id'] )
				{ 
					if ( $(wrapper) )
					{
						$('albumWrapNone').hide();
						
						$(wrapper).update( template.evaluate( $H( t.responseJSON ) ) );
						$(wrapper).hide();
						
						$(wrapper).down('img.galattach').addClassName('gallery_photo_wrap');
						
						new Effect.Appear( wrapper, { duration: 0.8 } );
						
						/* Use a time out box to stop flash being touchy */
						ipb.uploader._to = setTimeout( "ipb.uploader.showUploadBox()", 500 );
					}
				}
				else
				{
					return;
				}
			}
		});
	},
	
	/**
	 * Launch album selection box.
	 */
	_albumSelectorLaunch: function()
	{
		return ipb.gallery.selectAlbumDialogue();
	},
	
	/**
	 * Launch new album box.
	 */
	_albumNewLaunch: function(e, elem)
	{
		return ipb.gallery.newAlbumDialogue(e, elem);
	},
	
	/**
	 * Use a time out box to stop flash being touchy
	 */
	showUploadBox: function()
	{
		clearTimeout( ipb.uploader._to );
		/* move the box back, hide then show */
		$('uploadFieldWrap').setStyle( { 'position': 'relative', 'left': '0px' } ).hide();
		new Effect.Appear( $('uploadFieldWrap'), { duration: 0.8 } );
	},
	
	/**
	 * Fetch current album ID
	 */
	getCurrentAlbumId: function()
	{
		return parseInt( ipb.uploader._currentAlbumId );
	},
	
	/**
	 * Set current album ID
	 */
	setCurrentAlbumId: function( id )
	{
		ipb.uploader._currentAlbumId = parseInt( id );
	},
	
	/* ------------------------------ */
	/**
	 * Registers an upload object
	 * 
	 * @param	{int}		id			The uploader ID
	 * @param	{object}	options		Options to pass to object
	*/
	registerUploader: function( id, type, wrapper, options )
	{
		if( Object.isUndefined( id ) || id == null )
		{
			Debug.error("ipb.gallery_uploader.js: Attachment manager already has that ID");
			return;
		}
		// Already exists?
		if( ipb.uploader.uploaders[ id ] )
		{
			Debug.error("ipb.gallery_uploader.js: This uploader has already been registered");
		}
		
		//-----------------------------------------
		// Make object
		
		if( type == 'swf' ){
			if( options.file_size_limit )
			{
				options.file_size_limit = options.file_size_limit + " B";
			}
			uploader = new ipb.attachSWF( id, options, wrapper, ipb.uploader.template );
		} else {
			uploader = new ipb.attachTraditional( id, options, wrapper, ipb.uploader.template );
		}
		
		if( uploader )
		{
			ipb.uploader.uploaders[ id ] = uploader;
			
			if( $( 'nojs_' + id + '_1' ) ){
				$( 'nojs_' + id + '_1' ).hide();
			}
			if( $( 'nojs_' + id + '_2' ) ){
				$( 'nojs_' + id + '_2' ).hide();
			}
		}		
	},
	
	removeUpload: function(e)
	{
		elem = Event.findElement( e, 'li' );
		elemid = elem.id.replace('cur_', '');
		elemid = elemid.match( /^ali_(.+?)_([0-9a-zA-Z]+)$/ );
		
		if( !elemid[1] ){ return; }
		
		obj = ipb.uploader.uploaders[ elemid[1] ];
		attachid = elem.readAttribute('attachid').replace('cur_', '');
		
		// Send request to remove upload
		new Ajax.Request( ipb.vars['base_url'] + "app=gallery&module=ajax&section=image&do=uploadRemove" + "&sessionKey=" + obj.options['sessionKey'] + "&uploadKey=" + attachid + '&secure_key=' + ipb.vars['secure_hash'],
						{
							method: 'post',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }
								
								if( t.responseJSON.msg == 'upload_removed' )
								{
									ipb.uploader.uploaders[ elemid[1] ].removeUpload( elem.readAttribute('attachid'), elemid[2], t.responseJSON );
								}
								else
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
							}
						});
		
		Event.stop(e);
	},
	
	startedUploading: function(handler)
	{
		$('uploadBoxWrap').show();
		return true;
	},
	
	finishedUploading: function( attachid, fileindex )
	{
		if( !ipb.uploader.uploaders[ attachid ] ){ return; }
		if( !ipb.uploader.uploaders[ attachid ].boxes[ fileindex ] ){ return; }
		
		var row = ipb.uploader.uploaders[ attachid ].boxes[ fileindex ];
		
		if( $( row ) )
		{
			$$('.galleryNextButton').each( function(id) {
				new Effect.Appear( id, { duration: 0.8 } );
			} );
			
			new Effect.Fade( $('help_msg') );
		}		
	},
	
	/* ------------------------------ */
	/**
	 * Fetches an uploader
	 * 
	 * @param	{int}	id		ID of uploader to get
	 * @return	mixed			Object if exists, false if not
	*/
	getUploader: function( id )
	{
		if( ipb.uploader.uploaders[ id ] )
		{
			return ipb.uploader.uploaders[ id ];
		}
		else
		{
			return false;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Returns a human-readable string for errors
	 * 
	 * @param	{string}	msg		The msg code
	 * @return	{string}			The human-readable message
	*/
	_determineServerError: function( msg )
	{
		if( msg.blank() ){ return ipb.lang['silly_server']; }
		
		if( !Object.isUndefined( ipb.lang[ msg ] ) )
		{
			return ipb.lang[ msg ];
		}
		else
		{
			return ipb.lang['silly_server'];
		}
	},
	
	_jsonPass: function( id, json )
	{
		ipb.uploader.uploaders[ 'attach_' + id ].json = json;
		ipb.uploader.uploaders[ 'attach_' + id ].isReady();
		
		Debug.write( "ipb.gallery_uploader.js: Got json back from iframe id " + id );
	},
	
	/**
	* Builds boxes
	*/
	_buildBoxes: function( currentItems )
	{
		for( var i in currentItems )
		{
			/* Already got some items in the upload box? */
			$('uploadBoxWrap').show();
			$$('.galleryNextButton').invoke('show');
			
			Debug.write( "Templating item: " + currentItems[i][1] );
			
			id    = i;
			index = 'cur_' + currentItems[i][0];
			name  = currentItems[i][1];
			size  = currentItems[i][2];
			temp = uploader.template.gsub(/\[id\]/, uploader.id + '_' + index).gsub(/\[name\]/, name);

			$( uploader.wrapper ).insert( temp );

			$( 'ali_' + uploader.id + '_' + index ).select('.progress_bar')[0].hide();

			new Effect.Appear( $( 'ali_' + uploader.id + '_' + index ), { duration: 0.3 } );
			
			$( 'ali_' + uploader.id + '_' + index ).select('.info')[0].update( ipb.global.convertSize( size ) );
			// Add attachID
			$( 'ali_' + uploader.id + '_' + index ).writeAttribute( 'attachid', index );
			// Add event handlers
			$( 'ali_' + uploader.id + '_' + index ).select('.delete')[0].observe( 'click', ipb.uploader.removeUpload );
			
			// Remove old statuses
			['complete', 'in_progress', 'error'].each( function( cName ){ $( 'ali_' + uploader.id + '_' + index ).removeClassName( cName ); }.bind( uploader ) );

			$( 'ali_' + uploader.id + '_' + index ).addClassName( 'complete' );
			
			if( currentItems[ i ][ 3 ] == 1 )
			{
				tmp = currentItems[ i ];

				var width = tmp[5];
				var height = tmp[6];

				if( ( tmp[5] && tmp[5] > ipb.uploader.thumbSize ) )
				{
					width = ipb.uploader.thumbSize;
					factor = ( ipb.uploader.thumbSize / tmp[5] );
					height = tmp[6] * factor;
				}

				if( ( tmp[6] && tmp[6] > ipb.uploader.thumbSize ) )
				{
					height = ipb.uploader.thumbSize;
					factor = ( ipb.uploader.thumbSize / tmp[5] );
					width = tmp[5] * factor;
				}

				thumb = new Element('div').update( tmp[4] ).hide();
				
				$( 'ali_' + uploader.id + '_' + index ).select('.img_holder')[0].insert( thumb );
				new Effect.Appear( $( thumb ), { duration: 0.4 } );
			}
			
			uploader.boxes[ index ] = 'ali_' + uploader.id + '_' + index;
			
			// Fire finishedUpload
			ipb.uploader.finishedUploading( uploader.id, index );
		}
	}
};
ipb.uploader.init();

//==============================================================

_uploader.prototype.attachTraditional = Class.create({
	options: [],
	boxes: [],
	json: {},
	
	initialize: function( id, options, wrapper, template )
	{
		this.id = id;
		this.wrapper = wrapper;
		this.template = template;
		this.options = options;
		
		/* Build iframe */
		this.iframe = new Element('iframe', { 	id: 'iframeAttach_' + this.options['sessionKey'],
		 										name: 'iframeAttach_' + this.options['sessionKey'],
												scrolling: 'no',
												frameBorder: 'no',
												border: '0',
												className: '',
												allowTransparency: true,
												src: this.options.upload_url,
												tabindex: '1'
											}).setStyle({
												width: '410px',
												height: '40px',
												overflow: 'hidden',
												float: 'left',
												backgroundColor: 'transparent'
											});
											
		$('buttonPlaceholder').insert( { after: this.iframe } ).addClassName('traditional');
		
		$('add_files_' + this.id ).observe('click', this.processUpload.bindAsEventListener( this ) );
		$('space_info_' + this.id ).setStyle( 'clear: both' );
	},
	
	removeUpload: function( attachid, fileindex, fileinfo )
	{
		if( attachid.startsWith('cur_') ){
			fileindex = 'cur_' + fileindex;
		}
		
		// Remove box
		new Effect.Fade( $( this.boxes[ fileindex ] ), { duration: 0.4 } );
		
		// Remove reference
		this.boxes[ fileindex ] = null;
		
		// Update totals
		if( $('space_info_' + this.id ) && ipb.lang['used_space'] )
		{
			$('space_info_' + this.id ).update( ipb.lang['used_space'].gsub(/\[used\]/, fileinfo['upload_stats']['space_used_human']).gsub(/\[total\]/, fileinfo['upload_stats']['total_space_allowed_human']) );
		}
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", removeUpload) Attach ID: " + attachid + ", File index: " + fileindex );
	},
	
	/**
	* Processes upload
	*/
	processUpload: function( e )
	{
		$('attach_error_box').hide();
		var iFrameBox  = window.frames[ 'iframeAttach_' + this.options['sessionKey'] ].document.getElementById('iframeUploadBox');
		var iFrameForm = window.frames[ 'iframeAttach_' + this.options['sessionKey'] ].document.getElementById('iframeUploadForm');
		var box        = $('attach_' + id );
		
		window.frames[ 'iframeAttach_' + this.options['sessionKey'] ].document.getElementById('waitImg').style.display = 'inline-block';
		
		Debug.write( "Uploading");
		
		iFrameForm.action = ipb.vars['base_url'] + "app=gallery&module=post&section=image&do=uploadSave&type=album&album_id=" + ipb.uploader.getCurrentAlbumId() + "&sessionKey=" + this.options['sessionKey'] + "&fetch_all=1";
		
		iFrameForm.submit();
	},
	
	/**
	* Iframe is ready
	*/
	isReady: function()
	{
		if ( this.json )
		{
			if ( this.json['is_error'] )
			{
				$('attach_error_box').update( ipb.lang['error'] + " <strong>" + ipb.uploader._determineServerError( this.json['msg'] ) + "</strong>" );
				$('attach_error_box').show();
			}
			
			if ( this.json['current_items'] )
			{
				new Effect.Fade( $('help_msg') );
				
				$( this.wrapper ).update();
				ipb.uploader._buildBoxes( this.json['current_items'] );
			}
			
			/*if ( typeof( this.json['attach_rel_id']) !='undefined' )
			{
				if( $('space_info_uploader_' + this.json['attach_rel_id'] ) )
				{
					$('space_info_uploader_' + this.json['attach_rel_id'] ).update( ipb.lang['used_space'].gsub(/\[used\]/, this.json['upload_stats']['space_used_human']).gsub(/\[total\]/, this.json['upload_stats']['total_space_allowed_human']) );
				}
			}*/
		}
		
		Debug.write( "ipb.gallery_uploader.js: iFrame is ready" );
	},
	
	_setJSON: function( id, json )
	{
		Debug.dir( json );
		Debug.write( "ipb.gallery_uploader.js: Got JSON from the iFrame" );
	}
});

//==============================================================
/* ! swf */
_uploader.prototype.attachSWF = Class.create({
	options: [],
	boxes: [], /* simple array to hold file index => list id relationship, because im lazy :) */
	
	initialize: function( id, options, wrapper, template )
	{
		this.id = id;
		this.wrapper = wrapper;
		this.template = template;
		
		this.options = Object.extend({
			upload_url: 				'',
			file_post_name: 			'FILE_UPLOAD',
			file_types:					'*.*',
			file_types_description:		ipb.lang['att_select_files'],
			file_size_limit: 			"10 MB",
			file_upload_limit: 			0,
			file_queue_limit: 			100,
			flash_color: 				ipb.vars['swf_bgcolor'] || '#FFFFFF',
			custom_settings: 			{},
			post_params: 				{ 's': ipb.vars['session_id'] }
		}, arguments[1] || {});
		
		if( this.options.upload_url.blank() ){ Debug.error( "(ID " + id + ") No upload URL" ); return false; }
		
		// Update the text of the button to indicate more than one can be attached
		try {
			$('add_files_' + this.id).value = ipb.lang['click_to_attach'];
		} catch(err) {
			Debug.write( err );
		}
		
		// Set up SWFU
		try {
			var swfu;
			
				var settings = {
					upload_url: 			this.options.upload_url,
					flash_url: 				ipb.vars['swfupload_swf'],
					file_post_name: 		this.options.file_post_name,
					file_types: 			this.options.file_types,
					file_types_description: this.options.file_types_description,
					file_size_limit: 		this.options.file_size_limit,
					file_upload_limit:  	this.options.file_upload_limit,
					file_queue_limit: 		this.options.file_queue_limit,
					custom_settings: 		this.options.custom_settings,
					post_params: 			this.options.post_params,
					debug: 					ipb.vars['swfupload_debug'],
					
					// ---- BUTTON SETTINGS ----
					button_placeholder_id: 			'buttonPlaceholder',
					button_width: 					$('add_files_' + this.id).getWidth(),
					button_height: 					30,
					button_window_mode: 			SWFUpload.WINDOW_MODE.TRANSPARENT,
					button_cursor: 					SWFUpload.CURSOR.HAND,
				
					// ---- EVENTS ---- 
					upload_error_handler: 			this._uploadError.bind(this),
					upload_start_handler: 			this._uploadStart.bind(this),
					upload_success_handler: 		this._uploadSuccess.bind(this),
					upload_complete_handler: 		this._uploadComplete.bind(this),
					upload_progress_handler: 		this._uploadProgress.bind(this),
					file_dialog_complete_handler: 	this._fileDialogComplete.bind(this),
					file_queue_error_handler: 		this._fileQueueError.bind(this),
					queue_complete_handler: 		this._queueComplete.bind(this),
					file_queued_handler: 			this._fileQueued.bind(this)
				};
				Debug.dir( settings );
			swfu = new SWFUpload( settings );
			
			this.obj = swfu;
			
			// Now we have to get existing files
			var getExisting	=	ipb.vars['base_url'] + "app=gallery&module=ajax&section=image&do=fetchUploads&sessionKey=" +
 				   				options['sessionKey'] + "&album_id=" + ipb.uploader.getCurrentAlbumId() + '&secure_key=' + ipb.vars['secure_hash'] + '&fetch_all=1';
			Debug.write(getExisting);
			// Send request to get the uploads
			new Ajax.Request( getExisting,
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }

									if( t.responseJSON.current_items )
									{
										ipb.uploader._buildBoxes( t.responseJSON.current_items );
									}
								}
							});

			this.obj.onmouseover	= $('SWFUpload_0').focus();

			Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ") Created uploader");
			return true;
		}
		catch(e)
		{
			Debug.error( "ipb.gallery_uploader.js: (ID " + this.id + ") " + e );
			return false;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Removes an upload from the list (note: actual removing of file is done in the wrapper object
	 * 
	 * @param	{int}	attachid		Server ID of the attachment
	 * @param	{int}	fileindex		The file index that SWFU is using
	*/
	removeUpload: function( attachid, fileindex, fileinfo )
	{
		if( attachid.startsWith('cur_') ){
			fileindex = 'cur_' + fileindex;
		}
		
		// Remove box
		new Effect.Fade( $( this.boxes[ fileindex ] ), { duration: 0.4 } );
		
		// Remove reference
		this.boxes[ fileindex ] = null;
		
		if( $('space_info_' + this.id ) && ipb.lang['used_space'] )
		{
			$('space_info_' + this.id ).update( ipb.lang['used_space'].gsub(/\[used\]/, ipb.global.convertSize( fileinfo['upload_stats']['used'] ) ).gsub(/\[total\]/, fileinfo['upload_stats']['maxTotalHuman']) );
		}
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", removeUpload) Attach ID: " + attachid + ", File index: " + fileindex );
	},
	
	/* ------------------------------ */
	/**
	 * Updates the info string for an upload
	 * 
	 * @param	{object}	file		The file object from SWFU
	 * @param	{string}	msg			The message to set
	*/
	_updateInfo: function( file, msg )
	{
		$( this.boxes[ file ] ).select('.info')[0].update( msg );
	},
	
	/* ------------------------------ */
	/**
	 * Sets a CSS class on the box depending on status
	 * 
	 * @param	{object}	file		The file object from SWFU
	 * @param	{string}	type		Status to set
	*/
	_setStatus: function( file, type )
	{
		// Remove old statuses
		['complete', 'in_progress', 'error'].each( function( cName ){ $( this.boxes[ file ] ).removeClassName( cName ); }.bind( this ) );
		
		$( this.boxes[ file ] ).addClassName( type );
	},
	
	/* ------------------------------ */
	/**
	 * Returns a human-readable string for errors
	 * 
	 * @param	{string}	msg		The msg code
	 * @return	{string}			The human-readable message
	*/
	_determineServerError: function( msg )
	{
		if( msg.blank() ){ return ipb.lang['silly_server']; }
		
		if( !Object.isUndefined( ipb.lang[ msg ] ) )
		{
			return ipb.lang[ msg ];
		}
		else
		{
			return ipb.lang['silly_server'];
		}
	},
	
	/* ------------------------------ */
	/**
	 * Builds the list row for each upload
	 * 
	 * @param	{object}	file	The file object passed from SWFU
	*/
	_buildBox: function( file )
	{
		temp = uploader.template.gsub(/\[id\]/, this.id + '_' + file.index).gsub(/\[name\]/, file.name);
		this.boxes[ file.index ] = 'ali_' + this.id + '_' + file.index;
		
		$( this.wrapper ).insert( temp );
		
		new Effect.Appear( $( this.boxes[ file.index ] ), { duration: 0.3 } );
		this._updateInfo( file.index, ipb.global.convertSize( file.size ) + "bytes" );
	},
	
	_queueComplete: function( numFiles )
	{
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", " + numFiles + " finished uploading");
	},
	
	_fileQueued: function( file )
	{
		this._buildBox( file );
		$( this.boxes[ file.index ] ).addClassName('in_progress');
		this._updateInfo( file.index, ipb.lang['pending'] );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for fileQueueError
	 * 
	 * @param	{object}	file			The file object
	 * @param	{int}		errorCode		Error code
	 * @param	{string}	message			Message from SWFUpload
	*/
	_fileQueueError: function( file, errorCode, message )
	{
		var msg;
		
		try {
			if( errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED ){
				alert( ipb.lang['upload_queue'] + ' ' + message );
				return false;
			}
		
			switch (errorCode) {
				case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
					msg = ipb.lang['upload_too_big'];
					break;
				case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
					msg = ipb.lang['upload_no_file'];
					break;
				case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
					msg = ipb.lang['invalid_mime_type'];
					break;
				default:
					if( file !== null ) {
						msg = ipb.lang['upload_failed'] + " " + errorCode;
					}
					break;
			}

			// Have to manually build box
			this._buildBox( file );
			this._setStatus( file.index, 'error' );
			this._updateInfo( file.index, ipb.lang['upload_skipped'] + " (" + msg + ")" );
			$( this.boxes[ file.index ] ).select('.progress_bar')[0].hide();
			$( this.boxes[ file.index ] ).select('.links')[0].hide();

			
			Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", fileQueueError) " + errorCode + ": " + message );
		}
		catch( err )
		{
			Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", fileQueueError) " + errorCode + ": " + message );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadError
	 * 
	 * @param	{object}	file			The file object
	 * @param	{int}		errorCode		The error code returned
	 * @param	{string}	message			The message returned
	*/
	_uploadError: function( file, errorCode, message )
	{
		var msg;

		switch( errorCode )
		{
			case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
				msg = ipb.lang['error'] + message;
				break;
			case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
				msg = message;
				break;
			case SWFUpload.UPLOAD_ERROR.IO_ERROR:
				msg = ipb.lang['error'] + " IO";
				break;
			case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
				msg = ipb.lang['error_security'];
				break;
			case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
				msg = ipb.lang['upload_limit_hit'];
				break;
			case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
				msg = ipb.lang['invalid_mime_type'];
				break;
			default:
				msg = ipb.lang['error'] + ": " + errorCode;
				break;
		}
		
		this._setStatus( file.index, 'error' );
		this._updateInfo( file.index, ipb.lang['upload_skipped'] + " (" + msg + ")" );
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", uploadError) " + errorCode + ": " + message );
		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadStart
	 * 
	 * @param	{object}	file			The file object
	*/
	_uploadStart: function( file )
	{
		ipb.uploader.startedUploading( this.id );
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", uploadStart) " );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadSuccess
	 * 
	 * @param	{object}	file			The file object
	 * @param	{string}	serverData		Data from the server (should be JSON)
	*/
	_uploadSuccess: function( file, serverData )
	{
		if( !serverData.isJSON() ){ this._setStatus( file.index, 'error' ); this._updateInfo( file.index, ipb.lang['silly_server'] ); }
		returnedObj = serverData.evalJSON();
		
		if( Object.isUndefined( returnedObj ) ){ this._setStatus( file.index, 'error' ); this._updateInfo( file.index, ipb.lang['silly_server'] ); }
		
		// Error?
		if( returnedObj.is_error == 1 )
		{
			msg = this._determineServerError( returnedObj.msg );
			this._setStatus( file.index, 'error' );
			this._updateInfo( file.index, msg );
			return false;
		}
			
		if ( $('space_info_' + this.id ) && ipb.lang['used_space'] )
		{
			$('space_info_' + this.id ).update( ipb.lang['used_space'].gsub(/\[used\]/, ipb.global.convertSize( returnedObj.upload_stats.used ) ).gsub(/\[total\]/, returnedObj.upload_stats.maxTotalHuman) );
		}
		
		// IMAGE RESIZING
		if( returnedObj.current_items[ returnedObj.insert_id ][ 3 ] == 1 )
		{
			tmp = returnedObj.current_items[ returnedObj.insert_id ];
			
			var width = tmp[5];
			var height = tmp[6];
			
			if( ( tmp[5] && tmp[5] > ipb.uploader.thumbSize ) )
			{
				width = ipb.uploader.thumbSize;
				factor = ( ipb.uploader.thumbSize / tmp[5] );
				height = tmp[6] * factor;
			}
			
			if( ( tmp[6] && tmp[6] > ipb.uploader.thumbSize ) )
			{
				height = ipb.uploader.thumbSize;
				factor = ( ipb.uploader.thumbSize / tmp[5] );
				width = tmp[5] * factor;
			}
		
			thumb = new Element('div').update( tmp[4] ).hide();
				
			$( this.boxes[ file.index ] ).select('.img_holder')[0].insert( thumb );
			new Effect.Appear( $( thumb ), { duration: 0.4 } );
		}
		
		// SET STATUS & INFO
		this._setStatus( file.index, 'complete' );
		this._updateInfo( file.index, ipb.lang['upload_done'].gsub( /\[total\]/, ipb.global.convertSize( file.size ) ) );
		
		// Write attachID to the object for easy retreival later
		$( this.boxes[ file.index ] ).writeAttribute( 'attachid', returnedObj.insert_id );
		
		// Add event handlers
		$( this.boxes[ file.index ] ).select('.delete')[0].observe( 'click', ipb.uploader.removeUpload );
		
		ipb.uploader.finishedUploading( this.id, file.index );
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", uploadSuccess) " + serverData );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadComplete
	 * 
	 * @param	{object}	file			The file object
	*/
	_uploadComplete: function( file )
	{
		ipb.uploader.finishedUploading( this.id );
		
		progress_bar = $( this.boxes[ file.index ] ).select('.progress_bar span')[0];
		progress_bar.setStyle( "width: 100%" );
		new Effect.Fade( $( this.boxes[ file.index ] ).select('.progress_bar')[0], { duration: 0.6 } );
				
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", uploadComplete)" );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadProgress
	 * 
	 * @param	{object}	file			The file object
	 * @param	{int}		bytesLoaded		Number of bytes loaded so far
	 * @param	{int}		bytesTotal		Total size of file
	*/
	_uploadProgress: function( file, bytesLoaded, bytesTotal)
	{
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
		
		progress_bar = $( this.boxes[ file.index ] ).select('.progress_bar span')[0];
		progress_bar.setStyle( "width: " + percent + "%" ).update( percent + "%" );
		
		this._setStatus( file.index, 'in_progress' );
		this._updateInfo( file.index, ipb.lang['upload_progress'].gsub( /\[done\]/, ipb.global.convertSize( bytesLoaded ) ).gsub( /\[total\]/, ipb.global.convertSize( bytesTotal ) ) );
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", uploadProgress)" );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for fileDialogComplete (called when used finishes selecting files
	 * 
	 * @param	{int}		number			Number of files selected
	 * @param	{int}		queued			Number in the queue
	*/
	_fileDialogComplete: function( number, queued )
	{
		Debug.write( this.options.upload_url.replace( /album_id=(\d+?)?(&|$)/, 'album_id=' + ipb.uploader.getCurrentAlbumId() + '&' ) );
		
		/* ensure album Id is corect */
		this.obj.setUploadURL( this.options.upload_url.replace( /album_id=(\d+?)?(&|$)/, 'album_id=' + ipb.uploader.getCurrentAlbumId() + '&' ) );
		
		Debug.write( "ipb.gallery_uploader.js: (ID " + this.id + ", fileDialogComplete) Number: " + number + ", Queued: " + queued );
		this.obj.startUpload();
	}
	
	/* ------------------------------ */
	/**
	 * Event handler for the Add Files button
	 * 
	 * @param	{event}		e		The event
	*/
	/*showFilesDialog: function(e)
	{
		Debug.write( "ipb.gallery_uploader.js: Adding files" );
		this.obj.selectFile();
	}*/
});

/**
 * Copyright (c) 2007 - 2009, James Auldridge
 * All rights reserved.
 *
 * Licensed under the BSD License:
 *  http://www.opensource.org/licenses/bsd-license.php
 *
 */
var jaaulde = window.jaaulde || {};
jaaulde.utils = jaaulde.utils || {};
jaaulde.utils.flashsniffer = ( function()
{
	var lastMajorRelease = 10;
	var installed = false;
	var version = null;
	
	( function()
	{
		var fp, fpd, fAX;
		if( navigator.plugins && navigator.plugins.length )
		{
			fp = navigator.plugins['Shockwave Flash'];
			if( fp )
			{
				if( fp.description )
				{
					fpd = fp.description;
					try
					{
						version = fpd.match(/(\d+)\./)[1];
					}
					catch( e1 )
					{
					}

					if( ! isNaN( version ) )
					{
						installed = true;
					}
				}
			}
			else if( navigator.plugins['Shockwave Flash 2.0'] )
			{
				installed = true;
				version = 2;
			}
		}
		else if( navigator.mimeTypes && navigator.mimeTypes.length )
		{
			fp = navigator.mimeTypes['application/x-shockwave-flash'];
			if( fp && fp.enabledPlugin )
			{
				installed = true;
			}
		}
		else
		{
			for( var i = lastMajorRelease; i >= 2; i-- )
			{
				try
				{
					fAX = new ActiveXObject( 'ShockwaveFlash.ShockwaveFlash.' + i );
					installed = true;
					version = i;
					break;
				}
				catch( e2 )
				{
				}
			}
			if( ! installed )
			{
				try
				{
					fAX = new ActiveXObject( 'ShockwaveFlash.ShockwaveFlash' );
					installed = true;
					version = 2;
				}
				catch( e3 )
				{
				}
			}
			//Clean up vars as some of the above could have led to istantiated ActiveXObjects
			fp = null;
			fpd = null;
			fAX = null;
			delete fp;
			delete fpd;
			delete fAX;
		}
	} )();

	//Public Interface
	return {
		/* installed - Determine if Flash is installed
		 * 
		 * @return BOOL true if Flash is installed, false if not
		 */
		installed: function()
		{
			return !! installed;
		},
		/* version - Get version of installed Flash
		 * 
		 * @return INT if Flash is installed and properly detected, else NULL
		 */
		version: function()
		{
			return version;
		},
		/* isLatestVersion - Determine if Flash is installed the latest released version of Flash
		 *                   (as configured in property 'lastMajorRelease' documented above)
		 * 
		 * @return BOOL true if Flash is at latest version, false if not
		 */
		isLatestVersion: function()
		{
			return ( !! installed && version == lastMajorRelease );
		},
		/* isVersion - Determine if Flash is installed at a specified major version
		 * 
		 * @parameter INT exactVersion the exact version for which to check
		 * @return BOOL true if Flash is at specified version, false if not
		 */
		isVersion: function( exactVersion )
		{
			return ( !! installed && version == exactVersion );
		},
		/* meetsMinVersion - Determine if Flash is installed at a specified minimum version
		 * 
		 * @parameter INT minVersion the version number that installed Flash should be at OR above
		 * @return BOOL true if Flash is at minimum version, false if not
		 */
		meetsMinVersion: function( minVersion )
		{
			return ( !! installed && version !== null && version >= minVersion );
		}
	};
} )();
