<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board v<{%dyn.down.var.human.version%}>
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Time: <{%dyn.down.var.time%}>
|   Release: <{%dyn.down.var.md5%}>
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > SSI script
|   > Script written by Matt Mecham
|   > Date started: 29th April 2002
|   > UPDATED for 2.0: 1st July 2004
|   > UPDATED for 2.1: 13th Sept 2005
|   > UPDATED for 3.0: 26th Feb 2009
|
+--------------------------------------------------------------------------

+--------------------------------------------------------------------------
|   USAGE:
+--------------------------------------------------------------------------

Simply call this script via PHP includes, or SSI .shtml tags to generate content
on the fly, streamed into your own webpage.

+--------------------------------------------------------------------------
|   To show the board statistics
+--------------------------------------------------------------------------

include("http://domain.com/forums/ssi.php?a=stats");/*noLibHook*//*TERANOTE: remove additional /*

+--------------------------------------------------------------------------
|   To show the active users stats (x Members, X Guests, etc)
+--------------------------------------------------------------------------

include("http://domain.com/forums/ssi.php?a=active");/*noLibHook*//*TERANOTE: remove additional /*

+--------------------------------------------------------------------------
|   RSS / XML Syndication..
+--------------------------------------------------------------------------

RSS: http://domain.com/forums/ssi.php?a=out&f=1,2,3,4,5&show=10&type=rss
XML: http://domain.com/forums/ssi.php?a=out&f=1,2,3,4,5&show=10&type=xml

Will show last 10 topics in reverse chronological last post date order from
all the forums in the comma separated list
   
*/


/**
* Main executable wrapper.
*
* Set-up and load module to run
*
* @package	IP.Board
* @author   Matt Mecham
* @version	3.0
*/

define( 'CCS_GATEWAY_CALLED', TRUE ); // Prevents redirect checks in ipsRegistry
define( 'IPS_ENFORCE_ACCESS', TRUE ); // Prevents force login setting from returning a login page
define( 'IPB_THIS_SCRIPT', 'public' );

require_once( './initdata.php' );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

/**
* Path to SSI templates directory
*
*/
define( 'SSI_TEMPLATES_DIR', DOC_IPS_ROOT_PATH."ssi_templates" );
/**
* Maximum number of topics to show
*
*/
define( 'SSI_MAX_SHOW'     , 100 );

/**
* Allow SSI export. Enter "0" to turn off.
*
*/
define( 'SSI_ALLOW_SYND'   , 1 );

/* Go... */
$reg = ipsRegistry::instance();
$reg->init();

$ssi = new ssi( $reg );

class ssi
{
	function __construct( ipsRegistry $registry )
	{
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		
		/* Load forums class */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
		$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
		$this->registry->class_forums->forumsInit();

		switch ($this->request['a'])
		{
			case 'active':
				$this->_doActive();
				break;
				
			case 'stats':
				$this->_doStats();
				break;
				
			case 'out':
				if ( SSI_ALLOW_SYND == 1 )
				{
					$this->_doSyndication();
				}
				else
				{
					exit();
				}
				break;
				
			default:
				echo("An error occurred whilst processing this directive");
				exit();
				break;
		}
	}

	/**
	* Do Syndication
	*
	* Export topics / titles from selected forums
	*
	* @access	protected
	*/
	protected function _doSyndication()
	{
		//----------------------------------------
		// Sort out the forum ids
		//----------------------------------------
		
		$tmp_forums = array();
		$forums     = array();
		
		if ( $this->request['f'] )
		{
			$tmp_forums = explode( ",", $this->request['f'] );
		}
		else
		{
			fatal_error("Fatal error: no forum id specified");
		}
		
		//----------------------------------------
		// Intval the IDs
		//----------------------------------------
		
		foreach ($tmp_forums as $f )
		{
			$f = intval($f);
			
			if ( $f )
			{
				$forums[] = $f;
			}
		}
		
		//----------------------------------------
		// Check...
		//----------------------------------------
		
		if ( count($forums) < 1 )
		{
			fatal_error("Fatal error: no forum id specified");
		}
		
		$sql_fields = implode( ",", $forums );
		
		//----------------------------------------
		// Number of topics to return?
		//----------------------------------------
		
		$perpage = intval($this->request['show']) ? intval($this->request['show']) : 10;
		
		$perpage = ( $perpage > SSI_MAX_SHOW ) ? SSI_MAX_SHOW : $perpage;
		
		//----------------------------------------
		// Load the template...
		//----------------------------------------
		
		if ( $this->request['type'] == 'xml' )
		{
			$template = $this->_loadTemplate("syndicate_xml.html");
		}
		else
		{
			$template = $this->_loadTemplate("syndicate_rss.html");
		}
		
		//----------------------------------------
		// parse..
		//----------------------------------------
		
		$to_echo = "";
		$top     = "";
		$row     = "";
		$bottom  = "";
		
		preg_match( "#\[TOP\](.+?)\[/TOP\]#is", $template, $match );
		
		$top    = trim($match[1]);
		
		preg_match( "#\[ROW\](.+?)\[/ROW\]#is", $template, $match );
		
		$row    = trim($match[1]);
		
		preg_match( "#\[BOTTOM\](.+?)\[/BOTTOM\]#is", $template, $match );
		
		$bottom = trim($match[1]);
		
		//----------------------------------------
		// Header parse...
		//----------------------------------------
		
		//@header("Content-Type: text/xml;charset={$this->settings['gb_char_set']}");
		//@header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		//@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		//@header('Pragma: public');
		
		$to_echo .= $this->_parseTemplate( $top, array ( 'board_url'  => $this->settings['base_url'] ,
														 'board_name' => $this->settings['board_name'] ) );
		
		//----------------------------------------
		// Fix up
		//----------------------------------------
		
		$group = $this->caches['group_cache'][ $this->settings['guest_group'] ];
			
		//----------------------------------------
		// Get the topics, member info and other stuff
		//----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
													  'from'   => 'topics',
													  'where'  => "forum_id IN ($sql_fields) AND " . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ),
													  'order'  => 'last_post DESC',
													  'limit'  => array( 0, $perpage ) ) );
		
		$this->DB->execute();
				   
		if ( ! $this->DB->getTotalRows() )
		{
			fatal_error("Could not get the information from the database");
		}
	
		while ( $i = $this->DB->fetch() )
		{
			$forum = $this->registry->class_forums->forum_by_id[ $i['forum_id'] ];
			
			if ( $this->registry->permissions->check( 'read', $forum ) != TRUE )
			{
				continue;
			}

			if ( $forum['password'] != "" )
			{
				continue;
			}
			
			$to_echo .= $this->_parseTemplate( $row, array ( 'topic_title'    => str_replace( '&#', '&amp;#', $i['title'] ),
															 'topic_id'       => $i['tid'],
															 'topic_link'     => $this->settings['base_url']."showtopic=".$i['tid'],
															 'forum_title'    => htmlspecialchars($forum['name']),
															 'forum_id'       => $i['forum_id'],
															 'last_poster_id' => $i['last_poster_id'],
															 'last_post_name' => $i['last_poster_name'],
															 'last_post_time' => $this->registry->getClass('class_localization')->getDate( $i['last_post'] , 'LONG', 1 ),
															 'timestamp'      => $i['last_post'],
															 'starter_id'     => $i['starter_id'],
															 'starter_name'   => $i['starter_name'],
															 'board_url'      => $this->settings['board_url']          ,
															 'board_name'     => $this->settings['board_name'],
															 'rfc_date'       => date( 'r', $i['last_post'] ) )             ) . "\r\n";
		}
		
		//----------------------------------------
		// Print bottom...
		//----------------------------------------
		
		echo $to_echo."\r\n".$bottom;
		
		exit();
	}
	
	
	/**
	* Do Stats
	*
	* Show totals
	*/
	function _doStats()
	{
		//----------------------------------------
		// Load the template...
		//----------------------------------------
		
		$template = $this->_loadTemplate("stats.html");
		
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$to_echo = "";
		$time    = time() - 900;
		
		$stats       = $this->caches['stats'];
		$total_posts = $stats['total_replies'] + $stats['total_topics'];
		$to_echo  = $this->_parseTemplate( $template, array(  'total_posts'  => $total_posts,
															  'topics'       => $stats['total_topics'],
															  'replies'      => $stats['total_replies'],
															  'members'      => $stats['mem_count'] ) );
		echo $to_echo;
		
		exit();
	}

	/**
	* Do Active
	*
	* Show active users
	*/
	function _doActive()
	{
		//--------------------------------
		// Load the template...
		//--------------------------------
		
		$template = $this->_loadTemplate("active.html");
		
		//--------------------------------
		// INIT
		//--------------------------------
		
		$to_echo = "";
		
		//--------------------------------
		// Make sure we have a cut off...
		//--------------------------------
		
		if ($this->settings['au_cutoff'] == "")
		{
			$this->settings['au_cutoff'] = 15;
		}
		
		//-----------------------------------------
		// Get the users from the DB
		//-----------------------------------------
		
		$cut_off = $this->settings['au_cutoff'] * 60;
		$time    = time() - $cut_off;
		$rows    = array();
		$ar_time = time();
		
		if ( $this->memberData['member_id'] )
		{
			$rows = array( $ar_time => array( 'login_type'   => IPSMember::isLoggedInAnon( $this->memberData ),
											  'running_time' => $ar_time,
											  'member_id'    => $this->memberData['member_id'],
											  'member_name'  => $this->memberData['members_display_name'],
											  'member_group' => $this->memberData['member_group_id'] ) );
		}
		
		$this->DB->build( array( 'select' => 'id, member_id, member_name, login_type, running_time, member_group',
								 'from'   => 'sessions',
								 'where'  => "running_time > $time",
						 )      );
		
		
		$this->DB->execute();
		
		//-----------------------------------------
		// FETCH...
		//-----------------------------------------
		
		while ( $r = $this->DB->fetch() )
		{
			$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
		}
		
		krsort( $rows );
		
		//--------------------------------
		// cache all printed members so we
		// don't double print them
		//--------------------------------
					
		$cached = array();
		$active = array();
		
		foreach( $rows as $result )
		{
			//-----------------------------------------
			// Bot?
			//-----------------------------------------
			
			if ( strstr( $result['id'], '_session' ) )
			{
				//-----------------------------------------
				// Seen bot of this type yet?
				//-----------------------------------------
				
				$botname = preg_replace( '/^(.+?)=/', "\\1", $result['id'] );
				
				if ( ! $cached[ $result['member_name'] ] )
				{
					$cached[ $result['member_name'] ] = 1;
				}
				else
				{
					//-----------------------------------------
					// Yup, count others as guest
					//-----------------------------------------
					
					$active['GUESTS']++;
				}
			}
			
			//-----------------------------------------
			// Guest?
			//-----------------------------------------
			
			else if ( ! $result['member_id'] )
			{
				$active['GUESTS']++;
			}
			
			//-----------------------------------------
			// Member?
			//-----------------------------------------
			
			else
			{
				if ( empty( $cached[ $result['member_id'] ] ) )
				{
					$cached[ $result['member_id'] ] = 1;
					
					/* Reset login type in case the board/group setting got changed */
					$result['login_type']  = IPSMember::isLoggedInAnon( array( 'login_anonymous' => $result['login_type'] ), $result['member_group'] );
					
					if ( $result['login_type'] )
					{
						$active['ANON']++;
					}
					else
					{
						$active['MEMBERS']++;
					}
				}
			}
		}
		
		$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
				   
		$to_echo  = $this->_parseTemplate( $template, array (  'total'   => $active['TOTAL']   ? $active['TOTAL']   : 0 ,
															   'members' => $active['MEMBERS'] ? $active['MEMBERS'] : 0,
															   'guests'  => $active['GUESTS']  ? $active['GUESTS']  : 0,
															   'anon'    => $active['ANON']    ? $active['ANON']    : 0 ) );
		
		
		echo $to_echo;
		
		exit();
	}
	
	
	/**
	* Parse template
	*
	* Parses the template. Duh.
	*
	* @access	protected
	* @param	string		Template data
	* @param	array 		Array of data
	*/
	protected function _parseTemplate( $template, $assigned=array() )
	{
		foreach( $assigned as $word => $replace)
		{
			$template = preg_replace( "/\{$word\}/i", "$replace", $template );
		}
		
		$template = str_replace( '/ssi.php', '/index.php', $template );
		
		return $this->registry->output->replaceMacros( $template );
	}
	
	/**
	* Load template
	*
	* Loads the template
	*
	* @access   protected
	* @param	string	Template to load
	*/
	protected function _loadTemplate($template="")
	{
		$filename = SSI_TEMPLATES_DIR."/".$template;
		
		if ( is_file($filename) )
		{
			if ( $FH = fopen($filename, 'r') )
			{
				$template = fread( $FH, filesize($filename) );
				fclose($FH);
			}
			else
			{
				fatal_error("Couldn't open the template file");
			}
		}
		else
		{
			fatal_error("Template file does not exist");
		}
		
		return $template;
	}
}

function fatal_error( $error )
{
	print $error;
	exit();
}
