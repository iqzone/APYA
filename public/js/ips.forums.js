/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.forums.js - Forum view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _forums = window.IPBoard;

_forums.prototype.forums = {
	totalChecked: 0,
	showMod: [],
	fetchMore: {},
	modOptionsUnapproved: 0,
	modOptionsPinned: 0,
	modOptionsUnpinned: 0,
	modOptionsLocked: 0,
	modOptionsUnlocked: 0,
	modOptionsHidden: 0,
	modOptionsNotHidden: 0,
	
	init: function()
	{
		Debug.write("Initializing ips.forums.js");

		document.observe("dom:loaded", function(){
			ipb.forums.initEvents();
		});
	},
	initEvents: function()
	{
		if( $('forum_load_more') ){
			$('forum_load_more').observe('click', ipb.forums.loadMore);
			$('more_topics').show();
		}
		
		/* Set up mod checkboxes */
		if( $('tmod_all') )
		{ 
			ipb.forums.preCheckTopics();
			$('tmod_all').observe( 'click', ipb.forums.checkAllTopics );
		}
		
		ipb.delegate.register(".topic_mod", ipb.forums.checkTopic );
		ipb.delegate.register(".t_rename", ipb.forums.topicRename );
		ipb.delegate.register('.t_delete', ipb.forums.topicDeletePopUp );
	},
	
	
	loadMore: function(e)
	{
		Event.stop(e);		
		if( !ipb.forums.fetchMore ){ return; }
				
		ipb.forums.fetchMore['st'] = parseInt(ipb.forums.fetchMore['st']) + parseInt(ipb.forums.fetchMore['max_topics']);
		
		var url = ipb.vars['base_url'] + "app=forums&module=ajax&section=forums&do=getTopics&md5check="+ipb.vars['secure_hash'] + "&" + $H(ipb.forums.fetchMore).toQueryString();
		Debug.write( url );
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t){
								Debug.dir( t.responseJSON );
								if( t.responseJSON['topics'] == '' )
								{
									$('forum_load_more').replace("<span class='desc lighter'>"+ipb.lang['no_more_topics']+"</span>");
									return;
								}
								$$('tbody.dynamic_update').invoke("removeClassName", "dynamic_update");
								var newtbody = new Element("tbody", {'class': 'dynamic_update'});
								$('forum_table').select('tbody:last')[0].insert({ 'after': newtbody});
								newtbody.update( t.responseJSON['topics'] );
								$$('.pagination').invoke("replace", t.responseJSON['pages']);
								
								// Tooltips
								$$("tbody.dynamic_update [data-tooltip]").invoke('tooltip');
								
								// General click handlers
								$$("tbody.dynamic_update [data-clicklaunch]").invoke('clickLaunch');
								
								if( t.responseJSON['topics'] == '' || t.responseJSON['hasMore'] == false)
								{
									$('forum_load_more').replace("<span class='desc lighter'>"+ipb.lang['no_more_topics']+"</span>");
									return;
								}
								else
								{
									/* Reset the 'all' checkbox */
									if ( $('tmod_all') )
									{
										$('tmod_all').checked = false;
									}
								}
							}
						});
	},
	
	submitModForm: function(e)
	{
		// Check for delete action
		if( $F('mod_tact') == 'delete' ){
			if( !confirm( ipb.lang['delete_confirm'] ) ){
				Event.stop(e);
			}
		}
		
		// Check for merge action
		if( $F('mod_tact') == 'merge' ){
			if( !confirm( ipb.lang['delete_confirm'] ) ){
				Event.stop(e);
			}
		}
	},

	topicRename: function(e, elem)
	{
		if( DISABLE_AJAX ){
			return false;
		}
		
		Event.stop(e);
		
		// We need to find the topic concerned
		var link = $(elem).up('.__topic').down('a.topic_title');
		if( $( link ).readAttribute('showingRename') == 'true' ){ return; }
		
		// Create elements
		var temp = ipb.templates['topic_rename'].evaluate( { 	inputid: link.id + '_input',
																submitid: link.id + '_submit',
																cancelid: link.id + '_cancel',
																value: link.innerHTML.unescapeHTML().replace( /'/g, "&#039;" ).replace( /</g, "&lt;" ).replace( />/g, "&gt;" ).trim() } );
																
		$( link ).insert( { before: temp } );
		
		// Event handlers
		$( link.id + '_input' ).observe('keydown', ipb.forums.saveTopicRename );
		$( link.id + '_submit' ).observe('click', ipb.forums.saveTopicRename );
		$( link.id + '_cancel' ).observe('click', ipb.forums.cancelTopicRename );
		
		// Select and highlight
		$( link.id + '_input' ).focus();
		
		$( link ).hide().writeAttribute('showingRename', 'true');
	},
	
	cancelTopicRename: function(e)
	{
		var elem = Event.element(e);
		if( !elem.hasClassName( '_cancel' ) )
		{
			elem = Event.findElement(e, '.cancel');
		}
		
		try {
			var tid = elem.up('tr').id.replace( 'trow_', '' );
		} catch(err){ Debug.write( err ); return; }
		
		var linkid = 'tid-link-' + tid;
		
		if( $(linkid + '_input') ){ 
			$( linkid + '_input' ).remove();
		}
		
		if( $( linkid + '_submit' ) ){
			$( linkid + '_submit' ).remove();
		}
		
		$( linkid + '_cancel' ).remove();
		
		$( linkid ).show().writeAttribute('showingRename', false);
		
		Event.stop(e);		
	},
	
	saveTopicRename: function(e)
	{
		elem = Event.element(e);
		if( e.type == 'keydown' )
		{
			if( e.which != Event.KEY_RETURN )
			{
				return;
			}
		}
		
		try {
			tid = elem.up('tr').id.replace( 'trow_', '' );
		} catch(err){ Debug.write( err ); return; }
				
		new Ajax.Request( ipb.vars['base_url'] + "app=forums&module=ajax&section=topics&do=saveTopicTitle&md5check="+ipb.vars['secure_hash']+'&tid='+tid,
						{
							method: 'post',
							evalJSON: 'force',
							parameters: {
								'name': $F('tid-link-' + tid + '_input').replace( /&#039;/g, "'" ).replace( /\\/g, '\\\\' ).encodeParam()
							},
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] );
								}
								else if ( t.responseJSON['error'] )
								{
									alert( ipb.lang['error'] + ": " + t.responseJSON['error'] );
								}
								else
								{
									$('tid-link-' + tid ).update( t.responseJSON['title'] );
									$('tid-link-' + tid ).href = t.responseJSON['url'];
								}
								
								$('tid-link-' + tid + '_input').hide().remove();
								$('tid-link-' + tid + '_submit').hide().remove();
								$('tid-link-' + tid + '_cancel').hide().remove();
								$('tid-link-' + tid).show().writeAttribute('showingRename', false);
							}
						});
	},

	preCheckTopics: function()
	{	
		if( !$('selectedtids') ){ return; }
		
		topics = $F('selectedtids').split(',');
		
		if( topics )
		{
			topics.each( function(check){
				if( check != '' )
				{
					if( $('tmod_' + check ) )
					{
						$('tmod_' + check ).checked = true;
					}
					
					ipb.forums.totalChecked++;
				}
			});
		}
		
		ipb.forums.updateTopicModButton();
	},
	
	checkAllTopics: function(e)
	{
		Debug.write('checkAllTopics');
		check = Event.findElement(e, 'input');
		toCheck = $F(check);
		ipb.forums.totalChecked = 0;
		toRemove = new Array();
		selectedTopics = $F('selectedtids').split(',').compact();
		
		$$('.topic_mod').each( function(check){
			if( toCheck != null )
			{
				check.checked = true;
				selectedTopics.push( check.id.replace('tmod_', '') );
				ipb.forums.totalChecked++;
			}
			else
			{
				toRemove.push( check.id.replace('tmod_', '') );
				check.checked = false;
			}
		});
		
		selectedTopics = selectedTopics.uniq();
		
		if( toRemove.length >= 1 )
		{
			for( i=0; i<toRemove.length; i++ )
			{
				//selectedTopics = selectedTopics.without( parseInt( toRemove[i] ) );
				
				idToBeRemoved = parseInt( toRemove[i] );
				
				if ( ! isNaN( idToBeRemoved ) )
				{
					// without not so hot in Chrome
					_selectedTopics = selectedTopics;
					selectedTopics  = $A();
					
					_selectedTopics.each( function( item )
					{
						if ( idToBeRemoved != item )
						{
							selectedTopics.push( item );
						}
					} );
				}
			}
		}
		
		selectedTopics = selectedTopics.join(',');
		ipb.Cookie.set('modtids', selectedTopics, 0);
		
		$('selectedtids').value = selectedTopics;
		
		ipb.forums.updateTopicModButton();
	},
	
	checkTopic: function(e)
	{
		checkAll = true;
		remove	 = new Array();
		check	 = Event.findElement( e, 'input' );
		selectedTopics = $F('selectedtids').split(',').compact();
		
		if( check.checked == true )
		{
			selectedTopics.push( check.id.replace('tmod_', '') );
			ipb.forums.totalChecked++;
						
			if ( check.readAttribute( "data-approved" ) == 1 )
			{
				ipb.forums.modOptionsNotHidden++;
			}
			else if ( check.readAttribute( "data-approved" ) == 0 )
			{
				ipb.forums.modOptionsUnapproved++;
			}
			else if ( check.readAttribute( "data-approved" ) == -1 )
			{
				ipb.forums.modOptionsHidden++;
			}
			
			if ( check.readAttribute( "data-open" ) == 1 )
			{
				ipb.forums.modOptionsUnlocked++;
			}
			else
			{
				ipb.forums.modOptionsLocked++;
			}
			
			if ( check.readAttribute( "data-pinned" ) == 1 )
			{
				ipb.forums.modOptionsPinned++;
			}
			else
			{
				ipb.forums.modOptionsUnpinned++;
			}
			
			/* Re-check if all checkboxes are chosen or not */
			$$('.topic_mod').each( function(modCheck){
				if( checkAll && ! modCheck.checked )
				{
					checkAll = false;
				}
			});
		}
		else
		{
			checkAll = false;
			remove.push( check.id.replace('tmod_', '') );
			ipb.forums.totalChecked--;
			
			if ( check.readAttribute( "data-approved" ) == 1 )
			{
				ipb.forums.modOptionsNotHidden--;
			}
			else if ( check.readAttribute( "data-approved" ) == 0 )
			{
				ipb.forums.modOptionsUnapproved--;
			}
			else if ( check.readAttribute( "data-approved" ) == -1 )
			{
				ipb.forums.modOptionsHidden--;
			}
			
			if ( check.readAttribute( "data-open" ) == 1 )
			{
				ipb.forums.modOptionsUnlocked--;
			}
			else
			{
				ipb.forums.modOptionsLocked--;
			}
			
			if ( check.readAttribute( "data-pinned" ) == 1 )
			{
				ipb.forums.modOptionsPinned--;
			}
			else
			{
				ipb.forums.modOptionsUnpinned--;
			}
		}
		
		/* without not so hot in Chrome */
		_selectedTopics = selectedTopics;
		selectedTopics  = $A();
		
		_selectedTopics.each( function( item )
		{
			ok = true;
			
			remove.each( function( ritem )
			{
				if ( ritem == item )
				{
					ok = false;
				}
			} )
			
			if ( ok === true )
			{
				selectedTopics.push( item );
			}
		} );
			
		selectedTopics = selectedTopics.uniq().join(',');
				
		ipb.Cookie.set('modtids', selectedTopics, 0);
		
		$('selectedtids').value = selectedTopics;
		
		$('tmod_all').checked = checkAll;

		ipb.forums.updateTopicModButton();
	},
	
	updateTopicModButton: function( )
	{
		/* Do we have any checked? */
		if( ipb.forums.totalChecked > 0 )
		{
			/* Yes! Have we loaded in the HTML for the box? */
			if( !$('comment_moderate_box') )
			{
				/* No? Then do it! */
				$$('body')[0].insert({'bottom': ipb.templates['topic_moderation'].evaluate({count: ipb.forums.totalChecked}) });
				
				/* And set the action for the submit button */
				$('submitModAction').on('click', ipb.forums.doModerate);
			}
			else
			{
				/* Yes, just update the number of checked boxes */
				$('comment_count').update( ipb.forums.totalChecked );
			}
			
			/* And show the box */
			if( !$('comment_moderate_box').visible() )
			{
				new Effect.Appear( $('comment_moderate_box'), { duration: 0.3 } );
			}
			
			/* Update the available options */
			$('tactInPopup').select('option').invoke('remove');

			if ( ipb.forums.modOptionsUnapproved )
			{
				$('tactInPopup').insert( new Element('option', { value: 'approve' } ).update( ipb.lang['cpt_approve_f'] ) );
			}
			
			if ( ipb.forums.modOptionsUnpinned )
			{
				$('tactInPopup').insert( new Element('option', { value: 'pin' } ).update( ipb.lang['cpt_pin_f'] ) );
			}
			
			if ( ipb.forums.modOptionsPinned )
			{
				$('tactInPopup').insert( new Element('option', { value: 'unpin' } ).update( ipb.lang['cpt_unpin_f'] ) );
			}
			
			if ( ipb.forums.modOptionsLocked )
			{
				$('tactInPopup').insert( new Element('option', { value: 'open' } ).update( ipb.lang['cpt_open_f'] ) );
			}
			if ( ipb.forums.modOptionsUnlocked )
			{
				$('tactInPopup').insert( new Element('option', { value: 'close' } ).update( ipb.lang['cpt_close_f'] ) );
			}
			
			$('tactInPopup').insert( new Element('option', { value: 'move' } ).update( ipb.lang['cpt_move_f'] ) );
			$('tactInPopup').insert( new Element('option', { value: 'merge' } ).update( ipb.lang['cpt_merge_f'] ) );
			
			if ( ipb.forums.modOptionsNotHidden )
			{
				$('tactInPopup').insert( new Element('option', { value: 'delete' } ).update( ipb.lang['cpt_hide_f'] ) );
			}
			if ( ipb.forums.modOptionsHidden )
			{
				$('tactInPopup').insert( new Element('option', { value: 'sundelete' } ).update( ipb.lang['cpt_unhide_f'] ) );
			}
			
			$('tactInPopup').insert( new Element('option', { value: 'deletedo' } ).update( ipb.lang['cpt_delete_f'] ) );
			
			$('multiModOptions').select('option').each( function( item ){
				$('tactInPopup').insert( $( item ).clone().update( item.innerHTML ) );
			});
		}
		else
		{
			/* No - get rid of the box */
			if( $('comment_moderate_box') )
			{
				new Effect.Fade( $('comment_moderate_box'), { duration: 0.3 } );
			}
		}
	},
	
	doModerate: function()
	{
		if ( ipb.forums.totalChecked > 0 )
		{
			$('tact').value = $('tactInPopup').value;
			$('modform').submit();
		}
	},
	
	retrieveAttachments: function( id )
	{
		url = ipb.vars['base_url'] + "&app=forums&module=ajax&secure_key=" + ipb.vars['secure_hash'] + '&section=attachments&tid=' + id;
		popup = new ipb.Popup( 'attachments', { type: 'pane', modal: false, w: '500px', h: '600px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	retrieveWhoPosted: function( tid )
	{
		if( parseInt(tid) == 0 )
		{
			return false;
		}
		
		url = ipb.vars['base_url'] + "&app=forums&module=ajax&secure_key=" + ipb.vars['secure_hash'] + '&section=stats&do=who&t=' + tid;
		popup = new ipb.Popup( 'whoPosted', { type: 'pane', modal: false, w: '500px', h: 400, ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	/**
	 * Show a pop-up for topic delete
	 */
	topicDeletePopUp: function(e, elem)
	{	
		/* First off, fix URLs */
		var _url_delete = '';
		var _permaShow  = '';
		Event.stop(e);
		
		// Get popup contents
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;secure_key=" + ipb.vars['secure_hash'] + "&amp;template_group=forum&amp;template_bit=deleteTopic&amp;lang_module=forums&amp;lang_app=forums";
		
		new Ajax.Request(	url.replace(/&amp;/g, '&'),
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									/* Create the pop-up */
									var popid   = 'pop__topic_delete_popup';
									var content = new Template( t.responseJSON['html'] ).evaluate( { deleteUrl: elem.href } );
									
									new ipb.Popup( popid, { type: 'pane',
																					stem: true,
																					 modal: true,
																					 initial: content,
																					 hideAtStart: false,
																					 w: '350px' } );
								}
							}
						);
	}
};

ipb.forums.init();