<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Tagging: Blog Entries
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	© 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		25 Feb 2011
 * @version		$Revision: 4 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_blog_entries extends classes_tag_abstract
{
	protected $entryCache = array();
	protected $blogCache = array();

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
	 * Fetches parent ID
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		$entry = $this->_fetchEntry( $where['meta_id'] );
		
		return intval( $entry['blog_id'] );
	}
	
	/**
	 * Fetches permission data
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		if ( !empty( $where['meta_id'] ) )
		{
			$entry = $this->_fetchEntry( $where['meta_id'] );
			$blog  = $this->_fetchBlog( $entry['blog_id'] );
		}
		elseif ( !empty( $where['meta_parent_id'] ) )
		{
			$blog = $this->_fetchBlog( $where['meta_parent_id'] );
		}
		
		if ( $blog['blog_view_level'] == 'public' )
		{
			return '*';
		}
		
		return NULL;
	}
	
	/**
	 * Basic permission check
	 * 
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
		
		if ( !empty( $where['meta_id'] ) )
		{
			$entry = $this->_fetchEntry( $where['meta_id'] );
			$blog  = $this->_fetchBlog( $entry['blog_id'] );
		}
		elseif ( !empty( $where['meta_parent_id'] ) )
		{
			$blog = $this->_fetchBlog( $where['meta_parent_id'] );
		}
		
		if ( ! ipsRegistry::isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass( 'blogFunctions', new $classToLoad($this->registry) );
		}
		
		$blog = $this->registry->getClass('blogFunctions')->buildBlogData( $blog );
		
		switch ( $what )
		{
			case 'create':
				return true;
			break;
			case 'add':
				if ( $blog['blog_type'] == 'local' && $this->registry->getClass('blogFunctions')->allowPublish( $blog ) )
				{
		            return true;
		        }
			break;
			case 'edit':
			case 'remove':
				if ( $this->registry->getClass('blogFunctions')->allowEditEntry( $blog ) )
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
		$entry = $this->_fetchEntry( $where['meta_id'] );
		
		return ( $entry['entry_status'] == 'published' ) ? 1 : 0;
	}
	
	/**
	 * Fetch Blog Entry
	 *
	 * @param	int		Entry ID
	 * @return	array	Entry Data
	 */
	private function _fetchEntry( $id )
	{
		$id = intval( $id );
		if ( ! isset( $this->entryCache[ $id ] ) )
		{
			$this->entryCache[ $id ] = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$id}" ) );
		}
		
		return $this->entryCache[ $id ];
	}
	
	/**
	 * Fetch Blog
	 *
	 * @param	int		Blog ID
	 * @return	array	Blog Data
	 */
	private function _fetchBlog( $id )
	{
		$id = intval( $id );
		if ( ! isset( $this->blogCache[ $id ] ) )
		{
			$this->blogCache[ $id ] = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'blog_blogs', 'where' => "blog_id={$id}" ) );
		}
		
		return $this->blogCache[ $id ];
	}
}