<?php

/**
 * @file		settings.php 	Control calendar settings
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		March 24 2011
 * $LastChangedDate: 2012-01-06 06:20:45 -0500 (Fri, 06 Jan 2012) $
 * @version		vVERSION_NUMBER
 * $Revision: 10095 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_calendar_settings_settings
 * @brief		Control calendar settings
 *
 */
class admin_calendar_settings_settings extends ipsCommand 
{
	/**
	 * Shortcut for url
	 *
	 * @access	protected
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	protected
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= 'module=settings&amp;section=settings';
		$this->form_code_js	= 'module=settings&section=settings';
		
		//-------------------------------
		// Grab, init and load settings
		//-------------------------------
		
		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$settings		= new $classToLoad( $this->registry );
		$settings->makeRegistryShortcuts( $this->registry );
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );		
		$settings->form_code	= $settings->html->form_code    = 'module=settings&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'module=settings&section=settings';

		$this->request['conf_title_keyword'] = 'calendar';
		$settings->return_after_save         = $this->settings['base_url'] . $this->form_code;
		$settings->_viewSettings();
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();		
	}		
}
