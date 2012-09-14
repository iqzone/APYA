<?php
/**
* Twitter Content Block
*
* @package		IP.Blog
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_twitter extends contentBlocks implements iContentBlock
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
		$this->js_block   = 1;
		$this->configable = 1;
		$this->data       = array();
		
		$this->registry   = $registry;
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();	
	}
	
	/**
	 * Returns the HTML for the twitter block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* Check for config data */
		$config = unserialize( $cblock['cblock_config'] );
		
		if( ! ( $config['twitter_username'] || ! $config['twitter_id'] ) && ( $cblock['member_id'] == $this->memberData['member_id'] ) )
		{
			return $this->getConfigForm( $cblock );
		}
		
		$return_html = '';
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['twitter'], $this->configable );

if( $config['twitter_friends'] )
{
$return_html .= <<<HTML
<div style="width:176px;text-align:center">
	<embed src="http://twitter.com/flash/twitter_badge.swf"  flashvars="color1=16594585&type=user&id={$config['twitter_id']}"  quality="high" width="176" height="176" name="twitter_badge" align="middle" allowScriptAccess="always" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /><br><a style="font-size: 10px; color: #FD3699; text-decoration: none" href="http://twitter.com/{$config['twitter_username']}">follow {$config['twitter_username']} at http://twitter.com</a>
</div>
HTML;
}

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
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['twitter_settings'] );
		
		/* Friends dropdown */
		$friend_sel= array( 
							$config['twitter_friends'] ? " selected='selected'" : '',
							$config['twitter_friends'] ? '' : " selected='selected'",
						);
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->config_twitter( $config, $cblock, $friend_sel );
		
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