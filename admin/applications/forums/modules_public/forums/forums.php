<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum Viewing
 * Last Updated: $Date: 2012-05-22 10:39:38 -0400 (Tue, 22 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10779 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_forums extends ipsCommand
{
	/**
	 * Array of form data
	 *
	 * @var		array
	 */
	protected $forum				= array();
	
	/**
	 * Array of topic ids to open
	 *
	 * @var		array
	 */
	protected $update_topics_open	= array();
	
	/**
	 * Array of topic ids to close
	 *
	 * @var		array
	 */
	protected $update_topics_close	= array();
	
	/**
	 * Permissions array
	 *
	 */
	protected $permissions			= array();
	protected $_sdTids				= array();
	
	/**
	 * Permissions
	 * @var	bool
	 */
	protected $can_close_topics		= false;
	protected $can_move_topics		= false;
	protected $can_edit_topics		= false;
	protected $can_open_topics		= false;
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->initForums();

		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------
		
		if( ! $this->forum['id'] )
		{
			$this->registry->getClass('output')->showError( 'forums_no_id', 10333, null, null, 404 );
		}
		
		//-----------------------------------------
		// Build permissions
		//-----------------------------------------
		
		$this->buildPermissions();
		
		//-----------------------------------------
		// Is it a redirect forum?
		//-----------------------------------------
		
		if( !empty( $this->forum['redirect_on'] ) )
		{
			$redirect = $this->DB->buildAndFetch( array( 'select' => 'redirect_url', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );

			if( $redirect['redirect_url'] )
			{
				//-----------------------------------------
				// Update hits:
				//-----------------------------------------
				
				$this->DB->buildAndFetch( array( 'update' => 'forums', 'set' => 'redirect_hits=redirect_hits+1', 'where' => "id=".$this->forum['id']) );
				
				//-----------------------------------------
				// Boink!
				//-----------------------------------------
				
				$this->registry->getClass('output')->silentRedirect( $redirect['redirect_url'] );
			}
		}
		
		//-----------------------------------------
		// If this is a sub forum, we need to get
		// the cat details, and parent details
		//-----------------------------------------
		
		$this->nav = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $this->forum['id'] );
		
		//-----------------------------------------
		// Check forum access perms
		//-----------------------------------------
		
		if( empty( $this->request['L'] ) )
		{
			$this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['id'], 1 );
		}
		
		//-----------------------------------------
		// Are we viewing the forum, or viewing the forum rules?
		//-----------------------------------------
		
		$subforum_data  = array();
		$data		   = array();

		if( $this->registry->getClass('class_forums')->forumsGetChildren( $this->forum['id'] ) )
		{
			$subforum_data = $this->showSubForums();
		}
		
		if ( $this->forum['sub_can_post'] )
		{ 
			$data = $this->showForum();
		}
		else
		{
			//-----------------------------------------
			// No forum to show, just use the HTML in $this->sub_output
			// or there will be no HTML to use in the str_replace!
			//-----------------------------------------
			
			$subforum_data = $subforum_data ? $subforum_data : $this->showSubForums();
		}
		
		//-----------------------------------------
		// Set permissions
		//-----------------------------------------
		
		$this->forum['_user_can_post'] = $this->registry->class_forums->canStartTopic( $this->forum['id'] );

		//-----------------------------------------
		// Forum rules
		//-----------------------------------------
		
		if( $this->forum['show_rules'] == 2)
		{
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'rules';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
			
			$this->forum['rules_text'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $this->forum['rules_text'] );
			
			/* @link http://community.invisionpower.com/tracker/issue-37441-forum-rules/ */
			$this->forum['rules_text'] = str_replace( "<span rel='lightbox'>", "<span>", $this->forum['rules_text'] );
			$this->forum['rules_text'] = str_replace( "class='bbc_img'", "", $this->forum['rules_text'] );
		}
				
		//-----------------------------------------
		// Show the template
		//-----------------------------------------
		
		if ( !$this->request['sort_key'] )
		{
			$this->request['sort_key'] = $this->forum['sort_key'];
		}
		if ( !$this->request['sort_by'] )
		{
			$this->request['sort_by'] = $this->forum['sort_order'];
		}
							
		$template = $this->registry->getClass('output')->getTemplate('forum')->forumIndexTemplate( 
																									$this->forum,
																									$data['announce_data'],
		 																							$data['topic_data'],
																									$data['other_data'],
																									$data['multi_mod_data'],
																									$subforum_data,
																									$data['footer_filter'],
																									$data['active_users'],
																									$this->registry->getClass('class_forums')->forumsGetModerators( $this->forum['id'] )
																								);
																								
		$this->registry->getClass('output')->setTitle( strip_tags($this->forum['name']) . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->getClass('output')->addContent( $template );

		if( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
		//-----------------------------------------
		// Add code
		//-----------------------------------------
		
		if( $this->registry->getClass('IPSAdCode')->userCanViewAds() )
		{
			$this->registry->getClass('IPSAdCode')->setGlobalCode( 'header', 'ad_code_forum_view_header' );
			$this->registry->getClass('IPSAdCode')->setGlobalCode( 'footer', 'ad_code_forum_view_footer' );
		}

		if( $this->forum['parent_id'] == 0 )
		{
			$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='{$this->settings['base_url']}' />" );
		}
		else
		{
			$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'showforum=' . $this->forum['parent_id'], 'public', $this->registry->getClass('class_forums')->forum_by_id[ $this->forum['parent_id'] ]['name_seo'], 'showforum' ) . "' />" );
		}
		
		if ( $this->forum['description'] )
		{
			$this->registry->output->addMetaTag( 'description', $this->forum['name'] . ': ' . $this->forum['description'] );
		}
		else
		{
			$this->registry->output->addMetaTag( 'description', $this->forum['name'] );
		}
		
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Setup for the forum controller
	 *
	 * @return	@e void
	 */
	public function initForums()
	{
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_forums', 'public_boards' ) );
		
		//-----------------------------------------
		// Multi TIDS?
		// If st is not defined then kill cookie
		// st will always be defined across pages
		//-----------------------------------------
		
		if( !array_key_exists( 'st', $this->request ) AND !array_key_exists( 'prune_day', $this->request ) )
		{
			IPSCookie::set('modtids', ',', 0);
			$this->request['selectedtids'] = '';
		}
		else
		{
			$this->request['selectedtids'] = IPSCookie::get('modtids');
		}

		//-----------------------------------------
		// Get the forum info based on the forum ID,
		// and get the category name, ID, etc.
		//-----------------------------------------
		
		$this->forum = $this->registry->getClass('class_forums')->getForumById( $this->request['f'] );
		
		/* Followed stuffs */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'forums', 'forums' );
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('tags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' )  );
		}
		
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Builds permissions for the forum controller
	 *
	 * @return	@e void
	 */
	public function buildPermissions()
	{
		$mod = $this->memberData['forumsModeratorData'];
		
		if( $this->memberData['g_is_supmod'] )
		{
			$this->can_edit_topics  = 1;
			$this->can_close_topics = 1;
			$this->can_open_topics  = 1;
			$this->can_move_topics	= 1;
		}
		else if( isset($mod[ $this->forum['id'] ]) AND is_array( $mod[ $this->forum['id'] ] ) )
		{
			if ( $mod[ $this->forum['id'] ]['edit_topic'] )
			{
				$this->can_edit_topics = 1;
			}
			
			if ( $mod[ $this->forum['id'] ]['close_topic'] )
			{
				$this->can_close_topics = 1;
			}
			
			if ( $mod[ $this->forum['id'] ]['open_topic'] )
			{
				$this->can_open_topics  = 1;
			}
			
			if ( $mod[ $this->forum['id'] ]['move_topics'] )
			{
				$this->can_move_topics  = 1;
			}
		}
		
		$this->permissions['PostSoftDelete']			= $this->registry->getClass('class_forums')->canSoftDeletePosts( $this->forum['id'], array() );
		$this->permissions['PostSoftDeleteRestore']		= $this->registry->getClass('class_forums')->can_Un_SoftDeletePosts( $this->forum['id'] );
		$this->permissions['PostSoftDeleteSee']			= $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( $this->forum['id'] );
		$this->permissions['SoftDeleteReason']			= $this->registry->getClass('class_forums')->canSeeSoftDeleteReason( $this->forum['id'] );
		$this->permissions['SoftDeleteContent']			= $this->registry->getClass('class_forums')->canSeeSoftDeleteContent( $this->forum['id'] );
		$this->permissions['TopicSoftDelete']			= $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->forum['id'], array() );
		$this->permissions['TopicSoftDeleteRestore']	= $this->registry->getClass('class_forums')->can_Un_SoftDeleteTopics( $this->forum['id'] );
		$this->permissions['TopicSoftDeleteSee']		= $this->registry->getClass('class_forums')->canSeeSoftDeletedTopics( $this->forum['id'] );
		$this->permissions['canQueue']					= $this->registry->getClass('class_forums')->canQueuePosts( $this->forum['id'] );
		
		$this->forum['permissions'] =& $this->permissions;
	}
	
	/**
	 * Builds output array for sub forums
	 *
	 * @return	array
	 */
	public function showSubForums()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$return_cat_data	= array();
		$temp_cat_data		= array();
		$member_ids			= array();
		$fid				= intval($this->request['f']);

		if ( isset( $this->registry->getClass('class_forums')->forum_cache[ $fid ] ) AND is_array( $this->registry->getClass('class_forums')->forum_cache[ $fid ] ) )
		{
			$cat_data = $this->registry->getClass('class_forums')->forum_by_id[ $fid ];
			
			foreach( $this->registry->getClass('class_forums')->forum_cache[ $fid ] as $forum_data )
			{
				$forum_data['_queued_img']		= isset($forum_data['_queued_img'] )	? $forum_data['_queued_img']	: '';
				$forum_data['_queued_info']		= isset($forum_data['_queued_info'] )	? $forum_data['_queued_info']	: '';
				$forum_data['show_subforums']	= isset($forum_data['show_subforums'] )	? $forum_data['show_subforums']	: '';
				$forum_data['last_unread']		= isset($forum_data['last_unread'] )	? $forum_data['last_unread']	: '';
				
				//-----------------------------------------
				// Get all subforum stats
				// and calculate
				//-----------------------------------------

				if ( $forum_data['redirect_on'] )
				{
					$forum_data['redirect_target']		= isset($forum_data['redirect_target']) ? $forum_data['redirect_target'] : '_parent';
					$temp_cat_data[ $forum_data['id'] ]	= $forum_data;
				}
				else
				{
					$temp_cat_data[ $forum_data['id'] ]	= $this->registry->getClass('class_forums')->forumsFormatLastinfo( $this->registry->getClass('class_forums')->forumsCalcChildren( $forum_data['id'], $forum_data ) );
				}
				
				if( $temp_cat_data[ $forum_data['id'] ]['last_poster_id'] )
				{
					$member_ids[ $forum_data['id'] ]	= $temp_cat_data[ $forum_data['id'] ]['last_poster_id'];
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
					
					foreach( $temp_cat_data as $fid => $fdata )
					{
						if( $fid != $forumId )
						{
							continue;
						}
						
						$temp_cat_data[ $fid ]	= array_merge( $_member, $fdata );
						break;
					}
				}
			}
		}
		
		if ( count( $temp_cat_data ) )
		{
			$return_cat_data[] = array( 'cat_data'   => $cat_data,
										'forum_data' => $temp_cat_data );
		}
		
		return $return_cat_data;
    }

	/**
	 * Forum view check for authentication
	 *
	 * @return	string		HTML
	 */
	public function showForum()
	{
		// are we checking for user authentication via the log in form
		// for a private forum w/password protection?
	
		if( isset( $this->request['L'] ) AND $this->request['L'] > 1 )
		{
			$this->registry->getClass('output')->showError( 'forums_why_l_gt_1', 10336 );
		}
		
		return !empty( $this->request['L'] ) ? $this->authenticateUser() : $this->renderForum();
	}
	
	/**
	 * Authenicate the log in for a password protected forum
	 *
	 * @return	@e void
	 */
	public function authenticateUser()
	{
		if( $this->request['f_password'] == "" )
		{
			$this->registry->getClass('output')->showError( 'forums_pass_blank', 10337, null, null, 403 );
		}
		
		if( $this->request['f_password'] != $this->forum['password'] )
		{
			$this->registry->getClass('output')->showError( 'forums_wrong_pass', 10338, null, null, 403 );
		}
		
		IPSCookie::set( "ipbforumpass_" . $this->forum['id'], md5( $this->request['f_password'] ) );
		
		$this->registry->getClass('output')->redirectScreen( $this->lang->words['logged_in'] , "{$this->settings['base_url']}showforum={$this->forum['id']}", $this->forum['name_seo'], 'showforum' );
	}
	
	/**
	 * Builds an array of forum data for use in the output template
	 *
	 * @return	array
	 */
	public function renderForum()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['st']	= $this->request['changefilters'] ? 0 : ( isset($this->request['st']) ? intval($this->request['st']) : 0 );
		$announce_data			= array();
		$topic_data				= array();
		$other_data				= array();
		$multi_mod_data			= array();
		$footer_filter			= array();
		$member_ids				= array();
		
		//-----------------------------------------
		// Show?
		//-----------------------------------------
		
		if ( isset( $this->request['show'] ) AND $this->request['show'] == 'sinceLastVisit' )
		{
			$this->request['prune_day'] = 200;
		}
		
		//-----------------------------------------
	    // Are we actually a moderator for this forum?
	    //-----------------------------------------
	    
		$mod	= $this->memberData['forumsModeratorData'];
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			if( ! isset( $mod[ $this->forum['id'] ] ) OR ! is_array( $mod[ $this->forum['id'] ] ) )
			{
				$this->memberData['is_mod'] = 0;
			}
		}
	    
		//-----------------------------------------
		// Announcements
		//-----------------------------------------
		
		if( is_array( $this->registry->cache()->getCache('announcements') ) and count( $this->registry->cache()->getCache('announcements') ) )
		{
			$announcements = array();
			
			foreach( $this->registry->cache()->getCache('announcements') as $announce )
			{
				$order = $announce['announce_start'] ? $announce['announce_start'].','.$announce['announce_id'] : $announce['announce_id'];
				
				if(  $announce['announce_forum'] == '*' )
				{
					$announcements[ $order ] = $announce;
				}
				else if( strstr( ','.$announce['announce_forum'].',', ','.$this->forum['id'].',' ) )
				{
					$announcements[ $order ] = $announce;
				}
			}
			
			if( count( $announcements ) )
			{
				//-----------------------------------------
				// sort by start date
				//-----------------------------------------
				
				krsort( $announcements );
				
				foreach( $announcements as $announce )
				{
					if ( $announce['announce_start'] )
					{
						$announce['announce_start'] = $this->lang->getDate( $announce['announce_start'], 'date' );
					}
					else
					{
						$announce['announce_start'] = '--';
					}
					
					$announce['announce_title'] = IPSText::stripslashes($announce['announce_title']);
					$announce['forum_id']       = $this->forum['id'];
					$announce['announce_views'] = intval($announce['announce_views']);
					$announce_data[] = $announce;

					$member_ids[ $announce['member_id'] ]	= $announce['member_id'];
				}
				
				$this->forum['_showAnnouncementsBar'] = 1;
			}
		}
		
		//-----------------------------------------
		// Read topics
		//-----------------------------------------
		
		$First   = intval($this->request['st']);
		
		//-----------------------------------------
		// Sort options
		//-----------------------------------------
		
		$cookie_prune	= IPSCookie::get( $this->forum['id'] . "_prune_day" );
		$cookie_sort	= IPSCookie::get( $this->forum['id'] . "_sort_key" );
		$cookie_sortb	= IPSCookie::get( $this->forum['id'] . "_sort_by" );
		$cookie_fill	= IPSCookie::get( $this->forum['id'] . "_topicfilter" );
		
		$prune_value	= $this->selectVariable( array( 
												1 => ! empty( $this->request['prune_day'] ) ? $this->request['prune_day'] : NULL,
												2 => !empty($cookie_prune) ? $cookie_prune : NULL,
												3 => $this->forum['prune'],
												4 => '100' )
									    );

		$sort_key		= $this->selectVariable( array(
												1 => ! empty( $this->request['sort_key'] ) ? $this->request['sort_key'] : NULL,
												2 => !empty($cookie_sort) ? $cookie_sort : NULL,
												3 => $this->forum['sort_key'],
												4 => 'last_post'		    )
									   );

		$sort_by		= $this->selectVariable( array(
												1 => ! empty( $this->request['sort_by'] ) ? $this->request['sort_by'] : NULL,
												2 => !empty($cookie_sortb) ? $cookie_sortb : NULL,
												3 => $this->forum['sort_order'],
												4 => 'Z-A'				      )
									   );
									 
		$topicfilter	= $this->selectVariable( array(
												1 => ! empty( $this->request['topicfilter'] ) ? $this->request['topicfilter'] : NULL,
												2 => !empty($cookie_fill) ? $cookie_fill : NULL,
												3 => $this->forum['topicfilter'],
												4 => 'all'				      )
									   );

		if( ! empty( $this->request['remember'] ) )
		{
			if( $this->request['prune_day'] )
			{
				IPSCookie::set( $this->forum['id'] . "_prune_day", $this->request['prune_day'] );
			}
			
			if( $this->request['sort_key'] )
			{
				IPSCookie::set( $this->forum['id'] . "_sort_key", $this->request['sort_key'] );
			}	
			
			if( $this->request['sort_by'] )
			{
				IPSCookie::set( $this->forum['id'] . "_sort_by", $this->request['sort_by'] );
			}	
			
			if( $this->request['topicfilter'] )
			{
				IPSCookie::set( $this->forum['id'] . "_topicfilter", $this->request['topicfilter'] );
			}
		}

		//-----------------------------------------
		// Figure out sort order, day cut off, etc
		//-----------------------------------------
		
		$Prune			= $prune_value < 100 ? (time() - ($prune_value * 60 * 60 * 24)) : ( ( $prune_value == 200 AND $this->memberData['member_id'] ) ? $this->memberData['last_visit'] : 0 );

		$sort_keys		=  array( 'last_post'		 => 'sort_by_date',
							   'last_poster_name'  => 'sort_by_last_poster',
							   'title'		     => 'sort_by_topic',
							   'starter_name'      => 'sort_by_poster',
							   'start_date'		=> 'sort_by_start',
							   'topic_hasattach'   => 'sort_by_attach',
							   'posts'		     => 'sort_by_replies',
							   'views'		     => 'sort_by_views',
							   
							 );

		$prune_by_day	= array( '1'    => 'show_today',
							   '5'    => 'show_5_days',
							   '7'    => 'show_7_days',
							   '10'   => 'show_10_days',
							   '15'   => 'show_15_days',
							   '20'   => 'show_20_days',
							   '25'   => 'show_25_days',
							   '30'   => 'show_30_days',
							   '60'   => 'show_60_days',
							   '90'   => 'show_90_days',
							   '100'  => 'show_all',
							   '200'  => 'show_last_visit'
							 );

		$sort_by_keys = array( 'Z-A'  => 'descending_order',
						 	   'A-Z'  => 'ascending_order',
						     );
						     
		$filter_keys  = array( 'all'    => 'topicfilter_all',
							   'open'   => 'topicfilter_open',
							   'hot'    => 'topicfilter_hot',
							   'poll'   => 'topicfilter_poll',
							   'locked' => 'topicfilter_locked',
							   'moved'  => 'topicfilter_moved',
							 );
							 
		if( $this->memberData['member_id'] )
		{
			$filter_keys['istarted'] = 'topicfilter_istarted';
			$filter_keys['ireplied'] = 'topicfilter_ireplied';
		}

		//-----------------------------------------
		// check for any form funny business by wanna-be hackers
		//-----------------------------------------
		
		if( ( ! isset( $filter_keys[$topicfilter] ) ) or ( ! isset( $sort_keys[$sort_key] ) ) or ( ! isset( $prune_by_day[$prune_value] ) ) or ( ! isset( $sort_by_keys[$sort_by] ) ) )
		{
			$this->registry->getClass('output')->showError( 'forums_bad_filter', 10339 );
	    }
	    
	    $r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';
	    
		//-----------------------------------------
		// If sorting by starter, add secondary..
		//-----------------------------------------
		$sort_key_chk = $sort_key;
		
		if( $sort_key == 'starter_name' )
		{			
			$sort_key	= "starter_name {$r_sort_by}, t.last_post DESC";
			$r_sort_by	= '';
		}
	    
	    //-----------------------------------------
	    // Additional queries?
	    //-----------------------------------------
	    
	    $add_query_array = array();
	    $add_query       = "";
	    
	    switch( $topicfilter )
	    {
	    	case 'all':
	    		break;
	    	case 'open':
	    		$add_query_array[] = "t.state='open'";
	    		break;
	    	case 'hot':
	    		$add_query_array[] = "t.state='open' AND t.posts + 1 >= ".intval($this->settings['hot_topic']);
	    		break;
	    	case 'locked':
	    		$add_query_array[] = "t.state='closed'";
	    		break;
	    	case 'moved':
	    		$add_query_array[] = "t.state='link'";
	    		break;
	    	case 'poll':
	    		$add_query_array[] = "(t.poll_state='open' OR t.poll_state=1)";
	    		break;
	    	default:
	    		break;
	    }
	    
	    if( ! $this->memberData['g_other_topics'] or $topicfilter == 'istarted' OR ( ! $this->forum['can_view_others'] AND ! $this->memberData['is_mod'] ) )
		{
		    $add_query_array[] = "t.starter_id='".$this->memberData['member_id']."'";
		}
		
		$_SQL_EXTRA		= '';
		$_SQL_APPROVED	= '';
		$_SQL_AGE_PRUNE	= '';
		
		if( count($add_query_array) )
		{
			$_SQL_EXTRA	= ' AND '. implode( ' AND ', $add_query_array );
		}
		
		//-----------------------------------------
		// Moderator?
		//-----------------------------------------
		
		$this->request['modfilter'] = isset( $this->request['modfilter'] ) ? $this->request['modfilter'] : '';
						
		if ( $this->memberData['is_mod'] )
		{
			if ( $this->request['modfilter'] == 'unapproved' )
			{
				$_SQL_APPROVED	= ' AND (' . $this->registry->class_forums->fetchTopicHiddenQuery(array('hidden'), 't.') . ' OR t.topic_queuedposts )';
			}
			elseif ( $this->permissions['TopicSoftDeleteSee'] )
			{
				if ( $this->request['modfilter'] == 'hidden' )
				{
					$_SQL_APPROVED	= ' AND (' . $this->registry->class_forums->fetchTopicHiddenQuery(array('sdeleted'), 't.') . ' OR t.topic_deleted_posts )';
				}
				else
				{
					$_SQL_APPROVED	= ' AND ' . $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden', 'sdeleted'), 't.');
				}
			}
			else
			{
				$_SQL_APPROVED	= ' AND ' . $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'hidden'), 't.');
			}
		}
		else
		{
			if ( $this->permissions['TopicSoftDeleteSee'] )
			{
				$_SQL_APPROVED	= ' AND ' . $this->registry->class_forums->fetchTopicHiddenQuery(array('visible', 'sdeleted'), 't.');
			}
			else
			{
				$_SQL_APPROVED	= ' AND ' . $this->registry->class_forums->fetchTopicHiddenQuery(array('visible'), 't.');
			}
		}
		
		if ( $Prune )
		{
			if ( $prune_value == 200 )
			{
				/* Just new content, don't show pinned, please */
				if( $this->memberData['is_mod'] AND $this->request['modfilter'] )
				{
					$_SQL_AGE_PRUNE	= " AND (t.last_post > {$Prune} OR t.approved=0)";
				}
				else
				{
					$_SQL_AGE_PRUNE	= " AND (t.last_post > {$Prune})";
				}
			}
			else
			{
				if( $this->memberData['is_mod'] AND $this->request['modfilter'] )
				{
					$_SQL_AGE_PRUNE	= " AND (t.pinned=1 or t.last_post > {$Prune} OR t.approved=0)";
				}
				else
				{
					$_SQL_AGE_PRUNE	= " AND (t.pinned=1 or t.last_post > {$Prune})";					
				}
			}
		}
		
		//-----------------------------------------
		// Query the database to see how many topics there are in the forum
		//-----------------------------------------
		
		if( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			//-----------------------------------------

			$this->DB->build( array( 'select'	=> 'COUNT(' . $this->DB->buildDistinct( 'p.topic_id' ) . ') as max',
									 'from'		=> array( 'topics' => 't' ),
									 'where'	=> "t.forum_id={$this->forum['id']} AND p.author_id=".$this->memberData['member_id'] . " AND p.new_topic=0" . $_SQL_APPROVED . $_SQL_AGE_PRUNE,
									 'add_join'	=> array( array( 'from'	=> array( 'posts' => 'p' ),
																 'where'	=> 'p.topic_id=t.tid' ) ) ) );
			$this->DB->execute();
			
			$total_possible = $this->DB->fetch();
		}
		else if ( $_SQL_EXTRA or $_SQL_AGE_PRUNE OR $this->request['modfilter'] )
		{
			$this->DB->build( array(  'select' => 'COUNT(*) as max',
									  'from'   => 'topics t',
									  'where'  => "t.forum_id=" . $this->forum['id'] . $_SQL_APPROVED . $_SQL_AGE_PRUNE . $_SQL_EXTRA ) );

			$this->DB->execute();
			
			$total_possible = $this->DB->fetch();
		}
		else 
		{
			$total_possible['max'] = $this->memberData['is_mod'] ? $this->forum['topics'] + $this->forum['queued_topics'] : $this->forum['topics'];
			
			if ( $this->permissions['TopicSoftDeleteSee'] AND $this->forum['deleted_topics'] )
			{
				$total_possible['max'] += intval( $this->forum['deleted_topics'] );
			}
			
			$Prune = 0;
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------
		
		$_extraStuff	= '';
		
		if( $this->request['modfilter'] )
		{
			$_extraStuff	.= "&amp;modfilter=" . $this->request['modfilter'];
		}
		
		$this->forum['SHOW_PAGES'] = $this->registry->getClass('output')->generatePagination( array( 'totalItems'			=> $total_possible['max'],
																									 'itemsPerPage'			=> $this->settings['display_max_topics'],
																									 'currentStartValue'	=> $this->request['st'],
																									 'seoTitle'				=> $this->forum['name_seo'],
																									 'disableSinglePage'	=> false,
																									 'baseUrl'				=> "showforum=".$this->forum['id']."&amp;prune_day={$prune_value}&amp;sort_by={$sort_by}&amp;sort_key={$sort_key_chk}&amp;topicfilter={$topicfilter}{$_extraStuff}" )	);

		//-----------------------------------------
		// Start printing the page
		//-----------------------------------------
		
		$other_data = array( 'forum_data'		=> $this->forum,
							 'hasMore'			=> ( $this->request['st'] + $this->settings['display_max_topics'] > $total_possible['max'] ) ? false : true,
							 'can_edit_topics'	=> $this->can_edit_topics,
							 'can_open_topics'	=> $this->can_open_topics,
							 'can_close_topics'	=> $this->can_close_topics,
							 'can_move_topics'	=> $this->can_move_topics );

		$total_topics_printed = 0;
		
		//-----------------------------------------
		// Get main topics
		//-----------------------------------------
		
		$topic_array = array();
		$topic_ids   = array();
		$topic_sort  = "";
		
		//-----------------------------------------
		// Cut off?
		//-----------------------------------------
		
		$parse_dots = 1;
		
		if( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			// No point in getting dots again...
			//-----------------------------------------
			
			$parse_dots = 0;
			
			$_joins	= array( array( 'select'	=> 't.*',
							 		'from'		=> array( 'posts' => 'p' ),
									 'where'	=> 'p.topic_id=t.tid AND p.author_id=' . $this->memberData['member_id'] ) );

			if ( $this->settings['tags_enabled'] AND !$this->forum['bw_disable_tagging'] )
			{
				$_joins[]	= $this->registry->tags->getCacheJoin( array( 'meta_id_field' => 't.tid' ) );
			}
			
			// For some reason, mySQL doesn't like the distinct + t.* being in reverse order...
			$this->DB->build( array( 'select'	=> $this->DB->buildDistinct( 'p.author_id' ),
									 'from'		=> array( 'topics' => 't' ),
									 'where'	=> "t.forum_id=" . $this->forum['id'] . " AND t.pinned IN (0,1)" . $_SQL_APPROVED . $_SQL_AGE_PRUNE . " AND p.new_topic=0",
									 'order'	=> "t.pinned desc,{$topic_sort} t.{$sort_key} {$r_sort_by}",
									 'limit'	=> array( intval($First), intval($this->settings['display_max_topics']) ),
									 'add_join'	=> $_joins ) );
			$this->DB->execute();
		}
		else
		{
			$this->DB->build( array( 'select'   => 't.*',
									 'from'     => array( 'topics' =>  't' ),
									 'where'    => "t.forum_id=" . $this->forum['id'] . " AND t.pinned IN (0,1)" . $_SQL_APPROVED . $_SQL_AGE_PRUNE . $_SQL_EXTRA,
									 'order'    => 't.pinned DESC, '.$topic_sort.' t.'.$sort_key .' '. $r_sort_by,
									 'limit'    => array( intval($First), $this->settings['display_max_topics'] ),
									 'add_join' => ( $this->settings['tags_enabled'] AND !$this->forum['bw_disable_tagging'] ) ? array( $this->registry->tags->getCacheJoin( array( 'meta_id_field' => 't.tid' ) ) ) : array() 
		 					)		);
			$this->DB->execute();
		}
			
		while( $t = $this->DB->fetch() )
		{
			$topic_array[ $t['tid'] ] = $t;
			$topic_ids[ $t['tid'] ]   = $t['tid'];
			
			if ( $t['last_poster_id'] )
			{
				$member_ids[ $t['last_poster_id'] ]	= $t['last_poster_id'];
			}
			
			if ( $t['starter_id'] )
			{
				$member_ids[ $t['starter_id'] ]	= $t['starter_id'];
			}
		}
			
		ksort( $topic_ids );
		
		//-----------------------------------------
		// Are we dotty?
		//-----------------------------------------
		
		if( ( $this->settings['show_user_posted'] == 1 ) and ( $this->memberData['member_id'] ) and ( count($topic_ids) ) and ( $parse_dots ) )
		{
			$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' );

			$this->DB->build( array( 'select' => 'author_id, topic_id',
									 'from'   => 'posts',
									 'where'  => $_queued . ' AND author_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', $topic_ids ) . ')' )	);
									  
			$this->DB->execute();
			
			while( $p = $this->DB->fetch() )
			{
				if ( is_array( $topic_array[ $p['topic_id'] ] ) )
				{
					$topic_array[ $p['topic_id'] ]['author_id'] = $p['author_id'];
				}
			}
		}

		//-----------------------------------------
		// Get needed members
		//-----------------------------------------
		
		if( count($member_ids) )
		{
			$_members	= IPSMember::load( $member_ids );
			
			//-----------------------------------------
			// Add member data to announcements
			//-----------------------------------------
			
			$new_announces	= array();
			
			foreach( $announce_data as $announce )
			{
				$announce	= array_merge( $announce, IPSMember::buildDisplayData( $_members[ $announce['member_id'] ] ) );
				
				$new_announces[]	= $announce;
			}
			
			$announce_data	= $new_announces;
		}
		
		//-----------------------------------------
		// Show meh the topics!
		//-----------------------------------------
				
		$adCodeSet	= false;
		
		foreach( $topic_array as $topic )
		{
			/* Add member */
			if( $topic['last_poster_id'] )
			{
				$topic	= array_merge( IPSMember::buildDisplayData( $_members[ $topic['last_poster_id'] ] ), $topic );
			}
			else
			{
				$topic	= array_merge( IPSMember::buildProfilePhoto( array() ), $topic );
			}
			
			if ( $topic['starter_id'] )
			{
				$topic['_starter'] = $_members[ $topic['starter_id'] ];
			}
				
			/* AD Code */
			if( $this->registry->getClass('IPSAdCode')->userCanViewAds() && ! $adCodeSet )
			{
				$topic['_adCode'] = $this->registry->getClass('IPSAdCode')->getAdCode('ad_code_forum_view_topic_code');
				if( $topic['_adCode'] )
				{
					$adCodeSet = true;
				}
			}
			
			if ( $topic['pinned'] )
			{
				$this->pinned_topic_count++;
			}

			$topic_data[ $topic['tid'] ] = $this->renderEntry( $topic );
			
			$total_topics_printed++;
		}
		
		//-----------------------------------------
		// Finish off the rest of the page  $filter_keys[$topicfilter]))
		//-----------------------------------------
		
		$sort_by_html	= "";
		$sort_key_html	= "";
		$prune_day_html	= "";
		$filter_html	= "";
		
		foreach( $sort_by_keys as $k => $v )
		{
			$sort_by_html   .= $k == $sort_by      ? "<option value='$k' selected='selected'>{$this->lang->words[ $sort_by_keys[ $k ] ]}</option>\n"
											       : "<option value='$k'>{$this->lang->words[ $sort_by_keys[ $k ] ]}</option>\n";
		}
	
		foreach( $sort_keys as  $k => $v )
		{
			$sort_key_html  .= $k == $sort_key_chk ? "<option value='$k' selected='selected'>{$this->lang->words[ $sort_keys[ $k ] ]}</option>\n"
											       : "<option value='$k'>{$this->lang->words[ $sort_keys[ $k ] ]}</option>\n";
		}
		
		foreach( $prune_by_day as  $k => $v )
		{
			$prune_day_html .= $k == $prune_value  ? "<option value='$k' selected='selected'>{$this->lang->words[ $prune_by_day[ $k ] ]}</option>\n"
												   : "<option value='$k'>{$this->lang->words[ $prune_by_day[ $k ] ]}</option>\n";
		}
		
		foreach( $filter_keys as  $k => $v )
		{
			$filter_html    .= $k == $topicfilter  ? "<option value='$k' selected='selected'>{$this->lang->words[ $filter_keys[ $k ] ]}</option>\n"
												   : "<option value='$k'>{$this->lang->words[ $filter_keys[ $k ] ]}</option>\n";
		}
	
		$footer_filter['sort_by']      = $sort_key_html;
		$footer_filter['sort_order']   = $sort_by_html;
		$footer_filter['sort_prune']   = $prune_day_html;
		$footer_filter['topic_filter'] = $filter_html;
		
		if( $this->memberData['is_mod'] )
		{
			$count = 0;
			$other_pages = 0;
			
			if( $this->request['selectedtids'] != "" )
			{
				$tids = explode( ",",$this->request['selectedtids'] );
				
				if( is_array( $tids ) AND count( $tids ) )
				{
					foreach( $tids as $tid )
					{
						if( $tid != '' )
						{
							if( ! isset($topic_array[ $tid ]) )
							{
								$other_pages++;
							}
							
							$count++;
						}
					}
				}
			}
			
			$this->lang->words['f_go'] .= " ({$count})";
			
			if( $other_pages )
			{
				$this->lang->words['f_go'] .= " ({$other_pages} " . $this->lang->words['jscript_otherpage'] . ")";
			}
		}
	
		//-----------------------------------------
		// Multi-moderation?
		//-----------------------------------------
		
		if( $this->memberData['is_mod'] )
		{
			$mm_array = $this->registry->getClass('class_forums')->getMultimod( $this->forum['id'] );
			
			if ( is_array( $mm_array ) and count( $mm_array ) )
			{
				foreach( $mm_array as $m )
				{
					$multi_mod_data[] = $m;
				}
			}
		}
		
		//-----------------------------------------
		// Need to update topics?
		//-----------------------------------------
		
		if( count( $this->update_topics_open ) )
		{
			$this->DB->update( 'topics', array( 'state' => 'open' ), 'tid IN ('.implode( ",", $this->update_topics_open ) .')' );
		}
		
		if( count( $this->update_topics_close ) )
		{
			$this->DB->update( 'topics', array( 'state' => 'closed' ), 'tid IN ('.implode( ",", $this->update_topics_close ) .')' );
		}
		
		/* Got soft delete tids? */
		if ( is_array( $this->_sdTids ) AND count( $this->_sdTids ) )
		{
			$other_data['sdData'] = IPSDeleteLog::fetchEntries( $this->_sdTids, 'topic', false );
		}
		
		/* Fetch follow data */
		$other_data['follow_data'] = $this->_like->render( 'summary', $this->forum['id'] );
		
		return array( 'announce_data'	=> $announce_data,
					  'topic_data'		=> $topic_data,
					  'other_data'		=> $other_data,
					  'multi_mod_data'	=> $multi_mod_data,
					  'footer_filter'	=> $footer_filter,
					  'active_users'	=> $this->_generateActiveUserData()
					 );
    }
    
	/**
	 * Returns a list of the active user in the forum
	 *
	 * @return	@e array
	 */
	protected function _generateActiveUserData()
	{
		$users = array();
		
		/* Do we actually want active users? */
		if ( empty($this->settings['no_au_forum']) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . "sources/classes/session/api.php", 'session_api' );
			$sessionClass = new $classToLoad( $this->registry );
			
			$users = $sessionClass->getUsersIn( 'forums', array( 'skipParsing' => true, 'addWhere' => array( "s.location_2_type='forum'", "s.location_2_id={$this->forum['id']}" ) ) );
			
			/* Got any posting classes to add? */
			if ( ! empty($users['stats']['members']) && is_array($users['rows']['members']) && count($users['rows']['members']) )
			{
				foreach( $users['rows']['members'] as $sid => $sdata )
				{
					if ( isset($users['names'][ $sid ]) && $sdata['member_id'] != $this->memberData['member_id'] && $sdata['current_module'] == 'post' )
					{
						$users['names'][ $sid ] = "<span class='activeuserposting'>" . $users['names'][ $sid ] . "</span>";
					}
				}
			}
		}
		
		return $users;
	}
	
	/**
	 * Parase Topic Data
	 *
	 * @param	array	$topic				Topic data
	 * @param	bool	$last_time_default	Use default "last read time"
	 * @return	array
	 */
	public function parseTopicData( $topic, $last_time_default=true )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		/* Update perms */
		$this->permissions['TopicSoftDelete'] = $this->registry->getClass('class_forums')->canSoftDeleteTopics( $this->forum['id'], $topic );
		
		$topic = $this->registry->topics->parseTopicForLineEntry( $topic );
		
		/* Collect TIDS of soft deleted topics */
		if ( $topic['_isDeleted'] AND $this->permissions['TopicSoftDeleteSee'] )
		{
			$this->_sdTids[] = $topic['tid'];
		}
	
		//-----------------------------------------
		// Need to update this topic?
		//-----------------------------------------
		
		if ( $topic['state'] == 'open' )
		{
			if( !$topic['topic_open_time'] OR $topic['topic_open_time'] < $topic['topic_close_time'] )
			{
				if ( $topic['topic_close_time'] AND ( $topic['topic_close_time'] <= time() AND ( time() >= $topic['topic_open_time'] OR !$topic['topic_open_time'] ) ) )
				{
					$topic['state'] = 'closed';
					
					$this->update_topics_close[] = $topic['real_tid'];
				}
			}
			else if( $topic['topic_open_time'] OR $topic['topic_open_time'] > $topic['topic_close_time'] )
			{
				if ( $topic['topic_close_time'] AND ( $topic['topic_close_time'] <= time() AND time() <= $topic['topic_open_time'] ) )
				{
					$topic['state'] = 'closed';
					
					$this->update_topics_close[] = $topic['real_tid'];
				}
			}				
		}
		else if ( $topic['state'] == 'closed' )
		{
			if( !$topic['topic_close_time'] OR $topic['topic_close_time'] < $topic['topic_open_time'] )
			{
				if ( $topic['topic_open_time'] AND ( $topic['topic_open_time'] <= time() AND ( time() >= $topic['topic_close_time'] OR !$topic['topic_close_time'] ) ) )
				{
					$topic['state'] = 'open';
					
					$this->update_topics_open[] = $topic['real_tid'];
				}
			}
			else if( $topic['topic_close_time'] OR $topic['topic_close_time'] > $topic['topic_open_time'] )
			{
				if ( $topic['topic_open_time'] AND ( $topic['topic_open_time'] <= time() AND time() <= $topic['topic_close_time'] ) )
				{
					$topic['state'] = 'open';
					
					$this->update_topics_open[] = $topic['real_tid'];
				}
			}					
		}
		
		return $topic;
	}
	
	/**
	 * Returns an array of topic data
	 *
	 * @param	array 	Topic entry
	 * @return	array
	 */
	public function renderEntry( $topic )
	{
		$topic = $this->parseTopicData( $topic );
		
		$topic['pages']				= isset($topic['pages'])		? $topic['pages']		: '';
		$topic['prefix']			= isset($topic['prefix'])		? $topic['prefix']		: '';
		$topic['attach_img']		= isset($topic['attach_img'])	? $topic['attach_img']	: '';
		$topic['_hasqueued']		= isset($topic['_hasqueued'])	? $topic['_hasqueued']	: '';
		$topic['tidon']				= isset($topic['tidon'])		? $topic['tidon']		: 0;
		
		/* Collect TIDS of soft deleted topics */
		if ( $topic['_isDeleted'] AND $this->permissions['TopicSoftDeleteSee'] )
		{
			$this->_sdTids[] = $topic['tid'];
		}
		
		
		if ( $topic['pinned'] == 1 )
		{
			$topic['prefix'] = $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->lang->words['pre_pinned'] );
		}
		
		return $topic;
	}
	
	/**
	 * Given an array of possible variables, the first one found is returned
	 *
	 * @param	array 	Mixed variables
	 * @return	mixed 	First variable from the array
	 * @since	2.0
	 */
    public static function selectVariable($array)
    {
    	if ( !is_array($array) ) return -1;

    	ksort($array);

    	$chosen = -1;

    	foreach ($array as $v)
    	{
    		if ( isset($v) )
    		{
    			$chosen = $v;
    			break;
    		}
    	}

    	return $chosen;
    }
}