<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Image Ajax
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_comments extends ipsAjaxCommand
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
		
		/* Init some data */
		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( $fromApp );

		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'add':
				$this->_add();
			break;
			case 'delete':
				$this->_delete();
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
        }
    }
    
    /**
     * Moderate
     *
     * @return	@e void
     */
    protected function _moderate()
    {
    	$parentId   = intval( $this->request['parentId'] );
 		$commentIds = ( is_array( $_POST['commentIds'] ) ) ? IPSLib::cleanIntArray( $_POST['commentIds'] ) : array();
 		$modact	 	= trim( $this->request['modact'] );
 		 		
 		if ( count( $commentIds ) )
 		{
 			try
			{				
 				$this->_comments->moderate( $modact, $parentId, $commentIds, $this->memberData );	
 			
 				$this->returnJsonArray( array( 'msg' => 'ok' ) );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( 'Error ' . $error->getMessage() . ' line: ' . $error->getFile() . '.' . $error->getLine() );
			}
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
		$commentId = intval( $this->request['comment_id'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->returnString( 'error' );
		}
		
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		# Get Edit form
		try
		{
			$html = $this->_comments->fetchReply( $parentId, $commentId, $this->memberData );
	
			$this->editor->setContent( $html, 'topics' );
		
			$this->returnHtml( $this->editor->getContent() );
		}
		catch ( Exception $error )
		{
			$this->returnString( 'Error ' . $error->getMessage() );
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
		$commentId = intval( $this->request['comment_id'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->returnJsonError( 'error' );
		}
		
		try
		{
			$this->_comments->delete( $parentId, $commentId, $this->memberData );
			
			$this->returnJsonArray( array( 'msg' => 'ok' ) );
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( 'Error ' . $error->getMessage() );
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
		$commentId = intval( $this->request['comment_id'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Quick error checko */
		if ( ! $commentId OR ! $parentId )
		{
			$this->returnString( 'error' );
		}
		
		# Get Edit form
		try
		{
			$html = $this->_comments->displayAjaxEditForm( $parentId, $commentId, $this->memberData );
			
			$html = $this->registry->output->replaceMacros( $html );

			$this->returnHtml( $html );
		}
		catch ( Exception $error )
		{
			$this->returnString( 'Error ' . $error->getMessage() );
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
		$commentId = intval( $this->request['comment_id'] );
		$parentId  = intval( $this->request['parentId'] );
		
		/* Quick error checko */
		if ( ! $parentId OR ! $commentId )
		{
			$this->returnString( 'error' );
		}

		/* Edit */
		try
		{
			$output = $this->_comments->edit( $parentId, $commentId, $_POST['Post'], $this->memberData );
			
			$this->returnJsonArray( array( 'successString' => $this->registry->output->replaceMacros( $output ) ) );
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
		}
	}
	
	/**
	 * Add a comment via the magic and mystery of ajax
	 *
	 * @return	@e void
	 */
	protected function _add()
	{
		/* init */
		$parentId = intval( $this->request['parentId'] );
		
		if ( $_POST['Post'] AND $parentId )
		{
			try
			{
				$newComment = $this->_comments->add( $parentId, $_POST['Post'] );
				
				if( $newComment['comment_approved'] )
				{
					return $this->returnHtml( $this->_comments->fetchFormattedSingle( $parentId, $newComment['comment_id'] ) );
				}
				else
				{
					$this->returnJsonError( 'comment_requires_approval' );
				}	
			}
			catch( Exception $e )
			{
				$this->returnJsonError( $e->getMessage() );
			}
		}
		else
		{
			$this->returnJsonError( 'no_permission' );
		}
	}
}