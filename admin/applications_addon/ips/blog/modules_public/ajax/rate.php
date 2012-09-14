<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog AJAX rating
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_ajax_rate extends ipsAjaxCommand
{
	/**
	* Current blog
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog 				= array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// No guests
		//-----------------------------------------

		if ( !$this->memberData['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['no_guests'] );
		}
		
		//-----------------------------------------
		// Get teh blog
		//-----------------------------------------
		
		if ( ! $registry->isClassLoaded('blogFunctions') )
	 	{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$registry->setClass('blogFunctions', new $classToLoad($registry));
		}
				
		$blog_id	   = intval( $this->request['blogid'] );		
		$entry_id	   = intval( $this->request['entry_id'] );		
		$this->blog    = $registry->getClass('blogFunctions')->getActiveBlog();
		$this->blog_id = intval( $this->blog['blog_id'] );
				
		/* Fetch entry */
		$entry = $this->DB->buildAndFetch( array( 'select'	    => 'e.*',
												  'from'		=> array( 'blog_entries' => 'e' ),
												  'where'		=> "e.blog_id={$this->blog['blog_id']} and e.entry_id={$entry_id}",
												  'add_join'	=> array( array( 'select' => 'r.rating_id, r.rating as current_rating',
																				 'from'	  => array( 'blog_ratings' => 'r' ),
																				 'where'  => "e.blog_id=r.blog_id AND e.entry_id=r.entry_id AND r.member_id=" . $this->memberData['member_id'],
																				 'type'	  => 'left' ),
												  						  array( 'select' => 'm.members_display_name as entry_author_name, m.member_group_id, m.mgroup_others, m.members_seo_name', 
																				 'from'   => array( 'members' => 'm' ),
																				 'where'  => 'm.member_id=e.entry_author_id',
																				 'tye'    => 'left' ) ) ) );
		
		//-----------------------------------------
		// Are we authorized?
		//-----------------------------------------

		if ( ! $entry['entry_id'] OR ( $entry['rating_id'] != "" && ! $this->settings['blog_allow_multirate'] ) )
		{
			$this->returnString( 'no_permission' );
		}

		//-----------------------------------------
		// Get the new rating
		//-----------------------------------------
		
		$rating = intval( $this->request['rating'] );

		if ( !$rating or $rating<1 or $rating>5 or $this->memberData['member_id'] == $entry['entry_author_id'])
		{
			$this->returnString( 'no_permission' );
		}

		$this->DB->delete( 'blog_ratings', "member_id=" . $this->memberData['member_id'] . " and blog_id=" . $this->blog['blog_id'] . " AND entry_id=" . $entry_id );

		$insert_rate = array( 
								'member_id'		=> $this->memberData['member_id'],
								'blog_id'		=> $this->blog['blog_id'],
								'entry_id'		=> $entry_id,
								'rating_date'	=> time(),
								'rating'		=> $rating
							);
							
		$this->DB->insert( 'blog_ratings', $insert_rate );
		
		/* Rebuild rating stats for entry */
		$r = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as entry_rating_count, SUM(rating) as entry_rating_total',
											  'from'   => 'blog_ratings',
											  'where'  => "blog_id = ".intval($this->blog['blog_id']) . ' AND entry_id=' . $entry_id ) );
		
		$entry['entry_rating_count'] = $r['entry_rating_count'];
		$entry['entry_rating_total'] = $r['entry_rating_total'];
		
		$entry['entry_rating'] = $entry['entry_rating_count'] > $this->settings['blog_rating_treshhold'] ? ceil( $entry['entry_rating_total'] / $entry['entry_rating_count'] ) : 0;
		
		$this->DB->update( "blog_entries", array( 'entry_rating_total' => $r['entry_rating_total'], 'entry_rating_count' => $r['entry_rating_count'] ), "entry_id=" . $entry_id );
		
		/* Rebuild rating stats for blog */
		$r = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as blog_rating_count, SUM(rating) as blog_rating_total',
											  'from'   => 'blog_ratings',
											  'where'  => "blog_id = ".intval($this->blog['blog_id']) ) );

		$this->DB->update( "blog_blogs", array( 'blog_rating_total' => $r['blog_rating_total'], 'blog_rating_count' => $r['blog_rating_count'] ), "blog_id=".intval($this->blog['blog_id']) );

		$return	= array(
						'rating'	=> $rating,
						'total'		=> $entry['entry_rating_count'],
						'average'	=> intval( $entry['entry_rating'] ),
						'rated'		=> 'new'
						);

		$this->returnJsonArray( $return );
	}
}