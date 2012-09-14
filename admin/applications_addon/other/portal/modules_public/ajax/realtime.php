<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 04-abr-2012 -006  $
 * </pre>
 * @filename            status.php
 * @author      $Author: juliobarreraa@gmail.com $
 * @package     PRI
 * @subpackage          
 * @link        http://www.codebit.org
 * @since       04-abr-2012
 * @timestamp           14:35:48
 * @version     $Rev:  $
 *
 */
/**
 * Description of status
 *
 * @author juliobarreraa@gmail.com
 */

class public_portal_ajax_realtime extends ipsAjaxCommand {

    public $registry;
    
    protected $DB;

    public function doExecute(ipsRegistry $registry) {
        $this->registry = $registry;
        $response = '';
        
        switch($this->request['do']){
	        case 'likes':
	          $jsonResponse = $this->__likes();
	        break;
	        case 'comments':
	          $jsonResponse = $this->__comments();
	        break;
        }
        
        return $jsonResponse;
        
    }
    
    private function __likes(){
        $row = $this->DB->buildAndFetch(array(
              'select' => "ri.id, ri.member_id, ri.type, ri.type_id, ri.type",
              'from' => array('reputation_index' => 'ri'),
              'where' => "(ri.type IN ('log_id') and ri.app='portal') and ((UNIX_TIMESTAMP(DATE_ADD(from_unixtime(ri.rep_date),INTERVAL 60 SECOND))) > UNIX_TIMESTAMP(NOW())) and (ri.isread = 0 and ri.member_id<>{$this->memberData['member_id']})",
              'limit' => array(0,1),
              'order' => 'ri.id DESC',
              'add_join' => array(
              	array(
              	    'select' => 'lr.log_reply_id',
              	    'from' => array('log_reply' => 'lr'),
              	    'where' => "ri.type_id=lr.log_id and lr.log_member_id={$this->memberData['member_id']}",
              	    'type' => 'inner',
              	),

                array(
                    'select' => 'pf.friends_id',
                    'from' => array('profile_friends' => 'pf'),
                    'where' => '(pf.friends_member_id = ri.member_id)',
                    'type' => 'inner',
	           ),
              ),
        ));
        $html = '';
        if(!is_array($row)){
	        $row = $this->DB->buildAndFetch(array(
	              'select' => "ri.id, ri.member_id, ri.type, ri.type_id, ri.type",
	              'from' => array('reputation_index' => 'ri'),
	              'where' => "(ri.type IN ('id') and ri.app='portal') and ((UNIX_TIMESTAMP(DATE_ADD(from_unixtime(ri.rep_date),INTERVAL 60 SECOND))) > UNIX_TIMESTAMP(NOW())) and (ri.isread = 0 and ri.member_id<>{$this->memberData['member_id']})",
	              'limit' => array(0,1),
	              'order' => 'ri.id DESC',
	              'add_join' => array(
					array(
						'select' => 'lb.id as log_reply_id, lb.user_id',
						'from' => array('portal_logbook' => 'lb'),
						'where' => "ri.type_id=lb.id and lb.user_id={$this->memberData['member_id']}",
						'type' => 'inner',
					),
	                array(
	                    'select' => 'pf.friends_id',
	                    'from' => array('profile_friends' => 'pf'),
	                    'where' => '(pf.friends_member_id = ri.member_id)',
	                    'type' => 'inner',
		           ),
	              ),
	        ));
        }
        if((int)$row['log_reply_id'] > 0) {
	        $row['member'] = IPSMember::load($row['member_id'], 'all');
	        $this->DB->update( 'reputation_index', array( 'isread' => '1' ), 'id='.$row['id'] );
	        $this->output = $this->registry->getClass('output')->getTemplate('global_other')->notify($row);
	        $html = $this->cleanOutput($this->output);
	    }
	    else{
	    	$html = '';
	    }
        
        return $this->returnJsonArray(array('status' => 'success', 'html' => $html));
    }
    
    private function __comments(){
        //return $this->returnJsonArray(array('status' => 'success', 'html' => 'greatComments'));
        $row = $this->DB->buildAndFetch(array(
          'select' => "lr.log_id, lr.log_reply_id, lr.log_member_id, lr.log_date",
          'from' => array('log_reply' => 'lr'),
          'where' => "lr.isread = 0 and ((UNIX_TIMESTAMP(DATE_ADD(from_unixtime(lr.log_date),INTERVAL 60 SECOND))) > UNIX_TIMESTAMP(NOW())) and lr.log_member_id <> {$this->memberData['member_id']}",
        ));
        

        if(is_array($row)){
            $implicated = $this->DB->buildAndFetch(array(
                  'select' => "lr.log_member_id",
                  'from' => array('log_reply' => 'lr'),
                  'where' => "lr.log_member_id = {$this->memberData['member_id']} and lr.log_reply_id = {$row['log_reply_id']}",
            ));
            if(is_array($implicated)){
		        $row['member'] = IPSMember::load($row['log_member_id'], 'all');
		        $html = $this->registry->getClass('output')->getTemplate('global_other')->notifyComment($row);
		        $this->DB->update( 'log_reply', array( 'isread' => '1' ), 'log_id='.$row['log_id'] );
		        return $this->returnJsonArray(array('status' => 'success', 'html' => $html));
		    }
        }
        return $this->returnJsonArray(array('status' => 'success', 'html' => ''));
    }
}    