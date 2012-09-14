<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * User agent management
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
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_tools_uagents extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
	/**
	 * User agent functions
	 *
	 * @var		object			Skin templates
	 */
	protected $userAgentFunctions;
	
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_tools');

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=uagents&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=uagents&';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php', 'userAgentFunctions' );
		$this->userAgentFunctions = new $classToLoad( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'uagents' );
				$this->_listUagents();
			break;
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'uagents' );
				$this->_uagentsReorder();
			break;			
			case 'groupsList':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'uagents' );
				$this->_listGroups();
			break;
			case 'groupAdd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ua_manage' );
				$this->_groupForm('add');
			break;
			case 'groupEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ua_manage' );
				$this->_groupForm('edit');
			break;
			case 'groupAddDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ua_manage' );
				$this->_groupSave( 'add' );
			break;
			case 'groupEditDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ua_manage' );
				$this->_groupSave( 'edit' );
			break;
			case 'groupRemove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ua_remove' );
				$this->_groupRemove();
			break;
			case 'rebuildMaster':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'uagents' );
				$this->_rebuildMasterUserAgents();
			break;
			case 'revert':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ua_manage' );
				$this->_revertUserAgent();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Group remove
	 *
	 * @return	string	HTML
	 */
	protected function _groupRemove()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ugroup_id    = intval( $this->request['ugroup_id'] );
		
		//-----------------------------------------
		// Remove it...
		//-----------------------------------------
		
		$this->userAgentFunctions->removeUserAgentGroup( $ugroup_id );
		
		return $this->_listGroups();
	}
	
	/**
	 * Form Save
	 *
	 * @param	string 		Type of form to show (add/edit)
	 * @return	string		HTML
	 */
	protected function _groupSave( $type )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ugroup_id    = intval( $this->request['ugroup_id'] );
		$ugroup_title = trim( $this->request['ugroup_title'] );
		$ugroupData   = json_decode( $_POST['uAgentsData'], TRUE );
		
		if ( ! $ugroup_title )
		{
			$ugroup_title = $this->lang->words['ua_unknown'] . time();
		}
		
		//-----------------------------------------
		// Save in function
		//-----------------------------------------
		
		$this->userAgentFunctions->saveUserAgentGroup( $ugroup_title, $ugroupData, $ugroup_id );
		
		return $this->_listGroups();
	}
	
	/**
	 * Form type
	 *
	 * @param	string 		Type of form to show (add/edit)
	 * @return	@e void
	 */
	protected function _groupForm( $type='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ugroup_id = intval( $this->request['ugroup_id'] );
		$ugroup    = array();
		$form 	   = array();
		
		//-----------------------------------------
		// Get user agents
		//-----------------------------------------
	
		$matrixAgents = $this->userAgentFunctions->fetchAgents();
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'groupAddDo';
			$title    = $this->lang->words['ua_addnewtitle'];
			$button   = $this->lang->words['ua_addnewbutton'];
		}
		else
		{
			$groups = $this->userAgentFunctions->fetchGroups();
			$ugroup = $groups[ $ugroup_id ];
			
			if ( ! $ugroup['ugroup_id'] )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['ua_noid'];
				$this->_groupForm( $type );
				return;
			}
			
			$formcode = 'groupEditDo';
			$title    = sprintf( $this->lang->words['ua_editua'], $ugroup['ugroup_title'] );
			$button   = $this->lang->words['ua_savechanges'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$form['ugroup_title'] = $this->registry->getClass('output')->formInput( 'ugroup_title', $_POST['ugroup_title'] ? $_POST['ugroup_title'] : $ugroup['ugroup_title'] );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=tools&amp;section=uagents&amp;do=groupsList', $this->lang->words['ua_nav1'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=tools&amp;section=uagents&amp;do=list', $this->lang->words['ua_nav2'] );
		
		$this->registry->getClass('output')->html .= $this->html->uagents_groupForm( $form, $title, $formcode, $button, $ugroup, $matrixAgents );
	}
	
	/**
	 * List available user agent groups
	 *
	 * @return	@e void
	 */
	protected function _listGroups()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$groups = array();
		
		//-----------------------------------------
		// Get user agents
		//-----------------------------------------
	
		$groups = $this->userAgentFunctions->fetchGroups();

		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=tools&amp;section=uagents&amp;do=groupsList', $this->lang->words['ua_nav1'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=tools&amp;section=uagents&amp;do=list', $this->lang->words['ua_nav2'] );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->uagents_listUagentGroups( $groups );
		
		//-----------------------------------------
		// Reset
		//-----------------------------------------
		
		$this->request['do'] = 'groupsList';
	}
	
	/**
	 * Rebuild master user agents
	 *
	 * @return	string	HTML
	 */
	protected function _rebuildMasterUserAgents()
	{
		$this->userAgentFunctions->rebuildMasterUserAgents();
		
		return $this->_listUagents();
	}
	
	/**
	 * List available user agents
	 *
	 * @return	@e void
	 */
	protected function _listUagents()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$agents = array();
		
		//-----------------------------------------
		// Get user agents
		//-----------------------------------------
	
		$matrixAgents = $this->userAgentFunctions->fetchAgents();

		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=tools&amp;section=uagents&amp;do=list', $this->lang->words['ua_nav1'] );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->uagents_listUagents( $matrixAgents );
	}
	
	/**
	 * Reorder useragents
	 *
	 * @return	@e void
	 */
	protected function _uagentsReorder()
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
 		
 		if( is_array($this->request['uagents']) AND count($this->request['uagents']) )
 		{
 			foreach( $this->request['uagents'] as $this_id )
 			{
 				$this->DB->update( 'core_uagents', array( 'uagent_position' => $position ), 'uagent_id=' . $this_id );
 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
 	}
 	
 	/**
 	 * Revert User Agent
 	 *
 	 * @return	@e	void
 	 */
 	protected function _revertUserAgent()
 	{
 		$id = intval( $this->request['id'] );
 		
 		$userAgent = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_uagents', 'where' => "uagent_id={$id}" ) );
 		if ( !$userAgent['uagent_id'] )
 		{
 			$this->registry->output->showError( $this->lang->words['uagent_locate'], 11160 );
 			return;
 		}
 		
 		$this->DB->update( 'core_uagents', array( 'uagent_regex' => $userAgent['uagent_default_regex'] ), "uagent_id={$id}" );
 		
 		$this->registry->output->redirect( $this->settings['base_url'] . 'app=core&amp;module=tools&amp;section=uagents', $this->lang->words['uagent_reverted'] );
 	}
}