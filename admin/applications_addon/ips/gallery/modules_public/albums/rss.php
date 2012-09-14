<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Category Listing
 * Last Updated: $LastChangedDate: 2011-05-20 06:00:55 -0400 (Fri, 20 May 2011) $
 * </pre>
 *
 * @author		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8849 $
 *
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_rss extends ipsCommand
{
	/**
	 * Album helper
	 * 
	 * @access	private
	 * @var		object
	 */
	private $albums;
	
	/**
	 * Image helper
	 * 
	 * @access	private
	 * @var		object
	 */
	private $images;
	
	/**
	 * RSS class
	 */
	private $rss;
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */ 
	public function doExecute( ipsRegistry $registry )
	{
		$albumId = intval( $this->request['album'] );
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$this->rss			 = new $classToLoad();
		$this->rss->doc_type = ipsRegistry::$settings['gb_char_set'];
		
		/* Set up class vars */
		$this->albums  = $this->registry->gallery->helper('albums');
		$this->images  = $this->registry->gallery->helper('image');
		$this->gallery = $this->registry->gallery;
		
		/* What the hell are we doing? */
		switch( $this->request['do'] )
		{
			case 'album':
			default:
				$this->_albumRss( $albumId );
			break;
		}
		
		/* Build document */
		$this->rss->createRssDocument();

		$this->rss->rss_document = $this->registry->output->replaceMacros( $this->rss->rss_document );
		
		if ( ! $this->rss->rss_document )
		{
			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
			{
				header("HTTP/1.0 503 Service Temporarily Unavailable");
			}
			else
			{
				header("HTTP/1.1 503 Service Temporarily Unavailable");
			}
			
			print $this->lang->words['rssappoffline'];
			exit();
		}
		
		//-----------------------------------------
		// Then output
		//-----------------------------------------
		
		@header( 'Content-Type: text/xml; charset=' . IPS_DOC_CHAR_SET );
		@header( 'Expires: ' . gmstrftime( '%c', ( time() + 3600 ) ) . ' GMT' );
		@header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		@header( 'Pragma: public' );
		print $this->rss->rss_document;
		exit();
	}
	
	/**
	 * RSS YOUR FACE
	 * 
	 * @access	protected
	 * @param	int			$albumId
	 * @return 	string		H T M L
	 */
	protected function _albumRss( $albumId )
	{
		/* Display method */
		$album = $this->albums->fetchAlbumsById( $albumId );
		
		if ( $album['album_id'] )
		{
			$channelId = $this->rss->createNewChannel( array( 'title'			=> $album['album_name'],
															  'link'			=> $this->registry->output->buildSEOUrl( "app=gallery&amp;album=" . $album['album_id'], 'publicNoSession', $album['album_name_seo'], 'viewalbum' ),
			 												  'description'	    => $album['album_name'] . " Syndication",
			 												  'pubDate'		    => $this->rss->formatDate( time() ),
			 												  'webMaster'		=> $this->settings['email_in'] . " ({$this->settings['board_name']})",
			 												  'generator'		=> 'IP.Gallery' ) );
			
			/* Fetch last 25 images */
			$recents = $this->images->fetchImages( $this->memberData['member_id'], array( 'albumId' => $album['album_id'], 'limit' => 20, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
			
			foreach( $recents as $id => $image )
			{
				$this->rss->addItemToChannel( $channelId , array( 'title'				=> $image['caption'],
																  'link'				=> $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $image['id'], 'publicNoSession', $image['caption_seo'], 'viewimage' ),
																  'description'			=> $this->images->makeImageLink( $image, array( 'type' => 'small', 'link-type' => 'page' ) ) . "<br />" . $image['description'],
																  'pubDate'				=> $this->rss->formatDate( $image['idate'] ),
																  'guid'				=> $this->registry->output->buildSEOUrl( "app=gallery&amp;image=" . $image['id'], 'publicNoSession', $image['caption_seo'], 'viewimage' ) ) );
			}
		}
	}
}