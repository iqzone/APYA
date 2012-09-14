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

class search_form_forums
{
	/**
	 * Construct
	 * 
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
	}
	
	/**
	 * Return sort drop down
	 *
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array( 'date'  => $this->lang->words['s_search_type_0'],
					    'title' => $this->lang->words['forum_sort_title'],
					    'posts' => $this->lang->words['forum_sort_posts'],
					    'views' => $this->lang->words['forum_sort_views'] );
		
		return $array;
	}
	
	/**
	 * Retuns the html for displaying the forum category filter on the advanced search page
	 *
	 * @return	string	Filter HTML
	 */
	public function getHtml()
	{
		/* Make sure class_forums is setup */
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			ipsRegistry::setClass( 'class_forums', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		ipsRegistry::getClass('class_forums')->strip_invisible = 1;
		ipsRegistry::getClass('class_forums')->forumsInit();
		
		/* Got any archived content? */
		$canSearchArchives = false;
		
		if ( $this->settings['archive_on'] )
		{
			$canSearchArchives = true;
			
			if ( ( $this->settings['search_method'] == 'traditional' || $this->settings['search_method'] == 'sql' ) && $this->settings['archive_remote_sql_database'] )
			{
				$canSearchArchives = false;
			}
		}
		
		$topic = NULL;
		if ( $this->request['cType'] == 'topic' )
		{
			$this->request['cId'] = intval( $this->request['cId'] );
			$topic = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid={$this->request['cId']}" ) );
		}
		
		return array(
			'title'	=> IPSLib::getAppTitle('forums'),
			'html'	=> ipsRegistry::getClass('output')->getTemplate('search')->forumAdvancedSearchFilters(
				ipsRegistry::getClass( 'class_forums' )->buildForumJump( 0, 1, 0, ( isset( $this->request['cId'] ) and $this->request['cType'] == 'forum' ) ? array( $this->request['cId'] ) : array() ),
				$canSearchArchives,
				$topic
				) );
	}
}