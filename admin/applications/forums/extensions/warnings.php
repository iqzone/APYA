<?php
/**
 * @file		warnings.php 	Warnings extension for forums
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $ (Original: Mark)
 * @since		-
 * $LastChangedDate: 2012-04-05 12:35:31 -0400 (Thu, 05 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10571 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		warnings_forums
 * @brief		Warnings Extension for forums
 */
class warnings_forums
{
	/**
	 * Get Content URL
	 *
	 * @param	array		$warning		Row from members_warn_logs
	 * @return	@e array	array( url => URL to the content the warning came from, title => Title )
	 */
	public function getContentUrl( $warning )
	{	
		if ( is_numeric( $warning['wl_content_id1'] ) )
		{
			$post = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'posts', 'where' => "pid={$warning['wl_content_id1']}" ) );
			
			if ( ! empty($post['topic_id']) )
			{
				$topic = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid={$post['topic_id']}" ) );
				
				if ( ! empty($topic['tid']) )
				{
					return array( 'url' => ipsRegistry::getClass('output')->buildSEOUrl( "showtopic={$topic['tid']}&findpost={$post['pid']}", 'public', $topic['title_seo'], 'showtopic' ), 'title' => $topic['title'] );
				}
			}
		}
		elseif ( $warning['wl_content_id1'] == 'announcement' )
		{
			$announcement = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'announcements', 'where' => "announce_id=" . intval( $warning['wl_content_id2'] ) ) );
			
			if ( ! empty($announcement['announce_id']) )
			{
				return array( 'url' => ipsRegistry::getClass('output')->buildSEOUrl( "showannouncement={$announcement['announce_id']}", 'public', IPSText::makeSeoTitle( $announcement['announce_title'] ), 'showannouncement' ), 'title' => $announcement['announce_title'] );
			}
		}
		
		return NULL;
	}
}