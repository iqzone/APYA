<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Config for about me tab
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
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
$CONFIG['plugin_name']        = 'Recent Status Updates';

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = 'pp_tab_statusupdates';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'status';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = intval( ipsRegistry::$settings['su_enabled'] );

/**
* Order
*/
$CONFIG['plugin_order'] = 1;
