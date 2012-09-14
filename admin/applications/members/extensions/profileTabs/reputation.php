<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Reputation Profile Tab
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		4th January 2012
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profile_reputation extends profile_plugin_parent
{
	const PER_PAGE = 15;

	/**
	 * Feturn HTML block
	 *
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	public function return_html_block( $member=array() ) 
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
		$app = ( ! empty($this->request['app_tab']) and isset( $supportedApps[ $this->request['app_tab'] ] ) ) ? $this->request['app_tab'] : 'forums';
		$type = ( isset( $this->request['type'] ) and $this->request['type'] == 'received' ) ? 'received' : 'given';
		$st = isset( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;

		/* Load our extension class */
		$classToLoad     = IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/reputation.php', 'reputation_' . $app, $app );
		$reputationClass = new $classToLoad();
		
		/* Get our query */
		$_query = $reputationClass->fetch( $type, $member );
		$_query['group'] = 'r.app, r.type, r.type_id';
	
		/* Get a count */
		$queryForCount = $_query;
		$queryForCount['select'] = 'r.type_id';
		
		$this->DB->build( $queryForCount );
		
		$rawQuery = $this->DB->fetchSqlString();
		
		$this->DB->flushQuery();
		
		$this->DB->allow_sub_select = 1;
		$this->DB->query( 'SELECT COUNT(*) as dracula FROM ( ' . $rawQuery . ') as x' );
		$this->DB->execute();
		
		$count = $this->DB->fetch();

		/* Get em? */
		$processedResults = '';
		$pagination       = '';
		
		if ( ! empty($count['dracula']) )
		{
			$_query['limit'] = array( $st, self::PER_PAGE );
			$_query['order'] = ( $type == 'received' ) ? 'r.type_id DESC' : 'r.rep_date DESC';
			$results = array();

			$this->DB->build( $_query );
			
			$e = $this->DB->execute();
			while ( $row = $this->DB->fetch( $e ) )
			{
				$results[] = $reputationClass->process( $row );
			}

			/* Sort out pagination */
			$pagination = $this->registry->output->generatePagination( array(
				'totalItems'		=> intval($count['dracula']),
				'itemsPerPage'		=> self::PER_PAGE,
				'currentStartValue'	=> $st,
				'baseUrl'			=> "showuser={$member['member_id']}&amp;tab=reputation&amp;app_tab={$app}&amp;type={$type}",
				'seoTitle'			=> $member['members_seo_name'],
				'seoTemplate'		=> 'showuser'
				) );
			
			/* Process Results */
			$processedResults = $reputationClass->display( $results );
		}
		
		/* Display processed results */
		return $this->registry->getClass('output')->getTemplate('profile')->tabReputation( $member, $app, $type, $supportedApps, $processedResults, $pagination );
	}

}