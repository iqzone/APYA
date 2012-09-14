<?php
/**
 * @file		like.php 	Provides ajax methods for the central like/follow class
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
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
 *
 * @class		public_core_ajax_like
 * @brief		Provides ajax methods for the central like/follow class
 */
class public_core_global_like extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Init some data */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_like' ), 'core' );
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'unsubscribe':
				$html = $this->_unsubscribe();
			break;
			case 'doUnsubscribe':
				$html = $this->_doUnsubscribe();
			break;
        }
        
        /* Output */
		$this->registry->output->addContent( $html );
		$this->registry->output->sendOutput();	
    }
    
    /**
     * Perform the unsubscribe
     *
     * @return	@e void
     */
    protected function _doUnsubscribe()
    {
    	$like_rel_id = trim( $this->request['like_rel_id'] );
    	$like_area   = trim( $this->request['like_area'] );
    	$like_app    = trim( $this->request['like_app'] );
    	$like_id	 = trim( $this->request['like_id'] );
    	
   	 	/* Member? */
 		if ( ! $this->memberData['member_id'] )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-d-1' );
 		}
 		
    	if ( $this->request['auth_key'] !=  $this->member->form_hash )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-d-2' );
 		}
 		
 		/* Think we're safe... */
 		$this->_like = classes_like::bootstrap( $like_app, $like_area );
 		
 		/* Get data */
 		$data = $this->_like->getDataByRelationshipId( $like_rel_id, false );
 		
 		if ( $data[ $this->memberData['member_id'] ]['like_member_id'] != $this->memberData['member_id'] )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-d-3' );
 		}
 		
 		/* Remove it */
 		$this->_like->remove( $like_rel_id, $this->memberData['member_id'] );
		
 		/* Boing it */
 		$this->registry->getClass('output')->redirectScreen( $this->lang->words['pg_unlike_done'], $this->registry->getClass('output')->buildUrl( 'app=core&amp;module=search&amp;do=followed', 'public' ) );
    }
    
	/**
     * Show unsubscribe dialogue
     *
     * @return	@e void
     */
    protected function _unsubscribe()
    {
    	/* Fetch data */
    	$key = trim( IPSText::base64_decode_urlSafe( $this->request['key'] ) );
    	
    	list( $app, $area, $relId, $likeMemberId, $memberId, $email ) = explode( ';', $key );
    	
    	/* Sanitize */
    	$relId        = intval( $relId );
    	$likeMemberId = intval( $likeMemberId );
    	$memberId     = intval( $memberId );
    	
    	$app          = IPSText::alphanumericalClean( $app );
    	$area         = IPSText::alphanumericalClean( $area );
    	
 		/* Member? */
 		if ( ! $this->memberData['member_id'] )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-1' );
 		}
 		
    	if ( ! $app || ! $area || ! $relId )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-1' );
 		}
 		
   		if ( ( $memberId != $likeMemberId ) || ( $memberId != $this->memberData['member_id'] ) )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-2' );
 		}
 		
 		if ( $email != $this->memberData['email'] )
 		{
 			$this->registry->output->showError( 'no_permission', 'pcgl-3' );
 		}
 		
 		/* Think we're safe... */
 		try
 		{
 			$this->_like = classes_like::bootstrap( $app, $area );
 		}
 		catch ( Exception $ex )
 		{
 			$this->registry->output->showError('no_permission', 'pcgl-4');
 		}
 		
 		/* Get data */
 		$data = $this->_like->getDataByRelationshipId( $relId, false );
 		
 		if ( ! is_array( $data[ $this->memberData['member_id'] ] ) )
 		{
 			$this->registry->output->showError( $this->lang->words['pg_no_longer_following'], 'pcgl-4' );
 		}
 		
 		/* Get meta */
 		$meta = $this->_like->getMeta( $relId );
 		
 		/* Display box, then */
 		$this->registry->output->setTitle( $this->lang->words['pg_unfollow_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
		$this->registry->output->addNavigation( $this->lang->words['pg_unfollow_title'], '' );
		
		return $this->registry->output->getTemplate('global_other')->followUnsubscribe( $data, $meta );
    }
    

}