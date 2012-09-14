<?php
/**
 * Used to handle file uploads
 *
 * Used to handle file upload processing
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link			http://www.invisionpower.com
 * @version		$Rev: 9749 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_upload
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
	 * @param	object		$registry		Registry object
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
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}
		
		$this->_images = $this->registry->gallery->helper('image');
		$this->_albums = $this->registry->gallery->helper('albums');
		$this->_media  = $this->registry->gallery->helper('media');
	}
	
	/**
	 * Generate a new session key
	 *
	 * @return	@e string	MD5 Hash
	 */
	public function generateSessionKey()
	{
		return md5( microtime(true) . ',' . $this->memberData['member_id'] . ',' . $this->member->ip_address );
	}
	
	/**
	 * Generate a new item key
	 *
	 * @param	array		$data		Item data
	 * @return	@e string	MD5 Hash
	 */
	public function generateItemKey( $data )
	{
		return md5( microtime(true) . ',' . $this->memberData['member_id'] . ',' . $this->member->ip_address . $data['name'] . ',' . $data['size'] );
	}
	
	/**
	 * Fetch diskspace used
	 *
	 * @param	int		$memberId		Member ID
	 * @return	@e int	Bytes
	 */
	public function fetchDiskUsage( $memberId )
	{
		$total = $this->DB->buildAndFetch( array( 'select' => 'SUM( file_size ) as diskspace', 
		  										  'from'   => 'gallery_images', 
		  										  'where'  => 'member_id=' . intval( $memberId ) ) );
		  
		return intval($total['diskspace']);
	}
	
	/**
	 * Fetch number of images in an album from both
	 * images and images_upload tables
	 *
	 * @param	int			$albumId		Album ID
	 * @return	@e int
	 */
	public function fetchImageCount( $albumId )
	{
		/* Normal images */
		$images = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) AS total',
		  										   'from'   => 'gallery_images',
		  										   'where'  => 'img_album_id=' . intval( $albumId )
										   )	  );
		
		/* Got some in uploads too? */
		$upload = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) AS total',
		  										   'from'   => 'gallery_images_uploads',
		 										   'where'  => 'upload_album_id=' . intval( $albumId )
										   )	  );
		
		return intval($images['total']) + intval($upload['total']);
	}

	/**
	 * Fetch statistics used
	 *
	 * @return	@e array
	 */
	public function fetchStats()
	{
		$stats = array( 'used' => 0, 'maxItem' => 0 );
		
		if ( $this->memberData['member_id'] )
		{
			$stats['used'] = $this->fetchDiskUsage( $this->memberData['member_id'] );
		}
		
		/* Sort out upload limit */
		$stats['maxItem'] = ($this->memberData['g_max_upload'] > 0) ? ($this->memberData['g_max_upload'] * 1024) : intval($this->memberData['g_max_upload']);
		$maxPhp			  = IPSLib::getMaxPostSize();
		
		if ( $maxPhp < $stats['maxItem'] or $stats['maxItem'] == -1 )
		{
			$stats['maxItem'] = $maxPhp;
		}
		
		/* Sort out size limit */
		$stats['maxTotal'] = ($this->memberData['g_max_diskspace'] > 0) ? ($this->memberData['g_max_diskspace'] * 1024) : intval($this->memberData['g_max_diskspace']);
		
		if ( $stats['maxTotal'] != -1 )
		{
			$stats['maxTotal'] -= $stats['used'];
			
			if ( $stats['maxTotal'] < 0 )
			{
				$stats['maxTotal'] = 0;
			}
		}
		
		if ( $stats['maxItem'] > $stats['maxTotal'] and $stats['maxTotal'] != -1 )
		{
			$stats['maxItem'] = $stats['maxTotal'];
		}
		
		$stats['maxItemHuman']  = ( $stats['maxItem'] == -1 )  ? $this->lang->words['unlimited_ucfirst'] : IPSLib::sizeFormat( $stats['maxItem'] );
		$stats['maxTotalHuman'] = ( $stats['maxTotal'] == -1 ) ? $this->lang->words['unlimited_ucfirst'] : IPSLib::sizeFormat( $stats['maxTotal'] );
		
		return $stats;
	}
	
	/**
	 * Generic API for adding an image
	 *
	 * @since	4.0
	 * @access	public
	 * @param	string		Image Data (binary string OR key for $_FILES array)
	 * @param	int			Album ID to add into
	 * @param	array		Options ( if using binary string imageData then 'fileName' must be set ('mypic.jpg' for example) 'name', 'description' should be self explanatory)
	 * @param	int			Member ID (optional, default $this->memberData['member_id'])
	 * @return	@e array
	 * 
	 * @todo	[Future] This function is used only by the mobile app right now
	 */
	public function addImage( $imageData, $albumId=0, $opts=array(), $memberId=null )
	{
		/* Get yer maps! */
		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$this->_mapping = classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		/* Init */
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		/* Ensure we have an album id */
		if ( ! $album['album_id'] )
		{
			throw new Exception( "NO_ALBUM" );
		}
		
		/* Member */
		$member = ( $memberId === null ) ? $this->memberData : IPSMember::load( $memberId, 'all' );
		
		/* Ensure some defaults */
		if ( ! isset( $opts['allow_images'] ) )
		{
			$opts['allow_images'] = 1;
		}
		
		if ( ! isset( $opts['allow_media'] ) )
		{
			$opts['allow_media'] = $this->_media->allow();
		}
		
		/* Load uploader */
		require_once IPS_KERNEL_PATH.'classUpload.php';/*noLibHook*/
		$upload = new classUpload();
		
		if ( $opts['allow_media'] )
		{
			$ext = $this->_media->allowedExtensions();
			
			foreach( $ext as $k )
			{
				$upload->allowed_file_ext[] = $k;
			}
		}
		
		/* Add in allowed image extensions */
		foreach( $this->_images->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[] = $k;
		}
		
		/* Check diskspace */
		if ( $this->memberData['g_max_diskspace'] != -1 )
		{
		  	if ( ($this->fetchDiskUsage( $this->memberData['member_id'] ) + $_FILES['FILE_UPLOAD']['size']) > ( $this->memberData['g_max_diskspace'] * 1024 ) )
		  	{
			 	throw new Exception( 'OUT_OF_DISKSPACE' );
		  	}
		}
		
		/* Uhm... got an image limit? */
		if ( ! $this->registry->gallery->helper('albums')->isGlobal( $album ) AND $this->memberData['g_img_album_limit'] != -1 )
		{
			if ( $this->memberData['g_img_album_limit'] == 0 || ( $this->fetchImageCount($album['album_id']) + 1 ) > $this->memberData['g_img_album_limit'] )
			{
				throw new Exception( 'ALBUM_FULL' );
			}
		}
		
		/* Fetch dir name (creating if required ) */
		$dir = $this->createDirectoryName( $album['album_id'] );
		/* What do we have */
		if ( strlen( $imageData ) < 20 && ! empty( $_FILES[ $imageData ] ) )
		{
			/* Is Uploadable, so finish it off */
			$key                       = $imageData;
			$fileSize                  = $_FILES[ $key ]['size'] ? $_FILES[ $key ]['size'] : 1; // Prevent division by 0 warning
			$upload->upload_form_field = $key;
			$upload->out_file_dir      = $this->settings['gallery_images_path'].'/'.$dir;
			$upload->out_file_name     = "gallery_{$this->memberData['member_id']}_{$album['album_id']}_".time()%$fileSize;
			
			$upload->process();
			
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{		
					case 1:
						throw new Exception( 'FAILX' );
					break;
					
					case 2:
						throw new Exception( 'BAD_TYPE' );
					break;
					
					case 3:
						throw new Exception( 'TOO_BIG' );
					break;
					
					case 4:
						throw new Exception( 'FAIL' );
					break;
					
					default:
						throw new Exception( 'NOT_VALID' );
					break;
				}
			}
		}
		else
		{
			/* check for stuffs */
			if ( empty( $opts['fileName'] ) )
			{
				throw new Exception('IMAGE_FILE_NAME_NOT_SET');
			}

			
			/* It's binary data, so write the file damnit */
			$ext      = IPSText::getFileExtension( $opts['fileName'] );
			$fileSize = IPSLib::strlenToBytes( strlen( $imageData ) );
			if ( ! $ext )
			{
				throw new Exception('COULD_NOT_FETCH_EXTENSION');
			}
			
			$saveAs   = "gallery_{$this->memberData['member_id']}_{$album['album_id']}_" . ( time() %  $fileSize ) . '.' . $ext;
			$fileName = $this->settings['gallery_images_path'].'/'.$dir . $saveAs;
			
			$fp = @fopen( $fileName, 'wb' );
			@fwrite( $fp, $imageData );
			@fclose( $fp );
			@chmod( $fileName, IPS_FILE_PERMISSION );
			if ( ! file_exists( $fileName ) )
			{
				throw new Exception( 'IMAGE_NOT_WRITTEN' );
			}
			
			/* Set up some data for other functions */
			$upload->saved_upload_name  = $fileName;
			$upload->original_file_name = $opts['fileName'];
			$upload->parsed_file_name   = $saveAs;
			$upload->file_extension     = $ext;
		}


		//-------------------------------------------------------------
		// Exif/IPTC support?
		//-------------------------------------------------------------
		
		$meta_data = array();
		
		if ( $this->member->isMobileApp )
		{
			$meta_data['Camera Model'] = 'iPhone';
		}
		
		$meta_data = array_merge( $meta_data, $this->registry->gallery->helper('image')->extractExif( $upload->saved_upload_name ) );
		$meta_data = array_merge( $meta_data, $this->registry->gallery->helper('image')->extractIptc( $upload->saved_upload_name ) );
		
		//-------------------------------------------------------------
		// Pass to library
		//-------------------------------------------------------------
		
		
		$ext	= '.'.$upload->file_extension;
		$media	= $this->_media->isAllowedExtension( $ext ) ? 1 : 0;
		
		$image = array(	'media'				=> $media,
						'directory'			=> $dir,
						'masked_file_name'	=> $upload->parsed_file_name );

		// Check max upload size for this user
		if ( $media )
		{
			if ( $this->memberData['g_movie_size'] != -1 )
			{
		  		if ( $fileSize > ( $this->memberData['g_movie_size'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

				  	throw new Exception( 'TOO_BIG' );
				}
			}		
		}
		else
		{
			if ( $this->memberData['g_max_upload'] != -1 )
			{
				if ( $fileSize > ( $this->memberData['g_max_upload'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

				  	throw new Exception( 'TOO_BIG' );
				}
			}
		}

		clearstatcache();
		
		$ext_file_name = $upload->parsed_file_name;
		$fileSize      = filesize( $upload->saved_upload_name );
		$itemKey	   = $this->generateItemKey( array( 'name' => $upload->original_file_name, 'size' => $fileSize ) );
		$sessionKey    = $this->generateSessionKey();
		$latLon		   = array( 0, 0 );
		$geoJson	   = '';
		
		/* Geolocation shiznit */
		if ( ! empty( $meta_data['GPS'] ) )
		{
			$latLon = $this->registry->gallery->helper('image')->convertExifGpsToLatLon( $meta_data['GPS'] );
			
			if ( is_array( $latLon ) AND ( $latLon[0] !== false ) )
			{
				$geolocdata = $this->_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
			}
		}
		
		/* Finish */
		$title = ( ! empty( $opts['title'] ) )       ? $opts['title']       : $upload->original_file_name;
		$desc  = ( ! empty( $opts['description'] ) ) ? $opts['description'] : '';
		
		/* Fix up unicode issues */
		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$title = IPSText::utf8ToEntities( $title );
			$desc  = IPSText::utf8ToEntities( $desc );
		}
		
		/* Upload data */
		$upload_geodata = array( 'latLon'   => $latLon,
							     'gpsRaw'   => $meta_data['GPS'],
								 'locShort' => $geolocdata['geocache_short'] );
		
		/*  Build array */
		$image = array( 'upload_key'     		=> $itemKey,
						'upload_session' 		=> $sessionKey,
						'upload_member_id' 		=> $member['member_id'],
						'upload_album_id' 		=> $album['album_id'],
						'upload_date'      		=> time(),
						'upload_file_directory' => preg_replace( '#/$#', '', $dir ),
						'upload_file_orig_name' => $upload->original_file_name,
						'upload_file_name' 		=> $upload->parsed_file_name,
						'upload_file_size' 		=> $fileSize,
						'upload_file_type' 		=> $this->registry->gallery->helper('image')->getImageType( $ext_file_name ),
						'upload_title'			=> $title,
						'upload_description'	=> $desc,
						'upload_copyright'	    => '',
						'upload_data'			=> '',
						'upload_geodata'		=> serialize( $upload_geodata ),
						'upload_exif'			=> serialize( $meta_data ) );
		
		/* insert */
		$this->DB->insert( 'gallery_images_uploads', $image );
		
		$this->registry->gallery->helper('image')->buildSizedCopies( $this->_remapAsImage( $image ), array( 'destination' => 'uploads' ) );
		
		/* Does image need to be rotated? */
		if ( ! empty( $meta_data['IFD0.Orientation'] ) )
		{
			$angle = 0;
			
			switch ( $meta_data['IFD0.Orientation'] )
			{
				case 6:
					$angle = 270;
				break;
				case 8:
					$angle = 90;
				break;
				case 3:
					$angle = 180;
				break;
			}
			
			if ( $angle )
			{
				$this->registry->gallery->helper('image')->rotateImage( $this->_remapAsImage( $image ), $angle );
			}
		}
		
		return $this->finish( $sessionKey );
	}
	/**
	 * Generic API for editing an image
	 *
	 * @since	4.1
	 * @access	public
	 * @param	string		Image Data (binary string OR key for $_FILES array)
	 * @param	int			Album ID to add into
	 * @param	array		Options ( if using binary string imageData then 'fileName' must be set ('mypic.jpg' for example) 'name', 'description' should be self explanatory)
	 * @param	int			Member ID (optional, default $this->memberData['member_id'])
	 * @return	@e array	New image data
	 */
	public function editImage( $imageData, $imageId=0, $opts=array(), $memberId=null )
	{
		/* Get yer maps! */
		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$this->_mapping = classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		/* Init */
		$image = $this->_images->fetchImage( $imageId );
		
		/* Ensure we have an image id */
		if ( ! $image['id'] )
		{
			throw new Exception( "NO_IMAGE" );
		}
		
		$album = $this->_albums->fetchAlbumsById( $image['img_album_id'] );
		
		/* Ensure we have an album id */
		if ( ! $album['album_id'] )
		{
			throw new Exception( "NO_ALBUM" );
		}
		
		/* Member */
		$member = ( $memberId === null ) ? $this->memberData : IPSMember::load( $memberId, 'all' );
		
		/* Ensure some defaults */
		if ( ! isset( $opts['allow_images'] ) )
		{
			$opts['allow_images'] = 1;
		}
		
		if ( ! isset( $opts['allow_media'] ) )
		{
			$opts['allow_media'] = $this->_media->allow();
		}
		
		/* Load uploader */
		require_once IPS_KERNEL_PATH.'classUpload.php';/*noLibHook*/
		$upload = new classUpload();
		
		if ( $opts['allow_media'] )
		{
			$ext = $this->_media->allowedExtensions();
			
			foreach( $ext as $k )
			{
				$upload->allowed_file_ext[] = $k;
			}
		}
		
		/* Add in allowed image extensions */
		foreach( $this->_images->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[] = $k;
		}
		
		/* Check diskspace */
		if ( $this->memberData['g_max_diskspace'] != -1 )
		{
		  	if ( ($this->fetchDiskUsage( $this->memberData['member_id'] ) + $_FILES['FILE_UPLOAD']['size']) > ( $this->memberData['g_max_diskspace'] * 1024 ) )
		  	{
			 	throw new Exception( 'OUT_OF_DISKSPACE' );
		  	}
		}
		
		/* Uhm... got an image limit? */
		if ( ! $this->_albums->isGlobal( $album ) AND $this->memberData['g_img_album_limit'] != -1 )
		{
			if ( $this->memberData['g_img_album_limit'] == 0 || ( $this->fetchImageCount($album['album_id']) + 1 ) > $this->memberData['g_img_album_limit'] )
			{
				throw new Exception( 'ALBUM_FULL' );
			}
		}
		
		/* Fetch dir name (creating if required ) */
		$dir = $this->createDirectoryName( $album['album_id'] );
		
		/* What do we have */
		if ( strlen( $imageData ) < 20 && ! empty( $_FILES[ $imageData ] ) )
		{
			/* Is Uploadable, so finish it off */
			$key                       = $imageData;
			$fileSize                  = $_FILES[ $key ]['size'] ? $_FILES[ $key ]['size'] : 1; // Prevent division by 0 warning
			$upload->upload_form_field = $key;
			$upload->out_file_dir      = $this->settings['gallery_images_path'].'/'.$dir;
			$upload->out_file_name     = "gallery_{$this->memberData['member_id']}_{$album['album_id']}_".time()%$fileSize;
			
			$upload->process();
			
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{		
					case 1:
						throw new Exception( 'FAILX' );
					break;
					
					case 2:
						throw new Exception( 'BAD_TYPE' );
					break;
					
					case 3:
						throw new Exception( 'TOO_BIG' );
					break;
					
					case 4:
						throw new Exception( 'FAIL' );
					break;
					
					default:
						throw new Exception( 'NOT_VALID' );
					break;
				}
			}
		}
		else
		{
			/* check for stuffs */
			if ( empty( $opts['fileName'] ) )
			{
				throw new Exception('IMAGE_FILE_NAME_NOT_SET');
			}
			
			/* It's binary data, so write the file damnit */
			$ext      = IPSText::getFileExtension( $opts['fileName'] );
			$fileSize = IPSLib::strlenToBytes( strlen( $imageData ) );
			
			if ( ! $ext )
			{
				throw new Exception('COULD_NOT_FETCH_EXTENSION');
			}
			
			$saveAs   = "gallery_{$this->memberData['member_id']}_{$album['album_id']}_" . ( time() %  $fileSize ) . '.' . $ext;
			$fileName = $this->settings['gallery_images_path'].'/'.$dir . $saveAs;
			
			$fp = @fopen( $fileName, 'wb' );
			@fwrite( $fp, $imageData );
			@fclose( $fp );
			@chmod( $fileName, IPS_FILE_PERMISSION );
			
			if ( ! file_exists( $fileName ) )
			{
				throw new Exception( 'IMAGE_NOT_WRITTEN' );
			}
			
			/* Set up some data for other functions */
			$upload->saved_upload_name  = $fileName;
			$upload->original_file_name = $opts['fileName'];
			$upload->parsed_file_name   = $saveAs;
			$upload->file_extension     = $ext;
		}

		//-------------------------------------------------------------
		// Exif/IPTC support?
		//-------------------------------------------------------------
		
		$meta_data = array();
		
		if ( $this->member->isMobileApp )
		{
			$meta_data['Camera Model'] = 'iPhone';
		}
		
		$meta_data = array_merge( $meta_data, $this->_images->extractExif( $upload->saved_upload_name ) );
		$meta_data = array_merge( $meta_data, $this->_images->extractIptc( $upload->saved_upload_name ) );
		
		//-------------------------------------------------------------
		// Pass to library
		//-------------------------------------------------------------
		
		$ext	= '.'.$upload->file_extension;
		$media	= $this->_media->isAllowedExtension( $ext ) ? 1 : 0;
		
		// Check max upload size for this user
		if ( $media )
		{
			if ( $this->memberData['g_movie_size'] != -1 )
			{
		  		if ( $fileSize > ( $this->memberData['g_movie_size'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

				  	throw new Exception( 'TOO_BIG' );
				}
			}		
		}
		else
		{
			if ( $this->memberData['g_max_upload'] != -1 )
			{
				if ( $fileSize > ( $this->memberData['g_max_upload'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

				  	throw new Exception( 'TOO_BIG' );
				}
			}
		}

		clearstatcache();
		
		$ext_file_name = $upload->parsed_file_name;
		$fileSize      = filesize( $upload->saved_upload_name );
		$itemKey	   = $this->generateItemKey( array( 'name' => $upload->original_file_name, 'size' => $fileSize ) );
		$sessionKey    = $this->generateSessionKey();
		$latLon		   = array( 0, 0 );
		$geoJson	   = '';
		
		/* Geolocation shiznit */
		if ( ! empty( $meta_data['GPS'] ) )
		{
			$latLon = $this->_images->convertExifGpsToLatLon( $meta_data['GPS'] );
			
			if ( is_array( $latLon ) AND ( $latLon[0] !== false ) )
			{
				$geolocdata = $this->_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
			}
		}
		
		/* Finish */
		$title = ( ! empty( $opts['caption'] ) )     ? $opts['caption']     : $upload->original_file_name;
		$desc  = ( ! empty( $opts['description'] ) ) ? $opts['description'] : '';
		
		/* Upload data */
		$upload_geodata = array( 'latLon'   => $latLon,
							     'gpsRaw'   => $meta_data['GPS'],
								 'locShort' => $geolocdata['geocache_short'] );
		
		/*  Build array */
		$update = array('upload_file_directory' => preg_replace( '#/$#', '', $dir ),
						'upload_file_orig_name' => $upload->original_file_name,
						'upload_file_name' 		=> $upload->parsed_file_name,
						'upload_file_size' 		=> $fileSize,
						'upload_file_type' 		=> $this->_images->getImageType( $ext_file_name ),
						'upload_title'			=> empty($opts['caption']) ? $upload->original_file_name : $opts['caption'],
						'upload_description'	=> empty($opts['description']) ? '' : $opts['description'],
						'upload_copyright'	    => empty($opts['copyright']) ? '' : $opts['copyright'],
						'upload_data'			=> '',
						'upload_geodata'		=> serialize( $upload_geodata ),
						'upload_exif'			=> serialize( $meta_data ) );
		
		$update = $this->_remapAsImage( $update );
		
		/* Sort out GPS thing */
		if ( isset($opts['image_gps_show']) )
		{
			$update['image_gps_show'] = intval($opts['image_gps_show']);
		}
		
		/* Delete some things we don't want to keep (obviously.. if we're deleting them!) */
		foreach( array('id','member_id','img_album_id','medium_file_name','original_file_name','idate','image_feature_flag') as $key )
		{
			unset($update[ $key ]);
		}
		
		/* Data Hook Location - set & unset some additional data... */
		$update['_extraData'] = array( 'album' => $album, 'image' => $image );
		
		IPSLib::doDataHooks( $update, 'galleryEditImage' );
		
		unset($update['_extraData']);
		
		/* Update table */
		$this->DB->update( 'gallery_images', $update, 'id=' . $image['id'] );
		
		/* Delete OLD images and build new ones */
		$this->registry->gallery->helper('moderate')->removeImageFiles( $image, false );
		$this->_images->buildSizedCopies( array_merge( $image, $update ), array( 'destination' => 'images' ) );
		
		/* Re-load image data to ensure it's up to date */
		$image = $this->_images->fetchImage( $image['id'], true );
		
		/* Does image need to be rotated? */
		if ( ! empty( $meta_data['IFD0.Orientation'] ) )
		{
			$angle = 0;
			
			switch ( $meta_data['IFD0.Orientation'] )
			{
				case 6:
					$angle = 270;
				break;
				case 8:
					$angle = 90;
				break;
				case 3:
					$angle = 180;
				break;
			}
			
			if ( $angle )
			{
				$this->_images->rotateImage( $image, $angle );
			}
		}
		
		return $image;
	}
	
	/**
	 * Create directory name
	 * Determines the directory name
	 *
	 * @access	public
	 * @return	@e string		Dir name
	 */
	public function createDirectoryName( $albumId )
	{
		/* Safe mode enabled? Let's skip the whole dir process */
		if ( $this->settings['safe_mode_skins'] )
		{
			return '';
		}
		
		$dir  = '';
		$name = 'album_' . intval( $albumId );
		
		if ( ! is_dir( $this->settings['gallery_images_path'] . '/gallery' ) )
	  	{
	  		if ( @mkdir( $this->settings['gallery_images_path'].'/gallery', IPS_FOLDER_PERMISSION ) )
	  		{
			 	@chmod( $this->settings['gallery_images_path'].'/gallery', IPS_FOLDER_PERMISSION );
			 	@touch( $this->settings['gallery_images_path'].'/gallery/index.html' );
	  		}
	  		
		  	$dir = 'gallery';
	  	}
	  	else
	  	{
	  		$dir = 'gallery';
	  	}
	  	
		if ( ! is_dir( $this->settings['gallery_images_path'] . '/' . $dir . '/' . $name ) )
	  	{ 			
	  		if ( @mkdir( $this->settings['gallery_images_path']. '/' . $dir . '/' . $name, IPS_FOLDER_PERMISSION ) )
	  		{
			 	@chmod( $this->settings['gallery_images_path']. '/' . $dir . '/' . $name, IPS_FOLDER_PERMISSION );
			 	@touch( $this->settings['gallery_images_path']. '/' . $dir . '/' . $name . '/index.html' );
	  		}
	  		
		  	$dir .= '/' . $name;
	  	}
	  	else
	  	{
	  		$dir .= '/' . $name;
	  	}	  	

		$dir = ( $dir ) ? $dir . '/' : '';
		
		return $dir;
	}

	/**
	 * Processes the thumbnail for a media thingy
	 * @access	public
	 * @param 	string		Upload ID
	 * @return	@e array
	 */
	public function mediaThumb( $id )
	{
		$image = $this->registry->gallery->helper('image')->fetchImage( $id );
		
		if ( ! $image['id'] )
		{
			return false;
		}
		
		$albumId = $image['img_album_id'];
		$album   = $this->_albums->fetchAlbum( $albumId );
		
		/* Load uploader */
		require_once IPS_KERNEL_PATH.'classUpload.php';/*noLibHook*/
		$upload = new classUpload();
		
		/* Add in allowed photo extensions */
		foreach( $this->_images->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[] = $k;
		}
		
		if ( $_FILES['FILE_UPLOAD']['size'] < 1 )
		{
			throw new Exception( 'FAIL' );
		}
		
		/* Limit upload size to 2mb */
		if ( $_FILES['FILE_UPLOAD']['size'] > 2048 * 1024 )
		{
			throw new Exception( 'TOO_BIG' );
		}
		
		/* Fetch dir name (creating if required ) */
		$dir = $this->createDirectoryName( $albumId );
		
		$upload->out_file_dir  = $this->settings['gallery_images_path'].'/'.$dir;
		$upload->out_file_name = "media_{$this->memberData['member_id']}_{$album['album_id']}_".time()%$_FILES['FILE_UPLOAD']['size'];
		
		$upload->process();
					
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{		
				case 1:
					throw new Exception( 'upload_no_file' );
				break;
				
				case 2:
					throw new Exception( 'invalid_mime_type' );
				break;
				
				case 3:
					throw new Exception( 'upload_too_big' );
				break;
				
				case 4:
					throw new Exception( 'upload_failed' );
				break;
				
				default:
					throw new Exception( 'upload_failed' );
				break;
			}
		}
		
		$ext_file_name = $upload->parsed_file_name;
		$fileSize      = filesize( $upload->saved_upload_name );
		 
		/* insert */
		if ( is_numeric( $image['id'] ) )
		{
			$this->DB->update( 'gallery_images', array( 'thumbnail' => 1, 'medium_file_name' => $upload->parsed_file_name ), 'id=' . intval( $image['id']  ) );
		}
		else
		{
			$this->DB->update( 'gallery_images_uploads', array( 'upload_thumb_name' => 'tn_' . $upload->parsed_file_name, 'upload_medium_name' => $upload->parsed_file_name ), 'upload_key=\'' . $image['id'] . "'" );
		}
		
		/* Get kernel library */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		$img = ips_kernel_image::bootstrap( $this->settings['gallery_img_suite'] );
		
		/* Build 'original' thumbnail */
		$settings  = array( 'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
						    'image_file'	=> $upload->parsed_file_name,
						    'im_path'		=> $this->settings['gallery_im_path'],
						    'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
						    'jpg_quality'	=> GALLERY_JPG_QUALITY,
						    'png_quality'	=> GALLERY_PNG_QUALITY );	
		
		if ( $img->init( $settings ) )
		{
			$return = $img->resizeImage( 640, 640 );
			
			/* Over write it */
			$img->writeImage( $this->settings['gallery_images_path'] . '/' . $dir . $upload->parsed_file_name );
		}
		
		unset( $img );
		
		/* Build other sizes */
		$this->_media->buildThumbs( $image['id'] );
		
		/* Return some data we might like to see */
		$ret        = $this->_images->fetchImage( $image['id'], GALLERY_IMAGES_FORCE_LOAD );
		$ret['tag'] = $this->_images->makeImageTag( $ret );
		$ret['ok']  = 'done';
		
		return $ret;
	}
	
	/**
	 * Moves image to correct dir, adds to tmp upload table, builds thumbs
	 *
	 * @since	4.0
	 * @access	public
	 * @param	string		Session Key
	 * @param	int			Album ID to upload into
	 * @param	array		Options
	 * @param	int			Member ID (optional, default $this->memberData['member_id'])
	 * @return	@e array
	 */
	public function process( $sessionKey='', $albumId=0, $opts=array(), $memberId=null )
	{
		/* Get yer maps! */
		require_once( IPS_ROOT_PATH . 'sources/classes/mapping/bootstrap.php' );/*noLibHook*/
		$this->_mapping = classes_mapping::bootstrap( IPS_MAPPING_SERVICE );
		
		/* Init */
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumId );
		
		/* Ensure we have an album id */
		if ( ! $album['album_id'] )
		{
			trigger_error( "Album ID missing in gallery_upload::process" );
		}
		
		/* Member */
		$member = ( $memberId === null ) ? $this->memberData : IPSMember::load( $memberId, 'all' );
		
		/* Ensure some defaults */
		if ( ! isset( $opts['allow_images'] ) )
		{
			$opts['allow_images'] = 1;
		}
		
		if ( ! isset( $opts['allow_media'] ) )
		{
			$opts['allow_media'] = $this->_media->allow();
		}
		
		/* Load uploader */
		require_once( IPS_KERNEL_PATH . 'classUpload.php' );/*noLibHook*/
		$upload = new classUpload();
		
		if ( $opts['allow_media'] )
		{
			$ext = $this->_media->allowedExtensions();
			
			foreach( $ext as $k )
			{
				$upload->allowed_file_ext[] = $k;
			}
		}
		
		/* Add in allowed photo extensions */
		foreach( $this->_images->allowedExtensions() as $k )
		{
			$upload->allowed_file_ext[] = $k;
		}
		
		/* Check diskspace */
		if ( $this->memberData['g_max_diskspace'] != -1 )
		{
		  	if ( $this->fetchDiskUsage( $this->memberData['member_id'] ) + $_FILES['FILE_UPLOAD']['size'] > ( $this->memberData['g_max_diskspace'] * 1024 ) )
		  	{
			 	throw new Exception( 'OUT_OF_DISKSPACE' );
		  	}
		}
		
		if ( ! $this->registry->gallery->helper('albums')->isGlobal( $album ) AND $this->memberData['g_img_album_limit'] != -1 )
		{
			if ( $this->memberData['g_img_album_limit'] == 0 || ( $this->fetchImageCount($album['album_id']) + 1 ) > $this->memberData['g_img_album_limit'] )
			{
				throw new Exception( 'ALBUM_FULL' );
			}
		}
		
		/* Fetch dir name (creating if required ) */
		$dir = $this->createDirectoryName( $albumId );
		
		$upload->out_file_dir 	= $this->settings['gallery_images_path'].'/'.$dir;
		$upload->out_file_name	= $_FILES['FILE_UPLOAD']['size'] ? "gallery_{$this->memberData['member_id']}_{$album['album_id']}_".time()%$_FILES['FILE_UPLOAD']['size'] : "gallery_{$this->memberData['member_id']}_{$album['album_id']}_".time(); // Needed to prevent a division by 0 warning
		
		$upload->process();
		
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{		
				case 1:
					throw new Exception( 'FAIL' );
				break;
				
				case 2:
					throw new Exception( 'BAD_TYPE' );
				break;
				
				case 3:
					throw new Exception( 'TOO_BIG' );
				break;
				
				case 4:
					throw new Exception( 'FAIL' );
				break;
				
				default:
					throw new Exception( 'NOT_VALID' );
				break;
			}
		}

		//-------------------------------------------------------------
		// Exif/IPTC support?
		//-------------------------------------------------------------
		
		$meta_data = array_merge( array()   , $this->registry->gallery->helper('image')->extractExif( $upload->saved_upload_name ) );
		$meta_data = array_merge( $meta_data, $this->registry->gallery->helper('image')->extractIptc( $upload->saved_upload_name ) );
		
		//-------------------------------------------------------------
		// Pass to library
		//-------------------------------------------------------------
		
		
		$ext	= '.'.$upload->file_extension;
		$media	= $this->_media->isAllowedExtension( $ext ) ? 1 : 0;
		
		$image = array(	'media'				=> $media,
						'directory'			=> $dir,
						'masked_file_name'	=> $upload->parsed_file_name );


		// Check max upload size for this user
		if ( $media )
		{ 
			if ( $this->memberData['g_movie_size'] != -1 )
			{
		  		if( $_FILES['FILE_UPLOAD']['size'] > ( $this->memberData['g_movie_size'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

				  	throw new Exception( 'TOO_BIG' );
				}
			}		
		}
		else
		{
			if ( $this->memberData['g_max_upload'] != -1 )
			{
				if( $_FILES['FILE_UPLOAD']['size'] > ( $this->memberData['g_max_upload'] * 1024 ) )
				{
					@unlink( $upload->saved_upload_name );

				  	throw new Exception( 'TOO_BIG' );
				}
			}
		}

		clearstatcache();
		
		$ext_file_name = $upload->parsed_file_name;
		$fileSize      = filesize( $upload->saved_upload_name );
		$itemKey	   = $this->generateItemKey( array( 'name' => $upload->original_file_name, 'size' => $fileSize ) );
		$latLon		   = array( 0, 0 );
		$geoJson	   = '';
		
		/* Geolocation shiznit */
		if ( ! empty( $meta_data['GPS'] ) )
		{
			$latLon = $this->registry->gallery->helper('image')->convertExifGpsToLatLon( $meta_data['GPS'] );
			
			if ( is_array( $latLon ) AND ( $latLon[0] !== false ) )
			{
				$geolocdata = $this->_mapping->reverseGeoCodeLookUp( $latLon[0], $latLon[1] );
			}
		}
		
		/* Upload data */
		$upload_geodata = array( 'latLon'   => $latLon,
							     'gpsRaw'   => $meta_data['GPS'],
								 'locShort' => $geolocdata['geocache_short'] );
		
		/*  Build array */
		$image = array( 'upload_key'     		=> $itemKey,
						'upload_session' 		=> $sessionKey,
						'upload_member_id' 		=> $member['member_id'],
						'upload_album_id' 		=> $album['album_id'],
						'upload_date'      		=> time(),
						'upload_file_directory' => preg_replace( '#/$#', '', $dir ),
						'upload_file_orig_name' => $upload->original_file_name,
						'upload_file_name' 		=> $upload->parsed_file_name,
						'upload_file_size' 		=> $fileSize,
						'upload_file_type' 		=> $this->registry->gallery->helper('image')->getImageType( $ext_file_name ),
						'upload_title'			=> $upload->original_file_name,
						'upload_description'	=> '',
						'upload_copyright'	    => '',
						'upload_data'			=> '',
						'upload_geodata'		=> serialize( $upload_geodata ),
						'upload_exif'			=> serialize( $meta_data ) );
		
		/* Fix up unicode issues */
		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$image['upload_title'] = IPSText::utf8ToEntities( $upload->original_file_name );
		}
		
		/* insert */
		$this->DB->insert( 'gallery_images_uploads', $image );
		
		$this->registry->gallery->helper('image')->buildSizedCopies( $this->_remapAsImage( $image ), array( 'destination' => 'uploads' ) );
		
		/* Does image need to be rotated? */
		if ( ! empty( $meta_data['IFD0.Orientation'] ) )
		{
			$angle = 0;
			
			switch ( $meta_data['IFD0.Orientation'] )
			{
				case 6:
					$angle = 90;
				break;
				case 8:
					$angle = 270;
				break;
				case 3:
					$angle = 180;
				break;
			}
			
			if ( $angle )
			{
				$this->registry->gallery->helper('image')->rotateImage( $this->_remapAsImage( $image ), $angle );
			}
		}
		
		return $itemKey;
	}
	
	
	/**
	 * Saves images
	 *
	 * @access	public
	 * @param	array	Array of images indexed by key
	 * @return
	 */
	public function saveSessionImages( array $images )
	{
		/* Fetch images from DB */
		$ids     = array();
		$uploads = array();
		
		foreach( $images as $id => $data )
		{
			$ids[] = "'" . $this->DB->addSlashes( $id ) . "'";
		}
		
		if ( count( $ids ) )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_images_uploads',
									 'where'  => 'upload_key IN (' . implode( ',', $ids ) . ')' ) );
			$o = $this->DB->execute();

			while( $im = $this->DB->fetch( $o ) )
			{
				$uploads[ $im['upload_key'] ] = $im;
			}
		
			foreach( $images as $id => $data )
			{
				if ( isset( $data['caption']) OR isset( $data['masked_file_name'] ) OR isset( $data['description'] ) or isset( $data['directory'] ) or isset( $data['medium_file_name'] ) )
				{
					if ( is_array( $uploads[ $id ] ) )
					{
						if ( ! isset( $data['upload_data'] ) )
						{
							$data['upload_data'] = $uploads[ $id ]['upload_data'];
						}
						
						$data = array_merge( $uploads[ $id ], $this->_remapAsUpload( $data ) );
					}
					else
					{
						$data = $this->_remapAsUpload( $data );
					}
				}
				
				if ( count( $data ) AND strlen( $id ) == 32 )
				{
					$this->DB->update( 'gallery_images_uploads', $data, 'upload_key=\'' . $this->DB->addSlashes( $id ) . '\'' );
				}
			}
		}
	}
	
	/**
	 * Deletes images
	 *
	 * @access	public
	 * @param	array	Array of images indexed by key
	 * @return
	 */
	public function deleteSessionImages( array $images )
	{
		/* Init */
		$final = array();
		
		foreach( $images as $id => $data )
		{
			if ( ! is_numeric( $id ) )
			{
				$final[] = "'" . $id . "'";
			}
		}
		
		if ( count( $final ) )
		{
			$this->DB->delete( 'gallery_images_uploads', 'upload_key IN (' . implode( ",", $final ) . ")" );
		}
	}
	
	/**
	 * Finish: Publishes picture to selected album, sends out notifications, has a glass of very cheap wine and calls a cab home.
	 *
	 * @access	public
	 * @param	string		Session key
	 * @param	string
	 *
	 */
	public function finish( $sessionKey )
	{
		/* Init */
		$albums = array();
		
		/* Fetch the data */
		$this->DB->build( array( 'select'   => 'i.*',
								 'from'     => array( 'gallery_images_uploads' => 'i' ),
								 'where'    => 'i.upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'',
								 'order'    => 'i.upload_date ASC',
								 'limit'    => array( 0, 500 ),
								 'add_join' => array( array( 'select' => 'a.*',
								 							 'from'   => array( 'gallery_albums_main' => 'a' ),
								 							 'where'  => 'i.upload_album_id=a.album_id',
								 							 'type'   => 'left' ) ) ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			/* remap */
			$_i = $this->_remapAsImage( $row );
			
			$thisIsCoverImage = false;
			
			unset( $_i['id'] );
			
			/* Insert */
			if ( $_i['img_album_id'] )
			{
				/* Set approval flag */
				if ( isset( $row['album_g_approve_img'] ) )
				{
					if ( $row['album_g_approve_img'] AND ! $this->registry->gallery->helper('albums')->canModerate( $row ) )
					{
						$_i['approved'] = 0;
					}
				}
				
				/* Als bum again */
				if ( $_i['img_album_id'] AND IPSLib::isSerialized( $row['upload_data'] ) )
				{
					$_data            = unserialize( $row['upload_data'] );
					$thisIsCoverImage = ( isset( $_data['_isCover'] ) AND $_data['_isCover'] ) ? 1 : 0;
					
					/* Size data */
					if ( isset( $_data['sizes'] ) )
					{
						$_i['image_data'] = serialize( array( 'sizes' => $_data['sizes'] ) );
					}
				}
				
				$_i['image_notes'] = '';
				
				/* Data Hook Location */
				IPSLib::doDataHooks( $_i, 'galleryPreAddImage' );
				/* Insert into the database */
				$this->DB->insert( 'gallery_images', $_i );
				
				$newId = $this->DB->getInsertId();
				
				/* Do we have a taggy tag? */
				if ( ! empty( $_POST['ipsTags_' . $row['upload_key']] ) )
				{
					$this->registry->galleryTags->add( $_POST['ipsTags_' . $row['upload_key']], array(  'meta_id'		 => $newId,
																					      				'meta_parent_id' => $_i['img_album_id'],
																					      				'member_id'	     => $this->memberData['member_id'],
																					      				'meta_visible'   => $_i['approved'] ) );
				}
				
				/* Mark as read for uploader */		
				$this->registry->classItemMarking->markRead( array( 'albumID' => $_i['img_album_id'], 'itemID' => $newId ), 'gallery' );
				
				/* Geo location */
				$this->registry->gallery->helper('image')->setReverseGeoData( $newId );
				
				/* Data Hook Location */
				$_i['id'] = $newId;
				IPSLib::doDataHooks( $_i, 'galleryPostAddImage' );
				
				/* Make sure the id is set */
				if ( ! isset( $albums[ $_i['img_album_id'] ] ) )
				{
					$albums[ $_i['img_album_id'] ] = array();
				}
				
				if ( $thisIsCoverImage )
				{
					$albums[ $_i['img_album_id'] ] = array( 'album_cover_img_id' => intval( $newId ) );
				}
			}
		}
	
		/* Update albums? */
		if ( count( $albums ) )
		{
			/* Save and sync */
			$this->registry->gallery->helper('albums')->save( $albums );
			
			/* Fix image permissions */
			$this->registry->gallery->helper('image')->updatePermissionFromParent( $albums );
			
			/* Send notifications */
			$this->registry->gallery->helper('notification')->sendAlbumNotifications( array_keys( $albums ) );
		}
		
		/* Delete this session */
		$this->DB->delete( 'gallery_images_uploads', 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'' );
		
		/* Rebuild stats */
		$this->registry->gallery->rebuildStatsCache();
		
		return array_keys( $albums );
	}


	/**
	 * Fetches a single image
	 * NO SECURITY CHECKS ARE PERFORMED
	 *
	 * @access	public
	 * @param	string		ID key
	 * @return	@e array		Array of data remapped as image
	 */
	public function fetchImage( $id )
	{
		$_img = $this->DB->buildAndFetch( array( "select"	=>	"i.*",
												 "from"		=>	array( "gallery_images_uploads" =>	"i" ),
												 "where"	=>	"i.upload_key='" . trim( $this->DB->addSlashes( $id ) ) . "'",
												 "add_join"	=>	array( array( "select"	=>	"mem.members_display_name",
																			  "from"	=>	array( "members" => "mem" ),
																			  "where"	=>	"mem.member_id = i.upload_member_id",
																			  "type"	=>	"left" ) ) ) );
																			  
		return ( is_array( $_img ) && count( $_img ) ) ? $this->_remapAsImage( $_img ) : array();
	}
	
	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @access	protected
	 * @return	@e void
	 */
	public function fetchSessionUploadsAsJson( $sessionKey, $albumId, $msg='', $isError=0, $latestId=0 )
	{
		/* Start building the data */
		$JSON['sessionKey']		= $sessionKey;
		$JSON['album_id']		= $albumId;
		$JSON['upload_stats']   = $this->fetchStats();
		$JSON['msg']            = $msg;
		$JSON['is_error']       = $isError;
		
		if ( $latestId )
		{
			$JSON['insert_id'] = $latestId;
		}
		
		/* Fetch the data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_images_uploads',
								 'where'  => 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'',
								 'order'  => 'upload_date ASC',
								 'limit'  => array( 0, 500 )
						 )		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_isRead'] = 1;
			$thumb = ( ! $row['upload_thumb_name'] ) ? '' : $this->registry->gallery->helper('image')->makeImageTag( $this->_remapAsImage( $row ), array( 'type' => 'thumb', 'thumbClass' => 'thumb_img' ) );
			
			/* For-matt some data */
			$JSON['current_items'][ $row['upload_key'] ] = array( $row['upload_key']  ,
											 	 				  str_replace( array( '[', ']' ), '', $row['upload_file_orig_name'] ),
																  $row['upload_file_size'],
																  1,
																  $thumb,
																  100,
																  100 );
		}
		
		return $JSON;
	}
	
	/**
	 * Fetches all uploads for this 'session' as gallery_images format
	 *
	 * @access	protected
	 * @return	@e void
	 */
	public function fetchSessionUploadsAsImages( $sessionKey )
	{
		/* Fetch the data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_images_uploads',
								 'where'  => 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\'',
								 'order'  => 'upload_date ASC',
								 'limit'  => array( 0, 500 ) ) );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row            = $this->_remapAsImage( $row );
			$row['_isRead'] = 1;
			$row['thumb']   = $this->registry->gallery->helper('image')->makeImageLink( $row, array( 'type' => 'thumb' ) );
			
			$images[ $row['id'] ] = $row;
		}
		
		return $images;
	}
	
	/**
	 * Removes an uploaded item
	 *
	 * @access	public
	 * @param	string		Session key
	 * @param	string		Upload key
	 * @return	mixed		JSON array of remaining uploads for this session or FALSE
	 */
	public function removeUpload( $sessionKey, $uploadKey )
	{
		$upload = $this->DB->buildAndFetch( array( 'select' => '*',
								 		   		   'from'   => 'gallery_images_uploads',
								 		   	       'where'  => 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\' AND upload_key=\'' . $this->DB->addSlashes( $uploadKey ) .'\'' ) );
								 		   
		if ( $upload['upload_key'] )
		{
			/* Remove image from disk */
			$this->registry->gallery->helper('moderate')->removeImageFiles( $this->_remapAsImage( $upload ) );
			
			/* Remove DB row */
			$this->DB->delete( 'gallery_images_uploads', 'upload_session=\'' . $this->DB->addSlashes( $sessionKey ) .'\' AND upload_key=\'' . $this->DB->addSlashes( $uploadKey ) .'\'' );
			
			return $this->fetchSessionUploadsAsJson( $sessionKey, $upload['upload_album_id'], 'upload_removed', 0  );
		}
		
		return false;
	}
	
	/**
	 * Remap a tmp upload row as an image row table
	 *
	 * @param	array		$image		Image data
	 * @return	@e array	Remapped image data
	 */
	public function _remapAsImage( array $image )
	{
		if ( IPSLib::isSerialized( $image['upload_geodata'] ) )
		{
			$upload_geodata = unserialize( $image['upload_geodata'] );
			
			$latLon   = $upload_geodata['latLon'];
			$gpsRaw   = $upload_geodata['gpsRaw'];
			$locShort = $upload_geodata['locShort'];
		}
		
		if ( IPSLib::isSerialized( $image['upload_data'] ) )
		{
			$upload_data    = unserialize( $image['upload_data'] );
			$image_gps_show = intval( $upload_data['image_gps_show'] );
		}
		
		return array( 'id' 				   => $image['upload_key'],
					  'member_id' 		   => $image['upload_member_id'],
					  'img_album_id' 	   => $image['upload_album_id'],
					  'caption' 		   => $image['upload_title'],
					  'description' 	   => $image['upload_description'],
					  'directory' 		   => $image['upload_file_directory'],
					  'masked_file_name'   => $image['upload_file_name'],
					  'medium_file_name'   => $image['upload_medium_name'],
					  'original_file_name' => $image['upload_file_name_original'],
					  'file_name' 		   => $image['upload_file_orig_name'],
					  'file_size' 		   => $image['upload_file_size'],
					  'file_type' 	       => $image['upload_file_type'],
					  'approved' 		   => 1,
					  'thumbnail'		   => $image['upload_thumb_name'] ? 1 : 0,
					  'idate' 			   => $image['upload_date'],
					  'metadata' 		   => $image['upload_exif'],
					  'copyright'		   => $image['upload_copyright'],
					  'image_feature_flag' => $image['upload_feature_flag'],
					  'image_gps_show'	   => $image_gps_show,
					  'image_gps_raw'	   => serialize( $gpsRaw ),
					  'image_gps_lat'      => $latLon[0],
					  'image_gps_lon'      => $latLon[1],
					  'image_loc_short'    => $locShort,
					  'image_data'		   => $image['upload_data'],
					  'media'			   => $this->_media->isAllowedExtension( $image['upload_file_orig_name'] ) ? 1 : 0,
					  'media_thumb' 	   => $this->_media->isAllowedExtension( $image['upload_file_orig_name'] ) ? $image['upload_thumb_name'] : '',
					  'image_media_data'   => ( IPSLib::isSerialized( $image['upload_media_data'] ) ) ? $image['upload_media_data'] : @serialize( $image['upload_media_data'] ),
					  'caption_seo' 	   => IPSText::makeSeoTitle( $image['upload_title'] ) );
	}
	
	/**
	 * Remap a image row as a tmp upload row table
	 *
	 * @param	array		$image		Image data
	 * @return	@e array	Remapped image data
	 */
	public function _remapAsUpload( array $image )
	{
		/* Start data */
		if ( IPSLib::isSerialized( $image['image_data'] ) )
		{
			$_data = unserialize( $image['image_data'] );
		}
		else if ( IPSLib::isSerialized( $image['upload_data'] ) )
		{
			$_data = unserialize( $image['upload_data'] );
		}
		
		if ( ! is_array( $_data ) )
		{
			$_data = array();
		}
		
		$_thumbName = ( $image['image_masked_name'] ) ? 'tn_' . $image['image_masked_name'] : $image['upload_thumb_name'];
		
		if ( isset( $image['media_thumb'] ) OR $this->_media->isAllowedExtension( $image['image_masked_name'] ) )
		{
			$_thumbName = $image['media_thumb'];
		}
			
		$arr = array( 'upload_member_id' 		 => ( $image['member_id'] ) 	      ? $image['member_id'] 		       : $image['upload_member_id'],
					  'upload_album_id' 	 	 => ( $image['img_album_id'] ) 	      ? $image['img_album_id'] 	           : $image['upload_album_id'],
					  'upload_title' 		 	 => ( $image['caption'] ) 		      ? $image['caption'] 		           : $image['upload_title'],
					  'upload_description' 	 	 => ( $image['description'] ) 	      ? $image['description'] 	           : $image['upload_description'],
					  'upload_file_directory'    => ( $image['directory'] ) 	      ? $image['directory'] 		       : $image['upload_file_directory'],
					  'upload_file_name' 		 => ( isset( $image['masked_file_name'] ) )    ? $image['masked_file_name']         : $image['upload_file_name'],
					  'upload_medium_name'		 => ( isset( $image['medium_file_name'] ) )    ? $image['medium_file_name']         : $image['upload_medium_name'],
					  'upload_file_name_original'=> ( isset( $image['original_file_name'] ) )  ? $image['original_file_name']       : $image['upload_file_name_original'],
					  'upload_file_orig_name' 	 => ( isset( $image['file_name'] ) ) 	       ? $image['file_name'] 		       : $image['upload_file_orig_name'],
					  'upload_file_size' 		 => ( isset( $image['file_size'] ) ) 	       ? $image['file_size'] 		       : $image['upload_file_size'],
					  'upload_file_type' 	     => ( isset( $image['file_type'] ) ) 	       ? $image['file_type'] 		       : $image['upload_file_type'],
					  'upload_thumb_name'		 => $_thumbName,
					  'upload_date' 			 => ( $image['idate'] )               ? $image['idate'] 			       : $image['upload_date'],
					  'upload_exif' 		 	 => ( $image['metadata'] )  	      ? $image['metadata'] 		           : $image['upload_exif'],
					  'upload_copyright'		 => ( $image['copyright'] ) 	      ? $image['copyright']		           : $image['upload_copyright'],
					  'upload_feature_flag'		 => ( $image['image_feature_flag'] )  ? $image['image_feature_flag']       : $image['upload_feature_flag'],
					  'upload_media_data'        => ( IPSLib::isSerialized( $image['image_media_data'] ) ) ? $image['image_media_data'] : @serialize( $image['image_media_data'] ),
					  'upload_data'				 => '' );

		/* Cover image */
		if ( ( isset( $image['_isCover'] ) AND $image['_isCover'] ) OR ( isset( $image['album_cover_img_id'] ) AND $image['album_cover_img_id'] == $image['id'] ) )
		{
			$_data['_isCover'] = 1;
		}
		
		if ( isset( $image['image_gps_show'] ) )
		{
			$_data['image_gps_show'] = $image['image_gps_show'];
		}
		
		/* FINISH data */
		$arr['upload_data'] = serialize( $_data );
		
		/* Remove unsused rows */
		foreach( $arr as $k => $v )
		{
			if ( $k AND $v === null)
			{
				unset( $arr[ $k ] );
			}
		}
		
		return $arr;
	}
}