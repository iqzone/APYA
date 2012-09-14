<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Most Liked Content
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	© 2012 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		5th January 2012
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_reputation_most extends ipsCommand 
{
	const NUMBER_TO_SHOW = 20;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Get supported applications
		//-----------------------------------------
		
		$supportedApps = array();
		
		foreach( IPSLib::getEnabledApplications() as $app )
		{
			$file = IPSLib::getAppDir( $app['app_directory'] ) . '/extensions/reputation.php';
			
			if( is_file( $file ) )
			{			
				require_once( $file );/*maybeLibHook*/
				
				if( class_exists( 'reputation_' . $app['app_directory'] ) )
				{
					$supportedApps[ $app['app_directory'] ] = $app;
				}
			}
		}
						
		//-----------------------------------------
		// Get results
		//-----------------------------------------
		
		/* What is it we're getting? */
		$app = ( ! empty($this->request['app_tab']) and isset($supportedApps[ $this->request['app_tab'] ]) ) ? $this->request['app_tab'] : 'forums';
		
		/* Load our extension class */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/reputation.php', 'reputation_' . $app, $app );
		$reputationClass = new $classToLoad();
		
		/* Get our query */
		$_query = $reputationClass->fetch('most');
		$PRE    = trim(ipsRegistry::dbFunctions()->getPrefix());
		
		/* Got something? */
		if ( $_query['inner'] )
		{
			/* Build inner join */
			$this->DB->build( $_query['inner'] );
			$inner = $this->DB->fetchSqlString();
			$this->DB->flushQuery();
			
			$this->DB->allow_sub_select = 1;
			$this->DB->query( 'SELECT * FROM ' . $PRE . "reputation_totals WHERE rt_app_type=MD5( CONCAT( '" . $app . "', ';', '" . $_query['type'] . "' ) ) AND rt_type_id IN (" . $inner . ") GROUP BY rt_key ORDER BY rt_total DESC LIMIT 0," .  self::NUMBER_TO_SHOW );
			$this->DB->execute();
			
			$typeIds = array();
			$results = array();
			$index   = array();
			
			while( $row = $this->DB->fetch() )
			{
				$typeIds[ $row['rt_total'] . '.' . $row['rt_type_id'] ] = $row['rt_type_id'];
				$index[ $row['rt_type_id'] ] = $row['rt_total'] . '.' . $row['rt_type_id'];
			}
			
			if ( count( $typeIds ) )
			{
				$this->DB->build( array( 'select'   => 'r.*',
										 'from'	    => array( 'reputation_index' => 'r' ),
										 'where'    => "r.app='" . $app . "' AND r.type='" . $_query['type'] . "' AND r.type_id IN (" . implode( ',', array_values( $typeIds ) ) . ")",
										 'group'    => 'r.app, r.type, r.type_id',
										 'add_join' => $_query['joins'] ) );
					
				$e = $this->DB->execute();
				
				while ( $row = $this->DB->fetch( $e ) )
				{
					$results[ $index[ $row['type_id'] ] ] = $reputationClass->process( $row );
				}
				
				krsort( $results );
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
	
		/* Process Results */
		$processedResults = count($results) ? $reputationClass->display( $results ) : '';
		
		/* Setup page */
		$langBit = ipsRegistry::$settings['reputation_point_types'] == 'like' ? 'most_rep_likes' : 'most_rep_rep';
		$this->registry->output->setTitle( $this->lang->words[ $langBit ] );
		$this->registry->output->addNavigation( $this->lang->words[ $langBit ], NULL );
		
		/* Display processed results */
		$this->registry->output->addContent( $this->registry->getClass('output')->getTemplate('profile')->reputationPage( $langBit, $app, $supportedApps, $processedResults ) );
		$this->registry->output->sendOutput();
	}
}