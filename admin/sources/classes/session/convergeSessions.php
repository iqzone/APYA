<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * This class overrides parent publicSessions to prevent construct being called
 *	It doesn't do anything else...
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	IP.Converge
 * @link		http://www.invisionpower.com
 * @since		22nd May 2008 11:15 AM
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class convergeSessions extends publicSessions
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function __construct( $registry )
	{
		/* Make object */
		$this->registry		= ipsRegistry::instance();
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->_member		=  self::instance();
		$this->_memberData	=& self::instance()->fetchMemberData();
		
		$this->_userAgent = substr( $this->_member->user_agent, 0, 200 );
		
		//-----------------------------------------
		// Fix up app / section / module
		//-----------------------------------------
		
		$this->current_appcomponent	= IPS_APP_COMPONENT;
		$this->current_module		= IPSText::alphanumericalClean( $this->request['module'] );
		$this->current_section		= IPSText::alphanumericalClean( $this->request['section'] );
		
		$this->settings['session_expiration'] = ( $this->settings['session_expiration'] ) ? $this->settings['session_expiration'] : 60;
	}

	/**
	 * Create a member session
	 *
	 * @access	public
	 * @return	string		Session id
	 */	
	public function createMemberSession()
	{
		parent::_createMemberSession();
		
		return $this->session_data['id'];
	}
}