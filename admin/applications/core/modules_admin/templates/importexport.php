<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Import and export skin sets
 * Last Updated: $Date: 2012-05-11 06:56:52 -0400 (Fri, 11 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Who knows...
 * @version		$Revision: 10725 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_importexport extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @var		object
	 */
	protected $skinFunctions;
	
	/**
	 * Recursive depth guide
	 *
	 * @var		array
	 */
	protected $_depthGuide = array();
	
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**#@+
	 * URL bits
	 *
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=importexport';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=importexport';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinImportExport.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinImportExport( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_showForm();
			break;
			case 'exportSet':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_export' );
				$this->_exportSet();
			break;
			case 'exportImages':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_export' );
				$this->_exportImages();
			break;
			case 'exportReplacements':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_export' );
				$this->_exportReplacements();
			break;
			
			case 'importSet':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_importSet();
			break;
			case 'importImages':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_importImages();
			break;
			case 'importReplacements':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_importReplacements();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Import an XMLarchive skin set
	 *
	 * @return	string		HTML to show
	 */
	protected function _importReplacements()
	{
		$importLocation = trim( $this->request['importLocation'] );
		$setID          = intval( $this->request['setID'] );
		
		//-----------------------------------------
		// Attempt to get contents
		//-----------------------------------------
		
		try
		{
			$content = $this->registry->adminFunctions->importXml( $importLocation );
		}
		catch ( Exception $e )
		{
			$this->registry->output->showError( $e->getMessage() );
		}
		
		if ( $content )
		{
			$added = $this->skinFunctions->importReplacementsXMLArchive( $content, $setID );
			
			if ( $added !== FALSE )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['ie_replace_added'], $added );
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=templates&section=importexport&do=overview' );
			}
			else
			{
				$this->registry->output->global_error = $this->lang->words['ie_importfail'] . implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
				return $this->_showForm();
			}
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['ie_importfail_no'];
			return $this->_showForm();
		}
	}
	
	/**
	 * Import an XMLarchive skin set
	 *
	 * @return	string		HTML to show
	 */
	protected function _importImages()
	{
		$importName     = trim( $this->request['importName'] );
		$importLocation = trim( $this->request['importLocation'] );
		$setID          = intval( $this->request['setID'] );

		//-----------------------------------------
		// Fix up import name
		//-----------------------------------------

		if( ! $importName )
		{
			$importName = !empty( $_FILES['FILE_UPLOAD']['name'] ) ? $_FILES['FILE_UPLOAD']['name'] : $this->request['importLocation'];
		}
		
		$importName = preg_replace( "/[^a-zA-Z0-9_]/", "_", str_ireplace( array( '.xml', '.gz', 'images-' ), '', $importName ) );

		//-----------------------------------------
		// Attempt to get contents
		//-----------------------------------------
		
		try
		{
			$content = $this->registry->adminFunctions->importXml( $importLocation );
		}
		catch ( Exception $e )
		{
			$this->registry->output->showError( $e->getMessage() );
		}
		
		if ( $content )
		{
			$added = $this->skinFunctions->importImagesXMLArchive( $content, $importName, $setID );
			
			if ( $added !== FALSE )
			{
				$this->registry->output->global_message = sprintf( $this->lang->words['ie_images_added'], $added );
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=templates&section=importexport&do=overview' );
			}
			else
			{
				$this->registry->output->global_error = $this->lang->words['ie_importfail'] . implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
				return $this->_showForm();
			}
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['ie_importfail_no'];
			return $this->_showForm();
		}
	}
	
	/**
	 * Import an XMLarchive skin set
	 *
	 * @return	string		HTML to show
	 */
	protected function _importSet()
	{
		$importName     = trim( $this->request['importName'] );
		$importUpgrade  = intval( $this->request['importUpgrade'] ) ? TRUE : FALSE;
		$importLocation = trim( $this->request['importLocation'] );
		$imageDir       = trim( $this->request['importImgDirs'] );
		$checkVersion   = trim( $this->request['noVersionCheck'] ) ? FALSE : TRUE;
		
		//-----------------------------------------
		// Attempt to get contents
		//-----------------------------------------
		
		if ( $checkVersion )
		{
			try
			{
				$content = $this->registry->adminFunctions->importXml( $importLocation );
			}
			catch ( Exception $e )
			{
				$this->registry->output->showError( $e->getMessage() );
			}
		}
		else
		{	
			$_c      = $this->DB->buildAndFetch( array( 'select' => 'cs_value', 'from' => 'cache_store', 'where' => 'cs_key=\'_importSkinContent_\'' ) );
			$content = $_c['cs_value'];
		}
		
		if ( $content )
		{
			/* Checking versions? */
			if ( $checkVersion )
			{
				$this->DB->delete( 'cache_store', 'cs_key=\'_importSkinContent_\'' );
				
				preg_match( '#ipbLongVersion=\"(\d+?)\"\s+?ipbHumanVersion=\"([\d\.\-\_]+?)\"#i', $content, $matches );
				
				if ( $matches[1] and $matches[2] )
				{
					$_version = IPSLib::fetchVersionNumber();
					
					/* Long version is in format of 31001 (Major Minor X X X) we'll just check majorminor */
					$_importMajor  = substr( $matches[1]      , 0, 2 );
					$_currentMajor = substr( $_version['long'], 0, 2 );
					
					if ( $_importMajor < $_currentMajor )
					{
						/* Store it */
						$this->DB->insert( 'cache_store', array( 'cs_key' => '_importSkinContent_', 'cs_value' => $content ) );
						
						/* Generate continue link */
						$link = $this->settings['base_url'] . '&amp;noVersionCheck=1&amp;' . http_build_query( $this->request );
						
						/* Show form */
						$this->registry->output->global_error = sprintf( $this->lang->words['ie_importfail_old_version'], $matches[2], $_version['human'], $link );
						return $this->_showForm();
					}
				}
			}
		
			$added   = $this->skinFunctions->importSetXMLArchive( $content, 0, $imageDir, $importName, $importUpgrade );
			$message = $added['upgrade'] ? $this->lang->words['ie_set_upgraded'] : $this->lang->words['ie_set_imported'];
			
			$this->registry->output->global_message = sprintf( $message, $added['templates'], $added['replacements'], $added['css'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=templates&section=importexport&do=overview' );
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['ie_importfail_no'];
			return $this->_showForm();
		}
	}
	
	/**
	 * Export replacements to an XMLArchive
	 *
	 * @return	@e void
	 */
	protected function _exportReplacements()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID   = intval( $this->request['setID'] );
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Er.. that's it...
		//-----------------------------------------
		
		$this->registry->output->showDownload( $this->skinFunctions->generateReplacementsXML( $setID ), 'replacements-' . IPSText::makeSeoTitle( $setData['set_name'] ) . '.xml' );
	}
	
	/**
	 * Export an image set to an XMLArchive
	 *
	 * @return	@e void
	 */
	protected function _exportImages()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if ( is_string( $this->request['setID'] ) )
		{
			$imgDir = $this->request['setID'];
		}
		else
		{
			$setID    = intval( $this->request['setID'] );
			$setData  = $this->skinFunctions->fetchSkinData( $setID );
			$imgDir   = $setData['set_image_dir'];
		}
		
		//-----------------------------------------
		// Er.. that's it...
		//-----------------------------------------
		
		$xml = $this->skinFunctions->generateImagesXMLArchive( $imgDir );
		
		if ( count( $this->skinFunctions->fetchErrorMessages( TRUE ) ) )
		{
			$this->registry->output->global_error = implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
			$this->_showForm();
			return;
		}
		
		$this->registry->output->showDownload( $xml, 'images-' . IPSText::makeSeoTitle( $imgDir ) . '.xml' );
	}

	/**
	 * Export the entire set to an XMLArchive
	 *
	 * @return	@e void
	 */
	protected function _exportSet()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID   = intval( $this->request['setID'] );
		if ( !$setID )
		{
			return $this->_showForm();
		}
		
		$setOnly = ( $this->request['exportSetOptions'] == 'all' ) ? FALSE : TRUE;
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		$apps    = array();
		
		/* Figure out which apps to export */
		if ( is_array( $_POST['exportApps'] ) AND count( $_POST['exportApps'] ) )
		{
			foreach( $_POST['exportApps'] as $k => $v )
			{
				if ( $k == 'core' AND $v )
				{
					$apps[] = 'core';
					$apps[] = 'forums';
					$apps[] = 'members';
				}
				else if ( $v )
				{
					$apps[] = $k;
				}
			}
		}
		
		//-----------------------------------------
		// Er.. that's it...
		//-----------------------------------------
		
		$xml = $this->skinFunctions->generateSetXMLArchive( $setID, $setOnly, $apps );
		
		if ( count( $this->skinFunctions->fetchErrorMessages( TRUE ) ) )
		{
			$this->registry->output->global_error = implode( "<br />", $this->skinFunctions->fetchErrorMessages() );
			$this->_showForm();
			return;
		}
		
		$this->registry->output->showDownload( $xml, IPSText::makeSeoTitle( $setData['set_name'] ) . '.xml' );
	}
	
	/**
	 * Show the main form
	 *
	 * @return	@e void
	 */
	protected function _showForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sets        = $this->_fetchSetsDropDown( intval( $this->request['skinID'] ) );
		$imageDirs   = $this->skinFunctions->fetchImageDirectories();
		$form        = array();
		$warnings	 = array();
		$_imageDirs  = array();
		$_skinSetMap = array();
		
		//-----------------------------------------
		// Map skins to image dirs
		//-----------------------------------------
		
		foreach( $this->registry->output->allSkins as $_id => $data )
		{
			$_skinSetMap[ $data['set_image_dir'] ][] = $_id;
		}
		
		//-----------------------------------------
		// Build image dir option list
		//-----------------------------------------
		
		foreach( $imageDirs as $dir )
		{
			$name = '';
			
			/* Used in a skin set? */
			if ( isset($_skinSetMap[$dir]) )
			{
				$_count = count( $_skinSetMap[ $dir ] );
				$_name  = $this->registry->output->allSkins[ array_pop( $_skinSetMap[ $dir ] ) ];
				
				if ( $_count > 1 )
				{
					$name = $dir . sprintf( $this->lang->words['used_in_plus_others'], $_name['set_name'], $_count-1 );
				}
				else
				{
					$name = $dir . sprintf( $this->lang->words['used_in_no_others'], $_name['set_name'] );
				}
			}
			
			$_imageDirs[] = array( $dir, ( $name ) ? $name : $dir );
		}
		
		//-----------------------------------------
		// Warnings?
		//-----------------------------------------
		
		# Image directory writeable?
		if ( ! is_writable( rtrim( $this->skinFunctions->fetchImageDirectoryPath(''), '/' ) ) )
		{
			$warnings['importImgDir'] = 1;
		}
		
		# Main cache path writeable?
		if ( ! is_writable( IPS_CACHE_PATH . 'cache/skin_cache' ) )
		{
			$warnings['importSkinCacheDir'] = 1;
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['uploadField']      = $this->registry->output->formUpload();
		$form['importName']       = $this->registry->output->formInput( 'importName'    , $_POST['importName'] );
		$form['importUpgrade']    = $this->registry->output->formCheckbox( 'importUpgrade', $_POST['importUpgrade'] );
		$form['importLocation']   = $this->registry->output->formInput( 'importLocation', $_POST['importLocation'] );
		$form['exportImgDirs']    = $this->registry->output->formDropdown( 'setID', $_imageDirs, $_POST['exportImgDirs'] );
	
		array_unshift( $_imageDirs, array( '0', ' ' . $this->lang->words['ie_none'] . ' ' ) );
		
		$form['importImgDirs']    = $this->registry->output->formDropdown( 'importImgDirs', $_imageDirs, $_POST['importImgDirs'] );
		
		$form['exportSetOptions'] = $this->registry->output->formDropdown("exportSetOptions", array( 0 => array( 'current'  , $this->lang->words['ie_ex_skin'] ),
																					                 1 => array( 'all'      , $this->lang->words['ie_ex_skin_p'] ) ) );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->importexport_form( $sets, $form, $warnings );
	}
	
	/**
	 * This code is duplicated from output/formats/html
	 * I did debate moving it into a function elsewhere
	 * But it's only used here also, so...
	 *
	 * @param	int			Set ID to be 'selected'
	 * @param	int			Parent id
	 * @param	int			Iteration
	 * @return	string		HTML
	 */
	protected function _fetchSetsDropDown( $skinID=NULL, $parent=0, $iteration=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$output       = "";
		$depthMarkers = "";
		
		if( $iteration )
		{
			for( $i=0; $i<$iteration; $i++ )
			{
				$depthMarkers .= '--';
			}
		}
		
		//-----------------------------------------
		// Go get 'em
		//-----------------------------------------
		
		foreach( $this->registry->output->allSkins as $id => $data )
		{
			/* Allowed to use? */
			/*if ( $data['_youCanUse'] !== TRUE )
			{
				continue;
			}*/
		
			/* Root skins? */
			if ( count( $data['_parentTree'] ) AND $iteration == 0 )
			{
				continue;
			}
			else if( $iteration > 0 AND (!count( $data['_parentTree'] ) OR $data['_parentTree'][0] != $parent) )
			{
				continue;
			}
			
			$_selected = ( $skinID != NULL AND $skinID == $data['set_id'] ) ? 'selected="selected"' : '';
			
			/* Ok to add... */
			$output .= "\n<option id='skinSetDD_" . $data['set_id'] . "' " . $_selected . " value=\"". $data['set_id'] . "\">". $depthMarkers . $data['set_name'] . "</option>";
			
			if ( is_array( $data['_childTree'] ) AND count( $data['_childTree'] ) )
			{
				$output .= $this->_fetchSetsDropDown( $skinID, $data['set_id'], $iteration + 1 );
			}
		}
		
		return $output;
	}
}