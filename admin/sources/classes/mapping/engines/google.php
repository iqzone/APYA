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

class classes_mapping_google extends classes_mapping_composite
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
		return true;
	}
		
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
		$json = $file->getFileContents( "http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $lat . ',' . $lon . '&sensor=false' );
		
		if ( $json )
		{
			$array = @json_decode( $json, TRUE );
			
			if ( is_array( $array ) AND ! empty( $array['status'] ) AND strtolower( $array['status'] ) == 'ok' )
			{
				return $array['results'][0];
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
						 'engine'    => 'google' );
		
		if ( is_array( $array ) )
		{
			foreach( $array['address_components'] as $idx => $data )
			{
				$key = $data['types'][0];
				
				switch( $key )
				{
					case 'country':
						$return['country'] = ( $data['long_name'] ) ? $data['long_name'] : $data['short_name'];
					break;
					case 'administrative_area_level_1':
						$return['district'] = ( $data['long_name'] ) ? $data['long_name'] : $data['short_name'];
					break;
					case 'administrative_area_level_2':
						$return['district2'] = ( $data['long_name'] ) ? $data['long_name'] : $data['short_name'];
					break;
					case 'sublocality':
						$return['locality'] = ( $data['long_name'] ) ? $data['long_name'] : $data['short_name'];
					break;
				}
			}
			
			$return['short'] = $this->_getShort( $return );
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
		return 'http://maps.google.com/?zoom=11&q=' . urlencode( $lat . ',' . $lon );
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
		$latlon = $lat . ',' . $lon;
		
		$base   = "http://maps.google.com/maps/api/staticmap?center=%s&zoom=%s&size=%s&maptype=roadmap&markers=color:red|label:A|%s&sensor=false";
		
		$image0 = sprintf( $base, $latlon, 7, $size, $latlon );
		$image1 = sprintf( $base, $latlon, 11, $size, $latlon );
		
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