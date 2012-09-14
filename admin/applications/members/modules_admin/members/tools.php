<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member validation, locked and banned queues
 * Last Updated: $Date: 2012-06-06 05:12:48 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10870 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_members_members_tools extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var		object			Skin templates
	 */
	protected $html;

	/**
	 * Shortcut for url
	 *
	 * @var		string			URL shortcut
	 */
	protected $form_code;

	/**
	 * Shortcut for url (javascript)
	 *
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------

		$this->html			= $this->registry->output->loadTemplate('cp_skin_tools');
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ) );

		$this->form_code	= $this->html->form_code	= '&amp;module=members&amp;section=tools';
		$this->form_code_js	= $this->html->form_code_js	= '&module=members&section=tools';

		switch( $this->request['do'] )
		{
			case 'show_all_ips':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_ip' );
				$this->_showIPs();
			break;

			case 'learn_ip':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_ip' );
				$this->_learnIP();
			break;

			case 'do_validating':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_validating', 'members', 'members' );
				$this->_manageValidating();
			break;
			
			case 'unappemail':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_validating', 'members', 'members' );
				$this->_emailUnapprove();
			break;

			case 'do_locked':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_locked', 'members', 'members' );
				$this->_unlock();
			break;

			case 'do_spam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_spam', 'members', 'members' );
				$this->_unSpam();
			break;

			case 'do_banned':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_banned', 'members', 'members' );
				$this->_unban();
			break;
			
			case 'do_incomplete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_incomplete', 'members', 'members' );
				$this->_doIncomplete();
			break;

			case 'deleteMessages':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_delete_pms' );
				$this->_deleteMessages();
			break;

			case 'merge':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'members_merge' );
				$this->_mergeForm();
			break;

			case 'doMerge':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'members_merge' );
				$this->_completeMerge();
			break;

			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'membertools_ip' );
				$this->_toolsIndex();
			break;
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();

	}

	/**
	 * Rebuild the stats
	 *
	 * @return	bool
	 * @author	Brandon Farber
	 */
	public function rebuildStats()
	{
		$stats	= $this->cache->getCache('stats');

		$topics = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as tcount',
															 	 'from'   => 'topics',
											 				 	 'where'  => $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) ) );

		$posts  = $this->DB->buildAndFetch( array( 'select' => 'SUM(posts) as replies',
																 'from'   => 'topics',
																 'where'  => $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) ) );

		$stats['total_topics']  = $topics['tcount'];
		$stats['total_replies'] = $posts['replies'];

		$r = $this->DB->buildAndFetch( array( 'select' => 'count(member_id) as members', 'from' => 'members', 'where' => "member_group_id <> '{$this->settings['auth_group']}'" ) );

		$stats['mem_count'] = intval($r['members']);
		
		$stats = array_merge( $stats, IPSMember::resetLastRegisteredMember( true ) );

		$this->cache->setCache( 'stats', $stats, array( 'array' => 1 ) );

		return true;
	}

	/**
	 * Show the form to confirm merging a member
	 *
	 * @return	@e void	[Outputs to screen]
	 * @author	Brandon Farber
	 */
	protected function _mergeForm()
	{
		$id	= intval($this->request['member_id']);

		if( !$id )
		{
			$this->_toolsIndex( $this->lang->words['no_merge_id'] );
			return false;
		}

		$member	= IPSMember::load( $id );

		$this->registry->output->html .= $this->html->mergeStart( $member );
	}

	/**
	 * Merge two members
	 *
	 * @return	@e void	[Redirects to member account]
	 * @author	Brandon Farber
	 */
	protected function _completeMerge()
	{
		if( !$this->request['confirm'] )
		{
			$member = IPSMember::load( $this->request['member_id'] );
			
			//-----------------------------------------
			// Load member
			//-----------------------------------------
			
			$newMember = NULL;
			$_newMember = NULL;
			
			/* Name */
			if ( $this->request['name'] )
			{
				$newMember = IPSMember::load( $this->request['name'], '', 'displayname' );
				$_newMember = $newMember['member_id'];
			}
					
			/* Email */
			if ( $this->request['email'] )
			{
				$newMember = IPSMember::load( $this->request['email'], '', 'email' );
				
				if ( $_newMember !== NULL and $_newMember != $newMember['member_id'] )
				{
					$this->registry->output->global_error	= $this->lang->words['err_transfer_badmulti'];
					$this->_mergeForm();
					return false;
				}
				
				$_newMember = $newMember['member_id'];
			}
			
			/* ID */
			if ( $this->request['target_id'] )
			{
				$newMember = IPSMember::load( intval( $this->request['target_id'] ), '', 'id' );
				
				if ( $_newMember !== NULL and $_newMember != $newMember['member_id'] )
				{
					$this->registry->output->global_error	= $this->lang->words['err_transfer_badmulti'];
					$this->_mergeForm();
					return false;
				}
				
				$_newMember = $newMember['member_id'];
			}
					
			if ( !$newMember['member_id'] )
			{
				$this->registry->output->global_error	= $this->lang->words['no_merge_id'];
				$this->_mergeForm();
				return false;
			}

			$member2 = $newMember;

			if( !$member['member_id'] OR !$member2['member_id'] )
			{
				$this->registry->output->global_error	= $this->lang->words['no_merge_id'];
				$this->_mergeForm();
				return false;
			}

			//-----------------------------------------
			// Output
			//-----------------------------------------

			$this->registry->output->html .= $this->html->mergeConfirm( $member, $newMember );
		}
		else
		{
			$member			= IPSMember::load( $this->request['member_id'] );
			$member2		= IPSMember::load( $this->request['member_id2'] );

			if( !$member['member_id'] OR !$member2['member_id'] )
			{
				$this->registry->output->global_error	= $this->lang->words['no_merge_id'];
				$this->_mergeForm();
				return false;
			}

			//-----------------------------------------
			// Take care of forum stuff
			//-----------------------------------------

			$this->DB->update( 'posts'					, array( 'author_name'  => $member['members_display_name'], 'author_id'  => $member['member_id'] ), "author_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'starter_name' => $member['members_display_name'], 'seo_first_name' => $member['members_seo_name'], 'starter_id' => $member['member_id'] ), "starter_id=" . $member2['member_id'] );
			$this->DB->update( 'topics'					, array( 'last_poster_name' => $member['members_display_name'], 'seo_last_name' => $member['members_seo_name'], 'last_poster_id' => $member['member_id'] ), "last_poster_id=" . $member2['member_id'] );
			$this->DB->update( 'announcements'			, array( 'announce_member_id' => $member['member_id'] ), "announce_member_id=" . $member2['member_id'] );
			$this->DB->update( 'attachments'			, array( 'attach_member_id' => $member['member_id'] ), "attach_member_id=" . $member2['member_id'] );
			$this->DB->update( 'polls'					, array( 'starter_id' => $member['member_id'] ), "starter_id=" . $member2['member_id'] );
			$this->DB->update( 'topic_ratings'			, array( 'rating_member_id' => $member['member_id'] ), "rating_member_id=" . $member2['member_id'] );
			$this->DB->update( 'moderators'				, array( 'member_id' => $member['member_id'] ) , "member_id=" . $member2['member_id'] );
			$this->DB->update( 'forums'					, array( 'last_poster_name' => $member['members_display_name'], 'seo_last_name' => $member['members_seo_name'], 'last_poster_id' => $member['member_id'] ), "last_poster_id=" . $member2['member_id'] );

			$this->DB->update( 'core_share_links_log'	, array( 'log_member_id' => $member['member_id'] ), "log_member_id=" . $member2['member_id'] );
			$this->DB->update( 'core_soft_delete_log'	, array( 'sdl_obj_member_id' => $member['member_id'] ), "sdl_obj_member_id=" . $member2['member_id'] );
			$this->DB->update( 'rss_import'				, array( 'rss_import_mid' => $member['member_id'] ), "rss_import_mid=" . $member2['member_id'] );
			$this->DB->update( 'core_tags'				, array( 'tag_member_id' => $member['member_id'] ), "tag_member_id=" . $member2['member_id'] );
			
			/* Update archived posts */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer.php', 'classes_archive_writer' );
			$archiveWriter = new $classToLoad();
			$archiveWriter->setApp('forums');
			
			$archiveWriter->update( array( 'archive_author_id' => $member['member_id'], 'archive_author_name' => $member['members_display_name'] ), 'archive_author_id=' . $member2['member_id'] );
		
			//-----------------------------------------
			// Clean up profile stuff
			//-----------------------------------------

			$this->DB->update( 'profile_portal_views'	, array( 'views_member_id' => $member['member_id'] ), "views_member_id=" . $member2['member_id'] );
			$this->DB->update( 'members_warn_logs'		, array( 'wl_member' => $member['member_id'] ), "wl_member=" . $member2['member_id'] );
			$this->DB->update( 'members_warn_logs'		, array( 'wl_moderator' => $member['member_id'] ), "wl_moderator=" . $member2['member_id'] );

			$this->DB->update( 'dnames_change'			, array( 'dname_member_id' => $member['member_id'] ), "dname_member_id=" . $member2['member_id'] );
			$this->DB->update( 'mobile_notifications'	, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			$this->DB->update( 'inline_notifications'	, array( 'notify_to_id' => $member['member_id'] ), "notify_to_id=" . $member2['member_id'] );
			$this->DB->update( 'inline_notifications'	, array( 'notify_from_id' => $member['member_id'] ), "notify_from_id=" . $member2['member_id'] );
			
			$this->DB->update( 'member_status_actions'	, array( 'action_member_id' => $member['member_id'] ), "action_member_id=" . $member2['member_id'] );
			$this->DB->update( 'member_status_actions'	, array( 'action_status_owner' => $member['member_id'] ), "action_status_owner=" . $member2['member_id'] );
			$this->DB->update( 'member_status_replies'	, array( 'reply_member_id' => $member['member_id'] ), "reply_member_id=" . $member2['member_id'] );
			$this->DB->update( 'member_status_updates'	, array( 'status_member_id' => $member['member_id'] ), "status_member_id=" . $member2['member_id'] );
			
			//-----------------------------------------
			// Update admin stuff
			//-----------------------------------------

			$this->DB->update( 'upgrade_history'		, array( 'upgrade_mid' => $member['member_id'] ), "upgrade_mid=" . $member2['member_id'] );
			$this->DB->update( 'admin_logs'				, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			$this->DB->update( 'error_logs'				, array( 'log_member' => $member['member_id'] ), "log_member=" . $member2['member_id'] );
			$this->DB->update( 'moderator_logs'			, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			
			$this->DB->update( 'rc_comments'			, array( 'comment_by' => $member['member_id'] ), "comment_by=" . $member2['member_id'] );
			$this->DB->update( 'rc_reports'				, array( 'report_by' => $member['member_id'] ), "report_by=" . $member2['member_id'] );
			$this->DB->update( 'rc_reports_index'		, array( 'updated_by' => $member['member_id'] ), "updated_by=" . $member2['member_id'] );
			$this->DB->update( 'rc_reports_index'		, array( 'exdat1' => $member['member_id'] ), "seotemplate='showuser' AND exdat1=" . $member2['member_id'] );
			$this->DB->update( 'reputation_cache'		, array( 'type_id' => $member['member_id'] ), "type='member' AND type_id=" . $member2['member_id'] );
			$this->DB->update( 'reputation_index'		, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );

			//-----------------------------------------
			// Fix up member messages...
			//-----------------------------------------

			$this->DB->update( 'message_posts'			, array( 'msg_author_id' => $member['member_id'] ), 'msg_author_id=' . $member2['member_id'] );
			$this->DB->update( 'message_topics'			, array( 'mt_starter_id' => $member['member_id'] ), 'mt_starter_id=' . $member2['member_id'] );
			$this->DB->update( 'message_topics'			, array( 'mt_to_member_id' => $member['member_id'] ), 'mt_to_member_id=' . $member2['member_id'] );

			//-----------------------------------------
			// Stuff that can't have duplicates
			//-----------------------------------------

			//-----------------------------------------
			// Likes - also invalidates likes cache
			//-----------------------------------------
			
			/* Followed stuffs */
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$like = classes_like::bootstrap( 'core', 'default' );
		
			$like->updateMemberId( $member2['member_id'], $member['member_id'] );
			
			//-----------------------------------------
			// Poll votes
			//-----------------------------------------

			$voters		= array();

			$this->DB->build( array( 'select' => 'tid', 'from' => 'voters', 'where' => 'member_id=' . $member['member_id'] ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				$voters[]	= $r['tid'];
			}

			if( count($voters) )
			{
				$this->DB->update( 'voters'					, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] . " AND tid NOT IN(" . implode( ',', $voters ) . ")" );
			}
			else
			{
				$this->DB->update( 'voters'					, array( 'member_id' => $member['member_id'] ), "member_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Profile ratings
			//-----------------------------------------

			$ratingsFor		= array();
			$ratingsGot		= array();

			$this->DB->build( array( 'select' => 'rating_by_member_id,rating_for_member_id', 'from' => 'profile_ratings', 'where' => 'rating_by_member_id=' . $member['member_id'] . ' OR rating_for_member_id=' . $member['member_id'] ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				if( $r['rating_by_member_id'] == $member['member_id'] )
				{
					$ratingsFor[]	= $r['rating_for_member_id'];
				}

				if( $r['rating_for_member_id'] == $member['member_id'] )
				{
					$ratingsGot[]	= $r['rating_by_member_id'];
				}
			}

			if( count($ratingsFor) )
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_by_member_id' => $member['member_id'] ), "rating_by_member_id=" . $member2['member_id'] . " AND rating_for_member_id NOT IN(" . implode( ',', $ratingsFor ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_by_member_id' => $member['member_id'] ), "rating_by_member_id=" . $member2['member_id'] );
			}

			if( count($ratingsGot) )
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_for_member_id' => $member['member_id'] ), "rating_for_member_id=" . $member2['member_id'] . " AND rating_by_member_id NOT IN(" . implode( ',', $ratingsGot ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_ratings'		, array( 'rating_for_member_id' => $member['member_id'] ), "rating_for_member_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Profile friends
			//-----------------------------------------

			$myFriends		= array();
			$friendsMy		= array();

			$this->DB->build( array( 'select' => 'friends_member_id,friends_friend_id', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $member['member_id'] . ' OR friends_friend_id=' . $member['member_id'] ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				if( $r['friends_member_id'] == $member['member_id'] )
				{
					$myFriends[]	= $r['friends_friend_id'];
				}

				if( $r['friends_friend_id'] == $member['member_id'] )
				{
					$friendsMy[]	= $r['friends_member_id'];
				}
			}

			if( count($myFriends) )
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_member_id' => $member['member_id'] ), "friends_member_id=" . $member2['member_id'] . " AND friends_friend_id NOT IN(" . implode( ',', $myFriends ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_member_id' => $member['member_id'] ), "friends_member_id=" . $member2['member_id'] );
			}

			if( count($friendsMy) )
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_friend_id' => $member['member_id'] ), "friends_friend_id=" . $member2['member_id'] . " AND friends_member_id NOT IN(" . implode( ',', $friendsMy ) . ")" );
			}
			else
			{
				$this->DB->update( 'profile_friends'		, array( 'friends_friend_id' => $member['member_id'] ), "friends_friend_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Ignored users
			//-----------------------------------------

			$myIgnored		= array();
			$ignoredMe		= array();

			$this->DB->build( array( 'select' => 'ignore_owner_id,ignore_ignore_id', 'from' => 'ignored_users', 'where' => 'ignore_owner_id=' . $member['member_id'] . ' OR ignore_ignore_id=' . $member['member_id'] ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				if( $r['ignore_owner_id'] == $member['member_id'] )
				{
					$myIgnored[]	= $r['ignore_ignore_id'];
				}

				if( $r['ignore_ignore_id'] == $member['member_id'] )
				{
					$ignoredMe[]	= $r['ignore_owner_id'];
				}
			}

			if( count($myIgnored) )
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_owner_id' => $member['member_id'] ), "ignore_owner_id=" . $member2['member_id'] . " AND ignore_ignore_id NOT IN(" . implode( ',', $myIgnored ) . ")" );
			}
			else
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_owner_id' => $member['member_id'] ), "ignore_owner_id=" . $member2['member_id'] );
			}

			if( count($ignoredMe) )
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_ignore_id' => $member['member_id'] ), "ignore_ignore_id=" . $member2['member_id'] . " AND ignore_owner_id NOT IN(" . implode( ',', $ignoredMe ) . ")" );
			}
			else
			{
				$this->DB->update( 'ignored_users'		, array( 'ignore_ignore_id' => $member['member_id'] ), "ignore_ignore_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Message topic mapping
			//-----------------------------------------

			$pms		= array();

			$this->DB->build( array( 'select' => 'map_topic_id', 'from' => 'message_topic_user_map', 'where' => 'map_user_id=' . $member['member_id'] ) );
			$this->DB->execute();

			while( $r = $this->DB->fetch() )
			{
				$pms[]		= $r['map_topic_id'];
			}

			if( count($pms) )
			{
				$this->DB->update( 'message_topic_user_map'	, array( 'map_user_id' => $member['member_id'] ), "map_user_id=" . $member2['member_id'] . " AND map_topic_id NOT IN(" . implode( ',', $pms ) . ")" );
			}
			else
			{
				$this->DB->update( 'message_topic_user_map'	, array( 'map_user_id' => $member['member_id'] ), 'map_user_id=' . $member2['member_id'] );
			}

			//-----------------------------------------
			// Admin permissions
			//-----------------------------------------

			$count	= $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $member['member_id'] ) );

			if( !$count['row_id'] )
			{
				$this->DB->update( 'admin_permission_rows'	, array( 'row_id' => $member['member_id'] ), "row_id_type='member' AND row_id=" . $member2['member_id'] );
			}

			//-----------------------------------------
			// Member Sync
			//-----------------------------------------

			try
			{
				IPSMember::save( $member['member_id'], array(	'core'				=> array(
																			'posts'			=> ($member['posts'] + $member2['posts']),
																			'warn_level'	=> ($member['warn_level'] + $member2['warn_level']),
																			'warn_lastwarn'	=> ($member2['warn_lastwarn'] > $member['warn_lastwarn']) ? $member2['warn_lastwarn'] : $member['warn_lastwarn'] ,
																			'last_post'		=> ($member2['last_post'] > $member['last_post']) ? $member2['last_post'] : $member['last_post'],
																			'last_visit'	=> ($member2['last_visit'] > $member['last_visit']) ? $member2['last_visit'] : $member['last_visit'],
																							),
   														  		'extendedProfile'	=> array(
   														  					'pp_reputation_points'	=> ($member['pp_reputation_points'] + $member2['pp_reputation_points']),
																	 						),
								)							);
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			IPSLib::runMemberSync( 'onMerge', $member, $member2 );

			//-----------------------------------------
			// Delete member 2
			//-----------------------------------------

			IPSMember::remove( $member2['member_id'], false );

			//-----------------------------------------
			// Get current stats...
			//-----------------------------------------

			$this->cache->rebuildCache( 'stats', 'global' );
			$this->cache->rebuildCache( 'moderators', 'forums' );
			$this->cache->rebuildCache( 'announcements', 'forums' );

			//-----------------------------------------
			// Admin logs
			//-----------------------------------------

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['merged_accounts_log'], $member2['members_display_name'], $member['members_display_name'] ) );

			//-----------------------------------------
			// Redirect
			//-----------------------------------------

			$this->registry->output->global_message	= $this->lang->words['merged_members'];
			
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . "module=members&amp;section=members&amp;do=viewmember&amp;member_id=" . $member['member_id'] );
		}
	}

	/**
	 * Delete all private messages from a member
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _deleteMessages()
	{
		if( !$this->request['confirm'] )
		{
			$countTopics	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'message_topics', 'where' => 'mt_is_deleted=0 AND mt_starter_id=' . intval($this->request['member_id']) ) );
			$countReplies	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'message_posts', 'where' => 'msg_is_first_post=0 AND msg_author_id=' . intval($this->request['member_id']) ) );

			$member			= IPSMember::load( $this->request['member_id'] );

			//-----------------------------------------
			// Output
			//-----------------------------------------

			$this->registry->output->html .= $this->html->deleteMessagesWrapper( $member, $countTopics, $countReplies );
		}
		else
		{
			//-----------------------------------------
			// Deleting anything?
			//-----------------------------------------
			
			if( !$this->request['topics'] AND !$this->request['replies'] )
			{
				$this->registry->output->showError( $this->lang->words['no_msngr_sel_del'], 11247.22 );
			}
			
			//-----------------------------------------
			// Get messenger lib
			//-----------------------------------------

			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
			$messengerLibrary	= new $classToLoad( $this->registry );

			if( $this->request['topics'] )
			{
				//-----------------------------------------
				// Get topic ids
				//-----------------------------------------

				$messages	= array();

				$this->DB->build( array(
										'select'	=> 'mt_id',
										'from'		=> 'message_topics',
										'where'		=> 'mt_is_deleted=0 AND mt_starter_id=' . intval($this->request['member_id']),
								)		);
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$messages[] = $r['mt_id'];
				}

				//-----------------------------------------
				// Delete topics
				//-----------------------------------------

				if( count($messages) )
				{
					$messengerLibrary->deleteTopics( $this->request['member_id'], $messages, null, true );
				}
			}

			if( $this->request['replies'] )
			{
				//-----------------------------------------
				// Get reply ids
				//-----------------------------------------

				$messages	= array();

				$this->DB->build( array(
										'select'	=> 'msg_id',
										'from'		=> 'message_posts',
										'where'		=> 'msg_is_first_post=0 AND msg_author_id=' . intval($this->request['member_id']),
								)		);
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$messages[] = $r['msg_id'];
				}

				//-----------------------------------------
				// Delete topics
				//-----------------------------------------

				if( count($messages) )
				{
					$messengerLibrary->deleteMessages( $messages, $this->request['member_id'] );
				}
			}

			$this->registry->output->redirect( $this->settings['base_url'] . "module=members&amp;section=members&amp;do=viewmember&amp;member_id=" . $this->request['member_id'], $this->lang->words['deleted_pms'] );
		}
	}

	/**
	 * Learn about an IP address
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _learnIP()
	{
		if ( $this->request['ip'] == "" )
		{
			$this->_toolsIndex( $this->lang->words['t_noip'] );
			return false;
		}

		$ip				= trim($this->request['ip']);

		$resolved		= $this->lang->words['t_partip'];
		$exact			= 0;

		if ( substr_count( $ip, '.' ) == 3 )
		{
			$exact		= 1;
		}

		if ( strstr( $ip, '*' ) )
		{
			$exact		= 0;
			$ip			= str_replace( "*", "", $ip );
		}
		
		//-----------------------------------------
		// Warning...ipv6 doesn't tend to resolve properly on
		// some Windows machines - this seems to be an OS limitation
		//-----------------------------------------
		
		if( IPSLib::validateIPv6( $ip ) == true )
		{
			$exact		= 1;
		}

		if ( $exact == 1 )
		{
			$resolved	= @gethostbyaddr($ip);
			$query		= "='" . $ip . "'";
		}
		else
		{
			$query		= " LIKE '" . $ip . "%'";
		}

		$results	= IPSLib::findIPAddresses( $query );

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$registered		= array();
		$posted			= array();
		$voted			= array();
		$emailed		= array();
		$validating		= array();

		//-----------------------------------------
		// Find registered members
		//-----------------------------------------

		if( count($results['members']) )
		{
			foreach( $results['members'] as $m )
			{
				$m['_joined']	= ipsRegistry::getClass( 'class_localization')->getDate( $m['joined'], 'SHORT' );

				$registered[]	= $m;
			}

			unset($results['members']);
		}

		//-----------------------------------------
		// Find Names POSTED under
		//-----------------------------------------

		if( count($results['posts']) )
		{
			foreach( $results['posts'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_post_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['date'], 'SHORT' );

				$posted[]	= $m;
			}

			unset($results['posts']);
		}

		//-----------------------------------------
		// Find Names VOTED under
		//-----------------------------------------

		if( count($results['voters']) )
		{
			foreach( $results['voters'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_vote_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['date'], 'SHORT' );

				$voted[]	= $m;
			}

			unset($results['voters']);
		}
		
		//-----------------------------------------
		// Find Names VALIDATING under
		//-----------------------------------------

		if( count($results['validating']) )
		{
			foreach( $results['validating'] as $m )
			{
				$m['members_display_name']	= $m['members_display_name'] ? $m['members_display_name'] : $this->lang->words['t_guest'];
				$m['email'] 				= $m['email'] ? $m['email'] : $this->lang->words['t_notavail'];
				$m['_entry_date']			= ipsRegistry::getClass( 'class_localization')->getDate( $m['date'], 'SHORT' );

				$validating[]	= $m;
			}

			unset($results['validating']);
		}

		//-----------------------------------------
		// And output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->learnIPResults( $resolved, $registered, $posted, $voted, $emailed, $validating, $results );
	}

	/**
	 * Show all IP addresses a user has used
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _showIPs()
	{
		if ( !$this->request['name'] and !$this->request['member_id'] )
		{
			$this->_toolsIndex( $this->lang->words['t_noname'] );
			return false;
		}

		if ( $this->request['member_id'] )
		{
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name, email, ip_address', 'from' => 'members', 'where' => "member_id=" . intval($this->request['member_id']) ) );

			if ( ! $member['member_id'] )
			{
				$this->_toolsIndex( sprintf( $this->lang->words['t_nonameloc'], intval($this->request['member_id']) ) );
				return;
			}
		}
		else
		{
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name, email, ip_address', 'from' => 'members', 'where' => "members_l_username='" . $this->DB->addSlashes( strtolower($this->request['name']) ) . "' OR members_l_display_name='" . $this->DB->addSlashes( strtolower($this->request['name']) ) . "'" ) );

			if ( ! $member['member_id'] )
			{
				$this->_toolsIndex( $this->lang->words['t_noexact'], strtolower($this->request['name']) );
				return;
			}
		}

		$master	= array();
		$ips	= array();
		$reg	= array();
		$allips	= IPSMember::findIPAddresses( $member['member_id'] );
		$newips	= array();

		$st		= intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;
		$end	= 50;

		$links = $this->registry->output->generatePagination( array( 'totalItems'		=> count($allips),
														  			'itemsPerPage'		=> $end,
														  			'currentStartValue'	=> $st,
														  			'baseUrl'			=> $this->settings['base_url'] . $this->form_code . "&amp;do=show_all_ips&amp;member_id={$member['member_id']}",
												 			)      );

		//-----------------------------------------
		// Pseudo-pagination and ordering
		//-----------------------------------------
		
		foreach( $allips as $ip => $ipdata )
		{
			$newips[ $ipdata[1] ]	= array( $ip, $ipdata );
		}
		
		krsort($newips);

		$newips	= array_slice( $newips, $st, $end );
		$allips	= array();
		
		foreach( $newips as $ipdate => $ip_to_data )
		{
			$allips[ $ip_to_data[0] ]	= $ip_to_data[1];
		}

		if ( count($allips) > 0 )
		{
			foreach( $allips as $ip_address => $count )
			{
				$ips[]	= "'" . $ip_address . "'";
			}

			$this->DB->build( array( 'select' => 'ip_address', 'from' => 'members', 'where' => "ip_address IN (" . implode( ",", $ips ) . ") AND member_id != {$member['member_id']}" ) );
			$this->DB->execute();

			while ( $i = $this->DB->fetch() )
			{
				$reg[ $i['ip_address'] ][] = 1;
			}
		}

		$this->registry->output->html .= $this->html->showAllIPs( $member, $allips, $links, $reg );
	}

	/**
	 * IP Address Tools index page
	 *
	 * @param 	string		Message to display
	 * @param	string		Membername to default in the dropdown
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _toolsIndex( $msg="", $membername="" )
	{
		if ( !$membername )
		{
			$form = array(
							'text'		=> $this->lang->words['t_entername'],
							'form'		=> $this->registry->output->formInput( "name", isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' )
							);
		}
		else
		{
			$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "members_l_username LIKE '{$membername}%' OR members_l_display_name LIKE '{$membername}%'" ) );
			$this->DB->execute();

			if ( ! $this->DB->getTotalRows() )
			{
				$msg	= sprintf( $this->lang->words['t_nomemberloc'], $membername );

				$form = array(
								'text'		=> $this->lang->words['t_entername'],
								'form'		=> $this->registry->output->formSimpleInput( "name", isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' )
								);
			}
			else
			{
				$mem_array = array();

				while ( $m = $this->DB->fetch() )
				{
					$mem_array[] = array( $m['member_id'], $m['members_display_name'] );
				}

				$form = array(
								'text'		=> $this->lang->words['t_choosemem'],
								'form'		=> $this->registry->output->formDropdown( "member_id", $mem_array )
								);
			}
		}

		$this->registry->output->html .= $this->html->toolsIndex( $msg, $form );
	}
	
	/**
	 * Manage incomplete members
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _doIncomplete()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->registry->output->showError( $this->lang->words['t_nomemsel'], 11247 );
		}

		//-----------------------------------------
		// DELETE
		//-----------------------------------------

		if ( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete', 'members', 'members' );
			
			try
			{
				$message	= $this->_getManagementClass()->deleteMembers( $ids, 't_inc_removed' );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
		else if( $this->request['type'] == 'finalize' )
		{
			try
			{
				$message	= $this->_getManagementClass()->finalizeMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
	}
	
	/**
	 * Manage validating members
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _manageValidating()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->registry->output->showError( $this->lang->words['t_nomemsel'], 11247 );
		}

		//-----------------------------------------
		// APPROVE
		//-----------------------------------------

		if ( $this->request['type'] == 'approve' )
		{
			try
			{
				$message	= $this->_getManagementClass()->approveMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;

			if( $this->request['_return'] )
			{
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members&module=members&section=members&do=viewmember&member_id=' . $this->request['_return'] );
			}

			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// Resend validation email
		//-----------------------------------------

		else if ( $this->request['type'] == 'resend' )
		{
			try
			{
				$message	= $this->_getManagementClass()->approveMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;

			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// Ban
		//-----------------------------------------

		else if( $this->request['type'] == 'ban' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban' );
			
			try
			{
				$message	= $this->_getManagementClass()->banMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// SPAMMER
		//-----------------------------------------

		else if ( $this->request['type'] == 'spam' )
		{
			try
			{
				$message	= $this->_getManagementClass()->markMembersAsSpam( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// DELETE
		//-----------------------------------------

		else
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete' );
			
			try
			{
				$message	= $this->_getManagementClass()->denyMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
	}

	/**
	 * Manage spam requests
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unSpam()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->registry->output->showError( $this->lang->words['t_nomemunspammed'], 11248 );
		}

		//-----------------------------------------
		// Unspam
		//-----------------------------------------

		if ( $this->request['type'] == 'unspam' OR $this->request['type'] == 'unspam_posts' )
		{
			try
			{
				$message	= $this->_getManagementClass()->unmarkMembersAsSpam( $ids, $this->request['type'] == 'unspam_posts' ? true : false );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// Ban
		//-----------------------------------------

		else if ( $this->request['type'] == 'ban' OR $this->request['type'] == 'ban_blacklist' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban', 'members', 'members' );
			
			try
			{
				$message	= $this->_getManagementClass()->banSpammers( $ids, $this->request['type'] == 'ban_blacklist' ? true : false );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
		else if( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete', 'members', 'members' );
			
			$message	= $this->_getManagementClass()->deleteMembers( $ids );

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
	}

	/**
	 * Manage banned requests
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unban()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->registry->output->showError( $this->lang->words['t_nomemunban'], 11248 );
		}

		//-----------------------------------------
		// Unlock
		//-----------------------------------------

		if ( $this->request['type'] == 'unban' )
		{
			try
			{
				$message	= $this->_getManagementClass()->unbanMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}
	
			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

		else if ( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete', 'members', 'members' );
			
			IPSMember::remove( $ids );

			ipsRegistry::getClass('adminFunctions')->saveAdminLog( count($ids) . $this->lang->words['t_memdeleted']);

			$this->registry->output->global_message = count($ids) . $this->lang->words['t_memdeleted'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
	}

	/**
	 * Unapprove email change request
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _emailUnapprove()
	{
		//-----------------------------------------
		// GET member
		//-----------------------------------------

		if( !$this->request['mid'] )
		{
			$this->registry->output->showError( $this->lang->words['t_noemailloc'], 11249 );
		}

		try
		{
			$message	= $this->_getManagementClass()->unapproveEmailChange( $this->request['mid'] );
		}
		catch( Exception $error )
		{
			$this->registry->output->showError( $error->getMessage(), 11247 );
		}

		$this->registry->output->global_message	= $message;
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members&amp;type=validating' );
	}


	/**
	 * Unlock selected accounts
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _unlock()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		$ids = IPSLib::fetchInputAsArray( 'mid_' );
		
		if ( count($ids) < 1 )
		{
			$this->registry->output->showError( $this->lang->words['t_nolockloc'], 11251 );
		}

		//-----------------------------------------
		// Unlock
		//-----------------------------------------

		if ( $this->request['type'] == 'unlock' )
		{
			try
			{
				$message	= $this->_getManagementClass()->unlockMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// Ban
		//-----------------------------------------

		else if ( $this->request['type'] == 'ban' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban', 'members', 'members' );
			
			try
			{
				$message	= $this->_getManagementClass()->banMembers( $ids );
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 11247 );
			}

			$this->registry->output->global_message	= $message;
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

		else if ( $this->request['type'] == 'delete' )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete', 'members', 'members' );
			
			$this->registry->output->global_message	= $this->_getManagementClass()->deleteMembers( $ids );

			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'app=members' );
		}
	}

	/**
	 * Get the member management class
	 *
	 * @return	object
	 */
	protected function _getManagementClass()
	{
		static $_object = null;
		
		if( $_object === null )
		{
			$_class  = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/adminManage.php', 'adminMemberManagement', 'members' );
			$_object = new $_class( $this->registry );
		}
		
		return $_object;
	}
}