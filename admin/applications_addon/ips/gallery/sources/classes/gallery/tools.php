<?php
/**
 * Main/Rate
 *
 * Used to rate an image
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link			http://www.invisionpower.com
 * @version		$Rev: 9982 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_tools
{
	/**
	 * Stored error message
	 *
	 * @access	public
	 * @var		string
	 */
	private $errorMessage;
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */	
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		$this->albums     = $this->registry->gallery->helper('albums');
		$this->images	  = $this->registry->gallery->helper('image');
	}
	
	/**
	 * Return error message
	 * @return	@e string
	 */
	public function getError()
	{
		return $this->errorMessage;
	}
	
	/**
	 * Restore album rules
	 * @return	@e boolean
	 */
	public function restoreAlbumRules()
	{
		$offset = 0;
		$max    = array( 'maxx' => 0 );
		
		if ( ! $this->DB->checkForTable('gallery_categories') OR ! $this->DB->checkForTable('gallery_albums') )
		{
			$this->errorMessage = $this->lang->words['tools_no_old_album_table'];
			return false;
		}
		
		/* continue, my dear */
		/* As we don't store old > new IDs, we do some juggling */
		$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(id) as maxx',
										        'from'   => 'gallery_albums' ) );
		
		$max['maxx'] = intval( $max['maxx'] );
		
		/* Pick through and count offset */
		$this->DB->build( array( 'select' => '*',
						  		 'from'   => 'gallery_categories' ) );
		
		$o = $this->DB->execute();
		
		while( $cat = $this->DB->fetch( $o ) )
		{
			$offset++;
			
			$albumIdShouldBeHopefully = $max['maxx'] + $offset;
			
			if ( $cat['cat_rule_text'] )
			{
				/* Fetch album */
				$album = $this->albums->fetchAlbumsById( $albumIdShouldBeHopefully );
				
				if ( $album['album_id'] AND $this->albums->isGlobal( $album ) )
				{
					$this->albums->save( array( 'album_id'      => $album['album_id'],
												'album_g_rules' => serialize( array( 'title' => $cat['cat_rule_title'],
																					 'text'  => $cat['cat_rule_text'] ) ) ) );
				}
			} 
		}
		
		return true;
	}
	
	/**
	 * Rebuild tree information
	 * Stores child/parent IDs in a serialized array
	 * 
	 * @param	int		Album ID
	 */
	public function rebuildTree( $albumId )
	{
		$childTree  = array();
		$parentTree = array();
		$album      = $this->albums->fetchAlbumsById( $albumId, true );
		$parents    = $this->albums->fetchAlbumParents( $albumId );
		$children   = array();
		
		if ( ! $album['album_id'] )
		{
			return;
		}
		
		/* OMG do not attempt this with kid's album */
		if ( $albumId != $this->albums->getMembersAlbumId() )
		{
			$children = $this->albums->fetchAlbumsByFilters( array( 'album_parent_id' => $albumId, 'bypassPermissionChecks' => 1 ) );
		}
		else
		{
			$children = $this->albums->fetchAlbumsByFilters( array( 'album_is_global' => 1, 'album_parent_id' => $albumId, 'bypassPermissionChecks' => 1 ) );
		}
		
		if ( count( $children ) )
		{
			$tree = array_keys( $children );
		}
		
		if ( $album['album_parent_id'] )
		{
			$parents[ $album['album_parent_id'] ] = $album['album_parent_id'];
		}
		
		if ( count( $parents ) )
		{
			$parents = array_reverse( $parents, true );
			
			$parentTree = array_keys( $parents );
		}
		
		if ( count( $children ) )
		{
			$childTree = array_keys( $children );
		}
		
		$this->DB->update( 'gallery_albums_main', array( 'album_child_tree' => serialize( $childTree ), 'album_parent_tree' => serialize( $parentTree ) ), 'album_id=' . $album['album_id'] );
		
		/* Update parents */
		$pIds = array_keys( $parents );
		
		if ( is_array( $album['album_parent_tree'] ) )
		{
			/* Old parents */
			$pIds = array_unique( array_merge( $pIds, array_values( $album['album_parent_tree'] ) ) );
		}
		
		foreach( $pIds as $id )
		{
			$this->rebuildTree( $id );
		}
		
	}
	 
}