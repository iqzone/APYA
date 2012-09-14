<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ipsController for setup
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

/**
 * Class "Controller"
 * A very simple public facing interface to resolve incoming data into
 * a command class
 *
 * @author	Matt Mecham
 * @since	Wednesday 14th May 2008
 * @package	IP.Board
 * @ignore
 */
class ipsController
{
	/**
	 * Registry reference
	 *
	 * @access	private
	 * @var		object
	 */
	private $registry;

	/**
	 * Current command
	 *
	 * @access	public
	 * @var		object
	 */
	static public $cmd;

	/**
	 * Make us a singleton please
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
		$cmd_r   = new ipsController_CommandResolver();
		self::$cmd     = $cmd_r->getCommand( $this->registry );
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
	/**
	 * Base command
	 *
	 * @access	private
	 * @var		object
	 */
	private static $base_cmd;

	/**
	 * AJAX command
	 *
	 * @access	private
	 * @var		object
	 */
	private static $ajax_cmd;

	/**
	 * Default command
	 *
	 * @access	private
	 * @var		object
	 */
	private static $default_cmd;

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
			self::$base_cmd    = new ReflectionClass( 'ipsCommand' );
			self::$default_cmd = new ipsCommand_default();
		}
	}

	/**
	 * Get the command
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	object	Command object
	 */
	public function getCommand( ipsRegistry $registry )
	{
		$module   = ipsRegistry::$current_module;
		$section  = ipsRegistry::$current_section;
		$filepath = ( IPS_IS_UPGRADER ) ? IPS_ROOT_PATH . 'setup/applications/upgrade/sections/' : IPS_ROOT_PATH . 'setup/applications/install/sections/';

		/* Got a section? */
		if ( ! $section )
		{
			if ( is_file( $filepath . 'index.php' ) )
			{
				$section = 'index';
			}
		}

		$classname = IPS_APP_COMPONENT . '_' . $section;

		if ( is_file( $filepath . 'manualResolver.php' ) )
		{
			require_once( $filepath . 'manualResolver.php' );/*noLibHook*/
			$classname = IPS_APP_COMPONENT . '_' . $module . '_manualResolver';
		}
		else if ( is_file( $filepath . $section . '.php' ) )
		{
			require_once( $filepath . $section . '.php' );/*noLibHook*/
		}

		if ( class_exists( $classname ) )
		{
			$cmd_class = new ReflectionClass( $classname );

			if ( $cmd_class->isSubClassOf( self::$base_cmd ) )
			{
				return $cmd_class->newInstance();
			}
			else
			{
				throw new Exception( "{$section} in {$module} does not exist!" );
			}
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
	public $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Constructor override
	 *
	 * @access	public
	 * @return	@e void
	 */
	final public function __construct()
	{
	}

	/**
	 * Create registry shortcuts
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
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Execute the commands
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function execute( ipsRegistry $registry )
	{
		$this->makeRegistryShortcuts( $registry );
		$this->doExecute( $registry );
	}

	/**
	 * doExecute, most be override
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
	 * Main execution method
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	protected function doExecute( ipsRegistry $registry )
	{
		$filepath    = IPSLib::getAppDir(  IPS_APP_COMPONENT ) . '/modules/' . ipsRegistry::$current_module . '/' . ipsRegistry::$current_section . '.php';
		$filepath	 = str_replace( DOC_IPS_ROOT_PATH, '', $filepath );

		//-----------------------------------------
		// Uh oh, this is a big one.. (no forums app)
		//-----------------------------------------

		if ( ! is_file( $filepath ) )
		{
			print "Command File " . ipsRegistry::$current_module . " missing";
			exit();
		}
		else
		{
			print "Nothing to do!";
			exit();
		}
	}
}