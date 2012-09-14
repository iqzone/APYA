<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member management
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_forums_tools_tools extends ipsCommand
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
	 * Editor object
	 *
	 * @var		object			Editor library
	 */
	protected $han_editor;

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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_member_form');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=tools';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=tools';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ), 'forums' );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'deleteposts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_deleteposts' );
				$this->_deletePostsStart();
			break;
			
			case 'deletesubscriptions':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_deletesubscriptions' );
				$this->_deleteSubscriptions();
			break;
			
			case 'clearforumsubs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_clearforumsubs' );
				$this->_deleteForumSubscriptions();
			break;
			
			case 'deleteposts_process':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ftools_deleteposts' );
				$this->_deletePostsDo();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
		
	}
	
	/**
	 * Delete a forum's email subscriptions
	 * 
	 * @return	@e void
	 * @author	Brandon Farber
	 * @since	IPB3 / 22 Oct 2008
	 */
	protected function _deleteForumSubscriptions()
	{
		/**
		 * Get members watching forums so we can recache em..
		 */
		$forum_id	= intval( $this->request['f'] );
		$members	= array();
		
		$this->DB->build( array( 'select' => 'like_member_id', 'from' => 'core_like', 'where' => "like_app='forums' AND like_area='forums' AND like_rel_id=" . $forum_id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$members[]	= $r['like_member_id'];
		}
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'forums' );
		$_like->remove( $forum_id );
		
		/**
		 * Recache members
		 */
		foreach( $members as $mid )
		{
			$this->registry->getClass('class_forums')->recacheWatchedForums( $mid );
		}
		
		/**
		 * Topics is teh suck...gotta get em first to delete em
		 */
		$toDelete	= array();
		
		$this->DB->build( array(
								'select'	=> 'l.like_rel_id',
								'from'		=> array( 'core_like' => 'l' ),
								'where'		=> 't.forum_id=' . $forum_id,
								'add_join'	=> array(
													array(
														'from'	=> array( 'topics' => 't' ),
														'where'	=> "t.tid=l.like_rel_id AND l.like_app='forums' AND l.like_area='topics'",
														'type'	=> 'left'
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$toDelete[]	= $r['like_rel_id'];
		}
		
		if( count($toDelete) )
		{
			$this->DB->delete( 'core_like', "like_lookup_id IN ('" . implode( "','", $toDelete ) . "')" );
			$this->DB->delete( 'core_like_cache', "like_cache_id IN ('" . implode( "','", $toDelete ) . "')" );
		}

		$this->registry->output->redirect( $this->settings['_base_url'] . "app=forums", $this->lang->words['m_subsf_redirect'] );
	}

	/**
	 * Delete a member's email subscriptions
	 * 
	 * @return	@e void
	 * @author	Brandon Farber
	 * @since	IPB3 / 22 Oct 2008
	 */
	protected function _deleteSubscriptions()
	{
		$member_id	= intval( $this->request['member_id'] );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->removeByMemberId( $member_id );

		$_like	= classes_like::bootstrap( 'forums', 'forums' );
		$_like->removeByMemberId( $member_id );
		
		$this->registry->output->redirect( $this->settings['_base_url'] . "app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member_id}", $this->lang->words['m_subs_redirect'] );
	}

	/**
	 * Delete a member's posts [process]
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deletePostsDo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
				
		$member_id			= intval( $this->request['member_id'] );
		$delete_posts		= intval( $this->request['dposts'] );
		$delete_topics		= intval( $this->request['dtopics'] );
		$restart_for_posts	= intval( $this->request['restart_for_posts'] );
		$end				= intval( $this->request['dpergo'] ) ? intval( $this->request['dpergo'] ) : 50;
		$init				= intval( $this->request['init'] );
		$done				= 0;
		$start				= intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$forums_affected	= array();
		$topics_affected	= array();
		$img				= '<img src="' . $this->settings['skin_acp_url'] . '/images/aff_tick.png" alt="-" /> ';
		$posts_deleted		= 0;
		$topics_deleted		= 0;
		
		//--------------------------------------------
		// NOT INIT YET?
		//--------------------------------------------
		
		if ( ! $init )
		{
			/* Right, first off, are we deleting anything? */
			if ( !$delete_posts and !$delete_topics )
			{
				$this->registry->output->showError( $this->lang->words['no_post_topic_sel_del'] );
				return;
			}
			/* Okay, are we deleting topics AND posts? */
			if ( $delete_posts and $delete_topics )
			{
				// It's silly to try and do this all in one go, so
				// we'll delete topics first and start again for posts
				$delete_posts = 0;
				$restart_for_posts = 1;
			}
		
			$url = $this->settings['base_url'] . '&' . $this->form_code_js . "&do=deleteposts_process&dpergo=" . $this->request['dpergo']
																			  ."&st=0"
																			  ."&init=1"
																			  ."&dposts={$delete_posts}"
																			  ."&dtopics={$delete_topics}"
																			  ."&member_id={$member_id}"
																			  ."&name={$this->request['name']}"
																			  ."&restart_for_posts={$restart_for_posts}";
																			  
			$this->registry->output->multipleRedirectInit( $url );
		}

		//--------------------------------------------
		// Not loaded the func?
		//--------------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php', 'classItemMarking' );
		$this->registry->setClass( 'classItemMarking', new $classToLoad( $this->registry ) );
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$mod_func    = new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------
		
		if ( $member_id )
		{
			$member = IPSMember::load( $member_id, 'core' );
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['mem_delete_title'] );
			$this->registry->output->extra_nav[] = array( "{$this->settings['_base_url']}app=members&module=members&section=members&do=viewmember&member_id={$member_id}", $member['members_display_name'] );
			
			$topicWhere = 't.starter_id=' . $member_id;
			$postsWhere = 'p.author_id=' . $member_id;
		}
		else
		{
			$member = array( 'member_id' => 0, 'name' => $this->request['name'] );
			$name = $this->DB->addSlashes( $this->request['name'] );
			
			$topicWhere = "t.starter_id=0 AND t.starter_name='{$name}'";
			$postsWhere = "p.author_id=0 AND p.author_name='{$name}'";
		}
		
		/* Delete posts */
		if ( $delete_posts )
		{
			$this->DB->build( array( 'select'		=> 'p.*',
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> $postsWhere,
										'order'		=> 'p.pid ASC',
										//'limit'		=> array( $start, $end ),
										'add_join'	=> array(
															array(
																'select'	=> 't.*',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
																)
															)
							)		);
		}
		/* Delete topics */
		elseif ( $delete_topics )
		{
			$this->DB->build( array( 'select'		=> 't.*',
										'from'		=> array( 'topics' => 't' ),
										'where'		=> $topicWhere,
										'order'		=> 't.tid ASC',
										//'limit'		=> array( $start, $end ),
										'add_join'	=> array(
															array(
																'select'	=> 'p.*',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 't.topic_firstpost=p.pid',
																'type'		=> 'left',
																)
															)
							)		);
		}
				
		$outer = $this->DB->execute();
		
		//-----------------------------------------
		// Process...
		//-----------------------------------------
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Copy record to topic array
			//-----------------------------------------
			
			$topic	= $r;
			
			//-----------------------------------------
			// No longer a topic?
			//-----------------------------------------
			
			if ( ! $topic['tid'] )
			{
				//-----------------------------------------
				// Cleanup - might as well remove the orphaned post
				// or the ACP will always show posts to delete that
				// it won't be able to delete
				//-----------------------------------------
				
				if( $topic['pid'] )
				{
					$this->DB->delete( 'posts', 'pid=' . $topic['pid'] );
				}
				
				continue;
			}
			
			$done++;

			//-----------------------------------------
			// Get number of MID posters
			//-----------------------------------------
			
			$topic_i_posted = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
															   'from'	=> array( 'posts' => 'p' ),
															   'where'	=> $postsWhere . ' AND topic_id=' . $r['tid'] ) );
			
			//-----------------------------------------
			// Aready deleted this topic?
			//-----------------------------------------
			
			if ( ! $topic_i_posted['count'])
			{
				if ( $delete_topics && $topic['state'] == 'link' )
				{
					/* We'll catch this below */
				}
				else
				{
					continue;
				}
			}

			//-----------------------------------------
			// First check: Our topic and no other replies?
			//-----------------------------------------
			
			if ( ( ( $member_id and $topic['starter_id'] == $member_id ) or ( !$member_id and !$topic['starter_id'] and $topic['starter_name'] == $this->request['name'] ) ) AND $topic_i_posted['count'] == ( $topic['posts'] + 1 ) )
			{
				//-----------------------------------------
				// Ok, deleting topics or posts?
				//-----------------------------------------
				
				if ( $delete_posts OR $delete_topics )
				{
					$mod_func->topicDeleteFromDB( $r['tid'], TRUE );
																	  
					$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
					$topics_deleted++;
					$posts_deleted += $topic_i_posted['count'];
				}
			}
			
			//-----------------------------------------
			// Is this a topic we started?
			//-----------------------------------------
			
			else if ( ( ( $member_id and $topic['starter_id'] == $member_id ) or ( !$member_id and !$topic['starter_id'] and $topic['starter_name'] == $this->request['name'] ) ) AND $delete_topics )
			{
				$mod_func->topicDeleteFromDB( $r['tid'], TRUE );
																  
				$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
				$topics_deleted++;
				$posts_deleted += $topic['posts'] + 1;
			}
			
			//-----------------------------------------
			// Just delete the post, then
			//-----------------------------------------
			
			else if ( $delete_posts AND ! $r['new_topic'] AND $r['pid'] )
			{
				$mod_func->postDeleteFromDb( $r['pid'], TRUE, TRUE );

				$forums_affected[ $topic['forum_id'] ] = $topic['forum_id'];
				$topics_affected[ $topic['tid']      ] = $topic['tid'];

				$posts_deleted++;
			}
		}
		
		//-----------------------------------------
		// Rebuild topics and forums
		//-----------------------------------------
		
		if ( count( $topics_affected ) )
		{
			foreach( $topics_affected as $tid )
			{
				$mod_func->rebuildTopic( $tid, 0 );
			}
		}
		
		if ( count( $forums_affected ) )
		{
			foreach( $forums_affected as $fid )
			{
				$mod_func->forumRecount( $fid );
			}
		}
		
		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done )
		{
			//--------------------------------------
			// Hang on there! Do we need to do posts as well?
			//--------------------------------------
			
			if ( $this->request['restart_for_posts'] )
			{
				$url = $this->settings['base_url'] . '&' . $this->form_code_js . "&do=deleteposts_process&dpergo=" . $this->request['dpergo']
																			  ."&st=0"
																			  ."&init=1"
																			  ."&dposts=1"
																			  ."&dtopics=0"
																			  ."&member_id={$member_id}"
																			  ."&name={$this->request['name']}"
																			  ."&restart_for_posts=0";
																			  
				$this->registry->output->multipleRedirectHit( $url, $this->lang->words['mem_posts_next_step'] );
			}
						
		 	//-----------------------------------------
			// Recount stats..
			//-----------------------------------------
			
			$this->cache->rebuildCache( 'stats', 'global' );
			
			//-----------------------------------------
			// Reset member's posts
			//-----------------------------------------
			
			$forums = array();
			
			foreach( $this->registry->class_forums->forum_by_id as $data )
			{
				if ( ! $data['inc_postcount'] )
				{
					$forums[] = $data['id'];
				}
			}

			$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), 'p.' );
			
			if ( ! count( $forums ) )
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'posts p', 'where' => $_queued . ' AND ' . $postsWhere ) );
			}
			else
			{
				$count = $this->DB->buildAndFetch( array( 'select'	=> 'count(p.pid) as count',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> $_queued . ' AND ' . $postsWhere . ' AND t.forum_id NOT IN (' . implode( ",", $forums ) . ')',
																'add_join'	=> array( 
																					array( 'type'	=> 'left',
																		 					'from'	=> array( 'topics' => 't' ),
																		 					'where'	=> 't.tid=p.topic_id'
																		 				)			
																		 			)
																)		);
			}
			
			$new_post_count = intval( $count['count'] );
			
			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['member_posts_deleted'], $member_id ? $member['members_display_name'] : $this->request['name'] ) );

			IPSMember::save( $member_id, array( 'core' => array( 'posts' => $new_post_count ) ) );
			$this->registry->output->multipleRedirectFinish( $this->lang->words['mem_posts_process_done'] );
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			
			$next = $start + $end;
			
			$url = $this->settings['base_url'] . '&' . $this->form_code_js . "&do=deleteposts_process&dpergo={$end}"
																			  ."&st={$next}"
																			  ."&init=1"
																			  ."&dposts={$delete_posts}"
																			  ."&dtopics={$delete_topics}"
																			  ."&member_id={$member_id}"
																			  ."&name={$this->request['name']}"
																			  ."&restart_for_posts={$restart_for_posts}";
																			  
			$text = sprintf( $this->lang->words['mem_posts_process_more'], $end, $posts_deleted, $topics_deleted );

			$this->registry->output->multipleRedirectHit( $url, $img . ' ' . $text );
		}
	}
	
	/**
	 * Delete a member's posts [form/confirmation]
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deletePostsStart()
	{
		//-----------------------------------------
		// Page set up
		//-----------------------------------------

		$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['mem_delete_title'] );

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval($this->request['member_id']);
		
		//-----------------------------------------
		// Get number of topics member has started
		//-----------------------------------------
		
		$topicWhere = '1=0';
		$postsWhere = '1=0';
		if ( $member_id )
		{
			$member = IPSMember::load( $member_id, 'core' );
			$this->registry->output->extra_nav[] = array( "{$this->settings['_base_url']}app=members&module=members&section=members&do=viewmember&member_id={$member_id}", $member['members_display_name'] );
			
			$topicWhere = 't.starter_id=' . $member_id;
			$postsWhere = 'p.author_id=' . $member_id;
		}
		else
		{
			$member = array( 'member_id' => 0, 'name' => $this->request['name'] );
			$name = $this->DB->addSlashes( $this->request['name'] );
			
			$topicWhere = "t.starter_id=0 AND t.starter_name='{$name}'";
			$postsWhere = "p.author_id=0 AND p.author_name='{$name}'";
		}
	
		$topics = $this->DB->buildAndFetch( array( 'select'	=> 'count(*) as count',
													'from'	=> 'topics t',
													'where'	=> $topicWhere ) );
																	
		$posts  = $this->DB->buildAndFetch( array( 'select'		=> 'count(*) as count',
													'from'		=> array( 'posts' => 'p' ),
													'where'		=> $postsWhere,
													'add_join'	=> array(
																		array(
																			'from'	=> array( 'topics' => 't' ),
																			'where'	=> 't.tid=p.topic_id',
																			'type'	=> 'left',
																			)
																		)
										)		);

		//-----------------------------------------
		// Got any posts?
		//-----------------------------------------
		
		if ( ! $posts['count'] )
		{
			$this->registry->output->showError( $this->lang->words['t_noposts'], 11363 );
		}
		
		//-----------------------------------------
		// Get number of topics member has started
		//-----------------------------------------
		
		$this->lang->words['mem_delete_delete_posts']  = sprintf( $this->lang->words['mem_delete_delete_posts'] , intval($posts['count']) );
		$this->lang->words['mem_delete_delete_topics'] = sprintf( $this->lang->words['mem_delete_delete_topics'], intval($topics['count']) );
		
		$this->registry->output->html .= $this->html->deletePostsStart( $member, intval($topics['count']), intval($posts['count']) );
		
		
	}
}