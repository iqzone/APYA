<?php
/**
* Active Users Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_active_users extends contentBlocks implements iContentBlock
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
		$this->caches     =& $registry->cache()->fetchCaches();
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->DB         = $registry->DB();
		$this->registry   = $registry;
	}
	
	/**
	 * Returns the html for the active users
	 *
	 * @param	array		$cblock		Array of content block data
	 * @return	@e string	HTML
	 */	
	public function getBlock( $cblock )
	{
		/* Disabled? */
		if( !$this->settings['blog_showactive'] )
		{
			return '';
		}
		
		/* Grab active users */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
		$sessions    = new $classToLoad( $this->registry );
		
		$activeUsers = $sessions->getUsersIn( 'blog', array( 'addWhere' => array( 's.location_1_id='.$this->blog['blog_id'] ) ) );
		
		$return_html  = $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, sprintf( $this->lang->words['cblock_active_users'], $activeUsers['stats']['total'] ) );
		$return_html .= $this->registry->output->getTemplate('blog_cblocks')->block_activeusers( $activeUsers );
		$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );

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