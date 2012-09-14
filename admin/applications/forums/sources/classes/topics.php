<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Moderator actions
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
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class app_forums_classes_topics
{
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

	protected $topicData       = array();
	protected $_memberData     = array();
	protected $moderatorData   = null;
	protected $permissionsData = array();
	protected $errorMessage	   = array();
	protected $_parsedMembers  = array();
	private   $_topicCache     = array();
	private   $_countPosts	   = 0;
	private   $_countTopics	   = 0;
	private   $_archiveReader  = null;
	
	/**
	 * Return errors, instead of printing
	 *
	 * @var	bool
	 */
 	public $return	= true;
	
 	/* Switch fields */
	protected $fields = array('pid'           	 => 'archive_id',
							  'author_id'    	 => 'archive_author_id',
							  'author_name'  	 => 'archive_author_name',
						      'ip_address'   	 => 'archive_ip_address',
							  'post_date' 	     => 'archive_content_date',
							  'post'		 	 => 'archive_content',
							  'queued'	 	     => 'archive_queued',
							  'topic_id'     	 => 'archive_topic_id',
							  'new_topic'     	 => 'archive_is_first',
							  'post_bwoptions'   => 'archive_bwoptions',
							  'post_key'   	 	 => 'archive_attach_key',
							  'post_htmlstate'   => 'archive_html_mode',
							  'use_sig'   		 => 'archive_show_signature',
							  'use_emo'   		 => 'archive_show_emoticons',
							  'append_edit'   	 => 'archive_show_edited_by',
							  'edit_time'   	 => 'archive_edit_time',
							  'edit_name'   	 => 'archive_edit_name',
							  'post_edit_reason' => 'archive_edit_reason',
							  '_prefix_'		 => 'archive_',
							  '_table_'			 => 'forums_archive_posts' );
	
	/**
	 * Constructor
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
		
		/* Set default member */
		$this->setMemberData( $this->memberData );
		
		/* Check for class_forums */
		if ( ! $this->registry->isClassLoaded( 'class_forums' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
			$this->registry->strip_invisible = 0;
			$this->registry->class_forums->forumsInit();
		}
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('tags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' ) );
		}
			
		/* Unpack reputation @todo this isn't ideal here but it'll do for now */
		if ( $this->settings['reputation_enabled'] )
		{
			/* Load the class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			$this->registry->setClass( 'repCache', new $classToLoad() );
		
			/* Update the filter? */
			if( isset( $this->request['rep_filter'] ) AND $this->request['rep_filter'] == 'update' )
			{
				/**
				 * Check secure key
				 * @link	http://community.invisionpower.com/tracker/issue-22078-i-can-edit-your-treshold
				 */
				if( $this->request['secure_key'] != $this->member->form_hash )
				{
					$this->registry->output->showError( 'usercp_forums_bad_key', 1021522 );
				}
				
				$_mem_cache = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
				
				if( $this->request['rep_filter_set'] == '*' )
				{
					$_mem_cache['rep_filter'] = '*';
				}
				else
				{
					$_mem_cache['rep_filter'] = intval( $this->request['rep_filter_set'] );
				}
				
				IPSMember::packMemberCache( $this->memberData['member_id'], $_mem_cache );
				
				$this->memberData['_members_cache'] = $_mem_cache;
			}
			else
			{
				$this->memberData['_members_cache'] = isset($this->memberData['members_cache']) ? IPSMember::unpackMemberCache( $this->memberData['members_cache'] ) : array();
			}
		}
	}

	/**
	 * Auto populate the data and that. Populated topicData and forumData. Also does rudimentary access checks
	 *
	 * @param	mixed	$topic	Array of topic data, or single topic id
	 * @param	bool	$return	Return errors instead of printing
	 * @return	@e void
	 */
	public function autoPopulate( $topic="", $return=true )
	{
		/* @todo Remove other calls to request['t'] - intvalled here because it's called in a million places */
		$this->request['t'] = intval( $this->request['t'] );
		
		$this->return	= $return;
		
		/* Sanitize */
		$topicId = intval( $this->request['t'] );
			
		if ( ! is_array( $topic ) )
		{
			if ( ! $topicId )
			{
				throw new Exception( 'EX_topics_no_tid' );
			}
			
			/* May have loaded topic data previously */
			if ( empty( $this->registry->class_forums->topic_cache['tid'] ) )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topicId ) );									
				$this->DB->execute();
				
				$this->setTopicData( $this->DB->fetch() );
			}
			else
			{
				$this->setTopicData( $this->registry->class_forums->topic_cache );
			}
		}
		else
		{
			$this->setTopicData( $topic );
		}
		
		$this->topicData['title'] = IPSText::stripAttachTag( $this->topicData['title'] );
		
		/* @todo Remove other calls to request['f'] - intvalled here because it's called in a million places */
		$this->request['f'] = intval( $this->topicData['forum_id'] );
		
		/* Check to see if stuff is stuffable */
		$result = $this->canView();

		if ( $result === false )
		{
			throw new Exception( $this->getErrorMessage() );
		}
		
		if ( ! empty( $this->topicData['tag_cache_key'] ) )
		{
			$this->topicData['tags'] = $this->registry->tags->formatCacheJoinData( $this->topicData );
		}
		
		/* Set up */
		$this->topicData = $this->setUpTopic( $this->topicData );	
	}
	
	/**
	 * Sets up a topic
	 * @param  array $topic If not passed then $this->topicData is used
	 * @return array
	 */
	public function setUpTopic( $topic=array() )
	{
		$topic = ( count( $topic ) ) ? $topic : $this->topicData;
		
		/* Error out if the topic is not approved or soft deleted */
		$approved = $this->registry->class_forums->fetchHiddenTopicType( $topic );
		
		/* Deleted topic? */
		$topic['_isDeleted']  = ( $approved == 'sdelete' || $approved == 'pdelete' ) ? true : false;
		$topic['_isHidden']   = ( $approved == 'hidden' )  ? true : false;
		$topic['_isArchived'] = $this->isArchived( $topic );
		
		/* Posts per day restrictions? */
		$topic['_ppd_ok'] = $this->registry->getClass('class_forums')->checkGroupPostPerDay( $this->memberData, TRUE );
		
		/* Got any unread posts? */
		$topic['hasUnreadPosts'] = ( $this->registry->classItemMarking->isRead( array( 'forumID' => $topic['forum_id'], 'itemID' => $topic['tid'], 'itemLastUpdate' => $topic['last_post'] ), 'forums' ) ) ? false : true;
	
		return $topic;
	}
	
	/**
	 * Update a topic
	 * @param int $tid
	 * @param array $data
	 */
	public function updateTopic( $tid, $data )
	{
		if ( is_numeric( $tid ) && is_array( $data ) )
		{
			$this->DB->update( 'topics', $data, 'tid=' . intval( $tid ) );
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
		/* Topic ID */
		$tid       = intval( $tid );
		$topicData = $this->getTopicById( $tid );
		
		if ( $this->settings['post_order_column'] != 'post_date' )
		{
			$this->settings['post_order_column'] = 'pid';
		}
		
		if ( $this->settings['post_order_sort'] != 'desc' )
		{
			$this->settings['post_order_sort'] = 'asc';
		}
		
		/* Is this archived? */
		if ( $this->isArchived( $topicData ) )
		{
			/* Load up archive class */
			if ( ! is_object( $this->_archiveReader ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/reader.php', 'classes_archive_reader' );
				$this->_archiveReader = new $classToLoad();
			
				$this->_archiveReader->setApp('forums');
			}
			
			/* Fetch replies */
			$pcount  = $this->_archiveReader->getPostCount( $tid, array( 'visible' ) ) - 1;
			
			/* Fetch queued */
			$qpcount = $this->_archiveReader->getPostCount( $tid, array( 'hidden' ) );
			
			/* Fetch soft deleted */
			$dpcount = $this->_archiveReader->getPostCount( $tid, array( 'sdelete' ) );
			
			/* Get last post info */
			$_post = $this->getPosts( array( 'onlyVisible'     => true,
										     'sortField'	   => $this->settings['post_order_column'],
										     'sortOrder'	   => ( $this->settings['post_order_sort'] == 'asc' ) ? 'desc' : 'asc',
										     'topicId'		   => array( $tid ),
										     'limit'		   => 1,
										     'archiveToNative' => true,
										     'isArchivedTopic' => true ) );
			
			$last_post = array_pop( $_post );

			/* Get first post info */
			$_post = $this->getPosts( array( 'onlyVisible'    => true,
										     'sortField'	   => $this->settings['post_order_column'],
										     'sortOrder'	   => $this->settings['post_order_sort'],
										     'topicId'		   => array( $tid ),
										     'limit'		   => 1,
										     'archiveToNative' => true,
										     'isArchivedTopic' => true ) );
			
			$first_post = array_pop( $_post );

			$members     = IPSMember::load( array( intval( $last_post['archive_author_id'] ), intval( $first_post['archive_author_id'] ) ) );
			
			/* Merge in some data */
			$last_post['member_id']            = $members[ $last_post['archive_author_id'] ]['member_id'];
			$last_post['members_display_name'] = $members[ $last_post['archive_author_id'] ]['members_display_name'];
			$last_post['forum_id']			   = $topicData['forum_id'];
			$last_post['title']			       = $topicData['title'];
			
			$first_post['member_id']            = $members[ $first_post['archive_author_id'] ]['member_id'];
			$first_post['members_display_name'] = $members[ $first_post['archive_author_id'] ]['members_display_name'];
			$first_post['has_poll_id']			= ( $topicData['poll_start_date'] ) ? 1 : 0;

			$last_poster_name = $last_post['members_display_name'] ? $last_post['members_display_name'] : $last_post['archive_author_name'];
			$first_poster_name = $first_post['members_display_name'] ? $first_post['members_display_name'] : $first_post['author_name'];
			$_last_poster_name = $last_poster_name ? $last_poster_name : ( $pcount > 0 ? $this->lang->words['global_guestname'] : $first_poster_name );
		}
		else
		{
			/* Fetch replies */
			$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' );
			$posts		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$tid} and {$_queued}" ) );
			$pcount		= intval( $posts['posts'] - 1 );
			
			/* Fetch queued */
			$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'hidden' ), '' );
			$qposts		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$tid} and {$_queued}" ) );
			$qpcount	= intval( $qposts['posts'] );
			
			/* Fetch soft deleted */
			$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'sdelete' ), '' );
			$dposts		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$tid} and {$_queued}" ) );
			$dpcount	= intval( $dposts['posts'] );
			
			/* Get last post info */
			$_post = $this->getPosts( array( 'onlyVisible'     => true,
										     'sortField'	   => $this->settings['post_order_column'],
										     'sortOrder'	   => ( $this->settings['post_order_sort'] == 'asc' ) ? 'desc' : 'asc',
										     'topicId'		   => array( $tid ),
										     'limit'		   => 1 ) );
			
			$last_post = array_pop( $_post );

			/* Get first post info */
			$_post = $this->getPosts( array( 'onlyVisible'     => true,
										     'sortField'	   => $this->settings['post_order_column'],
										     'sortOrder'	   => $this->settings['post_order_sort'],
										     'topicId'		   => array( $tid ),
										     'limit'		   => 1 ) );
			
			$first_post = array_pop( $_post );
	
			$first_poster_name = $first_post['members_display_name'] ? $first_post['members_display_name'] : $first_post['author_name'];
			$_last_poster_name = $last_poster_name ? $last_poster_name : ( $pcount > 0 ? $this->lang->words['global_guestname'] : $first_poster_name );
		}
		
		/* Attachment count */
		$attach = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'attachments', 'where' => "attach_parent_id={$tid} AND attach_rel_module='post'" ) );

		/* Update topic */
		$this->DB->setDataType( array( 'starter_name', 'last_poster_name' ), 'string' );

		$this->DB->update( 'topics', array( 'last_post'			  => intval($last_post['post_date'] ? $last_post['post_date'] : $first_post['post_date']),
											'last_poster_id'	  => intval($last_post['author_id'] ? $last_post['author_id'] : ( $pcount > 0 ? 0 : $first_post['author_id'] )),
											'last_poster_name'	  => $_last_poster_name,
											'poll_state'          => empty($first_post['has_poll_id']) ? 0 : 1,
											'topic_queuedposts'	  => intval($qpcount),
											'topic_deleted_posts' => intval($dpcount), 
											'posts'				  => intval($pcount),
											'starter_id'		  => intval($first_post['author_id']),
											'starter_name'		  => $first_poster_name,
											'seo_first_name'      => IPSText::makeSeoTitle( $first_poster_name ),
											'seo_last_name'       => IPSText::makeSeoTitle( $_last_poster_name ),
											'start_date'		  => intval($first_post['post_date']),
											'topic_firstpost'	  => intval($first_post['pid']),
											'topic_hasattach'	  => intval($attach['count']),
											'title_seo'			  => IPSText::makeSeoTitle( $last_post['title'] ) ), 'tid=' . $tid );

		/* Update first post */
		if ( empty( $first_post['new_topic'] ) and $first_post['pid'] )
		{
			$this->DB->update( 'posts', array( 'new_topic' => 0 ), 'topic_id=' . $tid, true );
			$this->DB->update( 'posts', array( 'new_topic' => 1 ), 'pid=' . $first_post['pid'], true );
		}
		
		/* Update forum */
		if ( ( $this->registry->class_forums->allForums[ $last_post['forum_id'] ]['last_id'] == $tid ) )
		{
			$tt = $this->DB->buildAndFetch( array(	
													'select'	=> 'title, tid, last_post, last_poster_id, last_poster_name',
													'from'		=> 'topics',
													'where'		=> 'forum_id=' . $last_post['forum_id'] . ' and ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ),
													'order'		=> 'last_post desc',
													'limit'		=> array( 1 ) )	);
			
			$dbs = array( 'last_title'			=> $tt['title']				? $tt['title']				: "",
						  'last_id'				=> $tt['tid']				? $tt['tid']				: 0,
						  'last_post'			=> $tt['last_post']			? $tt['last_post']			: 0,
						  'last_poster_name'	=> $tt['last_poster_name']	? $tt['last_poster_name']	: "",
						  'last_poster_id'		=> $tt['last_poster_id']	? $tt['last_poster_id']		: 0,
						  'seo_last_title'   	=> IPSText::makeSeoTitle( $tt['title'] ),
						  'seo_last_name'    	=> IPSText::makeSeoTitle( $tt['last_poster_name'] ) );
			
			if ( $this->registry->class_forums->allForums[ $this->forum['id'] ]['newest_id'] == $tid )
			{
				$sort_key = $this->registry->class_forums->allForums[ $this->forum['id'] ]['sort_key'];
				
				$tt = $this->DB->buildAndFetch( array(  'select' => 'title, tid',
														'from'	 => 'topics',
														'where'	 => 'forum_id=' . $this->forum['id'] . ' and ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ),
														'order'	 => 'start_date desc',
														'limit'	 => array( 1 ) ) );
															
				$dbs['newest_id']		= $tt['tid']	? $tt['tid']	: 0;
				$dbs['newest_title']	= $tt['title']	? $tt['title']	: "";
				$dbs['seo_last_title']  = IPSText::makeSeoTitle( $tt['title'] );
			}
			
			$this->DB->setDataType( array( 'last_poster_name', 'seo_last_title', 'seo_last_name', 'last_title', 'newest_title' ), 'string' );

			$this->DB->update( 'forums', $dbs, "id=" . intval($this->forum['id']) );
		}

		return true;
	}
	
	/**
	 * Load and return a topic by ID.
	 * @param   intval  $tid
	 * @return	array	Topic Data
	 */
	public function getTopicById( $tid )
	{
		/* No topic ID? Whut?! */
		if ( empty($tid) )
		{
			return array();
		}
		
		if ( empty( $this->_topicCache[ $tid ] ) OR ! is_array( $this->_topicCache[ $tid ] ) )
		{
			$this->_topicCache[ $tid ] = $this->DB->buildAndFetch( array( 'select'   => 't.*', 
																		  'from'     => array( 'topics' => 't' ), 
																		  'where'    => "t.tid=" . intval( $tid ),
																		  'add_join' => array( array( 'select'  => 'p.pid, p.start_date as poll_start_date, p.choices, p.starter_id as poll_starter_id, p.votes, p.poll_question, p.poll_only, p.poll_view_voters',
																		  							  'type'	=> 'left',
																									  'from'	=> array( 'polls' => 'p' ),
																									  'where'	=> 'p.tid=t.tid' ) ) ) );
		}

		return $this->_topicCache[ $tid ];
	}
	
	/**
	 * Load and return a post by ID.
	 * @param   intval  $pid
	 * @param	boolean	Check for archived topic
	 * @return	array	Post Data
	 */
	public function getPostById( $pid, $checkArchived=false )
	{
		$post = array();
		
		if ( ! empty($pid) )
		{
			$posts = $this->getPosts( array( 'postId'          => array( $pid ),
											 'checkArchived'   => $checkArchived ) );
											 
			$post = $posts[ $pid ];
			
		}
		
		return $post;
	}
	
	/**
	 * getTopics
	 * Fetches topics based on different critera
	 * @param	array	Filters (see below for specifics)
	 * @return	array 
	 * 
	 * FILTERS:
	 * forumId			Get topics matching the (array) forum ids, (int) forum ID
	 * topicId			Get topics matching the (array) topic ids, (int) topic ID
	 * memberData		Set memberData (this->memberData is used otherwise)
	 * onlyViewable		Set whether this member can view them or not. (default is true ) NOTE: Will not check to see if parent topic is viewable!
	 * onlyVisible 		Set whether to skip unapproved posts where permission allows (default is true)
	 * topicType		array of 'sdelete', 'visible', 'hidden', 'pdeleted' (if you specify these, permission checks are NOT performed)
	 * archiveState		Archive status 'not', 'archived', 'working', 'exclude', 'restore'
	 * sortField		Sort key (date, pid, etc)
	 * sortOrder		asc/desc
	 * skipForumCheck	Skips the forum ID IN list check to ensure you have access to view (good for when using perms elsewhere)
	 * tidIsGreater		Where TID is greater than x
	 * tidIsLess		Where TID is less than x
	 * dateIsGreater	Where DATE is greater than UNIX
	 * getFirstPost	    Return the first post of the topic
	 * parse			Parses the first post of the topic
	 * limit, offset	Limit the amount of results in the returned query
	 * getCount			fetch count without limit
	 * getCountOnly		As above, but returns the count and does not fetch the data
	 */
	public function getTopics( $filters )
	{
		/* init */
		$filters	= $this->_setTopicFilters( $filters );
		$limit	    = null;
		$topics	    = array();
		$where		= array();
		$memberData	= ( ! empty( $filters['memberData'] ) && is_array( $filters['memberData'] ) ) ? $filters['memberData'] : $this->memberData;
		
		/* Forum Ids */
		if ( ! empty( $filters['forumId'] ) )
		{
			$filters['forumId'] = ( ! is_array( $filters['forumId'] ) ) ? array( $filters['forumId'] ) : $filters['forumId'];
			$where[] = "t.forum_id IN (" . implode( ',', $filters['forumId'] ) . ")";
		}
		
		/* Topic Ids */
		if ( ! empty( $filters['topicId'] ) )
		{
			$filters['topicId'] = ( ! is_array( $filters['topicId'] ) ) ? array( $filters['topicId'] ) : $filters['topicId'];
			$where[] = "t.tid IN (" . implode( ',', $filters['topicId'] ) . ")";
		}
		
		/* Archive state */
		if ( ! empty( $filters['archiveState'] ) && is_array( $filters['archiveState'] ) )
		{
			$where[] = $this->registry->class_forums->fetchTopicArchiveQuery( $filters['archiveState'], 't.' );
		}
		
		/* TID is greater */
		if ( ! empty( $filters['tidIsGreater'] ) )
		{
			$where[] = "t.tid > " . intval( $filters['tidIsGreater'] );
		}
		
		/* TID is less */
		if ( ! empty( $filters['tidIsLess'] ) )
		{
			$where[] = "t.tid < " . intval( $filters['tidIsLess'] );
		}
		
		/* TID is greater */
		if ( ! empty( $filters['dateIsGreater'] ) )
		{
			$where[] = "t.start_date > " . intval( $filters['dateIsGreater'] );
		}
		
		/* Visible / specific filters */
		if ( ! empty( $filters['topicType'] ) && is_array( $filters['topicType'] ) )
		{
			$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( $filters['topicType'], 't.' );
		}
		else
		{
			if ( isset( $filters['onlyViewable'] ) && $filters['onlyViewable'] === true && empty( $filters['onlyVisible'] ) )
			{
				$_perms = array( 'visible' );
				
				if ( $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( false ) )
				{
					$_perms[] = 'sdelete';
				}
				
				if ( $this->registry->getClass('class_forums')->canQueuePosts( false ) )
				{
					$_perms[] = 'hidden';
				}
				
				$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( $_perms, 't.' );
			}
			else
			{
				/* Show visible only */
				$where[] = $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible' ), 't.' );
			}
		}
		
		/* Forum ID check? */
		if ( ( isset( $filters['onlyViewable'] ) && $filters['onlyViewable'] === true ) && ( empty($filters['skipForumCheck']) OR $filters['skipForumCheck'] === false ) )
		{
			if ( empty( $filters['forumId'] ) )
			{
				$forumIds = $this->registry->class_forums->fetchSearchableForumIds( $memberData['member_id'] );
				
				if ( ! count( $forumIds ) )
				{
					return $where;
				}
				
				$where[] = "t.forum_id IN (" . implode( ",", $forumIds ) . ")";
			}
		}
		
		/* Did we want a count also? */
		if ( ! empty( $filters['getCount'] ) || ! empty( $filters['getCountOnly'] ) )
		{
			$count	= $this->DB->buildAndFetch( array(	'select'	=> 'COUNT(*) as topics', 
														'from'		=> array( 'topics' => 't' ), 
														'where'		=> implode( ' AND ', $where ) ) );

			$this->_countTopics = $count['topics'];
		}
		
		/* Just return? */
		if ( ! empty( $filters['getCountOnly'] ) )
		{
			return $this->_countTopics;
		}
		
		/* Offset, limit */
		if ( isset( $filters['offset'] ) OR isset( $filters['limit'] ) )
		{
			if ( $filters['offset'] > 0 || $filters['limit'] > 0 )
			{
				$limit = array( intval( $filters['offset'] ), intval( $filters['limit'] ) );
			}
		}
		
		/* Order */
		if ( ! empty( $filters['sortField'] ) )
		{
			$order = 't.' . $filters['sortField'];
			
			if ( isset( $filters['sortOrder'] ) )
			{
				$order .= ' ' . $filters['sortOrder'];
			}
		}
		
		/* Set joins */
		$joins = array( array( 'select'  	=> 'm.*',
						       'type'		=> 'left',
						       'from'		=> array( 'members' => 'm' ),
						       'where'	=> 'm.member_id=t.starter_id' ),
				        array( 'select'  	=> 'pp.*',
						       'type'		=> 'left',
						       'from'		=> array( 'profile_portal' => 'pp' ),
						       'where'	=> 'pp.pp_member_id=t.starter_id' ) );
				        
		/* Fetching first post ? */
		if ( ! empty( $filters['getFirstPost'] ) )
		{
			$joins[] = array( 'select' => 'p.*',
							  'from'   => array( 'posts' => 'p' ),
							  'where'  => 'p.pid=t.topic_firstpost',
							  'type'  => 'left' );
		}
		
		/* Fetch them */
		$this->DB->build( array( 'select'   => 't.*, t.title as real_title, t.posts as real_posts, t.last_post as topic_last_post', 
							     'from'     => array( 'topics' => 't' ), 
							     'where'    => implode( ' AND ', $where ),
								 'limit'	=> $limit ? $limit : '',
								 'order'	=> $order ? $order : 't.tid asc',
							     'add_join' => $joins ) );

		$o = $this->DB->execute();
		
		while( $topic = $this->DB->fetch( $o ) )
		{
			if ( $topic['starter_id'] && $topic['member_group_id'] )
			{
				$group = $this->caches['group_cache'][ $topic['member_group_id'] ];
				
				if ( is_array( $group ) && count( $group ) )
				{
					$topic = array_merge( $group, $topic );
				}
			}
			
			/* Get the first post? */
			if ( ! empty( $filters['getFirstPost'] ) )
			{
				if ( ! empty( $filters['parse'] ) )
				{
					$this->setTopicData( $topic );
					$this->setPermissionData();
					$topic['firstPostParsed'] = $this->parsePost( $topic );	
				}
				
				$topic['_postType']     = $this->registry->class_forums->fetchHiddenType( $topic );
			}
			
			/* Member title overwrites topic */
			$topic['title']     	= $topic['real_title'];
			$topic['posts']			= $topic['real_posts'];
			$topic['_topicType']    = $this->registry->class_forums->fetchHiddenTopicType( $topic );
			
			$topics[ $topic['tid'] ] = $topic;
		}
		
		return $topics;
	}
	
	/**
	 * getTopicsCount
	 * Fetches number of topics based on different critera (useful for pagination when combined with getPosts())
	 * @return	int 
	 */
	public function getTopicsCount()
	{
		return $this->_countTopics;
	}
	
	/**
	 * getPosts
	 * Fetches posts based on different critera
	 * @param	array	Filters (see below for specifics)
	 * @return	array 
	 * 
	 * FILTERS:
	 * topicId			Get posts matching the (array) topic ids, (int) topic ID
	 * forumId			Get posts matching the (array) forum ids, (int) forum ID
	 * notForumId		Get posts NOT matching the (array) forum ids, (int) forum ID
	 * postId			Get posts matching the (array) post ids, (int) post id
	 * authorId			Get posts matching the (array) author ids, (int) author id
	 * memberData		Set memberData (this->memberData is used otherwise)
	 * onlyViewable		Set whether this member can view them or not. (default is true ) NOTE: Will not check to see if parent topic is viewable!
	 * onlyVisible 		Set whether to skip unapproved posts where permission allows (default is true)
	 * postType			array of 'sdelete', 'visible', 'hidden', 'pdeleted' (if you specify these, permission checks are NOT performed)
	 * sortField		Sort key (date, pid, etc)
	 * sortOrder		asc/desc
	 * pidIsGreater		Where PID is greater than x
	 * dateIsGreater	Where DATE is greater than UNIX
	 * skipForumCheck	Skips the forum ID IN list check to ensure you have access to view (good for when using perms elsewhere)
	 * parse			Parses post content
	 * limit, offset	Limit the amount of results in the returned query
	 * getCount			fetch count without limit
	 * getCountOnly	    fetch count and return only
	 * checkArchived	Check archive tables if no matches
	 * 
	 */
	public function getPosts( $filters )
	{
		/* init */
		$filters	= $this->_setPostFilters( $filters );
		$limit	    = null;
		$posts	    = array();
		$where		= array();
		$memberData	= ( ! empty( $filters['memberData'] ) && is_array( $filters['memberData'] ) ) ? $filters['memberData'] : $this->memberData;
		
		$isArchived    = ( ! empty( $filters['isArchivedTopic'] ) ) ? true : false;
		$checkArchived = ( ! empty( $filters['checkArchived'] ) ) ? true : false;
		
		if ( $isArchived && ( $this->settings['archive_remote_sql_database'] && $this->settings['archive_remote_sql_user'] ) )
		{
			/* Load up archive class */
			if ( ! is_object( $this->_archiveReader ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/reader.php', 'classes_archive_reader' );
				$this->_archiveReader = new $classToLoad();
					
				$this->_archiveReader->setApp('forums');
			}
				
			$posts = $this->_archiveReader->getPosts( $filters );
				
			/* Fetch topics */
			$tids = array();
			$mids = array();
				
			foreach( $posts as $id => $post )
			{
				$tids[ $post['topic_id'] ] = $post['topic_id'];
		
				if ( $post['author_id'] )
				{
					$mids[ $post['author_id'] ] = $post['author_id'];
				}
			}
				
			$topics  = $this->getTopics( array( 'topicId' => $tids ) );
			$members = IPSMember::load( $mids );
			
			foreach( $posts as $id => $post )
			{
				$posts[ $id ] = array_merge( $topics[ $post['topic_id'] ], $post );
				
				if ( $post['author_id'] )
				{
					$_mem = $members[ $post['author_id'] ];
					unset( $_mem['title'] );
						
					$posts[ $id ] = array_merge( $_mem, $post );
				}
		
				if ( ! empty( $filters['parse'] ) )
				{
					$this->setTopicData( $post );
					$this->setPermissionData();
					$posts[ $id ] = $this->parsePost( $posts[ $id ] );
				}
				
				$posts[ $id ]['tid']        = $post['topic_id'];
				$posts[ $id ]['_postType']  = $this->registry->class_forums->fetchHiddenType( $post );
				$posts[ $id ]['_topicType'] = $this->registry->class_forums->fetchHiddenTopicType( $post );
			}
				
			return $posts;
		}
		else
		{
			/* Posts */
			if ( ! empty( $filters['postId'] ) )
			{
				$filters['postId'] = ( ! is_array( $filters['postId'] ) ) ? array( $filters['postId'] ) : $filters['postId'];
				$where[] = "p." . $this->getPostTableField( 'pid', $isArchived ) . " IN (" . implode( ',',$filters['postId'] ) . ")";
			}
			
			/* Topics */
			if ( ! empty( $filters['topicId'] ) )
			{
				$filters['topicId'] = ( ! is_array( $filters['topicId'] ) ) ? array( $filters['topicId'] ) : $filters['topicId'];
				$where[] = "p.". $this->getPostTableField( 'topic_id', $isArchived ) . " IN (" . implode( ',',$filters['topicId'] ) . ")";
			}
			
			/* Forum Ids */
			if ( ! empty( $filters['forumId'] ) )
			{
				$filters['forumId'] = ( ! is_array( $filters['forumId'] ) ) ? array( $filters['forumId'] ) : $filters['forumId'];
				$where[] = "t.forum_id IN (" . implode( ',', $filters['forumId'] ) . ")";
			}
			
			/* Author Ids */
			if ( ! empty( $filters['authorId'] ) )
			{
				$filters['authorId'] = ( ! is_array( $filters['authorId'] ) ) ? array( $filters['authorId'] ) : $filters['authorId'];
				$where[] = "p." . $this->getPostTableField( 'author_id', $isArchived ) . " IN (" . implode( ',',$filters['authorId'] ) . ")";
			}
			
			/* Not Forum Ids */
			if ( ! empty( $filters['notForumId'] ) )
			{
				$filters['notForumId'] = ( ! is_array( $filters['notForumId'] ) ) ? array( $filters['notForumId'] ) : $filters['notForumId'];
				$where[] = "t.forum_id NOT IN (" . implode( ',', $filters['notForumId'] ) . ")";
			}
			
			/* PID is greater */
			if ( ! empty( $filters['pidIsGreater'] ) )
			{
				$where[] = "p.". $this->getPostTableField( 'pid', $isArchived ) . " > " . intval( $filters['pidIsGreater'] );
			}
			
			/* PID is less */
			if ( ! empty( $filters['pidIsLess'] ) )
			{
				$where[] = "p.". $this->getPostTableField( 'pid', $isArchived ) . " < " . intval( $filters['pidIsLess'] );
			}
			
			/* Date is greater */
			if ( ! empty( $filters['dateIsGreater'] ) )
			{
				$where[] = "p.". $this->getPostTableField( 'post_date', $isArchived ) . " > " . intval( $filters['dateIsGreater'] );
			}
			
			/* Visible / specific filters */
			if ( ! empty( $filters['postType'] ) && is_array( $filters['postType'] ) )
			{
				$where[] = $this->registry->class_forums->fetchPostHiddenQuery( $filters['postType'], 'p.' . $this->getPostTableField( '_prefix_', $isArchived ) );
			}
			else
			{
				if ( isset( $filters['onlyViewable'] ) && $filters['onlyViewable'] === true && empty( $filters['onlyVisible'] ) )
				{
					$_perms = array( 'visible' );
					
					if ( $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( false ) )
					{
						$_perms[] = 'sdelete';
					}
					
					if ( $this->registry->getClass('class_forums')->canQueuePosts( false ) )
					{
						$_perms[] = 'hidden';
					}
					
					$where[] = $this->registry->class_forums->fetchPostHiddenQuery( $_perms, 'p.' . $this->getPostTableField( '_prefix_', $isArchived ) );
				}
				else
				{
					/* Show visible only */
					$where[] = $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), 'p.' . $this->getPostTableField( '_prefix_', $isArchived ) );
				}
			}
			
			/* Forum ID check? */
			if ( ( isset( $filters['onlyViewable'] ) && $filters['onlyViewable'] === true ) && ( empty($filters['skipForumCheck']) OR $filters['skipForumCheck'] === false ) )
			{
				if ( empty( $filters['forumId'] ) && empty( $filters['notForumId'] ) )
				{
					$forumIds = $this->registry->class_forums->fetchSearchableForumIds( $memberData['member_id'] );
					
					if ( ! count( $forumIds ) )
					{
						return $where;
					}
					
					$where[] = "t.forum_id IN (" . implode( ",", $forumIds ) . ")";
				}
			}
			
			/* Did we want a count also? */
			if ( ! empty( $filters['getCount'] ) || ! empty( $filters['getCountOnly'] ) )
			{
				$count	= $this->DB->buildAndFetch( array(	'select'	=> 'COUNT(*) as posts', 
															'from'		=> array( $this->getPostTableField( '_table_', $isArchived ) => 'p' ), 
															'where'		=> implode( ' AND ', $where ),
															'add_join'	=> array( array( 'type'		=> 'left',
																						 'from'		=> array( 'topics' => 't' ),
																					     'where'	=> 'p.' . $this->getPostTableField( 'topic_id', $isArchived ) . '=t.tid' ) ) ) );
	
				$this->_countPosts = $count['posts'];
				
				if ( $filters['getCountOnly'] )
				{
					return $this->_countPosts;
				}
			}
			
			/* Offset, limit */
			if ( isset( $filters['offset'] ) OR isset( $filters['limit'] ) )
			{
				if ( $filters['offset'] > 0 || $filters['limit'] > 0 )
				{
					$limit = array( intval( $filters['offset'] ), intval( $filters['limit'] ) );
				}
			}
			
			/* Order */
			if ( ! empty( $filters['sortField'] ) )
			{
				if ( strstr( $filters['sortField'], '.' ) )
				{
					$order = $filters['sortField'];
				}
				else
				{
					$order = 'p.' . $this->getPostTableField( $filters['sortField'], $isArchived );
				}
				
				if ( isset( $filters['sortOrder'] ) )
				{
					$order .= ' ' . $this->getPostTableField( $filters['sortOrder'], $isArchived );
				}
			}
			
			$_joins	= array( array(  'select'  	=> 't.*, t.title as real_title',
		  						     'type'		=> 'left',
								     'from'		=> array( 'topics' => 't' ),
								     'where'	=> 'p.' . $this->getPostTableField( 'topic_id', $isArchived ) . '=t.tid' ),
						      array( 'select'  	=> 'm.*, m.title as member_title',
								     'type'		=> 'left',
								     'from'		=> array( 'members' => 'm' ),
								     'where'	=> 'm.member_id=p.' . $this->getPostTableField( 'author_id', $isArchived ) ),
						      array( 'select'  	=> 'pp.*',
								     'type'		=> 'left',
								     'from'		=> array( 'profile_portal' => 'pp' ),
								     'where'	=> 'pp.pp_member_id=p.' . $this->getPostTableField( 'author_id', $isArchived ) ) );
	
			/* Add custom fields join? */
			if ( $this->settings['custom_profile_topic'] == 1 )
			{
				$_joins[] = array( 
										'select' => 'pc.*',
										'from'   => array( 'pfields_content' => 'pc' ),
										'where'  => 'pc.member_id=p.' . $this->getPostTableField( 'author_id', $isArchived ),
										'type'   => 'left'
									);
			}
	
			/* Fetch them */
			$this->DB->build( array( 'select'   => 'p.*, p.' . $this->getPostTableField( 'ip_address', $isArchived ) . ' as post_ip',
								     'from'     => array( $this->getPostTableField( '_table_', $isArchived ) => 'p' ), 
								     'where'    => implode( ' AND ', $where ),
									 'limit'	=> $limit ? $limit : '',
									 'order'	=> $order ? $order : 'p.' . $this->getPostTableField( 'pid', $isArchived ) . ' asc',
								     'add_join' => $_joins ) );
			
			$o = $this->DB->execute();
			
			while( $post = $this->DB->fetch( $o ) )
			{
				if ( $post[ $this->getPostTableField('author_id', $isArchived ) ] && $post['member_group_id'] )
				{
					$group = $this->caches['group_cache'][ $post['member_group_id'] ];
					
					if ( is_array( $group ) && count( $group ) )
					{
						$post = array_merge( $group, $post );
					}
				}
				
				/* Post IP overrides member */
				$post['ip_address'] = $post['post_ip'];
				
				/* Member title overwrites topic */
				$post['title']	= $post['topic_title'] = $post['real_title'];
				$pid			= $post[ $this->getPostTableField('pid', $isArchived ) ];
				
				if ( $isArchived AND $filters['archiveToNative'] )
				{
					$post = $this->archivePostToNativeFields( $post );
				}
				
				if ( ! empty( $filters['parse'] ) )
				{
					$this->setTopicData( $post );
					$this->setPermissionData();
					$post = $this->parsePost( $post );	
				}
				
				$post['_postType']     = $this->registry->class_forums->fetchHiddenType( $post );
				$post['_topicType']    = $this->registry->class_forums->fetchHiddenTopicType( $post );
				
				$posts[ $pid ] = $post;
			}
			
			/* got anything? */
			if ( ! count( $posts ) && $checkArchived )
			{
				$filters['checkArchived'] = false;
				$filters['isArchived']    = true;
				
				$posts = $this->getPosts( $filters );
			}
		}
		
		return $posts;
	}
	
	/**
	 * getPostsCount
	 * Fetches number of posts based on different critera (useful for pagination when combined with getPosts())
	 * @return	int 
	 */
	public function getPostsCount()
	{
		return $this->_countPosts;
	}
	
	/**
	 * @return the $errorMessage
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	/**
	 * @param field_type $errorMessage
	 */
	public function setErrorMessage( $errorMessage )
	{
		$this->errorMessage = $errorMessage;
	}
	
	/**
	 * Sets up the permissions for this class
	 * 
	 * @param	string	key
	 * @param	string	value
	 */
	public function setPermissionData( $k='', $v='' )
	{
		$this->registry->getClass('class_forums')->setMemberData( $this->getMemberData() );
		
		if ( empty( $k ) and empty( $v ) )
		{
			/* Auto set up */
			$this->permissionsData['softDelete']             = $this->registry->getClass('class_forums')->canSoftDeletePosts( $this->topicData['forum_id'], array() );
			$this->permissionsData['softDeleteRestore']      = $this->registry->getClass('class_forums')->can_Un_SoftDeletePosts( $this->topicData['forum_id'] );
			$this->permissionsData['softDeleteSee']          = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( $this->topicData['forum_id'] );
			$this->permissionsData['softDeleteReason']       = $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( $this->topicData['forum_id'] );
			$this->permissionsData['softDeleteContent']      = $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( $this->topicData['forum_id'] );
			$this->permissionsData['TopicSoftDelete']        = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->topicData['forum_id'], $this->topicData );
			$this->permissionsData['TopicSoftDeleteRestore'] = $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $this->topicData['forum_id'] );
			$this->permissionsData['TopicSoftDeleteSee']     = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( $this->topicData['forum_id'] );
		}
		else if ( isset( $k['tid'] ) && isset( $k['forum_id'] ) )
		{
			/* Auto set up */
			$this->permissionsData['softDelete']             = $this->registry->getClass('class_forums')->canSoftDeletePosts( $k['forum_id'], array() );
			$this->permissionsData['softDeleteRestore']      = $this->registry->getClass('class_forums')->can_Un_SoftDeletePosts( $k['forum_id'] );
			$this->permissionsData['softDeleteSee']          = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( $k['forum_id'] );
			$this->permissionsData['softDeleteReason']       = $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( $k['forum_id'] );
			$this->permissionsData['softDeleteContent']      = $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( $k['forum_id'] );
			$this->permissionsData['TopicSoftDelete']        = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $k['forum_id'], $k );
			$this->permissionsData['TopicSoftDeleteRestore'] = $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $k['forum_id'] );
			$this->permissionsData['TopicSoftDeleteSee']     = $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( $k['forum_id'] );
		}
		else if ( is_array( $k ) )
		{
			$this->permissionsData = $k;
		}
		else if ( ! empty( $k )  )
		{
			$this->permissionsData[ $k ] = $v;
		}
	}
	
	/**
	 * @return the $permissionData
	 */
	public function getPermissionData( $k='' )
	{
		return ( ! empty( $k ) ) ? $this->permissionsData[ $k ] : $this->permissionsData;
	}
	
	/**
	 * @return the $topicData
	 */
	public function getTopicData( $k='' )
	{	
		return ( ! empty( $k ) ) ? $this->topicData[ $k ] : $this->topicData;
	}

	/**
	 * @param	string	key
	 * @param	string	value
	 */
	public function setTopicData( $k, $v='' )
	{
		if ( is_integer( $k ) )
		{
			$this->topicData = $this->getTopicById( $k );
		}
		else if ( is_array( $k ) )
		{
			$this->topicData = $k;
		}
		else if ( ! empty( $k ) )
		{
			$this->topicData[ $k ] = $v;
		}
	}
	
	/**
	 * @return the $_memberData
	 */
	public function getMemberData( $k='' )
	{
		return ( ! empty( $k ) ) ? $this->_memberData[ $k ] : $this->_memberData;
	}

	/**
	 * @param	string	key
	 * @param	string	value
	 */
	public function setMemberData( $k, $v='' )
	{
		if ( is_integer( $k ) )
		{
			$this->_memberData = empty( $k ) ? IPSMember::setUpGuest() : IPSMember::load( $k );
		}
		else if ( is_string($k) && $k == intval($k) )
		{
			$this->_memberData = empty( $k ) ? IPSMember::setUpGuest() : IPSMember::load( $k );
		}
		else if ( is_array( $k ) )
		{
			$this->_memberData = $k;
		}
		else if ( ! empty( $k ) )
		{
			$this->_memberData[ $k ] = $v;
		}
	}
	
	/**
	 * Loads and fetches the moderator data
	 */
	public function getModeratorData()
	{
		$forumData = $this->registry->getClass('class_forums')->getForumbyId( $this->topicData['forum_id'] );
		
		if ( $this->moderatorData === null AND $this->memberData['member_id'] AND ! $this->memberData['g_is_supmod'] )
		{
			$other_mgroups	= array();
			$_mgroup_others	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );

			if( $_mgroup_others )
			{
				$other_mgroups = explode( ",", $_mgroup_others );
			}
		
			$other_mgroups[] = $this->memberData['member_group_id'];
			
			$member_group_ids = implode( ",", $other_mgroups );

			$this->moderatorData = $this->DB->buildAndFetch( array( 'select' => '*',
																    'from'	 => 'moderators',
																    'where'	 => "forum_id LIKE '%,{$forumData['id']},%' AND (member_id={$this->memberData['member_id']} OR (is_group=1 AND group_id IN({$member_group_ids})))" )	);
		
			$this->moderatorData = ( is_array( $this->moderatorData ) ) ? $this->moderatorData : array();
		}
		
		return $this->moderatorData;
	}

	/**
	 * Fetch the next unread topicID
	 * @param array or null $topicData
	 */
	public function getNextUnreadTopicId( $topicData=false)
	{
		$topicData   = ( ! is_array( $topicData ) ) ? $this->getTopicData() : $topicData;
		$readItems   = $this->registry->classItemMarking->fetchReadIds( array( 'forumID' => $topicData['forum_id'] ) );
		$lastMarked  = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $topicData['forum_id'] ) );
		$approved    = $this->memberData['is_mod'] ? ' AND ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible', 'hidden' ), '' ) . ' ' : ' AND ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) . ' ';
		
		/* Add in this topic ID to be sure */
		$readItems[ $topicData['tid'] ] = $topicData['tid'];
		
		/* First, attempt to fetch a topic older than this one */
		$tid = $this->DB->buildAndFetch( array( 'select' => 'tid',
												'from'   => 'topics',
												'where'  => "forum_id=" . intval( $topicData['forum_id'] ) . " {$approved} AND tid NOT IN(".implode(",",array_values($readItems)).") AND last_post < " . intval($topicData['last_post']) . " AND last_post > " . $lastMarked . " AND state != 'link'",
												'order'  => 'last_post DESC',
												'limit'  => array( 0, 1 ) )	);
		
		if ( ! $tid )
		{
			$tid = $this->DB->buildAndFetch( array( 'select' => 'tid',
													'from'   => 'topics',
													'where'  => "forum_id=" . intval( $topicData['forum_id'] ) . " {$approved} AND tid NOT IN(".implode(",",array_values($readItems)).") AND last_post > " . intval($topicData['last_post']) . " AND state != 'link'",
													'order'  => 'last_post DESC',
													'limit'  => array( 0, 1 ) )	);
		}
		
		return intval( $tid['tid'] );
	}
	
	/**
	 * Determines if we're on the last page or not ...
	 * @param array or null $topicData
	 * @return	boolean
	 */
	public function isOnLastPage( $topicData=false )
	{
		$topicData = ( ! is_array( $topicData ) ) ? $this->getTopicData() : $topicData;
		$st        = intval( $this->request['st'] );
		$perPage   = $this->settings['display_max_posts'];
		$posts     = intval( $topicData['posts'] ) + 1;
		
		if ( $posts <= $perPage )
		{
			return true;
		}
		
		$maxSt = ( ceil( $posts / $perPage ) - 1 ) * $perPage;
		
		if ( $st == $maxSt )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Return whether we can see the IP address or not
	 *
	 * @return	bool
	 */
	public function canSeeIp()
	{
		$moderator = $this->getModeratorData();
		
		if ( ! $this->memberData['g_is_supmod'] && empty( $moderator['view_ip'] ) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Is this topic archived?
	 * For this purpose, either 'working' or 'archived' returns true
	 * @param array $topicData
	 * @return boolean
	 */
	public function isArchived( $topicData )
	{
		if ( ! empty( $topicData['topic_archive_status'] ) )
		{
			$result = $this->registry->class_forums->fetchArchiveTopicType( $topicData );
			
			switch( $result )
			{
				case 'not':
				case 'exclude':
					return false;
				break;
				case 'working':
				case 'restore':
				case 'archived':
					return true;
				break;
			}
		}
		
		return false;
	}
	
	/**
	 * Can view or cannot view
	 * @param	mixed	Nothing or inline topicData
	 * @param   mixed	Nothing or inline memberData
	 */
	public function canView( $topicData=false, $memberData=false )
	{
		if ( is_array( $topicData ) && isset( $topicData['tid'] ) )
		{
			$this->setTopicData( $topicData );
		}
		
		if ( is_array( $memberData ) && isset( $memberData['member_id'] ) )
		{
			$this->setMemberData( $memberData );
		}
		
		/* get member/topic data */
		$memberData = $this->getMemberData();
		$topicData  = $this->getTopicData();
		
		/* Basic checks */
		if ( ! $topicData['tid'] )
		{
			$this->setErrorMessage( 'EX_topics_no_tid' );
			return false;
		}
		
		if ( ! $this->registry->getClass('class_forums')->getForumbyId( $topicData['forum_id'] ) )
		{
			$this->setErrorMessage( 'EX_topics_no_fid' );
			return false;
		}
		
		/* Set up member ID */
		$this->registry->class_forums->setMemberData( $memberData );
		
		/* Test */
		if ( ! $this->registry->class_forums->forumsCheckAccess( $topicData['forum_id'], 1, 'topic', $topicData, $this->return ) )
		{
			$this->setErrorMessage( 'EX_topic_not_approved' );
			return false;
		}
		
		/* Error out if the topic is not approved or soft deleted */
		$approved = $this->registry->class_forums->fetchHiddenTopicType( $topicData );
		
		if ( ! $this->registry->class_forums->canQueuePosts( $topicData['forum_id'] ) )
		{
			if ( $approved == 'hidden' )
			{
				$this->setErrorMessage( 'EX_topic_not_approved' );
				return false;
			}
		}
		
		/* Set up permissions */
		if ( ! count( $this->permissionsData ) )
		{
			$this->setPermissionData();
		}
		
		/* Deleted / Hidden? */
		if ( ( $approved == 'sdelete' or $approved == 'pdelete') AND ! $memberData['g_is_supmod'] AND ! $this->permissionsData['softDeleteSee'] )
		{
			$this->setErrorMessage( 'EX_topic_not_approved' );
			return false;
		}
		
		return true;
	}
	
	/**
	 * Return whether or not we have permission to delete the post (_getDeleteButtonData)
	 *
	 * @return	bool
	 */
	public function canDeletePost( $poster )
	{
		/* Is we archived? */
		if ( $this->isArchived( $this->getTopicData() ) )
		{
			return false;
		}
		
		$moderator = $this->getModeratorData();
		
		if ( ! $this->memberData['member_id']  )
		{
			return FALSE;
		}
		
		if ( $this->memberData['g_is_supmod'] OR $moderator['delete_post'] )
		{
			return TRUE;
		}
		
		if ( $poster['member_id'] == $this->memberData['member_id'] and ( $this->memberData['g_delete_own_posts'] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Return whether or not we can edit this post
	 *
	 * @param	array 		Array of post data
	 * @return  bool
	 */
	public function canEditPost( $poster=array() )
	{
		/* Is we archived? */
		if ( $this->isArchived( $this->getTopicData() ) )
		{
			return false;
		}
		
		$moderator	= $this->getModeratorData();
		$topicData	= $this->getTopicData();
		
		if ( $this->memberData['member_id'] == "" or $this->memberData['member_id'] == 0 )
		{
			return FALSE;
		}
				
		if ( $this->memberData['g_is_supmod'] )
		{
			return TRUE;
		}
		
		if ( $moderator['edit_post'] )
		{
			return TRUE;
		}
		
		if ( ( $topicData['state'] != 'open' ) and ( ! $this->memberData['g_is_supmod'] AND ! $moderator['edit_post'] ) )
		{
			if ( $this->memberData['g_post_closed'] != 1 )
			{
				return FALSE;
			}
		}
		
		if ( $poster['member_id'] == $this->memberData['member_id'] and ($this->memberData['g_edit_posts']) )
		{
			// Have we set a time limit?
			if ($this->memberData['g_edit_cutoff'] > 0)
			{
				if ( $poster['post_date'] > ( time() - ( intval($this->memberData['g_edit_cutoff']) * 60 ) ) )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Can make a reply to this topic
	 * @return string locked / moved / no_reply / reply 
	 */
	public function getReplyStatus()
	{
		/* Is we archived? */
		if ( $this->isArchived( $this->getTopicData() ) )
		{
			return 'archived';
		}
		
		/* Init */
		$topicData = $this->getTopicData();
		$forumData = $this->registry->getClass('class_forums')->getForumbyId( $topicData['forum_id'] );
		
		$status = '';
		
		if ($topicData['state'] == 'closed' OR ( $topicData['poll_state'] AND $topicData['poll_only'] ) )
		{
			/* Do we have the ability to post in closed topics or is this a poll only?*/
			if ( $this->memberData['g_post_closed'] == 1)
			{
				$status = 'locked';
			}
			else
			{
				$status = "locked";
			}
		}
		else
		{
			if ( $topicData['state'] == 'moved' )
			{
				$status = "moved";
			}
			else if ( $topicData['_isDeleted'] )
			{
				$status = 'no_reply';
			}
			else
			{
				if ( $forumData['min_posts_post'] && $forumData['min_posts_post'] > $this->memberData['posts'] && !$this->memberData['g_is_supmod'] )
				{
					$status = "locked";
				}
				else
				{
					if ( $this->memberData['member_id'] AND ( ( ( $this->memberData['member_id'] == $topicData['starter_id'] ) AND ! $this->memberData['g_reply_own_topics'] ) OR ( ( $this->memberData['member_id'] != $topicData['starter_id'] ) AND ! $this->memberData['g_reply_other_topics'] ) ) )
					{
						$status = "no_reply";
					}
					else if ( $this->registry->permissions->check( 'reply', $forumData ) == TRUE )
					{
						$status = "reply";
					}
					else
					{
						$status = "no_reply";
					}
				}
			}
		}
		
		return $status;
	}
	
	/**
	 * Get multimoderation data
	 *
	 * @return	array
	 */
	public function getMultiModerationData()
	{
		$return_array = array();
		$mm_array	 = $this->registry->class_forums->getMultimod( $this->topicData['forum_id'] );
		
		//-----------------------------------------
		// Print and show
		//-----------------------------------------
		
		if ( is_array( $mm_array ) and count( $mm_array ) )
		{
			foreach( $mm_array as $m )
			{
				$return_array[] = $m;
			}
		}
		
		return $return_array;
	}
	
	/**
	 * Parses and caches members
	 * @param array $member
	 */
	public function parseMember( array $member )
	{
		/* Not cached? */
		if ( ! isset( $this->_parsedMembers[ $member['author_id'] ] ) )
		{
			$member['member_id'] = !empty($member['mid']) ? $member['mid'] : $member['member_id'];
			
			/* Unset any post data */
			unset( $member['pid'], $member['append_edit'], $member['edit_time'], $member['use_sig'], $member['use_emo'],
					$member['post_edit_reason'], $member['post_date'], $member['post'], $member['queued'], $member['topic_id'],
					$member['post_htmlstate'], $member['new_topic'], $member['edit_name'], $member['post_key'], $member['title'] );
			
			/* Do we have a cached signature? */
			if ( isset( $member['cache_content_sig'] ) )
			{ 
				$member['cache_content'] = $member['cache_content_sig'];
				$member['cache_updated'] = $member['cache_updated_sig'];
			}
			else
			{
				unset( $member['cache_content'], $member['cache_updated'] );
			}
			
			/**
			 * Add group data and setup secondary groups as well
			 * @link	http://community.invisionpower.com/tracker/issue-29142-can-post-html-in-secondary-groups/
			 */
			if( !empty($this->caches['group_cache'][ $member['member_group_id'] ]) )
			{
				$member = array_merge( $member, $this->caches['group_cache'][ $member['member_group_id'] ] );
				$member = $this->member->setUpSecondaryGroups( $member );
			}

			$member = IPSMember::buildDisplayData( $member, array( 'signature' => 1, 'customFields' => 1, 'warn' => 1, 'checkFormat' => 1, 'cfLocation' => 'topic', 'photoTagSize' => array( 'thumb', 'small' ) ) );
			
			//-----------------------------------------
			// Add it to the cached list
			//-----------------------------------------
			
			$this->_parsedMembers[ $member['author_id'] ] = $member;
		}
		
		return $this->_parsedMembers[ $member['author_id'] ];
	}
	
	/**
	 * Parse the topic for forum/search results/also tagged view
	 * @param	array
	 */
	public function parseTopicForLineEntry( array $topic )
	{
		/* Set up permissions */
		if ( ! count( $this->permissionsData ) )
		{
			$this->setPermissionData();
		}
		
		$topic['real_tid']		= $topic['tid'];
		
		$topic['_isVisible']	= ( $this->registry->getClass('class_forums')->fetchHiddenTopicType( $topic ) == 'visible' ) ? true : false;
		$topic['_isHidden']		= ( $this->registry->getClass('class_forums')->fetchHiddenTopicType( $topic ) == 'hidden' ) ? true : false;
		$topic['_isDeleted']	= ( $this->registry->getClass('class_forums')->fetchHiddenTopicType( $topic ) == 'sdelete' ) ? true : false;
		$topic['_archiveFlag']	= $this->registry->class_forums->fetchArchiveTopicType( $topic );
		$topic['_isArchived']	= ( in_array( $topic['_archiveFlag'], array( 'working', 'archived', 'restore' ) ) );
		
		/* Member table and topic table overwrite */
		if ( isset( $topic['topic_title'] ) )
		{
			$topic['title'] = $topic['topic_title'];
		}
		
		if ( isset( $topic['topic_posts'] ) )
		{
			$topic['posts'] = $topic['topic_posts'];
		}
		
		if ( isset( $topic['topic_last_post'] ) )
		{
			$topic['last_post'] = $topic['topic_last_post'];
		}
		
		//-----------------------------------------
		// Rebuild SEO title on the fly, if needed
		//-----------------------------------------

		if( ! $topic['title_seo'] )
		{
			$topic['title_seo']	= IPSText::makeSeoTitle( $topic['title'] );
			
			$this->DB->update( 'topics', array( 'title_seo' => ( $topic['title_seo'] ) ? $topic['title_seo'] : '-' ), 'tid=' . $topic['tid'] );
		}

		//-----------------------------------------
		// Linky pinky!
		//-----------------------------------------
		
		/* We need original _tid in the skin template for linked topics, so we'll reassign here and just use that in the template, rather
			than add a bunch of HTML logic to show _tid for link and tid for regular topics */
		$topic['_tid']			= $topic['tid'];
		$topic['_forum_id']		= $topic['forum_id'];
		
		if ( $topic['state'] == 'link' )
		{
			$t_array				= explode("&", $topic['moved_to']);
			$topic['tid']			= $t_array[0];
			$topic['forum_id']		= $t_array[1];
			$topic['title']			= $topic['title'];
			$topic['views']			= '--';
			$topic['prefix']		= $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->lang->words['pre_moved'] );
		}

		/* Fetch last marking time for this entry */
		$lastMarked = ( $topic['_isArchived'] ) ? IPS_UNIX_TIME_NOW : $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $topic['forum_id'], 'itemID' => $topic['tid'], 'itemLastUpdate' => $topic['last_post'] ) );
		
		/* Check against it */
		if ( $topic['poll_state'] AND ( $topic['last_vote'] > $topic['last_post'] ) )
		{
			$topic['_hasUnread'] = ( $lastMarked < $topic['last_vote'] ) ? true : false;
		}
		else
		{
			$topic['_hasUnread'] = ( $lastMarked < $topic['last_post'] ) ? true : false;
		}
		
		/* Determine which link normal or /unread */
		if ( $lastMarked && $topic['posts'] && $topic['_hasUnread'] )
		{
			/* They've seen this topic but not all of it or there have been new */
			$topic['_canJumpToUnread'] = true;
		}
		
		if ( $topic['_hasUnread'] )
		{
			$topic['_unreadUrl'] = $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'] . '&amp;view=getnewpost', 'public', $topic['title_seo'], 'showtopicunread' );
		}
		
		$topic['_url'] = $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'], 'public', $topic['title_seo'], 'showtopic' );
		
		//-----------------------------------------
		// Yawn
		//-----------------------------------------
		
		$topic['prefix']		= $topic['poll_state']		? $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->lang->words['poll_prefix'] ) : '';
		$topic['folder_img']	= $this->registry->getClass('class_forums')->fetchTopicFolderIcon( $topic, ( $this->memberData['member_id'] and !empty($topic['author_id']) ), ( $topic['_hasUnread'] ? 0 : 1 ) );
		
		if ( $topic['starter_id'] && ! empty($topic['_starter']['member_id']) )
		{
			$topic['starter'] = $this->registry->getClass('output')->getTemplate('global')->userHoverCard( $topic['_starter'] );
		}
		else
		{
			$topic['starter'] = $this->registry->getClass('output')->getTemplate('global')->userHoverCard( array( 'member_id' => 0, 'members_display_name' => $this->settings['guest_name_pre'] . $topic['starter_name'] . $this->settings['guest_name_suf'] ) );
		}
		
		if ( $topic['last_poster_id'] && ! empty($topic['member_id']) )
		{
			$topic['last_poster_name'] = $topic['members_display_name'];
			$topic['last_poster'] = $this->registry->getClass('output')->getTemplate('global')->userHoverCard( $topic );
		}
		else
		{
			$topic['last_poster_name'] = $this->settings['guest_name_pre'] . $topic['last_poster_name'] . $this->settings['guest_name_suf'];
			$topic['last_poster']      = $this->registry->getClass('output')->getTemplate('global')->userHoverCard( array( 'member_id' => 0, 'members_display_name' => $this->settings['guest_name_pre'] . $topic['last_poster_name'] . $this->settings['guest_name_suf'] ) );
		}
		
		//-----------------------------------------
		// Pages 'n' posts
		//-----------------------------------------
		
		$pages			= 1;
		$topic['pages']	= "";
		
		if ( $this->registry->class_forums->canQueuePosts( $topic['fourm_id'] ) )
		{
			$topic['posts'] += intval($topic['topic_queuedposts']);
		}
		
		if( $this->permissionsData['softDeleteSee'] )
		{
			$topic['posts'] += intval($topic['topic_deleted_posts']);
		}
		
		if ($topic['posts'])
		{
			if ( (($topic['posts'] + 1) % $this->settings['display_max_posts']) == 0 )
			{
				$pages = ($topic['posts'] + 1) / $this->settings['display_max_posts'];
			}
			else
			{
				$number = ( ($topic['posts'] + 1) / $this->settings['display_max_posts'] );
				$pages = ceil( $number);
			}
		}
		
		if ( $pages > 1 )
		{
			for ( $i = 0 ; $i < $pages ; ++$i )
			{
				$real_no = $i * $this->settings['display_max_posts'];
				$page_no = $i + 1;
				
				if ( $page_no == 4 and $pages > 4 )
				{
					$topic['pages'][] = array( 'last'   => 1,
					 					       'st'     => ($pages - 1) * $this->settings['display_max_posts'],
					  						   'page'   => $pages,
					 							'total' => $pages );
					break;
				}
				else
				{
					$topic['pages'][] = array( 'last' => 0,
											   'st'   => $real_no,
											   'page' => $page_no,
											 	'total' => $pages );
				}
			}
		}
		
		$topic['_hasqueued'] = 0;

		$mod	= $this->memberData['forumsModeratorData'] ? $this->memberData['forumsModeratorData'] : array();
		
		if ( ( $this->memberData['g_is_supmod'] or
				($mod[ $topic['forum_id'] ]['post_q'] AND $mod[ $topic['forum_id'] ]['post_q'] == 1) ) and 
				( $topic['topic_queuedposts'] ) 
			)
		{
			$topic['_hasqueued'] = 1;
		}
		
		//-----------------------------------------
		// Topic rating
		//-----------------------------------------
		
	    $topic['_rate_img']   = '';
	    
	    if ( !empty($this->forum['forum_allow_rating']) )
		{
			if ( $topic['topic_rating_total'] )
			{
				$topic['_rate_int'] = round( $topic['topic_rating_total'] / $topic['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $topic['topic_rating_hits'] >= $this->settings['topic_rating_needed'] ) AND ( $topic['_rate_int'] ) )
			{
				$topic['_rate_img']  = $this->registry->getClass('output')->getTemplate('forum')->topic_rating_image( $topic['_rate_int'] );
			}
		}
		
		//-----------------------------------------
		// Already switched on?
		//-----------------------------------------
		
		if ( $this->memberData['is_mod'] )
		{
			if ( $this->request['selectedtids'] )
			{
				if ( strstr( ','.$this->request['selectedtids'].',', ','.$topic['tid'].',' ) )
				{
					$topic['tidon'] = 1;
				}
				else
				{
					$topic['tidon'] = 0;
				}
			}
		}
		
		/* Tags */
		if ( ! empty( $topic['tag_cache_key'] ) )
		{
			$topic['tags'] = $this->registry->tags->formatCacheJoinData( $topic );
		}
		
		return $topic;
	}
	
	/**
	 * Builds an array of post data for output
	 *
	 * @param	array	$row	Array of post data
	 * @return	array
	 */
	public function parsePost( array $post )
	{
		/* Init */
		$topicData      = $this->getTopicData();
		$forumData      = $this->registry->getClass('class_forums')->getForumById( $topicData['forum_id'] );
		$permissionData = $this->getPermissionData();

		/* Start memory debug */
		$_NOW   = IPSDebug::getMemoryDebugFlag();
		$poster = array();
		
		/* Bitwise options */
		$_tmp = IPSBWOptions::thaw( $post['post_bwoptions'], 'post', 'forums' );

		if ( count( $_tmp ) )
		{
			foreach( $_tmp as $k => $v )
			{
				$post[ $k ] = $v;
			}
		}

		/* Is this a member? */
		if ( $post['author_id'] != 0 )
		{
			$poster = $this->parseMember( $post );
		}
		else
		{
			/* Sort out guest */
			$post['author_name']				= $this->settings['guest_name_pre'] . $post['author_name'] . $this->settings['guest_name_suf'];
			
			$poster								= IPSMember::setUpGuest( $post['author_name'] );
			$poster['members_display_name']		= $post['author_name'];
			$poster['_members_display_name']	= $post['author_name'];
			$poster['custom_fields']			= "";
			$poster['warn_img']					= "";
			$poster								= IPSMember::buildProfilePhoto( $poster );
		}
		
		/* Memory debug */
		IPSDebug::setMemoryDebugFlag( "PID: ".$post['pid'] . " - Member Parsed", $_NOW );
		
		/* Update permission */
		$this->registry->getClass('class_forums')->setMemberData( $this->getMemberData() );
		$permissionData['softDelete'] = $this->registry->getClass('class_forums')->canSoftDeletePosts( $topicData['forum_id'], $post );
		
		/* Soft delete */
		$post['_softDelete']        = ( $post['pid'] != $topicData['topic_firstpost'] ) ? $permissionData['softDelete'] : FALSE;
		$post['_softDeleteRestore'] = $permissionData['softDeleteRestore'];
		$post['_softDeleteSee']     = $permissionData['softDeleteSee'];
		$post['_softDeleteReason']  = $permissionData['softDeleteReason'];
		$post['_softDeleteContent'] = $permissionData['softDeleteContent'];
		
		$post['_isVisible']		   = ( $this->registry->getClass('class_forums')->fetchHiddenType( $post ) == 'visible' ) ? true : false;
		$post['_isHidden']		   = ( $this->registry->getClass('class_forums')->fetchHiddenType( $post ) == 'hidden' ) ? true : false;
		$post['_isDeleted']		   = ( $this->registry->getClass('class_forums')->fetchHiddenType( $post ) == 'sdelete' ) ? true : false;
		
		/* Queued */
		if ( $topicData['topic_firstpost'] == $post['pid'] and ( $post['_isHidden'] OR $topicData['_isHidden'] ) )
		{
			$post['queued']    = 1;
			$post['_isHidden'] = true;
		}
	
		/* Edited stuff */
		$post['edit_by'] = "";
		
		if ( ( $post['append_edit'] == 1 ) and ( $post['edit_time'] != "" ) and ( $post['edit_name'] != "" ) )
		{
			$e_time = $this->registry->class_localization->getDate( $post['edit_time'] , 'LONG' );
			
			$post['edit_by'] = sprintf( $this->lang->words['edited_by'], $post['edit_name'], $e_time );
		}
		
		/* Now parse the post */
		if ( ! isset($post['cache_content']) OR ! $post['cache_content'] )
		{
			$_NOW2   = IPSDebug::getMemoryDebugFlag();
			
			IPSText::getTextClass('bbcode')->parse_smilies			= $post['use_emo'];
			IPSText::getTextClass('bbcode')->parse_html				= ( $forumData['use_html'] and $poster['g_dohtml'] and $post['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_nl2br			= $post['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_bbcode			= $forumData['use_ibc'];
			IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $post['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $post['mgroup_others'];
			
			$post['post']	= IPSText::getTextClass('bbcode')->preDisplayParse( $post['post'] );
					
			IPSDebug::setMemoryDebugFlag( "topics::parsePostRow - bbcode parse - Completed", $_NOW2 );
			
			IPSContentCache::update( $post['pid'], 'post', $post['post'] );
		}
		else
		{
			$post['post'] = '<!--cached-' . gmdate( 'r', $post['cache_updated'] ) . '-->' . $post['cache_content'];
		}
		
		/* Buttons */
		$post['_can_delete'] = $post['pid'] != $topicData['topic_firstpost'] 
							  ? $this->canDeletePost( $post ) 
							  : FALSE;		
		$post['_can_edit']   = $this->canEditPost( $post );
		$post['_show_ip']	 = $this->canSeeIp();
		$post['_canReply']   = ( $this->getReplyStatus() == 'reply' ) ? true : false;
		
		/* Signatures */
		$post['signature'] = "";
		
		if ( ! empty( $poster['signature'] ) )
		{
			if ( $post['use_sig'] == 1 )
			{
				if ( ! $this->memberData['view_sigs'] || ( $poster['author_id'] && $this->memberData['member_id'] && ! empty( $this->member->ignored_users[ $poster['author_id'] ]['ignore_signatures'] ) && IPSMember::isIgnorable( $poster['member_group_id'], $poster['mgroup_others'] ) ) )
				{
					$post['signature'] = '<!--signature.hidden.' . $post['pid'] . '-->';
				}
				else
				{
					$post['signature'] = $this->registry->output->getTemplate( 'global' )->signature_separator( $poster['signature'], $poster['author_id'], IPSMember::isIgnorable( $poster['member_group_id'], $poster['mgroup_others'] ) );
				}
			}
		}
		
		$post['forum_id'] = $topicData['forum_id'];		
		
		/* Reputation */
		if ( $this->settings['reputation_enabled'] AND ! $this->isArchived( $topicData ) )
		{ 
			/* Load the class */
			if ( ! $this->registry->isClassLoaded( 'repCache' ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
				$this->registry->setClass( 'repCache', new $classToLoad() );
			}
			
			$this->memberData['_members_cache']['rep_filter'] = isset( $this->memberData['_members_cache']['rep_filter'] ) ? $this->memberData['_members_cache']['rep_filter'] : '*';
			
			$post['pp_reputation_points']	= $post['pp_reputation_points'] ? $post['pp_reputation_points'] : 0;
			$post['has_given_rep']			= $post['has_given_rep'] ? $post['has_given_rep'] : 0;
			$post['rep_points']				= $this->registry->repCache->getRepPoints( array( 'app' => 'forums', 'type' => 'pid', 'type_id' => $post['pid'], 'rep_points' => $post['rep_points'] ) );
			$post['_repignored']			= 0;
			
			if ( ! ( $this->settings['reputation_protected_groups'] && 
				    in_array( $this->memberData['member_group_id'], explode( ',', $this->settings['reputation_protected_groups'] ) ) 
				   ) &&
			 	$this->memberData['_members_cache']['rep_filter'] !== '*' 
			)
			{
				if ( $this->settings['reputation_show_content'] && $post['rep_points'] < $this->memberData['_members_cache']['rep_filter'] && $this->settings['reputation_point_types'] != 'like' )
				{
					$post['_repignored'] = 1;
				}
			}
			
			if ( $this->registry->repCache->isLikeMode() )
			{
				$post['like'] = $this->registry->repCache->getLikeFormatted( array( 'app' => 'forums', 'type' => 'pid', 'id' => $post['pid'], 'rep_like_cache' => $post['rep_like_cache'] ) );
			}
		}
		
		/* Ignore stuff */
		$post['_ignored']	= 0;

		if ( $post['author_id'] && isset( $topicData['ignoredUsers'] ) && is_array( $topicData['ignoredUsers'] ) && count( $topicData['ignoredUsers'] ) )
		{ 
			if( in_array( $post['author_id'], $topicData['ignoredUsers'] ) )
			{
				if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$post['post']['member_group_id'].',' ) )
				{
					$post['_ignored'] = 1;
				}
			}
		}
		
		/* AD Code */
		$post['_adCode']	= '';

		if ( $this->registry->getClass('IPSAdCode')->userCanViewAds() && !$this->getTopicData('adCodeSet') && !IPS_IS_AJAX )
		{
			$post['_adCode'] = $this->registry->getClass('IPSAdCode')->getAdCode('ad_code_topic_view_code');
			if ( $post['_adCode'] )
			{
				$this->setTopicData( 'adCodeSet', true );
			}
		}
		
		/* Memory debug */
		IPSDebug::setMemoryDebugFlag( "PID: ".$post['pid']. " - Completed", $_NOW );
		
		return array( 'post' => $post, 'author' => $poster );
	}
	
	/**
	 * Add the recent post
	 * @param array $post
	 */
	public function addRecentPost( $post )
	{
		if ( $post['post_author_id'] && $post['post_id'] && $post['post_topic_id'] && $post['post_forum_id'] )
		{
			$this->DB->insert( 'forums_recent_posts', array( 'post_id'        => intval( $post['post_id'] ),
															 'post_topic_id'  => intval( $post['post_topic_id'] ),
															 'post_forum_id'  => intval( $post['post_forum_id'] ),
															 'post_author_id' => intval( $post['post_author_id'] ),
															 'post_date'      => intval( $post['post_date'] )
							  )								);
		}
	}
	
	/**
	 * Delete a recent post
	 * @param array $post
	 */
	public function deleteRecentPost( $where )
	{
		$query = array();
		
		foreach( array( 'post_id', 'post_topic_id', 'post_forum_id', 'post_author_id', 'post_date' ) as $k )
		{
			if ( ! empty( $where[ $k ] ) )
			{
				$query[] = ( is_array( $where[ $k ] ) ) ? $k . ' IN (' . implode( ',', $where[ $k ] ) . ')' : $k . '=' . intval( $where[ $k ] );
			}
		}
		
		if ( count( $query ) )
		{
			$this->DB->delete( 'forums_recent_posts', implode( ' AND ', $query ) );
		}
	}
	
	/**
	 * Update a recent post
	 * @param array $post
	 */
	public function updateRecentPost( $update, $where )
	{
		$query  = array();
		
		foreach( array( 'post_id', 'post_topic_id', 'post_forum_id', 'post_author_id', 'post_date' ) as $k )
		{
			if ( ! empty( $where[ $k ] ) )
			{
				$query[] = ( is_array( $where[ $k ] ) ) ? $k . ' IN (' . implode( ',', $where[ $k ] ) . ')' : $k . '=' . intval( $where[ $k ] );
			}
		}
		
		if ( count( $update ) )
		{
			$this->DB->update( 'forums_recent_posts', $update, implode( ' AND ', $query ) );
		}
	}
	
	/**
	 * Restore posts
	 * @param array $where
	 */
	public function restoreRecentPost( $where )
	{
		$date  = IPS_UNIX_TIME_NOW - 86400;
		$PRE   = trim(ipsRegistry::dbFunctions()->getPrefix());
		$query = array();
		
		$remap = array( 'post_id'        => 'pid',
						'post_topic_id'  => 'topic_id',
						'post_author_id' => 'post_author_id' );
		
		foreach( array( 'post_id', 'post_topic_id', 'post_forum_id', 'post_author_id' ) as $k )
		{
			if ( ! empty( $where[ $k ] ) )
			{
				$query[] = ( is_array( $where[ $k ] ) ) ? $remap[ $k ] . ' IN (' . implode( ',', $where[ $k ] ) . ')' : $remap[ $k ] . '=' . intval( $where[ $k ] );
			}
		}
		
		if ( count( $query ) )
		{
			$this->DB->loadCacheFile( IPSLib::getAppDir('forums') . '/sql/' . ips_DBRegistry::getDriverType() . '_topics_queries.php', 'topics_sql_queries' );
			
			$this->DB->buildFromCache( 'restoreRecentPost', array( 'query' => $query, 'date' => $date ), 'topics_sql_queries' );
			$this->DB->execute();
		}
	}
	
	/**
	 * Returns either a field name from post_db or from archive_db
	 * @param string $field
	 * @param string $isArchive
	 */
	public function getPostTableField( $field, $isArchive )
	{
		if ( ! $isArchive )
		{
			if ( $field == '_prefix_' )
			{
				return '';
			}
			
			if ( $field == '_table_' )
			{
				return 'posts';
			}
			
			return $field;
		}
		else
		{
			if ( $field == '_table_' )
			{
				return 'forums_archive_posts';
			}
			
			return $this->fields[ $field ];
		}
	}
	
	/**
	 * Take an archive row and returned native friendly array
	 * @param array $post
	 * @return array
	 */
	public function archivePostToNativeFields( $post )
	{
		$native  = array( 'pid'           	 => intval( $post['archive_id'] ),
						  'author_id'    	 => intval( $post['archive_author_id'] ),
						  'author_name'  	 => $post['archive_author_name'],
					      'ip_address'   	 => $post['archive_ip_address'],
						  'post_date' 	     => intval( $post['archive_content_date'] ),
						  'post'		 	 => $post['archive_content'],
						  'queued'	 	     => $post['archive_queued'],
						  'topic_id'     	 => intval( $post['archive_topic_id'] ),
						  'new_topic'     	 => intval( $post['archive_is_first'] ),
						  'post_bwoptions'   => $post['archive_bwoptions'],
						  'post_key'   	 	 => $post['archive_attach_key'],
						  'post_htmlstate'   => $post['archive_html_mode'],
						  'use_sig'   		 => $post['archive_show_signature'],
						  'use_emo'   		 => $post['archive_show_emoticons'],
						  'append_edit'   	 => $post['archive_show_edited_by'],
						  'edit_time'   	 => $post['archive_edit_time'],
						  'edit_name'   	 => $post['archive_edit_name'],
						  'post_edit_reason' => $post['archive_edit_reason'] );
		
		return array_merge( $post, $native );
	}
	
	/**
	 * Get fields
	 * @return array
	 */
	public function getPostTableFields()
	{
		return $this->fields;
	}
	
	/**
	 * Set post filters
	 * Takes user input and cleans it up a bit
	 *
	 * @param	array		Incoming filters
	 * @return	array
	 */
	protected function _setPostFilters( $filters )
	{
		$filters['sortOrder']		= ( isset( $filters['sortOrder'] ) )	? $filters['sortOrder']	: '';
		$filters['sortField']		= ( isset( $filters['sortField'] ) )	? $filters['sortField']	: '';
		$filters['offset']			= ( isset( $filters['offset'] ) )		? $filters['offset']	: '';
		$filters['limit']			= ( isset( $filters['limit'] ) )		? $filters['limit']		: '';
		$filters['isVisible']		= ( isset( $filters['isVisible'] ) )	? $filters['isVisible']	: '';
		
		switch( $filters['sortOrder'] )
		{
			default:
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder'] = 'desc';
			break;
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder'] = 'asc';
			break;
		}
		
		/* Do some set up */
		switch( $filters['sortField'] )
		{
			case 'date':
			case 'time':
				$filters['sortField']  = 'post_date';
			break;
			case 'pid':
			case 'id':
				$filters['sortField']  = 'pid';
			break;
		}
	
		
		/* Others */
		$filters['offset']       = intval( $filters['offset'] );
		$filters['limit']        = intval( $filters['limit'] );
		$filters['unixCutOff']   = ( ! empty( $filters['unixCutOff'] ) ) ? intval( $filters['unixCutOff'] ) : 0;
		
		/* So we don't have to do this twice */
		$filters['_cleaned']   = true;
		
		return $filters;
	}
	
	/**
	 * Set topic filters
	 * Takes user input and cleans it up a bit
	 *
	 * @param	array		Incoming filters
	 * @return	array
	 */
	protected function _setTopicFilters( $filters )
	{
		$filters['sortOrder']		= ( isset( $filters['sortOrder'] ) )		? $filters['sortOrder']	: '';
		$filters['sortField']		= ( isset( $filters['sortField'] ) )		? $filters['sortField']	: '';
		$filters['offset']			= ( isset( $filters['offset'] ) )			? $filters['offset']	: '';
		$filters['limit']			= ( isset( $filters['limit'] ) )			? $filters['limit']		: '';
		$filters['isVisible']		= ( isset( $filters['isVisible'] ) )		? $filters['isVisible']	: '';
		
		switch( $filters['sortOrder'] )
		{
			default:
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder'] = 'desc';
			break;
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder'] = 'asc';
			break;
		}
		
		/* Do some set up */
		switch( $filters['sortField'] )
		{
			case 'date':
			case 'time':
			case 'start_date':
				$filters['sortField']  = 'start_date';
			break;
			case 'lastDate':
			case 'lastTime':
			case 'last_post':
				$filters['sortField']  = 'last_post';
			break;
			case 'tid':
			case 'id':
				$filters['sortField']  = 'tid';
			break;
		}
	
		
		/* Others */
		$filters['offset']       = intval( $filters['offset'] );
		$filters['limit']        = intval( $filters['limit'] );
		$filters['unixCutOff']   = ( ! empty( $filters['unixCutOff'] ) ) ? intval( $filters['unixCutOff'] ) : 0;
		
		/* So we don't have to do this twice */
		$filters['_cleaned']   = true;
		
		return $filters;
	}
	
}