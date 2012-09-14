<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Board Index View
 * Last Updated: $Date: 2012-05-29 05:09:54 -0400 (Tue, 29 May 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10804 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_boards extends ipsCommand
{
	/**
	 * Main Execution Function
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_boards' ) );
		
		if (! $this->memberData['member_id'] )
		{
			$this->request['last_visit'] = time();
		}
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		$cat_data = $this->processAllCategories();
		
		//-----------------------------------------
		// Add in show online users
		//-----------------------------------------
		
		$active = $this->getActiveUserDetails();

		/* Check for sidebar hooks */
		$show_sidebar = false;
		
		if( is_array( $this->caches['hooks']['templateHooks']['skin_boards'] ) )
		{
			foreach( $this->caches['hooks']['templateHooks']['skin_boards'] as $c => $hook )
			{
				if( $hook['id'] == 'side_blocks' && $hook['skinFunction'] == 'boardIndexTemplate' )
				{
					$show_sidebar = true;
					break;
				}
			}
		}

		//-----------------------------------------
		// Show the template
		//-----------------------------------------
		
		$stats_info = $this->getTotalTextString();
		
		$active = array_merge( $active, array(  'text'    => $this->lang->words['total_word_string'],
												'posts'   => $this->total_posts,
												'active'  => $this->users_online,
												'members' => $this->total_members,
												'cut_off' => $this->settings['au_cutoff'],
												'info'    => $stats_info )	);
		
		/* Output */
		$template = $this->registry->getClass('output')->getTemplate('boards')->boardIndexTemplate( $this->registry->getClass('class_localization')->getDate( $this->memberData['last_visit'], 'LONG' ),
																									$active,
																									$cat_data,
																									$show_sidebar );
		
		//-----------------------------------------
		// Meta tags
		//-----------------------------------------
	
		if( $this->settings['seo_index_md'] )
		{
			$this->registry->output->addMetaTag( 'description', $this->settings['seo_index_md'], false );
		}
		
		if( $this->settings['seo_index_mk'] )
		{
			$this->registry->output->addMetaTag( 'keywords', $this->settings['seo_index_mk'], false );
		}
		
		$this->registry->output->addCanonicalTag( "act=idx", 'public', 'false' );
		
		//-----------------------------------------
		// Set ad codes
		//-----------------------------------------

		if( $this->registry->getClass('IPSAdCode')->userCanViewAds() )
		{
			$this->registry->getClass('IPSAdCode')->setGlobalCode( 'header', 'ad_code_board_index_header' );
			$this->registry->getClass('IPSAdCode')->setGlobalCode( 'footer', 'ad_code_board_index_footer' );
		}
		
		//-----------------------------------------
		// Print as normal
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $this->settings['seo_index_title'] ? $this->settings['seo_index_title'] : $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $template );
        $this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Builds an array of category data for output
	 *
	 * @return	array
	 */
	public function processAllCategories()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$return_cat_data	= array();
		$root				= array();
		$parent				= array();
		$member_ids			= array();
		
		//-----------------------------------------
		// Want to view categories?
		//-----------------------------------------
		
		if ( ! empty( $this->request['c'] ) )
		{
			foreach( explode( ",", $this->request['c'] ) as $c )
			{
				$c = intval( $c );
				$i = $this->registry->getClass('class_forums')->forum_by_id[ $c ]['parent_id'];
				
				$root[ $i ]   = $i;
				$parent[ $c ] = $c;
			}
		}
		
		if ( ! count( $root ) )
		{
			$root[] = 'root';
		}
		
		foreach( $root as $root_id )
		{
			if( is_array( $this->registry->class_forums->forum_cache[ $root_id ] ) and count( $this->registry->class_forums->forum_cache[ $root_id ] ) )
			{
				foreach( $this->registry->class_forums->forum_cache[ $root_id ] as $id => $forum_data )
				{
					$temp_cat_data = array();
					
					//-----------------------------------------
					// Only showing certain root forums?
					//-----------------------------------------
					
					if( count( $parent ) )
					{
						if( ! in_array( $id, $parent ) )
						{
							continue;
						}
					}
					
					$cat_data = $forum_data;
					
					if( isset( $this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) AND is_array( $this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) )
					{						
						foreach( $this->registry->class_forums->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							$forum_data['show_subforums'] 	= isset($forum_data['show_subforums']) 	? $forum_data['show_subforums'] : '';
							$forum_data['last_unread']		= isset($forum_data['last_unread'])		? $forum_data['last_unread']	: '';
							
							//-----------------------------------------
							// Get all subforum stats
							// and calculate
							//-----------------------------------------						
							
							if ( $forum_data['redirect_on'] )
							{
								$forum_data['redirect_target'] = isset($forum_data['redirect_target']) ? $forum_data['redirect_target'] : '_parent';
								
								$temp_cat_data[ $forum_data['id'] ] = $forum_data;
							}
							else
							{
								$temp_cat_data[ $forum_data['id'] ] = $this->registry->class_forums->forumsFormatLastinfo( $this->registry->class_forums->forumsCalcChildren( $forum_data['id'], $forum_data ) );
							}

							if( $temp_cat_data[ $forum_data['id'] ]['last_poster_id'] )
							{
								$member_ids[ $forum_data['id'] ]	= $temp_cat_data[ $forum_data['id'] ]['last_poster_id'];
							}
						}
					}

					if ( count( $temp_cat_data ) )
					{
						$return_cat_data[] = array( 'cat_data'   => $cat_data,
													'forum_data' => $temp_cat_data );
					}
					
					$temp_cat_data = array();
				}
			}
		}

		if( count($member_ids) )
		{
			$_members	= IPSMember::load( array_unique($member_ids), 'members,profile_portal' );
			
			foreach( $member_ids as $forumId => $memberId )
			{
				$_member	= $_members[ $memberId ];
				
				if( $_member['member_id'] )
				{
					$_member	= IPSMember::buildDisplayData( $_member );
					
					foreach( $return_cat_data as $k => $_type )
					{
						foreach( $_type as $__type => $obj )
						{
							if( $__type == 'forum_data' )
							{
								foreach( $obj as $fid => $fdata )
								{
									if( $fid != $forumId )
									{
										continue;
									}
									
									$return_cat_data[ $k ][ $__type ][ $fid ]	= array_merge( $_member, $fdata );
									break 3;
								}
							}
						}
					}
				}
			}
		}

		return $return_cat_data;
	}

	/**
	 * Returns an array of active users
	 *
	 * @return	array
	 */
	public function getActiveUserDetails()
	{
		$active = array( 'TOTAL'   => 0 ,
						 'NAMES'   => array(),
						 'GUESTS'  => 0 ,
						 'MEMBERS' => 0 ,
						 'ANON'    => 0 ,
					   );
		
		if ( $this->settings['show_active'] && $this->memberData['gbw_view_online_lists'] )
		{
			if ( ! $this->settings['au_cutoff'] )
			{
				$this->settings['au_cutoff'] = 15;
			}
			
			//-----------------------------------------
			// Get the users from the DB
			//-----------------------------------------
			
			$cut_off = $this->settings['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			$rows    = array();
			$ar_time = time();
			
			if ( $this->memberData['member_id'] )
			{
				$rows = array( $ar_time.'.'.md5( microtime() ) => array('id'           => 0,
																		'login_type'   => IPSMember::isLoggedInAnon($this->memberData),
																		'running_time' => $ar_time,
																		'seo_name'     => $this->memberData['members_seo_name'],
																		'member_id'    => $this->memberData['member_id'],
																		'member_name'  => $this->memberData['members_display_name'],
																		'member_group' => $this->memberData['member_group_id'] ) );
			}
			
			$this->DB->build( array('select' => 'id, member_id, member_name, seo_name, login_type, running_time, member_group, uagent_type',
									'from'   => 'sessions',
									'where'  => "running_time > {$time}" )	);
			$this->DB->execute();
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ( $r = $this->DB->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );

			//-----------------------------------------
			// cache all printed members so we
			// don't double print them
			//-----------------------------------------
			
			$cached = array();
			
			foreach ( $rows as $result )
			{
				$last_date = $this->registry->getClass('class_localization')->getDate( $result['running_time'], 'TINY' );
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( isset( $result['uagent_type'] ) && $result['uagent_type'] == 'search' )
				{
					/* Skipping bot? */
					if ( ! $this->settings['spider_active'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------
					
					if ( ! $cached[ $result['member_name'] ] )
					{
						$active['NAMES'][] = IPSMember::makeNameFormatted( $result['member_name'], $result['member_group'] );
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------
						
						$active['GUESTS']++;
					}
				}
				
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				
				else if ( ! $result['member_id'] OR ! $result['member_name'] )
				{
					$active['GUESTS']++;
				}
				
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = IPSMember::makeNameFormatted( $result['member_name'], $result['member_group'] );
						
						/* Reset login type in case the board/group setting got changed */
						$result['login_type']  = IPSMember::isLoggedInAnon( array( 'login_anonymous' => $result['login_type'] ), $result['member_group'] );
						
						if ( $result['login_type'] )
						{
							if ( $this->memberData['g_access_cp'] || ( $this->memberData['member_id'] == $result['member_id'] ) )
							{
								$active['NAMES'][] = IPSMember::makeProfileLink( $result['member_name'], $result['member_id'], $result['seo_name'], '', $last_date ) . "*";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'][] = IPSMember::makeProfileLink( $result['member_name'], $result['member_id'], $result['seo_name'], '', $last_date );
						}
					}
				}
			}

			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			$this->users_online = $active['TOTAL'];
		}
		
		$this->lang->words['active_users'] = sprintf( $this->lang->words['active_users'], $this->settings['au_cutoff'] );

		return $active;
	}

	/**
	 * Returns an array of board stats
	 *
	 * @return	string		Stats string
	 */
	public function getTotalTextString()
	{
		/* INIT */
		$stats_output = array();
		
		if ( $this->settings['show_totals'] )
		{
			$stats = $this->cache->getCache('stats');
			
			//-----------------------------------------
			// We need to determine if we have the most users ever online if we aren't
			// showing active users in the stats block
			//-----------------------------------------
			
			if( empty($this->users_online) )
			{
				$cut_off = $this->settings['au_cutoff'] * 60;
				$time    = time() - $cut_off;
				$total	 = $this->DB->buildAndFetch( array( 'select'	=> 'count(*) as users_online', 'from' => 'sessions', 'where' => "running_time > $time" ) );

				$this->users_online = $total['users_online'];
			}
			
			//-----------------------------------------
			// Update the most active count if needed
			//-----------------------------------------
			
			if ( $this->users_online > $stats['most_count'] )
			{
				$stats['most_count'] = $this->users_online;
				$stats['most_date']  = time();
				
				$this->cache->setCache( 'stats', $stats, array( 'array' => 1 ) );
			}
			
			$stats_output['most_time']   = $this->registry->getClass( 'class_localization')->getDate( $stats['most_date'], 'DATE' );
			$stats_output['most_online'] = $this->registry->getClass('class_localization')->formatNumber( $stats['most_count'] );
			
			$this->lang->words['most_online'] = str_replace( "<#NUM#>" ,  $stats_output['most_online']	, $this->lang->words['most_online'] );
			$this->lang->words['most_online'] = str_replace( "<#DATE#>",  $stats_output['most_time']	, $this->lang->words['most_online'] );

			$stats_output['total_posts'] = $stats['total_replies'] + $stats['total_topics'];
			
			$stats_output['total_posts'] = $this->registry->getClass('class_localization')->formatNumber( $stats_output['total_posts'] );
			$stats_output['mem_count']   = $this->registry->getClass('class_localization')->formatNumber( $stats['mem_count'] );
			
			$this->total_posts    = $stats_output['total_posts'];
			$this->total_members  = $stats_output['mem_count'];
			
			$stats_output['last_mem_seo']	= $stats['last_mem_name_seo'] ? $stats['last_mem_name_seo'] : IPSText::makeSeoTitle( $stats['last_mem_name'] );
			$stats_output['last_mem_link']	= $this->registry->output->formatUrl( $this->registry->output->buildUrl( "showuser=" . $stats['last_mem_id'], 'public' ), $stats_output['last_mem_seo'], 'showuser' );
			$stats_output['last_mem_name']	= $stats['last_mem_name'];
			$stats_output['last_mem_id']	= $stats['last_mem_id'];
	
			$this->lang->words['total_word_string'] = str_replace( "<#posts#>" , $stats_output['total_posts']   , $this->lang->words['total_word_string'] );
			$this->lang->words['total_word_string'] = str_replace( "<#reg#>"   , $stats_output['mem_count']     , $this->lang->words['total_word_string'] );
			$this->lang->words['total_word_string'] = str_replace( "<#mem#>"   , $stats_output['last_mem_name'] , $this->lang->words['total_word_string'] );
			$this->lang->words['total_word_string'] = str_replace( "<#link#>"  , $stats_output['last_mem_link'] , $this->lang->words['total_word_string'] ); 
		}

		return $stats_output;
	}
}