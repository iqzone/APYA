<?php

/**
 * Online friends plugin
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8752 $ 
 * @since		1st March 2009
 */

class plugin_online_friends implements pluginBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $registry;
	protected $caches;
	protected $request;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
		$this->memberData	=& $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @return	array
	 */
	public function getTags()
	{
		$_finalColumns		= array();
		$_noinfoColumns		= array();
		
		foreach( $this->DB->getFieldNames( 'sessions' ) as $_column )
		{
			if( $this->lang->words['col__sessions_' . $_column ] )
			{
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__sessions_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}

		foreach( $this->DB->getFieldNames( 'members' ) as $_column )
		{
			if( $this->lang->words['col__members_' . $_column ] )
			{
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__members_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		$_fieldInfo	= array();
		
		$this->DB->buildAndFetch( array( 'select' => 'pf_id,pf_title,pf_desc', 'from' => 'pfields_data' ) );
		$this->DB->execute();
		
		while( $r= $this->DB->fetch() )
		{
			$_fieldInfo[ $r['pf_id'] ]	= $r;
		}

		foreach( $this->DB->getFieldNames( 'pfields_content' ) as $_column )
		{
			if( $this->lang->words['col__pfields_content_' . $_column ] )
			{
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__pfields_content_' . $_column ] );
			}
			else if( preg_match( "/^field_(\d+)$/", $_column, $matches ) AND isset( $_fieldInfo[ $matches[1] ] ) )
			{
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		foreach( $this->DB->getFieldNames( 'profile_portal' ) as $_column )
		{
			if( $this->lang->words['col__profile_portal_' . $_column ] )
			{
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__profile_portal_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		$_finalColumns['my_member_id']		= array( "&#36;r['my_member_id']", $this->lang->words['col__special_my_member_id'] );
		$_finalColumns['pp_main_photo']		= array( "&#36;r['pp_main_photo']", $this->lang->words['col__special_pp_main_photo'] );
		$_finalColumns['pp_main_width']		= array( "&#36;r['pp_main_width']", $this->lang->words['col__special_pp_main_width'] );
		$_finalColumns['pp_main_height']	= array( "&#36;r['pp_main_height']", $this->lang->words['col__special_pp_main_height'] );
		$_finalColumns['_has_photo']		= array( "&#36;r['_has_photo']", $this->lang->words['col__special__has_photo'] );
		$_finalColumns['pp_small_photo']	= array( "&#36;r['pp_small_photo']", $this->lang->words['col__special_pp_small_photo'] );
		$_finalColumns['pp_small_width']	= array( "&#36;r['pp_small_width']", $this->lang->words['col__special_pp_small_width'] );
		$_finalColumns['pp_small_height']	= array( "&#36;r['pp_small_height']", $this->lang->words['col__special_pp_small_height'] );
		$_finalColumns['pp_mini_photo']		= array( "&#36;r['pp_mini_photo']", $this->lang->words['col__special_pp_mini_photo'] );
		$_finalColumns['pp_mini_width']		= array( "&#36;r['pp_mini_width']", $this->lang->words['col__special_pp_mini_width'] );
		$_finalColumns['pp_mini_height']	= array( "&#36;r['pp_mini_height']", $this->lang->words['col__special_pp_mini_height'] );
		$_finalColumns['member_rank_img_i']	= array( "&#36;r['member_rank_img_i']", $this->lang->words['col__special_member_rank_img_i'] );
		$_finalColumns['member_rank_img']	= array( "&#36;r['member_rank_img']", $this->lang->words['col__special_member_rank_img'] );

		return array(
					$this->lang->words['block_plugin__generic']	=> array( 
																		array( '&#36;title', $this->lang->words['block_custom__title'] ) ,
																		),	
						
					$this->lang->words['block_plugin__of_users']	=> array(
																		array( '&#36;friends', $this->lang->words['block_plugin__of_us'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																		),
					);
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo()
	{
		return array(
					'key'			=> 'online_friends',
					'name'			=> $this->lang->words['plugin_name__online_friends'],
					'description'	=> $this->lang->words['plugin_description__online_friends'],
					'hasConfig'		=> false,
					'templateBit'	=> 'block__online_friends',
					);
	}
	
	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig( $session )
	{
		return array();
	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig( $data )
	{
		return array( true, $data );
	}
	
	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin( $block )
	{
		$config			= unserialize($block['block_config']);
		
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );
		
		$friends		= array();
		$onlineFriends	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $this->memberData['member_id'] ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$friends[ $r['friends_friend_id'] ]	= $r['friends_friend_id'];
		}

		if( count($friends) )
		{
			$_time		= $this->settings['au_cutoff'] * 60;
			$_cutoff	= time() - $_time;

			$this->DB->build( array(
									'select'	=> 's.*',
									'from'		=> array( 'sessions' => 's' ),
									'where'		=> 's.member_id IN(' . implode( ',', $friends ) . ') AND s.running_time > ' . $_cutoff,
									'order'		=> 's.running_time DESC',
									'add_join'	=> array(
														array(
															'select'	=> 'm.*, m.member_id as my_member_id',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=s.member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pf.*',
															'from'		=> array( 'pfields_content' => 'pf' ),
															'where'		=> 'pf.member_id=m.member_id',
															'type'		=> 'left',
															),
														array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left',
															),
														)
							)		);
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$r['member_id']	= $r['my_member_id'];
				
				$r	= IPSMember::buildDisplayData( $r );

				$onlineFriends[ $r['member_id'] ]	= $r;
			}
		}
		
		
		if( $config['hide_empty'] AND !count($onlineFriends) )
		{
			return '';
		}

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		ob_start();
 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $onlineFriends );
 		ob_end_clean();
 		return $_return;
	}
}