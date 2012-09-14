<?php
/**
 * Member feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10065 $ 
 * @since		1st March 2009
 */

class feed_members implements feedBlockInterface
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
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @param	string		Additional info (database id;type)
	 * @return	array
	 */
	public function getTags( $info='' )
	{
		$_return			= array();
		$_noinfoColumns		= array();

		//-----------------------------------------
		// Switch on type
		//-----------------------------------------
		
		switch( $info )
		{
			case 'members':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__memberurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__memberdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__membertitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__membercontent'] ),
											);

				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_members']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__members'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'comments':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__memcommurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__memcommdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__memcommtitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__memcommcontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'member_status_updates' ) as $_column )
				{
					if( $this->lang->words['col__profile_comments_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__profile_comments_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns, 'poster' );
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns, 'receiver' );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_memcomm']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__memcomm'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'status':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__statusurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__statusdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__statustitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__statuscontent'] ),
											);

				foreach( $this->DB->getFieldNames( 'member_status_updates' ) as $_column )
				{
					if( $this->lang->words['col__member_status_updates_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__member_status_updates_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'member_status_replies' ) as $_column )
				{
					if( $this->lang->words['col__member_status_replies_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__member_status_replies_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns, 'member' );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_status']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__status'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
		}

		return $_return;
	}
	
	/**
	 * Appends member columns to existing arrays
	 *
	 * @access	protected
	 * @param	array 		Columns that have descriptions
	 * @param	array 		Columns that do not have descriptions
	 * @param	string		Optional array depth column
	 * @return	@e void		[Params are passed by reference and modified]
	 */
	protected function _addMemberColumns( &$_finalColumns, &$_noinfoColumns, $depth='' )
	{
		foreach( $this->DB->getFieldNames( 'sessions' ) as $_column )
		{
			if( $depth )
			{
				if( $this->lang->words['col__sessions_' . $_column ] )
				{
					unset($_finalColumns[ $depth . $_column ]);
					$_finalColumns[ $depth . $_column ]		= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['col__sessions_' . $_column ] );
				}
				else
				{
					$_noinfoColumns[ $depth . $_column ]	= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
			}
			else
			{
				if( $this->lang->words['col__sessions_' . $_column ] )
				{
					unset($_finalColumns[ $_column ]);
					$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__sessions_' . $_column ] );
				}
				else
				{
					$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
			}
		}

		foreach( $this->DB->getFieldNames( 'members' ) as $_column )
		{
			if( $depth )
			{
				if( $this->lang->words['col__members_' . $_column ] )
				{
					unset($_finalColumns[ $depth . $_column ]);
					$_finalColumns[ $depth . $_column ]	= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['col__members_' . $_column ] );
				}
				else
				{
					$_noinfoColumns[ $depth . $_column ]	= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}

			}
			else
			{
				if( $this->lang->words['col__members_' . $_column ] )
				{
					unset($_finalColumns[ $_column ]);
					$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__members_' . $_column ] );
				}
				else
				{
					$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
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
			if( $depth )
			{
				if( $this->lang->words['col__pfields_content_' . $_column ] )
				{
					unset($_finalColumns[ $depth . $_column ]);
					$_finalColumns[ $depth . $_column ]		= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['col__pfields_content_' . $_column ] );
				}
				else if( preg_match( "/^field_(\d+)$/", $_column, $matches ) AND isset( $_fieldInfo[ $matches[1] ] ) )
				{
					unset($_finalColumns[ $depth . $_column ]);
					$_column					= str_replace( 'field_', 'field_', $_column );
					$_finalColumns[ $depth . $_column ]		= array( "&#36;r['{$depth}']['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
				}
				else
				{
					$_column					= str_replace( 'field_', 'field_', $_column );
					$_noinfoColumns[ $depth . $_column ]	= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
			}
			else
			{
				if( $this->lang->words['col__pfields_content_' . $_column ] )
				{
					unset($_finalColumns[ $_column ]);
					$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__pfields_content_' . $_column ] );
				}
				else if( preg_match( "/^field_(\d+)$/", $_column, $matches ) AND isset( $_fieldInfo[ $matches[1] ] ) )
				{
					unset($_finalColumns[ $_column ]);
					$_column					= str_replace( 'field_', 'field_', $_column );
					$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
				}
				else
				{
					$_column					= str_replace( 'field_', 'field_', $_column );
					$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
			}
		}
		
		foreach( $this->DB->getFieldNames( 'profile_portal' ) as $_column )
		{
			if( $depth )
			{
				if( $this->lang->words['col__profile_portal_' . $_column ] )
				{
					unset($_finalColumns[ $depth . $_column ]);
					$_finalColumns[ $depth . $_column ]	= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['col__profile_portal_' . $_column ] );
				}
				else
				{
					$_noinfoColumns[ $depth . $_column ]	= array( "&#36;r['{$depth}']['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
			}
			else
			{
				if( $this->lang->words['col__profile_portal_' . $_column ] )
				{
					unset($_finalColumns[ $_column ]);
					$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__profile_portal_' . $_column ] );
				}
				else
				{
					$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
				}
			}
		}

		if( $depth )
		{
			$_finalColumns[ $depth . 'pp_main_photo']		= array( "&#36;r['{$depth}']['pp_main_photo']", $this->lang->words['col__special_pp_main_photo'] );
			$_finalColumns[ $depth . '_has_photo']			= array( "&#36;r['{$depth}']['_has_photo']", $this->lang->words['col__special__has_photo'] );
			$_finalColumns[ $depth . 'pp_small_photo']		= array( "&#36;r['{$depth}']['pp_small_photo']", $this->lang->words['col__special_pp_small_photo'] );
			$_finalColumns[ $depth . 'pp_mini_photo']		= array( "&#36;r['{$depth}']['pp_mini_photo']", $this->lang->words['col__special_pp_mini_photo'] );
			$_finalColumns[ $depth . 'member_rank_img_i']	= array( "&#36;r['{$depth}']['member_rank_img_i']", $this->lang->words['col__special_member_rank_img_i'] );
			$_finalColumns[ $depth . 'member_rank_img']		= array( "&#36;r['{$depth}']['member_rank_img']", $this->lang->words['col__special_member_rank_img'] );
		}
		else
		{
			$_finalColumns['pp_main_photo']		= array( "&#36;r['pp_main_photo']", $this->lang->words['col__special_pp_main_photo'] );
			$_finalColumns['_has_photo']		= array( "&#36;r['_has_photo']", $this->lang->words['col__special__has_photo'] );
			$_finalColumns['pp_small_photo']	= array( "&#36;r['pp_small_photo']", $this->lang->words['col__special_pp_small_photo'] );
			$_finalColumns['pp_mini_photo']		= array( "&#36;r['pp_mini_photo']", $this->lang->words['col__special_pp_mini_photo'] );
			$_finalColumns['member_rank_img_i']	= array( "&#36;r['member_rank_img_i']", $this->lang->words['col__special_member_rank_img_i'] );
			$_finalColumns['member_rank_img']	= array( "&#36;r['member_rank_img']", $this->lang->words['col__special_member_rank_img'] );
		}
	}
	
	/**
	 * Provides the ability to modify the feed type or content type values
	 * before they are passed into the gallery template search query
	 *
	 * @access 	public
	 * @param 	string 		Current feed type 
	 * @param 	string 		Current content type
	 * @return 	array 		Array with two keys: feed_type and content_type
	 */
	public function returnTemplateGalleryKeys( $feed_type, $content_type )
	{		
		return array( 'feed_type' => $feed_type, 'content_type' => $content_type );
	}

	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		return array(
					'key'			=> 'members',
					'app'			=> 'members',
					'name'			=> $this->lang->words['feed_name__members'],
					'description'	=> $this->lang->words['feed_description__members'],
					'hasFilters'	=> true,
					'templateBit'	=> 'feed__generic',
					'inactiveSteps'	=> array( ),
					);
	}
	
	/**
	 * Get the feed's available content types.  Returns form elements and data
	 *
	 * @param	array 			Session data
	 * @param	array 			true: Return an HTML radio list; false: return an array of types
	 * @return	array 			Form data
	 */
	public function returnContentTypes( $session = array(), $asHTML = true )
	{
		$_types		= array(
							array( 'members', $this->lang->words['ct_members'] ),
							array( 'comments', $this->lang->words['ct_pcomments'] ),
							array( 'status', $this->lang->words['ct_statuses'] ),
							);
		$_html		= array();
		
		if( !$asHTML )
		{
			return $_types;
		}
		
		foreach( $_types as $_type )
		{
			$_html[]	= "<input type='radio' name='content_type' id='content_type_{$_type[0]}' value='{$_type[0]}'" . ( $session['config_data']['content_type'] == $_type[0] ? " checked='checked'" : '' ) . " /> <label for='content_type_{$_type[0]}'>{$_type[1]}</label>"; 
		}
		
		return array(
					array(
						'label'			=> $this->lang->words['generic__select_contenttype'],
						'description'	=> '',
						'field'			=> '<ul style="line-height: 1.6"><li>' . implode( '</li><li>', $_html ) . '</ul>',
						)
					);
	}
	
	/**
	 * Check the feed content type selection
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		if( !in_array( $data['content_type'], array( 'members', 'comments', 'status' ) ) )
		{
			$data['content_type']	= 'members';
		}

		return array( true, $data['content_type'] );
	}
	
	/**
	 * Get the feed's available filter options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnFilters( $session )
	{
		$filters	= array();

		$groups		= array();
		
		foreach( $this->caches['group_cache'] as $group )
		{
			$groups[]	= array( $group['g_id'], $group['g_title'] );
		}

		switch( $session['config_data']['content_type'] )
		{
			case 'members':
			default:
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__groups'],
									'description'	=> $this->lang->words['feed_members__groups_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_groups[]', $groups, explode( ',', $session['config_data']['filters']['filter_groups'] ), 8 ),
									);
		
				$session['config_data']['filters']['filter_posts']			= $session['config_data']['filters']['filter_posts'] ? $session['config_data']['filters']['filter_posts'] : 0;
				$session['config_data']['filters']['filter_bday_day']		= $session['config_data']['filters']['filter_bday_day'] ? $session['config_data']['filters']['filter_bday_day'] : 0;
				$session['config_data']['filters']['filter_bday_mon']		= $session['config_data']['filters']['filter_bday_mon'] ? $session['config_data']['filters']['filter_bday_mon'] : 0;
				$session['config_data']['filters']['filter_has_blog']		= $session['config_data']['filters']['filter_has_blog'] ? $session['config_data']['filters']['filter_has_blog'] : 0;
				$session['config_data']['filters']['filter_has_gallery']	= $session['config_data']['filters']['filter_has_gallery'] ? $session['config_data']['filters']['filter_has_gallery'] : 0;
				$session['config_data']['filters']['filter_min_rating']		= $session['config_data']['filters']['filter_min_rating'] ? $session['config_data']['filters']['filter_min_rating'] : 0;
				$session['config_data']['filters']['filter_min_rep']		= $session['config_data']['filters']['filter_min_rep'] ? $session['config_data']['filters']['filter_min_rep'] : 0;
				$session['config_data']['filters']['filter_online']			= $session['config_data']['filters']['filter_online'] ? $session['config_data']['filters']['filter_online'] : 0;
				$session['config_data']['filters']['filter_friends']		= $session['config_data']['filters']['filter_friends'] ? $session['config_data']['filters']['filter_friends'] : 0;
				
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__posts'],
									'description'	=> $this->lang->words['feed_members__posts_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_posts', $session['config_data']['filters']['filter_posts'] ),
									);
		
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__friends'],
									'description'	=> $this->lang->words['feed_members__friends_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_friends', $session['config_data']['filters']['filter_friends'] ),
									);
		
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__online'],
									'description'	=> $this->lang->words['feed_members__online_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_online', $session['config_data']['filters']['filter_online'] ),
									);
		
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__bdayday'],
									'description'	=> $this->lang->words['feed_members__bdayday_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_bday_day', $session['config_data']['filters']['filter_bday_day'] ),
									);
		
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__bdaymon'],
									'description'	=> $this->lang->words['feed_members__bdaymon_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_bday_mon', $session['config_data']['filters']['filter_bday_mon'] ),
									);
		
				if( IPSLib::appIsInstalled('blog') )
				{
					$filters[]	= array(
										'label'			=> $this->lang->words['feed_members__blog'],
										'description'	=> $this->lang->words['feed_members__blog_desc'],
										'field'			=> $this->registry->output->formYesNo( 'filter_has_blog', $session['config_data']['filters']['filter_has_blog'] ),
										);
				}
		
				if( IPSLib::appIsInstalled('gallery') )
				{
					$filters[]	= array(
										'label'			=> $this->lang->words['feed_members__gallery'],
										'description'	=> $this->lang->words['feed_members__gallery_desc'],
										'field'			=> $this->registry->output->formYesNo( 'filter_has_gallery', $session['config_data']['filters']['filter_has_gallery'] ),
										);
				}
		
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__rating'],
									'description'	=> $this->lang->words['feed_members__rating_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_min_rating', $session['config_data']['filters']['filter_min_rating'] ),
									);
		
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_members__rep'],
									'description'	=> $this->lang->words['feed_members__rep_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_min_rep', $session['config_data']['filters']['filter_min_rep'] ),
									);
			break;
			
			case 'comments':
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_membersc__pgroups'],
									'description'	=> $this->lang->words['feed_membersc__pgroups_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_pgroups[]', $groups, explode( ',', $session['config_data']['filters']['filter_pgroups'] ), 8 ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_membersc__rgroups'],
									'description'	=> $this->lang->words['feed_membersc__rgroups_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_rgroups[]', $groups, explode( ',', $session['config_data']['filters']['filter_rgroups'] ), 8 ),
									);

				$session['config_data']['filters']['filter_poster']		= $session['config_data']['filters']['filter_poster'] ? $session['config_data']['filters']['filter_poster'] : '';
				$session['config_data']['filters']['filter_receiver']	= $session['config_data']['filters']['filter_receiver'] ? $session['config_data']['filters']['filter_receiver'] : '';

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_membersc__poster'],
									'description'	=> $this->lang->words['feed_membersc__poster_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_poster', $session['config_data']['filters']['filter_poster'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_membersc__receiver'],
									'description'	=> $this->lang->words['feed_membersc__receiver_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_receiver', $session['config_data']['filters']['filter_receiver'] ),
									);
			break;
			
			case 'status':
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_membersc__pgroups'],
									'description'	=> $this->lang->words['feed_membersc__pgroups_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_pgroups[]', $groups, explode( ',', $session['config_data']['filters']['filter_pgroups'] ), 8 ),
									);

				$session['config_data']['filters']['filter_replies']	= $session['config_data']['filters']['filter_replies'] ? $session['config_data']['filters']['filter_replies'] : 0;
				$session['config_data']['filters']['filter_poster']		= $session['config_data']['filters']['filter_poster'] ? $session['config_data']['filters']['filter_poster'] : '';

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_memberss__increplies'],
									'description'	=> $this->lang->words['feed_memberss__increplies_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_replies', $session['config_data']['filters']['filter_replies'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_memberss__poster'],
									'description'	=> $this->lang->words['feed_memberss__poster_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_poster', $session['config_data']['filters']['filter_poster'] ),
									);
									
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_memberss__locked'],
									'description'	=> $this->lang->words['feed_memberss__locked_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_locked', $session['config_data']['filters']['filter_locked'] ),
									);
									
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_memberss__latest'],
									'description'	=> $this->lang->words['feed_memberss__latest_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_latest', $session['config_data']['filters']['filter_latest'] ),
									);
			break;
		}

		return $filters;
	}
	
	/**
	 * Check the feed filters selection
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedFilters( $session, $data )
	{
		$filters	= array();
		
		switch( $session['config_data']['content_type'] )
		{
			case 'members':
			default:
				$filters['filter_groups']		= is_array($data['filter_groups']) ? implode( ',', $data['filter_groups'] ) : '';
				$filters['filter_posts']		= intval($data['filter_posts']);
				$filters['filter_bday_day']		= intval($data['filter_bday_day']);
				$filters['filter_bday_mon']		= intval($data['filter_bday_mon']);
				$filters['filter_has_blog']		= intval($data['filter_has_blog']);
				$filters['filter_has_gallery']	= intval($data['filter_has_gallery']);
				$filters['filter_min_rating']	= intval($data['filter_min_rating']);
				$filters['filter_min_rep']		= intval($data['filter_min_rep']);
				$filters['filter_friends']		= intval($data['filter_friends']);
				$filters['filter_online']		= intval($data['filter_online']);
			break;
			
			case 'comments':
				$filters['filter_pgroups']		= is_array($data['filter_pgroups']) ? implode( ',', $data['filter_pgroups'] ) : '';
				$filters['filter_rgroups']		= is_array($data['filter_rgroups']) ? implode( ',', $data['filter_rgroups'] ) : '';

				$filters['filter_poster']		= $data['filter_poster'] ? $data['filter_poster'] : '';
				$filters['filter_receiver']		= $data['filter_receiver'] ? $data['filter_receiver'] : '';
			break;
			
			case 'status':
				$filters['filter_pgroups']		= is_array($data['filter_pgroups']) ? implode( ',', $data['filter_pgroups'] ) : '';
				$filters['filter_poster']		= $data['filter_poster'] ? $data['filter_poster'] : '';
				$filters['filter_replies']		= intval($data['filter_replies']);
				$filters['filter_locked']		= intval($data['filter_locked']);
				$filters['filter_latest']		= intval($data['filter_latest']);
			break;
		}

		return array( true, $filters );
	}
	
	/**
	 * Get the feed's available ordering options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnOrdering( $session )
	{
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;
		$session['config_data']['sortby']		= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'posts';

		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'members':
			default:
				$sortby	= array( 
								array( 'name', $this->lang->words['sort_members__name'] ), 
								array( 'posts', $this->lang->words['sort_members__posts'] ), 
								array( 'joined', $this->lang->words['sort_members__joined'] ),
								array( 'last_active', $this->lang->words['sort_members__lastactive'] ),
								array( 'last_post', $this->lang->words['sort_members__lastpost'] ),
								array( 'age', $this->lang->words['sort_members__age'] ),
								array( 'profile_views', $this->lang->words['sort_members__views'] ),
								array( 'rating', $this->lang->words['sort_members__rating'] ),
								array( 'rep', $this->lang->words['sort_members__rep'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;
			
			case 'comments':
				$sortby	= array( 
								array( 'date', $this->lang->words['sort_membersc__date'] ), 
								);
			break;
			
			case 'status':
				$sortby	= array( 
								array( 'date', $this->lang->words['sort_memberss__date'] ), 
								array( 'replies', $this->lang->words['sort_memberss__replies'] ), 
								);
			break;
		}

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_sort_by'],
							'description'	=> $this->lang->words['feed_sort_by_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
							);
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_order_direction'],
							'description'	=> $this->lang->words['feed_order_direction_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortorder', array( array( 'desc', 'DESC' ), array( 'asc', 'ASC' ) ), $session['config_data']['sortorder'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_start'],
							'description'	=> $this->lang->words['feed_limit_offset_start_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_start', $session['config_data']['offset_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_end'],
							'description'	=> $this->lang->words['feed_limit_offset_end_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_end', $session['config_data']['offset_end'] ),
							);
		
		return $filters;
	}
	
	/**
	 * Check the feed ordering options
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Ordering data to use )
	 */
	public function checkFeedOrdering( $data, $session )
	{
		$limits						= array();

		$sortby						= array( 'name', 'posts', 'joined', 'last_active', 'last_post', 'age', 'profile_views', 'rating', 'rep', 'rand', 'date', 'replies' );

		$limits['sortby']			= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'posts';
		
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		return array( true, $limits );
	}
	
	/**
	 * Execute the feed and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function executeFeed( $block, $previewMode=false )
	{
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );
		
		$config	= unserialize( $block['block_config'] );
		
		switch( $config['content'] )
		{
			case 'members':
			default:
				$_whereMembers	= array();
				$_whereOthers	= array();
				$order			= '';
				$_hasOtherOrd	= false;
				$skipQuerying	= false;
				
				//-----------------------------------------
				// Set up filtering clauses
				//-----------------------------------------
		
				if( $config['filters']['filter_groups'] )
				{
					$_whereMembers[]	= "m.member_group_id IN(" . $config['filters']['filter_groups'] . ")";
				}
				
				if( $config['filters']['filter_posts'] )
				{
					$_whereMembers[]	= "m.posts > " . $config['filters']['filter_posts'];
				}
				
				if( $config['filters']['filter_bday_day'] )
				{
					$_whereMembers[]	= "m.bday_day=" . date('j', IPS_UNIX_TIME_NOW + $this->registry->class_localization->getTimeOffset() ) . " AND m.bday_month=" . date('n', IPS_UNIX_TIME_NOW + $this->registry->class_localization->getTimeOffset() );
				}
		
				if( $config['filters']['filter_bday_mon'] )
				{
					$_whereMembers[]	= "m.bday_month=" . date('n', IPS_UNIX_TIME_NOW + $this->registry->class_localization->getTimeOffset() );
				}
		
				if( $config['filters']['filter_has_blog'] )
				{
					$_whereMembers[]	= "m.has_blog NOT IN( '', 'a:0{}' )";
				}
	
				if( $config['filters']['filter_has_gallery'] )
				{
					$_whereMembers[]	= "m.has_gallery=1";
				}
		
				if( $config['filters']['filter_min_rating'] )
				{
					$_whereOthers[]	= "p.pp_rating_value >= " . $config['filters']['filter_min_rating'];
				}
		
				if( $config['filters']['filter_min_rep'] )
				{
					$_whereOthers[]	= "p.pp_reputation_points >= " . $config['filters']['filter_min_rep'];
				}
				
				if( $config['filters']['filter_friends'] )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder	= new $classToLoad( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$_whereMembers[]	= "m.member_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						if( $config['hide_empty'] )
						{
							return '';
						}

						$_whereMembers	= array(); // Still need to proceed so we can return an empty block
						$skipQuerying	= true;
					}
				}
				
				if( $config['filters']['filter_online'] )
				{
					$_whereOthers[]	= "s.id " . $this->DB->buildIsNull( false );
					$_whereOthers[] = 's.login_type=0';
				}
		
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"m.members_display_name ";
					break;
		
					default:
					case 'posts':
						$order	.=	"m.posts ";
					break;
					
					case 'joined':
						$order	.=	"m.joined ";
					break;
		
					case 'last_active':
						$order	.=	"m.last_activity ";
					break;
		
					case 'last_post':
						$order	.=	"m.last_post ";
					break;
		
					case 'age':
						$_whereMembers[]	= "m.bday_year IS NOT NULL AND m.bday_year > 0";
						$order	.=	"m.bday_year " . $config['sortorder'] . ",m.bday_month " . $config['sortorder'] . ",m.bday_day ";
					break;
		
					case 'profile_views':
						$order	.=	"m.members_profile_views ";
					break;
		
					case 'rating':
						$order	.=	"p.pp_rating_value ";
						$_hasOtherOrd	= true;
					break;
		
					case 'rep':
						$order	.=	"p.pp_reputation_points ";
						$_hasOtherOrd	= true;
					break;
		
					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
				
				$order	.= $config['sortorder'];
				
				//-----------------------------------------
				// If only pulling from members table, or ordering
				// on members table, try to make efficient...
				//-----------------------------------------
				
				if( !count($_whereOthers) AND !$_hasOtherOrd AND !$skipQuerying )
				{
					$mids		= array();
					$members	= array();
					$_order		= 0;
					
					$this->DB->build( array(
											'select'	=> 'm.member_id',
											'from'		=> 'members m',
											'where'		=> implode( ' AND ', $_whereMembers ),
											'order'		=> $order,
											'limit'		=> array( $config['offset_a'], $config['offset_b'] )
									)		);

					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$mids[ $_order ]	= $r['member_id'];
						
						$_order++;
					}
					
					$_members	= IPSMember::load( $mids );
					
					if( count($_members) )
					{
						foreach( $_members as $r )
						{
							//-----------------------------------------
							// Normalization
							//-----------------------------------------
			
							$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $r['member_id'], 'none', $r['members_seo_name'], 'showuser' );
							$r['title']		= $r['members_display_name'];
							$r['date']		= $r['joined'];
							$r['content']	= $r['pp_about_me'];
							
							IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
							IPSText::getTextClass( 'bbcode' )->parse_html				= $r['g_dohtml'];
							IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
							IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
							IPSText::getTextClass( 'bbcode' )->parsing_section			= 'aboutme';
							IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
							IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
				
							$r['content']	= IPSText::getTextClass('bbcode')->preDisplayParse( $r['content'] );

							$r				= IPSMember::buildDisplayData( $r );
							
							$_orderLookup	= array_keys( $mids, $r['member_id'] );
		
							$members[ $_orderLookup[0] ]		= $r;
						}
						
						ksort($members);
					}
				}
				else if( !$skipQuerying )
				{
					$where	= array_merge( $_whereMembers, $_whereOthers );
					
					//-----------------------------------------
					// Run the query and get the results
					//-----------------------------------------
					
					$members	= array();
					
					$this->DB->build( array(
											'select'	=> 'm.*, m.member_id as mid',
											'from'		=> array( 'members' => 'm' ),
											'where'		=> implode( ' AND ', $where ),
											'order'		=> $order,
											'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
											'add_join'	=> array(
																array(
																	'select'	=> 'p.*',
																	'from'		=> array( 'profile_portal' => 'p' ),
																	'where'		=> 'p.pp_member_id=m.member_id',
																	'type'		=> 'left',
																	),
																array(
																	'select'	=> 'pf.*',
																	'from'		=> array( 'pfields_content' => 'pf' ),
																	'where'		=> 'pf.member_id=m.member_id',
																	'type'		=> 'left',
																	),
																array(
																	'select'	=> 's.*',
																	'from'		=> array( 'sessions' => 's' ),
																	'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																	'type'		=> 'left',
																	),
																)
									)		);
					$outer	= $this->DB->execute();
					
					while( $r = $this->DB->fetch($outer) )
					{
						//-----------------------------------------
						// Normalization
						//-----------------------------------------
						
						$r['member_id']	= $r['mid'];
						$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $r['member_id'], 'none', $r['members_seo_name'], 'showuser' );
						$r['title']		= $r['members_display_name'];
						$r['date']		= $r['joined'];
						$r['content']	= $r['pp_about_me'];
						
						IPSText::getTextClass('bbcode')->parse_smilies			= 1;
						IPSText::getTextClass('bbcode')->parse_html				= $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'];
						IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
						IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
						IPSText::getTextClass('bbcode')->parsing_section		= 'aboutme';
						IPSText::getTextClass('bbcode')->parsing_mgroup			= $r['member_group_id'];
						IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $r['mgroup_others'];
			
						$r['content']	= IPSText::getTextClass('bbcode')->preDisplayParse( $r['content'] );

						$r				= IPSMember::buildDisplayData( $r );
						
						$members[]		= $r;
					}
				}
			break;
			
			case 'comments':
				$where		= array();
				$order		= '';
				
				//-----------------------------------------
				// Set up filtering clauses
				//-----------------------------------------
		
				if( $config['filters']['filter_pgroups'] )
				{
					$where[]	= "mp.member_group_id IN(" . $config['filters']['filter_pgroups'] . ")";
				}
				
				if( $config['filters']['filter_rgroups'] )
				{
					$where[]	= "mr.member_group_id IN(" . $config['filters']['filter_rgroups'] . ")";
				}
				
				if( $config['filters']['filter_poster'] == 'myself' )
				{
					$where[]	= "c.status_author_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_poster'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder	= new $classToLoad( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "c.status_author_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_poster'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_poster'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$where[]	= "c.status_author_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
		
				if( $config['filters']['filter_receiver'] == 'myself' )
				{
					$where[]	= "c.status_member_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_receiver'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder	= new $classToLoad( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "c.status_member_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_receiver'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_receiver'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$where[]	= "c.status_member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
		
				switch( $config['sortby'] )
				{
					case 'date':
					default:
						$order	.=	"c.status_date ";
					break;
				}
				
				$order	.= $config['sortorder'];

				//-----------------------------------------
				// Run the query and get the results
				//-----------------------------------------
				
				$members	= array();
				
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'member_status_updates' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'mp.member_id as posterid',
																'from'		=> array( 'members' => 'mp' ),
																'where'		=> 'mp.member_id=c.status_author_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'mr.member_id as receiverid',
																'from'		=> array( 'members' => 'mr' ),
																'where'		=> 'mr.member_id=c.status_member_id',
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					$r['poster']	= IPSMember::load( $r['posterid'] );
					$r['receiver']	= IPSMember::load( $r['receiverid'] );
					
					$r['member_id']	= $r['posterid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $r['receiverid'], 'none', $r['receiver']['members_seo_name'], 'showuser' ) . '#comment_id_' . $r['comment_id'];
					$r['title']		= $this->lang->words['comment_for_prefix'] . ' ' . $r['receiver']['members_display_name'];
					$r['date']		= $r['status_date'];
					$r['content']	= $r['status_content'];

					$r['poster']	= IPSMember::buildDisplayData( $r['poster'] );
					$r['receiver']	= IPSMember::buildDisplayData( $r['receiver'] );
					
					$members[]		= $r;
				}
			break;
			
			case 'status':
				$whereS		= array();
				$whereR	= array();
				$order		= '';
				
				//-----------------------------------------
				// Set up filtering clauses
				//-----------------------------------------
		
				if( $config['filters']['filter_pgroups'] )
				{
					$whereS[]	= "m.member_group_id IN(" . $config['filters']['filter_pgroups'] . ")";
					$whereR[]	= "m.member_group_id IN(" . $config['filters']['filter_pgroups'] . ")";
				}

				if( $config['filters']['filter_poster'] == 'myself' )
				{
					$whereS[]	= "s.status_member_id = " . $this->memberData['member_id'];
					$whereR[]	= "r.reply_member_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_poster'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder	= new $classToLoad( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$whereS[]	= "s.status_member_id IN( " . implode( ',', $friends ) . ")";
						$whereR[]	= "r.reply_member_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_poster'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_poster'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$whereS[]	= "s.status_member_id = " . $member['member_id'];
						$whereR[]	= "r.reply_member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}

				//-----------------------------------------
				// Now diverge, based on whether we'll get replies or not
				//-----------------------------------------
				
				if( $config['filters']['filter_replies'] )
				{
					if( $config['filters']['filter_locked'] )
					{
						$whereS[]	= "s.status_is_locked=0";
						$whereR[]	= "s.status_is_locked=0";
					}
					
					if( $config['filters']['filter_latest'] )
					{
						$whereS[]	= "s.status_is_latest=1";
						$whereR[]	= "s.status_is_latest=1";
					}
				
					switch( $config['sortby'] )
					{
						case 'date':
						default:
							$orderS	.=	"s.status_date ";
							$orderR	.=	"r.reply_date ";
						break;
						
						case 'replies':
							$orderS	.=	"s.status_replies ";
							$orderR	.=	"s.status_replies ";
						break;
					}
					
					$orderS	.= $config['sortorder'];
					$orderR	.= $config['sortorder'];
	
					//-----------------------------------------
					// We need to run two queries, get results,
					//	merge, and then cut
					//-----------------------------------------
					
					$members	= array();
					$newMembers	= array();
					
					$this->DB->build( array(
											'select'	=> 's.*',
											'from'		=> array( 'member_status_updates' => 's' ),
											'where'		=> implode( ' AND ', $whereS ),
											'order'		=> $orderS,
											'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
											'add_join'	=> array(
																array(
																	'from'		=> array( 'members' => 'm' ),
																	'where'		=> 'm.member_id=s.status_member_id',
																	'type'		=> 'left',
																	),
																)
									)		);
					$outer	= $this->DB->execute();
					
					while( $r = $this->DB->fetch($outer) )
					{
						$members[ $r['status_date'] ]		= $r;
					}
					
					$this->DB->build( array(
											'select'	=> 'r.*',
											'from'		=> array( 'member_status_replies' => 'r' ),
											'where'		=> implode( ' AND ', $whereR ),
											'order'		=> $orderR,
											'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
											'add_join'	=> array(
																array(
																	'from'		=> array( 'member_status_updates' => 's' ),
																	'where'		=> 'r.reply_status_id=s.status_id',
																	'type'		=> 'left',
																	),
																array(
																	'from'		=> array( 'members' => 'm' ),
																	'where'		=> 'm.member_id=r.reply_member_id',
																	'type'		=> 'left',
																	),
																)
									)		);
					$outer	= $this->DB->execute();
					
					while( $r = $this->DB->fetch($outer) )
					{
						$members[ $r['reply_date'] ]		= $r;
					}
					
					if( count($members) )
					{
						if( strtolower($config['sortorder']) == 'asc' )
						{
							ksort($members);
						}
						else
						{
							krsort($members);
						}

						$members	= array_slice( $members, $config['offset_a'], $config['offset_b'] );

						foreach( $members as $r )
						{
							//-----------------------------------------
							// Normalization
							//-----------------------------------------
	
							$r['member']	= IPSMember::load( $r['status_member_id'] ? $r['status_member_id'] : $r['reply_member_id'] );
							$r['member_id']	= $r['member']['member_id'];
							$r['status_id']	= $r['status_id'] ? $r['status_id'] : $r['reply_status_id'];
							$r['url']		= $this->registry->output->buildSEOUrl( "app=members&amp;module=profile&amp;section=status&amp;type=all&amp;status_id={$r['status_id']}", 'public', 'true', 'members_status_all' );
							$r['title']		= $r['member']['members_display_name'];
							$r['date']		= $r['status_date'] ? $r['status_date'] : $r['reply_date'];
							$r['content']	= $r['status_content'] ? $r['status_content'] : $r['reply_content'];
		
							$r['member']	= IPSMember::buildDisplayData( $r['member'] );
							
							$newMembers[]	= $r;
						}
					}
					
					$members	= $newMembers;
				}
				else
				{
					if( $config['filters']['filter_locked'] )
					{
						$whereS[]	= "s.status_is_locked=0";
					}
					
					if( $config['filters']['filter_latest'] )
					{
						$whereS[]	= "s.status_is_latest=1";
					}
				
					switch( $config['sortby'] )
					{
						case 'date':
						default:
							$order	.=	"s.status_date ";
						break;
						
						case 'replies':
							$order	.= "s.status_replies ";
						break;
					}
					
					$order	.= $config['sortorder'];
	
					//-----------------------------------------
					// Run the query and get the results
					//-----------------------------------------
					
					$members	= array();
					
					$this->DB->build( array(
											'select'	=> 's.*',
											'from'		=> array( 'member_status_updates' => 's' ),
											'where'		=> implode( ' AND ', $whereS ),
											'order'		=> $order,
											'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
											'add_join'	=> array(
																array(
																	'from'		=> array( 'members' => 'm' ),
																	'where'		=> 'm.member_id=s.status_member_id',
																	'type'		=> 'left',
																	),
																)
									)		);
					$outer	= $this->DB->execute();
					
					while( $r = $this->DB->fetch($outer) )
					{
						//-----------------------------------------
						// Normalization
						//-----------------------------------------

						$r['member']	= IPSMember::load( $r['status_member_id'] );
						$r['member_id']	= $r['member']['member_id'];
						$r['url']		= $this->registry->output->buildSEOUrl( "app=members&amp;module=profile&amp;section=status&amp;type=all&amp;status_id={$r['status_id']}", 'public', 'true', 'members_status_all' );
						$r['title']		= $r['member']['members_display_name'];
						$r['date']		= $r['status_date'];
						$r['content']	= $r['status_content'];
	
						$r['member']	= IPSMember::buildDisplayData( $r['member'] );
						
						$members[]		= $r;
					}
				}
			break;
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		
		// Using a gallery template, or custom?
		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}

		if( $config['hide_empty'] AND !count($members) )
		{
			return '';
		}
		
		ob_start();
		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $members );
		ob_end_clean();
		return $_return;
	}
}