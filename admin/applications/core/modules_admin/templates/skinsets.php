<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Skin set management
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

class admin_core_templates_skinsets extends ipsCommand
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=skinsets';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=skinsets';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinGenerator.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinGenerator( $registry );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'overview':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_listSets();
			break;
			case 'setAdd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setForm('add');
			break;
			case 'setEdit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setForm('edit');
			break;
			case 'setAddDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setSave( 'add' );
			break;
			case 'setEditDo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setSave( 'edit' );
			break;
			case 'setWriteMaster':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setWriteMaster();
			break;
			case 'setWriteMasterCss':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_setWriteMasterCss();
			break;
			case 'setRemoveSplash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_setRemoveSplash();
			break;
			case 'setRemove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_setRemove();
			break;
			case 'revertSplash':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_revertSplash();
			break;
			case 'setRevert':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_delete' );
				$this->_setRevert();
			break;
			case 'makeDefault':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_makeDefault();
			break;
			case 'toggleHidden':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->_toggleHidden();
			break;
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'settemplates_manage' );
				$this->reorder();
			break;
			case 'skinGenStepOne':
				$this->_skinGenStepOne();
			break;
			case 'skinGenStepOneComplete':
				$this->_skinGenStepOneComplete();
			break;
			case 'skinGenStepTwoStart':
				$this->_skinGenStepTwoStart();
			break;
			case 'launchVisualEditor':
				$this->_launchVisualEditor();
			break;
			case 'cancelVisualEditor':
				$this->_cancelVisualEditor();
			break;
			case 'convertVisEditToFull':
				$this->_convertVisEditToFull();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Convert a skin from vissy eddy to full
	 */
	protected function _convertVisEditToFull()
	{
		$skinSetId = intval( $this->request['set_id'] );
		
		/* Set this user session */
		$this->skinFunctions->convertToFull( $skinSetId );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		
		$this->registry->output->global_message = $this->lang->words['skin_gen_convert_done'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Close the visual editor
	 *
	 * @return	@e void
	 */
	protected function _cancelVisualEditor()
	{
		$skinSet = intval( $this->request['set_id'] );
		
		/* Set this user session */
		$this->skinFunctions->deleteUserSession( $this->memberData['member_id'] );
		
		/* Onto step two */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}" );
	}
	
	/**
	 * Launch the visual editor
	 *
	 * @return	@e void
	 */
	protected function _launchVisualEditor()
	{
		$skinSet = intval( $this->request['set_id'] );
		
		/* Build the local cache */
		try
		{
			$this->skinFunctions->buildLocalJsonCache();
		}
		catch ( Exception $e )
		{
			die( $e->getMessage );
		}
		
		/* Set this user session */
		$this->skinFunctions->setUserSession( $this->memberData['member_id'], array( 'skin_set_id' => $skinSet ) );
		
		/* Onto step two */
		$this->registry->output->silentRedirect( $this->settings['public_url'] );
	}
	
	/**
	 * Step Two: Start
	 *
	 * @return	@e void
	 */
	protected function _skinGenStepTwoStart()
	{
		$skinSetId = intval( $this->request['set_id'] );
		
		$this->_listSets( $this->html->skinGeneratorReady( $skinSetId ) );
	}
	
	/**
	 * Step One: Save
	 *
	 * @return	@e void
	 */
	protected function _skinGenStepOneComplete()
	{
		$set_name            = $this->request['set_name'];
		$set_author_name     = $this->request['set_author_name'];
		$set_author_url      = $this->request['set_author_url'];
		$skinSet             = array();
		$set_key			 = IPS_UNIX_TIME_NOW;
		$messages = $errors  = array();
		
		/* Fetch default skin to set some defaults for this skin */
		$default = $this->skinFunctions->fetchSkinData( $this->skinFunctions->fetchSetIdByKey('default') );
		
		/* Check */
		if ( ! $set_name )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_specifyname'];
			$this->_start();
			return;
		}
		
		/* Build save array */
		$save = array(  'set_name'			=> $set_name,
					    'set_key'			=> $set_key,
						'set_parent_id'  	=> 0,
						'set_permissions'	=> '*',
						'set_is_default'	=> 0,
						'set_author_name'	=> $set_author_name,
						'set_author_url'	=> $set_author_url,
						'set_image_dir'		=> $default['set_image_dir'],
						'set_emo_dir'		=> $default['set_emo_dir'],
						'set_css_inline'	=> 1,
						'set_output_format' => 'html',
						'set_hide_from_list'=> 1,
						'set_minify'		=> $this->settings['use_minify'],
						'set_master_key'    => 'root',
						'set_updated'       => IPS_UNIX_TIME_NOW,
						'set_added'			=> IPS_UNIX_TIME_NOW,
						'set_by_skin_gen'   => 1,
						'set_skin_gen_data' => serialize( array() ) );
		
		/* Add position */
		$curPos = $this->skinFunctions->fetchHighestSetPosition();
		$save['set_order'] = (int)$curPos++;
			
		$this->DB->insert( 'skin_collections', $save );
		$set_id = $this->DB->getInsertId();
		
		/* Caches need to be rebuilt first, so that the parent tree is setup. Bug #21008 */
		$this->skinFunctions->rebuildSkinSetsCache();
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		/* Rebuild tree info */
		$this->skinFunctions->rebuildTreeInformation( $set_id );
		
		/* Flush the data */
		$this->skinFunctions->flushSkinData();
		
		/* Rebuild caches */
		$this->skinFunctions->rebuildCSS( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildPHPTemplates( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildReplacementsCache( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		/* Build the local cache */
		try
		{
			$this->skinFunctions->buildLocalJsonCache();
		}
		catch ( Exception $e )
		{
			die( $e->getMessage );
		}
		
		/* Set this user session */
		$this->skinFunctions->setUserSession( $this->memberData['member_id'], array( 'skin_set_id' => $set_id ) );
		
		/* Onto step two */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}&do=skinGenStepTwoStart&amp;set_id=" . $set_id );
	}
	
	/**
	 * Start. No really.
	 *
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	protected function _skinGenStepOne()
	{
		/* Basic checks and tests */
		$warnings = $this->skinFunctions->healthCheck();
		$errors   = array();
		$form     = array();
		
		if ( $warnings !== true )
		{
			$errors = $warnings;
		}
		
		/* Perform license check */
		if ( IPSLib::hasActiveLicense() !== true )
		{
			$errors[] = "License key out of date";
		}
		
		/* Form */
		$form['set_name']          = $this->registry->getClass('output')->formInput( 'set_name'         , $_POST['set_name'] );
		$form['set_author_name']   = $this->registry->getClass('output')->formInput( 'set_author_name'  , $_POST['set_author_name'] );
		$form['set_author_url']    = $this->registry->getClass('output')->formInput( 'set_author_url'   , $_POST['set_author_url'] );		

		$this->registry->output->html .= $this->html->skinGenerator( $form, $errors );
	}
	
	/**
	 * AJAX Action: Reorder Skin Sets
	 */
	protected function reorder()
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
 		
 		if( is_array($this->request['skin_sets']) AND count($this->request['skin_sets']) )
 		{
 			foreach( $this->request['skin_sets'] as $this_id )
 			{
 				$this->DB->update( 'skin_collections', array( 'set_order' => $position ), 'set_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->skinFunctions->rebuildSkinSetsCache();

 		$ajax->returnString( 'OK' );
 		exit();

	}
	
	/**
	 * Toggle a skin set's hidden status
	 *
	 * @return	string	HTML
	 */
	protected function _toggleHidden()
	{
		/* INIT */
		$set_id  = intval( $this->request['set_id'] );
		$skinSet = $this->skinFunctions->fetchSkinData( $set_id );
	
		/* Toggle.. */
		$this->DB->update( 'skin_collections', array( 'set_hide_from_list' => ( $skinSet['set_hide_from_list'] ) ? 0 : 1 ), 'set_is_default=0 AND set_id=' . $set_id );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		
		$this->registry->output->global_message = $this->lang->words['ss_hiddentoggled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Make a skin set default for output engine
	 *
	 * @return	string	HTML
	 */
	protected function _makeDefault()
	{
		/* INIT */
		$set_id  = intval( $this->request['set_id'] );
		
		$this->skinFunctions->makeDefault( $set_id );
		
		$this->skinFunctions->rebuildSkinSetsCache();
		
		$this->registry->output->global_message = $this->lang->words['ss_defaultdone'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
	}
	
	/**
	 * Remove customizations from a skin set
	 *
	 * @return	string	HTML
	 */
	protected function _setRevert()
	{
		$set_id       = intval( $this->request['setID'] );
		$authKey      = $this->request['authKey'];
		$setData      = $this->skinFunctions->fetchSkinData( $set_id );
		$templates    = intval( $this->request['templates'] );
		$css 	      = intval( $this->request['css'] );
		$replacements = intval( $this->request['replacements'] );
		
		/* Auth check */
		if ( $authKey != $this->member->form_hash )
		{
			$this->registry->output->global_error = $this->lang->words['ss_authkeyerror'];
			return $this->_listSets();
		}
		
		/* Do it */
		$this->skinFunctions->removeCustomizations( $set_id, array( 'templates' => $templates, 'css' => $css, 'replacements' => $replacements ) );
		$this->registry->output->global_message = $this->lang->words['ss_revertcomplete'];
		$this->_listSets();
	}
	
	/**
	 * Show revert splash screen
	 *
	 * @return	string	HTML
	 */
	protected function _revertSplash()
	{
		/* INIT */
		$setId   = intval( $this->request['setID'] );
		$setData = $this->skinFunctions->fetchSkinData( $setId );
		
		/* Fetch the numbers */
		$counts = $this->skinFunctions->fetchCustomizationCount( $setId );
		
		/* done */
		$this->registry->getClass('output')->html .= $this->html->skinsets_revertSplash( $setData, $counts );
	}
	
	/**
	 * Write out skin CSS in master format
	 *
	 * @return	string	HTML
	 */
	protected function _setWriteMasterCss()
	{
		$set_id = intval( $this->request['set_id'] );

		if ( ! $set_id OR ! IN_DEV OR ! isset( $this->skinFunctions->remapData['css'][ $set_id ] ) )
		{
			return $this->_listSets();
		}

		try
		{
			$messages = $this->skinFunctions->writeMasterSkinCss( $set_id, $this->skinFunctions->remapData['css'][ $set_id ] );
		}
		catch( Exception $error )
		{
			$this->registry->output->global_error = $this->lang->words['sk_error'] . $error->getMessage();
			return $this->_listSets();
		}

		/* done */
		$this->registry->getClass('output')->html .= $this->html->tools_toolResults( $this->lang->words['ss_masterwritten'], $messages );
	}
		
	/**
	 * Write out a skin set in master format
	 *
	 * @return	string	HTML
	 */
	protected function _setWriteMaster()
	{
		$set_id = intval( $this->request['set_id'] );
		
		if ( ! $set_id OR ! IN_DEV OR ! isset( $this->skinFunctions->remapData['templates'][ $set_id ] ) )
		{
			return $this->_listSets();
		}
		
		try
		{
			$messages = $this->skinFunctions->writeMasterSkin( $set_id, $this->skinFunctions->remapData['templates'][ $set_id ] );
		}
		catch( Exception $error )
		{
			$this->registry->output->global_error = $this->lang->words['sk_error'] . $error->getMessage();
			return $this->_listSets();
		}
		
		/* done */
		$this->registry->getClass('output')->html .= $this->html->tools_toolResults( $this->lang->words['ss_masterwritten'], $messages );
	}
	
	/**
	 * Remove Skin Set
	 *
	 * @return	string	HTML
	 */
	protected function _setRemove()
	{
		$set_id  = intval( $this->request['set_id'] );
		$authKey = $this->request['authKey'];
		
		/* Auth check */
		if ( $authKey != $this->member->form_hash )
		{
			$this->registry->output->global_error = $this->lang->words['ss_authkeyerror'];
			return $this->_listSets();
		}
		
		/* Can remove check */
		if ( $this->skinFunctions->removeSet( $set_id ) === FALSE )
		{
			$this->registry->output->global_error = $this->lang->words['ss_cannotremove'];
			return $this->_listSets();
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['ss_setremoved'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=overview' );
		}
	}
	
	/**
	 * Remove splash
	 *
	 * @return	@e void
	 */
	protected function _setRemoveSplash()
	{
		$set_id  = intval( $this->request['set_id'] );
		$setData = $this->skinFunctions->fetchSkinData( $set_id );
		
		if ( $this->skinFunctions->canRemoveSet( $set_id ) === FALSE )
		{
			$this->registry->output->global_error = $this->lang->words['ss_cannotremove'];
			return $this->_listSets();
		}
		
		$this->registry->getClass('output')->html .= $this->html->skinsets_removeSplash( $setData );
	}
	
	/**
	 * Form: Save
	 *
	 * @param	string 		Type of form to show (add/edit)
	 * @return	@e void
	 */
	protected function _setSave( $type='' )
	{
		$set_id              = intval( $this->request['set_id'] );
		$set_name            = $this->request['set_name'];
		$set_key             = IPSText::alphanumericalClean( $this->request['set_key'] );
		$set_parent_id       = intval( $this->request['set_parent_id'] );
		$set_permissions     = '';
		$set_permissions_all = intval( $this->request['set_permissions_all'] );
		$set_is_default      = intval( $this->request['set_is_default'] );
		$set_author_name     = $this->request['set_author_name'];
		$set_author_url      = $this->request['set_author_url'];
		$set_image_dir       = $this->request['set_image_dir'];
		$set_emo_dir         = $this->request['set_emo_dir'];
		$set_output_format   = IPSText::alphanumericalClean( $this->request['set_output_format'] );
		$set_hide_from_list  = intval( $this->request['set_hide_from_list'] );
		$skinSet             = array();
	
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			$skinSet = $this->skinFunctions->fetchSkinData( $set_id );
			
			if ( ! $skinSet['set_id'] )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['ss_noid'];
				$this->_setForm( $type );
				return;
			}
		}
		
		//-----------------------------------------
		// Global checks..
		//-----------------------------------------
		
		if ( ! $set_name )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_specifyname'];
			$this->_setForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Fix up permissions
		//-----------------------------------------
		
		if ( $set_permissions_all == 1 )
		{
			$set_permissions = '*';
		}
		else if ( is_array( $_POST['set_permissions'] ) AND count( $_POST['set_permissions'] ) )
		{
			$set_permissions = implode( ",", $_POST['set_permissions'] );
		}
		else
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_nogroupaccess'];
			$this->_setForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Check emo and img dir
		//-----------------------------------------
		
		if ( $this->skinFunctions->checkImageDirectoryExists( $set_image_dir ) !== TRUE )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_imgdirnoexist'];
			$this->_setForm( $type );
			return;
		}
		
		if ( $this->skinFunctions->checkEmoticonDirectoryExists( $set_emo_dir ) !== TRUE )
		{
			$this->registry->getClass('output')->global_message = $this->lang->words['ss_emodirnoexist'];
			$this->_setForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Make sure we're not moving skin set into self
		//-----------------------------------------
		
		if ( $type == 'edit' AND $set_parent_id )
		{
			if ( in_array( $set_parent_id, $skinSet['_childTree'] ) )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['ss_dontmoveintoself'];
				$this->_setForm( $type );
				return;
			}
		}
		
		/* test to ensure we're not re-using an existing skin set key */
		if ( $set_key )
		{
			$test = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'skin_collections',
													 'where'  => 'set_key=\'' . $set_key . '\' AND set_id != ' . $set_id ) );
													 
			if ( $test['set_id'] )
			{
				$this->registry->getClass('output')->global_message = $this->lang->words['ss_keynogoodyo'];
				$this->_setForm( $type );
				return;
			}
		}
		
		//-----------------------------------------
		// Build Save Array
		//-----------------------------------------
		
		$save = array(  'set_name'			=> $set_name,
					    'set_key'			=> $set_key,
						'set_parent_id'  	=> $set_parent_id,
						'set_permissions'	=> ( $set_is_default ) ? '*' : $set_permissions,
						'set_is_default'	=> $set_is_default,
						'set_author_name'	=> $set_author_name,
						'set_author_url'	=> $set_author_url,
						'set_image_dir'		=> $set_image_dir,
						'set_emo_dir'		=> $set_emo_dir,
						'set_css_inline'	=> 1,
						'set_output_format' => $set_output_format,
						'set_hide_from_list'=> ( $set_is_default ) ? 0 : $set_hide_from_list,
						'set_minify'		=> $this->settings['use_minify'],
						'set_master_key'    => ( isset( $skinSet['set_master_key'] ) ) ? $skinSet['set_master_key'] : ( ( $set_output_format == 'xml' ) ? 'xmlskin' : 'root' ),
						'set_updated'       => time() );
		
		
		if ( $type == 'edit' )
		{
			$this->DB->update( 'skin_collections', $save, 'set_id=' . $set_id );
		}
		else
		{
			/* Add elements into the array */
			$save['set_added'] = time();
			
			/* Add position */
			$curPos = $this->skinFunctions->fetchHighestSetPosition();
			$save['set_order'] = (int)$curPos++;
			
			$this->DB->insert( 'skin_collections', $save );
			$set_id = $this->DB->getInsertId();
		}
		
		//-----------------------------------------
		// Unset any other default skins
		//-----------------------------------------
		
		if ( $set_is_default )
		{
			$this->skinFunctions->makeDefault( $set_id );
		}
		
		$messages = array();
		$errors   = array();
		

		/* Caches need to be rebuilt first, so that the parent tree is setup. Bug #21008 */
		$this->skinFunctions->rebuildSkinSetsCache();
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		/* Did we move this back into root? */
		if ( $set_parent_id != $skinSet['set_parent_id'] )
		{
			if ( ! $set_parent_id && $set_key != 'mobile' && $skinSet['set_master_key'] != 'root' )
			{
				$this->DB->update( 'skin_collections', array( 'set_master_key' => 'root' ), 'set_id = ' . $set_id );
			}	
		}
		
		/* Rebuild tree info */
		$this->skinFunctions->rebuildTreeInformation( $set_id );
		
		/* Flush the data */
		$this->skinFunctions->flushSkinData();
		
		//-----------------------------------------
		// Rebuild Caches
		//-----------------------------------------
		
		$this->skinFunctions->rebuildCSS( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildPHPTemplates( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		$this->skinFunctions->rebuildReplacementsCache( $set_id );
		$messages   = array_merge( $messages, $this->skinFunctions->fetchMessages( TRUE ) );
		$errors     = array_merge( $errors  , $this->skinFunctions->fetchErrorMessages( TRUE ) );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->output->setMessage( $this->lang->words['ss_skinsetsaved'] . '<br />' . implode( '<br />', $messages ) . '<br />' . implode( '<br />', $errors ), true );
		
		$this->_listSets(); // Can't redirect here. See bug report 35785
	}
	
	/**
	 * Form
	 *
	 * @param	string 		Type of form to show (add/edit)
	 * @return	@e void
	 */
	protected function _setForm( $type='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		 
		$set_id         = intval( $this->request['set_id'] );
		$parents        = array();
		$allSets        = array();
		$skinSet        = array();
		$form 	        = array();
		$emoDirs        = array();
		$skinDirs       = array();
		$outputFormats  = array();
		$setPermissions = array();
		
		//-----------------------------------------
		// Get parents and this skin set if editing
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'skin_collections' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$allSets[ $row['set_id'] ] = $row;
			
			if ( ( $row['set_id'] < 0 ) AND ( $type == 'edit' AND $set_id != $row['set_id'] ) )
			{
				$parents[] = array( $row['set_id'], $row['set_name'] );
			}
			
			if ( $set_id == $row['set_id'] )
			{
				$skinSet = $row;
			}
		}
		
		//-----------------------------------------
		// Grab output formats
		//-----------------------------------------
		
		$_outputFormats = $this->skinFunctions->fetchOutputFormats();
				
		foreach( $_outputFormats as $key => $conf )
		{
			$outputFormats[] = array( $key, $conf['identifies_as'] );
		}
		
		//-----------------------------------------
		// Grab image / emo directories
		//-----------------------------------------
		
		$_imgDir = $this->skinFunctions->fetchImageDirectories();
		$_emoDir = $this->skinFunctions->fetchEmoticonDirectories();
		
		foreach( $_imgDir as $_dir )
		{
			$skinDirs[] = array( $_dir, $_dir );
		}
		
		foreach( $_emoDir as $_dir )
		{
			$emoDirs[] = array( $_dir, $_dir );
		}
 		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'setAddDo';
			$title    = $this->lang->words['ss_addnewset'];
			$button   = $this->lang->words['ss_addnewset'];
		}
		else
		{
			$formcode = 'setEditDo';
			$title    = $this->lang->words['ss_edituserset'] . $skinSet['set_name'];
			$button   = $this->lang->words['ss_saveset'];
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
											
		$form['set_name']          = $this->registry->getClass('output')->formInput(    'set_name'         , ( $_POST['set_name'] ) ? $_POST['set_name'] : $skinSet['set_name'] );
		$form['set_key']           = $this->registry->getClass('output')->formInput(    'set_key'          , ( $_POST['set_key'] ) ? $_POST['set_key'] : $skinSet['set_key'] );
		$form['set_is_default']    = $this->registry->getClass('output')->formCheckbox( 'set_is_default'   , ( $_POST['set_is_default'] ) ? $_POST['set_is_default'] : $skinSet['set_is_default'], 1, 'setIsDefault', 'onclick="checkMakeGlobal()"' );
		$form['set_author_name']   = $this->registry->getClass('output')->formInput(    'set_author_name'  , ( $_POST['set_author_name'] ) ? $_POST['set_author_name'] : $skinSet['set_author_name'] );
		$form['set_author_url']    = $this->registry->getClass('output')->formInput(    'set_author_url'   , ( $_POST['set_author_url'] ) ? $_POST['set_author_url'] : $skinSet['set_author_url'] );
		$form['set_parent_id']     = $this->skinFunctions->getTiersFunction()->fetchAllsItemDropDown( ( $_POST['set_parent_id'] ) ? $_POST['set_parent_id'] : $skinSet['set_parent_id'], array( $skinSet['set_id'] ), array( 0, $this->lang->words['none_root_set'] ) );
	    $form['set_image_dir']     = $this->registry->getClass('output')->formDropdown( 'set_image_dir'    , $skinDirs, ( $_POST['set_image_dir'] ) ? $_POST['set_image_dir'] : $skinSet['set_image_dir'] );
		$form['set_emo_dir']       = $this->registry->getClass('output')->formDropdown( 'set_emo_dir'      , $emoDirs, ( $_POST['set_emo_dir'] ) ? $_POST['set_emo_dir'] : $skinSet['set_emo_dir'] );
		$form['set_output_format'] = $this->registry->getClass('output')->formDropdown( 'set_output_format', $outputFormats, ( $_POST['set_output_format'] ) ? $_POST['set_output_format'] : ( !empty( $skinSet ) ? $skinSet['set_output_format'] : 'html' ) );
		$form['set_hide_from_list']= $this->registry->getClass('output')->formYesNo(   'set_hide_from_list', ( $_POST['set_hide_from_list'] ) ? $_POST['set_hide_from_list'] : $skinSet['set_hide_from_list'] );
				
		//-----------------------------------------
		// Get group permissions
		//-----------------------------------------
		
		$set_permissions      = is_array( $_POST['set_permissions'] )  ? $_POST['set_permissions']  : explode( ',', $skinSet['set_permissions']   );
		$set_permissions_all  = FALSE;
		
		if ( in_array( '*', $set_permissions ) OR $_POST['set_permissions_all'] )
		{
			$set_permissions_all         = TRUE;
			$form['set_permissions_all'] = ' checked="checked"';
		}
		
		$form['set_permissions']  = $this->registry->getClass('output')->generateGroupDropdown( 'set_permissions[]', $set_permissions, TRUE, 'setPermissions' );
		
		//-----------------------------------------
		// Navvy Gation
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'].'module=templates&amp;section=skinsets&amp;do=list', $this->lang->words['ss_manageskinsets'] );
		
		$this->registry->getClass('output')->html .= $this->html->skinsets_setForm( $form, $title, $formcode, $button, $skinSet );
	}

	/**
	 * List template sets
	 *
	 * @return	@e void
	 */
	protected function _listSets( $html='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$root_id   		 = 'root';
		$sets      		 = array();
		$cacheData 		 = array();
		$hasData   		 = array();
		$canMerge  		 = $this->skinFunctions->canUseMergeSystem();
		$skinGenSessions = array();
		
		//-----------------------------------------
		// See if we have any cached data
		//-----------------------------------------
	
		$this->DB->build( array( 'select' => 'cache_id, cache_set_id',
								 'from'   => 'skin_cache',
								 'where'  => "cache_type='phptemplate'" ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$cacheData[ $row['cache_set_id'] ]['db']++;
		}
		
		//-----------------------------------------
		// See if we have any customized data
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'template_set_id',
								 'from'   => 'skin_templates',
								 'group'  => 'template_set_id' ) );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( ! $this->skinFunctions->remapData['templates'][ $row['template_set_id'] ] )
			{
				$hasData[ $row['template_set_id'] ]['templates'] = 1;
			}
		}
		
		$this->DB->build( array( 'select' => 'css_set_id',
								 'from'   => 'skin_css',
								 'group'  => 'css_set_id' ) );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if ( ! $this->skinFunctions->remapData['css'][ $row['css_set_id'] ] )
			{
				$hasData[ $row['css_set_id'] ]['css'] = 1;
			}
		}
		
		/* Fetch skin gen sessions */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_generator_sessions') );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$skinGenSessions[ $row['sg_skin_set_id'] ] = $row;
		}
		
		//-----------------------------------------
		// Recurse through and gather data
		//-----------------------------------------

		if ( is_array( $this->skinFunctions->recursiveTiers->getData( $root_id ) ) and count( $this->skinFunctions->recursiveTiers->getData( $root_id ) ) )
		{
			foreach( $this->skinFunctions->recursiveTiers->getData( $root_id ) as $id => $set_data )
			{
				$sets[] = $this->_listSetsFormatData( $set_data );
				$sets = $this->_listSetsRecurse( $set_data['set_id'], $sets );
			}
		}
		
		//-----------------------------------------
		// Check through...
		//-----------------------------------------
		
		foreach( $sets as $setID => $setData )
		{
			if ( @is_dir( IPS_CACHE_PATH . 'cache/skin_cache/cacheid_'.$setID ) )
			{
				$cacheData[ $setID ]['php'] = 1;
			}
		}
		
		//-----------------------------------------
		// Is the skin generator supported?
		//-----------------------------------------
		
		$licenseData = $this->cache->getCache('licenseData');
		$skinGenIsSupported = (bool) $licenseData['skinGen'];
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->skinsets_listSkinSets( $sets, $cacheData, $hasData, $canMerge, $skinGenSessions, $html, $skinGenIsSupported );
	}
	
	/**
	 * Recursive list sets function
	 *
	 * @param	int			Root ID to drill down from
	 * @param	array 		Array of skin set entries
	 * @param	int			Depth gauge count
	 * @return	array 		Array of skin set entries
	 */
	protected function _listSetsRecurse($id, $sets, $depth=0)
	{
		if ( is_array( $this->skinFunctions->recursiveTiers->getData( $id ) ) and count( $this->skinFunctions->recursiveTiers->getData( $id ) ) )
		{
			$depth++;

			foreach( $this->skinFunctions->recursiveTiers->getData( $id ) as $idx => $set_data )
			{
				$sets[] = $this->_listSetsFormatData( $set_data, $depth );
				$sets = $this->_listSetsRecurse( $set_data['set_id'], $sets, $depth );
			}
		}

		return $sets;
	}
	
	/**
	 * Format the data
	 *
	 * @param	array 		Skin set entry
	 * @param	int			Depth gauge count
	 * @return	array 		Modified skin set entry
	 */
	protected function _listSetsFormatData( $set_data, $depth=0 )
	{ 
		//-----------------------------------------
		// Get last modified date
		//-----------------------------------------

		$set_data['_set_updated'] = gmstrftime( '%c', $set_data['set_updated'] );
		$set_data['_set_added']   = gmstrftime( '%c', $set_data['set_added'] );
		
		/* @see http://forums.invisionpower.com/tracker/issue-18012-warnings-in-acp-302/ */
		$this->skinFunctions->remapData['masterKeys']	= is_array($this->skinFunctions->remapData['masterKeys']) ? $this->skinFunctions->remapData['masterKeys'] : array();

		$set_data['_canRemove']   = ( ! $set_data['set_is_default'] AND ( ! in_array( $set_data['set_key'], $this->skinFunctions->remapData['masterKeys'] ) ) ) ? 1 : 0;
		
		$set_data['_setImg']   = ( $set_data['set_by_skin_gen'] ) ? 'palette.png' : ( ( $set_data['set_parent_id'] > 0 ) ? 'package.png' : 'folder_palette.png' );
		
		$set_data['_canWriteMaster']    = ( isset( $this->skinFunctions->remapData['templates'][ $set_data['set_id'] ] ) );
		$set_data['_canWriteMasterCss'] = ( isset( $this->skinFunctions->remapData['css'][ $set_data['set_id'] ] ) );
		
		//-----------------------------------------
		// Set Depth
		//-----------------------------------------

		$set_data['depthguide'] = '';

		for( $i = 1 ; $i < $depth; $i++ )
		{
			$set_data['depthguide'] .= $this->_depthGuide[ $i ];
			$set_data['cssDepthGuide']++;
		}

		//-----------------------------------------
		// Last child?
		//-----------------------------------------

		if ( $depth > 0 )
		{
			$this->_depthGuide[ $depth ]  = "<img src='{$this->settings['skin_acp_url']}/images/icon_components/generic_trees/depth-guide.gif' style='vertical-align:middle' />";
			$set_data['depthguide'] .= "<img src='{$this->settings['skin_acp_url']}/images/icon_components/generic_trees/depth-guide.gif' style='vertical-align:middle' />";
			$set_data['cssDepthGuide']++;
		}

		return $set_data;
	}	
}