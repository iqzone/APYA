<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Tags
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

class public_blog_ajax_categories extends ipsAjaxCommand
{
	/**
	* Current blog
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog 				= array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Guests don't have tags */
		if( ! $this->memberData['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['no_guests'] );
		}
		
		$blog_id	   = intval( $this->request['blogid'] );
       	$this->blog    = $this->registry->getClass('blogFunctions')->getActiveBlog();
		$this->blog_id = intval( $this->blog['blog_id'] );
		
		switch( $this->request['do'] )
		{
			case 'fetchCategories':
				$this->fetchCategories();
				break;
			case 'updatecategory':
			default:
				$this->updateCategory();
				break;
		}
	}
	
	/**
	 * Returns logged in usrs tags
	 *
	 * @return	@e void
	 */
	public function updateCategory()
	{		
		/* Query tags */
		$blogid         = intval( $this->request['blogid'] );
		$category_id    = intval( $this->request['category_id'] );
		$category_title = $this->convertAndMakeSafe( $_POST['category_title'], TRUE );
		
		/* Check permissions and such like and so forth */
		if ( $this->blog['member_id'] == $this->memberData['member_id'] OR $this->memberData['g_is_supmod'] )
		{
			/* Update */
			$this->DB->update( 'blog_categories', array( 'category_title'     => $category_title,
														 'category_title_seo' => IPSText::makeSeoTitle( $category_title ) ), 'category_id=' . $category_id );
																			 				
			/* Rebuild cats */
			$this->registry->blogFunctions->categoriesRecacheForBlog( $blogid );
		}
		
		$this->returnJsonArray( array( 'result' => 'ok' ) );
	}
	
	/**
	 * Returns logged in usrs tags
	 *
	 * @return	@e void
	 */
	public function fetchCategories()
	{		
		$this->returnString( IPSText::simpleJsonEncode( array( 'cats' => $this->registry->blogFunctions->fetchBlogCategories( intval($this->request['blogid']) ) ) ) );
	}
}