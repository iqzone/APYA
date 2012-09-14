<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * BBCode Management
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_posts_bbcode extends ipsCommand 
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
		
		$this->form_code	= $this->html->form_code	= 'module=posts&amp;section=bbcode';
		$this->form_code_js	= $this->html->form_code_js	= 'module=posts&section=bbcode';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_posts' ) );

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'bbcode_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeForm('add');
			break;

			case 'bbcode_doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeSave('add');
			break;

			case 'bbcode_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeForm('edit');
			break;

			case 'bbcode_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeSave('edit');
			break;

			case 'bbcode_test':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeTest();
			break;

			case 'bbcode_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_delete' );
				$this->_bbcodeDelete();
			break;

			case 'bbcode_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeExport();
			break;

			case 'bbcode_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeImport();
			break;
			
			case 'bbcode_import_all':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeImportAll();
			break;
			case 'bbcode_export_all':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeExportAll();
			break;

			case 'bbcode':
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bbcode_manage' );
				$this->_bbcodeStart();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();			
	}
	
	/**
	 * Export all bbcode XML file
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeExportAll()
	{
		/* Loop through apps */
		$msg     = array();
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		foreach( ipsRegistry::$applications as $appDir => $appData )
		{
			$_c = 0;
			
			$xml->newXMLDocument();
			$xml->addElement( 'bbcodeexport' );
			$xml->addElement( 'bbcodegroup', 'bbcodeexport' );

			$this->DB->build( array( 'select' => '*', 'from' => 'custom_bbcode', 'where' => 'bbcode_app=\'' . $appDir . "'" ) );
			$this->DB->execute();

			while ( $r = $this->DB->fetch() )
			{
				$_c++;

				$xml->addElementAsRecord( 'bbcodegroup', 'bbcode', $r );
			}

			$xmlData = $xml->fetchDocument();
			
			
			$file = IPSLib::getAppDir(  $appDir ) . '/xml/' . $appDir . '_bbcode.xml';
			
			/* remove old file */
			@unlink( $file );
			
			/* Write it.. */
			if ( $_c )
			{
				@file_put_contents( $file, $xmlData );
			}
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['bbcode'][ $appDir ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
                    
		$this->registry->output->setMessage( "Completed" . implode( "<br />", $msg ), 1 );
		
		$this->_bbcodeStart();
	}
	
	/**
	 * Import a bbcode XML file
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeImportAll()
	{
		/* Loop through apps */
		$msg     = array();
		
		foreach( ipsRegistry::$applications as $appDir => $appData )
		{
			$file = IPSLib::getAppDir( $appDir ) . '/xml/' . $appDir . '_bbcode.xml';
			
			if ( is_file( $file ) )
			{
				$content = file_get_contents( $file );
				
				if ( $content )
				{
					$result = $this->bbcodeImportDo( $content, $appDir );
					
					$msg[] = sprintf( $this->lang->words['bbcode_import_app'], $appDir, $result['inserted'], $result['updated'] );
				}
			}
			
			/* In dev time stamp? */
			if ( IN_DEV )
			{
				$cache = $this->caches['indev'];
				$cache['import']['bbcode'][ $appDir ] = time();
				$this->cache->setCache( 'indev', $cache, array( 'donow' => 1, 'array' => 1 ) );
			}
		}
                    
		$this->registry->output->setMessage( implode( "<br />", $msg ), 1 );
		
		$this->_bbcodeStart();
	}
	
	/**
	 * Import a bbcode XML file
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml();
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_error = $this->lang->words['upload_failed'];
			$this->_bbcodeStart();
			return;
		}
		
		/* Do it */
		$this->bbcodeImportDo( $content );
                    
		$this->registry->output->global_message = $this->lang->words['import_complete'];
		
		$this->_bbcodeStart();
	}
	
	/**
	 * Perform the BBcode import
	 * Abstracted here so the installer can use it (and potentially other apps)
	 *
	 * @param	string		Raw XML code
	 * @return	array 		[ 'inserted' => int, 'updated' => int ]
	 */
	public function bbcodeImportDo( $content, $app='core' )
	{
		/* Init vars */
		$tags     = array();
		$return   = array( 'updated' => 0, 'inserted' => 0 );
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		$content = trim( $content );
		
		if ( empty( $content ) )
		{
			return $return;
		}
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'custom_bbcode' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$tags[ $r['bbcode_tag'] ] = 1;
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->fetchElements('bbcode') as $bbcode )
		{
			$entry  = $xml->fetchElementsFromRecord( $bbcode );

			$bbcode_title				= $entry['bbcode_title'];
			$bbcode_desc				= $entry['bbcode_desc'];
			$bbcode_tag					= $entry['bbcode_tag'];
			$bbcode_replace				= $entry['bbcode_replace'];
			$bbcode_useoption			= $entry['bbcode_useoption'];
			$bbcode_example				= $entry['bbcode_example'];
			$bbcode_switch_option		= $entry['bbcode_switch_option'];
			$bbcode_menu_option_text	= $entry['bbcode_menu_option_text'];
			$bbcode_menu_content_text	= $entry['bbcode_menu_content_text'];
			$bbcode_single_tag			= $entry['bbcode_single_tag'];
			$bbcode_php_plugin			= $entry['bbcode_php_plugin'];
			$bbcode_no_parsing			= $entry['bbcode_no_parsing'];
			$bbcode_optional_option		= $entry['bbcode_optional_option'];
			$bbcode_aliases				= $entry['bbcode_aliases'];
			$bbcode_image				= $entry['bbcode_image'];
			$bbcode_app					= ( $entry['bbcode_app'] ) ? $entry['bbcode_app'] : $app;
			$bbcode_protected			= ( $entry['bbcode_protected'] ) ? $entry['bbcode_protected'] : 0;
			$bbcode_sections			= ( $entry['bbcode_sections'] ) ? $entry['bbcode_sections'] : 'all';
			$bbcode_regex				= ( $entry['bbcode_custom_regex'] ) ? $entry['bbcode_custom_regex'] : '';
			
			if ( $tags[ $bbcode_tag ] )
			{
				$bbarray = array(
								 'bbcode_title'             => $bbcode_title,
								 'bbcode_desc'              => $bbcode_desc,
								 'bbcode_tag'               => $bbcode_tag,
								 'bbcode_replace'           => IPSText::safeslashes($bbcode_replace),
								 'bbcode_useoption'         => $bbcode_useoption,
								 'bbcode_example'           => $bbcode_example,
								 'bbcode_switch_option'     => $bbcode_switch_option,
								 'bbcode_menu_option_text'  => $bbcode_menu_option_text,
								 'bbcode_menu_content_text' => $bbcode_menu_content_text,
 								 'bbcode_php_plugin'		=> $bbcode_php_plugin,
 								 'bbcode_no_parsing'		=> $bbcode_no_parsing,
 								 'bbcode_optional_option'	=> $bbcode_optional_option,
 								 'bbcode_aliases'			=> $bbcode_aliases,
 								 'bbcode_image'				=> $bbcode_image,
 								 'bbcode_single_tag'		=> $bbcode_single_tag,
								 'bbcode_app'				=> $bbcode_app,
								 'bbcode_protected'			=> $bbcode_protected,
								 'bbcode_custom_regex'		=> $bbcode_regex,
								);
								
				$this->DB->update( 'custom_bbcode', $bbarray, "bbcode_tag='" . $bbcode_tag . "'" );
				$return['updated']++;
				continue;
			}
			
			if ( $bbcode_tag )
			{
				$bbarray = array(
								 'bbcode_title'             => $bbcode_title,
								 'bbcode_desc'              => $bbcode_desc,
								 'bbcode_tag'               => $bbcode_tag,
								 'bbcode_replace'           => IPSText::safeslashes($bbcode_replace),
								 'bbcode_useoption'         => $bbcode_useoption,
								 'bbcode_example'           => $bbcode_example,
								 'bbcode_switch_option'     => $bbcode_switch_option,
								 'bbcode_menu_option_text'  => $bbcode_menu_option_text,
								 'bbcode_menu_content_text' => $bbcode_menu_content_text,
								 'bbcode_groups'			=> 'all',
								 'bbcode_sections'			=> $bbcode_sections,
								 'bbcode_php_plugin'		=> $bbcode_php_plugin,
 								 'bbcode_no_parsing'		=> $bbcode_no_parsing,
 								 'bbcode_optional_option'	=> $bbcode_optional_option,
 								 'bbcode_aliases'			=> $bbcode_aliases,
 								 'bbcode_image'				=> $bbcode_image,
 								 'bbcode_single_tag'		=> $bbcode_single_tag,
								 'bbcode_app'				=> $bbcode_app,
								 'bbcode_protected'			=> $bbcode_protected,
								 'bbcode_custom_regex'		=> $bbcode_regex,
								);
								
				$this->DB->insert( 'custom_bbcode', $bbarray );
				$return['inserted']++;
			}
		}
		
		$this->bbcodeRebuildCache();
		
		return $return;
	}
		
	/**
	 * Export a bbcode XML file
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeExport()
	{
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'bbcodeexport' );
		$xml->addElement( 'bbcodegroup', 'bbcodeexport' );

		//-----------------------------------------
		// Get bbcodes
		//-----------------------------------------

		$select = array( 'select' => '*', 'from' => 'custom_bbcode' );
		
		if( $this->request['id'] )
		{
			$select['where'] = 'bbcode_id=' . intval($this->request['id']);
		}
		
		$this->DB->build( $select );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$xml->addElementAsRecord( 'bbcodegroup', 'bbcode', $r );
		}
		
		$xmlData = $xml->fetchDocument();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->registry->output->showDownload( $xmlData, 'bbcode.xml', '', 0 );
	}
	
	/**
	 * Delete a bbcode
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeDelete()
	{
		if ( ! $this->request['id'] )
		{
			$this->registry->output->global_message = $this->lang->words['no_bbcode_found_delete'];
			$this->_bbcodeStart();
			return;
		}
		
		$this->DB->delete( 'custom_bbcode', 'bbcode_id=' . intval($this->request['id']) );
		
		$this->bbcodeRebuildCache();
		
		$this->_bbcodeStart();
	}
	
	/**
	 * Test a bbcode
	 *
	 * @return	@e void		[Outputs to screen]
	 * @todo 	[Future] Also show the resulting HTML alongside the formatted content
	 */
	protected function _bbcodeTest()
	{
		$t = IPSText::stripslashes(htmlspecialchars($_POST['bbtest']));

		//-----------------------------------------
		// Run through libraries
		//-----------------------------------------
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_smilies		= 0;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
		//-----------------------------------------
		// Store the url/fix base url
		//-----------------------------------------
		
		$_current	= $this->settings[ 'base_url' ];
		$this->settings['base_url'] = $this->settings['board_url'] . '/index.php?';
		
		//-----------------------------------------
		// Parse
		//-----------------------------------------
		
		$t = IPSText::getTextClass('bbcode')->preDbParse( $t );
		$t = IPSText::getTextClass('bbcode')->preDisplayParse( $t );
		
		//-----------------------------------------
		// Restore base url
		//-----------------------------------------
		
		$this->settings['base_url'] = $_current;

		$this->registry->output->setMessage( $this->lang->words['bbcode_test'] . $t, true );
		
		$this->_bbcodeStart();
	}
	
	/**
	 * Save a bbcode [add|edit]
	 *
	 * @param	string		[add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeSave($type='add')
	{
		if ( $type == 'edit' )
		{
			if ( ! $this->request['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_bbcode_found_edit'];
				$this->_bbcodeForm($type);
				return;
			}
			
			$bbcode = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'custom_bbcode', 'where' => 'bbcode_id=' . intval($this->request['id']) ) );

			if( !$bbcode['bbcode_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_bbcode_found_edit'], 111162 );
			}
			
			if( $this->request['bbcode_tag'] )
			{
				$duplicate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'custom_bbcode', 'where' => "bbcode_tag='{$this->request['bbcode_tag']}'" ) );
				
				if( $duplicate['bbcode_id'] && $duplicate['bbcode_id'] != $this->request['id'] )
				{
					$this->registry->output->global_message = $this->lang->words['tag_already'];
					$this->_bbcodeForm($type);
					return;
				}
				
				/* Check aliases */
				$this->DB->build( array( 'select' => '*',
										 'from'	  => 'custom_bbcode',
										 'where'  => 'bbcode_aliases LIKE \'%' . $this->DB->addSlashes( $this->request['bbcode_tag'] ) . '%\'' ) );
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					if( $row['bbcode_id'] == $this->request['id'] )
					{
						continue;
					}
					
					foreach( explode( ',', $row['bbcode_aliases'] ) as $alias )
					{
						if ( strtolower( $alias ) == strtolower( $this->request['bbcode_tag'] ) )
						{
							$this->registry->output->global_message = sprintf( $this->lang->words['tag_already_alias'], $this->request['bbcode_tag'], $row['bbcode_tag'] );
							$this->_bbcodeForm($type);
							return;
						}
					}
				}
			}
		}
		else
		{
			$bbcode = array();
			
			if( $this->request['bbcode_tag'] )
			{
				$duplicate = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'custom_bbcode', 'where' => "bbcode_tag='{$this->request['bbcode_tag']}'" ) );
				
				if( $duplicate['bbcode_id'] )
				{
					$this->registry->output->global_message = $this->lang->words['tag_already'];
					$this->_bbcodeForm($type);
					return;
				}
				
				/* Check aliases */
				$this->DB->build( array( 'select' => '*',
										 'from'	  => 'custom_bbcode',
										 'where'  => 'bbcode_aliases LIKE \'%' . $this->DB->addSlashes( $this->request['bbcode_tag'] ) . '%\'' ) );
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					foreach( explode( ',', $row['bbcode_aliases'] ) as $alias )
					{
						if ( strtolower( $alias ) == strtolower( $this->request['bbcode_tag'] ) )
						{
							$this->registry->output->global_message = sprintf( $this->lang->words['tag_already_alias'], $this->request['bbcode_tag'], $row['bbcode_tag'] );
							$this->_bbcodeForm($type);
							return;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Fix BR tags
		//-----------------------------------------
		
		$this->request['bbcode_aliases']	= str_replace( array( ' ', "\n" ), '', IPSText::br2nl( $this->request['bbcode_aliases'] ) );
		
		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( !$this->request['bbcode_title'] or !$this->request['bbcode_tag'] or ( !$this->request['bbcode_replace'] AND !$this->request['bbcode_php_plugin'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['complete_form'];
			$this->_bbcodeForm($type);
			return;
		}
		
		if ( !$this->request['bbcode_single_tag'] AND !strstr( $this->request['bbcode_replace'], '{content}' ) AND !$this->request['bbcode_php_plugin'] )
		{
			$this->registry->output->global_message = $this->lang->words['must_use_content'];
			$this->_bbcodeForm($type);
			return;
		}
		
		if ( !strstr( $this->request['bbcode_replace'], '{option}' ) AND $this->request['bbcode_useoption'] AND !$this->request['bbcode_php_plugin'] )
		{
			$this->registry->output->global_message = $this->lang->words['must_use_option'];
			$this->_bbcodeForm($type);
			return;
		}
		
		if( preg_match( "/[^a-zA-Z0-9_]/", $this->request['bbcode_tag'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['bbcode_alpha_num'];
			$this->_bbcodeForm($type);
			return;
		}
		
		$_aliases	= explode( ",", $this->request['bbcode_aliases'] );
		
		foreach( $_aliases as $_alias )
		{
			if( preg_match( "/[^a-zA-Z0-9_]/", $_alias ) )
			{
				$this->registry->output->global_message = $this->lang->words['bbcode_alpha_num'];
				$this->_bbcodeForm($type);
				return;
			}
		}
		
		$array = array(
						'bbcode_title'				=> $this->request['bbcode_title'],
						'bbcode_desc'				=> IPSText::safeslashes( $_POST['bbcode_desc'] ),
						'bbcode_tag'				=> preg_replace( "/[^a-zA-Z0-9_]/", "", $this->request['bbcode_tag'] ),
						'bbcode_replace'			=> IPSText::safeslashes( $_POST['bbcode_replace'] ),
						'bbcode_example'			=> IPSText::safeslashes( $_POST['bbcode_example'] ),
						'bbcode_useoption'			=> $this->request['bbcode_useoption'],
						'bbcode_switch_option'		=> intval( $this->request['bbcode_switch_option'] ),
						'bbcode_menu_option_text'	=> trim( $this->request['bbcode_menu_option_text'] ),
						'bbcode_menu_content_text'	=> trim( $this->request['bbcode_menu_content_text'] ),
						'bbcode_single_tag'			=> intval( $this->request['bbcode_single_tag'] ),
						'bbcode_groups'				=> is_array( $this->request['bbcode_groups'] ) ? implode( ',', $this->request['bbcode_groups'] ) : '',
						'bbcode_sections'			=> is_array( $this->request['bbcode_sections'] ) ? implode( ',', $this->request['bbcode_sections'] ) : '',
						'bbcode_php_plugin'			=> trim( $this->request['bbcode_php_plugin'] ),
						'bbcode_no_parsing'			=> intval( $this->request['bbcode_no_parsing'] ),
						'bbcode_optional_option'	=> intval( $this->request['bbcode_optional_option'] ),
						'bbcode_aliases'			=> $this->request['bbcode_aliases'],
						'bbcode_image'				=> $this->request['bbcode_image'],
						'bbcode_app'				=> $this->request['bbcode_app'],
						'bbcode_protected'			=> intval( $this->request['bbcode_protected'] ),
						'bbcode_custom_regex'		=> str_replace( "&#092;", '\\', IPSText::stripslashes( $_POST['bbcode_custom_regex'] ) ),
						);

		if ( $type == 'add' )
		{
			$check	= $this->DB->buildAndFetch( array( 'select' => 'bbcode_tag', 'from' => 'custom_bbcode', 'where' => "bbcode_tag='{$array['bbcode_tag']}'" ) );
			
			if( $check['bbcode_tag'] )
			{
				$this->registry->output->global_message = $this->lang->words['must_use_unique_btag'];
				$this->_bbcodeForm($type);
				return;
			}
			
			$this->DB->insert( 'custom_bbcode', $array );
			$this->registry->output->global_message = $this->lang->words['new_bbcode'];
		}
		else
		{
			$check	= $this->DB->buildAndFetch( array( 'select' => 'bbcode_tag', 'from' => 'custom_bbcode', 'where' => "bbcode_tag='{$array['bbcode_tag']}' AND bbcode_id<>" . intval($this->request['id']) ) );
			
			if( $check['bbcode_tag'] )
			{
				$this->registry->output->global_message = $this->lang->words['must_use_unique_btag'];
				$this->_bbcodeForm($type);
				return;
			}
			
			if ( $this->request['drop_cache'] )
			{
				IPSContentCache::truncate();
			}
			
			$this->DB->update( 'custom_bbcode', $array, 'bbcode_id=' . intval($this->request['id']) );
			$this->registry->output->global_message = $this->lang->words['edited_bbcode'];
		}
		
		$this->bbcodeRebuildCache();
		
		$this->_bbcodeStart();
	}
	
	
	/**
	 * Add/Edit bbcode form
	 *
	 * @param	string		[add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeForm($type='add')
	{
		$this->registry->output->nav[]				= array( '', $this->lang->words['add_edit'] );
		
		if ( $type == 'edit' )
		{
			if ( ! $this->request['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_bbcode_found_edit'];
				$this->_bbcodeStart();
				return;
			}
			
			$bbcode = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'custom_bbcode', 'where' => 'bbcode_id=' . intval($this->request['id']) ) );
			
			#Issue 20376
			$bbcode['bbcode_desc']		= stripslashes( $bbcode['bbcode_desc'] );
			$bbcode['bbcode_replace']	= stripslashes( $bbcode['bbcode_replace'] );
			$bbcode['bbcode_example']	= stripslashes( $bbcode['bbcode_example'] );
			
			if( !$bbcode['bbcode_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_bbcode_found_edit'], 111163 );
			}
		}
		else
		{
			$bbcode = array();
		}
		
		//-----------------------------------------
		// Grab the 'sections'
		//-----------------------------------------
		
		$sections = array();
		
		foreach( ipsRegistry::$applications as $_app_dir => $app_data )
		{
			$_file	= IPSLib::getAppDir( $_app_dir ) . '/extensions/editorSections.php';
			
			if( is_file($_file) )
			{
				$BBCODE = array();
				
				include( $_file );/*maybeLibHook*/
				
				if( is_array($BBCODE) AND count($BBCODE) )
				{
					$sections = array_merge( $sections, $BBCODE );
				}
			}
		}
		
		$this->registry->output->html .= $this->html->bbcodeForm( $type, $bbcode, $sections );
	}
	
	/**
	 * Bbcode splash page
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _bbcodeStart()
	{
		//-----------------------------------------
		// Show the codes mahn!
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'custom_bbcode', 'order' => 'bbcode_title' ) );
		$this->DB->execute();
		
		$bbcode_rows = "";
		
		while ( $row = $this->DB->fetch() )
		{
			if ( $row['bbcode_useoption'] )
			{
				$option = '={option}';
			}
			else
			{
				$option = '';
			}
			
			if( $row['bbcode_single_tag'] )
			{
				$row['bbcode_fulltag'] = '['.$row['bbcode_tag'].$option.']';
			}
			else
			{
				$row['bbcode_fulltag'] = '['.$row['bbcode_tag'].$option.']{content}[/'.$row['bbcode_tag'].']';
			}
			
			$bbcode_rows .= $this->html->bbcodeRow( $row );
		}
		
		$this->registry->output->html .= $this->html->bbcodeWrapper( $bbcode_rows );
	}
	
	/**
	 * Rebuild bbcode cache
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function bbcodeRebuildCache()
	{
		$cache = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'custom_bbcode', 'order' => 'bbcode_title ASC' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$cache[ $r['bbcode_tag'] ] = $r;
		}
		
		$this->cache->setCache( 'bbcode', $cache, array( 'array' => 1 ) );
	}
}