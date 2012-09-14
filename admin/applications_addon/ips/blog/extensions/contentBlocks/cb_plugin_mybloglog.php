<?php
/**
* MyBlogLog Content Block
*
* @package		IP.Blog
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_mybloglog extends contentBlocks implements iContentBlock
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
		$this->configable = 1;
		$this->js_block   = 1;
		
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->registry   = $registry;
	}
	
	/**
	 * Returns the html for the mybloglog block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* Check for config data */
		$config = unserialize( $cblock['cblock_config'] );
		
		if( ! $config['mybloglog_id'] && ( $cblock['member_id'] == $this->memberData['member_id'] ) )
		{
			return $this->getConfigForm( $cblock );
		}
		
		$return_html = '';
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['mybloglog'], $this->configable );

$return_html .= <<<HTML
<script src="http://pub.mybloglog.com/comm3.php?mblID={$config['mybloglog_id']}&amp;r=widget&amp;is=normal&amp;o=l&amp;ro=4&amp;cs=black&amp;ww=220&amp;wc=single&amp;l=a"></script>
HTML;

		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
	
		return $return_html;
	}
	
	/**
	 * Configuration form for this plugin
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getConfigForm( $cblock )
	{
		/* Check for config data */
		$config = unserialize( $cblock['cblock_config'] );
			
		$return_html = '';
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['mybloglog_settings'] );
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->config_mybloglog( $config, $cblock );
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
		return $return_html;
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