<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Blog Library Functions
 * Last Updated: $LastChangedDate: 2012-01-06 11:19:13 -0500 (Fri, 06 Jan 2012) $
 * </pre>
 *
 * @author		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 13 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class blogFunctions
{
	/**
	* Registry Object Shortcuts
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
	 * Cache for the number of blogs for each member
	 *
	 * @var	array
	 */
	protected $blogCountCache;
	
	/**
	 * Array of blog data
	 *
	 * @var array
	 */
	public $blog;
	
	/**
	 * Array of cached blogs
	 *
	 * @var array
	 */	
	protected $_blogs  = array();
	
	/**
	 * BBCode parser shortcut
	 * 
	 * @var		$bbclass
	 */
	protected $bbclass = null;
	
	/**
	 * Setup registry classes
	 *
	 * @param  ipsRegistry	$registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry		=   $registry;
		$this->DB			=   $this->registry->DB();
		$this->settings		=&  $this->registry->fetchSettings();
		$this->request		=&  $this->registry->fetchRequest();
		$this->lang			=   $this->registry->getClass('class_localization');
		$this->member		=   $this->registry->member();
		$this->cache		=   $this->registry->cache();
		$this->caches		=&  $this->registry->cache()->fetchCaches();
		$this->memberData	=&  $this->registry->member()->fetchMemberData();
		$this->memberData   =   $this->buildPerms($this->memberData);
		
		/* Ensure we have the relevant caches */
		if ( isset($this->caches['blog_stats']) AND ( !isset($this->caches['blog_stats']['featured']) OR !isset($this->caches['blog_stats']['recent_entries']) OR !isset($this->caches['blog_stats']['fp_entries']) ) )
		{
			$this->rebuildStats();
		}
	}
	
	/**
	 * Load a Blog
	 *
	 * @param  mixed	Either INT or array of ids
	 * @param  mixed	Member data or null to use $this->memberData
	 * @return array
	 */
	public function loadBlog( $blog_id="", $member=null )
	{
		/* INIT */
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		$blogs      = array();
		$single     = true;
		
		/* Do we already have a blog loaded? */
		if ( ! is_array( $blog_id ) )
		{
			if ( isset( $this->_blogs[ $blog_id ]['blog_id'] ) )
			{
				return $this->_blogs[ $blog_id ];
			}
			else
			{
				/* Check ID */
				$blog_id = intval( $blog_id );
				
				if ( ! $blog_id )
				{ 
					$this->registry->output->showError( 'incorrect_use', 106174, null, null, 404 );
				}
				
				$single = true;
				$where  = "b.blog_id = {$blog_id}";
			}
		}
		else
		{
			if ( count( $blog_id ) == 1 && isset($this->_blogs[$blog_id[0]]['blog_id']))
			{
				return array($this->_blogs[$blog_id[0]]['blog_id'] => $this->_blogs[$blog_id[0]]);
			}
			else
			{
				$single = false;
				$where = 'b.blog_id IN (' . implode( ',', $blog_id ) . ')';
			}
		}
		
		$this->DB->build( array(
									'select'	=> "b.*, b.blog_id as my_blog_id",
									'from'		=> array('blog_blogs' => 'b'),
									'where'		=> $where,
									'add_join'	=> array( 
															array( 
																	'select'	=> 'bl.*',
																	'from'		=> array( 'blog_lastinfo' => 'bl' ),
																	'where'		=> "bl.blog_id=b.blog_id",
																	'type'		=> 'left',
																) 
														)	) 
						);
		
		
		$i = $this->DB->execute();
		
		while( $blog = $this->DB->fetch( $i ) )
		{
				/* Reset our ID */
				$blog['blog_id'] = $blog['my_blog_id'];
				
				/* Unpack data */
				$blogs[ $blog['blog_id'] ]			= $this->buildBlogData( $blog, $memberData );
				$this->_blogs[ $blog['blog_id'] ]	= $blogs[ $blog['blog_id'] ];
		}
		
		/* Found a blog? */
		if ( $single !== false )
		{
			if( !isset($blogs[ $blog_id ]) )
			{
				return false;
			}
			else
			{
				return $blogs[ $blog_id ];
			}
		}
		else
		{
			return $blogs;
		}
	}
	
	/**
	 * Build blog data
	 *
	 * @param	array		Blog data
	 * @param	array		[Member data array, uses $this->memberData if none]
	 * @param	array		Formatted data
	 */
	public function buildBlogData( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if ( ! $blog['blog_id'] )
		{
			return $blog;
		}
		
		$blog['blog_settings'] = IPSLib::isSerialized($blog['blog_settings']) ? unserialize( $blog['blog_settings'] ) : $blog['blog_settings'];

		/*	We overwrite the settings with whatever the global admin allowed. */
		$blog['blog_allowguests']					 = $memberData['g_blog_allowview']		    ? $blog['blog_allowguests']					   : 0;
		$blog['blog_settings']['allowguestcomments'] = $memberData['g_blog_allowcomment']		? $blog['blog_settings']['allowguestcomments'] : 0;
		$blog['blog_settings']['allowrss']			 = $this->settings['blog_allow_rss']		? $blog['blog_settings']['allowrss']		   : 0;
		$blog['blog_settings']['allowtrackback']	 = $this->settings['blog_allow_trackback']	? $blog['blog_settings']['allowtrackback']	   : 0;
		$blog['blog_settings']['entriesperpage']	 = $this->settings['blog_entries_perpage']	? $blog['blog_settings']['entriesperpage']	   : $this->settings['blog_entries_perpage'];
		$blog['blog_settings']['commentsperpage']	 = $this->settings['blog_comments_perpage'] ? $blog['blog_settings']['commentsperpage']	   : $this->settings['blog_comments_perpage'];
		$blog['blog_settings']['cblockwidth']		 = !empty($blog['blog_settings']['cblockwidth']) ? $blog['blog_settings']['cblockwidth']		   : "250";
		$blog['blog_settings']['editors']			 = !empty($blog['blog_settings']['editors']) ? $blog['blog_settings']['editors']			   : array();
		$blog['blog_settings']['blockslocation']	 = !empty($blog['blog_settings']['blockslocation']) ? $blog['blog_settings']['blockslocation']	   : 'right';
		
		/* Self-check */
		if ( ( ! $blog['blog_editors'] ) AND ( $blog['blog_settings']['editors'] AND is_array( $blog['blog_settings']['editors'] ) AND count( $blog['blog_settings']['editors'] ) ) )
		{
			$this->updateEditorMappings( $blog, $blog['blog_settings']['editors'] );
		}
		
		$blog['_is_editor'] = in_array( $memberData['member_id'], $blog['blog_settings']['editors'] );

		$blog['allow_entry'] = ( $this->ownsBlog( $blog, $memberData ) || in_array( $memberData['member_id'], $blog['blog_settings']['editors'] ) ) ? 1 : 0;

		/* Calculate the rating */
		if( $this->settings['blog_enable_rating'] )
		{
			$blog['blog_rating'] = $blog['blog_rating_count']>$this->settings['blog_rating_treshhold'] ? ceil( $blog['blog_rating_total'] / $blog['blog_rating_count'] ) : 0;
		}
		
		/* Some blogs appear not to have a view level set, possible upgrade bug. Manifests itself by not showing 'feature entry', etc */
		if ( isset( $blog['blog_view_level'] ) AND ! $blog['blog_view_level'] )
		{
			$blog['_level'] = 'public';
						
			$this->DB->update( 'blog_blogs', array( 'blog_view_level' => $blog['_level'] ), 'blog_id=' . $blog['blog_id'] );
		}
		else
		{
			$blog['_level'] = $blog['blog_view_level'];
		}
		
		/* Type */
		$blog['_type'] = ( isset( $memberData['has_blog'][ $blog['blog_id'] ]['_type'] ) ) ? $memberData['has_blog'][ $blog['blog_id'] ]['_type'] : 'owner';
		
		/* Categories */
		if ( $blog['blog_categories'] )
		{
			$blog['_categories'] = unserialize( $blog['blog_categories'] );
		}
		
		/* Last X comments */
		if ( $blog['blog_last_comment_20'] )
		{
			$blog['blog_last_comment_20'] = unserialize( $blog['blog_last_comment_20'] );
		}
		
		/* SEO Name */
		$blog['blog_seo_name'] = $this->fetchBlogSeoName( $blog );
		
		if( $this->settings['blog_enable_rating'] )
		{
			$blog['_blog_rate_int'] = $blog['blog_rating_count'] ? round( $blog['blog_rating_total'] / $blog['blog_rating_count'], 0 ) : 0;
			
			$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
		}
		
		return $blog;
	}
	
	/**
	 * Reset blog
	 * Simply wipes data from $this->blog
	 *
	 * @param	int		[Optional blog ID, will use $this->blog otherwise
	 */
	public function resetBlog( $blog_id=0 )
	{
		if ( $blog_id )
		{
			$this->_blogs[ $blog_id ] = array();
		}
		else
		{
			$this->blog = array();
		}
	}
	
	/**
	 * Set active blog - the one the user is viewing
	 * Populates $this->blog
	 *
	 * @param	array 		Blog data
	 * @param	mixed		[Either blog ID OR array of blog data ]
	 * @return	bool
	 */
	public function setActiveBlog( $blog, $silent_error=0 )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$blog = ( is_array( $blog ) AND ! empty( $blog['blog_id'] ) ) ? $blog : $this->loadBlog( $blog );
		
		if ( ! $this->registry->isClassLoaded( 'output' ) )
		{
			$silent_error = 1;
		}
		
		if( empty( $blog ) )
		{
			if( $silent_error )
			{
				$this->error = "Blog not found";
				return false;
			}
			else
			{
				$this->registry->output->showError( 'incorrect_use', 106175, null, null, 404 );
			}
		}
		
		//-----------------------------------------
		// Is the blog disabled?
		//-----------------------------------------
		
		if( $blog['blog_disabled'] && ! $this->memberData['g_is_supmod'] )
		{
			if( $silent_error )
			{
				$this->error = "The blog has been disabled";
				return false;
			}
			else
			{
				$this->registry->output->showError( 'blog_no_permission', 106178, null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Can we view?
		//-----------------------------------------
		
		$canView = FALSE;
		
		/* Are we the blog owner, or a moderator? */
		if ( $this->ownsBlog( $blog ) or $this->memberData['_blogmod']['moderate_can_view_private'] )
		{
			$canView = TRUE;
		}
		/* Nope, just some regular Joe */
		else
		{
			switch ( $blog['blog_view_level'] )
			{
				// Public
				case 'public':
					$canView = TRUE;
					break;
					
				// Friends Only
				case 'friends':
					$canView = IPSMember::checkFriendStatus( $blog['member_id'], 0, TRUE );
					break;
					
				// Private Club
				case 'privateclub':
					$canView = in_array( $this->memberData['member_id'], explode( ',', IPSText::cleanPermString( $blog['blog_authorized_users'] ) ) );
					break;
			}
		}
		
		if ( !$canView )
		{
			if( $silent_error )
			{
				$this->error = "Insufficient permissions";
				return false;
			}
			else
			{
				$this->registry->output->showError( 'blog_no_permission', 106176, null, null, 403 );
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------

		$this->blog = $blog;
		
		return true;
	}
	
	/**
	 * Get active blog
	 *
	 * @return	array		Array of data
	 */
	public function getActiveBlog()
	{
		if( empty($this->blog) && !empty($this->request['blogid']) )
		{
			$this->setActiveBlog($this->request['blogid']);
		}
		
		return $this->blog;
	}
	
	/**
	 * Moves a blog's entries / categories / tags to a new blog
	 * WARNING: No permission checks are performed in this function!!
	 *
	 * @param	int		"from" blog ID
	 * @param	int		"to" blog ID
	 */
	public function moveBlogContent( $blog_id, $to_id )
	{
		/* INIT */
		$attachments = array();
		$blog_id     = intval( $blog_id );
		$done        = false;
		$count       = 0;
		
		/* Little check */
		if ( ! $blog_id OR ! $to_id )
		{
			return false;
		}
				
		/* Update entries */
		$this->DB->update( 'blog_entries', array( 'blog_id' => $to_id ), 'blog_id=' . $blog_id );
		
		/* Update cats (meow) */
		$this->DB->update( 'blog_categories'      , array( 'category_blog_id' => $to_id ), "category_blog_id=" . $blog_id );
		$this->DB->update( 'blog_category_mapping', array( 'map_blog_id' => $to_id )     , "map_blog_id=" . $blog_id );
		
		/* Delete custom cblocks */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad($this->registry);
		$class_attach->type = 'blogcblock';
		$class_attach->init();

		/* Query custom content blocks */
		$this->DB->build( array( 
								'select' => 'a.cblock_id, b.cbcus_id',
								'from'   => "blog_cblocks a, {$this->DB->obj['sql_tbl_prefix']}blog_custom_cblocks b",
								'where'  => "a.cblock_ref_id=b.cbcus_id and a.cblock_type='custom' and a.blog_id=$blog_id"
						)	);
		$cbcusid = $this->DB->execute();
		
		/* Build an array of custom block ids */
		$remove_cusblocks = array();
		
		while( $cbcus = $this->DB->fetch( $cbcusid ) )
		{
			/* Add to the id array */
			$remove_cusblocks[] = $cbcus['cbcus_id'];
		}
		
		/* Delete the custom blocks */
		if( count( $remove_cusblocks ) > 0 )
		{
			/* Store attachments */
			$class_attach->bulkRemoveAttachment( $remove_cusblocks );
			
			$this->DB->delete( 'blog_custom_cblocks', 'cbcus_id IN ( ' . implode( ',', $remove_cusblocks ) . ')' );
		}
		
		/* Delete normal blocks */
		$this->DB->delete( 'blog_cblocks'     , "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_cblock_cache', "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_rsscache'    , "blog_id=" . $blog_id );
		
		/* Update data */
		$this->DB->update( 'blog_ratings'           , array( 'blog_id' => $to_id ), "blog_id=" . $blog_id );
		$this->DB->update( 'blog_rsscache'          , array( 'rsscache_refresh' => 1 ), "blog_id IN (0," . $blog_id . ")" ); // Set global and individual RSS to recache
		$this->DB->update( 'blog_trackback'         , array( 'blog_id' => $to_id ), "blog_id=" . $blog_id );
		$this->DB->update( 'blog_trackback_spamlogs', array( 'blog_id' => $to_id ), "blog_id=" . $blog_id );
		$this->DB->update( 'blog_updatepings'       , array( 'blog_id' => $to_id ), "blog_id=" . $blog_id );

		/* Move tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		classes_tags_bootstrap::run( 'blog', 'entries' )->moveTagsByParentId( $blog_id, $to_id );
		
		/* Rebuild group cache */
		$this->registry->cache()->rebuildCache( 'blog_gblogs', 'blog' );
		
		/* Rebuild */
		$this->rebuildBlog( $blog_id );
		$this->rebuildBlog( $to_id );
		
		/* Rebuild stats */
		$this->rebuildStats();
	}
	
	/**
	 * Remove a blog
	 * WARNING: No permission checks are performed in this function!!
	 *
	 * @param	int		Blog ID to remove
	 */
	public function removeBlog( $blog_id )
	{
		/* INIT */
		$attachments = array();
		$blog_id     = intval( $blog_id );
		$done        = false;
		$count       = 0;
		$last_id     = 0;
				
		/* loop through and delete */
		while( $done === false )
		{
			/* reset */
			$eids = array();
			
			/* Query blog entries */
			$this->DB->build( array( 'select' => 'entry_id',
									 'from'   => 'blog_entries',
									 'where'  => "blog_id=" . $blog_id . ' AND entry_id > ' . $last_id,
									 'limit'  => array( 0, 50 ) ) );
									 
			$eid = $this->DB->execute();
			
			/* Loop through and delete them */
			while( $entry = $this->DB->fetch( $eid ) )
			{
				/* Store attachments for deletion */
				if ( $entry['entry_has_attach'] )
				{
					$attachments[] = $entry['entry_id'];
				}
				
				$eids[]  = $entry['entry_id'];
				$last_id = $entry['entry_id'];
			}
			
			if ( count( $eids ) )
			{
				$this->deleteEntries( $eids );
			}
			else
			{
				$done = true;
			}
		}
		
		/* Delete entreis */
		$this->DB->delete( 'blog_entries', "blog_id=" . $blog_id );
		
		/* Delete cats (meow) */
		$this->DB->delete( 'blog_categories'      , "category_blog_id=" . $blog_id );
		$this->DB->delete( 'blog_category_mapping', "map_blog_id=" . $blog_id );
		
		/* Editor mappings */
		$this->DB->delete( 'blog_editors_map', "editor_blog_id=" . $blog_id );
		
		/* Reset the attach class for cblocks */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'blogcblock';
		$class_attach->init();

		/* Query custom content blocks */
		$this->DB->build( array( 'select' => 'a.cblock_id, b.cbcus_id',
								 'from'   => "blog_cblocks a, {$this->DB->obj['sql_tbl_prefix']}blog_custom_cblocks b",
								 'where'  => "a.cblock_ref_id=b.cbcus_id and a.cblock_type='custom' and a.blog_id=$blog_id"
						 )		);
		$cbcusid = $this->DB->execute();
		
		/* Build an array of custom block ids */
		$remove_cusblocks = array();
		
		while( $cbcus = $this->DB->fetch( $cbcusid ) )
		{
			/* Add to the id array */
			$remove_cusblocks[] = $cbcus['cbcus_id'];
		}
		
		/* Delete the custom blocks */
		if( count( $remove_cusblocks ) > 0 )
		{
			/* Store attachments */
			$class_attach->bulkRemoveAttachment( $remove_cusblocks );
			
			$this->DB->delete( 'blog_custom_cblocks', 'cbcus_id IN ( ' . implode( ',', $remove_cusblocks ) . ')' );
		}
		
		/* Delete normal blocks */
		$this->DB->delete( 'blog_cblocks', "blog_id=" . $blog_id );
		
		/* Delete other data */
		$this->DB->delete( 'blog_cblock_cache'      , "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_lastinfo'          , "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_ratings'           , "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_rsscache'          , "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_trackback'         , "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_trackback_spamlogs', "blog_id=" . $blog_id );
		$this->DB->delete( 'blog_updatepings'       , "blog_id=" . $blog_id );
		
		/* Update the member record? */
		$this->flagMyBlogsRecacheByBlogId( $blog_id );

		/* Delete the blog record */
		$this->DB->delete( 'blog_blogs', "blog_id=" . $blog_id );
		$this->DB->delete( 'permission_index', "app='blog' AND perm_type='blog' AND perm_type_id=" . $blog_id );
				
		/* Delete likes */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like = classes_like::bootstrap( 'blog', 'blog' );
		$_like->remove( $blog_id );
		
		/* Check the has_blog flag */
		$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as blogs', 'from' => 'blog_blogs', 'where' => "member_id={$this->memberData['member_id']}" ) );

		if( ! $total['blogs'] )
		{
			$this->DB->update( 'members', array( 'has_blog' => '' ), "member_id={$this->memberData['member_id']}" );
		}

		/* Rebuild group cache */
		$this->registry->cache()->rebuildCache( 'blog_gblogs', 'blog' );
		
		/* Rebuild stats */
		$this->rebuildStats();
	}
	
	/**
	 * Create a new blog
	 *
	 * @param	array		Array of blog data
	 * @param	mixed		Blog owner data (uses $this->memberData by default)
	 * @param	bool		Bypass permission checks (if in ACP this is automatic)
	 * @return  array		Array of data
	 * EXCEPTION CODES
	 * NO_NAME				No blog name entered
	 * NO_OWNER				No owner ID / data sent
	 * MAX_BLOGS_REACHED	More blogs than allowed
	 * NOT_ALLOWED_LOCAL	Not allowed local blogs
	 * NOT_ALLOWED_EXT		Not allowed external blogs
	 * CONFIG_ERROR			Unspecified configuration error
	 *
	 */
	public function createBlog( $blogData, $member=null, $silence=false )
	{
		$silence    = ( IN_ACP ) ? true : $silence;
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		
		/* Basic checks */
		if ( ! $blogData['blog_name'] )
		{
			throw new Exception( 'NO_NAME' );
		}
		
		/* Check for max blogs */
		if ( ! $this->checkMaxBlogs( $memberData ) )
		{
			if ( ! $silence )
			{
				throw new Exception( 'MAX_BLOGS_REACHED' );
			}
		}
		
		/* Check Type */
		if ( $blogData['blog_type'] != 'local' AND $blogData['blog_type'] != 'external' )
		{
			throw new Exception( 'CONFIG_ERROR' );
		}
		
		/* Local blogs allowed? */
		if ( ! $memberData[ 'g_blog_allowlocal' ] AND $blogData['blog_type'] == 'local' )
		{
			if ( ! $silence )
			{
				throw new Exception( 'NOT_ALLOWED_LOCAL' );
			}
		}
		/* External Blogs Allowed? */
		else if ( ! $memberData['g_blog_allowcreate'] AND $blogData['blog_type'] == 'external' )
		{
			if ( ! $silence )
			{
				throw new Exception( 'NOT_ALLOWED_EXT' );
			}
		}

		/* Blog Settings */
		$blog_settings	= array( 'allowrss'				=> $this->settings['blog_allow_rss']        ? $this->settings['blog_allow_rss'] : 1,
								 'allowtrackback'		=> $this->settings['blog_allow_trackback']  ? $this->settings['blog_allow_trackback'] : 1,
								 'trackcomments'		=> 0,
								 'entriesperpage'		=> $this->settings['blog_entries_perpage']  ? $this->settings['blog_entries_perpage'] : 10,
								 'commentsperpage'		=> $this->settings['blog_comments_perpage'] ? $this->settings['blog_comments_perpage'] : 20,
								 'allowguestcomments'	=> 1,
								 'defaultstatus'		=> $this->settings['blog_entry_defaultstatus'] == 'published' ? 'published' : 'draft',
								 'eopt_mode'			=> "autohide",
								 'blockslocation'		=> 'right'
								);

		/* Main Blog Record */
		$my_blog = array(	'member_id'			  => $memberData['member_id'],
							'blog_name'			  => IPSText::getTextClass('bbcode')->stripBadWords( $blogData[ 'blog_name' ] ),
							'blog_desc'			  => IPSText::getTextClass('bbcode')->stripBadWords( $blogData[ 'blog_desc' ] ),
							'blog_type'			  => $blogData['blog_type'],
							'blog_exturl'		  => '',
							'blog_allowguests'	  => 1,
							'blog_settings'		  => serialize( $blog_settings ),
							'blog_view_level'	  => 'public',
							'blog_groupblog'      => empty( $blogData['blog_groupblog_ids'] ) ? 0 : 1,
							'blog_groupblog_ids'  => ( isset( $blogData['blog_groupblog_ids'] ) ) ? ( is_array( $blogData['blog_groupblog_ids'] ) ? ',' . implode( ',', $blogData['blog_groupblog_ids'] ) . ',' : $blogData['blog_groupblog_ids'] ) : '',
							'blog_groupblog_name' => $blogData['blog_groupblog_name'] );
		
		$my_blog['blog_seo_name'] = IPSText::makeSeoTitle( $my_blog['blog_name'] );
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $my_blog, 'blogAddBlog' );
		
		/* Insert data */
		$this->DB->insert( 'blog_blogs', $my_blog );
		
		$blog_id			= $this->DB->getInsertId();
		$my_blog['blog_id'] = $blog_id;
		
		/* Default cBlocks Record */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'blog_default_cblocks',
								 'where'  => 'cbdef_default=1 and cbdef_enabled=1' ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			if ( $row['cbdef_id'] )
			{
				$this->DB->insert( 'blog_cblocks', array( 'blog_id'       => $blog_id,
														  'member_id'     => $memberData['member_id'],
														  'cblock_order'  => $row['cbdef_order'],
														  'cblock_show'   => 1,
														  'cblock_type'   => 'default',
														  'cblock_ref_id' => $row['cbdef_id'] ) );
			}
		}

		/* Update the member record */
		if ( empty( $my_blog['blog_groupblog_ids'] ) )
		{
			$this->flagMyBlogsRecache( array( $memberData['member_id'] ), 'member', true );
		}
		else
		{
			$this->flagMyBlogsRecache( explode(',', IPSText::cleanPermString( $my_blog['blog_groupblog_ids'] ) ), 'group' );
		}
		
		/* Update the Blog stats */
		$this->rebuildBlog( $blog_id );
		$this->rebuildStats();
		
		return $my_blog;
	}
	
	/**
	 * Rebuild Blog Level
	 *
	 * @param  integer	$blog_id
	 * @return	@e void
	 */
	public function rebuildBlog( $blog_id="" )
	{
		/* Init blog entry ids */
		$blogEntryIds = array();
		
		$this->DB->build( array( 'select' => 'entry_id', 'from' => 'blog_entries', 'where' => "blog_id={$blog_id} AND entry_status='published'" ) );
		$this->DB->execute();
		
		if ( $this->DB->getTotalRows() )
		{
			while( $tmp = $this->DB->fetch() )
			{
				$blogEntryIds[ $tmp['entry_id'] ] = $tmp['entry_id'];
			}
		}
		
		/* Query the row */
		$levelrow = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_lastinfo', 'where' => "blog_id={$blog_id}" ) );

		/* Recount Entries and Drafts */
		$this->DB->loadCacheFile( IPSLib::getAppDir('blog') . '/sql/' . ips_DBRegistry::getDriverType() . '_blog_queries.php', 'sql_blog_queries' );
		$this->DB->buildFromCache( 'blog_rebuild_getcounts', array( 'blog_id' => $blog_id, 'extra' => '' ), 'sql_blog_queries' );
		$this->DB->execute();

		$update = $this->DB->fetch();
		
		/* Reset tag cloud cache */
		$update['blog_tag_cloud'] = $this->fetchTagCloud( $blog_id, array(), true );
		
		/* Reset block cache */
		$update['blog_cblocks']           = serialize( $this->registry->getClass('cblocks')->fetchUsedContentBlocks( $blog_id, true ) );
		$update['blog_cblocks_available'] = serialize( $this->registry->getClass('cblocks')->fetchAllContentBlocks( $blog_id, true ) );

		/* Reset */
		$update['blog_num_entries']	= intval($update['blog_num_entries']);
		$update['blog_num_drafts']	= intval($update['blog_num_drafts']);
				
		/* Count Comments */
		$update['blog_num_comments'] = 0;
		
		if ( count($blogEntryIds) )
		{
			$row = $this->DB->buildAndFetch( array( 'select' => "COUNT(*) as comment_count", 'from' => 'blog_comments', 'where' => 'entry_id IN (' . implode( ',', $blogEntryIds ) . ') AND comment_approved=1' ) );
			
			$update['blog_num_comments'] = intval($row['comment_count']);
		}
		
		/* Last Entry Information */
		$row = $this->DB->buildAndFetch( array( 'select' => 'entry_id, entry_date, entry_name, entry, entry_html_state, entry_use_emo, entry_short, entry_banish',
												'from'   => 'blog_entries',
												'where'  => "blog_id={$blog_id} AND entry_status='published'",
												'order'  => 'entry_date DESC',
												'limit'  => array( 0, 1 )
										)		);
		
		$update['blog_last_entry']		   = intval($row['entry_id']);
		$update['blog_last_date']		   = intval($row['entry_date']);
		$update['blog_last_entryname']	   = trim($row['entry_name']);
		$update['blog_last_update']		   = intval($row['entry_date']);
		$update['blog_last_entry_excerpt'] = $this->getEntryExcerpt( $row );
		
		$_entry_banish = $row['entry_banish'];
		
		/* Last Comment Information */
		if ( count($blogEntryIds) )
		{
			/* Be sure we have the latest 300 entries */
			arsort($blogEntryIds);
			
			$blogEntryIds = array_slice( $blogEntryIds, 0, 300, true );
			
			$row = $this->DB->buildAndFetch( array( 'select'   => 'c.*',
													'from'	   => array('blog_comments' => 'c'),
													'where'	   => 'c.entry_id IN (' . implode( ',', $blogEntryIds ) . ') AND c.comment_approved=1',
													'order'	   => 'c.comment_id DESC',
													'limit'	   => array( 0, 1 ),
													'add_join' => array( array( 'select' => 'm.members_display_name, m.member_group_id, m.members_seo_name',
																				'from'	 => array( 'members' => 'm' ),
																				'where'	 => "c.member_id=m.member_id",
																				'type'	 => 'left' ) )
											)		);
		}
		
		$update['blog_last_comment']			= intval($row['comment_id']);
		$update['blog_last_comment_date']		= intval($row['comment_date']);
		$update['blog_last_comment_entry']		= intval($row['entry_id']);
		$update['blog_last_comment_entryname']	= trim($row['entry_name']);
		$update['blog_last_comment_name']		= empty($row['members_display_name']) ? $row['member_name'] : $row['members_display_name'];
		// This doesn't exist in current release...
		//$update['blog_last_comment_seoname']	= empty($row['members_seo_name']) ? IPSText::makeSeoTitle($row['member_name']) : $row['members_seo_name'];
		$update['blog_last_comment_mid']		= intval($row['member_id']);
		// And neither does this...
		//$update['blog_last_comment_group']		= intval($row['member_group_id']);
		$update['blog_last_update']				= $row['comment_date'] > $update['blog_last_update'] ? intval($row['comment_date']) : intval($update['blog_last_update']);
		
		/* Last 20 comments on this blog */
		$update['blog_last_comment_20']			= serialize( $this->fetchBlogComments( $blog_id, 20 ) );
		
		// Force tag cloud to recache
		$update['blog_tag_cloud'] = '';
		
		/* Update blog lastinfo */
		if ( $levelrow['blog_id'] )
		{
			$this->DB->update( "blog_lastinfo", $update, "blog_id={$blog_id}", true );
		}
		else
		{
			$update['blog_id'] = $blog_id;
			$this->DB->insert( "blog_lastinfo", $update, true );
		}
		
		/* This is annoying but worth it to remove filesorts and tmp tables.. grr */
		if ( $update['blog_last_date'] )
		{
			$this->DB->update( 'blog_blogs', array( 'blog_last_udate' => intval( $update['blog_last_update'] ), 'blog_last_edate' => intval( $update['blog_last_date'] ), 'blog_lentry_banish' => intval( $_entry_banish ) ), 'blog_id=' . $blog_id );
		}
		
		/* Reset blog categories */
		$this->categoriesRecacheForBlog( $blog_id );
	}
	
	/**
	 * Fetch comments from a blog(s) (not an entry)
	 *
	 * @param	mixed	INT:	Blog ID, ARRAY array of blog IDs
	 * @param	int		[number to capture, 10 is the limit]
	 * @param	array	Prefs (field, direction,offset)
	 * @param	bool	Core data only (does not get comment content) [default is true]
	 * @param	int		Number of chrs to cut comments down (0/false means no cutting)
	 * @param	bool	Parse comment BBCode (bbcode is stripped if cutOffChars is set)
	 * @param	bool	Show hidden comments (false by default )
	 * @return	array	Array of comments (indexed by comment ID)
	 */
	public function fetchBlogComments( $blogId, $limit=10, $orderPrefs=array(), $coreOnly=true, $cutOffChars=0, $parseComments=true, $showHidden=false )
	{
		/* Init vars */
		$comments	= array();
		$entries	= array();
		$members	= array();
		$mids		= array();
		
		/* Init filters */
		$parseComments	= ( ! $cutOffChars AND ! $coreOnly) ? $parseComments : false;
		$geddit			= ( $coreOnly === true ) ? 'comment_id, entry_id, comment_date, member_name, member_id' : '*';
		$geddit2		= ( $coreOnly === true ) ? 'entry_id, entry_name, entry_name_seo' : '*';
		$orderField		= ( $orderPrefs['field'] )     ? $orderPrefs['field'] : 'comment_id';
		$orderDir		= ( $orderPrefs['direction'] ) ? $orderPrefs['direction']    : 'DESC';
		$start			= intval( $orderPrefs['offset'] );
		$showHiddenBlog	= $showHidden ? '' : " AND entry_status='published'";
		$showHiddenCom	= $showHidden ? '' : ' AND comment_approved=1';
		
		
		if ( ! $coreOnly AND $parseComments )
		{
			$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );				
			$this->registry->setClass( 'blogParsing', new $classToLoad($this->registry) );
		}
		
		if ( $blogId )
		{
			$blogId = is_array($blogId) ? $blogId : array( $blogId );
			
			/* Load the entries - 2 queries is mejo che uan! */
			$this->DB->build( array( 'select'   => $geddit2,
									 'from'     => 'blog_entries',
									 'where'    => 'blog_id IN (' . implode( ',', IPSLib::cleanIntArray( $blogId ) ) . ')' . $showHiddenBlog,
									 'order'    => 'entry_id DESC',
									 'limit'    => array( 0, 500 )
							 )		);
			$_entries = $this->DB->execute();
			
			while( $entryData = $this->DB->fetch( $_entries ) )
			{
				$entries[ $entryData['entry_id'] ] = $entryData;
			}
			
			/* @link	http://community.invisionpower.com/tracker/issue-32408-cant-delete-blog-entry */
			if( count($entries) )
			{
				$this->DB->build( array( 'select'   => $geddit,
										 'from'     => array( 'blog_comments' => 'c' ),
										 'where'    => "entry_id IN (" . implode( ',', array_keys( $entries ) ) . ")" . $showHiddenCom,
										 'order'    => $orderField . ' ' . $orderDir,
										 'limit'    => array( $start, $limit )
								 )		);
				$q = $this->DB->execute();
	
				while( $comment = $this->DB->fetch( $q ) )
				{
					/* Merge back in entry data */
					if ( !empty($entries[ $comment['entry_id'] ]) )
					{
						$comment = array_merge( $comment, $entries[ $comment['entry_id'] ] );
					}
					
					unset( $comment['entry'] );
					
					$comment['_comment_date'] = $this->registry->getClass('class_localization')->getDate( $comment['comment_date'], 'LONG' );
					
					if ( ! $coreOnly AND $cutOffChars )
					{
						$comment['comment_text'] = strip_tags( IPSText::getTextClass( 'bbcode' )->stripAllTags( $comment['comment_text'] ) );
						
						if ( $cutOffChars < IPSText::mbstrlen( $comment['comment_text'] ) )
						{
							$comment['comment_text'] = $this->cutPost( $comment['comment_text'], $cutOffChars );
						}
					}
					if ( $parseComments )
					{
						$tmp = $this->registry->getClass('blogParsing')->parseComment( $comment, false );
						$comment = $tmp['comment'];
					}
					
					if ( ! $coreOnly AND $comment['member_id'] )
					{
						$mids[ $comment['member_id'] ] = $comment['member_id'];
					}
					else if ( ! $coreOnly )
					{
						$comment = array_merge( $comment, IPSMember::buildDisplayData( IPSMember::setUpGuest(), array( 'reputation' => 0, 'warn' => 0 ) ) );
					}
					
					$comments[ $comment['comment_id'] ] = $comment;
				}
				
				if ( count( $mids ) )
				{
					$members = IPSMember::load( $mids, 'all' );
				}
				
				if ( count( $members ) )
				{
					foreach( $comments as $cid => $cdata )
					{
						if ( $cdata['member_id'] AND $members[ $cdata['member_id'] ] )
						{
							$comments[ $cid ] = array_merge( $cdata, IPSMember::buildDisplayData( $members[ $cdata['member_id'] ] ) );
						}
					}
				}
			}
		}
		
		return $comments;
	}
	
	/**
	 * Fetch blog SEO name
	 *
	 * @param	mixed		[optional $blogId or array of blog data otherwise $this->blog is used]
	 * @return	string
	 */
	public function fetchBlogSeoName( $blogId=0 )
	{
		if ( is_array( $blogId ) AND $blogId['blog_id'] )
		{
			$blogData = $blogId;
		}
		else
		{
			$blogData = ( $blogId ) ? $this->loadBlog( intval( $blogId ) ) : $this->blog;
		}
		
		if ( $blogData['blog_id'] )
		{
			if ( ! $blogData['blog_seo_name'] )
			{
				$blogData['blog_seo_name'] = IPSText::makeSeoTitle( $blogData['blog_name'] );
				
				if ( $blogId == $this->blog['blog_id'] )
				{
					$this->blog['blog_seo_name'] = $blogData['blog_seo_name'];
				}
				
				/* Update DB */
				$this->DB->update( 'blog_blogs', array( 'blog_seo_name' => $blogData['blog_seo_name'] ), 'blog_id=' . $blogData['blog_id'] );
			}
		}
		
		return $blogData['blog_seo_name'];
	}
	
	/**
	 * Fetch all blogs the user "owns" or can edit
	 * Returns simple data: blog_name, blog_seo_name, _type (editor, group, owner)
	 *
	 * @param	array		$member			Array of member data or null to use memberData
	 * @param	bool		$forceFresh		Force reload from DB
	 * @return	@e array	Member blogs
	 */
	public function fetchMyBlogs( $member=null, $forceFresh=false )
	{
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		
		/* Needs to recache? */
		if ( ! empty($memberData['blogs_recache']) OR $forceFresh OR $memberData['has_blog'] == 'recache' )
		{
			$hasBlog = $this->rebuildMyBlogsCache( $memberData, $forceFresh );
		}
		else
		{
			$hasBlog = IPSLib::isSerialized($memberData['has_blog']) ? unserialize($memberData['has_blog']) : $memberData['has_blog'];
		}
		
		return is_array($hasBlog) ? $hasBlog : array();
	}
	
	/**
	 * Rebuild 'myBlogs'
	 *
	 * @param	array		Array of member data or null to use memberData
	 * @param	bool		Don't actually update the member row unless it is different
	 */
	public function rebuildMyBlogsCache( $member=null, $noBlindSave=false )
	{
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		$blogIds    = array();
		$blogs      = array();
		$aGroups    = array();
		
		/* Do a little set up */
		if ( is_array( $this->caches['group_cache'] ) )
		{
			foreach( $this->caches['group_cache'] as $gid => $gdata )
			{
				if ( $gdata['g_blog_alloweditors'] )
				{
					$aGroups[] = $gid;
				}
			}
		}
		
		/* Get editor blogs! */
		$this->DB->build( array( 'select' => '*',
								 'from'	  => 'blog_editors_map',
								 'where'  => 'editor_member_id=' . $memberData['member_id'] ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$blogIds[ $row['editor_blog_id'] ] = 'editor';
		}
		
		/* Get owner blogs! */
		$this->DB->build( array( 'select' => 'blog_id',
								 'from'   => 'blog_blogs',
								 'where'  => 'member_id=' . $memberData['member_id'] . ' AND blog_groupblog=0' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$blogIds[ $row['blog_id'] ] = 'owner';
		}
		
		/* Get private club blogs! */
		$this->DB->build( array( 'select' => 'blog_id',
								 'from'   => 'blog_blogs',
								 'where'  => 'blog_owner_only=0 AND ( blog_authorized_users IS NOT NULL AND blog_authorized_users LIKE \'%,' . $memberData['member_id'] . ',%\')' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$blogIds[ $row['blog_id'] ] = 'privateclub';
		}
		
		/* Fetch group blogs! Wow, this is tiresome */
		$gbs = $this->cache->getCache('blog_gblogs'); # We use getCache is it autoloads when in usercp etc
		
		if ( is_array($gbs) )
		{
			/* started to lose my mind right here */
			foreach( $gbs as $g => $bs )
			{
				if ( is_array( $bs['_groupIds'] ) AND count( $bs['_groupIds'] ) AND IPSMember::isInGroup( $memberData, $bs['_groupIds'], true ) )
				{
					$blogIds[ $bs['blog_id'] ] = 'group';
				}
			}
		}
		
		/* Fetch the data! */
		if ( count($blogIds) )
		{
			$this->DB->build( array( 'select'   => 'b.blog_name, b.blog_seo_name, b.blog_id, b.blog_type, b.member_id as blog_owner_id, b.blog_groupblog, b.blog_groupblog_ids, b.blog_view_level, b.blog_num_exthits, b.blog_num_views, b.blog_disabled',
									 'from'	    => array( 'blog_blogs' => 'b' ),
									 'where'	=> 'b.blog_id IN (' . implode( ',', array_keys( $blogIds ) ) . ')',
									 'order'	=> 'b.blog_name',
									 'add_join' => array( array( 'select' => 'm.member_id, m.member_group_id, m.mgroup_others, m.members_display_name, m.members_seo_name',
																 'from'   => array( 'members' => 'm' ),
																 'where'  => 'b.member_id=m.member_id',
																 'type'   => 'left' ) ) ) );
																
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				if ( empty($row['blog_disabled']) )
				{
					/* Set type */
					$row['_type'] = ( $row['blog_owner_id'] == $memberData['member_id'] AND ( ! $row['blog_groupblog'] ) ) ? 'owner' : $blogIds[ $row['blog_id'] ];
					
					/* If we are an editor, make sure the Admin allows this */
					if ( $row['_type'] == 'editor' )
					{
						if ( ! IPSMember::isInGroup( $row, $aGroups, true ) )
						{
							unset($blogIds[ $row['blog_id'] ]);
							continue;
						}
					}
					
					/* Set a flag for blogs we can actually post in */
					$row['_canPostIn'] = ( in_array( $row['_type'], array( 'group', 'owner', 'editor' ) ) AND $row['blog_type'] != 'external' );
					
					/* Unset some data */
					unset( $row['member_id'] );
					
					$blogs[ $row['blog_id'] ] = $row;
				}
			}
			
			/* Get entries and comments */
			if ( count($blogs) )
			{
				$this->DB->build( array('select' => 'blog_id, count(*) as entries, SUM(entry_num_comments) as comments', 'from' => 'blog_entries', 'where' => 'blog_id IN (' . implode( ',', array_keys( $blogs ) ) . ')', 'group' => 'blog_id') );
				$this->DB->execute();
				while( $row = $this->DB->fetch() )
				{
					if ( isset($blogs[ $row['blog_id'] ]) )
					{
						$blogs[ $row['blog_id'] ]['num_entries']       = $row['entries'];
						$blogs[ $row['blog_id'] ]['blog_num_comments'] = $row['comments'];
					}
				}
			}
		}
		
		$sBlogs  = count($blogs) ? serialize($blogs) : '';
		$hbCheck = is_array($memberData['has_blog']) ? serialize($memberData['has_blog']) : ( IPSLib::isSerialized($memberData['has_blog']) ? $memberData['has_blog'] : null );
		
		if ( $hbCheck != $sBlogs OR ! $noBlindSave OR $memberData['blogs_recache'] )
		{
			$this->DB->update( 'members', array( 'has_blog' => $sBlogs, 'blogs_recache' => 0 ), 'member_id=' . $memberData['member_id'] );
			
			// Update current member as well */
			if ( $this->memberData['member_id'] && $this->memberData['member_id'] == $memberData['member_id'] )
			{
				$this->memberData['blogs_recache']	= 0;
				$this->memberData['has_blog']		= $sBlogs;
			}
		}
		
		return $blogs;
	}
	
	/**
	 * Update private club mappings
	 *
	 * @param	mixed		INT blog ID, or ARRAY blog data
	 * @param	mixed		Array of IDs (optional) OR FALSE to remove all private club members
	 * @param	array		Array of memberData (uses $this->memberData if none passed)
	 */
	public function updatePrivateClubMappings( $blog, $members=array(), $memberData=null )
	{
		$memberData     = ( $memberData != null AND is_array( $memberData ) AND $memberData['member_id'] ) ? $memberData : $this->memberData;
		$currentMembers = array();
		
		if ( is_array( $blog ) AND $blog['blog_id'] )
		{
			$blogData = $blog;
		}
		else
		{
			$blogData = ( $blog ) ? $this->loadBlog( intval( $blog ) ) : $this->blog;
		}
		
		/* Rebuild */
		if ( $blogData['blog_id'] )
		{
			/* Fetch current mappings */
			$map = $this->DB->buildAndFetch( array( 'select' => '*',
													'from'   => 'blog_blogs',
													'where'  => "blog_id=" . $blogData['blog_id'] ) );
			
			$map['blog_authorized_users'] = IPSText::cleanPermString($map['blog_authorized_users']);
			
			if ( $map['blog_authorized_users'] )
			{
				$this->flagMyBlogsRecache( explode( ',', $map['blog_authorized_users'] ), 'member' );
			}
			
			/* Permission Index */
			if( $blogData['blog_view_level'] == 'private' )
			{
				$permission_str = 'blog_owner_only=1';
			}
			else
			{
				$permission_str = 'blog_owner_only=0';
			}
			
			if( is_array( $members ) && count( $members ) )
			{
				$this->flagMyBlogsRecache( $members, 'member', true );
				
				$permission_str .= ", blog_authorized_users='," . implode( ',', $members ) . ",'";
			}
			elseif ( $blogData['blog_view_level'] == 'privateclub' or $blogData['blog_view_level'] == 'friends' )
			{
				$permission_str .= ", blog_authorized_users=''";
			}
			else
			{
				$permission_str .= ", blog_authorized_users=NULL";
			}
	
			$this->DB->update( 'blog_blogs', $permission_str, "blog_id=" . $blogData['blog_id'], FALSE, TRUE );
		}
	}
	
	/**
	 * Update editor mappings
	 *
	 * @param	mixed		INT blog ID, or ARRAY blog data
	 * @param	mixed		Array of IDs (optional) OR FALSE to remove all editors
	 * @param	array		Array of memberData (uses $this->memberData if none passed)
	 */
	public function updateEditorMappings( $blog, $members=array(), $member=null )
	{
		$memberData     = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		$currentMembers = array();
		
		if ( is_array( $blog ) AND $blog['blog_id'] )
		{
			$blogData = $blog;
		}
		else
		{
			$blogData = ( $blog ) ? $this->loadBlog( intval( $blog ) ) : $this->blog;
		}
		
		/* Rebuild */
		if ( $blogData['blog_id'] )
		{
			/* Fetch current mappings */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_editors_map',
									 'where'  => 'editor_blog_id=' . $blogData['blog_id'] ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$currentMembers[] = $row['editor_member_id'];
			}
			
			/* Got several ? */
			if ( count($currentMembers) )
			{
				$this->flagMyBlogsRecache( $currentMembers, 'member' );
			}
			
			/* Delete old mappings */
			$this->DB->delete( 'blog_editors_map', 'editor_blog_id=' . $blogData['blog_id'] );
			
			/* Settings are still serialized? */
			if ( IPSLib::isSerialized($blogData['blog_settings']) )
			{
				$blogData['blog_settings'] = unserialize($blogData['blog_settings']);
			}
			
			/* Add new ones */
			if ( is_array($members) && count($members) )
			{
				foreach( $members as $mid )
				{
					$this->DB->insert( 'blog_editors_map', array( 'editor_member_id' => intval( $mid ),
																  'editor_blog_id'   => $blogData['blog_id'] ) );
				}
				
				$this->flagMyBlogsRecache( $members, 'member', true );
				
				/* Update blog */
				$blogData['blog_settings']['editors'] = $members;
			}
			else
			{
				/* Reset editors */
				if ( isset($blogData['blog_settings']['editors']) )
				{
					unset($blogData['blog_settings']['editors']);
				}
			}
			
			/* Save */
			$this->DB->update( 'blog_blogs', array( 'blog_settings' => serialize($blogData['blog_settings']), 'blog_editors' => intval( count($members) ) ), 'blog_id=' . $blogData['blog_id'] );
		}
	}
	
	/**
	 * Flag members for myBlogs recache based on a blog ID
	 *
	 * @param	array		$blog			Blog data or ID to load it
	 * @param	boolean		$forceValue		Forces a value in the has_blog column, useful when you're sure those members will have an icon shown for sure
	 * @return	@e void
	 */
	public function flagMyBlogsRecacheByBlogId( $blog, $forceValue=false )
	{
		if ( is_array($blog) && ! empty($blog['blog_id']) )
		{
			$blogData = $blog;
		}
		else
		{
			$blogData = $this->loadBlog( intval($blog) );
		}
		
		$memberIds = array( $blogData['member_id'] ); //This takes care ALSO of external type blogs
		
		if ( $blogData['blog_type'] == 'local' )
		{
			switch( $blogData['blog_view_level'] )
			{
				case 'friends':
				case 'privateclub':
					$blogData['blog_authorized_users'] = IPSText::cleanPermString($blogData['blog_authorized_users']);
					
					if ( $blogData['blog_authorized_users'] )
					{
						$memberIds = array_merge( $memberIds, explode( ',', $blogData['blog_authorized_users'] ) );
					}
					break;
			}
			
			/* Fetch current editors */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_editors_map',
									 'where'  => 'editor_blog_id=' . $blogData['blog_id'] ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$memberIds[] = $row['editor_member_id'];
			}
		}
		
		$this->flagMyBlogsRecache( array_unique($memberIds), 'member', $forceValue );
	}
	
	/**
	 * Flag members for myBlogs recache
	 *
	 * @param	array		$ids			Array of ids (members or groups)
	 * @param	string		$type			Type: member/group
	 * @param	boolean		$forceValue		Forces a value in the has_blog column, useful when you're sure those members will have an icon shown for sure 
	 * @return	@e void
	 */
	public function flagMyBlogsRecache( $ids=null, $type='member', $forceValue=false )
	{
		/* Init vars */
		$update = array( 'blogs_recache' => 1 );
		$where  = '';
		
		if ( $forceValue )
		{
			$update['has_blog'] = 'recache';
		}
		
		/* Sort our WHERE query part */
		if ( is_array($ids) && count($ids) )
		{
			if ( $type == 'member' )
			{
				$where = 'member_id IN (' . implode( ',', $ids ) . ')';
			}
			elseif ( $type == 'group' )
			{
				/* This query might require some time to run on large members tables but group blogs aren't changed that often */
				$where = 'member_group_id IN (' . implode( ',', $ids ) . ') OR ' . $this->DB->buildWherePermission( $ids, 'mgroup_others', false );
			}
		}
		
		$this->DB->update( 'members', $update, $where );
		
		/* Turn on the rebuild task */
		if ( empty($where) || $this->DB->getAffectedRows() )
		{
			$task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_application='blog' AND task_key='blog_recache'" ) );
			
			/* Already enabled perhaps? */
			if ( empty($task['task_enabled']) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );/*noLibHook*/
				$tasksObject = new $classToLoad( $this->registry );
				
				$newdate = $tasksObject->generateNextRun( $task );
				$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_enabled' => 1 ), "task_id=" . $task['task_id'] );
				$tasksObject->saveNextRunStamp();
			}
		}
	}
				
	/**
	 * Fetch tag cloud
	 * 
	 * @param	int		Blog ID
	 * @param	array	Blog Data
	 * @param	bool	Force fresh query
	 * @return	string	HTML formatted tag cloud
	 */
	public function fetchTagCloud( $blogId, $blogData=array(), $forceReload=false )
	{
		if ( ! count( $blogData ) )
		{
			if ( $blogId == $this->blog['blog_id'] )
			{
				$blogData = $this->blog;
			}
			else
			{
				$blogData = $this->loadBlog( $blogId );
			}
		}
		
		if ( $blogData['blog_tag_cloud'] AND $forceReload === false )
		{
			return $blogData['blog_tag_cloud'];
		}
		else
		{			
			$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'sources/classes/tags/cloud.php', 'classes_tags_cloud' );
			$cloud = new $classToLoad();
			$cloud->setSkinGroup('blog_global');
			$cloud->setSkinTemplate('hookTagCloud');
			$cloud->setApp('blog');
			$cloud->setArea('entries');
			$cloud->setParentId( $blogId );
			$tagCloud = $cloud->render( $cloud->getCloudData( array( 'limit' => 50, 'noCache' => TRUE ) ) );
			
			/* Update the cache */
			$this->DB->update( 'blog_lastinfo', array( 'blog_tag_cloud' => $tagCloud ), "blog_id={$blogId}" );
			
			return $tagCloud;
		}
	}
	
	/**
	 * Check to see if the logged in user is allowed to create more blogs
	 *
	 * @return	boolean
	 */
	public function checkMaxBlogs( $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		
		/* If the setting is 0, then we can just bypass */
		if( ! $memberData['g_blog_settings']['g_blog_maxblogs'] )
		{
			return true;
		}
		
		/* Check cache */
		if( !empty( $this->blogCountCache[$memberData['member_id']] ) )
		{
			$currBlogCount = $this->blogCountCache[$memberData['member_id']];
		}
		else
		{
			/* Need to query the count */
			$total = $this->DB->buildAndFetch( array( 'select'	=> 'count(blog_id) as blogs', 
													  'from'	=> 'blog_blogs', 
													  'where'	=> "member_id={$memberData['member_id']}" ) );
											
			/* Set cache and count */
			$this->blogCountCache[$memberData['member_id']] = $total['blogs'];
			$currBlogCount = $total['blogs'];
		}
		
		/* Check the setting */
		if( $currBlogCount >= $memberData['g_blog_settings']['g_blog_maxblogs'] )
		{
			return false;
		}
		
		/* We're ok here */
		return true;
	}
	
	/**
	 * Check access to an entry
	 *
	 * @param	array		Entry data
	 * @param	array		Blog id
	 * @return	boolean
	 */
	public function checkAccess( $entry, $blog )
	{
		if ( ! $this->memberData['member_id'] )
		{
			return false;
		}

		if ( !$entry['entry_id'] )
		{
			return false;
		}

		if ( $this->ownsBlog( $blog, $this->memberData ) )
		{
			$show_draft = 1;
		}
		elseif ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			$show_draft = 1;
		}
		else
		{
			$show_draft = 0;
		}

		if ( $entry['entry_status'] == 'draft' and !$show_draft )
		{
			return false;
		}
		
		return true;
	}

	/**
	 * Prettify category names for list style output
	 *
	 * @param	array 		Array (cat_id => cat_data );
	 * @return	string		HTML lump
	 */
	public function formatCategoriesUrl( $blogId, $categories=array() )
	{
		$_count = count( $categories );
		$_c     = 0;
	
		if ( $blogId AND is_array( $categories ) AND count( $categories ) )
		{
			foreach( $categories as $catId => $catData )
			{
				/* Build URL */
				$categories[ $catId ]['_last'] = ( $_count == $_c + 1 ) ? 1 : 0;
				$categories[ $catId ]['_url']  = $this->registry->output->buildSEOUrl( 'app=blog&amp;blogid=' . $blogId . '&amp;cat=' . $catId, 'public', $catData['category_title_seo'], 'blogcatview' );
				
				$_c++;
			}
		}
		
		return $categories;
	}
	
	/**
	 * Update category mappings
	 *
	 * @param	integer		$blogId			Blog ID
	 * @param	mixed		$entryId		Entry ID
	 * @param	string		$type			Category type (draft|private)
	 * @param	integer		$value			Category value (1|0)
	 * @return	@e void	 
	 */
	public function updateCategoryMappings( $blogId, $entryId, $type='draft', $value=0 )
	{
		$field = ( $type == 'private' ) ? 'map_is_private' : 'map_is_draft';
		
		if( ! is_array( $entryId ) && $entryId )
		{
			$entryId = array( intval( $entryId ) );
		}
		
		if ( $blogId and is_array( $entryId ) && count( $entryId ) )
		{
			$this->DB->update( 'blog_category_mapping', array( $field => intval( $value ) ), 'map_blog_id=' . intval( $blogId ) . ' AND map_entry_id IN ( '. implode( ',', $entryId ). ')' );
			
			/* Rebuild category counts */
			$this->categoriesRecacheForBlog( $blogId );
		}
	}
	
	/**
	 * Delete category mappings
	 *
	 * @param	int		Blog ID
	 * @param	mixed	Array of entry IDs OR boolean false to remove all entries for that blog
	 */
	public function deleteCategoryMappings( $blogId, $entryArray=array() )
	{
		if ( $blogId )
		{
			if ( $entryArray === false )
			{
				$this->DB->delete( 'blog_category_mapping', 'map_blog_id=' . intval( $blogId ) );
			}
			else
			{
				$update = IPSLib::cleanIntArray( $entryArray );
				
				if ( count($update) )
				{
					$this->DB->delete( 'blog_category_mapping', 'map_blog_id=' . intval( $blogId ) . ' AND map_entry_id IN(' . implode( ',', $update ) . ')' );
				}
			}
			
			/* Rebuild category counts */
			$this->categoriesRecacheForBlog( $blogId );	
		}
	}
	
	/**
	 * Recache categories for a blog
	 *
	 * @param	int			Blog ID
	 */
	public function categoriesRecacheForEntry( $blogId, $entryId )
	{		
		$cats      = $this->fetchBlogCategories( $blogId );
		$entryCats = array();
		
		/* Get the cats */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'blog_category_mapping',
								 'where'  => 'map_blog_id=' . intval( $blogId ) . ' AND map_entry_id=' . intval( $entryId ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$entryCats[ $row['map_category_id'] ] = array( 'category_title'     => $cats[ $row['map_category_id'] ]['category_title'],
														   'category_title_seo' => $cats[ $row['map_category_id'] ]['category_title_seo'] );
		}
		
		/* Got nothing? Add uncategorized */
		if ( ! count( $entryCats ) )
		{
			$this->DB->insert( 'blog_category_mapping', array( 'map_category_id' => 0,
															   'map_entry_id'    => $entryId,
															   'map_is_draft'    => 0,
															   'map_blog_id'     => $blogId ) );
				
			$entryCats[0] = array(  'category_title'     => $this->lang->words['blog_def_uncategorized'],
									'category_title_seo' => IPSText::makeSeoTitle( $this->lang->words['blog_def_uncategorized'] ) );
		}
		
		/* Cache */
		if ( count( $entryCats ) )
		{
			$this->DB->update( 'blog_entries', array( 'entry_category' => ',' . implode( ',', array_keys( $entryCats ) ) . ',' ), 'blog_id=' . $blogId . ' AND entry_id=' . $entryId );
		}
	}

	/**
	 * Recache categories for a blog
	 *
	 * @param	int			Blog ID
	 */
	public function categoriesRecacheForBlog( $blogId )
	{
		$counts = array();
		
		/* Get counts */
		$this->DB->build( array( 'select' => 'COUNT(*) as count, map_category_id',
								 'from'	  => 'blog_category_mapping',
								 'where'  => 'map_blog_id=' . intval( $blogId ) . ' AND map_is_draft=0',
								 'group'  => 'map_category_id' ) );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$counts[ $row['map_category_id'] ] = $row['count'];
		}
		
		/* Set array */
		$cats   = array( 0 => array( 'category_title'     => $this->lang->words['blog_def_uncategorized'],
									 'category_title_seo' => IPSText::makeSeoTitle( $this->lang->words['blog_def_uncategorized'] ),
									 'count'			  => intval( $counts[0] ) ) );
		
		/* Get the cats */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'blog_categories',
								 'order'  => 'category_title_seo ASC',
								 'where'  => 'category_blog_id=' . intval( $blogId ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$cats[ $row['category_id'] ] = array( 'category_title'     => $row['category_title'],
												  'category_title_seo' => $row['category_title_seo'],
												  'count'			   => intval( $counts[ $row['category_id'] ] ) );
		}

		/* Cache */
		if ( count( $cats ) )
		{
			$this->DB->update( 'blog_blogs', array( 'blog_categories' => serialize( $cats ) ), 'blog_id=' . $blogId );
		}
	}

	/**
	 * Delete categories
	 *
	 * @param	array		Array of category IDs to delete
	 * @param	array		Array of member id (null uses memberData)
	 */
	public function deleteCategories( $ids, $member=null )
	{
		$categories = array();
		$finalIds   = array();
		$blogIds    = array();
		$entryIds   = array();
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		
		/* Got any ids? */
		if ( ! count( $ids ) )
		{
			return false;
		}
		
		/* Inline blog ID? */
		if ( !empty( $this->request['blogid'] ) )
		{
			$blogIds[] = $this->request['blogid'];
		}

		/* Fetch data */
		$this->DB->build( array( 'select'   => 'c.*',
								 'from'     => array( 'blog_category_mapping' => 'c' ),
								 'where'    => 'c.map_category_id IN (' . implode( ',', $ids ) . ')',
								 'add_join' => array( array( 'select' => 'e.*',
															 'from'   => array( 'blog_entries' => 'e' ),
															 'where'  => 'e.entry_id=c.map_entry_id' ),
													  array( 'select' => 'b.*, b.member_id as blog_owner_id',
															 'from'   => array( 'blog_blogs' => 'b' ),
															 'where'  => 'b.blog_id=e.blog_id' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Reset member ID */
			$category = $row;
			$blog     = $row;
			
			$categories[ $row['map_category_id'] ][] = $row;
			$blog['member_id']              		 = $row['blog_owner_id'];
			
			/* Basic permission checks */
			if ( $this->ownsBlog( $blog, $memberData ) )
			{
				$blogIds[]  = $row['blog_id'];
				$entryIds[ $row['entry_id'] ] = $row['blog_id'];
				$finalIds[] = $row['map_category_id'];
			}
		}
		
		/* Delete categories */
		$this->DB->delete( 'blog_categories', 'category_id IN (' . implode( ",", $ids ) . ')' );
		
		/* Got any ids? */
		if ( count( $finalIds ) )
		{
			/* Delete mapping */
			$this->DB->delete( 'blog_category_mapping', 'map_category_id IN (' . implode( ",", $finalIds ) . ')' );
		}
		
		/* Rebuild the entry */
		if ( count( $entryIds ) )
		{
			foreach( $entryIds as $eid => $bid )
			{
				$this->categoriesRecacheForEntry( $bid, $eid );
			}
		}
		
		/* Rebuild blogs */
		if ( count( $blogIds ) )
		{
			foreach( $blogIds as $blogid )
			{
				$this->categoriesRecacheForBlog( $blogid );
			}
		}
		
		return true;
	}
	
	/**
	 * Fetch all user's created categories
	 * 
	 * @param	int		Blog ID
	 * @param	int		Number to fetch, 10 is default
	 * @param	array	Simple array of 'selected' arrays
	 * @return	array
	 */
	public function fetchBlogCategories( $blogId, $selected=array() )
	{
		$cats = array( 0 => array( 'category_title'     => $this->lang->words['blog_def_uncategorized'],
								   'category_title_seo' => IPSText::makeSeoTitle( $this->lang->words['blog_def_uncategorized'] ),
								   '_selected'			=> ( count( $selected ) AND in_array( 0, $selected ) ) ? 1 : 0 ) );
		
		if ( empty( $blogId ) )
		{
			return $cats; #meow
		}
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'blog_categories',
								 'order'  => 'category_title_seo ASC',
								 'where'  => 'category_blog_id=' . intval( $blogId ) ) );
		$this->DB->execute();
		
		while( $c = $this->DB->fetch() )
		{
			$c['_selected'] = ( count($selected) && in_array( $c['category_id'], $selected ) ) ? 1 : 0;
			
			$cats[ $c['category_id'] ] = $c;
		}
		
		return $cats;
	}
	
	/**
	 * Filter blog output through sendContent
	 *
	 * @param	array		Current blog
	 * @return	@e void
	 */
	public function sendBlogOutput( $blog, $title='', $headerButtons=TRUE )
	{		
		$this->blog =& $blog;
		
		//-----------------------------------------
		// Are we changing theme?
		//-----------------------------------------
		
		if ( isset( $this->request['changeTheme'] ) )
		{
			$blog = $this->changeTheme( $blog );
		}

		//-----------------------------------------
		// Get some js we need
		//-----------------------------------------
		
		$theme_js  = $this->getThemeJs( $blog );
		$cblock_js = $this->getCblockJs( $blog );
				
		//----------------------------------------- 
		// Header
		//----------------------------------------- 
		 
		$blog['blog_url'] = str_replace( "&amp;", "&", $this->getBlogUrl( $blog['blog_id'] ) ); 
		 
		// TODO: need to remove the header_js & $headerButtons variables from the template in a future major skin upgrade
		$html = $this->registry->output->getTemplate('blog_global')->blog_header( $blog, $cblock_js, $theme_js, '', $title, $headerButtons );

		$this->registry->output->addContent( $html, true ); 
		 
		//----------------------------------------- 
		// Sort out any additional CSS we need 
		//----------------------------------------- 
       
		if( $blog['header_css'] ) 
		{ 
			$css .= $blog['header_css']; 
		} 
		
		if( $this->settings['blog_themes_custom'] AND $blog['blog_theme_custom'] AND $this->request['previewTheme'] AND $this->memberData['g_access_cp'] )
		{
			$css .= $blog['blog_theme_custom'];
		}
		else if( $this->settings['blog_themes'] AND $blog['blog_theme_id'] )
		{
			$theme = $this->caches['blog_themes'][ $blog['blog_theme_id'] ];

			//-----------------------------------------
			// Clearing out any other CSS?
			//-----------------------------------------
			
			if( $theme['theme_css_overwrite'] )
			{
				$this->registry->output->clearLoadedCss();
			}
			
			if( $theme['theme_css'] )
			{
				$css .= str_replace( "{images}", $theme['theme_images'], $theme['theme_css'] );
			}
		}
		else if( $this->settings['blog_themes_custom'] AND $blog['blog_theme_final'] )
		{
			$css .= $blog['blog_theme_final'];
		}

		if( $css )
		{
			$this->registry->output->addToDocumentHead( 'inlinecss', $css );
		}
		
		//-----------------------------------------
		// And do the output
		//-----------------------------------------
		
		$this->registry->output->sendOutput();
	}

	/**
	 * Get the blog url
	 *
	 * @param  integer	$blog_id
	 * @param  string   $seoTitle
	 * @return string
	 */
	public function getBlogUrl($blog_id, $seoTitle = '')
	{
		$url = 'app=blog&amp;module=display&amp;section=blog&amp;blogid=' . $blog_id;
		$url = ipsRegistry::getClass('output')->buildSEOUrl( $url, 'public', $seoTitle, 'showblog' );;
		return $url;
	}

	/**
	 * Clean membername
	 *
	 * @param  string  $membername
	 * @return string
	 */
	public function cleanMemberName( $membername="" )
	{
		//-----------------------------------------
		// Replace all spaces and points with _
		//-----------------------------------------
		$cleanname = str_replace( " ", "_", $membername );
		$cleanname = str_replace( ".", "_", $cleanname );
		$cleanname = str_replace( "/", "_", $cleanname );
		$cleanname = str_replace( "\\", "_", $cleanname );

		//-----------------------------------------
		// replace all unicode with _
		//-----------------------------------------
		$cleanname = preg_replace("/&#[0-9]+;/s", "_", $cleanname );

		return strtolower( $cleanname );
	}

	/**
	 * Rebuild a blog entry
	 *
	 * @param  integer	$entry_id
	 * @return	@e void
	 */
	public function rebuildEntry( $entry_id="" )
	{
		$update = $this->DB->buildAndFetch( array( 
													'select' => 'COUNT(*)-SUM(comment_approved) as entry_queued_comments, SUM(comment_approved) as entry_num_comments',
													'from'	 => 'blog_comments',
													'where'	 => "entry_id={$entry_id}"
										)	);
												
		$trackbacks = $this->DB->buildAndFetch( array( 
														'select' => 'count(*) as num_trackbacks',
														'from'	 => 'blog_trackback',
														'where'	 => "entry_id={$entry_id} AND trackback_queued=0"
											)	);

		$row =	$this->DB->buildAndFetch( array( 
												'select'   => 'c.comment_id, c.comment_date, c.member_id, c.member_name',
												'from'	   => array( 'blog_comments' => 'c' ),
												'add_join' => array( array( 
																			'select' => 'm.members_display_name',
																			'from'	 => array( 'members' => 'm' ),
																			'where'	 => "c.member_id=m.member_id",
																			'type'	 => 'left'
																	)	),
												'where'	   => "c.entry_id={$entry_id} and comment_approved=1",
												'order'	   => 'c.comment_id DESC',
												'limit'	   => array( 0, 1 )
										)	);

		$entry = $this->DB->buildAndFetch( array( 
													'select'   => 'e.entry_date, e.entry_author_name',
													'from'	   => array( 'blog_entries' => 'e' ),
													'add_join' => array( array( 
																				'select' => 'm.members_display_name',
																				'from'	 => array( 'members' => 'm' ),
																				'where'	 => "e.entry_author_id=m.member_id",
																				'type'	 => 'left'
																		 )	),
													'where'	   => "e.entry_id={$entry_id}"
												)	);

		/* Build Update Array */
		$update['entry_last_comment']		= intval($row['comment_id']);
		$update['entry_last_comment_date']	= intval($row['comment_date']);
		$update['entry_last_comment_name']	= empty($row['members_display_name']) ? $row['member_name'] : $row['members_display_name'];
		$update['entry_last_comment_mid']	= intval($row['member_id']);
		$update['entry_trackbacks']			= intval($trackbacks['num_trackbacks']);
		$update['entry_num_comments']       = intval($update['entry_num_comments']);
		$update['entry_queued_comments']    = intval($update['entry_queued_comments']);
		
		if ( $update['entry_num_comments'] > 0 )
		{
			$update['entry_last_update']	= intval($row['comment_date']);
		}
		else
		{
			$update['entry_last_update']	= intval($entry['entry_date']);
		}
		
		$update['entry_author_name']		= empty($entry['members_display_name']) ? $entry['entry_author_name'] : $entry['members_display_name'];;
		
		/* Update the entry table */
		$this->DB->update( 'blog_entries', $update, "entry_id={$entry_id}", true );
	}
	
	/**
	 * Set comments queued or approved
	 *
	 * @param	array		Simple array of comments IDs
	 * @param	int			Approved=1, unapproved=0
	 * @param	array		Array of member data for user that activated this request (memberData used by default)
	 * @return	bool
	 */
	public function changeCommentApproval( $ids, $approved=0, $member=null )
	{
		/* INIT */
		$comments   = array();
		$finalIds   = array();
		$blogIds    = array();
		$entryIds   = array();
		$approved   = $approved ? 1 : 0;
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		$cache      = array();
		
		/* Got anything? */
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			return false;
		}
		
		/* Fetch data */
		$this->DB->build( array( 'select'   => 'c.*, c.member_id as comment_member_id',
								 'from'     => array( 'blog_comments' => 'c' ),
								 'where'    => 'c.comment_id IN (' . implode( ',', $ids ) . ')',
								 'add_join' => array( array( 'select' => 'e.*',
															 'from'   => array( 'blog_entries' => 'e' ),
															 'where'  => 'e.entry_id=c.entry_id' ),
													  array( 'select' => 'b.*, b.member_id as blog_owner_id',
															 'from'   => array( 'blog_blogs' => 'b' ),
															 'where'  => 'b.blog_id=e.blog_id' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Reset member ID */
			$comment = $row;
			$blog    = $row;
			
			$comments[ $row['comment_id'] ] 	= $row;
			$comment['member_id']           	= $row['comment_member_id'];
			$blog['member_id']              	= $row['blog_owner_id'];
			$blog['blog_settings']				= unserialize( $blog['blog_settings'] );
			$blog['blog_settings']['editors']	= is_array( $blog['blog_settings']['editors'] ) ? $blog['blog_settings']['editors']  : array();
			$blog['_is_editor']					= in_array( $memberData['member_id'], $blog['blog_settings']['editors'] );

			/* Basic permission checks */
			if ( $this->allowApprove( $blog, $memberData ) )
			{
				$blogIds[]  = $row['blog_id'];
				$entryIds[] = $row['entry_id'];
				$finalIds[] = $row['comment_id'];
			}
		}

		/* Got any ids? */
		if ( ! count( $finalIds ) )
		{
			return false;
		}
		
		/* Update comments */
		$this->DB->update( "blog_comments", array( 'comment_approved' => $approved ), "comment_id IN (" . implode( ",", $finalIds ) . ") AND entry_id IN(" . implode( ",", $entryIds ) . ')' );
	
		/* Rebuild the entry */
		if ( count( $entryIds ) )
		{
			foreach( $entryIds as $eid )
			{
				$this->rebuildEntry( $eid );
			}
		}
		
		/* Rebuild blogs */
		if ( count( $blogIds ) )
		{
			foreach( $blogIds as $blogid )
			{
				$this->rebuildBlog( $blogid );
			}
		}
		
		/* Update the Blog stats */
		$this->rebuildStats();
		
		return true;
	}
	
	/**
	 * Delete comments
	 *
	 * @param	array	Simple array of comments IDs
	 * @param	array	Array of member data for user that activated this request (memberData used by default)
	 * @return	bool
	 */
	public function deleteComments( $ids, $member=null )
	{
		/* INIT */
		$comments   = array();
		$finalIds   = array();
		$blogIds    = array();
		$entryIds   = array();
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		$cache      = array();
		
		/* Got anything? */
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			return false;
		}
		
		/* Fetch data */
		$this->DB->build( array( 'select'   => 'c.*, c.member_id as comment_member_id',
								 'from'     => array( 'blog_comments' => 'c' ),
								 'where'    => 'c.comment_id IN (' . implode( ',', $ids ) . ')',
								 'add_join' => array( array( 'select' => 'e.*',
															 'from'   => array( 'blog_entries' => 'e' ),
															 'where'  => 'e.entry_id=c.entry_id' ),
													  array( 'select' => 'b.*, b.member_id as blog_owner_id',
															 'from'   => array( 'blog_blogs' => 'b' ),
															 'where'  => 'b.blog_id=e.blog_id' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Reset member ID */
			$comment = $row;
			$blog    = $row;
			
			$comments[ $row['comment_id'] ] = $row;
			$comment['member_id']           = $row['comment_member_id'];
			$blog['member_id']              = $row['blog_owner_id'];
			
			/* Basic permission checks */
			if ( $this->allowDelComment( $blog, $comment, $memberData ) )
			{
				$blogIds[]  = $row['blog_id'];
				$entryIds[] = $row['entry_id'];
				$finalIds[] = $row['comment_id'];
			}
		}
		
		/* Got any ids? */
		if ( ! count( $finalIds ) )
		{
			return false;
		}
		
		/* Delete comments */
		$this->DB->delete( 'blog_comments', "comment_id IN (" . implode( ",", $finalIds ) . ") AND entry_id IN(" . implode( ",", $entryIds ) . ')' );
	
		/* Rebuild the entry */
		if ( count( $entryIds ) )
		{
			foreach( $entryIds as $eid )
			{
				$this->rebuildEntry( $eid );
			}
		}
		
		/* Rebuild blogs */
		if ( count( $blogIds ) )
		{
			foreach( $blogIds as $blogid )
			{
				$this->rebuildBlog( $blogid );
			}
		}
		
		/* Update the Blog stats */
		$cache = $this->cache->getCache('blog_stats');
		
		$cache['stats_num_comments'] -= count( $finalIds );
		
		$this->cache->setCache( 'blog_stats', $cache, array( 'array' => 1 ) );
		
		return true;
	}
	
	/**
	 * Delete entries
	 *
	 * @param	array		ID of entry_ids
	 * @param	int			Approved=1, unapproved=0
	 * @param	array		Member data of user that activated request, uses $this->memberData by default
	 * @return  bool
	 */
	public function changeEntryApproval( $ids, $approved=1, $member=null )
	{
		/* INIT */
		$comments   = array();
		$finalIds   = array();
		$blogIds    = array();
		$entries    = array();
		$status     = ( $approved ) ? 'published' : 'draft';
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		$_goPing    = false;
		
		/* Got anything? */
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			return false;
		}
		
		/* Fetch data */
		$this->DB->build( array( 'select'   => 'e.*',
								 'from'     => array( 'blog_entries' => 'e' ),
								 'where'    => 'e.entry_id IN (' . implode( ',', $ids ) . ')',
								 'add_join' => array( array( 'select' => 'b.*, b.member_id as blog_owner_id',
															 'from'   => array( 'blog_blogs' => 'b' ),
															 'where'  => 'b.blog_id=e.blog_id' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Reset member ID */
			$blog              = $row;
			$blog['member_id'] = $row['blog_owner_id'];
			$blog              = $this->buildBlogData( $blog, $memberData );
			
			/* Basic permission checks */
			if ( $status == 'draft' )
			{
				if ( $this->allowDraft( $blog, $memberData ) )
				{
					$blogIds[]  = $row['blog_id'];
					$finalIds[] = $row['entry_id'];
					$entries[ $row['entry_id'] ] = $row;
				}
			}
			else
			{
				if ( $this->allowPublish( $blog, $memberData ) )
				{
					$blogIds[]  = $row['blog_id'];
					$finalIds[] = $row['entry_id'];
					$entries[ $row['entry_id'] ] = $row;
				}
			}
		}
		
		/* Got any ids? */
		if ( ! count( $finalIds ) )
		{
			return false;
		}
		
		/* We are good to update */
		$this->DB->update( 'blog_entries', array( 'entry_status' => $status ), "entry_id IN (" . implode( ",", $finalIds ) . ")" );

		/* Extra work when we hit something as published */
		if ( $approved )
		{
			$_ids = array();

			/* Loop through and reset any future dates to now */
			foreach( $entries as $id => $entry )
			{
				if ( $entry['entry_future_date'] )
				{
					$_ids[] = $entry['entry_id'];
				}
			}
			
			/* Update if found */
			if ( count( $_ids ) )
			{
				$this->DB->update( 'blog_entries', array( 'entry_date' => time(), 'entry_future_date' => 0 ), "entry_id IN (" . implode( ",", $_ids ) . ")" );
			}
			
			/* Active the pings */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_updatepings',
									 'where'  => "blog_id IN (" . implode(",", $blogIds ) . ") AND entry_id IN (" . implode( ",", $finalIds ) . ')' ) );
			$tp = $this->DB->execute();
			
			while( $entry = $this->DB->fetch( $tp ) )
			{
				if ( $entries[ $entry['entry_id'] ]['entry_status'] == 'draft' )
				{
					$this->DB->update( 'blog_updatepings', array( 'ping_active' => 1 ), "blog_id={$entry['blog_id']} AND entry_id={$entry['entry_id']}" );
					$_goPing = true;
				}
			}
			
			if ( $_goPing )
			{
				/* Setup Task Object */
				$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );		
				$task = new $classToLoad( $this->registry );
				
				/* Task Dates */
				$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key='blogpings'" ) );
				$newdate   = $task->generateNextRun($this_task);
				
				/* Activate Task */
				$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_enabled' => 1 ), "task_id=".$this_task['task_id'] );
				$task->saveNextRunStamp();
			}
			
			/* Send out like notifications */
			$__blogs = $this->loadBlog($blogIds);
			
			if( isset( $__blogs['blog_id'] ) )
			{
				$__blogs = array( $__blogs['blog_id'] => $__blogs );
			}
			
			foreach( $entries as $id => $entry )
			{
				$this->sendBlogLikeNotifications($__blogs[$entry['blog_id']], $entry);
			}
		}
		
		/* Update Tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		classes_tags_bootstrap::run( 'blog', 'entries' )->updateVisibilityByMetaId( $finalIds, ( $approved ) ? 1 : 0 );
		
		/* Rebuild blogs */
		if ( count( $blogIds ) )
		{
			foreach( $blogIds as $blogid )
			{
				$this->updateCategoryMappings( $blogid, $finalIds, $status, ( $approved ) ? 0 : 1 );
				$this->rebuildBlog( $blogid );
			}
		}
		
		/* Reset cblocks */
		if( $this->settings['blog_cblock_cache'] )
		{
			$this->DB->update( 'blog_cblock_cache', array( 'cbcache_refresh' => 1 ), "blog_id IN (" . implode( ",", $blogIds ) . ") AND cbcache_key in('minicalendar','lastentries','lastcomments')", true );
		}
		
		/* Reset RSS but don't tell the guardian of our SVN: Terabye, he notices ALL our commits. Scary */
		$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ), "blog_id in(0," . implode( ",", $blogIds ) . ")" );
		
		/* Update the Blog stats */
		$this->rebuildStats();
	}
	
	/**
	 * Delete entries
	 *
	 * @param	array		ID of entry_ids
	 * @param	array		Member data of user that activated request, uses $this->memberData by default
	 * @return  bool
	 */
	public function deleteEntries( $ids, $member=null )
	{
		/* INIT */
		$comments   = array();
		$finalIds   = array();
		$blogIds    = array();
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		
		/* Got anything? */
		if ( ! is_array( $ids ) OR ! count( $ids ) )
		{
			return false;
		}
		
		/* Fetch data */
		$this->DB->build( array( 'select'   => 'e.*',
								 'from'     => array( 'blog_entries' => 'e' ),
								 'where'    => 'e.entry_id IN (' . implode( ',', $ids ) . ')',
								 'add_join' => array( array( 'select' => 'b.*, b.member_id as blog_owner_id',
															 'from'   => array( 'blog_blogs' => 'b' ),
															 'where'  => 'b.blog_id=e.blog_id' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Reset member ID */
			$blog              = $row;
			$blog['member_id'] = $row['blog_owner_id'];
			
			/* Basic permission checks */
			if ( $this->allowDelEntry( $blog, $memberData ) )
			{
				$blogIds[]  = $row['blog_id'];
				$finalIds[] = $row['entry_id'];
			}
		}
		
		/* Got any ids? */
		if ( ! count( $finalIds ) )
		{
			return false;
		}
		
		/* Fetch attach class */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type = 'blogentry';
		$class_attach->init();
		
		/* Remove attachments */
		$class_attach->bulkRemoveAttachment( $finalIds );		

		/* Delete remaining entry things */
		$this->DB->delete( 'blog_comments'	, "entry_id IN (" . implode( ",", $finalIds ) . ")" );
		$this->DB->delete( 'blog_trackback'	, "entry_id IN (" . implode( ",", $finalIds ) . ")" );
		$this->DB->delete( 'blog_polls'		, "entry_id IN (" . implode( ",", $finalIds ) . ")" );
		$this->DB->delete( 'blog_voters'	, "entry_id IN (" . implode( ",", $finalIds ) . ")" );
		$this->DB->delete( 'blog_entries'	, "entry_id IN (" . implode( ",", $finalIds ) . ")" );
		$this->DB->delete( 'blog_this'      , "bt_entry_id IN (" . implode( ",", $finalIds ) . ")" );
		
		/* Delete likes */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like = classes_like::bootstrap( 'blog', 'entries' );
		$_like->remove( $finalIds );
		
		/* Delete Tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		classes_tags_bootstrap::run( 'blog', 'entries' )->deleteByMetaId( $finalIds );
		
		/* Rebuild blogs */
		if ( count( $blogIds ) )
		{
			foreach( $blogIds as $blogid )
			{
				$this->deleteCategoryMappings( $blogid, $finalIds );
				$this->rebuildBlog( $blogid );
			}
		}
		
		/* Reset RSS */
		$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ), "blog_id in(0," . implode( ",", $finalIds ) . ")", true );
		
		/* Update the Blog stats */
		$this->rebuildStats();
	}
	
	/**
	 * Ensure that the user "owns" this blog (either directly or via group blog)
	 *
	 * @param	array		Array of blog data or blog ID
	 * @param	array		Array of member data or member ID or null(default) to use current member
	 * @return  bool
	 */
	public function ownsBlog( $blog, $member=null )
	{
		// Get member data:
		$memberData = null;
		
		if(is_null($member))
		{
			$memberData = $this->memberData;
		}
		else
		{
			if( is_array($member) && !empty( $member['member_id'] ) )
			{
				$memberData = $member;
			}
			elseif(is_numeric($member))
			{
				$memberData = IPSMember::load( intval( $member ), 'all' );
			}
		}
		
		if(is_null($memberData))
		{
			return false;
		}
		
		// Get blog data:
		if(is_numeric($blog))
		{
			$blog = $this->loadBlog(intval($blog), true);
		}

		$blog['member_id'] = ( $blog['member_id'] ) ? $blog['member_id'] : $blog['blog_owner_id'];
		
		/* Simple right now */
		if ( $blog['member_id'] == $memberData['member_id'] )
		{
			return true;
		}

		/* Group blog? */		
		if($blog['blog_groupblog'] && IPSMember::isInGroup($memberData, explode(',', IPSText::cleanPermString($blog['blog_groupblog_ids']))))
		{
			return true;
		}
		
		/* Fallback */
		$_blogs = $this->fetchMyBlogs( $memberData );
		
		if ( is_array( $_blogs[ $blog['blog_id'] ] ) AND $_blogs[ $blog['blog_id'] ]['_type'] == 'group' )
		{
			return true;
		}
		
		return false;
	}	
	
	/**
	 * Allow entry locking
	 *
	 * @param  array  $blog
	 * @return bool
	 */
	public function allowLocking( $blog )
	{
		if ( $this->ownsBlog( $blog ) && $this->memberData['g_blog_allowownmod'] )
		{
			return true;
		}
		if ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_lock'] )
		{
			return true;
		}
		return false;
	}

	/**
	 * Allow commenting on closed entries
	 *
	 * @return	@e void
	 */
	public function allowReplyClosed( $blog )
	{
		if ( $this->ownsBlog( $blog ) && $this->memberData['g_blog_allowownmod'] )
		{
			return true;
		}
		
		if ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_close'] )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Allow comment editing
	 *
	 * @param	array		$blog		Blog data
	 * @param	array		$comment	Comment data
	 * @return	@e bool
	 */
	public function allowEditComment( $blog, $comment=array() )
	{
		if( $this->ownsBlog( $blog ) && $this->memberData['g_blog_allowownmod'] )
		{
			return true;
		}

		if( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_edit_comments'] )
		{
			return true;
		}

		if( ( $this->memberData['member_id'] == $comment['comment_author_id'] ) && $this->memberData['g_edit_posts'] )
		{
			if( $this->memberData['g_edit_cutoff'] > 0 )
			{
				if( $comment['comment_date'] > ( time() - ( intval( $this->memberData['g_edit_cutoff'] ) * 60 ) ) )
				{
					return true;
				}
			}
			else
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Allow comment deletion
	 *
	 * @param  array  $blog
	 * @param  array  $comment
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowDelComment( $blog, $comment="", $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if( $this->ownsBlog( $blog, $memberData ) && $memberData['g_blog_allowownmod'] )
		{
			return true;
		}
		if( $memberData['g_is_supmod'] OR $memberData['_blogmod']['moderate_can_del_comments'] )
		{
			return true;
		}
		if( $memberData['member_id'] == $comment['comment_author_id'] && $memberData['g_delete_own_posts'] )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Allow trackback deletion
	 *
	 * @param  array  $blog
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowDelTrackback( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if( $this->ownsBlog( $blog, $memberData ) && $memberData['g_blog_allowownmod'] )
		{
			return true;
		}
		if( $memberData['g_is_supmod'] or $memberData['_blogmod']['moderate_can_del_trackback'] )
		{
			return true;
		}
		return false;
	}

	/**
	 * Allow entry editing
	 *
	 * @param  array  $blog
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowEditEntry( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if( $blog['allow_entry'] )
		{
			return true;
		}

		if( $memberData['g_is_supmod'] or $memberData['_blogmod']['moderate_can_edit_entries'] )
		{
			return true;
		}
		return false;
	}

	/**
	 * Allow entry deletion
	 *
	 * @param  array  $blog
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowDelEntry( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if ( ! isset( $blog['allow_entry'] ) )
		{
			$blog = $this->buildBlogData( $blog, $member );
		}
	
		if( $blog['allow_entry'] )
		{
			return true;
		}
		if( $memberData['g_is_supmod'] or $memberData['_blogmod']['moderate_can_del_entries'] )
		{
			return true;
		}
		return false;
	}

	/**
	 * Allow entry as draft
	 *
	 * @param  array  $blog
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowDraft( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if ( ! isset( $blog['allow_entry'] ) )
		{
			$blog = $this->buildBlogData( $blog, $member );
		}
		
		if( $blog['allow_entry'] && ( $this->ownsBlog( $blog, $memberData ) ) )
		{
			return true;
		}
		if( $memberData['g_is_supmod'] or $memberData['_blogmod']['moderate_can_view_draft'] )
		{
			return true;
		}

		return false;
	}
	 
	/**
	 * Allow entry publishment
	 *
	 * @param  array  $blog
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowPublish( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if ( ! isset( $blog['allow_entry'] ) )
		{
			$blog = $this->buildBlogData( $blog, $member );
		}
		
		if( $blog['allow_entry'] && ( ( $this->ownsBlog( $blog, $memberData ) ) or in_array( $memberData['member_id'], $blog['blog_settings']['editors'] ) ) && ! ( isset( $memberData['g_blog_preventpublish'] ) && $memberData['g_blog_preventpublish'] ) )
		{
			return true;
		}
		
		if( $memberData['g_is_supmod'] or $memberData['_blogmod']['moderate_can_publish'] )
		{
			return true;
		}

		return false;
	}

	/**
	 * Allow approve comments
	 *
	 * @param  array  $blog
	 * @param  array  $member (uses memberData)
	 * @return bool
	 */
	public function allowApprove( $blog, $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND isset( $member['member_id'] ) ) ? $member : $this->memberData;
		
		if( $this->ownsBlog( $blog, $memberData ) )
		{
			return true;
		}
		
		if( $memberData['g_is_supmod'] or $memberData['_blogmod']['moderate_can_approve'] )
		{
			return true;
		}
		
		if( $blog['_is_editor'] )
		{
			return true;
		}
				
		return false;
	}

	/**
	 * Build dropdown box
	 *
	 * @param  array   $values
	 * @param  mixed   $selected
	 * @return string
	 */
	public function buildDropDown( $values, $selected )
	{
		$return_html = "";

		foreach ( $values as $val )
		{
			if ( $selected == $val )
			{
				$return_html .= "<option value='".$val."' selected='selected'>{$val}</option>";
			}
			else
			{
				$return_html .= "<option value='".$val."'>{$val}</option>";
			}
		}

		return $return_html;
	}

	/**
	 * Build key-value dropdown
	 *
	 * @param  array   $values
	 * @param  mixed   $selected
	 * @return string
	 */
	public function buildKeyValDropDown ( $values, $selected )
	{
		$return_html = "";

		foreach( $values as $key => $val )
		{
			if( $selected == strtolower( $key ) )
			{
				$return_html .= "<option value='".strtolower($key)."' selected='selected'>{$val}</option>";
			}
			else
			{
				$return_html .= "<option value='".strtolower($key)."'>{$val}</option>";
			}
		}

		return $return_html;
	}

	/**
	 * Build Blog permissions
	 *
	 * @param	mixed	Member null is default and uses $this->memberData
	 * @return	@e void
	 */
	public function buildPerms( $member=null )
	{
		$memberData = ( $member != null AND is_array( $member ) AND $member['member_id'] ) ? $member : $this->memberData;
		
		/* Unserialize the settings */
		if( !is_array( $memberData['g_blog_settings'] ) )
		{
			$memberData['g_blog_settings'] = unserialize( $memberData['g_blog_settings'] );
		}

		$blog_perms['g_blog_allowview']	   = $memberData['g_blog_settings']['g_blog_allowview'];
		$blog_perms['g_blog_allowcomment'] = $memberData['g_blog_settings']['g_blog_allowcomment'];
		$blog_perms['g_blog_allowcreate']  = $memberData['g_blog_settings']['g_blog_allowcreate'];
		$blog_perms['g_blog_allowdelete']  = $memberData['g_blog_settings']['g_blog_allowdelete'];
		$blog_perms['g_blog_allowlocal']   = $memberData['g_blog_settings']['g_blog_allowlocal'];
		$blog_perms['g_blog_allowownmod']  = $memberData['g_blog_settings']['g_blog_allowownmod'] || $memberData['g_is_supmod'] ? 1 : 0;
		$blog_perms['g_blog_rsspergo']     = ! empty( $memberData['g_blog_settings']['g_blog_rsspergo'] ) ? intval( $memberData['g_blog_settings']['g_blog_rsspergo'] ) : 0;
		
		/* Authorized Blogs Array */
		$blog_perms['g_blog_authed_blogs'] = array();
		
		if( $memberData['member_id'] > 0 )
		{
			$this->DB->build( array( 
									'select' => 'blog_id', 
									'from'	 => 'blog_blogs', 
									'where'	 => "blog_authorized_users LIKE '%,{$memberData['member_id']},%'" 
								)	);
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$blog_perms['g_blog_authed_blogs'][] = $row['perm_type_id'];
			}
		}
		
		$modPerms = array( 
							'moderate_can_edit_comments'	=> 0,
							'moderate_can_edit_entries'		=> 0,
							'moderate_can_del_comments'		=> 0,
							'moderate_can_del_entries'		=> 0,
							'moderate_can_lock'				=> 0,
							'moderate_can_publish'			=> 0,
							'moderate_can_approve'			=> 0,
							'moderate_can_editcblocks'		=> 0,
							'moderate_can_view_draft'		=> 0,
							'moderate_can_view_private'		=> 0,
							'moderate_can_warn'				=> 0,
							'moderate_can_pin'				=> 0,
							'moderate_can_disable'			=> 0,
							'moderate_can_del_trackback'	=> 0 
						);

		$mgroups = explode( ',', $memberData['mgroup_others'] );
		if( is_array( $this->cache->getCache('blogmods') ) && count( $this->cache->getCache('blogmods') ) )
		{
			foreach( $this->cache->getCache('blogmods') as $r )
			{
				if( ( $r['moderate_mg_id'] == $memberData['member_id']&& $r['moderate_type'] == 'member' ) or
					( $r['moderate_mg_id'] == $memberData['member_group_id'] && $r['moderate_type'] == 'group' ) or
					( in_array( $r['moderate_mg_id'], $mgroups ) && $r['moderate_type'] == 'group' ) )
				{
					foreach( $r as $key => $auth )
					{
						if( $auth > $modPerms[$key] )
						{
							$modPerms[$key] = $auth;
						}
					}
				}
			}
		}
		
		$blog_perms['_blogmod'] = $modPerms;

		foreach( $blog_perms as $k => $v )
		{
			$memberData[ $k ] = $v;
		}
		
		return $memberData;
	}

	/**
	 * Rebuild Blog stats
	 *
	 * @return	@e void
	 */
	public function rebuildStats()
	{
		$cache = array( 'recent_entries' => array(), 'featured' => array(), 'fp_entries' => array() );
		
		$stats = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as num_blogs',
												  'from'	=> 'blog_blogs')	);
		
		$cache['stats_num_blogs']		= $stats['num_blogs'];
		
		$stats = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as num_entries', 'from' => 'blog_entries' ) );
		$cache['stats_num_entries'] 	= $stats['num_entries'];
		
		$stats = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as num_comments', 'from' => 'blog_comments' ) );
		$cache['stats_num_comments']	= $stats['num_comments'];
		
		$last_blog = $this->DB->buildAndFetch( array( 'select' => 'blog_id, blog_name, member_id',
													  'from'   => 'blog_blogs',
													  'where'  => "blog_view_level='public' AND blog_disabled=0",
													  'order'  => 'blog_id DESC',
													  'limit'  => array( 0, 1 )
											  )		 );
		
		$cache['stats_last_blog_id']		= $last_blog['blog_id'];
		$cache['stats_last_blog_name']		= $last_blog['blog_name'];
		$cache['seo_stats_last_blog_name']	= IPSText::makeSeoTitle( $last_blog['blog_name'] );
		$cache['stats_last_blog_mid']		= $last_blog['member_id'];
		$cache['stats_last_blog_mname']		= '';
		$cache['stats_last_blog_seoname']	= '';
		
		if ( $last_blog['member_id'] )
		{
			$member = $this->DB->buildAndFetch( array( 'select' => 'members_display_name,members_seo_name', 'from' => 'members', 'where' => "member_id={$last_blog['member_id']}" ) );
			
			$cache['stats_last_blog_mname']	= $member['members_display_name'];
			$cache['stats_last_blog_seoname'] = $member['members_seo_name'];
		}
		
		/* Fetch featured blog IDs */
		$this->DB->build( array( 'select' => 'blog_id, entry_id',
								 'from'   => 'blog_entries',
								 'where'  => 'entry_featured=1' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$cache['featured'][ $row['blog_id'] ] = $row['entry_id'];
		}
		
		/* Fetch pinned blog IDs */
		$this->DB->build( array( 'select' => 'blog_id',
								 'from'   => 'blog_blogs',
								 'where'  => 'blog_pinned=1 AND blog_disabled=0' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$cache['pinned'][ $row['blog_id'] ] = $row['blog_id'];
		}
		
		/* Last 20 entry IDs */
		$this->DB->build( array('select'   => 'e.entry_id, e.entry_last_update, e.entry_name, e.blog_id, e.entry_name_seo, e.entry_author_id, e.entry_date',
								'from'     => array('blog_entries' => 'e' ),
								'where'    => "e.entry_status='published' AND b.blog_view_level='public' AND b.blog_disabled=0",
								'order'    => 'e.entry_date DESC',
								'limit'    => array( 0, 20 ),
								'add_join' => array( array( 'select' => 'b.blog_name, b.blog_seo_name',
															'from'   => array( 'blog_blogs' => 'b' ),
															'where'  => 'b.blog_id=e.blog_id',
															'type'   => 'left' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$cache['recent_entries'][ $row['entry_id'] ] = $row['blog_id'];
		}
		
		/* Grab 50 IDs for the main page (last X entries grouped by blog_id) */
		$this->DB->build( array('select'   => 'MAX(e.entry_id) as max, MAX(e.entry_date) as maxdate',
								'from'     => array('blog_entries' => 'e' ),
								'where'    => "e.entry_status='published' AND e.entry_banish=0 AND e.entry_featured=0 AND b.blog_view_level='public' AND b.blog_disabled=0",
								'group'    => 'e.blog_id, b.blog_name, b.blog_seo_name',
								'order'    => 'maxdate DESC',
								'limit'    => array( 0, 50 ),
								'add_join' => array( array( 'select' => 'b.blog_id, b.blog_name, b.blog_seo_name',
															'from'   => array( 'blog_blogs' => 'b' ),
															'where'  => 'b.blog_id=e.blog_id',
															'type'   => 'left' ) ) ) );
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			$cache['fp_entries'][ $row['max'] ] = $row['blog_id'];
		}
		
		$this->cache->setCache( 'blog_stats', $cache, array( 'array' => 1 ) );
	}
	
	/**
	 * Cut post after maxlength
	 *
	 * @param  string	$source
	 * @param  integer	$maxlength
	 * @return string
	 */
	public function cutPost( $source, $maxlength )
	{
		$source = preg_replace('/\<img(.*)?src\=\'([^\']+)\'([^\>]+)?\>/', '$2', $source);
		$source = strip_tags($source);
		
		if ( IPSText::mbstrlen( $source ) > $maxlength )
		{
			$length = 0;
			$tagstack = array();
			$target = "";

			while ( strlen( $source ) > 0 )
			{
				$start = strpos( $source, "<" );
				$end = strpos( $source, ">", $start );

				if ( $start === false or $end === false )
				{
					$target .= substr( $source, 0, $maxlength - $length );
					$endpos = strrpos( $target, " " );
					if ( $endpos > 0 )
					{
						$target = substr( $target, 0, $endpos );
					}
					$target .= "...";
					$source = "";
				}
				else
				{
					if ( ( $length + $start + 1 ) > $maxlength )
					{
						$target .= substr( $source, 0, $maxlength - $length );
						
						$endpos = strrpos( $target, ">" );
						
						if ( $endpos )
						{
							$target = substr( $target, 0, $endpos + 1 );
						}
						else
						{
							$endpos = strrpos( $target, " " );
							
							if ( $endpos > 0 )
							{
								$target = substr( $target, 0, $endpos );
							}
						}
						
						$target .= "...";
						$source = "";
					}
					else
					{
						if ( substr( $source, $end-1, 1 ) == "/" )
						{
							$length += $start + 1;
							$target .= substr( $source, 0, $end+1 );
							$source = substr( $source, $end+1 );
						}
						else
						{
							$tag = trim( substr( $source, $start+1, $end-$start-1 ) );
							
							if ( substr( $tag, 0, 3 ) != "!--" )
							{
								if ( substr( $tag, 0, 1 ) == "/" )
								{
									$tag = substr( $tag, 1 );
									$endtag = 1;
								}
								else
								{
									$endtag = 0;
								}
								
								$tagendpos = strpos($tag, " ");
								
								if ( $tagendpos > 0 )
								{
									$tag = substr( $tag, 0, $tagendpos );
								}
								if ( $endtag )
								{
									$found = 0;
									$removedtags = array();
									while (!$found and $stacktag = array_pop( $tagstack ) )
									{
										if ( $stacktag == $tag )
										{
											$found = 1;
										}
										else
										{
											array_push( $removedtags, $stacktag);
										}
									}
									while ( $stacktag = array_pop( $removedtags ) )
									{
										array_push( $tagstack, $stacktag);
									}
								}
								else
								{
									array_push( $tagstack, $tag );
								}
							}
							$length += $start + 1;
							$target .= substr( $source, 0, $end+1 );
							$source = substr( $source, $end+1 );
						}
					}
				}
			}

			while ( $stacktag = array_pop( $tagstack ) )
			{
				$target .= "</".$stacktag.">";
			} 
			return $target;
		}
		else
		{
			return $source;
		}
	}
	
	/**
	 * Returns JS to control themes
	 *
	 * @param  array   $blog
	 * @return string
	 */
	public function getThemeJs( $blog )
	{
		if( !$this->settings['blog_themes'] )
		{
			return '';
		}
		
		$themes = array();
		$_themes = array();
		
		if( is_array($this->caches['blog_themes']) AND count($this->caches['blog_themes']) )
		{
			foreach( $this->caches['blog_themes'] as $k => $r )
			{
				$themes[ $r['theme_name'] . $r['theme_id'] ] = $r;
			}
		}

		ksort($themes);

		$total_themes = count($themes);

		if( $total_themes )
		{
			foreach( $themes as $theme )
			{
				$_themes[] = array(
									'name'		=>	$theme['theme_name'],
									'id'		=>	$theme['theme_id'],
									'selected'	=>	( $theme['theme_id'] == $blog['blog_theme_id'] ) ? 1 : 0
								);
			}
		}
		
		/* Check for custom theme */
		$_customTheme = '';
		
		if ( $blog['blog_theme_custom'] )
		{
			$_customTheme = $blog['blog_theme_custom'];
		}
		elseif ( $blog['blog_theme_final'] )
		{
			$_customTheme = $blog['blog_theme_final'];
		}
		elseif ( $blog['blog_theme_id'] && isset($this->caches['blog_themes'][ $blog['blog_theme_id'] ]['theme_css']) )
		{
			$_customTheme = $this->caches['blog_themes'][ $blog['blog_theme_id'] ]['theme_css'];
		}
		
		if ( $_customTheme )
		{
			$_customTheme = str_replace( "\n", '~~~__NL__~~~', $_customTheme );
		}
		
		return $this->registry->output->getTemplate( 'blog_global' )->theme_menu( $_themes, $blog, $_customTheme );
	}
	
	/**
	 * Sets a new blog theme
	 *
	 * @param  array  $blog
	 * @return	@e void
	 */
	public function changeTheme( $blog )
	{
		if( !$this->settings['blog_themes'] )
		{
			return $blog;
		}
		
		if( $this->ownsBlog($blog, $this->memberData) )
		{
			$id = intval($this->request['changeTheme']);

			if( !$id )
			{
				$this->DB->update( 'blog_blogs', array( 'blog_theme_id' => 0, 'blog_theme_custom' => '', 'blog_theme_final' => '', 'blog_theme_approved' => 0 ), 'blog_id=' . $blog['blog_id'] );
				$blog['blog_theme_id']		= 0;
				$blog['blog_theme_final']	= '';
				$blog['blog_theme_custom']	= '';

				return $blog;
			}

			$theme = $this->caches['blog_themes'][ $id ];

			if( !$theme['theme_id'] )
			{
				return $blog;
			}

			$this->DB->update( 'blog_blogs', array( 'blog_theme_id' => $theme['theme_id'] ), 'blog_id=' . $blog['blog_id'] );

			$blog['blog_theme_id'] = $theme['theme_id'];
		}
		
		return $blog;
	}
		
	/**
	 * Returns JS for controlling content blocks
	 *
	 * @param  array   $blog
	 * @return string
	 */
	public function getCBlockJs( $blog )
	{
		if ( ! $blog['allow_entry'] )
		{
			return "";
		}
		
		$_cblocks  = array();
		$__cblocks = $this->registry->getClass('cblocks')->fetchAllContentBlocks( $blog );
	
		if ( ! is_array( $__cblocks ) OR ! count( $__cblocks ) )
		{
			return '';
		}
				
		if ( $this->settings['blog_allow_cblockchange'] )
		{
			if ( is_array( $__cblocks ) )
			{
				foreach( $__cblocks as $cblock )
				{ 
					/* Skip... */
					if ( isset( $cblock['cbdef_enabled'] ) AND ! $cblock['cbdef_enabled'] )
					{
						continue;
					}
					
					if ( $cblock['cbdef_locked'] )
					{
						continue;
					}
					
					$cblock['cbdef_name'] = ( $this->lang->words[ 'cblock_' . $cblock['cbdef_function'] ] ) ? $this->lang->words[ 'cblock_' . $cblock['cbdef_function'] ] : $cblock['cbdef_name'];										
					if ( $cblock['cblock_id'] )
					{
						if ( ! $cblock['cblock_show'] )
						{
							$lang = $this->lang->words['cblock_' . $cblock['cbdef_function'] ];
							
							if( !$lang )
							{
								$lang = $cblock['cbdef_name'] ? $cblock['cbdef_name'] : $cblock['cbcus_name'];
							}
	
							if ( $cblock['cbdef_function'] == 'get_admin_block' )
							{
								$_cblocks[] = array( $cblock['cblock_id'], $lang, $cblock );
							}
							else
							{
								$_cblocks[] = array( $cblock['cblock_id'], $lang, $cblock );
							}
						}
					}
					else
					{
						if ( $cblock['cbdef_function'] == 'get_admin_block' )
						{
							$_cblocks[] = array( $cblock['cbdef_id'], $cblock['cbdef_name'], $cblock, 1 );
						}
						else
						{
							$_cblocks[] = array( $cblock['cbdef_id'], $cblock['cbdef_name'], $cblock, 1 );
						}
					}
				}
			}
		}
		
		$output = $this->registry->output->getTemplate( 'blog_global' )->cblock_menu( $_cblocks );

		return $output;
	}

	/**
	 * Calculate an excerpt of an entry
	 *
	 * @param  array  $entry
	 * @return string
	 */
	public function getEntryExcerpt( $entry )
	{
		/* Got entry data? */
		if ( is_array( $entry ) )
		{
			$blogId   = intval( $entry['blog_id'] );
			$entry_id = intval( $entry['entry_id'] );
		}
		/* Only the entry ID? */
		elseif ( is_int($entry) )
		{				
			$entry = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'blog_entries',
													  'where'  => 'entry_id=' . intval( $entry ) ) );
									 
			$blogId   = intval( $entry['blog_id'] );
			$entry_id = intval( $entry['entry_id'] );
		}
		
		/* Got something to return? */
		if ( isset($entry['entry_short']) && $entry['entry_short'] != '' )
		{
			return $entry['entry_short'];
		}
		elseif( isset($entry['entry']) )
		{
			/* Get our entry, unconvert smilies and convert BR tags */
			$brDone = false;
			$_entry = $entry['entry'];
			$_entry = IPSText::unconvertSmilies( $_entry );
			
			if ( stristr( $_entry, '<br />' ) || stristr( $_entry, '<br>' ) )
			{
				$_entry = IPSText::br2nl( $_entry );
				$brDone = true;
			}
			
			/* First cut the entry so we can avoid broken html tags more easily and have images too... */
			$start_extract = strpos( $_entry, '[extract]' );
			$end_extract   = strpos( $_entry, '[/extract]' );
			
			if ( $start_extract !== false && $end_extract !== false )
			{
				$start_extract += 9;
				$_entry = substr( $_entry, $start_extract, $end_extract - $start_extract );
				// We won't cut the entry if we have bot [extract] tags! :)
				//$_entry = $this->cutPost( $_entry, $this->settings['blog_entry_short'] );
			}
			else
			{
				$_entry = $this->cutPost( $_entry, $this->settings['blog_entry_short'] );
			}
			
			/* Convert media */
			#$_entry = $this->shortenMedia( $_entry );
			
			/* Ensure we have some data */
			$entry['entry_use_emo']    = isset($entry['entry_use_emo'])    ? $entry['entry_use_emo']    : 1;
			$entry['entry_html_state'] = isset($entry['entry_html_state']) ? $entry['entry_html_state'] : 0;
			
			/* Revert br tags.. */
			if ( $brDone )
			{
				$_entry = nl2br( $_entry );
			}
			
			/* Setup parsing options */
			IPSText::getTextClass('bbcode')->parse_html				= $entry['entry_html_state'] ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_nl2br			= $entry['entry_html_state'] == 2 ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_smilies			= $entry['entry_use_emo'] ? 1: 0;
			IPSText::getTextClass('bbcode')->parsing_section		= 'blog_entry';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $entry['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $entry['mgroup_others'];
			
			/* Parse for display */
			$_entry = IPSText::getTextClass('bbcode')->preDisplayParse( $_entry );
			$_entry = IPSText::stripAttachTag( trim($_entry) );
			$_entry = IPSText::getTextClass('bbcode')->stripAllTags( $_entry, false );
			
			/* We need the bbcode parser to parse emoticons.. */
			if ( ! is_object($this->bbclass) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/bbcode/core.php', 'class_bbcode_core' );
				$this->bbclass = new $classToLoad( $this->registry );
			}
			
			/* Parse back emoticons for short entries - only IPB 3.3+ */
			if ( method_exists( $this->bbclass, 'parseEmoticons' ) )
			{
				$_entry = $this->bbclass->parseEmoticons( $_entry );
			}
			
			/* Save in DB...*/			
			if ( $entry_id )
			{
				$this->DB->update( 'blog_entries', array( 'entry_short' => $_entry ), 'entry_id=' . $entry_id );
			}
			
			/* ...and return the short entry! */
			return $_entry;
		}
		
		return 'ERROR - NO ENTRY DATA'; // we should never reach here..
	}
	
	/**
	* Shortens media and images for featured entries
	*
	* @param	string	$entryText
	* @return	string
	*/
	public function shortenMedia( $entryText )
	{
		/* Media tag cache */
		$mediaCache = $this->cache->getCache( 'mediatag' );

		/* Check for media tags */
		if ( stristr( $entryText, '[media' ) )
		{
			preg_match_all( "#(\[media\])((?R)|.*?)(\[/media\])#si", $entryText, $matches );
	
			foreach( $matches[0] as $idx => $match )
			{
				if( is_array( $mediaCache ) AND count( $mediaCache ) )
				{
					foreach( $mediaCache as $type => $r )
					{
						$entryText = preg_replace( "#\[media\]{$r['match']}\[/media\]#is", $this->registry->output->getTemplate('blog_global')->short_media_tag( $matches[2][$idx], $type ), $entryText );
					}
				}
			}
		}
		
		// Image
		/*if ( stristr( $entryText, '<img' ) )
		{
			$entryText = preg_replace_callback( "#<img(?:.+?)src=[\"'](\S+?)['\"][^>]+?>#is", array( $this->registry->output->getTemplate('blog_global'), 'short_img_tag' ), $entryText );
		}
		
		// Image BBCode
		if ( stristr( $entryText, '[img' ) )
		{
			$entryText = preg_replace_callback( "#\[img\](\S+?)\[/img\]#is", array( $this->registry->output->getTemplate('blog_global'), 'short_img_tag' ), $entryText );
		}
		
		// Attachments
		if ( stristr( $entryText, '[attach' ) )
		{
			$entryText = preg_replace_callback( "#\[attachment=(.+?):(.+?)\]#", array( $this->registry->output->getTemplate('blog_global'), 'short_attach_tag' ), $entryText );
		}*/
	
		return $entryText;
	}
	
	public function sendBlogLikeNotifications($blog, $entry)
	{
		// Replacements for the email:
		$replace = array(
							'NAME'		=> '-member:members_display_name-',
							'AUTHOR'	=> $entry['entry_author_name'],
							'TITLE'		=> $entry['entry_name'],
							'URL'		=> $this->registry->output->buildSEOUrl( 'app=blog&blogid=' . $blog['blog_id'] . '&showentry=' . $entry['entry_id'], 'public', $entry['entry_name_seo'], 'showentry' ),
							'BLOG'		=> $blog['blog_name'],
						);
		
		// Notification options:
		$details = array(
							'notification_key'    => 'new_entry',
							'notification_url'    => '',
							'email_template'      => 'blog_notify_new_entry',
							'email_subject'       => sprintf( $this->lang->words['blog_notify_new_entry_title'], $blog['blog_name'] ),
							'build_message_array' => $replace,
						);
		
		// Sendify:
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		return classes_like::bootstrap( 'blog', 'blog' )->sendNotifications( $blog['blog_id'], array( 'immediate', 'offline' ), $details);
	}
}