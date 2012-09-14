#!/usr/local/bin/php
<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Main public executable wrapper.
 * Set-up and load module to run
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

define( 'IPS_IS_SHELL', TRUE );
define( 'IPB_THIS_SCRIPT', 'public' );
define( 'IPS_CLI_MEMORY_DEBUG', false );

if ( is_file( './initdata.php' ) )
{
	require_once( './initdata.php' );/*noLibHook*/
}
else
{
	require_once( '../initdata.php' );/*noLibHook*/
}



require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );/*noLibHook*/

$reg = ipsRegistry::instance();
$reg->init();

$moo = new moo( $reg );

class moo
{
	protected $processed = 0;
	protected $parser;
	protected $oldparser;
	protected $start     = 0;
	protected $end       = 0;
	
	function __construct( ipsRegistry $registry )
	{
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->memberData = array();
		$this->stdin      =  fopen('php://stdin', 'r');
		
		//-----------------------------------------
		// Get new parser and old parser
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . "sources/handlers/han_parse_bbcode.php" );/*noLibHook*/
		$this->parser		= new parseBbcode( $this->registry );
		$this->oldparser	= new parseBbcode( $this->registry, 'legacy' );
		
		//-----------------------------------------
		// Show options
		//-----------------------------------------
		
		$this->_print( "--------------------------------------------\nWelcome to the IP.Board Post Content Re-Builder\n--------------------------------------------\n" );
		$this->_print( "What do you wish to build?\n(c=Calendar Events, a=Announcements, x=Personal Conversations, s=Signatures, m=About me, p=posts, o=Remove Orphaned Posts)\nEnter: " );

		$option = $this->_fetchOption();

		switch( $option )
		{
			case 'c':
				$this->_doPosts('cal');
			break;
			case 'a':
				$this->_doPosts('announce');
			break;
			case 'x':
				$this->_doPosts('pms');
			break;
			case 's':
				$this->_doPosts('sigs');
			break;
			case 'm':
				$this->_doPosts('aboutme');
			break;
			case 'p':
				$this->_doPosts('posts');
			break;
			case 'o':
				$this->_doOrphanedPosts();
			break;
			default:
				$this->_doPosts('posts');
			break;
		}
	}
	
	/**
	 * Rebuild data
	 */
	protected function _doOrphanedPosts()
	{
		$batch  = 250;
		$c      = -1;
		$bucket = array();
		
		/* Lets grab the highest and lowest IDs */
		$rows = $this->DB->buildAndFetch( array( 'select'   => "count(pid) AS CNT, max(pid) AS MAX, min(pid) as MIN",
												 'from'     => array( 'posts' => 'p' ),
												 'where'    => 't.tid IS NULL',
												 'add_join' => array( array( 'select' => 't.*',
												 							 'from'   => array( 'topics' => 't' ),
												 							 'where'  => 't.tid=p.topic_id' ) ) ) );
		
		if ( empty( $rows['CNT'] ) )
		{
			$this->_print( "No orphaned posts have been found" );
			exit();
		}
		
		$this->DB->build( array( 'select' => 'pid, from_unixtime(post_date) as time',
								 'from'   => 'posts',
								 'where'  => 'pid IN( ' . $rows['MIN'] . ',' . $rows['MAX'] . ')' ) );
								 
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$posts[ $r['pid'] ] = $r['time'];
		}
		
		$this->_print( "--------------------------------------------\n" . $rows['CNT'] . " orphaned posts found\n--------------------------------------------\n" );
		$this->_print( "Oldest: PID:" . $rows['MIN'] . " - Date: " . $posts[ $rows['MIN'] ] );
		$this->_print( "Newest: PID:" . $rows['MAX'] . " - Date: " . $posts[ $rows['MAX'] ] );
		$this->_print( "Continue? (y/n):" );
		
		$option = $this->_fetchOption();
		
		if ( $option != 'y' )
		{
			$this->_print("Nothing deleted");
			exit();
		}
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$this->modLibrary = new $classToLoad( $this->registry );
		
		/* Still here? */
		$this->DB->build( array( 'select'   => "pid",
								 'from'     => array( 'posts' => 'p' ),
								 'where'    => 't.tid IS NULL',
								 'add_join' => array( array( 'select' => 't.*',
								 							 'from'   => array( 'topics' => 't' ),
								 							 'where'  => 't.tid=p.topic_id' ) ) ) );
								 							 
		$o = $this->DB->execute();
		
		while( $post = $this->DB->fetch( $o ) )
		{
			$c++;
			
			$bucket[] = $post['pid'];
			
			if ( $c % $batch )
			{
				$this->modLibrary->postDeleteFromDb( $bucket, true, true );
				$this->_print( $c . ' deleted' );
				
				//$this->_print( var_export( $bucket, true ) );
				
				$bucket = array();
				$c  = 0;
			}
		}
		
		$this->_print( "All done" );
	}
	
	/**
	 * Rebuild data
	 */
	protected function _doPosts( $type )
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$last	    = 0;
		$output     = array();
		$types	    = array( 'posts', 'pms', 'cal', 'announce', 'sigs', 'aboutme' );
		$type	    = in_array( $type, $types ) ? $type : 'posts';
		$groupCache = $this->caches['group_cache'];
		
		$this->_print( "Start at ID (Enter 0 to start from the very first entry)\nEnter: " );

		$this->start = intval( $this->_fetchOption() );
	
		$this->_print( "Process X rows (Enter 0 to process all rows)\nEnter: " );
	
		$this->end = intval( $this->_fetchOption() );
		
		//-----------------------------------------
		// Process batches...
		//-----------------------------------------
		
		while( ( $result = $this->_process( $type ) ) !== FALSE )
		{
			$this->_print( $result );
		}
	
		/* We're done.. */
		$this->_print( "COMPLETE" );
		
		/* Update isRebuilt cache */
		$sections		= array();
		$alreadyRebuilt	= $this->DB->buildAndFetch( array( 'select' => 'cs_value', 'from' => 'cache_store', 'where' => "cs_key='isRebuilt'" ) );

		if( $alreadyRebuilt['cs_value'] )
		{
			$sections	= explode( ',', $alreadyRebuilt['cs_value'] );
		}
		
		$sections[]	= $type;
		
		$this->DB->replace( 'cache_store', array( 'cs_key' => 'isRebuilt', 'cs_value' => implode( ',', $sections ) ), array( "cs_key" ) );
		
		/* Drop cache */
		if ( $type == 'posts' )
		{
			$this->DB->delete( 'content_cache_posts' );
		}
		else if ( $type == 'sigs' )
		{
			$this->DB->delete( 'content_cache_sigs' );
		}
	}
	
	/**
	 * Actually process
	 *
	 */
	protected function _process( $type )
	{
		if ( $this->end == 0 OR ( $this->end > $this->processed ) )
		{
			/* More to do... */
			$batch             = 250;
			$_s 	           = intval( $this->start );
			$done              = 0;
			$this->processed  += $batch;
			
			/* On mssql, the select query blocks all the updates.  Doing this causes the engine not to lock */
			if ( method_exists( $this->DB, 'setQueryRowLocking' ) )
			{
				$this->DB->setQueryRowLocking( 'OFF' );
			}
			
			switch( $type )
			{
				case 'cal':
					$this->DB->build( array( 'select' 	=> 'e.*',
															 'from' 	=> array( 'cal_events' => 'e' ),
															 'order' 	=> 'e.event_id ASC',
															 'where'    => 'e.event_id > ' . $_s,
															 'limit' 	=> array( $batch ),
															 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
															  									'select'	=> 'm.member_group_id, m.mgroup_others',
															  								  	'from'		=> array( 'members' => 'm' ),
															  								  	'where' 	=> "m.member_id=e.event_member_id"
															  						)	)
													) 		);
				break;

				case 'announce':
					$this->DB->build( array( 'select' 	=> 'a.*',
															 'from' 	=> array( 'announcements' => 'a' ),
															 'order' 	=> 'a.announce_id ASC',
															 'where'    => 'a.announce_id > ' . $_s,
															 'limit' 	=> array( $batch ),
															 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
															  									'select'	=> 'm.member_group_id, m.mgroup_others',
															  								  	'from'		=> array( 'members' => 'm' ),
															  								  	'where' 	=> "m.member_id=a.announce_member_id"
															  						)	)
													) 		);
				break;

				case 'pms':
					$this->DB->build( array( 'select' 	=> 'p.*',
															 'from' 	=> array( 'message_posts' => 'p' ),
															 'order' 	=> 'p.msg_id ASC',
															 'where'    => 'p.msg_id > ' . $_s,
															 'limit' 	=> array( $batch ),
															 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
															  									'select'	=> 'm.member_group_id, m.mgroup_others',
															  								  	'from'		=> array( 'members' => 'm' ),
															  								  	'where' 	=> "m.member_id=p.msg_author_id"
															  						)	)
													) 		);
				break;

				case 'sigs':
					$this->DB->build( array( 'select' 	=> 'me.signature, me.pp_member_id',
															 'from' 	=> array( 'profile_portal' => 'me' ),
															 'order' 	=> 'me.pp_member_id ASC',
															 'where'	=> "me.signature IS NOT NULL AND me.signature != '' AND me.pp_member_id > " . $_s,
															 'limit' 	=> array( $batch ),
															 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
															  									'select'	=> 'm.member_group_id, m.mgroup_others, m.members_display_name',
															  								  	'from'		=> array( 'members' => 'm' ),
															  								  	'where' 	=> "m.member_id=me.pp_member_id"
															  						)	)
													) 		);
				break;

				case 'aboutme':
					$this->DB->build( array( 'select' 	=> 'pp.pp_about_me, pp.pp_member_id',
															 'from' 	=> array( 'profile_portal' => 'pp' ),
															 'order' 	=> 'pp.pp_member_id ASC',
															 'where'	=> "pp.pp_about_me != '' AND pp.pp_about_me IS NOT NULL AND pp.pp_member_id > " . $_s,
															 'limit' 	=> array( $batch ),
															 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
															  									'select'	=> 'm.member_group_id, m.mgroup_others, m.members_display_name',
															  								  	'from'		=> array( 'members' => 'm' ),
															  								  	'where' 	=> "m.member_id=pp.pp_member_id"
															  						)	)
													) 		);
				break;

				case 'posts':
					$this->DB->build( array( 'select' 	=> 'p.*',
															 'from' 	=> array( 'posts' => 'p' ),
															 'order' 	=> 'p.pid ASC',
															 'where'    => 'p.pid > ' . $_s,
															 'limit' 	=> array( $batch ),
															 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
															 									'select'	=> 't.forum_id',
															  								  	'from'		=> array( 'topics' => 't' ),
															  								  	'where' 	=> "t.tid=p.topic_id"
															  						),
															  						2 => array( 'type'		=> 'left',
															  									'select'	=> 'm.member_group_id, m.mgroup_others',
															  								  	'from'		=> array( 'members' => 'm' ),
															  								  	'where' 	=> "m.member_id=p.author_id"
															  						),
																					3 => array( 'type'		=> 'left',
															  									'select'	=> 'f.*',
															  								  	'from'		=> array( 'forums' => 'f' ),
															  								  	'where' 	=> "t.forum_id=f.id"
															  						) ) ) );
				break;
			}

			$outer = $this->DB->execute();

			//-----------------------------------------
			// Process...
			//-----------------------------------------

			while( $r = $this->DB->fetch( $outer ) )
			{
				//-----------------------------------------
				// Reset
				//-----------------------------------------
		
				$this->parser->quote_open				= $this->oldparser->quote_open			    = 0;
				$this->parser->quote_closed			    = $this->oldparser->quote_closed			= 0;
				$this->parser->quote_error			    = $this->oldparser->quote_error			    = 0;
				$this->parser->error					= $this->oldparser->error					= '';
				$this->parser->image_count			    = $this->oldparser->image_count			    = 0;
				$this->parser->parsing_mgroup			= $this->oldparser->parsing_mgroup		    = $r['member_group_id'];
				$this->parser->parsing_mgroup_others	= $this->oldparser->parsing_mgroup_others	= $r['mgroup_others'];
			
				/* Clear cached queries */
				$this->DB->obj['cached_queries'] = array();
			
				$this->memberData['g_bypass_badwords'] = $this->caches['group_cache'][ $r['member_group_id'] ]['g_bypass_badwords'];

				switch( $type )
				{
					case 'cal':
						$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= $r['event_smilies'];
						$this->parser->parse_html		= $this->oldparser->parse_html	= 0;
						$this->parser->parse_bbcode	= $this->oldparser->parse_bbcode	= 1;
						$this->parser->parsing_section		= 'calendar';

						$rawpost = $this->oldparser->preEditParse( $r['event_content'] );
					break;

					case 'announce':
						$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= 1;
						$this->parser->parse_html		= $this->oldparser->parse_html	= $r['announce_html_enabled'];
						$this->parser->parse_bbcode	= $this->oldparser->parse_bbcode	= 1;
						$this->parser->parse_nl2br	= $this->oldparser->parse_nl2br	= $r['announce_nlbr_enabled'];
						$this->parser->parsing_section		= 'announcement';

						$rawpost = $this->oldparser->preEditParse( $r['announce_post'] );
					break;

					case 'pms':
						$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= 1;
						$this->parser->parse_html		= $this->oldparser->parse_html	= $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'];
						$this->parser->parse_bbcode	    = $this->oldparser->parse_bbcode	= 1;
						$this->parser->parse_nl2br	    = $this->oldparser->parse_nl2br	= 1;
						$this->parser->parsing_section	= 'pms';

						$rawpost = $this->oldparser->preEditParse( $r['msg_post'] );
					break;

					case 'sigs':
						$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= 0;
						$this->parser->parse_html		= $this->oldparser->parse_html	= $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'];
						$this->parser->parse_bbcode	= $this->oldparser->parse_bbcode	= 1;
						$this->parser->parse_nl2br	= $this->oldparser->parse_nl2br	= 1;
						$this->parser->parsing_section		= 'signatures';

						$rawpost = $this->oldparser->preEditParse( $r['signature'] );
					break;

					case 'aboutme':
						$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= 1;
						$this->parser->parse_html		= $this->oldparser->parse_html	= $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'];
						$this->parser->parse_bbcode	= $this->oldparser->parse_bbcode	= 1;
						$this->parser->parse_nl2br	= $this->oldparser->parse_nl2br	= 1;
						$this->parser->parsing_section		= 'aboutme';

						$rawpost = $this->oldparser->preEditParse( $r['pp_about_me'] );
					break;

					case 'posts':
						$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= $r['use_emo'];
						$this->parser->parse_html		= $this->oldparser->parse_html	= $r['use_html'] AND $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'];
						$this->parser->parse_bbcode	= $this->oldparser->parse_bbcode	= $r['use_ibc'];
						$this->parser->parse_nl2br	= $this->oldparser->parse_nl2br	= ( $r['post_htmlstate'] != 1 ) ? 1 : 0;
						$this->parser->parsing_section		= 'topics';

						$rawpost = $this->oldparser->preEditParse( $r['post'] );
					break;
				}
				
				/* @link http://community.invisionpower.com/tracker/issue-36434-rebuild-post-content-remove-break-from-post-content/ New parser retains <br>, legacy retains \n */
				if ( strstr( $rawpost, "\n" ) )
				{
					$rawpost = nl2br( $rawpost );
				}
			
				$newpost = $this->parser->preDbParse( $rawpost );

				//-----------------------------------------
				// Remove old \' escaping
				//-----------------------------------------

				$newpost = str_replace( "\\'", "'", $newpost );

				//-----------------------------------------
				// Convert old dohtml?
				//-----------------------------------------

				$htmlstate = 0;

				if ( strstr( strtolower($newpost), '[dohtml]' ) )
				{
					//-----------------------------------------
					// Can we use HTML?
					//-----------------------------------------

					if ( $type == 'posts' AND $this->registry->class_forums->forum_by_id[ $r['forum_id'] ]['use_html'] )
					{
						$htmlstate = 2;
					}

					$newpost = preg_replace( "#\[dohtml\]#i" , "", $newpost );
					$newpost = preg_replace( "#\[/dohtml\]#i", "", $newpost );
				}
				else
				{
					$htmlstate = intval( $r['post_htmlstate'] );
				}

				//-----------------------------------------
				// Convert old attachment tags
				//-----------------------------------------

				$newpost = preg_replace( "#\[attachmentid=(\d+?)\]#is", "[attachment=\\1:attachment]", $newpost );

				if ( $newpost OR $type == 'sigs' OR $type == 'aboutme' )
				{
					switch( $type )
					{
						case 'posts':
							$this->DB->update( 'posts', array( 'post' => $newpost, 'post_htmlstate' => $htmlstate ), 'pid='.$r['pid'] );
							$string = substr( $r['post'], 0, 30 ) . '...';
							$last = $r['pid'];
						break;

						case 'pms':
							$this->DB->update( 'message_posts', array( 'msg_post' => $newpost ), 'msg_id='.$r['msg_id'] );
							$string = substr( $r['msg_post'], 0, 30 ) . '...';
							$last = $r['msg_id'];
						break;

						case 'sigs':
							$this->DB->update( 'profile_portal', array( 'signature' => $newpost ), 'pp_member_id='.$r['pp_member_id'] );
							$string = substr( $r['members_display_name'], 0, 30 ) . '...';
							$last = $r['pp_member_id'];
						break;

						case 'aboutme':
							$this->DB->update( 'profile_portal', array( 'pp_about_me' => $newpost ), 'pp_member_id='.$r['pp_member_id'] );
							$string = substr( $r['post'], 0, 30 ) . '...';
							$last = $r['pp_member_id'];
						break;

						case 'cal':
							$this->DB->update( 'cal_events', array( 'event_content' => $newpost ), 'event_id='.$r['event_id'] );
							$string = substr( $r['event_content'], 0, 30 ) . '...';
							$last = $r['event_id'];
						break;

						case 'announce':
							$this->DB->update( 'announcements', array( 'announce_post' => $newpost ), 'announce_id='.$r['announce_id'] );
							$string = substr( $r['announce_post'], 0, 30 ) . '...';
							$last = $r['announce_id'];
						break;
					}
				}
				
				unset( $r );
				$done++;
			}
			
			if ( method_exists( $this->DB, 'setQueryRowLocking' ) )
			{
				$this->DB->setQueryRowLocking( 'ON' );
			}
		}
		
		/* Attempt to free memory */
		$this->DB->freeResult( $outer );
		
		/* Memory Debug */
		if ( IPS_CLI_MEMORY_DEBUG )
		{
			IPSDebug::setMemoryDebugFlag( "Done", 0 );
		
			foreach( IPSDebug::$memory_debug  as $i )
			{
				$this->_print( "Debug:... " . $i[0] . ' - ' . IPSLib::sizeFormat( $i[1] ) );
			}
		
			/* Reset */
			IPSDebug::$memory_debug = array();
		}
		
		if ( $done )
		{
			$this->start = intval( $last );
			return "Completed... " . $this->processed . "(last ID[ " . $last . " ] " . str_replace( "\n", "", $string ) . ")";
		}
		else
		{
			return FALSE;
		}
	}
	
	
	/**
	 * Out to stdout
	 */
	protected function _print( $message, $newline="\n" )
	{
		$stdout = fopen('php://stdout', 'w');
		fwrite( $stdout, $message . $newline );
		fclose( $stdout );
	}
	
	/* Fetch option
	 *
	 */
	protected function _fetchOption()
	{
		return trim( fgets( $this->stdin ) );
	}
}

exit();                 



?>