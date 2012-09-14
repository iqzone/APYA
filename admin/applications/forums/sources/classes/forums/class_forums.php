<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forums Class
 * Last Updated: $Date: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		26th January 2004
 * @version		$Rev: 10798 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_forums
{
	/**
	 * Cache of visible forums
	 *
	 * @var		array
	 */
	public $forum_cache		= array();
	
	/**
	 * Cache of all forums, regardless of perms
	 *
	 * @var		array
	 */
	public $allForums		= array();
	
	/**
	 * Cache of visible forums mapped by ID
	 *
	 * @var		array
	 */
	public $forum_by_id		= array();
	
	/**
	 * Depth guide
	 *
	 * @var		string
	 */
	public $depth_guide		= "--";
	
	/**
	 * Strip invisible forums?
	 *
	 * @var		bool
	 */
	public $strip_invisible	= false;
	
	/**
	 * Cache of moderators
	 *
	 * @var		array
	 */
	public $mod_cache		= array();
	
	/**
	 * Mod cache loaded
	 *
	 * @var		bool
	 */
	public $mod_cache_got	= false;
	
	/**
	 * Is a read topic only forum
	 *
	 * @var		bool
	 */
	public $read_topic_only	= false;

	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
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
	/**#@-*/
	
	/**
	 * Posts per day status string
	 *
	 * @var	   string
	 */
	public $ppdStatusMessage = '';
	
	/**
	 * Ability to set a different member for permission checks, etc
	 * @var array
	 */
	protected $_memberData     = array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param	object	ipsRegistry  $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Set default memberData */
		$this->setMemberData( $this->memberData );
	}
	
	/**
	 * Grab all forums and stuff into array
	 *
	 * @return	@e void
	 */
	public function forumsInit()
	{
		/* Query Forum Data */
		$forum_list = $this->getForumList();
		
		$hide_parents = ',';

		$this->forum_cache = array();
		$this->forum_by_id = array();

		foreach( $forum_list as $f )
		{
			if( $this->strip_invisible )
			{
				/* Don't show any children of hidden parents */
				if( strstr( $hide_parents, ','. $f['parent_id'] .',' ) )
				{
					$hide_parents .= $f['id'].',';
					continue;
				}
				
				/* Don't show forums that we do not have view permissions for */
				if( $f['perm_view'] != '*' )
				{ 
					if ( $this->registry->permissions->check( 'view', $f ) != TRUE )
					{
						$hide_parents .= $f['id'].',';
						continue;
					}
				}
				
				/* Don't show forums that we can not see based on the minimum posts to view setting */
				if( !empty( $f['min_posts_view'] ) AND ( $this->_memberData['posts'] < $f['min_posts_view'] ) AND !$this->_memberData['g_is_supmod'] )
				{
					continue;
				}		
			}
			
			/* We lump in archived counts here */
			//$f['posts']  += intval( $f['archived_posts'] );
			//$f['topics'] += intval( $f['archived_topics'] );
			
			/* Set the aprent id for root categories */
			if( $f['parent_id'] < 1 )
			{
				$f['parent_id'] = 'root';
			}
			
			$f['fid'] = $f['id'];
			
			/* Store the forum arrays */
			$this->forum_cache[ $f['parent_id'] ][ $f['id'] ] = $f;
			$this->forum_by_id[ $f['id'] ] = $this->forum_cache[ $f['parent_id'] ][ $f['id'] ];
		}
	}
	
	/**
	 * Returns a forum based on an ID
	 * Added in 3.2 to abstract out some forum functionality
	 *
	 * @return	array
	 * @note	This function DOES NOT perform any check on the forum data before returning it, you should check yourself if the member can view the forum
	 */
	public function getForumById( $id )
	{
		/* Init vars */
		$id = intval( $id );
		
		/* Got a forum already? */
		if ( ! isset($this->allForums[ $id ]) )
		{
			$forum = $this->DB->buildAndFetch( array( 'select'   => 'f.*',
													  'from'     => array( 'forums' => 'f' ),
													  'where'    => 'f.id=' . intval( $id ),
													  'add_join' => array( array( 'select' => 'p.*',
																				  'from'   => array( 'permission_index' => 'p' ),
																				  'where'  => "p.perm_type_id=f.id AND p.app='forums' AND p.perm_type='forum'",
																				  'type'   => 'left' ) ) ) );
			
			/* Got a result? */
			if ( ! empty( $forum['id'] ) )
			{
				/* Unpack bitwise fields */
				$_tmp = IPSBWOptions::thaw( $forum['forums_bitoptions'], 'forums', 'forums' );
	
				if ( count( $_tmp ) )
				{
					foreach( $_tmp as $k => $v )
					{
						$forum[ $k ] = $v;
					}
				}
				
				$forum = array_merge( $forum, $this->registry->permissions->parse( $forum ) );
			}
			else
			{
				$forum = array(); // Prevents trying to load the forum again if it doesn't exist
			}
			
			$this->allForums[ $id ]   = $forum;
		}
		
		return $this->allForums[ $id ];
	}
	
	/**
	 * Can start topics in this forum?
	 * @param int $forumId
	 */
	public function canStartTopic( $forumId )
	{
		$forumData = $this->getForumById( $forumId );
		
		/* Posting Allowed? */
		$canPost = true;
		
		if( ! $this->registry->permissions->check( 'start', $forumData ) )
		{
			$canPost = false;
		}

		if( ! $forumData['sub_can_post'] )
		{
			$canPost = false;
		}

		if( $forumData['min_posts_post'] && $forumData['min_posts_post'] > $this->_memberData['posts'] && !$this->_memberData['g_is_supmod'] )
		{
			$canPost = false;
		}
		
		if( ! $this->_memberData['g_post_new_topics'] )
		{
			$canPost = false;
		}
		
		return $canPost;
	}
	
	/**
	 * Returns a list of all forums
	 *
	 * @return	array
	 */
	public function getForumList()
	{
		/* Get the forums */			
		$this->DB->build( array( 'select'   => 'f.*',
								 'from'     => array( 'forums' => 'f' ),
								 'add_join' => array( array( 'select' => 'p.*',
															 'from'   => array( 'permission_index' => 'p' ),
															 'where'  => "p.perm_type='forum' AND p.app='forums' AND p.perm_type_id=f.id",
															 'type'   => 'left' ),
													  $this->registry->classItemMarking->getSqlJoin( array( 'item_app_key_1' => 'f.id' ) ) ) ) );
						
		$q = $this->DB->execute();
		
		/* Loop through and build an array of forums */
		$forums_list	= array();
		$update_seo		= array();
		$tempForums     = array();
		
		while( $f = $this->DB->fetch( $q ) )
		{
			$tempForums[ $f['parent_id'] . '.' . $f['position'] . '.' . $f['id'] ] = $f;
		}

		/* Sort in PHP */
		$tempForums = IPSLib::knatsort( $tempForums );
		
		foreach( $tempForums as $posData => $f )
		{
			$fr = array();
			
			/* Add back into topic markers */
			$f = $this->registry->classItemMarking->setFromSqlJoin( $f, 'forums' );
			
			/**
			 * This is here in case the SEO name isn't stored for some reason.
			 * We'll parse it and then update the forums table - should only happen once
			 */
			if ( ! $f['name_seo'] )
			{
				/* SEO name */
				$f['name_seo'] = IPSText::makeSeoTitle( $f['name'] );
				
				$update_seo[ $f['id'] ]	= $f['name_seo'];
			}
			
			/* Reformat the array for a category */
			if ( $f['parent_id'] == -1 )
			{
				$fr['id']				    = $f['id'];
				$fr['sub_can_post']         = $f['sub_can_post'];
				$fr['name'] 		        = $f['name'];
				$fr['name_seo'] 			= $f['name_seo'];
				$fr['parent_id']	        = $f['parent_id'];
				$fr['skin_id']		        = $f['skin_id'];
				$fr['permission_showtopic'] = $f['permission_showtopic'];
				$fr['forums_bitoptions']    = $f['forums_bitoptions'];
				$fr['hide_last_info']		= 0;
				$fr['can_view_others']		= 0;
				$fr['password']				= $f['password'];
				
				/* Permission index columns */
				$fr['app']					= $f['app'];
				$fr['perm_id']				= $f['perm_id'];
				$fr['perm_type']			= $f['perm_type'];
				$fr['perm_type_id']			= $f['perm_type_id'];
				$fr['perm_view']			= $f['perm_view'];
				$fr['perm_2']				= $f['perm_2'];
				$fr['perm_3']				= $f['perm_3'];
				$fr['perm_4']				= $f['perm_4'];
				$fr['perm_5']				= $f['perm_5'];
				$fr['perm_6']				= $f['perm_6'];
				$fr['perm_7']				= $f['perm_7'];
				$fr['owner_only']			= $f['owner_only'];
				$fr['friend_only']			= $f['friend_only'];
				$fr['authorized_users']		= $f['authorized_users'];
			}
			else
			{
				$fr = $f;

				$fr['description'] = isset( $f['description'] ) ? $f['description'] : '';
			}
			
			$fr = array_merge( $fr, $this->registry->permissions->parse( $f ) );

			/* Unpack bitwise fields */
			$_tmp = IPSBWOptions::thaw( $fr['forums_bitoptions'], 'forums', 'forums' );

			if ( count( $_tmp ) )
			{
				foreach( $_tmp as $k => $v )
				{
					/* Trigger notice if we have DB field */
					if ( isset( $fr[ $k ] ) )
					{
						trigger_error( "Thawing bitwise options for FORUMS: Bitwise field '$k' has overwritten DB field '$k'", E_USER_WARNING );
					}

					$fr[ $k ] = $v;
				}
			}
			
			/* Add... */
			$forums_list[ $fr['id'] ] = $fr;
		}

		$this->allForums	= $forums_list;
		
		/**
		 * Update forums table if SEO name wasn't cached yet
		 */
		if( count($update_seo) )
		{
			foreach( $update_seo as $k => $v )
			{
				$this->DB->update( 'forums', array( 'name_seo' => $v ), 'id=' . $k );
			}
		}
		
		return $forums_list;
	}
	
	/**
	 * @return the $_memberData
	 */
	public function getMemberData( $k='' )
	{
		return ( ! empty( $k ) ) ? $this->_memberData[ $k ] : $this->_memberData;
	}

	/**
	 * @param	string	key
	 * @param	string	value
	 */
	public function setMemberData( $k, $v='' )
	{
		if ( is_integer( $k ) )
		{
			$this->_memberData = empty( $k ) ? IPSMember::setUpGuest() : IPSMember::load( $k );
		}
		else if ( is_string($k) && $k == intval($k) )
		{
			$this->_memberData = empty( $k ) ? IPSMember::setUpGuest() : IPSMember::load( $k );
		}
		else if ( is_array( $k ) )
		{
			$this->_memberData = $k;
		}
		else if ( ! empty( $k ) )
		{
			$this->_memberData[ $k ] = $v;
		}
		
		$this->_memberData = IPSMember::setUpModerator( $this->_memberData );
	}
	
	/**
	 * Check to see if we have any group restrictions on whether we can post or not
	 * Naturally, this would be better off in the classPost class but this means we'd have
	 * to load it in topic view for the fast reply box.
	 *
	 * Optionally populates a status message in ppdStatusMessage
	 *
	 * @param	array 		[Member data (assumes $this->_memberData if nothing passed )]
	 * @param	boolean		Populate ppdStatusMessage?
	 * @return	boolean		TRUE = ok to post, FALSE cannot post
	 */
	public function checkGroupPostPerDay( $memberData = array(), $setStatus=FALSE )
	{
		$memberData = ( is_array( $memberData ) and count( $memberData ) ) ? $memberData : $this->_memberData;
		$group      = $this->caches['group_cache'][ $memberData['member_group_id'] ];
		$_data      = explode( ',', $memberData['members_day_posts'] );
		$_count     = intval( $_data[0] );
		$_time      = intval( $_data[1] );
		
		/* Ok? */
		if ( is_array( $group ) AND is_array( $memberData ) )
		{
			/* Check posts per day */
			if ( $group['g_ppd_limit'] )
			{
				/* Check to see if we're past our 24hrs */
				if ( ( time() - 86400 ) >= $_time AND ( $_time ) )
				{
					$count  = $this->fetchMemberPpdCount( $memberData['member_id'], time() - 86400 );
					$_count = $count['count'];
					$_time  = $count['min'];
					
					/* Update member immediately */
					IPSMember::save( $memberData['member_id'], array( 'core' => array( 'members_day_posts' => $_count . ',' . $_time  ) ) );
				}
				
				/* Grab the correct lang file */
				if ( $setStatus )
				{
					if ( ! isset( $this->lang->words['status_ppd_posts'] ) )
					{
						$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
					}
				}
				
				/* Do we only limit for x posts/days? */
				if ( $group['g_ppd_unit'] )
				{
					if ( $group['gbw_ppd_unit_type'] )
					{
						/* Days.. .*/
						if ( $memberData['joined'] > ( time() - ( 86400 * $group['g_ppd_unit'] ) ) )
						{
							if ( $_count >= $group['g_ppd_limit'] )
							{
								return FALSE;
							}
							else if ( $setStatus )
							{
								if ( $_time )
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_joined'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $_time + 86400, 'long' ), $this->lang->getDate( $memberData['joined'] + ( 86400 * $group['g_ppd_unit'] ), 'long' ) );
								}
								else
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_joined_no_time'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $memberData['joined'] + ( 86400 * $group['g_ppd_unit'] ), 'long' ) );
								}
							}
						}
					}
					else
					{
						/* Posts */
						if ( $memberData['posts'] < $group['g_ppd_unit'] )
						{
							if ( $_count >= $group['g_ppd_limit'] ) 
							{
								return FALSE;
							}
							else
							{ 
								if ( $_time )
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $_time + 86400, 'long' ), ( $group['g_ppd_unit'] - $memberData['posts'] ) );
								}
								else
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_no_time'], $group['g_ppd_limit'] - $_count, ( $group['g_ppd_unit'] - $memberData['posts'] ) );
								}
							}
						}
					}
				}
				else
				{
					/* No PPD limit, but still checking PPD */
					if ( $_count >= $group['g_ppd_limit'] )
					{
						return FALSE;
					}
					else
					{
						if ( $_time )
						{
							$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_nolimit'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $_time + 86400, 'long' ) );
						}
						else
						{
							$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_nolimit_no_time'], $group['g_ppd_limit'] - $_count );
						}
					}
				}
			}
			
			/* Still here? */
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Fetch member's PPD details
	 *
	 * @param	int		member id
	 * @param	int		timestamp 'from'
	 * @return	array	min, count
	 */
	public function fetchMemberPpdCount( $memberId, $time )
	{
		/* Recount today's posts BOTH approved and unapproved */
		$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(post_date) as min',
										  		  'from'   => 'posts',
										  		  'where'  => 'author_id=' . intval($memberId) . ' AND post_date > ' . $time . ' AND ' . $this->fetchPostHiddenQuery( array( 'visible', 'hidden' ) ) ) );
										
		return $count;
	}
	
	/**
	 * Fetch forum data
	 *
	 * @param	int			Forum ID
	 * @return	array 		Forum Data
	 */
	public function forumsFetchData( $id )
	{
		return is_array( $this->forum_by_id[ $id ] ) ? $this->forum_by_id[ $id ] : array( 'id' => 0 );
	}
	
	/**
	 * Grab all mods innit
	 *
	 * @return	@e void
	 */
	public function forumsGetModeratorCache()
	{
		$this->can_see_queued = array();
		
		if ( ! is_array( $this->caches['moderators'] ) )
		{
			$this->cache->rebuildCache( 'moderators', 'forums' );
		}
		
		/* Set Up */
		if ( count( $this->caches['moderators'] ) )
		{
			foreach( $this->caches['moderators'] as $r )
			{
				$forumIds = explode( ',', IPSText::cleanPermString( $r['forum_id'] ) );
				
				foreach( $forumIds as $forumId )
				{
					$this->mod_cache[ $forumId ][ $r['mid'] ] = array( 
																		'name'    => $r['members_display_name'],
																		'seoname' => $r['members_seo_name'],
																		'memid'   => $r['member_id'],
																		'id'      => $r['mid'],
																		'isg'     => $r['is_group'],
																		'gname'   => $r['group_name'],
																		'gid'     => $r['group_id'],
																	);	
				}
			}
		}
		
		$this->mod_cache_got = 1;
	}
	
	/**
	 * Get moderator status from DB
	 *
	 * @return	@e array
	 */
	public function getModerator()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$permissions	= array();
		
		//-----------------------------------------
		// If we are a mod, get our permissions from DB
		//-----------------------------------------
		
		if( $this->_memberData['is_mod'] )
		{
			$other_mgroups	= array();
			$_other_mgroups	= IPSText::cleanPermString( $this->_memberData['mgroup_others'] );
			
			if( $_other_mgroups )
			{
				$other_mgroups	= explode( ",", $_other_mgroups );
			}
			
			$other_mgroups[]	= $this->_memberData['member_group_id'];

			$this->DB->build( array( 
									'select' => '*',
									'from'   => 'moderators',
									'where'  => "(member_id='" . $this->_memberData['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" 
							)	);
										  
			$this->DB->execute();
			
			//-----------------------------------------
			// We do it this way to allow member-specific mod privileges
			// to override group-inherited mod privileges
			//-----------------------------------------
			
			while ( $moderator = $this->DB->fetch() )
			{
				if ( $moderator['member_id'] == $this->_memberData['member_id'] )
				{
					return $moderator;
				}
				else
				{
					$permissions	= $moderator;
				}
			}
		}
		
		return $permissions;
	}
	
	/**
	 * Get Moderators
	 *
	 * @param	integer	$forum_id
	 * @return	string
	 */
	public function forumsGetModerators( $forum_id="" )
	{
		if ( ! $this->mod_cache_got )
		{
			$this->forumsGetModeratorCache();
		}
		
		$mod_string = array();
		
		if ( $forum_id == "" )
		{
			return $mod_string;
		}
		
		if (isset($this->mod_cache[ $forum_id ] ) )
		{
			if (is_array($this->mod_cache[ $forum_id ]) )
			{
				foreach ($this->mod_cache[ $forum_id ] as $moderator)
				{
					if ($moderator['isg'] == 1)
					{
						$mod_string[] = array( $this->registry->getClass("output")->buildSEOUrl( "app=members&amp;module=list&amp;max_results=30&amp;filter={$moderator['gid']}&amp;sort_order=asc&amp;sort_key=members_display_name&amp;st=0&amp;b=1", "public", "false" ), $moderator['gname'], 0 );
					}
					else if( $moderator['memid'] )
					{
						if ( ! $moderator['name'] )
						{
							continue;
						}
						$mod_string[] = array( $this->registry->getClass("output")->buildSEOUrl( "showuser={$moderator['memid']}", "public", $moderator['seoname'], 'showuser' ), $moderator['name'], $moderator['memid'] );
					}
				}
			}
			else
			{
				if ($this->mods[$forum_id]['isg'] == 1)
				{
					$mod_string[] = array( $this->registry->getClass("output")->buildSEOUrl( "app=members&amp;max_results=30&amp;filter={$this->mods[$forum_id]['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0&amp;b=1", "public", "false" ), $this->mods[$forum_id]['gname'], 0 );
				}
				else if( $this->mods[$forum_id]['memid'] )
				{
					$mod_string[] = array( $this->registry->getClass("output")->buildSEOUrl( "showuser={$this->mods[$forum_id]['memid']}", "public", $this->mods[$forum_id]['seoname'], 'showuser' ), $this->mods[$forum_id]['name_seo'], $this->mods[$forum_id]['memid'] );
				}
			}
		}
		
		return $mod_string;
		
	}

	/**
	 * Check Forum Access
	 *
	 * @param	integer	$fid			Forum id
	 * @param	bool	$prompt_login	Prompt login/show error
	 * @param	string	$in				[topic|forum]
	 * @param	array 	$topic			Topic data
	 * @param	bool	$return			Return instead of displaying an error
	 * @return	bool
	 */
	public function forumsCheckAccess( $fid, $prompt_login=0, $in='forum', $topic=array(), $return=false )
	{
		$fid         = intval( $fid );
		$deny_access = 1;
		
		/* Pass it along */
		$this->registry->permissions->setMemberData( $this->getMemberData() );
		
		if ( $this->registry->permissions->check( 'view', $this->forum_by_id[$fid] ) == TRUE )
		{
			if ( $this->registry->permissions->check( 'read', $this->forum_by_id[$fid] ) == TRUE )
			{
				$deny_access = 0;
			}
			else
			{
				//-----------------------------------------
				// Can see topics?
				//-----------------------------------------
		
				if ( $this->forum_by_id[$fid]['permission_showtopic'] )
				{
					$this->read_topic_only = 1;
					
					if ( $in == 'forum' )
					{
						$deny_access = 0;
					}
					else
					{
						if( $return )
						{
							return false;
						}
						
						$this->forumsCustomError( $fid );
						
						$deny_access = 1;						
					}
				}
				else
				{
					if( $return )
					{
						return false;
					}
						
					$this->forumsCustomError( $fid );
					
					$deny_access = 1;
				}
			}
		}
		else
		{
			if( $return )
			{
				return false;
			}
						
			$this->forumsCustomError( $fid );
			
			$deny_access = 1;
		}
		
		/* Reset member data after use */
		$this->registry->permissions->setMemberData( $this->memberData );
		
		//-----------------------------------------
		// Do we have permission to even see the password page?
		//-----------------------------------------
		
		if ( $deny_access == 0 )
		{
			$group_exempt = 0;
			
			if ( isset( $this->forum_by_id[$fid]['password'] ) AND $this->forum_by_id[$fid]['password'] != '' AND $this->forum_by_id[$fid]['sub_can_post'] )
			{
				if ( isset( $this->forum_by_id[$fid]['password_override'] ) && IPSText::cleanPermString($this->forum_by_id[$fid]['password_override']) != '' )
				{
					if ( IPSMember::isInGroup( $this->_memberData, explode( ",", IPSText::cleanPermString($this->forum_by_id[$fid]['password_override']) ) ) )
					{
						$group_exempt = 1;
						$deny_access = 0;
					}
				}
				
				if ( $group_exempt == 0 )
				{
					if ( $this->forumsComparePassword( $fid ) == TRUE )
					{
						$deny_access = 0;
					}
					else
					{
						$deny_access = 1;
						
						if ( $prompt_login == 1 )
						{
							if( $return )
							{
								return false;
							}

							$this->forumsShowLogin( $fid );
						}
					}
				}
			}
		}
		
		if( is_array( $topic ) && count( $topic ) )
		{
			if ( ( ! $this->_memberData['g_other_topics'] ) AND ( $topic['starter_id'] != $this->_memberData['member_id'] ) )
			{
				if( $return )
				{
					return false;
				}
				
				$this->registry->getClass('output')->showError( 'forums_no_view_topic', 103136, null, null, 404 );
			}
			else if( (!$this->forum_by_id[$fid]['can_view_others'] AND !$this->_memberData['is_mod'] ) AND ( $topic['starter_id'] != $this->_memberData['member_id'] ) )
			{
				if( $return )
				{
					return false;
				}
				
				$this->registry->getClass('output')->showError( 'forums_no_view_topic', 103137, null, null, 404 );
			}
		}

		if( $this->forum_by_id[$fid]['min_posts_view'] && $this->forum_by_id[$fid]['min_posts_view'] > $this->_memberData['posts'] && !$this->_memberData['g_is_supmod'] )
		{
			if( $return )
			{
				return false;
			}

			$this->registry->getClass('output')->showError( 'forums_not_enough_posts', 103138, null, null, 403 );
		}
		
		if ( $deny_access == 1 )
        {
        	if( $return )
        	{
        		return false;
        	}

        	$this->registry->getClass('output')->showError( 'forums_no_permission', 103139, null, null, 404 );
        }
        else
        {
	        return TRUE;
        }
	}

	/**
	 * Compare forum pasword
	 *
	 * @param	integer	$fid	Forum ID
	 * @return	bool
	 */
	public function forumsComparePassword( $fid )
	{
		$cookie_pass = IPSCookie::get( 'ipbforumpass_'.$fid );
		
		if ( trim( $cookie_pass ) == md5( $this->forum_by_id[$fid]['password'] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Forums custom error
	 *
	 * @param	integer	$fid	Forum ID
	 * @return	@e void
	 */
	public function forumsCustomError( $fid )
	{
		$tmp = $this->DB->buildAndFetch( array( 'select' => 'permission_custom_error', 'from' => 'forums', 'where' => "id=".$fid ) );
		
		if ( $tmp['permission_custom_error'] )
		{
			$this->registry->output->showError( $tmp['permission_custom_error'], 103149, null, null, 403 );
		}
	}
		
	/**
	 * Forums log in screen
	 *
	 * @param	integer	$fid	Forum ID
	 * @return	@e void
	 */
	public function forumsShowLogin( $fid )
	{
		/* Lang */
		$this->registry->class_localization->loadLanguageFile( array( 'public_forums' ), 'forums' );
		
		/* Output */
		$content = $this->registry->getClass('output')->getTemplate('forum')->forumPasswordLogIn( $fid );
		$nav     = $this->forumsBreadcrumbNav( $fid );
		
		if( is_array( $nav ) AND count( $nav ) )
		{
			foreach( $nav as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] . ' -> ' . $this->forum_by_id[$fid]['name'] );
		$this->registry->getClass('output')->addContent( $content );
		
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Find all the parents of a child without getting the nice lady to 
	 * use the superstore tannoy to shout "Small ugly boy in tears at reception"
	 *
	 * @param	integer	$root_id
	 * @param	array 	$ids
	 * @return	array
	 */
	public function forumsGetParents( $root_id, $ids=array() )
	{
		if ( $this->forum_by_id[ $root_id ]['parent_id'] and $this->forum_by_id[ $root_id ]['parent_id'] != 'root' )
		{
			$ids[] = $this->forum_by_id[ $root_id ]['parent_id'];
			
			// Stop endless loop setting cat as it's own parent?
			if ( in_array( $root_id, $ids ) )
			{
				//return $ids;
			}
			
			$ids = $this->forumsGetParents( $this->forum_by_id[ $root_id ]['parent_id'], $ids );
		}
	
		return $ids;
	}

	/**
	 * Get all the children
	 *
	 * @param	integer	$root_id
	 * @param	array 	$ids
	 * @return	array
	 */
	public function forumsGetChildren( $root_id, $ids=array() )
	{
		if ( isset( $this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				$ids[] = $forum_data['id'];
				
				$ids = $this->forumsGetChildren($forum_data['id'], $ids);
			}
		}
		
		return $ids;
	}
	
	/**
	 * Gets cumulative posts/topics - sets new post marker and last topic id
	 *
	 * @param	integer	$root_id
	 * @param	array 	$forum_data
	 * @param	bool	$done_pass
	 * @return	array
	 */
	public function forumsCalcChildren( $root_id, $forum_data=array(), $done_pass=0 )
	{
		//-----------------------------------------
		// Markers
		//-----------------------------------------

		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $forum_data['id'] ), 'forums' );

		if( !isset($forum_data['_has_unread']) )
		{
			$forum_data['_has_unread'] = ( $forum_data['last_post'] && $forum_data['last_post'] > $rtime ) ? 1 : 0;
		}

		if ( isset( $this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $data )
			{
				if ( $data['last_post'] > $forum_data['last_post'] AND ! $data['hide_last_info'] )
				{
					$forum_data['last_post']			= $data['last_post'];
					$forum_data['fid']					= $data['id'];
					$forum_data['last_id']				= $data['last_id'];
					$forum_data['last_title']			= $data['last_title'];
					$forum_data['seo_last_title']		= $data['seo_last_title'];
					$forum_data['password']				= isset( $data['password'] ) ? $data['password'] : '';
					$forum_data['password_override']	= isset( $data['password_override'] ) ? $data['password_override'] : '';
					$forum_data['last_poster_id']		= $data['last_poster_id'];
					$forum_data['last_poster_name']		= $data['last_poster_name'];
					$forum_data['seo_last_name']		= $data['seo_last_name'];
					$forum_data['_has_unread']          = $forum_data['_has_unread'];
				}
				
				//-----------------------------------------
				// Markers.  We never set false from inside loop.
				//-----------------------------------------
				
				$rtime	             = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $data['id'] ), 'forums' );
				$data['_has_unread'] = 0;
				
				if( $data['last_post'] && $data['last_post'] > $rtime )
				{
					$forum_data['_has_unread']		= 1;
					$data['_has_unread']            = 1;
				}
				
				//-----------------------------------------
				// If this forum isn't 'lit' yet, check it's children
				//-----------------------------------------
				
				if ( ! $data['_has_unread'] )
				{
					$children			= $this->forumsGetChildren( $data['id'] );
					
					if( count($children) )
					{
						foreach( $children as $_child )
						{
							$_child	= $this->getForumById( $_child );
							
							$rtime	             = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $_child['id'] ), 'forums' );
							
							if( $_child['last_post'] && $_child['last_post'] > $rtime )
							{
								$forum_data['_has_unread']		= 1;
								$data['_has_unread']            = 1;
								break;
							}
						}
					}
				}

				//-----------------------------------------
				// Topics and posts
				//-----------------------------------------
				
				$forum_data['posts']  += $data['posts'];
				$forum_data['topics'] += $data['topics'];
				
				$_mod = ( isset( $this->_memberData['forumsModeratorData'] ) ) ? $this->_memberData['forumsModeratorData'] : array();
				
				if ( $this->_memberData['g_is_supmod'] or ( $_mod && !empty( $_mod[ $data['id'] ]['post_q'] ) ) )
				{
					$forum_data['queued_posts']  += $data['queued_posts'];
					$forum_data['queued_topics'] += $data['queued_topics'];
				}
				
				if ( ! $done_pass )
				{
					$forum_data['subforums'][ $data['id'] ] = array($data['id'], $data['name'], $data['name_seo'], intval( $data['_has_unread']  ), 0 );
				}
				
				$forum_data = $this->forumsCalcChildren( $data['id'], $forum_data, 1 );
			}
		}

		return $forum_data;
	}
	
	/**
	 * Create forum breadcrumb nav
	 *
	 * @param	integer	$root_id
	 * @param	string	$url
	 * @return	array
	 */
	public function forumsBreadcrumbNav($root_id, $url='showforum=', $omitLast=false)
	{
		$nav_array[] = array( $this->forum_by_id[$root_id]['name'], $url . $root_id, $this->forum_by_id[$root_id]['name_seo'], 'showforum' );
	
		$ids = $this->forumsGetParents( $root_id );
		
		if ( is_array($ids) and count($ids) )
		{
			foreach( $ids as $id )
			{
				$data = $this->forum_by_id[$id];
				
				$nav_array[] = array( $data['name'], $url . $data['id'], $data['name_seo'], 'showforum' );
			}
		}
		
		if ( $omitLast )
		{
			$x = array_shift( $nav_array );
		}
		
		return array_reverse( $nav_array );
	}
	
	/**
	 * Builds the forum jump
	 *
	 * @param	bool	$html
	 * @param	bool	$override
	 * @param	bool	$remove_redirects
	 * @param	mixed	$defaulted		Single forum id to check against, or array of forum ids to check
	 * @return	string
	 */
	public function forumsForumJump( $html=0, $override=0, $remove_redirects=0, $defaulted=array(), $noSelectRoot=false )
	{
		$jump_string	= "";
		$defaulted		= ( is_array($defaulted) AND count($defaulted) ) ? $defaulted : ( is_int($defaulted) ? array( $defaulted ) : ( $this->request['f'] ? array( $this->request['f']) : array() ) );
		
		if( is_array( $this->forum_cache['root'] ) AND count( $this->forum_cache['root'] ) )
		{
			foreach( $this->forum_cache['root'] as $forum_data )
			{
				if ( $forum_data['sub_can_post'] or ( isset( $this->forum_cache[ $forum_data['id'] ] ) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) AND count( $this->forum_cache[ $forum_data['id'] ] ) ) )
				{
					$forum_data['redirect_on'] = isset( $forum_data['redirect_on'] ) ? $forum_data['redirect_on'] : 0;
					
					if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
					{
						continue;
					}
					
					$selected = "";
					
					if ($html == 1 or $override == 1)
					{
						if( in_array( $forum_data['id'], $defaulted ) )
						{
							$selected = ' selected="selected"';
						}
					}
					
					if ( $noSelectRoot )
					{
						$selected = "disabled='disabed'";
					}
					
					$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">".$forum_data['name']."</option>\n";
					
					$depth_guide = $this->depth_guide;
					
					if ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
					{
						foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
							{
								continue;
							}						
							
							if ($html == 1 or $override == 1)
							{
								$selected = "";
								
								if( in_array( $forum_data['id'], $defaulted ) )
								{
									$selected = ' selected="selected"';
								}
							}
							
							$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$forum_data['name']."</option>\n";
							
							$jump_string = $this->_forumsForumJumpInternal( $forum_data['id'], $jump_string, $depth_guide . $this->depth_guide, $html, $override, $remove_redirects, $defaulted );
						}
					}
				}
			}
		}
		
		return $jump_string;
	}
	
	/**
	 * Internal helper function for forumsForumJump
	 *
	 * @param	integer	$root_id
	 * @param	string	$jump_string
	 * @param	string	$depth_guide
	 * @param	bool	$html
	 * @param	bool	$override
	 * @param	bool	$remove_redirects
	 * @param	array	$defaulted
	 * @return	string
	 */
	protected function _forumsForumJumpInternal( $root_id, $jump_string="", $depth_guide="",$html=0, $override=0, $remove_redirects=0, $defaulted=array() )
	{
		if ( isset($this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
				{
					continue;
				}
				
				$selected = "";
								
				if ($html == 1 or $override == 1)
				{
					if( in_array( $forum_data['id'], $defaulted ) )
					{
						$selected = ' selected="selected"';
					}
				}
					
				$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$forum_data['name']."</option>\n";
				
				$jump_string = $this->_forumsForumJumpInternal( $forum_data['id'], $jump_string, $depth_guide . $this->depth_guide, $html, $override, $defaulted );
			}
		}
		
		
		return $jump_string;
	}
	
	/**
	 * Sorts out the last poster, etc
	 *
	 * @param	array 	$forum_data
	 * @return	array
	 */
	public function forumsFormatLastinfo( $forum_data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->settings['disable_subforum_show'] = intval( $this->settings['disable_subforum_show'] );
		
		$show_subforums					= $this->registry->permissions->check( 'view', $this->forum_by_id[ $forum_data['id'] ] ) ? 1 : 0;
		$this->request['f']				= isset( $this->request['f'] ) ? intval( $this->request['f'] ) : 0;

		$forum_data['img_new_post']		= $this->forumsNewPosts( $forum_data );
		$forum_data['last_topic_title']	= $this->lang->words['f_none'];
		$forum_data['last_topic_id']	= 0;
		$forum_data['full_last_title']	= isset( $forum_data['last_title'] ) ? $forum_data['last_title'] : '';
		$forum_data['_hide_last_date']	= false;
		
		if (isset($forum_data['last_title']) and $forum_data['last_id'])
		{
			$forum_data['last_title'] = strip_tags( $forum_data['last_title'] );
			$forum_data['last_title'] = str_replace( "&#33;" , "!", $forum_data['last_title'] );
			$forum_data['last_title'] = str_replace( "&quot;", '"', $forum_data['last_title'] );
			
			$forum_data['last_title'] = IPSText::truncate($forum_data['last_title'], 30);
			
			if ( ( ! empty($forum_data['password']) ) OR ( $this->registry->permissions->check( 'read', $this->forum_by_id[ $forum_data['fid'] ] ) != TRUE AND $this->forum_by_id[ $forum_data['fid'] ]['permission_showtopic'] == 0 ) )
			{
				$forum_data['last_topic_title']	= $this->lang->words['f_protected'];
				$forum_data['_hide_last_date']	= true;
			}
			else if( $forum_data['hide_last_info'] )
			{
				$forum_data['last_topic_title']	= $this->lang->words['f_protected'];
				$forum_data['_hide_last_date']	= true;
			}
			else
			{
				$forum_data['last_topic_id']	= $forum_data['last_id'];
				
				if ( $this->memberData['member_id'] || $this->settings['topic_marking_guests'] )
				{
					$forum_data['last_topic_title']	= "<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$forum_data['last_id']}&amp;view=getnewpost", 'public' ), $forum_data['seo_last_title'], 'showtopicunread' ) . "' title='{$forum_data['full_last_title']}'>{$forum_data['last_title']}</a>";
				}
				else
				{
					$forum_data['last_topic_title']	= "<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$forum_data['last_id']}", 'public' ), $forum_data['seo_last_title'], 'showtopic' ) . "' title='{$forum_data['full_last_title']}'>{$forum_data['last_title']}</a>";
				}
			}
		}
		
		$forum_data['posts']	= $this->registry->getClass('class_localization')->formatNumber( $forum_data['posts'] );
		$forum_data['topics']	= $this->registry->getClass('class_localization')->formatNumber( $forum_data['topics'] );
		
		if ( $this->settings['disable_subforum_show'] == 0 AND $show_subforums == 1 )
		{
			if ( isset($forum_data['subforums']) and is_array( $forum_data['subforums'] ) and count( $forum_data['subforums'] ) )
			{
				$forum_data['show_subforums'] = 1;
				
				/* Rock star programming yo */
				$lastSubForum = array_pop( $forum_data['subforums'] );
				$lastSubForum[4] = 1;
				
				$forum_data['subforums'][ $lastSubForum['id'] ] = $lastSubForum;
			}
		}
		
		$_mod	= ( isset( $this->_memberData['forumsModeratorData'] ) ) ? $this->_memberData['forumsModeratorData'] : array();
		
		if ( $this->_memberData['g_is_supmod'] or !empty($_mod[ $forum_data['id'] ]['post_q']) )
		{
			if ( $forum_data['queued_posts'] or $forum_data['queued_topics'] )
			{
				$forum_data['_has_queued_and_can_see_icon']	= 1;
				$forum_data['queued_posts']					= intval($forum_data['queued_posts']);
				$forum_data['queued_topics']				= intval($forum_data['queued_topics']);
			}
		}
		
		return $forum_data;
	}
	
	/**
	 * Generate the appropriate folder icon for a forum
	 *
	 * @param	array 	$forum_data
	 * @return	string
	 */
	public function forumsNewPosts( $forum_data )
	{
		$sub = 0;
        
        if ( isset($forum_data['subforums']) AND count($forum_data['subforums']) )
        {
        	$sub = 1;
        }

        //-----------------------------------------
        // Sub forum?
        //-----------------------------------------
        
        if ($sub ==  0)
        {
			$sub_cat_img = '';
        }
        else
        {
        	$sub_cat_img = '_cat';
        }
		
		if ( isset($forum_data['password']) AND $forum_data['password'] != '' AND $sub == 0 )
        {
            return $forum_data['_has_unread'] ? "f_pass_unread" : "f_pass_read";
        }
        
        return $forum_data['_has_unread'] ? "f".$sub_cat_img."_unread" : "f".$sub_cat_img."_read";
    }
	
	/**
	 * Locate the category of any forum
	 *
	 * @param	int		Forum ID
	 * @return	int		Category ID (root forum ID)
	 */
	public function fetchTopParentID( $forumID )
	{
		$ids = $this->forumsGetParents( $forumID );
	
		return array_pop( $ids );
	}
	
	/**
	 * Generate the appropriate folder icon for a topic
	 *
	 * @param	array	Topic data array
	 * @param	string	Dot flag
	 * @param	bool	Whether item is read or not
	 * @return	array
	 */
	public function fetchTopicFolderIcon( $topic, $dot="", $is_read=false )
	{
		return array(
					'is_read'		=> $is_read,
					'is_closed'		=> ( $topic['state'] == 'closed' OR $this->registry->getClass('class_forums')->fetchHiddenTopicType( $topic ) == 'sdelete' ) ? true : false,
					'is_poll'		=> $topic['poll_state'] ? true : false,
					'show_dot'		=> $dot,
					'is_moved'		=> ( $topic['state'] == 'moved' or $topic['state'] == 'link' ) ? true : false,
					'is_hot'		=> ( $topic['posts'] + 1 >= $this->settings['hot_topic'] ) ? true : false,
					);
	}
	
	/**
	 * Build <select> jump menu
	 * $html = 0 means don't return the select html stuff
	 * $html = 1 means return the jump menu with select and option stuff
	 *
	 * @param	integer	HTML flag (see above)
	 * @param	integer	Override flag
	 * @param	integer
	 * @return	string	Parsed HTML
	 */
	public function buildForumJump( $html=1, $override=0, $remove_redirects=0, $selected = array() )
	{
		$the_html	= $this->forumsForumJump( $html, $override, $remove_redirects, $selected );
		
		if( $html )
		{
			$the_html	= $this->registry->getClass('output')->getTemplate('global')->forum_jump( $the_html );
		}

		return $the_html;
	}
	
	/**
	 * Determine if this user / forum combo can manage mod queue
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 */
	public function canQueuePosts( $fid=0 )
	{
		$return = 0;
		$_mod   = $this->_memberData['forumsModeratorData'];
		
		if ( $this->_memberData['g_is_supmod'] )
		{
			$return = 1;
		}
		else if ( $fid AND $this->_memberData['is_mod'] AND !empty($_mod[ $fid ]['post_q']) )
		{
			$return = 1;
		}
		
		return $return;
	}
	
	/**
	 * Determine if this user / forum combo can soft delete
	 *
	 * @param	integer	Forum ID
	 * @param	array	Post information
	 * @return	integer Boolean
	 */
	public function canSoftDeletePosts( $fid=0, $post )
	{
		if ( $fid and $this->_memberData['is_mod'] and !empty( $this->_memberData['forumsModeratorData'][ $fid ]['bw_mod_soft_delete'] ) )
		{
			return TRUE;
		}
		else
		{
			return IPSMember::canModerateContent( $this->_memberData, IPSMember::CONTENT_HIDE, $post['author_id'] );
		}
	}
	
	/**
	 * Determine if this user / forum combo can hard delete
	 *
	 * @param	integer	Forum ID
	 * @param	array	Post information
	 * @return	integer Boolean
	 */
	public function canHardDeletePosts( $fid=0, $post )
	{
		if ( $fid and $this->_memberData['is_mod'] and !empty( $this->_memberData['forumsModeratorData'][ $fid ]['delete_post'] ) )
		{
			return TRUE;
		}
		else
		{
			return IPSMember::canModerateContent( $this->_memberData, IPSMember::CONTENT_DELETE, $post['author_id'] );
		}
	}
	
	/**
	 * Determine if this user / forum combo can un soft delete
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 */
	public function can_Un_SoftDeletePosts( $fid=0 )
	{
		if ( $fid and $this->_memberData['is_mod'] and !empty( $this->_memberData['forumsModeratorData'][ $fid ]['bw_mod_un_soft_delete'] ) )
		{
			return TRUE;
		}
		else
		{
			return IPSMember::canModerateContent( $this->_memberData, IPSMember::CONTENT_UNHIDE );
		}
	}

	
	/**
	 * Determine if this user / forum combo can see soft deleted posts
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 */
	public function canSeeSoftDeletedPosts( $fid=0 )
	{
		if ( $this->_memberData['g_is_supmod'] or ( $fid and $this->_memberData['is_mod'] and !empty( $this->_memberData['forumsModeratorData'][ $fid ]['bw_mod_soft_delete_see'] ) ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Determine if this user / forum combo can soft delete
	 *
	 * @param	array	Topic Data
	 * @return	integer Boolean
	 */
	public function canSoftDeleteTopics( $fid, $topic=array() )
	{
		if ( $fid and $this->_memberData['is_mod'] and !empty( $this->_memberData['forumsModeratorData'][ $fid ]['bw_mod_soft_delete'] ) )
		{
			return TRUE;
		}
		else
		{
			return IPSMember::canModerateContent( $this->_memberData, IPSMember::CONTENT_HIDE, $topic['starter_id'] );
		}
	}
	
	/**
	 * Determine if this user / forum combo can un soft delete
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 *
	 * @deprecated
	 */
	public function can_Un_SoftDeleteTopics( $fid=0 )
	{
		return $this->can_Un_SoftDeletePosts( $fid );
	}
	
	/**
	 * Determine if this user / forum combo can hard delete
	 *
	 * @param	integer	Forum ID
	 * @param	array	Topic information
	 * @return	integer Boolean
	 */
	public function canHardDeleteTopics( $fid=0, $topic )
	{
		if ( $fid and $this->_memberData['is_mod'] and !empty( $this->_memberData['forumsModeratorData'][ $fid ]['delete_topic'] ) )
		{
			return TRUE;
		}
		elseif ( $topic['starter_id'] == $this->_memberData['member_id'] and $this->_memberData['g_delete_own_topics'] )
		{
			return TRUE;
		}
		else
		{
			return IPSMember::canModerateContent( $this->_memberData, IPSMember::CONTENT_DELETE, $topic['starter_id'] );
		}
	}
	
	/**
	 * Determine if this user / forum combo can see soft deleted posts
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 *
	 * @deprecated
	 */
	public function canSeeSoftDeletedTopics( $fid=0 )
	{
		return $this->canSeeSoftDeletedPosts( $fid );
	}
	
	/**
	 * Determine if this user / forum combo can see soft delete reason
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 *
	 * @deprecated
	 */
	public function canSeeSoftDeleteReason( $fid=0 )
	{
		return $this->canSeeSoftDeletedPosts( $fid );
	}
	
	/**
	 * Determine if this user / forum combo can soft delete
	 *
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 *
	 * @deprecated
	 */
	public function canSeeSoftDeleteContent( $fid=0 )
	{
		return $this->canSeeSoftDeletedPosts( $fid );
	}
	
	/**
	 * Post queue type
	 *
	 * @param	array		Post data
	 * @return	string		'visible', 'hidden' or 'sdelete'
	 */
	public function fetchHiddenType( $post )
	{
		if ( isset( $post['queued'] ) )
		{
			switch ( intval( $post['queued'] ) )
			{
				case 4:
					return 'oktoremove';
				break;
				case 3:
					return 'pdelete';
				break;
				case 2:
					return 'sdelete';
				break;
				case 1:
					return 'hidden';
				break;
				case 0:
					return 'visible';
				break;
			}
		}
		
		return 'visible';
	}
	
	/**
	 * Fetch correct flag
	 * Pass several or one field through ->fetchPostHiddenQuery( array('hidden', 'sdelete' ) );
	 *
	 * @param	array		Type: 'sdelete', 'hidden', 'visible', 'notVisible' (notVisible can mean either sDelete or hidden)
	 * @return	string
	 * @todo	"notVisible" is not relevant here = we should remove it as an option.  It can mean hidden OR soft deleted, but both can't be returned.
	 */
	public function fetchPostHiddenFlag( $type )
	{
		$value  = 0;
		
		switch( $type )
		{
			case 'oktoremove':
				$value = 4;
			break;
			case 'pdeleted':
			case 'pdelete':
				$value = 3;
			break;
			case 'sdeleted':
			case 'sdelete':
			case 'notVisible':
				$value = 2;
			break;
			case 'queued':
			case 'hidden':
				$value = 1;
			break;
			case 'approved':
			case 'visible':
				$value = 0;
			break;
		}
		
		return $value;
	}
	
	/**
	 * Fetch correct fields and data
	 * Pass several or one field through ->fetchPostHiddenQuery( array('hidden', 'sdelete' ) );
	 *
	 * @param	array		Type: 'sdelete', 'hidden', 'visible', 'notVisible' (notVisible can mean either sDelete or hidden)
	 * @param	string		Table prefix (t.)
	 * @return	string
	 */
	public function fetchPostHiddenQuery( $type, $tPrefix='' )
	{
		$type   = ( is_array( $type ) ) ? $type : array( $type );
		$values = array();
		
		foreach( $type as $_t )
		{
			switch( $_t )
			{
				case 'oktoremove':
					$values[] = 4;
				break;
				case 'pdeleted':
				case 'pdelete':
					$values[] = 3;
				break;
				case 'sdeleted':
				case 'sdelete':
					$values[] = 2;
				break;
				case 'queued':
				case 'hidden':
					$values[] = 1;
				break;
				case 'approved':
				case 'visible':
					$values[] = 0;
				break;
				
				case 'notVisible':
					$values[] = 1;
					$values[] = 2;
				break;
			}
		}
		
		if ( count( $values ) )
		{
			if ( count( $values ) == 1 )
			{
				return ' ' . $tPrefix . 'queued=' . $values[0] . ' ';
			}
			else
			{
				return ' ' . $tPrefix . 'queued IN (' . implode( ',', $values ) . ') ';
			}
		}
		
		/* oops, return something to not break the query */
		return '1=1';
	}
	
	/**
	 * Topic queue type
	 *
	 * @param	array		Post data
	 * @return	string		'visible', 'hidden' or 'sdelete'
	 */
	public function fetchHiddenTopicType( $topic )
	{
		if ( isset( $topic['approved'] ) )
		{
			switch ( intval( $topic['approved'] ) )
			{
				case -1:
					return 'sdelete';
				break;
				case 0:
					return 'hidden';
				break;
				case 1:
					return 'visible';
				break;
				case 3:
					return 'oktoremove';
				break;
				case 2:
					return 'pdelete';
				break;
			}
		}
		
		return 'visible';
	}
	
	/**
	 * Fetch correct flag
	 * Pass several or one field through ->fetchTopicHiddenFlag( array('hidden', 'sdelete' ) );
	 *
	 * @param	array		Type: 'sdelete', 'hidden', 'visible', 'notVisible' (notVisible can mean either sDelete or hidden)
	 * @return	string
	 */
	public function fetchTopicHiddenFlag( $type )
	{
		$value  = 1;
		
		switch( $type )
		{
			case 'sdeleted':
			case 'sdelete':
				$value = -1;
			break;
			case 'queued':
			case 'hidden':
				$value = 0;
			break;
			case 'approved':
			case 'visible':
				$value = 1;
			break;
			case 'pdelete':
			case 'pdeleted':
				$value = 2;
			break;
			case 'oktoremove':
				$value = 3;
			break;
		}
		
		return $value;
	}
	
	/**
	 * Fetch correct fields and data
	 * Pass several or one field through ->fetchPostHiddenQuery( array('hidden', 'sdelete' ) );
	 *
	 * @param	array		Type: 'sdelete', 'hidden', 'visible', 'notVisible' (notVisible can mean either sDelete or hidden)
	 * @param	string		Table prefix (t.)
	 * @return	string
	 */
	public function fetchTopicHiddenQuery( $type, $tPrefix='' )
	{
		$type   = ( is_array( $type ) ) ? $type : array( $type );
		$values = array();
		
		if ( in_array( 'all', $type ) )
		{
			return '1=1';
		}
		
		foreach( $type as $_t )
		{
			switch( $_t )
			{
				case 'sdeleted':
				case 'sdelete':
					$values[] = -1;
				break;
				case 'queued':
				case 'hidden':
					$values[] = 0;
				break;
				case 'approved':
				case 'visible':
					$values[] = 1;
				break;
				case 'pdelete':
				case 'pdeleted':
					$values[] = 2;
				break;
				case 'oktoremove':
					$values[] = 3;
				break;
			}
		}
		
		if ( count( $values ) )
		{
			if ( count( $values ) == 1 )
			{
				return ' ' . $tPrefix . 'approved=' . $values[0] . ' ';
			}
			else
			{
				return ' ' . $tPrefix . 'approved IN (' . implode( ',', $values ) . ') ';
			}
		}
		
		/* oops, return something to not break the query */
		return '1=1';
	}
	
	/**
	 * Fetch correct fields and data
	 *
	 * @param	array		Type: 'not', 'archived', 'working', 'exclude'
	 * @param	string		Table prefix (t.)
	 * @return	string
	 */
	public function fetchTopicArchiveQuery( $type, $tPrefix='' )
	{
		$type   = ( is_array( $type ) ) ? $type : array( $type );
		$values = array();
		
		foreach( $type as $_t )
		{
			switch( $_t )
			{
				case 'not':
					$values[] = 0;
				break;
				case 'archived':
					$values[] = 1;
				break;
				case 'working':
					$values[] = 2;
				break;
				case 'exclude':
					$values[] = 3;
				break;
				case 'restore':
					$values[] = 4;
				break;
			}
		}
		
		if ( count( $values ) )
		{
			if ( count( $values ) == 1 )
			{
				return ' ' . $tPrefix . 'topic_archive_status=' . $values[0] . ' ';
			}
			else
			{
				return ' ' . $tPrefix . 'topic_archive_status IN (' . implode( ',', $values ) . ') ';
			}
		}
		
		/* oops, return something to not break the query */
		return '1=1';
	}
	
	/**
	 * Fetch correct flag
	 * Pass several or one field through ->fetchTopicHiddenFlag( array('not', 'archived', 'working', 'exclude' ) );
	 *
	 * @param	array
	 * @return	string
	 */
	public function fetchTopicArchiveFlag( $type )
	{
		$value  = 0;
		
		switch( $type )
		{
			case 'not':
				$value = 0;
			break;
			case 'archived':
				$value = 1;
			break;
			case 'working':
				$value = 2;
			break;
			case 'exclude':
				$value = 3;
			break;
			case 'restore':
				$value = 4;
			break;
		}
		
		return $value;
	}
	
	/**
	 * Topic archive status
	 *
	 * @param	array		Topic data
	 * @return	string		not, archived, working, exclude
	 */
	public function fetchArchiveTopicType( $topic )
	{
		if ( isset( $topic['topic_archive_status'] ) )
		{
			switch ( intval( $topic['topic_archive_status'] ) )
			{
				default:
				case 0:
					return 'not';
				break;
				case 1:
					return 'archived';
				break;
				case 2:
					return 'working';
				break;
				case 3:
					return 'exclude';
				break;
				case 4:
					return 'restore';
				break;
			}
		}
		
		return 'not';
	}
	
	/**
	 * Determine if this user / forum combo can manage multi moderation tasks
	 * and return mm_array of allowed tasks
	 *
	 * @param	integer	Forum ID
	 * @return	array	Allowed tasks
	 */
	public function getMultimod( $fid )
	{
		$mm_array = array();
		$_mod     = $this->_memberData['forumsModeratorData'];
		$pass_go  = FALSE;
		
		if ( $this->_memberData['member_id'] )
		{
			if ( $this->_memberData['g_is_supmod'] )
			{
				$pass_go = TRUE;
			}
			else if ( !empty($_mod[ $fid ]['can_mm']) )
			{
				$pass_go = TRUE;
			}
		}
		
		if ( $pass_go != TRUE )
		{
			return $mm_array;
		}
		
		if ( ! is_array( $this->caches['multimod'] ) )
        {
        	$cache = array();
        	
			$this->DB->build( array( 'select' => '*', 'from' => 'topic_mmod', 'order' => 'mm_title' ) );
			$this->DB->execute();
						
			while ($i = $this->DB->fetch())
			{
				$cache[ $i['mm_id'] ] = $i;
			}
			
			$this->cache->setCache( 'multimod', $cache,  array( 'array' => 1 ) );
        }
		
		//-----------------------------------------
		// Get the topic mod thingies
		//-----------------------------------------
		
		if( count( $this->caches['multimod'] ) AND is_array( $this->caches['multimod'] ) )
		{
			foreach( $this->caches['multimod'] as $r )
			{
				if ( $r['mm_forums'] == '*' OR strstr( ",".$r['mm_forums'].",", ",".$fid."," ) )
				{
					$mm_array[] = array( $r['mm_id'], $r['mm_title'] );
				}
			}
		}
		
		return $mm_array;
	}

	/**
	 * Fetch forum IDs safe to use when searching, etc
	 *
	 * @param	int			Optional member ID, if no member ID is passed, it'll use current member
	 * @param	array		Array of ids to skip
	 * @param	bool		Return categories too
	 * @return	array		Array of "good" IDs
	 */
	public function fetchSearchableForumIds( $memberId=null, $skipIds=array(), $returnCategories=false )
	{
		$forumIdsOk = array();
		$member 	= ( $memberId === null || $memberId == $this->_memberData['member_id'] ) ? $this->_memberData : IPSMember::load( $memberId, 'core' );
		$posts 		= intval( $member['posts'] );
		
		/* Ensure this has been set up */
		if ( ! is_array( $this->forum_by_id ) OR ! count( $this->forum_by_id ) )
		{
			$this->strip_invisible = 1;
			$this->forumsInit();
		}

		/* Get list of good forum IDs */
		foreach( $this->forum_by_id as $id => $data )
		{
			/* Can we read? */
			if ( ! $this->registry->permissions->check( 'view', $data ) )
			{
				continue;
			}

			/* Can we read, or is this a category? */
			if( ! $returnCategories AND ! $this->registry->permissions->check( 'read', $data ) )
			{
				continue;
			}
			else if( $returnCategories AND ! $data['parent_id'] AND ! $this->registry->permissions->check( 'read', $data ) )
			{
				continue;
			}
			
			/* Can read, but is it password protected, etc? */
			if ( ! $this->forumsCheckAccess( $id, 0, 'forum', array(), true ) )
			{
				continue;
			}
									
			if ( ( ! $data['sub_can_post'] OR ! $data['can_view_others'] ) AND !$this->memberData['g_is_supmod'] AND !isset( $this->memberData['forumsModeratorData'][ $id ] ) )
			{
				continue;
			}
			
			if ( $data['min_posts_view'] > $posts AND !$member['g_is_supmod'] )
			{
				continue;
			}

			if ( is_array( $skipIds ) AND count( $skipIds ) )
			{
				if ( in_array( $id, $skipIds ) )
				{
					continue;
				}
			}
			
			$forumIdsOk[] = $id;
		}
		
		return $forumIdsOk;
	}
	
	/**
	 * Rebuild a forum's count
	 *
	 * @return	boolean
	 */
	public function forumRebuild( $fid )
	{
		$fid = intval($fid);
		
		if ( ! $fid )
		{
			return false;
		}
		
		/* Topics */
		$topics			= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'not', 'exclude' ) ) . ' AND ' . $this->fetchTopicHiddenQuery( array( 'visible' ), '' ) . " and forum_id={$fid}" ) );

		/* Queued topics */
		$queued_topics	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'not', 'exclude' ) ) . ' AND ' . $this->fetchTopicHiddenQuery( array( 'hidden' ), '' ) . " and forum_id={$fid}" ) );
																	
		/* Deleted topics */
		$deleted_topics	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'not', 'exclude' ) ) . ' AND ' . $this->fetchTopicHiddenQuery( array( 'sdeleted' ), '' ) . " and forum_id={$fid}" ) );
		
		/* Archived topics */
		$archived_topics	= $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														       'from'	=> 'topics',
														       'where'	=> $this->fetchTopicArchiveQuery( array( 'archived', 'working' ) ) . " and forum_id={$fid}" ) );
		
		/* Posts */
		$posts			= $this->DB->buildAndFetch( array( 'select'	=> 'SUM(posts) as replies',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'not', 'exclude' ) ) . ' AND ' . $this->fetchTopicHiddenQuery( array( 'visible' ), '' ) . " and forum_id={$fid}" ) );
		
		/* Queued posts */
		$queued_posts	= $this->DB->buildAndFetch( array( 'select'	=> 'SUM(topic_queuedposts) as replies',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'not', 'exclude' ) ) . ' AND ' . $this->fetchTopicHiddenQuery( array( 'visible', 'hidden' ), '' ) . " and forum_id={$fid}" ) );
		
		/* Deleted posts */
		$deleted_posts	= $this->DB->buildAndFetch( array( 'select'	=> 'SUM(topic_deleted_posts) as replies',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'not', 'exclude' ) ) . ' AND ' . "forum_id={$fid}" ) );
		
		/* Archived posts */
		$archived_posts	= $this->DB->buildAndFetch( array( 'select'	=> 'SUM(posts) as replies',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicArchiveQuery( array( 'archived', 'working' ) ) . " and forum_id={$fid}" ) );
		
		/* Last poster */
		$last_post		= $this->DB->buildAndFetch( array( 'select'	=> 'tid, title, last_poster_id, last_poster_name, seo_last_name, last_post',
														   'from'	=> 'topics',
														   'where'	=> $this->fetchTopicHiddenQuery( array( 'visible' ), '' ) . " and forum_id={$fid}",
														   'order'	=> 'last_post DESC',
														   'limit'	=> array( 1 ) ) );
		
		/* Newest topic */
		$newest_topic	= $this->DB->buildAndFetch( array( 'select'	=> 'title, tid, seo_first_name',
														   'from'	=> 'topics',
														   'where'	=> 'forum_id=' . $fid . ' and ' . $this->fetchTopicHiddenQuery( array( 'visible' ), '' ),
														   'order'	=> 'start_date desc',
														   'limit'	=> array( 1 ) ) );
		
		/* Rebuild last topics */
		$lastXTopics    = $this->lastXFreeze( $this->buildLastXTopicIds( $fid, FALSE ) );
		
		/* Save */
		$dbs = array( 'name_seo'			=> IPSText::makeSeoTitle( $this->allForums[ $fid ]['name'] ),
					  'last_poster_id'		=> intval($last_post['last_poster_id']),
					  'last_poster_name'	=> $last_post['last_poster_name'],
					  'seo_last_name'       => IPSText::makeSeoTitle( $last_post['last_poster_name'] ),
					  'last_post'			=> intval($last_post['last_post']),
					  'last_title'			=> $last_post['title'],
					  'seo_last_title'      => IPSText::makeSeoTitle( $last_post['title'] ),
					  'last_id'				=> intval($last_post['tid']),
					  'topics'				=> intval($topics['count']) + intval($archived_topics['count']),
					  'posts'				=> intval($posts['replies']) + intval($archived_posts['replies']),
					  'queued_topics'		=> intval($queued_topics['count']),
					  'queued_posts'		=> intval($queued_posts['replies']),
					  'deleted_posts'		=> intval($deleted_posts['replies']),
					  'archived_topics'		=> intval($archived_topics['count']),
					  'archived_posts'		=> intval($archived_posts['replies']),
					  'deleted_topics'		=> intval($deleted_topics['count']),
					  'newest_id'			=> intval($newest_topic['tid']),
					  'newest_title'		=> $newest_topic['title'],
					  'last_x_topic_ids'    => $lastXTopics );
					
		if ( $this->allForums[ $fid ]['_update_deletion'] )
		{
			$dbs['forum_last_deletion'] = time();
		}
		
		$this->DB->setDataType( array( 'last_poster_name', 'last_title', 'newest_title', 'seo_last_title', 'seo_last_name' ), 'string' );

		$this->DB->update( 'forums', $dbs, "id=" . $fid );

		return true;
	}
	/**
	 * Determine if user can still access forum
	 *
	 * @param	array 		Topic/member/forum data
	 * @param	boolean		Check topic access
	 * @return	boolean
	 */
	public function checkEmailAccess( $t, $checkTopic=true )
	{
		//-----------------------------------------
		// Test for group permissions
		//-----------------------------------------
		
		$member_groups	= array( $t['member_group_id'] );
		$mgroup_others	= "";
		$temp_mgroups	= array();
		$mgroup_perms	= array();
		
		$t['mgroup_others']	= IPSText::cleanPermString( $t['mgroup_others'] );
		
		if( $t['mgroup_others'] )
		{
			$temp_mgroups		= explode( ",", $t['mgroup_others'] );
			
			if( count($temp_mgroups) )
			{
				foreach( $temp_mgroups as $other_mgroup )
				{
					/* Does it exist? */
					if ( $this->caches['group_cache'][ $other_mgroup ]['g_perm_id'] )
					{
						$member_groups[]	= $other_mgroup;
						$mgroup_perms[]		= $this->caches['group_cache'][ $other_mgroup ]['g_perm_id'];
					}
				}
			}
			
			if( count($mgroup_perms) )
			{
				$mgroup_others = "," . implode( ",", $mgroup_perms ) . ",";
			}
		}

		$perm_id = ( $t['org_perm_id'] ) ? $t['org_perm_id'] : $this->caches['group_cache'][ $t['member_group_id'] ]['g_perm_id'] . $mgroup_others;

		//-----------------------------------------
		// Can they view forum?
		//-----------------------------------------

		if ( $this->registry->permissions->check( 'view', $this->allForums[ $t['forum_id'] ], explode( ',', $perm_id ) ) !== TRUE )
		{
			return false;
		}

		//-----------------------------------------
		// Can they read topics in the forum?
		//-----------------------------------------
		
		if ( $this->registry->permissions->check( 'read', $this->allForums[ $t['forum_id'] ], explode( ',', $perm_id ) ) !== TRUE )
		{
			return false;
		}
		
		//-----------------------------------------
		// Can view others topics
		//-----------------------------------------
		
		if( $checkTopic )
		{
			$canViewOthers	= false;
			$t				= IPSMember::setUpModerator( $t );
			
			foreach( $member_groups as $mgroup )
			{
				if( $this->caches['group_cache'][ $mgroup ]['g_other_topics'] )
				{
					$canViewOthers	= true;
				}
			}
			
			if( ! $canViewOthers AND $t['starter_id'] != $t['member_id'] )
			{
				return false;
			}
			else if( !$this->allForums[ $t['forum_id'] ]['can_view_others'] AND $t['starter_id'] != $t['member_id'] AND !$t['is_mod'] )
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Minimum posts to view
		//-----------------------------------------
		
		if( $this->allForums[ $t['forum_id'] ]['min_posts_view'] && $this->allForums[ $t['forum_id'] ]['min_posts_view'] > $t['posts'] && !$t['g_is_supmod'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Banned?
		//-----------------------------------------
		
		if( $t['member_banned'] )
		{
			return false;
		}
		
		$_canView	= false;
		
		foreach( $member_groups as $mgroup )
		{
			if( $this->caches['group_cache'][ $mgroup ]['g_view_board'] )
			{
				$_canView	= true;
				break;
			}
		}
		
		return $_canView;
	}
	
	/**
	 * Determine if topic is approved (or if they are a mod)
	 *
	 * @param	array 		Topic/member/forum data
	 * @return	boolean
	 */
	public function checkEmailApproved( $t )
	{
		$t['mgroup_others']	= IPSText::cleanPermString( $t['mgroup_others'] );
		
		//-----------------------------------------
		// Test for approved/approve perms
		//-----------------------------------------
		
		if( $t['approved'] > 1 )
		{
			return false;
		}

		if( $t['approved'] == 0 )
		{
			$mod = false;
			
			$memberGroups = array( $t['member_group_id'] );
			
			if( $t['mgroup_others'] )
			{
				$memberGroups = array_merge( $memberGroups, explode( ",", IPSText::cleanPermString( $t['mgroup_others'] ) ) );
			}
			
			foreach( $memberGroups as $groupId )
			{
				if( $this->caches['group_cache'][ $groupId ]['g_is_supmod'] == 1 )
				{
					$mod = true;
					break;
				}
			}
			
			if( !$mod )
			{
				if ( count($this->cache->getCache('moderators')) )
				{
					$other_mgroups = array();
					
					if( $t['mgroup_others'] )
					{
						$other_mgroups = explode( ",", IPSText::cleanPermString( $t['mgroup_others'] ) );
					}
					
					foreach( $this->cache->getCache('moderators') as $moderators )
					{
						if ( ( $moderators['member_id'] AND $moderators['member_id'] == $t['member_id'] ) OR $moderators['group_id'] == $t['member_group_id'] )
						{
							if( $moderators['forum_id'] == $t['forum_id'] )
							{
								$mod = true;
							}
						}
						else if( count($other_mgroups) AND in_array( $moderators['group_id'], $other_mgroups ) )
						{
							if( $moderators['forum_id'] == $t['forum_id'] )
							{
								$mod = true;
							}
						}
					}
				}
			}
			
			if( !$mod )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Does a guest have access to this forum?
	 *
	 * @param	int			Forum ID
	 * @param	int			Override guest group with another (Facebook bot, spider search engine bots)
	 * @return	boolean
	 * @author	Matt
	 */
	public function guestCanSeeTopic( $forumId=0, $groupOverride=0 )
	{
		$forumId	= ( $forumId ) ? $forumId : intval( $this->request['f'] );
		$gid		= ( $groupOverride ) ? $groupOverride : $this->settings['guest_group'];
		$perms		= explode( ',', IPSText::cleanPermString( $this->caches['group_cache'][ $gid ]['g_perm_id'] ) );

		if ( $forumId )
		{
			$forum = $this->forum_by_id[ $forumId ];
			
			if ( strstr( $forum['perm_read'], '*' ) )
			{
				return true;
			}
			else
			{
				foreach( $perms as $_perm )
				{
					if ( strstr( ',' . $forum['perm_read'] . ',', ',' . $_perm . ',' ) )
					{
						return true;
					}
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Recache the user's watched forums
	 *
	 * @param	int			Member ID
	 * @return	boolean
	 * @author	Matt
	 */
	public function recacheWatchedForums( $memberID )
	{
		/* INIT */
		$final		= array();
		$memberID	= intval( $memberID );

		/* Get forums the member follows */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'forums' );
		$forums	= $_like->getDataByMemberIdAndArea( $memberID );
		$forums	= is_array($forums) ? array_keys( $forums ) : array();
		
		foreach( $forums as $forum )
		{
			if ( $this->registry->permissions->check( 'view', $this->forum_by_id[ $forum ] ) === TRUE )
			{
				$final[]	= $forum;
			}
		}

		IPSMember::packMemberCache( $memberID, array( 'watchedForums' => $final ) );
		
		return TRUE;
	}
	
	/**
	 * Build (and optionally save) the last X topic IDs from a forum
	 *
	 * @param	int			Forum ID to save
	 * @param	boolean		TRUE = SAVE, FALSE = Return Array of IDs
	 * @param	int			No. topics to save ( Default is 5 )
	 * @return	array 		Array of topic IDs 
	 */
	public function buildLastXTopicIds( $forumID, $save=TRUE, $limit=5 )
	{
		$ids   = array();
		$forum = $this->forum_by_id[ $forumID ];
		
		if ( ! $forumID )
		{
			return array();
		}
		
		//-----------------------------------------
		// Make sure this forum has topics...
		// This causes a problem where the first post
		// in a new forum won't get added as latest topic.
		// Didn't want to try to rejig the process as it's
		// fine otherwise, so just commenting this out.
		//-----------------------------------------
		
		/*if ( ! $forum['topics'] )
		{
			return array();
		}*/
		
		//-----------------------------------------
		// Grab the topics
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'tid, start_date',
								 'from'   => 'topics',
								 'where'  => 'forum_id=' . $forumID . ' AND ' . $this->fetchTopicHiddenQuery( array( 'visible' ), '' ),
								 'order'  => 'start_date DESC',
								 'limit'  => array( 0, $limit ) ) );
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$ids[ $row['tid'] ] = $row['start_date'];
		}
		
		if ( $save === TRUE )
		{
			if ( count( $ids ) )
			{
				$this->DB->update( 'forums', array( 'last_x_topic_ids' => $this->lastXFreeze( $ids ) ), 'id=' . $forumID );
			}
		}
		
		return $ids;
	}
	
	/**
	 * Format last X topics string for saving
	 *
	 * @param	array 		Array of IDs
	 * @return	string		Frozen string format
	 */
	public function lastXFreeze( $ids=array() )
	{
		return serialize( $ids );
	}
	
	/**
	 * Format last X topics string for using
	 *
	 * @param	array 		Array of IDs
	 * @return	string		Thawed string format
	 */
	public function lastXThaw( $idString=array() )
	{
		return unserialize( $idString );
	}
	
	/**
	 * getHasUnread
	 * Get the next unread topic
	 * @param int forums ID
	 */
	public function getHasUnread( $forumId )
	{
		$latestStamp = intval( $this->registry->classItemMarking->fetchTimeLastMarked( array('forumID' => $forumId ), 'forums' ) );
		$forumData   = $this->getForumById( $forumId );
		$lastPost    = $forumData['last_post'];
		
		return ( $latestStamp < $lastPost ) ? true : false;
	}
	
	/**
	 * Post process VNC tids
	 * @param	array	tid => array( tid,forum_id)
	 * @return	array	cleaned tids
	 */
	public function postProcessVncTids( $rtids, $limit=array(), $maxHit=1000 )
	{
		$fCache = array( 'marked' => array(), 'lastStamp' => array() );
		$tids   = array();
		
		if ( ! is_array( $rtids ) OR ! count( $rtids ) )
		{
			return array();
		}
		
		foreach( $rtids as $tid => $data )
		{
			if ( ! isset( $fCache['canView'][ $data['forum_id'] ] ) )
			{
				$read = $this->registry->permissions->check( 'view', $this->getForumById( $data['forum_id'] ) );
				$view = $this->forumsCheckAccess( $data['forum_id'], 0, 'forum', array(), true );
				
				$fCache['canView'][ $data['forum_id'] ] = ( $read && $view ) ? true : false;
			}
			
			/* Permission to view? */
			if ( ! $fCache['canView'][ $data['forum_id'] ] )
			{
				continue;
			}		
			
			if ( IPSSearchRegistry::get('in.period') == 'unread' )
			{
				/* Avoid repeated calls to classItemMarking */
				if ( ! isset( $fCache['marked'][ $data['forum_id'] ] ) )
				{
					$fCache['marked'][ $data['forum_id'] ] = intval( $this->registry->classItemMarking->fetchTimeLastMarked( array('forumID' => $data['forum_id'] ), 'forums' ) );
				}
				
				/* May as well cache this too */
				if ( ! isset( $fCache['lastStamp'][ $data['forum_id'] ] ) )
				{
					$_f = $this->getForumById( $data['forum_id'] );
					$fCache['lastStamp'][ $data['forum_id'] ] = intval( $_f['last_post'] );
				}
				
				$lastStamp = ( isset( $fCache['lastStamp'][ $data['forum_id'] ] ) ) ? $fCache['lastStamp'][ $data['forum_id'] ] : 0;
				$lastRead  = ( isset( $fCache['marked'][ $data['forum_id'] ] ) )    ? $fCache['marked'][ $data['forum_id'] ] : 0;
				
				/* Lets get jiggy, etc */
				if ( $lastStamp && ( $lastStamp <= $lastRead ) )
				{
					continue;
				}
				else
				{
					if ( ! $this->registry->classItemMarking->isRead( array('forumID' => $data['forum_id'], 'itemID' => $tid, 'itemLastUpdate' => $data['last_post'] ), 'forums' ) )
					{
						$tids[ $tid ] = $tid;
					}
				}
			}
			else
			{
				$tids[ $tid ] = $tid;
			}
		}
	
		
		/* Count what's left */
		$count = count( $tids );
		
		/* Simulate limit */
		if ( isset( $limit[0] ) )
		{
			$tids = array_slice( $tids, intval( $limit[0] ), intval( $limit[1] ) );
		}
		
		/* return */
		return array( 'count' => $count, 'tids' => $tids );
	}
	
	/**
	 * Hook: Facebook sidebar block
	 *
	 * @return	string		HTML
	 */
	public function hooks_facebookActivity()
	{
		return $this->registry->output->getTemplate( 'boards' )->hookFacebookActivity(); 
	}
	
	/**
	 * Hook: Facebook sidebar block
	 *
	 * @return	string		HTML
	 */
	public function hooks_facebookLike()
	{
		return $this->registry->output->getTemplate( 'topic' )->hookFacebookLike(); 
	}
	
	/**
	 * Hook: Recent topics
	 * Moved here so we can update with out requiring global hook changes
	 *
	 * @param	int		Number of topics
	 * @param	bool	Whether to output directly (true) or return array of topics (false)
	 * @return	mixed	String if $output is true, array if $output is false
	 */
	public function hooks_recentTopics( $topicCount=5, $output=true )
	{
		/* INIT */
		$topicIDs	= array();
		$topic_rows = array();
		$timesUsed	= array();
		$bvnp       = explode( ',', $this->settings['vnp_block_forums'] );
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic', 'public_forums' ), 'forums' );
		
		/* Grab last X data */
		foreach( $this->forum_by_id as $forumID => $forumData )
		{
			if ( ! $forumData['can_view_others'] AND ! $this->_memberData['is_mod'] )
			{
				continue;
			}
			
			if ( $forumData['password'] != '' )
			{
				continue;
			}
			
			if ( ! $this->registry->permissions->check( 'read', $forumData ) )
			{
				continue;
			}
			
			if ( is_array( $bvnp ) AND count( $bvnp ) )
			{
				if ( in_array( $forumID, $bvnp ) )
				{
					continue;
				}
			}

			/* Still here? */
			$_topics = $this->lastXThaw( $forumData['last_x_topic_ids'] );
			
			if ( is_array( $_topics ) )
			{
				foreach( $_topics as $id => $time )
				{
					if( in_array( $time, $timesUsed ) )
					{
						while( in_array( $time, $timesUsed ) )
						{
							$time +=1;
						}
					}
					
					$timesUsed[]       = $time;
					$topicIDs[ $time ] = $id;
				}
			}
		}
		
		$timesUsed	= array();
		
		if ( is_array( $topicIDs ) && count( $topicIDs ) )
		{
			krsort( $topicIDs );
			
			/* We get up to double in case some of the latest are moved_to links - we do another array_slice afterwards to limit to right limit */
			$_topics	= array_slice( $topicIDs, 0, $topicCount * 2 );
			
			if ( is_array( $_topics ) && count( $_topics ) )
			{
				/* Query Topics */
				$this->registry->DB()->build( array( 
														'select'   => 't.tid, t.title as topic_title, t.title_seo, t.start_date, t.starter_id, t.starter_name, t.moved_to, t.views, t.posts',
														'from'     => array( 'topics' => 't' ),
														'where'    => 't.tid IN (' . implode( ',', array_values( $_topics ) ) . ')',
														'add_join' => array(
																			array(
																					'select'	=> 'm.*',
																					'from'		=> array( 'members' => 'm' ),
																					'where'		=> 'm.member_id=t.starter_id',
																					'type'		=> 'left',
																				),
																			array(
																					'select'	=> 'pp.*',
																					'from'		=> array( 'profile_portal' => 'pp' ),
																					'where'		=> 'm.member_id=pp.pp_member_id',
																					'type'		=> 'left',
																				),
																		)
											)	 );

				$outer = $this->registry->DB()->execute();

				while( $r = $this->registry->DB()->fetch( $outer ) )
				{
					if( !empty($r['moved_to']) )
					{
						continue;
					}

					$time	= $r['start_date'];
					
					if( in_array( $time, $timesUsed ) )
					{
						while( in_array( $time, $timesUsed ) )
						{
							$time +=1;
						}
					}
					
					$timesUsed[]          = $time;
					$topics_rows[ $time ] = IPSMember::buildDisplayData( $r );
				}
				
				/* Got any results? */
				if ( count($topics_rows) )
				{
					krsort( $topics_rows );
					$topics_rows = array_slice( $topics_rows, 0, $topicCount );
				}
			}
		}
		
		if( $output )
		{
			return $this->registry->output->getTemplate('boards')->hookRecentTopics( $topics_rows );
		}
		else
		{
			return $topics_rows;
		}
	}
}