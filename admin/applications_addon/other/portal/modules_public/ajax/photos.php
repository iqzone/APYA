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

class public_portal_ajax_photos extends ipsAjaxCommand {

    public $registry;

    public function doExecute(ipsRegistry $registry) {
        $this->registry = $registry;
        switch($this->request['do']) {
	        case 'showphoto':
	        default:
	            $this->__showPhoto((int)$this->request['status_id']);
        }
    }
    
    private function __showPhoto($status_id) {
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/timeline.php', 'timelineClass', 'portal');
        $this->timeline = new $classToLoad();
    	$photo = $this->timeline->getPhotografy($status_id);
        $comment_list = $this->fetchAllReplies($status_id);

        $this->output = $this->registry->getClass('output')->getTemplate('portal')->showphoto($photo, $comment_list);
        return $this->returnJsonArray(array('status' => 'success', 'html' => $this->output));
    }
    
    /**
     * Fetch all replies to a status
     * Default filters are sorted on reply_date ASC
     *
     * @param   mixed   [Array of member data OR member ID INT for member updating their status - will use ->getAuthor() if null]   
     * @param   array   Array of sort/filter data ( member_id [int], latest_only [0,1], offset [int], limit [int], unix_cutoff [int], sort_dir [asc,desc], sort_field [string] )
     */
    public function fetchAllReplies( $status=null, $filters=array() )
    {
        /* Load the class */
        if (!$this->registry->isClassLoaded('repCache')) {
            $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache');
            $this->registry->setClass('repCache', new $classToLoad());
        }
        //$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
        $where    = array();
        $replies = array();
        
        $sort_dir   = ( $filters['sort_dir'] == 'desc' ) ? 'desc' : 'asc';
        $sort_field = ( isset( $filters['sort_field'] ) ) ? $filters['sort_field'] : 'log_date';
        $offset     = ( isset( $filters['offset'] ) ) ? intval( $filters['offset'] ) : 0;
        $limit      = ( isset( $filters['limit'] ) ) ? intval( $filters['limit'] ) : 100;
        /* Grab them */
        $this->DB->build( array( 'select'   => 's.*',
                                 'from'     => array( 'log_reply' => 's' ),
                                 'where'    => 's.log_reply_id=' . intval($status),
                                 'order'    => 's.' . $sort_field . ' ' . $sort_dir,
                                 'limit'    => array( $offset, $limit ),
                                 'add_join' => array(array(  'select'   => 'm.*',
                                                             'from'     => array( 'members' => 'm' ),
                                                             'where'    => 'm.member_id=s.log_member_id',
                                                             'type'     => 'left' ),
                                                     array(  'select'   => 'pp.*',
                                                             'from'     => array( 'profile_portal' => 'pp' ),
                                                             'where'    => 'pp.pp_member_id=m.member_id',
                                                             'type'     => 'left' ) ) ) );
                                                             
        $o = $this->DB->execute();
        
        while( $row = $this->DB->fetch( $o ) )
        {
            /* Format some data */
            $row['reply_date_formatted'] = $this->registry->getClass('class_localization')->getDate( $row['log_date'], 'SHORT' );
            $row['_canDelete']           = $this->canDeleteReply( $this->getAuthor(), $row, $status );
            
            /* Format member */
            $row = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
            $row['reply_date_formatted'] = $row['log_date'];

	        $joins = array(array('select' => 'm.*', 'from' => array('members' => 'm'), 'where' => 'm.member_id=lr.log_member_id', 'type' => 'left'));
	        $joins[] = $this->registry->getClass('repCache')->getTotalRatingJoin('log_id', $row['log_id'], 'portal');
	        $joins[] = $this->registry->getClass('repCache')->getUserHasRatedJoin('log_id', $row['log_id'], 'portal');
			$data = $this->DB->buildAndFetch(array('select' => 'lr.*',
					'from' => array('log_reply' => 'lr'),
					'where' => "lr.log_id={$row['log_id']}",
					'add_join' => $joins
			));
	        
	        if($this->settings['reputation_enabled'] && $this->registry->repCache->isLikeMode()){
	            $row['like'] = $this->registry->repCache->getLikeFormatted(array('app' => 'portal', 'type' => 'log_id', 'log_id' => $data['log_id'], 'rep_like_cache' => $data['rep_like_cache']));
	        }
	        
            
            $replies[ $row['log_id'] ] = $row;
        }
        
        /* Phew */
        return $replies;
    }
}