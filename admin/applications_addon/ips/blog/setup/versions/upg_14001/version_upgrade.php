<?php

/*
+--------------------------------------------------------------------------
|   IP.Blog Component v<#VERSION#>
|   =============================================
|   by Remco Wilting
|   (c) 2001 - 2005 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionblog.com
+--------------------------------------------------------------------------
| > $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
| > $Revision: 4 $
| > $Author: ips_terabyte $
+--------------------------------------------------------------------------
|
|   > COMMUNITY BLOG SETUP INSTALLATION MODULES
|   > Script written by Matt Mecham
|   > Community Blog version by Remco Wilting
|   > Date started: 23rd April 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

// Number of Blogs updated per run
// Lower this amount if you get timeout errors during the blog upgrade and rebuild processes
define( 'UPDATE_PER_RUN'  , 50 );

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* This upgrade file is no longer needed */
		
		return true;
	}
}