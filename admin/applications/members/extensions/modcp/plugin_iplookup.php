<?php
/**
 * @file		plugin_iplookup.php 	Moderator control panel plugin: IP lookup tool
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		2/18/2011
 * $LastChangedDate: 2012-03-23 08:34:13 -0400 (Fri, 23 Mar 2012) $
 * @version		v3.3.3
 * $Revision: 10474 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_members_iplookup
 * @brief		Moderator control panel plugin: IP lookup tool
 * 
 */
class plugin_members_iplookup
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

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'ip_tools';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'iplookup';
	}

	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if( $this->memberData['g_is_supmod'] )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if( !$this->canView( $permissions ) )
		{
			return '';
		}

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if( $this->request['_do'] == 'submit' )
		{
			return $this->_doLookup();
		}

		return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'] );
	}
	
	/**
	 * Lookup IP address info
	 *
	 * @return @e string
	 */
	protected function _doLookup()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$exactMatch		= 1;
		$finalIPString	= trim( $this->request['ip'] );
		$startVal		= intval($this->request['st']);
		$ipTool			= $this->request['iptool'];

 		//-----------------------------------------
		// Remove trailing periods
		//-----------------------------------------
		
		if ( strstr( $finalIPString, '*' ) )
		{
			$exactMatch		= 0;
			$finalIPString	= preg_replace( "/^(.+?)\*(.+?)?$/", "\\1", $finalIPString ).'%';
		}
				
		//-----------------------------------------
		// H'okay, what have we been asked to do?
		// (that's a metaphorical "we" in a rhetorical question)
		//-----------------------------------------
		
		if ( $ipTool == 'resolve' )
		{
			$resolved	= @gethostbyaddr( $finalIPString );
			
			if ( $resolved == "" )
			{
				return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->inlineModIPMessage( $this->lang->words['cp_no_matches'] ) );
			}
			else
			{
				return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->inlineModIPMessage( sprintf( $this->lang->words['ip_resolve_result'], $finalIPString, $resolved ) ) );
			}
		}
		else if ( $ipTool == 'members' )
		{
			$sql	= $exactMatch ? "ip_address='$finalIPString'" : "ip_address LIKE '$finalIPString'";

			if ( !$this->memberData['g_access_cp'] )
			{
				$sql	.= "AND g.g_access_cp != 1";
			}
			
			$total_possible	= $this->DB->buildAndFetch( array(
															'select'	=> 'count(m.member_id) as max',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> $sql,
															'add_join'	=> array(
																				array(
																						'from'	=> array( 'groups' => 'g' ),
																						'type'	=> 'left',
																						'where'	=> 'g.g_id=m.member_group_id',
																					)
																			)
													)		);
			
			if ( $total_possible['max'] < 1 )
			{
				return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->inlineModIPMessage( $this->lang->words['cp_no_matches'] ) );
			}
			
			$pages	= $this->registry->getClass('output')->generatePagination( array(	'totalItems'		=> $total_possible['max'],
															  							'itemsPerPage'		=> 20,
																						'currentStartValue'	=> $startVal,
																						'baseUrl'			=> "app=core&amp;module=modcp&amp;tab=iplookup&amp;fromapp=members&amp;_do=submit&amp;iptool=members&amp;ip=" . $this->request['ip'],
																			)		);

			$this->DB->build( array(
									'select'	=> 'm.name, m.members_display_name, m.members_seo_name, m.member_id, m.ip_address, m.posts, m.joined, m.member_group_id',
									'from'		=> array( 'members' => 'm' ),
									'where'		=> 'm.' . $sql,
									'order'		=> "m.joined DESC",
									'limit'		=> array( $startVal, 20 ),
									'add_join'	=> array(
														array( 'select'	=> 'g.g_access_cp',
																'from'	=> array( 'groups' => 'g' ),
																'type'	=> 'left',
																'where'	=> 'g.g_id=m.member_group_id',
															)
													)
						)		);
			$this->DB->execute();
		
			while( $row = $this->DB->fetch() )
			{
				$row['joined']		= $this->registry->getClass( 'class_localization')->getDate( $row['joined'], 'JOINED' );
				$row['groupname']	= IPSMember::makeNameFormatted( $this->caches['group_cache'][ $row['member_group_id'] ]['g_title'], $row['member_group_id'] );

				$members[ $row['member_id'] ]	= $row;
			}
			
			return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->membersModIPFormMembers( $pages, $members ) );
		}
		else
		{
			$sql	= $exactMatch ? "p.ip_address='$finalIPString'" : "p.ip_address LIKE '$finalIPString'";

			//-----------------------------------------
			// Get forums we can view
			//-----------------------------------------
			
			$aforum	= $this->registry->getClass('class_forums')->fetchSearchableForumIds();

			if ( count($aforum) < 1)
			{
				return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->inlineModIPMessage( $this->lang->words['cp_no_matches'] ) );
			}
			
			$the_forums	= implode( ",", $aforum );
			
			$count	= $this->DB->buildAndFetch( array(
													'select'	=> 'COUNT(*) as total',
													'from'		=> array( 'posts' => 'p' ),
													'where'		=> "t.forum_id IN({$the_forums}) AND {$sql}",
													'add_join'	=> array(
																		array(
																				'from'		=> array( 'topics' => 't' ),
																				'where'		=> 't.tid=p.topic_id',
																				'type'		=> 'left'
																			),
																		)
											)		);
											
			//-----------------------------------------
			// Do we have any results?
			//-----------------------------------------
			
			if ( !$count['total'] )
			{
				return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->inlineModIPMessage( $this->lang->words['cp_no_matches'] ) );
			}

	 		//-----------------------------------------
	 		// Pages
	 		//-----------------------------------------
	 		
	 		$pageLinks	= $this->registry->getClass('output')->generatePagination( array(	'totalItems'		=> $count['total'],
															   								'itemsPerPage'		=> 10,
																							'currentStartValue'	=> $startVal,
																							'baseUrl'			=> "app=core&amp;module=modcp&amp;tab=iplookup&amp;fromapp=members&amp;_do=submit&amp;iptool=posts&amp;ip=" . $this->request['ip'],
																				)		);
			
			$results	= array();

			$this->DB->build( array(	
									'select'	=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "t.forum_id IN({$the_forums}) AND {$sql}",
									'limit'		=> array( $startVal, 10 ),
									'order'		=> 'p.pid DESC',
									'add_join'	=> array(
														array(
																'select'	=> 't.forum_id, t.title_seo',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left'
															),
														array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=p.author_id',
																'type'		=> 'left'
															),
														array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left'
															),
														array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left'
															),
														)
								)		);
			$outer	= $this->DB->execute();

			while ( $row = $this->DB->fetch($outer) )
			{
				//-----------------------------------------
				// Parse the member
				//-----------------------------------------
				
				$row	= IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
				
				//-----------------------------------------
				// Parse the post
				//-----------------------------------------
		
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->registry->class_forums->allForums[ $row['forum_id'] ]['use_html'] and $this->caches['group_cache'][ $row['member_group_id'] ]['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->registry->class_forums->allForums[ $row['forum_id'] ]['use_ibc'];
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];	
			
				$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
				
				$results[]	= $row;
			}

			return $this->registry->getClass('output')->getTemplate('modcp')->membersModIPForm( $this->request['ip'], $this->registry->getClass('output')->getTemplate('modcp')->membersModIPFormPosts( $count['total'], $pageLinks, $results ) );
		}
	}
}