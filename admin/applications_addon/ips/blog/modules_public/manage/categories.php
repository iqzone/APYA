<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		10/26/2010
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_manage_categories extends ipsCommand
{		
	public function doExecute( ipsRegistry $registry )
	{
		$this->blogFunctions = $this->registry->getClass('blogFunctions');
		
		switch($this->request['act'])
		{
			case 'manage':
				$output = $this->_manageCategories();
			break;
			
			case 'add':
				$output = $this->_addCategory();
			break;
			
			case 'list':
			default:
				$output = $this->_listCategories();
			break;
		}
		
		$this->registry->output->addNavigation( $this->lang->words['blist_blogs'], 'app=blog', 'false', 'app=blog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_manage'], 'app=blog&module=manage', 'false', 'manageblog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_ucp_cats_manage'], 'app=blog&module=manage&section=categories&act=list&blogid='.$this->request['blogid'], 'false', 'manageblog' );

		$this->registry->output->setTitle( $this->lang->words['blog_ucp_cats_manage'] . ' - ' . $this->lang->words['blog_manage'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $output );
		$this->registry->output->sendOutput();
	}
	
	protected function _listCategories()
	{
		$blogid  = intval( $this->request['blogid'] );
		$st		 = intval( $this->request['st'] );
		$blog    = $this->blogFunctions->loadBlog( $blogid );
		
		/* Check */
		if ( ! $blog['blog_id'] OR ( ! $this->blogFunctions->ownsBlog( $blog ) ) )
		{
			$this->registry->output->showError( 'blog_no_permission', 2060, null, null, 403 );
		}
			
		$subset = array_slice($blog['_categories'], $st, 20);
		
		/* Output */
		return $this->registry->output->getTemplate( 'blog_manage' )->listCategories( $blog, $subset );
	}
	
	protected function _addCategory()
	{
		$category_title = IPSText::truncate( trim( $this->request['title'] ), 32 );
		$blog_id		= intval( $this->request['blogid'] );
		
		/* Got permission to edit categories then? */
		if ( $this->blogFunctions->ownsBlog( $blog_id ) )
		{
			if ( $category_title )
			{
				$this->DB->insert( 'blog_categories', array( 'category_blog_id'   => $blog_id,
															 'category_title'     => $category_title,
															 'category_title_seo' => IPSText::makeSeoTitle( $category_title ) ) );
															 
				/* Rebuild categories for the blog */
				$this->blogFunctions->categoriesRecacheForBlog( $blog_id );
			}
		}
		
		/* Redirect back to manage categories */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog&module=manage&section=categories&blogid=' . $blog_id, 'false', true, 'manageblog' );
	}
	
	protected function _manageCategories()
	{
		/* Just the facts ma'am */
		$modIds   = IPSLib::cleanIntArray( $this->request['categories'] );
		$blog_id  = intval( $this->request['blogid'] );
		$finalIds = array();
		
		/* Can we moderate our own blogs? */
		if ( ! $this->blogFunctions->ownsBlog( $blog_id ) )
		{
			$this->registry->output->showError( 'blog_no_permission', 20600001, null, null, 403 );
		}
		
		if ( $this->request['form_hash'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'blog_no_permission', 20600002, null, null, 403 );
		}
	
		$this->blogFunctions->deleteCategories( array_keys( $modIds ) );
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog&module=manage&section=categories&blogid='.$blog_id, 'false', true, 'manageblog' );
	}
}