<?php
/**
* Search Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_search extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $configable;
	public $js_block;

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
		$this->blog       = $blog;
		$this->js_block   = 0;
		$this->confifable = 0;
		$this->data       = array();
		
		$this->registry   = $registry;
		$this->lang       = $registry->getClass( 'class_localization' );		
	}
	
	/**
	 * Returns the html for the search block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		$return_html  = $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['cblock_get_my_search'] );
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->get_my_search( $this->blog['blog_id'] );
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );

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