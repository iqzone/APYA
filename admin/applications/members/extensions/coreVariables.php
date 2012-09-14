<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Core variables extension file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_LOAD  = array();
$_RESET = array();

/* No default module? Reset to member list */
if ( !isset($_REQUEST['module']) )
{
	$_RESET['module'] = 'list';
	// Load properly the caches too
	$_GET['module']   = 'list';
}

# PERSONAL MESSAGES
if( ( isset( $_GET['module'] ) && $_GET['module'] == 'messaging' ) )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['profilefields']		= 1;
	$_LOAD['badwords']			= 1;
	$_LOAD['bbcode']			= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['moderators']		= 1;
}

# MEMBER LIST
if( isset( $_GET['module'] ) && $_GET['module'] == 'list' )
{
	$_LOAD['ranks']			= 1;
	$_LOAD['profilefields']	= 1;
	$_LOAD['reputation_levels']	= 1;
}

# ONLINE LIST
if( isset( $_GET['module'] ) && $_GET['module'] == 'online' )
{
	$_LOAD['ranks']			= 1;
	$_LOAD['profilefields']	= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['moderators']	= 1; // This is need for forums application - as there are usually people browsing topics,
								 // we should load this on the assumption it'll be needed anyways
}

# Status updates
if( isset( $_GET['section'] ) && $_GET['section'] == 'status' )
{
	$_LOAD['ranks']			= 1;
	$_LOAD['profilefields']	= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['moderators']	= 1; // This is need for forums application - as there are usually people browsing topics,
								 // we should load this on the assumption it'll be needed anyways
}

# PROFILE
if ( !empty( $_GET['showuser'] ) OR ( $_GET['module'] == 'profile' AND $_GET['section'] == 'view' ) )
{
	$_LOAD['ranks']				= 1;
	$_LOAD['profilefields']		= 1;
	$_LOAD['badwords']			= 1;
	$_LOAD['bbcode']			= 1;
	$_LOAD['emoticons']			= 1;
	$_LOAD['reputation_levels']	= 1;
	$_LOAD['moderators']		= 1;
}

if( IN_ACP )
{
	$_LOAD['moderators']		= 1;
}

/* Never, ever remove or re-order these options!!
 * Feel free to add, though. :) */

$_BITWISE = array();