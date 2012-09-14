<?php
/**
 * Main/Rate
 *
 * Used to rate an image
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link			http://www.invisionpower.com
 * @version		$Rev: 9211 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_media
{
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
	 * Media file extensions
	 * fileExt => canUseFlashPlayer (int)
	 * @var array
	 */
	protected $_ext    = array( 'flv'  => 1, 'f4v' => 1, 'mp4' => 1, 'mov' => 1, 'm4a' => 1, 'm4v' => 1, '3gp' => 1, 'avi' => 0, 'wmv' => 0, 'mpg' => 1, 'mpeg' => 1, 'mkv' => 0, 'swf' => 1 );
	
	protected $_mtypes = array( 'flv'  => 'video/x-flv',
								'f4v'  => 'video/x-flv',
								'mp4'  => 'video/mp4',
								'mov'  => 'video/quicktime',
								'm4a'  => 'audio/mp4a-latm',
								'm4v'  => 'video/x-m4v',
								'3gp'  => 'video/3gpp',
								'avi'  => 'video/x-msvideo',
								'wmv'  => 'video/x-ms-wmv',
								'mpg'  => 'video/mpeg',
								'mpeg' => 'video/mpeg',
								'mkv'  => 'video/x-matroska',
								'swf'  => 'application/x-shockwave-flash'
								);
	
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
		
		$this->gallery = $this->registry->gallery;
		$this->_albums = $this->registry->gallery->helper('albums');
	}
	
	/**
	 * Allow media within gallery?
	 * 
	 * @return @e boolean
	 */
	public function allow()
	{
		return true;
	}
	
	/**
	 * Return a mime type
	 * 
	 * @return	@e array
	 */
	public function getMimeType( $ext )
	{
		if ( strstr( $ext, '.' ) )
		{
			$ext = IPSText::getFileExtension( $ext );
		}
		
		return ( ! empty( $this->_mtypes[ $ext ] ) ) ? $this->_mtypes[ $ext ] : null;
	}
	
	/**
	 * Return an array of allowed extensions
	 * 
	 * @return	@e array
	 */
	public function allowedExtensions()
	{
		return array_keys( $this->_ext );
	}
	
	/**
	 * Can use flash player
	 * 
	 * @return	@e array
	 */
	public function isAllowedExtension( $ext )
	{
		$ext = IPSText::getFileExtension( $ext );
		
		return isset($this->_ext[ $ext ]) ? true : false;
	}
	
	/**
	 * Can use flash player
	 * 
	 * @return	@e array
	 */
	public function canUseFlashPlayer( $image )
	{
		$dir  = ( $image['directory'] ) ? $image['directory'] . '/' : '/';
		$file = $this->settings['gallery_images_url'] . '/' . $dir . $image['masked_file_name'];
		$ext  = IPSText::getFileExtension( $file );
		
		if ( ! empty( $this->_ext[ $ext ] ) )
		{ 
			if ( $ext != 'flv' AND $ext != 'f4v' AND ! $this->_checkCodec( $this->settings['gallery_images_path'] . '/' . $dir . $image['masked_file_name'] ) )
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns HTML for the player
	 * @param  array $image
	 * @param  array Array of player options (size, width)
	 * @param int $opt
	 */
	public function getPlayerHtml( $image, $playerOptions=array(), $opt=0 )
	{
		$dir  = ( $image['directory'] ) ? $image['directory'] . '/' : '/';
		
		if ( $this->settings['gallery_web_accessible'] == 'yes' )
		{
			$file = $this->settings['gallery_images_url'] . '/' . $dir . $image['masked_file_name'];
		}
		else
		{
			$file = "{$this->settings['board_url']}/index.php?app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$image['id']}&amp;file=mediafull";
		}

		$ext  = IPSText::getFileExtension( $file );
		
		if ( $this->canUseFlashPlayer( $image ) AND $opt != GALLERY_MEDIA_FORCE_NO_FLASH_PLAYER )
		{
			return $this->_getFlashPlayer( $file, $playerOptions );
		}
		else
		{
			return $this->_getEmbedPlayer( $file, $playerOptions );
		}
	}
	
	/**
	 * Build media thumbnails
	 * 
	 * @access	public
	 * @since	2.1
	 * @param	array 	Image data
	 * @param	bool	Add watermark
	 * @return	@e bool
	 */	
	public function buildThumbs( $image, $opts=array() )
	{
		/* Check */
		if ( ! is_array( $image ) )
		{
			$image = $this->gallery->helper('image')->fetchImage( $image, GALLERY_IMAGES_FORCE_LOAD );
		}
		
		/* Init */
		$dir       = $image['directory'] ? $image['directory'] . "/" : '';
		$thumb     = $this->settings['gallery_images_path'] . '/' . $dir . 'tn_'  . $image['medium_file_name'];
		
		/* Ensure we have options */
		$_table     = 'gallery_images';
		$_field     = 'medium_file_name';
		$_thumb     = 'media_thumb';
		$_where     = 'id=' . $image['id'];
		
		/* Auto majestically assign the table */
		if ( ! isset( $opts['destination'] ) AND ! is_numeric( $image['id'] ) AND strlen( $image['id'] ) == 32 )
		{
			$opts['destination'] = 'uploads';
		}
		
		/* Tmp upload table */
		if ( isset( $opts['destination'] ) and $opts['destination'] == 'uploads' )
		{
			$_table     = 'gallery_images_uploads';
			$_field     = 'upload_medium_name';
			$_thumb     = 'upload_thumb_name';
			$_where     = 'upload_key=\'' . $image['id'] . '\'';
		}
		
		/* A little set up here */
		
		if ( ! empty( $_thumb  ) )
		{
			$_save[ $_thumb ] = '';
		}
		
		$settings  = array( 'image_path'	=> $this->settings['gallery_images_path'] . '/' . $dir, 
						    'image_file'	=> $image['medium_file_name'],
						    'im_path'		=> $this->settings['gallery_im_path'],
						    'temp_path'		=> DOC_IPS_ROOT_PATH . '/cache/tmp',
						    'jpg_quality'	=> GALLERY_JPG_QUALITY,
						    'png_quality'	=> GALLERY_PNG_QUALITY );
			
		/* Get kernel library */
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		
		if ( $this->settings['gallery_img_suite'] == 'im' )
		{
			require_once( IPS_KERNEL_PATH . 'classImageImagemagick.php' );/*noLibHook*/
			$img = new classImageImagemagick();
		}
		else
		{
			require_once( IPS_KERNEL_PATH . 'classImageGd.php' );/*noLibHook*/
			$img = new classImageGd();
		}
		
		/* Basic checks */
		if ( ! count($image) )
		{
			return false;
		}
		
		/* Not a media file */
		if ( ! $image['media'] )
		{
			return false;
		}
	
		/* Ensure we have a file on disk */
		if ( ! is_file( $this->settings['gallery_images_path'] . '/' . $dir . $image['medium_file_name'] ) )
		{
			return false;
		}
		
		/* Unlink the images */
		if ( is_file( $thumb ) )
		{
			@unlink( $thumb );
		}
		
		/* Prep Thumbnail */
		if ( $img->init( $settings ) )
		{
			$return = $img->croppedResize( 100, 100 );
			
			$img->addWatermark( IPSLib::getAppDir( 'gallery' ) . '/extensions/play_watermark.png', 60 );
			
			$img->writeImage( $thumb );
			
			if ( ! empty( $_thumb  ) )
			{
				$_save[ $_thumb ] = 'tn_'  . $image['medium_file_name'];
			}		
			
			unset( $img );
		}
		
		if ( count( $_save ) )
		{
			$this->DB->update( $_table, $_save, $_where );
		}
		
		return true;
	}
	
	/**
	 * Get thumbnail
	 * @param mixed $image
	 * @return	@e string
	 */
	public function getThumb( $image, $opts=array() )
	{
		/* Class */
		$opts['thumbClass'] = ( $opts['thumbClass'] ) ? $opts['thumbClass'] : 'galattach';
		
		if ( $opts['type'] == 'small' )
		{
			$opts['thumbClass'] = 'galsmall';
		}
		
		/* Is a cover image for an album? */
		if ( isset( $opts['coverImg'] ) AND $opts['coverImg'] === true )
		{
			if ( ! strstr( $opts['thumbClass'], 'cover_img___xx___' ) )
			{
				$opts['thumbClass'] .= ' cover_img___xx___';
				$opts['link-type']   = 'album';
			}
		}
		
		$i_am_new    = ( empty( $image['_isRead'] ) ) ? ' hello_i_am_new' : '';
		$i_am_hidden = ( isset( $image['approved'] ) && ! $image['approved'] ) ? ' hello_i_am_hidden' : '';
		
		if ( is_numeric( $image ) OR ( ! is_array( $image ) AND strlen( $image ) == 32 ) )
		{
			$image = $this->registry->gallery->helper('image')->fetchImage( $image );
		}
		
		$dir = $image['directory'] ? $image['directory'] . "/" : '';
		
		if ( $image['media_thumb'] )
		{
			if ( $this->settings['gallery_web_accessible'] == 'yes' )
			{
				$imagemg_url = $this->settings['gallery_images_url'] . "/" . $dir . $image['media_thumb'];
			}
			else
			{
				$imagemg_url = "{$this->settings['board_url']}/index.php?app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$image['id']}&amp;file=media";
			}
			
			if ( $opts['link-type'] == 'src' )
			{
				return $imagemg_url;
			}
			else
			{
				return "<img src='{$imagemg_url}' class='{$opts['thumbClass']}{$i_am_new}{$i_am_hidden}' width='100' height='100' title='{$image['caption']}' alt='{$image['caption']}' id='tn_image_view_{$image['id']}' />";	
			}
		}
		else
		{
			$imagemg_url = "{$this->settings['img_url']}/gallery/media_nothumb.png";
			
			if ( $opts['link-type'] == 'src' )
			{
				return $imagemg_url;
			}
			else
			{
				return "<img src='{$imagemg_url}' width='100' height='100' class='{$opts['thumbClass']}{$i_am_new}{$i_am_hidden}' />";
			}
		}
	}
	
	/**
	 * Checks to see if it's a h264 movie
	 */
	protected function _checkCodec( $file )
	{
		if ( ! is_file( $file ) )
		{
			return false;
		}
		
		$ret = false;
		
		$flash41 = array( 20, 32 );
		$fourcc  = array( 0x66747970 );
		
		$fh    = fopen( $file, 'rb');
		$_data = fread( $fh, 8);
		fclose( $fh );
		
		$two   = substr( $_data, 0, 2);
		$four1 = substr( $_data, 0, 4);
		$four2 = substr( $_data, 4, 4);
		
		$_t  = unpack( 'n', $two );
		$two = $_t[1];
		
		$_t    = unpack( 'N', $four1 );
		$four1 = $_t[1];
		
		$_t    = unpack( 'N', $four2 );
		$four2 = $_t[1];
		
		if ( in_array( $four2, $fourcc ) && in_array( $four1, $flash41 ) )
		{
			$ret = true;
		}	
		
		return $ret;
	}
	
	/**
	 * Gets the HTML for the EMBED player
	 * 
	 * @param	string		$file			File name
	 * @param	array		$playerOptions	Player options
	 * @return	@e string	HTML
	 */
	private function _getEmbedPlayer( $file, $playerOptions=array() )
	{
		return $this->registry->output->getTemplate('gallery_img')->mediaEmbedPlayer( $file, $playerOptions );
	}
	
	/**
	 * Gets the HTML for the flash player
	 * 
	 * @param	string		$file			File name
	 * @param	array		$playerOptions	Player options
	 * @return	@e string	HTML
	 */
	private function _getFlashPlayer( $file, $playerOptions=array() )
	{
		$playerOptions['size']   = ( empty( $playerOptions['size'] ) ) ? array( 640, 360 ) : $playerOptions['size'];
		$playerOptions['volume'] = ( empty( $playerOptions['volume'] ) ) ? 75 : intval( $playerOptions['volume'] );
		
		return $this->registry->output->getTemplate('gallery_img')->mediaFlashPlayer( $file, $playerOptions );
	}
}