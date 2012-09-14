<?php
/**
 * @file		home.php 	Home listing
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2011-10-31 13:03:49 -0400 (Mon, 31 Oct 2011) $
 * @version		v4.2.1
 * $Revision: 9714 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_gallery_albums_home
 * @brief		Home listing
 */
class public_gallery_albums_home extends ipsCommand
{
	/**
	 * Generated Output
	 *
	 * @var		$output
	 */
	private $output;	
	
	/**
	 * Album helper
	 * 
	 * @var		$albums
	 */
	private $albums;
	
	/**
	 * Image helper
	 * 
	 * @var		$images
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
		$start        = intval( $this->request['st'] );
		$pages        = '';
		
		/* Get data (sortKey, sortOrder, offset, limit, unixCutOff) */
		define( 'GALLERY_A_YEAR_AGO', time() - ( 86400 * 365 ) );
		
		/* Fetch random medium image */
		$feature = $this->images->fetchFeatured();
		
		if ( ! empty( $feature['description'] ) )
		{
			$feature['description'] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripAllTags( $feature['description'] ), 300 );
		}
			
		if ( $feature['id'] )
		{
			$feature['tag'] = $this->images->makeImageLink( $feature, array( 'type' => 'medium', 'link-type' => 'page' ) );
		}
		
		/* Fetch 30 recent albums */
		$albums = $this->albums->fetchAlbumsByFilters(  array( 'offset' => $start, 'limit' => 30, 'getTotalCount' => true, 'sortKey' => 'date', 'sortOrder' => 'desc', 'isViewable' => 1  ) );
		$count  = $this->albums->getCount();
		
		if ( $count > 30 )
		{
			$pages = $this->registry->output->generatePagination(  array(   'totalItems'		=> $count,
																   			'itemsPerPage'		=> 30,
																   			'currentStartValue'	=> $start,
																   			'seoTitle'			=> true,
																   			'seoTemplate'		=> 'app=gallery',
																   			'baseUrl'			=> 'app=gallery' ) );
		}
				
		/* Sidebar stuff */
		$sidebar = array( 'globalAlbums'   => $this->_sidebarGlobalAlbums(),
						  'recentImages'   => $this->_sidebarRecentImages(),
						  'activeAlbums'   => $this->_sidebarActiveAlbums(),
						  'recentComments' => $this->_sidebarRecentComments() );
		
		/* Get Stats */
		if( !is_array( $this->caches['gallery_stats'] ) OR !count( $this->caches['gallery_stats'] ) )
		{
			$this->cache->getCache('gallery_stats');
		}
		
		$stats = array( 'images'		=> $this->caches['gallery_stats']['total_images_visible'],
						'diskspace'		=> $this->caches['gallery_stats']['total_diskspace'],
						'comments'		=> $this->caches['gallery_stats']['total_comments_visible'],
						'albums'		=> $this->caches['gallery_stats']['total_albums'] );
		
		/* Sort it out! */
		$this->output .= $this->registry->output->getTemplate('gallery_home')->home( $feature, $sidebar, $albums, $pages, $stats );
		
		$this->title = IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'];
		$this->nav[] = array( IPSLIb::getAppTitle('gallery') );
				
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
		
		/* Output!? */	
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Fetch sidebar: active albums
	 *
	 * @return	@e array
	 */
	protected function _sidebarActiveAlbums( $limit=10 )
	{
		/* Init vars */
		$albums      = array();
		$final       = array();
		$c           = 0;
		
		/* Fetch class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
		$sessions    = new $classToLoad( $this->registry );
		
		/* Get users */
		$users = $sessions->getUsersIn('gallery');
		
		/* Now parse */
		foreach( $users['rows'] as $type => $data )
		{
			foreach( $users['rows'][ $type ] as $id => $session )
			{
				if ( $session['location_2_id'] AND $session['location_2_type'] == 'album' )
				{
					$albums[ $session['location_2_id'] ][] = $session;
				}
			}
		}
		
		/* Load albums */
		if ( count( $albums ) )
		{
			$loadedAlbums = $this->albums->fetchAlbumsByFilters( array( 'album_id' => array_keys($albums), 'isViewable' => true ) );
		
			/* Final run through */
			foreach( $albums as $albumId => $data )
			{
				foreach( $data as $member )
				{
					if ( ! array_key_exists( $albumId, $final ) )
					{
						if ( $c > $limit )
						{
							break 2;
						}
						
						if ( ! isset( $loadedAlbums[ $albumId ] ) )
						{
							continue;
						}
						
						$final[ $albumId ] = array( 'album' => $loadedAlbums[ $albumId ], 'users' => array(), 'others' => 0 );
						
						$c++;
					}
					
					if ( $member['member_id'] AND count( $final[ $albumId ]['users'] ) < 20 )
					{
						$final[ $albumId ]['users'][] = $member['parsedMemberName'];
					}
					else
					{
						$final[ $albumId ]['others']++;
					}
				}
			}
		}
		
		return $final;
	}
	
	/**
	 * Fetch sidebar: global albums
	 *
	 * @return	@e array
	 */
	protected function _sidebarGlobalAlbums()
	{
		return $this->albums->fetchAlbumsByFilters( array( 'album_parent_id' => 0, 'isViewable' => true, 'getChildren' => 'global', 'album_is_global' => 1, 'getGlobalAlbumLatestImgs' => 5 ) );
	}
	
	/**
	 * Fetch sidebar: Recent images
	 *
	 * @return	@e array
	 */
	protected function _sidebarRecentImages()
	{
		return $this->images->fetchImages( $this->memberData['member_id'], array( 'unixCutOff' => GALLERY_A_YEAR_AGO, 'sortKey' => 'date', 'sortOrder' => 'desc', 'offset' => 0, 'limit' => 30 ) );
	}
	
	/**
	 * Fetch sidebar:Recent Comments
	 *
	 * @return	@e array
	 */
	protected function _sidebarRecentComments()
	{
		return $this->images->fetchImages( $this->memberData['member_id'], array( 'getLatestComment' => true, 'hasComments' => true, 'sortKey' => 'lastcomment', 'sortOrder' => 'desc', 'offset' => 0, 'limit' => 5 ) );
	}
}