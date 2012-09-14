#!/usr/local/bin/php
<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Main public executable wrapper.
 * Set-up and load module to run
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

define( 'IPS_IS_SHELL', TRUE );
define( 'IPB_THIS_SCRIPT', 'admin' );

if ( is_file( './initdata.php' ) )
{
	require_once( './initdata.php' );/*noLibHook*/
}
else
{
	require_once( '../initdata.php' );/*noLibHook*/
}

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

$reg = ipsRegistry::instance();
$reg->init();

/* Ensure it's CLI */
$cli = php_sapi_name() === 'cli';

if ( ! $cli )
{
      print "<html><head><title>Warning</title></head>\n";
      print "<body style='text-align:center'>\n";
      print "This script is meant to be run via command line<br />\n";
      print "More information:<br />\n";
      print "<a href=\"http://www.google.com/search?hl=en&q=php+cli+windows\" target=\"_blank\">http://www.google.com/search?hl=en&q=php+cli+windows</a><br />\n";
      print "This script will not run through a webserver.<br />\n";
      print "</body></html>\n";
      exit();
}


print "\n                   ";
print "\n (_) _ __   ____   ";
print "\n | || '_ \ / ___'  ";
print "\n | || |_) |  \__.  ";
print "\n | || .__/.\___  \ ";
print "\n |_||_|  |_______/  \n\n";

$moo = new moo( $reg );

class moo
{
	function __construct( ipsRegistry $registry )
	{
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->stdin      =  fopen('php://stdin', 'r');
		
		/* Gallery Object */
		require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
		$registry->setClass( 'gallery', new ipsGallery( $registry ) );
		
		$this->albums   = $this->registry->gallery->helper('albums');
		$this->images   = $this->registry->gallery->helper('image');
		$this->moderate = $this->registry->gallery->helper('moderate');
		
		/* Load up gallery boyo */
		$this->_print( "--------------------------------------------\nWelcome to the IP.Gallery Rebuild Tool\n--------------------------------------------\n" );
		$this->_print( "[0] Rebuild All Albums Node Data (Node level, left and right indexes)" );
		$this->_print( "[1] Rebuild Album Data (Cover image, comment counts, etc)" );
		$this->_print( "[2] Rebuild Album Permissions (Ensures inherited permissions are OK)" );
		$this->_print( "[3] Rebuild Image File Data (Thumbnail images, etc)" );
		$this->_print( "[4] Rebuild Image SEO Data" );
		
		$this->_print( "Enter Choice: ", "" );

		$option = $this->_fetchOption();
		
		if ( stristr( $option, 'look' ) )
		{
			$this->_print("\nYou see a forest full of shadows. In the distance smoke rises from a small building. There is a troll here." );
			exit;
		}
			
		switch( $option )
		{
			case 0:
				$this->_albumNodes();
			break;
			case 1:
				$this->_albumData();
			break;
			case 2:
				$this->_albumPermissions();
			break;
			case 3:
				$this->_images();
			break;
			case 4:
				$this->_seoImages();
			break;
			case 99:
				$this->_print("\n100!");
			break;
			default:
				$this->_print("\nThat wasn't a real option and I strongly believe you knew that");
			break;
		}
	}
	
	/**
	 * Rebuild All Nodes
	 */
	protected function _albumNodes()
	{
		$this->albums->rebuildNodeTree();
		
		$this->_print("Node Tree rebuilt");
		exit();
	}
	
	/**
	 * Rebuild All Permissions
	 */
	protected function _albumPermissions()
	{
		$this->moderate->resetPermissions(0);
		
		$this->_print("Permissions rebuilt");
		exit();
	}
	
	/**
	 * Rebuild All Album Data
	 */
	protected function _albumData()
	{
		$done  = 0;
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_albums_main',
								 'where'  => '1=1' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$done++;
			$this->albums->resync( $row );
		}
		
		$this->_print("Album Data rebuilt");
		exit();
	}

	/**
	 * Rebuild Images
	 */
	protected function _images()
	{
		/* INIT */
		$start = time();
		$done  = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_images',
								 'order'  => 'id ASC' )  );
								
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$done++;
			
			$this->images->resync( $row );
			$this->images->buildSizedCopies( $row );
			
			if ( $done % 50 == 0 )
			{
				$this->_print( "Completed... " . $done . " (IMG ID=" . $row['id'] . " total=" . ( $done ) . ")" );
			}
			
			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "Finished Images. Took " . $tkn . "m\n" );
	}
	
	/**
	 * Rebuild Images
	 */
	protected function _seoImages()
	{
		/* INIT */
		$start = time();
		$done  = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_images',
								 'order'  => 'id ASC' )  );
								
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$done++;
			
			$this->DB->update( 'gallery_images', array( 'caption_seo' => IPSText::makeSeoTitle( $row['caption'] ) ), 'id=' . $row['id'] );
			
			if ( $done % 50 == 0 )
			{
				$this->_print( "Completed... " . $done . " (IMG ID=" . $row['id'] . " total=" . ( $done ) . ")" );
			}
			
			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "Finished Images. Took " . $tkn . "m\n" );
	}


	/**
	 * Out to stdout
	 */
	protected function _print( $message, $newline="\n" )
	{
		$stdout = fopen('php://stdout', 'w');
		fwrite( $stdout, $message . $newline );
		fclose( $stdout );
	}
	
	/* Fetch option
	 *
	 */
	protected function _fetchOption()
	{
		return trim( fgets( $this->stdin ) );
	}
}

exit();