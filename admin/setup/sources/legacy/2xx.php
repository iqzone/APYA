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
		if ( ! $this->_member['id'] AND ! $this->_member['member_id'] )
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
		/* At this point, we could be either using 2.3 or 3.0 */
		if ( $this->DB->checkForField( 'member_id', 'members' ) )
		{
			/* Attempt to load member */
			$this->DB->build( array( 'select'   => 'm.*',
									 'from'     => array( 'members' => 'm' ),
									 'where'    => 'm.member_id=' . intval( $memberId ),
									 'add_join' => array( array( 'select' => 'g.*',
																 'from'	  => array( 'groups' => 'g' ),
																 'where'  => 'g.g_id=m.member_group_id' ) ) ) );

			$this->DB->execute();

			$this->_member = $this->DB->fetch();
		}
		else
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
		}

		/* Set up seconday groups */
		$this->_member = ips_MemberRegistry::setUpSecondaryGroups( $this->_member );

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
		/* Log in type */
		$loginType = $this->_fetchLogInType();
		$where     = ( $loginType == 'username' ) ? $this->DB->buildLower('m.name') . "='" . strtolower( $username ) . "'" : "m.email='" . strtolower( $username ) . "'";

		/* Attempt to load member */
		$this->DB->build( array( 'select'   => 'm.*',
								 'from'     => array( 'members' => 'm' ),
								 'where'    => $where,
								 'add_join' => array( array( 'select' => 'g.*',
															 'from'	  => array( 'groups' => 'g' ),
															 'where'  => 'g.g_id=m.mgroup' ),
													  array( 'select' => 'c.*',
															 'from'	  => array( 'members_converge' => 'c' ),
															 'where'  => 'c.converge_email=m.email' ) ) ) );


		$this->DB->execute();

		$mem = $this->DB->fetch();

		/* Check it out */
		if ( ! $mem['id'] OR ! $mem['converge_pass_hash'] )
		{
			return 'No user found by that username';
		}

		if ( $mem['converge_pass_hash'] != md5( md5( $mem['converge_pass_salt'] ) . md5( $password ) ) )
		{
			return 'Password incorrect';
		}

		/* Test seconday groups */
		$mem = ipsRegistry::member()->setUpSecondaryGroups( $mem );

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
		$loginType = $this->_fetchLogInType();

		return $this->registry->output->template()->upgrade_login_200plus( $loginType );
	}

	/**
	 * Fetch the log in type
	 *
	 * @access	private
	 * @return	string
	 */
	public function _fetchLoginType()
	{
		$loginType = 'username';

		$this->DB->build( array( 'select' => '*', 'from' => 'conf_settings', 'where' => "conf_key IN('ipbli_usertype','converge_login_method')", 'order' => 'conf_key ASC' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$r['conf_value'] = $r['conf_value'] ? $r['conf_value'] : $r['conf_default'];

			if ( $r['conf_value'] )
			{
				$loginType = $r['conf_value'];
			}
		}

		return $loginType;
	}
}