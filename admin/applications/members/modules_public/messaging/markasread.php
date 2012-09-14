<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Mark a private message as read
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Brandon Farber
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		Thurs 7th Jan 2010
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_messaging_markasread extends ipsCommand
{
	/**
	 * Main execution point
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$_ajax	= intval($this->request['ajax']);
		$topic	= intval($this->request['topicID']);
		
		if( !$topic )
		{
			$this->endReturn( $_ajax );
		}
		
		$_className	= IPSLib::loadLibrary( IPSLib::getAppDir('members') . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );

		$_messenger	= new $_className( $this->registry );
		$_messenger->toggleReadStatus( $this->memberData['member_id'], array( $topic ), true );
		
		$this->endReturn( $_ajax );
	}
	
	/**
	 * All done, return
	 *
	 * @param	int		AJAX result
	 * @return	@e void
	 */
 	public function endReturn( $_ajax )
 	{
		if( !$_ajax )
		{
			$_returnTo	= $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : $this->settings['base_url'];
			
			$this->registry->output->silentRedirect( $_returnTo );
		}
		else
		{
			print 'ok';
		}
		
		exit;
	}
}
