<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board - requirements checker
 * Last Updated: $Date: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $ (Orginal: Mark)
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		8th September 2010
 * @version		$Revision: 10771 $
 */

class core_upgradeCheck
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
		
		$minAppVersions = array(
			// 'app' => minVersionNumber
		);
		
		$args = func_get_args();
		if ( !empty( $args ) )
		{
			$errors = array();
			foreach ( $minAppVersions as $k => $v )
			{
				if ( !isset( ipsRegistry::$applications[ $k ] ) or !ipsRegistry::$applications[ $k ]['app_enabled'] )
				{
					continue;
				}
			
				$numbers = IPSSetUp::fetchAppVersionNumbers( $k );
			
				/* Are we upgrading this app now? */
				if ( isset( $args[0][ $k ] ) )
				{
					$ourVersion = $numbers['latest'][0];
				}
				/* No - check installed version */
				else
				{
					$ourVersion = $numbers['current'][0];
				}
				
				if ( $v > $ourVersion )
				{
					$appName = ipsRegistry::$applications[ $k ]['app_title'];
					$allVersions = IPSSetUp::fetchXmlAppVersions( $k );
					
					return "The version of {$appName} you have installed will not work with this version of IP.Board. You must upgrade {$appName} to {$allVersions[ $v ]} or higher, or disable it in the Admin CP in order continue.";
				}
			}
		}
		
		return TRUE;
	}
}