<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Moderator actions
 * Last Updated: $Date: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10798 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_moderate_moderate extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @var		string
	 */
	public $output;
	
	/**
	 * Moderator function library
	 *
	 * @var		object
	 */
	public $modLibrary;

	/**
	 * Moderator information
	 *
	 * @var		array		Array of moderator details
	 */
	protected $moderator		= array();

	/**
	 * Forum information
	 *
	 * @var		array		Array of forum details
	 */
	protected $forum			= array();

	/**
	 * Topic information
	 *
	 * @var		array		Array of topic details
	 */
	protected $topic			= array();

	/**
	 * Topic ids stored
	 *
	 * @var		array		Topic ids for multimoderation
	 */
	protected $tids			= array();
	
	/**
	 * Post ids stored
	 *
	 * @var		array		Post ids for multimoderation
	 */
	protected $pids			= array();
	
	protected $fromSearch   = false;
	protected $returnUrl    = false;
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load language & skin files
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_mod' ) );
		
		/* If we're here auth_key was fine, so did we come from search? */
		if ( $this->memberData['member_id'] AND $this->memberData['g_is_supmod'] AND $this->request['fromSearch'] AND $this->request['returnUrl'] )
		{
			$this->fromSearch = true;
			$this->returnUrl  = base64_decode( $this->request['returnUrl'] );
		}
		
		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		$this->_setupAndCheckInput();
		
		//-----------------------------------------
		// Load moderator functions
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$this->modLibrary = new $classToLoad( $this->registry );
		$this->modLibrary->init( $this->forum );

		//-----------------------------------------
		// Convert the code ID's into something
		// use mere mortals can understand....
		//-----------------------------------------
		
		switch ( $this->request['do'] )
		{
			case '02':
				$this->_moveForm();
			break;
			case '03':
				$this->_deleteForm();
			break;
			case '04':
				$this->_deletePost();
			break;
			case '05':
				$this->_editform();
			break;
			case '00':
				$this->_closeTopic();
			break;
			case '01':
				$this->_openTopic();
			break;
			case '08':
				$this->_deleteTopic();
			break;
			case '09':
				$this->_deleteTopicPermanent();
			break;
			case '12':
				$this->_doEdit();
			break;
			case '14':
				$this->_doMove();
			break;
			case '15':
				$this->_topicPinAlter( 'pin' );
			break;
			case '16':
				$this->_topicPinAlter( 'unpin' );
			break;
			case 'sdelete':
				$this->_softDeleteTopicToggle(true);
			break;
			case 'sundelete':
				$this->_softDeleteTopicToggle(false);
			break;
			case 'deleteArchivedTopic':
				$this->_deleteArchivedTopic();
			break;
			//-----------------------------------------
			// Unsubscribe
			//-----------------------------------------
			case '30':
				$this->_unsubscribeAllForm();
			break;
			case '31':
				$this->_unsubscribeAll();
			break;
			//-----------------------------------------
			// Merge Start
			//-----------------------------------------
			case '60':
				$this->_mergeStart();
			break;
			case '61':
				$this->_mergeComplete();
			break;
			//-----------------------------------------
			// Topic History
			//-----------------------------------------
			case '90':
				$this->_topicHistory();
			break;
			//-----------------------------------------
			// Multi---
			//-----------------------------------------	
			case 'topicchoice':
				$this->_multiTopicModify();
			break;
			//-----------------------------------------
			// Multi---
			//-----------------------------------------	
			case 'postchoice':
				$this->_multiPostModify();
			break;
			//-----------------------------------------
			// Resynchronize Forum
			//-----------------------------------------
			case 'resync':
				$this->_resyncForum();
			break;
			//-----------------------------------------
			// Prune / Move Topics
			//-----------------------------------------
			case 'prune_start':
				$this->_pruneStart();
			break;
			case 'prune_finish':
				$this->_pruneFinish();
			break;
			case 'prune_move':
				$this->_pruneMove();
			break;
			//-----------------------------------------
			// Add. topic view func.
			//-----------------------------------------
			case 'topic_approve':
			case 'topic_restore':
				$this->_topicApproveAlter('approve');
			break;
			case 'topic_unapprove':
				$this->_topicApproveAlter('unapprove');
			break;

			/* New options for 3.1 */
			case 'p_approve':
				$this->_postsManage('approve_unapproved');
			break;
			case 'p_delete_approve':
				$this->_postsManage('delete_unapproved');
			break;
			case 'p_restore':
				$this->_postsManage('restore_deleted');
			break;
			case 'p_delete_softed':
				$this->_postsManage('delete_deleted');
			break;
			case 'p_hrestore':
				$this->_postsManage('hdelete_restore');
			break;
			case 'p_hdelete':
				$this->_postsManage('hdelete_delete');
			break;
			
			case 'unArchiveTopic':
				$this->_topicUnarchive();
			break;
			
			default:
				$this->_showError();
			break;
		}
		
		// If we have any HTML to print, do so...
		
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Flag a topic as unarchive.
	 * A tragically boring and mundane function written on a misty Monday morning
	 */
	protected function _topicUnarchive()
	{
		if ( ! $this->memberData['g_access_cp'] )
		{
			$this->_showError( 'moderate_no_permission', 103119.2, 403 );
		}
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore.php', 'classes_archive_restore' );
		$archiveRestore = new $classToLoad();
		
		$archiveRestore->setApp('forums');
		
		$archiveRestore->setManualUnarchive( $this->topic['tid'] );
		
		/* Back to topic, then? */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . '&st=' . intval( $this->request['st'] ), $this->topic['title_seo'], "showtopic" );
	}
	
	/**
	 * A simple wrapper function for multimethods
	 *
	 * NO PERMISSION CHECKS WILL BE PERFORMED IN THIS FUNCTION
	 * AS CHECKS ARE PERFORMED IN ACTUAL FUNCTIONS
	 *
	 * @param	string		Type of action
	 */
	protected function _postsManage( $what )
	{
		$where  = '';
		$posts  = array();
		
		if ( $this->topic['tid'] )
		{
			switch ( $what )
			{
				case 'approve_unapproved':
				case 'delete_unapproved':
					$where = $this->registry->class_forums->fetchPostHiddenQuery( 'hidden' );
				break;
				case 'restore_deleted':
				case 'delete_deleted':
					$where = $this->registry->class_forums->fetchPostHiddenQuery( 'sdeleted' );
				break;
			}

			if( is_array($this->request['pid']) )
			{
				foreach( $this->request['pid'] as $pid )
				{
					$pid	= intval($pid);
					$this->pids[ $pid ] = $pid;
				}
			}
			else
			{
				/* Get posts */
				$this->DB->build( array( 'select' => '*',
										 'from'	  => 'posts',
										 'where'  => 'topic_id=' . $this->topic['tid'] . ' AND ' . $where ) );
										 
				$this->DB->execute();
				
				while( $row = $this->DB->fetch() )
				{
					$this->pids[ $row['pid'] ] = $row['pid'];
				}
			}
			
			$this->request['selectedpids'] = implode( ",", $this->pids );
			
			/* now... */
			if ( count( $this->pids ) )
			{
				switch ( $what )
				{
					case 'approve_unapproved':
						$this->_multiApprovePost(1);
					break;
					case 'delete_unapproved':
						$this->_multiDeletePost();
					break;
					case 'restore_deleted':
						$this->_multiSoftDeletePost(0, '' );
					break;
					case 'delete_deleted':
						$this->_multiDeletePost();
					break;
					case 'hdelete_restore':
						$this->_multiRestoreHardDeletedPost();
					break;
					case 'hdelete_delete':
						$this->_multiRemoveHardDeletedPost();
					break;
				}

				if( $this->request['return'] )
				{
					$_bits	= explode( ':', $this->request['return'] );
					
					if( count($_bits) AND $_bits[0] == 'modcp' )
					{
						$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'posts' );
					}
				}

				/* Done */
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . '&amp;st=' . intval($this->request['st']), $this->topic['title_seo'], 'showtopic' );
			}
		}
		
		/* Still here? */
		$this->_showError( 'mod_no_tid', 103118.1 );
	}
	
	/**
	 * Permanently remove a hard deleted post. Only available via Mod CP as of 3.3.
	 *
	 * @return	@e void
	 */
	protected function _multiRemoveHardDeletedPost()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'delete_post' );
		
		$_titles	= array();
		
		$this->DB->build( array(
								'select'	=> 'p.*', 
								'from'		=> array( 'posts' => 'p' ),
								'where'		=> 'p.pid IN(' . implode( ',', $this->pids ) . ')',
								'add_join'	=> array(
													array(
														'select'	=> 't.*',
														'from'		=> array( 'topics' => 't' ),
														'where'		=> 't.tid=p.topic_id',
														'type'		=> 'left',
														)
													)
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$_titles[ $r['tid'] ]	= $r['title'];

			$this->modLibrary->init( $this->registry->getClass('class_forums')->allForums[ $r['forum_id'] ], $r );
			$this->modLibrary->postDeleteFromDb( $r['pid'] );
			$this->modLibrary->forumRecount( $r['forum_id'] );
			$this->modLibrary->clearModQueueTable( 'post', $r['pid'] );
		}
		
		$this->modLibrary->addModerateLog( "", "", "", implode( ', ', $_titles ), sprintf( $this->lang->words['multi_post_delete'], implode( ', ', $this->pids ) ) );
	}

	/**
	 * Restore a hard deleted post. Only available via Mod CP as of 3.3.
	 *
	 * @return	@e void
	 */
	protected function _multiRestoreHardDeletedPost()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'delete_post' );
		
		$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid IN(' . implode( ',', $this->pids ) . ')' );
		
		/* Remove any soft delete logs */
		IPSDeleteLog::removeEntries( $this->pids, 'post', true );

		$this->DB->build( array(
								'select'	=> 'p.*', 
								'from'		=> array( 'posts' => 'p' ),
								'where'		=> 'p.pid IN(' . implode( ',', $this->pids ) . ')',
								'add_join'	=> array(
													array(
														'select'	=> 't.*',
														'from'		=> array( 'topics' => 't' ),
														'where'		=> 't.tid=p.topic_id',
														'type'		=> 'left',
														)
													)
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$this->modLibrary->init( $this->registry->getClass('class_forums')->allForums[ $r['forum_id'] ], $r );
			$this->modLibrary->rebuildTopic( $r['tid'] );
			$this->modLibrary->forumRecount( $r['forum_id'] );
			$this->modLibrary->clearModQueueTable( 'post', $r['pid'] );
		}
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		$this->modLibrary->addModerateLog( "", "", "", implode( ',', $this->pids ), sprintf( $this->lang->words['multi_post_restore'], implode( ',', $this->pids ) ) );
	}
	
	/**
	 * A simple wrapper function for multimethods
	 *
	 * NO PERMISSION CHECKS WILL BE PERFORMED IN THIS FUNCTION
	 * AS CHECKS ARE PERFORMED IN ACTUAL FUNCTIONS
	 *
	 * @param	string		Type of action
	 */
	protected function _topicsManage( $what )
	{
		$where  = '';
		$posts  = array();
		
		if ( $this->forum['id'] )
		{
			switch ( $what )
			{
				case 'approve_unapproved':
				case 'delete_unapproved':
					$where = $this->registry->class_forums->fetchTopicHiddenQuery( 'hidden' );
				break;
				case 'restore_deleted':
				case 'delete_deleted':
					$where = $this->registry->class_forums->fetchTopicHiddenQuery( 'sdeleted' );
				break;
			}
			
			/* Get posts */
			$this->DB->build( array( 'select' => '*',
									 'from'	  => 'topics',
									 'where'  => 'forum_id=' . $this->forum['id'] . ' AND ' . $where ) );
									 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$this->tids[ $row['tid'] ] = $row['tid'];
			}
			
			$this->request['selectedtids'] = implode( ",", $this->tids );
			
			/* now... */
			if ( count( $this->tids ) )
			{
				switch ( $what )
				{
					case 'approve_unapproved':
						$this->_multiAlterTopics('topic_q', $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) );
					break;
					case 'delete_unapproved':
						$this->_multiAlterTopics('delete_topic');
					break;
					case 'restore_deleted':
						$this->_multiSoftDeleteTopics(0, '' );
					break;
					case 'delete_deleted':
						$this->_multiAlterTopics('delete_topic');
					break;
				}
				
				/* Done */
				$url = "showforum=" . $this->forum['id'];
				$url = ( $this->request['st'] ) ? "showforum=" . $this->forum['id'] . '&amp;st=' . $this->request['st'] : $url;
		
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_topics'], $this->settings['base_url'] . $url, $this->forum['name_seo'], 'showforum'  );
			}	
		}
		
		/* Still here? */
		$this->_showError( 'mod_no_tid', 103118.1 );
	}

	/**
	 * Alter approve/unapprove state of topic
	 *
	 * @param	string		[approve|unapprove]
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _topicApproveAlter( $type='approve' )
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'post_q' );
		
		$approve_int = $type == 'approve' ? 1 : 0;
		
		$this->DB->update( 'topics', array( 'approved' => $approve_int ), 'tid=' . $this->topic['tid'] );
		
		if ( $approve_int )
		{
			$this->modLibrary->clearModQueueTable( 'topic', $this->topic['tid'], true );

			/* Likes */
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'forums', 'topics' );
			$_like->toggleVisibility( $this->topic['tid'], true );

			/* Tagging */
			if ( $this->registry->tags->isEnabled() )
			{
				$this->registry->tags->updateVisibilityByMetaId( $this->topic['tid'], 1 );
			}
			
			/* Remove any soft delete logs if we are 'restoring' */
			if( $this->request['do'] == 'topic_restore' )
			{
				IPSDeleteLog::removeEntries( array( $this->topic['tid'] ), 'topic', true );
			}
		}

		$this->modLibrary->forumRecount( $this->forum['id'] );
		$this->cache->rebuildCache( 'stats', 'global' );
		
		$this->_addModeratorLog( sprintf( $type == 'approve' ? $this->lang->words['acp_approve_topic'] : $this->lang->words['acp_unapprove_topic'], $this->topic['tid'] ) );

		if( $this->request['return'] )
		{
			$_bits	= explode( ':', $this->request['return'] );
			
			if( count($_bits) AND $_bits[0] == 'modcp' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['redirect_modified'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'topics' );
			}
		}

		$this->registry->output->redirectScreen( $this->lang->words['redirect_modified'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']), $this->topic['title_seo'], 'showtopic' );
	}
	
	/**
	 * Alter pin/unpinned state of topic
	 *
	 * @param	string		[pin|unpin]
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _topicPinAlter( $type='pin' )
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( $type == 'pin' )
		{
			if ( $this->topic['pinned'] )
			{
				$this->_showError( 'mod_topic_pinned', 10371 );
			}
			
			$this->_genericPermissionCheck( 'pin_topic' );
			
			$this->modLibrary->topicPin($this->topic['tid']);
			
			$this->_addModeratorLog( $this->lang->words['acp_pinned_topic'] );
			
			$words = $this->lang->words['p_pinned'];
		}
		else
		{
			if ( !$this->topic['pinned'] )
			{
				$this->_showError( 'mod_topic_unpinned', 10372 );
			}
			
			$this->_genericPermissionCheck( 'unpin_topic' );
			
			$this->modLibrary->topicUnpin($this->topic['tid']);
			
			$this->_addModeratorLog( $this->lang->words['acp_unpinned_topic'] );
			
			$words = $this->lang->words['p_unpinned'];
		}

		$url	= "showtopic=".$this->topic['tid']."&amp;st=".intval($this->request['st']);
		
		if( $this->request['from'] == 'forum' )
		{
			$url	= "showforum=".$this->topic['forum_id']."&amp;st=".intval($this->request['st']);
		}

		$this->registry->output->redirectScreen( $words, $this->settings['base_url'] . $url, $this->request['from'] == 'forum' ? $this->forum['name_seo'] : $this->topic['title_seo'], $this->request['from'] == 'forum' ? 'showforum' : 'showtopic' );
	}

	/**
	 * Alter pin/unpinned state of topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _resyncForum()
	{
		$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $this->forum['id'] );
		$this->cache->rebuildCache( 'stats', 'global' );
		
		$this->registry->output->redirectScreen( $this->lang->words['cp_resync'], $this->settings['base_url'] . "showforum=".$this->forum['id'], $this->forum['name_seo'], 'showforum' );
	}
	
	/**
	 * Process post multi-moderation
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiPostModify()
	{
		$this->pids  = $this->_getIds( 'selectedpids', 'selectedpidsJS' );
		
		if ( count( $this->pids ) )
		{
			/* Set pids back for templates */
			$this->request['selectedpids'] = $this->pids;
			
			switch ( $this->request['tact'] )
			{
				case 'approve':
					$this->_multiApprovePost(1);
				break;
				case 'unapprove':
					$this->_multiApprovePost(0);
				break;
				case 'deletedo':
					$this->_multiDeletePost();
				break;
				case 'delete':
					$this->_multiDeletePostSplash();
				break;
				case 'sdelete':
					$this->_multiSoftDeletePost(1, $this->request['deleteReason'] );
				break;
				case 'sundelete':
					$this->_multiSoftDeletePost(0);
				break;
				case 'merge':
					$this->_multiMergePost();
				break;
				case 'split':
					$this->_multiSplitTopic();
				break;
				case 'move':
					$this->_multiMovePost();
				break;
			}
		}
		
		IPSCookie::set( 'modpids', '', 0 );
		
		/* From modcp */
		if( $this->request['return'] )
		{
			$_bits	= explode( ':', $this->request['return'] );
			
			if( count($_bits) AND $_bits[0] == 'modcp' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'posts' );
			}
		}
				
		/* From search? */
		if ( $this->fromSearch AND $this->returnUrl )
		{
			if ( $this->request['nr'] )
			{
				$this->registry->output->silentRedirect( $this->returnUrl );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->returnUrl  );
			}

		}
		else if ( $this->request['pid'] )
		{
			if ( $this->request['nr'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . "findpost=" . $this->request['pid'] );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "findpost=" . $this->request['pid'] );
			}
		}
		else if ( $this->topic['tid'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . '&amp;st=' . intval($this->request['st']), $this->topic['title_seo'], 'showtopic' );
		}
	}
	
	/**
	 * Post multi-mod: Move posts
	 *
	 * @param 	string		[Optional] error message
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiMovePost( $error='' )
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );

		if( ! $this->topic['tid'] )
		{
			$this->_showError( 'mod_no_tid', 10373, 404 );
		}

		if( $this->request['checked'] != 1 )
		{
			$posts		= array();

			//-----------------------------------------
			// Display the posty wosty's
			//-----------------------------------------
			
			$this->DB->build( array(
									  'select' => 'p.post, p.pid, p.post_date, p.author_id, p.author_name, p.use_emo, p.post_htmlstate',
									  'from'   => array( 'posts' => 'p' ),
									  'where'  => 'p.topic_id=' . $this->topic['tid'] . ' AND p.pid IN (' . implode( ',', $this->pids ) . ')',
									  'order'  => 'p.post_date',
									  'add_join'	=> array(
									  						array( 'select'	=> 'm.member_group_id, m.mgroup_others',
									  								'from'	=> array( 'members' => 'm' ),
									  								'where'	=> 'm.member_id=p.author_id',
									  							)
									  						)
							)  );
								 
			$post_query = $this->DB->execute();

			while( $row = $this->DB->fetch( $post_query ) )
			{
				$row['post']	= IPSText::truncate( $row['post'], 800 );
				$row['date']	= ipsRegistry::getClass( 'class_localization')->getDate( $row['post_date'], 'LONG' );
				
				/* Parse the post */
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

				$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );				
				
				/* Add to output array */				
				$posts[]		= $row;
			}

			//-----------------------------------------
			// print my bottom, er, the bottom
			//-----------------------------------------

			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->movePostForm( $this->forum, $this->topic, $posts, $error );

			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
			$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
			$this->registry->getClass('output')->setTitle( $this->lang->words['cmp_title'].": ".$this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
		else
		{
			//-----------------------------------------
			// PROCESS Check the input
			//-----------------------------------------
			
			$old_id = $this->_getTidFromUrl();

			if ( !$old_id )
			{
				$this->request[ 'checked'] =  0 ;
				$this->_multiMovePost( $this->lang->words['cmp_notopic'] );
			}
			
			//-----------------------------------------
			// Grab topic
			//-----------------------------------------
			
			$move_to_topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $old_id ) );
			
			if ( ! $move_to_topic['tid'] or !$this->registry->class_forums->allForums[ $move_to_topic['forum_id'] ]['id'] )
			{
				$this->request[ 'checked'] =  0 ;
				$this->_multiMovePost( $this->lang->words['cmp_notopic'] );
			}
			
			$affected_ids	= count( $this->pids );
			
			//-----------------------------------------
			// Do we have enough?
			//-----------------------------------------
			
			if ( $affected_ids < 1 )
			{
				$this->_showError( 'mod_not_enough_split', 10374 );
			}
			
			//-----------------------------------------
			// Do we choose too many?
			//-----------------------------------------
			
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}" ) );
			
			if ( $affected_ids >= $count['cnt'] )
			{
				$this->_showError( 'mod_too_much_split', 10375 );
			}
			
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			$this->DB->update( 'posts', array( 'topic_id' => $move_to_topic['tid'], 'new_topic' => 0 ), "pid IN(" . implode( ",", $this->pids ) . ")" ); 
			$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" ); 
			
			//-----------------------------------------
			// Update attachments
			//-----------------------------------------
			
			$this->DB->update( 'attachments', array( 'attach_parent_id' => $move_to_topic['tid'] ), "attach_rel_module='post' AND attach_rel_id IN(" . implode( ",", $this->pids ) . ")" );
		
			//-----------------------------------------
			// Is first post queued for new topic?
			//-----------------------------------------

			$first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																	'from'	=> 'posts',
																	'where'	=> "topic_id={$move_to_topic['tid']}",
																	'order'	=> $this->settings['post_order_column'] . ' ASC',
																	'limit'	=> array( 1 ),
														)		);

			if( $first_post['queued'] )
			{
				$this->DB->update( 'topics', array( 'approved' => 0 ), "tid={$move_to_topic['tid']}" );
				$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $first_post['pid'] );
			}
			
			//-----------------------------------------
			// Is first post queued for old topic?
			//-----------------------------------------

			$other_first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																		'from'		=> 'posts',
																		'where'		=> "topic_id={$this->topic['tid']}",
																		'order'		=> $this->settings['post_order_column'] . ' ASC',
																		'limit'		=> array( 1 ),
																)		);

			if( $other_first_post['queued'] )
			{
				$this->DB->update( 'topics', array( 'approved' => 0 ), "tid={$this->topic['tid']}" );
				$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $other_first_post['pid'] );
			}	
			
			//-----------------------------------------
			// Rebuild the topics
			//-----------------------------------------
			
			$this->modLibrary->rebuildTopic($move_to_topic['tid']);
			$this->modLibrary->rebuildTopic($this->topic['tid']);
			
			//-----------------------------------------
			// Update the forum(s)
			//-----------------------------------------
			
			$this->modLibrary->forumRecount( $this->topic['forum_id'] );
			
			if ( $this->topic['forum_id'] != $move_to_topic['forum_id'] )
			{
				$this->modLibrary->forumRecount( $move_to_topic['forum_id'] );
			}

			$this->_addModeratorLog( sprintf( $this->lang->words['acp_moved_posts'], $this->topic['title'], $move_to_topic['title'] ) );
		}
	}
	
	/**
	 * Post multi-mod: Soft delete posts
	 *
	 * @param 	integer		1=undelete, 0=restore
	 * @param	string		Reason for moderating the post
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiSoftDeletePost( $delete=1, $reason='' )
	{
		$delete = ( $delete ) ? TRUE : FALSE;
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		/* Grab posts to check perms */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'posts',
								 'where'  => 'pid IN (' . implode( ',', $this->pids ) . ')' ) );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( $delete )
			{
				$result = $this->registry->getClass('class_forums')->canSoftDeletePosts( $this->topic['forum_id'], $row );
			}
			else
			{
				$result = $this->registry->getClass('class_forums')->can_Un_SoftDeletePosts( $this->topic['forum_id'] );
	
			}
			
			if ( ! $result )
			{
				$this->_showError( 'moderate_no_permission', 103119.1 );
			}
		}
		
		/* Fetch entries and checked for lock status if not sup global */
		//if ( ! $this->memberData['g_is_supmod'] )
		//{
		//	$logs = IPSDeleteLog::fetchEntries( $this->pids, 'post' );
		//	
		//	if ( is_array( $logs ) )
		//	{
		//		foreach( $logs as $l => $data )
		//		{
		//			if ( $data['sdl_locked'] )
		//			{
		//				$this->_showError( 'moderate_no_permission_deleted_items', 10367.1 );
		//			}
		//		}
		//	}
		//}
		
		/* Ensure we check for a single topic id */
		if ( ! $this->fromSearch )
		{
			$this->modLibrary->postToggleSoftDelete( $this->pids, $delete, $reason, $this->topic['tid'] );
		}
		else
		{
			$this->modLibrary->postToggleSoftDelete( $this->pids, $delete, $reason );
		}
	}
	
	/**
	 * Post multi-mod: Approve posts
	 *
	 * @param 	integer		1=approve, 0=unapprove
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiApprovePost( $approve=1 )
	{
		$_approve = ( $approve ) ? TRUE : FALSE;
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'post_q' );
		
		/* Ensure we check for a single topic id */
		if ( ! $this->fromSearch )
		{
			$this->modLibrary->postToggleApprove( $this->pids, $_approve, $this->topic['tid'] );
		}
		else
		{
			$this->modLibrary->postToggleApprove( $this->pids, $_approve );
		}
	}
	
	/**
	 * Post multi-mod: Delete posts
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiDeletePost()
	{
		$this->_resetModerator( $this->topic['forum_id'] );

		if( !$this->_genericPermissionCheck( 'delete_post', null, true ) )
		{
			if( !$this->memberData['g_delete_own_posts'] )
			{
				$this->_showError( 'mod_delete_first_post', 10376.1 );
			}
			else
			{
				/* Sort for the query */
				$_newPids	= array();
				$_pid		= IPSLib::cleanIntArray( $this->pids );
				
				if ( count($_pid) > 0 )
				{
					$_pid = " IN(" . implode( ",", $_pid ) . ")";
				}
				else
				{
					$this->_showError( 'mod_no_posts_to_del', 10376.3 );
				}

				/* Make sure we're the author of these posts */
				$this->DB->build( array( 'select' => 'pid, author_id', 'from' => 'posts', 'where' => 'pid' . $_pid ) );
				$this->DB->execute();
				
				while( $_r = $this->DB->fetch() )
				{
					if( $_r['author_id'] == $this->memberData['member_id'] )
					{
						$_newPids[ $_r['pid'] ]	= $_r['pid'];
					}
				}
				
				$this->pids	= $_newPids;
			}
		}

		//-----------------------------------------
		// Check to make sure that this isn't the first post in the topic..
		//-----------------------------------------
		
		if( !count($this->pids) )
		{
			$this->_showError( 'mod_no_posts_to_del', 10376.2 );
		}
		
		if ( empty( $this->topic ) )
		{
			$_pid		= IPSLib::cleanIntArray( $this->pids );
			if ( count($_pid) > 0 )
			{
				$_pid = " IN(" . implode( ",", $_pid ) . ")";
			}
			else
			{
				$this->_showError( 'mod_no_posts_to_del', 10376.3 );
			}

			$this->DB->build( array(
				'select' 	=> 'p.pid',
				'from' 		=> array( 'posts' => 'p' ),
				'add_join'	=> array( array(
					'select'	=> 't.topic_firstpost',
					'from'		=> array( 'topics' => 't' ),
					'where'		=> 't.tid=p.topic_id',
					) ),
				'where'		=> 'pid' . $_pid ) );
			$this->DB->execute();
			while( $_r = $this->DB->fetch() )
			{				
				if ( $_r['topic_firstpost'] == $_r['pid'] )
				{ 
					$this->_showError( 'mod_delete_first_post', '10376-A' );
				}
			}
		}
		else
		{
			foreach( $this->pids as $p )
			{
				if ( $this->topic['topic_firstpost'] == $p )
				{ 
					$this->_showError( 'mod_delete_first_post', 10376 );
				}
			}
		}
		
		$this->_addModeratorLog( sprintf( $this->lang->words['multi_post_delete_mod_log'], count( $this->pids ), $this->topic['title'] ) );

		$this->modLibrary->postDelete( $this->pids, true );

		$this->modLibrary->clearModQueueTable( 'post', $this->pids );
	}
	
	/**
	 * Post multi-mod: Split topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiSplitTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );
		 
		if ( ! $this->fromSearch AND ! $this->topic['tid'] )
		{
			$this->_showError( 'mod_no_tid', 10377, 404 );
		}
		
		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		if ( $this->request['checked'] != 1 )
		{
			$jump_html	= $this->registry->getClass('class_forums')->buildForumJump(0,1,1);
			$posts		= array();

			//-----------------------------------------
			// Display the posty wosty's
			//-----------------------------------------
			
			$this->DB->build( array(
									  'select' => 'p.post, p.pid, p.post_date, p.author_id, p.author_name, p.use_emo, p.post_htmlstate',
									  'from'   => array( 'posts' => 'p' ),
									  'where'  => 'p.topic_id=' . $this->topic['tid'] . ' AND p.pid IN (' . implode( ',', $this->pids ) . ')',
									  'order'  => 'p.post_date',
									  'add_join'	=> array(
									  						array( 'select'	=> 'm.member_group_id, m.mgroup_others',
									  								'from'	=> array( 'members' => 'm' ),
									  								'where'	=> 'm.member_id=p.author_id',
									  							)
									  						)
							)  );
								 
			$post_query = $this->DB->execute();

			while ( $row = $this->DB->fetch($post_query) )
			{
				// This causes HTML to get cut off sometimes
				//$row['post']	= IPSText::truncate( $row['post'], 800 );
				$row['date']	= ipsRegistry::getClass( 'class_localization')->getDate( $row['post_date'], 'LONG' );
				
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->forum['use_html'] and $row['post_htmlstate'] ) ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->forum['use_ibc'];
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
				
				$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
				
				$posts[]		= $row;
			}
			
			/* Pass back for templates */
			$this->request['selectedpids'] = $this->pids;
			
			//-----------------------------------------
			// print my bottom, er, the bottom
			//-----------------------------------------

			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->splitPostForm( $this->forum, $this->topic, $posts, $jump_html );

			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
			$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
			$this->registry->getClass('output')->setTitle( $this->lang->words['st_top'].": ".$this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
		else
		{
			/* INIT */
			$topics = array();
			$topic = array();
			$forum = array();
			$pids  = array();
			$tids  = array();
			
			/* As of 3.1, posts can come from multiple topics due to the mod options on searching */
			$this->DB->build( array( 'select'   => 't.*',
									 'from'     => array( 'topics' => 't' ),
									 'where'    => 't.tid=p.topic_id',
									 'add_join' => array( array( 'select' => 'p.pid',
									 							 'from'   => array( 'posts' => 'p' ),
									 							 'where'  => "pid IN(" . implode( ",", $this->pids ). ")" ) ) ) );
									 							 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$pids[ $row['pid'] ]   = $row;
				$tids[ $row['tid'] ][] = $row['pid'];
				$topics[ $row['tid'] ] = $row;
			}
			
			//-----------------------------------------
			// PROCESS Check the input
			//-----------------------------------------
			
			if ( $this->request['title'] == "" )
			{
				$this->_showError( 'mod_need_title', 10378 );
			}

			$affected_ids = count( $this->pids );
			
			//-----------------------------------------
			// Do we have enough?
			//-----------------------------------------
			
			if ( $affected_ids < 1 )
			{
				$this->_showError( 'mod_not_enough_split', 10379 );
			}
			
			//-----------------------------------------
			// Do we choose too many?
			//-----------------------------------------
			
			if ( $this->topic['tid'] )
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}" ) );
				
				if ( $affected_ids >= $count['cnt'] )
				{
					$this->_showError( 'mod_too_much_split', 10380 );
				}
			}
			
			//-----------------------------------------
			// Check the forum we're moving this too
			//-----------------------------------------
			
			if ( $this->forum['id'] )
			{
				$this->request['fid'] = intval($this->request['fid']);
				
				if ( $this->request['fid'] != $this->forum['id'] )
				{
					$f = $this->registry->class_forums->allForums[ $this->request['fid'] ];
					
					if ( ! $f['id'] )
					{
						$this->_showError( 'mod_no_forum_move', 10381, 404 );
					}
				
					if ( !$f['sub_can_post'] )
					{
						$this->_showError( 'mod_forum_no_posts', 10382 );
					}
				}
			}
			
			/* Loop */
			foreach( $tids as $_tid => $pids )
			{
				//-----------------------------------------
				// Is first post queued?
				//-----------------------------------------
				
				$topic_approved	= 1;
				$topic          = $topics[ $_tid ];
				
				/* Reset topic */
				if ( $this->fromSearch )
				{
					$this->request['title']		= $this->lang->words['mod_from'] . " " . $topic['title'];
				}
				
				$first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																'from'	=> 'posts',
																'where'	=> 'topic_id=' . $_tid . " AND pid IN(" . implode( ",", $pids ). ")",
																'order'	=> $this->settings['post_order_column'] . ' ASC',
																'limit'	=> array(0,1),
														)		);
	
				if( $first_post['queued'] )
				{
					$topic_approved	= 0;
	
					$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $first_post['pid'] );
				}
				
				//-----------------------------------------
				// Complete a new dummy topic
				//-----------------------------------------
				
				if( $this->settings['etfilter_shout'] )
				{
					if( function_exists('mb_convert_case') )
					{
						if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
						{
							$this->request['title'] = mb_convert_case( $this->request['title'], MB_CASE_TITLE, $this->settings['gb_char_set'] );
						}
						else
						{
							$this->request['title'] = ucwords( strtolower( $this->request['title'] ) );
						}
					}
					else
					{
						$this->request['title'] = ucwords( strtolower( $this->request['title'] ) );
					}
				}
				
				$this->DB->insert( 'topics', array(
													'title'				=> $this->request['title'],
													'state'				=> 'open',
													'posts'				=> 0,
													'starter_id'		=> 0,
													'starter_name'		=> 0,
													'start_date'		=> time(),
													'last_poster_id'	=> 0,
													'last_poster_name'	=> 0,
													'last_post'			=> time(),
													'author_mode'		=> 1,
													'poll_state'		=> 0,
													'last_vote'			=> 0,
													'views'				=> 0,
													'forum_id'			=> $this->request['fid'],
													'approved'			=> $topic_approved,
													'pinned'			=> 0,
								)				);
									
				$new_topic_id = $this->DB->getInsertId();
		
				//-----------------------------------------
				// Move the posts
				//-----------------------------------------
				
				$this->DB->update( 'posts', array( 'topic_id' => $new_topic_id, 'new_topic' => 0 ), 'topic_id=' . $_tid . " AND pid IN(" . implode( ",", $pids ). ")" ); 
				
				//-----------------------------------------
				// Move the posts
				//-----------------------------------------

				$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$_tid}" );

				//-----------------------------------------
				// Update attachments
				//-----------------------------------------
				
				$this->DB->update( 'attachments', array( 'attach_parent_id' => $new_topic_id ), "attach_rel_module='post' AND attach_rel_id IN(" . implode( ",", $pids ) . ")" );
				
				$_cnt	= $this->DB->getAffectedRows();
				
				$this->DB->update( 'topics', array( 'topic_hasattach' => $_cnt ), 'tid=' . $new_topic_id );
				
				//-----------------------------------------
				// Rebuild the topics
				//-----------------------------------------
				
				$this->modLibrary->rebuildTopic( $new_topic_id );
				$this->modLibrary->rebuildTopic( $_tid );
	
				//-----------------------------------------
				// Update the forum(s)
				//-----------------------------------------
				
				$this->modLibrary->forumRecount($topic['forum_id']);
				
				if ( $topic['forum_id'] != $this->request['fid'] )
				{
					$this->modLibrary->forumRecount( $this->request['fid'] );
				}
				
				/* Run moderation sync */
				$this->modLibrary->runModSync( 'topicSplit', $pids, $topic['tid'], $new_topic_id );
				
				/* link http://community.invisionpower.com/tracker/issue-37361-when-splitting-topic-404/ */
				$this->request['st'] = 0;
				
				$this->_addModeratorLog( $this->lang->words['acp_split_topic'] . " '{$topic['title']}'" );
			}
		}
	}
	
	/**
	 * Post multi-mod: Merge posts
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiMergePost()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'delete_post' );

		if ( count( $this->pids ) < 2 )
		{
			$this->_showError( 'mod_only_one_pid', 10383 );
		}

		//-----------------------------------------
		// Form or print?
		//-----------------------------------------
		
		if ( !$this->request['checked'] )
		{
			//-----------------------------------------
			// Get post data
			//-----------------------------------------
			
			$master_post	= "";
			$dropdown		= array();
			$authors		= array();
			$seen_author	= array();
			$upload_html	= "";
			$seoTitle		= '';
			
			//-----------------------------------------
			// Grab teh posts
			//-----------------------------------------
			
			$this->DB->build( array(
									'select'	=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "p.pid IN (" . implode( ",", $this->pids ) . ")",
									'add_join'	=> array(
														array(
																'select'	=> 't.forum_id, t.title_seo',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
															)
														)
								)		);
			$outer = $this->DB->execute();
		
			while ( $p = $this->DB->fetch( $outer ) )
			{
				if ( IPSMember::checkPermissions('read', $p['forum_id'] ) == TRUE )
				{
					$master_post .= $p['post'] . "<br /><br />";
					
					$dropdown[]			= array( $p['pid'], ipsRegistry::getClass( 'class_localization')->getDate( $p['post_date'], 'LONG') ." (#{$p['pid']})" );
					
					if ( !in_array( $p['author_id'], $seen_author ) )
					{
						$authors[]		= array( $p['author_id'], "{$p['author_name']} (#{$p['pid']})" );
						$seen_author[]	= $p['author_id'];
					}
					
					$seoTitle	= $p['title_seo'];
				}
			}
			
			//-----------------------------------------
			// Get Attachment Data
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> '*',
									 'from'		=> 'attachments',
									 'where'	=> "attach_rel_module='post' AND attach_rel_id IN (" . implode( ",", $this->pids ) . ")" ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$row['image']		= $this->caches['attachtypes'][ $row['attach_ext'] ]['atype_img'];
				$row['size']		= IPSLib::sizeFormat( $row['attach_filesize'] );
				$row['attach_file']	= IPSText::truncate( $row['attach_file'], 50 );
				$attachments[]		= $row;
			}
			
			//-----------------------------------------
			// Print form
			//-----------------------------------------
			
			/* Load editor stuff */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$_editor = new $classToLoad();

			$_editor->setContent( trim($master_post) );

			$editor = $_editor->show( 'Post', array( 'autoSaveKey' => md5( 'merge-' . $this->topic['tid'] ), 'height' => 350 ) );
			
			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->mergePostForm( $editor, $dropdown, $authors, $attachments, $seoTitle );

			if ( $this->topic['tid'] )
			{
				$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
			}
			
			$this->registry->getClass('output')->addNavigation( $this->lang->words['cm_title'], '' );
			$this->registry->getClass('output')->setTitle( $this->lang->words['cm_title']  . ' - ' . ipsRegistry::$settings['board_name']);
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
		else
		{
			//-----------------------------------------
			// DO THE THING, WITH THE THING!!
			//-----------------------------------------
			
			$this->request['postdate'] =  intval($this->request['postdate']);
			
			if ( empty($this->request['selectedpids']) || empty($this->request['postdate']) || empty($this->request['Post']) )
			{
				$this->_showError( 'mod_merge_posts', 10384 );
			}
			
			/* Load editor stuff */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$_editor = new $classToLoad();
			
			/* remove saved content */
			if ( $this->memberData['member_id'] )
			{
				$_editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => md5( 'merge-' . $this->topic['tid'] ) ) );
			}
		
			IPSText::getTextClass('bbcode')->parse_smilies		= 1;
			IPSText::getTextClass('bbcode')->parse_html			= 0;
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parsing_section	= 'topics';

			$post = $_editor->process( $_POST['Post'] );

			$post = IPSText::getTextClass('bbcode')->preDbParse( $post );

			//-----------------------------------------
			// Post to keep...
			//-----------------------------------------
			
			$posts			= array();
			$author			= array();
			$post_to_delete	= array();
			$new_post_key	= md5(time());
			$topics			= array();
			$forums			= array();
			$append_edit	= 0;
			
			//-----------------------------------------
			// Grab teh posts
			//-----------------------------------------
			
			$this->DB->build( array(
									'select'	=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "p.pid IN (" . implode( ",", $this->pids ) . ")",
									'add_join'	=> array(
														array(
																'select'	=> 't.forum_id',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
															)
														)
							)		);
			$outer = $this->DB->execute();
			
			while ( $p = $this->DB->fetch($outer) )
			{
				$posts[ $p['pid'] ]			= $p;
				$topics[ $p['topic_id'] ]	= $p['topic_id'];
				$forums[ $p['forum_id'] ]	= $p['forum_id'];
				
				if ( $p['author_id'] == $this->request['postauthor'] )
				{
					$author = array( 'id' => $p['author_id'], 'name' => $p['author_name'] );
				}
				
				if ( $p['pid'] != $this->request['postdate'] )
				{
					$post_to_delete[] = $p['pid'];
				}
				
				if( $p['append_edit'] )
				{
					$append_edit = 1;
				}
			}
			
			//-----------------------------------------
			// Update main post...
			//-----------------------------------------
			
			$this->DB->update( 'posts', array(	'author_id'		=> $author['id'],
												'author_name'	=> $author['name'],
												'post'			=> $post,
												'post_key'		=> $new_post_key,
												'edit_time'		=> time(),
												'edit_name'		=> $this->memberData['members_display_name'],
												'append_edit'	=> ( $append_edit OR !$this->memberData['g_append_edit'] ) ? 1 : 0,
										  ), 'pid=' . $this->request['postdate']
						 );

			/* Run moderation sync */
			$this->modLibrary->runModSync( 'postMerge', $this->pids, $this->request['postdate'] );
			
			//-----------------------------------------
			// Fix attachments
			//-----------------------------------------
			
			$attach_keep	= array();
			$attach_kill	= array();
			
			foreach ( $_POST as $key => $value )
			{
				if ( preg_match( '/^attach_(\d+)$/', $key, $match ) )
				{
					if ( $this->request[ $match[0] ] == 'keep' )
					{
						$attach_keep[] = $match[1];
					}
					else
					{
						$attach_kill[] = $match[1];
					}
				}
			}
			
			$attach_keep	= IPSLib::cleanIntArray( $attach_keep );
			$attach_kill	= IPSLib::cleanIntArray( $attach_kill );
			
			//-----------------------------------------
			// Keep
			//-----------------------------------------
			
			if ( count( $attach_keep ) )
			{
				$this->DB->update( 'attachments', array( 'attach_rel_id'		=> $this->request['postdate'],
															'attach_post_key'	=> $new_post_key,
															'attach_member_id'	=> $author['id'] ), 'attach_id IN(' . implode( ",", $attach_keep ) . ')' );
			}
			
			//-----------------------------------------
			// Kill Attachments
			//-----------------------------------------
			
			if( count( $attach_kill ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$class_attach       =  new $classToLoad( $this->registry );
				$class_attach->type =  'post';
				$class_attach->init();
				
				$class_attach->bulkRemoveAttachment( $attach_kill, 'attach_id' );
			}
			
			//-----------------------------------------
			// Kill old posts
			//-----------------------------------------
			
			if ( count($post_to_delete) )
			{
				$this->DB->delete( 'posts', 'pid IN(' . implode( ",", $post_to_delete ) . ')' );
				
				IPSDeleteLog::removeEntries( $post_to_delete, 'post', TRUE );
			}
			
			foreach( $topics as $t )
			{
				$this->modLibrary->rebuildTopic( $t, 0 );
			}
			
			foreach( $forums as $f )
			{
				$this->modLibrary->forumRecount( $f );
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
			
			/* Clear the content cache */
			IPSContentCache::drop( 'post', $this->pids );
			
			$this->_addModeratorLog( sprintf( $this->lang->words['acp_merged_posts'], implode( ", ", $this->pids ) ) );
		}
	}
	
	/**
	 * If we have an option between normal / soft delete, show it.
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiDeletePostSplash()
	{
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );
		
		$canSoftDelete = $this->registry->getClass('class_forums')->canSoftDeletePosts( $this->forum['id'], array() );
		$canHardDelete = ( $this->memberData['g_is_supmod'] OR $this->moderator['delete_post'] OR $this->memberData['g_delete_own_posts'] ) ? true : false;

		/* First hurdle */
		if ( ! $canSoftDelete AND ! $canHardDelete )
		{
			$this->_showError();
		}
		
		/* If we can't soft delete, then just do normal deletion */
		if ( ! $canSoftDelete )
		{
			return $this->_multiDeletePost();
		}
		else
		{
			/* Show splash page */
			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->softDeleteSplashPosts( $this->forum, $this->topic, $this->pids, $canHardDelete );

			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
			$this->registry->getClass('output')->setTitle( sprintf( $this->lang->words['mm_delete_top_posts'], count($this->pids) )  . ' - ' . ipsRegistry::$settings['board_name']);
			
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
	}
	
	/**
	 * Close a topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _closeTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'close_topic', '', true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_open_close_posts'] )
			{
				$this->_showError( 'mod_no_close_topic', 10385, 403 );
			}
		}

		$this->modLibrary->topicClose($this->topic['tid']);
		
		$this->_addModeratorLog( $this->lang->words['acp_locked_topic'] );
	
		$this->registry->output->redirectScreen( $this->lang->words['p_closed'], $this->settings['base_url'] . "showforum=".$this->forum['id'] . ( $this->request['_from'] == 'forum' ? '&st=' . intval( $this->request['st'] ) : '' ), $this->forum['name_seo'], "showforum" );
	}
	
	/**
	 * Soft delete a topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _softDeleteTopicToggle( $delete )
	{
		$reason = trim( $this->request['deleteReason'] );
		$delete = ( $delete ) ? true : false;
		$lang   = ( $delete ) ? 'p_deleted' : 'p_topic_restored';
		
		if ( $delete )
		{
			$result = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->topic['forum_id'], $this->topic );
		}
		else
		{
			$result = $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $this->topic['forum_id'] );

		}
		
		if ( ! $result )
		{
			$this->_showError( 'mod_no_delete_topic', 103119.0 );
		}
		
		$this->modLibrary->topicToggleSoftDelete( array( $this->topic['tid'] ), $delete, $reason );
		
		$this->registry->output->redirectScreen( $this->lang->words[ $lang ], $this->settings['base_url'] . "showforum=" . $this->forum['id'] . '&st=' . intval( $this->request['st'] ), $this->forum['name_seo'], 'showforum' );
	}
	
	/**
	 * Delete a topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deleteTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_topic', '', true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_delete_own_topics'] )
			{
				$this->_showError( 'mod_no_delete_topic', 10386, 403 );
			}
		}

		// Do we have a linked topic to remove?
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => "state='link' AND moved_to='" . $this->topic['tid'] . '&' . $this->forum['id'] . "'" ) );
		$this->DB->execute();
		
		if ( $linked_topic = $this->DB->fetch() )
		{
			$this->DB->delete( 'topics', "tid=" . $linked_topic['tid'] );
			
			$this->modLibrary->forumRecount( $linked_topic['forum_id'] );
		}
		
		$this->modLibrary->topicDelete($this->topic['tid']);
		$this->_addModeratorLog( $this->lang->words['acp_deleted_a_topic'] );
		
		$this->modLibrary->clearModQueueTable( 'topic', $this->topic['tid'] );

		if( $this->request['return'] )
		{
			$_bits	= explode( ':', $this->request['return'] );
			
			if( count($_bits) AND $_bits[0] == 'modcp' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['p_deleted'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'topics' );
			}
		}

		$this->registry->output->redirectScreen( $this->lang->words['p_deleted'], $this->settings['base_url'] . "showforum=" . $this->forum['id'] . '&st=' . intval( $this->request['st'] ), $this->forum['name_seo'], 'showforum' );
	}

	/**
	 * Perma-Delete a topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deleteTopicPermanent()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_topic', '', true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_delete_own_topics'] )
			{
				$this->_showError( 'mod_no_delete_topic', 10386, 403 );
			}
		}

		// Do we have a linked topic to remove?
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => "state='link' AND moved_to='" . $this->topic['tid'] . '&' . $this->forum['id'] . "'" ) );
		$this->DB->execute();
		
		if ( $linked_topic = $this->DB->fetch() )
		{
			$this->DB->delete( 'topics', "tid=" . $linked_topic['tid'] );
			
			$this->modLibrary->forumRecount( $linked_topic['forum_id'] );
		}
		
		$this->modLibrary->topicDeleteFromDb($this->topic['tid']);
		$this->_addModeratorLog( $this->lang->words['acp_deleted_a_topic'] );
		
		$this->modLibrary->clearModQueueTable( 'topic', $this->topic['tid'] );

		if( $this->request['return'] )
		{
			$_bits	= explode( ':', $this->request['return'] );
			
			if( count($_bits) AND $_bits[0] == 'modcp' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['p_deleted'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'topics' );
			}
		}

		$this->registry->output->redirectScreen( $this->lang->words['p_deleted'], $this->settings['base_url'] . "showforum=" . $this->forum['id'] . '&st=' . intval( $this->request['st'] ), $this->forum['name_seo'], 'showforum' );
	}
	
	/**
	 * Perma-Delete an archived topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deleteArchivedTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_topic', '', true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_delete_own_topics'] )
			{
				$this->_showError( 'mod_no_delete_topic', 10386, 403 );
			}
		}
		
		$pids = array();
		
		/* Restore topic first */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore.php', 'classes_archive_restore' );
		$this->archiveRestore = new $classToLoad();
		
		$this->archiveRestore->setApp('forums');
		
		/* Get pids */
		$this->DB->build( array( 'select' => 'archive_id',
								 'from'   => 'forums_archive_posts',
								 'where'  => 'archive_topic_id=' . intval( $this->topic['tid'] ) ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$pids[] = $row['archive_id'];
		}
		
		if ( count( $pids ) )
		{
			/* Restore ... */
			$this->archiveRestore->restore( $pids );
		
			$this->DB->update( 'topics', array( 'topic_archive_status' => $this->registry->class_forums->fetchTopicArchiveFlag( 'exclude' ) ), 'tid=' . $this->topic['tid'] );
		}
		
		/* ... now delete */
		return $this->_deleteTopicPermanent();
	}
	
	/**
	 * Delete a topic (confirmation screen)
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deleteForm()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$canSoftDelete = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->forum['id'], $this->topic );

		if ( $canSoftDelete )
		{
			/* Send to form, then */
			$this->tids[ $this->topic['tid'] ] = $this->topic['tid'];
			
			return $this->_multiAlterDeleteSplash();
		}
		else
		{
			if( !$this->_genericPermissionCheck( 'delete_topic', '', true ) )
			{
				if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_delete_own_topics'] )
				{
					$this->_showError( 'mod_no_delete_topic', 10387, 403 );
				}
			}
	
			$this->output = $this->registry->getClass('output')->getTemplate('mod')->deleteTopicForm( $this->forum, $this->topic );
	
			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
			$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
			$this->registry->getClass('output')->setTitle( $this->lang->words['t_delete'] . ": " . $this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
		}
	}
	
	/**
	 * Display the topic history
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _topicHistory()
	{
		if ( ! $this->memberData['g_access_cp'] )
		{
			$this->_showError( 'no_permission', 103119.5 );
		}
		
		//-----------------------------------------
		// Admin only
		//-----------------------------------------
		
		if( !$this->memberData['g_access_cp'] )
		{
			$this->_showError( 'moderate_no_permission', 103119.2, 403 );
		}

		if ($this->topic['last_post'] == $this->topic['start_date'])
		{
			$avg_posts = 1;
		}
		else
		{
			$avg_posts = round( ($this->topic['posts'] + 1) / ((( $this->topic['last_post'] - $this->topic['start_date']) / 86400)), 1 );
		}
		
		if ($avg_posts < 0)
		{
			$avg_posts = 1;
		}
		
		if ($avg_posts > ( $this->topic['posts'] + 1) )
		{
			$avg_posts = $this->topic['posts'] + 1;
		}
		
		$mod_logs = array();
		
		// Do we have any logs in the mod-logs DB about this topic? eh? well?

		$this->DB->build( array( 
								'select'	=> 'l.*',
								'from'		=> array( 'moderator_logs' => 'l' ),
								'where'		=> 'l.topic_id=' . $this->topic['tid'],
								'order'		=> 'l.ctime DESC',
								'add_join'	=> array( array(
															'select' 	=> 'm.member_group_id, m.members_display_name, m.members_seo_name',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'l.member_id=m.member_id',
															'type'		=> 'left'
													)	),
												
						)	);
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			/* No member name? */
			$row['members_display_name'] = empty($row['members_display_name']) ? $row['member_name'] : $row['members_display_name'];
			
			$mod_logs[] = $row;
		}

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->topicHistory( $this->topic, $avg_posts, $mod_logs );

		foreach( $this->registry->class_forums->forumsBreadcrumbNav( $this->topic['forum_id'] ) as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}

		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
		$this->registry->getClass('output')->setTitle( $this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
	}
	
	/**
	 * Unsubscribe all form
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unsubscribeAllForm()
	{
		$this->_genericPermissionCheck();
		
		//-----------------------------------------
		// Get like class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$topics	= $_like->getCount( $this->topic['tid'] );

		if ( $topics )
		{
			$text = sprintf( $this->lang->words['ts_count'], $topics );
		}
		else
		{
			$text = $this->lang->words['ts_none'];
		}

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->unsubscribeForm( $this->forum, $this->topic, $text );

		foreach( $this->registry->class_forums->forumsBreadcrumbNav( $this->topic['forum_id'] ) as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}

		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['ts_title']." &gt; ".$this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
	}
	
	/**
	 * Unsubscribe all complete
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unsubscribeAll()
	{
		$this->_genericPermissionCheck();
		
		//-----------------------------------------
		// Get like class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->remove( $this->topic['tid'] );

		$this->registry->output->redirectScreen( $this->lang->words['ts_redirect'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']), $this->topic['title_seo'], 'showtopic' );
	}
	
	/**
	 * Merge two topics form
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mergeStart()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->mergeTopicsForm( $this->forum, $this->topic );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['mt_top'] . " " . $this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
	}
	
	/**
	 * Merge two topics
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mergeComplete()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );

		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		if ( $this->request['topic_url'] == "" or $this->request['title'] == "" )
		{
			$this->_showError( 'mod_missing_url_title', 10388 );
		}
		
		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------

		$old_id = $this->_getTidFromUrl();
		
		if ( !$old_id )
		{
			$this->_showError( 'mod_missing_old_topic', 10389 );
		}

		//-----------------------------------------
		// Get the topic from the DB
		//-----------------------------------------
		
		$old_topic = $this->DB->buildAndFetch( array( 'select' => 'tid, title, forum_id, last_post, last_poster_id, last_poster_name, posts, views, topic_hasattach, approved', 'from' => 'topics', 'where' => 'tid=' . intval($old_id) ) );

		if ( ! $old_topic['tid'] )
		{
			$this->_showError( 'mod_missing_old_topic', 10390 );
		}
		
		//-----------------------------------------
		// Did we try and merge the same topic?
		//-----------------------------------------
		
		if ( $old_id == $this->topic['tid'] )
		{
			$this->_showError( 'mod_same_topics', 10391 );
		}
		
		//-----------------------------------------
		// Do we have moderator permissions for this
		// topic (ie: in the forum the topic is in)
		//-----------------------------------------
		
		$pass = FALSE;
		
		if ( $this->topic['forum_id'] == $old_topic['forum_id'] )
		{
			$pass = TRUE;
		}
		else
		{
			if ( $this->memberData['g_is_supmod'] == 1 )
			{
				$pass = TRUE;
			}
			else if( $this->memberData['member_id'] )
			{
				$other_mgroups	= array();
				$_mgroup_others	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
				
				if( $_mgroup_others )
				{
					$other_mgroups = explode( ",", $_mgroup_others );
				}
				
				$other_mgroups[] = $this->memberData['member_group_id'];
				
				
				$this->DB->build( array( 'select'	=> 'mid',
												'from'	=> 'moderators',
												'where'	=> "forum_id LIKE '%,{$old_topic['forum_id']},%' AND (member_id='" . $this->memberData['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" ) );
											  
				$this->DB->execute();
				
				if ( $this->DB->getTotalRows() )
				{
					$pass = TRUE;
				}
			}
		}
		
		if ( $pass == FALSE )
		{
			// No, we don't have permission
			
			$this->_showError();
		}
		
		//-----------------------------------------
		// Sort out polls
		//-----------------------------------------
		
		/* Who has a poll? */
		$main_topic_poll = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid={$this->topic['tid']}" ) );
		$old_topic_poll = 	$this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid={$old_topic['tid']}" ) );	
		
		/* The old topic has a poll and the new one doesn't */
		if ( $old_topic_poll['pid'] and !$main_topic_poll['pid'] )
		{
			// Make that poll the poll for the master topic
			$this->DB->update( 'polls', array( 'tid' => $this->topic['tid'] ), "tid={$old_topic_poll}" );
			
			// Make the votes for that now
			$this->DB->update( 'voters', array( 'tid' => $this->topic['tid'] ), "tid={$old_topic_poll}" );
			
			// Let the master topic know that it has a poll now
			$this->DB->update( 'topics', array( 'poll_state' => 1 ), "tid={$this->topic['tid']}" );
		}
		
		/* They both have polls */
		elseif ( $old_topic_poll['pid'] and $main_topic_poll['pid'] )
		{
			// Have we selected one?
			if ( $this->request['chosenpolltid'] )
			{
				$chosenTid = intval( $this->request['chosenpolltid'] );
				
				// Remove the non chosen ones
				$this->DB->delete( 'polls', "tid={$this->topic['tid']}" );
				$this->DB->delete( 'polls', "tid={$old_topic_poll} AND tid <> {$chosenTid}" );
				$this->DB->delete( 'voters', "tid={$this->topic['tid']}" );
				$this->DB->delete( 'voters', "tid={$old_topic_poll} AND tid <> {$chosenTid}" );
				
				// Make the chosen poll the poll for the master topic
				$this->DB->update( 'polls', array( 'tid' => $this->topic['tid'] ), "tid={$chosenTid}" );
				
				// Make the votes for that now
				$this->DB->update( 'voters', array( 'tid' => $this->topic['tid'] ), "tid={$chosenTid}" );
				
				// Let the master topic know that it has a poll now
				$this->DB->update( 'topics', array( 'poll_state' => 1 ), "tid={$this->topic['tid']}" );
			}
			
			// No? Well ask them which one to keep
			else
			{
				ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_topic' ) );
				$this->output .= $this->registry->getClass('output')->getTemplate('mod')->mergeMultiplePolls( array( $main_topic_poll, $old_topic_poll ), ",{$main_topic_poll['tid']},{$old_topic_poll['tid']}" );
				$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
				$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
				$this->registry->getClass('output')->setTitle( $this->lang->words['mt_top']." ".$this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
				return false;
			}
		}
				
		//-----------------------------------------
		// Update the posts, remove old polls, subs and topic
		//-----------------------------------------
		
		/* Remove from deletion log */
		IPSDeleteLog::removeEntries( array( $old_topic['tid'] ), 'topic', TRUE );
		
		$this->DB->update( 'posts', array( 'topic_id' => $this->topic['tid'] ), 'topic_id=' . $old_topic['tid'] );
		$this->DB->delete( 'voters', "tid=" . $old_topic['tid'] );
		$this->DB->delete( 'topics', "tid=" . $old_topic['tid'] );

		//-----------------------------------------
		// Update attachments
		//-----------------------------------------
		
		$this->DB->update( 'attachments', array( 'attach_parent_id' => $this->topic['tid'] ), "attach_rel_module='post' AND attach_parent_id=" . $old_topic['tid'] );

		//-----------------------------------------
		// Get like class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->remove( $old_topic['tid'] );
		
		//-----------------------------------------
		// Tags
		//-----------------------------------------
		
		$this->registry->tags->moveTagsByMetaId( $old_topic['tid'], $this->topic['tid'] );
		
		// Resync to remove duplicate tags - see bug report 34475
		$tags = $this->registry->tags->getTagsByMetaId( $this->topic['tid'] );
		$this->registry->tags->replace( $tags['tags'], array( 'meta_id' => $this->topic['tid'] ) ); 
		
		//-----------------------------------------
		// Update the newly merged topic
		//-----------------------------------------
		
		$updater = array(	'title'			=> $this->request['title'],
							'views'			=> $old_topic['views'] + $this->topic['views']
						);
						
		if ( $old_topic['last_post'] > $this->topic['last_post'] )
		{
			$updater['last_post']			= $old_topic['last_post'];
			$updater['last_poster_name']	= $old_topic['last_poster_name'];
			$updater['seo_last_name']       = IPSText::makeSeoTitle( $old_topic['last_poster_name'] );
			$updater['last_poster_id']		= $old_topic['last_poster_id'];
		}
		
		if( $old_topic['topic_hasattach'] )
		{
			$updater['topic_hasattach']		= intval($this->topic['topic_hasattach']) + $old_topic['topic_hasattach'];
		}

		$this->DB->update( 'topics', $updater, 'tid=' . $this->topic['tid'] );
		
		//-----------------------------------------
		// Fix up the "new_topic" attribute.
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> 'pid, author_name, author_id, post_date',
										'from'	=> 'posts',
										'where'	=> "topic_id=" . $this->topic['tid'],
										'order'	=> 'post_date ASC',
										'limit'	=> array( 0,1 ) ) );
		
		$this->DB->execute();
		
		if ( $first_post = $this->DB->fetch() )
		{
			$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" );
			$this->DB->update( 'posts', array( 'new_topic' => 1 ), "pid={$first_post['pid']}" );
		}
		
		//-----------------------------------------
		// Reset the post count for this topic
		//-----------------------------------------
		
		$amode = $first_post['author_id'] ? 1 : 0;
		
		$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' );
		
		$this->DB->build( array( 'select'	=> 'COUNT(*) as posts',
										'from'	=> 'posts',
										'where'	=> $_queued . " AND topic_id=" . $this->topic['tid'] ) );
		
		$this->DB->execute();
		
		if ( $post_count = $this->DB->fetch() )
		{
			$post_count['posts']--; //Remove first post
			
			$this->DB->update( 'topics', array( 'posts'				=> $post_count['posts'],
													'starter_name'		=> $first_post['author_name'],
													'starter_id'		=> $first_post['author_id'],
													'start_date'		=> $first_post['post_date'],
													'author_mode'		=> $amode,
													'topic_firstpost'	=> $first_post['pid']
								) , 'tid=' . $this->topic['tid'] );
		}
		
		$this->modLibrary->rebuildTopic( $this->topic['tid'] );
				
		//-----------------------------------------
		// Update the forum(s)
		//-----------------------------------------
		
		$this->registry->class_forums->allForums[ $this->topic['forum_id'] ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $this->topic['forum_id'] );
		
		if ( $this->topic['forum_id'] != $old_topic['forum_id'] )
		{
			$this->registry->class_forums->allForums[ $old_topic['forum_id'] ]['_update_deletion'] = 1;
			$this->modLibrary->forumRecount( $old_topic['forum_id'] );
		}
		
		/* Run moderation sync */
		$this->modLibrary->runModSync( 'topicMerge', $old_topic['tid'], $this->topic['tid'] );
		
		$this->_addModeratorLog( sprintf( $this->lang->words['acp_merged_topic'], $old_topic['title'], $this->topic['title'] ) );
		
		$this->registry->output->redirectScreen( $this->lang->words['mt_redirect'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'], $this->topic['title_seo'], 'showtopic' );
	}
	
	/**
	 * Merge two topics
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _moveForm()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'move_topic' );

		$jump_html = $this->registry->getClass('class_forums')->buildForumJump(0,1,1);
				 								
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->moveTopicForm( $this->forum, $this->topic, $jump_html );
		
		foreach( $this->registry->class_forums->forumsBreadcrumbNav( $this->forum['id'] ) as $_nav )
		{
			$this->registry->output->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}
		$this->registry->output->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );

		$this->registry->getClass('output')->setTitle( $this->lang->words['t_move'].": ".$this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
	}
	
	/**
	 * Merge two topics
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _doMove()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'move_topic' );

		//-----------------------------------------
		// Check for input..
		//-----------------------------------------

		if ( !$this->request['move_id'] or $this->request['move_id'] == -1 )
		{
			$this->_showError( 'mod_no_move_forum', 10392 );
		}

		if ( $this->request['move_id'] == $this->request['f'] )
		{
			$this->_showError( 'mod_no_move_save', 10393 );
		}
		
		$source = intval($this->request['f']);
		$moveto = intval($this->request['move_id']);
		
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'id, sub_can_post, name, redirect_on', 'from' => 'forums', 'where' => "id IN(" . $source . ',' . $moveto . ")" ) );
		$this->DB->execute();
		
		if ($this->DB->getTotalRows() != 2)
		{
			$this->_showError( 'mod_no_move_forum', 10394 );
		}
		
		$source_name	= "";
		$dest_name		= "";
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		while ( $f = $this->DB->fetch() )
		{
			if ( $f['id'] == $source )
			{
				$source_name	= $f['name'];
			}
			else
			{
				$dest_name		= $f['name'];
			}
			
			if ( ( $f['sub_can_post'] != 1 ) OR $f['redirect_on'] == 1 )
			{
				$this->_showError( 'mod_forum_no_posts', 10395 );
			}
		}

		$this->modLibrary->topicMove( $this->topic['tid'], $source, $moveto, $this->request['leave'] == 'y' ? 1 : 0 );

		$this->_addModeratorLog( sprintf( $this->lang->words['acp_moved_a_topic'], $source_name, $dest_name ) );
		
		// Resync the forums..
		
		$this->registry->class_forums->allForums[ $source ]['_update_deletion'] = 1;
		$this->registry->class_forums->allForums[ $moveto ]['_update_deletion'] = 1;
		
		$this->modLibrary->forumRecount($source);
		$this->modLibrary->forumRecount($moveto);

		$this->registry->output->redirectScreen( $this->lang->words['p_moved'], $this->settings['base_url'] . "showforum=" . $this->forum['id'], $this->forum['name_seo'], 'showforum' );
	}

	/**
	 * Delete a single post
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deletePost()
	{
		// Get this post id.
		
		$this->request['p'] = intval( $this->request['p'] );
		
		$post = $this->DB->buildAndFetch( array( 'select' => 'pid, author_id, post_date, new_topic', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} and pid=" . $this->request['p'] ) );

		if ( !$post['pid'] )
		{
			$this->_showError( 'mod_no_delete_post_find', 10396 );
		}
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_post', '', true ) )
		{
			if( !$this->memberData['g_delete_own_posts'] OR $this->memberData['member_id'] != $post['author_id'] )
			{
				$this->_showError( 'mod_no_delete_post', 10397, 403 );
			}
		}
		
		//-----------------------------------------
		// Check to make sure that this isn't the first post in the topic..
		//-----------------------------------------
		
		if ( $post['new_topic'] == 1 )
		{
			$this->_showError( 'mod_delete_first_post', 10398 );
		}

		$this->modLibrary->postDelete( $this->request['p'] );
		$this->modLibrary->forumRecount( $this->forum['id'] );
		
		$this->modLibrary->clearModQueueTable( 'post', $post['pid'] );

		if( $this->request['return'] )
		{
			$_bits	= explode( ':', $this->request['return'] );
			
			if( count($_bits) AND $_bits[0] == 'modcp' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['post_deleted'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'posts' );
			}
		}

		$this->registry->output->redirectScreen( $this->lang->words['post_deleted'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']), $this->topic['title_seo'], 'showtopic' );
	}
	
	/**
	 * Show the edit topic title form
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _editForm()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'edit_topic' );
								
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->editTopicTitle( $this->forum, $this->topic );

		$navigation = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $this->forum['id'] );
		
		if( is_array( $navigation ) AND count( $navigation ) )
		{
			foreach( $navigation as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
			
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "showtopic={$this->topic['tid']}", $this->topic['title_seo'], 'showtopic' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_edit'].": ".$this->topic['title']  . ' - ' . ipsRegistry::$settings['board_name']);
	}
	
	/**
	 * Save the topic title edits
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _doEdit()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'edit_topic' );

		if ( trim($this->request['TopicTitle']) == "" )
		{
			$this->_showError( 'mod_no_topic_title', 10399 );
		}
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
		$_postClass  = new $classToLoad( $this->registry );
		
		$this->request['TopicTitle'] =  $_postClass->cleanTopicTitle( $this->request['TopicTitle']  );
		$this->request['TopicTitle'] =  trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['TopicTitle'] ) );

		$this->request['TopicDesc'] =  trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['TopicDesc'] ) );
		$this->request['TopicDesc'] =  IPSText::mbsubstr( $this->request['TopicDesc'], 0, 70  );
		
		$title_seo = IPSText::makeSeoTitle( $this->request['TopicTitle'] );
		
		$this->DB->update( 'topics', array( 'title' => $this->request['TopicTitle'], 'title_seo' => $title_seo ), 'tid=' . $this->topic['tid'] );

		$this->modLibrary->forumRecount( $this->forum['id'] );

		$this->_addModeratorLog( sprintf( $this->lang->words['acp_edit_title'], $this->topic['tid'], $this->topic['title'], $this->request['TopicTitle'] ) );
	
		$this->registry->output->redirectScreen( $this->lang->words['p_edited'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'], $title_seo, 'showtopic' );
	}
		
	/**
	 * Open a closed topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _openTopic()
	{
		if ( $this->topic['state'] == 'open' )
		{
			$this->_showError( 'mod_no_open_opened', 103100 );
		}
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'open_topic', '', true ) )
		{
			if( !$this->memberData['g_open_close_posts'] OR $this->topic['starter_id'] != $this->memberData['member_id'] )
			{
				$this->_showError( 'mod_no_open_perms', 103101, 403 );
			}
		}

		$this->modLibrary->topicOpen($this->topic['tid']);
		
		$this->_addModeratorLog( $this->lang->words['acp_opened_topic'] );
		
		$_st	= '&st=' . intval( $this->request['st'] );
		
		if( $this->request['_fromTopic'] )
		{
			$_st	= '';
		}
	
		$this->registry->output->redirectScreen( $this->lang->words['p_opened'], $this->settings['base_url'] . "showforum=" . $this->topic['forum_id'] . $_st, $this->forum['name_seo'], "showforum" );
	}
	
	/**
	 * Move topics to another forum from the prune popup tool
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _pruneMove()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		$this->_resetModerator( $this->forum['forum_id'] );
		
		$this->_genericPermissionCheck( 'mass_move' );
		
		///-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		define( 'IPS_FORCE_HTML_REDIRECT', true );
		$pergo		= intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 50;
		$max		= intval( $this->request['max'] );
		$current	= intval($this->request['current']);
		$maxdone	= $pergo + $current;
		$tid_array	= array();
		$starter	= trim( $this->request['starter'] );
		$state		= trim( $this->request['state'] );
		$posts		= intval( $this->request['posts'] );
		$dateline	= intval( $this->request['dateline'] );
		$source		= $this->forum['id'];
		$moveto		= intval($this->request['df']);
		$date		= 0;
		$ignore_pin	= intval( $this->request['ignore_pin'] );
				
		if( $dateline )
		{
			$date	= time() - $dateline*60*60*24;			
		}

		//-----------------------------------------
		// Carry on...
		//-----------------------------------------
		
		$dbPruneWhere = $this->modLibrary->sqlPruneCreate( $this->forum['id'], $starter, $state, $posts, $date, $ignore_pin );

		$this->DB->build( array(
									'select'	=> 'tid',
									'from'		=> 'topics',
									'where'		=> $dbPruneWhere,
									'limit'		=> array( 0, $pergo ),
							)		);
		$batch	= $this->DB->execute();
		
		//-----------------------------------------
		// Get tids
		//-----------------------------------------
		
		while ( $row = $this->DB->fetch($batch) )
		{
			$tid_array[] = $row['tid'];
		}
		
		//-----------------------------------------
		// Done?
		//-----------------------------------------
				
		if ( empty( $tid_array ) )
		{
			$this->_addModeratorLog( $this->lang->words['acp_mass_moved'] );
			
			//-----------------------------------------
			// Update forum deletion
			//-----------------------------------------
			
			$this->registry->class_forums->allForums[ $moveto ]['_update_deletion'] = 1;
			$this->registry->class_forums->allForums[ $source ]['_update_deletion'] = 1;
			
			//-----------------------------------------
			// Resync the forums..
			//-----------------------------------------
			
			$this->modLibrary->forumRecount($source);
			$this->modLibrary->forumRecount($moveto);
		
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->request[ 'check'] =  0 ;
			$this->_pruneStart( $this->registry->getClass('output')->getTemplate('mod')->simplePage( $this->lang->words['cp_results'], $this->lang->words['cp_result_move'] . ($max) ) );
			
			return;
		}
				
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		$f = $this->registry->class_forums->allForums[ $moveto ];
		
		if ( $f['sub_can_post'] != 1 )
		{
			$this->_showError( 'mod_forum_no_posts', 103102 );
		}
		
		$this->modLibrary->topicMove( $tid_array, $source, $moveto );
		
		//-----------------------------------------
		// Refresh..
		//-----------------------------------------
		
		$num_rows	= count($tid_array);
		
		$link  = "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_move&amp;df=" . $this->request['df'] . "&amp;pergo={$pergo}&amp;current={$maxdone}&amp;max={$max}";
		$link .= "&amp;starter={$starter}&amp;state={$state}&amp;posts={$posts}&amp;dateline={$dateline}&amp;ignore_pin={$ignore_pin}";
		$link .= "&amp;auth_key=".$this->member->form_hash;
		$done  = $current + $num_rows;
		$text  = sprintf( $this->lang->words['cp_batch_done'], $done, $max - $done );
		
		$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . $link );
	}
	
	/**
	 * Prune delete topics
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _pruneFinish()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'mass_prune' );
		
		//-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		define( 'IPS_FORCE_HTML_REDIRECT', true );
		$pergo		= intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 50;
		$max		= intval( $this->request['max'] );
		$current	= intval($this->request['current']);
		$maxdone	= $pergo + $current;
		$tid_array	= array();
		$starter	= trim( $this->request['starter'] );
		$state		= trim( $this->request['state'] );
		$posts		= intval( $this->request['posts'] );
		$dateline	= intval( $this->request['dateline'] );
		$date		= 0;
		$ignore_pin	= intval( $this->request['ignore_pin'] );
		
		if( $dateline )
		{
			$date	= time() - $dateline*60*60*24;			
		}
		
		//-----------------------------------------
		// Carry on...
		//-----------------------------------------
		
		$dbPruneWhere = $this->modLibrary->sqlPruneCreate( $this->forum['id'], $starter, $state, $posts, $date, $ignore_pin );

		$this->DB->build( array(
									'select'	=> 'tid',
									'from'		=> 'topics',
									'where'		=> $dbPruneWhere,
									'limit'		=> array( 0, $pergo ),
							)		);
		$batch	= $this->DB->execute();
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		$num_rows = $this->DB->getTotalRows($batch);
		
		if ( ! $num_rows )
		{
			if ( !$current )
			{
				$this->_showError( 'mod_no_prune_topics', 103103 ); 
			}
		}
		
		//-----------------------------------------
		// Get tiddles
		//-----------------------------------------
		
		while ( $tid = $this->DB->fetch($batch) )
		{
			$tid_array[] = $tid['tid'];
		}
		
		$this->modLibrary->topicDelete($tid_array);
		
		//-----------------------------------------
		// Show results or refresh..
		//-----------------------------------------
		
		if ( ! $num_rows )
		{
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->_addModeratorLog( $this->lang->words['acp_pruned_forum'] );
			
			$this->request[ 'check'] =  0 ;
			
			$this->_pruneStart( $this->registry->getClass('output')->getTemplate('mod')->simplePage( $this->lang->words['cp_results'], $this->lang->words['cp_result_del'] . ($max)  ) );
		}
		else
		{
			$link  = "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_finish&amp;pergo={$pergo}&amp;current={$maxdone}&amp;max={$max}";
			$link .= "&amp;starter={$starter}&amp;state={$state}&amp;posts={$posts}&amp;dateline={$dateline}&amp;ignore_pin={$ignore_pin}";
			$link .= "&amp;auth_key=".$this->member->form_hash;
			$done  = $current + $num_rows;
			$text  = sprintf( $this->lang->words['cp_batch_done'], $done, $max - $done );
			
			$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . $link );
		}
	}
	
	/**
	 * Prune popup form
	 *
	 * @param	string		HTML output
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _pruneStart( $complete_html="" )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
				
		$this->_resetModerator( $this->forum['id'] );
		
		$this->_genericPermissionCheck( 'mass_prune' );
		
		$confirm_data = array( 'show' => '' );
		
		//-----------------------------------------
		// Check per go
		//-----------------------------------------
		
		$this->request[ 'pergo'		] =  $this->request['pergo']		? intval( $this->request['pergo'] )	: 50;
		$this->request[ 'posts'		] =  $this->request['posts']		? intval( $this->request['posts'] )	: '';
		$this->request[ 'member'	] =  $this->request['member']		? $this->request['member']			: '' ;
		$this->request[ 'determine'	] =  $this->request['determine']	? $this->request['determine']		: '' ;
		$this->request[ 'dateline'	] =  $this->request['dateline']		? $this->request['dateline']		: '' ;
		
		//-----------------------------------------
		// Are we checking first?
		//-----------------------------------------
		
		if ( $this->request['check'] AND $this->request['check'] == 1 )
		{
			$link		= "&amp;pergo=" . $this->request['pergo'];
			$link_text	= $this->lang->words['cp_prune_dorem'];
			
			$tcount = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as tcount', 'from' => 'topics', 'where' => "forum_id={$this->forum['id']} AND approved < 2" ) );
			
			$db_query = "";
			
			//-----------------------------------------
			// date...
			//-----------------------------------------
		
			if ($this->request['dateline'])
			{
				$date		= time() - $this->request['dateline']*60*60*24;
				$db_query	.= " AND last_post < $date";
				
				$link		.= "&amp;dateline={$this->request['dateline']}";
			}
			
			//-----------------------------------------
			// Member...
			//-----------------------------------------
			
			if ( $this->request['member'] )
			{
				$mem = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_display_name='" . $this->request['member'] . "'" ) );

				if ( !$mem['member_id'] )
				{
					$this->_showError( 'mod_no_prune_member', 103104 );
				}
				else
				{
					$db_query	.= " AND starter_id=" . $mem['member_id'];
					$link		.= "&amp;starter={$mem['member_id']}";
				}
			}
			
			//-----------------------------------------
			// Posts / Topic type
			//-----------------------------------------
			
			if ($this->request['posts'])
			{
				$db_query	.= " AND posts < " . intval($this->request['posts']);
				$link		.= "&amp;posts=" . $this->request['posts'];
			}
			
			if ($this->request['topic_type'] != 'all')
			{
				$db_query	.= " AND state='".$this->request['topic_type']."'";
				$link		.= "&amp;state=" . $this->request['topic_type'];
			}
			
			if ($this->request['ignore_pin'] == 1)
			{
				$db_query	.= " AND pinned <> 1";
				$link		.= "&amp;ignore_pin=1";
			}
			
			$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
																'from'	=> 'topics',
																'where'	=> "forum_id=" . $this->forum['id'] . ' AND approved < 2' . $db_query ) );
			
			//-----------------------------------------
			// Prune?
			//-----------------------------------------

			if ( $this->request['df'] == 'prune' )
			{
				$link = "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_finish&amp;" . $link;
			}
			else
			{
				if ( $this->request['df'] == $this->forum['id'] )
				{
					$this->_showError( 'mod_no_move_save', 103105 );
				}
				else if ( $this->request['df'] == -1 )
				{
					$this->_showError( 'mod_no_move_forum', 103106 );
				}
				
				$link		= "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_move&amp;df=" . $this->request['df'] . $link;
				$link_text	= $this->lang->words['cp_prune_domove'];
			}
			
			//-----------------------------------------
			// Build data
			//-----------------------------------------
			
			$confirm_data = array( 'tcount'		=> $tcount['tcount'],
								   'count'		=> $count['count'],
								   'link'		=> $link . '&amp;max=' . $count['count'],
								   'link_text'	=> $link_text,
								   'show'		=> 1 );
		}

		$html_forums .= $this->registry->getClass('class_forums')->buildForumJump(0,1,1);

		//-----------------------------------------
		// Make current destination forum this one if selected
		// before
		//-----------------------------------------
		
		if ( $this->request['df'] )
		{
			$html_forums = str_replace( '<option value="' . intval($this->request['df']) . '"', '<option value="' . intval($this->request['df']) . '" selected="selected"', $html_forums );
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->pruneSplash( $this->forum, $html_forums, $confirm_data, $complete_html );
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] );
		
		$nav	= $this->registry->class_forums->forumsBreadcrumbNav( $this->forum['id'] );

		if ( is_array( $nav ) AND count( $nav ) )
		{
			foreach( $nav as $_nav )
			{
				$this->registry->output->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}

		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Topic multi-moderation
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiTopicModify()
	{
		/* init */
		$done = false;
				
		/* Check these first */
		switch ( $this->request['tact'] )
		{
			case 't_approve':
				$this->_topicsManage('approve_unapproved');
				$done = true;
			break;
			case 't_delete_approve':
				$this->_topicsManage('delete_unapproved');
				$done = true;
			break;
			case 't_restore':
				$this->_topicsManage('restore_deleted');
				$done = true;
			break;
			case 't_delete_softed':
				$this->_topicsManage('delete_deleted');
				$done = true;
			break;
		}
			
		$this->tids  = $this->_getIds();

		if( count( $this->tids ) AND $done !== true )
		{
			switch ( $this->request['tact'] )
			{
				case 'close':
					$this->_multiAlterTopics('close_topic', "state='closed'");
				break;
				case 'open':
					$this->_multiAlterTopics('open_topic', "state='open'");
				break;
				case 'pin':
					$this->_multiAlterTopics('pin_topic', "pinned=1");
				break;
				case 'unpin':
					$this->_multiAlterTopics('unpin_topic', "pinned=0");
				break;
				case 'approve':
					$this->_multiAlterTopics('topic_q', $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) );
				break;
				case 'unapprove':
					$this->_multiAlterTopics('topic_q', $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'hidden' ), '' ) );
				break;
				case 'delete':
					$this->_multiAlterDeleteSplash();
				break;
				case 'deletedo':
					$this->_multiAlterTopics('delete_topic');
				break;
				case 'sdelete':
					$this->_multiSoftDeleteTopics(1, $this->request['deleteReason']);
				break;
				case 'sundelete':
					$this->_multiSoftDeleteTopics(0);
				break;
				case 'move':
					$this->_multiStartCheckedMove();
				return;
				break;
				case 'domove':
					$this->_multiCompleteCheckedMove();
				break;
				case 'merge':
					if ( $this->_multiTopicMerge() === FALSE )
					{
						return;
					}
				break;
				default:
					$this->_multiTopicMmod();
				break;
			}
		}

		IPSCookie::set( 'modtids', '', 0 );
		
		/* From search? */
		if ( $this->fromSearch AND $this->returnUrl )
		{
			if ( $this->request['nr'] )
			{
				$this->registry->output->silentRedirect( $this->returnUrl );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_topics'], $this->returnUrl  );
			}

		}
		else if( $this->request['return'] )
		{
			$_bits	= explode( ':', $this->request['return'] );
			
			if( count($_bits) AND $_bits[0] == 'modcp' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'topics' );
			}
		}
		else if ( $this->forum['id'] )
		{
			$url = "showforum=" . $this->forum['id'];
			$url = ( $this->request['st'] ) ? "showforum=" . $this->forum['id'] . '&amp;st=' . $this->request['st'] : $url;
			
			if ( $this->request['nr'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . $url, $this->forum['name_seo'], 'showforum' );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_topics'], $this->settings['base_url'] . $url, $this->forum['name_seo'], 'showforum' );
			}
		}
	}
	
	/**
	 * If we have an option between normal / soft delete, show it.
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiAlterDeleteSplash()
	{
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_forums' ), 'forums' );
		
		$canSoftDelete = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->forum['id'], $this->topic );
		$canHardDelete = ( $this->memberData['g_is_supmod'] OR $this->moderator['delete_topic'] OR ( $this->memberData['g_delete_own_topics'] AND $this->memberData['member_id'] == $this->topic['starter_id'] ) ) ? true : false;
		
		/* First hurdle */
		if ( ! $canSoftDelete AND ! $canHardDelete )
		{
			$this->_showError();
		}
		
		/* If we can't soft delete, then just do normal deletion */
		if ( ! $canSoftDelete )
		{
			return $this->_multiAlterTopics('delete_topic');
		}
		else
		{
			/* Show splash page */
			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->softDeleteSplash( $this->forum, $this->tids, $canHardDelete );

			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
			$this->registry->getClass('output')->setTitle( sprintf( $this->lang->words['mm_delete_top'], count($this->tids) )  . ' - ' . ipsRegistry::$settings['board_name']);
			
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
	}
	
	/**
	 * Merge two or more topics
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiTopicMerge()
	{
		$this->_genericPermissionCheck( 'split_merge' );
		
		if ( count($this->tids) < 2 )
		{
			$this->_showError( 'mod_topics_merge_two', 103107 );
		}
		
		//-----------------------------------------
		// Get the topics in ascending date order
		//-----------------------------------------
		
		$topics		= array();
		$tids       = array();
		$merge_ids	= array();
		$newViews	= 0;
		
		$this->DB->build( array( 'select' => 'tid, forum_id, approved, views', 'from' => 'topics', 'where' => 'tid IN (' . implode( ",", $this->tids ) . ')', 'order' => 'start_date asc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$topics[]	= $r;
			$tids[]     = $r['tid'];
			$newViews	+= $r['views'];
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count($topics) < 2 )
		{
			$this->_showError( 'mod_topics_merge_two', 103108 );
		}
		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------
		
		foreach( $topics as $topic )
		{
			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( 'split_merge' );
		}
		
		//-----------------------------------------
		// Get topic ID for first topic 'master'
		//-----------------------------------------
		
		$first_topic	= array_shift( $topics );
		$main_topic_id	= $first_topic['tid'];
		$unapproved		= array();

		foreach( $topics as $t )
		{
			/* Add to unapproved array */
			if( ! $t['approved'] )
			{
				$unapproved[] = $t['tid'];
			}
			
			$merge_ids[] = $t['tid'];
		}
				
		//-----------------------------------------
		// Sort out polls
		//-----------------------------------------
		
		$polls = array();
		
		/* Who has a poll? */
		$this->DB->build( array( 'select' => '*', 'from' => 'polls', 'where' => "tid={$main_topic_id} OR tid IN(" . implode( ',', $merge_ids ) . ")" ) );
		$this->DB->execute();

		while ( $row = $this->DB->fetch() )
		{
			$polls[ $row['tid'] ] = $row;
		}
		
		/* We have one poll */
		if ( count($polls) == 1 )
		{
			/* Update the poll ONLY if it's not our main one */
			if ( empty($polls[ $main_topic_id ]) )
			{
				$this->DB->update( 'polls',  array( 'tid' => $main_topic_id ), "tid IN(" . implode( ',', $merge_ids ) . ")" );
				$this->DB->update( 'voters', array( 'tid' => $main_topic_id ), "tid IN(" . implode( ',', $merge_ids ) . ")" );
				$this->DB->update( 'topics', array( 'poll_state' => 1 ), "tid={$main_topic_id}" );
			}
		}
		/* There's more than one poll to deal with */
		elseif (count($polls) > 1 )
		{
			// Have we selected one?
			$chosenTid = intval( $this->request['chosenpolltid'] );
			
			if ( $chosenTid )
			{
				/* Chosen one is not from the main topic? */
				if ( $chosenTid != $main_topic_id )
				{
					$this->DB->delete( 'polls', "tid={$main_topic_id}" );
					$this->DB->delete( 'voters', "tid={$main_topic_id}" );
					
					// Update poll status here if the chosen poll is not from the main topic otherwise there's no need to.. right? ;P
					$this->DB->update( 'topics', array( 'poll_state' => 1 ), "tid={$main_topic_id}" );
				}
				
				// Remove the non chosen ones
				$this->DB->delete( 'polls',  "tid IN(" . implode( ',', $merge_ids ) . ")" . " AND tid <> {$chosenTid}" );
				$this->DB->delete( 'voters', "tid IN(" . implode( ',', $merge_ids ) . ")" . " AND tid <> {$chosenTid}" );
				
				// Update the master topic
				$this->DB->update( 'polls',  array( 'tid' => $main_topic_id ), "tid={$chosenTid}" );
				$this->DB->update( 'voters', array( 'tid' => $main_topic_id ), "tid={$chosenTid}" );
			}
			
			// No? Well ask them which one to keep
			else
			{
				ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_topic' ) );
				$this->output .= $this->registry->getClass('output')->getTemplate('mod')->mergeMultiplePolls( $polls, $this->request['selectedtids'] );
				return false;
			}
		}
		
		//-----------------------------------------
		// Update the posts, subs and topic
		//-----------------------------------------
		
		/* Bug #20829: If the topic is not approved, set all the posts unapproved so that they are not displayed after the merge */
		if( is_array( $unapproved ) && count( $unapproved ) )
		{
			$this->DB->update( 'posts', array( 'queued' => 1 ), 'topic_id IN (' . implode( ",", $unapproved ) . ")" );
		}
		
		$this->DB->update( 'posts', array( 'topic_id' => $main_topic_id ), 'topic_id IN (' . implode( ",", $merge_ids ) . ")" );
		$this->DB->update( 'topics', array( 'views' => $newViews ), 'tid=' . $main_topic_id );
		$this->DB->delete( 'voters', "tid IN (" . implode( ",", $merge_ids ) . ")" );
		$this->DB->delete( 'topics', "tid IN (" . implode( ",", $merge_ids ) . ")" );

		//-----------------------------------------
		// Get like class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->remove( $merge_ids );
		
		//-----------------------------------------
		// Remove from delete log
		//-----------------------------------------
		
		IPSDeleteLog::removeEntries( $merge_ids, 'topic', TRUE );
		
		//-----------------------------------------
		// Update the newly merged topic
		//-----------------------------------------
		
		$this->modLibrary->rebuildTopic( $main_topic_id );
		
		$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $this->forum['id'] );
		$this->cache->rebuildCache( 'stats', 'global' );
		
		/* Tags */
		$this->registry->tags->moveTagsByMetaId( $tids, $main_topic_id );
		
		/* Make read */
		$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $first_topic['forum_id'], 'itemID' => $main_topic_id, 'markDate' => time(), 'containerLastActivityDate' => $this->registry->class_forums->allForums[  $first_topic['forum_id'] ]['last_post'] ) );
		
		/* Run Sync */
		foreach ( $merge_ids as $mid )
		{
			$this->modLibrary->runModSync( 'topicMerge', $mid, $main_topic_id );
		}
		
		/* Log */
		$this->_addModeratorLog( sprintf( $this->lang->words['multi_topic_merge_mod_log'], count( $topics ) ) );
	}
	
	/**
	 * Alter multiple topics at once
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiAlterTopics( $mod_action="", $sql="" )
	{
		if ( ! $mod_action )
		{
			$this->_showError( 'mod_um_what_now', 103109 );
		}

		$this->_genericPermissionCheck( $mod_action );
		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------
		
		$topics	= array();
		$forums	= array();

		$this->DB->build( array( 'select' => 'tid, forum_id, title', 'from' => 'topics', 'where' => 'tid IN (' . implode( ",", $this->tids ) . ')' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$topics[ $r['tid'] ] = $r;
		}
		
		foreach( $topics as $topic )
		{
			$forums[ $topic['forum_id'] ]	= $topic['forum_id'];

			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( $mod_action );
		}

		if ( $mod_action != 'delete_topic' )
		{
			$this->DB->buildAndFetch( array( 'update' => 'topics', 'set' => $sql, 'where' => "tid IN(" . implode( ",", $this->tids ) . ") AND state!='link'" ) );
			
			$this->_addModeratorLog( sprintf( $this->lang->words['acp_altered_topics'], $sql, implode( ", ", $this->tids) ) );

			if( $mod_action == 'topic_q' AND trim($sql) == 'approved=1' )
			{
				$this->modLibrary->clearModQueueTable( 'topic', $this->tids, true );
			}
		}
		else
		{
			$this->modLibrary->topicDelete( $this->tids );
			
			foreach( $this->tids as $_tid )
			{
				$this->request['t']		= $_tid;
				$this->request['f']		= $topics[ $_tid ]['forum_id'];
				$this->topic['title']	= $topics[ $_tid ]['title'];

				$this->_addModeratorLog( sprintf( $this->lang->words['acp_deleted_topics'], $_tid ) );
			}
			
			$this->request['t']		= 0;
			$this->request['f']		= 0;
			$this->topic['title']	= '';

			$this->modLibrary->clearModQueueTable( 'topic', $this->tids );
		}
		
		if ( $mod_action == 'delete_topic' or $mod_action == 'topic_q' )
		{
			foreach( $forums as $forum )
			{
				$this->registry->class_forums->allForums[ $forum ]['_update_deletion'] = 1;
				
				$this->modLibrary->forumRecount( $forum );
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		
		/* Tags */
		if ( $mod_action == 'topic_q' )
		{
			if ( $sql == 'approved=1' )
			{
				$this->registry->tags->updateVisibilityByMetaId( $this->tids, 1 );
			}
			else
			{
				$this->registry->tags->updateVisibilityByMetaId( $this->tids, 0 );
			}
		}
	}
	
	/**
	 * Alter multiple topics at once
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiSoftDeleteTopics( $delete=1, $reason='' )
	{
		$delete = ( $delete ) ? true : false;
		
		if ( $delete )
		{
			if( !count($this->topic) )
			{
				$finalResult	= false;
				
				$this->DB->build( array( 'select' => 'tid,starter_id', 'from' => 'topics', 'where' => 'tid IN(' . implode( ',', $this->tids ) . ')' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$result = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->forum['id'], $r );
					
					if( $result )
					{
						$this->tids[ $r['tid'] ]	= $r['tid'];
						$finalResult				= true;
					}
				}
				
				$result	= $finalResult;
			}
			else
			{
				$result = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->forum['id'], $this->topic );
			}
		}
		else
		{
			$result = $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $this->forum['id'] );

		}

		if ( ! $result )
		{
			$this->_showError( 'moderate_no_permission', 103119.4 );
		}
		
		/* Ensure we check for a single topic id */
		if ( ! $this->fromSearch )
		{
			$this->modLibrary->topicToggleSoftDelete( $this->tids, $delete, $reason, $this->topic['tid'] );
		}
		else
		{
			$this->modLibrary->topicToggleSoftDelete( $this->tids, $delete, $reason );
		}
		
		/* Tagging */
		$this->registry->tags->updateVisibilityByMetaId( $this->tids, $delete );
	}
	
	/**
	 * Show the form to move topics
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiStartCheckedMove()
	{
		$this->_genericPermissionCheck( 'move_topic' );

		$jump_html	= $this->registry->getClass('class_forums')->buildForumJump(0,1,1);
		$topics		= array();

		$this->DB->build( array( 'select' => 'title, tid, forum_id', 'from' => 'topics', 'where' => "tid IN(" . implode( ",", $this->tids ) . ")" ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$topics[] = $row;
		}
		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------

		foreach( $topics as $topic )
		{
			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( 'move_topic' );
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->moveTopicsForm( $this->forum, $jump_html, $topics );

		if( $this->forum['name'] )
		{
			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
		}
		else
		{
			$this->registry->getClass('output')->addNavigation( $this->lang->words['cp_tmove_start'], '' );
		}
		
		$this->registry->getClass('output')->setTitle( $this->lang->words['cp_ttitle']  . ' - ' . ipsRegistry::$settings['board_name']);
	}
	
	/**
	 * Complete the topic move
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiCompleteCheckedMove()
	{
		$this->_genericPermissionCheck( 'move_topic' );

		$add_link	= $this->request['leave'] == 'y' ? 1 : 0;
		$dest_id	= intval($this->request['df']);
		$topics		= array();
 		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => 'tid IN (' . implode( ",", $this->tids ) . ')', 'order' => 'start_date asc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$topics[ $r['forum_id'] ][] = $r['tid'];
		}
		
		foreach( $topics as $forum_id => $_topics )
		{
			$this->_resetModerator( $forum_id );
			
			$this->_genericPermissionCheck( 'move_topic' );
		}
 			
		//-----------------------------------------
		// Check for input..
		//-----------------------------------------
		
		if ( !$dest_id OR $dest_id == -1 )
		{
			$this->_showError( 'mod_no_forum_move', 103110 );
		}

		$_newTopics		= array();
		$source_id		= 0;
		$_allSources	= array();
		
		foreach( $topics as $forum_id => $_topics )
		{
			if ( $forum_id != $dest_id )
			{
				$source_id					= $forum_id;	// We'll just use the last one found for logs..
				$_allSources[ $forum_id ]	= $forum_id;
				$_newTopics[ $forum_id ]	= $_topics;
			}
		}
		
		$topics	= $_newTopics;
		
		//-----------------------------------------
		// Some basic details
		//-----------------------------------------
		
		$_destination	= $this->registry->class_forums->getForumById( $dest_id );
		$_source		= $this->registry->class_forums->getForumById( $source_id );
		
		if( !$_destination['sub_can_post'] OR $_destination['redirect_on'] )
		{
			$this->_showError( 'mod_forum_no_posts', 103112 );
		}

		$source_name	= $_source['name'];
		$dest_name		= $_destination['name'];

		//-----------------------------------------
		// If all topics are in same source forum, just call once
		//-----------------------------------------
		
		if( count($_allSources) == 1 )
		{
			$this->modLibrary->topicMove( $this->tids, $source_id, $dest_id, $add_link );
		}
		
		//-----------------------------------------
		// Otherwise call once per source..
		//-----------------------------------------
		
		else
		{
			foreach( $topics as $forum_id => $_topicIds )
			{
				$this->modLibrary->topicMove( $_topicIds, $forum_id, $dest_id, $add_link );
			}
		}

		//-----------------------------------------
		// Resync the forums..
		//-----------------------------------------
		
		$this->registry->class_forums->allForums[ $dest_id ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $dest_id );
		
		foreach( $_allSources as $_sourceId )
		{
			$this->registry->class_forums->allForums[ $_sourceId ]['_update_deletion'] = 1;
			$this->modLibrary->forumRecount( $_sourceId );
		}

		$this->_addModeratorLog( sprintf( $this->lang->words['acp_moved_topics'], $source_name, $dest_name ) );
	}
	
	/**
	 * Topic multi-moderation
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _multiTopicMmod()
	{
		//-----------------------------------------
		// It's tea time
		//-----------------------------------------
		
		if ( ! strstr( $this->request['tact'], 't_' ) OR ! count($this->tids) )
		{
			$this->_showError( 'mod_stupid_beggar', 103114 );
		}
		
		$mm_id	= intval( str_replace( 't_', '', $this->request['tact'] ) );
		
		//-----------------------------------------
		// Init modfunc module
		//-----------------------------------------
		
		$this->modLibrary->init( $this->forum, "", $this->moderator );
		
		//-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		if ( $this->modLibrary->mmAuthorize() != TRUE )
		{
			$this->_showError( 'mod_no_multimod', 103115 );
		}

		$mm_data	= $this->caches['multimod'][ $mm_id ];

		
		if ( ! $mm_data )
		{
			$this->_showError( 'mod_no_mm_id', 103116, 404 );
		}
		
		//-----------------------------------------
		// Does this forum have this mm_id
		//-----------------------------------------
		
		if ( $this->modLibrary->mmCheckIdInForum( $this->forum['id'], $mm_data ) != TRUE )
		{
			$this->_showError( 'mod_no_multimod', 103117, 404 );
		}
		
		//-----------------------------------------
		// Still here? We're damn good to go sir!
		//-----------------------------------------
		
		$this->modLibrary->stmInit();
		
		//-----------------------------------------
		// Open close?
		//-----------------------------------------
		
		if ( $mm_data['topic_state'] != 'leave' )
		{
			if ( $mm_data['topic_state'] == 'close' )
			{
				$this->modLibrary->stmAddClose();
			}
			else if ( $mm_data['topic_state'] == 'open' )
			{
				$this->modLibrary->stmAddOpen();
			}
		}
		
		//-----------------------------------------
		// pin no-pin?
		//-----------------------------------------
		
		if ( $mm_data['topic_pin'] != 'leave' )
		{
			if ( $mm_data['topic_pin'] == 'pin' )
			{
				$this->modLibrary->stmAddPin();
			}
			else if ( $mm_data['topic_pin'] == 'unpin' )
			{
				$this->modLibrary->stmAddUnpin();
			}
		}
		
		//-----------------------------------------
		// Approve / Unapprove
		//-----------------------------------------
		
		if ( $mm_data['topic_approve'] )
		{
			if ( $mm_data['topic_approve'] == 1 )
			{
				$this->modLibrary->stmAddApprove();
			}
			else if ( $mm_data['topic_approve'] == 2 )
			{
				$this->modLibrary->stmAddUnapprove();
			}
		}
		
		//-----------------------------------------
		// Update what we have so far...
		//-----------------------------------------
		
		$this->modLibrary->stmExec( $this->tids );
		
		//-----------------------------------------
		// Topic title (1337 - I am!)
		//-----------------------------------------

		if( $mm_data['topic_title_st'] OR $mm_data['topic_title_end'] )
		{
			$this->DB->update( 'topics', 'title=' . $this->DB->buildConcat( array( 
																							array( $mm_data['topic_title_st'], 'string' ), 
																							array( 'title' ), 
																							array( $mm_data['topic_title_end'], 'string' ) 
																				)	 ),
								"tid IN(" . implode( ',', $this->tids ) . ")", false, true
							);
		}

		//-----------------------------------------
		// Add reply?
		//-----------------------------------------
		
		if ( $mm_data['topic_reply'] and $mm_data['topic_reply_content'] )
		{
	   		$move_ids	= array();
	   		
	   		foreach( $this->tids as $tid )
	   		{
	   			$move_ids[]	= array( $tid, $this->forum['id'] );
	   		}

			IPSText::getTextClass('bbcode')->parse_smilies			= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section		= 'topics';
			
			$this->modLibrary->topicAddReply( 
											 IPSText::getTextClass('bbcode')->preDbParse( $mm_data['topic_reply_content'] )
											, $move_ids
											, $mm_data['topic_reply_postcount']
										   );
		}
		
		//-----------------------------------------
		// Move topic?
		//-----------------------------------------
		
		if ( $mm_data['topic_move'] )
		{
			//-----------------------------------------
			// Move to forum still exist?
			//-----------------------------------------
			
			$r = $this->registry->class_forums->allForums[ $mm_data['topic_move'] ];

			if( $r['id'] )
			{
				if ( $r['sub_can_post'] != 1 )
				{
					$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), "mm_id=" . $mm_id );
				}
				else
				{
					if ( $r['id'] != $this->forum['id'] )
					{
						$this->modLibrary->topicMove( $this->tids, $this->forum['id'], $r['id'], $mm_data['topic_move_link'] );
						$this->modLibrary->forumRecount( $r['id'] );
					}
				}
			}
			else
			{
				$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), "mm_id=" . $mm_id );
			}
		}
		
		//-----------------------------------------
		// Recount root forum
		//-----------------------------------------
		
		$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
		
		$this->modLibrary->forumRecount( $this->forum['id'] );
		
		$this->_addModeratorLog( sprintf( $this->lang->words['acp_multi_mod'], $mm_data['mm_title'], $this->forum['name'] ) );
	}
	
	/**
	 * Grabs post ids for multi-mod
	 *
	 * @param	string		Field to look in
	 * @return	@e void		Cleaned array of post ids
	 */
	protected function _getIds( $field='selectedtids', $fieldJS='' )
	{
		/* Check main field */
		$ids = array();
		
		/* Check ids field */
		if( $this->request[ $field ] )
		{
			if( is_array( $this->request[ $field ] ) )
			{
				$_ids = $this->request[ $field ];
			}
			else
			{
				$_ids = explode( ',', $this->request[ $field ] );
			}

			if( is_array( $_ids ) && count( $_ids ) )
			{
				foreach( $_ids as $_id )
				{
					$ids[] = $_id;
				}
			}
		}
	
		/* Check js saved ids */
		if( $this->request[ $fieldJS ] )
		{
			$jsIds	= explode( ',', $this->request[ $fieldJS ] );
			
			if( is_array( $jsIds ) && count( $jsIds ) )
			{
				foreach( $jsIds as $_id )
				{
					$ids[] = $_id;
				}
			}
		}

		if ( count( $ids ) < 1 )
 		{
 			$this->_showError( 'mod_no_tid', 103118 );
 		}
 		
 		$ids = IPSLib::cleanIntArray( $ids );
 		$ids = array_diff( $ids, array(0) );
 		$ids = array_unique( $ids );

 		return $ids;
	}
	
	/**
	 * Takes an input url and extracts the topic id
	 *
	 * @return	integer		Topic id
	 */
	protected function _getTidFromUrl()
	{
		/* Try to intval the url */
		if( ! intval( $this->request['topic_url'] ) )
		{
			/* Friendly URL */
			if( $this->settings['use_friendly_urls'] )
			{
				/* remove base url from url */
				$this->request['topic_url'] = str_replace( $this->settings['_original_base_url'], '', $this->request['topic_url'] );
				$this->request['topic_url'] = str_replace( array( '/index.php/', '/index.php?/' ), '/', $this->request['topic_url'] );

				$templates	= IPSLib::buildFurlTemplates();
				
				preg_match( $templates['showtopic']['in']['regex'], $this->request['topic_url'], $match );
				$old_id = intval( trim( $match[1] ) );
			}
			/* Normal URL */
			else
			{
				preg_match( '/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/', $this->request['topic_url'], $match );
				$old_id = intval( trim( $match[3] ) );
			}			
		}
		else
		{
			$old_id = intval($this->request['topic_url']);
		}

		return $old_id;
	}

	/**
	 * Show an error as a result of a moderator request
	 * Abstracted so that we can expand this, i.e. for logging
	 *
	 * @param 	string		Error message language key
	 * @param 	integer		Error code
	 * @param	integer		Header code
	 * @return	@e void		Outputs error screen
	 */
	protected function _showError( $msg = 'moderate_no_permission', $level = 10367, $header=500 )
	{
		$this->registry->output->showError( $msg, $level, true, null, $header );
	}
	
	/**
	 * Shortcut for adding a moderator log
	 *
	 * @param 	string		Error message language key
	 * @return	@e void
	 */
	protected function _addModeratorLog( $title = 'unknown' )
	{
		$this->modLibrary->addModerateLog( $this->request['f'], $this->request['t'], $this->request['p'], $this->topic['title'], $title );
	}
	
	/**
	 * Generic permission checking
	 *
	 * @param 	string		Key to check from 'moderator' aray
	 * @param	string		[Optional] error language key to use if check fails
	 * @param	boolean		Return (vs output)
	 * @return	mixed		Boolean true | Displays error screen
	 */
	protected function _genericPermissionCheck( $key='', $error='moderate_no_permission', $return=false )
	{
		$pass = 0;
	
		if ( $this->memberData['g_is_supmod'] == 1 )
		{
			$pass = 1;
		}
		else if ( $key AND $this->moderator[ $key ] == 1 )
		{
			$pass = 1;
		}

		if ( $pass == 0 )
		{
			if( $return )
			{
				return false;
			}

			$this->_showError( $error, 103119.5 );
		}
		
		return true;
	}

	/**
	 * Check the input
	 * 1) Checks against CSRF attacks
	 * 2) Checks submissions to ensure auth_key is valid
	 * 3) Determines if you have permission to access the moderator action
	 * 4) Sets up $this->forum and $this->moderator
	 *
	 * @author	Brandon Farber
	 * @return	@e void
	 */
	protected function _setupAndCheckInput()
	{
		$post_array			= array( '04', '02', '20', '22', 'resync', 'prune_start', 'prune_finish', 'prune_move', 'editmember' );
		$not_forum_array	= array( 'editmember', 'doeditmember' );
		
		//-----------------------------------------
		// Make sure this is a POST request
		//-----------------------------------------
		
		if ( ! in_array( $this->request['do'], $post_array ) )
		{
			if ( ! $this->request['auth_key'] ) // Changed from $_POST to enable linking to mod functions
			{
				$this->_showError( 'mod_no_authorization_key', 5030, null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Nawty, Nawty!
		//-----------------------------------------
		
		if ( $this->request['do'] != '02' and $this->request['do'] != '05' )
		{
			if ($this->request['auth_key'] != $this->member->form_hash )
			{
				$this->_showError( 'mod_no_authorization_key', 5031, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		if ( ! in_array( $this->request['do'], $not_forum_array ) AND ! $this->fromSearch )
		{
			//-----------------------------------------
			// t
			//-----------------------------------------
			
			if ( $this->request['t'] )
			{
				$this->request['t'] = intval($this->request['t']);
				
				if ( ! $this->request['t'] )
				{
					$this->_showError( 'mod_bad_tid', 5032, null, null, 404 );
				}
				else
				{
					$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $this->request['t'] ) );

					if ( empty($this->topic['tid']) )
					{
						$this->_showError( 'mod_no_tid', 103120, null, null, 404 );
					}
					
					if ( $this->request['f'] AND ( $this->topic['forum_id'] != $this->request['f'] ) ) 
					{
						$this->_showError( 'mod_no_tid', 3033, null, null, 404 );
					}
				}
			}
			
			//-----------------------------------------
			// p
			//-----------------------------------------
			
			if ( $this->request['p'] )
			{
				$this->request['p'] = intval($this->request['p']);
				
				if ( !$this->request['p'] )
				{
					$this->_showError( 'mod_bad_pid', 5033, null, null, 404 );
				}
			}
			
			//-----------------------------------------
			// F?
			//-----------------------------------------
			
			$this->request['f'] =  intval($this->request['f']);
			
			if ( ! $this->fromSearch )
			{
				if ( ! $this->request['f'] )
				{
					$this->_showError( 'mod_bad_fid', 4030, null, null, 404 );
				}
			}
			
			$this->request['st'] = intval($this->request['st']);
			
			//-----------------------------------------
			// Get the forum info based on the forum ID,
			//-----------------------------------------
			
			if( $this->request['f'] )
			{
				$this->forum = $this->registry->class_forums->allForums[ $this->request['f'] ];
			}

			//-----------------------------------------
			// Are we a moderator?
			//-----------------------------------------
			
			if ( $this->request['f'] AND !empty( $this->memberData['forumsModeratorData'][ $this->request['f'] ]) )
			{
				$this->moderator = $this->memberData['forumsModeratorData'][ $this->request['f'] ];
			}
		}
	}
	
	/**
	 * Reset the moderator array to be sure we check correct permissions
	 *
	 * @param	integer		Forum ID
	 * @author	Brandon Farber
	 * @return	@e void
	 */
	protected function _resetModerator( $forumId )
	{
		$this->moderator	= array();

		//-----------------------------------------
		// Are we a moderator?
		//-----------------------------------------
		
		if ( !empty( $this->memberData['forumsModeratorData'][ $forumId ]) )
		{
			$this->moderator = $this->memberData['forumsModeratorData'][ $forumId ];
		}
	}
}