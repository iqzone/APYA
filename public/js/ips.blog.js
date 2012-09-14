/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.blog.js - Blog javascript				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _blog = window.IPBoard;

_blog.prototype.blog = {
	
	cblocks: {},
	canPostDraft: 1,
	canPublish: 1,
	defStatusGlobal: 'published',
	defStatus: 'published',
	_updating: 0,
	currentCats: {},
	_newCats: $H(),
	goComments: 0,
	maxCats: 0,
	updateLeft: false,
	updateRight: false,
	cton: false,
	// Properties for sortable
	props:  { 	tag: 'div', 				only: 'cblock_drag',
	 			handle: 'draggable', 		containment: ['cblock_left', 'cblock_right'],
	 			constraint: '', 			dropOnEmpty: true,
	 		 	hoverclass: 'over'
	 		},
	popups: {},
	cp1: null,
	blogs: {},
	
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.blog.js");
		
		document.observe("dom:loaded", function(){
			if( ipb.blog.inEntry && ipb.blog.ownerID == ipb.vars['member_id'] && ipb.blog.withBlocks )
			{
				ipb.blog.setUpDrags();
				ipb.blog.setUpCloseLinks();
				
				if( $('change_header') )
				{
					$('change_header').observe('click', ipb.blog.changeHeader);
				}
				
				if( $('add_theme') )
				{
					$('add_theme').observe('click', ipb.blog.changeTheme);
				}
			}

			ipb.delegate.register('.post_id a[rel="bookmark"]', ipb.blog.showLinkToEntry );
			ipb.delegate.register('.delete_entry', ipb.blog.deleteEntry);
			ipb.delegate.register(".__delete", ipb.blog.deleteDialogue );
			
			ipb.blog.resizeIndexBlocks();
		});
	},
	
	resizeIndexBlocks: function()
	{
		if(!$('blog_index'))
		{
			return;
		}
		
		var rows = $('blog_index').select('.teaser_wrap');
		
		for(var i = 0; i < rows.length; i++)
		{
			var row = $(rows[i].id);
			var l   = row.select('.teaser_left .entry_area');
			var r   = row.select('.teaser_right .entry_area');
			
			if(l[0] && r[0])
			{
				var lh = l[0].getHeight();
				var rh = r[0].getHeight();
				
				if(lh > rh)
				{					
					r[0].setStyle({height: lh + 'px'});
				}
				else if(rh > lh)
				{					
					l[0].setStyle({height: rh + 'px'});
				}
			}
		}
	},
	
	/* INIT recent items menu */
	setUpRecentMenu: function()
	{
		Debug.write('setting up menu' );
		ipb.delegate.register(".__rmenu", ipb.blog.recentMenu);
	},
	
	recentMenu: function( e, elem )
	{
		Event.stop(e);
		
		var action = $( elem ).className.match('__x([a-z]+)');
		if( action == null || Object.isUndefined( action[1] ) ){ Debug.error("Error showing popup"); return; }
		
		var newTitle = $( elem ).innerHTML;
		var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=sidebar&do=" + action[1] + '&md5check=' + ipb.vars['secure_hash'];
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								$('recentajaxcontent').update( t.responseText );
								$('ratitle').update( newTitle );
							}
						});
		
	},
	
	/* INIT blogs as table */
	setUpBlogsAsTable: function()
	{
		Debug.write('setting up blog table layout' );
		ipb.delegate.register(".__blog_preview", ipb.blog.entryPreview);
	},
	
	entryPreview: function(e, elem)
	{				
		Event.stop(e);
		
		var toggle = $(elem).down(".expander");
		var row = $(elem).up(".__blog");
		var id = $(row).readAttribute("data-bid");
		
		if( !id ){ return; }
		
		// Stop multiple loads
		if( $(row).readAttribute('loadingPreview') == 'yes' ){
			return; // Just be patient!
		}		
		$( row ).writeAttribute('loadingPreview', 'yes');
		
		if( $("entry_preview_" + id) )
		{
			if( $("entry_preview_wrap_" + id).visible() )
			{ 
				new Effect.BlindUp( $("entry_preview_wrap_" + id), { duration: 0.3, afterFinish: function(){ $('entry_preview_' + id).hide(); } } );
				row.removeClassName('highlighted');
				$( toggle ).addClassName('closed').removeClassName('loading').removeClassName('open').writeAttribute('title', ipb.lang['open_blog_preview']);
			}
			else
			{
				new Effect.BlindDown( $("entry_preview_wrap_" + id), { duration: 0.3, beforeStart: function(){ $('entry_preview_' + id).show(); } } );
				row.addClassName('highlighted');
				$( toggle ).addClassName('open').removeClassName('loading').removeClassName('closed').writeAttribute('title', ipb.lang['close_tpreview']);
			}
			
			$(row).writeAttribute('loadingPreview', 'no');
		}
		else
		{
			var url    = ipb.vars['base_url'] + '&app=blog&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=entries&do=preview&entryid=' + id;
			
			$( toggle ).addClassName('loading').removeClassName('closed').removeClassName('open');
			
			// Send AJAX request
			new Ajax.Request( 	url,
				 				{
									method: 'get',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										if( t.responseText == 'no_entry' || t.responseText == 'no_permission' ){
											alert( ipb.lang['error_blog_preview'] );
											return;
										}
										
										if( row.tagName == "TR" )
										{
											var count = row.childElements().size();
											var newrow = new Element('tr', { 'class': 'preview', 'id': 'entry_preview_' + id });
											var newcell = new Element('td', { 'colspan': count } );
											var wrap = new Element('div', { 'id': 'entry_preview_wrap_' + id }).hide().update( new Element('div' ) );
											
											// Put all the bits inside each other
											row.insert( { after: newrow.insert( newcell.insert( wrap ) ) } );
										}
										else
										{
											var wrap = new Element('div', { 'id': 'entry_preview_wrap_' + id }).hide().update( new Element('div') );
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
	
	/* FORM STUFFS */
	
	/**
	 * Init MEOW
	 */
	formInitCats: function()
	{
		var _c = 0;
		
		$('formCats').update('');
		
		/* Display new cats first, that's the LAW */
		if ( ipb.blog._newCats.size() )
		{
			ipb.blog._newCats.each( function( c )
			{
				var html = ipb.templates['cat_entry'].evaluate( { 'cid': c.key, 'cat': c.value['category_title'] } );
				$('formCats').insert( html );
				
				$('cat_' + c.key).checked = ( c.value['_selected'] == 1 ) ? true : false;
				$('cat_' + c.key).observe('click', ipb.blog.formCheckboxClicked.bindAsEventListener( this, c.key ) );
				
				_c++;
			} );
		}
	
		if ( ipb.blog.currentCats.size() )
		{
			ipb.blog.currentCats.each( function( c )
			{
				var html = ipb.templates['cat_entry'].evaluate( { 'cid': c.key, 'cat': c.value['category_title'] } );
				$('formCats').insert( html );
				
				$('cat_' + c.key ).checked = ( c.value['_selected'] == 1 ) ? true : false;
				$('cat_' + c.key).observe('click', ipb.blog.formCheckboxClicked.bindAsEventListener( this, c.key ) );
				
				_c++;
			} );
		}
		
		/* Add overflow */
		if ( _c >= 6 )
		{
			$('formCats').className = 'formCatsList';
		}
		
		/* Max cats reached */
		if ( _c >= ipb.blog.maxCats )
		{
			$('categoryAddToggle').hide();
		}
	},
	
	/**
	 * Check box handler
	 */
	formCheckboxClicked: function( e, key )
	{
		if ( key.match( /catNew/ ) )
		{
			var c = ipb.blog._newCats.get( key );
		
			ipb.blog._newCats.set( key, {'_selected' : ( $('cat_' + key).checked ) ? 1 : 0 , 'category_title': c.category_title } );
		}
		else
		{
			var c = ipb.blog.currentCats.get( key );
			
			ipb.blog.currentCats.set( key, {'_selected' : ( $('cat_' + key).checked ) ? 1 : 0 , 'category_title': c.category_title } );
		}
	},
	
	/**
	 * Add meow
	 */
	formAddCat: function( element, e )
	{
		Event.stop(e);
		
		var _go = true;
		
		var newCatName = ipb.blog.cleanCategoryName($F('formCatAddInput'));
		
		if (newCatName)
		{
			/* Already got a meow by this name? */
			if ( ipb.blog.currentCats.size() )
			{
				ipb.blog.currentCats.each( function( c )
				{
					if ( c.value['category_title'] == newCatName )
					{
						alert( ipb.lang['blog_cat_exists'] );
						
						$('formCatAddInput').value = '';
						_go = false;
					}
				} );
			}
			
			if ( ipb.blog._newCats.size() )
			{
				ipb.blog._newCats.each( function( t )
				{
					if ( t.value == newCatName )
					{
						alert( ipb.lang['blog_cat_exists'] );
						
						$('formCatAddInput').value = '';
						_go = false;
					}
				} );
			}
			
			if ( _go == true )
			{
				var _id   = 'catNew-' + parseInt( ipb.blog._newCats.size() + 1 );
				var _name = newCatName;
				
				ipb.blog._newCats.set( _id, { 'category_title' : _name, '_selected' : 1 } );
				
				$('formCatAddInput').value = '';
				
				ipb.blog.formInitCats();
			}
		}
	},
	
	cleanCategoryName: function(name)
	{
		name = name.replace('&#032;', ' ');
		name = name.replace("\r", '');
		name = name.replace("\n", '');

		name = name.replace('&', '&amp;');
		name = name.replace('<!--', '&#60;&#33;--');
		name = name.replace('-->', '--&#62;');
		name = name.replace('<script', '&#60;script');
		name = name.replace('>', '&gt;');
		name = name.replace('<', '&lt;');
		name = name.replace('"', '&quot;');
		name = name.replace('$', '&#036;');
		name = name.replace('!', '&#33;');
		name = name.replace('\'', '&#39;');
		
		return name;
	},
	
	/* Form INIT */
	initPostForm: function()
	{
		if ( $('bf_timeToggle') )
		{
			$('bf_timeToggle').toggle();
			$('bf_timeCancel').toggle();
			$('bf_timeOpts').toggle();
		}
		
		$('bf_publish').observe('click', ipb.blog.pfTriggerSubmit.bindAsEventListener( this, 'publish' ) );
		$('bf_draft').observe('click', ipb.blog.pfTriggerSubmit.bindAsEventListener( this, 'draft' ) );
		
		ipb.blog.defStatus = ( ! ipb.blog.defStatus ) ? ipb.blog.defStatusGlobal : ipb.blog.defStatus;
		
		if ( ! ipb.blog.canPostDraft )
		{
			$('bf_draft').hide();
		}
		else
		{
			if ( ! ipb.blog.canPublish )
			{
				$('bf_publish').hide();
			}
			else
			{
				if ( $('bfs_modOptions') && $F('bfs_modOptions') == 'published' )
				{
					$('bfs_submit').value = ipb.lang['blog_publish_now'];
				}
				else
				{
				
					$('bfs_submit').value = ipb.lang['blog_save_draft'];
				}
			}
		}
		
		$('bf_modWrapper').hide();
		$('bf_timeOpts').hide();
		
		/* Blog choose */
		if ( $('blog_chooser') )
		{
			$('blog_chooser').observe( 'change', ipb.blog.pfBlogChooser.bindAsEventListener( this ) );
		}
	},
	
	pfBlogChooser: function( e )
	{
		Event.stop(e);
		
		blogid = parseInt( $('blog_chooser').value );
		
		if ( blogid )
		{
			var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=categories&do=fetchCategories&blogid=" + blogid + "&secure_key=" + ipb.vars['secure_hash'];
			
			new Ajax.Request( url.replace(/&amp;/g, '&'),
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if ( Object.isUndefined( t.responseJSON ) )
									{
										alert( "pfBlogChooser: " + ipb.lang['action_failed'] );
										return;
									}
									
									ipb.blog.currentCats = $H( t.responseJSON.cats );
									
									ipb.blog.formInitCats(e);
								}
							});
		}
	},
	
	pfTriggerSubmit: function( e, type )
	{
		// Got polls?
		if ( $('add_poll') )
		{
			ipb.poll.submitCheckPoll(e);
		}
		
		Event.stop(e);
		
		if ( !ipb.blog.canPostDraft || !ipb.blog.canPublish )
		{
			$('postingform').submit();
		}
		else
		{
			var draftId = ( $('bfs_modOptions').options[1].value == 'draft' ) ? 1 : 0;
			
			if ( type == 'draft' )
			{
				$('bfs_modOptions').selectedIndex = draftId;
			}
			else
			{
				$('bfs_modOptions').selectedIndex = ( draftId == 1 ) ? 0 : 1;
			}
			
			$('postingform').submit();
		}
	},
	
	pfTimeToggle: function(element, e)
	{
		Event.stop(e);
		$('bf_timeToggle').toggle();
		$('bf_timeOpts').toggle();
	},
	
	/* OTHER STUFFS */

	showLinkToEntry: function(e, elem)
	{
		_t = prompt( ipb.lang['copy_entry_link'], $( elem ).readAttribute('href') );
		Event.stop(e);
	},
	
	deleteEntry: function(e, elem)
	{
		if( !confirm( ipb.lang['delete_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	changeTheme: function(e)
	{
		Event.stop(e);
		
		if( ipb.blog.popups['themes'] )
		{
			ipb.blog.popups['themes'].show();
		}
		else
		{
			// Set up content
			var afterInit = function( popup )
			{
				$('theme_preview').observe('click', ipb.blog.previewTheme);
				$('theme_save').observe('click', ipb.blog.saveTheme);
				$('theme_color_picker').observe('click', ipb.blog.openPicker);
			};
			
			ipb.blog.popups['themes'] = new ipb.Popup('theme_editor', { type: 'pane', modal: false, hideAtStart: true, initial: ipb.templates['add_theme'].replace( /~~~__NL__~~~/g, "\n" ) }, { afterInit: afterInit } );
			ipb.blog.popups['themes'].show();
		}
	},
	
	openPicker: function(e)
	{
		Event.stop(e);
		window.open( ipb.vars['board_url'] + "/blog/colorpicker.html", "colorpicker", "status=0,toolbar=0,width=500,height=400,scrollbars=0");
	},
	
	saveTheme: function(e)
	{
		var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=themes&blogid=" + ipb.blog.blogID;
		var content = $F( 'themeContent' );
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'post',
							parameters: {
								content: content.encodeParam(),
								md5check: ipb.vars['secure_hash']
							},
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( !Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] + ": " + t.responseJSON['error'] );
									return;
								}
								
								ipb.blog.popups['themes'].update( ipb.templates['theme_saved'] );
							}
						});
		
	},
	
	previewTheme: function(e)
	{
		for( var i=0; i < document.styleSheets.length; i++ )
		{
			if( document.styleSheets[ i ].title == 'Theme' )
			{
				document.styleSheets[ i ].disabled = true;
			}
		}

		var style = document.createElement( 'style' );
		style.type = 'text/css';

		var content = $F( 'themeContent' );

		if( ! content )
		{
			return false;
		}
		
		var h = document.getElementsByTagName("head");
		h[0].appendChild( style );
		
		Debug.write( content );
		
		try
		{
	    	style.styleSheet.cssText = content;
	  	}
	  	catch(e)
	  	{
	  		try
	  		{
	    		style.appendChild( document.createTextNode( content ) );
	    		style.innerHTML=content;
	  		}
	  		catch(e){}
	  	}

		return false;
	},
	
	changeHeader: function(e)
	{
		Event.stop(e);
		
		if( ipb.blog.popups['header'] )
		{
			ipb.blog.popups['header'].show();
		}
		else
		{
			var html = ipb.templates['headers'];
			
			var afterInit = function( popup )
			{
				if( $('reset_header') )
				{
					$('reset_header').observe('click', function(e){
						if( !confirm( ipb.lang['blog_revert_header'] ) )
						{
							Event.stop(e);
							return false;
						}
						
						window.location.href = ipb.blog.blogURL.replace(/&amp;/g, '&') + "&changeHeader=reset";
					});
				}
			};
			
			ipb.blog.popups['header'] = new ipb.Popup('change_header', { type: 'pane', modal: true, hideAtStart: false, w: '600px', initial: html }, { afterInit: afterInit } );
		}
	},
	
	setUpCloseLinks: function()
	{
		ipb.delegate.register('.close_link', ipb.blog.closeBlock );
		ipb.delegate.register('.configure_link', ipb.blog.configureBlock );
		ipb.delegate.register('.customBlockOption', ipb.blog.addBlock );
		ipb.delegate.register('.delete_block', ipb.blog.deleteBlock );
	},
	
	deleteBlock: function(e, elem)
	{
		if( !confirm( ipb.lang['blog_sure_delcblock'] ) )
		{
			Event.stop(e);
			return false;
		}
	},
	
	configureBlock: function(e, elem)
	{
		Event.stop(e);
		
		// Get id
		Debug.write( $(elem).id );
		var elem = $( elem ).up( '.cblock_drag' );
		var blockid = $( elem ).id.replace('cblock_', '');
		var wrapper = $( elem ).down('.cblock_inner');
		
		if( !wrapper ){ return; }
		
		// Get block
		new Ajax.Request( ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=showcblockconfig&secure_key=" + ipb.vars['secure_hash'] + "&cblock_id=" + blockid + "&blogid=" + ipb.blog.blogID,
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									else
									{
										$( elem ).replace( t.responseText );
										Sortable.create('cblock_right', ipb.blog.props );
										Sortable.create('cblock_left', ipb.blog.props );
									}
								}
							}
						);
	},
	
	addBlock: function(e, elem)
	{		
		if( $( elem ).id == 'new_cblock' )
		{
			return;
		}
		else
		{
			Event.stop(e);
			
			// Get id
			Debug.write( $(elem).id );
			var blockid = $( elem ).id.replace('enable_cblock_', '');
			
			if( $( elem ).hasClassName('enable') ){
				var req = 'doenablecblock';
			} else {
				var req = 'doaddcblock';
			}				
			
			// Get block
			new Ajax.Request( ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=" + req + "&secure_key=" + ipb.vars['secure_hash'] + "&cbid=" + blockid + "&blogid=" + ipb.blog.blogID,
								{
									method: 'get',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										if( Object.isUndefined( t.responseJSON ) )
										{
											alert( ipb.lang['action_failed'] );
											return;
										}
										
										if( t.responseJSON['error'] )
										{
											alert( ipb.lang['action_failed'] + ": " + t.responseJSON['error'] );
											return;
										}
										
										if( t.responseJSON['cb_html'] )
										{
											// Figure out where to put it
											if( $('cblock_right').visible() )
											{
												$('cblock_right').insert( { bottom: t.responseJSON['cb_html'] } );
												Sortable.create('cblock_right', ipb.blog.props );
												Sortable.create('cblock_left', ipb.blog.props );
											}
											else if( $('cblock_left').visible() )
											{
												$('cblock_left').insert( { bottom: t.responseJSON['cb_html'] } );
												Sortable.create('cblock_right', ipb.blog.props );
												Sortable.create('cblock_left', ipb.blog.props );
											}
											else
											{
												document.location.reload(true);
											}
											
											// Remove it from the menu
											if( $('enable_cblock_' + blockid) ){
												$('enable_cblock_' + blockid).remove();
											}
										}
									}
								} );
			
		}
	},

	closeBlock: function(e, elem)
	{
		Event.stop(e);
		
		var elem = $( elem ).up( '.cblock_drag' );
		var cblockid = $( elem ).id.replace('cblock_', '');
		
		if( Object.isUndefined( cblockid ) ){ return; }
		if( !elem ){ return; }
		
		var url = ipb.vars['base_url'] + 'app=blog&module=ajax&section=cblocks&do=doremovecblock&blogid='+ipb.blog.blogID + '&cbid=' + cblockid + "&secure_key=" + ipb.vars['secure_hash'];
		
		new Ajax.Request( url.replace(/&amp;/, '&'),
						{
							method: 'post',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseText == 'error' )
								{
									Debug.write( "Error removing block" );
								}
								else
								{
									new Effect.Parallel( [
										new Effect.BlindUp( $(elem), { sync: true } ),
										new Effect.Fade( $(elem), { sync: true } )
									], { duration: 0.5, afterFinish: function(){
										// Get the name of the item
									
										var menu_item = ipb.templates['cblock_item'].evaluate( { 'id': cblockid, 'name': t.responseJSON['name'] } );
									
										$('content_blocks_menucontent').insert( menu_item );
										
										$(elem).remove();
										ipb.blog.updatedBlocks('');
									} } );
								}
							}
						});	
	},
	
/*	setUpDrags: function()
	{
		Debug.write("Here");
		
		if( !$('main_column') ){
			Debug.error("No main column found, cannot create draggable blocks");
			return false;
		}
		
		var height_l = null;
		var height_r = null;
		var width_c = null;
		
		if( $('cblock_left') ){
			height_l = $('cblock_left').getHeight();
			width_c = $('cblock_left').getWidth();
		}
		
		if( $('cblock_right') ){
			height_r = $('cblock_right').getHeight();
			if( width_c != null )
			{
				var n_width_c = $('cblock_right').getWidth();
				width_c = ( n_width_c > width_c ) ? n_width_c : width_c;
			}
			else
			{
				width_c = $('cblock_right').getWidth();
			}
		}
		
		
		
		// Step one: if side column doesnt exist, create it
		if( !$('cblock_left') )
		{
			var cblockleft = new Element('div', { 'id': 'cblock_left', 'class': 'ipsLayout_left temp', 'style': 'display: none' } );
			
			$$('.ipsLayout_content').each( function(content)
			{
				if ( content.descendantOf('main_column') ){
					content.insert( { after: cblockleft } );
				}
			});
			
			ipb.blog.updateLeft = true;
		}
		
		if( !$('cblock_right') )
		{
			var cblockright = new Element('div', { 'id': 'cblock_right', 'class': 'ipsLayout_right temp', 'style': 'display: none' } );
			
			$$('.ipsLayout_content').each( function(content)
			{
				if ( content.descendantOf('main_column') ){
					content.insert( { after: cblockright } );
				}
			});
			
			ipb.blog.updateRight = true;
		}
		
		Sortable.create('cblock_right', ipb.blog.props );
		Sortable.create('cblock_left', ipb.blog.props );

		
		// Add observer
		Draggables.addObserver(
			{
				onStart: function( eventName, draggable, event )
				{
					$('main_column').removeClassName('ipsLayout_withleft')
							.removeClassName('ipsLayout_largeleft')
							.removeClassName('ipsLayout_withright')
							.removeClassName('ipsLayout_largeright')
							.addClassName('ipsLayout_withleft')
							.addClassName('ipsLayout_largeleft')
							.addClassName('ipsLayout_withright')
							.addClassName('ipsLayout_largeright');
							
					//$('main_column').className += " ipsLayout_withleft ipsLayout_withright ipsLayout_largeleft ipsLayout_largeright";
					//$('main_column').addClassName('ipsLayout_largeleft').addClassName('ipsLayout_largeright').addClassName('ipsLayout_withleftandright');
					
					$('cblock_left').innerHTML += "&nbsp;";
					$('cblock_right').innerHTML += "&nbsp;";
							
					$('cblock_left').show().addClassName('drop_zone');
					$('cblock_right').show().addClassName('drop_zone');
					
					if( !Prototype.Browser.IE )
					{
						$('cblock_left').setStyle('opacity: 0.3');
						$('cblock_right').setStyle('opacity: 0.3');
					}
				},
				onEnd: function( eventName, draggable, event )
				{
					$('cblock_left').removeClassName('drop_zone').setStyle('opacity: 1');
					$('cblock_right').removeClassName('drop_zone').setStyle('opacity: 1');
					
					ipb.blog._updated( draggable );
				}
			}
		);
	},*/
	
	setUpDrags: function()
	{
		Debug.write("Here");
		
		if( !$('main_column') ){
			Debug.error("No main column found, cannot create draggable blocks");
		}
		
		var height_l = null;
		var height_r = null;
		var width_c = null;
		
		if( $('cblock_left') ){
			height_l = $('cblock_left').getHeight();
			width_c = $('cblock_left').getWidth();
		}
		
		if( $('cblock_right') ){
			height_r = $('cblock_right').getHeight();
			if( width_c != null )
			{
				var n_width_c = $('cblock_right').getWidth();
				width_c = ( n_width_c > width_c ) ? n_width_c : width_c;
			}
			else
			{
				width_c = $('cblock_right').getWidth();
			}
		}
		
		// Step one: if side column doesnt exist, create it
		if( !$('cblock_left') )
		{
			var cblockleft = new Element('div', { id: 'cblock_left' } );
			cblockleft.setStyle('width: ' + width_c + 'px; height: ' + height_r + 'px;').addClassName('cblock').addClassName('temp').hide();
			$('sidebar_holder').insert( { before: cblockleft } );
			ipb.blog.updateLeft = true;
		}
		
		if( !$('cblock_right') )
		{
			var cblockright = new Element('div', { id: 'cblock_right' } );
			cblockright.setStyle('width: ' + width_c + 'px; height: ' + height_l + 'px;').addClassName('cblock').addClassName('temp').hide();
			$('sidebar_holder').insert( { after: cblockright } );
			ipb.blog.updateRight = true;
		}
		
		Sortable.create('cblock_right', ipb.blog.props );
		Sortable.create('cblock_left', ipb.blog.props );
		
		// Add observer
		Draggables.addObserver(
			{
				onStart: function( eventName, draggable, event )
				{
					$('cblock_left').show().addClassName('drop_zone');
					$('cblock_right').show().addClassName('drop_zone');
					
					if( !Prototype.Browser.IE )
					{
						$('cblock_left').setStyle('opacity: 0.3');
						$('cblock_right').setStyle('opacity: 0.3');
					}
				},
				onEnd: function( eventName, draggable, event )
				{
					$('cblock_left').removeClassName('drop_zone').setStyle('opacity: 1');
					$('cblock_right').removeClassName('drop_zone').setStyle('opacity: 1');
					
					ipb.blog._updated( draggable );
				}
			}
		);
	},
	
	_updated: function( draggable )
	{
		if( ipb.blog._updating ){ return; }
		ipb.blog._updating = true;
		
		id = 0;
		
		// Get the ID
		if( draggable.element )
		{
			id = $( draggable.element ).id.replace('cblock_', '');
		}
		
		// Update classes
		ipb.blog.updatedBlocks( id );
		
		// Update position by ajax
		ipb.blog.updatePosition( id, draggable );
	},
	
	updatePosition: function( id, draggable )
	{
		if( !$('cblock_' + id ) ){ return; }
		
		// Need to figure out which column it is in
		if( $('cblock_' + id ).descendantOf('cblock_left') ){
			var pos = 'l';
		} else {
			var pos = 'r';
		}
		
		var nextid = 0;
		
		// Which block is next to it?
		var nextelem = $('cblock_' + id).next('.cblock_drag');
		
		if( !Object.isUndefined( nextelem ) && $(nextelem).id )
		{
			nextid = $( nextelem ).id.replace('cblock_', '');
		}
		
		var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=savecblockpos&oldid="+id+"&newid="+nextid+"&pos="+pos+"&blogid="+ipb.blog.blogID+"&secure_key="+ipb.vars['secure_hash'];
		
		// Ok, send the infos
		new Ajax.Request( 	url.replace('&amp;', '&'),
							{
								method: 'get',
								onSuccess: function(t){
									Debug.write( t.responseText );
								}
							}
						);
		
	},
	
	/*updatedBlocks: function( id )
	{
		var d_l = $('cblock_left').select('.cblock_drag');
		var d_r = $('cblock_right').select('.cblock_drag');
		
		//var d_l = Sortable.sequence('cblock_left');
		//var d_r = Sortable.sequence('cblock_right');
		//Debug.dir( d_l );
		
		// Check for descendants
		if( d_l.size() > 0 ){
			$('main_column').addClassName('ipsLayout_withleft').addClassName('ipsLayout_largeleft');
			$('cblock_left').removeClassName('temp');
		} else {
			$('main_column').removeClassName('ipsLayout_withleft').removeClassName('ipsLayout_largeleft');
			$('cblock_left').addClassName('temp').hide();
			$('cblock_left').innerHTML += "&nbsp;"; // Force a redraw for safari
		}
		
		if( d_r.size() > 0 ){
			$('main_column').addClassName('ipsLayout_withright').addClassName('ipsLayout_largeright');
			$('cblock_right').removeClassName('temp');
		} else {
			$('main_column').removeClassName('ipsLayout_withright').removeClassName('ipsLayout_largeright');
			$('cblock_right').addClassName('temp').hide();
			$('cblock_right').innerHTML += "&nbsp;"; // Force a redraw for safari
		}
		
		ipb.blog._updating = false;
	},*/
	
	updatedBlocks: function( id )
	{
		var d_l = $('cblock_left').select('.cblock_drag');
		var d_r = $('cblock_right').select('.cblock_drag');
		
		//var d_l = Sortable.sequence('cblock_left');
		//var d_r = Sortable.sequence('cblock_right');
		//Debug.dir( d_l );
		
		// Check for descendants
		if( d_l.size() > 0 ){
			$('main_blog_wrapper').addClassName('with_left');
			$('cblock_left').removeClassName('temp');
		} else {
			$('main_blog_wrapper').removeClassName('with_left');
			$('cblock_left').addClassName('temp').hide();
			$('cblock_left').innerHTML += "&nbsp;"; // Force a redraw for safari
		}
		
		if( d_r.size() > 0 ){
			//$('main_blog_wrapper').addClassName('with_right');
			$('cblock_right').removeClassName('temp');
		} else {
			$('main_blog_wrapper').removeClassName('with_right');
			$('cblock_right').addClassName('temp').hide();
			$('cblock_right').innerHTML += "&nbsp;"; // Force a redraw for safari
		}
		
		if( ipb.blog.updateLeft )
		{
			//$('cblock_left').setStyle('height: auto; position: static; top: auto; left: auto;');
		}
		
		ipb.blog._updating = false;
	},
	
	saveCblock: function( e, cblock, fields )
	{
		var save_fields = '';
		
		for( var i = 0; i < fields.length; i++ )
		{
			save_fields += '&' + 'cblock_config[' + fields[i] + ']' + '=' + $F( fields[i] );
		}
		
		var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=savecblockconfig&cblock_id=" + cblock + "&blogid=" + ipb.blog.blogID + "&secure_key=" + ipb.vars['secure_hash'];
		
		new Ajax.Request( url.replace(/&amp;/g, '&' ) + save_fields,
							{
								method: 'get',
								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									else if( t.responseText == 'refresh' )
									{
										document.location.reload(true);
									}
									else
									{
										$( 'cblock_' + cblock_id ).replace( t.responseText );
										Sortable.create('cblock_right', ipb.blog.props );
										Sortable.create('cblock_left', ipb.blog.props );
									}
									
								}
							}
						);
	},
	
	/**
	* Delete pop-up
	*/
	deleteDialogue: function(e, elem)
	{
		Event.stop(e);
		
		var id = elem.id.replace( 'blogDelete_', '' );
		
		if ( ! Object.isUndefined( ipb.blog.popups[ 'del_' + id ] ) )
		{
			ipb.blog.popups[ 'del_' + id ].kill();
		}
		ipb.blog.popups[ 'del_' + id ] = new ipb.Popup( 'd_e_l__' + id, {
														type: 'balloon',
														initial: ipb.templates['deleteDialogue'].evaluate( { 'id' : id } ),
														stem: true,
														hideAtStart: false,
														hideClose: true,
														defer: false,
														warning: true,
														attach: { target: $('blogLink_' + id), position: 'auto', 'event': 'click' },
														w: '400px'
													});
				
		/* Populate select box */
		if ( ipb.blog.blogs.size() )
		{
			ipb.blog.blogs.each( function( b )
			{
				if ( b.key != id )
				{
					var _o = new Element( 'option' );
					_o.value  = b.key;
					_o.text   = b.value.replace( '&#39;', "'" ).replace( '&quot;', '"' );
					
					$('delselect_' + id).insert( _o );
				}
			} );
		}

		$('delButton_' + id).observe('click', ipb.blog.ohJustDeleteItAlready.bindAsEventListener( this, id ) );
		$('delMove_' + id).observe('change', ipb.blog.deleteMoveCheck.bindAsEventListener( this, id ) );
	},
	
	/**
	* YeAh
	*/
	ohJustDeleteItAlready: function( e, id )
	{
		Event.stop(e);
		if ( ! $('delConfirm_' + id ).checked )
		{
			alert( ipb.lang['blog_error_deletecbox'] );
			return false;
		}
		
		var url = $('blogDelete_' + id ).href;
		
		if ( $('delMove_' + id ) && $('delMove_' + id ).checked )
		{
			url += '&moveTo=' + $('delselect_' + id ).options[ $('delselect_' + id ).selectedIndex ].value;
		}
		
		/* OG */
		window.location = url;
	},
	
	/**
	 * <insert stuff>
	 */
	deleteMoveCheck: function( e, id )
	{
		Event.stop(e);
		
		if ( $('delMove_' + id).checked )
		{
			$('delMore_' + id).hide();
		}
		else
		{
			$('delMore_' + id).show();
		}
		
		return true;
	},
	
	/**
	 * Change Entry Image
	 */
	changeImage: function()
	{
		$('image_change').value = '1';
		$('image_field').style.display = '';
		$('image_preview').style.display = 'none';
	}

};

ipb.blog.init();
