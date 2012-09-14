<?php
/**
 * @file		warnings.php 	Warnings extension for members
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $ (Original: Mark)
 * @since		-
 * $LastChangedDate: 2010-10-14 13:11:17 -0400 (Thu, 14 Oct 2010) $
 * @version		v3.3.3
 * $Revision: 477 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		warnings_members
 * @brief		Warnings Extension for members
 */
class warnings_members
{
	/**
	 * Get Content URL
	 *
	 * @param	array		$warning		Row from members_warn_logs
	 * @return	@e array	array( url => URL to the content the warning came from, title => Title )
	 */
	public function getContentUrl( $warning )
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_profile' ), 'members' );
		
		if ( $warning['wl_content_id1'] )
		{
			$post = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'message_posts', 'where' => "msg_id=" . intval( $warning['wl_content_id1'] ) ) );
			
			if ( ! empty($post['msg_topic_id']) )
			{
				$topic = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'message_topics', 'where' => "mt_id={$post['msg_topic_id']}" ) );
				
				if ( ! empty($topic['mt_id']) )
				{
					ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'message_topic_user_map', 'where' => "map_topic_id={$post['msg_topic_id']}" ) );
					ipsRegistry::DB()->execute();
					
					while ( $row = ipsRegistry::DB()->fetch() )
					{
						if ( $row['map_user_id'] == ipsRegistry::member()->getProperty('member_id') )
						{
							return array( 'url' => ipsRegistry::getClass('output')->buildUrl( "app=members&amp;module=messaging&amp;section=view&amp;do=findMessage&amp;topicID={$topic['mt_id']}&amp;msgID={$post['msg_id']}" ), 'title' => $topic['mt_title'] );
						}
					}
					
					return array( 'url' => ipsRegistry::getClass('output')->buildUrl( "app=core&module=reports&section=reports&do=showMessage&topicID={$topic['mt_id']}&msg={$post['msg_id']}" ), 'title' => $topic['mt_title'] );
				}
			}
		}
		else
		{
			$member = IPSMember::load( $warning['wl_member'] );
			
			if ( ! empty($member['member_id']))
			{
				return array( 'url' => ipsRegistry::getClass('output')->buildSEOUrl( "showuser={$member['member_id']}", 'public', $member['members_seo_name'], 'showuser' ), 'title' => ipsRegistry::getClass('class_localization')->words['warnings_profile'] );
			}
		}
	}
}