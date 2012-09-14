<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Generic Mapping Class
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

/**
 * Factory class
 *
 * @author matt
 *
 */
class classes_mapping
{
	/**
	 * App object
	 * @var object
	 */
	static protected $_app;
	
	/**
	 * Construct
	 */
	static public function bootstrap( $engine )
	{
		if ( ! $engine )
		{
			trigger_error( "No engine specified for class_mapping", E_USER_WARNING );
		}
		
		/* Pointless comment! */
		$_file  = IPS_ROOT_PATH . 'sources/classes/mapping/engines/' . $engine . '.php';
		
		if ( is_file( $_file ) )
		{
			$classToLoad = IPSLib::loadLibrary( $_file, 'classes_mapping_' . $engine );
			
			if ( class_exists( $classToLoad ) )
			{
				self::$_app = new $classToLoad();
			}
			else
			{
				throw new Exception( "No mapping engine class available for $engine" );
			}
		}
		else
		{
			throw new Exception( "No mapping engine class available for $engine" );
		}
		
		return self::$_app;
	}
	
}

/**
 * Composite class
 */
abstract class classes_mapping_composite
{
	/**
	 * 
	 * Location services available flag
	 * @return	boolean
	 */
	public function enabled()
	{
		return ( $this->fetchApiKey() ) ? true : false;
	}
	
	/**
	 * Return application key
	 * @return string
	 */
	public function fetchApiKey()
	{
		return false;
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
	public function reverseGeoCodeLookUp( $lat, $lon )
	{
		if ( $this->enabled() !== true )
		{
			return false;
		}
		
		/* First check the DB, this is bound to be cheaper than sockets and Yahoo APIs
		 * and it's likely that in an album of images one has already has set the geo location */
		$test = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'core_geolocation_cache',
												 'where'  => 'geocache_key=\'' . $this->getDbKey( $lat, $lon ) . '\'',
												 'limit'  => array( 0, 1 ) ) );
		
		if ( ! empty( $test['geocache_country'] ) )
		{
			return $test;
		}
		
		/* Still here? Oh well, lets do it. */ 
		$data      = $this->liveReverseLookUp( $lat, $lon );
		
		if ( ! is_array( $data ) )
		{
			return null;
		}
	
		$formatted = $this->formatReverseLookUp( $data );
		
		/* Save to db */
		if ( $formatted !== false && ! empty( $formatted['country'] ) )
		{
			$save = array( 'geocache_key'       => $this->getDbKey( $lat, $lon ),
						   'geocache_lat'	    => $lat,
						   'geocache_lon'	    => $lon,
						   'geocache_raw'		=> serialize( $data ),
						   'geocache_country'   => $formatted['country'],
						   'geocache_district'  => $formatted['district'],
						   'geocache_district2' => $formatted['district2'],
						   'geocache_locality'  => $formatted['locality'],
						   'geocache_type'      => $formatted['type'],
						   'geocache_engine'    => $formatted['engine'],
						   'geocache_added'	    => time(),
						   'geocache_short'	    => $formatted['short'] );
			
			$this->DB->replace( 'core_geolocation_cache', $save, array( 'geocache_key' ) );
			
			return $save;
		}
		else
		{
			return false;
		}
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
		return array( false, false );
	}
	
	/**
	 * 
	 * Parses the result of the look up to return info we need
	 * @param array $array
	 * @return array $return
	 */
	public function formatReverseLookUp( array $array )
	{	
		return $array;
	}
	
	/**
	 * Returns a URL for the main map (click event)
	 * @param string $lat
	 * @param string $lon
	 * @return	string URL
	 */
	public function getMapUrl( $lat, $lon )
	{
		return false;
	}
	
	/**
	 * Make a key for the DB
	 * @param string $lat
	 * @param string $lon
	 * @return string
	 */
	protected function getDbKey( $lat, $lon )
	{
		return md5( trim($lat) . ',' . trim($lon) );
	}
	
	/**
	 * Attempt to get images from the cache
	 * @param string $lat
	 * @param string $lon
	 * @param string $size
	 */
	protected function _getImagesFromCache( $lat, $lon, $size )
	{
		$file_0 = md5( $lat . ',' . $lon . ',' . $size ) . '_0.png';
		$file_1 = md5( $lat . ',' . $lon . ',' . $size ) . '_1.png';
		
		if ( is_file( $this->_getCachePath() . '/' . $file_0 ) && is_file( $this->_getCachePath() . '/' . $file_1 ) )
		{
			return array( $this->_getCacheUrl() . '/' . $file_0, $this->_getCacheUrl() . '/' . $file_1 );
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Write images to cache
	 * @param string $lat
	 * @param string $lon
	 * @param string $size
	 */
	protected function _setImageCache( $lat, $lon, $size, $key, $data )
	{
		if ( ! is_dir( $this->settings['upload_dir'] . '/maps' ) )
		{
			@mkdir( $this->settings['upload_dir'] . '/maps', IPS_FOLDER_PERMISSION );
			@chmod( $this->settings['upload_dir'] . '/maps', IPS_FOLDER_PERMISSION );
		}
		
		$filename = md5( $lat . ',' . $lon . ',' . $size ) . '_' . $key;
	
		/* Extension less */
		if ( @file_put_contents( $this->_getCachePath() . '/' . $filename, $data ) )
		{
			$data = @getimagesize( $this->_getCachePath() . '/' . $filename );
			
			if ( ! is_array( $data ) OR ! count( $data ) )
			{
				unlink( $this->_getCachePath() . '/' . $filename );
				return false;
			}
			else
			{
				if ( $data['mime'] == 'image/png' )
				{
					/* Add ext */
					if ( ! @rename( $this->_getCachePath() . '/' . $filename, $this->_getCachePath() . '/' . $filename . '.png' ) )
					{
						unlink( $this->_getCachePath() . '/' . $filename );
						return false;
					}
				}
				else
				{
					if ( $data['mime'] == 'image/jpg' )
					{
						$gd = @imagecreatefromjpeg( $this->_getCachePath() . '/' . $filename );
						
						if ( ! @imagepng( $gd, $this->_getCachePath() . '/' . $filename . '.png' ) )
						{
							unlink( $this->_getCachePath() . '/' . $filename );
							return false;
						}
					}
					else if ( $data['mime'] == 'image/gif' )
					{
						$gd = @imagecreatefromgif( $this->_getCachePath() . '/' . $filename );
						
						if ( ! @imagepng( $gd, $this->_getCachePath() . '/' . $filename . '.png' ) )
						{
							unlink( $this->_getCachePath() . '/' . $filename );
							return false;
						}
					}
				}
			}
			
		}
		else
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns cache path
	 * @return string
	 */
	protected function _getCachePath()
	{
		return ( is_dir( $this->settings['upload_dir'] . '/maps' )  ) ? $this->settings['upload_dir'] . '/maps' : $this->settings['upload_dir'];
	}
	
	/**
	 * Returns cache path
	 * @return string
	 */
	protected function _getCacheUrl()
	{
		return ( is_dir( $this->settings['upload_dir'] . '/maps' )  ) ? $this->settings['upload_url'] . '/maps' : $this->settings['upload_url'];
	}
}