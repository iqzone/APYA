<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Bounces a user to the right post
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 * @since		14th April 2004
 *
 * |   > Interesting Fact: I've had iTunes playing every Radiohead tune
 * |   > I own for about a week now. Thats a lot of repeats. Got some
 * |   > cool rare tracks though. Every album+rare+b sides = 6.7 hours
 * |   > music. Not bad. I need to get our more. No, you can't take the
 * |   > laptop with you - nerd.
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class  public_forums_forums_findpost extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[redirects]
	 */
	public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
		// Find a post
		// Don't really need to check perms 'cos topic
		// will do that for us. Woohoop
		//-----------------------------------------
		
		$pid = intval($this->request['pid']);
		
		if ( ! $pid )
		{
			$this->registry->getClass('output')->showError( 'findpost_missing_pid', 10331, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get topic...
		//-----------------------------------------
		
		$post = $this->DB->buildAndFetch( array( 'select'	=> 'p.*', 
												 'from'		=> array( 'posts' => 'p' ), 
												 'where'	=> 'p.pid=' . $pid,
												 'add_join'	=> array( array( 'select'	=> 't.*',
												 							 'from'		=> array( 'topics' => 't' ),
												 							 'where'	=> 't.tid=p.topic_id',
												 							 'type'		=> 'left' ) ) )	);
		
		if ( ! $post['topic_id'] )
		{
			$this->registry->getClass('output')->showError( 'findpost_missing_topic', 10332, null, null, 404 );
		}
		
		/* Check permission */
		if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $post['forum_id'], 0, 'topic', $post, TRUE ) )
		{
			$this->registry->getClass('output')->showError( 'findpost_missing_topic', 10332.1, null, null, 404 );
		}

		$sort_field	= ($this->settings['post_order_column'] == 'pid') ? 'pid' : 'post_date';
		$sort_value	= $sort_field == 'pid' ? $pid : $post['post_date'];
		
		$query  = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery('visible');
		
		/* Can we deal with hidden posts? */
		if ( $this->registry->class_forums->canQueuePosts( $post['forum_id'] ) )
		{
			if ( $this->permissions['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'approved', 'sdeleted', 'hidden' ) );
			}
			else
			{
				/* Otherwise, see queued and approved */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ) );
			}
		}
		else
		{
			/* We cannot see hidden posts */
			if ( $this->permissions['softDeleteSee'] )
			{
				/* See queued and soft deleted */
				$query = ' AND ' . $this->registry->class_forums->fetchPostHiddenQuery( array('approved', 'sdeleted') );
			}
		}
			
		$cposts = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$post['topic_id']} AND {$sort_field} <=" . intval( $sort_value ) . $query ) );							
		
		if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
		{
			$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
		}
		else
		{
			$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
			$pages = ceil( $number);
		}
		
		$st = ($pages - 1) * $this->settings['display_max_posts'];
		$hl = $this->request['hl'] ? '&hl=' . trim( $this->request['hl'] ) : '';
		
		$url = $this->registry->output->buildSEOUrl( "showtopic=" . $post['topic_id'] . "&st={$st}&p={$pid}" . $hl . "&#entry" . $pid, 'public', $post['title_seo'], 'showtopic' );
		
		$this->registry->getClass('output')->silentRedirect( $url );
 	}
}