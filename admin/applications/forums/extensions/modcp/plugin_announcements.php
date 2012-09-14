<?php
/**
 * @file		plugin_announcements.php 	Moderator control panel plugin: manage announcements
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * @since		2/18/2011
 * $LastChangedDate: 2012-05-30 10:45:05 -0400 (Wed, 30 May 2012) $
 * @version		v3.3.3
 * $Revision: 10821 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_forums_announcements
 * @brief		Moderator control panel plugin: manage announcements
 * 
 */
class plugin_forums_announcements
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
		return 'announcements';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'announcements';
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
		// Doing something else?
		//-----------------------------------------
		
		switch( $this->request['_do'] )
		{
			case 'delete':
				return $this->_delete();
			break;
			
			case 'add':
			case 'edit':
				return $this->_form( $this->request['_do'] );
			break;
			
			case 'save':
				return $this->_save( $this->request['type'] );
			break;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$announcements	= array();

		//-----------------------------------------
		// Get announcements
		//-----------------------------------------
		
		$this->DB->build( array(
								'select'	=> 'a.*',
								'from'		=> array( 'announcements' => 'a' ),
								'order'		=> 'a.announce_end DESC',
								'add_join'	=> array(
													array( 'select'	=> 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name',
															'from'	=> array( 'members' => 'm' ),
															'where'	=> 'm.member_id=a.announce_member_id',
															'type'	=> 'left'
														)
													)
								)		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$r['announce_seo_title']	= $r['announce_seo_title'] ? $r['announce_seo_title'] : IPSText::makeSeoTitle( $r['announce_title'] );
			
			if ( $r['announce_forum'] == '*' )
			{
				$r['announce_forum_show'] = $this->lang->words['announce_page_allforums'];
			}
			else
			{
				$tmp_forums = explode(",",$r['announce_forum']);

				if ( is_array( $tmp_forums ) and count($tmp_forums) )
				{
					if ( count($tmp_forums) > 5 )
					{
						$r['announce_forum_show'] = count($tmp_forums) . ' ' . $this->lang->words['announce_page_numforums'];
					}
					else
					{
						$r['_forums'] = array();
						
						foreach( $tmp_forums as $id )
						{
							$r['_forums'][] = array( $id, $this->registry->getClass('class_forums')->forum_by_id[ $id ]['name'] );
						}
					}
				}	
			}

			$announcements[] = $r;
		}
		
		return $this->registry->getClass('output')->getTemplate('modcp')->modAnnouncements( $announcements );
	}
	
	/**
	 * Delete announcement
	 *
	 * @return	@e void
	 */
	protected function _delete()
	{
		
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10311902, null, null, 403 );
		}
		
		$announce_id	= intval( $this->request['announce_id'] );

		//-----------------------------------------
		// Delete it
		//-----------------------------------------
		
		$this->DB->delete( 'announcements', 'announce_id=' . $announce_id );
		
		//-----------------------------------------
		// Update cache and redirect
		//-----------------------------------------
		
		$this->registry->cache()->rebuildCache( 'announcements', 'forums' );
		
		$this->registry->getClass('output')->redirectScreen( $this->lang->words['announcement_deleted'], $this->settings['base_url']."app=core&amp;module=modcp&amp;tab=announcements&amp;fromapp=forums" );
	}
	
	/**
	 * Show announcement form
	 *
	 * @param	string	$type	Add or edit
	 * @param	string	$msg	Message to show
	 * @return	@e string
	 */
	protected function _form( $type='add', $msg='' )
	{
		//-----------------------------------------
		// INIT the editor/bbcode classes
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'announcement';
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$announce	= array( 'announce_active' => 1, 'announce_id' => 0 );
		}
		else
		{
			$announce_id				= intval( $this->request['announce_id'] );
			$announce					= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'announcements', 'where' => 'announce_id='.$announce_id ) );
			$announce['announce_forum']	= explode( ",", $announce['announce_forum'] );
			$announce['announce_start']	= $announce['announce_start'] ? strftime( '%m-%d-%Y', $announce['announce_start'] ) : '';
			$announce['announce_end']	= $announce['announce_end']   ? strftime( '%m-%d-%Y', $announce['announce_end'] ) : '';
		}

		//-----------------------------------------
		// Do we have _POST?
		//-----------------------------------------
		
		foreach( array( 'announce_html_enabled', 'announce_title', 'announce_post', 'announce_start', 'announce_end', 'announce_forum', 'announce_active' ) as $bit )
		{
			if ( !empty($this->request[$bit]) )
			{
				$announce[ $bit ]	= $bit == 'announce_post' ? $_POST[ $bit ] : $this->request[ $bit ];
			}
			else if( !isset($announce[ $bit] ) )
			{
				$announce[ $bit ]	= null;
			}
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor			= new $classToLoad();
		$editor->setAllowHtml( $announce['announce_html_enabled'] );

		$_editor		= $editor->show( 'announce_post', array( 'type' => 'full' ), $announce['announce_post'] );

		//-----------------------------------------
		// Forums
		//-----------------------------------------
		
		$forum_html	= "<option value='*'>" . $this->lang->words['announce_form_allforums'] . "</option>" . $this->registry->getClass('class_forums')->forumsForumJump( false, true, true );
		
		//-----------------------------------------
		// Save forums?
		//-----------------------------------------
		
		if ( is_array( $announce['announce_forum'] ) and count( $announce['announce_forum'] ) )
		{
			foreach( $announce['announce_forum'] as $f )
			{
				$forum_html = preg_replace( "#option\s+value=[\"'](".preg_quote($f,'#').")[\"']#i", "option value='\\1' selected='selected'", $forum_html );
			}
		}
		
		$announce['announce_active_checked'] = $announce['announce_active'] 	  ? 'checked="checked"'  : '';
		$announce['html_checkbox']			 = $announce['announce_html_enabled'] ? "checked='checked' " : '';
		$announce['nlbr_checkbox']			 = $announce['announce_nlbr_enabled'] ? "checked='checked' " : '';

		return $this->registry->getClass('output')->getTemplate('modcp')->modAnnounceForm( $announce, $forum_html, $type, $_editor, $msg );
	}

	/**
	 * Add or update an announcement
	 *
	 * @param	string	$type	add|edit
	 * @return	@e void
	 */
	protected function _save( $type='add' )
	{
		
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10311903, null, null, 403 );
		}		
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$forums_to_save	= "";
		$start_date		= 0;
		$end_date		= 0;
		$announce_id	= intval( $this->request['announce_id'] );

		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( ! $this->request['announce_title'] or ! $this->request['announce_post'] )
		{
			return $this->_form( $type, $this->lang->words['announce_error_title'] );
		}
		
		//-----------------------------------------
		// Get forums to add announce in
		//-----------------------------------------
		
		if ( is_array( $this->request['announce_forum'] ) and count( $this->request['announce_forum'] ) )
		{
			if ( in_array( '*', $this->request['announce_forum'] ) )
			{
				$forums_to_save = '*';
			}
			else
			{
				$forums_to_save = implode( ",", $this->request['announce_forum'] );
			}
		}

		if ( ! $forums_to_save )
		{
			return $this->_form( $type, $this->lang->words['announce_error_forums'] );
		}
		
		//-----------------------------------------
		// Check Dates
		//-----------------------------------------
		
		if ( strstr( $this->request['announce_start'], '-' ) )
		{
			$start_array = explode( '-', $this->request['announce_start'] );
			
			if ( $start_array[0] and $start_array[1] and $start_array[2] )
			{
				if ( ! checkdate( $start_array[0], $start_array[1], $start_array[2] ) )
				{
					return $this->_form( $type, $this->lang->words['announce_error_date'] );
				}
			}

			$start_date = mktime( 12, 0, 0, $start_array[0], $start_array[1], $start_array[2] );
		}
		
		if ( strstr( $this->request['announce_end'], '-' ) )
		{
			$end_array = explode( '-', $this->request['announce_end']  );
			
			if ( $end_array[0] and $end_array[1] and $end_array[2] )
			{
				if ( ! checkdate( $end_array[0], $end_array[1], $end_array[2] ) )
				{
					return $this->_form( $type, $this->lang->words['announce_error_date'] );
				}
			}

			$end_date = mktime( 12, 0, 0, $end_array[0], $end_array[1], $end_array[2] );
		}
		
		//-----------------------------------------
		// Sort out the content
		//-----------------------------------------
		
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor				= new $classToLoad();
		$editor->setAllowBbcode( true );
		$editor->setAllowHtml( intval($this->request['announce_html_enabled']) );

		$announceContent	= $editor->process( $_POST['announce_post'] );

		IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html		= intval($this->request['announce_html_enabled']);
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= $this->request['announce_html_enabled'] ? $this->request['announce_nlbr_enabled'] : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'announcement';
		
		$announceContent	= IPSText::getTextClass( 'bbcode' )->preDbParse( $announceContent );

		//-----------------------------------------
		// Build save array
		//-----------------------------------------
		
		$save_array = array( 'announce_title'			=> $this->request['announce_title'],
							 'announce_post'			=> $announceContent,
							 'announce_active'			=> $this->request['announce_active'] ? $this->request['announce_active'] : 0,
							 'announce_forum'			=> $forums_to_save,
							 'announce_html_enabled'	=> $this->request['announce_html_enabled'] ? $this->request['announce_html_enabled'] : 0,
							 'announce_nlbr_enabled'	=> $this->request['announce_nlbr_enabled'] ? $this->request['announce_nlbr_enabled'] : 0,
							 'announce_start'			=> $start_date,
							 'announce_end'				=> $end_date,
							 'announce_seo_title'		=> IPSText::makeSeoTitle( $this->request['announce_title'] ),
						   );
						   
		//-----------------------------------------
		// Save..
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$save_array['announce_member_id']	= $this->memberData['member_id'];
			
			$this->DB->insert( 'announcements', $save_array );
		}
		else
		{
			if ( $announce_id )
			{
				$this->DB->update( 'announcements', $save_array, 'announce_id=' . $announce_id );
			}
		}
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
		
		$this->registry->cache()->rebuildCache( 'announcements', 'forums' );
		
		$this->registry->getClass('output')->redirectScreen( $this->lang->words['announcement_saved'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;tab=announcements&amp;fromapp=forums" );
	}
}