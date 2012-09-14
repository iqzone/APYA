/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.archive.js - Archive javascript 			*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Matt Mecham							*/
/************************************************/

IPBoard.prototype.ACPArchive = {
	inSection: null,
	ruleData: null,
	keys: [ 'approved', 'forum', 'member', 'pinned', 'poll', 'post', 'rating', 'lastpost', 'state', 'view' ],
	lastBlur: 0,
	ajaxPending: false,
	popUps: {},
	autoComplete: null,
	
	init: function()
	{
		Debug.write("Initializing acp.archive.js");
		
		document.observe("dom:loaded", function()
		{
			/* Set up the handles */
			if ( this.inSection == 'rules' )
			{
				this.setUpRulesPage();
			}
		}.bind(this) );
	},
	
	/**
	 * Remove a member
	 * @param e
	 * @param elem
	 */
	deleteMember: function(elem, e)
	{
		Event.stop(e);
		
		var type = elem.readAttribute('data-type');
		var id   = elem.readAttribute('data-id');
		
		var url = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=deleteMember&type=' + type + '&id=' + id + '&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
		
		Debug.write( url );
		new Ajax.Request( url,
				{
					method: 'post',
					hideLoader: true,
					evalJSON: 'force',
					onSuccess: function(t)
					{
						if ( t.responseJSON )
						{
							Debug.write( t.responseJSON );
														
							this._updateMembers( t.responseJSON['data'], t.responseJSON['count'], type );
							$(type + '_field_member_text').value = t.responseJSON['ids'];
							
							/* Update stats */
							this.lastBlur = this.getUnixTime();
							this.updateStats();
						}
					}.bind(this)
				} );
	},
	/**
	 * Save the dialog box
	 * @param e
	 * @param elem
	 */
	saveAddMemberDialog: function(elem, e)
	{
		Event.stop(e);
		
		var type = elem.readAttribute('data-type');
		
		var url = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=saveAddMemberDialog&type=' + type + '&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
		
		Debug.write( url );
		new Ajax.Request( url,
				{
					method: 'post',
					hideLoader: true,
					parameters: { addName: $F('addName') },
					evalJSON: 'force',
					onSuccess: function(t)
					{
						/* close box */
						this.popUps['lmd'].hide();
						
						if ( t.responseJSON )
						{
							Debug.write( t.responseJSON );
														
							this._updateMembers( t.responseJSON['data'], t.responseJSON['count'], type );
							$(type + '_field_member_text').value = t.responseJSON['ids'];
							
							/* Update stats */
							this.lastBlur = this.getUnixTime();
							this.updateStats();
						}
					}.bind(this)
				} );
	},
	
	/**
	 * Show the add member dialog box
	 * @param e
	 * @param elem
	 */
	launchAddMemberDialog: function(elem, e)
	{
		Event.stop(e);
		
		if ( ! Object.isUndefined( this.popUps['lmd'] ) )
		{
			//this.popUps['lmd'].kill();
		}
		
		var type = elem.readAttribute('data-type');
		
		var url = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=showAddMemberDialog&type=' + type + '&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
		
		Debug.write( url );
		
		new Ajax.Request( url,
				{
					method: 'post',
					hideLoader: true,
					onSuccess: function(t)
					{
						if ( t.responseText )
						{
							/* Create the pop-up */
							if ( ! Object.isUndefined( this.popUps['lmd'] ) )
							{
								this.popUps['lmd'].show();
								$('addName').value = '';
							}
							else
							{
								this.popUps['lmd'] = new ipb.Popup( 'lmd', { type: 'pane',
																			 modal: true,
																			 initial: t.responseText,
																		     hideAtStart: false,
																			 w: '550px' } );
								
								setTimeout( function ()
										{
											this.autoComplete = new ipb.Autocomplete( $('addName'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
										}.bind(this), 1000 );
							}
						}
					}.bind(this)
				} );
	},
	
	/**
	 * Save the dialog box
	 * @param e
	 * @param elem
	 */
	saveAddForumDialog: function(elem, e)
	{
		Event.stop(e);
		
		var type = elem.readAttribute('data-type');
		
		var url = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=saveAddForumDialog&type=' + type + '&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
		
		Debug.write( url );
		Debug.dir( $F('forumIds') );
		new Ajax.Request( url,
				{
					method: 'post',
					hideLoader: true,
					parameters: $('forumIds').serialize(true),
					evalJSON: 'force',
					onSuccess: function(t)
					{
						/* close box */
						this.popUps['lfd'].hide();
						
						if ( t.responseJSON )
						{
							Debug.write( t.responseJSON );
														
							this._updateForums( t.responseJSON['data'], type );
							$(type + '_field_forum_text').value = t.responseJSON['ids'];
							
							/* Update stats */
							this.lastBlur = this.getUnixTime();
							this.updateStats();
						}
					}.bind(this)
				} );
	},
	
	/**
	 * Show the add forum dialog box
	 * @param e
	 * @param elem
	 */
	launchAddForumDialog: function(elem, e)
	{
		Event.stop(e);
		
		var type = elem.readAttribute('data-type');
		
		var url = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=showAddForumDialog&type=' + type + '&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
		
		Debug.write( url );
		
		new Ajax.Request( url,
				{
					method: 'post',
					hideLoader: true,
					onSuccess: function(t)
					{
						if ( t.responseText )
						{
							/* Create the pop-up */
							this.popUps['lfd'] = new ipb.Popup( 'lfd', { type: 'pane',
																		 modal: true,
																		 initial: t.responseText,
																	     hideAtStart: false,
																		 w: '550px' } );
						}
					}.bind(this)
				} );
	},
	
	/**
	 * Fetch dialog to set unarchive preferences
	 * 
	 */
	showRestorePrefs: function()
	{
		var url = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=showRestoreDialog&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
		
		Debug.write( url );
		
		new Ajax.Request( url,
				{
					method: 'post',
					hideLoader: true,
					onSuccess: function(t)
					{
						if ( t.responseText )
						{
							/* Create the pop-up */
							new ipb.Popup( 'rpd', { type: 'pane',
													modal: true,
													initial: t.responseText,
												    hideAtStart: false,
													w: '550px' } );
						}
					}.bind(this)
				} );
	},
	
	/**
	 * Set up rules page
	 */
	setUpRulesPage: function()
	{
		Debug.dir( this.ruleData );
		
		/* Set current values */
		$H( this.ruleData['archive'] ).each( function( data )
		{
			var _key = data.key;
			var _val = data.value;

			this._setUpField( _key, _val, true );
		}.bind(this) );
		
		$H( this.ruleData['skip'] ).each( function( data )
		{
			var _key = data.key;
			var _val = data.value;

			this._setUpField( _key, _val, false );
		}.bind(this) );
		
		/* Got anything for forums? */
		if ( ! Object.isUndefined( this.ruleData['archive']['forum']['_parseData'] ) )
		{
			this._updateForums( this.ruleData['archive']['forum']['_parseData'], 'archive' );
			$('archive_field_forum_text').value = this.ruleData['archive']['forum']['text'];
		}
		
		/* Got anything for members? */
		if ( ! Object.isUndefined( this.ruleData['archive']['member']['_parseData'] ) )
		{
			this._updateMembers( this.ruleData['archive']['member']['_parseData']['data'], this.ruleData['archive']['member']['_parseData']['count'], 'archive' );
			$('archive_field_member_text').value = this.ruleData['archive']['member']['text'];
		}
		
		/* set up blurs */
		this.keys.each( function(key)
		{
			if ( $( 'archive_field_' + key ) )
			{
				$( 'archive_field_' + key ).on('blur', this._blur.bindAsEventListener(this) );
			}
			
			if ( $( 'archive_field_' + key + '_text' ) )
			{
				$( 'archive_field_' + key + '_text' ).on('blur', this._blur.bindAsEventListener(this) );
			}
			
			if ( $( 'archive_field_' + key + '_unit' ) )
			{
				$( 'archive_field_' + key + '_unit' ).on('blur', this._blur.bindAsEventListener(this) );
			}
			
			if ( $( 'skip_field_' + key ) )
			{
				$( 'skip_field_' + key ).on('blur', this._blur.bindAsEventListener(this) );
			}
			
			if ( $( 'skip_field_' + key + '_text' ) )
			{
				$( 'skip_field_' + key + '_text' ).on('blur', this._blur.bindAsEventListener(this) );
			}
			
			if ( $( 'skip_field_' + key + '_unit' ) )
			{
				$( 'skip_field_' + key + '_unit' ).on('blur', this._blur.bindAsEventListener(this) );
			}
		}.bind(this) );
		
		setInterval( function() { this.updateStats() }.bind(this), 1000 );
	},
	
	/**
	 * Number of seconds since epoch aka unixtime
	 * @returns int
	 */
	getUnixTime: function()
	{
		return Math.round( new Date().getTime() / 1000 );
	},
	
	/**
	 * Update stats
	 */
	updateStats: function()
	{
		if ( this.lastBlur > 0 && ( ( this.getUnixTime() - this.lastBlur ) <= 20 ) )
		{
			if ( this.ajaxPending === false )
			{
				/* Reset */
				this.ajaxPending = true;
				
				/* Get the data */
				var postParams = $('archiveForm').serialize(true);
				var url        = ( ipb.vars['base_url'] + 'app=forums&module=ajax&section=archive&withApp=forums&do=updateCounter&md5check=' + ipb.vars['md5_hash'] ).replace( /&amp;/g, '&');
				
				Debug.write( url );
				Debug.dir( postParams);
				
				new Ajax.Request( url,
				{
					method: 'post',
					parameters: postParams,
					hideLoader: true,
					onSuccess: function(t)
					{
						/* Save it sister */
						this.ajaxPending = false;
						this.lastBlur    = 0;
						
						if ( t.responseJSON['textString'] )
						{
							$('archiveCount').update( t.responseJSON['textString'] );
							
							/* Pervy flashing */
							var bg = $('archiveCount').getStyle('backgroundColor');
							
							new Effect.Highlight( $('archiveCount'), { 'startcolor': '#ffff99', 'restorecolor': bg, 'keepBackgroundImage': true } );
						}
					}.bind(this)
				} );
			}
		}
	},
	
	/**
	 * Set up row
	 */
	_setUpField: function( key, obj, isArchive )
	{
		var prefix = ( isArchive ) ? 'archive' : 'skip';
		
		switch( key )
		{
			case 'approved':
			case 'pinned':
			case 'poll':
			case 'state':
				var seek = ( obj.value == '-' ) ? 'any' : obj.value;
				
				$( prefix + '_field_' + key + '_' + seek ).selected = true;
			break;
			case 'post':
			case 'rating':
			case 'view':
				var seek = ( obj.value == '<' ) ? 'less' : 'more';
				
				$( prefix + '_field_' + key + '_' + seek ).selected = true;
				$( prefix + '_field_' + key + '_text').value = obj.text;
			break;
			case 'forum':
			case 'member':
				var seek = ( obj.value == '-' ) ? 'more' : 'less';
				
				$( prefix + '_field_' + key + '_' + seek ).selected = true;
			break;
			case 'lastpost':
				var seek = ( obj.value == '<' ) ? 'less' : 'more';
				
				$( prefix + '_field_' + key + '_' + seek ).selected = true;				
				$( prefix + '_field_' + key + '_text').value = obj.text;
				
				$( prefix + '_field_' + key + '_unit_' + obj.unit ).selected = true;
			break;
		}
	},
	
	/**
	 * It's all a bit of a
	 */
	_blur: function( e )
	{
		var elem = Event.findElement(e);
		
		this.lastBlur = this.getUnixTime();
	},
	
	/**
	 * Update members box out
	 */
	_updateMembers: function( obj, count, type )
	{
		/* Wipe it */
		$(type + '_membersGoHere').show().update('');
		
		/* Do it */
		if ( count )
		{
			$H(obj).each( function( data )
			{
				var val = data.value;
				var img = '<a href="#" data-clicklaunch="deleteMember" data-scope="ACPArchive" data-type="' + type + '" data-id="' + val.member_id + '"><img src="' + ipb.vars['image_acp_url'] + 'icons/delete.png"  /></a>&nbsp; &nbsp;';
				$(type + '_membersGoHere').insert( new Element('div').update( img + val['photoTag'] + ' <strong>' + val['members_display_name'] + '</strong> (' + val['g_title'] + ')' ) );
			} );
		}
		else
		{
			$(type + '_membersGoHere').hide();
		}
		
		$$("[data-clicklaunch]").invoke('clickLaunch');
	},
	
	/**
	 * Update forums box out
	 */
	_updateForums: function( obj, type )
	{
		/* Wipe it */
		$(type + '_forumsGoHere').show().update('');
		
		/* Do it */
		$H(obj).each( function( data )
		{
			var val = data.value;
			
			$(type + '_forumsGoHere').insert( new Element('div').update( val.nav + ' ' + val.data.name ) );
		} );
	}
};

ipb.ACPArchive.init();