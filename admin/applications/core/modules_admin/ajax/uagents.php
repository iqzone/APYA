<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX Functions For applications/core/js/ipb3CSS.js file
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

class admin_core_ajax_uagents extends ipsAjaxCommand 
{
	/**
	 * User agent functions
	 *
	 * @var		object			Skin templates
	 */
	protected $userAgentFunctions;
	
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
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php', 'userAgentFunctions' );
		$this->userAgentFunctions = new $classToLoad( $registry );
		
		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'saveuAgent':
				if ( !$registry->getClass('class_permissions')->checkPermission( 'ua_manage', ipsRegistry::$current_application, 'tools' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
				}
				$this->_saveuAgent();
			break;
			case 'removeuAgent':
				if ( !$registry->getClass('class_permissions')->checkPermission( 'ua_remove', ipsRegistry::$current_application, 'tools' ) )
				{
					$this->returnJsonError( $registry->getClass('class_localization')->words['sk_ajax_noperm'] );
				}
				$this->_removeuAgent();
			break;
		}
	}
	
	/**
	 * Reverts replacement
	 *
	 * @return	@e void
	 */
	protected function _removeuAgent()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$uagent_id				= intval( $this->request['uagent_id'] );

		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $uagent_id )
		{ 
			$this->returnJsonError('Missing Data');
		}

		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$userAgents = $this->userAgentFunctions->removeUserAgent( $uagent_id );
		
		$this->returnJsonArray( array( 'uagents' => $userAgents, 'errors' => $this->userAgentFunctions->fetchErrorMessages() ) );
	}

	/**
	 * Saves the user agent
	 *
	 * @return	@e void
	 */
	protected function _saveuAgent()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$uagent_id				= intval( $this->request['uagent_id'] );
		$uagent_key				= IPSText::alphanumericalClean( $this->request['uagent_key'] );
		$uagent_name			= $this->convertAndMakeSafe( $_POST['uagent_name'] );
		$uagent_regex			= $this->convertUnicode( $_POST['uagent_regex'] );
		$uagent_regex_capture	= intval( $this->request['uagent_regex_capture'] );
		$uagent_type			= IPSText::alphanumericalClean( $this->request['uagent_type'] );
		$uagent_position		= intval( $this->request['uagent_position'] );
		$type					= $this->request['type'];
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( $type == 'edit' AND ! $uagent_id )
		{ 
			$this->returnJsonError('Missing Data');
		}

		//-----------------------------------------
		// Other checks
		//-----------------------------------------
		
		if ( ! $uagent_key OR ! $uagent_name OR ! $uagent_regex OR ! $uagent_type )
		{
			$this->returnJsonError('Missing Data');
		}
		
		//-----------------------------------------
		// Save it
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			try
			{
				$userAgentID = $this->userAgentFunctions->saveUserAgentFromEdit( $uagent_id, $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $err->getMessage() . ' ' . str_replace( "\n", "\\n", implode( ",", $this->userAgentFunctions->fetchMessages() ) ) );
			}
		}
		else
		{
			try
			{
				$userAgentID = $this->userAgentFunctions->saveUserAgentFromAdd( $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position );
			}
			catch( Exception $err )
			{
				$this->returnJsonError( $err->getMessage() );
			}
		}
		
		//-----------------------------------------
		// Get Data
		//-----------------------------------------

		$this->returnJsonArray( array( 'uagents' => $this->userAgentFunctions->fetchAgents(), 'returnid' => $userAgentID, 'errors' => $this->userAgentFunctions->fetchErrorMessages() ) );
	}
}