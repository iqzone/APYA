/**
 * Wrapper for CKEditor based goodness
 * Written by Matt Mecham for his sins.
 * (c) 2011 IPS, Inc.
 */

ipsTextArea = {
	/**
	 * Insert text at the cursor position
	 * Some code from
	 * @link http://bytes.com/topic/javascript/answers/149268-moving-cursor-end-textboxes-text
	 * @param editorId
	 * @param text
	 */
	insertAtCursor: function( editorId, text )
	{
		var te		  = $('cke_' + editorId).down('textarea');
		
		var scrollPos = te.scrollTop;
		
		if ( CKEDITOR.env.ie )
		{
			te.focus();
			sel = document.selection.createRange();
			sel.text = text;
			sel.select();
		}
		else if ( te.selectionStart || te.selectionStart == '0' )
		{
			var startPos = te.selectionStart;
			var endPos   = te.selectionEnd;
			
			te.value = te.value.substring(0, startPos) + text + te.value.substring(endPos, te.value.length);
			
			if ( startPos == endPos )
			{
				this.setSelectionRange( te, startPos + text.length, endPos + text.length );
			}
			else
			{
				this.setCaretToPos( te, startPos + text.length );
			}		
		}
		else
		{
			te.value += text;
		}
		
		te.scrollTop = scrollPos;
	},
	
	/**
	 * Set selection range
	 * Some code from
	 * @link http://bytes.com/topic/javascript/answers/149268-moving-cursor-end-textboxes-text
	 */
	setSelectionRange: function(input, selectionStart, selectionEnd)
	{
		if ( input.setSelectionRange )
		{
			input.focus();
			input.setSelectionRange(selectionStart, selectionEnd);
		}
		else if ( input.createTextRange )
		{
			var range = input.createTextRange();
			range.collapse(true);
			range.moveEnd('character', selectionEnd);
			range.moveStart('character', selectionStart);
	    	range.select();
		}
	},

	/**
	 * Set caret position
	 * Some code from
	 * @link http://bytes.com/topic/javascript/answers/149268-moving-cursor-end-textboxes-text
	 */
	setCaretToPos: function( input, pos )
	{
		this.setSelectionRange( input, pos, pos );
	}	
}

IPSCKTools = {
	/**
	 * Get the selected HTML from the editor
	 * @param	object
	 */
	getSelectionHtml: function( editor )
	{
		var selection = editor.getSelection();
		
		if ( CKEDITOR.env.ie )
		{
			try {
				if ( ! Prototype.Browser.IE8 )
				{
					selection.unlock(true);
				}
			} catch(e){}
			
			var text = selection.getNative().createRange().htmlText;
		
			if ( text.toLowerCase().strip() == '<p>&nbsp;</p>' )
			{
				return false;
			}
			
			return text;
		}
		else if ( CKEDITOR.env.opera )
		{
			var selection = selection.getNative();
		
			var range = selection ? selection.getRangeAt(0) : selection.createRange();
			var div   = document.createElement('div');
			div.appendChild( range.cloneContents() );
			
			return div.innerHTML.replace( /<p><\/p>/g, '<p><br /></p>' );
		}
		else
		{
			var range = selection.getNative().getRangeAt( selection.rangeCount -1 ).cloneRange();
			var div   = document.createElement('div');
			div.appendChild( range.cloneContents() );
			
			return div.innerHTML;
		}
	},
	/**
	 * Clean HTML for inserting between tags
	 * @param	object
	 */
	cleanHtmlForTagWrap: function( html, convert )
	{
		var text = ( typeof( html ) != 'undefined' ) ? html.replace( /<br( \/)?>$/, '' ) : '';
		
		/* The text may have become double encoded */
		if ( convert )
		{
			text = text.replace( /&lt;/g  , '<' );
			text = text.replace( /&gt;/g  , '>' );
			text = text.replace( /&amp;/g , '&' );
			text = text.replace( /&#39;/g , "'" );
			text = text.replace( /&quot;/g, '"' );
		}
		
		return text;
	},
	
	/**
	 * Remove HTML formatting
	 * @param string
	 */
	stripHtmlTags: function( html )
	{
		return html.stripTags();
	}
};

/*
 * This is just a wrapper class around the objects to give it a global interface
 */
IPBoard.prototype.textEditor = {
		
	mainStore: $H(),
	lastSetUp: null,
	htmlCheckbox: $H(),
	_tmpContent: '',
	IPS_TEXTEDITOR_POLLING:	10000,			/* 10 seconds */
	IPS_NEW_POST_POLLING:	(2 * 60000),	/* 2 mins */
	IPS_SAVED_MSG:			"<a href='javascript:void()' class='_as_explain desc'>" + ipb.lang['ck_auto_saved'] + "</a>",
	IPS_AUTOSAVETEMPLATE:	"<a href='javascript:void()' class='_as_launch desc'>" + ipb.lang['ck_view_saved'] + "</a>",
	IPS_AUTOSAVEVIEW:		"<div><h3>" + ipb.lang['ck_saved'] + "</h3><div class='row2' style='padding:4px'><div class='as_content'>#{content}</div><div class='as_buttons'><input type='button' class='input_submit _as_restore' value='" + ipb.lang['ck_restore'] + "' /></div>",
	IPS_AUTOSAVEEXPLAIN:	"<div><h3>" + ipb.lang['ck_saved_title'] + "</h3><div class='row2' style='padding:8px'>" + ipb.lang['ck_saved_desc'] + "</div>",
	ajaxUrl:				'',
	
	initialize: function( editorId, options )
	{
		if ( inACP )
		{
			ipb.textEditor.ajaxUrl  = ipb.vars['front_url']; 
			ipb.vars['secure_hash'] = ipb.vars['md5_hash'];
		}
		else
		{
			ipb.textEditor.ajaxUrl  = ipb.vars['base_url'];
		}
		
		/* Insert into main store */
		if ( ! ipb.textEditor.mainStore.get( editorId ) )
		{
			newEditorObject = new ipb.textEditorObjects( editorId, options );
			
			ipb.textEditor.mainStore.set( editorId, newEditorObject );
		}
		
		/* Set up */
		ipb.textEditor.lastSetUp = editorId;
	},
	
	/**
	 * Bind a HTML checkbox to the toggle stuffs
	 */
	bindHtmlCheckbox: function( elem, editorId )
	{
		editorId = ( editorId ) ? editorId : ipb.textEditor.getEditor().editorId;
		
		if ( $( elem ) )
		{
			ipb.textEditor.htmlCheckbox[ editorId ] = $( elem );
			
			$( elem ).writeAttribute( 'data-editorId', editorId );
			$( elem ).observe('change', ipb.textEditor.htmlModeToggled.bindAsEventListener( this, $( elem ) ) );
		}
	},
	
	/**
	 * HTML mode has been toggled so we force STD mode
	 */
	htmlModeToggled: function( e, elem )
	{
		textObj = ipb.textEditor.getEditor( $( elem ).readAttribute( 'data-editorId' ) );
		isRte   = textObj.isRte();
		button  = $( 'cke_' + textObj.editorId ).down('.cke_button_ipsswitch');
		
		if ( elem.checked )
		{
			$('editor_html_message_' + textObj.editorId).show();
			
			if ( isRte )
			{
				button.hide();
				
				ipb.textEditor.switchEditor(true, textObj.editorId);
			}
			else
			{
				button.hide();
			}
		}
		else
		{
			$('editor_html_message_' + textObj.editorId).hide();
			
			if ( ! button.visible() )
			{
				button.show();
			}
		}
	},

	/**
	 * Switches an editor from rte to std or vice-versa
	 */
	switchEditor: function( noSaveChange, editorId  )
	{
		editorId     = ( editorId ) ? editorId : ipb.textEditor.getEditor().editorId;
		textObj      = ipb.textEditor.getEditor( editorId );
		isRte        = textObj.isRte();
		ourcontent   = textObj.getText();
		saveCookie   = ( noSaveChange ) ? false : true;
		htmlOps      = null;
		noSmilies    = 0;
		
		if ( $( 'noSmilies_' + editorId ) )
		{
			noSmilies = parseInt( $F( 'noSmilies_' + editorId ) );
		}
		
		if ( $( ipb.textEditor.htmlCheckbox[ editorId ] ) )
		{
			htmlOps = ( ipb.textEditor.htmlCheckbox[ editorId ].checked ) ? 1 : 0;
		}
		
		var _url  = ipb.textEditor.ajaxUrl + '&app=core&module=ajax&section=editor&do=switch&secure_key=' + ipb.vars['secure_hash'];
		
		if ( htmlOps !== null )
		{
			_url += '&htmlStatus=' + htmlOps;
		}
		
		Debug.write( _url );
		Debug.write( 'Fetched editor content: ' + ourcontent );

		new Ajax.Request( _url,
							{
								method: 'post',
								parameters: { 'content'   : ourcontent.encodeParam(),
											  'isRte'     : isRte,
											  'noSmilies' : noSmilies },
								onSuccess: function(t)
								{
									/* No Permission */
									if ( t.responseText != 'nopermission' )
									{
										Debug.write( 'Converted to: ' + t.responseText );
										
										/* Switch the text editor around */
										if ( textObj.isRte() )
										{
											/* Reset flag */
											textObj.setIsRte( false, noSaveChange );
											
											textObj.CKEditor.setMode( 'source' );
											
											/* Update */
											$( textObj.editorId ).value = ipb.textEditor.stdPre( t.responseText );
											
											try
											{
												Debug.write( $F( textObj.editorId ) );
												textObj.CKEditor.setData( $F( textObj.editorId ) );
											}
											catch( err )
											{
												Debug.write( "CKEditor error: " + err );
											}
											
											if ( saveCookie )
											{
												ipb.Cookie.set( 'rteStatus', 'std' );
											}
											
											/* Update text-direction, since CKE forces LTR in 'source' mode */
											if( isRTL )
											{
												$$('.cke_contents > textarea').each( function(elem) {
													$(elem).setStyle( { textAlign: 'right' } );
												});
											}
											
											if ( $('cke_' + textObj.editorId + '_stray') )
											{
												$('cke_' + textObj.editorId + '_stray').remove();
												
												if ( $('ips_x_smile_show_all') )
												{
													$('ips_x_smile_show_all').remove();
												}
											}
										}
										else
										{
											/* Reset flag */
											textObj.setIsRte( true, noSaveChange );
											
											try
											{
												textObj.CKEditor.setData( ipb.textEditor.ckPre( t.responseText ) );
											}
											catch( err )
											{
												Debug.write( "CKEditor error: " + err );
											}

											/* Init editor */
											textObj.CKEditor.setMode( 'wysiwyg' );
											
											if ( saveCookie )
											{
												ipb.Cookie.set( 'rteStatus', 'rte' );
											}
										}
									}
								}.bind(this),
								onException: function (t)
								{
									Debug.dir( t );
								}
							} );
		
	},
	
	/*
	 * Change all editor modes besides the current one
	 * (current one is changed inside switchEditor)
	 */
	toggleEditors: function( mode )
	{
		return false;
		
		/* We no longer use this as it doesn't ping ajax to convert! */
		ipb.textEditor.mainStore.each( function( instance ){ 
			if( instance.key != ipb.textEditor.lastSetUp )
			{
				_editor = ipb.textEditor.getEditor( instance.key );
				
				if ( ! _editor.options.isHtml )
				{
					if( mode == 'std' )
					{
						instance.value.setIsRte( false );
						instance.value.CKEditor.setMode( 'source' );
						
						if ( $('cke_' + _editor.editorId + '_stray') )
						{
							$('cke_' + _editor.editorId + '_stray').remove();
							
							if ( $('ips_x_smile_show_all') )
							{
								$('ips_x_smile_show_all').remove();
							}
						}
					}
					else
					{
						instance.value.setIsRte( true );
						instance.value.CKEditor.setMode( 'wysiwyg' );
					}
				}
			}
		});
	},
	
	/*
	 * Fetches an editor by ID
	 */
	getEditor: function( editorId )
	{
		editorId = ( ! editorId ) ? ipb.textEditor.getCurrentEditorId() : editorId;

		return ipb.textEditor.mainStore.get( editorId );
	},
	
	/**
	 * Fetches the current focused editor or the last one set up
	 * @returns string	editor id
	 */
	getCurrentEditorId: function()
	{
		if ( typeof(CKEDITOR) == 'undefined' || Object.isUndefined( CKEDITOR ) )
		{
			return ipb.textEditor.lastSetUp;
		}
		else
		{
			if ( CKEDITOR.currentInstance && ipb.textEditor.mainStore.get( CKEDITOR.currentInstance.name ).CKEditor )
			{
				return CKEDITOR.currentInstance.name;
			}
		    else
		    {
		    	return ipb.textEditor.lastSetUp;
		    }
		}
	},
	
	/**
	 * Make safe for CKEditor
	 */
	ckPre: function( text )
	{
		text = text.replace( '<script', '&#60;script' );
		text = text.replace( '</script', '&#60;/script' );
		
		// If the first content is text and not an HTML element, CKEditor throws an error
		// Error: body.getFirst().hasAttribute is not a function
		// Similar to: http://dev.ckeditor.com/ticket/6152
		// We'll just wrap in a span tab to be safe if it doesn't look like HTML is there
		if( text.charAt(0) != '<' )
		{
			// Disabled as we're not using BR mode
			//text = '<p>' + text + '</p>';
			
		}
		
		return text;
	},
	
	/**
	 * Make safe for std editor
	 */
	stdPre: function( text )
	{
		/* &lt; / &gt; is made safe by CKEditor */
		text = text.replace( /&lt;/g, '<' );
		text = text.replace( /&gt;/g, '>' );
		
		/* Properly encoded HTML &amp;#39; isn't parsed in the text area */
		text = text.replace( /&amp;/g, '&' );
		
		if ( ! $(ipb.textEditor.htmlCheckbox) || ! $(ipb.textEditor.htmlCheckbox).checked )
		{
			//text = text.replace( /<br([^>]+?)?>(\n)?/g, '\n' );
		}

		return text;
	},
	
	/**
	 * 0 pad times
	 * @param n
	 * @returns
	 */
	pad: function(n)
	{
		return ("0" + n).slice(-2);
	}
};

/*
 * Each CKEditor object is referenced via this class
 */
IPBoard.prototype.textEditorObjects = Class.create( {
	
	editorId: {},
	popups: [],
	cookie: 'rte',
	timers: {},
	options: {},
	CKEditor: null,
	EditorObj: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	initialize: function( editorId, options )
	{
		this.editorId = editorId;
		this.cookie   = ipb.Cookie.get('rteStatus');
		
		this.options  = Object.extend( { type: 	              'full',
										 ips_AutoSaveKey:      false,
										 height:			   0,
										 minimize:             0,
										 minimizeNowOpen:      0,
										 isRte:				   1,
										 isHtml: 			   0,
										 bypassCKEditor:	   0,
										 isTypingCallBack:     false,
										 delayInit:			   false,
										 noSmilies:			   false,
										 ips_AutoSaveData:     {},
										 ips_AutoSaveTemplate: new Template( ipb.textEditor.IPS_AUTOSAVETEMPLATE ) }, arguments[1] );
		
		/* Force */
		if ( this.options.type == 'ipsacp' )
		{
			this.setIsRte( true );
		}
		else if ( this.options.isRte == 0 )
		{
			this.setIsRte( false );
		}
		else if ( this.options.isRte == 1 )
		{
			this.setIsRte( true );
		}
		else
		{
			/* Do we have an override? */
			this.setIsRte( this.cookie == 'rte' ? 1 : ( this.cookie == 'std' ? 0 : 1 ) );
		}
		
		/* Force STD if iDevice */
		if ( ipb.vars['is_touch'] !== false )
		{
			this.setIsRte( 0 );
			this.options.bypassCKEditor = 1;
		}
	
		/* Create the CKEditor */
		if ( ! this.options.delayInit )
		{
			this.initEditor();
		}
	},
	
	/**
	 * Set RTe status of current editor
	 */
	setIsRte: function( value, noSaveChange )
	{
		value      = ( value ) ? 1 : 0;
		saveCookie = ( noSaveChange ) ? false : true;
		
		this.options.isRte = value;
		
		if ( $( 'isRte_' + this.editorId ) )
		{
			 $( 'isRte_' + this.editorId ).value = value;
		}
		
		/* Set cookie */
		if ( saveCookie )
		{
			ipb.Cookie.set( 'rteStatus', ( value ) ? 'rte' : 'std', 1 );
		}
	},
	
	/**
	 * Returns whether current editor is RTE (ckeditor)
	 */
	isRte: function()
	{
		return this.options.isRte ? 1 : 0;
	},
	
	/**
	 * Fetch editor contents 
	 */
	getText: function()
	{
		var val = '';
		
		if ( ! this.options.bypassCKEditor && this.CKEditor )
		{
			val = this.CKEditor.getData();
		}
		else
		{
			/* If CKEditor isn't beingn used (iOS, etc) */
			if( $( this.editorId ) )
			{
				val = $( this.editorId ).value;
			}
		}
		
		return val;	
	},
	
	/**
	 * Create the CKEditor
	 */
	initEditor: function(initContent)
	{
		/* Bypassing the CKEditor completely? Why so mean? */
		if ( this.options.bypassCKEditor )
		{
			if ( this.options.minimize )
			{
				this.options.minimizeNowOpen = 1;
			}
			
			if ( typeof( initContent ) == 'string' )
			{
				$( this.editorId ).value = initContent;
			}

			return;
		}
		
		/* Start the process of initiating */
		if ( $( this.editorId ).value && this.isRte() )
		{
			$( this.editorId ).value = ipb.textEditor.ckPre( $( this.editorId ).value );
		}
		else
		{				
			$( this.editorId ).value = ipb.textEditor.stdPre( $( this.editorId ).value );			
		}
		
		/* RTE init */
		try
		{
			var config	= {
							toolbar:			( this.options.type == 'ipsacp' ) ? 'ipsacp' : ( this.options.type == 'mini' ? 'ipsmini' : 'ipsfull' ),
							height:				( Object.isNumber( this.options.height ) && this.options.height > 0 ) ? this.options.height : ( this.options.type == 'mini' ? 150 : 300 ),
						    ips_AutoSaveKey:	this.options.ips_AutoSaveKey
			};

			/* STD editor? */
			if ( ! this.isRte() )
			{
				config.startupMode	= 'source';
			}
			
			/* Minimized - force ipsmini */
			if ( this.options.minimize )
			{
				config.toolbarStartupExpanded = false;
			}
			
			CKEDITOR.replace( this.editorId, config );
		}
		catch( err )
		{
			Debug.write( 'CKEditor error: ' + err );
		}

		this.CKEditor = CKEDITOR.instances[ this.editorId ];
		
		/* Bug in ckeditor init which treats initContent as event object inside an .on() */
		ipb.textEditor._tmpContent = ( Object.isString(initContent) || Object.isNumber(initContent) ) ? initContent : '';
		
		/* Got any saved data to show? */
		CKEDITOR.on( 'instanceReady', function( ev )
		{
			if ( ev.editor.name == this.editorId )
			{
				/* This is dumb and only way to access editor object */
				this.EditorObj = ev;
				
				/* Quickly make some changes if minimized */
				if ( this.options.minimize )
				{
					try
					{
						$('cke_top_' + this.editorId ).down('.cke_toolbox_collapser').hide();
						$('cke_bottom_' + this.editorId ).hide();
						
						$('cke_' + this.editorId ).down('.cke_wrapper').addClassName('minimized');
						
						if ( ! this.isRte() )
						{
							$('cke_' + this.editorId ).down('.cke_wrapper').addClassName('std');
						}
						else
						{
							/* IE bug when clicking the text editor to expand and it also registers a toolbar click*/
							if ( Prototype.Browser.IE9 )
							{
								$('cke_top_' + this.editorId ).down('.cke_toolbox').setStyle( { 'position': 'absolute', 'left': '-2000px' } );
							}
						}
					}catch(e){}
					
					ev.editor.on('focus', function()
					{
						try
						{
							if ( ! this.options.minimizeNowOpen )
							{ 
								this.showEditor().bind(this);
							}
						}catch(e){}
					}.bind(this) );
				}
				
				/* Fix for some tags */
				new Array( 'p', 'ul', 'li', 'blockquote', 'div' ).each( function ( tag )
				{
					ev.editor.dataProcessor.writer.setRules( tag, {
																	indent : false,
																	breakBeforeOpen : true,
																	breakAfterOpen : false,
																	breakBeforeClose : false,
																	breakAfterClose : true
																  } );
				} );
				
				/* Insert */
				if ( ipb.textEditor._tmpContent.length )
				{
					if ( ! this.isRte() )
					{
						ev.editor.setData( ipb.textEditor.stdPre( ipb.textEditor._tmpContent ) );
					}
					else
					{
						ev.editor.setData( ipb.textEditor.ckPre( ipb.textEditor._tmpContent ) );
					}
				}
				
				/* Clear tmp content */
				ipb.textEditor._tmpContent = '';
				
				this.displayAutoSaveData();
				
				if ( this.options.isTypingCallBack !== false )
				{
					this.timers['dirty'] = setInterval( this.checkForInput.bind(this), ipb.textEditor.IPS_TEXTEDITOR_POLLING );
				}
				
				/* Make sure our menus close */
				if ( $('cke_contents_' + this.editorId ).down('iframe') )
				{
					try
					{
						$('cke_contents_' + this.editorId ).down('iframe').contentWindow.document.onclick = parent.ipb.menus.docCloseAll;
					} catch( e ) { }
				}				
				
				/* Some ACP styles conflict */
				$$('.cke_top').each( function(elem) { elem.setStyle('background: transparent !important; padding: 0px !important'); } );
				$$('.cke_bottom').each( function(elem) { elem.setStyle('background: transparent !important; padding: 0px !important'); } );
				$$('.cke_contents').each( function(elem) { elem.setStyle('padding: 0px !important'); } );
				
				/* CKEditor tends to add a bunch of inline styles to cke_top_x which messes up custom styles */
				$('cke_top_' + this.editorId ).writeAttribute( 'style', '' );
				
				/* Update text-direction, since CKE forces LTR in 'source' mode */
				if( isRTL && !this.isRte() )
				{
					$$('.cke_contents > textarea').each( function(elem) {
						$(elem).setStyle( { textAlign: 'right' } );
					});
				}
				
				/* Is HTML? */
				if ( this.options.isHtml == 1 )
				{
					if ( Object.isUndefined( ipb.textEditor.htmlCheckbox[ this.editorId ] ) || ipb.textEditor.htmlCheckbox[ this.editorId ] == null )
					{ 
						$('cke_' + this.editorId ).insert( new Element( 'input', { type: 'checkbox', id: 'cbx_' + this.editorId, checked: true } ).hide() );
						
						ipb.textEditor.bindHtmlCheckbox( $('cbx_' + this.editorId ), this.editorId );
					}
					
					$( ipb.textEditor.htmlCheckbox[ this.editorId ] ).checked = true;
				}
				
				/* HTML checkbox checked? */
				if ( $( ipb.textEditor.htmlCheckbox[ this.editorId ] ) )
				{
					/* Check status of HTML checkbox on load */
					ipb.textEditor.htmlModeToggled( this, ipb.textEditor.htmlCheckbox[ this.editorId ] );
				}
				
				if ( this.options.noSmilies )
				{
					$('cke_top_' + this.editorId ).down('.cke_button_ipsemoticon').up('.cke_button').hide();
				}
				
			}
		}.bind(this) );		
	},
	
	/**
	 * Init CKEditor
	 */
	showEditor: function( content )
	{
		if ( this.options.minimize )
		{						
			this.options.minimizeNowOpen = 1;					
			
			$('cke_top_' + this.editorId ).down('.cke_toolbox_collapser').show();
			$('cke_bottom_' + this.editorId ).show();
			$('cke_' + this.editorId ).down('.cke_wrapper').removeClassName('minimized');
			
			this.EditorObj.editor.execCommand('toolbarCollapse');
			
			/* IE bug when clicking the text editor to expand and it also registers a toolbar click*/
			if ( Prototype.Browser.IE9 )
			{
				setTimeout( function() { $('cke_top_' + this.editorId ).down('.cke_toolbox').setStyle( { 'position': 'relative', 'left': '0px' } ) }.bind(this), 300 );
			}
			
			/* Shift screen if need be */
			try
			{
				var dims       = document.viewport.getDimensions();
				var editorDims = $('cke_' + this.editorId).getDimensions();
				var cOffset    = $('cke_' + this.editorId).cumulativeScrollOffset();
				var pOffset    = $('cke_' + this.editorId).positionedOffset();
				
				var bottomOfEditor = pOffset.top + editorDims.height;
				var bottomOfScreen = cOffset.top + dims.height;
				
				if ( bottomOfEditor > bottomOfScreen )
				{
					var diff = bottomOfEditor - bottomOfScreen;
					
					/* Scroll but with 100 extra pixels for comfort */
					window.scrollTo( 0, cOffset.top + diff + 100 );
				}
			}
			catch(e){ }
		}
	},
	
	/**
	 * Check for dirty status and throw it to a callback then cancel timer
	 */
	checkForInput: function()
	{
		if ( this.options.minimize == 1 && this.options.minimizeNowOpen == 0 )
		{
			return false;
		}

		var content = this.getText();
		
		if ( content && content.length && Object.isFunction( this.options.isTypingCallBack ) )
		{
			/* We have content, so throw to call back */
			this.options.isTypingCallBack();
			
			/* And cancel timer */
			clearInterval( this.timers['dirty'] );
			this.timers['dirty'] = '';
		}
	},
	
	/**
	 * Close previously minimized editor
	 */
	minimizeOpenedEditor: function()
	{
		if ( this.options.minimize == 1 && this.options.minimizeNowOpen == 1 )
		{ 
			if ( ! this.options.bypassCKEditor && this.CKEditor )
			{
				if ( $('cke_' + CKEDITOR.plugins.ipsemoticon.editor.name + '_stray') )
				{
					$('cke_' + CKEDITOR.plugins.ipsemoticon.editor.name + '_stray').remove();
					
					if ( $('ips_x_smile_show_all') )
					{
						$('ips_x_smile_show_all').remove();
					}
				}
				
				/* Re-shrink editor */
				this.EditorObj.editor.execCommand('toolbarCollapse');
				$('cke_top_' + this.editorId ).down('.cke_toolbox_collapser').hide();
				$('cke_bottom_' + this.editorId ).hide();
				$('cke_' + this.editorId ).down('.cke_wrapper').addClassName('minimized');
				
				if ( this.isRte() )
				{
					this.EditorObj.editor.setData('<p></p>');
					this.EditorObj.editor.focusManager.forceBlur();
				}
				else
				{
					this.EditorObj.editor.setData('');
					
					try
					{
						$('cke_' + this.editorId).down('textarea').blur();
					}
					catch(error){ Debug.write( error ); }
					
				}
				
				this.options.minimizeNowOpen = 0;
			}
			
			if ( this.options.bypassCKEditor )
			{
				$( this.editorId ).value = '';
			}
			
			try
			{
				if ( ! Object.isUndefined( $H( this.timers ) ) )
				{
					$H( this.timers ).each( function (timer)
					{
						var name = timer.key;
						
						if ( name.match( /^interval_/ ) )
						{
							clearInterval( this.timers[ name ] );
							Debug.write( 'Cleared Interval ' + name );
						}
						else
						{
							clearTimeout( this.timers[ name ] );
							Debug.write( 'Cleared Timeout ' + name );
						}
						
						this.timers[ name ] = '';
					}.bind(this) );
				}
			}
			catch(e) { Debug.error(e); }
		}
	},
	
	/**
	 * Make sure editor is in view
	 * 
	 */
	scrollTo: function()
	{
		var dims = document.viewport.getDimensions();
		var where = $( this.editorId ).positionedOffset();
		var offsets = document.viewport.getScrollOffsets();

		/* Is editor off the page? */
		if ( offsets.top + dims.height < where.top )
		{
			window.scroll( 0, ( parseInt( where.top ) - 200 ) );
		}
	},
	
	/**
	 * Remove editor completely
	 */
	remove: function()
	{
		this.CKEditor.destroy( true );
		
		this.CKEditor	= null;
		
		/* Remove object */
		ipb.textEditor.mainStore.unset( this.editorId );
		
		/* Remove timers */
		if ( ! Object.isUndefined( $H( this.timers ) ) )
		{
			$H( this.timers ).each( function (timer)
			{
				var name = timer.key;
				
				if ( name.match( /^interval_/ ) )
				{
					clearInterval( this.timers[ name ] );
					Debug.write( 'Cleared Interval ' + name );
				}
				else
				{
					clearTimeout( this.timers[ name ] );
					Debug.write( 'Cleared Timeout ' + name );
				}
				
				this.timers[ name ] = '';
			}.bind(this) );
		}
	},
	
	/**
	 * Inserts content into the text editor
	 */
	insert: function( content, scrollOnInit, clearFirst )
	{
		/* Minimized... */
		if ( this.options.minimize == 1 && this.options.minimizeNowOpen != 1 )
		{
			/* Scroll to editor when it loads? */
			if ( scrollOnInit )
			{
				$( this.editorId ).scrollTo();
			}
			
			this.showEditor();
		}
		else
		{
			/* Scroll always? */
			if ( scrollOnInit === 'always' )
			{
				if ( this.options.bypassCKEditor != 1 )
				{ 
					$( 'cke_' + this.editorId ).scrollTo();
				}
				else
				{
					$( this.editorId ).scrollTo();
				}
			}
		}
		
		if ( this.options.bypassCKEditor != 1 )
		{
			if ( this.isRte() )
			{
				// Using insertHtml() because if you have content in the editor and insert more (i.e. click reply on two posts in a topic),
				// subsequent inserts will show the HTML instead of formatting it properly
				this.CKEditor.insertHtml( content );
			}
			else
			{
				if ( this.CKEditor.getData() )
				{
					if ( clearFirst )
					{
						this.CKEditor.setData( ipb.textEditor.stdPre( content ) );
					}
					else
					{
						ipsTextArea.insertAtCursor( this.editorId, ipb.textEditor.stdPre( content ) );
						//this.CKEditor.setData( this.CKEditor.getData() + ipb.textEditor.stdPre( content ) );
					}
				}
				else
				{
					this.CKEditor.setData( ipb.textEditor.stdPre( content ) );
				}				
			}
		}
		else
		{
			$( this.editorId ).value += content;
		}
	},

	
	/**
	 * Show any display data we might have
	 */
	displayAutoSaveData: function()
	{
		if ( inACP )
		{
			return;
		}
		
		/* Keep looping until editor is ready */
		try
		{
			if ( Object.isUndefined( this.CKEditor ) || ! this.CKEditor.name || ! $('cke_' + this.editorId ) )
			{
				setTimeout( this.displayAutoSaveData.bind(this), 1000 );
				return;
			}
		} catch(e) { }
		
		Debug.write( 'Ready to show saved data: ' + 'cke_' + this.editorId );
	
		var sd = this.options.ips_AutoSaveData;
		
		if ( sd.key )
		{ 
			html  = this.options.ips_AutoSaveTemplate.evaluate( sd );
			
			if ( $('cke_' + this.editorId ).down('.cke_bottom').select('.cke_path').length < 1 )
			{
				$('cke_' + this.editorId ).down('.cke_resizer').insert( { before: new Element('div').addClassName('cke_path').update(html) } );
				
				ipb.delegate.register('._as_launch', this.launchViewContent.bind(this) );
			}
		}
	},
	
	/**
	 * Show the saved content in a natty little windah
	 */
	launchViewContent: function(e)
	{
		Event.stop(e);
		
		if ( ! Object.isUndefined( this.popups['view'] ) )
		{
			this.popups['view'].show();
		}
		else
		{			
			/* easy one this... */
			this.popups['view'] = new ipb.Popup( 'view', { type: 'modal',
											               initial: new Template( ipb.textEditor.IPS_AUTOSAVEVIEW ).evaluate( { content: this.options.ips_AutoSaveData.parsed } ),
											               stem: false,
											               warning: false,
											               hideAtStart: false,
											               w: '600px' } );
			
			ipb.delegate.register('._as_restore', this.restoreAutoSaveData.bind(this) );
		}
	},
	
	/**
	 * Show the about saved content in a natty little windah
	 */
	launchExplain: function(e)
	{
		Event.stop(e);
		
		var s = '__last_update_stamp_' + this.editorId;
		
		if ( ! Object.isUndefined( this.popups['explain'] ) )
		{
			this.popups['explain'].kill();
		}
					
		/* easy one this... */
		this.popups['explain'] = new ipb.Popup( 'explain', { type: 'balloon',
											                 initial: ipb.textEditor.IPS_AUTOSAVEEXPLAIN,
											                 stem: true,
											                 warning: false,
											                 hideAtStart: false,
											                 attach: { target: $$('.' + s ).first() },
											                 w: '300px' } );						
		
	},
	
	/**
	 * Restore auto saved content
	 */
	restoreAutoSaveData: function(e)
	{
		Event.stop(e);
		
		this.popups['view'].hide();
		
		if ( this.isRte() )
		{
			setTimeout( function() { this.CKEditor.setData( ipb.textEditor.ckPre( this.options.ips_AutoSaveData.restore_rte.replace( /&amp;/g, '&' ) ) ); }.bind(this), 500 );
		}
		else
		{
			setTimeout( function() { this.CKEditor.setData( this.options.ips_AutoSaveData.restore_std ); }.bind(this), 500 );
		}
	},
	
	/**
	 * Save contents of the editor
	 * @param	object	Current editor object (passed via plugin)
	 * @param	object	Current command object (passed via plugin)
	 */
	save: function( editor, command )
	{
		if ( inACP )
		{
			return;
		}
		
		var _url  = ipb.textEditor.ajaxUrl + '&app=core&module=ajax&section=editor&do=autoSave&secure_key=' + ipb.vars['secure_hash'] + '&autoSaveKey=' + this.options.ips_AutoSaveKey;
		Debug.write( _url );
		
		/* Fetch data */
		var content = this.CKEditor.getData();
		
		Debug.write( 'Fetched editor content: ' + content );
		
		new Ajax.Request( _url,
							{
								method: 'post',
								evalJSON: 'force',
								hideLoader: true,
								parameters: { 'content' : content.encodeParam() },
								onSuccess: function(t)
								{										    	
									/* No Permission */
									if ( t.responseJSON && ( t.responseJSON['status'] == 'ok' || t.responseJSON['status'] == 'nothingToSave' ) )
									{
										/* Reset 'dirty' */
										editor.resetDirty();
						
										/* No longer busy */
										command.setState( CKEDITOR.TRISTATE_OFF );
										
										if ( t.responseJSON['status'] == 'ok' )
										{
											this.updateSaveMessage();
										}
									}
								}.bind(this)
							} );	
		
	},
	
	/**
	 * Display the time the post was last auto saved
	 */
	updateSaveMessage: function()
	{
		var s = '__last_update_stamp_' + this.editorId;
		var d = new Date();
		var c = new Template( ipb.textEditor.IPS_SAVED_MSG ).evaluate( { time: d.toLocaleTimeString() } );
		
		/* remove old */
		$$('.' + s ).invoke('remove');
		
		/* Add new */
		$('cke_' + this.editorId ).down('.cke_resizer').insert( { before: new Element('div').addClassName('cke_path ' + s).update(c) } );
		
		ipb.delegate.register('._as_explain', this.launchExplain.bind(this) );
	}
} );