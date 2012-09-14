<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * User control panel resolver
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Main loader class
*/
class public_core_usercp_manualResolver extends ipsCommand
{
	/**
	 * Page Title set by sub classes
	 *
	 * @var		string
	 */
	protected $_pageTitle;

	/**
	* Navigation array set by sub classes
	 *
	 * @var		array
	 */
	protected $_nav = array();

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_thisNav = array();

		//-----------------------------------------
		// Load language
		//-----------------------------------------

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_usercp' ) );

		//-----------------------------------------
		// Logged in?
		//-----------------------------------------

		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'].'&app=core&module=global&section=login&do=form' );

			exit();
		}

		//-----------------------------------------
		// Make sure they're clean
		//-----------------------------------------

		$this->request['tab'] = IPSText::alphanumericalClean( $this->request['tab'] );
		$this->request['area'] = IPSText::alphanumericalClean( $this->request['area'] );

		//-----------------------------------------
		// Set up some basics...
		//-----------------------------------------

		$_TAB  = ( $this->request['tab'] )  ? $this->request['tab']  : 'core';
		$_AREA = ( $this->request['area'] ) ? $this->request['area'] : 'settings';
		$_DO   = ( $this->request['do'] )   ? $this->request['do']   : 'show';
		$_FUNC = ( $_DO == 'show' ) ? 'showForm' : ( $_DO == 'save' ? 'saveForm' : $_DO );
		$tabs  = array();
		$errors = array();

		//-----------------------------------------
		// Got a plug in?
		//-----------------------------------------
		
		IPSLib::loadInterface( 'interface_usercp.php' );
		
		$EXT_DIR  = IPSLib::getAppDir( $_TAB ) . '/extensions';

		if ( ! is_file($EXT_DIR . '/usercpForms.php') )
		{
			$this->registry->getClass('output')->showError( 'usercp_bad_tab', 10147 );
			exit();
		}

		//-----------------------------------------
		// Cycle through applications and load
		// usercpForm extensions
		//-----------------------------------------
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			$ext_dir  = IPSLib::getAppDir( $app_dir ) . '/extensions';

			// Make sure the extension exists
			if ( !is_file( $ext_dir . '/usercpForms.php' ) )
			{
				continue;
			}
			
			$__class        = IPSLib::loadLibrary( $ext_dir . '/usercpForms.php', 'usercpForms_' . $app_dir, $app_dir );
			$_usercp_module = new $__class();
			
			/* Block based on version to prevent old files showing up/causing an error */
			if( !$_usercp_module->version OR $_usercp_module->version < 32 )
			{
				continue;
			}

			$_usercp_module->makeRegistryShortcuts( $this->registry );

			if ( is_callable( array( $_usercp_module, 'init' ) ) )
			{
				$_usercp_module->init();

				/* Set default area? */
				if (  ( $_TAB == $app_dir ) AND ! isset( $_REQUEST['area'] ) )
				{
					if ( isset( $_usercp_module->defaultAreaCode ) )
					{
						$this->request['area'] = $_AREA = $_usercp_module->defaultAreaCode;
					}
				}
			}

			if ( is_callable( array( $_usercp_module, 'getLinks' ) ) )
			{
				$tabs[ $app_dir ]['_menu'] = $_usercp_module->getLinks();
				
				/* Got any links? */
				if ( !is_array($tabs[ $app_dir ]['_menu']) || !count($tabs[ $app_dir ]['_menu']) )
				{
					unset( $tabs[ $app_dir ] );
					continue;
				}
				
				/* Get title */
				$tabs[ $app_dir ]['_name'] = $_usercp_module->tab_name ? $_usercp_module->tab_name : IPSLib::getAppTitle( $app_dir );
				
				/* Add in 'last' element */
				$tabs[ $app_dir ]['_menu'][ count( $tabs[ $app_dir ]['_menu'] ) - 1 ]['last'] = 1;
				
				/* This nav? */
				if ( ! count( $_thisNav ) AND $app_dir == $_TAB )
				{
					foreach( $tabs[ $app_dir ]['_menu'] as $_navData )
					{
						if ( $_navData['url'] == 'area=' . $_AREA )
						{
							$_thisNav = array( 'app=core&amp;module=usercp&amp;tab=' . $_TAB . '&amp;area=' . $_AREA, $_navData['title'] );
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Set up basic navigation
		//-----------------------------------------

		$this->_nav[] = array( $this->lang->words['t_title'], '&amp;app=core&amp;module=usercp' );
		$this->_nav[] = array( $this->lang->words['tab__' . $_TAB ] ? $this->lang->words['tab__' . $_TAB ] : IPSLib::getAppTitle( $_TAB ) , '&amp;app=core&amp;module=usercp&amp;tab=' . $_TAB );

		if ( isset( $_thisNav[0] ) )
		{
			$this->_nav[] = array( $_thisNav[1], $_thisNav[0] );
		}

		//-----------------------------------------
		// Begin initilization routine for extension
		//-----------------------------------------
		$classToLoad   = IPSLib::loadLibrary( $EXT_DIR . '/usercpForms.php', 'usercpForms_' . $_TAB, $_TAB );
		$usercp_module = new $classToLoad();
		$usercp_module->makeRegistryShortcuts( $this->registry );
		$usercp_module->init();

		if ( ( $_DO == 'saveForm' || $_DO == 'showForm' ) AND ! is_callable( array( $usercp_module, $_FUNC ) ) )
		{
			$this->registry->getClass('output')->showError( 'usercp_bad_tab', 10148, true );
			exit();
		}

		//-----------------------------------------
		// Run it...
		//-----------------------------------------

		if ( $_FUNC == 'showForm' )
		{
			//-----------------------------------------
			// Facebook email
			//-----------------------------------------
 
			$html = $usercp_module->showForm( $_AREA );
		}
		else if ( $_FUNC == 'saveForm' )
		{
			//-----------------------------------------
			// Check secure key...
			//-----------------------------------------

			if ( $this->request['secure_hash'] != $this->member->form_hash )
			{
				$html = $usercp_module->showForm( $_AREA );
				$errors[] = $this->lang->words['securehash_not_secure'];
			}
			else
			{
				$errors = $usercp_module->saveForm( $_AREA );

				$do = ( $usercp_module->do_url ) ? $usercp_module->do_url : 'show';

				if ( is_array( $errors ) AND count( $errors ) )
				{
					$html = $usercp_module->showForm( $_AREA, $errors );
				}
				else if ( $usercp_module->ok_message )
				{
					$this->registry->getClass('output')->redirectScreen( $usercp_module->ok_message, $this->settings['base_url'] . 'app=' . IPS_APP_COMPONENT . '&module=usercp&tab=' . $_TAB . '&area=' . $_AREA . '&do='.$do.'&saved=1', 1 );
				}
				else
				{
					$this->registry->getClass('output')->silentRedirect( $this->settings['base_url_with_app'] . 'module=usercp&tab=' . $_TAB . '&area=' . $_AREA . '&do='.$do.'&saved=1'.'&_r='.time() );
				}
			}
		}
		else
		{
			if ( ! is_callable( array( $usercp_module, 'runCustomEvent' ) ) )
			{
				$html = $usercp_module->showForm( $_AREA );
				$errors[] = $this->lang->words['called_invalid_function'];
			}
			else
			{
				$html = $usercp_module->runCustomEvent( $_AREA );
			}
		}

		//-----------------------------------------
		// If you've run a custom event, may need to
		// reset the "area" to highlight the menu correctly
		//-----------------------------------------

		if ( is_callable( array( $usercp_module, 'resetArea' ) ) )
		{
			$_AREA = $usercp_module->resetArea( $_AREA );
		}

		//-----------------------------------------
		// Wrap form and show
		//-----------------------------------------

		$template = $this->registry->getClass('output')->getTemplate('ucp')->userCPTemplate( $_TAB, $html, $tabs, $_AREA, $errors, $usercp_module->hide_form_and_save_button, $usercp_module->uploadFormMax );

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->setTitle( ( $this->_pageTitle ) ? "{$this->lang->words['pagetitle_bit']} : " . $this->_pageTitle . ' - ' . $this->settings['board_name'] : "{$this->lang->words['pagetitle_bit']} - " . $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $template );

		if ( is_array( $this->_nav ) AND count( $this->_nav ) )
		{
			foreach( $this->_nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
			}
		}

		if ( is_array( $usercp_module->_nav ) AND count( $usercp_module->_nav ) )
		{
			foreach( $usercp_module->_nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
			}
		}

        $this->registry->getClass('output')->sendOutput();
	}
}