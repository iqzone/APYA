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

/**
 *
 * @class		public_blog_manage_dashboard
 * @brief		Manage and create blogs
 */
class public_blog_manage_dashboard extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Check member */
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 20600000, null, null, 403 );
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
	
		$this->blogFunctions = $this->registry->getClass('blogFunctions');
		
		$this->registry->output->addToDocumentHead('javascript', $this->settings['public_dir'] . 'js/ips.blog.js');
		$this->registry->output->addNavigation( $this->lang->words['blist_blogs'], 'app=blog', 'false', 'app=blog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_manage'], 'app=blog&module=manage', 'false', 'manageblog' );
		$this->registry->output->setTitle( $this->lang->words['blog_manage'] . ' - ' . $this->settings['board_name'] );
		
		$output = '';
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch ( $this->request['act'] )
		{
			case 'create':
				$output = $this->createForm();
				break;
			case 'delete':
				$output = $this->delete();
				break;
			case 'dashboard':
			default:
				$output = $this->dashboard();
				break;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->addContent( $output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Action: Show Dashboard
	 *
	 * @param	string	Error message to display
	 * @return	string	Output
	 */
	protected function dashboard( $error='' )
	{
		/* Get my blogs */
		$myBlogs = $this->blogFunctions->fetchMyBlogs( $this->memberData, true );
		
		/* Format blogs into dropdown in case we want to delete a blog or move entries */
		$dropdown = '';
		
		if ( count($myBlogs) )
		{
			$dropdown = array();
			
			foreach( $myBlogs as $id => $blog )
			{
				if( $blog['_type'] != 'editor' AND $blog['_type'] != 'privateclub' )
				{
					$dropdown[ $blog['blog_id'] ] = $blog['blog_name'];
				}
			}
			
			$dropdown = IPSText::simpleJsonEncode( $dropdown );
		}
				
		/* Get Latest Comments */
		$latestComments    = $this->blogFunctions->fetchBlogComments( array_keys( $myBlogs ), 5, array( 'field' => 'comment_id', 'direction' => 'DESC' ), false, 200, false, true );
		
		/* Output */
		return $this->registry->getClass('output')->getTemplate('blog_manage')->manageDashboard( $myBlogs, $latestComments, $error, $dropdown );
	}
		
	/**
	 * Action: Delete Blog
	 */
	protected function delete()
	{
		$blogId = intval( $this->request['blogid'] );
		$moveTo = intval( $this->request['moveTo'] );
		
		if( $this->request['form_hash'] != $this->member->form_hash OR ! $this->memberData['g_blog_allowdelete'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 20600002, null, null, 403 );
		}
		
		/* Can we moderate our own blogs? */
		if ( ! $this->blogFunctions->ownsBlog( $blogId ) )
		{
			$this->registry->output->showError( 'blog_no_permission', 20600001, null, null, 403 );
		}
		
		if ( $moveTo )
		{
			if ( ! $this->blogFunctions->ownsBlog( $moveTo ) )
			{
				$this->registry->output->showError( 'blog_no_permission', 20600001, null, null, 403 );
			}
			
			/* Move entries */
			$this->blogFunctions->moveBlogContent( $blogId, $moveTo );
		}
		
		/* Delete Blog */
		$this->blogFunctions->removeBlog( $blogId );
		
		/* Go home! */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog&module=manage', 'false', true, 'manageblog' );
	}
	
	/**
	 * Action: Create Blog
	 *
	 * @return	string	Output
	 */
	protected function createForm()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->registry->output->setTitle( $this->lang->words['blog_button_start'] . ' - ' . $this->settings['board_name'] );
		
		//-----------------------------------------
		// Checks
		//-----------------------------------------
		
		/* CSRF Check */
		if ( $this->request['form_hash'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'blog_no_permission', 2063, null, null, 403 );
		}
				
		/* Check that they are allowed to create a blog */
		if( !$this->memberData['g_blog_allowcreate'] && !$this->memberData['g_blog_allowlocal'] )
		{
			$this->registry->output->showError( 'no_blog_create_permission', 1061, null, null, 403 );
		}
		
		/* And that they can create more blogs */
		if( ! $this->blogFunctions->checkMaxBlogs() )
		{
			$this->registry->output->showError( 'no_more_blogs_for_you', 10622, null, null, 403 );
		}
		
		//-----------------------------------------
		// What stage are we on?
		//-----------------------------------------
		
		$method = '_create_step' . intval( $this->request['stage'] );
		
		if ( method_exists( $this, $method ) )
		{
			return $this->$method();
		}
		else
		{
			return $this->_create_step1();
		}
	}
	
	/**
	 * Create Blog: Step One
	 *
	 * @param	string	Error message to display
	 * @return	string	Output
	 */
	protected function _create_step1( $error='' )
	{	
		return $this->registry->getClass('output')->getTemplate('blog_manage')->create1( $error );
	}
	
	/**
	 * Create Blog: Step Two
	 *
	 * @param	string	Error message to display
	 * @return	string	Output
	 */
	protected function _create_step2( $error='' )
	{
		/* Check the user has agreed to the terms */
		if( empty( $this->request['agree_to_terms'] ) )
		{
			return $this->_create_step1( $this->lang->words['blog_must_agree'] );
		}
	
		$myblog = array();
		
		// Errors? Repopulate the $myblog array:
		if ( !empty($error) )
		{
			$myblog['blog_name'] = $this->request['blog_name'];
			$myblog['blog_type'] = $this->request['blog_type'];
			$myblog['blog_desc'] = $this->request['blog_desc'];
			
			switch ($myblog['blog_type'])
			{
				case 'local':
					$myblog['type_local'] = "selected='selected'";
					break;
				case 'external':
					$myblog['type_external'] = "selected='selected'";
					break;
				case 'rssfeed':
					$myblog['type_rssfeed'] = "selected='selected'";
					break;
			}
		}

		if ( !$myblog['blog_name'] )
		{
			if ( strtolower( substr( $this->memberData['members_display_name'], -1 ) ) == "s" )
			{
				$myblog['blog_name'] = trim($this->memberData['members_display_name']).$this->lang->words['blogname_end_s'];
			}
			else
			{
				$myblog['blog_name'] = trim($this->memberData['members_display_name']).$this->lang->words['blogname_noend_s'];
			}
		}
		
		return $this->registry->getClass('output')->getTemplate('blog_manage')->create2( $myblog, $error );
	}
	
	/**
	 * Create Blog: Step Three
	 *
	 * @return	string	Output
	 */
	protected function _create_step3()
	{		
		/* set up array */
		$blog = array( 'blog_name' => $this->request['blog_name'],
					   'blog_desc' => $this->request['blog_desc'],
					   'blog_type' => $this->request['blog_type'] );
		
		/* Create the blog, then! */
		try
		{
			$blog = $this->blogFunctions->createBlog( $blog );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			$this->lang->loadLanguageFile( 'public_error', 'core' );
			
			switch( $msg )
			{
				case 'NO_NAME':
					return $this->_create_step2( $this->lang->words['no_name_entered'] );
				break;
				case 'NO_OWNER':
					return $this->_create_step2( $this->lang->words['incorrect_use'] );
				break;
				case 'MAX_BLOGS_REACHED':
					return $this->_create_step2( $this->lang->words['no_more_blogs_for_you'] );
				break;
				case 'NOT_ALLOWED_LOCAL':
					return $this->_create_step2( $this->lang->words['incorrect_use'] );
				break;
				case 'NOT_ALLOWED_EXT':
					return $this->_create_step2( $this->lang->words['incorrect_use'] );
				break;
				case 'CONFIG_ERROR':
					return $this->_create_step2( $this->lang->words['incorrect_use'] );
				break;
			}
		}
		
		/* Bounce to blog list */
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=blog&module=manage&section=settings&blogid={$blog['blog_id']}&create=1", 'false', true, 'manageblog' );
	}
}