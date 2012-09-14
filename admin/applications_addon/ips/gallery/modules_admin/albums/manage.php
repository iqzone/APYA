<?php
/**
 * @file		manage.php 	Gallery albums management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * $LastChangedDate: 2011-12-02 06:11:30 -0500 (Fri, 02 Dec 2011) $
 * @version		v4.2.1
 * $Revision: 9935 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_albums_manage
 * @brief		Gallery albums management
 */
class admin_gallery_albums_manage extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_gallery' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_gallery', 'public_gallery_four' ) );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=albums&amp;section=manage&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=albums&section=manage&';
		
		/* Short cuts */
		$this->_albums = $this->registry->gallery->helper('albums');
		$this->_images = $this->registry->gallery->helper('image');
		
		/* What to do */
		switch( $this->request['do'] )
		{		
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_add' );
				$this->albumForm('add');
			break;
			
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_add' );
				$this->albumSave('add');
			break;
			
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->albumForm('edit');
			break;

			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->albumSave('edit');
			break;
			
			case 'deleteAlbum':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_delete' );
				$this->albumDelete();
			break;
			
			case 'emptyAlbum':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_empty' );
				$this->emptyAlbum();
			break;
			
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'album_manage' );
				$this->indexScreen();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Displays the index screen
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		/* init */
		$parent     = intval( $this->request['parentId'] );
		$album      = ( $parent ) ? $this->_albums->fetchAlbumsById( $parent ) : null;
		$albums     = array();
		$parents    = ( $album ) ? $this->_albums->fetchAlbumParents( $album['album_id'] ) : null;
		$start      = intval( $this->request['st'] );
		$perPage    = 50;
		
		if ( $parents !== null )
		{
			$parents = array_reverse( $parents, true );
			
			foreach( $parents as $id => $data )
			{
				$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=overview&amp;parentId={$data['album_id']}", '<strong>' . $data['album_name'] . '</strong>' );
			}
		}
		
		if ( $album !== null )
		{
			$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=overview&amp;parentId={$album['album_id']}", '<strong>' . $album['album_name'] . '</strong>' );
		}
		
		/* Recently member updated */
		$memberAlbums = $this->html->ajaxAlbums( $this->_albums->fetchAlbumsByFilters( array( 'album_is_global' => 0, 'getParents' => true, 'limit' => 30, 'sortKey' => 'date', 'sortOrder' => 'desc' ) ) );
		
		/* Global albums */
		$globalAlbums = $this->html->ajaxAlbums( $this->_albums->fetchAlbumsByFilters( array( 'album_is_global' => 1, 'getChildren' => 'global', 'album_parent_id' => $parent ) ) );
		
		$this->registry->output->html .= $this->html->albums( $globalAlbums, $parent, $memberAlbums );
	}	
	
	/**
	 * Displays the edit album form
	 *
	 * @return	@e void
	 */
	public function emptyAlbum()
	{
		$albumId = intval( $this->request['albumId'] );
		
		/* fetch images */
		$images  = $this->_images->fetchImages( null, array( 'albumId' => $albumId ) );
		
		if ( count( $images ) )
		{
			$this->registry->gallery->helper('moderate')->deleteImages( array_keys( $images ) );
		}
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['albums_empty_done'];
		$this->indexScreen();
	}
	
	/**
	 * Deletes an album from the database
	 *
	 * @return	@e void
	 */
	public function albumDelete()
	{
		$albumId          = intval( $this->request['albumId'] );
		$move_to_album_id = intval( $this->request['move_to_album_id'] );
		$moveToAlbum      = array();
		$doDelete		  = intval( $this->request['doDelete'] );
		$album            = $this->_albums->fetchAlbum( $albumId );
	
		/* Are we deleting the images or moving them? */
		if ( $move_to_album_id && ! $doDelete )
		{
			$moveToAlbum = $this->_albums->fetchAlbum( $move_to_album_id );
		}
		
		/* Fetch parents of album before its removed */
		$parents = $this->_albums->fetchAlbumParents( $albumId );
		
		/* Delete album */
		$result = $this->registry->gallery->helper('moderate')->deleteAlbum( $albumId, $moveToAlbum );
		
		/* Recache */
		$this->cacheAttachToForum();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['albums_removed_msg'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Displays the edit album form
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function albumForm( $type='add' )
	{
		/* Init */
		$albumId   = intval( $this->request['albumId'] );
		$albumType = ( $this->request['albumType'] == 'global' ) ? 'global' : 'member';
		$isGlobal  = ( $albumType == 'global' ) ? 1 : 0;
		$owner     = array( 'member_display_name' => '' );
		$form      = array();
		
		/* elements */
		$dd_is_public = array( array( 0, 'Owner Only' ),
							   array( 1, 'Public Album'),
							   array( 2, 'Owner Friends Only' ) );
							   
		/* elements */
		$dd_container = array( array( 0, $this->lang->words['album_mode_all'] ),
							   array( 1, $this->lang->words['album_mode_album'] ),
							   array( 2, $this->lang->words['album_mode_img'] ) );
							   
		/* Sort Options */
		$sort_options = array( array( 'idate'	, $this->lang->words['album_sort_idate'] ),
						       array( 'views'	, $this->lang->words['album_sort_views'] ),
						       array( 'comments', $this->lang->words['album_sort_comments'] ),
						       array( 'rating'	, $this->lang->words['album_sort_rating'] ),
						       array( 'caption_seo', $this->lang->words['album_sort_name'] )
							  );
		
		/* Order Options */
		$order_options = array( array( 'ASC' , $this->lang->words['album_sort_asc'] ),
						        array( 'DESC', $this->lang->words['album_sort_desc'] ) );
		
		if ( $type == 'edit' )
		{
			$album     = $this->_albums->fetchAlbumsById( $albumId );
			$albumType = ( $this->_albums->isGlobal( $album ) ) ? 'global' : 'member';
			
			if ( $album['album_owner_id'] )
			{
				$owner = IPSMember::load( $album['album_owner_id'] );
			}
		}
		else
		{
			$album = array( 'album_parent_id' => 0, 'album_is_global' => $isGlobal, 'album_is_public' => ( $isGlobal ) ? 0 : 1, 'album_owner_id' => ( $isGlobal ) ? 0 : $this->memberData['member_id'], 'album_watermark' => 0, 'album_can_tag' => 1 );
		}
		
		/* Sort our other data.. */
		$showGlobalParentsOnly = ( $isGlobal ) ? 1 : null;
		$ownerId			   = ( $album['album_owner_id'] ) ? $album['album_owner_id'] : null;
		
		$order = ( IPSLib::isSerialized( $album['album_sort_options'] ) ) ? unserialize( $album['album_sort_options'] ) : array();		
		
		$_options = array( 'bypassPermissionChecks' => true, 'forceRootOption' => $isGlobal, 'memberData' => array( 'member_id' => $ownerId ), 'album_is_global' => $showGlobalParentsOnly, 'skip' => $albumId, 'selected' => $album['album_parent_id'] );
		
		/* Form Elements */
		$form['album_name']		         = $this->registry->output->formInput( 'album_name', ( ! empty( $this->request['album_name'] ) ) ? $this->request['album_name'] : $album['album_name'] );
		$form['album_description']       = $this->registry->output->formTextarea( 'album_description', $this->request['album_description'] ? $this->request['album_description'] : IPSText::br2nl( $album['album_description'] ) );
		$form['album_is_public']         = $this->registry->output->formDropdown( 'album_is_public', $dd_is_public, ( ! empty( $this->request['album_is_public'] ) ) ? $this->request['album_is_public'] : $album['album_is_public'] ); 
		$form['album_owner_id__name']    = $this->registry->output->formInput( 'album_owner_id__name', $owner['members_display_name'], 'album_owner_autocomplete' );
		$form['album_sort_options__key'] = $this->registry->output->formDropdown( 'album_sort_options__key', $sort_options, ( ! empty( $this->request['album_sort_options__key'] ) ) ? $this->request['album_sort_options__key'] : $order['key'] ); 
		$form['album_sort_options__dir'] = $this->registry->output->formDropdown( 'album_sort_options__dir', $order_options, ( ! empty( $this->request['album_sort_options__dir'] ) ) ? $this->request['album_sort_options__dir'] : $order['dir'] ); 
		$form['album_detail_default']	 = $this->registry->output->formYesNo( 'album_detail_default', ( ! empty( $this->request['album_detail_default'] ) )  ? $this->request['album_detail_default']  : $album['album_detail_default'] );
		$form['album_allow_comments']	 = $this->registry->output->formYesNo( 'album_allow_comments'  , ( ! empty( $this->request['album_allow_comments'] ) ) ? $this->request['album_allow_comments'] : $album['album_allow_comments'] );
		
		/* Tags */
		$form['album_can_tag']	         = $this->registry->output->formYesNo( 'album_can_tag'  , ( ! empty( $this->request['album_can_tag'] ) ) ? $this->request['album_can_tag'] : $album['album_can_tag'] );
		$form['album_preset_tags']       = $this->registry->output->formTextarea("album_preset_tags", IPSText::br2nl( !empty($_POST['album_preset_tags']) ? $_POST['album_preset_tags'] : $album['album_preset_tags'] ) );
		
		/* Global albums */
		$form['album_g_approve_img']	= $this->registry->output->formYesNo( 'album_g_approve_img'   , ( ! empty( $this->request['album_g_approve_img'] ) )  ? $this->request['album_g_approve_img']  : $album['album_g_approve_img'] );
		$form['album_g_approve_com']	= $this->registry->output->formYesNo( 'album_g_approve_com'   , ( ! empty( $this->request['album_g_approve_com'] ) )  ? $this->request['album_g_approve_com']  : $album['album_g_approve_com'] );
		$form['album_g_container_only']	= $this->registry->output->formDropdown( 'album_g_container_only', $dd_container, ( ! empty( $this->request['album_g_container_only'] ) )  ? $this->request['album_g_container_only']  : $album['album_g_container_only'] );
		
		/* Rules */
		$rules = ( IPSLib::isSerialized( $album['album_g_rules'] ) ) ? unserialize( $album['album_g_rules'] ) : array();
		
		$form['album_g_rules__title'] = $this->registry->output->formInput( 'album_g_rules__title', ( ! empty( $this->request['album_g_rules__title'] ) ) ? $this->request['album_g_rules__title'] : $rules['title'], '', 50 );
		$form['album_g_rules__desc']  = $this->registry->output->formTextarea( 'album_g_rules__desc', $this->request['album_g_rules__desc'] ? $this->request['album_g_rules__desc'] : IPSText::br2nl( $rules['text'] ), 55 );
		
		/* Watermark */
		if ( $albumType == 'global' )
		{
			$wmOptions = array( array( 0, $this->lang->words['wm_dont_watermark'] ),
								array( 1, $this->lang->words['wm_optional_watermark'] ),
								array( 2, $this->lang->words['wm_force_watermark'] ),
								array( 3, $this->lang->words['wm_force_option_watermark'] )
								);
		}
		else
		{
			$wmOptions = array( array( 0, $this->lang->words['wm_dont_watermark'] ),
								array( 1, $this->lang->words['wm_apply_member_choose'] ),
								array( 2, $this->lang->words['wm_apply_member_force'] )
								);
		}
		
		$form['album_watermark'] = $this->registry->output->formDropdown( 'album_watermark', $wmOptions, empty($this->request['album_watermark']) ? $album['album_watermark'] : $this->request['album_watermark'] );
		
		/* Attach to forum */
		$this->registry->class_forums->strip_invisible = true;
		$this->registry->class_forums->forumsInit();
		
		/* fetch children of categories */
		$forums = array( 0 => array( 0, $this->lang->words['albums_parent_none'] ) );
		
		if ( is_array( $this->registry->class_forums->forum_cache['root'] ) and count( $this->registry->class_forums->forum_cache['root'] ) )
		{
			foreach( $this->registry->class_forums->forum_cache['root'] as $id => $data )
			{
				$catName = $data['name'];
				
				if ( is_array( $this->registry->class_forums->forum_cache[ $id ] ) and count( $this->registry->class_forums->forum_cache[ $id ] ) )
				{
					foreach( $this->registry->class_forums->forum_cache[ $id ] as $_id => $_data )
					{
						$forums[] = array( $_id, '[' . $catName . '] ' . $_data['name'] );
					}
				}
			}
		}
		
		$form['album_after_forum_id'] = $this->registry->output->formDropdown( 'album_after_forum_id', $forums, ( ! empty( $this->request['album_after_forum_id'] ) ) ? $this->request['album_after_forum_id'] : $album['album_after_forum_id'] ); 
		
		/* Get the global permission class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions = new $classToLoad( ipsRegistry::instance() );
		$form['neo_morpheus_trinity'] = $permissions->adminPermMatrix( 'albums', $album, 'gallery', '', false );
		
		/* Output */
		$this->registry->output->html .= $this->html->albumForm( $album, $form, $type, $albumType );		
	}	
	
	/**
	 * Handles the edit album form
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function albumSave( $type='add' )
	{
		/* ID */
		$albumId   = intval( $this->request['albumId'] );
		$albumType = ( $this->request['albumType'] == 'global' ) ? 'global' : 'member';
		$owner     = array();
		
		/* Other data */
		$album_owner_id__name = trim( $this->request['album_owner_id__name'] );
		$album_g_rules__title = trim( $this->request['album_g_rules__title'] );
		$album_g_rules__desc  = trim( nl2br( IPSText::stripslashes( $_POST['album_g_rules__desc'] ) ) );
		
		/* Fetch album */
		$album = $this->_albums->fetchAlbumsById( $albumId );
		
		/* Update array */
		$save = array( 'album_name'				=> trim( $this->request['album_name'] ),
					   'album_description'		=> trim( $this->request['album_description'] ),
					   'album_parent_id'		=> intval( $this->request['album_parent_id'] ),
					   'album_is_public'		=> intval( $this->request['album_is_public'] ),
					   'album_g_container_only'	=> intval( $this->request['album_g_container_only'] ),
					   'album_allow_comments'	=> intval( $this->request['album_allow_comments'] ),
					   'album_g_approve_com'	=> intval( $this->request['album_g_approve_com'] ),
					   'album_g_approve_img'	=> intval( $this->request['album_g_approve_img'] ),
					   'album_sort_options'		=> serialize( array( 'key' => $this->request['album_sort_options__key'], 'dir' => $this->request['album_sort_options__dir'] ) ),
					   'album_detail_default'	=> intval( $this->request['album_detail_default'] ),
					   'album_after_forum_id'	=> intval( $this->request['album_after_forum_id'] ),
					   'album_watermark'		=> intval( $this->request['album_watermark'] ),
					   'album_can_tag'			=> intval( $this->request['album_can_tag'] ),
					   'album_preset_tags'		=> trim( $this->request['album_preset_tags'] )
					  );
		
		/* Fetch user */
		if ( $albumType == 'member' )
		{
			/* No parent ID? that's bad! :O */
			if ( !$save['album_parent_id'] )
			{
				$this->registry->output->global_error = $this->lang->words['error_no_parent_album'];
				$this->albumForm( $type );
				return;
			}
			
			/* Make long position */
			$save['album_position'] = IPS_UNIX_TIME_NOW;
			
			/* Check owner */
			$owner = ( $type == 'edit' && ! $album_owner_id__name ) ? IPSMember::load( $album['album_owner_id'], 'all' ) : IPSMember::load( $album_owner_id__name, 'all', 'displayname' );
			
			if ( $owner['member_id'] )
			{
				$save['album_owner_id'] = $owner['member_id'];
			}
			else
			{
				$this->registry->output->global_error = $this->lang->words['error_no_user'];
				$this->albumForm( $type );
				return;
			}
		}
		else
		{
			$save['album_g_rules'] = serialize( array( 'title' => $album_g_rules__title, 'text'  => $album_g_rules__desc ) );
		}
		
		/* Push through to save */
		if ( $type == 'edit' )
		{
			if( ! $album['album_id'] )
			{
				$this->registry->output->showError( $this->lang->words['albums_edit_noid'], 1172 );
			}
		
			/* Is this a global album? */
			if ( $this->_albums->isGlobal( $album ) )
			{
				$save['album_is_public'] = 0;
			}
		
			$this->_albums->save( array( $albumId => $save ) );
		}
		else
		{
			$save['album_is_global'] = ( $albumType == 'global' ) ? 1 : 0;
			$save['album_is_public'] = ( $albumType == 'global' ) ? 0 : 1;
			
			try
			{
				$newAlbum = $this->registry->gallery->helper('moderate')->createAlbum( $save );
				
				$albumId  = $newAlbum['album_id'];
				
				if ( $save['album_is_global'] )
				{
					/* Save the permissions */
					
					/* @todo Introduce a callback in 3.2.0 perm system to handle this
					   Check to see if we need to recache permissions */
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
					$permissions = new $classToLoad( ipsRegistry::instance() );
					$permissions->savePermMatrix( $this->request['perms'], $albumId, 'albums', 'gallery' );
				}
			}
			catch( Exception $e )
			{
				$this->registry->output->showError( $this->lang->words['exception_' . $e->getMessage() ], '1172x1' );
			}
		}
	
		if ( $type == 'edit' )
		{
			$test = $this->_albums->fetchAlbumsById( $album['album_id'], GALLERY_ALBUM_FORCE_LOAD );
		
			# Trim leading / trailing commas
			$save = array();
			
			foreach( array( 'album_g_perms_view', 'album_g_perms_images', 'album_g_perms_comments', 'album_g_perms_moderate' ) as $field )
			{
				$save[ $field ] = trim( $test[ $field ], ',' );	
			}
			
			$this->DB->update( 'gallery_albums_main', $save, 'album_id=' . $album['album_id'] );
			
			/* Global things only in there ;) */
			if ( $this->_albums->isGlobal( $test ) )
			{
				/* Save the permissions */
				
				/* @todo Introduce a callback in 3.2.0 perm system to handle this
				   Check to see if we need to recache permissions */
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
				$permissions = new $classToLoad( ipsRegistry::instance() );
				$permissions->savePermMatrix( $this->request['perms'], $albumId, 'albums', 'gallery' );
				
				if ( ( $test['album_g_perms_view'] != '*' AND $test['album_g_perms_view'] != $album['album_g_perms_view'] ) || ( $test['album_g_perms_images'] != '*' AND $test['album_g_perms_images'] != $album['album_g_perms_images'] ) )
				{
					$this->registry->gallery->helper('moderate')->resetPermissions( $album['album_id'] );
				}
			}
		}
		
		/* Recache */
		$this->cacheAttachToForum();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['albums_edit_msg'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Recache album id > forum id stuff
	 * 
	 * @return	@e void
	 */
	public function cacheAttachToForum()
	{
		$cache = array();
		
		/* Fetch data */
		$this->DB->build( array( 'select' => 'album_id, album_after_forum_id',
								 'from'   => 'gallery_albums_main',
								 'where'  => 'album_after_forum_id != 0' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$cache[ $row['album_after_forum_id'] ][] = $row['album_id'];	
		}
		
		$this->cache->setCache( 'gallery_fattach', $cache, array( 'array' => 1, 'donow' => 0 ) );
	}
}