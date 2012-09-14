<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * FTP Class
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $ BY $Author: bfarber $
 * </pre>
 *
 * @author 		Mark Wade
 * @copyright	(c) 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

class classFtp
{
	/**
	 * Stream
	 *
	 * @param	resource
	 */
	private $stream;
	
	/**
	 * Directory
	 *
	 * @param	string
	 */
	private $directory;
	
	/**
	 * Transfer Mode
	 *
	 * @param	int
	 */
	public static $transferMode = FTP_ASCII;

	/** 
	 * Constructor
	 *
	 * @param	string		$host		Host name
	 * @param	string		$user		Username
	 * @param	string		$pass		Password
	 * @param	int			$port		Port
	 * @param	string		$dir		Initial directory
	 * @param	bool|null 	$passive	Passive mode, or NULL to use default
	 * @param	int			$timeout	Timeout
	 *
	 * @return	@e void
	 *
	 * @throws	Exception
	 *	@li FTP_NOT_INSTALLED (FTP module not installed in PHP)
	 *  @li CONNECT_FAIL (could not connect)
	 *  @li	LOGIN_FAIL (could not login)
	 *  @li	CHDIR_FAILED (could not set directory)
	 *  @li	PASV_FAIL (could not set passive mode)
	 */
	public function __construct( $host, $user, $pass, $port=21, $dir='/', $passive=NULL, $timeout=90 )
	{
		/* Verify FTP extension is available */
		if( !function_exists( 'ftp_connect' ) )
		{
			throw new Exception( 'FTP_NOT_INSTALLED' );
		}

		/* Connect */
		if ( !( $this->stream = ftp_connect( $host, $port, $timeout ) ) )
		{
			throw new Exception( 'CONNECT_FAIL' );
		}
		
		/* Login */
		if ( !@ftp_login( $this->stream, $user, $pass ) )
		{
			throw new Exception( 'LOGIN_FAIL' );
		}
		
		/* Set passive mode */
		if ( $passive !== NULL )
		{
			if( !@ftp_pasv( $this->stream, $passive ) )
			{
				throw new Exception( 'PASV_FAIL' );
			}
		}
		
		/* Change Directory */
		$this->chdir( $dir );
	}
	
	/**
	 * Destructor (closes the FTP connection automatically)
	 *
	 * @return	@e void
	 */
	public function __destruct()
	{
		@ftp_close( $this->stream );
	}
	
	/**
	 * List directory contents
	 *
	 * @return	@e array
	 */
	public function ls()
	{
		return @ftp_nlist( $this->stream, $this->directory );
	}
	
	/**
	 * Change Directory
	 *
	 * @param	string		$dir		New directory
	 * @return	@e void
	 * @throws	Exception	CHDIR_FAILED
	 */
	public function chdir( $dir )
	{
		if ( $dir == '..' )
		{
			$chdir = @ftp_cdup( $this->stream );
		}
		elseif ( substr( $dir, 0, 1 ) !== '/' )
		{
			$dir = $this->directory . '/' . $dir;
			$chdir = @ftp_chdir( $this->stream, $dir );
		}
		else
		{
			$chdir = @ftp_chdir( $this->stream, $dir );
		}
		
		if( !$chdir )
		{
			throw new Exception( 'CHDIR_FAILED' );
		}
		$this->directory = $dir;
	}
	
	/**
	 * Get File
	 *
	 * @param	string	$filename	Filename
	 * @return	@e classFtpFile
	 * @throws	Exception	NO_FILE
	 */
	public function file( $filename )
	{
		if( !( $size = @ftp_size( $this->stream, $filename ) ) )
		{
			throw new Exception( 'NO_FILE' );
		}
		$classFtpFile = new classFtpFile( $this->stream, $this->directory . '/' . $filename );
		$classFtpFile->size = $size;
		return $classFtpFile;
	}
	
	/**
	 * Upload
	 *
	 * @param	string	$filename	File to upload
	 * @param	string	$uploadName	Name to store on remote server as
	 * @return	@e classFtpFile
	 * @throws	Exception	UPLOAD_ERROR
	 */
	public function upload( $filename, $uploadName=NULL )
	{
		if ( !$uploadName )
		{
			$exploded = explode( '/', $filename );
			$uploadName = array_pop( $exploded );
		}
		
		if( !@ftp_put( $this->stream, $uploadName, $filename, self::$transferMode ) )
		{
			throw new Exception( 'UPLOAD_ERROR' );
		}
		
		return $this->file( $uploadName );
	}
	
	/**
	 * Create Directory
	 *
	 * @param	string	$name		Directory Name
	 * @return	@e void
	 * @throws	Exception	DIR_CREATE_ERROR
	 */
	public function mkdir( $name )
	{
		if( !@ftp_mkdir( $this->stream, $name ) )
		{
			throw new Exception( 'DIR_CREATE_ERROR' );
		}
	}
	
	/**
	 * Delete Directory
	 *
	 * @param	string	$name		Directory Name
	 * @return	@e void
	 * @throws	Exception	DIR_REMOVE_ERROR
	 */
	public function rmdir( $name )
	{
		if( !@ftp_rmdir( $this->stream, $name ) )
		{
			throw new Exception( 'DIR_REMOVE_ERROR' );
		}
	}
}

class classFtpFile
{
	/**
	 * Stream
	 *
	 * @param	resource
	 */
	private $stream;
	
	/**
	 * Filename
	 *
	 * @param	string
	 */
	private $filename;
	
	/**
	 * Constructor
	 *
	 * @param	resource	$stream		FTP Stream
	 * @param	string		$filename	Filename
	 * @return	@e void
	 */
	public function __construct( $stream, $filename )
	{
		$this->stream	= $stream;
		$this->filename	= $filename;
	}
	
	/**
	 * Download
	 *
	 * @param	string|null	$target		Local path to store file or NULL to use remote filename
	 * @return	@e void
	 * @throws	Exception	DOWNLOAD_ERROR
	 */
	public function download( $target=NULL )
	{
		if ( $target === NULL )
		{
			$exploded = explode( '/', $this->filename );
			$target = array_pop( $exploded );
		}
	
		if( !@ftp_get( $this->stream, $target, $this->filename, classFtp::$transferMode ) )
		{
			throw new Exception( 'DOWNLOAD_ERROR' );
		}
	}
	
	/**
	 * CHMOD
	 * 
	 * @param	int		$mode	Mode (in octal form)
	 * @return	@e void
	 * @throws	Exception	CHMOD_ERROR
	 */
	public function chmod( $mode )
	{
		if( !@ftp_chmod( $this->stream, $mode, $this->filename ) )
		{
			throw new Exception( 'CHMOD_ERROR' );
		}
	}
	
	/**
	 * Rename
	 *
	 * @param	string	$name	New filename
	 * @return	@e void
	 * @throws	Exception	RENAME_ERROR
	 */
	public function rename( $name )
	{
		if( !@ftp_rename( $this->stream, $this->filename, $name ) )
		{
			throw new Exception( 'RENAME_ERROR' );
		}
	}
	
	/**
	 * Delete
	 * 
	 * @return	@e void
	 * @throws	Exception	DELETE_ERROR
	 */
	public function delete()
	{
		if( !@ftp_delete( $this->stream, $this->filename ) )
		{
			throw new Exception( 'DELETE_ERROR' );
		}
	}
}