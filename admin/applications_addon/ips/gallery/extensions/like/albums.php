<?php
/**
 * @file		albums.php 	Albums like class (gallery application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-06-17 08:12:46 -0400 (Fri, 17 Jun 2011) $
 * @version		v4.2.1
 * $Revision: 9060 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		like_gallery_albums_composite
 * @brief		Albums like class (gallery application)
 */
class like_gallery_albums_composite extends classes_like_composite
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		if ( ! $this->registry->isClassLoaded('gallery') )
		{
			/* Gallery Object */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		$this->lang->loadLanguageFile( array( 'public_gallery_four' ), 'gallery' );
	}
	
	/**
	 * Return an array of acceptable frequencies
	 * Possible: immediate, offline, daily, weekly
	 * 
	 * @return	@e array
	 */
	public function allowedFrequencies()
	{
		return array( 'immediate', 'offline' );
	}
	
	/**
	 * Return types of notification available for this item
	 * 
	 * @return	@e array	array( key, human readable )
	 */
	public function getNotifyType()
	{
		return array( 'images', $this->lang->words['images_lower'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_album';
	}
	
	/**
	 * Fetch the template group
	 * 
	 * @return	@e string
	 */
	public function skin()
	{
		return 'gallery_global';
	}
	
	/**
	 * Returns the type of item
	 * 
	 * @param	mixed		$relId			Relationship ID or array of IDs
	 * @param	array		$selectType		Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
	 * @return	@e array	Meta data
	 */
	public function getMeta( $relId, $selectType=null )
	{
		$return    = array();
		$isNumeric = false;
		
		if ( is_numeric( $relId ) )
		{
			$relId     = array( intval($relId) );
			$isNumeric = true;
		}
		
		$this->DB->build( array( 'select' => 'a.*',
								 'from'   => array( 'gallery_albums_main' => 'a' ),
								 'where'  => 'a.album_id IN (' . implode( ',', $relId ) . ')',
								 'add_join' => array( array( 'select' => 'p.album_id as parent_album_id, p.album_name as parent_album_name, p.album_name_seo as parent_album_name_seo',
															 'from'   => array( 'gallery_albums_main' => 'p' ),
															 'where'  => 'p.album_id=a.album_parent_id',
															 'type'   => 'left'  ) ) ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.title'] = $row['album_name'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.url'] = $this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $row['album_id'], "public", $row['album_name_seo'], "viewalbum" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.type'] = 'Album';
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.parentTitle'] = ( ! empty( $row['parent_album_name'] ) ) ? $row['parent_album_name'] : null;
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.parentUrl'] = ( ! empty( $row['parent_album_name'] ) ) ? $this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $row['parent_album_id'], "public", $row['parent_album_name_seo'], "viewalbum" ) : null;
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['album_id'] ]['like.type'] = 'Album';
			} 
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
	
	/**
	 * Return the title based on the passed id
	 * 
	 * @param	integer		$relId		Relationship ID
	 * @return	@e string	Title
	 */
	public function getTitleFromId( $relId )
	{
		$meta = $this->getMeta( $relId, array( 'title' ) );
		
		if ( is_numeric( $relId ) )
		{
			return $meta[ $relId ]['like.title'];
		}
		else
		{
			$return = array();
			
			foreach( $meta as $id => $data )
			{
				$return[ $id ] = $data['like.title'];
			}
			
			return $return;
		}
	}

	/**
	 * Return the URL based on the passed id
	 * 
	 * @param	integer		$relId		Relationship ID
	 * @return	@e string	URL
	 */
	public function getUrlFromId( $relId )
	{
		$meta = $this->getMeta( $relId, array( 'url' ) );
		
		if ( is_numeric( $relId ) )
		{
			return $meta[ $relId ]['like.url'];
		}
		else
		{
			$return = array();
			
			foreach( $meta as $id => $data )
			{
				$return[ $id ] = $data['like.url'];
			}
			
			return $return;
		}
	}
}