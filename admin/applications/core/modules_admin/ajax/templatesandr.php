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

class admin_core_ajax_templatesandr extends ipsAjaxCommand 
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
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinDifferences( $registry );
		
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
			default:
			case 'getTemplateBitList':
				$this->_getTemplateBitList();
			break;
			case 'replace':
				$this->_replace();
			break;
		}
	}
	
	/**
	 * Fetch a JSON list of template bits for the template group
	 *
	 * @return	@e void
	 */
	protected function _replace()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$finalIDs 	 = array();
		$templates   = array();
		$sessionID	 = IPSText::md5Clean( $this->request['sessionID'] );

		$sessionData = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'template_sandr',
														'where'  => "sandr_session_id='" . addslashes( $sessionID ) ."'" ) );
		
		$templateData = unserialize( $sessionData['sandr_results'] );
		
		if ( is_array( $templateData ) )
		{
			foreach( $templateData as $_group => $_data )
			{
				if ( !empty( $_POST['groups'][ $_group ] ) )
				{
					foreach( $_data as $_name => $_id )
					{
						$finalIDs[] = $_id;
					}
				}
				else
				{
					foreach( $_data as $_name => $_id )
					{
						if ( !empty( $_POST['templates'][ $_id ] ) )
						{
							$finalIDs[] = $_id;
						}
					}
				}
			}
		}
		
		/* Check... */
		if ( ! count( $finalIDs ) )
		{
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}
		
		/* Load templates */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_templates',
								 'where'  => 'template_id IN ('. implode( ',', $finalIDs ) . ')' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$templates[ $row['template_id'] ] = $row;
		}
		
		/* Perform the replacement */
		foreach( $templates as $template_id => $template )
		{
			if ( $sessionData['sandr_is_regex'] )
			{
				$before = str_replace( '#', '\#', IPSText::stripslashes( $sessionData['sandr_search_for'] ) );
				$after  = preg_replace( '#\\\\\\\\(\d+?)#i', '$\\1', $sessionData['sandr_replace_with'] );
				
				$template['template_content'] = preg_replace( "#{$before}#si", $after, $template['template_content'] );
			
			}
			else
			{
				$template['template_content'] = str_ireplace( $sessionData['sandr_search_for'], $sessionData['sandr_replace_with'], $template['template_content'] );
			}
			
			/* Save it */
			$this->skinFunctions->saveTemplateBitFromEdit( $template['template_id'], $sessionData['sandr_set_id'], $template['template_content'], $template['template_data'] );
		}
		
		/* Done */
		$this->returnJsonArray( array( 'status' => 'ok' ) );
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
		$sessionID	   = IPSText::md5Clean( $this->request['sessionID'] );
		$templates	   = array();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $setID OR ! $templateGroup or ! $sessionID )
		{ 
			$this->returnJsonError( $this->lang->words['ajax_missing_data'] );
		}

		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$sessionData = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'template_sandr',
														'where'  => "sandr_session_id='" . addslashes( $sessionID ) ."'" ) );
		
		$templateIDs = unserialize( $sessionData['sandr_results'] );
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_templates',
								 'where'  => 'template_id IN ('. implode( ',', array_values( $templateIDs[ $templateGroup] ) ) . ')' ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			unset( $row['template_content'] );
			$templates[] = $row;
		}
		
		$this->returnJsonArray( array( 'templates' => $templates ) );
	}
}