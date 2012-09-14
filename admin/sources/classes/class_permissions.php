<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP Restrictions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */


class class_permissions
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Restrictions row
	 *
	 * @access	public
	 * @var		array
	 */
	public $restrictions_row	= array();
	
	/**
	 * Is restricted or not?
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $restricted		= false;
	
	/**
	 * Return or error out flag
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $return				= true;
	
	/**
	 * In dev flag (allow all)
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $in_dev				= false;
	
	public $editDetailsOnly		= false;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		[Optional] Member permissions array to use (otherwise loads the current member's from the db)
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $permissions=array() )
	{
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();

		if( is_array($permissions) AND count($permissions) )
		{
			$this->restrictions_row = $permissions;
			$this->restricted		= true;
		}
		else
		{
			//-----------------------------------------
			// Don't do this on login
			//-----------------------------------------
			
			if( !$this->memberData['member_id'] )
			{
				return false;
			}
			
			//-----------------------------------------
			// Get restrictions
			//-----------------------------------------
			
			$groups[]	= $this->memberData['member_group_id'];
			
			if( $this->memberData['mgroup_others'] )
			{
				$groups	= array_merge( $groups, explode( ',', IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
			}
			
			$this->DB->build( array( 'select' => '*', 'from' => 'admin_permission_rows', 'where' => "(row_id_type='member' AND row_id=" . $this->memberData['member_id'] . ") OR (row_id_type='group' AND row_id IN(" . implode( ',', $groups ) . "))" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				//-----------------------------------------
				// Member restrictions should override group restrictions
				//-----------------------------------------
				
				if( $r['row_id_type'] == 'member' )
				{
					$this->restrictions_row = unserialize($r['row_perm_cache']);
					$this->restricted		= true;
					break;
				}

				$this->restrictions_row = array_merge( $this->restrictions_row, unserialize($r['row_perm_cache']) );
				$this->restricted		= true;
			}
		}
		
		/* Add in permissions for change my details, if we have restrictions, but not acess to that module */
		if( is_array( $this->restrictions_row ) && count( $this->restrictions_row ) )
		{
			$coreId		= ipsRegistry::$applications[ 'core' ]['app_id'];
		
			foreach( ipsRegistry::$modules['core'] as $module )
			{
				if( $module['sys_module_key'] == 'mycp' )
				{
					$this->mycpModule = $module['sys_module_id'];
					continue;
				}
			}
			
			if( ! in_array( $coreId, $this->restrictions_row['applications'] ) )
			{
				$this->restrictions_row['applications'][]	= $coreId;
			}

			if( ! in_array( $this->mycpModule, $this->restrictions_row['modules'] ) )
			{
				$this->restrictions_row['modules'][]	= $this->mycpModule;
				$this->editDetailsOnly					= true;
			}
		}
	}

	/**
	 * Check Application Access
	 *
	 * Checks the permission for the member
	 *
	 * @access	public
	 * @param	string 		[ Application Key ]
	 * @return	mixed		Boolean member has access or not, or print error if $this->return is false
	 */
	public function checkForAppAccess( $app='' )
	{
		//-----------------------------------------
		// IN DEV check?
		//-----------------------------------------
		
		if ( $this->in_dev and IN_DEV )
		{
			return TRUE;
		}
	
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$app	= ( $app ) ? $app : ipsRegistry::$current_application;
		$app_id	= ipsRegistry::$applications[ $app ]['app_id'];
		$return	= $this->restricted	? false : true;

		if( is_array($this->restrictions_row) AND count($this->restrictions_row) )
		{
			if( isset($this->restrictions_row['applications']) )
			{
				if( in_array( $app_id, $this->restrictions_row['applications'] ) )
				{
					$return = TRUE;
				}
			}
		}

		//-----------------------------------------
		// I can haz teh permishuns?
		//-----------------------------------------
		
		if ( $this->return )
		{
			return $return;
		}
		else
		{
			if ( $return === FALSE )
			{
				ipsRegistry::getClass('output')->showError( $this->lang->words['no_permission'], 1002 );
			}
			else
			{
				return $return;
			}
		}
	}
	
	/**
	 * Check Module Access
	 *
	 * Checks the permission for the member
	 *
	 * @access	public
	 * @param	string 		[ Application Key ]
	 * @param	string 		[ Module Key ]
	 * @return	mixed		Boolean has access or not, or outputs HTML error message
	 */
	public function checkForModuleAccess( $app='', $module='' )
	{
		//-----------------------------------------
		// IN DEV check?
		//-----------------------------------------
		
		if ( $this->in_dev and IN_DEV )
		{
			return TRUE;
		}

		$auto_access = array( 'ajax', 'api', 'search', 'palette', 'login' );
		
		if ( in_array( $module, $auto_access ) )
		{
			return TRUE;
		}		
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$app	= ( $app ) ? $app    : ipsRegistry::$current_application;
		$app_id	= ipsRegistry::$applications[ $app ]['app_id'];
		$module	= ( $module ) ? $module : ipsRegistry::$current_module;
		$mod_id	= $this->_getModuleId( $app, $module );
		$return	= $this->restricted	? false : true;

		//-----------------------------------------
		// Access application?
		//-----------------------------------------
		
		if( ! $this->checkForAppAccess( $app ) )
		{ 
			return false;
		}

		if( is_array($this->restrictions_row) AND count($this->restrictions_row) )
		{
			if( isset($this->restrictions_row['modules']) )
			{
				if( in_array( $mod_id, $this->restrictions_row['modules'] ) )
				{
					$return = TRUE;
				}
			}
		}
		
		//-----------------------------------------
		// I can haz teh permishuns?
		//-----------------------------------------
		
		if ( $this->return )
		{
			return $return;
		}
		else
		{
			if ( $return === FALSE )
			{
				ipsRegistry::getClass('output')->showError( 'no_permission', 1003 );
			}
			else
			{
				return $return;
			}
		}
	}
	
	/**
	 * Check Section Access
	 *
	 * Checks the permission for the member
	 *
	 * @access	public
	 * @param	string 		[ Application Key ]
	 * @param	string 		[ Module Key ]
	 * @param	string 		[ Section Key ]
	 * @return	mixed		Boolean has access or not, or outputs HTML error message
	 */
	public function checkForSectionAccess( $app='', $module='', $section='' )
	{
		//-----------------------------------------
		// IN DEV check?
		//-----------------------------------------
		
		if ( $this->in_dev and IN_DEV )
		{
			return TRUE;
		}

		$auto_access = array( 'ajax', 'api', 'search', 'palette', 'login' );
		
		if ( in_array( $module, $auto_access ) )
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// Module access?
		//-----------------------------------------
		
		if ( !$this->checkForModuleAccess( $app, $module ) )
		{
			return FALSE;
		}
				
		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		$menu = ipsRegistry::cache()->getCache('app_menu_cache');
		
		foreach( $menu[ $app ] as $k => $items )
		{
			if ( preg_match( '/(\d)_' . $module . '/', $k ) )
			{
				foreach( $items['items'] as $i )
				{
					if ( $i['section'] == $section )
					{
						if ( $i['rolekey'] )
						{
							$return = $this->checkPermission( $i['rolekey'], $app, $module );
							break 2;
						}
						else
						{
							$return = TRUE;
							break 2;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// I can haz teh permishuns?
		//-----------------------------------------
		
		if ( $this->return )
		{
			return $return;
		}
		else
		{
			if ( $return === FALSE )
			{
				ipsRegistry::getClass('output')->showError( 'no_permission', 1004 );
			}
			else
			{
				return $return;
			}
		}
		
	}

	/**
	 * Grab the module id from the modules array
	 *
	 * @access	protected
	 * @param	string 		[ Application Key ]
	 * @param	string 		[ Module Key ]
	 * @return	integer		Module id
	 */
	protected function _getModuleId( $app='', $module='' )
	{
		$app		= ( $app ) ? $app : ipsRegistry::$current_application;
		$app_id		= ipsRegistry::$applications[ $app ]['app_id'];
		$module		= ( $module ) ? $module : ipsRegistry::$current_module;
		$modules	= ipsRegistry::$modules[ $app ];
		$mod_id		= 0;
		
		if( is_array($modules) AND count($modules) )
		{
			foreach( $modules as $module_id => $module_data )
			{
				if( $module_data['sys_module_key'] == $module AND $module_data['sys_module_admin'] == 1 )
				{
					$mod_id = $module_data['sys_module_id'];
					break;
				}
			}
		}
		
		return $mod_id;
	}
	
	/**
	 * Wrapper for checkPermission to show the error message regardless of current status of $this->return
	 *
	 * Checks the permission for the member
	 *
	 * @access	public
	 * @param	string		Permission Key (from items array)
	 * @param	string 		[ Application Key ]
	 * @param	string 		[ Module Key ]
	 * @return	mixed		True if has permission or outputs HTML error message if not
	 */
	public function checkPermissionAutoMsg( $key, $app='', $module='' )
	{		
		$tmp			= $this->return;
		$this->return	= false;
		
		$this->checkPermission( $key, $app, $module );

		$this->return	= $tmp;
		
		return true;
	}

	/**
	 * Check Permission to a given key
	 *
	 * Checks the permission for the member
	 *
	 * @access	public
	 * @param	string		Permission Key (from items array)
	 * @param	string 		[ Application Key ]
	 * @param	string 		[ Module Key ]
	 * @return	mixed		True if has permission or outputs HTML error message if not
	 */
	public function checkPermission( $key, $app='', $module='' )
	{
		//-----------------------------------------
		// IN DEV check?
		//-----------------------------------------

		if ( $this->in_dev and IN_DEV )
		{
			return TRUE;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$app	= ( $app ) ? $app    : ipsRegistry::$current_application;
		$app_id	= ipsRegistry::$applications[ $app ]['app_id'];
		$module	= ( $module ) ? $module : ipsRegistry::$current_module;
		$mod_id	= $this->_getModuleId( $app, $module );
		$return	= $this->restricted	? false : true;

		//-----------------------------------------
		// Check module, which will also check app
		//-----------------------------------------
		
		if( ! $this->checkForModuleAccess( $app, $module ) )
		{
			$return	= false;
		}
		
		else if( $key )
		{
			//print "<pre>";print $key. ' '.$module . ' ' .$mod_id;
			//print_r($this->restrictions_row['modules']);
			//print_r($this->restrictions_row['items'][ $mod_id ]);
			//-----------------------------------------
			// And now for the item..
			//-----------------------------------------
			
			if( is_array($this->restrictions_row) AND count($this->restrictions_row) )
			{
				if( isset($this->restrictions_row['items']) )
				{
					if( isset($this->restrictions_row['items'][ $mod_id ]) )
					{
						if( in_array( $key, $this->restrictions_row['items'][ $mod_id ] ) )
						{
							$return = TRUE;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Returning.. or...
		//-----------------------------------------
		
		if ( $this->return )
		{
			return $return;
		}
		else
		{
			if ( $return === FALSE )
			{
				ipsRegistry::getClass('output')->showError( 'no_permission', 1004 );
			}
			else
			{
				return $return;
			}
		}
	}	
	
}