<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Last Updated: $Date: 2011-10-03 13:58:06 -0400 (Mon, 03 Oct 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9574 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_gallery
{	
	/**
	 * Key type: Type of action (topic/forum)
	 *
	 * @access	private
	 * @var		string
	 */
	private $_type = '';
	
	/**
	 * Key ID
	 *
	 * @access	private
	 * @var		int
	 */
	private $_id = 0;
	
	/**
	* Constructor
	*
	*/
	function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
	}

	/**
	 * Set the key ID
	 * <code>furlRedirect_forums::setKey( 'topic', 12 );</code>
	 *
	 * @access	public
	 * @param	string	Type
	 * @param	mixed	Value
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @access	public
	 * @param	string		URI (example: index.php?showtopic=5&view=getlastpost)
	 * @return	@e void
	 */
	public function setKeyByUri( $uri )
	{
		if( IN_ACP )
		{
			return FALSE;
		}
		
		$uri = str_replace( '&amp;', '&', $uri );

		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		if( $uri == 'app=gallery' )
		{
			$this->setKey( 'app', 'gallery' );
			return TRUE;			
		}
		else
		{
			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );
				
				if ( $k )
				{
					if ( $k == 'image' || ( $k == 'img' && $_REQUEST['module'] != 'post' ) )
					{
						$this->setKey( 'image', intval( $v ) );
						return TRUE;
					}
					
					if ( $k == 'album' && ( empty($_REQUEST['do']) || $_REQUEST['do'] != 'delete' ) )
					{
						$this->setKey( 'album', intval( $v ) );
						return TRUE;
					}
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	* Return the SEO title
	*
	* @access	public
	* @return	@e string		The SEO friendly name
	*/
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'image';
				return $this->_fetchSeoTitle_image();
			break;
			case 'album';
				return $this->_fetchSeoTitle_album();
			break;
			case 'app':
				return $this->_fetchSeoTitle_app();
			break;
		}
	}
	
	public function _fetchSeoTitle_album()
	{
		/* Query the image */
		$album = $this->DB->buildAndFetch( array( 'select' => 'album_id, album_name, album_name_seo', 'from' => 'gallery_albums_main', 'where' => "album_id={$this->_id}" ) );

		/* Make sure we have an image */
		if ( $album['album_id'] )
		{
			return $album['album_name_seo'] ? $album['album_name_seo'] : IPSText::makeSeoTitle( $album['album_name'] );
		}
	}
	
	public function _fetchSeoTitle_image()
	{
		/* Query the image */
		$img = $this->DB->buildAndFetch( array( 'select' => 'id,caption,caption_seo', 'from' => 'gallery_images', 'where' => "id={$this->_id}" ) );

		/* Make sure we have an image */
		if( $img['id'] )
		{
			return $img['caption_seo'] ? $img['caption_seo'] : IPSText::makeSeoTitle( $img['caption'] );
		}
	}

	/**
	* Return the base gallery SEO title
	*
	* @access	public
	* @return	@e string
	*/
	public function _fetchSeoTitle_app()
	{
		$_SEOTEMPLATES = array();
		
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Try to figure out what is used in furlTemplates.php */
			@include( IPSLib::getAppDir( 'gallery' ) . '/extensions/furlTemplates.php' );/*noLibHook*/
			
			if( $_SEOTEMPLATES['app=gallery']['out'][1] )
			{
				return $_SEOTEMPLATES['app=gallery']['out'][1];
			}
			else
			{
				return 'gallery/';
			}
		}
	}
}