<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Externally edit templates and css
 * Last Updated: $Date: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Tuesday 17th August 2004
 * @version		$Revision: 8644 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_templates_external extends ipsCommand
{
	/**
	 * HTML Skin object
	 *
	 * @var		object
	 */
	protected $html;
	
	/**
	 * Skin Functions Class
	 *
	 * @var		object
	 */
	protected $skinFunctions;
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );
		
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=external';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=external';
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinCaching( $registry );		
	
		//-----------------------------------------
		// What to do?
		//-----------------------------------------

		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_external' );
		
		switch( $this->request['do'] )
		{
			default:
			case 'overview':
				$this->_overview();
				break;
			case 'save':
				$this->_save();
				break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Saves um.. it
	 *
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	protected function _save()
	{
		/* Init */
		$webdav_on = intval( $this->request['webdav_on'] );
		
		/* Save */
		IPSLib::updateSettings( array( 'webdav_on' => $webdav_on ) );
		
		/* Relist */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}" );
	}
	
	/**
	 * Shows the current state of the external edit system 
	 *
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	protected function _overview()
	{
		/* Init */
		$form = array();
		
		$form['webdav_on']= $this->registry->getClass('output')->formYesNo( 'webdav_on', $this->settings['webdav_on'] );
		
		$this->registry->output->html .= $this->html->externalEditOverview( $form );
	}
	
}