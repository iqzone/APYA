<?php
/**
 * @file		skin.php 	Provides ajax methods for the setting a user's skin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-08-06 01:51:01 +0100 (Sat, 06 Aug 2011) $
 * @version		v3.3.3
 * $Revision: 9373 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_core_ajax_like
 * @brief		Provides ajax methods for the central like/follow class
 */
class public_core_ajax_skin extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
				/* What to do? */
		switch( $this->request['do'] )
		{
			default:
			case 'change':
				$this->_change();
			break;
        }
    }
    
	/**
     * Show more dialogue
     *
     * @param	integer		$relid		Relationship ID
     * @return	@e void
     */
    protected function _change()
    {  
    	$skinId = $this->request['skinId'];
    	
    	/* Make sure cookies are set */
    	$this->settings['no_print_header'] = 0;
    	
 		if ( is_numeric( $skinId ) )
		{
			/* Rudimentaty check */
			if ( $this->registry->output->allSkins[ $skinId ]['_youCanUse'] AND $this->registry->output->allSkins[ $skinId ]['_gatewayExclude'] !== TRUE )
			{
				if ( $this->memberData['member_id'] )
				{
					/* Update... */
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'skin' => $skinId ) ) );
				}
				else
				{
					IPSCookie::set( 'guestSkinChoice', $skinId );
				}
				
				/* Make sure mobile skin is removed */
				IPSCookie::set("mobileApp", 'false', -1);
				IPSCookie::set("mobileBrowser", 0, -1);
				
				/* remove user agent bypass */
				IPSCookie::set("uagent_bypass", 0, -1);
				
				/* Update member row */
				$this->memberData['skin'] = $skinId;
			}
		}
		else if ( $skinId == 'fullVersion' )
		{
			/* Set cookie */
			IPSCookie::set("uagent_bypass", 1, -1);
			IPSCookie::set("mobileBrowser", 0, -1);
			
			/* Set member pref if not a mobile app */
			if ( $this->memberData['userAgentType'] != 'mobileAppLegacy' && $this->memberData['userAgentType'] != 'mobileApp' )
			{
				/* Got one set by default for this gateway? */
				foreach( $this->registry->output->allSkins as $data )
				{
					/* Can use with this output format? */
					if ( $data['_gatewayExclude'] !== FALSE )
					{
						continue;
					}
				
					/* Is default for our current gateway? */
					if ( $data['set_is_default'] && $this->caches['outputformats'][ $data['set_output_format'] ]['gateway_file'] == IPS_PUBLIC_SCRIPT )
					{
						$skinId = $data['set_id'];
						break;
					}
				}
				
				/* Update... */
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'skin' => $skinId ) ) );
			}
		}
		else if ( $skinId == 'unlockUserAgent' )
		{
			$this->member->updateMySession( array( 'uagent_bypass' => 1 ) );

			/* Set cookie */
			IPSCookie::set("uagent_bypass", 1, -1);
			IPSCookie::set("mobileBrowser", 0, -1);
		}
		else if ( $skinId == 'setAsMobile' )
		{
			$this->member->updateMySession( array( 'uagent_bypass' => 0 ) );

			/* Set cookie */
			IPSCookie::set("uagent_bypass", 0, -1);
			IPSCookie::set("mobileBrowser", 1, -1);
		}
		
		$this->returnJsonArray( array( 'status' => 'ok' ) );
    }
    
}