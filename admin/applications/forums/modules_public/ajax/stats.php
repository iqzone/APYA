<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Retrieve who posted stats
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_stats extends ipsAjaxCommand 
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
    	switch( $this->request['do'] )
    	{
			case 'who':
				$this->_whoPosted();
			break;
    	}
	}

	/**
	 * Retrieve posters in a given topic
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _whoPosted()
	{
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_stats' ), 'forums' );
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('forums') . '/modules_public/extras/stats.php', 'public_forums_extras_stats' );
		$stats = new $classToLoad( $this->registry );
		$stats->makeRegistryShortcuts( $this->registry );
		
		$output	= $stats->whoPosted( true );
		
		if ( !$output )
		{
			$this->returnJsonError( $this->lang->words['ajax_nohtml_return'] );
		}
		else
		{
			$this->returnHtml( $output );
		}
	}
}