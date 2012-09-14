<?php
/**
 * <pre>
 * Status Updates Class
 * Holds various functions
 * Last Updated: $Date: 2012-02-20 05:14:03 -0500 (Mon, 20 Feb 2012) $
 * </pre>
 *
 * @author		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @author		MattMecham
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10319 $ 
 *
 * Example: Create new status update
 * <code>
 * $status = new memberStatus( ipsRegistry::instance() )
 *
 * $status->setAuthor( $this->memberData );
 *
 * if ( $status->canCreate() )
 * {
 *		$status->setContent("This is my status update");
 *		$status->create();
 * }
 * </code>
 *
 * Example: Reply to a status update
 * <code>
 * $status = new memberStatus( ipsRegistry::instance() )
 *
 * $status->setAuthor( $this->memberData );
 * $status->setStatusData( $status->fetchMemberLatest( $memberId ) );
 *
 * if ( $status->canReply() )
 * {
 *		$status->setContent("This is my reply");
 *		$status->reply();
 * }
 *
 * Both valid
 * $this->setStatusData( $arrayOfData );
 * $this->setStatusData( $statusId );
 *
 * $this->setReplyData( $arrayOfData );
 * $this->setReplyData( $replyId );
 * </code>
 */

/**
 * Rules:
 * Member and Super Mods can create and delete a member's status
 * Super mods and members NOT banned, restricted posting, moderated posting, ignored by member can reply.
 * Super mods, Reply owner and status owner can delete a reply
 */
class memberStatus
{
	/**
	 * Registry Object Shortcuts
	 * 
	 * @var	object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Status set via status Id entry
	 *
	 * @var		array
	 */
	protected $_statusData			= array();
	
	/**
	 * Owner data of status set via status Id entry
	 *
	 * @var		array
	 */
	protected $_statusOwner			= array();
	
	/**
	 * Bypass all permission checks
	 *
	 * Use with care, this will allow the class to be used within an API
	 * 
	 * @var		boolean
	 */
	protected $_bypassPermChecks = false;
	
	protected $_statusCount      = 0;
	/**
	 * Allowed items to be saved in the get/set array
	 *
	 * @var		array
	 */
	protected $_internalData		= array( 'IsApproved' => 1, 'IsImport' => 0, 'Creator' => 'ipb', 'ExternalUpdates' => array() );
	protected $_allowedInternalData	= array( 'Author',
										   'Friend',
										   'StatusData',
										   'StatusOwner',
										   'IsImport',
										   'Creator',
										   'ReplyData',
										   'ReplyOwner',
										   'ExternalUpdates',
										   'IsApproved',
										   'Content' );
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param	object	Registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		/* Just to be sure */
		$this->setBypassPermissionCheck( false );
		
		/* Just for now */
		$this->settings['su_parse_url']   = 1;
		$this->settings['tc_parse_tags']  = 1;
		$this->settings['tc_parse_names'] = 1;
	}
	
	/**
	 * Gets the count stored by 'fetch'
	 * @return number
	 */
	public function getStatusCount()
	{
		return intval( $this->_statusCount );
	}
	
	/**
	 * Set the bypass permission flag
	 *
	 * @param	boolean
	 * @return	@e void
	 */
	public function setBypassPermissionCheck( $bool=false )
	{
		$this->_bypassPermChecks = ( $bool === true ) ? true : false;
	}
	
	/**
	 * Master check to see if statuses are enabled globally
     *
     * @return	boolean
     */
    public function isEnabled()
    {
    	return ( $this->settings['su_enabled'] ) ? TRUE : FALSE;
    }
    
    /**
	 * Is status locked?
	 *
	 * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @return	bool
	 */
	public function isLocked( $status=null )
	{
		$status  = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
		
		if ( $status['status_replies'] >= $this->settings['su_max_replies'] )
		{
			return 3;
		}
		
		return ( $status['status_is_locked'] ) ? $status['status_is_locked'] : 0;
	}
	
    /**
     * Fetch status actions
     * Default filters are last 10 status actions from everyone sorted in desc date order.
     * 'not_theirs' pulls all actions that are not on their own statuses
     * @param	mixed	[Array of member data OR member ID INT for member updating their status - will use ->getAuthor() if null]	
     * @param	array	Array of sort/filter data ( member_id [int], status_id [int], offset [int], limit [int], unix_cutoff [int], sort_dir [asc,desc], sort_field [string], 'not_theirs' [0,1] )
     */
    public function fetchActions( $author=null, $filters=array() )
    {
    	$author   = ( $author === null ) ? $this->getAuthor() : ( is_array( $author ) ? $author : $this->setAuthor( intval( $author ) ) );
    	$where    = array();
    	$actions  = array();
    	$mids     = array();
    	
    	if ( isset( $filters['member_id'] ) )
    	{
    		$where[] = "s.action_member_id=" . intval( $filters['member_id'] );
    	}
    	
    	if ( isset( $filters['unix_cutoff'] ) )
    	{
    		$where[] = "s.action_date >=" . intval( $filters['unix_cutoff'] );
    	}
    	
    	if ( isset( $filters['status_id'] ) )
    	{
    		$where[] = "s.action_status_id =" . intval( $filters['status_id'] );
    	}
    	
    	if ( isset( $filters['not_theirs'] ) AND isset( $filters['member_id'] ) AND $filters['not_theirs'] )
    	{
    		$where[] = "s.action_status_owner !=" . intval( $filters['member_id'] );
    	}
    	
    	if ( isset( $filters['custom'] ) )
    	{
    		$where[] = "s.action_custom =" . intval( $filters['custom'] );
    	}
    	
    	$sort_dir   = ( $filters['sort_dir'] == 'asc' ) ? 'asc' : 'desc';
    	$sort_field = ( isset( $filters['sort_field'] ) ) ? $filters['sort_field'] : 'action_date';
    	$offset     = ( isset( $filters['offset'] ) ) ? intval( $filters['offset'] ) : 0;
    	$limit      = ( isset( $filters['limit'] ) ) ? intval( $filters['limit'] ) : 10;
    	
    	/* Grab them */
    	$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_actions' => 's' ),
							     'where'    => ( count( $where ) ) ? implode( ' AND ', $where ) : '',
							     'order'    => 's.' . $sort_field . ' ' . $sort_dir,
							     'limit'    => array( $offset, $limit ),
							     'add_join' => array(array(  'select'	=> 'u.*',
															 'from'	    => array( 'member_status_updates' => 'u' ),
															 'where'    => 'u.status_id=s.action_status_id',
															 'type'	    => 'left' ),
							    					 array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.action_member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			/* Format some data */
			$row['action_date_formatted_short'] = $this->registry->getClass('class_localization')->getDate( $row['action_date'], 'SHORT' );
			
			/* Format member */
			$row = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
			
			/* Status owner */
			if ( $row['status_member_id'] )
			{
				$mids[ $row['status_member_id'] ] = $row['status_member_id'];
			}
			
			$actions[ $row['action_id'] ] = $row;
		}
		
		/* members? */
		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids );
			
			foreach( $actions as $id => $row )
			{
				if ( $row['status_member_id'] )
				{
					$actions[ $id ]['owner'] = IPSMember::buildDisplayData( $members[ $row['status_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
				}
			}
		}
		
		/* Phew */
		return $actions;
	}
    	
    /**
     * Fetch status updates
     * Default filters are last 10 status updates from everyone sorted in desc date order.
     * The 'latest_only' flag will grab the last 10 member's updates. Otherwise it'll just grab the last 10 updates which could be from 1 or more people
     * The 'member_id' field will only fetch status updates made by member_id (int) UNLESS type set
     * The 'owner_id' field will fetch status updates made by author_id (INT)
     * The 'status_id' field will only fetch status updates made by status_id (int)
     * The 'friends_only' flag will only fetch status updates from authors friends
     * The 'type' flag can contain 'all', 'mine', 'theirs' - default is 'mine'.
     * The 'isApproved' flag can contain true, false or null (true, approved only, false, unapproved only, null both)
     * getCount - returns a count of all matches without the limit
     * 
     * @param	mixed	[Array of member data OR member ID INT for member updating their status - will use ->getAuthor() if null]	
     * @param	array	Array of sort/filter data ( member_id [int], latest_only [0,1], offset [int], limit [int], unix_cutoff [int], sort_dir [asc,desc], sort_field [string], type [string] )
     */
    public function fetch( $author=null, $filters=array() )
    {
    	$author   = ( $author === null ) ? $this->getAuthor() : ( is_array( $author ) ? $author : $this->setAuthor( intval( $author ) ) );
    	$where    = array();
    	$statuses = array();
    	$mids     = array();
    	$friends  = array();
    	$authors  = array();
    	
    	/* Set up some filters */
    	$filters['type'] = ( empty( $filters['type'] ) ) ? 'mine' : $filters['type'];
    	
    	if ( isset( $filters['member_id'] ) )
    	{
    		$where[] = "s.status_member_id=" . intval( $filters['member_id'] );
    		
    		if ( $filters['type'] == 'mine' )
    		{
    			$where[] = "s.status_author_id=" . intval( $filters['member_id'] );
    		}
    	}
    	
   		if ( isset( $filters['relatedTo'] ) && ! empty( $filters['relatedTo'] ) )
    	{
    		$where[] = "s.status_member_id=" . intval( $filters['relatedTo'] ) . ' OR s.status_author_id=' . intval( $filters['relatedTo'] );   		
    	}
    	
    	if ( isset( $filters['ownerOnly'] ) )
    	{
    		$where[] = "s.status_author_id=s.status_member_id";
    	}
    	
    	if ( isset( $filters['status_id'] ) )
    	{
    		$where[] = "s.status_id=" . intval( $filters['status_id'] );
    	}
    	
    	if ( isset( $filters['unix_cutoff'] ) )
    	{
    		$where[] = "s.status_date >=" . intval( $filters['unix_cutoff'] );
    	}
    	
    	if ( isset( $filters['status_is_latest'] ) )
    	{
    		$where[] = "s.status_is_latest =" . intval( $filters['status_is_latest'] );
    	}
    	
    	if ( isset( $filters['isApproved'] ) && $filters['isApproved'] !== null )
    	{
    		if ( $filters['isApproved'] === true )
    		{
    			$where[] = "s.status_approved=1";
    		}
    		else if ( $filters['isApproved'] === false )
    		{
    			$where[] = "s.status_approved=0";
    		}
    	}
    	
    	if ( !empty( $filters['friends_only'] ) )
    	{
    		/* Hard limit to 300 friends */
    		$this->DB->build( array( 'select' => 'friends_friend_id',
    								 'from'   => 'profile_friends',
    								 'where'  => 'friends_member_id=' . $author['member_id'],
    								 'limit'  => array( 0, 300 ) ) );
    		$this->DB->execute();
    		
    		while( $row = $this->DB->fetch() )
    		{
    			$friends[ $row['friends_friend_id'] ] = $row['friends_friend_id'];
    		}
    		
    		if ( ! count( $friends ) )
    		{
    			$friends[0] = 0;
    		}
    		
    		$where[] = "s.status_member_id IN (" . implode( ",", $friends ) . ")";
    	}
    	
    	$sort_dir   = ( isset( $filters['sort_dir'] ) AND $filters['sort_dir'] == 'asc' ) ? 'asc' : 'desc';
    	$sort_field = ( isset( $filters['sort_field'] ) ) ? $filters['sort_field'] : 'status_date';
    	$offset     = ( isset( $filters['offset'] ) ) ? intval( $filters['offset'] ) : 0;
    	$limit      = ( isset( $filters['limit'] ) ) ? intval( $filters['limit'] ) : 10;
    	$member_id  = !empty( $filters['member_id'] ) ? $filters['member_id'] : 0;
    	
    	if ( ! empty( $filters['getCount'] ) )
    	{
    		$count = $this->DB->buildAndFetch( array( 'select'   => 'count(*) as cnt',
												      'from'	 => array( 'member_status_updates' => 's' ),
												      'where'    => ( count( $where ) ) ? implode( ' AND ', $where ) : '',
												      'add_join' => array(array(
																				  'from'	 => array( 'members' => 'm' ),
																				  'where'    => 'm.member_id=s.status_author_id',
																				  'type'	 => 'left' ),
																		  array( 
																				  'from'	 => array( 'profile_portal' => 'pp' ),
																				  'where'    => 'pp.pp_member_id=m.member_id',
																				  'type'	 => 'left' ) ) ) );
																		  
			$this->_statusCount = intval( $count['cnt'] );
    	}
    	
    	/* Grab them */
    	$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_updates' => 's' ),
							     'where'    => ( count( $where ) ) ? implode( ' AND ', $where ) : '',
							     'order'    => 's.' . $sort_field . ' ' . $sort_dir,
							     'limit'    => array( $offset, $limit ),
							     'add_join' => array(array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.status_author_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			/* Format some data */
			$row['status_replies']              = intval( $row['status_replies'] );
			$row['status_date_formatted_short'] = $this->registry->getClass('class_localization')->getDate( $row['status_date'], 'SHORT' );
			$row['_isLocked']	                = $this->isLocked( $row );
			$row['_userCanReply']               = $this->canReply( $author, $row, $row );
			$row['_canDelete']			        = $this->canDeleteStatus( $author, $row );
			$row['_canLock']			        = $this->canLockStatus( $author, $row );
			$row['_canUnlock']			        = $this->canUnlockStatus( $author, $row );
			$row['_creatorImg']					= $this->creatorImage( $row );
			$row['_creatorText']				= $this->creatorText( $row );
			
			/* For someone else? */
			if ( $row['status_member_id'] != $row['status_author_id'] )
			{
				$authorIds[ $row['status_id'] ] = $row['status_member_id'];
				$mids[$row['status_member_id']] = $row['status_member_id'];
			}
			/* Format member */
			$row = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
			
			/* Replies */
			if ( $row['status_replies'] AND strstr( $row['status_last_ids'], 'a:' ) )
			{
				$replies = unserialize( $row['status_last_ids'] );
				
				if ( is_array( $replies ) AND count( $replies ) )
				{
					ksort( $replies );
					
					foreach( $replies as $r )
					{
						$mids[ $r['reply_member_id'] ] = $r['reply_member_id'];
						$r['reply_date_formatted']     = $this->registry->getClass('class_localization')->getDate( $r['reply_date'], 'SHORT' );
						$r['_canDelete']			   = $this->canDeleteReply( $author, $r, $row );
						$r['reply_status_id']		   = $row['status_id'];
						$row['replies'][ $r['reply_id'] ] = $r;
					}
				}
			}
			else
			{
				$row['replies'] = array();
			}
			
			$statuses[ $row['status_id'] ] = $row;
		}
		
		/* members? */
		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids );
			
			foreach( $statuses as $id => $row )
			{
				if ( $row['status_member_id'] != $row['status_author_id'] )
				{
					$statuses[ $id ]['owner'] = IPSMember::buildDisplayData( $members[ $row['status_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
				}
				
				if ( is_array( $row['replies'] ) )
				{
					foreach( $row['replies'] as $rid => $r )
					{ 
						if ( $members[ $r['reply_member_id'] ] )
						{
							if ( ! isset( $members[ $r['reply_member_id'] ]['_done'] ) )
							{
								$members[ $r['reply_member_id'] ] = IPSMember::buildDisplayData( $members[ $r['reply_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
								$members[ $r['reply_member_id'] ]['_done'] = 1;
							}
							
							$statuses[ $id ]['replies'][ $rid ] = array_merge( $statuses[ $id ]['replies'][ $rid ], $members[ $r['reply_member_id'] ] );
						}
					}
				}
			}
		}
		
		/* Phew */
		return $statuses;
    }
    
    /**
     * Fetch all replies to a status
     * Default filters are sorted on reply_date ASC
     *
     * @param	mixed	[Array of member data OR member ID INT for member updating their status - will use ->getAuthor() if null]	
     * @param	array	Array of sort/filter data ( member_id [int], latest_only [0,1], offset [int], limit [int], unix_cutoff [int], sort_dir [asc,desc], sort_field [string] )
     */
    public function fetchAllReplies( $status=null, $filters=array() )
    {
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	$where    = array();
    	$replies = array();
    	
    	$sort_dir   = ( $filters['sort_dir'] == 'desc' ) ? 'desc' : 'asc';
    	$sort_field = ( isset( $filters['sort_field'] ) ) ? $filters['sort_field'] : 'reply_date';
    	$offset     = ( isset( $filters['offset'] ) ) ? intval( $filters['offset'] ) : 0;
    	$limit      = ( isset( $filters['limit'] ) ) ? intval( $filters['limit'] ) : 100;
        	
    	/* Grab them */
    	$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_replies' => 's' ),
							     'where'    => 's.reply_status_id=' . intval( $status['status_id'] ),
							     'order'    => 's.' . $sort_field . ' ' . $sort_dir,
							     'limit'    => array( $offset, $limit ),
							     'add_join' => array(array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.reply_member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			/* Format some data */
			$row['reply_date_formatted'] = $this->registry->getClass('class_localization')->getDate( $row['reply_date'], 'SHORT' );
			$row['_canDelete']			 = $this->canDeleteReply( $this->getAuthor(), $row, $status );
			
			/* Format member */
			$row = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
			
			$replies[ $row['reply_id'] ] = $row;
		}
		
		/* Phew */
		return $replies;
    }
    
    /**
     * Create a status update for a member
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param   array   [Array of member data for owner of status update. If null and StatusOwner empty, getAuthor will be used]
     * @return	array	Status information
     */
    public function create( $author=null, $owner=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$_owner = $this->getStatusOwner();
    	$owner  = ( $owner  === null ) ? ( ! empty( $_owner['member_id'] ) ? $_owner : $author ) : $owner;
    	$data	= array();
    	
    	if ( $this->canCreate( $author, $owner ) )
    	{
    		if ( $this->getContent() )
    		{
    			$content = $this->_cleanContent( $this->getContent() );
    			$hash    = IPSText::contentToMd5( $content );
    			
    			/* Check for this status update already created */
    			$test = $this->fetchByHash( $owner['member_id'], $hash );
    			
    			if ( $test['status_id'] )
    			{
    				/* Already imported this one */
    				return FALSE;
    			}
    			
    			$data = array( 'status_member_id' => $owner['member_id'],
    						   'status_author_id' => $author['member_id'],
							   'status_date'	  => time(),
							   'status_content'   => $this->_parseContent( $content, $this->_internalData['Creator'] ),
							   'status_hash'      => $hash,
							   'status_replies'	  => 0,
    						   'status_author_ip' => $this->member->ip_address,
    						   'status_approved'  => $this->getIsApproved(),
							   'status_imported'  => intval( $this->_internalData['IsImport'] ),
							   'status_creator'   => trim( addslashes( $this->_internalData['Creator'] ) ),
							   'status_last_ids'  => '' );

				/* Data Hook Location */
				IPSLib::doDataHooks( $data, 'statusUpdateNew' );
		
    			$this->DB->insert( 'member_status_updates', $data );
    			
    			$status_id = $this->DB->getInsertId();
    			
    			$data['status_id']	= $status_id;
    			 
    			if ( $owner['member_id'] != $author['member_id'] )
    			{
    				$this->_sendCommentNotification( $author, $owner, $data );
    			}
    			else
    			{
	    			$this->_recordAction( 'new', $author, $data );
	    			
	    			$this->rebuildOwnerLatest( $owner );
	    			
	    			/* Fire off external updates */
	    			$eU = $this->getExternalUpdates();
	    			
	    			if ( ! $this->_internalData['IsImport'] AND is_array( $eU ) )
	    			{
	    				$this->_triggerExternalUpdates( $eU, $status_id, $owner, $content );
	    			}
	    			
	    			//-----------------------------------------
	    			// Notify owner's friends as configured
	    			//-----------------------------------------
	    			
	    			$friends	= array();
	    			
	    			if ( $this->settings['friends_enabled'] AND $author['member_id'] == $owner['member_id'] )
	    			{
		    			$this->DB->build( array( 'select' => 'friends_member_id', 'from' => 'profile_friends', 'where' => 'friends_friend_id=' . $owner['member_id'] ) );
		    			$this->DB->execute();
		    			
		    			while( $_friend = $this->DB->fetch() )
		    			{
		    				$friends[ $_friend['friends_member_id'] ]	= $_friend['friends_member_id'];
		    			}
					}
					
					if( count($friends) )
					{
						//-----------------------------------------
						// Notifications library
						//-----------------------------------------
						
						$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
						$notifyLibrary		= new $classToLoad( $this->registry );
							
		    			$friends	= IPSMember::load( $friends );
		    			
		    			foreach( $friends as $friend )
		    			{	
							$friend['language'] = $friend['language'] == "" ? IPSLib::getDefaultLanguage() : $friend['language'];
							
				    		$ndata = array( 'NAME'		=> $friend['members_display_name'],
				    					   'OWNER'		=> $owner['members_display_name'],
										   'STATUS'		=> $data['status_content'],
										   'URL'		=> $this->settings['base_url'] . 'app=core&amp;module=usercp&amp;tab=core&amp;area=notifications' );
																				
							IPSText::getTextClass('email')->getTemplate( 'new_status', $friend['language'] );
							
							IPSText::getTextClass('email')->buildMessage( $ndata );
							
							IPSText::getTextClass('email')->subject	= sprintf( 
																				IPSText::getTextClass('email')->subject, 
																				$this->registry->output->buildSEOUrl( 'showuser=' . $owner['member_id'], 'publicNoSession', $owner['members_seo_name'], 'showuser' ),
																				$owner['members_display_name'],
																				$this->registry->output->buildSEOUrl( 'app=members&module=profile&section=status&do=list&status_id=' . $status_id, 'publicNoSession' )
																			);
			
							$notifyLibrary->setMember( $friend );
							$notifyLibrary->setFrom( $author );
							$notifyLibrary->setNotificationKey( 'friend_status_update' );
							$notifyLibrary->setNotificationUrl( $this->registry->output->buildSEOUrl( 'app=members&module=profile&section=status&do=list&status_id=' . $status_id, 'publicNoSession' ) );
							$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
							$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
							
							try
							{
								$notifyLibrary->sendNotification();
							}
							catch( Exception $e ){}
						}
					}
	    		}
    		}
    		
    		return $data;
    	}
    	
    	return FALSE;
    }

    /**
     * Create a status update for a member
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
     * @return	array	Reply information
     */
    public function reply( $author=null, $status=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	$data	= array();
    	
    	if ( $this->canReply( $author, $status, $status ) )
    	{
    		if ( $this->getContent() AND $status['status_id'] )
    		{
    			$data = array( 'reply_status_id'  => $status['status_id'],
							   'reply_member_id'  => $author['member_id'],
							   'reply_date'	      => time(),
							   'reply_content'    => $this->_cleanContent( $this->getContent() ) );

				/* Data Hook Location */
				IPSLib::doDataHooks( $data, 'statusCommentNew' );
				
    			$this->DB->insert( 'member_status_replies', $data );
				
				$data['reply_id'] = $this->DB->getInsertId();
				
				$this->_recordAction( 'reply', $author, $status, $data );
				 
    			$this->rebuildStatus( $status ); 
    			$this->rebuildOwnerLatest( $author );
    			
    			$this->_sendNotification( $author, $status, $data );
    		}
    		
    		return $data;
    	}
    	
    	return FALSE;
    }
    
    /**
     * Triggers external postings Twitter, etc
     *
     * @param	array		Update to...
     * @param	int			Status ID just posted
     * @param	array		[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param	string  	[Content to update]
     * @todo [Future]		At some point it could be expanded into a mini framework with plugins 
     */
    protected function _triggerExternalUpdates( $updates, $status_id=0, $author=null, $content=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$content = ( $content ) ? $this->_cleanContent( $content ) : $this->_cleanContent( $this->getContent() );
    	
    	/* Fail safe */
    	if ( ! $author['member_id'] OR ! $content )
    	{
    		return false;
    	}
    	
    	/* Twitter */
    	if ( $updates['twitter'] )
    	{
    		if ( IPSLib::twitter_enabled() AND $author['twitter_id'] )
    		{
    			$url     = $this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status_id;
    			
    			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
    			$twitter = new $classToLoad( $this->registry, $author['twitter_token'], $author['twitter_secret'] );
				
				$twitter->updateStatusWithUrl( $content, $url, FALSE );
    		}
    	}
    
    	/* Facebook */
    	if ( $updates['facebook'] )
    	{
    		if ( IPSLib::fbc_enabled() AND $author['fb_uid'] )
    		{ 
    			$url     = $this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status_id;
    			
    			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
    			$facebook = new $classToLoad( $this->registry );
				$facebook->updateStatusWithUrl( $content, $url, FALSE );
    		}
    	}
    }
    
    /**
     * Send notification
     *
     * Emails users of new status replies
     * Emails owner (if selected) and anyone else who replied (if selected)
     *
     * @param	array		Author array
     * @param	array		Status array
     * @param	array		Reply array
     * @return	boolean
     */
    protected function _sendNotification( $author, $status, $reply )
    {
    	if ( $author['member_id'] AND $status['status_id'] AND $reply['reply_id'] )
    	{
    		/* Get members */
    		$members = IPSMember::load( array( $author['member_id'], $reply['reply_member_id'], $status['status_member_id'] ), 'core' );
    		
    		$_author  = $members[ $author['member_id'] ];
    		$_owner   = $members[ $status['status_member_id'] ];
    		$_replier = $members[ $reply['reply_member_id'] ];
    		
    		if ( $_author['member_id'] AND $_owner['member_id'] AND $_replier['member_id'] )
    		{
	    		/* Make sure we have the correct language pack */
	    		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
	    		
	       		/* Reset count */
	    		$count   = 0;
	    		$blurb   = '';
	    		$subject = '';
	    		$_ids    = array();
	    		$members = array();
	    		
	    		/* Did the status owner want an email? */
	    		if ( $_owner['member_id'] != $_replier['member_id'] )
	    		{
					//-----------------------------------------
					// Notifications library
					//-----------------------------------------
					
					$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
					$notifyLibrary		= new $classToLoad( $this->registry );
					
					$_owner['language'] = $_owner['language'] == "" ? IPSLib::getDefaultLanguage() : $_owner['language'];
					
	    			$data = array( 'NAME'		=> $_owner['members_display_name'],
	    						   'OWNER'		=> $_owner['members_display_name'],
								   'POSTER'		=> $_replier['members_display_name'],
								   'BLURB'		=> $this->lang->words['email_blurb_yours'],
								   'STATUS'		=> $status['status_content'],
								   'URL'		=> $this->settings['base_url'] . 'app=core&amp;module=usercp&amp;tab=core&amp;area=notifications',
								   'TEXT'		=> $reply['reply_content']  );
																		
					IPSText::getTextClass('email')->getTemplate( 'status_reply', $_owner['language'] );
					
					IPSText::getTextClass('email')->buildMessage( $data	);
					
					IPSText::getTextClass('email')->subject	= sprintf( 
																		IPSText::getTextClass('email')->subject, 
																		$this->registry->output->buildSEOUrl( 'showuser=' . $_replier['member_id'], 'public', $_replier['members_seo_name'], 'showuser' ), 
																		$_replier['members_display_name'],
																		$this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status['status_id']
																	);
	
					$notifyLibrary->setMember( $_owner );
					$notifyLibrary->setFrom( $_replier );
					$notifyLibrary->setNotificationKey( 'reply_your_status' );
					$notifyLibrary->setNotificationUrl( $this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status['status_id'] );
					$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
					$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
					
					try
					{
						$notifyLibrary->sendNotification();
					}
					catch( Exception $e ){}
	    		}
	    		
	    		/* Now fetch everyone else who has replied */
	    		$this->DB->build( array( 'select'   => 'reply_member_id',
	    								 'from'     => 'member_status_replies',
	    								 'where'    => 'reply_status_id=' . $status['status_id'] . ' AND reply_member_id NOT IN (' . intval ( $_replier['member_id'] ) . ')' ) );
	    		$this->DB->execute();
	    		
	    		while( $row = $this->DB->fetch() )
	    		{
	    			$_ids[ $row['reply_member_id'] ] = $row['reply_member_id'];
	    		} 

	    		if ( count( $_ids ) )
	    		{
	    			$members = IPSMember::load( $_ids, 'core' );
	    		}
	    		
	    		if ( is_array( $members ) AND count( $members ) )
	    		{
					//-----------------------------------------
					// Notifications library
					//-----------------------------------------
					
					$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
					$notifyLibrary		= new $classToLoad( $this->registry );

	    			foreach( $members as $id => $member )
	    			{
    					/* Replying to your own status */
    					if ( $_owner['member_id'] == $member['member_id'] )
    					{
    						continue;
    					}
    					
	    				$blurb   = '';
	    				$subject = '';
	    				
	    				/* User made a reply to their own status */
	    				if ( $_replier['member_id'] == $_owner['member_id'] )
	    				{
	    					$blurb   = $this->lang->words['email_blurb_theirs'];
	    					$subject = sprintf( $this->lang->words['email_title_blurb_other'], $_owner['members_display_name']);
	    				}
	    				else
	    				{
	    					$blurb   = sprintf( $this->lang->words['email_blurb_other'], $_owner['members_display_name']);
	    					$subject = sprintf( $this->lang->words['email_title_blurb_other'], $_owner['members_display_name']);
	    				}
						
						$member['language'] = $member['language'] == "" ? IPSLib::getDefaultLanguage() : $member['language'];
						
			    		$data = array( 'NAME'		=> $member['members_display_name'],
			    					   'OWNER'		=> $_owner['members_display_name'],
									   'POSTER'		=> $_replier['members_display_name'],
									   'BLURB'		=> $blurb,
									   'STATUS'		=> $status['status_content'],
									   'URL'		=> $this->settings['base_url'] . 'app=core&amp;module=usercp&amp;tab=core&amp;area=notifications',
									   'TEXT'		=> $reply['reply_content']  );
																			
						IPSText::getTextClass('email')->getTemplate( 'status_reply', $member['language'] );
						
						IPSText::getTextClass('email')->buildMessage( $data	);
						
						if( $_owner['member_id'] == $member['member_id'] )
						{
							IPSText::getTextClass('email')->subject	= sprintf( 
																				IPSText::getTextClass('email')->subject, 
																				$this->registry->output->buildSEOUrl( 'showuser=' . $_replier['member_id'], 'public', $_replier['members_seo_name'], 'showuser' ), 
																				$_replier['members_display_name'],
																				$this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status['status_id']
																			);
						}
						else
						{
							IPSText::getTextClass('email')->subject	= sprintf( 
																				$this->lang->words['subject__other_status_reply'], 
																				$this->registry->output->buildSEOUrl( 'showuser=' . $_replier['member_id'], 'public', $_replier['members_seo_name'], 'showuser' ), 
																				$_replier['members_display_name'],
																				$this->registry->output->buildSEOUrl( 'showuser=' . $_owner['member_id'], 'public', $_owner['members_seo_name'], 'showuser' ), 
																				$_owner['members_display_name'],
																				$this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status['status_id']
																			);
						}

						$notifyLibrary->setMember( $member );
						$notifyLibrary->setFrom( $_replier );
						$notifyLibrary->setNotificationKey( 'reply_any_status' );
						$notifyLibrary->setNotificationUrl( $this->settings['base_url'] . 'app=members&module=profile&section=status&do=list&status_id=' . $status['status_id'] );
						$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
						$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
						
						try
						{
							$notifyLibrary->sendNotification();
						}
						catch( Exception $e ){}
					}
				}

				return TRUE;
			}
    	}
    	
    	return FALSE;
    }
    
	/**
     * Send comment notification
     *
     * Emails users of new status replies
     * Emails owner (if selected) and anyone else who replied (if selected)
     *
     * @param	array		Author array
     * @param	array		Status array
     * @param	array		Reply array
     * @return	boolean
     */
    protected function _sendCommentNotification( $author, $owner, $status )
    {
 		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );

		IPSText::getTextClass('email')->getTemplate( "new_comment_added", $owner['language'] );
	
		IPSText::getTextClass( 'email' )->buildMessage( array(  'MEMBERS_DISPLAY_NAME'	=> $owner['members_display_name'],
																'COMMENT_NAME'			=> $author['members_display_name'],
																'LINK'					=> $this->settings['board_url'] . '/index.' . $this->settings['php_ext'] . '?showuser=' . $owner['member_id'] )	 );

		IPSText::getTextClass('email')->subject	= sprintf(  IPSText::getTextClass('email')->subject, 
															$this->registry->output->buildSEOUrl( 'showuser=' . $owner['member_id']  . '&amp;tab=status', 'public', $owner['members_seo_name'], 'showuser' ),
															$this->registry->output->buildSEOUrl( 'showuser=' . $author['member_id'], 'public', $author['members_seo_name'], 'showuser' ), 
															$this->memberData['members_display_name'] );

		$notifyLibrary->setMember( $owner );
		$notifyLibrary->setFrom( $author );
		$notifyLibrary->setNotificationKey( 'profile_comment' );
		$notifyLibrary->setNotificationUrl( $this->registry->output->buildSEOUrl( 'showuser=' . $owner['member_id']  . '&amp;tab=status', 'public', $owner['members_seo_name'], 'showuser' ) );
		$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
		$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
		try
		{
			$notifyLibrary->sendNotification();
		}
		catch( Exception $e ){}
    	
    	return FALSE;
    }
    
    /**
     * Lock status
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
     * @return	boolean
     */
    public function lockStatus( $author=null, $status=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	
    	if ( $status['status_id'] )
		{
			if ( $this->canLockStatus( $author, $status ) )
			{
				/* Value? */
				$val = ( $author['g_is_supmod'] AND $author['member_id'] != $status['status_member_id'] ) ? 2 : 1;
				
				/* Update status */
				$this->DB->update( 'member_status_updates', array( 'status_is_locked' => $val ), 'status_id=' . $status['status_id'] );
				
				/* Rebuild latest */
				$this->rebuildOwnerLatest( $author );
				
				return TRUE;
			}
		}
		
		return FALSE;
    }
    
    /**
     * Unlock status
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
     * @return	boolean
     */
    public function unlockStatus( $author=null, $status=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	
    	if ( $status['status_id'] )
		{
			if ( $this->canUnlockStatus( $author, $status ) )
			{
				/* Update status */
				$this->DB->update( 'member_status_updates', array( 'status_is_locked' => 0 ), 'status_id=' . $status['status_id'] );
				
				/* Rebuild latest */
				$this->rebuildOwnerLatest( $author );
				
				return TRUE;
			}
		}
		
		return FALSE;
    }

    /**
     * Delete all user's status'
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @return	boolean
     */
    public function deleteAllMemberStatus( $author=null )
    {
    	$author   = ( $author === null ) ? $this->getAuthor() : $author;
    	$statuses = array();
    	
    	$this->DB->build( array( 'select' => '*',
    							 'from'   => 'member_status_updates',
    							 'where'  => 'status_member_id=' . $author['member_id'] ) );
    							 
    	$this->DB->execute();
    	
    	while( $row = $this->DB->fetch() )
    	{
    		$statuses[] = $row['status_id'];
    	}
    	
    	/* Delete data */
    	if ( count( $statuses ) )
    	{
    		$id = implode( ',', $statuses );
    		
    		/* Delete replies */
			$this->DB->delete( 'member_status_replies', 'reply_status_id IN(' . $id . ')' );
			
			/* Delete actions */
			$this->DB->delete( 'member_status_actions', 'action_status_id IN(' . $id . ')' );
			
			/* Delete statuses */
    		$this->DB->delete( 'member_status_updates', 'status_id IN(' . $id . ')' );
    	}
    	
    	/* Flush caches */
		$this->rebuildOwnerLatest( $author );
    }

     /**
     * Delete all user's replies
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @return	boolean
     */
    public function deleteAllReplies( $author=null )
    {
    	$author    = ( $author === null ) ? $this->getAuthor() : $author;
    	$statusIds = array();
    	$replyIds  = array();
    	$memberIds = array();
    	$stom      = array();
    	
    	/* Fetch reply IDs */
    	$this->DB->build( array( 'select' => '*',
    							 'from'   => 'member_status_replies',
    							 'where'  => 'reply_member_id=' . $author['member_id'] ) );
    							 
    	$this->DB->execute();
    	
    	while( $row = $this->DB->fetch() )
    	{
    		$replyIds[]  = $row['reply_id'];
    		$statusIds[] = $row['reply_status_id'];
    	}
    	
    	/* If nothing, goowf */
    	if ( ! count( $statusIds ) OR ! count( $replyIds ) )
    	{
    		return FALSE;
    	}
    	
    	/* Now fetch status owners to recache them */
    	$this->DB->build( array( 'select' => 'status_id, status_member_id',
    							 'from'   => 'member_status_updates',
    							 'where'  => 'status_id IN(' . implode( ',', $statusIds ) . ')' ) );
    							 
    	$this->DB->execute();
    	
    	while( $row = $this->DB->fetch() )
    	{
    		$memberIds[]               = $row['status_member_id'];
    		$stom[ $row['status_id'] ] = $row['status_member_id'];
    	}
    	
    	/* Got member ids? */
    	$members = IPSMember::load( $memberIds, 'all' );
    	
    	/* Delete data */
    	$id = implode( ',', $replyIds );
    	
    	/* Delete replies */
		$this->DB->delete( 'member_status_replies', 'reply_id IN(' . $id . ')' );
		
		/* Delete actions */
		$this->DB->delete( 'member_status_actions', 'action_reply_id IN(' . $id . ')' );
		
		foreach( $statusIds as $sid )
		{
			/* Rebuild this status */
			$this->rebuildStatus( $sid ); 
			
			/* Rebuild latest */
			$this->rebuildOwnerLatest( $members[ $stom[ $sid ] ] );
		}
    }
    
    /**
     * Delete status
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
     * @return	boolean
     */
    public function deleteStatus( $author=null, $status=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	
    	if ( $status['status_id'] )
		{
			if ( $this->canDeleteStatus( $author, $status ) )
			{
				/* Delete status */
				$this->DB->delete( 'member_status_updates', 'status_id=' . $status['status_id'] );
				
				/* Delete replies */
				$this->DB->delete( 'member_status_replies', 'reply_status_id=' . $status['status_id'] );
				
				/* Delete actions */
				$this->DB->delete( 'member_status_actions', 'action_status_id=' . $status['status_id'] );
				
				/* Rebuild latest */
				$this->rebuildOwnerLatest( $author );
				
				return TRUE;
			}
		}
		
		return FALSE;
    }
    
    /**
     * Delete reply
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @param	array	[Array of status reply information OR reply ID OR uses $this->_internalData['ReplyData'] if none]
     * @return	boolean
     */
    public function deleteReply( $author=null, $status=null, $reply=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	$reply   = ( $reply  === null ) ? $this->_internalData['ReplyData']  : ( ( is_array( $reply ) ) ? $reply : $this->_loadReply( $reply ) );
    	
    	if ( $status['status_id'] AND $reply['reply_id'] )
		{
			if ( $this->canDeleteReply( $author, $reply, $status ) )
			{
				/* Delete replies */
				$this->DB->delete( 'member_status_replies', 'reply_id=' . $reply['reply_id'] );
				
				/* Delete actions */
				$this->DB->delete( 'member_status_actions', 'action_reply_id=' . $reply['reply_id'] );
				
				/* Rebuild this status */
				$this->rebuildStatus( $status ); 
				
				/* Rebuild latest */
				$this->rebuildOwnerLatest( $author );
				
				return TRUE;
			}
		}
		
		return FALSE;
    }
    
    /**
     * Rebuild status data
     *
     * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
     * @return	boolean
     */
    public function rebuildStatus( $status=null )
    {
    	$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
    	$last   = array();
    	
    	if ( $status['status_id'] )
		{
			/* Fetch the number of replies */
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
													  'from'   => 'member_status_replies',
													  'where'  => 'reply_status_id=' . intval( $status['status_id'] ) ) );
													  
			/* Fetch last 3 replies */
			$this->DB->build( array( 'select' => 'reply_id, reply_member_id, reply_date, reply_content',
									 'from'   => 'member_status_replies',
									 'order'  => 'reply_date DESC',
									 'limit'  => array( 0, 3 ),
									 'where'  => 'reply_status_id=' . intval( $status['status_id'] ) ) );
									 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$last[ $row['reply_date'] ] = $row;
			}
			
			if ( count( $last ) )
			{
				ksort( $last );
			}
			 
			/* Reset data */
			$this->DB->update( 'member_status_updates', array( 'status_replies' => intval( $count['count'] ), 'status_last_ids' => serialize( $last ) ), 'status_id=' . $status['status_id'] );
		}
    	
    	return TRUE;
    }
    
    /**
     * Rebuilds status data such as last status update, etc.
     *
     * @param	array	[Array of member data for member updating their status - will use ->getAuthor() if null]
     * @return	boolean
     */
    public function rebuildOwnerLatest( $author=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
    	$last   = array();
    	
    	/* Reset last flag for everyone */
    	$this->DB->update( 'member_status_updates', array( 'status_is_latest' => 0 ), 'status_member_id=' . intval( $author['member_id'] ) );
    	
    	/* Fetch the latest update */
    	$status = $this->fetchMemberLatest( $author['member_id'] );
		    									   
		if ( $status['status_id'] )
		{
			/* Fetch the number of replies */
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
													  'from'   => 'member_status_replies',
													  'where'  => 'reply_status_id=' . intval( $status['status_id'] ) ) );
													  
			/* Fetch last 3 replies */
			$this->DB->build( array( 'select' => 'reply_id, reply_member_id, reply_date, reply_content',
									 'from'   => 'member_status_replies',
									 'order'  => 'reply_date DESC',
									 'limit'  => array( 0, 3 ),
									 'where'  => 'reply_status_id=' . intval( $status['status_id'] ) ) );
									 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$last[ $row['reply_date'] ] = $row;
			}
			
			ksort( $last );
			 
			/* Reset data */
			$this->DB->update( 'member_status_updates', array( 'status_is_latest' => 1, 'status_replies' => intval( $count['count'] ), 'status_last_ids' => serialize( $last ) ), 'status_id=' . $status['status_id'] );
		}
		
		return TRUE;
    }
    
    /**
     * Fetch the status update by hash
     *
     * @param	int		Member ID
     */
    public function fetchByHash( $memberId, $hash )
    {
    	$status = array();
    	
    	$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_updates' => 's' ),
							     'where'    => 's.status_member_id=' . intval( $memberId ) . ' AND s.status_imported=1 AND s.status_hash=\'' . addslashes( $hash ) . '\'',
							     'add_join' => array(array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.status_member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$this->DB->execute();
		
		$status = $this->DB->fetch();
		
		return is_array( $status ) ? $status : array();
    }
    
    /**
     * Fetch the member's latest status update
     *
     * @param	int		Member ID
     */
    public function fetchMemberLatest( $memberId )
    {
    	$status = array();
    	$member = array();
    	
    	$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_updates' => 's' ),
							     'where'    => 's.status_member_id=' . intval( $memberId ) . ' AND s.status_author_id=' . intval( $memberId ),
							     'order'    => 's.status_date DESC',
							     'limit'    => array( 0, 1 ),
							     'add_join' => array(array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.status_member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$this->DB->execute();
		
		$row = $this->DB->fetch();
		
		if ( is_array( $row ) AND count( $row ) )
		{
			foreach( $row as $k => $v )
			{
				if ( substr( $k, 0, 7 ) != 'status_' )
				{
					$member[ $k ] = $v;
				}
				
				$status[ $k ] = $v;
			}
		}
		
		$this->_internalData['StatusData']  = $status;
		$this->_internalData['StatusOwner'] = $member;
		    									   
		return is_array( $status ) ? $status : array();
    }
    
    /**
     * Fetch creator image
     *
     * @return	string (img URL)
     */
    public function creatorImage( $status )
    {
    	/* Got a creator? */
    	if ( ! $status['status_creator'] )
    	{
    		$status['status_creator'] = 'ipb';
    	}
    	
    	$creator = IPSText::alphanumericalClean( $status['status_creator'] );
    	
    	/* Image exists? */
    	if ( ! is_file( IPS_PUBLIC_PATH . 'style_status/' . $creator . '.png' ) )
    	{
    		$creator = 'ipb';
    	}
    	
    	return $this->settings['public_dir'] . 'style_status/' . $creator . '.png';
    }
    
    /**
     * Fetch creator text
     *
     * @return	string
     */
    public function creatorText( $status )
    {
    	/* Got a creator? */
    	if ( ! $status['status_creator'] )
    	{
    		$status['status_creator'] = 'ipb';
    	}
    	
    	if ( ! isset( $this->lang->words['status_creator_' . $status['status_creator'] ] ) )
    	{
    		$status['status_creator'] = 'ipb';
    	}
    	
    	return $this->lang->words['status_creator_' . $status['status_creator'] ];
    }
    
	/**
	 * Perm Check: Can post a status update
	 *
	 * @return	bool
	 */
	public function canCreate( $author=null, $owner=null )
	{
		$author = ( $author === null ) ? $this->getAuthor() : $author;
		
		if ( $owner !== null AND $author['member_id'] != $owner['member_id'] )
		{
			/* Not a new status by user */
			return $this->canComment( $author, $owner );
		}
		
		if ( ! $author['member_id'] )
		{
			return FALSE;
		}
		
		if ( ! $this->isEnabled() )
		{
			return FALSE;
		}
		
		if ( ! $author['g_mem_info'] OR $author['gbw_no_status_update'] OR $author['bw_no_status_update'] )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Perm Check: Can post a comment
	 *
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status owner information uses $this->_internalData['StatusOwner'] if none]
	 * @return	bool
	 */
	public function canComment( $author=null, $owner=null, $status=null )
	{
		$author = ( $author === null ) ? $this->getAuthor() : $author;
		$owner  = ( $owner === null )  ? $this->_internalData['StatusOwner'] : $owner;
		
		/* Are we allowed to comment? */
		if ( ! $author['g_reply_other_topics'] )
		{
			return false;
		}
		
		if ( $author['restrict_post'] )
		{
			if ( $author['restrict_post'] == 1 )
			{
				return false;
			}
			
			$post_arr = IPSMember::processBanEntry( $author['restrict_post'] );
			
			if ( time() >= $post_arr['date_end'] )
			{
				/* Update this member's profile */
				IPSMember::save( $author['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
			}
			else
			{
				return false;
			}
		}
		
		$_you_are_being_ignored = explode( ",", $owner['ignored_users'] );
		
		if ( is_array( $_you_are_being_ignored ) and count( $_you_are_being_ignored ) )
		{
			if ( in_array( $author['member_id'], $_you_are_being_ignored ) )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Perm Check: Can post a status update
	 *
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status owner information uses $this->_internalData['StatusOwner'] if none]
	 * @param	array	[Array of status data information uses $this->_internalData['StatusData'] if none]
	 * @return	bool
	 */
	public function canReply( $author=null, $owner=null, $status=null )
	{
		$author = ( $author === null ) ? $this->getAuthor() : $author;
		$owner  = ( $owner === null )  ? $this->_internalData['StatusOwner'] : $owner;
		$status = ( $status === null ) ? $this->_internalData['StatusData']  : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
		
		if ( ! $author['member_id'] )
		{
			return FALSE;
		}
		
		if ( ! $this->isEnabled() )
		{
			return FALSE;
		}
		
		if ( ! $author['g_mem_info'] OR $author['gbw_no_status_update'] OR $author['bw_no_status_update'] )
		{
			return FALSE;
		}
		
		if ( ! $this->_okToPost( $author, $owner ) )
		{
			return FALSE;
		}
		
		if ( $status['status_replies'] >= $this->settings['su_max_replies'] )
		{
			return FALSE;
		}
		
		if ( $status['status_is_locked'] )
		{
			if ( ! $author['g_is_supmod'] )
			{
				if ( $status['status_is_locked'] == 1 AND ( $author['member_id'] == $status['status_member_id'] ) )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Perm Check: Can delete a status update
	 *
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @return	bool
	 */
	public function canDeleteStatus( $author=null, $status=null )
	{
		$author  = ( $author === null ) ? $this->getAuthor() : $author;
		$status  = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
		
		if ( ! $author['member_id'] OR ! $status['status_id'] )
		{
			return FALSE;
		}
		
		if ( ! $this->isEnabled() )
		{
			return FALSE;
		}
		
		/* is left by someone else */
		if ( ( $status['status_member_id'] != $status['status_author_id'] ) && $status['status_author_id'] == $author['member_id'] )
		{
			return true;
		}
		
		if ( ! $author['g_is_supmod'] )
		{
			if ( $author['gbw_no_status_update'] OR $author['bw_no_status_update'] )
			{
				return FALSE;
			}
			
			if ( $author['member_id'] != $status['status_member_id'] )
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Perm Check: Can delete a reply
	 *
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status reply information OR reply ID OR uses $this->_internalData['ReplyData'] if none]
	 * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @return	bool
	 */
	public function canDeleteReply( $author=null, $reply=null, $status=null )
	{
		$author  = ( $author === null ) ? $this->getAuthor() : $author;
		$reply   = ( $reply  === null ) ? $this->_internalData['ReplyData']  : ( ( is_array( $reply ) ) ? $reply : $this->_loadReply( $reply ) );
		$status  = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
		
		if ( ! $author['member_id'] OR ! $status['status_id'] )
		{
			return FALSE;
		}
		
		if ( ! $this->isEnabled() )
		{
			return FALSE;
		}
		
		if ( ! $author['g_is_supmod'] )
		{
			if ( $author['gbw_no_status_update'] OR $author['bw_no_status_update'] )
			{
				return FALSE;
			}
			
			if ( $author['member_id'] == $status['status_member_id'] )
			{
				return TRUE;
			}
			
			if ( $author['member_id'] == $reply['reply_member_id'] )
			{
				return TRUE;
			}
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Perm Check: Can lock a status
	 * status_is_locked: 1 means user lock, so owner can unlock, 2 means admin lock, so cannot unlock.
	 *
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @return	bool
	 */
	public function canLockStatus( $author=null, $status=null )
	{
		$author  = ( $author === null ) ? $this->getAuthor() : $author;
		$status  = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
		
		if ( ! $author['member_id'] OR ! $status['status_id'] )
		{
			return FALSE;
		}
		
		if ( ! $this->isEnabled() )
		{
			return FALSE;
		}
		
		if ( ! $author['g_is_supmod'] )
		{
			if ( $author['member_id'] == $status['status_member_id'] )
			{
				return TRUE;
			}
			
			return FALSE;
		}
		else
		{
			return true;
		}
		
		/* is left by someone else */
		if ( ( $status['status_member_id'] != $status['status_author_id'] ) && $status['status_member_id'] != $author['member_id'] )
		{
			return false;
		}
		
		return TRUE;
	}
	
	/**
	 * Perm Check: Can lock a status
	 * status_is_locked: 1 means user lock, so owner can unlock, 2 means admin lock, so cannot unlock.
	 *
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @return	bool
	 */
	public function canUnlockStatus( $author=null, $status=null )
	{
		$author  = ( $author === null ) ? $this->getAuthor() : $author;
		$status  = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
		
		if ( ! $author['member_id'] OR ! $status['status_id'] OR ! $status['status_is_locked'] )
		{
			return FALSE;
		}
		
		if ( ! $this->isEnabled() )
		{
			return FALSE;
		}
		
		if ( ! $author['g_is_supmod'] )
		{
			if ( $author['member_id'] == $status['status_member_id'] AND ( $status['status_is_locked'] == 1 ) )
			{
				return TRUE;
			}
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Record a custom action
	 *
	 * @param	string	Type (new or reply)
	 * @param	array   text, url, app
	 * @param	array	[Array of author data, uses getAuthor if none]
	 */
	public function recordAction( $type, $custom=array(), $author=null )
	{
		$author  = ( $author === null ) ? $this->getAuthor() : $author;
		
		if ( $author['member_id'] AND $custom['text'] )
		{ 
			$this->DB->insert( 'member_status_actions', array( 'action_member_id'    => $author['member_id'],
															   'action_date'	     => time(),
															   'action_custom'		 => 1,
															   'action_custom_text'  => $custom['text'],
															   'action_custom_url'   => $custom['url'],
															   'action_app'			 => $custom['app'],
															   'action_key'          => 'custom' ) );
		}
	}
	
	/**
	 * Record an action
	 *
	 * @param	string	Type (new or reply)
	 * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status information OR status ID OR uses $this->_internalData['StatusData'] if none]
	 * @param	array	[Array of status reply information OR reply ID OR uses $this->_internalData['ReplyData'] if none]
	 */
	protected function _recordAction( $type, $author=null, $status=null, $reply=null )
	{
		$author  = ( $author === null ) ? $this->getAuthor() : $author;
		$status  = ( $status === null ) ? $this->_internalData['StatusData'] : ( is_array( $status ) ) ? $status : ( $status ? $this->_loadStatus( $status ) : array() );
		$reply   = ( $reply  === null ) ? $this->_internalData['ReplyData']  : ( ( is_array( $reply ) ) ? $reply : $this->_loadReply( $reply ) );
		
		if ( $author['member_id'] AND $status['status_id'] )
		{ 
			$this->DB->insert( 'member_status_actions', array( 'action_status_id'    => $status['status_id'],
															   'action_reply_id'     => intval( $reply['reply_id'] ),
															   'action_member_id'    => $author['member_id'],
															   'action_status_owner' => $status['status_member_id'],
															   'action_date'	     => time(),
															   'action_custom'		 => 0,
															   'action_key'          => ( $type == 'new' ) ? 'new' : 'reply' ) );
		}
	}
	
	/**
	 * Load a status from the DB
	 *
	 * @param	int			Status ID
	 * @return	array		Array OR FALSE
	 */
	protected function _loadStatus( $statusId )
	{
		$status = array();
		$member = array();
		
		$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_updates' => 's' ),
							     'where'    => 's.status_id=' . intval( $statusId ),
							     'add_join' => array(array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.status_member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$this->DB->execute();
		
		$row = $this->DB->fetch();
		
		if( ! $row )
		{
			return FALSE;
		}
		
		foreach( $row as $k => $v )
		{
			if ( substr( $k, 0, 7 ) != 'status_' )
			{
				$member[ $k ] = $v;
			}
			
			$status[ $k ] = $v;
		}
		
		$this->_internalData['StatusData']  = $status;
		$this->_internalData['StatusOwner'] = $member;
		
		return ( isset( $status['status_id'] ) ) ? $status : FALSE;
	}
	
	/**
	 * Load a reply from the DB
	 *
	 * @param	int			Status ID
	 * @return	array		Array OR FALSE
	 */
	protected function _loadReply( $replyId )
	{
		$reply  = array();
		$member = array();
		
		$this->DB->build( array( 'select'   => 's.*',
							     'from'	    => array( 'member_status_replies' => 's' ),
							     'where'    => 's.reply_id=' . intval( $replyId ),
							     'add_join' => array(array(  'select'	=> 'm.*',
															 'from'	    => array( 'members' => 'm' ),
															 'where'    => 'm.member_id=s.reply_member_id',
															 'type'	    => 'left' ),
													 array(  'select'   => 'pp.*',
															 'from'	    => array( 'profile_portal' => 'pp' ),
															 'where'    => 'pp.pp_member_id=m.member_id',
															 'type'	    => 'left' ) ) ) );
															 
		$this->DB->execute();
		
		$row = $this->DB->fetch();
		
		if( ! $row )
		{
				return FALSE;
		}
		
		foreach( $row as $k => $v )
		{
			if ( substr( $k, 0, 6 ) != 'reply_' )
			{
				$member[ $k ] = $v;
			}
			
			$reply[ $k ] = $v;
		}
		
		$this->_internalData['ReplyData']  = $reply;
		$this->_internalData['ReplyOwner'] = $member;
		
		return ( isset( $reply['reply_id'] ) ) ? $reply : FALSE;
	}
	
	/**
	 * Clean up content suitable for posting
	 *
	 * @param	string		In
	 * @return	string		Out
	 */
	protected function _cleanContent( $content )
	{
		/* Just cut off after X chars. */
		$content = IPSText::truncate( IPSText::getTextClass('bbcode')->stripBadWords( $content ), $this->settings['su_max_chars'] );
		
		return $content;
	}
	
	/**
	 * Auto parse some stuff
	 *
	 * Eventually could abstract it out but for now, this will do. Mkay.
	 */
	protected function _parseContent( $content, $creator='' )
	{
		/* Auto parse tags */
		if ( $this->settings['su_parse_url'] )
		{
			$content = preg_replace_callback( '#(^|\s|\(|>|\](?<!\[url\]))((?:http|https|news|ftp)://\w+[^\),\s\<\[]+)#is', array( $this, '_autoParseUrls' ), $content );
		}
		
		/* Twittah? */
		if ( $creator == 'twitter' )
		{
			if ( $this->settings['tc_parse_tags'] )
			{
				$content = preg_replace_callback( '#(^|\s)(\\#([a-z_A-Z0-9:_-]+))#', array( $this, '_autoParseTags' ), $content );
			}
			
			if ( $this->settings['tc_parse_names'] )
			{
				$content = preg_replace_callback('#(^|\s)@([a-z_A-Z0-9]+)#', array( $this, '_autoParseNames' ), $content );
			}
		}
                
                
                
                //echo ":D";
                if ( $this->settings['tc_parse_names'] )
                {
                		$content = utf8_encode($content);
                		//var_dump(html_entity_decode(utf8_encode($content)));exit;
                        $content = preg_replace_callback('#(^|\s)@([0-9]+)-@([a-z_A-Z0-9 áéíóúÁÉÍÓÚñÑ\.]+)@#', array( $this, '_autoParseNamesInternal' ), $content );
                }
		
		return $content;
	}
	
	/**
	 * Callback to auto-parse @names
	 * 
	 * @param	array		Matches from the regular expression
	 * @return	string		Converted text
	 */
	protected function _autoParseNames( $matches )
	{
		return $this->_autoParseUrls( array( '', $matches[1], 'http://www.twitter.com/' . urlencode( $matches[2] ), $matches[2] ) );
	}
        
	/**
	 * Callback to auto-parse @names
	 * 
	 * @param	array		Matches from the regular expression
	 * @return	string		Converted text
	 */
	protected function _autoParseNamesInternal( $matches )
	{
            return $matches[1] . '<a href="' . $this->registry->output->buildSEOUrl( 'showuser=' . $matches[2], 'publicNoSession', $matches[3], 'showuser' ) . '" class="su_links">' . $matches[3] . '</a>';
	}
        
	
	/**
	 * Callback to auto-parse #tags
	 * 
	 * @param	array		Matches from the regular expression
	 * @return	string		Converted text
	 */
	protected function _autoParseTags( $matches )
	{
		return $this->_autoParseUrls( array( '', $matches[1], 'http://search.twitter.com/search?q=%23' . urlencode( $matches[3] ), $matches[2] ) );
	}
	
	/**
	 * Callback to auto-parse urls
	 * I totally stole this from Brandon's code but do NOT tell him.
	 * 
	 * @param	array		Matches from the regular expression
	 * @return	string		Converted text
	 */
	protected function _autoParseUrls( $matches )
	{
		/* We use this function in other areas also */
		$matches[3] = ( $matches[3] ) ? $matches[3] : $matches[2];
		
		//-----------------------------------------
		// Adding rel='nofollow'?
		//-----------------------------------------
		
		$rels	= array();
		$rel	= '';

		if( $this->settings['posts_add_nofollow'] )
		{
			$rels[]	= "nofollow";
		}
		
		if( $this->settings['links_external'] )
		{
			$rels[]	= "external";
		}
		
		if( count($rels) )
		{
			$rel = " rel='" . implode( ' ', $rels ) . "'";
		}
		
		return $matches[1] . '<a href="' . $matches[2] . '"' . $rel . ' class="su_links">' . $matches[3] . '</a>';
	}
	
	/**
     * Check for mod posts or restricted posts or ignored
     *
     * @param	array	[Array of author data, uses getAuthor if none]
	 * @param	array	[Array of status owner information uses $this->_internalData['StatusOwner'] if none]
     * @return	bool
     */
    protected function _okToPost( $author=null, $owner=null )
    {
    	$author = ( $author === null ) ? $this->getAuthor() : $author;
		$owner  = ( $owner === null )  ? $this->_internalData['StatusOwner'] : $owner;
		
    	/* Restricted Posting */
    	if ( $author['restrict_post'] )
		{
			if ( $author['restrict_post'] == 1 )
			{
				return FALSE;
			}
			
			$post_arr = IPSMember::processBanEntry( $author['restrict_post'] );
			
			if( time() >= $post_arr['date_end'] )
			{
				/* Update this member's profile */
				IPSMember::save( $author['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
			}
			else
			{
				return FALSE;
			}
		}

		/* Moderated Posting */ 
		if ( $author['mod_posts'] )
		{
			if ( $author['mod_posts'] == 1 )
			{
				return FALSE;
			}
			else
			{
				$mod_arr = IPSMember::processBanEntry( $author['mod_posts'] );
				
				if( time() >= $mod_arr['date_end'] )
				{
					/* Update this member's profile */
					IPSMember::save( $author['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
				}
				else
				{
					return FALSE;
				}
			}
		}
		
		/* Member is ignoring you! */
		$_you_are_being_ignored = explode( ",", $owner['ignored_users'] );
		
		if ( is_array( $_you_are_being_ignored ) and count( $_you_are_being_ignored ) )
		{
			if ( in_array( $author['member_id'], $_you_are_being_ignored ) )
			{
				return FALSE;
			}
		}
		
		return TRUE;
    }
    
	/**
	 * Various getters and setters
	 *
	 * @param	string
	 * @param	mixed		void, or an array of arguments
	 * @return	mixed		Data
	 */
	public function __call( $method, $arguments )
	{
		$firstBit = substr( $method, 0, 3 );
		$theRest  = substr( $method, 3 );
	
		if ( in_array( $theRest, $this->_allowedInternalData ) )
		{
			if ( $firstBit == 'set' )
			{
				if ( $theRest == 'Author' OR $theRest == 'Friend' )
				{
					if ( is_array( $arguments[0] ) )
					{
						/* Ensure we have group data */
						if ( ! isset( $arguments[0]['g_id'] ) AND $arguments[0]['member_id'] )
						{
							$this->_internalData[ $theRest ] = IPSMember::load( intval( $arguments[0]['member_id'] ), 'all' );
						}
						else
						{
							$this->_internalData[ $theRest ] = $arguments[0];
						}
					}
					else
					{
						if( $arguments[0] )
						{
							/* Set up moderator stuff, too */
							$this->_internalData[ $theRest ] = IPSMember::load( intval( $arguments[0] ), 'all' );
						}
						else
						{
							$this->_internalData[ $theRest ] = IPSMember::setUpGuest();
						}
					}
				}
				else if ( $theRest == 'StatusData' )
				{
					if ( is_array( $arguments[0] ) )
					{
						$this->_internalData[ $theRest ] = $arguments[0];
					}
					else
					{
						$this->_internalData[ $theRest ] = $this->_loadStatus( intval( $arguments[0] ) );
					}
				}
				else if ( $theRest == 'ReplyData' )
				{
					if ( is_array( $arguments[0] ) )
					{
						$this->_internalData[ $theRest ] = $arguments[0];
					}
					else
					{
						$this->_internalData[ $theRest ] = $this->_loadReply( intval( $arguments[0] ) );
					}
				}
				else
				{
					$this->_internalData[ $theRest ] = $arguments[0];
					return TRUE;
				}
				
				return $this->_internalData[ $theRest ];
			}
			else
			{
				if ( ( $theRest == 'Author' OR $theRest == 'Friend' OR $theRest == 'StatusData' OR $theRest == 'ReplyData' ) AND isset( $arguments[0] ) )
				{
					return isset( $this->_internalData[ $theRest ][ $arguments[0] ] ) ? $this->_internalData[ $theRest ][ $arguments[0] ] : '';
				}
				else
				{
					return isset( $this->_internalData[ $theRest ] ) ? $this->_internalData[ $theRest ] : '';
				}
			}
		}
	}
}