/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.profile.js - Forum view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _profile = window.IPBoard;

_profile.prototype.profile = {
	activeTab: '',
	viewingProfile: 0,
	customization: 0,
	defaultComment: '',
	statusInit: false,
	
	init: function()
	{
		Debug.write("Initializing ips.profile.js");
		
		document.observe("dom:loaded", function(){
			ipb.profile.initEvents();
			
			/* Profile pics */
			if ( ipb.profile.customization )
			{
				ipb.profile.updateBgImage();
			}
		});
	},
	
	/* ------------------------------ */
	/**
	 * Initialize events for the profile page
	*/
	initEvents: function()
	{
		if( $('friend_toggle') ){
			$('friend_toggle').observe('click', ipb.profile.toggleFriendStatus );
		}
		
		if( $('dname_history') ){
			$('dname_history').observe('click', ipb.profile.showDNameHistory );
		}
		
		if( $('view-all-friends') ){
			$('view-all-friends').observe('click', ipb.profile.retrieveFriends );
		}
		
		ipb.delegate.register('.delete_comment', ipb.profile.deleteComment );
		ipb.delegate.register('.tab_toggle', ipb.profile.changeTabContent );
		
		/*ipb.delegate.register('.bbc_spoiler_show', ipb.global.toggleSpoiler);*/
	},
	
	changeTabContent: function(e, elem)
	{
		Event.stop(e);
		var id = elem.id.replace('tab_link_', '');
		if( !id || id.blank() ){ return; }
		
		if( !$('pane_' + id) )
		{
			new Ajax.Request( ipb.vars['base_url'] + 'app=members&section=load&module=ajax&member_id=' + ipb.profile.viewingProfile + '&tab=' + id + '&md5check=' + ipb.vars['secure_hash'],
							{
								method: 'post',
								onSuccess: function(t)
								{
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
										return;
									}
									
									if( t.responseText != 'error' )
									{
										var newdiv = new Element('div', { 'id': 'pane_' + id } ).hide().update( t.responseText );
										$('profile_panes_wrap').insert( newdiv );
										
										ipb.profile.togglePanes( id );
										
										if( id == 'members:status' && !ipb.profile.statusInit ){
											ipb.status.initEvents();
											ipb.profile.statusInit = true;
										}
									}
									else
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
								}
							});
		}
		else
		{
			ipb.profile.togglePanes( id );
		}
		
	},
	
	togglePanes: function( newid )
	{
		var currentID = $('profile_tabs').select(".active")[0].id.replace('tab_link_', '');
		var currentPane = $('pane_' + currentID);
		
		var newPane = $('pane_' + newid);
		
		var curHeight = $(currentPane).measure('height');
		var newHeight = $(newPane).measure('height');
		
		// Hide current one
		$('profile_panes_wrap').setStyle( { height: curHeight + "px" } );
		$( currentPane ).absolutize();
		new Effect.Fade( $( currentPane ), { duration: 0.2 } );
		
		// Resize container
		new Effect.Morph( $('profile_panes_wrap'), { style: 'height: ' + newHeight + 'px', duration: 0.2, afterFinish: function(){
			new Effect.Appear( newPane, { duration: 0.2, afterFinish: function(){
				ipb.profile.executeJavascript( $( newPane ) );
				$('profile_panes_wrap').setStyle( { height: 'auto' } );
				$( currentPane ).setStyle( { position: 'static', height: 'auto' } );
			} } );
		} } );
		
		$('profile_tabs').select(".tab_toggle").invoke("removeClassName", "active");
		$('tab_link_' + newid).addClassName('active');
	},
	
	/**
	 * Resize and set BG image
	 */
	updateBgImage: function()
	{
		var main = $('main_profile_body');
		
		if ( $('userBg') )
		{
			$('userBg').setStyle( { 'height': main.getHeight() + 'px' } );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Retrieve all of a member's friends
	 * 
	 * @param	{event}		e		The event
	*/
	retrieveFriends: function(e)
	{
		Event.stop(e);
		link	= Event.findElement(e, 'a');
		href	= link.href.replace( /module=profile/, 'module=ajax' );
		
		new Ajax.Request( href,
						{
							method: 'post',
							parameters: { md5check: ipb.vars['secure_hash'] },
							onSuccess: function(t)
							{
								$('friend_list').innerHTML = t.responseText;
								Debug.write( t.responseText);
								/* if we have an opaque bg, make it fit */
								$('userBg').setStyle( { 'height': $('main_profile_body').getHeight() + 'px' } );
							}
						});

		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Responds to Enter and Esc keys
	*/
	watchForKeypress: function(e)
	{
		if( e.which == Event.KEY_RETURN )
		{
			ipb.profile.saveStatus( e );
		}
		
		if( e.keyCode == Event.KEY_ESC )
		{
			ipb.profile.cancelStatus( e );
		}		
	},

	
	/* ------------------------------ */
	/**
	 * Shows the display name history popup
	 * 
	 * @param	{event}		e		The event
	*/
	showDNameHistory: function(e)
	{		
		var mid = ipb.profile.viewingProfile;
		
		if( parseInt(mid) == 0 )
		{
			return false;
		}
		
		Event.stop(e);
		
		var _url 		= ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=dname&id=' + mid;
		warnLogs = new ipb.Popup( 'dnamehistory', {type: 'pane', modal: true, w: '500px', h: '500px', ajaxURL: _url, hideAtStart: false, close: '.cancel' } );
	},
	
	/* ------------------------------ */
	/**
	 * Adds/Removes a friend
	 * 
	 * @param	{event}		e		The event
	*/
	toggleFriendStatus: function(e)
	{
		Event.stop(e);
		
		// Are they a friend?
		if( ipb.profile.isFriend ){
			urlBit = "remove";
		} else {
			urlBit = "add";
		}
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=friends&module=ajax&do=" + urlBit + "&member_id=" + ipb.profile.viewingProfile + "&md5check=" + ipb.vars['secure_hash'],
						{
							method: 'post',
							onSuccess: function(t)
							{
								switch( t.responseText )
								{
									case 'pp_friend_timeflood':
										alert(ipb.lang['cannot_readd_friend']);
										Event.stop(e);
										break;
									case "pp_friend_already":
										alert(ipb.lang['friend_already']);
										break;
									case "error":
										alert(ipb.lang['action_failed']);
										break;
									default:
									 	if ( ipb.profile.isFriend ) { 
											ipb.profile.isFriend = false;
											newShow = ipb.templates['add_friend'];
										} else {
											ipb.profile.isFriend = true;
											newShow = ipb.templates['remove_friend'];
										}
										
										$('friend_toggle').update( newShow );
									break;
								}
							}
						});
	},
											
	/* ------------------------------ */
	/**
	 * Executes IPBs post handling JS for the topic/post tabs
	 * 
	 * @param	{element}	wrapper		The wrapper to look in
	*/
	executeJavascript: function( wrapper )
	{
		//Code highlighting
		//dp.SyntaxHighlighter.HighlightAll('bbcode_code');
		prettyPrint();
		
	}
};

ipb.profile.init();