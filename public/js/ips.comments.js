/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.gallery.js - Gallery javascript			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier & Brandon Farber		*/
/************************************************/

var _comments     = window.IPBoard;
var _comments_id  = 0;
/**
 * 
 */
_comments.prototype.comments = {
	
	totalChecked:	0,
	inSection: '',
	
	cur_left:	0,
	cur_right:	0,
	cur_image:	0,
	
	catPopups: [],
	popup: null,
	sAp: null,
	sApLn: 0,
	templates: {},
	commentCache: {},
	data: {},
	parentId: 0,
	commentId: 0,
	deletePopUps: {},
	hidePopUps: {},
	deleted: 0,
	modPop: false,
	cCard: 0,
	hc: '',
	up: '',
	_id: 0,
	modOptionsUnapproved: 0,
	modOptionsHidden: 0,
	modOptionsUnhidden: 0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.comments.js");
		
		document.observe("dom:loaded", function(){
			
			ipb.comments.preCheckComments();
			
			ipb.delegate.register('.post_id a[rel="bookmark"]', ipb.comments.showLinkToComment );
			
			/* Comments */
			ipb.delegate.register('.edit_comment'   , ipb.comments.editShow );
			ipb.delegate.register('.delete_comment' , ipb.comments.deletePop );
			ipb.delegate.register('.hide_comment' , ipb.comments.hidePop );
			ipb.delegate.register('.reply_comment'  , ipb.comments.reply );
			ipb.delegate.register('input.ipsComment_mod', ipb.comments.checkComment );
			
			if ( $('commentPost') && !$('commentCaptcha') )
			{
				$('commentPost').observe( 'click', ipb.comments.add );
			}
			
			if ( $('reputation_filter') )
			{
				$('reputation_filter').hide();
			}
			
			if( $('comment_name') )
			{
				$('comment_name').observe( 'focus', function() { 
					if( $('commentCaptcha') )
					{
						$('commentCaptcha').show();
					}
				});
			}
		});
	},
	
	moderate: function(e, elem)
	{
		// Count checked boxes that are visible
		var count = $$(".ipsComment_mod:checked").findAll( function(el){ return el.up('.ipsComment').visible(); } );
		
		if( count.size() ){
			if( !$('comment_moderate_box') ){
				$$('body')[0].insert({'bottom': ipb.templates['comment_moderation'].evaluate({count: count.size()}) });
				$('submitModAction').on('click', ipb.comments.doModerate);
			} else {
				$('comment_count').update( count.size() );
			}
			
			if( !$('comment_moderate_box').visible() ){
				new Effect.Appear( $('comment_moderate_box'), { duration: 0.3 } );
			}
			
			/* Update the available options */
			$('commentModAction').select('option').invoke('remove');
			
			if ( ipb.comments.modOptionsUnapproved )
			{
				$('commentModAction').insert( new Element('option', { value: 'approve' } ).update( ipb.lang['cpt_approve'] ) );
			}
			if ( ipb.comments.modOptionsUnhidden )
			{
				$('commentModAction').insert( new Element('option', { value: 'hide' } ).update( ipb.lang['cpt_hide'] ) );
			}
			if ( ipb.comments.modOptionsHidden )
			{
				$('commentModAction').insert( new Element('option', { value: 'unhide' } ).update( ipb.lang['cpt_undelete'] ) );
			}
			
			$('commentModAction').insert( new Element('option', { value: 'delete' } ).update( ipb.lang['cpt_delete'] ) );
		}
		else
		{
			if( $('comment_moderate_box') ){
				new Effect.Fade( $('comment_moderate_box'), { duration: 0.3 } );
			}
		}
	},
	
	doModerate: function(e)
	{
		var doMod	= $('commentModAction').value;
		var url		= ipb.comments.data['ajaxModerateUrl'] ? ipb.comments.data['ajaxModerateUrl'] : ipb.vars['base_url'] + 'app=core&module=ajax&section=comments&do=moderate&parentId=' + ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
		
		// Get checked comment IDs
		var ids = $$('.ipsComment_mod:checked').collect( function(item){
			return item.up('.ipsComment').readAttribute('data-commentid');
		});
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								encoding: ipb.vars['charset'],
								parameters: {
									md5check: 			ipb.vars['secure_hash'],
									modact:				doMod,
									"commentIds[]":		ids
								},
								onSuccess: function(t)
								{
									if ( t.responseJSON['error'] ){
										alert( t.responseJSON['error'] );
										return false;
									}
									else
									{
										$$('.ipsComment_mod:checked').each( function(i){
											i.checked = false;
										});
										
										if( $('comment_moderate_box') ){
											new Effect.Fade( $('comment_moderate_box'), { duration: 0.3 } );
										}
										
										switch( doMod ){
											case 'delete':
												ipb.comments.deleted += ids.length;
												Debug.write( "Deleted - " + ipb.comments.deleted + ", on this page - " + ipb.comments.data['counts']['thisPageCount'] );
												if ( ipb.comments.data['counts']['curStart'] ){
													if ( ipb.comments.data['counts']['thisPageCount'] - ipb.comments.deleted < 1 ){
														window.location = ipb.comments.data['findLastComment'] ? ipb.comments.data['findLastComment'] : ipb.vars['base_url'] + 'app=core&module=global&section=comments&do=findLastComment&parentId=' +ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
														return false;
													}
												}
												
												Effect.multiple( 	ids.collect( function(i){ return $('comment_id_' + i); } ),
																	Effect.Fade,
																	{ duration: 0.8 }
																);
											break;
											case 'hide':
											case 'unapprove':
												for( var i = 0 ; i <= ids.length ; i++ ){
													ipb.comments.visibilityEffect( 'off', ids[i] );
												}
											break;
											case 'unhide':
											case 'approve':
												for( var i = 0 ; i <= ids.length ; i++ ){
													ipb.comments.visibilityEffect( 'on', ids[i] );
												}
											break;
										}
									}
								}
							});
	},
	
	/**
	 * Do the color switch for approved/unapproved
	 * 
	 */
	visibilityEffect: function( mode, id )
	{
		if ( ! $('comment_id_' + id ) )
		{
			return;
		}
		
		var moderated = 'moderated';
		var row		  = 'row1';
		
		if ( mode == 'off' )
		{
			toClass   = moderated;
			fromClass = row;
		}
		else
		{
			toClass   = row;
			fromClass = moderated;
		}
		Debug.write( mode );
		/* Ensure the Morph BG has been removed */
		$('comment_id_' + id ).setStyle( { 'backgroundColor': '' } );
		
		if ( mode == 'off' )
		{
			/* Add  BG and fetch RGB value */
			$('comment_id_' + id ).addClassName( toClass );
			var endColor = $('comment_id_' + id ).getStyle( 'background-color' );

			/* Add BG and fetch RGB value */
			$('comment_id_' + id ).removeClassName( toClass );
			var startColor = $('comment_id_' + id ).getStyle( 'background-color' );

			new Effect.Morph( 'comment_id_' + id, { 'style': 'background-color:' + endColor, duration: 0.6, afterFinish: function() { $('comment_id_' + id ).addClassName( toClass ); } } );
			
			$( 'mod_comment_id_' + id ).writeAttribute( 'data-status', '-1' );
			ipb.comments.modOptionsHidden++;
			ipb.comments.modOptionsUnhidden--;
		}
		else
		{			
			var startColor = $('comment_id_' + id ).getStyle( 'background-color' );
			
			$('comment_id_' + id ).removeClassName( fromClass );
			var endColor = $('comment_id_' + id ).getStyle( 'background-color' );
			
			$('comment_id_' + id ).addClassName( fromClass );
			
			new Effect.Morph( 'comment_id_' + id, { 'style': 'background-color:' + endColor, duration: 0.6, afterFinish: function() { $('comment_id_' + id ).removeClassName( fromClass ); } } );
			
			$( 'mod_comment_id_' + id ).writeAttribute( 'data-status', '1' );
			ipb.comments.modOptionsHidden--;
			ipb.comments.modOptionsUnhidden++;
		}
	},
	
	/**
	 * Set Data
	 */
	setData: function( json )
	{
		ipb.comments.data = json;
		Debug.dir( ipb.comments.data );
	},
	
	/**
	 * Show a pop-up for delete
	 */
	deletePop: function(e, elem)
	{
		Event.stop(e);
		
		var commentId = elem.up('.ipsComment').readAttribute('data-commentid');
		if( !commentId ){ return; }
		
		/* Create the pop-up */
		var popid   = 'pop__delete_popup_' + commentId;
		var content = ipb.templates['comment_delete'].evaluate( { 'commentId': commentId } );
		
		ipb.comments.deletePopUps = new ipb.Popup( popid, {  type: 'balloon',
															 initial: content,
															 stem: true,
															 hideAtStart: false,
															 attach: { target: $('delete_comment_' + commentId ), position: 'auto', 'event': 'click' },
															 w: '350px' } );
		
		/* store for use later */
		ipb.comments.commentId = commentId;
	},
	
	/**
	 * Fire delete
	 */
	deleteIt: function( e )
	{
		Event.stop(e);
		
		ipb.comments.deletePopUps.hide();
		
		/* Fire ajax */
		var url = ipb.comments.data['ajaxDeleteUrl'] ? ipb.comments.data['ajaxDeleteUrl'] : ipb.vars['base_url'] + 'app=core&module=ajax&section=comments&do=delete&parentId=' + ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
		
		Debug.write( url );
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								encoding: ipb.vars['charset'],
								parameters: {
									md5check: 			ipb.vars['secure_hash'],
									comment_id:			ipb.comments.commentId
									},
								onSuccess: function(t)
								{
									if ( t.responseJSON['error'] ){
										alert( ipb.lang['no_permission'] );
										return false;
									}
									else
									{
										/* Inc. deleted count */
										ipb.comments.deleted++;
										Debug.write( "Deleted - " + ipb.comments.deleted + ", on this page - " + ipb.comments.data['counts']['thisPageCount'] );
										
										if ( ipb.comments.data['counts']['curStart'] )
										{
											/* How many posts are actually left? */
											if ( ipb.comments.data['counts']['thisPageCount'] - ipb.comments.deleted < 1 )
											{
												/* redirect to the previous page */
												window.location = ipb.comments.data['findLastComment'] ? ipb.comments.data['findLastComment'] : ipb.vars['base_url'] + 'app=core&module=global&section=comments&do=findLastComment&parentId=' +ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
												
												return false;
											}
										}
										
										/* Just go away if still here */
										Effect.Fade( 'comment_id_' + ipb.comments.commentId, { 'duration': 0.6 } );
									}
								}
							}
						);
	},
	
	/**
	 * Show a pop-up for hide
	 */
	hidePop: function(e, elem)
	{
		Event.stop(e);
		
		var commentId = elem.up('.ipsComment').readAttribute('data-commentid');
		if( !commentId ){ return; }
		
		/* Create the pop-up */
		var popid   = 'pop__hide_popup_' + commentId;
		var content = ipb.templates['comment_hide'].evaluate( { 'commentId': commentId, url: elem.href } );
		
		ipb.comments.deletePopUps = new ipb.Popup( popid, {  type: 'balloon',
															 initial: content,
															 stem: true,
															 hideAtStart: false,
															 attach: { target: $('hide_comment_' + commentId ), position: 'auto', 'event': 'click' },
															 w: '550px' } );
		
		$('hidePop_reason').defaultize( ipb.lang['post_hide_reason_default'] );
		
		/* store for use later */
		ipb.comments.commentId = commentId;
	},
	
	/**
	 * Show quick edit
	*/
	editShow: function(e, elem)
	{
		if( DISABLE_AJAX )
		{
			return false;
		}
		
		// If user is holding ctrl or command, just submit since they
		// want to open a new tab (requested by Luke)
		if( e.ctrlKey == true || e.metaKey == true || e.keyCode == 91 )
		{
			return false;
		}
		
		Event.stop(e);
		var edit = [];
		
		edit['button'] = elem;
		if( !edit['button'] ){ return; }
		
		// Prevents loading the editor twice
		if( edit['button'].readAttribute('_editing') == '1' )
		{
			return false;
		}		
		
		edit['pid'] = edit['button'].id.replace('edit_comment_', '');
		edit['post'] = $( 'comment_id_' + edit['pid'] ).down('.comment_content');
		
		// Find post content
		ipb.comments.commentCache[ edit['pid'] ] = edit['post'].innerHTML;

		url = ipb.comments.data['ajaxShowEditUrl'] ? ipb.comments.data['ajaxShowEditUrl'] : ipb.vars['base_url'] + 'app=core&module=ajax&section=comments&do=showEdit&parentId=' +ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
		
		if ( Prototype.Browser.IE7 )
		{
			window.location = '#entry' + edit['pid'];
		}
		else
		{
			new Effect.ScrollTo( edit['post'], { offset: -100 } );
		}
		Debug.write( url );
		// DO TEH AJAX LOL
		new Ajax.Request( 	url, 
							{
								method: 'post',
								parameters: {
									md5check: 	ipb.vars['secure_hash'],
									comment_id:	edit['pid']
								},
								onSuccess: function(t)
								{
									if( t.responseText == 'nopermission' )
									{
										alert(ipb.lang['no_permission']);
										return;
									}
									if( t.responseText == 'error' || t.responseText.match( /^Error / ) )
									{
										alert(ipb.lang['no_permission']);
										return;
									}
									
									// Put it in
									edit['button'].writeAttribute('_editing', '1');
									
									edit['post'].update( t.responseText );
									
									edit['pid'] = 'e' + edit['pid'];

									// Set up events
									if( $('edit_save_' + edit['pid'] ) ){
										$('edit_save_' + edit['pid'] ).observe('click', ipb.comments.editSave );
									}
						
									if( $('edit_cancel_' + edit['pid'] ) ){
										$('edit_cancel_' + edit['pid'] ).observe('click', ipb.comments.editCancel );
									}
								}
							}
						);
		
		Debug.write( url );
	},
	
	/**
	 * Saves the contents of quick edit
	 */
	editSave: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		var postid = elem.id.replace('edit_save_e', '');
		if( !postid ){ return; }

		var Post = ipb.textEditor.getEditor('edit-' + postid).getText();
		
		if( Post.blank() )
		{
			alert( ipb.lang['post_empty'] );
			return;
		}
		
		var url = ipb.comments.data['ajaxSaveEditUrl'] ? ipb.comments.data['ajaxSaveEditUrl'] : ipb.vars['base_url'] + 'app=core&module=ajax&section=comments&do=saveEdit&parentId=' + ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								encoding: ipb.vars['charset'],
								parameters: {
									md5check: 			ipb.vars['secure_hash'],
									Post: 				Post.encodeParam(),
									comment_id:			postid
								},
								onSuccess: function(t)
								{
									if( t.responseJSON['error'] )
									{
										if( $('error_msg_e' + postid) )
										{
											$('error_msg_e' + postid).update( t.responseJSON['error'] );
											new Effect.BlindDown( $('error_msg_e' + postid), { duration: 0.4 } );
										}
										else
										{
											alert( t.responseJSON['error'] );
										}
										
										return false;
									}
									else
									{
										
										$('edit_comment_' + postid).writeAttribute('_editing', '0');
										
										$( 'comment_id_' + postid ).down('.comment_content').update( t.responseJSON['successString'] );
										
										if ( Prototype.Browser.IE7 )
										{
											window.location = '#entry' + edit['pid'];
										}
										else
										{
											new Effect.ScrollTo( $( 'comment_id_' + postid ).down('.comment_content'), { offset: -50 } );
										}
										
										ipb.textEditor.getEditor( 'edit-' + postid ).remove();

										prettyPrint();
									}
								}
							}
						);
	},
	
	/**
	 * Cancel the quick edit
	 * 
	 * @var		{event}		e		The event
	 */
	editCancel: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		var postid = elem.id.replace('edit_cancel_e', '');
		if( !postid ){ return; }
		
		if( ipb.comments.commentCache[ postid ] ){
			$( 'comment_id_' + postid ).down('.comment_content').update( ipb.comments.commentCache[ postid ] );
			$('edit_comment_' + postid).writeAttribute('_editing', '0');
			
			ipb.textEditor.getEditor( 'edit-' + postid ).remove();
		}
		
		return;
	},
	
	/**
	 * Saves the contents of comment
	*/
	add: function(e)
	{
		Event.stop(e);

		var content = ipb.textEditor.getEditor().getText();
		var isRte   = ipb.textEditor.getEditor().isRte();
		
		if( content.blank() )
		{
			alert( ipb.lang['post_empty'] );
			return;
		}
		
		/* Close editor */
		ipb.textEditor.getEditor().minimizeOpenedEditor();
		
		in_use = 0;
		
		var url = ipb.comments.data['ajaxSaveUrl'] ? ipb.comments.data['ajaxSaveUrl'] : ipb.vars['base_url'] + 'app=core&module=ajax&section=comments&do=add&parentId=' + ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];

		new Ajax.Request(	url,
							{
								method: 'post',
								encoding: ipb.vars['charset'],
								parameters: {
									md5check:			ipb.vars['secure_hash'],
									Post:				content.encodeParam(),
									comment_name:		$('comment_name') ? $('comment_name').value : '',
									isRte:				isRte
								},
								onSuccess: function(t)
								{
									if ( t.responseJSON && t.responseJSON['error'] )
									{
										if( t.responseJSON['error'] == 'comment_requires_approval' )
										{
											ipb.global.okDialogue( ipb.lang['comment_requires_approval'] );
										}
										else if( t.responseJSON['error'] == 'NO_COMMENT' )
										{
											ipb.global.errorDialogue( ipb.lang['post_empty'] );
										}
										else
										{
											ipb.global.errorDialogue( ipb.lang['no_permission'] );
										}
									}
									else if ( t.responseText && t.responseText != 'no_permission' )
									{
										/* Are we *NOT* on the last page? */
										if ( ! Object.isUndefined( ipb.comments.data ) && ! Object.isUndefined( ipb.comments.data['counts'] ) )
										{
											if ( ( ipb.comments.data['counts']['commentTotal'] ) && ( ( ipb.comments.data['counts']['commentTotal'] - ipb.comments.data['counts']['curStart'] ) >= ipb.comments.data['counts']['perPage'] ) )
											{ 
												/* http redirect */
												window.location = ipb.comments.data['findLastComment'] ? ipb.comments.data['findLastComment'] : ipb.vars['base_url'] + 'app=core&module=global&section=comments&do=findLastComment&parentId=' +ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
												
												return false;
											}
										}
										
										/* Fetch latest ID */
										latestId = 0;
										m        = t.responseText.match( /<a id='comment_(\d+?)'>/ );
										
										if ( m && m[1] )
										{
											latestId = m[1];
										}
										
										$('comment_wrap').insert( t.responseText );
										//Debug.write( 'inserted data' );
										ipb.comments.data['counts']['thisPageCount']++;
										
										/* animate, exterminate, germinate */
										if ( latestId > 0 && $('comment_id_' + latestId ) )
										{
											/* Add dark BG and fetch RGB value */
											$('comment_id_' + latestId ).addClassName( 'row2' );
											var startColor = $('comment_id_' + latestId ).getStyle( 'background-color' );
											
											/* Add light BG and fetch RGB value */
											$('comment_id_' + latestId ).removeClassName('row2').addClassName( 'row1' );
											var endColor    = $('comment_id_' + latestId ).getStyle( 'background-color' );
											var endBorderColor = $('comment_id_' + latestId ).getStyle( 'border-top-color' );
											
											/* Remove light BG */
											$('comment_id_' + latestId).removeClassName('row1').addClassName('row2');
											
											$('comment_id_' + latestId ).hide();
											new Effect.BlindDown( 'comment_id_' + latestId, { duration: 1.0, queue: 'front' } );
											//new Effect.Morph( 'comment_id_' + latestId, { 'style': 'border-top-color:' + endBorderColor, queue: 'end' } );
											//new Effect.Morph( 'comment_id_' + latestId, { 'style': 'background-color:' + endColor, queue: 'end', afterFinish: function() { $('comment_id_' + latestId ).removeClassName('row2').addClassName( 'row1' ); } } );
										}
										
										prettyPrint();
									}
								}
							}
						);
	},
	
	/**
	 * Show quick edit
	*/
	reply: function(e, elem)
	{
		if( DISABLE_AJAX )
		{
			return false;
		}
		
		// If user is holding ctrl or command, just submit since they
		// want to open a new tab (requested by Luke)
		if( e.ctrlKey == true || e.metaKey == true || e.keyCode == 91 )
		{
			return false;
		}
		
		Event.stop(e);
		var edit = [];
		Debug.write("Here");
		
		if ( ! elem ){ return; }
		
		commentId = elem.id.replace('reply_comment_', '');
		
		url = ipb.comments.data['ajaxFetchReplyUrl'] ? ipb.comments.data['ajaxFetchReplyUrl'] : ipb.vars['base_url'] + 'app=core&module=ajax&section=comments&do=fetchReply&parentId=' +ipb.comments.parentId + '&fromApp=' + ipb.comments.data['fromApp'];
		
		Effect.ScrollTo( $('fast_reply'), { offset: -100 } );
		
		Debug.write( url );
		// DO TEH AJAX LOL
		new Ajax.Request( 	url, 
							{
								method: 'post',
								parameters: {
									md5check: 	ipb.vars['secure_hash'],
									comment_id:	commentId
								},
								onSuccess: function(t)
								{
									if( t.responseText == 'nopermission' )
									{
										alert(ipb.lang['no_permission']);
										return;
									}
									if( t.responseText == 'error' )
									{
										alert(ipb.lang['action_failed']);
										return;
									}
									
									ipb.textEditor.getEditor('commentFastReply').insert( t.responseText );
								}
							}
						);
	},
	
	/**
	 * Show the comment link
	 */
	showLinkToComment: function(e, elem)
	{	
		_t = prompt( ipb.lang['copy_topic_link'], $( elem ).readAttribute('href') );
		Event.stop(e);
	},
	
	/**
	 * Toggles the multimod buttons in posts
	 * 
	 * @param	{event}		e		The event
	 * @param	{element}	elem	The element that fired
	*/
	toggleMultiquote: function(e, elem)
	{
		Event.stop(e);
		
		// Get list of already quoted posts
		try {
			quoted = ipb.Cookie.get('gal_pids').split(',').compact();
		} catch(err){
			quoted = $A();
		}
		
		id = elem.id.replace('multiq_', '');
		
		// Hokay, are we selecting/deselecting?
		if( elem.hasClassName('selected') )
		{
			elem.removeClassName('selected');
			quoted = quoted.uniq().without( id ).join(',');
		}
		else
		{
			elem.addClassName('selected');
			quoted.push( id );
			quoted = quoted.uniq().join(',');
		}
		
		// Save cookie
		ipb.Cookie.set('gal_pids', quoted, 0);
	},
	
	/**
	 * Check the files we've selected
	 */
	preCheckComments: function()
	{
		if( $('selectedgcids') )
		{
			var topics = $F('selectedgcids').split(',');
		}
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;

		if( topics )
		{
			topics.each( function(check){
				if( check != '' )
				{
					if( $('pid_' + check ) )
					{
						checkedOnPage++;
						$('pid_' + check ).checked = true;
					}
					
					ipb.comments.totalChecked++;
				}
			});
		}
		
		$$('.comment_mod').each( function(check){
			checkboxesOnPage++;
		} );
		
		if( $('comments_all') )
		{
			if( checkedOnPage == checkboxesOnPage )
			{
				$('comments_all').checked = true;
			}
		}
		
		ipb.comments.updateModButton();
	},
	
	/**
	 * Confirm they want to delete stuff
	 * 
	 * @var 	{event}		e	The event
	*/
	checkComment: function(e, elem)
	{
		remove = new Array();
		check = elem;
				
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;
		
		if( check.checked == true )
		{
			Debug.write("Checked");
			ipb.comments.totalChecked++;
						
			switch ( check.readAttribute("data-status") )
			{
				case '0':
					ipb.comments.modOptionsUnapproved += 1;
					break;
					
				case '-1':
					ipb.comments.modOptionsHidden += 1;
					break;
					
				default:
					ipb.comments.modOptionsUnhidden += 1;
					break;
			}
		}
		else
		{
			remove.push( check.id.replace('pid_', '') );
			ipb.comments.totalChecked--;
			
			switch ( check.readAttribute("data-status") )
			{
				case '0':
					ipb.comments.modOptionsUnapproved -= 1;
					break;
					
				case '-1':
					ipb.comments.modOptionsHidden -= 1;
					break;
					
				default:
					ipb.comments.modOptionsUnhidden -= 1;
					break;
			}
		}
		
		$$('.comment_mod').each( function(check){
			checkboxesOnPage++;
			
			if( $(check).checked == true )
			{
				checkedOnPage++;
			}
		} );
		
		if( $('comments_all') )
		{
			if( checkedOnPage == checkboxesOnPage )
			{
				$('comments_all').checked = true;
			}
			else
			{
				$('comments_all' ).checked = false;
			}
		}
		
		ipb.comments.moderate();
	},
	
	/**
	 * Update the moderation button
	 */
	updateModButton: function( )
	{
		if( $('mod_submit') )
		{
			if( ipb.comments.totalChecked == 0 ){
				$('mod_submit').disabled = true;
			} else {
				$('mod_submit').disabled = false;
			}
		
			$('mod_submit').value = ipb.lang['with_selected'].replace('{num}', ipb.comments.totalChecked);
		}
	},
	
	/**
	 * Sets the supplied post to hidden
	 * 
	 * @var		{int}	id		The ID of the post to hide
	*/
	setCommentHidden: function(id)
	{
		if( $( 'comment_id_' + id ).select('.post_wrap')[0] )
		{
			$( 'comment_id_' + id ).select('.post_wrap')[0].hide();
			
			if( $('unhide_post_' + id ) )
			{
				$('unhide_post_' + id).observe('click', ipb.comments.showHiddenComment );
			}
		}
	},
	
	/**
	 * Unhides the supplied post
	 * 
	 * @var		{event}		e	The link event
	*/
	showHiddenComment: function(e)
	{
		link = Event.findElement(e, 'a');
		id = link.id.replace('unhide_post_', '');
		
		if( $('comment_id_' + id ).select('.post_wrap')[0] )
		{
			elem = $('comment_id_' + id ).select('.post_wrap')[0];
			new Effect.Parallel( [
				new Effect.BlindDown( elem ),
				new Effect.Appear( elem )
			], { duration: 0.5 } );
		}
		
		if( $('comment_id_' + id ).select('.post_ignore')[0] )
		{
			elem = $('comment_id_' + id ).select('.post_ignore')[0];
			/*new Effect.BlindUp( elem, {duration: 0.2} );*/
			elem.hide();
		}
		
		Event.stop(e);
	}
};

ipb.comments.init();