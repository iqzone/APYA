<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template editing
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

class admin_core_templates_templates extends ipsCommand
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=templates';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=templates';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinCaching( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'templates_manage' );
				$this->_listTemplateGroups();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * List template sets
	 *
	 * @return	@e void
	 */
	protected function _listTemplateGroups()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID           = intval( $this->request['setID'] );
		$templateGroups  = array();
		$css			 = array();
		$setData         = array();
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		/* is this a vised skin? */
		if ( $setData['set_by_skin_gen'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . '&amp;app=core&amp;module=templates&amp;section=skinsets&amp;do=setEdit&amp;set_id=' . $setID );
		}
		
		//-----------------------------------------
		// Fetch Template Groups
		//-----------------------------------------
		
		$templateGroups = $this->skinFunctions->fetchTemplates( $setID, 'groupNames' );
		
		//-----------------------------------------
		// Get CSS
		//-----------------------------------------
	
		$_css = $this->skinFunctions->fetchCSS( $setID );
		
		//-----------------------------------------
		// Fix up positioning
		//-----------------------------------------
		
		foreach( $_css as $_id => $_data )
		{
			$_data['css_content'] = null;
			$css[ $_data['css_position'] . '.' . $_data['css_id'] ] = $_data;
		}
		
		ksort( $css, SORT_NUMERIC );
		
		//-----------------------------------------
		// Add in group counts
		//-----------------------------------------
		
		foreach( $templateGroups as $name => $data )
		{
			$templateGroups[ $name ]['_modCount'] = $this->skinFunctions->fetchModifiedTemplateCount( $setID, $name );
			unset( $templateGroups[ $name ]['template_name'] );
			unset( $templateGroups[ $name ]['template_data'] );
			unset( $templateGroups[ $name ]['template_content'] );
		}
		
		//-----------------------------------------
		// Now ensure that skin_global is first
		//-----------------------------------------
		
		$tmp = $templateGroups['skin_global'];
		unset( $templateGroups['skin_global'] );
		$templateGroups = array_merge( array( 'skin_global' => $tmp ), $templateGroups );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=overview', $this->lang->words['te_nav1'] );
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'].'module=templates&amp;section=templates&amp;do=list&amp;setID=' . $setID, $this->lang->words['te_nav2'] . $setData['set_name'] );
		$this->registry->output->extra_title[]	= "Manage Templates in " . $setData['set_name'];
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->templates_listTemplateGroups( $templateGroups, $css, $setData );
	}
}