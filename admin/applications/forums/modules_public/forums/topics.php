<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Topic View
 * Last Updated: $Date: 2012-06-08 14:41:59 -0400 (Fri, 08 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10902 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_topics extends ipsCommand
{
	/**
	 * First post content
	 *
	 * @var		string
	 */
	protected $_firstPostContent = '';

	/**
	 * Number of posts so far (offset)
	 *
	 * @var		integer
	 */
	public $post_count	 = 0;
	
	/**
	 * First post id
	 *
	 * @var		integer
	 */
	public $first		= 0;
	
	/**
	 * Quoted post ids
	 *
	 * @var		string
	 */
	public $qpids		= "";
	
	/**
	 * Can rate a topic permission
	 *
	 * @var		boolean
	 */
	public $can_rate	= false;
	
	/**
	 * Is this a poll only (disallow replies)?
	 *
	 * @var		boolean
	 */
	public $poll_only	= false;
	
	/**
	 * Can we edit at least one post?
	 * 
	 * @var		boolean
	 */
 	protected $_canEditAPost	= false;
	
	/**
	 * Attachments library
	 *
	 * @var		object
	 */
	public $class_attach;

	/**
	 * Soft deleted PIDS
	 */
	protected $_sdPids = array();
	
	/**
	 * Permissions array
	 *
	 */
	protected $permissions = array();
	
	/**
	 * Max post date for this page
	 * 
	 */
	protected $_maxPostDate = 0;
	
	/**
	 * Mod actions
	 *
	 * @var		array
	 */
	protected $mod_action = array(	'CLOSE_TOPIC'   => '00',
									'OPEN_TOPIC'	=> '01',
									'MOVE_TOPIC'	=> '02',
									'HIDE_TOPIC'	=> '03',
									'UNHIDE_TOPIC'	=> 'sundelete',
									'DELETE_TOPIC'  => '08',
									'EDIT_TOPIC'	=> '05',
									'PIN_TOPIC'	    => '15',
									'UNPIN_TOPIC'   => '16',
									'UNSUBBIT'	    => '30',
									'MERGE_TOPIC'   => '60',
									'TOPIC_HISTORY' => '90',
								);
	
	/**
	 * Main Execution Function
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$post_data = array();
		$poll_data = '';
		$function  = '';
		
		/* Print CSS */
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='stylesheet' type='text/css' title='Main' media='print' href='{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_print.css' />" );
		
		/* Followed stuffs */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'forums', 'topics' );
		
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		try
		{
			/* Load up the data dudes */
			$this->registry->getClass('topics')->autoPopulate( null, false );
		}
		catch( Exception $crowdCheers )
		{
			$msg = str_replace( 'EX_', '', $crowdCheers->getMessage() );
			
			$this->registry->output->showError( $msg, 10340, null, null, 404 );
		}
		
		
		/* Shortcut */
		$this->forumClass = $this->registry->getClass('class_forums');
		
		/* Setup basics for this method */
		$topicData      = $this->registry->getClass('topics')->getTopicData();
		
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
	
		/* Rating */
		$this->can_rate = $this->memberData['member_id'] ? intval( $this->memberData['g_topic_rate_setting'] ) : 0;

		/* Set up topic */
		$topicData = $this->topicSetUp( $topicData );

		/* Specific view? */
		$this->_doViewCheck();

		/* Get Posts */
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		if ( $this->registry->getClass('topics')->isArchived( $topicData ) && $this->registry->class_forums->fetchArchiveTopicType( $topicData ) != 'working' )
		{
			/* Load up archive class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/reader.php', 'classes_archive_reader' );
			$this->archiveReader = new $classToLoad();
		
			$this->archiveReader->setApp('forums');
			
			$postData = $this->archiveReader->get( array( 'parentData' => $topicData,
														  'goNative'   => true,
														  'offset'     => intval( $this->request['st'] ),
														  'limit'      => intval( $this->settings['display_max_posts'] ),
														  'sortKey'    => $this->settings['post_order_column'],
														  'sortOrder'  => $this->settings['post_order_sort'] ) );
		}
		else
		{
			$postData = $this->_getPosts();
		}
		
		/* Finish off post Data */
		if ( count( $postData ) )
		{
			foreach( $postData as $pid => $data )
			{
				$postData[ $pid ] = $this->parsePostRow( $data );
			}
		}
		
		unset( $this->cached_members );
		
		/* Status? */
		if ( $topicData['_ppd_ok'] === TRUE )
		{
			/* status from PPD */
			if ( $this->forumClass->ppdStatusMessage )
			{
				$topicData['_fastReplyStatusMessage'][] = $this->forumClass->ppdStatusMessage;
			}
		}
		
		$topicData['_fastReplyModAll'] = FALSE;
		switch( intval( $forumData['preview_posts'] ) )
		{
			case 1:
			case 3:
				$topicData['_fastReplyModAll'] = TRUE;
			break;
		}
		
		//-----------------------------------------
		// Update the item marker
		//-----------------------------------------
	
		if ( ! $this->request['view'] && ! $this->registry->getClass('topics')->isArchived( $topicData ) )
		{
			/* If we marked page 2 but land back on page 1 again we don't want to unmark it! */
			$lastMarked = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $forumData['id'], 'itemID' => $topicData['tid'] ) );

			/* is this the very last page? */
			if ( $this->registry->getClass('topics')->isOnLastPage() )
			{
				/* ...then make the timestamp 'NOW' so polls will be cleared correctly */
				$this->_maxPostDate = IPS_UNIX_TIME_NOW;
			}
			
			if ( $lastMarked < $this->_maxPostDate )
			{
				$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $forumData['id'], 'itemID' => $topicData['tid'], 'markDate' => $this->_maxPostDate, 'containerLastActivityDate' => $forumData['last_post'] ) );
			}
		}
		
		/* Set has unread flag */
		$forumData['_hasUnreadTopics'] = $this->registry->getClass('class_forums')->getHasUnread( $forumData['id'] );
		
		IPSDebug::setMemoryDebugFlag( "TOPICS: Parsed Posts - Completed", $_NOW );
	
		//-----------------------------------------
		// Generate template
		//-----------------------------------------
		
		$topicData['id'] = $topicData['forum_id'];
		
		//-----------------------------------------
		// This has to be called first to set $this->poll_only
		//-----------------------------------------
		
		$poll_data = ( $topicData['poll_state'] ) ? $this->_generatePollOutput() : array( 'html' => '', 'poll' => '' );
		
		$displayData = array( 'fast_reply'		    => $this->_getFastReplyData(),
							  'multi_mod'			=> $this->registry->getClass('topics')->getMultiModerationData(),
							  'reply_button'		=> $this->_getReplyButtonData(),
							  'active_users'		=> $this->_getActiveUserData(),
							  'mod_links'			=> ( $this->registry->getClass('topics')->isArchived( $topicData ) ) ? '' : $this->_generateModerationPanel(),
							  'follow_data' 		=> ( $this->registry->getClass('topics')->isArchived( $topicData ) or $topicData['_isDeleted'] ) ? '' : $this->_like->render( 'summary', $topicData['tid'] ),
							  'same_tagged'			=> ( $this->registry->getClass('topics')->isArchived( $topicData ) ) ? '' : $this->_getSameTaggedData(),
							  'poll_data'			=> $poll_data,
							  'load_editor_js'		=> ( $this->_getFastReplyData() && $topicData['_isDeleted'] ) ? true : false,
							  'smilies'				=> '' );

		//-----------------------------------------
		// If we can edit, but not reply, load JS still
		//-----------------------------------------

		if( !$displayData['fast_reply'] AND $this->_canEditAPost )
		{
			$displayData['load_editor_js']	= true;
			
			$classToLoad			= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor					= new $classToLoad();
			$displayData['smilies']	= $editor->fetchEmoticons();
		}
		
		$postData = $this->_parseAttachments( $postData );
		
		/* Rules */
		if( $forumData['show_rules'] == 2 )
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];

			if( ! $forumData['rules_raw_html'] )
			{
				$forumData['rules_text'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $forumData['rules_text'] );
			}
		}
		
		/* Got soft delete pids? */
		if ( is_array( $this->_sdPids ) AND count( $this->_sdPids ) )
		{
			$displayData['sdData'] = IPSDeleteLog::fetchEntries( $this->_sdPids, 'post', false );
		}
		if ( $topicData['_isDeleted'] )
		{
			$topicData['sdData'] = IPSDeleteLog::fetchEntries( array( $topicData['tid'] ), 'topic', false );
			$topicData['sdData'] = $topicData['sdData'][ $topicData['tid'] ];
		}
				
		if( $topicData['starter_id'] )
		{
			$topicData['_starter']	= IPSMember::buildDisplayData( IPSMember::load( $topicData['starter_id'] ) );
		}
		else
		{
			$topicData['_starter']	= IPSMember::buildDisplayData( array(
																		'member_id'				=> 0,
																		'members_display_name'	=> $topicData['starter_name'] ? $this->settings['guest_name_pre'] . $topicData['starter_name'] . $this->settings['guest_name_suf'] : $this->lang->words['global_guestname'],
																)		);
		}
		
		/* Can we report? */
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary', 'core' );
		$reports		= new $classToLoad( $this->registry );
		
		$topicData['_canReport']	= $reports->canReport( 'post' );

		$template = $this->registry->output->getTemplate('topic')->topicViewTemplate( $forumData, $topicData, $postData, $displayData );

		//-----------------------------------------
		// Send for output
		//-----------------------------------------
		
		$this->registry->output->setTitle( strip_tags( $topicData['title'] ) . '<%pageNumber%> - ' . $forumData['name'] . ' - ' . $this->settings['board_name']);
		$this->registry->output->addContent( $template );
				
		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->output->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}

		/**
		 * Add navigational links
		 */
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'showforum=' . $topicData['forum_id'], 'publicNoSession', $forumData['name_seo'], 'showforum' ) . "' />" );
		$this->registry->output->addToDocumentHead( 'raw', "<link rel='author' href='" . $this->registry->output->buildSEOUrl( 'showuser=' . $topicData['starter_id'], 'publicNoSession', $topicData['seo_first_name'], 'showuser' ) . "' />" );
		
		/* Add Meta Content */
		if ( $this->_firstPostContent )
		{
			/* Strip tags on title to ensure multi-mod added code isn't displayed */
			$this->registry->output->addMetaTag( 'keywords', strip_tags( $topicData['title'] ) . ' ' . str_replace( "\n", " ", str_replace( "\r", "", strip_tags( $this->_firstPostContent ) ) ), TRUE );
		}
		
		$pageData = $this->registry->output->getPaginationProcessedData();
		$pageMeta = ( $pageData['pages'] > 1 ) ? sprintf( $this->lang->words['topic_meta_pages'], $pageData['current_page'], $pageData['pages'] ) .' ' : '';
		
		# Trim to 155 chars based on Dan's recommendation
		$this->registry->output->addMetaTag( 'description', $pageMeta . sprintf( $this->lang->words['topic_meta_description'], strip_tags( $topicData['title'] ), $forumData['name'], str_replace( "\r", "", $this->_firstPostContent ) ), FALSE );
		
		/* Set Ad code for the board index */
		if( $this->registry->getClass('IPSAdCode')->userCanViewAds() )
		{
			$this->registry->getClass('IPSAdCode')->setGlobalCode( 'header', 'ad_code_topic_view_header' );
			$this->registry->getClass('IPSAdCode')->setGlobalCode( 'footer', 'ad_code_topic_view_footer' );
		}
				
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Builds an array of post data for output
	 *
	 * @param	array	$row	Array of post data
	 * @return	array
	 */
	public function parsePostRow( $row = array() )
	{
		/* Init */
		$topicData      = $this->registry->getClass('topics')->getTopicData();
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();
		
		/* Parse post */
		$parsed = $this->registry->getClass('topics')->parsePost( $row );
		
		if( $parsed['post']['_can_edit'] )
		{
			$this->_canEditAPost	= true;
		}
		
		/* Collect PIDS of soft deleted posts */
		if ( $parsed['post']['_isDeleted'] AND $parsed['post']['_softDeleteSee'] )
		{
			$this->_sdPids[] = $parsed['post']['pid'];
		}
		
		/* Grab first post */
		if ( $topicData['topic_firstpost'] == $parsed['post']['pid'] )
		{
			$this->_firstPostContent = $parsed['post']['post'];
		}
		else if( $this->request['st'] AND ! $this->_firstPostContent )
		{
			$this->_firstPostContent = $parsed['post']['post'];
		}
		
		/* Anything to highlight? */
		if ( isset($this->request['hl']) AND $this->request['hl'] )
		{
			$parsed['post']['post'] = IPSText::searchHighlight( $parsed['post']['post'], $this->request['hl'] );
		}
		
		/* Multi quote */
		if ( $this->qpids )
		{
			if ( strstr( ','.$this->qpids.',', ','.$parsed['post']['pid'].',' ) )
			{
				$parsed['post']['_mq_selected'] = 1;
			}
		}
		
		/* Mod PIDS */
		if ( $this->memberData['is_mod'] )
		{
			if ( $this->request['selectedpids'] )
			{
				if ( strstr( ','.$this->request['selectedpids'].',', ','.$parsed['post']['pid'].',' ) )
				{
					$parsed['post']['_pid_selected'] = 1;
				}
				
				$this->request['selectedpidcount'] =  count( explode( ",", $this->request['selectedpids']  ) );
			}
		}
		
		/* Post number */
		$this->post_count++;
	
		$parsed['post']['post_count'] = intval($this->request['st']) + $this->post_count;
		
		if ( $parsed['post']['_isDeleted'] )
		{
			$this->post_count--;
		}
		
		/* Post max date for item marking*/
		if ( $row['post_date'] > $this->_maxPostDate )
		{
			$this->_maxPostDate = $row['post_date'];
		}
		
		return $parsed;
	}

	/**
	 * Redirects to new post
	 * @param mixed $topicData
	 */
	public function returnNewPost( $topicData=false )
	{
		$topicData      = ( $topicData === false ) ? $this->registry->getClass('topics')->getTopicData() : $topicData;
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();
		$st             = 0;
		$pid	        = "";
		$last_time      = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $forumData['id'], 'itemID' => $topicData['tid'] ) );
		$query          = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery('visible');
		
		/* Can we deal with hidden posts? */
		if ( $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
		{
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'approved', 'sdeleted', 'hidden' ) );
			}
			else
			{
				/* Otherwise, see queued and approved */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ) );
			}
		}
		else
		{
			/* We cannot see hidden posts */
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array('approved', 'sdeleted') );
			}
		}

		$this->DB->build( array( 'select' => 'MIN(pid) as pid',
								 'from'   => 'posts',
								 'where'  => "topic_id={$topicData['tid']} AND post_date > " . intval( $last_time ) . $query,
								 'limit'  => array( 0,1 ) )	);						
		$this->DB->execute();
		
		$post = $this->DB->fetch();
		
		if ( $post['pid'] )
		{
			$pid = "#entry".$post['pid'];
			
			$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topicData['tid']} AND pid <= {$post['pid']}" . $query ) );										
			$this->DB->execute();
		
			$cposts = $this->DB->fetch();
			
			if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
			{
				$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
			}
			else
			{
				$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
				$pages = ceil( $number);
			}
			
			$st = ($pages - 1) * $this->settings['display_max_posts'];
			
			if( $this->settings['post_order_sort'] == 'desc' )
			{
				$st = (ceil(($topicData['posts']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
			}						
			
			$stUrlParam = ( $st ) ? "&st={$st}" : '';
			
			$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$topicData['tid'].$stUrlParam.$pid, $topicData['title_seo'], 302, 'showtopic' );
		}
		else
		{
			$this->returnLastPost( $topicData );
		}
	}
	
	/**
	 * Return last post
	 *
	 * @return	@e void
	 */
	public function returnLastPost( $topicData=false )
	{
		/* Init */
		$topicData      = ( $topicData === false ) ? $this->registry->getClass('topics')->getTopicData() : $topicData;
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();
		$st             = 0;
		$query          = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery('visible');
		$_posts			= $topicData['posts'];
		
		if( $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
		{
			$_posts	+= intval($topicData['topic_queuedposts']);
		}
		
		if( $permissionData['softDeleteSee'] )
		{
			$_posts	+= intval($topicData['topic_deleted_posts']);
		}

		/* Can we deal with hidden posts? */
		if ( $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
		{
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'approved', 'sdeleted', 'hidden' ) );
			}
			else
			{
				/* Otherwise, see queued and approved */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ) );
			}
		}
		else
		{
			/* We cannot see hidden posts */
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array('approved', 'sdeleted') );
			}
		}
			
		if( $_posts )
		{
			if ( (($_posts + 1) % $this->settings['display_max_posts']) == 0 )
			{
				$pages = ($_posts + 1) / $this->settings['display_max_posts'];
			}
			else
			{
				$number = ( ($_posts + 1) / $this->settings['display_max_posts'] );
				$pages = ceil( $number );
			}
			
			$st = ($pages - 1) * $this->settings['display_max_posts'];
			
			if( $this->settings['post_order_sort'] == 'desc' )
			{
				$st = (ceil(($_posts/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
			}
		}
		
		$this->DB->build( array(  'select' => 'pid',
								  'from'   => 'posts',
								  'where'  => "topic_id=".$topicData['tid'] . $query,
								  'order'  => $this->settings['post_order_column'].' DESC',
								  'limit'  => array( 0,1 ) ) );
							 
		$this->DB->execute();
		
		$post = $this->DB->fetch();
		
		$stUrlParam = ( $st ) ? "&st={$st}" : '';
		$this->registry->output->silentRedirect($this->settings['base_url']."showtopic=".$topicData['tid']. $stUrlParam ."#entry".$post['pid'], $topicData['title_seo'], 302, 'showtopic' );
	}
	
	/**
	* Parse attachments
	*
	* @param	array	Array of post data
	* @return	string	HTML parsed by attachment class
	*/
	public function _parseAttachments( $postData )
	{
		/* Init */
		$topicData = $this->registry->getClass('topics')->getTopicData();
		$forumData = $this->forumClass->getForumById( $topicData['forum_id'] );
		
		//-----------------------------------------
		// No attachments?  Then what are you doing here?
		//-----------------------------------------
		
		if ( ! $topicData['topic_hasattach'] )
		{
			return $postData;
		}
		
		//-----------------------------------------
		// INIT. Yes it is
		//-----------------------------------------
		
		$postHTML = array();
		
		//-----------------------------------------
		// Separate out post content
		//-----------------------------------------
		
		foreach( $postData as $id => $post )
		{
			$postHTML[ $id ] = $post['post']['post'];
		}
		
		//-----------------------------------------
		// ATTACHMENTS!!!
		//-----------------------------------------
		
		if ( ! is_object( $this->class_attach ) )
		{
			//-----------------------------------------
			// Grab render attach class
			//-----------------------------------------
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->class_attach		   =  new $classToLoad( $this->registry );
		}
		
		//-----------------------------------------
		// Not got permission to view downloads?
		//-----------------------------------------
		
		if ( $this->registry->permissions->check( 'download', $this->registry->class_forums->forum_by_id[ $topicData['forum_id'] ] ) === FALSE )
		{
			$this->settings['show_img_upload'] =  0 ;
		}
		
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		$this->class_attach->type  = 'post';
		$this->class_attach->init();

		$attachHTML = $this->class_attach->renderAttachments( $postHTML, array_keys( $postData ) );

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
				
				$postData[ $id ]['post']['post']			= $data['html'];
				$postData[ $id ]['post']['attachmentHtml']	= $data['attachmentHtml'];
			}
		}

		return $postData;
	}
	
	/**
	 * Generate the Poll output
	 *
	 * @return	string
	 */
	public function _generatePollOutput()
	{
		/* Init */
		$topicData   = $this->registry->getClass('topics')->getTopicData();
		$forumData   = $this->forumClass->getForumById( $topicData['forum_id'] );
		$showResults = 0;
		$pollData    = array();
		
		//-----------------------------------------
		// Get the poll information...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'polls', 'where' => 'tid=' . $topicData['tid'] ) );
		$this->DB->execute();
		
		$poll = $this->DB->fetch();
		
		//-----------------------------------------
		// check we have a poll
		//-----------------------------------------
		
		if ( ! $poll['pid'] )
		{
			return array( 'html' => '', 'poll' => '' );
		}
		
		//-----------------------------------------
		// Do we have a poll question?
		//-----------------------------------------
		
		if ( ! $poll['poll_question'] )
		{
			$poll['poll_question'] = $topicData['title'];
		}
		
		//-----------------------------------------
		// Poll only?
		//-----------------------------------------
		
		if ( $poll['poll_only'] == 1 )
		{
			$this->registry->getClass('topics')->setTopicData( 'poll_only', true );
		}
		
		//-----------------------------------------
		// Additional Poll Vars
		//-----------------------------------------
		
		$poll['_totalVotes']  = 0;
		$poll['_memberVoted'] = 0;
		$memberChoices        = array();
		
		//-----------------------------------------
		// Have we voted in this poll?
		//-----------------------------------------
		
		/* Save a join @link http://community.invisionpower.com/tracker/issue-35773-pollsvoters-voters-table-joining-to-million-plus-rows/ */
		if ( $poll['poll_view_voters'] AND $this->settings['poll_allow_public'] )
		{
			$this->DB->build( array( 'select'   => 'v.*',
									 'from'     => array( 'voters' => 'v' ),
									 'where'    => 'v.tid=' . $topicData['tid'],
									 'add_join' => array( array( 'select' => 'm.*',
																 'from'   => array( 'members' => 'm' ),
																 'where'  => 'm.member_id=v.member_id',
																 'type'   => 'left' ) ) ) );
		}
		else
		{
			$this->DB->build( array( 'select'   => '*',
									 'from'     => 'voters',
									 'where'    => 'tid=' . $topicData['tid'] ) );
		}
		
		$this->DB->execute();
		
		while( $voter = $this->DB->fetch() )
		{
			$poll['_totalVotes']++;
			
			if ( $voter['member_id'] == $this->memberData['member_id'] )
			{
				$poll['_memberVoted'] = 1;
			}
			
			/* Member choices */
			if ( $poll['poll_view_voters'] AND $voter['member_choices'] AND $this->settings['poll_allow_public'] )
			{
				$_choices = unserialize( $voter['member_choices'] );
				
				if ( is_array( $_choices ) AND count( $_choices ) )
				{
					$memberData = array( 'member_id'            => $voter['member_id'],
										 'members_seo_name'     => $voter['members_seo_name'],
										 'members_display_name' => $voter['members_display_name'],
										 'members_colored_name' => str_replace( '"', '\"', IPSMember::makeNameFormatted( $voter['members_display_name'], $voter['member_group_id'] ) ),
										 '_last'                => 0 );
					
					foreach( $_choices as $_questionID => $data )
					{
						foreach( $data as $_choice )
						{
							$memberChoices[ $_questionID ][ $_choice ][ $voter['member_id'] ] = $memberData;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Already Voted
		//-----------------------------------------
		
		if ( $poll['_memberVoted'] )
		{
			$showResults = 1;
		}
	
		//-----------------------------------------
		// Created poll and can't vote in it
		//-----------------------------------------
		
		if ( ($poll['starter_id'] == $this->memberData['member_id']) and ($this->settings['allow_creator_vote'] != 1) )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Guest, but can view results without voting
		//-----------------------------------------
		
		if ( ! $this->memberData['member_id'] AND $this->settings['allow_result_view'] )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// is the topic locked?
		//-----------------------------------------
		
		if ( $topicData['state'] == 'closed' )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Can we see the poll before voting?
		//-----------------------------------------
		
		if ( $this->settings['allow_result_view'] == 1 AND $this->request['mode'] == 'show' )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Stop the parser killing images
		// 'cos there are too many
		//-----------------------------------------
		
		$tmp_max_images			      = $this->settings['max_images'];
		$this->settings['max_images'] = 0;
		
		//-----------------------------------------
		// Parse it
		//-----------------------------------------
		
		$poll_answers 	 = unserialize(stripslashes($poll['choices']));
		
		if( !is_array($poll_answers) OR !count($poll_answers) )
		{
			$poll_answers = unserialize( preg_replace( '!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", stripslashes( $poll['choices'] ) ) );
		}
		if( !is_array($poll_answers) OR !count($poll_answers) )
		{
			$poll_answers = '';
		}
		
		reset($poll_answers);
		
		foreach ( $poll_answers as $id => $data )
		{
			if( !is_array($data['choice']) OR !count($data['choice']) )
			{
				continue;
			}

			//-----------------------------------------
			// Get the question
			//-----------------------------------------
			
			$pollData[ $id ]['question'] = $data['question'];
			
			$tv_poll = 0;
			
			# Get total votes for this question
			if( is_array($poll_answers[ $id ]['votes']) AND count($poll_answers[ $id ]['votes']) )
			{
				foreach( $poll_answers[ $id ]['votes'] as $number)
				{
					$tv_poll += intval( $number );
				}
			}
				
			//-----------------------------------------
			// Get the choices for this question
			//-----------------------------------------
			
			foreach( $data['choice'] as $choice_id => $text )
			{
				$choiceData = array();
				$choice     = $text;
				$voters     = array();
				
				# Get total votes for this question -> choice
				$votes   = intval($data['votes'][ $choice_id ]);
				
				if ( strlen($choice) < 1 )
				{
					continue;
				}
			
				$choice = IPSText::getTextClass( 'bbcode' )->parsePollTags($choice);
				
				if ( $showResults )
				{
					$percent = $votes == 0 ? 0 : $votes / $tv_poll * 100;
					$percent = sprintf( '%.2F' , $percent );
					$width   = $percent > 0 ? intval($percent * 2) : 0;
				
					/* Voters */
					if ( $poll['poll_view_voters'] AND $memberChoices[ $id ][ $choice_id ] )
					{
						$voters = $memberChoices[ $id ][ $choice_id ];
						$_tmp   = $voters;
					
						$lastDude = array_pop( $_tmp );
					
						$voters[ $lastDude['member_id'] ]['_last'] = 1;
					}
					
					$pollData[ $id ]['choices'][ $choice_id ] = array( 'votes'   => $votes,
													  				   'choice'  => $choice,
																	   'percent' => $percent,
																	   'width'   => $width,
																	   'voters'  => $voters );
				}
				else
				{
					$pollData[ $id ]['choices'][ $choice_id ] =  array( 'type'   => !empty($data['multi']) ? 'multi' : 'single',
													   					'votes'  => $votes,
																		'choice' => $choice );
				}
			}
		}
		
		$_editPoll	= $this->registry->getClass('topics')->canEditPost( array( 'member_id' => $topicData['starter_id'], 'post_date' => $topicData['start_date'] ) );

		$html = $this->registry->output->getTemplate('topic')->pollDisplay( $poll, $topicData, $forumData, $pollData, $showResults, $_editPoll );
		
		$this->settings['max_images'] = $tmp_max_images;
		
		return array( 'html' => $html, 'poll' => $poll );
	}

	/**
	 * Tests to see if we're viewing a post, etc
	 *
	 * @return	@e void
	 */
	protected function _doViewCheck()
	{
		/* Init */
		$topicData      = $this->registry->getClass('topics')->getTopicData();
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();
		
		if ( $this->request['view'] )
		{
			/* Determine what we can see */
			$_approved	= $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible' ), '' );
			
			/* Can we deal with hidden posts? */
			if ( $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
			{
				if ( $permissionData['TopicSoftDeleteSee'] )
				{
					/* See queued and soft deleted */
					$_approved = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'approved', 'sdeleted', 'hidden' ), '' );
				}
				else
				{
					/* Otherwise, see queued and approved */
					$_approved = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible', 'hidden' ), '' );
				}
			}
			else
			{
				/* We cannot see hidden posts */
				if ( $permissionData['TopicSoftDeleteSee'] )
				{
					/* See queued and soft deleted */
					$_approved = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'approved', 'sdeleted' ), '' );
				}
			}
				
			if ( $this->request['view'] == 'getnextunread' )
			{
				$tid   = $this->registry->getClass('topics')->getNextUnreadTopicId();
				
				if ( $tid )
				{
					$topic = $this->registry->getClass('topics')->getTopicById( $tid );
				
					$this->returnNewPost( $topic );
				}
				else
				{
					$this->registry->output->showError( 'topics_none_newer', 10356, null, null, 404 );
				}
			}
			else if ($this->request['view'] == 'new')
			{
				//-----------------------------------------
				// Newer 
				//-----------------------------------------

				$this->DB->build( array( 
												'select' => 'tid, title_seo',
												'from'   => 'topics',
												'where'  => "forum_id={$forumData['id']} AND {$_approved} AND state <> 'link' AND last_post > {$topicData['last_post']}",
												'order'  => 'last_post',
												'limit'  => array( 0,1 )
									)	);
				$this->DB->execute();
				
				if ( $this->DB->getTotalRows() )
				{
					$this->topic = $this->DB->fetch();
					
					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$topicData['tid'], $topicData['title_seo'], true, 'showtopic' );
				}
				else
				{
					$this->registry->output->showError( 'topics_none_newer', 10356, null, null, 404 );
				}
			}
			else if ($this->request['view'] == 'old')
			{
				//-----------------------------------------
				// Older
				//-----------------------------------------

				$this->DB->build( array( 
												'select' => 'tid, title_seo',
												'from'   => 'topics',
												'where'  => "forum_id={$forumData['id']} AND {$_approved} AND state <> 'link' AND last_post < {$topicData['last_post']}",
												'order'  => 'last_post DESC',
												'limit'  => array( 0,1 )
									)	);
									
				$this->DB->execute();
					
				if ( $this->DB->getTotalRows() )
				{
					$this->topic = $this->DB->fetch();
					
					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$topicData['tid'], $topicData['title_seo'], true, 'showtopic' );
				}
				else
				{
					$this->registry->output->showError( 'topics_none_older', 10357, null, null, 404 );
				}
			}
			else if ($this->request['view'] == 'getlastpost')
			{
				//-----------------------------------------
				// Last post
				//-----------------------------------------
				
				$this->returnLastPost();
			}
			else if ($this->request['view'] == 'getnewpost')
			{
				$this->returnNewPost();
			}
			else if ($this->request['view'] == 'findpost')
			{
				//-----------------------------------------
				// Find a post
				//-----------------------------------------
				
				$pid	= intval($this->request['p']);
				$query	= ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery('visible');
				
				/* Can we deal with hidden posts? */
				if ( $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
				{
					if ( $permissionData['softDeleteSee'] )
					{
						/* See queued and soft deleted */
						$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'approved', 'sdeleted', 'hidden' ) );
					}
					else
					{
						/* Otherwise, see queued and approved */
						$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ) );
					}
				}
				else
				{
					/* We cannot see hidden posts */
					if ( $permissionData['softDeleteSee'] )
					{
						/* See queued and soft deleted */
						$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array('approved', 'sdeleted') );
					}
				}
				
				if ( $pid > 0 )
				{
					$sort_value = $pid;
					$sort_field = ($this->settings['post_order_column'] == 'pid') ? 'pid' : 'post_date';
					
					if($sort_field == 'post_date')
					{
						$date = $this->DB->buildAndFetch( array( 'select' => 'post_date', 'from' => 'posts', 'where' => 'pid=' . $pid ) );

						$sort_value = $date['post_date'];
					}

					$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topicData['tid']} AND {$sort_field} <=" . intval( $sort_value ) . $query ) );										
					$this->DB->execute();
					
					$cposts = $this->DB->fetch();
					
					if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
						$pages = ceil($number);
					}
					
					$st = ($pages - 1) * $this->settings['display_max_posts'];
					
					if( $this->settings['post_order_sort'] == 'desc' )
					{
						$st = (ceil(($topicData['posts']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
					}
					
					$search_hl = '';
					
					if( !empty( $this->request['hl'] ) )
					{
						$search_hl .= "&amp;hl={$this->request['hl']}";
					}
					
					$stUrlParam = ( $st ) ? "&st={$st}" : '';
					
					$this->registry->output->silentRedirect( $this->settings['base_url']."showtopic=".$topicData['tid']."{$stUrlParam}{$search_hl}"."#entry".$pid, $topicData['title_seo'], 302, 'showtopic' );
				}
				else
				{
					$this->returnLastPost();
				}
			}
		}
	}
	
	/**
	 * Fetch data tagged the same
	 * @return array
	 */
	protected function _getSameTaggedData()
	{
		/* Init */
		$topicData = $this->registry->getClass('topics')->getTopicData();
		$results   = array();
		$final     = array();
		
		/* Have we got tags? */
		if ( $this->settings['forums_enabled_also_tagged'] && $this->settings['tags_enabled'] && count( $topicData['tags']['tags'] ) )
		{
			$results = $this->registry->tags->search( $topicData['tags']['tags'], array( 'meta_app'        => 'forums',
																						 'meta_area'       => 'topics',
																						 'meta_parent_id'  => false,
																						 'not_meta_id'     => $topicData['tid'],
																						 'sortKey'		   => 'tg.tag_meta_id',
																						 'sortOrder'	   => 'desc',
																						 'limit'		   => 100,
																						 'joins'		   => array( array( 'select' => 't.*, t.approved, t.title as topic_title, t.posts as topic_posts, t.last_post as topic_last_post', 'from' => array( 'topics' => 't' ), 'where' => 't.tid=tg.tag_meta_id' ),
																													 array( 'select' => 'm.*', 'from' => array( 'members' => 'm' ), 'where' => 'last_poster_id=m.member_id' ),
																													 array( 'select' => 'p.*', 'from' => array( 'profile_portal' => 'p' ), 'where' => 'p.pp_member_id=m.member_id' ),
																													 $this->registry->tags->getCacheJoin( array( 'meta_id_field' => 't.tid' ) ) ),
																						 'isViewable'      => true ) );
		}
		
		/* Limit to 5 unique topics */
		if ( count( $results ) )
		{
			foreach( $results as $id => $data )
			{
				if ( ! in_array( $data['tid'], $final ) )
				{
					if ( $this->registry->class_forums->fetchHiddenTopicType( $data ) != 'visible' )
					{
						continue;
					}
					
					if ( count( $final ) == 5 )
					{
						break;
					}
					
					$data = $this->registry->topics->parseTopicForLineEntry( $data );
					
					/* Sort out navigation */
					$data['nav'] = $this->registry->class_forums->forumsBreadcrumbNav( $data['forum_id'] );
					
					if ( $data['last_poster_id'] )
					{
						$final[ $data['tid'] ] = IPSMember::buildDisplayData( $data, array( 'photoTagSize' => 'mini' ) );
					}
					else
					{
						$final[ $data['tid'] ] = $data;
					}
				}
			}	
		}
	
		return $final;
	}
	
	/**
	 * Get reply button data
	 *
	 * @return	array
	 */
	public function _getReplyButtonData()
	{
		/* Init */
		$topicData = $this->registry->getClass('topics')->getTopicData();
		$forumData = $this->forumClass->getForumById( $topicData['forum_id'] );
		
		$image = $this->registry->getClass('topics')->getReplyStatus();
		$url   = ( $image == 'reply' OR ( $image == 'locked' AND $this->memberData['g_post_closed'] ) ) ? $this->settings['base_url_with_app'] . "module=post&amp;section=post&amp;do=reply_post&amp;f=".$forumData['id']."&amp;t=".$topicData['tid'] : '';

		return array( 'image' => $image, 'url' => $url );
	}
	
	/**
	 * Get fast reply status
	 *
	 * @return	string
	 */
	public function _getFastReplyData()
	{
		/* Hang on, can we post? */
		if ( $this->memberData['member_id'] )
		{
			if ( $this->memberData['unacknowledged_warnings'] )
			{
				//return false;
			}
			elseif ( $this->memberData['restrict_post'] )
			{
				$data = IPSMember::processBanEntry( $this->memberData['restrict_post'] );
				if ( $data['date_end'] )
				{
					if ( time() >= $data['date_end'] )
					{
						IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
		}
		
		/* Init */
		$topicData = $this->registry->getClass('topics')->getTopicData();
		$forumData = $this->forumClass->getForumById( $topicData['forum_id'] );
		$show      = false;
		
		if (  
		       ( $this->registry->permissions->check( 'reply', $forumData ) == TRUE )
		   and ( $topicData['state'] != 'closed' OR $this->memberData['g_post_closed'] )
		   and ( $topicData['_ppd_ok'] === TRUE )
		   and ( ! $topicData['poll_only'] ) )
		{
			$show  = true;
		}
		
		return $show;
	}
	
	/**
	 * Returns a list of the active user in the topic
	 *
	 * @return	@e array
	 */
	public function _getActiveUserData()
	{
		$topicData = $this->registry->getClass('topics')->getTopicData();
		
		/* Nothing to retrieve? */
		if( $this->registry->getClass('topics')->isArchived( $topicData ) || $this->settings['no_au_topic'] )
		{
			return array();
		}

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/session/api.php", 'session_api' );
		$sessionClass = new $classToLoad( $this->registry );
		
		$users = $sessionClass->getUsersIn( 'forums', array( 'skipParsing' => true, 'addWhere' => array( "s.location_1_type='topic'", "s.location_1_id={$topicData['tid']}" ) ) );
		
		/* Got any posting classes to add? */
		if ( ! empty($users['stats']['members']) && is_array($users['rows']['members']) && count($users['rows']['members']) )
		{
			foreach( $users['rows']['members'] as $sid => $sdata )
			{
				if ( isset($users['names'][ $sid ]) && $sdata['member_id'] != $this->memberData['member_id'] && $sdata['current_module'] == 'post' )
				{
					$users['names'][ $sid ] = "<span class='activeuserposting'>" . $users['names'][ $sid ] . "</span>";
				}
			}
		}
		
		return $users;
	}
	
	
	/**
	 * Generate the moderation panel
	 * $skcusgej, still. After all this time	 
	 *
	 * @return	array
	 */
	public function _generateModerationPanel()
	{
		/* Init */
		$topicData = $this->registry->getClass('topics')->getTopicData();
		$forumData = $this->forumClass->getForumById( $topicData['forum_id'] );
		$moderator = $this->registry->getClass('topics')->getModeratorData();
		$mod_links = array();
		$_got_data = 0;
		$actions   = array( 'edit_topic', 'pin_topic', 'unpin_topic', 'close_topic', 'open_topic', 'move_topic', 'merge_topic', 'hide_topic', 'unhide_topic', 'delete_topic', 'unsubbit' );
		
		if ( ! $this->memberData['member_id'] )
		{
			return;
		}
		
		if ( $this->memberData['member_id'] == $topicData['starter_id'] )
		{
			$_got_data = 1;
		}
		
		if ( $this->memberData['g_is_supmod'] == 1 )
		{
			$_got_data = 1;
		}
		
		if ( !empty( $moderator['mid'] ) )
		{
			$_got_data = 1;
		}
		
		if ( $_got_data == 0 )
		{
		   	return;
		}

		//-----------------------------------------
		// Add on approve/unapprove topic fing
		//-----------------------------------------
		
		if ( $this->registry->class_forums->canQueuePosts( $forumData['id'] ) ) 
		{
			if ( $topicData['approved'] != 1 )
			{
				$mod_links[] = array( 'option' => 'topic_approve',
									  'value'  => $this->lang->words['cpt_approvet'] );
			}
			/*else
			{
				$mod_links[] = array( 'option' => 'topic_unapprove',
									  'value'  => $this->lang->words['cpt_unapprovet'] );
			}*/
		}
		
		foreach( $actions as $key )
		{
			if( is_array($this->_addModLink($key)) )
			{
				if ($this->memberData['g_is_supmod'])
				{
					$mod_links[] = $this->_addModLink($key);
				}
				elseif ( $key == 'hide_topic' )
				{
					if ( $this->registry->getClass('class_forums')->canSoftDeleteTopics( $forumData['id'], $topicData ) )
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
				elseif ( $key == 'unhide_topic' )
				{
					if ( $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $forumData['id'], $topicData ) )
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
				elseif ( $key == 'delete_topic' )
				{
					if ( $this->registry->getClass('class_forums')->canHardDeleteTopics( $forumData['id'], $topicData ) )
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
				elseif ( !empty($moderator['mid']) )
				{
					if ($key == 'merge_topic' or $key == 'split_topic')
					{
						if ($moderator['split_merge'] == 1)
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
					else if ( !empty($moderator[ $key ]) )
					{
						$mod_links[] = $this->_addModLink($key);
					}
					
					// What if member is a mod, but doesn't have these perms as a mod?
					
					elseif ($key == 'open_topic' or $key == 'close_topic')
					{
						if ($this->memberData['g_open_close_posts'])
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
				}
				elseif ($key == 'open_topic' or $key == 'close_topic')
				{
					if ($this->memberData['g_open_close_posts'])
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
			}
		}
		
		if ($this->memberData['g_access_cp'] == 1)
		{
			$mod_links[] = $this->_addModLink('topic_history');
		}

		return $mod_links;
	}
	
	/**
	 * Append mod links
	 *
	 * @param	string	$key
	 * @return	array 	Options
	 */
	public function _addModLink( $key="" )
	{
		/* Init */
		$topicData = $this->registry->getClass('topics')->getTopicData();
		$forumData = $this->forumClass->getForumById( $topicData['forum_id'] );
		
		if ($key == "") return "";
		
		if ($topicData['state'] == 'open'   and $key == 'open_topic') return "";
		if ($topicData['state'] == 'closed' and $key == 'close_topic') return "";
		if ($topicData['state'] == 'moved'  and ($key == 'close_topic' or $key == 'move_topic')) return "";
		if ($topicData['pinned'] == 1 and $key == 'pin_topic')   return "";
		if ($topicData['pinned'] == 0 and $key == 'unpin_topic') return "";
		if ($topicData['approved'] != -1 and $key == 'unhide_topic' ) return "";
		if ($topicData['approved'] == -1 and $key == 'hide_topic' ) return "";
		
		return array( 'option' => $this->mod_action[ strtoupper($key) ],
					  'value'  => $this->lang->words[ strtoupper($key) ] );
	}
	
	/**
	 * Get Topic Data
	 *
	 * @return	array
	 */
	public function _getPosts()
	{
		/* Init */
		$topicData      = $this->registry->getClass('topics')->getTopicData();
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();
		$first          = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		
		/* Default - just see all visible posts */
		$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( 'visible', 'p.' );

		/* Can we deal with hidden posts? */
		if ( $this->registry->class_forums->canQueuePosts($topicData['forum_id']) )
		{
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden', 'sdeleted' ), 'p.' );
			}
			else
			{
				/* Otherwise, see queued and approved */
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ), 'p.' );
			}
			
			/* Specifically requesting to see queued posts only */
			if ( $this->request['modfilter'] AND  $this->request['modfilter'] == 'invisible_posts' )
			{
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( 'hidden', 'p.' );
			}
		}
		else
		{
			/* We cannot see hidden posts */
			if ( $permissionData['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array('approved', 'sdeleted'), 'p.' );
			}
		}
		
		/* Did we specifically want to see soft deleted posts? */
		if ( $this->request['modfilter'] == 'deleted_posts' AND $permissionData['softDeleteSee'] )
		{
			$queued_query_bit = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( 'sdeleted', 'p.' );
		}

		/* Data Hook Location */
		$dataHook = array( 'members' => array(), 'postJoins' => array() );
		IPSLib::doDataHooks( $dataHook , 'topicViewQuery' );
		
		//-----------------------------------------
		// Joins
		//-----------------------------------------		
		
		$_extraMember = ( is_array($dataHook['members']) && count($dataHook['members']) ) ? ',m.'.implode(',m.', $dataHook['members']) : '';
		
		$_post_joins = array( array( 'select' => 'm.member_id as mid,m.name,m.member_group_id,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title as member_title, m.warn_level, m.warn_lastwarn, m.members_display_name, m.members_seo_name, m.member_banned, m.has_gallery, m.has_blog, m.members_bitoptions,m.mgroup_others'.$_extraMember,
									 'from'   => array( 'members' => 'm' ),
									 'where'  => 'm.member_id=p.author_id',
									 'type'   => 'left' ),
							  array( 'select' => 'pp.*',
									 'from'   => array( 'profile_portal' => 'pp' ),
									 'where'  => 'm.member_id=pp.pp_member_id',
									 'type'   => 'left' ),
							  array( 'select' => 'w.wl_id',
									 'from'	  => array( 'members_warn_logs' => 'w' ),
									 'where'  => 'w.wl_content_app=\'forums\' and w.wl_content_id1=p.pid' ) );
		
		/* Add data hook joins */					
		if ( is_array($dataHook['postJoins']) && count($dataHook['postJoins']) )
		{
			$_post_joins = array_merge( $_post_joins, $dataHook['postJoins'] );
		}
		
		/* Add custom fields join? */
		if( $this->settings['custom_profile_topic'] == 1 )
		{
			$_post_joins[] = array( 'select' => 'pc.*',
									'from'   => array( 'pfields_content' => 'pc' ),
									'where'  => 'pc.member_id=p.author_id',
									'type'   => 'left' );
		}							
		
		/* Reputation system enabled? */
		if( $this->settings['reputation_enabled'] )
		{
			/* Add the join to figure out if the user has already rated the post */
			$_post_joins[] = $this->registry->repCache->getUserHasRatedJoin( 'pid', 'p.pid', 'forums' );
			
			/* Add the join to figure out the total ratings for each post */
			if( $this->settings['reputation_show_content'] )
			{
				$_post_joins[] = $this->registry->repCache->getTotalRatingJoin( 'pid', 'p.pid', 'forums' );
			}
		}
		
		/* Cache? */
		if ( IPSContentCache::isEnabled() )
		{
			if ( IPSContentCache::fetchSettingValue('post') )
			{
				$_post_joins[] = IPSContentCache::join( 'post', 'p.pid' );
			}
			
			if ( IPSContentCache::fetchSettingValue('sig') )
			{
				$_post_joins[] = IPSContentCache::join( 'sig' , 'm.member_id', 'ccb', 'left', 'ccb.cache_content as cache_content_sig, ccb.cache_updated as cache_updated_sig' );
			}
		}
		
		/* Ignored Users */
		$ignored_users = array();
		
		foreach( $this->member->ignored_users as $_i )
		{
			if( $_i['ignore_topics'] )
			{
				$ignored_users[] = $_i['ignore_ignore_id'];
			}
		}
		
		//-----------------------------------------
		// Get posts
		//-----------------------------------------

		$this->DB->build( array( 'select'   => 'p.*',
								 'from'	    => array( 'posts' => 'p' ),
								 'where'    => 'p.topic_id='.$topicData['tid']. $queued_query_bit,
								 'order'    => 'p.'.$this->settings['post_order_column'].' '.$this->settings['post_order_sort'],
								 'limit'    => array( $first, $this->settings['display_max_posts'] ),
								 'add_join' => $_post_joins ) );

		$oq = $this->DB->execute();

		if ( ! $this->DB->getTotalRows() )
		{
			if ( $first >= $this->settings['display_max_posts'] )
			{
				//-----------------------------------------
				// AUTO FIX: Get the correct number of replies...
				//-----------------------------------------

				$this->DB->build( array(
										'select' => 'COUNT(*) as pcount',
										'from'   => 'posts',
										'where'  => "topic_id=".$topicData['tid']." and queued=0" )	);

				$newq   = $this->DB->execute();

				$pcount = $this->DB->fetch($newq);

				$pcount['pcount'] = $pcount['pcount'] > 0 ? $pcount['pcount'] - 1 : 0;

				//-----------------------------------------
				// Update the post table...
				//-----------------------------------------

				if ( $pcount['pcount'] > 1 )
				{
					$this->DB->update( 'topics', array( 'posts' => $pcount['pcount'] ), "tid=".$topicData['tid'] );

				}

				$this->registry->output->silentRedirect($this->settings['base_url']."showtopic={$topicData['tid']}&view=getlastpost");
			}
		}

		//-----------------------------------------
		// Render the page top
		//-----------------------------------------

		$topicData['go_new'] = isset($topicData['go_new']) ? $topicData['go_new'] : '';

		//-----------------------------------------
		// Format and print out the topic list
		//-----------------------------------------
		
		$this->registry->getClass('topics')->setTopicData('adCodeSet'   , false );
		$this->registry->getClass('topics')->setTopicData('ignoredUsers', $ignored_users );
		$posts = array();

		while ( $row = $this->DB->fetch( $oq ) )
		{
			$row['member_id']     = $row['mid'];
			$posts[ $row['pid'] ] = $row;
		}
		
		/* Return */
		return $posts;	
	}

	/**
	 * Topic set up ya'll
	 *
	 * @return	@e void
	 */
	public function topicSetUp( $topicData )
	{
		/* Init */
		$topicData      = ( $topicData['tid'] ) ? $topicData : $this->registry->getClass('topics')->getTopicData();
		$forumData      = $this->forumClass->getForumById( $topicData['forum_id'] );
		$permissionData = $this->registry->getClass('topics')->getPermissionData();

		//-----------------------------------------
		// Memory...
		//-----------------------------------------
		
		$_before = IPSDebug::getMemoryDebugFlag();
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['start']	= ! empty( $this->request['start'] )	? intval( $this->request['start'] )	: '';
		$this->request['st']	= ! empty( $this->request['st'] )		? intval( $this->request['st'] )	: '';
		
		$this->settings['post_order_column'] = ( $this->settings['post_order_column'] != 'post_date' ) ? 'pid' : 'post_date';
		$this->settings['post_order_sort']   = ( $this->settings['post_order_sort']   != 'desc' )      ? 'asc' : 'desc';
		$this->settings['au_cutoff']         = ( empty( $this->settings['au_cutoff'] ) ) ? 15 : $this->settings['au_cutoff'];
			
		//-----------------------------------------
		// Compile the language file
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_boards', 'public_topic' ) );
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		//-----------------------------------------
 		// Get all the member groups and
 		// member title info
 		//-----------------------------------------
	   
		if ( ! is_array( $this->cache->getCache('ranks') ) )
		{
			$this->cache->rebuildCache( 'ranks', 'global' );
		}
		
		//-----------------------------------------
		// Are we actually a moderator for this forum?
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$moderator = $this->memberData['forumsModeratorData'];
			
			if ( !isset($moderator[ $forumData['id'] ]) OR !is_array( $moderator[ $forumData['id'] ] ) )
			{
				$this->memberData['is_mod'] = 0;
			}
		}
		
		$this->settings['_base_url'] = $this->settings['base_url'];
		$this->first				 = intval( $this->request['st'] ) > 0 ? intval( $this->request['st'] ) : 0;
		$this->request['view']	     = ! empty( $this->request['view'] ) ? $this->request['view'] : NULL ;
		
		//-----------------------------------------
		// Check viewing permissions, private forums,
		// password forums, etc
		//-----------------------------------------

		if ( ( ! $this->memberData['g_other_topics'] ) AND ( $topicData['starter_id'] != $this->memberData['member_id'] ) )
		{
			$this->registry->output->showError( 'topics_not_yours', 10359, null, null, 403 );
		}
		else if( (!$forumData['can_view_others'] AND !$this->memberData['is_mod'] ) AND ( $topicData['starter_id'] != $this->memberData['member_id'] ) )
		{
			$this->registry->output->showError( 'topics_not_yours2', 10360, null, null, 403 );
		}
		else if( $forumData['redirect_on'] AND $forumData['redirect_url'] )
		{
			$this->registry->output->silentRedirect( $forumData['redirect_url'] );
		}
		
		//-----------------------------------------
		// Update the topic views counter
		//-----------------------------------------
		
		if ( ! $this->request['view'] AND $topicData['state'] != 'link' )
		{
			if ( $this->settings['update_topic_views_immediately'] )
			{
				$this->DB->update( 'topics', 'views=views+1', "tid=".$topicData['tid'], true, true );
			}
			else
			{
				$this->DB->insert( 'topic_views', array( 'views_tid' => $topicData['tid'] ), true );
			}
		}
		
		//-----------------------------------------
		// Need to update this topic?
		//-----------------------------------------
		
		if ( $topicData['state'] == 'open' )
		{
			if( !$topicData['topic_open_time'] OR $topicData['topic_open_time'] < $topicData['topic_close_time'] )
			{
				if ( $topicData['topic_close_time'] AND ( $topicData['topic_close_time'] <= time() AND ( time() >= $topicData['topic_open_time'] OR !$topicData['topic_open_time'] ) ) )
				{
					$topicData['state'] = 'closed';
					
					$this->DB->update( 'topics', array( 'state' => 'closed' ), 'tid='.$topicData['tid'], true );
				}
			}
			else if( $topicData['topic_open_time'] OR $topicData['topic_open_time'] > $topicData['topic_close_time'] )
			{
				if ( $topicData['topic_close_time'] AND ( $topicData['topic_close_time'] <= time() AND time() <= $topicData['topic_open_time'] ) )
				{
					$topicData['state'] = 'closed';
					
					$this->DB->update( 'topics', array( 'state' => 'closed' ), 'tid='.$topicData['tid'], true );
				}
			}				
		}
		else if ( $topicData['state'] == 'closed' )
		{
			if( !$topicData['topic_close_time'] OR $topicData['topic_close_time'] < $topicData['topic_open_time'] )
			{
				if ( $topicData['topic_open_time'] AND ( $topicData['topic_open_time'] <= time() AND ( time() >= $topicData['topic_close_time'] OR !$topicData['topic_close_time'] ) ) )
				{
					$topicData['state'] = 'open';
					
					$this->DB->update( 'topics', array( 'state' => 'open' ), 'tid='.$topicData['tid'], true );
				}
			}
			else if( $topicData['topic_close_time'] OR $topicData['topic_close_time'] > $topicData['topic_open_time'] )
			{

				if ( $topicData['topic_open_time'] AND ( $topicData['topic_open_time'] <= time() AND time() <= $topicData['topic_close_time'] ) )
				{
					$topicData['state'] = 'open';
					
					$this->DB->update( 'topics', array( 'state' => 'open' ), 'tid='.$topicData['tid'], true );
				}
			}				
		}
		
		//-----------------------------------------
		// Current topic rating value
		//-----------------------------------------
		
		$topicData['_rate_show']  = 0;
		$topicData['_rate_int']   = 0;
		$topicData['_rate_img']   = '';
		
		if ( $topicData['state'] != 'open' )
		{
			$topicData['_allow_rate'] = 0;
		}
		else
		{
			$topicData['_allow_rate'] = $this->can_rate;
		}
		
		if ( $forumData['forum_allow_rating'] )
		{
			$rating = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$topicData['tid']} and rating_member_id=".$this->memberData['member_id'] ) );
			
			if ( $rating['rating_value'] AND $this->memberData['g_topic_rate_setting'] != 2 )
			{
				$topicData['_allow_rate'] = 0;
			}
			
			$topicData['_rate_id']	   = 0;
			$topicData['_rating_value']  = $rating['rating_value'] ? $rating['rating_value'] : -1;
			
			if ( $topicData['topic_rating_total'] )
			{
				$topicData['_rate_int'] = round( $topicData['topic_rating_total'] / $topicData['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $topicData['topic_rating_hits'] >= $this->settings['topic_rating_needed'] ) AND ( $topicData['_rate_int'] ) )
			{
				$topicData['_rate_id']   = $topicData['_rate_int'];
				$topicData['_rate_show'] = 1;
			}
		}
		else
		{
			$topicData['_allow_rate'] = 0;
		}		
		
		//-----------------------------------------
		// If this forum is a link, then 
		// redirect them to the new location
		//-----------------------------------------
		
		if ( $topicData['state'] == 'link' AND $topicData['moved_to'] )
		{
			$f_stuff	= explode("&", $topicData['moved_to']);
			$_topic		= $this->DB->buildAndFetch( array( 'select' => 'title_seo', 'from' => 'topics', 'where' => 'tid=' . $f_stuff[0] ) );
			
			/**
			 * Mark redirect links as read too
			 * 
			 * @link	http://community.invisionpower.com/tracker/issue-36985-linked-topics-readunread-status/
			 */
			$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $forumData['id'], 'itemID' => $topicData['tid'] ), 'forums' );
			
			$this->registry->output->silentRedirect( $this->settings['base_url'] . "showtopic={$f_stuff[0]}", $_topic['title_seo'], true, 'showtopic' );
		}
		
		//-----------------------------------------
		// If this is a sub forum, we need to get
		// the cat details, and parent details
		//-----------------------------------------
		
	   	$this->nav = $this->registry->class_forums->forumsBreadcrumbNav( $forumData['id'] );
		
		//-----------------------------------------
		// Hi! Light?
		//-----------------------------------------
		
		$hl = !empty( $this->request['hl'] ) ? '&amp;hl=' . $this->request['hl'] : '';
		
		//-----------------------------------------
		// If we can see queued topics, add count
		//-----------------------------------------
		
		if ( $this->registry->class_forums->canQueuePosts($forumData['id']) )
		{
			if( isset( $this->request['modfilter'] ) AND $this->request['modfilter'] == 'invisible_posts' )
			{
				$topicData['posts'] = intval( $topicData['topic_queuedposts'] );
			}
			else
			{
				$topicData['posts'] += intval( $topicData['topic_queuedposts'] );
			}
		}
		
		if ( $permissionData['softDeleteSee'] AND $topicData['topic_deleted_posts'] )
		{
			$topicData['posts'] += intval( $topicData['topic_deleted_posts'] );
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------

		if( $this->request['modfilter'] )
		{
			$hl	.= "&amp;modfilter=" . $this->request['modfilter'];
		}
		
		$topicData['SHOW_PAGES']
			= $this->registry->output->generatePagination( array( 
																	'totalItems'		=> ($topicData['posts']+1),
																	'itemsPerPage'		=> $this->settings['display_max_posts'],
																	'currentStartValue'	=> $this->first,
																	'seoTitle'			=> $topicData['title_seo'],
																	'realTitle'			=> $topicData['title'],
																	'seoTemplate'		=> 'showtopic',
 																	'baseUrl'			=> "showtopic=".$topicData['tid'].$hl ) );
								   
		//-----------------------------------------
		// Fix up some of the words
		//-----------------------------------------
		
		$topicData['TOPIC_START_DATE'] = $this->registry->class_localization->getDate( $topicData['start_date'], 'LONG' );
		
		$this->lang->words['topic_stats'] = str_replace( "<#START#>", $topicData['TOPIC_START_DATE'], $this->lang->words['topic_stats']);
		$this->lang->words['topic_stats'] = str_replace( "<#POSTS#>", $topicData['posts']		   , $this->lang->words['topic_stats']);
		
		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------
		
		$this->qpids = IPSCookie::get('mqtids');
		
		//-----------------------------------------
		// Multi PIDS?
		//-----------------------------------------
		
		$this->request['selectedpids'] = ! empty( $this->request['selectedpids'] ) ? $this->request['selectedpids'] : IPSCookie::get('modpids');
		$this->request['selectedpidcount'] = 0 ;

		IPSCookie::set('modpids', '', 0);
		
		IPSDebug::setMemoryDebugFlag( "TOPIC: topics.php::topicSetUp", $_before );
		
		return $topicData;
	}
}