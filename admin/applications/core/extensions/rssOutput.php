<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * RSS output plugin :: report center
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_core
{
	/**
	 * Expiration date
	 *
	 * @var		integer			Expiration timestamp
	 */
	protected $expires			= 0;
	
	/**
	 * Grab the RSS links
	 *
	 * @return	array
	 */
	public function getRssLinks()
	{
		//-----------------------------------------
		// As this is member specific, hardcoded
		// into output library
		//-----------------------------------------
		
		return array();
	}
	
	/**
	 * Grab the RSS document content and return it
	 *
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( ipsRegistry::$request['member_id'] );
		$secure_key		= IPSText::md5Clean( ipsRegistry::$request['rss_key'] );
		$rss_data		= array();
		$to_print		= '';
		
		if( $secure_key and $member_id )
		{
			if( $member_id == ipsRegistry::member()->getProperty('member_id') )
			{
				//-----------------------------------------
				// Get RSS export
				//-----------------------------------------
				
				$rss_data = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'rss_cache',
																	 'from'   => 'rc_modpref',
																	 'where'  => "mem_id=" . $member_id . " AND rss_key='" . $secure_key . "'"
															 )		);
				
				//-----------------------------------------
				// Got one?
				//-----------------------------------------
				
				if ( $rss_data['rss_cache'] )
				{
					return $rss_data['rss_cache'];
				}
			}

			//-----------------------------------------
			// Create a dummy one
			//-----------------------------------------
			
			ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_reports' ), 'core' );
			
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
			$rss		 = new $classToLoad();
			
			$channel_id = $rss->createNewChannel( array( 'title'		=> ipsRegistry::getClass('class_localization')->words['rss_feed_title'],
														 'link'			=> ipsRegistry::$settings['board_url'],
														 'description'	=> ipsRegistry::getClass('class_localization')->words['reports_rss_desc'],
														 'pubDate'		=> $rss->formatDate( time() )
												)		);
			$rss->createRssDocument();
			
			return $rss->rss_document;
		}
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 *
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		return time() + 3600;
	}
}