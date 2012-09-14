<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 07-may-2012 -006  $
 * </pre>
 * @filename            news.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		07-may-2012
 * @timestamp           17:35:32
 * @version		$Rev:  $
 *
 */

/**
 * Description of news
 *
 * @author juliobarreraa@gmail.com
 */
class public_portal_ajax_news extends ipsAjaxCommand {

    public function doExecute(ipsRegistry $registry) {
        //print_r($this->request);
        $takeAction = trim(strtolower($this->request['do']));
        $response = null;
        switch ($takeAction) {
            case 'load':
                $response = $this->__newsLoad();
                break;
            default:
                $response = $this->__newsCount();
        }

        return $response;
    }

    private function __newsCount() {
	    if((int)$_GET['showuser'] || (int)$_GET['user']) {
	    	$user_id = (int)$_GET['showuser'];
	    	if(!$user_id)
	    		$user_id = (int)$_GET['user'];
	    }
        $userProfile = '';
        if($user_id){
            $userProfile = "AND (pl.user_id = {$user_id})";
        }
        
        
        $rows = $this->DB->buildAndFetch(array(
            'select' => 'count(pl.id) as count',
            'from' => array('portal_logbook' => 'pl'),
            'where' => "pl.id > " . (int) $this->request['last'] . " AND (pl.user_id = ".(int)$this->memberData['member_id']." OR pf.friends_id <> 'NULL'".") {$userProfile}",
            'order' => 'pl.id DESC',
            'add_join' => array(array(
                    'select' => '',
                    'from' => array('portal_tables_conf' => 'ct'),
                    'where' => 'pl.conf_table_id=ct.id',
                    'type' => 'inner'
                ),
                array(
                    'select' => '',
                    'from' => array('profile_friends' => 'pf'),
                    'where' => '(pl.user_id = pf.friends_member_id AND (pf.friends_friend_id = '.(int)$this->memberData['member_id'].'))',
                    'type' => 'inner',
            )),
                ));

        $news = false;

        if ((int) $rows['count'] > (int) $this->request['countItems']) {
            $news = true;
        }
        return $this->returnJsonArray(array('status' => 'success', 'news' => $rows['count'], 'change' => $news));
    }

    private function __newsLoad() {
        //Load language
        $this->lang->loadLanguageFile(array('public_portal'), 'portal');

        //Load library status load
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/timeline.php', 'timelineClass', 'portal');

        $st = intval($this->request['last']);

        $this->timeline = new $classToLoad($st, true);

        $rows = $this->timeline->getStatus(); //Get Status ajax

        $status = $this->registry->getClass('output')->getTemplate('portal')->statusAjax($rows);
        return $this->returnJsonArray(array('status' => 'success', 'html' => $status));
    }

}

?>
