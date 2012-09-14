<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Moderator actions
 * Last Updated: $Date: 2012-06-04 12:04:53 -0400 (Mon, 04 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10862 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class moderatorLibrary
{
	/**
	 * Moderator information
	 *
	 * @var		array		Array of moderator details
	 */
	public $moderator		= array();

	/**
	 * Forum information
	 *
	 * @var		array		Array of forum details
	 */
	public $forum			= array();

	/**
	 * Topic information
	 *
	 * @var		array		Array of topic details
	 */
	public $topic			= array();
	
	/**
	 * Error code encountered
	 *
	 * @var		string		Error code
	 */
	public $error 			= "";

	/**
	 * Stored statement
	 *
	 * @var		string		Stored multi-mod statement
	 */
	public $stm				= "";

	/**#@+
	 * Registry Object Shortcuts
	 * 
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Check for class_forums */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
			$this->registry->class_forums->forumsInit();
		}
		
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('tags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' ) );
		}
	}
	
	/**
	 * Initialization
	 *
	 * @param	array 		Forum array
	 * @param 	array 		[Optional] Topic array
	 * @param	array 		[Optional] Moderator array
	 * @return	boolean		True
	 */
	public function init($forum="", $topic="", $moderator="")
	{
		$this->forum = $forum;
		
		if ( is_array($topic) )
		{
			$this->topic = $topic;
		}
		
		if ( is_array($moderator) )
		{
			$this->moderator = $moderator;
		}
		
		return true;
	}
	
	/**
	 * Toggle approve status of content by a member
	 * WARNING: This is a utility function. No permission checks are performed. 
	 *
	 * @param	int			Member ID (topic starter / post author)
	 * @param	boolean		TRUE = approve, FALSE = unapprove
	 * @param	string		Option [ topics / replies / all ]
	 * @param	int			[ Optional: last X hours worth of data ]
	 * @return	boolean
	 */
	public function toggleApproveMemberContent( $memberID, $approve=FALSE, $option, $date=0 )
	{
		$memberID  = intval( $memberID );
		$date	   = intval( $date );
		$timeCut   = ( $date ) ? ( time() - ( $date * 3600 ) ) : 0;
		$topicFind = '';
		$postFind  = '';
		$forumIDs  = array();
		$topicIDs  = array();
		$followTids = array();
		
		if ( ! $memberID )
		{
			return FALSE;
		}
		
		switch ( $option )
		{
			default:
			case 'all':
			case 'both':
			case 'topics':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
				$topicFind  = 'starter_id=' . $memberID;
				$topicFind .= ( $timeCut ) ? ' AND start_date > ' . $timeCut : '';
			break;
			case 'replies':
			case 'posts':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
			break;
		}
		
		//-----------------------------------------
		// Find forums..
		//-----------------------------------------
		
		if ( $topicFind )
		{
			$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'forum_id' ) . ',tid',
							  		 'from'   => 'topics',
									 'where'  => $topicFind ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forumIDs[ $row['forum_id'] ]	= $row['forum_id'];
				$topicIDs[ $row['tid'] ]		= $row['tid'];
				$followTids[ $row['tid'] ]		= $row['tid'];
			}
		}
		
		if ( $postFind )
		{
			$this->DB->build( array( 'select'   => $this->DB->buildDistinct( 't.forum_id' ) . ',t.tid',
							  		 'from'     => array( 'posts' => 'p' ),
									 'where'    => $postFind,
									 'add_join' => array( array( 'select' => '',
																 'from'   => array( 'topics' => 't' ),
																 'where'  => 'p.topic_id=t.tid' ) ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forumIDs[ $row['forum_id'] ]	= $row['forum_id'];
				$topicIDs[ $row['tid'] ]		= $row['tid'];
			}
		}
		
		//-----------------------------------------
		// Run...
		//-----------------------------------------
		
		if ( $topicFind AND count($topicIDs) )
		{
			$this->DB->update( 'topics', array( 'approved' => ( $approve === TRUE ) ? 1 : 0 ), $topicFind );
			
			/* Tagging */
			$this->registry->tags->updateVisibilityByMetaId( $topicIDs, ( $approve === TRUE ? 1 : 0 ) );
			
			/* Likes */
			if ( count( $followTids ) )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like	= classes_like::bootstrap( 'forums', 'topics' );
				$_like->toggleVisibility( $followTids, $approve );
			}
		}
		
		if ( $postFind )
		{
			$this->DB->update( 'posts', array( 'queued' => ( $approve === TRUE ) ? 0 : 1 ), $postFind );
		}

		if ( count( $topicIDs ) )
		{
			foreach( $topicIDs as $id )
			{
				$this->rebuildTopic( $id );
			}
		}

		if ( count( $forumIDs ) )
		{
			foreach( $forumIDs as $id )
			{
				$this->forumRecount( $id );
			}
		}
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		return TRUE;
	}
	
	/**
	 * Delete content by a member
	 * WARNING: This is a utility function. No permission checks are performed.
	 *
	 * @param	int			Member ID (topic starter / post author)
	 * @param	string		Option [ topics / replies / all ]
	 * @param	int			[ Optional: last X hours worth of data ]
	 * @return	boolean
	 */
	public function deleteMemberContent( $memberID, $option, $date=0 )
	{
		$memberID  = intval( $memberID );
		$date	   = intval( $date );
		$timeCut   = ( $date ) ? ( time() - ( $date * 3600 ) ) : 0;
		$topicFind = '';
		$postFind  = '';
		$topicIDs  = array();
		$postIDs   = array();
		$forumIDs  = array();
		
		if ( ! $memberID )
		{
			return FALSE;
		}
		
		switch ( $option )
		{
			default:
			case 'all':
			case 'both':
			case 'topics':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
				$topicFind  = 'starter_id=' . $memberID;
				$topicFind .= ( $timeCut ) ? ' AND start_date > ' . $timeCut : '';
			break;
			case 'replies':
			case 'posts':
				$postFind  = 'author_id=' . $memberID . ' AND new_topic=0';
				$postFind .= ( $timeCut ) ? ' AND post_date > ' . $timeCut : '';
			break;
		}
		
		//-----------------------------------------
		// Run...
		//-----------------------------------------
		
		if ( $topicFind )
		{
			/* Update the posts to delete */
			$this->DB->update( 'topics', array( 'approved' => $this->registry->class_forums->fetchTopicHiddenFlag('pdelete') ), $topicFind );
		
			$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'forum_id' ),
							  		 'from'   => 'topics',
									 'where'  => $topicFind ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forumIDs[ $row['forum_id'] ] = $row['forum_id'];
			}
			
			/* Fetch unique topic IDs */
			$this->DB->build( array( 'select'   => 'tid',
							  		 'from'     => 'topics',
									 'where'    => $topicFind,
									 'order'    => 'tid DESC',
									 'limit'    => array( 0, 2000 ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$tagTopicIDs[] = $row['tid'];
			}
			
			if ( count( $tagTopicIDs ) )
			{
				/* Tagging */
				try
				{
					$this->registry->tags->updateVisibilityByMetaId( $tagTopicIDs, 0 );
				}
				catch ( Exception $e ) { }
				
				/* Likes */
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like	= classes_like::bootstrap( 'forums', 'topics' );
				$_like->toggleVisibility( $tagTopicIDs, false );
			}
		}
		
		if ( $postFind )
		{
			/* Update the posts to delete */
			$this->DB->update( 'posts', array( 'queued' => $this->registry->class_forums->fetchPostHiddenFlag('pdelete'), 'pdelete_time' => IPS_UNIX_TIME_NOW ), $postFind );
		
			/* Fetch unique forum IDs */
			$this->DB->build( array( 'select'   => $this->DB->buildDistinct( 't.forum_id' ),
							  		 'from'     => array( 'topics' => 't' ),
									 'where'    => 'p.author_id=' . $memberID . ' AND p.new_topic=0 AND p.topic_id=t.tid',
									 'add_join' => array( array( 'select' => '',
																 'from'   => array( 'posts' => 'p' ),
																 'type'   => 'inner' ) ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$forumIDs[ $row['forum_id'] ] = $row['forum_id'];
			}
			
			/* Fetch unique topic IDs */
			$this->DB->build( array( 'select'   => $this->DB->buildDistinct( 'topic_id' ),
							  		 'from'     => 'posts',
									 'where'    => $postFind,
									 'order'    => 'topic_id DESC',
									 'limit'    => array( 0, 2000 ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$topicIDs[ $row['topic_id'] ] = $row['topic_id'];
			}
		}
		
		//-----------------------------------------
		// Delete topics...
		//-----------------------------------------
		
		if ( is_array( $topicIDs ) AND count( $topicIDs ) )
		{
			foreach( $topicIDs as $id )
			{
				$this->rebuildTopic( $id, 0 );
			}
		}
		
		if ( count( $forumIDs ) )
		{
			foreach( $forumIDs as $id )
			{
				$this->registry->class_forums->allForums[ $id ]['_update_deletion'] = 1;
				$this->forumRecount( $id );
			}
		}
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		return TRUE;
	}
	
	/**
	 * Delete / undelete posts
	 *
	 * @param	array 		Array of Post IDs
	 * @param	boolean		Approve (TRUE) / Unapprove (FALSE)
	 * @param	int			Fix so posts can only come from a specific topic ID
	 * @return	boolean
	 */
	public function postToggleSoftDelete( $postIDs, $delete=FALSE, $reason='', $topicIDFix=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_restoreTopic	= 1;
		$_deletedPost	= 0;
		$_pdeleteTs		= 0;
		
		if ( $delete === TRUE )
		{
			$_restoreTopic	= -1;
			$_deletedPost	= 2;
			$_pdeleteTs		= IPS_UNIX_TIME_NOW;
		}
		
		$_topics	= array();
		$_forumIDs	= array();
		$_pids		= IPSLib::cleanIntArray( $postIDs );
		$_tids		= array();
		
		//-----------------------------------------
		// Fetch distinct topic IDs
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'p.topic_id' ),
								 'from'   => array( 'posts' => 'p' ),
								 'where'  => 'p.pid IN (' . implode( ',', $postIDs ) . ')',
								 'add_join' => array( array( 'select' => 't.*',
															 'from'   => array( 'topics' => 't' ),
															 'where'  => 'p.topic_id=t.tid',
															 'type'   => 'inner' ) ) ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$_topics[ $row['topic_id'] ] = $row;
		}

		//-----------------------------------------
		// Did we get the first post too?
		//-----------------------------------------
		
		foreach( $_topics as $tid => $topicData )
		{
			/* Fix to a topic? */
			if ( $topicIDFix AND ( $topicIDFix != $tid ) )
			{
				continue;
			}
			
			if ( $this->registry->getClass('topics')->isArchived( $topicData ) )
			{
				continue;
			}
			
			$_forumIDs[] = $topicData['forum_id'];
			
			if ( in_array( $topicData['topic_firstpost'], $_pids ) )
			{
				$this->DB->update( 'topics', array( 'approved' => $_restoreTopic ), 'tid=' . $tid );
				
				$tmp    = $_pids;
				$_pids	= array();
			
				foreach( $tmp as $t )
				{
					if ( $t != $topicData['topic_firstpost'] )
					{
						$_pids[] = $t;
					}
					else
					{
						$_tids[ $topicData['tid'] ]	= $topicData['tid'];
					}
				}
			}
		}
	
		if ( count( $_pids ) )
		{
			$this->DB->update( 'posts', array( 'queued' => $_deletedPost, 'pdelete_time' => $_pdeleteTs ), 'pid IN (' . implode( ",", $_pids ) . ')' );
			
			if ( $_deletedPost )
			{
				foreach( $_pids as $_p )
				{
					/* Add entry to delete log innit */
					IPSDeleteLog::addEntry( $_p, 'post', $reason, $this->memberData );
				}
			}
			else
			{
				/* Un-deleting, so delete */
				IPSDeleteLog::removeEntries( $_pids, 'post' );
			}
		}
		
		if ( count( $_tids ) )
		{
			if ( $_deletedPost )
			{
				foreach( $_tids as $_t )
				{
					IPSDeleteLog::addEntry( $_t, 'topic', $reason, $this->memberData );
				}
			}
			else
			{
				/* Un-deleting, so delete */
				IPSDeleteLog::removeEntries( $_tids, 'topic' );
			}
		}
		
		if ( $delete )
		{
			/* Delete from recent posts */
			$this->registry->topics->deleteRecentPost( array( 'post_id' => $_pids ) );
		
			foreach( $_topics as $tid => $topicData )
			{
				$this->addModerateLog( $topicData['forum_id'], $tid, 0, $topicData['title'], sprintf( $this->lang->words['acp_softdeleted_posts'], count( $_pids ), $topicData['title'] ) );
			}
			
			/* Run moderation sync */
			$this->runModSync( 'postHide', $_pids );
		}
		else
		{
			/* Restore from recent posts */
			$this->registry->topics->restoreRecentPost( array( 'post_id' => $_pids ) );
			
			foreach( $_topics as $tid => $topicData )
			{
				$this->addModerateLog( $topicData['forum_id'], $tid, 0, $topicData['title'], sprintf( $this->lang->words['acp_unsoftdeleted_posts'], count( $_pids ), $topicData['title'] ) );
			}
			
			/* Run moderation sync */
			$this->runModSync( 'postUnhide', $_pids );
		}
		
		foreach( $_topics as $tid => $topicData )
		{
			$this->rebuildTopic( $tid );
		}
		
		if ( count( $_forumIDs ) )
		{
			foreach( $_forumIDs as $_fid )
			{
				$this->forumRecount( $_fid );
			}
		}
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		return TRUE;
	}
	
	/**
	 * Approve / unapprove posts
	 *
	 * @param	array 		Array of Post IDs
	 * @param	boolean		Approve (TRUE) / Unapprove (FALSE)
	 * @param	int			Fix so posts can only come from a specific topic ID
	 * @return	boolean
	 */
	public function postToggleApprove( $postIDs, $approve=FALSE, $topicIDFix=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_approveTopic	= 1;
		$_queuedPost	= 0;
		
		if ( $approve === FALSE )
		{
			$_approveTopic = 0;
			$_queuedPost   = 1;
		}
		
		$_topics	= array();
		$_forumIDs	= array();
		$_pids		= IPSLib::cleanIntArray( $postIDs );
		$_tids		= array();
						
		//-----------------------------------------
		// Fetch distinct topic IDs
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'p.topic_id' ) . ', p.author_id, p.author_name, p.post',
								 'from'   => array( 'posts' => 'p' ),
								 'where'  => 'p.pid IN (' . implode( ',', $postIDs ) . ')',
								 'add_join' => array( array( 'select' => 't.*',
															 'from'   => array( 'topics' => 't' ),
															 'where'  => 'p.topic_id=t.tid',
															 'type'   => 'inner' ) ) ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$_topics[ $row['topic_id'] ] = $row;
		}
		
		//-----------------------------------------
		// Did we get the first post too?
		//-----------------------------------------
		
		foreach( $_topics as $tid => $topicData )
		{
			/* Fix to a topic? */
			if ( $topicIDFix AND ( $topicIDFix != $tid ) )
			{
				continue;
			}
			
			if ( $this->registry->getClass('topics')->isArchived( $topicData ) )
			{
				continue;
			}
			
			$_forumIDs[] = $topicData['forum_id'];
			
			if ( in_array( $topicData['topic_firstpost'], $_pids ) )
			{
				$this->DB->update( 'topics', array( 'approved' => $_approveTopic ), 'tid=' . $tid );
			
				/* Unapprove the topic, but not the first post? */
				//if ( $_queuedPost )
				//{
					$tmp    = $_pids;
					$_pids	= array();
				
					foreach( $tmp as $t )
					{
						if ( $t != $topicData['topic_firstpost'] )
						{
							$_pids[] = $t;
						}
						else
						{
							/* @link	http://community.invisionpower.com/tracker/issue-33622-approveunapprove-and-old-posts */
							$_pids[]					= $t;
							$_tids[ $topicData['tid'] ]	= $topicData['tid'];
						}
					}
				//}
			}
		}
			
		if ( count( $_pids ) )
		{
			$this->DB->update( 'posts', array( 'queued' => $_queuedPost ), 'pid IN (' . implode( ",", $_pids ) . ')' );
		}
				
		if ( $approve )
		{
			$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_email_content' ), 'core' );
		
			/* Delete from recent posts */
			$this->registry->topics->restoreRecentPost( array( 'post_id' => $_pids ) );
			
			foreach( $_topics as $tid => $topicData )
			{
				$this->addModerateLog( $topicData['forum_id'], $tid, 0, $topicData['title'], sprintf( $this->lang->words['acp_approved_posts'], count( $_pids ), $topicData['title'] ) );
				
				/* Notifications are sent in clearModQueueTable now */
			}
			
			if( count($_pids) )
			{
				$this->clearModQueueTable( 'post', $_pids, true );
			}
			
			if( count($_tids) )
			{
				$this->clearModQueueTable( 'topic', $_tids, true );
			}
		}
		else
		{
			/* Delete from recent posts */
			$this->registry->topics->deleteRecentPost( array( 'post_id' => $_pids ) );
			
			foreach( $_topics as $tid => $topicData )
			{
				$this->addModerateLog( $topicData['forum_id'], $tid, 0, $topicData['title'], sprintf( $this->lang->words['acp_unapproved_posts'], count( $_pids ), $topicData['title'] ) );
			}
		}
		
		foreach( $_topics as $tid => $topicData )
		{
			$this->rebuildTopic( $tid );
		}
		
		if ( count( $_forumIDs ) )
		{
			foreach( $_forumIDs as $_fid )
			{
				$this->forumRecount( $_fid );
			}
		}
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		return TRUE;
	}
	
	/**
	 * Clear out the mod-queue table appropriately
	 *
	 * @param	string		[topic|post] Type of item moved
	 * @param	mixed		ID of topic or post, or array of ids
	 * @param	boolean		Was content approved?
	 * @return	@e void
	 */
	public function clearModQueueTable( $type, $typeId, $approved=false )
	{
		//-----------------------------------------
		// Are we operating on one id, or an array
		//-----------------------------------------
		
		if( is_array($typeId) )
		{
			$where	= "type_id IN(" . implode( ',', IPSLib::cleanIntArray($typeId) ) . ")";
		}
		else
		{
			$where	= "type_id=" . intval($typeId);
		}

		//-----------------------------------------
		// Was content deleted
		//-----------------------------------------
				
		if( ! $approved )
		{
			$this->DB->delete( 'mod_queued_items', "type='{$type}' AND {$where}" );
		}

		//-----------------------------------------
		// No, then we are approving content
		//-----------------------------------------
		
		else
		{
			//-----------------------------------------
			// Get post class..
			//-----------------------------------------
	
			require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
			$_postClass  = new $classToLoad( $this->registry );
			
			//-----------------------------------------
			// Working with posts?
			//-----------------------------------------
			
			if( $type == 'post' )
			{
				IPSDebug::fireBug( 'info', array( 'type is post' ) );
				
				$this->DB->build( array(
										'select'	=> 'm.id',
										'from'		=> array( 'mod_queued_items' => 'm' ),
										'where'		=> "m.type='{$type}' AND m.{$where}",
										'add_join'	=> array(
															array(
																'select'	=> 'p.pid, p.post, p.author_id, p.post_date, p.topic_id',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=m.type_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 't.*',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
																),
															)
								)		);
				$outer = $this->DB->execute();

				while( $r = $this->DB->fetch($outer) )
				{
					$member	= IPSMember::load( $r['author_id'], 'extendedProfile,groups' );
					$_postClass->setPublished( true );
					$_postClass->setAuthor( $member );
					$_postClass->setForumData( $this->registry->class_forums->allForums[ $r['forum_id'] ] );
					
					$_postClass->incrementUsersPostCount();
					$_postClass->sendOutTrackedTopicEmails( $r, $r['post'] );
										
					$this->DB->delete( 'mod_queued_items', 'id=' . $r['id'] );
				}
			}
			else
			{
				IPSDebug::fireBug( 'info', array( 'type is topic' ) );
				
				$this->DB->build( array(
										'select'	=> 'm.id',
										'from'		=> array( 'mod_queued_items' => 'm' ),
										'where'		=> "m.type='{$type}' AND m.{$where}",
										'add_join'	=> array(
															array(
																'select'	=> 't.*',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=m.type_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'p.pid, p.post, p.post_date',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=t.topic_firstpost',
																'type'		=> 'left',
																),
															)
								)		);
				$outer = $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					$member	= IPSMember::load( $r['starter_id'], 'extendedProfile,groups' );
					$_postClass->setPublished( true );
					$_postClass->setAuthor( $member );
					$_postClass->setForumData( $this->registry->class_forums->allForums[ $r['forum_id'] ] );
					$_postClass->incrementUsersPostCount();
					
					$_postClass->sendOutTrackedForumEmails( $this->registry->class_forums->getForumById( $r['forum_id'] ), $r, $r['post'] );
										
					$this->DB->delete( 'mod_queued_items', 'id=' . $r['id'] );
				}
				
			}
		}
	}

	/**
	 * Delete a post
	 *
	 * @param	mixed 		Post id | Array of post ids
	 * @return	array		Topic IDs
	 */
	public function postDelete($id)
	{
		$posts			= array();
		$attach_tid		= array();
		$attach_ids		= array();
		$topics			= array();
		$fids           = array();
		$this->error	= "";

		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$pid = " IN(" . implode( ",", $id ) . ")";
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$pid   = "={$id}";
			}
			else
			{
				return false;
			}
		}
		
		/* Set ids */
		$_ids = ( is_array( $id ) ) ? $id : array( $id );
		
		/* Get data */
		$this->DB->build( array( 'select' => 'p.pid, p.topic_id, p.new_topic',
								 'from'   => array( 'posts' => 'p' ),
								 'where'  => 'p.pid' . $pid,
								 'add_join' => array( array( 'select' => 't.*',
															 'from'   => array('topics' => 't' ),
															 'where'  => 'p.topic_id=t.tid' ) ) ) );
		$q = $this->DB->execute();
		
		while ( $r = $this->DB->fetch( $q ) )
		{
			if ( ! $this->registry->getClass('topics')->isArchived( $r ) )
			{
				$posts[ $r['pid'] ]			= $r['topic_id'];
				$topics[ $r['topic_id'] ]	= 1;
			}
		}
		
		/* Reset keys */
		$pid = " IN(" . implode( ",", array_keys( $posts ) ) . ")";
			
		/* Update the posts to delete */
		$this->DB->update( 'posts', array( 'queued' => $this->registry->class_forums->fetchPostHiddenFlag('pdelete'), 'pdelete_time' => IPS_UNIX_TIME_NOW ), 'pid' . $pid );
		
		/* Delete from recent posts */
		$this->registry->topics->deleteRecentPost( array( 'post_id' => $_ids ) );
		
		//-----------------------------------------
		// Update the stats
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'stats', 'global' );

		//-----------------------------------------
		// Update all relevant topics
		//-----------------------------------------
		
		foreach( array_keys($topics) as $tid )
		{
			$this->rebuildTopic( $tid );
		}
		
		if ( count( $topics ) )
		{
			$this->DB->build( array( 'select' => 'forum_id',
									 'from'   => 'topics',
									 'where'  => 'tid IN (' . implode( ',', array_keys( $topics ) ) . ')' ) );
									 
			$i = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $i ) )
			{
				$fids[ $row['forum_id'] ] = $row['forum_id'];
			}
		}
		
		if ( count( $fids ) )
		{
			foreach( $fids as $f )
			{
				$this->forumRecount( $f );
			}
		}
		
		/* Run moderation sync */
		$this->runModSync( 'postDelete', array_keys( $posts ) );
		
		//-----------------------------------------
		// Log and return
		//-----------------------------------------
		
		$pid = str_replace( array( 'IN', '(', ')', '=' ), '', $pid );
		
		$this->addModerateLog( "", "", "", $this->lang->words['mod_from_id'] . ' ' . implode( ',', array_keys($topics) ), sprintf( $this->lang->words['multi_post_delete'], implode( ', ', $_ids ) ) );
		
		return array_keys($topics);
	}
	
	/**
	 * Delete a post
	 *
	 * @param	mixed 		Post id | Array of post ids
	 * @param	bool		Skip rebuilding stats/forums
	 * @param	bool		Quick mode (will skip loading data and rebuild - only use if you are processing many posts and will rebuild manually later)
	 * @return	array		Topic IDs
	 */
	public function postDeleteFromDb( $id, $nostats=false, $quick=false )
	{
		$posts			= array();
		$attach_tid		= array();
		$attach_ids		= array();
		$topics			= array();
		$fids           = array();
		$this->error	= "";

		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$pid = " IN(" . implode( ",", $id ) . ")";
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$pid   = "={$id}";
			}
			else
			{
				return false;
			}
		}
		
		/* Remove from deletion log */
		$_ids = ( is_array( $id ) ) ? $id : array( $id );
		IPSDeleteLog::removeEntries( $_ids, 'post', TRUE );
		
		//-----------------------------------------
		// Get Stuff
		//-----------------------------------------
		
		/* Get IDs */
		if ( $quick )
		{
			$posts = array_flip( $_ids );
		}
		else
		{
			$this->DB->build( array( 'select' => 'p.pid, p.topic_id, p.new_topic',
									 'from'   => array( 'posts' => 'p' ),
									 'where'  => 'p.pid' . $pid,
									 'add_join' => array( array( 'select' => 't.*',
																 'from'   => array('topics' => 't' ),
																 'where'  => 'p.topic_id=t.tid' ) ) ) );
			$q = $this->DB->execute();
			
			while ( $r = $this->DB->fetch( $q ) )
			{
				if ( ! $this->registry->getClass('topics')->isArchived( $r ) )
				{
					$posts[ $r['pid'] ]			= $r['topic_id'];
					$topics[ $r['topic_id'] ]	= 1;
				}
			}
		}
		
		/* Reset keys */
		$pid = " IN(" . implode( ",", array_keys( $posts ) ) . ")";
		
		/* Delete from rep cache */
		$this->DB->delete( 'reputation_cache' , "app='forums' AND type='pid' AND type_id" . $pid );
		$this->DB->delete( 'reputation_index' , "app='forums' AND type='pid' AND type_id" . $pid );
		
		/* And totals */
		foreach( array_keys($posts) as $post )
		{
			$this->DB->delete( 'reputation_totals', "rt_key=MD5('forums;pid;" . $post . "') AND rt_type_id=" . $post );
		}
		
		/* Delete from recent posts */
		$this->registry->topics->deleteRecentPost( array( 'post_id' => array_keys( $posts ) ) );
		
		//-----------------------------------------
		// Is there an attachment to this post?
		//-----------------------------------------
		
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'post';
		$class_attach->init();
		
		$class_attach->bulkRemoveAttachment( array_keys( $posts ) );
		
		//-----------------------------------------
		// delete the post
		//-----------------------------------------
		
		$this->DB->delete( 'posts', "pid" . $pid );
		
		/* Remove cache content */
		IPSContentCache::drop( 'post', array_keys( $posts ) );

		//-----------------------------------------
		// Update all relevant topics
		//-----------------------------------------
		
		if ( !$quick )
		{
			foreach( array_keys($topics) as $tid )
			{
				$this->rebuildTopic( $tid );
			}
		}
		
		//-----------------------------------------
		// Update the stats
		//-----------------------------------------
		
		if( !$nostats and !$quick )
		{
			if ( count( $topics ) )
			{
				$this->DB->build( array( 'select' => 'forum_id',
										 'from'   => 'topics',
										 'where'  => 'tid IN (' . implode( ',', array_keys( $topics ) ) . ')' ) );
										 
				$i = $this->DB->execute();
				
				while( $row = $this->DB->fetch( $i ) )
				{
					$fids[ $row['forum_id'] ] = $row['forum_id'];
				}
			}
			
			if ( count( $fids ) )
			{
				foreach( $fids as $f )
				{
					$this->forumRecount( $f );
				}
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		
		/* Run moderation sync */
		$this->runModSync( 'postDelete', array_keys( $posts ) );
		
		//-----------------------------------------
		// Log and return
		//-----------------------------------------
		
		if ( $quick )
		{
			return TRUE;
		}
		else
		{
			$pid = str_replace( array( 'IN', '(', ')', '=' ), '', $pid );
			$this->addModerateLog( "", "", "", $this->lang->words['mod_from_id'] . ' ' . implode( ',', array_keys($topics) ), sprintf( $this->lang->words['multi_post_delete'], implode( ',', $_ids ) ) );
			
			return array_keys($topics);
		}
	}
	
	/**
	 * Rebuild a topic
	 *
	 * @param	integer 	Topic id
	 * @return	boolean		Rebuild complete
	 */
	public function rebuildTopic( $tid )
	{
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		$this->registry->topics->rebuildTopic( $tid );
		
		return true;
	}
		
	/**
	 * Add a reply to a topic
	 *
	 * @param	string 		Post contnet
	 * @param 	array		Array of topic ids to apply this reply to
	 * @param	boolean		Increment post count?
	 * @return	boolean		Reply added
	 * @deprecated
	 */
	public function topicAddReply( $post="", $tids=array(), $incpost=false )
	{
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
		
		$r = TRUE;
		foreach ( $tids as $row )
		{
			try
			{
				$_postClass  = new $classToLoad( $this->registry );
				$_postClass->setBypassPermissionCheck( true );
				$_postClass->setForumId( $row[1] );
				$_postClass->setTopicId( $row[0] );
				$_postClass->setAuthor( $this->memberData );
				$_postClass->setPostContent( $post );
				$_postClass->setSettings( array(
					'enableSignature'	=> TRUE,
					'enableEmoticons'	=> TRUE,
					) );
				$_postClass->setIncrementPostCountFlag( $incpost ? true : false );
				
				$_postClass->addReply();
			}
			catch ( Exception $e )
			{
				$r = FALSE;
			}
		}
		
		return $r;
	}
	
	/**
	 * Close a topic
	 *
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicClose( $id )
	{
		$this->stmInit();
		$this->stmAddClose();
		$this->stmExec($id);
		return TRUE;
	}
	
	
	/**
	 * Open a topic
	 *
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicOpen($id)
	{
		$this->stmInit();
		$this->stmAddOpen();
		$this->stmExec($id);
		return TRUE;
	}
	
	/**
	 * Pin a topic
	 *
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicPin($id)
	{
		$this->stmInit();
		$this->stmAddPin();
		$this->stmExec($id);
		return TRUE;
	}
	
	/**
	 * Unpin a topic
	 *
	 * @param	integer 	Topic id
	 * @return	boolean
	 */
	public function topicUnpin($id)
	{
		$this->stmInit();
		$this->stmAddUnpin();
		$this->stmExec($id);
		return TRUE;
	}
	
	/**
	 * Delete a topic
	 *
	 * @param	mixed 		Topic id | Array of topic ids
	 * @param	boolean		Skip updating the stats
	 * @return	boolean
	 */
	public function topicDelete( $id, $nostats=0 )
	{
		$posts			= array();
		$attach			= array();
		$this->error	= "";
		$ids            = array();
		
		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$ids = $id;
				$tid = " IN(" . implode( ",", $id ) . ")";
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$tid   = "={$id}";
				$ids   = array( $id );
			}
			else
			{
				return false;
			}
		}
		
		/* Set ids */
		$_ids = ( is_array( $id ) ) ? $id : array( $id );
		
		/* GET AND EXAMINE */
		$topics = $this->registry->topics->getTopics( array( 'topicId' => $_ids, 'archiveState' => array( 'not', 'exclude' ), 'topicType' => array( 'all' ) ) );
		
		if ( ! count( $topics ) )
		{
			return false;
		}
		else
		{
			$ids = array_keys( $topics );
			$tid = " IN(" . implode( ",", $ids ) . ")";
		}
		
		/* Update the posts to delete */
		$this->DB->update( 'topics', array( 'approved' => $this->registry->class_forums->fetchTopicHiddenFlag('pdelete'), 'tdelete_time' => IPS_UNIX_TIME_NOW ), 'tid' . $tid );
		
		/* Delete from recent posts */
		$this->registry->topics->deleteRecentPost( array( 'post_topic_id' => $ids ) );
		
		/* Tagging */
		$this->registry->tags->updateVisibilityByMetaId( $ids, 0 );

		/* Likes */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->toggleVisibility( $ids, false );
		
		//-----------------------------------------
		// Recount forum...
		//-----------------------------------------
		
		if ( !$nostats )
		{
			if ( $this->forum['id'] )
			{
				$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
				$this->forumRecount( $this->forum['id'] );
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		
		/* Run moderation sync */
		$this->runModSync( 'topicDelete', $_ids );

		return TRUE;
	}
	
	/**
	 * Delete a topic
	 *
	 * @param	mixed 		Topic id | Array of topic ids
	 * @param	boolean		Skip updating the stats
	 * @return	boolean
	 */
	public function topicDeleteFromDB( $id, $nostats=0 )
	{
		$posts			= array();
		$attach			= array();
		$this->error	= "";
		$ids            = array();
		
		if ( is_array( $id ) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				$ids = $id;
				$tid = " IN(" . implode( ",", $id ) . ")";
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( intval($id) )
			{
				$ids = array( $id );
				$tid   = "={$id}";
			}
			else
			{
				return false;
			}
		}
		
		/* GET AND EXAMINE */
		$topics = $this->registry->topics->getTopics( array( 'topicId' => $ids, 'archiveState' => array( 'not', 'exclude' ), 'topicType' => array( 'all' ) ) );
				
		if ( ! count( $topics ) )
		{
			return false;
		}
		else
		{
			$ids = array_keys( $topics );
			$tid = " IN(" . implode( ",", $ids ) . ")";
		}
		
		/* Remove from deletion log */
		IPSDeleteLog::removeEntries( $ids, 'topic', TRUE );
		
		/* Tagging */
		$this->registry->tags->deleteByMetaId( $ids );
		
		/* Delete from recent posts */
		$this->registry->topics->deleteRecentPost( array( 'post_topic_id' => $ids ) );
		
		//-----------------------------------------
		// Remove polls assigned to this topic
		//-----------------------------------------
		
		$this->DB->delete( 'polls', "tid" . $tid );
		$this->DB->delete( 'voters', "tid" . $tid );
		$this->DB->delete( 'topic_ratings', "rating_tid" . $tid );	
		$this->DB->delete( 'topic_views', "views_tid" . $tid );
		$this->DB->delete( 'topics', "tid" . $tid );

		//-----------------------------------------
		// Like class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->remove( $ids );
				
		//-----------------------------------------
		// Get PIDS for attachment deletion
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'pid', 'from' => 'posts', 'where' => "topic_id" . $tid ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$posts[] = $r['pid'];
		}
		
		/* Remove cache content */
		IPSContentCache::drop( 'post', $posts );
		
		//-----------------------------------------
		// Remove the attachments
		//-----------------------------------------
		
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'post';
		$class_attach->init();
		
		$class_attach->bulkRemoveAttachment( $posts );		
		
		//-----------------------------------------
		// Remove the posts
		//-----------------------------------------
		
		$this->DB->delete( 'posts', "topic_id" . $tid );
		
		if ( count( $posts ) )
		{
			$this->DB->delete( 'reputation_cache' , "app='forums' AND type='pid' AND type_id IN (" . implode( ',', $posts ) . ")");
			$this->DB->delete( 'reputation_index' , "app='forums' AND type='pid' AND type_id IN (" . implode( ',', $posts ) . ")" );
			$this->DB->delete( 'reputation_totals', "rt_key=MD5('forums;pid') AND rt_type_id IN (" . implode( ',', $posts ) . ")" );
		}
		
		//-----------------------------------------
		// Recount forum...
		//-----------------------------------------
		
		if ( !$nostats )
		{
			if ( $this->forum['id'] )
			{
				$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
				$this->forumRecount( $this->forum['id'] );
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		
		/* Run moderation sync */
		$this->runModSync( 'topicDelete', $ids );

		return TRUE;
	}

	/**
	 * Move a topic
	 *
	 * @param	mixed 		Topic id | Array of topic ids
	 * @param	integer		Source forum
	 * @param	integer		Move to forum
	 * @param	boolean		Leave the 'link'
	 * @return	boolean
	 */
	public function topicMove($topics, $source=0, $moveto=0, $leavelink=0)
	{
		$this->error	= "";
		$source			= intval($source);
		$moveto			= intval($moveto);
		$forumIDSQL     = ( $source ) ? " forum_id={$source} AND " : '';
		$ids            = array();
		
		if ( is_array( $topics ) )
		{
			$topics = IPSLib::cleanIntArray( $topics );
			
			if ( count($topics) > 0 )
			{
				$tid = " IN(" . implode( ",", $topics ) . ")";
				$ids = $topics;
			}
			else
			{
				return false;
			}

			//-----------------------------------------
			// Mark as read in new forum
			//-----------------------------------------

			foreach( $topics as $_tid )
			{	
				$this->registry->classItemMarking->markRead( array( 'forumID' => $moveto, 'itemID' => $_tid ), 'forums' );
			}
		}
		else
		{
			if ( intval($topics) )
			{
				$tid   = "={$topics}";
				$ids   = array( $topics );
			}
			else
			{
				return false;
			}
			
			//-----------------------------------------
			// Mark as read in new forum
			//-----------------------------------------
			
			$this->registry->classItemMarking->markRead( array( 'forumID' => $moveto, 'itemID' => $topics ), 'forums' );
		}
		
		/* GET AND EXAMINE */
		$_topics = $this->registry->topics->getTopics( array( 'topicId' => $ids, 'archiveState' => array( 'not', 'exclude' ), 'topicType' => array( 'all' ) ) );
		
		if ( ! count( $_topics ) )
		{
			return false;
		}
		else
		{
			$ids = array_keys( $_topics );
			$tid = " IN(" . implode( ",", $ids ) . ")";
		}
		
		/* Update recent posts */
		$this->registry->topics->updateRecentPost( array( 'post_forum_id' => $moveto ), array( 'post_topic_id' => $ids ) );
		
		//-----------------------------------------
		// Update the topic
		//-----------------------------------------
		
		$this->DB->update( 'topics', array( 'forum_id' => $moveto ), $forumIDSQL . "tid" . $tid );
		
		//-----------------------------------------
		// Update the polls
		//-----------------------------------------
		
		$this->DB->update( 'polls', array( 'forum_id' => $moveto ), $forumIDSQL . "tid" . $tid );
			
		//-----------------------------------------
		// Update the voters
		//-----------------------------------------
		
		$this->DB->update( 'voters', array( 'forum_id' => $moveto ), $forumIDSQL . "tid" . $tid );
		
		//-----------------------------------------
		// Are we leaving a stink er link?
		//-----------------------------------------
		
		if ( $leavelink AND $source )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'topics', 'where' => "tid" . $tid ) );
			$oq = $this->DB->execute();

			while ( $row = $this->DB->fetch($oq) )
			{		    
				$this->DB->setDataType( array( 'title', 'starter_name', 'last_poster_name', 'seo_last_name' ), 'string' );

				$this->DB->insert( 'topics', array (
														'title'				=> $row['title'],
														'state'				=> 'link',
														'posts'				=> 0,
														'views'				=> 0,
														'starter_id'		=> $row['starter_id'],
														'start_date'		=> $row['start_date'],
														'starter_name'		=> $row['starter_name'],
														'seo_first_name'    => IPSText::makeSeoTitle( $row['starter_name'] ),
														'last_post'			=> $row['last_post'],
														'forum_id'			=> $source,
														'approved'			=> 1,
														'pinned'			=> 0,
														'moved_to'			=> $row['tid'] . '&' . $moveto,
														'moved_on'			=> time(),
														'last_poster_id'	=> $row['last_poster_id'],
														'last_poster_name'	=> $row['last_poster_name'],
														'seo_last_name'     => IPSText::makeSeoTitle( $row['starter_name'] ),
									)				);
			}
		
		}
		
		/* Tagging */
		$this->registry->tags->moveTagsToParentId( $ids, $moveto );
				
		//-----------------------------------------
		// Sort out subscriptions
		//-----------------------------------------
		
		$trid_to_delete	= array();

		//-----------------------------------------
		// Like class - remove if you don't have access
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$topics	= $_like->getDataByRelationshipId( $tid );
		
		if( count($topics) )
		{
			foreach( $topics as $r )
			{
				$perm_id	= $r['org_perm_id'] ? $r['org_perm_id'] : $r['g_perm_id'];
				
				if ( $this->registry->permissions->check( 'read', $this->registry->class_forums->allForums[ $moveto ], explode( ',', IPSText::cleanPermString( $perm_id ) ) ) !== TRUE )
				{
					$trid_to_delete[]	= $r['like_id'];
				}
			}
		}

		if ( count($trid_to_delete) > 0 )
		{
			$this->DB->delete( 'core_like', "like_id IN('" . implode( "','", $trid_to_delete ) . "')" );
		}
		
		return true;
	}
	
	/**
	 * Delete / undelete posts
	 *
	 * @param	array 		Array of Post IDs
	 * @param	boolean		Approve (TRUE) / Unapprove (FALSE)
	 * @return	boolean
	 */
	public function topicToggleSoftDelete( $topicIds, $delete=FALSE, $reason='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_restoreTopic	= 1;
		
		if ( $delete === TRUE )
		{
			$_restoreTopic = -1;
		}
		
		$_topics	= array();
		$_forumIDs	= array();
		$_tids		= IPSLib::cleanIntArray( $topicIds );
		
		//-----------------------------------------
		// Fetch distinct topic IDs
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'topics',
								 'where'  => 'tid IN (' . implode( ',', $_tids ) . ')' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( ! $this->registry->getClass('topics')->isArchived( $row ) )
			{
				$_topics[ $row['tid'] ]   = $row;
				$_forumIDs[ $row['forum_id'] ] = $row['forum_id'];
			}
		}

		//-----------------------------------------
		// Did we get the first post too?
		//-----------------------------------------
		
		foreach( $_topics as $tid => $topicData )
		{
			$this->DB->update( 'topics', array( 'approved' => $_restoreTopic ), 'tid=' . $tid );
			
			if ( $delete )
			{
				IPSDeleteLog::addEntry( $tid, 'topic', $reason, $this->memberData );
			}
			
			/* Rebuild topic */
			$this->rebuildTopic( $tid );
			
			/* Mod log */
			$this->addModerateLog( $topicData['forum_id'], $tid, 0, $topicData['title'], sprintf( $this->lang->words['acp_altered_topics'], "approved={$_restoreTopic}", $topicData['title'] ) );
		}
		
		/* Restoring */
		if ( ! $delete )
		{
			/* Un-deleting, so delete */
			IPSDeleteLog::removeEntries( $_tids, 'post' );
			
			/* Delete from recent posts */
			$this->registry->topics->restoreRecentPost( array( 'post_topic_id' => $_tids ) );
			
			/* Run moderation sync */
			$this->runModSync( 'topicUnhide', $_tids );
		}
		else
		{
			/* Delete from recent posts */
			$this->registry->topics->deleteRecentPost( array( 'post_topic_id' => $_tids ) );
			
			/* Run moderation sync */
			$this->runModSync( 'topicHide', $_tids );
		}
		
		if ( count( $_forumIDs ) )
		{
			foreach( $_forumIDs as $_fid )
			{
				$this->forumRecount( $_fid );
			}
		}
		
		/* Tagging */
		$this->registry->tags->updateVisibilityByMetaId( $_tids, ( $delete ? 0 : 1 ) );

		/* Likes */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'topics' );
		$_like->toggleVisibility( $_tids, ( $delete ) ? 0 : 1 );
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		return TRUE;
	}

	/**
	 * Recount a forum
	 *
	 * @return	boolean
	 */
	public function forumRecount( $fid="" )
	{
		$fid = $fid ? intval($fid) : $this->forum['id'];
		
		$this->registry->class_forums->forumRebuild( $fid );
		
		return true;
	}
	
	
	/**
	 * Multi-statement init
	 *
	 * @return	boolean
	 */
	public function stmInit()
	{
		$this->stm	= array();
		
		return true;
	}
	
	/**
	 * Multi-statement execute
	 *
	 * @param 	mixed		Id | Array of ids
	 * @return	boolean
	 */
	public function stmExec($id)
	{
		if ( count($this->stm) < 1 )
		{
			return false;
		}
		
		$final_array = array();
		
		foreach( $this->stm as $real_array )
		{
			foreach( $real_array as $k => $v )
			{
				$final_array[ $k ] = $v;
			}
		}
		
		if ( isset( $final_array['approved'] ) and $final_array['approved'] == -1 )
		{
			$this->_addToSoftDeleteLog( $id );
		}

		if ( is_array($id) )
		{
			$id = IPSLib::cleanIntArray( $id );
			
			if ( count($id) > 0 )
			{
				/* Ensure we don't moderate topics in archives */
				$this->DB->update( 'topics', $final_array, $this->registry->class_forums->fetchTopicArchiveQuery( array('not', 'exclude' ) ) . " AND tid IN(" . implode( ",", $id ) . ")" );
			}
			else
			{
				return false;
			}
		}
		else if ( intval($id) != "" )
		{
			$this->DB->update( 'topics', $final_array, $this->registry->class_forums->fetchTopicArchiveQuery( array('not', 'exclude' ) ) . " AND tid=" . intval($id) );
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Add record to soft delete log
	 *
	 * @param	int|array	Topic ID(s)
	 */
	private function _addToSoftDeleteLog( $topicIds )
	{
		if ( !is_array( $topicIds ) )
		{
			$topicIds = array( $topicIds );
		}
		
		foreach ( $topicIds as $i )
		{
			$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
			$this->DB->replace( 'core_soft_delete_log', array(
				'sdl_obj_id'		=> $i,
				'sdl_obj_key'		=> 'topic',
				'sdl_obj_member_id'	=> $this->memberData['member_id'],
				'sdl_obj_date'		=> time(),
				'sdl_obj_reason'	=> $this->lang->words['mm_title'],
				'sdl_locked'		=> 0,
				), array( 'sdl_obj_id', 'sdl_obj_key' ) );
		}
	}
	
	/**
	 * Multi-statement pin topic
	 *
	 * @return	boolean
	 */
	public function stmAddPin()
	{
		$this->stm[] = array( 'pinned' => 1 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement unpin topic
	 *
	 * @return	boolean
	 */
	public function stmAddUnpin()
	{
		$this->stm[] = array( 'pinned' => 0 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement close topic
	 *
	 * @return	boolean
	 */
	public function stmAddClose()
	{
		$this->stm[] = array( 'state' => 'closed' );
		$this->stm[] = array( 'topic_open_time' => 0 );
		$this->stm[] = array( 'topic_close_time' => 0 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement open topic
	 *
	 * @return	boolean
	 */
	public function stmAddOpen()
	{
		$this->stm[] = array( 'state' => 'open' );
		$this->stm[] = array( 'topic_open_time' => 0 );
		$this->stm[] = array( 'topic_close_time' => 0 );
				
		return TRUE;
	}
	
	/**
	 * Multi-statement update topic title
	 *
	 * @return	boolean
	 */
	public function stmAddTitle($new_title='')
	{
		if ( $new_title == "" )
		{
			return FALSE;
		}
		
		$this->stm[] = array( 'title' => $new_title );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement approve topic
	 *
	 * @return	boolean
	 */
	public function stmAddApprove()
	{
		$this->stm[] = array( 'approved' => 1 );
		
		return TRUE;
	}
	
	/**
	 * Multi-statement unapprove topic
	 *
	 * @return	boolean
	 */
	public function stmAddUnapprove()
	{
		$this->stm[] = array( 'approved' => -1 );

		return TRUE;
	}
	
	/**
	 * Create 'where' clause for SQL forum pruning
	 *
	 * @return	boolean
	 */
	public function sqlPruneCreate( $forum_id, $starter_id="", $topic_state="", $post_min="", $date_exp="", $ignore_pin="" )
	{
		$sql = 'forum_id=' . intval($forum_id) . ' AND approved < 2';

		if ( intval($date_exp) )
		{
			$sql .= " AND last_post < " . intval( $date_exp );
		}
		
		if ( intval($starter_id) )
		{
			$sql .= " AND starter_id=" . intval( $starter_id );
			
		}
		
		if ( intval($post_min) )
		{
			$sql .= " AND posts < " . intval( $post_min );
		}
		
		if ($topic_state != 'all')
		{
			if ($topic_state)
			{
				$sql .= " AND state='{$topic_state}'";
			}
		}
		
		if ( $ignore_pin != "" )
		{
			$sql .= " AND pinned=0";
		}
		
		/* Archived */
		$sql .= ' AND ' . $this->registry->class_forums->fetchTopicArchiveQuery( array('not', 'exclude' ) );
		
		return $sql;
	}
	
	/**
	 * Determines if current member is authorized to use multi-mod
	 *
	 * @return	boolean
	 */
	public function mmAuthorize()
	{
		$pass_go = FALSE;
		
		if ( $this->memberData['member_id'] )
		{
			if ( $this->memberData['g_is_supmod'] )
			{ 
				$pass_go = TRUE;
			}
			else if ( $this->moderator['can_mm'] == 1 )
			{
				$pass_go = TRUE;
			}
		}
		
		return $pass_go;
	}
	
	/**
	 * Checks if multi-mod is allowed in a specified forum
	 *
	 * @return	boolean
	 */
	public function mmCheckIdInForum( $fid, $mm_data)
	{
		$retval = FALSE;
		
		if (  $mm_data['mm_forums'] == '*' OR strstr( "," . $mm_data['mm_forums'] . ",", "," . $fid . "," ) )
		{
			$retval = TRUE;
		}
		
		return $retval;	
	}
	
	/**
	 * Add an entry to the moderator log
	 *
	 * @param	integer		Forum id
	 * @param	integer		Topic id
	 * @param	string		Topic title
	 * @param	string		Title to add to moderator log
	 * @return	boolean
	 */
	public function addModerateLog($fid, $tid, $pid, $t_title, $mod_title='Unknown')
	{
		$this->DB->setDataType( 'member_name', 'string' );
		
		$this->DB->insert( 'moderator_logs', array (
												  'forum_id'		=> intval($fid),
												  'topic_id'		=> intval($tid),
												  'post_id'			=> intval($pid),
												  'member_id'		=> $this->memberData['member_id'],
												  'member_name'		=> $this->memberData['members_display_name'],
												  'ip_address'		=> $this->member->ip_address,
												  'http_referer'	=> htmlspecialchars( my_getenv('HTTP_REFERER') ),
												  'ctime'			=> time(),
												  'topic_title'		=> $t_title,
												  'action'			=> $mod_title,
												  'query_string'	=> htmlspecialchars( my_getenv('QUERY_STRING') ),
											  )  );
		return TRUE;
	}
	
	/**
	 * Run the forum mod sync system
	 *
	 * @param	string		Function to call
	 * @return	bool
	 */
	public function runModSync( $func )
	{
		/* ipsRegistry::$applications only contains apps with a public title #15785 */
		$app_cache = $this->registry->cache()->getCache('app_cache');
		
		/* Params */
		$params = func_get_args();
		array_shift( $params );

		/* Loop through applications */
		foreach( $app_cache as $app_dir => $app )
		{
			if( IPSLib::appIsInstalled( $app_dir ) )
			{
				/* Setup */
				$_file  = IPSLib::getAppDir( $app['app_directory'] ) . '/extensions/forumDataSync.php';
					
				/* Check for the file */
				if( is_file( $_file ) )
				{
					/* Get the file */
					$classToLoad = IPSLib::loadLibrary( $_file, $app['app_directory'] . '_forumDataSync', $app['app_directory'] );
					
					/* Check for the class */
					if( class_exists( $classToLoad ) )
					{
						/* Create an object */
						$_obj = new $classToLoad( $this->registry );
	
						/* Check for the module */
						if( method_exists( $_obj, $func ) )
						{
							/* Call it */
							call_user_func_array( array( $_obj, $func ), $params );
						}
					}
				}
			}
		}
	}
}