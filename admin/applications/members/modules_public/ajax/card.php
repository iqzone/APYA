<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile AJAX hCard
 * Last Updated: $Date: 2012-05-30 13:28:08 -0400 (Wed, 30 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10824 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_card extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( ! $this->memberData['g_mem_info'] )
 		{
 			$this->returnString( 'error' );
		}
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile', 'public_online' ), 'members' );
		
		/* Got a valid member? */
		$member_id = intval( $this->request['mid'] );

		if( empty($member_id) )
		{
			$this->returnString( 'error' );
		}
		
		$member = IPSMember::load( $member_id, 'profile_portal,pfields_content,sessions,groups,basic', 'id' );
		
		if( empty($member['member_id']) )
		{
			$this->returnString( 'error' );
		}
		
		$member = IPSMember::buildDisplayData( $member, array( 'customFields' => 1, 'cfSkinGroup' => 'profile', 'spamStatus' => 1 ) );
		$member = IPSMember::getLocation( $member );
		
		$board_posts = $this->caches['stats']['total_topics'] + $this->caches['stats']['total_replies'];
		
		if( $member['posts'] and $board_posts  )
		{
			$member['_posts_day'] = round( $member['posts'] / ( ( time() - $member['joined']) / 86400 ), 2 );
	
			# Fix the issue when there is less than one day
			$member['_posts_day'] = ( $member['_posts_day'] > $member['posts'] ) ? $member['posts'] : $member['_posts_day'];
			$member['_total_pct'] = sprintf( '%.2f', ( $member['posts'] / $board_posts * 100 ) );
		}
		
		$member['_posts_day'] = floatval( $member['_posts_day'] );
		
		/* Load status class */
		if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
			$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		/* Fetch */
		$member['_status'] = $this->registry->getClass('memberStatus')->fetch( $this->memberData, array( 'member_id' => $member['member_id'], 'limit' => 1 ) );
		
		if ( is_array( $member['_status'] ) AND count( $member['_status'] ) )
		{
			$member['_status'] = array_pop( $member['_status'] );
		}
		
		/* Reputation */
		if ( $this->settings['reputation_protected_groups'] )
		{
			if ( in_array( $member['member_group_id'], explode( ",", $this->settings['reputation_protected_groups'] ) ) )
			{
				$this->settings['reputation_show_profile'] = false;
			}
		}
		
		$commonFriends = $this->__getCommonFriends((int)$this->memberData['member_id']);
		$commons = array();
		if(count($commonFriends)) {
			$commons = $commonFriends[0]['common'];
		}
		$this->returnHtml( $this->registry->getClass('output')->getTemplate('profile')->showCard( $member, 0, $commons ) );
	}
	
	private function __getCommonFriends($member_id) {
        $row = $this->DB->buildAndFetch(array(
            'select' => 'pf.friends_friend_id',
            'from' => array('profile_friends' => 'pf'),
            'where' => '(pf.friends_member_id = ' . $member_id . ' AND pf.friends_approved = 1) AND pf.friends_friend_id = '. (int)$this->request['mid'],
            'limit' => '1',
        ));
        //you have friends with friends?
        if ($row) {
            ipsRegistry::DB()->allow_sub_select = 1;
            $this->DB->build(array(
            	'select' => 'friends_friend_id',
            	'from' => array('profile_friends' => 'pf'),
            	'where' =>  'friends_member_id=' . $member_id,
            ));
            
            $this->DB->execute();
            
            while($item = $this->DB->fetch()) 
            {
	            $users[] = $item['friends_friend_id'];
            }
            

            
            //Get friends for $member_id
            $this->DB->build(array(
                'select' => 'pf.friends_id',
                'from' => array('profile_friends' => 'pf'),
                'where' => 'pf.friends_friend_id NOT IN ('.join(',', $users).') AND (pf.friends_member_id = ' . $row['friends_friend_id'] . ') AND pf.friends_approved = 1',
                'limit' => array(0, (int) $this->settings['suggest_max_friends']),
                'order' => 'rand()',
                'add_join' => array(array(
                        'select' => 'm.members_display_name, m.member_id, m.members_seo_name',
                        'from' => array('members' => 'm'),
                        'where' => 'pf.friends_friend_id = m.member_id AND pf.friends_friend_id = ' . $member_id,
                        'type' => 'inner'
                    ),
                    array(
                        'select' => 'pp.pp_thumb_photo, pp.pp_main_photo, pp.pp_photo_type',
                        'from' => array('profile_portal' => 'pp'),
                        'where' => "m.member_id = pp.pp_member_id",
                    ),
                ),
            ));

            $this->DB->execute();
            
            $rows = array();

            while($member = $this->DB->fetch()) {
                $members[] = $member;
            }
            
            if(is_array($members)) {
	            foreach($members as $member) {
	                $this->DB->build(array(
	                    'select' => 'friends_friend_id',
	                    'from' => array('profile_friends' => 'pf'),
	                    'where' => 'friends_member_id = ' . $member['member_id'],
	                    'add_join' => array(array(
	                            'select' => 'm.members_display_name, m.member_id, m.members_seo_name',
	                            'from' => array('members' => 'm'),
	                            'where' => 'pf.friends_friend_id = m.member_id',
	                            'type' => 'inner',
			                    array(
			                        'select' => 'pp.pp_thumb_photo, pp.pp_main_photo, pp.pp_photo_type',
			                        'from' => array('profile_portal' => 'pp'),
			                        'where' => "m.member_id = pp.pp_member_id",
			                    ),
	                        ),
	                    )
	                ));
	
	                $this->DB->execute();
	
	
	                while($item = $this->DB->fetch()) 
	                {
	                    $usersfriend[] = $item['friends_friend_id'];
	                }
	
	                $row = IPSMember::buildProfilePhoto($member);
	                $userIntersect = array_intersect($users, $usersfriend);
	                
	                
	                $row['common'] = array();
	                foreach($userIntersect as $common) {
		                $memberLoad = IPSMember::load($common);
		                $memberLoad = IPSMember::buildProfilePhoto($memberLoad);
		                $row['common'][] = $memberLoad;
	                }
	                
	                $rows[] = $row; //Build row within $member
	                unset($row);
	            }
            }
            return $rows;
        }
        return array();
	}
}