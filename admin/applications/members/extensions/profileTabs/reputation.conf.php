<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Config for reputation tab
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		4th January 2012
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
$CONFIG['plugin_name']        = 'Reputation';

/**
* Language string for the tab
*/
$CONFIG['plugin_lang_bit']    = ipsRegistry::$settings['reputation_point_types'] == 'like' ? 'pp_tab_rep_likes' : 'pp_tab_rep_rep';

/**
* Plug in key (must be the same as the main {file}.php name
*/
$CONFIG['plugin_key']         = 'reputation';

/**
* Show tab?
*/
$CONFIG['plugin_enabled']     = intval( ipsRegistry::$settings['reputation_enabled'] );

/**
* Order
*/
$CONFIG['plugin_order'] = 2;
