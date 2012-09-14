<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Reputation
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_reputation extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* What to do... */
		switch( $this->request['do'] )
		{
			case 'add_rating':
				$this->doRating();
			break;
		}
	}
	
	/**
	 * Adds a rating to the index
	 *
	 * @return	@e void
	 */
	public function doRating()
	{
		/* INIT */
		$app     = $this->request['app_rate'];
		$type    = $this->request['type'];
		$type_id = intval( $this->request['type_id'] );
		$rating  = intval( $this->request['rating'] );
		
		/* Check */
		if( ! $app || ! $type || ! $type_id || ! $rating )
		{
			$this->registry->output->showError( 'reputation_missing_data', 10126, false, false, 403 );
		}
		
		/* Check the secure key. Needed here to prevent direct URLs from increasing reps */
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'reputation_missing_data', 10126, false, false, 403 );
		}
			
		/* Get the rep library */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
		$repCache = new $classToLoad();
		
		/* Add the rating */
		if( ! $repCache->addRate( $type, $type_id, $rating, '', 0, $app ) )
		{
			$this->registry->output->showError( $repCache->error_message, 10127, false, false, 403 );
		}
		else
		{
			/* Redirect to */
			$return_url = '';
			
			if( !empty( $this->request['post_return'] ) )
			{
				$return_url = $this->settings['base_url'] . 'app=forums&module=forums&section=findpost&pid=' . intval( $this->request['post_return'] );
			}
			else if( $_SERVER['HTTP_REFERER'] )
			{
				$return_url = $_SERVER['HTTP_REFERER'];
			}
			else
			{
				$return_url = $this->settings['base_url'];
			}
			
			/* Probably Temporary :) */
			$this->registry->output->silentRedirect( $return_url );
		}
	}
}