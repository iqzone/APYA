<?php
/**
* Albums Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_albums extends contentBlocks implements iContentBlock
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
		$this->DB         = $registry->DB();
		$this->registry   = $registry;
	}
	
	/**
	 * Returns the html for the album block
	 *
	 * @param  array  $cblock  Array of content block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		/* Init vars */
		$return_html = '';

		if( IPSLib::appIsInstalled('gallery') )
		{
			/* Gallery 4? */
			/* Get main library */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			
			$this->albums = $this->registry->gallery->helper('albums');
			$this->images = $this->registry->gallery->helper('image');
			
			/* Fetch 10 recently updated member albums */
			$albums   = $this->albums->fetchAlbumsByFilters( array( 'album_owner_id'   => $this->blog['member_id'],
																	'isViewable'       => true,
																	'album_is_global'  => 0,
																	'sortKey'          => 'date',
																	'sortOrder'        => 'desc',
																	'limit'            => 10,
																	'checkForMore' 	   => true,
																	'offset'           => 0
															)		);
			
			/* Got albums? */
		    if ( ( is_array($albums) && count($albums) ) || $this->settings['blog_inline_edit'] )
	        {
				$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, $this->registry->class_localization->words['cblock_get_albums'] );
		        
				foreach( $albums as $id => $album )
		        {
					$return_html .= $this->registry->output->getTemplate('blog_cblocks')->album_row($album);
				}
				
				$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
			}
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