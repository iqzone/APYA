<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX Functions For applications/core/js/ipb3Templates.js file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * Author: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_templates extends ipsAjaxCommand 
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
		
		/* Check... */
		if ( !$registry->getClass('class_permissions')->checkPermission( 'templates_manage', ipsRegistry::$current_application, 'templates' ) )
		{
			$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
		}
						
		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'getTemplateBitList':
				$this->_getTemplateBitList();
			break;
			case 'getTemplateGroupList':
				$this->_getTemplateGroupList();
			break;
			case 'getTemplateForEdit':
				$this->_getTemplateForEdit();
			break;
			case 'saveTemplateBit':
				$this->_saveTemplateBit();
			break;
			case 'revertTemplateBit':
				/* Check... */
				if ( !$registry->getClass('class_permissions')->checkPermission( 'templates_delete', ipsRegistry::$current_application, 'templates' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
					exit();
				}
				$this->_revertTemplateBit();
			break;
		}
	}
	
	/**
	 * Reverts a template bit
	 *
	 * @return	@e void
	 */
	protected function _revertTemplateBit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID			= intval( $this->request['setID'] );
		$templateID	   = intval( $this->request['template_id'] );

		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ! $templateID  )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$template = $this->skinFunctions->revertTemplateBit( $templateID, $setID );
		
		$this->returnJsonArray( array( 'templateData' => $template, 'errors' => $this->skinFunctions->fetchErrorMessages()  ) );
	}

	/**
	 * Saves the template bit
	 *
	 * @return	@e void
	 */
	protected function _saveTemplateBit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$test				= $_POST['_template_name'];
		$setID			  = intval( $this->request['template_set'] );
		$templateID		 = intval( $this->request['template_id'] );
		$type			   = ( $this->request['type'] == 'add' ) ? 'add' : 'edit';
		$template_content   = $_POST['template_content'];
		$template_group	 = IPSText::alphanumericalClean( $_POST['template_group'] );
		$ent_template_group = str_replace( "skin_", "", IPSText::alphanumericalClean( $_POST['_template_group'] ) );
		$template_name	  = IPSText::alphanumericalClean( $_POST['_template_name'] );
		$template_data		= $_POST['template_data'];

		IPSDebug::fireBug( 'info', array( 'Template content: ' . $template_content ) );
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ( $type == 'edit' AND ! $templateID ) )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}

		//-----------------------------------------
		// Add checks
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			if ( ! $template_name )
			{
				$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
			}
		}
		
		//-----------------------------------------
		// Save it
		//-----------------------------------------

		if ( $type == 'edit' )
		{
			try
			{
				$template_id = $this->skinFunctions->saveTemplateBitFromEdit( $templateID, $setID, $template_content, $template_data );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $this->lang->words[ 'templates_' . $err->getMessage() ] ? $this->lang->words[ 'templates_' . $err->getMessage() ] : $err->getMessage() );//. ' ' . implode( "\n", $this->skinFunctions->fetchMessages() ) );
			}
		}
		else
		{
			$template_group = ( $ent_template_group ) ? 'skin_' . $ent_template_group : $template_group;
			
			try
			{
				$template_id	= $this->skinFunctions->saveTemplateBitFromAdd( $setID, $template_content, $template_data, $template_group, $template_name );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $this->lang->words[ 'templates_' . $err->getMessage() ] ? $this->lang->words[ 'templates_' . $err->getMessage() ] : $err->getMessage() );// . ' ' . implode( "\n", $this->skinFunctions->fetchMessages() ) );
			}
		}
		
		//-----------------------------------------
		// Fetch new data and return
		//-----------------------------------------
		
		$template = $this->skinFunctions->fetchTemplateBitForEdit( $template_id, $setID );
		
		//-----------------------------------------
		// Get Data
		//-----------------------------------------
				
		$this->returnJsonArray( array( 'templateData' => $template, 'errors' => $this->skinFunctions->fetchErrorMessages() ) );
	}

	/**
	 * Fetch a JSON list of template data ready for editing
	 *
	 * @return	@e void
	 */
	protected function _getTemplateForEdit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID	  = intval( $this->request['setID'] );
		$templateID = intval( $this->request['template_id'] );
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ! $templateID  )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$template = $this->skinFunctions->fetchTemplateBitForEdit( $templateID, $setID );
		
		$this->returnJsonArray( array( 'templateData' => $template ) );
	}
	
	/**
	 * Fetch a JSON list of template groups
	 *
	 * @return	@e void
	 */
	protected function _getTemplateGroupList()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID		 = intval( $this->request['setID'] );
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}

		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$templateGroups = $this->skinFunctions->fetchTemplates( $setID, 'groupNames' );
		
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
		
		$this->returnJsonArray( array( 'groups' => $templateGroups ) );
	}

	/**
	 * Fetch a JSON list of template bits for the template group
	 *
	 * @return	@e void
	 */
	protected function _getTemplateBitList()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$setID		 = intval( $this->request['setID'] );
		$templateGroup = IPSText::alphanumericalClean( $this->request['templateGroup'] );
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ! $templateGroup  )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}

		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$templates = $this->skinFunctions->fetchTemplates( $setID, 'groupTemplatesNoContent', $templateGroup );
	
		$this->returnJsonArray( array( 'templates' => array_values( $templates ),
									   'groupData' => array( '_modCount' => $this->skinFunctions->fetchModifiedTemplateCount( $setID, $templateGroup ) ) ) );
	}
}