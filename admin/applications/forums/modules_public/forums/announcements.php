<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Announcement View
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
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_announcements extends ipsCommand
{
	/**
	* Class entry point
	*
	* @param	object		Registry reference
	* @return	@e void		[Outputs to screen/redirects]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		$announceID = intval( $this->request['announce_id'] );
		
		if ( ! $announceID )
		{
			$this->registry->getClass('output')->showError( 'announcement_id_missing', 10327, null, null, 404 );
		}
		
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topic' ) );
		
		//-----------------------------------------
		// Get the announcement
		//-----------------------------------------
		
		$_post_joins = array(
							array( 'select'	=> 'm.*',
									'from'	=> array( 'members' => 'm' ),
									'where'	=> 'm.member_id=a.announce_member_id',
									'type'	=> 'left'
								),
							array( 'select'	=> 'pp.*',
									'from'	=> array( 'profile_portal' => 'pp' ),
									'where'	=> 'm.member_id=pp.pp_member_id',
									'type'	=> 'left'
								),
							);
							
		/* Add custom fields join? */
		if( $this->settings['custom_profile_topic'] == 1 )
		{
			$_post_joins[] = array( 
									'select' => 'pc.*',
									'from'   => array( 'pfields_content' => 'pc' ),
									'where'  => 'pc.member_id=m.member_id',
									'type'   => 'left'
								);
		}	
		
		$announce = $this->DB->buildAndFetch( array( 'select'	=> 'a.*',
															'from'		=> array( 'announcements' => 'a' ),
															'where'		=> 'a.announce_id=' . $announceID,
															'add_join'	=> $_post_joins
													)		);
		
		if ( ! $announce['announce_id'] or ! $announce['announce_forum'] )
		{
			$this->registry->getClass('output')->showError( 'announcement_id_missing', 10328, null, null, 404 );
		}

		//-----------------------------------------
		// Permission to see it?
		//-----------------------------------------
		
		$pass = 0;
		
		if ( $announce['announce_forum'] == '*' )
		{
			$pass = 1;
		}
		else
		{
			$tmp = explode( ",", $announce['announce_forum'] );
			
			if ( ! is_array( $tmp ) and ! ( count( $tmp ) ) )
			{
				$pass = 0;
			}
			else
			{
				foreach( $tmp as $id )
				{
					if ( $this->registry->getClass('class_forums')->forum_by_id[ $id ]['id'] )
					{
						if ( IPSMember::checkPermissions( 'read', $id ) )
						{
							$pass = 1;
							break;
						}
					}
				}
			}
		}
		
		if ( $pass != 1 )
		{
			$this->registry->getClass('output')->showError( 'announcement_no_perms', 2035, true, null, 403 );
		}
		
		if( ! $announce['announce_active'] AND ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->getClass('output')->showError( 'announcement_no_perms', 2036, true, null, 403 );
		}

		//-----------------------------------------
		// Parsey parsey!
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html				= $announce['announce_html_enabled'] ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $announce['announce_html_enabled'] ? $announce['announce_nlbr_enabled'] : 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'announcements';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $announce['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $announce['mgroup_others'];

		$announce['announce_post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $announce['announce_post'] );
        
		$member = IPSMember::buildDisplayData( $announce, array( 'signature' => 1, 'customFields' => 1, 'checkFormat' => 1, 'cfLocation' => 'topic' ) );
		
		if ( $member['member_id'] )
		{
			$member['_members_display_name'] = "<a href='{$this->settings['_base_url']}showuser={$member['member_id']}'>{$member['members_display_name_short']}</a>";
		}        
		
		if ( $announce['announce_start'] and $announce['announce_end'] )
		{
			$announce['running_date'] = sprintf( $this->lang->words['announce_both'], gmstrftime( '%x', $announce['announce_start'] ), gmstrftime( '%x', $announce['announce_end'] ) );
		}
		else if ( $announce['announce_start'] and ! $announce['announce_end'] )
		{
			$announce['running_date'] = sprintf( $this->lang->words['announce_start'], gmstrftime( '%x', $announce['announce_start'] ) );
		}
		else if ( ! $announce['announce_start'] and $announce['announce_end'] )
		{
			$announce['running_date'] = sprintf( $this->lang->words['announce_end'], gmstrftime( '%x', $announce['announce_end'] ) );
		}
		else
		{
			$announce['running_date'] = '';
		}
		
		$template = $this->registry->getClass('output')->getTemplate('topic')->announcement_show($announce, $member);
		
		//-----------------------------------------
		// Update hits
		//-----------------------------------------
		
		$this->DB->build( array( 'update' => 'announcements', 'set' => 'announce_views=announce_views+1', 'where' => "announce_id=".$announceID ) );
		$this->DB->execute();
		
		if ( $this->request['f'] )
		{
			$nav = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $this->request['f'] );
		}
		
		$nav[] = array( $announce['announce_title'], "" );
			
		foreach( $nav as $_id => $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}
		
		//-----------------------------------------
		// Have we got an SEO title?
		//-----------------------------------------
		
		if ( $announce['announce_seo_title'] == '' )
		{
			$announce['announce_seo_title'] = IPSText::makeSeoTitle( $announce['announce_title'] );
			
			$this->DB->update( 'announcements', array( 'announce_seo_title' => $announce['announce_seo_title'] ), "announce_id={$announceID}" );
		}
				
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $announce['announce_title'] . ' - ' . $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $template );
		$this->registry->getClass('output')->sendOutput();
		
	}

	/**
	 * Hides announcements that have passed their expire date
	 *
	 * @return	@e void
	 */
	public function announceRetireExpired()
	{
		//-----------------------------------------
		// Update all out of date 'uns
		//-----------------------------------------
		
		$this->DB->update( 'announcements', array( 'announce_active' => 0 ), 'announce_end != 0 AND announce_end < '.time() );
		
		$this->announceRecache();
	}

    /**
	 * Rebuilds the attachment cache
	 *
	 * @author	Matt Mecham
	 * @return	@e void
	 */
	public function announceRecache()
	{
		$cache = array();
		
		$this->DB->build( array('select'	=> 'a.*',
								'from'		=> array( 'announcements' => 'a' ),
								'order'		=> 'a.announce_end DESC',
								'add_join'	=> array( array( 'select' => 'm.member_id, m.name, m.members_display_name, m.members_seo_name',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=a.announce_member_id',
															 'type'   => 'left' ) )
								)		);
		$outer = $this->DB->execute();
		
		while ( $r = $this->DB->fetch($outer) )
		{
			/* Fix missing seo titles */
			if ( $r['announce_seo_title'] == '' )
			{
				$r['announce_seo_title'] = IPSText::makeSeoTitle( $r['announce_title'] );
				
				$this->DB->update( 'announcements', array( 'announce_seo_title' => $r['announce_seo_title'] ), "announce_id={$r['announce_id']}" );
			}
			
			$start_ok = 0;
			$end_ok   = 0;
			
			if ( ! $r['announce_active'] )
			{
				continue;
			}

			if ( ! $r['announce_start'] )
			{
				$start_ok = 1;
			}
			else if ( $r['announce_start'] <= mktime( 12, 0, 0 ) )
			{
				$start_ok = 1;
			}
			
			if ( ! $r['announce_end'] )
			{
				$end_ok = 1;
			}
			else if ( $r['announce_end'] >= mktime( 12, 0, 0 ) )
			{
				$end_ok = 1;
			}

			if ( $start_ok and $end_ok )
			{
				$cache[ $r['announce_id'] ] = array(  'announce_id'			=> $r['announce_id'],
													  'announce_title'		=> $r['announce_title'],
													  'announce_seo_title'	=> $r['announce_seo_title'],
													  'announce_start'		=> $r['announce_start'],
													  'announce_end'		=> $r['announce_end'],
													  'announce_forum'		=> $r['announce_forum'],
													  'announce_views'		=> $r['announce_views'],
													  'member_id'			=> $r['member_id'],
													  'member_name'			=> $r['members_display_name'],
													  'members_seo_name'	=> $r['members_seo_name']
													);
			}
		}

		$this->registry->cache()->setCache( 'announcements', $cache,  array( 'array' => 1, 'donow' => 1 ) );
 	}
}