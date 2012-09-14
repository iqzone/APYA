<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Last Updated: $LastChangedDate: 2011-05-18 10:35:52 -0400 (Wed, 18 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 8824 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_gallery
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
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Gallery Object */
		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$registry->setClass( 'gallery', new $classToLoad( $registry ) );
		}
		
		/* Public Side Stuff */
		if( ! IN_ACP )
		{
			/* Load the language File */
			$registry->class_localization->loadLanguageFile( array( 'public_gallery', 'public_gallery_four' ), 'gallery' );
			
			/* Set a default module */
			if ( ! ipsRegistry::$request['module'] )
			{
				ipsRegistry::$request['module'] = 'albums';
			}
		}
	}
	
	/**
	 * After output initialization
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function afterOutputInit( ipsRegistry $registry )
	{ 
		/* Public Side Stuff */
		if ( ! IN_ACP )
		{	
			$registry->getClass('gallery')->checkGlobalAccess();
			$registry->getClass('output')->addContent( $registry->getClass('output')->getTemplate('gallery_global')->globals() );
			
			if( ipsRegistry::$request['request_method'] == 'get' )
			{
				if( $_GET['autocom'] == 'gallery' or $_GET['automodule'] == 'gallery' )
				{
					$registry->output->silentRedirect( ipsRegistry::$settings['base_url'] . "app=gallery", 'false', true, 'app=gallery' );
				}
			}
		}
	}	
}