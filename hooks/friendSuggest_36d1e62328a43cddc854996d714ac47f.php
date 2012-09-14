<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-may-2012 -006  $
 * </pre>
 * @filename            friendSuggest.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		09-may-2012
 * @timestamp           13:58:52
 * @version		$Rev:  $
 *
 */

/**
 * Description of friendSuggest
 *
 * @author juliobarreraa@gmail.com
 */
class friendSuggest {

    //Private
    private $recommend; //Instance of class suggest
    //Protected
    protected $registry;
    protected $memberData;
    //Public
    public $lang;

    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->recommend = new suggest($this->registry);
        $this->registry->class_localization->loadLanguageFile(array('public_portal'), 'portal'); //Load language
        $this->memberData = & $this->registry->member()->fetchMemberData(); //Get member info
    }

    public function getOutput() {
        $members = array();
        $member_id = (int) $this->memberData['member_id'];
        if ($member_id) {
            $members = $this->recommend->getSuggest($member_id);
        }
        return $this->registry->output->getTemplate('portal')->hookSuggest($members);
    }

}

class suggest {

    //Private
    private $DB;
    //Protected
    protected $registry;
    protected $settings;

    public function __construct(ipsRegistry $ipsRegistry) {
        $this->DB = $ipsRegistry->DB();
        $this->registry = $ipsRegistry;
        $this->settings = & $this->registry->fetchSettings(); //Get settings default $this->settings['suggest_max_friends']
    }

    public function getSuggest($member_id) {
        $row = $this->DB->buildAndFetch(array(
            'select' => 'pf.friends_friend_id',
            'from' => array('profile_friends' => 'pf'),
            'where' => 'pf.friends_member_id = ' . $member_id . ' AND pf.friends_approved = 1',
            'order' => 'rand()',
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
                        'where' => 'pf.friends_friend_id = m.member_id AND pf.friends_friend_id <> ' . $member_id,
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
	                            'type' => 'inner'
	                        ),
	                    )
	                ));
	
	                $this->DB->execute();
	
	
	                while($item = $this->DB->fetch()) 
	                {
	                    $usersfriend[] = $item['friends_friend_id'];
	                }
	
	
	
	                $row = IPSMember::buildProfilePhoto($member);
	                $row['common'] = count(array_intersect($users, $usersfriend));
	                $rows[] = $row; //Build row within $member
	                unset($row);
	            }
            }
            return $rows;
        }
        return array();
    }

}

?>
