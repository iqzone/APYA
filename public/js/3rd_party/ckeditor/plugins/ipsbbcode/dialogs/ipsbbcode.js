/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*
* This plug in by:
* Matt Mecham 2011
* Invision Power Services, Inc.
*/

(function()
{
	var ipsBbCodeDialog = function( editor, initVal )
	{
		var fetchDdContents = function()
		{
			arr = [];
			arr[0] = [ ipb.lang['ckeditor__pselectbbcode'], 'nill' ];
								
			$H(editor.config.IPS_BBCODE).each( function( tag )
			{
				k = tag.key;
				v = tag.value;
				
				arr[ arr.length ] = [ v.title, v.tag ];
			} );
			
			return arr;
		};
			
		var fetchTagData = function( tag )
		{
			return ( editor.config.IPS_BBCODE[tag] ) ? editor.config.IPS_BBCODE[tag] : [];
		}
		
		var resetIt = function()
		{
			var dialog    = editor.config._dialog; /* When you're hacky and you know it */
			var option    = dialog.getContentElement( 'info', 'option' ).getElement();
			var content   = dialog.getContentElement( 'info', 'content' ).getElement();
			var select    = dialog.getContentElement( 'info', 'bbcodeTag' ).getElement();
			
			option.hide();
			content.hide();
			
			$('bbcode-description').hide();
		};
		
		// Handles the event when the "Type" selection box is changed.
		var bbCodeChanged = function()
		{
			var dialog    = this.getDialog(),
				typeValue = this.getValue();
			
			editor.config._dialog = dialog;
			
			var tagData   = fetchTagData( typeValue );
			var option    = dialog.getContentElement( 'info', 'option' ).getElement();
			var content   = dialog.getContentElement( 'info', 'content' ).getElement();

			option.hide();
			content.hide();
			$('bbcode-description').hide();
				
			if ( typeValue != 'nill' && tagData.tag )
			{
				$('bbcode-description').update( tagData.desc ).show();

				/* Show contents */
				if ( parseInt( tagData.useoption ) == 1 )
				{
					oid = option.getAttribute('id');
					option.show();
					$(oid).down('label').update( tagData.menu_option_text ? tagData.menu_option_text : ipb.lang['ckeditor__genoption'] );
				}
				
				/* Show value */
				if( parseInt( tagData.single_tag ) == 0 )
				{
					cid = content.getAttribute('id');
					content.show();
					$(cid).down('label').update( tagData.menu_content_text ? tagData.menu_content_text : ipb.lang['ckeditor__gencontent'] );
					
					var html = IPSCKTools.getSelectionHtml( editor );
					
					if ( html != '' && ! Object.isUndefined( $(cid).down('textarea') ) )
					{
				        $(cid).down('textarea').value = html;
				        $(cid).down('textarea').hide();
				        
				        if ( ! $('__x_insert') )
				        {
				        	$(cid).down('textarea').up('div').insert( new Element( 'div', { id: '__x_insert' } ) );
				        }
				        
				         $('__x_insert').update( html );
					}
					else
					{
						
						if ( $('__x_insert') )
				        {
				        	$('__x_insert').update('');
				        	$(cid).down('textarea').value = '';
				        	$(cid).down('textarea').show();
				        }
				    }
				}
			}
		};
	
		return {
			title : ipb.lang['ckeditor__bbcode'],
			minWidth : 350,
			minHeight : 140,
			onShow : function()
			{
				var element = this.getParentEditor().getSelection().getSelectedElement();
			},
			onOk : function()
			{
				var editor    = this.getParentEditor();
				
				element = editor.document.createElement( 'span' );
				element.setAttribute( 'name', 'bbcodename' );
				
				this.commitContent( { element : element } );
				
			    tag			= element.getAttribute('cke-saved-bbcode-tag');
				option		= element.getAttribute('cke-saved-bbcode-option');
				ourcontent	= element.getAttribute('cke-saved-bbcode-content');
				
				if( ourcontent == 'false' )
				{
					ourcontent = '';
				}
				
				if( option == 'false' )
				{
					option = '';
				}

				var tagData = fetchTagData( tag );
				
				if ( ourcontent.match( /\n|\r/ ) )
				{
					Debug.write('moo');
					ourcontent = ourcontent.replace( /\n|\r/g, '<br>' );
				}
				
				if ( tagData.tag )
				{
					if( parseInt( tagData.single_tag ) == 1 )
					{
						if ( parseInt( tagData.useoption ) == 1 )
						{
							mytag = '[' + tag + '=\'' + option + '\']';
						}
						else
						{
							mytag = '[' + tag + ']';
						}
					}
					else if ( parseInt( tagData.useoption ) == 1 )
					{
						if ( tagData.optional_option == 1 && option == '' )
						{
							mytag = '[' + tag + ']' + ourcontent + '[/' + tag + ']';
						}
						else
						{
							mytag = '[' + tag + '=\'' + option + '\']' + ourcontent + '[/' + tag + ']';
						}
					}
					else
					{
						mytag = '[' + tag + ']' + ourcontent + '[/' + tag + ']';
					}
					
					// Insert HTML
					editor.insertHtml( mytag );
					
					resetIt();
				}
				
			},
			contents : [
				{
					id : 'info',
					label : ipb.lang['ckeditor__bbcodelabel'],
					title : ipb.lang['ckeditor__bbcodelabel'],
					elements : [
						{
							id : 'bbcodeTag',
							type : 'select',
							label : ipb.lang['ckeditor__bbcodelabel'],
							items : fetchDdContents(),
							'default': ( typeof( initVal ) != 'undefined' && initVal != '' ) ? initVal : 'nill',
							onLoad: bbCodeChanged,
							onChange : bbCodeChanged,
							commit : function( data )
							{
								var element = data.element;
	
								if ( this.getValue() )
									element.setAttribute( 'cke-saved-bbcode-tag', this.getValue() );
								else
								{
									element.setAttribute( 'cke-saved-bbcode-tag', false );
								}
							}
						},
						{
							id : 'codedescription',
							type : 'html',
							html: '<div id="bbcode-description"></div>'
						},
						{
							id : 'option',
							type : 'text',
							label : ipb.lang['ckeditor__genoption'],
							hidden: 1,
							'default' : '',
							accessKey : 'N',
							setup : function( element )
							{
							},
							commit : function( data )
							{
								var element = data.element;
	
								if ( this.getValue() )
									element.setAttribute( 'cke-saved-bbcode-option', this.getValue() );
								else
								{
									element.setAttribute( 'cke-saved-bbcode-option', false );
								}
							}
						},
						{
							id : 'content',
							type : 'textarea',
							label : ipb.lang['ckeditor__gencontent'],
							'default' : '',
							hidden: 1,
							accessKey : 'O',
							setup : function( data )
							{
							},
							commit : function( data )
							{
								var element = data.element;
	
								if ( this.getValue() )
									element.setAttribute( 'cke-saved-bbcode-content', this.getValue() );
								else
								{
									element.setAttribute( 'cke-saved-bbcode-content', false );
								}
								
								this.value = '';
							}
						}
					]
				}
			]
		};
	};
	
	
	$H(CKEDITOR.config.IPS_BBCODE).each( function( tag )
	{
		k = tag.key;
		v = tag.value;

		if ( v.image && v.image != '' )
		{
			eval( "CKEDITOR.dialog.add( 'ipsbbcode_" + v.tag + "', function( editor ) { return ipsBbCodeDialog( editor, '" + v.tag + "' ); } );" );
		}
	} );
		
	CKEDITOR.dialog.add( 'ipsbbcode', function( editor )
	{
		return ipsBbCodeDialog( editor, '' );
		
	} );
	
} )();
