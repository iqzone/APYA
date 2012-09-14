<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Reputation
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_reputation extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'add_rating':
				$this->_doRating();
			break;
			
			case 'more':
				$this->_more();
			break;
			
			case 'view':
				$this->_viewRep();
			break;
		}
		
		exit();
	}
	
	/**
	 * Displays reputation popup
	 *
	 * @return	@e void
	 */
	protected function _viewRep()
	{
		$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
		
		if ( !$this->memberData['gbw_view_reps'] )
		{
			$this->returnJsonError('no_permission');
		}

		$repApp		= $this->request['repApp'];
		$repType	= $this->request['repType'];
		$repId		= intval($this->request['repId']);
		
		/* Get data */
		$reps		= array();
		$members	= array();

		$this->DB->build( array(
								'select'	=> 'member_id, rep_rating',
								'from'		=> 'reputation_index',
								'where'		=> "app='{$repApp}' AND type='{$repType}' AND type_id='{$repId}'",
								'order'		=> 'rep_date',
						)		);
		$q = $this->DB->execute();

		while ( $r = $this->DB->fetch( $q ) )
		{
			$reps[ $r['member_id'] ]	= $r;
			$members[ $r['member_id'] ]	= $r['member_id'];
		}
		
		if( count($members) AND count($reps) )
		{
			$_members	= IPSMember::load( $members );
			
			foreach( $reps as $memId => $repData )
			{
				$reps[ $memId ]['member']	= $_members[ $memId ];
			}
		}
		
		return $this->returnHtml( $this->registry->output->getTemplate('global_other')->reputationPopup( $reps ) );
	}
	
	/**
	 * Adds a rating to the index
	 *
	 * @return	@e void
	 */
	protected function _more()
	{
		$app   = trim( $this->request['f_app'] );
		$type  = trim( $this->request['f_type'] );
		$id    = intval( $this->request['f_id'] );
		
		/* Get the rep library */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
		$repCache = new $classToLoad();
		
		/* Fetch members who have wanted to favorite this item */
		$data = $repCache->getDataByRelationshipId( array( 'app' => $app, 'type' => $type, 'id' => $id, 'rating' => 1 ) );
		
		return $this->returnHtml( $this->registry->output->getTemplate( 'global_other' )->repMoreDialogue( $data, $id ) );
	}
	
	/**
	 * Adds a rating to the index
	 *
	 * @return	@e void
	 */
	protected function _doRating()
	{
		/* INIT */
		$app     = $this->request['app_rate'];
		$type    = $this->request['type'];
		$type_id = intval( $this->request['type_id'] );
		$rating  = intval( $this->request['rating'] );
		
		/* Check */
		if( ! $app || ! $type || ! $type_id || ! $rating )
		{
			$this->returnString( $this->lang->words['ajax_incomplete_data'] );
		}
				
		/* Get the rep library */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
		$repCache = new $classToLoad();
		
		/* Add the rating */
		if ( $repCache->addRate( $type, $type_id, $rating, '', 0, $app ) !== false )
		{
			$formatted = $repCache->getLikeFormatted( array( 'id' => $type_id, 'app' => $app, 'type' => $type ) );
			$current   = $repCache->getCurrentMemberRating( array( 'id' => $type_id, 'app' => $app, 'type' => $type, 'memberId' => $this->memberData['member_id'] ) );
			$rating    = $repCache->getCurrentRating( array( 'id' => $type_id, 'app' => $app, 'type' => $type ) );
			
			$this->returnJsonArray( array( 'status' => 'ok', 'rating' => $rating, 'likeData' => $formatted, 'canRepUp' => IPSMember::canRepUp( $current, $this->memberData ), 'canRepDown' => IPSMember::canRepDown( $current, $this->memberData ) ) );	
		}
		else
		{
			if ( $repCache->error_message )
			{
				$this->returnJsonError( $repCache->error_message );
			}
			
			$this->returnString( 'done' );
		}
	}
}