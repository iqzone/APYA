<?php
/**
 * @file		topics.php 	Topics like class (forums application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * $LastChangedDate: 2012-05-29 04:58:00 -0400 (Tue, 29 May 2012) $
 * @version		v3.3.3
 * $Revision: 10803 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		like_forums_topics_composite
 * @brief		Topics like class (forums application)
 */
class like_forums_topics_composite extends classes_like_composite
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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

	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		if ( ! $this->registry->isClassLoaded('class_forums') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			$this->registry->getClass('class_forums')->forumsInit();
		}
		
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		$this->lang->loadLanguageFile( array( 'public_forums', 'public_topic' ), 'forums' );
	}
	
	/**
	 * Fetch the template group
	 * 
	 * @return	@e string
	 */
	public function skin()
	{
	     return 'topic';
	}	
	
	/**
	 * Return an array of acceptable frequencies
	 * Possible: immediate, offline, daily, weekly
	 * 
	 * @return	@e array
	 */
	public function allowedFrequencies()
	{
		return array( 'immediate', 'offline', 'daily', 'weekly' );
	}
	
	/**
	 * Return types of notification available for this item
	 * 
	 * @return	@e array	array( key, human readable )
	 */
	public function getNotifyType()
	{
		return array( 'topics', $this->lang->words['follow_notify_topics'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_t';
	}
	
	/**
	 * Check notifications that are to be sent to make sure they're valid and that
	 * 
	 * @param	array		$metaData		like_ DB data and like owner member data
	 * @return	@e boolean
	 */
	public function notificationCanSend( $metaData )
	{
		/* Digests are checked in buildNotificationData */
		$topic = $this->registry->getClass('topics')->getTopicById( $metaData['like_rel_id'] );
		$forum = $this->registry->getClass('class_forums')->getForumById( $topic['forum_id'] );
		
		/* Set member */
		$this->registry->getClass('class_forums')->setMemberData( $metaData );
		
		$result = $this->registry->getClass('class_forums')->forumsCheckAccess( $forum['id'], 0, 'forum', $topic, true );
		
		/* reset */
		$this->registry->getClass('class_forums')->setMemberData( $this->memberData );
		
		if ( ! $result )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Function to let plugins determine if a notification should not be sent.  Return false to send notification, or true to skip sending it.
	 * 
	 * @param	array	Notification data
	 * @return	@e bool
	 */
 	public function excludeNotification( $row )
 	{
 		if ( $row['like_notify_freq'] == 'offline' AND $row['last_activity'] < $row['like_notify_sent'] )
 		{
 			return true;
 		}
 		
 		return false;
 	}
	
	/**
	 * Builds the notification data via the app class
	 * 
	 * @param	array		$data		like_ DB data and like owner member data
	 * @param	string		$type		Types of notifications to send
	 * @return	@e array	array( notification_key, notification_url, email_template, email_subject, build_message_array )
	 * @see		allowedFrequencies()
	 */
	public function buildNotificationData( $data, $type )
	{
		$topic     = $data;
		$forum     = $this->registry->getClass('class_forums')->getForumById( $topic['forum_id'] );
		$flag      = $this->registry->class_forums->fetchHiddenTopicType( $topic );
		$tFurl     = $this->registry->getClass('output')->buildSEOUrl( 'showtopic=' . $topic['tid'], 'publicNoSession', $topic['title_seo'], 'showtopic' );
		$group     = $this->caches['group_cache'][ $data['member_group_id'] ];
		
		/* Topic has been posted in since last digest? */
		if ( $topic['last_post'] <= $data['like_notify_sent'] )
		{
			return false;
		}
		
		/* Unapproved or deleted */
		if ( $flag != 'visible' )
		{
			return false;
		}
		
		/* Set member */
		$this->registry->getClass('class_forums')->setMemberData( $data );
		
		$result = $this->registry->getClass('class_forums')->forumsCheckAccess( $forum['id'], 0, 'forum', $topic, true );
		
		/* reset */
		$this->registry->getClass('class_forums')->setMemberData( $this->memberData );
		
		if ( ! $result )
		{
			return false;
		}
			
		/* build email unless cached */
		if ( empty( $this->_cache['bnd'][ $topic['tid'] ] ) )
		{
			$othersPosted = false;
			$posts        = $this->registry->getClass('topics')->getPosts( array( 'topicId'       => $topic['tid'],
																			      'postType'      => 'visible',
																				  'dateIsGreater' => $data['like_notify_sent'],
																			      'sortField'     => 'pid',
																			      'limit' 		  => 50,
																			      'sortOrder' 	  => 'asc' ) );
			
			foreach( $posts as $pid => $post )
			{
				/* Don't send if it's just us... */
				if ( $data['like_member_id'] != $post['author_id'] )
				{
					$othersPosted = true;
				}
				
				$post_output .= "<br />-------------------------------------------<br />"
							 .  $post['author_name'] . " -- " . ipsRegistry::getClass( 'class_localization')->getDate( $post['post_date'], 'SHORT' ) . "<br />" . $post['post'] . "<br /><br />";
			}
			
			/* ensure we have something to send */
			if ( ! $post_output )
			{
				return false;
			}
								
			/* Process it */
			$main_output .= $this->lang->words['topic_langbit'] . ": " . $topic['title'] . " (" . $this->lang->words['forum_langbit'] . ":" . $forum['name'] . ")<br />"
						 .  $tFurl . "<br />"
						 .  "=====================================<br />"
						 .  $post_output
						 .  "<br />=====================================<br />";
			
			$this->_cache['bnd'][ $topic['tid'] ] = $main_output;
		}
		else
		{
			$othersPosted = true;
			$main_output  = $this->_cache['bnd'][ $topic['tid'] ];
		}
		
		if ( $othersPosted )
		{
			/* Return array */		
			return array( 'notification_key'	    => 'followed_topics_digest',
		        		  'notification_url'		=> $tFurl,
		        		  'email_template'			=> ( $data['like_notify_freq'] == 'daily' ) ? 'digest_topic_daily' : 'digest_topic_weekly',
		        		  'email_subject'	    	=> ( $data['like_notify_freq'] == 'daily' ) ? $this->lang->words['subject__digest_topic_daily'] : $this->lang->words['subject__digest_topic_weekly'],
		        		  'build_message_array'		=> array( 'URL'  	    => $tFurl,
															  'TITLE'		=> $topic['title'],
															  'NAME'		=> '-member:members_display_name-',
															  'CONTENT'		=> $main_output ) );
		}
		
	}
	
	/**
	 * Fetches joins for fetching data
	 * 
	 * @param	string		$field		DB field name (defaults to 'l.like_rel_id')
	 * @return	@e array
	 */
	public function getDataJoins( $field='l.like_rel_id' )
	{
		return array( array( 'select' => 't.*, t.last_post as topic_last_post', 'from' => array( 'topics' => 't' ), 'where' => 't.tid=' . $field, 'type' => 'left' ) );
	}
	
	/**
	 * Returns the type of item
	 * 
	 * @param	mixed		$relId			Relationship ID or array of IDs
	 * @param	array		$selectType		Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
	 * @return	@e array	Meta data
	 */
	public function getMeta( $relId, $selectType=null )
	{
		$return    = array();
		$isNumeric = false;
		
		if ( is_numeric( $relId ) )
		{
			$relId     = array( intval($relId) );
			$isNumeric = true;
		}

		$this->DB->build( array( 'select' => 't.*',
								 'from'   => array( 'topics' => 't' ),
								 'where'  => 't.tid IN (' . implode( ',', $relId ) . ')',
								 'add_join' => array( array( 'select' => 'id as parent_fid, p.name as parent_name, p.name_seo as parent_name_seo',
															 'from'   => array( 'forums' => 'p' ),
															 'where'  => 'p.id=t.forum_id',
															 'type'   => 'left'  ) ) ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['tid'] ]['like.title'] = $row['title'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['tid'] ]['like.url'] = $this->registry->output->buildSEOUrl( "showtopic=" . $row['tid'], "public", $row['title_seo'], "showtopic" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['tid'] ]['like.type'] = $this->lang->words['follow_topic'];
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['tid'] ]['like.parentTitle'] = ( ! empty( $row['parent_name'] ) ) ? $row['parent_name'] : null;
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['tid'] ]['like.parentUrl'] = ( ! empty( $row['parent_name'] ) ) ? $this->registry->output->buildSEOUrl( "showforum=" . $row['parent_fid'], "public", $row['parent_name_seo'], "showforum" ) : null;
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['tid'] ]['like.parentType'] = $this->lang->words['follow_forum'];
			} 
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
	
	/**
	 * Returns the join table for sorting when using 'view content I follow'
	 * core_like has the alias "l".
	 */
	public function getSearchJoinAndSortBy()
	{
		return array( 'from'  => array( 'topics' => 't' ),
					  'where' => 'l.like_rel_id=t.tid',
					  'order' => 't.last_post',
					  'extraWhere' => $this->DB->buildWherePermission( $this->registry->class_forums->fetchSearchableForumIds( $this->memberData['member_id'] ), 't.forum_id', FALSE ) );
	}
}