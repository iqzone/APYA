<?php
/**
 *
 * @class	version_upgrade
 * @brief	3.2.0 Alpha 1 Upgrade Logic
 *
 */
class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
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
			case 'logs':
				$this->convertLogs();
				break;
			case 'frules':
				$this->fixForumRules();
			break;
			case 'ban':
				$this->banUpdate();
			break;
			case 'posts':
				$this->updatePostsTable();
			break;
			case 'groups':
				$this->fixGroups();
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
		$path		= IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_33000/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix		= $this->registry->dbFunctions()->getPrefix();
		$sourceFile	= '';
		$options	= IPSSetUp::getSavedData('custom_options');
		
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
		
			$this->registry->output->addMessage("{$cnt} queries ran... ");
		}
		
		/* Next Page */
		if ( $id < 2 )
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
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '33000', $id );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
	}	
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function convertLogs() 
	{
		/* Init */
		$pergo		= 100;
		$start		= intval( $this->request['st'] );
						
		/* Get them */
		$total = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'warn_logs', 'where' => "wlog_type<>'pos'" ) );
		ipsRegistry::DB()->build( array(
			'select'	=> '*',
			'from'		=> 'warn_logs',
			'where'		=> "wlog_type<>'pos'",
			'order'		=> 'wlog_id ASC',
			'limit'		=> array( $start, $pergo )
			) );
		$e = ipsRegistry::DB()->execute();
		
		/* Are you leeeeeeeeaving? */
		if ( !ipsRegistry::DB()->getTotalRows( $e ) )
		{
			// We probably should delete the old warn_logs table here, but I'm not going to in case any bugs come up with this converter
			$this->request['workact'] = 'frules';
			return true;
		}
		
		/* Nope, let's do some converting! */
		while ( $row = ipsRegistry::DB()->fetch( $e ) )
		{
			//-----------------------------------------
			// Get the PM / EMail we sent to the member
			// This will  now be our "note for member"
			//-----------------------------------------
		
			preg_match( "#<content>(.+?)</content>#is", $row['wlog_contact_content'], $content );
			$noteForMember = $content[1];
			
			//-----------------------------------------
			// Work out what punishment we gave
			//-----------------------------------------
			
			$noteForMods	= '';
			$mq				= 0;
			$mqUnit			= '';
			$rpa			= 0;
			$rpaUnit		= '';
			$suspend		= 0;
			$suspendUnit	= '';
					
			$unserialized = unserialize( $row['wlog_notes'] );
			
			/* Old style */
			if ( $unserialized == FALSE )
			{
				preg_match( "#<content>(.+?)</content>#is", $row['wlog_notes'], $content );
				$noteForMods = $content[1];
				
				foreach ( array( 'mod' => 'mq', 'post' => 'rpa', 'susp' => 'suspend' ) as $k => $v )
				{
					$data = array();
					preg_match( "#<{$k}>(.+?)</{$k}>#is", $row['wlog_notes'], $data );
					if ( $data[1] )
					{
						$data = explode( ',', $data[1] );
												
						if ( $data[2] == 1 || $data[0] > 999999 )
						{
							$$v = -1;
						}
						else
						{
							$$v = $data[0];
							$unitVar = "{$v}Unit";
							$$unitVar = $data[1];
						}
					}
				}
				
			}
			
			/* New style */
			else
			{
				$noteForMods	= $unserialized['content'];
				
				foreach ( array( 'mod' => 'mq', 'post' => 'rpa', 'susp' => 'suspend' ) as $k => $v )
				{
					if ( $unserialized[ $k . '_indef' ] == 1 || $unserialized[ $k ] > 999999 )
					{
						$$v = -1;
					}
					else
					{
						$$v = $unserialized[ $k ];
						$unitVar = "{$v}Unit";
						$$unitVar = $unserialized[ $k . '_unit' ];
					}
				}
			}
			
			//-----------------------------------------
			// Save
			//-----------------------------------------
		
			ipsRegistry::DB()->insert( 'members_warn_logs', array(
				'wl_member'			=> $row['wlog_mid'],
				'wl_moderator'		=> $row['wlog_addedby'],
				'wl_date'			=> $row['wlog_date'],
				'wl_reason'			=> 0,
				'wl_points'			=> ( $row['wlog_type'] == 'neg' ) ? 1 : 0,
				'wl_note_member'	=> $noteForMember,
				'wl_note_mods'		=> $noteForMods,
				'wl_mq'				=> intval( $mq ),
				'wl_mq_unit'		=> $mqUnit,
				'wl_rpa'			=> intval( $rpa ),
				'wl_rpa_unit'		=> $rpaUnit,
				'wl_suspend'		=> intval( $suspend ),
				'wl_suspend_unit'	=> $suspendUnit,
				'wl_ban_group'		=> 0,
				'wl_expire'			=> 0,
				'wl_expire_unit'	=> '',
				'wl_acknowledged'	=> 1,
				'wl_content_app'	=> '',
				'wl_content_id1'	=> '',
				'wl_content_id2'	=> ''
				) );
		}
		
		/* Next! */
		$percent = round( ( 100 / $total['count'] ) * $start );
		$this->request['st'] = $start + $pergo;
		$this->registry->output->addMessage( "Upgrading records... ( {$percent}% complete )" );
		
		$this->request['workact'] = 'logs';
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function fixForumRules() 
	{
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'forums',
								 'where'  => "rules_text LIKE '%&%'" ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$this->DB->update( 'forums', array( 'rules_text' => IPSText::UNhtmlspecialchars( $row['rules_text'] ) ), 'id=' . $row['id'] );
		}
		
		$this->registry->output->addMessage( "Forum rules updated" );
		
		$this->request['workact'] = 'groups';
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function fixGroups() 
	{
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'groups' ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$row['gbw_view_last_info']    = 1;
			$row['gbw_view_online_lists'] = 1;
			$row['gbw_hide_leaders_page'] = 0;
			
			$g_bitoptions = IPSBWOPtions::freeze( $row, 'groups', 'global' );
			
			$this->DB->update( 'groups', array( 'g_bitoptions' => $g_bitoptions ), 'g_id=' . $row['g_id'] );
		}
		
		$this->registry->output->addMessage( "Groups updated" );
		
		$this->request['workact'] = 'posts';
	}
	
	/**
	 * Fix posts table
	 * 
	 * @param	int
	 * @return	@e void
	 */
	public function updatePostsTable()
	{
		$doManual	= $options['core'][33000]['manualPostsTableQuery'];
		$prefix		= $this->registry->dbFunctions()->getPrefix();
		$output     = '';
		
		if ( $doManual && ! $this->DB->checkForField( 'post_field_int', 'posts' ) )
		{
			foreach( array( 'ALTER TABLE posts ADD post_field_int INT(10) DEFAULT 0',
							'ALTER TABLE posts ADD post_field_t1 TEXT NULL DEFAULT NULL',
							'ALTER TABLE posts ADD post_field_t2 TEXT NULL DEFAULT NULL' ) as $query )
			{
				$query = trim( $query );
				
				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
							
				/* Ensure the last character is a semi-colon */
				if ( substr( $query, -1 ) != ';' )
				{
					$query .= ';';
				}
				
				$output .= $query . "\n\n";
			}
		}
		else
		{
			if ( ! $this->DB->checkForField( 'post_field_int', 'posts' ) )
			{
				$this->DB->addField( 'posts', 'post_field_int', 'INT(10)', '0' );
			}
			
			if ( !$this->DB->checkForField( 'post_field_t1', 'posts' ) )
			{
				$this->DB->addField( 'posts', 'post_field_t1', 'TEXT' );
			}
			
			if ( !$this->DB->checkForField( 'post_field_t2', 'posts' ) )
			{
				$this->DB->addField( 'posts', 'post_field_t2', 'TEXT' );
			}
		}
		
		if ( $output )
		{
			/* Create source file */
			if ( $this->registry->dbFunctions()->getDriverType() == 'mysql' )
			{
				$sourceFile = IPSSetUp::createSqlSourceFile( $output, '33000', 'posts' );
			}
			
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output, $sourceFile );
		}
		
		$this->registry->output->addMessage( "Posts table updated" );
		$this->request['workact'] = 'ban';
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function banUpdate() 
	{
		$options	= IPSSetUp::getSavedData('custom_options');
		$flagBanned	= $options['core'][33000]['flagBanned'];
		
		if ( $flagBanned )
		{
			if ( ! empty( ipsRegistry::$settings['banned_group'] ) )
			{
				$this->DB->update( 'members', array( 'member_banned' => 1 ), "member_group_id=" . intval( ipsRegistry::$settings['banned_group'] ) );
			}
		}
			
		$this->registry->output->addMessage( "Banned members updated" );
		
		$this->request['workact'] = '';
	}
	
	
}