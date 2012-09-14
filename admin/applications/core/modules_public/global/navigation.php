<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Captcha
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_navigation extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Set up */
		$inapp = trim( $this->request['inapp'] );
		
		/* Load navigation stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/navigation/build.php', 'classes_navigation_build' );
		$navigation = new $classToLoad( $inapp );
		
		/* Return */
		$html = $this->registry->output->getTemplate( 'global_other' )->quickNavigationWrapper( $navigation->loadApplicationTabs(), $navigation->loadNavigationData(), $inapp );
		
		$this->registry->getClass('output')->setTitle( $this->lang->words['navigation_title'] );
		$this->registry->getClass('output')->addContent( $html );
        $this->registry->getClass('output')->sendOutput();
	}
}