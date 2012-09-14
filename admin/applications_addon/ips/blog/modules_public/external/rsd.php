<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog RSD support
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_actions_rsd extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Get the Blog info
		//-----------------------------------------

		$blogid		= $this->request['blogid'] ? intval($this->request['blogid']) : 0;
		$blog		= $this->registry->getClass('blogFunctions')->loadBlog( $blogid );

		$blog_url	= preg_replace( "#\?$#is", "", preg_replace( "#\&amp;$#is", "", $this->registry->getClass('blogFunctions')->getBlogUrl( $blogid ) ) );

		//-----------------------------------------
		// Do we report the apis?
		//-----------------------------------------

		$apis = "";

		if ( $this->settings['blog_allow_xmlrpc'] && $blog['blog_settings']['enable_xmlrpc'] )
		{
			$apis  = "\t\t\t<api name=\"MetaWeblog\" preferred=\"true\" apiLink=\"{$this->settings['board_url']}/interface/blog/xmlrpc.{$this->settings['php_ext']}\" blogID=\"{$blogid}\"/>\n";
			$apis .= "\t\t\t<api name=\"Blogger\" preferred=\"false\" apiLink=\"{$this->settings['board_url']}/interface/blog/xmlrpc.{$this->settings['php_ext']}\" blogID=\"{$blogid}\"/>\n";
		}

		//-----------------------------------------
		// Build the XML
		//-----------------------------------------

		$rsd_xml  = "<?xml version=\"1.0\"?>\n";
		$rsd_xml .= "<rsd version=\"1.0\" xmlns=\"http://archipelago.phrasewise.com/rsd\">\n";
		$rsd_xml .= "\t<service>\n";
		$rsd_xml .= "\t\t<engineName>IP.Blog ".BLOG_VERSION."</engineName>\n";
		$rsd_xml .= "\t\t<engineLink>http://www.invisionblog.com/</engineLink>\n";
		$rsd_xml .= "\t\t<homePageLink>{$blog_url}</homePageLink>\n";
		$rsd_xml .= "\t\t<apis>\n";
		$rsd_xml .= $apis;
		$rsd_xml .= "\t\t</apis>\n";
		$rsd_xml .= "\t</service>\n";
		$rsd_xml .= "</rsd>";

		//-----------------------------------------
		// Output the XML
		//-----------------------------------------

		@header( 'Content-Type: text/xml' );
		@header( 'Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT' );
		@header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		@header( 'Pragma: public' );

		echo $rsd_xml;
	}
}