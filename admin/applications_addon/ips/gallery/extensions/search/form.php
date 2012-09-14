<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Returns HTML for the form (optional class, not required)
 * Last Updated: $Date: 2011-06-14 16:40:48 -0400 (Tue, 14 Jun 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9041 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_gallery
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
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
		$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_gallery', 'public_gallery_four' ), 'gallery' );
	}
	
	/**
	 * Return sort drop down
	 * 
	 * @return	@e array
	 */
	public function fetchSortDropDown()
	{
		$array = array( 
						'images' => array( 
											'date'		=> $this->lang->words['sort_date'],
					    					'title'		=> $this->lang->words['sort_caption'],
					    					'views'		=> $this->lang->words['sort_views'],
					    					'comments'	=> $this->lang->words['sort_comments'],
										),
					    'comments'  => array( 
					    						'date'  => $this->lang->words['s_search_type_0'],
					    					),
					    'albums'    => array( 
					    						'date'  => $this->lang->words['s_search_type_0'],
					    					)
					);
		
		return $array;
	}
	
	/**
	 * Return sort in
	 * Optional function to allow apps to define searchable 'sub-apps'.
	 * 
	 * @return	@e array
	 */
	public function fetchSortIn()
	{
		$array = array( 
						array( 'images',	$this->lang->words['advsearch_images'] ),
					    array( 'comments',	$this->lang->words['advsearch_comments'] ),
					    array( 'albums',	$this->lang->words['advsearch_albums'] )  
					);
		
		return $array;
	}
	
	/**
	 * Retuns the html for displaying the extra gallery search filters
	 *
	 * @return	@e string	Filter HTML
	 */
	public function getHtml()
	{
		$dropdown = $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isViewable' => true, 'album_is_global' => 1 ) );
		
		return array( 'title' => IPSLIb::getAppTitle('gallery'), 'html' => ipsRegistry::getClass('output')->getTemplate('gallery_user')->galleryAdvancedSearchFilters( $dropdown ) );
	}
}