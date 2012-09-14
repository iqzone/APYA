<?php
/**
 * @file		plugin_draftentries.php 	Moderator control panel plugin: show draft blog entries
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2/23/2011
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_blog_draftentries
 * @brief		Moderator control panel plugin: show draft blog entries
 */
class plugin_blog_draftentries
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		//-----------------------------------------
		// Other stuff
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'blog' );
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'unapproved_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'draftentries';
	}

	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$this->canView( $permissions ) )
		{
			return '';
		}
		
		/* Init vars */
		$st		 = intval($this->request['st']);
		$each	 = 25;
		$results = array();
		$_where  = array();
		
		//----------------------------------
		// Set SQL filters
		//----------------------------------
		
		$_where[]	= "b.entry_status='draft'"; # All drafts please!
		
		if( !$this->memberData['g_is_supmod'] )
		{
			if( !$this->memberData['_blogmod']['moderate_can_disable'] )
			{
				$_where[]	= 'bl.blog_disabled=0';
			}

			if( !$this->memberData['_blogmod']['moderate_can_view_private'] )
			{
				$_where[]	= '(bl.blog_owner_only=0 OR b.entry_author_id=' . $this->memberData['member_id'] . ')';
				$_where[]	= '(bl.blog_authorized_users ' . $this->DB->buildIsNull() . " OR bl.blog_authorized_users='' OR b.entry_author_id=" . $this->memberData['member_id'] . " OR bl.blog_authorized_users LIKE '%," . $this->memberData['member_id'] . ",%')";
			}
		}
		
		
		# Get count
		$count = $this->DB->buildAndFetch( array('select'	=> 'COUNT(*) as total',
												 'from'		=> array( 'blog_entries' => 'b' ),
				 								 'where'	=> implode( ' AND ', $_where ),
												 'add_join'	=> array( array( 'from'   => array( 'blog_blogs' => 'bl' ),
																			 'where'  => "bl.blog_id=b.blog_id",
																			 'type'   => 'left' ) )
										  )		 );
		
		//-----------------------------------------
		// Page links
		//-----------------------------------------
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=blog&amp;tab=draftentries",
															)		);
		
		/* Get entries pending approval if we got any */
		if ( $count['total'] )
		{
			$this->DB->build( array( 'select'	=> "b.*",
									 'from'		=> array( 'blog_entries' => 'b' ),
	 								 'where'	=> implode( ' AND ', $_where ),
				 					 'limit'	=> array( $st, $each ),
									 'add_join'	=> array(
			 											array( 'select'	=> 'bl.*',
																'from'	=> array( 'blog_blogs' => 'bl' ),
																'where'	=> "bl.blog_id=b.blog_id",
																'type'	=> 'left'
															),
														array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=b.entry_author_id',
																'type'		=> 'left',
															),
														array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'm.member_id=pp.pp_member_id',
																'type'		=> 'left',
															),
														)
							)		);
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$row['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $row['blog_id'], 'itemID' => $row['entry_id'] ), 'blog' );
				
				if( $row['entry_num_comments'] AND $row['entry_last_comment_date'] > $row['_lastRead'] )
				{
					$row['newpost'] = true;
				}
				else
				{
					$row['newpost'] = false;
				}
				
				$row['app']                 = 'blog';
				$row['content']             = $row['entry_short'];
				$row['content_title']       = $row['entry_name'];
				$row['updated']             = $row['entry_date'];
				$row['type_2']              = 'entry';
				$row['type_id_2']           = $row['entry_id'];
				$row['member_id']           = $row['entry_author_id'];
				
				/* Trick CSS */
				$row['entry_status']		= 'published';
				
				$results[] = IPSMember::buildDisplayData( $row );
			}
		}
		
		return $this->registry->getClass('output')->getTemplate('blog_portal')->moderatorPanel( $results, $pages );
	}
}