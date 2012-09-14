<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Ajax Functions For Topics
 * Last Updated: $Date: 2012-06-06 05:12:48 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10870 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_topics extends ipsAjaxCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs for the ajax handler]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Load topic class */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		$this->lang->loadLanguageFile( array( 'public_topic', 'public_mod' ), 'forums' );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'editBoxShow':
				$this->editBoxShow();
			break;
			
			case 'editBoxSave':
				$this->editBoxSave();
			break;
			
			case 'saveTopicTitle':
				$this->saveTopicTitle();
			break;

			case 'rateTopic':
				$this->rateTopic();
			break;
			
			case 'postApproveToggle':
				$this->_postApproveToggle();
			break;
			
			case 'preview':
				$this->_topicPreview();
			break;

			case 'reply':
				$this->_reply();
			break;
			
			case 'quote':
			case 'mqquote':
				$this->_quote();
			break;
			
			case 'pollForReplies':
				$this->_pollForReplies();
			break;
			case 'getNewPosts':
				$this->_getNewPosts();
			break;
			case 'markRead':
				$this->_markRead();
			break;
			case 'sigCloseMenu':
				$this->_sigCloseMenu();
			break;
			case 'ignoreSig':
				$this->_ignoreSig();
			break;
		}
	}
	
	protected function _ignoreSig()
	{
		$memberId = trim( $this->request['memberId'] );
		$memberId = ( is_numeric( $memberId ) ) ? $memberId : 'all';
		
		/* Whut */
		if ( $memberId == 'all' )
		{
			IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'view_sigs' => 0 ) ) );
		}
		else
		{
			/* Insert or update? */
			$ignored = IPSMember::fetchIgnoredUsers( $this->memberData );
			
			if ( ! empty( $ignored[ $memberId ] ) )
			{
				/* Update */
				$this->DB->update( 'ignored_users', array( 'ignore_signatures' => 1 ), 'ignore_owner_id=' . $this->memberData['member_id'] . ' AND ignore_ignore_id=' . $memberId );
			}
			else
			{
				$this->DB->insert( 'ignored_users', array( 'ignore_owner_id'   => $this->memberData['member_id'],
													   	   'ignore_ignore_id'  => $memberId,
													       'ignore_messages'   => 0,
													       'ignore_topics'     => 0,
														   'ignore_signatures' => 1 ) );
			}
			
			/* Rebuild cache */
			IPSMember::rebuildIgnoredUsersCache( $this->memberData );
		}
		
		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
	
	protected function _sigCloseMenu()
	{
		$pid = intval( $this->request['pid'] );
		
		/* Fetch post */
		$post  = $this->registry->getClass('topics')->getPostById( $pid );
		$this->registry->getClass('topics')->setTopicData( $post );
		
		if ( $this->registry->getClass('topics')->canView() )
		{
			return $this->returnHtml( $this->registry->output->getTemplate('topic')->ajaxSigCloseMenu( $post ) );
		}
		else
		{
			$this->returnJsonError( 'nopermission' );
		}
	}
	
	protected function _markRead()
	{
		$tid = intval( $this->request['tid'] );
		
		/* Get latest post */
		$post  = $this->registry->getClass('topics')->getPosts( array( 'topicId' => $tid, 'sortField' => 'pid', 'sortOrder' => 'desc', 'offset' => 0, 'limit' => 1 ) );
		$post  = array_pop( $post );
		$forum = $this->registry->class_forums->getForumById( $post['forum_id'] );
		
		$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $post['forum_id'], 'itemID' => $tid, 'markDate' => $post['post_date'], 'containerLastActivityDate' => $forum['last_post'] ) );
		
		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
	
	/**
	 * Polls for replies innit
	 */
	protected function _pollForReplies()
	{
		$topicId = intval( $this->request['t'] );
		$topPid  = intval( $this->request['pid'] );
		
		/* Can we view this topic? */
		$this->registry->getClass('topics')->setTopicData( $topicId );
		
		/* Can we view? */
		if ( $this->registry->getClass('topics')->canView() !== true )
		{
			$this->returnJsonArray( array( 'count' => 0 ) );
		}
		
		/* Get posts */ 
		$posts = $this->_getPosts( $topicId, $topPid, $this->registry->getClass('topics')->getTopicData('forum_id') );
		
		/* Got anything sailor? */
		if ( ! count( $posts ) )
		{
			$this->returnJsonArray( array( 'count' => 0 ) );
		}
		else
		{
			$this->returnJsonArray( array( 'count' => count( $posts ), 'data' => $posts ) );
		}
	}
	
	/**
	 * Fetches the new posts to insert into the live page ...
	 */
	protected function _getNewPosts()
	{
		$topicId = intval( $this->request['t'] );
		$topPid  = intval( $this->request['pid'] );
		
		/* Can we view this topic? */
		$this->registry->getClass('topics')->setTopicData( $topicId );
		
		/* Can we view? */
		if ( $this->registry->getClass('topics')->canView() !== true )
		{
			$this->returnHtml( '' );
		}
		
		/* Get posts */ 
		$posts = $this->_getPosts( $topicId, $topPid, $this->registry->getClass('topics')->getTopicData('forum_id') );
		
		/* Got anything sailor? */
		if ( ! count( $posts ) )
		{
			$this->returnHtml( '' );
		}
		else
		{
			$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $this->registry->getClass('topics')->getTopicData('forum_id'),
																		    'itemID'  => $topicId,
																			'containerLastActivityDate' => $this->registry->getClass('topics')->getTopicData('last_post' ) ) );
			$html = '';
			
			foreach( $posts as $pid => $data )
			{
				$html .= $this->registry->output->getTemplate('topic')->post( $data, array(), $this->registry->getClass('topics')->getTopicData() );
			}
			
			$this->returnHtml( $html );
		}
	}
	
	/**
	 * Fetches posts
	 * @param	INT		Topic ID
	 * @param	INT		Top PID
	 * @param	INT		Forum ID
	 */
	protected function _getPosts( $topicId, $topPid, $forumId )
	{
		IPSDebug::fireBug( 'info', array( 'Retrieving posts for topic id ' . $topicId . ' where pid is greater than ' . $topPid ) );
		
		/* Reset viewing member id - can be set to someone else by post class (e.g. in the sendOutQuoteNotifications() method) */
		$this->registry->getClass('topics')->setMemberData( $this->memberData );

		/* Get posts */
		$posts = $this->registry->getClass('topics')->getPosts( array(   'topicId' 		  => $topicId,
																		 'pidIsGreater'   => $topPid,
																		 'onlyViewable'   => true,
																		 'onlyVisible'    => true,
																		 'parse'		  => true,
																		 'forumId'		  => $forumId,
																		 'limit'		  => 50,
																		 'sortKey'		  => 'date',
																		 'sortOrder'	  => 'asc' ) );
		
		if ( count( $posts ) )
		{
			/* Fetch post count ID */
			$this->registry->getClass('topics')->getPosts( array(   'topicId' 		 => $topicId,
																	'pidIsLess'      => $topPid,
																	'onlyViewable'   => true,
																	'onlyVisible'    => true,
																	'parse'		  	 => false,
																	'getCount'		 => true,
																	'forumId'		 => $forumId,
																	'limit'			 => 50,
																	'sortKey'		 => 'date',
																	'sortOrder'	     => 'asc' ) );
			
			$count = $this->registry->getClass('topics')->getPostsCount();
			$count++;
			
			foreach( $posts as $id => $data )
			{
				$posts[ $id ]['post']['post_count'] = ++$count;
			}
		}
		
		/* Parse attachments */
		$topic	= $this->registry->getClass('topics')->getTopicData();
		
		if( $topic['topic_hasattach'] )
		{
			//-----------------------------------------
			// INIT. Yes it is
			//-----------------------------------------
			
			$postHTML = array();
			
			//-----------------------------------------
			// Separate out post content
			//-----------------------------------------
			
			foreach( $posts as $id => $post )
			{
				$postHTML[ $id ] = $post['post']['post'];
			}

			//-----------------------------------------
			// Grab render attach class
			//-----------------------------------------
				
			if ( ! is_object( $this->class_attach ) )
			{	
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach		   =  new $classToLoad( $this->registry );
			}
			
			//-----------------------------------------
			// Not got permission to view downloads?
			//-----------------------------------------
			
			if ( $this->registry->permissions->check( 'download', $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ] ) === FALSE )
			{
				$this->settings['show_img_upload'] = 0;
			}
			
			//-----------------------------------------
			// Continue...
			//-----------------------------------------
			
			$this->class_attach->type  = 'post';
			$this->class_attach->init();

			$attachHTML = $this->class_attach->renderAttachments( $postHTML, array_keys( $posts ) );
	
			/* Now parse back in the rendered posts */
			if( is_array($attachHTML) AND count($attachHTML) )
			{
				foreach( $attachHTML as $id => $data )
				{
					/* Get rid of any lingering attachment tags */
					if ( stristr( $data['html'], "[attachment=" ) )
					{
						$data['html'] = IPSText::stripAttachTag( $data['html'] );
					}

					$posts[ $id ]['post']['post']			= $data['html'];
					$posts[ $id ]['post']['attachmentHtml']	= $data['attachmentHtml'];
				}
			}
		}
		
		return $posts;
	}
	
	/**
	 * Fetches data to quote
	 *
	 * @return	@e void
	 */
	protected function _quote()
	{
		/* Init */
		$tid	= intval( $this->request['t'] );
		$pid	= intval( $this->request['p'] );
		$pids	= explode( ',', IPSText::cleanPermString( $this->request['pids'] ) );
		$posts	= array();
		$_post	= '';

		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		/* set up bbcode */
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		/* Set up topic */
		if( $pid )
		{
			$posts[]  = $this->registry->getClass('topics')->getPostById( $pid );
		}
		else
		{
			foreach( $pids as $pid )
			{
				$posts[]  = $this->registry->getClass('topics')->getPostById( $pid );
			}
		}

		/* Permission ids */
		$perm_id	= $this->memberData['org_perm_id'] ? $this->memberData['org_perm_id'] : $this->memberData['g_perm_id'];
		$perm_array	= explode( ",", $perm_id );

		/* Naughty boy chex */
		foreach( $posts as $post )
		{
			/* Set up data */
			$this->registry->getClass('topics')->setTopicData( $post );
			
			if ( $this->registry->getClass('topics')->canView() !== true )
			{
				$this->returnJsonError( 'nopermission' );
			}
		
			if ( $this->registry->permissions->check( 'read', $this->registry->class_forums->getForumById( $post['forum_id'] ), $perm_array ) !== TRUE )
			{
				$this->returnJsonError( 'nopermission' );
			}

			/* Post visible? */
			if ( $this->registry->getClass('class_forums')->fetchHiddenType( $post ) != 'visible' )
			{
				$this->returnJsonError( 'nopermission' );
			}

			/* Phew that was a toughy wasn't it? */
			if ( $this->settings['strip_quotes'] )
			{
				$post['post'] = IPSText::getTextClass( 'bbcode' )->stripQuotes( $post['post'] );
			}
			
			/* Strip shared media in quotes */
			$post['post'] = IPSText::getTextClass( 'bbcode' )->stripSharedMedia( $post['post'] );
			
			/* We don't use makeQuoteSafe() here because the result is returned via AJAX and inserted as text into the editor.  + shows as &#043; as a result if we do */
			$_quoted	= preg_replace( '/(<br\s*\/?>\s*)+$/', "<br />", "<br />" . rtrim($post['post']) . "<br />" );
			$_post		.= "[quote name='" . ( $post['members_display_name'] ? $post['members_display_name'] : $post['author_name'] ) . "' timestamp='" . $post['post_date'] . "' post='" . $post['pid'] . "']{$_quoted}[/quote]<br />";
		}

		$this->editor->setContent( $_post, 'topics' );
		
		$this->returnHtml( $this->editor->getContent() );
	}
	
	/**
	 * Saves the post
	 *
	 * @return	@e void
	 */
	protected function _reply()
	{
		/* Init */
		$fid  	 = intval( $this->request['f'] );
		$tid  	 = intval( $this->request['t'] );
		$topPid  = intval( $this->request['pid'] );
		
		/* Basic checks */
		if ( ! $tid )
		{
			$this->returnJsonError( $this->lang->words['ajax_reply_noperm'] );
		}
		
		if ( $this->memberData['member_id'] )
		{
			if ( IPSMember::isOnModQueue( $this->memberData ) === NULL )
			{
				$this->returnJsonError( $this->lang->words['ajax_reply_noperm'] );
			}
		}

		/* Load lang and classes */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_topics' ) );

		if ( ! is_object( $this->postClass ) )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
                        
			$this->postClass = new $classToLoad( $this->registry );
		}
	
		/* Fetch topic data */
		$topicData = $this->registry->getClass('topics')->getTopicById( $tid );
		
		if ( ! $this->registry->getClass('topics')->canView( $topicData ) )
		{
			$this->returnJsonError( $this->lang->words['ajax_reply_noperm'] );
		}
		
		/* Duplicate posts */
		if ( time() - $this->memberData['last_post'] <= 4 )
		{
			$_lastPost	= $this->DB->buildAndFetch( array( 'select' => 'topic_id',
														   'from'   => 'posts',
														   'where'  => 'author_id=' . $this->memberData['member_id'],
														   'order'  => 'post_date DESC',
														   'limit'  => array( 0, 1 ) ) );

			/* We made a reply within the last 4 seconds to this topic.. */
			if ( $_lastPost['topic_id'] == $tid )
			{
				$this->returnJsonError( $this->lang->words['topic_duplicate_post'] );
			}
		}
		
		/* Are we following? */
		if ( $this->memberData['auto_track'] )
		{
			$_likes = 1;
		}
		else
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'forums','topics' );
			$_likes	= $_like->isLiked( $topicData['tid'], $this->memberData['member_id'] );
		}
				
		/* Set post class data */
		$this->postClass->setForumData( $this->registry->getClass('class_forums')->getForumById( $topicData['forum_id'] ) );
		$this->postClass->setTopicID( $tid );
		$this->postClass->setForumID( $fid );
		
		$this->postClass->setSettings( array( 'enableSignature' => 1,
											  'enableEmoticons' => 1,
											  'post_htmlstatus' => 0,
											  'enableTracker'   => ( $_likes ) ? 1 : 0 ) );
		
		/* Topic Data */
		$this->postClass->setTopicData( $topicData );
		$this->postClass->setAuthor( $this->member->fetchMemberData() );
		$this->postClass->setIsAjax( TRUE );
		$this->postClass->setPublished( 'reply' );
		
		$this->postClass->setPostContent( $_POST['Post'] );

		/* POST */
		try
		{
			/**
			 * If there was an error, return it as a JSON error
			 */
			if ( $this->postClass->addReply() === FALSE )
			{
				$this->returnJsonError( $this->postClass->getPostError() );
			}
			
			/* If it requires preview, return a message */
			if( $this->postClass->getPublished() === false )
			{
				$this->returnJsonArray( array( 'success' => 1, 'message' => $this->lang->words['thanks_need_preview'] ) );
			}

			IPSDebug::fireBug( 'info', array( 'The post was successfully saved...' ) );

			$topic = $this->postClass->getTopicData();
			$post  = $this->postClass->getPostData();
			
			/* If we are merging, back up one */
			if( $post['pid'] == $topPid )
			{
				$topPid--;
			}
			
			/* Can we report? */
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary', 'core' );
			$reports		= new $classToLoad( $this->registry );
			
			/* Can we view this topic? */
			$this->registry->getClass('topics')->setTopicData( $topic );
			
			$topic					= $this->registry->getClass('topics')->getTopicData();
			$topic['_canReport']	= $reports->canReport( 'post' );
		
			/* Get posts */ 
			$posts = $this->_getPosts( $tid, $topPid, $this->registry->getClass('topics')->getTopicData('forum_id') );
			
			IPSDebug::fireBug( 'info', array( 'Found ' . count($posts) . ' posts...' ) );
			
			/* Got anything sailor? */
			if ( ! count( $posts ) )
			{
				$this->returnHtml( '' );
			}
			else
			{
				$html		= '';
				$lastpid	= 0;
				
				foreach( $posts as $pid => $data )
				{
					$html		.= $this->registry->output->getTemplate('topic')->post( $data, array(), $topic, $this->postClass->getForumData() );
					$lastpid	= $pid;
				}
								
				if( count($posts) == 1 )
				{
					$this->returnJsonArray( array( 'success' => 1, 'post' => $html, 'postid' => $lastpid ), true );
				}
				else
				{
					$this->returnHtml( $html );
				}
			}		
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
		}
	}

	/**
	 * Displays a topic preview
	 *
	 * @return	@e void
	 */
	protected function _topicPreview()
	{
		/* INIT */
		$tid			= intval( $this->request['tid'] );
		$pid			= intval( $this->request['pid'] );
		$sTerm			= trim( $this->request['searchTerm'] );
		$topic			= array();
		$posts			= array();
		$permissions	= array();
		$query			= '';

		/* Topic visibility */
	
		$_perms = array( 'visible' );
		
		if ( $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( false ) )
		{
			$_perms[] = 'sdelete';
		}
		
		if ( $this->registry->getClass('class_forums')->canQueuePosts( false ) )
		{
			$_perms[] = 'hidden';
		}

		/* Grab topic data and first post */
		$topic = $this->DB->buildAndFetch( array( 'select'   => '*, title as topic_title, posts as topic_posts, last_post as topic_last_post',
												  'from'     => 'topics',
												  'where'    => $this->registry->class_forums->fetchTopicHiddenQuery( $_perms ) . ' AND tid=' . $tid ) );
		
		if ( ! $topic['tid'] )
		{
			return $this->returnString( 'no_topic' );
		}
		
		/* Permission check */
		if ( $this->registry->class_forums->forumsCheckAccess( $topic['forum_id'], 0, 'topic', $topic, true ) !== true )
		{
			return $this->returnString( 'no_permission' );
		}
		
		/* is archived? */
		$isArchived = $this->registry->topics->isArchived( $topic );
		
		/* Build permissions */
		$permissions['PostSoftDeleteSee']      = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( $topic['forum_id'] );
		$permissions['SoftDeleteContent']      = $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( $topic['forum_id'] );
		$permissions['TopicSoftDeleteSee']     = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( $topic['forum_id'] );
		$permissions['canQueue']			   = $this->registry->getClass('class_forums')->canQueuePosts( $topic['forum_id'] );
		
		/* Boring old boringness */
		if ( $permissions['canQueue'] )
		{
			if ( $permissions['PostSoftDeleteSee'] )
			{
				$query	= $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'hidden', 'sdeleted'), $this->registry->topics->getPostTableField('_prefix_', $isArchived ) ) . ' AND ';
			}
			else
			{
				$query	= $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'hidden'), $this->registry->topics->getPostTableField('_prefix_', $isArchived ) ) . ' AND ';
			}
		}
		else
		{
			if ( $permissions['PostSoftDeleteSee'] )
			{
				$query	= $this->registry->class_forums->fetchPostHiddenQuery(array('visible', 'sdeleted'), $this->registry->topics->getPostTableField('_prefix_', $isArchived ) ) . ' AND ';
			}
			else
			{
				$query	= $this->registry->class_forums->fetchPostHiddenQuery(array('visible'), $this->registry->topics->getPostTableField('_prefix_', $isArchived ) ) . ' AND ';
			}
		}
		
		/* Get first post */
		$_post = $this->registry->topics->getPosts( array( 'onlyViewable'    => true,
														   'sortField'		 => 'pid',
														   'sortOrder'		 => 'asc',
														   'topicId'		 => array( $topic['tid'] ),
														   'limit'			 => 1,
														   'archiveToNative' => true,
														   'isArchivedTopic' => $this->registry->topics->isArchived( $topic ) ) );
		
		$posts['first'] = array_pop( $_post );
		
		/* Archived? Get last post */
		if ( $topic['topic_posts'] && $isArchived )
		{
			/* Get last post */
			$_post = $this->registry->topics->getPosts( array( 'onlyViewable'    => true,
															   'sortField'		 => 'pid',
															   'sortOrder'		 => 'desc',
															   'topicId'		 => array( $topic['tid'] ),
															   'limit'			 => 1,
															   'archiveToNative' => true,
															   'isArchivedTopic' => $this->registry->topics->isArchived( $topic ) ) );
			
			$posts['last'] = array_pop( $_post );
		}
		/* Any more for any more? */
		else if ( $topic['topic_posts'] && ! $isArchived )
		{
			/* Grab number of unread posts? */
			$last_time = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $topic['forum_id'], 'itemID' => $tid ) );
			
			if ( $last_time AND $last_time < $topic['topic_last_post'] )
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MAX(' . $this->registry->topics->getPostTableField('pid', $isArchived ) . ') as max, MIN(pid) as min',
												  		  'from'   => $this->registry->topics->getPostTableField('_table_', $isArchived ),
												          'where'  => $query . $this->registry->topics->getPostTableField('topic_id', $isArchived ) . "={$tid} AND " . $this->registry->topics->getPostTableField('post_date', $isArchived ) ." > " . intval( $last_time ) )	);
			}
			else
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'MAX(' . $this->registry->topics->getPostTableField('pid', $isArchived ) . ') as max',
												  		  'from'   => $this->registry->topics->getPostTableField('_table_', $isArchived ),
												          'where'  => $query . $this->registry->topics->getPostTableField('topic_id', $isArchived ) . "={$tid}" ) );
				$count['min']   = 0;
				$count['count'] = 0;
			}
											  
			$topic['_lastRead']    = $last_time;
			$topic['_unreadPosts'] = intval( $count['count'] );
			
			/* Got a max and min */
			if ( $count['max'] )
			{
				$_posts = $this->registry->topics->getPosts( array( 'onlyViewable'    => true,
														  		    'postId'		  => array( intval( $count['min'] ), intval( $count['max'] ) ),
																    'archiveToNative' => true,
														   	        'isArchivedTopic' => $this->registry->topics->isArchived( $topic ) ) );
				
				
				
				foreach( $_posts as $pid => $r )
				{
					$r['tid']		= $topic['tid'];
					$r['title_seo']	= $topic['title_seo'];
					
					if ( $r['pid'] == $count['max'] )
					{
						$posts['last'] = $r;
					}
					else
					{
						$posts['unread'] = $r;
					}
				}
			}
			
			if ( is_array( $posts['unread'] ) AND is_array( $posts['last'] ) )
			{
				if ( $posts['unread']['pid'] == $posts['last']['pid'] )
				{
					unset( $posts['unread'] );
				}
				else if ( $posts['unread']['pid'] == $posts['first']['pid'] )
				{
					unset( $posts['unread'] );
				}
				
			}
		}
		
		/* Search? */
		if ( $pid AND $sTerm )
		{
			$_posts = $this->registry->topics->getPosts( array( 'onlyViewable'    => true,
														  		'postId'		  => array( $pid ),
																'archiveToNative' => true,
														   	    'isArchivedTopic' => $this->registry->topics->isArchived( $topic ) ) );
			
			$posts['search'] = array_pop( $_posts );
		}
		
		/* Still here? */
		foreach( $posts as $k => $data )
		{
			$data  = IPSMember::buildDisplayData( $data );
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $data['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ]['use_html'] and $this->caches['group_cache'][ $data['member_group_id'] ]['g_dohtml'] and $data['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $data['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ]['use_ibc'];
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $data['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $data['mgroup_others'];
						
			$data['post']	= IPSText::getTextClass( 'bbcode' )->stripQuotes( $data['post'], array( 'quote', 'spoiler' ) );		
			$data['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $data['post'] );
			
			/* Search term? */
			if ( $k == 'search' AND $pid AND $sTerm )
			{
				$data['post'] = IPSText::truncateTextAroundPhrase( IPSText::getTextClass( 'bbcode' )->stripAllTags( str_replace( '<br />', ' ', strip_tags( $data['post'], '<br>' ) ) ), $sTerm );
				$data['post'] = IPSText::searchHighlight( $data['post'], $sTerm );
			}
			else
			{
				$data['post'] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripAllTags( strip_tags( $data['post'], '<br>' ) ), 500 );
			}
			
			$data['_isVisible']		   = ( $this->registry->getClass('class_forums')->fetchHiddenType( $data ) == 'visible' ) ? true : false;
			$data['_isHidden']		   = ( $this->registry->getClass('class_forums')->fetchHiddenType( $data ) == 'hidden' ) ? true : false;
			$data['_isDeleted']		   = ( $this->registry->getClass('class_forums')->fetchHiddenType( $data ) == 'sdelete' ) ? true : false;

			$posts[ $k ] = $data;
		}
		
		$topic['_key'] = uniqid(microtime());
		
		return $this->returnHtml( $this->registry->output->getTemplate('topic')->topicPreview( $topic, $posts ) );
	}
	
	/**
	 * Toggle the posts approve thingy
	 *
	 * @return	@e void
	 */
	protected function _postApproveToggle()
	{
		/* INIT */
		$topicID  = intval( $this->request['t'] );
		$postID   = intval( $this->request['p'] );
		$approve  = ( $this->request['approve'] == 1 ) ? TRUE : FALSE;
		$_yoGo    = FALSE;
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
        $_modLibrary = new $classToLoad( $this->registry );
                
		/* Load topic */
		$topic = $this->DB->buildAndFetch( array( 'select' => '*',
												  'from'   => 'topics',
												  'where'  => 'tid=' . $topicID ) );
		
		if ( ! $topic['tid'] )
		{
			$this->returnJsonArray( array( 'error' => 'notopic' ) );
		}
		
		/* Permission Checks */
		if ( $this->memberData['g_is_supmod'] )
		{
			$_yoGo = TRUE;
		}
		else if ( is_array( $this->memberData['forumsModeratorData'] ) AND $this->memberData['forumsModeratorData'][ $topic['forum_id'] ]['post_q'] )
		{
			$_yoGo = TRUE;
		}

		if ( ! $_yoGo )
		{
			$this->returnJsonArray( array( 'error' => 'nopermission' ) );
		}
		
		$_modLibrary->postToggleApprove( array( $postID ), $approve, $topicID );
		
		$this->returnJsonArray( array( 'status' => 'ok', 'postApproved' => $approve ) );
	}
	
	/**
	 * Add vote to rating
	 *
	 * @return	@e void
	 */
	public function rateTopic()
	{
		/* INIT */
		$topic_id  = intval( $this->request['t'] );
		$rating_id = intval( $this->request['rating'] );
		$vote_cast = array();
		
		IPSDebug::fireBug( 'info', array( 'The topic rating request has been received...' ) );
		
		/* Query topic */
		$topic_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid={$topic_id}" ) );
		
		/* Make sure we have a valid topic id */
		if( ! $topic_data['tid'] )
		{
			IPSDebug::fireBug( 'error', array( 'The topic was not found in the database' ) );
			$this->returnJsonArray( array( 'error_key' => 'topics_no_tid', 'error_code' => 10346 ) );
		}
		
		if( $topic_data['state'] != 'open' )
		{
			IPSDebug::fireBug( 'error', array( 'The topic is not open' ) );
			
			$this->returnJsonArray( array( 'error_key' => 'topic_rate_locked', 'error_code' => 10348 ) );
		}					
		
		/* Query Forum */
		$forum_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'forums', 'where' => "id={$topic_data['forum_id']}" ) );
				
		/* Permission Check */
		$can_rate = ( $forum_data['forum_allow_rating'] && $this->memberData['member_id'] && $this->memberData['g_topic_rate_setting'] ) ? 1 : 0;
		
		if( ! $can_rate )
		{
			IPSDebug::fireBug( 'error', array( 'The user cannot rate topics in this forum' ) );
			
			$this->returnJsonArray( array( 'error_key' => 'topic_rate_no_perm', 'error_code' => 10345 ) );
			exit();
		}
   		
		/* Sneaky members rating topic more than 5? */		
   		if( $rating_id > 5 )
   		{
	   		$rating_id = 5;
   		}
   		
   		if( $rating_id < 0 )
   		{
	   		$rating_id = 0;
   		}
   		
		/* Have we rated before? */
		$rating = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$topic_data['tid']} and rating_member_id=".$this->memberData['member_id'] ) );
		
		/* Already rated? */
		if( $rating['rating_id'] )
		{
			/* Do we allow re-ratings? */
			if( $this->memberData['g_topic_rate_setting'] == 2 )
			{
				if( $rating_id != $rating['rating_value'] )
				{
					$new_rating = $rating_id - $rating['rating_value'];
					
					$this->DB->update( 'topic_ratings', array( 'rating_value' => $rating_id ), 'rating_id=' . $rating['rating_id'] );
					
					$this->DB->update( 'topics', array( 'topic_rating_total' => intval( $topic_data['topic_rating_total'] ) + $new_rating ), 'tid=' . $topic_data['tid'] );
				}
				
				IPSDebug::fireBug( 'info', array( 'The rating was updated' ) );
				
				$this->returnJsonArray( array(
										'rated'				 => 'update',
										'message'			 => $this->lang->words['topic_rating_changed'],
										'topic_rating_total' => intval( $topic_data['topic_rating_total'] ) + $new_rating,
										'topic_rating_hits'	 => $topic_data['topic_rating_hits']
								) 	);
			}
			else
			{
				IPSDebug::fireBug( 'warn', array( 'The user is not allowed to update their rating' ) );
				
				$this->returnJsonArray( array( 'error_key' => 'topic_rated_already', 'error_code' => 0 ) );
			}
		}
		/* NEW RATING! */
		else
		{
			$this->DB->insert( 'topic_ratings', array( 
														'rating_tid'        => $topic_data['tid'],
														'rating_member_id'  => $this->memberData['member_id'],
														'rating_value'      => $rating_id,
														'rating_ip_address' => $this->member->ip_address 
													) 
							);
																	
			$this->DB->update( 'topics', array( 
													'topic_rating_hits'  => intval( $topic_data['topic_rating_hits'] )  + 1,
													'topic_rating_total' => intval( $topic_data['topic_rating_total'] ) + $rating_id 
												), 'tid='.$topic_data['tid'] );

			IPSDebug::fireBug( 'info', array( 'The rating was inserted' ) );
			
			$this->returnJsonArray( array( 
									'rated'				 => 'new',
									'message'			 => $this->lang->words['topic_rating_done'],
									'topic_rating_total' => intval( $topic_data['topic_rating_total'] ) + $rating_id ,
									'topic_rating_hits'	 => intval( $topic_data['topic_rating_hits'] )  + 1,
									'_rate_int'			 => round( (intval( $topic_data['topic_rating_total'] ) + $rating_id) / (intval( $topic_data['topic_rating_hits'] ) + 1) )
							) 	);
		}
	}	
	
	/**
	 * Saves a ajax topic title edit
	 *
	 * @return	@e void
	 */
	public function saveTopicTitle()
	{
		/* INIT */
		$name	   = $_POST['name'];
		$tid	   = intval( $this->request['tid'] );
		$can_edit  = 0;

		/* Check ID */
		if( ! $tid )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_no_topic_id'] );
		}
		
		/* Load Topic */
		$topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $tid ) );
		
		if( ! $topic['tid'] )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_topic_not_found'] );
		}
		
		/* Check Permissions */
		if ( $this->memberData['g_is_supmod'] )
		{
			$can_edit = 1;
		}
		
		else if( is_array( $this->memberData['forumsModeratorData'] ) AND $this->memberData['forumsModeratorData'][ $topic['forum_id'] ]['edit_topic'] )
		{
			$can_edit = 1;
		}

		if( ! $can_edit )
		{
			$this->returnJsonError( $this->lang->words['ajax_no_t_permission'] );
		}

		/* Make sure we have a valid name */
		if( trim( $name ) == '' || ! $name )
		{
			$this->returnJsonError( $this->lang->words['ajax_no_t_name'] );
			exit();
		}
		
		/* Clean */
		if( $this->settings['etfilter_shout'] )
		{
			if( function_exists('mb_convert_case') )
			{
				if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
				{
					$name = mb_convert_case( $name, MB_CASE_TITLE, $this->settings['gb_char_set'] );
				}
				else
				{
					$name = ucwords( strtolower($name) );
				}
			}
			else
			{
				$name = ucwords( strtolower($name) );
			}
		}
		
		$name		= IPSText::parseCleanValue( $name );
		$name		= $this->cleanTopicTitle( $name );
		$name		= IPSText::getTextClass( 'bbcode' )->stripBadWords( $name );
		$title_seo	= IPSText::makeSeoTitle( $name, TRUE );

		/* Update the topic */
		$this->DB->update( 'topics', array( 'title' => $name, 'title_seo' => $title_seo ), 'tid='.$tid );
		
		$this->DB->insert( 'moderator_logs', array(
											  		'forum_id'		=> intval( $topic['forum_id'] ),
											  		'topic_id'		=> $tid,
											  		'member_id'		=> $this->memberData['member_id'],
											  		'member_name'	=> $this->memberData['members_display_name'],
											  		'ip_address'	=> $this->member->ip_address,
											 		'http_referer'	=> htmlspecialchars( my_getenv('HTTP_REFERER') ),
											  		'ctime'			=> time(),
											  		'topic_title'	=> $name,
											  		'action'		=> sprintf( $this->lang->words['ajax_topictitle'], $topic['title'], $name),
											  		'query_string'	=> htmlspecialchars( my_getenv('QUERY_STRING') ),
										  )  );			
		
		/* Update the last topic title? */
		if ( $topic['tid'] == $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ]['last_id'] )
		{
			$this->DB->update( 'forums', array( 'last_title' => $name, 'seo_last_title' => $title_seo ), 'id=' . $topic['forum_id'] );
		}
		
		if ( $topic['tid'] == $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ]['newest_id'] )
		{
			$this->DB->update( 'forums', array( 'newest_title' => $name ), 'id=' . $topic['forum_id'] );
		}	

		/* All Done */
		$this->returnJsonArray( array( 'title' => $name, 'url' => $this->registry->output->buildSEOUrl( 'showtopic=' . $tid, 'public', $title_seo, 'showtopic' ) ) );
	}
	
	/**
	 * Clean the topic title
	 *
	 * @param	string	Raw title
	 * @return	string	Cleaned title
	 */
	public function cleanTopicTitle( $title="" )
	{
		if( $this->settings['etfilter_punct'] )
		{
			$title	= preg_replace( '/\?{1,}/'      , "?"    , $title );		
			$title	= preg_replace( '/(&#33;){1,}/' , "&#33;", $title );
		}

		//-----------------------------------------
		// The DB column is 250 chars, so we need to do true mb_strcut, then fix broken HTML entities
		// This should be fine, as DB would do it regardless (cept we can fix the entities)
		//-----------------------------------------

		$title = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $title, 0, 250 ) );
		
		$title = IPSText::stripAttachTag( $title );
		$title = str_replace( "<br />", "", $title  );
		$title = trim( $title );

		return $title;
	}	

	/**
	 * Saves the post
	 *
	 * @return	@e void
	 */
	public function editBoxSave()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$pid		   = intval( $this->request['p'] );
		$fid		   = intval( $this->request['f'] );
		$tid		   = intval( $this->request['t'] );
		$attach_pids   = array();

   		$this->request['post_edit_reason'] = $this->convertAndMakeSafe( $_POST['post_edit_reason'] );

   		//-----------------------------------------
		// Set things right
		//-----------------------------------------
		
		$this->request['Post'] =  IPSText::parseCleanValue( $_POST['Post'] );

		//-----------------------------------------
		// Check P|T|FID
		//-----------------------------------------

		if ( ! $pid OR ! $tid OR ! $fid )
		{
			$this->returnString( 'error' );
		}
		
		if ( $this->memberData['member_id'] )
		{
			if ( IPSMember::isOnModQueue( $this->memberData ) === NULL )
			{
				$this->returnJsonError( $this->lang->words['ajax_reply_noperm'] );
			}
		}

		//-----------------------------------------
		// Load Lang
		//-----------------------------------------

		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topics' ) );

		if ( ! is_object( $this->postClass ) )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
                        
			$this->postClass = new $classToLoad( $this->registry );
		}
		
		# Forum Data
		$this->postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $fid ] );
		
		# IDs
		$this->postClass->setTopicID( $tid );
		$this->postClass->setPostID( $pid );
		$this->postClass->setForumID( $fid );
		$this->postClass->setPublished( 'reply' );
		
		if( isset($this->request['post_htmlstatus']) )	// Off is "0"
		{
			$this->postClass->setSettings( array( 'post_htmlstatus' => $this->request['post_htmlstatus'] ) );
		}
		
		/* Topic Data */
		$this->postClass->setTopicData( $this->DB->buildAndFetch( array( 
																			'select'   => 't.*, p.poll_only', 
																			'from'     => array( 'topics' => 't' ), 
																			'where'    => "t.forum_id={$fid} AND t.tid={$tid}",
																			'add_join' => array(
																								array( 
																										'type'	=> 'left',
																										'from'	=> array( 'polls' => 'p' ),
																										'where'	=> 'p.tid=t.tid'
																									)
																								)
									) 							)	 );
		# Set Author
		$this->postClass->setAuthor( $this->member->fetchMemberData() );
		
		# Set from ajax
		$this->postClass->setIsAjax( TRUE );

		# Post Content
		$this->postClass->setPostContent( $_POST['Post'] );

		# Get Edit form
		try
		{
			/**
			 * If there was an error, return it as a JSON error
			 */
			if ( $this->postClass->editPost() === FALSE )
			{
				$this->returnJsonError( $this->postClass->getPostError() );
			}
			
			$topic = $this->postClass->getTopicData();
			$post  = $this->postClass->getPostData();
			
			//-----------------------------------------
			// Pre-display-parse
			//-----------------------------------------
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $post['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->registry->getClass('class_forums')->forum_by_id[ $fid ]['use_html'] and $this->memberData['g_dohtml'] and $post['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $post['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->registry->getClass('class_forums')->forum_by_id[ $fid ]['use_ibc'];
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->postClass->getAuthor('member_group_id');
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->postClass->getAuthor('mgroup_others');
				
			$post['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $post['post'] );

			if ( IPSText::getTextClass( 'bbcode' )->error )
			{
				$this->returnJsonError( $this->lang->words[ IPSText::getTextClass( 'bbcode' )->error ] );
			}			

			$edit_by	= '';
			
			if ( $post['append_edit'] == 1 AND $post['edit_time'] AND $post['edit_name'] )
			{
				$e_time		= $this->registry->getClass( 'class_localization')->getDate( $post['edit_time'] , 'LONG' );
				$edit_by	= sprintf( $this->lang->words['edited_by'], $post['edit_name'], $e_time );
			}
			
			/* Attachments */
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------
				
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}

			$this->class_attach->type = 'post';
			$this->class_attach->init();

			$attachHtml             = $this->class_attach->renderAttachments( array( $pid => $post['post'] ) );
			$post['post']           = $attachHtml[ $pid ]['html'];
			$post['attachmentHtml'] = $attachHtml[ $pid ]['attachmentHtml'];
			
			$output		= $this->registry->output->getTemplate('topic')->quickEditPost( array(
																							'post'				=> $this->registry->getClass('output')->replaceMacros( IPSText::stripAttachTag( $post['post'] ) ),
																							'attachmentHtml'    => $post['attachmentHtml'],
																							'pid'				=> $pid,
																							'edit_by'			=> $edit_by,
																							'post_edit_reason'	=> $post['post_edit_reason']
																					) 		);

			//-----------------------------------------
			// Return plain text
			//-----------------------------------------

			$this->returnJsonArray( array( 'successString' => $output ) );
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
		}
	}
	
	/**
	 * Shows the edit box
	 *
	 * @return	@e void
	 */
	public function editBoxShow()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$pid		 = intval( $this->request['p'] );
		$fid		 = intval( $this->request['f'] );
		$tid		 = intval( $this->request['t'] );
		$show_reason = 0;

		//-----------------------------------------
		// Check P|T|FID
		//-----------------------------------------
		
		if ( ! $pid OR ! $tid OR ! $fid )
		{
			$this->returnString( 'error' );
		}
		
		if ( $this->memberData['member_id'] )
		{
			if ( IPSMember::isOnModQueue( $this->memberData ) === NULL )
			{
				$this->returnJsonError( $this->lang->words['ajax_reply_noperm'] );
			}
		}

		//-----------------------------------------
		// Get classes
		//-----------------------------------------
		
		if ( ! is_object( $this->postClass ) )
		{
			$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_editors' ), 'core' );
			
			require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/post/classPost.php" );/*noLibHook*/
			$classToLoad     = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
			$this->postClass = new $classToLoad( $this->registry );
		}

		/* Set post class data */
		$this->postClass->setForumData( $this->registry->getClass('class_forums')->getForumById( $fid ) );
		$this->postClass->setTopicID( $tid );
		$this->postClass->setForumID( $fid );
		$this->postClass->setPostID( $pid );
		
		/* Topic Data */
		$this->postClass->setTopicData( $this->registry->getClass('topics')->getTopicById( $tid ) );
		
		# Set Author
		$this->postClass->setAuthor( $this->member->fetchMemberData() );
		
		# Get Edit form
		try
		{
			$html = $this->postClass->displayAjaxEditForm();
			
			$html = $this->registry->output->replaceMacros( $html );

			$this->returnHtml( $html );
		}
		catch ( Exception $error )
		{
			$this->returnString( $error->getMessage() );
		}
	}
}