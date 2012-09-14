<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile display name history
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_dname extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$info = array();
 		$id   = intval( $this->request['id'] );
 				
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
			$this->returnJsonError( $this->lang->words['dname_profiles_off'] );
		}
		
 		if ( ! $this->settings['auth_allow_dnames'] )
 		{
			$this->returnJsonError( $this->lang->words['dnames_off'] );
 		}
		
		if( !$id )
		{
			$this->returnJsonError( $this->lang->words['dnames_no_id'] );
		}

    	$member	= IPSMember::load( $id );
    	
    	//-----------------------------------------
    	// Get Dname history
    	//-----------------------------------------
 		
 		$this->DB->build( array( 'select'		=> 'd.*',
										'from'		=> array( 'dnames_change' => 'd' ),
										'where'		=> 'dname_member_id='.$id,
										'add_join'	=> array( 0 => array(	'select'	=> 'm.members_display_name',
																	  		'from'		=> array( 'members' => 'm' ),
																	  		'where'		=> 'm.member_id=d.dname_member_id',
																	  		'type'		=> 'inner' ) ),
										'order'		=> 'dname_date DESC'
								) 		);
 		$this->DB->execute();
    	
    	while( $row = $this->DB->fetch() )
    	{
    		$records[] = $row;
    	}

    	//-----------------------------------------
    	// Print the pop-up window
    	//-----------------------------------------
    	
    	$html = $this->registry->getClass('output')->getTemplate('profile')->dnameWrapper( $member['members_display_name'], $records );

		//-----------------------------------------
		// Push to print handler
		//-----------------------------------------
		
		$this->returnHtml( $html );
	}
}