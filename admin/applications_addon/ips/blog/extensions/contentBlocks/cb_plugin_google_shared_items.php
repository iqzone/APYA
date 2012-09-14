<?php
/**
* Google Shared Items Content Block
*
* @package		IP.Blog
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_google_shared_items extends contentBlocks implements iContentBlock
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
		
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->registry   = $registry;
	}
	
	/**
	 * Returns the html for the google shared items block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* Check for config data */
		$config = unserialize( $cblock['cblock_config'] );
		
		if( ! $config['shareditems_id'] && ( $cblock['member_id'] == $this->memberData['member_id'] ) )
		{
			return $this->getConfigForm( $cblock );
		}
		
		$return_html = '';
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['cblock_google'], $this->configable );
	
$return_html .= <<<HTML
<script type="text/javascript" src="http://www.google.com/reader/ui/publisher-en.js"></script>
<script type="text/javascript" src="http://www.google.com/reader/public/javascript/user/{$config['shareditems_id']}/state/com.google/broadcast?n=10&callback=GRC_p(%7Bc%3A%22slate%22%2Ct%3A%22My%20shared%20items%22%2Cs%3A%22false%22%2Cb%3A%22false%22%7D)%3Bnew%20GRC"></script>
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
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['cblock_google_config'] );
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->config_google( $config, $cblock );
		
/*$return_html .= <<<HTML
	{$this->lang->words['google_id']}: <input type="text" id="shareditems_id" name="shareditems_id" value="{$config['shareditems_id']}" /><br />
	<input class="button" type="button" value="{$this->lang->words['save_settings_c']}" onclick='cblock_save_form( {$cblock['cblock_id']}, new Array( "shareditems_id" )  )' />
HTML;*/
		
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