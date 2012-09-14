<?php
/**
 * @file		logsSplash.php 	Logs splash screen
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		8th Feb 2011
 * $LastChangedDate: 2011-09-12 20:59:05 -0400 (Mon, 12 Sep 2011) $
 * @version		v3.3.3
 * $Revision: 9483 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_core_logs_logsSplash
 * @brief		Logs splash screen
 *
 */
class admin_core_logs_logsSplash extends ipsCommand 
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	protected $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Template */
		$this->html	= $this->registry->output->loadTemplate( 'cp_skin_adminlogs' );
		
		/* Load Language */		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );		
		
		/* URL Bits */
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=splash';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=splash';
		
		/* Get the splash screen */
		$this->registry->output->html .= $this->html->logSplashScreen();
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
}