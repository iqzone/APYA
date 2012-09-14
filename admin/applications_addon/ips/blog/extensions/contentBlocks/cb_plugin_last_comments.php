<?php
/**
* Last Comments Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_last_comments extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $show_draft;

	protected $last_read;
	protected $entries_read;
	protected $configable;
	protected $js_block;
	
	protected $DB;
	protected $lang;
	protected $settings;
	protected $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( $blog, ipsRegistry $registry )
	{
		/* Need blog parsing, if it doesn't exist yet */
		if( ! $registry->isClassLoaded( 'blogParsing' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
			$registry->setClass( 'blogParsing', new $classToLoad( $registry, $blog ) );
		}
		
		/* Setup */
		$this->blog         = $blog;
		$this->data         = array();
		$this->cblock_cache = $this->cblock_cache['lastcomments'];
		$this->show_draft   = $registry->blogParsing->show_draft;
		$this->last_read    = $registry->blogParsing->last_read;
		$this->entries_read = $registry->blogParsing->entries_read;
		$this->configable   = 0;
		$this->js_block     = 0;
		
		$this->DB           = $registry->DB();
		$this->lang         = $registry->getClass('class_localization');
		$this->settings     = $registry->settings();
		$this->registry     = $registry;
	}
	
	/**
	 * Returns the html for the last comments block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* INIT */
		$_html    = '';
		$comments = array();
		
		/* Try the cache */
		if ( $this->use_cache && isset( $this->cblock_cache ) && ! $this->show_draft && ! $this->cblock_cache['cbcache_refresh'] )
		{
			$comments = unserialize( $this->cblock_cache['cbcache_content'] );
		}
		else
		{
			if ( $this->blog['blog_id'] AND is_array( $this->blog['blog_last_comment_20'] ) && count( $this->blog['blog_last_comment_20'] ) )
			{
				$comments  = array();
				$_comments = $this->blog['blog_last_comment_20'];
				$members   = array();
				$mids	   = array();
				$max	   = 5;
				$c         = 0;
				
				foreach( $_comments as $cid => $cdata )
				{
					if ( $c++ >= $max )
					{
						continue;
					}
					
					$comments[ $cid ] = $cdata;
			
					if ( $cdata['member_id'] )
					{
						$mids[ $cdata['member_id'] ] = $cdata['member_id'];
					}
				}
				
				if ( count( $mids ) )
				{
					$members = IPSMember::load( $mids, 'all' );
					
					if ( count( $members ) )
					{
						foreach( $comments as $cid => $cdata )
						{
							if ( $cdata['member_id'] and isset( $members[ $cdata['member_id'] ] ) )
							{
								$comments[ $cid ] = array_merge( $comments[ $cid ], $members[ $cdata['member_id'] ] );
							}
						}
					}
				}
			}
			else
			{
				$this->DB->build( array( 'select'   => "c.comment_id, c.entry_id, c.comment_date",
										 'from'     => array( 'blog_comments' => 'c' ),
										 'where'    => "e.blog_id={$this->blog['blog_id']} AND c.comment_approved=1",
										 'order'    => 'c.comment_id DESC',
										 'limit'    => array( 0, 5 ),
										 'add_join' => array( array( 'select' => 'e.entry_name, e.blog_id, e.entry_name_seo',
										 							 'from'   => array( 'blog_entries' => 'e' ),
										 							 'where'  => "e.entry_id=c.entry_id",
										 							 'type'   => 'left' ),
										 					  array( 'select' => 'm.member_id, m.member_group_id, m.members_display_name, m.members_seo_name',
										 							 'from'   => array( 'members' => 'm' ),
										 							 'where'  => "m.member_id=c.member_id",
										 							 'type'   => 'left' ),
										 					  array( 'select' => 'pp.*',
										 							 'from'   => array( 'profile_portal' => 'pp' ),
										 							 'where'  => "pp.pp_member_id=c.member_id",
										 							 'type'   => 'left' ) )
								 )		);
				$this->DB->execute();
	
				while( $comment = $this->DB->fetch() )
				{
					$comments[ $comment['comment_id'] ] = $comment;
				}
			}
			
			/*  Do we update the cache */
			if( $this->use_cache && !$this->show_draft  )
			{
				if( isset( $this->cblock_cache['lastcomments'] ) )
				{
					$update['cbcache_content']     = serialize( $comments );
					$update['cbcache_refresh']     = 0;
					$update['cbcache_lastupdate'] = time();
					$this->DB->update( 'blog_cblock_cache', $update, "blog_id={$this->blog['blog_id']} AND cbcache_key='lastcomments'", true );
				}
				else
				{
					$insert['cbcache_content']    = serialize( $comments );
					$insert['cbcache_refresh']    = 0;
					$insert['cbcache_lastupdate'] = time();
					$insert['blog_id']            = $this->blog['blog_id'];
					$insert['cbcache_key']        = 'lastcomments';
					$this->DB->insert( 'blog_cblock_cache', $insert, true );
				}
			}
		}
		
		if ( (is_array($comments) && count($comments)) || $this->settings['blog_inline_edit'] )
		{
			$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['cblock_get_last_comments'], 0, true );
			
			/* Parse comments */
			if ( is_array($comments) && count($comments) )
			{
				foreach( $comments as $cid => $comment )
				{
					$comment			  = IPSMember::buildDisplayData( array_merge( $comment, empty($comment['member_id']) ? IPSMember::setUpGuest( $comment['member_name'] ) : $comment, array( 'reputation' => 0, 'warn' => 0 ) ) );
					
					$comment['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $this->blog['blog_id'], 'itemID' => $comment['entry_id'] ) );
					
					
					if( $comment['comment_date'] > $comment['_lastRead'] )
					{
						$comment['newpost'] = 1;
					}
					else
					{
						$comment['newpost'] = 0;
					}
					
					$comment['blog_id'] = $this->blog['blog_id'];
					
					$comments[ $cid ] = $comment;
				}
			}
			
			$return_html .= $this->registry->output->getTemplate('blog_cblocks')->comments( $comments );
			$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
		}

		return $return_html;
	}
	
	/**
	 * Returns the html for the content block configuration form
	 *
	 * @param  array   $cblock  Array of content block data
	 * @return string
	 */	
	public function getConfigForm( $cblock )
	{
		return '';
	}
	
	/**
	 * Handles any extra processing needed on config data
	 *
	 * @param  array  $data  array of config data
	 * @return array
	 */	
	public function saveConfig( $data )
	{
		return $data;
	}		
}