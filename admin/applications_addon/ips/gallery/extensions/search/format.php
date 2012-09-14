<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Formats gallery search results
 * Last Updated: $Date: 2011-10-20 10:51:26 -0400 (Thu, 20 Oct 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9643 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_gallery extends search_format
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_gallery', 'public_gallery_four' ), 'gallery' );
		
		/* Setup glib */
		if( !ipsRegistry::isClassLoaded( 'gallery' ) )
		{
			/* Gallery Object */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		$this->_images = $registry->gallery->helper('image');
		$this->_albums = $registry->gallery->helper('albums');
	}
	
	/**
	 * Parse search results
	 *
	 * @param	array 	$r				Search result
	 * @return	@e array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		/* INIT */
		$search_term = IPSSearchRegistry::get('in.clean_search_term');
		
		/* Make sure we have data */
		if  ( ! is_array( $rows ) OR ! count( $rows ) )
		{
			return array();
		}
		
		/* Go through and build HTML */
		foreach( $rows as $id => $data )
		{
			/* Format content */
			list( $html, $sub ) = $this->formatContent( $data );
			
			if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
			{
				$data['content_title'] = IPSText::searchHighlight( $data['content_title'], $search_term );
			}
			else
			{
				$data['content'] = IPSText::searchHighlight( $data['content'], $search_term );
			}
			
			$results[ $id ] = array( 'html' => $html, 'app' => $data['app'], 'type' => $data['type'], 'sub' => $sub );
		}
		
		return $results;
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @param	array   $search_row		Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $data )
	{
		$data['misc']	= unserialize( $data['misc'] );		
		$data['thumb']	= ( ! isset( $data['thumb'] ) ) ? $this->_images->makeImageLink( $data, array( 'type' => 'thumb' ) ) : $data['thumb'];
		
		switch ( IPSSearchRegistry::get('gallery.searchInKey') )
		{
			case 'images':
				$template = 'galleryImageSearchResult';
			break;
			case 'albums':
				$template = 'galleryAlbumSearchResult';
			break;
			default:
				$template = 'galleryCommentSearchResult';
			break;
		}
		
		return array( ipsRegistry::getClass('output')->getTemplate('gallery_user')->$template( $data, IPSSearchRegistry::get('opt.searchTitleOnly') ), 0 );
	}

	/**
	 * Return the output for the followed content results
	 *
	 * @param	array		$results		Array of results to show
	 * @param	array		$followData		Meta data from follow/like system
	 * @return	@e string
	 */
	public function parseFollowedContentOutput( $results, $followData )
	{
		/* Images? */
		if( IPSSearchRegistry::get('in.followContentType') == 'images' )
		{
			IPSSearchRegistry::set('gallery.searchInKey', 'images');
			
			if( count($results) )
			{
				$results = $this->_processImageResults( $results );
				
				/* Merge in follow data */
				if ( count($followData) )
				{
					foreach( $followData as $_follow )
					{
						$results[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
					}
				}
			}
			
			return $this->registry->output->getTemplate('gallery_user')->searchResultsAsGallery( $this->parseAndFetchHtmlBlocks( $results ) );
		}
		/* Or albums? */
		else
		{
			IPSSearchRegistry::set('gallery.searchInKey', 'albums');
			
			$results = $this->_processAlbumResults( $results );
			
			if( count($results) )
			{
				/* Merge in follow data */
				if ( count($followData) )
				{
					foreach( $followData as $_follow )
					{
						$results[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
					}
				}
			}
            
			return $this->registry->output->getTemplate('gallery_user')->searchResultsAsGallery( $this->parseAndFetchHtmlBlocks( $results ) );
		}
	}

	/**
	 * Decides which type of search this was
	 *
	 * @return @e array
	 */
	public function processResults( $ids )
	{
		$this->templates = array( 'group' => 'gallery_user', 'template' => 'searchResultsAsGallery' );
		
		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{	
			return $this->_processImageResults( $ids );
		}
		else if ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{	
			return $this->_processAlbumResults( $ids );
		}
		else
		{
			return $this->_processCommentResults( $ids );
		}
	}
	
	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @return @e array
	 */
	public function _processAlbumResults( $ids )
	{
		/* INIT */
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts          = IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$sortKey			= '';
		$sortType			= '';
		$rows				= array();
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Sorting */
			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey  = 'date';
					$sortType = 'numerical';
				break;
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $order_dir );
			IPSSearch::$ast = $sortType;
			
			/* Fetch data */
			$albums = $this->_albums->fetchAlbumsByFilters( array( 'isViewable'        => true,
													 		   	   'album_id'          => $ids,
															   	   'sortKey'		   => $sortKey,
													 		       'sortOrder'		   => $sort_order )  );
			
			/* Sort */
			if ( count( $albums ) )
			{
				foreach( $albums as $id => $row )
				{
					/* Got author but no member data? */
					if ( ! empty( $row['album_owner_id'] ) )
					{
						$members[ $row['album_owner_id'] ] = $row['album_owner_id'];
					}
				}
			}
			
			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $albums as $id => $r )
				{
					if ( ! empty( $r['album_owner_id'] ) AND isset( $mems[ $r['album_owner_id'] ] ) )
					{
						$albums[ $id ] = array_merge( $albums[ $id ], IPSMember::buildDisplayData( $mems[ $r['album_owner_id'] ], array( 'reputation' => 0, 'warn' => 0 ) ) );
					}
				}
			}
		}

		return $albums;
	}
	
	public function _processCommentResults( $ids )
	{
		/* INIT */
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts          = IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$sortKey			= '';
		$sortType			= '';
		$rows				= array();
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Sorting */
			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey  = 'post_date';
					$sortType = 'numerical';
				break;
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $order_dir );
			IPSSearch::$ast = $sortType;
			
			/* Fetch data */
			$this->DB->build( array( 
									'select'   => "c.*",
									'from'	   => array( 'gallery_comments' => 'c' ),
		 							'where'	   => 'c.pid IN( ' . implode( ',', $ids ) . ')',
									'add_join' => array( array( 'select' => 'g.*',
																'from'   => array( 'gallery_images' => 'g' ),
																'where'  => "g.id=c.img_id",
																'type'   => 'left' ),
														array( 'select' => 'a.*',
																'from'   => array( 'gallery_albums_main' => 'a' ),
																'where'  => "a.album_id=g.img_album_id",
																'type'   => 'left' ),
														array(  'select' => 'm.members_display_name, m.member_group_id, m.mgroup_others, m.members_seo_name',
																'from'   => array( 'members' => 'm' ),
																'where'  => "m.member_id=c.author_id",
																'type'   => 'left' ) ) ) );
	
			/* Grab data */
			$this->DB->execute();
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[] = $row;
			}

			/* Sort */
			if ( count( $_rows ) )
			{
				usort( $_rows, array("IPSSearch", "usort") );
		
				foreach( $_rows as $id => $row )
				{				
					/* Got author but no member data? */
					if ( ! empty( $row['author_id'] ) )
					{
						$members[ $row['author_id'] ] = $row['author_id'];
					}
					
					$results[ $row['pid'] ] = $this->genericizeResults( $row );
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['author_id'] ) AND isset( $mems[ $r['author_id'] ] ) )
					{
						$mems[ $r['author_id'] ]['m_posts'] = $mems[ $r['author_id'] ]['posts'];
						
						$_mem = IPSMember::buildDisplayData( $mems[ $r['author_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$results[ $id ] = array_merge( $results[ $id ], $_mem );
					}
				}
			}
		}

		return $results;
	}
	
	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @access public
	 * @return array
	 */
	public function _processImageResults( $ids )
	{
		/* INIT */
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts          = IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$sortKey			= '';
		$sortType			= '';
		$rows				= array();
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Sorting */
			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey  = 'idate';
					$sortType = 'numerical';
				break;
				case 'title':
					$sortKey  = 'caption';
					$sortType = 'string';
				break;
				case 'views':
					$sortKey	= 'views';
					$sortType	= 'numerical';
				break;
				case 'comments':
					$sortKey	= 'comments';
					$sortType	= 'numerical';
				break;
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $order_dir );
			IPSSearch::$ast = $sortType;
			
			$_post_joins[] = $this->registry->galleryTags->getCacheJoin( array( 'meta_id_field' => 'g.id' ) );
			
			/* Fetch data */
			$this->DB->build( array( 'select'   => "g.*",
									 'from'	   => array( 'gallery_images' => 'g' ),
		 							 'where'	   => 'g.id IN( ' . implode( ',', $ids ) . ')',
									 'add_join' => array_merge( $_post_joins, array( array( 'select' => 'a.*',
																						    'from'   => array( 'gallery_albums_main' => 'a' ),
																						    'where'  => "a.album_id=g.img_album_id",
																						    'type'   => 'left' ),
																				     array( 'select' => 'm.members_display_name, m.member_group_id, m.mgroup_others, m.members_seo_name',
																						    'from'   => array( 'members' => 'm' ),
																						    'where'  => "m.member_id=g.member_id",
																						    'type'   => 'left' ) ) ) ) );
	
			/* Grab data */
			$this->DB->execute();
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[] = $row;
			}

			/* Sort */
			if ( count( $_rows ) )
			{
				usort( $_rows, array("IPSSearch", "usort") );
		
				foreach( $_rows as $id => $row )
				{
					/* Got author but no member data? */
					if ( ! empty( $row['member_id'] ) )
					{
						$members[ $row['member_id'] ] = $row['member_id'];
					}
					
					/* Notes */
					$row['image_notes']			= unserialize( $row['image_notes'] );
					$row['_image_notes_count']	= is_array( $row['image_notes'] ) ? count( $row['image_notes'] ) : 0;
					$row['caption_seo']			= $row['caption_seo'] ? $row['caption_seo'] : IPSText::makeSeoTitle( $row['caption'] );
					
					/* Tags */
					if ( ! empty( $row['tag_cache_key'] ) )
					{
						$row['tags'] = $this->registry->galleryTags->formatCacheJoinData( $row );
					}
					
					$results[ $row['id'] ] = $this->genericizeResults( $row );				
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['member_id'] ) AND isset( $mems[ $r['member_id'] ] ) )
					{
						$mems[ $r['member_id'] ]['m_posts'] = $mems[ $r['member_id'] ]['posts'];
						
						$_mem = IPSMember::buildDisplayData( $mems[ $r['member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$results[ $id ] = array_merge( $results[ $id ], $_mem );
					}
				}
			}
		}

		return $results;
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 */
	public function genericizeResults( $r )
	{
		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{
			$r['app']           = 'gallery';
			$r['content']       = $r['description'];
			$r['content_title'] = $r['caption'];
			$r['updated']       = $r['idate'];
			$r['type_2']        = 'img';
			$r['type_id_2']     = $r['id'];
			$r['misc']          = serialize( array( 'directory'			=> $r['directory'],
													'masked_file_name'	=> $r['masked_file_name'],
													'thumbnail'			=> $r['thumbnail'],
											)		);
		}
		elseif ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{
			// Nothing to see here yet
		}
		else
		{
			$r['app']           = 'gallery';
			$r['content']       = $r['comment'];
			$r['content_title'] = $r['caption'];
			$r['updated']       = $r['post_date'];
			$r['type_2']        = 'comment';
			$r['type_id_2']     = $r['id'];
			$r['misc']          = serialize( array( 'directory'			=> $r['directory'],
													'masked_file_name'	=> $r['masked_file_name'],
													'thumbnail'			=> $r['thumbnail'],
											)		);
		}

		return $r;
	}
}