#!/usr/local/bin/php
<?php

ini_set('display_errors', 1);
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Rebuilds stuff from XML
 * Last Updated: $Date: 2012-06-06 15:26:34 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10878 $
 *
 */

define( 'IPS_IS_SHELL', TRUE );
define( 'IPB_THIS_SCRIPT', 'admin' );

if ( is_file( './initdata.php' ) )
{
	require_once( './initdata.php' );/*noLibHook*/
}
elseif ( is_file( '../initdata.php' ) )
{
	require_once( '../initdata.php' );/*noLibHook*/
}
else
{
	require_once( 'initdata.php' );/*noLibHook*/
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
		
		$this->_print( "--------------------------------------------\nWelcome to the IN_DEV build tool" );
		$this->_print( "This tool will create the required directories and files to run in IN_DEV\n--------------------------------------------\n" );
		
		/* Is IN_DEV on? */
		if ( ! IN_DEV )
		{
			$this->_print( "IN_DEV is not on. Please edit 'conf_global.php' and add: define( 'IN_DEV', 1 );" );
			exit();
		}
		
		/* Can we write into require folders? */
		$req  = array( 'cache', 'cache/skin_cache', 'cache/lang_cache', 'cache/lang_cache/1', PUBLIC_DIRECTORY . '/style_css' );/*noLibHook*/
		$stop = false;
		
		foreach( $req as $r )
		{
			if ( ! is_dir( DOC_IPS_ROOT_PATH . $r ) )
			{
				$this->_print( "Cannot locate: $r - please CHMOD appropriately and re-run" );
				$stop = true;
			}
			
			if ( ! is_writable( DOC_IPS_ROOT_PATH . $r ) )
			{
				$this->_print( "Cannot write to: $r - please CHMOD appropriately and re-run" );
				$stop = true;
			}
		}
		
		if ( $stop )
		{
			exit();
		}
		
		$this->_print( "Hit Any Key To Continue: " );

		$option = $this->_fetchOption();

		/* Export all skins required */
		$this->_doSkins();
		
		/* Export all language bits */
		$this->_doLang();
	}
	
	/**
	 * Rebuild skins
	 */
	protected function _doSkins()
	{
		/* INIT */
		$start    = time();
		$output   = array();
		$errors   = array();
		
		/* Grab class */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );/*noLibHook*/
		
		$skinFunctions = new skinImportExport( $this->registry );
		
		/* Fetch master dirs */
		$master = $skinFunctions->fetchMasterKeys();
		
		if ( ! count( $master ) )
		{
			$this->_print( "Could not locate any master skin keys" );
			exit();
		}
		
		/* PHP */
		foreach( $master as $m )
		{
			$path = ( $m == 'root' )    ? IPS_CACHE_PATH . 'cache/skin_cache/master_skin'     : IPS_CACHE_PATH . 'cache/skin_cache/master_skin_' . $m; 
			$path = ( $m == 'xmlskin' ) ? IPS_CACHE_PATH . 'cache/skin_cache/master_skin_xml' : $path; 
			
			if ( is_dir( $path ) )
			{
				$this->_print( "$path already exists..." );
				continue;
			}
			
			if ( ! mkdir( $path, IPS_FOLDER_PERMISSION ) )
			{
				$this->_print( "Could not create $path" );
				exit();
			}
			else
			{
				@chmod( $path, IPS_FOLDER_PERMISSION );
				$this->_print( "$path created..." );
			}
		}
		
		/* CSS */
		foreach( $master as $m )
		{
			$path = ( $m == 'root' )    ? DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css/master_css'         : DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css/master_css_' . $m; 
			$path = ( $m == 'xmlskin' ) ? DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css/master_css_xml' : $path; 
			
			if ( is_dir( $path ) )
			{
				$this->_print( "$path already exists..." );
				continue;
			}
			
			if ( ! mkdir( $path, IPS_FOLDER_PERMISSION ) )
			{
				$this->_print( "Could not create $path" );
				exit();
			}
			else
			{
				@chmod( $path, IPS_FOLDER_PERMISSION );
				$this->_print( "$path created..." );
			}
		}
		
		/* Now create master dirs for PHP */
		foreach( $master as $m )
		{
			$dir	= ( $m == 'root' ) ? 'master_skin' : 'master_skin_' . $m;
			$dir	= ( $m == 'xmlskin' ) ? 'master_skin_xml' : $dir;
			
			$skinFunctions->writeMasterSkin( $m, $dir );
			
			$this->_print( "PHP templates written for $m" );
		}
		
		/* Now create master dirs for CSS */
		foreach( $master as $m )
		{
			$dir	= ( $m == 'root' ) ? 'master_css' :'master_css_' . $m;
			$dir	= ( $m == 'xmlskin' ) ? 'master_css_xml' : $dir;
			
			$skinFunctions->writeMasterSkinCss( $m, $dir );
			
			$this->_print( "CSS written for $m" );
		}
		
		/* Now create master dirs for replacements */
		foreach( $master as $m )
		{
			$dir	= ( $m == 'root' ) ? 'master_skin' : 'master_skin_' . $m;
			$dir	= ( $m == 'xmlskin' ) ? 'master_skin_xml' : $dir;
			
			$skinFunctions->writeMasterSkinReplacements( $m, $dir );
			
			$this->_print( "Replacements written for $m" );
		}

		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "COMPLETE. Took " . $tkn . "m\n" );
	}
	
	/**
	 * Rebuild langs
	 */
	protected function _doLang()
	{
		/* INIT */
		$start    = time();
		
		/* Grab class */
		require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php' );/*noLibHook*/
		$lang = new admin_core_languages_manage_languages( $this->registry );
		$lang->makeRegistryShortcuts( $this->registry );
		
		$lang->cacheToDisk( 'master' );
				
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "Master languages written" );
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
