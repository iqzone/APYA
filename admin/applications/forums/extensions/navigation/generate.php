<?php
/**
 * @file		generate.php 	Navigation plugin: attachments
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		3/8/2011
 * $LastChangedDate: 2011-03-31 06:17:44 -0400 (Thu, 31 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8229 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_core_attachments
 * @brief		Provide ability to share attachments via editor
 */
class navigation_forums
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
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			=  $this->registry->class_localization;
		
		if ( ! $this->registry->isClassLoaded('class_forums' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			$this->registry->getClass('class_forums')->strip_invisible = 1;
			$this->registry->getClass('class_forums')->forumsInit();
		}
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTabName()
	{ 
		return IPSLib::getAppTitle( 'forums' );
	}
	
	/**
	 * Returns navigation data
	 * @return	array	array( array( 0 => array( 'title' => 'x', 'url' => 'x' ) ) );
	 */
	public function getNavigationData()
	{
		$blocks = array();
		$links  = $this->_getData();
			
		/* Add to blocks */
		$blocks[] = array( 'title' => '', 'links' => $links );
		
		return $blocks;
	}
	
	/**
	 * Fetches forum jump data
	 *
	 * @return	string
	 */
	private function _getData()
	{
		$depth_guide = 0;
		$links		 = array();
		
		if( is_array($this->registry->class_forums->forum_cache['root'] ) AND count($this->registry->class_forums->forum_cache['root'] ) )
		{
			foreach($this->registry->class_forums->forum_cache['root'] as $forum_data )
			{
				if ( $forum_data['sub_can_post'] or ( isset($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) AND is_array($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) AND count($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) ) )
				{
					$forum_data['redirect_on'] = isset( $forum_data['redirect_on'] ) ? $forum_data['redirect_on'] : 0;
					
					if ( $forum_data['redirect_on'] == 1 )
					{
						continue;
					}
					
					$links[] = array( 'important' => true, 'depth' => $depth_guide, 'title' => $forum_data['name'], 'url' => $this->registry->output->buildSEOUrl( 'showforum=' . $forum_data['id'], 'public', $forum_data['name_seo'], 'showforum' ) );
					
					if ( isset($this->registry->class_forums->forum_cache[ $forum_data['id'] ]) AND is_array($this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) )
					{
						$depth_guide++;
						
						foreach($this->registry->class_forums->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							if ( $forum_data['redirect_on'] == 1 )
							{
								continue;
							}						
						
							$links[] = array( 'depth' => $depth_guide, 'title' => $forum_data['name'], 'url' => $this->registry->output->buildSEOUrl( 'showforum=' . $forum_data['id'], 'public', $forum_data['name_seo'], 'showforum' ) );
					
							$links = $this->_getDataRecursively( $forum_data['id'], $links, $depth_guide );			
						}
						
						$depth_guide--;
					}
				}
			}
		}
		
		return $links;
	}
	
	/**
	 * Internal helper function for forumsForumJump
	 *
	 * @param	integer	$root_id
	 * @param	array	$links
	 * @param	string	$depth_guide
	 * @return	string
	 */
	private function _getDataRecursively( $root_id, $links=array(), $depth_guide=0 )
	{
		if ( isset( $this->registry->class_forums->forum_cache[ $root_id ] ) AND is_array($this->registry->class_forums->forum_cache[ $root_id ] ) )
		{
			$depth_guide++;
			
			foreach($this->registry->class_forums->forum_cache[ $root_id ] as $forum_data )
			{
				if ( $forum_data['redirect_on'] == 1 )
				{
					continue;
				}
				
				$links[] = array( 'depth' => $depth_guide, 'title' => $forum_data['name'], 'url' => $this->registry->output->buildSEOUrl( 'showforum=' . $forum_data['id'], 'public', $forum_data['name_seo'], 'showforum' ) );
				
				$links = $this->_getDataRecursively( $forum_data['id'], $links, $depth_guide );
			}
		}
		
		
		return $links;
	}
	
}