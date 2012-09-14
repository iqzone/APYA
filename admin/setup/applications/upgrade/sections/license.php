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


class upgrade_license extends ipsCommand
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
		/* If less than 3, just bounce out as settings tables won't be here, etc */
		if ( IPSSetUp::is300plus() !== TRUE )
		{
			$this->registry->autoLoadNextAction( 'upgrade' );
			return;
		}

		if ( $this->request['do'] == 'check' )
		{
			$lcheck = $this->check();
			if ( $lcheck === TRUE )
			{
				$this->registry->autoLoadNextAction( 'upgrade' );
				return;
			}
		}
		else
		{
			$lcheck = $this->check( TRUE );
			if ( $lcheck === TRUE )
			{
				$this->registry->autoLoadNextAction( 'upgrade' );
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
	private function check( $init=FALSE )
	{
		$this->request['lkey'] = ( $init ) ? ipsRegistry::$settings['ipb_reg_number'] : trim( $this->request['lkey'] );

		if ( !$this->request['lkey'] and !$init )
		{
			return true;
		}
							
		$url = ipsRegistry::$settings['board_url'] ? ipsRegistry::$settings['board_url'] : ipsRegistry::$settings['base_url'];
		
		require_once( IPS_KERNEL_PATH . 'classFileManagement.php' );/*noLibHook*/
		$query = new classFileManagement();
		$response = $query->getFileContents( "http://license.invisionpower.com/?a=check&key={$this->request['lkey']}&url={$url}" );
		$response = json_decode( $response, true );
						
		if( $response['result'] != 'ok' )
		{
			if ( $this->request['ignoreError'] )
			{
				return TRUE;
			}
			else
			{
				return "License key check failed. Click next to continue anyway.";
			}
		}
		else
		{
			IPSLib::updateSettings( array( 'ipb_reg_number' => $this->request['lkey'] ) );
			return TRUE;
		}
						
	}
}