<?php

/**
 * Content Blcoks Interfaces
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $ 
 */

interface iContentBlock
{
	/**
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( $blog, ipsRegistry $registry );
		
	/**
	 * Returns the html for the content block
	 *
	 * @param  array  $cblock  Array of content block data
	 * @return string
	 */
	public function getBlock( $cblock );
	
	/**
	 * Returns the html for the content block configuration form
	 *
	 * @param  array   $cblock  Array of content block data
	 * @return string
	 */
	public function getConfigForm( $cblock );
	
	/**
	 * Handles any extra processing needed on config data
	 *
	 * @param  array  $data  array of config data
	 * @return array
	 */	
	public function saveConfig( $data );
}

/**
* Blog content block plugin manager
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class contentBlocks
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
	 * Determines if cblock caches will be used
	 *
	 * @access	public
	 * @var		bool
	 */	
	public $use_cache = 0;
	
	/**
	 * Cached cblock data
	 *
	 * @access	public
	 * @var		array
	 */	
	public $cblock_cache;
	
	/**
	 * Content Blocks
	 *
	 * @access	public
	 * @var		array
	 */		
	public $content_blocks;
	
	/**
	 * Legacy Blocks
	 *
	 * @access	public
	 * @var		array
	 */		
	public $legacy_blocks;
	
	/**
	 * Blog Data
	 *
	 * @access	public
	 * @var		array
	 */		
	public $blog;
	public $blogFunctions;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param	object	Registry
	 * @param	array 	Blog data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $blog=array() )
	{
		/* Make registry objects */
		$this->registry		 =  $registry;
		$this->DB			 =  $this->registry->DB();
		$this->settings		 =  $this->registry->fetchSettings();
		$this->request		 =  $this->registry->fetchRequest();
		$this->lang			 =  $this->registry->getClass('class_localization');
		$this->member		 =  $this->registry->member();
		$this->memberData	 =  $this->registry->member()->fetchMemberData();
		$this->cache		 =  $this->registry->cache();
		$this->caches		 =  $this->registry->cache()->fetchCaches();
		$this->blogFunctions =  $this->registry->getClass('blogFunctions');
		
		if( is_array($blog) && count($blog) )
		{
			$this->blog = $blog;
		}
		else
		{
			$this->blog = $this->blogFunctions->getActiveBlog();
		}
		
		/* Mapping array to preserver legacy content blocks */
		$this->legacy_blocks = array(
 										'get_admin_block'        => 'admin_block',
 										'get_my_categories'      => 'categories',
 										'get_my_tags'            => 'tags',
 										'get_my_search'          => 'search',
 										'get_mini_calendar'      => 'calendar',
 										'get_last_entries'       => 'last_entries',
 										'get_last_comments'      => 'last_comments',
 										'get_albums'             => 'albums',
 										'get_my_picture'         => 'mypicture',
 										'get_random_album_image' => 'rand_album_image',
 										'get_active_users'       => 'active_users',
									);		

		/* Content Block Cache */
		if ( !$this->cache->getCache('cblocks') )
		{
			$cache = array();
			
			/* Not cached, query the data */
			$this->DB->build( array( 
										'select' => '*',
										'from'	 => 'blog_default_cblocks',
										'where'	 => "cbdef_enabled=1",
										'order'	 => 'cbdef_order'
							)  );
			$this->DB->execute();
			
			while( $defblock = $this->DB->fetch() )
			{
				$cache[$defblock['cbdef_id']] = array( 
														'cbdef_name'     => $defblock['cbdef_name'],
														'cbdef_function' => $defblock['cbdef_function'],
														'cbdef_default'	 => $defblock['cbdef_default'],
														'cbdef_order'    => $defblock['cbdef_order'],
														'cbdef_locked'   => $defblock['cbdef_locked']
													);
			}

			$this->cache->setCache( 'cblocks', $cache, array( 'array' => 1 ) );
		}
		
		/* Check the block content cache */
		if( $this->settings['blog_cblock_cache'] AND $this->blog['blog_id'] )
		{
			if( $this->blog['member_id'] == $this->memberData['member_id'] )
			{
				$this->use_cache = 0;
			}
			elseif( is_array( $this->memberData['g_blog_authed_blogs'] ) AND count( $this->memberData['g_blog_authed_blogs'] ) AND in_array( $this->blog['blog_id'], $this->memberData['g_blog_authed_blogs'] ) )
			{
				$this->use_cache = 0;
			}
			elseif( isset( $this->memberData['_blogmod'] ) && $this->memberData['_blogmod']['moderate_can_view_private'] )
			{
				$this->use_cache = 0;
			}
			else
			{
				$this->use_cache = 1;
			}
		}
		else
		{
			$this->use_cache = 0;
		}

		if( $this->use_cache AND $this->blog['blog_id'] )
		{
			$this->DB->build( array( 
										'select' => '*',
										'from'   => 'blog_cblock_cache',
										'where'	 => 'blog_id = '. intval( $this->blog['blog_id'] )
								)		);
			$qid = $this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$this->cblock_cache[ $row['cbcache_key'] ] = $row;
			}
		}
	}
	
	/**
	 * Fetch cBlogs data
	 *
	 * @access	public
	 * @param	int		Blog ID
	 * @param	bool	Force refresh from DB
	 * @return	array
	 */
	public function fetchUsedContentBlocks( $blogData=array(), $forceFresh=false )
	{
		$cblocks  = array();
		$blogData = is_array( $blogData ) && $blogData['blog_id'] ? $blogData : $this->blog;

		if ( $blogData['blog_cblocks'] AND ( $forceFresh === false ) )
		{
			if ( is_array( $blogData['blog_cblocks'] ) )
			{
				$cblocks = $blogData['blog_cblocks'];
			}
			else
			{
				$cblocks = unserialize( $blogData['blog_cblocks'] );
			}
		}
		
		if ( ( ! count( $cblocks ) ) AND $blogData['blog_id'] )
		{
			/* Query our content blocks */
			if( ! $this->settings['blog_allow_cblocks'] )
			{
				$this->DB->build( array( 
										'select' => '*',
										'from'	 => 'blog_cblocks',
										'where'	 => "blog_id=" . intval( $blogData['blog_id'] ) . " and cblock_type='default'",
										'order'	 => 'cblock_order ASC' ) );
			}
			else
			{
				$this->DB->build( array( 
										'select'   => "bc.*",
										'from'	   => array('blog_cblocks' => 'bc'),
										'add_join' => array( array( 'select' => 'bcc.*',
																	'from'   => array( 'blog_custom_cblocks' => 'bcc' ),
																	'where'  => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
																	'type'   => 'left'
																) ),
										'where'	   => "bc.blog_id =" . intval( $blogData['blog_id'] ),
										'order'	   => 'bc.cblock_order ASC' )	);
			}
			
			$this->DB->execute();
			
			/* Loop through the blocks we found */			
			while( $r = $this->DB->fetch() )
			{
				$cblocks[ $r['cblock_position'] ][] = $r;
			}
		}
		
		return $cblocks;//( is_array( $cblocks ) ) ? $cblocks : array();
	}
	
	/**
	 * Fetch all cBlogs data
	 *
	 * @access	public
	 * @param	int		Blog ID
	 * @param	bool	Force refresh from DB
	 * @return	array
	 */
	public function fetchAllContentBlocks( $blogData=array(), $forceFresh=false )
	{
		$cblocks  = array();
		$blogData = isset( $blogData['blog_id'] ) ? $blogData : $this->blog;
		
		if ( $blogData['blog_cblocks_available'] AND ( $forceFresh === false ) )
		{
			if ( is_array( $blogData['blog_cblocks_available'] ) )
			{
				$cblocks = $blogData['blog_cblocks_available'];
			}
			else
			{
				$cblocks = unserialize( $blogData['blog_cblocks_available'] );
			}
		}
		
		if ( ( ! count( $cblocks ) ) AND $blogData['blog_id'] )
		{
			if ( $this->settings['blog_allow_cblocks'] )
			{
				$this->DB->build( array( 'select'	=> "bc.*",
										 'from'	    => array( 'blog_cblocks' => 'bc' ),
										 'where'	=> "bc.blog_id={$blogData['blog_id']} and bc.cblock_show=0",
										 'order'	=> 'bc.cblock_order ASC',
										 'add_join' => array(  array( 'select' => 'bcc.*',
																	  'from'   => array( 'blog_custom_cblocks' => 'bcc' ),
																	  'where'  => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
																	  'type'   => 'inner' ) ) )	);
				$qid = $this->DB->execute();
				
				while( $cblock = $this->DB->fetch( $qid ) )
				{
					$cblocks[] = $cblock;
				}
			}

			$this->DB->build( array( 'select'	=> 'bd.*',
									 'from'		=> array( 'blog_default_cblocks' => 'bd' ),
									 'where'	=> "cbdef_enabled=1 and cbdef_locked=0",
									 'order'	=> 'cbdef_order ASC',
									 'add_join' => array( array( 'select' => 'b.cblock_id, b.cblock_show',
																 'from'	 => array( 'blog_cblocks' => 'b' ),
																 'where' => "b.cblock_ref_id=bd.cbdef_id and b.cblock_type='default' and b.blog_id = {$blogData['blog_id']}",
																 'type'	 => 'left' ) ) ) );
			$qid = $this->DB->execute();
			
			/* Loop through the blocks we found */			
			while( $cblock = $this->DB->fetch( $qid ) )
			{
				$cblocks[] = $cblock;
			}
		}
		
		return $cblocks;//( is_array( $cblocks ) ) ? $cblocks : array();
	}
	
	/**
	 * Recache all
	 *
	 * @access	public
	 * @param	int		Blog id
	 */
	public function recacheAllBlocks( $blogId )
	{
		$this->recacheBlogUsedBlocks( $blogId );
		$this->recacheBlogAllBlocks( $blogId );
	}
	 
	/**
	 * Recache blog used blocks
	 *
	 * @access	public
	 * @param	int		Blog ID
	 * @param	array	[Content block data ]
	 * @return	array	Content blocks
	 */
	public function recacheBlogUsedBlocks( $blogId, $contentBlocks=array() )
	{
		$contentBlocks = ( is_array( $contentBlocks ) AND count( $contentBlocks ) ) ? $contentBlocks : $this->fetchUsedContentBlocks( array( 'blog_id' => $blogId ), true );
		
		if ( $blogId )
		{
			$this->DB->update( 'blog_lastinfo', array( 'blog_cblocks' => serialize( $contentBlocks ) ), 'blog_id=' . intval( $blogId ) );
		}
		
		return $contentBlocks;
	}
	
	/**
	 * Recache blog all blocks
	 *
	 * @access	public
	 * @param	int		Blog ID
	 * @param	array	[Content block data ]
	 * @return	array	Content blocks
	 */
	public function recacheBlogAllBlocks( $blogId, $contentBlocks=array() )
	{
		$contentBlocks = ( is_array( $contentBlocks ) AND count( $contentBlocks ) ) ? $contentBlocks : $this->fetchAllContentBlocks( array( 'blog_id' => $blogId ), true );
		
		if ( $blogId )
		{
			$this->DB->update( 'blog_lastinfo', array( 'blog_cblocks_available' => serialize( $contentBlocks ) ), 'blog_id=' . intval( $blogId ) );
		}
		
		return $contentBlocks;
	}
	
	/**
	 * Drop cache
	 *
	 * @access	public
	 * @param	int		[blog ID, if nothing passed, will drop all]
	 */
	public function dropCache( $blogId=0 )
	{
		if ( $blogId )
		{
			$this->DB->update( 'blog_lastinfo', array( 'blog_cblocks' => serialize( array() ), 'blog_cblocks_available' => serialize( array() ) ), 'blog_id=' . intval( $blogId ) );
		}
		else
		{
			$this->DB->update( 'blog_lastinfo', array( 'blog_cblocks' => serialize( array() ), 'blog_cblocks_available' => serialize( array() ) ) );
		}
	}
	 
	/**
	 * show_blocks
	 *
	 * @param  string $position Should be either 'right' or 'left'
	 * @return string
	 */
	public function show_blocks( $position )
	{		
		/* Load content blocks */
		if( ! count( $this->content_blocks ) )
		{
			/* Query our content blocks */
			$this->content_blocks = $this->fetchUsedContentBlocks( $this->blog );
			
			/* Update blog? */
			if ( is_array( $this->content_blocks ) AND count( $this->content_blocks ) AND ! $this->blog['blog_cblocks'] )
			{
				$this->recacheBlogUsedBlocks( $this->blog['blog_id'], $this->content_blocks );
			}
		}
		
		/* Loop through the content block position we want */
		$return_html_content = '';
		$cblock_attach       = array();
		
		if( count( $this->content_blocks[$position] ) )
		{
			foreach( $this->content_blocks[$position] as $cblock )
			{
				/* INI HTML */
				$cblock_html_content = '';				
				
				/* Viewable? */
				if ( ! $cblock['cblock_show'] )
				{
					continue;
				}
				
				/* Determine cblock type */
				if( $cblock['cblock_type'] == 'default' && $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function'] )
				{
					$cblock['locked'] = $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_locked'];

					/* Plugin Name */
					$plugin_to_run = $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function'];
					
					/* Check legacy array */
					$plugin_to_run = isset( $this->legacy_blocks[$plugin_to_run] ) ? $this->legacy_blocks[$plugin_to_run] : $plugin_to_run;
					
					/* Get plugin */
					$_cb_file  = IPSLib::getAppdir( 'blog' ) . '/extensions/contentBlocks/cb_plugin_' . $plugin_to_run . '.php';
					
					if( is_file( $_cb_file ) )
					{
						$_cb_class = IPSLib::loadLibrary( $_cb_file, 'cb_' . $plugin_to_run, 'blog' );
						
						if( class_exists( $_cb_class ) && in_array( 'iContentBlock', class_implements( $_cb_class ) ) )
						{
							$plugin = new $_cb_class( $this->blog, $this->registry );
							$plugin->use_cache = $this->use_cache;
							$cblock_html_content = $plugin->getBlock( $cblock );
						}
					}
				}
				else if( $cblock['cblock_type'] == 'custom' )
				{
					/* Custom Type */
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppdir('blog') . '/extensions/contentBlocks/cb_plugin_custom.php', 'cb_custom', 'blog' );
					$plugin      = new $classToLoad( $this->blog, $this->registry );
					$plugin->use_cache = $this->use_cache;
					$cblock_html_content = $plugin->getBlock( $cblock );
					
					/* Attachments */
			    	if ( $cblock['cbcus_has_attach'] )
			    	{
			    		$cblock_attach[] = $cblock['cbcus_id'];
			    	}
				}
				
				/* Dragdrop Handlers */
				if( $cblock_html_content )
				{
					$return_html_content .= $cblock_html_content;
				}
			}
			
			if( count( $cblock_attach ) )
			{
				$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach	      = new $classToLoad( $this->registry );
				$this->class_attach->type = 'blogcblock';
				$this->class_attach->init();
	
				$return_html_content = $this->class_attach->renderAttachments( $return_html_content, $cblock_attach, 'blog_show' );
				$return_html_content = $return_html_content[0]['html'];
			}			
			
			/* Bar Settings */
			$hidden   = ( $return_html_content == "" ) ? "display:none;": "";
			$width    = $this->blog['blog_settings']['cblockwidth'];
			
			/* Finalize bar html */
			if( $return_html_content )
			{
				$return_html_content  = $this->registry->getClass('output')->getTemplate('blog_cblocks')->blog_cblocks( $return_html_content, $position, $hidden );
			}
		}
		
		return $return_html_content;
	}

	/**
	 * get_cblock_html
	 *
	 * Legacy/Shortcut Function for outputting the html from a plugin block
	 *
	 * @param  integer $cblock_id
	 * @param  string  [$type]     Type of output, either get_block or get_config_form
	 * @return string
	 */
    public function get_cblock_html( $cblock_id, $type='getBlock' )
	{
		$cb_plugin = $this->getPlugin( $cblock_id );
		
		return $this->wrapPluginOutput( $cb_plugin, $cb_plugin->$type( $this->cblock ) );
	}
	
	/**
	* Get content block name
	*
	* @access	public
	* @param	integer		Cblock id
	* @return	string		Cblock name HTML
	*/	
	public function getCblockName( $cblock_id )
	{
		$this->DB->build( array( 'select'	=> "bc.*",
							          'from'	=> array('blog_cblocks' => 'bc'),
						              'add_join'=> array( 0 => array( 'select' => 'bcc.*',
													                  'from'   => array( 'blog_custom_cblocks' => 'bcc' ),
													                  'where'  => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
													                  'type'   => 'left'
														)			),
            						  'where'	=> "bc.blog_id = {$this->blogFunctions->blog['blog_id']} and bc.cblock_id={$cblock_id}"
						     )		);
		$qid	= $this->DB->execute();
	    $cblock	= $this->DB->fetch($qid);
	    
	    if ( !$cblock['cblock_id'] )
	    {
	    	return "";
	    }

		$cblock_name = "";
		
		if ( $cblock['cblock_type'] == 'default' && $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function'] )
		{
			if ( !$this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_locked'] )
			{
				if ( $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function'] == 'get_admin_block' )
				{
					$cblock_name = array( 'id' => $cblock['cblock_id'], 'name' => $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_name']);
					//$cblock_name = "--IMGITEM-- <a href='javascript:enable_cblock({$cblock['cblock_id']})'>".$this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_name']."</a>";
				}
				else
				{
					$lang = $this->lang->words['cblock_'.$this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function']];
					
					if( !$lang )
					{
						$lang = $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_name'];
					}
					
					$cblock_name = array( 'id' => $cblock['cblock_id'], 'name' => $lang );
					//$cblock_name = "--IMGITEM-- <a href='javascript:enable_cblock({$cblock['cblock_id']})'>".$lang."</a>";
				}
			}
		}
		elseif ( $cblock['cblock_type'] == 'custom' )
		{
			$cblock_name = array( 'id' => $cblock['cblock_id'], 'name' => $cblock['cbcus_name'] );
			//$cblock_name = "--IMGITEM-- <a href='javascript:enable_cblock({$cblock['cblock_id']})'>".$cblock['cbcus_name']."</a>";
	    }
	    
		return $cblock_name;
	}
	
	/**
	 * Return a plug in object for the specified ID
	 *
	 * @param    integer $cblock_id
	 * @return object
	 */
	public function getPlugin( $cblock_id )
	{
		/* Query the content block */
		$this->DB->build( array( 			
									'select'   => "bc.*",
									'from'	   => array('blog_cblocks' => 'bc'),
									'add_join' => array( array( 													
																'select' => 'bcc.*',
																'from'   => array( 'blog_custom_cblocks' => 'bcc' ),
																'where'  => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
																'type'   => 'left'
											)	),
									'where'	=> "bc.blog_id={$this->blog['blog_id']} and bc.cblock_id={$cblock_id}"
						    )	);
		$this->DB->execute();
	    $cblock = $this->DB->fetch();
		
	    if( ! $cblock['cblock_id'] )
	    {
	    	return '';
	    }
		
		/* Determine cblock type */
		if( $cblock['cblock_type'] == 'default' && $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function'] )
		{
			/* Default Type */
			if( ! $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_locked'] )
			{
				/* Plugin Name */
				$plugin_to_run = $this->caches['cblocks'][$cblock['cblock_ref_id']]['cbdef_function'];
				
				/* Check legacy array */
				$plugin_to_run = isset( $this->legacy_blocks[$plugin_to_run] ) ? $this->legacy_blocks[$plugin_to_run] : $plugin_to_run;
				
				/* Get plugin */
				$_cb_file  = IPSLib::getAppdir('blog') . '/extensions/contentBlocks/cb_plugin_' . $plugin_to_run . '.php';
				
				if( is_file( $_cb_file ) )
				{
					$_cb_class = IPSLib::loadLibrary( $_cb_file, 'cb_' . $plugin_to_run, 'blog' );

					if( class_exists( $_cb_class ) )
					{ 
						$this->cblock = $cblock;
						return new $_cb_class( $this->blog, $this->registry );
					}
				}
			}
		}
		else if( $cblock['cblock_type'] == 'custom' )
		{
			/* Custom Type */
			$this->cblock = $cblock;
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppdir('blog') . '/extensions/contentBlocks/cb_plugin_custom.php', 'cb_custom', 'blog' );
			return new $classToLoad( $this->blog, $this->registry );
		}
	}
	
	/**
	 * Wraps plugin output for ajax return
	 *
	 * @param	object		$plugin			Content block plugin
	 * @param	string		$cblockHtml		Html content to wrap
	 * @return	@e string
	 */	
	public function wrapPluginOutput( $plugin, $cblockHtml='' )
	{
		/* Attachments */
		if ( $plugin->cblock['cbcus_has_attach'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->class_attach       = new $classToLoad( $this->registry );
			$this->class_attach->type = 'blogcblock';
			$this->class_attach->init();
			
			$cblockHtml = $this->class_attach->renderAttachments( $cblockHtml, array( $plugin->cblock['cbcus_id'] ), 'blog_show' );
		}			
		
		if ( $cblockHtml )
		{
	      	$cblockHtml = $this->registry->output->replaceMacros( $cblockHtml );
		}
		
		return $cblockHtml;
	}
}