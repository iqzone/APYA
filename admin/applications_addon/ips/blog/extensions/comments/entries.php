<?php
/**
 * Gallery Comments class
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_blog_entries extends classes_comments_renderer
{
	/**
	 * Internal remap array
	 *
	 * @param	array
	 */
	protected $_remap = array( 'comment_id'				=> 'comment_id',
							   'comment_author_id'		=> 'member_id',
							   'comment_author_name'	=> 'member_name',
							   'comment_text'			=> 'comment_text',
							   'comment_ip_address'		=> 'ip_address',
							   'comment_edit_date'		=> 'comment_edit_time',
							   'comment_date'			=> 'comment_date',
							   'comment_approved'		=> 'comment_approved',
							   'comment_parent_id'		=> 'entry_id'
							 );

	/**
	 * Internal parent remap array
	 *
	 * @param	array
	 */
	protected $_parentRemap = array('parent_id'			=> 'entry_id',
									'parent_owner_id'	=> 'entry_author_id',
									'parent_parent_id'  => 'blog_id',
									'parent_title'	    => 'entry_name',
									'parent_seo_title'  => 'entry_name_seo',
									'parent_date'	    => 'entry_date'
									);
							
	protected $_entries = array();
	
	protected $isAkismetSpam = null;

	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		parent::__construct();
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );
		
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			/* Gallery Object */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
		}
		
		/* Load up content blocks lib */ 
		if ( ! $this->registry->isClassLoaded('cblocks') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
			$this->registry->setClass( 'cblocks', new $classToLoad( $this->registry ) );
		}
		
		$this->memberData = $this->registry->blogFunctions->buildPerms( $this->memberData );
	}
	
	public function setEntryData($entry = null)
	{
		if(!is_null($entry) && is_array($entry))
		{
			$this->_entries[$entry['entry_id']] = $entry;
		}
	}
		
	/**
	 * Who am I?
	 * 
	 * @return	string
	 */
	public function whoAmI()
	{
		return 'blog-entries';
	}
	
	/**
	 * Comment table
	 *
	 * @return	string
	 */
	public function table()
	{
		return 'blog_comments';
	}

	/**
	 * Skin class
	 *
	 * @return	string
	 */
	public function skin()
	{
		return 'global_comments';
	}
	
	/**
	 * Fetch parent
	 *
	 * @return	array
	 */
	public function fetchParent( $id )
	{
		if ( ! $id )
		{
			print IPSDebug::prettifyBackTrace( debug_backtrace() );
		}
		
		if( !isset($this->_entries[ $id ]) )
		{
			$this->_entries[$id] = $this->DB->buildAndFetch( array(	'select' => '*',
																	'from'   => 'blog_entries',
																	'where'  => 'entry_id=' . intval($id)
															  )	);
		}
		
		return $this->_entries[$id];
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array( 'urls-showParent' => "app=blog&blogid=#{parent_parent_id}&showentry=%s&",
					  'urls-report'     => $this->getReportLibrary()->canReport( 'blog' ) ? "app=blog&module=actions&section=report&cid=%s&do=report" : '' );
	}
	
	/**
	 * Number of items per page
	 *
	 * @return	int
	 */
	public function perPage()
	{
		$blog = $this->registry->blogFunctions->getActiveBlog();
		
		return ( $blog['blog_id'] && $blog['blog_settings']['commentsperpage'] ) ? $blog['blog_settings']['commentsperpage'] : 20;
	}
	
	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string	Type of save (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	array	Array of GENERIC data
	 */
	public function preSave( $type, array $array )
	{
		if ( $type == 'add' )
		{
			/* Load image and album */
			$parent  = $this->fetchParent( $array['comment_parent_id'] );
			$blog    = $this->registry->blogFunctions->loadBlog( $parent['blog_id'] );
			
			/* Test approval */
			if ( $array['comment_approved'] )
			{
				$queued  = $this->_checkCommentQueued( $blog );
				$array['comment_approved'] = ( $queued ) ? 0 : 1;
			}
			
			/* Using Akismet? */
			if( $this->settings['blog_akismet_key'] )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/lib/akismet.class.php', 'Akismet', 'blog' );
				$akismet = new $classToLoad( $this->settings['board_url'], $this->settings['blog_akismet_key'] );
				
				# Setup some data for the logger
				$akismetData = array( 'author'		=> $array['comment_author_name'],
									  'email'		=> $array['comment_author_id'] ? $this->memberData['email'] : '',
									  'body'		=> $array['comment_text'],
									  'permalink'	=> $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$parent['blog_id']}&amp;showentry={$array['comment_parent_id']}" ),
									  'user_ip'		=> $this->member->ip_address,
									 );
				
				# ..and pass it to AKI too		 
				$akismet->setCommentType( 'comment' );
				$akismet->setCommentAuthor( $akismetData['author'] );
				$akismet->setCommentAuthorEmail( $akismetData['email'] );
				$akismet->setCommentContent( $akismetData['body'] );
				$akismet->setPermalink( $akismetData['permalink'] );
				$akismet->setUserIP( $akismetData['user_ip'] );
				
				
				try
				{
					/* So that's spam? */
					if( $akismet->isCommentSpam() )
					{
						$this->isAkismetSpam = true;
						
						if( $this->settings['blog_akismet_action'] == 'delete' )
						{
							$this->akismetLog( $akismetData, 'delete' );
	
							$this->registry->output->showError( 'blog_spam_detected', 2069 );
						}
						else if( $this->settings['blog_akismet_action'] == 'queue' )
						{
							$array['comment_approved'] = 0;
						}
					}
					else
					{
						$this->isAkismetSpam = false;
					}
				}
				catch( Exception $e )
				{
					// Log error
					$this->akismetLog( $akismetData, '', $e->getMessage() );
				}
			}
		}
		
		IPSLib::doDataHooks( $array, 'blogPre'.ucwords($type).'Comment' );
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	array	Array of GENERIC data
	 */
	public function postSave( $type, array $array )
	{
		if ( $type == 'add' )
		{
			/* Load image and album */
			$parent  = $this->fetchParent( $array['comment_parent_id'] );
			$blog    = $this->registry->blogFunctions->loadBlog( $parent['blog_id'] );
			
			/* Using Akismet? (Part 2) */
			if( !is_null($this->isAkismetSpam) )
			{
				$akismetData = array( 'author'		=> $array['comment_author_name'],
									  'email'		=> $array['comment_author_id'] ? $this->memberData['email'] : '',
									  'body'		=> $array['comment_text'],
									  'permalink'	=> $this->registry->output->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$parent['blog_id']}&amp;showentry={$array['comment_parent_id']}" ),
									  'user_ip'		=> $this->member->ip_address,
									 );
				
				if( $this->isAkismetSpam )
				{
					if( $this->settings['blog_akismet_action'] == 'queue' )
					{
						$this->akismetLog( $akismetData, 'queue' );
					}
					else
					{
						$this->akismetLog( $akismetData, 'allow' );
					}
				}
				else
				{
					$this->akismetLog( $akismetData, 'fine' );
				}
			}
	
			$this->registry->blogFunctions->rebuildEntry( $parent['entry_id'] );
			$this->registry->blogFunctions->rebuildBlog( $blog['blog_id'] );
			
			if( $this->settings['blog_cblock_cache'] ) 
			{
				$this->DB->update( 'blog_cblock_cache', array( 'cbcache_refresh' => 1 ), "blog_id={$blog['blog_id']} AND cbcache_key in('lastcomments')", true );
			}
	
			/* Update the Blog stats */
			if ( $array['comment_approved'] )
			{
				$cache = $this->cache->getCache('blog_stats');
				
				$cache['stats_num_comments']++;
			
				$this->cache->setCache( 'blog_stats', $cache, array( 'array' => 1, 'donow' => 1 ) );
			}
	
			/* Update the member's last post (for flood control) & markers */
			if( $this->memberData['member_id'] )
			{
				$this->memberData['last_post'] = time();
				$this->DB->update( 'members', array( 'last_post' => intval( $this->memberData['last_post'] ) ), 'member_id='.$this->memberData['member_id'] );
				
				$this->registry->getClass('classItemMarking')->markRead( array( 'blogID' => $blog['blog_id'], 'itemID' => $array['comment_parent_id'] ), 'blog' );
			}
		}
		
		IPSLib::doDataHooks( $array, 'blogPost'.ucwords($type).'Comment' );
		
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
		/* Load image and album */
		$parent  = $this->fetchParent( $parentId );
		$blog    = $this->registry->blogFunctions->loadBlog( $parent['blog_id'] );
		
		/* Rebuild the entry */
		$this->registry->blogFunctions->rebuildEntry( $parent['entry_id'] );
		
		/* Rebuild blog */
		$this->registry->blogFunctions->rebuildBlog( $blog['blog_id'] );
		
		/* Update the Blog stats */
		$r = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key='blog_stats'" ) );
		
		if ( $r['cs_array'] )
		{
			$cache = unserialize( stripslashes( $r['cs_value'] ) );
		}
		else
		{
			$cache = $r['cs_value'];
		}
		
		$cache['stats_num_comments'] -= count( $commentIds );
		
		$this->cache->setCache( 'blog_stats', $cache, array( 'array' => 1 ) );
		
		$array = array(	'commentIds'=> $commentIds, 'parentId'	=> $parentId);
		
		IPSLib::doDataHooks( $array, 'blogPostDeleteComments' );
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
		/* Load image and album */
		$parent  = $this->fetchParent( $parentId );
		$blog    = $this->registry->blogFunctions->loadBlog( $parent['blog_id'] );
		
		/* Rebuild the entry */
		$this->registry->blogFunctions->rebuildEntry( $parent['entry_id'] );
		
		/* Rebuild blog */
		$this->registry->blogFunctions->rebuildBlog( $blog['blog_id'] );
		$this->registry->blogFunctions->rebuildStats();
		
		$array = array(	'toggle'	=> $toggle,
						'commentIds'=> $commentIds,
						'parentId'	=> $parentId); 
						
		IPSLib::doDataHooks( $array, 'blogPostCommentVisibilityToggle' );
	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	int
	 */
	public function count( $parent )
	{
		/* Check parent */
		if ( is_numeric( $parent ) )
		{
			$parent = $this->remapFromLocal( $this->fetchParent( $parent ) );
		}
		
		$blog   = $this->registry->blogFunctions->loadBlog( $parent['parent_parent_id'] );
		
		/* Guarantee the data */
		$parent = $this->remapToLocal( $parent, 'parent' );
		
		$total  = $parent['entry_num_comments'];
		
		/* more? */
		$total += ( $this->registry->blogFunctions->allowApprove( $blog ) ) ? $parent['entry_queued_comments'] : 0;
	
		return $total;
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
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $array['comment_parent_id'] );
		$blog   = $this->registry->blogFunctions->loadBlog( $parent['blog_id'] );
		
		/* Fetch comment */
		if ( $array['comment_id'] )
		{ 
			$comment = $this->fetchById( $array['comment_id'] );
		}
		
		/* Check permissions */
		switch( $type )
		{
			case 'view':
				if ( ! $this->registry->blogFunctions->setActiveBlog( $blog, 1 ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'edit':
				if ( ! $this->registry->blogFunctions->allowEditComment( $blog, $comment ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'add':
				if ( ! $this->_checkForNewComment( $blog, $parent ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'delete':
				if ( ! $this->registry->blogFunctions->allowDelComment( $blog, $comment, $this->memberData ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'visibility':
			case 'moderate':
				if ( $this->registry->blogFunctions->allowApprove( $blog, $this->memberData ) )
				{
					return true;
				}
				else
				{
					return 'NO_PERMISSION';
				}
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
	public function remapKeys($type='comment')
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}
	
	/**
	 * Check for queued comments
	 *
	 * @return bool
	 */
	protected function _checkCommentQueued( $blog )
	{
		$queued = 0;
		
		// Do we need to queue this comment?
		if ( !$this->registry->blogFunctions->allowApprove( $this->blog ) )
		{
			if ( $blog['blog_settings']['approvemode'] == 'guests' && !$this->memberData['member_id'] )
			{
				$queued = 1;
			}
			elseif( $blog['blog_settings']['approvemode'] == 'all' )
			{
				$queued = 1;
			}
		}
		
		return $queued;
	}
	
	/**
	 * perform: Check for new comment
	 *
	 * @return bool
	 */
	protected function _checkForNewComment( $blog, $entry )
	{ 
		if( ! $this->memberData['g_blog_allowcomment'] or $blog['blog_settings']['disable_comments'] )
		{
            return false;
        }

		if( ! $this->memberData['member_id'] && !$blog['blog_settings']['allowguestcomments'] )
		{
            return false;
        }

		if( $entry['entry_locked'] && ! $this->registry->blogFunctions->allowReplyClosed( $blog ) )
		{
            return false;
        }
        
        return true;
	}

	/**
	 * Log akismet call
	 *
	 * @param	array		$comment		Data sent to akismet
	 * @param	string		$action			Action performed
	 * @param	string		$akismetError	Akismet error, if any was returned
	 * @return	@e void
	 */
	public function akismetLog( $comment, $action, $akismetError='' )
	{
		$msg	= '';
		$errors	= '';
		
		if( $akismetError != '' )
		{
			$msg	= $this->lang->words['akismet_error'];
			$errors	= serialize( $akismetError );
		}
		else if( $action == 'fine' )
		{
			$msg = $this->lang->words['akismet_comment_notspam'];
		}
		else
		{
			$msg = $this->lang->words['akismet_comment_spam'];
		}

		$insert	= array('log_date'			=> IPS_UNIX_TIME_NOW,
						'log_msg'			=> $msg,
						'log_errors'		=> $errors,
						'log_data'			=> serialize($comment),
						'log_type'			=> 'comment',
						'log_etbid'			=> intval($comment['comment_id']),
						'log_isspam'		=> ( $action != '' AND $action != 'fine' ) ? 1 : 0,
						'log_action'		=> ( $action != '' AND $action != 'fine' ) ? $action : null,
						'log_submitted'		=> 0,
						'log_connect_error'	=> $action ? 0 : 1
						);

		$this->DB->insert( 'blog_akismet_logs', $insert );
	}
}