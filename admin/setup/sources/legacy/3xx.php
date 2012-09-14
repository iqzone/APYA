<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Version Specific Upgrade Functions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		1st December 2008
 * @version		$Revision: 10721 $
 *
 */

class upgradeLegacy
{
	/**
	 * Member data
	 *
	 * @access	private
	 * @var		array
	 */
	private $_member;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	@e void
	 */
    public function __construct( ipsRegistry $registry )
    {
		/* Make object */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Make sure tables exist that won't in pre 3.0 versions */
		if ( is_file( IPS_ROOT_PATH . 'setup/sql/ipb3_' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '.php' ) )
		{
			/* Init vars */
			$UPGRADE_TABLE_FIELD		= '';
			$UPGRADE_SESSION_TABLE		= '';
			$UPGRADE_CSS_PREVIOUS		= '';
			$UPGRADE_TEMPLATE_PREVIOUS	= '';
			
			require( IPS_ROOT_PATH . 'setup/sql/ipb3_' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '.php' );/*noLibHook*/
			
			$prefix = $this->registry->dbFunctions()->getPrefix();
			
			if ( ! $this->DB->checkForField( 'upgrade_app', 'upgrade_history' ) )
			{
				if ( $UPGRADE_TABLE_FIELD )
				{
					$this->DB->query( IPSSetUp::addPrefixToQuery( $UPGRADE_TABLE_FIELD, $prefix ) );
				}
			}
			
			if ( ! $this->DB->checkForTable( 'upgrade_sessions' ) )
			{
				if ( $UPGRADE_SESSION_TABLE )
				{
					$this->DB->query( IPSSetUp::addPrefixToQuery( $UPGRADE_SESSION_TABLE, $prefix ) );
				}
			}
			
			if ( ! $this->DB->checkForTable( 'skin_css_previous' ) )
			{
				if ( $UPGRADE_CSS_PREVIOUS )
				{
					$this->DB->query( IPSSetUp::addPrefixToQuery( $UPGRADE_CSS_PREVIOUS, $prefix ) );
				}
			}
			
			if ( ! $this->DB->checkForTable( 'skin_templates_previous' ) )
			{
				if ( $UPGRADE_TEMPLATE_PREVIOUS )
				{
					$this->DB->query( IPSSetUp::addPrefixToQuery( $UPGRADE_TEMPLATE_PREVIOUS, $prefix ) );
				}
			}
		}
    }

	/**
	 * Fetch auth key
	 *
	 * @access	public
	 * @return	string
	 */
	public function fetchAuthKey()
	{
		if ( ! $this->_member['member_id'] )
		{
			throw new Exception( "MEMBER NOT SET" );
		}
		else
		{
			return $this->_member['member_login_key'];
		}
	}
	
	/**
	 * Fetch member data
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchMemberData()
	{
		return ( is_array( $this->_member ) ) ? $this->_member : array();
	}
	
	/**
	 * Load and return member data
	 *
	 * @access	public
	 * @param	int		Member ID to load
	 * @return	array
	 */
	public function loadMemberData( $memberId )
	{
		/* Attempt to load member */
		$this->DB->build( array( 'select'   => 'm.*',
								 'from'     => array( 'members' => 'm' ),
								 'where'    => 'm.member_id=' . intval( $memberId ),
								 'add_join' => array( array( 'select' => 'g.*',
															 'from'	  => array( 'groups' => 'g' ),
															 'where'  => 'g.g_id=m.member_group_id' ) ) ) );
		
		$this->DB->execute();
		
		/* Set up seconday groups */
		$this->_member = ips_MemberRegistry::setUpSecondaryGroups( $this->DB->fetch() );
		
		return $this->fetchMemberData();
	}
	
	/**
	 * Authenticate log in
	 *
	 * @access	public
	 * @param	string		Username (from $this->request)
	 * @param	string		Password (from $this->request)
	 * @return	mixed		TRUE if successful, string (message) if not
	 */
	public function authenticateLogIn( $username, $password )
	{
		require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );/*noLibHook*/
    	$han_login 				  = new han_login( $this->registry );
    	$han_login->is_admin_auth = 1;
    	$han_login->init();
		
		$email = '';
		
		/* Is this a username or email address? */
		if ( IPSText::checkEmailAddress( $username ) )
		{
			$email		= $username;
			$username   = '';
		}
	
		$han_login->loginAuthenticate( $username, $email, $password );

		$mem = $han_login->member_data;

		if ( ( ! $mem['member_id'] ) or ( $han_login->return_code == 'NO_USER' ) )
		{
			return 'No user found by that username';
		}

		if ( $han_login->return_code == 'NO_ACCESS' )
		{
			return 'You do not have access to the upgrade system';
		}
		else if ( $han_login->return_code != 'SUCCESS' )
		{
			return 'Password or username incorrect';
		}

		/* Test seconday groups */
		$mem = ipsRegistry::member()->setUpSecondaryGroups( $mem );

		if ( $mem['g_access_cp'] != 1 )
		{
			return 'You do not have access to the upgrade system';
		}
		
		/* Set up _member */
		$this->loadMemberData( $mem['member_id'] );
		
		/* Still here? */
		return TRUE;
	}
	
	/**
	 * Return log in form HTML
	 *
	 * @access	public
	 * @return	string		HTML
	 */
	public function fetchLogInForm()
	{
		require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );/*noLibHook*/
    	$han_login 				  = new han_login( $this->registry );
    	$han_login->is_admin_auth = 1;
    	$han_login->init();

		$additional_data	= ''; //$han_login->additionalFormHTML();
		$replace			= false;
		$data				= array();
		
		if( !is_null($additional_data) AND is_array($additional_data) AND count($additional_data) )
		{
			$replace 	= $additional_data[0];
			$data		= $additional_data[1];
		}
		
		/* Check login type */
		$uses_name  = false;
		$uses_email = false;
		$loginType  = 'username';
		
		foreach( $this->cache->getCache('login_methods') as $method )
		{
			if ( $method['login_user_id'] == 'username' or $method['login_user_id'] == 'either' )
			{
				$uses_name	= true;
			}
			
			if ( $method['login_user_id'] == 'email' or $method['login_user_id'] == 'either' )
			{
				$uses_email	= true;
			}
		}
	
		if ( $uses_name AND $uses_email )
		{
			$loginType = 'either';
		}
		elseif ( $uses_email )
		{
			$loginType = 'email';
		}
		
		return $this->registry->output->template()->upgrade_login_300plus( $data, ( $replace == 'replace' ? true : false ), $loginType );
	}
}