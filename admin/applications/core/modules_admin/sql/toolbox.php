<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * SQL Admin
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_sql_toolbox extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Require the right driver file */
		$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/sql/' . strtolower( ipsRegistry::dbFunctions()->getDriverType() ) . '.php', 'admin_core_sql_toolbox_module' );/*noLibHook*/
		$dbdriver = new $classToLoad();
		$dbdriver->makeRegistryShortcuts( $registry );
		$dbdriver->doExecute( $registry );
	}
}