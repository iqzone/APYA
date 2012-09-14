<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer: ADMIN file
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


class install_admin extends ipsCommand
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
		$_e = 0;
		
		/* Check input? */
		if( $this->request['do'] == 'check' )
		{
			if( ! $this->request['username'] )
			{
				$_e = 1;
				$this->registry->output->addWarning( 'You must specify a display name for the admin account' );
			}
		
			if( ! $this->request['password'] )
			{
				$_e = 1;
				$this->registry->output->addWarning( 'You must specify a password for the admin account' );	
			}
			else 
			{
				if( $this->request['password'] != $this->request['confirm_password']	)
				{
					$_e = 1;
					$this->registry->output->addWarning( 'The admin passwords did not match' );	
				}
			}
			
			if( ! $this->request['email'] OR IPSText::checkEmailAddress( $this->request['email'] ) !== TRUE )
			{
				$_e = 1;
				$this->registry->output->addWarning( 'You must specify an email address for the admin account' );	
			}
			
			if ( $_e )
			{
				$this->registry->output->setTitle( "Admin: Errors" );
				$this->registry->output->setNextAction( 'admin&do=check' );
				$this->registry->output->addContent( $this->registry->output->template()->page_admin() );
				$this->registry->output->sendOutput();	
			}
			else 
			{
				/* Save Form Data */
				IPSSetUp::setSavedData('admin_user',       $this->request['username'] );
				IPSSetUp::setSavedData('admin_pass',       $this->request['password'] );
				IPSSetUp::setSavedData('admin_email',      $this->request['email'] );

				/* Next Action */
				$this->registry->autoLoadNextAction( 'install' );
				return;				
			}		
		}

		/* Output */
		$this->registry->output->setTitle( "Admin Account Creation" );
		$this->registry->output->setNextAction( 'admin&do=check' );
		$this->registry->output->addContent( $this->registry->output->template()->page_admin() );
		$this->registry->output->sendOutput();
	}
}