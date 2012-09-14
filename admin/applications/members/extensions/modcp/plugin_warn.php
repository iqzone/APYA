<?php
/**
 * @file		plugin_warn.php 	Moderator control panel plugin: show latest warnings
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2/14/2011
 * $LastChangedDate: 2011-12-14 12:27:41 -0500 (Wed, 14 Dec 2011) $
 * @version		v3.3.3
 * $Revision: 10000 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_members_warn
 * @brief		Moderator control panel plugin: show latest warnings
 * 
 */
class plugin_members_warn
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
		return 'manage_members';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'warn';
	}

	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if( $this->memberData['g_is_supmod'] OR ( $this->memberData['is_mod'] AND $permissions['allow_warn'] ) )
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
		// Load reasons
		//-----------------------------------------
		
		$reasons = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_reasons', 'order' => 'wr_order' ) );
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$reasons[ $row['wr_id'] ] = $row;
		}
		
		//-----------------------------------------
		// Get last 10 warnings
		//-----------------------------------------
		
		$st			= intval($this->request['st']);
		$total		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as warns', 'from' => 'members_warn_logs' ) );
		$warnings	= array();
		
		$this->DB->build( array(
								'select'	=> 'w.*',
								'from'		=> array( 'members_warn_logs' => 'w' ),
								'order'		=> 'w.wl_date DESC',
								'limit'		=> array( $st, 10 ),
								'add_join'	=> array(
													array(
														'select'	=> 'm.*',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=w.wl_member',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'pp.*',
														'from'		=> array( 'profile_portal' => 'pp' ),
														'where'		=> 'm.member_id=pp.pp_member_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'mm.member_id as punisher_id, mm.member_group_id as punisher_group, mm.members_display_name as punisher_name, mm.members_seo_name as punisher_seo_name',
														'from'		=> array( 'members' => 'mm' ),
														'where'		=> 'mm.member_id=w.wl_moderator',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'ppp.pp_main_photo as punisher_check, ppp.pp_thumb_photo as punisher_photo, ppp.pp_thumb_width as punisher_width, ppp.pp_thumb_height as punisher_height',
														'from'		=> array( 'profile_portal' => 'ppp' ),
														'where'		=> 'mm.member_id=ppp.pp_member_id',
														'type'		=> 'left',
														),
													),
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			/* Sort out punisher */
			$r['punisherMember']	= array( 'member_id'			=> $r['punisher_id'],
											 'member_group_id' 		=> $r['punisher_group'],
											 'members_display_name' => $r['punisher_name'],
											 'members_seo_name'		=> $r['punisher_seo_name']
											);
			
			$r['punisher_photo']	= IPSMember::buildProfilePhoto( array( 'pp_main_photo' => $r['punisher_check'], 'pp_thumb_photo' => $r['punisher_photo'], 'pp_thumb_width' => $r['punisher_width'], 'pp_thumb_height' => $r['punisher_height'] ), 'mini' );
			
			
			$r['wl_reason']			= $r['wl_reason'] ? $reasons[ $r['wl_reason'] ]['wr_name'] : '--';
			$warnings[]				= IPSMember::buildDisplayData( $r, array( 'reputation' => 0, 'warn' => 0 ) );
		}

		//-----------------------------------------
		// Page links
		//-----------------------------------------
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $total['warns'],
																		'itemsPerPage'		=> 10,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=members&amp;tab=warn",
															)		);
		
		return $this->registry->output->getTemplate('modcp')->latestWarnLogs( $warnings, $pages );
	}
}