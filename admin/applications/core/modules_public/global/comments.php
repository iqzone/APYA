<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Image Ajax
 * Last Updated: $LastChangedDate: 2012-06-07 13:14:08 -0400 (Thu, 07 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10890 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_comments extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		/* From App */
		$fromApp = trim( $this->request['fromApp'] );
		
		if( !$fromApp )
		{
			$this->registry->output->showError( 'noappcomments', 100135.8 );
		}
		
		/* Init some data */
		try
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
			$this->_comments = classes_comments_bootstrap::controller( $fromApp );
		}
		catch ( Exception $e )
		{
			$this->registry->output->showError( 'nocomment_found', '1-global-comments-noapp' );
		}
		
		/* Some templates/files are still using comment_id too, so let's work around it.. */
		if ( ! empty($this->request['comment_id']) )
		{
			$this->request['commentId'] = $this->request['comment_id'];
		}

		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'add':
				$this->_add();
			break;
			case 'delete':
				$this->_delete();
			break;
			case 'approve':
				$this->_approve();
			break;
			case 'showEdit':
				$this->_showEdit();
			break;
			case 'saveEdit':
				$this->_saveEdit();
			break;
			case 'fetchReply':
				$this->_fetchReply();
			break;
			case 'moderate':
				$this->_moderate();
			break;
			case 'hide':
				$this->_hide();
			break;
			case 'unhide':
				$this->_unhide();
			break;
			case 'findLastComment':
				$this->_findLastComment();
			break;
			case 'findComment':
				$this->_findComment();
			break;
        }
    }
    
    /**
     * Call
     *
     * @see 	http://community.invisionpower.com/tracker/issue-35002-comments-controller
     * @todo	Separate out AJAX functions into a separate controller
     */
    public function __call( $function, $args )
    {
    	$this->registry->output->showError( 'nocomment_found', '1-global-comments-nomethod' );
    }
		
	/**
	 * Find last page of comments
	 *
	 * @return	@e void		[Redirects]
	 */
	protected function _findLastComment()
	{
		/* Init */
		$parentId = intval( $this->request['parentId'] );
		
		$this->_comments->redirectToComment( 'last', $parentId );
	}
	
	/**
	 * Find last page of comments
	 *
	 * @return	@e void		[Redirects]
	 */
	protected function _findComment()
	{
		/* Init */
		$parentId   = intval( $this->request['parentId'] );
		$comment_id = intval( $this->request['commentId'] );
		
		if ( !$comment_id )
		{
			$this->registry->output->showError( 'nocomment_found', '1-global-comments-find' );
		}
		
		$this->_comments->redirectToComment( $comment_id, $parentId );
	}
    
    /**
     * Hide
     *
     * @return	@e void
     */
    protected function _hide()
    {
    	$parentId  = intval( $this->request['parentId'] );
 		$commentId = intval( $this->request['commentId'] );
 		
		/* Perm check (looks nice) */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'posting_bad_auth_key', '1-global-comments-_hide-0', null, null, 403 );
		}

 		$return = $this->_comments->hide( $parentId, $commentId, $this->request['reason'], $this->memberData );
 		
 		if ( $return === TRUE )
 		{
 			$this->_findComment();
 		}
 		else
 		{
 			$this->registry->output->showError( $return );
 		}
 		
    }
    
    /**
     * Unhide
     *
     * @return	@e void
     */
    protected function _unhide()
    {
    	$parentId  = intval( $this->request['parentId'] );
 		$commentId = intval( $this->request['commentId'] );
 		
		/* Perm check (looks nice) */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'posting_bad_auth_key', '1-global-comments-_unhide-0', null, null, 403 );
		}

 		$return = $this->_comments->unhide( $parentId, $commentId, $this->memberData );
 		
 		if ( $return === TRUE )
 		{
 			$this->_findComment();
 		}
 		else
 		{
 			$this->registry->output->showError( $return );
 		}
 		
    }
    
    /**
	 * Reply
	 *
	 * @return	@e void
	 */
	protected function _fetchReply()
	{
		/* INIT */
		$commentId = intval( $this->request['commentId'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->returnString( 'error' );
		}
		
		# Get Edit form
		try
		{
			$html = $this->_comments->fetchReply( $parentId, $commentId, $this->memberData );

			$this->returnString( $html );
		}
		catch ( Exception $error )
		{
			$this->returnString( $error->getMessage() );
		}
	}

	/**
	 * Deletes a comment
	 *
	 * @return	@e void
	 */
	protected function _delete()
	{
		/* INIT */
		$commentId = intval( $this->request['commentId'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Perm check (looks nice) */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'posting_bad_auth_key', '1-global-comments-_delete-0', null, null, 403 );
		}
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_delete-2', null, null, 403 );
		}
		
		try
		{
			$this->_comments->delete( $parentId, $commentId, $this->memberData );
			
			/* Redirect to find latest */
			if ( $this->request['modcp'] )
			{
				$app = $this->request['fromApp'];
				$app = explode( '-', $app );
				$app = $app[0];
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp='. $app .'&tab=' . $this->request['modcp'] );
			}
			else
			{
				$this->_comments->redirectToComment( 'last', $parentId );
			}
		}
		catch ( Exception $error )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_delete-2', null, null, 403 );
		}
	}
	
	/**
	 * Approves a comment
	 *
	 * @return	@e void
	 */
	protected function _approve()
	{
		/* INIT */
		$commentId = intval( $this->request['commentId'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Perm check (looks nice) */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'posting_bad_auth_key', '1-global-comments-_approve-0', null, null, 403 );
		}
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_approve-2', null, null, 403 );
		}
		
		try
		{
			$this->_comments->visibility( 'on', $parentId, $commentId, $this->memberData );
			
			if ( $this->request['modcp'] )
			{
				$app = $this->request['fromApp'];
				$app = explode( '-', $app );
				$app = $app[0];
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp='. $app .'&tab=' . $this->request['modcp'] );
			}
			else
			{
				$this->_comments->redirectToComment( $commentId, $parentId );
			}
		}
		catch ( Exception $error )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_approve-2', null, null, 403 );
		}
	}
	
	/**
	 * Shows the edit box
	 *
	 * @return	@e void
	 */
	protected function _showEdit()
	{
		/* INIT */
		$commentId = intval( $this->request['commentId'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_showEdit-11', null, null, 404 );
		}

		# Get Edit form
		try
		{
			$html = $this->_comments->displayEditForm( $parentId, $commentId, $this->memberData );
			
			/* Get nav */
			$parent = $this->_comments->remapFromLocal( $this->_comments->fetchParent( $parentId ), 'parent' );
		
			/* Output */
			$this->registry->getClass('output')->setTitle( $this->lang->words['edit_comment'] . ' ' . $parent['parent_title'] );
			$this->registry->getClass('output')->addContent( $html );
			
			$this->registry->getClass('output')->addNavigation( $this->lang->words['edit_comment'] . ' ' . $parent['parent_title'], sprintf( $this->_comments->fetchSetting('urls-showParent'), $parentId ), $parent['parent_seo_title'] );
	
			$this->registry->getClass('output')->sendOutput();
		}
		catch ( Exception $error )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_showEdit-0', null, null, 403 );
		}
	}
	
	/**
	 * Saves the post
	 *
	 * @return	@e void
	 */
	protected function _saveEdit()
	{
		/* INIT */
		$commentId = intval( $this->request['commentId'] );
		$parentId  = intval( $this->request['parentId'] );
		$post      = IPSText::parseCleanValue( $_POST['Post'] );
		
		/* Perm check (looks nice) */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'posting_bad_auth_key', '1-global-comments-_saveEdit-0', null, null, 403 );
		}
		
		/* Quick error checko */
		if ( ! $parentId OR ! $commentId )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_saveEdit-1', null, null, 403 );
		}

		/* Edit */
		try
		{
			$this->_comments->edit( $parentId, $commentId, $_POST['Post'], $this->memberData );
			
			if ( $this->request['modcp'] )
			{
				$app = $this->request['fromApp'];
				$app = explode( '-', $app );
				$app = $app[0];
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp='. $app .'&tab=' . $this->request['modcp'] );
			}
			else
			{
				$this->_comments->redirectToComment( $commentId, $parentId );
			}
		}
		catch ( Exception $error )
		{
			$this->registry->getClass('output')->showError( 'no_permission', '1-global-comments-_saveEdit-2', null, null, 403 );
		}
	}
	
	/**
	 * Add a comment via the magic and mystery of NORMAL POSTING FOOL
	 *
	 * @return	@e void
	 */
	protected function _add()
	{
		/* init */
		$post     = IPSText::parseCleanValue( $_POST['Post'] );
		$parentId = intval( $this->request['parentId'] );
		
		/* Perm check (looks nice) */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'posting_bad_auth_key', '1-global-comments-_add-0', null, null, 403 );
		}
		
		/* If we are a guest, verify captcha */
		if ( ! $this->memberData['member_id'] AND $this->settings['guest_captcha'] AND $this->settings['bot_antispam_type'] != 'none' )
		{
			if ( !$this->registry->getClass('class_captcha')->validate() )
			{
				$this->registry->getClass('output')->showError( 'posting_bad_captcha', '1-global-comments-_add-3', null, null, 403 );
			}
		}

		/* Save the comment */
		if ( $post AND $parentId )
		{
			try
			{
				$newComment = $this->_comments->add( $parentId, $_POST['Post'] );
				
				/* Redirect to find latest */
				$this->_comments->redirectToComment( 'last', $parentId, $newComment['comment_approved'] ? false : true );
			}
			catch( Exception $e )
			{
				$this->registry->output->showError( 'no_permission', '1-global-comments-_add-1', null, null, 403 );
			}
		}
		else
		{
			$this->registry->output->showError( 'no_permission', '1-global-comments-_add-2', null, null, 403 );
		}
	}
}