<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Moderator Stuff
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 4 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_post_mod extends ipsCommand
{
	public $nav = array();
	public $blog_id = 0;
	public $blog = array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Check the MD5 Hash */
		if( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'incorrect_use', 2068, null, null, 403 );
		}
		
		/* Exempt from auto-check */
		$exempt = array( 'toggledisable' );
		
		/* Not exempt? */
		if ( ! in_array( $this->request['do'], $exempt ) )
		{
			/* Check ID */
			$this->blog_id = intval( $this->request['blogid'] );
			$this->blog    = $this->registry->getClass('blogFunctions')->getActiveBlog();
			
			/* Build selected array */
			foreach( $this->request as $k => $v )
			{
				if( preg_match( "/bmod_(.+?)$/", $k, $match ) )
				{
					if( $v && intval( $match[1] ) )
					{
						$this->request['selectedbids'][] = intval( $match[1] );
					}
				}
			}
		
			if( $this->request['selectedbids'] )
			{
				$this->request['selectedbids'] = implode( ',', $this->request['selectedbids'] );
			}
			
			if( ! $this->blog_id and ! $this->request['selectedbids'] )
			{
				$this->registry->output->showError( 'blog_mmod_noids', 10697, null, null, 404 );
			}
		}
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'dopublish':
				$this->doPublish();
				break;
			case 'dodraft':
				$this->doDraft();
				break;
			case 'dolock':
				$this->doLock();
				break;
			case 'dounlock':
				$this->doUnLock();
				break;
			case 'dodelentry':
				$this->deleteEntry();
				break;
			case 'dodelcomment':
				$this->deleteComment();
				break;
			case 'doapprove':
				$this->approveComment();
				break;
			case 'dommod':
				$this->doMMod();
				break;
			case 'delcblock':
				$this->delCBlock();
				break;
			case 'dodeltrackback':
				$this->deleteTrackBack();
				break;
			case 'doapprovetb':
				$this->approveTrackBack();
				break;
			case 'toggledisable':
				$this->doToggleDisable();
				break;
			default:
				$this->registry->output->showError( 'incorrect_use', 10698 );
				break;
		}
	}

	/**
	 * Publish a Blog Entry
	 *
	 * @return	@e void
	 */
	public function doPublish()
	{
		/* Check Permissions */
		if( ! $this->registry->blogFunctions->allowPublish( $this->blog ) )
		{
			$this->registry->output->showError( 'no_blog_mod_permission', 10699, null, null, 403 );
		}

   		/* Check entry ID */
   		$entry_id = intval( $this->request['entryid'] );

   		if( ! $entry_id )
   		{
   			$this->registry->output->showError( 'incorrect_use', 106100, null, null, 404 );
   		}

		$entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$entry_id}" ) );

		/* Check access */
		if( empty( $entry ) or $entry['entry_name'] == "" )
		{
   			$this->registry->output->showError( 'incorrect_use', 106101, null, null, 404 );
		}

		/* Check published status */
		if( $entry['entry_status'] != 'draft' )
		{
   			$this->registry->output->showError( 'incorrect_use', 106102 );
		}

		/* Publish */
		$this->registry->blogFunctions->changeEntryApproval( array( $entry_id ), 1 );
		
		/* Redirect back to Blog page */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&blogid={$this->blog_id}", $this->blog['blog_seo_name'], false, 'showblog' );
	}

	/**
	 * Make a Blog Entry draft
	 *
	 * @return	@e void
	 */
	public function doDraft()
	{
		/* Check Permissions */
		if( ! $this->registry->blogFunctions->allowDraft( $this->blog ) )
		{
			$this->registry->output->showError( 'no_blog_mod_permission', 10699, null, null, 403 );
		}
		
   		/* Did we enter a entry_id? */
   		$entry_id = intval( $this->request['entryid'] );

   		if (! $entry_id )
   		{
   			$this->registry->output->showError( 'incorrect_use', 106104, null, null, 404 );
   		}

   		$entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$entry_id}" )	);

		/* Check if we got access (and not just trying some id's :P) */
		if( empty( $entry ) or $entry['entry_name'] == "" )
		{
   			$this->registry->output->showError( 'incorrect_use', 106105, null, null, 404 );
		}

		/* So is it really published? */
		if( $entry['entry_status'] != 'published' )
		{
   			$this->registry->output->showError( 'incorrect_use', 106106 );
		}

		/* Publish */
		$this->registry->blogFunctions->changeEntryApproval( array( $entry_id ), 0 );
		
		if( $this->settings['blog_cblock_cache'] ) 
		{
			$this->DB->update( 'blog_cblock_cache', array( 'cbcache_refresh' => 1 ), "blog_id={$this->blog_id} AND cbcache_key in('minicalendar','lastentries','lastcomments')", true );
		}
		
		//* Redirect back to blog */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&blogid={$this->blog_id}", $this->blog['blog_seo_name'], false, 'showblog' );

	}

	/**
	 * Lock a Blog Entry
	 *
	 * @return	@e void
	 */
	public function doLock()
	{
		/* Check permissions */
		if( ! $this->registry->blogFunctions->allowLocking( $this->blog ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106107, null, null, 403 );
		}

   		/* Did we enter a entry_id? */
   		$entry_id = intval( $this->request['entryid'] );

   		if( ! $entry_id )
   		{
   			$this->registry->output->showError( 'incorrect_use', 106108, null, null, 404 );
   		}

   		$entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$entry_id}" ) );

		/* Check if we got access (and not just trying some id's :P) */
		if( empty( $entry ) or $entry['entry_name'] == "" )
		{
   			$this->registry->output->showError( 'incorrect_use', 106109, null, null, 404 );
		}

		/* So is allready locked? */
		if( $entry['entry_locked'] )
		{
   			$this->registry->output->showError( 'incorrect_use', 106110 );
		}

		/* We are good to update */
		$this->DB->update( 'blog_entries', array( 'entry_locked' => 1 ), "entry_id={$entry_id}");
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_locked'], $this->blog['blog_id'], $this->blog['blog_name'], $entry['entry_name'] ) );

		/* Redirect back to Blog page */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&blogid={$this->blog_id}", $this->blog['blog_seo_name'], false, 'showblog' );
	}

	/**
	 * Unlock a Blog Entry
	 *
	 * @return	@e void
	 */
	public function doUnLock()
	{
		/* Check permissions */
		if( ! $this->registry->blogFunctions->allowLocking( $this->blog ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106111, null, null, 403 );
		}

   		/* Did we enter a entry_id? */
   		$entry_id = intval( $this->request['entryid'] );

   		if( ! $entry_id )
   		{
   			$this->registry->output->showError( 'incorrect_use', 106112, null, null, 404 );
   		}

   		$entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$entry_id}" ) );

		/* Check if we got access (and not just trying some id's :P) */
		if( empty( $entry ) or $entry['entry_name'] == "" )
		{
   			$this->registry->output->showError( 'incorrect_use', 106113, null, null, 404 );
		}

		/* So is really locked? */
		if( ! $entry['entry_locked'] )
		{
   			$this->registry->output->showError( 'incorrect_use', 106114 );
		}

		/* We are good to update */
		$this->DB->update( 'blog_entries', array( 'entry_locked' => 0 ), "entry_id={$entry_id}" );
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_unlocked'], $this->blog['blog_id'], $this->blog['blog_name'], $entry['entry_name'] ) );

		/* Redirect back to Blog page */
		$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&blogid={$this->blog_id}", $this->blog['blog_seo_name'], false, 'showblog' );
	}

	/**
	 * Delete an entry
	 *
	 * @return	@e void
	 */
	public function deleteEntry()
	{
		if( ! $this->registry->blogFunctions->allowDelEntry( $this->blog ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106116, null, null, 403 );
		}

		/* Do we have a entry_id? */
		$eid = intval( $this->request['entryid'] );
		
		if( ! $eid )
        {
			$this->registry->output->showError( 'incorrect_use', 106117, null, null, 404 );
		}
		
   		$entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$eid}" ) );
		
		/* Check if we got access (and not just trying some id's :P) */
		if( empty( $entry ) or $entry['entry_name'] == "" )
		{
   			$this->registry->output->showError( 'incorrect_use', 106118, null, null, 404 );
		}
				
		/* Delete entries */
		$this->registry->blogFunctions->deleteEntries( array( $eid ) );
				
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_delete'], $this->blog['blog_id'], $this->blog['blog_name'], $entry['entry_name'] ) );
		
		/* Bounce back to blog */
		$this->registry->output->redirectScreen( $this->lang->words['entry_deleted'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&blogid={$this->blog_id}&st={$this->request['st']}", $this->blog['blog_seo_name'], 'showblog' );
	}

	/**
	 * Delete a comment
	 *
	 * @return	@e void
	 */
	public function deleteComment()
	{
		/* Check entry id */
		$eid = intval( $this->request['entryid'] );
		$cid = intval( $this->request['cid'] );
		
		if ( ! $eid )
        {
			$this->registry->output->showError( 'incorrect_use', 106119, null, null, 404 );
		}
			
		if ( ! $cid )
        {
			$this->registry->output->showError( 'incorrect_use', 106120, null, null, 404 );
		}
		
		/* Grab comment */
		$comment = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_comments', 'where' => "comment_id={$cid} and entry_id={$eid}" ) );
		
		/* Do delete */
		$this->registry->blogFunctions->deleteComments( array( $cid ) );
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_del_comment'], $this->blog['blog_id'], $this->blog['blog_name'], $comment['comment_id'], $comment['member_id'] ) );
		
		/* Redirect back to the entry */
		$this->registry->output->redirectScreen( $this->lang->words['comment_deleted'], $this->settings['base_url'] . "app=blog&blogid={$this->blog_id}&showentry={$eid}");
	}

	/**
	 * Approve a comment
	 *
	 * @return	@e void
	 */
	public function approveComment()
	{
		/* Do we have a entry_id? */
		$eid = intval( $this->request['entryid'] );
		
		if( ! $eid )
        {
			$this->registry->output->showError( 'incorrect_use', 106122, null, null, 404 );
		}

		/* Do we have a comment_id? */
		$cid = intval( $this->request['cid'] );
		
		if( ! $cid )
        {
			$this->registry->output->showError( 'incorrect_use', 106123, null, null, 404 );
		}

		$comment = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_comments', 'where' => "comment_id=$cid and entry_id=$eid" ) );
		
		/* Check permissions */
		if( ! $this->registry->blogFunctions->allowApprove( $this->blog ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106124, null, null, 403 );
		}
		
		/* Update */
		$this->registry->getClass('blogFunctions')->changeCommentApproval( array( $cid ), 1 );
		
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_app_comment'], $this->blog['blog_id'], $this->blog['blog_name'], $comment['comment_id'], $comment['member_id'] ) );
		
		/* Redirect */
		$this->registry->output->redirectScreen( $this->lang->words['comment_approved'], $this->settings['base_url'] . "app=blog&blogid={$this->blog_id}&showentry={$eid}&show=comment&cid={$cid}");
	}

	/**
	 * Delete a trackback
	 *
	 * @return	@e void
	 */
	public function deleteTrackBack()
	{
		/* Do we have a entry_id? */
		$eid = intval( $this->request['eid'] );
		
		if( ! $eid )
        {
			$this->registry->output->showError( 'incorrect_use', 106125, null, null, 404 );
		}

		/* Do we have a trackback_id? */
		$tbid = intval( $this->request['tbid'] );
		if( ! $tbid )
        {
			$this->registry->output->showError( 'incorrect_use', 106126, null, null, 404 );
		}

		$trackback = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_trackback', 'where' => "trackback_id={$tbid} and entry_id={$eid}" ) );
		
		if( ! $trackback['trackback_id'] )
		{
			$this->registry->output->showError( 'incorrect_use', 106127, null, null, 404 );
		}
		
		/* Check permissions */
		if( ! $this->registry->blogFunctions->allowDelTrackback( $this->blog ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106128, null, null, 403 );
		}
		
		/* Delete the track back */
		$this->DB->delete( 'blog_trackback', "trackback_id={$tbid} and entry_id={$eid}" );
		
		/* Rebuild the entry */
		$this->registry->blogFunctions->rebuildEntry( $eid );
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_del_tb'], $this->blog['blog_id'], $this->blog['blog_name'], $trackback['trackback_id'], $eid ) );

		/* Redirect */
		$this->registry->output->redirectScreen( $this->lang->words['trackback_deleted'], $this->settings['base_url'] . "app=blog&blogid={$this->blog_id}&showentry={$eid}&st={$this->request['st']}");
	}

	/**
	 * Approve a trackback
	 *
	 * @return	@e void
	 */
	public function approveTrackBack()
	{
		/* Do we have a entry_id? */
		$eid = intval( $this->request['eid'] );
		
		if( ! $eid )
        {
			$this->registry->output->showError( 'incorrect_use', 106129, null, null, 404 );
		}

		/* Do we have a trackback_id? */
		$tbid = intval( $this->request['tbid'] );
		
		if( ! $tbid )
        {
			$this->registry->output->showError( 'incorrect_use', 106130, null, null, 404 );
		}

		$trackback = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_trackback', 'where' => "trackback_id={$tbid} and entry_id={$eid}" ) );
		
		if( ! $trackback['trackback_id'] )
		{
			$this->registry->output->showError( 'incorrect_use', 106131, null, null, 404 );
		}
		
		/* Check permissions */
		if( ! $this->registry->blogFunctions->allowDelTrackback( $this->blog ) )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106132, null, null, 403 );
		}

		if( ! $trackback['trackback_queued'] )
		{
			$this->registry->output->showError( 'incorrect_use', 106133 );
		}
		
		/* Update the track back */
		$this->DB->update( 'blog_trackback', array('trackback_queued' => 0), "trackback_id={$tbid} and entry_id={$eid}" );
		
		/* Rebuild Entry */
		$this->registry->blogFunctions->rebuildEntry( $eid );
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_app_tb'], $this->blog['blog_id'], $this->blog['blog_name'], $trackback['trackback_id'], $eid ) );
		
		/* Redirect */
		$this->registry->output->redirectScreen( $this->lang->words['trackback_approved'], $this->settings['base_url'] . "app=blog&blogid={$this->blog_id}&showentry={$eid}&st={$this->request['st']}");
	}
	
	/**
	 * Toggle a blog's disabled status
	 *
	 * @return	@e void
	 */
	public function doToggleDisable()
	{
		/* INIT */
		$blogid  = intval( $this->request['blog_id'] );
		$disable = intval( $this->request['disable'] );
		
		/* Do we have permission */
		if( ! ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_disable'] ) )
		{
			$this->registry->output->showError( 'cp_no_perms', 106134, null, null, 403 );
		}
		
 		/* Found some ids? */
 		if( ! $blogid )
 		{
			$this->registry->output->showError( 'blog_mmod_noids', 106135, null, null, 404 );
 		}
 		
 		/* Load blog */
 		$blog = $this->registry->blogFunctions->loadBlog( $blogid );
 		
 		/* What to do */
 		if ( $disable )
 		{
			$this->DB->update( 'blog_blogs', array( 'blog_disabled' => 1 ), "blog_id=" . $blogid );
					
			/* Take out featured articles also */
			$this->DB->update( 'blog_entries', array( 'entry_featured' => 0 ), "blog_id=" . $blogid );
		}
		else
		{
			$this->DB->update( 'blog_blogs', array( 'blog_disabled' => 0 ), "blog_id=" . $blogid );
		}
					
		/* Reset all stats */
		$this->registry->blogFunctions->flagMyBlogsRecacheByBlogId( $blog );
		$this->registry->blogFunctions->rebuildStats();
		
		/* return to Blog list */
		$this->registry->output->redirectScreen( $this->lang->words['blog_mmod_applied'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&amp;blogid=".$blog['blog_id'], $blog['blog_seo_name'], 'showblog' );
	}
	
	/**
	 * Blog multi-moderation
	 *
	 * @return	@e void
	 */
	public function doMMod()
	{
		/* Do we have permission */
		if( ! ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_disable'] or $this->memberData['_blogmod']['moderate_can_pin'] ) )
		{
			$this->registry->output->showError( 'cp_no_perms', 106134, null, null, 403 );
		}
		
		/* IDs */
		$ids = array();
 		$ids = explode( ',', $this->request['selectedbids'] );
		
 		/* Found some ids? */
 		if( count( $ids ) < 1 )
 		{
			$this->registry->output->showError( 'blog_mmod_noids', 106135 );
 		}
 		
		$ids = IPSLib::cleanIntArray( $ids );
 		$ids = implode( ',', $ids );
		
 		/* What todo */
		switch( $this->request['blogact'] )
		{
			case 'pin':
					if( ! $this->memberData['g_is_supmod'] and ! $this->memberData['_blogmod']['moderate_can_pin'] )
					{
						$this->registry->output->showError( 'cp_no_perms', 106136, null, null, 403 );
					}
					$this->DB->update( 'blog_blogs', array( 'blog_pinned' => 1 ), "blog_id IN ({$ids})" );
					$this->registry->blogFunctions->rebuildStats();
			break;
			case 'unpin':
					if( ! $this->memberData['g_is_supmod'] and ! $this->memberData['_blogmod']['moderate_can_pin'] )
					{
						$this->registry->output->showError( 'cp_no_perms', 106137, null, null, 403 );
					}
					$this->DB->update( 'blog_blogs', array( 'blog_pinned' => 0 ), "blog_id IN ({$ids})" );
					$this->registry->blogFunctions->rebuildStats();
					break;
			case 'disable':
					if( ! $this->memberData['g_is_supmod'] and ! $this->memberData['_blogmod']['moderate_can_disable'] )
					{
						$this->registry->output->showError( 'cp_no_perms', 106138, null, null, 403 );
					}
					
					/* Unpin */
					$this->DB->update( 'blog_blogs', array( 'blog_disabled' => 1 ), "blog_id IN ({$ids})" );
					
					/* Take out featured articles also */
					$this->DB->update( 'blog_entries', array( 'entry_featured' => 0 ), "blog_id IN ({$ids})" );
					
					/* Reset all stats */
					foreach( explode( ',', $ids ) as $bid )
					{
						$this->registry->blogFunctions->flagMyBlogsRecacheByBlogId( $bid );
					}
					
					$this->registry->blogFunctions->rebuildStats();
					break;
			case 'enable':
					if( ! $this->memberData['g_is_supmod'] and !$this->memberData['_blogmod']['moderate_can_disable'] )
					{
						$this->registry->output->showError( 'cp_no_perms', 106139, null, null, 403 );
					}
					$this->DB->update( 'blog_blogs', array( 'blog_disabled' => 0 ), "blog_id IN ({$ids})" );
					
					/* Reset all stats */
					foreach( explode( ',', $ids ) as $bid )
					{
						$this->registry->blogFunctions->flagMyBlogsRecacheByBlogId( $bid );
					}
					
					$this->registry->blogFunctions->rebuildStats();
					break;
			default:
					$this->registry->output->showError( 'incorrect_use', 106140 );
					break;
		}

		/* return to Blog list */
		$type = !empty( $this->request['type'] ) ? "&amp;type={$this->request['type']}" : '';
		$this->registry->output->redirectScreen( $this->lang->words['blog_mmod_applied'], $this->settings['base_url'] . "app=blog{$type}", 'false', 'app=blog' );
	}

	/**
	 * Delete a Custom Content Block
	 *
	 * @return	@e void
	 */
	public function delCBlock()
	{
		/* Check ID */
		$cbid = intval( $this->request['cbid'] );
		
		if ( !$cbid || $this->request['cbid'] != $cbid )
		{
   			$this->registry->output->showError( 'incorrect_use', 106142, null, null, 404 );
		}

		/* Get the content block details */
		$cblock	= $this->DB->buildAndFetch( array( 
									'select'   => "bc.*",
									'from'     => array('blog_cblocks' => 'bc'),
									'add_join' => array(
														array( 
																'select' => 'bdc.*',
																'from'   => array( 'blog_default_cblocks' => 'bdc' ),
																'where'  => "bc.cblock_ref_id=bdc.cbdef_id and bc.cblock_type='default' and bdc.cbdef_locked=0",
																'type'   => 'left'
															),
														array( 
																'select' => 'bcc.*',
																'from'   => array( 'blog_custom_cblocks' => 'bcc' ),
																'where'  => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
																'type'   => 'left'
															)
														),
									'where'	   => "bc.cblock_id={$cbid} AND (bdc.cbdef_id IS NOT NULL OR bcc.cbcus_id IS NOT NULL)"
						     )	);

		if( ! $cblock['cblock_type'] == 'custom' )
		{
   			$this->registry->output->showError( 'incorrect_use', 106143 );
		}

		/* Check permissions */
		if( ! $this->memberData['g_is_supmod'] and ! $this->memberData['_blogmod']['moderate_can_editcblocks'] )
		{
			if( $cblock['member_id'] != $this->memberData['member_id'] )
			{
            	$this->registry->output->showError( 'no_blog_mod_permission', 106141, null, null, 403 );
        	}
		}

		//-----------------------------------------
		// We are good to go, just delete it starting with attachments
		//-----------------------------------------
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'blogcblock';
		$class_attach->init();

		$class_attach->bulkRemoveAttachment( array( $cblock['cbcus_id'] ) );
		$this->DB->delete( 'blog_custom_cblocks', "cbcus_id={$cblock['cbcus_id']}" );
		$this->DB->delete( 'blog_cblocks'       , "cblock_id={$cblock['cblock_id']}" );
		
		/* Update cache */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	= new $classToLoad( $this->registry );
		$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );
		
		/* Moderator Log */
		$this->addModLog( sprintf( $this->lang->words['modlog_del_cb'], $this->blog['blog_id'], $this->blog['blog_name'], $cblock['cbcus_name'] ) );
		
		/* Redirect */
		$blog_url = str_replace( "&amp;", "&", $this->registry->blogFunctions->getBlogUrl( $this->blog['blog_id'], $this->blog['blog_seo_name'] ) );
		$this->registry->output->silentRedirect( $blog_url );
	}

	/**
	 * Add an entry to the Mod log
	 *
	 * @param  string  $mod_title
	 * @return	@e void
	 */
	public function addModLog( $mod_title )
	{
		$this->DB->insert( 'moderator_logs', array(
													'member_id'   => $this->memberData['member_id'],
													'member_name' => $this->memberData['members_display_name'],
													'ip_address'  => $this->member->ip_address,
													'http_referer'=> htmlspecialchars( my_getenv( 'HTTP_REFERER' ) ),
													'ctime'       => time(),
													'action'      => $mod_title,
													'query_string'=> htmlspecialchars( my_getenv('QUERY_STRING') ),
												), true );
	}
}