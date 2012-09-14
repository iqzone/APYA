<?php
/**
 * @file		forums.php 	Forums like class (forums application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		15th Feb 2011
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
 * @class		like_forums_forums_composite
 * @brief		Forums like class (forums application)
 */
class like_forums_forums_composite extends classes_like_composite
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
		return 'forum';
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
		return array( 'forums', $this->lang->words['follow_notify_forums'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_f';
	}
	
	/**
	 * Check notifications that are to be sent to make sure they're valid and that
	 * 
	 * @param	array		$metaData		like_ DB data and like owner member data
	 * @return	@e boolean
	 */
	public function notificationCanSend( $metaData )
	{
		$group = $this->caches['group_cache'][ $metaData['member_group_id'] ];
		
		/* Can only see own topics? */
		if ( ! $group['g_other_topics'] )
		{
			/* Send nothing because we can only see our topics and we never get notification of our own content */
			return false;
		}
		
		$forum = $this->registry->getClass('class_forums')->getForumById( $metaData['like_rel_id'] );
		
		/* Set member */
		$this->registry->getClass('class_forums')->setMemberData( $metaData );
		
		$result = $this->registry->getClass('class_forums')->forumsCheckAccess( $forum['id'], 0, 'forum', array(), true );
		
		/* reset */
		$this->registry->getClass('class_forums')->setMemberData( $this->memberData );
		
		if ( ! $result )
		{
			return false;
		}
		
		return true;
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
		$forum = array_merge( $data, $this->registry->permissions->parse( $data ) );
		$fFurl = $this->registry->getClass('output')->buildSEOUrl( 'showforum=' . $forum['id'], 'publicNoSession', $forum['name_seo'], 'showforum' );
		$group = $this->caches['group_cache'][ $data['member_group_id'] ];
		
		/* Topic has been posted in since last digest? */
		if ( $forum['last_post'] <= $data['like_notify_sent'] )
		{
			return false;
		}
		
		/* Set member */
		$this->registry->getClass('class_forums')->setMemberData( $data );
		
		$result = $this->registry->getClass('class_forums')->forumsCheckAccess( $forum['id'], 0, 'forum', array(), true );
		
		/* reset */
		$this->registry->getClass('class_forums')->setMemberData( $this->memberData );
		
		if ( ! $result )
		{
			return false;
		}
		
		/* build email unless cached */
		if ( empty( $this->_cache['bndf'][ $forum['id'] ] ) )
		{
			$othersPosted = false;
			$topics       = $this->registry->getClass('topics')->getTopics( array( 'forumId'       => $forum['id'],
														         				   'topicType'     => 'visible',
																 				   'dateIsGreater' => $data['like_notify_sent'],
																 				   'getFirstPost'  => true,
														         				   'sortField'     => 'tid',
														         				   'sortOrder' 	   => 'asc',
														         				   'limit'         => 50 ) );
			
			foreach( $topics as $pid => $topic )
			{
				/* Don't send if it's just us... */
				if ( $data['like_member_id'] != $topic['starter_id'] )
				{
					$othersPosted = true;
				}			
				
				$post_output .= "<br />-------------------------------------------<br />"
				             .  $this->lang->words['topic_langbit'] . ": " . $topic['title'] . " ( ".  $topic['starter_name'] . " -- " . ipsRegistry::getClass('class_localization')->getDate( $topic['start_date'], 'SHORT' ) . ")<br />"
				             .  $this->registry->getClass('output')->buildSEOUrl( 'showtopic=' . $topic['tid'], 'publicNoSession', $topic['title_seo'], 'showtopic' )
							 .  "<br />............................................<br />"
							 .  $topic['post'] . "<br /><br />";
			}
			
			/* ensure we have something to send */
			if ( ! $post_output )
			{
				return false;
			}
								
			/* Process it */
			$main_output = $this->lang->words['forum_langbit'] .  ":" . $forum['name'] . ")<br />"
						 . "=====================================<br />"
						 . $post_output
						 . "<br />=====================================<br />";
						 
			$this->_cache['bndf'][ $forum['id'] ] = $main_output;
		}
		else
		{
			$othersPosted = true;
			$main_output  = $this->_cache['bndf'][ $forum['id'] ];
		}
		
		if ( $othersPosted )
		{
			/* Return array */		
			return array( 'notification_key'	    => 'followed_forums_digest',
		        		  'notification_url'		=> $fFurl,
		        		  'email_template'			=> ( $data['like_notify_freq'] == 'daily' ) ? 'digest_forum_daily' : 'digest_forum_weekly',
		        		  'email_subject'	    	=> ( $data['like_notify_freq'] == 'daily' ) ? $this->lang->words['subject__digest_forum_daily'] : $this->lang->words['subject__digest_forum_weekly'],
		        		  'build_message_array'		=> array( 'URL'  	    => $fFurl,
															  'TITLE'		=> $forum['name'],
															  'NAME'		=> '-member:members_display_name-',
															  'FORUM_NAME'	=> $forum['name'],
															  'FORUM_ID'	=> $forum['id'],
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
		return array( array( 'select' => 'f.*', 'from' => array( 'forums' => 'f'), 'where' => 'f.id=' . $field, 'type' => 'left' ),
					  array( 'select' => 'p.*', 'from' => array( 'permission_index' => 'p'), 'where' => "p.perm_type_id=f.id AND p.app='forums' AND p.perm_type='forum'", 'type' => 'left' )  );
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
		
		$this->DB->build( array( 'select' => 'f.*',
								 'from'   => array( 'forums' => 'f' ),
								 'where'  => 'f.id IN (' . implode( ',', $relId ) . ')',
								 'add_join' => array( array( 'select' => 'p.id as parent_fid, p.name as parent_name, p.name_seo as parent_name_seo',
															 'from'   => array( 'forums' => 'p' ),
															 'where'  => 'p.id=f.parent_id',
															 'type'   => 'left'  ) ) ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.title'] = $row['name'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.url'] = $this->registry->output->buildSEOUrl( "showforum=" . $row['id'], "public", $row['name_seo'], "showforum" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.type'] = $this->lang->words['follow_forum'];
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.parentTitle'] = ( ! empty( $row['parent_name'] ) ) ? $row['parent_name'] : null;
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.parentUrl'] = ( ! empty( $row['parent_name'] ) ) ? $this->registry->output->buildSEOUrl( "showforum=" . $row['parent_fid'], "public", $row['parent_name_seo'], "showforum" ) : null;
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.parentType'] = '';
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
		return array( 'extraWhere' => $this->DB->buildWherePermission( $this->registry->class_forums->fetchSearchableForumIds( $this->memberData['member_id'] ), 'l.like_rel_id', FALSE ) );
	}
}