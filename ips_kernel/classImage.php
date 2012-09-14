<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Image Handler: create thumbnails, apply watermarks and copyright tests, save or display final image
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Monday 5th May 2008 14:00
 * @version		$Revision: 10721 $
 *
 * GD Example
 * <code>
 * $image = new classImageGd();
 * $image->init( array(
 * 					'image_path'	=> "/path/to/images/",
 * 					'image_file'	=> "image_filename.jpg",
 * 			)		);
 * 
 * if( $image->error )
 * {
 * 	print $image->error;exit;
 * }
 * 
 * Set max width and height
 * $image->resizeImage( 600, 480 );
 * // Add a watermark
 * $image->addWatermark( "/path/to/watermark/trans.png" );
 * //$image->addCopyrightText( "Hello World!", array( 'color' => '#ffffff', 'font' => 3 ) );
 * $image->displayImage();
 * </code>
 */

/**
 * Bootstrapper
 * Picks GD or imagemagik
 */
class ips_kernel_image
{
	/**
	 * Loads correct class
	 *
	 * @param	string		[gd (for GD library)|im (for ImageMagick)]
	 * @return	@e object
	 */
	static public function bootstrap( $load='gd' )
	{
		if ( $load == 'im' )
		{
			require_once( IPS_KERNEL_PATH . 'classImageImagemagick.php' );/*noLibHook*/
			$img = new classImageImagemagick();
		}
		else
		{
			require_once( IPS_KERNEL_PATH . 'classImageGd.php' );/*noLibHook*/
			$img = new classImageGd();
		}
		
		return $img;
	}
}

/**
 * Image interface
 *
 */
interface interfaceImage
{
    /**
	 * Initiate image handler, perform any necessary setup
	 *
	 * @param	array 		Necessary options to init module
	 * @return	@e boolean
	 */
	public function init( $opts=array() );
	
    /**
	 * Add a supported image type (assumes you have properly extended the class to add the support)
	 *
	 * @param	string 		Image extension type to add support for
	 * @return	@e boolean
	 */
	public function addImagetype( $ext );
	
	/**
	 * Resizes and image, then crops an image to a fixed ratio
	 *
	 * @param	int		Width of final cropped image
	 * @param	int		Height of final cropped image
	 * @param	@e array
	 */
	public function croppedResize( $width, $height );

	/**
	 * Crops an image
	 *
	 * @param	int			Crop from X
	 * @param	int			Crop from Y
	 * @param	int			Width
	 * @param	int			Height
	 * @return	@e array
	 */
	public function crop( $x1, $y1, $width, $height );

	/**
	 * Resize image proportionately
	 *
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @param	boolean		Second pass of image for crop
	 * @param	boolean		Instead of resizing image to same size, return false if the image doesn't need to be resized
	 * @param	array		Canvas size - if larger than resized image, image will be placed in the center
	 * @return	@e array
	 */
	public function resizeImage( $width, $height, $secondPass=false, $returnIfUnnecessary=false, $canvas=array() );
	
    /**
	 * Write image to file
	 *
	 * @param	string 		File location (including file name)
	 * @return	@e boolean
	 */
	public function writeImage( $path );
	
    /**
	 * Print image to screen
	 *
	 * @return	@e void
	 */
	public function displayImage();
	
    /**
	 * Add watermark to image
	 *
	 * @param	string 		Watermark image path
	 * @param	integer		[Optional] Opacity 0-100
	 * @return	@e boolean
	 */
	public function addWatermark( $path, $opacity=100 );
	
    /**
	 * Add copyright text to image
	 *
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, background color, font [1-5])
	 * @return	@e boolean
	 */
	public function addCopyrightText( $text, $textOpts=array() );

}

/**
 * Image abstract class
 *
 */
abstract class classImage
{
	/**
	 * Error encountered
	 *
	 * @var		string
	 */
	public $error				= '';
	
	/**
	 * Image Path
	 *
	 * @var		string
	 */
	protected $image_path		= '';
	
	/**
	 * Image File
	 *
	 * @var		string
	 */
	protected $image_file		= '';
	
	/**
	 * Image path + file
	 *
	 * @var		string
	 */
	protected $image_full		= '';
	
	/**
	 * Image dimensions
	 *
	 * @var		array
	 */
	protected $orig_dimensions	= array( 'width' => 0, 'height' => 0 );
	
	/**
	 * Image current dimensions
	 *
	 * @var		array
	 */
	public $cur_dimensions		= array( 'width' => 0, 'height' => 0 );
	
	/**
	 * Image Types Supported
	 *
	 * @var		array
	 */
	protected $image_types		= array( 'gif', 'jpeg', 'jpg', 'jpe', 'png' );
	
	/**
	 * Extension of image
	 *
	 * @var		string
	 */
	public $image_extension		= '';
	
	/**
	 * Resize image anyways (e.g. if we have added watermark)
	 *
	 * @var		bool
	 */
	public $force_resize		= false;

    /**
	 * Image handler constructor
	 *
	 * @return	@e oolean
	 */
	public function __construct()
	{
		return true;
	}
	
    /**
	 * Image handler desctructor
	 *
	 * @return	@e void
	 */
	public function __destruct()
	{
	}

	/**
	 * Cleans up paths, generates var $in_file_complete
	 *
	 * @param	string		Path to clean
	 * @return 	@e string
	 */
	protected function _cleanPaths( $path='' )
	{
	 	//---------------------------------------------------------
	 	// Remove trailing slash
	 	//---------------------------------------------------------
	 	
		return rtrim( $path, '/' );
	}
	
    /**
	 * Add a supported image type (assumes you have properly extended the class to add the support)
	 *
	 * @param	string 		Image extension type to add support for
	 * @return	@e boolean
	 */
	public function addImagetype( $ext )
	{
	 	//---------------------------------------------------------
	 	// Add a supported image extension
	 	//---------------------------------------------------------
	 	
		if( !in_array( $ext, $this->image_types ) )
		{
			$this->image_types[] = $ext;
		}
		
		return true;
	}
	
	/**
	 * Checks to see if an uploaded image truly is an image
	 *
	 * @param	string	Image path
	 * @return	@e mixed
	 */
	public function extractImageData( $imagePath )
	{
		$fileExt = IPSText::getFileExtension( $imagePath );
		
		$img_attributes = @getimagesize( $imagePath );
				
		if ( ! is_array( $img_attributes ) or ! count( $img_attributes ) )
		{
			return false;
		}
		else if ( ! $img_attributes[2] )
		{
			return false;
		}
		else if ( $img_attributes[2] == 1 AND ( $fileExt == 'jpg' OR $fileExt == 'jpeg' ) )
		{
			return false;
		}
		
		$return = array( 'width'    => $img_attributes[0],
						 'height'   => $img_attributes[1],
						 'fileType' => '' );
		
		switch( $img_attributes[2] )
		{
			case 1:
				$return['fileType'] = 'gif';
			break;
			case 2:
				$return['fileType'] = 'jpg';
			break;
			case 3:
				$return['fileType'] = 'png';
			break;
		}
		
		return $return;
	}
	
	/**
	* Checks for XSS inside file.  If found, deletes file, sets error_no to 5 and returns
	*
	* @param	string		Image path
	* @return	@e boolean
	*/
	public function hasXssInfile( $fileName )
	{
		// HTML added inside an inline file is not good in IE...
		$fh = fopen( $fileName, 'rb' );
		
		$file_check = fread( $fh, 512 );
		
		fclose( $fh );
		
		if ( ! $file_check )
		{
			return true;
		}
		# Thanks to Nicolas Grekas from comments at www.splitbrain.org for helping to identify all vulnerable HTML tags
		else if ( preg_match( '#<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si', $file_check ) )
		{
			return true;
		}
		
		return false;
	}
	
    /**
	 * Get new dimensions for resizing
	 *
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	@e array
	 */
	protected function _getResizeDimensions( $width, $height )
	{
	 	//---------------------------------------------------------
	 	// Verify width and height are valid and > 0
	 	//---------------------------------------------------------
	 	
		$width	= intval($width);
		$height	= intval($height);

		if( !$width OR !$height )
		{
			$this->error		= 'bad_dimensions';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Is the current image already smaller?
	 	//---------------------------------------------------------
	 	
		if( $width >= $this->cur_dimensions['width'] AND $height >= $this->cur_dimensions['height'] )
		{
			$this->error		= 'already_smaller';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Return new dimensions
	 	//---------------------------------------------------------

		return $this->_scaleImage( array(
										'cur_height'	=> $this->cur_dimensions['height'],
										'cur_width'		=> $this->cur_dimensions['width'],
										'max_height'	=> $height,
										'max_width'		=> $width,
								)		);
	}

	/**
	 * Return proportionate image dimensions based on current and max dimension settings
	 *
	 * @param	array 		[ cur_height, cur_width, max_width, max_height ]
	 * @return	@e array
	 */
	protected function _scaleImage( $arg )
	{
		$ret = array(
					  'img_width'  => $arg['cur_width'],
					  'img_height' => $arg['cur_height']
					);
		
		if ( $arg['cur_width'] > $arg['max_width'] )
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil( ( $arg['cur_height'] * ( ( $arg['max_width'] * 100 ) / $arg['cur_width'] ) ) / 100 );
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}
		
		if ( $arg['cur_height'] > $arg['max_height'] )
		{
			$ret['img_height']  = $arg['max_height'];
			$ret['img_width']   = ceil( ( $arg['cur_width'] * ( ( $arg['max_height'] * 100 ) / $arg['cur_height'] ) ) / 100 );
		}

		return $ret;
	}
}