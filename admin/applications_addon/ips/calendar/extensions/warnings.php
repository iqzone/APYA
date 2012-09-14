<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Warnings Extension
 * Last Updated: $Date: 2011-12-21 12:46:45 -0500 (Wed, 21 Dec 2011) $
 * </pre>
 *
 * @author 		$author $ (Original: Mark)
 * @copyright	© 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10054 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class warnings_calendar
{
	/**
	 * Get Content URL
	 *
	 * @param	array	Row from members_warn_logs
	 * @return	array	array( url => URL to the content the warning came from, title => Title )
	 */
	public function getContentUrl( $warning )
	{	
		$exploded = explode( '-', $warning['wl_content_id2'] );
	
		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );
		$comments = classes_comments_bootstrap::controller( $warning['wl_content_id1'] );
		
		$parent = $comments->remapFromLocal( $comments->fetchParent( $exploded[0] ), 'parent' );
		
		if ( empty($parent['parent_id']) )
		{
			return NULL;
		}
		else
		{
			return array( 'url' => ipsRegistry::getClass('output')->buildUrl( "app=core&module=global&section=comments&fromApp={$warning['wl_content_id1']}&do=findComment&parentId={$exploded[0]}&comment_id={$exploded[1]}" ), 'title' => $parent['parent_title'] );
		}
	}
}