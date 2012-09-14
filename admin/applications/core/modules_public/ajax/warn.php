<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.2
 * Retrieve member warn log
 * Last Updated: $Date: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 8644 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_warn extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* System disabled? */
		if ( !$this->settings['warn_on'] )
		{
			$this->lang->loadLanguageFile( array( 'public_profile' ), 'members' );
			$this->returnJsonError( $this->lang->words['ajax_no_warn'] );
		}
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'view':
				$this->_viewWarnLogs();
			break;
		}
	}

	/**
	 * Retieve warn logs and return them
	 *
	 * @return	@e void
	 */
 	protected function _viewWarnLogs()
 	{
		/* Get library and load */
 		$classToLoad	= IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_public/modcp/editmember.php', 'public_core_modcp_editmember' );
 		$warn			= new $classToLoad();
		$warn->makeRegistryShortcuts( $this->registry );
		$warn->loadData();
		
		$this->lang->loadLanguageFile( array( 'public_modcp' ), 'core' );
		
		$warnHTML	= $warn->viewLog( true );
		
		if ( $warnHTML )
		{
			$this->returnHtml( $warnHTML );
		}
		else
		{
			$this->lang->loadLanguageFile( array( 'public_profile' ), 'members' );
			$this->returnJsonError( $this->lang->words['ajax_bad_html'] );
		}
	}
}