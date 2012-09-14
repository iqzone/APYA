<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class blog_forumDataSync
{	
	/**
	* Constructor
	*
	*/
	function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang	      =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Post Delete
	 *
	 * @access	public
	 * @param	array 		Array of post IDs that have been deleted
	 * @return 	bool
	 */
	public function postDelete( $pids )
	{
		if ( count( $pids ) )
		{
			$this->DB->delete( 'blog_this', 'bt_app=\'forums\' AND bt_id2 IN (' . implode( ',', $pids ) . ')' );
		}
	}
	
	/**
	 * Topic Delete
	 *
	 * @access	public
	 * @param	array 		Array of topic IDs that have been deleted
	 * @return 	bool
	 */
	public function topicDelete( $tids )
	{
		if ( count( $tids ) )
		{
			$this->DB->delete( 'blog_this', 'bt_app=\'forums\' AND bt_id1 IN (' . implode( ',', $tids ) . ')' );
		}
	}
	
	/**
	 * Topic Split
	 *
	 * @access	public
	 * @param	array 		Array of post IDs that have been split
	 * @param	int			Existing topic ID
	 * @param	int			New topic ID
	 * @return 	bool
	 */
	public function topicSplit( $pids, $oldTid, $newTid )
	{
		if ( count( $pids ) )
		{
			$this->DB->update( 'blog_this', array( 'bt_id1' => $newTid ), 'bt_app=\'forums\' AND bt_id2 IN (' . implode( ',', $pids ) . ')' );
		}
	}
	
	/**
	 * Post Merge
	 *
	 * @access	public
	 * @param	array 		Array of post IDs that have been merged
	 * @param	int			new PID
	 * @return 	bool
	 */
	public function postMerge( $pids, $newPid )
	{
		if ( count( $pids ) )
		{
			$this->DB->update( 'blog_this', array( 'bt_id2' => $newPid ), 'bt_app=\'forums\' AND bt_id2 IN (' . implode( ',', $pids ) . ')' );
		}
	}
	
	/**
	 * Post Merge
	 *
	 * @access	public
	 * @param	array 		Old topic ID
	 * @param	int			new topic ID
	 * @return 	bool
	 */
	public function topicMerge( $oldTid, $newTid )
	{
		if ( $oldTid and $newTid )
		{
			$this->DB->update( 'blog_this', array( 'bt_id1' => $newTid ), 'bt_app=\'forums\' AND bt_id1=' . $oldTid );
		}
	}

}