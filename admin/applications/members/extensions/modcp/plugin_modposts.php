<?php
/**
 * @file		plugin_modposts.php 	Moderator control panel plugin: show members on mod queue
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		2/17/2011
 * $LastChangedDate: 2011-11-16 11:36:38 -0500 (Wed, 16 Nov 2011) $
 * @version		v3.3.3
 * $Revision: 9829 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_members_modposts
 * @brief		Moderator control panel plugin: show members on mod queue
 * 
 */
class plugin_members_modposts
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
		
		/* Load language strings.. */
		$this->registry->class_localization->loadLanguageFile( array( 'public_list' ), 'members' );
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
		return 'modposts';
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
		
		/* Add some additional CSS */
		$this->registry->output->addToDocumentHead( 'importcss', "{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_mlist.css" );
		
		//-----------------------------------------
		// Get 10 members on mod queue
		//-----------------------------------------
		
		$st			= intval($this->request['st']);
		$total		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as members', 'from' => 'members', 'where' => "mod_posts!=0 AND mod_posts!='' AND mod_posts " . $this->DB->buildIsNull( false ) ) );
		$members	= array();
		
		$this->DB->build( array(
								'select'	=> 'm.*',
								'from'		=> array( 'members' => 'm' ),
								'order'		=> 'm.joined DESC',
								'limit'		=> array( $st, 10 ),
								'where'		=> "m.mod_posts!=0 AND m.mod_posts!='' AND m.mod_posts " . $this->DB->buildIsNull( false ),
								'add_join'	=> array(
													array(
														'select'	=> 'pp.*',
														'from'		=> array( 'profile_portal' => 'pp' ),
														'where'		=> 'm.member_id=pp.pp_member_id',
														'type'		=> 'left',
														),
													),
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$mod_arr		= IPSMember::processBanEntry( $r['mod_posts'] );
			
			if( $mod_arr['date_end'] AND $mod_arr['date_end'] < time() )
			{
				IPSMember::save( $r['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
				continue;
			}

			if( $mod_arr['date_start'] == 1 )
			{
				$r['_language']	= $this->lang->words['modcp_modq_indef'];
			}
			else
			{
				$r['_language']	= $this->registry->getClass('class_localization')->getDate( $mod_arr['date_end'], 'SHORT' );
			}

			$members[]		= IPSMember::buildDisplayData( $r );
		}

		//-----------------------------------------
		// Page links
		//-----------------------------------------
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $total['members'],
																		'itemsPerPage'		=> 10,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=members&amp;tab=modposts",
															)		);

		return $this->registry->output->getTemplate('modcp')->membersList( 'modposts', $members, $pages );
	}
}