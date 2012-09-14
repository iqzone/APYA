<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Blog This plug in for share links library.
 *
 * Created by Mark Wade
 * Last Updated: $Date: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	© 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $
 *
 */

class sl_blogthis
{
	/**
	 * Requires a permission check
	 *
	 * @access	public
	 * @param	array		Data array
	 * @return	boolean
	 */
	public function requiresPermissionCheck( $array )
	{
		return true;
	}
	
	/**
	 * Share
	 *
	 * @access	private
	 * @param	string		Plug in
	 */
	public function share( $title, $url )
	{
		/* Init vars */
		$appChosen = NULL;
		$furlRegex = NULL;
		
		/* Which app should we load? */
		if ( !empty( ipsRegistry::$request['overrideApp'] ) )
		{
			$appChosen = trim(ipsRegistry::$request['overrideApp']);
		}
		/* If friendly URLs are off, this is really easy */
		elseif ( empty( ipsRegistry::$settings['use_friendly_urls'] ) )
		{
			$urlParsed = parse_url( $url );
			parse_str( $urlParsed['query'], $check );
			
			if ( empty( $check['app'] ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/share/links.php', 'share_links' );
				$share = new $classToLoad( ipsRegistry::instance(), 'blogthis' );
				
				$url = array_merge( $check, $share->deconstructUrl( $url ) );
				$appChosen = $url['data_app'];
			}
			else
			{
				$url       = $check;
				$appChosen = $check['app'];
			}
		}
		/* It's not that easy.. Work out what app this URL belongs to */
		else
		{
			$url = str_replace( ipsRegistry::$settings['_original_base_url'], '', $url );
			$url = str_replace( array( '/index.php/', '/index.php?/' ), '/', $url );
			
			foreach ( ipsRegistry::$applications as $app )
			{
				$_file = IPSLib::getAppDir( $app['app_directory'] ) . '/extensions/furlTemplates.php';
				
				if ( is_file( $_file ) )
				{
					$_SEOTEMPLATES = array();
					require( $_file );/*noLibHook*/
					
					foreach ( $_SEOTEMPLATES as $k => $data )
					{
						if ( preg_match( $data['in']['regex'], $url ) )
						{
							$appChosen = $app['app_directory'];
							$furlRegex = $data['in']['regex'];
							break 2;
						}
					}
				}
			}
		}
		
		/* Got a blogthis extension file for it? */
		if ( ! empty( $appChosen ) && IPSLib::appIsInstalled( $appChosen ) )
		{
			$file = '';
			
			if ( is_file( IPSLib::getAppDir( $appChosen ) . '/extensions/blogthis/bt_'. $appChosen .'.php' ) )
			{
				$file = IPSLib::getAppDir( $appChosen ) . '/extensions/blogthis/bt_'. $appChosen .'.php';
			}
			elseif ( is_file( IPSLib::getAppDir( 'blog' ) . '/extensions/blogthis/bt_'. $appChosen .'.php' ) )
			{
				$file = IPSLib::getAppDir( 'blog' ) . '/extensions/blogthis/bt_'. $appChosen .'.php';
			}
			
			if ( $file )
			{
				require_once( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogthis/bt.php' );/*noLibHook*/
				$className = IPSLib::loadLibrary( $file, 'bt_' . $appChosen, $appChosen );
				$class = new $className( ipsRegistry::instance(), $appChosen );
				
				if ( method_exists( $class, 'getIds' ) )
				{
					$ids = $class->getIds( $url, $furlRegex );
					$idString = '';
					
					foreach ( $ids as $k => $v )
					{
						$idString .= '&id' . intval( $k ) . '=' . urlencode( $v );
					}
					
					ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::getClass('output')->buildUrl( "app=blog&module=post&section=post&do=showform&btapp={$appChosen}{$idString}" ) );
				}
			}
		}
		
		/* Still here? Use a generic return then */
		$encodedTitle = base64_encode( $title );
		$encodedUrl   = base64_encode( $url );
		ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::getClass('output')->buildUrl( "app=blog&module=post&section=post&do=showform&btapp=0&title={$encodedTitle}&url={$encodedUrl}" ) );
	}
}