<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member property updater (AJAX)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_forums_ajax_member_editform extends ipsAjaxCommand 
{
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
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_member_form');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums', 'admin_member_form' ) );
		
    	switch( $this->request['do'] )
    	{
			case 'show':
			default:
				$this->show();
			break;
    	}
	}

	/**
	* Show the form
	*
	* @return	@e void		[Outputs to screen]
	*/
	protected function show()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name      = trim( IPSText::alphanumericalClean( ipsRegistry::$request['name'] ) );
		$member_id = intval( ipsRegistry::$request['member_id'] );
		$output    = '';
		
		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'extendedProfile,customFields' );
		
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['t_noid'] );
		}
		
		//-----------------------------------------
		// Return the form
		//-----------------------------------------
		
		if ( method_exists( $this->html, $name ) )
		{
			$output = $this->html->$name( $member );
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->returnHtml( $output );
	}
}