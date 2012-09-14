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

class classes_tags_bootstrap
{
	/**
	 * App object
	 *
	 * @var array
	 */
	static protected $apps;
	
	/**
	 * Construct
	 *
	 * @param	string		Application (or aai key)
	 * @param	string		Area
	 * @return	string
	 */
	public static function run( $app=null, $area=null )
	{
		if ( strlen( $app ) == 32 AND $area === null )
		{
			$test = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*',
									  		  		 		 'from'   => 'core_tags',
									 		  		 		 'where'  => 'tag_aai_lookup=\'' .  ipsRegistry::DB()->addSlashes( $app ) . '\'',
									  		  		 		 'limit'  => array( 0, 1 ) ) );
			
			if ( $test['tag_meta_app'] && $test['tag_meta_area'] )
			{
				$app  = $test['tag_meta_app'];
				$area = $test['tag_meta_area'];
			}
		}
		
		if ( $app === null OR $area === null )
		{
			trigger_error( "App or area missing from classes_like", E_USER_WARNING );
		}
		
		/* Pointless comment! */
		$_file	= IPSLib::getAppDir( $app ) . '/extensions/tags/' . $area . '.php';
		$_key	= ( $app && $area ) ? md5( $app . $area ) : 'default';
		 
		/* Get from cache if already cached */
		if ( isset( self::$apps[ $_key ] ) )
		{
			return self::$apps[ $_key ];
		}
		
		/* Get other classes */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/abstract.php');/*noLibHook*/
		
		if ( $app && $area )
		{
			/* Otherwise create object and cache */
			if ( is_file( $_file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $_file, 'tags_' . $app . '_' . $area, $app );
				
				if ( class_exists( $classToLoad ) )
				{
					self::$apps[ $_key ] = new $classToLoad();
					self::$apps[ $_key ]->setApp( $app );
					self::$apps[ $_key ]->setArea( $area );
					self::$apps[ $_key ]->init();
					
				}
				else
				{
					throw new Exception( "No tags class available for $app - $area" );
				}
			}
			else
			{
				/* Allow an application to worry about the 'area' */
				if( is_file( IPSLib::getAppDir( $app ) . '/extensions/tags/default.php' ) )
				{
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/tags/default.php', 'tags_' . $app . '_default', $app );
					
					if ( class_exists( $classToLoad ) )
					{
						self::$apps[ $_key ] = new $classToLoad();
						self::$apps[ $_key ]->setApp( $app );
						self::$apps[ $_key ]->setArea( $area );
						self::$apps[ $_key ]->init();
					}
					else
					{
						throw new Exception( "No tags class available for $app - $area" );
					}
				}
				else
				{
					throw new Exception( "No tags class available for $app - $area" );
				}
			}
		}
		else
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/tags/extensions/default.php', 'tags_default' );
			self::$apps[ $_key ] = new $classToLoad();
			self::$apps[ $_key ]->setApp( $app );
			self::$apps[ $_key ]->setArea( $area );
			self::$apps[ $_key ]->init();
		}
		
		return self::$apps[ $_key ];
	}
}