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
 * @timestamp           12:03:45
 * @version		$Rev: 10391 $
 *
 */
if (!defined('IN_IPB')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
    exit();
}

class public_portal_ajax_friends extends ipsAjaxCommand {

    /**
     * Friends library
     *
     * @var		object
     */
    protected $friends;
    public $registry;

    public function doExecute(ipsRegistry $registry) {
        $this->registry = $registry;
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/friends.php', 'memberFriendsLib', 'portal');
        $this->friends = new $classToLoad($this->registry);
        $member_id = (int)$this->memberData['member_id'];
        switch ($this->request['do']) {
            case 'get':
                $jsonResponse = $this->friends->getFriends($member_id);
                break;
            case 'suggest':
                $jsonResponse = $this->friends->getSuggest();
                break;
            default:
                $this->returnJsonError( $this->lang->words['notfound'] );
        }

        $this->returnJsonArray($jsonResponse);
    }

}

?>