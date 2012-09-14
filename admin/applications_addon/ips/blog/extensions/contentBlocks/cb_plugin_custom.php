<?php
/**
* Custom Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_custom extends contentBlocks implements iContentBlock
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	protected $data;
	protected $configable;
	public $js_block;
	
	public function __construct( $blog, ipsRegistry $registry )
	{
		$this->blog       = $blog;
		$this->registry   = $registry;
		$this->data       = array();
		$this->configable = 0;
		$this->js_block   = 0;
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
	}
	
	public function getBlock( $cblock )
	{
		IPSText::getTextClass('bbcode')->parse_html			= $cblock['cbcus_html_state'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= $cblock['cbcus_html_state'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'blog_cblock';
		
		$cblock['cbcus'] = IPSText::getTextClass('bbcode')->preDisplayParse( $cblock['cbcus'] );
		
		$cblock['allow_edit'] = ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_editcblocks'] ) ? 1 : 0;
		
		$cblock_html  = $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, $cblock['cbcus_name'] );
		$cblock_html .= $this->registry->output->getTemplate('blog_cblocks')->show_cblock( $cblock );
		$cblock_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( $cblock, $this->member->form_hash );
		
		return $cblock_html;
	}
	
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