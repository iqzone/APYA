<?php
/**
 * @file		api_topic_view.php 	Forums topic view API
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		2.2.0
 * $LastChangedDate: 2011-12-20 11:14:05 -0500 (Tue, 20 Dec 2011) $
 * @version		v3.3.3
 * $Revision: 10035 $
 */

if ( ! class_exists( 'apiCore' ) )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );/*noLibHook*/
}

/**
 *
 * @class		apiTopicView
 * @brief		Forums topic view API
 */
class apiTopicView extends apiCore
{
	/**
	 * Configuration array for the topic list
	 *
	 * @var		$topic_list_config
	 */
	public $topic_list_config = array(  'offset'		=> 0,
										'limit'			=> 5,
										'forums'		=> '*',
										'order_field'	=> 'last_post',
										'order_by'		=> 'DESC'
									  );
	
	/**
	 * Array of post IDs with attachments
	 *
	 * @var		$attach_pids
	 */
	protected $attach_pids	= array();
	
	/**
	 * Constructor: calls parent init() method and runs other additional calls
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->init();
		
		/* Load forums app main class */
		ipsRegistry::getAppClass( 'forums' );
	}
	
	/**
	 * Returns an array of topics
	 *
	 * @param	boolean		Load only topics viewable by guests?
	 * @return	@e array	Array of topics data
	 */
	public function return_topic_list_data( $view_as_guest=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topics = array();
		
		if( $view_as_guest )
		{
			$this->registry->class_forums->strip_invisible	= true;
			$this->registry->class_forums->forumsInit();
		}
		else
		{
			$this->registry->class_forums->strip_invisible	= false;
			$this->registry->class_forums->forumsInit();
		}

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$this->topic_list_config['order_field'] = ( $this->topic_list_config['order_field'] == 'started' )  ? 'start_date' : $this->topic_list_config['order_field'];
		$this->topic_list_config['order_field'] = ( $this->topic_list_config['order_field'] == 'lastpost' ) ? 'last_post'  : $this->topic_list_config['order_field'];
		$this->topic_list_config['forums']      = ( is_array( $this->topic_list_config['forums'] ) ) ? implode( ",", $this->topic_list_config['forums'] ) : $this->topic_list_config['forums'];
				
		//-----------------------------------------
		// Fix up allowed forums
		//-----------------------------------------
		
		if ( $this->topic_list_config['forums'] )
		{
			# Reset topics...
			if ( $this->topic_list_config['forums'] == '*' )
			{
				$_tmp_array 					   = array();
				$this->topic_list_config['forums'] = '';
				
				foreach( $this->registry->class_forums->forum_by_id as $id => $data )
				{
					$_tmp_forums[] = $id;
				}
			}
			else
			{
				$_tmp_forums                       = explode( ',', $this->topic_list_config['forums'] );
				$_tmp_array 					   = array();
				$this->topic_list_config['forums'] = '';
			}
			
			foreach( $_tmp_forums as $_id )
			{
				$_tmp_array[] = $_id;
			}
			
			$this->topic_list_config['forums'] = implode( ',', $_tmp_array );
		}
		
		//-----------------------------------------
		// Get from the DB
		//-----------------------------------------
		
		$_approved	= $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), 't.' );
		
		$this->DB->build( array( 'select'   => 't.*',
								 'from'     => array( 'topics' => 't' ),
								 'where'    => $_approved . ' AND t.forum_id IN (0,'.$this->topic_list_config['forums'].')',
							     'order'    => $this->topic_list_config['order_field'].' '.$this->topic_list_config['order_by'],
								 'limit'    => array( $this->topic_list_config['offset'], $this->topic_list_config['limit'] ),
								 'add_join' => array( 
													  0 => array( 'select' => 'p.*',
																  'from'   => array( 'posts' => 'p' ),
																  'where'  => 't.topic_firstpost=p.pid',
																  'type'   => 'left' ),
													  1 => array( 'select' => 'm.member_id, m.members_display_name as member_name, m.members_seo_name, m.member_group_id, m.email',
													  			  'from'   => array( 'members' => 'm' ),
																  'where'  => "m.member_id=p.author_id",
																  'type'   => 'left' ),
													  2 => array( 'select' => 'f.id as forum_id, f.name as forum_name, f.use_html, f.name_seo',
													  			  'from'   => array( 'forums' => 'f' ),
																  'where'  => "t.forum_id=f.id",
																  'type'   => 'left' ) )
						)      );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			if( $row['topic_hasattach'] )
			{
				$this->attach_pids[] = $row['pid'];
			}
			
			//-----------------------------------------
			// Guest name?
			//-----------------------------------------
			
			$row['member_name']    = $row['member_name'] ? $row['member_name'] : $row['author_name'];
			
			//-----------------------------------------
			// Topic link
			//-----------------------------------------
			
			$row['link-topic']		= $this->registry->getClass('output')->buildSEOUrl( "showtopic={$row['tid']}", 'public', $row['title_seo'], 'showtopic' );
			$row['link-forum']		= $this->registry->getClass('output')->buildSEOUrl( "showforum={$row['forum_id']}", 'public', $row['name_seo'], 'showforum' );
			$row['link-profile']	= $this->registry->getClass('output')->buildSEOUrl( "showuser={$row['member_id']}", 'public', $row['members_seo_name'], 'showuser' );

			$topics[] = $row;
		}
		
		if( count( $this->attach_pids ) )
		{
			$final_attachments = array();
			
			$this->DB->build( array( 'select'	=> '*',
									 'from'		=> 'attachments',
									 'where'	=> "attach_rel_module='post' AND attach_rel_id IN (" . implode( ",", $this->attach_pids ) . ")"
							 )      );

			$this->DB->execute();
			
			while ( $a = $this->DB->fetch() )
			{
				$final_attachments[ $a[ 'attach_pid' ] ][ $a['attach_id'] ] = $a;
			}
			
			$final_topics = array();
			
			foreach( $topics as $mytopic )
			{
				$this_topic_attachments = array();
				
				foreach ( $final_attachments as $pid => $data )
				{
					if( $pid <> $mytopic['pid'] )
					{
						continue;
					}
					
					$temp_out = "";
					$temp_hold = array();
					
					foreach( $final_attachments[$pid] as $aid => $row )
					{
						//-----------------------------------------
						// Is it an image, and are we viewing the image in the post?
						//-----------------------------------------
						
						if ( $this->settings['show_img_upload'] and $row['attach_is_image'] )
						{
							if ( $this->settings['siu_thumb'] AND $row['attach_thumb_location'] AND $row['attach_thumb_width'] )
							{ 
								$this_topic_attachments[] = array( 'size' 		=> IPSLib::sizeFormat( $row['attach_filesize'] ),
																	'method' 	=> 'post',
																	'id'		=> $row['attach_id'],
																	'file'		=> $row['attach_file'],
																	'hits'		=> $row['attach_hits'],
																	'thumb_location'	=> $row['attach_thumb_location'],
																	'type'		=> 'thumb',
																	'thumb_x'	=> $row['attach_thumb_width'],
																	'thumb_y'	=> $row['attach_thumb_height'],
																	'ext'		=> $row['attach_ext'],
																);
							}
							else
							{
								$this_topic_attachments[] = array( 'size' 		=> IPSLib::sizeFormat( $row['attach_filesize'] ),
																	'method' 	=> 'post',
																	'id'		=> $row['attach_id'],
																	'file'		=> $row['attach_file'],
																	'hits'		=> $row['attach_hits'],
																	'thumb_location'	=> $row['attach_thumb_location'],
																	'type'		=> 'image',
																	'thumb_x'	=> $row['attach_thumb_width'],
																	'thumb_y'	=> $row['attach_thumb_height'],
																	'ext'		=> $row['attach_ext'],
																);
							}
						}
						else
						{
								$this_topic_attachments[] = array( 'size' 		=> IPSLib::sizeFormat( $row['attach_filesize'] ),
																	'method' 	=> 'post',
																	'id'		=> $row['attach_id'],
																	'file'		=> $row['attach_file'],
																	'hits'		=> $row['attach_hits'],
																	'thumb_location'	=> $row['attach_thumb_location'],
																	'type'		=> 'reg',
																	'thumb_x'	=> $row['attach_thumb_width'],
																	'thumb_y'	=> $row['attach_thumb_height'],
																	'ext'		=> $row['attach_ext'],
																);
						}
					}
				}

				if( count( $this_topic_attachments ) )
				{
					$mytopic['attachment_data'] = $this_topic_attachments;
				}
				
				$final_topics[] = $mytopic;
			}
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
				
		if( count( $final_topics ) )
		{
			return $final_topics;
		}
		else
		{
			return $topics;
		}
	}
}