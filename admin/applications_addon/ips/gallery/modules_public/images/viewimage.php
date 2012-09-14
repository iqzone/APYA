<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * View Image
 * Last Updated: $LastChangedDate: 2011-12-09 15:24:24 -0500 (Fri, 09 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9978 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_viewimage extends ipsCommand
{
	/**
	 * Allow to set an avatar
	 *
	 * @var		bool
	 */
	public $canDoAvatar	= false;
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{	
		$imageId = intval($this->request['image']);
		
		if ( $imageId < 1 )
		{
			// Handle old links
			if ( $this->request['img'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=gallery&image=' . $this->request['img'], NULL, TRUE );
			}
			
			$this->registry->output->showError( 'img_not_found', 10740, null, null, 404 );
		}
		
		/* Load language files */
		$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
		$this->lang->loadLanguageFile( array( 'public_editors'), 'core' );
		
		/* Init some data */
		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'gallery-images' );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_follow = classes_like::bootstrap( 'gallery', 'images' );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$this->_mapping = classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		/* Short cuts */
		$this->_albums   = $this->registry->gallery->helper('albums');
		$this->_images   = $this->registry->gallery->helper('image');
		$this->_media    = $this->registry->gallery->helper('media');
		
		/* Like/reputation cache */
		if ( $this->settings['reputation_enabled'] )
		{
			/* Load the class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			$this->registry->setClass( 'repCache', new $classToLoad() );
		
			/* Update the filter? */
			if ( isset( $this->request['rep_filter'] ) && $this->request['rep_filter'] == 'update' )
			{
				$_mem_cache = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
				
				if( $this->request['rep_filter_set'] == '*' )
				{
					$_mem_cache['rep_filter'] = '*';
				}
				else
				{
					$_mem_cache['rep_filter'] = intval( $this->request['rep_filter_set'] );
				}
				
				IPSMember::packMemberCache( $this->memberData['member_id'], $_mem_cache );
				
				$this->memberData['_members_cache'] = $_mem_cache;
			}
			else
			{
				$this->memberData['_members_cache'] = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
			}
			
			$this->memberData['_members_cache']['rep_filter'] = isset( $this->memberData['_members_cache']['rep_filter'] ) ? $this->memberData['_members_cache']['rep_filter'] : '*';
		}
		
		/* Set up joins */
		$joins = array( array( 'select'	=> 'm.*',
							   'from'		=> array( 'members' => 'm' ),
							   'where'	=> 'm.member_id=i.member_id',
							   'type'		=> 'left' ),
					    array( 'select'	=> 'pp.*',
							   'from'		=> array( 'profile_portal' => 'pp'),
							   'where'		=> 'pp.pp_member_id=i.member_id',
							   'type'		=> 'left' ) );
					    
		/* Rating */
		$join = $this->registry->gallery->helper('rate')->getTableJoins( 'i.id', 'image', $this->memberData['member_id'] );
		
		if ( $join !== false && is_array( $join ) )
		{
			array_push( $joins, $join );
		}
		
		/* Reputation enabled? */						
		if ( $this->settings['reputation_enabled'] )
		{
			$joins[] = $this->registry->getClass('repCache')->getTotalRatingJoin('id', $imageId, 'gallery');
			$joins[] = $this->registry->getClass('repCache')->getUserHasRatedJoin('id', $imageId, 'gallery');
		}
		
		$joins[] = $this->registry->galleryTags->getCacheJoin( array( 'meta_id_field' => 'i.id' ) );
		
		/* Fetch the image */
		$image = $this->DB->buildAndFetch( array( 'select'	 => 'i.*, i.member_id as mid',
												  'from'	 => array( 'gallery_images' => 'i' ),
												  'where'	 => 'i.id=' . $imageId,
												  'add_join' => $joins ) );
		
		/* Got an image? */
		if ( ! $image['id'] )
		{
		 	$this->registry->output->showError( 'img_not_found', 10744, null, null, 404 );
		}
		
		/* can view image? */
		if ( ! $this->_images->isViewable( $image ) )
		{
			$this->registry->output->showError( 'img_not_found', 10744.1, null, null, 404 );
		}
		
		/* check permalink */
		$this->registry->getClass('output')->checkPermalink( $image['caption_seo'] );
		
		/* TIG TAG */
		if ( ! empty( $image['tag_cache_key'] ) )
		{
			$image['tags'] = $this->registry->galleryTags->formatCacheJoinData( $image );
		}
		
		/* Load up author */													
		$author = IPSMember::buildDisplayData( $image );
		
		/* Set this for the sessions class */
		$this->member->sessionClass()->addQueryKey( 'location_2_id', intval( $image['img_album_id'] ) );
		
		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$image['_data'] = unserialize( $image['image_data'] );
		}
		
		/* Load up parent album */
		$album = $this->registry->gallery->helper('albums')->fetchAlbum( $image['img_album_id'] );
		
		/* Image Notes */
		$image = $this->_unpackNotes( $image );
		
		/* Fetch the image */
		$output = $this->_showImage( $image, $album, $author );

		/* Update the stats */
		$this->DB->update( 'gallery_images', array( 'views' => ($image['views'] + 1) ), 'id=' . $image['id'], true );
				
		/* Fetch navigation */
		$nav     = array( array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' ) );
		$parents = $this->registry->gallery->helper('albums')->fetchAlbumParents( $album['album_id'] );
		
		if ( count($parents) )
		{
			$parents = array_reverse( $parents, true );
			
			foreach( $parents as $id => $data )
			{
				$nav[] = array( $data['album_name'], 'app=gallery&amp;album=' . $data['album_id'], $data['album_name_seo'], 'viewalbum' );	
			}
		}
		
		/* add in this album */
		$nav[] = array( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		
		/* add in image */
		$nav[] = array( $image['caption'] );
		
		/* Output */
		$this->registry->getClass('output')->setTitle( $image['caption'] . ' - ' . $album['album_name'] . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $output );
		
		foreach( $nav as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}
		
		/* Add Meta Content */
		$this->registry->output->addMetaTag( 'keywords', $image['caption'], TRUE );
		
		$this->registry->output->addMetaTag( 'description', sprintf( $this->lang->words['gallery_img_meta_description'], $image['caption'], $album['album_name'], $image['description'] ), FALSE, 155 );
		
		/* Make meta src image the thumbnail */
		$this->settings['meta_imagesrc'] = $this->_images->makeImageTag( $image, array( 'type' => 'thumb', 'link-type' => 'src' ) );
		
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Show an image from an album
	 *
	 * @param	array		Image array
	 * @param	array		Album array
	 * @param	array		Author array
	 * @return	@e void
	 */
	protected function _showImage( $image, $album, $author )
	{
		/* Set up some vars */
		$image['description']	= empty($image['description']) ? trim($image['description']) : IPSText::getTextClass('bbcode')->preDisplayParse( $image['description'] );
		$dir					= $image['directory'] ? $image['directory'] . '/' : '';
		
		/* Fix guest names */
		if ( empty($author['member_id']) )
		{
			$author['members_display_name'] = $this->lang->words['global_guestname'];
		}
		
		/* Set up the image buttons */
		$image = $this->_setImageButtons( $image, $album );
		
		if ( $image['media'] )
		{
			/* Media specific bw check */
			if ( ! $this->_images->checkBandwidth() )
			{
				$image['movie'] = $this->lang->words['bwlimit'];
			}
			else
			{
				$image['movie'] = $this->_media->getPlayerHtml( $image );
			}
			
			$dimensions	 = array( 0 => 0, 1 => 0 );
		}
		else
		{
			$image['image']	         = $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'medium', 'link-type' => 'none' ) );
			$image['image_url-full'] = $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'full'  , 'link-type' => 'src' ) );
		 	$image['image_url']      = $this->settings['gallery_images_url'] . '/' . $dir . $image['masked_file_name'];
		 	$dimensions	             = $image['_data']['sizes']['max'];
		}
	
		$image['dimensions']	= intval($dimensions[0]) . 'x' . intval($dimensions[1]);
		$image['filesize']		= IPSLib::sizeFormat( $image['file_size'] );
		
		$image['copyright'] = $image['copyright'] ? $image['copyright'] : '';
		$image['copyright'] = str_replace( "&amp;copy", "&copy", $image['copyright'] );
		
		/* Meta data */
		if ( $image['metadata'] != '' )
		{
			$meta_info = unserialize( $image['metadata'] );
			
			if ( ! empty( $meta_info['GPS'] ) )
			{
				unset( $meta_info['GPS'] );
			}
			
			if ( is_array($meta_info) AND count($meta_info) )
			{
				$image['metahtml'] = $this->registry->output->getTemplate('gallery_img')->meta_html( $meta_info );
				
				if ( ! empty( $meta_info['Camera Model'] ) )
				{
					$image['_camera_model'] = $meta_info['Camera Model'];
					
					if ( ! empty( $meta_info['Camera Make'] ) )
					{
						if ( ! stristr( $image['_camera_model'], $meta_info['Camera Make'] ) )
						{
							$image['_camera_model'] = $meta_info['Camera Make'] . ' ' . $image['_camera_model'];
						}
					}
				}
				
				if ( ! empty( $meta_info['Date Taken'] ) )
				{
					$test = strtotime( $meta_info['Date Taken'] );
					
					if ( is_numeric( $test ) and strlen( $test ) == 10 )
					{
						$image['_date_taken'] = strtotime( $meta_info['Date Taken'] );
					}
				}
			}
		}

		/* Location services */
		if ( $image['image_gps_lat'] )
		{
			$image['_latLon']   = implode( ',', $this->registry->gallery->helper('image')->getLatLon( $image ) );
			$image['_locShort'] = $image['image_loc_short'];
			
			if ( ! $image['_locShort'] )
			{
				$_gps               = $this->_mapping->reverseGeoCodeLookUp( $image['image_gps_lat'], $image['image_gps_lon'] );
				$image['_locShort'] = $_gps['geocache_short'];
			}
			
			$image['_maps']   = $this->_mapping->getImageUrls( $image['image_gps_lat'], $image['image_gps_lon'], '300x180' );
			$image['_mapUrl'] = $this->_mapping->getMapUrl( $image['image_gps_lat'], $image['image_gps_lon'] );
		}
		else
		{
			$image['_latLon'] = $image['_geocode'] = $image['_locShort'] = false;
		}
		
		/* Got anything to say? */
		$comment_html = $this->_comments->fetchFormatted( $image, array( 'offset' => intval( $this->request['st'] ) ) );

		/* Photo strip search */
		$photo_strip = $this->registry->gallery->helper('image')->fetchPhotoStrip( $image );
		
		/* Follow */
		$follow = $this->_follow->render( 'summary', $image['id'] );
		
		/* Item Marking */		
		$this->registry->classItemMarking->markRead( array( 'albumID' => $image['img_album_id'], 'itemID' => $image['id'] ), 'gallery' );
		
		if ( $this->settings['reputation_enabled'] )
		{
			$image['like']	= $this->registry->repCache->getLikeFormatted( array( 'app' => 'gallery', 'type' => 'id', 'id' => $image['id'], 'rep_like_cache' => $image['rep_like_cache'] ) );
		}
		
		/* Can report? */
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary', 'core' );
		$reports		= new $classToLoad( $this->registry );
		$image['_canReport'] = $reports->canReport('gallery');
		
		/* Done.. */
		return $this->registry->output->getTemplate('gallery_img')->show_image( $image, $author, $this->canDoAvatar, $photo_strip, $comment_html, $this->registry->gallery->helper('image')->fetchNextPrevImages( $image['id'] ), $follow, $album );
	}

	/**
	 * Get special buttons
	 *
	 * @param	string  $mode
	 * @return	@e string
	 */
	public function _setImageButtons( $image, $album )
	{
		$image['can_set_as_profile_photo'] = ( $image['member_id'] == $this->memberData['member_id'] ) ? true : false;
		
		//-------------------------------------------------------
		// Edit Image Button
		//-------------------------------------------------------
		if( $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) || ( $image['member_id'] == $this->memberData['member_id'] && $this->memberData['g_edit_own'] ) )
		{
		 	$image['edit_button'] = true;
		 	$image['mod_buttons'] = true;
		}

		//-------------------------------------------------------
		// Move Image Button
		//-------------------------------------------------------
		if( ( $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) || ( $image['member_id'] == $this->memberData['member_id'] && $this->memberData['g_move_own'] ) ) )
		{
		 	$image['move_button'] = true;
		 	$image['mod_buttons'] = true;
		}

		//-------------------------------------------------------
		// Delete Image Button
		//-------------------------------------------------------
		if( $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) || ( $image['member_id'] == $this->memberData['member_id'] && $this->memberData['g_del_own'] ) )
		{
		 	$image['delete_button'] = true;
		 	$image['mod_buttons'] = true;
		}

		//-------------------------------------------------------
		// Pin + Approve/Unapprove Image Button
		//-------------------------------------------------------
		if( $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) )
		{
		 	$image['pin_button']     = true;		 		
		 	$image['approve_button'] = true;
		 	$image['mod_buttons']    = true;
		}
		
		//-------------------------------------------------------
		// Cover Image
		//-------------------------------------------------------
		$image['unset_as_cover']	= '';
		$image['set_as_cover']		= '';
		
		if ( $album['album_owner_id'] == $this->memberData['member_id'] AND $this->memberData['member_id'] )
		{
			$image['cover_type'] = 'album';
			$image['set_as_cover'] = false;
			
			if ( $album['album_cover_img_id'] != $image['id'] )
			{
				$image['set_as_cover'] = true;
			}
		}
		
		/* Image Control Moderation */
		if( ! $image['media'] )
		{
			if( $this->registry->gallery->helper('albums')->canModerate( $album['album_id'] ) || ( $image['member_id'] == $this->memberData['member_id'] AND $this->memberData['member_id'] ) )
			{ 
			 	$image['image_control_mod'] = true;
			}
		}
		
		return $image;	
	}

	/**
	 * Unpack notes
	 *
	 * @access	protected
	 * @param	array		Image data
	 * @return	@e array		Image data with notes unpacked
	 */
	protected function _unpackNotes( $image )
	{
		$image['image_notes'] = unserialize( $image['image_notes'] );
		$image['image_notes'] = is_array( $image['image_notes'] ) && count( $image['image_notes'] ) ? $image['image_notes'] : array();
		
		foreach( $image['image_notes'] as $k => $v )
		{
			$image['image_notes'][$k]['note'] = IPSText::getTextClass('bbcode')->stripBadWords( $v['note'] );
		}
		
		$tmp = array_keys( $image['image_notes'] );
		$image['_last_image_note'] = array_pop( $tmp );
		
		return $image;
	}
}