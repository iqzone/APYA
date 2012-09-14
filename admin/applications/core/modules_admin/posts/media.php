<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * BBCode Media Tag Management
 * Last Updated: $LastChangedDate: 2012-05-22 13:10:11 -0400 (Tue, 22 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10783 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_posts_media extends ipsCommand 
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_bbcode');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=posts&amp;section=media';
		$this->form_code_js	= $this->html->form_code_js	= 'module=posts&section=media';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_posts' ) );

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'do_del':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_delete' );
				$this->_mediaTagDelete();
			break;
			
			case 'form_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagForm( 'add' );
			break;
			
			case 'form_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagForm( 'edit' );
			break;
			
			case 'domediatagadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagSave( 'add' );
			break;
			
			case 'domediatagedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagSave( 'edit' );
			break;
			
			case 'mediatag_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagExport();
			break;

			case 'mediatag_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagImport();
			break;
			
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_moveMediaTag();
			break;
		
			case 'index':
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'media_manage' );
				$this->_mediaTagIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Reorder media tags
	 *
	 * @return	@e void
	 */
	protected function _moveMediaTag()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['media']) AND count($this->request['media']) )
 		{
 			foreach( $this->request['media'] as $this_id )
 			{
 				$this->DB->update( 'bbcode_mediatag', array( 'mediatag_position' => $position ), 'mediatag_id=' . $this_id );
 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Import a mediaTag XML file
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mediaTagImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml( 'mediatag.xml' );

		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['m_upload_failed'];
			$this->_mediaTagIndex();
			return;
		}
		
		$this->doMediaImport( $content );
                    
		$this->registry->output->global_message = $this->lang->words['m_upload_complete'];
		
		$this->_mediaTagIndex();
	}
	
	/**
	 * Abstracted import routine for installer
	 *
	 * @param	string		XML file content
	 * @return	@e void
	 */
	public function doMediaImport( $content )
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$tags = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bbcode_mediatag' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tags[ $r['mediatag_name'] ] = $r['mediatag_id'];
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->fetchElements('mediatag') as $mediatag )
		{
			$entry = $xml->fetchElementsFromRecord( $mediatag );
			$name  = $entry['mediatag_name'];
			
			if ( $name )
			{
				$array = array( 'mediatag_name'		=> $name,
								'mediatag_match'	=> $entry['mediatag_match'],
								'mediatag_replace'	=> $entry['mediatag_replace']
								);
	
				if ( $tags[ $name ] )
				{
					$this->DB->update( 'bbcode_mediatag', $array, "mediatag_id=" . $tags[ $name ] );
				}
				else
				{
					$this->DB->insert( 'bbcode_mediatag', $array );
				}
			}
		}
		
		$this->recacheMediaTag();
	}
		
	/**
	 * Export a mediaTag XML file
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mediaTagExport()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'mediatagexport' );
		$xml->addElement( 'mediataggroup', 'mediatagexport' );

		$select = array( 'select' => '*', 'from' => 'bbcode_mediatag' );
		
		if( $this->request['id'] )
		{
			$select['where'] = 'mediatag_id=' . intval($this->request['id']);
		}
		
		$this->DB->build( $select );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			// Unset a few values to keep it cleaner
			unset( $r['mediatag_id'], $r['mediatag_position'] );
			
			$xml->addElementAsRecord( 'mediataggroup', 'mediatag', $r );
		}
		
		$xmlData = $xml->fetchDocument();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->registry->output->showDownload( $xmlData, 'mediatag.xml', '', 0 );
	}
	
	/**
	 * Delete a custom media tag
	 *
	 * @return	@e void
	 */	
	protected function _mediaTagDelete()
	{
		/* ID */
		$id = intval( $this->request['id'] );
		
		/* Remove */
		$this->DB->delete( 'bbcode_mediatag', "mediatag_id={$id}" );
		
		$this->recacheMediaTag();
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['m_replace_removed'];
	 	$this->_mediaTagIndex();
	}
	
	/**
	 * Save changes to a custom media tag
	 *
	 * @param	string [$type='add']
	 * @return	@e void
	 */
	protected function _mediaTagSave( $type='add' )
	{
		/* INI */
		$errors = array();
		
		/* Check input */
		if( ! $this->request['mediatag_name'] )
		{
			$errors[] = $this->lang->words['m_error_name'];
		}
		
		if( ! $this->request['mediatag_match'] )
		{
			$errors[] = $this->lang->words['m_error_match'];
		}
		
		if( ! $this->request['mediatag_replace'] )
		{
			$errors[] = $this->lang->words['m_error_replace'];
		}
		
		if( count( $errors ) )
		{
			$this->_mediaTagForm( $type, $errors );
			return;
		}
	
	 	/* Data */
	 	$data = array( 	 			
	 					'mediatag_name'    => $this->request['mediatag_name'],
	 					'mediatag_match'   => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_match'] ) ) ) ), ',' ),
	 					'mediatag_replace' => IPSText::formToText( rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_replace'] ) ) ) ), ',' ) ),
	 				);
	 	
	 	/* Check the type */
	 	if( $type == 'add' )
	 	{
	 		/* Insert the record */
	 		$this->DB->insert( 'bbcode_mediatag', $data );
	 		
	 		/* Update cache */
	 		$this->recacheMediaTag();
	 		
	 		/* All done */
			$this->registry->output->global_message	= sprintf( $this->lang->words['m_tag_added'], $data['mediatag_name'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=overview" );
	 	}
	 	else
	 	{
	 		/* ID */
	 		$id = intval( $this->request['id'] );
	 		/* Update */
	 		$this->DB->update( 'bbcode_mediatag', $data, "mediatag_id={$id}" );
	 		
	 		/* Recache */
	 		$this->recacheMediaTag();
	 		
	 		/* Done and done */
			$this->registry->output->global_message	= sprintf( $this->lang->words['m_tag_updated'], $data['mediatag_name'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=overview" );
	 	}
	 
	}	
	
	/**
	 * Show the mediatag add/edit form
	 *
	 * @param	string	[$type='add']
	 * @param	array 	[Optional] array of errors
	 * @return	@e void
	 */
	protected function _mediaTagForm( $type='add', $errors=array() )
	{
		/* Check form type */
		if( $type == 'add' )
		{
			/* Data */
			$data   = array(
								'mediatag_name'    => $this->request['mediatag_name'],
			 					'mediatag_match'   => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_match'] ) ) ) ), ',' ),
			 					'mediatag_replace' => rtrim( str_replace( '&#092;', '\\', str_replace( '&#039', "'", trim( IPSText::stripslashes( $_POST['mediatag_replace'] ) ) ) ), ',' ),
							);
		}
		else
		{
			/* Data */
			$id		= intval( $this->request['id'] );
			$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bbcode_mediatag', 'where' => "mediatag_id={$id}" ) );
			
			/* Set Defaults */
			$data['mediatag_name']    = !empty( $this->request['mediatag_name'] )		? $this->request['mediatag_name']							: $data['mediatag_name'];
			$data['mediatag_match']   = !empty( $this->request['mediatag_match'] )		? IPSText::textToForm( $this->request['mediatag_match'] )	: IPSText::textToForm( $data['mediatag_match'] );
			$data['mediatag_replace'] = !empty( $this->request['mediatag_replace'] )	? IPSText::textToForm( $this->request['mediatag_replace'] )	: IPSText::textToForm( $data['mediatag_replace'] );
		}
		
		/* Setup Form */
		$this->registry->output->html .= $this->html->mediaTagForm( $type, $data, $errors );
	}	
	
	/**
	 * List all the the current media tag types
	 *
	 * @return	@e void
	 */
	protected function _mediaTagIndex()
	{
		/* Query Bookmarks */
		$this->DB->build( array(
												'select' => '*',
												'from'   => 'bbcode_mediatag',
												'order'  => 'mediatag_position ASC',
										)	);
		$this->DB->execute();
		
		$bbcode_rows = "";

		/* List the bookmarks */
		while( $r = $this->DB->fetch() )
		{
			$bbcode_rows .= $this->html->mediaTagRow( $r );
		}
		
		/* End table and output */
        $this->registry->output->html .= $this->html->mediaTagWrapper( $bbcode_rows );
	}
	
	/**
	 * Recache the mediatag config
	 *
	 * @return	@e void
	 */
	public function recacheMediaTag()
	{
		/* Query the tags */
		$this->DB->build( array( 'select' => '*', 'from' => 'bbcode_mediatag', 'order' => 'mediatag_position ASC' ) );
		$this->DB->execute();

		$media_config = array();

		while( $r = $this->DB->fetch() )
		{
			$media_config[$r['mediatag_name']] = array(
														'match'   => preg_replace( "#{[0-9]}#", "(.*?)", str_replace( '.', '\.', str_replace( '?', '?', $r['mediatag_match'] ) ) ),
														'replace' => $r['mediatag_replace'],
													);
		}

		/* Save to cache */
		$this->cache->setCache( 'mediatag', $media_config, array( 'array' => 1 ) );
	}
}