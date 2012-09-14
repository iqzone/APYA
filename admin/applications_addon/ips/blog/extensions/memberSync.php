<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Blog Extensions
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		12th March 2002
 * @version		$Revision: 4 $
 *
 */

/**
 * Member Synchronization extensions
 */
class blogMemberSync
{
	/**
	 * Registry reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
		$this->DB       = $this->registry->DB();
	}
	
	/**
	 * This method is run when a member is flagged as a spammer
	 *
	 * @access	public
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onSetAsSpammer( $member )
	{
		if ( $member['member_id'] )
		{
			/* Init vars & libs */
			$blogs = array();
			$eids  = array();
			
			if ( ! $this->registry->isClassLoaded( 'blogFunctions' ) )
			{
				/* Load the Blog functions library */
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
				$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
			}
			
			/* Load & disable any blogs they own */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_blogs',
									 'where'  => 'member_id=' . intval( $member['member_id'] )
							 )		);
			$outer1 = $this->DB->execute();
			
			while( $row = $this->DB->fetch($outer1) )
			{
				$blogs[ $row['blog_id'] ] = $row;
			}
			
			$this->DB->update( 'blog_blogs', array( 'blog_disabled' => 1 ), 'member_id=' . intval( $member['member_id'] ) );
			
			/* Grab all unique entry IDs this user has posted in */
			$this->DB->build( array( 'select' => 'DISTINCT(entry_id)',
									 'from'   => 'blog_comments',
									 'where'  => 'member_id=' . intval( $member['member_id'] ) ) );
			$outer2 = $this->DB->execute();
			
			while( $row = $this->DB->fetch($outer2) )
			{
				$eids[] = $row['entry_id'];
			}
			
			/* Got anyfing? */
			if ( count( $eids ) )
			{
				/* Unapprove any comments they have made */
				$this->DB->update( 'blog_comments', array( 'comment_approved' => 0 ), 'member_id=' . intval( $member['member_id'] ) );
			
				foreach( $eids as $entry_id )
				{
					$this->registry->blogFunctions->rebuildEntry( $entry_id );
				}
			}
			
			/* Got members has_blog to re-flag? */
			if ( count($blogs) )
			{
				foreach( $blogs as $blog )
				{
					$this->registry->blogFunctions->flagMyBlogsRecacheByBlogId( $blog );
				}
			}
			
			$this->registry->blogFunctions->rebuildStats();
		}
	}
	
	/**
	 * This method is run when a member is un-flagged as a spammer
	 *
	 * @access	public
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onUnSetAsSpammer( $member )
	{
		if ( $member['member_id'] )
		{
			/* Init vars & libs */
			$blogs = array();
			$eids  = array();
			
			if ( ! $this->registry->isClassLoaded( 'blogFunctions' ) )
			{
				/* Load the Blog functions library */
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
				$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
			}
			
			/* Load & enable any blogs they own */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_blogs',
									 'where'  => 'member_id=' . intval( $member['member_id'] )
							 )		);
			$outer1 = $this->DB->execute();
			
			while( $row = $this->DB->fetch($outer1) )
			{
				$blogs[ $row['blog_id'] ] = $row;
			}
			
			$this->DB->update( 'blog_blogs', array( 'blog_disabled' => 0 ), 'member_id=' . intval( $member['member_id'] ) );
			
			/* Grab all unique entry IDs this user has posted in */
			$this->DB->build( array( 'select' => 'DISTINCT(entry_id)',
									 'from'   => 'blog_comments',
									 'where'  => 'member_id=' . intval( $member['member_id'] ) ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$eids[] = $row['entry_id'];
			}
			
			/* Got anyfing? */
			if ( count( $eids ) )
			{
				/* Unapprove any comments they have made */
				$this->DB->update( 'blog_comments', array( 'comment_approved' => 1 ), 'member_id=' . intval( $member['member_id'] ) );
			
				foreach( $eids as $entry_id )
				{
					$this->registry->blogFunctions->rebuildEntry( $entry_id );
				}
			}
			
			/* Got members has_blog to re-flag? */
			if ( count($blogs) )
			{
				foreach( $blogs as $blog )
				{
					$this->registry->blogFunctions->flagMyBlogsRecacheByBlogId( $blog );
				}
			}
			
			$this->registry->blogFunctions->rebuildStats();
		}
	}
	
	/**
	 * This method is run when a user successfully logs in
	 *
	 * @param	array		$member		Array of member data
	 * @return	@e void
	 */
	public function onLogin( $member )
	{
		/* Got blogs to recache? */
		if ( ! empty($member['blogs_recache']) || $member['has_blog'] == 'recache' )
		{
			if ( ! $this->registry->isClassLoaded( 'blogFunctions' ) )
			{
				/* Load the Blog functions library */
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
				$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
			}
			
			$this->registry->blogFunctions->rebuildMyBlogsCache( $member );
		}
	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 */
	public function onDelete( $mids )
	{
		//-----------------------------------------
		// Get blogs
		//-----------------------------------------
		
		$blogs	= array();
		
		$this->registry->DB()->build( array( 'select' => 'blog_id', 'from' => 'blog_blogs', 'where' => 'member_id' . $mids ) );
		$this->registry->DB()->execute();
		
		while( $r = $this->registry->DB()->fetch() )
		{
			$blogs[]	= $r['blog_id'];
		}
		
		$entries	= array();
		
		$this->registry->DB()->build( array( 'select' => 'entry_id', 'from' => 'blog_entries', 'where' => 'entry_author_id' . $mids . ( count($blogs) ? ' OR blog_id IN(' . implode( ',', $blogs ) . ')' : '' ) ) );
		$this->registry->DB()->execute();
		
		while( $r = $this->registry->DB()->fetch() )
		{
			$entries[ $r['entry_id'] ]	= $r['entry_id'];
		}

		//-----------------------------------------
		// Delete stuff
		//-----------------------------------------
		
		$this->registry->DB()->delete( 'blog_blogs', 'member_id' . $mids );
		$this->registry->DB()->delete( 'blog_ratings', 'member_id' . $mids );
		$this->registry->DB()->delete( 'blog_voters', 'member_id' . $mids );
		$this->registry->DB()->delete( 'blog_moderators', "moderate_type='member' AND moderate_mg_id" . $mids );

		if( count($blogs) )
		{
			$this->registry->DB()->delete( 'blog_ratings', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			$this->registry->DB()->delete( 'blog_rsscache', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			$this->registry->DB()->delete( 'blog_trackback', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			$this->registry->DB()->delete( 'blog_trackback_spamlogs', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			$this->registry->DB()->delete( 'blog_updatepings', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			$this->registry->DB()->delete( 'blog_views', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			$this->registry->DB()->delete( 'blog_lastinfo', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			
			//-----------------------------------------
			// Take care of cblocks
			//-----------------------------------------
			
			$cblocks	= array();
			
			$this->registry->DB()->build( array( 'select' => 'cblock_ref_id', 'from' => 'blog_cblocks', 'where' => "cblock_type='custom' AND blog_id IN(" . implode( ',', $blogs ) . ')' ) );
			$this->registry->DB()->execute();
			
			while( $r = $this->registry->DB()->fetch() )
			{
				$cblocks[]	= $r['cblock_ref_id'];
			}
			
			if( count( $cblocks ) )
			{
				$this->registry->DB()->delete( 'blog_custom_cblocks', 'cbcus_id IN(' . implode( ',', $cblocks ) . ')' );
				$this->registry->DB()->delete( 'blog_cblocks', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
				$this->registry->DB()->delete( 'blog_cblock_cache', 'blog_id IN(' . implode( ',', $blogs ) . ')' );
			}
		}
		
		if( count($entries) )
		{
			$this->registry->DB()->delete( 'blog_entries', 'entry_id IN(' . implode( ',', $entries ) . ')' );
			$this->registry->DB()->delete( 'blog_voters', 'entry_id IN(' . implode( ',', $entries ) . ')' );
			$this->registry->DB()->delete( 'blog_comments', 'entry_id IN(' . implode( ',', $entries ) . ')' );
			$this->registry->DB()->delete( 'blog_polls', 'entry_id IN(' . implode( ',', $entries ) . ')' );
		}

		$this->registry->DB()->delete( 'blog_cblocks', 'member_id' . $mids );
		
		//-----------------------------------------
		// Update stuff
		//-----------------------------------------
		
		$this->registry->DB()->update( 'blog_comments', array( 'member_id' => 0 ), 'member_id' . $mids );
		$this->registry->DB()->update( 'blog_lastinfo', array( 'blog_last_comment_mid' => 0 ), 'blog_last_comment_mid' . $mids );
		$this->registry->DB()->update( 'blog_entries', array( 'entry_last_comment_mid' => 0 ), 'entry_last_comment_mid' . $mids );
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 */
	public function onMerge( $member, $member2 )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->registry->DB()->update( 'blog_blogs', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_cblocks', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_comments', array( 'member_id' => $member['member_id'], 'member_name' => $member2['members_display_name'] ), 'member_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_entries', array( 'entry_author_id' => $member['member_id'], 'entry_author_name' => $member2['members_display_name'] ), 'entry_author_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_entries', array( 'entry_last_comment_mid' => $member['member_id'], 'entry_last_comment_name' => $member2['members_display_name'] ), 'entry_last_comment_mid=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_moderators', array( 'moderate_mg_id' => $member['member_id'] ), "moderate_type='member' AND moderate_mg_id=" . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_polls', array( 'starter_id' => $member['member_id'] ), 'starter_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_ratings', array( 'member_id' => $member['member_id'] ), 'member_id=' . $member2['member_id'] );
		$this->registry->DB()->update( 'blog_lastinfo', array( 'blog_last_comment_mid' => $member['member_id'], 'blog_last_comment_name' => $member2['members_display_name'] ), 'blog_last_comment_mid=' . $member2['member_id'] );		
		
		//-----------------------------------------
		// Votes should only have one
		//-----------------------------------------
		
		$votes	= array();
		
		$this->registry->DB()->build( array( 'select' => '*', 'from' => 'blog_voters', 'where' => 'member_id=' . $member2['member_id'] ) );
		$this->registry->DB()->execute();
		
		while( $r = $this->registry->DB()->fetch() )
		{
			$votes[]	= $r;
		}
		
		if( count($votes) )
		{
			foreach( $votes as $vote )
			{
				$check	= $this->registry->DB()->buildAndFetch( array( 'select' => 'vote_id', 'from' => 'blog_voters', 'where' => 'member_id=' . $member['member_id'] . ' AND entry_id=' . $vote['entry_id'] ) );
				
				if( !$check['vote_id'] )
				{
					$this->registry->DB()->update( 'blog_voters', array( 'member_id' => $member['member_id'] ), 'vote_id=' . $vote['vote_id'] );
				}
			}
		}
	}

	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	@e void
	 */
	public function onNameChange( $id, $new_name )
	{
		//-----------------------------------------
		// Fix cached names
		//-----------------------------------------
		
		$this->registry->DB()->update( 'blog_comments', array( 'member_name' => $new_name ), 'member_id=' . $id );
		$this->registry->DB()->update( 'blog_entries', array( 'entry_author_name' => $new_name ), 'entry_author_id=' . $id );
		$this->registry->DB()->update( 'blog_entries', array( 'entry_last_comment_name' => $new_name ), 'entry_last_comment_mid=' . $id );
		$this->registry->DB()->update( 'blog_lastinfo', array( 'blog_last_comment_name' => $new_name ), 'blog_last_comment_mid=' . $id );
	}
}