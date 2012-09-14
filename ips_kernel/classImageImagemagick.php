<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Image Handler: ImageMagick Library
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
 * ImageMagick Example
 * <code>
 * $image = new classImageImagemagick();
 * $image->init( array(
 * 					'image_path'	=> "/path/to/images/",
 * 					'image_file'	=> "image_filename.jpg",
 *					'im_path'		=> '/path/to/imagemagick/folder/',
 *					'temp_path'		=> '/tmp/',
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

class classImageImagemagick extends classImage implements interfaceImage
{
	/**
	 * ImageMagick Path
	 *
	 * @var		string
	 */
	protected $im_path		= null;
	
	/**
	 * Temp directory (must be writable)
	 *
	 * @var		string
	 */
	protected $temp_path		= '';
	
	/**
	 * Temp file (directory, name, .temp)
	 *
	 * @var		string
	 */
	protected $temp_file		= '';
	
	/**
	 * Image quality settings
	 *
	 * @var		array
	 */
	public $quality			= array( 'png' => 8, 'jpg' => 75 );
	
    /**
	 * Initiate image handler, perform any necessary setup
	 *
	 * @param	array 		Necessary options to init module [image_path, image_file, im_path, temp_path]
	 * @return	@e boolean
	 */
	public function init( $opts=array() )
	{
	 	//---------------------------------------------------------
	 	// Verify params
	 	//---------------------------------------------------------
	 	
		if( empty($opts['image_path']) )
		{
			$this->error		= 'no_image_path';
			return false;
		}
		
		if( empty($opts['image_file']) )
		{
			$this->error		= 'no_image_file';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Store params
	 	//---------------------------------------------------------
	 	
		$this->image_path		= $this->_cleanPaths( $opts['image_path'] );
		$this->image_file		= basename( $opts['image_file'] );
		$this->image_full		= $this->image_path . '/' . $this->image_file;
		$this->im_path			= ( $opts['im_path'] ) ? $this->_cleanPaths( $opts['im_path'] ) . '/' : '';
		$this->temp_path		= $this->_cleanPaths( $opts['temp_path'] );
		$this->temp_file		= $this->temp_path . '/' . $this->image_file . '.temp';
		
	 	//---------------------------------------------------------
	 	// Check paths and files
	 	//---------------------------------------------------------
	 	
		if ( $this->im_path && ! is_dir( $this->im_path ) )
		{
			$this->error		= 'bad_imagemagick_path';
			return false;
		}
		
		if( !is_dir( $this->temp_path ) )
		{
			$this->error		= 'bad_temp_path';
			return false;
		}
		
		if( !is_writable( $this->temp_path ) )
		{
			$this->error		= 'temp_path_not_writable';
			return false;
		}
		
		if( is_file( $this->temp_file ) )
		{
			@unlink( $this->temp_file );
		}
		
	 	//---------------------------------------------------------
	 	// Get image extension
	 	//---------------------------------------------------------
	 			
		$this->image_extension	= strtolower( pathinfo( $this->image_file, PATHINFO_EXTENSION ) );
		
	 	//---------------------------------------------------------
	 	// Verify this is a valid image type
	 	//---------------------------------------------------------
	 	
		if( !in_array( $this->image_extension, $this->image_types ) )
		{
			$this->error		= 'image_not_supported';
			return false;
		}
		
		//---------------------------------------------------------
		// Quality values
		//---------------------------------------------------------
		
		if( !empty($opts['jpg_quality']) )
		{
			$this->quality['jpg']	= $opts['jpg_quality'];
		}
		
		if( !empty($opts['png_quality']) )
		{
			$this->quality['png']	= $opts['png_quality'];
		}
		
	 	//---------------------------------------------------------
	 	// Get and store dimensions
	 	//---------------------------------------------------------
	 	
		$dimensions = getimagesize( $this->image_full );
		
		$this->orig_dimensions	= array( 'width' => $dimensions[0], 'height' => $dimensions[1] );
		$this->cur_dimensions	= $this->orig_dimensions;
		
		return true;
	}
	
	/**
	 * Resizes and image, then crops an image to a fixed ratio
	 *
	 * @param	int		Width of final cropped image
	 * @param	int		Height of final cropped image
	 * @return	@e array
	 */
	public function croppedResize( $width, $height )
	{
		/* Changes here made to prevent "division by 0" errors when getimagesize() failed */
	 	$ratioOrig = $this->orig_dimensions['width'] / ( $this->orig_dimensions['height'] ? $this->orig_dimensions['height'] : 1 );
	 	
	 	/* This breaks square thumbs - always adds black bars */
	 	if( $ratioOrig < 1 )
	 	{
	 		//$ratioOrig	= 1;
	 	}
  
	    if ( $width / $height > $ratioOrig ) 
	    {
	       $nheight = $width / $ratioOrig;
	       $nwidth  = $width;
	    }
	    else
	    {
	       $nwidth  = $height * $ratioOrig;
	       $nheight = $height;
	    }
	
        $vals = $this->resizeImage( $nwidth, $nheight );
       
   		/* Crop from center */
   		$_x = $vals['newWidth'] / 2;
   		$_y = $vals['newHeight'] / 2;
   		
        $this->_cropX = ( $_x - ( $width / 2 ) );
        $this->_cropY = ( $_y - ( $height / 2 ) );
       
        $this->cur_dimensions['width']  = $width;
 	    $this->cur_dimensions['height'] = $height;

		return $this->resizeImage( $width, $height, true );
	}
	
	/**
	 * Crops an image
	 *
	 * @param	int			Crop from X
	 * @param	int			Crop from Y
	 * @param	int			Width
	 * @param	int			Height
	 * @return	@e array
	 */
	public function crop( $x1, $y1, $width, $height )
	{  		
        $this->_cropX = $x1;
        $this->_cropY = $y1;
       
        $this->cur_dimensions['width']  = $width;
 	    $this->cur_dimensions['height'] = $height;

		return $this->resizeImage( $width, $height, true );
	}
	
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
	public function resizeImage( $width, $height, $secondPass=false, $returnIfUnnecessary=false, $canvas=array() )
	{
	 	//---------------------------------------------------------
	 	// Grab proportionate dimensions and remember
	 	//---------------------------------------------------------
	 	
	 	if ( ! $secondPass )
	 	{
	 		$new_dims = $this->_getResizeDimensions( $width, $height );
	 	}
		else
		{
			$new_dims['img_width']  = $width;
			$new_dims['img_height'] = $height;
		}
		
		if( ! is_array($new_dims) OR !count($new_dims) OR !$new_dims['img_width'] )
		{
			if( ! $this->force_resize )
			{ 
				return array( 'originalWidth'  => $this->orig_dimensions['width'],
					 		  'originalHeight' => $this->orig_dimensions['height'],
					 		  'newWidth'       => $this->orig_dimensions['width'],
					 		  'newHeight'      => $this->orig_dimensions['height'],
							  'noResize'	   => true );
			}
			else
			{
				// @see	http://community.invisionpower.com/tracker/issue-20025-attached-images-gets-resized-even-when-its-dimensions-are-lower-than-limits/
				$new_dims['img_width']	= ( $width > $this->orig_dimensions['width'] ) ? $this->orig_dimensions['width'] : $width;
				$new_dims['img_height']	= ( $height > $this->orig_dimensions['height'] ) ? $this->orig_dimensions['height'] : $height;
			}
		}

		$this->cur_dimensions = array( 'width' => $new_dims['img_width'], 'height' => $new_dims['img_height'] );

		//---------------------------------------------------------
		// Need image type for quality setting
		//---------------------------------------------------------
		
		$type		= strtolower( pathinfo( basename($this->image_full), PATHINFO_EXTENSION ) );
		$quality	= '';
		
		if( $type == 'jpg' OR $type == 'jpeg' )
		{
			$quality	= " -quality {$this->quality['jpg']}";
		}
		else if( $type == 'png' )
		{
			$quality	= " -quality {$this->quality['png']}";
		}
		
	 	//---------------------------------------------------------
	 	// Resize image to temp file
	 	//---------------------------------------------------------
	 	
	 	if ( $type == 'gif' )
	 	{
	 		system("{$this->im_path}convert {$this->image_full} -coalesce  {$this->temp_file}");
	 		
		 	$this->image_full = $this->temp_file;
	 	}
	 	
	 	/* Canvas? */
 		if ( is_array( $canvas ) && count( $canvas ) )
 		{
 			$border 	= array( 0, 0 );
 			$borderStmt = '';
 			
 			/* Figure out border */
 			if ( $new_dims['img_width'] < $width )
 			{
 				$border[0] = ( $width - $new_dims['img_width'] ) / 2;
 			}
 			
 			if ( $new_dims['img_height'] < $height )
 			{
 				$border[1] = ( $height - $new_dims['img_height'] ) / 2;
 			}
 			
 			if ( count( $border ) )
 			{
 				$borderStmt = ' -bordercolor white -border ' . $border[0] . 'x' . $border[1];
 			}
 			
 			system("{$this->im_path}convert{$quality} -resize \"{$new_dims['img_width']}x{$new_dims['img_height']}!\" {$borderStmt} {$this->image_full} {$this->temp_file}" );
 		}
	 	else if ( ! empty( $this->_cropX ) || ! empty( $this->_cropY ) )
	 	{
        	system("{$this->im_path}convert{$quality} -resize \"{$new_dims['img_width']}x{$new_dims['img_height']}^\" -crop \"{$width}x{$height}\" {$this->image_full} {$this->temp_file}" );
        }
        else
        {
        	system("{$this->im_path}convert{$quality} -resize \"{$new_dims['img_width']}x{$new_dims['img_height']}!\" {$this->image_full} {$this->temp_file}" );
		}
                
	 	//---------------------------------------------------------
	 	// Successful?
	 	//---------------------------------------------------------
	 	
	 	if ( is_file( $this->temp_file ) )
	 	{
		 	return array( 'originalWidth'  => $this->orig_dimensions['width'],
						  'originalHeight' => $this->orig_dimensions['height'],
						  'newWidth'       => ( is_array( $canvas ) && count( $canvas ) ) ? $canvas[0] : $new_dims['img_width'],
					  	  'newHeight'      => ( is_array( $canvas ) && count( $canvas ) ) ? $canvas[1] : $new_dims['img_height'] );
	 	}
	 	else
	 	{
		 	return array();
	 	}
	}
	
    /**
	 * Write image to file
	 *
	 * @param	string 		File location (including file name)
	 * @return	@e boolean
	 */
	public function writeImage( $imagePath )
	{
	 	//---------------------------------------------------------
	 	// Remove image if it exists
	 	//---------------------------------------------------------
	 	
		if( is_file( $imagePath ) )
		{
			@unlink( $imagePath );
		}
	
	 	//---------------------------------------------------------
	 	// Temp file doesn't exist
	 	//---------------------------------------------------------
	 	
		if( !is_file( $this->temp_file ) )
		{
	 		$this->error = 'temp_image_not_exists';
		 	return false;
		}
		
	 	//---------------------------------------------------------
	 	// Rename temp file to final destination
	 	//---------------------------------------------------------
	 	
		rename( $this->temp_file, $imagePath );
		
		if( !is_file( $imagePath ) )
		{
	 		$this->error = 'unable_to_write_image';
		 	return false;
	 	}
		
	 	//---------------------------------------------------------
	 	// Chmod 777 and return
	 	//---------------------------------------------------------
	 		 	
	 	@chmod( $imagePath, IPS_FILE_PERMISSION );
	 	
	 	return true;
	}
	
    /**
	 * Print image to screen
	 *
	 * @return	@e void
	 */
	public function displayImage()
	{
	 	//---------------------------------------------------------
	 	// Print appropriate header
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				@header('Content-type: image/gif');
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@header('Content-Type: image/jpeg' );
			break;
			
			case 'png':
				@header('Content-Type: image/png' );
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Print file contents and exit
	 	//---------------------------------------------------------
	 	
		print file_get_contents( $this->temp_file );
		
		exit;
	}

    /**
	 * Add watermark to image
	 *
	 * @param	string 		Watermark image path
	 * @param	integer		[Optional] Opacity 0-100
	 * @return	@e boolean
	 */
	public function addWatermark( $path, $opacity=100 )
	{
	 	//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------
	 	
		if( !$path )
		{
			$this->error		= 'no_watermark_path';
			return false;
		}
		
		$type		= strtolower( pathinfo( basename($path), PATHINFO_EXTENSION ) );
		$opacity	= $opacity > 100 ? 100 : ( $opacity < 0 ? 1 : $opacity );
		
		if( !in_array( $type, $this->image_types ) )
		{
			$this->error		= 'bad_watermark_type';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Get dimensions
	 	//---------------------------------------------------------
	 	
	 	$img_info	= @getimagesize( $path );
	 	$locate_x	= $this->cur_dimensions['width'] - $img_info[0];
	 	$locate_y	= $this->cur_dimensions['height'] - $img_info[1];

	 	//---------------------------------------------------------
	 	// Working with original file or temp file?
	 	//---------------------------------------------------------
	 	
		$file 		= is_file( $this->temp_file ) ? $this->temp_file : $this->image_full;
		
	 	//---------------------------------------------------------
	 	// Apply watermark and verify
	 	//---------------------------------------------------------
	 	
		system("{$this->im_path}composite -geometry +{$locate_x}+{$locate_y} {$path} {$file} {$this->temp_file}" );

	 	if( is_file( $this->temp_file ) )
	 	{
	 		$this->force_resize	= true;
	 		
		 	return true;
	 	}
	 	else
	 	{
		 	return false;			
	 	}
	}
	
    /**
	 * Add copyright text to image
	 *
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, halign, valign, font [1-5])
	 * @return	@e boolean
	 */
	public function addCopyrightText( $text, $textOpts=array() )
	{
	 	//---------------------------------------------------------
	 	// Have text?
	 	//---------------------------------------------------------
	 	
		if( !$text )
		{
	 		$this->error		= 'no_text_for_copyright';
		 	return false;
	 	}
	 	
	 	//---------------------------------------------------------
	 	// @ causes IM to try to read text from file specified by @
	 	//---------------------------------------------------------
	 	$text	= ltrim( $text, '@' );
	 	
	 	//---------------------------------------------------------
	 	// Verify options
	 	//---------------------------------------------------------
	 		 	
		$font	= $textOpts['font'] 	? $textOpts['font'] 			: 3;
		$color	= $textOpts['color']	? $textOpts['color']			: '#ffffff';
		$width	= $this->cur_dimensions['width'] - 10;
		$halign	= ( isset($textOpts['halign']) AND in_array( $textOpts['halign'], array( 'right', 'center', 'left' ) ) )
										? $textOpts['halign']			: 'right';
		$valign	= ( isset($textOpts['valign']) AND in_array( $textOpts['valign'], array( 'top', 'middle', 'bottom' ) ) )
										? $textOpts['valign']			: 'bottom';
		
	 	//---------------------------------------------------------
	 	// Working with orig file or temp file?
	 	//---------------------------------------------------------
	 	
		$file 		= is_file( $this->temp_file ) ? $this->temp_file : $this->image_full;
		
	 	//---------------------------------------------------------
	 	// Set gravity (location of text)
	 	//---------------------------------------------------------
	 	
		$gravity	= "";
		
		switch( $valign )
		{
			case 'top':
				$gravity = "North";
			break;
			
			case 'bottom':
				$gravity = "South";
			break;
		}
		
		if( $valign == 'middle' AND $halign == 'center' )
		{
			$gravity = "Center";
		}
		
		switch( $halign )
		{
			case 'right':
				$gravity .= "East";
			break;
			
			case 'left':
				$gravity .= "West";
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Apply annotation to image and verify
	 	//---------------------------------------------------------
	 			
		system("{$this->im_path}composite {$file} -fill {$color} -undercolor #000000 -gravity {$gravity} -annotate +0+5 '{$text}' {$this->temp_file}" );

	 	if( is_file( $this->temp_file ) )
	 	{
	 		$this->force_resize	= true;
	 		
		 	return true;
	 	}
	 	else
	 	{
		 	return false;			
	 	}
	}
	
    /**
	 * Image handler desctructor
	 *
	 * @return	@e void
	 */
	public function __destruct()
	{
	 	//---------------------------------------------------------
	 	// Remove temp file if it hasn't been saved
	 	//---------------------------------------------------------
	 	
		if( is_file( $this->temp_file ) )
		{
			@unlink( $this->temp_file );
		}
		
		parent::__destruct();
	}
}