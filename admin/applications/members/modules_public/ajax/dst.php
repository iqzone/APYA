<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member AJAX DST switcher
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

class public_members_ajax_dst extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		if( !$this->memberData['member_id'] )
		{
			$this->returnNull();
		}
		
		if( $this->memberData['members_auto_dst'] == 1 AND $this->settings['time_dst_auto_correction'] )
		{
			$newValue	= $this->memberData['dst_in_use'] ? 0 : 1;
			
			IPSMember::save( $this->memberData['member_id'], array( 'members' => array( 'dst_in_use' => $newValue ) ) );
		}

		$this->returnNull();
	}
}