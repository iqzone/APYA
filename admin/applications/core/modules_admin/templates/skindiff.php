<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Skin diff report tools
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Who knows...
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_skindiff extends ipsCommand
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
	
	/**
	 * Amount of items per go
	 *
	 * @var		int
	 */
	protected $_bitsPerRound = 10;
	
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=skindiff';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=skindiff';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinDifferences( $registry );
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'skindiff_reports' );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'skinDiffStart':
				$this->_start();
			break;
			case 'viewReport':
				$this->_viewReport();
			break;
			case 'exportReport':
				$this->_exportReport();
			break;
			case 'skin_diff_view_diff':
				$this->skin_differences_view_diff();
			break;
			case 'removeReport':
				$this->_removeReport();
			break;
			case 'multiManage':
				$this->_multiManage();
			break;
			default:
				$this->_list();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Action a report drop down
	 *
	 * @return	@e void
	 */
	protected function _multiManage()
	{
		$merge_id    = $this->request['sessionID'] = intval( $this->request['merge_id'] );
		$mergeOption = trim( $this->request['mergeOption'] );
		$items       = is_array( $_POST['changeIds'] ) ? IPSLib::cleanIntArray( array_keys( $_POST['changeIds'] ) ) : array();
		
		/* Fetch session and check */
		$session = $this->skinFunctions->fetchSession( $merge_id );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		/* Got items? */
		if ( count( $items ) )
		{
			/* Process. I love pointless comments */
			switch( $mergeOption )
			{
				case 'resolve_custom':
					$this->skinFunctions->resolveConflict( $items, 'custom' );
				break;
				case 'resolve_new':
					$this->skinFunctions->resolveConflict( $items, 'new' );
				break;
				case 'commit':
					$this->skinFunctions->commit( $items );
				break;
				case 'revert':
					$this->skinFunctions->revert( $items );
				break;
			}
		}
		
		/* Throw it out */
		$this->registry->output->global_message = $this->lang->words['skindiff_okmsg'];
		return $this->_viewReport();
	}
	
	/**
	 * Remove a report
	 *
	 * @return	@e void
	 */
	protected function _removeReport()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$sessionID = intval( $this->request['sessionID'] );
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $sessionID );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		//-----------------------------------------
		// Remove...
		//-----------------------------------------
		
		$this->skinFunctions->removeSession( $sessionID );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['sd_removed'];
		$this->_list();
	}
	
	/**
	 * Export a report
	 *
	 * @return	@e void
	 */
	protected function _exportReport()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$sessionID = intval( $this->request['sessionID'] );
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $sessionID );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$data = $this->skinFunctions->fetchReport( $sessionID );
		
		$content = $this->html->skindiff_export( $session, $data['data'], $data['counts']['missing'], $data['counts']['changed'] );

		$this->registry->output->showDownload( $content, 'diff-' . IPSText::makeSeoTitle( $this->skinFunctions->fetchReportTitle( $session ) ) . '.html', "unknown/unknown", 0 );
	}
	
	/**
	 * View a diff report
	 *
	 * @return	@e void
	 */
	protected function _viewReport()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$sessionID  = intval( $this->request['sessionID'] );
		$content    = '';
		$missing    = 0;
		$changed    = 0;
		$last_group = '';
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $sessionID, true );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$data = $this->skinFunctions->fetchReport( $sessionID );
		
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'].'module=templates&amp;section=skindiff&amp;do=overview', $this->lang->words['sk_skindiffreports'] );
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'].'module=templates&amp;section=skindiff&amp;do=viewReport&amp;sessionID=' . $session['merge_id'], $this->skinFunctions->fetchReportTitle( $session ) );
		
		$this->registry->output->html .= $this->html->skindiff_reportOverview( $session, $data['data'], $data['counts']['missing'], $data['counts']['changed'] );
	}

	/**
	 * Compare skin differences (XML files)
	 *
	 * @since	2.1.0.2005-07-22
	 * @return	@e void
	 */
	public function _start()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$content = "";
		$setID   = intval( $this->request['setID'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $setID )
		{
			$this->registry->output->showError( $this->lang->words['sd_noid'] );
		}
		
		//-----------------------------------------
		// Get skin set...
		//-----------------------------------------
		
		$skin_set = $this->skinFunctions->fetchSkinData( $setID );
		
		if ( ! $skin_set['set_id'] )
		{
			$this->registry->output->showError( $this->lang->words['sd_noid'] );
		}
		
		/* Create session */
		try
		{
			$sessionId = $this->skinFunctions->createSession( $setID );
		}
		catch( Exception $e )
		{
			$this->registry->output->showError( $this->lang->words['sd_exception'] );
		}
		
		/* Get count */
		$session = $this->skinFunctions->fetchSession( $sessionId );
		
		$items   = intval( $session['merge_templates_togo'] ) + intval( $session['merge_css_togo'] );
		
		$this->registry->output->html .= $this->html->skindiff_ajaxScreen( $sessionId, $items, $this->_bitsPerRound );
	}
	
	
	/**
	 * List all current difference 'sets'
	 *
	 * @return	@e void
	 */
	protected function _list()
	{
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		$this->registry->output->html = $this->html->skindiff_overview( $this->skinFunctions->fetchSessions(), $this->skinFunctions->getTiersFunction()->fetchAllsItemDropDown(), $this->skinFunctions->canUseMergeSystem() );
	}
	
}