<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Upload handler : Handles $_FILES and checks for security
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		15th March 2004
 * @version		$Revision: 10721 $
 *
 *
 * Example Usage:
 * <code>
 * $upload = new classUpload();
 * $upload->out_file_dir     = './uploads';
 * $upload->max_file_size    = '10000000';
 * $upload->make_script_safe = 1;
 * $upload->allowed_file_ext = array( 'gif', 'jpg', 'jpeg', 'png' );
 * $upload->process();
 *
 * if ( $upload->error_no )
 * {
 *	  switch( $upload->error_no )
 *	  {
 *		  case 1:
 *			  // No upload
 *			  print "No upload"; exit();
 *		  case 2:
 *		  case 5:
 *			  // Invalid file ext
 *			   print "Invalid File Extension"; exit();
 *		  case 3:
 *			  // Too big...
 *			   print "File too big"; exit();
 *         case 4:
 *			  // Cannot move uploaded file
 *			   print "Move failed"; exit();
 *	  }
 *  }
 * print $upload->saved_upload_name . " uploaded!";
 * </code>
 * ERRORS:
 * 1: No upload
 * 2: Not valid upload type
 * 3: Upload exceeds $max_file_size
 * 4: Could not move uploaded file, upload deleted
 * 5: File pretending to be an image but isn't (poss XSS attack)
 *
 */

if ( ! defined( 'IPS_FILE_PERMISSION' ) )
{
	 define( 'IPS_FILE_PERMISSION', 0777 );
}

if ( ! defined( 'IPS_FOLDER_PERMISSION' ) )
{
	 define( 'IPS_FOLDER_PERMISSION', 0777 );
}

class classUpload
{
	/**
	 * Name of upload form field
	 *
	 * @var		string
	 */
	public $upload_form_field	= 'FILE_UPLOAD';
	
	/**
	 * Out filename *without* extension
	 * (Leave blank to retain user filename)
	 *
	 * @var		string
	 */
	public $out_file_name		= '';
	
	/**
	 * Out dir (./upload) - no trailing slash
	 *
	 * @var		string
	 */
	public $out_file_dir		= './';
	
	/**
	 * Maximum file size of this upload
	 *
	 * @var		integer
	 */
	public $max_file_size		= 0;
	
	/**
	 * Forces PHP, CGI, etc to text
	 *
	 * @var		integer
	 */
	public $make_script_safe	= 1;
	
	/**
	 * Force non-img file extenstion (leave blank if not) (ex: 'ibf' makes upload.doc => upload.ibf)
	 *
	 * @var		string
	 */
	public $force_data_ext		= '';
	
	/**
	 * Allowed file extensions array( 'gif', 'jpg', 'jpeg'..)
	 *
	 * @var		array
	 */
	public $allowed_file_ext 	= array();
	
	/**
	 * Check file extension allowed
	 *
	 * @var		boolean
	 */
	public $check_file_ext	 	= true;
	
	/**
	 * Array of IMAGE file extensions
	 *
	 * @var		array
	 */
	public $image_ext			= array( 'gif', 'jpeg', 'jpg', 'jpe', 'png' );
	
	/**
	 * Check to make sure an image is an image
	 *
	 * @var		boolean
	 */
	public $image_check			= true;
	
	/**
	 * Returns current file extension	
	 *
	 * @var		string
	 */
	public $file_extension		= '';
	
	/**
	 * If force_data_ext == 1, this will return the 'real' extension
	 * and $file_extension will return the 'force_data_ext'
	 *
	 * @var		string
	 */
	public $real_file_extension	= '';
	
	/**
	 * Returns error number [1-5]
	 *
	 * @var		integer
	 */
	public $error_no			= 0;
	
	/**
	 * Returns if upload is img or not
	 *
	 * @var		boolean
	 */
	public $is_image			= 0;
	
	/**
	 * Returns file name as was uploaded by user
	 *
	 * @var		string
	 */
	public $original_file_name	= "";
	
	/**
	 * Returns final file name as is saved on disk. (no path info)
	 *
	 * @var		string
	 */
	public $parsed_file_name	= "";
	
	/**
	 * Returns final file name with path info
	 *
	 * @var		string
	 */
	public $saved_upload_name	= "";
	
	/**
	 * Processes the upload
	 *
	 * @return	@e boolean
	 */
	public function process()
	{
		$this->_cleanPaths();
		
		//-------------------------------------------------
		// Check for getimagesize
		//-------------------------------------------------
		
		if ( ! function_exists( 'getimagesize' ) )
		{
			$this->image_check = 0;
		}
		
		//-------------------------------------------------
		// Set up some variables to stop carpals developing
		//-------------------------------------------------
		
		$FILE_NAME = IPSText::parseCleanValue( str_replace( array( '<', '>' ), '-', isset($_FILES[ $this->upload_form_field ]['name']) ? $_FILES[ $this->upload_form_field ]['name'] : '' ) );
		$FILE_SIZE = isset($_FILES[ $this->upload_form_field ]['size']) ? $_FILES[ $this->upload_form_field ]['size'] : '';
		$FILE_TYPE = isset($_FILES[ $this->upload_form_field ]['type']) ? $_FILES[ $this->upload_form_field ]['type'] : '';

		//-------------------------------------------------
		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.
		//-------------------------------------------------
		
		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
		
		//-------------------------------------------------
		// Naughty Mozilla likes to use "none" to indicate an empty upload field.
		// I love universal languages that aren't universal.
		//-------------------------------------------------
		
		if ( !isset($_FILES[ $this->upload_form_field ]['name'])
			or $_FILES[ $this->upload_form_field ]['name'] == ""
			or !$_FILES[ $this->upload_form_field ]['name']
			or !$_FILES[ $this->upload_form_field ]['size']
			or $_FILES[ $this->upload_form_field ]['name'] == "none"
			)
		{
			if( $_FILES[ $this->upload_form_field ]['error'] == 2 )
			{
				$this->error_no = 3;
			}
			else if( $_FILES[ $this->upload_form_field ]['error'] == 1 )
			{
				$this->error_no = 3;
			}
			else
			{
				$this->error_no = 1;
			}
						
			return false;
		}
		
		if( !is_uploaded_file( $_FILES[ $this->upload_form_field ]['tmp_name'] ) )
		{
			$this->error_no = 1;
			return false;
		}
				
		//-------------------------------------------------
		// Do we have allowed file_extensions?
		//-------------------------------------------------
		
		if( $this->check_file_ext )
		{
			if ( ! is_array( $this->allowed_file_ext ) or ! count( $this->allowed_file_ext ) )
			{
				$this->error_no = 2;
				return false;
			}
		}
		
		$this->allowed_file_ext = array_map( 'strtolower', $this->allowed_file_ext );
		
		//-------------------------------------------------
		// Get file extension
		//-------------------------------------------------
		
		$this->file_extension = $this->_getFileExtension( $FILE_NAME );

		if ( ! $this->file_extension )
		{
			$this->error_no = 2;
			return false;
		}
		
		$this->real_file_extension = $this->file_extension;
		
		//-------------------------------------------------
		// Valid extension?
		//-------------------------------------------------
		
		if ( $this->check_file_ext AND !in_array( $this->file_extension, $this->allowed_file_ext ) )
		{
			$this->error_no = 2;
			return false;
		}
		
		//-------------------------------------------------
		// Check the file size
		//-------------------------------------------------
		
		if ( ( $this->max_file_size ) and ( $FILE_SIZE > $this->max_file_size ) )
		{
			$this->error_no = 3;
			return false;
		}
		
		//-------------------------------------------------
		// Make the uploaded file safe
		// Storing original_file_name before replacements
		//-------------------------------------------------
		
		$this->original_file_name = $FILE_NAME;
		
		$FILE_NAME = preg_replace( '/[^\w\.]/', "_", $FILE_NAME );

		//-------------------------------------------------
		// Convert file name?
		// In any case, file name is WITHOUT extension
		//-------------------------------------------------
		
		if ( $this->out_file_name )
		{
			$this->parsed_file_name = $this->out_file_name;
		}
		else
		{
			$this->parsed_file_name = str_replace( '.' . $this->file_extension, "", $FILE_NAME );
		}
		
		//-------------------------------------------------
		// Make safe?
		//-------------------------------------------------
		
		$renamed = 0;
		
		if ( $this->make_script_safe )
		{
			if ( preg_match( '/\.(cgi|pl|js|asp|php|html|htm|jsp|jar)(\.|$)/i', $FILE_NAME ) )
			{
				$FILE_TYPE                 = 'text/plain';
				$this->file_extension      = 'txt';
				$this->parsed_file_name	   = preg_replace( '/\.(cgi|pl|js|asp|php|html|htm|jsp|jar)(\.|$)/i', "$2", $this->parsed_file_name );
				
				$renamed = 1;
			}
		}
		
		//-------------------------------------------------
		// Is it an image?
		//-------------------------------------------------

		if ( is_array( $this->image_ext ) and count( $this->image_ext ) )
		{
			if ( in_array( $this->real_file_extension, $this->image_ext ) )
			{
				$this->is_image = 1;
			}
		}

		//-------------------------------------------------
		// Add on the extension...
		//-------------------------------------------------
		
		if ( $this->force_data_ext and ! $this->is_image )
		{
			$this->file_extension = str_replace( ".", "", $this->force_data_ext ); 
		}
		
		$this->parsed_file_name .= '.' . $this->file_extension;
		
		//-------------------------------------------------
		// Copy the upload to the uploads directory
		// ^^ We need to do this before checking the img
		//    size for the openbasedir restriction peeps
		//    We'll just unlink if it doesn't checkout
		//-------------------------------------------------
		
		$this->saved_upload_name = $this->out_file_dir . '/' . $this->parsed_file_name;
		
		if ( ! @move_uploaded_file( $_FILES[ $this->upload_form_field ]['tmp_name'], $this->saved_upload_name ) )
		{
			$this->error_no = 4;
			return;
		}
		else
		{
			@chmod( $this->saved_upload_name, IPS_FILE_PERMISSION );
		}
		
		if( ! $renamed AND $this->file_extension != 'txt' )
		{
			$this->_checkXSSInfile();
			
			if( $this->error_no )
			{
				return false;
			}
		}
		
		//-------------------------------------------------
		// Is it an image?
		//-------------------------------------------------
		
		if ( $this->is_image )
		{
			//-------------------------------------------------
			// Are we making sure its an image?
			//-------------------------------------------------
			
			if ( $this->image_check )
			{
				$img_attributes = @getimagesize( $this->saved_upload_name );
				
				if ( ! is_array( $img_attributes ) or !count( $img_attributes ) )
				{
					@unlink( $this->saved_upload_name );
					$this->error_no = 5;
					return false;
				}
				else if ( ! $img_attributes[2] )
				{
					@unlink( $this->saved_upload_name );
					$this->error_no = 5;
					return false;
				}
				else if ( $img_attributes[2] == 1 AND ( $this->file_extension == 'jpg' OR $this->file_extension == 'jpeg' ) )
				{
					// Potential XSS attack with a fake GIF header in a JPEG
					@unlink( $this->saved_upload_name );
					$this->error_no = 5;
					return false;
				}
			}
		}
		
		//-------------------------------------------------
		// If filesize and $_FILES['size'] don't match then
		// either file is corrupt, or there was funny
		// business between when it hit tmp and was moved
		//-------------------------------------------------
		
		if( filesize($this->saved_upload_name) != $_FILES[ $this->upload_form_field ]['size'] )
		{
			@unlink( $this->saved_upload_name );
			
			$this->error_no = 1;
			return false;
		}
	}

	/**
	* Checks for XSS inside file.  If found, deletes file, sets error_no to 5 and returns
	*
	* @return	@e boolean
	*/
	protected function _checkXSSInfile()
	{
		// HTML added inside an inline file is not good in IE...
		$fh = fopen( $this->saved_upload_name, 'rb' ); 
		//$file_check = file_get_contents( $this->saved_upload_name ); 

		$file_check = fread( $fh, 512 ); 

		fclose( $fh ); 
		
		if ( ! $file_check )
		{
			@unlink( $this->saved_upload_name );
			$this->error_no = 5;
			return false;
		}
		# Thanks to Nicolas Grekas from comments at www.splitbrain.org for helping to identify all vulnerable HTML tags
		else if( preg_match( '#<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<cross\-domain\-policy#si', $file_check ) )
		{
			@unlink( $this->saved_upload_name );
			$this->error_no = 5;
			return false;
		}
		# Check against local file-inclusion PHP code
		//else if( strpos( $file_check, '<' . '?php' ) !== false OR strpos( $file_check, 'eval' ) !== false OR strpos( $file_check, 'base64_decode' ) !== false )
		//{
		//	@unlink( $this->saved_upload_name );
		//	$this->error_no = 5;
		//	return false;
		//}

		return true;
	}
	
	/**
	 * Returns the file extension of the current filename
	 *
	 * @param	string		Filename
	 * @return	@e string
	 */
	public function _getFileExtension( $file )
	{
		if( class_exists('IPSText') )
		{
			return IPSText::getFileExtension( $file );
		}

		return strtolower( str_replace( ".", "", substr( $file, strrpos( $file, '.' ) ) ) );
	}

	/**
	 * Trims off trailing slashes
	 *
	 * @return	@e void
	 */
	protected function _cleanPaths()
	{
		$this->out_file_dir = rtrim( $this->out_file_dir, '/' );
	}
}
