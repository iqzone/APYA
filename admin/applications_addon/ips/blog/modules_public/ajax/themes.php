<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog AJAX theme storage
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

class public_blog_ajax_themes extends ipsAjaxCommand
{
	/**
	* Current blog
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog = array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// No guests
		//-----------------------------------------

		if ( !$this->memberData['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['no_guests'] );
		}

		/* Themes disabled? */
		if( !$this->settings['blog_themes_custom'] )
		{
			$this->returnJsonError( $this->lang->words['blog_xmltheme_none'] );
		}

		//-----------------------------------------
		// Get teh blog
		//-----------------------------------------
		
		$blog_id	   = intval( $this->request['blogid'] );
       	$this->blog    = $this->registry->getClass('blogFunctions')->getActiveBlog();
		$this->blog_id = intval( $this->blog['blog_id'] );

		//-----------------------------------------
		// Get the Blog url
		//-----------------------------------------

		$this->settings['blog_url'] = $this->registry->getClass('blogFunctions')->getBlogUrl( $blog_id );

		//-----------------------------------------
		// Are we authorized?
		//-----------------------------------------

		if( ! $this->memberData['g_blog_allowlocal'] )
		{
			$this->returnJsonError( $this->lang->words['no_blog_create_permission'] );
		}

		if ( $this->memberData['member_id'] != $this->blog['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['incorrect_use'] );
		}

		//-----------------------------------------
		// Save the theme
		//-----------------------------------------

		$content	= IPSText::stripslashes( strip_tags( urldecode( $_REQUEST['content'] ) ) );

		$this->DB->update( 'blog_blogs', array( 'blog_theme_custom' => $content, 'blog_theme_approved' => 0 ), 'blog_id=' . $this->blog['blog_id'] );
		
		$this->returnString( $this->lang->words['blog_xmltheme_save'] );
	}
}