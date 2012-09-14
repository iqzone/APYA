<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.1.0
|   =============================================
|   by Michael John
|   Copyright 2011-2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET = array();

###### Redirect requests... ######

if( ! $_REQUEST['module'] AND $_REQUEST['app'] == 'portal' )
{
	$_RESET['module'] = 'portal';
}


$_LOAD = array();


$_LOAD['portal']			= 1;
$_LOAD['emoticons']			= 1;
$_LOAD['bbcode']			= 1;
$_LOAD['badwords']			= 1;
$_LOAD['calendars']			= 1;
$_LOAD['ranks']				= 1;
$_LOAD['reputation_levels']	= 1;
$_LOAD['attachtypes']		= 1;

$CACHE['portal'] = array( 
								'array'            => 1,
								'allow_unload'     => 0,
							    'default_load'     => 1,
							    'recache_file'     => IPSLib::getAppDir( 'portal' ) . '/modules_admin/portal/portal.php',
								'recache_class'    => 'admin_portal_portal_portal',
							    'recache_function' => 'portalRebuildCache' 
							);