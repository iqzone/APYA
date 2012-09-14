/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.warn.js - Warning system javascript		*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Mark Wade							*/
/************************************************/


var _idx = window.IPBoard;

_idx.prototype.warn = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		document.observe("dom:loaded", function(){
			$('reason-select').observe('change', ipb.warn.changeReason.bindAsEventListener( this ) );
			$('points-field').observe('change', ipb.warn.changeReason.bindAsEventListener( this ) );
			$('mq_perm').observe('change', ipb.warn.showTime.bindAsEventListener( this, 'mq' ) );
			$('rpa_perm').observe('change', ipb.warn.showTime.bindAsEventListener( this, 'rpa' ) );
			$('suspend_perm').observe('change', ipb.warn.showTime.bindAsEventListener( this, 'suspend' ) );
			$('change-punishment-button').observe('click', ipb.warn.showCustom.bindAsEventListener( this ) );
			$('points-explain-button').observe('click', ipb.warn.explainPoints.bindAsEventListener( this ) );
			});
	},
	
	/*------------------------------*/
	/* Change Reason Select Box		*/
	changeReason: function( e )
	{
		if ( $('reason-select').value != '' )
		{
			if( e.target.id == 'reason-select' )
			{
				$('points-field').value = '';
			}
		
			new Ajax.Request( ipb.vars['base_url'] + "app=members&section=warnings&module=ajax&do=form&md5check=" + ipb.vars['secure_hash'],
			{
				method: 'post',
				evalJSON: 'force',
				parameters: {
					id: $('reason-select').value,
					member: window.location.toString().toQueryParams().member,
					points: $('points-field').value
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
						if ( t.responseJSON['manuallySetPoints'] == 1 )
						{
							new Effect.Appear( $('points-li'), { duration: 0.3 } );
						}
						else
						{
							new Effect.Fade( $('points-li'), { duration: 0.3 } );
						}
						
						if ( t.responseJSON['allowCustomRemovePoints'] == 1 )
						{
							new Effect.Appear( $('remove-points-li'), { duration: 0.3 } );
							$('remove_input').value = t.responseJSON['removePoints'];
							$('remove_unit_select').value = t.responseJSON['removePointsUnit'];
						}
						else
						{
							new Effect.Fade( $('remove-points-li'), { duration: 0.3 } );
						}
						
						if ( t.responseJSON['setPoints'] )
						{
							$('points-field').value = t.responseJSON['setPoints'];
						}
						else
						{
							$('points-field').value = '';
						}
						
						$('specified-punishment').innerHTML = t.responseJSON['setPunishment'];
						
						if ( t.responseJSON['allowCustomPunishment'] == 1 )
						{
							$('change-punishment-button').show();
						}
						else
						{
							$('change-punishment-button').hide();
						}
						
						if ( t.responseJSON['mq'] == -1 )
						{
							$('mq_perm').checked = 'checked';
							$('mq_time').hide();
						}
						else
						{
							$('mq_perm').checked = '';
							$('mq_time').show();
							$('mq_input').value = t.responseJSON['mq'];
						}
						$('mq_unit_select').value = t.responseJSON['mq_unit'];
						
						if ( t.responseJSON['rpa'] == -1 )
						{
							$('rpa_perm').checked = 'checked';
							$('rpa_time').hide();
						}
						else
						{
							$('rpa_perm').checked = '';
							$('rpa_time').show();
							$('rpa_input').value = t.responseJSON['rpa'];
						}
						$('rpa_unit_select').value = t.responseJSON['rpa_unit'];
						
						if ( t.responseJSON['suspend'] == -1 )
						{
							$('suspend_perm').checked = 'checked';
							$('suspend_time').hide();
						}
						else
						{
							$('suspend_perm').checked = '';
							$('suspend_time').show();
							$('suspend_input').value = t.responseJSON['suspend'];
						}
						$('suspend_unit_select').value = t.responseJSON['suspend_unit'];
						
						if ( t.responseJSON['ban_group'] == 1 )
						{
							$('ban_group').checked = 'checked';
						}
						else
						{
							$('ban_group').checked = '';
						}
						
					}
				}
			});
		}
	},
			
	/*------------------------------*/
	/* Show/hide time frames 		*/
	showTime: function( e, type )
	{
		if ( $( type + '_perm' ).checked )
		{
			new Effect.Fade( $( type + '_time' ), { duration: 0.2 } );
		}
		else
		{
			new Effect.Appear( $( type + '_time' ), { duration: 0.2 } );
		}
		
		$( type + '_perm' ).observe('change', ipb.warn.showTime.bindAsEventListener( this, type ) );
	},
	
	/*------------------------------*/
	/* Show custom options	 		*/
	showCustom: function( e, type )
	{
		Event.stop(e);
		
		new Effect.Fade( $( 'punishment_li' ), { duration: 0.2 } );
		new Effect.Appear( $( 'mq_li' ), { duration: 0.2 } );
		new Effect.Appear( $( 'rpa_li' ), { duration: 0.2 } );
		new Effect.Appear( $( 'suspend_li' ), { duration: 0.2 } );
	},
	
	/*------------------------------*/
	/* Explain points		 		*/
	explainPoints: function( e, type )
	{
		url = ipb.vars['base_url'] + "&app=members&module=ajax&secure_key=" + ipb.vars['secure_hash'] + '&section=warnings&do=explain_points';
		popup = new ipb.Popup( 'attachments', { type: 'pane', modal: false, w: '800px', h: '900px', ajaxURL: url, hideAtStart: false } );
	}

};

ipb.warn.init();