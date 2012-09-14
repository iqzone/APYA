<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Manage mobile app images
 * Last Updated: $Date: 2012-03-30 16:23:27 +0100 (Fri, 30 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		2012-04-20
 * @version		$Revision: 10526 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_mobileapp_images extends ipsCommand
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_mobileapp');
	
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=mobileapp&amp;section=images&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=mobileapp&section=images&';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_mobileapp' ) );
		
		/* Load up iOS class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/mobile/iosImages.php', 'classes_mobile_iosImages' );
		$this->images = new $classToLoad();
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_images' );
				$this->_overview();
			break;
			case 'exportXml':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_images' );
				$this->_export();
			break;
			case 'importXml':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_images' );
				$this->_import();
			break;
			case 'refresh':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'app_images' );
				$this->_refresh();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Refresh stuffs
	 */
	protected function _refresh()
	{
		$dirErrors = $this->images->checkDirectories();
		$imgErrors = $this->images->checkImages();
		
		if ( count( $dirErrors ) || $imgErrors['missing'] !== false OR $imgErrors['dimensions'] !== false OR $imgErrors['writeable'] !== false )
		{
			$this->images->setStyleLastUpdated( time() );
			
			$this->registry->output->global_message = $this->lang->words['mi_recache_done'];
		}
		else
		{
			$this->registry->output->global_error = $this->lang->words['mi_error_refresh_failed'];
		}
	
		$this->_overview();
	}
	
	/**
	 * Export as XML archive
	 */
	protected function _export()
	{
		$xml = $this->images->getXmlArchive();
		
		if ( ! $xml )
		{
			$this->registry->output->global_error = $this->lang->words['mi_error_no_export'];
			$this->_overview();
			return;
		}
		
		$this->registry->output->showDownload( $xml, 'mobileiOsImages.xml' );
	}
	
	/**
	 * Import an XML archive
	 */
	protected function _import()
	{
		try
		{
			$xml = $this->registry->adminFunctions->importXml();
		}
		catch ( Exception $e )
		{
			$this->registry->output->showError( $e->getMessage() );
		}
		
		$result = $this->images->importXmlArchive( $xml );
		
		if ( ! $result )
		{
			$this->registry->output->global_error = $this->lang->words['mi_import_failed'];
		}
		
		$this->_overview();
	}

	/**
	 * List the import form
	 *
	 * @return	@e void
	 */
	protected function _overview()
	{
		$dirErrors     = $this->images->checkDirectories();
		$imgErrors     = $this->images->checkImages();
		$currentImages = $this->images->getImageDirContents();
		$defaultImages = $this->images->getDefaultImageDirContents();
		$canImport     = true;
		
		if ( $dirErrors !== false )
		{
			$canImport = false;
			
			foreach( $dirErrors as $error )
			{
				$this->registry->output->global_error .= sprintf( $this->lang->words['mi_error_' . $error['key'] ], $error['extra'] ) . '<br />';
			}
		}
		
		if ( $imgErrors['writeable'] !== false )
		{
			$canImport = false;
			
			$this->registry->output->global_error .= $this->lang->words['mi_error_images_writeable'] . '<br />';
		}
		
		if ( $imgErrors['missing'] !== false )
		{
			foreach( $imgErrors['missing'] as $img )
			{
				$this->registry->output->global_error .= sprintf( $this->lang->words['mi_error_missing_img'], '<a href="' . $this->images->getDefaultImageUrl() . $img . '" target="_blank">' . $img . '</a>' ) . '<br />';
			}
		}
		
		$this->registry->output->html .= $this->html->overview( $currentImages, $defaultImages, $this->images->getImageDir(), $imgErrors, $canImport );
	}
}