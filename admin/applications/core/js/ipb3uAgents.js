/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP User Agents Functions
* @author (v.1.0.0) Matt Mecham
*/

function IPBUAgents()
{
	/**
	* Template groups
	*
	* @var	array
	*/
	this.uAgentsData = {};
	
	/**
	* Group Data
	*/
	this.uAgentsGroupData = {};
	
	/**
	* Agents by key
	*/
	this.agentsByKey = {};
	
	/**
	* uagents Names
	*
	* @var hash
	*/
	this.cssNames = { 
					  'uagentsRow'          : 'uAgentsRow',
					  'uagentsHover'        : 'uAgentsHover',
					  'uagentsGRow'         : 'uAgentsGRow',
					  'uagentsGHover'       : 'uAgentsGHover',
					  'uagentsGRRow'        : 'uAgentsGRRow',
					  'uagentsGRHover'      : 'uAgentsGRHover'
					   };
	
	
	/**
	* INIT User Agents List
	*/
	this.init = function()
	{
		/* Build uagents list */
		this.buildUAgentsList();
	};
	
	/**
	* INIT User Agent Form
	*/
	this.groupFormInit = function()
	{
		/* Figure out which are already in the list */
		
		/* Set up key based array */
		Object.keys( this.uAgentsData['uagents'] ).each( function( i )
		{
			IPB3UAgents.agentsByKey[ IPB3UAgents.uAgentsData['uagents'][i]['uagent_key'] ] = IPB3UAgents.uAgentsData['uagents'][i];
		} );
		
		/* Soft delete the ones in the group already */
		Object.keys( this.uAgentsGroupData ).each( function( i )
		{
			IPB3UAgents.uAgentsData['uagents'][ IPB3UAgents.agentsByKey[ IPB3UAgents.uAgentsGroupData[i]['uagent_key'] ]['uagent_id'] ]['_used'] = 1;
		} );
		
			
		/* Build uagents list */
		this.buildGroupAgentList();
		
		/* Build uagents list that are already in the group */
		this.buildGroupAgentsUsedList();
	};
	
	/**
	* Save uagents
	*/
	this.saveuAgent = function( uagent_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=uagents&amp;do=saveuAgent&amp;&uagent_id=" + uagent_id + '&secure_key=' + ipb.vars['md5_hash'];
				
		/* Set up params */
		var params = { 'uagent_key'           : $F('tplate_keyBox_' + uagent_id ),
				       'uagent_name'          : $F('tplate_titleBox_' + uagent_id ),
				       'uagent_regex'         : $F('tplate_regexBox_' + uagent_id ),
				       'uagent_regex_capture' : $F('tplate_captureBox_' + uagent_id ),
					   'uagent_type'		  : $F('tplate_typeBox_' + uagent_id ),
					   'uagent_position'      : $F('tplate_positionBox_' + uagent_id ),
				       'type'                 : ( uagent_id == 0 ) ? 'add' : 'edit'
				 	};
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'POST',
							parameters: params,
							onSuccess: function (t )
							{
								if ( t.responseText.match( /^(\s+?)?\{/ ) )
								{
									eval( "var json = " + t.responseText );
									
									if ( json['error'] )
									{
										alert( json['error'] );
										return;
									}
									else
									{
										IPB3UAgents.uAgentsData = json;
										IPB3UAgents.buildUAgentsList();
										
										ipb.global.showInlineNotification( ipb.lang['useragent_saved'], { showClose: true } );
										
										if ( json['errors'] )
										{
											/* Something inline would be very nice here.. rather than this ugly alert */
											alert("Error:: " + json['errors'] );
										}
										else
										{
											alert("Error:: Saved!");
										}
									}
								}
								else
								{
									alert( ipb.lang['oops'] + t.responseText );
								}
							},
							onException: function( f,e ){ alert( ipb.lang['oops'] + e ); },
							onFailure: function( t ){ alert( ipb.lang['oops'] + t.responseText ); }
						  } );
	};
	
	/**
	* Revert css
	*/
	this.removeuAgent = function( uagent_id )
	{
		/* Make sure we're mean it... */
		if ( ! confirm( ipb.lang['removeuseragent'] ) )
		{
			return false;
		}
		
		
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=uagents&amp;do=removeuAgent&amp;uagent_id=" + uagent_id + '&secure_key=' + ipb.vars['md5_hash'];
	
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								if ( t.responseText.match( /^(\s+?)?\{/ ) )
								{
									eval( "var json = " + t.responseText );
									
									if ( json['error'] )
									{
										alert( json['error'] );
										return;
									}
									else
									{
										IPB3UAgents.uAgentsData = json;
										IPB3UAgents.buildUAgentsList();
										
										if ( json['errors'] )
										{
											/* Something inline would be very nice here.. rather than this ugly alert */
											alert("Error:: " + json['errors'] );
										}
									}
								}
								else
								{
									alert( ipb.lang['oops'] + t.responseText );
								}
							},
							onException: function( f,e ){ alert( ipb.lang['oops'] + e ); },
							onFailure: function( t ){ alert( ipb.lang['oops'] + t.responseText ); }
						  } );
	};
	
	/**
	* Add uagents form
	*/
	this.adduAgentForm = function()
	{
		IPB3UAgents.showuAgentsEditor( 0 );
	};
	
	/**
	* Shows the template editor
	*/
	this.showuAgentsEditor = function( uagent_id )
	{
		/* Close any open editors */
		IPB3UAgents.canceluAgents();
		
		var _tplate = new Template( $('tplate_uagentsEditor').innerHTML );
		var _keys   = Object.keys( IPB3UAgents.uAgentsData['uagents'] );
		var _data   = {};
		
		/* Get replacement Data */
		if ( uagent_id )
		{
			_keys.each( function( i )
			{
				if ( IPB3UAgents.uAgentsData['uagents'][ i ]['uagent_id'] == uagent_id )
				{
					_data = IPB3UAgents.uAgentsData['uagents'][ i ];
				}
			} );
		}
		else
		{
			_data = { 'uagent_id' : 0 };
		}
	
		var _div         = new Element('div', { id: 'tplate_editorWrapper' } );
		//_div.id          = 'tplate_editorWrapper';
		_div.update( _tplate.evaluate( _data ) );
		document.body.appendChild( _div );
		
		/* Adjust title */
		if ( ! uagent_id )
		{
			$('tplate_title_' + uagent_id ).update( ipb.lang['adduagenttitle'] );
		}
		
		/* Select box type */
		_box = $('tplate_typeBox_' + _data['uagent_id'] );
		
		for( i = 0 ; i < _box.options.length ; i++ )
		{
			if ( _box.options[i].value == _data['uagent_type'] )
			{
				_box.selectedIndex = i;
			}
		}
		
		ipb.positionCenter( 'tplate_editor_' + uagent_id );
	};
	
	/**
	* Close the editor
	*/
	this.canceluAgents = function()
	{
		/*if ( ! confirm( "All unsaved changes will be lost!" ) )
		{
			return false;
		}*/
		
		try
		{
			$('tplate_editorWrapper').remove();
		}
		catch(e)
		{
		}
	};
	
	/**
	* Build a list of uagents groups (files)
	*/
	this.buildUAgentsList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_uagentsRow').innerHTML );
		var json    = IPB3UAgents.uAgentsData;
		
		var _groups = Object.keys( json['uagents'] );
		
		/* Clear out any current listing */
		$('tplate_uagentList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			json['uagents'][i]['_cssClass']  = IPB3UAgents.cssNames['uagentsRow'];
			json['uagents'][i]['uagent_name'] = json['uagents'][i]['uagent_name'].replace("%20", " ");
			_output += _tplate.evaluate( json['uagents'][i] );
			
		} );
		
		/* Write it out */
		$('tplate_uagentList').update( _output );
		
		/* Post process */
		_groups.each( function( i )
		{
			/* Image. Can't use #{...} inline as it parses as HTML entities. Most annoying */
			$('tplate_img_' + json['uagents'][i]['uagent_id'] ).src += 'type_' + json['uagents'][i]['uagent_type'] + '.png';
		} );
	};
	
	/**
	* Template Mouse Event
	*/
	this.mouseEvent = function( e )
	{
		if ( _div = $( Event.element( e ) ).up( '.' + IPB3UAgents.cssNames['uagentsRow'] ) )
		{
			var _uagent_id = _div.id.replace( 'tplate_uagentsRow_', '' );
		
			if ( e.type == 'mouseover' )
			{
				_div.addClassName( IPB3UAgents.cssNames['uagentsHover'] );
			}
			else if ( e.type == 'mouseout' )
			{
				_div.removeClassName( IPB3UAgents.cssNames['uagentsHover'] );
			}
			else if ( e.type == 'click' )
			{
				var _el = Event.findElement( e, 'div' ).id;
				
				if ( _el.endsWith('_remove' ) )
				{
					IPB3UAgents.removeuAgent( _uagent_id );
				}
				else
				{
					IPB3UAgents.showuAgentsEditor( _uagent_id );
				}
			}
		}
	};
	
	/**
	* Build a list of USED uagents
	*/
	this.buildGroupAgentsUsedList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_groupRow').innerHTML );
		var json    = IPB3UAgents.uAgentsGroupData;
		
		var _groups = Object.keys( json );
		
		var _save   = {};
		
		/* Clear out any current listing */
		$('tplate_groupList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			if ( ! IPB3UAgents.uAgentsGroupData[i]['_hide'] )
			{
				_save[i] = IPB3UAgents.uAgentsGroupData[i];
				
				json[i] = IPB3UAgents.uAgentsData['uagents'][ IPB3UAgents.agentsByKey[ IPB3UAgents.uAgentsGroupData[i]['uagent_key'] ]['uagent_id'] ];
				json[i]['uagent_versions'] = _save[i]['uagent_versions'];
				
				json[i]['_cssClass']  = IPB3UAgents.cssNames['uagentsGRRow'];
			
				_output += _tplate.evaluate( json[i] );
			}
		} );
		
		/* Write it out */
		$('tplate_groupList').update( _output );
		
		/* Post process */
		_groups.each( function( i )
		{
			if ( ! IPB3UAgents.uAgentsGroupData[i]['_hide'] )
			{
				/* Image. Can't use #{...} inline as it parses as HTML entities. Most annoying */
				$('tplate_groupimg_' + json[i]['uagent_id'] ).src += 'type_' + json[i]['uagent_type'] + '.png';
				
				/* Can we configure a version? */
				var _data = IPB3UAgents.uAgentsData['uagents'][ IPB3UAgents.agentsByKey[ IPB3UAgents.uAgentsGroupData[i]['uagent_key'] ]['uagent_id'] ];
				
				if ( _data['uagent_regex_capture'] > 0 )
				{
					$('tplate_grouprow_' + _data['uagent_id'] + '_configure').show();
				}
			}
		} );
		
		/* Update hidden field */
		$('uAgentsData').value = Object.toJSON( _save );
	};
	
	/**
	* Save user agent form
	*/
	this.saveGroupForm = function()
	{
		$('uAgentsForm').submit();
	};
	
	/**
	* Template Mouse Event
	*/
	this.groupUsedMouseEvent = function( e )
	{
		if ( _div = $( Event.element( e ) ).up( '.' + IPB3UAgents.cssNames['uagentsGRRow'] ) )
		{
			var _uagent_id = _div.id.replace( 'tplate_grouprow_', '' );
		
			if ( e.type == 'mouseover' )
			{
				_div.addClassName( IPB3UAgents.cssNames['uagentsGRHover'] );
			}
			else if ( e.type == 'mouseout' )
			{
				_div.removeClassName( IPB3UAgents.cssNames['uagentsGRHover'] );
			}
			else if ( e.type == 'click' )
			{
				var _el = Event.findElement( e, 'div' ).id;
				
				if ( _el.endsWith('_remove' ) )
				{
					IPB3UAgents.groupRemoveFromList( _uagent_id );
				}
				else if ( _el.endsWith( '_configure' ) )
				{
					IPB3UAgents.showConfigureEditor( _uagent_id );
				}
			}
		}
	};
	
	/**
	* Close the editor
	*/
	this.cancelAgentVersion = function()
	{
		/*if ( ! confirm( "All unsaved changes will be lost!" ) )
		{
			return false;
		}*/
		
		try
		{
			$('tplate_versionsWrapper').remove();
		}
		catch(e)
		{
		}
	};
	
	/**
	* Shows the versions editor
	*/
	this.showConfigureEditor = function( uagent_id )
	{
		/* Close any open editors */
		IPB3UAgents.cancelAgentVersion();
		
		var _tplate = new Template( $('tplate_versionsEditor').innerHTML );
		var _keys   = Object.keys( IPB3UAgents.uAgentsData['uagents'] );
		var _data   = {};
		
		_keys.each( function( i )
		{
			if ( IPB3UAgents.uAgentsData['uagents'][ i ]['uagent_id'] == uagent_id )
			{
				_data                    = IPB3UAgents.uAgentsData['uagents'][ i ];
				_data['uagent_versions'] = IPB3UAgents.uAgentsGroupData[ IPB3UAgents.uAgentsData['uagents'][ i ]['uagent_key'] ]['uagent_versions'];
			}
		} );
	
		var _div         = new Element('div', { id: 'tplate_versionsWrapper' } );
		_div.update( _tplate.evaluate( _data ) );
		document.body.appendChild( _div );
	
		ipb.positionCenter( 'tplate_versions_' + uagent_id );
	};
	
	/**
	* Save agent version info
	*/
	this.saveAgentVersion = function( uagent_id )
	{
		/* Update array */
		IPB3UAgents.uAgentsGroupData[ IPB3UAgents.uAgentsData['uagents'][ uagent_id ]['uagent_key'] ]['uagent_versions'] = $F( 'tplate_versionsBox_' + uagent_id );
		
		/* Update display */
		IPB3UAgents.buildGroupAgentsUsedList();
		
		/* Close any open editors */
		IPB3UAgents.cancelAgentVersion();
	};
	
	/**
	* Add user agent to the list
	*/
	this.groupRemoveFromList = function( uagent_id )
	{
		/* Soft delete */
		IPB3UAgents.uAgentsData['uagents'][ uagent_id ]['_used'] = 0;
		
		/* Add to add to group array */
		IPB3UAgents.uAgentsGroupData[ IPB3UAgents.uAgentsData['uagents'][ uagent_id ]['uagent_key'] ]['_hide'] = 1;
		
		/* Build uagents list */
		IPB3UAgents.buildGroupAgentList();
		
		/* Build uagents list that are already in the group */
		IPB3UAgents.buildGroupAgentsUsedList();
	};
	
	/**
	* Add user agent to the list
	*/
	this.groupAddToList = function( uagent_id )
	{
		/* Soft delete */
		IPB3UAgents.uAgentsData['uagents'][ uagent_id ]['_used'] = 1;
		
		/* Add to add to group array */
		IPB3UAgents.uAgentsGroupData[ IPB3UAgents.uAgentsData['uagents'][ uagent_id ]['uagent_key'] ] = IPB3UAgents.uAgentsData['uagents'][ uagent_id ];
		IPB3UAgents.uAgentsGroupData[ IPB3UAgents.uAgentsData['uagents'][ uagent_id ]['uagent_key'] ]['_hide'] = 0;
		
		/* Build uagents list */
		IPB3UAgents.buildGroupAgentList();
		
		/* Build uagents list that are already in the group */
		IPB3UAgents.buildGroupAgentsUsedList();
	};
	
	/**
	* Template Mouse Event
	*/
	this.groupMouseEvent = function( e )
	{
		if ( _div = $( Event.element( e ) ).up( '.' + IPB3UAgents.cssNames['uagentsGRow'] ) )
		{
			var _uagent_id = _div.id.replace( 'tplate_agentrow_', '' );
		
			if ( e.type == 'mouseover' )
			{
				_div.addClassName( IPB3UAgents.cssNames['uagentsGHover'] );
			}
			else if ( e.type == 'mouseout' )
			{
				_div.removeClassName( IPB3UAgents.cssNames['uagentsGHover'] );
			}
			else if ( e.type == 'click' )
			{
				IPB3UAgents.groupAddToList( _uagent_id );
			}
		}
	};
	
	/**
	* Build a list of uagents
	*/
	this.buildGroupAgentList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_agentRow').innerHTML );
		var json    = IPB3UAgents.uAgentsData;
		
		var _groups = Object.keys( json['uagents'] );
		
		/* Clear out any current listing */
		$('tplate_agentsList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			if ( ! json['uagents'][i]['_used'] )
			{
				json['uagents'][i]['_cssClass']  = IPB3UAgents.cssNames['uagentsGRow'];
			
				_output += _tplate.evaluate( json['uagents'][i] );
			}
		} );
		
		/* Write it out */
		$('tplate_agentsList').update( _output );
		
		/* Post process */
		_groups.each( function( i )
		{
			if ( ! json['uagents'][i]['_used'] )
			{
				/* Image. Can't use #{...} inline as it parses as HTML entities. Most annoying */
				$('tplate_agentimg_' + json['uagents'][i]['uagent_id'] ).src += 'type_' + json['uagents'][i]['uagent_type'] + '.png';
			}
		} );
	};
}