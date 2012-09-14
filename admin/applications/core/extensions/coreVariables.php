<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core registered caches, redirect resets and bitwise settings
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
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
 * Which caches to load by default
 */
$_LOAD = array();

if( IN_ACP AND ( !isset($_GET['module']) OR $_GET['module'] == 'mycp' ) )
{
	$_LOAD['stats']				= 1;
}

if( isset($_GET['module']) )
{
	if ( $_GET['module'] == 'search' )
	{
		$_LOAD['ranks']				= 1;
		$_LOAD['bbcode']			= 1;
		$_LOAD['emoticons']			= 1;
		$_LOAD['reputation_levels']	= 1;
		$_LOAD['attachtypes']		= 1;
	}
	
	if ( $_GET['module'] == 'reports' )
	{
		$_LOAD['ranks']				= 1;
		$_LOAD['bbcode']			= 1;
		$_LOAD['emoticons']			= 1;
		$_LOAD['reputation_levels']	= 1;
		$_LOAD['moderators']		= 1;
	}
	
	if( $_GET['module'] == 'usercp' )
	{
		$_LOAD['ranks']				= 1;
		$_LOAD['reputation_levels']	= 1;
		$_LOAD['emoticons']         = 1;
	}
	
	if ( $_GET['module'] == 'modcp' )
	{
		$_LOAD['moderators']		= 1;
		$_LOAD['ranks']				= 1;
		$_LOAD['bbcode']			= 1;
		$_LOAD['emoticons']			= 1;
		$_LOAD['reputation_levels']	= 1;
		$_LOAD['attachtypes']		= 1;
	}
	
	if( IN_ACP AND $_GET['module'] == 'applications' AND $_GET['section'] == 'hooks' )
	{
		$_LOAD['disabledHooksCache']	= 1;
	}
}
else if( IN_ACP )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['moderators']		= 1;
	$_LOAD['adminnotes']		= 1;
	$_LOAD['performanceCache']	= 1;
	$_LOAD['sphinx_config']		= 1;
}


/* Never, ever remove or re-order these options!!
 * Feel free to add, though. :) */

$_BITWISE = array();
