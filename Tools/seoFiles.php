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
define( 'IPB_THIS_SCRIPT', 'public' );

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
		
		$this->_print( "--------------------------------------------\nWelcome to the IP.Board IP.Downloads SEO Fixer\n--------------------------------------------\n" );
		$this->_print( "Start from row?\nEnter: " );

		$start = intval( $this->_fetchOption() );
		
		$this->_print( "Do X rows\nEnter: " );
		
		$end = intval( $this->_fetchOption() );
		
		$this->_doCats();
		$this->_doFiles( $start, $end );
	}

	/**
	 * Rebuild categories
	 */
	protected function _doCats()
	{
		/* INIT */
		$start = time();
		$done  = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'downloads_categories',
								 //'limit'  => array( $_start, $end ) //those variables are always null
						 )		);
								
		$t = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $t ) )
		{
			$title_seo      = IPSText::makeSeoTitle( $row['cname'] );
			
			$this->DB->update( 'downloads_categories', array( 'cname_furl' => $title_seo ), 'cid=' . $row['cid'] );

			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "FINISHED CATEGORIES. Took " . $tkn . "m\n" );
	}
		
	/**
	 * Rebuild langs
	 */
	protected function _doFiles( $_start, $end )
	{
		/* INIT */
		$start = time();
		$done  = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'downloads_files',
								 'order'  => 'file_id ASC',
								 'limit'  => array( $_start, $end ) ) );
								
		$t = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $t ) )
		{
			$title_seo      = IPSText::makeSeoTitle( $row['file_name'] );
			
			$this->DB->update( 'downloads_files', array( 'file_name_furl' => $title_seo ), 'file_id=' . $row['file_id'] );
												
			$done++;
			
			if ( $done % 250 == 0 )
			{
				$this->_print( "Completed... " . $done . " (File ID=" . $row['file_id'] . " total=" . ( $_start + $done ) . ")" );
			}
			
			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "FINISHED FILES. Took " . $tkn . "m\n" );
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



?>