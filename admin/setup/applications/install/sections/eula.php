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


class install_eula extends ipsCommand
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
		/* Simply return the EULA page */
		$this->registry->output->setTitle( "EULA" );
		$this->registry->output->addContent( $this->registry->output->template()->page_eula() );
		$this->registry->output->sendOutput();
	}
}