<?php
/**
* Friends Content Block
*
* @package		IP.Blog
* @author		Remco Wilting
* @author		Brandon Farber
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_friends extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $configable;
	public $js_block;
	
	protected $lang;
	protected $DB;
	protected $settings;
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
		$this->data       = array();
		$this->configable = 0;
		$this->js_block   = 0;
		
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->DB         = $registry->DB();
		$this->settings   = $registry->settings();
		$this->registry   = $registry;
		
		/* Friends enabled */
		if( ! $this->settings['friends_enabled'] )
		{
			return '';
		}
	}
	
	/**
	 * Returns the html for hte digg conent block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */	
	public function getBlock( $cblock )
	{
		/* Friends enabled */
		if( !$this->settings['friends_enabled'] )
		{
			return '';
		}
		
		$return_html = '';
		$friends     = array();
		
		// Get friends
		$this->DB->build( array('select'	=> 'f.*',
								'from'		=> array( 'profile_friends' => 'f' ),
								'where'		=> 'f.friends_member_id=' . $this->blog['member_id'] . ' AND friends_approved=1',
								'add_join'	=> array(
													array( 'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name, m.member_group_id',
															'from'	=> array( 'members' => 'm' ),
															'where'	=> 'm.member_id=f.friends_friend_id',
															'type'	=> 'left'
														),
													array( 'select'	=> 'b.blog_id, b.blog_name, b.blog_seo_name',
															'from'	=> array( 'blog_blogs' => 'b' ),
															'where'	=> 'b.member_id=f.friends_friend_id',
															'type'	=> 'left'
														),
													array( 'select' => 'pp.*',
															'from'   => array( 'profile_portal' => 'pp' ),
															'where'  => 'pp.pp_member_id=f.friends_friend_id',
															'type'   => 'left' 
														),
													)
						 )		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( ! $r['blog_id'] )
			{
				continue;
			}
			
			$r = IPSMember::buildDisplayData( $r, array( 'reputation' => 0, 'warn' => 0 ) );
			
			$friends[] = $r;
		}
		
		if( count($friends) or $this->settings['blog_inline_edit'] )
		{
			$return_html  = $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, $this->lang->words['cblock_get_my_friends'], false, true );
			$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_friends( $friends );
			$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
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