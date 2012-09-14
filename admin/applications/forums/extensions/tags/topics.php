<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Tagging: Forums/Topics Class
 * Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		25 Feb 2011
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_forums_topics extends classes_tag_abstract
{
	protected $topicCache = array();
		
	/**
	 * CONSTRUCTOR
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
	}
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		if ( ! $this->registry->isClassLoaded('class_forums') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			$this->registry->getClass('class_forums')->forumsInit();
		}
		
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		parent::init();
	}
	
	/**
	 * Little 'trick' to force preset tags
	 *
	 * @param	string	view to show
	 * @param	array	Where data to show
	 */
	public function render( $what, $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$forum = $this->registry->class_forums->getForumById( $where['meta_parent_id'] );
		}
		
		if ( ! empty( $forum['tag_predefined'] ) )
		{
			/* Turn off open system */
			$this->settings['tags_open_system'] = false;
		}
		
		return parent::render( $what, $where );
	}
	
	/**
	 * Fetches parent ID
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		$topic = $this->_getTopic( $where['meta_id'] );
		
		return intval( $topic['forum_id'] );
	}
	
	/**
	 * Fetches permission data
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		if ( isset( $where['meta_id'] ) )
		{
			$topic = $this->_getTopic( $where['meta_id'] );
			$forum = $this->registry->class_forums->getForumById( $topic['forum_id'] );
		}
		else if ( isset( $where['meta_parent_id'] ) )
		{
			$forum = $this->registry->class_forums->getForumById( $where['meta_parent_id'] );
		}
		
		return $forum['perm_view'];
	}
	
	/**
	 * Basic permission check
	 * @param	string	$what (add/remove/edit/create/prefix) [ add = add new tags to items, create = create unique tags, use a tag as a prefix for an item ]
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		/* Check parent */
		$return = parent::can( $what, $where );

		if ( $return === false  )
		{
			return $return;
		}
		
		/* Init some vars */
		$topic = array();
		$forum = array();
		
		if ( ! empty( $where['meta_id'] ) )
		{
			$topic = $this->_getTopic( $where['meta_id'] );
			$forum = $this->registry->class_forums->getForumById( $topic['forum_id'] );
		}
		else if ( ! empty( $where['meta_parent_id'] ) )
		{
			$forum = $this->registry->class_forums->getForumById( $where['meta_parent_id'] );
		}
		
		/* Forum disabled */
		if ( $forum['bw_disable_tagging'] )
		{
			return false;
		}

		switch ( $what )
		{
			case 'create':
				if ( ! $this->_isOpenSystem() )
				{
					return false;
				}
				
				return true;
			break;
			case 'add':
			
				if ( $this->registry->class_forums->canStartTopic( $forum['id'] ) )
				{
					return true;
				}
				else if( defined('IN_CONVERTER') AND IN_CONVERTER == true )
				{
					return true;
				}
			break;
			case 'edit':
			case 'remove':
				if ( $this->memberData['member_id'] == $topic['starter_id'] or $this->memberData['g_is_supmod'] or ( $this->memberData['is_mod'] and isset( $this->memberData['forumsModeratorData'][ $forum['id'] ] ) and $this->memberData['forumsModeratorData'][ $forum['id'] ]['edit_post'] ) )
				{
					return true;
				}
			break;
			case 'prefix':
				if ( $this->registry->class_forums->canStartTopic( $forum['id'] ) )
				{
					return true;
				}
				else if( defined('IN_CONVERTER') AND IN_CONVERTER == true )
				{
					return true;
				}
			break;
		}
		
		return false;
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		$topic    = $this->_getTopic( $where['meta_id'] );
		$approved = $this->registry->class_forums->fetchHiddenTopicType( $topic );
		
		return ( $approved == 'visible' ) ? 1 : 0;
	}
	
	/**
	 * Search for tags
	 * @param mixed $tags	Array or string
	 * @param array $options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 */
	public function search( $tags, $options )
	{
		$ok = array();
		
		/* Fix up forum IDs */
		if ( isset( $options['meta_parent_id'] ) )
		{
			if ( is_array( $options['meta_parent_id'] ) )
			{
				foreach( $options['meta_parent_id'] as $id )
				{
					if ( $this->_canSearchForum( $id ) === true )
					{
						$ok[] = $id;
					}
				}
			}
			else
			{
				if ( $this->_canSearchForum( $options['meta_parent_id'] ) === true )
				{
					$ok[] = $options['meta_parent_id'];
				}
			}
		}
		else
		{
			/* Fetch forum IDs */
			$allForums = $this->registry->class_forums->fetchSearchableForumIds();
			
			foreach( $allForums as $id )
			{
				if ( $this->_canSearchForum( $id ) === true )
				{
					$ok[] = $id;
				}
			}
		}
		
		$options['meta_parent_id'] = $ok;
		
		return parent::search( $tags, $options );
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array	Where Data
	 * @return	Array of pre-defined tags or null
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$forum = $this->registry->class_forums->getForumById( $where['meta_parent_id'] );
		}
		
		$this->settings['tags_predefined'] = ( ! empty( $forum['tag_predefined'] ) ) ? $forum['tag_predefined'] : $this->settings['tags_predefined'];
		
		return parent::_getPreDefinedTags( $where );
	}
	
	/**
	 * Can set an item as a topic prefix
	 * 
	 * @param 	array		$where		Where Data
	 * @return 	@e boolean
	 */
	protected function _prefixesEnabled( $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$forum = $this->registry->class_forums->getForumById( $where['meta_parent_id'] );
		}
		
		if ( $forum['bw_disable_prefixes'] )
		{
			return false;
		}
		else
		{
			return parent::_prefixesEnabled( $where );
		}
	}
	
	/**
	 * Check a forum for tag searching
	 * 
	 * @param	id		$id		Forum ID
	 * @return	@e boolean	True if it can be searched
	 */
	protected function _canSearchForum( $id )
	{
		$data = $this->registry->class_forums->getForumById( $id );
					
		if ( ! $this->registry->permissions->check( 'read', $data ) )
		{
			return false;
		}
		
		/* Can read, but is it password protected, etc? */
		if ( ! $this->registry->class_forums->forumsCheckAccess( $id, 0, 'forum', array(), true ) )
		{
			return false;
		}

		if ( ! $data['sub_can_post'] OR ! $data['can_view_others'] )
		{
			return false;
		}
	
		/* Tagging disabled */
		if ( $data['bw_disable_tagging'] )
		{
			return false;
		}
		
		return true;
	}

	
	/**
	 * Fetch a topic
	 * 
	 * @param	integer		$tid	Topic ID
	 * @return	@e array	Topic data
	 */
	protected function _getTopic( $tid )
	{
		if ( ! isset( $this->topicCache[ $tid ] ) )
		{
			$this->topicCache[ $tid ] = $this->registry->getClass('topics')->getTopicById( $tid );
		}
		
		return $this->topicCache[ $tid ];
	}
}