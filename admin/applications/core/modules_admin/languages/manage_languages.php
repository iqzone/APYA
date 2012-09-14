<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Language Pack Management
 * Last Updated: $LastChangedDate: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10771 $
 */
 
if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_languages_manage_languages extends ipsCommand
{
	/**
	 * Daily flag
	 *
	 * @var		bool
	 */
	private $__daily	= false;

	/**
	 * Tab buttons
	 *
	 * @var		array
	 */
	public $tab_buttons     = array();

	/**
	 * Tabs
	 *
	 * @var		array
	 */
	public $tab_tabs        = array();

	/**
	 * Javascript action to execute on tab click
	 *
	 * @var		string
	 */
	public $tab_js_action   = '';

	/**
	 * Default tab
	 *
	 * @var		string
	 */
	public $default_tab     = '';
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Get skin and language file
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_system', 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_system' ), 'core' );
		
		//-----------------------------------------
		// Set urls
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=languages&amp;section=manage_languages&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=languages&section=manage_languages&';	

		//-----------------------------------------
		// Go
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'copy_lang_pack':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageCopy();
			break;
			
			case 'edit_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageWordEntryForm( 'edit' );
			break;
			
			case 'do_edit_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->handleWordEntryForm( 'edit' );
			break;
			
			case 'add_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageWordEntryForm( 'add' );
			break;
			
			case 'do_add_word_entry':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->handleWordEntryForm( 'add' );
			break;
			
			case 'list_word_packs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageListWordPacks();
			break;
			
			case 'edit_word_pack':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->languageEditWordPack();
			break;
			
			case 'do_edit_word_pack':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->languageEditWordPackValues();
			break;
			
			case 'edit_lang_info':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_pack_info' );
				$this->languageInformationForm( 'edit' );
			break;
			
			case 'do_edit_lang_info':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_pack_info' );
				$this->handleLanguageInformationForm( 'edit' );
			break;

			case 'new_language':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->languageInformationForm( 'new' );
			break;
			
			case 'do_new_language':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_packs' );
				$this->handleLanguageInformationForm( 'new' );
			break;
			
			case 'language_swap':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_pack_info' );
				$this->swapLanguages();
			break;
			
			case 'revert':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'lang_words' );
				$this->languageDoRevertWord();
			break;
			
			case 'export':
				$this->languageExportToXML( intval( $this->request['id'] ) );
			break;
			
			case 'language_do_import':
				$this->imprtFromXML( intval( $this->request['id'] ) );
			break;

			case 'language_do_indev_export':
				foreach( ipsRegistry::$applications as $app_dir => $app_data )
				{
					$this->request['app_dir']	= $app_dir;
					
					$this->request['type']	= 'admin';
					$this->languageExportToXML( 1, 1 );
					
					$this->request['type']	= 'public';
					$this->languageExportToXML( 1, 1 );
				}
				
				$this->registry->output->setMessage( $this->lang->words['indev_lang_export_done'], 1 );
				$this->languagesList();
			break;
			
			case 'language_do_indev_import':
				$this->importFromCacheFiles();
			break;
			
			case 'remove_language':
				$this->languageRemove();
			break;
			
			case 'remove_word_entry':
				$this->removeWordEntry();
			break;
			
			case 'remove_word_pack':
				$this->removeWordPack();
			break;
			
			case 'rebuildFromXml':
				$this->rebuildFromXml();
			break;
			
			case 'recache_lang_pack':
				$this->recacheLangPack();
			break;
			
			case 'translateExtSplash':
				$this->translateExtSplash();
			break;
			
			case 'translateImport':
				$this->translateImport();
			break;
			
			case 'translateKill':
				$this->translateKill();
			break;
			
			default:
				$this->languagesList();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Swap member language choice globally
	 *
	 * @return	@e void
	 */
	public function swapLanguages()
	{
		/* INIT */
		$langFrom		= intval( $this->request['lang_from'] );
		$langTo			= intval( $this->request['lang_to'] );
		
		/* Run query... */
		if( !$langFrom )
		{
			$this->DB->update( 'members', array( 'language' => $langTo > 0 ? $langTo : '' ), "language='' OR language " . $this->DB->buildIsNull( true ) );
		}
		else
		{
			$this->DB->update( 'members', array( 'language' => $langTo > 0 ? $langTo : '' ), "language='{$langFrom}'" );
		}

		/* Done */
		$this->registry->output->global_message = $this->lang->words['mem_lang_choice_swapped'];
		$this->languagesList();
		return;
	}

	/**
	 * Finish a session and remove data
	 *
	 * @return	@e void
	 */
	public function translateKill()
	{
		/* INIT */
		$langId        = intval( $this->request['id'] );
		$mainDir       = DOC_IPS_ROOT_PATH . 'translate';
		$words_by_file = array();
		$errors        = array();
		$filesToImport = array();
		
		/* Start top message */
		if ( ! is_dir( $mainDir ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_dir'];
			$this->languagesList();
			return;
		}
		
		if ( ! is_writeable( $mainDir ) )
		{
			/* Just bounce back asking them to chmod translate */
			$this->registry->output->global_error = $this->lang->words['ext_chmod_translate_dir'];
			$this->languagesList();
			return;
		}
		
		/* Load kernel class for file management */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$classFileManagement = new $classToLoad();
		
		/* Remove files */
		$classFileManagement->emptyDirectory( $mainDir );
		
		/* Delete session */
		$session = $this->DB->delete( 'cache_store', "cs_key='translate_session'" );
		
		/* Done */
		$this->registry->output->global_message = $this->lang->words['ext_all_killed'];
		$this->languagesList();
		return;
	}
		
	/**
	 * Translate externally import changed files
	 *
	 * @return	@e void
	 */
	public function translateImport()
	{
		/* INIT */
		$langId        = intval( $this->request['id'] );
		$mainDir       = DOC_IPS_ROOT_PATH . 'translate';
		$words_by_file = array();
		$errors        = array();
		$filesToImport = array();
		$fileCount     = 0;
		
		/* Start top message */
		if ( ! is_dir( $mainDir ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_dir'];
			$this->languagesList();
			return;
		}
		
		if ( ! is_writeable( $mainDir ) )
		{
			/* Just bounce back asking them to chmod translate */
			$this->registry->output->global_error = $this->lang->words['ext_chmod_translate_dir'];
			$this->languagesList();
			return;
		}

		/* Get file count */
		try
		{
			foreach( new DirectoryIterator( $mainDir ) as $f )
			{
				if ( !$f->isDir() )
				{
					$fileCount++;
				}
			}
		}
		catch ( Exception $e )
		{
			$fileCount	= 0;
		}
		
		/* Get lang */
		$lang = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => 'lang_id=' . $langId ) );
		
		/* Get current session if one */
		$sessionData = $this->cache->getCache('translate_session');
		
		/* Check */
		if ( empty( $sessionData['lang_id'] ) OR ! count( $sessionData['files'] ) OR !$fileCount )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_files'];
			$this->languagesList();
			return;
		}
		
		/* Still here? Okay */
		if ( is_array( $_POST['cb'] ) AND count( $_POST['cb'] ) )
		{
			/* Gather a list of files to import */
			foreach( $_POST['cb'] as $file => $value )
			{
				if ( $_POST['cb'][ $file ] )
				{
					$filesToImport[ $file ] = $file;
				}
			}
			
			/* Assume nothing */
			if ( count( $filesToImport ) )
			{
				$counts = $this->_importFromDisk( $mainDir, $sessionData['lang_id'], array_keys( $filesToImport ), true );
				
				foreach( $counts as $file => $data )
				{
					/* Update session data */
					$sessionData['files'][ $file ]['dbtime'] = time();
					
					$this->registry->output->global_message = sprintf( $this->lang->words['ext_file_written'], $file, intval( $data['inserts'] ), intval( $data['updates'] ) );
				}
				
				$this->registry->output->global_message .= '<br />' . sprintf( $this->lang->words['ext_recache'], "{$this->settings['base_url']}&{$this->form_code}&do=recache_lang_pack&id={$sessionData['lang_id']}" );
				
				/* Update session */
				$this->cache->setCache( 'translate_session', $sessionData, array( 'array' => 1, 'donow' => 1 ) );
				
				$this->translateExtSplash();
				return;
			}
		}
		else
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_selected_files'];
			$this->translateExtSplash();
			return;
		}
	}
	
	/**
	 * Translate externally splash
	 *
	 * @return	@e void
	 */
	public function translateExtSplash()
	{
		/* INIT */
		$langId        = intval( $this->request['id'] );
		$mainDir       = DOC_IPS_ROOT_PATH . 'translate';
		$words_by_file = array();
		$errors        = array();
		
		/* Start top message */
		if ( ! is_dir( $mainDir ) )
		{
			/* Just bounce back asking them to create translate */
			$this->registry->output->global_error = $this->lang->words['ext_no_translate_dir'];
			$this->languagesList();
			return;
		}
		
		if ( ! is_writeable( $mainDir ) )
		{
			/* Just bounce back asking them to chmod translate */
			$this->registry->output->global_error = $this->lang->words['ext_chmod_translate_dir'];
			$this->languagesList();
			return;
		}
		
		/* Load kernel class for file management */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$classFileManagement = new $classToLoad();
		
		/* Get lang */
		$lang = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => 'lang_id=' . $langId ) );
		
		/* Get current session if one */
		$session      = $this->DB->buildandFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='translate_session'" ) );
		$sessionData = ( strstr( $session['cs_value'], 'a:' ) ) ? unserialize( $session['cs_value'] ) : array();
		
		/* No current session? */
		if ( empty( $sessionData['lang_id'] ) OR ! count( $sessionData['files'] ) )
		{
			/* Ensure directory is empty */
			$classFileManagement->emptyDirectory( $mainDir );
			
			/* Ensure it's gone, gone */
			$this->DB->delete( 'cache_store', "cs_key='translate_session'" );
			$sessionData          = $lang;
			$sessionData['files'] = array();
			$header               = "/*******************************************************\nNOTE: This is a translatable file generated by IP.Board " . IPB_VERSION . " (" . IPB_LONG_VERSION . ") on " . date( "r" ) . " by " . $this->memberData['members_display_name'] . "\nPLEASE set your text editor to save this document as UTF-8 regardless of your board's character-set\n*******************************************************/\n\n";
		
			/* Export all the languages into flat files */
			$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => 'lang_id=' . $langId, 'order' => 'word_custom_version DESC, word_default_version DESC' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$_text    = ( $r['word_custom'] ) ? $r['word_custom'] : $r['word_default'];
				$_version = ( $r['word_custom_version'] ) ? $r['word_custom_version'] : $r['word_default_version'];
				$words_by_file[$r['word_app']][$r['word_pack']][] = array( $r['word_key'], $_text );
			}
			
			//-----------------------------------------
			// Now loop and write to file
			//-----------------------------------------
			
			foreach( $words_by_file as $app => $word_packs )
			{			
				foreach( $word_packs as $pack => $words )
				{	
					if( $pack == 'public_js' )
					{
						$to_write	= '';
						$_file		= 'ipb.lang.js';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
						}					
					}
					else if( $pack == 'admin_js' )
					{
						$to_write	= '';
						$_file		= 'acp.lang.js';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
						}
					}
					else
					{
						//-----------------------------------------
						// Build cache file contents
						//-----------------------------------------
						
						$to_write	= "<?php\n\n$header\n\n\$lang = array( \n";
						$_file		= $app . '_' . $pack . '.php';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "'{$word[0]}'\t\t\t\t=> \"{$word[1]}\",\n";
						}
	
						$to_write .= " ); \n";					
					}
					
					//-----------------------------------------
					// Convert data
					//-----------------------------------------
					
					$to_write = IPSText::convertCharsets( $to_write, IPS_DOC_CHAR_SET, 'UTF-8' );
					
					//-----------------------------------------
					// Write the file
					//-----------------------------------------
					
					@unlink( $mainDir . '/' . $_file );
					
					if ( $fh = @fopen( $mainDir . '/' . $_file, 'wb' ) )
					{
						fwrite( $fh, $to_write, strlen( $to_write ) );
						fclose( $fh );
						@chmod( $mainDir . '/' . $_file, IPS_FILE_PERMISSION );
						
						$mtime = @filemtime( $mainDir . '/' . $_file );
						
						$sessionData['files'][ $_file ] = array( 'mtime' => $mtime, 'dbtime' => $mtime );
					}
					else
					{
						$errors[] = $this->lang->words['l_nowrite'] .  $mainDir . '/' . $_file;
					}
				}
			}
			
			/* Sort files */
			ksort( $sessionData['files'] );
			
			/* Save session */
			$this->DB->insert( 'cache_store', array( 'cs_key'     => 'translate_session',
													   'cs_value'   => serialize( $sessionData ),
													   'cs_array'   => 1,
													   'cs_updated' => time() ) );
		
		}
		else
		{
			/* Update mtime */
			foreach( $sessionData['files'] as $file => $data )
			{
				$sessionData['files'][ $file ]['mtime'] =  @filemtime( $mainDir . '/' . $file );
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}&{$this->form_code}&do=translateExtSplash&id={$sessionData['lang_id']}", "{$this->lang->words['ext_title_for']} {$sessionData['lang_title']}" );
		
		$this->registry->output->html .= $this->html->languages_translateExt( $sessionData, $lang );
	}
	
	/**
	 * Import files from disk
	 *
	 * @param	string		Directory to look in
	 * @param	int			Language ID to import into
	 * @param	array 		[Only import these files]
	 * @param	boolean		[Perform character set translation from UTF-8]
	 * @return	array		List of files successfully imported
	 */
	 private function _importFromDisk( $mainDir, $langId, $onlyTheseFiles=array(), $convertCharSet=false )
	 {
	 	/* INIT */
	 	$imported		= array();
	    $lang_entries	= array();
	    $js_entries		= array();
	 	$counts			= array();
	 	
		/* Start looping */
	 	if ( is_dir( $mainDir ) )
		{
			$dh = opendir( $mainDir );
						
			/* Ensure it has a trailing slash */
			if ( substr( $mainDir, -1 ) != '/' )
			{
				$mainDir .= '/';
			}
			
			while( $f = readdir( $dh ) )
			{
				if ( $f[0] == '.' || $f == 'index.html' )
				{
					continue;
				}
				
				/* Skipping? */
				if ( is_array( $onlyTheseFiles ) AND count( $onlyTheseFiles ) AND ! in_array( $f, $onlyTheseFiles ) )
				{
					continue;
				}
										
				if ( preg_match( '#^\S+?_\S+?\.php$#', $f ) )
				{
					//-----------------------------------------
					// INIT
					//-----------------------------------------
					
					$updated	= 0;
					$inserted	= 0;
					$app		= preg_replace( '#^([^_]+?)_(\S+?)\.php$#', "\\1", $f );
					$word_pack	= preg_replace( '#^([^_]+?)_(\S+?)\.php$#', "\\2", $f );
					$lang		= array();
					$db_lang	= array();
					
					$counts[ $f ] = array();
					
					if ( ! is_file( $mainDir . $f ) )
					{
						continue;
					}
					
					/* Require the file */
					require( $mainDir . $f );/*noLibHook*/
					
					//-----------------------------------------
					// Loop
					//-----------------------------------------

					foreach( $lang as $k => $v )
					{
						//-----------------------------------------
						// Build db array
						//-----------------------------------------
						
						$db_array = array(
											'lang_id'				=> $langId,
											'word_app'				=> $app,
											'word_pack'				=> $word_pack,
											'word_key'				=> $k,
											'word_custom'			=> IPSText::convertCharsets($v, 'UTF-8', IPS_DOC_CHAR_SET ),
											'word_js'				=> 0
										);	
		
						//-----------------------------------------
						// If cached, get from cache
						//-----------------------------------------
						
						if( $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ] )
						{
							$lang_entry	= $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ];
						}
						
						//-----------------------------------------
						// Otherwise get all langs from this entry and
						// put in cache
						//-----------------------------------------
						
						else
						{
							$this->DB->build( array(
													'select'	=> '*',
													'from'		=> 'core_sys_lang_words',
													'where'		=> "lang_id={$langId} AND word_app='{$db_array['word_app']}' AND word_pack='{$db_array['word_pack']}'"
												)		);
							$this->DB->execute();
							
							while( $r = $this->DB->fetch() )
							{
								$lang_entries[ $r['lang_id'] ][ $r['word_app'] ][ $r['word_pack'] ][ $r['word_key'] ]	= $r;
							}
							
							if( $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ] )
							{
								$lang_entry	= $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ];
							}
						}
						
						/* Finish off */
						$db_array['word_default']         = $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ]['word_default'];
						$db_array['word_default_version'] = $lang_entries[ $langId ][ $db_array['word_app'] ][ $db_array['word_pack'] ][ $db_array['word_key'] ]['word_default_version'];
						$db_array['word_custom_version']  = IPB_LONG_VERSION;
						
						//-----------------------------------------
						// If there is no new custom lang bit to insert
						// don't delete what is already there.
						//-----------------------------------------
						
						if( ! $db_array['word_custom'] )
						{
							unset($db_array['word_custom']);
						}
		
						//-----------------------------------------
						// Lang bit already exists, update
						//-----------------------------------------
						
						if( $lang_entry['word_id'] )
						{
							//-----------------------------------------
							// Don't update default version
							//-----------------------------------------
							
							unset( $db_array['word_default_version'] );
							
							$counts[ $f ]['updates']++;
							$this->DB->update( 'core_sys_lang_words', $db_array, "word_id={$lang_entry['word_id']}" );
						}
						
						//-----------------------------------------
						// Lang bit doesn't exist, so insert
						//-----------------------------------------
						
						else if( !$lang_entry['word_id'] )
						{
							$counts[ $f ]['inserts']++;
							$this->DB->insert( 'core_sys_lang_words', $db_array );
						}
					}
				}
				else if( preg_match( '/(\.js)$/', $f ) )
				{
					$_js_word_pack	= '';
					
					if( $f == 'ipb.lang.js' )
					{
						$_js_word_pack	= 'public_js';
					}
					else if( $f == 'acp.lang.js' )
					{
						$_js_word_pack	= 'admin_js';
					}
					
					//-----------------------------------------
					// If not cached, get it
					//-----------------------------------------
					
					if( !$js_entries[ $langId ][ $_js_word_pack ] )
					{
						$this->DB->build( array(
												'select'	=> '*',
												'from'		=> 'core_sys_lang_words',
												'where'		=> "lang_id={$langId} AND word_pack='{$_js_word_pack}'"
											)		);
						$this->DB->execute();
						
						while( $r = $this->DB->fetch() )
						{
							$js_entries[ $r['word_pack'] ][ $r['word_key'] ]	= $r;
						}
					}
						
					//-----------------------------------------
					// Delete current words for this app and word pack
					//-----------------------------------------
					
					$this->DB->delete( 'core_sys_lang_words', 'lang_id=' . $langId . " AND word_app='core' AND word_pack='" . $_js_word_pack . "'" );
					
					//-----------------------------------------
					// Get each line
					//-----------------------------------------
					
					$js_file = file( $mainDir . $f );
					
					//-----------------------------------------
					// Loop through lines and import
					//-----------------------------------------
					
					foreach( $js_file as $r )
					{
						//-----------------------------------------
						// preg_match what we want
						//-----------------------------------------
						
						preg_match( '#ipb\.lang\[\'(.+?)\'\](.+?)= ["\'](.+?)["\'];#', $r, $matches );

						//-----------------------------------------
						// Valid?
						//-----------------------------------------
						
						if( $matches[1] && $matches[3] )
						{
							$counts[ $f ]['inserts']++;
							$insert = array(
												'lang_id'				=> $langId,
												'word_app'				=> 'core',
												'word_pack'				=> $_js_word_pack,
												'word_key'				=> $matches[1],
												'word_custom'			=> IPSText::convertCharsets($matches[3], 'UTF-8', IPS_DOC_CHAR_SET ),
												'word_js'				=> 1,
												'word_default'			=> $js_entries[ $_js_word_pack ][ $matches[1] ]['word_default'],
												'word_default_version'	=> $js_entries[ $_js_word_pack ][ $matches[1] ]['word_default_version'],
												'word_custom_version'	=> IPB_LONG_VERSION,
											);

							$this->DB->insert( 'core_sys_lang_words', $insert );
						}
					}
				}
			}

			closedir( $dh );
		}
		
		return $counts;
	 }


	/**
	 * Recaches a language pack
	 *
	 * @return	@e void
	 */
	public function recacheLangPack()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->cache_errors = array();
		
		$lang_id = intval( $this->request['id'] );
		
		$this->cacheToDisk( $lang_id );
		
		$this->registry->output->global_message = $this->lang->words['language_recache_done'] . ( !empty( $this->cache_errors ) ? ( "<br />" . implode( "<br />", $this->cache_errors ) ) : '' );
		$this->languagesList();
	}
	
	/**
	 * Remove a word entry
	 *
	 * @return	@e void
	 */
	public function removeWordEntry()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$word_id	= intval($this->request['word_id']);
		$lang_id	= intval( $this->request['id'] );
		
		//-----------------------------------------
		// Delete lang bit
		//-----------------------------------------
		
		$this->DB->delete( 'core_sys_lang_words', 'word_id=' . $word_id );
		
		//-----------------------------------------
		// Recache to disk
		//-----------------------------------------
		
		$this->cacheToDisk( $lang_id );
		
		//-----------------------------------------
		// Bounce to new URL
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->registry->adminFunctions->generated_acp_hash;
		$this->registry->output->global_message = $this->lang->words['language_word_removed'];
		$this->languageEditWordPack();
	}
	
	/**
	 * Remove a word pack
	 *
	 * @return	@e void
	 */
	public function removeWordPack()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$pack		= explode( '/', $this->request['word_pack'] );
		$lang_id	= intval( $this->request['id'] );
		
		if( $lang_id )
		{
			//-----------------------------------------
			// Delete from DB
			//-----------------------------------------
			
			$this->DB->delete( 'core_sys_lang_words', "lang_id='{$lang_id}' AND word_pack='" . $pack[1] . "' AND word_app='" . $pack[0] . "'" );
			
			//-----------------------------------------
			// Delete from disk
			//-----------------------------------------
			
			$_file	= IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/' . $pack[0] . '_' . $pack[1] . '.php';
			
			if( is_file( $_file ) )
			{
				@unlink( $_file );
			}
			
			//-----------------------------------------
			// And recache
			//-----------------------------------------
			
			$this->cacheToDisk( $lang_id );
		}
		
		//-----------------------------------------
		// Bounce back
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->registry->adminFunctions->generated_acp_hash;
		$this->registry->output->global_message = $this->lang->words['language_wordpack_removed'];
		$this->languageListWordPacks();
	}
	
	/**
	 * Rebuilds language from XML files
	 *
	 * @return @e void
	 */
	public function rebuildFromXml()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$apps		= array();
		$previous	= trim( $this->request['previous'] );
		$type		= trim( $this->request['type'] );
		$id			= intval($this->request['id']);
		$_word		= ( $type == 'admin' ) ? 'admin' : 'public';
		
		//-----------------------------------------
		// Verify writable
		//-----------------------------------------
		
		if ( ! is_writeable( IPS_CACHE_PATH . 'cache/lang_cache/' . $id ) )
		{
			$this->registry->output->global_error = $this->lang->words['cannot_write_to_cache'] ? sprintf( $this->lang->words['cannot_write_to_cache'], $id ) : "Cannot write to cache/lang_cache/" . $id;
			$this->languagesList();
			return;
		}
				
		//-----------------------------------------
		// Get setup class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . "setup/sources/base/setup.php" );/*noLibHook*/
		
		//-----------------------------------------
		// Get apps
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $appDir => $appData )
		{
			$apps[] = $appDir;
		}
		
		//-----------------------------------------
		// Klude for setup class
		//-----------------------------------------
		
		IPSSetUp::setSavedData( 'install_apps', implode( ',', $apps ) );
		
		//-----------------------------------------
		// Get next app
		//-----------------------------------------
		
		$next = IPSSetUp::fetchNextApplication( $previous, '', $this->settings['gb_char_set'] );
	
		if ( $next['key'] )
		{
			$msg	= $next['title'] . sprintf( $this->lang->words['importing_x_langs'], $_word );
			$_PATH  = IPSLib::getAppDir( $next['key'] ) .  '/xml/';
		
			//-----------------------------------------
			// Try to import all the lang packs
			//-----------------------------------------
			
			try
			{
				foreach( new DirectoryIterator( $_PATH ) as $f )
				{
					if ( preg_match( "#" . $_word . "_(.+?)_language_pack.xml#", $f->getFileName() ) )
					{
						$this->request['file_location'] = $_PATH . $f->getFileName();
						$this->imprtFromXML( 1, true, true, $next['key'] );
					}
				}
			} catch ( Exception $e ) {}

			//-----------------------------------------
			// Off to next setp
			//-----------------------------------------
			
			$this->registry->output->html	.= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $this->form_code . "do=rebuildFromXml&amp;id={$id}&amp;type={$type}&amp;previous=" . $next['key'], $msg . '<br>' . $this->registry->output->global_message );
			$this->registry->output->global_message	= '';
			return;
		}
		else
		{
			if ( $type == 'public' )
			{
				//-----------------------------------------
				// Onto admin languages
				//-----------------------------------------

				$this->registry->output->html	.= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $this->form_code . "do=rebuildFromXml&amp;id={$id}&amp;type=admin", $this->lang->words['starting_admin_import'] );
				return;
			}
			else
			{
				//-----------------------------------------
				// And we're done
				//-----------------------------------------

				$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code, $this->lang->words['lang_reimport_done'] );
			}
		}
	}
	
	/**
	 * Copies a language pack
	 *
	 * @return void
	 */
	public function languageCopy()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Get lang pack
		//-----------------------------------------
		
		$lang_info = $this->DB->buildAndFetch( array( 'select' => 'lang_short, lang_title, lang_isrtl', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );
		
		$lang_info['lang_title'] .= " (COPY)";
		
		//-----------------------------------------
		// Insert language pack
		//-----------------------------------------
		
		$this->DB->insert( 'core_sys_lang', $lang_info );
		$new_id	= $this->DB->getInsertID();
		
		//-----------------------------------------
		// Copy the language bits now
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$id}" ) );
		$q = $this->DB->execute();

		while( $r = $this->DB->fetch( $q ) )
		{
			unset( $r['word_id'] );
			$r['lang_id'] = $new_id;
			
			$this->DB->insert( 'core_sys_lang_words', $r );
		}

		//-----------------------------------------
		// Recache and redirect
		//-----------------------------------------
		
		$this->cacheToDisk( $new_id );
		$this->registry->class_localization->rebuildLanguagesCache();
		
		$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code, $this->lang->words['l_copied'] );
	}
	
	/**
	 * Removes a language pack and cleans up files
	 *
	 * @return void
	 */
	public function languageRemove()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );

		//-----------------------------------------
		// Make sure this isn't default pack
		//-----------------------------------------
		
		$langCheck = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => 'lang_id=' . $id ) );
		
		if( $langCheck['lang_default'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_delete_default_lang'] );
		}
		
		if( $langCheck['lang_protected'] AND !IN_DEV )
		{
			$this->registry->output->showError( $this->lang->words['cannot_delete_protected_lang'] );
		}

		//-----------------------------------------
		// Delete from database
		//-----------------------------------------
		
		$this->DB->delete( 'core_sys_lang'      , "lang_id={$id}" );
		$this->DB->delete( 'core_sys_lang_words', "lang_id={$id}" );
		
		//-----------------------------------------
		// Delete from disk
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
		$fileManagement	= new $classToLoad();
		$fileManagement->removeDirectory( IPS_CACHE_PATH . 'cache/lang_cache/' . $id . '/' );
		
		//-----------------------------------------
		// Update member default choice
		//-----------------------------------------
		$default = $this->DB->buildAndFetch( array( 'select' => 'lang_id', 'from' => 'core_sys_lang', 'where' => "lang_default=1" ) );
		$this->DB->update( 'members', array( 'language' => intval($default['lang_id']) ), "language={$id}" );
		
		//-----------------------------------------
		// Update cache and redirect
		//-----------------------------------------
		
		$this->registry->class_localization->rebuildLanguagesCache();
		$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code, $this->lang->words['l_removed'] );
	}
	
	/**
	 * Revert a word pack entry to the default value
	 *
	 * @return	@e void
	 */
	public function languageDoRevertWord()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$word_id	= intval( $this->request['word_id'] );
		$lang_id	= intval( $this->request['id'] );
		$pack		= explode( '/', $this->request['word_pack'] );		
		
		//-----------------------------------------
		// Revert
		//-----------------------------------------
		
		$this->DB->update( 'core_sys_lang_words', array( 'word_custom' => '' ), "word_id={$word_id}" );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->cacheToDisk( $lang_id );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$this->request['secure_key'] = $this->registry->adminFunctions->generated_acp_hash;
		$this->registry->output->global_message = $this->lang->words['language_word_revert'];
		$this->languageEditWordPack();
	}	
	
	/**
	 * Saves new language edits
	 *
	 * @return	@e void
	 */	
	public function languageEditWordPackValues()
	{			
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );
		
		$langCheck = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => 'lang_id=' . $id ) );

		if( $langCheck['lang_protected'] AND !IN_DEV )
		{
			$this->registry->output->showError( $this->lang->words['lang_pack_protected'] );
		}

		//-----------------------------------------
		// Loop through language bits submitted
		//-----------------------------------------
		
		if( is_array( $_POST['lang'] ) && count( $_POST['lang'] ) )
		{
			foreach( $_POST['lang'] as $k => $v )
			{
				if( $v )
				{
					$v	= IPSText::safeSlashes($v);
					$v	= str_replace( '&#092;', '\\', $v );
					
					$this->DB->update( 'core_sys_lang_words', "word_custom='" . $this->DB->addSlashes( $v ) . "', word_custom_version=word_default_version", "word_id=" . intval($k), false, true );
				}
			}
		}
		
		//-----------------------------------------
		// Recache and redirect
		//-----------------------------------------
		
		$this->cacheToDisk( $id );

		$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code . "&amp;do=edit_word_pack&amp;word_pack={$this->request['pack']}&amp;id={$this->request['id']}&amp;search={$this->request['search']}&amp;filter={$this->request['filter']}&amp;st={$this->request['st']}", $this->lang->words['language_word_pack_edited'] );
	}
		
	/**
	 * Handles the word entry form
	 *
	 * @param	string	$mode	Either add or edit
	 * @return	@e void
	 */
	public function handleWordEntryForm( $mode='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$lang_id		= intval( $this->request['id'] );	
		$word_id		= intval( $this->request['word_id'] );	
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		if( ! $this->request['word_pack_db'] )
		{
			$this->registry->output->global_error	= $this->lang->words['l_packreq'];
			$this->languageWordEntryForm( $mode );
			return;
		}
		
		if( ! $this->request['word_key'] )
		{
			$this->registry->output->global_error	= $this->lang->words['l_keyreq'];
			$this->languageWordEntryForm( $mode );
			return;
		}
		
		if( ! $this->request['word_default'] )
		{
			$this->registry->output->global_error	= $this->lang->words['l_textreq'];
			$this->languageWordEntryForm( $mode );
			return;
		}
		
		$this->request['word_app']		= strtolower($this->request['word_app']);
		$this->request['word_pack_db']	= str_replace( '/', '_', strtolower($this->request['word_pack_db']) );
		
		if( $this->request['word_pack_db'] == 'admin_js' OR $this->request['word_pack_db'] == 'public_js' )
		{
			$this->request['word_app']	= 'core';
		}
		
		//-----------------------------------------
		// Build DB insert array
		//-----------------------------------------
		
		$db_array = array(
							'lang_id'             => $lang_id,
							'word_app'            => $this->request['word_app'],
							'word_pack'           => $this->request['word_pack_db'],
							'word_key'            => $this->request['word_key'],
							'word_default'        => IPSText::formToText( $_POST['word_default'] ),
						);

		//-----------------------------------------
		// Add or update
		//-----------------------------------------
		
		if( $mode == 'add' )
		{
			/* Check for a duplicate */
			$_check	= $this->DB->buildAndFetch( array( 'select' => 'word_id', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$lang_id} AND word_app='{$db_array['word_app']}' AND word_pack='{$db_array['word_pack']}' AND word_key='{$db_array['word_key']}'" ) );
			
			if( $_check['word_id'] )
			{
				$this->registry->output->global_error = $this->lang->words['l_textexistsalready'];
				$this->languageWordEntryForm( $mode );
				return;
			}
			
			$this->DB->insert( 'core_sys_lang_words', $db_array );
			
			$text	= $this->lang->words['l_added'];
		}
		else 
		{
			$this->DB->update( 'core_sys_lang_words', $db_array, "word_id={$word_id}" );
			
			$text	= $this->lang->words['l_updated'];
		}
		
		//-----------------------------------------
		// Recache and redirect
		//-----------------------------------------
		
		$this->cacheToDisk( $lang_id );
		
		$this->registry->output->redirect( "{$this->settings['base_url']}&module=languages&section=manage_languages&do=list_word_packs&id={$lang_id}", $text );
	}	
	
	/**
	 * Form for adding/editing a word entry
	 *
	 * @param	string	$mode	Either add or edit
	 * @return	@e void
	 */	
	public function languageWordEntryForm( $mode='add' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$word_id	= intval( $this->request['word_id'] );
		$lang_id	= intval( $this->request['id'] );
		$pack		= explode( '/', $this->request['word_pack'] );

		//-----------------------------------------
		// Adding or editing
		//-----------------------------------------
		
		if( $mode == 'add' )
		{
			$op     = 'do_add_word_entry';
			$title  = $this->lang->words['l_addnew'];
			$header = $this->lang->words['l_addnewfull'];
			$button = $this->lang->words['l_addthis'];			
			$data   = array( 'word_pack' => $pack[1], 'word_app' => $pack[0] );
		}
		else 
		{
			$op     = 'do_edit_word_entry';
			$title  = $this->lang->words['l_edit'];
			$header = $this->lang->words['l_editentry'];
			$button = $this->lang->words['l_savechanges'];
			
			//-----------------------------------------
			// Get data
			//-----------------------------------------
			
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "word_id={$word_id}" ) );
		}

		//-----------------------------------------
		// Set defaults
		//-----------------------------------------
		
		$data['word_app']		= !empty( $this->request['word_app'] )		? $this->request['word_app']		: $data['word_app'];
		$data['word_pack']		= !empty( $this->request['word_pack_db'] )	? $this->request['word_pack_db']	: $data['word_pack'];
		$data['word_key']		= !empty( $this->request['word_key'] )		? $this->request['word_key']		: $data['word_key'];
		$data['word_default']	= !empty( $this->request['word_default'] )	? $this->request['word_default']	: $data['word_default'];

		//-----------------------------------------
		// Applications dropdown
		//-----------------------------------------
		
		$_apps = array();
		
		foreach( ipsRegistry::$applications as $app => $appdata )
		{
			$_apps[] = array( $app, $appdata['app_title'] );
		}
		
		$data['word_app'] = $this->compileSelectOptions( $_apps, $data['word_app'] );
		
		//-----------------------------------------
		// Output form
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->languageWordEntryForm( $op, $word_id, $lang_id, $title, $header, $data, $button );
	}
	
	/**
 	 * Creates an option list for select tags
	 *
	 * @param	array	$options		Array of values/text for dropdown
	 * @param	mixed	[$selected]		Selected value or array of selected values
	 * @return	string
	 */
	public function compileSelectOptions( $options, $selected='' )
	{
		if( ! is_array( $selected ) )
		{
			$selected = array( $selected );
		}

		$html = '';
		if( is_array( $options ) )
		{
			foreach( $options as $option )
			{
				$sel = ( $option[0] AND in_array( $option[0], $selected ) ) ? ' selected="selected"' : '';
				$val = ( $option[0] == 'disabled'  ) ? 'disabled="disabled"' : "value='{$option[0]}'";

				$html .= "<option {$val}{$sel}>{$option[1]}</option>";
			}
		}

		return $html;
	}
	
	/**
 	 * Adds the button and content to the tab list for this object
	 *
	 * @param	string	$button
	 * @param	string	$content
	 * @param	string	[$js_action]
	 * @return	@e void
	 */
	public function addTab( $button, $content, $js_action='', $default_tab=0 )
	{
		$this->tab_buttons[]   = $button;
		$this->tab_tabs[]      = $content;
		$this->tab_js_action[] = $js_action;

		if( $default_tab )
		{
			$this->default_tab = count( $this->tab_tabs ) - 1;
		}
	}
	
	/**
 	 * Builds the html for the tabbed area
	 *
	 * @return	string		Tab HTML
	 */
	public function buildTabs()
	{
		/* Buttons */
		$i = 0;
		$tabs = array();
		foreach( $this->tab_buttons as $i => $button )
		{
			/* Tab Button */
			$js    = ( $this->tab_js_action[$i] ) ? 'onmousedown="'.$this->tab_js_action[$i].'"' : '';
			$tabs[] = array( 'id' => $i, 'text' => $button, 'js' => $js );
		}

		/* Tab Contents */
		$i = 0;
		$content = array();
		foreach( $this->tab_tabs as $tab )
		{
			/* Create the pane */
			$content[] = array( 'id' => $i, 'content' => $tab );
			$i++;
		}

		$default_tab = ( $this->default_tab ) ? $this->default_tab : '';

		/* End of tab pane */
		$tabs = $this->html->ui_content_tabs( $tabs, $content, $default_tab );

		return $tabs;
	}

	/**
	 * Edit the entries in a word pack
	 *
	 * @return	@e void
	 */
	public function languageEditWordPack()
	{	
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		/* Fix up for search */
		$keywordForPageLinks	 = $this->request['search'];
		$this->request['search'] = str_replace( "&#39;", "\\'", $this->request['search'] );

		$id			= intval( $this->request['id'] );
		$pack		= explode( '/', $this->request['word_pack'] );
		$per_page	= 20;
		$st			= $this->request['st']     ? intval( $this->request['st'] ) : 0;
		$search		= $this->request['search'] ? " AND ( word_default LIKE '%" . $this->request['search'] . "%' OR word_custom LIKE '%" . $this->request['search'] . "%' OR word_key LIKE '%" . $this->request['search'] . "%' )" : '';
		$filter		= $this->request['filter'] ? " AND word_custom_version < word_default_version AND word_custom " . $this->DB->buildIsNull(false) . ' ' : '';
		$wp_query	= $pack[0] && $pack[1]     ? " AND word_app='{$pack[0]}' AND word_pack='{$pack[1]}' " : '';
		
		//-----------------------------------------
		// Get language pack
		//-----------------------------------------
		
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );
				
		//-----------------------------------------
		// How many words?
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 
												'select' => 'COUNT(*) as count',
												'from'   => 'core_sys_lang_words',
												'where'  => "lang_id={$id} {$wp_query} {$search} {$filter}"
										)	 );

		//-----------------------------------------
		// Pagination
		//-----------------------------------------
		
		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'        => intval( $count['count'] ),
																	'itemsPerPage'      => $per_page,
																	'currentStartValue' => $st,
																	'baseUrl'           => "{$this->settings['base_url']}{$this->form_code}&do=edit_word_pack&word_pack=".implode( '/', $pack )."&id={$id}&search={$keywordForPageLinks}&filter={$this->request['filter']}",
												 			)      );

		//-----------------------------------------
		// Get the words
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select'	=> '*', 
								'from'		=> 'core_sys_lang_words', 
								'where'		=> "lang_id={$id} {$wp_query} {$search} {$filter}",
								'limit'		=> array( $st, $per_page ),
								'order'		=> 'word_key ASC'
						)	);
		$this->DB->execute();
		
		$lang = array();
		
		while( $r = $this->DB->fetch() )
		{
			$lang[] = array(
								'id'      => $r['word_id'],
								'default' => nl2br( htmlspecialchars( $r['word_default'], ENT_QUOTES ) ),
								'custom'  => htmlspecialchars( $r['word_custom'], ENT_QUOTES ),
								'pack'    => $r['word_app'] . '/' . $r['word_pack'],
								'key'	  => $r['word_key']
							);
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&do=list_word_packs&id={$id}", $data['lang_title'] );
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['language_word_pack_edit'] );
		$this->registry->output->html .= $this->html->languageWordPackEdit( $id, $lang, $pages );
	}	
	
	/**
	 * List the word packs available for the selected language set
	 *
	 * @return	@e void
	 * @author	Josh
	 */
	public function languageListWordPacks()
	{		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );

		//-----------------------------------------
		// Get language pack
		//-----------------------------------------
		
		$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );

		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=list_word_packs&id={$data['lang_id']}", $data['lang_title'] );
		
		//-----------------------------------------
		// Get words
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$id}" ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Some init before looping
		//-----------------------------------------
		
		$_packs		= array();
		$_missing	= array();
		$_stats		= array();

		while( $r = $this->DB->fetch() )
		{	
			//-----------------------------------------
			// Create our array
			//-----------------------------------------
			
			$_stats[$r['word_app']]								= isset( $_stats[$r['word_app']] ) ? $_stats[$r['word_app']] : array();
			$_stats[$r['word_app']][$r['word_pack']]			= isset( $_stats[$r['word_app']][$r['word_pack']] ) ? $_stats[$r['word_app']][$r['word_pack']] : array();
			$_stats[$r['word_app']][$r['word_pack']]['total']	= isset( $_stats[$r['word_app']][$r['word_pack']]['total'] ) ? $_stats[$r['word_app']][$r['word_pack']]['total'] : 0;

			if( ! isset( $_packs[$r['word_app']] ) )
			{
				$_packs[$r['word_app']] = array();	
			}
			
			//-----------------------------------------
			// Add this language pack to array
			//-----------------------------------------
			
			if( ! in_array( $r['word_pack'], $_packs[$r['word_app']] ) )
			{
				$_packs[$r['word_app']][] = $r['word_pack'];
			}
			
			//-----------------------------------------
			// Update stats
			//-----------------------------------------
			
			$_stats[$r['word_app']][$r['word_pack']]['total']++;

			if( $r['word_custom'] )
			{
				$_stats[$r['word_app']][$r['word_pack']]['custom']++;
				
				if( $r['word_custom_version'] < $r['word_default_version'] )
				{
					$_stats[$r['word_app']][$r['word_pack']]['outofdate']++;
				}				
			}
		}		
		
		//-----------------------------------------
		// Loop through applications
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app => $data )
		{
			//-----------------------------------------
			// Check if app has langs
			//-----------------------------------------
			
			if( isset( $_packs[$app] ) && count( $_packs[$app] ) )
			{
				$default_tab = ( $app == $this->request['app'] ) ? 1 : 0;
				asort($_packs[$app]);
				
				$this->addTab( $data['app_title'], $this->html->languageAppPackList( $app, $_packs[$app], $_stats[$app] ), '', $default_tab );
			}
		}
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->languageWordPackList( $id, $this->buildTabs(), $data['app_title'] );
	}	
	
	/**
	 * Handles the language information form submit
	 *
	 * @param	string	$mode	Either new or edit
	 * @return	@e void
	 */
	public function handleLanguageInformationForm( $mode='new' )
	{
		//-----------------------------------------
		// Error checking
		//-----------------------------------------
		
		$errors = array();
		
		if( ! $this->request['lang_title'] )
		{
			$errors[] = '<li>' . $this->lang->words['language_title_missing'] . '</li>';
		}
		
		if( ! $this->request['lang_short'] )
		{
			$errors[] = '<li>' . $this->lang->words['language_locale_missing'] . '</li>';
		}
		
		if( count( $errors ) )
		{
			$this->registry->output->global_message = '<ul>'. implode( '', $errors ).'</ul>';
			$this->lang_info_form( $mode );
			return;
		}
		
		//-----------------------------------------
		// Build insert array
		//-----------------------------------------
		
		$db_array = array(
							'lang_title'   => $this->request['lang_title'],
							'lang_short'   => $this->request['lang_short'],
							'lang_default' => $this->request['lang_default'],
							'lang_isrtl'   => $this->request['lang_isrtl'],
						);
						
		//-----------------------------------------
		// Adding or editing
		//-----------------------------------------
		
		if( $mode == 'new' )
		{
			//-----------------------------------------
			// Default?
			//-----------------------------------------
			
			if( $this->request['lang_default'] )
			{
				$this->DB->update( 'core_sys_lang', array( 'lang_default' => 0 ) );
				
				$db_array['lang_default']	= 1;
			}

			//-----------------------------------------
			// Insert and get id
			//-----------------------------------------
			
			$this->DB->insert( 'core_sys_lang', $db_array );

			$id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Create directory
			//-----------------------------------------
			
			@mkdir( IPS_CACHE_PATH . 'cache/lang_cache/' . $id, IPS_FOLDER_PERMISSION );
			@file_put_contents( IPS_CACHE_PATH . 'cache/lang_cache/' . $id . '/index.html', '' );
			@chmod( IPS_CACHE_PATH . 'cache/lang_cache/' . $id, IPS_FOLDER_PERMISSION );
			
			//-----------------------------------------
			// Copy over language bits from default lang
			//-----------------------------------------
			
			$default	= $this->DB->buildAndFetch( array( 'select' => 'lang_id', 'from' => 'core_sys_lang', 'where' => "lang_default=1" ) );
			
			$this->DB->build( array( 'select' => 'word_app,word_pack,word_key,word_default', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$default['lang_id']}" ) );
			$q = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $q ) )
			{
				$r['lang_id'] = $id;
				$this->DB->insert( 'core_sys_lang_words', $r );
			}
						
			//-----------------------------------------
			// Rebuild IPB and disk caches
			//-----------------------------------------
			
			$this->registry->class_localization->rebuildLanguagesCache();
			$this->cacheToDisk($id);
			
			//-----------------------------------------
			// Show listing
			//-----------------------------------------
			
			$this->registry->output->global_message = $this->lang->words['language_pack_created'];
			$this->languagesList();						
		}
		else 
		{
			//-----------------------------------------
			// Default?
			//-----------------------------------------
			
			if( $this->request['lang_default'] )
			{
				$this->DB->update( 'core_sys_lang', array( 'lang_default' => 0 ) );
				
				$db_array['lang_default']	= 1;
			}
			
			//-----------------------------------------
			// Check ID and update
			//-----------------------------------------
			
			$id = intval( $this->request['id'] );

			$this->DB->update( 'core_sys_lang', $db_array, "lang_id={$id}" );

			//-----------------------------------------
			// Rebuild cache and show list
			//-----------------------------------------
			
			$this->registry->class_localization->rebuildLanguagesCache();
			$this->registry->output->global_message = $this->lang->words['language_pack_updated'];
			$this->languagesList();
		}
	}	
	
	/**
	 * Builds the language information form
	 *
	 * @param	string	$mode	new or edit
	 * @return	@e void
	 */	
	public function languageInformationForm( $mode='new' )
	{
		//-----------------------------------------
		// Adding or editing
		//-----------------------------------------
		
		if( $mode == 'new' )
		{
			$title	= $this->lang->words['language_form_new_title'];
			$button	= $this->lang->words['language_form_new_button'];
			$op		= 'do_new_language';
			$header	= $this->lang->words['language_form_new_info'];
			$data	= array(0);
			$id		= 0;		
		}
		else 
		{
			$title	= $this->lang->words['language_form_edit_title'];
			$button	= $this->lang->words['language_form_edit_button'];
			$op		= 'do_edit_lang_info';
			$header	= $this->lang->words['language_form_edit_info'];
			$id		= intval( $this->request['id'] );

			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['invalid_id'], 11147 );
			}	
			
			//-----------------------------------------
			// Get language pack info
			//-----------------------------------------
			
			$data	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$id}" ) );
		}
		
		//-----------------------------------------
		// Set some defaults
		//-----------------------------------------
		
		$data['lang_title']		= !empty( $this->request['lang_title'] )	? $this->request['lang_title']   : $data['lang_title'];
		$data['lang_short']		= !empty( $this->request['lang_short'] )	? $this->request['lang_short']   : $data['lang_short'];
		$data['lang_default']	= !empty( $this->request['lang_default'] )	? $this->request['lang_default'] : $data['lang_default'];
		$data['lang_isrtl']		= !empty( $this->request['lang_isrtl'] )	? $this->request['lang_isrtl']   : $data['lang_isrtl'];

		$data['lang_default']	= $this->registry->output->formYesNo( 'lang_default', $data['lang_default'] );
		$data['lang_isrtl']		= $this->registry->output->formYesNo( 'lang_isrtl', $data['lang_isrtl'] );
		
		//-----------------------------------------
		// Show form
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( '', $title );
		$this->registry->output->html .= $this->html->languageInformationForm( $op, $id, $title, $header, $data, $button );
	}	
	
	/**
	 * Lists currently installed languages
	 *
	 * @return	@e void
	 */
	public function languagesList()
	{
		/* Do we have a valid translation session? */
		$sessionData  = $this->cache->getCache('translate_session');
		$hasTranslate = false;
		
		/* Check */
		if ( ! empty( $sessionData['lang_id'] ) AND count( $sessionData['files'] ) )
		{
			$hasTranslate = true;
		}
		
		/* Fallback for the recache all button */
		$forceEnglish = IPSCookie::get('forceEnglish');
		
		if ( !$this->lang->words['language_list_recache'] || $forceEnglish )
		{
			$this->lang->words['language_list_recache'] = 'Recache all...';
		}
		
		//-----------------------------------------
		// Get languages
		//-----------------------------------------
		
		$rows = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			/* Get Local Data */
			//setlocale( LC_ALL, $r['lang_short'] );
			IPSLib::setlocale( $r['lang_short'] );
			$this->registry->class_localization->local_data = localeconv();
			
			$_menu = array();

			if( $r['lang_protected'] && ! IN_DEV )
			{
				$_menu[] = array( "", $this->lang->words['lang_pack_protected'], 'edit' );
			}
			else
			{
				$_menu[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=list_word_packs&amp;id={$r['lang_id']}", $this->lang->words['language_list_translate'], 'info' );
				
				/* If we don't have a current session... */
				if ( ! $hasTranslate )
				{
					$_menu[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=translateExtSplash&amp;id={$r['lang_id']}", $this->lang->words['language_list_translate_ext'], 'info' );
				}
			}
			
			$_menu[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=copy_lang_pack&amp;id={$r['lang_id']}", $this->lang->words['language_list_copy'] );
			
			$_menu[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=export&amp;id={$r['lang_id']}", $this->lang->words['l_xmlexportfull'], 'export' );
			
			if ( $r['lang_id'] )
			{
				$_menu[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=rebuildFromXml&amp;id={$r['lang_id']}&amp;type=public", $this->lang->words['rebuild_lang_from_xml'] );
			}
			
			foreach( ipsRegistry::$applications as $app_dir => $app_data )
			{
				$_menu[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=export&amp;id={$r['lang_id']}&amp;app_dir={$app_dir}", $this->lang->words['l_xmlexport'] . $app_data['app_title'], 'export' );
			}

			//-----------------------------------------
			// Data for output
			//-----------------------------------------
			
			$rows[] = array( 'title'	=> $r['lang_title'],
							 'local'	=> $r['lang_short'],
							 'date'		=> $this->registry->class_localization->getDate( time(), 'long', 1 ) . '<br />' . $this->registry->class_localization->getDate( time(), 'short', 1 ),
							 'money'	=> $this->registry->class_localization->formatMoney( '12345231.12', 0 ),
							 'default'	=> $r['lang_default'],
							 'menu'		=> $_menu,
							 'id'		=> $r['lang_id'],
							 'protected'=> $r['lang_protected'],
							);
		}
		
		//-----------------------------------------
		// Reset locale
		//-----------------------------------------
		
		IPSLib::setlocale( $this->registry->class_localization->local );
		//setlocale( LC_ALL, $this->registry->class_localization->local );
		$this->registry->class_localization->local_data = localeconv();
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->languages_list( $rows, $hasTranslate );
	}
	
	/**
	 * Updates the cached language files from the database
	 *
	 * @param	integer	ID of the language pack to export
	 * @param	string	Just cache one app?
	 * @return	@e void
	 */
	public function cacheToDisk( $lang_id, $app_override='' )
	{
		/* Generate cached warning */
		$warnString = "/*******************************************************\nNOTE: This is a cache file generated by IP.Board on " . date( "r" ) . " by " . $this->memberData['members_display_name'] . "\nDo not translate this file as you will lose your translations next time you edit via the ACP\nPlease translate via the ACP\n*******************************************************/\n\n";
		
		//-----------------------------------------
		// Build where statement
		//-----------------------------------------
		
		if ( $lang_id == 'master' )
		{
			$lang_id = 'master_lang';
			$where	 = "lang_id=1";
		}
		else if( $lang_id AND $lang_id != 1 )
		{
			$where	= "lang_id={$lang_id}";
		}
		else
		{
			$lang_id	= 1;
			$where		= "lang_id=1";
		}
		
		//-----------------------------------------
		// If missing directory, create
		//-----------------------------------------
		
		if( ! is_dir( IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/' ) )
		{
			mkdir( IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/', IPS_FOLDER_PERMISSION );
		}
		
		/* Loop over apps */
		$this->DB->build( array( 'select' => 'DISTINCT(word_app) as word_app',
								 'from'	  => 'core_sys_lang_words',
								 'limit'  => array(0, 100 ) ) ); # Because you never know.
		
		$o = $this->DB->execute();
		
		while( $langApps = $this->DB->fetch( $o ) )
		{
			
			if ( $app_override )
			{
				if ( $app_override != $langApps['word_app'] )
				{
					continue;
				}
			}
			
			//-----------------------------------------
			// Get the words
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => 'word_custom, word_default, word_app, word_pack, word_key',
									 'from'   => 'core_sys_lang_words',
									 'where'  => $where .  " AND word_app='" . $langApps['word_app'] . "'",
									 'order'  => 'word_key ASC' ) );
									 
			$i = $this->DB->execute();
			
			$words_by_file = array();
			
			while( $r = $this->DB->fetch( $i ) )
			{
				$_text = ( $r['word_custom'] ) ? $r['word_custom'] : $r['word_default'];
				$words_by_file[$r['word_app']][$r['word_pack']][] = array( $r['word_key'], $_text );
			}
			
			//-----------------------------------------
			// Now loop and write to file
			//-----------------------------------------
			
			foreach( $words_by_file as $app => $word_packs )
			{			
				foreach( $word_packs as $pack => $words )
				{	
					if( $pack == 'public_js' )
					{
						$to_write	= '';
						$_file		= 'ipb.lang.js';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( "\\", "\\\\", $word[1] );
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
						}					
					}
					else if( $pack == 'admin_js' )
					{
						$to_write	= '';
						$_file		= 'acp.lang.js';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( "\\", "\\\\", $word[1] );
							$word[1]	= str_replace( '"', '\\"', $word[1] );
							$to_write	.= "ipb.lang['{$word[0]}']	= \"{$word[1]}\";\n";
						}
					}
					else
					{
						//-----------------------------------------
						// Build cache file contents
						//-----------------------------------------
						
						$to_write	= "<?php\n\n$warnString\n\n\$lang = array( \n";
						$_file		= $app . '_' . $pack . '.php';
						
						foreach( $words as $word )
						{
							$word[1]	= str_replace( "\\", "\\\\", $word[1] );
							$word[1]	= str_replace( '"', '\\"', $word[1] );
	
							//-----------------------------------------
							// Protect swapped sprintf arguments
							// @link	http://community.invisionpower.com/tracker/issue-22240-lang-strings-using-sprintf-argument-swapping/
							//-----------------------------------------
							
							$word[1]	= str_replace( '$s', '\\$s', $word[1] );
							
							$to_write	.= "'{$word[0]}' => \"{$word[1]}\",\n";
						}
	
						$to_write .= " ); \n";					
					}
					
					//-----------------------------------------
					// Write the file
					//-----------------------------------------
					
					$_dir = IPS_CACHE_PATH . 'cache/lang_cache/' . $lang_id . '/';
					
					if( is_file($_dir . $_file) )
					{
						@unlink( $_dir . $_file );
					}
					
					if ( $fh = @fopen( $_dir . $_file, 'wb' ) )
					{
						fwrite( $fh, $to_write, strlen( $to_write ) );
						fclose( $fh );
						@chmod( $_dir . $_file, IPS_FILE_PERMISSION );
					}
					else
					{
						$this->cache_errors[] = $this->lang->words['l_nowrite'] . $_dir . $_file;
					}
				}
			}
		}
	}
	
	/**
	 * Imports language packs from an xml file and updates the database and recaches the languages
	 *
	 * @param	integer	$lang_id		ID of the language pack to import
	 * @param	bool	$in_dev			Set to 1 for developer language import
	 * @param	bool	$no_return		If set to 1, this function will return a value, rather than outputting data
	 * @param	string	$app_override	Overrides the application for which languages are being imported
	 * @param	bool	$skip_charset	Skips charset conversion in the XML file (useful during upgrade routine as strings don't need to be converted)
	 * @return	mixed
	 */
	public function imprtFromXML( $lang_id=0, $in_dev=0, $no_return=0, $app_override='', $skip_charset=false )
	{
		//-----------------------------------------
		// Set version..
		//-----------------------------------------
		
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// INDEV?
		//-----------------------------------------

		if ( $in_dev )
		{
			$_FILES['FILE_UPLOAD']['name']	= '';
		}
		else if( $this->request['file_location'] )
		{
			$this->request['file_location']	= IPS_ROOT_PATH . $this->request['file_location'];
		}

		//-----------------------------------------
		// Not an upload?
		//-----------------------------------------
		
		if ( $_FILES['FILE_UPLOAD']['name'] == "" or ! $_FILES['FILE_UPLOAD']['name'] or ($_FILES['FILE_UPLOAD']['name'] == "none") )
		{
			//-----------------------------------------
			// Check and load from server
			//-----------------------------------------
			
			if ( ! $this->request['file_location'] )
			{
				$this->registry->output->global_message = $this->lang->words['l_nofile'];
				$this->languagesList();
				return;
			}
			
			if ( ! is_file( $this->request['file_location'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['l_noopen'] . $this->request['file_location'];
				$this->languagesList();
				return;
			}
			
			if ( preg_match( '#\.gz$#', $this->request['file_location'] ) )
			{
				if ( $FH = @gzopen( $this->request['file_location'], 'rb' ) )
				{
					while ( ! @gzeof( $FH ) )
					{
						$content .= @gzread( $FH, 1024 );
					}
					
					@gzclose( $FH );
				}
			}
			else
			{
				$content = file_get_contents( $this->request['file_location'] );
			}
			
			$originalContent	= $content;
			
			//-----------------------------------------
			// Extract archive
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH.'classXMLArchive.php' );/*noLibHook*/
			$xmlarchive = new classXMLArchive();
			
			//-----------------------------------------
			// Read the archive
			//-----------------------------------------
			
			$xmlarchive->readXML( $content );
			
			//-----------------------------------------
			// Get the data
			//-----------------------------------------
			
			$content = '';
			
			foreach( $xmlarchive->asArray() as $k => $f )
			{
				if( $k == 'language_entries.xml' )
				{
					$content = $f['content'];
					break;
				}
			}

			//-----------------------------------------
			// No content from de-archiving, must not
			// be archive, but rather raw XML file
			//-----------------------------------------
			
			if( $content == '' AND strpos( $originalContent, "<languageexport" ) !== false )
			{
				$content	= $originalContent;
			}
		}
		
		//-----------------------------------------
		// It's an upload
		//-----------------------------------------
		
		else
		{
			//-----------------------------------------
			// Get uploaded schtuff
			//-----------------------------------------
			
			$tmp_name = $_FILES['FILE_UPLOAD']['name'];
			$tmp_name = preg_replace( '#\.gz$#', "", $tmp_name );
			
			if( $_FILES['FILE_UPLOAD']['error'] )
			{
				switch( $_FILES['FILE_UPLOAD']['error'] )
				{
					case 1:					
						$this->registry->output->global_message = sprintf( $this->lang->words['lang_upload_too_large'], ini_get( 'upload_max_filesize' ) );
						$this->languagesList();
						return;
					break;
					
					default:
						$this->registry->output->global_message = $this->lang->words['lang_upload_other_error'];
						$this->languagesList();
						return;
					break;						
				}
			}
			
			//-----------------------------------------
			// Get content
			//-----------------------------------------
			
			try
			{
				$uploadedContent = $this->registry->adminFunctions->importXml( $tmp_name );
			}
			catch ( Exception $e )
			{
				$this->registry->output->showError( $e->getMessage() );
			}
			
			//-----------------------------------------
			// Extract archive
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH.'classXMLArchive.php' );/*noLibHook*/
			$xmlarchive = new classXMLArchive();
			
			//-----------------------------------------
			// Read the archive
			//-----------------------------------------
			
			$xmlarchive->readXML( $uploadedContent );
			
			//-----------------------------------------
			// Get the data
			//-----------------------------------------
			
			$content = '';
			
			foreach( $xmlarchive->asArray() as $k => $f )
			{
				if( $k == 'language_entries.xml' )
				{
					$content = $f['content'];
					break;
				}
			}

			//-----------------------------------------
			// No content from de-archiving, must not
			// be archive, but rather raw XML file
			//-----------------------------------------
			
			if( $content == '' AND strpos( $uploadedContent, "<languageexport" ) !== false )
			{
				$content	= $uploadedContent;
			}
		}

		//-----------------------------------------
		// Make sure we have content
		//-----------------------------------------
		
		if( !$content )
		{
			if( $no_return )
			{
				return;
			}
			
			$this->registry->output->global_message = $this->lang->words['l_badfile'];
			$this->languagesList();
			return;
		}

		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( $skip_charset ? 'utf-8' : IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		//-----------------------------------------
		// Is this full language pack?...
		//-----------------------------------------
		
		foreach( $xml->fetchElements('langinfo') as $lang_data )
		{
			$lang_info	= $xml->fetchElementsFromRecord( $lang_data );
			
			$lang_data	= array(
									'lang_short' => $lang_info['lang_short'],
									'lang_title' => $lang_info['lang_title'],
								);
		}

		$lang_ids	= array();
		$insertId	= 0;

		//-----------------------------------------
		// Do we have language pack info?
		//-----------------------------------------
		
		if( $lang_data['lang_short'] )
		{
			//-----------------------------------------
			// Does this pack already exist
			//-----------------------------------------
			
			$update_lang = $this->DB->buildAndFetch( array( 
													'select' => 'lang_id', 
													'from'   => 'core_sys_lang',
													'where'  => "lang_short='{$lang_data['lang_short']}'",
											)	);
	
			//-----------------------------------------
			// If doesn't exist, then create new pack
			//-----------------------------------------
			
			if( !$update_lang['lang_id'] )
			{
				$this->DB->insert( 'core_sys_lang', $lang_data );
				
				$insertId	= $this->DB->getInsertId();
				
				if( @mkdir( IPS_CACHE_PATH . '/cache/lang_cache/' . $insertId ) )
				{
					@file_put_contents( IPS_CACHE_PATH . 'cache/lang_cache/' . $insertId . '/index.html', '' );
					@chmod( IPS_CACHE_PATH . '/cache/lang_cache/' . $insertId, IPS_FOLDER_PERMISSION );
				}
				
				//-----------------------------------------
				// Copy over language bits from default lang
				//-----------------------------------------
				
				$default	= $this->DB->buildAndFetch( array( 'select' => 'lang_id', 'from' => 'core_sys_lang', 'where' => "lang_default=1" ) );
				
				$this->DB->build( array( 'select' => 'word_app,word_pack,word_key,word_default,word_js', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$default['lang_id']}" ) );
				$q = $this->DB->execute();

				while( $r = $this->DB->fetch( $q ) )
				{
					$r['lang_id']     = $insertId;
					$r['word_custom'] = '';
					
					$this->DB->insert( 'core_sys_lang_words', $r );
				}
							
				//-----------------------------------------
				// Rebuild IPB and disk caches
				//-----------------------------------------
				
				$this->registry->class_localization->rebuildLanguagesCache();
			}
			else
			{
				$insertId = $update_lang['lang_id'];
			}
		}

		//-----------------------------------------
		// We need to add language bits to every pack..
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$lang_ids[]	= $r['lang_id'];
		}

		//-----------------------------------------
		// Init counts array
		//-----------------------------------------
		
		$counts = array( 'updates' => 0, 'inserts' => 0 );
		
		//-----------------------------------------
		// Init a cache array to save entries
		//-----------------------------------------
		
		$lang_entries	= array();
		
		if( $app_override )
		{
			$this->DB->build( array( 'select'	=> "MD5( CONCAT( lang_id, '-', word_app, '-', word_pack, '-', word_key ) ) as word_lookup, word_id, md5(word_default) as word_default, word_default_version",
									 'from'		=> 'core_sys_lang_words',
									 'where'	=> "word_app='{$app_override}' AND lang_id IN(" . implode( ",", $lang_ids ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$lang_entries[ $r['word_lookup'] ]	= $r;
			}
		}
		
		//-----------------------------------------
		// Start looping
		//-----------------------------------------
		
		foreach( $xml->fetchElements('lang') as $entry )
		{
			$lang  = $xml->fetchElementsFromRecord( $entry );
			
			foreach( $lang_ids as $_lang_id )
			{
				$lang_entry	= array();
				
				//-----------------------------------------
				// Build db array
				//-----------------------------------------
								
				$db_array = array(
									'lang_id'				=> $_lang_id,
									'word_app'				=> $app_override ? $app_override : $lang['word_app'],
									'word_pack'				=> $lang['word_pack'],
									'word_key'				=> $lang['word_key'],
									'word_default'			=> $lang['word_default'],
									'word_custom'			=> $in_dev ? '' : $lang['word_custom'],
									'word_js'				=> intval($lang['word_js']),
									'word_default_version'	=> $lang['word_default_version'] ? $lang['word_default_version'] : $LATESTVERSION['long'],
									'word_custom_version'	=> $lang['word_custom_version'],
								);
								
				$dbKey     = md5( $db_array['lang_id'] . '-' . $db_array['word_app']  . '-' . $db_array['word_pack'] . '-' . $db_array['word_key'] );
				$langIdKey = md5( $_lang_id . '-' . $db_array['word_app'] . '-' . $db_array['word_pack'] . '-' . $db_array['word_key'] );
				
				// If the default value hasn't changed, we don't need to update the word_default_version (see bug report 19172)
				if ( md5( $lang['word_default'] ) == $lang_entries[ $dbKey ]['word_default'] )
				{
					$db_array['word_default_version'] = $lang_entries[ $dbKey ]['word_default_version'];
				}
				
				//-----------------------------------------
				// If cached, get from cache
				//-----------------------------------------
				
				if ( $lang_entries[ $langIdKey ] )
				{
					$lang_entry	= $lang_entries[ $langIdKey ];
				}
												
				//-----------------------------------------
				// Otherwise get all langs from this entry and
				// put in cache
				//-----------------------------------------
				
				else if( ! $app_override )
				{
					$this->DB->build( array(
											'select'	=> '*',
											'from'		=> 'core_sys_lang_words',
											'where'		=> "lang_id={$_lang_id} AND word_app='{$db_array['word_app']}' AND word_pack='{$db_array['word_pack']}'"
										)		);
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$lang_entries[ md5( $r['lang_id'] . '-' . $r['word_app'] . '-' . $r['word_pack'] . '-' . $r['word_key'] ) ]	= $r;
					}
					
					if ( $lang_entries[ $langIdKey ] )
					{
						$lang_entry	= $lang_entries[ $langIdKey ];
					}
				}
				
				//-----------------------------------------
				// Didn't find any match?  Must be importing
				// a new language pack, huh?
				//-----------------------------------------
				
				if ( !isset( $lang_entry['word_id'] ) )
				{
					//continue 2;
				}
				
				//-----------------------------------------
				// If there is no new custom lang bit to insert
				// don't delete what is already there.
				//-----------------------------------------
				
				if( !$db_array['word_custom'] || ( $insertId > 0 && $insertId != $_lang_id ) )
				{
					unset($db_array['word_custom']);
					unset($db_array['word_custom_version']);
				}

				//-----------------------------------------
				// Lang bit already exists, update
				//-----------------------------------------
				
				if ( $lang_entry['word_id'] AND ( ! $insertId OR ( $insertId == $_lang_id ) ) )
				{
					//-----------------------------------------
					// Don't update default version
					//-----------------------------------------
					
					// This causes no languages to show "out of date" on upgrade
					// @link	http://community.invisionpower.com/tracker/issue-31637-no-out-of-date-entries-for-language-string-updates
					// This WAS added to fix a previously reported bug, however, so need to watch out for this
					// unset( $db_array['word_default_version'] );
					
					$counts['updates']++;
					$this->DB->update( 'core_sys_lang_words', $db_array, "word_id={$lang_entry['word_id']}" );
				}
				
				//-----------------------------------------
				// Lang bit doesn't exist, so insert
				//-----------------------------------------

				else if( ! $lang_entry['word_id'] )
				{
					/* Ensure there is a value to avoid null */
					if ( ! $db_array['word_custom'] )
					{
						$db_array['word_custom'] = '';
					}
					
					/* Ensure custom word but isn't added to other packs that simply had missing strings */
					if ( $insertId AND $insertId != $_lang_id )
					{
						$db_array['word_custom'] = '';
					}
					
					$counts['inserts']++;
					$this->DB->insert( 'core_sys_lang_words', $db_array );
				}
				
				unset( $lang_entry, $db_array );
			}
		}
		
		/* Save some memory */
		unset( $lang_entries );
		
		//-----------------------------------------
		// Recache all our lang packs
		//-----------------------------------------

		foreach( $lang_ids as $_lang_id )
		{
			$this->cacheToDisk( $_lang_id, $app_override );
		}
		
		//-----------------------------------------
		// Set output message
		//-----------------------------------------
		
		$this->registry->output->global_message = sprintf( $this->lang->words['l_updatedcount'], $counts['updates'], $counts['inserts'] );
		
		if ( is_array( $this->cache_errors ) AND count( $this->cache_errors ) )
		{
			$this->registry->output->global_message .= "<br />" . implode( "<br />", $this->cache_errors );
		}

		//-----------------------------------------
		// Free a little memory
		//-----------------------------------------
		
		unset( $xml );
		
		//-----------------------------------------
		// Update IPB cache
		//-----------------------------------------
		
		$this->registry->class_localization->rebuildLanguagesCache();
		
		//-----------------------------------------
		// Return! Now!
		//-----------------------------------------
		
		if ( ! $no_return )
		{
			$this->languagesList();
			return;
		}
	}

	/**
	 * Export language entries to xml file
	 *
	 * @param	integer	$lang_id	Language pack to export
	 * @param	bool	$disk		Save to disk instead
	 * @return void
	 */
	public function languageExportToXML( $lang_id, $disk=false )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$app_dir		= trim( $this->request['app_dir'] );
		$type			= trim( $this->request['type'] );
		$_where			= '';
		$_name			= 'language.xml';
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		$doPack			= true;

		//-----------------------------------------
		// Filter
		//-----------------------------------------
		
		if ( $app_dir )
		{
			$_where	= " AND word_app='" . $app_dir . "'";
			$_name	= $app_dir . '_language_pack.xml';
			$doPack	= false;
		}
		
		if ( $type )
		{
			if ( $type == 'admin' )
			{
				$_where	.= " AND word_pack LIKE 'admin_%'";
				$_name	= 'admin_' . $_name;
			}
			else
			{
				$_where	.= " AND word_pack LIKE 'public_%'";
				$_name	= 'public_' . $_name;
			}
			
			$doPack	= false;
		}
		
		//-----------------------------------------
		// Create the XML library
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'languageexport' );
		$xml->addElement( 'languagegroup', 'languageexport' );

		//-----------------------------------------
		// Get language pack
		//-----------------------------------------
		
		$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_lang', 'where' => "lang_id={$lang_id}" ) );
		
		//-----------------------------------------
		// Add pack if necessary
		//-----------------------------------------
		
		if( $doPack )
		{
			$xml->addElementAsRecord( 'languagegroup', 'langinfo', $data );
		}

		//-----------------------------------------
		// Get the words
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang_words', 'where' => "lang_id={$lang_id}" . $_where ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Add words to export
		//-----------------------------------------
		
		$word_packs = array();
		$_strings	= 0;
		
		while( $r = $this->DB->fetch() )
		{
			$content = array();
			$_strings++;
			
			if( $disk )
			{
				$content = array( 
								'word_app'				=> $r['word_app'],
								'word_pack'				=> $r['word_pack'],
								'word_key'				=> $r['word_key'],
								'word_default'			=> $r['word_default'],
								//'word_default_version'	=> ( $r['word_default_version'] >= 30000 ) ? $r['word_default_version'] : $LATESTVERSION['long'],
								//'word_js'				=> $r['word_js']
							);

				if( $r['word_js'] )
				{
					$content['word_js']	= $r['word_js'];
				}
			}
			else
			{			
				$content = array( 
								'word_app'				=> $r['word_app'],
								'word_pack'				=> $r['word_pack'],
								'word_key'				=> $r['word_key'],
								'word_default'			=> $r['word_default'],
								'word_custom'			=> $r['word_custom'],
								'word_default_version'	=> ( $r['word_default_version'] >= 30000 ) ? $r['word_default_version'] : $LATESTVERSION['long'],
								'word_custom_version'	=> $r['word_custom_version'],
								'word_js'				=> $r['word_js']
							);
			}

			$xml->addElementAsRecord( 'languagegroup', 'lang', $content );
		}
		
		//-----------------------------------------
		// Got any strings?
		//-----------------------------------------
		
		if( !$_strings )
		{
			if( $disk )
			{
				return false;
			}
			else
			{
				$this->registry->output->global_message = $this->lang->words['l_nolangbits'];
				$this->languagesList();
				return;
			}
		}

		//-----------------------------------------
		// Write to disk or output to browser
		//-----------------------------------------
		
		if( $disk )
		{
			@unlink( IPSLib::getAppDir($app_dir) . '/xml/' . $_name );
			@file_put_contents( IPSLib::getAppDir($app_dir) . '/xml/' . $_name, $xml->fetchDocument() );
			return true;
		}
		else
		{
			//-----------------------------------------
			// Create xml archive
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
			$xmlArchive = new classXMLArchive();
			
			//-----------------------------------------
			// Add XML document
			//-----------------------------------------
			
			$xmlArchive->add( $xml->fetchDocument(), 'language_entries.xml' );
			
			//-----------------------------------------
			// Print to browser
			//-----------------------------------------
			
			$this->registry->output->showDownload( $xmlArchive->getArchiveContents(), $_name );
			exit();
		}
	}

	/**
	 * Builds the language db entries from cache
	 *
	 * @param	boolean			return as normal
	 * @return	@e void
	 * @author	Josh
	 */
	public function importFromCacheFiles( $returnAsNormal=TRUE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$msg			= array();
		$lang_id		= 1;
		$LATESTVERSION	= IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $_POST['apps'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['l_noapp'];
			$this->languagesList();
			return;
		}
		
		//-----------------------------------------
		// Loop through apps...
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app => $app_data )
		{
			if ( in_array( $app, $_POST['apps'] ) )
			{
				//-----------------------------------------
				// Get directory
				//-----------------------------------------
				
				$_dir    = IPS_CACHE_PATH . 'cache/lang_cache/master_lang/';
				
				//-----------------------------------------
				// Go through directories
				//-----------------------------------------
				
				if ( is_dir( $_dir ) )
				{
					$dh = opendir( $_dir );
								
					while( $f = readdir( $dh ) )
					{
						if ( $f[0] == '.' || $f == 'index.html' )
						{
							continue;
						}	
				
						if ( preg_match( "#^" . $app . '_\S+?\.php$#', $f ) )
						{
							//-----------------------------------------
							// INIT
							//-----------------------------------------
							
							$updated	= 0;
							$inserted	= 0;
							$word_pack	= preg_replace( "#^" . $app . '_(\S+?)\.php$#', "\\1", $f );
							$lang		= array();
							$db_lang	= array();
							
							//-----------------------------------------
							// Delete current language bits
							//-----------------------------------------

							$this->DB->delete( 'core_sys_lang_words', "lang_id=1 AND word_app='" . $app . "' AND word_pack='" . $word_pack . "'" );

							if ( IPS_IS_SHELL )
							{
								$stdout = fopen('php://stdout', 'w');
								fwrite( $stdout, 'Processing: ' . $f . "\n" );
								fclose( $stdout );
							}
							
							require( $_dir . $f );/*noLibHook*/
						
							//-----------------------------------------
							// Loop
							//-----------------------------------------

							foreach( $lang as $k => $v )
							{
								$inserted++;

								$insert = array(
													'lang_id'				=> $lang_id,
													'word_app'				=> $app,
													'word_pack'				=> $word_pack,
													'word_key'				=> $k,
													'word_default'			=> IPSText::encodeForXml($v),
													'word_default_version'	=> $LATESTVERSION['long'],
													'word_js'				=> 0,
												);

								$this->DB->insert( 'core_sys_lang_words', $insert );
							}
							
							$msg[] = sprintf( $this->lang->words['indev_lang_import'], $f, $inserted, $updated );
						}
						else if( preg_match( '/(\.js)$/', $f ) AND $app == 'core' )
						{
							$_js_word_pack	= '';
							
							if( $f == 'ipb.lang.js' )
							{
								$_js_word_pack	= 'public_js';
							}
							else if( $f == 'acp.lang.js' )
							{
								$_js_word_pack	= 'admin_js';
							}
							
							//-----------------------------------------
							// Delete current words for this app and word pack
							//-----------------------------------------
							
							$this->DB->delete( 'core_sys_lang_words', "lang_id=1 AND word_app='" . $app . "' AND word_pack='" . $_js_word_pack . "'" );
						
							if ( IPS_IS_SHELL )
							{
								$stdout = fopen('php://stdout', 'w');
								fwrite( $stdout, 'Processing: ' . $f . "\n" );
								fclose( $stdout );
							}
							
							//-----------------------------------------
							// Get each line
							//-----------------------------------------
							
							$js_file = file( $_dir . $f );
							
							//-----------------------------------------
							// Loop through lines and import
							//-----------------------------------------
							
							foreach( $js_file as $r )
							{
								//-----------------------------------------
								// preg_match what we want
								//-----------------------------------------
								
								preg_match( '#ipb\.lang\[\'(.+?)\'\](.+?)= ["\'](.+?)["\'];#', $r, $matches );

								//-----------------------------------------
								// Valid?
								//-----------------------------------------
								
								if( $matches[1] && $matches[3] )
								{
									$inserted++;
									$insert = array(
														'lang_id'      => $lang_id,
														'word_app'     => 'core',
														'word_pack'    => $_js_word_pack,
														'word_key'     => $matches[1],
														'word_default' => IPSText::encodeForXml($matches[3]),
														'word_js'      => 1,
													);
									$this->DB->insert( 'core_sys_lang_words', $insert );
								}
							}
						}
					}
	
					closedir( $dh );
				}
			}
		}

		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		if ( $returnAsNormal === TRUE )
		{
			$this->registry->output->setMessage( implode( "<br />", $msg ), true );
		
			if ( ! $this->__daily )
			{
				$this->languagesList();
			}
		}
		else
		{
			return $msg;
		}
	}
}