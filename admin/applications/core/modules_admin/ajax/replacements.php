<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX Functions For applications/core/js/ipb3CSS.js file
 * Last Updated: $Date: 2012-05-24 16:20:59 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * Author: Matt Mecham
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10792 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_replacements extends ipsAjaxCommand 
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
			case 'saveReplacement':
				/* Check... */
				if ( !$registry->getClass('class_permissions')->checkPermission( 'replacements_manage', ipsRegistry::$current_application, 'templates' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
					exit();
				}
				$this->_saveReplacement();
			break;
			case 'revertReplacement':
				/* Check... */
				if ( !$registry->getClass('class_permissions')->checkPermission( 'replacements_delete', ipsRegistry::$current_application, 'templates' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
					exit();
				}
				$this->_revertReplacement();
			break;
			
			case 'retrieve':
				/* Check... */
				if ( !$registry->getClass('class_permissions')->checkPermission( 'easy_logo', ipsRegistry::$current_application, 'templates' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
					exit();
				}
				$this->getEasyLogo();
			break;
		}
	}
	
	/**
	 * Get logo image replacement for a skin
	 *
	 * @return	@e void
	 */
	public function getEasyLogo()
	{
		$setID			= intval( $this->request['value'] );
		
		$replacements	= $this->skinFunctions->fetchReplacements( $setID );
		$currentUrl		= $replacements['logo_img']['replacement_content'];
		$currentId		= $replacements['logo_img']['replacement_id'];
		
		$this->returnJsonArray( array( 'url' => $currentUrl, 'id' => $currentId ) );
	}
	
	/**
	 * Reverts replacement
	 *
	 * @return	string		Json
	 */
	protected function _revertReplacement()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID		 = intval( $this->request['setID'] );
		$replacementID = intval( $this->request['replacement_id'] );

		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ! $replacementID  )
		{ 
			$this->returnJsonError('Missing Data');
			exit();
		}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$replacements = $this->skinFunctions->revertReplacement( $replacementID, $setID );
		
		$this->returnJsonArray( array( 'replacements' => $replacements, 'errors' => $this->skinFunctions->fetchErrorMessages() ) );
	}

	/**
	 * Saves the CSS
	 *
	 * @return	string		Json
	 */
	protected function _saveReplacement()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID			   = intval( $this->request['setID'] );
		$replacementID	   = intval( $this->request['replacement_id'] );
		$type				= ( $this->request['type'] == 'add' ) ? 'add' : 'edit';
		$replacement_content = $this->convertUnicode( $_POST['replacement_content'] );
		$replacement_key	 = $this->convertUnicode( $_POST['_replacement_key'] );
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ( $type == 'edit' AND ! $replacementID ) )
		{ 
			$this->returnJsonError('Missing Data');
			exit();
		}

		//-----------------------------------------
		// Add checks
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			if ( ! $replacement_key )
			{
				$this->returnJsonError('Missing Data');
				exit();
			}
		}
		
		//-----------------------------------------
		// Save it
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			$css_id = $this->skinFunctions->saveReplacementFromEdit( $replacementID, $setID, $replacement_content, $replacement_key );
		}
		else
		{
			try
			{
				$css_id = $this->skinFunctions->saveReplacementFromAdd( $setID, $replacement_content, $replacement_key );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $err->getMessage() );
				exit();
			}
		}
		
		//-----------------------------------------
		// Get Data
		//-----------------------------------------
		
		$replacements = $this->skinFunctions->fetchReplacements( $setID );
		
		$this->returnJsonArray( array( 'replacements' => $replacements, 'errors' => $this->skinFunctions->fetchErrorMessages() ) );
	}
}