<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer: EULA file
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */


class install_done extends ipsCommand
{	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$installLocked = FALSE;
		
		/* Lock the page */
		if ( @file_put_contents( DOC_IPS_ROOT_PATH . 'cache/installer_lock.php', 'Just out of interest, what did you expect to see here?' ) )
		{
			$installLocked = TRUE;
		}
		
		/* Clean conf global */
		IPSInstall::cleanConfGlobal();
		
		/* Simply return the EULA page */
		$this->registry->output->setTitle( "Complete!" );
		$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->page_installComplete( $installLocked ) );
		$this->registry->output->sendOutput( FALSE );
	}
}