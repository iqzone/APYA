<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.1.0
|   =============================================
|   by Michael John
|   Copyright 2011-2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit;
}

class portalBlockGateway
{
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $caches;
	protected $cache;    
    
	public function __construct( ipsRegistry $registry )
	{
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	} 
    
	/**
	 * Show the recently started topic titles
	 *
	 * @return	string		HTML content to replace tag with
	 */
	public function latest_topics_sidebar()
	{
		$results	= array();
		$limit		= $this->settings['latest_topics_sidebar'] ? $this->settings['latest_topics_sidebar'] : 5;
		
		$results	= $this->registry->class_forums->hooks_recentTopics( $limit, false );

		return count($results) ? $this->registry->getClass('output')->getTemplate('portal')->latestPosts( $results ) : '';
	}
    
	/**
	 * Show the "news" articles
	 *
	 * @return	string		HTML content to replace tag with
	 */
	public function latest_topics_main()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------

 		$attach_pids	= array();
 		$attach_posts	= array();
 		$forums			= array();
 		$rows			= array();
 		$output			= array();
		$where_clause	= array();
 		$limit			= $this->settings['latest_topics_main'] ? $this->settings['latest_topics_main'] : 3;
 		$posts			= intval($this->memberData['posts']);

 		//-----------------------------------------
    	// Grab articles new/recent in 1 bad ass query
    	//-----------------------------------------

 		foreach( explode( ',', $this->settings['portal_latest_topics_forums'] ) as $forum_id )
 		{
 			if( !$forum_id )
 			{
 				continue;
 			}

 			$forums[] = intval($forum_id);
 		}
 		
 		if( !count($forums) )
 		{
 			return;
 		}
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk  = array();
	
		foreach( $this->registry->class_forums->forum_by_id as $id => $data )
		{
			/* Allowing this forum? */
			if ( ! in_array( $id, $forums ) )
			{
				continue;
			}
			
			/* Can we read? */
			if ( ! $this->registry->permissions->check( 'read', $data ) )
			{
				continue;
			}

			/* Can read, but is it password protected, etc? */
			if ( ! $this->registry->class_forums->forumsCheckAccess( $id, 0, 'forum', array(), true ) )
			{
				continue;
			}

			if ( ! $data['can_view_others'] )
			{
				continue;
			}
			
			if ( $data['min_posts_view'] > $posts )
			{
				continue;
			}

			$forumIdsOk[] = $id;
		}

		if( !count($forumIdsOk) )
		{
			return '';
		}

		/* Add allowed forums */
		$where_clause[] = "t.forum_id IN (" . implode( ",", $forumIdsOk ) . ")";

		//-----------------------------------------
		// Will we need to parse attachments?
		//-----------------------------------------
		
		$parseAttachments	= false;
		
		//-----------------------------------------
		// Run query
		//-----------------------------------------
		
		$pinned   = array();
		$unpinned = array();
		$all	  = array();
		$data     = array();
		$count    = 0;
		
		if( !$this->settings['portal_exclude_pinned'] )
		{
			/* Fetch all pinned topics to avoid filesort */
			$this->DB->build( array( 'select' => 't.tid, t.start_date',
									 'from'   => 'topics t',
									 'where'  => "t.pinned=1 AND t.approved=1 AND t.state != 'link' AND " . implode( ' AND ', $where_clause ),
									 //'order'  => 't.tid DESC',
									 'limit'  => array ( $limit ) ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$pinned[ $row['start_date'] ] = $row['tid'];
				$all[ $row['start_date'] ]    = $row['tid'];
			}
		}
		
		/* Still need more? */
		
		if ( count( $pinned ) < $limit )
		{
			$pinnedWhere	= $this->settings['portal_exclude_pinned'] ? "" : "t.pinned=0 AND ";
			
			$this->DB->build( array( 'select' => 't.tid, t.start_date, t.last_post',
									 'from'   => 'topics t',
									 'where'  => $pinnedWhere . "t.approved=1 AND t.state != 'link' AND " . implode( ' AND ', $where_clause ),
									 'order'  => 'tid DESC',
									 'limit'  => array ( $limit - count( $pinned ) ) ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$unpinned[ $row['last_post'] ] = $row['tid'];
				$all[ $row['last_post'] ]      = $row['tid'];
			}
		}
		
		/* got anything? */
		if ( ! count( $all ) )
		{
			return;
		}
		
		$this->DB->build( array( 
								'select'	=> 't.*',
								'from'		=> array( 'topics' => 't' ),
								'where'		=> "t.tid IN (" . implode( ",",  array_values( $all ) ) . ")",
								'add_join'	=> array(
													array( 
															'select'	=> 'p.*',
															'from'	=> array( 'posts' => 'p' ),
															'where'	=> 'p.pid=t.topic_firstpost',
															'type'	=> 'left'
														),
													array(
															'select'	=> 'f.id, f.name, f.name_seo, f.use_html',
															'from'		=> array( 'forums' => 'f' ),
															'where'		=> "f.id=t.forum_id",
															'type'		=> 'left',
														),
													array( 
															'select'	=> 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name, m.mgroup_others, m.login_anonymous, m.last_visit, m.last_activity',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=p.author_id',
															'type'		=> 'left'
														),
													array( 
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left'
														),
												
													)
					)		);
		
		$outer = $this->DB->execute();
		
 		//-----------------------------------------
 		// Loop through..
 		//-----------------------------------------
 		
 		while( $row = $this->DB->fetch($outer) )
 		{
			$data[ $row['tid'] ] = $row;
		}
		
		krsort( $unpinned );
		krsort( $pinned );
		
		foreach( $unpinned as $date => $tid )
		{
			if ( count( $pinned ) < $limit )
			{
				$pinned[ $date ] = $tid;
			}
			else
			{
				break;
			}
			
			$count++;
		}
		
		/* Now put it altogether */
		foreach( $pinned as $date => $tid )
		{
 			//-----------------------------------------
 			// INIT
 			//-----------------------------------------
 			
			$entry              = $data[ $tid ];
 			$bottom_string		= "";
 			$read_more			= "";
 			$top_string			= "";
 			$got_these_attach	= 0;
 			
			if( $entry['topic_hasattach'] )
			{
				$parseAttachments	= true;
			}

			//-----------------------------------------
			// Parse the post
			//-----------------------------------------
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $entry['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $entry['use_html'] and $entry['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $entry['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $entry['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $entry['mgroup_others'];
			$entry['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $entry['post'] );
 			
 			//-----------------------------------------
 			// BASIC INFO
 			//-----------------------------------------
 			
 			$real_posts			= $entry['posts'];
 			$entry['posts']		= ipsRegistry::getClass('class_localization')->formatNumber(intval($entry['posts']));

            $entry	= IPSMember::buildDisplayData( $entry );
            
 			//-----------------------------------------
			// Attachments?
			//-----------------------------------------
			
			if( $entry['pid'] )
			{
				$attach_pids[ $entry['pid'] ] = $entry['pid'];
			} 			

			if ( IPSMember::checkPermissions('download', $entry['forum_id'] ) === FALSE )
			{
				$this->settings[ 'show_img_upload'] =  0 ;
			} 
                        
            $entry['share_links'] = IPSLib::shareLinks( $entry['title'], array( 'url' => $this->registry->output->buildSEOUrl( 'showtopic=' . $entry['tid'], 'publicNoSession', $entry['title_seo'], 'showtopic' )  ) );
 			
			$rows[] = $entry;
 		}
 		
 		$output = $this->registry->getClass('output')->getTemplate('portal')->articles( $rows );
 		
 		//-----------------------------------------
 		// Process Attachments
 		//-----------------------------------------
 		
 		if ( $parseAttachments AND count( $attach_pids ) )
 		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------
				
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach                  = new $classToLoad( $this->registry );
				
				$this->class_attach->attach_post_key = '';

				ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_topic' ), 'forums' );
			}
			
			$this->class_attach->attach_post_key	=  '';
			$this->class_attach->type				= 'post';
			$this->class_attach->init();
		
			$output = $this->class_attach->renderAttachments( $output, $attach_pids );
			$output	= $output[0]['html'];
 		}
 		
 		return $output;
 	}        
    
	public function portal_sitenav()
	{
 		if ( ! $this->settings['portal_show_nav'] )
 		{
 			return;
 		}
 		
 		$links		= array();
 		$raw_nav	= $this->settings['portal_nav'];
 		
 		foreach( explode( "\n", $raw_nav ) as $l )
 		{
 			$l = str_replace( "&#039;", "'", $l );
 			$l = str_replace( "&quot;", '"', $l );
 			$l = str_replace( '{board_url}', $this->settings['base_url'], $l );
 			
 			preg_match( "#^(.+?)\[(.+?)\]$#is", trim($l), $matches );
 			
 			$matches[1] = trim($matches[1]);
 			$matches[2] = trim($matches[2]);
 			
 			if ( $matches[1] and $matches[2] )
 			{
	 			$matches[1] = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $matches[1] ) );
	 			
	 			$links[] = $matches;
 			}
 		}
 		
 		if( !count($links) )
 		{
 			return;
 		}

 		return $this->registry->getClass('output')->getTemplate('portal')->siteNavigation( $links );
  	}
  	
	/**
	 * Show the affiliates block
	 *
	 * @return	string		HTML content to replace tag with
	 */
	public function portal_affiliates()
	{
 		if ( ! $this->settings['portal_show_fav'] )
 		{
 			return;
 		}
 		
		$this->settings['portal_fav'] = str_replace( "&#039;", "'", $this->settings['portal_fav'] );
		$this->settings['portal_fav'] = str_replace( "&quot;", '"', $this->settings['portal_fav'] );
		$this->settings['portal_fav'] = str_replace( '{board_url}', $this->settings['base_url'], $this->settings['portal_fav'] );
 		
 		return $this->registry->getClass('output')->getTemplate('portal')->affiliates();
 	}
    
	public function online_users_show()
	{
		//-----------------------------------------
		// Get the users from the DB
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums', 'forums' ) . '/boards.php', 'public_forums_forums_boards' );
		$boards	= new $classToLoad();
		$boards->makeRegistryShortcuts( $this->registry );
		
		$active				= $boards->getActiveUserDetails();
		$active['visitors']	= $active['GUESTS']  + $active['ANON'];
		$active['members']	= $active['MEMBERS'];

 		return $this->registry->getClass('output')->getTemplate('portal')->onlineUsers( $active );
 	}    
    
	public function portal_show_poll()
	{
 		if ( ! $this->settings['portal_poll_url'] )
 		{
 		     return;	
 		}
        
 		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------
		
		/* Friendly URL */
		if( $this->settings['use_friendly_urls'] )
		{
			preg_match( "#/topic/(\d+)(.*?)/#", $this->settings['portal_poll_url'], $match );
			$tid = intval( trim( $match[1] ) );
		}
		/* Normal URL */
		else
		{
			preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $this->settings['portal_poll_url'], $match );
			$tid = intval( trim( $match[3] ) );
		}
		
		if ( !$tid )
		{
			return;
		}
        
        $this->request['t'] = $tid;
        
		$this->registry->class_localization->loadLanguageFile( array( 'public_boards', 'public_topic' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );

 		//-----------------------------------------
		// Load forum class
		//-----------------------------------------

		if( !$this->registry->isClassLoaded('class_forums') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			
			$this->registry->getClass('class_forums')->strip_invisible = 1;
			$this->registry->getClass('class_forums')->forumsInit();
    	}

 		//-----------------------------------------
		// Load topic class
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
        		
		try
		{
			$this->registry->getClass('topics')->autoPopulate();
		}
		catch( Exception $crowdCheers )
		{
            return;  
		}        
        
 		//-----------------------------------------
		// We need to get the poll function
		//-----------------------------------------        
        
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums', 'forums' ) . '/topics.php', 'public_forums_forums_topics', 'forums' );
		$topic = new $classToLoad();
        $topic->forumClass = $this->registry->getClass('class_forums');        
		$topic->makeRegistryShortcuts( $this->registry );

        # Move this over to use above class rather then query.
		$topic->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where'  => "tid=" . $tid ) );
		$topic->forum = ipsRegistry::getClass('class_forums')->forum_by_id[ $topic->topic['forum_id'] ];
	
		$this->request['f'] = $topic->forum['id'] ;
        
 		//-----------------------------------------
		// If good, display poll
		//----------------------------------------- 
        		
		if ( $topic->topic['poll_state'] )
		{
 			return $this->registry->getClass('output')->getTemplate('portal')->pollWrapper( $topic->_generatePollOutput(), $topic->topic );
 		}
 		else
 		{
 			return;
 		} 
 	}    
}
?>