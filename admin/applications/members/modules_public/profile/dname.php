<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile View
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_dname extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
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
 			$this->registry->output->showError( 'dname_profiles_off', 10233.22, null, null, 403 );
		}
		
 		if ( ! $this->settings['auth_allow_dnames'] )
 		{
 			$this->registry->output->showError( 'dnames_off', 10234, null, null, 403 );
 		}
		
		if( !$id )
		{
			$this->registry->output->showError( 'dnames_no_id', 10235, null, null, 404 );
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
    	
    	/* Stop Google indexing soft 404s */
    	if ( ! count( $records ) && ! $this->memberData['member_id'] )
    	{
    		$this->registry->output->showError( '404_soft', 10235.1, null, null, 404 );
    	}

    	//-----------------------------------------
    	// Print the pop-up window
    	//-----------------------------------------
    	
    	$html = $this->registry->getClass('output')->getTemplate('profile')->dnameWrapper( $member['members_display_name'], $records );

		//-----------------------------------------
		// Push to print handler
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $this->lang->words['dname_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->getClass('output')->addNavigation( $this->lang->words['dname_title'], '' );
		$this->registry->getClass('output')->addContent( $html );
		$this->registry->output->sendOutput();
 	}
 	
}