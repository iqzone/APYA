<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Library to facilitate ACP member searches
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class adminMemberSearch
{
	/**#@+
	 * Registry objects
	 *
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $lang;
	/**#@-*/

	/**
	 * Data for HTML form
	 *
	 * @var		array
	 */
	protected $htmlPresets		= array();
	
	/**
	 * Reset filters and pull all members
	 *
	 * @var		bool
	 */
	protected $showAllMembers	= false;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Generate context-menu filter boxes. 
	 * Pass &_nosave=1 to not store / read from a cookie
	 *
	 * @author	Matt Mecham
	 * @since	IPB 3.0.0
	 * @return	array
	 */
	public function generateFilterBoxes()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		static $_return		= array();
		
		if( count($_return) )
		{
			return $_return;
		}

		$form				= array();
		$custom_field_data	= array();
		$filters_preset		= 0;

		//-----------------------------------------
		// Custom filtering
		//-----------------------------------------
		
		$member_string			= $this->request['string']					? trim( $this->request['string'] )					: '';
		$member_contains		= $this->request['f_member_contains']		? trim( $this->request['f_member_contains'] )		: '';
		$member_contains_type	= $this->request['f_member_contains_type']	? trim( $this->request['f_member_contains_type'] )	: '';
		$member_contains_text	= $this->request['f_member_contains_text']	? trim( $this->request['f_member_contains_text'] )	: '';

		$_member_contains		= array(	
										0 => array( 'members_display_name'	, $this->lang->words['m_f_display']	),
										1 => array( 'name'					, $this->lang->words['m_f_login']	),
										2 => array( 'member_id'				, $this->lang->words['m_f_id']		),
										3 => array( 'email'					, $this->lang->words['m_f_email']	),
										4 => array( 'ip_address'			, $this->lang->words['m_f_ip']		),
										5 => array( 'signature'				, $this->lang->words['m_f_sig']		) 
										);

		$_member_contains_type	= array(	
										0 => array( 'contains'	, $this->lang->words['m_f_contains']	),
										1 => array( 'equals'	, $this->lang->words['m_f_equals']		),
										2 => array( 'begins'	, $this->lang->words['m_f_begins']		),
										3 => array( 'ends'		, $this->lang->words['m_f_ends']		)
										);

		//-----------------------------------------
		// Order by
		//-----------------------------------------
		
		$order_by			= '';

		$order_by			= $this->request['order_by'] ? $this->request['order_by'] : 'joined';

		$_order_by			= array(
									0 => array( 'joined'				, $this->lang->words['m_f_joined']		),
									1 => array( 'members_l_username'	, $this->lang->words['m_f_slogin']		),
									2 => array( 'members_l_display_name', $this->lang->words['m_f_sdisplay']	),
									3 => array( 'email'					, $this->lang->words['m_f_email']		),
									4 => array( 'posts'					, $this->lang->words['m_f_posts']		),
									);

		$order_direction	= $this->request['order_direction'] ? strtolower($this->request['order_direction']) : 'desc';

		$_order_direction	= array(
									0 => array( 'asc'	, $this->lang->words['m_f_orderaz']	),
									1 => array( 'desc'	, $this->lang->words['m_f_orderza']	)
									);

		//-----------------------------------------
		// Member status type
		//-----------------------------------------
		
		$member_status		= ( $this->request['type'] AND in_array( $this->request['type'], array( 'all', 'banned', 'spam', 'validating', 'incomplete', 'locked' ) ) ) ? $this->request['type'] : 'all';
		
		$_member_status		= array(
									0 => array( 'all',			$this->lang->words['m_f_sall'] ),
									1 => array( 'banned',		$this->lang->words['m_f_sbanned'] ),
									2 => array( 'spam',			$this->lang->words['m_f_sspam'] ),
									3 => array( 'validating',	$this->lang->words['m_f_svalidating'] ),
									4 => array( 'incomplete',	$this->lang->words['m_f_simpcomplete'] ),
									5 => array( 'locked',		$this->lang->words['m_f_slocked'] ),
									);

		//-----------------------------------------
		// Search type
		//-----------------------------------------

		$search_type		= $this->request['f_search_type'] ? $this->request['f_search_type'] : 'normal';

		$_search_type		= array( 0 => array( 'normal', $this->lang->words['m_f_toedit'] ) );
		
		if( $this->registry->getClass('class_permissions')->checkPermission( 'member_delete' ) )
		{
			$_search_type[1]	= array( 'delete', $this->lang->words['m_f_todelete'] );
		}
		
		if( $this->registry->getClass('class_permissions')->checkPermission( 'member_move' ) )
		{
			$_search_type[2]	= array( 'move', $this->lang->words['m_f_tomove'] );
		}
		
		//-----------------------------------------
		// Date ranges
		//-----------------------------------------
		
		$date_reg_from		= $this->request['f_date_reg_from']		? trim( $this->request['f_date_reg_from'] )		: '';
		$date_reg_to		= $this->request['f_date_reg_to']		? trim( $this->request['f_date_reg_to'] )		: '';
		
		$date_post_from		= $this->request['f_date_post_from']	? trim( $this->request['f_date_post_from'] )	: '';
		$date_post_to		= $this->request['f_date_post_to']		? trim( $this->request['f_date_post_to'] )		: '';
	
		$date_active_from	= $this->request['f_date_active_from']	? trim( $this->request['f_date_active_from'] )	: '';
		$date_active_to		= $this->request['f_date_active_to']	? trim( $this->request['f_date_active_to'] )	: '';
		
		//-----------------------------------------
		// Groups
		//-----------------------------------------
		
		$primary_group		= $this->request['f_primary_group']		? trim( $this->request['f_primary_group'] )		: 0;
		$secondary_group	= $this->request['f_secondary_group']	? trim( $this->request['f_secondary_group'] )	: 0;
		$include_secondary	= $this->request['f_inc_secondary']		? 1 : 0;
		
		$_primary_group		= array( 0 => array( '0', $this->lang->words['m_f_primany'] ) );
		$_secondary_group	= array( 0 => array( '0', $this->lang->words['m_f_secany'] ) );

		foreach( ipsRegistry::cache()->getCache('group_cache') as $_gdata )
		{
			$_primary_group[]	= array( $_gdata['g_id'] , $_gdata['g_title'] );
			$_secondary_group[]	= array( $_gdata['g_id'] , $_gdata['g_title'] );
		}

		//-----------------------------------------
		// Post counts
		//-----------------------------------------
		
		$post_count			= ( $this->request['f_post_count'] || $this->request['f_post_count'] == '0' ) ? trim( $this->request['f_post_count'] ) : '';
		$post_count_type	= ( $this->request['f_post_count_type'] ) ? trim( $this->request['f_post_count_type'] ) : '';

		$_post_count_types	= array( 0 => array( 'lt'   , $this->lang->words['pc_lt'] ),
								   	 1 => array( 'gt'  , $this->lang->words['pc_gt'] ),
								   	 2 => array( 'eq'  , $this->lang->words['pc_eq'] ) );

		//-----------------------------------------
		// Reset filters if set to
		//-----------------------------------------
		
		if( $this->request['reset_filters'] )
		{
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
		}
		
		//-----------------------------------------
		// Retrieve filter from "cookie"
		//-----------------------------------------

		if ( ! $this->request['__update'] AND ! $this->request['_nosave'] )
		{
			$_cookie_array	= ipsRegistry::getClass('adminFunctions')->staffGetCookie( 'memberFilter' );
			
			if ( is_array( $_cookie_array ) AND count ( $_cookie_array ) )
			{
				$member_contains		= substr( $_cookie_array['c_member_contains'], 0,20 );
				$member_contains_type	= substr( $_cookie_array['c_member_contains_type'], 0,20 );
				$member_contains_text	= substr( $_cookie_array['c_member_contains_text'], 0,50 );
				$member_status			= trim( IPSText::alphanumericalClean( $_cookie_array['c_member_status'] ) );
				$member_string			= trim( $_cookie_array['c_member_string'] );
				$post_count				= trim( IPSText::alphanumericalClean( $_cookie_array['c_post_count'] ) );
				$post_count_type		= trim( IPSText::alphanumericalClean( $_cookie_array['c_post_count_type'] ) );
				$order_by				= trim( IPSText::alphanumericalClean( $_cookie_array['c_order_by'] ) );
				$order_direction		= trim( IPSText::alphanumericalClean( $_cookie_array['c_order_direction'] ) );
				$date_reg_from			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_reg_from'], '/-' ) );
				$date_reg_to			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_reg_to'], '/-' ) );
				$date_post_from			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_post_from'], '/-' ) );
				$date_post_to			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_post_to'], '/-' ) );
				$date_active_from		= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_active_from'], '/-' ) );
				$date_active_to			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_active_to'], '/-' ) );
				$primary_group			= trim( IPSText::alphanumericalClean( $_cookie_array['c_primary_group'], '/-' ) );
				$include_secondary		= trim( IPSText::alphanumericalClean( $_cookie_array['c_include_secondary'], '/-' ) );
				$secondary_group		= trim( IPSText::alphanumericalClean( $_cookie_array['c_secondary_group'], '/-' ) );
				$custom_field_cookie	= explode( '||', $_cookie_array['c_custom_fields'] );

				if( 
					$member_contains || $member_contains_type || $member_contains_text || $order_by || $order_direction ||
					$date_reg_from || $date_reg_to || $date_post_from || $date_post_to || $date_active_from || $date_active_to || $primary_group ||
					$secondary_group  || $post_count || $post_count_type || $include_secondary || $member_status || $member_string
					)
				{
					$filters_preset	= 1;
				}

				if( is_array( $custom_field_cookie ) AND count($custom_field_cookie) )
				{
					foreach( $custom_field_cookie as $field )
					{
						$data											= explode( '==', $field );
						$custom_field_data[ 'field_' . $data[0] ]		= $data[1];
						ipsRegistry::$request[ 'field_' . $data[0] ]	= $data[1];

						if( $data[1] )
						{
							$filters_preset	= 1;
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Get custom profile information
		//-----------------------------------------

		$custom_field_data	= count($custom_field_data) ? $custom_field_data : $_REQUEST;

		foreach( $custom_field_data as $k => $v )
		{
			if( strpos( $k, 'ignore_field_' ) === 0 )
			{
				$key	= substr( $k, 13 );
				
				$custom_field_data[ 'field_' . $key ]	= '';
			}
		}

		$classToLoad			= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields					= new $classToLoad();
    	
    	$fields->member_data	= $custom_field_data;
    	$fields->initData( 'edit', 1 );
    	$fields->parseToEdit();

		$custom_field_data_imploded	= array();

		foreach( $custom_field_data as $k => $v )
		{
			if( strpos( $k, 'field_' ) === 0 AND $v )
			{
				$custom_field_data_imploded[]	= substr( $k, 6 ) . '==' . $v;
			}
		}
		
		$custom_field_data_imploded	= implode( '||', $custom_field_data_imploded );
		
		//-----------------------------------------
		// Generate form data
		//-----------------------------------------

		$form['_member_contains']		= $this->registry->output->formDropdown( 'f_member_contains'		, $_member_contains, $member_contains  );
		$form['_member_contains_type']	= $this->registry->output->formDropdown( 'f_member_contains_type'	, $_member_contains_type, $member_contains_type );
		$form['_member_contains_text']	= $this->registry->output->formInput('f_member_contains_text'	, $member_contains_text, 'f_member_contains_text', 15, '', ' no_width' );
		$form['_member_status']			= $this->registry->output->formDropdown( 'type'						, $_member_status, $member_status  );
		$form['_member_string']			= $this->registry->output->formSimpleInput('string'					, $member_string, 15 );
		$form['_order_by']				= $this->registry->output->formDropdown( 'order_by'					, $_order_by, preg_replace( "#.*\.(.*)$#", "\\1", $order_by ) );
		$form['_order_direction']		= $this->registry->output->formDropdown( 'order_direction'			, $_order_direction, $order_direction );
		$form['_search_type']			= $this->registry->output->formDropdown( 'f_search_type'			, $_search_type, $search_type );
		$form['_post_count']			= $this->registry->output->formSimpleInput('f_post_count'			, $post_count, 10 );
		$form['_post_count_type']		= $this->registry->output->formDropdown( 'f_post_count_type'		, $_post_count_types, $post_count_type );
		$form['_date_reg_from']			= $this->registry->output->formInput('f_date_reg_from'				, $date_reg_from, 'f_date_reg_from', 15, 'text', '', ' no_width' );
		$form['_date_reg_to']			= $this->registry->output->formInput('f_date_reg_to'				, $date_reg_to, 'f_date_reg_to', 15, 'text', '', ' no_width' );
		$form['_date_post_from']		= $this->registry->output->formInput('f_date_post_from'				, $date_post_from, 'f_date_post_from', 15, 'text', '', ' no_width' );
		$form['_date_post_to']			= $this->registry->output->formInput('f_date_post_to'				, $date_post_to, 'f_date_post_to', 15, 'text', '', ' no_width' );
		$form['_date_active_from']		= $this->registry->output->formInput('f_date_active_from'			, $date_active_from, 'f_date_active_from', 15, 'text', '', ' no_width' );
		$form['_date_active_to']		= $this->registry->output->formInput('f_date_active_to'				, $date_active_to, 'f_date_active_to', 15, 'text', '', ' no_width' );
		$form['_primary_group']			= $this->registry->output->formDropdown( 'f_primary_group'			, $_primary_group, $primary_group );
		$form['_secondary_group']		= $this->registry->output->formDropdown( 'f_secondary_group'		, $_secondary_group, $secondary_group );
		$form['_include_secondary']		= $this->registry->output->formCheckbox( 'f_inc_secondary'			, $include_secondary );

		//-----------------------------------------
		// Store the cooookie
		// @see http://community.invisionpower.com/tracker/issue-19031-acp-members-page-always-thinks-its-being-filtered/
		//-----------------------------------------
		
		if ( ( $this->request['__update'] OR $this->request['f_search_type'] ) AND ! $this->request['_nosave'] )
		{
			$_cookie = array(
							'c_member_status'			=> $member_status,
							'c_member_string'			=> $member_string,
							'c_member_contains'			=> $member_contains,
							'c_member_contains_type'	=> $member_contains_type,
							'c_member_contains_text'	=> $member_contains_text,
							'c_order_by'				=> preg_replace( "#.*\.(.*)$#", "\\1", $order_by ),
							'c_order_direction'			=> $order_direction,
							'c_post_count'				=> $post_count,
							'c_post_count_type'			=> $post_count_type,
							'c_date_reg_from'			=> $date_reg_from,
							'c_date_reg_to'				=> $date_reg_to,
							'c_date_post_from'			=> $date_post_from,
							'c_date_post_to'			=> $date_post_to,
							'c_date_active_from'		=> $date_active_from,
							'c_date_active_to'			=> $date_active_to,
							'c_primary_group'			=> $primary_group,
							'c_secondary_group'			=> $secondary_group,
							'c_include_secondary'		=> $include_secondary,
							'c_custom_fields'			=> $custom_field_data_imploded
							);

			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', $_cookie );

		}

		//-----------------------------------------
		// Create filter boxes
		//-----------------------------------------

		$this->htmlPresets	= array( 'form' => $form, 'fields' => $fields, 'presets' => $filters_preset );
	
		//-----------------------------------------
		// Return data
		//-----------------------------------------
		
		$_return['custom_fields']	= '';
		
		if( is_array( $fields->out_fields ) AND count( $fields->out_fields ) )
		{
			foreach( $fields->out_fields as $id => $data )
			{
				$_return['custom_fields'][ $id ] = $fields->in_fields[ $id ];
			}
		}

		foreach( array_keys( $form ) as $_key )
		{
			$__key = substr( $_key, 1 );
			
			$_return[ $__key ] = ${ $__key };
		}

		return $_return;
	}
	
	/**
	 * Get HTML presets
	 *
	 * @return	array
	 */
	public function getHtmlPresets()
	{
		if( !count($this->htmlPresets) )
		{
			$this->generateFilterBoxes();
		}

		return $this->htmlPresets;
	}

	/**
	 * Get type of search performed
	 *
	 * @return	array
	 */
	public function getSearchType()
	{
		$data	= $this->generateFilterBoxes();
		
		return $data['search_type'];
	}

	/**
	 * Get member search status type
	 *
	 * @return	array
	 */
	public function getMemberType()
	{
		$data	= $this->generateFilterBoxes();
		
		return $data['member_status'];
	}
	
	/**
	 * Reset filters - pull all members with no WHERE clause
	 *
	 * @return	@e void
	 */
	public function resetFilters()
	{
		$this->showAllMembers	= true;
	}
	
	/**
	 * Get search results count
	 *
	 * @param	string	[$extraQuery]	Extra query where clause
	 * @return	int 	Number of search results
	 */
	public function getSearchResultsCount( $extraQuery='' )
	{
		$extra	= $extraQuery ? " AND " . $extraQuery : '';
		$count	= $this->DB->buildAndFetch( array(
													'select'	=> 'COUNT(*) as count',
													'from'		=> array( 'members' => 'm' ),
													'where'		=> $this->getWhereClause() . $extra,
													'add_join'	=> array(
																		array(
																			'from'	=> array( 'profile_portal' => 'pp' ),
																			'where'	=> 'pp.pp_member_id=m.member_id',
																			'type'	=> 'left' 
																			),
																		array(
																			'from'	=> array( 'pfields_content' => 'p' ),
																			'where'	=> 'p.member_id=m.member_id',
																			'type'	=> 'left' 
																			),
																		array(
																			'from'	=> array( 'members_partial' => 'par' ),
																			'where'	=> 'par.partial_member_id=m.member_id',
																			'type'	=> 'left' 
																			),
																		array(
																			'from'		=> array( 'validating' => 'val' ),
																			'where'		=> 'val.member_id=m.member_id',
																			'type'		=> 'left'
																			)
																		)
										)		);

		return intval($count['count']);
	}
	
	/**
	 * Get search results
	 *
	 * @param	int		$st				Start offset
	 * @param	int		$limit			Results limit
	 * @param	string	[$extraQuery]	Extra query where clause
	 * @param	bool	[$minimalInfo]	Only return ids and names
	 * @return	array 	Array of search results, or an array with keys 'ids' and 'names' if $minimalInfo is true
	 */
	public function getSearchResults( $st, $limit, $extraQuery='', $minimalInfo=false )
	{
		$extra		= $extraQuery ? " AND " . $extraQuery : '';
		$members	= array();
		$ids		= array();
		$names		= array();
		
		//-----------------------------------------
		// Build query
		//-----------------------------------------
		
		$query		= array(
								'select'	=> 'm.*, m.member_id as mem_id, m.ip_address as mem_ip',
								'from'		=> array( 'members' => 'm' ),
								'where'		=> $this->getWhereClause() . $extra,
								'order'		=> $this->getOrderByClause(),
								'add_join'	=> array(
													array(
														'select'	=> 'p.*',
														'from'		=> array( 'pfields_content' => 'p' ),
														'where'		=> 'p.member_id=m.member_id',
														'type'		=> 'left'
														),
													array(
														'select'	=> 'pp.*',
														'from'		=> array( 'profile_portal' => 'pp' ),
														'where'		=> 'pp.pp_member_id=m.member_id',
														'type'		=> 'left'
														),
													array(
														'select'	=> 'par.*',
														'from'		=> array( 'members_partial' => 'par' ),
														'where'		=> 'par.partial_member_id=m.member_id',
														'type'		=> 'left'
														),
													array(
														'select'	=> 'val.*',
														'from'		=> array( 'validating' => 'val' ),
														'where'		=> 'val.member_id=m.member_id',
														'type'		=> 'left'
														)
													)
						);

		if( $st OR $limit )
		{
			$query['limit']	= array( $st, $limit );
		}
		
		//-----------------------------------------
		// Execute query and return results
		//-----------------------------------------
		
		$this->DB->build( $query );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			if( $minimalInfo )
			{
				$ids[ $r['mem_id'] ]					= $r['mem_id'];
				$names[ $r['members_display_name'] ]	= $r['members_display_name'];
			}
			else
			{
				$r['member_id']				= $r['mem_id'];
				$r['ip_address']			= $r['mem_ip'];
				$r['_joined']				= $this->registry->class_localization->getDate( $r['joined'], 'JOINED' );
				$r['group_title']			= $this->caches['group_cache'][ $r['member_group_id'] ]['g_title'];
	
				$members[ $r['member_id'] ]	= IPSMember::buildDisplayData( $r );
			}
		}
		
		return $minimalInfo ? array( 'ids' => $ids, 'names' => $names ) : $members;
	}
	
	/**
	 * Get the ORDER BY part of the SQL query
	 *
	 * @return	string	SQL ORDER BY string
	 */
	public function getOrderByClause()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$data			= $this->generateFilterBoxes();

		switch( $data['order_direction'] )
		{
			case 'asc':
				$order_direction = 'asc';
			break;
			default:
			case 'desc':
				$order_direction = 'desc';
			break;
		}
		
		switch( $data['order_by'] )
		{
			default:
			case 'joined':
				$order_by  = 'm.joined';
			break;
			case 'members_l_username':
				$order_by  = 'm.members_l_username';
			break;
			case 'members_l_display_name':
			case 'members_display_name':
				$order_by  = 'm.members_l_display_name';
			break;
			case 'email':
				$order_by  = 'm.email';
			break;
			case 'posts':
				$order_by  = 'm.posts';
			break;
		}
		
		return $order_by . ' ' . $order_direction;
	}
		
	/**
	 * Get the WHERE part of the SQL query
	 *
	 * @return	string	SQL WHERE string
	 */
	public function getWhereClause()
	{
		//-----------------------------------------
		// Ignore filters?
		//-----------------------------------------
		
		if( $this->showAllMembers )
		{
			return '';
		}

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$data			= $this->generateFilterBoxes();
		$_sql			= array();

		//-----------------------------------------
		// Filters
		//-----------------------------------------
		
		if ( $data['member_contains_text'] )
		{
			$_field	= '';
			$_text	= $this->DB->addSlashes( $data['member_contains_text'] );

			switch( $data['member_contains'] )
			{
				default:
				case 'member_id':
					$_field = 'm.member_id';
				break;

				case 'name':
					$_field = 'm.members_l_username';
					$_text	= strtolower( $_text  );
				break;

				case 'members_display_name':
					$_field = 'm.members_l_display_name';
					$_text	= strtolower( $_text  );
				break;
				case 'email':
					$_field = 'm.email';
				break;
				case 'ip_address':
					$_field = 'm.ip_address';
				break;
				case 'signature':
					$_field = 'pp.signature';
				break;
			}

			switch( $data['member_contains_type'] )
			{
				default:
				case 'contains':
					$_sql[] = $this->DB->buildCast( $_field, 'VARCHAR' ) . " LIKE '%" . $_text . "%'";
				break;
				case 'begins':
					$_sql[] = $this->DB->buildCast( $_field, 'VARCHAR' ) . " LIKE '" . $_text . "%'";
				break;
				case 'ends':
					$_sql[] = $this->DB->buildCast( $_field, 'VARCHAR' ) . " LIKE '%" . $_text . "'";
				break;
				case 'equals':
					$_sql[] = $this->DB->buildCast( $_field, 'VARCHAR' ) . " = '" . $_text . "'";
				break;
			}
		}
		
		//-----------------------------------------
		// "Simple" all-in-one search
		//-----------------------------------------
		
		if( $data['member_string'] AND strlen($data['member_string']) >= 3 )
		{
			/* Fix from ticket 766094 */
			$_text	= $this->DB->addSlashes( IPSText::convertCharsets( $data['member_string'], 'utf-8', IPS_DOC_CHAR_SET ) );

			$_sql[]	= '(' . $this->DB->buildCast( 'm.name', 'VARCHAR' ) . " LIKE '%" . $_text . "%' OR " .
						$this->DB->buildCast( 'm.members_display_name', 'VARCHAR' ) . " LIKE '%" . $_text . "%' OR " .
						$this->DB->buildCast( 'm.email', 'VARCHAR' ) . " LIKE '%" . $_text . "%' OR " .
						$this->DB->buildCast( 'm.ip_address', 'VARCHAR' ) . " LIKE '%" . $_text . "%')";
		}

		//-----------------------------------------
		// Group limiting
		//-----------------------------------------
		
		if ( $data['primary_group'] )
		{
			if( $data['include_secondary'] )
			{
				$_sql[]	= "( m.member_group_id=" . intval( $data['primary_group'] ) . " OR " . 
							"m.mgroup_others LIKE '%," . intval( $data['primary_group'] ) . ",%' OR " .
							"m.mgroup_others LIKE '" . intval( $data['primary_group'] ) . ",%' OR " .
							"m.mgroup_others LIKE '%," . intval( $data['primary_group'] ) . "' OR " .
							"m.mgroup_others='" . intval( $data['primary_group'] ) . "' )";
			}
			else
			{
				$_sql[]	= "m.member_group_id=" . intval( $data['primary_group'] );
			}
		}

		if ( $data['secondary_group'] )
		{
			$_sql[] = "( m.mgroup_others LIKE '%," . $data['secondary_group'] . ",%' OR " .
						"m.mgroup_others LIKE '" . $data['secondary_group'] . ",%' OR " .
						"m.mgroup_others LIKE '%," . $data['secondary_group'] . "' OR " .
						"m.mgroup_others='" . $data['secondary_group'] . "' )";
		}

		//-----------------------------------------
		// Post count
		//-----------------------------------------
		
		if ( ( $data['post_count'] OR $data['post_count'] == '0' ) AND $data['post_count_type'] )
		{
			$_type	= '';
			
			if( $data['post_count_type'] == 'gt' )
			{
				$_type	= '>';
			}
			else if( $data['post_count_type'] == 'lt' )
			{
				$_type	= '<';
			}
			else if( $data['post_count_type'] == 'eq' )
			{
				$_type	= '=';
			}
			
			if( $_type )
			{
				$_sql[] = "m.posts" . $_type . intval( $data['post_count'] );
			}
		}

		//-----------------------------------------
		// Date filters
		//-----------------------------------------

		foreach( array( 'reg', 'post', 'active' ) as $_bit )
		{
			foreach( array( 'from', 'to' ) as $_when )
			{
				$bit	= 'date_' . $_bit . '_' . $_when;
				
				if ( $data[ $bit ] )
				{
					//-----------------------------------------
					// mm/dd/yyyy instead of mm-dd-yyyy
					//-----------------------------------------
					
					$data[ $bit ]	= str_replace( '/', '-', $data[ $bit ] );
					
					list( $month, $day, $year ) = explode( '-', $data[ $bit ] );

					if ( ! checkdate( $month, $day, $year ) )
					{
						$this->registry->output->global_message	= sprintf($this->lang->words['m_daterange'], $month, $day, $year );
					}
					else
					{
						/* Bug #24067 */
						/* Original fix caused this bug: http://community.invisionpower.com/tracker/issue-24416-search-dates-invalid-acp/
							Changed to just verify the result is an int, as that is all that's needed to prevent DB error */
						$time_int = mktime( 0, 0, 0, $month, $day, $year );
						
						if ( !is_int($time_int) )
						{
							$this->registry->output->global_message	= sprintf($this->lang->words['m_daterange'], $month, $day, $year );
						}
						else
						{
							switch( $_bit )
							{
								case 'reg':
									$field = 'joined';
								break;
								case 'post':
									$field = 'last_post';
								break;
								case 'active':
									$field = 'last_activity';
								break;
							}
		
							if ( $_when == 'from' )
							{
								$_sql[] = 'm.' . $field . ' > ' . $time_int;
							}
							else
							{
								$_sql[] = 'm.' . $field . ' < ' . $time_int;
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Custom fields...
		//-----------------------------------------

		if( is_array($data['custom_fields']) AND count($data['custom_fields']) )
		{
			foreach ( $data['custom_fields'] as $id => $value )
	 		{
 				if ( $value )
 				{
					if( $this->caches['profilefields'][ $id ]['pf_type'] == 'drop' )
					{
						$_sql[]	= "p.field_{$id}='" . $this->DB->addSlashes( $value ) . "'";
					}
					else if( $this->caches['profilefields'][ $id ]['pf_type'] == 'cbox' )
					{
						if ( count( $value ) )
						{
							foreach ( $value as $k => $v )
							{
								$_sql[]	= "p.field_{$id} LIKE '%|" . $this->DB->addSlashes( $k ) . "|%'";
							}
						}
					}
					else
					{
						$_sql[] = $this->caches['profilefields'][ $id ]['pf_search_type'] == 'loose' ? "p.field_{$id} LIKE '%" . $this->DB->addSlashes( $value ) . "%'" : "p.field_{$id} = '" . $this->DB->addSlashes( $value ) . "'";
					}
 				}
	 		}
 		}
 		
		//-----------------------------------------
		// Search member status type
		//-----------------------------------------
		
		if ( $data['member_status'] )
		{
			switch( $data['member_status'] )
			{
				case 'banned':
					$_sql[]	= "(m.member_banned=1)";
				break;

				case 'spam':
					$_sql[]	= '(' . IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' ) . ' OR val.spam_flag=1)';
				break;
				
				case 'validating':
					$_sql[] = "(val.lost_pass=0 AND val.vid " . $this->DB->buildIsNull( false ) . ")";
				break;
				
				case 'incomplete':
					$_sql[] = "par.partial_member_id " . $this->DB->buildIsNull( false );
				break;

				case 'locked':
					if( $this->settings['ipb_bruteforce_attempts'] > 0 )
					{
						$_sql[] = "m.failed_login_count >= " . intval($this->settings['ipb_bruteforce_attempts']);
					}
					else
					{
						$_sql[] = "m.failed_login_count > 0";
					}
				break;
				
				default:
				case 'all':
					//-----------------------------------------
					// Hide partial members if showing 'all', and
					// we did not explicitly search
					//-----------------------------------------
					
					if( !count($_sql) )
					{
						$_sql[]	= "par.partial_member_id " . $this->DB->buildIsNull();
					}
				break;
			}
		}

		//-----------------------------------------
		// Return search string
		//-----------------------------------------
		
		return count($_sql) ? implode( " AND ", $_sql ) : '';
	}
}