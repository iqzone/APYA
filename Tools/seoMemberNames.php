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
		
		$this->_print( "--------------------------------------------\nWelcome to the IP.Board Member Name SEO Fixer\n--------------------------------------------\n" );
		$this->_print( "Start from row?\nEnter: " );

		$start = intval( $this->_fetchOption() );
		
		$this->_print( "Do X rows\nEnter: " );
		
		$end = intval( $this->_fetchOption() );
		
		$this->_doTopics( $start, $end );
	}
	
	/**
	 * Rebuild langs
	 */
	protected function _doTopics( $_start, $end )
	{
		/* INIT */
		$start = time();
		$done  = 0;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'members',
								 'order'  => 'member_id ASC',
								 'limit'  => array( $_start, $end ) ) );
						
		$t = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $t ) )
		{
			$members_seo_name = IPSText::makeSeoTitle( $row['members_display_name'] );
			
			$this->DB->update( 'members', array( 'members_seo_name' => $members_seo_name ), 'member_id=' . $row['member_id'] );
												
			$done++;
			
			if ( $done % 250 == 0 )
			{
				$this->_print( "Completed... " . $done . " (Last ID=" . $row['member_id'] . " total=" . ( $_start + $done ) . ")" );
			}
			
			/* Clear cached queries */
			$this->DB->obj['cached_queries'] = array();
		}
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "COMPLETE. Took " . $tkn . "m\n" );
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