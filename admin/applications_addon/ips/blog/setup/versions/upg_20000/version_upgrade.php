<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Upgrade Class
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 * 
 * @version		$Rev: 4 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @link		http://www.invisionpower.com
 * @package		IP.Board
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
	 * @access	protected
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
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
			default:
				$this->upgradeBlog();
			break;
		}
		
		return true;	
	}
	
	/**
	 * Main work of upgrading blog
	 *
	 * @return	@e void
	 */
	public function upgradeBlog()
	{
		//-----------------------------------------
		// Got our XML?
		//-----------------------------------------
		
		if ( is_file( IPS_ROOT_PATH . 'applications_addon/ips/blog/xml/information.xml' ) )
		{
			//-----------------------------------------
			// Not already "installed"?
			//-----------------------------------------
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'app_directory', 'from' => 'core_applications', 'where' => "app_directory='blog'" ) );
			
			if( !$check['app_directory'] )
			{
				//-----------------------------------------
				// Get position
				//-----------------------------------------
				
				$max	= $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as max', 'from' => 'core_applications' ) );
				
				$_num	= $max['max'] + 1;
				
				//-----------------------------------------
				// Get XML data
				//-----------------------------------------
				
				$data	= IPSSetUp::fetchXmlAppInformation( 'blog' );

				//-----------------------------------------
				// Get current versions
				//-----------------------------------------
				
				if ( $this->DB->checkForTable( 'blog_upgrade_history' ) )
				{
					/* Fetch current version number */
					$version = $this->DB->buildAndFetch( array( 'select' => '*',
																'from'   => 'blog_upgrade_history',
																'order'  => 'blog_version_id DESC',
																'limit'  => array( 0, 1 ) ) );
																
					$data['_currentLong']	= $version['blog_version_id'];
					$data['_currentHuman']	= $version['blog_version_human'];
				}
				
				$_enabled   = ( $data['disabledatinstall'] ) ? 0 : 1;
	
				if ( $data['_currentLong'] )
				{
					//-----------------------------------------
					// Insert record
					//-----------------------------------------
					
					$this->DB->insert( 'core_applications', array(   'app_title'        => $data['name'],
																	 'app_public_title' => ( $data['public_name'] ) ? $data['public_name'] : '',	// Allow blank in case it's an admin-only app
																	 'app_description'  => $data['description'],
																	 'app_author'       => $data['author'],
																	 'app_version'      => $data['_currentHuman'],
																	 'app_long_version' => $data['_currentLong'],
																	 'app_directory'    => $data['key'],
																	 'app_added'        => time(),
																	 'app_position'     => $_num,
																	 'app_protected'    => 0,
																	 'app_location'     => IPSLib::extractAppLocationKey( $data['key'] ),
																	 'app_enabled'      => $_enabled ) );
				}
			}
		}
						
		/* Query Blogs */
		$this->DB->build( array( 'select' => '*', 'from' => 'blog_blogs' ) );
		$bq = $this->DB->execute();

		while( $r = $this->DB->fetch( $bq ) )
		{
			/* INIT */
			$private_blog = array();
			$public_blog  = array();
			$club_blog    = array();
			$new_tags     = array();

			/* Primary Blog Type */
			$_primary_blog_type = $r['blog_private'] ? 'private' : 'public';

			if( $_primary_blog_type == 'public' )
			{
				$public_blog = $r;
			}
			else
			{
				$private_blog = $r;
			}

			/* Query Categories */
			$this->DB->build( array( 'select' => '*', 'from' => 'blog_categories', 'where' => "blog_id={$r['blog_id']}" ) );
			$this->DB->execute();

			$cats = array();
			while( $i = $this->DB->fetch() )
			{
				$cats[$i['category_id']] = $i;
			}

			/* Query Entries */
			$this->DB->build( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "blog_id={$r['blog_id']}" ) );
			$this->DB->execute();

			$entries = array();
			while( $j = $this->DB->fetch() )
			{
				/* Type of entry */
				$_type = !empty( $cats[$j['category_id']]['category_type'] ) ? $cats[$j['category_id']]['category_type'] : $_primary_blog_type;

				$entries[$_type][] = $j;

				/* Convert the category to a tag */
				if( $j['category_id'] )
				{
					$new_tags[$j['entry_id']] = array(
													'app'		=> 'blog',
													'tag'		=> trim( $cats[$j['category_id']]['category_name'] ),
													'type'		=> 'category',
													'type_id'	=> $j['entry_id'],
													'type_2' 	=> 'blog',
													'type_id_2'	=> $j['blog_id'],
													'updated'	=> time(),
													'member_id' => $j['entry_author_id'],
													);
				}
			}

			/* Do we have private entries? */
			if( is_array( $entries['private'] ) && count( $entries['private'] ) )
			{
				/* If the primary blog is not private, we'll have to make a new one */
				if( $_primary_blog_type != 'private' )
				{
					/* New Blog */
					$private_blog = $r;

					/* Reset some data */
					unset( $private_blog['blog_id'] );
					$private_blog['blog_private'] = 1;
					$private_blog['blog_name']   .= ' (PRIVATE)';
					$private_blog['blog_desc']   .= ' (PRIVATE)';

					/* Insert */
					$this->DB->insert( 'blog_blogs', $private_blog );
					$private_blog['blog_id'] = $this->DB->getInsertID();

					/* Loop through the entries now and change the entry id */
					foreach( $entries['private'] as $entry )
					{
						$this->DB->update( 'blog_entries', array( 'blog_id' => $private_blog['blog_id'] ), "entry_id={$entry['entry_id']}" );

						if( $new_tags[$entry['entry_id']] )
						{
							$new_tags[$entry['entry_id']]['type_2'] = $private_blog['blog_id'];
						}
					}
				}

				/* Insert Permission Index */
				$_perm_row = array(
									'app'			=> 'blog',
									'perm_type'		=> 'blog',
									'perm_type_id'	=> $private_blog['blog_id'],
									'perm_view'		=> '*',
									'perm_2'		=> '',
									'perm_3'		=> '',
									'perm_4'		=> '',
									'perm_5'		=> '',
									'perm_6'		=> '',
									'perm_7'		=> '',
									'owner_only'	=> 1
								);
				$this->DB->insert( 'permission_index', $_perm_row );
			}

			/* Do we have public entries? */
			if( is_array( $entries['public'] ) && count( $entries['public'] ) )
			{
				/* If the primary blog is not private, we'll have to make a new one */
				if( $_primary_blog_type != 'public' )
				{
					/* New Blog */
					$public_blog = $r;

					/* Reset some data */
					unset( $public_blog['blog_id'] );
					$public_blog['blog_private'] = 1;
					$public_blog['blog_name']   .= ' (PUBLIC)';
					$public_blog['blog_desc']   .= ' (PUBLIC)';

					/* Insert */
					$this->DB->insert( 'blog_blogs', $public_blog );
					$public_blog['blog_id'] = $this->DB->getInsertID();

					/* Loop through the entries now and change the entry id */
					foreach( $entries['public'] as $entry )
					{
						$this->DB->update( 'blog_entries', array( 'blog_id' => $public_blog['blog_id'] ), "entry_id={$entry['entry_id']}" );

						if( $new_tags[$entry['entry_id']] )
						{
							$new_tags[$entry['entry_id']]['type_2'] = $public_blog['blog_id'];
						}
					}
				}

				/* Insert Permission Index */
				$_perm_row = array(
									'app'			=> 'blog',
									'perm_type'		=> 'blog',
									'perm_type_id'	=> $public_blog['blog_id'],
									'perm_view'		=> '*',
									'perm_2'		=> '',
									'perm_3'		=> '',
									'perm_4'		=> '',
									'perm_5'		=> '',
									'perm_6'		=> '',
									'perm_7'		=> '',
									'owner_only'	=> 0
								);
				$this->DB->insert( 'permission_index', $_perm_row );
			}

			/* Do we have public entries? */
			if( is_array( $entries['privateclub'] ) && count( $entries['privateclub'] ) )
			{
				$club_blog = $r;

				/* Reset some data */
				unset( $club_blog['blog_id'] );
				$club_blog['blog_private'] = 1;
				$club_blog['blog_name']   .= ' (PRIVATE CLUB)';
				$club_blog['blog_desc']   .= ' (PRIVATE CLUB)';

				/* Insert */
				$this->DB->insert( 'blog_blogs', $club_blog );
				$club_blog['blog_id'] = $this->DB->getInsertID();

				/* Loop through the entries now and change the entry id */
				foreach( $entries['privateclub'] as $entry )
				{
					$this->DB->update( 'blog_entries', array( 'blog_id' => $club_blog['blog_id'] ), "entry_id={$entry['entry_id']}" );

					if( $new_tags[$entry['entry_id']] )
					{
						$new_tags[$entry['entry_id']]['type_2'] = $club_blog['blog_id'];
					}
				}

				/* Get the authorized members */
				$this->DB->build( array( 'select' => '*', 'from' => 'blog_authmembers', 'where' => "blog_id={$r['blog_id']}" ) );
				$this->DB->execute();

				$_club_member_ids = array();
				while( $m = $this->DB->fetch() )
				{
					$_club_member_ids[] = $m['member_id'];
				}
				
				$_club_member_ids[] = $club_blog['member_id'];

				/* Insert Permission Index */
				$_perm_row = array(
									'app'			=> 'blog',
									'perm_type'		=> 'blog',
									'perm_type_id'	=> $club_blog['blog_id'],
									'perm_view'		=> '*',
									'perm_2'		=> '',
									'perm_3'		=> '',
									'perm_4'		=> '',
									'perm_5'		=> '',
									'perm_6'		=> '',
									'perm_7'		=> '',
									'owner_only'	=> 0,
									'authorized_users' => ',' . implode( ',', $_club_member_ids ) . ',',
								);
				$this->DB->insert( 'permission_index', $_perm_row );
			}

			/* Convert Cats to tags */
			if( is_array( $new_tags ) && count( $new_tags ) )
			{
				foreach( $new_tags as $tag )
				{
					$this->DB->insert( 'tags_index', $tag );
					$tag_id = $this->DB->getInsertId();
					$this->DB->update( 'blog_entries', array( 'entry_hastags' => 1 ), "entry_id={$tag['type_id']}" );
				}
			}
		}		
	
		/* Convert the old permission settings */
		$this->DB->build( array( 'select' => 'g_id, g_perm_id', 'from' => 'groups' ) );
		$gq = $this->DB->execute();
		
		while( $g = $this->DB->fetch( $gq ) )
		{
			/* Explode Masks */
			$_perm_masks = explode( ',', $g['g_perm_id'] );
			
			$blog_perms = array();
			foreach( $_perm_masks as $_mask_id )
			{
				$blog_perms = array(
									'g_blog_allowview'		=> in_array( $_mask_id, explode( ',', $this->settings['blog_view_perms']     ) ) ? 1 : 0,
									'g_blog_allowcomment'	=> in_array( $_mask_id, explode( ',', $this->settings['blog_comment_perms']  ) ) ? 1 : 0,
									'g_blog_allowcreate'	=> in_array( $_mask_id, explode( ',', $this->settings['blog_create_perms']   ) ) ? 1 : 0,
									'g_blog_allowlocal'		=> in_array( $_mask_id, explode( ',', $this->settings['blog_local_perms']    ) ) ? 1 : 0,
									'g_blog_allowownmod'	=> in_array( $_mask_id, explode( ',', $this->settings['blog_isownmod_perms'] ) ) ? 1 : 0
									);
			}
			$this->DB->update( 'groups', array( 'g_blog_settings' => serialize( $blog_perms ) ), "g_id={$g['g_id']}" );
		}
				
		/* Drop unused tables: Categories, authmembers, perm settings */
		$this->DB->delete( 'blog_lastinfo' );
	}
}