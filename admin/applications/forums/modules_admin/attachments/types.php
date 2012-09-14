<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Attachments: Manage
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		Mon 24th May 2004
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_attachments_types extends ipsCommand
{
	/**
	 * HTML  object
	 *
	 * @var		object
	 */
	protected $html;
	
	/**
	 * Main execution point
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_attachments' );
		$this->html->form_code    = 'module=attachments&amp;section=types&amp;';
		$this->html->form_code_js = 'module=attachments&amp;section=types&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_attachments' ) );

		//-----------------------------------------
		// StRT!
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'attach_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_add' );
				$this->attachmentTypeForm('add');
			break;
			
			case 'attach_doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_add' );
				$this->attachmentTypeSave('add');
			break;
			
			case 'attach_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_edit' );
				$this->attachmentTypeForm('edit');
			break;
			
			case 'attach_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_delete' );
				$this->attachmentTypeDelete();
			break;
			
			case 'attach_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_edit' );
				$this->attachmentTypeSave('edit');
			break;
			
			case 'attach_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_export' );
				$this->attachmentTypeExport();
			break;
			
			case 'attach_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_import' );
				$this->attachmentTypeImport();
			break;
			
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mime_view' );
				$this->attachmentTypesOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Imports attachment types from an xml document
	 *
	 * @return	@e void
	 */
	public function attachmentTypeImport()
	{
		/* Get the XML Content */
		$content = $this->registry->adminFunctions->importXml( 'ipb_attachtypes.xml' );
		
		/* Check to make sure we have content */
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['ty_failed'];
			$this->attachmentTypesOverview();
		}
		
		/* Get the XML class */
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		/* Get a list of the types already installed */
		$types = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type', 'order' => "atype_extension" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$types[ $r['atype_extension'] ] = 1;
		}
		
		/* Loop through the xml document and insert new types */
		foreach( $xml->fetchElements('attachtype') as $record )
		{
			$entry  = $xml->fetchElementsFromRecord( $record );

			/* Build the insert array */
			$insert_array = array( 'atype_extension' => $entry['atype_extension'],
								   'atype_mimetype'  => $entry['atype_mimetype'],
								   'atype_post'      => $entry['atype_post'],
								   'atype_img'       => $entry['atype_img']
								 );

			/* Bypass if this type has already been added */
			if ( $types[ $entry['atype_extension'] ] )
			{
				continue;
			}
			
			/* Insert the new type */
			if ( $entry['atype_extension'] and $entry['atype_mimetype'] )
			{
				$this->DB->insert( 'attachments_type', $insert_array );
			}
		}
		
		/* Rebuild the cache and bounce */
		$this->attachmentTypeCacheRebuild();                    
		
		$this->registry->output->global_message = $this->lang->words['ty_imported'];		
		$this->attachmentTypesOverview();
	}	
	
	/**
	 * Builds the attachment type xml export
	 *
	 * @return	@e void
	 */
	public function attachmentTypeExport()
	{
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'attachtypesexport' );
		$xml->addElement( 'attachtypesgroup', 'attachtypesexport' );

		/* Query the attachment Types */
		$this->DB->build( array( 
								'select' => 'atype_extension,atype_mimetype,atype_post,atype_img',
								'from'   => 'attachments_type',
								'order'  => "atype_extension" 
						)	 );
		$this->DB->execute();
		
		/* Loop through the types */
		$entry = array();
		
		while( $r = $this->DB->fetch() )
		{
			$xml->addElementAsRecord( 'attachtypesgroup', 'attachtype', $r );
		}

		/* Send for download */
		$this->registry->output->showDownload( $xml->fetchDocument(), 'attachments.xml', "unknown/unknown", false );
	}	
	
	/**
	 * Removes the specified attachment type
	 *
	 * @return	@e void
	 */
	public function attachmentTypeDelete()
	{
		/* INI */
		$this->request[ 'id'] =  intval( $this->request['id']  );
		
		/* Delete the type */
		$this->DB->delete( 'attachments_type', "atype_id={$this->request['id']}" );
		
		/* Build the cache and Bounce */		
		$this->attachmentTypeCacheRebuild();
		
		$this->registry->output->global_message = $this->lang->words['ty_deleted'];	
		$this->attachmentTypesOverview();
	}

	/**
	 * Processes the from for adding/editing attachments
	 *
	 * @param	string	$type	Either add or edit	 
	 * @return	@e void
	 */
	public function attachmentTypeSave( $type='add' )
	{
		/* INI */
		$this->request['id'] = intval( $this->request['id'] );
		
		/* Make sure the form was filled out */
		if ( ! $this->request['atype_extension'] or ! $this->request['atype_mimetype'] )
		{
			$this->registry->output->global_message = $this->lang->words['ty_enterinfo'];
			$this->attachmentTypeForm( $type );
			return;
		}
		
		/* Build the save array */
		$save_array = array( 'atype_extension' => str_replace( ".", "", $this->request['atype_extension'] ),
							 'atype_mimetype'  => trim( $this->request['atype_mimetype'] ),
							 'atype_post'      => trim( $this->request['atype_post'] ),
							 'atype_img'       => trim( $this->request['atype_img'] ) );
		
		/* Add attachment type to the database */
		if ( $type == 'add' )
		{
			/* Check to see if this attachment type already exists */
			$attach = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='".$save_array['atype_extension']."'" ) );
			
			if ( $attach['atype_id'] )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['ty_already'], $save_array['atype_extension'] );
				$this->attachmentTypeForm($type);
				return;
			}
			
			/* Insert the attachment type */
			$this->DB->insert( 'attachments_type', $save_array );
			
			/* Done Message */
			$this->registry->output->global_message = $this->lang->words['ty_added'];
			
		}
		else
		{
			/* Update the attachment type */
			$this->DB->update( 'attachments_type', $save_array, 'atype_id=' . $this->request['id'] );
			
			/* Done Message */
			$this->registry->output->global_message = $this->lang->words['ty_edited'];
		}
		
		/* Cache and Bounce */
		$this->attachmentTypeCacheRebuild();		
		$this->attachmentTypesOverview();
	}	
	
	/**
	 * Displays the form for adding/editing attachment types
	 *
	 * @param	string	$type	Either add or edit
	 * @return	@e void
	 */
	public function attachmentTypeForm( $type='add' )
	{
		/* INI */
		$this->request['id']		= $this->request['id'] ? intval( $this->request['id'] ) : 0;
		$this->request['baseon']	= $this->request['baseon'] ? intval( $this->request['baseon'] ) : 0;
		
		/* Navigation */
		$this->registry->output->nav[] = array( '', $this->lang->words['ty_addedit'] );
		
		$baseon	= '';
		
		if( $type == 'add' )
		{
			/* Setup */
			$code   = 'attach_doadd';
			$button = $this->lang->words['ty_addnew'];
			$id     = 0;
			
			/* Default Data */
			if( $this->request['baseon'] )
			{
				$attach = $this->DB->buildAndFetch( array( 
																	'select' => '*', 
																	'from' => 'attachments_type', 
																	'where' => 'atype_id=' . $this->request['baseon']
														)		);
			}
			else
			{
				$attach = array( 'atype_extension' 	=> '',
								 'atype_mimetype'	=> '',
								 'atype_post'		=> '',
								 'atype_img'		=> '' );
			}
			
			/* Generate Based On Dropdown*/
			$dd = array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type', 'order' => 'atype_extension' ) );
			$this->DB->execute();
		
			while( $r = $this->DB->fetch() )
			{
				$dd[] = array( $r['atype_id'], $this->lang->words['ty_baseon'] . $r['atype_extension'] );
			}
				
			$title	= $button;
			$baseon	= $this->html->attachmentTypeBaseOn( $this->registry->output->formDropdown( 'baseon', $dd ) );
		}
		else
		{
			/* Setup */
			$code   = 'attach_doedit';
			$button = $this->lang->words['ty_edit'];
			$title  = $button;
			$id     = intval( $this->request['id'] );
			
			/* Default Data */
			$attach = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => 'atype_id='.ipsRegistry::$request['id'] ) );
		
			/* Check for valid id */
			if ( ! $attach['atype_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['ty_noid'];
				$this->attachmentTypesOverview();
			}
		}
		
		/* Form Elements */
		$form = array(
						'atype_extension' => $this->registry->output->formSimpleInput( 'atype_extension', !empty( $this->request['atype_extension'] )	? $this->request['atype_extension']	: $attach['atype_extension'], 10 ),
						'atype_mimetype'  => $this->registry->output->formSimpleInput( 'atype_mimetype' , !empty( $this->request['atype_mimetype'] )	? $this->request['atype_mimetype']	: $attach['atype_mimetype'] , 40 ),
						'atype_post'      => $this->registry->output->formYesNo(       'atype_post'     , !empty( $this->request['atype_post'] )		? $this->request['atype_post']		: $attach['atype_post']          ),
						'atype_img'       => $this->registry->output->formSimpleInput( 'atype_img'      , !empty( $this->request['atype_img'] )			? $this->request['atype_img']		: $attach['atype_img']      , 40 ),
					);
		
		/* Output */
		$this->registry->output->html .= $this->html->attachmentTypeForm( $title, $code, $id, $form, $button, $baseon );
	}	
	
	/**
	 * Shows the attachment types that have been setup
	 *
	 * @return	@e void
	 */
	public function attachmentTypesOverview()
	{
		/* Get the attachments */
		$this->DB->build( array( 'select' => '*', 'from' => 'attachments_type', 'order' => 'atype_extension' ) );
		$this->DB->execute();
		
		/* Loop through the attachments */
		$attach_rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_imagedir'] = $this->registry->output->skin['set_image_dir'];
			
			$r['apost_checked']  = $r['atype_post']  ? "<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='{$this->lang->words['yesno_yes']}' />" : "<img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='{$this->lang->words['yesno_no']}' />";
			
			$attach_rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->attachmentTypeOverview( $attach_rows );
	}	
	
	/*
	 * Rebuilds the attachment type cache
	 *
	 * @return	@e void
	 */
	public function attachmentTypeCacheRebuild()
	{
		$cache = array();
			
		$this->DB->build( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_img', 'from' => 'attachments_type', 'where' => "atype_post=1" ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$r['atype_extension']			= strtolower( $r['atype_extension'] );
			$cache[ $r['atype_extension'] ] = $r;
		}
		
		$this->cache->setCache( 'attachtypes', $cache, array( 'array' => 1, 'donow' => 0 ) );		
	}
}