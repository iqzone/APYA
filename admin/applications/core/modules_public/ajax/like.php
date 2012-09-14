<?php
/**
 * @file		like.php 	Provides ajax methods for the central like/follow class
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-08-05 20:51:01 -0400 (Fri, 05 Aug 2011) $
 * @version		v3.3.3
 * $Revision: 9373 $
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
class public_core_ajax_like extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* From App */
		$app	= trim( $this->request['f_app'] );
		$area	= trim( $this->request['f_area'] );
		$relid	= intval( $this->request['f_relid'] );
		
		if ( ! $app OR ! $area OR empty( $relid ) )
		{
			trigger_error( "Missing data in " . __FILE__ . ' ' . __LINE__ );
		}
		
		/* Init some data */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( $app, $area );
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_like' ), 'core' );
		
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'setDialogue':
				$this->_setDialogue( $app, $area, $relid );
				break;
			case 'save':
				$this->_save( $relid );
				break;
			case 'unset':
				$this->_unset( $relid );
				break;
			case 'more':
				$this->_more( $relid );
				break;	
        }
    }
    
	/**
     * Show more dialogue
     *
     * @param	integer		$relid		Relationship ID
     * @return	@e void
     */
    protected function _more( $relid )
    {   			
 		/* Fetch data */
 		return $this->returnHtml( $this->_like->render( 'more', $relid ) );
    }
    
	/**
     * Do unset like
     *
     * @param	integer		$relid		Relationship ID
     * @return	@e void
     */
    protected function _unset( $relid )
    {
		if( !$this->memberData['member_id'] )
		{
			return $this->returnNull();
		}

    	/* Set data */
 		$this->_like->remove( $relid, $this->memberData['member_id'] );
 		
 		/* Fetch data */
 		return $this->returnHtml( $this->_like->render( 'summary', $relid ) );
    }
    
	/**
     * Save like
     *
     * @param	integer		$relid		Relationship ID
     * @return	@e void
     */
    protected function _save( $relid )
    {
		if( !$this->memberData['member_id'] )
		{
			return $this->returnNull();
		}

    	$like_notify	= intval( $this->request['like_notify'] );
    	$like_freq		= trim( $this->request['like_freq'] );
    	$like_anon		= intval( $this->request['like_anon'] );

    	/* Set data */
 		$this->_like->add( $relid, $this->memberData['member_id'], array( 'like_notify_do' => $like_notify, 'like_notify_freq' => $like_freq ), $like_anon );
 		
 		/* Fetch data */
 		return $this->returnHtml( $this->_like->render( 'summary', $relid ) ); 
		/* This used to force response as UTF-8, but that caused this bug: @link http://community.invisionpower.com/tracker/issue-32255-character-conversion-bug-with-follow-this-x */
    }
    
    /**
     * Show set form
     *
     * @param	string		$app		Application
     * @param 	string		$area		Area
     * @param	integer		$relid		Relationship ID
     * @return	@e void
     */
    protected function _setDialogue( $app, $area, $relid )
    {
		if( !$this->memberData['member_id'] )
		{
			return $this->returnNull();
		}

 		$data = $this->_like->getDataForSetDialogue( $relid );
 		
 		return $this->returnHtml( $this->registry->getClass('output')->getTemplate('global_other')->likeSetDialogue( $app, $area, $relid, $data ) );
    }
}