<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Image Ajax
 * Last Updated: $LastChangedDate: 2011-10-03 18:58:06 +0100 (Mon, 03 Oct 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9574 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_albumSelector extends ipsAjaxCommand
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
			default:
			case 'albumSelector':
				$this->_albumSelectorSplash();
			break;
			case 'albumSelectorPane':
				$this->_albumSelectorPane();
			break;
			case 'select':
				$this->_select();
			break;
        }
    }
    
    /**
     * Item has been selected
     */
    public function _select()
    {
    	$albumId    = intval( $this->request['album_id'] );
    	$parentText = '';
    	
    	if ( $albumId )
    	{
    		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
    	
    		$album['status']     = 'ok';
    		$album['allParents'] = $this->registry->gallery->helper('albums')->getParentsAsTextString( $album );
    		
    		/* Store in cache */
    		$cache = explode( ',', ipsMember::getFromMemberCache( $this->memberData, 'gallery_recentSelects' ) );
    		
    		array_unshift( $cache, $albumId );
    		
    		$cache = array_slice( array_unique( $cache ), 0, 25 );
    		
    		ipsMember::setToMemberCache( $this->memberData, array( 'gallery_recentSelects' => implode(',', $cache ) ) );
    		
    		return $this->returnJsonArray( $album );
    	}
    	else
    	{
    		return $this->returnJsonArray( array( 'status' => 'ok', 'album_id' => 0, 'album_name' => $this->lang->words['as_root'] ) );
    	}
    	
    	return $this->returnJsonArray( array( 'status' => 'fail' ) );
    }
    
	/**
	 * Album pane
	 * Actions can be 'edit', 'move', 'delete', 'upload'
	 *
	 * @return	@e void
	 */
	public function _albumSelectorPane( $inline=array() )
	{
		$albums			= array();
		$isGlobal		= null;
		
		/* Pointless comment */
		if ( count( $inline ) )
		{
			foreach( $inline as $k => $v )
			{
				$this->request[ $k ] = $v;
			}
		}
		
		$isAcp          = IN_ACP;
		$type			= trim( $this->request['type'] );
		$parent			= intval( $this->request['parent'] );
		$albums         = trim( $this->request['albums'] );
		$albumId		= intval( $this->request['album_id'] );
		$memberId		= intval( $this->request['member_id'] );
		$moderate		= intval( $this->request['moderate'] );
		$filters		= array();
		$nav            = array();
		$albumData      = array();
		$modAction      = '';
		
		/* define filters */
		switch( $type )
		{
			case 'moveImage':
			case 'moveImages':
				if ( $albums == 'global' )
				{
					$filters['isViewable'] = 1;
					$filters['addUploadableFlag'] = 1;
				}
				else
				{
					$filters['isUploadable'] = 1;
				}
			break;
			case 'move':
				$filters['isUploadable'] = 1;
			break;
			default:
			case 'upload':
				if ( $albums == 'global' )
				{
					$filters['isViewable'] = 1;
					$filters['addUploadableFlag'] = 1;
				}
				else
				{
					$filters['isUploadable'] = 1;
				}
			break;
			case 'edit':
			case 'editAlbum':
				$filters['isUploadable']   = 1;
				$filters['skip']           = array( $albumId );
			break;
			case 'createGlobalAlbum':
				$filters['isCreatable']     = 1;
				$filters['album_is_global'] = 1;
				$filters['skip']		    = array( $this->registry->gallery->helper('albums')->getMembersAlbumId() );
				$this->request['searchIsGlobal'] = 1;
			break;
			case 'createMembersAlbum':
			case 'createAlbum':
			case 'create':
				$filters['isCreatable'] = 1;
			break;
		}
	
		/* If we're in the acp, bypass perm checks */
		if ( $isAcp )
		{
			$filters['bypassPermissionChecks'] = true;
		}
		
		/* Load album */
		if ( $albumId )
		{
			$albumData = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		}
		
		/* Are we modding */
		if ( $moderate || $isAcp )
		{
			$filters['moderatingData'] = array( 'action'    => $type,
												'owner_id'  => $memberId ? $memberId : $albumData['album_owner_id'],
												'moderator' => $this->memberData,
												'album_id'  => $albumData['album_id'] );
		}
		
		/* Fetch the albums */
		if ( $albums == 'global' )
		{
			if ( $parent > 0 )
			{
				$nav = $this->_getNavigation( $parent );
			}
			
			$albums   = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'album_is_global' => 1, 'getChildren' => 'global', 'album_parent_id' => $parent ) ) );
			
			$isGlobal = true;
		}
		else if ( $albums == 'recent' )
		{
			$albumIds = explode( ',', ipsMember::getFromMemberCache( $this->memberData, 'gallery_recentSelects' ) );
			$albums   = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'album_id' => $albumIds, 'sortKey' => 'name', 'sortOrder' => 'asc' ) ) );
		}
		else if ( $albums == 'search' )
		{		
			$searchIsGlobal = intval( $this->request['searchIsGlobal'] );
			$searchText		= trim( IPSText::parseCleanValue( $_REQUEST['searchText'] ) );
			$searchType		= trim( $this->request['searchType'] );
			$searchMatch	= trim( $this->request['searchMatch'] );
			$searchDir		= trim( $this->request['searchDir'] );
			$searchSort		= trim( $this->request['searchSort'] );
			
			/* Reset searchText to the cleaned value for further form input */
			$_REQUEST['searchText'] = $searchText;
			
			/* Lets go */
			$filters['sortOrder']  = $searchDir;
			$filters['sortKey']    = $searchSort;
			$filters['limit']      = 200;
			$filters['getParents'] = true;
			
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
					$filters['album_parent_id'] = intval( $searchText );
					
					$nav = $this->_getNavigation( $searchText );
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
			
			/* Global or not? */
			if ( $searchIsGlobal )
			{
				$filters['album_is_global'] = 1;
			}
			else
			{
				$filters['album_is_global'] = 0;
			}
			
			$albums   = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( $filters );
		}
		else if ( $albums == 'othermember' )
		{
			$albums = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'sortKey' => 'date', 'sortOrder' => 'desc', 'album_is_global' => 0, 'getParents' => true, 'album_owner_id' => $memberId ) ) );
		}
		else
		{
			if ( in_array( $type, array( 'upload', 'createMembersAlbum', 'createAlbum', 'create' ) ) )
			{
				$albums = $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array_merge( $filters, array( 'sortKey' => 'date', 'sortOrder' => 'desc', 'album_is_global' => 0, 'getParents' => true, 'album_owner_id' => $this->memberData['member_id'] ) ) );
			}
		}
		
		$html = $this->registry->output->getTemplate('gallery_albums')->albumSelectorPanel( $albums, $filters, $nav, $albumData );
		
		/* return with wrapper */
		if ( count( $inline) )
		{
			return $html;
		}
		
		return $this->returnHtml( $html );
	}
	
    /**
	 * Album selectOR. Hell Yeah!
	 * Displays allowable global albums and member albums for this action.
	 * Actions can be 'edit', 'move', 'delete', 'upload'
	 *
	 * @return	@e void
	 */
	public function _albumSelectorSplash()
	{
		$albumId   = intval( $this->request['album_id'] );
		$albumData = array();
		
		/* Fetch the albums */
		$this->request['albums'] = 'global';
		
		/* Load album */
		if ( $albumId )
		{
			$albumData = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		}
		
		$recents = explode( ',', ipsMember::getFromMemberCache( $this->memberData, 'gallery_recentSelects' ) );
		
		/* return with wrapper */
		return $this->returnHtml( $this->registry->output->getTemplate('gallery_albums')->albumSelector( $this->_albumSelectorPane( array( 'albums' => 'global' ) ), $recents, $albumData ) );
	}
	
	/**
	 * Returns processed navigation
	 * @param int $parent
	 * @return array 
	 */
	protected function _getNavigation( $parent )
	{
		/* Do the nav, man */
		$parentAlbum = $this->registry->gallery->helper('albums')->fetchAlbumsById( $parent );
		$parents     = $this->registry->gallery->helper('albums')->fetchAlbumParents( $parentAlbum['album_id'] );
		
		$nav[] = array('album_id' => 0, 'album_parent_id' => 0, 'album_name' => $this->lang->words['root_album']);
		
		if ( $parents !== null )
		{
			$parents = array_reverse( $parents, true );
			
			foreach( $parents as $id => $data )
			{
				$nav[] = $data;
			}
			
			$nav[] = array_merge( array( '_last' => 1 ), $parentAlbum );
		}

		return $nav;
	}
}