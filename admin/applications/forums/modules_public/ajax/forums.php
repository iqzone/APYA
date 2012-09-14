<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Fetch topics and other data from the forum
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_forums extends ipsAjaxCommand 
{
	/**
	* Main class entry point
	*
	* @param	object		ipsRegistry reference
	* @return	@e void		[Outputs to screen]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		switch( $this->request['do'] )
		{
			default:
			case 'getTopics':
				$this->_getTopics();
			break;
			case 'markRead':
				$this->_markRead();
			break;
		}
	}
	
	/**
	 * Mark topic as read
	 */
	protected function _markRead()
	{
		$fid = intval( $this->request['fid'] );
		
		$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $fid ) );
		
		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
	
	/**
	 * Get topics from a forum
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _getTopics()
	{
		//-----------------------------------------
		// Reset input
		//-----------------------------------------
		
		$_GET['showforum']					= intval($_GET['f']);
		ipsRegistry::$request['showforum']	= intval(ipsRegistry::$request['f']);
		
		if ( $_GET['showforum'] < 1 )
		{
			$this->returnJsonArray( array( 'error' => 'incorrect_f' ) );
		}
		
		//-----------------------------------------
		// Get the forum controller
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/forums.php', 'public_forums_forums_forums' );
		$forums	= new $classToLoad();
		$forums->makeRegistryShortcuts( $this->registry );
		$forums->initForums();
		$forums->buildPermissions();
		
		$data	= $forums->renderForum();
		$html	= '';

		if( is_array( $data['topic_data'] ) && count( $data['topic_data'] ) )
		{
			foreach( $data['topic_data'] as $idx => $tdata )
			{
				$html .= $this->registry->output->getTemplate('forum')->topic( $tdata, $data['other_data']['forum_data'], $data['other_data'], 1 );
			}
		}

		$html  = $this->cleanOutput( $this->parseAndCleanHooks( $html ) );
		$pages = $this->parseAndCleanHooks( $data['other_data']['forum_data']['SHOW_PAGES'] );
		
		$this->returnJsonArray( array( 'topics' => $html, 'pages' => $pages, 'hasMore' => $data['other_data']['hasMore'] ) );
	}
}