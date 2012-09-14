<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Notification AJAX methods
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
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_notifications extends ipsAjaxCommand 
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
			case 'getlatest':
			default:
				$this->getMyNotifications();
			break;
			
			case 'getNextNotification':
				$this->getMoreNotifications( 'DESC' );
			break;
			
			case 'getLastNotification':
				$this->getMoreNotifications( 'ASC' );
			break;
		}
	}

	/**
	 * Retrieve next notification for user
	 *
	 * @param	string		Database order (asc/desc)
	 * @return	@e void		[Outputs JSON to browser AJAX call]
	 */
	protected function getMoreNotifications( $dir='DESC' )
	{
		//-----------------------------------------
		// We logged in?
		//-----------------------------------------
		
		if( !$this->memberData['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['notifylogin_error'] );
		}
		
		//-----------------------------------------
		// Retrieve notifications
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		$_data = $notifyLibrary->fetchUnreadNotifications( 50, 'notify_sent', $dir );
		
		//-----------------------------------------
		// Now we loop through and set has previous, 
		// has more, and get "next" notification
		//-----------------------------------------
		
		$_hasPrevious	= false;
		$_hasNext		= false;
		$_hitId			= false;
		$_gotNext		= false;
		$_thisNotify	= array();
		
		foreach( $_data as $k => $v )
		{
			if( !$_hitId )
			{
				if( $v['notify_id'] == $this->request['last'] )
				{
					$_hitId	= true;
				}
				
				$_hasPrevious	= true;
				continue;
			}
			
			if( !$_gotNext )
			{
				$_gotNext		= true;
				$_thisNotify	= $v;
				continue;
			}
			
			$_hasNext	= true;
			break;
		}
		
		if( count($_thisNotify) )
		{
			$_thisNotify['notify_date_formatted']		= $this->registry->class_localization->getDate( $_thisNotify['notify_sent'], 'short' );
			
			$_thisNotify['has_more']		= $_hasNext;
			$_thisNotify['has_previous']	= $_hasPrevious;
		}
		else
		{
			$_thisNotify['error']	= $this->lang->words['nomore_notifications_4u'];
		}
		
		if( !$_thisNotify['notify_url'] )
		{
			$_thisNotify['notify_url']	= $this->registry->output->buildSEOUrl( "app=core&module=usercp&area=viewNotification&do=view&view={$_thisNotify['notify_id']}", 'public' );
		}
		
		$this->returnJsonArray( $_thisNotify );
	}
	
	/**
	 * Retrieve user's last x notifications
	 *
	 * @return	@e void		[Outputs JSON to browser AJAX call]
	 */
	protected function getMyNotifications()
	{
		//-----------------------------------------
		// We logged in?
		//-----------------------------------------
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['notifylogin_error'] );
		}
		
		//-----------------------------------------
		// Retrieve notifications
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		$notifyLibrary->setMember( $this->memberData );

		$_data = $notifyLibrary->fetchLatestNotifications( 5 );

		//-----------------------------------------
		// Now mark them all read
		//-----------------------------------------
		
		$notifyLibrary->markNotificationsAsReadByMemberId( $this->memberData['member_id'] );
		
		//-----------------------------------------
		// Return results
		//-----------------------------------------

    	$this->returnJsonArray( array( 'html' => $this->cleanOutput( $this->registry->output->getTemplate('global_other')->notificationsList( $_data ) ) ) );
	}
}