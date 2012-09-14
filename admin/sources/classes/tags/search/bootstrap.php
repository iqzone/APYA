<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Tagging: Bootstrap
 * Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		24 Feb 2011
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classes_tags_search_bootstrap
{
	/**
	 * App object
	 *
	 * @var array
	 */
	static private $app;
	
	static private $area;
	
	/**
	 * @return the $app
	 */
	public static function getApp()
	{
		return classes_tags_search_bootstrap::$app;
	}

	/**
	 * @return the $area
	 */
	public static function getArea()
	{
		return classes_tags_search_bootstrap::$area;
	}

	/**
	 * @param array $app
	 */
	public static function setApp( $app )
	{
		classes_tags_search_bootstrap::$app = $app;
	}

	/**
	 * @param field_type $area
	 */
	public static function setArea( $area )
	{
		classes_tags_search_bootstrap::$area = $area;
	}

	/**
	 * Construct
	 *
	 * @param	string		Application
	 * @param	string		Area
	 * @return	string
	 */
	public static function init( $app='', $area='' )
	{
		$_sen = ( ipsRegistry::$settings['search_method'] == 'traditional' ) ? 'sql' : ipsRegistry::$settings['search_method'];
		
		/* Set app and area if set */
		if ( $app )
		{
			self::setApp( $app );
		}
		
		if ( $area )
		{
			self::setArea( $area );
		}
		
		/* Get other classes */
		if ( $_sen == 'sql' )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/search/sql.php');/*noLibHook*/
			return new classes_tags_search_sql();
		}
		else
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/search/sphinx.php');/*noLibHook*/
			return new classes_tags_search_sphinx();
		}
	}
}