<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * RSS import class
 * by Matt Mecham
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		Matt
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		26th November 2009 (Happy Thanksgiving!)
 * @version		$Rev: 4 $
 *
*/

class blogRssImport
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
     * Post class object
     *
     * @access	protected
     * @var		object
     */
    protected $_postClass;
    
    /**
     * RSS Import object
     *
     * @access	protected
     * @var		object
     */
    protected $_rssImport;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
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
		
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
		}

		$this->blogFunctions = $this->registry->getClass('blogFunctions');
		
		/* Used by tasks */
		$this->settings['blog_max_cats']    = empty($this->settings['blog_max_cats']) ? 50 : $this->settings['blog_max_cats'];
		$this->settings['blog_entry_short'] = empty($this->settings['blog_entry_short']) ? 350 : $this->settings['blog_entry_short'];
		
		/* Grab post class (tags class is loaded internally) */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/post/blogPost.php', 'blogPost', 'blog' );
		$this->_postClass = new $classToLoad( $this->registry, TRUE );
    }
 
	/**
	 * Validate
	 * Validates a feed and returns nice error strings
	 *
	 * @access		public
	 * @param		string		Feed URL
	 * @param		string		[http_auth user]
	 * @param		string		[http_auth_pass]
	 * @return		mixed		TRUE on success, error message on fail
 	 */
 	public function validate( $url, $user='', $pass='' )
 	{
 		try
 		{
 			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/rss/import.php', 'rss_import' );
 			$this->_rssClass = new $classToLoad( $url );
 			
 			if ( $user and $pass )
 			{
 				$this->_rssClass->setAuthUser( $user );
 				$this->_rssClass->setAuthPass( $pass );
 			}
 			
 			@$this->_rssClass->validate();
 		}
 		catch( Exception $error )
 		{
 			$msg = $error->getMessage();
 			
 			return ( isset( $this->lang->words[ 'brss_' . $msg ] ) ) ? $this->lang->words[ 'brss_' . $msg ] . ' ' . implode( '<br />', $this->_rssClass->errors ) : $msg;
 		}
 		
 		return TRUE;
 	}
 	
 	/**
 	 * Process a single blog
 	 *
 	 * @return	@e int		Number of blogs processed
 	 */
 	public function runTask()
 	{
 		/* INIT */
 		$processed = 0;
 		$timeCheck = time() - ( 3600 * 2 );
 		$bids      = array();
 		
 		/* Fetch blogs to process (not been processed in 2+ hours) */
 		$this->DB->build( array( 'select' => '*',
 								 'from'   => 'blog_rssimport',
 								 'where'  => 'rss_last_import < ' . $timeCheck,
 								 'order'  => 'rss_last_import ASC',
 								 'limit'  => array( 0, 30 ) ) );
 		
 		$o = $this->DB->execute();
 		
 		while( $data = $this->DB->fetch( $o ) )
 		{
	 		/* Fetch blog data */
	 		$blog = $this->blogFunctions->loadBlog( intval( $data['rss_blog_id'] ) );
	 		
	 		/* Load blog owner */
	 		$member = IPSMember::load( $blog['member_id'], 'core, groups' );
	 		
	 		/* Set up perms */
	 		$member = $this->blogFunctions->buildPerms( $member );
	 		
	 		if ( $member['g_blog_rsspergo'] AND $blog['blog_id'] AND $member['member_id'] AND $data['rss_id'] AND $data['rss_url'] )
	 		{
		 		/* Off load to main function */
		 		$val = $this->_process( $blog, $data, $member );
		 		
		 		$bids[] = $blog['blog_id'];
		 		$processed++;
		 	}
		 	else
		 	{
		 		/* Something went wrong - delete the rss import - may want to just deactivate it maybe? */
		 		$this->DB->delete( 'blog_rssimport', 'rss_id=' . intval( $data['rss_id'] ) );
		 	}
	 	}
 		
 		if ( count( $bids ) )
 		{
	 		/* Reset RSS cache */
	 		$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ), "blog_id in(0," . implode( ",", $bids ) . ")", true );
	 		
	 		/* rebuild stats */
	 		$this->blogFunctions->rebuildStats();
	 	}
 		
 		return $processed;
 	}
 	
 	/**
 	 * Process a single blog
 	 *
 	 * @access	public
 	 * @param	mixed		(Array: array of blog data, INT blog ID)
 	 * @return	int			Number of feeds added
 	 */
 	public function processSingle( $blog )
 	{
 		#need to rsort so they insert in ID order
 	
 		/* Fetch blog data */
 		$blog = ( is_array( $blog ) ) ? $blog : $this->blogFunctions->loadBlog( intval( $blog ) );
 		
 		/* Load blog owner */
 		$member = IPSMember::load( $blog['member_id'], 'all', 'id' );
 		
 		/* Set up perms */
 		$member = $this->blogFunctions->buildPerms( $member );
 		
 		/* Fetch RSS import data */
 		$data = $this->DB->buildAndFetch( array( 'select' => '*',
 												 'from'	  => 'blog_rssimport',
 												 'where'  => 'rss_blog_id=' . intval( $blog['blog_id'] ) ) );
 		
 		/* Ensure we have DATAR FOR DIS */
 		if ( ! $data['rss_id'] OR ! $data['rss_url'] )
 		{
 			return false;
 		}
 		
 		/* Off load to main function */
 		$val = $this->_process( $blog, $data, $member );
 		
 		/* Reset RSS cache */
 		$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ), "blog_id in(0," . $blog['blog_id'] . ")", true );
 		
 		/* rebuild stats */
 		$this->blogFunctions->rebuildStats();
 	}
 	
 	/**
 	 * Process a single blog - work function
 	 *
 	 * @param	array		Blog data
 	 * @param	array		RSS Import data
 	 * @param	array		Member data
 	 * @return	int			Number of feeds added
 	 */
 	protected function _process( $blog, $data, $memberData )
 	{
 		/* Ensure we have DATAR FOR DIS */
 		if ( ! $data['rss_id'] OR ! $data['rss_url'] OR ! $memberData['g_blog_rsspergo'] OR ! $this->settings['blog_allow_rssimport'] )
 		{
 			return false;
 		}
 		
 		/* Immediately update timestamp of last import */
 		$this->DB->update( 'blog_rssimport', array( 'rss_last_import' => time() ), 'rss_id=' . $data['rss_id'] );
 		
 		/* Init the class and such like and so-forth */
 		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/rss/import.php', 'rss_import' );
 		$this->_rssClass = new $classToLoad( $data['rss_url'], 'blog-' . $blog['blog_id'] );
 			
		try
 		{
 			if ( $data['rss_auth_user'] and $data['rss_auth_pass'] )
			{
				$this->_rssClass->setAuthUser( $data['rss_auth_user'] );
				$this->_rssClass->setAuthPass( $data['rss_auth_pass'] );
			}
 			
 			$this->_rssClass->load();
 			
 			/* build */
 			$this->_rssClass->build( array( 'limit' => intval( $memberData['g_blog_rsspergo'] ) ) );
 			
 			/* Process */
 			while( $row = $this->_rssClass->fetch() )
 			{
 				/* Set up settings */
 				$this->_rssClass->setParseHtml( intval( $memberData['g_blog_do_html'] ) );
 				$this->_rssClass->setParseBbcode( 1 );
 				
 				$row['title']   = $this->_rssClass->cleanTitle( $row['title'] );
 				$row['content'] = $this->_rssClass->parseContent( $row['content'], $row['link'] );
 			
 				$entry = $this->_buildEntry( $row, $blog, $memberData, $data );
 				
 				$this->DB->setDataType( array( 'entry', 'entry_short' ), 'string' );
 				
 				/* Insert */
 				$this->DB->insert( 'blog_entries', $entry );
 				$entry['entry_id'] = $this->DB->getInsertId();
 				
 				/* Cats */
 				if ( is_string( $data['rss_cats'] ) && IPSText::cleanPermString( $data['rss_cats'] ) != '' )
 				{
 					$data['rss_cats'] = explode( ',', $data['rss_cats'] );
 				}
 				
 				if ( is_array( $data['rss_cats'] ) AND count( $data['rss_cats'] ) )
 				{
					foreach( $data['rss_cats'] as $cid )
					{
						if ( trim( $cid ) )
						{
							$_POST['catCheckBoxes'][ $cid ] = 1;
						}
					}
					
					$this->_postClass->processCategories( $blog['blog_id'], $entry['entry_id'], 'published', false );
 				}
 				
 				/* Tags */
 				$tags = unserialize( $data['rss_tags'] );
 				$_REQUEST['ipsTags'] = $tags['tags'];
 				
 				require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
				classes_tags_bootstrap::run( 'blog', 'entries' )->add( $_REQUEST['ipsTags'], array(
					'meta_id'			=> $entry['entry_id'],
					'meta_parent_id'	=> $blog['blog_id'],
					'member_id'			=> $memberData['member_id'],
					'meta_visible'		=> 1, 
					) );
 			}
 			
 			/* Do we need to rebuild? */
 			$count = $this->_rssClass->finish();
 			
 			if ( $count )
 			{
 				$this->DB->update( 'blog_rssimport', array( 'rss_count' => $data['rss_count'] + $count ), 'rss_id=' . $data['rss_id'] );
 				
 				$this->blogFunctions->rebuildBlog( $blog['blog_id'] );
 			}
 			
 			return true;
 		}
 		catch( Exception $error )
 		{
 			/* Oops, something happened here didn't it! */
 			return false;
 		}
 	}
 	
	/**
	 * Compile Post
	 * Compiles all the incoming information into an array
	 *
	 * @return array
	 */
	public function _buildEntry( $row, $blog, $memberData, $data )
	{
		/* Build the entry array */
		$entry = array( 'blog_id'			  => $blog['blog_id'],
						'entry_author_id'	  => $memberData['member_id'],
						'entry_author_name'	  => $memberData['members_display_name'],
						'entry_date'		  => $row['unixdate'],
						'entry_name'		  => $row['title'],
						'entry_name_seo'	  => IPSText::makeSeoTitle($row['title']),
						'entry'     		  => $row['content'],
						'entry_short'		  => $this->blogFunctions->getEntryExcerpt( array( 'entry_short' => '', 'entry_id' => null, 'entry' => $row['content'] ) ),
						'entry_status'		  => 'published',
						'entry_post_key'	  => md5( microtime() ),
						'entry_html_state'	  => intval( $this->memberData['g_blog_do_html'] ),
						'entry_use_emo'		  => 1,
						'entry_last_update'	  => time(),
						'entry_gallery_album' => 0,
						'entry_poll_state'    => 0,
						'entry_rss_import'	  => 1 );
		return $entry;
	}
}