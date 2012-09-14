<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Login handler abstraction : AJAX Find Names functions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 * @see			admin_core_ajax_findnames
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_findnames extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	switch( $this->request['do'] )
    	{
			case 'get-member-names':
    			$this->_getMemberNames();
    		break;
    	}
	}
	
	/**
	 * Returns possible matches for the string input
	 *
	 * @return	@e void		Outputs to screen
	 */
	protected function _getMemberNames()
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

    	$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['name'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );

    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------

    	if ( IPSText::mbstrlen( $name ) < 3 )
    	{
    		$this->returnJsonError( 'requestTooShort' );
    	}

    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------

    	$this->DB->build( array( 'select'	=> 'm.members_display_name, m.name, m.member_id, m.member_group_id',
    							 'from'	    => array( 'members' => 'm' ),
    							 'where'	=> "m.members_l_display_name LIKE '" . $this->DB->addSlashes( strtolower( $name ) ) . "%'",
    							 'order'	=> $this->DB->buildLength( 'm.members_display_name' ) . ' ASC',
    							 'limit'	=> array( 0, 15 ),
 								 'add_join' => array( array( 'select' => 'p.*',
														     'from'   => array( 'profile_portal' => 'p' ),
														     'where'  => 'p.pp_member_id=m.member_id',
														     'type'   => 'left' ) ) ) );
		$this->DB->execute();

    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------

    	if ( ! $this->DB->getTotalRows() )
 		{
    		$this->returnJsonArray( array( ) );
    	}

    	$return = array();

		while( $r = $this->DB->fetch() )
		{
			$photo = IPSMember::buildProfilePhoto( $r );
			$group = IPSMember::makeNameFormatted( '' , $r['member_group_id'] );
			$return[ $r['member_id'] ] = array( 'name' 	=> $r['members_display_name'],
												'showas'=> '<strong>' . $r['members_display_name'] . '</strong> (' . $group . ')',
												'img'	=> $photo['pp_mini_photo'],
												'img_w'	=> $photo['pp_mini_width'],
												'img_h'	=> $photo['pp_mini_height']
											);
		}

		$this->returnJsonArray( $return );
	}
}