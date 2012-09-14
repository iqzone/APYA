<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Tagging: Psuedo class for blog RSS imports
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

class tags_blog_rssimport extends classes_tag_abstract
{
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
		return intval( $where['meta_id'] );
	}
	
	/**
	 * Fetches permission data
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		return NULL;
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		return FALSE;
	}

}