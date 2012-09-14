<?php
/**
* Last Entries Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_last_entries extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $show_draft;

	protected $last_read;
	protected $configable;
	public $js_block;
	
	protected $DB;
	protected $settings;
	protected $lang;
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
		$this->show_draft   = $registry->blogParsing->show_draft;
		$this->last_read    = $registry->blogParsing->last_read;
		$this->configable   = 0;
		$this->js_block     = 0;
		
		$this->DB           = $registry->DB();
		$this->settings     = $registry->settings();
		$this->lang         = $registry->getClass( 'class_localization' );
		$this->registry     = $registry;
	}
	
	/**
	 * Returns the html for the last entries block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* INIT */
		$return_html = "";
		$extra       = "";
		$entries     = array();
		$mids        = array();
		
		/* Can we use the cache */
		if( $this->use_cache && isset( $this->cblock_cache ) && ! $this->show_draft && ! $this->cblock_cache['cbcache_refresh'] )
		{
			$entries = unserialize( $this->cblock_cache['cbcache_content'] );
		}
		else
		{
			/* Do we show the drafts? */
			if( !$this->show_draft )
			{
				$extra = " AND entry_status='published'";
			}
			

			$this->DB->build( array( 'select' => 'entry_id, entry_last_update, entry_name, blog_id, entry_name_seo, entry_author_id, entry_date',
									 'from'   => 'blog_entries',
									 'where'  => "blog_id={$this->blog['blog_id']}" . $extra,
									 'order'  => 'entry_date DESC',
									 'limit'  => array( 0, 5 ) ) );
			$this->DB->execute();
			
			while( $entry = $this->DB->fetch() )
			{
				$entries[ $entry['entry_id'] ]     = $entry;
				$mids[ $entry['entry_author_id'] ] = $entry['entry_author_id'];
			}
			
			if ( count( $mids ) )
			{
				$members = IPSMember::load( $mids, 'all' );
				
				if ( count( $members ) )
				{
					foreach( $entries as $cid => $cdata )
					{
						if ( $cdata['entry_author_id'] and isset( $members[ $cdata['entry_author_id'] ] ) )
						{
							$entries[ $cid ] = array_merge( $entries[ $cid ], $members[ $cdata['entry_author_id'] ] );
						}
					}
				}
			}

			/* Do we update the cache */
			if( $this->use_cache && ! $this->show_draft )
			{
				if( isset( $this->cblock_cache['lastentries'] ) )
				{
					$update['cbcache_content']    = serialize( $entries );
					$update['cbcache_refresh']    = 0;
					$update['cbcache_lastupdate'] = time();
					$this->DB->update( 'blog_cblock_cache', $update, "blog_id={$this->blog['blog_id']} AND cbcache_key='lastentries'", true );
				}
				else
				{
					$insert['cbcache_content'] = serialize( $entries );
					$insert['cbcache_refresh'] = 0;
					$insert['cbcache_lastupdate'] = time();
					$insert['blog_id'] = $this->blog['blog_id'];
					$insert['cbcache_key'] = 'lastentries';
					$this->DB->insert( 'blog_cblock_cache', $insert, true );
				}
			}
		}

		if( count( $entries ) > 0 or $this->settings['blog_inline_edit'] )
		{
			$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['cblock_get_last_entries'], 0, true );
			
			if( is_array( $entries ) )
			{
				foreach( $entries as $eid => $entry )
				{
					$entry                = IPSMember::buildDisplayData( $entry, array( 'reputation' => 0, 'warn' => 0 ) );
					$entry['_entry_date'] = $this->registry->getClass('class_localization')->getDate( $entry['entry_date'], 'SHORT' );
					
					// Updated by Rikki 12/27
					// $entry['newpost'] now set to true or false, and actual link is generated in template
					$entry['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $this->blog['blog_id'], 'itemID' => $entry['entry_id'] ) );
					
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
			}
			
			$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->recentEntries($entries);
			$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
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