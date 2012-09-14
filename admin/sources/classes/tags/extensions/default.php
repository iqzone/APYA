<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Tagging: Default extensions file
 * Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		24 Feb 2011
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_default extends classes_tag_abstract
{
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Set up defaults */
		$this->registry   =  ipsRegistry::instance();
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		
	}
	
	/**
	 * DEFAULT: returns nothing and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		return 0;
	}
	
	/**
	 * DEFAULT: returns nothing and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		return '*';
	}
	
	/**
	 * Basic permission check
	 * @param	string	$what (add/remove/edit)
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		return false;
	}
	
	/**
	 * DEFAULT: returns true and should be defined in your own class
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		return 1;
	}
}