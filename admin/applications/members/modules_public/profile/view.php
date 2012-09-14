<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile View
 * Last Updated: $Date: 2012-05-21 16:37:50 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10777 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_view extends ipsCommand
{
	/**
	 * Custom fields object
	 *
	 * @var		object
	 */
	public $custom_fields;
	
	/**
	 * Temporary stored output HTML
	 *
	 * @var		string
	 */
	public $output;
	
	/**
	 * Member name
	 *
	 * @var		string
	 */
	protected $member_name;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_online' ), 'members' );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
 			$this->registry->output->showError( 'profiles_off', 10245, null, null, 403 );
		}

		$this->_viewModern();

		//-----------------------------------------
		// Push to print handler
		//-----------------------------------------
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->setTitle( $this->member_name . ' - ' . $this->lang->words['page_title_pp'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['page_title_pp'] . ': ' . $this->member_name, '' );
		$this->registry->output->sendOutput();
 	}
 	
 	/**
 	 * Get 12 random friends
 	 *
 	 * @param	array 	$member	Member data
 	 * @return	@e array
 	 */
 	protected function _getRandomFriends( $member )
 	{
		# Get random number from member's friend cache... grab 10 random. array_rand( array, no.)
		# also fall back on last 10 if no cache
		
		$_member_ids	= array();
		$friends		= array();

		if ( $member['pp_setting_count_friends'] > 0 && $this->settings['friends_enabled'] )
		{
			$member['_cache'] = IPSMember::unpackMemberCache( $member['members_cache'] );
		
			if ( is_array( $member['_cache']['friends'] ) AND count( $member['_cache']['friends'] ) )
			{
				foreach( $member['_cache']['friends'] as $id => $approved )
				{
					$id = intval( $id );
				
					if ( $approved AND $id )
					{
						$_member_ids[] = $id;
					}
				}
				
				$member['_total_approved_friends']	= count( $_member_ids );

				if ( is_array( $_member_ids ) AND $member['_total_approved_friends'] )
				{
					$_max		= $member['_total_approved_friends'] > 12 ? 12 : $member['_total_approved_friends'];
					$_rand		= array_rand( $_member_ids, $_max );
					$_final		= array();
					
					# If viewing member is in list, let's show em
					if( in_array( $this->memberData['member_id'], $_member_ids ) )
					{						
						$_final[]	= $this->memberData['member_id'];
						
						$new_mids	= array();
						
						foreach( $_member_ids as $mid )
						{
							if( $mid == $this->memberData['member_id'] )
							{
								continue;
							}
							
							$new_mids[] = $mid;
						}
												
						$_member_ids = $new_mids;
						unset( $new_mids );
						
						if( is_array( $_rand ) )
						{
							if( count( $_rand ) >= 12 )
							{
								array_pop( $_rand );
							}
						}
					}
				
					if ( is_array( $_rand ) AND count( $_rand ) )
					{
						foreach( $_rand as $_id )
						{
							$_final[] = $_member_ids[ $_id ];
						}
					}
				
					if ( count( $_final ) )
					{
						$sql_extra = ' AND friends_friend_id IN (' . IPSText::cleanPermString( implode( ',', $_final ) ) . ')';
					}
				}
			}
			
			/* Fetch friends */
			$_memberIds	= array();
			$_members	= array();
			$_friends	= array();
			
			$this->DB->build( array('select'	=> '*',
									'from'		=> 'profile_friends',
									'where'		=> 'friends_member_id=' . $member['member_id'] . ' AND friends_approved=1' . $sql_extra,
									'limit'		=> array( 0, 12 ),
									'order'		=> 'friends_approved DESC' )	);
																
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$_memberIds[]	= $row['friends_friend_id'];
				$_friends[]		= $row;
			}
			
			/* Got members? */
			$_members	= IPSMember::load( $_memberIds, 'core,extendedProfile' );
			
			foreach( $_friends as $row )
			{
				if( ! isset( $_members[ $row['friends_friend_id'] ] ) )
				{
					continue;
				}
				
				$row					= array_merge( $row, $_members[ $row['friends_friend_id'] ] );
				$row['_friends_added']	= ipsRegistry::getClass('class_localization')->getDate( $row['friends_added'], 'SHORT' );
				$row					= IPSMember::buildProfilePhoto( $row );
				
				$friends[]				= $row;
			}
		}
		
		return $friends;
 	}

	/**
	 * Modern profile
	 *
	 * @return	@e void		[Outputs to screen]
	 */
 	protected function _viewModern()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id			= intval( $this->request['id'] ) ? intval( $this->request['id'] ) : intval( $this->request['MID'] );

		if( !$member_id )
		{
			$this->registry->output->showError( 'profiles_no_member', 10246.0, null, null, 404 );
		}

		$tab				= substr( IPSText::alphanumericalClean( str_replace( '..', '', trim( $this->request['tab'] ) ) ), 0, 20 );
		$firsttab			= '';
		$member				= array();
		$friends			= array();
		$visitors			= array();
		$comment_perpage	= 5;
		$pips				= 0;
		$default_tab        = '';
		$tabs				= array();
		$_tabs				= array();
		$_positions			= array( 0 => 0 );
		$_member_ids		= array();
		$sql_extra			= '';
		$pass				= 0;
		$mod				= 0;
		$_todays_date		= getdate();
		$_rCustom			= intval( $this->request['removeCustomization'] );
		$_dCustom			= intval( $this->request['disableCustomization'] );
		$time_adjust		= $this->settings['time_adjust'] == "" ? 0 : $this->settings['time_adjust'];
		$board_posts		= $this->caches['stats']['total_topics'] + $this->caches['stats']['total_replies'];
		$seenFiles			= array();

		/* Removing customization? */
		if ( $_rCustom AND ( $member_id == $this->memberData['member_id'] OR $this->memberData['g_is_supmod'] ) AND $this->request['secure_key'] == $this->member->form_hash )
		{
			IPSMember::save( $member_id, array( 'extendedProfile' => array( 'pp_customization' => serialize( array() ) ) ) );
		}
		
		/* Disable? */
		if ( $_dCustom AND $this->memberData['g_is_supmod'] AND $this->request['secure_key'] == $this->member->form_hash )
		{
			IPSMember::save( $member_id, array( 'core' => array( 'bw_disable_customization' => 1 ) ) );
		}

		//-----------------------------------------
		// Grab all data...
		//-----------------------------------------
		
		$member		= IPSMember::load( $member_id, 'profile_portal,pfields_content,sessions,groups', 'id' );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( 'profiles_no_member', 10246, null, null, 404 );
		}
		
		/* Member banned or is spammer? */
		if ( IPSMember::isInactive( $member ) && ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->output->showError( 'profiles_not_active', '10246.1', null, null, 403 );
		}
		
		//-----------------------------------------
		// Configure tabs
		//-----------------------------------------
		
		foreach( IPSLib::getEnabledApplications() as $appDir => $app )
		{
			/* Path to tabs */
			$custom_path = IPSLib::getAppDir( $appDir ) . '/extensions/profileTabs';

			if ( is_dir( $custom_path ) )
			{
				foreach( new DirectoryIterator( $custom_path ) as $f )
				{
					if ( ! $f->isDot() && ! $f->isDir() )
					{
						$file = $f->getFileName();
						
						if( $file[0] == '.' )
						{
							continue;
						}
									
						if ( preg_match( '#\.conf\.php$#i', $file ) )
						{
							$classname = str_replace( ".conf.php", "", $file );
							
							$CONFIG = array();
							require( $custom_path . '/' . $file );/*noLibHook*/
							
							//-------------------------------
							// Allowed to use?
							//-------------------------------
						
							if ( $CONFIG['plugin_enabled'] )
							{
								/* Block friends tab if we have disabled friends or friends feature is shut off */
								if( $CONFIG['plugin_key'] == 'friends' AND ( !$member['pp_setting_count_friends'] OR !$this->settings['friends_enabled'] ) )
								{
									continue;
								}
								
								$CONFIG['app']				= $appDir;
								
								$_position					= $this->_getTabPosition( $_positions, $CONFIG['plugin_order'] );
								$_tabs[ $_position ]		= $CONFIG;
								$_positions[]				= $_position;
							}
						}
					}
				}
			}
		}
		
		ksort( $_tabs );
		
		foreach( $_tabs as $_pos => $data )
		{		
			$data['_lang']					= isset($this->lang->words[ $data['plugin_lang_bit'] ]) ? $this->lang->words[ $data['plugin_lang_bit'] ] : $data['plugin_name'];
			$tabs[ $data['plugin_key'] ]	= $data;
		}
		
		if ( $tab && @is_file( IPSLib::getAppDir( $tabs[ $tab ]['app'] ) . '/extensions/profileTabs/' . $tab . '.php' ) )
		{
			$default_tab = $tabs[ $tab ]['app'] . ':' . $tab;
			
			/* Update <title> */
			$this->lang->words['page_title_pp'] .= ': ' . $tabs[ $tab ]['_lang'];
		}
		else
		{
			$default_tab = 'core:info';
		}
		
		$friends	= $this->_getRandomFriends( $member );
		
		/* Check USER permalink... */
		$this->registry->getClass('output')->checkPermalink( ( $member['members_seo_name'] ) ? $member['members_seo_name'] : IPSText::makeSeoTitle( $member['members_display_name'] ) );
		
		/* Build data */
		$member = IPSMember::buildDisplayData( $member, array( 'customFields' => 1, 'cfSkinGroup' => 'profile', 'checkFormat' => 1, 'cfGetGroupData' => 1, 'signature' => 1, 'spamStatus' => 1 ) );

		//-----------------------------------------
		// Recent visitor?
		//-----------------------------------------
		
		if ( $member['member_id'] != $this->memberData['member_id'] && ! IPSMember::isLoggedInAnon($this->memberData) )
		{
			$this->_addRecentVisitor( $member, $this->memberData['member_id'] );
		}

		//-----------------------------------------
		// DST?
		//-----------------------------------------
		
		if ( $member['dst_in_use'] == 1 )
		{
			$member['time_offset'] += 1;
		}

		//-----------------------------------------
		// Format extra user data
		//-----------------------------------------
		
		$member['_age'] = ( $member['bday_year'] ) ? date( 'Y' ) - $member['bday_year'] : 0;
		
		if( $member['bday_month'] > date( 'n' ) )
		{
			$member['_age'] -= 1;
		}
		else if( $member['bday_month'] == date( 'n' ) )
		{
			if( $member['bday_day'] > date( 'j' ) )
			{
				$member['_age'] -= 1;
			}
		}

		$member['_local_time']	= $member['time_offset'] != "" ? gmstrftime( $this->settings['clock_long'], time() + ($member['time_offset']*3600) + ($time_adjust * 60) ) : '';
		$member['g_title']		= IPSMember::makeNameFormatted( $member['g_title'], $member['g_id'], $member['prefix'], $member['suffix'] );
		$member['_bday_month']	= $member['bday_month'] ? $this->lang->words['M_' . $member['bday_month'] ] : 0;

		//-----------------------------------------
		// Visitors
		//-----------------------------------------
		
		if ( $member['pp_setting_count_visitors'] )
		{
			$_pp_last_visitors	= unserialize( $member['pp_last_visitors'] );
			$_visitor_info		= array();
			
			if ( is_array( $_pp_last_visitors ) )
			{
				krsort( $_pp_last_visitors );
			
				$_members = IPSMember::load( array_values( $_pp_last_visitors ), 'extendedProfile' );
	
				foreach( $_members as $_id => $_member )
				{ 
					$_visitor_info[ $_id ] = IPSMember::buildDisplayData( $_member, array( 'reputation' => 0, 'warn' => 0 ) );
				}
				
				foreach( $_pp_last_visitors as $_time => $_id )
				{
					if ( !$_visitor_info[ $_id ]['members_display_name_short'] )
					{
						$_visitor_info[ $_id ] = IPSMember::buildDisplayData( IPSMember::setUpGuest(), array( 'reputation' => 0, 'warn' => 0 ) );
					}
					
					$_visitor_info[ $_id ]['_visited_date'] 				= ipsRegistry::getClass( 'class_localization')->getDate( $_time, 'TINY' );
					$_visitor_info[ $_id ]['members_display_name_short']	= $_visitor_info[ $_id ]['members_display_name_short'] ? $_visitor_info[ $_id ]['members_display_name_short'] : $this->lang->words['global_guestname'];

					$visitors[] = $_visitor_info[ $_id ];
					
					if ( count($visitors) == 5 )
					{
						break;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Online location
		//-----------------------------------------
		
		$member = IPSMember::getLocation( $member );
		
		//-----------------------------------------
		// Add profile view
		//-----------------------------------------
		
		$this->DB->insert( 'profile_portal_views', array( 'views_member_id' => $member['member_id'] ), true );
		
		//-----------------------------------------
		// Grab default tab...
		//-----------------------------------------
		
		$tab_html = '';
		
		if ( $tab )
		{
			if( is_file( IPSLib::getAppDir( $tabs[ $tab ]['app'] ) . '/extensions/profileTabs/' . $tab . '.php' ) )
			{
				require( IPSLib::getAppDir( 'members' ) . '/sources/tabs/pluginParentClass.php' );/*noLibHook*/
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $tabs[ $tab ]['app'] ) . '/extensions/profileTabs/' . $tab . '.php', 'profile_' . $tab, $tabs[ $tab ]['app'] );
				$plugin      = new $classToLoad( $this->registry );
				$tab_html    = $plugin->return_html_block( $member );
			}
		}
		
		//-----------------------------------------
		// Set description tag
		//-----------------------------------------
		
		$_desc = ( $member['pp_about_me'] ) ? $member['pp_about_me'] : $member['signature'];
		
		if ( $_desc )
		{
			$this->registry->output->addMetaTag( 'description', $member['members_display_name'] . ': ' . IPSText::xssMakeJavascriptSafe( IPSText::getTextClass('bbcode')->stripAllTags( $_desc ) ) );
		}
		
		/* Reputation */
		if ( $this->settings['reputation_protected_groups'] )
		{
			if ( in_array( $member['member_group_id'], explode( ",", $this->settings['reputation_protected_groups'] ) ) )
			{
				$this->settings['reputation_show_profile'] = false;
			}
		}
		
		//-----------------------------------------
		// Try to "fix" empty custom field groups
		//-----------------------------------------
		
		foreach( $member['custom_fields'] as $group => $mdata )
		{
			if( $group != 'profile_info' AND $group != 'contact' )
			{
				if( is_array( $member['custom_fields'][ $group ] ) AND count( $member['custom_fields'][ $group ] ) )
				{
					$_count	= 0;
					
					foreach( $member['custom_fields'][ $group ] as $key => $value )
					{
						if( $value )
						{
							$_count++;
						}
					}
					
					if( !$_count )
					{
						unset($member['custom_fields'][ $group ]);
					}
				}
			}
		}
		
		//-----------------------------------------
		// Format signature
		//-----------------------------------------
		
		if( $member['signature'] )
		{
			IPSText::getTextClass('bbcode')->parse_html				= $member['g_dohtml'];
			IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
			IPSText::getTextClass('bbcode')->parse_smilies			= 0;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass('bbcode')->parsing_section		= 'signatures';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $member['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $member['mgroup_others'];
		
			$member['signature']	= IPSText::getTextClass('bbcode')->preDisplayParse( $member['signature'] );
		
			$member['signature'] = $this->registry->getClass('output')->getTemplate('global')->signature_separator( $member['signature'] );
		}
		
		//-----------------------------------------
		// Format 'About me'
		//-----------------------------------------

		if( $member['pp_about_me'] )
		{
			IPSText::getTextClass('bbcode')->parse_html				= $member['g_dohtml'];
			IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
			IPSText::getTextClass('bbcode')->parse_smilies			= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass('bbcode')->parsing_section		= 'aboutme';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $member['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $member['mgroup_others'];
	
			$member['pp_about_me']	= IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $member['pp_about_me'] ) );
		}
		
		/* final data */
		if ( $default_tab == 'core:info' )
		{
			/* Load status class */
			if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
				$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
			}
			
			/* Fetch */
			$status = $this->registry->getClass('memberStatus')->fetchMemberLatest( $member['member_id'] );
		}
		
		//-----------------------------------------
		// Warnings?
		//-----------------------------------------
		
		$warns = array();
		if ( $member['show_warn'] )
		{
			if ( $member['member_banned'] )
			{
				$warns['ban'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_suspend<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['ban'] = $_warn['wl_id'];
				}
			}
			if ( $member['temp_ban'] )
			{
				$warns['suspend'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_suspend<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['suspend'] = $_warn['wl_id'];
				}
			}
			if ( $member['restrict_post'] )
			{
				$warns['rpa'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_rpa<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['rpa'] = $_warn['wl_id'];
				}
			}
			if ( $member['mod_posts'] )
			{
				$warns['mq'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_mq<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['mq'] = $_warn['wl_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Add to output
		//-----------------------------------------
		
		$this->request['member_id'] = intval( $this->request['showuser'] );
		
		$this->member_name	= $member['members_display_name'];
		
		
		/* Photo album */
		
		ini_set('display_errors', 1);

		/* Setup Gallery Environment */
		require_once( IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/classes/gallery.php' );
		$this->registry->setClass( 'gallery', new ipsGallery( $this->registry ) );
		
		$this->albums = $this->registry->gallery->helper('albums');
		$this->images = $this->registry->gallery->helper('image');
		
		/* Find the users profile photo album */
		$photoAlbum = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'gallery_albums_main', 'where' => "album_owner_id={$this->request['member_id']}" ) );
		
   		$profileImages = array();
   		
		if($photoAlbum) {
    		/* Query images */
    		$this->DB->build( array( 
    									'select'	=> '*', 
    									'from'		=> 'gallery_images', 
    									'where'		=> "img_album_id={$photoAlbum['album_id']} AND approved=1",
    									'order'		=> "id DESC",
    									'limit'		=> array( 0, 5 )
    						)	);
    		$q = $this->DB->execute();
    		
    		while( $image = $this->DB->fetch( $q ) )
    		{
    			$image['_image'] = $this->images->makeImageTag( $image, array( 'type' => 'thumb', 'link-type' => 'src' ) );
    			$profileImages[] = $image;
    		}
		}
		
		
		$this->output		= $this->registry->getClass('output')->getTemplate('profile')->profileModern( $tabs, $member, $visitors, $default_tab, $tab_html, $friends, $status, $warns, true, $profileImages );
	}
	
	/**
	 * Determines where to put custom profile tabs
	 *
	 * @param	array 		$takenPositions		Array of positions that have been used
	 * @param	integer		$requestedPosition	Position to check
	 * @return	integer
	 */
	protected function _getTabPosition( $takenPositions, $requestedPosition )
	{
		if( in_array( $requestedPosition, $takenPositions ) )
		{
			$requestedPosition++;
			$requestedPosition = $this->_getTabPosition( $takenPositions, $requestedPosition );
		}
		
		return $requestedPosition;
	}
 	
 	/**
	 * Adds a recent visitor to ones profile
	 *
	 * @param	array 				Member information
	 * @param	integer				Member id to add
	 * @return	boolean
	 * @since	IPB 2.2.0.2006-7-31
	 */
 	protected function _addRecentVisitor( $member=array(), $member_id_to_add=0 )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id_to_add	= intval( $member_id_to_add );
		$found				= 0;
		$_recent_visitors	= array();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $member_id_to_add )
		{
			return false;
		}
		
		//-----------------------------------------
		// Sort out data...
		//-----------------------------------------
		
		$recent_visitors = unserialize( $member['pp_last_visitors'] );
		
		if ( ! is_array( $recent_visitors ) OR ! count( $recent_visitors ) )
		{
			$recent_visitors = array();
		}
		
		foreach( $recent_visitors as $_time => $_id )
		{
			if ( $_id == $member_id_to_add )
			{
				$found = 1;
				continue;
			}
			else
			{
				$_recent_visitors[ $_time ] = $_id;
			}
		}
		
		$recent_visitors = $_recent_visitors;
	
		krsort( $recent_visitors );
	
		//-----------------------------------------
		// No more than 10
		//-----------------------------------------
	
		if ( ! $found )
		{
			if ( count( $recent_visitors ) > 5 )
			{
				$_tmp = array_pop( $recent_visitors );
			}
		}
		
		//-----------------------------------------
		// Add the visit
		//-----------------------------------------
			
		$recent_visitors[ time() ] = $member_id_to_add;
		
		krsort( $recent_visitors );
		
		//-----------------------------------------
		// Update profile...
		//-----------------------------------------
	
		if ( $member['pp_member_id'] )
		{
			$this->DB->update( 'profile_portal ', array( 'pp_last_visitors' => serialize( $recent_visitors ) ), 'pp_member_id=' . $member['member_id'], true );
		}
		else
		{
			$this->DB->insert( 'profile_portal ', array( 'pp_member_id'		=> $member['member_id'],
															'pp_last_visitors'	=> serialize( $recent_visitors ) 
								), true					);
		}
		
		return true;
	}
}