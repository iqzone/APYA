<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Config for blog tab
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Plug in name (Default tab name)
*/
$CONFIG['plugin_name']        = 'Blogs';

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = 'pp_tab_blog';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'blog';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = IPSLib::appIsInstalled( 'blog' ) ? 1 : 0;

/**
* Order: CANNOT USE 1
*/
$CONFIG['plugin_order'] = 5;

?>