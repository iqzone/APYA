/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.members.js - Member functions			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _temp = window.IPBACP;

_temp.prototype.members = {
	
	popups: {},
	fields: {},
	
	movePruneAction: function( e, type )
	{
		$('f_search_type').value = type;
		$('memberListForm').submit(); 
	},
	
	switchSearch: function(e, type)
	{
		try {
			if( type == 'advanced' )
			{
				$('m_simple').hide();
				$('m_advanced').show();
			}
			else
			{
				$('m_advanced').hide();
				$('m_simple').show();
			}
		} catch(err) {}
	},
	
	goToTab: function(tabid) 
	{
		var evt;
		var el = $(tabid);

		if ( document.createEvent )
		{
			evt = document.createEvent("MouseEvents");
			evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
		}

		(evt) ? el.dispatchEvent(evt) : (el.click && el.click());
	},

	newPhoto: function( e, url )
	{
		Event.stop(e);
		
		acp.members.hideActionMenu();
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		acp.members.popups['photo'] = new ipb.Popup('m_photo', { type: 'pane', modal: false, hideAtStart: false, w: '600px', ajaxURL: url } );		
	},
	
	banManager: function( e, url )
	{
		Event.stop(e);
		
		acp.members.hideActionMenu();
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		acp.members.popups['photo'] = new ipb.Popup('m_ban', { type: 'pane', modal: false, hideAtStart: false, w: '600px', h: 500, ajaxURL: url } );		
	},

	removePhoto: function( e, member_id )
	{
		Event.stop(e);
		Debug.write("Removing photo...");
		
		url = ipb.vars['base_url'] + "app=members&amp;module=ajax&amp;section=editform&amp;do=remove_photo&amp;member_id=" + member_id + '&secure_key=' + ipb.vars['md5_hash'];

		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							evalJSON: 'force',
							onSuccess: function (t )
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									Debug.write( t.responseText );
									alert( ipb.lang['templates_servererror'] + t.responseText );
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									Debug.write( t.responseJSON['error'] );
									alert( ipb.lang['templates_servererror'] + t.responseJSON['error']);
									return;
								}
								else
								{
									try {
										$('MF__pp_photo').src = t.responseJSON['pp_main_photo'];
										$('MF__pp_photo').setStyle( { width: t.responseJSON['pp_main_width'] + 'px' } );
										$('MF__pp_photo').setStyle( { height: t.responseJSON['pp_main_height'] + 'px' } );
										$('MF__pp_photo_container').setStyle( { width: t.responseJSON['pp_main_width'] + 'px' } );
										$('MF__removephoto').remove();
									} catch(err) {
										Debug.write( err );
									}
								}
							}
						  } );
	},
	
	editField: function( e, id, lang, url )
	{
		Event.stop(e);
		
		acp.members.hideActionMenu();
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );

		acp.members.popups[ id ] = new ipb.Popup('m_' + id, { type: 'pane', modal: false, hideAtStart: false, w: '700px', ajaxURL: url } );
	},

	hideActionMenu: function()
	{
		if ( $('member_tasks_menucontent').visible() )
		{
			$('member_tasks_menucontent').hide();
		}
	}
};