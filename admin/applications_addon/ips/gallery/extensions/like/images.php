<?php

/**
 * Gallery Like class (Images)
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9060 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class like_gallery_images_composite extends classes_like_composite
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
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
	/**#@-*/
	
	/**
	 * CONSTRUCTOR
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
	 * return type of notification available for this item
	 * 
	 * @return	@e array
	 */
	public function getNotifyType()
	{
		return array( 'comments', $this->lang->words['comments_lower'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_image';
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
	 * @param	mixed	Relationship ID or array of
	 * @param	array	Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
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
		
		$this->DB->build( array( 'select' => 'i.*',
								 'from'   => array( 'gallery_images' => 'i' ),
								 'where'  => 'i.id IN (' . implode( ',', $relId ) . ')',
								 'add_join' => array( array( 'select' => 'a.*',
															 'from'   => array( 'gallery_albums_main' => 'a' ),
															 'where'  => 'i.img_album_id=a.album_id',
															 'type'   => 'left'  ) ) ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.title'] = $row['caption'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.url'] = $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $row['id'], "public", $row['caption_seo'], "viewimage" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.type'] = 'Image';
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.parentTitle'] = $row['album_name'];
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.parentUrl'] = $this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $row['album_id'], "public", $row['album_name_seo'], "viewalbum" );
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['id'] ]['like.parentType'] = 'Album';
			} 
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
}