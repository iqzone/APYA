<?php
/**
 * @file		attach.php 	Provides ajax methods to switch uploader type
 *
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-05-25 10:30:28 -0400 (Wed, 25 May 2011) $
 * @version		v3.3.3
 * $Revision: 8887 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_core_ajax_attach
 * @brief		Provides ajax methods for the attach functions
 */
class public_core_ajax_attach extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Guest? */
		if ( !$this->memberData['member_id'] )
		{
			$this->returnJsonError('no_permission');
		}
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'setPref':
				$this->_setPref();
				break;
        }
    }
    
	/**
     * Sets uploader preference
     *
     * @return	@e void
     */
    protected function _setPref()
    {
    	/* Init */
    	$uploader = ( $this->request['pref'] == 'flash' ) ? 'flash' : 'default';
    
    	IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'member_uploader' => $uploader ) ) );
    		
 		/* Fetch data */
 		return $this->returnJsonArray( array( 'status' => 'ok' ) );
    }
}