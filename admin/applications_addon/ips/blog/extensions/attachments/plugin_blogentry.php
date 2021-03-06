<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog attachment parsing
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_blogentry extends class_attach
{
	/**
	 * Module type
	 *
	 * @access	public
	 * @var		string
	 */
	public $module		= 'blogentry';

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
		$this->mysettings = array( 'siu_thumb'	=> $this->settings['blog_thumb'],
								   'siu_width'	=> $this->settings['blog_thumb_width'],
								   'siu_height'	=> $this->settings['blog_thumb_height'],
								   'upload_dir'	=> $this->settings['blog_upload_dir'] ? $this->settings['blog_upload_dir'] : $this->settings['upload_dir'] );

		return true;
	}

	/**
	 * Checks the attachment and checks for download / show perms
	 *
	 * @access	public
	 * @param	integer		Attachment id
	 * @return	array 		Attachment data
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

		$this->DB->build( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "a.attach_rel_module='".$this->module."' AND a.attach_id=".$attach_id,
												 'add_join' => array( 0 => array( 'select' => 'e.entry_id, e.blog_id',
																				  'from'   => array( 'blog_entries' => 'e' ),
																				  'where'  => "e.entry_id=a.attach_rel_id",
																				  'type'   => 'left' ) )
										)      );

		$attach_sql = $this->DB->execute();

		$attach     = $this->DB->fetch( $attach_sql );

		//-----------------------------------------
		// Check..
		//-----------------------------------------

		if ( empty( $attach['blog_id'] ) )
		{
			return FALSE;
		}

		/* Load up functions lib */
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
		}
	
		$this->registry->getClass('blogFunctions')->buildPerms();

		$blog = $this->registry->getClass('blogFunctions')->loadBlog( $attach['blog_id'] );
		
		if( !$blog['blog_id'] )
		{
			return false;
		}

		return $attach;
	}

	/**
	 * Check the attachment and make sure its OK to display
	 *
	 * @access	public
	 * @param	array		Array of ids
	 * @param	array 		Array of relationship ids
	 * @return	array 		Attachment data
	 */
	public function renderAttachment( $attach_ids, $rel_ids=array(), $attach_post_key=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rows  		= array();
		$query_bits	= array();
		$query 		= '';
		$match 		= 0;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( is_array( $attach_ids ) AND count( $attach_ids ) )
		{
			$query_bits[] = "attach_id IN (" . implode( ",", $attach_ids ) .")";
		}
		
		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			$rel_ids = array_map( 'intval', $rel_ids );
			// We need to reset the array - this query bit will return everything we need, but
			// if we "OR" join the above query bit with this one, it causes bigger mysql loads
			$query_bits	  = array();
			$query_bits[] = "attach_rel_id IN (" . implode( ",", $rel_ids ) . ")";
			
			$match = 1;
		}
		
		if ( $attach_post_key )
		{
			$query_bits[] = "attach_post_key='".$this->DB->addSlashes( $attach_post_key )."'";
			
			$match  = 2;
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
			
			while( $db_row = $this->DB->fetch( $attach_sql ) )
			{
				$_ok = 1;
				
				if ( $match == 1 )
				{
					if ( ! in_array( $db_row['attach_rel_id'], $rel_ids ) )
					{
						$_ok = 0;
					}
				}
				else if ( $match == 2 )
				{
					if ( $db_row['attach_post_key'] != $attach_post_key )
					{
						$_ok = 0;
					}
				}
				
				//-----------------------------------------
				// Ok?
				//-----------------------------------------
				
				if ( $_ok )
				{
					$rows[ $db_row['attach_id'] ] = $db_row;
				}
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $rows;
	}

	/**
	 * Recounts number of attachments for the articles row
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
		// INIT
		//-----------------------------------------

		$cnt = array( 'cnt' => 0 );

		//-----------------------------------------
		// Check..
		//-----------------------------------------

		if ( ! $post_key )
		{
			return 0;
		}


		$this->DB->build( array( "select" => 'COUNT(*) as cnt',
								 'from'   => 'attachments',
								 'where'  => "attach_rel_module='".$this->module."' AND attach_post_key='{$post_key}'") );
		$this->DB->execute();

		$cnt = $this->DB->fetch();

		if ( $cnt['cnt'] )
		{
			$this->DB->buildAndFetch( array( 'update' => 'blog_entries',
											 'set'    => "entry_has_attach=entry_has_attach+" . $cnt['cnt'],
											 'where'  => "entry_id=" . intval( $rel_id ) ) );
		}

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

		$this->DB->build( array( 'select'   => 'entry_id',
												 'from'     => 'blog_entries',
												 'where'    => 'entry_id='. intval( $attachment['attach_rel_id'] )
										) 		);

		$this->DB->execute();

		$entry = $this->DB->fetch();

		if ( isset( $entry['entry_id'] ) )
		{
			$this->DB->build( array( "select" => 'count(*) as cnt',
													 'from'   => 'attachments',
													 'where'  => "attach_rel_module='blogentry' AND attach_rel_id = ".$entry['entry_id'] ) );
			$this->DB->execute();

			$cnt = $this->DB->fetch();

			$count = intval( $cnt['cnt'] );

			$this->DB->build( array( 'update' => 'blog_entries', 'set' => "entry_has_attach=". $count , 'where' => "entry_id=".$entry['entry_id'] ) );
			$this->DB->execute();
		}

		return TRUE;
	}

	/**
	 * Determines if you have permission for bulk attachment removal
	 * Returns TRUE or FALSE
	 * IT really does
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

		$ok_to_remove = FALSE;

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
			$ok_to_remove = TRUE;
		}
		else if ( $this->memberData['g_is_supmod'] )
		{
			$ok_to_remove = TRUE;
		}
		elseif ( $this->memberData['_blogmod']['moderate_edit_comment'] )
		{
			$ok_to_remove = TRUE;
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
		$max_php_size      = intval( IPSLib::getMaxPostSize() );
		$member_id         = intval( $member_id ? $member_id : $this->memberData['member_id'] );
		$space_left        = 0;
		$space_used        = 0;
		$space_allowed     = 0;
		$max_single_upload = 0;
		$space_calculated  = 0;

		if ( $post_key )
		{
			//-----------------------------------------
			// Check to make sure we're not attempting
			// to upload to another's post...
			//-----------------------------------------

			if ( ! $this->memberData['g_is_supmod'] )
			{
				$post = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'posts', 'where' => "post_key='{$post_key}'" ) );

				if ( $post['post_key'] AND ( $post['author_id'] != $member_id ) )
				{
					$space_allowed    = -1;
					$space_calculated = 1;
				}
			}
		}

		//-----------------------------------------
		// Generate total space allowed
		//-----------------------------------------

		$total_space_allowed = ( $this->memberData['g_blog_attach_max'] ? $this->memberData['g_blog_attach_max'] : 0 ) * 1024;

		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------

		if ( ! $member_id )
		{
			$space_allowed = -1;
		}

		if ( ! $space_calculated )
		{
			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------

			if ( $total_space_allowed > 0 )
			{
				if ( $this->memberData['g_blog_attach_per_entry'] )
				{
					//-----------------------------------------
					// Per post limit...
					//-----------------------------------------

					$_space_used = $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure',
																					 'from'   => 'attachments',
																					 'where'  => "attach_post_key='".$post_key."'" ) );

					$space_used    = $_space_used['figure'] ? $_space_used['figure'] : 0;
					$space_allowed = ( $this->memberData['g_blog_attach_per_entry'] * 1024 ) - $space_used;
					$space_allowed = $space_allowed < 0 ? 0 : $space_allowed;
				}
				else
				{
					//-----------------------------------------
					// Global limit...
					//-----------------------------------------

					$_space_used = $this->DB->buildAndFetch( array( 'select' => 'SUM(attach_filesize) as figure',
																					 'from'   => 'attachments',
																					 'where'  => 'attach_member_id='.$member_id." AND attach_rel_module in('blogentry','blogcblock')" ) );

					$space_used    = $_space_used['figure'] ? $_space_used['figure'] : 0;
					$space_allowed = ( $this->memberData['g_blog_attach_max'] * 1024 ) - $space_used;
					$space_allowed = $space_allowed < 0 ? 0 : $space_allowed;
				}
			}
			else
			{
				# Unlimited
				$space_allowed = 0;
			}

			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------

			$space_left = $space_allowed ? $space_allowed : 0;
			$space_left = ($space_left < 0) ? 0 : $space_left;

			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------

			if ( ! $max_single_upload )
			{
				if ( $space_left > 0 AND $space_left < $max_php_size )
				{
					$max_single_upload = $space_left;
				}
				else if ( $max_php_size )
				{
					$max_single_upload = $max_php_size;
				}
			}
		}

		$return = array( 'space_used' => $space_used, 'space_left' => $space_left, 'space_allowed' => $space_allowed, 'max_single_upload' => $max_single_upload, 'total_space_allowed' => $total_space_allowed );

		return $return;
	}

}