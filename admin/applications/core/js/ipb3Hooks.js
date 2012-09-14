
function addAnotherRequirement()
{
	requireIndex = parseInt(requireIndex) + 1;

	// Show the data location dropdown now

	url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getApplications&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + requireIndex + "&name=requireApp";
	url = url.replace( /&amp;/g, '&' );

	new Ajax.Request( url,
	{
		method: 'GET',
		onSuccess: function ( t )
			{
				var newApp = ipb.templates['new_app_require'].evaluate({ index: requireIndex, dropdown: t.responseText });
				
				if ( $('RequirementsContainer').down('tbody') )
				{
					$('RequirementsContainer').down('tbody').insert( newApp );
				}
				else
				{
					$('RequirementsContainer').insert( newApp );
				}
			},
		onException: function( f,e ){ alert( "Exception: " + e ); },
		onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
	} );
}

function getAppVersions( getIndex )
{
	getIndex   = parseInt(getIndex);
	indexValue = $F('requireApp[' + getIndex + ']');
	
	// Show the versions dropdowns now
	if ( indexValue == 0 )
	{
		$('requirementRow_' + getIndex + '_versions').update('');
	}
	else
	{
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getAppVersions&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + getIndex + "&chosenApp=" + indexValue;
		url = url.replace( /&amp;/g, '&' );
	
		new Ajax.Request( url,
		{
			method: 'GET',
			onSuccess: function ( t )
				{
					$('requirementRow_' + getIndex + '_versions').update( t.responseText );
				},
			onException: function( f,e ){ alert( "Exception: " + e ); },
			onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
		} );
	}
}

function removeRequirement( idToRemove )
{
	idToRemove = parseInt(idToRemove);
	
	if( $('requirementTitleRow_' + idToRemove ) != null )
	{
		$('requirementTitleRow_' + idToRemove ).remove();
	}

	if( $('requirementRow_' + idToRemove ) != null )
	{
		$('requirementRow_' + idToRemove ).remove();
	}
}


function addAnotherFile()
{
	elementIndex = parseInt(elementIndex) + 1;

	var newFile = ipb.templates['new_hook_file'].evaluate({ index: elementIndex });
	
	if ( $('fileTableContainer').down('tbody') )
	{
		$('fileTableContainer').down('tbody').insert( newFile );
	}
	else
	{
		$('fileTableContainer').insert( newFile );
	}
}

function removeFile( elementIndex )
{
	elementIndex = parseInt(elementIndex);

	if( $('fileRow_' + elementIndex ) != null )
	{
		$('fileRow_' + elementIndex ).remove();
	}
}

function selectHookType( elementIndex )
{
	var type = $F('hook_type[' + elementIndex + ']');

	if( $('tr_classToOverload[' + elementIndex + ']') != null )
	{
		$('tr_classToOverload[' + elementIndex + ']').remove();
	}
	
	if( $('tr_skinGroup[' + elementIndex + ']') != null )
	{
		$('tr_skinGroup[' + elementIndex + ']').remove();
	}
	
	if( $('tr_skinFunction[' + elementIndex + ']') != null )
	{
		$('tr_skinFunction[' + elementIndex + ']').remove();
	}
	
	if( $('tr_type[' + elementIndex + ']') != null )
	{
		$('tr_type[' + elementIndex + ']').remove();
	}
	
	if( $('tr_id[' + elementIndex + ']') != null )
	{
		$('tr_id[' + elementIndex + ']').remove();
	}
	
	if( $('tr_position[' + elementIndex + ']') != null )
	{
		$('tr_position[' + elementIndex + ']').remove();
	}
	
	if( $('tr_dataLocation[' + elementIndex + ']') != null )
	{
		$('tr_dataLocation[' + elementIndex + ']').remove();
	}
	
	if( $('tr_libApplication[' + elementIndex + ']') != null )
	{
		$('tr_libApplication[' + elementIndex + ']').remove();
	}
		
	if( type == 'templateHooks' )
	{
		// Show the skin dropdown now

		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getGroupsForAdd&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + elementIndex;
		url = url.replace( /&amp;/g, '&' );
	
		new Ajax.Request( url,
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								var newRow = ipb.templates['hook_skinGroup'].evaluate({ index: elementIndex, dropdown: t.responseText });
								
								if ( $('fileTable_' + elementIndex).down('tbody') )
								{
									$('fileTable_' + elementIndex).down('tbody').insert( newRow );
								}
								else
								{
									$('fileTable_' + elementIndex).insert( newRow );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ); },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
						  } );
	}
	else if( type == 'dataHooks' )
	{
		// Show the data location dropdown now

		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getDataLocationsForAdd&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + elementIndex;
		url = url.replace( /&amp;/g, '&' );
	
		new Ajax.Request( url,
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								var newRow = ipb.templates['hook_dataLocation'].evaluate({ index: elementIndex, dropdown: t.responseText });
								
								if ( $('fileTable_' + elementIndex).down('tbody') )
								{
									$('fileTable_' + elementIndex).down('tbody').insert( newRow );
								}
								else
								{
									$('fileTable_' + elementIndex).insert( newRow );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ); },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
						  } );
	}
	else if( type == 'libraryHooks' )
	{
		// Show the data location dropdown now

		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getApplications&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + elementIndex;
		url = url.replace( /&amp;/g, '&' );
	
		new Ajax.Request( url,
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								var replace = { index: elementIndex, dropdown: t.responseText };
								var newRow  = ipb.templates['hook_classToOverload'].evaluate(replace) + ipb.templates['hook_libApplication'].evaluate(replace);
								
								if ( $('fileTable_' + elementIndex).down('tbody') )
								{
									$('fileTable_' + elementIndex).down('tbody').insert( newRow );
								}
								else
								{
									$('fileTable_' + elementIndex).insert( newRow );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ); },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
						  } );
	}
	else if( type != '0' )
	{
		// Show the classToOverload field
		
		var newRow = ipb.templates['hook_classToOverload'].evaluate({ index: elementIndex });
		
		if ( $('fileTable_' + elementIndex).down('tbody') )
		{
			$('fileTable_' + elementIndex).down('tbody').insert( newRow );
		}
		else
		{
			$('fileTable_' + elementIndex).insert( newRow );
		}
	}
}

function getTemplatesForAdd( elementIndex )
{
	var type = $F('skinGroup[' + elementIndex + ']');
	
	if( $('tr_skinFunction[' + elementIndex + ']') != null )
	{
		$('tr_skinFunction[' + elementIndex + ']').remove();
	}
	
	if( $('tr_type[' + elementIndex + ']') != null )
	{
		$('tr_type[' + elementIndex + ']').remove();
	}
	
	if( $('tr_id[' + elementIndex + ']') != null )
	{
		$('tr_id[' + elementIndex + ']').remove();
	}
	
	if( $('tr_position[' + elementIndex + ']') != null )
	{
		$('tr_position[' + elementIndex + ']').remove();
	}
	
	/* Got a type? */
	if( type != '0' )
	{
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getTemplatesForAdd&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + elementIndex + "&group=" + type;
		url = url.replace( /&amp;/g, '&' );
	
		new Ajax.Request( url,
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								var newRow = ipb.templates['hook_skinFunction'].evaluate({ index: elementIndex, dropdown: t.responseText });
								
								if ( $('fileTable_' + elementIndex).down('tbody') )
								{
									$('fileTable_' + elementIndex).down('tbody').insert( newRow );
								}
								else
								{
									$('fileTable_' + elementIndex).insert( newRow );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ); },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
						  } );
	}
}

function getTypeOfHook( elementIndex )
{
	var template = $F('skinFunction[' + elementIndex + ']');
	
	if( $('tr_type[' + elementIndex + ']') != null )
	{
		$('tr_type[' + elementIndex + ']').remove();
	}
	
	if( $('tr_id[' + elementIndex + ']') != null )
	{
		$('tr_id[' + elementIndex + ']').remove();
	}
	
	if( $('tr_position[' + elementIndex + ']') != null )
	{
		$('tr_position[' + elementIndex + ']').remove();
	}
	
	/* Insert the new hook type row */
	if ( template != '0' )
	{
		var newRow = ipb.templates['hook_pointTypes'].evaluate({ index: elementIndex });
		
		if ( $('fileTable_' + elementIndex).down('tbody') )
		{
			$('fileTable_' + elementIndex).down('tbody').insert( newRow );
		}
		else
		{
			$('fileTable_' + elementIndex).insert( newRow );
		}
	}
}

function getHookIds( elementIndex )
{
	var template	= $F('skinFunction[' + elementIndex + ']');
	var type		= $F('type[' + elementIndex + ']');
	var group		= $F('skinGroup[' + elementIndex + ']');

	if( $('tr_id[' + elementIndex + ']') != null )
	{
		$('tr_id[' + elementIndex + ']').remove();
	}
	
	if( $('tr_position[' + elementIndex + ']') != null )
	{
		$('tr_position[' + elementIndex + ']').remove();
	}
	
	/* Got a type? */
	if( type != '0' )
	{
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getHookIds&amp;secure_key=" + ipb.vars['md5_hash'] + "&i=" + elementIndex + "&type=" + type + "&template=" + template + "&group=" + group;
		url = url.replace( /&amp;/g, '&' );
	
		new Ajax.Request( url,
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								var newRow = ipb.templates['hook_pointIds'].evaluate({ index: elementIndex, dropdown: t.responseText });
								
								if ( $('fileTable_' + elementIndex).down('tbody') )
								{
									$('fileTable_' + elementIndex).down('tbody').insert( newRow );
								}
								else
								{
									$('fileTable_' + elementIndex).insert( newRow );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ); },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ); }
						  } );
	}
}

function getHookEntryPoints( elementIndex )
{
	var type = $F('type[' + elementIndex + ']');
	
	Debug.write( type );
	
	if( $('tr_position[' + elementIndex + ']') != null )
	{
		$('tr_position[' + elementIndex + ']').remove();
	}
	
	var options = '';
	
	if( type == 'foreach' )
	{
		options += "<option value='outer.pre'>(outer.pre) " + ipb.lang['a_outerpre'] + "</option>";
		options += "<option value='inner.pre'>(inner.pre) " + ipb.lang['a_innerpre'] + "</option>";
		options += "<option value='inner.post'>(inner.post) " + ipb.lang['a_innerpost']+ "</option>";
		options += "<option value='outer.post'>(outer.post) " + ipb.lang['a_outerpost']+ "</option>";
	}
	else
	{
		options += "<option value='pre.startif'>(pre.startif) " + ipb.lang['a_prestartif'] + "</option>";
		options += "<option value='post.startif'>(post.startif) " + ipb.lang['a_poststartif'] + "</option>";
		options += "<option value='pre.else'>(pre.else) " + ipb.lang['a_preelse'] + "</option>";
		options += "<option value='post.else'>(post.else) " + ipb.lang['a_postelse'] + "</option>";
		options += "<option value='pre.endif'>(pre.endif) " + ipb.lang['a_preendif'] + "</option>";
		options += "<option value='post.endif'>(post.endif) " + ipb.lang['a_postendif'] + "</option>";
	}
	/* Insert the new hook type row */
	//if ( type != '0' )
	//{
		var newRow = ipb.templates['hook_pointLocation'].evaluate({ index: elementIndex, hookPoints: options });
		
		if ( $('fileTable_' + elementIndex).down('tbody') )
		{
			$('fileTable_' + elementIndex).down('tbody').insert( newRow );
		}
		else
		{
			$('fileTable_' + elementIndex).insert( newRow );
		}
	//}
}