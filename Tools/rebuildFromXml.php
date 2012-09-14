#!/usr/local/bin/php
<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Rebuilds stuff from XML
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
		
		$this->_print( "--------------------------------------------\nWelcome to the IP.Board Shell Rebuild Stuff From XML Tool\n--------------------------------------------\n" );
		$this->_print( "What do you wish to do? [l=Rebuild Lang, s=Rebuild Skins, b=Both]\nEnter: " );

		$option = $this->_fetchOption();

		switch( $option )
		{
			case 'l':
				$this->_doLang();
			break;
			case 's':
				$this->_doSkins();
			break;
			case 'b':
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
		$output   = array();
		$errors   = array();
		
		/* Grab class */
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );/*noLibHook*/
		
		$skinFunctions = new skinImportExport( $this->registry );
		
		
		/* Grab skin data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections' ) );

		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			/* Bit of jiggery pokery... */
			if ( $row['set_key'] == 'default' )
			{
				$row['set_key'] = 'root';
				$row['set_id']  = 0;
			}

			$skinSets[ $row['set_key'] ] = $row;
		}

		/* Templates */
		foreach( ipsRegistry::$applications as $appDir => $appData )
		{
			foreach( $skinSets as $skinKey => $skinData )
			{
				$_PATH    = IPSLib::getAppDir( $appDir ) .  '/xml/';

				$output[] = "Upgrading {$skinData['set_name']} for $appDir templates...";

				if ( is_file( $_PATH . $appDir . '_' . $skinKey . '_templates.xml' ) )
				{
					//-----------------------------------------
					// Install
					//-----------------------------------------

					$return = $skinFunctions->importTemplateAppXML( $appDir, $skinKey, $skinData['set_id'], TRUE );

					$output[] = intval( $return['insertCount'] ) . " added, " . intval( $return['updateCount'] ) . " templates updated";
				}

				if ( is_file( $_PATH . $appDir . '_' . $skinKey . '_css.xml' ) )
				{
					//-----------------------------------------
					// Install
					//-----------------------------------------

					$return = $skinFunctions->importCSSAppXML( $appDir, $skinKey, $skinData['set_id'] );

					$output[] = intval( $return['insertCount'] ) . " {$skinData['set_name']} CSS files inserted";
				}
			}
 		}
	
		/* Replacements */
		foreach( $skinSets as $skinKey => $skinData )
		{
			if ( is_file( IPS_ROOT_PATH . 'setup/xml/skins/replacements_' . $skinKey . '.xml' ) )
			{
				$skinFunctions->importReplacementsXMLArchive( file_get_contents( IPS_ROOT_PATH . 'setup/xml/skins/replacements_' . $skinKey . '.xml' ), $skinKey );
				
				$output[] = $skinKey . " replacements rebuilt";
			}
		}
	
		/* reset ID */
		$skinSets['root']['set_id'] = 1;
		
		/* Recache */
		foreach( $skinSets as $skinKey => $skinData )
		{
			$skinFunctions->rebuildPHPTemplates( $skinData['set_id'] );

			if ( $skinFunctions->fetchErrorMessages() !== FALSE )
			{
				$output[] = implode( "<br />", $skinFunctions->fetchErrorMessages() );
			}

			$skinFunctions->rebuildCSS( $skinData['set_id'] );

			if ( $skinFunctions->fetchErrorMessages() !== FALSE )
			{
				$output[] = implode( "<br />", $skinFunctions->fetchErrorMessages() );
			}

			$skinFunctions->rebuildReplacementsCache( $skinData['set_id'] );

			if ( $skinFunctions->fetchErrorMessages() !== FALSE )
			{
				$output[] = implode( "<br />", $skinFunctions->fetchErrorMessages() );
			}
			
			$output[] = $skinKey . " recached";
		}
		
		/* recache other */
		$skinFunctions->rebuildSkinSetsCache();
		
		$this->_print( implode( "\n", $output ) );
		
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
			$output[] = "Upgrading ADMIN languages...";
			$_PATH    = IPSLib::getAppDir( $app ) .  '/xml/';

			/* Loop through the xml directory and look for lang packs */
			try
			{
				foreach( new DirectoryIterator( $_PATH ) as $f )
				{
					if( preg_match( "#(admin|public)_(.+?)_language_pack.xml#", $f->getFileName() ) )
					{
						//-----------------------------------------
						// Import and cache
						//-----------------------------------------
						
						$output[] = "Importing " . $f->getFileName();
						
						$this->request['file_location'] = $_PATH . $f->getFileName();
						$lang->imprtFromXML( true, true, true, $app );
					}
				}
			} catch ( Exception $e ) {}
		}
		
		$this->_print( implode( "\n", $output ) );
		$this->_print( "\nAll languages imported and recached" );
		
		$end = time();
		$tkn = ( $end - $start) / 60;
		
		$this->_print( "\COMPLETE. Took " . $tkn . "m\n" );
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