<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Configure default notification options
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_members_members_notifications extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */	
	protected $html;
	
	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * Notifications library
	 *
	 * @var		object
	 */
	protected $notifyLibrary;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_notifications' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=notifications';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=notifications';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ) );
		
		//-----------------------------------------
		// Permissions config
		//-----------------------------------------
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'configure_notifications' );
		
		//-----------------------------------------
		// Notifications library
		//-----------------------------------------
		
		$classToLoad			= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$this->notifyLibrary	= new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{				
			case 'save':
				$this->saveDefaults();
			break;

			default:
			case 'show':
				$this->showDefaults();
			break;			
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Show form to configure notification default options
	 *
	 * @return	@e void
	 */
	public function showDefaults()
	{
		$_configOptions	= $this->notifyLibrary->getNotificationData();
		$_notifyConfig	= $this->notifyLibrary->getDefaultNotificationConfig();

		$this->registry->output->html .= $this->html->showConfigurationOptions( $_configOptions, $_notifyConfig );
	}

	/**
	 * Save default notification configuration
	 *
	 * @return	@e void
	 */
	public function saveDefaults()
	{
		$_configOptions		= $this->notifyLibrary->getNotificationData();
		$_notifyConfig		= $this->notifyLibrary->getDefaultNotificationConfig();

		foreach( $_configOptions as $option )
		{
			$_notifyConfig[ $option['key'] ]						= array();
			$_notifyConfig[ $option['key'] ]['selected']			= ( is_array($this->request['default_' . $option['key'] ]) AND count($this->request['default_' . $option['key'] ]) ) ? $this->request['default_' . $option['key'] ] : array();
			$_notifyConfig[ $option['key'] ]['disabled']			= ( is_array($this->request['disabled_' . $option['key'] ]) AND count($this->request['disabled_' . $option['key'] ]) ) ? $this->request['disabled_' . $option['key'] ] : array();
			$_notifyConfig[ $option['key'] ]['disable_override']	= intval($this->request['disable_override_' . $option['key'] ]);
		}

		$this->notifyLibrary->saveNotificationConfig( $_notifyConfig );
		
		$this->registry->output->global_message = $this->lang->words['notificationconfig_saved'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
}