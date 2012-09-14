<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'sql':
			case 'sql1':
			default:
				$this->upgradeSql(1);
				break;
			case 'sql2':
				$this->upgradeSql(2);
				break;
			case 'sql3':
				$this->upgradeSql(3);
				break;
			case 'sql4':
				$this->upgradeSql(4);
				break;
			case 'sql5':
				$this->upgradeSql(5);
				break;
			case 'sql6':
				$this->upgradeSql(6);
				break;
				
			case 'logs':
			case 'logs1':
				$this->upgradeLogs(1);
				break;
			case 'logs2':
				$this->upgradeLogs(2);
				break;
			case 'logs3':
				$this->upgradeLogs(3);
				break;
			case 'logs4':
				$this->upgradeLogs(4);
				break;
			case 'logs5':
				$this->upgradeLogs(5);
				break;
			case 'logs6':
				$this->upgradeLogs(6);
				break;
			case 'logs7':
				$this->upgradeLogs(7);
				break;
			case 'logs8':
				$this->upgradeLogs(8);
				break;
			case 'logs9':
				$this->upgradeLogs(9);
				break;
			case 'logs10':
				$this->upgradeLogs(10);
				break;
				
			case 'forums':
				$this->fixArchiveForums();
				break;

			case 'loginmethods':
				$this->fixLoginMethods();
				break;

			case 'oldhooks':
				$this->removeOldHooks();
				break;

			case 'setdefaultskin':
				$this->setDefaultSkin();
				break;
				
			case 'hooks':
				$this->convertHooks();
				break;

			case 'photos':
				$this->convertPhotos();
				break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Run SQL files
	 * 
	 * @param	int
	 * @return	@e void
	 */
	public function upgradeSql( $id=1 )
	{
		$cnt		= 0;
		$file		= '_updates_'.$id.'.php';
		$output		= "";
		$path		= IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_32000/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix		= $this->registry->dbFunctions()->getPrefix();
		$sourceFile	= '';
		$options	= IPSSetUp::getSavedData('custom_options');
		$postTable	= $options['core'][32000]['manualPostsTableQuery'];
		
		if ( is_file( $path ) )
		{
			$SQL		= array();
			
			require( $path );/*noLibHook*/
			
			/* Set DB driver to return any errors */
			$this->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';
				
				$query  = str_replace( "<%time%>", time(), $query );
				
				if ( $this->settings['mysql_tbl_type'] )
				{
					if ( preg_match( "/^create table(.+?)/i", $query ) )
					{
						$query = preg_replace( "/^(.+?)\);$/is", "\\1) ENGINE={$this->settings['mysql_tbl_type']};", $query );
					}
				}
				
				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
				
				if( IPSSetUp::getSavedData('man') OR ( $id == 6 AND $postTable ) )
				{
					$query = trim( $query );
					
					/* Ensure the last character is a semi-colon */
					if ( substr( $query, -1 ) != ';' )
					{
						$query .= ';';
					}
					
					$output .= $query . "\n\n";
				}
				else
				{			
					$this->DB->query( $query );
					
					if ( $this->DB->error )
					{
						$this->registry->output->addError( "<br />" . $query . "<br />" . $this->DB->error );
					}
					else
					{
						$cnt++;
					}
				}
			}
		
			$this->registry->output->addMessage("{$cnt} queries ran....");
		}
		
		/* Next Page */
		if ( $id < 6 )
		{
			$nextid = $id + 1;
			$this->request['workact'] = 'sql' . $nextid;	
		}
		else
		{
			$this->request['workact'] = 'logs';	
		}
		
		if ( $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '32000', $id );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
	}	
	
	/**
	 * Upgrade log file tables
	 * 
	 * @param	int
	 * @return	@e void
	 */
	public function upgradeLogs( $id=1 )
	{
		/* Verify posts alter table query has run */
		if( $id == 1 )
		{
			if( !$this->DB->checkForField( 'post_bwoptions', 'posts' ) )
			{
				$this->_output	= ' ';
				$this->registry->output->addError( "You must first run the query to alter the posts table before you can proceed.  Once you have run this query, you can hit refresh to continue." );
				return;
			}
		}

		$cnt		= 0;
		$file		= '_updates_logs_'.$id.'.php';
		$output		= "";
		$path		= IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_32000/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix		= $this->registry->dbFunctions()->getPrefix();
		
		if ( is_file( $path ) )
		{
			$SQL		= array();
			$TABLE		= '';
			
			require( $path );/*noLibHook*/
			
			/* Set DB driver to return any errors */
			$this->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';

				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
				
				/* Chose to prune and run? */
				if( $this->request['pruneAndRun'] )
				{
					$this->DB->delete( $TABLE );
					
					$man	= false;
				}
				else
				{
					/* Show alter table / prune option? */
					$man	= IPSSetUp::getSavedData('man');
					
					if( $TABLE )
					{
						$count	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as logs', 'from' => $TABLE ) );
						
						if( $count['logs'] > 100000 )
						{
							$man	= true;
						}
					}
				}

				/* Show option to run manually or prune ? */
				if( $man )
				{
					$query = trim( $query );
					
					/* Ensure the last character is a semi-colon */
					if ( substr( $query, -1 ) != ';' )
					{
						$query .= ';';
					}
					
					$output .= $query . "\n\n";
				}
				else
				{			
					$this->DB->query( $query );
					
					if ( $this->DB->error )
					{
						$this->registry->output->addError( "<br />" . $query . "<br />" . $this->DB->error );
					}
					else
					{
						$cnt++;
					}
				}
			}
		
			$this->registry->output->addMessage("{$cnt} queries ran....");
		}
		
		/* Next Page */
		if ( $id < 10 )
		{
			$nextid = $id + 1;
			$this->request['workact'] = 'logs' . $nextid;	
		}
		else
		{
			$this->request['workact'] = 'forums';	
		}
		
		if ( $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries_logs( $output, $id, $TABLE );
		}
	}
	
	/**
	 * Convert archive forums to read-only forums
	 * 
	 * @return	@e void
	 */
	public function fixArchiveForums()
	{
		/* Update array for archive forums */
		$_total		= 0;
		$_update	= array(
							'perm_3'	=> '',
							'perm_4'	=> '',
							'perm_5'	=> '',
							);

		/* Get 'archive' forums */
		$this->DB->build( array( 'select' => '*', 'from' => 'forums', 'where' => 'status=0' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$this->DB->update( 'permission_index', $_update, "app='forums' AND perm_type='forum' AND perm_type_id={$r['id']}" );
			
			$_total++;
		}
		
		/* Now drop the status field in forums table */
		$this->DB->dropField( 'forums', 'status' );

		/* Message */
		$this->registry->output->addMessage("{$_total} forums with 'archive' status converted to read-only forums....");
		
		/* Next Page */
		$this->request['workact'] = 'loginmethods';
	}
	
	/**
	 * Pull login method conf to DB
	 * 
	 * @return	@e void
	 */
	public function fixLoginMethods()
	{
		/* Init */
		$_total	= 0;
		
		if( !defined('IPS_PATH_CUSTOM_LOGIN') )
		{
			define( 'IPS_PATH_CUSTOM_LOGIN' , IPS_ROOT_PATH . 'sources/loginauth' );
		}
		
		/* Try to loop over all login methods to grab current configs */
		try
		{
			foreach( new DirectoryIterator( IPS_PATH_CUSTOM_LOGIN ) as $file )
			{
				if ( $file->isDir() )
				{
					if( is_file( $file->getPathname() . '/conf.php' ) )
					{
						$LOGIN_CONF = array();
						
						include( $file->getPathname() . '/conf.php' );/*noLibHook*/

						if( is_array($LOGIN_CONF) AND count($LOGIN_CONF) )
						{
							$this->DB->update( 'login_methods', array( 'login_custom_config' => @serialize($LOGIN_CONF) ), "login_folder_name='" . $this->DB->addSlashes( $file->getFilename() ) . "'" );
							
							$_total++;
						}
					}
				}
			}
		}
		catch( Exception $e )
		{
		}

		/* Message */
		$this->registry->output->addMessage("{$_total} login method configs imported....");
		
		/* Next Page */
		$this->request['workact'] = 'oldhooks';
	}
	
	/**
	 * Remove old hooks
	 * 
	 * @return	@e void
	 */
	public function removeOldHooks()
	{
		/* Hooks to remove */
		$hooks		= array( 'watched_content', 'todays_top_posters', 'unread_notifications' );
		$_hookIds	= array();
		$_total		= 0;

		/* Get hook records */
		$this->DB->build( array( 'select' => 'hook_id', 'from' => 'core_hooks', 'where' => "hook_key IN('" . implode( "','", $hooks ) . "')" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_hookIds[]	= $r['hook_id'];
		}
		
		/* Remove associated files */
		if( count($_hookIds) )
		{
			$this->DB->build( array( 'select' => 'hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id IN(' . implode( ',', $_hookIds ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
			}
			
			/* Remove hook records */
			$this->DB->delete( 'core_hooks_files', 'hook_hook_id IN(' . implode( ',', $_hookIds ) . ')' );
			$this->DB->delete( 'core_hooks', 'hook_id IN(' . implode( ',', $_hookIds ) . ')' );
			
			$_total++;
		}

		/* Message */
		$this->registry->output->addMessage("{$_total} outdated hook(s) uninstalled....");
		
		/* Next Page */
		$this->request['workact'] = 'setdefaultskin';
	}

	/**
	 * Restore IPB default skin as default
	 * 
	 * @return	@e void
	 */
	public function setDefaultSkin()
	{
		/* Get skin functions library */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		
		$skinFunctions = new skinCaching( $this->registry );
		
		/* Update members to use default skin */
		$this->DB->update( 'members', array( 'skin' => 0 ) );

		/* Update forums to use default skin */
		$this->DB->update( 'forums', array( 'skin_id' => 0 ) );
		
		/* Reset any default skin(s) */
		$this->DB->update( 'skin_collections', array( 'set_is_default' => 0 ) );
		
		/* Set IPB default skin as default */
		$this->DB->update( 'skin_collections', array( 'set_is_default' => 1 ), "set_key='default'" );
		
		/* Verify we have a skin set as default */
		$_check	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'skin_collections', 'where' => 'set_is_default=1' ) );
		
		if( $_check['total'] < 1 )
		{
			$this->DB->build( array( 'update' => 'skin_collections', 'set' => 'set_is_default=1', 'where' => "set_output_format='html'", 'limit' => array( 0, 1 ) ) );
			$this->DB->execute();
		}
		
		/* Check for any modifications in IPB default skin */
		$_master			= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_collections', 'where' => "set_key='default'" ) );
		$_master['set_id']	= intval($_master['set_id']);
		$_modifiedTemplates	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'skin_templates', 'where' => "template_set_id={$_master['set_id']} AND template_removable=1 OR template_user_edited=1" ) );
		$_modifiedCSS		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'skin_css', 'where' => "css_set_id={$_master['set_id']} AND css_master_key=''" ) );
		
		if( $_modifiedTemplates['total'] OR $_modifiedCSS['total'] )
		{
			/* Create a new skin with IPB default as parent */
			$curPos = $skinFunctions->fetchHighestSetPosition();
			
			$_master['set_key']			= 'default_modified';
			$_master['set_parent_id']	= $_master['set_id'];
			$_master['set_is_default']	= 0;
			$_master['set_name']		= $_master['set_name'] . ' (Pre 3.2)';
			$_master['set_order']		= (int)$curPos++;
			unset($_master['set_id']);
			
			$this->DB->insert( 'skin_collections', $_master );
			$set_id = $this->DB->getInsertId();
			
			$this->DB->update( 'skin_templates', array( 'template_set_id' => $set_id ), "template_set_id={$_master['set_parent_id']} AND template_removable=1 OR template_user_edited=1" );
			$this->DB->update( 'skin_css', array( 'css_set_id' => $set_id ), "css_set_id={$_master['set_parent_id']} AND css_master_key=''" );
			
			$skinFunctions->rebuildSkinSetsCache();
			$skinFunctions->rebuildTreeInformation( $set_id );
			//$skinFunctions->flushSkinData();
			
			$skinFunctions->rebuildCSS( $set_id );
			$skinFunctions->rebuildPHPTemplates( $set_id );
			$skinFunctions->rebuildReplacementsCache( $set_id );
		}

		/* Message */
		$this->registry->output->addMessage("Default skin restored....");
		
		/* Next Page */
		$this->request['workact'] = 'hooks';
	}
	
	/**
	 * Convert stored hook data
	 * 
	 * @return	@e void
	 */
	public function convertHooks()
	{
		$_total	= 0;
		
		/* Grab all hooks */
		$this->DB->build( array( 'select' => '*', 'from' => 'core_hooks' ) );
		$outer	= $this->DB->execute();
		
		while( $hook = $this->DB->fetch($outer) )
		{
			$_extraData	= unserialize($hook['hook_extra_data']);
			
			/* Fix setting groups */
			if( is_array($_extraData['settingGroups']) AND count($_extraData['settingGroups']) )
			{
				$_settingGroups	= array();
				
				foreach( $_extraData['settingGroups'] as $_group )
				{
					if( intval($_group) == $_group )
					{
						$_settingGroups[ $_group ]	= intval($_group);
					}
				}
				
				if( count($_settingGroups) )
				{
					$_extraData['settingGroups']	= array();

					$this->DB->build( array( 'select' => 'conf_title_id, conf_title_keyword', 'from' => 'core_sys_settings_titles' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						if( in_array( $r['conf_title_id'], $_settingGroups ) )
						{
							$_extraData['settingGroups'][]	= $r['conf_title_keyword'];
						}
					}
				}
			}
			
			/* Fix settings */
			if( is_array($_extraData['settings']) AND count($_extraData['settings']) )
			{
				$_settings	= array();
				
				foreach( $_extraData['settings'] as $_id )
				{
					if( intval($_id) == $_id )
					{
						$_settings[ $_id ]	= intval($_id);
					}
				}
				
				if( count($_settings) )
				{
					$_extraData['settings']	= array();

					$this->DB->build( array( 'select' => 'conf_id, conf_key', 'from' => 'core_sys_conf_settings' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						if( in_array( $r['conf_id'], $_settings ) )
						{
							$_extraData['settings'][]	= $r['conf_key'];
						}
					}
				}
			}
			
			/* Fix tasks */
			if( is_array($_extraData['tasks']) AND count($_extraData['tasks']) )
			{
				$_tasks	= array();
				
				foreach( $_extraData['tasks'] as $_id )
				{
					if( intval($_id) == $_id )
					{
						$_tasks[ $_id ]	= intval($_id);
					}
				}
				
				if( count($_tasks) )
				{
					$_extraData['tasks'] = array();
					
					$this->DB->build( array( 'select' => 'task_id, task_key', 'from' => 'task_manager' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						if( in_array( $r['task_id'], $_tasks ) )
						{
							$_extraData['tasks'][]	= $r['task_key'];
						}
					}
				}
			}
			
			/* Fix help files */
			if( is_array($_extraData['help']) AND count($_extraData['help']) )
			{
				$_help = array();
				
				foreach( $_extraData['help'] as $_id )
				{
					if( intval($_id) == $_id )
					{
						$_help[ $_id ]	= intval($_id);
					}
				}
				
				if( count($_help) )
				{
					$_extraData['help']	= array();
					
					$this->DB->build( array( 'select' => 'id, title', 'from' => 'faq' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						if( in_array( $r['id'], $_help ) )
						{
							$_extraData['help'][]	= $r['title'];
						}
					}
				}
			}
			
			/* Fix modules */
			if( is_array($_extraData['modules']) AND count($_extraData['modules']) )
			{
				$_modules	= array();
				
				foreach( $_extraData['modules'] as $_id )
				{
					if( intval($_id) == $_id )
					{
						$_modules[ $_id ]	= intval($_id);
					}
				}
				
				if( count($_modules) )
				{
					$_extraData['modules']	= array();

					$this->DB->build( array( 'select' => 'sys_module_id, sys_module_admin, sys_module_application, sys_module_key', 'from' => 'core_sys_module' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						if( in_array( $r['sys_module_id'], $_modules ) )
						{
							$_extraData['modules'][]	= ( $r['sys_module_admin'] ? 'admin' : 'public' ) . '-' . $r['sys_module_application'] . '-' . $r['sys_module_key'];
						}
					}
				}
			}

			/* Fix templates */
			if( is_array($_extraData['templates']) AND count($_extraData['templates']) )
			{
				$_newTemplates	= array();

				foreach( $_extraData['templates'] as $file => $templates )
				{
					foreach( $templates as $template )
					{
						$_newTemplates[ $file ][ $template ]	= $template;
					}
				}
				
				$_extraData['templates']	= $_newTemplates;
			}

			/* Fix CSS */
			if( is_array($_extraData['css']) AND count($_extraData['css']) )
			{
				$_css	= array();
				
				foreach( $_extraData['css'] as $_id )
				{
					if( intval($_id) == $_id )
					{
						$_css[ $_id ]	= intval($_id);
					}
				}
				
				if( count($_css) )
				{
					$_extraData['css']	= array();

					$this->DB->build( array( 'select' => 'css_group', 'from' => 'skin_css', 'where' => "css_id IN(" . implode( ',', $_css ) . ")" ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$_extraData['css'][ $r['css_group'] ]	= $r['css_group'];
					}
				}
			}
			
			$this->DB->update( 'core_hooks', array( 'hook_extra_data' => serialize( $_extraData ) ), 'hook_id=' . $hook['hook_id'] );
			
			$_total++;
		}

		/* Message */
		$this->registry->output->addMessage("{$_total} hooks updated....");
		
		/* Next Page */
		$this->request['workact'] = 'photos';
	}

	/**
	 * Convert and rebuild photos
	 * 
	 * @param	int
	 * @return	@e void
	 */
	public function convertPhotos()
	{
		/* Is this the first cycle? */
		if( !$this->request['from'] )
		{
			$this->_output	= <<<EOF
<h2>Profile Photo Conversion</h2>
<div class='message unspecified'>
	IP.Board 3.2 supports one image to identify each user.  You can choose to retain your existing avatars or your existing profile photos for this image.
	<br />
	<select name='from'>
		<option value='avatars'>Use avatars</option>
		<option value='photos'>Use profile photos</option>
	</select>
	<br />
	The image you save will need to be rebuilt.  You can let the upgrader handle this now, or you can skip this step and complete it later from the ACP.
	<br /><br />
	<input type='radio' name='skip' value='0' checked='checked' /> Rebuild thumbnails now<br />
	<input type='radio' name='skip' value='1' /> Skip this step and rebuild thumbnails from the ACP later
EOF;

			return;
		}

		/* Our options */
		$convertFrom	= $this->request['from'] == 'avatars' ? 'avatars' : 'photos';
		$skipRebuild	= intval($this->request['skip']);

		/* Init */
		$st		= intval($this->request['st']);
		$did	= 0;
		$each	= 200;
		
		require_once( IPS_ROOT_PATH . 'sources/classes/member/photo.php' );/*noLibHook*/
		$photo	= new classes_member_photo( $this->registry );

		/* Loop over members */
		$this->DB->build( array( 'select' => '*', 'from' => 'profile_portal', 'order' => 'pp_member_id ASC', 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$did++;

			$update	= array();
			
			if( $r['fb_photo'] )
			{
				$update['pp_photo_type']	= 'facebook';
			}
			else if( $r['tc_photo'] )
			{
				$update['pp_photo_type']	= 'twitter';
			}
			else
			{
				if( $convertFrom == 'avatars' )
				{
					if( $r['avatar_type'] == 'upload' AND $r['avatar_location'] )
					{
						$update['pp_photo_type']	= 'custom';
						$update['pp_main_photo']	= $r['avatar_location'];
						$_dims						= @getimagesize( $this->settings['upload_dir'] . '/' . $r['avatar_location'] );
						$update['pp_main_width']	= $_dims[0] ? $_dims[0] : 1;
						$update['pp_main_height']	= $_dims[1] ? $_dims[1] : 1;
					}
					else if( $r['avatar_type'] == 'gravatar' )
					{
						$update['pp_photo_type']	= 'gravatar';
						$update['pp_gravatar']		= $r['avatar_location'];
						
						$md5Gravatar = md5( $update['pp_gravatar'] );
						
						$_url	= "http://www.gravatar.com";
						
						if( $this->registry->output->isHTTPS )
						{
							$_url	= "https://secure.gravatar.com";
						}
						
						$update['pp_main_photo']	= $_url . "/avatar/" .$md5Gravatar . "?s=100";
						$update['pp_main_width']	= 100;
						$update['pp_main_height']	= 100;
						$update['pp_thumb_photo']	= $_url . "/avatar/" .$md5Gravatar . "?s=100";
						$update['pp_thumb_width']	= 100;
						$update['pp_thumb_height']	= 100;
					}
				}
				else
				{
					if( $r['pp_main_photo'] )
					{
						$update['pp_photo_type']	= 'custom';
						$update['pp_main_photo']	= $r['pp_main_photo'];
					}
				}
			}
			
			if( !$skipRebuild AND $update['pp_photo_type'] == 'custom' )
			{
				$info	= $photo->buildSizedPhotos( str_replace( 'upload:', '', $update['pp_main_photo'] ), $r['pp_member_id'], true );

				$update['pp_main_width']	= intval( $info['final_width'] );
				$update['pp_main_height']	= intval( $info['final_height'] );
				$update['pp_thumb_photo']	= $info['t_final_location'] ? $info['t_final_location'] : $info['final_location'];
				$update['pp_thumb_width']	= intval( $info['t_final_width'] );
				$update['pp_thumb_height']	= intval( $info['t_final_height'] );
			}
			
			if( count($update) )
			{
				$this->DB->update( 'profile_portal', $update, 'pp_member_id=' . $r['pp_member_id'] );
			}
		}

		/* Show message and redirect */
		if( $did > 0 )
		{
			$this->request['st']		= ( $st + $did );
			$this->request['workact']	= 'photos';
			
			$message					= $skipRebuild ? "Up to {$this->request['st']} profile photos converted ..." : "Up to {$this->request['st']} profile photos converted and rebuilt...";
			$this->registry->output->addMessage( $message );

			/* Yes, we are being sneaky here.  Shhhhh */
			$this->request['st']		= $this->request['st'] . '&amp;from=' . $this->request['from'] . '&amp;skip=' . $skipRebuild;
		}
		else
		{
			$this->request['st']		= 0;
			$this->request['workact']	= '';
			
			$this->registry->output->addMessage( "All profile photos converted..." );
		}

		/* Next Page */
		return;
	}
}