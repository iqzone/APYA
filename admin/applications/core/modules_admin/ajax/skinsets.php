<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX Functions For applications/core/js/ipb3CSS.js file
 * Last Updated: $Date: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 8644 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_skinsets extends ipsAjaxCommand 
{
	/**
	 * Skin functions object handle
	 *
	 * @var		object
	 */
	protected $skinFunctions;
	
	/**
	 * Main executable
	 *
	 * @param	object	registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ), 'core' );
		
		$this->html = $this->registry->output->loadTemplate('cp_skin_templates');
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=skinsets';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=skinsets';
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		$this->skinFunctions = new skinCaching( $registry );

		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'showAddDialogue':
				$this->_showAddDialogue();
			break;
		}
	}
	
	/**
	 * Displays the add dialogue
	 *
	 * @return	string		Json
	 */
	protected function _showAddDialogue()
	{
		$this->returnHtml( $this->html->newSkinSetPopUp( IPSLib::hasActiveLicense( false ) ) );
	}

	
}