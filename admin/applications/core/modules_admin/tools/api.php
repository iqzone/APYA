<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * API Users
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
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_tools_api extends ipsCommand
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
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin...
		//-----------------------------------------
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate('cp_skin_api');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=api';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=api';
		
		//-----------------------------------------
		// What are we to do, today?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'api_list':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiList();
			break;
			case 'api_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiForm( 'add' );
			break;
			case 'api_add_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiSave( 'add' );
			break;
			case 'api_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiForm( 'edit' );
			break;
			case 'api_edit_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_manage' );
				$this->apiSave( 'edit' );
			break;
			case 'api_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'api_remove' );
				$this->apiRemove();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * API User Remove
	 * Removes an API User
	 *
	 * @return	@e void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	public function apiRemove()
	{
		$api_user_id   = $this->request['api_user_id'] ? intval($this->request['api_user_id']) : 0;
		
		if( !$api_user_id )
		{
			$this->registry->output->global_message = $this->lang->words['a_whatuser'];
			$this->apiList();
			return;
		}
		
		$api_user = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'api_users', 'where' => 'api_user_id=' . $api_user_id ) );
		
		if ( ! $api_user['api_user_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['a_user404'];
			$this->apiList();
			return;
		}
		
		$this->DB->delete( 'api_users', 'api_user_id='.$api_user_id );
		
		$this->registry->output->global_message = $this->lang->words['a_removed'];
		$this->apiList();
	}

	/**
	 * API Save
	 * Save API user
	 *
	 * @param	string		Type
	 * @return	@e void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	public function apiSave( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_user_id   = $this->request['api_user_id'] ? intval($this->request['api_user_id']) : 0;
		$api_user_key  = $this->request['api_user_key'];
		$api_user_name = $this->request['api_user_name'];
		$api_user_ip   = $this->request['api_user_ip'];
		$permissions = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( ! $api_user_name )
		{
			$this->registry->output->global_message = $this->lang->words['a_entertitle'];
			$this->apiForm( $type );
			return;
		}
		
		//-----------------------------------------
		// More checking...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			if ( ! $api_user_key )
			{
				$this->registry->output->global_message = $this->lang->words['a_noapikey'];
				$this->apiForm( $type );
				return;
			}
		}
		else
		{
			$api_user = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'api_users', 'where' => 'api_user_id='.$api_user_id ) );
			
			if ( ! $api_user['api_user_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_user404'];
				$this->apiList();
				return;
			}
		}
		
		//-----------------------------------------
		// Save basics
		//-----------------------------------------
		
		$save = array( 'api_user_name' => $api_user_name,
					   'api_user_ip'   => $api_user_ip
					  );
		
		//-----------------------------------------
		// Sort out permissions...
		//-----------------------------------------
		
		foreach( $this->request as $key => $value )
		{
			if ( preg_match( "#^_perm_([^_]+?)_(.*)$#", $key, $matches ) )
			{
				$module   = $matches[1];
				$function = $matches[2];
				
				if ( $value )
				{
					$permissions[ $module ][ $function ] = 1;
				}
			}
		}
	
		//-----------------------------------------
		// Add in perms
		//-----------------------------------------
		
		$save['api_user_perms'] = serialize( $permissions );
		
		//-----------------------------------------
		// Save...
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Add in key..
			//-----------------------------------------
			
			$save['api_user_key'] = $api_user_key;
			
			//-----------------------------------------
			// Save it...
			//-----------------------------------------
			
			$this->registry->output->global_message = $this->lang->words['a_added'];
			
			$this->DB->insert( 'api_users', $save );
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['a_edited'];
			
			$this->DB->update( 'api_users', $save, 'api_user_id=' . $api_user_id );
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * API Form
	 * Shows the add/edit form
	 *
	 * @param	string		Type
	 * @return	@e void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	public function apiForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$api_user_id = $this->request['api_user_id'] ? intval($this->request['api_user_id']) : 0;
		$form        = array();
		$permissions = array();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode  = 'api_add_save';
			$title     = $this->lang->words['a_createnew'];
			$button    = $this->lang->words['a_createnew'];
			$api_user  = array();
			$api_perms = array();
		}
		else
		{
			$api_user = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'api_users', 'where' => 'api_user_id='.$api_user_id ) );
			
			if ( ! $api_user['api_user_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['a_user404'];
				$this->apiList();
				return;
			}
			
			$formcode = 'api_edit_save';
			$title    = $this->lang->words['a_edituser'].$api_user['api_user_name'];
			$button   = $this->lang->words['a_savechanges'];
			
			$api_perms = unserialize( $api_user['api_user_perms'] );
		}
		
		//-----------------------------------------
		// Form
		//-----------------------------------------
		
		$form['api_user_name'] = $this->registry->output->formInput( 'api_user_name', !empty($_POST['api_user_name']) ? stripslashes($_POST['api_user_name']) : $api_user['api_user_name'] );
		$form['api_user_ip']   = $this->registry->output->formInput( 'api_user_ip', !empty($_POST['api_user_ip']) ? stripslashes($_POST['api_user_ip']) : $api_user['api_user_ip'] );
		
		//-----------------------------------------
		// Get all modules and stuff and other things
		//-----------------------------------------
		
		$path   = DOC_IPS_ROOT_PATH . 'interface/board/modules';
		
		if ( is_dir( $path ) )
		{
			$handle = opendir( $path );

			while ( ( $file = readdir($handle) ) !== FALSE )
			{
				if ( is_dir( $path . '/' . $file ) )
				{
					if ( is_file( $path . '/' . $file . '/config.php' ) )
					{
						$CONFIG = array();
						$_name  = $file;
				
						require_once( $path . "/" . $file . '/config.php' );/*noLibHook*/
									
						if ( $CONFIG['api_module_title'] )
						{
							$permissions[ $_name ] = array(  'key'    => $CONFIG['api_module_key'],
															 'title'  => $CONFIG['api_module_title'],
															 'desc'   => $CONFIG['api_module_desc'],
															 'path'   => $path . "/" . $file,
															 'perms'  => array() );
															
							//-----------------------------------------
							// Get all available methods
							//-----------------------------------------
							
							if ( is_file( $path . '/' . $file . '/methods.php' ) )
							{
								$ALLOWED_METHODS = array();
								require_once( $path . '/' . $file . '/methods.php' );/*noLibHook*/
								
								$permissions[ $_name ]['perms'] = array_keys( $ALLOWED_METHODS );
							}
							
							//-----------------------------------------
							// Sort out form field
							//-----------------------------------------
							
							if ( is_array( $permissions[ $_name ]['perms'] ) )
							{
								foreach( $permissions[ $_name ]['perms'] as $perm )
								{
									$_checked = intval( $api_perms[ $_name ][ $perm ] );
									$permissions[ $_name ]['form_perms'][ $perm ] = array( 'title' => $perm,
																						   'form'  => $this->registry->output->formCheckbox( '_perm_' . $_name . '_' . $perm, $_checked ) );
								}
							}
						}
						
						$CONFIG          = array();
						$ALLOWED_METHODS = array();
					}
				}
			}

			closedir( $handle );
		}
		
		//-----------------------------------------
		// Auto-generate API key
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$form['_api_user_key'] = md5( mt_rand() . $this->memberData['member_login_key'] . uniqid( mt_rand(), true ) );
		}
		
		$this->registry->output->html .= $this->registry->output->global_template->information_box( $this->lang->words['a_title'], $this->lang->words['a_msg2'] ) . "<br />";
		$this->registry->output->html .= $this->html->api_form( $form, $title, $formcode, $button, $api_user, $type, $permissions );
		
	}

	/**
	 * API LIST
	 * List all currently stored API users
	 *
	 * @return	@e void		[Outputs]
	 * @author 	Matt Mecham
	 * @since  	2.3.2
	 */
	public function apiList()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$users = array();
		
		//-----------------------------------------
		// Get users from the DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'api_users', 'order' => 'api_user_id' ) );			
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$users[] = $row;
		}
		
		//-----------------------------------------
		// Dun...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->api_list( $users );
	}
}