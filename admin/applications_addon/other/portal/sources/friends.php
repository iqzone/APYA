<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 2012-03-05 16:36:15 -0500 (Mon, 05 Mar 2012) $
 * </pre>
 * @filename            friends.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		hunter
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		03-abr-2012
 * @timestamp           12:18:52
 * @version		$Rev: 10391 $
 *
 */
class memberFriendsLib {

    public $DB;
    public $registry;

    public function __construct(ipsRegistry $registry) {
        $this->DB = $registry->DB();
        $this->registry = $registry;
        $this->memberData = & $this->registry->member()->fetchMemberData();
    }

    public function getFriends($member_id) {
        /* Are we already friends? */
        $this->DB->build(array(
            'select' => 'pf.friends_id',
            'from' => array('profile_friends' => 'pf'),
            'where' => "friends_member_id={$member_id} AND friends_approved=1",
            'add_join' => array(array(
                    'select' => 'm.members_display_name, m.member_id, m.members_seo_name',
                    'from' => array('members' => 'm'),
                    'where' => 'pf.friends_friend_id = m.member_id',
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
        while ($row = $this->DB->fetch()) {
            $row = IPSMember::buildProfilePhoto($row);
            $rows[] = array(
                'id' => $row['member_id'],
                'name' => $row['members_display_name'],
                'avatar' => $row['pp_mini_photo'],
                'type' => $this->_getType(),
            );
            unset($row);
        }
        return $rows;
    }
    
    private function _getType() {
        return 'contact';
    }

}

?>