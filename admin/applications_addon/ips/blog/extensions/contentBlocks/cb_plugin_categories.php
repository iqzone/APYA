<?php
/**
* Categories Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
* @todo 		[Future] We should cache the tag cloud and update it dynamically as needed
*/
class cb_categories extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $configable;
	public $js_block;	
	
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
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( $blog, ipsRegistry $registry )
	{
		$this->blog       = $blog;
		$this->data       = array();
		$this->configable = 0;
		$this->js_block   = 0;
		$this->settings   = $registry->settings();
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->registry   = $registry;
	}
	
	/**
	 * Returns the html for the category block
	 *
	 * @param  array  $cblock  Array of content block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		$return_html = "";
		
		if ( count( $this->blog['_categories'] ) or $this->settings['blog_inline_edit'] )
		{
			$return_html  = $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['cblock_get_my_categories'] );
			$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->categories( $this->blog['blog_id'], $this->blog['_categories'] );
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