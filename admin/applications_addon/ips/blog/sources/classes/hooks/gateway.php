<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Subscriptions Hooks Gateway "Handler"
 * Owner: Matt "Oh Lord, why did I get assigned this?" Mecham
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 4 $
 */
class blog_hookGateway
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
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData;
	public $cache;
	public $caches;
	
	/**
	 * Number of recent entries to load
	 *
	 * @var		$recentEntries
	 */
	public $recentEntries = 5;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	function __construct( ipsRegistry $registry )
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
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_blog' ), 'blog' );
		
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
		}

		/* Build the permissions */
		$this->memberData = $this->registry->getClass('blogFunctions')->buildPerms( $this->memberData );
	}
	
    /**
     * Checks if a user is allowed to see entries in a blog
     * 
     * @param	array		$blogData		Blog data
     * @return	@e boolean
     */
	public function userCanViewBlog( $blogData )
	{
		# Missing a proper blog id?
		if ( empty($blogData['blog_id']) )
		{
			return FALSE;
		}
		
		# Blog is disabled?
		if ( $blogData['blog_disabled'] && ! $this->memberData['g_is_supmod'] && ! $this->memberData['_blogmod']['moderate_can_disable'] )
		{
			return FALSE;
		}
		
		# Member check
		if ( $this->memberData['member_id'] )
		{
			# Owner only?
			if ( $blogData['blog_owner_only'] && $blogData['member_id'] != $this->memberData['member_id'] )
			{
				return FALSE;
			}
			
			# Got a list of authorized users?
			$blogData['blog_authorized_users'] = IPSText::cleanPermString( trim($blogData['blog_authorized_users']) );
			
			if ( ! empty($blogData['blog_authorized_users']) )
			{
				$_check = explode( ',', $blogData['blog_authorized_users'] );
				
				if ( ! in_array( $this->memberData['member_id'], $_check ) )
				{
					return FALSE;
				}
			}
		}
		else
		{
			# Owner only or no guests allowed?
			if ( $blogData['blog_owner_only'] || empty($blogData['blog_allowguests'])  )
			{
				return FALSE;
			}
		}
		
		/* Still here? We can view it then.. */
		return TRUE;
	}
    
    /**
     * Sorts entries by their publish date
     * 
     * @param	array		$a		First entry data
     * @param	array		$b		Second entry data
     * @return	@e integer
     */
    protected function sortEntriesByDate( $a, $b )
	{
		if ( $a['entry_date'] == $b['entry_date'] )
		{
			return 0;
		}
		
		return ( $a['entry_date'] > $b['entry_date'] ) ? -1 : 1;
    }
    
    /**
     * Shows board index recent entries
     * 
     * @return	@e string
     */
    public function recentEntries()
    {
    	/* App switched on? */
    	if ( ! IPSLib::appIsInstalled( 'blog' ) )
    	{
    		return '';
    	}
    	
    	/* Check to handle a version mismatch */
    	if( ! method_exists( $this->registry->output->getTemplate( 'blog_portal' ), 'recentEntries' ) )
    	{
	    	return '';
    	}
    	
    	/* Unserialize blog permissions */
		if( ! $this->memberData['g_blog_settings'] )
		{
			return '';
		}
		
		if( IPSLib::isSerialized( $this->memberData['g_blog_settings'] ) )
		{
			$this->memberData['g_blog_settings'] = unserialize( $this->memberData['g_blog_settings'] );
		}
		
		/* Check permission */
		if( ! $this->memberData['g_blog_settings']['g_blog_allowview'] )
		{
			return '';
		}
		
		/* Init vars */
		$output  = '';
		$blogs   = array();
		$entries = array();
		$members = array();
		
		/* Got a predefined blogs value? */
		if ( defined( 'BLOG_RENTRIES_BLOG_ID' ) && IPSText::cleanPermString( BLOG_RENTRIES_BLOG_ID ) )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_blogs',
									 'where'  => 'blog_id IN (' .  IPSText::cleanPermString( BLOG_RENTRIES_BLOG_ID ) . ')'
							 )		);
			$outer = $this->DB->execute();
			
			while( $tb = $this->DB->fetch( $outer ) )
			{
				/* Can we really view this blog? */
				if ( $this->userCanViewBlog( $tb ) )
				{
					$blogs[ $tb['blog_id'] ] = $tb;
				}
			}
			
			/* Got blog to load entries from? */
			if ( count($blogs) )
			{
				$this->DB->build( array( 'select'	=> 'entry_id, entry_last_update, entry_name, blog_id, entry_name_seo, entry_author_id, entry_date',
										 'from'		=> 'blog_entries',
										 'where'	=> "blog_id IN (" .  implode( ',', array_keys($blogs) ) . ") AND entry_status='published'",
										 'limit'	=> array( 0, $this->recentEntries ),
										 'order'	=> 'entry_date DESC'
								 )		);
				$this->DB->execute();
				
				while( $entry = $this->DB->fetch() )
				{
					$entries[ $entry['entry_id'] ] = array_merge( $blogs[ $entry['blog_id'] ], $entry );
					
					if ( ! empty($entry['entry_author_id']) )
					{
						$mids[ $entry['entry_author_id'] ] = $entry['entry_author_id'];
					}
				}
			}
		}
		/* We're loading the entries from the stats cache then */
		else
		{
			# Get stats cache if not available, we need the ids in it
			if ( ! isset($this->caches['blog_stats']) )
			{
				$this->caches['blog_stats'] = $this->cache->getCache('blog_stats');
			}
			
			/* Do we actually have cached IDs to load entries? */
			if ( is_array($this->caches['blog_stats']['recent_entries']) && count($this->caches['blog_stats']['recent_entries']) )
			{
				$_entries = array();
				$_blogIds = array();
				
				$this->DB->build( array( 'select'	=> 'entry_id, entry_last_update, entry_name, blog_id, entry_name_seo, entry_author_id, entry_date',
										 'from'		=> 'blog_entries',
										 'where'	=> "entry_id IN (" .  implode( ',', array_keys($this->caches['blog_stats']['recent_entries']) ) . ")"
								 )		);
				$this->DB->execute();
				
				while( $entry = $this->DB->fetch() )
				{
					$_entries[ $entry['entry_id'] ] = $entry;
					$_blogIds[ $entry['blog_id'] ]  = $entry['blog_id'];
				}
				
				/* Now load blogs */
				if ( count($_entries) )
				{
					$this->DB->build( array( 'select' => '*',
											 'from'   => 'blog_blogs',
											 'where'  => 'blog_id IN (' .  implode( ',', $_blogIds ) . ')'
									 )		);
					$outer = $this->DB->execute();
					
					while( $tb = $this->DB->fetch( $outer ) )
					{
						/* Can we really view this blog? */
						if ( $this->userCanViewBlog( $tb ) )
						{
							$blogs[ $tb['blog_id'] ] = $tb;
						}
					}
					
					uasort( $_entries, array( $this, 'sortEntriesByDate' ) );
					
					/* Permissions check and final array */
					foreach( $_entries as $cid => $cdata )
					{
						if ( isset($blogs[ $cdata['blog_id'] ]) )
						{
							$entries[ $cid ] = array_merge( $blogs[ $cdata['blog_id'] ], $cdata );
							
							/* Entry is good to view? Add member id */
							if ( ! empty($cdata['entry_author_id']) )
							{
								$mids[ $cdata['entry_author_id'] ] = $cdata['entry_author_id'];
							}
							
							if ( count($entries) == $this->recentEntries )
							{
								break;
							}
						}
					}
				}
			}
		}
		
		/* So.. we got some good entries to show? */
		if( count( $entries ) )
		{
			/* Load entries members data */
			if ( count($mids) )
			{
				$members = IPSMember::load( $mids, 'all' );
				
				if ( count( $members ) )
				{
					foreach( $entries as $eid => $edata )
					{
						if ( $edata['entry_author_id'] && isset($members[ $edata['entry_author_id'] ]) )
						{
							$entries[ $eid ] = array_merge( $entries[ $eid ], $members[ $edata['entry_author_id'] ] );
						}
					}
				}
			}
			
			foreach( $entries as $eid => $entry )
			{
				$entry                = IPSMember::buildDisplayData( $entry, array( 'reputation' => 0, 'warn' => 0 ) );
				$entry['_entry_date'] = $this->registry->getClass('class_localization')->getDate( $entry['entry_date'], 'SHORT2' );
				
				$entry['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $entry['blog_id'], 'itemID' => $entry['entry_id'] ), 'blog' );
				
				if( $entry['entry_last_update'] > $entry['_lastRead'] )
				{
					$entry['newpost'] = true;
				}
				else
				{
					$entry['newpost'] = false;
				}
				
				$entries[ $eid ] = $entry;
			}
			
			$output = $this->registry->output->getTemplate('blog_portal')->recentEntries( $entries );
		}
		
		return $output;
	}
	
    /**
     * Shows a box called "Blogged This"
     *
     * @access	public
     * @return	string		HTML
     */
    public function showBloggedThis()
    {
    	/* App switched on? */
    	if ( ! IPSLib::appIsInstalled( 'blog' ) )
    	{
    		return '';
    	}
    	
    	$entries = array();
    	$eids    = array();

    	/* Got a topic? */
    	if ( $this->registry->getClass('class_forums')->topic_cache['tid'] )
    	{
    		/* Grab IDs first */
    		$this->DB->build( array( 'select' => '*',
    								 'from'	  => 'blog_this',
    								 'where'  => 'bt_app=\'forums\' AND bt_id1=' . intval( $this->registry->getClass('class_forums')->topic_cache['tid'] ) ) );
    								 
    		$this->DB->execute();
    		
    		while( $row = $this->DB->fetch() )
    		{
    			$eids[ $row['bt_entry_id'] ] = $row['bt_entry_id'];
    		}
    		
    		/* Now do the main query */
    		if ( count( $eids ) )
    		{
    			/* Load parsing lib */
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
				$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry, null ) );
				
				/* Get data */
    			$this->DB->build( array( 'select'	=> 'e.*',
										 'from'		=> array( 'blog_entries' => 'e' ),
										 'where'	=> 'e.entry_id IN(' . implode( ',', array_keys( $eids ) ) . ')',
										 'order'    => 'e.entry_date DESC',
										 'limit'    => array( 0, 10 ),
										 'add_join'	=> array(array(  'select'   => 'b.*',
																	 'from'     => array( 'blog_blogs' => 'b' ),
																	 'where'    => 'b.blog_id=e.blog_id',
																	 'type'     => 'left' ),
										 					 array( 'select'	=> 'bl.*',
																	 'from'	    => array( 'blog_lastinfo' =>'bl' ),
																	 'where'    => 'b.blog_id=bl.blog_id',
																	 'type'	    => 'left' ), 
															 array(  'select'	=> 'm.*',
																	 'from'	    => array( 'members' => 'm' ),
																	 'where'    => 'm.member_id=e.entry_author_id',
																	 'type'	    => 'left' ),
															 array(  'select'   => 'pp.*',
																	 'from'	    => array( 'profile_portal' => 'pp' ),
																	 'where'    => 'pp.pp_member_id=m.member_id',
																	 'type'	    => 'left' ) ) ) );
				$o = $this->DB->execute();
				
				while( $row = $this->DB->fetch( $o ) )
				{
					if ( ! $row['blog_disabled'] AND $row['entry_status'] != 'draft' )
					{
						$row						 = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
						$row                         = $this->registry->getClass('blogParsing')->parseEntry( $row, 1, array( 'entryParse' => 1, 'noPositionInc' => 1 ), $row );
						$entries[ $row['blog_id'] ]  = $row;
					}
				}
				
				if ( !empty( $entries ) )
				{
					return $this->registry->getClass('output')->getTemplate('blog_hooks')->topics_blogThis( $entries );
    			}
    		}
    	}
    	
    	return '';
    }
}