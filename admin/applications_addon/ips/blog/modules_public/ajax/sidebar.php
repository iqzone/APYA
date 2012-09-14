<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog top 10 listing
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

class public_blog_ajax_sidebar extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		
		/* Load parsing lib */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry, null ) );
		
		/* INIT */
		$query    = array();
		$sort_key = '';
		$sort_by  = '';
		$max      = 10;
		$st       = 0;
		$select   = '';
		$where    = '';
		$blogs    = array();
		
		switch( $this->request['do'] )
		{
			case 'recent':
				return $this->_fetchRecent();
			break;
			case 'rating':
				$sort_key = 'blog_rating';
				$sort_by  = 'desc';
			break;
			case 'viewed':
				$sort_key = 'b.blog_num_views';
				$sort_by  = 'desc';
			break;
			case 'commented':
				$sort_key = 'bl.blog_num_comments';
				$sort_by  = 'desc';
			break;
			case 'bloggers':
				$sort_key = 'bl.blog_num_entries';
				$sort_by  = 'desc';
			break;
		}
		
		if ( $sort_key == 'blog_rating' )
		{
			$sort_key = 'blog_rating ' . $sort_by . ', blog_rating_count';
			$select   = ', CASE when b.blog_rating_count > ' . intval( $this->settings[ 'blog_rating_treshhold' ] ) . ' THEN (b.blog_rating_total/b.blog_rating_count) else 0 end as blog_rating';
		}
		
		if ( !$this->member->getProperty('g_is_supmod') and !$this->memberData['_blogmod']['moderate_can_disable'])
		{
			$query[] = "b.blog_disabled = 0";
		}

		if ( ! $this->member->getProperty('member_id') )
		{
			$query[] = "b.blog_allowguests = 1";
		}
		
		/* Perm blogs */
		$query[] = "( ( b.blog_owner_only=1 AND b.member_id="  . intval( $this->memberData['member_id'] ) . " ) OR b.blog_owner_only=0 ) AND ( b.blog_authorized_users LIKE '%,"  . intval( $this->memberData['member_id'] ) .  ",%' OR b.blog_authorized_users IS NULL )";

		/* Got a query */
		if ( count( $query ) )
		{
			$where = implode( ' AND ', $query );
		}
		
		$this->DB->build( array( 'select'	=> 'b.*, b.blog_id as blog_id_id' . $select,
								 'from'		=> array( 'blog_blogs' => 'b' ),
								 'where'	=> $where,
								 'order'    => $sort_key . ' ' . $sort_by,
								 'limit'    => array( $st, $max ),
								 'add_join'	=> array(array( 'select'	=> 'bl.*',
															 'from'	    => array( 'blog_lastinfo' =>'bl' ),
															 'where'    => 'b.blog_id=bl.blog_id',
															 'type'	    => 'left' ), 
								 					 array( 'select'	=> 'e.*',
															 'from'	    => array( 'blog_entries' =>'e' ),
															 'where'    => 'e.entry_id=bl.blog_last_entry',
															 'type'	    => 'left' ),
													 array(  'select'	=> 'm.members_display_name,m.members_seo_name',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=b.member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
		$o = $this->DB->execute();
		
		while( $blog = $this->DB->fetch( $o ) )
		{
			/* MySQL thing */
			$blog['blog_id'] = ( $blog['blog_id_id'] ) ? $blog['blog_id_id'] : $blog['blog_id'];
			
			/* Skin External blog with no url */
			if( $blog['blog_type'] == 'external' && ! $blog['blog_exturl'] )
			{
				continue;
			}
			
			/* Format Blog Data */
			$blog = $this->registry->getClass('blogFunctions')->buildBlogData( $blog );
			$blog = IPSMember::buildDisplayData( $blog, array( 'reputation' => 0, 'warn' => 0 ) );
			$blog = $this->registry->getClass('blogParsing')->parseEntry( $blog, 1, array( 'entryParse' => 1, 'noPositionInc' => 1 ), $blog );
			
			$blog['last_read']		      = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $blog['blog_id'] ) );
			$blogs[ $blog['blog_id'] ]    = $blog;
		}
		
		/* Now return it */
		$this->returnString( str_replace( '{style_image_url}', $this->settings['img_url'], $this->registry->getClass('output')->getTemplate('blog_list')->blogAjaxSidebar( $blogs ) ) );
	}
	
	/**
	 * Fetch extra sidebar data
	 *
	 * @access	protected
	 */
	protected function _fetchRecent()
	{
		/* INIT */
		$entries			= array();
		$_entries			= array();
		$whereExtra         = '';
		
		if ( ! $this->memberData['member_id'] )
		{
			$whereExtra = " AND b.blog_allowguests = 1";
		}
		
		/* Recent entries */
		if ( count( $this->caches['blog_stats']['recent_entries'] ) )
		{
			$this->DB->build( array('select'   => 'e.entry_id, e.entry_last_update, e.entry_name, e.blog_id, e.entry_name_seo, e.entry_author_id, e.entry_date',
									'from'     => array('blog_entries' => 'e' ),
									'where'    => "e.entry_id IN(" . implode( ",", array_keys( $this->caches['blog_stats']['recent_entries'] ) ) . ") {$whereExtra}",
									'order'    => 'e.entry_date DESC',
									'limit'    => array( 0, 10 ),
									'add_join' => array( array( 'select' => 'b.blog_name, b.blog_seo_name',
																'from'   => array( 'blog_blogs' => 'b' ),
																'where'  => 'b.blog_id=e.blog_id',
																'type'   => 'left' ) ) ) );
							
			$this->DB->execute();
			
			while( $entry = $this->DB->fetch() )
			{
				$_entries[ $entry['entry_id'] ]     = $entry;
				$mids[ $entry['entry_author_id'] ] = $entry['entry_author_id'];
			}
		}

		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids, 'all' );
			
			if ( count( $members ) )
			{
				foreach( $_entries as $cid => $cdata )
				{
					if ( $cdata['entry_author_id'] and isset( $members[ $cdata['entry_author_id'] ] ) )
					{
						$_entries[ $cid ] = array_merge( $_entries[ $cid ], $members[ $cdata['entry_author_id'] ] );
					}
				}
			}
		}

		if( count( $_entries ) > 0 )
		{
			if( is_array( $_entries ) )
			{
				foreach( $_entries as $eid => $entry )
				{
					$entry                = IPSMember::buildDisplayData( $entry, array( 'reputation' => 0, 'warn' => 0 ) );
					$entry['_entry_date'] = $this->registry->getClass('class_localization')->getDate( $entry['entry_date'], 'SHORT2' );
					
					// Updated by Rikki 12/27
					// $entry['newpost'] now set to true or false, and actual link is generated in template
					$entry['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $entry['blog_id'], 'itemID' => $entry['entry_id'] ) );
					
					if( $entry['entry_last_update'] > $entry['_lastRead'] )
					{
						$entry['newpost'] = true;
					}
					else
					{
						$entry['newpost'] = false;
					}
					
					$entries[ $eid ] = $entry;
				}
			}
		}

		/* Now return it */
		$this->returnString( str_replace( '{style_image_url}', $this->settings['img_url'], $this->registry->getClass('output')->getTemplate('blog_list')->blogAjaxSidebarREntries( $entries ) ) );
	}
}

?>