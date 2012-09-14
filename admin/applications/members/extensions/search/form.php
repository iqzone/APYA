<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Returns HTML for the form (optional class, not required)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_members
{
	/**
	 * Construct
	 *
	 */
	public function __construct()
	{
		/* Make object */
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
	 * Return sort drop down
	 * 
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array( 'members'  => array( 'date'  => $this->lang->words['s_search_type_0'],
					    					 'title' => $this->lang->words['forum_sort_title'] ),
					    'comments' => array( 'date'  => $this->lang->words['s_search_sort_creation_date'] ) );
		
		return $array;
	}
	
	/**
	 * Return sort in
	 * Optional function to allow apps to define searchable 'sub-apps'.
	 * 
	 *
	 * @return	array
	 */
	public function fetchSortIn()
	{
		if( $this->request['do'] == 'user_activity' )
		{
			$array = array();
		}
		else
		{
			$array = array( 0 => array( 'members', $this->lang->words['s_msin_members'] ),
							1 => array( 'comments', $this->lang->words['s_msin_comments'] ) );
		}
		
		return $array;
	}
	
}
