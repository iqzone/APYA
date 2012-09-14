<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Category Listing
 * Last Updated: $LastChangedDate: 2011-12-15 07:33:40 -0500 (Thu, 15 Dec 2011) $
 * </pre>
 *
 * @author		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10005 $
 *
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_album extends ipsCommand
{
	/**
	 * Array of navigation bits
	 * 
	 * @var array
	 */
	private $nav = array();
	
	/**
	 * Document title
	 * 
	 * @var string
	 */
	private $title = '';
	
	/**
	 * Album helper
	 * 
	 * @var		object
	 */
	private $albums;
	
	/**
	 * Image helper
	 * 
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
		$albumId = intval( $this->request['album'] );
		
		/* Set up class vars */
		$this->albums  = $this->registry->gallery->helper('albums');
		$this->images  = $this->registry->gallery->helper('image');
		
		/* Favorites */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_follow = classes_like::bootstrap( 'gallery', 'albums' );
		
		/* What the hell are we doing? */
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$output = $this->_showAlbum( $albumId );
			break;
			case 'delete':
				$this->_delete( $albumId );
			break;
		}

		//----------------------------
		// Output
		//----------------------------
				
		$this->registry->getClass('output')->setTitle( $this->title );
		$this->registry->getClass('output')->addContent( $output );
		
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
	 * Deletes an album
	 * 
	 * @param	int			$albumId
	 * @return 	redirect back to album
	 */
	protected function _delete( $albumId )
	{
		$move_to_album_id = intval( $this->request['move_to_album_id'] );
		$moveToAlbum      = array();
		$doDelete		  = intval( $this->request['doDelete'] );
		$album            = $this->albums->fetchAlbumsById( $albumId );
		
		/* Form hash check */
		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->output->showError( 'no_permission', '1-albums-albums-delete-0', null, null, 403 );
		}
	
		/* Do we have permission to remove the album? */
		if ( ! $this->albums->isOwner( $album ) AND ! $this->albums->canModerate( $album ) )
		{
			$this->registry->output->showError( $this->lang->words['4_move_fail'], '1-albums-albums-delete-1', null, null, 403 );
		}
				
		/* Are we deleting the images or moving them? */
		if ( $move_to_album_id && ! $doDelete )
		{
			$moveToAlbum = $this->albums->fetchAlbum( $move_to_album_id );
			
			if ( ! $this->albums->isUploadable( $moveToAlbum ) )
			{
				$this->registry->output->showError( $this->lang->words['4_move_fail'], '1-albums-albums-delete-2', null, null, 403 );
			}
		}
		
		/* Fetch parents of album before its removed */
		$parents = $this->albums->fetchAlbumParents( $albumId );
		
		/* Delete album */
		$result = $this->registry->gallery->helper('moderate')->deleteAlbum( $albumId, $moveToAlbum );
		
		/* Return back to parent album or root or album we moved to */
		$parent = array_pop( $parents );
		
		if ( !empty($parent['album_id']) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $parent['album_id'], $parent['album_name_seo'], false, 'viewalbum' );
		}
		elseif ( $moveToAlbum['album_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&amp;album=' . $moveToAlbum['album_id'], $moveToAlbum['album_name_seo'], false, 'viewalbum' );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery', 'false', false, 'app=gallery' );
		}
	}
	
	/**
	 * Display an album
	 * 
	 * @param	int			$albumId
	 * @return 	string		H T M L
	 */
	protected function _showAlbum( $albumId )
	{
		/* First stop, fetch the album */
		$album = $this->albums->fetchAlbumsById( $albumId );
		
		/* got access? */
		$this->albums->isViewable( $album, true );
		
		/* Start session */
		session_start();
		
		/* Init vars */
		$display  = ( !empty($this->request['display']) && in_array($this->request['display'], array('overview','detail')) ) ? $this->request['display'] : ( $album['album_detail_default'] ? 'detail' : 'overview' );
		$owner    = array();
		
		/* Set display method */
		if ( !empty( $this->request['display'] ) )
		{
			$_SESSION['ipg_detail'][ $albumId ] = $display;
		}
		elseif ( $_SESSION['ipg_detail'][ $albumId ] )
		{
			$display = $_SESSION['ipg_detail'][ $albumId ];
		}
		
		/* Check album permalink... */
		$this->registry->getClass('output')->checkPermalink( $album['album_name_seo'] );
		
		/* Add canonical tag */
		$this->registry->getClass('output')->addCanonicalTag( ( $this->request['st'] ) ? 'app=gallery&amp;album=' . $album['album_id'] . '&st=' . $this->request['st'] : 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		
		/* Store root doc URL */
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=gallery&amp;album=' .  $album['album_id'], 'publicNoSession', $album['album_name_seo'], 'viewalbum' ) );
		
		/* Check the rest */
		$parents           = $this->albums->fetchAlbumParents( $album );
		$children          = $this->albums->fetchAlbumChildren( $album, array( 'nestDescendants' => true, 'isViewable' => true, 'limit' => 5000, 'sortKey' => 'position', 'sortOrder' => 'asc' ) );
		$childrenRootCount = count( $children );
		$childKeys         = array_slice( $this->albums->flattenAndExtractAlbumIds( $children ), 0, 500, true );
		$children 	       = array_slice( $children, 0, 30, true );

		$start     = intval( $this->request['st'] );
		$cover     = array();
		
		/* Can develop this as a special landing page for members
		if ( $this->albums->getMembersAlbumId() == $album['album_id'] )
		{
			$subAlbums = $children;
		}
		else
		{
			$subAlbums = $this->albums->fetchAlbumsByFilters( array( 'sortKey' => 'position', 'sortOrder' => 'asc', 'album_parent_id' => $album['album_id'], 'isViewable' => true, 'getChildren' => 'global', 'getGlobalAlbumLatestImgs' => 5, 'limit' => 100 ) );
		} */
		
		
		$subAlbums = $this->albums->fetchAlbumsByFilters( array( 'sortKey' => 'position', 'sortOrder' => 'asc', 'album_parent_id' => $album['album_id'], 'isViewable' => true, 'getChildren' => 'global', 'getGlobalAlbumLatestImgs' => 5, 'limit' => 100 ) );
		
		/* Discover some permissions */
		$album['_canEdit']     = $this->albums->canEdit( $album );
		$album['_canDelete']   = $this->albums->canDelete( $album );
		$album['_canModerate'] = $this->albums->canModerate( $album );
		
		/* Follows */
		$follow = $this->_follow->render( 'summary', $album['album_id'] );
		
		/* Fetch owner if appropriate */
		if ( $this->albums->isGlobal( $album ) !== true )
		{
			$_owner = array();
			
			/* Album is owned by a member? */
			if ( $album['album_owner_id'] )
			{
				$_owner = IPSMember::load( $album['album_owner_id'] );
				
				/* Album is owned by a DELETED member? Reset some things.. */
				if ( empty($_owner['member_id']) )
				{
					$album['album_owner_id'] = 0;
					$this->DB->update( 'gallery_albums_main', array( 'album_owner_id' => 0 ), 'album_id='.$album['album_id'] );
				}
			}
			
			/* Fallback on guest setup */
			if ( empty($_owner['member_id']) )
			{
				$_owner = IPSMember::setUpGuest();
			}
			
			$album['owner'] = IPSMember::buildProfilePhoto( $_owner );
		}
		
		/* Load members data for childrens.. */
		if ( is_array($children) && count($children) )
		{
			$_membersToLoad	= array();
			$_membersData	= array();
			
			foreach( $children as $aid => $data )
			{
				if ( $data['album_owner_id'] && !$this->albums->isGlobal($data) )
				{
					$_membersToLoad[ $data['album_owner_id'] ] = $data['album_owner_id'];
				}
			}
			
			if ( count($_membersToLoad) )
			{
				$_membersData = IPSMember::load( $_membersToLoad, 'extendedProfile', 'id' );
			}
			
			foreach( $children as $aid => $data )
			{
				if ( $data['album_owner_id'] && !empty($_membersData[ $data['album_owner_id'] ]) )
				{
					$children[ $aid ]['member'] = IPSMember::buildDisplayData( $_membersData[ $data['album_owner_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
				}
			}
		}
		
		/* Is this a container or an album with no direct uploads? */
		if ( ( $this->albums->isContainerOnly( $album ) OR $this->albums->isEmpty( $album ) ) && count( $children ) )
		{
			/* Fetch random medium image */
			$feature = $this->images->fetchFeatured( $album['album_id'] );
			
			if ( ! count( $feature ) )
			{
				$feature = array();
			}
			else
			{
				if ( ! empty( $feature['description'] ) )
				{
					$feature['description'] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripAllTags( $feature['description'] ), 300 );
				}
			
				$feature['tag'] = $this->images->makeImageLink( $feature, array( 'type' => 'medium', 'link-type' => 'page' ) );
			}
			
			/* Fetch 20 recent images */
			$recents  = $this->images->fetchImages( $this->memberData['member_id'], array( 'albumId' => $childKeys, 'limit' => 30, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
			
			/* Fetch recent comments */
			$comments = $this->images->fetchImages( $this->memberData['member_id'], array( 'albumId' => $childKeys, 'getLatestComment' => true, 'hasComments' => true, 'sortKey' => 'lastcomment', 'sortOrder' => 'desc', 'offset' => 0, 'limit' => 5 ) );
			
			/* Seriously, what? */
			$output = $this->registry->output->getTemplate('gallery_albums')->albumFeatureView( $feature, $recents, $album, $children, $this->lang->words['sub_albums'], array(), $comments, $childrenRootCount, false, $subAlbums );
		}
		else
		{
			if ( !$this->request['sortby'] or !in_array( $this->request['sortby'], array( 'idate', 'views', 'comments', 'rating', 'name' ) ) )
			{
				$this->request['sortby'] = $album['album_sort_options__key'];
				$this->request['sortorder'] = $album['album_sort_options__dir'];
			}
			
			$this->request['sortorder'] = $this->request['sortorder'] ? $this->request['sortorder'] : 'DESC';
			
			/* Fetch all images in this album */
			$images   = $this->images->fetchAlbumImages( $album['album_id'], array( 'parseDescription' => true, 'limit' => GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE, 'offset' => $start, 'sortKey' => $this->request['sortby'], 'sortOrder' => $this->request['sortorder'] ) );
						
			$album['_totalViewableImages'] = (int) $album['album_count_imgs'];
			
			if ( $this->albums->canModerate( $album ) )
			{
				$album['_totalViewableImages'] += (int) $album['album_count_imgs_hidden'];
			}
			
			if ( GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE < $album['_totalViewableImages'] )
			{
				$album['_pages'] = $this->registry->output->generatePagination(  array( 'totalItems'		=> $album['_totalViewableImages'],
																			   			'itemsPerPage'		=> GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE,
																			   			'currentStartValue'	=> $start,
																			   			'seoTitle'			=> $album['album_name_seo'],
																			   			'seoTemplate'		=> 'viewalbum',
																			   			'baseUrl'			=> "app=gallery&amp;album={$album['album_id']}&amp;sortby={$this->request['sortby']}&amp;sortorder={$this->request['sortorder']}" ) );
			}
			
			/* Cover */
			$cover        = $this->images->fetchImage( $album['album_cover_img_id'], FALSE, FALSE );
			$cover['tag'] = $this->images->makeImageLink( $cover, array( 'h1image' => TRUE ) );
						
			/* Fetch and format cover image */
			if ( $display == 'overview' )
			{ 
				/* Seriously, what? */
				$output = $this->registry->output->getTemplate('gallery_albums')->albumView( $cover, $images, $album, $children, $parents, $follow, $subAlbums, $childrenRootCount );
				
				/* Make meta src image the thumbnail */
				$this->settings['meta_imagesrc'] = $this->images->makeImageTag( $cover, array( 'type' => 'thumb', 'link-type' => 'src' ) );
			}
			else
			{
				$cover['tag'] = $this->images->makeImageLink( $cover, array( 'h1image' => TRUE ) );
			
				/* Build small sized images */
				foreach( $images as $id => $data )
				{
					$images[ $id ]['_smallTag'] = $this->images->makeImageLink( $data, array( 'type' => 'small', 'link-type' => 'page' ) );
				}
				
				/* Seriously, what? */
				$output = $this->registry->output->getTemplate('gallery_albums')->albumViewDetail( $cover, $images, $album, $children, $parents, $follow, $subAlbums, $childrenRootCount );
			}		
		}
		
		$this->title =  $album['album_name'] . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'];
		
		$this->nav   = array( 0 => array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' ) );
		
		$parents = array_reverse( $parents, true );
		
		foreach( $parents as $id => $data )
		{
			$this->nav[] = array( $data['album_name'], 'app=gallery&amp;album=' . $data['album_id'], $data['album_name_seo'], 'viewalbum' );	
		}
		
		$this->nav[] = array( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		
		/* Add Meta Content */
		$this->registry->output->addMetaTag( 'keywords', $album['album_name'], TRUE );
		
		if ( $album['album_description'] )
		{
			$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", $album['album_description'] ) ), FALSE, 155 );
		}
		
		return $output;
	}
}