<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Mobile iOS App image class
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
 * Manages mobile app images for the ACP
 *
 * @author matt
 *
 */
class classes_mobile_iosImages
{
	/**
	 * Image names
	 * @var string
	 */
	protected $_fileNames = array(  'ActionBarBackground',
									'actionButton',
								    'actionButtonActive',
									'backButton',
									'backButtonActive',
									'menuButton',
									'menuButtonActive',
									'NavBarBackground',
									'readIcon',
									'unreadIcon' );
	
	/**
	 * Retina suffix
	 * @var string
	 */
	protected $_retinaSuffix = '@2x';
	
	
	/**
	 * Mobile app folder
	 */
	protected $_imageDir        = '';
	protected $_imageUrl        = '';
	protected $_defaultDir      = '';
	protected $_defaultImageUrl = '';
	
	protected $_cache		    = array( 'default' => array(), 'current' => array() );
	/**
	 * Style last updated timestampe
	 */
	protected $_styleLastUpdated = 0;
	

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		
		/* Set up */
		$this->setImageDir();
		$this->setImageUrl();
		$this->setStyleLastUpdated();
		$this->setDefaultImageDir();
		$this->setDefaultImageUrl();
	}
	
	/**
	 * Sets style last updated
	 * @param	string	(Optional dir)
	 */
	public function setStyleLastUpdated( $time=0 )
	{
		$this->_styleLastUpdated = ( $time ) ? $time : intval( ipsRegistry::$settings['style_last_updated'] );
		
		/* Do we need to update the setting? */
		if ( $time && ( $time > ipsRegistry::$settings['style_last_updated'] ) )
		{
			/* Rebuild DB */
			$this->_refreshDatabase();
			
			/* Update setting */
			IPSLib::updateSettings( array( 'style_last_updated' => $time + 1 ) );
			
			/* Flsuh cache */
			$this->_flushCache();
		}
	}
	
	/**
	 * Get image dir
	 * @return string
	 */
	public function getStyleLastUpdated()
	{
		return $this->_styleLastUpdated;
	}
	
	/**
	 * Sets the default image url
	 * @param	string	(Optional dir)
	 */
	public function setDefaultImageUrl( $url='' )
	{
		$this->_defaultImageUrl = ( $url ) ? $url : ipsRegistry::$settings['public_dir'] . 'style_extra/default_mobile_app/';
	}
	
	/**
	 * Sets the default image dir
	 * @param	string	(Optional dir)
	 */
	public function setDefaultImageDir( $dir='' )
	{
		$this->_defaultDir = ( $dir ) ? $dir : DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_extra/default_mobile_app/';
	}
	
	/**
	 * Sets the image dir
	 * @param	string	(Optional dir)
	 */
	public function setImageDir( $dir='' )
	{
		$this->_imageDir = ( $dir ) ? $dir : DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/mobile_app/';
	}
	
	/**
	 * Sets the image url
	 * @param	string	(Optional dir)
	 */
	public function setImageUrl( $url='' )
	{
		$this->_imageUrl = ( $url ) ? $url : ipsRegistry::$settings['public_dir'] . 'style_images/mobile_app/';
	}
	
	/**
	 * Get default image dir
	 * @return string
	 */
	public function getDefaultImageDir()
	{
		return $this->_defaultDir;
	}
	
	/**
	 * Get image dir
	 * @return string
	 */
	public function getImageDir()
	{
		return $this->_imageDir;
	}
	
	/**
	 * Get default image url
	 * @return string
	 */
	public function getDefaultImageUrl()
	{
		return $this->_defaultImageUrl;
	}
	
	/**
	 * Get image url
	 * @return string
	 */
	public function getImageUrl()
	{
		return $this->_imageUrl;
	}
	
	/**
	 * Get retina suffix
	 * @return string
	 */
	public function getRetinaSuffix()
	{
		return $this->_retinaSuffix;
	}
	
	/**
	 * Get default image dir contents
	 * @return	array		Of data (filename, mtime, size)
	 */
	public function getDefaultImageDirContents()
	{
		$images = array();
		$cache  = $this->_getCache( 'default' );
		
		if ( $cache !== false )
		{
			return $cache;
		}
		
		/* Grab data */
		try
		{
			foreach( new DirectoryIterator( $this->getDefaultImageDir() ) as $file )
			{
				if ( ! $file->isDot() AND ! $file->isDir() )
				{
					$_name = $file->getFileName();
	
					if ( substr( $_name, 0, 1 ) != '.' )
					{
						if ( substr( $_name, -4 ) == '.png' )
						{
							try
							{
								$data = getimagesize( $this->getDefaultImageDir() . '/' . $_name );
							}
							catch( Exception $e ) { } 
							
							$images[] = array(  'filename'   => $_name,
												'imgsrc'     => $this->getImageUrl() . $file,
												'dimensions' => array( $data[0], $data[1] ),
												'mtime'      => $file->getMTime(),
												'size'	     => $file->getSize(),
												'writeable'  => $file->isWritable() );
						}
					}
				}
			}
		} catch ( Exception $e ) {}
		
		/* Add to cache */
		$this->_addCache( 'default', $images );
	
		return $images;
	}
	
	/**
	 * Get current image dir contents
	 * @return	array		Of data (filename, mtime, size)
	 */
	public function getImageDirContents()
	{
		$images = array();
		$cache  = $this->_getCache( 'current' );
		
		if ( $cache !== false )
		{
			return $cache;
		}
		try
		{
			foreach( new DirectoryIterator( $this->getImageDir() ) as $file )
			{
				if ( ! $file->isDot() AND ! $file->isDir() )
				{
					$_name = $file->getFileName();
		
					if ( substr( $_name, 0, 1 ) != '.' )
					{
						if ( substr( $_name, -4 ) == '.png' )
						{
							try
							{
								$data = getimagesize( $this->getImageDir() . '/' . $_name );
							}
							catch( Exception $e ) { } 
							
							$images[] = array(  'filename'   => $_name,
												'imgsrc'     => $this->getImageUrl() . $file,
												'dimensions' => array( $data[0], $data[1] ),
												'mtime'      => $file->getMTime(),
												'size'	     => $file->getSize(),
												'writeable'  => $file->isWritable() );
						}
					}
				}
			}
		} catch ( Exception $e ) { }
		
		/* Add to cache */
		$this->_addCache( 'current', $images );
		
		return $images;
	}
	
	/**
	 * Get filenames
	 * @return array
	 */
	public function getFileNames()
	{
		return $this->_fileNames;
	}
	
	/**
	 * Checks directories
	 * @return	array Error codes
	 */
	public function checkDirectories()
	{
		$errors = array();
		
		/* Exists */
		if ( ! is_dir( $this->getImageDir() ) )
		{
			$errors[] = array( 'key'   => 'doesnt_exist',
							   'extra' => $this->getImageDir() );
		}
		
		if ( ! is_dir( $this->getDefaultImageDir() ) )
		{
			$errors[] = array( 'key'   => 'doesnt_exist',
							   'extra' => $this->getDefaultImageDir() );
		}
		
		/* is writeable */
		if ( ! is_writeable( $this->getImageDir() ) )
		{
			$errors[] = array( 'key'   => 'cannot_write',
							   'extra' => $this->getImageDir() );
		}
		
		return ( count( $errors ) ) ? $errors : false;
	}
	
	/**
	 * Check images
	 * @return	array Error codes
	 */
	public function checkImages()
	{
		$default       = $this->getDefaultImageDirContents();
		$images        = $this->getImageDirContents();
		$fileNames     = $this->getFileNames();
		$workingImages = array();
		$errors        = array( 'missing' => false, 'dimensions' => false, 'writeable' => false );
		
		foreach( $images as $i )
		{
			$workingImages[ $i['filename'] ] = true;
		}
		
		/* Lets see if any are missing */
		foreach( $fileNames as $name )
		{
			$image = $this->_getFileFromArray( $name . '.png', $images );
			
			if ( $image === false )
			{
				$errors['missing'][] = $name . '.png';
			} 
		}
		
		/* Are they the correct size? */
		foreach( $default as $def )
		{
			$image = $this->_getFileFromArray( $def['filename'], $images );
			
			if ( $image['dimensions'] && $def['dimensions'] )
			{
				if ( ( $image['dimensions'][0] != $def['dimensions'][0] ) || ( $image['dimensions'][1] != $def['dimensions'][1] ) )
				{
					$errors['dimensions'][] = $def['filename'];
				}
			}
			
			if ( ! is_writeable( $this->getImageDir() . $image['filename'] ) )
			{
				$errors['writeable'][] = $image['filename'];
			}
		}
	
		return ( count( $errors ) ) ? $errors : false;
	}
	
	/**
	 * Return an XML archive
	 * @return string
	 */
	public function getXmlArchive()
	{
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlArchive = new classXMLArchive();
		$xmlArchive->setStripPath( $this->getImageDir() );
		$xmlArchive->add( $this->getImageDir() );
		
		$xml = $xmlArchive->getArchiveContents();
		
		return $xml;
	}
	
	/**
	 * Imports a set XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @return	mixed		Number of items added, or bool
	 */
	public function importXmlArchive( $content )
	{
		/* Correct file type ? */
		if ( ! strstr( $content, "<xmlarchive" ) )
		{
			return false;
		}
	
		/* Get XML class */
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlArchive = new classXMLArchive();
		$xmlArchive->readXML( $content );
		
		/* Got stuff? */
		if ( ! $xmlArchive->countFileArray() )
		{
			return false;
		}
	
		/* Write it */
		if ( $xmlArchive->write( $content, $this->getImageDir() ) === FALSE )
		{
			return false;
		}
		
		/* Check images */
		$errors = $this->checkImages();
		
		if ( $errors['missing'] !== false OR $errors['dimensions'] !== false OR $errors['writeable'] !== false )
		{
			return false;
		}
		
		/* Push changes to the app */
		$this->setStyleLastUpdated( time() );
	
		return true;
	}
	
	/**
	 * Rebuild the db
	 */
	private function _refreshDatabase()
	{
		$images = $this->getImageDirContents();
		
		/* Delete current contents */
		$this->DB->delete( 'mobile_app_style' );
		
		foreach( $images as $img )
		{
			if ( ! stristr( $img['filename'], $this->getRetinaSuffix() . '.png') )
			{
				$hasRetina = ( $this->_getFileFromArray( str_replace( '.png', $this->getRetinaSuffix() . '.png', $img['filename'] ), $images ) ) ? 1 : 0;
				$insert    = array( 'filename'    => $img['filename'],
								    'isInUse'     => 1,
									'hasRetina'   => $hasRetina,
						            'lastUpdated' => IPS_UNIX_TIME_NOW );
				
				$this->DB->insert( 'mobile_app_style', $insert );
			}
		}
	}
	
	/**
	 * Fetches a file by name from the array
	 * @param string $file
	 * @param array $array
	 * @return array|boolean
	 */
	private function _getFileFromArray( $file, array $array )
	{
		foreach( $array as $image )
		{
			if ( $file == $image['filename'] )
			{
				return $image;
			}
		}
		
		return false;
	}
	
	/**
	 * Fetch data from a cache
	 * @param string $key
	 * @return mixed
	 */
	private function _getCache( $key )
	{
		if ( $key && ! empty( $this->_cache[ $key ] ) )
		{
			return $this->_cache[ $key ];
		}
		
		return false;
	}
	
	/**
	 * Add an item to the local cache
	 * @param string $key
	 * @param array $value
	 */
	private function _addCache( $key, $value )
	{
		if ( $key )
		{
			$this->_cache[ $key ] = $value;
		}
	}
	
	/**
	 * Flush cache
	 */
	private function _flushCache()
	{
		$this->_cache = array( 'default' => array(), 'current' => array() );
	}
	
}
