<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Image Handler: GD2 Library
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

class classImageGd extends classImage implements interfaceImage
{
	/**
	 * Image resource
	 *
	 * @var		resource
	 */
	protected $image			= null;
	
	/**
	 * Image quality settings
	 *
	 * @var		array
	 */
	public $quality			= array( 'png' => 8, 'jpg' => 75 );
	
	/**
	 * Initiate image handler, perform any necessary setup
	 *
	 * @param	array 		Necessary options to init module [image_path, image_file]
	 * @return	@e boolean
	 */
	public function init( $opts=array() )
	{
	 	//---------------------------------------------------------
	 	// Verify input
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
	 	// Store paths
	 	//---------------------------------------------------------
	 			
		$this->image_path		= $this->_cleanPaths( $opts['image_path'] );
		$this->image_file		= $opts['image_file'];
		$this->image_full		= $this->image_path . '/' . $this->image_file;
		
	 	//---------------------------------------------------------
	 	// Get extension
	 	//---------------------------------------------------------
	 	
		$this->image_extension	= strtolower(pathinfo( $this->image_file, PATHINFO_EXTENSION ));
		
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
	 	// Get and remember dimensions
	 	//---------------------------------------------------------
	 
		$dimensions = @getimagesize( $this->image_full );
		
		$this->orig_dimensions	= array( 'width' => $dimensions[0], 'height' => $dimensions[1] );
		$this->cur_dimensions	= $this->orig_dimensions;
		
	 	//---------------------------------------------------------
	 	// Create image resource
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				$this->image = @imagecreatefromgif( $this->image_full );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				$this->image = @imagecreatefromjpeg( $this->image_full );
			break;
			
			case 'png':
				$this->image = @imagecreatefrompng( $this->image_full );
				
				if( $this->image )
				{
					@imagealphablending( $this->image, TRUE );
					@imagesavealpha( $this->image, TRUE );
				}
			break;
		}
		
		if( !$this->image )
		{
			//-----------------------------------------
			// Fallback
			// @see http://forums.invisionpower.com/index.php?app=tracker&showissue=17836
			//-----------------------------------------
			
			if( $this->image = @imagecreatefromstring( file_get_contents( $this->image_full ) ) )
			{
				return true;
			}

			$this->error		= 'no_full_image';
			
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Resizes and image, then crops an image to a fixed ratio
	 *
	 * @param	int		Width of final cropped image
	 * @param	int		Height of final cropped image
	 * @param	@e array
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
	 	// Get proportionate dimensions and store
	 	//---------------------------------------------------------
	 	
	 	$dst_x = 0;
	 	$dst_y = 0;
	 	
	 	if ( ! $secondPass )
	 	{
	 		$new_dims = $this->_getResizeDimensions( $width, $height );
	 		
	 		/* Canvas? */
	 		if ( is_array( $canvas ) && count( $canvas ) )
	 		{
	 			$storedNewDims = $new_dims;
	 			$new_dims 	   = array( 'img_width' => $canvas[0], 'img_height' => $canvas[1] );
	 			
	 			if ( $storedNewDims['img_width'] < $canvas[0] )
	 			{
	 				$dst_x = ( $canvas[0] - $storedNewDims['img_width'] ) / 2;
	 			}
	 			
	 			if ( $storedNewDims['img_height'] < $canvas[1] )
	 			{
	 				$dst_y = ( $canvas[1] - $storedNewDims['img_height'] ) / 2;
	 			}
	 		}
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

	 	//---------------------------------------------------------
	 	// Create new image resource
	 	//---------------------------------------------------------
	 	
	 	$new_img = @imagecreatetruecolor( $new_dims['img_width'], $new_dims['img_height'] );
		
		if ( ! $new_img )
	 	{
	 		$this->error		= 'image_creation_failed';
		 	return array();
	 	}
	 	
	 	
		/* If we specified a canvas, then we need to restore the $new_dims ready for the image copy */
		if ( is_array( $canvas ) and count( $canvas ) )
		{
			$new_dims = $storedNewDims;
			
			/* And fill canvas with white */
			@imagefilledrectangle( $new_img, 0, 0, $canvas[0], $canvas[1], imagecolorallocatealpha( $new_img, 255, 255, 255, 0 ) );
		}
	 	
	 	//---------------------------------------------------------
	 	// Apply alpha blending
	 	//---------------------------------------------------------
	 	
	 	
		switch( $this->image_extension )
		{			
			/* GIFs require a lot of extra work */
			case 'gif':
				/* Using the method described at http://us2.php.net/manual/en/function.imagecopyresampled.php#89987 for gif transparency. Bug #20826 */
				@imagealphablending( $new_img, FALSE );
				
				$transindex = @imagecolortransparent( $this->image );
				if( $transindex >= 0 ) 
				{
					$transcol	= @imagecolorsforindex( $this->image, $transindex );
					$transindex	= @imagecolorallocatealpha( $new_img, $transcol['red'], $transcol['green'], $transcol['blue'], 127 );
					@imagefill( $new_img, 0, 0, $transindex );
				}
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@imagealphablending( $new_img, TRUE );
			break;
			
			case 'png':
				@imagealphablending( $new_img, FALSE );
				@imagesavealpha( $new_img, TRUE );
			break;
		}
	

	 	//---------------------------------------------------------
	 	// Copy image resampled
	 	//---------------------------------------------------------
	 	
	 	@imagecopyresampled( $new_img, $this->image, $dst_x, $dst_y, intval( $this->_cropX ) , intval( $this->_cropY ), $new_dims['img_width'], $new_dims['img_height'], $this->cur_dimensions['width'], $this->cur_dimensions['height'] ); 
	 	
		 $this->cur_dimensions = array( 'width' => $new_dims['img_width'], 'height' => $new_dims['img_height'] );
	 	
	 	//---------------------------------------------------------
	 	// Don't forget the alpha blending
	 	//---------------------------------------------------------
	 	
	 	switch( $this->image_extension )
		{
			/* Even more gif work.... Bug #20826 */
			case 'gif':
				if( $transindex >= 0 )
				{
					@imagecolortransparent( $new_img, $transindex );
					
					for( $y = 0; $y < $new_dims['img_height']; ++$y )
					{
						for( $x = 0; $x < $new_dims['img_width']; ++$x )
						{
			      			if( ( ( @imagecolorat( $new_img, $x, $y) >> 24 ) & 0x7F ) >= 100 )
							{
								@imagesetpixel( $new_img, $x, $y, $transindex );
							}
						}
					}
				}
				
				@imagetruecolortopalette( $new_img, true, 255 );
				@imagesavealpha( $new_img, false );
			break;
			
			case 'png':
				@imagealphablending( $new_img, FALSE );
				@imagesavealpha( $new_img, TRUE );
			break;
		}
		
		/* If this is the second pass, see if we need to do a BG fill */
	 	if ( $secondPass )
	 	{
	 		if ( $this->orig_dimensions['width'] < $width AND $this->orig_dimensions['height'] < $height )
	 		{
	 			$_x = ( $new_dims['img_width'] - $this->orig_dimensions['width'] ) / 2;
	 			$_y = ( $new_dims['img_height'] - $this->orig_dimensions['height'] ) / 2;
	 			
	 			
	 			$rgb = @imagecolorat( $new_img, $_x + 1, $_y + 1 );
	 			$col = @imagecolorsforindex( $new_img, $rgb );
	 			
	 			/* If we've an almost transparent BG, then fill with offwhite */
	 			if ( $col['alpha'] > 120 )
	 			{
	 				$col = array( 'red' => '245', 'green' => '245', 'blue' => '245', 'alpha' => 0 );
	 			}
	 			else
	 			{
	 				$col['alpha'] = 100;
	 			}
							
	 			$iCol = @imagecolorallocatealpha( $new_img, $col['red'], $col['green'], $col['blue'], $col['alpha'] );
	 			
	 			/* T */
	 			@imagefilledrectangle( $new_img, 0, 0, $new_dims['img_width'], $_y, $iCol );
	 			/* R */
	 			@imagefilledrectangle( $new_img, 0, 0, $_x, $new_dims['img_height'], $iCol );
	 			/* B */
	 			@imagefilledrectangle( $new_img, 0, ( $_y + $this->orig_dimensions['height'] ), $new_dims['img_width'], ( ( $_y * 2 ) + $this->orig_dimensions['height'] ) , $iCol );
	 			/* L */
	 			@imagefilledrectangle( $new_img, ( $_x + $this->orig_dimensions['width'] ), 0, $new_dims['img_width'], $new_dims['img_height'], $iCol );
	 		}
	 	}

	 	//---------------------------------------------------------
	 	// Destroy original resource and store new resource
	 	//---------------------------------------------------------

	 	@imagedestroy( $this->image );
	 	
	 	$this->image	= $new_img;

	 	return array( 'originalWidth'  => $this->orig_dimensions['width'],
					  'originalHeight' => $this->orig_dimensions['height'],
					  'newWidth'       => ( is_array( $canvas ) && count( $canvas ) ) ? $canvas[0] : $new_dims['img_width'],
					  'newHeight'      => ( is_array( $canvas ) && count( $canvas ) ) ? $canvas[1] : $new_dims['img_height'] );
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
	 	// Write file and verify
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				@imagegif( $this->image, $imagePath );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@imagejpeg( $this->image, $imagePath, $this->quality['jpg'] );
			break;
			
			case 'png':
				@imagepng( $this->image, $imagePath, $this->quality['png'] );
			break;
		}
		
		if ( ! is_file( $imagePath ) )
		{
	 		$this->error = 'unable_to_write_image';
		 	return false;
	 	}
		
	 	//---------------------------------------------------------
	 	// Chmod 777
	 	//---------------------------------------------------------
	 	
	 	@chmod( $imagePath, IPS_FILE_PERMISSION );
	 	
	 	//---------------------------------------------------------
	 	// Destroy image resource
	 	//---------------------------------------------------------
	 	
	 	@imagedestroy( $this->image );
	 	
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
	 	// Send appropriate header and output image
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				@header('Content-type: image/gif');
				@imagegif( $this->image );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@header('Content-Type: image/jpeg' );
				@imagejpeg( $this->image, null, $this->quality['jpg'] );
			break;
			
			case 'png':
				@header('Content-Type: image/png' );
				@imagepng( $this->image, null, $this->quality['png'] );
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Destroy image resource
	 	//---------------------------------------------------------
	 	
	 	@imagedestroy( $this->image );
		
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
	 	// Create resource from watermark and verify
	 	//---------------------------------------------------------
	 	
		switch( $type )
		{
			case 'gif':
				$mark = imagecreatefromgif( $path );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				$mark = imagecreatefromjpeg( $path );
			break;
			
			case 'png':
				$mark = imagecreatefrompng( $path );
			break;
		}
		
		if( !$mark )
		{
	 		$this->error		= 'image_creation_failed';
		 	return false;
	 	}

	 	//---------------------------------------------------------
	 	// Alpha blending..
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'jpeg':
			case 'jpg':
			case 'jpe':
			case 'png':
				@imagealphablending( $this->image, TRUE );
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Get dimensions of watermark
	 	//---------------------------------------------------------
	 	
	 	$img_info		= @getimagesize( $path );
	 	$locate_x		= $this->cur_dimensions['width'] - $img_info[0];
	 	$locate_y		= $this->cur_dimensions['height'] - $img_info[1];

	 	//---------------------------------------------------------
	 	// Merge watermark image onto original image
	 	// @see	http://us.php.net/manual/en/function.imagecopymerge.php#32393
	 	//---------------------------------------------------------
	 	
		/* PNGs like to be difficult: Bug #20788 */
		if( $type == 'png' )
		{
			/* Create a new image */
			$new_img = imagecreatetruecolor( $this->cur_dimensions['width'], $this->cur_dimensions['height'] );
			
			/* Setup Transparency */
			imagealphablending( $new_img, false );
			$transparent = imagecolorallocatealpha( $new_img, 0, 0, 0, 127 );
			imagefill( $new_img, 0, 0, $transparent );
			imagesavealpha( $new_img, true );
			imagealphablending( $new_img, true );
			
			/* Copy the main image into the new image */
			imagecopyresampled( $new_img, $this->image, 0, 0, 0, 0, $this->cur_dimensions['width'], $this->cur_dimensions['height'], $this->cur_dimensions['width'], $this->cur_dimensions['height'] );
			
			/* Copy the watermark onto the new image */
		 	imagecopyresampled( $new_img, $mark, $locate_x, $locate_y, 0, 0, $img_info[0], $img_info[1], $img_info[0], $img_info[1] );
			
			/* Set the image */
			$this->image = $new_img;
		}
		else
		{
			@imagecopymerge( $this->image, $mark, $locate_x, $locate_y, 0, 0, $img_info[0], $img_info[1], $opacity );
		}

	 	//---------------------------------------------------------
	 	// And alpha blending again...
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'png':
				@imagealphablending( $this->image, FALSE );
				@imagesavealpha( $this->image, TRUE );
			break;
		}	 	

	 	//---------------------------------------------------------
	 	// Destroy watermark image resource and return
	 	//---------------------------------------------------------
	 	
	 	imagedestroy( $mark );

		$this->force_resize	= true;
		
	 	return true;
	}
	
    /**
	 * Add copyright text to image
	 *
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, halign, valign, padding, font [1-5])
	 * @return	@e boolean
	 */
	public function addCopyrightText( $text, $textOpts=array() )
	{
	 	//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------
	 	
		if( !$text )
		{
	 		$this->error		= 'no_text_for_copyright';
		 	return false;
	 	}
	 	
		$font	= $textOpts['font'] 	? $textOpts['font'] 			: 3;
		
	 	//---------------------------------------------------------
	 	// Colors input as hex...convert to rgb
	 	//---------------------------------------------------------
	 	
		$color	= $textOpts['color']	? array(
												hexdec( substr( ltrim( $textOpts['color'], '#' ), 0, 2 ) ),
												hexdec( substr( ltrim( $textOpts['color'], '#' ), 2, 2 ) ),
												hexdec( substr( ltrim( $textOpts['color'], '#' ), 5, 2 ) )
												)						: array( 255, 255, 255 );
		$width		= $this->cur_dimensions['width'] - 10;
		$halign		= ( isset($textOpts['halign']) AND in_array( $textOpts['halign'], array( 'right', 'center', 'left' ) ) )
										? $textOpts['halign']			: 'right';
		$valign		= ( isset($textOpts['valign']) AND in_array( $textOpts['valign'], array( 'top', 'middle', 'bottom' ) ) )
										? $textOpts['valign']			: 'bottom';
		$padding	= $textOpts['padding'] 	? $textOpts['padding'] 			: 5;
		
	 	//---------------------------------------------------------
	 	// Get some size info and set properties
	 	//---------------------------------------------------------
	 	
		$fontwidth	= imagefontwidth($font);
		$fontheight	= imagefontheight($font);

		$margin 	= floor($padding / 2 ); 

		if ( $width > 0 )
		{
			$maxcharsperline	= floor( ($width - ($margin * 2)) / $fontwidth);
			
			if( $maxcharsperline )
			{
				$text 				= wordwrap( $text, $maxcharsperline, "\n", 1 );
			}
		}

		$lines 					= explode( "\n", $text );

	 	//---------------------------------------------------------
	 	// Top, middle or bottom?
	 	//---------------------------------------------------------
	 	
		switch( $valign )
		{
			case "middle":
				$y = ( imagesy($this->image) - ( $fontheight * count($lines) ) ) / 2;
				break;

			case "bottom":
				$y = imagesy($this->image) - ( ( $fontheight * count($lines) ) + $margin );
				break;

			default:
				$y = $margin;
				break;
		}
		
	 	//---------------------------------------------------------
	 	// Allocate colors for text/bg
	 	//---------------------------------------------------------
	 	
		$color		= imagecolorallocate( $this->image, $color[0], $color[1], $color[2] );
		$rect_back	= imagecolorallocate( $this->image, 0,0,0 );
		
	 	//---------------------------------------------------------
	 	// Switch on horizontal position and write text lines
	 	//---------------------------------------------------------
	 	
		switch( $halign )
		{
			case "right":
				while( list($numl, $line) = each($lines) ) 
				{
					imagefilledrectangle( $this->image, ( imagesx($this->image) - $fontwidth * strlen($line) ) - $margin, $y, imagesx($this->image) - 1, imagesy($this->image) - 1, $rect_back );
					imagestring( $this->image, $font, ( imagesx($this->image) - $fontwidth * strlen($line) ) - $margin, $y, $line, $color );
					$y += $fontheight;
				}
				break;

			case "center":
				while( list($numl, $line) = each($lines) ) 
				{
					imagefilledrectangle( $this->image, floor( ( imagesx($this->image) - $fontwidth * strlen($line) ) / 2 ), $y, imagesx($this->image), imagesy($this->image), $rect_back );
					imagestring( $this->image, $font, floor( ( imagesx($this->image) - $fontwidth * strlen($line) ) / 2 ), $y, $line, $color );
					$y += $fontheight;
				}
			break;

			default:
				while( list($numl, $line) = each($lines) ) 
				{
					imagefilledrectangle( $this->image, $margin, $y, imagesx($this->image), imagesy($this->image), $rect_back );
					imagestring( $this->image, $font, $margin, $y, $line, $color );
					$y += $fontheight;
				}
			break;
		}
		
		$this->force_resize	= true;
		
		return true;
	}
}