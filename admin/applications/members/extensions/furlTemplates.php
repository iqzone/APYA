<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sets up SEO templates
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * SEO templates
 *
 * 'allowRedirect' is a flag to tell IP.Board whether to check the incoming link and if not formatted correctly, redirect the correct one
 *
 * OUT FORMAT REGEX:
 * First array element is a regex to run to see if we've a match for the URL
 * The second array element is the template to use the results of the parenthesis capture
 *
 * Special variable #{__title__} is replaced with the $title data passed to output->formatUrl( $url, $title)
 *
 * IMPORTANT: Remember that when these regex are used, the output has not been fully parsed so you will get:
 * showuser={$data['member_id']} NOT showuser=1 so do not try and match numerics only!
 *
 * IN FORMAT REGEX
 *
 * This allows the registry to piece back together a URL based on the template regex
 * So, for example: "/user/(\d+?)/", 'matches' => array(  array( 'showuser' => '$1' ) )tells IP.Board to populate 'showuser' with the result
 * of the parenthesis capture #1
 */
$_SEOTEMPLATES = array(
	
	'showuser'	=> array( 'app'		      => 'members',
						  'allowRedirect' => 1,
						  'out'           => array( '#showuser=(.+?)((?:&|&amp;)f=(.+?))?(&|$)#i', 'user/$1-#{__title__}/$2$4' ),
						  'in'            => array( 'regex'   => "#^/user/(\d+?)-#i",
												    'matches' => array( array( 'showuser', '$1' ) ) ) ),
	
	
	'members_status_single' => array( 'app'		      => 'members',
									  'allowRedirect' => 0,
									  'out'           => array( '#app=members(?:&|&amp;)module=profile(?:&|&amp;)section=status(?:&|&amp;)type=single(?:&|&amp;)status_id=(\d+?)(&|$)#i', 'statuses/id/$1/$2' ),
									  'in'            => array( 'regex'   => "#/statuses/id/(\d+?)/#i",
															    'matches' => array( array( 'app'    , 'members' ),
															    					array( 'section', 'status' ),
															    					array( 'module' , 'profile' ),
															    					array( 'type'   , 'single' ),
															    					array( 'status_id', '$1' ) ) ) ),
															    					
	'members_status_friends'=> array( 'app'		      => 'members',
									  'allowRedirect' => 0,
									  'out'           => array( '#app=members(?:&|&amp;)module=profile(?:&|&amp;)section=status(?:&|&amp;)type=friends(&|$)#i', 'statuses/friends/$2' ),
									  'in'            => array( 'regex'   => "#/statuses/friends#i",
															    'matches' => array( array( 'app'    , 'members' ),
															    					array( 'section', 'status' ),
															    					array( 'module' , 'profile' ),
															    					array( 'type'   , 'friends' ) ) ) ),
	
	'members_status_all'	=> array( 'app'		      => 'members',
									  'allowRedirect' => 0,
									  'out'           => array( '#app=members(?:&|&amp;)module=profile(?:&|&amp;)section=status((?:&|&amp;)type=all)?(&|$)#i', 'statuses/all/$2' ),
									  'in'            => array( 'regex'   => "#/statuses/all#i",
															    'matches' => array( array( 'app'    , 'members' ),
															    					array( 'section', 'status' ),
															    					array( 'module' , 'profile'  ) ) ) ),

						
	'members_list'  => array( 
						'app'			=> 'members',
						'allowRedirect' => 0,
						'out'			=> array( '#app=members((&|&amp;)module=list)?#i', 'members/' ),
						'in'			=> array( 
													'regex'		=> "#/members(/|$|\?)#i",
													'matches'	=> array( array( 'app', 'members' ),
																		  array( 'module', 'list' )  )
												) 
									),
									
	'most_liked'  => array( 
						'app'			=> 'members',
						'allowRedirect' => 0,
						'out'			=> array( '#app=members(?:&|&amp;)module=reputation(?:&|&amp;)section=most#i', 'best-content/' ),
						'in'			=> array( 
													'regex'		=> "#/best-content(/|$|\?)#i",
													'matches'	=> array( array( 'app', 'members' ),
																		  array( 'module', 'reputation' ),
																		  array( 'section', 'most' )  )
												) 
									),
);
