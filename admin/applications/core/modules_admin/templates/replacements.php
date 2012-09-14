<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Macros/replacements
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

class admin_core_templates_replacements extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @var		object
	 */
	protected $skinFunctions;

	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;
	
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=replacements';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=replacements';
		
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
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'replacements_manage' );
				$this->_listReplacements();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * List available CSS for this skin set
	 *
	 * @return	@e void
	 */
	protected function _listReplacements()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID        = intval( $this->request['setID'] );
		$replacements = array();
		$setData      = array();
		
		//-----------------------------------------
		// Get template set data
		//-----------------------------------------
	
		$setData = $this->skinFunctions->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Get Replacements
		//-----------------------------------------
	
		$replacements = $this->skinFunctions->fetchReplacements( $setID );
		
		// Filter out ones belonging to apps not installed
		// @todo: remove those checks below once we abstract replacements to each app
		if ( !IPSLib::appIsInstalled( 'blog' ) )
		{
			$replacements = array_filter( $replacements, create_function( '$v', 'return !in_array( $v[\'replacement_key\'], array( \'blog_banish\', \'blog_blog\', \'blog_category\', \'blog_comments\', \'blog_comments_new\', \'blog_link\', \'blog_locked\', \'blog_rss_import\' ) );' ) );
		}
		if ( !IPSLib::appIsInstalled( 'gallery' ) )
		{
			$replacements = array_filter( $replacements, create_function( '$v', 'return !in_array( $v[\'replacement_key\'], array( \'galery_album_edit\', \'gallery_album_delete\', \'gallery_image\', \'gallery_link\', \'gallery_slideshow\' ) );' ) );
		}

		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=overview', $this->lang->words['re_nav1'] );
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=replacements&amp;do=list&amp;setID=' . $setID, $this->lang->words['re_nav2'] . $setData['set_name'] );
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->replacements_listReplacements( $replacements, $setData );
	}
}