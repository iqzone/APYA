<?php
/**
 * @file		albums.php 	Provides ajax methods to mange albums
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * $LastChangedDate: 2011-10-30 16:59:09 -0400 (Sun, 30 Oct 2011) $
 * @version		v4.2.1
 * $Revision: 9707 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_ajax_albums
 * @brief		Provides ajax methods to mange albums
 */
class admin_gallery_ajax_albums extends ipsAjaxCommand 
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load skin */
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_gallery');
		
		/* Load lang */
		$this->lang->loadLanguageFile( array( 'admin_gallery', 'public_gallery_four' ), 'gallery' );
		
		/* Load gallery helper classes */
		$this->_albums = $this->registry->gallery->helper('albums');
		$this->_images = $this->registry->gallery->helper('image');
		
    	switch( $this->request['do'] )
    	{
			case 'albumAutocomplete':
				$this->_albumAutocomplete();
				break;
			case 'getAlbumPopup':
				$this->_getAlbumPopup();
				break;
			case 'deleteDialogue':
				$this->_deleteDialogue();
				break;
			case 'reorder':
				$this->_reorder();
				break;
			case 'recount':
				$this->_recount();
				break;
			case 'rebuildThumbs':
				$this->_rebuildThumbs();
				break;
			case 'resetPermissions':
				$this->_resetPermissions();
				break;
			case 'resyncAlbums':
				$this->_resyncAlbums();
				break;
			case 'rebuildNodes':
				$this->_rebuildNodes();
				break;
			case 'rebuildTreeCaches':
				$this->_rebuildTreeCaches();
				break;
			case 'rebuildStats':
				$this->_rebuildStats();
				break;
			case 'getMemberAlbums':
				$this->_getMemberAlbums();
			break;
			case 'getGlobalAlbums':
				$this->_getGlobalAlbums();
			break;
			/* Album selector */
			case 'show':
			case 'albumSelector':			
			case 'albumSelectorPane':
			case 'select':
				/* Load public side.. */
				$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('gallery') . '/modules_public/ajax/albumSelector.php', 'public_gallery_ajax_albumSelector' );
				$publicSide  = new $classToLoad();
				$publicSide->makeRegistryShortcuts( $this->registry );
		
				/* ..and return */
				$publicSide->doExecute( $this->registry );
			break;
    	}
	}
	
	/**
	 * Get member albums or else
	 *
	 * @return	@e void
	 */
	public function _getGlobalAlbums()
	{
		$searchMatch = trim( $this->request['searchMatch'] );
		$searchSort  = trim( $this->request['searchSort'] );
		$searchDir   = trim( $this->request['searchDir'] );
		$searchText  = trim( $this->request['searchText'] );
		$filters     = array('getChildren' => 'global', 'album_is_global' => 1, 'limit' => 150 );
		$output      = '';
		
		/* Lets go */
		$filters['sortOrder'] = $searchDir;
		$filters['sortKey']   = $searchSort;
		
		/* Got text? */
		if ( $searchText )
		{
			if ( $searchMatch == 'is' )
			{
				$filters['albumNameIs'] = $searchText;
			}
			else
			{
				$filters['albumNameContains'] = $searchText;
			}
		}
		else
		{
			$filters['album_parent_id'] = 0;
		}
		
		$this->html->form_code = 'module=albums&amp;section=manage&amp;';
		
		$albums = $this->_albums->fetchAlbumsByFilters( $filters );
		
		$this->returnHtml( $this->html->ajaxAlbums( $albums ) );
	}
	
	/**
	 * Get member albums or else
	 *
	 * @return	@e void
	 */
	public function _getMemberAlbums()
	{
		$searchType  = trim( $this->request['searchType'] );
		$searchMatch = trim( $this->request['searchMatch'] );
		$searchSort  = trim( $this->request['searchSort'] );
		$searchDir   = trim( $this->request['searchDir'] );
		$searchText  = trim( $this->request['searchText'] );
		$filters     = array('getParents' => true, 'album_is_global' => 0, 'limit' => 150 );
		$output      = '';
		
		/* Lets go */
		$filters['sortOrder'] = $searchDir;
		$filters['sortKey']   = $searchSort;
		
		/* Got text? */
		if ( $searchText )
		{
			if ( $searchType == 'album' )
			{
				if ( $searchMatch == 'is' )
				{
					$filters['albumNameIs'] = $searchText;
				}
				else
				{
					$filters['albumNameContains'] = $searchText;
				}
			}
			else if ( $searchType == 'parent')
			{
				$filters['album_parent_id'] = $searchText;
			}
			else
			{
				if ( $searchMatch == 'is' )
				{
					$filters['albumOwnerNameIs'] = $searchText;
				}
				else
				{
					$filters['albumOwnerNameContains'] = $searchText;
				}
			}
		}
		
		$this->html->form_code = 'module=albums&amp;section=manage&amp;';
		
		$albums = $this->_albums->fetchAlbumsByFilters( $filters );
		
		$this->returnHtml( $this->html->ajaxAlbums( $albums ) );
	}
	
	/**
	 * Album Auto Complete
	 *
	 * @return	@e void
	 */
	public function _albumAutocomplete()
	{
		/* Load public side.. */
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('gallery') . '/modules_public/ajax/album.php', 'public_gallery_ajax_album' );
		$publicSide  = new $classToLoad();
		$publicSide->makeRegistryShortcuts( $this->registry );
		
		/* ..and return */
		$publicSide->_albumAutocomplete();
	}
	
    /**
	 * Album popup
	 *
	 * @return	@e void
	 */
	public function _getAlbumPopup()
	{
		$albumId = intval($this->request['albumId']);
		
		$album   = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		$last10  = $this->registry->gallery->helper('image')->fetchAlbumImages( $albumId, array( 'sortOrder' =>'desc', 'sortKey' => 'idate', 'limit' => 10 ) );
		
		$this->returnJsonArray( array( 'popup' => $this->html->albumPopup( $album, $last10 ) ) );
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
		$data['options'] = $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'isUploadable' => 0, 'memberData' => array( 'member_id' => $ownerId ), 'skip' => array( $albumId ) ) );
		
		$this->returnHtml( $this->html->acpDeleteAlbumDialogue( $data, $hasKids ) );
	}
	
	/**
	 * Resyncs albums
	 *
	 * @return	@e void
	 */
	public function _rebuildTreeCaches()
	{
		$albumId = ( $this->request['albumId'] == 'all' ) ? '' : intval( $this->request['albumId'] );
		$json    = array();
		$pergo   = 50;
		$where   = ( $albumId ) ? 'album_id=' . $albumId : '1=1';
		
		/* Could use the api, but i'm lazy */
		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) with_elmo',
													  'from'   => 'gallery_albums_main',
													  'where'  => $where ) );
			
			$json['total'] = intval( $count['with_elmo'] );
			$json['pergo'] = $pergo;
		}
		else
		{
			$lastId  = intval( $this->request['pb_lastId'] );
			$pb_done = intval( $this->request['pb_done'] );
			$seen    = 0;
			$_where  = ( $albumId ) ?  $where : 'album_id > ' . $lastId;
			$limit   = ( $albumId ) ? array( $pb_done, $pergo ) : array( 0, $pergo );
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_albums_main',
									 'where'  => $_where,
									 'order'  => 'album_id ASC',
									 'limit'  => $limit ) );
			
			$o = $this->DB->execute();
			
			while( $album = $this->DB->fetch($o) )
			{
				$seen++;
				$lastId = $album['album_id'];
				
				$this->registry->gallery->helper('tools')->rebuildTree( $album['album_id'] );
			}
			
			/* Done? */
			if ( $seen )
			{
				$json = array( 'status' => 'processing',
							   'lastId' => $lastId );
			}
			else
			{
				$json = array( 'status' => 'done',
							   'lastId' => $lastId );
			}
			
		}
		
		$this->returnJsonArray( $json );
	}
	
	/**
	 * Rebuilds gallery stats
	 *
	 * @return	@e void
	 */
	public function _rebuildStats()
	{
		$albumId = ( $this->request['albumId'] == 'all' ) ? 0 : intval( $this->request['albumId'] );
		$json    = array();
		
		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$json['total'] = 1;
			$json['pergo'] = 1;
		}
		else
		{
			/* Simple */
			$this->registry->gallery->rebuildStatsCache();
		
			$json = array( 'status' => 'done',
						   'lastId' => $albumId );
		}
		
		$this->returnJsonArray( $json );
	}
	
	/**
	 * Rebuilds album nodes
	 *
	 * @return	@e void
	 */
	public function _rebuildNodes()
	{
		$albumId = ( $this->request['albumId'] == 'all' ) ? 0 : intval( $this->request['albumId'] );
		$json    = array();
		
		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$json['total'] = 1;
			$json['pergo'] = 1;
		}
		else
		{
			/* Simple */
			$this->registry->gallery->helper('albums')->rebuildNodeTree();
		
			$json = array( 'status' => 'done',
						   'lastId' => $albumId );
		}
		
		$this->returnJsonArray( $json );
	}
	
	/**
	 * Rebuilds album permissions
	 *
	 * @return	@e void
	 */
	public function _resetPermissions()
	{
		$albumId = ( $this->request['albumId'] == 'all' ) ? 0 : intval( $this->request['albumId'] );
		$json    = array();
		
		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$json['total'] = 1;
			$json['pergo'] = 1;
		}
		else
		{
			/* Simple */
			$this->registry->gallery->helper('moderate')->resetPermissions( $albumId );
		
			$json = array( 'status' => 'done',
						   'lastId' => $albumId );
		}
		
		$this->returnJsonArray( $json );
	}
	
	/**
	 * Resyncs albums
	 *
	 * @return	@e void
	 */
	public function _resyncAlbums()
	{
		$albumId = ( $this->request['albumId'] == 'all' ) ? '' : intval( $this->request['albumId'] );
		$json    = array();
		$pergo   = 50;
		$where   = ( $albumId ) ? 'album_id=' . $albumId : '1=1';
		
		/* Could use the api, but i'm lazy */
		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) with_elmo',
													  'from'   => 'gallery_albums_main',
													  'where'  => $where ) );
			
			$json['total'] = intval( $count['with_elmo'] );
			$json['pergo'] = $pergo;
		}
		else
		{
			$lastId  = intval( $this->request['pb_lastId'] );
			$pb_done = intval( $this->request['pb_done'] );
			$seen    = 0;
			$_where  = ( $albumId ) ?  $where : 'album_id > ' . $lastId;
			$limit   = ( $albumId ) ? array( $pb_done, $pergo ) : array( 0, $pergo );
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_albums_main',
									 'where'  => $_where,
									 'limit'  => $limit ) );
			
			$o = $this->DB->execute();
			
			while( $album = $this->DB->fetch($o) )
			{
				$seen++;
				$lastId = $album['album_id'];
				
				$this->_albums->resync( $album['album_id'] );
			}
			
			/* Done? */
			if ( $seen )
			{
				$json = array( 'status' => 'processing',
							   'lastId' => $lastId );
			}
			else
			{
				$json = array( 'status' => 'done',
							   'lastId' => $lastId );
			}
			
		}
		
		$this->returnJsonArray( $json );
	}
	
	/**
	 * Rebuilds thumbs
	 *
	 * @return	@e void
	 */
	public function _rebuildThumbs()
	{
		$albumId = ( $this->request['albumId'] == 'all' ) ? '' : intval( $this->request['albumId'] );
		$json    = array();
		$pergo   = 10;
		$where   = ( $albumId ) ? 'img_album_id=' . $albumId : '1=1';
		
		/* Could use the api, but i'm lazy */
		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) with_elmo',
													  'from'   => 'gallery_images',
													  'where'  => $where ) );
			
			$json['total'] = intval( $count['with_elmo'] );
			$json['pergo'] = $pergo;
		}
		else
		{
			$lastId  = intval( $this->request['pb_lastId'] );
			$pb_done = intval( $this->request['pb_done'] );
			$seen    = 0;
			$_where  = ( $albumId ) ?  $where : 'id > ' . $lastId;
			$limit   = ( $albumId ) ? array( $pb_done, $pergo ) : array( 0, $pergo );
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_images',
									 'where'  => $_where,
									 'limit'  => $limit ) );
			
			$o = $this->DB->execute();
			
			while( $image = $this->DB->fetch($o) )
			{
				$seen++;
				$lastId = $image['id'];
				
				/* Delete the various sizes (we're about the rebuild them) */
				@unlink( "{$this->settings['gallery_images_path']}/{$image['directory']}/tn_{$image['masked_file_name']}" );
				@unlink( "{$this->settings['gallery_images_path']}/{$image['directory']}/med_{$image['masked_file_name']}" );
				@unlink( "{$this->settings['gallery_images_path']}/{$image['directory']}/sml_{$image['masked_file_name']}" );
				
				/* Rename our masked file */
				if ( $image['original_file_name'] )
				{
					@unlink( "{$this->settings['gallery_images_path']}/{$image['directory']}/{$image['masked_file_name']}" );
					@rename( "{$this->settings['gallery_images_path']}/{$image['directory']}/{$image['original_file_name']}", "{$this->settings['gallery_images_path']}/{$image['directory']}/{$image['masked_file_name']}" );
				}
				
				/* Now rebuild the image */
				$this->_images->buildSizedCopies( $image );
			}
			
			/* Done? */
			if ( $seen )
			{
				$json = array( 'status' => 'processing',
							   'lastId' => $lastId );
			}
			else
			{
				$json = array( 'status' => 'done',
							   'lastId' => $lastId );
			}
			
		}
		
		$this->returnJsonArray( $json );
	}
	
	/**
	 * Recount albums
	 *
	 * @return	@e void
	 */
	public function _recount()
	{
		$albumId = intval( $this->request['albumId'] );
		
		$this->_albums->resync( $albumId );
		
		$this->returnJsonArray( array( 'ok' => $this->lang->words['acp_album_resync_done'] ) );
	}
	
	/**
	 * Reorder albums
	 *
	 * @return	@e void
	 */
	public function _reorder()
	{
		/* Init */
		$albumId    = intval( $this->request['albumId'] );
 		$nearId     = 0;
 		$map = $pam = array();
 		$c          = 0;
 		$position   = 'before';
 		
 		if ( is_array( $this->request['albums'] ) AND count( $this->request['albums'] ) )
 		{
 			foreach( $this->request['albums'] as $this_id )
 			{				
 				$map[ $c ]       = $this_id;
 				$pam[ $this_id ] = $c;
 				
 				$c++;
 			}
 		}
 		
 		/* Is first */
 		if ( $pam[ $albumId ] == 0 )
 		{
 			$position = 'before';
 			$nearId   = $map[1];
 		}
 		else
 		{
 			$position = 'after';
 			$nearId   = $map[ $pam[ $albumId ] - 1 ];
 		}
 		
 		/* reset all other albums */
 		foreach( $pam as $aid => $count )
 		{
 			$this->DB->update( 'gallery_albums_main', array( 'album_position' => $count ), 'album_id=' . intval( $aid ) );
 		}
 		
 		$this->_albums->movePosition( $albumId, $nearId, $position );

 		$this->returnString( 'OK' );
	}
}