<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Bing mapping class
 * Owner: Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10721 $
 */

class classes_mapping_bing extends classes_mapping_composite
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
	
	/**
	 * Method constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Return application key
	 * @return string
	 */
	public function fetchApiKey()
	{
		return defined( 'BING_API_KEY' ) ? BING_API_KEY : false;
	}
	
	//$result = $file->getFileContents( "http://where.yahooapis.com/geocode?appid=" . $this->getYahooAppId() . "&location=" . $lat . "," . $lon . "&flags=J&gflags=R&locale=" . YAHOO_LOCALE );
	
	/**
	 * Reverse geocode look up
	 * I know. Seriously. This is really technical.
	 * 
	 * @access	public
	 * @param	int		Lat
	 * @param	int		Lon
	 * @return	array	Decoded JSON in an array
	 */
	public function liveReverseLookUp( $lat, $lon )
	{
		if ( $this->enabled() !== true )
		{
			return false;
		}
		
		/* Lets do it. */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classFileManagement.php', 'classFileManagement' );
		$file = new $classToLoad();
		
		/* fetch */
		$json = $file->getFileContents( "http://dev.virtualearth.net/REST/v1/Locations/" . $lat . "," . $lon . "?&key=" . BING_API_KEY );
		
		if ( strstr( $json, ':{"' ) )
		{
			$array = @json_decode( $json, TRUE );
			
			if ( is_array( $array ) AND ! empty( $array['statusCode'] ) AND $array['statusCode'] == 200 )
			{
				return $array['resourceSets'][0]['resources'][0];
			}
		}
		
		return false;
	}
	
	/**
	 * 
	 * Parses the result of the look up to return info we need
	 * @param array $array
	 * @return array $return
	 */
	public function formatReverseLookUp( array $array )
	{
		$return = array( 'country'   => '',
						 'district'	 => '',
						 'distrcit2' => '',
						 'locality'  => '',
						 'short'     => '',
						 'type'		 => 'address',
						 'engine'    => 'bing' );
		
		if ( is_array( $array ) )
		{
			$return['country']   = $array['address']['countryRegion'];
			$return['district']	 = $array['address']['adminDistrict'];
			$return['district2'] = $array['address']['adminDistrict2'];
			$return['locality']  = $array['address']['locality'];
			$return['short']     = $this->_getShort( $return );
		}
		
		return $return;
	}
	
	/**
	 * Returns a URL for the main map (click event)
	 * @param string $lat
	 * @param string $lon
	 * @return	string URL
	 */
	public function getMapUrl( $lat, $lon )
	{
		return 'http://www.bing.com/maps/?v=2&encType=1&where1=' . urlencode( $lat . ',' . $lon );
	}
	
	/*
	 * Fetch image URLS
	 * Returns an array with 2 image URLs for use next to the image
	 * Yes.
	 * 
	 * @access public
	 * @param	string	lat
	 * @param	string	lon
	 * @param	string	Image size (200x200 is default)
	 */
	public function getImageUrls( $lat, $lon, $size='200x200' )
	{
		/* Init */
		$_size  = str_replace( 'x', ',', $size );
		$latlon = $lat . ',' . $lon;
		
		$base   = "http://dev.virtualearth.net/REST/v1/Imagery/Map/Road/%s/%s/?mapSize=%s&pp=%s;9;A&key=" . $this->fetchApiKey();
		
		$image0 = sprintf( $base, $latlon, 7, $_size, $latlon );
		$image1 = sprintf( $base, $latlon, 11, $_size, $latlon );
		
		/* Try from cache */
		$images = $this->_getImagesFromCache( $lat, $lon, $size );
		
		if ( $images === false )
		{
			/* Fetch live */
			if ( $_data_0 = @file_get_contents( $image0 ) )
			{
				$_data_1 = @file_get_contents( $image1 );
				
				/* Attempt to write cache */
				if ( $this->_setImageCache( $lat, $lon, $size, 0, $_data_0 ) !== false )
				{
					$this->_setImageCache( $lat, $lon, $size, 1, $_data_1 );
				}						
			}
		}
		else
		{
			return array( $images[0], $images[1] );
		}
		
		return array( $image0, $image1 );
	}
	
	/**
	 * Returns formatted address
	 * 
	 * @param array $return
	 * @return string
	 */
	protected function _getShort( $return )
	{
		if ( $return['locality'] != $return['district2'] )
		{
			$short = $return['locality'] . ', ' . $return['district2'] . ', ' . $return['country'];
		}
		else
		{
			$short = $return['locality'] . ', ' . $return['country'];
		}
		
		return $short;
	}
	
}