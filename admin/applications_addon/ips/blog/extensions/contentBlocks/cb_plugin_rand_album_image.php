<?php
/**
* Random Album Image Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_rand_album_image extends contentBlocks implements iContentBlock
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
	
	protected $galfunc;
	protected $category;
	
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
		
		$this->settings   = $registry->settings();
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->DB         = $registry->DB();
		$this->lang       = $registry->getClass( 'class_localization' );	
		$this->registry   = $registry;	
	}
	
	/**
	 * Returns the html for the random album image block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		/* Gallery installed? */
		if( IPSLib::appIsInstalled('gallery') )
		{
			/* Get main library */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			
			$_image_link = $this->registry->gallery->helper('image')->fetchMembersImages( $this->blog['member_id'], array( 'limit' => 1, 'sortKey' => 'random' ) );
			
			$image_link  = array_pop( $_image_link );
			$image_link  = trim($image_link['thumb']);
			
			if ( $image_link or $this->settings['blog_inline_edit'] )
			{
				$return_html  = $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, $this->lang->words['cblock_get_random_album_image'] );
				$return_html .= $this->registry->output->getTemplate('blog_cblocks')->show_random_album_image( $image_link );
				$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
	
				return $return_html;
			}
		}
		
		return '';	
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