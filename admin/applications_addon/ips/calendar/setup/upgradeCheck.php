<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Calendar - requirements checker
 * Last Updated: $Date: 2011-12-01 11:57:36 -0500 (Thu, 01 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: mark $ (Orginal: Mark)
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Calendar
 * @link		http://www.invisionpower.com
 * @since		8th September 2010
 * @version		$Revision: 9927 $
 */

class calendar_upgradeCheck
{
	/**
	 * Check we can upgrade
	 *
	 * @return	mixed	Boolean true or error message
	 */
	public function checkForProblems()
	{
		//-----------------------------------------
		// Compatibility check
		//-----------------------------------------
		
		$requiredIpbVersion = 32003; // 3.2.0
		
		$args = func_get_args();
		if ( !empty( $args ) )
		{
			$numbers = IPSSetUp::fetchAppVersionNumbers( 'core' );
		
			/* Are we upgrading core now? */
			if ( isset( $args[0]['core'] ) )
			{
				$ourVersion = $numbers['latest'][0];
			}
			/* No - check installed version */
			else
			{
				$ourVersion = $numbers['current'][0];;
			}
			
			if ( $requiredIpbVersion > $ourVersion )
			{
				$allVersions = IPSSetUp::fetchXmlAppVersions( 'core' );
				
				return "This version of IP.Calendar requires IP.Board {$allVersions[ $requiredIpbVersion ]} or higher.";
			}
		}
		
		return TRUE;
	}
}