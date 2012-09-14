<?php
/**
 * @file		list.php 	IP.Blog index listing
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		24th June 2008
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_blog_display_list
 * @brief		IP.Blog index listing
 */
class public_blog_display_list extends ipsCommand
{
	/**
	 * DB query start value (Default: 0)
	 *
	 * @var		$first
	 */
	protected $first		= 0;

	/**
	 * DB query limit value (Default: 20)
	 *
	 * @var		$max_results
	 */
	protected $max_results	= 20;
	
	/**
	 * DB query sort key (Default: blog_last_edate)
	 *
	 * @var		$sort_key
	 */
	protected $sort_key		= 'blog_last_edate';
	
	/**
	 * DB sort order (Default: desc)
	 *
	 * @var		$sort_order
	 */
	protected $sort_order	= 'desc';
	
	/**
	 * DB filtering (Default: ALL)
	 *
	 * @var		$filter
	 */
	protected $filter		= 'all';
	
	/**
	 * Name filtering type (Default: begins)
	 *
	 * @var		$name_box
	 */
	protected $name_box		= 'begins';
	
	/**
	 * View type string
	 *
	 * @var		$_view_type
	 */
	protected $_view_type	= '';
	
	/**
	 * Blog type view from cookie
	 *
	 * @var		$_stickCookie
	 */
	protected $_stickCookie	= '';
	
	/**
	 * Tags object
	 * 
	 * @var		$tagsClass
	 */
	public $tagsClass;
	
	/**
	 * Ids of entries with attachments
	 * 
	 * @var		$entryWithAttachments
	 */
	public $entryWithAttachments = array();
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load parsing lib */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry, null ) );
		
		/* Check cookie */
		$this->_stickCookie = IPSCookie::get('blog_view_type');
		
		/* View Type */
		if( !empty( $this->request['type'] ) )
		{
			$this->_view_type = ( in_array( $this->request['type'], array( 'all', 'dash' ) ) ) ? $this->request['type'] : 'dash';
			
			if ( isset( $this->request['stick'] ) )
			{
				$this->_stickCookie = $this->_view_type;
				
				if ( $this->request['stick'] )
				{
					IPSCookie::set( 'blog_view_type', $this->_view_type );
				}
				else
				{
					IPSCookie::set( 'blog_view_type', '', 0 );
				}
			}
		}
		else
		{
			if ( $this->_stickCookie )
			{
				$this->_view_type = $this->_stickCookie;
			}
		}
		
		/* Ensure we have a value */
		if ( ! $this->_view_type )
		{
			$this->_view_type = 'dash';
		}
		
		/* Load tags class */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$this->tagsClass = classes_tags_bootstrap::run( 'blog', 'entries' );
		
		/* Whadda we doin'? */
		if ( $this->_view_type == 'all' )
		{
			$output = $this->_showAll();
		}
		else
		{
			$output = $this->_showDash();
			
			/* Parse attachments if we got em */
			if ( $this->settings['blog_list_full'] && count($this->entryWithAttachments) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------
	
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach       = new $classToLoad( $this->registry );
				$this->class_attach->type = 'blogentry';
				$this->class_attach->init();
	
				$_output = $this->class_attach->renderAttachments( $output, $this->entryWithAttachments, 'blog_show' );
				$output = $_output[0]['html'];
	
				foreach( $_output as $entry_id => $_attachData )
				{
					if( $entry_id && $_attachData['attachmentHtml'] )
					{
						$output = str_replace( '<!--IBF.ATTACHMENT_' . $entry_id . '-->', $_attachData['attachmentHtml'], $output );
					}
				}
			}
		}
		
		/* Set defaults */
		$this->registry->output->addNavigation( $this->lang->words['blog_list'], 'app=blog', 'blogs', 'blogs' );
		
		if ( $this->request['type'] == 'all' )
		{
			$this->registry->output->addNavigation( $this->lang->words['blog_list_all'], 'app=blog&amp;type=all', 'false', 'app=blog' );
		}
		
		$this->registry->output->setTitle( $this->lang->words['blog_list'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Shows all blogs
	 *
	 * @return	@e string	Parsed template
	 */
	protected function _showAll()
	{
		//------------------------------------------
		// Test for input
		//------------------------------------------

		$all_sorting_options = $this->_fetchSortingOptions();

		/* Init vars */
		$blogs		= array();
		$featured	= array();
		$query		= array();
		$url		= array();

		//---------------------------------------------------
		// Blog types filter
		//---------------------------------------------------

		if ( strtolower($this->filter) != 'all' )
		{
			$query[] = "b.blog_type='" . $this->filter . "' ";
		}

		//---------------------------------------------------
		// User entered Blog filters...
		//---------------------------------------------------

		$mapit = array( 'name'		=> 'm.members_display_name',
						'blogname'	=> 'b.blog_name',
						);

		foreach( $mapit as $in => $tbl )
		{
			if ( $this->request[ $in ] )
			{
				$url[]	= $in . '=' . $this->request[ $in ];

				//------------------------------
				// Name...
				//------------------------------

				if ( $in == 'name' and $this->request[ $in ] != "" )
				{
					if ( $this->name_box == 'begins' )
					{
						$query[] = "m.members_display_name LIKE '" . $this->request[ $in ] . "%'";
					}
					else
					{
						$query[] = "m.members_display_name LIKE '%" . $this->request[ $in ] . "%'";
					}
				}
				else if ( $in == 'blogname' and $this->request[ $in ] != "" )
				{
					$query[] = "b.blog_name LIKE '%" . $this->request[ $in ] . "%'";
				}
			}
		}

		if ( !$this->member->getProperty('g_is_supmod') and !$this->memberData['_blogmod']['moderate_can_disable'])
		{
			$query[] = "b.blog_disabled = 0";
		}

		if ( ! $this->member->getProperty('member_id') )
		{
			$query[] = "b.blog_allowguests = 1";
		}
		
		/* Now, did we want to search by member_id? */
		if ( !empty( $this->request['member_id'] ) )
		{
			$member = IPSMember::load( intval( $this->request['member_id'] ), 'core' );
			
			if ( $member['member_id'] )
			{
				/* Fetch their owned blogs */
				$blogs = $this->registry->blogFunctions->fetchMyBlogs( $member );
				
				if ( count( $blogs ) )
				{
					$query[] = "b.blog_id IN ( " . implode( ',',  array_keys( $blogs ) ) . ")";
					$url[]   = 'member_id=' . $this->request['member_id'];
				}
			}
		}
		
		/* Ensure indexes are used */
		//$query[] = "b.blog_pinned IN(0,1) AND b.blog_last_edate > -1";
		if ( $this->memberData['g_is_supmod'] )
		{
			$query[] = '1=1';
		}
		else
		{
			$query[] = "( ( b.blog_owner_only=1 AND b.member_id="  . intval( $this->memberData['member_id'] ) . " ) OR b.blog_owner_only=0 ) AND ( b.blog_authorized_users LIKE '%,"  . intval( $this->memberData['member_id'] ) .  ",%' OR b.blog_authorized_users IS NULL )";
		}
		
		/* Finish */
		if( count( $query ) )
		{
			$query_string = implode( " AND ", $query );
		}
			
		/* Featured entry */
		if ( count( $this->caches['blog_stats']['featured'] ) AND ! $this->request['member_id'] )
		{
			$featured = $this->_fetchFeatured();
		}

		//---------------------------------------------
		// Count...
		//---------------------------------------------

		$max = $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) AS total_blogs',
												'from'		=> array('blog_blogs' => 'b'),
												'where'		=> "{$query_string}",
												'add_join'	=> array( array( 'from'	 => array( 'members' => 'm' ),
																			 'where' => "b.member_id=m.member_id",
																			 'type'	 => 'left' ) ) ) );
												
		$links = $this->registry->output->generatePagination( array( 'totalItems'		 => $max['total_blogs'],
																	 'itemsPerPage'		 => $this->max_results,
																	 'currentStartValue' => $this->first,
																	 'baseUrl'			 => "app=blog&amp;name_box={$this->name_box}&amp;sort_key={$this->sort_key}&amp;sort_order={$this->sort_order}&amp;filter={$this->filter}&amp;type=all&amp;max_results={$this->max_results}&amp;".implode( '&amp;', $url ),
																  ) );
		//---------------------------------------------------
		// Get the blogs now...
		//---------------------------------------------------

		if ( $max['total_blogs'] > 0)
		{		
			$members = array();
			$mids    = array();
			
			if ( $this->sort_key == 'blog_rating' )
			{
				$this->sort_key = 'blog_rating ' . $this->sort_order . ', blog_rating_count';
				//$select         = ', CASE when b.blog_rating_count > ' . intval( $this->settings[ 'blog_rating_treshhold' ] ) . ' THEN (b.blog_rating_total/b.blog_rating_count) else 0 end as blog_rating';
				$select         = ', CASE when b.blog_rating_count > 0 THEN (b.blog_rating_total/b.blog_rating_count) else 0 end as blog_rating';
			}
			
			if ( count( $featured ) )
			{
				$query_string .= " AND b.blog_id NOT IN ( " . implode( ",", array_keys( $featured ) ) . ")";
			}
		
			$this->DB->build( array( 'select'	=> 'b.*, b.blog_id as blog_id_id' . $select,
									 'from'		=> array( 'blog_blogs' => 'b' ),
									 'where'	=> $query_string,
									 'order'    => 'b.blog_pinned DESC,' . $this->sort_key . ' ' . $this->sort_order,
									 'limit'    => array( $this->first, $this->max_results ),
									 'add_join'	=> array(array( 'select'	=> 'bl.*',
																 'from'	    => array( 'blog_lastinfo' =>'bl' ),
																 'where'    => 'b.blog_id=bl.blog_id',
																 'type'	    => 'left' ), 
									 					 array( 'select'	=> 'e.*',
																 'from'	    => array( 'blog_entries' =>'e' ),
																 'where'    => 'e.entry_id=bl.blog_last_entry',
																 'type'	    => 'left' ),
														 array(  'select'	=> 'm.members_display_name,m.members_seo_name',
																 'from'	    => array( 'members' => 'm' ),
																 'where'    => 'm.member_id=b.member_id',
																 'type'	    => 'left' ),
														 array(  'select'   => 'pp.*',
																 'from'	    => array( 'profile_portal' => 'pp' ),
																 'where'    => 'pp.pp_member_id=m.member_id',
																 'type'	    => 'left' ) ) ) );
			$o = $this->DB->execute();
			
			while( $blog = $this->DB->fetch( $o ) )
			{
				/* MySQL thing */
				$blog['blog_id'] = ( $blog['blog_id_id'] ) ? $blog['blog_id_id'] : $blog['blog_id'];
				
				/* Skin External blog with no url */
				if( $blog['blog_type'] == 'external' && ! $blog['blog_exturl'] )
				{
					continue;
				}
			
				/* Format Blog Data */
				$blog['last_read']	 = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $blog['blog_id'] ) );
				$blog['_lastAuthor'] = array();
				$blog['_diffLA']     = 0;
				$blog				 = $this->registry->getClass('blogFunctions')->buildBlogData( $blog );
				$blog				 = IPSMember::buildDisplayData( $blog, array( 'reputation' => 0, 'warn' => 0 ) );
				$blog                = $this->registry->getClass('blogParsing')->parseEntry( $blog, 1, array( 'entryParse' => 1, 'noPositionInc' => 1 ), $blog );
								
				/* Last author is not the same as blog owner */
				if ( $blog['entry_author_id'] AND $blog['entry_author_id'] != $blog['member_id'] )
				{
					$mids[]          = $blog['entry_author_id'];
					$blog['_diffLA'] = 1;
				}
				
				/* Blog moderation */
				$blog['bidon']		= 0;

				if ( $this->member->getProperty('g_is_supmod') or $this->memberData['_blogmod']['moderate_can_disable'] or $this->memberData['_blogmod']['moderate_can_pin'] )
				{
					if( $this->request['selectedbids'] )
					{
						if( strstr( ',' . $this->request['selectedbids'] . ',', ',' . $blog['blog_id'] . ',' ) )
						{
							$blog['bidon']	= 1;
						}
					}
				}
				
				/* List Type */
				if( $blog['blog_pinned'] )
				{
					$type = 'pinned';
				}
				else
				{
					$type = 'normal';
				}
 				
				/* Add to array */
				$blogs[ $type ][] = $blog;
			}
			
			/* More members to load? */
			if ( count( $mids ) )
			{
				$members = IPSMember::load( $mids, 'all' );
				
				if ( count( $members ) )
				{
					foreach( array( 'pinned', 'normal' ) as $type )
					{
						if ( is_array( $blogs[ $type ] ) )
						{
							foreach( $blogs[ $type ] as $idx => $data )
							{
								if ( $data['_diffLA'] )
								{
									$blogs[ $type ][ $idx ]['_lastAuthor'] = IPSMember::buildDisplayData( $members[ $data['entry_author_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
								}
							}
						}
					}
				}
			}
		}
				
		/* Fetch extra data */
		$extraData = $this->_sideBarData();
		
		/* Push out */
		return $this->registry->getClass('output')->getTemplate('blog_list')->blogIndexPage( $links, $featured, $blogs, $extraData, 'all', $all_sorting_options );
	}
	
	/**
	 * Show blogs dashboard
	 *
	 * @return	@e string	Parsed template
	 */
	protected function _showDash()
	{
		/* INIT */
		$blogs				 = array();
		$featured			 = array();
		$seenBlogs           = array( 0 => 0 );
		$all_sorting_options = $this->_fetchSortingOptions();
		$whereExtra          = '';
		
		if ( ! $this->memberData['member_id'] )
		{
			$whereExtra = " AND b.blog_allowguests = 1";
		}
		
		/* Featured entry */
		if ( count( $this->caches['blog_stats']['featured'] ) )
		{
			$featured = $this->_fetchFeatured();
		}
		
		//---------------------------------------------------
		// Get the blogs now...
		//---------------------------------------------------
		
		if ( $this->caches['blog_stats']['stats_num_blogs'] )
		{
			/* Pinned */
			if ( is_array( $this->caches['blog_stats']['pinned'] ) AND count( $this->caches['blog_stats']['pinned'] ) )
			{
				$this->DB->build( array( 'select'	=> 'b.*, b.blog_id as blog_id_id',
										 'from'		=> array( 'blog_blogs' => 'b' ),
										 'where'	=> 'b.blog_id IN (' . implode( ',', array_values( $this->caches['blog_stats']['pinned'] ) ) . ') AND b.blog_disabled=0 AND blog_type=\'local\' AND b.blog_view_level=\'public\'' . $whereExtra,
										 'order'    => 'b.blog_last_edate DESC',
										 'limit'    => array( 0, 20 ),
										 'add_join'	=> array(array( 'select'	=> 'bl.*',
																	 'from'	    => array( 'blog_lastinfo' =>'bl' ),
																	 'where'    => 'b.blog_id=bl.blog_id',
																	 'type'	    => 'left' ), 
										 					 array( 'select'	=> 'e.*',
																	 'from'	    => array( 'blog_entries' =>'e' ),
																	 'where'    => 'e.entry_id=bl.blog_last_entry',
																	 'type'	    => 'left' ),
															 array(  'select'	=> 'm.members_display_name, m.member_group_id, m.members_seo_name',
																	 'from'	    => array( 'members' => 'm' ),
																	 'where'    => 'm.member_id=e.entry_author_id',
																	 'type'	    => 'left' ),
															 array(  'select'	=> 'bm.member_id as blog_member_id, bm.members_display_name as blog_members_display_name, bm.member_group_id as blog_member_group_id, bm.members_seo_name as blog_members_seo_name',
																	 'from'	    => array( 'members' => 'bm' ),
																	 'where'    => 'bm.member_id=b.member_id',
																	 'type'	    => 'left' ),
															 array(  'select'   => 'pp.*',
																	 'from'	    => array( 'profile_portal' => 'pp' ),
																	 'where'    => 'pp.pp_member_id=m.member_id',
																	 'type'	    => 'left' ) ) ) );
				$o = $this->DB->execute();
				
				while( $row = $this->DB->fetch( $o ) )
				{
					/* MySQL thing */
					$row['blog_id'] = $row['blog_id_id'] ? $row['blog_id_id'] : $row['blog_id'];
					
					/* Tags */
					if ( $row['entry_id'] )
					{
						$row['tags'] = $this->tagsClass->getTagsByMetaId( $row['entry_id'] );
					}
					
					$row			= IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
					$row			= $this->registry->getClass('blogParsing')->parseEntry( $row, 1, array( 'entryParse' => $this->settings['blog_list_full'] ? 0 : 1, 'noPositionInc' => 1 ), $row );
					if ( $row['entry_has_attach'] )
					{
						$this->entryWithAttachments[ $row['entry_id'] ] = $row['entry_id'];
					}
					
					$blogs['pinned'][ $row['blog_id'] ]	= $row;
				}
			}

			/* Normal */
			if ( count( $this->caches['blog_stats']['fp_entries'] ) )
			{
				$this->DB->build( array( 'select'	=> 'e.*',
										 'from'		=> array( 'blog_entries' => 'e' ),
										 'where'	=> 'e.entry_id IN(' . implode( ',', array_keys( $this->caches['blog_stats']['fp_entries'] ) ) . ')' . $whereExtra,
										 'order'    => 'e.entry_date DESC',
										 'limit'    => array( 0, 20 ),
										 'add_join'	=> array(array(  'select'   => 'b.*, b.blog_id as blog_id_id',
																	 'from'     => array( 'blog_blogs' => 'b' ),
																	 'where'    => 'b.blog_id=e.blog_id',
																	 'type'     => 'left' ),
										 					 array( 'select'	=> 'bl.*',
																	 'from'	    => array( 'blog_lastinfo' =>'bl' ),
																	 'where'    => 'b.blog_id=bl.blog_id',
																	 'type'	    => 'left' ), 
															 array(  'select'	=> 'm.*',
																	 'from'	    => array( 'members' => 'm' ),
																	 'where'    => 'm.member_id=e.entry_author_id',
																	 'type'	    => 'left' ),
															 array(  'select'   => 'pp.*',
																	 'from'	    => array( 'profile_portal' => 'pp' ),
																	 'where'    => 'pp.pp_member_id=m.member_id',
																	 'type'	    => 'left' ) ) ) );
																	 
				$o = $this->DB->execute();
				
				while( $row = $this->DB->fetch( $o ) )
				{
					/* Just be sure */
					if ( ! $row['blog_name'] OR ! $row['entry_name'] OR $row['entry_status'] != 'published' )
					{
						continue;
					}
					
					/* Tags */
					if ( $row['entry_id'] )
					{
						$row['tags'] = $this->tagsClass->getTagsByMetaId( $row['entry_id'] );
					}
					
					/* MySQL thing */
					$row['blog_id'] = $row['blog_id_id'] ? $row['blog_id_id'] : $row['blog_id'];
					
					$row['_can_approve']				= $this->registry->blogFunctions->allowApprove( $row );
					$row						        = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
					$row								= $this->registry->getClass('blogParsing')->parseEntry( $row, 1, array( 'entryParse' => $this->settings['blog_list_full'] ? 0 : 1, 'noPositionInc' => 0 ), $row );
					
					/* Got attachments to parse? */
					if ( $row['entry_has_attach'] )
					{
						$this->entryWithAttachments[ $row['entry_id'] ] = $row['entry_id'];
					}
					
					/* Store */
					$seenBlogs[ $row['blog_id'] ]       = $row['blog_id'];
					$blogs['normal'][ $row['blog_id'] ] = $row;
				}
			}
		}
		
		/* Fetch extra data */
		$extraData = $this->_sideBarData();
		
		/* Push out */
		return $this->registry->getClass('output')->getTemplate('blog_list')->blogIndexPage( '', $featured, $blogs, $extraData, 'dash', $all_sorting_options );
	}
	
	/**
	 * Fetch sorting options
	 * 
	 * @return	@e array
	 */
	protected function _fetchSortingOptions()
	{
		$this->first		= $this->request['st']			? intval($this->request['st'])			: 0;
		$this->max_results	= $this->request['max_results'] ? intval($this->request['max_results']) : 20;
		$this->sort_key		= $this->request['sort_key']	? $this->request['sort_key']			: $this->sort_key;
		$this->sort_order	= $this->request['sort_order']	? $this->request['sort_order']			: $this->sort_order;
		$this->filter		= $this->request['filter']		? strtolower($this->request['filter'])	: $this->filter;
		$this->namebox		= $this->request['namebox']		? $this->request['namebox']				: $this->namebox;
		
		/* Set it right for the skins */
		if ( ! isset( $this->request['sort_key'] ) )
		{
			$this->request['sort_key'] = $this->sort_key;
		}
		
		//------------------------------------------
		// Init some arrays
		//------------------------------------------

		$all_sorting_options	= array(
										'the_filter'		=> array(
																	'all'					=> 'show_all',
																	'local'					=> 'show_local',
																	'external'				=> 'show_external',
																	),
										'the_sort_key'		=> array(
																	'members_display_name'	=> 'sort_by_name',
																	'blog_name'				=> 'sort_by_blogname',
																	'blog_last_edate'		=> 'sort_by_lastentry',
																	'blog_num_entries'		=> 'sort_by_numentries',
																	'blog_num_views'		=> 'sort_by_numviews',
																	'blog_num_comments'		=> 'sort_by_numcomments',
																	'blog_last_comment'		=> 'sort_by_lastcomment',
																	'blog_rating'			=> 'sort_by_rating',
																	),
										'the_max_results'	=> array(
																	10						=> '10',
																	20						=> '20',
																	30						=> '30',
																	40						=> '40',
																	50						=> '50',
																	),
										'the_sort_order'	=> array(
																	'desc'					=> 'descending_order',
																	'asc'					=> 'ascending_order',
																	),
										'sort_orders'		=> array(
																	'members_display_name'	=> 'asc',
																	'blog_name'				=> 'asc',
																	'blog_last_date'		=> 'desc',
																	'blog_num_entries'		=> 'desc',
																	'blog_num_views'		=> 'desc',
																	'blog_num_comments'		=> 'desc',
																	'blog_last_comment'		=> 'desc',
																	'blog_rating'			=> 'desc',
																	),
										'selected'			=> array(
																	'the_filter'			=> $this->filter,
																	'the_sort_key'			=> $this->sort_key,
																	'the_sort_order'		=> $this->sort_order,
																	'the_max_results'		=> $this->max_results,
																	),
										);

									//---------------------------------------------------
									// Error?
									//---------------------------------------------------

		$error = isset($all_sorting_options['the_sort_key'][ $this->sort_key ])		? 0 : 1;
		$error = isset($all_sorting_options['the_sort_order'][ $this->sort_order ]) ? $error	: 1;
		$error = isset($all_sorting_options['the_filter'][ $this->filter ])			? $error	: 1;
		$error = isset($all_sorting_options['the_max_results'][ $this->max_results ])	? $error	: 1;

		if ( $error )
		{
			$this->registry->output->showError( 'incorrect_use', 10688 );
		}
		
		return $all_sorting_options;
	}
	
	/**
	 * Fetch featured blogs
	 * 
	 * @return	@e array
	 */
	protected function _fetchFeatured()
	{
		/* Init vars */
		$featured = array();
		$extra_w  = ( strtolower($this->filter) == 'external' ) ? " AND b.blog_type='" . $this->filter . "' " : '';
		
		if ( count( $this->caches['blog_stats']['featured'] ) )
		{ 
			$this->DB->build( array( 'select'	=>'e.*, e.blog_id as my_blog_id',
									 'from'		=> array( 'blog_entries' => 'e' ),
									 'where'	=> 'e.entry_id IN(' . implode( ',', array_values( $this->caches['blog_stats']['featured'] ) ) . ')'.$extra_w,
									 'order'    => 'e.entry_date DESC',
									 'limit'    => array( 0, 20 ),
									 'add_join'	=> array(array(  'select'   => 'b.*',
																 'from'     => array( 'blog_blogs' => 'b' ),
																 'where'    => 'b.blog_id=e.blog_id',
																 'type'     => 'left' ),
									 					 array( 'select'	=> 'bl.*',
																 'from'	    => array( 'blog_lastinfo' =>'bl' ),
																 'where'    => 'b.blog_id=bl.blog_id',
																 'type'	    => 'left' ), 
														 array(  'select'	=> 'm.*',
																 'from'	    => array( 'members' => 'm' ),
																 'where'    => 'm.member_id=e.entry_author_id',
																 'type'	    => 'left' ),
														 array(  'select'   => 'pp.*',
																 'from'	    => array( 'profile_portal' => 'pp' ),
																 'where'    => 'pp.pp_member_id=m.member_id',
																 'type'	    => 'left' ) ) ) );
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				if ( ! $row['blog_disabled'] AND $row['entry_status'] != 'draft' )
				{
					$row['last_read']	 			= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $row['blog_id'] ) );
					$row['blog_id']					= $row['my_blog_id'];
					$row							= $this->registry->getClass('blogFunctions')->buildBlogData( $row );
					$seenBlogs[ $row['blog_id'] ]	= $row['blog_id'];
					$row							= IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
					$row['entry_featured']			= 1;
					
					/* Tags */
					if ( $row['entry_id'] )
					{
						$row['tags'] = $this->tagsClass->getTagsByMetaId( $row['entry_id'] );
					}
					
					$row							= $this->registry->getClass('blogParsing')->parseEntry( $row, 1, array( 'entryParse' => ( $this->_view_type == 'dash' && $this->settings['blog_list_full'] ) ? 0 : 1, 'noPositionInc' => 1 ), $row );
					
					/* Got attachments to parse? */
					if ( $row['entry_has_attach'] )
					{
						$this->entryWithAttachments[ $row['entry_id'] ] = $row['entry_id'];
					}
					
					$featured[ $row['blog_id'] ]	= $row;
				}
			}
		}
		
		return $featured;
	}
	
	/**
	 * Fetch extra sidebar data
	 *
	 * @return	@e array	Current active users and recent entries
	 */
	protected function _sideBarData()
	{
		/* INIT */
		$entries			= array();
		$_entries			= array();
		$whereExtra         = '';
		
		if ( ! $this->memberData['member_id'] )
		{
			$whereExtra = " AND b.blog_allowguests = 1";
		}

		/* Recent entries */
		if ( count( $this->caches['blog_stats']['recent_entries'] ) )
		{
			$this->DB->build( array('select'   => 'e.entry_id, e.entry_last_update, e.entry_name, e.blog_id, e.entry_name_seo, e.entry_author_id, e.entry_date',
									'from'     => array('blog_entries' => 'e' ),
									'where'    => "e.entry_id IN(" . implode( ",", array_keys( $this->caches['blog_stats']['recent_entries'] ) ) . ")" . $whereExtra,
									'order'    => 'e.entry_date DESC',
									'limit'    => array( 0, 10 ),
									'add_join' => array( array( 'select' => 'b.blog_name, b.blog_seo_name',
																'from'   => array( 'blog_blogs' => 'b' ),
																'where'  => 'b.blog_id=e.blog_id',
																'type'   => 'left' ) ) ) );
							
			$this->DB->execute();
			
			while( $entry = $this->DB->fetch() )
			{
				$_entries[ $entry['entry_id'] ]    = $entry;
				$mids[ $entry['entry_author_id'] ] = $entry['entry_author_id'];
			}
		}
		
		if ( count( $mids ) )
		{
			$members = IPSMember::load( $mids, 'all' );
			
			if ( count( $members ) )
			{
				foreach( $_entries as $cid => $cdata )
				{
					if ( $cdata['entry_author_id'] and isset( $members[ $cdata['entry_author_id'] ] ) )
					{
						$_entries[ $cid ] = array_merge( $_entries[ $cid ], $members[ $cdata['entry_author_id'] ] );
					}
				}
			}
		}

		if( count( $_entries ) > 0 )
		{
			if( is_array( $_entries ) )
			{
				foreach( $_entries as $eid => $entry )
				{
					$entry = IPSMember::buildDisplayData( $entry, array( 'reputation' => 0, 'warn' => 0 ) );
					
					$entry['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $entry['blog_id'], 'itemID' => $entry['entry_id'] ) );
					
					if( $entry['entry_last_update'] > $entry['_lastRead'] )
					{
						$entry['newpost'] = true;
					}
					else
					{
						$entry['newpost'] = false;
					}

					$entries[ $eid ] = $entry;
				}
			}
		}
		
		/* Add it in */
		$extraData['activeUsers']   = $this->_getActiveUsers();
		$extraData['recentEntries'] = $entries;
		
		/* Add our cookie too while we're at it.. */
		$extraData['_stickCookie'] = $this->_stickCookie;

		return $extraData;
	}

	/**
	 * Get active users
	 *
	 * @return	@e array 
	 */	
	protected function _getActiveUsers()
	{
		$activeUsers = array();
		
		if ( $this->settings['blog_showactive'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
			$sessions    = new $classToLoad( $this->registry );
			
			$activeUsers = $sessions->getUsersIn('blog');
		}
		
		return $activeUsers;
	}
}