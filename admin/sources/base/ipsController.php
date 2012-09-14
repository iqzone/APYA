<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Primary controller
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10721 $
 */

/**
* Class "Controller"
* A very simple public facing interface to resolve incoming data into
* a command class
*
* @author	Matt Mecham
* @since	Wednesday 14th May 2008
* @package	IP.Board
*/
class ipsController
{
	/**
	 * Registry reference
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	
	/**
	 * Command
	 *
	 * @access	public
	 * @var		string
	 */
	static public $cmd;

	/**
	 * Constructor
	 *
	 * @access	private
	 * @return	@e void
	 */
	private function __construct() { }

	/**
	 * Public facing function to run the controller
	 *
	 * @access	public
	 * @return	@e void
	 */
	static public function run()
	{
		$instance = new ipsController();
		$instance->init();
		$instance->handleRequest();
	}

	/**
	 * Initialize ipsRegistry and this class
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function init()
	{
		$this->registry = ipsRegistry::instance();
		$this->registry->init();
	}

	/**
	 * Handle the incoming request using the command resolver
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function handleRequest()
	{
		$cmd_r  = new ipsController_CommandResolver();
		
		try
		{
			self::$cmd = $cmd_r->getCommand( $this->registry );
		}
		catch( Exception $e )
		{
			$msg = $e->getMessage();

			/* An error occured, so throw a 404 */
			try
			{
				/* The following patch prevents links such as /admin/index.php?adsess=123&app=anyrandomnamehere&module=login&do=login-complete from loading 
					an ACP error screen complete with wrapper, which may expose the applications that are installed to an end user.
					This issue was reported by Christopher Truncer via the IPS ticket system. */
				if( IN_ACP AND !$this->registry->member()->getProperty('member_id') )
				{
					$this->registry->getClass('output')->silentRedirect( ipsRegistry::$settings['base_url'] );
				}

				$this->registry->getClass('output')->showError( 'incorrect_furl', 404, null, null, 404 );
			}
			catch( Exception $e )
			{
				print $msg;
				exit();
			}
		}
						
		IPSDebug::setMemoryDebugFlag( "Everything up until execute call", 0 );
		
		self::$cmd->execute( $this->registry );
	}
}

/**
* Class "Command Resolver"
* Resolves the incoming data
*
* @author	Matt Mecham
* @since	Wednesday 14th May 2008
* @package	IP.Board
*/
class ipsController_CommandResolver
{
	/**#@+
	 * Internal strings to remember
	 *
	 * @access	protected
	 * @var		string
	 */
	protected static $base_cmd;
	protected static $default_cmd;
	protected static $modules_dir  = 'modules_public';
	protected static $class_dir    = 'public';
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		if ( ! self::$base_cmd )
		{
			self::$base_cmd    = ipsRegistry::$current_module == 'ajax' ? new ReflectionClass( 'ipsAjaxCommand' ) : new ReflectionClass( 'ipsCommand' );
			self::$default_cmd = new ipsCommand_default();
			self::$modules_dir = ( IPS_AREA != 'admin' ) ? 'modules_public' : 'modules_admin';
			self::$class_dir   = ( IPS_AREA != 'admin' ) ? 'public'         : 'admin';
		}
	}

	/**
	 * Retreive the command
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	object
	 */
	public function getCommand( ipsRegistry $registry )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		$module    = ipsRegistry::$current_module;
		$section   = ipsRegistry::$current_section;
		$filepath  = IPSLib::getAppDir( IPS_APP_COMPONENT ) . '/' . self::$modules_dir . '/' . $module . '/';

		/* Bug Fix #21009 */
		if ( ! ipsRegistry::$applications[ IPS_APP_COMPONENT ]['app_enabled'] )
		{
			throw new Exception( "The specified application has been disabled" );
		}
		if ( !IN_ACP and !IPSLib::moduleIsEnabled( $module, IPS_APP_COMPONENT ) and $module != 'ajax' )
		{
			throw new Exception( "The specified module has been disabled" );
		}
				
		/* Got a section? */
		if ( ! $section )
		{
			if ( is_file( $filepath . 'defaultSection.php' ) )
			{
				$DEFAULT_SECTION = '';
				include( $filepath . 'defaultSection.php' );/*noLibHook*/

				if ( $DEFAULT_SECTION )
				{
					$section = $DEFAULT_SECTION;
					
					ipsRegistry::$current_section	= $section;
				}
			}
		}

		$_classname = self::$class_dir . '_' .  IPS_APP_COMPONENT . '_' . $module . '_';
		
		/* Rarely used, let's leave file_exists which is faster for non-existent files */
		if ( file_exists( $filepath . 'manualResolver.php' ) )
		{
			$classname = IPSLib::loadActionOverloader( $filepath . 'manualResolver.php'	, $_classname . 'manualResolver' );
		}
		else if ( is_file( $filepath . $section . '.php' ) )
		{
			$classname = IPSLib::loadActionOverloader( $filepath . $section . '.php'	, $_classname . $section );
		}

		IPSDebug::setMemoryDebugFlag( "Controller getCommand executed" );

		if ( class_exists( $classname ) )
		{
			$cmd_class = new ReflectionClass( $classname );

			if ( $cmd_class->isSubClassOf( self::$base_cmd ) )
			{
				return $cmd_class->newInstance();
			}
			else
			{
				throw new Exception( "$section in $module does not exist!" );
			}
		}
		else
		{
			throw new Exception( "$classname does not exist!" );
		}

		# Fudge it to return just the default object
		return clone self::$default_cmd;
	}
}


abstract class ipsCommand
{
	/**#@+
	 * Registry Object Shortcuts
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
	 * Constructor
	 *
	 * @access	public
	 * @return	@e void
	 */
	final public function __construct()
	{
	}

	/**
	 * Make the registry shortcuts
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function makeRegistryShortcuts( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->memberData = IPSMember::setUpModerator( $this->memberData );
	}

	/**
	 * Execute the command (call doExecute)
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function execute( ipsRegistry $registry )
	{
		//$registry->getClass('class_permissions')
		$this->makeRegistryShortcuts( $registry );
		$this->doExecute( $registry );
	}

	/**
	 * Do execute method (must be overriden)
	 *
	 * @access	protected
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	protected abstract function doExecute( ipsRegistry $registry );
}

/**
 * Abstract class for handling ajax requests
 */


abstract class ipsAjaxCommand
{
	/**#@+
	 * Registry Object Shortcuts
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
	protected $ajax;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	@e void
	 */
	final public function __construct()
	{
	}
	
	/**
	 * Magic function to catch all the ajax methods
	 *
	 * @access	public
	 * @param	string  $func       Function name being called
	 * @param	array   $arguments  Array of parameters
	 * @return	mixed
	 */
	public function __call( $func, $arguments )
	{
		if( method_exists( $this->ajax, $func ) )
		{
			return call_user_func_array( array( $this->ajax, $func ), $arguments );
		}
	}

	/**
	 * Creates all the registry shorctus
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function makeRegistryShortcuts( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->memberData = IPSMember::setUpModerator( $this->memberData );
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax  = new $classToLoad();
		
		IPSDebug::fireBug( 'registerExceptionHandler' );
		IPSDebug::fireBug( 'registerErrorHandler' );
	}

	/**
	 * Executes the ajax request, checks secure key
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function execute( ipsRegistry $registry )
	{ 
		/* Setup Shortcuts First */
		$this->makeRegistryShortcuts( $registry );
		
		/* Check the secure key */
		$this->request['secure_key'] = $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			IPSDebug::fireBug( 'error', array( "The security key did not match the member's form hash" ) );

			$this->returnString( 'nopermission' );
		}
		
		$this->doExecute( $registry );
	}

	/**
	 * Do execute method (must be overriden)
	 *
	 * @access	protected
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	protected abstract function doExecute( ipsRegistry $registry );
}


class ipsCommand_default extends ipsCommand
{
	/**
	 * Do execute method
	 *
	 * @access	protected
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	protected function doExecute( ipsRegistry $registry )
	{
		$modules_dir = ( IPS_AREA != 'admin' ) ? 'modules_public' : 'modules_admin';
		$filepath    = IPSLib::getAppDir(  IPS_APP_COMPONENT ) . '/' . $modules_dir . '/' . ipsRegistry::$current_module . '/' . ipsRegistry::$current_section . '.php';
		$filepath	 = str_replace( DOC_IPS_ROOT_PATH, '', $filepath );

		//-----------------------------------------
		// Redirect to board index
		//-----------------------------------------

		if ( ! (IPS_APP_COMPONENT == 'forums' AND ipsRegistry::$current_module == 'forums' AND ipsRegistry::$current_section == 'boards') )
		{
			if( IPB_THIS_SCRIPT == 'admin' )
			{
				$registry->output->silentRedirect( ipsRegistry::$settings['_base_url'] );
			}
			else
			{
				$registry->output->silentRedirect( ipsRegistry::$settings['_original_base_url'] );
			}
		}

		//-----------------------------------------
		// Uh oh, this is a big one.. (no forums app)
		//-----------------------------------------

		if ( ! is_file( $filepath ) )
		{
			$this->registry->getClass('output')->showError( array( 'command_file_missing', $filepath ), 401100, null, null, 404 );
		}
		else
		{
			$this->registry->getClass('output')->showError( array( 'command_class_incorrect', $filepath ), 401200, null, null, 404 );
		}
	}
}