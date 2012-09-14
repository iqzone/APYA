<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * INIT File - Sets up globals
 * Last Updated: $Date: 2012-05-22 09:39:17 -0400 (Tue, 22 May 2012) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2008 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10778 $
 *
 */

// Get constants.php if it exists - this can be used to
// override any of the constants defined in this file without
// being overwritten when IP.Board is updated
if ( is_file( dirname( __FILE__ ) . '/constants.php' ) )
{
	require_once( dirname( __FILE__ ) . '/constants.php' );/*noLibHook*/
}

if ( @function_exists( 'memory_get_usage' ) )
{
	define( 'IPS_MEMORY_START', memory_get_usage() );
}

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: FOLDER AND FILE NAMES
//--------------------------------------------------------------------------

/**
* CP_DIRECTORY
*
* The name of the CP directory
* @since 2.0.0.2005-01-01
*/
if ( !defined( 'CP_DIRECTORY' ) )
{
	define( 'CP_DIRECTORY', 'admin' );
}

/**
 * PUBLIC_DIRECTORY
 *
 * The name of the public directory
 */
if ( !defined( 'PUBLIC_DIRECTORY' ) )
{
	define( 'PUBLIC_DIRECTORY', 'public' );
}

/**
 * Default app name
 * You can set this in your own scripts before 'initdata.php' is required.
 */
if ( ! defined( 'IPS_DEFAULT_PUBLIC_APP' ) )
{
	define( 'IPS_DEFAULT_PUBLIC_APP', 'portal' );
}

/**
* PUBLIC SCRIPT
*/
if ( ! defined( 'IPS_PUBLIC_SCRIPT' ) )
{
	define( 'IPS_PUBLIC_SCRIPT', strtolower( basename( $_SERVER['SCRIPT_NAME'] ) ) );
}

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: MAIN PATHS
//--------------------------------------------------------------------------

/**
* "PUBLIC" ROOT PATH
*/
if ( !defined( 'DOC_IPS_ROOT_PATH' ) )
{
	define( 'DOC_IPS_ROOT_PATH', str_replace( "\\", "/", dirname( __FILE__ ) ) . '/' );
}

/**
* "ADMIN" ROOT PATH
*/
if ( !defined( 'IPS_ROOT_PATH' ) )
{
	define( 'IPS_ROOT_PATH', DOC_IPS_ROOT_PATH . CP_DIRECTORY . "/" );
}

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: OTHER PATHS
//--------------------------------------------------------------------------

/**
 * PUBLIC PATH
 */
if ( !defined( 'IPS_PUBLIC_PATH' ) )
{
	define( 'IPS_PUBLIC_PATH', DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/' );
}

/**
* IPS KERNEL PATH
*/
if ( !defined( 'IPS_KERNEL_PATH' ) )
{
	define( 'IPS_KERNEL_PATH', DOC_IPS_ROOT_PATH . 'ips_kernel/' );
}

/**
* Custom log in path
*/
if ( !defined( 'IPS_PATH_CUSTOM_LOGIN' ) )
{
	define( 'IPS_PATH_CUSTOM_LOGIN' , IPS_ROOT_PATH . 'sources/loginauth' );
}

/**
* HOOKS PATH
*/
if ( !defined( 'IPS_HOOKS_PATH' ) )
{
	define( 'IPS_HOOKS_PATH'       , DOC_IPS_ROOT_PATH . 'hooks/' );
}

@set_include_path( @get_include_path() . PATH_SEPARATOR . IPS_KERNEL_PATH );

//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: USER LOCATION
//--------------------------------------------------------------------------

if( preg_match( "#/" . CP_DIRECTORY . "(/|$)#", $_SERVER['PHP_SELF'] ) AND defined('IPB_THIS_SCRIPT') AND IPB_THIS_SCRIPT == 'admin' )
{
	define( 'IPS_AREA', 'admin' );
}
else
{
	define( 'IPS_AREA', 'public' );
}

if ( !defined( 'IN_ACP' ) )
{
	define( 'IN_ACP', IPS_AREA == 'public' ? 0 : 1 );
}

/**
 * Default Application if one is not specified in the URL / POST, etc
 *
 */
if ( ! defined( 'IPS_DEFAULT_APP' ) )
{
	define( 'IPS_DEFAULT_APP', ( IPS_AREA == 'public' ) ? IPS_DEFAULT_PUBLIC_APP : 'core' );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: DEBUG
//--------------------------------------------------------------------------

/**
 * E_NOTICE / E_ALL Debug mode
 * Can capture and / or log php errors to a file (cache/phpNotices.cgi)
 * use 'TRUE' to capture all or enter comma sep. list of classes to capture, like
 * define( 'IPS_ERROR_CAPTURE', 'classItemMarking,publicSessions' );
 * Set to 'FALSE' for off
 */
if ( ! defined( 'IPS_ERROR_CAPTURE' ) )
{
	define( 'IPS_ERROR_CAPTURE', false );//'classItemMarking,publicSessions' );
}

/**
* SQL DEBUG MODE
*
* Turns on SQL debugging mode to review queries on the board. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
if ( ! defined( 'IPS_SQL_DEBUG_MODE' ) )
{
	define( 'IPS_SQL_DEBUG_MODE', 0 );
}

/**
* SQL DEBUG MODE LOGGING
*
* Turns on SQL debugging mode which logs all SQL to cache/. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
if ( ! defined( 'IPS_SQL_DEBUG_LOG' ) )
{
	define( 'IPS_SQL_DEBUG_LOG', 0 );
}

/**
* SQL LOG FILESORT or TEMP TABLES MODE
*
* Logs all queries using filesort and/or temp tables
*/
if ( ! defined( 'IPS_SQL_FIND_EVIL_MODE' ) )
{
	define( 'IPS_SQL_FIND_EVIL_MODE', false );
}

/**
* SQL FIND SLOW QUERIES MODE
*
* Logs all queries that take longer than X seconds (0.1), etc
*/
if ( ! defined( 'IPS_SQL_FIND_SLOW_MODE' ) )
{
	define( 'IPS_SQL_FIND_SLOW_MODE', false );
}

/**
* MEMORY DEBUG MODE
*
* Turns on MEMORY debugging mode. This is NOT recommended
* as it opens your board up to potential security risks
* @since 2.2.0.2006-11-06
*/
if ( ! defined( 'IPS_MEMORY_DEBUG_MODE' ) )
{
	define( 'IPS_MEMORY_DEBUG_MODE', 0 );
}

/*
* Write to a general debug file?
* IP.Board has debug messages that are sent to the log.
* The log file will fill VERY quickly, so leave this off unless you
* are debugging, etc
*/
if ( ! defined( 'IPS_LOG_ALL' ) )
{
	define( 'IPS_LOG_ALL', FALSE );
}

if ( ! defined( 'IPS_XML_RPC_DEBUG_ON' ) )
{
	/**
	* Write to debug file?
	* Enter relative / full path into the constant below
	* Remove contents to turn off debugging.
	* WARNING: If you are passing passwords and such via XML_RPC
	* AND wish to debug, ensure that the debug file ends with .php
	* to prevent it loading as plain text via HTTP which would show
	* the entire contents of the file.
	* @since 2.2.0.2006-11-06
	*/
	define( 'IPS_XML_RPC_DEBUG_ON'  , 0 );
	define( 'IPS_XML_RPC_DEBUG_FILE', str_replace( "\\", "/", dirname( __FILE__ ) ) ."/" . 'cache/xmlrpc_debug_ipboard.cgi' );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: ACP
//--------------------------------------------------------------------------

/**
* Allow IP address matching when dealing with ACP sessions
* @since 2.2.0.2006-06-30
*/
if ( ! defined( 'IPB_ACP_IP_MATCH' ) )
{
	define( 'IPB_ACP_IP_MATCH', 1 );
}

/**
* Number of minutes of inactivity in ACP before you are timed out
* @since 3.0.0
*/
if ( ! defined( 'IPB_ACP_SESSION_TIME_OUT' ) )
{
	define( 'IPB_ACP_SESSION_TIME_OUT', 60 );
}

/**
* Use GZIP page compression in the ACP
* @since 2.2.0.2006-06-30
*/
if( !@ini_get('zlib.output_compression') )
{
	define( 'IPB_ACP_USE_GZIP', 1 );
}
else
{
	define( 'IPB_ACP_USE_GZIP', 0 );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: MISC
//--------------------------------------------------------------------------

/**
* USE SHUT DOWN
*
* Enable shut down features?
* Uses PHPs register_shutdown_function to save
* low priority tasks until end of exec
* @since 2.0.0.2005-01-01
*/
if ( ! defined( 'IPS_USE_SHUTDOWN' ) )
{
	define( 'IPS_USE_SHUTDOWN', IPS_AREA == 'public' ? 1 : 0 );
}

/**
* Allow UNICODE
*/
if ( ! defined( 'IPS_ALLOW_UNICODE' ) )
{
	define( 'IPS_ALLOW_UNICODE', 1 );
}

/**
 * File and folder permissions
 */
if ( ! defined( 'IPS_FILE_PERMISSION' ) )
{
	define( 'IPS_FILE_PERMISSION', 0777 );
}
if ( ! defined( 'IPS_FOLDER_PERMISSION' ) )
{
	define( 'IPS_FOLDER_PERMISSION', 0777 );
}

/**
* Time now stamp
*/
if ( ! defined( 'IPS_UNIX_TIME_NOW' ) )
{
	define( 'IPS_UNIX_TIME_NOW', time() );
}

/* Min PHP version number */
if ( ! defined( 'MIN_PHP_VERS' ) )
{
	define( 'MIN_PHP_VERS', '5.2.0' );
}
//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: MAGIC QUOTES
//--------------------------------------------------------------------------

@set_magic_quotes_runtime(0);

if ( ! defined( 'IPS_MAGIC_QUOTES' ) )
{
	define( 'IPS_MAGIC_QUOTES', @get_magic_quotes_gpc() );
}

//--------------------------------------------------------------------------
// ADVANCED CONFIGURATION: ERROR REPORTING
//--------------------------------------------------------------------------

error_reporting( E_STRICT | E_ERROR | E_WARNING | E_PARSE | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING );

//--------------------------------------------------------------------------
// XX NOTHING USER CONFIGURABLE XX NOTHING USER CONFIGURABLE XX
//--------------------------------------------------------------------------

/**
* IN IPB
*/
define( 'IN_IPB', 1 );

/**
* SAFE MODE
*/
if ( IPS_AREA != 'public' )
{
	if ( function_exists('ini_get') )
	{
		$test = @ini_get("safe_mode");

		define( 'SAFE_MODE_ON', ( $test === TRUE OR $test == 1 OR $test == 'on' ) ? 1 : 0 );
	}
	else
	{
		define( 'SAFE_MODE_ON', 1 );
	}
}
else
{
	define( 'SAFE_MODE_ON', 0 );
}

//--------------------------------------------------------------------------
// NON-CONFIGURABLE: Attempt to sort out some defaults
//--------------------------------------------------------------------------

define( 'ORIGINAL_TIME_LIMIT', @ini_get('max_execution_time') ? @ini_get('max_execution_time') : 0 );

if ( @function_exists("set_time_limit") == 1 and SAFE_MODE_ON == 0 )
{
	if ( ( defined('IPS_IS_SHELL') AND IPS_IS_SHELL ) OR ( IPS_AREA != 'public' ) )
	{
		@set_time_limit(0);
	}
}

/**
* Fix for PHP 5.1.x warning
*
* Sets default time zone to server time zone
* @since 2.2.0.2006-05-19
*/
if ( function_exists( 'date_default_timezone_set' ) and !defined( 'DO_NOT_SET_TIMEZONE' ) ) // bug report 25566
{
	if ( ! @date_default_timezone_get() )
	{
		date_default_timezone_set( @ini_get('date.timezone') ? ini_get('date.timezone') : 'UTC' );
	}
	else
	{
		date_default_timezone_set( 'UTC' );
	}
}

//--------------------------------------------------------------------------
// NON-CONFIGURABLE: Global Functions
//--------------------------------------------------------------------------

/**
* Get an environment variable value
*
* Abstract layer allows us to user $_SERVER or getenv()
*
* @param	string	Env. Variable key
* @return	string
* @since	2.2
*/
function my_getenv($key)
{
    $return = array();

    if ( is_array( $_SERVER ) AND count( $_SERVER ) )
    {
	    if( isset( $_SERVER[$key] ) )
	    {
		    $return = $_SERVER[$key];
	    }
    }

    if ( ! $return )
    {
	    $return = getenv($key);
    }

    return $return;
}

/**
* json_encode function if not available in PHP
*
* @param	mixed 		Anything, really
* @return	string
* @since	3.0
*/
if (!function_exists('json_encode'))
{
	function json_encode( $a, $flag=null )
	{
		require_once( IPS_KERNEL_PATH . 'PEAR/JSON/JSON.php' );/*noLibHook*/

		$json = new Services_JSON();

		return $json->encode( $a );
	}
}

/**
* json_encode function if not available in PHP
*
* @param	mixed 		Anything, really
* @return	string
* @since	3.0
*/
if (!function_exists('json_decode'))
{
	function json_decode( $a, $assoc=false )
	{
		require_once( IPS_KERNEL_PATH . 'PEAR/JSON/JSON.php' );/*noLibHook*/

		if ( $assoc === TRUE )
		{
			$json = new Services_JSON( SERVICES_JSON_LOOSE_TYPE );
		}
		else
		{
			$json = new Services_JSON();
		}

		return $json->decode( $a );
	}
}

/**
* Exception error handler
*/
function IPS_exception_error( $error )
{
	@header( "Content-type: text/plain" );
	print $error;
	exit();
}

?>