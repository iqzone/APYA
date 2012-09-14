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

class public_blog_manage_comments extends ipsCommand
{	
	public function doExecute( ipsRegistry $registry )
	{
		$this->blogFunctions = $this->registry->getClass('blogFunctions');
				
		switch($this->request['act'])
		{
			case 'moderate':
				$output = $this->_moderateComments();
			break;
			
			case 'list':
			default:
				$output = $this->_listComments();
			break;
		}
		
		$this->registry->output->addNavigation( $this->lang->words['blist_blogs'], 'app=blog', 'false', 'app=blog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_manage'], 'app=blog&module=manage', 'false', 'manageblog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_manage_comments'], 'app=blog&module=manage&section=comments&act=list&blogid='.$this->request['blogid'], 'false', 'manageblog' );
		
		$this->registry->output->setTitle( $this->lang->words['blog_manage_comments'] . ' - ' . $this->lang->words['blog_manage'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $output );
		$this->registry->output->sendOutput();
	}
	
	protected function _listComments()
	{
		$blogid  = intval( $this->request['blogid'] );
		$st		 = intval( $this->request['st'] );
		$perPage = 10;
		$blog    = $this->blogFunctions->loadBlog( $blogid );
		
		/* Check */
		if ( ! $blog['blog_id'] OR ( ! $this->blogFunctions->ownsBlog( $blog ) ) )
		{
			$this->registry->output->showError( 'blog_no_permission', 2060, null, null, 403 );
		}
				
		/* Pagination */
		$max = $this->DB->buildAndFetch( array( 'select' => "SUM(entry_num_comments) as live, SUM(entry_queued_comments) as queued",
												'from'   => "blog_entries",
												'where'  => "blog_id=" . $blogid ) );

		$pagination	= $this->registry->output->generatePagination( array( 'totalItems'		  => intval( $max['live'] ) + intval( $max['queued'] ),
																	  'itemsPerPage'	  => $perPage,
																	  'currentStartValue' => $st,
																	  'baseUrl'			  => "app=blog&amp;module=manage&amp;section=comments&amp;act=list&amp;blogid=" . $blogid ) );
						
		/* Get comments */
		$comments     = $this->blogFunctions->fetchBlogComments( array( $blogid ), $perPage, array( 'field' => 'comment_id', 'direction' => 'DESC', 'offset' => $st ), false, 0, true, true);
		
		/* Output */
		return $this->registry->output->getTemplate('blog_manage')->manageComments($comments, $blog, $pagination);
	}
	
	protected function _moderateComments()
	{	
		// Check we're allowed to moderate blogs:
		if ( ! $this->memberData['g_blog_allowownmod'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 20600001, null, null, 403 );
		}
		
		// Check secure key:
		if( $this->request['form_hash'] != $this->member->form_hash)
		{
			$this->registry->output->showError( 'blog_no_permission', 20600002, null, null, 403 );
		}
		
		// Check data:
		$_mod_ids = IPSLib::cleanIntArray( $this->request['modIds'] );
		$_mod_opt = $this->request['modOption'];
		
		if( !is_array($_mod_ids) )
		{
			$this->registry->output->showError( 'incorrect_use', 106174, null, null, 404 );
		}
		
		// Do moderation:
		switch( $_mod_opt )
		{
			case 'delete':
				$res = $this->blogFunctions->deleteComments( array_keys( $_mod_ids ) );
			break;
			case 'approve':
				$res = $this->blogFunctions->changeCommentApproval( array_keys( $_mod_ids ), 1 );
			break;
			case 'unapprove':
				$res = $this->blogFunctions->changeCommentApproval( array_keys( $_mod_ids ), 0 );
			break;
		}
				
		// Return:
		if( !empty($this->request['returnTo']) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . $this->request['returnTo'] );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog&module=manage', 'false', true, 'manageblog' );
		}
	}
}