<?php
/**
 * @file		header.php 	Returns a custom blog header
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		19 Mar 2012
 * $LastChangedDate: 2012-03-19 16:54:21 -0400 (Mon, 19 Mar 2012) $
 * @version		v2.5.2
 * $Revision: 10447 $
 * 
 * @todo	This file is here for legacy compatibility with the old custom headers, needs to be removed at some point (3.0?)
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		public_blog_display_header
 * @brief		Returns a custom blog header
 */
class public_blog_display_header extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Print out the 'blank' gif */
		
		ob_start();
		@header( "Content-Type: image/gif" );
		print base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
		
		exit();
	}
}