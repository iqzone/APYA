/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.status.js - Status  management code		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Matt Mecham							*/
/************************************************/

var _status = window.IPBoard;

_status.prototype.status = {
	_updateClicked: false,
	forMemberId: 0,
	maxReplies: 0,
	smallSpace: 0,
	myLatest: 0,
	skin_group: 'profile',
	events: { 'page': false, 'normal': false },
	
	init: function()
	{
		Debug.write("Initializing ips.status.js");
		
		document.observe("dom:loaded", function(){
			ipb.status.initEvents();
		});
	},
	
	/*!! initEvents */
	initEvents: function()
	{
		Debug.write( 'Status init' );
		/* Show all comments link */
		ipb.delegate.register(".__showAll", ipb.status.showAllComments);
		
		/* Links for showing reply box */
		ipb.delegate.register(".__showform", ipb.status.showForm);
		
		/* Submit button for replies */
		$$('.__submit').each( function(suBo)
		{
			id = suBo.identify();
			
			$(id).observe( 'click', ipb.status.addReply.bindAsEventListener( this, id.replace( 'statusSubmit-', '' ) ) );
		});
		
		/* Delete comment link */
		ipb.delegate.register(".__sDR", ipb.status.deleteReply);
		
		/* Delete status link */
		ipb.delegate.register(".__sD", ipb.status.deleteStatus);
		
		/* Lock status link */
		ipb.delegate.register(".__sL", ipb.status.lockStatus);
		
		/* Unlock status link */
		ipb.delegate.register(".__sU", ipb.status.unlockStatus);
		
		/* Delete status link */
		ipb.delegate.register(".__sT", ipb.status.showFeedback);
		
		/* Delete status link */
		ipb.delegate.register(".__sTO", ipb.status.hideFeedback);
		
		if ( $('statusUpdate_page') && ipb.status.events['page'] === false )
		{
			try
			{
				ipb.status.forMemberId = parseInt( $('statusUpdate_page').readAttribute('data-for-member-id') );
			}
			catch(e) {}
			
			if ( ! ipb.status.forMemberId )
			{
				$('statusUpdate_page').defaultize( ipb.lang['global_status_update'] );
			}
			else
			{
				$('statusUpdate_page').defaultize( ipb.lang['global_leave_msg'] );
			}
			
			ipb.status.events['page'] = $('statusSubmit_page').on( 'click', ipb.status.updateSubmit.bindAsEventListener( this, 'statusUpdate_page' ) );
		}
	},
	
	showForm: function(e, elem)
	{
		Event.stop(e);
		
		var id = $( elem ).id.replace('statusReplyFormShow-', '');
		if( Object.isUndefined( id ) || !$('statusReplyForm-' + id) ){ return; }
		
		$(elem).hide();
		$('statusReplyForm-' + id).show();
		$('statusText-' + id).focus();
	},
	
	
	/*!! updateSubmit */
	/* Add a sexy ajax status" */
	updateSubmit: function(e, field)
	{
		Event.stop(e);
		
		$('statusSubmitGlobal').stopObserving( 'click' );
		
		if ( $( field ).value.length < 2 || $( field ).value == ipb.lang['prof_update_default'] )
		{
			return false;
		}
		
		var su_Twitter  = $('su_Twitter') && $('su_Twitter').checked ? 1  : 0;
		var su_Facebook = $('su_Facebook') && $('su_Facebook').checked ? 1 : 0;
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=new&md5check=" + ipb.vars['secure_hash'] + '&smallSpace=' + ipb.status.smallSpace + '&skin_group=' + ipb.status.skin_group + '&forMemberId=' + ipb.status.forMemberId,
						{
							method: 'post',
							evalJSON: 'force',
							parameters: {
								content: $( field ).value.encodeParam(),
								su_Twitter: su_Twitter,
								su_Facebook: su_Facebook
							},
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									alert( t.responseJSON['error'] );
								}
								else
								{
									try {
										$('status_wrapper').innerHTML = t.responseJSON['html'] + $('status_wrapper').innerHTML;
										
										/* Showing latest only? */
										if ( ipb.status.myLatest )
										{
											if ( $('statusWrap-' + ipb.status.myLatest ) )
											{
												$('statusWrap-' + ipb.status.myLatest ).hide();
											}
										}
										
										/* Need to blur out of field
											@link	http://community.invisionpower.com/tracker/issue-21358-small-input-field-behavior-issue-after-updating-status/
										*/
										$( field ).blur();
										$( field ).value = '';
										
										ipb.menus.closeAll(e);
										
										/* Re-init events */
										ipb.status.initEvents();
									}
									catch(err)
									{
										Debug.error( 'Logging error: ' + err );
									}
								}
							}
						});
	},
	
	/*!! deleteStatus */
	/* result of clicking "delete" on a status */
	deleteStatus: function(e, elem)
	{
		Event.stop(e);
		
		if ( ! confirm( ipb.lang['delete_confirm'] ) )
		{
			return false;
		}
		
		var status = $( elem ).className.match('__d([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error showing all comments"); return; }
		var status_id = status[1];
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=deleteStatus&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash'],
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON.error )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								else
								{
									$('statusWrap-' + status_id ).remove();
								}
							}
						});
	},
	
	/*!! showFeedback */
	/* result of clicking "lock" on a status */
	showFeedback: function(e, elem)
	{
		Event.stop(e);
		
		var status = $( elem ).className.match('__t([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error"); return; }
		var status_id = status[1];
		
		//$('statusWrap-' + status_id ).addClassName('rowdark');
		if( $('statusReplyFormShow-' + status_id ) )
		{
			$('statusReplyFormShow-' + status_id ).hide();
		}
		
		if( $('statusReplyForm-' + status_id ) )
		{
			$('statusReplyForm-' + status_id ).show();
		}
		
		$('statusFeedback-' + status_id ).show();
		$('statusToggle-' + status_id ).hide();
		$('statusToggleOff-' + status_id ).show();
		
		if( $('statusText-' + status_id ) )
		{
			$('statusText-' + status_id ).focus();
		}
	},
	
	/*!! hideFeedback */
	/* result of clicking "lock" on a status */
	hideFeedback: function(e, elem)
	{
		Event.stop(e);
		
		var status = $( elem ).className.match('__to([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error"); return; }
		var status_id = status[1];
		
		//$('statusWrap-' + status_id ).removeClassName('rowdark');
		$('statusFeedback-' + status_id ).hide();
		$('statusToggle-' + status_id ).show();
		$('statusToggleOff-' + status_id ).hide();
	},

	
	/*!! lockStatus */
	/* result of clicking "lock" on a status */
	lockStatus: function(e, elem)
	{
		Event.stop(e);
		
		if ( ! confirm( ipb.lang['delete_confirm'] ) )
		{
			return false;
		}
		
		var status = $( elem ).className.match('__l([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error"); return; }
		var status_id = status[1];
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=lockStatus&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash'] + '&skin_group=' + ipb.status.skin_group,
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON.error )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								else
								{
									$('statusUnlock-' + status_id ).show();
									$('statusLock-' + status_id ).hide();
									$('statusLockImg-' + status_id ).show();
									
								}
							}
						});
	},
	
	/*!! unlockStatus */
	/* result of clicking "unlock" on a status */
	unlockStatus: function(e, elem)
	{
		Event.stop(e);
		
		if ( ! confirm( ipb.lang['delete_confirm'] ) )
		{
			return false;
		}
		
		var status = $( elem ).className.match('__u([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error"); return; }
		var status_id = status[1];
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=unlockStatus&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash']+ '&skin_group=' + ipb.status.skin_group,
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON.error )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								else
								{
									$('statusUnlock-' + status_id ).hide();
									$('statusLock-' + status_id ).show();
									$('statusLockImg-' + status_id ).hide();
								}
							}
						});
	},
	
	/*!! deleteReply */
	/* result of clicking "delete" on a comment */
	deleteReply: function(e, elem)
	{
		Event.stop(e);
		
		if ( ! confirm( ipb.lang['delete_confirm'] ) )
		{
			return false;
		}
		
		var status = $( elem ).className.match('__dr([0-9]+)-([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error showing all comments"); return; }
		var status_id = status[1];
		var reply_id  = status[2];
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=deleteReply&status_id=" + status_id + "&reply_id=" + reply_id + "&md5check=" + ipb.vars['secure_hash']+ '&skin_group=' + ipb.status.skin_group,
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON.error )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								else
								{
									$('statusReply-' + reply_id ).remove();
								}
							}
						});
	},


	/*!! showAllComments */
	/* result of clicking "show all X comments" */
	showAllComments: function(e, elem)
	{
		Event.stop(e);
		
		var status = $( elem ).className.match('__x([0-9]+)');
		if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error showing all comments"); return; }
		var status_id = status[1];
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=showall&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash']+ '&skin_group=' + ipb.status.skin_group,
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								
								if ( t.responseJSON['error'] )
								{
									alert( t.responseJSON['error'] );
								}
								else
								{
									try {
										$('statusMoreWrap-' + status_id ).hide();
										$('statusReplies-' + status_id ).update( t.responseJSON['html'] );
										
										if ( t.responseJSON['status_replies'] > 20 )
										{
											$('statusReplies-' + status_id ).addClassName('status_replies_many');
										}
									}
									catch(err)
									{
										Debug.error( err );
									}
								}
							}
						});
	},
	
	/*!! addReply */
	/* Add a sexy ajax reply" */
	addReply: function(e, status_id)
	{
		Event.stop(e);
		
		if ( $('statusText-' + status_id ).value.length < 2 )
		{
			return false;
		}
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&do=reply&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash']+ '&skin_group=' + ipb.status.skin_group,
						{
							method: 'post',
							evalJSON: 'force',
							parameters: {
								content: $('statusText-' + status_id ).value.encodeParam()
							},
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								
								if ( t.responseJSON['error'] )
								{
									alert( t.responseJSON['error'] );
								}
								else
								{
									try {
										$( 'statusReplyBlank-' + status_id ).show().innerHTML += t.responseJSON['html'];
										$('statusText-' + status_id ).value = '';
									}
									catch(err)
									{
										Debug.write( err );
									}
								}
							}
						});
	}
};

ipb.status.init();