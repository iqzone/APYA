<?php
/**
 * @file		blog.php 	Blogs like class (blog application)
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		like_blog_blog_composite
 * @brief		Blogs like class (blog application)
 */
class like_blog_blog_composite extends classes_like_composite
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
		
		$this->lang->loadLanguageFile( array( 'public_blog' ), 'blog' );
	}
	
	/**
	 * Fetch the template group
	 * 
	 * @return	@e string
	 */
	public function skin()
	{
		return 'forum';
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
		return array( 'comments', $this->lang->words['blog_like_comments'] );
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'follow_blog';
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

		$this->DB->build( array( 'select' => '*',
								 'from'   => array( 'blog_blogs' => 'b' ),
								 'where'  => 'b.blog_id IN (' . implode( ',', $relId ) . ')',
								));

		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['blog_id'] ]['like.title'] = $row['blog_name'];
			}

			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['blog_id'] ]['like.url'] = $this->registry->output->buildSEOUrl( "app=blog&amp;blogid=" . $row['blog_id'], "public", $row['blog_seo_name'], "showblog" );
			}

			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['blog_id'] ]['like.type'] = $this->lang->words['blog_blog'];
			} 

			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['blog_id'] ]['like.parentTitle'] = $this->lang->words['blog_title'];
			} 

			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['blog_id'] ]['like.parentUrl'] = $this->registry->output->buildSEOUrl( "app=blog", "public" );
			} 

			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['blog_id'] ]['like.parentType'] = $this->lang->words['blog_blog'];
			} 
		}

		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}
}