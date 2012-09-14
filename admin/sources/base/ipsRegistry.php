<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ipsRegistry:: Registry file controlls handling of objects needed throughout IPB
 * Last Updated: $Date: 2012-06-01 04:38:59 -0400 (Fri, 01 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tue. 17th August 2004
 * @version		$Rev: 10847 $
 */
 
/**
* Base registry class
*/
class ipsRegistry
{
	/**
	 * Holds instance of registry (singleton implementation)
	 *
	 * @var		object
	 */
	private static $instance;

	/**
	 * Registry initialized yet?
	 *
	 * @var		boolean
	 */
	private static $initiated = FALSE;

	/**
	 * SEO templates
	 *
	 * @var		array
	 */
	protected static $_seoTemplates = array();

	/**
	 * Incoming URI - used in SEO / fURL stuffs
	 *
	 * @var		string
	 */
	protected static $_uri = '';
	
	/**
	 * Flag to note incorrect FURL (no furl template match)
	 *
	 * @var		string
	 */
	protected static $_noFurlMatch = false;

	/**#@+
	 * Holds data for app / coreVariables
	 *
	 * @var		array
	 */
	protected static $_coreVariables			= array();
	protected static $_masterCoreVariables	= array();
	/**#@-*/

	/**
	 * Handles for other singletons
	 *
	 * @var		array
	 */
	protected static $handles					= array();

	/**
	 * Handles for other classes
	 *
	 * @var		array
	 */
	protected static $classes					= array();

	/**
	 * Word array
	 *
	 * @var		array
	 */
	static public $words					= array();

	/**
	 * URLs
	 *
	 * @var		array
	 */
	static public $urls						= array();

	/**
	 * Our processed URL
	 *
	 * @var		string
	 */
	static public $processed_url			= '';

	/**
	 * Server load
	 *
	 * @var		string
	 */
	static public $server_load;

	/**
	 * Do not print HTTP headers
	 *
	 * @var		bool
	 */
	static public $no_print_header			= false;

	/**#@+
	 * Query string information
	 *
	 * @var		string
	 */
	static public $query_string_safe;
	static public $query_string_real;
	static public $query_string_formatted;
	/**#@-*/

	/**#@+
	 * Version information
	 *
	 * @var		string
	 */
	static public $version         = null;
	static public $acpversion      = null;
	static public $vn_full         = '';
	static public $vn_build_date   = '';
	static public $vn_build_reason = '';
	/**#@-*/

	/**#@+
	 * Version information
	 *
	 * @var		mixed	Strings and arrays
	 */
	static public $applications        = array();
	static public $modules             = array();
	static public $modules_by_section  = array();
	static public $current_application = '';
	static public $current_module      = '';
	static public $current_section     = '';
	/**#@-*/

	/**
	 * Application class vars
	 *
	 * @var		array
	 */
	static public $app_class = array();

	/**
	 * Settings
	 *
	 * @var		array
	 */
	static public $settings	= array();

	/**
	 * Input parameters
	 *
	 * @var		array
	 */
	static public $request	= array();

	/**
	 * Template striping
	 *
	 * @var		array
	 */
	public $templateStriping = array();

	/**
	 * Initialize singleton
	 *
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Enable app search
	 */
	static public $appSearch = FALSE;

	/**
	 * Ok, we need a guaranteed way to perform some clean up
	 * before __destruct() is called on other classes. For example
	 * using the DB connection isn't possible if class_db::__destruct() runs
	 * before ipsRegistry::__destruct. PHP 5 - 5.2.5 ran the __destruct functions
	 * in the order the classes are created, so we could bank on ipsRegistry::__destruct
	 * being called first as it's created first. Happy days
	 * But as of 5.2.5 the order is __reversed__ so that ipsRegistry would be called last
	 * making it all but useless. 
	 * So, we use a register_shutdown_function instead which is always called before any
	 * __destruct() destructors.  Happy days again. We hope
	 *
	 * @return	@e void
	 */
	static public function __myDestruct()
	{
		foreach( self::$handles as $name => $obj )
		{
			if ( method_exists( $obj, '__myDestruct' ) )
			{
				$obj->__myDestruct();
			}
		}

		//-----------------------------------------
		// Process any pending emails to go out...
		//-----------------------------------------

		self::processMailQueue();
	}

	/**
	 * Process the mail queue
	 *
	 * @return	@e void
	 */
	static public function processMailQueue()
	{
		//-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		$doReset								= 0;
		$cache									= self::$handles['caches']->getCache('systemvars');
		self::$settings['mail_queue_per_blob']	= isset(self::$settings['mail_queue_per_blob']) ? self::$settings['mail_queue_per_blob'] : 10;

		if ( ! isset( $cache['mail_queue'] ) OR $cache['mail_queue'] < 0 )
		{
			$mailQueue		     = self::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as total',
																	 'from'   => 'mail_queue' ) );
			$mailQueueCount	     = intval($mailQueue['total']);
			$cache['mail_queue'] = $mailQueueCount;
			$doReset		     = 1;
		}
		else
		{
			$mailQueueCount = intval($cache['mail_queue']);
		}
		
		$sent_ids = array();

		if ( $mailQueueCount > 0 )
		{ 
			/* Test lock */
			$test = self::DB()->buildAndFetch( array( 'select' => 'cs_value, cs_updated',
													  'from'   => 'cache_store',
													  'where'  => 'cs_key=\'mail_processing\'' ) );
			
			if ( ! empty( $test['cs_value'] ) )
			{
				/* Check time */
				if ( empty( $test['cs_updated'] ) OR ( time() - 30 > $test['cs_updated'] ) )
				{
					/* 30 seconds, so unlock incase its stuck */
					self::DB()->replace( 'cache_store', array( 'cs_key' => 'mail_processing', 'cs_value' => 0, 'cs_updated' => time() ), array( 'cs_key' ) );
				}
				
				return false;
			}
			
			/* LOCK- Race condition when mail queue table is locked so it cannot delete mail so dupe emails are sent */
			self::DB()->replace( 'cache_store', array( 'cs_key' => 'mail_processing', 'cs_value' => 1, 'cs_updated' => time() ), array( 'cs_key' ) );
			if ( !self::DB()->getAffectedRows() )
			{
				return;
			}
			
			//-----------------------------------------
			// Get the mail stuck in the queue
			//-----------------------------------------

			self::DB()->build( array( 'select' => '*',
									  'from'   => 'mail_queue',
									  'order'  => 'mail_id ASC',
									  'limit'  => array( 0, self::$settings['mail_queue_per_blob'] ) ) );
			self::DB()->execute();

			while ( $r = self::DB()->fetch() )
			{
				$data[]     = $r;
				$sent_ids[] = $r['mail_id'];
			}

			if ( count($sent_ids) )
			{
				//-----------------------------------------
				// Delete sent mails and update count
				//-----------------------------------------

				$mailQueueCount = $mailQueueCount - count($sent_ids);

				self::DB()->delete( 'mail_queue', 'mail_id IN (' . implode( ",", $sent_ids ) . ')' );

				foreach( $data as $mail )
				{
					if ( $mail['mail_to'] and $mail['mail_subject'] and $mail['mail_content'] )
					{
						/* Clear out previous data */
						IPSText::getTextClass('email')->clearContent();
						
						/* Specifically a HTML email */
						if ( $mail['mail_html_on'] )
						{
							IPSText::getTextClass('email')->setHtmlEmail( true );
							IPSText::getTextClass('email')->setHtmlTemplate( $mail['mail_content'] );
						}
						else
						{
							/* We want to parse the plain text emails */
							IPSText::getTextClass('email')->setHtmlEmail( false );
							IPSText::getTextClass('email')->setPlainTextTemplate( $mail['mail_content'] );
						}
						
						/* Build plain/HTML versions */
						IPSText::getTextClass('email')->buildMessage( array() );
		
						IPSText::getTextClass('email')->to		= $mail['mail_to'];
						IPSText::getTextClass('email')->cc		= empty($mail['mail_cc']) ? array() : explode( ',', $mail['mail_cc'] );
						IPSText::getTextClass('email')->from	= $mail['mail_from'] ? $mail['mail_from'] : self::$settings['email_out'];
						IPSText::getTextClass('email')->subject	= $mail['mail_subject'];

						IPSText::getTextClass('email')->sendMail();

						IPSDebug::addLogMessage('Email Sent: ' . $mail['mail_to'], 'bulkemail' );
					}
				}
			}
			else
			{
				//-----------------------------------------
				// No mail after all?
				//-----------------------------------------

				$mailQueueCount = 0;
				$doReset        = 1;
			}

			//-----------------------------------------
			// Set new mail_queue count
			//-----------------------------------------

			$cache['mail_queue']	= $mailQueueCount;
		}
		
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		if ( $mailQueueCount > 0 OR $doReset )
		{
			self::$handles['caches']->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1 ) );
			
			/* UNLOCK */
			self::DB()->replace( 'cache_store', array( 'cs_key' => 'mail_processing', 'cs_value' => 0, 'cs_updated' => time() ), array( 'cs_key' ) );
		}
	}

	/**
	 * Initiate the registry
	 *
	 * @return	mixed	false or void
	 */
	static public function init()
	{
		$INFO 			   = array();
		$_ipsPowerSettings = array();
		
		if ( self::$initiated === TRUE )
		{
			return FALSE;
		}
		
		self::$initiated = TRUE;
	
		/* Load static classes */
		require( IPS_ROOT_PATH . "sources/base/core.php" );/*noLibHook*/
		require( IPS_ROOT_PATH . "sources/base/ipsMember.php" );/*noLibHook*/
		
		/* Debugging notices? */
		if ( defined( 'IPS_ERROR_CAPTURE' ) AND IPS_ERROR_CAPTURE !== FALSE )
		{
			@error_reporting( E_ALL | E_NOTICE );
			@set_error_handler("IPSDebug::errorHandler");
		}
		
		/* Load core variables */
		self::_loadCoreVariables();

		/* Load config file */
		if ( is_file( DOC_IPS_ROOT_PATH . 'conf_global.php' ) )
		{
			require( DOC_IPS_ROOT_PATH . 'conf_global.php' );/*noLibHook*/

			if ( is_array( $INFO ) )
			{
				foreach( $INFO as $key => $val )
				{
					ipsRegistry::$settings[ $key ]	= str_replace( '&#092;', '\\', $val );
				}
			}
		}

		/* Load secret sauce */
		if ( is_array( $_ipsPowerSettings) )
		{
			ipsRegistry::$settings = array_merge( $_ipsPowerSettings, ipsRegistry::$settings );
		}
				
		/* Just in case they copy a space in the license... @link http://community.invisionpower.com/tracker/issue-33048-strip-whitespace-from-license-key */
		ipsRegistry::$settings['ipb_reg_number'] = trim(ipsRegistry::$settings['ipb_reg_number']);
		
		/* Make sure we're installed */
		if ( empty( $INFO['sql_database'] ) )
		{
			/* Quick PHP version check */
			if ( ! version_compare( MIN_PHP_VERS, PHP_VERSION, '<=' ) )
			{
				print "You must be using PHP " . MIN_PHP_VERS . " or better. You are currently using: " . PHP_VERSION;
				exit();
			}

			$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : @getenv('HTTP_HOST');
			$self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : @getenv('PHP_SELF');

			if( IPS_AREA == 'admin' )
			{
				@header("Location: http://".$host.rtrim(dirname($self), '/\\')."/install/index.php" );
			}
			else
			{
				if( !defined('CP_DIRECTORY') )
				{
					define( 'CP_DIRECTORY', 'admin' );
				}

				@header("Location: http://".$host.rtrim(dirname($self), '/\\')."/".CP_DIRECTORY."/install/index.php" );
			}
		}

		/* Switch off dev mode you idjit */
		if ( ! defined( 'IN_DEV' ) )
		{
			define( 'IN_DEV', 0 );
		}

		/* Shell defined? */
		if ( ! defined( 'IPS_IS_SHELL' ) )
		{
			define( 'IPS_IS_SHELL', FALSE );
		}

		/* If this wasn't defined in the gateway file... */
		if ( ! defined( 'ALLOW_FURLS' ) )
		{
			define( 'ALLOW_FURLS', ( ipsRegistry::$settings['use_friendly_urls'] ) ? TRUE : FALSE );
		}
		
		if ( ! defined( 'IPS_IS_MOBILE_APP' ) )
		{
			define( 'IPS_IS_MOBILE_APP', false );
		}
		
		/**
		 * File and folder permissions
		 */
	 	if( !defined('IPS_FILE_PERMISSION') )
	 	{
			define( 'IPS_FILE_PERMISSION', 0777 );
		}

	 	if( !defined('IPS_FOLDER_PERMISSION') )
	 	{
			define( 'IPS_FOLDER_PERMISSION', 0777 );
		}
		
		/* Set it again incase a gateway turned it off */
		ipsRegistry::$settings['use_friendly_urls'] = ALLOW_FURLS;
		
		/* Start timer */
		IPSDebug::startTimer();

		/* Cookies... */
		IPSCookie::$sensitive_cookies = array( 'session_id', 'admin_session_id', 'member_id', 'pass_hash' );

		/* INIT DB */ 
		self::$handles['db'] = ips_DBRegistry::instance();

		/* Set DB */
		self::$handles['db']->setDB( ipsRegistry::$settings['sql_driver'] );

		/* Input set up... */
		if ( is_array( $_POST ) and count( $_POST ) )
		{
			foreach( $_POST as $key => $value )
			{
				# Skip post arrays
				if ( ! is_array( $value ) )
				{
					$_POST[ $key ] = IPSText::stripslashes( $value );
				}
			}
		}
		
    	//-----------------------------------------
    	// Clean globals, first.
    	//-----------------------------------------

		IPSLib::cleanGlobals( $_GET );
		IPSLib::cleanGlobals( $_POST );
		IPSLib::cleanGlobals( $_COOKIE );
		IPSLib::cleanGlobals( $_REQUEST );

		# GET first
		$input = IPSLib::parseIncomingRecursively( $_GET, array() );

		# Then overwrite with POST
		self::$request = IPSLib::parseIncomingRecursively( $_POST, $input );
		
		# Fix some notices
		if( !isset(self::$request['module']) )
		{
			self::$request['module']	= '';
		}
		
		if( !isset(self::$request['section']) )
		{
			self::$request['section']	= '';
		}

		# Assign request method
		self::$request['request_method'] = strtolower( my_getenv('REQUEST_METHOD') );
		
		/* Define some constants */
		define( 'IPS_IS_TASK', ( isset( self::$request['module'] ) AND self::$request['module'] == 'task' AND self::$request['app'] == 'core' ) ? TRUE : FALSE );
		define( 'IPS_IS_AJAX', ( isset( self::$request['module'] ) AND self::$request['module'] == 'ajax' ) ? TRUE : FALSE );
		
		/* First pass of app set up. Needs to be BEFORE caches and member are set up */
		self::_fUrlInit();

		self::_manageIncomingURLs();

		/* _manageIncomingURLs MUST be called first!!! */
		self::_setUpAppData();
		
		/* Load app / coreVariables.. must be called after app Data */
		self::_loadAppCoreVariables( IPS_APP_COMPONENT );

		/* Must be called after _manageIncomingURLs */
		self::$handles['db']->getDB()->setDebugMode( ( IPS_SQL_DEBUG_MODE ) ? ( isset($_GET['debug']) ? intval($_GET['debug']) : 0 ) : 0 );

		/* Get caches */
		self::$handles['caches']   = ips_CacheRegistry::instance();

		/* Make sure all is well before we proceed */
		try
		{
			self::instance()->setUpSettings();
		}
		catch( Exception $e )
		{
			print file_get_contents( DOC_IPS_ROOT_PATH . 'cache/skin_cache/settingsEmpty.html' );
			exit;
		}
		
		/* Bah, now let's go over any input cleaning routines that have settings *sighs* */
		self::$request = IPSLib::postParseIncomingRecursively( self::$request );
		
		/* Set up dummy member class to prevent errors if cache rebuild required */
		self::$handles['member']   = ips_MemberRegistryDummy::instance();
		
		/* Build module and application caches */
		self::instance()->checkCaches();
		
		/* Set up app specific redirects. Must be called before member/sessions setup */
		self::_parseAppResets();
		
		/* Re-assign member */
		unset( self::$handles['member'] );
		self::$handles['member']   = ips_MemberRegistry::instance();
		
		/* Load other classes */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_localization.php', 'class_localization' );
		self::instance()->setClass( 'class_localization', new $classToLoad( self::instance() ) );
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		self::instance()->setClass( 'permissions'       , new $classToLoad( self::instance() ) );

		/* Must be called before output initiated */
		self::getAppClass( IPS_APP_COMPONENT );
		
		if ( IPS_AREA == 'admin' )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/output/publicOutput.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/output/adminOutput.php', 'adminOutput' );
			self::instance()->setClass( 'output'           , new $classToLoad( self::instance() ) );
			
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/class_admin_functions.php", 'adminFunctions' );
			self::instance()->setClass( 'adminFunctions'   , new $classToLoad( self::instance() ) );
			
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_permissions.php', 'class_permissions' );
			self::instance()->setClass( 'class_permissions', new $classToLoad( self::instance() ) );
		}
		else
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH  . 'sources/classes/output/publicOutput.php', 'output' );
			self::instance()->setClass( 'output', new $classToLoad( self::instance(), TRUE ) );
			
			register_shutdown_function( array( 'ipsRegistry', '__myDestruct' ) );
		}
		
		/* Post member processing */
		self::$handles['member']->postOutput();
		
		/* Add SEO templates to the output system */
		self::instance()->getClass('output')->seoTemplates = self::$_seoTemplates;

		//-----------------------------------------
		// Sort out report center early, so counts
		// and cache is right
		//-----------------------------------------

		$memberData	=& self::$handles['member']->fetchMemberData();
		$memberData['showReportCenter']	= false;

		$member_group_ids	= array( $memberData['member_group_id'] );
		$member_group_ids	= array_diff( array_merge( $member_group_ids, explode( ',', $memberData['mgroup_others'] ) ), array('') );
		$report_center		= array_diff( explode( ',', ipsRegistry::$settings['report_mod_group_access'] ), array('') );

		foreach( $report_center as $groupId )
		{
			if( in_array( $groupId, $member_group_ids ) )
			{
				$memberData['showReportCenter']	= true;
				break;
			}
		}

		if( $memberData['showReportCenter'] )
		{
			$memberData['access_report_center']	= true;

			$memberCache	= $memberData['_cache'];
			$reportsCache	= self::$handles['caches']->getCache('report_cache');

			if( ! $memberCache['report_last_updated'] || $memberCache['report_last_updated'] < $reportsCache['last_updated'] )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary' );
				$reports = new $classToLoad( ipsRegistry::instance() );
				$totalReports = $reports->rebuildMemberCacheArray();

				$memberCache['report_num']	= $totalReports;
				$memberData['_cache']	= $memberCache;
			}
		}
		
		/* More set up */
		self::_finalizeAppData();
		
		/* Finish fURL stuffs */
		self::_fUrlComplete();
		
		self::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );

		if ( IPS_AREA == 'admin' )
		{
			$validationStatus  = self::member()->sessionClass()->getStatus();
			$validationMessage = self::member()->sessionClass()->getMessage();
			
			if ( ( ipsRegistry::$request['module'] != 'login' ) AND ( ! $validationStatus ) )
			{
				//-----------------------------------------
				// Force log in
				//-----------------------------------------

				if ( ipsRegistry::$request['module'] == 'ajax' )
				{
					@header( "Content-type: application/json;charset=" . IPS_DOC_CHAR_SET );
					print "{
							'error' : \"" . self::instance()->getClass('class_localization')->words['acp_sessiontimeout'] . "\",
							'__session__expired__log__out__' : 1
					  	   }";
					exit();
				}
				else
				{
					ipsRegistry::$request['module'] = 'login';
					ipsRegistry::$request['core']   = 'login';
					
					$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'core' ) . "/modules_admin/login/manualResolver.php", 'admin_core_login_manualResolver' );
					$runme       = new $classToLoad( self::instance() ); 
					$runme->doExecute( self::instance() ); 

					exit();
				}
			}
		}
		else if ( IPS_AREA == 'public' )
		{	
			/* Set up member */
			self::$handles['member']->finalizePublicMember();
			
			/* Are we banned: Via IP Address? */
			if ( IPSMember::isBanned( 'ipAddress', self::$handles['member']->ip_address ) === TRUE )
			{
				self::instance()->getClass('output')->showError( 'you_are_banned', 2000, true, null, 403 );
			}
			
			/* Are we banned: By DB */
			if ( self::$handles['member']->getProperty('member_banned') == 1 or self::$handles['member']->getProperty( 'temp_ban' ) )
			{
				/* Don't show this message if we're viewing the warn log */
				if ( ipsRegistry::$request['module'] != 'ajax' or ipsRegistry::$request['section'] != 'warnings' )
				{
					self::getClass( 'class_localization' )->loadLanguageFile( 'public_error', 'core' );
					
					$message = '';
					if ( self::$handles['member']->getProperty('member_banned') )
					{
						$message = self::getClass( 'class_localization' )->words['no_view_board_b'];
					}
					else
					{
						$ban_arr = IPSMember::processBanEntry( self::$handles['member']->getProperty( 'temp_ban' ) );
		
						/* No longer banned */
						if( time() >= $ban_arr['date_end'] )
						{
							self::DB()->update( 'members', array( 'temp_ban' => '' ), 'member_id=' . self::$handles['member']->getProperty( 'member_id' ) );
						}
						/* Still banned */
						else
						{
							$message = sprintf( self::getClass( 'class_localization' )->words['account_susp'], self::getClass( 'class_localization' )->getDate( $ban_arr['date_end'], 'LONG', 1 ) );
						}
					}
									
					/* Get anything? */
					if ( $message )
					{
						$warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => 'wl_member=' . self::$handles['member']->getProperty('member_id') . ' AND wl_suspend<>0', 'order' => 'wl_date DESC', 'limit' => 1 ) );
						
						if ( $warn['wl_id'] and ipsRegistry::$settings['warn_show_own'] )
						{
							$moredetails = "<a href='javascript:void(0);' onclick='warningPopup( this, {$warn['wl_id']} );'>". self::getClass('class_localization')->words['warnings_moreinfo'] ."</a>";
						}
					
						self::instance()->getClass('output')->showError( "{$message} {$moredetails}", 1001, true, null, 403 );
					}
				}
			}

			
			/* Check server load */
			if ( ipsRegistry::$settings['load_limit'] > 0 )
			{
				$server_load	= IPSDebug::getServerLoad();

				if ( $server_load )
				{
					$loadinfo = explode( "-", $server_load );

					if ( count($loadinfo) )
					{
						self::$server_load = $loadinfo[0];

						if ( self::$server_load > ipsRegistry::$settings['load_limit'] )
						{
							self::instance()->getClass('output')->showError( 'server_too_busy', 2001 );
						}
					}
				}
			}
			
			/* Specific Ajax Check */
			if ( IPS_IS_AJAX and ipsRegistry::$request['section'] != 'warnings' )
			{
				if ( self::$handles['member']->getProperty('g_view_board') != 1 || ( ipsRegistry::$settings['board_offline'] && ! self::$handles['member']->getProperty('g_access_offline') ) )
				{
					@header( "Content-type: application/json;charset=" . IPS_DOC_CHAR_SET );
					print json_encode( array( 'error' => 'no_permission', '__board_offline__' => 1 ) );
					exit();
				}
			}
			
			/* Other public check */
			if ( IPB_THIS_SCRIPT == 'public' and
				IPS_ENFORCE_ACCESS				   === FALSE AND (
				ipsRegistry::$request['section']   != 'login'  and
				ipsRegistry::$request['section']   != 'lostpass'  and
				IPS_IS_AJAX === FALSE and
				ipsRegistry::$request['section']   != 'rss' and
				ipsRegistry::$request['section']   != 'attach' and
				ipsRegistry::$request['module']    != 'task'   and
				ipsRegistry::$request['section']   != 'captcha' ) )
			{
				//-----------------------------------------
				// Permission to see the board?
				//-----------------------------------------
				
				if ( self::$handles['member']->getProperty('g_view_board') != 1 )
				{
					self::getClass('output')->showError( 'no_view_board', 1000, null, null, 403 );
				}
				
				//--------------------------------
				//  Is the board offline?
				//--------------------------------

				if ( ipsRegistry::$settings['board_offline'] == 1 AND ! IPS_IS_SHELL )
				{
					if ( self::$handles['member']->getProperty('g_access_offline') != 1 )
					{
						ipsRegistry::$settings['no_reg'] = 1;
						self::getClass('output')->showBoardOffline();
					}
				}

				//-----------------------------------------
				// Do we have a display name?
				//-----------------------------------------
				
				if( !( ipsRegistry::$request['section'] == 'register' AND ( ipsRegistry::$request['do'] == 'complete_login' OR ipsRegistry::$request['do'] == 'complete_login_do' ) ) )
				{
					if ( ! self::$handles['member']->getProperty('members_display_name') /*AND self::$handles['member']->getProperty('members_created_remote') - bug report 36622 */ )
					{
						$pmember = self::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id=" . self::$handles['member']->getProperty('member_id') ) );
	
						if ( !$pmember['partial_member_id'] )
						{
							$pmember = array( 
								'partial_member_id' => self::$handles['member']->getProperty('member_id'),
								'partial_date'		=> time(),
								'partial_email_ok'	=> ( self::$handles['member']->getProperty('email') == self::$handles['member']->getProperty('name') . '@' . self::$handles['member']->getProperty('joined') ) ? 0 : 1
								);
							self::DB()->insert( 'members_partial', $pmember );
							$pmember['partial_id'] = self::DB()->getInsertId();
						}
						
						self::instance()->getClass('output')->silentRedirect( ipsRegistry::$settings['base_url'] . 'app=core&module=global&section=register&do=complete_login&mid='.self::$handles['member']->getProperty('member_id').'&key='.$pmember['partial_date'] );
					}
				}

				//--------------------------------
				//  Is log in enforced?
				//--------------------------------

				if ( ! ( defined( 'IPS_IS_SHELL' ) && IPS_IS_SHELL === TRUE ) && ( ( ! IPS_IS_MOBILE_APP ) && ( self::$handles['member']->getProperty('member_group_id') == ipsRegistry::$settings['guest_group'] ) and (ipsRegistry::$settings['force_login'] == 1) && ipsRegistry::$request['section'] != 'register' ) )
				{
					if( ipsRegistry::$settings['logins_over_https'] AND ( !$_SERVER['HTTPS'] OR $_SERVER['HTTPS'] != 'on' ) )
					{
						//-----------------------------------------
						// Set referrer
						//-----------------------------------------
						
						if ( !my_getenv('HTTP_REFERER') OR stripos( my_getenv('HTTP_REFERER'), ipsRegistry::$settings['board_url'] ) === false )
						{
							$http_referrer = ( strtolower($_SERVER['HTTPS']) == 'on' ? "https://" : "http://" ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						}
						else
						{
							$http_referrer = my_getenv('HTTP_REFERER');
						}
			
						self::instance()->getClass('output')->silentRedirect( str_replace( 'http://', 'https://', ipsRegistry::$settings['base_url'] ) . 'app=core&module=global&section=login&referer=' . urlencode($http_referrer) );
					}
					
					ipsRegistry::$request['app']		= 'core';
					ipsRegistry::$request['module'] 	= 'login';
					ipsRegistry::$request['core']   	= 'login';
					ipsRegistry::$request['referer']	= ipsRegistry::$request['referer'] ? ipsRegistry::$request['referer'] : ( strtolower($_SERVER['HTTPS']) == 'on' ? "https://" : "http://" ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					if( is_file( DOC_IPS_ROOT_PATH . '/' . PUBLIC_DIRECTORY . '/style_css/' . ipsRegistry::getClass('output')->skin['_csscacheid'] . '/ipb_login_register.css' ) )
					{
						ipsRegistry::getClass('output')->addToDocumentHead( 'importcss', ipsRegistry::$settings['css_base_url'] . 'style_css/' . ipsRegistry::getClass('output')->skin['_csscacheid'] . '/ipb_login_register.css' );
					}
					
					$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'core' ) . "/modules_public/global/login.php", 'public_core_global_login' );
					$runme       = new $classToLoad( self::instance() );
					$runme->doExecute( self::instance() );
					exit;
				}
			}
			
			/* Have we entered an incorrect FURL that has no match? */
			if ( ipsRegistry::$settings['use_friendly_urls'] AND self::$_noFurlMatch === true )
			{
				self::getClass('output')->showError( 'incorrect_furl', 404, null, null, 404 );
			}
			else if( isset(ipsRegistry::$request['act']) AND ipsRegistry::$request['act'] == 'rssout' )
			{
				self::getClass('output')->showError( 'incorrect_furl', 404, null, null, 404 );
			}
		}
		
		IPSDebug::setMemoryDebugFlag( "Registry initialized" );
	}

	/**
	 * Loads application's core variables (if required)
	 *
	 * @return	@e void
	 */
	static public function _loadCoreVariables()
	{
		if ( ! ( isset( self::$_masterCoreVariables['__CLASS__'] ) AND is_object( self::$_masterCoreVariables['__CLASS__'] ) ) )
		{
			require_once( IPS_ROOT_PATH . 'extensions/coreVariables.php' );/*noLibHook*/

			/* Add handle */
			self::$_masterCoreVariables['__CLASS__'] = new coreVariables();
		}
	}

	/**
	 * Fetches apps core variable data
	 *
	 * @param	string		App dir
	 * @return	string		Core variable
	 */
	static public function _fetchCoreVariables( $type )
	{
		if ( ! isset( self::$_masterCoreVariables[ $type ] ) OR ! is_array( self::$_masterCoreVariables[ $type ] ) )
		{
			self::_loadCoreVariables();

			switch( $type )
			{
				case 'cache':
				case 'cacheload':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchCaches();
					self::$_masterCoreVariables['cache']     = is_array( $return['caches'] )    ? $return['caches'] : array();
					self::$_masterCoreVariables['cacheload'] = is_array( $return['cacheload'] ) ? $return['cacheload'] : array();
				break;
				case 'redirect':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchRedirects();
					self::$_masterCoreVariables['redirect'] = is_array( $return ) ? $return : array();
				break;
				case 'templates':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchTemplates();
					self::$_masterCoreVariables['templates'] = is_array( $return ) ? $return : array();
				break;
				case 'bitwise':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchBitwise();
					self::$_masterCoreVariables['bitwise'] = is_array( $return ) ? $return : array();
				break;
			}
		}

		return self::$_masterCoreVariables[ $type ];
	}

	/**
	 * Loads application's core variables (if required)
	 *
	 * @param	string		App key (dir.. that's directory, not duuhr)
	 * @return	@e void
	 */
	static public function _loadAppCoreVariables( $appDir )
	{
		$CACHE = $_LOAD = $_RESET = $_BITWISE = array();
		
		if ( ! isset( self::$_coreVariables[ $appDir ] ) )
		{
			$file = IPSLib::getAppDir( $appDir ) . '/extensions/coreVariables.php';
			
			if( is_file( $file ) )
			{
				require( $file );/*noLibHook*/

				/* Add caches */
				self::$_coreVariables[ $appDir ]['cache']     = ( isset($CACHE) AND is_array( $CACHE ) ) ? $CACHE : array();
				self::$_coreVariables[ $appDir ]['cacheload'] = ( isset($_LOAD) AND is_array( $_LOAD ) ) ? $_LOAD : array();

				/* Add redirect */
				self::$_coreVariables[ $appDir ]['redirect'] = ( isset($_RESET) AND is_array( $_RESET ) ) ? $_RESET : array();

				/* Add bitwise */
				self::$_coreVariables[ $appDir ]['bitwise']  = ( isset($_BITWISE) AND is_array( $_BITWISE ) ) ? $_BITWISE : array();
			}
		}
	}

	/**
	 * Fetches apps core variable data
	 *
	 * @param	string		App dir
	 * @param	string		Type of variable to return
	 * @return	string		Core variable
	 */
	static public function _fetchAppCoreVariables( $appDir, $type )
	{
		if ( !isset(self::$_coreVariables[ $appDir ][ $type ]) OR !is_array( self::$_coreVariables[ $appDir ][ $type ] ) )
		{
			self::_loadAppCoreVariables( $appDir );
		}

		return self::$_coreVariables[ $appDir ][ $type ];
	}

	/**
	 * Grabs apps bitwise array
	 *
	 * @param	string	$appDir
	 * @return	string	Core variables
	 */
	static public function fetchBitWiseOptions( $appDir )
	{
		if ( $appDir == 'global' )
		{
			return self::_fetchCoreVariables( 'bitwise' );
		}
		else
		{
			return self::_fetchAppCoreVariables( $appDir, 'bitwise' );
		}
	}

	/**
	 * Function for storing class handles to
	 * prevent having to re-initialize them constantly
	 *
	 * @param	string		Key
	 * @param	object		Object to store
	 */
	static public function setClass( $key='', $value='' )
	{
		self::checkForInit();

		if ( ! $key OR ! $value )
		{
			throw new Exception( "Missing a key or value" );
		}
		else if ( ! is_object( $value ) )
		{
			throw new Exception( "$value is not an object" );		
		}

		self::$classes[ $key ] = $value;
	}

	/**
	 * Function for retrieving class handles
	 * AUTOLOADED classes are as follows:
	 * KERNEL -> class_captcha
	 * KERNEL -> classTemplateEngine (templateEngine)
	 * CORE   -> localization class
	 *
	 * @param	string		Key
	 * @param	object		Retrieved object
	 */
	static public function getClass( $key )
	{		
		/* Do some magic here to retreive common classes without
		   having to initialize them first */
		if ( ! isset( self::$classes[ $key ] ) )
		{
			switch( $key )
			{
				default:
					throw new Exception( "$key is not an object" );
				break;
				case 'class_captcha':
					$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCaptcha.php', 'classCaptcha' );
					self::$classes['class_captcha'] = new $classToLoad( self::instance() );
				break;
				case 'templateEngine':
					$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
					self::$classes['templateEngine'] = new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
				break;
				case 'cacheSimple':
					$classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/cache/simple.php', 'classes_cache_simple' );
					self::$classes['cacheSimple'] = new $classToLoad();
				break;
				case 'class_localization':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_localization.php', 'class_localization' );
					self::$classes['class_localization'] = new $classToLoad( self::instance() );
				break;
				case 'memberStatus':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
					self::$classes['memberStatus'] = new $classToLoad( self::instance() );
				break;
				case 'IPSAdCode':
					/* Ads */
					if ( IPSLib::appIsInstalled( 'nexus' ) )
					{
						$systemvars = self::cache()->getCache('systemvars');

						if ( isset($systemvars['nexus_ads']) AND $systemvars['nexus_ads'] ) // Avoids loading the nexus_ads cache on every page load unless needed
						{
							require_once( IPSLib::getAppDir( 'nexus' ).'/sources/ads.php' );/*noLibHook*/
							$classToLoad = 'IPSAdCodeNexus';
						}
						else
						{
							self::$settings['ad_code_global_enabled'] = FALSE;
						}
					}
					
					if ( !isset( $classToLoad ) )
					{
						$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/ads.php", 'IPSAdCode' );
					}
										
					/* INIT ad class */
					self::$classes['IPSAdCode'] = new $classToLoad( self::instance() );
				break;
				/* Yes I know this is forums app and not global, but it's still called so much this is a good idea */
				case 'class_forums':
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
					self::$classes['class_forums'] = new $classToLoad( self::instance() );
					self::$classes['class_forums']->strip_invisible	= true;
					self::$classes['class_forums']->forumsInit();
				break;
				
				case 'classItemMarking':
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php', 'classItemMarking' );
					self::$classes['classItemMarking'] = new $classToLoad( self::instance() );
				break;
				
			}

			return self::$classes[ $key ];
		}
		else
		{
			return self::$classes[ $key ];
		}
	}

	/**
	 * Return a list of classes
	 * IN_DEV ONLY
	 *
	 * @return	mixed 		Array of items or false if IN_DEV is off
	 */
	public static function getLoadedClassesAsArray()
	{
		if ( ! IN_DEV )
		{
			return FALSE;
		}
		else
		{
			return array_keys( self::$classes );
		}
	}

	/**
	 * Shortcut function for accessing the registry
	 *
	 * @param	string	Key
	 * @return	object	Stored object
	 */
	public function __get( $key )
	{
		self::checkForInit();

		$_class = self::getClass( $key );

		if ( is_object( $_class ) )
		{
			return $_class;
		}
	}

	/**
	 * See if a class is loaded
	 *
	 * @param	string	Key
	 * @return	bool	Loaded or not
	 */
	static public function isClassLoaded( $key )
	{
		return ( isset( self::$classes[ $key ] ) ) ? TRUE : FALSE;
	}

	/**
	 * Get DB object
	 *
	 * @param	string	Key
	 * @return	object
	 */
	static public function DB( $key='' )
	{
		self::checkForInit();
		return self::$handles['db']->getDB( $key );
	}

	/**
	 * Get DB functions object
	 *
	 * @param	string	Key
	 * @return	object
	 */
	static public function dbFunctions( $key='' )
	{
		self::checkForInit();
		return self::$handles['db'];
	}

	/**
	 * Get Cache object
	 *
	 * @return	object
	 */
	static public function cache()
	{
		self::checkForInit();
		return self::$handles['caches'];
	}

	/**
	 * Get settings array
	 *
	 * @return	array
	 */
	static public function settings()
	{
		self::checkForInit();
		return ipsRegistry::$settings;
	}

	/**
	 * Fetch all the settings as a reference
	 *
	 * @return	array
	 */
	static public function &fetchSettings()
	{
		return ipsRegistry::$settings;
	}

	/**
	 * Fetch all request items as reference
	 *
	 * @return	array
	 */
	static public function &fetchRequest()
	{
		return ipsRegistry::$request;
	}

	/**
	 * Get Request array
	 *
	 * @return	array
	 */
	static public function request()
	{
		self::checkForInit();
		return ipsRegistry::$request;
	}

	/**
	 * Get Member object
	 *
	 * @return	object
	 */
	static public function member()
	{
		self::checkForInit();

		if( isset( self::$handles['member'] ) )
		{
			return self::$handles['member'];
		}
	}

	/**
	 * Get current application
	 *
	 * @return	string
	 */
	static public function getCurrentApplication()
	{
		return self::$current_application;
	}

	/**
	 * Get all applications
	 *
	 * @return	array
	 */
	public function getApplications()
	{
		return self::$applications;
	}

	/**
	 * Check to see if we've initialized
	 *
	 * @return	@e void
	 */
	static protected function checkForInit()
	{
		if ( self::$initiated !== TRUE )
		{
			throw new Exception('ipsRegistry has not been initiated. Do so by calling ipsRegistry::init()' );
		}
	}

	/**
	 * Grab the app class file
	 *
	 * @param	string		$app	Application to check for app class
	 * @return	mixed		Null if invalid or no app passed, otherwise app class object
	 */
	public static function getAppClass( $app='' )
	{
		if( !$app OR !is_string($app) )
		{
			return null;
		}

		# Load app class
		if ( ! isset( self::$app_class[ $app ] ) )
		{
			self::$app_class[ $app ] = null;

			$_file = IPSLib::getAppDir( $app ) . '/app_class_' . $app . '.php';
			$_name = 'app_class_' . $app;

			if ( is_file( $_file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $_file, $_name, $app );

				self::$app_class[ $app ] = new $classToLoad( self::instance() );
			}
		}
		
		return self::$app_class[ $app ];
	}
	
	/**
	 * Fix the $_SERVER['REQUEST_URI'] variable
	 *
	 * @return	@e void
	 */
	protected static function _fixRequestUri()
	{
		/* IIS rewrite */
		if( !empty($_SERVER['HTTP_X_ORIGINAL_URL']) )		// IIS 7 with Microsoft Rewrite module
		{
			$_SERVER['REQUEST_URI']	= $_SERVER['HTTP_X_ORIGINAL_URL'];
		}
		else if( !empty($_SERVER['HTTP_X_REWRITE_URL']) )	// IIS with ISAPI_Rewrite
		{
			$_SERVER['REQUEST_URI']	= $_SERVER['HTTP_X_REWRITE_URL'];
		}
		
		/* Got it? */
		if ( ! isset( $_SERVER['REQUEST_URI'] ) )
		{
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			
			if ( isset( $_SERVER['QUERY_STRING'] ) )
			{ 
				/* Ok, can't use bot detection as that is set up later, so this should do it
				   Basically facebook bot encodes all / to %2F after query string, so LIKE button
				   returns board index and when clicking link from FB brings up board index as URL is ?%2Ftopic%2F4314-test-digest%2F */
				if ( substr( $_SERVER['QUERY_STRING'], 0, 3 ) == '%2F' )
				{
					$_SERVER['QUERY_STRING'] = str_replace( '%2F', '/', $_SERVER['QUERY_STRING'] );
				}
				
				$_SERVER['REQUEST_URI']	.= '?' . $_SERVER['QUERY_STRING'];
			}
		}
		else
		{
			if ( isset( $_SERVER['QUERY_STRING'] ) )
			{ 
				/* Facebook thing */
				if ( substr( $_SERVER['QUERY_STRING'], 0, 3 ) == '%2F' )
				{
					$_SERVER['REQUEST_URI'] = str_replace( '%2F', '/', $_SERVER['REQUEST_URI'] );
				}
			}
		}
	}

	/**
	 * INIT furls
	 * Performs set up and figures out any incoming links
	 *
	 * @return	@e void
	 */
	protected static function _fUrlInit()
	{
		/**
		 * Fix request uri
		 */
		self::_fixRequestUri();
		
		if ( ipsRegistry::$settings['use_friendly_urls'] )
		{
			/* Grab and store accessing URL */
			self::$_uri = preg_replace( "/s=(&|$)/", '', str_replace( '/?', '/' . IPS_PUBLIC_SCRIPT . '?', $_SERVER['REQUEST_URI'] ) );
			
			$_urlBits = array();
			
			/* Grab FURL data... */
			if ( ! IN_DEV AND is_file( DOC_IPS_ROOT_PATH . 'cache/furlCache.php' ) )
			{
				$templates = array();
				include( DOC_IPS_ROOT_PATH . 'cache/furlCache.php' );/*noLibHook*/
				self::$_seoTemplates = $templates;
			}
			else
			{
				/* Attempt to write it */
				self::$_seoTemplates = IPSLib::buildFurlTemplates();
				
				try
				{
					IPSLib::cacheFurlTemplates();
				}
				catch( Exception $e )
				{
				}
			}

			if ( is_array( self::$_seoTemplates ) AND count( self::$_seoTemplates ) )
			{ 
				$uri = $_SERVER['REQUEST_URI']  ? $_SERVER['REQUEST_URI']  : @getenv('REQUEST_URI');
				
				/* Bug 21295 - remove known URL from test URI */
				$_t        = !empty(ipsRegistry::$settings['board_url']) ? @parse_url( ipsRegistry::$settings['board_url'] ) : @parse_url( ipsRegistry::$settings['base_url'] );
				$_toTest   = ( $_t['path'] AND $_t['path'] != '/' ) ? preg_replace( "#^{$_t['path']}#", '', $uri ) : str_replace( $_t['scheme'] . '://' . $_t['host'], '', $uri );
				$_404Check = $_toTest; //We want to retain any /index.php for this test later in this block of code
				$_toTest   = str_ireplace( array( '//' . IPS_PUBLIC_SCRIPT . '?', '/' . IPS_PUBLIC_SCRIPT . '?', '/' . IPS_PUBLIC_SCRIPT ), '', $_toTest );
				$_gotMatch = false;

				foreach( self::$_seoTemplates as $key => $data )
				{
					if ( empty( $data['in']['regex'] ) )
					{
						continue;
					}

					if ( preg_match( $data['in']['regex'], $_toTest, $matches ) )
					{
						$_gotMatch = true;
						
						if ( is_array( $data['in']['matches'] ) )
						{
							foreach( $data['in']['matches'] as $_replace )
							{
								$k = IPSText::parseCleanKey( $_replace[0] );

								if ( strpos( $_replace[1], '$' ) !== false )
								{
									$v = IPSText::parseCleanValue( $matches[ intval( str_replace( '$', '', $_replace[1] ) ) ] );
								}
								else
								{
									$v = IPSText::parseCleanValue( $_replace[1] );
								}

								$_GET[ $k ]     = $v;
								$_POST[ $k ]    = $v;
								$_REQUEST[ $k ] = $v;
								$_urlBits[ $k ] = $v;

								ipsRegistry::$request[ $k ]	= $v;
							}
						}

						if ( strpos( $_toTest, self::$_seoTemplates['__data__']['varBlock'] ) !== false )
						{
							/* Changed how the input variables are parsed based on feedback in bug report 24907
							   @link http://community.invisionpower.com/tracker/issue-24907-member-list-pagination-not-work-with-checkbox
							   Input variables now preserve array depth properly as a result */
							$_parse	= substr( $_toTest, strpos( $_toTest, self::$_seoTemplates['__data__']['varBlock'] ) + strlen( self::$_seoTemplates['__data__']['varBlock'] ) );
							$_data	= explode( self::$_seoTemplates['__data__']['varSep'], $_parse );
							$_query	= '';

							for( $i = 0, $j = count($_data); $i < $j; $i++ )
							{
								$_query	.= ( $i % 2 == 0 ? '&' : '=' ) . $_data[$i];
							}

							$_data = array();
							parse_str($_query, $_data);
							$_data = IPSLib::parseIncomingRecursively($_data);
							
							foreach( $_data as $k => $v )
							{
								$_GET[ $k ]     = $v;
								$_POST[ $k ]    = $v;
								$_REQUEST[ $k ] = $v;
								$_urlBits[ $k ] = $v;

								ipsRegistry::$request[ $k ]	= $v;
							}
						}
						
						break;
					}
				}
				
				/* Check against the original request for 404 error */
				$_404checkPass	= false;
				
				if( !strstr( $_404Check, '&' ) AND !strstr( $_404Check, '=' ) AND ( strstr( $_404Check, IPS_PUBLIC_SCRIPT . '?/' ) OR !strstr( $_404Check, '.php' ) ) )
				{
					$_404checkPass	= true;
				}
				
				if( strstr( $_404Check, '/' . IPS_PUBLIC_SCRIPT ) )
				{
					if( preg_match( "#(.+?)/" . preg_quote( IPS_PUBLIC_SCRIPT ) . "#", $_404Check, $matches ) AND !is_file( DOC_IPS_ROOT_PATH . preg_replace( '/(.+?)\?.+/', '$1', $_404Check ) ) )
					{
						$_404checkPass	= true;
					}
				}
				/* Got a match? */
				if (
					! defined('CCS_GATEWAY_CALLED')
					AND ! defined('IPS_ENFORCE_ACCESS')
					AND ! defined('LOFIVERSION_CALLED')
					AND IPS_IS_MOBILE_APP === false
					AND IPS_DEFAULT_PUBLIC_APP == 'forums'
					AND $_gotMatch === false
					AND $_toTest
					AND $_toTest != '/'
					AND $_toTest != '/?'
					AND $_404checkPass
				  )
				{
					self::$_noFurlMatch = true;
				}
				
				//-----------------------------------------
				// If using query string furl, extract any
				// secondary query string.
				// Ex: http://localhost/index.php?/path/file.html?key=value
				// Will pull the key=value properly
				//-----------------------------------------
				
				$_qmCount = substr_count( $_toTest, '?' );
				
				/* We don't want to check for secondary query strings in the ACP */
				if ( ! IN_ACP && $_qmCount > 1 )
				{ 
					$_secondQueryString	= substr( $_toTest, strrpos( $_toTest, '?' ) + 1 );
					$_secondParams		= explode( '&', $_secondQueryString );
					
					if( count($_secondParams) )
					{
						foreach( $_secondParams as $_param )
						{
							list( $k, $v )	= explode( '=', $_param );
							
							$k	= IPSText::parseCleanKey( $k );
							$v	= IPSText::parseCleanValue( $v );

							$_GET[ $k ]     = $v;
							$_REQUEST[ $k ] = $v;
							$_urlBits[ $k ] = $v;

							ipsRegistry::$request[ $k ]	= $v;
						}
					}
				}

				/* Process URL bits for extra ? in them */
				/* We don't want to check for secondary query strings in the ACP */
				if ( ! IN_ACP && is_array( $_GET ) AND count( $_GET ) )
				{
					foreach( $_GET as $k => $v )
					{
						if ( ! is_array( $v ) AND strstr( $v, '?') )
						{
							list( $rvalue, $more ) = explode( '?', $v );

							if ( $rvalue AND $more )
							{
								//$k	= IPSText::parseCleanKey( $_k );
								//$v	= IPSText::parseCleanValue( $_v );

								/* Reset key with correct value */
								$_v = IPSText::parseCleanValue( $rvalue );
								
								$_GET[ $k ]     = $_v;
								$_REQUEST[ $k ] = $_v;
								$_urlBits[ $k ] = $_v;

								ipsRegistry::$request[ $k ]	= $_v;
								
								/* Now add in the other value */
								if ( strstr( $more, '=' ) )
								{
									list( $_k, $_v ) = explode( '=', $more );
									
									if ( $_k and $_v )
									{
										$_GET[ $_k ]     = $_v;
										$_REQUEST[ $_k ] = $_v;
										$_urlBits[ $_k ] = $_v;
			
										ipsRegistry::$request[ $_k ]	= $_v;
									}
								}
							}
						}
					}
				}
			}
			
			/* Reformat basic URL */
			if ( is_array( $_urlBits ) )
			{
				ipsRegistry::$settings['query_string_real'] = '';
				
				foreach( $_urlBits as $k => $v )
				{
					ipsRegistry::$settings['query_string_real'] .= '&' . $k . '=' . $v;
				}
				
				ipsRegistry::$settings['query_string_real'] = trim( ipsRegistry::$settings['query_string_real'], '&' );
			}
		}
	}

	/**
	 * Complete furls
	 * Redirects if settings permit
	 *
	 * @return	@e void
	 */
	protected static function _fUrlComplete()
	{
		/* INIT */
		$_template = FALSE;
		
		if ( IPS_IS_TASK === TRUE || IPS_IS_AJAX === TRUE )
		{
			return;
		}
		
		/* www.hostname.com vs hostname.com - need to ensure we are at the correct URL for AJAX purposes */
		if ( ! defined('CCS_GATEWAY_CALLED') && ipsRegistry::$request['request_method'] == 'get' )
		{
			$_requestedHost		= $_SERVER['HTTP_HOST'];
			$_configuredHost	= parse_url( ipsRegistry::$settings['board_url'], PHP_URL_HOST );
			$uri				= $_SERVER['REQUEST_URI']  ? $_SERVER['REQUEST_URI']  : @getenv('REQUEST_URI');
			$_t					= !empty(ipsRegistry::$settings['board_url']) ? @parse_url( ipsRegistry::$settings['board_url'] ) : @parse_url( ipsRegistry::$settings['base_url'] );
			$_toTest			= ( $_t['path'] AND $_t['path'] != '/' ) ? preg_replace( "#^{$_t['path']}#", '', $uri ) : str_replace( $_t['scheme'] . '://' . $_t['host'], '', $uri );
			
			if( strpos( $_configuredHost, 'www.' ) === 0 AND strpos( $_requestedHost, 'www.' ) !== 0 )
			{
				ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::$settings['board_url'] . $_toTest, '', true );
			}
			else if( strpos( $_configuredHost, 'www.' ) !== 0 AND strpos( $_requestedHost, 'www.' ) === 0 )
			{
				ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::$settings['board_url'] . $_toTest, '', true );
			}
		}
		
		if ( ipsRegistry::$settings['use_friendly_urls'] AND ipsRegistry::$settings['seo_r_on'] AND is_array( self::$_seoTemplates ) AND self::$_uri )
		{ 
			/* SEO Tweak - if default app is forums then don't bother with act=idx nonsense */
			if ( IPS_DEFAULT_APP == 'forums' AND ipsRegistry::$request['request_method'] == 'get' AND ! ipsRegistry::$settings['actidx_override'] )
			{ 
				if ( ! stristr( self::$_uri, 'interface/' ) AND preg_match( '#(/index$|act=idx(\?)?)$#i', self::$_uri ) )
				{ 
					ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::$settings['_original_base_url'], '', true, 'act=idx' );
				}
			}
			
			/* Quick check */
			if ( strpos( self::$_uri, '=' ) !== false )
			{
				/* Got a template? */
				foreach( self::$_seoTemplates as $key => $data )
				{
					//-----------------------------------------
					// This was checking against key, which was "idmshowfile" for IP.Downloads
					// but that isn't in the URL.  Changed 3.3.2010, see report 20900.
					//-----------------------------------------
					
					if ( !empty($data['out'][0]) AND preg_match( $data['out'][0], self::$_uri ) )
					{
						$_template = $key;
						break;
					}
				}
				
				/* Goddit? */
				if ( $_template !== FALSE && ! IN_ACP )
				{
					if ( self::$_seoTemplates[ $_template ]['app'] AND self::$_seoTemplates[ $_template ]['allowRedirect'] )
					{ 
						/* Load information file */
						if( is_file( IPSLib::getAppDir( self::$_seoTemplates[ $_template ]['app'] ) . '/extensions/furlRedirect.php' ) )
						{
							$_class = IPSLib::loadLibrary( IPSLib::getAppDir( self::$_seoTemplates[ $_template ]['app'] ) . '/extensions/furlRedirect.php', 'furlRedirect_' . self::$_seoTemplates[ $_template ]['app'], self::$_seoTemplates[ $_template ]['app'] );
							$_furl  = new $_class( ipsRegistry::instance() );

							$_furl->setKeyByUri( self::$_uri );
							$_seoTitle = $_furl->fetchSeoTitle();

							if ( $_seoTitle && empty( ipsRegistry::$request['debug'] ) )
							{
								/* redirect... */
								// Per recommendations, removing user option and forcing 301 redirect
								//$_send301 = ( ipsRegistry::$settings['seo_r301'] ) ? TRUE : FALSE;

								ipsRegistry::getClass('output')->silentRedirect( self::$_uri, $_seoTitle, true, $_template );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Rebuild URL data from incoming sources.
	 * Called after _fUrlInit
	 *
	 * @return	@e void
	 */
	protected static function _manageIncomingURLs()
	{
		//-----------------------------------------
		// Build master load requests
		//-----------------------------------------

		$_RESET = self::_fetchCoreVariables( 'redirect' );

		if ( is_array( $_RESET ) )
		{
			foreach( $_RESET as $k => $v )
			{
				$_GET[ $k ]  = $v;
				$_POST[ $k ] = $v;
				$_REQUEST[ $k ] = $v;
				ipsRegistry::$request[ $k ]	= $v;
			}
		}
	}

	/**
	 * Set up request redirect stuff
	 *
	 * @return	@e void
	 * @author	MattMecham
	 */
	protected static function _setUpAppData()
	{
		# Finalize the app component constants
		$_REQUEST['app'] = str_replace( '&module', '', $_REQUEST['app'] );
		$_appcomponent = preg_replace( '/[^a-zA-Z0-9\-\_]/', "" , ( isset( $_REQUEST['app'] ) && trim( $_REQUEST['app'] ) ? $_REQUEST['app'] : IPS_DEFAULT_APP ) );
		
		/**
		 * @link	http://community.invisionpower.com/tracker/issue-21825-urls/
		 */
		if( is_array($_appcomponent) )
		{
			$_appcomponent	= array_shift($_appcomponent);
		}

		define( 'IPS_APP_COMPONENT', ( $_appcomponent ) ? $_appcomponent : IPS_DEFAULT_APP );
	}

	/**
	 * Parse any current app redirects
	 *
	 * @return void
	 */
	protected static function _parseAppResets()
	{
		//-----------------------------------------
		// Build app data and APP specific load requests
		//-----------------------------------------

		if ( IPS_AREA != 'admin' )
		{
			$_RESET = self::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'redirect' );

			if ( is_array( $_RESET ) AND count( $_RESET ) )
			{
				foreach( $_RESET as $k => $v )
				{
					$_GET[ $k ]  = $v;
					
					self::$request[ $k ]	= $v;
				}
			}
		}
	}

	/**
	 * Set up application data, etc.
	 *
	 * @return	@e void
	 * @author	MattMecham
	 */
	protected static function _finalizeAppData()
	{
		//-----------------------------------------
		// Run the app class post output func
		//-----------------------------------------

		$app_class	= self::getAppClass( IPS_APP_COMPONENT );

		if ( $app_class AND method_exists( $app_class, 'afterOutputInit' ) )
		{
			$app_class->afterOutputInit( self::instance() );
		}
		
		//-----------------------------------------
		// Version numbers
		//-----------------------------------------

		if ( strpos( self::$acpversion , '.' ) !== false )
		{
			list( $n, $b, $r ) = explode( ".", self::$acpversion );
		}
		else
		{
			$n = $b = $r = '';
		}

		self::$vn_full         = self::$acpversion;
		self::$acpversion      = $n;
		self::$vn_build_date   = $b;
		self::$vn_build_reason = $r;

		# Figure out default modules, etc
		$_module = IPSText::alphanumericalClean( ipsRegistry::$request['module'] );
		$_first  = '';

		//-----------------------------------------
		// Set up some defaults
		//-----------------------------------------

		ipsRegistry::$current_application  = IPS_APP_COMPONENT;

		if ( IPS_AREA == 'admin' )
		{
			//-----------------------------------------
			// Application: Do we have permission?
			//-----------------------------------------
			
			if( ipsRegistry::$request['module'] != 'login' )
			{
				ipsRegistry::getClass('class_permissions')->return = 0;
				ipsRegistry::getClass('class_permissions')->checkForAppAccess( IPS_APP_COMPONENT );
				ipsRegistry::getClass('class_permissions')->return = 1;
			}

			//-----------------------------------------
			// Got a module
			//-----------------------------------------

			if ( ipsRegistry::$request['module'] == 'ajax' )
			{
				$_module = 'ajax';
			}
			else
			{
				$fakeApps	= ipsRegistry::getClass('output')->fetchFakeApps();

				foreach( ipsRegistry::$modules as $app => $items )
				{
					if ( is_array( $items ) )
					{
						foreach( $items as $data )
						{
							if ( $data['sys_module_admin'] AND ( $data['sys_module_application'] == ipsRegistry::$current_application ) )
							{
								if ( ! $_first )
								{
									# Got permission for this one?
									ipsRegistry::getClass('class_permissions')->return = 1;

									if ( ipsRegistry::getClass('class_permissions')->checkForModuleAccess( $data['sys_module_application'], $data['sys_module_key'] ) === TRUE )
									{
										if ( is_dir( IPSLib::getAppDir( $data['sys_module_application'] ) . "/modules_admin/{$data['sys_module_key']}" ) === TRUE )
										{
											$isFakeApp	= false;

											foreach( $fakeApps as $tab => $apps )
											{
												foreach( $apps as $thisApp )
												{
													if( $thisApp['app'] == $app AND $thisApp['module'] == $data['sys_module_key'] )
													{
														$isFakeApp	= true;
													}
												}
											}

											if( !$isFakeApp )
											{
												$_first = $data['sys_module_key'];
											}
										}
									}

									ipsRegistry::getClass('class_permissions')->return = 0;
								}

								if ( ipsRegistry::$request['module'] == $data['sys_module_key'] )
								{
									$_module = $data['sys_module_key'];
									break;
								}
							}
						}
					}
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Got a module?
			//-----------------------------------------

			if ( $_module == 'ajax' )
			{
				$_module = 'ajax';
			}
			else
			{
				foreach( ipsRegistry::$modules as $app => $items )
				{
					if ( is_array( $items ) )
					{
						foreach( $items as $data )
						{
							if ( ! $data['sys_module_admin'] AND ( $data['sys_module_application'] == ipsRegistry::$current_application ) )
							{
								if ( ! $_first )
								{
									$_first = $data['sys_module_key'];
								}

								if ( $_module == $data['sys_module_key'] )
								{
									$_module = $data['sys_module_key'];
									break;
								}
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Finish off...
		//-----------------------------------------

		ipsRegistry::$current_module  = ( $_module ) ? $_module : $_first;
		ipsRegistry::$current_section = ( ipsRegistry::$request['section'] ) ? ipsRegistry::$request['section'] : '';
		
		/* Clean */
		ipsRegistry::$current_module = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		ipsRegistry::$current_section = IPSText::alphanumericalClean( ipsRegistry::$current_section );
		
		IPSDebug::addMessage( "Setting current module to: " . ipsRegistry::$current_module . " and current section to: " . ipsRegistry::$current_section );

		if ( IPS_AREA == 'admin' )
		{
			//-----------------------------------------
			// Module: Do we have permission?
			//-----------------------------------------

			ipsRegistry::getClass('class_permissions')->return = 0;
			ipsRegistry::getClass('class_permissions')->checkForModuleAccess( ipsRegistry::$current_application, ipsRegistry::$current_module );
			ipsRegistry::getClass('class_permissions')->return = 1;
		}
	}

	/**
	 * Set up settings
	 *
	 * @return	@e void
	 * @author	MattMecham
	 */
	protected function setUpSettings()
	{
		$settings_cache = self::$handles['caches']->getCache('settings');

		if ( ! is_array( $settings_cache ) OR ! count( $settings_cache ) )
		{
			throw new Exception( "Could not initiate the registry, the settings cache is empty or missing" );
		}

		foreach( $settings_cache as $k => $v )
		{
			ipsRegistry::$settings[$k] = str_replace( '&#092;', '\\', $v );
		}

		//-----------------------------------------
		// Back up base URL
		//-----------------------------------------

		ipsRegistry::$settings['base_url']           = !empty(ipsRegistry::$settings['board_url']) ? ipsRegistry::$settings['board_url'] : ipsRegistry::$settings['base_url'];
		ipsRegistry::$settings['board_url']          = !empty(ipsRegistry::$settings['board_url']) ? ipsRegistry::$settings['board_url'] : ipsRegistry::$settings['base_url'];
		ipsRegistry::$settings['_original_base_url'] = ipsRegistry::$settings['base_url'];
		
		//-----------------------------------------
		// Fetch correct current URL
		//-----------------------------------------
		
		ipsRegistry::$settings['this_url']		 	= ( strtolower($_SERVER['HTTPS']) == 'on' ? "https://" : "http://" ) . my_getenv('HTTP_HOST') . str_replace( array( '<', '>', '(', ')' ), '-', my_getenv('REQUEST_URI') );
		
		//-----------------------------------------
		// Make a safe query string (we build the query string in furlinit)
		//-----------------------------------------

		ipsRegistry::$settings['query_string_safe'] = str_replace( '&amp;amp;', '&amp;', IPSText::parseCleanValue( urldecode( !empty(ipsRegistry::$settings['query_string_real']) ? ipsRegistry::$settings['query_string_real'] : my_getenv('QUERY_STRING') ) ) );
		ipsRegistry::$settings['query_string_real'] = str_replace( '&amp;'    , '&'    , ipsRegistry::$settings['query_string_safe'] );

		//-----------------------------------------
		// Format it..
		//-----------------------------------------

		ipsRegistry::$settings['query_string_formatted'] = str_replace( ipsRegistry::$settings['board_url'] . '/index.'.ipsRegistry::$settings['php_ext'].'?', '', ipsRegistry::$settings['query_string_safe'] );
		ipsRegistry::$settings['query_string_formatted'] = preg_replace( "#s=([a-z0-9]){32}#", '', ipsRegistry::$settings['query_string_formatted'] );
		ipsRegistry::$settings['query_string_formatted'] = preg_replace( '#settingNewSkin=\d+?($|&)#', '', ipsRegistry::$settings['query_string_formatted'] );
		ipsRegistry::$settings['query_string_formatted'] = preg_replace( '#langid=\d+?($|&)#', '', ipsRegistry::$settings['query_string_formatted'] );

		//-----------------------------------------
		// Default settings
		//-----------------------------------------

		ipsRegistry::$settings['_admin_link'] = ipsRegistry::$settings['base_url'] . '/' . CP_DIRECTORY . '/index.php';
		if ( ipsRegistry::$settings['logins_over_https'] )
		{
			ipsRegistry::$settings['_admin_link'] = str_replace( 'http://', 'https://', ipsRegistry::$settings['_admin_link'] );
		}
		
		ipsRegistry::$settings['max_user_name_length'] = ipsRegistry::$settings['max_user_name_length'] ? ipsRegistry::$settings['max_user_name_length'] : 26;

		# Upload
		ipsRegistry::$settings['upload_dir'] = ipsRegistry::$settings['upload_dir'] ? ipsRegistry::$settings['upload_dir'] : DOC_IPS_ROOT_PATH . 'uploads';
		ipsRegistry::$settings['upload_url'] = ipsRegistry::$settings['upload_url'] ? ipsRegistry::$settings['upload_url'] : ipsRegistry::$settings['base_url'] . '/uploads';

		# Char set
		ipsRegistry::$settings['gb_char_set'] = ipsRegistry::$settings['gb_char_set'] ? ipsRegistry::$settings['gb_char_set'] : 'UTF-8';

		if( ! defined( 'IPS_DOC_CHAR_SET' ) )
		{
			define( 'IPS_DOC_CHAR_SET', strtoupper( ipsRegistry::$settings['gb_char_set'] ) );
			
			if( function_exists('mb_internal_encoding') AND function_exists('mb_list_encodings') )
			{
				$valid_encodings	= array();
				$valid_encodings	= mb_list_encodings();
	
				if ( count($valid_encodings) )
				{
					if ( in_array( strtoupper(IPS_DOC_CHAR_SET), $valid_encodings ) )
					{
						mb_internal_encoding( strtoupper(IPS_DOC_CHAR_SET) );
					}
				}
			}
		}
		
		# Define cache path 
		define( 'IPS_CACHE_PATH', ( ! empty( ipsRegistry::$settings['ipb_cache_path'] ) ) ? ipsRegistry::$settings['ipb_cache_path'] : DOC_IPS_ROOT_PATH );
		
		# Make sure ENFORCE ACCESS is defined
		if ( ! defined( 'IPS_ENFORCE_ACCESS' ) )
		{
			define( 'IPS_ENFORCE_ACCESS', FALSE );
		}
		
		/* Set up default status update settings */
		ipsRegistry::$settings['su_max_chars']   = ( isset( ipsRegistry::$settings['su_max_chars'] ) )   ? ipsRegistry::$settings['su_max_chars']   : 500;
		ipsRegistry::$settings['su_max_replies'] = ( isset( ipsRegistry::$settings['su_max_replies'] ) ) ? ipsRegistry::$settings['su_max_replies'] : 100;
		ipsRegistry::$settings['su_enabled']	 = ( isset( ipsRegistry::$settings['su_enabled'] ) )     ? ipsRegistry::$settings['su_enabled']     : 1;
		
		# If htaccess mod rewrite is on, enforce path_info usage
		
		ipsRegistry::$settings['url_type'] = ( ipsRegistry::$settings['htaccess_mod_rewrite'] ) ? 'path_info' : ipsRegistry::$settings['url_type'];

		# Facebook receiver location
		ipsRegistry::$settings['fbc_xdlocation'] = ipsRegistry::$settings['_original_base_url'] . '/interface/facebook/xd_receiver.php';
		ipsRegistry::$settings['fb_req_perms']   = 'email,publish_stream,read_stream';
		ipsRegistry::$settings['fb_locale']      = ( ipsRegistry::$settings['fb_locale'] ) ? ipsRegistry::$settings['fb_locale'] : 'en_US';
		
		# If we can't write to our /cache/tmp directory, turn off minify., It would still work, but be horribly inefficient.
		if ( ipsRegistry::$settings['use_minify'] AND ! is_writeable( IPS_CACHE_PATH . 'cache/tmp' ) )
		{
			ipsRegistry::$settings['_use_minify'] = ipsRegistry::$settings['use_minify'];
			ipsRegistry::$settings['use_minify']  = 0;
		}
		# Also turn it off if set_include_path is not enabled
		if ( ipsRegistry::$settings['use_minify'] and !function_exists( 'set_include_path' ) )
		{
			ipsRegistry::$settings['use_minify']  = 0;
		}
		
		# If we've turned on 301 redirects, then ensure headers are printed
		if ( ! ipsRegistry::$settings['print_headers'] )
		{
			if ( ipsRegistry::$settings['use_friendly_urls'] AND ipsRegistry::$settings['seo_r_on'] )
			{
				ipsRegistry::$settings['print_headers'] = 1;
			}
		}
		
		# If rep is set to likes, force show points or cache never builds
		if ( ipsRegistry::$settings['reputation_point_types'] == 'like' )
		{
			ipsRegistry::$settings['reputation_show_content'] = 1;
		}
		
		# URL shortener
		if ( ! defined( 'IPS_URL_SHORTEN_SERVICE') )
		{
			define( 'IPS_URL_SHORTEN_SERVICE', 'topic' );
		}
	}

	/**
	 * Check caches
	 *
	 * @return	@e void
	 * @author	MattMecham
	 */
	protected function checkCaches()
	{
		//-----------------------------------------
		// Check app cache data
		//-----------------------------------------

		# Apps
		$app_cache = self::$handles['caches']->getCache('app_cache');

		# Modules...
		self::$modules = self::$handles['caches']->getCache('module_cache');

		if ( ! count( $app_cache ) OR ! count( self::$modules ) )
		{
			self::$handles['caches']->rebuildCache( 'app_cache', 'global' );
			self::$handles['caches']->rebuildCache( 'module_cache', 'global' );

			$app_cache     = self::$handles['caches']->getCache('app_cache');
			self::$modules = self::$handles['caches']->getCache('module_cache');
		}

		//-----------------------------------------
		// Build app data and APP specific load requests
		//-----------------------------------------

		foreach( $app_cache as $_app_dir => $_app_data )
		{
			if ( !IPS_IS_TASK AND ( IPS_AREA == 'public' && ! $_app_data['app_public_title'] ) )
			{
				continue;
			}

			$_app_data['app_public_title'] = ( $_app_data['app_public_title'] ) ? $_app_data['app_public_title'] : $_app_data['app_title'];
			self::$applications[ $_app_dir ] = $_app_data;
		}
		
		/* Sort by position */
		uasort( self::$applications, 'ipsRegistry::_appPositionSort' );
		
		# Modules by section...
		foreach( self::$modules as $_app_dir => $_modules )
		{
			foreach( $_modules as $_data )
			{
				self::$modules_by_section[ $_app_dir ][ $_data['sys_module_key'] ] = $_data;
			}
		}

		//-----------------------------------------
		// System vars and group
		//-----------------------------------------

		$systemvars_cache = self::$handles['caches']->getCache('systemvars');

		if ( ! isset( $systemvars_cache ) OR ! isset( $systemvars_cache['task_next_run']) )
		{
			$update 						= array( 'task_next_run' => time() );
			$update['loadlimit'] 			= ( $systemvars_cache['loadlimit'] )           ? $systemvars_cache['loadlimit'] : 0;
			$update['mail_queue'] 			= ( $systemvars_cache['mail_queue'] )          ? $systemvars_cache['mail_queue'] : 0;
			$update['last_virus_check'] 	= ( $systemvars_cache['last_virus_check'] )    ? $systemvars_cache['last_virus_check'] : 0;
			$update['last_deepscan_check'] 	= ( $systemvars_cache['last_deepscan_check'] ) ? $systemvars_cache['last_deepscan_check'] : 0;

			self::$handles['caches']->setCache( 'systemvars', $update, array( 'donow' => 1, 'array' => 1 ) );
		}

		$group_cache = self::$handles['caches']->getCache('group_cache');

		if ( ! is_array( $group_cache ) OR ! count( $group_cache ) )
		{
			$this->cache()->rebuildCache( 'group_cache', 'global' );
		}

		//-----------------------------------------
		// User agent caches
		//-----------------------------------------

		$useragent_cache = $this->cache()->getCache('useragents');

		if ( ! is_array( $useragent_cache ) OR ! count( $useragent_cache ) )
		{
			$this->cache()->rebuildCache( 'useragents', 'global' );
		}

		//-----------------------------------------
		// Output formats
		//-----------------------------------------

		$outputformats_cache = $this->cache()->getCache('outputformats');

		if ( ! is_array( $outputformats_cache ) OR ! count( $outputformats_cache ) )
		{
			$this->cache()->rebuildCache( 'outputformats', 'global' );
		}

		//-----------------------------------------
		// Hooks cache
		//-----------------------------------------

		if ( $this->cache()->exists('hooks') !== TRUE )
		{
			$this->cache()->rebuildCache( 'hooks', 'global' );
		}
		
		//-----------------------------------------
		// Version numbers
		//-----------------------------------------

		$version_numbers = $this->cache()->getCache('vnums');

		if ( ! is_array( $version_numbers ) OR ! count( $version_numbers ) )
		{
			$this->cache()->rebuildCache( 'vnums', 'global' );
			
			$version_numbers = $this->cache()->getCache('vnums');
		}
		
		/* Set them */
		if ( ! defined('IPB_VERSION' ) )
		{
			define( 'IPB_VERSION'     , $version_numbers['human'] );
			ipsRegistry::$version	= IPB_VERSION;
		}
		
		if ( ! defined('IPB_LONG_VERSION') )
		{
			define( 'IPB_LONG_VERSION', $version_numbers['long'] );
			ipsRegistry::$acpversion	= IPB_LONG_VERSION;
			ipsRegistry::$vn_full		= IPB_LONG_VERSION;
		}
		
		//-----------------------------------------
		// Any to rebuild?
		//-----------------------------------------
		
		if( count($this->cache()->fetchRebuildList()) )
		{
			foreach( $this->cache()->fetchRebuildList() as $_toRebuild )
			{
				$this->cache()->rebuildCache( $_toRebuild );
			}
		}
	}
	
	/**
	 * Sort by position
	 *
	 * @return	integer
	 */
	protected static function _appPositionSort( $a, $b )
	{
		$a['app_position'] = ( isset( $a['app_position'] ) ) ? $a['app_position'] : 0;
		$b['app_position'] = ( isset( $b['app_position'] ) ) ? $b['app_position'] : 0;
		
		return ( $a['app_position'] > $b['app_position'] ) ? +1 : -1;
	}
}


/**
* Base Database class
*/
class ips_DBRegistry
{
	/**
	 * Database instance
	 *
	 * @var		object
	 */
	private static $instance;

	/**
	 * Generic data storage for each class that extends ips_base_Registry
	 *
	 * @var		array
	 */
	protected static $data_store = array();

	/**
	 * Variables array
	 *
	 * @var		array
	 */
	protected static $vars = array();

	/**
	 * DB Objects
	 *
	 * @var		array
	 */
	protected static $dbObjects = array();

	/**
	 * DB Prefixes
	 *
	 * @var		array
	 */
	protected static $dbPrefixes = array( '__default__' => '' );

	/**
	 * DB Drivers
	 *
	 * @var		array
	 */
	protected static $dbDrivers = array( '__default__' => '' );

	/**
	 * DB key
	 *
	 * @var		string
	 */
	protected static $defaultKey = '__default__';
	
	/**
	 * Cache files tried to load
	 *
	 * @var		array
	 */
	protected static $_queryFilesTriedToLoad = array();
	
	/**
	 * Cache file parsed names
	 *
	 * @var		array
	 */
	protected static $_queryFilesNames = array();

	/**
	 * Loaded query files
	 * Initialize singleton
	 *
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Fetch query file, if available
	 *
	 * @param	string		'public' or 'admin'
	 * @param	string		App dir
	 * @param	string		Key
	 * @return	boolean
	 */
	static public function loadQueryFile( $where, $app, $key='' )
	{
		$key    = ( $key ) ? $key : self::$defaultKey;
		$where  = ( $where == 'admin' ) ? 'admin' : 'public';
		$driver = self::getDriverType( $key );
		
		/* Already tried to load? */
		if ( isset( self::$_queryFilesTriedToLoad[ $app . '-' . $driver ] ) )
		{
			return;
		}
		else
		{
			self::$_queryFilesTriedToLoad[ $app . '-' . $driver ] = 1;
		}
		
		$file   = self::fetchQueryFileName( $where, $app, $key );
		$class  = self::fetchQueryFileClassName( $where, $app, $key );
				
		IPSDebug::addMessage( "* Checking for query cache file: " . $file );
		
		if ( is_file( $file ) )
		{
			self::getDB( $key )->loadCacheFile( $file, $class, TRUE );
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Fetch query file, if available
	 *
	 * @param	string		'public' or 'admin'
	 * @param	string		App dir
	 * @param	string		Key
	 * @return	File name
	 */
	static public function fetchQueryFileClassName( $where, $app, $key='' )
	{
		$key    = ( $key ) ? $key : self::$defaultKey;
		$where  = ( $where == 'admin' ) ? 'admin' : 'public';
		
		return $where . '_' . $app . '_sql_queries';
	}
	
	/**
	 * Fetch query file, if available
	 *
	 * @param	string		'public' or 'admin'
	 * @param	string		App dir
	 * @param	string		Key
	 * @return	File name
	 */
	static public function fetchQueryFileName( $where, $app, $key='' )
	{
		$key    = ( $key ) ? $key : self::$defaultKey;
		$where  = ( $where == 'admin' ) ? 'admin' : 'public';
		$driver = self::getDriverType( $key );
		
		/* Already tried to load? */
		if ( ! isset( self::$_queryFilesNames[ $app . '-' . $driver ] ) )
		{
			self::$_queryFilesNames[ $app . '-' . $driver ] = IPSLib::getAppDir( $app ) . '/sql/' . $driver . '_' . $where . '.php';
		}
		
		return self::$_queryFilesNames[ $app . '-' . $driver ];
	}
	
	/**
	 * Get DB reference based on key
	 *
	 * @param	string	Key
	 * @return	object
	 */
	static public function getDB( $key='' )
	{
		$key = ( $key ) ? $key : self::$defaultKey;

		return self::$data_store[ $key ];
	}

	/**
	 * Get the prefix for this connection
	 *
	 * @param	string	Key
	 * @return	string	Prefix
	 */
	static public function getPrefix( $key='' )
	{
		$key = ( $key ) ? $key : self::$defaultKey;

		if ( isset(self::$dbPrefixes[$key]) )
		{
			return self::$dbPrefixes[ $key ];
		}
		else
		{
			throw new Exception( "Database connection key $key does not exist" );
		}
	}

	/**
	 * Returns the driver type currently in use, ex: mysql
	 *
	 * @param	string  $key  Key of the db connection to check
	 * @return	string
	 */
	static public function getDriverType( $key= '' )
	{
		/* Set Key */
		$key = ( $key ) ? $key : self::$defaultKey;

		return self::$dbDrivers[ $key ];
	}

	/**
	 * Set a database object
	 *
	 * @param	string	DB driver
	 * @param	string	Key
	 * @param	array   Array of settings( sql_database, sql_user, sql_pass, sql_host, sql_charset, sql_tbl_prefix ) and any other connection vars
	 * @return	@e void
	 */
	static public function setDB( $db_driver, $key='', $settings=array() )
	{
		/* INIT */
		$db_driver        = strtolower( $db_driver );
		$query_file_extra = ( IPS_AREA == 'admin' ) ? '_admin' : '';
		$key              = ( $key ) ? $key : self::$defaultKey;
		
		/* Fix up settings */
		foreach( array( 'sql_database', 'sql_user', 'sql_pass', 'sql_host', 'sql_port', 'sql_socket', 'sql_charset', 'sql_tbl_prefix' ) as $_s )
		{
			if ( ! isset( $settings[ $_s ] ) )
			{
				$settings[ $_s ] = isset( ipsRegistry::$settings[ $_s ] ) ? ipsRegistry::$settings[ $_s ] : '';
			}
		}
		
		/* Load main class core */
		if ( ! class_exists( 'db_driver_' . $db_driver ) )
		{
			require_once ( IPS_KERNEL_PATH . 'classDb' . ucwords($db_driver) . '.php' );/*noLibHook*/
		}

		$classname = "db_driver_" . $db_driver;
		
		/* INIT Object */
						
		self::$dbObjects[ $key ] = new $classname;

		self::$dbObjects[ $key ]->obj['sql_database']			= $settings['sql_database'];
		self::$dbObjects[ $key ]->obj['sql_user']				= $settings['sql_user'];
		self::$dbObjects[ $key ]->obj['sql_port']				= $settings['sql_port'];
		self::$dbObjects[ $key ]->obj['sql_socket']				= isset($settings['sql_socket']) ? $settings['sql_socket'] : null;
		self::$dbObjects[ $key ]->obj['sql_pass']				= str_replace( '\\\'', '\'', $settings['sql_pass'] );
		self::$dbObjects[ $key ]->obj['sql_host']				= $settings['sql_host'];
		self::$dbObjects[ $key ]->obj['sql_charset']			= $settings['sql_charset'];
		self::$dbObjects[ $key ]->obj['sql_tbl_prefix']			= $settings['sql_tbl_prefix'] ? $settings['sql_tbl_prefix'] : '';
		self::$dbObjects[ $key ]->obj['force_new_connection']	= ( $key != self::$defaultKey ) ? 1 : 0;
		self::$dbObjects[ $key ]->obj['persistent']				= isset($settings['persistent']) ? $settings['persistent'] : 0;
		self::$dbObjects[ $key ]->obj['use_shutdown']			= IPS_USE_SHUTDOWN;
		
		# Error log
		self::$dbObjects[ $key ]->obj['error_log']				= DOC_IPS_ROOT_PATH . 'cache/sql_error_log_'.date('m_d_y').'.cgi';
		self::$dbObjects[ $key ]->obj['use_error_log']			= IN_DEV ? 0 : 1;
		
		# Debug log - Don't use this on a production board!
		self::$dbObjects[ $key ]->obj['debug_log']				= DOC_IPS_ROOT_PATH . 'cache/sql_debug_log_'.date('m_d_y').'.cgi';
		self::$dbObjects[ $key ]->obj['use_debug_log']			= ( defined('IPS_SQL_DEBUG_LOG') AND IPS_SQL_DEBUG_LOG ) ? 1 : 0;
		
		# Bad log - Don't use this on a production board!
		self::$dbObjects[ $key ]->obj['bad_log']				= DOC_IPS_ROOT_PATH . 'cache/sql_bad_log_'.date('m_d_y').'.cgi';
		self::$dbObjects[ $key ]->obj['use_bad_log']			= ( defined( 'IPS_SQL_FIND_EVIL_MODE' ) AND IPS_SQL_FIND_EVIL_MODE ) ? TRUE : FALSE;
		
		# Slow log - Don't use this on a production board!
		self::$dbObjects[ $key ]->obj['slow_log']				= DOC_IPS_ROOT_PATH . 'cache/sql_slow_log_'.date('m_d_y').'.cgi';
		self::$dbObjects[ $key ]->obj['use_slow_log']			= ( defined( 'IPS_SQL_FIND_SLOW_MODE' ) AND IPS_SQL_FIND_SLOW_MODE ) ? (float) IPS_SQL_FIND_SLOW_MODE : FALSE;

		/* Required vars? */
		if ( is_array( self::$dbObjects[ $key ]->connect_vars ) and count( self::$dbObjects[ $key ]->connect_vars ) )
		{
			foreach( self::$dbObjects[ $key ]->connect_vars as $k => $v )
			{
				self::$dbObjects[ $key ]->connect_vars[ $k ] = ( isset( $settings[ $k ] ) ) ? $settings[ $k ] : ipsRegistry::$settings[ $k ];
			}
		}

		/* Backwards compat */
		if ( ! self::$dbObjects[ $key ]->connect_vars['mysql_tbl_type'] )
		{
			self::$dbObjects[ $key ]->connect_vars['mysql_tbl_type'] = 'myisam';
		}

		/* Update settings */
		self::$dbPrefixes[ $key ]	= self::$dbObjects[ $key ]->obj['sql_tbl_prefix'];
		self::$dbDrivers[ $key ]	= $db_driver;
		
		/* Want to catch errors? */
		if ( ! empty( $settings['catchConnectionError'] ) )
		{
			self::$dbObjects[ $key ]->return_die = true;
		}
		
		/* Get a DB connection */
		self::$dbObjects[ $key ]->connect();
		
		/* ensure it's switched off now */
		self::$dbObjects[ $key ]->return_die = false;

		self::$data_store[ $key ] = self::$dbObjects[ $key ];
	}
	
	/**
	 * Unset a DB
	 */
	static public function unsetDB( $key=false )
	{  
		if ( ! empty( self::$dbObjects[ $key ] ) )
		{
			unset( self::$dbObjects[ $key ] );
		}
	}
}

/**
* Base application class
*/
class ips_CacheRegistry
{
	/**
	 * Database instance
	 *
	 * @var		object
	 */
	private static $instance;

	/**
	 * Database instance
	 *
	 * @var		array
	 */
	protected $save_options  = array();

	/**
	 * Static var for cache library
	 */
	protected static $cacheLib;

	/**
	 * Debug information
	 *
	 * @var		array
	 */
	public $debugInfo = array();

	/**
	 * Initialized flag
	 *
	 * @var		bool
	 */
	protected static $initiated = FALSE;
	
	/**
	 * Generic data storage
	 *
	 * @var		array
	 */
	protected static $data_store	= array();

	/**
	 * List of caches to rebuild
	 *
	 * @var		array
	 */
	protected static $rebuild_list	= array();

	/**
	 * Singleton init
	 *
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}
	
	/**
	 * Retrieve the list of caches to be rebuilt
	 *
	 * @return	@e array
	 */
	public function fetchRebuildList()
	{
		return self::$rebuild_list;
	}

	/**
	 * Initiate class
	 *
	 * @return	@e void
	 */
	private function init()
	{
		if ( self::$initiated !== TRUE )
		{
			//--------------------------------
			// Eaccelerator...
			//--------------------------------

			if( function_exists('eaccelerator_get') AND ipsRegistry::$settings['use_eaccelerator'] == 1 )
			{
				require( IPS_KERNEL_PATH.'interfaces/interfaceCache.php' );/*noLibHook*/
				require( IPS_KERNEL_PATH.'classCacheEaccelerator.php' );/*noLibHook*/
				self::$cacheLib = new classCacheEaccelerator( ipsRegistry::$settings['board_url'] );
			}

			//--------------------------------
			// Memcache
			//--------------------------------

			else if( function_exists('memcache_connect') AND ipsRegistry::$settings['use_memcache'] == 1 )
			{
				require( IPS_KERNEL_PATH.'interfaces/interfaceCache.php' );/*noLibHook*/
				require( IPS_KERNEL_PATH.'classCacheMemcache.php' );/*noLibHook*/
				self::$cacheLib = new classCacheMemcache( ipsRegistry::$settings['board_url'], ipsRegistry::$settings );
			}

			//--------------------------------
			// XCache...
			//--------------------------------

			else if( function_exists('xcache_get') AND ipsRegistry::$settings['use_xcache'] == 1 )
			{
				require( IPS_KERNEL_PATH.'interfaces/interfaceCache.php' );/*noLibHook*/
				require( IPS_KERNEL_PATH.'classCacheXcache.php' );/*noLibHook*/
				self::$cacheLib = new classCacheXcache( ipsRegistry::$settings['board_url'] );
			}

			//--------------------------------
			// APC...
			//--------------------------------

			else if( function_exists('apc_fetch') AND ipsRegistry::$settings['use_apc'] == 1 )
			{
				require( IPS_KERNEL_PATH.'interfaces/interfaceCache.php' );/*noLibHook*/
				require( IPS_KERNEL_PATH.'classCacheApc.php' );/*noLibHook*/
				self::$cacheLib = new classCacheApc( ipsRegistry::$settings['board_url'] );
			}
			
			//--------------------------------
			// Wincache...
			//--------------------------------

			else if( function_exists('wincache_ucache_get') AND ipsRegistry::$settings['use_wincache'] == 1 )
			{
				require( IPS_KERNEL_PATH.'interfaces/interfaceCache.php' );/*noLibHook*/
				require( IPS_KERNEL_PATH.'classCacheWincache.php' );/*noLibHook*/
				self::$cacheLib = new classCacheWincache( ipsRegistry::$settings['board_url'] );
			}

			//--------------------------------
			// Diskcache
			//--------------------------------

			else if( !empty( ipsRegistry::$settings['use_diskcache'] ) )
			{
				require( IPS_KERNEL_PATH.'interfaces/interfaceCache.php' );/*noLibHook*/
				require( IPS_KERNEL_PATH.'classCacheDiskcache.php' );/*noLibHook*/
				self::$cacheLib = new classCacheDiskcache( ipsRegistry::$settings['board_url'] );
			}

			if( is_object(self::$cacheLib) AND self::$cacheLib->crashed )
			{
				// There was a problem - not installed maybe?
				// unset(self::$cacheLib);
				self::$cacheLib = NULL;
			}

			$caches         = array();
			$_caches		= array();
			$_load			= array();
			$_pre_load      = IPSDebug::getMemoryDebugFlag();

			//-----------------------------------------
			// Get default cache list
			//-----------------------------------------

			$CACHE = ipsRegistry::_fetchCoreVariables( 'cache' );
			$_LOAD = ipsRegistry::_fetchCoreVariables( 'cacheload' );

			if ( is_array( $CACHE ) )
			{
				foreach( $CACHE as $key => $data )
				{
					if ( !empty($data['acp_only']) AND IPS_AREA != 'admin' )
					{
						continue;
					}

					$_caches[ $key ]	= $CACHE;

					if ( $data['default_load'] )
					{
						$caches[ $key ] = $key;
					}
				}

				if( count($_LOAD) )
				{
					foreach( $_LOAD as $key => $one )
					{
						$_load[ $key ] = $key;
					}
				}
			}

			//-----------------------------------------
			// Get application cache list
			//-----------------------------------------

			if ( IPS_APP_COMPONENT )
			{
				$CACHE = ipsRegistry::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'cache' );
				$_LOAD = ipsRegistry::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'cacheload' );

				if ( is_array( $CACHE ) )
				{
					foreach( $CACHE as $key => $data )
					{
						if ( !empty( $data['acp_only'] ) AND IPS_AREA != 'admin' )
						{
							continue;
						}

						$_caches[ $key ]	= $CACHE;

						if ( $data['default_load'] )
						{
							$caches[ $key ] = $key;
						}
					}

					if( count($_LOAD) )
					{
						foreach( $_LOAD as $key => $one )
						{
							$_load[ $key ] = $key;
						}
					}
				}
			}

			//-----------------------------------------
			// Get global caches list
			//-----------------------------------------
			
			if ( is_file( DOC_IPS_ROOT_PATH . 'cache/globalCaches.php' ) )
			{
				$GLOBAL_CACHES = array();
				include( DOC_IPS_ROOT_PATH . 'cache/globalCaches.php' );/*noLibHook*/
				
				if( is_array($GLOBAL_CACHES) AND count($GLOBAL_CACHES) )
				{
					foreach( $GLOBAL_CACHES as $key )
					{
						$_load[ $key ] = $key;
					}
				}
			}
			
			//-----------------------------------------
			// Add caches to the load list 
			//-----------------------------------------
			
			if( is_array($_load) AND count($_load) )
			{
				foreach( $_load as $key )
				{
					$caches[ $key ] = $key;
				}
			}

			//-----------------------------------------
			// Load 'em
			//-----------------------------------------

			self::_loadCaches( $caches );
		}

		self::$initiated = TRUE;
	}

	/**
	 * Load cache(s)
	 *
	 * @param	array 	Array of caches to load: array( 'group_cache', 'forum_cache' )
	 * @param	boolean	Set to FALSE to skip trying to load the caches from DB
	 * @return	mixed	Loaded Cache
	 * @author	MattMecham
	 */
	protected static function _loadCaches( $caches=array(), $loadFromDb=true, $init=false )
	{
		if ( ! is_array( $caches ) OR ! count( $caches ) )
		{
			return NULL;
		}

		$requestedCaches	= $caches;
		$_seenKeys			= array();
		
		//--------------------------------
		// Eaccelerator...
		//--------------------------------

		if ( is_object( self::$cacheLib ) )
		{
			$temp_cache 	 = array();
			$new_cache_array = array();

			foreach( $caches as $key )
			{
				$temp_cache[$key] = self::$cacheLib->getFromCache( $key );

				if ( ! $temp_cache[$key] OR $temp_cache[$key] == 'rebuildCache' )
				{
					$new_cache_array[] = $key;
				}
				else
				{
					if ( is_string($temp_cache[$key]) AND strpos( $temp_cache[$key], "a:" ) !== false )
					{
						self::$data_store[ $key ] = unserialize( $temp_cache[$key] );
						
						//-----------------------------------------
						// Fallback fix if unserialize fails
						//-----------------------------------------
						
						if( !is_array(self::$data_store[ $key ]) )
						{
							$new_cache_array[] = $key;
							continue;
						}
					}
					else if( $temp_cache[$key] == "EMPTY" )
					{
						self::$data_store[ $key ] = NULL;
					}
					else
					{
						self::$data_store[ $key ] = $temp_cache[$key];
					}
					
					$_seenKeys[ $key ]	= $key;
				}
			}

			$caches = $new_cache_array;

			unset($new_cache_array, $temp_cache);
		}

		$_rebuild	= array();

		if( count($caches) && $loadFromDb )
		{
			//--------------------------------
			// Get from DB...
			//--------------------------------
	
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key IN ( '" . implode( "','", $caches ) . "' )" ) );
			$exe = ipsRegistry::DB()->execute();

			while ( $r = ipsRegistry::DB()->fetch( $exe ) )
			{
				$_seenKeys[ $r['cs_key'] ] = $r['cs_key'];

				//-----------------------------------------
				// Forcing rebuild?
				//-----------------------------------------
				
				if( $r['cs_rebuild'] )
				{
					if ( $r['cs_key'] == 'licenseData' )
					{
						self::instance()->rebuildCache( $r['cs_key'] );
						self::$data_store[ $r['cs_key'] ] = self::instance()->getCache( $r['cs_key'] );
					}
					else
					{
						self::$rebuild_list[]	= $r['cs_key'];
					}
					continue;
				}
				
				//-----------------------------------------
				// Debug?
				//-----------------------------------------
				
				if ( IN_DEV )
				{
					self::instance()->debugInfo[ $r['cs_key'] ] = array( 'size' => IPSLib::strlenToBytes( strlen($r['cs_value']) ) );
				}
	
				//-----------------------------------------
				// Serialized array?
				//-----------------------------------------
				
				if ( $r['cs_array'] OR substr( $r['cs_value'], 0, 2 ) == "a:" )
				{
					self::$data_store[ $r['cs_key'] ] = unserialize( $r['cs_value'] );
	
					if ( ! is_array( self::$data_store[ $r['cs_key'] ] ) )
					{
						self::$data_store[ $r['cs_key'] ] = array();
					}
				}
				else
				{
					self::$data_store[ $r['cs_key'] ] = ( $r['cs_value'] ) ? $r['cs_value'] : NULL;
				}
	
				//-----------------------------------------
				// Push to alt cache store
				//-----------------------------------------
				
				if ( is_object( self::$cacheLib ) )
				{
					if ( ! $r['cs_value'] )
					{
						$r['cs_value'] = "EMPTY";
					}
	
					self::$cacheLib->putInCache( $r['cs_key'], $r['cs_value'], 86400 );
				}
			}
		}

		//-----------------------------------------
		// Make sure each key is in data_store otherwise
		// repeated calls will keep trying to load it
		//-----------------------------------------

		foreach( $requestedCaches as $_cache )
		{
			if ( ! in_array( $_cache, $_seenKeys ) )
			{
				self::$data_store[ $_cache ] = NULL;
			}
		}
	}

	/**
	 * Set a cache
	 *
	 * @param	string	Cache Key
	 * @param	mixed	Cache value (typically an array)
	 * @return	@e void
	 */
	protected function cacheSet( $key, $val )
	{
		/* Update in_memory cache */
		self::$data_store[ $key ] = $val;

		$this->save_options['donow'] = isset($this->save_options['donow']) ? $this->save_options['donow'] : 0;

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		if ( $key )
		{
			if ( empty($val) )
			{
				if ( !empty($this->save_options['array']) )
				{
					$value = serialize(self::$data_store[ $key ]);
				}
				else
				{
					$value = self::$data_store[ $key ];
				}
			}
			else
			{
				if ( !empty($this->save_options['array']) )
				{
					$value = serialize($val);
				}
				else
				{
					$value = $val;
				}
			}

			ipsRegistry::DB()->preventAddSlashes( array( 'cs_key' => 1 ) );

			if ( $this->save_options['donow'] )
			{
				ipsRegistry::DB()->replace( 'cache_store', array( 'cs_array' => intval($this->save_options['array']), 'cs_key' => $key, 'cs_value' => $value, 'cs_updated' => time(), 'cs_rebuild' => 0 ), array( 'cs_key' ) );
			}
			else
			{
				ipsRegistry::DB()->replace( 'cache_store', array( 'cs_array' => intval($this->save_options['array']), 'cs_key' => $key, 'cs_value' => $value, 'cs_updated' => time(), 'cs_rebuild' => 0 ), array( 'cs_key' ), true );
			}

			if ( is_object( self::$cacheLib ) )
			{
				if ( ! $val )
				{
					$val = "EMPTY";
				}

				self::$cacheLib->updateInCache( $key, $val, 86400 );
			}
		}

		/* Reest... */
		$this->save_options = array();
	}

	/**
	 * Grab item from cache library
	 *
	 * @param	string	Key
	 * @return	mixed	false, or cached value
	 */
	static public function getWithCacheLib( $key )
	{
		if ( is_object( self::$cacheLib ) )
		{
			return self::$cacheLib->getFromCache( $key );
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Store into cache library
	 *
	 * @param	string	Key
	 * @param	mixed	Item to cache
	 * @param	int		Time to live
	 * @return	mixed
	 */
	static public function putWithCacheLib( $key, $value, $ttl=0 )
	{
		if ( is_object( self::$cacheLib ) )
		{
			return self::$cacheLib->putInCache( $key, $value, $ttl );
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Check to see if the cache exists or not
	 *
	 * @param	string		Cache name
	 * @return	boolean
	 */
	static public function exists( $key )
	{
		return ( isset( self::$data_store[ $key ] ) AND ( self::$data_store[ $key ] !== NULL ) ) ? TRUE : FALSE;
	}

	/**
 	 * Get the cache
	 * If the cache has not been loaded during init(), then it'll load the
	 * cache.
	 *
	 * @param	mixed	Cache key, or array of cache keys
	 * @param	boolean	Set to FALSE to skip trying to load the caches from DB [if not loaded already] 
	 * @return	mixed	Cache value if $keys is a string, else boolean (loaded successfully or not)
	 */
	static public function getCache( $keys, $loadFromDb=true )
	{
		if ( is_string( $keys ) )
		{
			if ( ! isset( self::$data_store[ $keys ] ) )
			{
				self::_loadCaches( array( $keys ), $loadFromDb );
			}
		
			return self::$data_store[ $keys ];
		}
		elseif ( is_array( $keys ) && count( $keys ) )
		{
			$toLoad = array();
			
			foreach( $keys as $key )
			{
				if ( ! isset( self::$data_store[ $key ] ) )
				{
					$toLoad[] = $key;
				}
			}
			
			if ( count($toLoad) )
			{
				self::_loadCaches( $toLoad, $loadFromDb );
			}
			
			return TRUE;
		}
		
		return FALSE;
	}

	/**
 	 * Delete a cache from DB and alt cache store
	 *
	 * @param	mixed	Cache key, or array of cache keys
	 * @return	@e boolean
	 */
	static public function deleteCache( $keys )
	{
		if ( is_string( $keys ) )
		{
			ipsRegistry::DB()->delete( 'cache_store', "cs_key='{$keys}'" );
		
			if ( is_object( self::$cacheLib ) )
			{
				self::$cacheLib->removeFromCache( $keys );
			}
		}
		elseif ( is_array( $keys ) && count( $keys ) )
		{
			ipsRegistry::DB()->delete( 'cache_store', "cs_key IN('" . implode( "','", $keys ) . "')" );

			if ( is_object( self::$cacheLib ) )
			{
				foreach( $keys as $key )
				{
					self::$cacheLib->removeFromCache( $keys );
				}
			}
		}
		
		return TRUE;
	}

	/**
	 * Fetch all the caches as a reference
	 *
	 * @return	array
	 */
	static public function &fetchCaches()
	{
		return self::$data_store;
	}

	/**
 	 * Update a cache key without saving it to the db
	 *
	 * @param	string	Cache key
	 * @param	mixed	Value
	 * @return	@e void
	 */
	static public function updateCacheWithoutSaving( $key, $value )
	{
		self::$data_store[ $key ] = $value;
	}

	/**
 	 * Store an updated cache value
	 *
	 * @param	string	Cache key
	 * @param	mixed	Value
	 * @param	array 	Options
	 * @return	mixed
	 */
	static public function setCache( $key, $value, $options=array() )
	{
		if ( ! $key )
		{
			throw new Exception( "Key missing in setCache" );
		}

		self::instance()->save_options = $options;
		return self::instance()->cacheSet( $key, $value );
	}

	/**
 	 * Rebuild a cache using defined $CACHE settings in it's extensions file
	 *
	 * @param	string	Cache key
	 * @param	string	Application
	 * @return	@e void
	 */
	static public function rebuildCache( $key, $app='' )
	{
		if ( defined( 'IPS_NO_CACHE_REBUILD' ) )
		{
			return true;
		}
		
		/* INIT */
		$app		= IPSText::alphanumericalClean( $app );
		$_caches	= array();
    
		if( $app )
		{
			if ( $app == 'global' )
			{
				$_caches = ipsRegistry::_fetchCoreVariables( 'cache' );
			}
			else
			{
				/* isset there is needed to prevent issues on applications installation */
				if( isset( ipsRegistry::$applications[$app] ) && !IPSLib::appIsInstalled($app) )
				{
					return;
				}

				$_caches = ipsRegistry::_fetchAppCoreVariables( $app, 'cache' );
			}
		}
		else
		{
			/* Get all caches from all apps */
			$_caches = ipsRegistry::_fetchCoreVariables( 'cache' );
			
			foreach( ipsRegistry::$applications as $appDir => $appData )
			{
				$CACHE = ipsRegistry::_fetchAppCoreVariables( $appDir, 'cache' );
				
				if ( is_array( $CACHE ) )
				{
					$_caches = array_merge( $_caches, $CACHE );
				}
			}
		}
		
		/* Rebuild the cache, if found */
		if( isset( $_caches[ $key ] ) )
		{
			$file_to_check = $_caches[ $key ]['recache_file'];

			if ( $file_to_check AND is_file( $file_to_check ) )
			{
				$_func  = $_caches[ $key ]['recache_function'];
				
				/* Hackish way to check for action overloader */
				if ( strpos( $file_to_check, '/modules_' ) !== FALSE )
				{
					$_class = IPSLib::loadActionOverloader( $file_to_check, $_caches[ $key ]['recache_class'] );
				}
				elseif ( $app )
				{
					$_class = IPSLib::loadLibrary( $file_to_check, $_caches[ $key ]['recache_class'], ($app == 'global') ? 'core' : $app );
				}
				
				/* Fallback */
				if ( !$_class )
				{
					$_class = $_caches[ $key ]['recache_class'];
				}
				
				$recache = new $_class( ipsRegistry::instance() );

				if( method_exists( $recache, 'makeRegistryShortcuts' ) )
				{
					$recache->makeRegistryShortcuts( ipsRegistry::instance() );
				}

				$recache->$_func();
			}
		}
	}
}

/**
* Dummy class to prevent errors if cache()->rebuild is required during init()
*/
class ips_MemberRegistryDummy
{
	/**
	 * Database instance
	 *
	 * @var		object
	 */
	private static $instance;
	
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/**
	 * Fetch all the member data as a reference
	 *
	 * @return	array
	 */
	static public function &fetchMemberData()
	{
		return array();
	}

	/**
	 * Post output class setup
	 * 
	 * @return	@e void
	 */
 	public static function postOutput()
 	{
	}
}

/**
* Base application class
*/
class ips_MemberRegistry
{
	/**
	 * Database instance
	 *
	 * @var		object
	 */
	private static $instance;

	/**
	 * Member settings
	 *
	 * @var		array
	 */
	protected $settings = array();

	/**
	 * Member info
	 *
	 * @var		array
	 */
	protected static $member   = array();

	/**
	 * Member ID
	 *
	 * @var		integer
	 */
	public $member_id;

	/**
	 * Perm mask ID
	 *
	 * @var		integer
	 */
	public $perm_id;

	/**
	 * Array of perm masks
	 *
	 * @var		array
	 */
	public $perm_id_array = array();

	/**
	 * Unique form hash
	 *
	 * @var		string
	 */
	public $form_hash     = '';

	/**
	 * Language ID to use
	 *
	 * @var		integer
	 */
	public $language_id   = '1';

	/**
	 * Skin ID to use
	 *
	 * @var		integer
	 */
	public $skin_id       = '';

	/**
	 * Preferences
	 *
	 * @var		array
	 */
	public $preferences   = array();

	/**#@+
	 * Member's session data
	 *
	 * @var		mixed	integer, string, array
	 */
	public $session_id   = 0;
	public $session_type = '';
	public $ip_address   = '';
	public $last_click   = 0;
	public $location     = '';
	public $acp_tab_data = array();
	/**#@-*/

	/**#@+
	 * Environment/browser data
	 *
	 * @var		mixed	integer, string, array
	 */
	public $user_agent;
	public $browser          = array();
	public $operating_system = 'unknown';
	public $is_not_human     = 0;
	/**#@-*/

	/**
	 * Ignored users
	 *
	 * @var		array
	 */
	public $ignored_users	= array();

	/**
	 * Sessions class
	 *
	 * @var		object
	 */
	protected static $session_class;

	/**
	 * Initiated
	 *
	 * @var		false
	 */
	protected static $initiated    = FALSE;
	
	/**
	 * Data store
	 *
	 * @var		array
	 */
	protected static $data_store = array( 'member_group_id' => 0, 'mgroup_others' => '' );
	
	/**
	 * Is this Facebook?
	 * @var 	bool
	 */
	public $iAmFacebook = false;
	
	/**
	 * Initialization method
	 *
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
			self::init();
		}

		return self::$instance;
	}

	/**
	 * Destructor
	 *
	 * @return	@e void
	 */
	public function __myDestruct()
	{
		/* Item marking clean up */
		if( ipsRegistry::isClassLoaded('classItemMarking') )
		{
			ipsRegistry::getClass('classItemMarking')->__myDestruct();
		}

		/* Session clean up */
		self::sessionClass()->__myDestruct();
	}

	/**
	 * Singleton init method
	 *
	 * @return	@e void
	 */
	protected static function init()
	{
		if ( self::$initiated !== TRUE )
		{
			//-----------------------------------------
			// IP Address
			//-----------------------------------------

			if ( ipsRegistry::$settings['xforward_matching'] )
			{
				foreach( array_reverse( explode( ',', my_getenv('HTTP_X_FORWARDED_FOR') ) ) as $x_f )
				{
					$addrs[] = trim($x_f);
				}

				$addrs[] = my_getenv('HTTP_CLIENT_IP');
				$addrs[] = my_getenv('HTTP_X_CLUSTER_CLIENT_IP');
				$addrs[] = my_getenv('HTTP_PROXY_USER');
			}

			$addrs[] = my_getenv('REMOTE_ADDR');

			//-----------------------------------------
			// Do we have one yet?
			//-----------------------------------------

			foreach ( $addrs as $ip )
			{
				//-----------------------------------------
				// IP v4
				//-----------------------------------------
				
				if ( IPSLib::validateIPv4( $ip ) )
				{
					self::instance()->ip_address	= $ip;
					break;
				}
				
				//-----------------------------------------
				// IP v6
				//-----------------------------------------
				
				else if ( IPSLib::validateIPv6( $ip ) )
				{
					self::instance()->ip_address = $ip;
					break;
				}
			}

			//-----------------------------------------
			// Make sure we take a valid IP address
			//-----------------------------------------

			if ( !self::instance()->ip_address AND ! isset( $_SERVER['SHELL'] ) AND $_SERVER['SESSIONNAME'] != 'Console' )
			{
				if ( ! defined('IPS_IS_SHELL') OR ! IPS_IS_SHELL )
				{
					print "Could not determine your IP address";
					exit();
				}
			}

			//-----------------------------------------
			// Get user-agent, browser and OS
			//-----------------------------------------

			self::instance()->user_agent       = IPSText::parseCleanValue( my_getenv('HTTP_USER_AGENT') );
			self::instance()->operating_system = self::_fetch_os();

			if ( IPS_AREA == 'admin' )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/adminSessions.php', 'adminSessions' );

				/**
				 * Support for extending the session class
				 */
				if( is_file( IPS_ROOT_PATH . "sources/classes/session/ssoAdminSessions.php" ) )
				{
					$classToLoadA = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/session/ssoAdminSessions.php", 'ssoAdminSessions' );

					/**
					 * Does the ssoAdminSessions class exist?
					 */
					if( class_exists( $classToLoadA ) )
					{
						$parent = get_parent_class( $classToLoadA );

						/**
						 * Is it a child of adminSessions
						 */
						if( $parent == $classToLoad )
						{
							self::$session_class = new $classToLoadA();
						}
						else
						{
							self::$session_class = new $classToLoad();
						}
					}
				}
				else
				{
					self::$session_class = new $classToLoad();
				}
			}
			else
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/publicSessions.php', 'publicSessions' );

				/**
				 * Support for extending the session class
				 */
				if( is_file( IPS_ROOT_PATH . "sources/classes/session/ssoPublicSessions.php" ) )
				{
					$classToLoadA = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/session/ssoPublicSessions.php", 'ssoPublicSessions' );

					/**
					 * Does the ssoPublicSessions class exist?
					 */
					if( class_exists( $classToLoadA ) )
					{
						$parent = get_parent_class( $classToLoadA );

						/**
						 * Is it a child of publicSessions
						 */
						if( $parent == $classToLoad )
						{
							self::$session_class = new $classToLoadA();
						}
						else
						{
							self::$session_class = new $classToLoad();
						}
					}
				}
				else
				{
					self::$session_class = new $classToLoad();
				}

				//-----------------------------------------
				// Set other
				//-----------------------------------------

				self::$data_store['publicSessionID']  = self::$session_class->session_data['id'];
			}
			
			//-----------------------------------------
			// Set user agent
			//-----------------------------------------
			
			$_cookie = IPSCookie::get("uagent_bypass");
			
			self::$data_store['userAgentKey']     = isset(self::$session_class->session_data['uagent_key']) ? self::$session_class->session_data['uagent_key'] : '';
			self::$data_store['userAgentType']    = isset(self::$data_store['uagent_type'] ) ? self::$data_store['uagent_type'] : self::$session_class->session_data['uagent_type'];
			self::$data_store['userAgentVersion'] = isset(self::$session_class->session_data['uagent_version']) ? self::$session_class->session_data['uagent_version'] : '';
			self::$data_store['userAgentBypass']  = ( $_cookie ) ? true : ( isset(self::$session_class->session_data['uagent_bypass']) ? self::$session_class->session_data['uagent_bypass'] : '' );
		
			self::$data_store['forumsModeratorData']	= array();
			
			/* Some mobile app set up */
			if ( self::$data_store['userAgentType'] == 'mobileApp' )
			{
				/* This converts non UTF-8 POST/GET data in __construct */
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/base/ipsMobileApp.php', 'ipsMobileApp' );
				ipsRegistry::setClass( 'isMobileApp', new $classToLoad() );			
			}
   		}
	}
	
	/**
	 * Post output class setup
	 * 
	 * @return	@e void
	 */
 	public static function postOutput()
 	{
 		if( !self::$data_store['member_id'] )
 		{
 			self::$data_store	= IPSMember::buildNoPhoto( self::$data_store );
 		}
 	}

	/**
	 * Sessions class interface
	 *
	 * @return	object
	 */
	public static function sessionClass()
	{
		return self::$session_class;
	}

	/**
	 * Get a member property
	 *
	 * @param	string	Key
	 * @return	mixed	Member data
	 */
	static public function getProperty( $key )
	{
		return ( isset( self::$data_store[ $key ] ) ) ? self::$data_store[ $key ] : null;
	}

	/**
	 * Set a member property.  DOES NOTE SAVE IT TO DB.
	 *
	 * @param	string	Key
	 * @param	mixed	Value
	 * @return	mixed	Member data
	 */
	static public function setProperty( $key, $value )
	{
		return self::$data_store[ $key ] = $value;
	}

	/**
	 * Fetch all the member data as a reference
	 *
	 * @return	array
	 */
	static public function &fetchMemberData()
	{
		return self::$data_store;
	}

	/**
	 * Sets up a search engine's data and permissions
	 *
	 * @param	array 		array of useragent information
	 * @return	@e void
	 */
	static public function setSearchEngine( $uAgent )
	{
		$cache = ipsRegistry::cache()->getCache('group_cache');
		$group = $cache[ intval( ipsRegistry::$settings['guest_group'] ) ];
		
		/* Are we facebook? */
		if ( $uAgent['uagent_key'] == 'facebook' )
		{
			self::instance()->iAmFacebook = true;
			
			$group = ( ipsRegistry::$settings['fbc_bot_group'] ) ? $cache[ intval( ipsRegistry::$settings['fbc_bot_group'] ) ] : $group;
		}
		
		/* Are we mobile? */
		if ( $uAgent['uagent_key'] == 'googlemobile' )
		{
			self::$data_store['uagent_type'] = 'mobileBot';
		}
				
		foreach ( $group as $k => $v )
		{
			self::$data_store[ $k ] = $v;
		}

		/* Fix up member and group data */
		self::$data_store['members_display_name']	= $uAgent['uagent_name'];
		self::$data_store['_members_display_name']	= $uAgent['uagent_name'];
		self::$data_store['name']					= $uAgent['uagent_name'];
		self::$data_store['member_group_id']		= self::$data_store['g_id'];
		self::$data_store['view_sigs']				= ipsRegistry::$settings['guests_sig'];
		self::$data_store['restrict_post']			= 1;
		self::$data_store['g_use_search']			= 0;
		self::$data_store['g_edit_profile']			= 0;
		self::$data_store['g_use_pm']				= 0;
		self::$data_store['g_is_supmod']			= 0;
		self::$data_store['g_access_cp']			= 0;
		self::$data_store['g_access_offline']		= 0;
		self::$data_store['g_avoid_flood']			= 0;
		self::$data_store['g_post_new_topics']		= 0;
		self::$data_store['g_reply_own_topics']		= 0;
		self::$data_store['g_reply_other_topics']	= 0;
		self::$data_store['member_id']				= 0;
		self::$data_store['_cache']					= array();
		self::$data_store['_cache']['friends']		= array();

		/* Fix up permission strings */
		self::instance()->perm_id       = $group['g_perm_id'];
        self::instance()->perm_id_array = explode( ",", $group['g_perm_id'] );
		
		# Form hash
		self::instance()->form_hash = md5("this is only here to prevent it breaking on guests");
			
		/* It's allliiiiiiveeeeee */
		self::instance()->is_not_human  = true;
		
		/* Disable relative time */
		ipsRegistry::$settings['time_use_relative'] = 0;
		
		/* Logging? */
		if ( ipsRegistry::$settings['spider_visit'] )
		{
			ipsRegistry::DB()->insert( 'spider_logs', array ( 'bot'		     => $uAgent['uagent_key'],
															  'request_addr' => htmlentities( strip_tags( str_replace( '\\', '',  str_replace( "'", "", my_getenv('REQUEST_URI')) ) ) ),
															  'ip_address'   => self::instance()->ip_address,
															  'entry_date'   => time() ), true );
		}
	}

	/**
	 * Set current member to the member ID specified
	 *
	 * @param	integer		Member ID
	 * @return	@e void
	 */
	static public function setMember( $member_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$member_id = intval( $member_id );
		$addrs     = array();

		//-----------------------------------------
		// If we have a member ID, set up the member
		//-----------------------------------------

		if ( $member_id )
		{
			self::$data_store = IPSMember::load( $member_id, 'extendedProfile,customFields,groups' );
		}
				
		/* Got a member ID? */
		if ( !empty($member_id) && self::$data_store['member_id'] )
		{
			self::setUpMember();
			
			self::instance()->language_id = self::$data_store['language'];
			
			# Form hash
			self::instance()->form_hash = md5( self::$data_store['email'].'&'.self::$data_store['member_login_key'].'&'.self::$data_store['joined'] );
		}
		else
		{
			self::$data_store = IPSMember::setUpGuest();

			self::instance()->perm_id       = !empty(self::$data_store['org_perm_id']) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
			self::instance()->perm_id_array = explode( ',', self::instance()->perm_id );
			
			if( IPSCookie::get('language') )
			{
				self::instance()->language_id = IPSCookie::get('language');
			}
			
			# Form hash
			self::instance()->form_hash = md5("this is only here to prevent it breaking on guests");
		}

		/* Get the ignored users */
		if( IPS_AREA == 'public' )
		{
			/* Ok, Fetch ignored users */
			self::instance()->ignored_users = IPSMember::fetchIgnoredUsers( self::$data_store );
		}
		 	
		//-----------------------------------------
		// Set member data
		//-----------------------------------------

		self::instance()->member_id = $member_id;
	}

	/**
	 * Fetches the user's operating system
	 *
	 * @return	string
	 */
	static protected function _fetch_os()
	{
		$useragent = strtolower(my_getenv('HTTP_USER_AGENT'));

		if ( strstr( $useragent, 'mac' ) )
		{
			return 'mac';
		}

		if ( preg_match( '#wi(n|n32|ndows)#', $useragent ) )
		{
			return 'windows';
		}

		return 'unknown';
	}

	/**
	 * Set up a member's secondary groups
	 *
	 * @param	array		$data		Member data
	 * @return	@e array	Member data with secondary group perms set properly
	 */
	static public function setUpSecondaryGroups( $data )
    {
    	if ( !empty($data['mgroup_others']) )
		{
			$cache			= ipsRegistry::cache()->getCache('group_cache');
			$groups_id		= explode( ',', $data['mgroup_others'] );
			$exclude		= array( 'g_id', 'g_title', 'g_icon', 'prefix', 'suffix', 'g_promotion', 'g_photo_max_vars' );
			$less_is_more	= array( 'g_search_flood' );
			$neg1_is_best	= array();
			$zero_is_best	= array( 'g_attach_max', 'g_attach_per_post', 'g_edit_cutoff', 'g_max_messages', 'g_pm_perday', 'g_pm_flood_mins', 'g_displayname_unit', 'g_sig_unit', 'g_mod_preview', 'g_ppd_limit', 'g_ppd_unit', 'gbw_no_status_update', 'g_max_bgimg_upload', 'gbw_disable_prefixes', 'gbw_disable_tagging' );
			$special		= array( 'g_signature_limits', 'g_dname_date', 'g_dname_changes', 'g_mod_post_unit', 'gbw_mod_post_unit_type' );
			
			//-----------------------------------------
			// Merge in per-app group overrides
			//-----------------------------------------
			
			foreach( IPSLib::getEnabledApplications() as $application )
			{
				if( !empty($application['extensions']['groupOptions']) )
				{
					if( !empty($application['extensions']['groupOptions']['neg1_is_best']) AND is_array($application['extensions']['groupOptions']['neg1_is_best']) )
					{
						$neg1_is_best	= array_merge( $neg1_is_best, $application['extensions']['groupOptions']['neg1_is_best'] );
					}
					
					if( !empty($application['extensions']['groupOptions']['zero_is_best']) AND is_array($application['extensions']['groupOptions']['zero_is_best']) )
					{
						$zero_is_best	= array_merge( $zero_is_best, $application['extensions']['groupOptions']['zero_is_best'] );
					}
					
					if( !empty($application['extensions']['groupOptions']['less_is_more']) AND is_array($application['extensions']['groupOptions']['less_is_more']) )
					{
						$less_is_more	= array_merge( $less_is_more, $application['extensions']['groupOptions']['less_is_more'] );
					}
					
					if( !empty($application['extensions']['groupOptions']['exclude']) AND is_array($application['extensions']['groupOptions']['exclude']) )
					{
						$exclude		= array_merge( $exclude, $application['extensions']['groupOptions']['exclude'] );
					}
				}
			}

			//-----------------------------------------
			// Merge permissions
			//-----------------------------------------
			
			if ( count( $groups_id ) )
			{
				foreach( $groups_id as $pid )
				{
					if ( empty($cache[ $pid ]['g_id']) )
					{
						continue;
					}
					
					//-----------------------------------------
					// Loop through and mix
					//-----------------------------------------

					foreach( $cache[ $pid ] as $k => $v )
					{
						if ( ! in_array( $k, $exclude ) )
						{
							//-----------------------------------------
							// Add to perm id list
							//-----------------------------------------

							if ( $k == 'g_perm_id' )
							{
								$data['g_perm_id'] .= ','.$v;
							}
							else if ( in_array( $k, $zero_is_best ) )
							{
								if ( empty( $data[ $k ] ) )
								{
									continue;
								}
								else if( $v == 0 )
								{
									$data[ $k ] = 0;
								}
								else if ( $v > $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
							else if( in_array( $k, $neg1_is_best ) )
							{
								if ( $data[ $k ] == -1 )
								{
									continue;
								}
								else if( $v == -1 )
								{
									$data[ $k ] = -1;
								}
								else if ( $v > $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
							else if ( in_array( $k, $less_is_more ) )
							{
								if ( $v < $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
							else if( in_array( $k, $special ) )
							{
								switch( $k )
								{
									case 'g_signature_limits':
									
										//-----------------------------------------
										// No limits should win out
										//-----------------------------------------
										
										if( !$data[ $k ] )
										{
											continue;
										}
										
										//-----------------------------------------
										// We have limits
										//-----------------------------------------

										if( $v )
										{
											$values	= explode( ':', $v );
											$_cur	= explode( ':', $data[ $k ] );
											$_new 	= array();
											
											foreach( $values as $index => $value )
											{											
												if( $_cur[ $index ] === null OR $values[ $index ] === null OR $_cur[ $index ] === '' OR $values[ $index ] === '' )
												{
													$_new[ $index ]	= null;
												}
												elseif ( $index == 0 and $_cur[ $index ] < $values[ $index ] ) // If we're looking at whether or not signatures are disabled, and they currently are, then go with whatever this group says
												{
													$_new[ $index ]	= $_cur[ $index ];
												}
												else if( $_cur[ $index ] > $values[ $index ] )
												{
													$_new[ $index ]	= $_cur[ $index ];
												}
												else
												{
													$_new[ $index ]	= $values[ $index ];
												}
											}
																						
											ksort($_new);
											$data[ $k ]	= implode( ':', $_new );
										}
										else
										{
											//-----------------------------------------
											// Set no limits
											//-----------------------------------------
											
											$data[ $k ]	= null;
										}
									break;
									
									case 'g_dname_date':
										//-----------------------------------------
										// We'll handle this in g_dname_changes
										//-----------------------------------------
										
										continue;
									break;
									
									case 'g_dname_changes':
										$changes	= $v;
										$timeFrame	= $cache[ $pid ]['g_dname_date'];

										//-----------------------------------------
										// No time frame restriction
										//-----------------------------------------
										
										if( !$timeFrame )
										{
											//-----------------------------------------
											// This group allows more changes
											//-----------------------------------------

											if( $changes > $data[ $k ] )
											{
												$data[ $k ]				= $changes;
												$data['g_dname_date']	= 0;
											}
											
											//-----------------------------------------
											// Existing data is date restricted
											//-----------------------------------------
											
											else if( $data['g_dname_date'] )
											{
												if( $data[ $k ] )
												{
													$_compare	= round($data['g_dname_date'] / $data[ $k ]);
													
													if( $_compare > $changes )
													{
														$data[ $k ]				= $changes;
														$data['g_dname_date']	= 0;
													}
												}
											}
										}
										
										//-----------------------------------------
										// Time frame restriction
										//-----------------------------------------
										
										else if( $changes )
										{
											$_compare	= round($timeFrame / $changes);

											//-----------------------------------------
											// Existing has no time frame restriction
											//-----------------------------------------
											
											if( !$data['g_dname_date'] AND $data[ $k ] )
											{
												if( $_compare < $data[ $k ] )
												{
													$data[ $k ]				= $changes;
													$data['g_dname_date']	= $timeFrame;
												}
											}
											else if( !$data['g_dname_date'] )
											{
												$data[ $k ]				= $changes;
												$data['g_dname_date']	= $timeFrame;
											}
											else if( $data['g_dname_date'] AND $data[ $k ] )
											{
												$_oldCompare	= $data['g_dname_date'] / $data[ $k ];

												if( $_compare < $_oldCompare )
												{
													$data[ $k ]				= $changes;
													$data['g_dname_date']	= $timeFrame;
												}
											}
										}
									break;
									
									case 'g_mod_post_unit':
										/* Have we met the current requirements? */
										if ( ( !$data['gbw_mod_post_unit_type'] and $data['g_mod_post_unit'] >= $data['posts'] ) or ( $data['gbw_mod_post_unit_type'] and time() >= ( $data['joined'] + ( $data['g_mod_post_unit'] * 3600 ) ) ) )
										{
											// Yes - so let's stick with this
											continue;
										}
										else
										{
											// No - go with the new group
											$data['g_mod_post_unit'] = $cache[ $pid ]['g_mod_post_unit'];
											$data['gbw_mod_post_unit_type'] = $cache[ $pid ]['gbw_mod_post_unit_type'];
										}
									break;
									
									case 'gbw_mod_post_unit_type':
										// We handle this with g_mod_post_unit - so do nothing
									break;
								}
							}
							else
							{
								if ( !isset($data[ $k ]) OR $v > $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
						}
					}
				}
			}
									
			//-----------------------------------------
			// Tidy perms_id
			//-----------------------------------------

			$rmp = array();
			$tmp = explode( ',', IPSText::cleanPermString($data['g_perm_id']) );

			if ( count( $tmp ) )
			{
				foreach( $tmp as $t )
				{
					$rmp[ $t ] = $t;
				}
			}

			if ( count( $rmp ) )
			{
				$data['g_perm_id'] = implode( ',', $rmp );
			}
		}

		return $data;
	}

	/**
	 * Update the member's session
	 *
	 * @param	array 		Array of data to update
	 * @return	@e void
	 */
	static public function updateMySession( $data )
	{
		self::$session_class->updateSession( self::$data_store['publicSessionID'], self::$data_store['member_id'], $data );
	}

	/**
	 * Finalize public member
	 *
	 * Now that everything has loaded, lets do the final set up
	 *
	 * @return	@e void
	 */
	static public function finalizePublicMember()
	{
		/* Build profile picture */
		self::$data_store = IPSMember::buildProfilePhoto( self::$data_store );

		/* SEO Name */
		if ( ! self::$data_store['members_seo_name'] )
		{
			self::$data_store['members_seo_name'] = IPSMember::fetchSeoName( self::$data_store );
		}
		
		/* Rebuild messenger count if triggered */
		if( self::$data_store['msg_count_reset'] )
		{
			/* Just instantiating the class will perform reset */
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
			$messenger		= new $classToLoad( ipsRegistry::instance() );
		}
	}

	/**
	 * Set up a member
	 *
	 * @return	@e void
	 */
	static protected function setUpMember()
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------

        $cache = ipsRegistry::cache()->getCache('group_cache');

		//-----------------------------------------
		// Unpack cache
		//-----------------------------------------
		
		if ( isset(self::$data_store['members_cache']) )
		{
			self::$data_store['_cache'] = IPSMember::unpackMemberCache( self::$data_store['members_cache'] );
		}
		else
		{
			self::$data_store['_cache'] = array();
		}

		if ( ! isset( self::$data_store['_cache']['friends'] ) or ! is_array( self::$data_store['_cache']['friends'] ) )
		{
			self::$data_store['_cache']['friends'] = array();
		}
		
		//-----------------------------------------
		// Unpack ignored users
		//-----------------------------------------
		
		if ( isset(self::$data_store['ignored_users']) )
		{
			self::$data_store['_ignoredUsers'] = @unserialize( self::$data_store['ignored_users'] );
		}
		else
		{
			self::$data_store['_ignoredUsers'] = array();
		}
	
		//-----------------------------------------
        // Set up main 'display' group
        //-----------------------------------------
        
        if( is_array( $cache[ self::$data_store['member_group_id'] ] ) )
        {
        	self::$data_store = array_merge( self::$data_store, $cache[ self::$data_store['member_group_id'] ] );
    	}
    	
		//-----------------------------------------
		// Work out permissions
		//-----------------------------------------

		self::$data_store = self::instance()->setUpSecondaryGroups( self::$data_store );
		
		/* Ensure we don't have a ,, string */
		self::$data_store['org_perm_id'] = IPSText::cleanPermString( self::$data_store['org_perm_id'] );
		
		self::instance()->perm_id       = ! empty(self::$data_store['org_perm_id']) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
        self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );

        //-----------------------------------------
        // Synchronise the last visit and activity times if
        // we have some in the member profile
        //-----------------------------------------

        if ( ! self::$data_store['last_activity'] )
       	{
       		self::$data_store['last_activity'] = IPS_UNIX_TIME_NOW;
       	}

		//-----------------------------------------
		// If there hasn't been a cookie update in 2 hours,
		// we assume that they've gone and come back
		//-----------------------------------------

		if ( ! self::$data_store['last_visit'] )
		{
			//-----------------------------------------
			// No last visit set, do so now!
			//-----------------------------------------

			ipsRegistry::DB()->update( 'members', array( 'last_visit' => self::$data_store['last_activity'], 'last_activity' => IPS_UNIX_TIME_NOW ), "member_id=".self::$data_store['member_id'], true );
			self::$data_store['last_visit'] = self::$data_store['last_activity'];

		}
		else if ( ( IPS_UNIX_TIME_NOW  - self::$data_store['last_activity']) > 300 )
		{
			//-----------------------------------------
			// If the last click was longer than 5 mins ago and this is a member
			// Update their profile.
			//-----------------------------------------

			$be_anon = IPSMember::isLoggedInAnon( self::$data_store );

			ipsRegistry::DB()->update( 'members', array( 'login_anonymous' => "{$be_anon}&1", 'last_activity' => IPS_UNIX_TIME_NOW ), 'member_id=' . self::$data_store['member_id'], true );
		}
		
		//-----------------------------------------
		// Group promotion based on time since joining
		//-----------------------------------------
		
		/* Are we checking for auto promotion? */
		if ( self::$data_store['g_promotion'] != '-1&-1' )
		{
			/* Are we checking for post based auto incrementation? 0 is post based, 1 is date based, so...  */
			if ( self::$data_store['gbw_promote_unit_type'] )
			{
				list($gid, $gdate) = explode( '&', self::$data_store['g_promotion'] );
			
				if ( $gid > 0 and $gdate > 0 )
				{
					if ( self::$data_store['joined'] <= ( time() - ( $gdate * 86400 ) ) )
					{
						IPSMember::save( self::$data_store['member_id'], array( 'core' => array( 'member_group_id' => $gid ) ) );
						
						/* Now reset the members group stuff */
						self::$data_store = array_merge( self::$data_store, $cache[ $gid ] );
						
						self::$data_store = self::instance()->setUpSecondaryGroups( self::$data_store );

						self::instance()->perm_id       = !empty(self::$data_store['org_perm_id']) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
				        self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );
					}
				}
			}
		}
	}
}