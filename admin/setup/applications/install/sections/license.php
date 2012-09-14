<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer: License Key
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


class install_license extends ipsCommand
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
		$lcheck = '';
		if ( $this->request['do'] == 'check' )
		{
			$lcheck = $this->check();
			if ( $lcheck === TRUE )
			{
				$this->registry->autoLoadNextAction( 'db' );
				return;
			}
		}
	
		$this->registry->output->setTitle( "License Key" );
		$this->registry->output->setNextAction( "license&do=check" );
		$this->registry->output->addContent( $this->registry->output->template()->page_license( $lcheck ) );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Check License Key
	 *
	 * @access	public
	 * @return	bool
	 */
	private function check()
	{
		$this->request['lkey'] = trim( $this->request['lkey'] );
		
		// License key is optional
		if( ! $this->request['lkey'] )
		{
			return true;
		}
		
		$url = IPSSetup::getSavedData( 'install_url' );
		
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );/*noLibHook*/
		$query = new classFileManagement();
		$response = $query->getFileContents( "http://license.invisionpower.com/?a=check&key={$this->request['lkey']}&url={$url}" );
		$response = json_decode( $response, true );
		
		if( $response['result'] != 'ok' )
		{
			return "Your license key could not be activated. Please check your key and try again. If the problem persists, please contact technical support.";
		}
		else
		{
			IPSSetup::setSavedData( 'lkey', $this->request['lkey'] );
			return TRUE;
		}
						
	}
}