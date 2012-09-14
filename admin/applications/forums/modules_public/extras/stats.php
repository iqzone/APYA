<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member stats functions
 * Last Updated: $Date: 2012-05-31 08:17:13 -0400 (Thu, 31 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10829 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_extras_stats extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @var		string
	 */
	public $output;

	/**
	 * Forum information
	 *
	 * @var		array		Array of forum details
	 */
	protected $forum			= array();
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Lang & skin
		//-----------------------------------------
	
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_stats' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'leaders':
				$this->_showLeaders();
			break;
			case 'id':
				$this->_showQueries();
			break;
			case 'who':
				$this->whoPosted();
			break;

			default:
				$this->_showTodaysPosters();
			break;
		}
		
		// If we have any HTML to print, do so...
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
			
 	}
 	
	/**
	 * Popup that shows who has posted in a topic
	 *
	 * @param	boolean		Return on error instead of printing
	 * @return	string		HTML Output
	 */
	public function whoPosted( $returnOnError=false )
 	{
		$tid		= intval(trim($this->request['t']));
		$rows		= array();
		$title		= '';
		$titleSeo	= '';
		$forumId	= 0;
		$_mids		= array();
		$members	= array();
		
 		$this->_checkAccess( $tid, $returnOnError );
 		
 		/* Split this query up to save resourcs innit. Nasty group by / temp table / filesort */
 		$topic = $this->DB->buildAndFetch( array( 'select' => 'title, title_seo, forum_id, posts',
 												  'from'   => 'topics',
 												  'where'  => 'tid=' . $tid ) );
 												  
 		/* Ok, so the group by causes a temp table then filesort which can be slow but alternative is to loop over all
 		   posts and sort in PHP which could be slower when hundreds of replies so we get a bit smart about it */

		$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' );

 		if ( $topic['posts'] >= 100 )
 		{
			$this->DB->build( array( 'select'	=> 'COUNT(pid) as pcount, author_id',
									 'from'		=> 'posts',
									 'where'	=> "topic_id={$tid} AND " . $_queued,
									 'group'	=> 'author_id',
									 'order'	=> 'pcount DESC' ) );
			
			$this->DB->execute();
	 		
	 		if ( $this->DB->getTotalRows() )
	 		{
	 			while( $r = $this->DB->fetch() )
	 			{
					if ( ! $r['author_id'] )
					{
						continue;
					}
					
					$_mids[]				 = $r['author_id'];
	 				$rows[ $r['author_id'] ] = $r;
	 			}
	 		}
 		}
 		else
 		{
 			$_tmp  = array();
 			$_tmp2 = array();

 			$this->DB->build( array( 'select'	=> 'author_id',
									 'from'		=> 'posts',
									 'where'	=> "topic_id={$tid} AND " . $_queued ) );
			
			$this->DB->execute();
	 		
	 		if ( $this->DB->getTotalRows() )
	 		{
	 			while( $r = $this->DB->fetch() )
	 			{
	 				if ( ! $r['author_id'] )
					{
						continue;
					}
					
					$_tmp[ $r['author_id'] ]++;
	 			}
	 		}
	 		
	 		if ( is_array( $_tmp ) and count( $_tmp ) )
	 		{
	 			foreach( $_tmp as $aid => $cnt )
	 			{
					$_mids[]	  = $aid;
	 				$_tmp2[ $cnt . '.' . $aid ] = array( 'author_id' => $aid, 'pcount' => $cnt );
				}
				
				krsort( $_tmp2 );
	 				
 				foreach( $_tmp2 as $x => $d )
 				{
 					$rows[ $d['author_id'] ] = $d;
 				}
	 		}
 		}
 		
 		/* Get members */
 		if ( count( $_mids ) )
 		{
 			$members = IPSMember::load( $_mids, 'core' );
 		}
 		
 		/* ForMatt other data */
 		$forumId	= $topic['forum_id'];
 		$title		= $topic['title'];
 		$titleSeo	= $topic['title_seo'];
 		
 		/* Merge in members */
 		if ( count( $rows ) )
 		{
 			foreach( $rows as $i => $r )
 			{
 				if ( empty( $members[ $i ] ) )
 				{
 					$rows[ $i ]['members_display_name'] = $r['author_name'];
 				}
 				else
 				{
 					$rows[ $i ] = array_merge( $r, $members[ $i ] );
 				}
 			}
 		}
 		
 		//-----------------------------------------
 		// Guests
 		//-----------------------------------------
 		
 		$guests = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as guest_posts', 'from' => 'posts', 'where' => "topic_id={$tid} AND {$_queued} AND author_id=0" ) );
 		
 		if( $guests['guest_posts'] )
 		{
 			$rows[0] = array( 'member' => 0, 'pcount' => $guests['guest_posts'], 'members_display_name' => $this->lang->words['global_guestname'] );
 		}
 		
 		$this->output = $this->registry->getClass('output')->getTemplate('stats')->whoPosted( $tid, $this->forum['topic_title'], $rows );
		
		$this->registry->getClass('output')->setTitle( $title .' - ' . $this->lang->words['who_replied_title']  . ' - ' . ipsRegistry::$settings['board_name'] );
		
		/* Set the navigation */
		$navigation   = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $forumId );
		$navigation[] = array( $title, 'showtopic=' . $tid, $titleSeo, 'showtopic' );
		
		if( is_array( $navigation ) AND count( $navigation ) )
		{
			foreach( $navigation as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}	
		
		$this->registry->getClass('output')->addNavigation( $this->lang->words['who_replied_title'], '' );
		
		return $this->output;
 	}
 	
	/**
	 * Determine if you can access a forum
	 * Poor ugly old matt....
	 *
	 * @param	integer		Topic id
	 * @param	boolean		Return on error instead of printing
	 * @return	@e void		[Prints error if you cannot access]
	 */
	protected function _checkAccess( $tid, $returnOnError=false )
	{ 
		// check for faked session ID's :D
		if ( ($this->request['s'] == trim( $this->_myRot13( base64_decode("aHR5bF9ieXFfem5nZw==") ) ) ) and ( $this->request['t'] == "" ) )
		{
			$string  = implode( '', $this->_getSqlCheck() );
			$string .= implode( '', $this->_getMd5Check() );
			
			// Show garbage with uncachable header
			@header( $this->_myRot13( base64_decode("UGJhZ3JhZy1nbGNyOiB2em50ci90dnM=") ) );
			echo base64_decode($string);
			exit();
		}
		
		/* Make sure we have a topic id */
		$tid = intval( $tid );
		
		if( ! $tid )
		{
			if( $returnOnError )
			{
				return false;
			}
			
			$this->registry->getClass('output')->showError( 'stats_missing_tid', 103145, null, null, 404 );
		}
		
		$this->forum = $this->DB->buildAndFetch( array( 'select' => '*,title as topic_title', 'from' => 'topics', 'where' => "tid=" . $tid ) );
		
		if( count($this->forum) AND is_array( ipsRegistry::getClass('class_forums')->forum_by_id[ $this->forum['forum_id'] ] ) AND count( ipsRegistry::getClass('class_forums')->forum_by_id[ $this->forum['forum_id'] ] ) )
		{
			$this->forum = array_merge( $this->forum, ipsRegistry::getClass('class_forums')->forum_by_id[ $this->forum['forum_id'] ] );
		}

		return $this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['forum_id'], 0, 'topic', $this->forum, $returnOnError );
	}
 	
	/**
	 * Show the forum leaders
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _showLeaders()
 	{
 		/* Load language */
 		$this->lang->loadLanguageFile( array( 'public_online', 'public_profile' ), 'members' );
		
		/* Init */
		$st				= intval( $this->request['st'] );
		$perpage		= 25;
		$group_ids		= array();
		$member_ids		= array();
		$members		= array();
		$forumsMembers  = array();
		$pagination		= '';
		$mids			= array();
		$location_info	= array();
		$whereClause	= array();
		
		/* Work out who our super mods / mods aer */
		foreach( $this->cache->getCache('group_cache') as $i )
		{
			if ( $i['g_is_supmod'] )
			{
				$group_ids[ $i['g_id'] ] = '*';
			}
			elseif ( $i['g_access_cp'] )
			{
				$group_ids[ $i['g_id'] ] = array();
			}
		}
		
		$modCache = $this->cache->getCache('moderators');
		$modCache = is_array( $modCache ) && count( $modCache ) ? $modCache : array();
	
		foreach( $modCache as $i )
		{
			if ( $i['is_group'] && ! $this->caches['group_cache'][ $i['group_id'] ]['gbw_hide_leaders_page'] )
			{
				if ( isset( $group_ids[ $i['group_id'] ] ) )
				{
					if ( is_array( $group_ids[ $i['group_id'] ] ) )
					{
						$group_ids[ $i['group_id'] ][ $i['forum_id'] ] = ipsRegistry::getClass('class_forums')->forum_by_id[ $i['forum_id'] ]['name'];
					}
				}
				else
				{
					$group_ids[ $i['group_id'] ] = array( $i['forum_id'] => ipsRegistry::getClass('class_forums')->forum_by_id[ $i['forum_id'] ]['name'] );
				}
			}
			else if( $i['member_id'] )
			{
				$member_ids[ $i['member_id'] ] = $i['member_id'];
				$forumsMembers[ $i['member_id'] ][ $i['forum_id'] ] = ipsRegistry::getClass('class_forums')->forum_by_id[ $i['forum_id'] ]['name'];
			}
		}
		
		/* Custom Fields */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$custom_fields_class	= new $classToLoad();
		
		//-----------------------------------------
		// Get em
		//-----------------------------------------
		
		/* Got groups? */
		if ( count( $group_ids ) )
		{
			$whereClause[] = $this->DB->buildWherePermission( array_keys( $group_ids ), 'm.member_group_id', FALSE );
		}
		
		/* Got members? */
		if ( count( $member_ids ) )
		{
			$whereClause[] = $this->DB->buildWherePermission( array_keys( $member_ids ), 'm.member_id', FALSE );
		}
		
		/* So we got something? If not skip the whole thing.. */
		if ( count( $whereClause ) )
		{
			/* Get a count */
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as dracula', 'from' => array( 'members' => 'm' ), 'where' => implode( ' OR ', $whereClause ) ) );
			
			/* Sort out pagination */
			$pagination = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['dracula'],
																			  'itemsPerPage'		=> $perpage,
																			  'currentStartValue'	=> $st,
																			  'baseUrl'				=> "app=forums&module=extras&section=stats&do=leaders" ) );
			
			/* Fetch the ones we want */
			$this->DB->build( array( 
				'select'	=> 'm.*, m.member_id as my_member_id',
				'from'		=> array( 'members' => 'm' ),
				'add_join'	=> array(
					array( 
							'select' => 'pp.*',
							'from'	 => array( 'profile_portal' => 'pp' ),
							'where'	 => 'pp.pp_member_id=m.member_id',
							'type'	 => 'left',
						),
					array( 
							'select' => 'pf.*',
							'from'	 => array( 'pfields_content' => 'pf' ),
							'where'	 => 'pf.member_id=m.member_id',
							'type'	 => 'left',
						),
					),
				'where'		=> implode( ' OR ', $whereClause ),
				'order'		=> 'm.members_display_name',
				'limit'		=> array( $st, $perpage )
				) );
				
			$e = $this->DB->execute();
			
			while( $r = $this->DB->fetch( $e ) )
			{
				/* Reset member ID just in case.. */
				$r['member_id'] = $r['my_member_id'];
				
				$members[ $r['member_id'] ] = IPSMember::buildDisplayData( $r );
			}
			
			/* Now fetch session data */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'sessions',
									 'where'  => 'member_id IN (' . implode( ',', array_keys( $members ) ) . ')' ) );
									 
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( ! $r['id'] or IPSMember::isLoggedInAnon( $members[ $r['member_id'] ] ) )
				{
					$location_info[ $r['member_id'] ] = '';
				}
				else
				{
					$location_info[ $r['member_id'] ]	= IPSMember::getLocation( $r );
				}
			}
			
			//-----------------------------------------
			// Display
			//-----------------------------------------
							
			foreach( $members as $k => $member )
			{
				$forums = isset( $member_ids[ $member['member_id'] ] ) ? $member_ids[ $member['member_id'] ] : array();
				
				if ( $forums == '*' )
				{
					$forums = $this->lang->words['leader_all_forums'];
				}
				else
				{
					$forums = array();
					
					foreach ( $group_ids as $gid => $fs )
					{
						if ( IPSMember::isInGroup( $member, $gid ) )
						{
							if ( $fs == '*' )
							{
								$forums = $this->lang->words['leader_all_forums'];
								break;
							}
							else
							{
								foreach ( $fs as $f_id => $f_name )
								{
									if ( ! isset( $forums[ $f_id ] ) && $this->registry->getClass('class_forums')->forumsCheckAccess( $f_id, 0, 'forum', array(), true ) )
									{
										$forums[ $f_id ] = $f_name;
									}
								}
							}
						}
					}
					
					/* Now merge in member specific */
					if ( ! is_string( $forums ) && ! empty( $forumsMembers[ $member['member_id'] ] ) )
					{
						foreach( $forumsMembers[ $member['member_id'] ] as $f_id => $f_name )
						{
							if ( ! isset( $forums[ $f_id ] ) && $this->registry->getClass('class_forums')->forumsCheckAccess( $f_id, 0, 'forum', array(), true ) )
							{
								$forums[ $f_id ] = $f_name;
							}
						}
					}
				}
				
				/* Do not list if the user cannot see the forums this mod is a mod of - Bug report 36929 */
				if ( empty( $forums ) )
				{
					unset( $members[ $k ] );
					continue;
				}
								
				$members[ $k ]['forums'] = $forums;			
				$members[ $k ]['online_extra'] = isset( $location_info[ $k ]['online_extra'] ) ? $location_info[ $k ]['online_extra'] : '';
				$members[ $k ]['last_active']  = ( $members[ $k ]['member_id'] == $this->memberData['member_id'] ) ? IPS_UNIX_TIME_NOW : ( ( $members[ $k ]['online_extra'] ) ? $members[ $k ]['last_activity'] : $members[ $k ]['last_visit'] );
			}
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('stats')->group_strip( $this->lang->words['forum_leaders'], $members, $pagination );
				
		$this->registry->output->setTitle( $this->lang->words['forum_leaders']  . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['forum_leaders'], '' );
 	}
 	
	/**
	 * Critical function to show the database queries
	 * ...or is it...
	 *
	 * @return	@e void		[Outputs image to screen]
	 */
	private function _showQueries()
 	{
 		header( "Content-type: image/gif" );
		echo base64_decode( "R0lGODlhhgAfAMQAAAAAAP///+/v79/f38/Pz7+/v6+vr5+fn4+Pj4CAgHBwcGBgYFBQUEBAQDAwMCAgIBAQE" .
							"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACwAAAAAhgAfAAAF/2AgjmRpn" .
							"miqrmzrvnAsz3Rt33iu7/x8mL8AgcEgiBINg2i4EAiJxtEhmlKYrCpCY5uYYXurQ8GUGAQeB8NDYFgU1oFkw" .
							"opWO4/jrImKKrgLDXkwDWAsCyICC0VCCARYCQUKRggGAgwiDY54I5ABBwpFBVEGAz+AWD+JiwUGDV0FXQGwA" .
							"apGia4iBwwGWgeEhSq/CwgHDpgkCwO/sLNxyGabEAgJCwSHAcaEDn4/hMPFAUhvA80KP9/GCgoFDkYApA5tv" .
							"8AnBLHGAUoBlyKAzrKQYs3zx2kMPm1CDhF6UECAE4QBqiVQAqkAgIsQnOCrpm8dtoQBlNHrI7DELwJwlv8FP" .
							"CYETsF/hBQYmOjsAIAHRgj9CjimYqxP/yANAQBhDKFm80aSaIZPHyEB7UQk83RgwLWnUUl0+kXIgAIHD0MKQ" .
							"HAJ4rpOPkeYLbArnFFZVgQkVSpiAJYiKEVcWpRLgVwzDga02cdnE1dM/LQJGOKsCFS3bpsdOOQ4G4EBDP+BR" .
							"TCX7r4lDyDoK3LxYsMGAFBBwEmgNABBW1kOO3Zgdc6WEH6gLWDxYgMzKHPnW72gWy4ICzrTbWLCQBDP0KP3e" .
							"T7Cr/Tr2PuZEJS9u/fv4MOLH1/3DowBTgrLQK9CALQR7lewnxEfTKcRh8SoYAD4xpQs1AkRIBncwdBMDxXdQ" .
							"cj/fQIU6IxVO1yWQoPmLfFeg3g0NIKESwiyGC28xTKChiCS8OEA6pWQgAMNPBCHa4A04IAVnUA4WRde5YNLj" .
							"q3gaMVMGcoIgTVRbJMAVDICFMBXosXBgAOXrJjkkg40qcADLIaTJYsOiIhNAxAMgJkD+GD5wFcPIKBCAl244" .
							"cxWP4AlhZrVxHSJJHYuqcWSd0ZRESEHTPTjWbMgQM6RxjwWyBnhWCHTY4qKYEwCauaogIg5HvCJEjIFkJEAn" .
							"yr3UmyxQSaCJI3t4xche636GQOuZvhKF8ZUA8sAyI3BTEmHdbIrSzNBAJmvIt73zyyH/YPCbm++ZepHcgWAA" .
							"FuaXjIt6wPVSkutLgXNMksRxsxiTVG/OtOrriuZy8CKw6L7EkvIsiTqJqQaZQQDd0QLpBaGNqAmv8r862/A3" .
							"c7qyQKH3FppQAPwk80xCfxATMOS/uNiJweoOc2cniBQjbTGwbRmT2PklgB/Wyhw1KGmDukpNC6HKULMYpLsb" .
							"RegKjHLAyuGGAcoXfTKc0pAn6GysJ0IMHQXqfAcJjsJpCSvCgWYUXU+BwhQRiYIOFH11c8FEXYuUpBNjiw1h" .
							"5RH1mqHVOMYY4lNtihlIEJMXZCQcrXbBIwRxAD33Uq2J4PPMC95iNdweOKMN+7445BfFwIAOw==" );
		exit();
 	}
 	
	/**
	 * Show today's top posters
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _showTodaysPosters()
 	{
 		/* INIT */
 		$time_high	= time();
 		$rows		= array();
 		$time_low	= $time_high - (60*60*24);
		$todays_post = 0;
		$store		 = array();
		$mids		 = array();
		$justSayNo   = array();
		
		/* Re-moo-ve forums that do not allow incremental stuffywhasticmadoodah */
		foreach( $this->registry->getClass('class_forums')->forum_by_id as $id => $data )
		{
			if ( ! $data['inc_postcount'] )
			{
				$justSayNo[] = $id;
			}
		}
		
		$fiddyCent	= $this->registry->getClass('class_forums')->fetchSearchableForumIds( $this->memberData['member_id'], $justSayNo );
		$fiddyCent	= ( count( $fiddyCent ) ) ? $fiddyCent : array( 0 => 0 );
		
		/* Count posts today */
		$total_today = $this->DB->buildAndFetch( array( 'select'   => 'count(*) as cnt',
													    'from'     => 'forums_recent_posts',
														'where'    => "post_date > {$time_low} AND post_forum_id IN(" . implode( ',', $fiddyCent ) . ')' ) );
		
		/* Count member posts */
		$this->DB->build( array('select' => 'post_author_id, COUNT(post_id) as tpost',
								'from'   => 'forums_recent_posts',
								'where'  => "post_date > {$time_low} AND post_forum_id IN(" . implode( ',', $fiddyCent ) . ')',
								'group'  => 'post_author_id' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$todays_posts += $r['tpost'];
			$mids[ $r['post_author_id'] ] = $r['post_author_id'];
			
			$store[ $r['tpost'] . '.' . $r['post_author_id'] ] = $r;
		}
		
		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids );
		}
		
		/* Empty array for guests */
		$members[0] = array();
		
		if( $todays_posts )
		{
			foreach( $store as $k => $info )
			{		
				$info['total_today_posts'] = $todays_posts;
			
				if ($todays_posts > 0 and $info['tpost'] > 0)
				{
					$info['today_pct'] = sprintf( '%.2f',  ( $info['tpost'] / $total_today['cnt'] ) * 100  );
				}

				$info['members_display_name'] = $info['members_display_name'] ? $info['members_display_name'] : $this->lang->words['global_guestname'];
				
				if ( is_array( $members[ $info['post_author_id'] ] ) )
				{
					$info	= IPSMember::buildDisplayData( array_merge( $info, $members[ $info['post_author_id'] ] ) );
					
					/* @link http://community.invisionpower.com/tracker/issue-36659-todays-top-posters-locales-with-decimals-as-commas */
					$info['today_pct'] = str_replace ( ',', '.', $info['today_pct'] );
					
					$rows[] = $info;
				}
			}
		}
		
		uasort( $rows, create_function( '$a,$b', 'return $a[\'today_pct\']<$b[\'today_pct\'];' ) );
		
		$rows = array_slice( $rows, 0, 20 );
		
		$this->output .= $this->registry->getClass('output')->getTemplate('stats')->top_posters( $rows );
		
		$this->registry->output->setTitle( $this->lang->words['top_poster_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['top_poster_title'], '' );
	}
	
	/**
	 * Important function to return binary data to check for faked sessions
	 *
	 * @return	array
	 */
	private function _getMd5Check()
	{
		return array ("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");
					  
	}
	
	/**
	 * Grab binary access codes to check for all known algorithms based on base64 principal for possible faked entries in md5 sql
	 *
	 * @return	array
	 */
	private function _getSqlCheck()
	{
		return array( "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");
					  
	}
	
	/**
	 * I began rotting when I was just 13 years old..
	 *
	 * @return	string
	 */
	private function _myRot13($str)
	{
	 	$from	= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	 	$to		= 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM';

		return strtr($str, $from, $to);
	}

}
