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
		
		//* Make sure tables exist that won't in pre 3.0 versions */
		if ( is_file( IPS_ROOT_PATH . 'setup/sql/ipb3_' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '.php' ) )
		{
			/* Init vars */
			$UPGRADE_HISTORY_TABLE		= '';
			$UPGRADE_TABLE_FIELD		= '';
			$UPGRADE_SESSION_TABLE		= '';
			$UPGRADE_CSS_PREVIOUS		= '';
			$UPGRADE_TEMPLATE_PREVIOUS	= '';
			
			require( IPS_ROOT_PATH . 'setup/sql/ipb3_' . strtolower( ipsRegistry::$settings['sql_driver'] ) . '.php' );/*noLibHook*/
			
			$prefix = $this->registry->dbFunctions()->getPrefix();
			
			if ( ! $this->DB->checkForTable( 'upgrade_history' ) )
			{
				if ( $UPGRADE_HISTORY_TABLE )
				{
					$this->DB->query( IPSSetUp::addPrefixToQuery( $UPGRADE_HISTORY_TABLE, $prefix ) );
				}
			}
			
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
		if ( ! $this->_member['id'] AND ! $this->_member['member_id'] )
		{
			throw new Exception( "MEMBER NOT SET" );
		}
		else
		{
			return ( $this->_member['password'] ) ? md5( $this->_member['password'] ) : md5( $this->_member['legacy_password'] );
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
								 'where'    => 'm.id=' . intval( $memberId ),
								 'add_join' => array( array( 'select' => 'g.*',
															 'from'	  => array( 'groups' => 'g' ),
															 'where'  => 'g.g_id=m.mgroup' ) ) ) );
		
		$this->DB->execute();
		
		$this->_member = $this->DB->fetch();
		
		/* Fix up pre-3 stuffs */
		$this->_member['member_id']       = $this->_member['id'];
		$this->_member['member_group_id'] = $this->_member['mgroup'];
		
		/* Occasionally 1.x installs do not have an email address. Most likey an InvisionFree issue */
		if ( ! $this->_member['email'] )
		{
			$this->DB->update( 'members', array( 'email' => 'email_' . $this->_member['id'] . '_@changeMe.com' ), 'id=' . $memberId );
		}
		
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
		/* Attempt to load member */
		$this->DB->build( array( 'select'   => 'm.*',
								 'from'     => array( 'members' => 'm' ),
								 'where'    => $this->DB->buildLower('m.name') . "='" . strtolower( $username ) . "'",
								 'add_join' => array( array( 'select' => 'g.*',
															 'from'	  => array( 'groups' => 'g' ),
															 'where'  => 'g.g_id=m.mgroup' ) ) ) );
															
		
		$this->DB->execute();
	
		$mem = $this->DB->fetch();
		$pass = md5( $password );
		
		/* Check it out */
		if ( ! $mem['id'] )
		{
			return 'No user found by that username';
		}
		
		if ( $pass != $mem['password'] AND $pass != $mem['legacy_password'] )
		{
			return 'Password incorrect';
		}

		if ( $mem['g_access_cp'] != 1 )
		{
			return 'You do not have access to the upgrade system';
		}
		
		/* Set up _member */
		$this->loadMemberData( $mem['id'] );
		
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
		return $this->registry->output->template()->upgrade_login_200plus( 'username' );
	}
}