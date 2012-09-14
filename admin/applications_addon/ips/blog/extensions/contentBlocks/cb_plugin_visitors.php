<?php
/**
* Latest Vistiros Content Block
*
* @package		IP.Blog
* @author		Brandon Farber
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_visitors extends contentBlocks implements iContentBlock
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
		$this->DB         = $registry->DB();
		$this->settings   = $registry->settings();
	}
	
	/**
	 * Returns html for the latest visitors block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* Check for config data */
		$config                        = unserialize($cblock['cblock_config']);
		$config['num_latest_visitors'] = $config['num_latest_visitors'] ? $config['num_latest_visitors'] : 5;
		
		if( !$config['num_latest_visitors'] && $cblock['member_id'] == $this->memberData['member_id'] )
		{
			return $this->getConfigForm( $cblock );
		}

		$_pp_last_visitors = $this->blog['blog_last_visitors'] ? unserialize($this->blog['blog_last_visitors']) : array();
		$_visitor_info     = array();
		$_count            = 0;

		if( is_array($_pp_last_visitors) )
		{
			krsort($_pp_last_visitors);

			$_ids = array_values($_pp_last_visitors);
			
			if( is_array($_ids) && count($_ids) )
			{
				$_visitor_info = IPSMember::load( $_ids, 'extendedProfile, groups', 'id' );
				
				foreach( $_visitor_info as $mid => $row )
				{
					$_visitor_info[ $mid ] = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
				}
			
				foreach( $_pp_last_visitors as $_time => $_id )
				{
					if ( $_count + 1 > $config['num_latest_visitors'] )
					{
						break;
					}
				
					$_count++;
				
					$_visitor_info[ $_id ]['visited_date'] = $_time;
	
					$visitors[] = $_visitor_info[ $_id ];
				}
			}
		}
		
		$return_html  = $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, $this->lang->words['blog_latest_visitors'], $this->configable, true );
		$return_html .= $this->registry->output->getTemplate('blog_cblocks')->latest_visitors( $visitors );
		$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
		
		// Add the new recent visitor
		
		// @todo: un-comment and replace this code below once the blog version is compatible with IPB 3.3+ only
		//if ( ! IPSMember::isLoggedInAnon($this->memberData) )
		
		$privacy = explode( '&', $this->memberData['login_anonymous'] );
		
		if( ! $privacy[0] )
		{
			$this->blogAddVisitor( $cblock, $this->memberData['member_id'] );
		}
	
		return  $return_html;
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
		$config['num_latest_visitors'] = $config['num_latest_visitors'] ? $config['num_latest_visitors'] : 5;
			
		$return_html  = $this->registry->output->getTemplate('blog_cblocks')->cblock_header( $cblock, $this->lang->words['blog_latest_visitors'] );
		$return_html .= $this->registry->output->getTemplate('blog_cblocks')->config_visitors( $config, $cblock );
		$return_html .= $this->registry->output->getTemplate('blog_cblocks')->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
		
		return $return_html;
	}
	
 	/**
 	* Adds a recent visitor to your blog
 	*
 	* @return	@e void
 	* @since	IP.Blog 1.4
 	*/
 	public function blogAddVisitor( $cblock, $member_id_to_add=0 )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id_to_add = intval($member_id_to_add);
		$found			  = 0;
		$_recent_visitors = array();
		
		/* Check for config data */
		$config = @unserialize($cblock['cblock_config']);
		
		$config['num_latest_visitors'] = $config['num_latest_visitors'] ? $config['num_latest_visitors'] : 5;
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $member_id_to_add )
		{
			return false;
		}
		
		if( $member_id_to_add == $this->blog['member_id'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Sort out data...
		//-----------------------------------------
		
		$recent_visitors = @unserialize($this->blog['blog_last_visitors']);
		
		if ( ! is_array($recent_visitors) OR !count($recent_visitors) )
		{
			$recent_visitors = array();
		}
		
		foreach( $recent_visitors as $_time => $_id )
		{
			if ( $_id == $member_id_to_add )
			{
				$found  = 1;
				continue;
			}
			else
			{
				$_recent_visitors[ $_time ] = $_id;
			}
		}
		
		$recent_visitors = $_recent_visitors;
		
		krsort($recent_visitors);
		
		//-----------------------------------------
		// Pop one off if we didn't update...
		//-----------------------------------------
	
		if ( ! $found )
		{
			# Over? Pop one off...
			if ( count( $recent_visitors ) > $config['num_latest_visitors'] )
			{
				$_tmp = array_pop( $recent_visitors );
			}
		}
		
		# Add in ours..	
		$recent_visitors[ time() ] = $member_id_to_add;
		
		krsort($recent_visitors);
		
		//-----------------------------------------
		// Update profile...
		//-----------------------------------------

		$this->DB->update( 'blog_blogs ', array( 'blog_last_visitors' => serialize($recent_visitors) ), 'blog_id=' . $this->blog['blog_id'], true );
		
		return true;
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