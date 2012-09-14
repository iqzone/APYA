<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Image Ajax
 * Last Updated: $LastChangedDate: 2011-11-15 12:10:51 -0500 (Tue, 15 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9821 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_album extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'fetchAlbumJson':
				$this->_fetchAlbumJson();
				break;
			case 'newAlbumDialogue':
				$this->_newAlbumDialogue();
				break;
			case 'checkWatermarkOption':
				$this->_checkWatermarkOption();
				break;
			case 'newAlbumSubmit':
				$this->_newAlbumSubmit();
				break;
			case 'deleteDialogue':
				$this->_deleteDialogue();
				break;
			case 'moderate':
				$this->_moderate();
				break;
			case 'albumAutocomplete':
				$this->_albumAutocomplete();
				break;
			case 'getAlbumJson':
				$this->_getAlbumJson();
				break;
        }
    }
    
    /**
	 * Album Auto Complete
	 *
	 * @return	@e void
	 */
	public function _getAlbumJson()
	{
		$albumId 			   = intval( $this->request['albumId'] );
		$album                 = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		$album['last10Images'] = $this->registry->gallery->helper('image')->fetchAlbumImages( $albumId, array( 'sortOrder' =>'desc', 'sortKey' => 'idate', 'limit' => 10 ) );
		
		$this->returnJsonArray( array( 'album' => $album ) );
	}
	
	/**
	 * Album Auto Complete
	 *
	 * @return	@e void
	 */
	public function _albumAutocomplete()
	{
    	/* Init */
    	$search = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['search'], 0 ), true );
		$search = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );
    	
		$return = array();
		
    	if ( IPSText::mbstrlen( $search ) < 3 )
    	{
    		$this->returnJsonError( 'requestTooShort' );
    	}

    	/* Fetch albums */
    	$bypass = ( IN_ACP ) ? GALLERY_ALBUM_BYPASS_PERMS : false;
    	$albums = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'albumNameContains' => $search ) );

    	if ( ! count( $albums ) )
 		{
    		$this->returnJsonArray( array( ) );
    	}
		
    	/* Return */
		foreach( $albums as $id => $album )
		{
			$return[ $id ] = array( 'name' 	=> $album['album_name'],
									'showas'=> '<strong>' . $album['album_name'] . '</strong>',
									'img'	=> $this->registry->gallery->inlineResize( $album['thumb'], 30, 30 ),
									'img_w'	=> $album['album_count_imgs'],
									'img_h'	=> '' );
		}

		$this->returnJsonArray( $return );
	}
		
	/**
	 * Moderate not exterminate
	 *
	 * @return	@e void
	 */
	public function _moderate()
	{
		/* Init data */
		$albumId   = intval( $this->request['albumId'] );
		$imageIds  = ( is_array( $_POST['imageIds'] ) ) ? IPSLib::cleanIntArray( $_POST['imageIds'] ) : array();
		$toAlbumId = intval( $this->request['toAlbumId'] );
		$selectAll = intval( $this->request['selectAll'] );
		
		/* Did we select all? */
		if ( $selectAll )
		{
			$im = $this->registry->gallery->helper('albums')->fetchAlbumImages( $albumId );
			
			if ( count( $im ) )
			{
				$imageIds = array_keys( $im );
			}
		}
		
		if ( !count($imageIds) )
		{
			$this->returnJsonError( $this->lang->words['album_modaction_noimages'] );
		}
		
		/* To what, are we doing young man? */
		switch ( $this->request['modact'] )
		{
			case 'unapprove':
			case 'approve':
				if ( ! $this->registry->gallery->helper('albums')->canModerate() )
				{
					$this->returnJsonError('no_permission');
				}
				
				$this->registry->gallery->helper('moderate')->toggleVisibility( $imageIds, ( ( $this->request['modact'] == 'approve' ) ? true : false ) );
			break;
			case 'delete':
				if ( ! $this->registry->gallery->helper('albums')->isOwner( $albumId ) AND ! $this->registry->gallery->helper('albums')->canModerate( $albumId ) )
				{
					$this->returnJsonError('no_permission');
				}
				
				$this->registry->gallery->helper('moderate')->deleteImages( $imageIds );
			break;
			case 'move':
				if ( ! $this->registry->gallery->helper('albums')->isOwner( $albumId ) AND ! $this->registry->gallery->helper('albums')->canModerate( $albumId ) )
				{
					$this->returnJsonError('no_permission');
				}
				
				$this->registry->gallery->helper('moderate')->moveImages( $imageIds, $toAlbumId );
			break;
		}
		
		$this->returnJsonArray( array( 'done' => 1 ) );
	}
	
	/**
	 * Delete album (boo)
	 *
	 * @return	@e void
	 */
	public function _deleteDialogue()
	{
		/* Init data */
		$albumId = intval( $this->request['albumId'] );
		$data    = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		$ownerId = ( $data['album_owner_id'] ) ? $data['album_owner_id'] : null;
		$hasKids = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_parent_id' => $albumId, 'limit' => 1 ) );
		
		/* Populate options */
		if ( empty($data['album_count_imgs']) && empty($data['album_count_imgs_hidden']) )
		{
			$data['options'] = false;
		}
		else
		{
			$data['options'] = $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isUploadable' => 1, 'memberData' => array( 'member_id' => $ownerId ), 'skip' => array( $albumId ) ) );
		}
		
		$this->returnHtml( $this->registry->output->getTemplate('gallery_albums')->deleteAlbumDialogue( $data, $hasKids ) );
	}
	
	/**
	 * New album submit
	 *
	 * @return	@e void
	 */
	public function _newAlbumSubmit()
	{
		/* Cannot create? */
		if ( !$this->registry->gallery->helper('albums')->canCreate() )
		{
			$this->returnJsonError( $this->lang->words['album_cannot_create_limit'] );
		}
		
		/* Fix up names, damn charsets */
    	$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['album_name'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );
		
    	$desc = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['album_description'], 0 ), true );
		$desc = IPSText::convertCharsets( $desc, 'utf-8', IPS_DOC_CHAR_SET );
		
		/* Init data */
		$album = array( 'album_name'			=> $name,
						'album_description'		=> $desc,
						'album_detail_default'	=> intval( $this->request['album_detail_default'] ),
						'album_sort_options'	=> serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) ),
						'album_is_public'		=> intval( $this->request['album_is_public'] ),
						'album_parent_id'		=> intval( $this->request['album_parent_id'] ),
						'album_owner_id'		=> intval( $this->memberData['member_id'] ),
						'album_watermark'		=> intval( $this->request['album_watermark'] )
						);
		
		/* Save it for the judge */
		try 
		{
			$album = $this->registry->gallery->helper('moderate')->createAlbum( $album );
		
			$this->returnJsonArray( array( 'album' => $album ) );
		}
		catch ( Exception $e )
		{
			$msg = $e->getMessage();
			
			if ( $msg == 'MEMBER_ALBUM_NO_PARENT' )
			{
				$msg = $this->lang->words['parent_zero_not_global'];
			}
			
			$this->returnJsonError( $msg );
		}
		
	}
	
	/**
	 * Want a new album?
	 *
	 * @return	@e void
	 */
	public function _newAlbumDialogue()
	{
		/* Cannot create? */
		if ( !$this->registry->gallery->helper('albums')->canCreate() )
		{
			$this->returnJsonError( $this->lang->words['album_cannot_create_limit'] );
		}
		
		/* Init data */
		$data = array( 'album_is_public' => 1 );
		
		/* Populate options */
		$data['_parent'] = $this->registry->gallery->helper('albums')->fetchAlbumsById( $this->registry->gallery->helper('albums')->getMembersAlbumId() );
		
		$this->returnHtml( $this->registry->output->getTemplate('gallery_albums')->newAlbumDialogue( $data ) );
	}
	
	/**
	 * Checks if a certain album can have watermarks or not
	 * 
	 * @return	@e void
	 */
	public function _checkWatermarkOption()
	{
		if ( $this->registry->gallery->helper('image')->applyWatermark( intval($this->request['parentId']) ) )
		{
			$this->returnJsonArray( array( 'watermark' => 'show' ) );
		}
		else
		{
			$this->returnJsonArray( array( 'watermark' => 'hide' ) );
		}
	}
	

	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @return	@e void
	 */
	public function _fetchAlbumJson()
	{
		/* init */
		$album_id = intval( $this->request['album_id'] );
		$album    = array();
		
		$album    = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array( 'album_id'  => $album_id ) );
		
		/* Fetch */
		if ( isset( $album[ $album_id ] ) )
		{
			$this->returnJsonArray( $album[ $album_id ] );
		}
		else
		{
			$this->returnJsonArray( array() );
		}
	}
}