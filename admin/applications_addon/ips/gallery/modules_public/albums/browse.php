<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Home Listing (matt mecham)
 * Last Updated: $LastChangedDate: 2011-11-01 09:19:21 -0400 (Tue, 01 Nov 2011) $
 * </pre>
 *
 * @author		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9728 $
 *
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_browse extends ipsCommand
{
	/**
	 * Generated Output
	 *
	 * @access	private
	 * @var		string
	 */
	private $output;	
	
	/**
	 * Album helper
	 * 
	 * @access	private
	 * @var		object
	 */
	private $albums;
	
	/**
	 * Image helper
	 * 
	 * @access	private
	 * @var		object
	 */
	private $images;
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */ 
	public function doExecute( ipsRegistry $registry )
	{
		/* Set up class vars */
		$this->albums = $this->registry->gallery->helper('albums');
		$this->images = $this->registry->gallery->helper('image');
		
		$output = $this->_getImageListing();
		
		/* Sort it out! */
		$this->output = $output;
		
		//----------------------------
		// Output
		//----------------------------
				
		$this->registry->getClass('output')->setTitle( $this->title );
		$this->registry->getClass('output')->addContent( $this->output );
		
		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}		

		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Get the image listing
	 *
	 * @access protected
	 * @return	html
	 */
	protected function _getImageListing()
	{
		/* Init */
		$albumId    = intval( $this->request['albumId'] );
		$start      = intval( $this->request['st'] );
		$album      = array( 'album_id' => 0, 'album_name_seo' => true );
		$sortKey    = ( ! empty( $this->request['sort_key'] ) )   ? $this->request['sort_key']   : 'date';
		$sortOrder  = ( ! empty( $this->request['sort_order'] ) ) ? $this->request['sort_order'] : 'desc';
		$filterKey  = ( ! empty( $this->request['filter_key'] ) ) ? $this->request['filter_key'] : 'all';
		$total      = 0;
		$pages      = false;
		$parents    = array();
		
		if ( $albumId )
		{
			/* Perm check performed inside fetchAlbum */
			$album   = $this->albums->fetchAlbum( $albumId );
			$parents = $this->albums->fetchAlbumParents( $album );
		}
		
		$filters = array( 'getTotalCount'    => true,
						  'getChildrenCount' => true,
						  'isViewable'       => true,
						  'sortKey'          => $sortKey,
						  'sortOrder'        => $sortOrder,
						  'limit'            => GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE,
						  'offset'           => $start );
		
		if ( $filterKey == 'all' )
		{
			if ( $albumId )
			{
				$filters['album_parent_id'] = $albumId;
			}
		}
		elseif ( $filterKey == 'global' )
		{
			$filters['album_is_global'] = 1;
			$filters['album_parent_id'] = $albumId;
		}
		elseif ( $filterKey == 'member' )
		{
			$filters['album_is_global'] = 0;
		}
		
		/* Fetch the goddamned albums */
		$children = $this->albums->fetchAlbumsByFilters( $filters );
		$total    = $this->albums->getCount();
		
		/* Fetch pagination */
		if ( GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE < $total )
		{
			$pages = $this->registry->output->generatePagination(  array( 'totalItems'		  => $total,
																		  'itemsPerPage'	  => GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE,
																		  'currentStartValue' => $start,
																		  'seoTitle'		  => $album['album_name_seo'],
																		  'seoTemplate'		  => ( $albumId ) ? 'browsealbum' : 'browsealbumroot',
																		  'baseUrl'			  => "app=gallery&amp;browseAlbum={$album['album_id']}&amp;sort_key={$sortKey}&amp;sort_order={$sortOrder}&amp;filter_key={$filterKey}" ) );
			
		}
	
		$this->title = ( $albumId ) ? $album['album_name'] . ' - ' . $this->lang->words['gbutton_browse'] . ' - ' . IPSLIb::getAppTitle('gallery')  . ' - ' . $this->settings['board_name'] : $this->lang->words['gbutton_browse'] . ' - ' . IPSLIb::getAppTitle('gallery') .' - ' . $this->settings['board_name'];
		$this->nav[] = array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		$this->nav[] = array( $this->lang->words['gbutton_browse'], 'app=gallery&amp;browseAlbum=0', true, 'browsealbumroot' );
		
		/* Add in the old 'rents */
		if ( count( $parents ) )
		{
			$parents = array_reverse( $parents, true );
			
			foreach( $parents as $id => $data )
			{
				$this->nav[] = array( $data['album_name'], 'app=gallery&amp;browseAlbum=' . $data['album_id'], $data['album_name_seo'], 'browsealbum' );	
			}
		}
		
		if ( $albumId )
		{
			$this->nav[] = array( $album['album_name'], 'app=gallery&amp;browseAlbum=' . $album['album_id'], $album['album_name_seo'], 'browsealbum' );
		}
		
		/* Add Meta Content */
		if ( isset( $album['album_name'] ) )
		{
			$this->registry->output->addMetaTag( 'keywords', $album['album_name'], TRUE );
		}
		
		if ( $album['album_description'] )
		{
			$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", $album['album_description'] ) ), FALSE, 155 );
		}
		
		/* Get Stats */
		if( !is_array( $this->caches['gallery_stats'] ) OR !count( $this->caches['gallery_stats'] ) )
		{
			$this->cache->getCache('gallery_stats');
		}
		$stats = array(
						'images'		=> $this->caches['gallery_stats']['total_images_visible'],
						'diskspace'		=> $this->caches['gallery_stats']['total_diskspace'],
						'comments'		=> $this->caches['gallery_stats']['total_comments_visible'],
						'albums'		=> $this->caches['gallery_stats']['total_albums'],
					);
		
		/* Return */
		return $this->registry->output->getTemplate('gallery_home')->browse( $children, $album, $pages, $stats );
	}
}