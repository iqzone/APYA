<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Sphinx Gallery Search
 * Last Updated: $Date: 2011-05-13 05:01:26 -0400 (Fri, 13 May 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8756 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

include( IPS_ROOT_PATH . '/applications_addon/ips/gallery/extensions/search/engines/sql.php' );/*noLibHook*/
