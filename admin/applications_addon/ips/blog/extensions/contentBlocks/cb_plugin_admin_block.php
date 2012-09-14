<?php
/**
* Admin Defined Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_admin_block extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $configable;
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $category;
	public $js_block;
	
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
		$this->cache      = $registry->cache();
		$this->caches  	  =& $registry->cache()->fetchCaches();
		$this->registry   = $registry;
		$this->DB         = $registry->DB();
	}
	
	/**
	 * Returns the html for the admin block
	 *
	 * @param  array  $cblock  Array of content block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		/* Block Cache */
        $cache_id = 'cblock_'.$cblock['cblock_ref_id'];
        
        if( !$this->cache->exists('blog_admin_blocks') OR !isset($this->caches['blog_admin_blocks'][ $cache_id ]) )
        {
        	$this->cache->getCache('blog_admin_blocks');
        }

		$content = $this->caches['blog_admin_blocks'][ $cache_id ];
		
		/* Do the HTML manually as we want javascript enabled */
		$content = str_replace( "<br>"        , "\n"         , $content );
		$content = str_replace( "<br />"      , "\n"         , $content );
		$content = str_replace( "&gt;"        , ">"          , $content );
		$content = str_replace( "&lt;"        , "<"          , $content );
		$content = str_replace( '"java script', '"javascript', $content );

		$content = IPSText::getTextClass('bbcode')->preDisplayParse( $content );

		$content = str_replace( '{blog.id}'      , $this->blog['blog_id']     , $content);
		$content = str_replace( '{blog.name}'    , $this->blog['blog_name']   , $content);
		$content = str_replace( '{blog.memberid}', $this->blog['member_id']   , $content);
		$content = str_replace( '{member.id}'    , $this->memberData['member_id'] , $content);
		$content = str_replace( '{blog.url}'     , $this->settings['blog_url'], $content);
		$content = str_replace( '{base.url}'     , $this->settings['base_url'], $content);

		$return_html  = $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->caches['cblocks'][ $cblock['cblock_ref_id'] ]['cbdef_name'] );
		$return_html .= $content;
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