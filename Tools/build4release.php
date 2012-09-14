#!/usr/local/bin/php
<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Main public executable wrapper.
 * Set-up and load module to run
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
		
		$this->_print( "--------------------------------------------\nWelcome to the IP.Board Shell Get Stuff Ready For Release Tool\n--------------------------------------------\n" );
		$this->_print( "What do you wish to do? [l=Rebuild and Export Lang, s=Rebuild and Export Skins, a=Both]\nEnter: " );

		$option = $this->_fetchOption();

		switch( $option )
		{
			case 'l':
				$this->_doLang();
			break;
			case 's':
				$this->_doSkins();
			break;
			case 'a':
				$this->_doLang();
				$this->_doSkins();
			break;
			default:
				$this->_print( "\nIncorrect option" );
				exit();
			break;
		}
	}
	
	/**
	 * Rebuild langs
	 */
	protected function _doSkins()
	{
		/* INIT */
		$start    = time();
		$messages = array();
		$errors   = array();
		
		/* Grab class */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinImportExport( $this->registry );
		
		/* This is which skins we want to export for default installations */
		$skinIDs = array_values( $this->skinFunctions->remapData['export'] );
		
		//-----------------------------------------
		// Can do .. stuff?
		//-----------------------------------------
		
		if ( ! is_writable( IPS_ROOT_PATH . 'setup/xml/skins' ) )
		{
			$this->_print( 'Cannot write to /admin/setup/xml/skins' );
			exit();
		}
		
		//-----------------------------------------
		// Rebuild stuff
		//-----------------------------------------
		
		foreach( $skinIDs as $id )
		{
			$msg        = $this->skinFunctions->rebuildMasterCSS( $id );
			$messages[] = "<strong>CSS Rebuilt for $id</strong>";
			$messages   = array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			$msg        = $this->skinFunctions->rebuildMasterFromPHP( $id );
			$messages[] = "<strong>HTML Rebuilt for $id</strong>";
			$messages   = array_merge( $messages, $msg, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			$msg        = $this->skinFunctions->rebuildMasterReplacements( $id );
			$messages[] = "<strong>Replacements Rebuilt for $id</strong>";
			$messages   = array_merge( $messages, (array)$msg, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		}
		
		/* Got any errors? */
		if ( count( $errors ) )
		{
			$this->_print( 'ERROR: ' . implode( "\n *", $errors ) );
			//exit();
		}
		
		$this->skinFunctions->rebuildTreeInformation();
		$this->skinFunctions->rebuildSkinSetsCache();
		
		//-----------------------------------------
		// Rebuild skin caches
		//-----------------------------------------
		
		foreach( $skinIDs as $id )
		{
			$setData = $this->skinFunctions->fetchSkinData( $id );
			
			$key = $setData['set_key'];
			
			if ( $id > 0 )
			{
				$this->skinFunctions->rebuildPHPTemplates( $id );
				$messages[] = "<strong>PHP Templates Recached for $key</strong>";
				$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
				$this->skinFunctions->rebuildCSS( $id );
				$messages[] = "<strong>CSS Recached for $key</strong>";
				$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
				
				$this->skinFunctions->rebuildReplacementsCache( $id );
				$messages[] = "<strong>Replacements Recached for $key</strong>";
				$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
				$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			}
		
			/* Build and write XML files for replacements and CSS */
			$replacementsXML = $this->skinFunctions->generateReplacementsXML( $id, FALSE );
			
			@unlink( IPS_ROOT_PATH . 'setup/xml/skins/replacements_' . $key . '.xml', $replacementsXML );
			
			if ( $replacementsXML )
			{
				if ( ! @file_put_contents( IPS_ROOT_PATH . 'setup/xml/skins/replacements_' . $key . '.xml', $replacementsXML ) )
				{
					$errors[] = "Cannot write: " . IPS_ROOT_PATH . 'setup/xml/skins/replacements_' . $key . '.xml';
				}
				else
				{
					$messages[] = "Written: " . IPS_ROOT_PATH . 'setup/xml/skins/replacements_' . $key . '.xml';
				}
			}
			
			/* Export all CSS - Function loops through all skin sets */
			$this->skinFunctions->exportAllAppCSS( $id );
			$messages[] = "<strong>App CSS Written For $key</strong>";
			$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );

			/* Export all CSS - Function loops through all skin sets */
			$this->skinFunctions->exportAllAppTemplates( $id );
			$messages[] = "<strong>App XML Written For $key</strong>";
			$messages = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
			$errors   = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
			
			/* Got any errors? */
			if ( count( $errors ) )
			{
				$this->_print( 'ERROR: ' . implode( "\n *", $errors ) );
				//exit();
			}
		}
		
		/* Generate skin sets XML */
		$skinSetXML = $this->skinFunctions->generateMasterSkinSetXML( $skinIDs );
		
		@unlink( IPS_ROOT_PATH . 'setup/xml/skins/setsData.xml' );
		
		if ( ! @file_put_contents( IPS_ROOT_PATH . 'setup/xml/skins/setsData.xml', $skinSetXML ) )
		{
			$errors[] = "Cannot write:: " . IPS_ROOT_PATH . 'setup/xml/skins/setsData.xml';
		}
		else
		{
			$messages[] = "Written:  " . IPS_ROOT_PATH . 'setup/xml/skins/setsData.xml';
		}
		
		$this->_print( implode( "\n", $messages ) );
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "COMPLETE. Took " . $tkn . "m\n" );
	}
	
	/**
	 * Rebuild langs
	 */
	protected function _doLang()
	{
		/* Grab class */
		require_once( IPS_ROOT_PATH . 'applications/core/modules_admin/languages/manage_languages.php' );/*noLibHook*/
		$lang = new admin_core_languages_manage_languages( $this->registry );
		$lang->makeRegistryShortcuts( $this->registry );
		
		$start = time();
		
		foreach( ipsRegistry::$applications as $app => $app_data )
		{
			$this->_print("\nStarting App: " . $app_data['app_title'] );
			$_start = time();
			
			/* Stuff the post array */
			$_POST['apps'] = array( $app => $app );
			$msgs = $lang->importFromCacheFiles( FALSE );
			
			$_end = time();
			$_tkn = ( $_end - $_start) / 60;
			
			$this->_print( "\nDone. Took " . $_tkn . "m\n" . implode( "\n", $msgs ) );
		}
		
		$this->_print( "\nAll languages imported" );
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$this->_print("\nExporting XML for App: " . $app_data['app_title'] );
				
			$this->request['app_dir']	= $app_dir;
			
			$this->request['type']	= 'admin';
			$lang->languageExportToXML( 1, 1 );
			
			$this->request['type']	= 'public';
			$lang->languageExportToXML( 1, 1 );
		}
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "\nCOMPLETE. Took " . $tkn . "m\n" );
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