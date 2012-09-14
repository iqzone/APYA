<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile Plugin Library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profile_friends extends profile_plugin_parent
{
	const FRIENDS_PER_PAGE = 100;

	/**
	 * Feturn HTML block
	 *
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! is_array( $member ) OR ! count( $member ) )
		{
			return $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_aboutme_to_show' );
		}
		
		$friends	= array();

		//-----------------------------------------
		// Grab the friends
		//-----------------------------------------
		
		/* How many friends do we have? */
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as dracula', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $member['member_id'] . ' AND friends_approved=1' ) );
		
		/* Sort out pagination */
		$st = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$pagination = $this->registry->output->generatePagination( array( 
			'totalItems'		=> $count['dracula'],
			'itemsPerPage'		=> self::FRIENDS_PER_PAGE,
			'currentStartValue'	=> $st,
			'baseUrl'			=> "showuser={$member['member_id']}&amp;tab=friends",
			'seoTemplate'		=> 'showuser',
			'seoTitle'			=> $member['members_seo_name'],
			)	);
			
		/* Get em! */
		$queryData = array( 'select'		=> 'f.*',
							 'from'			=> array( 'profile_friends' => 'f' ),
							 'where'		=> 'f.friends_member_id=' . $member['member_id'] . ' AND f.friends_approved=1',
							 'add_join'		=> array(
												  1 => array( 'select' => 'pp.*',
															  'from'   => array( 'profile_portal' => 'pp' ),
															  'where'  => 'pp.pp_member_id=f.friends_friend_id',
															  'type'   => 'left' ),
											 	  2 => array( 'select' => 'm.*',
															  'from'   => array( 'members' => 'm' ),
															  'where'  => 'm.member_id=f.friends_friend_id',
															  'type'   => 'left' ) 
												) 
								);
		// Ordering is bad because it causes a filesort, but if they have more than 100 members, we're going to have
		// to order so we can paginate
		if ( $count['dracula'] > self::FRIENDS_PER_PAGE )
		{
			$queryData['order'] = 'm.members_display_name';
			$queryData['limit'] = array( $st, self::FRIENDS_PER_PAGE );
		}
		
		$this->DB->build( $queryData );
		$outer	= $this->DB->execute();
		
		//-----------------------------------------
		// Get and store...
		//-----------------------------------------
		
		while( $row = $this->DB->fetch($outer) )
		{
			if( $row['member_id'] )
			{
				$friends[ $row['members_display_name'] ]	= IPSMember::buildDisplayData( $row, 0 );
			}
		}
		
		ksort($friends);
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->tabFriends( $friends, $member, $pagination );
		
		//-----------------------------------------
		// Macros...
		//-----------------------------------------
		
		$content = $this->registry->output->replaceMacros( $content );
		
		//-----------------------------------------
		// Return content..
		//-----------------------------------------
		
		return $content ? $content : $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_aboutme_to_show' );
	}
	
}