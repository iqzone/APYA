<?php
/**
 * @file		skin.php 	Provides methods for the setting a user's skin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		27 March 2012
 * $LastChangedDate: 2012-04-05 12:35:31 -0400 (Thu, 05 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10571 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		public_core_global_skin
 * @brief		Provides methods for the setting a user's skin
 */
class public_core_global_skin extends ipsCommand
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
     * Changes the skin ID choice for the member
     *
     * @return	@e void
     */
    protected function _change()
    {  
    	$skinId = $this->request['skinId'];
    	
    	if ( ( $this->request['skinId'] != 'setAsMobile' ) && $this->request['k'] != $this->member->form_hash )
    	{
    		$this->registry->output->showError('no_permission');
    	}
    	
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
		
		/* Redirect */
		if ( $this->settings['query_string_real'] )
		{
			$url = preg_replace( '#&k=(?:\S+?)($|&)#', '\1', str_replace( '&amp;', '&', $this->settings['query_string_real'] ) );
			$url = preg_replace( '#&settingNewSkin=(?:\S+?)($|&)#', '\1', $url );
			$url = preg_replace( '#&setAsMobile=(?:\S+?)($|&)#'   , '\1', $url );
			
			$this->registry->getClass('output')->silentRedirect( $this->settings['board_url'] . '?' . $url, '', true );
		}
		
		$this->registry->getClass('output')->silentRedirect( $this->settings['board_url'], '', true );
    }
}