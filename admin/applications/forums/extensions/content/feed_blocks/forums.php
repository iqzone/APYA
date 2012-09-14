<?php
/**
 * Forum feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10065 $ 
 * @since		1st March 2009
 */

class feed_forums implements feedBlockInterface
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
			case 'topics':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__topicurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__topicdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__topictitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__topiccontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'topics' ) as $_column )
				{
					if( $this->lang->words['col__topics_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__topics_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$_finalColumns['topic_title']		= array( "&#36;r['topic_title']", $this->lang->words['col__topics_title'] );
				$_finalColumns['lastpost']			= array( "&#36;r['lastpost']", $this->lang->words['col__topics_last_post'] );
				$_finalColumns['lastposter']		= array( "&#36;r['lastposter']", $this->lang->words['col__topics_last_poster_id'] );
				$_finalColumns['lastpostername']	= array( "&#36;r['lastpostername']", $this->lang->words['col__topics_last_poster_name'] );
				$_finalColumns['topic_posts']		= array( "&#36;r['topic_posts']", $this->lang->words['col__topics_posts'] );
				
				foreach( $this->DB->getFieldNames( 'posts' ) as $_column )
				{
					if( $this->lang->words['col__posts_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__posts_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'forums' ) as $_column )
				{
					if( $this->lang->words['col__forums_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__forums_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$_finalColumns['fid']	= array( "&#36;r['fid']", $this->lang->words['col__forums_id'] );
				$_finalColumns['fname']	= array( "&#36;r['fname']", $this->lang->words['col__forums_name'] );
				
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_topics']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__topics'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'forums':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__forumurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__forumdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__forumtitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__forumcontent'] ),
											);

				foreach( $this->DB->getFieldNames( 'forums' ) as $_column )
				{
					if( $this->lang->words['col__forums_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__forums_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_forums']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__forums'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'replies':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__replyurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__replydate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__replytitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__replycontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'posts' ) as $_column )
				{
					if( $this->lang->words['col__posts_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__posts_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'topics' ) as $_column )
				{
					if( $this->lang->words['col__topics_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__topics_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$_finalColumns['topic_title']		= array( "&#36;r['topic_title']", $this->lang->words['col__topics_title'] );
				$_finalColumns['lastpost']			= array( "&#36;r['lastpost']", $this->lang->words['col__topics_last_post'] );
				$_finalColumns['lastposter']		= array( "&#36;r['lastposter']", $this->lang->words['col__topics_last_poster_id'] );
				$_finalColumns['lastpostername']	= array( "&#36;r['lastpostername']", $this->lang->words['col__topics_last_poster_name'] );
				$_finalColumns['topic_posts']		= array( "&#36;r['topic_posts']", $this->lang->words['col__topics_posts'] );

				foreach( $this->DB->getFieldNames( 'forums' ) as $_column )
				{
					if( $this->lang->words['col__forums_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__forums_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$_finalColumns['fid']	= array( "&#36;r['fid']", $this->lang->words['col__forums_id'] );
				$_finalColumns['fname']	= array( "&#36;r['fname']", $this->lang->words['col__forums_name'] );
				
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_replies']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__replies'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
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
	 * @return	@e void		[Params are passed by reference and modified]
	 */
	protected function _addMemberColumns( &$_finalColumns, &$_noinfoColumns )
	{
		foreach( $this->DB->getFieldNames( 'sessions' ) as $_column )
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

		foreach( $this->DB->getFieldNames( 'members' ) as $_column )
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
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__pfields_content_' . $_column ] );
			}
			else if( preg_match( "/^field_(\d+)$/", $_column, $matches ) AND isset( $_fieldInfo[ $matches[1] ] ) )
			{
				unset($_finalColumns[ $_column ]);
				$_column					= str_replace( 'field_', 'user_field_', $_column );
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
			}
			else
			{
				$_column					= str_replace( 'field_', 'user_field_', $_column );
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		foreach( $this->DB->getFieldNames( 'profile_portal' ) as $_column )
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

		$_finalColumns['pp_main_photo']		= array( "&#36;r['pp_main_photo']", $this->lang->words['col__special_pp_main_photo'] );
		$_finalColumns['_has_photo']		= array( "&#36;r['_has_photo']", $this->lang->words['col__special__has_photo'] );
		$_finalColumns['pp_small_photo']	= array( "&#36;r['pp_small_photo']", $this->lang->words['col__special_pp_small_photo'] );
		$_finalColumns['pp_mini_photo']		= array( "&#36;r['pp_mini_photo']", $this->lang->words['col__special_pp_mini_photo'] );
		$_finalColumns['member_rank_img_i']	= array( "&#36;r['member_rank_img_i']", $this->lang->words['col__special_member_rank_img_i'] );
		$_finalColumns['member_rank_img']	= array( "&#36;r['member_rank_img']", $this->lang->words['col__special_member_rank_img'] );
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
					'key'			=> 'forums',
					'app'			=> 'forums',
					'name'			=> $this->lang->words['feed_name__forums'],
					'description'	=> $this->lang->words['feed_description__forums'],
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
							array( 'forums', $this->lang->words['ct_forums'] ),
							array( 'topics', $this->lang->words['ct_topics'] ),
							array( 'replies', $this->lang->words['ct_replies'] ),
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
		if( !in_array( $data['content_type'], array( 'forums', 'topics', 'replies' ) ) )
		{
			$data['content_type']	= 'topics';
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
		
		//-----------------------------------------
		// For all the content types, we allow to filter by forums
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );

		$aff = new $classToLoad( $this->registry );
		$aff->forumsInit();
		$dropdown = $aff->adForumsForumList(1);
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_forums__forums'],
							'description'	=> $this->lang->words['feed_forums__forums_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_forums[]', $dropdown, explode( ',', $session['config_data']['filters']['filter_forums'] ), 10 ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_forums__perms'],
							'description'	=> $this->lang->words['feed_forums__perms_desc'],
							'field'			=> $this->registry->output->formYesNo( 'filter_perms', $session['config_data']['filters']['filter_perms'] ),
							);

		switch( $session['config_data']['content_type'] )
		{
			case 'topics':
			default:
				$session['config_data']['filters']['filter_status']		= $session['config_data']['filters']['filter_status'] ? $session['config_data']['filters']['filter_status'] : 'either';
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'approved';
				$session['config_data']['filters']['filter_pinned']		= $session['config_data']['filters']['filter_pinned'] ? $session['config_data']['filters']['filter_pinned'] : 'either';
				$session['config_data']['filters']['filter_posts']		= $session['config_data']['filters']['filter_posts'] ? $session['config_data']['filters']['filter_posts'] : 0;
				$session['config_data']['filters']['filter_starter']	= $session['config_data']['filters']['filter_starter'] ? $session['config_data']['filters']['filter_starter'] : '';
				$session['config_data']['filters']['filter_poll']		= $session['config_data']['filters']['filter_poll'] ? $session['config_data']['filters']['filter_poll'] : 'either';
				$session['config_data']['filters']['filter_moved']		= $session['config_data']['filters']['filter_moved'];
				$session['config_data']['filters']['filter_attach']		= $session['config_data']['filters']['filter_attach'] ? $session['config_data']['filters']['filter_attach'] : 0;
				$session['config_data']['filters']['filter_rating']		= $session['config_data']['filters']['filter_rating'] ? $session['config_data']['filters']['filter_rating'] : 0;
				

				$status		= array( array( 'open', $this->lang->words['status__open'] ), array( 'closed', $this->lang->words['status__closed'] ), array( 'either', $this->lang->words['status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__status'],
									'description'	=> $this->lang->words['feed_forums__status_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_status', $status, $session['config_data']['filters']['filter_status'] ),
									);

				$visibility	= array( array( 'approved', $this->lang->words['approved__yes'] ), array( 'unapproved', $this->lang->words['approved__no'] ), array( 'either', $this->lang->words['approved__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__visibility'],
									'description'	=> $this->lang->words['feed_forums__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$pinned		= array( array( 'pinned', $this->lang->words['pinned__yes'] ), array( 'unpinned', $this->lang->words['pinned__no'] ), array( 'either', $this->lang->words['pinned__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__pinned'],
									'description'	=> $this->lang->words['feed_forums__pinned_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_pinned', $pinned, $session['config_data']['filters']['filter_pinned'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__posts'],
									'description'	=> $this->lang->words['feed_forums__posts_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_posts', $session['config_data']['filters']['filter_posts'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__starter'],
									'description'	=> $this->lang->words['feed_forums__starter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_starter', $session['config_data']['filters']['filter_starter'] ),
									);

				$poll		= array( array( 'poll', $this->lang->words['poll__yes'] ), array( 'nopoll', $this->lang->words['poll__no'] ), array( 'either', $this->lang->words['poll__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__poll'],
									'description'	=> $this->lang->words['feed_forums__poll_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_poll', $poll, $session['config_data']['filters']['filter_poll'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__move'],
									'description'	=> $this->lang->words['feed_forums__move_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_moved', $session['config_data']['filters']['filter_moved'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__attach'],
									'description'	=> $this->lang->words['feed_forums__attach_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_attach', $session['config_data']['filters']['filter_attach'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__rating'],
									'description'	=> $this->lang->words['feed_forums__rating_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_rating', $session['config_data']['filters']['filter_rating'] ),
									);
			break;
			
			case 'replies':
				$session['config_data']['filters']['filter_status']		= $session['config_data']['filters']['filter_status'] ? $session['config_data']['filters']['filter_status'] : 'either';
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'approved';
				$session['config_data']['filters']['filter_pinned']		= $session['config_data']['filters']['filter_pinned'] ? $session['config_data']['filters']['filter_pinned'] : 'either';
				$session['config_data']['filters']['filter_posts']		= $session['config_data']['filters']['filter_posts'] ? $session['config_data']['filters']['filter_posts'] : 0;
				$session['config_data']['filters']['filter_poster']		= $session['config_data']['filters']['filter_poster'] ? $session['config_data']['filters']['filter_poster'] : '';
				$session['config_data']['filters']['filter_poll']		= $session['config_data']['filters']['filter_poll'] ? $session['config_data']['filters']['filter_poll'] : 'either';
				$session['config_data']['filters']['filter_attach']		= $session['config_data']['filters']['filter_attach'] ? $session['config_data']['filters']['filter_attach'] : 0;
				$session['config_data']['filters']['filter_rating']		= $session['config_data']['filters']['filter_rating'] ? $session['config_data']['filters']['filter_rating'] : 0;
				

				$status		= array( array( 'open', $this->lang->words['status__open'] ), array( 'closed', $this->lang->words['status__closed'] ), array( 'either', $this->lang->words['status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__status'],
									'description'	=> $this->lang->words['feed_forums__status_desc_r'],
									'field'			=> $this->registry->output->formDropdown( 'filter_status', $status, $session['config_data']['filters']['filter_status'] ),
									);

				$visibility	= array( array( 'approved', $this->lang->words['approved__yes'] ), array( 'unapproved', $this->lang->words['approved__no'] ), array( 'either', $this->lang->words['approved__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__visibility_r'],
									'description'	=> $this->lang->words['feed_forums__visibility_desc_r'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$pinned		= array( array( 'pinned', $this->lang->words['pinned__yes'] ), array( 'unpinned', $this->lang->words['pinned__no'] ), array( 'either', $this->lang->words['pinned__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__pinned'],
									'description'	=> $this->lang->words['feed_forums__pinned_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_pinned', $pinned, $session['config_data']['filters']['filter_pinned'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__posts'],
									'description'	=> $this->lang->words['feed_forums__posts_desc_r'],
									'field'			=> $this->registry->output->formInput( 'filter_posts', $session['config_data']['filters']['filter_posts'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__poster'],
									'description'	=> $this->lang->words['feed_forums__poster_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_poster', $session['config_data']['filters']['filter_poster'] ),
									);

				$poll		= array( array( 'poll', $this->lang->words['poll__yes'] ), array( 'nopoll', $this->lang->words['poll__no'] ), array( 'either', $this->lang->words['poll__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__poll'],
									'description'	=> $this->lang->words['feed_forums__poll_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_poll', $poll, $session['config_data']['filters']['filter_poll'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__attach'],
									'description'	=> $this->lang->words['feed_forums__attach_desc_r'],
									'field'			=> $this->registry->output->formInput( 'filter_attach', $session['config_data']['filters']['filter_attach'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__rating'],
									'description'	=> $this->lang->words['feed_forums__rating_desc_r'],
									'field'			=> $this->registry->output->formInput( 'filter_rating', $session['config_data']['filters']['filter_rating'] ),
									);
			break;
			
			case 'forums':
				$session['config_data']['filters']['filter_root']	= isset($session['config_data']['filters']['filter_root']) ? $session['config_data']['filters']['filter_root'] : 1;

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_forums__root'],
									'description'	=> $this->lang->words['feed_forums__root_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_root', $session['config_data']['filters']['filter_root'] ),
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
		
		$filters['filter_forums']	= is_array($data['filter_forums']) ? implode( ',', $data['filter_forums'] ) : '';
		$filters['filter_perms']	= intval($data['filter_perms']);

		switch( $session['config_data']['content_type'] )
		{
			case 'topics':
			default:
				$filters['filter_status']		= $data['filter_status'] ? $data['filter_status'] : 'either';
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'approved';
				$filters['filter_pinned']		= $data['filter_pinned'] ? $data['filter_pinned'] : 'either';
				$filters['filter_posts']		= $data['filter_posts'] ? $data['filter_posts'] : 0;
				$filters['filter_starter']		= $data['filter_starter'] ? $data['filter_starter'] : '';
				$filters['filter_poll']			= $data['filter_poll'] ? $data['filter_poll'] : 'either';
				$filters['filter_moved']		= intval($data['filter_moved']);
				$filters['filter_attach']		= $data['filter_attach'] ? $data['filter_attach'] : 0;
				$filters['filter_rating']		= $data['filter_rating'] ? $data['filter_rating'] : 0;
			break;
			
			case 'replies':
				$filters['filter_status']		= $data['filter_status'] ? $data['filter_status'] : 'either';
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'approved';
				$filters['filter_pinned']		= $data['filter_pinned'] ? $data['filter_pinned'] : 'either';
				$filters['filter_posts']		= $data['filter_posts'] ? $data['filter_posts'] : 0;
				$filters['filter_poster']		= $data['filter_poster'] ? $data['filter_poster'] : '';
				$filters['filter_poll']			= $data['filter_poll'] ? $data['filter_poll'] : 'either';
				$filters['filter_attach']		= $data['filter_attach'] ? $data['filter_attach'] : 0;
				$filters['filter_rating']		= $data['filter_rating'] ? $data['filter_rating'] : 0;
			break;
			
			case 'forums':
				$filters['filter_root']			= isset($data['filter_root']) ? $data['filter_root'] : 1;
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

		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'topics':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'last_post';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_topic__title'] ), 
								array( 'posts', $this->lang->words['sort_topic__posts'] ), 
								array( 'start_date', $this->lang->words['sort_topic__startdate'] ),
								array( 'last_post', $this->lang->words['sort_topic__lastdate'] ),
								array( 'views', $this->lang->words['sort_topic__views'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'replies':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'post_date';

				$sortby	= array( 
								array( 'post_date', $this->lang->words['sort_topic__postdate'] ), 
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'forums':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_topic__name'] ), 
								array( 'topics', $this->lang->words['sort_topic__topics'] ), 
								array( 'posts', $this->lang->words['sort_topic__posts'] ),
								array( 'last_post', $this->lang->words['sort_topic__lastdate'] ),
								array( 'position', $this->lang->words['sort_topic__position'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
		}
		
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
		$limits		= array();
		
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		switch( $session['config_data']['content_type'] )
		{
			case 'topics':
			default:
				$sortby					= array( 'title', 'posts', 'start_date', 'last_post', 'views', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'last_post';
			break;
			
			case 'replies':
				$sortby					= array( 'post_date' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'post_date';
			break;
			
			case 'forums':
				$sortby					= array( 'name', 'topics', 'posts', 'last_post', 'position', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'position';
			break;
		}
		
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
		$where	= array();

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		$forumClass	= $this->registry->getClass('class_forums');
		$forumClass->forumsInit();
		
		$_forumsToCheck	= array();
		
		if( $config['filters']['filter_forums'] )
		{
			if( $config['filters']['filter_perms'] )
			{
				$_forumsToCheck	= $forumClass->fetchSearchableForumIds();
				$_selected		= explode( ',', $config['filters']['filter_forums'] );
				$_useForum		= array();
				
				foreach( $_selected as $_check )
				{
					if( in_array( $_check, $_forumsToCheck ) )
					{
						$_useForum[]	= $_check;
					}
				}

				if( !count($_useForum) )
				{
					return '';
				}
					
				$config['filters']['filter_forums']	= implode( ',', $_useForum );
			}
			
			if( $config['content'] == 'forums' )
			{
				$where[]	= "f.id IN(" . $config['filters']['filter_forums'] . ")";
			}
			else if( $config['content'] == 'topics' )
			{
				$where[]	= "forum_id IN(" . $config['filters']['filter_forums'] . ")";
			}
			else
			{
				$where[]	= "t.forum_id IN(" . $config['filters']['filter_forums'] . ")";
			}
		}
		else if( $config['filters']['filter_perms'] )
		{
			$_forumsToCheck	= $forumClass->fetchSearchableForumIds( null, array(), true );
			
			if( count($_forumsToCheck) )
			{
				if( $config['content'] == 'forums' )
				{
					$where[]	= "f.id IN(" . implode( ',', $_forumsToCheck ) . ")";
				}
				else if( $config['content'] == 'topics' )
				{
					$where[]	= "forum_id IN(" . implode( ',', $_forumsToCheck ) . ")";
				}
				else
				{
					$where[]	= "t.forum_id IN(" . implode( ',', $_forumsToCheck ) . ")";
				}
			}
			else
			{
				return '';
			}
		}

		switch( $config['content'] )
		{
			case 'topics':

				if( $config['filters']['filter_status'] != 'either' )
				{
					$where[]	= "state='" . $config['filters']['filter_status'] . "'";
				}
				
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= $config['filters']['filter_visibility'] == 'approved' ? $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ) : $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'hidden' ), '' );
				}
				else
				{
					$where[]	= $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible', 'hidden' ), '' );
				}

				if( $config['filters']['filter_pinned'] != 'either' )
				{
					$where[]	= "pinned=" . ( $config['filters']['filter_pinned'] == 'pinned' ? 1 : 0 );
				}

				if( $config['filters']['filter_posts'] > 0 )
				{
					$where[]	= "posts >= " . $config['filters']['filter_posts'];
				}
				
				if( $config['filters']['filter_starter'] == 'myself' )
				{
					$where[]	= "starter_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_starter'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder	= new $classToLoad( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "starter_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_starter'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_starter'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$where[]	= "starter_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
				
				if( $config['filters']['filter_poll'] != 'either' )
				{
					$where[]	= "poll_state=" . ( $config['filters']['filter_poll'] == 'poll' ? 1 : 0 );
				}

				if( $config['filters']['filter_moved'] )
				{
					$where[]	= "(moved_to=0 OR moved_to='' OR moved_to IS NULL)";
				}
				
				if( $config['filters']['filter_attach'] )
				{
					$where[]	= "topic_hasattach > 0";
				}
				
				if( $config['filters']['filter_rating'] )
				{
					$where[]	= "(topic_rating_total/topic_rating_hits) >= " . $config['filters']['filter_rating'];
				}
			break;
			
			case 'replies':
				if( $config['filters']['filter_status'] != 'either' )
				{
					$where[]	= "t.state='" . $config['filters']['filter_status'] . "'";
				}
				
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					//$where[]	= $config['filters']['filter_visibility'] == 'approved' ? $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), 't.' ) : $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'hidden' ), 't.' );
					$where[]	= $config['filters']['filter_visibility'] == 'approved' ? $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), 'p.' ) : $this->registry->class_forums->fetchPostHiddenQuery( array( 'hidden' ), 'p.' );
				}
				else
				{
					$where[]	= $this->registry->class_forums->fetchTopicHiddenQuery( array( 'visible', 'hidden' ), 't.' );
					$where[]	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible', 'hidden' ), 'p.' );
				}

				if( $config['filters']['filter_pinned'] != 'either' )
				{
					$where[]	= "t.pinned=" . ( $config['filters']['filter_pinned'] == 'pinned' ? 1 : 0 );
				}

				if( $config['filters']['filter_posts'] > 0 )
				{
					$where[]	= "t.posts >= " . $config['filters']['filter_posts'];
				}

				if( $config['filters']['filter_attach'] )
				{
					$where[]	= "t.topic_hasattach > 0";
				}
				
				if( $config['filters']['filter_rating'] )
				{
					$where[]	= "(t.topic_rating_total/t.topic_rating_hits) >= " . $config['filters']['filter_rating'];
				}
				
				if( $config['filters']['filter_poll'] != 'either' )
				{
					$where[]	= "t.poll_state=" . ( $config['filters']['filter_poll'] == 'poll' ? 1 : 0 );
				}

				if( $config['filters']['filter_poster'] == 'myself' )
				{
					$where[]	= "p.author_id = " . $this->memberData['member_id'];
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
						$where[]	= "p.author_id IN( " . implode( ',', $friends ) . ")";
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
						$where[]	= "p.author_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
				
				$where[]	= "p.pid <> t.topic_firstpost";
			break;
			
			case 'forums':
				if( $config['filters']['filter_root'] )
				{
					$where[]	= "f.parent_id < 1";
				}
			break;
		}
		
		$order	= '';

		switch( $config['content'] )
		{
			case 'topics':
				switch( $config['sortby'] )
				{
					case 'title':
						$order	.=	"title ";
					break;
		
					case 'posts':
						$order	.=	"posts ";
					break;
					
					case 'start_date':
						$order	.=	"start_date ";
					break;

					default:
					case 'last_post':
						$order	.=	"last_post ";
					break;
		
					case 'views':
						$order	.=	"views ";
					break;

					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
			break;
			
			case 'replies':
				switch( $config['sortby'] )
				{
					default:
					case 'post_date':
						$order	.=	"p.post_date ";
					break;
				}
			break;

			case 'forums':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"f.name ";
					break;
		
					case 'topics':
						$order	.=	"f.topics ";
					break;
					
					case 'posts':
						$order	.=	"f.posts ";
					break;
		
					case 'last_post':
						$order	.=	"f.last_post ";
					break;
		
					default:
					case 'position':
						$order	.=	"f.position ";
					break;

					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
			break;
		}
		
		$order	.= $config['sortorder'];
		
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$content			= array();
		$attach_pids		= array();
		$parseAttachments	= false;
		$memberIds			= array();

		switch( $config['content'] )
		{
			case 'topics':
				//-----------------------------------------
				// Split into two queries, get tids first
				//-----------------------------------------
				
				$tids		= array();
				$content	= array();
				$_order		= 0;
				
				$this->DB->build( array(
										'select'	=> 'tid',
										'from'		=> 'topics',
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] )
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$tids[ $_order ]	= $r['tid'];
					$_order++;
				}
				
				if( count($tids) )
				{	
					$this->DB->build( array(
											'select'	=> 't.*, t.title as topic_title, t.last_post as lastpost, t.last_poster_id as lastposter, t.last_poster_name as lastpostername, t.posts as topic_posts',
											'from'		=> array( 'topics' => 't' ),
											'where'		=> 't.tid IN(' . implode( ',', $tids ) . ')',
											'add_join'	=> array(
																array(
																	'select'	=> 'p.*',
																	'from'		=> array( 'posts' => 'p' ),
																	'where'		=> 'p.pid=t.topic_firstpost',
																	'type'		=> 'left',
																	),
																array(
																	'select'	=> 'poster.member_group_id as poster_group_id, poster.member_id as poster_id, poster.mgroup_others as poster_group_others',
																	'from'		=> array( 'members' => 'poster' ),
																	'where'		=> 'poster.member_id=p.author_id',
																	'type'		=> 'left',
																	),
																array(
																	'select'	=> 'f.*, f.name as fname, f.id as fid',
																	'from'		=> array( 'forums' => 'f' ),
																	'where'		=> 'f.id=t.forum_id',
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
																		
						$r['title']		= $r['topic_title'];
						$r['member_id']	= $r['poster_id'];
						$r['forum_id']	= $r['fid'];
						$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showtopic=' . $r['tid'], 'none', $r['title_seo'], 'showtopic' );
						$r['date']		= ( $config['sortby'] == 'start_date' ) ? $r['start_date'] : $r['lastpost'];
						$r['content']	= $r['post'];
	
						IPSText::getTextClass( 'bbcode' )->parse_smilies			= $r['use_emo'];
						IPSText::getTextClass( 'bbcode' )->parse_html				= ( $r['use_html'] and $this->caches['group_cache'][ $r['poster_group_id'] ]['g_dohtml'] and $r['post_htmlstate'] ) ? 1 : 0;
						IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $r['post_htmlstate'] == 2 ? 1 : 0;
						IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $r['use_ibc'];
						IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
						IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['poster_group_id'];
						IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['poster_group_others'];
			
						$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
						$r['content']	.= "\n<!--IBF.ATTACHMENT_{$r['pid']}-->\n";

						$_orderLookup	= array_keys( $tids, $r['tid'] );
						
						$content[ $_orderLookup[0] ]		= $r;
						
						if( $r['topic_hasattach'] )
						{
							$parseAttachments	= true;
						}
						
						$attach_pids[ intval($r['pid']) ]			= intval($r['pid']);
						$memberIds[ intval($r['lastposter']) ]	= intval($r['lastposter']);
					}
					
					ksort($content);
					
					$newContent	= array();
					
					foreach( $content as $_content )
					{
						$newContent[ $_content['pid'] ]	= $_content;
					}
					
					$content	= $newContent;
				}
			break;
			
			case 'replies':
				$this->DB->build( array(
										'select'	=> 'p.*',
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 't.*, t.title as topic_title, t.last_post as lastpost, t.last_poster_id as lastposter, t.last_poster_name as lastpostername, t.posts as topic_posts',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'f.*, f.name as fname, f.id as fid',
																'from'		=> array( 'forums' => 'f' ),
																'where'		=> 'f.id=t.forum_id',
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
					
					$r['title']		= $r['topic_title'];
					$r['member_id']	= $r['author_id'];
					$r['forum_id']	= $r['fid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showtopic=' . $r['tid'] . '&amp;view=findpost&amp;p=' . $r['pid'], 'none', $r['title_seo'], 'showtopic' );
					$r['date']		= $r['post_date'];
					$r['content']	= $r['post'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= $r['use_emo'];
					IPSText::getTextClass( 'bbcode' )->parse_html				= ( $r['use_html'] and $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'] and $r['post_htmlstate'] ) ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $r['post_htmlstate'] == 2 ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $r['use_ibc'];
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					$r['content']	.= "\n<!--IBF.ATTACHMENT_{$r['pid']}-->\n";

					$content[ $r['pid'] ]		= $r;
					
					if( $r['topic_hasattach'] )
					{
						$parseAttachments	= true;
					}
					
					$attach_pids[ intval($r['pid']) ]		= intval($r['pid']);
					$memberIds[ intval($r['author_id']) ]	= intval($r['author_id']);
				}
			break;
			
			case 'forums':
				$this->DB->build( array(
										'select'	=> 'f.*, f.name as fname, f.id as fid, f.posts as fposts',
										'from'		=> 'forums f',
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					$r['forum_id']	= $r['fid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showforum=' . $r['forum_id'], 'none', $r['name_seo'], 'showforum' );
					$r['title']		= $r['fname'];
					$r['date']		= $r['last_post'];
					$r['content']	= $r['description'];

					$r['id']		= $r['forum_id'];
					
					$content[]		= $r;
					
					$memberIds[ intval($r['last_poster_id']) ]	= intval($r['last_poster_id']);
				}
			break;
		}

		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();

		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}		

		if( $config['hide_empty'] AND !count($content) )
		{
			return '';
		}

		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$memberData	= IPSMember::load( $memberIds );
		$newContent	= array();

		foreach( $content as $_content )
		{
			$_origTitle	= $_content['title'];

			switch( $config['content'] )
			{
				case 'topics':
					$memberRecord	= IPSMember::buildDisplayData( $memberData[ $_content['lastposter'] ] );
				break;
				
				case 'replies':
					$memberRecord	= IPSMember::buildDisplayData( $memberData[ $_content['author_id'] ] );
				break;
				
				case 'forums':
					$memberRecord	= IPSMember::buildDisplayData( $memberData[ $_content['last_poster_id'] ] );
				break;
			}
			
			$memberRecord['title']	= $_origTitle;
			
			$newContent[]	= array_merge( $_content, $memberRecord );
		}
		
		$content	= $newContent;

		//-----------------------------------------
		// Parse attachments if we need to
		//-----------------------------------------
		
		if( $parseAttachments )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$_classAttach	=  new $classToLoad( $this->registry );
			
			$_classAttach->attach_post_key	=  '';

			ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_topic' ), 'forums' );
			
			$_classAttach->attach_post_key	=  '';
			$_classAttach->type				= 'post';
			$_classAttach->init();

			//-----------------------------------------
			// Need to separate for proper parsing...
			//-----------------------------------------
			
			$htmlToParse	= array();
			
			foreach( $content as $k => $v )
			{
				$htmlToParse[ $k ]	= $v['content'];
			}
			
			//-----------------------------------------
			// Parse
			//-----------------------------------------
			
			$_return	= $_classAttach->renderAttachments( $htmlToParse, $attach_pids );
			
			//-----------------------------------------
			// Remerge...
			//-----------------------------------------
			
			foreach( $_return as $k => $v )
			{
				if( $content[ $k ] )
				{
					$content[ $k ]['content']	= $v['html'] . $v['attachmentHtml'];
				}
			}
		}
		
		ob_start();
		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $content );
		ob_end_clean();
		return $_return;
	}
}