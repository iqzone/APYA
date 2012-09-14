<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sets up SEO templates
 * Last Updated: $Date: 2012-06-07 13:08:02 -0400 (Thu, 07 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10889 $
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
	
'section=register' => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=register(&amp;|&|$)#i', 'register/$3' ),
						     'in'            => array( 'regex'   => "#/register(/|$|\?)#i",
							 'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'register' ) ) ) ),

'tags'			   => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=search(&amp;|&)do=search(&amp;|&)search_tags=(\S+?)(&amp;|&)search_app=(\S+?)(&amp;|&|$)#i', 'tags/$6/$4/$7' ),
						     'in'            => array( 'regex'   => "#/tags/(\S+?)/(\S+?)/#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'search' ), array( 'do', 'search' ), array( 'search_tags', '$2' ), array( 'search_app', '$1' ) ) ) ),

'privacy'		   => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=privacy(&amp;|&|$)#i', 'privacypolicy/$4/' ),
						     'in'            => array( 'regex'   => "#/privacypolicy/#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'privacy' ) ) ) ),

'likeunsubscribe'  => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=like(&amp;|&)do=unsubscribe(&amp;|&)key=(\S+?)(&amp;|&|$)#i', 'unsubscribe/$5/' ),
						     'in'            => array( 'regex'   => "#/unsubscribe/(\S+?)/$#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'like' ), array( 'do', 'unsubscribe' ), array( 'key', '$1' ) ) ) ),
'findcomment'  	   => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=comments(&amp;|&)do=findComment(&amp;|&)fromApp=(\S+?)(&amp;|&)parentId=(\d+?)(&amp;|&)commentId=(\d+?)(&amp;|&|$)#i', 'findComment/$5/$7-$9' ),
						     'in'            => array( 'regex'   => "#/findComment/(\S+?-\S+?)/(\d+?)-(\d+?)$#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'comments' ), array( 'do', 'findComment' ), array( 'fromApp', '$1' ), array( 'parentId', '$2' ), array( 'commentId', '$3' ) ) ) ),											
'section=rss'      => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=rss(&amp;|&)type=(\w+?)$#i', 'rss/$4/' ),
						     'in'            => array( 'regex'   => "#/rss/(\w+?)/$#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'rss' ), array( 'type', '$1' ) ) ) ),
											
'section=rss2'     => array( 'app'		     => 'core',
							 'allowRedirect' => 0,
							 'out'           => array( '#app=core(&amp;|&)module=global(&amp;|&)section=rss(&amp;|&)type=(\w+?)(&amp;|&)id=(\w+?)$#i', 'rss/$4/$6-#{__title__}/' ),
						     'in'            => array( 'regex'   => "#/rss/(\w+?)/(\w+?)-#i",
											           'matches' => array( array( 'app', 'core' ), array( 'module', 'global' ), array( 'section', 'rss' ), array( 'type', '$1' ), array( 'id', '$2' ) ) ) ),

# Changed section=rss2 id matching to use \w for more flexibility

);
	