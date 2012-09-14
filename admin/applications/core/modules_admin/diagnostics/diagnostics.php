<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Diagnostic tools
 * Last Updated: $Date: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10771 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_diagnostics_diagnostics extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Db tools
	 *
	 * @var		object
	 */
	protected $dbTools;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Load skin and setup stuff */
		$this->html			= $this->registry->output->loadTemplate('cp_skin_diagnostics');
		$this->form_code	= $this->html->form_code	= 'module=diagnostics&amp;section=diagnostics';
		$this->form_code_js	= $this->html->form_code_js	= 'module=diagnostics&section=diagnostics';
		
		//-----------------------------------------
		// Some of these functions can take a while..
		//-----------------------------------------
		
		@set_time_limit(0);
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_system' ), 'core' );

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'dbindex':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'index_checker' );
				$this->_indexCheck();
			break;
				
			case 'dbchecker':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'database_checker' );
				$this->_dbCheck();
			break;			
				
			case 'whitespace':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'whitespace_checker' );
				$this->_whitespaceCheck();
			break;
			
			case 'connections':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'connection_checker' );
				$this->_checkConnections();
			break;
				
			case 'filepermissions':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'permission_checker' );
				$this->_permissionsCheck();
			break;

			case 'email':
				$this->_emailCheckerForm();
			break;
			case 'doemail':
				$this->_emailCheckerGo();
			break;
			
			default:
				$this->_listFunctions();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Test outbound connections
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _checkConnections()
	{
		//-----------------------------------------
		// Turn errors on so we can view them
		//-----------------------------------------
		
		@ini_set( 'display_errors', 1 );
		error_reporting( E_ALL ^ E_NOTICE );
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$_files = new $classToLoad();
		
		$_test		= $_files->getFileContents( "http://www.invisionpower.com/connection-checker.html" );
		$_headers	= $_files->raw_headers;
		
		$_test1		= $_files->getFileContents( "https://www.invisionpower.com/connection-checker.html" );
		$_headers1	= $_files->raw_headers;
		
		$this->registry->output->html .= $this->html->connectionCheckerResult( $_headers, $_test, $_headers1, $_test1 );
	}
	
	/**
	 * Check file permissions
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _permissionsCheck()
	{
		$checkdirs = array( PUBLIC_DIRECTORY . '/style_images', 
							PUBLIC_DIRECTORY . '/style_css', 
							PUBLIC_DIRECTORY . '/style_emoticons', 
							'cache', 
							'cache/skin_cache', 
							'cache/lang_cache', 
							'cache/tmp', 
							'uploads',
							'uploads/profile', 
							rtrim( str_replace( DOC_IPS_ROOT_PATH, '', IPS_HOOKS_PATH ), '/\\' ),
							);
		
		//-----------------------------------------		
		// Get language directories
		//-----------------------------------------

		if( is_array( $this->cache->getCache('lang_data') ) && count( $this->cache->getCache('lang_data') ) )
		{
			foreach( $this->cache->getCache('lang_data') as $v )
			{
				$this_lang   = 'cache/lang_cache/' . $v['lang_id'];
				$checkdirs[] = $this_lang;
				
				try
				{
					foreach( new DirectoryIterator( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . '/' . $this_lang ) as $file )
					{
						if( $file->isFile() )
						{
							$checkdirs[] = $this_lang . '/' . $file->getFilename();
						}
					}
				} catch ( Exception $e ) {}
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'lang_id', 'from' => 'core_sys_lang' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$this_lang		= 'cache/lang_cache/' . $v['lang_id'];
				$checkdirs[]	= $this_lang;
				
				try
				{
					foreach( new DirectoryIterator( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . '/' . $this_lang ) as $file )
					{
						if( $file->isFile() )
						{
							$checkdirs[] = $this_lang . '/' . $file->getFilename();
						}
					}
				} catch ( Exception $e ) {}		
			}
		}
		
		//-----------------------------------------		
		// Get emoticon directories
		//-----------------------------------------
				
		if( is_array( $this->cache->getCache('emoticons') ) && count( $this->cache->getCache('emoticons') ) )
		{
			foreach( $this->cache->getCache('emoticons') as $v )
			{
				$checkdirs[] = PUBLIC_DIRECTORY . '/style_emoticons/' . $v['emo_set'];
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'emo_set', 'from' => 'emoticons' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$checkdirs[] = PUBLIC_DIRECTORY . '/style_emoticons/' . $v['emo_set'];
			}
		}
		
		//-----------------------------------------		
		// Get skin directories
		//-----------------------------------------
				
		$skin_dirs = array();
		
		if( is_array( $this->cache->getCache('skin_id_cache') ) && count( $this->cache->getCache('skin_id_cache') ) )
		{
			foreach( $this->cache->getCache('skin_id_cache') as $k => $v )
			{
				if( $k == 1 && !IN_DEV )
				{
					continue;
				}
				
				$checkdirs[]	= 'cache/skin_cache/cacheid_' . $v['set_id'];
				$skin_dirs[]	= $v['set_skin_set_id'];
			}
		}
		else
		{
			$this->DB->build( array( 'select' => 'set_id', 'from' => 'skin_collections' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$checkdirs[]	= 'cache/skin_cache/cacheid_' . $v['set_id'];
				$skin_dirs[]	= $v['set_skin_set_id'];
			}
		}
		
		//-----------------------------------------		
		// Get skin files
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'template_group' ),
								 'from'   => 'skin_templates',
								 'group'  => 'template_group',
						 )		);
		$this->DB->execute();
		
		while( $v = $this->DB->fetch() )
		{
			foreach( $skin_dirs as $dir )
			{
				$checkdirs[] = 'cache/skin_cache/cacheid_' . $dir . '/' . $v['group_name'] . '.php';
			}
		}
		
		$checkdirs	= array_unique($checkdirs);
		$output		= array();
		
		foreach( $checkdirs as $dir_to_check )
		{
			if( !file_exists( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . '/' . $dir_to_check ) )
			{
				# Could be skin files from custom skins for components they don't own
				# or they could be using safe_mode skins
				# Make sure skin_cache still shows up though...
				
				if( !strpos( $dir_to_check, 'skin_' ) OR !strpos( $dir_to_check, '.php' ) )
				{
					$output[] = "<span style='color:red;font-weight:bold;'>{$this->lang->words['d_p404']} ". rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . "/{$dir_to_check}</span>";
				}
			}
			else 
			{
				$output[] = ( is_writable( DOC_IPS_ROOT_PATH . '/' . $dir_to_check ) ) ? "<span style='color:green;'>" . rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . "/{$dir_to_check} {$this->lang->words['d_pyes']}</span>" : "<span style='color:red;font-weight:bold;'>{$this->lang->words['d_pno']} ". rtrim( DOC_IPS_ROOT_PATH, '/\\' ) . "/{$dir_to_check}</span>";
			}
		}
		
		$this->registry->output->html .= $this->html->permissionsResults( $output );
	}
	
	/**
	 * Whitespace checking
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _whitespaceCheck()
	{
		$files_with_junk = $this->_whitespaceDirRecurse( rtrim( DOC_IPS_ROOT_PATH, '/\\' ) );
		
		$this->registry->output->html .= $this->html->whitespaceResults( $files_with_junk );
	}
	
	/**
	 * Recurse over a directory
	 *
	 * @param	string		Directory to check
	 * @return	array 		Array of files with whitespace in them
	 */
	public function _whitespaceDirRecurse( $dir )
	{
		$skip_dirs = array( 'uploads',
							PUBLIC_DIRECTORY,
							'js',
							'images',
							);
		
		$files	= array();
		
		try
		{
			foreach( new DirectoryIterator( $dir ) as $directory )
			{
				if( $directory->isDot() )
				{
					continue;
				}
        	
	    		if ( strpos( $directory->getFilename(), '_' ) === 0 OR strpos( $directory->getFilename(), '.' ) === 0 )
	    		{
			    	continue;
			    }
        	
				$newpath	= $dir . '/' . $directory->getFilename();
				$level		= explode( '/', $newpath );
        	
				if ( is_dir($newpath) && !in_array( $directory->getFilename(), $skip_dirs ) )
				{
					$files = array_merge( $files, $this->_whitespaceDirRecurse($newpath) );
				}
				else
				{
					if ( strpos( $directory->getFilename(), ".php" ) !== false && !is_dir( $newpath ) )
					{
						$file			= file_get_contents($newpath);
						$has_whitespace	= false;
						
						if( substr( ltrim($file), 0, 3 ) == '<?php' AND substr( $file, 0, 3 ) != '<?php' )
						{
							$has_whitespace	= true;
						}
						else if( substr( rtrim($file), -2 ) == '?>' AND substr( $file, -2 ) != '?>' )
						{
							//-----------------------------------------
							// PHP explicitly allows one newline after closing tag
							//-----------------------------------------
							
							if( substr( rtrim($file), -2 ) == '?>' AND substr( $file, -3 ) != "?>\n" )
							{
								$has_whitespace	= true;
							}
						}
        	
						if( $has_whitespace )
						{
							$files[] = $newpath;
						}
					}
				}
			}
		} catch ( Exception $e ) {}
		
		return $files;
	}
	
	/**
	 * Table and index checker basic stuff
	 *
	 * @param	void
	 * @return	array 	Table files
	 */
	public function _getDbTools()
	{
		//-----------------------------------------
		// First we get the SQL definitions for each app
		//-----------------------------------------
		
		$sql_table_files = array();
		
		foreach( $this->registry->getApplications() as $app )
		{
			$_file = IPSLib::getAppDir( $app['app_directory'] ) . '/setup/versions/install/sql/' . $app['app_directory'] . '_' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '_tables.php';
			
			if( is_file( $_file ) )
			{
				$sql_table_files[ $app['app_title'] ] = $_file;
			}
		}
		
		//-----------------------------------------
		// Get the library to run the checks
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'db_lib/' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '_tools.php', 'db_tools' );
		$this->dbTools = new $classToLoad();
		
		return $sql_table_files;
	}

	/**
	 * Check the database for missing indexes
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _indexCheck()
	{
		//-----------------------------------------
		// First we get the SQL definitions for each app
		//-----------------------------------------
		
		$sql_table_files = $this->_getDbTools();

		//-----------------------------------------
		// Now let's loop
		//-----------------------------------------
		
		$errors_array = array();
		$tables_array = array();
		$queriesRan = array();
		
		foreach( $sql_table_files as $app_title => $sql_file )
		{
			$TABLE = array();
			require_once( $sql_file );/*noLibHook*/
			
			if ( is_array( $TABLE ) AND count( $TABLE ) )
			{
				$output = $this->dbTools->dbIndexDiag( $TABLE, $this->request['fix'] );
			
				if( !$output )
				{
					continue;
				}
				
				if ( isset( $output['results']['ran'] ) )
				{
					$queriesRan = array_merge( $queriesRan, $output['results']['ran'] );
					unset( $output['results']['ran'] );
				}

				if( $output['error_count'] > 0 )
				{
					$errors_array[] = $app_title;
				}
			
				$tables_array[$app_title] = $output['results'];
			}
		}

		/* Output */
		$this->registry->output->html .= $this->html->indexChecker( $errors_array, $tables_array, $queriesRan );
    }
    
	/**
	 * Check the database for missing tables/columns
	 *
	 * @return	@e void		[Outputs to screen]
	 * @todo 	[Future] Functionality to show EXTRA columns/tables?
	 */
	public function _dbCheck()
	{
		//-----------------------------------------
		// First we get the SQL definitions for each app
		//-----------------------------------------
		
		$sql_table_files = $this->_getDbTools();
		
		//-----------------------------------------
		// Now let's loop
		//-----------------------------------------
		
		$errors_array = array();
		$tables_array = array();
		$queriesRan = array();
		
		foreach( $sql_table_files as $app_title => $sql_file )
		{
			$TABLE = array();
			require_once( $sql_file );/*noLibHook*/
			
			$output = $this->dbTools->dbTableDiag( $TABLE, $this->request['fix'] );

			if( !$output )
			{
				continue;
			}
			
			if ( isset( $output['results']['ran'] ) )
			{
				$queriesRan = array_merge( $queriesRan, $output['results']['ran'] );
				unset( $output['results']['ran'] );
			}
			
			if( $output['error_count'] > 0 )
			{
				$errors_array[] = $app_title;
			}
			
			$tables_array[$app_title] = $output['results'];
		}

		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->dbChecker( $errors_array, $tables_array, $queriesRan );
    }    
	
	/**
	 * Show the overview page
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _listFunctions()
	{
		//-----------------------------------------
		// PHP INFO?
		//-----------------------------------------
		
		if ( $this->request['phpinfo'] AND $this->request['phpinfo'] )
		{
			@ob_start();
			phpinfo();
			$parsed = @ob_get_contents();
			@ob_end_clean();
			
			preg_match( "#<body>(.*)</body>#is" , $parsed, $match1 );
			
			$php_body  = $match1[1];
			
			# PREVENT WRAP: Most cookies
			$php_body  = str_replace( "; " , ";<br />"   , $php_body );
			# PREVENT WRAP: Very long string cookies
			$php_body  = str_replace( "%3B", "<br />"    , $php_body );
			# PREVENT WRAP: Serialized array string cookies
			$php_body  = str_replace( ";i:", ";<br />i:" , $php_body );
			# PREVENT WRAP: LS_COLORS env
			$php_body  = str_replace( ":*.", "<br />:*." , $php_body );
			# PREVENT WRAP: PATH env
			$php_body  = str_replace( "bin:/", "bin<br />:/" , $php_body );
			# PREVENT WRAP: Cookie %2C split
			$php_body  = str_replace( "%2C", "%2C<br />" , $php_body );
			#PREVENT WRAP: Cookie , split
			$php_body  = preg_replace( "#,(\d+),#", ",<br />\\1," , $php_body );
		  
			$this->registry->output->html .= $this->html->phpInfo( $php_body );
			return;
		}
		
		//-----------------------------------------
		// Stats
		//-----------------------------------------

		$reg	= $this->DB->buildAndFetch( array(
													'select' 	=> 'COUNT(*) as reg'  ,
													'from' 		=> array( 'validating' => 'v' ),
													'where' 	=> 'v.lost_pass <> 1 AND m.member_group_id=' . $this->settings['auth_group'],
													'add_join'	=> array( array(
																				'from'	=> array( 'members' => 'm' ),
																 				'where'	=> 'm.member_id=v.member_id',
																 				'type'	=> 'left'
																 			) ) )	);

		if( $this->settings['ipb_bruteforce_attempts'] )
		{
			$lock	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as mems'  , 'from' => 'members', 'where' => 'failed_login_count >= ' . $this->settings['ipb_bruteforce_attempts'] ) );
		}

		$my_timestamp = time() - $this->settings['au_cutoff'] * 60;

		$online	 = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as sessions', 'from' => 'sessions', 'where' => 'running_time>' . $my_timestamp ) );

		$pending = $this->DB->buildAndFetch( array( 'select' => 'SUM(queued_topics) as topics, SUM(queued_posts) as posts', 'from' => 'forums' ) );

		$spammers = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
													 'from'   => 'members',
													 'where'  => "members_bitoptions = '1'" ) );

		$statsbox = $this->html->acp_stats_wrapper( array( 'topics'      => ipsRegistry::getClass('class_localization')->formatNumber($this->caches['stats']['total_topics']),
														   'replies'     => ipsRegistry::getClass('class_localization')->formatNumber($this->caches['stats']['total_replies']),
														   'topics_mod'	 => ipsRegistry::getClass('class_localization')->formatNumber($pending['topics']),
														   'posts_mod'	 => ipsRegistry::getClass('class_localization')->formatNumber($pending['posts']),
														   'members'     => ipsRegistry::getClass('class_localization')->formatNumber($this->caches['stats']['mem_count']),
														   'validate'    => ipsRegistry::getClass('class_localization')->formatNumber( $reg['reg'] ),
														   'spammer'     => ipsRegistry::getClass('class_localization')->formatNumber( $spammers['count'] ),
														   'locked'		 => ipsRegistry::getClass('class_localization')->formatNumber( $lock['mems'] ),
														   'sql_driver'  => strtoupper(SQL_DRIVER),
														   'sql_version' => $this->DB->true_version,
														   'php_version' => phpversion(),
														   'sessions'	 => ipsRegistry::getClass('class_localization')->formatNumber($online['sessions']),
														   'php_sapi'    => @php_sapi_name(),
														   'ipb_version' => ipsRegistry::$version,
														   'ipb_id'      => ipsRegistry::$vn_full ) );
		
		//-----------------------------------------
		// Server stuff
		//-----------------------------------------
        
		$this->DB->getSqlVersion();

		$sql_version		= strtoupper($this->settings['sql_driver']) . " " . $this->DB->true_version;
		
		$php_version		= phpversion() . " (" . @php_sapi_name() . ")  ( <a href='{$this->settings['base_url']}{$this->form_code}&amp;phpinfo=1'>{$this->lang->words['d_aphpinfo']}</a> )";
		$server_software	= @php_uname();
		
		$load_limit			= IPSDebug::getServerLoad();
        $server_load_found	= 0;
        $total_memory		= "--";
        $avail_memory		= "--";
        $_disabled			= @ini_get('disable_functions') ? explode( ',', @ini_get('disable_functions') ) : array();
        $_shellExecAvail	= in_array( 'shell_exec', $_disabled ) ? false : true;

		//-----------------------------------------
		// Check memory
		//-----------------------------------------

		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
		{
			$mem = $_shellExecAvail ? @shell_exec('systeminfo') : null;
			
			if( $mem )
			{
				$server_reply = explode( "\n", str_replace( "\r", "", $mem ) );
				
				if( count($server_reply) )
				{
					foreach( $server_reply as $info )
					{
						if( strstr( $info, $this->lang->words['d_atotal'] ) )
						{
							$total_memory =  trim( str_replace( ":", "", strrchr( $info, ":" ) ) );
						}
						
						if( strstr( $info, $this->lang->words['d_aavail']) )
						{
							$avail_memory =  trim( str_replace( ":", "", strrchr( $info, ":" ) ) );
						}
					}
				}
			}
		}
		else
		{
			$mem			= $_shellExecAvail ? @shell_exec("free -m") : null;
			
			if( $mem )
			{
				$server_reply	= explode( "\n", str_replace( "\r", "", $mem ) );
				$mem			= array_slice( $server_reply, 1, 1 );
				$mem			= preg_split( "#\s+#", $mem[0] );
	
				$total_memory	= ( $mem[1] ) ? $mem[1] . ' MB' : '--';
				$avail_memory	= ( $mem[3] ) ? $mem[3] . ' MB' : '--';
			}
			else
			{
				$total_memory	= '--';
				$avail_memory	= '--';
			}
		}
		
		$disabled_functions	= ( is_array($_disabled) && count($_disabled) ) ? implode( ', ', $_disabled ) : $this->lang->words['d_anoinfo'];
		$extensions			= get_loaded_extensions();
		$extensions			= array_combine( $extensions, $extensions );
		sort( $extensions, SORT_STRING );
		
		//-----------------------------------------
		// Set variables and pass to skin
		//-----------------------------------------
		
		$data = array(
						'version'			=> 'v' . IPB_VERSION,
						'version_full'		=> IPB_LONG_VERSION,
						'version_sql'		=> $sql_version,
						'driver_type'		=> strtoupper($this->settings['sql_driver']),
						'version_php'		=> $php_version,
						'disabled'			=> $disabled_functions,
						'extensions'		=> str_replace( "suhosin", "<strong>suhosin</strong>", implode( ", ", $extensions ) ),
						'safe_mode'			=> SAFE_MODE_ON == 1 ? "<span style='color:red;font-weight:bold;'>{$this->lang->words['d_aon']}</span>" : "<span style='color:green;font-weight:bold;'>{$this->lang->words['d_aoff']}</span>",
						'server'			=> $server_software,
						'load'				=> $load_limit,
						'total_memory'		=> $total_memory,
						'avail_memory'		=> $avail_memory,
					);

		if( $_shellExecAvail )
		{
			if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
			{
				$tasks = @shell_exec( "tasklist" );
				$tasks = str_replace( " ", "&nbsp;", $tasks );
			}
			else if( strtolower( PHP_OS ) == 'darwin' )
			{
				$tasks = @shell_exec( "top -l 1" );
				$tasks = str_replace( " ", "&nbsp;", $tasks );
			}
			else
			{
				$tasks = @shell_exec( "top -b -n 1" );
				$tasks = str_replace( " ", "&nbsp;", $tasks );
			}
		}
		else
		{
			$tasks	= '';
		}
		
		if( !$tasks )
		{
			$tasks = $this->lang->words['d_aunable'];
		}
		else
		{
			$tasks = "<pre>".$tasks."</pre>";
		}
		
		$data['tasks']	= $tasks;
		
		$this->registry->output->html .= $this->html->diagnosticsOverview( $data, $statsbox );
	}
	
	/**
	 * Show the email tester page
	 *
	 * @param	sring		Error message
	 * @return	@e void		[Outputs to screen]
	 */
	public function _emailCheckerForm( $error='' )
	{
		$this->registry->output->html .= $this->html->emailChecker( $error );
	}
	
	/**
	 * Send a test email
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _emailCheckerGo()
	{
		//-----------------------------------------
		// Send it
		//-----------------------------------------
	
		IPSText::getTextClass('email')->to = $this->request['to'];
		IPSText::getTextClass('email')->from = $this->request['from'];
		IPSText::getTextClass('email')->subject	= $this->request['subject'];
		IPSText::getTextClass('email')->message	= $this->request['message'];
		IPSText::getTextClass('email')->sendMail();
		
		//-----------------------------------------
		// Check the error log
		//-----------------------------------------
		
		$error = '';
		
		$lastLog = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'mail_error_logs', 'order' => 'mlog_date DESC', 'limit' => 1 ) );
		if ( $lastLog['mlog_date'] >= ( time() - 2 ) and $lastLog['mlog_subject'] == $this->request['subject'] )
		{
			$error = $lastLog['mlog_msg'];
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['email_tester_ok'];
		}
		
		//-----------------------------------------
		// Show
		//-----------------------------------------
		
		$this->_emailCheckerForm( $error );
	}
}