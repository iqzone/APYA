<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login Manager Administration
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Thursday 26th January 2006 (11:03)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_tools_login extends ipsCommand
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
		// Load language and skin
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_tools');
		
		//-----------------------------------------
		// Set URL shortcuts
		//-----------------------------------------
		
		$this->form_code    = $this->html->form_code	= 'module=tools&amp;section=login';
		$this->form_code_js = $this->html->form_code_js	= 'module=tools&section=login';
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'login_manage' );
		
		//-----------------------------------------
		// What are we doing now?
		//-----------------------------------------
		
		switch(ipsRegistry::$request['do'])
		{
			case 'manage':
			default:
				ipsRegistry::$request[ 'do'] =  'manage' ;
				$this->_loginList();
			break;
			
			case 'login_toggle':
				$this->_loginToggle();
			break;
			
			case 'login_uninstall':
				$this->_loginUninstall();
			break;
			
			case 'login_install':
				$this->_loginInstall();
			break;
			
		  	case 'login_reorder':
			 	$this->_loginReorder();
		  	break;
			
			case 'login_add':
				$this->_loginForm('add');
			break;

			case 'login_add_do':
				$this->_loginSave('add');
			break;
				
			case 'login_edit_details':
				$this->_loginForm('edit');
			break;

			case 'login_edit_do':
				$this->_loginSave('edit');
			break;
			
			case 'login_acp_conf':
				$this->_loginACPConf();
			break;
			
			case 'login_save_conf':
				$this->_loginSaveConf();
			break;
		
			case 'master_xml_export':
				$this->_masterXmlExport();
			break;
			
			case 'login_export':
				$this->_masterXmlExport( ipsRegistry::$request['login_id'] );
			break;
					
			case 'login_diagnostics':
				$this->_loginDiagnostics();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Save login method configuration details
	 *
	 * @return	boolean	Saved or not
	 */
	protected function _loginSaveConf()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval($this->request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
			
		if ( ! $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return false;
		}

		//-----------------------------------------
		// Check (still waiting)
		//-----------------------------------------
		
		if( !is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/acp.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}

		//-----------------------------------------
		// Check our ACP options file
		//-----------------------------------------
		$config = array();
		require_once( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/acp.php' );/*noLibHook*/
		
		if( !is_array($config) OR !count($config) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		$save = array();
		
		foreach( $config as $option )
		{
			if( $option['key'] )
			{
				$save[ $option['key'] ] = str_replace( '&#092;', '\\', $_POST[ $option['key'] ] );
			}
		}
		
		$this->DB->update( 'login_methods', array( 'login_custom_config' => serialize( $save ) ), 'login_id=' . $login_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		//-----------------------------------------
		// And then show list
		//-----------------------------------------
		
		ipsRegistry::getClass('output')->global_message = sprintf($this->lang->words['l_confup'], $login['login_title'] );
		$this->_loginList();
		return true;
	}
	
	/**
	 * Configure details specific to a login method
	 *
	 * @return	mixed		Outputs, or return false
	 * @todo 	[Future] Remove legacy check for conf.php file in IPB 3.4 or higher
	 */
	protected function _loginACPConf()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval(ipsRegistry::$request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
			
		if ( ! $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return false;
		}
		
		$LOGIN_CONF	= $login['login_custom_config'] ? @unserialize($login['login_custom_config']) : array();
		
		//-----------------------------------------
		// If not populated, check for flat file config (legacy)
		//-----------------------------------------
		
		if( !count($LOGIN_CONF) )
		{
			if( is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/conf.php' ) )
			{
				require( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/conf.php' );/*noLibHook*/
			}
		}

		//-----------------------------------------
		// Check (still waiting)
		//-----------------------------------------
		
		if( !is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/acp.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}

		//-----------------------------------------
		// Get config
		//-----------------------------------------

		$config		= array();

		require_once( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/acp.php' );/*noLibHook*/
		
		if( !is_array($config) OR !count($config) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}

		//-----------------------------------------
		// Teh form
		//-----------------------------------------

		$form = array();
		
		foreach( $config as $option )
		{
			$form_control = '';
			
			if( $option['type'] == 'yesno' )
			{
				$form_control = ipsRegistry::getClass('output')->formYesNo( $option['key'], $_POST[ $option['key'] ] ? $_POST[ $option['key'] ] : $LOGIN_CONF[ $option['key'] ] );
			}
			else if( $option['type'] == 'select' )
			{
				$form_control = ipsRegistry::getClass('output')->formDropdown( $option['key'], $option['options'], $_POST[ $option['key'] ] ? $_POST[ $option['key'] ] : $LOGIN_CONF[ $option['key'] ] );
			}
			else
			{
				$form_control = ipsRegistry::getClass('output')->formInput( $option['key'], $_POST[ $option['key'] ] ? $_POST[ $option['key'] ] : $LOGIN_CONF[ $option['key'] ] );
			}
			
			$form[] = array(
							'title'			=> $option['title'],
							'description'	=> $option['description'],
							'control'		=> $form_control
							);
		}
		
		ipsRegistry::getClass('output')->html .= $this->html->login_conf_form( $login, $form );
	}
	
	
	/**
	 * Build XML file from array of data
	 *
	 * @param	array 		Entries to add
	 * @return	string		XML Document
	 */
	protected function _buildXML( $data=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'export' );
		$xml->addElement( 'group', 'export' );

		//-----------------------------------------
		// Set group
		//-----------------------------------------

		foreach( $data as $thisentry => $r )
		{
			$r['login_enabled']			= 0;
			
			if ( $r['login_folder_name'] == 'internal' )
			{
				$r['login_enabled']			= 1;
			}
			else if ( $r['login_folder_name'] == 'ipconverge' )
			{
				$r['login_maintain_url']	= '';
				$r['login_register_url']	= '';
				$r['login_login_url']		= '';
				$r['login_logout_url']		= '';
				$r['login_enabled']			= 0;
			}
			
			unset($r['login_id']);
			
			$xml->addElementAsRecord( 'group', 'row', $r );
		}

		return $xml->fetchDocument();
	}
	
	/**
	 * Export master XML file for installer
	 *
	 * @param	integer		[Optional] Login ID
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _masterXmlExport( $login_id=0 )
	{		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entries	= array();
		$where		= '';
		
		if( $login_id )
		{
			$where = 'login_id=' . intval($login_id);
		}

		//-----------------------------------------
		// Get login methods
		//-----------------------------------------
	
		$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'login_methods',
										'where'	=> $where
								) 		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$entries[] = $r;
		}

		$document = $this->_buildXML( $entries );

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$filename = $login_id ? 'loginauth_install.xml' : 'loginauth.xml';
		
		ipsRegistry::getClass('output')->showDownload( $document, $filename, '', 0 );
	}
	
	/**
	 * Shows the login 'diagnostics' screen
	 *
	 * @return	mixed		Outputs, or returns false
	 */
	protected function _loginDiagnostics()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval(ipsRegistry::$request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
			
		if ( ! $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return false;
		}

		/* Try to setup the module */
		require_once( IPS_PATH_CUSTOM_LOGIN . '/login_core.php' );/*noLibHook*/
    	require_once( IPS_PATH_CUSTOM_LOGIN . '/login_interface.php' );/*noLibHook*/

    	$login['_file_auth_exists']	= false;

		if( is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/auth.php' ) )
		{
			$LOGIN_CONF	= $login['login_custom_config'] ? @unserialize($login['login_custom_config']) : array();	
			
			$classToLoad = IPSLib::loadLibrary( IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'] . '/auth.php', 'login_' . $login['login_folder_name'] );
			$module		 = new $classToLoad( $this->registry, $login, $LOGIN_CONF );
			
			if( isset($module->missingModules) && is_array($module->missingModules) && count($module->missingModules) )
			{
				$login['_missingModules'] = implode( ', ', $module->missingModules );
			}

			$login['_file_auth_exists']	= true;
		}
		
		ipsRegistry::getClass('output')->html .= $this->html->login_diagnostics( $login );
	}
	
	/**
	 * Saves the login method to the database [add,edit]
	 *
	 * @param	string		Add or Edit flag
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginSave($type='add')
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id				= intval(ipsRegistry::$request['login_id']);
		$login_title			= trim( ipsRegistry::$request['login_title'] );
		$login_description		= trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['login_description'])) );
		$login_folder_name		= trim( ipsRegistry::$request['login_folder_name'] );
		$login_maintain_url		= trim( ipsRegistry::$request['login_maintain_url'] );
		$login_register_url		= trim( ipsRegistry::$request['login_register_url'] );
		$login_alt_login_html	= trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['login_alt_login_html'])) );
		$login_alt_acp_html		= trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['login_alt_acp_html'])) );
		$login_enabled			= intval(ipsRegistry::$request['login_enabled']);
		$login_settings			= intval(ipsRegistry::$request['login_settings']);
		$login_replace_form		= intval(ipsRegistry::$request['login_replace_form']);
		$login_safemode			= intval(ipsRegistry::$request['login_safemode']);
		$login_login_url		= trim( ipsRegistry::$request['login_login_url'] );
		$login_logout_url		= trim( ipsRegistry::$request['login_logout_url'] );
		$login_complete_page	= trim( ipsRegistry::$request['login_complete_page'] );
		$login_user_id			= in_array( ipsRegistry::$request['login_user_id'], array( 'username', 'email', 'either' ) ) ? ipsRegistry::$request['login_user_id'] : 'username';
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $login_id )
			{
				ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
				$this->_loginList();
				return;
			}
		}
		
		if ( ! $login_title OR ! $login_folder_name )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_form'];
			$this->_loginForm( $type );
			return;
		}
		
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 'login_title'			=> $login_title,
						'login_description'		=> $login_description,
						'login_folder_name'		=> $login_folder_name,
						'login_maintain_url'	=> $login_maintain_url,
						'login_register_url'	=> $login_register_url,
						'login_alt_login_html'	=> $login_alt_login_html,
						'login_alt_acp_html'	=> $login_alt_acp_html,
						'login_enabled'			=> $login_enabled,
						'login_settings'		=> $login_settings,
						'login_replace_form'	=> $login_replace_form,
						'login_logout_url'		=> $login_logout_url,
						'login_login_url'		=> $login_login_url,
						'login_user_id'			=> $login_user_id
					 );
		
		//--------------------------------------------
		// In DEV?
		//--------------------------------------------
		
		if ( IN_DEV )
		{
			$array['login_safemode']  = $login_safemode;
		}
		
		//--------------------------------------------
		// Nike.. do it
		//--------------------------------------------
		
		if ( $type == 'add' )
		{
			$this->DB->insert( 'login_methods', $array );
			
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_added'];
		}
		else
		{
			$this->DB->update( 'login_methods', $array, 'login_id='.$login_id );
			
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_edited'];
		}
		
		if( $login_folder_name == 'ipconverge' )
		{
			IPSLib::updateSettings( array( 'ipconverge_enabled' => $login_enabled ) );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		$this->_loginList();
	}
	
	/**
	 * Shows the login method form [add,edit]
	 *
	 * @param	string		Add or Edit flag
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginForm( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval(ipsRegistry::$request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'login_add_do';
			$title    = $this->lang->words['l_registernew'];
			$button   = $this->lang->words['l_registernew'];
		}
		else
		{
			$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id='.$login_id ) );
			
			if ( ! $login['login_id'] )
			{
				ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
				$this->_loginList();
				return;
			}
			
			$formcode = 'login_edit_do';
			$title    = $this->lang->words['editloginmethod'] . $login['login_title'];
			$button   = $this->lang->words['sl_form_edit_button'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$valid_types	= array( array( 'username', $this->lang->words['l_username'] ), array( 'email', $this->lang->words['l_email'] ), array( 'either', $this->lang->words['l_username_or_email'] ) );
		$form 			= array();
		
		$form['login_title']			= ipsRegistry::getClass('output')->formInput(		'login_title'			, $_POST['login_title']			? $_POST['login_title']			: $login['login_title'] );
		$form['login_description']		= ipsRegistry::getClass('output')->formInput(		'login_description'		, IPSText::htmlspecialchars( $_POST['login_description'] ? $_POST['login_description'] : $login['login_description'] ) );
		$form['login_folder_name']		= ipsRegistry::getClass('output')->formInput(		'login_folder_name'		, $_POST['login_folder_name']	? $_POST['login_folder_name']	: $login['login_folder_name'] );
		$form['login_maintain_url']		= ipsRegistry::getClass('output')->formInput(		'login_maintain_url'	, $_POST['login_maintain_url']	? $_POST['login_maintain_url']	: $login['login_maintain_url'] );
		$form['login_register_url']		= ipsRegistry::getClass('output')->formInput(		'login_register_url'	, $_POST['login_register_url']	? $_POST['login_register_url']	: $login['login_register_url'] );
		$form['login_login_url']		= ipsRegistry::getClass('output')->formInput(		'login_login_url'		, $_POST['login_login_url'] 	? $_POST['login_login_url']		: $login['login_login_url'] );
		$form['login_logout_url']		= ipsRegistry::getClass('output')->formInput(		'login_logout_url'		, $_POST['login_logout_url']	? $_POST['login_logout_url']	: $login['login_logout_url'] );
		$form['login_enabled']			= ipsRegistry::getClass('output')->formYesNo(		'login_enabled'			, $_POST['login_enabled']		? $_POST['login_enabled']		: $login['login_enabled'] );
		$form['login_settings']			= ipsRegistry::getClass('output')->formYesNo(		'login_settings'		, $_POST['login_settings']		? $_POST['login_settings']		: $login['login_settings'] );
		$form['login_register_url']		= ipsRegistry::getClass('output')->formInput(		'login_register_url'	, $_POST['login_register_url']	? $_POST['login_register_url']	: $login['login_register_url'] );
		$form['login_replace_form']		= ipsRegistry::getClass('output')->formYesNo(		'login_replace_form'	, $_POST['login_replace_form']	? $_POST['login_replace_form']	: $login['login_replace_form'] );
		$form['login_alt_login_html']	= ipsRegistry::getClass('output')->formTextarea(	'login_alt_login_html'	, IPSText::htmlspecialchars( $_POST['login_alt_login_html'] ? $_POST['login_alt_login_html'] : $login['login_alt_login_html'] ) );
		$form['login_alt_acp_html']		= ipsRegistry::getClass('output')->formTextarea(	'login_alt_acp_html'	, IPSText::htmlspecialchars( $_POST['login_alt_acp_html'] ? $_POST['login_alt_acp_html'] : $login['login_alt_acp_html'] ) );
		$form['login_user_id']			= ipsRegistry::getClass('output')->formDropdown(	'login_user_id'			, $valid_types, $_POST['login_user_id']	? $_POST['login_user_id']	: $login['login_user_id'] );
		
		if ( IN_DEV )
		{
			$form['login_safemode']  = ipsRegistry::getClass('output')->formYesNo( 'login_safemode' , $_POST['login_safemode']  ? $_POST['login_safemode'] : $login['login_safemode'] );
		}
		
		ipsRegistry::getClass('output')->html .= $this->html->login_form( $form, $title, $formcode, $button, $login );
	}
	
	/**
	 * Lists the login method overview screen
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginList()
	{
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$content		= "";
		$db_methods		= array();
		$dir_methods	= array();
		$installed		= array();
		
		//-----------------------------------------
		// Get login methods from database
		//-----------------------------------------
		
		$i	= 0;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'order' => 'login_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['login_installed'] 				= 1;
			$db_methods[ $i ]					= $r;
			$installed[]						= $r['login_folder_name'];
			$i++;
		}

		ksort( $db_methods );
		
		//-----------------------------------------
		// Now get the available login methods
		//-----------------------------------------
		
		$dh = opendir( IPS_PATH_CUSTOM_LOGIN );
		
		if ( $dh !== false )
		{
			while ( false !== ($file = readdir($dh) ) )
			{
				if( is_dir( IPS_PATH_CUSTOM_LOGIN . '/' . $file ) AND !in_array( $file, array( '.', '..', '.svn', '.DS_Store', '_vti_cnf' ) ) )
				{
					$data = array( 'login_title' => $file, 'login_folder_name' => $file );
					
					if( is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' ) )
					{
						$file_content = file_get_contents( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' );
						
						$xml->loadXML( $file_content );

						foreach( $xml->fetchElements('row') as $record )
						{
							$data	= $xml->fetchElementsFromRecord( $record );
						}
					}
					
					$data['acp_plugin']	= is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/acp.php' ) ? 1 : 0;

					$dir_methods[ $file ] = $data;
				}
			}
			
			closedir( $dh );
		}

		//-----------------------------------------
		// First...we show installed methods
		//-----------------------------------------
		
		$content 	.= $this->html->login_subheader( $this->lang->words['l_installed'] );
		$dbm_count	= 0;
		
		if( count($db_methods ) )
		{
			foreach( $db_methods as $r )
			{
				$dbm_count++;
				
				$r['_enabled_img']		= $r['login_enabled']   ? 'tick.png' : 'cross.png';
				$r['acp_plugin']		= $dir_methods[ $r['login_folder_name'] ]['acp_plugin'];
				
				$content .= $this->html->login_row($r);
			}
		}
		
		if( !$dbm_count )
		{
			$content .= $this->html->login_norow( "installed" );
		}
		
		//-----------------------------------------
		// Then the ones not installed
		//-----------------------------------------
		
		$content 	.= $this->html->login_subheader( $this->lang->words['l_others'] );
		$dm_count	= 0;
		
		if( count( $dir_methods ) )
		{
			foreach( $dir_methods as $r )
			{
				if( in_array( $r['login_folder_name'], $installed ) )
				{
					continue;
				}
				
				$dm_count++;
				
				// Need to set a bogus login id to ensure HTML ids are unique for the javascript
				$r['login_id']			= str_replace( '.', '', uniqid( 'abc', true ) );
				
				$r['_enabled_img']		= 'cross.png';
				
				$content .= $this->html->login_row($r);
			}
		}
		
		if( !$dm_count )
		{
			$content .= $this->html->login_norow( "uninstalled" );
		}

		ipsRegistry::getClass('output')->html .= $this->html->login_overview( $content );
	}
	
	/**
	 * Toggle login method enabled/disabled
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginToggle()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id	= intval(ipsRegistry::$request['login_id']);
		
		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id, login_enabled, login_folder_name', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
		
		if( !$login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return;
		}
		
		if( $login['login_enabled'] )
		{
			$toggle = $this->lang->words['l_disabled'];
			
			$this->DB->update( 'login_methods', array( 'login_enabled' => 0 ), 'login_id=' . $login_id );
			
			if( $login['login_folder_name'] == 'ipconverge' )
			{
				IPSLib::updateSettings( array( 'ipconverge_enabled' => 0 ) );
			}
		}
		else
		{
			$toggle = $this->lang->words['l_enabled'];
			
			$this->DB->update( 'login_methods', array( 'login_enabled' => 1 ), 'login_id=' . $login_id );
			
			if( $login['login_folder_name'] == 'ipconverge' )
			{
				IPSLib::updateSettings( array( 'ipconverge_enabled' => 1 ) );
			}
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();
		
		ipsRegistry::getClass('output')->global_message = $this->lang->words['l_successfully'] . $toggle;
		
		$this->_loginList();
	}
	
	/**
	 * Uninstall a login method
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginUninstall()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id	= intval(ipsRegistry::$request['login_id']);
		
		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id, login_enabled, login_folder_name', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
		
		if( !$login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return;
		}
		
		$this->DB->delete( 'login_methods', 'login_id=' . $login_id );
		
		if( $login['login_folder_name'] == 'ipconverge' )
		{
			IPSLib::updateSettings( array( 'ipconverge_enabled' => 0 ) );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		ipsRegistry::getClass('output')->global_message = $this->lang->words['l_uninstalled'];
		
		$this->_loginList();
	}
	
	/**
	 * Install a login method
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginInstall()
	{
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml		= new classXML( IPS_DOC_CHAR_SET );
		$login_id	= basename(ipsRegistry::$request['login_folder']);
		
		//-----------------------------------------
		// Now get the XML data
		//-----------------------------------------
		
		$dh = opendir( IPS_PATH_CUSTOM_LOGIN );
		
		if ( $dh !== false )
		{
			while ( false !== ($file = readdir($dh) ) )
			{
				if( is_dir( IPS_PATH_CUSTOM_LOGIN . '/' . $file ) AND $file == $login_id )
				{
					if( is_file( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' ) )
					{
						$file_content = file_get_contents( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' );
						
						$xml->loadXML( $file_content );

						foreach( $xml->fetchElements('row') as $record )
						{
							$data  = $xml->fetchElementsFromRecord( $record );
						}
					}
					else
					{
						closedir( $dh );

						ipsRegistry::getClass('output')->global_message = $this->lang->words['l_installer404'];
						$this->_loginList();
						return;
					}
					
					$dir_methods[ $file ] = $data;
					
					break;
				}
			}
			
			closedir( $dh );
		}

		if( !is_array($dir_methods) OR !count($dir_methods) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_installer404'];
			$this->_loginList();
			return;
		}

		//-----------------------------------------
		// Now verify it isn't installed
		//-----------------------------------------
		
		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id', 'from' => 'login_methods', 'where' => "login_folder_name='" . $login_id . "'" ) );
		
		if( $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_already'];
			$this->_loginList();
			return;
		}
		
		//-----------------------------------------
		// Get the highest order and insert method
		//-----------------------------------------
		
		$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(login_order) as highest_order', 'from' => 'login_methods' ) );
		
		$dir_methods[ $login_id ]['login_order'] = $max['highest_order'] + 1;
		
		$this->DB->insert( 'login_methods', $dir_methods[ $login_id ] );

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		ipsRegistry::getClass('output')->global_message = $this->lang->words['l_yesinstalled'];
		
		$this->_loginList();
	}
	
	/**
	 * Reorder a login method
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _loginReorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;

 		if( is_array($this->request['logins']) AND count($this->request['logins']) )
 		{
 			foreach( $this->request['logins'] as $this_id )
 			{
 				if( intval($this_id) == $this_id )
 				{
	 				$this->DB->update( 'login_methods', array( 'login_order' => $position ), 'login_id=' . intval($this_id) );
	 				
	 				$position++;
 				}
 			}
 		}
 		
 		$this->loginsRecache();

 		$ajax->returnString( 'OK' );
 		exit();
	}

	/**
	 * Updates cache store record
	 *
	 * @return	boolean		Cache store updated successfully
	 */
	public function loginsRecache()
	{
		$cache	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1', 'order' => 'login_order ASC' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{	
			$cache[ $r['login_id'] ] = $r;
		}
		
		ipsRegistry::cache()->setCache( 'login_methods', $cache, array( 'array' => 1 ) );
	}
}