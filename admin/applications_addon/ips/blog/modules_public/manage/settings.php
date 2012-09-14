<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		10/26/2010
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_manage_settings extends ipsCommand
{
	/**
	* Handle basic permissions checks for this module, then passes the work off to the appropriate method.
	*/
	public function doExecute( ipsRegistry $registry )
	{
		$this->blogFunctions = $this->registry->getClass('blogFunctions');
				
		$blog_id  = intval( $this->request['blogid'] );
		
		if( empty($blog_id) )
		{
			$this->registry->output->showError( 'incorrect_use', 106174, null, null, 404 );
		}
		
		$myblog = $this->blogFunctions->loadBlog( $blog_id );
		
		/* Check */
		if ( empty($myblog['blog_id']) OR ! $this->blogFunctions->ownsBlog($myblog) )
		{
			$this->registry->output->showError( 'blog_no_permission', 2060, null, null, 403 );
		}
		
		if( isset($this->request['form_hash']) && $this->request['form_hash'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'blog_no_permission', 2060, null, null, 403 );
		}
		
		/* Add navigation */
		$this->registry->output->addNavigation( $this->lang->words['blist_blogs'], 'app=blog', 'false', 'app=blog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_manage'], 'app=blog&amp;module=manage', 'false', 'manageblog' );
		$this->registry->output->setTitle( $this->lang->words['blog_manage'] . ' - ' . $this->settings['board_name'] );
		
		// What are we doing with our lives? (Or this request)
		switch($this->request['act'])
		{
			case 'rssimport':
				$output = $this->_actRssImport($myblog);
			break;
			
			case 'settings':
			default:
				$output = $this->_actSettings($myblog);
			break;
		}
		
		/* Finally print output */
		$this->registry->output->addContent( $output );
		$this->registry->output->sendOutput();
	}
	
	/**
	* Handle RSS import form:
	*/
	protected function _actRssImport($myblog)
	{
		$errors = array();
		
		if( isset($this->request['form_hash']) )
		{
			$errors = $this->_processRssForm($myblog);
		}
		
		return $this->_showRssForm($myblog, $errors);
	}
	
	/**
	* Process RSS import form:
	*/
	protected function _processRssForm($blog)
	{
		// No feed URL? CEASE FEEDINGS!
		if( ! $this->request['rss_url'] )
		{
	 		$this->DB->delete( 'blog_rssimport', 'rss_blog_id=' . intval( $blog['blog_id'] ) );
			$this->registry->output->redirectScreen( $this->lang->words['rss_stopped'], $this->settings['base_url'] . 'app=blog&amp;module=manage', 'false', true, 'manageblog' );
		}
		
		/* INIT */
		$rss_url       = trim($this->request['rss_url']);
		$rss_auth_user = trim($this->request['rss_auth_user']);
		$rss_auth_pass = trim($this->request['rss_auth_pass']);
		$rss_cats      = count($this->request['rss_cats']) ? IPSText::cleanPermString( implode( ',', array_map( 'intval', $this->request['rss_cats'] ) ) ) : '';
		
		/* Fetch Class */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/post/blogRssImport.php', 'blogRssImport', 'blog' );
		$blogRssImport = new $classToLoad($this->registry);
	
		/* Check */
		if ( ! $this->settings['blog_allow_rssimport'] OR ! $this->memberData['g_blog_rsspergo'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 206000012, null, null, 403 );
		}
		
		/* Fetch any current RSS import data */
 		$data = $this->DB->buildAndFetch( array( 'select' => '*',
 												 'from'	  => 'blog_rssimport',
 												 'where'  => 'rss_blog_id=' . intval( $blog['blog_id'] ) ) );
 		
 		/* Check it, yo */
 		if ( ! $rss_url )
 		{
 			return array( 0 => $this->lang->words['bucpo_error_no_rss_url'] );
 		}
 		
 		/* Check for http */
 		$rss_url = str_replace( 'feed://', 'http://', $rss_url );
 		if ( ! strstr( $rss_url, 'http://' ) && ! strstr( $rss_url, 'https://' ) )
 		{
 			return array( 0 => $this->lang->words['bucpo_error_no_rss_url'] );
 		}
 		
 		/* Tags */
 		$rss_tags = serialize( array( 'tags' => $this->request['ipsTags'] ) );
 		
 		/* Validate */
 		$validate = $blogRssImport->validate( $rss_url, $rss_auth_user, $rss_auth_pass );
 		
 		if ( $validate !== TRUE )
 		{
 			return array( 0 => $validate );
 		}
 		
 		$save = array( 'rss_blog_id'     => $blog['blog_id'],
 					   'rss_url'	     => $rss_url,
 					   'rss_auth_user'   => $rss_auth_user,
 					   'rss_auth_pass'   => $rss_auth_pass,
 					   'rss_tags'		 => $rss_tags,
 					   'rss_cats'		 => $rss_cats,
 					   'rss_last_import' => 0 );
 					   
 		if ( $data['rss_url'] AND ( $data['rss_url'] != $rss_url ) )
 		{
 			/* Different URL? Reset count */
 			$save['rss_count'] = 0;
 		}   
 
 		 /* OK? Store it */
 		if ( $data['rss_id'] )
 		{
 			$this->DB->update( 'blog_rssimport', $save, 'rss_id=' . intval( $data['rss_id'] ) );
 		}
 		else
 		{
 			$this->DB->insert( 'blog_rssimport', $save );
 		}
 		
 		/* Run an import */
 		$blogRssImport->processSingle( $blog['blog_id'] );
 		
 		/* Bounce to the settings screen */
		$this->registry->output->redirectScreen( $this->lang->words['succes_stored_settings'], $this->settings['base_url'] . 'app=blog&amp;module=manage', 'false', true, 'manageblog' );
	}
	
	/**
	* Show the RSS import form:
	*/
	protected function _showRssForm($blog, $errors)
	{
		/* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['bucpo_rssform'], 'app=blog&amp;module=manage&amp;section=settings&amp;act=rssimport&amp;blogid='.$blog['blog_id'], '', '' );
		$this->registry->output->setTitle( $this->lang->words['bucpo_rssform'] . ' - ' . $this->settings['board_name'] );
		
		/* Not allowed? */
		if ( ! $this->settings['blog_allow_rssimport'] OR ! $this->memberData['g_blog_rsspergo'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 206000012, null, null, 403 );
		}
		
		/* Fetch any current RSS import data */
 		$data = $this->DB->buildAndFetch( array( 'select' => '*',
 												 'from'	  => 'blog_rssimport',
 												 'where'  => 'rss_blog_id=' . intval( $blog['blog_id'] ) ) );
 		
 		/* Date */
 		$data['_rss_last_import'] = $this->registry->class_localization->getDate( $data['rss_last_import'], 'LONG' );
 		
 		/* Me-ow */
 		$data['_cats'] = $this->blogFunctions->fetchBlogCategories( $blog['blog_id'], explode( ',', IPSText::cleanPermString( $data['rss_cats'] ) ) );
 		
		$error = count($errors) ? implode( "<br />", $errors ) : '';
		
		/* Tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagBox = classes_tags_bootstrap::run( 'blog', 'rssimport' )->render( 'entryBox', array( 'meta_parent_id' => $blog['blog_id'] ) );
		
		return $this->registry->output->getTemplate('blog_manage')->rssImportForm( $blog, $data, $error, $tagBox );
	}
	
	/**
	* Handles the settings form:
	*/
	protected function _actSettings($myblog)
	{
		$errors = array();
		
		if( isset($this->request['form_hash']) )
		{
			$errors = $this->_processForm($myblog);
		}
		
		return $this->_showSettingsForm($myblog, $errors);
	}
	
	// For everything below this line, I am sorry. Some things just can't be fixed.
	
	/**
	* Shows the settings form:
	*/
	protected function _showSettingsForm($myblog, $errors = array())
	{
		/* Navigation */
		$this->registry->output->addNavigation( $this->lang->words['bucpo_settings'], 'app=blog&amp;module=manage&amp;section=settings&amp;blogid='.$myblog['blog_id'], '', '' );
		$this->registry->output->setTitle( $this->lang->words['bucpo_settings'] . ' - ' . $this->settings['board_name'] );
				
		//-----------------------------------------
		// Did we get here because of an error?
		//-----------------------------------------
		
		if ( count($errors) )
		{
			$myblog['blog_name']							= $this->request['blog_name'];
			$myblog['blog_desc']							= $this->request['blog_desc'];
			$myblog['blog_type']							= $this->request['blog_type'];
			$myblog['private_buddies']						= $this->memberData['g_blog_allowprivclub'] ? IPSText::stripslashes( $_POST['private_buddies'] ) : "";
			$myblog['blog_editors']							= $this->memberData['g_blog_alloweditors'] ? IPSText::stripslashes( $_POST['blog_editors'] ) : "";
			$myblog['blog_allowguests']						= $this->request['blog_allowguests'] ? 1 : 0;
			$myblog['blog_settings']['allowguestcomments']	= $this->request['blog_allowguestcomments'] ? 1 : 0;
			$myblog['blog_settings']['disable_comments']	= $this->request['blog_disable_comments'] ? 1 : 0;
			$myblog['blog_settings']['allowrss']			= $this->request['blog_allowrss'] ? 1 : 0;
			$myblog['blog_settings']['rssfeedburner']		= $this->request['blog_rss_feedburner'];
			$myblog['blog_settings']['allowtrackback']		= $this->request['blog_allowtrackback'] ? 1 : 0;
			$myblog['blog_settings']['trackcomments']		= $this->request['blog_trackcomments'] ? 1 : 0;
			$myblog['blog_settings']['defaultstatus']		= $this->request['blog_defaultstatus'] == "published" ? "published" : "draft";
			$myblog['blog_settings']['eopt_mode']			= $this->request['blog_eopt_mode'] == "autohide" ? "autohide" : "show";
			$myblog['blog_settings']['viewmode']			= $this->request['blog_viewmode'];
			$myblog['blog_settings']['entriesperpage']		= $this->request['blog_entriesperpage'];
			$myblog['blog_settings']['commentsperpage']		= $this->request['blog_commentsperpage'];
			$myblog['blog_settings']['approvemode']			= $this->request['blog_approvemode'];
			$myblog['blog_skin_id']							= $this->request['blog_skin_id'] ? $this->request['blog_skin_id'] : 0;
			$myblog['blog_settings']['allowtrackback']	= $this->request['blog_allowtrackback'] ? $this->request['blog_allowtrackback'] : 0;
			$myblog['blog_settings']['approvetrackbacks']	= $this->request['blog_approvetrackbacks'] ? $this->request['blog_approvetrackbacks'] : 0;
		
			if ( $this->request['blog_exturl'] )
			{
				if ( stripos( $this->request['blog_exturl'], "http://" ) !== 0 && stripos( $this->request['blog_exturl'], "https://" ) !== 0 )
				{
					$this->request['blog_exturl'] =  "http://" . $this->request['blog_exturl'];
				}
				
				$myblog['blog_exturl'] = $this->request['blog_exturl'];
			}
			
			if ( $this->request['blog_rssurl'] )
			{
				if ( stripos( $this->request['blog_rssurl'], "http://" ) !== 0 && stripos( $this->request['blog_rssurl'], "https://" ) !== 0 )
				{
					$this->request['blog_rssurl'] = "http://".$this->request['blog_rssurl'];
				}
				
				$myblog['blog_exturl'] = $this->request['blog_rssurl'];
			}
		}
		
		/* Blog View Level */
		$myblog['view_level'] = array();
		
		$myblog['view_level'][] = array( 'public', $this->lang->words['cat_public'], $myblog['blog_view_level'] == 'public' ? " selected='selected'" : '' );
		
		if( $this->memberData['g_blog_allowprivate'] )
		{
			if( $this->memberData['g_blog_allowprivclub'] )
			{
				$myblog['view_level'][] = array( 'friends', $this->lang->words['cat_friends'], $myblog['blog_view_level'] == 'friends' ? " selected='selected'" : '' );
				$myblog['view_level'][] = array( 'privateclub', $this->lang->words['cat_privateclub'], $myblog['blog_view_level'] == 'privateclub' ? " selected='selected'" : '' );
			}
			
			$myblog['view_level'][] = array( 'private', $this->lang->words['cat_private'], $myblog['blog_view_level'] == 'private' ? " selected='selected'" : '' );
		}
		
		/* Private Club Buddies */
		$myblog['private_buddies']	= !empty($myblog['private_buddies']) ? $myblog['private_buddies'] : '';
		
		if( $this->memberData['g_blog_allowprivclub'] && ! $myblog['private_buddies'] )
		{
			/* INIT */
			$private_club_members = array();
			
			/* Get our buddies */
			$_buddies = IPSText::cleanPermString( $myblog['blog_authorized_users'] );
			
			/* Get their names */
			if( $_buddies )
			{
				$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => 'member_id IN(' . $_buddies . ')' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					if( $r['member_id'] != $this->memberData['member_id'] )
					{
						$private_club_members[] = $r['members_display_name'];
					}
				}
			}
			
			$myblog['private_buddies'] = implode( "\n", $private_club_members );
		}
		
		//-----------------------------------------
		// Editors
		//-----------------------------------------
		
		if ( $this->memberData['g_blog_alloweditors'] )
		{
			if ( is_array( $myblog['blog_settings']['editors'] ) AND count( $myblog['blog_settings']['editors'] ) > 0 )
			{
				$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "member_id in(".implode( ",", $myblog['blog_settings']['editors'] ).")" ) );
				$this->DB->execute();
				
				$editors = array();
				
				while( $member = $this->DB->fetch() )
				{
					$editors[] = $member['members_display_name'];
				}
				
				$myblog['blog_editors'] = implode( "\n", $editors );
			}
			else
			{
				$myblog['blog_editors'] = '';
			}
		}
		
		//-----------------------------------------
		// Skins?
		//-----------------------------------------
		
		if ( $this->settings['blog_skin_list'] )
		{
			$skinids	= explode( ",", $this->settings['blog_skin_list'] );
		}
		else
		{
			$skinids	= array();
		}
		
		$skinarray[0]	= $this->lang->words['blog_skin_default'];
		
		foreach ( $skinids as $skin_id )
		{
			$skinarray[ $skin_id ]	= $this->caches['skinsets'][ $skin_id ]['set_name'];
		}
		
		//-----------------------------------------
		// Get the settings, I didn't want these in the cache as they are only used here :)
		//-----------------------------------------
		
		$this->DB->build( array( 
									'select'	=> 'conf_key, conf_value, conf_default',
									'from'		=> 'core_sys_conf_settings',
									'where'		=> "conf_key in('blog_entriesperpage_list', 'blog_commentsperpage_list')"
						)	);
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$settings[ $row['conf_key'] ] = $row['conf_value'] ? $row['conf_value'] : $row['conf_default'];
		}
		
		//-----------------------------------------
		// Build a few dropdowns
		//-----------------------------------------
		
		$myblog['blog_settings']['defaultstatus']	= $myblog['blog_settings']['defaultstatus'] == "published" ? "published" : "draft";
		$myblog['blog_defaultstatus']				= $this->blogFunctions->buildKeyValDropDown( array ("draft" => $this->lang->words['draft'], "published" => $this->lang->words['published']), $myblog['blog_settings']['defaultstatus'] );
		$myblog['blog_eopt_mode']					= $this->blogFunctions->buildKeyValDropDown( array ("autohide" => $this->lang->words['eoptions_hide'], "show" => $this->lang->words['eoptions_show']), $myblog['blog_settings']['eopt_mode'] );
		$myblog['blog_skin_id']						= $this->blogFunctions->buildKeyValDropDown( $skinarray, $myblog['blog_skin_id'] );
		$myblog['entriesperpage']					= $this->blogFunctions->buildDropDown( explode ( "," ,$settings['blog_entriesperpage_list'] ), $myblog['blog_settings']['entriesperpage'] );
		$myblog['commentsperpage']					= $this->blogFunctions->buildDropDown( explode ( "," ,$settings['blog_commentsperpage_list'] ), $myblog['blog_settings']['commentsperpage'] );
		$myblog['approvemode']						= $this->blogFunctions->buildKeyValDropDown( array ( 'none' => $this->lang->words['approvemode_none'], 'guests' => $this->lang->words['approvemode_guests'], 'all' => $this->lang->words['approvemode_all'] ), $myblog['blog_settings']['approvemode'] ? $myblog['blog_settings']['approvemode'] : 'none' );
		
		//-----------------------------------------
		// Display global settings warning text
		//-----------------------------------------
		$guest		= $this->DB->buildAndFetch ( array ( 'select' => 'g_perm_id', 'from' => 'groups', 'where' => "g_id = {$this->settings['guest_group']}" ) );
		$masks		= explode( ',', $guest['g_perm_id'] );
		$perm_row	= $this->DB->buildAndFetch ( array ( 'select' => '*', 'from' => 'permission_index', 'where' => "app='blog' AND perm_type='main'" ) );
		$parsed		= $this->registry->permissions->parse( $perm_row );
		
		if ( count($parsed) )
		{
			if( $parsed['perm_view'] == '*' )
			{
				$myblog['global_guestview']	= '';
			}
			else
			{
				foreach( $masks as $mask_id )
				{
					if( strpos( $parsed['perm_view'], ',' . $mask_id . ',' ) !== false )
					{
						$myblog['global_guestview']	= '';
					}
				}
			}
			
			if( $parsed['perm_comments'] == '*' )
			{
				$myblog['global_guestcomment']	= '';
			}
			else
			{
				foreach( $masks as $mask_id )
				{
					if( strpos( $parsed['perm_comments'], ',' . $mask_id . ',' ) !== false )
					{
						$myblog['global_guestcomment']	= '';
					}
				}
			}
		}
		else
		{
			$myblog['global_guestview']	    = '';
			$myblog['global_guestcomment']	= '';
		}
		
		if ( !$myblog['blog_name'] )
		{
			$errors[] = $this->lang->words['blog_store_to_activate'];
			
			if ( strtolower( substr( $this->memberData['members_display_name'], -1 ) ) == "s" )
			{
				$myblog['blog_name'] = trim( $this->memberData['members_display_name'] ) . $this->lang->words['blogname_end_s'];
			}
			else
			{
				$myblog['blog_name'] = trim( $this->memberData['members_display_name'] ) . $this->lang->words['blogname_noend_s'];
			}
		}
		
		if ( $myblog['blog_disabled'] )
		{
			$errors[] = $this->lang->words['blog_disabled'];
		}
		
		//-----------------------------------------
		// Ping settings?
		//-----------------------------------------
		$myblog['ping_options'] = array();
		
		if( $this->settings['blog_allow_pingblogs'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'blog_pingservices', 'where' => 'blog_service_enabled=1' ) );
			$this->DB->execute();
			
			while( $service = $this->DB->fetch() )
			{
				$service['enabled'] = $myblog['blog_settings']['pings'][$service['blog_service_key']] ? "checked='checked'" : "" ;
				$myblog['ping_options'][] = $service;
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$error = count($errors) ? implode( "<br />", $errors ) : '';
		
		return $this->registry->getClass('output')->getTemplate('blog_manage')->settingsForm( $myblog, $error );
	}
	
	/**
	* Process the settings form.
	*/
	protected function _processForm($myblog)
	{
		/* INIT */
		$blogid    = intval( $this->request['blogid'] );
		$errors    = array();
		$save_blog = array();
				
		/* Settings */
		if(is_string($myblog['blog_settings']))
		{
			$myblog['blog_settings'] = unserialize( $myblog['blog_settings'] );
		}
		
		/* Check Name */
		if( $this->request['blog_name'] )
		{
			$save_blog['blog_name'] = IPSText::getTextClass('bbcode')->stripBadWords( $this->request['blog_name'] );
		}
		else
		{
			$errors[] = $this->lang->words['no_name_entered'];
		}
		
		/* Description and FURL Name */
		$save_blog['blog_desc']     = IPSText::getTextClass('bbcode')->stripBadWords( $this->request['blog_desc'] );
		$save_blog['blog_seo_name'] = IPSText::makeSeoTitle( $save_blog['blog_name'] );
		
		/* Settings based on type */
		switch( $this->request['blog_type'] )
		{
			/* Local Blog Settings */
			case 'local':
				$save_blog['blog_exturl']						= "";
				$save_blog['blog_skin_id']						= $this->request['blog_skin_id'] ? $this->request['blog_skin_id'] : 0;
				$save_blog['blog_allowguests']					= $this->request['blog_allowguests'] ? 1 : 0;
				$save_blog['blog_view_level']					= $this->request['blog_view_level'];
				
				$myblog['blog_settings']['viewmode']			= $this->request['blog_viewmode'];
				$myblog['blog_settings']['rssfeedburner']		= $this->request['blog_rss_feedburner'];
				$myblog['blog_settings']['allowguestcomments']	= $this->request['blog_allowguestcomments']           ? 1 : 0;
				$myblog['blog_settings']['disable_comments']	= $this->request['blog_disable_comments']             ? 1 : 0;
				$myblog['blog_settings']['allowrss']			= $this->request['blog_allowrss']                     ? 1 : 0;
				$myblog['blog_settings']['allowtrackback']		= $this->request['blog_allowtrackback']               ? 1 : 0;
				$myblog['blog_settings']['trackcomments']		= $this->request['blog_trackcomments']                ? 1 : 0;
				$myblog['blog_settings']['entriesperpage']		= $this->request['blog_entriesperpage']               ? $this->request['blog_entriesperpage']  : $myblog['blog_settings']['entriesperpage'];
				$myblog['blog_settings']['commentsperpage']		= $this->request['blog_commentsperpage']              ? $this->request['blog_commentsperpage'] : $myblog['blog_settings']['commentsperpage'];
				$myblog['blog_settings']['approvemode']			= $this->request['blog_approvemode']                  ? $this->request['blog_approvemode']     : "all";
				$myblog['blog_settings']['defaultstatus']		= $this->request['blog_defaultstatus'] == "published" ? "published" : "draft";
				$myblog['blog_settings']['eopt_mode']			= $this->request['blog_eopt_mode']     == "autohide"  ? "autohide" : "show";
				$myblog['blog_settings']['allowtrackback']		= $this->request['blog_allowtrackback'] ? 1 : 0 ;
				$myblog['blog_settings']['approve_trackbacks']	= $this->request['blog_approvetrackbacks'] ? 1 : 0;
				
				if( $myblog['blog_settings']['limitentrysize'] != 0 && $myblog['blog_settings']['limitentrysize'] < 100 )
				{
					$errors[] = $this->lang->words['limitentrysize_error'];
				}
			break;
			
			/* External Blog Settings */
			case 'external':
				if( $this->request['blog_exturl'] )
				{
					if( stripos( $this->request['blog_exturl'], "http://" ) !== 0 && stripos( $this->request['blog_exturl'], "https://" ) !== 0 )
					{
						$this->request['blog_exturl'] =  "http://" . $this->request['blog_exturl'] ;
					}
					
					$save_blog['blog_exturl'] = $this->request['blog_exturl'];
				}
				else
				{
					$errors[] = $this->lang->words['no_exturl_entered'];
				}
			break;
			
			/* Error */
			default:
				$this->registry->output->showError( 'blog_no_permission', 10616, null, null, 403 );
			break;
		}
		
		/* Ping Settings */
		$myblog['blog_settings']['pings'] = array();
		
		if( $this->settings['blog_allow_pingblogs'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'blog_pingservices', 'where' => 'blog_service_enabled=1' ) );
			$this->DB->execute();
			
			while( $service = $this->DB->fetch() )
			{
				$myblog['blog_settings']['pings'][$service['blog_service_key']] = $this->request['blog_ping_'.$service['blog_service_key']] ? 1 : 0;
			}
		}
		
		/* XML-RPC Settings */
		$myblog['blog_settings']['enable_xmlrpc'] = $this->request['blog_enable_xmlrpc'] ? 1 : 0;

		if( $myblog['blog_settings']['enable_xmlrpc'] )
		{
			if( $this->request['blog_xmlrpc_password'] == "" || ( $myblog['blog_settings']['xmlrpc_password'] == "" && $this->request['blog_xmlrpc_password'] == "Iamapassword" ) )
			{
				$errors[] = $this->lang->words['blog_xmlrpc_nopass'];
			}
			else
			{
				if( $this->request['blog_xmlrpc_password'] != "Iamapassword" )
				{
					if( $this->request['blog_xmlrpc_password'] == $this->request['blog_xmlrpc_password2'] )
					{
						$myblog['blog_settings']['xmlrpc_password'] = md5( $this->request['blog_xmlrpc_password'] );
					}
					else
					{
						$errors[] = $this->lang->words['blog_xmlrpc_nomatch'];
					}
				}
			}
		}

		/* Blog Type */
		$save_blog['blog_type']	= $this->request['blog_type'];
		
		/* More blog settings */
		$this->settings['blog_privclub_max'] = intval( $this->settings['blog_privclub_max'] ? intval( $this->settings['blog_privclub_max'] ) : 20 );
		$this->settings['blog_editors_max']  = intval( $this->settings['blog_editors_max']  ? intval( $this->settings['blog_editors_max'] )  : 10 );

		$num_buddies = count( explode( "<br />", $this->request['private_buddies'] ) );
		$num_editors = count( explode( "<br />", $this->request['blog_editors'] ) );
		
		/* Check max private club buddies */
		if( $this->memberData['g_blog_allowprivclub'] && $this->request['blog_view_level'] == 'privateclub' && $num_buddies > $this->settings['blog_privclub_max'] )
		{
			$errors[] = str_replace( "#maxnum#", $this->settings['blog_privclub_max'], $this->lang->words['too_many_buddies'] );
		}
		
		/* Check Max editors */
		if( $this->memberData['g_blog_alloweditors'] && $num_editors > $this->settings['blog_editors_max'] )
		{
			$errors[] = str_replace( "#maxnum#", $this->settings['blog_editors_max'], $this->lang->words['too_many_editors'] );
		}

		/* Found errors? */
		if( count($errors) )
		{
			return $errors;
		}

		/* Private Club */
		$buddies     = array();
		$buddies_arr = array();
		
		if( $this->memberData['g_blog_allowprivclub'] && ( $this->request['blog_view_level'] == 'privateclub' or $this->request['blog_view_level'] == 'friends' ) )
		{
			if ( $this->request['blog_view_level'] == 'privateclub' )
			{
				$this->request['private_buddies'] = $this->memberData['members_display_name'] . '<br />' . $this->request['private_buddies'];
			
				foreach( explode( "<br />", $this->request['private_buddies'] ) as $b )
				{
					if( $b )
					{
						$buddies[] = strtolower( $this->DB->addslashes( $b ) );
					}
				}
	        	
				if( count( $buddies ) )
				{
					/* Query Setup */
					$buddies = implode( "','", $buddies );
	        	
					/* Query */
					$this->DB->build( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_display_name IN ('{$buddies}')" ) );
					$qid = $this->DB->execute();
	        	
					while( $member = $this->DB->fetch($qid) )
					{
						$buddies_arr[] = $member['member_id'];
					}
				}
			}
			else
			{
				$friendData = ipsRegistry::instance()->member()->getProperty('_cache');
				$buddies_arr = array_keys( $friendData['friends'] );
				$buddies_arr[] = $this->memberData['member_id'];
			}
		}
		
		/* Blog Editors */
		$editors_array = array();
		
		if( $this->memberData['g_blog_alloweditors'] )
		{
			$editors = array();

			foreach( explode( "<br />", $this->request['blog_editors'] ) as $b )
			{
				if( $b )
				{
					$editors[] = strtolower( $this->DB->addslashes( $b ) );
				}
			}
			
			if( count( $editors ) )
			{
				/* Query Setup */
				$editors = implode( "','", $editors );
				
				/* Query */
				$this->DB->build( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_display_name IN ('{$editors}')" ) );
				$this->DB->execute();
            	
				while( $member = $this->DB->fetch() )
				{
					$editors_array[] = $member['member_id'];
				}
			}
		}

		$myblog['blog_settings']['editors']	= $editors_array;
		$save_blog['blog_settings']			= serialize( $myblog['blog_settings'] );
		
		/* Let's update the BLOG record */
		$this->DB->update( 'blog_blogs', $save_blog, "blog_id={$myblog['blog_id']}" );
		
		/* Update editor mappings */
		$this->blogFunctions->updateEditorMappings( array_merge( $myblog, $save_blog ), $editors_array );
		
		/* Update private mappings */
		$this->blogFunctions->updatePrivateClubMappings( array_merge( $myblog, $save_blog ), $buddies_arr );

		if( $myblog['blog_view_level'] != $save_blog['blog_view_level'] )
		{
			 $this->blogFunctions->rebuildStats();
		
			/* Rebuild Tag Perms */
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			classes_tags_bootstrap::run( 'blog', 'entries' )->updatePermssionsByParentId( $myblog['blog_id'] );
		}
	
		/* Bounce to the settings screen */
		$this->registry->output->redirectScreen( $this->lang->words['succes_stored_settings'], $this->settings['base_url'] . "app=blog&module=manage", 'false', true, 'manageblog' );
	}
}