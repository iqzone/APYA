<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Main public executable wrapper.
 * Set-up and load module to run
 * Last Updated: $Date: 2011-03-31 11:17:44 +0100 (Thu, 31 Mar 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8229 $
 *
 */

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
		
		$this->_doLang();
		$this->_doSkins();
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
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections' ) );
		$o = $this->DB->execute();
																	   		  
		while( $row = $this->DB->fetch( $o ) )
		{
			$id      = intval( $row['set_id'] );
			$setData = $row;
			
			$key = $setData['set_key'];
			
			if ( $id )
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
			$this->_print("\nLanguage: Starting App: " . $app_data['app_title'] );
			$_start = time();
			
			/* Stuff the post array */
			$_POST['apps'] = array( $app => $app );
			$msgs = $lang->importFromCacheFiles( FALSE );
			
			$_end = time();
			$_tkn = ( $_end - $_start) / 60;
			
			$this->_print( "\nDone. Took " . $_tkn . "m\n" . implode( "\n", $msgs ) );
		}
		
		// We need to change the Nexus "client area" link for the IPS company forums
		if ( $this->settings['board_url'] == 'http://community.invisionpower.com' )
		{
			$this->DB->update( 'core_sys_lang_words', array( 'word_custom' => "Marketplace Purchases" ), "word_key='client_area'" );
		}
		
		$this->_print( "\nAll languages imported" );
		
		$this->DB->build( array( 'select' => '*', 'from' => 'core_sys_lang' ) );
		$e = $this->DB->execute();

		while( $r = $this->DB->fetch( $e ) )
		{
			$lang->cacheToDisk( $r['lang_id'] );
			$this->_print( "Recached lang - " . $r['lang_id'] );
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
		print nl2br( preg_replace( "#\n{1,}#", "\n", $message ) );
	}
}

exit();                 



?>