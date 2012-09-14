<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Returns HTML for the blog (optional class, not required)
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_blog
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
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
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_blog' ), 'blog' );
	}
	
	/**
	 * Return sort drop down
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array(
						'entries'	=> array( 
												'date'		=> $this->lang->words['blog_entry_date'],
					    						'title'		=> $this->lang->words['entry_title'],
					    						'comments'	=> $this->lang->words['blog_comments']
					    					),
					    'comments'	=> array(
					    						'date'  => $this->lang->words['s_search_type_0'],
					    					)
					);
		
		return $array;
	}
	
	/**
	 * Return sort in
	 * Optional function to allow apps to define searchable 'sub-apps'.
	 * 
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchSortIn()
	{
		/* This blocks 'comments' showing as an option when doing a tag search */
		if( $this->request['search_tags'] )
		{
			return false;
		}
		
		$array = array( 
						array( 'entries',	$this->lang->words['portal_blog_entries'] ),
					    array( 'comments',	$this->lang->words['sort_by_numcomments'] ) 
					);
		
		return $array;
	}
}
