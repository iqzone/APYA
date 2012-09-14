<?php
/**
 * @file		group_form.php 	Core group editing form
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2011-07-20 20:19:22 -0400 (Wed, 20 Jul 2011) $
 * @version		v3.3.3
 * $Revision: 9296 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @interface	admin_group_form__core
 * @brief		Core group editing form
 *
 */
class admin_group_form__core implements admin_group_form
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
	 * Tab name (leave it blank to use the default application title)
	 *
	 * @var		$tab_name
	 */
	public $tab_name = "";

	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= ipsRegistry::instance();
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;

		$this->tab_name		= ipsRegistry::getClass('class_localization')->words['tab_groupform_rc'];
	}
	
	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$group		Group data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_group_core', 'core' );
		
		//-----------------------------------------
		// Get report center plugins and sort perms
		//-----------------------------------------
		
		$_canView		= array();
		$_canSend		= array();
		$_plugins		= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_classes', 'order' => 'class_title' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$senders	= explode( ',', IPSText::cleanPermString( $r['group_can_report'] ) );
			$viewers	= explode( ',', IPSText::cleanPermString( $r['mod_group_perm'] ) );
			
			if( in_array( $group['g_id'], $senders ) )
			{
				$_canSend[]	= $r['com_id'];
			}

			if( in_array( $group['g_id'], $viewers ) )
			{
				$_canView[]	= $r['com_id'];
			}
			
			$_plugins[ $r['com_id'] ]	= $r;
		}

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ), $_plugins, $_canView, $_canSend ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		return array();
	}
	
	/**
	 * Post-process the entries for saving
	 *
	 * @author	Brandon Farber
	 * @param	int		$groupId	Group id
	 * @return	@e void
	 */
	public function postSave( $groupId )
	{
		$this->DB->build( array( 'select' => 'com_id, group_can_report, mod_group_perm', 'from' => 'rc_classes' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$senders	= explode( ',', IPSText::cleanPermString( $r['group_can_report'] ) );
			$viewers	= explode( ',', IPSText::cleanPermString( $r['mod_group_perm'] ) );
			$canView	= 0;
			$canSend	= 0;
			
			if( $this->request['rc_send_' . $r['com_id'] ] )
			{
				$canSend	= 1;
			}

			if( $this->request['rc_view_' . $r['com_id'] ] )
			{
				$canView	= 1;
			}
			
			$_newSenders	= array();
			$_newViewers	= array();
			
			foreach( $senders as $sender )
			{
				if( $sender == $groupId )
				{
					if( $canSend )
					{
						$_newSenders[ $sender ]	= $sender;
					}
				}
				else
				{
					$_newSenders[ $sender ]	= $sender;
				}
			}
			
			if( $canSend )
			{
				$_newSenders[ $groupId ]	= $groupId;
			}
			
			foreach( $viewers as $viewer )
			{
				if( $viewer == $groupId )
				{
					if( $canView )
					{
						$_newViewers[ $viewer ]	= $viewer;
					}
				}
				else
				{
					$_newViewers[ $viewer ]	= $viewer;
				}
			}
			
			if( $canView )
			{
				$_newViewers[ $groupId ]	= $groupId;
			}

			$this->DB->update( 'rc_classes', array( 'group_can_report' => ',' . implode( ',', $_newSenders ) . ',', 'mod_group_perm' => ',' . implode( ',', $_newViewers ) . ',' ), 'com_id=' . $r['com_id'] );
		}
		
		$this->cache->rebuildCache( 'report_plugins', 'global' );
	}
}