/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP Template Diff Functions
* @author (v.1.0.0) Matt Mecham
*/

/* Prototype Ajax Global Responders
 * Based on code from: http://codejanitor.com/wp/2006/03/23/ajax-timeouts-with-prototype/
 * Aborts ajax after a 5 minute delay of nothing happening  
*/

var IPB3TemplateDiffResults = new IPB3TemplateDiffResults;
						
function IPB3TemplateDiffResults()
{
	this.popUps             = {};
	this.cookieArray        = new Array();
	this.defaultCookieArray = new Array( 'merged', 'conflicted', 'uncommitted', 'committed' );
	
	/**
	 * INIT
	 */
	this.init = function()
	{
		document.observe("dom:loaded", function()
		{
			/* Set up cbox all */
			$('cboxall').observe( 'click', IPB3TemplateDiffResults.toggleSelectAll );
			
			/* Set up cbox handler */
			ipb.delegate.register(".__cbx", IPB3TemplateDiffResults.cboxEvent);
		
			cookie = ipb.Cookie.get('acp_mergecenter');
			Debug.write( cookie );
			
			/* Set a fairly good default */
			if ( cookie )
			{
				IPB3TemplateDiffResults.cookieArray = cookie.split( ',' );
			}
			
			if ( ! IPB3TemplateDiffResults.cookieArray.length )
			{
				IPB3TemplateDiffResults.cookieArray = IPB3TemplateDiffResults.defaultCookieArray;
			}
			
			/* Update the view */
			IPB3TemplateDiffResults.updateView();
		} );
	};

	
	/**
	 * Event when dude or dudette clicks a checko-boxo
	 */
	this.cboxEvent = function( e, elem )
	{
		newArray = new Array();
		
		$$('.__cbx').each(
			function(x)
			{
				if ( x.checked )
				{ 
					newArray.push( x.id.replace( '_cb_', '' ) );
				}
			}
		);
		
		if ( ! newArray.length )
		{
			IPB3TemplateDiffResults.cookieArray = IPB3TemplateDiffResults.defaultCookieArray;
		}
		else
		{
			IPB3TemplateDiffResults.cookieArray = newArray;
		}
		
		/* Set cookie */
		ipb.Cookie.set( 'acp_mergecenter', IPB3TemplateDiffResults.cookieArray.join( ',' ) );
		
		/* Update view */
		IPB3TemplateDiffResults.updateView(e);
	};
	
	/**
	 * View options
	 */
	this.updateView = function( e )
	{
		/* Check */
		if ( ! IPB3TemplateDiffResults.cookieArray.length )
		{
			IPB3TemplateDiffResults.cookieArray = IPB3TemplateDiffResults.defaultCookieArray;
		}
		
		/* reset all headers */
		$$('.__group').invoke('show');
		
		/* reset all items */
		$$('.__diffRow').invoke('hide');
		
		/* Reset view cboxes */
		$$('.__cbx').each( function(x) { x.checked = false; } );
		
		for( i = 0 ; i <= IPB3TemplateDiffResults.cookieArray.length ; i++ )
		{
			opt = IPB3TemplateDiffResults.cookieArray[ i ];
			
			switch( opt )
			{
				case 'nocustom':
					$$('._nochanges').invoke('show');
					$('_cb_nocustom').checked = true;
				break;
				case 'merged':
					$$('.__canMerge').invoke('show');
					$('_cb_merged').checked = true;
				break;
				case 'conflicted':
					$$('.__conflicts').invoke('show');
					$('_cb_conflicted').checked = true;
				break;
				case 'uncommitted':
					$$('._uncommitted').invoke('show');
					$('_cb_uncommitted').checked = true;
				break;
				case 'committed':
					$$('.__committed').invoke('show');
					$('_cb_committed').checked = true;
				break;
			}
		}
		
		/* Go through and remove any group titles */
		$$('.__group').each( function( i )
		{
			/* Extract group key */
			if ( $( i ).className.match( /_xgid/ ) )
			{
				_g = $( i ).className.match( '_xgid([0-9a-zA-Z]+)' );
				
				if ( _g != null && ! Object.isUndefined( _g ) )
				{
					_groupKey = _g[1];
					
					if ( _groupKey )
					{
						_c = 0;
						
						$$('._xgid' + _groupKey).each( function(y)
						{
							if ( $(y).visible() )
							{
								_c++;
							}
						} );
						
						if ( _c < 2 )
						{
							$(i).hide();
						}
					}
				}
			}
		} );
	};
	
	/**
	 * Select all
	 */
	this.toggleSelectAll = function( e )
	{
		/* Wot do we have? */
		var status = $('cboxall').checked;
		
		/* unselect? */
		if ( status == false )
		{
			$$('.__xBox').each( function(x) { x.checked = false; } );
		}
		else
		{
			/* Just select non-hidden items */
			$$('.__diffRow').each( function( i )
			{
				if ( $(i).visible() )
				{
					_g = $( i ).className.match( '__rowId([0-9]+)' );
					
					if ( _g != null && ! Object.isUndefined( _g ) )
					{
						$$('.__cBox' + _g[1]).each( function(x) { if ( ! x.disabled ) { x.checked = true; } } );
					}
				}
			} );
		}
	};
	
	/**
	 * View version
	 */
	this.viewVersion = function( changeId, type )
	{
		/* Grab it via ajax */
		var _url = ipb.vars['base_url'] + "&app=core&module=ajax&section=templatediff&do=viewVersion&change_id=" + changeId + '&type=' + type + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( _url,
						  {
							method: 'get',
							onSuccess: function (t)
							{
								/* Not a JSON response? */
								if ( ! t.responseText.match( /^(\s+?)?\{/ ) )
								{
									alert( ipb.lang['sk_error'] + t.responseText );
									return;
								}
	
								/* Process results */
								eval( "var json = " + t.responseText );
	
								if ( json['error'] )
								{
									alert( ipb.lang['sk_error'] + json['error'] );
									return false;
								}
								
								/* Type */
								switch( type )
								{
									default:
									case 'orig':
										_text = ipb.lang['comparediff_oldd'];
									break;
									case 'new':
										_text = ipb.lang['comparediff_newd'];
									break;
									case 'custom':
										_text = ipb.lang['comparediff_cust'];
									break;
								}
								
								/* otherwise... */
								if ( json['data_content'] )
								{
									if ( json['data_type'] == 'css' )
									{
										content = '<div class="acp-box"><h3>' + _text + ': ' + json['data_title'] + '.css</h3><div style="padding:4px;line-height:125%;overflow:auto;max-height: 575px;"><pre>' + json['data_content'] + '</pre></div></div>';
									}
									else
									{
										content = '<div class="acp-box"><h3>' + _text + ': ' + json['data_group'] + ' &gt; ' + json['data_title'] + '</h3><div style="padding:4px;line-height:125%;overflow:auto;max-height: 575px;"><pre>' + json['data_content'] + '</pre></div></div>';
									}
									
									max = document.viewport.getHeight();
									max = ( max < 600 ) ? max : 600;
									
									popup = new ipb.Popup('VPopUp', { type: 'pane', modal: false, hideAtStart: true, w: '600px', h: max, initial: content } );
									popup.show();
								}
							},
							onFailure: function()
							{
								return false;
							},
							onException: function()
							{
								return false;
							}
							
						  } );
	};

	/**
	 * View diff preview
	 */
	this.viewDiff = function( changeId, type )
	{
		/* Grab it via ajax */
		var _url = ipb.vars['base_url'] + "&app=core&module=ajax&section=templatediff&do=viewDiff&change_id=" + changeId + '&type=' + type + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( _url,
						  {
							method: 'get',
							onSuccess: function (t)
							{
								/* Not a JSON response? */
								if ( ! t.responseText.match( /^(\s+?)?\{/ ) )
								{
									alert( ipb.lang['sk_error'] + t.responseText );
									return;
								}
	
								/* Process results */
								eval( "var json = " + t.responseText );
	
								if ( json['error'] )
								{
									alert( ipb.lang['sk_error'] + json['error'] );
									return false;
								}
								
								/* Type */
								switch( type )
								{
									default:
									case 'diff':
										_text = ipb.lang['comparediff_diff'];
									break;
									case 'merge':
										_text = ipb.lang['comparediff_merg'];
									break;
								}
								
								/* otherwise... */
								if ( json['change_data_content'] )
								{
									/* Extra work for merge */
									if ( type == 'merge' )
									{
										_qb = "<div class='acp-actionbar' style='text-align:left;'><div style='float:right'><input type='button' id='mergeEditCustom' class='button __re" + changeId + "' value='" + ipb.lang['merge_edititem'] + "' /></div><input type='button' id='mergeResolveCustom' class='button __rc" + changeId + "' value='" + ipb.lang['resolveall_button'] + "' />&nbsp;<input type='button' id='mergeResolveNew' class='button __rn" + changeId + "' value='" + ipb.lang['resolveall_newwin'] + "' /></div>";
									}
									else
									{
										_qb = '';
									}

									if ( json['change_data_type'] == 'css' )
									{
										content = '<div class="acp-box"><h3>'+ _text + ': ' + json['change_data_title'] + '.css</h3><div style="padding:4px;line-height:125%;overflow: auto;max-height: 575px;"><pre>' + json['change_data_content'] + '</pre></div>' + _qb + "</div>";
									}
									else
									{
										content = '<div class="acp-box"><h3>'+ _text + ': ' + json['change_data_group'] + ' &gt; ' + json['change_data_title'] + '</h3><div style="padding:4px;line-height:125%;overflow: auto;max-height: 575px;"><pre>' + json['change_data_content'] + '</pre></div>' + _qb + "</div>";
									}
									
									max = document.viewport.getHeight();
									max = ( max < 600 ) ? max : 600;
									
									IPB3TemplateDiffResults.popUps['preview-' + changeId ] = new ipb.Popup('diffPopUp', { type: 'pane', modal: false, hideAtStart: true, w: '800px', h: max, initial: content } );
									IPB3TemplateDiffResults.popUps['preview-' + changeId ].show();
									
									/* Observe */
									if ( type == 'merge' )
									{
										$('mergeResolveCustom').observe( 'click', IPB3TemplateDiffResults.resolveAllSingle );
										$('mergeResolveNew').observe( 'click', IPB3TemplateDiffResults.resolveAllSingle );
										$('mergeEditCustom').observe( 'click', IPB3TemplateDiffResults.goEdit );
									}
									
								}
							},
							onFailure: function()
							{
								return false;
							},
							onException: function()
							{
								return false;
							}
							
						  } );
	};
	
	/**
	 * View diff preview
	 */
	this.editBit = function( changeId )
	{
		/* Grab it via ajax */
		var _url = ipb.vars['base_url'] + "&app=core&module=ajax&section=templatediff&do=editDiff&change_id=" + changeId + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( _url,
						  {
							method: 'get',
							onSuccess: function (t)
							{
								/* Not a JSON response? */
								if ( ! t.responseText.match( /^(\s+?)?\{/ ) )
								{
									alert( ipb.lang['sk_error'] + t.responseText );
									return;
								}
	
								/* Process results */
								eval( "var json = " + t.responseText );
	
								if ( json['error'] )
								{
									alert( ipb.lang['sk_error'] + json['error'] );
									return false;
								}
								
								/* otherwise... */
								if ( json['change_merge_content'] )
								{
									/* Ok, for some reason Safari (and possibly others) doesn't show the pop-up when viewing a
									   template bit. I presume it's because prototype/javascript will not insert invalid HTML into a node even
									   though it's encoded properly. After a few days working on merge, this is a quick hack to get it done
									   Force a 750px div in to position pop-up correctly on the screen, then once the pop-up has been created
									   shove the content in.
									   <3 javascript */
									var _tmp = Math.floor( Math.random() * 1001 );
									
									_qb = "<div style='float:left; margin-top:6px'><input type='button' id='mergeResolveCustom_" + _tmp + "' class='button __rc" + changeId + "' value='" + ipb.lang['resolveall_button'] + "' />&nbsp;<input type='button' id='mergeResolveNew_" + _tmp + "' class='button __rn" + changeId + "' value='" + ipb.lang['resolveall_newwin'] + "' /></div>";
									_tb = '<textarea name="content" id="mergeContent" style="width:100%; height:500px; line-height:150%">' + json['change_merge_content'] + '</textarea>' + _qb + '<div style="text-align:right" class="acp-actionbar"><input type="button" id="mergeSubmit" value="' + ipb.lang['save_button'] + '" class="button primary __i' + changeId + '" /></div>';
									
									if ( json['change_data_type'] == 'css' )
									{
										content = '<div class="acp-box"><h3>' + json['change_data_title'] + '.css' + '</h3><div style="padding:4px;">' + _tb + '</div></div>';
									}
									else
									{
										content = '<div class="acp-box"><h3>' + json['change_data_group'] + ' &gt; ' + json['change_data_title'] + '</h3><div style="padding:4px;">' + _tb + '</div></div>';
									}
									
									IPB3TemplateDiffResults.popUps['edit-' + changeId ] = new ipb.Popup('diffEditPopUp_' + _tmp, { type: 'pane', modal: false, hideAtStart: true, w: '800px', h: 650, initial: '<div style="height:590px">&nbsp;</div>' } );
									IPB3TemplateDiffResults.popUps['edit-' + changeId ].show();
									
									$('diffEditPopUp_' + _tmp + '_inner').update( content );
									
									/* Observe */
									$('mergeSubmit').observe( 'click', IPB3TemplateDiffResults.saveEdit );
									
									$('mergeResolveCustom_' + _tmp ).observe( 'click', IPB3TemplateDiffResults.resolveAllSingle );
									$('mergeResolveNew_' + _tmp ).observe( 'click', IPB3TemplateDiffResults.resolveAllSingle );
									
								}
							},
							onFailure: function()
							{
								return false;
							},
							onException: function()
							{
								return false;
							}
							
						  } );
	};
	
	/**
	 * View diff preview
	 */
	this.goEdit = function(e)
	{
		elem = Event.element(e);
				
		/* fetch ID */
		var test = $( elem ).className.match('__re([0-9]+)');
		if( test == null || Object.isUndefined( test ) ){ Debug.error("Error"); return; }
		
		var changeId = test[1];
		
		/* Close preview pop-up */
		if ( IPB3TemplateDiffResults.popUps['preview-' + changeId ] )
		{
			IPB3TemplateDiffResults.popUps['preview-' + changeId ].hide();
		}

		/* Fire edit */
		IPB3TemplateDiffResults.editBit( changeId );
	};
	
	/**
	 * View diff preview
	 */
	this.resolveAllSingle = function(e)
	{
		elem      = Event.element(e);
		var _type = '';
		
		/* fetch ID */
		if ( $( elem ).className.match( /__rc/ ) )
		{
			var test = $( elem ).className.match('__rc([0-9]+)');
			if( test == null || Object.isUndefined( test ) ){ Debug.error("Error"); return; }
			_type    = 'custom';
		}
		else
		{
			var test = $( elem ).className.match('__rn([0-9]+)');
			if( test == null || Object.isUndefined( test ) ){ Debug.error("Error"); return; }
			_type    = 'new';
		}
		
		var changeId = test[1];
		
		/* Grab it via ajax */
		var _url = ipb.vars['base_url'] + "&app=core&module=ajax&section=templatediff&do=resolveAllSingle&change_id=" + changeId + '&type=' + _type + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( _url,
						  {
							method: 'get',
							onSuccess: function (t)
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
											/* Close pop-up */
											if ( IPB3TemplateDiffResults.popUps['edit-' + changeId ] )
											{
												IPB3TemplateDiffResults.popUps['edit-' + changeId ].hide();
											}
											 
											if ( IPB3TemplateDiffResults.popUps['preview-' + changeId ] )
											{
												IPB3TemplateDiffResults.popUps['preview-' + changeId ].hide();
											}
											
											/* Update desc string */
											$('mDesc-' + changeId).update( json['desc'] );
											
											/* Hide merge available data */
											$$('.__whenHasMerge' + changeId).each( function(m){
												m.hide();
											});
										}
									}
									else
									{
										alert( ipb.lang['oops'] + t.responseText );
									}
							},
							onFailure: function()
							{
								return false;
							},
							onException: function()
							{
								return false;
							}
							
						  } );
	};
		
	/**
	 * View diff preview
	 */
	this.saveEdit = function(e)
	{
		elem = Event.element(e);
		
		/* fetch ID */
		var test = $( elem ).className.match('__i([0-9]+)');
		if( test == null || Object.isUndefined( test ) ){ Debug.error("Error"); return; }
		
		var changeId = test[1];
		var content  = $('mergeContent').value;
		
		/* Test to see if we've left in merge stuff */
		if ( content.match( '<<<<<<<<<<' ) || content.match( '>>>>>>>>>' ) )
		{
			alert( ipb.lang['mustfixfirst'] );
		}
		else
		{
			/* Fire it of to Ajaxia for central processing */
			var url = ipb.vars['base_url'] + "&app=core&module=ajax&section=templatediff&do=saveEdit&change_id=" + changeId + '&secure_key=' + ipb.vars['md5_hash'];
			
			/* Set up params */
			var params = { 'content' : content };
					
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
											/* Close pop-up */
											IPB3TemplateDiffResults.popUps['edit-' + changeId ].hide();
											
											/* Update desc string */
											$('mDesc-' + changeId).update( json['desc'] );
											
											/* Hide merge available data */
											$$('.__whenHasMerge' + changeId).each( function(m){
												m.hide();
											});
										}
									}
									else
									{
										alert( ipb.lang['oops'] + t.responseText );
									}
								},
								onException: function( f,e ){ alert( "Exception: " + e ); },
								onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
							  } );
		}
	};
}

IPB3TemplateDiffResults.init();