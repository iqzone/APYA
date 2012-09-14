<?php
/**
 * Calendar comments class
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10473 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_calendar_events extends classes_comments_renderer
{
	/**
	 * Internal remap array
	 *
	 * @param	array
	 */
	protected $_remap = array( 'comment_id'			=> 'comment_id',
							 'comment_author_id'	=> 'comment_mid',
							 'comment_author_name'  => 'comment_author',
							 'comment_text'			=> 'comment_text',
							 'comment_ip_address'   => 'ip_address',
							 'comment_edit_date'	=> 'comment_edit_time',
							 'comment_date'			=> 'comment_date',
							 'comment_approved'		=> 'comment_approved',
							 'comment_parent_id'	=> 'comment_eid' );
					 
	/**
	 * Internal parent remap array
	 *
	 * @param	array
	 */
	protected $_parentRemap = array( 'parent_id'			=> 'event_id',
							 	   'parent_owner_id'	=> 'event_member_id',
							       'parent_parent_id'   => 'event_calendar_id',
							       'parent_title'	    => 'event_title',
							       'parent_seo_title'   => 'event_title_seo',
							       'parent_date'	    => 'event_saved' );
	
	/**
	 * Parent SEO template
	 *
	 * @return	string
	 */
	public function seoTemplate()
	{
		return 'cal_event';
	}

	/**
	 * Who am I?
	 *
	 * @return	string
	 */
	public function whoAmI()
	{
		return 'calendar-events';
	}
	
	/**
	 * Comment table
	 *
	 * @return	string
	 */
	public function table()
	{
		return 'cal_event_comments';
	}
	
	/**
	 * Fetch parent
	 *
	 * @return	array
	 */
	public function fetchParent( $id )
	{
		static $cachedEvents	= array();
		
		if( !isset($cachedEvents[ $id ]) )
		{
			$_event					= $this->DB->buildAndFetch( array(
																	'select'	=> 'e.*',
																	'from'		=> array( 'cal_events' => 'e' ),
																	'where'		=> 'e.event_id=' . intval($id),
																	'add_join'	=> array(
																						array(
																							'select'	=> 'c.*',
																							'from'		=> array( 'cal_calendars' => 'c' ),
																							'where'		=> 'c.cal_id=e.event_calendar_id',
																							'type'		=> 'left',
																							),
																						array(
																							'select'	=> 'p.*',
																							'from'		=> array( 'permission_index' => 'p' ),
																							'where'		=> "p.perm_type='calendar' AND p.perm_type_id=c.cal_id",
																							'type'		=> 'left',
																							),
																						),
															)		);
			$cachedEvents[ $id ]	= $_event;
		}
		
		return $cachedEvents[ $id ];
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array( 'urls-showParent' => "app=calendar&module=calendar&section=view&do=showevent&event_id=%s",
					  'urls-report'		=> $this->getReportLibrary()->canReport( 'calendar' ) ? "app=core&amp;module=reports&amp;rcom=calendar&amp;comment_id=%s&amp;event_id=%s" : '' );
	}
	
	/**
	 * Number of items per page
	 *
	 * @return	int
	 */
	public function perPage()
	{
		return 5;
	}
	
	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string	Type of save (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	int		Comment id (if available)
	 * @param	int		Parent id
	 * @return 	array	Array of GENERIC data
	 */
	public function preSave( $type, array $array, $commentId=0, $parentId=0 )
	{
		if ( $type == 'add' )
		{
			$event	= $this->fetchParent( $array['comment_parent_id'] );

			/* Test approval */
			if ( $array['comment_approved'] )
			{
				$array['comment_approved']	= $this->memberData['g_is_supmod'] ? 1 : ( $event['cal_comment_moderate'] ? 0 : 1 );
			}
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'calendarAddComment' );
		}
		else
		{
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'calendarEditComment' ); 
		}
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	int		Comment id (if available)
	 * @param	int		Parent id
	 * @return 	array	Array of GENERIC data
	 */
	public function postSave( $type, array $array, $commentId=0, $parentId=0 )
	{
		$this->_rebuildCommentCount( $parentId );

		IPSLib::doDataHooks( $array, 'calendarComment' . ucfirst( $type ) . 'PostSave' );
		
		return $array;
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postDelete( $commentIds, $parentId )
	{
		$this->_rebuildCommentCount( $parentId );
		
		$_dataHook	= array( 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );
							 
		/* Data Hook Location */
		IPSLib::doDataHooks( $_dataHook, 'calendarCommentPostDelete' );
	}
	
	/**
	 * Toggles visibility
	 * 
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postVisibility( $toggle, $commentIds, $parentId )
	{
		$this->_rebuildCommentCount( $parentId );
		
		$_dataHook	= array( 'toggle'		=> $toggle,
							 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );
							 
		/* Data Hook Location */
		IPSLib::doDataHooks( $_dataHook, 'calendarCommentToggleVisibility' );
	}
	
	/**
	 * Rebuild comment counts for an event
	 *
	 * @param	int		Event ID
	 * @return	@e void
	 */
	protected function _rebuildCommentCount( $parentId )
	{
		$approved	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'cal_event_comments', 'where' => 'comment_approved=1 AND comment_eid=' . $parentId ) );
		$unapproved	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'cal_event_comments', 'where' => 'comment_approved=0 AND comment_eid=' . $parentId ) );
		
		$this->DB->update( 'cal_events', array( 'event_comments' => $approved['total'], 'event_comments_pending' => $unapproved['total'] ), 'event_id=' . $parentId );
	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	int
	 */
	public function count( $parent )
	{
		/* Get parent */
		if ( is_numeric( $parent ) )
		{
			$parent	= $this->fetchParent( $parent );
		}

		if( $this->memberData['g_is_supmod'] )
		{
			return ( intval($parent['event_comments']) + intval($parent['event_comments_pending']) );
		}
		
		return intval($parent['event_comments']);
	}
	
	/**
	 * Perform a permission check
	 *
	 * @param	string	Type of check (add/edit/delete/editall/deleteall/approve all)
	 * @param	array 	Array of GENERIC data
	 * @return	true or string to be used in exception
	 */
	public function can( $type, array $array )
	{ 
		/* Init */
		$comment = array();
		
		/* Got data? */
		if ( empty( $array['comment_parent_id'] ) )
		{
			trigger_error( "No parent ID passed to " . __FILE__, E_USER_WARNING );
		}
		
		/* Get the file */
		$event	= $this->fetchParent( $array['comment_parent_id'] );

		/* Fetch comment */
		if ( $array['comment_id'] )
		{ 
			$comment	= $this->fetchById( $array['comment_id'] );
		}

		/* Check permissions */
		switch( $type )
		{
			case 'view':
				if( !$this->registry->permissions->check( 'view', $event ) )
				{
					return 'NO_PERMISSION';
				}

				if( $event['event_private'] AND ( !$this->memberData['member_id'] OR $this->memberData['member_id'] != $event['event_member_id'] ) )
				{
					return 'NO_PERMISSION';
				}
				
				if( $event['event_perms'] != '*' )
				{
					$permissionGroups	= explode( ',', IPSText::cleanPermString( $event['event_perms'] ) );
					
					if( !IPSMember::isInGroup( $this->memberData, $permissionGroups ) )
					{
						return 'NO_PERMISSION';
					}
				}

				return true;
			break;
			
			case 'edit':
				if( !$this->registry->permissions->check( 'comment', $event ) )
				{
					return 'NO_PERMISSION';
				}

				if( !$this->memberData['g_is_supmod'] AND ( $this->memberData['member_id'] != $comment['comment_author_id'] OR !$comment['comment_author_id'] ) )
				{
					return 'NO_PERMISSION';
				}

				return true;
			break;
			
			case 'add':
				if( !$this->registry->permissions->check( 'comment', $event ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			
			case 'delete':
				return $this->memberData['g_is_supmod'] ? true : 'NO_PERMISSION';
			break;
			
			case 'visibility':
			case 'moderate':
				return $this->memberData['g_is_supmod'] ? true : 'NO_PERMISSION';
			break;
			case 'hide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_HIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
				break;
			case 'unhide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_UNHIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
				break;
		}
	}

	/**
	 * Returns remap keys (generic => local)
	 *
	 * @return	array
	 */
	public function remapKeys( $type='comment' )
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}
}