<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-may-2012 -006  $
 * </pre>
 * @filename            timeline.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		09-may-2012
 * @timestamp           15:52:10
 * @version		$Rev:  $
 *
 */

/**
 * Description of timeline
 *
 * @author juliobarreraa@gmail.com
 */
class blogrights {
    //Private
    private $timeline;
    //Protected
    protected $registry;
    protected $memberData;
    protected $DB;
    //Public
    public $lang;

    public $blogFunctions;
    public $blog;
    public $content_blocks;
    
    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->settings = & $this->registry->fetchSettings(); //Get settings timeline_max_status
        $this->memberData = & $this->registry->member()->fetchMemberData(); //This member data 
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        $this->DB = $this->registry->DB();
        $this->cache         =  $this->registry->cache();
        $this->caches        =  $this->registry->cache()->fetchCaches();

        //Obsolete
        $this->member		=  $this->registry->member();
        if ( ! $this->registry->isClassLoaded('blogFunctions') )
        {
            $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
            $this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
        }
        $this->blogFunctions =  $this->registry->getClass('blogFunctions');
    }
    
    public function getOutput() {
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
        $this->lang->loadLanguageFile(array('public_profile', 'public_portal'), 'members');
        $this->blog = $this->blogFunctions->getActiveBlog();

        $htmlopt = $this->show_blocks();
        return $this->registry->output->getTemplate('portal')->blog_cblocks($htmlopt['html'], $htmlopt['position'], $htmlopt['hidden']);
    }    

    private function show_blocks($position='right') {
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
                $this->class_attach       = new $classToLoad( $this->registry );
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
                return array('html' => $return_html_content, 'position' => $position, 'hidden' => $hidden);
            }

        }

    }

    /**
     * Fetch cBlogs data
     *
     * @access  public
     * @param   int     Blog ID
     * @param   bool    Force refresh from DB
     * @return  array
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
                                        'from'   => 'blog_cblocks',
                                        'where'  => "blog_id=" . intval( $blogData['blog_id'] ) . " and cblock_type='default'",
                                        'order'  => 'cblock_order ASC' ) );
            }
            else
            {
                $this->DB->build( array( 
                                        'select'   => "bc.*",
                                        'from'     => array('blog_cblocks' => 'bc'),
                                        'add_join' => array( array( 'select' => 'bcc.*',
                                                                    'from'   => array( 'blog_custom_cblocks' => 'bcc' ),
                                                                    'where'  => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
                                                                    'type'   => 'left'
                                                                ) ),
                                        'where'    => "bc.blog_id =" . intval( $blogData['blog_id'] ),
                                        'order'    => 'bc.cblock_order ASC' )   );
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
     * Recache blog used blocks
     *
     * @access  public
     * @param   int     Blog ID
     * @param   array   [Content block data ]
     * @return  array   Content blocks
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
}

?>
