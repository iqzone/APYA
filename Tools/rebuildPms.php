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
		
		$this->_print( "--------------------------------------------\nWelcome to the IP.Board PM Re-Builder\n--------------------------------------------\n" );
		
		$this->_print( "Start at row (Enter 0 to start from the very first entry)\nEnter: " );

		$this->start = intval( $this->_fetchOption() );
	
		$this->_print( "Process X rows (Enter 0 to process all rows)\nEnter: " );
	
		$this->end = intval( $this->_fetchOption() );
		
		if ( $this->start == 0 )
		{
			$this->_print( "No starting ID? Do you want to remove all current PMs to start again? (y/n): " );

			$delete = $this->_fetchOption();
			
			if ( $delete == 'y' )
			{
				/* Ditch current data */
				$this->DB->delete( 'message_posts' );
				$this->DB->delete( 'message_topics' );
				$this->DB->delete( 'message_topic_user_map' );
			}
		}
		
		while( ( $result = $this->_process() ) !== FALSE )
		{
			$this->_print( $result );
		}
	
		/* We're done.. */
		$this->_print( "COMPLETE" );
	}
	
	/**
	 * Actually process it
	 */
	protected function _process()
	{
		if ( ! $this->end OR ( $this->end > $this->processed ) )
		{
			/* More to do... */
			$batch           = 250;
			$_s 	         = $this->processed + $this->start;
			$_e 	         = ( $this->end AND $this->end < $batch ) ? $this->end : $batch;
			$this->processed = $_s + $batch;
			$this->start	 = 0;
			$converted       = 0;
			$seen            = 0;
			
			/* Select max topic ID thus far */
			$_tmp = $this->DB->buildAndFetch( array( 'select' => 'MAX(mt_id) as max',
													 'from'   => 'message_topics' ) );
												
			$topicID = intval( $_tmp['max'] );
		
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'message_text',
									 'order'  => 'msg_id ASC',
									 'limit'  => array( $_s, $_e ) ) );
								
			$o = $this->DB->execute();
		
			while( $post = $this->DB->fetch( $o ) )
			{
				$seen++;
			
				/* Make sure all data is valid */
				if ( intval( $post['msg_sent_to_count'] ) < 1 )
				{
					continue;
				}
			
				/* a little set up */
				$oldTopics = array();
			
				/* Now fetch all topics */
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'message_topics_old',
										 'where'  => 'mt_msg_id=' . intval( $post['msg_id'] ) ) );
									
				$t = $this->DB->execute();
			
				while( $topic = $this->DB->fetch( $t ) )
				{
					/* Got any data? */
					if ( ! $topic['mt_from_id'] OR ! $topic['mt_to_id'] )
					{
						continue;
					}
				
					$oldTopics[ $topic['mt_id'] ] = $topic;  # Luke added that space. That's his first contribution to the code vaults at IPS.
				}
				
				/* Attempt to free memory */
				$this->DB->freeResult( $t );
			
				/* Fail safe */
				if ( ! count( $oldTopics ) )
				{
					continue;
				}
			
				/* Increment number */
				$topicID++;
			
				/* Add in the post */
				$this->DB->insert( 'message_posts', array( 'msg_topic_id'      => $topicID,
														   'msg_date'          => $post['msg_date'],
														   'msg_post'          => $post['msg_post'],
														   'msg_post_key'      => $post['msg_post_key'],
														   'msg_author_id'     => $post['msg_author_id'],
														   'msg_ip_address'    => $post['msg_ip_address'],
														   'msg_is_first_post' => 1 ) );
				$postID = $this->DB->getInsertId();
				
				/* Update attachments */
				$this->DB->update( 'attachments', array( 'attach_rel_id' => $postID ), "attach_rel_module='msg' AND attach_rel_id=".$post['msg_id'] );
			
				/* Define some stuff. "To" member is added last in IPB 2 */
				$_tmp       = $oldTopics;
				ksort( $_tmp );
				$topicData  = array_pop( $_tmp ); 
				$_invited   = array();
				$_seenOwner = array();
				$_isDeleted = 0;
			
				/* Add the member rows */
				foreach( $oldTopics as $mt_id => $data )
				{
					/* Prevent SQL error with unique index: Seen the owner ID already? */
					if ( $_seenOwner[ $data['mt_owner_id'] ] )
					{
						continue;
					}
				
					$_seenOwner[ $data['mt_owner_id'] ] = $data['mt_owner_id'];
				
					/* Build invited - does not include 'to' person */
					if ( $data['mt_owner_id'] AND ( $post['msg_author_id'] != $data['mt_owner_id'] ) AND ( $topicData['mt_to_id'] != $data['mt_owner_id'] ) )
					{
						$_invited[ $data['mt_owner_id'] ] = $data['mt_owner_id'];/*noLibHook*/
					}
				
					$_isSent  = ( $data['mt_vid_folder'] == 'sent' )   ? 1 : 0;
					$_isDraft = ( $data['mt_vid_folder'] == 'unsent' ) ? 1 : 0;
				
					$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'          => $data['mt_owner_id'],
																		'map_topic_id'         => $topicID,
																		'map_folder_id'        => ( $_isDraft ) ? 'drafts' : 'myconvo',
																		'map_read_time'        => ( $data['mt_user_read'] ) ? $data['mt_user_read'] : ( $data['mt_read'] ? time() : 0 ),
																		'map_user_active'      => 1,
																		'map_user_banned'      => 0,
																		'map_has_unread'       => 0,//( $data['mt_read'] ) ? 0 : 1,
																		'map_is_system'        => 0,
																		'map_last_topic_reply' => $post['msg_date'],
																		'map_is_starter'       => ( $data['mt_owner_id'] == $post['msg_author_id'] ) ? 1 : 0 ) );
				
				}
			
				/* Now, did we see the author? If not, add them too but as inactive */
				if ( ! $_seenOwner[ $post['msg_author_id'] ] )
				{
					$_isDeleted = 1;
				
					/*$this->DB->insert( 'message_topic_user_map', array( 'map_user_id'     => $post['msg_author_id'],
																		'map_topic_id'    => $topicID,
																		'map_folder_id'   => 'myconvo',
																		'map_read_time'   => 0,
																		'map_user_active' => 0,
																		'map_user_banned' => 0,
																		'map_has_unread'  => 0,
																		'map_is_system'   => 0,
																		'map_is_starter'  => 1 ) );*/
				}
			
				$_isSent  = ( $topicData['mt_vid_folder'] == 'sent' )   ? 1 : 0;
				$_isDraft = ( $topicData['mt_vid_folder'] == 'unsent' ) ? 1 : 0;
				
				/* This is for MSSQL. I need a quick fix. This is it. Enjoy. */
				if ( method_exists( $this->DB, 'setTableIdentityInsert' ) )
				{
					$this->DB->setTableIdentityInsert( 'message_topics', 'ON' );
				}
				
				/* Add the topic */
				$this->DB->insert( 'message_topics', array( 'mt_id'			     => $topicID,
															'mt_date'		     => $topicData['mt_date'],
															'mt_title'		     => $topicData['mt_title'],
															'mt_starter_id'	     => $post['msg_author_id'],
															'mt_start_time'      => $post['msg_date'],
															'mt_last_post_time'  => $post['msg_date'],
															'mt_invited_members' => serialize( array_keys( $_invited ) ),
															'mt_to_count'		 => count(  array_keys( $_invited ) ) + 1,
															'mt_to_member_id'	 => $topicData['mt_to_id'],
															'mt_replies'		 => 0,
															'mt_last_msg_id'	 => $postID,
															'mt_first_msg_id'    => $postID,
															'mt_is_draft'		 => $_isDraft,
															'mt_is_deleted'		 => $_isDeleted,
															'mt_is_system'		 => 0 ) );
				
				/* This is for MSSQL. I need a quick fix. This is it. Enjoy. */
				if ( method_exists( $this->DB, 'setTableIdentityInsert' ) )
				{
					$this->DB->setTableIdentityInsert( 'message_topics', 'OFF' );
				}
				
				$converted++;
			}
		}
		
		/* Attempt to free memory */
		$this->DB->freeResult( $o );
		
		/* Clear cached queries */
		$this->DB->obj['cached_queries'] = array();
		
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
		
		if ( $seen )
		{
			/* Check for more.. */
			return "Checked {$seen}, Completed... " . $this->processed . "(last ID=[ " . $topicID . " ] )";
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