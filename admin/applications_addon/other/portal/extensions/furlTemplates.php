<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_SEOTEMPLATES = array(				
						'app=portal'		=> array( 
											'app'			=> 'portal',
											'allowRedirect' => 1,
											'out'			=> array( '#app=portal$#i', 'portal/' ),
											'in'			=> array( 
																		'regex'		=> "#/portal(/|$|\?)#i",
																		'matches'	=> array( array( 'app', 'portal' ) )
																	) 
														),
					);
