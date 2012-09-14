<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * IP.Board calendar attachments plugin
 * Last Updated: $Date: 2012-03-20 18:21:37 -0400 (Tue, 20 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10460 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_event extends class_attach
{
	/**
	 * Module type
	 *
	 * @access	public
	 * @var		string
	 */
	public $module = 'event';
	
	/**
	 * Checks the attachment and checks for download / show perms
	 *
	 * @access	public
	 * @param	integer		Attachment id
	 * @return	mixed 		Attachment data, or false if no permission
	 */
	public function getAttachmentData( $attach_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_ok = 0;
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$attach	= $this->DB->buildAndFetch( array(	'select'	=> 'a.*',
													'from'		=> array( 'attachments' => 'a' ),
													'where'		=> "a.attach_rel_module='" . $this->module . "' AND a.attach_id=" . $attach_id,
													'add_join'	=> array(	0 => array(	'select'	=> 'e.*',
																						'from'		=> array( 'cal_events' => 'e' ),
																						'where'		=> "e.event_id=a.attach_rel_id",
																						'type'		=> 'left' ),
																			1 => array(	'select'	=> 'c.*',
																						'from'		=> array( 'cal_calendars' => 'c' ),
																						'where'		=> "c.cal_id=e.event_calendar_id",
																						'type'		=> 'left' ),
													)
						)		);

		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( empty( $attach['event_id'] ) )
		{
			if( !$this->memberData['g_is_supmod'] AND $attach['attach_member_id'] != $this->memberData['member_id'] )
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
    	// For previews
    	//-----------------------------------------
    	
		if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->memberData['member_id'] )
		{
			return $attach;
		}
		else if( $attach['event_id'] )
		{
			return $attach;
		}

		return FALSE;
	}
	
	/**
	 * Check the attachment and make sure its OK to display
	 *
	 * @access	public
	 * @param	array		Array of ids
	 * @param	array 		Array of relationship ids
	 * @param	string		Post key
	 * @return	array 		Attachment data
	 */
	public function renderAttachment( $attach_ids, $rel_ids=array(), $attach_post_key=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rows		= array();
		$query_bits	= array();
		$query		= '';
		$match		= 0;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( is_array( $attach_ids ) AND count( $attach_ids ) )
		{
			$query_bits[]	= "attach_id IN (" . implode( ",", $attach_ids ) .")";
		}
		
		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			$rel_ids = array_map( 'intval', $rel_ids );
			// We need to reset the array - this query bit will return everything we need, but
			// if we "OR" join the above query bit with this one, it causes bigger mysql loads
			$query_bits		= array();
			$query_bits[]	= "attach_rel_id IN (" . implode( ",", $rel_ids ) . ")";
			$match			= 1;
		}
		
		if ( $attach_post_key )
		{
			$query_bits[]	= "attach_post_key='" . $this->DB->addSlashes( $attach_post_key ) . "'";
			$match			= 2;
		}
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		if( count($query_bits) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='{$this->module}' AND ( " . implode( ' OR ', $query_bits ) . " )" ) );
			$attach_sql = $this->DB->execute();
			
			//-----------------------------------------
			// Loop through and filter off naughty ids
			//-----------------------------------------
			
			while( $row = $this->DB->fetch( $attach_sql ) )
			{
				$_ok = 1;
				
				if ( $match == 1 )
				{
					if ( ! in_array( $row['attach_rel_id'], $rel_ids ) )
					{
						$_ok = 0;
					}
				}
				else if ( $match == 2 )
				{
					if ( $row['attach_post_key'] != $attach_post_key )
					{
						$_ok = 0;
					}
				}
				
				//-----------------------------------------
				// Ok?
				//-----------------------------------------
				
				if ( $_ok )
				{
					$rows[ $row['attach_id'] ]	= $row;
				}
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
	
		return $rows;
	}
	
	/**
	 * Recounts number of attachments for the event
	 *
	 * @access	public
	 * @param	string		Post key
	 * @param	integer		Related ID
	 * @param	array 		Arguments for query
	 * @return	array 		Returns count of items found
	 */
	public function postUploadProcess( $post_key, $rel_id, $args=array() )
	{
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $post_key )
		{
			return array( 'count' => 0 );
		}

		$cnt	= $this->DB->buildAndFetch( array( "select"	=> 'COUNT(*) as cnt',
													'from'	=> 'attachments',
													'where'	=> "attach_post_key='{$post_key}'" ) );

		$this->DB->update( 'cal_events', array( 'event_attachments' => $cnt['cnt'] ), 'event_id=' . intval( $rel_id ) );

		return array( 'count' => $cnt['cnt'] );
	}
	
	/**
	 * Recounts number of attachments for the articles row
	 *
	 * @access	public
	 * @param	array 		Attachment data
	 * @return	boolean
	 */
	public function attachmentRemovalCleanup( $attachment )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}

		//-----------------------------------------
		// Update count
		//-----------------------------------------
		
		$this->DB->update( 'cal_events', 'event_attachments=event_attachments-1', 'event_id=' . intval( $attachment['attach_rel_id'] ), false, true );

		return TRUE;
	}
	
	/**
	 * Determines if you have permission for bulk attachment removal
	 * Returns TRUE or FALSE
	 *
	 * @access	public
	 * @param	array 		Ids to check against
	 * @return	boolean
	 */
	public function canBulkRemove( $attach_rel_ids=array() )
	{
		return $this->memberData['g_is_supmod'] ? true : false;
	}
	
	/**
	 * Determines if you can remove this attachment
	 * Returns TRUE or FALSE
	 * IT really does
	 *
	 * @access	public
	 * @param	array 		Attachment data
	 * @return	boolean
	 */
	public function canRemove( $attachment )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ok_to_remove	= FALSE;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] == $attachment['attach_member_id'] )
		{
			$ok_to_remove	= TRUE;
		}
		else if ( $this->memberData['g_is_supmod'] )
		{
			$ok_to_remove	= TRUE;
		}
		else
		{
			$event	= $this->DB->buildAndFetch( array(	'select'	=> '*',
									 					'from'		=> 'cal_events',
									 					'where'		=> 'event_id=' . intval( $attachment['attach_rel_id'] ) ) );
																						
			if ( $this->memberData['member_id'] == $event['event_member_id'] )
			{
				$ok_to_remove	= TRUE;
			}
		}
		
		return $ok_to_remove;
	}
	
	/**
	 * Returns an array of the allowed upload sizes in bytes.
	 * Return 'space_allowed' as -1 to not allow uploads.
	 * Return 'space_allowed' as 0 to allow unlimited uploads
	 * Return 'max_single_upload' as 0 to not set a limit
	 *
	 * @access	public
	 * @param	string		MD5 post key
	 * @param	id			Member ID
	 * @return	array 		[ 'space_used', 'space_left', 'space_allowed', 'max_single_upload' ]
	 */
	public function getSpaceAllowance( $post_key='', $member_id='' )
	{
		$max_php_size			= IPSLib::getMaxPostSize();
		$member_id				= intval( $member_id ? $member_id : $this->memberData['member_id'] );
		$space_left				= 0;
		$space_used				= 0;
		$space_allowed			= 0;
		$max_single_upload		= 0;
		$total_space_allowed	= 0;
		
		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------
		
		if ( ! $member_id )
		{
			$space_allowed	= -1;
		}
		else
		{
			//-----------------------------------------
			// Generate total space allowed
			//-----------------------------------------

			$total_space_allowed	= ( $this->memberData['g_attach_per_post'] ? $this->memberData['g_attach_per_post'] : $this->memberData['g_attach_max'] ) * 1024;
			
			//-----------------------------------------
			// Generate space used figure
			//-----------------------------------------
			
			if ( $this->memberData['g_attach_per_post'] )
			{
				//-----------------------------------------
				// Per post limit...
				//-----------------------------------------
				
				$_space_used	= $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure', 'from' => 'attachments', 'where' => "attach_post_key='" . $post_key . "'" ) );

				$space_used		= $_space_used['figure'] ? $_space_used['figure'] : 0;
			}
			else
			{
				//-----------------------------------------
				// Global limit...
				//-----------------------------------------
				
				$_space_used	= $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure', 'from' => 'attachments', 'where' => 'attach_member_id=' . $member_id . " AND attach_rel_module IN( 'post', 'msg', 'event' )" ) );

				$space_used		= $_space_used['figure'] ? $_space_used['figure'] : 0;
			}

			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------
		
			if ( $this->memberData['g_attach_max'] > 0 )
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$_g_space_used	= $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure', 'from' => 'attachments', 'where' => 'attach_member_id=' . $member_id . " AND attach_rel_module IN( 'post', 'msg', 'event' )" ) );

					$g_space_used	= $_g_space_used['figure'] ? $_g_space_used['figure'] : 0;
					
					if( ( $this->memberData['g_attach_max'] * 1024 ) - $g_space_used < 0 )
					{
						$space_used				= $g_space_used;
						$total_space_allowed	= $this->memberData['g_attach_max'] * 1024;
						
						$space_allowed			= ( $this->memberData['g_attach_max'] * 1024 ) - $space_used;
						$space_allowed			= $space_allowed < 0 ? -1 : $space_allowed;
					}
					else
					{
						$space_allowed			= ( $this->memberData['g_attach_per_post'] * 1024 ) - $space_used;
						$space_allowed			= $space_allowed < 0 ? -1 : $space_allowed;
					}
				}
				else
				{
					$space_allowed				= ( $this->memberData['g_attach_max'] * 1024 ) - $space_used;
					$space_allowed				= $space_allowed < 0 ? -1 : $space_allowed;
				}
			}
			else
			{
				if ( $this->memberData['g_attach_per_post'] )
				{
					$space_allowed				= ( $this->memberData['g_attach_per_post'] * 1024 ) - $space_used;
					$space_allowed				= $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{
					$space_allowed				= 0;
				}
			}
			
			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------
			
			$space_left		= $space_allowed ? $space_allowed : 0;
			$space_left		= ( $space_left < 0 ) ? -1 : $space_left;
			
			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------
			
			if ( ! $max_single_upload )
			{
				if ( $space_left > 0 AND $space_left < $max_php_size )
				{
					$max_single_upload	= $space_left;
				}
				else if ( $max_php_size )
				{
					$max_single_upload	= $max_php_size;
				}
			}
		}

		IPSDebug::fireBug( 'info', array( 'Space left: ' . $space_left ) );
		IPSDebug::fireBug( 'info', array( 'Max PHP size: ' . $max_php_size ) );
		IPSDebug::fireBug( 'info', array( 'Max single file size: ' . $max_single_upload ) );
		
		$return	= array( 'space_used' => $space_used, 'space_left' => $space_left, 'space_allowed' => $space_allowed, 'max_single_upload' => $max_single_upload, 'total_space_allowed' => $total_space_allowed );
		
		return $return;
	}
	
	/**
	 * Returns an array of settings:
	 * 'siu_thumb' = Allow thumbnail creation?
	 * 'siu_height' = Height of the generated thumbnail in pixels
	 * 'siu_width' = Width of the generated thumbnail in pixels
	 * 'upload_dir' = Base upload directory (must be a full path)
	 *
	 * You can omit any of these settings and IPB will use the default
	 * settings (which are the ones entered into the ACP for post thumbnails)
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function getSettings()
	{
		$this->mysettings	= array();
		
		return true;
	}
}